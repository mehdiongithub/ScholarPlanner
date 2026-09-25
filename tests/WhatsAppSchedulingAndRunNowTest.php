<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/WacrmWhatsAppProviderTest.php';

use App\Services\Database;
use App\Services\NotificationService;
use App\Services\NotificationQueueService;
use App\Services\NotificationSchedulerService;
use App\Services\WhatsApp\CurlMockRegistry;

class WhatsAppSchedulingAndRunNowTest {
    private PDO $db;
    private NotificationService $notificationService;
    private NotificationQueueService $queueService;
    private NotificationSchedulerService $schedulerService;
    private array $createdUserIds = [];

    private int $phoneSeq = 600000;

    public function __construct() {
        $this->db = Database::connection();
        $this->notificationService = new NotificationService();
        $this->queueService = new NotificationQueueService();
        $this->schedulerService = new NotificationSchedulerService($this->db);
        $this->phoneSeq = rand(200000, 800000);
    }

    private function cleanLeftovers(): void {
        $this->db->exec("DELETE FROM notification_logs WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'sched_%')");
        $this->db->exec("DELETE FROM notification_preferences WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'sched_%')");
        $this->db->exec("DELETE FROM user_preferences WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'sched_%')");
        $this->db->exec("DELETE FROM subscriptions WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'sched_%')");
        $this->db->exec("DELETE FROM users WHERE email LIKE 'sched_%'");
    }

    private function assert(bool $condition, string $message): void {
        if (!$condition) {
            throw new Exception("Assertion failed: " . $message);
        }
    }

