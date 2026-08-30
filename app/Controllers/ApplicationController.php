<?php
namespace App\Controllers;

use PDO;
use Exception;
use RuntimeException;
use App\Services\Database;
use App\Services\Auth;
use App\Services\DocumentReadinessService;
use App\Services\NotificationQueueService;
use App\Helpers\Security;
use App\Helpers\View;

class ApplicationController {
    private PDO $db;
    
    // Status whitelist
    private array $allowedStatuses = [
        'interested',
        'planning',
        'documents_pending',
        'ready_to_apply',
        'applied',
        'interview',
        'accepted',
        'rejected',
        'withdrawn'
    ];

    public function __construct() {
        $this->db = Database::connection();
    }

    private function halt(): void {
        if (defined('TESTING_MODE') && TESTING_MODE) {
            throw new RuntimeException("Halt execution");
        }
        exit();
    }

    private function dieWithError(int $code, string $message): void {
        http_response_code($code);
        if (defined('TESTING_MODE') && TESTING_MODE) {
            throw new RuntimeException($message);
        }
        die($message);
    }

    private function logAudit(string $action, ?int $targetUserId, ?int $targetId, array $meta = []): void {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO audit_logs (user_id, action, module, resource_type, resource_id, ip_address, metadata)
                VALUES (:uid, :action, 'applications', 'application', :res_id, :ip, :meta)
            ");
            $stmt->execute([
                'uid' => Auth::userId() ?: $targetUserId,
                'action' => $action,
                'res_id' => $targetId,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                'meta' => json_encode($meta)
            ]);
        } catch (\Exception $e) {
            // Fail-safe silently
        }
    }

    /**
     * GET /applications
     * Displays paginated dashboard listing of student applications
     */
    public function index(): void {
        Auth::requireAuth();
        $userId = Auth::userId();

        $search = trim($_GET['search'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $sort = trim($_GET['sort'] ?? 'newest'); // 'newest' or 'deadline'
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $whereClauses = ["sa.user_id = :user_id"];
        $params = ['user_id' => $userId];

        if ($search !== '') {
            $whereClauses[] = "(s.title LIKE :search OR s.provider_name LIKE :search)";
            $params['search'] = '%' . $search . '%';
        }

        if ($status !== '') {
            $whereClauses[] = "sa.status = :status";
            $params['status'] = $status;
        }

        $whereSql = implode(" AND ", $whereClauses);

        // Count total
        $countStmt = $this->db->prepare("
            SELECT COUNT(*) FROM scholarship_applications sa
            JOIN scholarships s ON sa.scholarship_id = s.id
            WHERE $whereSql
        ");
        $countStmt->execute($params);
        $totalItems = (int)$countStmt->fetchColumn();
        $totalPages = ceil($totalItems / $limit);

        // Order clause
        $orderSql = "sa.created_at DESC";
        if ($sort === 'deadline') {
            $orderSql = "s.application_deadline ASC";
        }

        // Select
        $selectStmt = $this->db->prepare("
            SELECT sa.*, s.title, s.provider_name, s.application_deadline, s.slug, s.funding_type,
                   sm.match_score, sm.eligibility_status
             FROM scholarship_applications sa
             JOIN scholarships s ON sa.scholarship_id = s.id
             LEFT JOIN scholarship_matches sm ON sm.scholarship_id = s.id AND sm.user_id = :uid
             WHERE $whereSql
             ORDER BY $orderSql
             LIMIT $limit OFFSET $offset
        ");
        
        $selectParams = array_merge($params, ['uid' => $userId]);
        $selectStmt->execute($selectParams);
        $applications = $selectStmt->fetchAll(PDO::FETCH_ASSOC);

        // Add Document Readiness to each application record
        $readinessService = new DocumentReadinessService();
        foreach ($applications as &$app) {
            $app['readiness'] = $readinessService->calculateForScholarship($userId, (int)$app['scholarship_id']);
            
            // Deadline details
            $deadline = $app['application_deadline'];
            if (!$deadline) {
                $app['days_remaining'] = null;
                $app['deadline_status'] = 'rolling';
            } else {
                $deadlineUnix = strtotime($deadline);
                $todayUnix = strtotime(date('Y-m-d'));
                $diffSec = $deadlineUnix - $todayUnix;
                $days = (int)round($diffSec / 86400);
                
                $app['days_remaining'] = $days;
                if ($days < 0) {
                    $app['deadline_status'] = 'passed';
                } elseif ($days <= 7) {
                    $app['deadline_status'] = 'closing_soon';
                } else {
                    $app['deadline_status'] = 'active';
                }
            }
        }

        View::render('applications.index', [
            'applications' => $applications,
            'search' => $search,
            'status' => $status,
            'sort' => $sort,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
            'csrf_token' => Security::csrfToken()
        ]);
    }

    /**
     * POST /applications
     * Adds scholarship opportunity to tracker list
     */
    public function store(): void {
        Auth::requireAuth();
        $userId = Auth::userId();

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            $_SESSION['application_errors'] = ['csrf' => 'CSRF verification failed. Please try again.'];
            header("Location: " . url('/applications'));
            $this->halt();
        }

        $scholarshipId = (int)($_POST['scholarship_id'] ?? 0);
        $status = strtolower(trim($_POST['status'] ?? 'interested'));
        $refNum = trim($_POST['application_reference'] ?? '');
        $personalNotes = trim($_POST['personal_notes'] ?? '');

        $errors = [];

        // Verify scholarship
        $stmtSch = $this->db->prepare("SELECT * FROM scholarships WHERE id = :id LIMIT 1");
        $stmtSch->execute(['id' => $scholarshipId]);
        $sch = $stmtSch->fetch(PDO::FETCH_ASSOC);
        if (!$sch || $sch['status'] !== 'published') {
            $errors['scholarship_id'] = 'Invalid or unpublished scholarship.';
        } else {
            // Check deadline
            if ($sch['application_deadline'] && strtotime($sch['application_deadline']) < strtotime(date('Y-m-d'))) {
                $errors['scholarship_id'] = 'This scholarship deadline has already passed.';
            }
        }

        if (!in_array($status, $this->allowedStatuses)) {
            $errors['status'] = 'Invalid application status selected.';
        }

        // Check if student tries to set status to admin/review statuses
        $adminStatuses = ['interview', 'accepted', 'rejected'];
        if (!Auth::hasRole(['admin', 'employee'])) {
            if (in_array($status, $adminStatuses)) {
                $errors['status'] = 'Unauthorized status selection.';
            }
        }

        // Document readiness check for applied
        if ($status === 'applied') {
            $readinessService = new DocumentReadinessService();
            $readiness = $readinessService->calculateForScholarship($userId, $scholarshipId);
            if ($readiness['readiness_percentage'] < 100) {
                $errors['status'] = 'All required documents must be uploaded and approved before applying.';
            }
        }

        if (strlen($refNum) > 100) {
            $errors['application_reference'] = 'Reference number cannot exceed 100 characters.';
        }

        if (strlen($personalNotes) > 2000) {
            $errors['personal_notes'] = 'Personal notes cannot exceed 2000 characters.';
        }

        // Check duplicate
        $stmtDup = $this->db->prepare("SELECT id FROM scholarship_applications WHERE user_id = :uid AND scholarship_id = :sid LIMIT 1");
        $stmtDup->execute(['uid' => $userId, 'sid' => $scholarshipId]);
        if ($stmtDup->fetch()) {
             $errors['scholarship_id'] = 'This scholarship is already in your application tracker.';
        }

        if (!empty($errors)) {
            $_SESSION['application_errors'] = $errors;
            header("Location: " . url('/scholarships/' . ($sch['slug'] ?? '')));
            $this->halt();
        }

        $this->db->beginTransaction();
        try {
            $appliedAt = ($status === 'applied') ? date('Y-m-d H:i:s') : null;

            // Insert
            $stmtIns = $this->db->prepare("
                INSERT INTO scholarship_applications (user_id, scholarship_id, status, application_reference, personal_notes, applied_at)
                VALUES (:uid, :sid, :status, :ref, :notes, :applied)
            ");
            $stmtIns->execute([
                'uid' => $userId,
                'sid' => $scholarshipId,
                'status' => $status,
                'ref' => $refNum !== '' ? $refNum : null,
                'notes' => $personalNotes !== '' ? $personalNotes : null,
                'applied' => $appliedAt
            ]);

            $appId = (int)$this->db->lastInsertId();

            // Log to history
            $stmtHist = $this->db->prepare("
                INSERT INTO scholarship_application_history (application_id, old_status, new_status, changed_by, notes)
                VALUES (:aid, 'none', :new_status, :changed_by, 'Application tracking initialized.')
            ");
            $stmtHist->execute([
                'aid' => $appId,
                'new_status' => $status,
                'changed_by' => $userId
            ]);

            $this->logAudit('application.create', $userId, $appId, ['scholarship_title' => $sch['title']]);

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            if ($e->getCode() === '23000' || strpos($e->getMessage(), '1062 Duplicate entry') !== false) {
                $_SESSION['application_errors'] = ['scholarship_id' => 'This scholarship is already in your application tracker.'];
                header("Location: " . url('/scholarships/' . ($sch['slug'] ?? '')));
                $this->halt();
            }
            throw $e;
        }

        $_SESSION['application_success'] = 'Scholarship added to tracker successfully.';
        header("Location: " . url('/applications'));
        $this->halt();
    }

    /**
     * GET /applications/{id}
     * Displays tracking details, documents checklist status, matching indicators and logs
     */
    public function show(string $id): void {
        Auth::requireAuth();
        $rawId = decode_id($id);
        if ($rawId === null) {
            $this->dieWithError(404, "Invalid application ID.");
        }
        $id = $rawId;
        $userId = Auth::userId();

        $stmt = $this->db->prepare("
            SELECT sa.*, s.title, s.provider_name, s.application_deadline, s.slug, s.funding_type,
                   sm.match_score, sm.eligibility_status
            FROM scholarship_applications sa
            JOIN scholarships s ON sa.scholarship_id = s.id
            LEFT JOIN scholarship_matches sm ON sm.scholarship_id = s.id AND sm.user_id = :uid
            WHERE sa.id = :id 
            LIMIT 1
        ");
        $stmt->execute(['id' => $id, 'uid' => $userId]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$app) {
            $this->dieWithError(404, "Application tracker record not found.");
        }

        // IDOR Ownership Guard
        if ((int)$app['user_id'] !== $userId) {
            $this->dieWithError(403, "Unauthorized access to application tracker.");
        }

        // Fetch application-related notifications history
        $stmtNotif = $this->db->prepare("
            SELECT * FROM notification_logs 
            WHERE user_id = :uid AND scholarship_id = :sid 
            ORDER BY created_at DESC
        ");
        $stmtNotif->execute([
            'uid' => $app['user_id'],
            'sid' => $app['scholarship_id']
        ]);
        $notifications = $stmtNotif->fetchAll(PDO::FETCH_ASSOC);

        // Document readiness
        $readinessService = new DocumentReadinessService();
        $readiness = $readinessService->calculateForScholarship($userId, (int)$app['scholarship_id']);

        // Status history logs
        $stmtHistory = $this->db->prepare("
            SELECT h.*, u.first_name, u.last_name, r.name as role_name
            FROM scholarship_application_history h
            JOIN users u ON h.changed_by = u.id
            JOIN roles r ON u.role_id = r.id
            WHERE h.application_id = :aid
            ORDER BY h.changed_at DESC
        ");
        $stmtHistory->execute(['aid' => $id]);
        $history = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);

        // Days remaining
        $deadline = $app['application_deadline'];
        $daysRemaining = null;
        $deadlineStatus = 'rolling';
        if ($deadline) {
            $daysRemaining = (int)round((strtotime($deadline) - strtotime(date('Y-m-d'))) / 86400);
            $deadlineStatus = ($daysRemaining < 0) ? 'passed' : (($daysRemaining <= 7) ? 'closing_soon' : 'active');
        }

        // Defense-in-depth: Unset internal staff notes to prevent any leakage to student view
        unset($app['internal_notes']);

        View::render('applications.show', [
            'app' => $app,
            'readiness' => $readiness,
            'history' => $history,
            'notifications' => $notifications,
            'days_remaining' => $daysRemaining,
            'deadline_status' => $deadlineStatus,
            'csrf_token' => Security::csrfToken()
        ]);
    }

    /**
     * POST /applications/{id}/update
     * Updates status, notes, references, and logs transitions
     */
    public function update(string $id): void {
        Auth::requireAuth();
        $rawId = decode_id($id);
        if ($rawId === null) {
            $this->dieWithError(404, "Invalid application ID.");
        }
        $id = $rawId;
        $encId = encode_id($id);
        $userId = Auth::userId();

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            $_SESSION['application_errors'] = ['csrf' => 'CSRF verification failed. Please try again.'];
            header("Location: " . url('/applications/' . $encId));
            $this->halt();
        }

        // Fetch existing
        $stmt = $this->db->prepare("SELECT * FROM scholarship_applications WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$app) {
            $this->dieWithError(404, "Application tracker record not found.");
        }

        // Ownership validation
        if ((int)$app['user_id'] !== $userId) {
            $this->dieWithError(403, "Unauthorized access to application tracker.");
        }

        $status = strtolower(trim($_POST['status'] ?? $app['status']));
        $refNum = trim($_POST['application_reference'] ?? '');
        $personalNotes = trim($_POST['personal_notes'] ?? '');
        $appliedAtInput = trim($_POST['applied_at'] ?? '');

        $errors = [];

        if (!in_array($status, $this->allowedStatuses)) {
            $errors['status'] = 'Invalid application status selected.';
        }

        $oldStatus = $app['status'];

        // Get scholarship details
        $stmtSch = $this->db->prepare("SELECT status, application_deadline FROM scholarships WHERE id = :id LIMIT 1");
        $stmtSch->execute(['id' => $app['scholarship_id']]);
        $sch = $stmtSch->fetch(PDO::FETCH_ASSOC);

        // Check if student (visitor) is modifying
        $adminStatuses = ['interview', 'accepted', 'rejected'];
        if (!Auth::hasRole(['admin', 'employee'])) {
            // Cannot change to administrative status
            if (in_array($status, $adminStatuses)) {
                $errors['status'] = 'Unauthorized status selection.';
            }
            // Cannot change from administrative status
            if (in_array($oldStatus, $adminStatuses)) {
                $errors['status'] = 'Cannot modify status after administrative review.';
            }
            
            // Block transitions on inactive/archived or passed deadline scholarships (unless withdrawing)
            if ($sch) {
                if ($sch['status'] !== 'published') {
                    $errors['status'] = 'This scholarship opportunity has been closed or archived.';
                } elseif ($sch['application_deadline'] && strtotime($sch['application_deadline']) < strtotime(date('Y-m-d')) && $status !== 'withdrawn' && $status !== $oldStatus) {
                    $errors['status'] = 'The application deadline for this scholarship has already passed.';
                }
            }

            // Document readiness check for transition to applied
            if ($status === 'applied' && $oldStatus !== 'applied') {
                $readinessService = new DocumentReadinessService();
                $readiness = $readinessService->calculateForScholarship($userId, (int)$app['scholarship_id']);
                if ($readiness['readiness_percentage'] < 100) {
                    $errors['status'] = 'All required documents must be uploaded and approved before applying.';
                }
            }

            // If already applied, cannot change back to planning/interested
            if ($oldStatus === 'applied' && $status !== 'applied' && $status !== 'withdrawn') {
                $errors['status'] = 'Cannot revert status back after submission.';
            }
        }

        if (strlen($refNum) > 100) {
            $errors['application_reference'] = 'Reference number cannot exceed 100 characters.';
        }

        if (strlen($personalNotes) > 2000) {
            $errors['personal_notes'] = 'Personal notes cannot exceed 2000 characters.';
        }

        $appliedAt = $app['applied_at'];
        if ($status === 'applied') {
            if ($appliedAtInput !== '') {
                $unix = strtotime($appliedAtInput);
                if (!$unix) {
                    $errors['applied_at'] = 'Invalid submission date format.';
                } else {
                    $appliedAt = date('Y-m-d H:i:s', $unix);
                }
            } else {
                $appliedAt = $app['applied_at'] ?: date('Y-m-d H:i:s');
            }
        } else {
            $appliedAt = null;
        }

        if (!empty($errors)) {
            $_SESSION['application_errors'] = $errors;
            header("Location: " . url('/applications/' . $encId));
            $this->halt();
        }

        $this->db->beginTransaction();
        try {
            // Update table
            $stmtUp = $this->db->prepare("
                UPDATE scholarship_applications 
                SET status = :status,
                    application_reference = :ref,
                    personal_notes = :notes,
                    applied_at = :applied
                WHERE id = :id
            ");
            $stmtUp->execute([
                'status' => $status,
                'ref' => $refNum !== '' ? $refNum : null,
                'notes' => $personalNotes !== '' ? $personalNotes : null,
                'applied' => $appliedAt,
                'id' => $id
            ]);

            // Log status history if changed
            if ($oldStatus !== $status) {
                $stmtHist = $this->db->prepare("
                    INSERT INTO scholarship_application_history (application_id, old_status, new_status, changed_by, notes)
                    VALUES (:aid, :old_status, :new_status, :changed_by, 'Status changed by applicant.')
                ");
                $stmtHist->execute([
                    'aid' => $id,
                    'old_status' => $oldStatus,
                    'new_status' => $status,
                    'changed_by' => $userId
                ]);
            }

            $this->logAudit('application.update', $userId, $id, [
                'old_status' => $oldStatus,
                'new_status' => $status
            ]);

            $this->db->commit();
            \App\Services\CacheService::clear();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }

        $_SESSION['application_success'] = 'Application tracker updated successfully.';
        header("Location: " . url('/applications/' . $encId));
        $this->halt();
    }

    /**
     * POST /applications/{id}/delete
     * Removes application tracker record
     */
    public function delete(string $id): void {
        Auth::requireAuth();
        $rawId = decode_id($id);
        if ($rawId === null) {
            $this->dieWithError(404, "Invalid application ID.");
        }
        $id = $rawId;
        $encId = encode_id($id);
        $userId = Auth::userId();

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            $_SESSION['application_errors'] = ['csrf' => 'CSRF verification failed. Please try again.'];
            header("Location: " . url('/applications/' . $encId));
            $this->halt();
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("SELECT * FROM scholarship_applications WHERE id = :id LIMIT 1 FOR UPDATE");
            $stmt->execute(['id' => $id]);
            $app = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$app) {
                $this->dieWithError(404, "Application tracker record not found.");
            }

            if ((int)$app['user_id'] !== $userId) {
                $this->dieWithError(403, "Unauthorized access to application tracker.");
            }

            $stmtDel = $this->db->prepare("DELETE FROM scholarship_applications WHERE id = :id");
            $stmtDel->execute(['id' => $id]);

            $this->logAudit('application.delete', $userId, $id, ['scholarship_id' => $app['scholarship_id']]);

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }

        $_SESSION['application_success'] = 'Application removed from tracker successfully.';
        header("Location: " . url('/applications'));
        $this->halt();
    }

    /**
     * GET /admin/applications
     * Admin overview panel of all applications
     */
    public function adminIndex(): void {
        Auth::requireRole(['admin', 'employee']);
        Auth::requirePermission('applications.view');

        $search = trim($_GET['search'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $scholarshipId = (int)($_GET['scholarship_id'] ?? 0);
        $sort = trim($_GET['sort'] ?? 'newest'); // newest, oldest, deadline, status
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $whereClauses = ["1=1"];
        $params = [];

        if ($search !== '') {
            $whereClauses[] = "(u.first_name LIKE :search OR u.last_name LIKE :search OR u.email LIKE :search OR s.title LIKE :search)";
            $params['search'] = '%' . $search . '%';
        }

        if ($status !== '') {
            $whereClauses[] = "sa.status = :status";
            $params['status'] = $status;
        }

        if ($scholarshipId > 0) {
            $whereClauses[] = "sa.scholarship_id = :scholarship_id";
            $params['scholarship_id'] = $scholarshipId;
        }

        $whereSql = implode(" AND ", $whereClauses);

        // Count total
        $countStmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM scholarship_applications sa
            JOIN users u ON sa.user_id = u.id
            JOIN scholarships s ON sa.scholarship_id = s.id
            WHERE $whereSql
        ");
        $countStmt->execute($params);
        $totalItems = (int)$countStmt->fetchColumn();
        $totalPages = ceil($totalItems / $limit);

        // Sorting order logic
        $orderSql = "sa.created_at DESC";
        if ($sort === 'oldest') {
            $orderSql = "sa.created_at ASC";
        } elseif ($sort === 'deadline') {
            $orderSql = "s.application_deadline ASC";
        } elseif ($sort === 'status') {
            $orderSql = "sa.status ASC";
        }

        // Select
        $selectStmt = $this->db->prepare("
            SELECT sa.*, u.first_name, u.last_name, u.email as user_email, s.title as scholarship_title, s.application_deadline
            FROM scholarship_applications sa
            JOIN users u ON sa.user_id = u.id
            JOIN scholarships s ON sa.scholarship_id = s.id
            WHERE $whereSql
            ORDER BY $orderSql
            LIMIT $limit OFFSET $offset
        ");
        $selectStmt->execute($params);
        $applications = $selectStmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch distinct scholarships list for active filter options
        $schStmt = $this->db->query("
            SELECT DISTINCT s.id, s.title 
            FROM scholarships s
            JOIN scholarship_applications sa ON sa.scholarship_id = s.id
            ORDER BY s.title ASC
        ");
        $scholarships = $schStmt->fetchAll(PDO::FETCH_ASSOC);

        View::render('admin.applications.index', [
            'applications' => $applications,
            'scholarships' => $scholarships,
            'search' => $search,
            'status' => $status,
            'scholarship_id' => $scholarshipId,
            'sort' => $sort,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
            'csrf_token' => Security::csrfToken()
        ]);
    }

    /**
     * GET /admin/applications/{id}
     * Admin view details review page (with user personal notes masked)
     */
    public function adminShow(string $id): void {
        Auth::requireRole(['admin', 'employee']);
        Auth::requirePermission('applications.view');
        $rawId = decode_id($id);
        if ($rawId === null) {
            $this->dieWithError(404, "Invalid application ID.");
        }
        $id = $rawId;

        $stmt = $this->db->prepare("
            SELECT sa.*, u.first_name, u.last_name, u.email as user_email, s.title as scholarship_title, s.application_deadline, s.slug
            FROM scholarship_applications sa
            JOIN users u ON sa.user_id = u.id
            JOIN scholarships s ON sa.scholarship_id = s.id
            WHERE sa.id = :id 
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$app) {
            $this->dieWithError(404, "Application record not found.");
        }

        // Mask notes for privacy
        $app['personal_notes'] = '[MASKED FOR STUDENT PRIVACY]';

        $readinessService = new DocumentReadinessService();
        $readiness = $readinessService->calculateForScholarship((int)$app['user_id'], (int)$app['scholarship_id']);

        $stmtHistory = $this->db->prepare("
            SELECT h.*, u.first_name, u.last_name, r.name as role_name
            FROM scholarship_application_history h
            JOIN users u ON h.changed_by = u.id
            JOIN roles r ON u.role_id = r.id
            WHERE h.application_id = :aid
            ORDER BY h.changed_at DESC
        ");
        $stmtHistory->execute(['aid' => $id]);
        $history = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);

        // Fetch application-related notifications history
        $stmtNotif = $this->db->prepare("
            SELECT * FROM notification_logs 
            WHERE user_id = :uid AND scholarship_id = :sid 
            ORDER BY created_at DESC
        ");
        $stmtNotif->execute([
            'uid' => $app['user_id'],
            'sid' => $app['scholarship_id']
        ]);
        $notifications = $stmtNotif->fetchAll(PDO::FETCH_ASSOC);

        View::render('admin.applications.show', [
            'app' => $app,
            'readiness' => $readiness,
            'history' => $history,
            'notifications' => $notifications,
            'csrf_token' => Security::csrfToken()
        ]);
    }

    /**
     * POST /admin/applications/{id}/status
     * Admin status review change updates and notification triggers
     */
    public function adminUpdateStatus(string $id): void {
        Auth::requireRole(['admin', 'employee']);
        Auth::requirePermission('applications.review');
        $rawId = decode_id($id);
        if ($rawId === null) {
            $this->dieWithError(404, "Invalid application ID.");
        }
        $id = $rawId;
        $encId = encode_id($id);

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            $_SESSION['admin_app_error'] = 'CSRF verification failed.';
            header("Location: " . url('/admin/applications/' . $encId));
            $this->halt();
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                SELECT sa.*, u.email as user_email, s.title as scholarship_title 
                FROM scholarship_applications sa
                JOIN users u ON sa.user_id = u.id
                JOIN scholarships s ON sa.scholarship_id = s.id
                WHERE sa.id = :id 
                LIMIT 1
                FOR UPDATE
            ");
            $stmt->execute(['id' => $id]);
            $app = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$app) {
                $this->dieWithError(404, "Application record not found.");
            }

            $status = strtolower(trim($_POST['status'] ?? ''));
            $notes = trim($_POST['notes'] ?? '');
            $internalNotes = trim($_POST['internal_notes'] ?? '');

            if (!in_array($status, $this->allowedStatuses)) {
                $_SESSION['admin_app_error'] = 'Invalid status selected.';
                header("Location: " . url('/admin/applications/' . $encId));
                $this->halt();
            }

            $oldStatus = $app['status'];

            $stmtUp = $this->db->prepare("
                UPDATE scholarship_applications 
                SET status = :status, internal_notes = :internal_notes
                WHERE id = :id
            ");
            $stmtUp->execute([
                'status' => $status,
                'internal_notes' => $internalNotes !== '' ? $internalNotes : null,
                'id' => $id
            ]);

            $stmtHist = $this->db->prepare("
                INSERT INTO scholarship_application_history (application_id, old_status, new_status, changed_by, notes)
                VALUES (:aid, :old, :new, :changed_by, :notes)
            ");
            $stmtHist->execute([
                'aid' => $id,
                'old' => $oldStatus,
                'new' => $status,
                'changed_by' => Auth::userId(),
                'notes' => $notes !== '' ? $notes : 'Status updated by administrator review.'
            ]);

            $this->logAudit('application.admin_review', $app['user_id'], $id, [
                'old_status' => $oldStatus,
                'new_status' => $status,
                'review_notes' => $notes,
                'internal_notes' => $internalNotes
            ]);

            $this->db->commit();
            \App\Services\CacheService::clear();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }

        $notifService = new NotificationQueueService();
        $type = 'APPLICATION_STATUS_UPDATED';
        if ($status === 'accepted') {
            $type = 'APPLICATION_ACCEPTED';
        } elseif ($status === 'rejected') {
            $type = 'APPLICATION_REJECTED';
        }

        // Use transition-specific idempotency key
        $idempKey = "app_status_change_{$id}_{$oldStatus}_{$status}";

        $notifService->enqueue(
            (int)$app['user_id'],
            (int)$app['scholarship_id'],
            $type,
            'email',
            $app['user_email'],
            'Scholarship Application Status Update',
            [
                'scholarship_title' => $app['scholarship_title'],
                'old_status' => strtoupper($oldStatus),
                'new_status' => strtoupper($status),
                'notes' => $notes
            ],
            $idempKey
        );

        $_SESSION['admin_app_success'] = 'Application status updated successfully.';
        header("Location: " . url('/admin/applications'));
        $this->halt();
    }

    public function applicationsData(): void {
        Auth::requireRole(['admin', 'employee']);
        if (!Auth::hasPermission('scholarships.view')) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Forbidden']);
            exit();
        }
        $db = \App\Services\Database::connection();
        
        $customWhere = "";
        $customParams = [];
        
        if (!empty($_GET['status'])) {
            $customWhere = "scholarship_applications.status = :status";
            $customParams['status'] = $_GET['status'];
        }
        if (!empty($_GET['scholarship_id'])) {
            if ($customWhere !== "") $customWhere .= " AND ";
            $customWhere .= "scholarship_applications.scholarship_id = :scholarship_id";
            $customParams['scholarship_id'] = $_GET['scholarship_id'];
        }
        
        $columns = [
            'id' => 'scholarship_applications.id',
            'status' => 'scholarship_applications.status',
            'created_at' => 'scholarship_applications.created_at',
            'scholarship_title' => 'scholarships.title',
            'first_name' => 'users.first_name',
            'last_name' => 'users.last_name',
            'user_email' => 'users.email',
            'application_deadline' => 'scholarships.application_deadline'
        ];
        $joins = [
            'JOIN scholarships ON scholarship_applications.scholarship_id = scholarships.id',
            'JOIN users ON scholarship_applications.user_id = users.id'
        ];
        $searchableColumns = ['scholarships.title', 'scholarship_applications.status', 'users.first_name', 'users.last_name', 'users.email'];
        $columnMapping = [
            'status' => 'scholarship_applications.status',
            'created_at' => 'scholarship_applications.created_at',
            'scholarship_title' => 'scholarships.title'
        ];
        
        $result = \App\Helpers\DataTableHelper::process(
            $db,
            'scholarship_applications',
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
}
