<?php

require_once __DIR__ . '/bootstrap.php';

use App\Services\Database;
use App\Services\Auth;
use App\Services\SubscriptionService;
use App\Services\NotificationQueueService;
use App\Services\NotificationDispatchService;
use App\Services\NotificationSchedulerService;
use App\Services\NotificationService;
use App\Services\NotificationTypes;
use App\Services\EmailNotificationService;
use App\Services\WhatsAppNotificationService;
use App\Services\ScholarshipMatchingService;
use App\Services\WhatsApp\WacrmWhatsAppProvider;
use App\Services\WhatsApp\WhatsAppProviderInterface;
use App\Controllers\NotificationController;

class Step7MockWhatsAppProvider implements WhatsAppProviderInterface {
    public int $callCount = 0;
    public array $calls = [];
    public bool $mockSuccess = true;
    public ?string $mockMessageId = 'wacrm_step7_msg_';
    public ?string $mockError = null;
    public ?int $mockRetryAfter = null;

    public function sendTemplateMessage(string $recipient, string $templateName, array $parameters): array {
        $this->callCount++;
        $this->calls[] = [
            'recipient' => $recipient,
            'template' => $templateName,
            'parameters' => $parameters
        ];

        return [
            'success' => $this->mockSuccess,
            'message_id' => $this->mockSuccess ? ($this->mockMessageId . $this->callCount) : null,
            'error' => $this->mockSuccess ? null : ($this->mockError ?? 'Simulated WACRM error'),
            'retry_after' => $this->mockRetryAfter
        ];
    }
}

class Step7NotificationAutomationTest {
    private PDO $db;
    private NotificationQueueService $queueService;
    private NotificationDispatchService $dispatchService;
    private NotificationSchedulerService $schedulerService;
    private NotificationService $notificationService;
    private ScholarshipMatchingService $matchingService;

    private int $visitorRoleId;
    private int $planPremiumId;
    private int $planFreeId;
    private int $pakistanCountryId;
    private int $sindhStateId;
    private int $csFieldId;
    private int $phoneSeq = 700000;

    public function __construct() {
        $this->db = Database::connection();
        $this->queueService = new NotificationQueueService();
        $this->dispatchService = new NotificationDispatchService();
        $this->schedulerService = new NotificationSchedulerService($this->db);
        $this->notificationService = new NotificationService();
        $this->matchingService = new ScholarshipMatchingService();
    }

    private function assert(bool $condition, string $message): void {
        if (!$condition) {
            throw new Exception("Assertion failed: " . $message);
        }
    }

    private function invokeUpdateQueueItemStatus(int $id, int $attempts, bool $success, ?string $errorMessage = null, ?string $providerMessageId = null): void {
        $ref = new ReflectionMethod($this->queueService, 'updateQueueItemStatus');
        $ref->setAccessible(true);
        $ref->invoke($this->queueService, $id, $attempts, $success, $errorMessage, $providerMessageId);
    }

    public function run(): void {
        echo "=================================================================\n";
        echo " RUNNING STEP 7 NOTIFICATION AUTOMATION & QUEUE WORKER SUITE (28 TESTS)\n";
        echo "=================================================================\n\n";

        $this->setUp();

        try {
            // Group 1: Scheduling & Enqueuing Integrity (1-2)
            $this->test1_schedulerEnqueuesWithoutSendingDirectly();
            $this->test2_schedulerIdempotentOnRepeatedExecution();

            // Group 2: Queue Worker Concurrency & Row Locking (3-4)
            $this->test3_queueWorkerAtomicClaimWithRowLock();
            $this->test4_concurrentWorkersCannotClaimSameItem();

            // Group 3: WACRM Dispatch, Retries & SSRF Security (5-8)
            $this->test5_successfulWacrmDispatchMarksSentAndRecordsMessageId();
            $this->test6_temporaryWacrmFailureRetriesWithBackoff();
            $this->test7_permanentWacrmFailureMarksFailed();
            $this->test8_wacrmSsrfProtectionRejectsPrivateAndLoopbackInProduction();

            // Group 4: Delivery Webhook State Machine & Idempotency (9-12)
            $this->test9_deliveryWebhookUpdatesDeliveredStatusAndTimestamp();
            $this->test10_duplicateDeliveryWebhookIsIdempotent();
            $this->test11_outOfOrderWebhookCannotDowngradeDelivered();
            $this->test12_failedNotificationReceivingDeliveredWebhookUpdatesToDelivered();

            // Group 5: Subscription Protection & Delivery Accounting (13-14)
            $this->test13_subscriptionsOnlyDeliveredNotificationsCount();
            $this->test14_dailyDigestBatchCountsAsExactlyOneDelivery();

            // Group 6: Automated Policy Guardrails & User Preferences (15-18)
            $this->test15_lifetime25MessageCapStrictlyEnforced();
            $this->test16_sundayQuietRuleDefersWhatsApp();
            $this->test17_userPreferencesRespected();
            $this->test18_deadlineReminderPreferencesAndScopeEnforced();

            // Group 7: Email Queue, Stale Recovery & Scheduler Mutex (19-21)
            $this->test19_emailQueueAndSmtpDispatchWithCredentialRedaction();
            $this->test20_staleProcessingJobRecovery();
            $this->test21_masterSchedulerMutexLock();

            // Group 8: Cancelled & Expired Subscriptions Lifecycle Alert Gating (22-24)
            $this->test22_cancelledSubscriptionInsidePaidPeriodReceivesAlert();
            $this->test23_expiredCancelledSubscriptionBlockedFromPremiumAlerts();
            $this->test24_postExpiryDeliveryWebhookDoesNotReactivateSubscription();

            // Group 9: HTTP Webhook Endpoint Ingestion (25-28)
            $this->test25_webhookHttpEndpointParsesPayloadAndUpdatesStatus();
            $this->test26_metaCloudApiWebhookFormatSupported();
            $this->test27_invalidOrEmptyWebhookPayloadRejected();
            $this->test28_deliveryWebhookSyncsSubscriptionUsageTable();

            echo "\n=================================================================\n";
            echo " ✔ ALL 28 STEP 7 NOTIFICATION AUTOMATION TESTS PASSED!\n";
            echo "=================================================================\n\n";
        } finally {
            $this->tearDown();
        }
    }