    private function createTestUser(string $email, ?string $phone = null, bool $emailOptIn = true, bool $whatsappOptIn = true): int {
        if ($phone === null) {
            $phone = '+92300' . str_pad((string)(++$this->phoneSeq), 7, '0', STR_PAD_LEFT);
        }
        $roleId = (int)$this->db->query("SELECT id FROM roles WHERE name = 'visitor' LIMIT 1")->fetchColumn();
        if (!$roleId) {
            $roleId = (int)$this->db->query("SELECT id FROM roles LIMIT 1")->fetchColumn() ?: 1;
        }
        $stmt = $this->db->prepare("
            INSERT INTO users (role_id, email, phone, whatsapp_phone, first_name, last_name, email_opt_in, whatsapp_opt_in, status, password_hash, created_at, updated_at)
            VALUES (:role, :email, :phone, :wa_phone, 'Test', 'Student', :email_opt, :wa_opt, 'active', 'hash', NOW(), NOW())
        ");
        $stmt->execute([
            'role' => $roleId,
            'email' => $email,
            'phone' => $phone,
            'wa_phone' => $phone,
            'email_opt' => $emailOptIn ? 1 : 0,
            'wa_opt' => $whatsappOptIn ? 1 : 0
        ]);
        $uid = (int)$this->db->lastInsertId();
        $this->createdUserIds[] = $uid;

        // User preference
        $this->db->prepare("
            INSERT INTO user_preferences (user_id, preferred_channel, allow_multi_channel, created_at, updated_at)
            VALUES (:uid, 'whatsapp', 1, NOW(), NOW())
            ON DUPLICATE KEY UPDATE preferred_channel = 'whatsapp', allow_multi_channel = 1
        ")->execute(['uid' => $uid]);

        // Notification preferences
        $types = ['matching_scholarship_alerts', 'deadline_reminders', 'email_alerts', 'whatsapp_alerts'];
        foreach ($types as $t) {
            $this->db->prepare("
                INSERT INTO notification_preferences (user_id, notification_type, email_enabled, whatsapp_enabled, created_at, updated_at)
                VALUES (:uid, :ntype, :em_en, :wa_en, NOW(), NOW())
            ")->execute([
                'uid' => $uid,
                'ntype' => $t,
                'em_en' => $emailOptIn ? 1 : 0,
                'wa_en' => $whatsappOptIn ? 1 : 0
            ]);
        }

        // Active subscription
        $planId = (int)$this->db->query("SELECT id FROM subscription_plans LIMIT 1")->fetchColumn() ?: 1;
        $this->db->prepare("
            INSERT INTO subscriptions (user_id, plan_id, status, starts_at, ends_at, auto_renew)
            VALUES (:uid, :pid, 'active', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 1)
        ")->execute(['uid' => $uid, 'pid' => $planId]);

        return $uid;
    }

    public function run(): void {
        echo "=================================================================\n";
        echo " RUNNING WHATSAPP SCHEDULING AND RUN NOW TEST SUITE\n";
        echo "=================================================================\n\n";

        $this->cleanLeftovers();

        // Mock WACRM curl response
        CurlMockRegistry::reset();
        CurlMockRegistry::$response = json_encode(['data' => ['message_id' => 'msg_wacrm_test_' . uniqid()]]);
        CurlMockRegistry::$httpCode = 201;

        try {
            $this->test1_normalScheduleUsesMatchingSendTime();
            $this->test2_futureAvailableAtIsNotDispatchedEarly();
            $this->test3_runNowSetsAvailableAtNowAndDispatchesImmediately();
            $this->test4_runNowAcceleratesExistingPendingBatch();
            $this->test5_deduplicationMaintainsSingleDailyBatchWithMergedMatches();
            $this->test6_sundayDeferralUsesMatchingSendTimeToMonday();
            $this->test7_lifetime25CapPreserved();
            $this->test8_emailNotificationFlowUnbroken();

            echo "\n=================================================================\n";
            echo " ✔ ALL 8 WHATSAPP SCHEDULING AND RUN NOW TESTS PASSED!\n";
            echo "=================================================================\n";
        } finally {
            $this->cleanup();
        }
    }

    /**
     * Test 1: Normal Automatic Dispatch:
     * When enqueueDailyWhatsAppBatch is called for today, it must schedule for configured matching_send_time (e.g. 16:10 PKT),
     * NOT 23:59:59 and NOT 18:59:59.
     */
    public function test1_normalScheduleUsesMatchingSendTime(): void {
        echo "[Test 1] Normal schedule assigns configured matching_send_time (e.g. 16:10 PKT)... ";
        
        // Ensure settings have 16:10 in Asia/Karachi
        $this->db->query("REPLACE INTO settings (`key`, `value`, `type`, `group_name`, `is_public`) VALUES ('matching_send_time', '16:10', 'string', 'notifications_schedule', 1)");
        $this->db->query("REPLACE INTO settings (`key`, `value`, `type`, `group_name`, `is_public`) VALUES ('matching_timezone', 'Asia/Karachi', 'string', 'notifications_schedule', 1)");

        $uid = $this->createTestUser('sched_t1_' . uniqid() . '@example.com');
        $calendarDay = date('Y-m-d');
        $matches = [
            ['scholarship_id' => 901, 'title' => 'Test Merit Scholarship 1', 'deadline' => '2026-12-31']
        ];

        $ok = $this->notificationService->enqueueDailyWhatsAppBatch($uid, $matches, $calendarDay, null, null, false);
        $this->assert($ok === true, "Batch enqueue should succeed");

        $idempotencyKey = "scholarship_whatsapp_{$uid}_{$calendarDay}";
        $row = $this->db->query("SELECT available_at, status FROM notification_logs WHERE idempotency_key = '$idempotencyKey'")->fetch(PDO::FETCH_ASSOC);

        $this->assert(!empty($row), "Notification log record must exist");
        $this->assert($row['status'] === 'pending', "Status must be pending");

        $expectedAvailableAt = "{$calendarDay} 16:10:00";
        $this->assert($row['available_at'] === $expectedAvailableAt, "available_at must be '{$expectedAvailableAt}', got '{$row['available_at']}'");

        echo "PASS\n";
    }

    /**
     * Test 2: Future available_at remains pending and is NOT dispatched early by the queue worker.
     */
    public function test2_futureAvailableAtIsNotDispatchedEarly(): void {
        echo "[Test 2] Future available_at is NOT dispatched early by queue worker... ";

        $uid = $this->createTestUser('sched_t2_' . uniqid() . '@example.com');
        $calendarDay = date('Y-m-d');
        $futureTime = date('Y-m-d H:i:s', strtotime('+4 hours'));

        $this->db->prepare("
            INSERT INTO notification_logs (
                user_id, scholarship_id, notification_type, channel, provider,
                recipient, payload, idempotency_key, status, available_at, attempts, created_at, updated_at
            ) VALUES (
                :uid, NULL, 'DAILY_MATCH_DIGEST', 'whatsapp', 'wacrm',
                '+923251371826', '{\"matches\":[]}', :key, 'pending', :avail, 0, NOW(), NOW()
            )
        ")->execute([
            'uid' => $uid,
            'key' => "scholarship_whatsapp_{$uid}_{$calendarDay}",
            'avail' => $futureTime
        ]);

        // Process queue - worker MUST NOT dispatch future item
        $dispatched = $this->queueService->processQueue(10);

        $row = $this->db->query("SELECT status, available_at FROM notification_logs WHERE idempotency_key = 'scholarship_whatsapp_{$uid}_{$calendarDay}'")->fetch(PDO::FETCH_ASSOC);
        $this->assert($row['status'] === 'pending', "Status must remain pending for future available_at");
        $this->assert($row['available_at'] === $futureTime, "available_at must remain unchanged in the future");

        echo "PASS\n";
    }

    /**
     * Test 3: Run Now assigns available_at <= NOW() and queue worker dispatches it immediately via WACRM without waiting.
     */
    public function test3_runNowSetsAvailableAtNowAndDispatchesImmediately(): void {
        echo "[Test 3] Run Now assigns available_at <= NOW() and worker dispatches immediately... ";

        $uid = $this->createTestUser('sched_t3_' . uniqid() . '@example.com');
        $calendarDay = date('Y-m-d');
        $matches = [
            ['scholarship_id' => 903, 'title' => 'Test Immediate Scholarship', 'deadline' => '2026-12-31']
        ];

        // Enqueue with isRunNow = true
        $ok = $this->notificationService->enqueueDailyWhatsAppBatch($uid, $matches, $calendarDay, null, null, true);
        $this->assert($ok === true, "Batch enqueue with isRunNow should succeed");

        $idempotencyKey = "scholarship_whatsapp_{$uid}_{$calendarDay}";
        $row = $this->db->query("SELECT available_at, status FROM notification_logs WHERE idempotency_key = '$idempotencyKey'")->fetch(PDO::FETCH_ASSOC);
        
        $this->assert($row['status'] === 'pending', "Status should initially be pending");
        $this->assert(strtotime($row['available_at']) <= time() + 5, "available_at must be <= NOW(), got '{$row['available_at']}'");

        // Worker immediately executes
        $dispatched = $this->queueService->processQueue(10);
        $this->assert($dispatched >= 1, "Queue worker must dispatch the Run Now item immediately");

        $afterRow = $this->db->query("SELECT status, attempts, error_message, provider_message_id FROM notification_logs WHERE idempotency_key = '$idempotencyKey'")->fetch(PDO::FETCH_ASSOC);
        $this->assert($afterRow['status'] === 'sent', "Status must transition to 'sent' (got '{$afterRow['status']}', error: '{$afterRow['error_message']}')");
        $this->assert((int)$afterRow['attempts'] === 1, "Attempts must be 1");
        $this->assert(!empty($afterRow['provider_message_id']), "provider_message_id must be populated from WACRM");

        echo "PASS\n";
    }

    /**
     * Test 4: Run Now accelerates an existing batch that was previously pending for a future time.
     */
    public function test4_runNowAcceleratesExistingPendingBatch(): void {
        echo "[Test 4] Run Now accelerates existing future pending batch to NOW()... ";

        $uid = $this->createTestUser('sched_t4_' . uniqid() . '@example.com');
        $calendarDay = date('Y-m-d');
        $futureTime = "{$calendarDay} 23:59:59";

        // Pre-insert an existing pending batch with a future available_at
        $idempotencyKey = "scholarship_whatsapp_{$uid}_{$calendarDay}";
        $payload = [
            'calendar_day' => $calendarDay,
            'match_count' => 1,
            'matches' => [
                ['scholarship_id' => 904, 'title' => 'Scheduled Scholarship', 'deadline' => '2026-11-30']
            ]
        ];
        $this->db->prepare("
            INSERT INTO notification_logs (
                user_id, scholarship_id, notification_type, channel, provider,
                recipient, payload, idempotency_key, status, available_at, attempts, created_at, updated_at
            ) VALUES (
                :uid, NULL, 'DAILY_MATCH_DIGEST', 'whatsapp', 'wacrm',
                '+923251371826', :payload, :key, 'pending', :avail, 0, NOW(), NOW()
            )
        ")->execute([
            'uid' => $uid,
            'payload' => json_encode($payload),
            'key' => $idempotencyKey,
            'avail' => $futureTime
        ]);

        // Calling enqueueDailyWhatsAppBatch with isRunNow = true must accelerate available_at
        $ok = $this->notificationService->enqueueDailyWhatsAppBatch($uid, $payload['matches'], $calendarDay, null, null, true);
        $this->assert($ok === true, "Batch merge/acceleration must succeed");

        $row = $this->db->query("SELECT available_at, status FROM notification_logs WHERE idempotency_key = '$idempotencyKey'")->fetch(PDO::FETCH_ASSOC);
        $this->assert(strtotime($row['available_at']) <= time() + 5, "available_at must be updated to <= NOW()");

        // Worker dispatches immediately
        $dispatched = $this->queueService->processQueue(10);
        $this->assert($dispatched >= 1, "Queue worker must dispatch accelerated batch");

        $finalRow = $this->db->query("SELECT status FROM notification_logs WHERE idempotency_key = '$idempotencyKey'")->fetch(PDO::FETCH_ASSOC);
        $this->assert($finalRow['status'] === 'sent', "Status must transition to 'sent'");

        echo "PASS\n";
    }

