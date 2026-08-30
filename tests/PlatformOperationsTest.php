<?php

require_once __DIR__ . '/bootstrap.php';

use App\Controllers\IntelligenceController;
use App\Services\Auth;
use App\Services\Database;
use App\Helpers\Security;

class PlatformOperationsTest {
    private PDO $db;
    private array $cleanUpUserIds = [];
    private array $cleanUpScholarshipIds = [];
    private array $cleanUpApplicationIds = [];
    private array $cleanUpNotifIds = [];

    private int $adminId;
    private int $employeeId;
    private int $studentId;

    private int $schIdComplete;
    private int $schIdDraft;
    private int $schIdExpired;
    private int $schIdDupe;

    public function __construct() {
        $this->db = Database::connection();
    }

    public function run(): void {
        echo "\n--- Running PlatformOperationsTest ---\n";
        
        try {
            $this->cleanTestData();
            $this->setupTestData();

            $this->testAuthorizationAndRoles();
            $this->testQualityScoreCalculationsAndDuplicates();
            $this->testAnalyticsAndFunnelConversions();
            $this->testBulkOperationalActionsAndTransactions();
            $this->testHighVolumePerformanceSimulation();

            echo "PlatformOperationsTest PASSED.\n";
        } finally {
            $this->cleanTestData();
        }
    }

