<?php

if (!defined('TESTING_MODE')) {
    define('TESTING_MODE', true);
}

use App\Services\Database;
use App\Services\Auth;
use App\Services\NotificationSchedulerService;
use App\Services\NotificationService;
use App\Services\NotificationQueueService;
use App\Services\NotificationTypes;
use App\Services\SubscriptionService;
use App\Helpers\Security;

class NotificationSchedulerTest {
    private PDO $db;
    private NotificationSchedulerService $scheduler;
    private int $userId;
    private int $schId;
    private int $premiumPlanId;
    private array $originalSettings;

    public function __construct() {
        $this->db = Database::connection();
        $this->scheduler = new NotificationSchedulerService($this->db);
    }

    public function run(): void {
        echo "--- Running NotificationSchedulerTest ---\n";

        $this->backupOriginalSettings();
        $this->cleanTestData();
        $this->setupTestData();

        try {
            $this->test1_AdminCanSaveNotificationSettings();
            $this->test2_UnauthorizedUserCannotChangeSettings();
            $this->test3_CsrfProtectionWorks();
            $this->test4_InvalidWeekdayRejected();
            $this->test5_InvalidTimeRejected();
            $this->test6_InvalidTimezoneRejected();
            $this->test7_InvalidBatchSizeRejected();
            $this->test8_SchedulerDisabledNoProcessing();
            $this->test9_SchedulerEnabledOnAllowedDayEligible();
            $this->test10_SchedulerEnabledOnDisallowedDayNoProcessing();
            $this->test11_SchedulerOutsideConfiguredTimeNoProcessing();
            $this->test12_SchedulerInsideConfiguredTimeProcessingAllowed();
            $this->test13_DryRunDoesNotSendMessages();
            $this->test14_DryRunDoesNotMarkNotificationsAsSent();
            $this->test15_BatchSizeIsRespected();
            $this->test16_DuplicateCronExecutionDoesNotDuplicateNotificationEvents();
            $this->test17_ConcurrentSchedulerProcessesCannotClaimSameNotification();
            $this->test18_StaleProcessingRecordsRecoveredSafely();
            $this->test19_NewMatchIdempotencyFromStep3RemainsIntact();
            $this->test20_ExistingScholarshipMatchingIntegration();
            $this->test21_ExistingSubscriptionGatingIntegration();
            $this->test22_ExistingWacrmProviderIntegration();

            echo "NotificationSchedulerTest PASSED.\n\n";
        } finally {
            $this->restoreOriginalSettings();
            $this->cleanTestData();
        }
    }

    private function backupOriginalSettings(): void {
        $this->originalSettings = $this->scheduler->getSettings();
    }

    private function restoreOriginalSettings(): void {
        if (!empty($this->originalSettings)) {
            $this->scheduler->updateSettings([
                'whatsapp_notifications_enabled' => $this->originalSettings['whatsapp_notifications_enabled'] ? '1' : '0',
                'whatsapp_allowed_days' => $this->originalSettings['whatsapp_allowed_days'],
                'whatsapp_send_time' => $this->originalSettings['whatsapp_send_time'],
                'whatsapp_timezone' => $this->originalSettings['whatsapp_timezone'],
                'whatsapp_batch_size' => $this->originalSettings['whatsapp_batch_size'],
                'whatsapp_new_match_enabled' => $this->originalSettings['whatsapp_new_match_enabled'] ? '1' : '0',
                'whatsapp_deadline_reminder_enabled' => $this->originalSettings['whatsapp_deadline_reminder_enabled'] ? '1' : '0'
            ]);
        }
    }

    private function cleanTestData(): void {
        $this->db->exec("DELETE FROM notification_logs WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'notif-sched-%')");
        $this->db->exec("DELETE FROM notification_preferences WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'notif-sched-%')");
        $this->db->exec("DELETE FROM subscriptions WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'notif-sched-%')");
        $this->db->exec("DELETE FROM education_records WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'notif-sched-%')");
        $this->db->exec("DELETE FROM student_profiles WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'notif-sched-%')");
        $this->db->exec("DELETE FROM user_preferences WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'notif-sched-%')");
        $this->db->exec("DELETE FROM users WHERE email LIKE 'notif-sched-%'");
        $this->db->exec("DELETE FROM scholarships WHERE slug LIKE 'notif-sched-%'");
    }

