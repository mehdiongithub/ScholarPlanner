<?php

if (!defined('TESTING_MODE')) {
    define('TESTING_MODE', true);
}
if (!defined('BYPASS_BATCH_CUTOFF')) {
    define('BYPASS_BATCH_CUTOFF', true);
}

use App\Services\Database;
use App\Services\Auth;
use App\Services\NotificationSchedulerService;
use App\Services\NotificationService;
use App\Services\NotificationQueueService;
use App\Services\ScholarshipMatchingService;
use App\Services\NotificationTypes;
use App\Services\SubscriptionService;
use App\Helpers\Security;

class ProductionCronAutomationTest {
    private PDO $db;
    private NotificationSchedulerService $scheduler;
    private int $userId;
    private int $schId1;
    private int $schId2;
    private int $premiumPlanId;
    private array $originalSettings;

    public function __construct() {
        $this->db = Database::connection();
        $this->scheduler = new NotificationSchedulerService($this->db);
    }

    public function run(): void {
        echo "=================================================================\n";
        echo " RUNNING PRODUCTION CRON AUTOMATION TEST SUITE\n";
        echo "=================================================================\n";

        $this->backupOriginalSettings();
        $this->cleanTestData();
        $this->setupTestData();

        try {
            // Section 1: Admin Configuration & Server-side Validation
            $this->test1_AdminConfigureMatchingSchedule();
            $this->test2_AdminConfigureDeadlineScheduleIndependently();
            $this->test3_ValidationRejectsInvalidTimeAndDays();
            $this->test4_ValidationRejectsInvalidTimezoneAndBatch();

            // Section 2: Schedule Evaluation & Timing Logic (Minute Precision)
            $this->test5_EvaluationBeforeSendTimeDoesNotTrigger();
            $this->test6_EvaluationAtExactSendTimeTriggers();
            $this->test7_EvaluationAfterSendTimeDoesNotTrigger();
            $this->test8_IdempotencyPreventsDuplicateExecutionInSameMinuteSlot();

            // Section 3: Allowed Days & Sunday Quiet Rule
            $this->test9_DisallowedDayBlocksExecution();
            $this->test10_SundayQuietRuleRespectedForMatchingAndDeadlines();

            // Section 4: Dynamic Admin Schedule Modifications
            $this->test11_ChangingScheduleTimeDynamicallyAdjustsDueEvaluation();
            $this->test12_ChangingAllowedDaysDynamicallyAdjustsDueEvaluation();
            $this->test13_ChangingTimezoneCorrectlyEvaluatesLocalWallClock();
            $this->test14_DisablingSchedulerStopsExecutionInstantly();

            // Section 5: Concurrency, Mutex Locking & Stale Recovery
            $this->test15_DatabaseMutexAdvisoryLockPreventsConcurrentRuns();
            $this->test16_StaleProcessingRecordsRecoveredSafely();

            // Section 6: Business Rules & WhatsApp Multi-Match Digest Grouping
            $this->test17_MultiMatchDailyDigestGroupingPreserved();
            $this->test18_OneWhatsAppPerUserPerDayLimitPreserved();
            $this->test19_MatchingJobExecutionDispatchesQueueWorker();

            // Section 7: 24-Hour Master Cron Schedule Simulation
            $this->test20_Full24HourTimelineSimulation();

            echo "\n=================================================================\n";
            echo " ✔ ALL 20 PRODUCTION CRON AUTOMATION TESTS PASSED CLEANLY!\n";
            echo "=================================================================\n\n";
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
                'matching_scheduler_enabled' => $this->originalSettings['matching_scheduler_enabled'] ? '1' : '0',
                'matching_send_time' => $this->originalSettings['matching_send_time'] ?? '08:00',
                'matching_timezone' => $this->originalSettings['matching_timezone'] ?? 'Asia/Karachi',
                'matching_allowed_days' => $this->originalSettings['matching_allowed_days'] ?? ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
                'deadline_scheduler_enabled' => $this->originalSettings['deadline_scheduler_enabled'] ? '1' : '0',
                'deadline_send_time' => $this->originalSettings['deadline_send_time'] ?? '09:00',
                'deadline_timezone' => $this->originalSettings['deadline_timezone'] ?? 'Asia/Karachi',
                'deadline_allowed_days' => $this->originalSettings['deadline_allowed_days'] ?? ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
                'whatsapp_batch_size' => $this->originalSettings['whatsapp_batch_size'] ?? 50
            ]);
        }
    }

    private function cleanTestData(): void {
        $this->db->exec("DELETE FROM notification_logs WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'cron-auto-%')");
        $this->db->exec("DELETE FROM notification_preferences WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'cron-auto-%')");
        $this->db->exec("DELETE FROM subscriptions WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'cron-auto-%')");
        $this->db->exec("DELETE FROM education_records WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'cron-auto-%')");
        $this->db->exec("DELETE FROM student_profiles WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'cron-auto-%')");
        $this->db->exec("DELETE FROM user_preferences WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'cron-auto-%')");
        $this->db->exec("DELETE FROM users WHERE email LIKE 'cron-auto-%'");
        $this->db->exec("DELETE FROM scholarships WHERE slug LIKE 'cron-auto-%'");
    }

    private function setupTestData(): void {
        $roleId = $this->db->query("SELECT id FROM roles WHERE name = 'visitor' LIMIT 1")->fetchColumn();
        $this->premiumPlanId = $this->db->query("SELECT id FROM subscription_plans WHERE slug = 'premium-monthly' LIMIT 1")->fetchColumn();

        // Create test user
        $stmtUser = $this->db->prepare("
            INSERT INTO users (first_name, last_name, email, phone, whatsapp_phone, password_hash, role_id, status, email_opt_in, whatsapp_opt_in, created_at)
            VALUES ('CronAuto', 'Tester', 'cron-auto-user@example.com', '+923008888888', '+923008888888', 'hash', :role_id, 'active', 1, 1, NOW())
        ");
        $stmtUser->execute(['role_id' => $roleId]);
        $this->userId = (int)$this->db->lastInsertId();

        // Profile & Education
        $this->db->exec("INSERT INTO student_profiles (user_id, gender, date_of_birth, nationality_country_id, residence_country_id) VALUES ({$this->userId}, 'male', '2000-01-01', 1, 1)");
        $this->db->exec("INSERT INTO education_records (user_id, institution_name, degree_level, degree_title, field_of_study, cgpa, cgpa_scale, percentage, is_current) VALUES ({$this->userId}, 'Test University', 'Bachelor', 'Bachelor of Science', 'Computer Science', 3.85, 4.00, 95.00, 1)");
        $this->db->exec("INSERT INTO user_preferences (user_id, funding_preferences, preferred_channel, allow_multi_channel) VALUES ({$this->userId}, 'Fully Funded', 'whatsapp', 1)");
        $this->db->exec("INSERT INTO notification_preferences (user_id, notification_type, whatsapp_enabled) VALUES ({$this->userId}, 'whatsapp_alerts', 1)");
        $this->db->exec("INSERT INTO notification_preferences (user_id, notification_type, whatsapp_enabled) VALUES ({$this->userId}, 'matching_scholarship_alerts', 1)");
        $this->db->exec("INSERT INTO notification_preferences (user_id, notification_type, whatsapp_enabled) VALUES ({$this->userId}, 'deadline_reminders', 1)");

        // Active premium subscription
        $this->db->exec("INSERT INTO subscriptions (user_id, plan_id, status, starts_at, ends_at) VALUES ({$this->userId}, {$this->premiumPlanId}, 'active', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY))");

        // Create 2 test scholarships
        $stmtSch1 = $this->db->prepare("
            INSERT INTO scholarships (title, slug, status, verification_status, provider_name, country_id, funding_type, application_deadline, description, created_at)
            VALUES ('Cron Auto Match 1', 'cron-auto-match-1', 'published', 'verified', 'Global Fund', 1, 'Fully Funded', DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'Desc 1', NOW())
        ");
        $stmtSch1->execute();
        $this->schId1 = (int)$this->db->lastInsertId();
        $this->db->exec("INSERT INTO scholarship_degree_levels (scholarship_id, degree_level) VALUES ({$this->schId1}, 'Bachelor')");
        $this->db->exec("INSERT INTO scholarship_eligible_nationalities (scholarship_id, country_id) VALUES ({$this->schId1}, 1)");
        $this->db->exec("INSERT INTO scholarship_eligibility_rules (scholarship_id, minimum_cgpa, cgpa_scale) VALUES ({$this->schId1}, 3.00, 4.00)");

        $stmtSch2 = $this->db->prepare("
            INSERT INTO scholarships (title, slug, status, verification_status, provider_name, country_id, funding_type, application_deadline, description, created_at)
            VALUES ('Cron Auto Match 2', 'cron-auto-match-2', 'published', 'verified', 'Tech Trust', 1, 'Fully Funded', DATE_ADD(CURDATE(), INTERVAL 45 DAY), 'Desc 2', NOW())
        ");
        $stmtSch2->execute();
        $this->schId2 = (int)$this->db->lastInsertId();
        $this->db->exec("INSERT INTO scholarship_degree_levels (scholarship_id, degree_level) VALUES ({$this->schId2}, 'Bachelor')");
        $this->db->exec("INSERT INTO scholarship_eligible_nationalities (scholarship_id, country_id) VALUES ({$this->schId2}, 1)");
        $this->db->exec("INSERT INTO scholarship_eligibility_rules (scholarship_id, minimum_cgpa, cgpa_scale) VALUES ({$this->schId2}, 3.00, 4.00)");
    }

    public function test1_AdminConfigureMatchingSchedule(): void {
        $config = [
            'whatsapp_notifications_enabled' => '1',
            'matching_scheduler_enabled' => '1',
            'matching_send_time' => '08:00',
            'matching_timezone' => 'Asia/Karachi',
            'matching_allowed_days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']
        ];
        $this->scheduler->updateSettings($config);
        $saved = $this->scheduler->getSettings();

        if (!$saved['whatsapp_notifications_enabled'] || !$saved['matching_scheduler_enabled'] || $saved['matching_send_time'] !== '08:00') {
            throw new Exception("TEST 1 Failed: Matching schedule settings were not saved properly.");
        }
        if ($saved['matching_allowed_days'] !== ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']) {
            throw new Exception("TEST 1 Failed: Matching allowed days mismatch.");
        }
        echo "  ✔ Test 1: Admin can configure Matching Schedule (08:00, Mon-Sat, Asia/Karachi).\n";
    }

    public function test2_AdminConfigureDeadlineScheduleIndependently(): void {
        $config = [
            'whatsapp_notifications_enabled' => '1',
            'deadline_scheduler_enabled' => '1',
            'deadline_send_time' => '09:00',
            'deadline_timezone' => 'Asia/Karachi',
            'deadline_allowed_days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']
        ];
        $this->scheduler->updateSettings($config);
        $saved = $this->scheduler->getSettings();

        if (!$saved['deadline_scheduler_enabled'] || $saved['deadline_send_time'] !== '09:00') {
            throw new Exception("TEST 2 Failed: Deadline schedule settings were not saved properly.");
        }
        if ($saved['deadline_allowed_days'] !== ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']) {
            throw new Exception("TEST 2 Failed: Deadline allowed days mismatch.");
        }
        echo "  ✔ Test 2: Admin can configure Deadline Schedule independently (09:00, Mon-Sat, Asia/Karachi).\n";
    }

    public function test3_ValidationRejectsInvalidTimeAndDays(): void {
        // Invalid Time
        $val1 = $this->scheduler->validateSettings([
            'matching_send_time' => '25:99',
            'matching_allowed_days' => ['Monday']
        ]);
        if ($val1['valid'] || empty($val1['errors']['matching_send_time'])) {
            throw new Exception("TEST 3 Failed: Invalid time '25:99' was not rejected.");
        }

        // Invalid Day
        $val2 = $this->scheduler->validateSettings([
            'matching_send_time' => '08:00',
            'matching_allowed_days' => ['Funday']
        ]);
        if ($val2['valid'] || empty($val2['errors']['matching_allowed_days'])) {
            throw new Exception("TEST 3 Failed: Invalid weekday 'Funday' was not rejected.");
        }

        // Empty Days
        $val3 = $this->scheduler->validateSettings([
            'matching_send_time' => '08:00',
            'matching_allowed_days' => []
        ]);
        if ($val3['valid'] || empty($val3['errors']['matching_allowed_days'])) {
            throw new Exception("TEST 3 Failed: Empty days list was not rejected.");
        }
        echo "  ✔ Test 3: Validation correctly rejects invalid times, invalid days, and empty day selections.\n";
    }

    public function test4_ValidationRejectsInvalidTimezoneAndBatch(): void {
        // Invalid Timezone
        $val1 = $this->scheduler->validateSettings([
            'matching_timezone' => 'Mars/Phobos'
        ]);
        if ($val1['valid'] || empty($val1['errors']['matching_timezone'])) {
            throw new Exception("TEST 4 Failed: Invalid timezone 'Mars/Phobos' was not rejected.");
        }

        // Out of bounds batch size
        $val2 = $this->scheduler->validateSettings([
            'whatsapp_batch_size' => 1000
        ]);
        if ($val2['valid'] || empty($val2['errors']['whatsapp_batch_size'])) {
            throw new Exception("TEST 4 Failed: Batch size 1000 was not rejected.");
        }
        echo "  ✔ Test 4: Validation correctly rejects invalid timezones and out-of-bounds batch sizes.\n";
    }

    public function test5_EvaluationBeforeSendTimeDoesNotTrigger(): void {
        $this->scheduler->updateSettings([
            'whatsapp_notifications_enabled' => '1',
            'matching_scheduler_enabled' => '1',
            'matching_send_time' => '08:00',
            'matching_timezone' => 'Asia/Karachi',
            'matching_allowed_days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']
        ]);

        // Simulated time: Monday 07:59:00 Asia/Karachi
        $timeBefore = new DateTime('2026-09-07 07:59:00', new DateTimeZone('Asia/Karachi'));
        $eval = $this->scheduler->isMatchingScheduleDue($timeBefore);

        if ($eval['due'] !== false || $eval['reason'] !== 'time_not_matched') {
            throw new Exception("TEST 5 Failed: Evaluator triggered before scheduled time at 07:59.");
        }
        echo "  ✔ Test 5: At 07:59 (1 minute before 08:00 schedule), matching schedule evaluates to NOT DUE.\n";
    }

    public function test6_EvaluationAtExactSendTimeTriggers(): void {
        // Simulated time: Monday 08:00:00 Asia/Karachi
        $timeExact = new DateTime('2026-09-07 08:00:00', new DateTimeZone('Asia/Karachi'));
        $eval = $this->scheduler->isMatchingScheduleDue($timeExact);

        if ($eval['due'] !== true || $eval['slot'] !== 'matching:2026-09-07:08:00:Asia/Karachi') {
            throw new Exception("TEST 6 Failed: Evaluator failed to trigger at exact time 08:00: " . json_encode($eval));
        }
        echo "  ✔ Test 6: At exact 08:00 on Monday, matching schedule evaluates to DUE (READY).\n";
    }

    public function test7_EvaluationAfterSendTimeDoesNotTrigger(): void {
        // Simulated time: Monday 08:01:00 Asia/Karachi
        $timeAfter = new DateTime('2026-09-07 08:01:00', new DateTimeZone('Asia/Karachi'));
        $eval = $this->scheduler->isMatchingScheduleDue($timeAfter);

        if ($eval['due'] !== false || $eval['reason'] !== 'time_not_matched') {
            throw new Exception("TEST 7 Failed: Evaluator triggered after scheduled time at 08:01.");
        }
        echo "  ✔ Test 7: At 08:01 (1 minute after 08:00 schedule), matching schedule evaluates to NOT DUE.\n";
    }

    public function test8_IdempotencyPreventsDuplicateExecutionInSameMinuteSlot(): void {
        $slotKey = 'matching:2026-09-07:08:00:Asia/Karachi';
        // Record execution of this slot
        $this->scheduler->recordJobExecution('matching', 'success', $slotKey);

        $timeExact = new DateTime('2026-09-07 08:00:30', new DateTimeZone('Asia/Karachi'));
        $eval = $this->scheduler->isMatchingScheduleDue($timeExact);

        if ($eval['due'] !== false || $eval['reason'] !== 'already_executed_for_slot') {
            throw new Exception("TEST 8 Failed: Idempotency slot guard did not prevent re-execution in same minute.");
        }

        // Clear runtime marker
        $this->db->exec("DELETE FROM settings WHERE `key` LIKE 'matching_last_run_%'");
        echo "  ✔ Test 8: Idempotency slot key prevents duplicate execution within the same minute slot.\n";
    }

    public function test9_DisallowedDayBlocksExecution(): void {
        // Allowed days: Monday, Wednesday, Friday only
        $this->scheduler->updateSettings([
            'matching_allowed_days' => ['Monday', 'Wednesday', 'Friday'],
            'matching_send_time' => '08:00',
            'matching_timezone' => 'Asia/Karachi'
        ]);

        // Tuesday at 08:00 (2026-09-08 is Tuesday)
        $tuesdayTime = new DateTime('2026-09-08 08:00:00', new DateTimeZone('Asia/Karachi'));
        $eval = $this->scheduler->isMatchingScheduleDue($tuesdayTime);

        if ($eval['due'] !== false || $eval['reason'] !== 'day_not_allowed') {
            throw new Exception("TEST 9 Failed: Execution on unconfigured Tuesday was not blocked: " . json_encode($eval));
        }
        echo "  ✔ Test 9: Execution on unselected days (Tuesday when Mon/Wed/Fri configured) is strictly blocked.\n";
    }

    public function test10_SundayQuietRuleRespectedForMatchingAndDeadlines(): void {
        // Configure Mon-Sat allowed, Sunday excluded
        $this->scheduler->updateSettings([
            'matching_allowed_days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
            'matching_send_time' => '08:00',
            'deadline_allowed_days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
            'deadline_send_time' => '09:00',
            'matching_timezone' => 'Asia/Karachi',
            'deadline_timezone' => 'Asia/Karachi'
        ]);

        // Sunday 2026-09-13
        $sunday0800 = new DateTime('2026-09-13 08:00:00', new DateTimeZone('Asia/Karachi'));
        $sunday0900 = new DateTime('2026-09-13 09:00:00', new DateTimeZone('Asia/Karachi'));

        $evalMatch = $this->scheduler->isMatchingScheduleDue($sunday0800);
        $evalDead = $this->scheduler->isDeadlineScheduleDue($sunday0900);

        if ($evalMatch['due'] !== false || $evalMatch['reason'] !== 'day_not_allowed') {
            throw new Exception("TEST 10 Failed: Sunday quiet rule failed for matching job.");
        }
        if ($evalDead['due'] !== false || $evalDead['reason'] !== 'day_not_allowed') {
            throw new Exception("TEST 10 Failed: Sunday quiet rule failed for deadline reminders.");
        }
        echo "  ✔ Test 10: Sunday Quiet Rule successfully respected for both Matching and Deadline schedules.\n";
    }

    public function test11_ChangingScheduleTimeDynamicallyAdjustsDueEvaluation(): void {
        // Change matching send time to 10:30
        $this->scheduler->updateSettings([
            'matching_send_time' => '10:30',
            'matching_allowed_days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']
        ]);

        $time0800 = new DateTime('2026-09-07 08:00:00', new DateTimeZone('Asia/Karachi'));
        $time1030 = new DateTime('2026-09-07 10:30:00', new DateTimeZone('Asia/Karachi'));

        $evalOld = $this->scheduler->isMatchingScheduleDue($time0800);
        $evalNew = $this->scheduler->isMatchingScheduleDue($time1030);

        if ($evalOld['due'] !== false) {
            throw new Exception("TEST 11 Failed: Old time 08:00 still triggered after updating to 10:30.");
        }
        if ($evalNew['due'] !== true) {
            throw new Exception("TEST 11 Failed: New time 10:30 did not trigger.");
        }
        echo "  ✔ Test 11: Dynamically changing schedule time in Admin Panel takes effect immediately without cron restarts.\n";
    }

    public function test12_ChangingAllowedDaysDynamicallyAdjustsDueEvaluation(): void {
        // Add Sunday to allowed days
        $this->scheduler->updateSettings([
            'matching_allowed_days' => ['Monday', 'Sunday'],
            'matching_send_time' => '08:00'
        ]);

        $sundayTime = new DateTime('2026-09-13 08:00:00', new DateTimeZone('Asia/Karachi'));
        $eval = $this->scheduler->isMatchingScheduleDue($sundayTime);

        if ($eval['due'] !== true) {
            throw new Exception("TEST 12 Failed: Sunday was added to allowed days but failed to evaluate to due.");
        }
        echo "  ✔ Test 12: Dynamically adding/removing days in Admin Panel updates evaluation instantly.\n";
    }

    public function test13_ChangingTimezoneCorrectlyEvaluatesLocalWallClock(): void {
        // Set timezone to America/New_York (UTC-4 in Sep)
        $this->scheduler->updateSettings([
            'matching_timezone' => 'America/New_York',
            'matching_send_time' => '08:00',
            'matching_allowed_days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']
        ]);

        // When it is 08:00 AM in New York on Monday Sep 7, 2026 (12:00 UTC / 17:00 Karachi)
        $nyTime = new DateTime('2026-09-07 08:00:00', new DateTimeZone('America/New_York'));
        $eval = $this->scheduler->isMatchingScheduleDue($nyTime);

        if ($eval['due'] !== true || $eval['slot'] !== 'matching:2026-09-07:08:00:America/New_York') {
            throw new Exception("TEST 13 Failed: Timezone America/New_York was not evaluated correctly: " . json_encode($eval));
        }
        echo "  ✔ Test 13: Timezone adjustments correctly evaluate wall-clock time across any global timezone.\n";
    }

    public function test14_DisablingSchedulerStopsExecutionInstantly(): void {
        // Disable Matching Schedule
        $this->scheduler->updateSettings([
            'matching_scheduler_enabled' => '0',
            'matching_send_time' => '08:00',
            'matching_allowed_days' => ['Monday']
        ]);

        $mondayTime = new DateTime('2026-09-07 08:00:00', new DateTimeZone('Asia/Karachi'));
        $eval = $this->scheduler->isMatchingScheduleDue($mondayTime);

        if ($eval['due'] !== false || $eval['reason'] !== 'matching_scheduler_disabled') {
            throw new Exception("TEST 14 Failed: Disabled scheduler still evaluated as due.");
        }
        echo "  ✔ Test 14: Disabling schedule toggle in Admin Panel halts execution immediately.\n";
    }

    public function test15_DatabaseMutexAdvisoryLockPreventsConcurrentRuns(): void {
        $lock1 = $this->scheduler->acquireLock('test_cron_mutex_lock', 0);
        if (!$lock1) {
            throw new Exception("TEST 15 Failed: Failed to acquire primary database advisory lock.");
        }

        // Secondary connection attempt must fail immediately because it is a separate MySQL session
        $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
        $port = $_ENV['DB_PORT'] ?? '3306';
        $dbName = $_ENV['DB_DATABASE'] ?? 'scholarship';
        $user = $_ENV['DB_USERNAME'] ?? 'root';
        $pass = $_ENV['DB_PASSWORD'] ?? '';

        $db2 = new PDO("mysql:host={$host};port={$port};dbname={$dbName}", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $scheduler2 = new NotificationSchedulerService($db2);
        $lock2 = $scheduler2->acquireLock('test_cron_mutex_lock', 0);

        if ($lock2) {
            $scheduler2->releaseLock('test_cron_mutex_lock');
            $this->scheduler->releaseLock('test_cron_mutex_lock');
            throw new Exception("TEST 15 Failed: Secondary connection acquired an already locked mutex!");
        }

        $this->scheduler->releaseLock('test_cron_mutex_lock');
        echo "  ✔ Test 15: MySQL advisory mutex locking (GET_LOCK) strictly prevents concurrent overlapping cron ticks.\n";
    }

    public function test16_StaleProcessingRecordsRecoveredSafely(): void {
        // Insert fake stale processing notification
        $this->db->exec("
            INSERT INTO notification_logs (user_id, scholarship_id, channel, notification_type, recipient, status, attempts, error_message, processing_started_at, created_at, updated_at)
            VALUES ({$this->userId}, {$this->schId1}, 'whatsapp', 'DAILY_MATCH_DIGEST', '+923008888888', 'processing', 1, 'In-flight', DATE_SUB(NOW(), INTERVAL 30 MINUTE), DATE_SUB(NOW(), INTERVAL 30 MINUTE), DATE_SUB(NOW(), INTERVAL 30 MINUTE))
        ");
        $logId = (int)$this->db->lastInsertId();

        $res = $this->scheduler->recoverStaleProcessing(15);
        if (($res['recovered'] ?? 0) < 1) {
            throw new Exception("TEST 16 Failed: Stale processing record was not recovered.");
        }

        $stmt = $this->db->prepare("SELECT status, processing_started_at FROM notification_logs WHERE id = :id");
        $stmt->execute(['id' => $logId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row['status'] !== 'pending' || $row['processing_started_at'] !== null) {
            throw new Exception("TEST 16 Failed: Status was not reset to pending with cleared processing_started_at.");
        }

        $this->db->exec("DELETE FROM notification_logs WHERE id = {$logId}");
        echo "  ✔ Test 16: Stale processing notifications (>15 min) are automatically recovered back to pending.\n";
    }

    public function test17_MultiMatchDailyDigestGroupingPreserved(): void {
        // Clean existing logs
        $this->db->exec("DELETE FROM notification_logs WHERE user_id = {$this->userId}");

        // Re-enable scheduler
        $this->scheduler->updateSettings([
            'whatsapp_notifications_enabled' => '1',
            'matching_scheduler_enabled' => '1',
            'matching_send_time' => '08:00',
            'matching_timezone' => 'Asia/Karachi',
            'matching_allowed_days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']
        ]);

        // Run matching job for Monday
        $res = $this->scheduler->runMatchingJob('2026-09-07', false);

        // Verify that only 1 consolidated WhatsApp notification was created for the user with both matched scholarships
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM notification_logs WHERE user_id = :uid AND channel = 'whatsapp' AND notification_type = 'DAILY_MATCH_DIGEST'");
        $stmt->execute(['uid' => $this->userId]);
        $waCount = (int)$stmt->fetchColumn();

        if ($waCount !== 1) {
            throw new Exception("TEST 17 Failed: Expected exactly 1 consolidated WhatsApp notification, got {$waCount}.");
        }

        $stmt = $this->db->prepare("SELECT payload FROM notification_logs WHERE user_id = :uid AND channel = 'whatsapp' AND notification_type = 'DAILY_MATCH_DIGEST' LIMIT 1");
        $stmt->execute(['uid' => $this->userId]);
        $rawPayload = $stmt->fetchColumn();

        $payload = json_decode($rawPayload, true);
        $matches = $payload['matches'] ?? [];
        if (count($matches) < 2) {
            throw new Exception("TEST 17 Failed: Multi-match digest did not include all matches (count: " . count($matches) . ").");
        }
        echo "  ✔ Test 17: Multi-match grouping generates 1 daily digest WhatsApp notification for all matches.\n";
    }

    public function test18_OneWhatsAppPerUserPerDayLimitPreserved(): void {
        // Attempting a second matching job on the same calendar day must not create a 2nd WhatsApp notification
        $res2 = $this->scheduler->runMatchingJob('2026-09-07', false);

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM notification_logs WHERE user_id = :uid AND channel = 'whatsapp' AND notification_type = 'DAILY_MATCH_DIGEST'");
        $stmt->execute(['uid' => $this->userId]);
        $waCount = (int)$stmt->fetchColumn();

        if ($waCount !== 1) {
            throw new Exception("TEST 18 Failed: Daily rate limit breached! Expected 1 WhatsApp message, got {$waCount}.");
        }
        echo "  ✔ Test 18: 1 WhatsApp message per user per calendar day rate limit is strictly enforced.\n";
    }

    public function test19_MatchingJobExecutionDispatchesQueueWorker(): void {
        // Set available_at to past so queue processor picks it up
        $this->db->exec("UPDATE notification_logs SET available_at = DATE_SUB(NOW(), INTERVAL 1 MINUTE) WHERE user_id = {$this->userId}");

        $queueService = new NotificationQueueService();
        $processed = $queueService->processQueue(10);

        $stmt = $this->db->prepare("SELECT status FROM notification_logs WHERE user_id = :uid AND channel = 'whatsapp' AND notification_type = 'DAILY_MATCH_DIGEST' LIMIT 1");
        $stmt->execute(['uid' => $this->userId]);
        $status = $stmt->fetchColumn();

        if ($status !== 'sent') {
            throw new Exception("TEST 19 Failed: Notification was not dispatched to 'sent' status (actual: {$status}).");
        }
        echo "  ✔ Test 19: Notification queue worker cleanly dispatches queued alerts to Meta/WACRM provider.\n";
    }

    public function test20_Full24HourTimelineSimulation(): void {
        // Configure standard production schedule:
        // Matching: 08:00 Asia/Karachi, Mon-Sat
        // Deadline: 09:00 Asia/Karachi, Mon-Sat
        $this->scheduler->updateSettings([
            'whatsapp_notifications_enabled' => '1',
            'matching_scheduler_enabled' => '1',
            'matching_send_time' => '08:00',
            'matching_timezone' => 'Asia/Karachi',
            'matching_allowed_days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
            'deadline_scheduler_enabled' => '1',
            'deadline_send_time' => '09:00',
            'deadline_timezone' => 'Asia/Karachi',
            'deadline_allowed_days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']
        ]);

        $testTimeline = [
            ['time' => '2026-09-07 07:59:00', 'day' => 'Monday', 'expect_match' => false, 'expect_dead' => false],
            ['time' => '2026-09-07 08:00:00', 'day' => 'Monday', 'expect_match' => true,  'expect_dead' => false],
            ['time' => '2026-09-07 08:01:00', 'day' => 'Monday', 'expect_match' => false, 'expect_dead' => false],
            ['time' => '2026-09-07 08:59:00', 'day' => 'Monday', 'expect_match' => false, 'expect_dead' => false],
            ['time' => '2026-09-07 09:00:00', 'day' => 'Monday', 'expect_match' => false, 'expect_dead' => true],
            ['time' => '2026-09-07 09:01:00', 'day' => 'Monday', 'expect_match' => false, 'expect_dead' => false],
            ['time' => '2026-09-13 08:00:00', 'day' => 'Sunday', 'expect_match' => false, 'expect_dead' => false],
            ['time' => '2026-09-13 09:00:00', 'day' => 'Sunday', 'expect_match' => false, 'expect_dead' => false],
        ];

        foreach ($testTimeline as $step) {
            $dt = new DateTime($step['time'], new DateTimeZone('Asia/Karachi'));
            $matchEval = $this->scheduler->isMatchingScheduleDue($dt);
            $deadEval = $this->scheduler->isDeadlineScheduleDue($dt);

            if ($matchEval['due'] !== $step['expect_match']) {
                throw new Exception("TEST 20 Failed: Matching mismatch at {$step['time']} ({$step['day']}). Expected " . ($step['expect_match'] ? 'TRUE' : 'FALSE') . ", got " . ($matchEval['due'] ? 'TRUE' : 'FALSE'));
            }
            if ($deadEval['due'] !== $step['expect_dead']) {
                throw new Exception("TEST 20 Failed: Deadline mismatch at {$step['time']} ({$step['day']}). Expected " . ($step['expect_dead'] ? 'TRUE' : 'FALSE') . ", got " . ($deadEval['due'] ? 'TRUE' : 'FALSE'));
            }
        }
        echo "  ✔ Test 20: 24-hour master cron simulation validated across all critical minute ticks and Sunday quiet hours.\n";
    }
}