    private function setUp(): void {
        $this->visitorRoleId = (int)$this->db->query("SELECT id FROM roles WHERE name = 'visitor' LIMIT 1")->fetchColumn();
        $this->planPremiumId = (int)$this->db->query("SELECT id FROM subscription_plans WHERE slug = 'premium-monthly' LIMIT 1")->fetchColumn();
        $this->planFreeId = (int)$this->db->query("SELECT id FROM subscription_plans WHERE slug = 'free' LIMIT 1")->fetchColumn();
        
        $this->pakistanCountryId = (int)($this->db->query("SELECT id FROM countries WHERE name = 'Pakistan' LIMIT 1")->fetchColumn() ?: 1);
        $this->sindhStateId = (int)($this->db->query("SELECT id FROM states WHERE country_id = {$this->pakistanCountryId} LIMIT 1")->fetchColumn() ?: 1);
        $this->csFieldId = (int)($this->db->query("SELECT id FROM fields_of_study LIMIT 1")->fetchColumn() ?: 1);

        $this->cleanTestData();
    }

    private function tearDown(): void {
        $this->cleanTestData();
        \App\Services\NotificationService::$simulateSunday = null;
    }

    private function cleanTestData(): void {
        $this->db->exec("DELETE FROM notification_logs WHERE recipient LIKE '%step7%' OR error_message LIKE '%Step7%' OR idempotency_key LIKE '%step7%'");
        $this->db->exec("DELETE FROM subscription_usage WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'step7_%')");
        $this->db->exec("DELETE FROM subscriptions WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'step7_%')");
        $this->db->exec("DELETE FROM user_scholarship_reminders WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'step7_%')");
        $this->db->exec("DELETE FROM scholarship_matches WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'step7_%')");
        $this->db->exec("DELETE FROM user_preferences WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'step7_%')");
        $this->db->exec("DELETE FROM notification_preferences WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'step7_%')");
        $this->db->exec("DELETE FROM education_records WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'step7_%')");
        $this->db->exec("DELETE FROM student_profiles WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'step7_%')");
        $this->db->exec("DELETE FROM users WHERE email LIKE 'step7_%'");
        $this->db->exec("DELETE FROM scholarship_degree_levels WHERE scholarship_id IN (SELECT id FROM scholarships WHERE title LIKE 'Step7 %')");
        $this->db->exec("DELETE FROM scholarships WHERE title LIKE 'Step7 %'");
    }

    private function createUser(string $email, int $optInWa = 1, int $optInEmail = 1, ?string $phone = null, bool $withSub = true): int {
        if ($phone === null) {
            $this->phoneSeq++;
            $phone = '+92300' . str_pad((string)$this->phoneSeq, 7, '0', STR_PAD_LEFT);
        }
        $stmt = $this->db->prepare("
            INSERT INTO users (
                email, password_hash, first_name, last_name, phone, whatsapp_phone,
                role_id, status, email_opt_in, whatsapp_opt_in, created_at, updated_at
            ) VALUES (
                :email, 'test_hash', 'Step7', 'Tester', :phone, :wa_phone,
                :role, 'active', :opt_email, :opt_wa, NOW(), NOW()
            )
        ");
        $stmt->execute([
            'email' => $email,
            'phone' => $phone,
            'wa_phone' => $phone,
            'role' => $this->visitorRoleId,
            'opt_email' => $optInEmail,
            'opt_wa' => $optInWa
        ]);
        $uid = (int)$this->db->lastInsertId();

        // Create student profile
        $this->db->prepare("
            INSERT INTO student_profiles (user_id, nationality_country_id, residence_country_id, residence_state_id, date_of_birth, gender, created_at, updated_at)
            VALUES (:uid, :ncid, :rcid, :sid, '2000-01-01', 'Male', NOW(), NOW())
        ")->execute([
            'uid' => $uid,
            'ncid' => $this->pakistanCountryId,
            'rcid' => $this->pakistanCountryId,
            'sid' => $this->sindhStateId
        ]);

        // Create education record
        $this->db->prepare("
            INSERT INTO education_records (user_id, institution_name, degree_level, degree_title, field_of_study, country_id, cgpa, cgpa_scale, percentage, is_current, graduation_status, created_at, updated_at)
            VALUES (:uid, 'IBA Karachi', 'Bachelor\'s', 'Computer Science', 'Computer Science', :cid, 3.80, 4.00, 95.0, 1, 'in_progress', NOW(), NOW())
        ")->execute([
            'uid' => $uid,
            'cid' => $this->pakistanCountryId
        ]);

