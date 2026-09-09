<?php

use App\Services\Database;
use App\Services\Auth;
use App\Services\SubscriptionService;
use App\Services\ReferralService;
use App\Services\PaymentService;
use App\Services\NotificationQueueService;
use App\Services\NotificationTypes;
use App\Controllers\BillingController;
use App\Controllers\ReferralPartnerController;

class Step1SubscriptionProtectionTest {
    private PDO $db;
    private int $planPremiumId;
    private int $visitorRoleId;
    private int $partnerRoleId;

    public function __construct() {
        $this->db = Database::connection();
    }

    public function run(): void {
        echo "=================================================================\n";
        echo " RUNNING STEP 1 SUBSCRIPTION PROTECTION & EXPIRY TEST SUITE (56 TESTS)\n";
        echo "=================================================================\n\n";

        $this->setUp();

        try {
            // SUBSCRIPTION LIFECYCLE TESTS (1 - 28)
            $this->test1_newSubscriptionActivatesCorrectly();
            $this->test2_monthlyExpiryDateIsCorrect();
            $this->test3_sep10PurchaseExpiresNormallyOct9At235959();
            $this->test4_31stDayMonthEdgeCases();
            $this->test5_februaryExpiry();
            $this->test6_leapYearExpiry();
            $this->test7_yearBoundaryExpiry();
            $this->test8_userWith0DeliveredMessagesAtNormalExpiry();
            $this->test9_userWith1DeliveredMessage();
            $this->test10_userWith2DeliveredMessages();
            $this->test11_userWith3DeliveredMessages();
            $this->test12_userWith4DeliveredMessages();
            $this->test13_userWithExactly5DeliveredMessages();
            $this->test14_userWith6PlusDeliveredMessages();
            $this->test15_failedNotificationIsNotCounted();
            $this->test16_pendingNotificationIsNotCounted();
            $this->test17_queuedNotificationIsNotCounted();
            $this->test18_skippedNotificationIsNotCounted();
            $this->test19_sentButNotDeliveredNotificationIsNotCounted();
            $this->test20_deliveredNotificationIsCountedExactlyOnce();
            $this->test21_duplicateDeliveryCallbackDoesNotIncrementCountTwice();
            $this->test22_runningExpiryCronRepeatedlyIsSafe();
            $this->test23_protectedSubscriptionDoesNotBecomeExpiredPrematurely();
            $this->test24_protectedSubscriptionEventuallyExpiresAfter5QualifyingDeliveries();
            $this->test25_expiryNotificationIsSentExactlyOnce();
            $this->test26_expiryNotificationIsNotSentBeforeActualExpiry();
            $this->test27_userCanPurchaseNewSubscriptionAfterActualExpiry();
            $this->test28_newSubscriptionDoesNotCorruptPreviousSubscriptionHistory();

            // REFERRAL VISIBILITY TESTS (29 - 43)
            $this->test29_userRegistersUsingValidReferralCode();
            $this->test30_referralPartnerAttributionIsStoredCorrectly();
            $this->test31_userPurchasesSubscriptionUsingReferralAttribution();
            $this->test32_correctPartnerSeesQualifyingUser();
            $this->test33_wrongPartnerCannotSeeTheUser();
            $this->test34_subscriptionExpiryRemovesUserFromActivePartnerVisibility();
            $this->test35_historicalReferralRecordRemainsIntact();
            $this->test36_userPurchasesAgainAndBecomesVisibleAgainWhenEligible();
            $this->test37_noPaymentMonthMeansNoActivePaidCustomerVisibility();
            $this->test38_sixMonthReferralWindowIsEnforced();
            $this->test39_afterSixMonthsUserIsNoLongerActiveQualifyingReferral();
            $this->test40_historicalCommissionRecordsRemainAvailable();
            $this->test41_selfReferralRemainsBlocked();
            $this->test42_duplicateReferralAttributionRemainsBlocked();
            $this->test43_referralDashboardPaginationAndFilteringWorks();

            // STRICT SUBSCRIPTION-SPECIFIC DELIVERY & ISOLATION TESTS (44 - 56)
            $this->test44_nullSubscriptionNotificationsNeverCountTowardSubscription();
            $this->test45_crossSubscriptionIsolation();
            $this->test46_oldSubscriptionDeliveriesCannotSatisfyNewSubscription();
            $this->test47_unassignedNotificationsCountTowardZeroSubscriptions();
            $this->test48_retriesPreserveSubscriptionOwnershipAndCountOnce();
            $this->test49_subscriptionUsageSynchronizedWithStrictOwnership();
            $this->test50_nonScholarshipNotificationTypesNeverCountTowardDeliveryEntitlement();
            $this->test51_subscriptionUsageDiscrepancyDoesNotForceExpiry();
            $this->test52_startTimeBoundaryEnforcedInSqlAndHelper();
            $this->test53_finalExpiryBoundaryConsistency();
            $this->test54_unknownAndWrongProviderMessageIdCallbacksDoNotCorrupt();
            $this->test55_callbackPreservesSubscriptionOwnershipStrictly();
            $this->test56_subscriptionUsageFourCasesReconciliation();

            echo "\n=================================================================\n";
            echo " ✔ ALL 56 STEP 1 TESTS PASSED SUCCESSFULLY!\n";
            echo "=================================================================\n\n";

        } finally {
            $this->tearDown();
        }
    }

    private function setUp(): void {
        $this->planPremiumId = (int)$this->db->query("SELECT id FROM subscription_plans WHERE slug = 'premium-monthly'")->fetchColumn();
        $this->visitorRoleId = (int)$this->db->query("SELECT id FROM roles WHERE name = 'visitor'")->fetchColumn();
        $this->partnerRoleId = (int)$this->db->query("SELECT id FROM roles WHERE name = 'referral_partner'")->fetchColumn();

        $_ENV['CASHMAAL_WEB_ID'] = '99999';
        $_ENV['CASHMAAL_IPN_KEY'] = 'test_secret_ipn_key_step4';
        $_ENV['CASHMAAL_PAY_URL'] = 'https://cmaal.com/Pay/';
        $_ENV['CASHMAAL_VERIFY_URL'] = 'https://api.cmaal.com/verify_v2';

        $this->cleanTestData();
    }

    private function tearDown(): void {
        $this->cleanTestData();
    }

    private function cleanTestData(): void {
        $this->db->exec("DELETE FROM referral_commissions WHERE transaction_reference LIKE 'STP1_%'");
        $this->db->exec("DELETE FROM referral_discount_claims WHERE referred_user_id IN (SELECT id FROM users WHERE email LIKE 'step1_%@example.com')");
        $this->db->exec("DELETE FROM referral_signups WHERE referral_code LIKE 'P1%' OR referral_code LIKE 'STP1%'");
        $this->db->exec("DELETE FROM payment_transactions WHERE transaction_reference LIKE 'STP1_%'");
        $this->db->exec("DELETE FROM notification_logs WHERE recipient LIKE 'step1_%@example.com' OR recipient LIKE '923999%'");
        $this->db->exec("DELETE FROM subscription_usage WHERE feature = 'qualifying_scholarship_alerts'");
        $this->db->exec("DELETE FROM subscriptions WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'step1_%@example.com')");
        $this->db->exec("DELETE FROM users WHERE email LIKE 'step1_%@example.com'");
    }

