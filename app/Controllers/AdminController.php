<?php

namespace App\Controllers;

use PDO;
use Exception;
use App\Services\Auth;
use App\Services\Database;
use App\Helpers\Security;
use App\Helpers\View;

class AdminController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::connection();
    }

    private function resolveId(string $id, bool $isAjax = false): int {
        $raw = decode_id($id);
        if ($raw === null && is_numeric($id) && (int)$id > 0) {
            $raw = (int)$id;
        }
        if ($raw === null) {
            if ($isAjax || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
                http_response_code(404);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Resource not found']);
                exit();
            }
            http_response_code(404);
            try {
                view('errors.404');
            } catch (\Exception $e) {
                echo "<h1>404 Not Found</h1>";
            }
            exit();
        }
        return $raw;
    }

    /**
     * Helper to write audit logs
     */
    private function logAction(string $action, string $module, string $resType, ?int $resId, ?array $metadata = null): void {
        Auth::logAudit(Auth::userId(), $action, $module, $resType, $resId, null, $metadata);
    }

    /**
     * GET /admin
     * Main Admin Dashboard overview with dynamic statistics
     */
    public function dashboard(): void {
        Auth::requireRole(['admin', 'employee']);

        // Fetch dashboard statistics counts
        $totalUsers = (int)$this->db->query("SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id = r.id WHERE r.name = 'visitor'")->fetchColumn();
        $verifiedUsers = (int)$this->db->query("SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id = r.id WHERE r.name = 'visitor' AND u.email_verified_at IS NOT NULL")->fetchColumn();
        $pendingUsers = (int)$this->db->query("SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id = r.id WHERE r.name = 'visitor' AND u.email_verified_at IS NULL")->fetchColumn();
        $suspendedUsers = (int)$this->db->query("SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id = r.id WHERE r.name = 'visitor' AND u.status = 'suspended'")->fetchColumn();

        $totalScholarships = (int)$this->db->query("SELECT COUNT(*) FROM scholarships")->fetchColumn();
        $activeScholarships = (int)$this->db->query("SELECT COUNT(*) FROM scholarships WHERE status = 'published' AND (application_deadline IS NULL OR application_deadline >= CURDATE())")->fetchColumn();
        $pendingScholarships = (int)$this->db->query("SELECT COUNT(*) FROM scholarships WHERE status = 'pending_review'")->fetchColumn();
        $expiredScholarships = (int)$this->db->query("SELECT COUNT(*) FROM scholarships WHERE status = 'published' AND application_deadline < CURDATE()")->fetchColumn();
        $draftScholarships = (int)$this->db->query("SELECT COUNT(*) FROM scholarships WHERE status = 'draft'")->fetchColumn();
        $archivedScholarships = (int)$this->db->query("SELECT COUNT(*) FROM scholarships WHERE status = 'archived'")->fetchColumn();

        $totalApps = (int)$this->db->query("SELECT COUNT(*) FROM scholarship_applications")->fetchColumn();
        $pendingApps = (int)$this->db->query("SELECT COUNT(*) FROM scholarship_applications WHERE status = 'submitted' OR status = 'pending'")->fetchColumn();
        $pendingInst = (int)$this->db->query("SELECT COUNT(*) FROM institutions WHERE status = 'pending'")->fetchColumn();

        $activeSubs = (int)$this->db->query("SELECT COUNT(*) FROM subscriptions WHERE status = 'active'")->fetchColumn();
        $paymentIssues = (int)$this->db->query("SELECT COUNT(*) FROM payment_transactions WHERE status = 'failed'")->fetchColumn();

        $revenueRows = $this->db->query("
            SELECT currency, SUM(amount) as total 
            FROM payment_transactions 
            WHERE status = 'paid' 
            GROUP BY currency
        ")->fetchAll(PDO::FETCH_ASSOC);

        $revenueStrings = [];
        foreach ($revenueRows as $row) {
            $revenueStrings[] = htmlspecialchars($row['currency']) . ' ' . number_format($row['total'], 2);
        }
        $formattedRevenue = !empty($revenueStrings) ? implode(' | ', $revenueStrings) : 'USD 0.00';

        $newStudentsThisMonth = (int)$this->db->query("
            SELECT COUNT(*) 
            FROM users u 
            JOIN roles r ON u.role_id = r.id 
            WHERE r.name = 'visitor' AND u.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ")->fetchColumn();

        $newScholarshipsThisMonth = (int)$this->db->query("
            SELECT COUNT(*) 
            FROM scholarships 
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ")->fetchColumn();

        $newApplicationsThisMonth = (int)$this->db->query("
            SELECT COUNT(*) 
            FROM scholarship_applications 
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ")->fetchColumn();

        $newSubscriptionsThisMonth = (int)$this->db->query("
            SELECT COUNT(*) 
            FROM subscriptions 
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ")->fetchColumn();

        // Fetch recent actions / audit logs
        $recentLogs = $this->db->query("
            SELECT l.*, u.email as actor_email, u.first_name, u.last_name
            FROM audit_logs l
            LEFT JOIN users u ON l.user_id = u.id
            ORDER BY l.id DESC
            LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Recent Scholarships (10)
        $recentScholarships = $this->db->query("
            SELECT s.*, c.name as country_name 
            FROM scholarships s
            LEFT JOIN countries c ON s.country_id = c.id
            ORDER BY s.id DESC
            LIMIT 10
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Recent Students (10)
        $recentStudents = $this->db->query("
            SELECT u.*, 
                   (SELECT profile_completion_percentage FROM student_profiles sp WHERE sp.user_id = u.id) as completion_percentage
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE r.name = 'visitor'
            ORDER BY u.id DESC
            LIMIT 10
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Fetch pending items alerts list
        $alerts = [];
        if ($pendingScholarships > 0) {
            $alerts[] = ['text' => "$pendingScholarships scholarships awaiting review.", 'link' => '/admin/scholarships?status=pending_review', 'type' => 'warning'];
        }
        if ($pendingInst > 0) {
            $alerts[] = ['text' => "$pendingInst new institutions awaiting approval.", 'link' => '/admin/institutions', 'type' => 'info'];
        }

        View::render('admin.dashboard', [
            'user' => Auth::currentUser(),
            'stats' => [
                'total_users' => $totalUsers,
                'verified_users' => $verifiedUsers,
                'pending_users' => $pendingUsers,
                'suspended_users' => $suspendedUsers,
                'total_scholarships' => $totalScholarships,
                'active_scholarships' => $activeScholarships,
                'pending_scholarships' => $pendingScholarships,
                'expired_scholarships' => $expiredScholarships,
                'draft_scholarships' => $draftScholarships,
                'archived_scholarships' => $archivedScholarships,
                'total_applications' => $totalApps,
                'pending_applications' => $pendingApps,
                'pending_institutions' => $pendingInst,
                'active_subscriptions' => $activeSubs,
                'formatted_revenue' => $formattedRevenue,
                'payment_issues' => $paymentIssues,
                'new_students_30d' => $newStudentsThisMonth,
                'new_scholarships_30d' => $newScholarshipsThisMonth,
                'new_applications_30d' => $newApplicationsThisMonth,
                'new_subscriptions_30d' => $newSubscriptionsThisMonth
            ],
            'recent_logs' => $recentLogs,
            'recent_scholarships' => $recentScholarships,
            'recent_students' => $recentStudents,
            'alerts' => $alerts,
            'csrf_token' => Security::csrfToken()
        ]);
    }

    // ==========================================
    // USER MANAGEMENT
    // ==========================================

    /**
     * GET /admin/users
     * Lists users with search, pagination and filters
     */
    public function usersIndex(): void {
        Auth::requirePermission('users.view');

        $search = trim($_GET['search'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $verified = trim($_GET['verified'] ?? '');
        $plan = trim($_GET['plan'] ?? '');

        // Pagination
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $whereClauses = ["r.name = 'visitor'"]; // Only manage student visitors
        $params = [];

        if ($search !== '') {
            $whereClauses[] = "(u.first_name LIKE :search OR u.last_name LIKE :search OR u.email LIKE :search)";
            $params['search'] = '%' . $search . '%';
        }
        if ($status !== '') {
            $whereClauses[] = "u.status = :status";
            $params['status'] = $status;
        }
        if ($verified === '1') {
            $whereClauses[] = "u.email_verified_at IS NOT NULL";
        } elseif ($verified === '0') {
            $whereClauses[] = "u.email_verified_at IS NULL";
        }
        if ($plan !== '') {
            if ($plan === 'free') {
                $whereClauses[] = "NOT EXISTS (SELECT 1 FROM subscriptions s WHERE s.user_id = u.id AND s.status = 'active')";
            } else {
                $whereClauses[] = "EXISTS (SELECT 1 FROM subscriptions s JOIN subscription_plans p ON s.plan_id = p.id WHERE s.user_id = u.id AND s.status = 'active' AND p.name = :plan)";
                $params['plan'] = $plan;
            }
        }

        $whereSql = "WHERE " . implode(" AND ", $whereClauses);

        // Get total count
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id = r.id $whereSql");
        $countStmt->execute($params);
        $totalCount = (int)$countStmt->fetchColumn();
        $totalPages = ceil($totalCount / $limit);

        // Get records
        $stmt = $this->db->prepare("
            SELECT u.*, 
                   (SELECT profile_completion_percentage FROM student_profiles p WHERE p.user_id = u.id LIMIT 1) as completion,
                   (SELECT p.name FROM subscriptions s JOIN subscription_plans p ON s.plan_id = p.id WHERE s.user_id = u.id AND s.status = 'active' LIMIT 1) as plan_name
            FROM users u
            JOIN roles r ON u.role_id = r.id
            $whereSql
            ORDER BY u.id DESC
            LIMIT $limit OFFSET $offset
        ");
        $stmt->execute($params);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch subscription plans list for filter dropdown
        $plans = $this->db->query("SELECT DISTINCT name FROM subscription_plans ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);

        View::render('admin.users.index', [
            'user' => Auth::currentUser(),
            'users' => $users,
            'plans' => $plans,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalCount' => $totalCount,
            'search' => $search,
            'status' => $status,
            'verified' => $verified,
            'plan' => $plan,
            'csrf_token' => Security::csrfToken()
        ]);
    }

    /**
     * GET /admin/users/{id}
     * Show detailed user profile, preferences, education and matching info
     */
    public function usersShow(string $id): void {
        Auth::requirePermission('users.view');
        $id = $this->resolveId($id);

        // Fetch user basic
        $stmt = $this->db->prepare("
            SELECT u.*, r.name as role_name 
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE u.id = :id AND r.name = 'visitor'
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $targetUser = $stmt->fetch();
        if (!$targetUser) {
            http_response_code(404);
            echo "User not found";
            exit();
        }

        // Fetch profile
        $stmtProfile = $this->db->prepare("SELECT * FROM student_profiles WHERE user_id = :id LIMIT 1");
        $stmtProfile->execute(['id' => $id]);
        $profile = $stmtProfile->fetch() ?: [];

        // Fetch education records
        $stmtEdu = $this->db->prepare("
            SELECT er.*, c.name as country_name, s.name as state_name, ci.name as city_name
            FROM education_records er
            LEFT JOIN countries c ON er.country_id = c.id
            LEFT JOIN states s ON er.state_id = s.id
            LEFT JOIN cities ci ON er.city_id = ci.id
            WHERE er.user_id = :id
            ORDER BY er.start_date DESC
        ");
        $stmtEdu->execute(['id' => $id]);
        $education = $stmtEdu->fetchAll(PDO::FETCH_ASSOC);

        // Fetch preferences
        $stmtPref = $this->db->prepare("SELECT * FROM user_preferences WHERE user_id = :id LIMIT 1");
        $stmtPref->execute(['id' => $id]);
        $preferences = $stmtPref->fetch() ?: [];

        // Fetch active matches
        $stmtMatches = $this->db->prepare("
            SELECT m.*, s.title, s.provider_name, s.slug
            FROM scholarship_matches m
            JOIN scholarships s ON m.scholarship_id = s.id
            WHERE m.user_id = :id AND s.status = 'published'
            ORDER BY m.match_score DESC
        ");
        $stmtMatches->execute(['id' => $id]);
        $matches = $stmtMatches->fetchAll(PDO::FETCH_ASSOC);

        // Fetch applications
        $stmtApps = $this->db->prepare("
            SELECT sa.*, s.title, s.slug
            FROM scholarship_applications sa
            JOIN scholarships s ON sa.scholarship_id = s.id
            WHERE sa.user_id = :id
            ORDER BY sa.id DESC
        ");
        $stmtApps->execute(['id' => $id]);
        $applications = $stmtApps->fetchAll(PDO::FETCH_ASSOC);

        // Fetch subscription
        $stmtSub = $this->db->prepare("
            SELECT s.*, p.name as plan_name
            FROM subscriptions s
            JOIN subscription_plans p ON s.plan_id = p.id
            WHERE s.user_id = :id
            ORDER BY s.id DESC LIMIT 1
        ");
        $stmtSub->execute(['id' => $id]);
        $subscription = $stmtSub->fetch() ?: null;

        View::render('admin.users.show', [
            'user' => Auth::currentUser(),
            'targetUser' => $targetUser,
            'profile' => $profile,
            'education' => $education,
            'preferences' => $preferences,
            'matches' => $matches,
            'applications' => $applications,
            'subscription' => $subscription,
            'csrf_token' => Security::csrfToken()
        ]);
    }

    /**
     * GET /admin/users/{id}/edit
     */
    public function usersEdit(string $id): void {
        Auth::requirePermission('users.edit');
        $id = $this->resolveId($id);

        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $targetUser = $stmt->fetch();
        if (!$targetUser) {
            http_response_code(404);
            echo "User not found";
            exit();
        }

        View::render('admin.users.edit', [
            'user' => Auth::currentUser(),
            'targetUser' => $targetUser,
            'csrf_token' => Security::csrfToken()
        ]);
    }

    /**
     * POST /admin/users/{id}/update
     */
    public function usersUpdate(string $id): void {
        Auth::requirePermission('users.edit');
        $id = $this->resolveId($id);
        $encId = encode_id($id);

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            $_SESSION['admin_errors'] = 'CSRF verification failed.';
            header("Location: " . url("/admin/users/$encId/edit"));
            exit();
        }

        $email = strtolower(trim($_POST['email'] ?? ''));
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $status = trim($_POST['status'] ?? 'active');

        if ($email === '' || $firstName === '') {
            $_SESSION['admin_errors'] = 'Required fields missing.';
            header("Location: " . url("/admin/users/$encId/edit"));
            exit();
        }

        // Check unique email
        $check = $this->db->prepare("SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1");
        $check->execute([$email, $id]);
        if ($check->fetch()) {
            $_SESSION['admin_errors'] = 'Email already registered.';
            header("Location: " . url("/admin/users/$encId/edit"));
            exit();
        }

        $stmt = $this->db->prepare("
            UPDATE users 
            SET email = :email, first_name = :first_name, last_name = :last_name, status = :status
            WHERE id = :id
        ");
        $stmt->execute([
            'email' => $email,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'status' => $status,
            'id' => $id
        ]);

        $this->logAction('user_update', 'users', 'users', $id, ['email' => $email, 'status' => $status]);
        $_SESSION['admin_success'] = 'User account updated successfully.';
        header("Location: " . url("/admin/users/$encId"));
        exit();
    }

    /**
     * POST /admin/users/{id}/suspend
     */
    public function usersSuspend(string $id): void {
        Auth::requirePermission('users.edit');
        $id = $this->resolveId($id, true);

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            http_response_code(400);
            echo json_encode(['error' => 'CSRF verification failed']);
            exit();
        }

        $stmt = $this->db->prepare("UPDATE users SET status = 'suspended' WHERE id = ?");
        $stmt->execute([$id]);

        $this->logAction('user_suspend', 'users', 'users', $id);
        echo json_encode(['success' => true, 'message' => 'User suspended successfully.']);
        exit();
    }

    /**
     * POST /admin/users/{id}/activate
     */
    public function usersActivate(string $id): void {
        Auth::requirePermission('users.edit');
        $id = $this->resolveId($id, true);

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            http_response_code(400);
            echo json_encode(['error' => 'CSRF verification failed']);
            exit();
        }

        $stmt = $this->db->prepare("UPDATE users SET status = 'active' WHERE id = ?");
        $stmt->execute([$id]);

        $this->logAction('user_activate', 'users', 'users', $id);
        echo json_encode(['success' => true, 'message' => 'User activated successfully.']);
        exit();
    }

    /**
     * POST /admin/users/{id}/password
     */
    public function usersResetPassword(string $id): void {
        Auth::requirePermission('users.edit');
        $id = $this->resolveId($id);
        $encId = encode_id($id);

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            $_SESSION['admin_errors'] = 'CSRF verification failed.';
            header("Location: " . url("/admin/users/$encId"));
            exit();
        }

        $pass = trim($_POST['password'] ?? '');
        if (strlen($pass) < 8) {
            $_SESSION['admin_errors'] = 'Password must be at least 8 characters long.';
            header("Location: " . url("/admin/users/$encId"));
            exit();
        }

        $hash = password_hash($pass, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->execute([$hash, $id]);

        $this->logAction('user_password_reset', 'users', 'users', $id);
        $_SESSION['admin_success'] = 'Password changed successfully.';
        header("Location: " . url("/admin/users/$encId"));
        exit();
    }

    /**
     * POST /admin/users/{id}/delete
     */
    public function usersDelete(string $id): void {
        Auth::requirePermission('users.delete');
        $id = $this->resolveId($id, true);

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            http_response_code(400);
            echo json_encode(['error' => 'CSRF verification failed']);
            exit();
        }

        // Soft deactivation is preferred to prevent broken relationships
        $stmt = $this->db->prepare("UPDATE users SET status = 'deleted' WHERE id = ?");
        $stmt->execute([$id]);

        $this->logAction('user_delete', 'users', 'users', $id);
        echo json_encode(['success' => true, 'message' => 'User deleted successfully.']);
    }

    // ==========================================
    // EMPLOYEE & ROLE MANAGEMENT
    // ==========================================

    /**
     * GET /admin/employees
     */
    public function employeesIndex(): void {
        Auth::requirePermission('employees.manage');

        $employees = $this->db->query("
            SELECT u.*, r.name as role_name, r.description as role_desc
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE r.name <> 'visitor'
            ORDER BY u.id DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $roles = $this->db->query("SELECT id, name FROM roles WHERE name <> 'visitor' ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

        View::render('admin.employees.index', [
            'user' => Auth::currentUser(),
            'employees' => $employees,
            'roles' => $roles,
            'csrf_token' => Security::csrfToken()
        ]);
    }

    /**
     * GET /admin/employees/create
     */
    public function employeesCreate(): void {
        Auth::requirePermission('employees.manage');

        $roles = $this->db->query("SELECT id, name FROM roles WHERE name <> 'visitor' ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

        View::render('admin.employees.create', [
            'user' => Auth::currentUser(),
            'roles' => $roles,
            'csrf_token' => Security::csrfToken()
        ]);
    }

    /**
     * POST /admin/employees
     */
    public function employeesStore(): void {
        Auth::requirePermission('employees.manage');

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            $_SESSION['admin_errors'] = 'CSRF verification failed.';
            header("Location: " . url("/admin/employees/create"));
            exit();
        }

        $email = strtolower(trim($_POST['email'] ?? ''));
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $password = $_POST['password'] ?? '';
        $roleId = (int)($_POST['role_id'] ?? 0);

        if ($email === '' || $firstName === '' || $password === '' || $roleId === 0) {
            $_SESSION['admin_errors'] = 'Required fields missing.';
            header("Location: " . url("/admin/employees/create"));
            exit();
        }

        // Validate role exists and is not visitor
        $stmtRole = $this->db->prepare("SELECT id FROM roles WHERE id = ? AND name <> 'visitor' LIMIT 1");
        $stmtRole->execute([$roleId]);
        if (!$stmtRole->fetch()) {
            $_SESSION['admin_errors'] = 'Invalid role selection.';
            header("Location: " . url("/admin/employees/create"));
            exit();
        }

        // Validate email uniqueness
        $check = $this->db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $check->execute([$email]);
        if ($check->fetch()) {
            $_SESSION['admin_errors'] = 'Email already registered.';
            header("Location: " . url("/admin/employees/create"));
            exit();
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("
            INSERT INTO users (email, first_name, last_name, password_hash, role_id, status, email_verified_at)
            VALUES (:email, :first_name, :last_name, :hash, :role_id, 'active', NOW())
        ");
        $stmt->execute([
            'email' => $email,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'hash' => $hash,
            'role_id' => $roleId
        ]);
        $empId = $this->db->lastInsertId();

        $this->logAction('employee_create', 'employees', 'users', $empId, ['email' => $email]);
        $_SESSION['admin_success'] = 'Employee account created successfully.';
        header("Location: " . url("/admin/employees"));
    }

    public function employeesUpdate(string $id): void {
        Auth::requirePermission('employees.manage');
        $id = $this->resolveId($id);

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            $_SESSION['admin_errors'] = 'CSRF verification failed.';
            header("Location: " . url("/admin/employees"));
            exit();
        }

        $roleId = (int)($_POST['role_id'] ?? 0);
        $status = trim($_POST['status'] ?? 'active');

        $stmtRole = $this->db->prepare("SELECT id FROM roles WHERE id = ? AND name <> 'visitor' LIMIT 1");
        $stmtRole->execute([$roleId]);
        if (!$stmtRole->fetch()) {
            $_SESSION['admin_errors'] = 'Invalid role selection.';
            header("Location: " . url("/admin/employees"));
            exit();
        }

        $stmt = $this->db->prepare("UPDATE users SET role_id = :role_id, status = :status WHERE id = :id");
        $stmt->execute([
            'role_id' => $roleId,
            'status' => $status,
            'id' => $id
        ]);

        $this->logAction('employee_update', 'employees', 'users', $id, ['role_id' => $roleId, 'status' => $status]);
        $_SESSION['admin_success'] = 'Employee status and role updated.';
        header("Location: " . url("/admin/employees"));
    }

    /**
     * GET /admin/employees/roles
     * View roles and manage granular permissions mappings (Super Admin only)
     */
    public function rolesIndex(): void {
        Auth::requirePermission('roles.manage');

        $roles = $this->db->query("SELECT * FROM roles WHERE name <> 'visitor' ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $permissions = $this->db->query("SELECT * FROM permissions ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

        // Fetch active mappings
        $mappings = $this->db->query("SELECT role_id, permission_id FROM role_permissions")->fetchAll(PDO::FETCH_ASSOC);
        $activeMap = [];
        foreach ($mappings as $m) {
            $activeMap[$m['role_id']][] = (int)$m['permission_id'];
        }

        View::render('admin.employees.roles', [
            'user' => Auth::currentUser(),
            'roles' => $roles,
            'permissions' => $permissions,
            'activeMap' => $activeMap,
            'csrf_token' => Security::csrfToken()
        ]);
    }

    /**
     * POST /admin/employees/roles
     * Update roles permissions mappings inside transactions
     */
    public function rolesUpdate(): void {
        Auth::requirePermission('roles.manage');

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            $_SESSION['admin_errors'] = 'CSRF verification failed.';
            header("Location: " . url("/admin/employees/roles"));
            exit();
        }

        $roleId = (int)($_POST['role_id'] ?? 0);
        $permissions = $_POST['permissions'] ?? [];

        // Validate role exists and is not visitor
        $stmtRole = $this->db->prepare("SELECT id FROM roles WHERE id = ? AND name <> 'visitor' LIMIT 1");
        $stmtRole->execute([$roleId]);
        if (!$stmtRole->fetch()) {
            $_SESSION['admin_errors'] = 'Invalid role.';
            header("Location: " . url("/admin/employees/roles"));
            exit();
        }

        $this->db->beginTransaction();
        try {
            // Clear current permissions mapping for this role
            $del = $this->db->prepare("DELETE FROM role_permissions WHERE role_id = ?");
            $del->execute([$roleId]);

            // Add new permissions mapping
            if (!empty($permissions)) {
                $ins = $this->db->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
                foreach ($permissions as $pId) {
                    $ins->execute([$roleId, (int)$pId]);
                }
            }

            $this->db->commit();
            $this->logAction('role_permissions_update', 'employees', 'roles', $roleId, ['permissions_count' => count($permissions)]);
            $_SESSION['admin_success'] = 'Role permissions updated successfully.';
        } catch (Exception $e) {
            $this->db->rollBack();
            $_SESSION['admin_errors'] = 'Failed to update role permissions: ' . $e->getMessage();
        }

        header("Location: " . url("/admin/employees/roles"));
    }

    // ==========================================
    // LOCATIONS MANAGEMENT
    // ==========================================

    /**
     * GET /admin/locations/countries
     * Country manager
     */
    public function countries(): void {
        Auth::requirePermission('settings.view');

        $countries = $this->db->query("SELECT * FROM countries ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

        View::render('admin.locations.countries', [
            'user' => Auth::currentUser(),
            'countries' => $countries,
            'csrf_token' => Security::csrfToken()
        ]);
    }

    /**
     * POST /admin/locations/countries
     */
    public function countriesStore(): void {
        Auth::requirePermission('settings.edit');

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            $_SESSION['admin_errors'] = 'CSRF verification failed.';
            header("Location: " . url("/admin/locations/countries"));
            exit();
        }

        $name = trim($_POST['name'] ?? '');
        $code = strtoupper(trim($_POST['iso_code'] ?? ''));
        $currency = strtoupper(trim($_POST['currency_code'] ?? ''));
        $dial = trim($_POST['dial_code'] ?? '');

        if ($name === '' || $code === '') {
            $_SESSION['admin_errors'] = 'Country Name and ISO Code are required.';
            header("Location: " . url("/admin/locations/countries"));
            exit();
        }

        $stmt = $this->db->prepare("
            INSERT INTO countries (name, iso_code, currency_code, dial_code)
            VALUES (:name, :code, :curr, :dial)
        ");
        $stmt->execute([
            'name' => $name,
            'code' => $code,
            'curr' => $currency,
            'dial' => $dial
        ]);
        $cId = $this->db->lastInsertId();

        $this->logAction('country_create', 'locations', 'countries', $cId, ['name' => $name]);
        $_SESSION['admin_success'] = 'Country added successfully.';
        header("Location: " . url("/admin/locations/countries"));
    }

    public function countriesEdit(string $id): void {
        Auth::requirePermission('settings.view');
        $id = $this->resolveId($id);

        $stmt = $this->db->prepare("SELECT * FROM countries WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $country = $stmt->fetch();
        if (!$country) {
            http_response_code(404);
            echo "Country not found";
            exit();
        }

        View::render('admin.locations.countries_edit', [
            'user' => Auth::currentUser(),
            'country' => $country,
            'csrf_token' => Security::csrfToken()
        ]);
    }

    /**
     * POST /admin/locations/countries/{id}/update
     */
    public function countriesUpdate(string $id): void {
        Auth::requirePermission('settings.edit');
        $id = $this->resolveId($id);
        $encId = encode_id($id);

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            $_SESSION['admin_errors'] = 'CSRF verification failed.';
            header("Location: " . url("/admin/locations/countries/$encId/edit"));
            exit();
        }

        $name = trim($_POST['name'] ?? '');
        $code = strtoupper(trim($_POST['iso_code'] ?? ''));
        $currency = strtoupper(trim($_POST['currency_code'] ?? ''));
        $dial = trim($_POST['dial_code'] ?? '');

        if ($name === '' || $code === '') {
            $_SESSION['admin_errors'] = 'Country Name and ISO Code are required.';
            header("Location: " . url("/admin/locations/countries/$encId/edit"));
            exit();
        }

        $stmt = $this->db->prepare("
            UPDATE countries 
            SET name = :name, iso_code = :code, currency_code = :curr, dial_code = :dial 
            WHERE id = :id
        ");
        $stmt->execute([
            'name' => $name,
            'code' => $code,
            'curr' => $currency,
            'dial' => $dial,
            'id' => $id
        ]);

        $this->logAction('country_update', 'locations', 'countries', $id, ['name' => $name]);
        $_SESSION['admin_success'] = 'Country updated successfully.';
        header("Location: " . url("/admin/locations/countries"));
        exit();
    }

    /**
     * POST /admin/locations/countries/{id}/delete
     */
    public function countriesDelete(string $id): void {
        Auth::requirePermission('settings.edit');
        $id = $this->resolveId($id, true);

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            http_response_code(400);
            echo json_encode(['error' => 'CSRF verification failed']);
            exit();
        }

        // Safety check to prevent cascade errors
        $stmtCheck = $this->db->prepare("SELECT COUNT(*) FROM states WHERE country_id = ?");
        $stmtCheck->execute([$id]);
        if ((int)$stmtCheck->fetchColumn() > 0) {
            $msg = 'Cannot delete country: Related states/provinces exist. Delete states first.';
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode(['error' => $msg]);
                exit();
            }
            $_SESSION['admin_errors'] = $msg;
            header("Location: " . url("/admin/locations/countries"));
            exit();
        }

        $stmt = $this->db->prepare("DELETE FROM countries WHERE id = ?");
        $stmt->execute([$id]);

        $this->logAction('country_delete', 'locations', 'countries', $id);
        
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Country deleted successfully.']);
            exit();
        }
        $_SESSION['admin_success'] = 'Country deleted successfully.';
        header("Location: " . url("/admin/locations/countries"));
    }

    /**
     * GET /admin/locations/states
     * State manager
     */
    public function states(): void {
        Auth::requirePermission('settings.view');

        $states = $this->db->query("
            SELECT s.*, c.name as country_name 
            FROM states s
            JOIN countries c ON s.country_id = c.id
            ORDER BY c.name ASC, s.name ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $countries = $this->db->query("SELECT id, name FROM countries ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

        View::render('admin.locations.states', [
            'user' => Auth::currentUser(),
            'states' => $states,
            'countries' => $countries,
            'csrf_token' => Security::csrfToken()
        ]);
    }

    /**
     * POST /admin/locations/states
     */
    public function statesStore(): void {
        Auth::requirePermission('settings.edit');

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            $_SESSION['admin_errors'] = 'CSRF verification failed.';
            header("Location: " . url("/admin/locations/states"));
            exit();
        }

        $name = trim($_POST['name'] ?? '');
        $countryId = (int)($_POST['country_id'] ?? 0);

        if ($name === '' || $countryId === 0) {
            $_SESSION['admin_errors'] = 'State Name and Country are required.';
            header("Location: " . url("/admin/locations/states"));
            exit();
        }

        $stmt = $this->db->prepare("INSERT INTO states (name, country_id) VALUES (:name, :cid)");
        $stmt->execute(['name' => $name, 'cid' => $countryId]);
        $sId = $this->db->lastInsertId();

        $this->logAction('state_create', 'locations', 'states', $sId, ['name' => $name, 'country_id' => $countryId]);
        $_SESSION['admin_success'] = 'State added successfully.';
        header("Location: " . url("/admin/locations/states"));
    }

    public function statesEdit(string $id): void {
        Auth::requirePermission('settings.view');
        $id = $this->resolveId($id);

        $stmt = $this->db->prepare("SELECT * FROM states WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $state = $stmt->fetch();
        if (!$state) {
            http_response_code(404);
            echo "State not found";
            exit();
        }

        $countries = $this->db->query("SELECT id, name FROM countries ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

        View::render('admin.locations.states_edit', [
            'user' => Auth::currentUser(),
            'state' => $state,
            'countries' => $countries,
            'csrf_token' => Security::csrfToken()
        ]);
    }

    /**
     * POST /admin/locations/states/{id}/update
     */
    public function statesUpdate(string $id): void {
        Auth::requirePermission('settings.edit');
        $id = $this->resolveId($id);
        $encId = encode_id($id);

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            $_SESSION['admin_errors'] = 'CSRF verification failed.';
            header("Location: " . url("/admin/locations/states/$encId/edit"));
            exit();
        }

        $name = trim($_POST['name'] ?? '');
        $countryId = (int)($_POST['country_id'] ?? 0);

        if ($name === '' || $countryId === 0) {
            $_SESSION['admin_errors'] = 'State Name and Country are required.';
            header("Location: " . url("/admin/locations/states/$encId/edit"));
            exit();
        }

        $stmt = $this->db->prepare("UPDATE states SET name = :name, country_id = :cid WHERE id = :id");
        $stmt->execute(['name' => $name, 'cid' => $countryId, 'id' => $id]);

        $this->logAction('state_update', 'locations', 'states', $id, ['name' => $name, 'country_id' => $countryId]);
        $_SESSION['admin_success'] = 'State updated successfully.';
        header("Location: " . url("/admin/locations/states"));
        exit();
    }

    /**
     * POST /admin/locations/states/{id}/delete
     */
    public function statesDelete(string $id): void {
        Auth::requirePermission('settings.edit');
        $id = $this->resolveId($id, true);

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            http_response_code(400);
            echo json_encode(['error' => 'CSRF verification failed']);
            exit();
        }

        // Safety check to prevent cascade errors
        $stmtCheck = $this->db->prepare("SELECT COUNT(*) FROM cities WHERE state_id = ?");
        $stmtCheck->execute([$id]);
        if ((int)$stmtCheck->fetchColumn() > 0) {
            $msg = 'Cannot delete state: Related cities exist. Delete cities first.';
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode(['error' => $msg]);
                exit();
            }
            $_SESSION['admin_errors'] = $msg;
            header("Location: " . url("/admin/locations/states"));
            exit();
        }

        $stmt = $this->db->prepare("DELETE FROM states WHERE id = ?");
        $stmt->execute([$id]);

        $this->logAction('state_delete', 'locations', 'states', $id);
        
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'State deleted successfully.']);
            exit();
        }
        $_SESSION['admin_success'] = 'State deleted successfully.';
        header("Location: " . url("/admin/locations/states"));
    }

    /**
     * GET /admin/locations/cities
     * City manager
     */
    public function cities(): void {
        Auth::requirePermission('settings.view');

        $cities = $this->db->query("
            SELECT c.*, s.name as state_name, co.name as country_name 
            FROM cities c
            JOIN states s ON c.state_id = s.id
            JOIN countries co ON s.country_id = co.id
            ORDER BY co.name ASC, s.name ASC, c.name ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $countries = $this->db->query("SELECT id, name FROM countries ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

        View::render('admin.locations.cities', [
            'user' => Auth::currentUser(),
            'cities' => $cities,
            'countries' => $countries,
            'csrf_token' => Security::csrfToken()
        ]);
    }

    /**
     * POST /admin/locations/cities
     */
    public function citiesStore(): void {
        Auth::requirePermission('settings.edit');

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            $_SESSION['admin_errors'] = 'CSRF verification failed.';
            header("Location: " . url("/admin/locations/cities"));
            exit();
        }

        $name = trim($_POST['name'] ?? '');
        $stateId = (int)($_POST['state_id'] ?? 0);

        if ($name === '' || $stateId === 0) {
            $_SESSION['admin_errors'] = 'City Name and State are required.';
            header("Location: " . url("/admin/locations/cities"));
            exit();
        }

        $stmt = $this->db->prepare("INSERT INTO cities (name, state_id) VALUES (:name, :sid)");
        $stmt->execute(['name' => $name, 'sid' => $stateId]);
        $cId = $this->db->lastInsertId();

        $this->logAction('city_create', 'locations', 'cities', $cId, ['name' => $name, 'state_id' => $stateId]);
        $_SESSION['admin_success'] = 'City added successfully.';
        header("Location: " . url("/admin/locations/cities"));
    }

    public function citiesEdit(string $id): void {
        Auth::requirePermission('settings.view');
        $id = $this->resolveId($id);

        $stmt = $this->db->prepare("
            SELECT c.*, s.country_id 
            FROM cities c
            JOIN states s ON c.state_id = s.id
            WHERE c.id = ? 
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $city = $stmt->fetch();
        if (!$city) {
            http_response_code(404);
            echo "City not found";
            exit();
        }

        $countries = $this->db->query("SELECT id, name FROM countries ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $states = $this->db->query("SELECT id, name FROM states WHERE country_id = {$city['country_id']} ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

        View::render('admin.locations.cities_edit', [
            'user' => Auth::currentUser(),
            'city' => $city,
            'countries' => $countries,
            'states' => $states,
            'csrf_token' => Security::csrfToken()
        ]);
    }

    /**
     * POST /admin/locations/cities/{id}/update
     */
    public function citiesUpdate(string $id): void {
        Auth::requirePermission('settings.edit');
        $id = $this->resolveId($id);
        $encId = encode_id($id);

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            $_SESSION['admin_errors'] = 'CSRF verification failed.';
            header("Location: " . url("/admin/locations/cities/$encId/edit"));
            exit();
        }

        $name = trim($_POST['name'] ?? '');
        $stateId = (int)($_POST['state_id'] ?? 0);

        if ($name === '' || $stateId === 0) {
            $_SESSION['admin_errors'] = 'City Name and State are required.';
            header("Location: " . url("/admin/locations/cities/$encId/edit"));
            exit();
        }

        $stmt = $this->db->prepare("UPDATE cities SET name = :name, state_id = :sid WHERE id = :id");
        $stmt->execute(['name' => $name, 'sid' => $stateId, 'id' => $id]);

        $this->logAction('city_update', 'locations', 'cities', $id, ['name' => $name, 'state_id' => $stateId]);
        $_SESSION['admin_success'] = 'City updated successfully.';
        header("Location: " . url("/admin/locations/cities"));
        exit();
    }

    /**
     * POST /admin/locations/cities/{id}/delete
     */
    public function citiesDelete(string $id): void {
        Auth::requirePermission('settings.edit');
        $id = $this->resolveId($id, true);

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            http_response_code(400);
            echo json_encode(['error' => 'CSRF verification failed']);
            exit();
        }

        $stmt = $this->db->prepare("DELETE FROM cities WHERE id = ?");
        $stmt->execute([$id]);

        $this->logAction('city_delete', 'locations', 'cities', $id);
        
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'City deleted successfully.']);
            exit();
        }
        $_SESSION['admin_success'] = 'City deleted successfully.';
        header("Location: " . url("/admin/locations/cities"));
    }

    // ==========================================
    // ACADEMIC DATA MANAGEMENT
    // ==========================================

    /**
     * GET/POST /admin/academic/fields
     */
    public function fields(): void {
        Auth::requirePermission('settings.view');

        $fields = $this->db->query("SELECT * FROM fields_of_study ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

        View::render('admin.academic.fields', [
            'user' => Auth::currentUser(),
            'fields' => $fields,
            'csrf_token' => Security::csrfToken()
        ]);
    }

    /**
     * POST /admin/academic/fields
     */
    public function fieldsStore(): void {
        Auth::requirePermission('settings.edit');
        $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            if ($isAjax) {
                if (!headers_sent()) http_response_code(400);
                if (!headers_sent()) header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'CSRF verification failed. Please refresh the page.']);
                if (defined('TESTING_MODE') && TESTING_MODE) return;
                exit();
            }
            $_SESSION['admin_errors'] = 'CSRF verification failed.';
            if (!headers_sent()) header("Location: " . url("/admin/academic/fields"));
            if (defined('TESTING_MODE') && TESTING_MODE) return;
            exit();
        }

        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');

        if ($name === '') {
            if ($isAjax) {
                if (!headers_sent()) http_response_code(422);
                if (!headers_sent()) header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Field Name is required.']);
                if (defined('TESTING_MODE') && TESTING_MODE) return;
                exit();
            }
            $_SESSION['admin_errors'] = 'Field Name is required.';
            if (!headers_sent()) header("Location: " . url("/admin/academic/fields"));
            if (defined('TESTING_MODE') && TESTING_MODE) return;
            exit();
        }

        // Duplicate name check
        $stmtCheck = $this->db->prepare("SELECT id FROM fields_of_study WHERE LOWER(name) = LOWER(:name) LIMIT 1");
        $stmtCheck->execute(['name' => $name]);
        if ($stmtCheck->fetch()) {
            if ($isAjax) {
                if (!headers_sent()) http_response_code(422);
                if (!headers_sent()) header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'A Field of Study with this name already exists.']);
                if (defined('TESTING_MODE') && TESTING_MODE) return;
                exit();
            }
            $_SESSION['admin_errors'] = 'A Field of Study with this name already exists.';
            if (!headers_sent()) header("Location: " . url("/admin/academic/fields"));
            if (defined('TESTING_MODE') && TESTING_MODE) return;
            exit();
        }

        $stmt = $this->db->prepare("INSERT INTO fields_of_study (name, description) VALUES (:name, :desc)");
        $stmt->execute(['name' => $name, 'desc' => $desc]);
        $fId = $this->db->lastInsertId();

        $this->logAction('field_create', 'academic', 'fields_of_study', $fId, ['name' => $name]);

        if ($isAjax) {
            if (!headers_sent()) header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Field of Study added successfully.',
                'field' => [
                    'id' => encode_id((int)$fId),
                    'name' => $name,
                    'description' => $desc
                ]
            ]);
            if (defined('TESTING_MODE') && TESTING_MODE) return;
            exit();
        }

        $_SESSION['admin_success'] = 'Field of Study added successfully.';
        if (!headers_sent()) header("Location: " . url("/admin/academic/fields"));
        if (defined('TESTING_MODE') && TESTING_MODE) return;
        exit();
    }

    public function fieldsEdit(string $id): void {
        Auth::requirePermission('settings.view');
        $id = $this->resolveId($id);
        $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

        $stmt = $this->db->prepare("SELECT * FROM fields_of_study WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $field = $stmt->fetch();
        if (!$field) {
            if ($isAjax) {
                if (!headers_sent()) http_response_code(404);
                if (!headers_sent()) header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Field not found.']);
                if (defined('TESTING_MODE') && TESTING_MODE) return;
                exit();
            }
            if (!headers_sent()) http_response_code(404);
            echo "Field not found";
            if (defined('TESTING_MODE') && TESTING_MODE) return;
            exit();
        }

        if ($isAjax) {
            if (!headers_sent()) header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'field' => [
                    'record_id' => encode_id((int)$field['id']),
                    'name' => $field['name'],
                    'description' => $field['description'] ?? ''
                ]
            ]);
            if (defined('TESTING_MODE') && TESTING_MODE) return;
            exit();
        }

        View::render('admin.academic.fields_edit', [
            'user' => Auth::currentUser(),
            'field' => $field,
            'csrf_token' => Security::csrfToken()
        ]);
    }

    /**
     * POST /admin/academic/fields/{id}/update
     */
    public function fieldsUpdate(string $id): void {
        Auth::requirePermission('settings.edit');
        $id = $this->resolveId($id);
        $encId = encode_id($id);
        $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            if ($isAjax) {
                if (!headers_sent()) http_response_code(400);
                if (!headers_sent()) header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'CSRF verification failed. Please refresh the page.']);
                if (defined('TESTING_MODE') && TESTING_MODE) return;
                exit();
            }
            $_SESSION['admin_errors'] = 'CSRF verification failed.';
            if (!headers_sent()) header("Location: " . url("/admin/academic/fields/$encId/edit"));
            if (defined('TESTING_MODE') && TESTING_MODE) return;
            exit();
        }

        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');

        if ($name === '') {
            if ($isAjax) {
                if (!headers_sent()) http_response_code(422);
                if (!headers_sent()) header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Field Name is required.']);
                if (defined('TESTING_MODE') && TESTING_MODE) return;
                exit();
            }
            $_SESSION['admin_errors'] = 'Field Name is required.';
            if (!headers_sent()) header("Location: " . url("/admin/academic/fields/$encId/edit"));
            if (defined('TESTING_MODE') && TESTING_MODE) return;
            exit();
        }

        // Duplicate name check (excluding current id)
        $stmtCheck = $this->db->prepare("SELECT id FROM fields_of_study WHERE LOWER(name) = LOWER(:name) AND id != :id LIMIT 1");
        $stmtCheck->execute(['name' => $name, 'id' => $id]);
        if ($stmtCheck->fetch()) {
            if ($isAjax) {
                if (!headers_sent()) http_response_code(422);
                if (!headers_sent()) header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'A Field of Study with this name already exists.']);
                if (defined('TESTING_MODE') && TESTING_MODE) return;
                exit();
            }
            $_SESSION['admin_errors'] = 'A Field of Study with this name already exists.';
            if (!headers_sent()) header("Location: " . url("/admin/academic/fields/$encId/edit"));
            if (defined('TESTING_MODE') && TESTING_MODE) return;
            exit();
        }

        $stmt = $this->db->prepare("UPDATE fields_of_study SET name = :name, description = :desc WHERE id = :id");
        $stmt->execute(['name' => $name, 'desc' => $desc, 'id' => $id]);

        $this->logAction('field_update', 'academic', 'fields_of_study', $id, ['name' => $name]);

        if ($isAjax) {
            if (!headers_sent()) header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Field of Study updated successfully.',
                'field' => [
                    'id' => $encId,
                    'name' => $name,
                    'description' => $desc
                ]
            ]);
            if (defined('TESTING_MODE') && TESTING_MODE) return;
            exit();
        }

        $_SESSION['admin_success'] = 'Field of Study updated successfully.';
        if (!headers_sent()) header("Location: " . url("/admin/academic/fields"));
        if (defined('TESTING_MODE') && TESTING_MODE) return;
        exit();
    }

    /**
     * POST /admin/academic/fields/{id}/delete
     */
    public function fieldsDelete(string $id): void {
        Auth::requirePermission('settings.edit');
        $id = $this->resolveId($id, true);
        $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            if ($isAjax) {
                if (!headers_sent()) http_response_code(400);
                if (!headers_sent()) header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'CSRF verification failed.']);
                if (defined('TESTING_MODE') && TESTING_MODE) return;
                exit();
            }
            $_SESSION['admin_errors'] = 'CSRF verification failed.';
            if (!headers_sent()) header("Location: " . url("/admin/academic/fields"));
            if (defined('TESTING_MODE') && TESTING_MODE) return;
            exit();
        }

        try {
            $stmt = $this->db->prepare("DELETE FROM fields_of_study WHERE id = ?");
            $stmt->execute([$id]);

            $this->logAction('field_delete', 'academic', 'fields_of_study', $id);
            
            if ($isAjax) {
                if (!headers_sent()) header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Field of Study deleted successfully.']);
                if (defined('TESTING_MODE') && TESTING_MODE) return;
                exit();
            }
            $_SESSION['admin_success'] = 'Field of Study deleted successfully.';
            if (!headers_sent()) header("Location: " . url("/admin/academic/fields"));
            if (defined('TESTING_MODE') && TESTING_MODE) return;
            exit();
        } catch (\PDOException $e) {
            $errMsg = 'Cannot delete this Field of Study because it is currently linked to scholarships or student profiles.';
            if ($isAjax) {
                if (!headers_sent()) http_response_code(409);
                if (!headers_sent()) header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $errMsg]);
                if (defined('TESTING_MODE') && TESTING_MODE) return;
                exit();
            }
            $_SESSION['admin_errors'] = $errMsg;
            if (!headers_sent()) header("Location: " . url("/admin/academic/fields"));
            if (defined('TESTING_MODE') && TESTING_MODE) return;
            exit();
        }
    }

    /**
     * GET /admin/academic/degrees
     * Degree levels CRUD
     */
    public function degrees(): void {
        Auth::requirePermission('settings.view');

        $degrees = $this->db->query("SELECT * FROM degree_levels ORDER BY sort_order ASC, name ASC")->fetchAll(PDO::FETCH_ASSOC);

        View::render('admin.academic.degrees', [
            'user' => Auth::currentUser(),
            'degrees' => $degrees,
            'csrf_token' => Security::csrfToken()
        ]);
    }

    /**
     * POST /admin/academic/degrees
     */
    public function degreesStore(): void {
        Auth::requirePermission('settings.edit');
        $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            if ($isAjax) {
                if (!headers_sent()) http_response_code(400);
                if (!headers_sent()) header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'CSRF verification failed. Please refresh the page.']);
                if (defined('TESTING_MODE') && TESTING_MODE) return;
                exit();
            }
            $_SESSION['admin_errors'] = 'CSRF verification failed.';
            if (!headers_sent()) header("Location: " . url("/admin/academic/degrees"));
            if (defined('TESTING_MODE') && TESTING_MODE) return;
            exit();
        }

        $name = trim($_POST['name'] ?? '');
        $sort = (int)($_POST['sort_order'] ?? 0);
        $status = trim($_POST['status'] ?? 'active');
        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = 'active';
        }

        if ($name === '') {
            if ($isAjax) {
                if (!headers_sent()) http_response_code(422);
                if (!headers_sent()) header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Degree Level Name is required.']);
                if (defined('TESTING_MODE') && TESTING_MODE) return;
                exit();
            }
            $_SESSION['admin_errors'] = 'Degree Level Name is required.';
            if (!headers_sent()) header("Location: " . url("/admin/academic/degrees"));
            if (defined('TESTING_MODE') && TESTING_MODE) return;
            exit();
        }

        // Duplicate name check
        $stmtCheck = $this->db->prepare("SELECT id FROM degree_levels WHERE LOWER(name) = LOWER(:name) LIMIT 1");
        $stmtCheck->execute(['name' => $name]);
        if ($stmtCheck->fetch()) {
            if ($isAjax) {
                if (!headers_sent()) http_response_code(422);
                if (!headers_sent()) header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'A Degree Level with this name already exists.']);
                if (defined('TESTING_MODE') && TESTING_MODE) return;
                exit();
            }
            $_SESSION['admin_errors'] = 'A Degree Level with this name already exists.';
            if (!headers_sent()) header("Location: " . url("/admin/academic/degrees"));
            if (defined('TESTING_MODE') && TESTING_MODE) return;
            exit();
        }

        $stmt = $this->db->prepare("INSERT INTO degree_levels (name, sort_order, status) VALUES (:name, :sort, :status)");
        $stmt->execute(['name' => $name, 'sort' => $sort, 'status' => $status]);
        $dId = $this->db->lastInsertId();

        $this->logAction('degree_create', 'academic', 'degree_levels', $dId, ['name' => $name]);

        if ($isAjax) {
            if (!headers_sent()) header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Degree Level added successfully.',
                'degree' => [
                    'id' => encode_id((int)$dId),
                    'name' => $name,
                    'sort_order' => $sort,
                    'status' => $status
                ]
            ]);
            if (defined('TESTING_MODE') && TESTING_MODE) return;
            exit();
        }

        $_SESSION['admin_success'] = 'Degree Level added successfully.';
        if (!headers_sent()) header("Location: " . url("/admin/academic/degrees"));
        if (defined('TESTING_MODE') && TESTING_MODE) return;
        exit();
    }

    public function degreesEdit(string $id): void {
        Auth::requirePermission('settings.view');
        $id = $this->resolveId($id);
        $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

        $stmt = $this->db->prepare("SELECT * FROM degree_levels WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $degree = $stmt->fetch();
        if (!$degree) {
            if ($isAjax) {
                if (!headers_sent()) http_response_code(404);
                if (!headers_sent()) header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Degree level not found.']);
                if (defined('TESTING_MODE') && TESTING_MODE) return;
                exit();
            }
            if (!headers_sent()) http_response_code(404);
            echo "Degree level not found";
            if (defined('TESTING_MODE') && TESTING_MODE) return;
            exit();
        }

        if ($isAjax) {
            if (!headers_sent()) header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'degree' => [
                    'record_id' => encode_id((int)$degree['id']),
                    'name' => $degree['name'],
                    'sort_order' => (int)$degree['sort_order'],
                    'status' => $degree['status'] ?? 'active'
                ]
            ]);
            if (defined('TESTING_MODE') && TESTING_MODE) return;
            exit();
        }

        View::render('admin.academic.degrees_edit', [
            'user' => Auth::currentUser(),
            'degree' => $degree,
            'csrf_token' => Security::csrfToken()
        ]);
    }

    /**
     * POST /admin/academic/degrees/{id}/update
     */
    public function degreesUpdate(string $id): void {
        Auth::requirePermission('settings.edit');
        $id = $this->resolveId($id);
        $encId = encode_id($id);
        $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            if ($isAjax) {
                if (!headers_sent()) http_response_code(400);
                if (!headers_sent()) header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'CSRF verification failed. Please refresh the page.']);
                if (defined('TESTING_MODE') && TESTING_MODE) return;
                exit();
            }
            $_SESSION['admin_errors'] = 'CSRF verification failed.';
            if (!headers_sent()) header("Location: " . url("/admin/academic/degrees/$encId/edit"));
            if (defined('TESTING_MODE') && TESTING_MODE) return;
            exit();
        }

        $name = trim($_POST['name'] ?? '');
        $sort = (int)($_POST['sort_order'] ?? 0);
        $status = trim($_POST['status'] ?? 'active');
        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = 'active';
        }

        if ($name === '') {
            if ($isAjax) {
                if (!headers_sent()) http_response_code(422);
                if (!headers_sent()) header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Degree Level Name is required.']);
                if (defined('TESTING_MODE') && TESTING_MODE) return;
                exit();
            }
            $_SESSION['admin_errors'] = 'Degree Level Name is required.';
            if (!headers_sent()) header("Location: " . url("/admin/academic/degrees/$encId/edit"));
            if (defined('TESTING_MODE') && TESTING_MODE) return;
            exit();
        }

        // Duplicate name check (excluding current id)
        $stmtCheck = $this->db->prepare("SELECT id FROM degree_levels WHERE LOWER(name) = LOWER(:name) AND id != :id LIMIT 1");
        $stmtCheck->execute(['name' => $name, 'id' => $id]);
        if ($stmtCheck->fetch()) {
            if ($isAjax) {
                if (!headers_sent()) http_response_code(422);
                if (!headers_sent()) header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'A Degree Level with this name already exists.']);
                if (defined('TESTING_MODE') && TESTING_MODE) return;
                exit();
            }
            $_SESSION['admin_errors'] = 'A Degree Level with this name already exists.';
            if (!headers_sent()) header("Location: " . url("/admin/academic/degrees/$encId/edit"));
            if (defined('TESTING_MODE') && TESTING_MODE) return;
            exit();
        }

        $stmt = $this->db->prepare("UPDATE degree_levels SET name = :name, sort_order = :sort, status = :status WHERE id = :id");
        $stmt->execute(['name' => $name, 'sort' => $sort, 'status' => $status, 'id' => $id]);

        $this->logAction('degree_update', 'academic', 'degree_levels', $id, ['name' => $name]);

        if ($isAjax) {
            if (!headers_sent()) header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Degree Level updated successfully.',
                'degree' => [
                    'id' => $encId,
                    'name' => $name,
                    'sort_order' => $sort,
                    'status' => $status
                ]
            ]);
            if (defined('TESTING_MODE') && TESTING_MODE) return;
            exit();
        }

        $_SESSION['admin_success'] = 'Degree Level updated successfully.';
        if (!headers_sent()) header("Location: " . url("/admin/academic/degrees"));
        if (defined('TESTING_MODE') && TESTING_MODE) return;
        exit();
    }

    /**
     * POST /admin/academic/degrees/{id}/delete
     */
    public function degreesDelete(string $id): void {
        Auth::requirePermission('settings.edit');
        $id = $this->resolveId($id, true);
        $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            if ($isAjax) {
                if (!headers_sent()) http_response_code(400);
                if (!headers_sent()) header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'CSRF verification failed.']);
                if (defined('TESTING_MODE') && TESTING_MODE) return;
                exit();
            }
            $_SESSION['admin_errors'] = 'CSRF verification failed.';
            if (!headers_sent()) header("Location: " . url("/admin/academic/degrees"));
            if (defined('TESTING_MODE') && TESTING_MODE) return;
            exit();
        }

        try {
            $stmt = $this->db->prepare("DELETE FROM degree_levels WHERE id = ?");
            $stmt->execute([$id]);

            $this->logAction('degree_delete', 'academic', 'degree_levels', $id);
            
            if ($isAjax) {
                if (!headers_sent()) header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Degree Level deleted successfully.']);
                if (defined('TESTING_MODE') && TESTING_MODE) return;
                exit();
            }
            $_SESSION['admin_success'] = 'Degree Level deleted successfully.';
            if (!headers_sent()) header("Location: " . url("/admin/academic/degrees"));
            if (defined('TESTING_MODE') && TESTING_MODE) return;
            exit();
        } catch (\PDOException $e) {
            $errMsg = 'Cannot delete this Degree Level because it is currently linked to scholarships or student profiles.';
            if ($isAjax) {
                if (!headers_sent()) http_response_code(409);
                if (!headers_sent()) header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $errMsg]);
                if (defined('TESTING_MODE') && TESTING_MODE) return;
                exit();
            }
            $_SESSION['admin_errors'] = $errMsg;
            if (!headers_sent()) header("Location: " . url("/admin/academic/degrees"));
            if (defined('TESTING_MODE') && TESTING_MODE) return;
            exit();
        }
    }

    /**
     * GET /admin/academic/funding
     * Funding Types CRUD
     */
    public function funding(): void {
        Auth::requirePermission('settings.view');

        $fundings = $this->db->query("SELECT * FROM funding_types ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

        View::render('admin.academic.funding', [
            'user' => Auth::currentUser(),
            'fundings' => $fundings,
            'csrf_token' => Security::csrfToken()
        ]);
    }

    /**
     * POST /admin/academic/funding
     */
    public function fundingStore(): void {
        Auth::requirePermission('settings.edit');
        $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            if ($isAjax) {
                if (!headers_sent()) http_response_code(400);
                if (!headers_sent()) header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'CSRF verification failed. Please refresh the page.']);
                if (defined('TESTING_MODE') && TESTING_MODE) return;
                exit();
            }
            $_SESSION['admin_errors'] = 'CSRF verification failed.';
            if (!headers_sent()) header("Location: " . url("/admin/academic/funding"));
            if (defined('TESTING_MODE') && TESTING_MODE) return;
            exit();
        }

        $name = trim($_POST['name'] ?? '');
        $status = trim($_POST['status'] ?? 'active');
        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = 'active';
        }

        if ($name === '') {
            if ($isAjax) {
                if (!headers_sent()) http_response_code(422);
                if (!headers_sent()) header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Funding Type Name is required.']);
                if (defined('TESTING_MODE') && TESTING_MODE) return;
                exit();
            }
            $_SESSION['admin_errors'] = 'Funding Type Name is required.';
            if (!headers_sent()) header("Location: " . url("/admin/academic/funding"));
            if (defined('TESTING_MODE') && TESTING_MODE) return;
            exit();
        }

        // Duplicate name check
        $stmtCheck = $this->db->prepare("SELECT id FROM funding_types WHERE LOWER(name) = LOWER(:name) LIMIT 1");
        $stmtCheck->execute(['name' => $name]);
        if ($stmtCheck->fetch()) {
            if ($isAjax) {
                if (!headers_sent()) http_response_code(422);
                if (!headers_sent()) header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'A Funding Type with this name already exists.']);
                if (defined('TESTING_MODE') && TESTING_MODE) return;
                exit();
            }
            $_SESSION['admin_errors'] = 'A Funding Type with this name already exists.';
            if (!headers_sent()) header("Location: " . url("/admin/academic/funding"));
            if (defined('TESTING_MODE') && TESTING_MODE) return;
            exit();
        }

        $stmt = $this->db->prepare("INSERT INTO funding_types (name, status) VALUES (:name, :status)");
        $stmt->execute(['name' => $name, 'status' => $status]);
        $fId = $this->db->lastInsertId();

        $this->logAction('funding_create', 'academic', 'funding_types', $fId, ['name' => $name]);

        if ($isAjax) {
            if (!headers_sent()) header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Funding Type added successfully.',
                'funding' => [
                    'id' => encode_id((int)$fId),
                    'name' => $name,
                    'status' => $status
                ]
            ]);
            if (defined('TESTING_MODE') && TESTING_MODE) return;
            exit();
        }

        $_SESSION['admin_success'] = 'Funding Type added successfully.';
        if (!headers_sent()) header("Location: " . url("/admin/academic/funding"));
        if (defined('TESTING_MODE') && TESTING_MODE) return;
        exit();
    }

    public function fundingEdit(string $id): void {
        Auth::requirePermission('settings.view');
        $id = $this->resolveId($id);
        $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

        $stmt = $this->db->prepare("SELECT * FROM funding_types WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $funding = $stmt->fetch();
        if (!$funding) {
            if ($isAjax) {
                if (!headers_sent()) http_response_code(404);
                if (!headers_sent()) header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Funding type not found.']);
                if (defined('TESTING_MODE') && TESTING_MODE) return;
                exit();
            }
            if (!headers_sent()) http_response_code(404);
            echo "Funding type not found";
            if (defined('TESTING_MODE') && TESTING_MODE) return;
            exit();
        }

        if ($isAjax) {
            if (!headers_sent()) header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'funding' => [
                    'record_id' => encode_id((int)$funding['id']),
                    'name' => $funding['name'],
                    'status' => $funding['status'] ?? 'active'
                ]
            ]);
            if (defined('TESTING_MODE') && TESTING_MODE) return;
            exit();
        }

        View::render('admin.academic.funding_edit', [
            'user' => Auth::currentUser(),
            'funding' => $funding,
            'csrf_token' => Security::csrfToken()
        ]);
    }

    /**
     * POST /admin/academic/funding/{id}/update
     */
    public function fundingUpdate(string $id): void {
        Auth::requirePermission('settings.edit');
        $id = $this->resolveId($id);
        $encId = encode_id($id);
        $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            if ($isAjax) {
                if (!headers_sent()) http_response_code(400);
                if (!headers_sent()) header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'CSRF verification failed. Please refresh the page.']);
                if (defined('TESTING_MODE') && TESTING_MODE) return;
                exit();
            }
            $_SESSION['admin_errors'] = 'CSRF verification failed.';
            if (!headers_sent()) header("Location: " . url("/admin/academic/funding/$encId/edit"));
            if (defined('TESTING_MODE') && TESTING_MODE) return;
            exit();
        }

        $name = trim($_POST['name'] ?? '');
        $status = trim($_POST['status'] ?? 'active');
        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = 'active';
        }

        if ($name === '') {
            if ($isAjax) {
                if (!headers_sent()) http_response_code(422);
                if (!headers_sent()) header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Funding Type Name is required.']);
                if (defined('TESTING_MODE') && TESTING_MODE) return;
                exit();
            }
            $_SESSION['admin_errors'] = 'Funding Type Name is required.';
            if (!headers_sent()) header("Location: " . url("/admin/academic/funding/$encId/edit"));
            if (defined('TESTING_MODE') && TESTING_MODE) return;
            exit();
        }

        // Duplicate name check (excluding current id)
        $stmtCheck = $this->db->prepare("SELECT id FROM funding_types WHERE LOWER(name) = LOWER(:name) AND id != :id LIMIT 1");
        $stmtCheck->execute(['name' => $name, 'id' => $id]);
        if ($stmtCheck->fetch()) {
            if ($isAjax) {
                if (!headers_sent()) http_response_code(422);
                if (!headers_sent()) header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'A Funding Type with this name already exists.']);
                if (defined('TESTING_MODE') && TESTING_MODE) return;
                exit();
            }
            $_SESSION['admin_errors'] = 'A Funding Type with this name already exists.';
            if (!headers_sent()) header("Location: " . url("/admin/academic/funding/$encId/edit"));
            if (defined('TESTING_MODE') && TESTING_MODE) return;
            exit();
        }

        $stmt = $this->db->prepare("UPDATE funding_types SET name = :name, status = :status WHERE id = :id");
        $stmt->execute(['name' => $name, 'status' => $status, 'id' => $id]);

        $this->logAction('funding_update', 'academic', 'funding_types', $id, ['name' => $name]);

        if ($isAjax) {
            if (!headers_sent()) header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Funding Type updated successfully.',
                'funding' => [
                    'id' => $encId,
                    'name' => $name,
                    'status' => $status
                ]
            ]);
            if (defined('TESTING_MODE') && TESTING_MODE) return;
            exit();
        }

        $_SESSION['admin_success'] = 'Funding Type updated successfully.';
        if (!headers_sent()) header("Location: " . url("/admin/academic/funding"));
        if (defined('TESTING_MODE') && TESTING_MODE) return;
        exit();
    }

    /**
     * POST /admin/academic/funding/{id}/delete
     */
    public function fundingDelete(string $id): void {
        Auth::requirePermission('settings.edit');
        $id = $this->resolveId($id, true);
        $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            if ($isAjax) {
                if (!headers_sent()) http_response_code(400);
                if (!headers_sent()) header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'CSRF verification failed.']);
                if (defined('TESTING_MODE') && TESTING_MODE) return;
                exit();
            }
            $_SESSION['admin_errors'] = 'CSRF verification failed.';
            if (!headers_sent()) header("Location: " . url("/admin/academic/funding"));
            if (defined('TESTING_MODE') && TESTING_MODE) return;
            exit();
        }

        // Check if funding type is currently used by any scholarships
        $stmtF = $this->db->prepare("SELECT name FROM funding_types WHERE id = ? LIMIT 1");
        $stmtF->execute([$id]);
        $existing = $stmtF->fetch();
        if ($existing) {
            $stmtCount = $this->db->prepare("SELECT COUNT(*) FROM scholarships WHERE LOWER(funding_type) = LOWER(:name)");
            $stmtCount->execute(['name' => $existing['name']]);
            if ((int)$stmtCount->fetchColumn() > 0) {
                $errMsg = 'Cannot delete this Funding Type because it is currently assigned to one or more scholarships.';
                if ($isAjax) {
                    if (!headers_sent()) http_response_code(409);
                    if (!headers_sent()) header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => $errMsg]);
                    if (defined('TESTING_MODE') && TESTING_MODE) return;
                    exit();
                }
                $_SESSION['admin_errors'] = $errMsg;
                if (!headers_sent()) header("Location: " . url("/admin/academic/funding"));
                if (defined('TESTING_MODE') && TESTING_MODE) return;
                exit();
            }
        }

        try {
            $stmt = $this->db->prepare("DELETE FROM funding_types WHERE id = ?");
            $stmt->execute([$id]);

            $this->logAction('funding_delete', 'academic', 'funding_types', $id);
            
            if ($isAjax) {
                if (!headers_sent()) header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Funding Type deleted successfully.']);
                if (defined('TESTING_MODE') && TESTING_MODE) return;
                exit();
            }
            $_SESSION['admin_success'] = 'Funding Type deleted successfully.';
            if (!headers_sent()) header("Location: " . url("/admin/academic/funding"));
            if (defined('TESTING_MODE') && TESTING_MODE) return;
            exit();
        } catch (\PDOException $e) {
            $errMsg = 'Cannot delete this Funding Type because it is currently linked to existing records.';
            if ($isAjax) {
                if (!headers_sent()) http_response_code(409);
                if (!headers_sent()) header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $errMsg]);
                if (defined('TESTING_MODE') && TESTING_MODE) return;
                exit();
            }
            $_SESSION['admin_errors'] = $errMsg;
            if (!headers_sent()) header("Location: " . url("/admin/academic/funding"));
            if (defined('TESTING_MODE') && TESTING_MODE) return;
            exit();
        }
    }

    // ==========================================
    // MATCHING SYSTEM
    // ==========================================

    /**
     * GET /admin/matching/rules
     */
    public function matchingRules(): void {
        Auth::requirePermission('reports.view');

        // Render matched criteria configurations
        View::render('admin.matching.rules', [
            'user' => Auth::currentUser()
        ]);
    }

    /**
     * GET /admin/matching/stats
     */
    public function matchingStats(): void {
        Auth::requirePermission('reports.view');

        // Query aggregates from matches table
        $totalMatches = (int)$this->db->query("SELECT COUNT(*) FROM scholarship_matches")->fetchColumn();
        
        $mostMatched = $this->db->query("
            SELECT s.title, s.provider_name, COUNT(m.id) as match_count, ROUND(AVG(m.match_score), 1) as avg_score
            FROM scholarship_matches m
            JOIN scholarships s ON m.scholarship_id = s.id
            GROUP BY s.id
            ORDER BY match_count DESC
            LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);

        $noMatchUsers = $this->db->query("
            SELECT u.id, u.email, u.first_name, u.last_name
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE r.name = 'visitor' AND NOT EXISTS (SELECT 1 FROM scholarship_matches m WHERE m.user_id = u.id)
            ORDER BY u.email ASC
            LIMIT 50
        ")->fetchAll(PDO::FETCH_ASSOC);

        View::render('admin.matching.stats', [
            'user' => Auth::currentUser(),
            'totalMatches' => $totalMatches,
            'mostMatched' => $mostMatched,
            'noMatchUsers' => $noMatchUsers
        ]);
    }

    // ==========================================
    // SETTINGS & WEBSITE CMS
    // ==========================================

    /**
     * GET /admin/settings
     */
    public function settings(): void {
        Auth::requirePermission('settings.view');

        // Fetch settings grouped by group_name
        $settings = $this->db->query("SELECT * FROM settings ORDER BY group_name ASC, `key` ASC")->fetchAll(PDO::FETCH_ASSOC);
        
        $groups = [];
        foreach ($settings as $s) {
            // Mask sensitive fields (like SMTP password, payment keys)
            if ($s['is_public'] == 0 && preg_match('/pass|secret|key/i', $s['key'])) {
                $s['value'] = '********';
            }
            $groups[$s['group_name']][] = $s;
        }

        $schedulerService = new \App\Services\NotificationSchedulerService($this->db);
        $schedulerSettings = $schedulerService->getSettings();
        $matchingNextRun = $schedulerService->calculateNextRun(
            $schedulerSettings['matching_send_time'],
            $schedulerSettings['matching_timezone'],
            $schedulerSettings['matching_allowed_days']
        );
        $deadlineNextRun = $schedulerService->calculateNextRun(
            $schedulerSettings['deadline_send_time'],
            $schedulerSettings['deadline_timezone'],
            $schedulerSettings['deadline_allowed_days']
        );

        View::render('admin.settings', [
            'user' => Auth::currentUser(),
            'groups' => $groups,
            'schedulerSettings' => $schedulerSettings,
            'matchingNextRun' => $matchingNextRun,
            'deadlineNextRun' => $deadlineNextRun,
            'csrf_token' => Security::csrfToken()
        ]);
    }

    /**
     * POST /admin/settings
     */
    public function settingsUpdate(): void {
        Auth::requirePermission('settings.edit');

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            $_SESSION['admin_errors'] = 'CSRF verification failed.';
            if (defined('TESTING_MODE') && TESTING_MODE) {
                return;
            }
            header("Location: " . url("/admin/settings"));
            exit();
        }

        // Validate & persist notification scheduler settings if submitted
        if (isset($_POST['whatsapp_send_time']) || isset($_POST['is_notification_settings']) || isset($_POST['matching_send_time'])) {
            $schedulerService = new \App\Services\NotificationSchedulerService($this->db);
            $validation = $schedulerService->validateSettings($_POST);
            if (!$validation['valid']) {
                $_SESSION['admin_errors'] = implode(' ', $validation['errors']);
                header("Location: " . url("/admin/settings"));
                return;
            }
            try {
                $schedulerService->updateSettings($_POST);
            } catch (Exception $e) {
                $_SESSION['admin_errors'] = 'Failed to update scheduler settings: ' . $e->getMessage();
                header("Location: " . url("/admin/settings"));
                return;
            }
        }

        // Get whitelist of keys currently in DB
        $currentKeys = $this->db->query("SELECT `key`, `value` FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("UPDATE settings SET `value` = :val WHERE `key` = :key");
            foreach ($currentKeys as $key => $oldVal) {
                if (isset($_POST[$key])) {
                    $newVal = trim($_POST[$key]);

                    // Skip updating if it is a masked password that wasn't modified
                    if ($newVal === '********') {
                        continue;
                    }

                    $stmt->execute(['val' => $newVal, 'key' => $key]);
                }
            }

            $this->db->commit();
            $this->logAction('settings_update', 'settings', 'settings', null);
            $_SESSION['admin_success'] = 'System settings updated successfully.';
        } catch (Exception $e) {
            $this->db->rollBack();
            $_SESSION['admin_errors'] = 'Failed to save settings: ' . $e->getMessage();
        }

        header("Location: " . url("/admin/settings"));
    }

    /**
     * POST /admin/settings/wacrm/test-connection
     */
    public function testWacrmConnection(): void {
        Auth::requirePermission('settings.view');
        
        $csrf = $_POST['csrf_token'] ?? '';
        if (!Security::verifyCsrfToken($csrf)) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['status' => 'NOT CONNECTED', 'error' => 'CSRF validation failed.']);
            if (($_ENV['APP_ENV'] ?? '') === 'testing') {
                return;
            }
            exit;
        }

        $provider = new \App\Services\WhatsApp\WacrmWhatsAppProvider();
        $status = $provider->testConnection();

        header('Content-Type: application/json');
        echo json_encode(['status' => $status]);
        if (($_ENV['APP_ENV'] ?? '') === 'testing') {
            return;
        }
        exit;
    }

    // ==========================================
    // IMMUTABLE AUDIT LOGS
    // ==========================================

    /**
     * GET /admin/audit-logs
     */
    public function auditLogs(): void {
        Auth::requirePermission('audit_logs.view');

        $module = trim($_GET['module'] ?? '');
        $search = trim($_GET['search'] ?? '');

        // Pagination
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $whereClauses = [];
        $params = [];

        if ($module !== '') {
            $whereClauses[] = "l.module = :module";
            $params['module'] = $module;
        }
        if ($search !== '') {
            $whereClauses[] = "(l.action LIKE :search OR u.email LIKE :search OR l.metadata LIKE :search)";
            $params['search'] = '%' . $search . '%';
        }

        $whereSql = '';
        if (!empty($whereClauses)) {
            $whereSql = "WHERE " . implode(" AND ", $whereClauses);
        }

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM audit_logs l LEFT JOIN users u ON l.user_id = u.id $whereSql");
        $countStmt->execute($params);
        $totalCount = (int)$countStmt->fetchColumn();
        $totalPages = ceil($totalCount / $limit);

        $stmt = $this->db->prepare("
            SELECT l.*, u.email as actor_email, u.first_name, u.last_name
            FROM audit_logs l
            LEFT JOIN users u ON l.user_id = u.id
            $whereSql
            ORDER BY l.id DESC
            LIMIT $limit OFFSET $offset
        ");
        $stmt->execute($params);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch distinct modules for filter
        $modules = $this->db->query("SELECT DISTINCT module FROM audit_logs ORDER BY module ASC")->fetchAll(PDO::FETCH_COLUMN);

        View::render('admin.audit_logs', [
            'user' => Auth::currentUser(),
            'logs' => $logs,
            'modules' => $modules,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalCount' => $totalCount,
            'selectedModule' => $module,
            'search' => $search
        ]);
    }

    // ==========================================
    // SUBSCRIPTIONS & PAYMENTS
    // ==========================================

    /**
     * GET /admin/subscriptions
     */
    public function subscriptionsIndex(): void {
        Auth::requirePermission('subscriptions.view');

        $status = trim($_GET['status'] ?? '');
        $whereSql = '';
        $params = [];

        if ($status !== '') {
            $whereSql = "WHERE s.status = :status";
            $params['status'] = $status;
        }

        $stmt = $this->db->prepare("
            SELECT s.*, p.name as plan_name, u.email as user_email, u.first_name, u.last_name
            FROM subscriptions s
            JOIN subscription_plans p ON s.plan_id = p.id
            JOIN users u ON s.user_id = u.id
            $whereSql
            ORDER BY s.id DESC
        ");
        $stmt->execute($params);
        $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        View::render('admin.subscriptions', [
            'user' => Auth::currentUser(),
            'subscriptions' => $subscriptions,
            'selectedStatus' => $status
        ]);
    }

    /**
     * GET /admin/plans
     * Subscription plans management view
     */
    public function plansIndex(): void {
        Auth::requireRole(['admin', 'employee']);
        if (!Auth::hasRole('admin') && !Auth::hasPermission('subscriptions.view')) {
            Auth::abort403();
        }

        $totalPlans = (int)$this->db->query("SELECT COUNT(*) FROM subscription_plans")->fetchColumn();
        $activePlans = (int)$this->db->query("SELECT COUNT(*) FROM subscription_plans WHERE status = 'active'")->fetchColumn();
        $inactivePlans = (int)$this->db->query("SELECT COUNT(*) FROM subscription_plans WHERE status = 'inactive'")->fetchColumn();
        $paidPlans = (int)$this->db->query("SELECT COUNT(*) FROM subscription_plans WHERE price > 0")->fetchColumn();

        View::render('admin.plans.index', [
            'user' => Auth::currentUser(),
            'totalPlans' => $totalPlans,
            'activePlans' => $activePlans,
            'inactivePlans' => $inactivePlans,
            'paidPlans' => $paidPlans,
            'csrf_token' => Security::csrfToken()
        ]);
    }

    /**
     * GET /admin/plans/data
     * Server-side DataTables JSON provider for subscription plans
     */
    public function plansData(): void {
        Auth::requireRole(['admin', 'employee']);
        if (!Auth::hasRole('admin') && !Auth::hasPermission('subscriptions.view')) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Forbidden']);
            exit();
        }

        $db = $this->db;

        $columns = [
            'id' => 'subscription_plans.id',
            'name' => 'subscription_plans.name',
            'slug' => 'subscription_plans.slug',
            'description' => 'subscription_plans.description',
            'price' => 'subscription_plans.price',
            'currency' => 'subscription_plans.currency',
            'billing_interval' => 'subscription_plans.billing_interval',
            'duration_days' => 'subscription_plans.duration_days',
            'max_matches' => 'subscription_plans.max_matches',
            'whatsapp_alerts' => 'subscription_plans.whatsapp_alerts',
            'email_alerts' => 'subscription_plans.email_alerts',
            'deadline_reminders' => 'subscription_plans.deadline_reminders',
            'application_tracking' => 'subscription_plans.application_tracking',
            'status' => 'subscription_plans.status',
            'created_at' => 'subscription_plans.created_at',
            'subscriber_count' => '(SELECT COUNT(*) FROM subscriptions WHERE subscriptions.plan_id = subscription_plans.id AND subscriptions.status IN (\'active\', \'protected\'))'
        ];

        $searchableColumns = [
            'subscription_plans.name',
            'subscription_plans.slug',
            'subscription_plans.description',
            'subscription_plans.currency'
        ];

        $columnMapping = [
            'name' => 'subscription_plans.name',
            'price' => 'subscription_plans.price',
            'billing_interval' => 'subscription_plans.billing_interval',
            'duration_days' => 'subscription_plans.duration_days',
            'status' => 'subscription_plans.status',
            'created_at' => 'subscription_plans.created_at'
        ];

        $joins = [];
        $customWhere = '1=1';
        $customParams = [];

        if (!empty($_GET['status']) && in_array($_GET['status'], ['active', 'inactive'])) {
            $customWhere .= " AND subscription_plans.status = :status_filter";
            $customParams['status_filter'] = $_GET['status'];
        }

        $result = \App\Helpers\DataTableHelper::process(
            $db,
            'subscription_plans',
            $columns,
            $searchableColumns,
            $columnMapping,
            $joins,
            $customWhere,
            $customParams,
            function($row) {
                $row['encoded_id'] = encode_id((int)$row['id']);
                $row['formatted_price'] = ((float)$row['price'] == 0) ? 'Free' : (number_format((float)$row['price'], 2) . ' ' . $row['currency']);
                return $row;
            }
        );

        header('Content-Type: application/json');
        echo json_encode($result);
        exit();
    }

    /**
     * POST /admin/plans
     * Create a new subscription plan
     */
    public function plansStore(): void {
        Auth::requireRole(['admin', 'employee']);
        if (!Auth::hasRole('admin') && !Auth::hasPermission('subscriptions.edit')) {
            Auth::abort403();
        }

        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            if ($this->isAjaxRequest()) {
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Invalid CSRF security token.']);
                exit();
            }
            $_SESSION['admin_errors'] = 'Invalid CSRF security token.';
            header("Location: " . url("/admin/plans"));
            exit();
        }

        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        if (empty($slug)) {
            $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $name), '-'));
        } else {
            $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $slug), '-'));
        }

        $description = trim($_POST['description'] ?? '');
        $billingInterval = trim($_POST['billing_interval'] ?? 'month');
        $durationDays = (int)($_POST['duration_days'] ?? 30);
        $price = (float)($_POST['price'] ?? 0);
        $currency = strtoupper(trim($_POST['currency'] ?? 'PKR'));
        if (strlen($currency) !== 3) {
            $currency = 'PKR';
        }
        $countryCode = !empty($_POST['country_code']) ? strtoupper(trim($_POST['country_code'])) : null;
        $maxMatches = isset($_POST['max_matches']) && $_POST['max_matches'] !== '' ? (int)$_POST['max_matches'] : null;

        $whatsappAlerts = !empty($_POST['whatsapp_alerts']) ? 1 : 0;
        $emailAlerts = !empty($_POST['email_alerts']) ? 1 : 0;
        $deadlineReminders = !empty($_POST['deadline_reminders']) ? 1 : 0;
        $applicationTracking = !empty($_POST['application_tracking']) ? 1 : 0;
        $status = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';

        $errors = [];
        if (empty($name)) {
            $errors[] = 'Plan Name is required.';
        }
        if (empty($slug)) {
            $errors[] = 'Plan Slug is required.';
        }
        if ($price < 0) {
            $errors[] = 'Price must be 0 or greater.';
        }
        if ($durationDays <= 0) {
            $errors[] = 'Duration in days must be greater than 0.';
        }

        // Check unique slug
        $stmtCheck = $this->db->prepare("SELECT COUNT(*) FROM subscription_plans WHERE slug = :slug");
        $stmtCheck->execute(['slug' => $slug]);
        if ((int)$stmtCheck->fetchColumn() > 0) {
            $errors[] = "The slug '{$slug}' is already taken. Please choose another slug.";
        }

        if (!empty($errors)) {
            if ($this->isAjaxRequest()) {
                http_response_code(422);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => implode(' ', $errors)]);
                exit();
            }
            $_SESSION['admin_errors'] = implode(' ', $errors);
            header("Location: " . url("/admin/plans"));
            exit();
        }

        $stmt = $this->db->prepare("
            INSERT INTO subscription_plans 
                (name, slug, description, billing_interval, duration_days, price, currency, country_code, max_matches, whatsapp_alerts, email_alerts, deadline_reminders, application_tracking, status, created_at, updated_at)
            VALUES 
                (:name, :slug, :description, :billing_interval, :duration_days, :price, :currency, :country_code, :max_matches, :whatsapp_alerts, :email_alerts, :deadline_reminders, :application_tracking, :status, NOW(), NOW())
        ");
        $stmt->execute([
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'billing_interval' => $billingInterval,
            'duration_days' => $durationDays,
            'price' => $price,
            'currency' => $currency,
            'country_code' => $countryCode,
            'max_matches' => $maxMatches,
            'whatsapp_alerts' => $whatsappAlerts,
            'email_alerts' => $emailAlerts,
            'deadline_reminders' => $deadlineReminders,
            'application_tracking' => $applicationTracking,
            'status' => $status
        ]);

        $newId = (int)$this->db->lastInsertId();
        $this->logAction('create', 'subscription_plans', 'subscription_plan', $newId, [
            'name' => $name,
            'slug' => $slug,
            'price' => $price,
            'currency' => $currency
        ]);

        if ($this->isAjaxRequest()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => "Plan '{$name}' created successfully."]);
            exit();
        }

        $_SESSION['admin_success'] = "Plan '{$name}' created successfully.";
        header("Location: " . url("/admin/plans"));
        exit();
    }

    /**
     * GET /admin/plans/{id}/edit
     * Return plan details for editing
     */
    public function plansEdit(string $id): void {
        Auth::requireRole(['admin', 'employee']);
        if (!Auth::hasRole('admin') && !Auth::hasPermission('subscriptions.view')) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Forbidden']);
            exit();
        }

        $planId = $this->resolveId($id, true);
        $stmt = $this->db->prepare("SELECT * FROM subscription_plans WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $planId]);
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$plan) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Plan not found']);
            exit();
        }

        $plan['encoded_id'] = encode_id((int)$plan['id']);
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'plan' => $plan]);
        exit();
    }

    /**
     * POST /admin/plans/{id}/update
     * Update an existing subscription plan
     */
    public function plansUpdate(string $id): void {
        Auth::requireRole(['admin', 'employee']);
        if (!Auth::hasRole('admin') && !Auth::hasPermission('subscriptions.edit')) {
            Auth::abort403();
        }

        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            if ($this->isAjaxRequest()) {
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Invalid CSRF security token.']);
                exit();
            }
            $_SESSION['admin_errors'] = 'Invalid CSRF security token.';
            header("Location: " . url("/admin/plans"));
            exit();
        }

        $planId = $this->resolveId($id, true);
        $stmtOld = $this->db->prepare("SELECT * FROM subscription_plans WHERE id = :id LIMIT 1");
        $stmtOld->execute(['id' => $planId]);
        $oldPlan = $stmtOld->fetch(PDO::FETCH_ASSOC);

        if (!$oldPlan) {
            $err = 'Subscription plan not found.';
            if ($this->isAjaxRequest()) {
                http_response_code(404);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $err]);
                exit();
            }
            $_SESSION['admin_errors'] = $err;
            header("Location: " . url("/admin/plans"));
            exit();
        }

        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        if (empty($slug)) {
            $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $name), '-'));
        } else {
            $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $slug), '-'));
        }

        $description = trim($_POST['description'] ?? '');
        $billingInterval = trim($_POST['billing_interval'] ?? 'month');
        $durationDays = (int)($_POST['duration_days'] ?? 30);
        $price = (float)($_POST['price'] ?? 0);
        $currency = strtoupper(trim($_POST['currency'] ?? 'PKR'));
        if (strlen($currency) !== 3) {
            $currency = 'PKR';
        }
        $countryCode = !empty($_POST['country_code']) ? strtoupper(trim($_POST['country_code'])) : null;
        $maxMatches = isset($_POST['max_matches']) && $_POST['max_matches'] !== '' ? (int)$_POST['max_matches'] : null;

        $whatsappAlerts = !empty($_POST['whatsapp_alerts']) ? 1 : 0;
        $emailAlerts = !empty($_POST['email_alerts']) ? 1 : 0;
        $deadlineReminders = !empty($_POST['deadline_reminders']) ? 1 : 0;
        $applicationTracking = !empty($_POST['application_tracking']) ? 1 : 0;
        $status = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';

        $errors = [];
        if (empty($name)) {
            $errors[] = 'Plan Name is required.';
        }
        if (empty($slug)) {
            $errors[] = 'Plan Slug is required.';
        }
        if ($price < 0) {
            $errors[] = 'Price must be 0 or greater.';
        }
        if ($durationDays <= 0) {
            $errors[] = 'Duration in days must be greater than 0.';
        }

        // Check unique slug on other plans
        $stmtCheck = $this->db->prepare("SELECT COUNT(*) FROM subscription_plans WHERE slug = :slug AND id != :id");
        $stmtCheck->execute(['slug' => $slug, 'id' => $planId]);
        if ((int)$stmtCheck->fetchColumn() > 0) {
            $errors[] = "The slug '{$slug}' is already taken by another plan.";
        }

        if (!empty($errors)) {
            if ($this->isAjaxRequest()) {
                http_response_code(422);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => implode(' ', $errors)]);
                exit();
            }
            $_SESSION['admin_errors'] = implode(' ', $errors);
            header("Location: " . url("/admin/plans"));
            exit();
        }

        $stmt = $this->db->prepare("
            UPDATE subscription_plans 
            SET name = :name,
                slug = :slug,
                description = :description,
                billing_interval = :billing_interval,
                duration_days = :duration_days,
                price = :price,
                currency = :currency,
                country_code = :country_code,
                max_matches = :max_matches,
                whatsapp_alerts = :whatsapp_alerts,
                email_alerts = :email_alerts,
                deadline_reminders = :deadline_reminders,
                application_tracking = :application_tracking,
                status = :status,
                updated_at = NOW()
            WHERE id = :id
        ");
        $stmt->execute([
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'billing_interval' => $billingInterval,
            'duration_days' => $durationDays,
            'price' => $price,
            'currency' => $currency,
            'country_code' => $countryCode,
            'max_matches' => $maxMatches,
            'whatsapp_alerts' => $whatsappAlerts,
            'email_alerts' => $emailAlerts,
            'deadline_reminders' => $deadlineReminders,
            'application_tracking' => $applicationTracking,
            'status' => $status,
            'id' => $planId
        ]);

        $this->logAction('update', 'subscription_plans', 'subscription_plan', $planId, [
            'name' => $name,
            'slug' => $slug,
            'price' => $price,
            'currency' => $currency,
            'status' => $status
        ]);

        if ($this->isAjaxRequest()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => "Plan '{$name}' updated successfully."]);
            exit();
        }

        $_SESSION['admin_success'] = "Plan '{$name}' updated successfully.";
        header("Location: " . url("/admin/plans"));
        exit();
    }

    /**
     * POST /admin/plans/{id}/toggle-status
     * Toggle active/inactive status of a plan
     */
    public function plansToggleStatus(string $id): void {
        Auth::requireRole(['admin', 'employee']);
        if (!Auth::hasRole('admin') && !Auth::hasPermission('subscriptions.edit')) {
            Auth::abort403();
        }

        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Invalid CSRF security token.']);
            exit();
        }

        $planId = $this->resolveId($id, true);
        $stmt = $this->db->prepare("SELECT id, name, status FROM subscription_plans WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $planId]);
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$plan) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Plan not found']);
            exit();
        }

        $newStatus = ($plan['status'] === 'active') ? 'inactive' : 'active';
        $updateStmt = $this->db->prepare("UPDATE subscription_plans SET status = :status, updated_at = NOW() WHERE id = :id");
        $updateStmt->execute(['status' => $newStatus, 'id' => $planId]);

        $this->logAction('status_change', 'subscription_plans', 'subscription_plan', $planId, [
            'old_status' => $plan['status'],
            'new_status' => $newStatus
        ]);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => "Plan '{$plan['name']}' status updated to {$newStatus}.",
            'status' => $newStatus
        ]);
        exit();
    }

    /**
     * POST /admin/plans/{id}/delete
     * Delete a plan if no subscriptions exist
     */
    public function plansDelete(string $id): void {
        Auth::requireRole(['admin', 'employee']);
        if (!Auth::hasRole('admin') && !Auth::hasPermission('subscriptions.delete')) {
            Auth::abort403();
        }

        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Invalid CSRF security token.']);
            exit();
        }

        $planId = $this->resolveId($id, true);
        $stmt = $this->db->prepare("SELECT * FROM subscription_plans WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $planId]);
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$plan) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Plan not found']);
            exit();
        }

        // Cannot delete default free plan
        if ($plan['slug'] === 'free') {
            http_response_code(422);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'The default free plan cannot be deleted.']);
            exit();
        }

        // Check if any subscriptions exist
        $stmtCount = $this->db->prepare("SELECT COUNT(*) FROM subscriptions WHERE plan_id = :id");
        $stmtCount->execute(['id' => $planId]);
        $subCount = (int)$stmtCount->fetchColumn();

        if ($subCount > 0) {
            http_response_code(422);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false, 
                'error' => "Cannot delete plan because {$subCount} subscription(s) are linked to it. You can deactivate it instead."
            ]);
            exit();
        }

        $delStmt = $this->db->prepare("DELETE FROM subscription_plans WHERE id = :id");
        $delStmt->execute(['id' => $planId]);

        $this->logAction('delete', 'subscription_plans', 'subscription_plan', $planId, [
            'name' => $plan['name'],
            'slug' => $plan['slug']
        ]);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => "Plan '{$plan['name']}' deleted successfully."]);
        exit();
    }

    /**
     * GET /admin/alert-timers
     * Alert Timers & WhatsApp Automation Schedule Management view
     */
    public function alertTimersIndex(): void {
        Auth::requireRole(['admin', 'employee']);
        if (!Auth::hasRole('admin') && !Auth::hasPermission('settings.view')) {
            Auth::abort403();
        }

        $schedulerService = new \App\Services\NotificationSchedulerService($this->db);
        $schedulerSettings = $schedulerService->getSettings();
        
        $matchingNextRun = $schedulerService->calculateNextRun(
            $schedulerSettings['matching_send_time'],
            $schedulerSettings['matching_timezone'],
            $schedulerSettings['matching_allowed_days']
        );
        
        $deadlineNextRun = $schedulerService->calculateNextRun(
            $schedulerSettings['deadline_send_time'],
            $schedulerSettings['deadline_timezone'],
            $schedulerSettings['deadline_allowed_days']
        );

        // Daily operational stats
        $sentToday = (int)$this->db->query("
            SELECT COUNT(*) FROM notification_logs 
            WHERE channel = 'whatsapp' AND status = 'sent' AND DATE(sent_at) = CURDATE()
        ")->fetchColumn();

        $pendingQueue = (int)$this->db->query("
            SELECT COUNT(*) FROM notification_logs 
            WHERE channel = 'whatsapp' AND status = 'pending'
        ")->fetchColumn();

        $failedCount = (int)$this->db->query("
            SELECT COUNT(*) FROM notification_logs 
            WHERE channel = 'whatsapp' AND status = 'failed' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ")->fetchColumn();

        $masterEnabled = !empty($schedulerSettings['whatsapp_notifications_enabled']);
        $activeTimersCount = ($masterEnabled ? 1 : 0) * (
            (!empty($schedulerSettings['matching_scheduler_enabled']) ? 1 : 0) +
            (!empty($schedulerSettings['deadline_scheduler_enabled']) ? 1 : 0)
        );

        View::render('admin.alert_timers.index', [
            'user' => Auth::currentUser(),
            'settings' => $schedulerSettings,
            'matchingNextRun' => $matchingNextRun,
            'deadlineNextRun' => $deadlineNextRun,
            'sentToday' => $sentToday,
            'pendingQueue' => $pendingQueue,
            'failedCount' => $failedCount,
            'activeTimersCount' => $activeTimersCount,
            'csrf_token' => Security::csrfToken()
        ]);
    }

    /**
     * GET /admin/alert-timers/data
     * Return structured JSON list of alert timers
     */
    public function alertTimersData(): void {
        Auth::requireRole(['admin', 'employee']);
        if (!Auth::hasRole('admin') && !Auth::hasPermission('settings.view')) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Forbidden']);
            exit();
        }

        $schedulerService = new \App\Services\NotificationSchedulerService($this->db);
        $s = $schedulerService->getSettings();

        $matchingNextRun = $schedulerService->calculateNextRun(
            $s['matching_send_time'],
            $s['matching_timezone'],
            $s['matching_allowed_days']
        );
        $deadlineNextRun = $schedulerService->calculateNextRun(
            $s['deadline_send_time'],
            $s['deadline_timezone'],
            $s['deadline_allowed_days']
        );

        $timers = [
            [
                'id' => 'matching',
                'name' => 'Daily Scholarship Matching Alerts',
                'slug' => 'matching_alerts',
                'channel' => 'whatsapp',
                'alert_type' => 'matching',
                'description' => 'Evaluates newly matched scholarships for subscribed students and dispatches automated WhatsApp digest alerts.',
                'send_time' => $s['matching_send_time'],
                'send_time_display' => date('g:i A', strtotime('2000-01-01 ' . $s['matching_send_time'])),
                'timezone' => $s['matching_timezone'],
                'allowed_days' => $s['matching_allowed_days'],
                'is_active' => !empty($s['matching_scheduler_enabled']) && !empty($s['whatsapp_notifications_enabled']),
                'scheduler_enabled' => !empty($s['matching_scheduler_enabled']),
                'master_enabled' => !empty($s['whatsapp_notifications_enabled']),
                'next_run' => $matchingNextRun,
                'last_run_at' => $s['matching_last_run_at'],
                'last_run_status' => $s['matching_last_run_status'] ?: 'NEVER',
                'last_run_slot' => $s['matching_last_run_slot']
            ],
            [
                'id' => 'deadline',
                'name' => 'Scholarship Application Deadline Reminders',
                'slug' => 'deadline_reminders',
                'channel' => 'whatsapp',
                'alert_type' => 'deadline',
                'description' => 'Dispatches urgent countdown alerts (7 days, 3 days, 1 day) to students for their saved and tracked scholarships.',
                'send_time' => $s['deadline_send_time'],
                'send_time_display' => date('g:i A', strtotime('2000-01-01 ' . $s['deadline_send_time'])),
                'timezone' => $s['deadline_timezone'],
                'allowed_days' => $s['deadline_allowed_days'],
                'is_active' => !empty($s['deadline_scheduler_enabled']) && !empty($s['whatsapp_notifications_enabled']),
                'scheduler_enabled' => !empty($s['deadline_scheduler_enabled']),
                'master_enabled' => !empty($s['whatsapp_notifications_enabled']),
                'next_run' => $deadlineNextRun,
                'last_run_at' => $s['deadline_last_run_at'],
                'last_run_status' => $s['deadline_last_run_status'] ?: 'NEVER',
                'last_run_slot' => $s['deadline_last_run_slot']
            ]
        ];

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'master_enabled' => !empty($s['whatsapp_notifications_enabled']),
            'timers' => $timers
        ]);
        exit();
    }

    /**
     * POST /admin/alert-timers/update
     * Update dispatch time, days, and status of an alert timer
     */
    public function alertTimersUpdate(): void {
        Auth::requireRole(['admin', 'employee']);
        if (!Auth::hasRole('admin') && !Auth::hasPermission('settings.edit')) {
            Auth::abort403();
        }

        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Invalid CSRF security token.']);
            exit();
        }

        $timerType = trim($_POST['timer_type'] ?? '');
        if (!in_array($timerType, ['matching', 'deadline', 'master'], true)) {
            http_response_code(422);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Invalid timer type specified.']);
            exit();
        }

        $schedulerService = new \App\Services\NotificationSchedulerService($this->db);
        $payload = [];

        if ($timerType === 'matching') {
            $sendTime = trim($_POST['send_time'] ?? '08:00');
            $timezone = trim($_POST['timezone'] ?? 'Asia/Karachi');
            $allowedDays = $_POST['allowed_days'] ?? [];
            $isActive = !empty($_POST['is_active']) ? '1' : '0';

            $payload = [
                'matching_send_time' => $sendTime,
                'matching_timezone' => $timezone,
                'matching_allowed_days' => is_array($allowedDays) ? $allowedDays : explode(',', $allowedDays),
                'matching_scheduler_enabled' => $isActive,
                'whatsapp_new_match_enabled' => $isActive
            ];
        } elseif ($timerType === 'deadline') {
            $sendTime = trim($_POST['send_time'] ?? '09:00');
            $timezone = trim($_POST['timezone'] ?? 'Asia/Karachi');
            $allowedDays = $_POST['allowed_days'] ?? [];
            $isActive = !empty($_POST['is_active']) ? '1' : '0';

            $payload = [
                'deadline_send_time' => $sendTime,
                'deadline_timezone' => $timezone,
                'deadline_allowed_days' => is_array($allowedDays) ? $allowedDays : explode(',', $allowedDays),
                'deadline_scheduler_enabled' => $isActive,
                'whatsapp_deadline_reminder_enabled' => $isActive
            ];
        } elseif ($timerType === 'master') {
            $payload = [
                'whatsapp_notifications_enabled' => !empty($_POST['is_active']) ? '1' : '0'
            ];
        }

        $validation = $schedulerService->validateSettings($payload);
        if (!$validation['valid']) {
            http_response_code(422);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => implode(' ', $validation['errors'])]);
            exit();
        }

        try {
            $schedulerService->updateSettings($validation['sanitized']);
            $this->logAction('update', 'alert_timers', 'scheduler_setting', null, [
                'timer_type' => $timerType,
                'settings' => $validation['sanitized']
            ]);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Alert timer schedule updated successfully!'
            ]);
            exit();
        } catch (Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Failed to update schedule: ' . $e->getMessage()]);
            exit();
        }
    }

    /**
     * POST /admin/alert-timers/toggle-status
     * Quick toggle active / paused for a timer
     */
    public function alertTimersToggleStatus(): void {
        Auth::requireRole(['admin', 'employee']);
        if (!Auth::hasRole('admin') && !Auth::hasPermission('settings.edit')) {
            Auth::abort403();
        }

        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Invalid CSRF security token.']);
            exit();
        }

        $timerType = trim($_POST['timer_type'] ?? '');
        $schedulerService = new \App\Services\NotificationSchedulerService($this->db);
        $s = $schedulerService->getSettings();

        $newVal = '1';

        if ($timerType === 'matching') {
            $currentVal = !empty($s['matching_scheduler_enabled']);
            $newVal = $currentVal ? '0' : '1';
            $schedulerService->updateSettings([
                'matching_scheduler_enabled' => $newVal,
                'whatsapp_new_match_enabled' => $newVal
            ]);
            $label = 'Daily Matching Alerts';
        } elseif ($timerType === 'deadline') {
            $currentVal = !empty($s['deadline_scheduler_enabled']);
            $newVal = $currentVal ? '0' : '1';
            $schedulerService->updateSettings([
                'deadline_scheduler_enabled' => $newVal,
                'whatsapp_deadline_reminder_enabled' => $newVal
            ]);
            $label = 'Deadline Reminders';
        } elseif ($timerType === 'master') {
            $currentVal = !empty($s['whatsapp_notifications_enabled']);
            $newVal = $currentVal ? '0' : '1';
            $schedulerService->updateSettings([
                'whatsapp_notifications_enabled' => $newVal
            ]);
            $label = 'Master WhatsApp Automation';
        } else {
            http_response_code(422);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Invalid timer type.']);
            exit();
        }

        $this->logAction('status_change', 'alert_timers', 'scheduler_setting', null, [
            'timer_type' => $timerType,
            'is_active' => $newVal
        ]);

        $statusText = ($newVal === '1') ? 'Activated' : 'Paused';
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'is_active' => ($newVal === '1'),
            'message' => "{$label} successfully {$statusText}."
        ]);
        exit();
    }

    /**
     * POST /admin/alert-timers/run-now
     * Immediately triggers evaluation and queueing of an alert timer
     */
    public function alertTimersRunNow(): void {
        Auth::requireRole(['admin', 'employee']);
        if (!Auth::hasRole('admin') && !Auth::hasPermission('settings.edit')) {
            Auth::abort403();
        }

        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Invalid CSRF security token.']);
            exit();
        }

        $timerType = trim($_POST['timer_type'] ?? '');
        $schedulerService = new \App\Services\NotificationSchedulerService($this->db);

        try {
            if ($timerType === 'matching') {
                $slotKey = 'manual_matching_' . date('Y-m-d_H:i:s');
                $result = $schedulerService->runMatchingJob();
                $schedulerService->recordJobExecution('matching', 'SUCCESS', $slotKey);

                $this->logAction('manual_trigger', 'alert_timers', 'matching_job', null, $result);

                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'timer_type' => 'matching',
                    'message' => "Daily Matching Alerts evaluated! {$result['users_processed']} students checked, {$result['matches_found']} matches identified, {$result['whatsapp_batches']} WhatsApp message digests enqueued.",
                    'details' => $result
                ]);
                exit();
            } elseif ($timerType === 'deadline') {
                $slotKey = 'manual_deadline_' . date('Y-m-d_H:i:s');
                $result = $schedulerService->runDeadlineRemindersJob();
                $schedulerService->recordJobExecution('deadline', 'SUCCESS', $slotKey);

                $this->logAction('manual_trigger', 'alert_timers', 'deadline_job', null, $result);

                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'timer_type' => 'deadline',
                    'message' => "Deadline Reminders evaluated! {$result['users_processed']} students checked, {$result['reminders_enqueued']} WhatsApp reminders enqueued.",
                    'details' => $result
                ]);
                exit();
            } else {
                http_response_code(422);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Invalid timer type for immediate run.']);
                exit();
            }
        } catch (Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Execution error: ' . $e->getMessage()]);
            exit();
        }
    }

    /**
     * GET /admin/alert-timers/dry-run
     * Preview execution status without modifying data
     */
    public function alertTimersDryRun(): void {
        Auth::requireRole(['admin', 'employee']);
        if (!Auth::hasRole('admin') && !Auth::hasPermission('settings.view')) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Forbidden']);
            exit();
        }

        $schedulerService = new \App\Services\NotificationSchedulerService($this->db);
        $report = $schedulerService->runDryRun();

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'report' => $report]);
        exit();
    }

    /**
     * Check if request was made via AJAX / XMLHttpRequest
     */
    private function isAjaxRequest(): bool {
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_SERVER['HTTP_ACCEPT']) && strpos(strtolower($_SERVER['HTTP_ACCEPT']), 'application/json') !== false);
    }

    /**
     * GET /admin/manual-subscriptions
     * Lists active user plans, unsubscribed users, and allows granting manual plans
     */
    public function manualSubscriptionsIndex(): void {
        Auth::requireRole(['admin', 'employee']);
        if (!Auth::hasPermission('users.view') && !Auth::hasRole('admin')) {
            Auth::abort403();
        }

        // Active paid plans
        $plansStmt = $this->db->query("
            SELECT id, name, slug, duration_days, price, currency 
            FROM subscription_plans 
            WHERE slug != 'free' AND status = 'active' 
            ORDER BY price ASC
        ");
        $plans = $plansStmt->fetchAll(PDO::FETCH_ASSOC);

        // Active student users for the grant modal dropdown
        $studentsStmt = $this->db->query("
            SELECT u.id, u.first_name, u.last_name, u.email 
            FROM users u 
            JOIN roles r ON u.role_id = r.id 
            WHERE r.name = 'visitor' AND u.status = 'active' 
            ORDER BY u.first_name ASC, u.last_name ASC
        ");
        $students = $studentsStmt->fetchAll(PDO::FETCH_ASSOC);

        // Count unsubscribed users (students who haven't bought/have no active paid subscription)
        $unsubscribedCount = (int)$this->db->query("
            SELECT COUNT(*) 
            FROM users u 
            JOIN roles r ON u.role_id = r.id 
            WHERE r.name = 'visitor' AND u.status = 'active' 
              AND NOT EXISTS (
                  SELECT 1 FROM subscriptions s 
                  WHERE s.user_id = u.id 
                    AND s.status IN ('active', 'protected') 
                    AND (s.ends_at IS NULL OR s.ends_at > NOW())
              )
        ")->fetchColumn();

        // Stats summary
        $stats = [
            'unsubscribed' => $unsubscribedCount,
            'total' => (int)$this->db->query("SELECT COUNT(*) FROM manual_subscription_grants")->fetchColumn(),
            'pending' => (int)$this->db->query("SELECT COUNT(*) FROM manual_subscription_grants WHERE status = 'pending'")->fetchColumn(),
            'activated' => (int)$this->db->query("SELECT COUNT(*) FROM manual_subscription_grants WHERE status = 'activated'")->fetchColumn(),
            'revoked' => (int)$this->db->query("SELECT COUNT(*) FROM manual_subscription_grants WHERE status = 'revoked'")->fetchColumn()
        ];

        View::render('admin.manual_subscriptions.index', [
            'user' => Auth::currentUser(),
            'plans' => $plans,
            'students' => $students,
            'stats' => $stats,
            'csrf_token' => Security::csrfToken()
        ]);
    }

    /**
     * GET /admin/manual-subscriptions/data
     * Server-side DataTables JSON provider (supports view=unsubscribed and view=grants)
     */
    public function manualSubscriptionsData(): void {
        Auth::requireRole(['admin', 'employee']);
        if (!Auth::hasPermission('users.view') && !Auth::hasRole('admin')) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Forbidden']);
            exit();
        }

        $db = \App\Services\Database::connection();
        $view = trim($_GET['view'] ?? 'unsubscribed');

        if ($view === 'unsubscribed') {
            $customWhere = "r.name = 'visitor' AND users.status = 'active' AND NOT EXISTS (
                SELECT 1 FROM subscriptions s 
                WHERE s.user_id = users.id 
                  AND s.status IN ('active', 'protected') 
                  AND (s.ends_at IS NULL OR s.ends_at > NOW())
            )";
            $customParams = [];

            $columns = [
                'id' => 'users.id',
                'first_name' => 'users.first_name',
                'last_name' => 'users.last_name',
                'email' => 'users.email',
                'phone' => 'users.phone',
                'created_at' => 'users.created_at',
                'email_verified_at' => 'users.email_verified_at',
                'pending_grant_id' => 'g.id',
                'pending_token' => 'g.activation_token',
                'pending_plan_name' => 'p.name',
                'pending_duration' => 'g.duration_days',
                'pending_created_at' => 'g.created_at'
            ];

            $joins = [
                'JOIN roles r ON users.role_id = r.id',
                'LEFT JOIN (SELECT user_id, MAX(id) AS max_id FROM manual_subscription_grants WHERE status = \'pending\' GROUP BY user_id) pg ON pg.user_id = users.id',
                'LEFT JOIN manual_subscription_grants g ON g.id = pg.max_id',
                'LEFT JOIN subscription_plans p ON g.plan_id = p.id'
            ];

            $searchableColumns = ['users.first_name', 'users.last_name', 'users.email', 'users.phone'];

            $columnMapping = [
                'student_name' => 'users.first_name',
                'email' => 'users.email',
                'created_at' => 'users.created_at'
            ];

            $result = \App\Helpers\DataTableHelper::process(
                $db,
                'users',
                $columns,
                $searchableColumns,
                $columnMapping,
                $joins,
                $customWhere,
                $customParams,
                function($row) {
                    $userId = (int)$row['id'];
                    $row['user_id_encoded'] = encode_id($userId);
                    $row['raw_user_id'] = $userId;
                    $row['student_name'] = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')) ?: 'Student';
                    $row['has_pending_grant'] = !empty($row['pending_grant_id']);
                    $row['pending_grant_encoded'] = !empty($row['pending_grant_id']) ? encode_id((int)$row['pending_grant_id']) : null;
                    $row['activation_url'] = !empty($row['pending_token']) ? absolute_url('/subscriptions/activate?token=' . $row['pending_token']) : null;
                    $row['joined_date_display'] = !empty($row['created_at']) ? date('M d, Y', strtotime($row['created_at'])) : '-';

                    unset($row['id']);
                    return $row;
                }
            );

            header('Content-Type: application/json');
            echo json_encode($result);
            exit();
        }

        // Otherwise, view === 'grants'
        $customWhere = "";
        $customParams = [];

        if (!empty($_GET['status'])) {
            $customWhere = "manual_subscription_grants.status = :status";
            $customParams['status'] = $_GET['status'];
        }

        $columns = [
            'id' => 'manual_subscription_grants.id',
            'user_id' => 'manual_subscription_grants.user_id',
            'first_name' => 'users.first_name',
            'last_name' => 'users.last_name',
            'email' => 'users.email',
            'plan_name' => 'subscription_plans.name',
            'duration_days' => 'manual_subscription_grants.duration_days',
            'activation_token' => 'manual_subscription_grants.activation_token',
            'status' => 'manual_subscription_grants.status',
            'admin_notes' => 'manual_subscription_grants.admin_notes',
            'activated_at' => 'manual_subscription_grants.activated_at',
            'created_at' => 'manual_subscription_grants.created_at',
            'sub_starts_at' => 'subscriptions.starts_at',
            'sub_ends_at' => 'subscriptions.ends_at'
        ];

        $joins = [
            'JOIN users ON manual_subscription_grants.user_id = users.id',
            'JOIN subscription_plans ON manual_subscription_grants.plan_id = subscription_plans.id',
            'LEFT JOIN subscriptions ON manual_subscription_grants.subscription_id = subscriptions.id'
        ];

        $searchableColumns = ['users.first_name', 'users.last_name', 'users.email', 'subscription_plans.name', 'manual_subscription_grants.status'];

        $columnMapping = [
            'student_name' => 'users.first_name',
            'email' => 'users.email',
            'plan_name' => 'subscription_plans.name',
            'duration_days' => 'manual_subscription_grants.duration_days',
            'status' => 'manual_subscription_grants.status',
            'activated_at' => 'manual_subscription_grants.activated_at',
            'created_at' => 'manual_subscription_grants.created_at'
        ];

        $result = \App\Helpers\DataTableHelper::process(
            $db,
            'manual_subscription_grants',
            $columns,
            $searchableColumns,
            $columnMapping,
            $joins,
            $customWhere,
            $customParams,
            function($row) {
                $rawId = (int)$row['id'];
                $encodedId = encode_id($rawId);
                $row['record_id'] = $encodedId;
                $row['student_name'] = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
                $row['plan_display'] = $row['plan_name'] . ' (' . $row['duration_days'] . ' Days)';
                $row['activation_url'] = absolute_url('/subscriptions/activate?token=' . $row['activation_token']);

                if ($row['status'] === 'activated' && !empty($row['sub_starts_at']) && !empty($row['sub_ends_at'])) {
                    $row['validity_display'] = date('M d, Y', strtotime($row['sub_starts_at'])) . ' — ' . date('M d, Y', strtotime($row['sub_ends_at']));
                } elseif ($row['status'] === 'pending') {
                    $row['validity_display'] = 'Starts on user click (' . $row['duration_days'] . ' days)';
                } else {
                    $row['validity_display'] = 'N/A';
                }

                unset($row['id'], $row['first_name'], $row['last_name']);
                return $row;
            }
        );

        header('Content-Type: application/json');
        echo json_encode($result);
        exit();
    }

    /**
     * POST /admin/manual-subscriptions
     * Store new manual subscription grant and dispatch activation email
     */
    public function manualSubscriptionsStore(): void {
        Auth::requireRole(['admin', 'employee']);
        if (!Auth::hasPermission('users.edit') && !Auth::hasRole('admin')) {
            Auth::abort403();
        }

        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            if ($this->isAjaxRequest()) {
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Invalid CSRF security token.']);
                exit();
            }
            $_SESSION['admin_errors'] = 'Invalid CSRF security token.';
            header("Location: " . url("/admin/manual-subscriptions"));
            exit();
        }

        $rawUserParam = trim((string)($_POST['user_id'] ?? ''));
        $userId = $this->resolveId($rawUserParam, true);
        $planId = (int)($_POST['plan_id'] ?? 0);
        $durationDays = max(1, min(3650, (int)($_POST['duration_days'] ?? 30)));
        $adminNotes = trim($_POST['admin_notes'] ?? '');
        $sendEmail = !empty($_POST['send_email']);

        // Verify user exists and is a visitor
        $stmtUser = $this->db->prepare("
            SELECT u.*, r.name as role_name 
            FROM users u 
            JOIN roles r ON u.role_id = r.id 
            WHERE u.id = :id AND r.name = 'visitor'
            LIMIT 1
        ");
        $stmtUser->execute(['id' => $userId]);
        $student = $stmtUser->fetch(PDO::FETCH_ASSOC);

        if (!$student) {
            $err = 'Invalid or non-existent student user selected.';
            if ($this->isAjaxRequest()) {
                http_response_code(422);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $err]);
                exit();
            }
            $_SESSION['admin_errors'] = $err;
            header("Location: " . url("/admin/active-user-plans"));
            exit();
        }

        // Verify student does not already have an active subscription
        $stmtActive = $this->db->prepare("
            SELECT id FROM subscriptions 
            WHERE user_id = :uid 
              AND status IN ('active', 'protected') 
              AND (ends_at IS NULL OR ends_at > NOW()) 
            LIMIT 1
        ");
        $stmtActive->execute(['uid' => $userId]);
        if ($stmtActive->fetch()) {
            $err = 'This student already has an active subscription plan.';
            if ($this->isAjaxRequest()) {
                http_response_code(422);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $err]);
                exit();
            }
            $_SESSION['admin_errors'] = $err;
            header("Location: " . url("/admin/active-user-plans"));
            exit();
        }

        // Verify plan exists
        $stmtPlan = $this->db->prepare("SELECT * FROM subscription_plans WHERE id = :id AND status = 'active' LIMIT 1");
        $stmtPlan->execute(['id' => $planId]);
        $plan = $stmtPlan->fetch(PDO::FETCH_ASSOC);

        if (!$plan) {
            $err = 'Invalid subscription plan selected.';
            if ($this->isAjaxRequest()) {
                http_response_code(422);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $err]);
                exit();
            }
            $_SESSION['admin_errors'] = $err;
            header("Location: " . url("/admin/active-user-plans"));
            exit();
        }

        // Revoke any prior pending grants for this student so only the latest link is valid
        $stmtCancelOld = $this->db->prepare("
            UPDATE manual_subscription_grants 
            SET status = 'revoked', updated_at = NOW() 
            WHERE user_id = :uid AND status = 'pending'
        ");
        $stmtCancelOld->execute(['uid' => $userId]);

        // Generate 64-character crypto token
        $token = bin2hex(random_bytes(32));
        $tokenExpiresAt = date('Y-m-d H:i:s', strtotime('+60 days'));
        $adminId = Auth::userId();

        $stmtIns = $this->db->prepare("
            INSERT INTO manual_subscription_grants (
                user_id, plan_id, duration_days, activation_token,
                status, created_by, admin_notes, token_expires_at, created_at
            ) VALUES (
                :user_id, :plan_id, :duration_days, :activation_token,
                'pending', :created_by, :admin_notes, :token_expires_at, NOW()
            )
        ");
        $stmtIns->execute([
            'user_id' => $userId,
            'plan_id' => $planId,
            'duration_days' => $durationDays,
            'activation_token' => $token,
            'created_by' => $adminId,
            'admin_notes' => $adminNotes ?: null,
            'token_expires_at' => $tokenExpiresAt
        ]);
        $grantId = (int)$this->db->lastInsertId();

        $activationUrl = absolute_url('/subscriptions/activate?token=' . $token);

        // Enqueue activation email if requested
        $emailQueued = false;
        $emailError = null;
        if ($sendEmail) {
            $studentName = trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? '')) ?: 'Student';
            $planName = $plan['name'] ?? 'Premium';
            $subject = "Activate Your Complimentary {$planName} Subscription on ScholarPlanner";
            $emailBody = $this->buildManualSubscriptionEmailHtml($studentName, $planName, $durationDays, $activationUrl);

            try {
                $queueService = new \App\Services\NotificationQueueService();
                $idempotencyKey = 'manual_sub_grant_' . $grantId;
                $emailQueued = $queueService->enqueue(
                    $userId,
                    null,
                    \App\Services\NotificationTypes::MANUAL_SUBSCRIPTION_ACTIVATION,
                    'email',
                    $student['email'],
                    $subject,
                    [
                        'student_name' => $studentName,
                        'plan_name' => $planName,
                        'duration_days' => $durationDays,
                        'activation_url' => $activationUrl,
                        'body_html' => $emailBody
                    ],
                    $idempotencyKey
                );
            } catch (\Exception $e) {
                $emailError = $e->getMessage();
                \App\Services\Logger::error("Failed to enqueue manual subscription email for grant $grantId: " . $e->getMessage());
            }
        }

        $this->logAction('manual_subscription_granted', 'subscriptions', 'manual_subscription_grants', $grantId, [
            'user_id' => $userId,
            'plan_id' => $planId,
            'duration_days' => $durationDays,
            'email_queued' => $emailQueued
        ]);

        if ($this->isAjaxRequest()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'grant_id' => encode_id($grantId),
                'activation_url' => $activationUrl,
                'email_queued' => $emailQueued,
                'email_sent' => $emailQueued,
                'email_error' => $emailError,
                'message' => 'Subscription grant created successfully. ' . ($emailQueued ? 'Activation email queued for background delivery.' : 'Activation link ready to copy.')
            ]);
            exit();
        }

        $_SESSION['admin_success'] = "Subscription grant created successfully. " . ($emailQueued ? "Activation email queued for background delivery." : "Activation link is ready.");
        header("Location: " . url("/admin/manual-subscriptions"));
        exit();
    }

    /**
     * POST /admin/manual-subscriptions/{id}/resend
     * Resend activation email for a pending grant via queue
     */
    public function manualSubscriptionsResend(string $id): void {
        Auth::requireRole(['admin', 'employee']);
        $rawId = $this->resolveId($id, true);

        $stmt = $this->db->prepare("
            SELECT g.*, u.first_name, u.last_name, u.email, p.name as plan_name, p.slug as plan_slug 
            FROM manual_subscription_grants g 
            JOIN users u ON g.user_id = u.id 
            JOIN subscription_plans p ON g.plan_id = p.id 
            WHERE g.id = :id 
            LIMIT 1
        ");
        $stmt->execute(['id' => $rawId]);
        $grant = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$grant) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Subscription grant not found.']);
            exit();
        }

        if ($grant['status'] !== 'pending') {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Cannot resend: grant is already ' . $grant['status'] . '.']);
            exit();
        }

        $studentName = trim(($grant['first_name'] ?? '') . ' ' . ($grant['last_name'] ?? '')) ?: 'Student';
        $planName = $grant['plan_name'] ?? 'Premium';
        $activationUrl = absolute_url('/subscriptions/activate?token=' . urlencode($grant['activation_token']));
        $subject = "Activate Your Complimentary {$planName} Subscription on ScholarPlanner";
        $emailBody = $this->buildManualSubscriptionEmailHtml($studentName, $planName, (int)$grant['duration_days'], $activationUrl);

        $queued = false;
        $queueError = null;
        try {
            $queueService = new \App\Services\NotificationQueueService();
            $idempotencyKey = 'manual_sub_resend_' . $rawId . '_' . time();
            $queued = $queueService->enqueue(
                (int)$grant['user_id'],
                null,
                \App\Services\NotificationTypes::MANUAL_SUBSCRIPTION_ACTIVATION,
                'email',
                $grant['email'],
                $subject,
                [
                    'student_name' => $studentName,
                    'plan_name' => $planName,
                    'duration_days' => (int)$grant['duration_days'],
                    'activation_url' => $activationUrl,
                    'body_html' => $emailBody
                ],
                $idempotencyKey
            );
        } catch (\Exception $e) {
            $queueError = $e->getMessage();
            \App\Services\Logger::error("Failed to enqueue manual subscription resend for grant $rawId: " . $e->getMessage());
        }

        $this->logAction('manual_subscription_email_resent', 'subscriptions', 'manual_subscription_grants', $rawId);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => $queued,
            'message' => $queued ? 'Activation email queued for background delivery.' : ('Failed to enqueue email: ' . ($queueError ?? 'unknown error'))
        ]);
        exit();
    }

    /**
     * POST /admin/manual-subscriptions/{id}/revoke
     * Revoke an unclicked pending grant
     */
    public function manualSubscriptionsRevoke(string $id): void {
        Auth::requireRole(['admin', 'employee']);
        $rawId = $this->resolveId($id, true);

        $stmt = $this->db->prepare("SELECT * FROM manual_subscription_grants WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $rawId]);
        $grant = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$grant) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Grant not found.']);
            exit();
        }

        if ($grant['status'] !== 'pending') {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Only pending grants can be revoked. Current status: ' . $grant['status']]);
            exit();
        }

        $stmtUpd = $this->db->prepare("UPDATE manual_subscription_grants SET status = 'revoked', updated_at = NOW() WHERE id = :id");
        $stmtUpd->execute(['id' => $rawId]);

        $this->logAction('manual_subscription_revoked', 'subscriptions', 'manual_subscription_grants', $rawId);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Subscription grant has been revoked successfully.']);
        exit();
    }

    /**
     * Build styled HTML activation email template
     */
    public function buildManualSubscriptionEmailHtml(string $studentName, string $planName, int $durationDays, string $activationUrl): string {
        $studentName = htmlspecialchars($studentName);
        $planName = htmlspecialchars($planName);
        $siteName = 'ScholarPlanner';

        return '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                body { margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #1e293b; }
                .container { max-width: 600px; margin: 30px auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03); border: 1px solid #e2e8f0; }
                .header { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); padding: 36px 30px; text-align: center; }
                .header h1 { margin: 0; font-size: 26px; font-weight: 800; color: #ffffff; letter-spacing: -0.5px; }
                .header p { margin: 6px 0 0 0; color: #bfdbfe; font-size: 14px; font-weight: 500; }
                .content { padding: 36px 30px; }
                .greeting { font-size: 18px; font-weight: 700; color: #0f172a; margin-bottom: 16px; }
                .lead { font-size: 15px; line-height: 1.6; color: #475569; margin-bottom: 24px; }
                .timer-notice { background-color: #eff6ff; border-left: 4px solid #3b82f6; padding: 18px 20px; border-radius: 8px; margin-bottom: 28px; }
                .timer-notice-title { font-weight: 700; color: #1e40af; font-size: 14px; margin-bottom: 6px; }
                .timer-notice-text { font-size: 13.5px; line-height: 1.5; color: #1e3a8a; margin: 0; }
                .plan-badge-box { text-align: center; margin: 24px 0 32px 0; }
                .plan-badge { display: inline-block; background-color: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 8px; padding: 12px 24px; font-size: 15px; font-weight: 700; color: #0f172a; }
                .btn-container { text-align: center; margin: 32px 0; }
                .btn { display: inline-block; background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); color: #ffffff !important; text-decoration: none; font-size: 16px; font-weight: 700; padding: 16px 38px; border-radius: 8px; box-shadow: 0 4px 12px rgba(37,99,235,0.25); }
                .perks-list { background-color: #f8fafc; border-radius: 8px; padding: 20px 24px; margin-bottom: 28px; }
                .perks-title { font-weight: 700; font-size: 14px; color: #334155; margin-bottom: 12px; }
                .perk-item { font-size: 13.5px; color: #475569; margin-bottom: 8px; }
                .perk-item:last-child { margin-bottom: 0; }
                .fallback { font-size: 12px; color: #64748b; line-height: 1.5; border-top: 1px solid #e2e8f0; padding-top: 20px; margin-top: 28px; word-break: break-all; }
                .footer { background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 24px 30px; text-align: center; font-size: 12px; color: #94a3b8; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>' . $siteName . '</h1>
                    <p>Personalized Scholarship Discovery Platform</p>
                </div>
                <div class="content">
                    <div class="greeting">Hello ' . $studentName . ',</div>
                    <p class="lead">Great news! An administrator has granted you a complimentary <strong>' . $planName . '</strong> subscription.</p>
                    
                    <div class="timer-notice">
                        <div class="timer-notice-title">⏱ When does your subscription start?</div>
                        <p class="timer-notice-text">
                            <strong>Your timer has NOT started yet!</strong> Your <strong>' . $durationDays . '-day</strong> validity period will begin <em>at the exact moment</em> you click the button below to open your activation link.
                        </p>
                    </div>

                    <div class="plan-badge-box">
                        <div class="plan-badge">
                            Tier: ' . $planName . ' &bull; Duration: ' . $durationDays . ' Days
                        </div>
                    </div>

                    <div class="btn-container">
                        <a href="' . $activationUrl . '" class="btn" target="_blank">Activate My Subscription Now</a>
                    </div>

                    <div class="perks-list">
                        <div class="perks-title">Included in your subscription:</div>
                        <div class="perk-item">&#10004; Direct WhatsApp & Email real-time scholarship alerts</div>
                        <div class="perk-item">&#10004; Unlimited side-by-side scholarship comparisons</div>
                        <div class="perk-item">&#10004; Priority document readiness score & deadline reminders</div>
                        <div class="perk-item">&#10004; Full access to advanced matching intelligence</div>
                    </div>

                    <div class="fallback">
                        If the button above does not work, copy and paste this activation link directly into your browser:<br>
                        <a href="' . $activationUrl . '" style="color: #2563eb;">' . $activationUrl . '</a>
                    </div>
                </div>
                <div class="footer">
                    &copy; ' . date('Y') . ' ' . $siteName . '. All rights reserved.<br>
                    This activation link is valid for 60 days. If you did not expect this, you may disregard this email.
                </div>
            </div>
        </body>
        </html>';
    }

    /**
     * Dispatch styled activation email to student (synchronous fallback)
     */
    private function sendManualSubscriptionEmail(array $student, array $plan, int $durationDays, string $token, string $notes = ''): array {
        $studentName = trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? '')) ?: 'Student';
        $planName = $plan['name'] ?? 'Premium';
        $activationUrl = absolute_url('/subscriptions/activate?token=' . urlencode($token));
        $subject = "Activate Your Complimentary {$planName} Subscription on ScholarPlanner";
        $body = $this->buildManualSubscriptionEmailHtml($studentName, $planName, $durationDays, $activationUrl);

        try {
            $emailService = new \App\Services\EmailNotificationService();
            return $emailService->sendEmail($student['email'], $subject, $body);
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * GET /admin/payments
     */
    public function paymentsIndex(): void {
        Auth::requirePermission('payments.view');

        $payments = $this->db->query("
            SELECT t.*, u.email as user_email, u.first_name, u.last_name
            FROM payment_transactions t
            JOIN users u ON t.user_id = u.id
            ORDER BY t.id DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        View::render('admin.payments', [
            'user' => Auth::currentUser(),
            'payments' => $payments,
            'csrf_token' => Security::csrfToken()
        ]);
    }

    // ==========================================
    // ADMIN PROFILE MANAGEMENT
    // ==========================================

    /**
     * GET /admin/profile
     */
    public function profile(): void {
        Auth::requireAuth();

        View::render('admin.profile', [
            'user' => Auth::currentUser(),
            'csrf_token' => Security::csrfToken()
        ]);
    }

    /**
     * POST /admin/profile
     */
    public function profileUpdate(): void {
        Auth::requireAuth();

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            $_SESSION['admin_errors'] = 'CSRF verification failed.';
            header("Location: " . url("/admin/profile"));
            exit();
        }

        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));

        $currPass = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';

        if ($firstName === '' || $email === '') {
            $_SESSION['admin_errors'] = 'Required fields missing.';
            header("Location: " . url("/admin/profile"));
            exit();
        }

        $user = Auth::currentUser();
        $dbUser = $this->db->query("SELECT * FROM users WHERE id = {$user['id']}")->fetch();

        // 1. Password change check
        if ($newPass !== '') {
            if ($currPass === '') {
                $_SESSION['admin_errors'] = 'Current password is required to change password.';
                header("Location: " . url("/admin/profile"));
                exit();
            }
            if (!password_verify($currPass, $dbUser['password_hash'])) {
                $_SESSION['admin_errors'] = 'Incorrect current password.';
                header("Location: " . url("/admin/profile"));
                exit();
            }
            if (strlen($newPass) < 8) {
                $_SESSION['admin_errors'] = 'New password must be at least 8 characters long.';
                header("Location: " . url("/admin/profile"));
                exit();
            }

            $hash = password_hash($newPass, PASSWORD_BCRYPT);
            $stmt = $this->db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt->execute([$hash, $user['id']]);
        }

        // 2. Info update
        $stmtInfo = $this->db->prepare("UPDATE users SET first_name = :fn, last_name = :ln, email = :email WHERE id = :id");
        $stmtInfo->execute([
            'fn' => $firstName,
            'ln' => $lastName,
            'email' => $email,
            'id' => $user['id']
        ]);

        $this->logAction('profile_update', 'profile', 'users', $user['id']);
        $_SESSION['admin_success'] = 'Profile updated successfully.';
        header("Location: " . url("/admin/profile"));
    }

    public function usersData(): void {
        Auth::requireRole(['admin', 'employee']);
        if (!Auth::hasPermission('users.view')) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Forbidden']);
            exit();
        }
        $db = \App\Services\Database::connection();
        $customWhere = "roles.name = 'visitor'";
        $customParams = [];

        // Search query from custom filter
        $searchQuery = trim($_GET['search_query'] ?? '');
        if ($searchQuery !== '') {
            $customWhere .= " AND (users.first_name LIKE :custom_search OR users.last_name LIKE :custom_search OR users.email LIKE :custom_search)";
            $customParams['custom_search'] = '%' . $searchQuery . '%';
        }

        if (!empty($_GET['status'])) {
            $customWhere .= " AND users.status = :status";
            $customParams['status'] = $_GET['status'];
        }
        $verified = $_GET['verified'] ?? $_GET['email_verified'] ?? '';
        if ($verified !== '') {
            if ($verified === '1') {
                $customWhere .= " AND users.email_verified_at IS NOT NULL";
            } else {
                $customWhere .= " AND users.email_verified_at IS NULL";
            }
        }
        $plan = trim($_GET['plan'] ?? '');
        if ($plan !== '') {
            if ($plan === 'free') {
                $customWhere .= " AND NOT EXISTS (SELECT 1 FROM subscriptions s WHERE s.user_id = users.id AND s.status = 'active')";
            } else {
                $customWhere .= " AND EXISTS (SELECT 1 FROM subscriptions s JOIN subscription_plans p ON s.plan_id = p.id WHERE s.user_id = users.id AND s.status = 'active' AND p.name = :plan_name)";
                $customParams['plan_name'] = $plan;
            }
        }

        $columns = [
            'id' => 'users.id',
            'first_name' => 'users.first_name',
            'last_name' => 'users.last_name',
            'email' => 'users.email',
            'status' => 'users.status',
            'created_at' => 'users.created_at',
            'email_verified_at' => 'users.email_verified_at',
            'completion' => 'student_profiles.profile_completion_percentage',
            'plan_name' => 'subscription_plans.name'
        ];
        $joins = [
            'LEFT JOIN roles ON users.role_id = roles.id',
            'LEFT JOIN student_profiles ON users.id = student_profiles.user_id',
            'LEFT JOIN subscriptions ON users.id = subscriptions.user_id AND subscriptions.status = \'active\' AND (subscriptions.ends_at IS NULL OR subscriptions.ends_at > NOW())',
            'LEFT JOIN subscription_plans ON subscriptions.plan_id = subscription_plans.id'
        ];
        $searchableColumns = ['users.first_name', 'users.last_name', 'users.email', 'users.status'];
        $columnMapping = [
            'student_name' => 'users.first_name',
            'email' => 'users.email',
            'email_verified_at' => 'users.email_verified_at',
            'status' => 'users.status',
            'completion' => 'student_profiles.profile_completion_percentage',
            'plan_name' => 'subscription_plans.name',
            'created_at' => 'users.created_at'
        ];
        $result = \App\Helpers\DataTableHelper::process(
            $db,
            'users',
            $columns,
            $searchableColumns,
            $columnMapping,
            $joins,
            $customWhere,
            $customParams,
            function($row) {
                $row['record_id'] = encode_id((int)$row['id']);
                $row['student_name'] = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
                $row['completion'] = $row['completion'] !== null ? (int)$row['completion'] : 0;
                $row['plan_name'] = $row['plan_name'] ?: 'Free Tier';
                unset($row['id'], $row['first_name'], $row['last_name']);
                return $row;
            }
        );
        header('Content-Type: application/json');
        echo json_encode($result);
        exit();
    }

    public function employeesData(): void {
        Auth::requireRole(['admin', 'employee']);
        if (!Auth::hasPermission('employees.manage')) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Forbidden']);
            exit();
        }
        $db = \App\Services\Database::connection();
        $customWhere = "roles.name IN ('admin', 'employee')";
        $customParams = [];
        if (!empty($_GET['role'])) {
            $customWhere .= " AND roles.name = :role";
            $customParams['role'] = $_GET['role'];
        }
        if (!empty($_GET['status'])) {
            $customWhere .= " AND users.status = :status";
            $customParams['status'] = $_GET['status'];
        }
        $columns = [
            'id' => 'users.id',
            'first_name' => 'users.first_name',
            'last_name' => 'users.last_name',
            'email' => 'users.email',
            'role_name' => 'roles.name',
            'role_id' => 'users.role_id',
            'status' => 'users.status',
            'created_at' => 'users.created_at'
        ];
        $joins = [
            'LEFT JOIN roles ON users.role_id = roles.id'
        ];
        $searchableColumns = ['users.first_name', 'users.last_name', 'users.email', 'roles.name', 'users.status'];
        $columnMapping = [
            'email' => 'users.email',
            'role_name' => 'roles.name',
            'status' => 'users.status',
            'created_at' => 'users.created_at'
        ];
        $result = \App\Helpers\DataTableHelper::process(
            $db,
            'users',
            $columns,
            $searchableColumns,
            $columnMapping,
            $joins,
            $customWhere,
            $customParams,
            function($row) {
                $row['record_id'] = encode_id((int)$row['id']);
                $row['employee_name'] = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
                unset($row['id'], $row['first_name'], $row['last_name']);
                return $row;
            }
        );
        header('Content-Type: application/json');
        echo json_encode($result);
        exit();
    }

    public function countriesData(): void {
        Auth::requireRole(['admin', 'employee']);
        if (!Auth::hasPermission('settings.view')) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Forbidden']);
            exit();
        }
        $db = \App\Services\Database::connection();
        $columns = [
            'id' => 'id',
            'name' => 'name',
            'iso_code' => 'iso2',
            'currency_code' => 'currency_code',
            'dial_code' => 'phone_code'
        ];
        $searchableColumns = ['name', 'iso2', 'phone_code', 'currency_code'];
        $columnMapping = [
            'name' => 'name',
            'iso_code' => 'iso2',
            'currency_code' => 'currency_code',
            'dial_code' => 'phone_code'
        ];
        $result = \App\Helpers\DataTableHelper::process(
            $db,
            'countries',
            $columns,
            $searchableColumns,
            $columnMapping,
            [],
            '',
            [],
            function($row) {
                $row['record_id'] = encode_id((int)$row['id']);
                unset($row['id']);
                return $row;
            }
        );
        header('Content-Type: application/json');
        echo json_encode($result);
        exit();
    }

    public function statesData(): void {
        Auth::requireRole(['admin', 'employee']);
        if (!Auth::hasPermission('settings.view')) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Forbidden']);
            exit();
        }
        $db = \App\Services\Database::connection();
        $columns = [
            'id' => 'states.id',
            'name' => 'states.name',
            'country_name' => 'countries.name'
        ];
        $joins = ['JOIN countries ON states.country_id = countries.id'];
        $searchableColumns = ['states.name', 'countries.name'];
        $columnMapping = [
            'name' => 'states.name',
            'country_name' => 'countries.name'
        ];
        $result = \App\Helpers\DataTableHelper::process(
            $db,
            'states',
            $columns,
            $searchableColumns,
            $columnMapping,
            $joins,
            '',
            [],
            function($row) {
                $row['record_id'] = encode_id((int)$row['id']);
                unset($row['id']);
                return $row;
            }
        );
        header('Content-Type: application/json');
        echo json_encode($result);
        exit();
    }

    public function citiesData(): void {
        Auth::requireRole(['admin', 'employee']);
        if (!Auth::hasPermission('settings.view')) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Forbidden']);
            exit();
        }
        $db = \App\Services\Database::connection();
        $columns = [
            'id' => 'cities.id',
            'name' => 'cities.name',
            'state_name' => 'states.name',
            'country_name' => 'countries.name'
        ];
        $joins = [
            'JOIN states ON cities.state_id = states.id',
            'JOIN countries ON states.country_id = countries.id'
        ];
        $searchableColumns = ['cities.name', 'states.name', 'countries.name'];
        $columnMapping = [
            'name' => 'cities.name',
            'state_name' => 'states.name',
            'country_name' => 'countries.name'
        ];
        $result = \App\Helpers\DataTableHelper::process(
            $db,
            'cities',
            $columns,
            $searchableColumns,
            $columnMapping,
            $joins,
            '',
            [],
            function($row) {
                $row['record_id'] = encode_id((int)$row['id']);
                unset($row['id']);
                return $row;
            }
        );
        header('Content-Type: application/json');
        echo json_encode($result);
        exit();
    }

    public function fieldsData(): void {
        Auth::requireRole(['admin', 'employee']);
        if (!Auth::hasPermission('settings.view')) {
            if (!headers_sent()) http_response_code(403);
            if (!headers_sent()) header('Content-Type: application/json');
            echo json_encode(['error' => 'Forbidden']);
            if (defined('TESTING_MODE') && TESTING_MODE) return;
            exit();
        }
        $db = \App\Services\Database::connection();
        $columns = [
            'id' => 'id',
            'name' => 'name',
            'description' => 'description'
        ];
        $searchableColumns = ['name', 'description'];
        $columnMapping = [
            'name' => 'name',
            'description' => 'description'
        ];
        $result = \App\Helpers\DataTableHelper::process(
            $db,
            'fields_of_study',
            $columns,
            $searchableColumns,
            $columnMapping,
            [],
            '',
            [],
            function($row) {
                $row['record_id'] = encode_id((int)$row['id']);
                unset($row['id']);
                return $row;
            }
        );
        if (!headers_sent()) header('Content-Type: application/json');
        echo json_encode($result);
        if (defined('TESTING_MODE') && TESTING_MODE) return;
        exit();
    }

    public function degreesData(): void {
        Auth::requireRole(['admin', 'employee']);
        if (!Auth::hasPermission('settings.view')) {
            if (!headers_sent()) http_response_code(403);
            if (!headers_sent()) header('Content-Type: application/json');
            echo json_encode(['error' => 'Forbidden']);
            if (defined('TESTING_MODE') && TESTING_MODE) return;
            exit();
        }
        $db = \App\Services\Database::connection();
        $columns = [
            'id' => 'id',
            'name' => 'name',
            'status' => 'status',
            'sort_order' => 'sort_order'
        ];
        $searchableColumns = ['name', 'status'];
        $columnMapping = [
            'name' => 'name',
            'status' => 'status',
            'sort_order' => 'sort_order'
        ];
        $result = \App\Helpers\DataTableHelper::process(
            $db,
            'degree_levels',
            $columns,
            $searchableColumns,
            $columnMapping,
            [],
            '',
            [],
            function($row) {
                $row['record_id'] = encode_id((int)$row['id']);
                unset($row['id']);
                return $row;
            }
        );
        if (!headers_sent()) header('Content-Type: application/json');
        echo json_encode($result);
        if (defined('TESTING_MODE') && TESTING_MODE) return;
        exit();
    }

    public function fundingData(): void {
        Auth::requireRole(['admin', 'employee']);
        if (!Auth::hasPermission('settings.view')) {
            if (!headers_sent()) http_response_code(403);
            if (!headers_sent()) header('Content-Type: application/json');
            echo json_encode(['error' => 'Forbidden']);
            if (defined('TESTING_MODE') && TESTING_MODE) return;
            exit();
        }
        $db = \App\Services\Database::connection();
        $columns = [
            'id' => 'id',
            'name' => 'name',
            'status' => 'status'
        ];
        $searchableColumns = ['name', 'status'];
        $columnMapping = [
            'name' => 'name',
            'status' => 'status'
        ];
        $result = \App\Helpers\DataTableHelper::process(
            $db,
            'funding_types',
            $columns,
            $searchableColumns,
            $columnMapping,
            [],
            '',
            [],
            function($row) {
                $row['record_id'] = encode_id((int)$row['id']);
                unset($row['id']);
                return $row;
            }
        );
        if (!headers_sent()) header('Content-Type: application/json');
        echo json_encode($result);
        if (defined('TESTING_MODE') && TESTING_MODE) return;
        exit();
    }

    public function paymentsData(): void {
        Auth::requireRole(['admin', 'employee']);
        if (!Auth::hasPermission('settings.view')) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Forbidden']);
            exit();
        }
        $db = \App\Services\Database::connection();
        
        $customWhere = "";
        $customParams = [];
        if (!empty($_GET['status'])) {
            $customWhere = "payment_transactions.status = :status";
            $customParams['status'] = $_GET['status'];
        }
        if (!empty($_GET['gateway'])) {
            if ($customWhere !== "") $customWhere .= " AND ";
            $customWhere .= "payment_transactions.provider = :gateway";
            $customParams['gateway'] = $_GET['gateway'];
        }
        if (!empty($_GET['currency'])) {
            if ($customWhere !== "") $customWhere .= " AND ";
            $customWhere .= "payment_transactions.currency = :currency";
            $customParams['currency'] = $_GET['currency'];
        }

        $columns = [
            'id' => 'payment_transactions.id',
            'reference_id' => 'payment_transactions.transaction_reference',
            'gateway_name' => 'payment_transactions.provider',
            'amount' => 'payment_transactions.amount',
            'currency' => 'payment_transactions.currency',
            'status' => 'payment_transactions.status',
            'created_at' => 'payment_transactions.created_at',
            'first_name' => 'users.first_name',
            'last_name' => 'users.last_name',
            'email' => 'users.email'
        ];
        $joins = ['JOIN users ON payment_transactions.user_id = users.id'];
        $searchableColumns = ['payment_transactions.transaction_reference', 'payment_transactions.provider_transaction_id', 'payment_transactions.provider', 'users.first_name', 'users.last_name', 'users.email'];
        $columnMapping = [
            'reference_id' => 'payment_transactions.transaction_reference',
            'gateway_name' => 'payment_transactions.provider',
            'amount' => 'payment_transactions.amount',
            'currency' => 'payment_transactions.currency',
            'status' => 'payment_transactions.status',
            'created_at' => 'payment_transactions.created_at'
        ];
        $result = \App\Helpers\DataTableHelper::process(
            $db,
            'payment_transactions',
            $columns,
            $searchableColumns,
            $columnMapping,
            $joins,
            $customWhere,
            $customParams,
            function($row) {
                $row['record_id'] = encode_id((int)$row['id']);
                $row['student_name'] = e($row['first_name'] . ' ' . $row['last_name']);
                unset($row['id'], $row['first_name'], $row['last_name']);
                return $row;
            }
        );
        header('Content-Type: application/json');
        echo json_encode($result);
        exit();
    }

    public function subscriptionsData(): void {
        Auth::requireRole(['admin', 'employee']);
        if (!Auth::hasPermission('settings.view')) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Forbidden']);
            exit();
        }
        $db = \App\Services\Database::connection();
        
        $customWhere = "";
        $customParams = [];
        if (!empty($_GET['status'])) {
            $customWhere = "subscriptions.status = :status";
            $customParams['status'] = $_GET['status'];
        }

        $columns = [
            'id' => 'subscriptions.id',
            'plan_name' => 'subscription_plans.name',
            'status' => 'subscriptions.status',
            'starts_at' => 'subscriptions.starts_at',
            'ends_at' => 'subscriptions.ends_at',
            'created_at' => 'subscriptions.created_at',
            'first_name' => 'users.first_name',
            'last_name' => 'users.last_name',
            'email' => 'users.email'
        ];
        $joins = [
            'JOIN users ON subscriptions.user_id = users.id',
            'JOIN subscription_plans ON subscriptions.plan_id = subscription_plans.id'
        ];
        $searchableColumns = ['subscription_plans.name', 'users.first_name', 'users.last_name', 'users.email'];
        $columnMapping = [
            'plan_name' => 'subscription_plans.name',
            'status' => 'subscriptions.status',
            'starts_at' => 'subscriptions.starts_at',
            'ends_at' => 'subscriptions.ends_at',
            'created_at' => 'subscriptions.created_at'
        ];
        $result = \App\Helpers\DataTableHelper::process(
            $db,
            'subscriptions',
            $columns,
            $searchableColumns,
            $columnMapping,
            $joins,
            $customWhere,
            $customParams,
            function($row) {
                $row['record_id'] = encode_id((int)$row['id']);
                $row['student_name'] = e($row['first_name'] . ' ' . $row['last_name']);
                unset($row['id'], $row['first_name'], $row['last_name']);
                return $row;
            }
        );
        header('Content-Type: application/json');
        echo json_encode($result);
        exit();
    }

    public function auditLogsData(): void {
        Auth::requireRole(['admin', 'employee']);
        if (!Auth::hasPermission('settings.view')) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Forbidden']);
            exit();
        }
        $db = \App\Services\Database::connection();
        $columns = [
            'id' => 'audit_logs.id',
            'action' => 'audit_logs.action',
            'module' => 'audit_logs.module',
            'resource_type' => 'audit_logs.resource_type',
            'resource_id' => 'audit_logs.resource_id',
            'ip_address' => 'audit_logs.ip_address',
            'user_agent' => 'audit_logs.user_agent',
            'created_at' => 'audit_logs.created_at',
            'first_name' => 'users.first_name',
            'last_name' => 'users.last_name',
            'actor_email' => 'users.email'
        ];
        $joins = ['LEFT JOIN users ON audit_logs.user_id = users.id'];
        $searchableColumns = ['audit_logs.action', 'audit_logs.module', 'users.first_name', 'users.last_name', 'users.email', 'audit_logs.ip_address'];
        $columnMapping = [
            'action' => 'audit_logs.action',
            'module' => 'audit_logs.module',
            'created_at' => 'audit_logs.created_at'
        ];
        $extraWhere = '';
        $extraParams = [];
        $selectedModule = $_GET['module'] ?? '';
        if ($selectedModule !== '') {
            $extraWhere = 'audit_logs.module = :selected_module';
            $extraParams = ['selected_module' => $selectedModule];
        }

        $result = \App\Helpers\DataTableHelper::process(
            $db,
            'audit_logs',
            $columns,
            $searchableColumns,
            $columnMapping,
            $joins,
            $extraWhere,
            $extraParams,
            function($row) {
                $row['record_id'] = encode_id((int)$row['id']);
                $row['actor'] = $row['first_name'] ? e($row['first_name'] . ' ' . $row['last_name']) : 'System';
                unset($row['id'], $row['first_name'], $row['last_name']);
                return $row;
            }
        );
        header('Content-Type: application/json');
        echo json_encode($result);
        exit();
    }

    public function referralsIndex(): void {
        Auth::requirePermission('referrals.view');
        $db = \App\Services\Database::connection();

        $defaultDiscount = \App\Services\ReferralService::getSetting('referral_default_discount_percent', '10.00', $db);
        $defaultCommission = \App\Services\ReferralService::getSetting('referral_default_commission_percent', '30.00', $db);
        $windowMonths = \App\Services\ReferralService::getSetting('referral_attribution_window_months', '6', $db);
        $commissionBasis = \App\Services\ReferralService::getSetting('referral_commission_basis', 'paid_amount_after_discount', $db);

        View::render('admin.referrals.index', [
            'csrf_token' => Security::csrfToken(),
            'default_discount' => $defaultDiscount,
            'default_commission' => $defaultCommission,
            'window_months' => $windowMonths,
            'commission_basis' => $commissionBasis,
            'errors' => $_SESSION['admin_errors'] ?? null,
            'success' => $_SESSION['admin_success'] ?? null
        ]);
        unset($_SESSION['admin_errors'], $_SESSION['admin_success']);
    }

    public function referralsStore(): void {
        Auth::requirePermission('referrals.manage');
        
        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            $_SESSION['admin_errors'] = 'CSRF verification failed.';
            header("Location: " . url("/admin/referrals"));
            exit();
        }

        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $customCode = trim($_POST['referral_code'] ?? '');

        // Strict percentage bounds validation without floats (0.00% to 100.00%)
        $rawDiscount = $_POST['discount_percent'] ?? '10.00';
        $discBps = \App\Services\ReferralService::parsePercentageToBasisPoints($rawDiscount);
        if ($discBps === null) {
            $_SESSION['admin_errors'] = 'Discount percentage must be a valid number between 0.00% and 100.00%.';
            header("Location: " . url("/admin/referrals"));
            exit();
        }
        $discountPercent = sprintf('%d.%02d', intdiv($discBps, 100), $discBps % 100);

        $commissionPercent = null;
        if (isset($_POST['commission_percent']) && trim((string)$_POST['commission_percent']) !== '') {
            $commBps = \App\Services\ReferralService::parsePercentageToBasisPoints($_POST['commission_percent']);
            if ($commBps === null) {
                $_SESSION['admin_errors'] = 'Commission percentage must be a valid number between 0.00% and 100.00%.';
                header("Location: " . url("/admin/referrals"));
                exit();
            }
            $commissionPercent = sprintf('%d.%02d', intdiv($commBps, 100), $commBps % 100);
        }

        if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
            $_SESSION['admin_errors'] = 'All fields are required.';
            header("Location: " . url("/admin/referrals"));
            exit();
        }

        $db = \App\Services\Database::connection();

        // Check duplicate email
        $stmtCheck = $db->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
        $stmtCheck->execute(['email' => $email]);
        if ((int)$stmtCheck->fetchColumn() > 0) {
            $_SESSION['admin_errors'] = 'This email address is already registered.';
            header("Location: " . url("/admin/referrals"));
            exit();
        }

        // Validate or generate unique 8-character uppercase referral code
        if (!empty($customCode)) {
            if (!\App\Services\ReferralService::validateCode($customCode)) {
                $_SESSION['admin_errors'] = 'Referral code must be 1 to 8 alphanumeric characters with no spaces or special characters.';
                header("Location: " . url("/admin/referrals"));
                exit();
            }
            $referralCode = \App\Services\ReferralService::normalizeCode($customCode);
            $stmtCheckCode = $db->prepare("SELECT COUNT(*) FROM users WHERE UPPER(referral_code) = :code");
            $stmtCheckCode->execute(['code' => $referralCode]);
            if ((int)$stmtCheckCode->fetchColumn() > 0) {
                $_SESSION['admin_errors'] = 'Referral code is already in use.';
                header("Location: " . url("/admin/referrals"));
                exit();
            }
        } else {
            $referralCode = \App\Services\ReferralService::generateUniqueCode($db);
        }

        // Get referral_partner role id
        $partnerRoleId = $db->query("SELECT id FROM roles WHERE name = 'referral_partner'")->fetchColumn();
        if (!$partnerRoleId) {
            $_SESSION['admin_errors'] = 'Referral partner role not found in system.';
            header("Location: " . url("/admin/referrals"));
            exit();
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        // Insert referral partner
        $stmt = $db->prepare("
            INSERT INTO users (role_id, first_name, last_name, email, password_hash, status, referral_code, discount_percent, commission_percent, email_verified_at) 
            VALUES (:role_id, :first_name, :last_name, :email, :password_hash, 'active', :referral_code, :discount_percent, :commission_percent, NOW())
        ");
        $stmt->execute([
            'role_id' => $partnerRoleId,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'password_hash' => $passwordHash,
            'referral_code' => $referralCode,
            'discount_percent' => $discountPercent,
            'commission_percent' => $commissionPercent
        ]);

        $partnerId = $db->lastInsertId();
        $this->logAction('referral_partner_create', 'referrals', 'users', $partnerId, ['email' => $email, 'code' => $referralCode]);

        $_SESSION['admin_success'] = 'Referral partner created successfully. Referral code is: ' . $referralCode;
        header("Location: " . url("/admin/referrals"));
        exit();
    }

    public function referralsUpdateCode(string $encodedId): void {
        Auth::requirePermission('referrals.manage');

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            $_SESSION['admin_errors'] = 'CSRF verification failed.';
            header("Location: " . url("/admin/referrals"));
            exit();
        }

        $id = decode_id($encodedId);
        if (!$id) {
            $_SESSION['admin_errors'] = 'Invalid partner ID.';
            header("Location: " . url("/admin/referrals"));
            exit();
        }

        $newCode = trim($_POST['referral_code'] ?? '');
        $db = \App\Services\Database::connection();

        if (empty($newCode)) {
            $newCode = \App\Services\ReferralService::generateUniqueCode($db);
        } else {
            if (!\App\Services\ReferralService::validateCode($newCode)) {
                $_SESSION['admin_errors'] = 'Referral code must be 1 to 8 alphanumeric characters with no spaces or special characters.';
                header("Location: " . url("/admin/referrals"));
                exit();
            }
            $newCode = \App\Services\ReferralService::normalizeCode($newCode);
            $stmtCheck = $db->prepare("SELECT COUNT(*) FROM users WHERE UPPER(referral_code) = :code AND id != :id");
            $stmtCheck->execute(['code' => $newCode, 'id' => $id]);
            if ((int)$stmtCheck->fetchColumn() > 0) {
                $_SESSION['admin_errors'] = 'Referral code already taken by another partner.';
                header("Location: " . url("/admin/referrals"));
                exit();
            }
        }

        $stmtUpd = $db->prepare("UPDATE users SET referral_code = :code WHERE id = :id");
        $stmtUpd->execute(['code' => $newCode, 'id' => $id]);

        $this->logAction('referral_partner_update_code', 'referrals', 'users', $id, ['new_code' => $newCode]);

        $_SESSION['admin_success'] = 'Referral code updated successfully to: ' . $newCode;
        header("Location: " . url("/admin/referrals"));
        exit();
    }

    public function referralsUpdatePercentages(string $encodedId): void {
        Auth::requirePermission('referrals.manage');

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            $_SESSION['admin_errors'] = 'CSRF verification failed.';
            header("Location: " . url("/admin/referrals"));
            exit();
        }

        $id = decode_id($encodedId);
        if (!$id) {
            $_SESSION['admin_errors'] = 'Invalid partner ID.';
            header("Location: " . url("/admin/referrals"));
            exit();
        }

        $rawDiscount = $_POST['discount_percent'] ?? '';
        $discBps = \App\Services\ReferralService::parsePercentageToBasisPoints($rawDiscount);
        if ($discBps === null) {
            $_SESSION['admin_errors'] = 'Discount percentage must be a valid number between 0.00% and 100.00%.';
            header("Location: " . url("/admin/referrals"));
            exit();
        }
        $discountPercent = sprintf('%d.%02d', intdiv($discBps, 100), $discBps % 100);

        $commissionPercent = null;
        if (isset($_POST['commission_percent']) && trim((string)$_POST['commission_percent']) !== '') {
            $commBps = \App\Services\ReferralService::parsePercentageToBasisPoints($_POST['commission_percent']);
            if ($commBps === null) {
                $_SESSION['admin_errors'] = 'Commission percentage must be a valid number between 0.00% and 100.00%.';
                header("Location: " . url("/admin/referrals"));
                exit();
            }
            $commissionPercent = sprintf('%d.%02d', intdiv($commBps, 100), $commBps % 100);
        }

        $db = \App\Services\Database::connection();
        $stmtUpd = $db->prepare("UPDATE users SET discount_percent = :disc, commission_percent = :comm WHERE id = :id");
        $stmtUpd->execute([
            'disc' => $discountPercent,
            'comm' => $commissionPercent,
            'id' => $id
        ]);

        $this->logAction('referral_partner_update_percentages', 'referrals', 'users', $id, [
            'discount_percent' => $discountPercent,
            'commission_percent' => $commissionPercent
        ]);

        $_SESSION['admin_success'] = 'Partner percentages updated successfully.';
        header("Location: " . url("/admin/referrals"));
        exit();
    }

    public function referralsSettingsUpdate(): void {
        Auth::requirePermission('settings.edit');

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            $_SESSION['admin_errors'] = 'CSRF verification failed.';
            header("Location: " . url("/admin/referrals"));
            exit();
        }

        // Strict percentage bounds validation without floats (0.00% to 100.00%)
        $rawDiscount = $_POST['referral_default_discount_percent'] ?? '';
        $discBps = \App\Services\ReferralService::parsePercentageToBasisPoints($rawDiscount);
        if ($discBps === null) {
            $_SESSION['admin_errors'] = 'Default discount percentage must be a valid number between 0.00% and 100.00%.';
            header("Location: " . url("/admin/referrals"));
            exit();
        }

        $rawCommission = $_POST['referral_default_commission_percent'] ?? '';
        $commBps = \App\Services\ReferralService::parsePercentageToBasisPoints($rawCommission);
        if ($commBps === null) {
            $_SESSION['admin_errors'] = 'Default commission percentage must be a valid number between 0.00% and 100.00%.';
            header("Location: " . url("/admin/referrals"));
            exit();
        }

        $windowMonths = max(1, (int)($_POST['referral_attribution_window_months'] ?? 6));
        $basis = trim($_POST['referral_commission_basis'] ?? 'paid_amount_after_discount');

        if (!in_array($basis, ['paid_amount_after_discount', 'original_plan_amount'], true)) {
            $basis = 'paid_amount_after_discount';
        }

        $db = \App\Services\Database::connection();
        $settings = [
            'referral_default_discount_percent' => sprintf('%d.%02d', intdiv($discBps, 100), $discBps % 100),
            'referral_default_commission_percent' => sprintf('%d.%02d', intdiv($commBps, 100), $commBps % 100),
            'referral_attribution_window_months' => (string)$windowMonths,
            'referral_commission_basis' => $basis
        ];

        $stmt = $db->prepare("
            INSERT INTO settings (group_name, `key`, `value`, is_public, created_at, updated_at)
            VALUES ('referral', :key, :value, 0, NOW(), NOW())
            ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), updated_at = NOW()
        ");
        foreach ($settings as $k => $v) {
            $stmt->execute(['key' => $k, 'value' => $v]);
        }

        $this->logAction('referral_settings_update', 'settings', 'settings', 0, $settings);

        $_SESSION['admin_success'] = 'Referral settings updated successfully.';
        header("Location: " . url("/admin/referrals"));
        exit();
    }

    public function referralsCorrectAttribution(): void {
        Auth::requirePermission('referrals.manage');

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            $_SESSION['admin_errors'] = 'CSRF verification failed.';
            header("Location: " . url("/admin/referrals"));
            exit();
        }

        $userId = (int)($_POST['user_id'] ?? 0);
        $newPartnerId = !empty($_POST['partner_id']) ? (int)$_POST['partner_id'] : null;

        // Prevent self-referral attribution
        if ($newPartnerId && $newPartnerId === $userId) {
            $_SESSION['admin_errors'] = 'A user cannot be attributed to themselves as a referral partner.';
            header("Location: " . url("/admin/referrals"));
            exit();
        }

        $db = \App\Services\Database::connection();

        $stmtUser = $db->prepare("SELECT id, referral_partner_id, referred_by_code FROM users WHERE id = :id LIMIT 1");
        $stmtUser->execute(['id' => $userId]);
        $targetUser = $stmtUser->fetch(PDO::FETCH_ASSOC);

        if (!$targetUser) {
            $_SESSION['admin_errors'] = 'Target user not found.';
            header("Location: " . url("/admin/referrals"));
            exit();
        }

        $newCode = null;
        if ($newPartnerId) {
            $stmtPartner = $db->prepare("SELECT id, referral_code FROM users WHERE id = :id AND status = 'active' LIMIT 1");
            $stmtPartner->execute(['id' => $newPartnerId]);
            $partner = $stmtPartner->fetch(PDO::FETCH_ASSOC);
            if (!$partner) {
                $_SESSION['admin_errors'] = 'Selected referral partner not found or inactive.';
                header("Location: " . url("/admin/referrals"));
                exit();
            }
            $newCode = $partner['referral_code'];
        }

        $stmtUpd = $db->prepare("UPDATE users SET referral_partner_id = :pid, referred_by_code = :code WHERE id = :uid");
        $stmtUpd->execute([
            'pid' => $newPartnerId,
            'code' => $newCode,
            'uid' => $userId
        ]);

        $this->logAction('referral_attribution_corrected', 'referrals', 'users', $userId, [
            'old_partner_id' => $targetUser['referral_partner_id'],
            'new_partner_id' => $newPartnerId,
            'new_code' => $newCode
        ]);

        $_SESSION['admin_success'] = 'User referral attribution updated successfully.';
        header("Location: " . url("/admin/referrals"));
        exit();
    }

    public function referralsDelete(string $encodedId): void {
        Auth::requirePermission('referrals.manage');

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            $_SESSION['admin_errors'] = 'CSRF verification failed.';
            header("Location: " . url("/admin/referrals"));
            exit();
        }

        $id = decode_id($encodedId);
        if (!$id) {
            $_SESSION['admin_errors'] = 'Invalid partner ID.';
            header("Location: " . url("/admin/referrals"));
            exit();
        }

        $db = \App\Services\Database::connection();

        // Check if user is actually a referral partner
        $stmtCheck = $db->prepare("
            SELECT u.id, u.referral_code FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE u.id = :id AND r.name = 'referral_partner'
            LIMIT 1
        ");
        $stmtCheck->execute(['id' => $id]);
        $partner = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if (!$partner) {
            $_SESSION['admin_errors'] = 'Partner not found or role does not match.';
            header("Location: " . url("/admin/referrals"));
            exit();
        }

        // Get visitor role id to demote them
        $visitorRoleId = $db->query("SELECT id FROM roles WHERE name = 'visitor'")->fetchColumn();

        $stmtUpd = $db->prepare("
            UPDATE users SET role_id = :visitor_role_id, status = 'suspended', referral_code = NULL 
            WHERE id = :id
        ");
        $stmtUpd->execute([
            'visitor_role_id' => $visitorRoleId,
            'id' => $id
        ]);

        $this->logAction('referral_partner_delete', 'referrals', 'users', $id, ['old_code' => $partner['referral_code']]);

        $_SESSION['admin_success'] = 'Referral partner access revoked successfully.';
        header("Location: " . url("/admin/referrals"));
        exit();
    }

    public function referralsData(): void {
        Auth::requirePermission('referrals.view');
        $db = \App\Services\Database::connection();
        
        $columns = [
            'id' => 'users.id',
            'first_name' => 'users.first_name',
            'last_name' => 'users.last_name',
            'email' => 'users.email',
            'referral_code' => 'users.referral_code',
            'discount_percent' => 'users.discount_percent',
            'commission_percent' => 'users.commission_percent',
            'status' => 'users.status',
            'created_at' => 'users.created_at',
            'conversions' => '(SELECT COUNT(*) FROM referral_commissions rc WHERE rc.partner_id = users.id AND rc.status = \'earned\')',
            'total_earned' => '(SELECT COALESCE(SUM(rc.commission_amount), 0.00) FROM referral_commissions rc WHERE rc.partner_id = users.id AND rc.status = \'earned\')'
        ];
        
        $joins = [
            'LEFT JOIN roles ON users.role_id = roles.id'
        ];
        
        $customWhere = "roles.name = 'referral_partner'";
        $customParams = [];
        
        $searchableColumns = ['users.first_name', 'users.last_name', 'users.email', 'users.referral_code'];
        $columnMapping = [
            'referral_code' => 'users.referral_code',
            'discount_percent' => 'users.discount_percent',
            'commission_percent' => 'users.commission_percent',
            'status' => 'users.status',
            'created_at' => 'users.created_at'
        ];
        
        $result = \App\Helpers\DataTableHelper::process(
            $db,
            'users',
            $columns,
            $searchableColumns,
            $columnMapping,
            $joins,
            $customWhere,
            $customParams,
            function($row) {
                $row['record_id'] = encode_id((int)$row['id']);
                $row['partner_name'] = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
                $row['conversions'] = (int)$row['conversions'];
                $row['payouts'] = number_format((float)($row['total_earned'] ?? 0), 2);
                unset($row['id'], $row['first_name'], $row['last_name']);
                return $row;
            }
        );
        header('Content-Type: application/json');
        echo json_encode($result);
        exit();
    }

    public function referralsCommissionsData(): void {
        Auth::requirePermission('referrals.view');
        $db = \App\Services\Database::connection();

        $columns = [
            'id' => 'referral_commissions.id',
            'partner_name' => 'CONCAT(partner.first_name, \' \', partner.last_name)',
            'partner_email' => 'partner.email',
            'referred_user' => 'CONCAT(ref_user.first_name, \' \', ref_user.last_name)',
            'referred_email' => 'ref_user.email',
            'transaction_reference' => 'referral_commissions.transaction_reference',
            'original_plan_amount' => 'referral_commissions.original_plan_amount',
            'referral_discount_amount' => 'referral_commissions.referral_discount_amount',
            'actual_paid_amount' => 'referral_commissions.actual_paid_amount',
            'commission_percentage' => 'referral_commissions.commission_percentage',
            'commission_basis' => 'referral_commissions.commission_basis',
            'commission_amount' => 'referral_commissions.commission_amount',
            'payment_date' => 'referral_commissions.payment_date',
            'status' => 'referral_commissions.status'
        ];

        $joins = [
            'JOIN users partner ON referral_commissions.partner_id = partner.id',
            'JOIN users ref_user ON referral_commissions.referred_user_id = ref_user.id'
        ];

        $customWhere = '1=1';
        $customParams = [];

        if (!empty($_GET['partner_id'])) {
            $customWhere .= ' AND referral_commissions.partner_id = :filter_pid';
            $customParams['filter_pid'] = (int)$_GET['partner_id'];
        }
        if (!empty($_GET['month'])) {
            $customWhere .= ' AND referral_commissions.payment_date BETWEEN :filter_mstart AND :filter_mend';
            $customParams['filter_mstart'] = $_GET['month'] . '-01 00:00:00';
            $customParams['filter_mend'] = date('Y-m-t 23:59:59', strtotime($customParams['filter_mstart']));
        }
        if (!empty($_GET['status'])) {
            $customWhere .= ' AND referral_commissions.status = :filter_status';
            $customParams['filter_status'] = $_GET['status'];
        }

        $searchableColumns = ['partner.first_name', 'partner.last_name', 'partner.email', 'ref_user.first_name', 'ref_user.last_name', 'referral_commissions.transaction_reference'];
        $columnMapping = [
            'payment_date' => 'referral_commissions.payment_date',
            'commission_amount' => 'referral_commissions.commission_amount',
            'actual_paid_amount' => 'referral_commissions.actual_paid_amount',
            'status' => 'referral_commissions.status'
        ];

        $result = \App\Helpers\DataTableHelper::process(
            $db,
            'referral_commissions',
            $columns,
            $searchableColumns,
            $columnMapping,
            $joins,
            $customWhere,
            $customParams,
            function($row) {
                $row['record_id'] = encode_id((int)$row['id']);
                return $row;
            }
        );
        header('Content-Type: application/json');
        echo json_encode($result);
        exit();
    }
}