    private function setupTestData(): void {
        $this->db->beginTransaction();

        // 1. Fetch or create roles
        $adminRoleId = $this->db->query("SELECT id FROM roles WHERE name = 'admin'")->fetchColumn();
        $employeeRoleId = $this->db->query("SELECT id FROM roles WHERE name = 'employee'")->fetchColumn();
        $visitorRoleId = $this->db->query("SELECT id FROM roles WHERE name = 'visitor'")->fetchColumn();

        // 2. Create users
        $this->db->exec("
            INSERT INTO users (email, password_hash, first_name, last_name, role_id, status)
            VALUES ('ops-admin@example.com', 'hash', 'Ops', 'Admin', {$adminRoleId}, 'active')
        ");
        $this->adminId = (int)$this->db->lastInsertId();
        $this->cleanUpUserIds[] = $this->adminId;

        $this->db->exec("
            INSERT INTO users (email, password_hash, first_name, last_name, role_id, status)
            VALUES ('ops-staff@example.com', 'hash', 'Ops', 'Staff', {$employeeRoleId}, 'active')
        ");
        $this->employeeId = (int)$this->db->lastInsertId();
        $this->cleanUpUserIds[] = $this->employeeId;

        $this->db->exec("
            INSERT INTO users (email, password_hash, first_name, last_name, role_id, status)
            VALUES ('ops-student@example.com', 'hash', 'Ops', 'Student', {$visitorRoleId}, 'active')
        ");
        $this->studentId = (int)$this->db->lastInsertId();
        $this->cleanUpUserIds[] = $this->studentId;

        // Create student profile
        $this->db->exec("
            INSERT INTO student_profiles (user_id, nationality_country_id, profile_completion_percentage)
            VALUES ({$this->studentId}, 1, 100)
        ");

        // 3. Create scholarships
        // A. Complete Published Scholarship
        $this->db->exec("
            INSERT INTO scholarships (title, slug, provider_name, description, short_description, official_website, official_application_url, country_id, study_level, funding_type, application_open_date, application_deadline, status, verification_status)
            VALUES ('Ops Scholarship Alpha', 'ops-sch-alpha', 'Provider Alpha', 'Full Desc', 'Short Desc', 'https://website.com', 'https://apply.com', 1, 'undergraduate', 'Fully Funded', '2026-01-01', '2026-12-31', 'published', 'verified')
        ");
        $this->schIdComplete = (int)$this->db->lastInsertId();
        $this->cleanUpScholarshipIds[] = $this->schIdComplete;

        // Link rules and docs to Alpha for 100% completeness score
        $this->db->exec("INSERT INTO scholarship_eligibility_rules (scholarship_id, minimum_age, minimum_cgpa) VALUES ({$this->schIdComplete}, 18, 3.0)");
        $docId = $this->db->query("SELECT id FROM documents LIMIT 1")->fetchColumn() ?: 1;
        $this->db->exec("INSERT INTO scholarship_documents (scholarship_id, document_id) VALUES ({$this->schIdComplete}, {$docId})");

        // B. Incomplete Draft Scholarship
        $this->db->exec("
            INSERT INTO scholarships (title, slug, provider_name, description, status, verification_status)
            VALUES ('Ops Scholarship Beta', 'ops-sch-beta', 'Provider Beta', 'Full Desc', 'draft', 'unverified')
        ");
        $this->schIdDraft = (int)$this->db->lastInsertId();
        $this->cleanUpScholarshipIds[] = $this->schIdDraft;

        // C. Expired Published Scholarship
        $this->db->exec("
            INSERT INTO scholarships (title, slug, provider_name, description, application_deadline, status, verification_status)
            VALUES ('Ops Scholarship Gamma', 'ops-sch-gamma', 'Provider Gamma', 'Full Desc', '2026-01-01', 'published', 'unverified')
        ");
        $this->schIdExpired = (int)$this->db->lastInsertId();
        $this->cleanUpScholarshipIds[] = $this->schIdExpired;

        // D. Duplicate Title Scholarship
        $this->db->exec("
            INSERT INTO scholarships (title, slug, provider_name, description, status, verification_status)
            VALUES ('Ops Scholarship Alpha', 'ops-sch-alpha-dupe', 'Provider Alpha Dupe', 'Full Desc', 'published', 'unverified')
        ");
        $this->schIdDupe = (int)$this->db->lastInsertId();
        $this->cleanUpScholarshipIds[] = $this->schIdDupe;

        // 4. Create tracking application
        $this->db->exec("
            INSERT INTO scholarship_applications (user_id, scholarship_id, status, applied_at)
            VALUES ({$this->studentId}, {$this->schIdComplete}, 'interested', NULL)
        ");
        $this->cleanUpApplicationIds[] = (int)$this->db->lastInsertId();

        // 5. Create notification queue logs
        $this->db->exec("
            INSERT INTO notification_logs (user_id, scholarship_id, notification_type, channel, recipient, status, attempts, available_at, idempotency_key)
            VALUES ({$this->studentId}, {$this->schIdComplete}, 'match_alert', 'email', 'ops-student@example.com', 'failed', 3, NOW(), 'idemp_ops_1')
        ");
        $this->cleanUpNotifIds[] = (int)$this->db->lastInsertId();

        $this->db->commit();
    }

    private function cleanTestData(): void {
        // Safe database deletion by IDs or emails
        $this->db->exec("DELETE FROM users WHERE email IN ('ops-admin@example.com', 'ops-staff@example.com', 'ops-student@example.com')");
        $this->db->exec("DELETE FROM scholarships WHERE slug IN ('ops-sch-alpha', 'ops-sch-beta', 'ops-sch-gamma', 'ops-sch-alpha-dupe')");
        
        $userIds = !empty($this->cleanUpUserIds) ? implode(',', $this->cleanUpUserIds) : '0';
        $schIds = !empty($this->cleanUpScholarshipIds) ? implode(',', $this->cleanUpScholarshipIds) : '0';

        $this->db->exec("DELETE FROM user_documents WHERE user_id IN ($userIds)");
        $this->db->exec("DELETE FROM education_records WHERE user_id IN ($userIds)");
        $this->db->exec("DELETE FROM student_profiles WHERE user_id IN ($userIds)");
        $this->db->exec("DELETE FROM scholarship_applications WHERE user_id IN ($userIds) OR scholarship_id IN ($schIds)");
        $this->db->exec("DELETE FROM scholarship_matches WHERE user_id IN ($userIds) OR scholarship_id IN ($schIds)");
        $this->db->exec("DELETE FROM scholarship_eligibility_rules WHERE scholarship_id IN ($schIds)");
        $this->db->exec("DELETE FROM scholarship_documents WHERE scholarship_id IN ($schIds)");
        $this->db->exec("DELETE FROM notification_logs WHERE user_id IN ($userIds) OR scholarship_id IN ($schIds)");
        $this->db->exec("DELETE FROM audit_logs WHERE user_id IN ($userIds)");
    }

    private function loginUser(array $user): void {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
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

    /**
     * TEST 1: Admin, Employee, and unauthorized Student authorization boundaries
     */
    private function testAuthorizationAndRoles(): void {
        echo "Testing admin/employee dashboard authorization rules...\n";
        $controller = new IntelligenceController();

        // 1. Block visitor student
        $this->loginUser(['id' => $this->studentId, 'role_name' => 'visitor']);
        try {
            $controller->index();
            throw new Exception("Security violation: allowed visitor student access to administrative console.");
        } catch (RuntimeException $e) {
            // Expected throw from Auth::requireRole()
        }

        // 2. Allow Admin
        $this->loginUser(['id' => $this->adminId, 'role_name' => 'admin']);
        $_GET = ['tab' => 'alerts'];
        
        ob_start();
        try {
            $controller->index();
        } catch (Exception $e) {
            ob_end_clean();
            throw $e;
        }
        ob_end_clean();

        // 3. Allow Employee (staff check)
        $this->loginUser(['id' => $this->employeeId, 'role_name' => 'employee']);
        $_GET = ['tab' => 'quality'];
        
        ob_start();
        try {
            $controller->index();
        } catch (Exception $e) {
            ob_end_clean();
            throw $e;
        }
        ob_end_clean();
    }

    /**
     * TEST 2: Quality score calculation percentages and duplicate detections
     */
    private function testQualityScoreCalculationsAndDuplicates(): void {
        echo "Testing dynamic quality metrics and validation warnings...\n";
        $controller = new IntelligenceController();
        $this->loginUser(['id' => $this->adminId, 'role_name' => 'admin']);

        // Fetch Quality list details
        $_GET = ['tab' => 'quality', 'sort' => 'completeness_score'];
        
        // Directly test calculation queries using helper methods in controller
        $reflector = new ReflectionClass(IntelligenceController::class);
        
        $methodQualityStats = $reflector->getMethod('calculateQualityStats');
        $methodQualityStats->setAccessible(true);
        $stats = $methodQualityStats->invoke($controller);

        if ($stats['total'] < 4) {
            throw new Exception("Quality stats failed: total count mismatch.");
        }
        if ($stats['duplicates'] < 2) {
            throw new Exception("Duplicate title detection failed: expected at least 2 duplicates.");
        }
        if ($stats['expired'] < 1) {
            throw new Exception("Expired listing counting failed.");
        }

        $methodQualityList = $reflector->getMethod('fetchQualityList');
        $methodQualityList->setAccessible(true);
        $list = $methodQualityList->invoke($controller);

        $completeSch = null;
        $dupeSch = null;
        foreach ($list['items'] as $item) {
            if ($item['id'] === $this->schIdComplete) {
                $completeSch = $item;
            } elseif ($item['id'] === $this->schIdDupe) {
                $dupeSch = $item;
            }
        }

        // Verify completeness score calculation
        if ($completeSch && (int)$completeSch['completeness_score'] !== 100) {
            throw new Exception("Completeness score calculation incorrect. Expected 100%, got " . $completeSch['completeness_score'] . "%");
        }

        // Verify duplicate warnings trigger
        if ($dupeSch && !in_array('Duplicate title detected', $dupeSch['warnings'])) {
            throw new Exception("Duplicate validation warning was not logged.");
        }
    }

    /**
     * TEST 3: Conversion funnel metrics and matching analytics values
     */
    private function testAnalyticsAndFunnelConversions(): void {
        echo "Testing conversion funnel math and matching metrics...\n";
        $controller = new IntelligenceController();

        $reflector = new ReflectionClass(IntelligenceController::class);
        $methodAppAnalytics = $reflector->getMethod('calculateApplicationAnalytics');
        $methodAppAnalytics->setAccessible(true);
        $appStats = $methodAppAnalytics->invoke($controller);

        if ($appStats['funnel']['interested'] < 1) {
            throw new Exception("Funnel Stage 1 (Interested) count mismatch.");
        }

        $methodMatchAnalytics = $reflector->getMethod('calculateMatchingAnalytics');
        $methodMatchAnalytics->setAccessible(true);
        $matchStats = $methodMatchAnalytics->invoke($controller);

        if (!isset($matchStats['avg_score'])) {
            throw new Exception("Matching average statistics missing.");
        }
    }

    /**
     * TEST 4: Safe bulk verify, archive, and delete operations inside transactions
     */
    private function testBulkOperationalActionsAndTransactions(): void {
        echo "Testing safe bulk action handlers, CSRF verification, and database transactions...\n";
        $controller = new IntelligenceController();
        $this->loginUser(['id' => $this->adminId, 'role_name' => 'admin']);

        // 1. Verify CSRF guard
        $_POST = [
            'csrf_token' => 'invalid_csrf',
            'bulk_action' => 'verify',
            'scholarship_ids' => [$this->schIdDupe]
        ];
        try {
            $controller->bulkAction();
            throw new Exception("Security violation: allowed bulk action execution with invalid CSRF token.");
        } catch (RuntimeException $e) {
            // Expected redirect throw
        }

        // 2. Safe bulk verification update
        $_POST = [
            'csrf_token' => 'test_token',
            'bulk_action' => 'verify',
            'scholarship_ids' => [$this->schIdDupe]
        ];
        try {
            $controller->bulkAction();
        } catch (RuntimeException $e) {
            // Expected redirect
        }

        $status = $this->db->query("SELECT verification_status FROM scholarships WHERE id = {$this->schIdDupe}")->fetchColumn();
        if ($status !== 'verified') {
            throw new Exception("Bulk verification status update failed.");
        }

        // 3. Safe bulk archive expired listings
        $_POST = [
            'csrf_token' => 'test_token',
            'bulk_action' => 'archive',
            'scholarship_ids' => [$this->schIdExpired, $this->schIdComplete]
        ];
        try {
            $controller->bulkAction();
        } catch (RuntimeException $e) {
            // Expected redirect
        }

        // Gamma (expired) should be archived. Alpha (active) should remain published
        $gammaStatus = $this->db->query("SELECT status FROM scholarships WHERE id = {$this->schIdExpired}")->fetchColumn();
        $alphaStatus = $this->db->query("SELECT status FROM scholarships WHERE id = {$this->schIdComplete}")->fetchColumn();
        
        if ($gammaStatus !== 'archived') {
            throw new Exception("Bulk archive failed to archive expired opportunity.");
        }
        if ($alphaStatus !== 'published') {
            throw new Exception("Bulk archive incorrectly archived non-expired opportunity.");
        }

        // 4. Safe bulk delete drafts
        $_POST = [
            'csrf_token' => 'test_token',
            'bulk_action' => 'delete_drafts',
            'scholarship_ids' => [$this->schIdDraft, $this->schIdComplete]
        ];
        try {
            $controller->bulkAction();
        } catch (RuntimeException $e) {
            // Expected redirect
        }

        // Beta (draft) should be deleted. Alpha (published) should remain untouched.
        $betaExists = $this->db->query("SELECT COUNT(*) FROM scholarships WHERE id = {$this->schIdDraft}")->fetchColumn();
        $alphaExists = $this->db->query("SELECT COUNT(*) FROM scholarships WHERE id = {$this->schIdComplete}")->fetchColumn();

        if ((int)$betaExists !== 0) {
            throw new Exception("Bulk delete drafts failed to remove draft listing.");
        }
        if ((int)$alphaExists !== 1) {
            throw new Exception("Bulk delete drafts incorrectly deleted published listing.");
        }
    }

    /**
     * TEST 5: Profile high-volume queries with 1,000+ mock records to verify performance
     */
    private function testHighVolumePerformanceSimulation(): void {
        echo "Simulating 1,000 application and notification entries to verify operational query scaling...\n";
        
        $this->db->beginTransaction();
        try {
            // Bulk insert 1,000 notifications outbox logs
            $sql = "INSERT INTO notification_logs (user_id, scholarship_id, notification_type, channel, recipient, status, attempts, available_at, idempotency_key) VALUES ";
            $parts = [];
            for ($i = 0; $i < 1000; $i++) {
                $parts[] = "({$this->studentId}, {$this->schIdComplete}, 'match_alert', 'email', 'bulk-log-{$i}@example.com', 'sent', 1, NOW(), 'bulk_idemp_{$i}')";
            }
            $sql .= implode(', ', $parts);
            $this->db->exec($sql);
            
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }

        $controller = new IntelligenceController();
        $this->loginUser(['id' => $this->adminId, 'role_name' => 'admin']);

        // Profile indexing lists loading speed
        $_GET = ['tab' => 'notifications', 'notif_status' => 'sent'];
        
        $reflector = new ReflectionClass(IntelligenceController::class);
        $methodQueueList = $reflector->getMethod('fetchNotificationQueueList');
        $methodQueueList->setAccessible(true);

        $start = microtime(true);
        $logs = $methodQueueList->invoke($controller);
        $duration = microtime(true) - $start;

        echo sprintf("✔ Profiled loading system queue list (1,000+ entries): %.4f seconds.\n", $duration);
        
        if ($duration > 0.05) {
            echo "⚠️ Warning: Queue list query execution exceeded 0.05s. Ensure database indices are configured.\n";
        }
    }
}
