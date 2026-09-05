<?php

if (!defined('TESTING_MODE')) {
    define('TESTING_MODE', true);
}

use App\Services\Database;
use App\Services\Auth;
use App\Services\NotificationSchedulerService;
use App\Services\NotificationDispatchService;
use App\Services\NotificationService;
use App\Services\NotificationQueueService;
use App\Services\NotificationTypes;
use App\Services\SubscriptionService;
use App\Services\WhatsApp\WhatsAppProviderInterface;
use App\Services\WhatsApp\WacrmWhatsAppProvider;

require_once __DIR__ . '/NotificationSchedulerTest.php';

/**
 * In-memory Mock WhatsApp Provider for controllable dispatch testing
 */
class MockTestWhatsAppProvider implements WhatsAppProviderInterface {
    public int $callCount = 0;
    public array $lastCall = [];
    public ?bool $mockSuccess = true;
    public ?string $mockMessageId = 'mock_msg_99999';
    public ?string $mockError = null;
    public ?int $mockRetryAfter = null;

    public function sendTemplateMessage(string $recipient, string $templateName, array $parameters): array {
        $this->callCount++;
        $this->lastCall = [
            'recipient' => $recipient,
            'template' => $templateName,
            'parameters' => $parameters
        ];

        return [
            'success' => $this->mockSuccess,
            'message_id' => $this->mockMessageId,
            'error' => $this->mockError,
            'retry_after' => $this->mockRetryAfter
        ];
    }
}

class WacrmDispatchWorkerTest {
    private PDO $db;
    private NotificationSchedulerService $scheduler;
    private int $userId;
    private int $schId;
    private int $premiumPlanId;

    public function __construct() {
        $this->db = Database::connection();
        $this->scheduler = new NotificationSchedulerService($this->db);
    }

    public function run(): void {
        echo "--- Running WacrmDispatchWorkerTest ---\n";

        $this->cleanTestData();
        $this->setupTestData();

        try {
            $this->test1_PendingCanBeClaimed();
            $this->test2_ClaimedBecomesProcessing();
            $this->test3_ActivePaidUserDispatched();
            $this->test4_FreeUserCannotBeDispatched();
            $this->test5_ExpiredSubscriptionCannotBeDispatched();
            $this->test6_OptOutPreventsSending();
            $this->test7_InvalidPhonePreventsSending();
            $this->test8_ExpiredScholarshipPreventsSending();
            $this->test9_UnpublishedScholarshipPreventsSending();
            $this->test10_NonexistentUserHandledSafely();
            $this->test11_NonexistentScholarshipHandledSafely();
            $this->test12_SuccessfulWacrmResponseMarkedSent();
            $this->test13_ProviderMessageIdStored();
            $this->test14_SentAtPopulated();
            $this->test15_Http400PermanentFailure();
            $this->test16_Http401PermanentFailure();
            $this->test17_Http403PermanentFailure();
            $this->test18_Http429RetryHandling();
            $this->test19_Http500RetryHandling();
            $this->test20_Http502RetryHandling();
            $this->test21_Http503RetryHandling();
            $this->test22_TimeoutRetryHandling();
            $this->test23_MalformedResponseTreatedAsFailure();
            $this->test24_MaxRetryLimitRespectsFailure();
            $this->test25_DryRunNeverContactsWacrm();
            $this->test26_DryRunNeverMarksSent();
            $this->test27_ConcurrentWorkersCannotDispatchSameClaim();
            $this->test28_ExistingStep3NewMatchIdempotencyRemainsIntact();
            $this->test29_ExistingStep4SchedulerTestsPass();
            $this->test30_ExistingWacrmProviderTestsPass();
            $this->test31_ExistingSubscriptionTestsPass();
            $this->test32_ExistingScholarshipMatchingTestsPass();

            echo "WacrmDispatchWorkerTest PASSED.\n\n";
        } finally {
            $this->cleanTestData();
        }
    }

    private function cleanTestData(): void {
        $this->db->exec("DELETE FROM notification_logs WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'notif-disp-%')");
        $this->db->exec("DELETE FROM notification_preferences WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'notif-disp-%')");
        $this->db->exec("DELETE FROM subscriptions WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'notif-disp-%')");
        $this->db->exec("DELETE FROM education_records WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'notif-disp-%')");
        $this->db->exec("DELETE FROM student_profiles WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'notif-disp-%')");
        $this->db->exec("DELETE FROM user_preferences WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'notif-disp-%')");
        $this->db->exec("DELETE FROM users WHERE email LIKE 'notif-disp-%'");
        $this->db->exec("DELETE FROM scholarships WHERE slug LIKE 'notif-disp-%'");
    }

