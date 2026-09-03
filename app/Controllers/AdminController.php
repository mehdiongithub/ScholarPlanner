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

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            $_SESSION['admin_errors'] = 'CSRF verification failed.';
            header("Location: " . url("/admin/academic/fields"));
            exit();
        }

        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');

        if ($name === '') {
            $_SESSION['admin_errors'] = 'Field Name is required.';
            header("Location: " . url("/admin/academic/fields"));
            exit();
        }

        $stmt = $this->db->prepare("INSERT INTO fields_of_study (name, description) VALUES (:name, :desc)");
        $stmt->execute(['name' => $name, 'desc' => $desc]);
        $fId = $this->db->lastInsertId();

        $this->logAction('field_create', 'academic', 'fields_of_study', $fId, ['name' => $name]);
        $_SESSION['admin_success'] = 'Field of Study added successfully.';
        header("Location: " . url("/admin/academic/fields"));
    }

    public function fieldsEdit(string $id): void {
        Auth::requirePermission('settings.view');
        $id = $this->resolveId($id);

        $stmt = $this->db->prepare("SELECT * FROM fields_of_study WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $field = $stmt->fetch();
        if (!$field) {
            http_response_code(404);
            echo "Field not found";
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

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            $_SESSION['admin_errors'] = 'CSRF verification failed.';
            header("Location: " . url("/admin/academic/fields/$encId/edit"));
            exit();
        }

        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');

        if ($name === '') {
            $_SESSION['admin_errors'] = 'Field Name is required.';
            header("Location: " . url("/admin/academic/fields/$encId/edit"));
            exit();
        }

        $stmt = $this->db->prepare("UPDATE fields_of_study SET name = :name, description = :desc WHERE id = :id");
        $stmt->execute(['name' => $name, 'desc' => $desc, 'id' => $id]);

        $this->logAction('field_update', 'academic', 'fields_of_study', $id, ['name' => $name]);
        $_SESSION['admin_success'] = 'Field of Study updated successfully.';
        header("Location: " . url("/admin/academic/fields"));
        exit();
    }

    /**
     * POST /admin/academic/fields/{id}/delete
     */
    public function fieldsDelete(string $id): void {
        Auth::requirePermission('settings.edit');
        $id = $this->resolveId($id, true);

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            http_response_code(400);
            echo json_encode(['error' => 'CSRF verification failed']);
            exit();
        }

        $stmt = $this->db->prepare("DELETE FROM fields_of_study WHERE id = ?");
        $stmt->execute([$id]);

        $this->logAction('field_delete', 'academic', 'fields_of_study', $id);
        
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Field deleted successfully.']);
            exit();
        }
        $_SESSION['admin_success'] = 'Field of Study deleted successfully.';
        header("Location: " . url("/admin/academic/fields"));
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

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            $_SESSION['admin_errors'] = 'CSRF verification failed.';
            header("Location: " . url("/admin/academic/degrees"));
            exit();
        }

        $name = trim($_POST['name'] ?? '');
        $sort = (int)($_POST['sort_order'] ?? 0);
        $status = trim($_POST['status'] ?? 'active');

        if ($name === '') {
            $_SESSION['admin_errors'] = 'Degree Level Name is required.';
            header("Location: " . url("/admin/academic/degrees"));
            exit();
        }

        $stmt = $this->db->prepare("INSERT INTO degree_levels (name, sort_order, status) VALUES (:name, :sort, :status)");
        $stmt->execute(['name' => $name, 'sort' => $sort, 'status' => $status]);
        $dId = $this->db->lastInsertId();

        $this->logAction('degree_create', 'academic', 'degree_levels', $dId, ['name' => $name]);
        $_SESSION['admin_success'] = 'Degree Level added successfully.';
        header("Location: " . url("/admin/academic/degrees"));
    }

    public function degreesEdit(string $id): void {
        Auth::requirePermission('settings.view');
        $id = $this->resolveId($id);

        $stmt = $this->db->prepare("SELECT * FROM degree_levels WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $degree = $stmt->fetch();
        if (!$degree) {
            http_response_code(404);
            echo "Degree level not found";
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

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            $_SESSION['admin_errors'] = 'CSRF verification failed.';
            header("Location: " . url("/admin/academic/degrees/$encId/edit"));
            exit();
        }

        $name = trim($_POST['name'] ?? '');
        $sort = (int)($_POST['sort_order'] ?? 0);
        $status = trim($_POST['status'] ?? 'active');

        if ($name === '') {
            $_SESSION['admin_errors'] = 'Degree Level Name is required.';
            header("Location: " . url("/admin/academic/degrees/$encId/edit"));
            exit();
        }

        $stmt = $this->db->prepare("UPDATE degree_levels SET name = :name, sort_order = :sort, status = :status WHERE id = :id");
        $stmt->execute(['name' => $name, 'sort' => $sort, 'status' => $status, 'id' => $id]);

        $this->logAction('degree_update', 'academic', 'degree_levels', $id, ['name' => $name]);
        $_SESSION['admin_success'] = 'Degree Level updated successfully.';
        header("Location: " . url("/admin/academic/degrees"));
        exit();
    }

    /**
     * POST /admin/academic/degrees/{id}/delete
     */
    public function degreesDelete(string $id): void {
        Auth::requirePermission('settings.edit');
        $id = $this->resolveId($id, true);

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            http_response_code(400);
            echo json_encode(['error' => 'CSRF verification failed']);
            exit();
        }

        $stmt = $this->db->prepare("DELETE FROM degree_levels WHERE id = ?");
        $stmt->execute([$id]);

        $this->logAction('degree_delete', 'academic', 'degree_levels', $id);
        
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Degree Level deleted successfully.']);
            exit();
        }
        $_SESSION['admin_success'] = 'Degree Level deleted successfully.';
        header("Location: " . url("/admin/academic/degrees"));
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

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            $_SESSION['admin_errors'] = 'CSRF verification failed.';
            header("Location: " . url("/admin/academic/funding"));
            exit();
        }

        $name = trim($_POST['name'] ?? '');
        $status = trim($_POST['status'] ?? 'active');

        if ($name === '') {
            $_SESSION['admin_errors'] = 'Funding Type Name is required.';
            header("Location: " . url("/admin/academic/funding"));
            exit();
        }

        $stmt = $this->db->prepare("INSERT INTO funding_types (name, status) VALUES (:name, :status)");
        $stmt->execute(['name' => $name, 'status' => $status]);
        $fId = $this->db->lastInsertId();

        $this->logAction('funding_create', 'academic', 'funding_types', $fId, ['name' => $name]);
        $_SESSION['admin_success'] = 'Funding Type added successfully.';
        header("Location: " . url("/admin/academic/funding"));
    }

    public function fundingEdit(string $id): void {
        Auth::requirePermission('settings.view');
        $id = $this->resolveId($id);

        $stmt = $this->db->prepare("SELECT * FROM funding_types WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $funding = $stmt->fetch();
        if (!$funding) {
            http_response_code(404);
            echo "Funding type not found";
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

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            $_SESSION['admin_errors'] = 'CSRF verification failed.';
            header("Location: " . url("/admin/academic/funding/$encId/edit"));
            exit();
        }

        $name = trim($_POST['name'] ?? '');
        $status = trim($_POST['status'] ?? 'active');

        if ($name === '') {
            $_SESSION['admin_errors'] = 'Funding Type Name is required.';
            header("Location: " . url("/admin/academic/funding/$encId/edit"));
            exit();
        }

        $stmt = $this->db->prepare("UPDATE funding_types SET name = :name, status = :status WHERE id = :id");
        $stmt->execute(['name' => $name, 'status' => $status, 'id' => $id]);

        $this->logAction('funding_update', 'academic', 'funding_types', $id, ['name' => $name]);
        $_SESSION['admin_success'] = 'Funding Type updated successfully.';
        header("Location: " . url("/admin/academic/funding"));
        exit();
    }

    /**
     * POST /admin/academic/funding/{id}/delete
     */
    public function fundingDelete(string $id): void {
        Auth::requirePermission('settings.edit');
        $id = $this->resolveId($id, true);

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            http_response_code(400);
            echo json_encode(['error' => 'CSRF verification failed']);
            exit();
        }

        $stmt = $this->db->prepare("DELETE FROM funding_types WHERE id = ?");
        $stmt->execute([$id]);

        $this->logAction('funding_delete', 'academic', 'funding_types', $id);
        
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Funding Type deleted successfully.']);
            exit();
        }
        $_SESSION['admin_success'] = 'Funding Type deleted successfully.';
        header("Location: " . url("/admin/academic/funding"));
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

        View::render('admin.settings', [
            'user' => Auth::currentUser(),
            'groups' => $groups,
            'schedulerSettings' => $schedulerSettings,
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

        // Validate notification scheduler settings if submitted
        if (isset($_POST['whatsapp_send_time']) || isset($_POST['is_notification_settings'])) {
            $schedulerService = new \App\Services\NotificationSchedulerService($this->db);
            $validation = $schedulerService->validateSettings($_POST);
            if (!$validation['valid']) {
                $_SESSION['admin_errors'] = implode(' ', $validation['errors']);
                header("Location: " . url("/admin/settings"));
                return;
            }
            foreach ($validation['sanitized'] as $k => $v) {
                $_POST[$k] = $v;
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
        if (!empty($_GET['status'])) {
            $customWhere .= " AND users.status = :status";
            $customParams['status'] = $_GET['status'];
        }
        if (isset($_GET['email_verified']) && $_GET['email_verified'] !== '') {
            if ($_GET['email_verified'] == '1') {
                $customWhere .= " AND users.email_verified_at IS NOT NULL";
            } else {
                $customWhere .= " AND users.email_verified_at IS NULL";
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
            'LEFT JOIN subscriptions ON users.id = subscriptions.user_id AND subscriptions.status = \'active\' AND (subscriptions.expires_at IS NULL OR subscriptions.expires_at > NOW())',
            'LEFT JOIN subscription_plans ON subscriptions.plan_id = subscription_plans.id'
        ];
        $searchableColumns = ['users.first_name', 'users.last_name', 'users.email', 'users.status'];
        $columnMapping = [
            'email' => 'users.email',
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
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Forbidden']);
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
            'academic_fields',
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

    public function degreesData(): void {
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
        header('Content-Type: application/json');
        echo json_encode($result);
        exit();
    }

    public function fundingData(): void {
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
        header('Content-Type: application/json');
        echo json_encode($result);
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
        View::render('admin.referrals.index', [
            'csrf_token' => Security::csrfToken(),
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
        $discountPercent = (float)($_POST['discount_percent'] ?? 10.00);

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

        // Generate unique referral code
        $referralCode = 'PARTNER_' . strtoupper(bin2hex(random_bytes(3)));

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
            INSERT INTO users (role_id, first_name, last_name, email, password_hash, status, referral_code, discount_percent, email_verified_at) 
            VALUES (:role_id, :first_name, :last_name, :email, :password_hash, 'active', :referral_code, :discount_percent, NOW())
        ");
        $stmt->execute([
            'role_id' => $partnerRoleId,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'password_hash' => $passwordHash,
            'referral_code' => $referralCode,
            'discount_percent' => $discountPercent
        ]);

        $partnerId = $db->lastInsertId();
        $this->logAction('referral_partner_create', 'referrals', 'users', $partnerId, ['email' => $email, 'code' => $referralCode]);

        $_SESSION['admin_success'] = 'Referral partner created successfully. Referral code is: ' . $referralCode;
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
            'status' => 'users.status',
            'created_at' => 'users.created_at',
            'conversions' => '(SELECT COUNT(*) FROM payment_transactions pt WHERE pt.referral_code_used = users.referral_code AND pt.status IN (\'paid\', \'success\'))'
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
                $row['payouts'] = number_format($row['conversions'] * 20.00, 2);
                unset($row['id'], $row['first_name'], $row['last_name']);
                return $row;
            }
        );
        header('Content-Type: application/json');
        echo json_encode($result);
        exit();
    }
}