    /**
     * Test 5: Deduplication: Exactly ONE daily batch record exists per user/calendar day.
     * New matches merge into the payload.
     */
    public function test5_deduplicationMaintainsSingleDailyBatchWithMergedMatches(): void {
        echo "[Test 5] Deduplication: Exactly 1 record per user/day with aggregated matches... ";

        $uid = $this->createTestUser('sched_t5_' . uniqid() . '@example.com');
        $calendarDay = date('Y-m-d');

        $matches1 = [
            ['scholarship_id' => 905, 'title' => 'Match A', 'deadline' => '2026-10-01']
        ];
        $matches2 = [
            ['scholarship_id' => 906, 'title' => 'Match B', 'deadline' => '2026-10-05']
        ];

        $this->notificationService->enqueueDailyWhatsAppBatch($uid, $matches1, $calendarDay);
        $this->notificationService->enqueueDailyWhatsAppBatch($uid, $matches2, $calendarDay);

        $idempotencyKey = "scholarship_whatsapp_{$uid}_{$calendarDay}";
        $count = (int)$this->db->query("SELECT COUNT(*) FROM notification_logs WHERE idempotency_key = '$idempotencyKey'")->fetchColumn();
        $this->assert($count === 1, "Exactly 1 record must exist for user/day");

        $row = $this->db->query("SELECT payload FROM notification_logs WHERE idempotency_key = '$idempotencyKey'")->fetch(PDO::FETCH_ASSOC);
        $data = json_decode($row['payload'], true);
        $this->assert(count($data['matches']) === 2, "Payload must contain both matches (found " . count($data['matches']) . ")");
        $this->assert((int)$data['match_count'] === 2, "match_count must be 2");

        echo "PASS\n";
    }

