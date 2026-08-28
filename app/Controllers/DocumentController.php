<?php

namespace App\Controllers;

use App\Services\Auth;
use App\Services\Database;
use App\Services\DocumentReadinessService;
use App\Services\NotificationQueueService;
use App\Helpers\Security;
use App\Helpers\View;
use Exception;
use PDO;

class DocumentController {
    private PDO $db;
    
    private array $allowedMimes = [
        'pdf'  => 'application/pdf',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'doc'  => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];

    public function __construct() {
        $this->db = Database::connection();
    }

    /**
     * Helper to write audit trail logs
     */
    private function logAudit(string $action, int $userId, ?int $resourceId, ?array $metadata = null): void {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO audit_logs (user_id, action, module, resource_type, resource_id, ip_address, user_agent, metadata) 
                VALUES (:uid, :action, 'documents', 'document', :res_id, :ip, :ua, :meta)
            ");
            $stmt->execute([
                'uid' => Auth::userId() ?: $userId,
                'action' => $action,
                'res_id' => $resourceId,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                'ua' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'meta' => $metadata ? json_encode($metadata) : null
            ]);
        } catch (Exception $e) {
            // Fail-safe silently
        }
    }

    /**
     * Ensure private uploads directory exists and is secured
     */
    private function ensureStorageDirectory(): string {
        $dir = ROOT_PATH . '/storage/private_uploads';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        $htaccess = $dir . '/.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "Deny from all\n");
        }
        
        $gitignore = $dir . '/.gitignore';
        if (!file_exists($gitignore)) {
            file_put_contents($gitignore, "*\n!.gitignore\n!.htaccess\n");
        }
        
        return $dir;
    }

    /**
     * GET /documents
     * Show applicant documents list and upload forms
     */
    public function index(): void {
        Auth::requireAuth();
        $userId = Auth::userId();

        $errors = $_SESSION['document_errors'] ?? [];
        $success = $_SESSION['document_success'] ?? null;
        unset($_SESSION['document_errors'], $_SESSION['document_success']);

        // Fetch document types
        $documents = $this->db->query("SELECT * FROM documents ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

        // Fetch user uploads
        $stmt = $this->db->prepare("
            SELECT ud.*, d.name as doc_name, d.description as doc_desc 
            FROM user_documents ud
            JOIN documents d ON ud.document_id = d.id
            WHERE ud.user_id = :uid
        ");
        $stmt->execute(['uid' => $userId]);
        $userDocs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $userDocsMap = [];
        foreach ($userDocs as $ud) {
            $userDocsMap[$ud['document_id']] = $ud;
        }

        // Calculate readiness
        $readinessService = new DocumentReadinessService();
        $readiness = $readinessService->calculateGlobal($userId);

        View::render('documents.index', [
            'documents' => $documents,
            'userDocsMap' => $userDocsMap,
            'readiness' => $readiness,
            'errors' => $errors,
            'success' => $success,
            'csrf_token' => Security::csrfToken()
        ]);
    }

    /**
     * POST /documents/upload
     * Upload or replace a document file
     */
    public function upload(): void {
        Auth::requireAuth();
        $userId = Auth::userId();
        
        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            $_SESSION['document_errors'] = ['csrf' => 'CSRF verification failed. Please try again.'];
            header("Location: " . url('/documents'));
            $this->halt();
        }

        $docId = (int)($_POST['document_id'] ?? 0);
        $stmt = $this->db->prepare("SELECT name FROM documents WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $docId]);
        $docTypeName = $stmt->fetchColumn();

        if (!$docTypeName) {
            $_SESSION['document_errors'] = ['document_id' => 'Invalid document type selected.'];
            header("Location: " . url('/documents'));
            $this->halt();
        }

        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $errCode = $_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE;
            $msg = 'File upload failed.';
            if ($errCode === UPLOAD_ERR_INI_SIZE || $errCode === UPLOAD_ERR_FORM_SIZE) {
                $msg = 'File size exceeds maximum limit of 5MB.';
            } elseif ($errCode === UPLOAD_ERR_NO_FILE) {
                $msg = 'Please select a file to upload.';
            }
            $_SESSION['document_errors'] = ['file' => $msg];
            header("Location: " . url('/documents'));
            $this->halt();
        }

        $file = $_FILES['file'];
        $originalName = $file['name'];
        $tempPath = $file['tmp_name'];
        $fileSize = $file['size'];

        // 1. Validation: Max size 5MB
        if ($fileSize > 5 * 1024 * 1024) {
            $_SESSION['document_errors'] = ['file' => 'File size exceeds maximum limit of 5MB.'];
            header("Location: " . url('/documents'));
            $this->halt();
        }

        // 2. Validation: Empty file
        if ($fileSize === 0) {
            $_SESSION['document_errors'] = ['file' => 'Uploaded file is empty or corrupted.'];
            header("Location: " . url('/documents'));
            $this->halt();
        }

        // 3. Validation: Null-byte injection check
        if (strpos($originalName, "\0") !== false) {
            $_SESSION['document_errors'] = ['file' => 'Invalid filename detected.'];
            header("Location: " . url('/documents'));
            $this->halt();
        }

        // 4. Validation: File extension allowlist
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!array_key_exists($ext, $this->allowedMimes)) {
            $_SESSION['document_errors'] = ['file' => 'Invalid file format. Allowed formats: PDF, JPG, JPEG, PNG, DOC, DOCX.'];
            header("Location: " . url('/documents'));
            $this->halt();
        }

        // 5. Validation: Double extension block
        if (preg_match('/\.(php|phtml|php3|php4|php5|php7|phps|exe|sh|bat|cmd|pl|cgi)\./i', $originalName)) {
            $_SESSION['document_errors'] = ['file' => 'Security policy restriction: unsafe double extension block.'];
            header("Location: " . url('/documents'));
            $this->halt();
        }

        // 6. Validation: Binary MIME-type spoof check
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $realMime = finfo_file($finfo, $tempPath);
        finfo_close($finfo);

        $expectedMime = $this->allowedMimes[$ext];
        // Handle variations (e.g. DOCX can map to zip or application/vnd.openxmlformats-officedocument)
        if ($ext === 'docx') {
            $allowedDocx = [
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/zip',
                'application/octet-stream'
            ];
            $mimeValid = in_array($realMime, $allowedDocx);
        } else {
            $mimeValid = ($realMime === $expectedMime);
        }

        if (!$mimeValid) {
            $_SESSION['document_errors'] = ['file' => 'File verification failed: MIME-type spoofing detected.'];
            header("Location: " . url('/documents'));
            $this->halt();
        }

        // 7. Secure storage execution
        $storageDir = $this->ensureStorageDirectory();
        $storedName = bin2hex(random_bytes(16)) . '.' . $ext;
        $targetPath = $storageDir . '/' . $storedName;

        $saveSuccess = false;
        if (defined('TESTING_MODE') && TESTING_MODE) {
            $saveSuccess = @copy($tempPath, $targetPath);
        } else {
            $saveSuccess = @move_uploaded_file($tempPath, $targetPath);
        }

        if (!$saveSuccess) {
            $_SESSION['document_errors'] = ['file' => 'Failed to save file to secure storage.'];
            header("Location: " . url('/documents'));
            $this->halt();
        }

        $checksum = hash_file('sha256', $targetPath);
        $cleanOriginalName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $originalName);

        // 8. Check if replacement or insert
        $stmtCheck = $this->db->prepare("SELECT * FROM user_documents WHERE user_id = :uid AND document_id = :did LIMIT 1");
        $stmtCheck->execute(['uid' => $userId, 'did' => $docId]);
        $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            // Delete old physical file
            if (file_exists($existing['storage_path'])) {
                @unlink($existing['storage_path']);
            }

            // Update record
            $stmtUp = $this->db->prepare("
                UPDATE user_documents 
                SET original_filename = :orig, 
                    stored_filename = :stored, 
                    storage_path = :path, 
                    mime_type = :mime, 
                    file_size = :size, 
                    checksum = :hash, 
                    status = 'uploaded', 
                    rejection_reason = NULL,
                    uploaded_at = NOW()
                WHERE id = :id
            ");
            $stmtUp->execute([
                'orig' => $cleanOriginalName,
                'stored' => $storedName,
                'path' => $targetPath,
                'mime' => $realMime,
                'size' => $fileSize,
                'hash' => $checksum,
                'id' => $existing['id']
            ]);

            $this->logAudit('document.replace', $userId, $existing['id'], ['document_type' => $docTypeName]);
            $_SESSION['document_success'] = "Document replaced successfully.";
        } else {
            // Insert record
            $stmtIns = $this->db->prepare("
                INSERT INTO user_documents (user_id, document_id, original_filename, stored_filename, storage_path, mime_type, file_size, checksum, status)
                VALUES (:uid, :did, :orig, :stored, :path, :mime, :size, :hash, 'uploaded')
            ");
            $stmtIns->execute([
                'uid' => $userId,
                'did' => $docId,
                'orig' => $cleanOriginalName,
                'stored' => $storedName,
                'path' => $targetPath,
                'mime' => $realMime,
                'size' => $fileSize,
                'hash' => $checksum
            ]);
            $newId = (int)$this->db->lastInsertId();

            $this->logAudit('document.upload', $userId, $newId, ['document_type' => $docTypeName]);
            $_SESSION['document_success'] = "Document uploaded successfully.";
        }

        \App\Services\CacheService::clear();
        header("Location: " . url('/documents'));
        $this->halt();
    }

    /**
     * GET /documents/{id}/download
     * Securely download document owned by applicant
     */
    public function download(int $id): void {
        Auth::requireAuth();
        $userId = Auth::userId();

        $stmt = $this->db->prepare("SELECT * FROM user_documents WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $doc = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$doc) {
            $this->dieWithError(404, "Document not found.");
        }

        // Ownership validation (IDOR protection)
        if ((int)$doc['user_id'] !== $userId) {
            $this->dieWithError(403, "Unauthorized access: you do not own this document.");
        }

        if (!file_exists($doc['storage_path'])) {
            $this->dieWithError(404, "File not found on disk.");
        }

        $this->logAudit('document.download', $userId, $id);

        header('Content-Type: ' . $doc['mime_type']);
        header('Content-Disposition: inline; filename="' . basename($doc['original_filename']) . '"');
        header('Content-Length: ' . $doc['file_size']);
        readfile($doc['storage_path']);
        $this->halt();
    }

    /**
     * POST /documents/{id}/delete
     * Delete document owned by applicant
     */
    public function delete(int $id): void {
        Auth::requireAuth();
        $userId = Auth::userId();

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            $_SESSION['document_errors'] = ['csrf' => 'CSRF verification failed. Please try again.'];
            header("Location: " . url('/documents'));
            $this->halt();
        }

        $stmt = $this->db->prepare("SELECT * FROM user_documents WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $doc = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$doc) {
            $_SESSION['document_errors'] = ['delete' => 'Document not found.'];
            header("Location: " . url('/documents'));
            $this->halt();
        }

        // Ownership validation (IDOR protection)
        if ((int)$doc['user_id'] !== $userId) {
            $_SESSION['document_errors'] = ['delete' => 'Unauthorized deletion request.'];
            header("Location: " . url('/documents'));
            $this->halt();
        }

        // Business rule constraint: Approved records cannot be deleted
        if ($doc['status'] === 'approved') {
            $_SESSION['document_errors'] = ['delete' => 'Approved documents cannot be deleted to preserve audit trails.'];
            header("Location: " . url('/documents'));
            $this->halt();
        }

        // Remove from disk
        if (file_exists($doc['storage_path'])) {
            @unlink($doc['storage_path']);
        }

        // Remove from database
        $stmtDel = $this->db->prepare("DELETE FROM user_documents WHERE id = :id");
        $stmtDel->execute(['id' => $id]);

        $this->logAudit('document.delete', $userId, $id, ['original_filename' => $doc['original_filename']]);
        $_SESSION['document_success'] = "Document deleted successfully.";

        \App\Services\CacheService::clear();
        header("Location: " . url('/documents'));
        $this->halt();
    }

    /**
     * GET /admin/documents
     * Administrative uploaded documents management listing
     */
    public function adminIndex(): void {
        Auth::requireRole(['admin', 'employee']);
        Auth::requirePermission('documents.view');

        $status = $_GET['status'] ?? null;
        $docId = $_GET['document_id'] ?? null;
        $search = $_GET['search'] ?? null;

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $where = [];
        $params = [];

        if ($status) {
            $where[] = "ud.status = :status";
            $params['status'] = $status;
        }
        if ($docId) {
            $where[] = "ud.document_id = :docId";
            $params['docId'] = $docId;
        }
        if ($search) {
            $where[] = "(u.first_name LIKE :search OR u.last_name LIKE :search OR u.email LIKE :search OR ud.original_filename LIKE :search)";
            $params['search'] = '%' . $search . '%';
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Total count
        $countQuery = "
            SELECT COUNT(*) 
            FROM user_documents ud
            JOIN users u ON ud.user_id = u.id
            $whereClause
        ";
        $stmtCount = $this->db->prepare($countQuery);
        $stmtCount->execute($params);
        $totalItems = $stmtCount->fetchColumn();
        $totalPages = ceil($totalItems / $limit);

        // Fetch page
        $query = "
            SELECT ud.*, u.first_name, u.last_name, u.email as user_email, d.name as doc_type_name
            FROM user_documents ud
            JOIN users u ON ud.user_id = u.id
            JOIN documents d ON ud.document_id = d.id
            $whereClause
            ORDER BY ud.uploaded_at DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->db->prepare($query);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch document types for filters
        $docTypes = $this->db->query("SELECT id, name FROM documents ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

        // Fetch admin statistics
        $totalUploaded = $this->db->query("SELECT COUNT(*) FROM user_documents")->fetchColumn();
        $pendingReview = $this->db->query("SELECT COUNT(*) FROM user_documents WHERE status = 'uploaded'")->fetchColumn();
        $approvedCount = $this->db->query("SELECT COUNT(*) FROM user_documents WHERE status = 'approved'")->fetchColumn();
        $rejectedCount = $this->db->query("SELECT COUNT(*) FROM user_documents WHERE status = 'rejected'")->fetchColumn();
        
        $missingCount = $this->db->query("
            SELECT COUNT(*) 
            FROM scholarship_applications sa
            JOIN scholarship_documents sd ON sa.scholarship_id = sd.scholarship_id
            LEFT JOIN user_documents ud ON sa.user_id = ud.user_id AND sd.document_id = ud.document_id
            WHERE ud.id IS NULL AND sd.is_required = 1
        ")->fetchColumn();

        $success = $_SESSION['admin_doc_success'] ?? null;
        $error = $_SESSION['admin_doc_error'] ?? null;
        unset($_SESSION['admin_doc_success'], $_SESSION['admin_doc_error']);

        View::render('admin.documents.index', [
            'records' => $records,
            'docTypes' => $docTypes,
            'status' => $status,
            'docId' => $docId,
            'search' => $search,
            'page' => $page,
            'totalPages' => $totalPages,
            'stats' => [
                'total_uploaded' => $totalUploaded,
                'pending_review' => $pendingReview,
                'approved' => $approvedCount,
                'rejected' => $rejectedCount,
                'missing' => $missingCount
            ],
            'success' => $success,
            'error' => $error,
            'csrf_token' => Security::csrfToken()
        ]);
    }

    /**
     * GET /admin/documents/{id}
     * Administrative uploaded document detail view and review options
     */
    public function adminShow(int $id): void {
        Auth::requireRole(['admin', 'employee']);
        Auth::requirePermission('documents.view');

        $stmt = $this->db->prepare("
            SELECT ud.*, u.first_name, u.last_name, u.email as user_email, d.name as doc_type_name, d.description as doc_desc
            FROM user_documents ud
            JOIN users u ON ud.user_id = u.id
            JOIN documents d ON ud.document_id = d.id
            WHERE ud.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$record) {
            $this->dieWithError(404, "Document record not found.");
        }

        View::render('admin.documents.show', [
            'record' => $record,
            'csrf_token' => Security::csrfToken()
        ]);
    }

    /**
     * POST /admin/documents/{id}/approve
     * Approve document
     */
    public function approve(int $id): void {
        Auth::requireRole(['admin', 'employee']);
        Auth::requirePermission('documents.review');

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            $_SESSION['admin_doc_error'] = 'CSRF verification failed.';
            header("Location: " . url('/admin/documents/' . $id));
            $this->halt();
        }

        $stmt = $this->db->prepare("
            SELECT ud.*, d.name as doc_name, u.email as user_email 
            FROM user_documents ud
            JOIN documents d ON ud.document_id = d.id
            JOIN users u ON ud.user_id = u.id
            WHERE ud.id = :id 
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $doc = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$doc) {
            $_SESSION['admin_doc_error'] = 'Document not found.';
            header("Location: " . url('/admin/documents'));
            $this->halt();
        }

        $stmtUp = $this->db->prepare("
            UPDATE user_documents 
            SET status = 'approved', 
                rejection_reason = NULL,
                reviewed_at = NOW(), 
                reviewed_by = :reviewer 
            WHERE id = :id
        ");
        $stmtUp->execute([
            'reviewer' => Auth::userId(),
            'id' => $id
        ]);

        $this->logAudit('document.approve', $doc['user_id'], $id, ['document_type' => $doc['doc_name']]);
        \App\Services\CacheService::clear();

        // Enqueue Notification
        $notifService = new NotificationQueueService();
        $notifService->enqueue(
            (int)$doc['user_id'],
            null,
            'DOCUMENT_APPROVED',
            'email',
            $doc['user_email'],
            'Document Approved',
            ['document_name' => $doc['doc_name']]
        );

        $_SESSION['admin_doc_success'] = "Document approved successfully.";
        header("Location: " . url('/admin/documents'));
        $this->halt();
    }

    /**
     * POST /admin/documents/{id}/reject
     * Reject document
     */
    public function reject(int $id): void {
        Auth::requireRole(['admin', 'employee']);
        Auth::requirePermission('documents.review');

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            $_SESSION['admin_doc_error'] = 'CSRF verification failed.';
            header("Location: " . url('/admin/documents/' . $id));
            $this->halt();
        }

        $reason = trim($_POST['rejection_reason'] ?? '');
        if (empty($reason)) {
            $_SESSION['admin_doc_error'] = 'Rejection reason is required.';
            header("Location: " . url('/admin/documents/' . $id));
            $this->halt();
        }

        $stmt = $this->db->prepare("
            SELECT ud.*, d.name as doc_name, u.email as user_email 
            FROM user_documents ud
            JOIN documents d ON ud.document_id = d.id
            JOIN users u ON ud.user_id = u.id
            WHERE ud.id = :id 
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $doc = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$doc) {
            $_SESSION['admin_doc_error'] = 'Document not found.';
            header("Location: " . url('/admin/documents'));
            $this->halt();
        }

        $stmtUp = $this->db->prepare("
            UPDATE user_documents 
            SET status = 'rejected', 
                rejection_reason = :reason,
                reviewed_at = NOW(), 
                reviewed_by = :reviewer 
            WHERE id = :id
        ");
        $stmtUp->execute([
            'reason' => $reason,
            'reviewer' => Auth::userId(),
            'id' => $id
        ]);

        $this->logAudit('document.reject', $doc['user_id'], $id, ['document_type' => $doc['doc_name'], 'reason' => $reason]);
        \App\Services\CacheService::clear();

        // Enqueue Notification
        $notifService = new NotificationQueueService();
        $notifService->enqueue(
            (int)$doc['user_id'],
            null,
            'DOCUMENT_REJECTED',
            'email',
            $doc['user_email'],
            'Document Rejected',
            [
                'document_name' => $doc['doc_name'],
                'rejection_reason' => $reason
            ]
        );

        $_SESSION['admin_doc_success'] = "Document rejected successfully.";
        header("Location: " . url('/admin/documents'));
        $this->halt();
    }

    /**
     * GET /admin/documents/{id}/download
     * Securely download document by administrator
     */
    public function adminDownload(int $id): void {
        Auth::requireRole(['admin', 'employee']);
        Auth::requirePermission('documents.download');

        $stmt = $this->db->prepare("SELECT * FROM user_documents WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $doc = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$doc) {
            $this->dieWithError(404, "Document not found.");
        }

        if (!file_exists($doc['storage_path'])) {
            $this->dieWithError(404, "File not found on disk.");
        }

        $this->logAudit('document.download', $doc['user_id'], $id, ['admin_download' => true]);

        header('Content-Type: ' . $doc['mime_type']);
        header('Content-Disposition: inline; filename="' . basename($doc['original_filename']) . '"');
        header('Content-Length: ' . $doc['file_size']);
        readfile($doc['storage_path']);
        $this->halt();
    }

    /**
     * Stop execution or throw testing exception to avoid CLI test halts
     */
    private function halt(): void {
        if (defined('TESTING_MODE') && TESTING_MODE) {
            throw new \RuntimeException("Halt execution");
        }
        exit();
    }

    /**
     * Terminate execution with code and error message or throw exception in test mode
     */
    private function dieWithError(int $code, string $message): void {
        http_response_code($code);
        if (defined('TESTING_MODE') && TESTING_MODE) {
            throw new \RuntimeException($message);
        }
        die($message);
    }
}