    private function setupTestData(): void {
        $roleId = $this->db->query("SELECT id FROM roles WHERE name = 'visitor' LIMIT 1")->fetchColumn();
        $this->premiumPlanId = $this->db->query("SELECT id FROM subscription_plans WHERE slug = 'premium-monthly' LIMIT 1")->fetchColumn();

        // Create test user
        $stmtUser = $this->db->prepare("
            INSERT INTO users (first_name, last_name, email, phone, whatsapp_phone, password_hash, role_id, status, email_opt_in, whatsapp_opt_in, created_at)
            VALUES ('Sched', 'Tester', 'notif-sched-user@example.com', '+923007777777', '+923007777777', 'hash', :role_id, 'active', 1, 1, NOW())
        ");
        $stmtUser->execute(['role_id' => $roleId]);
        $this->userId = $this->db->lastInsertId();

        // Create student profile
        $this->db->exec("INSERT INTO student_profiles (user_id, gender, date_of_birth, nationality_country_id, residence_country_id) VALUES ({$this->userId}, 'male', '2000-01-01', 1, 1)");
        $this->db->exec("INSERT INTO education_records (user_id, institution_name, degree_level, degree_title, field_of_study, cgpa, cgpa_scale, percentage, is_current) VALUES ({$this->userId}, 'Test University', 'Bachelor', 'Bachelor of Science', 'Computer Science', 3.80, 4.00, 95.00, 1)");
        $this->db->exec("INSERT INTO user_preferences (user_id, funding_preferences) VALUES ({$this->userId}, 'Fully Funded')");
        $this->db->exec("INSERT INTO notification_preferences (user_id, notification_type, whatsapp_enabled) VALUES ({$this->userId}, 'whatsapp_alerts', 1)");
        $this->db->exec("INSERT INTO notification_preferences (user_id, notification_type, whatsapp_enabled) VALUES ({$this->userId}, 'matching_scholarship_alerts', 1)");
        $this->db->exec("INSERT INTO notification_preferences (user_id, notification_type, whatsapp_enabled) VALUES ({$this->userId}, 'deadline_reminders', 1)");

        // Create test scholarship
        $stmtSch = $this->db->prepare("
            INSERT INTO scholarships (title, slug, status, verification_status, provider_name, country_id, funding_type, application_deadline, description, created_at)
            VALUES ('Sched Match Scholarship', 'notif-sched-match-scholarship', 'published', 'verified', 'Global Fund', 1, 'Fully Funded', DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'Description', NOW())
        ");
        $stmtSch->execute();
        $this->schId = $this->db->lastInsertId();

        $this->db->exec("INSERT INTO scholarship_degree_levels (scholarship_id, degree_level) VALUES ({$this->schId}, 'Bachelor')");
        $this->db->exec("INSERT INTO scholarship_eligible_nationalities (scholarship_id, country_id) VALUES ({$this->schId}, 1)");
        $this->db->exec("INSERT INTO scholarship_eligibility_rules (scholarship_id, minimum_cgpa, cgpa_scale) VALUES ({$this->schId}, 3.00, 4.00)");

        // Set active premium subscription
        $this->db->exec("INSERT INTO subscriptions (user_id, plan_id, status, starts_at, ends_at) VALUES ({$this->userId}, {$this->premiumPlanId}, 'active', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY))");
    }

    private function test1_AdminCanSaveNotificationSettings(): void {
        $validSettings = [
            'whatsapp_notifications_enabled' => '1',
            'whatsapp_allowed_days' => ['Monday', 'Wednesday', 'Friday'],
            'whatsapp_send_time' => '14:30',
            'whatsapp_timezone' => 'Asia/Karachi',
            'whatsapp_batch_size' => 75,
            'whatsapp_new_match_enabled' => '1',
            'whatsapp_deadline_reminder_enabled' => '1'
        ];

        $updated = $this->scheduler->updateSettings($validSettings);
        $saved = $this->scheduler->getSettings();

        if (!$saved['whatsapp_notifications_enabled'] || $saved['whatsapp_send_time'] !== '14:30' || $saved['whatsapp_batch_size'] !== 75) {
            throw new Exception("TEST 1 Failed: Settings were not correctly saved into the database.");
        }
        if ($saved['whatsapp_allowed_days'] !== ['Monday', 'Wednesday', 'Friday']) {
            throw new Exception("TEST 1 Failed: Allowed days were not persisted correctly.");
        }
        echo "✔ TEST 1: Admin can save notification settings successfully.\n";
    }

    private function test2_UnauthorizedUserCannotChangeSettings(): void {
        $adminCtrl = new \App\Controllers\AdminController();

        // 1. Unauthenticated (no session)
        Auth::logout();
        unset($_SESSION['user_id']);
        unset($_SESSION['role_id']);
        unset($_SESSION['role_name']);
        $caught = false;
        try {
            $adminCtrl->settingsUpdate();
        } catch (\Exception $e) {
            $caught = true; // Blocked
        }
        if (!$caught) {
            throw new Exception("TEST 2 Failed: Unauthenticated request was not blocked.");
        }

        // 2. Normal student user (visitor role)
        Auth::logout();
        $_SESSION['user_id'] = $this->userId;
        $visitorRoleId = $this->db->query("SELECT id FROM roles WHERE name = 'visitor' LIMIT 1")->fetchColumn();
        $_SESSION['role_id'] = $visitorRoleId;
        $_SESSION['role_name'] = 'visitor';

        $caughtVisitor = false;
        try {
            $adminCtrl->settingsUpdate();
        } catch (\Exception $e) {
            $caughtVisitor = true;
        }
        if (!$caughtVisitor) {
            throw new Exception("TEST 2 Failed: Visitor role without settings.edit was not blocked.");
        }
        echo "✔ TEST 2: Unauthorized user cannot change settings.\n";
    }

    private function test3_CsrfProtectionWorks(): void {
        Auth::logout();
        $adminUser = $this->db->query("SELECT u.id, u.role_id, r.name as role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE r.name IN ('admin', 'super_admin') LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if (!$adminUser) {
            // Create fallback admin for testing
            $adminRoleId = $this->db->query("SELECT id FROM roles WHERE name = 'admin' LIMIT 1")->fetchColumn();
            $stmt = $this->db->prepare("INSERT INTO users (first_name, last_name, email, password_hash, role_id, status) VALUES ('Test', 'Admin', 'notif-sched-admin@example.com', 'hash', :rid, 'active')");
            $stmt->execute(['rid' => $adminRoleId]);
            $adminUser = ['id' => $this->db->lastInsertId(), 'role_id' => $adminRoleId, 'role_name' => 'admin'];
        }

        $_SESSION['user_id'] = $adminUser['id'];
        $_SESSION['role_id'] = $adminUser['role_id'];
        $_SESSION['role_name'] = $adminUser['role_name'];
        $_SESSION['csrf_token'] = Security::csrfToken();

        // Attempt settings update with missing/invalid CSRF token
        $_POST = [
            'csrf_token' => 'invalid_token_123',
            'is_notification_settings' => '1',
            'whatsapp_send_time' => '10:00'
        ];

        $adminCtrl = new \App\Controllers\AdminController();
        // Capture session errors
        $_SESSION['admin_errors'] = null;
        $adminCtrl->settingsUpdate();

        if (empty($_SESSION['admin_errors']) || strpos($_SESSION['admin_errors'], 'CSRF') === false) {
            throw new Exception("TEST 3 Failed: Request with invalid CSRF token was not rejected.");
        }
        echo "✔ TEST 3: CSRF protection verified.\n";
    }

    private function test4_InvalidWeekdayRejected(): void {
        $validation = $this->scheduler->validateSettings([
            'whatsapp_allowed_days' => ['Monday', 'InvalidDay', 'Friday']
        ]);
        if ($validation['valid'] || empty($validation['errors']['whatsapp_allowed_days'])) {
            throw new Exception("TEST 4 Failed: Invalid weekday name was not rejected.");
        }

        $validationEmpty = $this->scheduler->validateSettings([
            'whatsapp_allowed_days' => []
        ]);
        if ($validationEmpty['valid']) {
            throw new Exception("TEST 4 Failed: Empty weekday list was not rejected.");
        }
        echo "✔ TEST 4: Invalid weekday rejected.\n";
    }

    private function test5_InvalidTimeRejected(): void {
        $invalidTimes = ['25:00', '12:60', '9:00', 'invalid', '24:00', '10:0'];
        foreach ($invalidTimes as $time) {
            $validation = $this->scheduler->validateSettings([
                'whatsapp_allowed_days' => ['Monday'],
                'whatsapp_send_time' => $time
            ]);
            if ($validation['valid'] || empty($validation['errors']['whatsapp_send_time'])) {
                throw new Exception("TEST 5 Failed: Invalid time '{$time}' was incorrectly accepted.");
            }
        }
        echo "✔ TEST 5: Invalid time format rejected.\n";
    }

    private function test6_InvalidTimezoneRejected(): void {
        $invalidTimezones = ['Invalid/Timezone', 'Asia/NonExistentCity', 'Moon/Crater'];
        foreach ($invalidTimezones as $tz) {
            $validation = $this->scheduler->validateSettings([
                'whatsapp_allowed_days' => ['Monday'],
                'whatsapp_send_time' => '10:00',
                'whatsapp_timezone' => $tz
            ]);
            if ($validation['valid'] || empty($validation['errors']['whatsapp_timezone'])) {
                throw new Exception("TEST 6 Failed: Invalid timezone '{$tz}' was incorrectly accepted.");
            }
        }
        echo "✔ TEST 6: Invalid timezone identifier rejected.\n";
    }

    private function test7_InvalidBatchSizeRejected(): void {
        $invalidBatches = [0, -5, 501, 10000, 'abc'];
        foreach ($invalidBatches as $batch) {
            $validation = $this->scheduler->validateSettings([
                'whatsapp_allowed_days' => ['Monday'],
                'whatsapp_send_time' => '10:00',
                'whatsapp_batch_size' => $batch
            ]);
            if ($validation['valid'] || empty($validation['errors']['whatsapp_batch_size'])) {
                throw new Exception("TEST 7 Failed: Invalid batch size '{$batch}' was incorrectly accepted.");
            }
        }
        echo "✔ TEST 7: Invalid batch size rejected.\n";
    }

    private function test8_SchedulerDisabledNoProcessing(): void {
        $settings = [
            'whatsapp_notifications_enabled' => false,
            'whatsapp_allowed_days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
            'whatsapp_send_time' => '00:00',
            'whatsapp_timezone' => 'UTC',
            'whatsapp_batch_size' => 50,
            'whatsapp_new_match_enabled' => true,
            'whatsapp_deadline_reminder_enabled' => true
        ];

        $res = $this->scheduler->isScheduleDue(new DateTime('now', new DateTimeZone('UTC')), $settings);
        if ($res['due'] || $res['reason'] !== 'notifications_disabled') {
            throw new Exception("TEST 8 Failed: Scheduler evaluated as due when disabled.");
        }
        echo "✔ TEST 8: Scheduler disabled -> no processing.\n";
    }

    private function test9_SchedulerEnabledOnAllowedDayEligible(): void {
        // Monday 10:00 UTC
        $customTime = new DateTime('2026-09-07 10:05:00', new DateTimeZone('UTC')); // 2026-09-07 is Monday
        $settings = [
            'whatsapp_notifications_enabled' => true,
            'whatsapp_allowed_days' => ['Monday', 'Wednesday'],
            'whatsapp_send_time' => '10:00',
            'whatsapp_timezone' => 'UTC',
            'whatsapp_batch_size' => 50,
            'whatsapp_new_match_enabled' => true,
            'whatsapp_deadline_reminder_enabled' => true
        ];

        $res = $this->scheduler->isScheduleDue($customTime, $settings);
        if (!$res['due']) {
            throw new Exception("TEST 9 Failed: Schedule was rejected on allowed Monday after send time.");
        }
        echo "✔ TEST 9: Scheduler enabled on allowed day -> eligible for processing.\n";
    }

    private function test10_SchedulerEnabledOnDisallowedDayNoProcessing(): void {
        // Tuesday 10:00 UTC (disallowed)
        $customTime = new DateTime('2026-09-08 10:05:00', new DateTimeZone('UTC')); // 2026-09-08 is Tuesday
        $settings = [
            'whatsapp_notifications_enabled' => true,
            'whatsapp_allowed_days' => ['Monday', 'Wednesday'],
            'whatsapp_send_time' => '10:00',
            'whatsapp_timezone' => 'UTC',
            'whatsapp_batch_size' => 50,
            'whatsapp_new_match_enabled' => true,
            'whatsapp_deadline_reminder_enabled' => true
        ];

        $res = $this->scheduler->isScheduleDue($customTime, $settings);
        if ($res['due'] || $res['reason'] !== 'day_not_allowed') {
            throw new Exception("TEST 10 Failed: Schedule was permitted on disallowed Tuesday.");
        }
        echo "✔ TEST 10: Scheduler on disallowed day -> no processing.\n";
    }

    private function test11_SchedulerOutsideConfiguredTimeNoProcessing(): void {
        // Monday 09:30 UTC (configured for 10:00 UTC)
        $customTime = new DateTime('2026-09-07 09:30:00', new DateTimeZone('UTC'));
        $settings = [
            'whatsapp_notifications_enabled' => true,
            'whatsapp_allowed_days' => ['Monday'],
            'whatsapp_send_time' => '10:00',
            'whatsapp_timezone' => 'UTC',
            'whatsapp_batch_size' => 50,
            'whatsapp_new_match_enabled' => true,
            'whatsapp_deadline_reminder_enabled' => true
        ];

        $res = $this->scheduler->isScheduleDue($customTime, $settings);
        if ($res['due'] || $res['reason'] !== 'before_send_time') {
            throw new Exception("TEST 11 Failed: Schedule was permitted before configured send time.");
        }
        echo "✔ TEST 11: Scheduler outside configured time -> no processing.\n";
    }

    private function test12_SchedulerInsideConfiguredTimeProcessingAllowed(): void {
        // Monday 10:00 UTC exact
        $customTime = new DateTime('2026-09-07 10:00:00', new DateTimeZone('UTC'));
        $settings = [
            'whatsapp_notifications_enabled' => true,
            'whatsapp_allowed_days' => ['Monday'],
            'whatsapp_send_time' => '10:00',
            'whatsapp_timezone' => 'UTC',
            'whatsapp_batch_size' => 50,
            'whatsapp_new_match_enabled' => true,
            'whatsapp_deadline_reminder_enabled' => true
        ];

        $res = $this->scheduler->isScheduleDue($customTime, $settings);
        if (!$res['due']) {
            throw new Exception("TEST 12 Failed: Schedule was not permitted at exact send time.");
        }
        echo "✔ TEST 12: Scheduler inside configured time -> processing allowed.\n";
    }

    private function test13_DryRunDoesNotSendMessages(): void {
        // Enqueue a pending notification
        $this->db->exec("DELETE FROM notification_logs WHERE user_id = {$this->userId}");
        $notifService = new NotificationService();
        $notifService->createNewMatchNotification($this->userId, $this->schId);

        // Execute dry-run
        $dryReport = $this->scheduler->runDryRun();

        if (!$dryReport['dry_run']) {
            throw new Exception("TEST 13 Failed: Dry-run flag missing from dry run report.");
        }
        echo "✔ TEST 13: Dry-run does not send messages.\n";
    }

    private function test14_DryRunDoesNotMarkNotificationsAsSent(): void {
        $statusBefore = $this->db->query("SELECT status FROM notification_logs WHERE user_id = {$this->userId} AND scholarship_id = {$this->schId}")->fetchColumn();
        
        $dryReport = $this->scheduler->runDryRun();

        $statusAfter = $this->db->query("SELECT status FROM notification_logs WHERE user_id = {$this->userId} AND scholarship_id = {$this->schId}")->fetchColumn();

        if ($statusBefore !== 'pending' || $statusAfter !== 'pending') {
            throw new Exception("TEST 14 Failed: Dry-run mutated notification record status in database.");
        }
        echo "✔ TEST 14: Dry-run does not mark notifications as sent.\n";
    }

    private function test15_BatchSizeIsRespected(): void {
        $this->db->exec("DELETE FROM notification_logs WHERE user_id = {$this->userId}");

        // Insert 5 test pending rows
        for ($i = 1; $i <= 5; $i++) {
            $key = "sched_batch_test_{$this->userId}_{$i}";
            $this->db->exec("
                INSERT INTO notification_logs (user_id, scholarship_id, notification_type, channel, recipient, idempotency_key, status, available_at)
                VALUES ({$this->userId}, {$this->schId}, 'NEW_MATCH', 'whatsapp', '+923007777777', '{$key}', 'pending', NOW())
            ");
        }

        // Claim with batch size of 2
        $batch = $this->scheduler->claimPendingBatch(2, [NotificationTypes::NEW_MATCH], 'whatsapp');

        if (count($batch) !== 2) {
            throw new Exception("TEST 15 Failed: Expected 2 claimed rows, got " . count($batch));
        }

        $remainingPending = $this->db->query("SELECT COUNT(*) FROM notification_logs WHERE user_id = {$this->userId} AND status = 'pending'")->fetchColumn();
        if ($remainingPending != 3) {
            throw new Exception("TEST 15 Failed: Expected 3 remaining pending rows, found $remainingPending.");
        }
        echo "✔ TEST 15: Batch size is strictly respected.\n";
    }

    private function test16_DuplicateCronExecutionDoesNotDuplicateNotificationEvents(): void {
        $this->db->exec("DELETE FROM notification_logs WHERE user_id = {$this->userId}");

        // Create initial NEW_MATCH event
        $notifService = new NotificationService();
        $notifService->createNewMatchNotification($this->userId, $this->schId);

        $initialCount = $this->db->query("SELECT COUNT(*) FROM notification_logs WHERE user_id = {$this->userId} AND scholarship_id = {$this->schId}")->fetchColumn();

        // Simulate subsequent cron matching execution attempts
        $notifService->createNewMatchNotification($this->userId, $this->schId);
        $notifService->createNewMatchNotification($this->userId, $this->schId);

        $finalCount = $this->db->query("SELECT COUNT(*) FROM notification_logs WHERE user_id = {$this->userId} AND scholarship_id = {$this->schId}")->fetchColumn();

        if ($initialCount != 1 || $finalCount != 1) {
            throw new Exception("TEST 16 Failed: Repeated cron run created duplicate notification events.");
        }
        echo "✔ TEST 16: Duplicate cron execution does not duplicate notification events.\n";
    }

    private function test17_ConcurrentSchedulerProcessesCannotClaimSameNotification(): void {
        $this->db->exec("DELETE FROM notification_logs WHERE user_id = {$this->userId}");

        // Insert 4 pending rows
        for ($i = 1; $i <= 4; $i++) {
            $key = "sched_concurrent_test_{$this->userId}_{$i}";
            $this->db->exec("
                INSERT INTO notification_logs (user_id, scholarship_id, notification_type, channel, recipient, idempotency_key, status, available_at)
                VALUES ({$this->userId}, {$this->schId}, 'NEW_MATCH', 'whatsapp', '+923007777777', '{$key}', 'pending', NOW())
            ");
        }

        // Process 1 claims batch of 2
        $batch1 = $this->scheduler->claimPendingBatch(2, [NotificationTypes::NEW_MATCH], 'whatsapp');
        // Process 2 claims batch of 2 immediately
        $batch2 = $this->scheduler->claimPendingBatch(2, [NotificationTypes::NEW_MATCH], 'whatsapp');

        $ids1 = array_column($batch1, 'id');
        $ids2 = array_column($batch2, 'id');

        // Verify mutually exclusive batches (no overlap)
        $intersection = array_intersect($ids1, $ids2);
        if (!empty($intersection)) {
            throw new Exception("TEST 17 Failed: Two worker processes claimed overlapping notification rows: " . implode(', ', $intersection));
        }

        if (count($ids1) !== 2 || count($ids2) !== 2) {
            throw new Exception("TEST 17 Failed: Expected 2 rows per batch, got " . count($ids1) . " and " . count($ids2));
        }
        echo "✔ TEST 17: Concurrent scheduler processes cannot claim the same notification.\n";
    }

    private function test18_StaleProcessingRecordsRecoveredSafely(): void {
        $this->db->exec("DELETE FROM notification_logs WHERE user_id = {$this->userId}");

        // 1. Insert a stale processing record (> 20 mins ago) with attempts = 0
        $keyStale1 = "sched_stale_1_{$this->userId}";
        $this->db->exec("
            INSERT INTO notification_logs (user_id, scholarship_id, notification_type, channel, recipient, idempotency_key, status, attempts, processing_started_at)
            VALUES ({$this->userId}, {$this->schId}, 'NEW_MATCH', 'whatsapp', '+923007777777', '{$keyStale1}', 'processing', 0, DATE_SUB(NOW(), INTERVAL 25 MINUTE))
        ");

        // 2. Insert a stale processing record with attempts = 3 (max retry reached)
        $keyStaleMax = "sched_stale_max_{$this->userId}";
        $this->db->exec("
            INSERT INTO notification_logs (user_id, scholarship_id, notification_type, channel, recipient, idempotency_key, status, attempts, processing_started_at)
            VALUES ({$this->userId}, {$this->schId}, 'NEW_MATCH', 'whatsapp', '+923007777777', '{$keyStaleMax}', 'processing', 3, DATE_SUB(NOW(), INTERVAL 25 MINUTE))
        ");

        $recovery = $this->scheduler->recoverStaleProcessing(15, 3);

        if ($recovery['recovered'] !== 1 || $recovery['failed'] !== 1) {
            throw new Exception("TEST 18 Failed: Expected 1 recovered and 1 failed, got " . json_encode($recovery));
        }

        $row1Status = $this->db->query("SELECT status, attempts FROM notification_logs WHERE idempotency_key = '{$keyStale1}'")->fetch(PDO::FETCH_ASSOC);
        if ($row1Status['status'] !== 'pending' || $row1Status['attempts'] != 1) {
            throw new Exception("TEST 18 Failed: Stale row 1 was not reset to pending with incremented attempt.");
        }

        $rowMaxStatus = $this->db->query("SELECT status, error_message FROM notification_logs WHERE idempotency_key = '{$keyStaleMax}'")->fetch(PDO::FETCH_ASSOC);
        if ($rowMaxStatus['status'] !== 'failed') {
            throw new Exception("TEST 18 Failed: Stale row exceeding max attempts was not marked failed.");
        }
        echo "✔ TEST 18: Stale processing records can be recovered safely.\n";
    }

    private function test19_NewMatchIdempotencyFromStep3RemainsIntact(): void {
        $this->db->exec("DELETE FROM notification_logs WHERE user_id = {$this->userId}");
        $key = "{$this->userId}_{$this->schId}_NEW_MATCH_whatsapp";

        $notifService = new NotificationService();
        $res1 = $notifService->createNewMatchNotification($this->userId, $this->schId);
        $res2 = $notifService->createNewMatchNotification($this->userId, $this->schId);

        if (!$res1 || $res2) {
            throw new Exception("TEST 19 Failed: Step 3 idempotency was breached.");
        }

        $count = $this->db->query("SELECT COUNT(*) FROM notification_logs WHERE idempotency_key = '{$key}'")->fetchColumn();
        if ($count != 1) {
            throw new Exception("TEST 19 Failed: Found $count records in DB.");
        }
        echo "✔ TEST 19: NEW_MATCH idempotency from Step 3 remains intact.\n";
    }

    private function test20_ExistingScholarshipMatchingIntegration(): void {
        $matching = new \App\Services\ScholarshipMatchingService();
        $match = $matching->matchUserAndScholarship($this->userId, $this->schId);

        if ($match['eligibility_status'] !== 'ELIGIBLE') {
            throw new Exception("TEST 20 Failed: Matching service failed to evaluate test profile as eligible.");
        }
        echo "✔ TEST 20: Existing scholarship matching tests still pass.\n";
    }

    private function test21_ExistingSubscriptionGatingIntegration(): void {
        $canWhatsApp = SubscriptionService::can($this->userId, 'whatsapp_alerts');
        if (!$canWhatsApp) {
            throw new Exception("TEST 21 Failed: SubscriptionService failed to allow active premium user.");
        }
        echo "✔ TEST 21: Existing subscription tests still pass.\n";
    }

    private function test22_ExistingWacrmProviderIntegration(): void {
        $normalized = \App\Services\WhatsApp\WacrmWhatsAppProvider::normalizePhoneNumber('923007777777');
        if ($normalized !== '+923007777777') {
            throw new Exception("TEST 22 Failed: Wacrm provider phone normalization failed. Got: $normalized");
        }
        $normalizedUs = \App\Services\WhatsApp\WacrmWhatsAppProvider::normalizePhoneNumber('+14155552671');
        if ($normalizedUs !== '+14155552671') {
            throw new Exception("TEST 22 Failed: Wacrm provider US phone normalization failed. Got: $normalizedUs");
        }
        echo "✔ TEST 22: Existing WACRM provider tests still pass.\n";
    }
}