    /**
     * Test 6: Sunday Deferral: Defers to Monday at configured matching_send_time (e.g. 16:10).
     */
    public function test6_sundayDeferralUsesMatchingSendTimeToMonday(): void {
        echo "[Test 6] Sunday deferral schedules WhatsApp to Monday at matching_send_time... ";

        // User with email disabled so WhatsApp is deferred to Monday
        $uid = $this->createTestUser('sched_t6_' . uniqid() . '@example.com', null, false, true);

        // Find next Sunday
        $sundayDt = new DateTime('next sunday', new DateTimeZone('Asia/Karachi'));
        $sundayDay = $sundayDt->format('Y-m-d');

        NotificationService::$simulateSunday = true;
        try {
            $matches = [
                ['scholarship_id' => 907, 'title' => 'Sunday Scholarship', 'deadline' => '2026-12-01']
            ];
            $this->notificationService->enqueueDailyWhatsAppBatch($uid, $matches, $sundayDay);

            $mondayDt = clone $sundayDt;
            $mondayDt->modify('next monday');
            $mondayDay = $mondayDt->format('Y-m-d');

            $idempotencyKey = "scholarship_whatsapp_{$uid}_{$mondayDay}";
            $row = $this->db->query("SELECT available_at, status FROM notification_logs WHERE idempotency_key = '$idempotencyKey'")->fetch(PDO::FETCH_ASSOC);

            $this->assert(!empty($row), "Batch must be scheduled under Monday's date");
            $expectedAvail = "{$mondayDay} 16:10:00";
            $this->assert($row['available_at'] === $expectedAvail, "available_at must be Monday at 16:10:00, got '{$row['available_at']}'");
        } finally {
            NotificationService::$simulateSunday = null;
        }

        echo "PASS\n";
    }

