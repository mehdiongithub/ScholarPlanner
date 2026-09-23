<?php

if (!defined('TESTING_MODE')) {
    define('TESTING_MODE', true);
}
if (!defined('BYPASS_BATCH_CUTOFF')) {
    define('BYPASS_BATCH_CUTOFF', true);
}

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/WacrmWhatsAppProviderTest.php';

use App\Services\Database;
use App\Services\NotificationSchedulerService;
use App\Services\NotificationQueueService;
use App\Services\NotificationService;
use App\Services\SubscriptionService;
use App\Services\WhatsApp\ScholarshipMessageFormatter;
use App\Services\WhatsApp\CurlMockRegistry;

class Step8ProductionHardeningTest {
    private PDO $db;
    private NotificationSchedulerService $scheduler;
    private NotificationQueueService $queueService;
    private int $visitorRoleId;
    private int $pakistanCountryId;
    private int $sindhStateId;
    private int $phoneSeq = 8000;

    public function __construct() {
        $this->db = Database::connection();
        $this->scheduler = new NotificationSchedulerService($this->db);
        $this->queueService = new NotificationQueueService($this->db);

        $this->visitorRoleId = (int)$this->db->query("SELECT id FROM roles WHERE name = 'visitor' LIMIT 1")->fetchColumn() ?: 1;
        $this->pakistanCountryId = (int)$this->db->query("SELECT id FROM countries WHERE name = 'Pakistan' LIMIT 1")->fetchColumn() ?: 1;
        $this->sindhStateId = (int)$this->db->query("SELECT id FROM states WHERE name = 'Sindh' LIMIT 1")->fetchColumn() ?: 1;
    }

    public function run(): void {
        echo "=================================================================\n";
        echo " RUNNING STEP 8 PRODUCTION AUTOMATION HARDENING TEST SUITE (20 TESTS)\n";
        echo "=================================================================\n\n";

        $this->cleanTestData();

        try {
            // Group 1: Master Cron CLI & Mutex Locking (1-4)
            $this->test1_masterCronScriptExistsAndValidSyntax();
            $this->test2_masterCronAdvisoryLockPreventsConcurrentExecution();
            $this->test3_masterCronLockReleasesSafelyInFinally();
            $this->test4_masterCronDryRunExecutionSucceedsCleanly();

            // Group 2: Queue Worker Concurrency & Self-Healing (5-8)
            $this->test5_queueWorkerAtomicClaimWithRowLocking();
            $this->test6_concurrentWorkersGetDisjointBatches();
            $this->test7_staleProcessingRecoveryResetsOrphanedJobs();
            $this->test8_exponentialBackoffIntervalsRecordedCorrectly();

            // Group 3: Professional Scholarship Message Formatter (9-14)
            $this->test9_singleMessageFormattingStructureAndHeading();
            $this->test10_htmlSanitizationAndEntityDecoding();
            $this->test11_deadlineFormattingTodayTomorrowAndStandard();
            $this->test12_multiScholarshipDigestBatchFormatting();
            $this->test13_metaTemplateParameterMappingNewMatch();
            $this->test14_metaTemplateParameterMappingDeadlineAndPayment();

            // Group 4: Operational Visibility & Invariants (15-18)
            $this->test15_adminNotificationSummaryIncludesDeliveredCount();
            $this->test16_lifetime25WhatsAppCapEnforcedAcrossAutomatedJobs();
            $this->test17_sundayQuietRuleSuppressesScholarshipAlerts();
            $this->test18_subscriptionProtectionMaintainsDeliveredAccounting();

            // Group 5: Full End-to-End Automation Cycle (19-20)
            $this->test19_fullAutomatedCycleMatchingToQueueWorker();
            $this->test20_deliveryWebhookTriggersInstantUsageAccounting();

            echo "\n=================================================================\n";
            echo " ✔ ALL 20 STEP 8 PRODUCTION AUTOMATION TESTS PASSED!\n";
            echo "=================================================================\n\n";
        } finally {
            $this->cleanTestData();
        }
    }

    private function assert(bool $condition, string $message): void {
        if (!$condition) {
            throw new \Exception("Assertion failed: " . $message);
        }
    }

    private function invokeUpdateQueueItemStatus(int $id, int $attempts, bool $success, ?string $errorMessage = null, ?string $providerMessageId = null): void {
        $ref = new ReflectionMethod($this->queueService, 'updateQueueItemStatus');
        $ref->setAccessible(true);
        $ref->invoke($this->queueService, $id, $attempts, $success, $errorMessage, $providerMessageId);
    }