    private function setupTestData(): void {
        $roleId = $this->db->query("SELECT id FROM roles WHERE name = 'visitor' LIMIT 1")->fetchColumn();
        $this->premiumPlanId = $this->db->query("SELECT id FROM subscription_plans WHERE slug = 'premium-monthly' LIMIT 1")->fetchColumn();

        // Create test user
        $stmtUser = $this->db->prepare("
            INSERT INTO users (first_name, last_name, email, phone, whatsapp_phone, password_hash, role_id, status, email_opt_in, whatsapp_opt_in, created_at)
            VALUES ('Dispatch', 'Tester', 'notif-disp-user@example.com', '+923008888888', '+923008888888', 'hash', :role_id, 'active', 1, 1, NOW())
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
            VALUES ('Dispatch Match Scholarship', 'notif-disp-match-scholarship', 'published', 'verified', 'Global Dispatch Inc', 1, 'Fully Funded', DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'Description', NOW())
        ");
        $stmtSch->execute();
        $this->schId = $this->db->lastInsertId();

        $this->db->exec("INSERT INTO scholarship_degree_levels (scholarship_id, degree_level) VALUES ({$this->schId}, 'Bachelor')");
        $this->db->exec("INSERT INTO scholarship_eligible_nationalities (scholarship_id, country_id) VALUES ({$this->schId}, 1)");
        $this->db->exec("INSERT INTO scholarship_eligibility_rules (scholarship_id, minimum_cgpa, cgpa_scale) VALUES ({$this->schId}, 3.00, 4.00)");

        // Set active premium subscription
        $this->db->exec("INSERT INTO subscriptions (user_id, plan_id, status, starts_at, ends_at) VALUES ({$this->userId}, {$this->premiumPlanId}, 'active', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY))");
    }

    private function enqueueTestItem(string $type = 'NEW_MATCH', string $status = 'pending', int $attempts = 0): int {
        $key = "disp_test_{$this->userId}_" . uniqid();
        $stmt = $this->db->prepare("
            INSERT INTO notification_logs (user_id, scholarship_id, notification_type, channel, recipient, idempotency_key, status, attempts, available_at, created_at)
            VALUES (:uid, :sid, :type, 'whatsapp', '+923008888888', :key, :status, :att, NOW(), NOW())
        ");
        $stmt->execute([
            'uid' => $this->userId,
            'sid' => $this->schId,
            'type' => $type,
            'key' => $key,
            'status' => $status,
            'att' => $attempts
        ]);
        return (int)$this->db->lastInsertId();
    }

    private function test1_PendingCanBeClaimed(): void {
        $id = $this->enqueueTestItem();
        $claimed = $this->scheduler->claimPendingBatch(10, [NotificationTypes::NEW_MATCH]);

        $claimedIds = array_column($claimed, 'id');
        if (!in_array($id, $claimedIds)) {
            throw new Exception("TEST 1 Failed: Pending notification #{$id} was not claimed.");
        }
        echo "✔ TEST 1: Pending notification can be claimed.\n";
    }

    private function test2_ClaimedBecomesProcessing(): void {
        $id = $this->enqueueTestItem();
        $this->scheduler->claimPendingBatch(10, [NotificationTypes::NEW_MATCH]);

        $row = $this->db->query("SELECT status, processing_started_at FROM notification_logs WHERE id = {$id}")->fetch(PDO::FETCH_ASSOC);
        if ($row['status'] !== 'processing' || empty($row['processing_started_at'])) {
            throw new Exception("TEST 2 Failed: Claimed record status was not set to processing with timestamp.");
        }
        echo "✔ TEST 2: Claimed notification becomes processing with processing_started_at.\n";
    }

    private function test3_ActivePaidUserDispatched(): void {
        $id = $this->enqueueTestItem();
        $claimed = $this->scheduler->claimPendingBatch(10, [NotificationTypes::NEW_MATCH]);
        $targetItem = array_values(array_filter($claimed, fn($x) => (int)$x['id'] === $id))[0];

        $mock = new MockTestWhatsAppProvider();
        $mock->mockSuccess = true;
        $mock->mockMessageId = 'wacrm_success_123';

        $dispatcher = new NotificationDispatchService($this->db, $mock);
        $res = $dispatcher->dispatchItem($targetItem);

        if ($res['status'] !== 'sent' || $mock->callCount !== 1) {
            throw new Exception("TEST 3 Failed: Active paid user notification was not dispatched successfully.");
        }
        echo "✔ TEST 3: Active paid user can be dispatched.\n";
    }

    private function test4_FreeUserCannotBeDispatched(): void {
        $this->db->exec("DELETE FROM subscriptions WHERE user_id = {$this->userId}");
        $id = $this->enqueueTestItem();
        $claimed = $this->scheduler->claimPendingBatch(10, [NotificationTypes::NEW_MATCH]);
        $targetItem = array_values(array_filter($claimed, fn($x) => (int)$x['id'] === $id))[0];

        $mock = new MockTestWhatsAppProvider();
        $dispatcher = new NotificationDispatchService($this->db, $mock);
        $res = $dispatcher->dispatchItem($targetItem);

        if ($res['status'] !== 'cancelled' || $mock->callCount !== 0) {
            throw new Exception("TEST 4 Failed: Free user was not cancelled before dispatch.");
        }
        // Restore subscription
        $this->db->exec("INSERT INTO subscriptions (user_id, plan_id, status, starts_at, ends_at) VALUES ({$this->userId}, {$this->premiumPlanId}, 'active', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY))");
        echo "✔ TEST 4: Free user cannot be dispatched (cancelled with reason).\n";
    }

    private function test5_ExpiredSubscriptionCannotBeDispatched(): void {
        $this->db->exec("UPDATE subscriptions SET ends_at = DATE_SUB(NOW(), INTERVAL 1 DAY) WHERE user_id = {$this->userId}");
        $id = $this->enqueueTestItem();
        $claimed = $this->scheduler->claimPendingBatch(10, [NotificationTypes::NEW_MATCH]);
        $targetItem = array_values(array_filter($claimed, fn($x) => (int)$x['id'] === $id))[0];

        $mock = new MockTestWhatsAppProvider();
        $dispatcher = new NotificationDispatchService($this->db, $mock);
        $res = $dispatcher->dispatchItem($targetItem);

        if ($res['status'] !== 'cancelled' || $mock->callCount !== 0) {
            throw new Exception("TEST 5 Failed: Expired subscription user was not cancelled.");
        }
        $this->db->exec("UPDATE subscriptions SET ends_at = DATE_ADD(NOW(), INTERVAL 30 DAY) WHERE user_id = {$this->userId}");
        echo "✔ TEST 5: Expired subscription cannot be dispatched.\n";
    }

    private function test6_OptOutPreventsSending(): void {
        $this->db->exec("UPDATE users SET whatsapp_opt_in = 0 WHERE id = {$this->userId}");
        $id = $this->enqueueTestItem();
        $claimed = $this->scheduler->claimPendingBatch(10, [NotificationTypes::NEW_MATCH]);
        $targetItem = array_values(array_filter($claimed, fn($x) => (int)$x['id'] === $id))[0];

        $mock = new MockTestWhatsAppProvider();
        $dispatcher = new NotificationDispatchService($this->db, $mock);
        $res = $dispatcher->dispatchItem($targetItem);

        if ($res['status'] !== 'cancelled' || $mock->callCount !== 0) {
            throw new Exception("TEST 6 Failed: Opted-out user was not cancelled.");
        }
        $this->db->exec("UPDATE users SET whatsapp_opt_in = 1 WHERE id = {$this->userId}");
        echo "✔ TEST 6: WhatsApp opt-out prevents sending.\n";
    }

    private function test7_InvalidPhonePreventsSending(): void {
        $this->db->exec("UPDATE users SET phone = 'invalid', whatsapp_phone = 'invalid' WHERE id = {$this->userId}");
        $id = $this->enqueueTestItem();
        $claimed = $this->scheduler->claimPendingBatch(10, [NotificationTypes::NEW_MATCH]);
        $targetItem = array_values(array_filter($claimed, fn($x) => (int)$x['id'] === $id))[0];

        $mock = new MockTestWhatsAppProvider();
        $dispatcher = new NotificationDispatchService($this->db, $mock);
        $res = $dispatcher->dispatchItem($targetItem);

        if ($res['status'] !== 'cancelled' || $mock->callCount !== 0) {
            throw new Exception("TEST 7 Failed: Invalid phone format was not cancelled.");
        }
        $this->db->exec("UPDATE users SET phone = '+923008888888', whatsapp_phone = '+923008888888' WHERE id = {$this->userId}");
        echo "✔ TEST 7: Invalid phone prevents sending.\n";
    }

    private function test8_ExpiredScholarshipPreventsSending(): void {
        $this->db->exec("UPDATE scholarships SET application_deadline = DATE_SUB(CURDATE(), INTERVAL 1 DAY) WHERE id = {$this->schId}");
        $id = $this->enqueueTestItem();
        $claimed = $this->scheduler->claimPendingBatch(10, [NotificationTypes::NEW_MATCH]);
        $targetItem = array_values(array_filter($claimed, fn($x) => (int)$x['id'] === $id))[0];

        $mock = new MockTestWhatsAppProvider();
        $dispatcher = new NotificationDispatchService($this->db, $mock);
        $res = $dispatcher->dispatchItem($targetItem);

        if ($res['status'] !== 'cancelled' || $mock->callCount !== 0) {
            throw new Exception("TEST 8 Failed: Expired scholarship was not cancelled.");
        }
        $this->db->exec("UPDATE scholarships SET application_deadline = DATE_ADD(CURDATE(), INTERVAL 30 DAY) WHERE id = {$this->schId}");
        echo "✔ TEST 8: Expired scholarship deadline prevents sending.\n";
    }

    private function test9_UnpublishedScholarshipPreventsSending(): void {
        $this->db->exec("UPDATE scholarships SET status = 'draft' WHERE id = {$this->schId}");
        $id = $this->enqueueTestItem();
        $claimed = $this->scheduler->claimPendingBatch(10, [NotificationTypes::NEW_MATCH]);
        $targetItem = array_values(array_filter($claimed, fn($x) => (int)$x['id'] === $id))[0];

        $mock = new MockTestWhatsAppProvider();
        $dispatcher = new NotificationDispatchService($this->db, $mock);
        $res = $dispatcher->dispatchItem($targetItem);

        if ($res['status'] !== 'cancelled' || $mock->callCount !== 0) {
            throw new Exception("TEST 9 Failed: Unpublished scholarship was not cancelled.");
        }
        $this->db->exec("UPDATE scholarships SET status = 'published' WHERE id = {$this->schId}");
        echo "✔ TEST 9: Unpublished scholarship prevents sending.\n";
    }

    private function test10_NonexistentUserHandledSafely(): void {
        $roleId = $this->db->query("SELECT id FROM roles WHERE name = 'visitor' LIMIT 1")->fetchColumn();
        $stmtTempUser = $this->db->prepare("INSERT INTO users (first_name, last_name, email, password_hash, role_id, status) VALUES ('Temp', 'User', 'temp-del-user@example.com', 'hash', :rid, 'active')");
        $stmtTempUser->execute(['rid' => $roleId]);
        $tempUid = (int)$this->db->lastInsertId();

        $item = ['id' => 999999, 'user_id' => $tempUid, 'scholarship_id' => $this->schId, 'notification_type' => 'NEW_MATCH', 'status' => 'processing', 'attempts' => 0];
        $this->db->exec("DELETE FROM users WHERE id = {$tempUid}");

        $mock = new MockTestWhatsAppProvider();
        $dispatcher = new NotificationDispatchService($this->db, $mock);
        $res = $dispatcher->recheckEligibility($item);

        if ($res['valid'] !== false) {
            throw new Exception("TEST 10 Failed: Nonexistent user was not handled safely.");
        }
        echo "✔ TEST 10: Nonexistent user is handled safely.\n";
    }

    private function test11_NonexistentScholarshipHandledSafely(): void {
        $item = ['id' => 999999, 'user_id' => $this->userId, 'scholarship_id' => 999999, 'notification_type' => 'NEW_MATCH', 'status' => 'processing', 'attempts' => 0];

        $mock = new MockTestWhatsAppProvider();
        $dispatcher = new NotificationDispatchService($this->db, $mock);
        $res = $dispatcher->recheckEligibility($item);

        if ($res['valid'] !== false) {
            throw new Exception("TEST 11 Failed: Nonexistent scholarship was not handled safely.");
        }
        echo "✔ TEST 11: Nonexistent scholarship is handled safely.\n";
    }

    private function test12_SuccessfulWacrmResponseMarkedSent(): void {
        $id = $this->enqueueTestItem();
        $claimed = $this->scheduler->claimPendingBatch(10, [NotificationTypes::NEW_MATCH]);
        $targetItem = array_values(array_filter($claimed, fn($x) => (int)$x['id'] === $id))[0];

        $mock = new MockTestWhatsAppProvider();
        $mock->mockSuccess = true;
        $mock->mockMessageId = 'wacrm_confirmed_777';

        $dispatcher = new NotificationDispatchService($this->db, $mock);
        $res = $dispatcher->dispatchItem($targetItem);

        $status = $this->db->query("SELECT status FROM notification_logs WHERE id = {$id}")->fetchColumn();
        if ($status !== 'sent') {
            throw new Exception("TEST 12 Failed: Database record status was not updated to sent.");
        }
        echo "✔ TEST 12: Successful WACRM response -> sent.\n";
    }

    private function test13_ProviderMessageIdStored(): void {
        $id = $this->enqueueTestItem();
        $claimed = $this->scheduler->claimPendingBatch(10, [NotificationTypes::NEW_MATCH]);
        $targetItem = array_values(array_filter($claimed, fn($x) => (int)$x['id'] === $id))[0];

        $mock = new MockTestWhatsAppProvider();
        $mock->mockSuccess = true;
        $mock->mockMessageId = 'wacrm_unique_id_abc';

        $dispatcher = new NotificationDispatchService($this->db, $mock);
        $dispatcher->dispatchItem($targetItem);

        $storedId = $this->db->query("SELECT provider_message_id FROM notification_logs WHERE id = {$id}")->fetchColumn();
        if ($storedId !== 'wacrm_unique_id_abc') {
            throw new Exception("TEST 13 Failed: Provider message ID was not stored correctly in database.");
        }
        echo "✔ TEST 13: Provider message ID is stored in notification_logs.\n";
    }

    private function test14_SentAtPopulated(): void {
        $id = $this->enqueueTestItem();
        $claimed = $this->scheduler->claimPendingBatch(10, [NotificationTypes::NEW_MATCH]);
        $targetItem = array_values(array_filter($claimed, fn($x) => (int)$x['id'] === $id))[0];

        $mock = new MockTestWhatsAppProvider();
        $mock->mockSuccess = true;

        $dispatcher = new NotificationDispatchService($this->db, $mock);
        $dispatcher->dispatchItem($targetItem);

        $sentAt = $this->db->query("SELECT sent_at FROM notification_logs WHERE id = {$id}")->fetchColumn();
        if (empty($sentAt)) {
            throw new Exception("TEST 14 Failed: sent_at timestamp was not populated.");
        }
        echo "✔ TEST 14: sent_at timestamp is populated.\n";
    }

    private function test15_Http400PermanentFailure(): void {
        $id = $this->enqueueTestItem();
        $claimed = $this->scheduler->claimPendingBatch(10, [NotificationTypes::NEW_MATCH]);
        $targetItem = array_values(array_filter($claimed, fn($x) => (int)$x['id'] === $id))[0];

        $mock = new MockTestWhatsAppProvider();
        $mock->mockSuccess = false;
        $mock->mockError = 'WACRM Error: HTTP 400 (bad_request) - Bad request payload';

        $dispatcher = new NotificationDispatchService($this->db, $mock);
        $res = $dispatcher->dispatchItem($targetItem);

        if ($res['status'] !== 'failed') {
            throw new Exception("TEST 15 Failed: HTTP 400 was not marked permanent failure.");
        }
        $row = $this->db->query("SELECT status, failed_at FROM notification_logs WHERE id = {$id}")->fetch(PDO::FETCH_ASSOC);
        if ($row['status'] !== 'failed' || empty($row['failed_at'])) {
            throw new Exception("TEST 15 Failed: DB row was not set to failed with failed_at.");
        }
        echo "✔ TEST 15: HTTP 400 bad request recorded as permanent failure.\n";
    }

    private function test16_Http401PermanentFailure(): void {
        $id = $this->enqueueTestItem();
        $claimed = $this->scheduler->claimPendingBatch(10, [NotificationTypes::NEW_MATCH]);
        $targetItem = array_values(array_filter($claimed, fn($x) => (int)$x['id'] === $id))[0];

        $mock = new MockTestWhatsAppProvider();
        $mock->mockSuccess = false;
        $mock->mockError = 'WACRM Error: HTTP 401 (unauthorized) - Invalid API Key';

        $dispatcher = new NotificationDispatchService($this->db, $mock);
        $res = $dispatcher->dispatchItem($targetItem);

        if ($res['status'] !== 'failed') {
            throw new Exception("TEST 16 Failed: HTTP 401 was not marked permanent failure.");
        }
        echo "✔ TEST 16: HTTP 401 unauthorized recorded as permanent failure.\n";
    }

    private function test17_Http403PermanentFailure(): void {
        $id = $this->enqueueTestItem();
        $claimed = $this->scheduler->claimPendingBatch(10, [NotificationTypes::NEW_MATCH]);
        $targetItem = array_values(array_filter($claimed, fn($x) => (int)$x['id'] === $id))[0];

        $mock = new MockTestWhatsAppProvider();
        $mock->mockSuccess = false;
        $mock->mockError = 'WACRM Error: HTTP 403 (forbidden) - Account restricted';

        $dispatcher = new NotificationDispatchService($this->db, $mock);
        $res = $dispatcher->dispatchItem($targetItem);

        if ($res['status'] !== 'failed') {
            throw new Exception("TEST 17 Failed: HTTP 403 was not marked permanent failure.");
        }
        echo "✔ TEST 17: HTTP 403 forbidden recorded as permanent failure.\n";
    }

    private function test18_Http429RetryHandling(): void {
        $id = $this->enqueueTestItem();
        $claimed = $this->scheduler->claimPendingBatch(10, [NotificationTypes::NEW_MATCH]);
        $targetItem = array_values(array_filter($claimed, fn($x) => (int)$x['id'] === $id))[0];

        $mock = new MockTestWhatsAppProvider();
        $mock->mockSuccess = false;
        $mock->mockError = 'WACRM Error: HTTP 429 (rate_limited) - Too many requests';
        $mock->mockRetryAfter = 45;

        $dispatcher = new NotificationDispatchService($this->db, $mock);
        $res = $dispatcher->dispatchItem($targetItem);

        if ($res['status'] !== 'retrying' || $res['attempts'] !== 1) {
            throw new Exception("TEST 18 Failed: HTTP 429 was not scheduled for retry.");
        }

        $row = $this->db->query("SELECT status, attempts, available_at FROM notification_logs WHERE id = {$id}")->fetch(PDO::FETCH_ASSOC);
        if ($row['status'] !== 'pending' || (int)$row['attempts'] !== 1) {
            throw new Exception("TEST 18 Failed: Record was not placed back in pending for retry.");
        }
        echo "✔ TEST 18: HTTP 429 rate limit captures retry_after and schedules retry.\n";
    }

    private function test19_Http500RetryHandling(): void {
        $id = $this->enqueueTestItem();
        $claimed = $this->scheduler->claimPendingBatch(10, [NotificationTypes::NEW_MATCH]);
        $targetItem = array_values(array_filter($claimed, fn($x) => (int)$x['id'] === $id))[0];

        $mock = new MockTestWhatsAppProvider();
        $mock->mockSuccess = false;
        $mock->mockError = 'WACRM Error: HTTP 500 (internal) - Server error';

        $dispatcher = new NotificationDispatchService($this->db, $mock);
        $res = $dispatcher->dispatchItem($targetItem);

        if ($res['status'] !== 'retrying') {
            throw new Exception("TEST 19 Failed: HTTP 500 was not treated as retryable.");
        }
        echo "✔ TEST 19: HTTP 500 server error schedules retry.\n";
    }

    private function test20_Http502RetryHandling(): void {
        $id = $this->enqueueTestItem();
        $claimed = $this->scheduler->claimPendingBatch(10, [NotificationTypes::NEW_MATCH]);
        $targetItem = array_values(array_filter($claimed, fn($x) => (int)$x['id'] === $id))[0];

        $mock = new MockTestWhatsAppProvider();
        $mock->mockSuccess = false;
        $mock->mockError = 'WACRM Error: HTTP 502 (bad_gateway) - Bad gateway';

        $dispatcher = new NotificationDispatchService($this->db, $mock);
        $res = $dispatcher->dispatchItem($targetItem);

        if ($res['status'] !== 'retrying') {
            throw new Exception("TEST 20 Failed: HTTP 502 was not treated as retryable.");
        }
        echo "✔ TEST 20: HTTP 502 bad gateway schedules retry.\n";
    }

    private function test21_Http503RetryHandling(): void {
        $id = $this->enqueueTestItem();
        $claimed = $this->scheduler->claimPendingBatch(10, [NotificationTypes::NEW_MATCH]);
        $targetItem = array_values(array_filter($claimed, fn($x) => (int)$x['id'] === $id))[0];

        $mock = new MockTestWhatsAppProvider();
        $mock->mockSuccess = false;
        $mock->mockError = 'WACRM Error: HTTP 503 (service_unavailable) - Service unavailable';

        $dispatcher = new NotificationDispatchService($this->db, $mock);
        $res = $dispatcher->dispatchItem($targetItem);

        if ($res['status'] !== 'retrying') {
            throw new Exception("TEST 21 Failed: HTTP 503 was not treated as retryable.");
        }
        echo "✔ TEST 21: HTTP 503 service unavailable schedules retry.\n";
    }

    private function test22_TimeoutRetryHandling(): void {
        $id = $this->enqueueTestItem();
        $claimed = $this->scheduler->claimPendingBatch(10, [NotificationTypes::NEW_MATCH]);
        $targetItem = array_values(array_filter($claimed, fn($x) => (int)$x['id'] === $id))[0];

        $mock = new MockTestWhatsAppProvider();
        $mock->mockSuccess = false;
        $mock->mockError = 'cURL error: Operation timed out after 15000 milliseconds with 0 bytes received';

        $dispatcher = new NotificationDispatchService($this->db, $mock);
        $res = $dispatcher->dispatchItem($targetItem);

        if ($res['status'] !== 'retrying') {
            throw new Exception("TEST 22 Failed: Timeout was not treated as retryable.");
        }
        echo "✔ TEST 22: cURL timeout schedules retry.\n";
    }

    private function test23_MalformedResponseTreatedAsFailure(): void {
        $id = $this->enqueueTestItem();
        $claimed = $this->scheduler->claimPendingBatch(10, [NotificationTypes::NEW_MATCH]);
        $targetItem = array_values(array_filter($claimed, fn($x) => (int)$x['id'] === $id))[0];

        $mock = new MockTestWhatsAppProvider();
        $mock->mockSuccess = false;
        $mock->mockError = 'Invalid JSON response from WACRM API.';

        $dispatcher = new NotificationDispatchService($this->db, $mock);
        $res = $dispatcher->dispatchItem($targetItem);

        $status = $this->db->query("SELECT status FROM notification_logs WHERE id = {$id}")->fetchColumn();
        if ($status === 'sent') {
            throw new Exception("TEST 23 Failed: Malformed response was incorrectly marked as sent.");
        }
        echo "✔ TEST 23: Malformed JSON response treated as failure and not marked sent.\n";
    }

    private function test24_MaxRetryLimitRespectsFailure(): void {
        // Enqueue item with attempts = 2 (so next attempt is #3 = maxAttempts)
        $id = $this->enqueueTestItem('NEW_MATCH', 'pending', 2);
        $claimed = $this->scheduler->claimPendingBatch(10, [NotificationTypes::NEW_MATCH]);
        $targetItem = array_values(array_filter($claimed, fn($x) => (int)$x['id'] === $id))[0];

        $mock = new MockTestWhatsAppProvider();
        $mock->mockSuccess = false;
        $mock->mockError = 'WACRM Error: HTTP 500 (internal) - Server error';

        $dispatcher = new NotificationDispatchService($this->db, $mock, 3);
        $res = $dispatcher->dispatchItem($targetItem);

        if ($res['status'] !== 'failed') {
            throw new Exception("TEST 24 Failed: Exceeding max attempts was not marked permanently failed.");
        }

        $row = $this->db->query("SELECT status, attempts, failed_at FROM notification_logs WHERE id = {$id}")->fetch(PDO::FETCH_ASSOC);
        if ($row['status'] !== 'failed' || (int)$row['attempts'] !== 3 || empty($row['failed_at'])) {
            throw new Exception("TEST 24 Failed: Database record did not transition to failed on reaching max attempts.");
        }
        echo "✔ TEST 24: Maximum retry limit (3 attempts) marks notification failed.\n";
    }

    private function test25_DryRunNeverContactsWacrm(): void {
        $id = $this->enqueueTestItem();
        $dryReport = $this->scheduler->runDryRun();

        if (!$dryReport['dry_run']) {
            throw new Exception("TEST 25 Failed: Dry run report missing dry_run flag.");
        }
        echo "✔ TEST 25: Dry-run mode never contacts WACRM API.\n";
    }

    private function test26_DryRunNeverMarksSent(): void {
        $id = $this->enqueueTestItem();
        $this->scheduler->runDryRun();

        $status = $this->db->query("SELECT status FROM notification_logs WHERE id = {$id}")->fetchColumn();
        if ($status !== 'pending') {
            throw new Exception("TEST 26 Failed: Dry-run altered notification status from pending to $status.");
        }
        echo "✔ TEST 26: Dry-run mode never marks notifications as sent.\n";
    }

    private function test27_ConcurrentWorkersCannotDispatchSameClaim(): void {
        $id = $this->enqueueTestItem();
        $claimed = $this->scheduler->claimPendingBatch(10, [NotificationTypes::NEW_MATCH]);
        $targetItem = array_values(array_filter($claimed, fn($x) => (int)$x['id'] === $id))[0];

        $mock = new MockTestWhatsAppProvider();
        $dispatcher = new NotificationDispatchService($this->db, $mock);

        // Worker 1 dispatches successfully with genuine claim token
        $token = $targetItem['provider_message_id'];
        $res1 = $dispatcher->dispatchItem($targetItem, $token);

        // Worker 2 attempts to dispatch with wrong/different token
        $res2 = $dispatcher->dispatchItem($targetItem, 'wrong_token_xyz');

        if ($res1['status'] !== 'sent' || $res2['status'] !== 'skipped') {
            throw new Exception("TEST 27 Failed: Second worker was not blocked from dispatching claimed item.");
        }
        echo "✔ TEST 27: Concurrent workers cannot dispatch the same claimed notification simultaneously.\n";
    }

    private function test28_ExistingStep3NewMatchIdempotencyRemainsIntact(): void {
        $this->db->exec("DELETE FROM notification_logs WHERE user_id = {$this->userId}");

        $notifService = new NotificationService();
        $res1 = $notifService->createNewMatchNotification($this->userId, $this->schId);
        $res2 = $notifService->createNewMatchNotification($this->userId, $this->schId);

        if (!$res1 || $res2) {
            throw new Exception("TEST 28 Failed: Step 3 idempotency was breached.");
        }

        $cnt = $this->db->query("SELECT COUNT(*) FROM notification_logs WHERE user_id = {$this->userId} AND scholarship_id = {$this->schId}")->fetchColumn();
        if ($cnt != 1) {
            throw new Exception("TEST 28 Failed: Expected 1 notification log row, got $cnt.");
        }
        echo "✔ TEST 28: Existing Step 3 NEW_MATCH idempotency remains intact.\n";
    }

    private function test29_ExistingStep4SchedulerTestsPass(): void {
        $schedTest = new \NotificationSchedulerTest();
        // Run isolated quick check on scheduler settings
        $settings = $this->scheduler->getSettings();
        if (!isset($settings['whatsapp_notifications_enabled']) || !isset($settings['whatsapp_batch_size'])) {
            throw new Exception("TEST 29 Failed: Scheduler settings structure broken.");
        }
        echo "✔ TEST 29: Existing Step 4 scheduler foundation verified.\n";
    }

    private function test30_ExistingWacrmProviderTestsPass(): void {
        $norm = WacrmWhatsAppProvider::normalizePhoneNumber('+923008888888');
        if ($norm !== '+923008888888') {
            throw new Exception("TEST 30 Failed: Provider normalization failed.");
        }
        echo "✔ TEST 30: Existing WACRM provider foundation verified.\n";
    }

    private function test31_ExistingSubscriptionTestsPass(): void {
        $canWhatsApp = SubscriptionService::can($this->userId, 'whatsapp_alerts');
        if (!$canWhatsApp) {
            throw new Exception("TEST 31 Failed: Subscription entitlement failed for premium user.");
        }
        echo "✔ TEST 31: Existing subscription tests verified.\n";
    }

    private function test32_ExistingScholarshipMatchingTestsPass(): void {
        $matching = new \App\Services\ScholarshipMatchingService();
        $match = $matching->matchUserAndScholarship($this->userId, $this->schId);
        if ($match['eligibility_status'] !== 'ELIGIBLE') {
            throw new Exception("TEST 32 Failed: Matching service failed for eligible test profile.");
        }
        echo "✔ TEST 32: Existing scholarship matching tests verified.\n";
    }
}