    /**
     * Test 7: Lifetime 25-Message Cap is enforced on WhatsApp.
     */
    public function test7_lifetime25CapPreserved(): void {
        echo "[Test 7] Lifetime 25-message WhatsApp cap is strictly enforced... ";

        $uid = $this->createTestUser('sched_t7_' . uniqid() . '@example.com');
        $calendarDay = date('Y-m-d');

        // Pre-insert 25 sent WhatsApp messages
        for ($i = 1; $i <= 25; $i++) {
            $this->db->prepare("
                INSERT INTO notification_logs (user_id, scholarship_id, notification_type, channel, recipient, status, attempts, sent_at, idempotency_key)
                VALUES (:uid, NULL, 'DAILY_MATCH_DIGEST', 'whatsapp', '+923000000000', 'sent', 1, NOW(), :key)
            ")->execute(['uid' => $uid, 'key' => "wa_cap_{$uid}_{$i}"]);
        }

        $matches = [
            ['scholarship_id' => 908, 'title' => 'Cap Scholarship', 'deadline' => '2026-12-01']
        ];
        $ok = $this->notificationService->enqueueDailyWhatsAppBatch($uid, $matches, $calendarDay);
        $this->assert($ok === false, "Batch enqueue must be rejected once user reaches 25 messages");

        echo "PASS\n";
    }

    /**
     * Test 8: Email Notification Flow is untouched and working.
     */
    public function test8_emailNotificationFlowUnbroken(): void {
        echo "[Test 8] Email notification flow remains untouched and operational... ";

        $uid = $this->createTestUser('sched_t8_' . uniqid() . '@example.com', null, true, false);
        $calendarDay = date('Y-m-d');

        // Enqueue email notification
        $idempotencyKey = "test_email_unbroken_{$uid}";
        $ok = $this->queueService->enqueue(
            $uid,
            null,
            'NEW_MATCH',
            'email',
            'sched_t8@example.com',
            'New Scholarship Match Found',
            ['title' => 'Email Test Scholarship'],
            $idempotencyKey
        );

        $this->assert($ok === true, "Email enqueue must succeed");

        $row = $this->db->query("SELECT channel, status, available_at FROM notification_logs WHERE idempotency_key = '$idempotencyKey'")->fetch(PDO::FETCH_ASSOC);
        $this->assert(!empty($row), "Email log row must exist");
        $this->assert($row['channel'] === 'email', "Channel must be email");
        $this->assert($row['status'] === 'pending', "Status must be pending");
        $this->assert(strtotime($row['available_at']) <= time() + 5, "available_at must be <= NOW() for instant email dispatch");

        echo "PASS\n";
    }

    private function cleanup(): void {
        if (!empty($this->createdUserIds)) {
            $ids = implode(',', array_map('intval', $this->createdUserIds));
            $this->db->exec("DELETE FROM notification_logs WHERE user_id IN ($ids)");
            $this->db->exec("DELETE FROM notification_preferences WHERE user_id IN ($ids)");
            $this->db->exec("DELETE FROM user_preferences WHERE user_id IN ($ids)");
            $this->db->exec("DELETE FROM subscriptions WHERE user_id IN ($ids)");
            $this->db->exec("DELETE FROM users WHERE id IN ($ids)");
        }
    }
}

$test = new WhatsAppSchedulingAndRunNowTest();
$test->run();