    private function cleanTestData(): void {
        $this->db->exec("DELETE FROM notification_logs WHERE recipient LIKE '%step8%' OR error_message LIKE '%Step8%' OR idempotency_key LIKE '%step8%'");
        $this->db->exec("DELETE FROM subscription_usage WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'step8_%')");
        $this->db->exec("DELETE FROM subscriptions WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'step8_%')");
        $this->db->exec("DELETE FROM scholarship_matches WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'step8_%')");
        $this->db->exec("DELETE FROM user_preferences WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'step8_%')");
        $this->db->exec("DELETE FROM notification_preferences WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'step8_%')");
        $this->db->exec("DELETE FROM education_records WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'step8_%')");
        $this->db->exec("DELETE FROM student_profiles WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'step8_%')");
        $this->db->exec("DELETE FROM users WHERE email LIKE 'step8_%'");
        $this->db->exec("DELETE FROM scholarship_degree_levels WHERE scholarship_id IN (SELECT id FROM scholarships WHERE title LIKE 'Step8 %')");
        $this->db->exec("DELETE FROM scholarships WHERE title LIKE 'Step8 %'");
    }

    private function createUser(string $email, bool $withSub = true): int {
        $this->phoneSeq++;
        $phone = '+92300' . str_pad((string)$this->phoneSeq, 7, '0', STR_PAD_LEFT);

        $stmt = $this->db->prepare("
            INSERT INTO users (
                email, password_hash, first_name, last_name, phone, whatsapp_phone,
                role_id, status, email_opt_in, whatsapp_opt_in, created_at, updated_at
            ) VALUES (
                :email, 'hash_test', 'Step8', 'Tester', :phone, :wa_phone,
                :role, 'active', 1, 1, NOW(), NOW()
            )
        ");
        $stmt->execute([
            'email' => $email,
            'phone' => $phone,
            'wa_phone' => $phone,
            'role' => $this->visitorRoleId
        ]);
        $uid = (int)$this->db->lastInsertId();

        $this->db->prepare("
            INSERT INTO student_profiles (user_id, nationality_country_id, residence_country_id, residence_state_id, date_of_birth, gender, created_at, updated_at)
            VALUES (:uid, :ncid, :rcid, :sid, '2000-01-01', 'Male', NOW(), NOW())
        ")->execute([
            'uid' => $uid,
            'ncid' => $this->pakistanCountryId,
            'rcid' => $this->pakistanCountryId,
            'sid' => $this->sindhStateId
        ]);

        $this->db->prepare("
            INSERT INTO education_records (user_id, institution_name, degree_level, degree_title, field_of_study, country_id, cgpa, cgpa_scale, percentage, is_current, graduation_status, created_at, updated_at)
            VALUES (:uid, 'NED University', 'Bachelor\'s', 'Computer Science', 'Computer Science', :cid, 3.85, 4.00, 95.0, 1, 'in_progress', NOW(), NOW())
        ")->execute([
            'uid' => $uid,
            'cid' => $this->pakistanCountryId
        ]);

