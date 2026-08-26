<?php

namespace App\Controllers;

use App\Services\Auth;
use App\Services\Database;

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
            SELECT m.*, s.title, s.provider_name, s.host_country, s.application_deadline, s.funding_type, s.slug, c.name as host_country_name
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
            $m['matched_criteria'] = json_decode($m['matched_criteria'], true) ?: [];
            $m['failed_criteria'] = json_decode($m['failed_criteria'], true) ?: [];
            $m['missing_criteria'] = json_decode($m['missing_criteria'], true) ?: [];
        }

        $readinessService = new \App\Services\DocumentReadinessService();
        $docReadiness = $readinessService->calculateGlobal($user['id']);

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
            ]
        ]);
    }

    /**
     * GET /api/matches
     * JSON endpoint for the current user's matches
     */
    public function matchesApi(): void {
        header('Content-Type: application/json');
        
        if (!Auth::check()) {
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

        echo json_encode(['matches' => $matches]);
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
}