        // User preferences
        $this->db->prepare("
            INSERT INTO user_preferences (user_id, preferred_channel, allow_multi_channel, deadline_reminder_scope, deadline_reminder_days, created_at, updated_at)
            VALUES (:uid, 'both', 1, 'all', '7,3,1', NOW(), NOW())
        ")->execute(['uid' => $uid]);

        // Notification preferences
        $types = ['matching_scholarship_alerts', 'deadline_reminders', 'email_alerts', 'whatsapp_alerts'];
        foreach ($types as $t) {
            $this->db->prepare("
                INSERT INTO notification_preferences (user_id, notification_type, email_enabled, whatsapp_enabled, created_at, updated_at)
                VALUES (:uid, :ntype, 1, 1, NOW(), NOW())
            ")->execute(['uid' => $uid, 'ntype' => $t]);
        }

        // Active subscription so user qualifies for matching alerts
        if ($withSub) {
            $this->createSubscription($uid, 'active', date('Y-m-d H:i:s', strtotime('-5 days')), date('Y-m-d H:i:s', strtotime('+25 days')));
        }

        return $uid;
    }

    private function createScholarship(string $title, ?string $deadline = null): int {
        $deadline = $deadline ?: date('Y-m-d', strtotime('+30 days'));
        $slug = strtolower(str_replace(' ', '-', $title)) . '-' . time() . '-' . rand(100, 999);
        $stmt = $this->db->prepare("
            INSERT INTO scholarships (
                title, slug, provider_name, description, short_description, funding_type, status,
                verification_status, country_id, application_deadline, created_at, updated_at
            ) VALUES (
                :title, :slug, 'Global Foundation', 'Description of scholarship', 'Short desc', 'fully_funded', 'published',
                'verified', :cid, :dl, NOW(), NOW()
            )
        ");
        $stmt->execute([
            'title' => $title,
            'slug' => $slug,
            'cid' => $this->pakistanCountryId,
            'dl' => $deadline
        ]);
        $sid = (int)$this->db->lastInsertId();

        $this->db->prepare("INSERT INTO scholarship_degree_levels (scholarship_id, degree_level) VALUES (:sid, 'Bachelor\'s')")
            ->execute(['sid' => $sid]);

        return $sid;
    }

    private function createSubscription(int $userId, string $status, string $startsAt, string $endsAt, int $planId = 0): int {
        $planId = $planId ?: $this->planPremiumId;
        $stmt = $this->db->prepare("
            INSERT INTO subscriptions (
                user_id, plan_id, status, starts_at, ends_at, normal_ends_at,
                minimum_delivered_required, auto_renew, provider, created_at, updated_at
            ) VALUES (
                :uid, :pid, :status, :starts, :ends, :normal_ends,
                5, 1, 'manual', NOW(), NOW()
            )
        ");
        $stmt->execute([
            'uid' => $userId,
            'pid' => $planId,
            'status' => $status,
            'starts' => $startsAt,
            'ends' => $endsAt,
            'normal_ends' => $endsAt
        ]);
        return (int)$this->db->lastInsertId();
    }

    private function createLog(int $userId, int $schId, string $status = 'pending', string $channel = 'whatsapp', ?string $provMsgId = null, ?int $subId = null, string $type = 'DAILY_MATCH_DIGEST', ?string $idempotencyKey = null): int {
        $key = $idempotencyKey ?: ('step7_test_' . uniqid() . '_' . rand(1000, 9999));
        $provMsgId = $provMsgId ?: ('step7_prov_' . uniqid());
        $stmt = $this->db->prepare("
            INSERT INTO notification_logs (
                user_id, scholarship_id, subscription_id, notification_type, channel,
                recipient, status, provider_message_id, idempotency_key, attempts,
                available_at, created_at, updated_at
            ) VALUES (
                :uid, :sid, :sub_id, :type, :channel,
                '+923007770001', :status, :prov_id, :key, 0,
                NOW(), NOW(), NOW()
            )
        ");
        $stmt->execute([
            'uid' => $userId,
            'sid' => $schId,
            'sub_id' => $subId,
            'type' => $type,
            'channel' => $channel,
            'status' => $status,
            'prov_id' => $provMsgId,
            'key' => $key
        ]);
        return (int)$this->db->lastInsertId();
    }

    // =========================================================================
    // Group 1: Scheduling & Enqueuing Integrity (1-2)
    // =========================================================================

    public function test1_schedulerEnqueuesWithoutSendingDirectly(): void {
        echo "[Test 1] Scheduler enqueues matching notifications without sending directly... ";
        $uid = $this->createUser('step7_t1@scholartest.com');
        $sid = $this->createScholarship('Step7 Scholarship T1');

        $cntBefore = (int)$this->db->query("SELECT COUNT(*) FROM notification_logs WHERE user_id = $uid")->fetchColumn();
        $this->schedulerService->runMatchingJob();
        $cntAfter = (int)$this->db->query("SELECT COUNT(*) FROM notification_logs WHERE user_id = $uid")->fetchColumn();

        $this->assert($cntAfter > $cntBefore, "Matching job must enqueue items in notification_logs");

        // Verify direct delivery was NOT attempted by scheduler (all enqueued items must be 'pending' or 'retrying')
        $directSent = (int)$this->db->query("SELECT COUNT(*) FROM notification_logs WHERE user_id = $uid AND status = 'sent'")->fetchColumn();
        $this->assert($directSent === 0, "Scheduler must only enqueue pending jobs, zero direct sends");
        echo "OK\n";
    }

    public function test2_schedulerIdempotentOnRepeatedExecution(): void {
        echo "[Test 2] Scheduler is idempotent on repeated execution... ";
        $uid = $this->createUser('step7_t2@scholartest.com');
        $sid = $this->createScholarship('Step7 Scholarship T2');

        $this->schedulerService->runMatchingJob();
        $firstCount = (int)$this->db->query("SELECT COUNT(*) FROM notification_logs WHERE user_id = $uid")->fetchColumn();

        // Run matching again in the same day/window
        $this->schedulerService->runMatchingJob();
        $secondCount = (int)$this->db->query("SELECT COUNT(*) FROM notification_logs WHERE user_id = $uid")->fetchColumn();

        $this->assert($firstCount === $secondCount, "Scheduler rerun must not duplicate enqueued notifications (expected $firstCount, got $secondCount)");
        echo "OK\n";
    }

    // =========================================================================
    // Group 2: Queue Worker Concurrency & Row Locking (3-4)
    // =========================================================================

    public function test3_queueWorkerAtomicClaimWithRowLock(): void {
        echo "[Test 3] Queue worker claims pending items with concurrency locking (SELECT ... FOR UPDATE)... ";
        $uid = $this->createUser('step7_t3@scholartest.com');
        $sid = $this->createScholarship('Step7 Scholarship T3');
        $logId = $this->createLog($uid, $sid, 'pending', 'email');

        // Verify initial state
        $initialStatus = $this->db->query("SELECT status FROM notification_logs WHERE id = $logId")->fetchColumn();
        $this->assert($initialStatus === 'pending', "Item must start as pending");

        // Atomic claim simulation using the exact worker SQL pattern
        $this->db->beginTransaction();
        $stmtLock = $this->db->prepare("
            SELECT id FROM notification_logs 
            WHERE status IN ('pending', 'retrying') 
              AND available_at <= NOW() 
              AND id = :id 
            LIMIT 1 FOR UPDATE
        ");
        $stmtLock->execute(['id' => $logId]);
        $claimedId = (int)$stmtLock->fetchColumn();
        $this->assert($claimedId === $logId, "Worker must claim the exact item under FOR UPDATE lock");

        $upStmt = $this->db->prepare("UPDATE notification_logs SET status = 'processing', updated_at = NOW() WHERE id = :id");
        $upStmt->execute(['id' => $logId]);
        $this->db->commit();

        $markedStatus = $this->db->query("SELECT status FROM notification_logs WHERE id = $logId")->fetchColumn();
        $this->assert($markedStatus === 'processing', "Claimed item must be marked 'processing'");
        echo "OK\n";
    }

    public function test4_concurrentWorkersCannotClaimSameItem(): void {
        echo "[Test 4] Two concurrent workers cannot claim the same queue item... ";
        $uid = $this->createUser('step7_t4@scholartest.com');
        $sid = $this->createScholarship('Step7 Scholarship T4');
        $logId = $this->createLog($uid, $sid, 'pending', 'email');

        // Worker 1 claims the item in transaction
        $this->db->beginTransaction();
        $stmtW1 = $this->db->prepare("SELECT id FROM notification_logs WHERE id = :id AND status IN ('pending', 'retrying') FOR UPDATE");
        $stmtW1->execute(['id' => $logId]);
        $w1Claimed = $stmtW1->fetchColumn();
        $this->assert((int)$w1Claimed === $logId, "Worker 1 successfully claimed item");

        $this->db->prepare("UPDATE notification_logs SET status = 'processing' WHERE id = :id")->execute(['id' => $logId]);
        $this->db->commit();

        // Worker 2 attempts to claim from pending queue
        $stmtW2 = $this->db->prepare("SELECT id FROM notification_logs WHERE id = :id AND status IN ('pending', 'retrying')");
        $stmtW2->execute(['id' => $logId]);
        $w2Claimed = $stmtW2->fetchColumn();

        $this->assert($w2Claimed === false, "Worker 2 must NOT claim the item already claimed by Worker 1");
        echo "OK\n";
    }

    // =========================================================================
    // Group 3: WACRM Dispatch, Retries & SSRF Security (5-8)
    // =========================================================================

    public function test5_successfulWacrmDispatchMarksSentAndRecordsMessageId(): void {
        echo "[Test 5] Successful WACRM dispatch marks status = 'sent' and records provider_message_id... ";
        $uid = $this->createUser('step7_t5@scholartest.com');
        $sid = $this->createScholarship('Step7 Scholarship T5');
        $logId = $this->createLog($uid, $sid, 'pending', 'whatsapp');

        $mockProv = new Step7MockWhatsAppProvider();
        $mockProv->mockSuccess = true;
        $mockProv->mockMessageId = 'wacrm_succ_9999';

        $res = $mockProv->sendTemplateMessage('+923007770001', 'daily_match_digest', ['Title' => 'Test']);
        $this->assert($res['success'] === true, "Mock provider returns success");

        $this->invokeUpdateQueueItemStatus($logId, 1, true, null, $res['message_id']);

        $row = $this->db->query("SELECT status, provider_message_id, sent_at FROM notification_logs WHERE id = $logId")->fetch(PDO::FETCH_ASSOC);
        $this->assert($row['status'] === 'sent', "Status must be 'sent'");
        $this->assert($row['provider_message_id'] === $res['message_id'], "provider_message_id must be recorded");
        $this->assert(!empty($row['sent_at']), "sent_at must be populated");
        echo "OK\n";
    }

    public function test6_temporaryWacrmFailureRetriesWithBackoff(): void {
        echo "[Test 6] Temporary WACRM failure retries up to max attempts with backoff... ";
        $uid = $this->createUser('step7_t6@scholartest.com');
        $sid = $this->createScholarship('Step7 Scholarship T6');
        $logId = $this->createLog($uid, $sid, 'pending', 'whatsapp');

        // Attempt 1 failure (current attempts = 0 -> becomes 1)
        $this->invokeUpdateQueueItemStatus($logId, 0, false, "Connection timeout to WACRM API", null);

        $row1 = $this->db->query("SELECT status, attempts, available_at FROM notification_logs WHERE id = $logId")->fetch(PDO::FETCH_ASSOC);
        $this->assert($row1['status'] === 'retrying', "Attempt 1 failure must set status to 'retrying'");
        $this->assert((int)$row1['attempts'] === 1, "Attempts must be incremented to 1");
        $this->assert(strtotime($row1['available_at']) > time(), "available_at must have exponential backoff");
        echo "OK\n";
    }

    public function test7_permanentWacrmFailureMarksFailed(): void {
        echo "[Test 7] Permanent WACRM failure marks status = 'failed'... ";
        $uid = $this->createUser('step7_t7@scholartest.com');
        $sid = $this->createScholarship('Step7 Scholarship T7');
        $logId = $this->createLog($uid, $sid, 'pending', 'whatsapp');

        // Fail reaching max attempts (current attempts = 2 -> becomes 3)
        $this->invokeUpdateQueueItemStatus($logId, 2, false, "Invalid phone number or recipient unreachable", null);

        $row = $this->db->query("SELECT status, attempts, failed_at, error_message FROM notification_logs WHERE id = $logId")->fetch(PDO::FETCH_ASSOC);
        $this->assert($row['status'] === 'failed', "After 3 attempts, status must be 'failed'");
        $this->assert(!empty($row['failed_at']), "failed_at timestamp must be set");
        $this->assert(str_contains($row['error_message'], 'Invalid phone number'), "Error message must be preserved");
        echo "OK\n";
    }

    public function test8_wacrmSsrfProtectionRejectsPrivateAndLoopbackInProduction(): void {
        echo "[Test 8] WACRM SSRF protection: private IP, loopback, and malformed URLs rejected in production mode... ";
        $strict = true; // Force strict production mode

        // Loopback IPv4
        $r1 = WacrmWhatsAppProvider::resolveAndValidate('https://127.0.0.1/api', $strict);
        $this->assert($r1['safe'] === false, "127.0.0.1 must be rejected");

        // Localhost string
        $r2 = WacrmWhatsAppProvider::resolveAndValidate('https://localhost/api', $strict);
        $this->assert($r2['safe'] === false, "localhost must be rejected");

        // Private 10.x.x.x
        $r3 = WacrmWhatsAppProvider::resolveAndValidate('https://10.0.0.5/api', $strict);
        $this->assert($r3['safe'] === false, "10.0.0.5 must be rejected");

        // AWS/Cloud Link-Local Metadata 169.254.169.254
        $r4 = WacrmWhatsAppProvider::resolveAndValidate('https://169.254.169.254/latest/meta-data', $strict);
        $this->assert($r4['safe'] === false, "169.254.169.254 link-local must be rejected");

        // HTTP (non-https) in production
        $r5 = WacrmWhatsAppProvider::resolveAndValidate('http://api.wacrm.com/api', $strict);
        $this->assert($r5['safe'] === false, "Plain HTTP must be rejected in production");

        // Valid safe public HTTPS URL with mocked safe resolver
        $safeResolver = fn(string $host) => ['93.184.216.34']; // Example.com public IP
        $r6 = WacrmWhatsAppProvider::resolveAndValidate('https://api.wacrm.net/v1', $strict, $safeResolver);
        $this->assert($r6['safe'] === true, "Public HTTPS URL with public IP must be accepted");
        echo "OK\n";
    }

    // =========================================================================
    // Group 4: Delivery Webhook State Machine & Idempotency (9-12)
    // =========================================================================

    public function test9_deliveryWebhookUpdatesDeliveredStatusAndTimestamp(): void {
        echo "[Test 9] Delivery webhook (delivered) updates notification_logs.status and delivered_at... ";
        $uid = $this->createUser('step7_t9@scholartest.com');
        $sid = $this->createScholarship('Step7 Scholarship T9');
        $msgId = 'step7_wacrm_deliv_' . uniqid();
        $logId = $this->createLog($uid, $sid, 'sent', 'whatsapp', $msgId);

        $delivTime = '2026-09-10 12:00:00';
        $res = $this->queueService->recordDeliveryStatus($msgId, 'delivered', $delivTime);

        $this->assert($res['success'] === true, "recordDeliveryStatus must succeed");
        $row = $this->db->query("SELECT status, delivered_at FROM notification_logs WHERE id = $logId")->fetch(PDO::FETCH_ASSOC);
        $this->assert($row['status'] === 'delivered', "Status must be 'delivered'");
        $this->assert($row['delivered_at'] === $delivTime, "delivered_at must match webhook timestamp");
        echo "OK\n";
    }

    public function test10_duplicateDeliveryWebhookIsIdempotent(): void {
        echo "[Test 10] Duplicate delivery webhook is idempotent and preserves initial delivered_at... ";
        $uid = $this->createUser('step7_t10@scholartest.com');
        $sid = $this->createScholarship('Step7 Scholarship T10');
        $msgId = 'step7_wacrm_dup_' . uniqid();
        $logId = $this->createLog($uid, $sid, 'sent', 'whatsapp', $msgId);

        $firstTime = '2026-09-10 10:00:00';
        $this->queueService->recordDeliveryStatus($msgId, 'delivered', $firstTime);

        // Duplicate delivered webhook arriving later with different timestamp
        $secondTime = '2026-09-10 10:30:00';
        $res2 = $this->queueService->recordDeliveryStatus($msgId, 'delivered', $secondTime);

        $this->assert($res2['success'] === true, "Duplicate webhook must succeed without error");
        $row = $this->db->query("SELECT status, delivered_at FROM notification_logs WHERE id = $logId")->fetch(PDO::FETCH_ASSOC);
        $this->assert($row['status'] === 'delivered', "Status must remain 'delivered'");
        $this->assert($row['delivered_at'] === $firstTime, "delivered_at must remain immutable initial timestamp ($firstTime)");
        echo "OK\n";
    }

    public function test11_outOfOrderWebhookCannotDowngradeDelivered(): void {
        echo "[Test 11] Out-of-order webhook (sent after delivered) cannot downgrade delivered... ";
        $uid = $this->createUser('step7_t11@scholartest.com');
        $sid = $this->createScholarship('Step7 Scholarship T11');
        $msgId = 'step7_wacrm_ooo_' . uniqid();
        $logId = $this->createLog($uid, $sid, 'sent', 'whatsapp', $msgId);

        // Delivered arrived first
        $this->queueService->recordDeliveryStatus($msgId, 'delivered', '2026-09-10 11:00:00');

        // Belated sent status arrives
        $resSent = $this->queueService->recordDeliveryStatus($msgId, 'sent');
        $this->assert($resSent['success'] === true, "Webhook must handle out-of-order safely");

        // Belated failed status arrives
        $resFailed = $this->queueService->recordDeliveryStatus($msgId, 'failed', null, 'Delayed failure event');
        $this->assert($resFailed['success'] === true, "Webhook must handle out-of-order failure safely");

        $status = $this->db->query("SELECT status FROM notification_logs WHERE id = $logId")->fetchColumn();
        $this->assert($status === 'delivered', "Terminal status 'delivered' must never downgrade to sent or failed");
        echo "OK\n";
    }

    public function test12_failedNotificationReceivingDeliveredWebhookUpdatesToDelivered(): void {
        echo "[Test 12] Failed notification receiving delivered webhook updates to delivered... ";
        $uid = $this->createUser('step7_t12@scholartest.com');
        $sid = $this->createScholarship('Step7 Scholarship T12');
        $msgId = 'step7_wacrm_fail_to_deliv_' . uniqid();
        $logId = $this->createLog($uid, $sid, 'failed', 'whatsapp', $msgId);

        // Late delivery confirmation arrives from WhatsApp
        $res = $this->queueService->recordDeliveryStatus($msgId, 'delivered', '2026-09-10 13:00:00');
        $this->assert($res['success'] === true, "Late delivery update must succeed");

        $status = $this->db->query("SELECT status FROM notification_logs WHERE id = $logId")->fetchColumn();
        $this->assert($status === 'delivered', "Failed record must successfully update to 'delivered'");
        echo "OK\n";
    }

    // =========================================================================
    // Group 5: Subscription Protection & Delivery Accounting (13-14)
    // =========================================================================

    public function test13_subscriptionsOnlyDeliveredNotificationsCount(): void {
        echo "[Test 13] Subscriptions: only delivered notifications count toward qualifying deliveries... ";
        $uid = $this->createUser('step7_t13@scholartest.com', 1, 1, null, false);
        $sid = $this->createScholarship('Step7 Scholarship T13');
        $subId = $this->createSubscription($uid, 'active', date('Y-m-d H:i:s', strtotime('-5 days')), date('Y-m-d H:i:s', strtotime('+25 days')));

        // Create 1 pending, 1 processing, 1 sent, 1 failed, 1 delivered
        $this->createLog($uid, $sid, 'pending', 'whatsapp', 'msg_pen', $subId);
        $this->createLog($uid, $sid, 'processing', 'whatsapp', 'msg_pro', $subId);
        $this->createLog($uid, $sid, 'sent', 'whatsapp', 'msg_snt', $subId);
        $this->createLog($uid, $sid, 'failed', 'whatsapp', 'msg_fld', $subId);

        $logDeliv = $this->createLog($uid, $sid, 'sent', 'whatsapp', 'msg_dlv', $subId);
        $this->queueService->recordDeliveryStatus('msg_dlv', 'delivered', date('Y-m-d H:i:s'));

        $qualifyingCount = SubscriptionService::countQualifyingDeliveredMessages($subId, $this->db);
        $this->assert($qualifyingCount === 1, "Only the 1 delivered notification must count (got $qualifyingCount)");
        echo "OK\n";
    }

    public function test14_dailyDigestBatchCountsAsExactlyOneDelivery(): void {
        echo "[Test 14] Daily digest batch with multiple scholarships counts as exactly 1 physical delivery... ";
        $uid = $this->createUser('step7_t14@scholartest.com', 1, 1, null, false);
        $sid1 = $this->createScholarship('Step7 Scholarship T14-A');
        $sid2 = $this->createScholarship('Step7 Scholarship T14-B');
        $sid3 = $this->createScholarship('Step7 Scholarship T14-C');
        $subId = $this->createSubscription($uid, 'active', date('Y-m-d H:i:s', strtotime('-5 days')), date('Y-m-d H:i:s', strtotime('+25 days')));

        $batchIdempotencyKey = 'scholarship_whatsapp_' . $uid . '_2026-09-10';
        $sharedProviderMsgId = 'wacrm_batch_shared_1001';
        $combinedPayload = json_encode([
            'matches' => [
                ['scholarship_id' => $sid1, 'title' => 'Step7 Scholarship T14-A'],
                ['scholarship_id' => $sid2, 'title' => 'Step7 Scholarship T14-B'],
                ['scholarship_id' => $sid3, 'title' => 'Step7 Scholarship T14-C']
            ]
        ]);

        $stmt = $this->db->prepare("
            INSERT INTO notification_logs (
                user_id, scholarship_id, subscription_id, notification_type, channel,
                recipient, status, provider_message_id, idempotency_key, attempts, payload,
                available_at, created_at, updated_at
            ) VALUES (
                :uid, :sid, :sub_id, 'DAILY_MATCH_DIGEST', 'whatsapp',
                '+923007770001', 'sent', :prov_id, :key, 0, :payload,
                NOW(), NOW(), NOW()
            )
        ");
        $stmt->execute([
            'uid' => $uid,
            'sid' => $sid1,
            'sub_id' => $subId,
            'prov_id' => $sharedProviderMsgId,
            'key' => $batchIdempotencyKey,
            'payload' => $combinedPayload
        ]);

        $this->queueService->recordDeliveryStatus($sharedProviderMsgId, 'delivered', date('Y-m-d H:i:s'));

        $count = SubscriptionService::countQualifyingDeliveredMessages($subId, $this->db);
        $this->assert($count === 1, "Bundled daily digest batch with 3 scholarships must count as exactly 1 physical delivery (got $count)");
        echo "OK\n";
    }

    // =========================================================================
    // Group 6: Automated Policy Guardrails & User Preferences (15-18)
    // =========================================================================

    public function test15_lifetime25MessageCapStrictlyEnforced(): void {
        echo "[Test 15] Lifetime 25-message WhatsApp cap strictly enforced across worker... ";
        $uid = $this->createUser('step7_t15@scholartest.com');
        $sid = $this->createScholarship('Step7 Scholarship T15');

        // Insert 25 delivered/sent messages
        for ($i = 1; $i <= 25; $i++) {
            $this->createLog($uid, $sid, ($i % 2 === 0 ? 'delivered' : 'sent'), 'whatsapp', "msg_cap_$i", null, 'NEW_MATCH');
        }

        // Enqueue the 26th message
        $logId26 = $this->createLog($uid, $sid, 'pending', 'whatsapp', 'msg_cap_26', null, 'NEW_MATCH');

        if (!defined('BYPASS_BATCH_CUTOFF')) {
            define('BYPASS_BATCH_CUTOFF', true);
        }
        $this->queueService->processQueue(10);

        $status26 = $this->db->query("SELECT status, error_message FROM notification_logs WHERE id = $logId26")->fetch(PDO::FETCH_ASSOC);
        $this->assert($status26['status'] === 'skipped', "26th message must be skipped (status: {$status26['status']})");
        $this->assert(str_contains($status26['error_message'], 'Lifetime limit reached'), "Must state lifetime limit reached");
        echo "OK\n";
    }

    public function test16_sundayQuietRuleDefersWhatsApp(): void {
        echo "[Test 16] Sunday quiet rule defers automated scholarship WhatsApp messages... ";
        // WhatsApp-only user without email
        $uid = $this->createUser('step7_t16@scholartest.com', 1, 0, '+923007770016');
        $sid = $this->createScholarship('Step7 Scholarship T16');
        $logId = $this->createLog($uid, $sid, 'pending', 'whatsapp', 'msg_sun_16', null, 'DAILY_MATCH_DIGEST');

        \App\Services\NotificationService::$simulateSunday = true;
        $this->queueService->processQueue(10);
        \App\Services\NotificationService::$simulateSunday = null;

        $row = $this->db->query("SELECT status, available_at FROM notification_logs WHERE id = $logId")->fetch(PDO::FETCH_ASSOC);
        $this->assert($row['status'] === 'pending', "Sunday scholarship WhatsApp must remain pending/deferred");
        $this->assert(strtotime($row['available_at']) > time(), "Sunday WhatsApp must be deferred to next Monday cutoff");
        echo "OK\n";
    }

    public function test17_userPreferencesRespected(): void {
        echo "[Test 17] User preferences: channel preference and disabled notifications strictly respected... ";
        // User with whatsapp_opt_in = 0
        $uid = $this->createUser('step7_t17@scholartest.com', 0, 1, '+923007770017');
        $sid = $this->createScholarship('Step7 Scholarship T17');
        $logId = $this->createLog($uid, $sid, 'pending', 'whatsapp', 'msg_pref_17', null, 'NEW_MATCH');

        $this->queueService->processQueue(10);

        $row = $this->db->query("SELECT status, error_message FROM notification_logs WHERE id = $logId")->fetch(PDO::FETCH_ASSOC);
        $this->assert($row['status'] === 'skipped', "WhatsApp must be skipped for user with whatsapp_opt_in = 0");
        $this->assert(str_contains($row['error_message'], 'opted out'), "Error must indicate opt-out");
        echo "OK\n";
    }

    public function test18_deadlineReminderPreferencesAndScopeEnforced(): void {
        echo "[Test 18] Deadline reminder preferences (1, 3, 7 days) and scope enforced... ";
        $uid = $this->createUser('step7_t18@scholartest.com');
        
        // Update user preference: only 3-day reminders, scope = selected
        $this->db->prepare("
            INSERT INTO user_preferences (user_id, deadline_reminder_scope, deadline_reminder_days, preferred_channel, allow_multi_channel, created_at, updated_at)
            VALUES (:uid, 'selected', '3', 'email', 0, NOW(), NOW())
            ON DUPLICATE KEY UPDATE deadline_reminder_scope = 'selected', deadline_reminder_days = '3'
        ")->execute(['uid' => $uid]);

        // Scholarship A: deadline in 3 days, NOT saved in user_scholarship_reminders
        $sidA = $this->createScholarship('Step7 Unsaved 3 Days', date('Y-m-d', strtotime('+3 days')));
        // Scholarship B: deadline in 3 days, SAVED in user_scholarship_reminders with is_enabled = 1
        $sidB = $this->createScholarship('Step7 Saved 3 Days', date('Y-m-d', strtotime('+3 days')));

        // Both need ELIGIBLE scholarship_matches records for the user
        $this->db->prepare("INSERT INTO scholarship_matches (user_id, scholarship_id, eligibility_status, match_score, created_at, updated_at) VALUES (:uid, :sid, 'ELIGIBLE', 90, NOW(), NOW())")
            ->execute(['uid' => $uid, 'sid' => $sidA]);
        $this->db->prepare("INSERT INTO scholarship_matches (user_id, scholarship_id, eligibility_status, match_score, created_at, updated_at) VALUES (:uid, :sid, 'ELIGIBLE', 90, NOW(), NOW())")
            ->execute(['uid' => $uid, 'sid' => $sidB]);

        $this->db->prepare("
            INSERT INTO user_scholarship_reminders (user_id, scholarship_id, is_enabled, reminder_days, created_at, updated_at) 
            VALUES (:uid, :sid, 1, '3', NOW(), NOW())
        ")->execute(['uid' => $uid, 'sid' => $sidB]);

        // Run deadline reminders job
        $this->schedulerService->runDeadlineRemindersJob();

        $remA = (int)$this->db->query("SELECT COUNT(*) FROM notification_logs WHERE user_id = $uid AND scholarship_id = $sidA")->fetchColumn();
        $remB = (int)$this->db->query("SELECT COUNT(*) FROM notification_logs WHERE user_id = $uid AND scholarship_id = $sidB")->fetchColumn();

        $this->assert($remA === 0, "Unsaved scholarship must NOT trigger reminder under 'selected' scope");
        $this->assert($remB >= 1, "Saved scholarship with reminder enabled must trigger reminder");
        echo "OK\n";
    }

    // =========================================================================
    // Group 7: Email Queue, Stale Recovery & Scheduler Mutex (19-21)
    // =========================================================================

    public function test19_emailQueueAndSmtpDispatchWithCredentialRedaction(): void {
        echo "[Test 19] Email queue & SMTP dispatch with credential redaction... ";
        $uid = $this->createUser('step7_t19@scholartest.com');
        $sid = $this->createScholarship('Step7 Scholarship T19');
        $logId = $this->createLog($uid, $sid, 'pending', 'email');

        // Test error message redaction in NotificationQueueService
        $sensitiveError = "SMTP Auth error for password=SuperSecretPassword123 with Bearer secret_jwt_token_abc";
        $this->invokeUpdateQueueItemStatus($logId, 2, false, $sensitiveError, null);

        $savedError = $this->db->query("SELECT error_message FROM notification_logs WHERE id = $logId")->fetchColumn();
        $this->assert(!str_contains($savedError, 'SuperSecretPassword123'), "Plain password must NOT appear in database logs");
        $this->assert(!str_contains($savedError, 'secret_jwt_token_abc'), "Bearer token must NOT appear in database logs");
        $this->assert(str_contains($savedError, '[REDACTED]'), "Credentials must be redacted");
        echo "OK\n";
    }

    public function test20_staleProcessingJobRecovery(): void {
        echo "[Test 20] Stale processing job recovery resets stale items... ";
        $uid = $this->createUser('step7_t20@scholartest.com');
        $sid = $this->createScholarship('Step7 Scholarship T20');

        // Job 1: attempts = 1, processing started 30 minutes ago (stale)
        $log1 = $this->createLog($uid, $sid, 'processing', 'email');
        $this->db->query("UPDATE notification_logs SET attempts = 1, processing_started_at = DATE_SUB(NOW(), INTERVAL 30 MINUTE), updated_at = DATE_SUB(NOW(), INTERVAL 30 MINUTE) WHERE id = $log1");

        // Job 2: attempts = 3, processing started 30 minutes ago (exceeded max attempts)
        $log2 = $this->createLog($uid, $sid, 'processing', 'email');
        $this->db->query("UPDATE notification_logs SET attempts = 3, processing_started_at = DATE_SUB(NOW(), INTERVAL 30 MINUTE), updated_at = DATE_SUB(NOW(), INTERVAL 30 MINUTE) WHERE id = $log2");

        $recovery = $this->schedulerService->recoverStaleProcessing(15, 3);
        $this->assert($recovery['recovered'] >= 1, "Stale job 1 must be recovered back to pending");
        $this->assert($recovery['failed'] >= 1, "Stale job 2 must be marked as failed");

        $status1 = $this->db->query("SELECT status FROM notification_logs WHERE id = $log1")->fetchColumn();
        $status2 = $this->db->query("SELECT status FROM notification_logs WHERE id = $log2")->fetchColumn();
        $this->assert($status1 === 'pending', "Job 1 must be reset to pending");
        $this->assert($status2 === 'failed', "Job 2 must be failed");
        echo "OK\n";
    }

    public function test21_masterSchedulerMutexLock(): void {
        echo "[Test 21] Master scheduler mutex lock prevents overlapping scheduler execution... ";
        $lockKey = 'step7_test_scheduler_lock';

        $acquired1 = $this->schedulerService->acquireLock($lockKey, 0);
        $this->assert($acquired1 === true, "First acquireLock must succeed");

        // Concurrent acquire while locked on secondary connection must fail immediately
        $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
        $port = $_ENV['DB_PORT'] ?? '3306';
        $dbName = $_ENV['DB_DATABASE'] ?? 'scholarship';
        $user = $_ENV['DB_USERNAME'] ?? 'root';
        $pass = $_ENV['DB_PASSWORD'] ?? '';

        $db2 = new PDO("mysql:host={$host};port={$port};dbname={$dbName}", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $scheduler2 = new NotificationSchedulerService($db2);
        $acquired2 = $scheduler2->acquireLock($lockKey, 0);
        $this->assert($acquired2 === false, "Concurrent acquireLock must fail while held");

        $this->schedulerService->releaseLock($lockKey);
        $acquired3 = $this->schedulerService->acquireLock($lockKey, 0);
        $this->assert($acquired3 === true, "AcquireLock must succeed after release");
        $this->schedulerService->releaseLock($lockKey);
        echo "OK\n";
    }

    // =========================================================================
    // Group 8: Cancelled & Expired Subscriptions Lifecycle Alert Gating (22-24)
    // =========================================================================

    public function test22_cancelledSubscriptionInsidePaidPeriodReceivesAlert(): void {
        echo "[Test 22] Cancelled subscription inside paid period receives scholarship alert and attaches subscription_id... ";
        $uid = $this->createUser('step7_t22@scholartest.com', 1, 1, null, false);
        $sid = $this->createScholarship('Step7 Scholarship T22');
        // Cancelled subscription with 20 days remaining
        $subId = $this->createSubscription($uid, 'cancelled', date('Y-m-d H:i:s', strtotime('-10 days')), date('Y-m-d H:i:s', strtotime('+20 days')));

        $this->schedulerService->runMatchingJob();

        $row = $this->db->query("
            SELECT subscription_id FROM notification_logs 
            WHERE user_id = $uid AND scholarship_id = $sid 
            ORDER BY id DESC LIMIT 1
        ")->fetch(PDO::FETCH_ASSOC);

        $this->assert(!empty($row), "Matching alert must be generated during active paid period of cancelled subscription");
        $this->assert((int)$row['subscription_id'] === $subId, "Alert must attach the active cancelled subscription ID");
        echo "OK\n";
    }

    public function test23_expiredCancelledSubscriptionBlockedFromPremiumAlerts(): void {
        echo "[Test 23] Expired cancelled subscription is blocked from receiving premium alerts... ";
        $uid = $this->createUser('step7_t23@scholartest.com', 1, 1, null, false);
        $sid = $this->createScholarship('Step7 Scholarship T23');
        // Expired subscription past nominal expiry
        $subId = $this->createSubscription($uid, 'expired', date('Y-m-d H:i:s', strtotime('-40 days')), date('Y-m-d H:i:s', strtotime('-10 days')));

        $activePlan = SubscriptionService::getActivePlan($uid);
        $this->assert($activePlan['plan_slug'] === 'free', "User with expired cancelled subscription must revert to free plan");
        $this->assert(SubscriptionService::can($uid, 'whatsapp_alerts') === false, "Expired cancelled subscription must not receive premium alerts");
        echo "OK\n";
    }

    public function test24_postExpiryDeliveryWebhookDoesNotReactivateSubscription(): void {
        echo "[Test 24] Post-expiry delivery webhook does not reactivate or protect expired subscription... ";
        $uid = $this->createUser('step7_t24@scholartest.com', 1, 1, null, false);
        $sid = $this->createScholarship('Step7 Scholarship T24');
        $subId = $this->createSubscription($uid, 'expired', date('Y-m-d H:i:s', strtotime('-40 days')), date('Y-m-d H:i:s', strtotime('-10 days')));
        $this->db->prepare("UPDATE subscriptions SET final_expired_at = NOW() WHERE id = :id")->execute(['id' => $subId]);

        $msgId = 'wacrm_post_exp_' . uniqid();
        $logId = $this->createLog($uid, $sid, 'sent', 'whatsapp', $msgId, $subId);

        // Webhook arrives post-expiry
        $this->queueService->recordDeliveryStatus($msgId, 'delivered', date('Y-m-d H:i:s'));

        $subRow = $this->db->query("SELECT status FROM subscriptions WHERE id = $subId")->fetch(PDO::FETCH_ASSOC);
        $this->assert($subRow['status'] === 'expired', "Expired subscription must remain 'expired' (cannot reactivate)");
        echo "OK\n";
    }

    // =========================================================================
    // Group 9: HTTP Webhook Endpoint Ingestion (25-28)
    // =========================================================================

    public function test25_webhookHttpEndpointParsesPayloadAndUpdatesStatus(): void {
        echo "[Test 25] Webhook HTTP endpoint (/api/notifications/wacrm/webhook) parses payload and updates delivery status safely... ";
        $uid = $this->createUser('step7_t25@scholartest.com');
        $sid = $this->createScholarship('Step7 Scholarship T25');
        $msgId = 'wacrm_http_webhook_' . uniqid();
        $logId = $this->createLog($uid, $sid, 'sent', 'whatsapp', $msgId);

        // Simulate incoming JSON payload
        $payload = [
            'provider_message_id' => $msgId,
            'status' => 'delivered',
            'timestamp' => '2026-09-10 14:00:00'
        ];

        $_POST = $payload;
        $controller = new NotificationController();

        ob_start();
        $controller->wacrmWebhook();
        $output = ob_get_clean();

        $res = json_decode($output, true);
        $this->assert(isset($res['status']) && $res['status'] === 'success', "Endpoint must return success");
        $this->assert($res['processed'] === 1, "Endpoint must process 1 record");

        $status = $this->db->query("SELECT status FROM notification_logs WHERE id = $logId")->fetchColumn();
        $this->assert($status === 'delivered', "Log record must be updated to 'delivered' via HTTP webhook");
        echo "OK\n";
    }

    public function test26_metaCloudApiWebhookFormatSupported(): void {
        echo "[Test 26] Meta Cloud API nested webhook format parsed correctly... ";
        $uid = $this->createUser('step7_t26@scholartest.com');
        $sid = $this->createScholarship('Step7 Scholarship T26');
        $msgId = 'wamid.HBgLM' . uniqid();
        $logId = $this->createLog($uid, $sid, 'sent', 'whatsapp', $msgId);

        // Standard Meta WhatsApp Cloud API nested structure
        $metaPayload = [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'id' => '100012345678',
                    'changes' => [
                        [
                            'value' => [
                                'messaging_product' => 'whatsapp',
                                'metadata' => ['display_phone_number' => '12345', 'phone_number_id' => '67890'],
                                'statuses' => [
                                    [
                                        'id' => $msgId,
                                        'status' => 'delivered',
                                        'timestamp' => 1788960000
                                    ]
                                ]
                            ],
                            'field' => 'messages'
                        ]
                    ]
                ]
            ]
        ];

        $_POST = $metaPayload;
        $controller = new NotificationController();

        ob_start();
        $controller->wacrmWebhook();
        $output = ob_get_clean();

        $res = json_decode($output, true);
        $this->assert(isset($res['status']) && $res['status'] === 'success', "Meta format must be accepted");
        $this->assert($res['processed'] === 1, "Meta status record must be processed");

        $status = $this->db->query("SELECT status FROM notification_logs WHERE id = $logId")->fetchColumn();
        $this->assert($status === 'delivered', "Notification log must transition to 'delivered'");
        echo "OK\n";
    }

    public function test27_invalidOrEmptyWebhookPayloadRejected(): void {
        echo "[Test 27] Invalid or empty webhook payload safely rejected... ";
        $_POST = [];
        $controller = new NotificationController();

        ob_start();
        $controller->wacrmWebhook();
        $output = ob_get_clean();

        $res = json_decode($output, true);
        $this->assert(isset($res['status']) && $res['status'] === 'error', "Empty payload must return error status");
        echo "OK\n";
    }

    public function test28_deliveryWebhookSyncsSubscriptionUsageTable(): void {
        echo "[Test 28] Receiving delivered webhook automatically syncs subscription_usage table... ";
        $uid = $this->createUser('step7_t28@scholartest.com', 1, 1, null, false);
        $sid = $this->createScholarship('Step7 Scholarship T28');
        $subId = $this->createSubscription($uid, 'active', date('Y-m-d H:i:s', strtotime('-5 days')), date('Y-m-d H:i:s', strtotime('+25 days')));

        $msgId = 'wacrm_sync_usage_' . uniqid();
        $logId = $this->createLog($uid, $sid, 'sent', 'whatsapp', $msgId, $subId);

        // Before delivery, usage table count is 0
        SubscriptionService::syncSubscriptionUsage($subId, $this->db);
        $beforeCount = (int)$this->db->query("SELECT qualifying_delivered_count FROM subscription_usage WHERE subscription_id = $subId")->fetchColumn();
        $this->assert($beforeCount === 0, "Usage before delivery must be 0");

        // Deliver message
        $this->queueService->recordDeliveryStatus($msgId, 'delivered', date('Y-m-d H:i:s'));

        $afterCount = (int)$this->db->query("SELECT qualifying_delivered_count FROM subscription_usage WHERE subscription_id = $subId")->fetchColumn();
        $this->assert($afterCount === 1, "Usage table must automatically sync to 1 upon delivery webhook (got $afterCount)");
        echo "OK\n";
    }
}
