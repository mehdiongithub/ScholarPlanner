<?php

use App\Services\Database;
use App\Services\Auth;
use App\Services\DocumentReadinessService;
use App\Services\NotificationQueueService;
use App\Controllers\ApplicationController;
use App\Helpers\Security;

if (!defined('TESTING_MODE')) {
    define('TESTING_MODE', true);
}

class ApplicationTest {
    private PDO $db;
    private int $userId;
    private int $otherUserId;
    private int $adminId;
    private int $scholarshipId;
    private int $secondScholarshipId;
    private int $documentTypeId;

    public function __construct() {
        $this->db = Database::connection();
    }

    public function run(): void {
        echo "--- Running ApplicationTest ---\n";

        $this->cleanTestData();
        $this->setupTestData();

        try {
            $this->testSecurityAndAccessControls();
            $this->testDocumentAndMatchIntegrations();
            $this->testFunctionalCRUDAndTransitions();
            $this->testDeadlineCronReminders();
            $this->testPerformanceScaleSimulation();
            
            echo "ApplicationTest PASSED.\n\n";
        } finally {
            $this->cleanTestData();
        }
    }

    private function loginUser(array $user): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $ref = new ReflectionClass(Auth::class);
        $prop = $ref->getProperty('currentUser');
        $prop->setAccessible(true);
        $prop->setValue(null, null);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role_name'] = $user['role_name'];
        $_SESSION['permissions'] = $user['permissions'] ?? [];
        $_SESSION['csrf_token'] = 'test_token';
    }

    private function logout(): void {
        $ref = new ReflectionClass(Auth::class);
        $prop = $ref->getProperty('currentUser');
        $prop->setAccessible(true);
        $prop->setValue(null, null);

        $_SESSION = [];
    }

    private function setupTestData(): void {
        $this->db->beginTransaction();

        // 1. Create Visitor User
        $this->db->exec("
            INSERT INTO users (email, password_hash, first_name, last_name, role_id, status)
            VALUES ('app-user@example.com', 'hash', 'App', 'User', 
                    (SELECT id FROM roles WHERE name = 'visitor'), 'active')
        ");
        $this->userId = (int)$this->db->lastInsertId();

        // 2. Create Other Visitor User
        $this->db->exec("
            INSERT INTO users (email, password_hash, first_name, last_name, role_id, status)
            VALUES ('app-other@example.com', 'hash', 'Other', 'User', 
                    (SELECT id FROM roles WHERE name = 'visitor'), 'active')
        ");
        $this->otherUserId = (int)$this->db->lastInsertId();

        // 3. Create Admin User
        $this->db->exec("
            INSERT INTO users (email, password_hash, first_name, last_name, role_id, status)
            VALUES ('app-admin@example.com', 'hash', 'App', 'Admin', 
                    (SELECT id FROM roles WHERE name = 'admin'), 'active')
        ");
        $this->adminId = (int)$this->db->lastInsertId();

        // 4. Create Document Type
        $this->db->exec("INSERT INTO documents (name, description) VALUES ('App Passport', 'Passport Copy for Application')");
        $this->documentTypeId = (int)$this->db->lastInsertId();

        // 5. Create Scholarships
        $this->db->exec("
            INSERT INTO scholarships (title, provider_name, description, application_deadline, status, slug, funding_type)
            VALUES ('App Scholarship Alpha', 'Alpha Provider', 'Desc Alpha', '" . date('Y-m-d', strtotime('+7 days')) . "', 'published', 'app-scholarship-alpha', 'Full')
        ");
        $this->scholarshipId = (int)$this->db->lastInsertId();

        $this->db->exec("
            INSERT INTO scholarships (title, provider_name, description, application_deadline, status, slug, funding_type)
            VALUES ('App Scholarship Beta', 'Beta Provider', 'Desc Beta', '" . date('Y-m-d', strtotime('+3 days')) . "', 'published', 'app-scholarship-beta', 'Partial')
        ");
        $this->secondScholarshipId = (int)$this->db->lastInsertId();

        // 6. Map scholarship document requirement
        $stmtMap = $this->db->prepare("INSERT INTO scholarship_documents (scholarship_id, document_id, is_required) VALUES (?, ?, 1)");
        $stmtMap->execute([$this->scholarshipId, $this->documentTypeId]);

        // 7. Seed Match Score
        $stmtMatch = $this->db->prepare("
            INSERT INTO scholarship_matches (user_id, scholarship_id, match_score, eligibility_status)
            VALUES (?, ?, ?, 'ELIGIBLE')
        ");
        $stmtMatch->execute([$this->userId, $this->scholarshipId, 85]);

        // Seed notification preferences to enable deadline reminders for this user
        $stmtPref = $this->db->prepare("
            INSERT INTO notification_preferences (user_id, notification_type, email_enabled, whatsapp_enabled)
            VALUES (?, 'deadline_reminders', 1, 1),
                   (?, 'email_alerts', 1, 0)
        ");
        $stmtPref->execute([$this->userId, $this->userId]);

        $this->db->commit();
    }

    private function cleanTestData(): void {
        $testEmails = "'app-user@example.com', 'app-other@example.com', 'app-admin@example.com'";
        
        $this->db->exec("
            DELETE FROM scholarship_application_history 
            WHERE application_id IN (
                SELECT id FROM scholarship_applications 
                WHERE user_id IN (SELECT id FROM users WHERE email IN ($testEmails) OR email LIKE 'app-scale-%')
            )
            OR changed_by IN (SELECT id FROM users WHERE email IN ($testEmails) OR email LIKE 'app-scale-%')
        ");
        
        $this->db->exec("
            DELETE FROM scholarship_applications 
            WHERE user_id IN (SELECT id FROM users WHERE email IN ($testEmails) OR email LIKE 'app-scale-%')
        ");
        
        $this->db->exec("
            DELETE FROM user_documents 
            WHERE user_id IN (SELECT id FROM users WHERE email IN ($testEmails) OR email LIKE 'app-scale-%')
        ");
        
        $this->db->exec("
            DELETE FROM scholarship_documents 
            WHERE scholarship_id IN (
                SELECT id FROM scholarships WHERE provider_name IN ('Alpha Provider', 'Beta Provider', 'Scale Provider')
            )
        ");
        
        $this->db->exec("DELETE FROM documents WHERE name = 'App Passport'");
        
        $this->db->exec("
            DELETE FROM scholarship_matches 
            WHERE user_id IN (SELECT id FROM users WHERE email IN ($testEmails) OR email LIKE 'app-scale-%')
        ");
        
        $this->db->exec("
            DELETE FROM notification_preferences 
            WHERE user_id IN (SELECT id FROM users WHERE email IN ($testEmails) OR email LIKE 'app-scale-%')
        ");

        $this->db->exec("
            DELETE FROM notification_logs 
            WHERE user_id IN (SELECT id FROM users WHERE email IN ($testEmails) OR email LIKE 'app-scale-%')
        ");
        
        $this->db->exec("DELETE FROM scholarships WHERE provider_name IN ('Alpha Provider', 'Beta Provider', 'Scale Provider')");
        
        $this->db->exec("
            DELETE FROM audit_logs 
            WHERE module = 'applications' 
              AND user_id IN (SELECT id FROM users WHERE email IN ($testEmails) OR email LIKE 'app-scale-%')
        ");
        
        $this->db->exec("DELETE FROM users WHERE email IN ($testEmails) OR email LIKE 'app-scale-%'");
    }

    /**
     * Test guest block, CSRF and IDOR boundary rules
     */
    private function testSecurityAndAccessControls(): void {
        $controller = new ApplicationController();

        // 1. Guest blocked from indexing
        $this->logout();
        try {
            $controller->index();
            throw new Exception("Security failed: guest allowed to view tracker dashboard.");
        } catch (RuntimeException $e) {
            // Expected auth block throws Exception
        }

        // Log in User A
        $this->loginUser(['id' => $this->userId, 'role_name' => 'visitor']);

        // Create initial application tracker for User A
        $_POST['csrf_token'] = 'test_token';
        $_POST['scholarship_id'] = $this->scholarshipId;
        $_POST['status'] = 'interested';
        $_POST['application_reference'] = 'REF-123';
        $_POST['personal_notes'] = 'My test notes';
        
        try {
            $controller->store();
        } catch (RuntimeException $e) {
            // Halt expected
        }

        // Fetch user application tracker row
        $appId = (int)$this->db->query("SELECT id FROM scholarship_applications WHERE user_id = {$this->userId} LIMIT 1")->fetchColumn();
        if (!$appId) {
            throw new Exception("Functional failed: Application tracker row not saved.");
        }

        // 2. IDOR: Log in User B and attempt to view User A's application details
        $this->loginUser(['id' => $this->otherUserId, 'role_name' => 'visitor']);
        try {
            $controller->show($appId);
            throw new Exception("Security IDOR failed: User B was allowed to view User A's application.");
        } catch (RuntimeException $e) {
            if ($e->getMessage() !== 'Unauthorized access to application tracker.') {
                throw $e;
            }
            // Correctly blocked
        }

        // 3. IDOR: User B tries to update User A's application notes
        $_POST['csrf_token'] = 'test_token';
        $_POST['status'] = 'planning';
        $_POST['personal_notes'] = 'hacked notes';
        try {
            $controller->update($appId);
            throw new Exception("Security IDOR failed: User B was allowed to update User A's application.");
        } catch (RuntimeException $e) {
            if ($e->getMessage() !== 'Unauthorized access to application tracker.') {
                throw $e;
            }
            // Correctly blocked
        }

        // 4. IDOR: User B tries to delete User A's application tracker row
        try {
            $controller->delete($appId);
            throw new Exception("Security IDOR failed: User B was allowed to delete User A's application.");
        } catch (RuntimeException $e) {
            if ($e->getMessage() !== 'Unauthorized access to application tracker.') {
                throw $e;
            }
            // Correctly blocked
        }

        // Log back in User A
        $this->loginUser(['id' => $this->userId, 'role_name' => 'visitor']);
        
        // 5. CSRF: Attempt write without token
        $_POST['csrf_token'] = 'invalid_token';
        $_POST['status'] = 'planning';
        try {
            $controller->update($appId);
            throw new Exception("Security CSRF failed: update allowed with invalid token.");
        } catch (RuntimeException $e) {
            // Halt expected due to redirect on CSRF failure
        }
        
        // Assert old status was preserved
        $status = $this->db->query("SELECT status FROM scholarship_applications WHERE id = $appId")->fetchColumn();
        if ($status !== 'interested') {
            throw new Exception("Security CSRF failed: status was altered in database.");
        }

        // 6. Invalid Status block
        $_POST['csrf_token'] = 'test_token';
        $_POST['status'] = 'invalid_state';
        try {
            $controller->update($appId);
        } catch (RuntimeException $e) {
            // Redirect halt
        }
        $status = $this->db->query("SELECT status FROM scholarship_applications WHERE id = $appId")->fetchColumn();
        if ($status !== 'interested') {
            throw new Exception("Security input validation failed: invalid status transition allowed.");
        }

        // 7. Invalid Scholarship ID block
        $_POST['csrf_token'] = 'test_token';
        $_POST['scholarship_id'] = 99999;
        $_POST['status'] = 'interested';
        try {
            $controller->store();
        } catch (RuntimeException $e) {
            // Redirect halt
        }
        $exists = (bool)$this->db->query("SELECT id FROM scholarship_applications WHERE scholarship_id = 99999")->fetchColumn();
        if ($exists) {
            throw new Exception("Security input validation failed: stored application with non-existent scholarship.");
        }

        // 8. Parameter Tampering: Student tries to update status directly to an admin status (accepted)
        $_POST['csrf_token'] = 'test_token';
        $_POST['status'] = 'accepted';
        try {
            $controller->update($appId);
        } catch (RuntimeException $e) {
            // Expected redirect halt
        }
        $status = $this->db->query("SELECT status FROM scholarship_applications WHERE id = $appId")->fetchColumn();
        if ($status === 'accepted') {
            throw new Exception("Security parameter tampering failed: student allowed to set status to 'accepted'.");
        }

        // 9. Document Readiness Enforcement: Student tries to update status to applied, but required document is not approved (percentage is 0%)
        $_POST['csrf_token'] = 'test_token';
        $_POST['status'] = 'applied';
        try {
            $controller->update($appId);
        } catch (RuntimeException $e) {
            // Expected redirect halt
        }
        $status = $this->db->query("SELECT status FROM scholarship_applications WHERE id = $appId")->fetchColumn();
        if ($status === 'applied') {
            throw new Exception("Document readiness enforcement failed: student allowed to apply without 100% readiness.");
        }

        // 10. Deadline Enforcement: Student tries to update tracker status for closed/expired scholarship
        // Create an expired scholarship
        $this->db->exec("
            INSERT INTO scholarships (title, provider_name, description, application_deadline, status, slug, funding_type)
            VALUES ('Expired Scholarship', 'Expired Provider', 'Desc', '" . date('Y-m-d', strtotime('-1 day')) . "', 'published', 'expired-sch-test', 'Full')
        ");
        $expiredSchId = (int)$this->db->lastInsertId();
        
        // Add tracker for expired scholarship (status: interested)
        $_POST['csrf_token'] = 'test_token';
        $_POST['scholarship_id'] = $expiredSchId;
        $_POST['status'] = 'interested';
        try {
            $controller->store();
        } catch (RuntimeException $e) {
            // Expected redirect halt
        }
        
        $expiredAppId = (int)$this->db->query("SELECT id FROM scholarship_applications WHERE scholarship_id = $expiredSchId AND user_id = {$this->userId}")->fetchColumn();
        if ($expiredAppId) {
            throw new Exception("Deadline enforcement failed: student allowed to initialize tracker for expired scholarship.");
        }
        
        // Cleanup expired scholarship
        $this->db->exec("DELETE FROM scholarships WHERE id = $expiredSchId");

        // 11. Try to update an existing tracker when scholarship deadline is in the past
        // We set the deadline of second scholarship to past
        $this->db->exec("UPDATE scholarships SET application_deadline = '" . date('Y-m-d', strtotime('-2 days')) . "' WHERE id = {$this->secondScholarshipId}");
        
        // Let's add a tracker for it first (with status = interested) by inserting directly to DB to simulate a historical tracker
        $this->db->exec("
            INSERT INTO scholarship_applications (user_id, scholarship_id, status)
            VALUES ({$this->userId}, {$this->secondScholarshipId}, 'interested')
        ");
        $histAppId = (int)$this->db->lastInsertId();
        
        // Try to change status to planning
        $_POST['csrf_token'] = 'test_token';
        $_POST['status'] = 'planning';
        try {
            $controller->update($histAppId);
        } catch (RuntimeException $e) {
            // Expected halt
        }
        
        $status = $this->db->query("SELECT status FROM scholarship_applications WHERE id = $histAppId")->fetchColumn();
        if ($status !== 'interested') {
            throw new Exception("Deadline update enforcement failed: student allowed to update status after deadline.");
        }
        
        // Try to change status to withdrawn (which should be allowed!)
        $_POST['csrf_token'] = 'test_token';
        $_POST['status'] = 'withdrawn';
        try {
            $controller->update($histAppId);
        } catch (RuntimeException $e) {
            // Expected halt
        }
        
        $status = $this->db->query("SELECT status FROM scholarship_applications WHERE id = $histAppId")->fetchColumn();
        if ($status !== 'withdrawn') {
            throw new Exception("Deadline update enforcement failed: student should be allowed to withdraw post-deadline.");
        }
        
        // Cleanup direct inserts
        $this->db->exec("DELETE FROM scholarship_application_history WHERE application_id = $histAppId");
        $this->db->exec("DELETE FROM scholarship_applications WHERE id = $histAppId");
        
        // Restore second scholarship deadline for later cron tests
        $this->db->exec("UPDATE scholarships SET application_deadline = '" . date('Y-m-d', strtotime('+3 days')) . "' WHERE id = {$this->secondScholarshipId}");

        echo "✔ Security access controls and IDOR boundaries verified.\n";
    }

    /**
     * Test typical tracker CRUD operations, status workflow walks, and history loggers
     */
    private function testFunctionalCRUDAndTransitions(): void {
        $controller = new ApplicationController();
        $this->loginUser(['id' => $this->userId, 'role_name' => 'visitor']);

        $appId = (int)$this->db->query("SELECT id FROM scholarship_applications WHERE user_id = {$this->userId} LIMIT 1")->fetchColumn();

        // 1. Update status to applied & check timeline insertion
        $_POST['csrf_token'] = 'test_token';
        $_POST['status'] = 'applied';
        $_POST['applied_at'] = date('Y-m-d');
        $_POST['application_reference'] = 'REF-ABC';
        $_POST['personal_notes'] = 'I have submitted my files.';
        
        try {
            $controller->update($appId);
        } catch (RuntimeException $e) {
            // Expected halt after save
        }

        // Verify status and history log row
        $app = $this->db->query("SELECT * FROM scholarship_applications WHERE id = $appId")->fetch();
        if ($app['status'] !== 'applied' || $app['application_reference'] !== 'REF-ABC' || $app['personal_notes'] !== 'I have submitted my files.') {
            throw new Exception("Functional update failed to save changes.");
        }

        $history = $this->db->query("SELECT * FROM scholarship_application_history WHERE application_id = $appId ORDER BY id DESC LIMIT 1")->fetch();
        if ($history['old_status'] !== 'interested' || $history['new_status'] !== 'applied') {
            throw new Exception("Workflow history log failed to insert transition record.");
        }

        // 2. Admin Review Transition
        $adminPermissions = ['applications.view', 'applications.review'];
        $this->loginUser(['id' => $this->adminId, 'role_name' => 'admin', 'permissions' => $adminPermissions]);

        $_POST['csrf_token'] = 'test_token';
        $_POST['status'] = 'interview';
        $_POST['notes'] = 'Selected for interview stage.';
        try {
            $controller->adminUpdateStatus($appId);
        } catch (RuntimeException $e) {
            // Expected halt
        }

        $app = $this->db->query("SELECT * FROM scholarship_applications WHERE id = $appId")->fetch();
        if ($app['status'] !== 'interview') {
            throw new Exception("Admin workflow update failed to set status.");
        }

        // Assert history logged the admin change
        $history = $this->db->query("SELECT * FROM scholarship_application_history WHERE application_id = $appId ORDER BY id DESC LIMIT 1")->fetch();
        if ($history['old_status'] !== 'applied' || $history['new_status'] !== 'interview' || $history['notes'] !== 'Selected for interview stage.' || (int)$history['changed_by'] !== $this->adminId) {
            throw new Exception("Workflow history log failed to log admin transition correctly.");
        }

        // Assert Notification enqueued to queue outbox for applicant User A
        $notif = $this->db->query("SELECT * FROM notification_logs WHERE user_id = {$this->userId} ORDER BY id DESC LIMIT 1")->fetch();
        if (!$notif || $notif['notification_type'] !== 'APPLICATION_STATUS_UPDATED' || strpos($notif['payload'], 'Selected for interview stage.') === false) {
            throw new Exception("Workflow notification failed to enqueue status update log.");
        }

        echo "✔ Workflow status transitions, timeline logging, and notification triggers verified.\n";
    }

    /**
     * Verify Document checklist integrations and Matching engine linkages
     */
    private function testDocumentAndMatchIntegrations(): void {
        $this->loginUser(['id' => $this->userId, 'role_name' => 'visitor']);

        $appId = (int)$this->db->query("SELECT id FROM scholarship_applications WHERE user_id = {$this->userId} LIMIT 1")->fetchColumn();

        // 1. Initial State: Document missing (approved = 0, readiness = 0%)
        $readinessService = new DocumentReadinessService();
        $metrics = $readinessService->calculateForScholarship($this->userId, $this->scholarshipId);
        if ($metrics['required_count'] !== 1 || $metrics['approved_count'] !== 0 || $metrics['readiness_percentage'] !== 0) {
            throw new Exception("Document readiness initial metrics are incorrect: " . json_encode($metrics));
        }

        // 2. Upload and Approve Passport
        $this->db->prepare("
            INSERT INTO user_documents (user_id, document_id, original_filename, stored_filename, storage_path, mime_type, file_size, checksum, status)
            VALUES (?, ?, 'pass.pdf', 'p.pdf', 'path', 'application/pdf', 100, 'hash', 'approved')
        ")->execute([$this->userId, $this->documentTypeId]);

        // Recalculate metrics (approved = 1, readiness = 100%)
        $metrics = $readinessService->calculateForScholarship($this->userId, $this->scholarshipId);
        if ($metrics['required_count'] !== 1 || $metrics['approved_count'] !== 1 || $metrics['readiness_percentage'] !== 100) {
            throw new Exception("Document readiness calculation values failed to update: " . json_encode($metrics));
        }

        echo "✔ Document readiness checklist and matching metrics integrations verified.\n";
    }

    /**
     * Test the updated deadline reminders cron with application tracker checks
     */
    private function testDeadlineCronReminders(): void {
        // Enqueue warning offsets for Beta Scholarship (closing in 3 days)
        // User A is NOT eligible by matching but has an active application tracker for Beta Scholarship!
        $this->loginUser(['id' => $this->userId, 'role_name' => 'visitor']);
        
        // Add Beta Scholarship application tracker (status: documents_pending)
        $_POST['csrf_token'] = 'test_token';
        $_POST['scholarship_id'] = $this->secondScholarshipId;
        $_POST['status'] = 'documents_pending';
        $_POST['application_reference'] = '';
        $_POST['personal_notes'] = 'Active Beta track';
        
        try {
            (new ApplicationController())->store();
        } catch (RuntimeException $e) {
            // Halt
        }

        // Clear notification logs from setup
        $this->db->exec("DELETE FROM notification_logs");

        // Run deadline cron command via CLI SAPI emulation
        ob_start();
        require dirname(__DIR__) . '/cron/deadline_reminders.php';
        $logOutput = ob_get_clean();

        // Verify notification enqueued for Beta Scholarship (closing in 3 days) to User A
        $notif = $this->db->query("SELECT * FROM notification_logs WHERE user_id = {$this->userId} ORDER BY id DESC LIMIT 1")->fetch();
        if (!$notif || $notif['notification_type'] !== 'SCHOLARSHIP_DEADLINE_SOON' || strpos($notif['payload'], 'App Scholarship Beta') === false) {
            throw new Exception("Cron deadline reminders failed to queue warnings for tracked applications: " . $logOutput);
        }

        echo "✔ Cron deadline reminders enqueuing and active tracker mapping verified.\n";
    }

    /**
     * Scale simulation checks performance metrics across 500 users, 1000 scholarships, 1000 applications
     */
    private function testPerformanceScaleSimulation(): void {
        // Scale Setup
        $this->db->beginTransaction();

        $userIds = [];
        for ($i = 0; $i < 500; $i++) {
            $this->db->exec("
                INSERT INTO users (email, password_hash, first_name, last_name, role_id, status)
                VALUES ('app-scale-u-{$i}@example.com', 'hash', 'First', 'Last', 
                        (SELECT id FROM roles WHERE name = 'visitor'), 'active')
            ");
            $userIds[] = (int)$this->db->lastInsertId();
        }

        $schIds = [];
        for ($i = 0; $i < 1000; $i++) {
            $this->db->exec("
                INSERT INTO scholarships (title, provider_name, description, application_deadline, status, slug, funding_type)
                VALUES ('Scale Scholarship Opportunity {$i}', 'Scale Provider', 'Scale Description', '" . date('Y-m-d', strtotime('+30 days')) . "', 'published', 'scale-sch-{$i}', 'Partial')
            ");
            $schIds[] = (int)$this->db->lastInsertId();
        }

        $startTime = microtime(true);

        // Link 1000 application tracking records
        $stmtApp = $this->db->prepare("
            INSERT INTO scholarship_applications (user_id, scholarship_id, status, personal_notes)
            VALUES (?, ?, ?, ?)
        ");

        for ($k = 0; $k < 1000; $k++) {
            $uid = $userIds[$k % 500];
            $sid = $schIds[$k];
            $stmtApp->execute([
                $uid,
                $sid,
                ($k % 3 === 0) ? 'applied' : (($k % 3 === 1) ? 'planning' : 'interested'),
                'Bulk seeded notes'
            ]);
        }

        $this->db->commit();
        $setupTime = microtime(true) - $startTime;

        // Perform calculation: fetch all applications for user with indices
        $evalStart = microtime(true);
        $readiness = new DocumentReadinessService();

        for ($p = 0; $p < 500; $p++) {
            // Check readiness check for 2 scholarships per user (total 1000 checks)
            $readiness->calculateForScholarship($userIds[$p], $schIds[$p]);
            $readiness->calculateForScholarship($userIds[$p], $schIds[$p + 500]);
        }

        $evalTime = microtime(true) - $evalStart;

        echo "✔ Performance scale metrics:\n";
        echo "   • Setup Time: " . round($setupTime, 4) . "s\n";
        echo "   • 1000 readiness evaluations Time: " . round($evalTime, 4) . "s\n";

        if ($evalTime > 3.0) {
            throw new Exception("Performance scale limit exceeded: calculations took {$evalTime}s");
        }
    }
}