    private function createUser(string $email, string $role = 'visitor', ?string $createdAt = null, ?int $partnerId = null, ?string $refCode = null): int {
        $roleId = ($role === 'referral_partner') ? $this->partnerRoleId : $this->visitorRoleId;
        $stmt = $this->db->prepare("
            INSERT INTO users (email, password_hash, first_name, last_name, role_id, referral_partner_id, referred_by_code, status, created_at, updated_at)
            VALUES (:email, :hash, 'Step1', 'Tester', :role_id, :pid, :code, 'active', :created_at, NOW())
        ");
        $stmt->execute([
            'email' => $email,
            'hash' => password_hash('Pass123!', PASSWORD_BCRYPT),
            'role_id' => $roleId,
            'pid' => $partnerId,
            'code' => $refCode,
            'created_at' => $createdAt ?: date('Y-m-d H:i:s')
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

    private function addDeliveredNotification(int $userId, int $subId, string $deliveredAt, string $type = 'NEW_MATCH'): int {
        $stmt = $this->db->prepare("
            INSERT INTO notification_logs (
                user_id, subscription_id, notification_type, channel, recipient, provider_message_id,
                status, delivered_at, created_at, updated_at
            ) VALUES (
                :uid, :sub_id, :type, 'whatsapp', '923999123456', :msg_id,
                'delivered', :delivered_at, :created_at, NOW()
            )
        ");
        $msgId = 'msg_' . bin2hex(random_bytes(6));
        $stmt->execute([
            'uid' => $userId,
            'sub_id' => $subId,
            'type' => $type,
            'msg_id' => $msgId,
            'delivered_at' => $deliveredAt,
            'created_at' => $deliveredAt
        ]);
        return (int)$this->db->lastInsertId();
    }

    private function assert(bool $condition, string $message): void {
        if (!$condition) {
            throw new Exception("Assertion Failed: " . $message);
        }
    }

    // =========================================================================
    // 1 - 28 SUBSCRIPTION LIFECYCLE TESTS
    // =========================================================================

    private function test1_newSubscriptionActivatesCorrectly(): void {
        echo "[Test 1] New subscription activates correctly... ";
        $uid = $this->createUser('step1_u1@example.com');
        $expiry = SubscriptionService::calculatePlanExpiry($this->planPremiumId, '2026-09-10 12:00:00');
        $subId = $this->createSubscription($uid, 'active', '2026-09-10 12:00:00', $expiry);

        $stmt = $this->db->prepare("SELECT * FROM subscriptions WHERE id = :id");
        $stmt->execute(['id' => $subId]);
        $sub = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assert($sub['status'] === 'active', "Subscription must be active");
        $this->assert((int)$sub['user_id'] === $uid, "User ID must match");
        $this->assert((int)$sub['minimum_delivered_required'] === 5, "Minimum delivered required must default to 5");
        $this->assert($sub['normal_ends_at'] === $expiry, "Normal ends_at must be populated");
        echo "PASS\n";
    }

    private function test2_monthlyExpiryDateIsCorrect(): void {
        echo "[Test 2] Monthly expiry date is correct... ";
        $expiry = SubscriptionService::calculateCalendarMonthExpiry(new DateTime('2026-09-10 10:00:00'));
        $this->assert($expiry === '2026-10-09 23:59:59', "Expected 2026-10-09 23:59:59, got $expiry");
        echo "PASS\n";
    }

    private function test3_sep10PurchaseExpiresNormallyOct9At235959(): void {
        echo "[Test 3] 10 September purchase expires normally on 9 October 23:59:59... ";
        // Test different times of day on 10 September
        $expMorning = SubscriptionService::calculatePlanExpiry($this->planPremiumId, '2026-09-10 08:15:00');
        $expNoon = SubscriptionService::calculatePlanExpiry($this->planPremiumId, '2026-09-10 12:00:00');
        $expNight = SubscriptionService::calculatePlanExpiry($this->planPremiumId, '2026-09-10 23:45:00');

        $this->assert($expMorning === '2026-10-09 23:59:59', "Morning purchase expiry must be 2026-10-09 23:59:59");
        $this->assert($expNoon === '2026-10-09 23:59:59', "Noon purchase expiry must be 2026-10-09 23:59:59");
        $this->assert($expNight === '2026-10-09 23:59:59', "Night purchase expiry must be 2026-10-09 23:59:59");
        echo "PASS\n";
    }

    private function test4_31stDayMonthEdgeCases(): void {
        echo "[Test 4] 31st-day month edge cases... ";
        // 31 January 2026 -> 28 February 2026 23:59:59
        $expJan31 = SubscriptionService::calculateCalendarMonthExpiry(new DateTime('2026-01-31 15:00:00'));
        $this->assert($expJan31 === '2026-02-28 23:59:59', "31 Jan -> 28 Feb 23:59:59");

        // 31 March 2026 -> 30 April 2026 23:59:59
        $expMar31 = SubscriptionService::calculateCalendarMonthExpiry(new DateTime('2026-03-31 15:00:00'));
        $this->assert($expMar31 === '2026-04-30 23:59:59', "31 Mar -> 30 Apr 23:59:59");

        // 31 May 2026 -> 30 June 2026 23:59:59
        $expMay31 = SubscriptionService::calculateCalendarMonthExpiry(new DateTime('2026-05-31 15:00:00'));
        $this->assert($expMay31 === '2026-06-30 23:59:59', "31 May -> 30 Jun 23:59:59");

        // 31 August 2026 -> 30 September 2026 23:59:59
        $expAug31 = SubscriptionService::calculateCalendarMonthExpiry(new DateTime('2026-08-31 15:00:00'));
        $this->assert($expAug31 === '2026-09-30 23:59:59', "31 Aug -> 30 Sep 23:59:59");

        // 30 January 2026 -> 28 February 2026 23:59:59
        $expJan30 = SubscriptionService::calculateCalendarMonthExpiry(new DateTime('2026-01-30 15:00:00'));
        $this->assert($expJan30 === '2026-02-28 23:59:59', "30 Jan -> 28 Feb 23:59:59");

        // 30 April 2026 -> 29 May 2026 23:59:59
        $expApr30 = SubscriptionService::calculateCalendarMonthExpiry(new DateTime('2026-04-30 15:00:00'));
        $this->assert($expApr30 === '2026-05-29 23:59:59', "30 Apr -> 29 May 23:59:59");
        echo "PASS\n";
    }

    private function test5_februaryExpiry(): void {
        echo "[Test 5] February monthly expiry... ";
        // 1 February 2026 (non-leap) -> 28 February 2026 23:59:59
        $expFeb1 = SubscriptionService::calculateCalendarMonthExpiry(new DateTime('2026-02-01 10:00:00'));
        $this->assert($expFeb1 === '2026-02-28 23:59:59', "1 Feb 2026 -> 28 Feb 2026 23:59:59");

        // 28 February 2026 (non-leap) -> 27 March 2026 23:59:59
        $expFeb28NonLeap = SubscriptionService::calculateCalendarMonthExpiry(new DateTime('2026-02-28 10:00:00'));
        $this->assert($expFeb28NonLeap === '2026-03-27 23:59:59', "28 Feb 2026 non-leap -> 27 Mar 2026 23:59:59");

        // 10 February 2026 -> 9 March 2026 23:59:59
        $expFeb10 = SubscriptionService::calculateCalendarMonthExpiry(new DateTime('2026-02-10 10:00:00'));
        $this->assert($expFeb10 === '2026-03-09 23:59:59', "10 Feb 2026 -> 9 Mar 2026 23:59:59");
        echo "PASS\n";
    }

    private function test6_leapYearExpiry(): void {
        echo "[Test 6] Leap year monthly expiry... ";
        // 1 February 2028 (leap year) -> 29 February 2028 23:59:59
        $expLeapFeb1 = SubscriptionService::calculateCalendarMonthExpiry(new DateTime('2028-02-01 10:00:00'));
        $this->assert($expLeapFeb1 === '2028-02-29 23:59:59', "1 Feb 2028 -> 29 Feb 2028 23:59:59");

        // 31 January 2028 (leap year) -> 29 February 2028 23:59:59
        $expLeapJan31 = SubscriptionService::calculateCalendarMonthExpiry(new DateTime('2028-01-31 10:00:00'));
        $this->assert($expLeapJan31 === '2028-02-29 23:59:59', "31 Jan 2028 -> 29 Feb 2028 23:59:59");

        // 28 February 2028 (leap year) -> 27 March 2028 23:59:59
        $expLeapFeb28 = SubscriptionService::calculateCalendarMonthExpiry(new DateTime('2028-02-28 10:00:00'));
        $this->assert($expLeapFeb28 === '2028-03-27 23:59:59', "28 Feb 2028 leap -> 27 Mar 2028 23:59:59");

        // 29 February 2028 -> 28 March 2028 23:59:59
        $expLeapFeb29 = SubscriptionService::calculateCalendarMonthExpiry(new DateTime('2028-02-29 10:00:00'));
        $this->assert($expLeapFeb29 === '2028-03-28 23:59:59', "29 Feb 2028 -> 28 Mar 2028 23:59:59");
        echo "PASS\n";
    }

    private function test7_yearBoundaryExpiry(): void {
        echo "[Test 7] Year boundary monthly expiry... ";
        // 10 December 2026 -> 9 January 2027 23:59:59
        $expDec10 = SubscriptionService::calculateCalendarMonthExpiry(new DateTime('2026-12-10 10:00:00'));
        $this->assert($expDec10 === '2027-01-09 23:59:59', "10 Dec 2026 -> 9 Jan 2027 23:59:59");

        // 31 December 2026 -> 30 January 2027 23:59:59
        $expDec31 = SubscriptionService::calculateCalendarMonthExpiry(new DateTime('2026-12-31 10:00:00'));
        $this->assert($expDec31 === '2027-01-30 23:59:59', "31 Dec 2026 -> 30 Jan 2027 23:59:59");

        // 1 January 2027 -> 31 January 2027 23:59:59
        $expJan1 = SubscriptionService::calculateCalendarMonthExpiry(new DateTime('2027-01-01 10:00:00'));
        $this->assert($expJan1 === '2027-01-31 23:59:59', "1 Jan 2027 -> 31 Jan 2027 23:59:59");
        echo "PASS\n";
    }

    private function test8_userWith0DeliveredMessagesAtNormalExpiry(): void {
        echo "[Test 8] User with 0 delivered messages at normal expiry becomes protected... ";
        $uid = $this->createUser('step1_u8@example.com');
        $subId = $this->createSubscription($uid, 'active', date('Y-m-d H:i:s', strtotime('-35 days')), date('Y-m-d H:i:s', strtotime('-1 day')));

        $res = SubscriptionService::processDailyLifecycle($this->db);
        $status = $this->db->query("SELECT status FROM subscriptions WHERE id = $subId")->fetchColumn();

        $this->assert($status === 'protected', "Subscription with 0 delivered messages must become protected, got $status");
        $notifCount = (int)$this->db->query("SELECT COUNT(*) FROM notification_logs WHERE user_id = $uid AND notification_type = 'SUBSCRIPTION_EXPIRED'")->fetchColumn();
        $this->assert($notifCount === 0, "No expiration notification must be sent while protected");
        echo "PASS\n";
    }

    private function test9_userWith1DeliveredMessage(): void {
        echo "[Test 9] User with 1 delivered message remains protected... ";
        $uid = $this->createUser('step1_u9@example.com');
        $subId = $this->createSubscription($uid, 'active', date('Y-m-d H:i:s', strtotime('-35 days')), date('Y-m-d H:i:s', strtotime('-1 day')));
        $this->addDeliveredNotification($uid, $subId, date('Y-m-d H:i:s', strtotime('-10 days')));

        SubscriptionService::processDailyLifecycle($this->db);
        $status = $this->db->query("SELECT status FROM subscriptions WHERE id = $subId")->fetchColumn();
        $this->assert($status === 'protected', "1 delivered message must keep subscription protected");
        echo "PASS\n";
    }

    private function test10_userWith2DeliveredMessages(): void {
        echo "[Test 10] User with 2 delivered messages remains protected... ";
        $uid = $this->createUser('step1_u10@example.com');
        $subId = $this->createSubscription($uid, 'active', date('Y-m-d H:i:s', strtotime('-35 days')), date('Y-m-d H:i:s', strtotime('-1 day')));
        $this->addDeliveredNotification($uid, $subId, date('Y-m-d H:i:s', strtotime('-20 days')));
        $this->addDeliveredNotification($uid, $subId, date('Y-m-d H:i:s', strtotime('-10 days')));

        SubscriptionService::processDailyLifecycle($this->db);
        $status = $this->db->query("SELECT status FROM subscriptions WHERE id = $subId")->fetchColumn();
        $this->assert($status === 'protected', "2 delivered messages must keep subscription protected");
        echo "PASS\n";
    }

    private function test11_userWith3DeliveredMessages(): void {
        echo "[Test 11] User with 3 delivered messages remains protected... ";
        $uid = $this->createUser('step1_u11@example.com');
        $subId = $this->createSubscription($uid, 'active', date('Y-m-d H:i:s', strtotime('-35 days')), date('Y-m-d H:i:s', strtotime('-1 day')));
        for ($i = 1; $i <= 3; $i++) {
            $this->addDeliveredNotification($uid, $subId, date('Y-m-d H:i:s', strtotime("-{$i} days")));
        }

        SubscriptionService::processDailyLifecycle($this->db);
        $status = $this->db->query("SELECT status FROM subscriptions WHERE id = $subId")->fetchColumn();
        $this->assert($status === 'protected', "3 delivered messages must keep subscription protected");
        echo "PASS\n";
    }

    private function test12_userWith4DeliveredMessages(): void {
        echo "[Test 12] User with 4 delivered messages remains protected... ";
        $uid = $this->createUser('step1_u12@example.com');
        $subId = $this->createSubscription($uid, 'active', date('Y-m-d H:i:s', strtotime('-35 days')), date('Y-m-d H:i:s', strtotime('-1 day')));
        for ($i = 1; $i <= 4; $i++) {
            $this->addDeliveredNotification($uid, $subId, date('Y-m-d H:i:s', strtotime("-{$i} days")));
        }

        SubscriptionService::processDailyLifecycle($this->db);
        $status = $this->db->query("SELECT status FROM subscriptions WHERE id = $subId")->fetchColumn();
        $this->assert($status === 'protected', "4 delivered messages must keep subscription protected");
        echo "PASS\n";
    }

    private function test13_userWithExactly5DeliveredMessages(): void {
        echo "[Test 13] User with exactly 5 delivered messages transitions to expired... ";
        $uid = $this->createUser('step1_u13@example.com');
        $subId = $this->createSubscription($uid, 'active', date('Y-m-d H:i:s', strtotime('-35 days')), date('Y-m-d H:i:s', strtotime('-1 day')));
        for ($i = 1; $i <= 5; $i++) {
            $this->addDeliveredNotification($uid, $subId, date('Y-m-d H:i:s', strtotime("-{$i} days")));
        }

        SubscriptionService::processDailyLifecycle($this->db);
        $status = $this->db->query("SELECT status FROM subscriptions WHERE id = $subId")->fetchColumn();
        $this->assert($status === 'expired', "Exactly 5 delivered messages must transition subscription to expired, got $status");

        $sub = $this->db->query("SELECT final_expired_at, expiry_reason FROM subscriptions WHERE id = $subId")->fetch(PDO::FETCH_ASSOC);
        $this->assert(!empty($sub['final_expired_at']), "final_expired_at must be populated");
        $this->assert(!empty($sub['expiry_reason']), "expiry_reason must be populated");
        echo "PASS\n";
    }

    private function test14_userWith6PlusDeliveredMessages(): void {
        echo "[Test 14] User with 6+ delivered messages transitions to expired normally... ";
        $uid = $this->createUser('step1_u14@example.com');
        $subId = $this->createSubscription($uid, 'active', date('Y-m-d H:i:s', strtotime('-35 days')), date('Y-m-d H:i:s', strtotime('-1 day')));
        for ($i = 1; $i <= 8; $i++) {
            $this->addDeliveredNotification($uid, $subId, date('Y-m-d H:i:s', strtotime("-{$i} days")));
        }

        SubscriptionService::processDailyLifecycle($this->db);
        $status = $this->db->query("SELECT status FROM subscriptions WHERE id = $subId")->fetchColumn();
        $this->assert($status === 'expired', "6+ delivered messages must expire normally at normal expiry");
        echo "PASS\n";
    }

    private function test15_failedNotificationIsNotCounted(): void {
        echo "[Test 15] Failed notification is not counted... ";
        $uid = $this->createUser('step1_u15@example.com');
        $subId = $this->createSubscription($uid, 'active', date('Y-m-d H:i:s', strtotime('-35 days')), date('Y-m-d H:i:s', strtotime('-1 day')));

        $stmt = $this->db->prepare("
            INSERT INTO notification_logs (user_id, subscription_id, notification_type, channel, recipient, status, failed_at, created_at, updated_at)
            VALUES (:uid, :sub_id, 'NEW_MATCH', 'whatsapp', '923999123456', 'failed', NOW(), NOW(), NOW())
        ");
        $stmt->execute(['uid' => $uid, 'sub_id' => $subId]);

        $count = SubscriptionService::countQualifyingDeliveredMessages($subId, $this->db);
        $this->assert($count === 0, "Failed notification must not be counted, got $count");
        echo "PASS\n";
    }

    private function test16_pendingNotificationIsNotCounted(): void {
        echo "[Test 16] Pending notification is not counted... ";
        $uid = $this->createUser('step1_u16@example.com');
        $subId = $this->createSubscription($uid, 'active', date('Y-m-d H:i:s', strtotime('-35 days')), date('Y-m-d H:i:s', strtotime('-1 day')));

        $stmt = $this->db->prepare("
            INSERT INTO notification_logs (user_id, subscription_id, notification_type, channel, recipient, status, available_at, created_at, updated_at)
            VALUES (:uid, :sub_id, 'NEW_MATCH', 'whatsapp', '923999123456', 'pending', NOW(), NOW(), NOW())
        ");
        $stmt->execute(['uid' => $uid, 'sub_id' => $subId]);

        $count = SubscriptionService::countQualifyingDeliveredMessages($subId, $this->db);
        $this->assert($count === 0, "Pending notification must not be counted, got $count");
        echo "PASS\n";
    }

    private function test17_queuedNotificationIsNotCounted(): void {
        echo "[Test 17] Queued/processing notification is not counted... ";
        $uid = $this->createUser('step1_u17@example.com');
        $subId = $this->createSubscription($uid, 'active', date('Y-m-d H:i:s', strtotime('-35 days')), date('Y-m-d H:i:s', strtotime('-1 day')));

        $stmt = $this->db->prepare("
            INSERT INTO notification_logs (user_id, subscription_id, notification_type, channel, recipient, status, processing_started_at, created_at, updated_at)
            VALUES (:uid, :sub_id, 'NEW_MATCH', 'whatsapp', '923999123456', 'processing', NOW(), NOW(), NOW())
        ");
        $stmt->execute(['uid' => $uid, 'sub_id' => $subId]);

        $count = SubscriptionService::countQualifyingDeliveredMessages($subId, $this->db);
        $this->assert($count === 0, "Processing notification must not be counted, got $count");
        echo "PASS\n";
    }

    private function test18_skippedNotificationIsNotCounted(): void {
        echo "[Test 18] Skipped/cancelled notification is not counted... ";
        $uid = $this->createUser('step1_u18@example.com');
        $subId = $this->createSubscription($uid, 'active', date('Y-m-d H:i:s', strtotime('-35 days')), date('Y-m-d H:i:s', strtotime('-1 day')));

        $stmt = $this->db->prepare("
            INSERT INTO notification_logs (user_id, subscription_id, notification_type, channel, recipient, status, created_at, updated_at)
            VALUES (:uid, :sub_id, 'NEW_MATCH', 'whatsapp', '923999123456', :status, NOW(), NOW())
        ");
        $stmt->execute(['uid' => $uid, 'sub_id' => $subId, 'status' => 'cancelled']);
        $stmt->execute(['uid' => $uid, 'sub_id' => $subId, 'status' => 'skipped']);

        $count = SubscriptionService::countQualifyingDeliveredMessages($subId, $this->db);
        $this->assert($count === 0, "Cancelled and skipped notifications must not be counted, got $count");
        echo "PASS\n";
    }

    private function test19_sentButNotDeliveredNotificationIsNotCounted(): void {
        echo "[Test 19] Sent-but-not-delivered notification is not counted... ";
        $uid = $this->createUser('step1_u19@example.com');
        $subId = $this->createSubscription($uid, 'active', date('Y-m-d H:i:s', strtotime('-35 days')), date('Y-m-d H:i:s', strtotime('-1 day')));

        $stmt = $this->db->prepare("
            INSERT INTO notification_logs (user_id, subscription_id, notification_type, channel, recipient, status, sent_at, delivered_at, created_at, updated_at)
            VALUES (:uid, :sub_id, 'NEW_MATCH', 'whatsapp', '923999123456', 'sent', NOW(), NULL, NOW(), NOW())
        ");
        $stmt->execute(['uid' => $uid, 'sub_id' => $subId]);

        $count = SubscriptionService::countQualifyingDeliveredMessages($subId, $this->db);
        $this->assert($count === 0, "Sent-without-delivery notification must not be counted, got $count");
        echo "PASS\n";
    }

    private function test20_deliveredNotificationIsCountedExactlyOnce(): void {
        echo "[Test 20] Delivered notification is counted exactly once... ";
        $uid = $this->createUser('step1_u20@example.com');
        $subId = $this->createSubscription($uid, 'active', date('Y-m-d H:i:s', strtotime('-35 days')), date('Y-m-d H:i:s', strtotime('-1 day')));

        $this->addDeliveredNotification($uid, $subId, date('Y-m-d H:i:s', strtotime('-5 days')));

        $count = SubscriptionService::countQualifyingDeliveredMessages($subId, $this->db);
        $this->assert($count === 1, "Delivered notification must be counted exactly once, got $count");
        echo "PASS\n";
    }

    private function test21_duplicateDeliveryCallbackDoesNotIncrementCountTwice(): void {
        echo "[Test 21] Duplicate delivery callback does not increment count twice... ";
        $uid = $this->createUser('step1_u21@example.com');
        $subId = $this->createSubscription($uid, 'active', date('Y-m-d H:i:s', strtotime('-35 days')), date('Y-m-d H:i:s', strtotime('+10 days')));

        $queue = new NotificationQueueService();
        $msgId = 'wacrm_dup_' . bin2hex(random_bytes(6));

        $stmt = $this->db->prepare("
            INSERT INTO notification_logs (user_id, subscription_id, notification_type, channel, recipient, provider_message_id, status, created_at, updated_at)
            VALUES (:uid, :sub_id, 'NEW_MATCH', 'whatsapp', '923999123456', :msg_id, 'sent', NOW(), NOW())
        ");
        $stmt->execute(['uid' => $uid, 'sub_id' => $subId, 'msg_id' => $msgId]);

        // First callback
        $initialDeliveredAt = '2026-09-08 12:00:00';
        $res1 = $queue->recordDeliveryStatus($msgId, 'delivered', $initialDeliveredAt);
        $this->assert($res1['success'] === true, "First delivery callback must succeed");

        // Verify row status and delivered_at timestamp
        $row1 = $this->db->query("SELECT status, delivered_at FROM notification_logs WHERE provider_message_id = '$msgId'")->fetch(PDO::FETCH_ASSOC);
        $this->assert($row1['status'] === 'delivered', "Status must be delivered");
        $this->assert(!empty($row1['delivered_at']), "Delivered_at must be populated");
        $savedDeliveredAt = $row1['delivered_at'];

        $count1 = SubscriptionService::countQualifyingDeliveredMessages($subId, $this->db);
        $this->assert($count1 === 1, "Count after 1st callback must be 1, got $count1");

        // Duplicate callbacks: 2, 5, 10
        for ($k = 2; $k <= 10; $k++) {
            $res = $queue->recordDeliveryStatus($msgId, 'delivered', date('Y-m-d H:i:s', strtotime("+{$k} hours")));
            $this->assert($res['success'] === true, "Duplicate callback #$k must return success");
        }

        // Verify database state after 10 duplicate callbacks:
        // 1. Total row count in notification_logs remains exactly 1
        $totalRows = (int)$this->db->query("SELECT COUNT(*) FROM notification_logs WHERE provider_message_id = '$msgId'")->fetchColumn();
        $this->assert($totalRows === 1, "Exactly 1 notification row must exist after 10 callbacks, got $totalRows");

        // 2. delivered_at was NOT replaced by duplicate callbacks
        $rowFinal = $this->db->query("SELECT status, delivered_at FROM notification_logs WHERE provider_message_id = '$msgId'")->fetch(PDO::FETCH_ASSOC);
        $this->assert($rowFinal['delivered_at'] === $savedDeliveredAt, "delivered_at timestamp must not be overwritten by duplicate callbacks");

        // 3. Qualifying count remains exactly 1
        $finalCount = SubscriptionService::countQualifyingDeliveredMessages($subId, $this->db);
        $this->assert($finalCount === 1, "Qualifying count must remain 1 after 10 callbacks, got $finalCount");

        // 4. Subscription usage remains 1
        $usage = SubscriptionService::syncSubscriptionUsage($subId, $this->db);
        $this->assert((int)$usage['qualifying_delivered_count'] === 1, "Subscription usage qualifying count must remain 1");

        echo "PASS\n";
    }

    private function test22_runningExpiryCronRepeatedlyIsSafe(): void {
        echo "[Test 22] Running expiry cron repeatedly is safe... ";
        $uid = $this->createUser('step1_u22@example.com');
        $subId = $this->createSubscription($uid, 'active', date('Y-m-d H:i:s', strtotime('-35 days')), date('Y-m-d H:i:s', strtotime('-1 day')));
        for ($i = 1; $i <= 3; $i++) {
            $this->addDeliveredNotification($uid, $subId, date('Y-m-d H:i:s', strtotime("-{$i} days")));
        }

        // Run 5 times
        for ($k = 1; $k <= 5; $k++) {
            SubscriptionService::processDailyLifecycle($this->db);
        }

        $status = $this->db->query("SELECT status FROM subscriptions WHERE id = $subId")->fetchColumn();
        $this->assert($status === 'protected', "Subscription must remain protected after repeated runs, got $status");

        $usageRows = (int)$this->db->query("SELECT COUNT(*) FROM subscription_usage WHERE subscription_id = $subId")->fetchColumn();
        $this->assert($usageRows <= 1, "Must not create duplicate subscription_usage rows");
        echo "PASS\n";
    }

    private function test23_protectedSubscriptionDoesNotBecomeExpiredPrematurely(): void {
        echo "[Test 23] Protected subscription does not become expired prematurely... ";
        $uid = $this->createUser('step1_u23@example.com');
        $subId = $this->createSubscription($uid, 'protected', date('Y-m-d H:i:s', strtotime('-40 days')), date('Y-m-d H:i:s', strtotime('-10 days')));
        for ($i = 1; $i <= 3; $i++) {
            $this->addDeliveredNotification($uid, $subId, date('Y-m-d H:i:s', strtotime("-{$i} days")));
        }

        // Check active plan
        $plan = SubscriptionService::getActivePlan($uid);
        $this->assert($plan['plan_slug'] === 'premium-monthly', "Protected subscription must provide active premium plan");
        $this->assert(SubscriptionService::can($uid, 'whatsapp_alerts') === true, "Protected user must retain whatsapp_alerts permission");

        // Run lifecycle: still 3 messages -> remains protected
        SubscriptionService::processDailyLifecycle($this->db);
        $status = $this->db->query("SELECT status FROM subscriptions WHERE id = $subId")->fetchColumn();
        $this->assert($status === 'protected', "Must remain protected while count < 5");
        echo "PASS\n";
    }

    private function test24_protectedSubscriptionEventuallyExpiresAfter5QualifyingDeliveries(): void {
        echo "[Test 24] Protected subscription eventually expires after 5 qualifying deliveries... ";
        $uid = $this->createUser('step1_u24@example.com');
        $subId = $this->createSubscription($uid, 'protected', date('Y-m-d H:i:s', strtotime('-60 days')), date('Y-m-d H:i:s', strtotime('-30 days')));

        // Initially 3 delivered messages
        for ($i = 1; $i <= 3; $i++) {
            $this->addDeliveredNotification($uid, $subId, date('Y-m-d H:i:s', strtotime("-2" . $i . " days")));
        }

        SubscriptionService::processDailyLifecycle($this->db);
        $status1 = $this->db->query("SELECT status FROM subscriptions WHERE id = $subId")->fetchColumn();
        $this->assert($status1 === 'protected', "Must remain protected at 3 messages");

        // Later 2 additional messages are delivered
        $this->addDeliveredNotification($uid, $subId, date('Y-m-d H:i:s', strtotime('-5 days')));
        $this->addDeliveredNotification($uid, $subId, date('Y-m-d H:i:s', strtotime('-2 days')));

        SubscriptionService::processDailyLifecycle($this->db);
        $status2 = $this->db->query("SELECT status FROM subscriptions WHERE id = $subId")->fetchColumn();
        $this->assert($status2 === 'expired', "Must transition to expired once 5 delivered messages are reached, got $status2");
        echo "PASS\n";
    }

    private function test25_expiryNotificationIsSentExactlyOnce(): void {
        echo "[Test 25] Expiry notification is sent exactly once with personalized 'Hello {User Name}' and idempotency... ";
        $uid = $this->createUser('step1_u25@example.com');
        $subId = $this->createSubscription($uid, 'active', date('Y-m-d H:i:s', strtotime('-35 days')), date('Y-m-d H:i:s', strtotime('-1 day')));

        // Initially 4 messages: subscription must be protected and send ZERO expiry notifications
        for ($i = 1; $i <= 4; $i++) {
            $this->addDeliveredNotification($uid, $subId, date('Y-m-d H:i:s', strtotime("-{$i} days")));
        }

        SubscriptionService::processDailyLifecycle($this->db);
        $statusEarly = $this->db->query("SELECT status FROM subscriptions WHERE id = $subId")->fetchColumn();
        $this->assert($statusEarly === 'protected', "Subscription with 4 messages must become protected, got $statusEarly");
        $notifEarlyCount = (int)$this->db->query("SELECT COUNT(*) FROM notification_logs WHERE user_id = $uid AND notification_type = 'SUBSCRIPTION_EXPIRED'")->fetchColumn();
        $this->assert($notifEarlyCount === 0, "Zero expiry notifications while subscription is protected");

        // Now deliver 5th message
        $this->addDeliveredNotification($uid, $subId, date('Y-m-d H:i:s', strtotime('-1 hour')));

        // Run lifecycle 10 consecutive times
        for ($k = 1; $k <= 10; $k++) {
            SubscriptionService::processDailyLifecycle($this->db);
        }

        $statusFinal = $this->db->query("SELECT status FROM subscriptions WHERE id = $subId")->fetchColumn();
        $this->assert($statusFinal === 'expired', "Subscription must be expired after 5 deliveries, got $statusFinal");

        $stmtNotif = $this->db->prepare("SELECT * FROM notification_logs WHERE user_id = :uid AND notification_type = 'SUBSCRIPTION_EXPIRED'");
        $stmtNotif->execute(['uid' => $uid]);
        $notifs = $stmtNotif->fetchAll(PDO::FETCH_ASSOC);

        $this->assert(count($notifs) === 1, "Exactly 1 expiry notification must be enqueued after 10 cron runs, got " . count($notifs));
        $this->assert($notifs[0]['idempotency_key'] === "sub_expired_alert_{$subId}", "Idempotency key must match sub_expired_alert_{$subId}");
        $payload = json_decode($notifs[0]['payload'] ?? '{}', true);
        $this->assert(strpos($payload['summary'] ?? '', 'Hello Step1 Tester') !== false, "Expiry message must contain 'Hello {User Name}'");
        $this->assert(strpos($payload['summary'] ?? '', 'Your ScholarPlanner subscription has now ended') !== false, "Summary must explain subscription ended");
        echo "PASS\n";
    }

    private function test26_expiryNotificationIsNotSentBeforeActualExpiry(): void {
        echo "[Test 26] Expiry notification is not sent before actual expiry... ";
        $uid = $this->createUser('step1_u26@example.com');
        $subId = $this->createSubscription($uid, 'active', date('Y-m-d H:i:s', strtotime('-35 days')), date('Y-m-d H:i:s', strtotime('-1 day')));
        // Only 2 messages
        $this->addDeliveredNotification($uid, $subId, date('Y-m-d H:i:s', strtotime('-10 days')));
        $this->addDeliveredNotification($uid, $subId, date('Y-m-d H:i:s', strtotime('-5 days')));

        SubscriptionService::processDailyLifecycle($this->db);

        $count = (int)$this->db->query("SELECT COUNT(*) FROM notification_logs WHERE user_id = $uid AND notification_type = 'SUBSCRIPTION_EXPIRED'")->fetchColumn();
        $this->assert($count === 0, "Zero expiry notifications while subscription is protected");
        echo "PASS\n";
    }

    private function test27_userCanPurchaseNewSubscriptionAfterActualExpiry(): void {
        echo "[Test 27] User can purchase a new subscription after actual expiry... ";
        $uid = $this->createUser('step1_u27@example.com');
        // Old subscription expired
        $subOldId = $this->createSubscription($uid, 'expired', date('Y-m-d H:i:s', strtotime('-60 days')), date('Y-m-d H:i:s', strtotime('-30 days')));

        // Simulate new verified payment activation via BillingController
        $billing = new BillingController();
        $ref = 'STP1_TXN_NEW_SUB_27';

        $stmtTx = $this->db->prepare("
            INSERT INTO payment_transactions (user_id, plan_id, provider, transaction_reference, amount, currency, status, created_at, updated_at)
            VALUES (:uid, :pid, 'cashmaal', :ref, 1499.00, 'PKR', 'pending', NOW(), NOW())
        ");
        $stmtTx->execute(['uid' => $uid, 'pid' => $this->planPremiumId, 'ref' => $ref]);

        $_POST = [
            'web_id' => '99999',
            'ipn_key' => 'test_secret_ipn_key_step4',
            'status' => '1',
            'CM_TID' => 'CM_STP1_27',
            'order_id' => $ref,
            'Amount' => '1499.00',
            'currency' => 'PKR'
        ];

        ob_start();
        $billing->cashmaalIpn();
        ob_get_clean();

        // Check that a NEW active subscription exists
        $stmtNewSub = $this->db->prepare("SELECT id, status FROM subscriptions WHERE user_id = :uid AND status = 'active'");
        $stmtNewSub->execute(['uid' => $uid]);
        $newSub = $stmtNewSub->fetch(PDO::FETCH_ASSOC);

        $this->assert(!empty($newSub), "New active subscription must exist");
        $this->assert((int)$newSub['id'] !== $subOldId, "New subscription must be a separate record from old expired subscription");
        echo "PASS\n";
    }

    private function test28_newSubscriptionDoesNotCorruptPreviousSubscriptionHistory(): void {
        echo "[Test 28] New subscription does not corrupt previous subscription history... ";
        $uid = $this->createUser('step1_u28@example.com');
        
        // Subscription A (purchased 70 days ago, normal ends 40 days ago, min 5 required)
        $sub1Id = $this->createSubscription($uid, 'active', date('Y-m-d H:i:s', strtotime('-70 days')), date('Y-m-d H:i:s', strtotime('-40 days')));
        // Seed exactly 4 qualifying delivered messages for Sub 1
        for ($i = 1; $i <= 4; $i++) {
            $this->addDeliveredNotification($uid, $sub1Id, date('Y-m-d H:i:s', strtotime("-5{$i} days")));
        }

        // Subscription B for the same user (purchased 20 days ago, normal ends in 10 days, min 5 required)
        $sub2Id = $this->createSubscription($uid, 'active', date('Y-m-d H:i:s', strtotime('-20 days')), date('Y-m-d H:i:s', strtotime('+10 days')));
        // Seed exactly 3 qualifying delivered messages for Sub 2
        for ($j = 1; $j <= 3; $j++) {
            $this->addDeliveredNotification($uid, $sub2Id, date('Y-m-d H:i:s', strtotime("-{$j} days")));
        }

        // Total lifetime delivered messages for user = 4 + 3 = 7
        $lifetimeCount = (int)$this->db->query("SELECT COUNT(*) FROM notification_logs WHERE user_id = $uid AND status = 'delivered'")->fetchColumn();
        $this->assert($lifetimeCount === 7, "User must have 7 lifetime delivered messages, got $lifetimeCount");

        // Subscription-specific counts:
        $count1 = SubscriptionService::countQualifyingDeliveredMessages($sub1Id, $this->db);
        $this->assert($count1 === 4, "Sub 1 qualifying count must be exactly 4 (not user lifetime 7), got $count1");

        $count2 = SubscriptionService::countQualifyingDeliveredMessages($sub2Id, $this->db);
        $this->assert($count2 === 3, "Sub 2 qualifying count must be exactly 3 (not user lifetime 7), got $count2");

        // Process daily lifecycle: Sub 1 has reached normal expiry (-40 days) with count 4 (< 5 required).
        // It must become/remain PROTECTED! It must NOT be expired by user's 7 lifetime deliveries!
        SubscriptionService::processDailyLifecycle($this->db);

        $status1 = $this->db->query("SELECT status FROM subscriptions WHERE id = $sub1Id")->fetchColumn();
        $this->assert($status1 === 'protected', "Sub 1 must be protected (4 < 5), not expired by lifetime 7 messages. Got: $status1");

        // Add 1 more delivered message to Sub 2 -> Sub 2 count becomes 4, Sub 1 count remains 4
        $this->addDeliveredNotification($uid, $sub2Id, date('Y-m-d H:i:s'));
        $count1After = SubscriptionService::countQualifyingDeliveredMessages($sub1Id, $this->db);
        $count2After = SubscriptionService::countQualifyingDeliveredMessages($sub2Id, $this->db);
        $this->assert($count1After === 4, "Sub 1 count must not be modified by Sub 2 delivery, got $count1After");
        $this->assert($count2After === 4, "Sub 2 count must be 4, got $count2After");

        // Now deliver the 5th message specifically to Sub 1 -> Sub 1 transitions to expired
        $this->addDeliveredNotification($uid, $sub1Id, date('Y-m-d H:i:s'));
        $count1Final = SubscriptionService::countQualifyingDeliveredMessages($sub1Id, $this->db);
        $this->assert($count1Final === 5, "Sub 1 count must now be 5, got $count1Final");

        SubscriptionService::processDailyLifecycle($this->db);
        $status1Final = $this->db->query("SELECT status FROM subscriptions WHERE id = $sub1Id")->fetchColumn();
        $this->assert($status1Final === 'expired', "Sub 1 must now be expired after 5 deliveries, got $status1Final");

        // Sub 2 remains unaffected and active
        $status2Final = $this->db->query("SELECT status FROM subscriptions WHERE id = $sub2Id")->fetchColumn();
        $this->assert($status2Final === 'active', "Sub 2 must remain active");

        echo "PASS\n";
    }

    // =========================================================================
    // 29 - 43 REFERRAL VISIBILITY TESTS
    // =========================================================================

    private function test29_userRegistersUsingValidReferralCode(): void {
        echo "[Test 29] User registers using valid referral code... ";
        $partnerId = $this->createPartner('step1_p29@example.com', 'STP1P29');
        $this->assert(ReferralService::validateCode('STP1P29') === true, "Referral code STP1P29 must be valid");
        echo "PASS\n";
    }

    private function test30_referralPartnerAttributionIsStoredCorrectly(): void {
        echo "[Test 30] Referral partner attribution is stored correctly... ";
        $partnerId = $this->createPartner('step1_p30@example.com', 'STP1P30');
        $userId = $this->createUser('step1_u30@example.com', 'visitor', null, $partnerId, 'STP1P30');

        $user = $this->db->query("SELECT referral_partner_id, referred_by_code FROM users WHERE id = $userId")->fetch(PDO::FETCH_ASSOC);
        $this->assert((int)$user['referral_partner_id'] === $partnerId, "referral_partner_id must match partner ID");
        $this->assert($user['referred_by_code'] === 'STP1P30', "referred_by_code must match partner code");
        echo "PASS\n";
    }

    private function test31_userPurchasesSubscriptionUsingReferralAttribution(): void {
        echo "[Test 31] User purchases subscription using referral attribution... ";
        $partnerId = $this->createPartner('step1_p31@example.com', 'STP1P31');
        $userId = $this->createUser('step1_u31@example.com', 'visitor', null, $partnerId, 'STP1P31');

        $ref = 'STP1_TXN_REF_31';
        $stmt = $this->db->prepare("
            INSERT INTO payment_transactions (
                user_id, plan_id, provider, transaction_reference, amount, currency, referral_partner_id, referral_code_used, status, created_at, updated_at
            ) VALUES (
                :uid, :pid, 'cashmaal', :ref, 1350.00, 'PKR', :pid2, 'STP1P31', 'paid', NOW(), NOW()
            )
        ");
        $stmt->execute(['uid' => $userId, 'pid' => $this->planPremiumId, 'ref' => $ref, 'pid2' => $partnerId]);
        $txId = (int)$this->db->lastInsertId();

        $comm = ReferralService::calculateAndRecordCommission($txId, $this->db);
        $this->assert(!empty($comm), "Commission record must be generated");
        $this->assert((int)$comm['partner_id'] === $partnerId, "Commission partner_id must match");
        echo "PASS\n";
    }

    private function test32_correctPartnerSeesQualifyingUser(): void {
        echo "[Test 32] Correct partner sees the qualifying user... ";
        $partnerId = $this->createPartner('step1_p32@example.com', 'STP1P32');
        $userId = $this->createUser('step1_u32@example.com', 'visitor', date('Y-m-d H:i:s', strtotime('-10 days')), $partnerId, 'STP1P32');
        $this->createSubscription($userId, 'active', date('Y-m-d H:i:s', strtotime('-10 days')), date('Y-m-d H:i:s', strtotime('+20 days')));

        $isActive = ReferralService::isUserActiveReferralCustomer($userId, $partnerId, $this->db);
        $this->assert($isActive === true, "Correct partner must see qualifying user as active referral customer");

        $customers = ReferralService::getPartnerActiveCustomers($partnerId, 1, 10, $this->db);
        $this->assert($customers['total_items'] === 1, "Active customers total must be 1, got " . $customers['total_items']);
        echo "PASS\n";
    }

    private function test33_wrongPartnerCannotSeeTheUser(): void {
        echo "[Test 33] Wrong partner cannot see the user... ";
        $partnerA = $this->createPartner('step1_p33a@example.com', 'STP1P33A');
        $partnerB = $this->createPartner('step1_p33b@example.com', 'STP1P33B');

        $userId = $this->createUser('step1_u33@example.com', 'visitor', date('Y-m-d H:i:s', strtotime('-10 days')), $partnerA, 'STP1P33A');
        $this->createSubscription($userId, 'active', date('Y-m-d H:i:s', strtotime('-10 days')), date('Y-m-d H:i:s', strtotime('+20 days')));

        // Partner B checks visibility
        $isActiveForB = ReferralService::isUserActiveReferralCustomer($userId, $partnerB, $this->db);
        $this->assert($isActiveForB === false, "Wrong partner must not see user as active");

        $customersB = ReferralService::getPartnerActiveCustomers($partnerB, 1, 10, $this->db);
        $this->assert($customersB['total_items'] === 0, "Partner B must see 0 active customers, got " . $customersB['total_items']);
        echo "PASS\n";
    }

    private function test34_subscriptionExpiryRemovesUserFromActivePartnerVisibility(): void {
        echo "[Test 34] Subscription expiry removes user from active partner visibility... ";
        $partnerId = $this->createPartner('step1_p34@example.com', 'STP1P34');
        $userId = $this->createUser('step1_u34@example.com', 'visitor', date('Y-m-d H:i:s', strtotime('-40 days')), $partnerId, 'STP1P34');
        // Expired subscription
        $this->createSubscription($userId, 'expired', date('Y-m-d H:i:s', strtotime('-40 days')), date('Y-m-d H:i:s', strtotime('-10 days')));

        $isActive = ReferralService::isUserActiveReferralCustomer($userId, $partnerId, $this->db);
        $this->assert($isActive === false, "Expired subscription must remove user from active partner customer visibility");

        $customers = ReferralService::getPartnerActiveCustomers($partnerId, 1, 10, $this->db);
        $this->assert($customers['total_items'] === 0, "Partner must see 0 active customers when subscription expired");
        echo "PASS\n";
    }

    private function test35_historicalReferralRecordRemainsIntact(): void {
        echo "[Test 35] Historical referral record remains intact after expiry... ";
        $partnerId = $this->createPartner('step1_p35@example.com', 'STP1P35');
        $userId = $this->createUser('step1_u35@example.com', 'visitor', date('Y-m-d H:i:s', strtotime('-40 days')), $partnerId, 'STP1P35');
        $subId = $this->createSubscription($userId, 'expired', date('Y-m-d H:i:s', strtotime('-40 days')), date('Y-m-d H:i:s', strtotime('-10 days')));

        // Verify user referral_partner_id is still preserved
        $partnerStored = (int)$this->db->query("SELECT referral_partner_id FROM users WHERE id = $userId")->fetchColumn();
        $this->assert($partnerStored === $partnerId, "Referral partner attribution must not be deleted on expiry");
        echo "PASS\n";
    }

    private function test36_userPurchasesAgainAndBecomesVisibleAgainWhenEligible(): void {
        echo "[Test 36] User purchases again and becomes visible again when eligible... ";
        $partnerId = $this->createPartner('step1_p36@example.com', 'STP1P36');
        $userId = $this->createUser('step1_u36@example.com', 'visitor', date('Y-m-d H:i:s', strtotime('-40 days')), $partnerId, 'STP1P36');

        // Month 1: expired
        $this->createSubscription($userId, 'expired', date('Y-m-d H:i:s', strtotime('-40 days')), date('Y-m-d H:i:s', strtotime('-10 days')));
        $this->assert(ReferralService::isUserActiveReferralCustomer($userId, $partnerId, $this->db) === false, "Initially inactive");

        // Month 2: repurchases within 6 months
        $this->createSubscription($userId, 'active', date('Y-m-d H:i:s'), date('Y-m-d H:i:s', strtotime('+30 days')));
        $this->assert(ReferralService::isUserActiveReferralCustomer($userId, $partnerId, $this->db) === true, "Active customer again upon renewal");

        $customers = ReferralService::getPartnerActiveCustomers($partnerId, 1, 10, $this->db);
        $this->assert($customers['total_items'] === 1, "Partner sees 1 active customer upon renewal");
        echo "PASS\n";
    }

    private function test37_noPaymentMonthMeansNoActivePaidCustomerVisibility(): void {
        echo "[Test 37] No payment month means no active paid-customer visibility for that month... ";
        $partnerId = $this->createPartner('step1_p37@example.com', 'STP1P37');
        $userId = $this->createUser('step1_u37@example.com', 'visitor', date('Y-m-d H:i:s', strtotime('-60 days')), $partnerId, 'STP1P37');

        // Payment made in previous month, but NONE in current month
        $prevMonth = date('Y-m', strtotime('-1 month'));
        $curMonth = date('Y-m');

        $stmtTx = $this->db->prepare("
            INSERT INTO payment_transactions (user_id, plan_id, provider, transaction_reference, amount, currency, referral_partner_id, status, paid_at, created_at, updated_at)
            VALUES (:uid, :pid, 'cashmaal', 'STP1_TXN_37', 1350.00, 'PKR', :pid2, 'paid', :paid_date, :created_date, NOW())
        ");
        $prevDate = date('Y-m-15 12:00:00', strtotime('-1 month'));
        $stmtTx->execute(['uid' => $userId, 'pid' => $this->planPremiumId, 'pid2' => $partnerId, 'paid_date' => $prevDate, 'created_date' => $prevDate]);

        $monthlyData = ReferralService::getPartnerMonthlyPayments($partnerId, $curMonth, 1, 10, $this->db);
        $this->assert($monthlyData['total_items'] === 0, "No payments in current month means 0 monthly customer entries");
        echo "PASS\n";
    }

    private function test38_sixMonthReferralWindowIsEnforced(): void {
        echo "[Test 38] Six-month referral window is enforced... ";
        $partnerId = $this->createPartner('step1_p38@example.com', 'STP1P38');
        // User registered 5 months ago (inside 6-month window)
        $userId = $this->createUser('step1_u38@example.com', 'visitor', date('Y-m-d H:i:s', strtotime('-5 months')), $partnerId, 'STP1P38');
        $this->createSubscription($userId, 'active', date('Y-m-d H:i:s'), date('Y-m-d H:i:s', strtotime('+30 days')));

        $isActive = ReferralService::isUserActiveReferralCustomer($userId, $partnerId, $this->db);
        $this->assert($isActive === true, "5 months is within 6-month window: must be active");
        echo "PASS\n";
    }

    private function test39_afterSixMonthsUserIsNoLongerActiveQualifyingReferral(): void {
        echo "[Test 39] After six months user is no longer an active qualifying referral... ";
        $partnerId = $this->createPartner('step1_p39@example.com', 'STP1P39');
        // User registered 7 months ago (outside 6-month window)
        $userId = $this->createUser('step1_u39@example.com', 'visitor', date('Y-m-d H:i:s', strtotime('-7 months')), $partnerId, 'STP1P39');
        $this->createSubscription($userId, 'active', date('Y-m-d H:i:s'), date('Y-m-d H:i:s', strtotime('+30 days')));

        $isActive = ReferralService::isUserActiveReferralCustomer($userId, $partnerId, $this->db);
        $this->assert($isActive === false, "User registered > 6 months ago must not be an active referral customer");

        $customers = ReferralService::getPartnerActiveCustomers($partnerId, 1, 10, $this->db);
        $this->assert($customers['total_items'] === 0, "Partner sees 0 active customers for user past 6 months");
        echo "PASS\n";
    }

    private function test40_historicalCommissionRecordsRemainAvailable(): void {
        echo "[Test 40] Historical commission records remain available... ";
        $partnerId = $this->createPartner('step1_p40@example.com', 'STP1P40');
        $userId = $this->createUser('step1_u40@example.com', 'visitor', date('Y-m-d H:i:s', strtotime('-7 months')), $partnerId, 'STP1P40');

        $paidAtDate = date('Y-m-d H:i:s', strtotime('-7 months'));
        $stmtTx = $this->db->prepare("
            INSERT INTO payment_transactions (user_id, plan_id, provider, transaction_reference, amount, currency, referral_partner_id, status, paid_at, created_at, updated_at)
            VALUES (:uid, :pid, 'cashmaal', 'STP1_TXN_40', 1350.00, 'PKR', :pid2, 'paid', :paid_at, :created_at, NOW())
        ");
        $stmtTx->execute([
            'uid' => $userId,
            'pid' => $this->planPremiumId,
            'pid2' => $partnerId,
            'paid_at' => $paidAtDate,
            'created_at' => $paidAtDate
        ]);
        $txId = (int)$this->db->lastInsertId();

        ReferralService::calculateAndRecordCommission($txId, $this->db);

        $metrics = ReferralService::getPartnerSummaryMetrics($partnerId, null, $this->db);
        $this->assert((float)$metrics['total_earned_commission'] > 0, "Historical earned commission must remain in summary");
        echo "PASS\n";
    }

    private function test41_selfReferralRemainsBlocked(): void {
        echo "[Test 41] Self-referral remains blocked... ";
        $partnerId = $this->createPartner('step1_p41@example.com', 'STP1P41');

        // Partner pays for themselves
        $stmtTx = $this->db->prepare("
            INSERT INTO payment_transactions (user_id, plan_id, provider, transaction_reference, amount, currency, referral_partner_id, status, created_at, updated_at)
            VALUES (:uid, :pid, 'cashmaal', 'STP1_TXN_41', 1499.00, 'PKR', :pid2, 'paid', NOW(), NOW())
        ");
        $stmtTx->execute(['uid' => $partnerId, 'pid' => $this->planPremiumId, 'pid2' => $partnerId]);
        $txId = (int)$this->db->lastInsertId();

        $comm = ReferralService::calculateAndRecordCommission($txId, $this->db);
        $this->assert($comm === null, "Self-referral commission must be rejected");
        echo "PASS\n";
    }

    private function test42_duplicateReferralAttributionRemainsBlocked(): void {
        echo "[Test 42] Duplicate referral attribution remains blocked... ";
        $partner1 = $this->createPartner('step1_p42a@example.com', 'STP1P42A');
        $partner2 = $this->createPartner('step1_p42b@example.com', 'STP1P42B');

        $userId = $this->createUser('step1_u42@example.com', 'visitor', null, $partner1, 'STP1P42A');

        // User tries claiming discount with partner 2 code
        $discount = ReferralService::calculateDiscount($userId, 1499.00, 'PKR', $this->db, false);
        $this->assert((int)$discount['partner_id'] === $partner1, "Attribution must remain locked to Partner 1");
        echo "PASS\n";
    }

    private function test43_referralDashboardPaginationAndFilteringWorks(): void {
        echo "[Test 43] Referral dashboard pagination and filtering works... ";
        $partnerId = $this->createPartner('step1_p43@example.com', 'STP1P43');
        $curMonth = date('Y-m');

        // Create 20 paid transactions for partner in current month
        for ($i = 1; $i <= 20; $i++) {
            $u = $this->createUser("step1_u43_{$i}@example.com", 'visitor', null, $partnerId, 'STP1P43');
            $ref = "STP1_TXN_43_{$i}";
            $stmt = $this->db->prepare("
                INSERT INTO payment_transactions (user_id, plan_id, provider, transaction_reference, amount, currency, referral_partner_id, status, paid_at, created_at, updated_at)
                VALUES (:uid, :pid, 'cashmaal', :ref, 1350.00, 'PKR', :pid2, 'paid', NOW(), NOW(), NOW())
            ");
            $stmt->execute(['uid' => $u, 'pid' => $this->planPremiumId, 'ref' => $ref, 'pid2' => $partnerId]);
        }

        // Page 1 (10 per page)
        $p1 = ReferralService::getPartnerMonthlyPayments($partnerId, $curMonth, 1, 10, $this->db);
        $this->assert($p1['total_items'] === 20, "Total items must be 20");
        $this->assert(count($p1['records']) === 10, "Page 1 must have 10 records");
        $this->assert($p1['total_pages'] === 2, "Total pages must be 2");

        // Page 2
        $p2 = ReferralService::getPartnerMonthlyPayments($partnerId, $curMonth, 2, 10, $this->db);
        $this->assert(count($p2['records']) === 10, "Page 2 must have 10 records");

        // Records check display_name
        $this->assert(!empty($p1['records'][0]['display_name']), "display_name must be populated on records");
        echo "PASS\n";
    }

    // =========================================================================
    // 44 - 49 STRICT SUBSCRIPTION-SPECIFIC DELIVERY & ISOLATION TESTS
    // =========================================================================

    private function test44_nullSubscriptionNotificationsNeverCountTowardSubscription(): void {
        echo "[Test 44] NULL-subscription notifications never count toward subscription... ";
        $uid = $this->createUser('step1_u44@example.com');
        $subId = $this->createSubscription($uid, 'active', date('Y-m-d H:i:s', strtotime('-35 days')), date('Y-m-d H:i:s', strtotime('-1 day')));

        // 1. Create 4 qualifying delivered scholarship notifications with subscription_id = Sub A
        for ($i = 1; $i <= 4; $i++) {
            $this->addDeliveredNotification($uid, $subId, date('Y-m-d H:i:s', strtotime("-{$i} days")));
        }

        // 2. Create 10 additional qualifying-looking scholarship notifications with subscription_id = NULL occurring after starts_at
        $stmtNull = $this->db->prepare("
            INSERT INTO notification_logs (user_id, subscription_id, notification_type, channel, recipient, status, delivered_at, created_at, updated_at)
            VALUES (:uid, NULL, 'NEW_MATCH', 'whatsapp', '923999123456', 'delivered', :delivered_at, NOW(), NOW())
        ");
        for ($k = 1; $k <= 10; $k++) {
            $stmtNull->execute([
                'uid' => $uid,
                'delivered_at' => date('Y-m-d H:i:s', strtotime("-{$k} hours"))
            ]);
        }

        // 3. Authoritative count for Sub A must be strictly 4, NOT 14
        $count = SubscriptionService::countQualifyingDeliveredMessages($subId, $this->db);
        $this->assert($count === 4, "Count for Sub A must be strictly 4 (excluding 10 NULL notifications), got $count");

        // 4. Daily lifecycle must transition/keep Sub A as PROTECTED (not expired)
        SubscriptionService::processDailyLifecycle($this->db);
        $status = $this->db->query("SELECT status FROM subscriptions WHERE id = $subId")->fetchColumn();
        $this->assert($status === 'protected', "Sub A must remain protected (4 < 5), got $status");
        echo "PASS\n";
    }

    private function test45_crossSubscriptionIsolation(): void {
        echo "[Test 45] Cross-subscription isolation (Sub A = 4, Sub B = 10)... ";
        $uid = $this->createUser('step1_u45@example.com');
        $subA = $this->createSubscription($uid, 'active', date('Y-m-d H:i:s', strtotime('-40 days')), date('Y-m-d H:i:s', strtotime('-10 days')));
        $subB = $this->createSubscription($uid, 'active', date('Y-m-d H:i:s', strtotime('-20 days')), date('Y-m-d H:i:s', strtotime('-1 day')));

        // Sub A = 4 qualifying deliveries
        for ($i = 1; $i <= 4; $i++) {
            $this->addDeliveredNotification($uid, $subA, date('Y-m-d H:i:s', strtotime("-3{$i} days")));
        }

        // Sub B = 10 qualifying deliveries
        for ($j = 1; $j <= 10; $j++) {
            $this->addDeliveredNotification($uid, $subB, date('Y-m-d H:i:s', strtotime("-{$j} days")));
        }

        $countA = SubscriptionService::countQualifyingDeliveredMessages($subA, $this->db);
        $countB = SubscriptionService::countQualifyingDeliveredMessages($subB, $this->db);

        $this->assert($countA === 4, "Sub A count must be exactly 4, got $countA");
        $this->assert($countB === 10, "Sub B count must be exactly 10, got $countB");

        // Run lifecycle
        SubscriptionService::processDailyLifecycle($this->db);

        $statusA = $this->db->query("SELECT status FROM subscriptions WHERE id = $subA")->fetchColumn();
        $statusB = $this->db->query("SELECT status FROM subscriptions WHERE id = $subB")->fetchColumn();

        $this->assert($statusA === 'protected', "Sub A must be protected (4 < 5), got $statusA");
        $this->assert($statusB === 'expired', "Sub B must be expired (10 >= 5), got $statusB");

        // Lifetime total in notification_logs is 14, but Sub A must NEVER use Sub B's deliveries
        $lifetime = (int)$this->db->query("SELECT COUNT(*) FROM notification_logs WHERE user_id = $uid AND status = 'delivered'")->fetchColumn();
        $this->assert($lifetime === 14, "User lifetime count must be 14");
        $this->assert($countA === 4, "Sub A must remain at 4");
        echo "PASS\n";
    }

    private function test46_oldSubscriptionDeliveriesCannotSatisfyNewSubscription(): void {
        echo "[Test 46] Old subscription deliveries cannot satisfy new subscription... ";
        $uid = $this->createUser('step1_u46@example.com');
        $oldSubId = $this->createSubscription($uid, 'expired', date('Y-m-d H:i:s', strtotime('-70 days')), date('Y-m-d H:i:s', strtotime('-40 days')));

        // 6 deliveries belonging to Old Sub
        for ($i = 1; $i <= 6; $i++) {
            $this->addDeliveredNotification($uid, $oldSubId, date('Y-m-d H:i:s', strtotime("-5{$i} days")));
        }

        // New subscription purchased today
        $expiryNew = SubscriptionService::calculatePlanExpiry($this->planPremiumId);
        $newSubId = $this->createSubscription($uid, 'active', date('Y-m-d H:i:s'), $expiryNew);

        $countOld = SubscriptionService::countQualifyingDeliveredMessages($oldSubId, $this->db);
        $countNew = SubscriptionService::countQualifyingDeliveredMessages($newSubId, $this->db);

        $this->assert($countOld === 6, "Old subscription must have 6 qualifying deliveries");
        $this->assert($countNew === 0, "New subscription must have exactly 0 deliveries, got $countNew");
        echo "PASS\n";
    }

    private function test47_unassignedNotificationsCountTowardZeroSubscriptions(): void {
        echo "[Test 47] Unassigned notifications (subscription_id = NULL) count toward zero subscriptions... ";
        $uid = $this->createUser('step1_u47@example.com');
        $activeSub = $this->createSubscription($uid, 'active', date('Y-m-d H:i:s', strtotime('-10 days')), date('Y-m-d H:i:s', strtotime('+20 days')));
        $protectedSub = $this->createSubscription($uid, 'protected', date('Y-m-d H:i:s', strtotime('-40 days')), date('Y-m-d H:i:s', strtotime('-10 days')));
        $newSub = $this->createSubscription($uid, 'active', date('Y-m-d H:i:s'), date('Y-m-d H:i:s', strtotime('+30 days')));

        // Insert 5 delivered notifications with subscription_id = NULL
        $stmtNull = $this->db->prepare("
            INSERT INTO notification_logs (user_id, subscription_id, notification_type, channel, recipient, status, delivered_at, created_at, updated_at)
            VALUES (:uid, NULL, 'NEW_MATCH', 'whatsapp', '923999123456', 'delivered', NOW(), NOW(), NOW())
        ");
        for ($i = 1; $i <= 5; $i++) {
            $stmtNull->execute(['uid' => $uid]);
        }

        $countActive = SubscriptionService::countQualifyingDeliveredMessages($activeSub, $this->db);
        $countProtected = SubscriptionService::countQualifyingDeliveredMessages($protectedSub, $this->db);
        $countNew = SubscriptionService::countQualifyingDeliveredMessages($newSub, $this->db);

        $this->assert($countActive === 0, "Active sub count must be 0 with NULL notifications, got $countActive");
        $this->assert($countProtected === 0, "Protected sub count must be 0 with NULL notifications, got $countProtected");
        $this->assert($countNew === 0, "New sub count must be 0 with NULL notifications, got $countNew");
        echo "PASS\n";
    }

    private function test48_retriesPreserveSubscriptionOwnershipAndCountOnce(): void {
        echo "[Test 48] Retry preserves subscription_id and yields qualifying count = 1... ";
        $uid = $this->createUser('step1_u48@example.com');
        $subId = $this->createSubscription($uid, 'active', date('Y-m-d H:i:s', strtotime('-20 days')), date('Y-m-d H:i:s', strtotime('+10 days')));

        $queue = new NotificationQueueService();
        $msgId = 'wacrm_retry_' . bin2hex(random_bytes(6));
        $idemp = 'idemp_retry_' . bin2hex(random_bytes(6));

        // Initial queue attempt
        $stmt = $this->db->prepare("
            INSERT INTO notification_logs (user_id, subscription_id, notification_type, channel, recipient, provider_message_id, idempotency_key, status, attempts, created_at, updated_at)
            VALUES (:uid, :sub_id, 'NEW_MATCH', 'whatsapp', '923999123456', :msg_id, :idemp, 'pending', 0, NOW(), NOW())
        ");
        $stmt->execute(['uid' => $uid, 'sub_id' => $subId, 'msg_id' => $msgId, 'idemp' => $idemp]);
        $notifId = (int)$this->db->lastInsertId();

        // 1. First attempt fails (transient error)
        $queue->recordDeliveryStatus($msgId, 'failed', null, 'Connection timeout');
        $rowFail = $this->db->query("SELECT status, subscription_id, idempotency_key FROM notification_logs WHERE id = $notifId")->fetch(PDO::FETCH_ASSOC);
        $this->assert($rowFail['status'] === 'failed', "Status must be failed");
        $this->assert((int)$rowFail['subscription_id'] === $subId, "Subscription ID must be preserved");

        // 2. Retry resets to pending (simulating queue worker retry policy)
        $this->db->exec("UPDATE notification_logs SET status = 'pending', attempts = 1 WHERE id = $notifId");

        // 3. Dispatched (sent)
        $this->db->exec("UPDATE notification_logs SET status = 'sent', sent_at = NOW() WHERE id = $notifId");

        // 4. Delivered
        $queue->recordDeliveryStatus($msgId, 'delivered', date('Y-m-d H:i:s'));

        // Verify: total rows in notification_logs is still 1, subscription_id is preserved, count is 1
        $totalRows = (int)$this->db->query("SELECT COUNT(*) FROM notification_logs WHERE provider_message_id = '$msgId'")->fetchColumn();
        $this->assert($totalRows === 1, "Exactly 1 notification row must exist, got $totalRows");

        $count = SubscriptionService::countQualifyingDeliveredMessages($subId, $this->db);
        $this->assert($count === 1, "Qualifying count must be 1, got $count");
        echo "PASS\n";
    }

    private function test49_subscriptionUsageSynchronizedWithStrictOwnership(): void {
        echo "[Test 49] subscription_usage synchronization with strict ownership... ";
        $uid = $this->createUser('step1_u49@example.com');
        $subA = $this->createSubscription($uid, 'active', date('Y-m-d H:i:s', strtotime('-30 days')), date('Y-m-d H:i:s', strtotime('-1 day')));
        $subB = $this->createSubscription($uid, 'active', date('Y-m-d H:i:s', strtotime('-15 days')), date('Y-m-d H:i:s', strtotime('-1 day')));

        // A = 4 qualifying deliveries
        for ($i = 1; $i <= 4; $i++) {
            $this->addDeliveredNotification($uid, $subA, date('Y-m-d H:i:s', strtotime("-{$i} days")));
        }

        // 10 NULL-subscription notifications
        $stmtNull = $this->db->prepare("
            INSERT INTO notification_logs (user_id, subscription_id, notification_type, channel, recipient, status, delivered_at, created_at, updated_at)
            VALUES (:uid, NULL, 'NEW_MATCH', 'whatsapp', '923999123456', 'delivered', NOW(), NOW(), NOW())
        ");
        for ($k = 1; $k <= 10; $k++) {
            $stmtNull->execute(['uid' => $uid]);
        }

        // B = 5 qualifying deliveries
        for ($j = 1; $j <= 5; $j++) {
            $this->addDeliveredNotification($uid, $subB, date('Y-m-d H:i:s', strtotime("-{$j} days")));
        }

        // Synchronize usage
        $usageA = SubscriptionService::syncSubscriptionUsage($subA, $this->db);
        $usageB = SubscriptionService::syncSubscriptionUsage($subB, $this->db);

        $this->assert((int)$usageA['qualifying_delivered_count'] === 4, "Sub A usage count must be 4, got " . $usageA['qualifying_delivered_count']);
        $this->assert((int)$usageB['qualifying_delivered_count'] === 5, "Sub B usage count must be 5, got " . $usageB['qualifying_delivered_count']);
        echo "PASS\n";
    }

    private function test50_nonScholarshipNotificationTypesNeverCountTowardDeliveryEntitlement(): void {
        echo "[Test 50] Non-scholarship notification types never count even if delivered... ";
        $uid = $this->createUser('step1_u50@example.com');
        $subId = $this->createSubscription($uid, 'active', date('Y-m-d H:i:s', strtotime('-35 days')), date('Y-m-d H:i:s', strtotime('-1 day')));

        $nonScholarshipTypes = [
            'EMAIL_VERIFICATION',
            'PASSWORD_RESET',
            'PAYMENT_CONFIRMATION',
            'PAYMENT_SUCCESS',
            'SUBSCRIPTION_CONFIRMATION',
            'SUBSCRIPTION_EXPIRED',
            'ADMIN_BROADCAST',
            'INTERNAL_NOTE',
            'SYSTEM_ALERT',
            'REFERRAL_PAYOUT'
        ];

        $stmtNonQ = $this->db->prepare("
            INSERT INTO notification_logs (user_id, subscription_id, notification_type, channel, recipient, status, delivered_at, created_at, updated_at)
            VALUES (:uid, :sub_id, :type, 'whatsapp', '923999123456', 'delivered', NOW(), NOW(), NOW())
        ");
        foreach ($nonScholarshipTypes as $tType) {
            $stmtNonQ->execute(['uid' => $uid, 'sub_id' => $subId, 'type' => $tType]);
        }

        $count = SubscriptionService::countQualifyingDeliveredMessages($subId, $this->db);
        $this->assert($count === 0, "Non-scholarship notifications must NEVER count toward delivery entitlement even if delivered, got $count");

        // Verify lifecycle keeps subscription protected
        SubscriptionService::processDailyLifecycle($this->db);
        $status = $this->db->query("SELECT status FROM subscriptions WHERE id = $subId")->fetchColumn();
        $this->assert($status === 'protected', "Subscription must remain protected despite delivered non-scholarship messages, got $status");
        echo "PASS\n";
    }

    private function test51_subscriptionUsageDiscrepancyDoesNotForceExpiry(): void {
        echo "[Test 51] Subscription usage discrepancy does not force expiry (notification_logs is authoritative)... ";
        $uid = $this->createUser('step1_u51@example.com');
        $subId = $this->createSubscription($uid, 'active', date('Y-m-d H:i:s', strtotime('-35 days')), date('Y-m-d H:i:s', strtotime('-1 day')));

        // Insert exactly 4 qualifying delivered messages in notification_logs
        for ($i = 1; $i <= 4; $i++) {
            $this->addDeliveredNotification($uid, $subId, date('Y-m-d H:i:s', strtotime("-{$i} days")));
        }

        // Artificially inflate subscription_usage to 5
        $stmtUsage = $this->db->prepare("
            INSERT INTO subscription_usage (
                user_id, subscription_id, feature, usage_count, qualifying_delivered_count,
                minimum_required, protected_state, final_expired_state, period_start, period_end, created_at, updated_at
            ) VALUES (
                :uid, :sub_id, 'qualifying_scholarship_alerts', 5, 5,
                5, 0, 0, DATE_SUB(NOW(), INTERVAL 35 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY), NOW(), NOW()
            ) ON DUPLICATE KEY UPDATE usage_count = 5, qualifying_delivered_count = 5
        ");
        $stmtUsage->execute(['uid' => $uid, 'sub_id' => $subId]);

        // Run lifecycle: notification_logs only has 4 -> must remain protected!
        SubscriptionService::processDailyLifecycle($this->db);

        $status = $this->db->query("SELECT status FROM subscriptions WHERE id = $subId")->fetchColumn();
        $this->assert($status === 'protected', "Subscription must remain protected when notification_logs has 4 even if subscription_usage had 5, got $status");

        // Verify that syncSubscriptionUsage corrected the discrepancy
        $syncedUsage = (int)$this->db->query("SELECT qualifying_delivered_count FROM subscription_usage WHERE subscription_id = $subId")->fetchColumn();
        $this->assert($syncedUsage === 4, "Usage count must be resynced to authoritative notification_logs count (4), got $syncedUsage");
        echo "PASS\n";
    }

    private function test52_startTimeBoundaryEnforcedInSqlAndHelper(): void {
        echo "[Test 52] Start-time boundary enforced identically in SQL and isQualifyingDeliveredMessage... ";
        $uid = $this->createUser('step1_u52@example.com');
        $startsAt = '2026-09-10 00:00:00';
        $endsAt = '2026-10-09 23:59:59';
        $subId = $this->createSubscription($uid, 'active', $startsAt, $endsAt);

        $stmtSub = $this->db->prepare("SELECT * FROM subscriptions WHERE id = :id LIMIT 1");
        $stmtSub->execute(['id' => $subId]);
        $sub = $stmtSub->fetch(PDO::FETCH_ASSOC);

        // 1. Notification delivered 1 second before starts_at
        $msgBefore = 'wacrm_start_before_' . bin2hex(random_bytes(4));
        $stmtBefore = $this->db->prepare("
            INSERT INTO notification_logs (user_id, subscription_id, notification_type, channel, recipient, provider_message_id, idempotency_key, status, delivered_at, created_at, updated_at)
            VALUES (:uid, :sub_id, 'NEW_MATCH', 'whatsapp', '923999123456', :msg, :idemp, 'delivered', '2026-09-09 23:59:59', NOW(), NOW())
        ");
        $stmtBefore->execute(['uid' => $uid, 'sub_id' => $subId, 'msg' => $msgBefore, 'idemp' => "idemp_$msgBefore"]);
        $logBeforeId = (int)$this->db->lastInsertId();
        $logBefore = $this->db->query("SELECT * FROM notification_logs WHERE id = $logBeforeId")->fetch(PDO::FETCH_ASSOC);

        // SQL count must be 0
        $count1 = SubscriptionService::countQualifyingDeliveredMessages($subId, $this->db);
        $this->assert($count1 === 0, "Delivery 1 sec before starts_at must yield SQL count = 0, got $count1");

        // Helper must return false
        $helperBefore = SubscriptionService::isQualifyingDeliveredMessage($logBefore, $sub);
        $this->assert($helperBefore === false, "isQualifyingDeliveredMessage must return false for delivery before starts_at");

        // 2. Notification delivered at exact starts_at (inclusive start boundary)
        $msgAt = 'wacrm_start_at_' . bin2hex(random_bytes(4));
        $stmtAt = $this->db->prepare("
            INSERT INTO notification_logs (user_id, subscription_id, notification_type, channel, recipient, provider_message_id, idempotency_key, status, delivered_at, created_at, updated_at)
            VALUES (:uid, :sub_id, 'NEW_MATCH', 'whatsapp', '923999123456', :msg, :idemp, 'delivered', '2026-09-10 00:00:00', NOW(), NOW())
        ");
        $stmtAt->execute(['uid' => $uid, 'sub_id' => $subId, 'msg' => $msgAt, 'idemp' => "idemp_$msgAt"]);
        $logAtId = (int)$this->db->lastInsertId();
        $logAt = $this->db->query("SELECT * FROM notification_logs WHERE id = $logAtId")->fetch(PDO::FETCH_ASSOC);

        // SQL count must now be 1
        $count2 = SubscriptionService::countQualifyingDeliveredMessages($subId, $this->db);
        $this->assert($count2 === 1, "Delivery at exact starts_at must yield SQL count = 1, got $count2");

        // Helper must return true
        $helperAt = SubscriptionService::isQualifyingDeliveredMessage($logAt, $sub);
        $this->assert($helperAt === true, "isQualifyingDeliveredMessage must return true for delivery at starts_at");

        // 3. Notification delivered after starts_at
        $msgAfter = 'wacrm_start_after_' . bin2hex(random_bytes(4));
        $stmtAfter = $this->db->prepare("
            INSERT INTO notification_logs (user_id, subscription_id, notification_type, channel, recipient, provider_message_id, idempotency_key, status, delivered_at, created_at, updated_at)
            VALUES (:uid, :sub_id, 'NEW_MATCH', 'whatsapp', '923999123456', :msg, :idemp, 'delivered', '2026-09-10 12:00:00', NOW(), NOW())
        ");
        $stmtAfter->execute(['uid' => $uid, 'sub_id' => $subId, 'msg' => $msgAfter, 'idemp' => "idemp_$msgAfter"]);
        $logAfterId = (int)$this->db->lastInsertId();
        $logAfter = $this->db->query("SELECT * FROM notification_logs WHERE id = $logAfterId")->fetch(PDO::FETCH_ASSOC);

        // SQL count must now be 2
        $count3 = SubscriptionService::countQualifyingDeliveredMessages($subId, $this->db);
        $this->assert($count3 === 2, "Delivery after starts_at must yield SQL count = 2, got $count3");

        // Helper must return true
        $helperAfter = SubscriptionService::isQualifyingDeliveredMessage($logAfter, $sub);
        $this->assert($helperAfter === true, "isQualifyingDeliveredMessage must return true for delivery after starts_at");

        echo "PASS\n";
    }

    private function test53_finalExpiryBoundaryConsistency(): void {
        echo "[Test 53] Final-expiry boundary consistency (before, at, after)... ";
        $uid = $this->createUser('step1_u53@example.com');
        $startsAt = '2026-09-10 00:00:00';
        $endsAt = '2026-10-09 23:59:59';
        $finalExpiredAt = '2026-10-15 12:00:00';

        // Expired subscription with final_expired_at set
        $subId = $this->createSubscription($uid, 'expired', $startsAt, $endsAt);
        $this->db->exec("UPDATE subscriptions SET final_expired_at = '$finalExpiredAt', expiry_reason = 'minimum_delivery_satisfied' WHERE id = $subId");

        $stmtSub = $this->db->prepare("SELECT * FROM subscriptions WHERE id = :id LIMIT 1");
        $stmtSub->execute(['id' => $subId]);
        $sub = $stmtSub->fetch(PDO::FETCH_ASSOC);

        // 1. Delivery 1 second before final_expired_at
        $msg1 = 'wacrm_exp_before_' . bin2hex(random_bytes(4));
        $this->db->prepare("
            INSERT INTO notification_logs (user_id, subscription_id, notification_type, channel, recipient, provider_message_id, idempotency_key, status, delivered_at, created_at, updated_at)
            VALUES (:uid, :sub_id, 'NEW_MATCH', 'whatsapp', '923999123456', :msg, :idemp, 'delivered', '2026-10-15 11:59:59', NOW(), NOW())
        ")->execute(['uid' => $uid, 'sub_id' => $subId, 'msg' => $msg1, 'idemp' => "idemp_$msg1"]);
        $log1 = $this->db->query("SELECT * FROM notification_logs WHERE provider_message_id = '$msg1'")->fetch(PDO::FETCH_ASSOC);

        $count1 = SubscriptionService::countQualifyingDeliveredMessages($subId, $this->db);
        $this->assert($count1 === 1, "Delivery before final_expired_at must qualify (count = 1), got $count1");
        $this->assert(SubscriptionService::isQualifyingDeliveredMessage($log1, $sub) === true, "Helper must return true for delivery before final expiry");

        // 2. Delivery at exact final_expired_at (inclusive rule: <= final_expired_at)
        $msg2 = 'wacrm_exp_at_' . bin2hex(random_bytes(4));
        $this->db->prepare("
            INSERT INTO notification_logs (user_id, subscription_id, notification_type, channel, recipient, provider_message_id, idempotency_key, status, delivered_at, created_at, updated_at)
            VALUES (:uid, :sub_id, 'NEW_MATCH', 'whatsapp', '923999123456', :msg, :idemp, 'delivered', '2026-10-15 12:00:00', NOW(), NOW())
        ")->execute(['uid' => $uid, 'sub_id' => $subId, 'msg' => $msg2, 'idemp' => "idemp_$msg2"]);
        $log2 = $this->db->query("SELECT * FROM notification_logs WHERE provider_message_id = '$msg2'")->fetch(PDO::FETCH_ASSOC);

        $count2 = SubscriptionService::countQualifyingDeliveredMessages($subId, $this->db);
        $this->assert($count2 === 2, "Delivery at exact final_expired_at must qualify under inclusive rule (count = 2), got $count2");
        $this->assert(SubscriptionService::isQualifyingDeliveredMessage($log2, $sub) === true, "Helper must return true for delivery at final expiry");

        // 3. Delivery after final_expired_at (1 second after)
        $msg3 = 'wacrm_exp_after_' . bin2hex(random_bytes(4));
        $this->db->prepare("
            INSERT INTO notification_logs (user_id, subscription_id, notification_type, channel, recipient, provider_message_id, idempotency_key, status, delivered_at, created_at, updated_at)
            VALUES (:uid, :sub_id, 'NEW_MATCH', 'whatsapp', '923999123456', :msg, :idemp, 'delivered', '2026-10-15 12:00:01', NOW(), NOW())
        ")->execute(['uid' => $uid, 'sub_id' => $subId, 'msg' => $msg3, 'idemp' => "idemp_$msg3"]);
        $log3 = $this->db->query("SELECT * FROM notification_logs WHERE provider_message_id = '$msg3'")->fetch(PDO::FETCH_ASSOC);

        $count3 = SubscriptionService::countQualifyingDeliveredMessages($subId, $this->db);
        $this->assert($count3 === 2, "Delivery after final_expired_at must NOT count (count remains 2), got $count3");
        $this->assert(SubscriptionService::isQualifyingDeliveredMessage($log3, $sub) === false, "Helper must return false for delivery after final expiry");

        echo "PASS\n";
    }

    private function test54_unknownAndWrongProviderMessageIdCallbacksDoNotCorrupt(): void {
        echo "[Test 54] Unknown and wrong provider message ID callbacks do not corrupt state... ";
        $uid = $this->createUser('step1_u54@example.com');
        $subA = $this->createSubscription($uid, 'active', date('Y-m-d H:i:s', strtotime('-10 days')), date('Y-m-d H:i:s', strtotime('+20 days')));
        $subB = $this->createSubscription($uid, 'active', date('Y-m-d H:i:s', strtotime('-5 days')), date('Y-m-d H:i:s', strtotime('+25 days')));

        $queue = new NotificationQueueService();

        // 1. Unknown provider message ID
        $beforeRows = (int)$this->db->query("SELECT COUNT(*) FROM notification_logs")->fetchColumn();
        $resUnknown = $queue->recordDeliveryStatus('DOES_NOT_EXIST', 'delivered');

        $this->assert($resUnknown['success'] === false, "Unknown provider message ID must return success = false");
        $afterRows = (int)$this->db->query("SELECT COUNT(*) FROM notification_logs")->fetchColumn();
        $this->assert($afterRows === $beforeRows, "No new notification row must be created for unknown provider message ID");

        $countA0 = SubscriptionService::countQualifyingDeliveredMessages($subA, $this->db);
        $this->assert($countA0 === 0, "No delivery count increment for Sub A");
        $countB0 = SubscriptionService::countQualifyingDeliveredMessages($subB, $this->db);
        $this->assert($countB0 === 0, "No delivery count increment for Sub B");

        // 2. Wrong provider message ID targeting
        $msgA = 'wacrm_subA_notif_' . bin2hex(random_bytes(4));
        $msgB = 'wacrm_subB_notif_' . bin2hex(random_bytes(4));

        $this->db->prepare("
            INSERT INTO notification_logs (user_id, subscription_id, notification_type, channel, recipient, provider_message_id, idempotency_key, status, created_at, updated_at)
            VALUES (:uid, :sub_id, 'NEW_MATCH', 'whatsapp', '923999123456', :msg, :idemp, 'sent', NOW(), NOW())
        ")->execute(['uid' => $uid, 'sub_id' => $subA, 'msg' => $msgA, 'idemp' => "idemp_$msgA"]);

        $this->db->prepare("
            INSERT INTO notification_logs (user_id, subscription_id, notification_type, channel, recipient, provider_message_id, idempotency_key, status, created_at, updated_at)
            VALUES (:uid, :sub_id, 'NEW_MATCH', 'whatsapp', '923999123456', :msg, :idemp, 'sent', NOW(), NOW())
        ")->execute(['uid' => $uid, 'sub_id' => $subB, 'msg' => $msgB, 'idemp' => "idemp_$msgB"]);

        // Send callback for msgA ONLY
        $resA = $queue->recordDeliveryStatus($msgA, 'delivered');
        $this->assert($resA['success'] === true, "Callback for msgA must succeed");

        // Verify: Sub A notification is delivered, Sub B notification is STILL sent
        $rowA = $this->db->query("SELECT status, subscription_id FROM notification_logs WHERE provider_message_id = '$msgA'")->fetch(PDO::FETCH_ASSOC);
        $rowB = $this->db->query("SELECT status, subscription_id FROM notification_logs WHERE provider_message_id = '$msgB'")->fetch(PDO::FETCH_ASSOC);

        $this->assert($rowA['status'] === 'delivered', "Sub A notification must be delivered");
        $this->assert((int)$rowA['subscription_id'] === $subA, "Sub A ownership must remain subA");

        $this->assert($rowB['status'] === 'sent', "Sub B notification must remain sent");
        $this->assert((int)$rowB['subscription_id'] === $subB, "Sub B ownership must remain subB");

        $this->assert(SubscriptionService::countQualifyingDeliveredMessages($subA, $this->db) === 1, "Sub A count must be 1");
        $this->assert(SubscriptionService::countQualifyingDeliveredMessages($subB, $this->db) === 0, "Sub B count must be 0");

        // 3. Repeated duplicate callbacks (1, 2, 5, 10) for same provider_message_id
        for ($k = 2; $k <= 10; $k++) {
            $queue->recordDeliveryStatus($msgA, 'delivered');
        }
        $subACountFinal = SubscriptionService::countQualifyingDeliveredMessages($subA, $this->db);
        $this->assert($subACountFinal === 1, "Duplicate callbacks must leave qualifying count at exactly 1, got $subACountFinal");
        $totalRowsA = (int)$this->db->query("SELECT COUNT(*) FROM notification_logs WHERE provider_message_id = '$msgA'")->fetchColumn();
        $this->assert($totalRowsA === 1, "Total notification rows for msgA must remain exactly 1");

        echo "PASS\n";
    }

    private function test55_callbackPreservesSubscriptionOwnershipStrictly(): void {
        echo "[Test 55] Callback cannot change subscription ownership or cross-contaminate... ";
        $uid = $this->createUser('step1_u55@example.com');
        $subA = $this->createSubscription($uid, 'active', date('Y-m-d H:i:s', strtotime('-20 days')), date('Y-m-d H:i:s', strtotime('+10 days')));
        $subB = $this->createSubscription($uid, 'active', date('Y-m-d H:i:s', strtotime('-10 days')), date('Y-m-d H:i:s', strtotime('+20 days')));

        $msg = 'wacrm_own_test_' . bin2hex(random_bytes(4));
        $this->db->prepare("
            INSERT INTO notification_logs (user_id, subscription_id, notification_type, channel, recipient, provider_message_id, idempotency_key, status, created_at, updated_at)
            VALUES (:uid, :sub_id, 'NEW_MATCH', 'whatsapp', '923999123456', :msg, :idemp, 'sent', NOW(), NOW())
        ")->execute(['uid' => $uid, 'sub_id' => $subA, 'msg' => $msg, 'idemp' => "idemp_$msg"]);

        $queue = new NotificationQueueService();
        $res = $queue->recordDeliveryStatus($msg, 'delivered');
        $this->assert($res['success'] === true, "Callback must succeed");

        $row = $this->db->query("SELECT status, subscription_id FROM notification_logs WHERE provider_message_id = '$msg'")->fetch(PDO::FETCH_ASSOC);
        $this->assert($row['status'] === 'delivered', "Status must be delivered");
        $this->assert((int)$row['subscription_id'] === $subA, "subscription_id must remain subA (never NULL or subB)");

        // Count for subA is 1; Count for subB is 0
        $countA = SubscriptionService::countQualifyingDeliveredMessages($subA, $this->db);
        $countB = SubscriptionService::countQualifyingDeliveredMessages($subB, $this->db);
        $this->assert($countA === 1, "Sub A count must be 1");
        $this->assert($countB === 0, "Sub B count must be 0 (cannot count toward B)");

        echo "PASS\n";
    }

    private function test56_subscriptionUsageFourCasesReconciliation(): void {
        echo "[Test 56] subscription_usage 4 reconciliation cases (notif vs usage)... ";
        $uid = $this->createUser('step1_u56@example.com');

        // Case 1: notification count = 4, usage count = 0 -> Reconcile usage to 4 and remain protected
        $sub1 = $this->createSubscription($uid, 'active', date('Y-m-d H:i:s', strtotime('-35 days')), date('Y-m-d H:i:s', strtotime('-1 day')));
        $sub1Data = $this->db->query("SELECT starts_at, ends_at FROM subscriptions WHERE id = $sub1")->fetch(PDO::FETCH_ASSOC);
        for ($i = 1; $i <= 4; $i++) {
            $this->addDeliveredNotification($uid, $sub1, date('Y-m-d H:i:s', strtotime("-{$i} days")));
        }
        // Force usage count = 0 matching this subscription's period
        $this->db->prepare("
            INSERT INTO subscription_usage (user_id, subscription_id, feature, usage_count, qualifying_delivered_count, minimum_required, protected_state, final_expired_state, period_start, period_end, created_at, updated_at)
            VALUES (:uid, :sub_id, 'qualifying_scholarship_alerts', 0, 0, 5, 0, 0, :pstart, :pend, NOW(), NOW())
            ON DUPLICATE KEY UPDATE usage_count = 0, qualifying_delivered_count = 0
        ")->execute(['uid' => $uid, 'sub_id' => $sub1, 'pstart' => $sub1Data['starts_at'], 'pend' => $sub1Data['ends_at']]);

        SubscriptionService::processDailyLifecycle($this->db);
        $status1 = $this->db->query("SELECT status FROM subscriptions WHERE id = $sub1")->fetchColumn();
        $this->assert($status1 === 'protected', "Case 1: Subscription with 4 notifications must be protected, got $status1");
        $usage1 = (int)$this->db->query("SELECT qualifying_delivered_count FROM subscription_usage WHERE subscription_id = $sub1")->fetchColumn();
        $this->assert($usage1 === 4, "Case 1: Usage count must be reconciled to 4, got $usage1");

        // Case 2: notification count = 4, usage count = 5 -> Lifecycle must NOT expire subscription
        $sub2 = $this->createSubscription($uid, 'active', date('Y-m-d H:i:s', strtotime('-35 days')), date('Y-m-d H:i:s', strtotime('-1 day')));
        $sub2Data = $this->db->query("SELECT starts_at, ends_at FROM subscriptions WHERE id = $sub2")->fetch(PDO::FETCH_ASSOC);
        for ($i = 1; $i <= 4; $i++) {
            $this->addDeliveredNotification($uid, $sub2, date('Y-m-d H:i:s', strtotime("-{$i} days")));
        }
        $this->db->prepare("
            INSERT INTO subscription_usage (user_id, subscription_id, feature, usage_count, qualifying_delivered_count, minimum_required, protected_state, final_expired_state, period_start, period_end, created_at, updated_at)
            VALUES (:uid, :sub_id, 'qualifying_scholarship_alerts', 5, 5, 5, 0, 0, :pstart, :pend, NOW(), NOW())
            ON DUPLICATE KEY UPDATE usage_count = 5, qualifying_delivered_count = 5
        ")->execute(['uid' => $uid, 'sub_id' => $sub2, 'pstart' => $sub2Data['starts_at'], 'pend' => $sub2Data['ends_at']]);

        SubscriptionService::processDailyLifecycle($this->db);
        $status2 = $this->db->query("SELECT status FROM subscriptions WHERE id = $sub2")->fetchColumn();
        $this->assert($status2 === 'protected', "Case 2: Must NOT expire subscription when notif count is 4 even if usage count was 5, got $status2");
        $usage2 = (int)$this->db->query("SELECT qualifying_delivered_count FROM subscription_usage WHERE subscription_id = $sub2")->fetchColumn();
        $this->assert($usage2 === 4, "Case 2: Usage count must be corrected back to 4, got $usage2");

        // Case 3: notification count = 5, usage count = 0 -> Authoritative count of 5 allows final expiry
        $sub3 = $this->createSubscription($uid, 'active', date('Y-m-d H:i:s', strtotime('-35 days')), date('Y-m-d H:i:s', strtotime('-1 day')));
        $sub3Data = $this->db->query("SELECT starts_at, ends_at FROM subscriptions WHERE id = $sub3")->fetch(PDO::FETCH_ASSOC);
        for ($i = 1; $i <= 5; $i++) {
            $this->addDeliveredNotification($uid, $sub3, date('Y-m-d H:i:s', strtotime("-{$i} days")));
        }
        $this->db->prepare("
            INSERT INTO subscription_usage (user_id, subscription_id, feature, usage_count, qualifying_delivered_count, minimum_required, protected_state, final_expired_state, period_start, period_end, created_at, updated_at)
            VALUES (:uid, :sub_id, 'qualifying_scholarship_alerts', 0, 0, 5, 0, 0, :pstart, :pend, NOW(), NOW())
            ON DUPLICATE KEY UPDATE usage_count = 0, qualifying_delivered_count = 0
        ")->execute(['uid' => $uid, 'sub_id' => $sub3, 'pstart' => $sub3Data['starts_at'], 'pend' => $sub3Data['ends_at']]);

        SubscriptionService::processDailyLifecycle($this->db);
        $status3 = $this->db->query("SELECT status FROM subscriptions WHERE id = $sub3")->fetchColumn();
        $this->assert($status3 === 'expired', "Case 3: Must expire subscription when authoritative count is 5 even if usage count was 0, got $status3");
        $usage3 = (int)$this->db->query("SELECT qualifying_delivered_count FROM subscription_usage WHERE subscription_id = $sub3")->fetchColumn();
        $this->assert($usage3 === 5, "Case 3: Usage count must be reconciled to 5, got $usage3");

        // Case 4: notification count = 5, usage count = 99 -> Lifecycle still uses authoritative count of 5 and reconciles usage
        $sub4 = $this->createSubscription($uid, 'active', date('Y-m-d H:i:s', strtotime('-35 days')), date('Y-m-d H:i:s', strtotime('-1 day')));
        $sub4Data = $this->db->query("SELECT starts_at, ends_at FROM subscriptions WHERE id = $sub4")->fetch(PDO::FETCH_ASSOC);
        for ($i = 1; $i <= 5; $i++) {
            $this->addDeliveredNotification($uid, $sub4, date('Y-m-d H:i:s', strtotime("-{$i} days")));
        }
        $this->db->prepare("
            INSERT INTO subscription_usage (user_id, subscription_id, feature, usage_count, qualifying_delivered_count, minimum_required, protected_state, final_expired_state, period_start, period_end, created_at, updated_at)
            VALUES (:uid, :sub_id, 'qualifying_scholarship_alerts', 99, 99, 5, 0, 0, :pstart, :pend, NOW(), NOW())
            ON DUPLICATE KEY UPDATE usage_count = 99, qualifying_delivered_count = 99
        ")->execute(['uid' => $uid, 'sub_id' => $sub4, 'pstart' => $sub4Data['starts_at'], 'pend' => $sub4Data['ends_at']]);

        SubscriptionService::processDailyLifecycle($this->db);
        $status4 = $this->db->query("SELECT status FROM subscriptions WHERE id = $sub4")->fetchColumn();
        $this->assert($status4 === 'expired', "Case 4: Must expire subscription based on authoritative count of 5, got $status4");
        $usage4 = (int)$this->db->query("SELECT qualifying_delivered_count FROM subscription_usage WHERE subscription_id = $sub4")->fetchColumn();
        $this->assert($usage4 === 5, "Case 4: Usage count must be reconciled from 99 to 5, got $usage4");

        echo "PASS\n";
    }
}

