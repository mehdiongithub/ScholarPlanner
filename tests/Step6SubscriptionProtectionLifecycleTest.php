<?php

require_once __DIR__ . '/bootstrap.php';

use App\Services\Database;
use App\Services\Auth;
use App\Services\SubscriptionService;
use App\Services\ReferralService;
use App\Services\NotificationQueueService;
use App\Services\NotificationDispatchService;
use App\Services\NotificationTypes;

class Step6SubscriptionProtectionLifecycleTest {
    private PDO $db;
    private int $planPremiumId;
    private int $planFreeId;
    private int $visitorRoleId;
    private int $partnerRoleId;

    public function __construct() {
        $this->db = Database::connection();
    }

    public function run(): void {
        echo "=================================================================\n";
        echo " RUNNING STEP 6 SUBSCRIPTION PROTECTION LIFECYCLE TEST SUITE (32 TESTS)\n";
        echo "=================================================================\n\n";

        $this->setUp();

        try {
            // Edge Cases 1-8: Nominal Expiry & Delivered Count Matrix
            $this->test1_subscriptionActiveBeforeNominalExpiry();
            $this->test2_nominalExpiryWith0DeliveredMessages();
            $this->test3_nominalExpiryWith1DeliveredMessage();
            $this->test4_nominalExpiryWith2DeliveredMessages();
            $this->test5_nominalExpiryWith3DeliveredMessages();
            $this->test6_nominalExpiryWith4DeliveredMessages();
            $this->test7_nominalExpiryWithExactly5DeliveredMessages();
            $this->test8_nominalExpiryWithMoreThan5DeliveredMessages();

            // Edge Cases 9-11: Protected Subscription Persistence & Final Expiry
            $this->test9_protectedSubscriptionRemainsProtectedAt0Delivered();
            $this->test10_protectedSubscriptionRemainsProtectedAt1To4Delivered();
            $this->test11_protectedSubscriptionExpiresAfterReaching5Delivered();

            // Edge Cases 12-17: Delivered Message Accounting & Non-Delivered Filtering
            $this->test12_failedNotificationDoesNotCount();
            $this->test13_queuedNotificationDoesNotCount();
            $this->test14_pendingNotificationDoesNotCount();
            $this->test15_sentButNotDeliveredNotificationDoesNotCount();
            $this->test16_deliveredNotificationCountsExactlyOnce();
            $this->test17_duplicateDeliveryWebhookDoesNotIncreaseCount();

            // Edge Cases 18-19: Re-running Lifecycle & Multi-Match Batch Accounting
            $this->test18_reRunningLifecycleDoesNotDuplicateOrAlterCount();
            $this->test19_multipleScholarshipsInOneCombinedDailyMessageCountAccordingToSingleMessageRule();

            // Edge Cases 20-22: Calendar Date Calculation & Boundaries
            $this->test20_purchaseOnThe10thProducesNominalExpiryOnThe9thOfFollowingMonth();
            $this->test21_monthEndPurchaseDates();
            $this->test22_februaryLeapYearDates();

            // Edge Cases 23-25: Visibility, Final Expiry Invalidation & Full Idempotency
            $this->test23_protectedSubscriptionRemainsVisibleToCustomerAndAdminReporting();
            $this->test24_finalExpiredSubscriptionIsNoLongerTreatedAsActive();
            $this->test25_dailyLifecycleIsIdempotent();

            // Edge Cases 26-28: Cancelled Subscription Expiry Semantics & State Machine Rules
            $this->test26_cancelledSubscriptionWithZeroDeliveriesNeverBecomesProtectedAtNominalExpiry();
            $this->test27_cancelledSubscriptionWithPartialDeliveriesNeverBecomesProtectedAtNominalExpiry();
            $this->test28_stateMachineProhibitsCancelledToProtectedTransition();

            // Edge Cases 29-32: Cancelled Subscription Paid-Period Eligibility & Post-Expiry Invalidation
            $this->test29_cancelledSubscriptionBeforeNominalExpiryReceivesAlertAndAttachesSubscriptionId();
            $this->test30_cancelledSubscriptionWithFiveOrMoreDeliveriesStillExpiresDirectlyAtNominalExpiry();
            $this->test31_expiredCancelledSubscriptionReceivesNoFurtherScholarshipAlertsAndRevertsToFree();
            $this->test32_postExpiryDeliveryCannotReactivateOrProtectCancelledSubscription();

            echo "\n=================================================================\n";
            echo " ✔ ALL 32 STEP 6 SUBSCRIPTION PROTECTION TESTS PASSED!\n";
            echo "=================================================================\n\n";

        } finally {
            $this->tearDown();
        }
    }

    private function setUp(): void {
        $this->planPremiumId = (int)$this->db->query("SELECT id FROM subscription_plans WHERE slug = 'premium-monthly'")->fetchColumn();
        $this->planFreeId = (int)$this->db->query("SELECT id FROM subscription_plans WHERE slug = 'free'")->fetchColumn();
        $this->visitorRoleId = (int)$this->db->query("SELECT id FROM roles WHERE name = 'visitor'")->fetchColumn();
        $this->partnerRoleId = (int)$this->db->query("SELECT id FROM roles WHERE name = 'referral_partner'")->fetchColumn();

        $this->cleanTestData();
    }

    private function tearDown(): void {
        $this->cleanTestData();
    }

    private function cleanTestData(): void {
        $this->db->exec("DELETE FROM referral_commissions WHERE referred_user_id IN (SELECT id FROM users WHERE email LIKE 'step6_lifecycle_%@example.com')");
        $this->db->exec("DELETE FROM referral_discount_claims WHERE referred_user_id IN (SELECT id FROM users WHERE email LIKE 'step6_lifecycle_%@example.com')");
        $this->db->exec("DELETE FROM referral_signups WHERE referred_user_id IN (SELECT id FROM users WHERE email LIKE 'step6_lifecycle_%@example.com')");
        $this->db->exec("DELETE FROM payment_transactions WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'step6_lifecycle_%@example.com')");
        $this->db->exec("DELETE FROM notification_logs WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'step6_lifecycle_%@example.com')");
        $this->db->exec("DELETE FROM subscription_usage WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'step6_lifecycle_%@example.com')");
        $this->db->exec("DELETE FROM subscriptions WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'step6_lifecycle_%@example.com')");
        $this->db->exec("DELETE FROM users WHERE email LIKE 'step6_lifecycle_%@example.com'");
        $this->db->exec("DELETE FROM scholarships WHERE slug LIKE 'step6-test-%'");
    }