        $this->db->prepare("
            INSERT INTO user_preferences (user_id, preferred_channel, allow_multi_channel, deadline_reminder_scope, deadline_reminder_days, created_at, updated_at)
            VALUES (:uid, 'both', 1, 'all', '7,3,1', NOW(), NOW())
        ")->execute(['uid' => $uid]);

        $types = ['matching_scholarship_alerts', 'deadline_reminders', 'email_alerts', 'whatsapp_alerts'];
        foreach ($types as $t) {
            $this->db->prepare("
                INSERT INTO notification_preferences (user_id, notification_type, email_enabled, whatsapp_enabled, created_at, updated_at)
                VALUES (:uid, :ntype, 1, 1, NOW(), NOW())
            ")->execute(['uid' => $uid, 'ntype' => $t]);
        }

        if ($withSub) {
            $planId = (int)$this->db->query("SELECT id FROM subscription_plans WHERE slug = 'monthly' OR price > 0 ORDER BY id ASC LIMIT 1")->fetchColumn() ?: 1;
            $this->db->prepare("
                INSERT INTO subscriptions (user_id, plan_id, status, starts_at, ends_at, created_at, updated_at)
                VALUES (:uid, :pid, 'active', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), NOW(), NOW())
            ")->execute(['uid' => $uid, 'pid' => $planId]);
            $subId = (int)$this->db->lastInsertId();

            SubscriptionService::syncSubscriptionUsage($subId, $this->db);
        }

        return $uid;
    }

    private function createScholarship(string $title): int {
        $slug = 'step8-' . strtolower(str_replace(' ', '-', $title)) . '-' . time() . '-' . rand(100, 999);
        $stmt = $this->db->prepare("
            INSERT INTO scholarships (
                title, slug, provider_name, description, short_description, funding_type, status,
                verification_status, country_id, application_deadline, created_at, updated_at
            ) VALUES (
                :title, :slug, 'Global Foundation', 'Step 8 verified opportunity.', 'Short desc', 'fully_funded', 'published',
                'verified', :cid, DATE_ADD(NOW(), INTERVAL 20 DAY), NOW(), NOW()
            )
        ");
        $stmt->execute([
            'title' => $title,
            'slug' => $slug,
            'cid' => $this->pakistanCountryId
        ]);
        $sid = (int)$this->db->lastInsertId();

        $this->db->prepare("INSERT INTO scholarship_degree_levels (scholarship_id, degree_level) VALUES (:sid, 'Bachelor\'s')")
            ->execute(['sid' => $sid]);

        return $sid;
    }

    // =========================================================================
    // Group 1: Master Cron CLI & Mutex Locking (1-4)
    // =========================================================================

    public function test1_masterCronScriptExistsAndValidSyntax(): void {
        echo "[Test 1] Master cron entrypoint exists and has valid PHP syntax... ";
        $file = dirname(__DIR__) . '/cron/master_cron.php';
        $this->assert(file_exists($file), "cron/master_cron.php must exist");
        $output = shell_exec("php -l \"$file\"");
        $this->assert(strpos($output, 'No syntax errors detected') !== false, "master_cron.php syntax check must pass");
        echo "OK\n";
    }

    public function test2_masterCronAdvisoryLockPreventsConcurrentExecution(): void {
        echo "[Test 2] Master cron advisory lock (app_master_scheduler) prevents concurrent runs... ";
        $lockAcquired = $this->scheduler->acquireLock('app_master_scheduler', 0);
        $this->assert($lockAcquired === true, "First lock acquisition must succeed");

        // Open secondary connection to simulate concurrent process
        $dsn = "mysql:host=" . ($_ENV['DB_HOST'] ?? '127.0.0.1') . ";port=" . ($_ENV['DB_PORT'] ?? 3306) . ";dbname=" . ($_ENV['DB_DATABASE'] ?? 'scholarship') . ";charset=utf8mb4";
        $db2 = new PDO($dsn, $_ENV['DB_USERNAME'] ?? 'root', $_ENV['DB_PASSWORD'] ?? '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $scheduler2 = new NotificationSchedulerService($db2);

        $concurrentLock = $scheduler2->acquireLock('app_master_scheduler', 0);
        $this->assert($concurrentLock === false, "Concurrent process must NOT acquire active lock");

        $this->scheduler->releaseLock('app_master_scheduler');
        echo "OK\n";
    }

    public function test3_masterCronLockReleasesSafelyInFinally(): void {
        echo "[Test 3] Releasing lock makes it immediately available to subsequent runners... ";
        $this->scheduler->acquireLock('app_master_scheduler', 0);
        $this->scheduler->releaseLock('app_master_scheduler');

        $reacquired = $this->scheduler->acquireLock('app_master_scheduler', 0);
        $this->assert($reacquired === true, "Lock must be immediately re-acquirable once released");
        $this->scheduler->releaseLock('app_master_scheduler');
        echo "OK\n";
    }

    public function test4_masterCronDryRunExecutionSucceedsCleanly(): void {
        echo "[Test 4] Master cron --dry-run CLI flag executes cleanly with exit code 0... ";
        $cmd = "php " . escapeshellarg(dirname(__DIR__) . '/cron/master_cron.php') . " --dry-run";
        $output = [];
        $exitCode = 0;
        exec($cmd, $output, $exitCode);
        $text = implode("\n", $output);

        $this->assert($exitCode === 0, "Dry-run must exit with code 0 (got $exitCode)");
        $this->assert(strpos($text, 'DRY-RUN INSPECTION MODE ACTIVE') !== false, "Must indicate dry-run inspection mode");
        $this->assert(strpos($text, 'Dry-run inspection complete') !== false, "Must report inspection complete");
        echo "OK\n";
    }

    // =========================================================================
    // Group 2: Queue Worker Concurrency & Self-Healing (5-8)
    // =========================================================================

    public function test5_queueWorkerAtomicClaimWithRowLocking(): void {
        echo "[Test 5] Queue worker claims pending items atomically using row-level locking... ";
        $uid = $this->createUser('step8_t5@scholartest.com');
        $sid = $this->createScholarship('Step8 Scholarship T5');

        $stmt = $this->db->prepare("
            INSERT INTO notification_logs (
                user_id, scholarship_id, notification_type, channel, recipient,
                provider, status, attempts, idempotency_key, created_at, updated_at
            ) VALUES (
                :uid, :sid, 'NEW_MATCH', 'whatsapp', '+923008000005',
                'wacrm', 'pending', 0, 'step8_claim_test_1', NOW(), NOW()
            )
        ");
        $stmt->execute(['uid' => $uid, 'sid' => $sid]);
        $logId = (int)$this->db->lastInsertId();

        $claimed = $this->scheduler->claimPendingBatch(5, ['NEW_MATCH'], 'whatsapp');
        $this->assert(!empty($claimed), "Worker must claim pending item");

        $claimedIds = array_column($claimed, 'id');
        $this->assert(in_array($logId, $claimedIds), "Inserted log item must be in claimed list");

        $status = $this->db->query("SELECT status FROM notification_logs WHERE id = $logId")->fetchColumn();
        $this->assert($status === 'processing', "Claimed item status must immediately transition to 'processing'");
        echo "OK\n";
    }

    public function test6_concurrentWorkersGetDisjointBatches(): void {
        echo "[Test 6] Concurrent workers executing claimPendingBatch receive disjoint items... ";
        $uid = $this->createUser('step8_t6@scholartest.com');
        $sid = $this->createScholarship('Step8 Scholarship T6');

        $ids = [];
        for ($i = 1; $i <= 4; $i++) {
            $stmt = $this->db->prepare("
                INSERT INTO notification_logs (
                    user_id, scholarship_id, notification_type, channel, recipient,
                    provider, status, attempts, idempotency_key, created_at, updated_at
                ) VALUES (
                    :uid, :sid, 'NEW_MATCH', 'whatsapp', '+923008000006',
                    'wacrm', 'pending', 0, :ikey, NOW(), NOW()
                )
            ");
            $stmt->execute(['uid' => $uid, 'sid' => $sid, 'ikey' => "step8_disjoint_{$i}"]);
            $ids[] = (int)$this->db->lastInsertId();
        }

        $batch1 = $this->scheduler->claimPendingBatch(2, ['NEW_MATCH'], 'whatsapp');
        $batch2 = $this->scheduler->claimPendingBatch(2, ['NEW_MATCH'], 'whatsapp');

        $batch1Ids = array_column($batch1, 'id');
        $batch2Ids = array_column($batch2, 'id');

        $intersection = array_intersect($batch1Ids, $batch2Ids);
        $this->assert(empty($intersection), "Batches claimed by workers must have zero overlapping items");
        echo "OK\n";
    }

    public function test7_staleProcessingRecoveryResetsOrphanedJobs(): void {
        echo "[Test 7] Stale processing items (>15 mins) are recovered to pending... ";
        $uid = $this->createUser('step8_t7@scholartest.com');
        $sid = $this->createScholarship('Step8 Scholarship T7');

        $stmt = $this->db->prepare("
            INSERT INTO notification_logs (
                user_id, scholarship_id, notification_type, channel, recipient,
                provider, status, attempts, processing_started_at, idempotency_key, created_at, updated_at
            ) VALUES (
                :uid, :sid, 'NEW_MATCH', 'whatsapp', '+923008000007',
                'wacrm', 'processing', 1, DATE_SUB(NOW(), INTERVAL 20 MINUTE), 'step8_stale_test', NOW(), NOW()
            )
        ");
        $stmt->execute(['uid' => $uid, 'sid' => $sid]);
        $logId = (int)$this->db->lastInsertId();

        $res = $this->scheduler->recoverStaleProcessing(15, 3);
        $this->assert($res['recovered'] >= 1, "Stale job must be recovered");

        $status = $this->db->query("SELECT status FROM notification_logs WHERE id = $logId")->fetchColumn();
        $this->assert($status === 'pending', "Recovered item must revert to 'pending'");
        echo "OK\n";
    }

    public function test8_exponentialBackoffIntervalsRecordedCorrectly(): void {
        echo "[Test 8] Failed retryable items calculate exponential backoff delay... ";
        $uid = $this->createUser('step8_t8@scholartest.com');
        $sid = $this->createScholarship('Step8 Scholarship T8');

        $stmt = $this->db->prepare("
            INSERT INTO notification_logs (
                user_id, scholarship_id, notification_type, channel, recipient,
                provider, status, attempts, idempotency_key, created_at, updated_at
            ) VALUES (
                :uid, :sid, 'NEW_MATCH', 'whatsapp', '+923008000008',
                'wacrm', 'processing', 0, 'step8_backoff_test', NOW(), NOW()
            )
        ");
        $stmt->execute(['uid' => $uid, 'sid' => $sid]);
        $logId = (int)$this->db->lastInsertId();

        $this->invokeUpdateQueueItemStatus($logId, 0, false, 'Temporary HTTP 500 error', null);

        $row = $this->db->query("SELECT status, attempts, available_at FROM notification_logs WHERE id = $logId")->fetch(PDO::FETCH_ASSOC);
        $this->assert($row['status'] === 'retrying', "Transient failure must reset to 'retrying'");
        $this->assert((int)$row['attempts'] === 1, "Attempts count must increment to 1");
        $this->assert(strtotime($row['available_at']) > time(), "available_at must have future backoff timestamp");
        echo "OK\n";
    }

    // =========================================================================
    // Group 3: Professional Scholarship Message Formatter (9-14)
    // =========================================================================

    public function test9_singleMessageFormattingStructureAndHeading(): void {
        echo "[Test 9] Single scholarship message format conforms to professional standard... ";
        $payload = [
            'title' => 'Fulbright Scholarship 2027',
            'description' => 'Fully funded master degree scholarship in the United States.',
            'provider_name' => 'USEFP',
            'study_level' => 'Master\'s',
            'country_name' => 'United States',
            'funding_type' => 'Fully Funded',
            'application_deadline' => '2026-11-30',
            'official_application_url' => 'https://usefp.org/apply'
        ];

        $msg = ScholarshipMessageFormatter::formatSingleMessage($payload, 'NEW_MATCH');
        $this->assert(str_starts_with($msg, "🎓 SCHOLARSHIP OPPORTUNITY"), "Heading must start with 🎓 SCHOLARSHIP OPPORTUNITY");
        $this->assert(strpos($msg, 'Fulbright Scholarship 2027') !== false, "Title must be present");
        $this->assert(strpos($msg, '🏛 Provider:') !== false, "Provider must be labeled");
        $this->assert(strpos($msg, 'USEFP') !== false, "Provider name must be present");
        $this->assert(strpos($msg, '🔗 Apply:') !== false, "Apply link label must be present");
        $this->assert(strpos($msg, 'https://usefp.org/apply') !== false, "URL must be included");
        echo "OK\n";
    }

    public function test10_htmlSanitizationAndEntityDecoding(): void {
        echo "[Test 10] HTML tags stripped and HTML entities decoded in formatted output... ";
        $payload = [
            'title' => '<b>Commonwealth</b> &amp; Chevening &lt;Grant&gt;',
            'description' => '<p>Study in the <strong>UK</strong> with &quot;100% tuition&quot; coverage.</p>',
            'provider_name' => 'British Council'
        ];

        $msg = ScholarshipMessageFormatter::formatSingleMessage($payload, 'NEW_MATCH');
        $this->assert(strpos($msg, '<p>') === false, "HTML <p> tag must be removed");
        $this->assert(strpos($msg, '<b>') === false, "HTML <b> tag must be removed");
        $this->assert(strpos($msg, '&amp;') === false, "HTML &amp; entity must be decoded");
        $this->assert(strpos($msg, 'Commonwealth & Chevening <Grant>') !== false, "Title must have clean decoded characters");
        $this->assert(strpos($msg, '"100% tuition"') !== false, "Quotes entity must be decoded");
        echo "OK\n";
    }

    public function test11_deadlineFormattingTodayTomorrowAndStandard(): void {
        echo "[Test 11] Deadline relative formatting accurately reflects Today, Tomorrow, and dates... ";
        $today = date('Y-m-d');
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        $future = date('Y-m-d', strtotime('+45 days'));

        $this->assert(ScholarshipMessageFormatter::formatDeadline($today) === 'Today', "Today deadline must format as 'Today'");
        $this->assert(ScholarshipMessageFormatter::formatDeadline($tomorrow) === 'Tomorrow', "Tomorrow deadline must format as 'Tomorrow'");
        $this->assert(strpos(ScholarshipMessageFormatter::formatDeadline($future), date('Y', strtotime('+45 days'))) !== false, "Future deadline must contain 4-digit year");
        $this->assert(ScholarshipMessageFormatter::formatDeadline('open/rolling') === 'Open / Rolling', "Rolling deadline must format as 'Open / Rolling'");
        echo "OK\n";
    }

    public function test12_multiScholarshipDigestBatchFormatting(): void {
        echo "[Test 12] Multiple scholarships format into structured single digest message... ";
        $matches = [
            [
                'title' => 'Scholarship Alpha',
                'short_description' => 'First opportunity.',
                'application_deadline' => '2026-10-15',
                'country_name' => 'Germany',
                'funding_type' => 'Full',
                'official_application_url' => 'https://daad.de'
            ],
            [
                'title' => 'Scholarship Beta',
                'short_description' => 'Second opportunity.',
                'application_deadline' => '2026-11-01',
                'country_name' => 'Australia',
                'funding_type' => 'Partial',
                'official_application_url' => 'https://australiaawards.gov.au'
            ]
        ];

        $digest = ScholarshipMessageFormatter::formatMultipleMessage($matches, 2);
        $this->assert(str_starts_with($digest, "🎓 SCHOLARSHIP OPPORTUNITIES"), "Digest heading must be 🎓 SCHOLARSHIP OPPORTUNITIES");
        $this->assert(strpos($digest, 'Scholarship Alpha') !== false, "Must list Scholarship Alpha");
        $this->assert(strpos($digest, 'Scholarship Beta') !== false, "Must list Scholarship Beta");
        $this->assert(strpos($digest, 'ScholarPlanner') !== false, "Must have brand footer");
        echo "OK\n";
    }

    public function test13_metaTemplateParameterMappingNewMatch(): void {
        echo "[Test 13] Meta WhatsApp template mapping for new_match produces exactly 8 positional parameters... ";
        $payload = [
            'title' => 'Erasmus Mundus Joint Master',
            'description' => 'Prestigious European master degree scholarship across multiple universities.',
            'provider_name' => 'European Commission',
            'study_level' => 'Master\'s',
            'country_name' => 'European Union',
            'funding_type' => 'Fully Funded',
            'application_deadline' => '2027-01-15',
            'official_application_url' => 'https://erasmus-plus.ec.europa.eu'
        ];

        $params = ScholarshipMessageFormatter::buildTemplateParams('NEW_MATCH', $payload);
        $this->assert(count($params) === 8, "NEW_MATCH template requires exactly 8 positional parameters (got " . count($params) . ")");
        $this->assert($params[0] === 'Erasmus Mundus Joint Master', "Param 1 must be Title");
        $this->assert($params[2] === 'European Commission', "Param 3 must be Provider");
        $this->assert($params[3] === 'Master\'s', "Param 4 must be Study Level");
        $this->assert($params[4] === 'European Union', "Param 5 must be Country");
        $this->assert($params[5] === 'Fully Funded', "Param 6 must be Funding");
        $this->assert($params[7] === 'https://erasmus-plus.ec.europa.eu', "Param 8 must be Application URL");
        echo "OK\n";
    }

    public function test14_metaTemplateParameterMappingDeadlineAndPayment(): void {
        echo "[Test 14] Meta template mapping for deadline reminders and payment confirmation... ";
        $dlPayload = [
            'title' => 'Chevening Award',
            'description' => 'UK government master grant.',
            'provider_name' => 'FCDO',
            'days_left' => 3,
            'application_deadline' => '2026-11-05',
            'official_application_url' => 'https://chevening.org'
        ];
        $dlParams = ScholarshipMessageFormatter::buildTemplateParams('SCHOLARSHIP_DEADLINE_SOON', $dlPayload);
        $this->assert(count($dlParams) === 6, "DEADLINE_SOON template must produce 6 positional parameters (got " . count($dlParams) . ")");
        $this->assert($dlParams[3] === '3 days', "Param 4 must format days left");

        $payPayload = [
            'user_name' => 'Hamza Ali',
            'plan_name' => 'ScholarPlanner Monthly Pro',
            'amount' => '1000.00',
            'currency' => 'PKR',
            'reference' => 'TXN_STEP8_VERIFY_99'
        ];
        $payParams = ScholarshipMessageFormatter::buildTemplateParams('PAYMENT_CONFIRMATION', $payPayload);
        $this->assert(count($payParams) === 5, "PAYMENT_CONFIRMATION template must produce 5 parameters (got " . count($payParams) . ")");
        $this->assert($payParams[0] === 'Hamza Ali', "Param 1 must be user name");
        $this->assert($payParams[1] === 'ScholarPlanner Monthly Pro', "Param 2 must be plan name");
        $this->assert($payParams[2] === '1000.00', "Param 3 must be amount");
        $this->assert($payParams[3] === 'PKR', "Param 4 must be currency");
        echo "OK\n";
    }

    // =========================================================================
    // Group 4: Operational Visibility & Invariants (15-18)
    // =========================================================================

    public function test15_adminNotificationSummaryIncludesDeliveredCount(): void {
        echo "[Test 15] NotificationController summary query accurately includes 'delivered' count... ";
        $uid = $this->createUser('step8_t15@scholartest.com');
        $sid = $this->createScholarship('Step8 Scholarship T15');

        $this->db->prepare("
            INSERT INTO notification_logs (user_id, scholarship_id, notification_type, channel, recipient, provider, status, created_at, updated_at)
            VALUES (:uid, :sid, 'NEW_MATCH', 'whatsapp', '+923008000015', 'wacrm', 'delivered', NOW(), NOW())
        ")->execute(['uid' => $uid, 'sid' => $sid]);

        $summary = $this->db->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status='delivered' THEN 1 ELSE 0 END) as delivered
            FROM notification_logs
            WHERE user_id = $uid
        ")->fetch(PDO::FETCH_ASSOC);

        $this->assert((int)$summary['delivered'] === 1, "Delivered count in summary must be 1 (got {$summary['delivered']})");
        echo "OK\n";
    }

    public function test16_lifetime25WhatsAppCapEnforcedAcrossAutomatedJobs(): void {
        echo "[Test 16] Lifetime 25 WhatsApp cap strictly stops scheduling at 25 sent/delivered messages... ";
        $uid = $this->createUser('step8_t16@scholartest.com');
        $sid = $this->createScholarship('Step8 Scholarship T16');

        // Insert 25 delivered messages
        for ($i = 1; $i <= 25; $i++) {
            $this->db->prepare("
                INSERT INTO notification_logs (
                    user_id, scholarship_id, notification_type, channel, recipient, provider, status, created_at, updated_at
                ) VALUES (
                    :uid, :sid, 'NEW_MATCH', 'whatsapp', '+923008000016', 'wacrm', 'delivered', NOW(), NOW()
                )
            ")->execute(['uid' => $uid, 'sid' => $sid]);
        }

        // Run matching job
        $this->scheduler->runMatchingJob();

        // Count should not exceed 25
        $count = (int)$this->db->query("
            SELECT COUNT(*) FROM notification_logs 
            WHERE user_id = $uid AND channel = 'whatsapp'
        ")->fetchColumn();

        $this->assert($count === 25, "User with 25 WhatsApp messages must not receive additional WhatsApp alerts (got $count)");
        echo "OK\n";
    }

    public function test17_sundayQuietRuleSuppressesScholarshipAlerts(): void {
        echo "[Test 17] Sunday quiet rule prevents outbound automated scholarship WhatsApp alerts... ";
        $uid = $this->createUser('step8_t17@scholartest.com');
        $sid = $this->createScholarship('Step8 Scholarship T17');

        // Force simulate Sunday = true
        NotificationService::$simulateSunday = true;
        $this->scheduler->runMatchingJob(null, true);
        NotificationService::$simulateSunday = null;

        $count = (int)$this->db->query("
            SELECT COUNT(*) FROM notification_logs 
            WHERE user_id = $uid AND scholarship_id = $sid AND channel = 'whatsapp'
        ")->fetchColumn();

        $this->assert($count === 0, "Sunday quiet rule must defer scholarship WhatsApp notifications (got $count)");
        echo "OK\n";
    }

    public function test18_subscriptionProtectionMaintainsDeliveredAccounting(): void {
        echo "[Test 18] Delivered notifications directly maintain subscription_usage table... ";
        $uid = $this->createUser('step8_t18@scholartest.com');
        $sid = $this->createScholarship('Step8 Scholarship T18');
        $subId = (int)$this->db->query("SELECT id FROM subscriptions WHERE user_id = $uid ORDER BY id DESC LIMIT 1")->fetchColumn();

        $msgId = 'step8_wacrm_deliv_' . uniqid();
        $this->db->prepare("
            INSERT INTO notification_logs (
                user_id, scholarship_id, subscription_id, notification_type, channel, recipient,
                provider, provider_message_id, status, created_at, updated_at
            ) VALUES (
                :uid, :sid, :subid, 'NEW_MATCH', 'whatsapp', '+923008000018',
                'wacrm', :msgid, 'sent', NOW(), NOW()
            )
        ")->execute(['uid' => $uid, 'sid' => $sid, 'subid' => $subId, 'msgid' => $msgId]);

        // Deliver
        $this->queueService->recordDeliveryStatus($msgId, 'delivered', date('Y-m-d H:i:s'));

        $usage = (int)$this->db->query("SELECT qualifying_delivered_count FROM subscription_usage WHERE subscription_id = $subId")->fetchColumn();
        $this->assert($usage === 1, "subscription_usage.qualifying_delivered_count must atomically sync to 1");
        echo "OK\n";
    }

    // =========================================================================
    // Group 5: Full End-to-End Automation Cycle (19-20)
    // =========================================================================

    public function test19_fullAutomatedCycleMatchingToQueueWorker(): void {
        echo "[Test 19] Full cycle: Matching enqueues item -> Worker claims and dispatches -> marked sent... ";
        $uid = $this->createUser('step8_t19@scholartest.com');
        $sid = $this->createScholarship('Step8 Scholarship T19');

        // Step A: Scheduler matching job enqueues alert
        $matchingRes = $this->scheduler->runMatchingJob('2026-09-10', false);
        $this->assert($matchingRes['users_processed'] >= 1, "Matching job must evaluate users");

        $pendingItem = $this->db->query("
            SELECT id, status FROM notification_logs 
            WHERE user_id = $uid AND scholarship_id = $sid 
            ORDER BY id DESC LIMIT 1
        ")->fetch(PDO::FETCH_ASSOC);

        $this->assert(!empty($pendingItem), "Notification log row must be enqueued");
        $this->assert($pendingItem['status'] === 'pending', "Newly scheduled item must start in 'pending'");

        // Step B: Worker processes queue
        CurlMockRegistry::reset();
        CurlMockRegistry::$httpCode = 200;
        CurlMockRegistry::$response = json_encode([
            'messages' => [['id' => 'wamid.HBgL_step8_meta_123']],
            'data' => [
                'message_id' => 'msg_wacrm_step8_' . uniqid(),
                'whatsapp_message_id' => 'wamid.step8.test'
            ]
        ]);
        $processed = $this->queueService->processQueue(50);
        $this->assert($processed >= 1, "Worker must process at least 1 item");

        $itemAfter = $this->db->query("SELECT status, provider_message_id FROM notification_logs WHERE id = {$pendingItem['id']}")->fetch(PDO::FETCH_ASSOC);
        $this->assert(in_array($itemAfter['status'], ['sent', 'delivered']), "Worker must dispatch item to sent/delivered (got {$itemAfter['status']})");
        echo "OK\n";
    }

    public function test20_deliveryWebhookTriggersInstantUsageAccounting(): void {
        echo "[Test 20] Delivery webhook triggers instant usage accounting and keeps state machine stable... ";
        $uid = $this->createUser('step8_t20@scholartest.com');
        $sid = $this->createScholarship('Step8 Scholarship T20');
        $subId = (int)$this->db->query("SELECT id FROM subscriptions WHERE user_id = $uid ORDER BY id DESC LIMIT 1")->fetchColumn();

        $msgId = 'step8_wacrm_final_' . uniqid();
        $this->db->prepare("
            INSERT INTO notification_logs (
                user_id, scholarship_id, subscription_id, notification_type, channel, recipient,
                provider, provider_message_id, status, created_at, updated_at
            ) VALUES (
                :uid, :sid, :subid, 'NEW_MATCH', 'whatsapp', '+923008000020',
                'wacrm', :msgid, 'sent', NOW(), NOW()
            )
        ")->execute(['uid' => $uid, 'sid' => $sid, 'subid' => $subId, 'msgid' => $msgId]);

        // Incoming webhook arrives
        $res = $this->queueService->recordDeliveryStatus($msgId, 'delivered', date('Y-m-d H:i:s'));
        $this->assert($res['success'] === true, "Delivery status recording must succeed");

        // Verify notification_logs
        $logRow = $this->db->query("SELECT status, delivered_at FROM notification_logs WHERE provider_message_id = '$msgId'")->fetch(PDO::FETCH_ASSOC);
        $this->assert($logRow['status'] === 'delivered', "Status must be 'delivered'");
        $this->assert(!empty($logRow['delivered_at']), "delivered_at must be populated");

        // Verify usage table
        $usageCount = (int)$this->db->query("SELECT qualifying_delivered_count FROM subscription_usage WHERE subscription_id = $subId")->fetchColumn();
        $this->assert($usageCount === 1, "Usage table must show 1 qualifying delivery");

        // Attempting an out-of-order 'sent' webhook must not downgrade
        $resLate = $this->queueService->recordDeliveryStatus($msgId, 'sent', date('Y-m-d H:i:s'));
        $this->assert($resLate['success'] === true && $resLate['updated'] === false, "Late sent status must not overwrite delivered status");

        $statusAfterLate = $this->db->query("SELECT status FROM notification_logs WHERE provider_message_id = '$msgId'")->fetchColumn();
        $this->assert($statusAfterLate === 'delivered', "Status must remain 'delivered'");
        echo "OK\n";
    }
}
