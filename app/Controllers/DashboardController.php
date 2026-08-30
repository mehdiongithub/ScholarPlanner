<?php

namespace App\Controllers;

use App\Services\Auth;
use App\Services\Database;
use PDO;

class DashboardController {
    /**
     * Display student/visitor dashboard
     */
    public function index(): void {
        Auth::requireRole('visitor');

        $user = Auth::currentUser();
        $db = Database::connection();

        // 1. Fetch completion percentage
        $stmt = $db->prepare("SELECT profile_completion_percentage FROM student_profiles WHERE user_id = :user_id LIMIT 1");
        $stmt->execute(['user_id' => $user['id']]);
        $completion = $stmt->fetchColumn() ?: 0;

        // 2. Fetch subscription tier
        $stmtSub = $db->prepare("
            SELECT s.status, s.ends_at, p.name as plan_name 
            FROM subscriptions s
            JOIN subscription_plans p ON s.plan_id = p.id
            WHERE s.user_id = :user_id
            ORDER BY s.id DESC LIMIT 1
        ");
        $stmtSub->execute(['user_id' => $user['id']]);
        $sub = $stmtSub->fetch();

        // 3. Inputs
        $filter = trim($_GET['filter'] ?? 'all');
        $sort = trim($_GET['sort'] ?? 'match_score');

        // Whitelist sort fields
        if (!in_array($sort, ['match_score', 'deadline', 'newest'])) {
            $sort = 'match_score';
        }

        // Check if matches exist. If not, calculate lazy.
        $stmtCount = $db->prepare("SELECT COUNT(*) FROM scholarship_matches WHERE user_id = :user_id");
        $stmtCount->execute(['user_id' => $user['id']]);
        $hasMatches = $stmtCount->fetchColumn() > 0;

        if (!$hasMatches) {
            $matchingService = new \App\Services\ScholarshipMatchingService();
            $matchingService->recalculateForUser($user['id']);
        }

        // Build query to select matches joined with scholarships
        $sql = "
            SELECT m.*, s.title, s.provider_name, s.application_deadline, s.funding_type, s.slug, c.name as host_country_name,
                   (SELECT GROUP_CONCAT(sdl.degree_level SEPARATOR ', ') FROM scholarship_degree_levels sdl WHERE sdl.scholarship_id = s.id) as degree_level,
                   (SELECT GROUP_CONCAT(fs.name SEPARATOR ', ') FROM scholarship_fields sf JOIN fields_of_study fs ON sf.field_of_study_id = fs.id WHERE sf.scholarship_id = s.id) as field_of_study
            FROM scholarship_matches m
            JOIN scholarships s ON m.scholarship_id = s.id
            LEFT JOIN countries c ON s.country_id = c.id
            WHERE m.user_id = :user_id AND s.status = 'published'
        ";

        $params = ['user_id' => $user['id']];

        // Apply filters
        if ($filter === 'highly_recommended') {
            $sql .= " AND m.recommendation_level = 'HIGHLY_RECOMMENDED'";
        } elseif ($filter === 'eligible') {
            $sql .= " AND m.eligibility_status = 'ELIGIBLE'";
        } elseif ($filter === 'possibly_eligible') {
            $sql .= " AND m.eligibility_status = 'POSSIBLY_ELIGIBLE'";
        } elseif ($filter === 'missing') {
            $sql .= " AND m.eligibility_status = 'INSUFFICIENT_DATA'";
        } elseif ($filter === 'closing_soon') {
            $sql .= " AND s.application_deadline >= CURDATE() AND s.application_deadline <= DATE_ADD(CURDATE(), INTERVAL 14 DAY)";
        }

        // Apply sorting
        if ($sort === 'deadline') {
            $sql .= " ORDER BY s.application_deadline ASC";
        } elseif ($sort === 'newest') {
            $sql .= " ORDER BY s.published_at DESC, s.created_at DESC";
        } else {
            $sql .= " ORDER BY m.match_score DESC";
        }

        $stmtMatches = $db->prepare($sql);
        $stmtMatches->execute($params);
        $matches = $stmtMatches->fetchAll(PDO::FETCH_ASSOC);

        // Decode JSON elements
        foreach ($matches as &$m) {
            $m['matched_criteria'] = json_decode((string)($m['matched_criteria'] ?? ''), true) ?: [];
            $m['failed_criteria'] = json_decode((string)($m['failed_criteria'] ?? ''), true) ?: [];
            $m['missing_criteria'] = json_decode((string)($m['missing_criteria'] ?? ''), true) ?: [];
        }

        // Apply Premium Matching feature gate limits
        if (!\App\Services\SubscriptionService::can($user['id'], 'premium_matching')) {
            $matches = array_slice($matches, 0, \App\Services\SubscriptionService::getLimit($user['id'], 'max_matches'));
        }

        $docReadiness = null;
        if (\App\Services\SubscriptionService::can($user['id'], 'document_readiness')) {
            $readinessService = new \App\Services\DocumentReadinessService();
            $docReadiness = $readinessService->calculateGlobal($user['id']);
        }

        // 4. Query statistics counts
        $stmtMatchesCount = $db->prepare("
            SELECT COUNT(*) FROM scholarship_matches m
            JOIN scholarships s ON m.scholarship_id = s.id
            WHERE m.user_id = :user_id AND s.status = 'published'
        ");
        $stmtMatchesCount->execute(['user_id' => $user['id']]);
        $matchesCount = (int)$stmtMatchesCount->fetchColumn();

        $stmtSavedCount = $db->prepare("
            SELECT COUNT(*) FROM saved_scholarships ss
            JOIN scholarships s ON ss.scholarship_id = s.id
            WHERE ss.user_id = :user_id AND s.status = 'published'
        ");
        $stmtSavedCount->execute(['user_id' => $user['id']]);
        $savedCount = (int)$stmtSavedCount->fetchColumn();

        $stmtAppsCount = $db->prepare("
            SELECT COUNT(*) FROM scholarship_applications sa
            JOIN scholarships s ON sa.scholarship_id = s.id
            WHERE sa.user_id = :user_id
        ");
        $stmtAppsCount->execute(['user_id' => $user['id']]);
        $appsCount = (int)$stmtAppsCount->fetchColumn();

        $stmtDeadlinesCount = $db->prepare("
            SELECT COUNT(DISTINCT s.id)
            FROM scholarships s
            LEFT JOIN scholarship_matches m ON m.scholarship_id = s.id AND m.user_id = :user_id_m
            LEFT JOIN saved_scholarships ss ON ss.scholarship_id = s.id AND ss.user_id = :user_id_s
            WHERE (m.user_id IS NOT NULL OR ss.user_id IS NOT NULL)
              AND s.status = 'published'
              AND s.application_deadline >= CURDATE()
        ");
        $stmtDeadlinesCount->execute([
            'user_id_m' => $user['id'],
            'user_id_s' => $user['id']
        ]);
        $deadlinesCount = (int)$stmtDeadlinesCount->fetchColumn();

        // 5. Query lists
        $stmtDeadlines = $db->prepare("
            SELECT DISTINCT s.id, s.title, s.application_deadline, s.slug, s.provider_name
            FROM scholarships s
            LEFT JOIN scholarship_matches m ON m.scholarship_id = s.id AND m.user_id = :user_id_m
            LEFT JOIN saved_scholarships ss ON ss.scholarship_id = s.id AND ss.user_id = :user_id_s
            WHERE (m.user_id IS NOT NULL OR ss.user_id IS NOT NULL)
              AND s.status = 'published'
              AND s.application_deadline >= CURDATE()
            ORDER BY s.application_deadline ASC
            LIMIT 5
        ");
        $stmtDeadlines->execute([
            'user_id_m' => $user['id'],
            'user_id_s' => $user['id']
        ]);
        $upcomingDeadlines = $stmtDeadlines->fetchAll(PDO::FETCH_ASSOC);

        $stmtSaved = $db->prepare("
            SELECT ss.*, s.id as scholarship_id, s.title, s.provider_name, s.application_deadline, s.funding_type, s.slug, c.name as host_country_name,
                   (SELECT GROUP_CONCAT(sdl.degree_level SEPARATOR ', ') FROM scholarship_degree_levels sdl WHERE sdl.scholarship_id = s.id) as degree_level,
                   (SELECT GROUP_CONCAT(fs.name SEPARATOR ', ') FROM scholarship_fields sf JOIN fields_of_study fs ON sf.field_of_study_id = fs.id WHERE sf.scholarship_id = s.id) as field_of_study
            FROM saved_scholarships ss
            JOIN scholarships s ON ss.scholarship_id = s.id
            LEFT JOIN countries c ON s.country_id = c.id
            WHERE ss.user_id = :user_id AND s.status = 'published'
            ORDER BY ss.created_at DESC
            LIMIT 5
        ");
        $stmtSaved->execute(['user_id' => $user['id']]);
        $savedScholarships = $stmtSaved->fetchAll(PDO::FETCH_ASSOC);

        $stmtApps = $db->prepare("
            SELECT sa.*, s.title, s.provider_name, s.application_deadline, s.slug, s.funding_type
            FROM scholarship_applications sa
            JOIN scholarships s ON sa.scholarship_id = s.id
            WHERE sa.user_id = :user_id
            ORDER BY sa.created_at DESC
            LIMIT 5
        ");
        $stmtApps->execute(['user_id' => $user['id']]);
        $recentApplications = $stmtApps->fetchAll(PDO::FETCH_ASSOC);

        // Map bookmarks status
        $stmtSavedIds = $db->prepare("SELECT scholarship_id FROM saved_scholarships WHERE user_id = :uid");
        $stmtSavedIds->execute(['uid' => $user['id']]);
        $savedIds = $stmtSavedIds->fetchAll(PDO::FETCH_COLUMN) ?: [];
        foreach ($matches as &$m) {
            $m['is_saved'] = in_array((int)$m['scholarship_id'], $savedIds);
        }

        view('auth.dashboard', [
            'user' => $user,
            'completion' => $completion,
            'docReadiness' => $docReadiness,
            'matches' => $matches,
            'filter' => $filter,
            'sort' => $sort,
            'subscription' => $sub ?: [
                'plan_name' => 'None (Free Guest)',
                'status' => 'inactive',
                'ends_at' => null
            ],
            'savedCount' => $savedCount,
            'appsCount' => $appsCount,
            'deadlinesCount' => $deadlinesCount,
            'matchesCount' => $matchesCount,
            'upcomingDeadlines' => $upcomingDeadlines,
            'savedScholarships' => $savedScholarships,
            'recentApplications' => $recentApplications,
            'csrf_token' => \App\Helpers\Security::csrfToken()
        ]);
    }

    /**
     * GET /api/matches
     * JSON endpoint for the current user's matches
     */
    public function matchesApi(): void {
        header('Content-Type: application/json');
        
        if (!Auth::isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit();
        }

        $user = Auth::currentUser();
        $db = Database::connection();

        // Calculate if empty
        $stmtCount = $db->prepare("SELECT COUNT(*) FROM scholarship_matches WHERE user_id = :user_id");
        $stmtCount->execute(['user_id' => $user['id']]);
        if ($stmtCount->fetchColumn() == 0) {
            $matchingService = new \App\Services\ScholarshipMatchingService();
            $matchingService->recalculateForUser($user['id']);
        }

        // Fetch user matches
        $stmt = $db->prepare("
            SELECT m.*, s.title, s.provider_name, s.application_deadline, s.slug
            FROM scholarship_matches m
            JOIN scholarships s ON m.scholarship_id = s.id
            WHERE m.user_id = :user_id AND s.status = 'published'
            ORDER BY m.match_score DESC
        ");
        $stmt->execute(['user_id' => $user['id']]);
        $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Decode JSON fields for structured output
        foreach ($matches as &$m) {
            $m['matched_criteria'] = json_decode($m['matched_criteria'], true) ?: [];
            $m['failed_criteria'] = json_decode($m['failed_criteria'], true) ?: [];
            $m['missing_criteria'] = json_decode($m['missing_criteria'], true) ?: [];
        }

        // Apply Premium Matching feature gate limits
        if (!\App\Services\SubscriptionService::can($user['id'], 'premium_matching')) {
            $matches = array_slice($matches, 0, \App\Services\SubscriptionService::getLimit($user['id'], 'max_matches'));
        }



        echo json_encode(['matches' => $matches]);
        if (defined('TESTING_MODE') && TESTING_MODE) {
            return;
        }
        exit();
    }

    /**
     * GET /admin/matches/test
     * Diagnostic testing view for admins/employees to verify engine rules step-by-step
     */
    public function matchesDiagnostic(): void {
        Auth::requireRole(['admin', 'employee']);
        
        $db = Database::connection();
        
        // 1. Fetch lists of users and published scholarships
        $users = $db->query("
            SELECT u.id, u.email, u.first_name, u.last_name, r.name as role_name
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE r.name = 'visitor'
            ORDER BY u.email ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        $scholarships = $db->query("
            SELECT id, title, provider_name
            FROM scholarships
            WHERE status = 'published'
            ORDER BY title ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $selectedUserId = !empty($_GET['user_id']) ? (int)$_GET['user_id'] : null;
        $selectedSchId = !empty($_GET['scholarship_id']) ? (int)$_GET['scholarship_id'] : null;
        
        $result = null;
        if ($selectedUserId && $selectedSchId) {
            $matchingService = new \App\Services\ScholarshipMatchingService();
            $result = $matchingService->matchUserAndScholarship($selectedUserId, $selectedSchId);
        }

        view('admin.matches_test', [
            'users' => $users,
            'scholarships' => $scholarships,
            'selectedUserId' => $selectedUserId,
            'selectedSchId' => $selectedSchId,
            'result' => $result
        ]);
    }

    /**
     * Display Admin Dashboard
     */
    public function admin(): void {
        Auth::requireRole('admin');

        $user = Auth::currentUser();
        $db = Database::connection();

        // Fetch basic counts for admin panel stats
        $usersCount = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $scholarshipsCount = $db->query("SELECT COUNT(*) FROM scholarships")->fetchColumn();
        $matchesCount = $db->query("SELECT COUNT(*) FROM scholarship_matches")->fetchColumn();
        $logsCount = $db->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn();

        view('auth.admin', [
            'user' => $user,
            'stats' => [
                'users_count' => $usersCount,
                'scholarships_count' => $scholarshipsCount,
                'matches_count' => $matchesCount,
                'logs_count' => $logsCount
            ]
        ]);
    }

    /**
     * Display Employee Dashboard
     */
    public function employee(): void {
        // Enforce database-driven permissions (requires scholarships.view permission)
        Auth::requirePermission('scholarships.view');

        $user = Auth::currentUser();
        $db = Database::connection();

        // Fetch verification queue stats
        $pendingReviews = $db->query("SELECT COUNT(*) FROM scholarships WHERE verification_status = 'pending'")->fetchColumn();
        $assignedTasks = $db->prepare("SELECT COUNT(*) FROM employee_assignments WHERE employee_id = :id AND status = 'assigned'");
        $assignedTasks->execute(['id' => $user['id']]);
        $tasksCount = $assignedTasks->fetchColumn();

        view('auth.employee', [
            'user' => $user,
            'pending_reviews' => $pendingReviews,
            'assigned_tasks' => $tasksCount
        ]);
    }

    private function redirect(string $url): void {
        if (defined('TESTING_MODE') && TESTING_MODE) {
            throw new \RuntimeException("Redirect to " . $url);
        }
        header("Location: " . $url);
        exit();
    }

    /**
     * GET /admin/institutions
     */
    public function adminInstitutionsIndex(): void {
        Auth::requireRole('admin');
        $db = Database::connection();

        $stmt = $db->query("
            SELECT i.*, c.name as country_name, s.name as state_name, ci.name as city_name
            FROM institutions i
            LEFT JOIN countries c ON i.country_id = c.id
            LEFT JOIN states s ON i.state_id = s.id
            LEFT JOIN cities ci ON i.city_id = ci.id
            ORDER BY i.id DESC
        ");
        $institutions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        view('admin.institutions.index', [
            'institutions' => $institutions
        ]);
    }

    /**
     * GET /admin/institutions/create
     */
    public function adminInstitutionsCreate(): void {
        Auth::requireRole('admin');
        $db = Database::connection();

        $countries = $db->query("SELECT id, name FROM countries ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

        view('admin.institutions.create', [
            'countries' => $countries
        ]);
    }

    /**
     * POST /admin/institutions
     */
    public function adminInstitutionsStore(): void {
        Auth::requireRole('admin');
        
        $csrf = $_POST['csrf_token'] ?? null;
        if (!\App\Services\Security::verifyCsrfToken($csrf)) {
            $_SESSION['admin_errors'] = ['csrf' => 'CSRF verification failed.'];
            $this->redirect(url('/admin/institutions/create'));
        }

        $db = Database::connection();

        $name = trim($_POST['name'] ?? '');
        $type = trim($_POST['institution_type'] ?? '');
        $countryId = !empty($_POST['country_id']) ? (int)$_POST['country_id'] : null;
        $coverageType = trim($_POST['coverage_type'] ?? 'state');
        $status = trim($_POST['status'] ?? 'approved');
        $cityId = !empty($_POST['city_id']) ? (int)$_POST['city_id'] : null;

        $errors = [];
        if (empty($name)) $errors['name'] = 'Name is required.';
        if (empty($type)) $errors['institution_type'] = 'Institution type is required.';
        if ($countryId === null) $errors['country_id'] = 'Country is required.';

        $states = $_POST['states'] ?? [];
        if (!is_array($states)) {
            $states = [$states];
        }
        $states = array_filter(array_map('intval', $states));

        if ($coverageType === 'state') {
            if (count($states) !== 1) {
                $errors['states'] = 'Exactly one state is required for State Specific coverage.';
            }
        } elseif ($coverageType === 'multi_state') {
            if (count($states) < 1) {
                $errors['states'] = 'At least one state is required for Multiple States coverage.';
            }
        }

        if (!empty($errors)) {
            $_SESSION['admin_errors'] = $errors;
            $this->redirect(url('/admin/institutions/create'));
        }

        // The primary state_id in institutions table (for single state)
        $primaryStateId = ($coverageType === 'state' && !empty($states)) ? $states[0] : null;

        try {
            $db->beginTransaction();

            $stmt = $db->prepare("
                INSERT INTO institutions (name, institution_type, country_id, state_id, city_id, coverage_type, status, created_at, updated_at)
                VALUES (:name, :type, :country_id, :state_id, :city_id, :coverage_type, :status, NOW(), NOW())
            ");
            $stmt->execute([
                'name' => $name,
                'type' => $type,
                'country_id' => $countryId,
                'state_id' => $primaryStateId,
                'city_id' => $cityId,
                'coverage_type' => $coverageType,
                'status' => $status
            ]);
            $instId = $db->lastInsertId();

            // Insert relations if not national
            if ($coverageType !== 'national') {
                $stmtRel = $db->prepare("INSERT INTO institution_states (institution_id, state_id) VALUES (:inst_id, :state_id)");
                foreach ($states as $sId) {
                    $stmtRel->execute([
                        'inst_id' => $instId,
                        'state_id' => $sId
                    ]);
                }
            }

            $db->commit();
            $_SESSION['admin_success'] = 'Institution created successfully.';
            $this->redirect(url('/admin/institutions'));
        } catch (\Exception $e) {
            $db->rollBack();
            $_SESSION['admin_errors'] = ['db' => 'Failed to save institution: ' . $e->getMessage()];
            $this->redirect(url('/admin/institutions/create'));
        }
    }

    /**
     * GET /admin/institutions/{id}/edit
     */
    public function adminInstitutionsEdit(string $id): void {
        Auth::requireRole('admin');
        $db = Database::connection();

        $instId = (int)$id;
        $stmt = $db->prepare("SELECT * FROM institutions WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $instId]);
        $institution = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$institution) {
            http_response_code(404);
            echo "Institution not found.";
            exit();
        }

        $countries = $db->query("SELECT id, name FROM countries ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

        // Fetch current states for this institution
        $stmtStates = $db->prepare("SELECT state_id FROM institution_states WHERE institution_id = :id");
        $stmtStates->execute(['id' => $instId]);
        $selectedStates = $stmtStates->fetchAll(PDO::FETCH_COLUMN) ?: [];

        $states = [];
        if (!empty($institution['country_id'])) {
            $stmtStatesList = $db->prepare("SELECT id, name FROM states WHERE country_id = :cid ORDER BY name ASC");
            $stmtStatesList->execute(['cid' => $institution['country_id']]);
            $states = $stmtStatesList->fetchAll(PDO::FETCH_ASSOC);
        }

        $cities = [];
        if (!empty($institution['state_id'])) {
            $stmtCitiesList = $db->prepare("SELECT id, name FROM cities WHERE state_id = :sid ORDER BY name ASC");
            $stmtCitiesList->execute(['sid' => $institution['state_id']]);
            $cities = $stmtCitiesList->fetchAll(PDO::FETCH_ASSOC);
        }

        view('admin.institutions.edit', [
            'institution' => $institution,
            'countries' => $countries,
            'selectedStates' => $selectedStates,
            'states' => $states,
            'cities' => $cities
        ]);
    }

    /**
     * POST /admin/institutions/{id}/update
     */
    public function adminInstitutionsUpdate(string $id): void {
        Auth::requireRole('admin');
        
        $csrf = $_POST['csrf_token'] ?? null;
        if (!\App\Services\Security::verifyCsrfToken($csrf)) {
            $_SESSION['admin_errors'] = ['csrf' => 'CSRF verification failed.'];
            $this->redirect(url("/admin/institutions/{$id}/edit"));
        }

        $db = Database::connection();
        $instId = (int)$id;

        $name = trim($_POST['name'] ?? '');
        $type = trim($_POST['institution_type'] ?? '');
        $countryId = !empty($_POST['country_id']) ? (int)$_POST['country_id'] : null;
        $coverageType = trim($_POST['coverage_type'] ?? 'state');
        $status = trim($_POST['status'] ?? 'approved');
        $cityId = !empty($_POST['city_id']) ? (int)$_POST['city_id'] : null;

        $errors = [];
        if (empty($name)) $errors['name'] = 'Name is required.';
        if (empty($type)) $errors['institution_type'] = 'Institution type is required.';
        if ($countryId === null) $errors['country_id'] = 'Country is required.';

        $states = $_POST['states'] ?? [];
        if (!is_array($states)) {
            $states = [$states];
        }
        $states = array_filter(array_map('intval', $states));

        if ($coverageType === 'state') {
            if (count($states) !== 1) {
                $errors['states'] = 'Exactly one state is required for State Specific coverage.';
            }
        } elseif ($coverageType === 'multi_state') {
            if (count($states) < 1) {
                $errors['states'] = 'At least one state is required for Multiple States coverage.';
            }
        }

        if (!empty($errors)) {
            $_SESSION['admin_errors'] = $errors;
            $this->redirect(url("/admin/institutions/{$id}/edit"));
        }

        // The primary state_id in institutions table (for single state)
        $primaryStateId = ($coverageType === 'state' && !empty($states)) ? $states[0] : null;

        try {
            $db->beginTransaction();

            $stmt = $db->prepare("
                UPDATE institutions 
                SET name = :name, 
                    institution_type = :type, 
                    country_id = :country_id, 
                    state_id = :state_id, 
                    city_id = :city_id, 
                    coverage_type = :coverage_type, 
                    status = :status,
                    updated_at = NOW()
                WHERE id = :id
            ");
            $stmt->execute([
                'name' => $name,
                'type' => $type,
                'country_id' => $countryId,
                'state_id' => $primaryStateId,
                'city_id' => $cityId,
                'coverage_type' => $coverageType,
                'status' => $status,
                'id' => $instId
            ]);

            // Sync relations
            $db->prepare("DELETE FROM institution_states WHERE institution_id = :id")->execute(['id' => $instId]);
            if ($coverageType !== 'national') {
                $stmtRel = $db->prepare("INSERT INTO institution_states (institution_id, state_id) VALUES (:inst_id, :state_id)");
                foreach ($states as $sId) {
                    $stmtRel->execute([
                        'inst_id' => $instId,
                        'state_id' => $sId
                    ]);
                }
            }

            $db->commit();
            $_SESSION['admin_success'] = 'Institution updated successfully.';
            $this->redirect(url('/admin/institutions'));
        } catch (\Exception $e) {
            $db->rollBack();
            $_SESSION['admin_errors'] = ['db' => 'Failed to update institution: ' . $e->getMessage()];
            $this->redirect(url("/admin/institutions/{$id}/edit"));
        }
    }

    /**
     * POST /admin/institutions/{id}/delete
     */
    public function adminInstitutionsDelete(string $id): void {
        Auth::requireRole('admin');
        
        $csrf = $_POST['csrf_token'] ?? null;
        if (!\App\Services\Security::verifyCsrfToken($csrf)) {
            $_SESSION['admin_errors'] = ['csrf' => 'CSRF verification failed.'];
            $this->redirect(url('/admin/institutions'));
        }

        $db = Database::connection();
        $instId = (int)$id;

        try {
            $db->prepare("DELETE FROM institutions WHERE id = :id")->execute(['id' => $instId]);
            $_SESSION['admin_success'] = 'Institution deleted successfully.';
        } catch (\Exception $e) {
            $_SESSION['admin_errors'] = ['db' => 'Failed to delete institution: ' . $e->getMessage()];
        }
        
        $this->redirect(url('/admin/institutions'));
    }
}
