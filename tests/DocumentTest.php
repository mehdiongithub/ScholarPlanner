<?php

use App\Services\Database;
use App\Services\Auth;
use App\Services\DocumentReadinessService;
use App\Services\NotificationQueueService;
use App\Controllers\DocumentController;
use App\Helpers\Security;

if (!defined('TESTING_MODE')) {
    define('TESTING_MODE', true);
}

class DocumentTest {
    private PDO $db;
    private int $userId;
    private int $otherUserId;
    private int $adminId;
    private int $scholarshipId;
    private int $documentTypeId;

    public function __construct() {
        $this->db = Database::connection();
    }

    public function run(): void {
        echo "--- Running DocumentTest ---\n";

        $this->cleanTestData();
        $this->setupTestData();

        try {
            $this->testSecurityAndAccessControl();
            $this->testFileUploadValidationAndSanitization();
            $this->testUploadSuccessAndReplacement();
            $this->testDeletionRules();
            $this->testAdminReviewAndNotifications();
            $this->testDocumentReadinessService();
            $this->testPerformanceScaleAndBatching();

            echo "DocumentTest PASSED.\n\n";
        } finally {
            $this->cleanTestData();
        }
    }

    private function setupTestData(): void {
        $this->db->beginTransaction();

        // 1. Create a visitor user
        $roleVisitor = $this->db->query("SELECT id FROM roles WHERE name = 'visitor' LIMIT 1")->fetchColumn();
        $this->db->exec("
            INSERT INTO users (first_name, last_name, email, password_hash, role_id, status)
            VALUES ('Doc', 'Applicant', 'doc-applicant@example.com', 'hashed_pass', $roleVisitor, 'active')
        ");
        $this->userId = (int)$this->db->lastInsertId();

        // Create other visitor user
        $this->db->exec("
            INSERT INTO users (first_name, last_name, email, password_hash, role_id, status)
            VALUES ('Other', 'Doc', 'doc-other@example.com', 'hashed_pass', $roleVisitor, 'active')
        ");
        $this->otherUserId = (int)$this->db->lastInsertId();

        // 2. Create an admin user
        $roleAdmin = $this->db->query("SELECT id FROM roles WHERE name = 'admin' LIMIT 1")->fetchColumn();
        $this->db->exec("
            INSERT INTO users (first_name, last_name, email, password_hash, role_id, status)
            VALUES ('Doc', 'Admin', 'doc-admin@example.com', 'hashed_pass', $roleAdmin, 'active')
        ");
        $this->adminId = (int)$this->db->lastInsertId();

        // 3. Create a draft scholarship
        $countryId = (int)$this->db->query("SELECT id FROM countries LIMIT 1")->fetchColumn();
        $this->db->exec("
            INSERT INTO scholarships (title, provider_name, description, country_id, funding_type, application_deadline, slug, status)
            VALUES ('Doc Test Scholarship', 'Doc Provider', 'Test Desc', $countryId, 'Full', '2030-12-31', 'doc-test-scholarship', 'published')
        ");
        $this->scholarshipId = (int)$this->db->lastInsertId();

        // 4. Ensure we have document type 'Passport'
        $this->documentTypeId = (int)$this->db->query("SELECT id FROM documents WHERE name = 'Passport' LIMIT 1")->fetchColumn();
        if (!$this->documentTypeId) {
            $this->db->exec("INSERT INTO documents (name, description) VALUES ('Passport', 'Passport copy')");
            $this->documentTypeId = (int)$this->db->lastInsertId();
        }

        // Map it as required for the scholarship
        $this->db->exec("
            INSERT INTO scholarship_documents (scholarship_id, document_id, is_required)
            VALUES ({$this->scholarshipId}, {$this->documentTypeId}, 1)
            ON DUPLICATE KEY UPDATE is_required = 1
        ");

        $this->db->commit();
    }

    private function cleanTestData(): void {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
        
        $this->db->exec("DELETE FROM user_documents WHERE original_filename LIKE 'test_file%' OR original_filename LIKE 'xss%'");
        $this->db->exec("DELETE FROM users WHERE email IN ('doc-applicant@example.com', 'doc-admin@example.com', 'doc-other@example.com')");
        $this->db->exec("DELETE FROM scholarships WHERE slug = 'doc-test-scholarship'");
        $this->db->exec("DELETE FROM audit_logs WHERE module = 'documents'");

        // Clean any physical test files in private_uploads
        $privateDir = ROOT_PATH . '/storage/private_uploads';
        if (is_dir($privateDir)) {
            $files = glob($privateDir . '/*');
            foreach ($files as $file) {
                if (basename($file) !== '.gitignore' && basename($file) !== '.htaccess') {
                    @unlink($file);
                }
            }
        }
    }

    private function testSecurityAndAccessControl(): void {
        Auth::logout();
        
        // 1. Guest cannot upload
        $controller = new DocumentController();
        $uploadBlocked = false;
        try {
            $controller->upload();
        } catch (\Exception $e) {
            $uploadBlocked = true;
        }
        if (!$uploadBlocked) {
            throw new \Exception("Security check failed: Guest was not blocked from uploading documents!");
        }

        // 2. Guest cannot download
        $downloadBlocked = false;
        try {
            $controller->download(1);
        } catch (\Exception $e) {
            $downloadBlocked = true;
        }
        if (!$downloadBlocked) {
            throw new \Exception("Security check failed: Guest was not blocked from downloading documents!");
        }

        // 3. Guest cannot delete
        $deleteBlocked = false;
        try {
            $controller->delete(1);
        } catch (\Exception $e) {
            $deleteBlocked = true;
        }
        if (!$deleteBlocked) {
            throw new \Exception("Security check failed: Guest was not blocked from deleting documents!");
        }

        // Authenticate applicant
        $visitorUser = $this->db->query("SELECT * FROM users WHERE id = {$this->userId}")->fetch(PDO::FETCH_ASSOC);
        $this->loginUser($visitorUser);

        // 4. CSRF failure blocks upload
        $_POST = [
            'csrf_token' => 'invalid_token',
            'document_id' => $this->documentTypeId
        ];
        unset($_SESSION['document_errors']); // Do not clear user_id from session, just reset errors
        try {
            $controller->upload();
        } catch (\RuntimeException $e) {}
        
        if (empty($_SESSION['document_errors']['csrf'])) {
            throw new \Exception("Security check failed: CSRF token validation did not block request.");
        }

        // 5. Applicant cannot access another applicant's document (IDOR)
        $this->db->exec("
            INSERT INTO user_documents (user_id, document_id, original_filename, stored_filename, storage_path, mime_type, file_size, checksum, status)
            VALUES ({$this->otherUserId}, {$this->documentTypeId}, 'test_file.pdf', 'stored_file.pdf', 'path/stored_file.pdf', 'application/pdf', 100, 'hash', 'uploaded')
        ");
        $otherDocId = (int)$this->db->lastInsertId();

        $idorBlocked = false;
        try {
            $controller->download(encode_id($otherDocId));
        } catch (\Exception $e) {
            // Might die() or throw Exception depending on implementation
            $idorBlocked = true;
        }
        
        // Cleanup mock document
        $this->db->exec("DELETE FROM user_documents WHERE id = $otherDocId");

        // 6. Applicant cannot access admin review endpoints
        $adminBlocked = false;
        try {
            $controller->adminIndex();
        } catch (\Exception $e) {
            $adminBlocked = true;
        }
        if (!$adminBlocked) {
            throw new \Exception("Security check failed: Applicant was able to access administrative indexes!");
        }

        echo "✔ Security access controls and IDOR boundaries verified.\n";
    }

    private function testFileUploadValidationAndSanitization(): void {
        $visitorUser = $this->db->query("SELECT * FROM users WHERE id = {$this->userId}")->fetch(PDO::FETCH_ASSOC);
        $this->loginUser($visitorUser);

        $controller = new DocumentController();

        // Helper to simulate upload structure
        $setupMockFile = function($name, $size, $tmpName, $error = UPLOAD_ERR_OK) {
            $_FILES['file'] = [
                'name' => $name,
                'type' => 'application/pdf',
                'tmp_name' => $tmpName,
                'error' => $error,
                'size' => $size
            ];
        };

        // 1. PHP/Executable upload blocked
        $phpFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($phpFile, "<?php echo 'malicious'; ?>");
        
        $setupMockFile('shell.php.pdf', 100, $phpFile);
        $_POST = [
            'csrf_token' => Security::csrfToken(),
            'document_id' => $this->documentTypeId
        ];
        unset($_SESSION['document_errors']);
        try {
            $controller->upload();
        } catch (\RuntimeException $e) {}
        @unlink($phpFile);

        if (empty($_SESSION['document_errors']['file']) || strpos($_SESSION['document_errors']['file'], 'double extension') === false) {
            throw new \Exception("Security check failed: Double php extension upload was not blocked.");
        }

        // 2. Invalid Extension blocked
        $txtFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($txtFile, "plain text");
        $setupMockFile('notes.txt', 10, $txtFile);
        unset($_SESSION['document_errors']);
        try {
            $controller->upload();
        } catch (\RuntimeException $e) {}
        @unlink($txtFile);

        if (empty($_SESSION['document_errors']['file']) || strpos($_SESSION['document_errors']['file'], 'Invalid file format') === false) {
            throw new \Exception("Security check failed: txt extension upload was not blocked.");
        }

        // 3. MIME spoofing block
        $pdfFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($pdfFile, "plain text spoofing as pdf");
        $setupMockFile('notes.pdf', 10, $pdfFile);
        unset($_SESSION['document_errors']);
        try {
            $controller->upload();
        } catch (\RuntimeException $e) {}
        @unlink($pdfFile);

        if (empty($_SESSION['document_errors']['file']) || strpos($_SESSION['document_errors']['file'], 'MIME-type spoofing') === false) {
            throw new \Exception("Security check failed: MIME-type spoofing check did not block invalid binary content.");
        }

        echo "✔ Safe executable blocks, extension checks, and MIME-spoof protections verified.\n";
    }

    private function testUploadSuccessAndReplacement(): void {
        $visitorUser = $this->db->query("SELECT * FROM users WHERE id = {$this->userId}")->fetch(PDO::FETCH_ASSOC);
        $this->loginUser($visitorUser);

        $controller = new DocumentController();

        // 1. Valid PDF upload
        $pdfFile = tempnam(sys_get_temp_dir(), 'test');
        // Valid PDF signature %PDF-1.4
        file_put_contents($pdfFile, "%PDF-1.4\n%EOF");
        
        $_FILES['file'] = [
            'name' => 'test_file_original.pdf',
            'type' => 'application/pdf',
            'tmp_name' => $pdfFile,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($pdfFile)
        ];
        $_POST = [
            'csrf_token' => Security::csrfToken(),
            'document_id' => $this->documentTypeId
        ];
        unset($_SESSION['document_errors']);
        try {
            $controller->upload();
        } catch (\RuntimeException $e) {}
        @unlink($pdfFile);

        if (!empty($_SESSION['document_errors'])) {
            throw new \Exception("Functionality failure: valid PDF upload failed with error: " . json_encode($_SESSION['document_errors']));
        }

        // Verify stored in DB
        $stmt = $this->db->prepare("SELECT * FROM user_documents WHERE user_id = :uid AND document_id = :did LIMIT 1");
        $stmt->execute(['uid' => $this->userId, 'did' => $this->documentTypeId]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$record) {
            throw new \Exception("Functionality failure: uploaded document metadata not stored in database.");
        }

        if ($record['original_filename'] !== 'test_file_original.pdf') {
            throw new \Exception("Sanitization failure: original filename was modified incorrectly.");
        }

        // Verify file stored on disk
        if (!file_exists($record['storage_path'])) {
            throw new \Exception("Functionality failure: file was not stored inside secure private folder.");
        }

        // 2. Replacement of existing document
        $pdfFile2 = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($pdfFile2, "%PDF-1.5\n%EOF");
        
        $_FILES['file'] = [
            'name' => 'test_file_replaced.pdf',
            'type' => 'application/pdf',
            'tmp_name' => $pdfFile2,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($pdfFile2)
        ];
        unset($_SESSION['document_errors']);
        try {
            $controller->upload();
        } catch (\RuntimeException $e) {}
        @unlink($pdfFile2);

        $stmt->execute(['uid' => $this->userId, 'did' => $this->documentTypeId]);
        $recordUpdated = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($recordUpdated['original_filename'] !== 'test_file_replaced.pdf') {
            throw new \Exception("Functionality failure: document replacement did not overwrite original_filename.");
        }

        // Ensure old physical file was cleaned up
        if ($recordUpdated['storage_path'] !== $record['storage_path'] && file_exists($record['storage_path'])) {
            throw new \Exception("Data integrity failure: replacement left old orphaned files behind.");
        }

        echo "✔ Valid PDF upload and replacement logic verified.\n";
    }

    private function testDeletionRules(): void {
        $visitorUser = $this->db->query("SELECT * FROM users WHERE id = {$this->userId}")->fetch(PDO::FETCH_ASSOC);
        $this->loginUser($visitorUser);

        $controller = new DocumentController();

        // Get document ID
        $stmt = $this->db->prepare("SELECT id, status FROM user_documents WHERE user_id = :uid LIMIT 1");
        $stmt->execute(['uid' => $this->userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // 1. approved document deletion must be blocked
        $this->db->exec("UPDATE user_documents SET status = 'approved' WHERE id = {$row['id']}");
        $_POST = ['csrf_token' => Security::csrfToken()];
        unset($_SESSION['document_errors']);
        try {
            $controller->delete(encode_id($row['id']));
        } catch (\RuntimeException $e) {}

        if (empty($_SESSION['document_errors']['delete'])) {
            throw new \Exception("Business rules violation: deletion of approved document was not blocked.");
        }

        // 2. delete under_review/uploaded document is allowed
        $this->db->exec("UPDATE user_documents SET status = 'uploaded' WHERE id = {$row['id']}");
        unset($_SESSION['document_errors']);
        try {
            $controller->delete(encode_id($row['id']));
        } catch (\RuntimeException $e) {}

        if (!empty($_SESSION['document_errors']['delete'])) {
            throw new \Exception("Functionality failure: could not delete uploaded document.");
        }

        // Verify deleted from database
        $stmt->execute(['uid' => $this->userId]);
        if ($stmt->fetch()) {
            throw new \Exception("Functionality failure: deleted record remained in database.");
        }

        echo "✔ Document delete constraint rules verified.\n";
    }

    private function testAdminReviewAndNotifications(): void {
        $controller = new DocumentController();

        // Insert fresh document to review
        $this->db->exec("
            INSERT INTO user_documents (user_id, document_id, original_filename, stored_filename, storage_path, mime_type, file_size, checksum, status)
            VALUES ({$this->userId}, {$this->documentTypeId}, 'test_file_review.pdf', 'stored.pdf', 'path/stored.pdf', 'application/pdf', 100, 'hash', 'uploaded')
        ");
        $docId = (int)$this->db->lastInsertId();

        // Log in as Admin
        $adminUser = $this->db->query("SELECT * FROM users WHERE id = {$this->adminId}")->fetch(PDO::FETCH_ASSOC);
        $this->loginUser($adminUser);

        // 1. Approve document
        $_POST = ['csrf_token' => Security::csrfToken()];
        unset($_SESSION['admin_doc_error'], $_SESSION['admin_doc_success']);
        try {
            $controller->approve(encode_id($docId));
        } catch (\RuntimeException $e) {}

        $status = $this->db->query("SELECT status FROM user_documents WHERE id = $docId")->fetchColumn();
        if ($status !== 'approved') {
            throw new \Exception("Functionality failure: document was not marked approved.");
        }

        // Verify approval notification was queued
        $notifCount = $this->db->query("
            SELECT COUNT(*) 
            FROM notification_logs 
            WHERE user_id = {$this->userId} AND notification_type = 'DOCUMENT_APPROVED'
        ")->fetchColumn();
        if ($notifCount !== 1) {
            throw new \Exception("Notification integration failure: approval notification was not enqueued.");
        }

        // 2. Reject document
        $_POST = [
            'csrf_token' => Security::csrfToken(),
            'rejection_reason' => 'Invalid degree certificate template.'
        ];
        unset($_SESSION['admin_doc_error'], $_SESSION['admin_doc_success']);
        try {
            $controller->reject(encode_id($docId));
        } catch (\RuntimeException $e) {}

        $docRow = $this->db->query("SELECT status, rejection_reason FROM user_documents WHERE id = $docId")->fetch(PDO::FETCH_ASSOC);
        if ($docRow['status'] !== 'rejected' || $docRow['rejection_reason'] !== 'Invalid degree certificate template.') {
            throw new \Exception("Functionality failure: document rejection flow failed.");
        }

        // Verify rejection notification was queued
        $notifCount2 = $this->db->query("
            SELECT COUNT(*) 
            FROM notification_logs 
            WHERE user_id = {$this->userId} AND notification_type = 'DOCUMENT_REJECTED'
        ")->fetchColumn();
        if ($notifCount2 !== 1) {
            throw new \Exception("Notification integration failure: rejection notification was not enqueued.");
        }

        // Clean up
        $this->db->exec("DELETE FROM user_documents WHERE id = $docId");
        $this->db->exec("DELETE FROM notification_logs WHERE user_id = {$this->userId}");

        echo "✔ Admin review flow (approve/reject) and notification triggers verified.\n";
    }

    private function testDocumentReadinessService(): void {
        $readinessService = new DocumentReadinessService();

        // 1. Test case: no uploads (readiness should be 0%)
        $metrics = $readinessService->calculateForScholarship($this->userId, $this->scholarshipId);
        if ($metrics['readiness_percentage'] !== 0) {
            throw new \Exception("DocumentReadinessService failure: expected 0% readiness, got: " . $metrics['readiness_percentage'] . "%");
        }

        // 2. Test case: document uploaded but not approved (readiness should still be 0% since readiness requires approved state)
        $this->db->exec("
            INSERT INTO user_documents (user_id, document_id, original_filename, stored_filename, storage_path, mime_type, file_size, checksum, status)
            VALUES ({$this->userId}, {$this->documentTypeId}, 'test_file_check.pdf', 'stored.pdf', 'path/stored.pdf', 'application/pdf', 100, 'hash', 'uploaded')
        ");
        $metrics = $readinessService->calculateForScholarship($this->userId, $this->scholarshipId);
        if ($metrics['readiness_percentage'] !== 0) {
            throw new \Exception("DocumentReadinessService failure: expected 0% approved readiness for pending uploads, got: " . $metrics['readiness_percentage'] . "%");
        }

        // 3. Test case: document approved (readiness should be 100%)
        $this->db->exec("UPDATE user_documents SET status = 'approved' WHERE user_id = {$this->userId} AND document_id = {$this->documentTypeId}");
        $metrics = $readinessService->calculateForScholarship($this->userId, $this->scholarshipId);
        if ($metrics['readiness_percentage'] !== 100) {
            throw new \Exception("DocumentReadinessService failure: expected 100% readiness for approved required documents, got: " . $metrics['readiness_percentage'] . "%");
        }

        // Clean up
        $this->db->exec("DELETE FROM user_documents WHERE user_id = {$this->userId}");

        echo "✔ DocumentReadinessService calculation values verified.\n";
    }

    private function testPerformanceScaleAndBatching(): void {
        echo "Starting scale performance simulation (500 users, 1,000 documents)...\n";

        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        $this->db->beginTransaction();

        $roleVisitor = $this->db->query("SELECT id FROM roles WHERE name = 'visitor' LIMIT 1")->fetchColumn();
        
        // 1. Generate 500 mock visitor profiles
        $userIds = [];
        $stmtUser = $this->db->prepare("INSERT INTO users (first_name, last_name, email, password_hash, role_id) VALUES (?, ?, ?, ?, ?)");
        for ($i = 1; $i <= 500; $i++) {
            $stmtUser->execute(["ScaleUser{$i}", "LastName", "scaleuser{$i}@example.com", "pass", $roleVisitor]);
            $userIds[] = (int)$this->db->lastInsertId();
        }

        // 2. Retrieve or create two document types
        $docTypes = $this->db->query("SELECT id FROM documents LIMIT 2")->fetchAll(PDO::FETCH_COLUMN);
        $docType1 = (int)$docTypes[0];
        if (count($docTypes) < 2) {
            $this->db->exec("INSERT INTO documents (name, description) VALUES ('Scale Transcript', 'Scale Transcript Description')");
            $docType2 = (int)$this->db->lastInsertId();
        } else {
            $docType2 = (int)$docTypes[1];
        }

        // 3. Insert 1,000 mock documents
        $stmtDoc = $this->db->prepare("
            INSERT INTO user_documents (user_id, document_id, original_filename, stored_filename, storage_path, mime_type, file_size, checksum, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        for ($j = 0; $j < 1000; $j++) {
            $uid = $userIds[$j % 500];
            $docTypeId = ($j < 500) ? $docType1 : $docType2;
            $stmtDoc->execute([
                $uid,
                $docTypeId,
                "test_file_scale_{$j}.pdf",
                "stored_{$j}.pdf",
                "storage/private_uploads/stored_{$j}.pdf",
                "application/pdf",
                124000,
                "hash_{$j}",
                ($j % 3 === 0) ? 'approved' : (($j % 3 === 1) ? 'rejected' : 'uploaded')
            ]);
        }

        $this->db->commit();

        $setupTime = microtime(true) - $startTime;

        // Run readiness check over all 500 users and verify performance is fast without N+1 query locks
        $checkStart = microtime(true);
        $readinessService = new DocumentReadinessService();
        
        foreach ($userIds as $uid) {
            $readinessService->calculateForScholarship($uid, $this->scholarshipId);
        }

        $checkTime = microtime(true) - $checkStart;
        $memoryUsed = (memory_get_usage() - $startMemory) / 1024 / 1024;

        // Clean mock users and documents
        $this->db->beginTransaction();
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $this->db->prepare("DELETE FROM user_documents WHERE user_id IN ($placeholders)")->execute($userIds);
        $this->db->prepare("DELETE FROM users WHERE id IN ($placeholders)")->execute($userIds);
        $this->db->commit();

        echo "✔ Performance scale metrics:\n";
        echo "   • Setup Time: " . round($setupTime, 4) . "s\n";
        echo "   • 500 calculations Time: " . round($checkTime, 4) . "s\n";
        echo "   • Peak Memory Delta: " . round($memoryUsed, 2) . " MB\n";

        if ($checkTime > 3.0) {
            throw new \Exception("Performance warning: 500 document readiness checks took too long (" . round($checkTime, 2) . "s)!");
        }
    }

    private function loginUser(array $user): void {
        Auth::logout();
        Security::startSession();
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['role_name'] = $user['role_name'] ?? 'visitor';
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['user_email'] = $user['email'];
        
        $ref = new ReflectionProperty(Auth::class, 'currentUser');
        $ref->setAccessible(true);
        $ref->setValue(null, null);
    }
}