    private function createScholarship(string $title): int {
        $slug = 'step6-test-' . bin2hex(random_bytes(6));
        $deadline = date('Y-m-d', strtotime('+30 days'));
        $stmt = $this->db->prepare("
            INSERT INTO scholarships (title, slug, provider_name, provider_type, description, status, verification_status, application_deadline, created_at, updated_at)
            VALUES (:title, :slug, 'Step6 Provider', 'government', 'Test scholarship', 'published', 'verified', :deadline, NOW(), NOW())
        ");
        $stmt->execute([
            'title' => $title,
            'slug' => $slug,
            'deadline' => $deadline
        ]);
        return (int)$this->db->lastInsertId();
    }

    private function createUser(string $email, string $role = 'visitor', ?int $partnerId = null, ?string $refCode = null): int {
        $roleId = ($role === 'referral_partner') ? $this->partnerRoleId : $this->visitorRoleId;
        $stmt = $this->db->prepare("
            INSERT INTO users (email, password_hash, first_name, last_name, role_id, referral_partner_id, referred_by_code, status, created_at, updated_at)
            VALUES (:email, :hash, 'Step6', 'Tester', :role_id, :pid, :code, 'active', NOW(), NOW())
        ");
        $stmt->execute([
            'email' => $email,
            'hash' => password_hash('Pass123!', PASSWORD_BCRYPT),
            'role_id' => $roleId,
            'pid' => $partnerId,
            'code' => $refCode
        ]);
        return (int)$this->db->lastInsertId();
    }

    private function createPartner(string $email, string $code): int {
        $partnerId = $this->createUser($email, 'referral_partner');
        $stmt = $this->db->prepare("UPDATE users SET referral_code = :code, status = 'active' WHERE id = :id");
        $stmt->execute(['code' => $code, 'id' => $partnerId]);
        return $partnerId;
    }

    private function createSubscription(int $userId, string $status, string $startsAt, string $endsAt, int $minRequired = 5): int {
        $stmt = $this->db->prepare("
            INSERT INTO subscriptions (
                user_id, plan_id, status, starts_at, ends_at, normal_ends_at, minimum_delivered_required, auto_renew, created_at, updated_at
            ) VALUES (
                :uid, :pid, :status, :starts, :ends, :normal_ends, :min_req, 1, :created_at, NOW()
            )
        ");
        $stmt->execute([
            'uid' => $userId,
            'pid' => $this->planPremiumId,
            'status' => $status,
            'starts' => $startsAt,
            'ends' => $endsAt,
            'normal_ends' => $endsAt,
            'min_req' => $minRequired,
            'created_at' => $startsAt
        ]);
        return (int)$this->db->lastInsertId();
    }

    private function addNotificationLog(int $userId, int $subId, string $status, ?string $deliveredAt = null, string $type = 'NEW_MATCH', ?string $payload = null): int {
        $stmt = $this->db->prepare("
            INSERT INTO notification_logs (
                user_id, subscription_id, scholarship_id, notification_type, channel, provider, recipient,
                provider_message_id, idempotency_key, status, payload, delivered_at, created_at, updated_at
            ) VALUES (
                :uid, :sub_id, NULL, :type, 'whatsapp', 'wacrm', '923999887766',
                :msg_id, :idem_key, :status, :payload, :delivered_at, NOW(), NOW()
            )
        ");
        $randKey = 'notif_step6_' . bin2hex(random_bytes(6));
        $stmt->execute([
            'uid' => $userId,
            'sub_id' => $subId,
            'type' => $type,
            'msg_id' => 'wacrm_' . $randKey,
            'idem_key' => $randKey,
            'status' => $status,
            'payload' => $payload ?: json_encode(['title' => 'Sample Scholarship']),
            'delivered_at' => $deliveredAt
        ]);
        return (int)$this->db->lastInsertId();
    }

    private function assert(bool $condition, string $message): void {
        if (!$condition) {
            throw new Exception("Assertion Failed: " . $message);
        }
    }

    // =========================================================================
    // 25 STEP 6 EDGE-CASE TESTS
    // =========================================================================

    /**
     * 1. Subscription active before nominal expiry.
     */
    private function test1_subscriptionActiveBeforeNominalExpiry(): void {
        echo "[Test 1] Subscription active before nominal expiry... ";
        $uid = $this->createUser('step6_lifecycle_u1@example.com');
        $startsAt = date('Y-m-d H:i:s', strtotime('-10 days'));
        $endsAt = date('Y-m-d H:i:s', strtotime('+20 days'));
        $subId = $this->createSubscription($uid, 'active', $startsAt, $endsAt);

        // Run daily lifecycle
        $metrics = SubscriptionService::processDailyLifecycle($this->db);

        // Status must remain active
        $status = $this->db->query("SELECT status FROM subscriptions WHERE id = $subId")->fetchColumn();
        $this->assert($status === 'active', "Subscription before nominal expiry must remain active, got: $status");

        // Active plan check
        $plan = SubscriptionService::getActivePlan($uid);
        $this->assert($plan['status'] === 'active', "ActivePlan status must be active");
        $this->assert($plan['plan_slug'] === 'premium-monthly', "Plan slug must be premium-monthly");
        $this->assert(SubscriptionService::can($uid, 'premium_alerts') === true, "Active subscription must have premium_alerts");
        echo "PASS\n";
    }

    /**
     * 2. Nominal expiry with 0 delivered messages.
     */
    private function test2_nominalExpiryWith0DeliveredMessages(): void {
        echo "[Test 2] Nominal expiry with 0 delivered messages... ";
        $uid = $this->createUser('step6_lifecycle_u2@example.com');
        $startsAt = date('Y-m-d H:i:s', strtotime('-35 days'));
        $endsAt = date('Y-m-d H:i:s', strtotime('-1 day'));
        $subId = $this->createSubscription($uid, 'active', $startsAt, $endsAt);

        // Zero notifications delivered
        $metrics = SubscriptionService::processDailyLifecycle($this->db);

        $row = $this->db->query("SELECT status, final_expired_at FROM subscriptions WHERE id = $subId")->fetch(PDO::FETCH_ASSOC);
        $this->assert($row['status'] === 'protected', "Subscription with 0 deliveries must transition to protected, got {$row['status']}");
        $this->assert(empty($row['final_expired_at']), "final_expired_at must remain NULL while protected");

        // Usage check
        $usage = $this->db->query("SELECT qualifying_delivered_count, protected_state FROM subscription_usage WHERE subscription_id = $subId ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $this->assert((int)$usage['qualifying_delivered_count'] === 0, "Usage count must be 0");
        $this->assert((int)$usage['protected_state'] === 1, "protected_state must be 1");

        // Entitlement preservation
        $this->assert(SubscriptionService::can($uid, 'premium_alerts') === true, "Protected customer must retain premium entitlements");
        echo "PASS\n";
    }

    /**
     * 3. Nominal expiry with 1 delivered message.
     */
    private function test3_nominalExpiryWith1DeliveredMessage(): void {
        echo "[Test 3] Nominal expiry with 1 delivered message... ";
        $uid = $this->createUser('step6_lifecycle_u3@example.com');
        $startsAt = date('Y-m-d H:i:s', strtotime('-35 days'));
        $endsAt = date('Y-m-d H:i:s', strtotime('-1 day'));
        $subId = $this->createSubscription($uid, 'active', $startsAt, $endsAt);

        $this->addNotificationLog($uid, $subId, 'delivered', date('Y-m-d H:i:s', strtotime('-15 days')));

        SubscriptionService::processDailyLifecycle($this->db);

        $status = $this->db->query("SELECT status FROM subscriptions WHERE id = $subId")->fetchColumn();
        $this->assert($status === 'protected', "Subscription with 1 delivery must transition to protected, got: $status");
        echo "PASS\n";
    }

    /**
     * 4. Nominal expiry with 2 delivered messages.
     */
    private function test4_nominalExpiryWith2DeliveredMessages(): void {
        echo "[Test 4] Nominal expiry with 2 delivered messages... ";
        $uid = $this->createUser('step6_lifecycle_u4@example.com');
        $startsAt = date('Y-m-d H:i:s', strtotime('-35 days'));
        $endsAt = date('Y-m-d H:i:s', strtotime('-1 day'));
        $subId = $this->createSubscription($uid, 'active', $startsAt, $endsAt);

        $this->addNotificationLog($uid, $subId, 'delivered', date('Y-m-d H:i:s', strtotime('-20 days')));
        $this->addNotificationLog($uid, $subId, 'delivered', date('Y-m-d H:i:s', strtotime('-10 days')));

        SubscriptionService::processDailyLifecycle($this->db);

        $status = $this->db->query("SELECT status FROM subscriptions WHERE id = $subId")->fetchColumn();
        $this->assert($status === 'protected', "Subscription with 2 deliveries must transition to protected, got: $status");
        echo "PASS\n";
    }

    /**
     * 5. Nominal expiry with 3 delivered messages.
     */
    private function test5_nominalExpiryWith3DeliveredMessages(): void {
        echo "[Test 5] Nominal expiry with 3 delivered messages... ";
        $uid = $this->createUser('step6_lifecycle_u5@example.com');
        $startsAt = date('Y-m-d H:i:s', strtotime('-35 days'));
        $endsAt = date('Y-m-d H:i:s', strtotime('-1 day'));
        $subId = $this->createSubscription($uid, 'active', $startsAt, $endsAt);

        for ($i = 1; $i <= 3; $i++) {
            $this->addNotificationLog($uid, $subId, 'delivered', date('Y-m-d H:i:s', strtotime("-{$i} days")));
        }

        SubscriptionService::processDailyLifecycle($this->db);

        $status = $this->db->query("SELECT status FROM subscriptions WHERE id = $subId")->fetchColumn();
        $this->assert($status === 'protected', "Subscription with 3 deliveries must transition to protected, got: $status");
        echo "PASS\n";
    }

    /**
     * 6. Nominal expiry with 4 delivered messages.
     */
    private function test6_nominalExpiryWith4DeliveredMessages(): void {
        echo "[Test 6] Nominal expiry with 4 delivered messages... ";
        $uid = $this->createUser('step6_lifecycle_u6@example.com');
        $startsAt = date('Y-m-d H:i:s', strtotime('-35 days'));
        $endsAt = date('Y-m-d H:i:s', strtotime('-1 day'));
        $subId = $this->createSubscription($uid, 'active', $startsAt, $endsAt);

        for ($i = 1; $i <= 4; $i++) {
            $this->addNotificationLog($uid, $subId, 'delivered', date('Y-m-d H:i:s', strtotime("-{$i} days")));
        }

        SubscriptionService::processDailyLifecycle($this->db);

        $status = $this->db->query("SELECT status FROM subscriptions WHERE id = $subId")->fetchColumn();
        $this->assert($status === 'protected', "Subscription with 4 deliveries must transition to protected, got: $status");
        echo "PASS\n";
    }

    /**
     * 7. Nominal expiry with exactly 5 delivered messages.
     */
    private function test7_nominalExpiryWithExactly5DeliveredMessages(): void {
        echo "[Test 7] Nominal expiry with exactly 5 delivered messages... ";
        $uid = $this->createUser('step6_lifecycle_u7@example.com');
        $startsAt = date('Y-m-d H:i:s', strtotime('-35 days'));
        $endsAt = date('Y-m-d H:i:s', strtotime('-1 day'));
        $subId = $this->createSubscription($uid, 'active', $startsAt, $endsAt);

        for ($i = 1; $i <= 5; $i++) {
            $this->addNotificationLog($uid, $subId, 'delivered', date('Y-m-d H:i:s', strtotime("-{$i} days")));
        }

        SubscriptionService::processDailyLifecycle($this->db);

        $sub = $this->db->query("SELECT status, final_expired_at, expiry_reason FROM subscriptions WHERE id = $subId")->fetch(PDO::FETCH_ASSOC);
        $this->assert($sub['status'] === 'expired', "Subscription with 5 deliveries must transition to expired, got {$sub['status']}");
        $this->assert(!empty($sub['final_expired_at']), "final_expired_at must be populated");
        $this->assert($sub['expiry_reason'] === 'normal_completion', "expiry_reason must be normal_completion, got {$sub['expiry_reason']}");

        // Expiration notification enqueued with idempotency key
        $notifKey = "sub_expired_alert_{$subId}";
        $notif = $this->db->query("SELECT * FROM notification_logs WHERE idempotency_key = '$notifKey'")->fetch(PDO::FETCH_ASSOC);
        $this->assert(!empty($notif), "Expiration notification must be enqueued");
        $this->assert($notif['notification_type'] === 'SUBSCRIPTION_EXPIRED', "Notification type must be SUBSCRIPTION_EXPIRED");
        echo "PASS\n";
    }

    /**
     * 8. Nominal expiry with more than 5 delivered messages.
     */
    private function test8_nominalExpiryWithMoreThan5DeliveredMessages(): void {
        echo "[Test 8] Nominal expiry with more than 5 delivered messages... ";
        $uid = $this->createUser('step6_lifecycle_u8@example.com');
        $startsAt = date('Y-m-d H:i:s', strtotime('-35 days'));
        $endsAt = date('Y-m-d H:i:s', strtotime('-1 day'));
        $subId = $this->createSubscription($uid, 'active', $startsAt, $endsAt);

        for ($i = 1; $i <= 7; $i++) {
            $this->addNotificationLog($uid, $subId, 'delivered', date('Y-m-d H:i:s', strtotime("-{$i} days")));
        }

        SubscriptionService::processDailyLifecycle($this->db);

        $status = $this->db->query("SELECT status FROM subscriptions WHERE id = $subId")->fetchColumn();
        $this->assert($status === 'expired', "Subscription with 7 deliveries must transition to expired, got: $status");
        echo "PASS\n";
    }

    /**
     * 9. Protected subscription remains protected at 0 delivered.
     */
    private function test9_protectedSubscriptionRemainsProtectedAt0Delivered(): void {
        echo "[Test 9] Protected subscription remains protected at 0 delivered... ";
        $uid = $this->createUser('step6_lifecycle_u9@example.com');
        $startsAt = date('Y-m-d H:i:s', strtotime('-45 days'));
        $endsAt = date('Y-m-d H:i:s', strtotime('-15 days'));
        $subId = $this->createSubscription($uid, 'protected', $startsAt, $endsAt);

        // Run lifecycle again
        SubscriptionService::processDailyLifecycle($this->db);

        $status = $this->db->query("SELECT status FROM subscriptions WHERE id = $subId")->fetchColumn();
        $this->assert($status === 'protected', "Protected subscription with 0 deliveries must remain protected, got: $status");

        // No expired notification should be queued
        $notifKey = "sub_expired_alert_{$subId}";
        $notif = $this->db->query("SELECT id FROM notification_logs WHERE idempotency_key = '$notifKey'")->fetchColumn();
        $this->assert(empty($notif), "No expiration notification must be generated for protected subscription");
        echo "PASS\n";
    }

    /**
     * 10. Protected subscription remains protected at 1–4 delivered.
     */
    private function test10_protectedSubscriptionRemainsProtectedAt1To4Delivered(): void {
        echo "[Test 10] Protected subscription remains protected at 1–4 delivered... ";
        $uid = $this->createUser('step6_lifecycle_u10@example.com');
        $startsAt = date('Y-m-d H:i:s', strtotime('-50 days'));
        $endsAt = date('Y-m-d H:i:s', strtotime('-20 days'));
        $subId = $this->createSubscription($uid, 'protected', $startsAt, $endsAt);

        // Add 3 delivered messages
        for ($i = 1; $i <= 3; $i++) {
            $this->addNotificationLog($uid, $subId, 'delivered', date('Y-m-d H:i:s', strtotime("-{$i} days")));
        }

        SubscriptionService::processDailyLifecycle($this->db);

        $status = $this->db->query("SELECT status FROM subscriptions WHERE id = $subId")->fetchColumn();
        $this->assert($status === 'protected', "Protected subscription with 3 deliveries must remain protected, got: $status");

        $usage = (int)$this->db->query("SELECT qualifying_delivered_count FROM subscription_usage WHERE subscription_id = $subId ORDER BY id DESC LIMIT 1")->fetchColumn();
        $this->assert($usage === 3, "Usage count must be 3, got: $usage");
        echo "PASS\n";
    }

    /**
     * 11. Protected subscription expires after reaching 5 delivered.
     */
    private function test11_protectedSubscriptionExpiresAfterReaching5Delivered(): void {
        echo "[Test 11] Protected subscription expires after reaching 5 delivered... ";
        $uid = $this->createUser('step6_lifecycle_u11@example.com');
        $startsAt = date('Y-m-d H:i:s', strtotime('-60 days'));
        $endsAt = date('Y-m-d H:i:s', strtotime('-30 days'));
        $subId = $this->createSubscription($uid, 'protected', $startsAt, $endsAt);

        // Add 5 delivered messages
        for ($i = 1; $i <= 5; $i++) {
            $this->addNotificationLog($uid, $subId, 'delivered', date('Y-m-d H:i:s', strtotime("-{$i} days")));
        }

        SubscriptionService::processDailyLifecycle($this->db);

        $sub = $this->db->query("SELECT status, final_expired_at, expiry_reason FROM subscriptions WHERE id = $subId")->fetch(PDO::FETCH_ASSOC);
        $this->assert($sub['status'] === 'expired', "Protected subscription reaching 5 must transition to expired, got {$sub['status']}");
        $this->assert(!empty($sub['final_expired_at']), "final_expired_at must be set");
        $this->assert($sub['expiry_reason'] === 'minimum_delivery_satisfied', "expiry_reason must be minimum_delivery_satisfied, got {$sub['expiry_reason']}");

        $usage = (int)$this->db->query("SELECT qualifying_delivered_count FROM subscription_usage WHERE subscription_id = $subId ORDER BY id DESC LIMIT 1")->fetchColumn();
        $this->assert($usage === 5, "Usage count must be synchronized to 5");
        echo "PASS\n";
    }

    /**
     * 12. Failed notification does not count.
     */
    private function test12_failedNotificationDoesNotCount(): void {
        echo "[Test 12] Failed notification does not count... ";
        $uid = $this->createUser('step6_lifecycle_u12@example.com');
        $startsAt = date('Y-m-d H:i:s', strtotime('-10 days'));
        $endsAt = date('Y-m-d H:i:s', strtotime('+20 days'));
        $subId = $this->createSubscription($uid, 'active', $startsAt, $endsAt);

        $this->addNotificationLog($uid, $subId, 'failed', null);

        $count = SubscriptionService::countQualifyingDeliveredMessages($subId, $this->db);
        $this->assert($count === 0, "Failed notification must not count toward delivered, got: $count");
        echo "PASS\n";
    }

    /**
     * 13. Queued notification does not count.
     */
    private function test13_queuedNotificationDoesNotCount(): void {
        echo "[Test 13] Queued notification does not count... ";
        $uid = $this->createUser('step6_lifecycle_u13@example.com');
        $startsAt = date('Y-m-d H:i:s', strtotime('-10 days'));
        $endsAt = date('Y-m-d H:i:s', strtotime('+20 days'));
        $subId = $this->createSubscription($uid, 'active', $startsAt, $endsAt);

        $this->addNotificationLog($uid, $subId, 'queued', null);

        $count = SubscriptionService::countQualifyingDeliveredMessages($subId, $this->db);
        $this->assert($count === 0, "Queued notification must not count toward delivered, got: $count");
        echo "PASS\n";
    }

    /**
     * 14. Pending notification does not count.
     */
    private function test14_pendingNotificationDoesNotCount(): void {
        echo "[Test 14] Pending notification does not count... ";
        $uid = $this->createUser('step6_lifecycle_u14@example.com');
        $startsAt = date('Y-m-d H:i:s', strtotime('-10 days'));
        $endsAt = date('Y-m-d H:i:s', strtotime('+20 days'));
        $subId = $this->createSubscription($uid, 'active', $startsAt, $endsAt);

        $this->addNotificationLog($uid, $subId, 'pending', null);

        $count = SubscriptionService::countQualifyingDeliveredMessages($subId, $this->db);
        $this->assert($count === 0, "Pending notification must not count toward delivered, got: $count");
        echo "PASS\n";
    }

    /**
     * 15. Sent-but-not-delivered notification does not count.
     */
    private function test15_sentButNotDeliveredNotificationDoesNotCount(): void {
        echo "[Test 15] Sent-but-not-delivered notification does not count... ";
        $uid = $this->createUser('step6_lifecycle_u15@example.com');
        $startsAt = date('Y-m-d H:i:s', strtotime('-10 days'));
        $endsAt = date('Y-m-d H:i:s', strtotime('+20 days'));
        $subId = $this->createSubscription($uid, 'active', $startsAt, $endsAt);

        // sent with NULL delivered_at
        $this->addNotificationLog($uid, $subId, 'sent', null);

        $count = SubscriptionService::countQualifyingDeliveredMessages($subId, $this->db);
        $this->assert($count === 0, "Sent-but-not-delivered notification must not count toward delivered, got: $count");
        echo "PASS\n";
    }

    /**
     * 16. Delivered notification counts exactly once.
     */
    private function test16_deliveredNotificationCountsExactlyOnce(): void {
        echo "[Test 16] Delivered notification counts exactly once... ";
        $uid = $this->createUser('step6_lifecycle_u16@example.com');
        $startsAt = date('Y-m-d H:i:s', strtotime('-10 days'));
        $endsAt = date('Y-m-d H:i:s', strtotime('+20 days'));
        $subId = $this->createSubscription($uid, 'active', $startsAt, $endsAt);

        $this->addNotificationLog($uid, $subId, 'delivered', date('Y-m-d H:i:s'));

        $count = SubscriptionService::countQualifyingDeliveredMessages($subId, $this->db);
        $this->assert($count === 1, "Delivered notification must count exactly once, got: $count");
        echo "PASS\n";
    }

    /**
     * 17. Duplicate delivery webhook does not increase the count.
     */
    private function test17_duplicateDeliveryWebhookDoesNotIncreaseCount(): void {
        echo "[Test 17] Duplicate delivery webhook does not increase the count... ";
        $uid = $this->createUser('step6_lifecycle_u17@example.com');
        $startsAt = date('Y-m-d H:i:s', strtotime('-10 days'));
        $endsAt = date('Y-m-d H:i:s', strtotime('+20 days'));
        $subId = $this->createSubscription($uid, 'active', $startsAt, $endsAt);

        $dispatchService = new NotificationDispatchService();
        $providerMsgId = 'wacrm_dup_' . bin2hex(random_bytes(6));

        // Create log record in 'sent' status
        $stmt = $this->db->prepare("
            INSERT INTO notification_logs (
                user_id, subscription_id, scholarship_id, notification_type, channel, provider, recipient,
                provider_message_id, idempotency_key, status, created_at, updated_at
            ) VALUES (
                :uid, :sub_id, NULL, 'NEW_MATCH', 'whatsapp', 'wacrm', '923999887766',
                :msg_id, :idem_key, 'sent', NOW(), NOW()
            )
        ");
        $stmt->execute([
            'uid' => $uid,
            'sub_id' => $subId,
            'msg_id' => $providerMsgId,
            'idem_key' => 'idem_' . $providerMsgId
        ]);
        $logId = (int)$this->db->lastInsertId();

        // First delivery webhook arrives
        $firstTimestamp = date('Y-m-d H:i:s', strtotime('-5 days'));
        $res1 = $dispatchService->recordDeliveryStatus($providerMsgId, 'delivered', $firstTimestamp);
        $this->assert($res1['success'] === true, "First delivery report must succeed");

        $count1 = SubscriptionService::countQualifyingDeliveredMessages($subId, $this->db);
        $this->assert($count1 === 1, "Count must be 1 after first delivery");

        // Duplicate delivery webhook arrives (with later timestamp)
        $secondTimestamp = date('Y-m-d H:i:s', strtotime('-5 days + 5 minutes'));
        $res2 = $dispatchService->recordDeliveryStatus($providerMsgId, 'delivered', $secondTimestamp);
        $this->assert($res2['success'] === true, "Duplicate delivery report must be handled safely");

        // Verify delivered_at timestamp preserved original timestamp
        $rowLog = $this->db->query("SELECT delivered_at, status FROM notification_logs WHERE id = $logId")->fetch(PDO::FETCH_ASSOC);
        $this->assert($rowLog['status'] === 'delivered', "Status must remain delivered");
        $this->assert($rowLog['delivered_at'] === $firstTimestamp, "Original delivered_at must be preserved, got: {$rowLog['delivered_at']}");

        // Delivered count must still be strictly 1
        $count2 = SubscriptionService::countQualifyingDeliveredMessages($subId, $this->db);
        $this->assert($count2 === 1, "Duplicate webhook must not increment delivered count, got: $count2");
        echo "PASS\n";
    }

    /**
     * 18. Re-running lifecycle does not duplicate or alter the count.
     */
    private function test18_reRunningLifecycleDoesNotDuplicateOrAlterCount(): void {
        echo "[Test 18] Re-running lifecycle does not duplicate or alter the count... ";
        $uid = $this->createUser('step6_lifecycle_u18@example.com');
        $startsAt = date('Y-m-d H:i:s', strtotime('-35 days'));
        $endsAt = date('Y-m-d H:i:s', strtotime('-1 day'));
        $subId = $this->createSubscription($uid, 'active', $startsAt, $endsAt);

        // Add 2 delivered messages
        $this->addNotificationLog($uid, $subId, 'delivered', date('Y-m-d H:i:s', strtotime('-20 days')));
        $this->addNotificationLog($uid, $subId, 'delivered', date('Y-m-d H:i:s', strtotime('-10 days')));

        // Run lifecycle 3 times consecutively
        SubscriptionService::processDailyLifecycle($this->db);
        $statusRun1 = $this->db->query("SELECT status FROM subscriptions WHERE id = $subId")->fetchColumn();
        $usageRun1 = (int)$this->db->query("SELECT qualifying_delivered_count FROM subscription_usage WHERE subscription_id = $subId ORDER BY id DESC LIMIT 1")->fetchColumn();

        SubscriptionService::processDailyLifecycle($this->db);
        $statusRun2 = $this->db->query("SELECT status FROM subscriptions WHERE id = $subId")->fetchColumn();
        $usageRun2 = (int)$this->db->query("SELECT qualifying_delivered_count FROM subscription_usage WHERE subscription_id = $subId ORDER BY id DESC LIMIT 1")->fetchColumn();

        SubscriptionService::processDailyLifecycle($this->db);
        $statusRun3 = $this->db->query("SELECT status FROM subscriptions WHERE id = $subId")->fetchColumn();
        $usageRun3 = (int)$this->db->query("SELECT qualifying_delivered_count FROM subscription_usage WHERE subscription_id = $subId ORDER BY id DESC LIMIT 1")->fetchColumn();

        $this->assert($statusRun1 === 'protected' && $statusRun2 === 'protected' && $statusRun3 === 'protected', "Status must remain protected across repeated runs");
        $this->assert($usageRun1 === 2 && $usageRun2 === 2 && $usageRun3 === 2, "Usage count must stay 2 across repeated runs");

        $usageRows = (int)$this->db->query("SELECT COUNT(*) FROM subscription_usage WHERE subscription_id = $subId")->fetchColumn();
        $this->assert($usageRows === 1, "Subscription usage must have exactly 1 record (no duplicate rows created), got: $usageRows");
        echo "PASS\n";
    }

    /**
     * 19. Multiple scholarships in one combined daily message count according to single-message delivery rule.
     */
    private function test19_multipleScholarshipsInOneCombinedDailyMessageCountAccordingToSingleMessageRule(): void {
        echo "[Test 19] Multiple scholarships in one combined daily message count as 1 delivery... ";
        $uid = $this->createUser('step6_lifecycle_u19@example.com');
        $startsAt = date('Y-m-d H:i:s', strtotime('-10 days'));
        $endsAt = date('Y-m-d H:i:s', strtotime('+20 days'));
        $subId = $this->createSubscription($uid, 'active', $startsAt, $endsAt);

        // Combined daily digest payload with 3 matched scholarships
        $combinedPayload = json_encode([
            'matches' => [
                ['scholarship_id' => 101, 'title' => 'Fulbright Scholarship'],
                ['scholarship_id' => 102, 'title' => 'Chevening Scholarship'],
                ['scholarship_id' => 103, 'title' => 'DAAD Scholarship']
            ]
        ]);

        $this->addNotificationLog($uid, $subId, 'delivered', date('Y-m-d H:i:s', strtotime('-2 days')), 'DAILY_MATCH_DIGEST', $combinedPayload);

        $count = SubscriptionService::countQualifyingDeliveredMessages($subId, $this->db);
        $this->assert($count === 1, "Combined daily digest with 3 scholarships must count as exactly 1 delivered message, got: $count");
        echo "PASS\n";
    }

    /**
     * 20. Purchase on the 10th produces nominal expiry on the 9th of following month.
     */
    private function test20_purchaseOnThe10thProducesNominalExpiryOnThe9thOfFollowingMonth(): void {
        echo "[Test 20] Purchase on the 10th produces nominal expiry on the 9th of following month... ";
        $dt = new DateTime('2026-09-10 14:30:00');
        $expiry = SubscriptionService::calculateCalendarMonthExpiry($dt);
        $this->assert($expiry === '2026-10-09 23:59:59', "Expected 2026-10-09 23:59:59, got $expiry");

        // Plan expiry wrapper
        $planExpiry = SubscriptionService::calculatePlanExpiry($this->planPremiumId, '2026-09-10 09:00:00');
        $this->assert($planExpiry === '2026-10-09 23:59:59', "Plan expiry must be 2026-10-09 23:59:59, got: $planExpiry");
        echo "PASS\n";
    }

    /**
     * 21. Month-end purchase dates.
     */
    private function test21_monthEndPurchaseDates(): void {
        echo "[Test 21] Month-end purchase dates... ";
        // 1st of month: e.g. 2026-09-01 -> 2026-09-30 23:59:59
        $expSep01 = SubscriptionService::calculateCalendarMonthExpiry(new DateTime('2026-09-01 00:00:00'));
        $this->assert($expSep01 === '2026-09-30 23:59:59', "1 Sep -> 30 Sep 23:59:59, got $expSep01");

        // 31st of 31-day month to 30-day month: 31 March -> 30 April
        $expMar31 = SubscriptionService::calculateCalendarMonthExpiry(new DateTime('2026-03-31 12:00:00'));
        $this->assert($expMar31 === '2026-04-30 23:59:59', "31 Mar -> 30 Apr 23:59:59, got $expMar31");

        // 31 May -> 30 June
        $expMay31 = SubscriptionService::calculateCalendarMonthExpiry(new DateTime('2026-05-31 12:00:00'));
        $this->assert($expMay31 === '2026-06-30 23:59:59', "31 May -> 30 Jun 23:59:59, got $expMay31");

        // 31 August -> 30 September
        $expAug31 = SubscriptionService::calculateCalendarMonthExpiry(new DateTime('2026-08-31 12:00:00'));
        $this->assert($expAug31 === '2026-09-30 23:59:59', "31 Aug -> 30 Sep 23:59:59, got $expAug31");

        // 31 October -> 30 November
        $expOct31 = SubscriptionService::calculateCalendarMonthExpiry(new DateTime('2026-10-31 12:00:00'));
        $this->assert($expOct31 === '2026-11-30 23:59:59', "31 Oct -> 30 Nov 23:59:59, got $expOct31");

        // 30th of 30-day month: 30 April -> 29 May
        $expApr30 = SubscriptionService::calculateCalendarMonthExpiry(new DateTime('2026-04-30 12:00:00'));
        $this->assert($expApr30 === '2026-05-29 23:59:59', "30 Apr -> 29 May 23:59:59, got $expApr30");
        echo "PASS\n";
    }

    /**
     * 22. February/leap-year dates.
     */
    private function test22_februaryLeapYearDates(): void {
        echo "[Test 22] February/leap-year dates... ";
        // Non-leap year 2026:
        $expJan31_2026 = SubscriptionService::calculateCalendarMonthExpiry(new DateTime('2026-01-31 10:00:00'));
        $this->assert($expJan31_2026 === '2026-02-28 23:59:59', "2026: 31 Jan -> 28 Feb, got $expJan31_2026");

        $expFeb01_2026 = SubscriptionService::calculateCalendarMonthExpiry(new DateTime('2026-02-01 10:00:00'));
        $this->assert($expFeb01_2026 === '2026-02-28 23:59:59', "2026: 1 Feb -> 28 Feb, got $expFeb01_2026");

        $expFeb28_2026 = SubscriptionService::calculateCalendarMonthExpiry(new DateTime('2026-02-28 10:00:00'));
        $this->assert($expFeb28_2026 === '2026-03-27 23:59:59', "2026: 28 Feb -> 27 Mar, got $expFeb28_2026");

        // Leap year 2024:
        $expJan31_2024 = SubscriptionService::calculateCalendarMonthExpiry(new DateTime('2024-01-31 10:00:00'));
        $this->assert($expJan31_2024 === '2024-02-29 23:59:59', "2024: 31 Jan -> 29 Feb, got $expJan31_2024");

        $expFeb01_2024 = SubscriptionService::calculateCalendarMonthExpiry(new DateTime('2024-02-01 10:00:00'));
        $this->assert($expFeb01_2024 === '2024-02-29 23:59:59', "2024: 1 Feb -> 29 Feb, got $expFeb01_2024");

        $expFeb29_2024 = SubscriptionService::calculateCalendarMonthExpiry(new DateTime('2024-02-29 10:00:00'));
        $this->assert($expFeb29_2024 === '2024-03-28 23:59:59', "2024: 29 Feb -> 28 Mar, got $expFeb29_2024");
        echo "PASS\n";
    }

    /**
     * 23. Protected subscription remains visible to customer/admin reporting.
     */
    private function test23_protectedSubscriptionRemainsVisibleToCustomerAndAdminReporting(): void {
        echo "[Test 23] Protected subscription remains visible to customer/admin reporting... ";
        $partnerId = $this->createPartner('step6_lifecycle_partner23@example.com', 'PROT23');
        $uid = $this->createUser('step6_lifecycle_u23@example.com', 'visitor', $partnerId, 'PROT23');

        $startsAt = date('Y-m-d H:i:s', strtotime('-40 days'));
        $endsAt = date('Y-m-d H:i:s', strtotime('-10 days'));
        $subId = $this->createSubscription($uid, 'protected', $startsAt, $endsAt);

        // Payment transaction in current month
        $currentMonth = date('Y-m');
        $this->db->prepare("
            INSERT INTO payment_transactions (
                user_id, subscription_id, plan_id, provider, transaction_reference,
                amount, currency, referral_partner_id, referral_code_used, status, paid_at, created_at, updated_at
            ) VALUES (
                :uid, :sub_id, :pid, 'cashmaal', 'TXN_STEP6_P23',
                1500.00, 'PKR', :partner_id, 'PROT23', 'paid', NOW(), NOW(), NOW()
            )
        ")->execute([
            'uid' => $uid,
            'sub_id' => $subId,
            'pid' => $this->planPremiumId,
            'partner_id' => $partnerId
        ]);

        // Customer view: getActivePlan returns the protected subscription
        $activePlan = SubscriptionService::getActivePlan($uid);
        $this->assert($activePlan['status'] === 'protected', "Active plan status must be protected, got: {$activePlan['status']}");
        $this->assert($activePlan['plan_slug'] === 'premium-monthly', "Protected plan slug must be premium-monthly");
        $this->assert(SubscriptionService::can($uid, 'premium_alerts') === true, "Protected customer must retain premium_alerts capability");

        // Admin/Partner reporting: Partner sees customer in active paid customers
        $isActive = ReferralService::isUserActiveReferralCustomer($uid, $partnerId, $this->db);
        $this->assert($isActive === true, "Protected customer must be reported as active referral customer");

        $partnerList = ReferralService::getPartnerActiveCustomers($partnerId, 1, 15, $this->db);
        $customerUserIds = array_column($partnerList['records'], 'referred_user_id');
        $this->assert(in_array($uid, array_map('intval', $customerUserIds), true), "Protected customer must remain visible in partner active customer reporting");
        echo "PASS\n";
    }

    /**
     * 24. Final-expired subscription is no longer treated as active.
     */
    private function test24_finalExpiredSubscriptionIsNoLongerTreatedAsActive(): void {
        echo "[Test 24] Final-expired subscription is no longer treated as active... ";
        $partnerId = $this->createPartner('step6_lifecycle_partner24@example.com', 'EXP24');
        $uid = $this->createUser('step6_lifecycle_u24@example.com', 'visitor', $partnerId, 'EXP24');

        $startsAt = date('Y-m-d H:i:s', strtotime('-40 days'));
        $endsAt = date('Y-m-d H:i:s', strtotime('-10 days'));
        $subId = $this->createSubscription($uid, 'expired', $startsAt, $endsAt);
        $this->db->exec("UPDATE subscriptions SET final_expired_at = NOW(), expiry_reason = 'normal_completion' WHERE id = $subId");

        // Customer view: falls back to Free plan
        $plan = SubscriptionService::getActivePlan($uid);
        $this->assert($plan['plan_slug'] === 'free', "Final-expired user must fall back to free plan, got {$plan['plan_slug']}");
        $this->assert(SubscriptionService::can($uid, 'premium_alerts') === false, "Final-expired user must not have premium_alerts");

        // Partner view: no active paid customer for expired subscription
        $isActive = ReferralService::isUserActiveReferralCustomer($uid, $partnerId, $this->db);
        $this->assert($isActive === false, "Final-expired customer must NOT be reported as active referral customer");

        $partnerList = ReferralService::getPartnerActiveCustomers($partnerId, 1, 15, $this->db);
        $customerUserIds = array_column($partnerList['records'], 'referred_user_id');
        $this->assert(!in_array($uid, array_map('intval', $customerUserIds), true), "Final-expired customer must NOT be in active paid customer list");
        echo "PASS\n";
    }

    /**
     * 25. Daily lifecycle is idempotent.
     */
    private function test25_dailyLifecycleIsIdempotent(): void {
        echo "[Test 25] Daily lifecycle is idempotent across multiple runs... ";
        $uidA = $this->createUser('step6_lifecycle_u25a@example.com');
        $uidB = $this->createUser('step6_lifecycle_u25b@example.com');
        $uidC = $this->createUser('step6_lifecycle_u25c@example.com');

        // Cohort A: active, before nominal expiry
        $subA = $this->createSubscription($uidA, 'active', date('Y-m-d H:i:s', strtotime('-5 days')), date('Y-m-d H:i:s', strtotime('+25 days')));

        // Cohort B: nominal expiry reached with 2 delivered (becomes protected)
        $subB = $this->createSubscription($uidB, 'active', date('Y-m-d H:i:s', strtotime('-35 days')), date('Y-m-d H:i:s', strtotime('-1 day')));
        $this->addNotificationLog($uidB, $subB, 'delivered', date('Y-m-d H:i:s', strtotime('-20 days')));
        $this->addNotificationLog($uidB, $subB, 'delivered', date('Y-m-d H:i:s', strtotime('-10 days')));

        // Cohort C: nominal expiry reached with 5 delivered (becomes expired)
        $subC = $this->createSubscription($uidC, 'active', date('Y-m-d H:i:s', strtotime('-35 days')), date('Y-m-d H:i:s', strtotime('-1 day')));
        for ($i = 1; $i <= 5; $i++) {
            $this->addNotificationLog($uidC, $subC, 'delivered', date('Y-m-d H:i:s', strtotime("-{$i} days")));
        }

        // Execution 1
        SubscriptionService::processDailyLifecycle($this->db);

        $statusA1 = $this->db->query("SELECT status FROM subscriptions WHERE id = $subA")->fetchColumn();
        $statusB1 = $this->db->query("SELECT status FROM subscriptions WHERE id = $subB")->fetchColumn();
        $statusC1 = $this->db->query("SELECT status FROM subscriptions WHERE id = $subC")->fetchColumn();
        $expiredNotifs1 = (int)$this->db->query("SELECT COUNT(*) FROM notification_logs WHERE notification_type = 'SUBSCRIPTION_EXPIRED'")->fetchColumn();

        $this->assert($statusA1 === 'active', "Sub A must remain active");
        $this->assert($statusB1 === 'protected', "Sub B must transition to protected");
        $this->assert($statusC1 === 'expired', "Sub C must transition to expired");

        // Execution 2 (Same day, re-run)
        SubscriptionService::processDailyLifecycle($this->db);

        $statusA2 = $this->db->query("SELECT status FROM subscriptions WHERE id = $subA")->fetchColumn();
        $statusB2 = $this->db->query("SELECT status FROM subscriptions WHERE id = $subB")->fetchColumn();
        $statusC2 = $this->db->query("SELECT status FROM subscriptions WHERE id = $subC")->fetchColumn();
        $expiredNotifs2 = (int)$this->db->query("SELECT COUNT(*) FROM notification_logs WHERE notification_type = 'SUBSCRIPTION_EXPIRED'")->fetchColumn();

        $this->assert($statusA2 === $statusA1, "Sub A status must be identical on rerun");
        $this->assert($statusB2 === $statusB1, "Sub B status must be identical on rerun");
        $this->assert($statusC2 === $statusC1, "Sub C status must be identical on rerun");
        $this->assert($expiredNotifs2 === $expiredNotifs1, "Expired notifications count must NOT increase on rerun, got $expiredNotifs2 vs $expiredNotifs1");

        // Execution 3 (Third re-run)
        SubscriptionService::processDailyLifecycle($this->db);

        $statusA3 = $this->db->query("SELECT status FROM subscriptions WHERE id = $subA")->fetchColumn();
        $statusB3 = $this->db->query("SELECT status FROM subscriptions WHERE id = $subB")->fetchColumn();
        $statusC3 = $this->db->query("SELECT status FROM subscriptions WHERE id = $subC")->fetchColumn();
        $expiredNotifs3 = (int)$this->db->query("SELECT COUNT(*) FROM notification_logs WHERE notification_type = 'SUBSCRIPTION_EXPIRED'")->fetchColumn();

        $this->assert($statusA3 === $statusA1, "Sub A status stable on run 3");
        $this->assert($statusB3 === $statusB1, "Sub B status stable on run 3");
        $this->assert($statusC3 === $statusC1, "Sub C status stable on run 3");
        $this->assert($expiredNotifs3 === $expiredNotifs1, "Expired notifications count stable on run 3");
        echo "PASS\n";
    }

    /**
     * 26. Cancelled subscription with 0 deliveries never becomes protected at nominal expiry.
     * Transitions directly to expired with expiry_reason = 'cancelled_completion'.
     */
    private function test26_cancelledSubscriptionWithZeroDeliveriesNeverBecomesProtectedAtNominalExpiry(): void {
        echo "[Test 26] Cancelled subscription with 0 deliveries expires at nominal expiry without protection... ";
        $uid = $this->createUser('step6_lifecycle_u26@example.com');
        $startsAt = date('Y-m-d H:i:s', strtotime('-35 days'));
        $endsAt = date('Y-m-d H:i:s', strtotime('-1 day'));
        $subId = $this->createSubscription($uid, 'cancelled', $startsAt, $endsAt);

        // Run daily lifecycle
        SubscriptionService::processDailyLifecycle($this->db);

        $stmt = $this->db->prepare("SELECT status, expiry_reason, final_expired_at FROM subscriptions WHERE id = :id");
        $stmt->execute(['id' => $subId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assert($row['status'] === 'expired', "Cancelled subscription must transition to expired, got {$row['status']}");
        $this->assert($row['expiry_reason'] === 'cancelled_completion', "Expiry reason must be cancelled_completion, got {$row['expiry_reason']}");
        $this->assert(!empty($row['final_expired_at']), "final_expired_at must be populated");

        // Customer access reverts to free plan
        $plan = SubscriptionService::getActivePlan($uid);
        $this->assert($plan['plan_slug'] === 'free', "User must revert to free plan upon cancelled expiry");
        echo "PASS\n";
    }

    /**
     * 27. Cancelled subscription with partial deliveries (1-4) never becomes protected at nominal expiry.
     */
    private function test27_cancelledSubscriptionWithPartialDeliveriesNeverBecomesProtectedAtNominalExpiry(): void {
        echo "[Test 27] Cancelled subscription with partial deliveries (3 delivered) expires without protection... ";
        $uid = $this->createUser('step6_lifecycle_u27@example.com');
        $startsAt = date('Y-m-d H:i:s', strtotime('-35 days'));
        $endsAt = date('Y-m-d H:i:s', strtotime('-1 day'));
        $subId = $this->createSubscription($uid, 'cancelled', $startsAt, $endsAt);

        // Add 3 delivered notifications
        for ($i = 1; $i <= 3; $i++) {
            $this->addNotificationLog($uid, $subId, 'delivered', date('Y-m-d H:i:s', strtotime("-{$i} days")));
        }

        // Run daily lifecycle
        SubscriptionService::processDailyLifecycle($this->db);

        $stmt = $this->db->prepare("SELECT status, expiry_reason, final_expired_at FROM subscriptions WHERE id = :id");
        $stmt->execute(['id' => $subId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assert($row['status'] === 'expired', "Cancelled subscription with 3 deliveries must transition to expired, got {$row['status']}");
        $this->assert($row['expiry_reason'] === 'cancelled_completion', "Expiry reason must be cancelled_completion, got {$row['expiry_reason']}");
        $this->assert(!empty($row['final_expired_at']), "final_expired_at must be populated");

        $plan = SubscriptionService::getActivePlan($uid);
        $this->assert($plan['plan_slug'] === 'free', "User must revert to free plan upon cancelled expiry");
        echo "PASS\n";
    }

    /**
     * 28. State machine strictly prohibits transition from 'cancelled' to 'protected'.
     */
    private function test28_stateMachineProhibitsCancelledToProtectedTransition(): void {
        echo "[Test 28] State machine strictly prohibits cancelled -> protected transition... ";
        $uid = $this->createUser('step6_lifecycle_u28@example.com');
        $startsAt = date('Y-m-d H:i:s', strtotime('-10 days'));
        $endsAt = date('Y-m-d H:i:s', strtotime('+20 days'));
        $subId = $this->createSubscription($uid, 'cancelled', $startsAt, $endsAt);

        // Attempt direct transition via SubscriptionService::transition
        $result = SubscriptionService::transition($subId, 'protected', 'attempted_deficit_protection', $this->db);
        $this->assert($result === false, "transition() from cancelled to protected must return false");

        $stmt = $this->db->prepare("SELECT status FROM subscriptions WHERE id = :id");
        $stmt->execute(['id' => $subId]);
        $status = $stmt->fetchColumn();
        $this->assert($status === 'cancelled', "Subscription status must remain cancelled in DB, got $status");
        echo "PASS\n";
    }

    /**
     * 29. Cancelled subscription before nominal expiry retains premium alert eligibility
     * and automatically attaches subscription_id to scholarship notifications.
     */
    private function test29_cancelledSubscriptionBeforeNominalExpiryReceivesAlertAndAttachesSubscriptionId(): void {
        echo "[Test 29] Cancelled subscription before nominal expiry retains alert eligibility and attaches sub ID... ";
        $uid = $this->createUser('step6_lifecycle_u29@example.com');
        $startsAt = date('Y-m-d H:i:s', strtotime('-10 days'));
        $endsAt = date('Y-m-d H:i:s', strtotime('+20 days'));
        $subId = $this->createSubscription($uid, 'cancelled', $startsAt, $endsAt);

        // 1. Confirm customer contract: retains premium plan & features before ends_at
        $plan = SubscriptionService::getActivePlan($uid);
        $this->assert($plan['plan_slug'] === 'premium-monthly', "Cancelled user before ends_at must retain premium plan, got {$plan['plan_slug']}");
        $this->assert(SubscriptionService::can($uid, 'premium_alerts') === true, "Cancelled user must retain premium_alerts capability");
        $this->assert(SubscriptionService::can($uid, 'whatsapp_alerts') === true, "Cancelled user must retain whatsapp_alerts capability");

        // 2. Create published scholarship
        $schId = $this->createScholarship('Step6 Cancelled Eligible Scholarship');

        // 3. Enqueue NEW_MATCH with null subscriptionId -> must resolve to cancelled sub ID
        $queueService = new NotificationQueueService();
        $enqueued = $queueService->enqueue(
            $uid,
            $schId,
            'NEW_MATCH',
            'email',
            'step6_lifecycle_u29@example.com',
            'Test Match Alert',
            ['title' => 'Test Match Alert', 'summary' => 'Summary']
        );
        $this->assert($enqueued === true, "enqueue() must succeed for cancelled user within paid period");

        // 4. Verify subscription_id was resolved and attached
        $stmt = $this->db->prepare("SELECT subscription_id, status FROM notification_logs WHERE user_id = :uid AND scholarship_id = :sid ORDER BY id DESC LIMIT 1");
        $stmt->execute(['uid' => $uid, 'sid' => $schId]);
        $log = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assert((int)$log['subscription_id'] === $subId, "subscription_id must be attached to cancelled subscription ({$subId}), got {$log['subscription_id']}");

        // 5. Mark delivered and verify countQualifyingDeliveredMessages
        $this->db->exec("UPDATE notification_logs SET status = 'delivered', delivered_at = NOW() WHERE user_id = $uid AND scholarship_id = $schId");
        $deliveredCount = SubscriptionService::countQualifyingDeliveredMessages($subId, $this->db);
        $this->assert($deliveredCount === 1, "Delivered count for cancelled subscription must be 1, got $deliveredCount");
        echo "PASS\n";
    }

    /**
     * 30. Cancelled subscription with 5+ deliveries still expires directly at nominal expiry.
     */
    private function test30_cancelledSubscriptionWithFiveOrMoreDeliveriesStillExpiresDirectlyAtNominalExpiry(): void {
        echo "[Test 30] Cancelled subscription with 5+ deliveries expires directly at nominal expiry... ";
        $uid = $this->createUser('step6_lifecycle_u30@example.com');
        $startsAt = date('Y-m-d H:i:s', strtotime('-35 days'));
        $endsAt = date('Y-m-d H:i:s', strtotime('-1 day'));
        $subId = $this->createSubscription($uid, 'cancelled', $startsAt, $endsAt);

        // Add 6 delivered notifications
        for ($i = 1; $i <= 6; $i++) {
            $this->addNotificationLog($uid, $subId, 'delivered', date('Y-m-d H:i:s', strtotime("-{$i} days")));
        }

        // Run daily lifecycle
        SubscriptionService::processDailyLifecycle($this->db);

        $stmt = $this->db->prepare("SELECT status, expiry_reason, final_expired_at FROM subscriptions WHERE id = :id");
        $stmt->execute(['id' => $subId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assert($row['status'] === 'expired', "Cancelled subscription with 6 deliveries must transition to expired, got {$row['status']}");
        $this->assert($row['expiry_reason'] === 'cancelled_completion', "Expiry reason must be cancelled_completion, got {$row['expiry_reason']}");
        $this->assert(!empty($row['final_expired_at']), "final_expired_at must be populated");
        echo "PASS\n";
    }

    /**
     * 31. Expired cancelled subscription receives no further premium scholarship alerts and falls back to Free plan.
     */
    private function test31_expiredCancelledSubscriptionReceivesNoFurtherScholarshipAlertsAndRevertsToFree(): void {
        echo "[Test 31] Expired cancelled subscription receives no further alerts and reverts to Free plan... ";
        $uid = $this->createUser('step6_lifecycle_u31@example.com');
        $startsAt = date('Y-m-d H:i:s', strtotime('-40 days'));
        $endsAt = date('Y-m-d H:i:s', strtotime('-10 days'));
        $subId = $this->createSubscription($uid, 'expired', $startsAt, $endsAt);
        $this->db->exec("UPDATE subscriptions SET final_expired_at = NOW(), expiry_reason = 'cancelled_completion' WHERE id = $subId");

        // 1. Verify user plan has reverted to Free
        $plan = SubscriptionService::getActivePlan($uid);
        $this->assert($plan['plan_slug'] === 'free', "Active plan must be free after cancelled subscription expires, got {$plan['plan_slug']}");
        $this->assert(SubscriptionService::can($uid, 'premium_alerts') === false, "Expired cancelled user must NOT have premium_alerts");
        $this->assert(SubscriptionService::can($uid, 'whatsapp_alerts') === false, "Expired cancelled user must NOT have whatsapp_alerts");
        $this->assert(SubscriptionService::can($uid, 'deadline_reminders') === false, "Expired cancelled user must NOT have deadline_reminders");

        // 2. Attempt to enqueue NEW_MATCH -> must be rejected by feature gating
        $schId = $this->createScholarship('Step6 Expired Post-Cancel Opportunity');
        $queueService = new NotificationQueueService();
        $enqueued = $queueService->enqueue(
            $uid,
            $schId,
            'NEW_MATCH',
            'email',
            'step6_lifecycle_u31@example.com',
            'Test Match Alert After Expiry',
            ['title' => 'Test Match', 'summary' => 'Summary']
        );
        $this->assert($enqueued === false, "enqueue() must return false for match alert when user has reverted to Free plan");

        // 3. Attempt to enqueue DEADLINE_REMINDER -> must be rejected by feature gating
        $enqueuedDeadline = $queueService->enqueue(
            $uid,
            $schId,
            'SCHOLARSHIP_DEADLINE_SOON',
            'email',
            'step6_lifecycle_u31@example.com',
            'Test Deadline Alert After Expiry',
            ['title' => 'Test Deadline', 'summary' => 'Summary']
        );
        $this->assert($enqueuedDeadline === false, "enqueue() must return false for deadline alert when user has reverted to Free plan");
        echo "PASS\n";
    }

    /**
     * 32. Post-expiry delivery cannot reactivate or protect the cancelled subscription.
     */
    private function test32_postExpiryDeliveryCannotReactivateOrProtectCancelledSubscription(): void {
        echo "[Test 32] Post-expiry delivery cannot reactivate or protect cancelled subscription... ";
        $uid = $this->createUser('step6_lifecycle_u32@example.com');
        $startsAt = date('Y-m-d H:i:s', strtotime('-40 days'));
        $endsAt = date('Y-m-d H:i:s', strtotime('-10 days'));
        $subId = $this->createSubscription($uid, 'expired', $startsAt, $endsAt);
        $finalExpiredAt = date('Y-m-d H:i:s', strtotime('-10 days'));
        $this->db->exec("UPDATE subscriptions SET final_expired_at = '$finalExpiredAt', expiry_reason = 'cancelled_completion' WHERE id = $subId");

        // Create a pending notification with a provider message ID
        $schId = $this->createScholarship('Step6 Post-Expiry Delivery Scholarship');
        $provMsgId = 'prov_post_exp_' . uniqid();
        $notifId = $this->addNotificationLog($uid, $subId, 'sent', null, 'NEW_MATCH', null);
        $this->db->exec("UPDATE notification_logs SET provider_message_id = '$provMsgId', scholarship_id = $schId, created_at = DATE_SUB(NOW(), INTERVAL 11 DAY) WHERE id = $notifId");

        // A webhook arrives now (post-expiry) marking it delivered
        $queueService = new NotificationQueueService();
        $res = $queueService->recordDeliveryStatus($provMsgId, 'delivered', date('Y-m-d H:i:s'));
        $this->assert($res['success'] === true, "Delivery status update must succeed in notification_logs");

        // Verify subscriptions table remains expired
        $stmt = $this->db->prepare("SELECT status, final_expired_at, expiry_reason FROM subscriptions WHERE id = :id");
        $stmt->execute(['id' => $subId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assert($row['status'] === 'expired', "Subscription status must remain expired, got {$row['status']}");

        // Run lifecycle again to prove lifecycle never reactivates or protects expired subscription
        SubscriptionService::processDailyLifecycle($this->db);

        $stmt->execute(['id' => $subId]);
        $rowAfter = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assert($rowAfter['status'] === 'expired', "Subscription status must still be expired after lifecycle run");

        // countQualifyingDeliveredMessages should NOT count deliveries delivered after final_expired_at
        $qualifyingCount = SubscriptionService::countQualifyingDeliveredMessages($subId, $this->db);
        $this->assert($qualifyingCount === 0, "Deliveries after final_expired_at must NOT be counted as qualifying, got $qualifyingCount");
        echo "PASS\n";
    }
}

if (php_sapi_name() === 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === realpath(__FILE__)) {
    (new Step6SubscriptionProtectionLifecycleTest())->run();
}
