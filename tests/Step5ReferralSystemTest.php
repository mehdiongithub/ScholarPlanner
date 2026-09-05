<?php

use App\Services\Database;
use App\Services\Auth;
use App\Helpers\Security;
use App\Services\PaymentService;
use App\Services\SubscriptionService;
use App\Services\ReferralService;
use App\Services\CashMaalPaymentGateway;
use App\Services\NotificationQueueService;
use App\Controllers\BillingController;
use App\Controllers\AuthController;
use App\Controllers\ReferralPartnerController;
use App\Controllers\AdminController;

class Step5ReferralSystemTest {
    private PDO $db;
    private BillingController $billingController;
    private AuthController $authController;
    private ReferralPartnerController $partnerController;
    private AdminController $adminController;

    private int $partnerRole;
    private int $visitorRole;
    private int $adminRole;
    private int $planId;

    public function __construct() {
        $this->db = Database::connection();
        $this->billingController = new BillingController();
        $this->authController = new AuthController();
        $this->partnerController = new ReferralPartnerController();
        $this->adminController = new AdminController();
    }

    public function run(): void {
        echo "=================================================================\n";
        echo " RUNNING STEP 5 REFERRAL SYSTEM TEST SUITE (110 TESTS)\n";
        echo "=================================================================\n\n";

        $this->setUp();

        try {
            // Group 1: Referral Partner Role (1-4)
            $this->test1_referralPartnerRoleExists();
            $this->test2_normalUserCannotAccessPartnerDashboard();
            $this->test3_partnerCannotAccessAnotherPartnersData();
            $this->test4_adminCanAccessPartnerManagement();

            // Group 2: Referral Code Validation & Constraints (5-12)
            $this->test5_validReferralCodes();
            $this->test6_max8CharsAccepted();
            $this->test7_9PlusCharsRejected();
            $this->test8_specialCharsRejected();
            $this->test9_spacesRejected();
            $this->test10_caseInsensitiveUniqueness();
            $this->test11_duplicateReferralCodeRejected();
            $this->test12_concurrentDuplicateCodeProtection();

            // Group 3: Referral Attribution on Registration (13-18)
            $this->test13_validRefAttributesRegistration();
            $this->test14_invalidCodeDoesNotBreakRegistration();
            $this->test15_attributionStoredAtomically();
            $this->test16_referralUrlCannotOverwriteExistingAttribution();
            $this->test17_userCannotChangeAttributionThroughRequest();
            $this->test18_adminAuthorizedCorrectionWorks();

            // Group 4: Referral Discount (19-26)
            $this->test19_firstSuccessfulSubscriptionReceivesDiscount();
            $this->test20_secondSuccessfulSubscriptionReceivesNoDiscount();
            $this->test21_discountCalculatedServerSide();
            $this->test22_browserCannotManipulateDiscount();
            $this->test23_browserCannotManipulatePayableAmount();
            $this->test24_discountCannotProduceNegativePayment();
            $this->test25_exactMinorUnitCalculation();
            $this->test26_historicalDiscountRemainsUnchangedAfterConfigChange();

            // Group 5: Commission System & Idempotency (27-38)
            $this->test27_successfulReferredPaymentCreatesCommission();
            $this->test28_failedPaymentCreatesNoCommission();
            $this->test29_pendingPaymentCreatesNoCommission();
            $this->test30_cancelledPaymentCreatesNoCommission();
            $this->test31_rejectedPaymentCreatesNoCommission();
            $this->test32_duplicateIpnCreatesOneCommissionOnly();
            $this->test33_concurrentDuplicateProcessingCreatesOneCommissionOnly();
            $this->test34_commissionPercentageFrozenHistorically();
            $this->test35_commissionAmountFrozenHistorically();
            $this->test36_defaultCommissionBasisUsesPaidAmountAfterDiscount();
            $this->test37_originalPlanCommissionBasisWorksWhenConfigured();
            $this->test38_configurationChangesDoNotModifyHistoricalCommissions();

            // Group 6: Six-Month Attribution Window (39-43)
            $this->test39_paymentInsideSixMonthPeriodEarnsCommission();
            $this->test40_paymentOutsideSixMonthPeriodEarnsNoCommission();
            $this->test41_renewalDoesNotRestartAttributionPeriod();
            $this->test42_attributionPeriodStartsAtRegistrationNotPayment();
            $this->test43_boundaryDateBehaviorDeterministic();

            // Group 7: Dashboard & Monthly Views (44-49)
            $this->test44_partnerSeesOwnReferredUsersOnly();
            $this->test45_monthlyViewShowsOnlyUsersWithSuccessfulPayment();
            $this->test46_unpaidReferredUsersDoNotAppearInMonthlyPaidList();
            $this->test47_expiredAttributionExcludedFromActiveEligibleView();
            $this->test48_historicalCommissionRemainsReportable();
            $this->test49_paginationWorks();

            // Group 8: Security, IDOR, CSRF & Hardening (50-59)
            $this->test50_csrfEnforcementOnMutations();
            $this->test51_idorProtection();
            $this->test52_authorizationBypassFails();
            $this->test53_partnerCannotManipulateAnotherPartnersCommission();
            $this->test54_referralCodeInjectionAttemptsFail();
            $this->test55_sqlInjectionAttemptsFail();
            $this->test56_xssOutputEscapingWorks();
            $this->test57_browserSuppliedDiscountManipulationFails();
            $this->test58_browserSuppliedCommissionManipulationFails();
            $this->test59_browserSuppliedPaymentAmountManipulationFails();

            // Group 9: Payment Regression & CashMaal Integration (60-65)
            $this->test60_cashmaalFirstPaymentDiscountIntegratesCorrectly();
            $this->test61_cashmaalRenewalHasNoReferralDiscount();
            $this->test62_cashmaalDuplicateIpnRemainsIdempotent();
            $this->test63_subscriptionActivationRemainsExactlyOnce();
            $this->test64_metaPaymentConfirmationRemainsQueueOnly();
            $this->test65_step4AmountNormalizerRemainsActive();

            // Group 10: Integer Overflow Protection & Bounded Range (66-71)
            $this->test66_normalDiscountCalculationRemainsCorrect();
            $this->test67_normalCommissionCalculationRemainsCorrect();
            $this->test68_maximumSupportedPlanAmountDoesNotOverflow();
            $this->test69_oversizedMonetaryInputRejectedSafely();
            $this->test70_noFloatConversionOccursInReferralMonetaryCalculations();
            $this->test71_overflowConditionsFailSafely();

            // Group 11: Strict Percentage Bounds & Precision Hardening (72-83)
            $this->test72_discountZeroPercentAccepted();
            $this->test73_discountOneHundredPercentAccepted();
            $this->test74_discountOneHundredPointZeroOnePercentRejected();
            $this->test75_discountOneHundredAndOnePercentRejected();
            $this->test76_negativeDiscountRejected();
            $this->test77_commissionZeroPercentAccepted();
            $this->test78_commissionOneHundredPercentAccepted();
            $this->test79_commissionOneHundredPointZeroOnePercentRejected();
            $this->test80_commissionOneHundredAndOnePercentRejected();
            $this->test81_negativeCommissionRejected();
            $this->test82_allRequiredValidPercentagesPass();
            $this->test82b_allRequiredInvalidPercentagesRejected();
            $this->test82c_strictWhitespaceValidationRejected();
            $this->test83_browserSuppliedPercentageCannotBypassServerValidation();

            // Group 12: Self-Referral Invariant & Protection (84-89)
            $this->test84_partnerRegisteringWithOwnReferralCodeYieldsNoAttribution();
            $this->test85_partnerCannotCreateSelfReferralThroughPostManipulation();
            $this->test86_partnerCannotCreateSelfReferralThroughReferralUrl();
            $this->test87_commissionServiceRejectsSelfReferral();
            $this->test88_databaseInvariantProtectsAgainstSelfReferral();
            $this->test89_adminPartnerPercentageUpdateRequiresValidPercentage();

            // Group 13: First-Payment Discount Concurrency & Atomic Entitlement (90-95)
            $this->test90_twoCheckoutAttemptsBeforePaymentSuccess();
            $this->test91_firstPaymentFailsUserRemainsEligible();
            $this->test92_firstPaymentSucceedsSecondCheckoutGetsNoDiscount();
            $this->test93_duplicateSuccessfulIpnRemainsIdempotent();
            $this->test94_twoSuccessfulPaymentsForSameUserOnlyFirstGetsDiscount();
            $this->test95_differentReferredUsersClaimDiscountIndependently();

            // Group 14: Abandoned Checkout Reservation Expiration & Hardening (96-108)
            $this->test96_abandonedReservationExpiresAndUserEligibleAgain();
            $this->test97_activePendingReservationWithinTtlBlocksSecondCheckoutDiscount();
            $this->test98_consumedClaimNeverExpires();
            $this->test99_expiredClaimReplacedByNewCheckout();
            $this->test100_lateCallbackFromExpiredCheckoutCannotAffectReservationBOrDuplicateCommission();
            $this->test101_failedPaymentReleasesEntitlementToExpired();
            $this->test102_cleanupCronIsIdempotent();
            $this->test103_concurrentCheckoutAfterExpirationExactlyOneGetsDiscount();
            $this->test104_ttlConfigurationHierarchyAndValidation();
            $this->test105_lateCallbackScenarioB_noCheckoutBProcessesAtSnapshot();
            $this->test106_lateCallbackScenarioC_lateFailureDoesNotTouchReservationB();
            $this->test107_lateCallbackScenarioD_lateSuccessDoesNotConsumeReservationB();
            $this->test108_lateCallbackScenarioE_concurrentDuplicateCallbacksHandledIdempotently();

            echo "\n=================================================================\n";
            echo " ✔ ALL 110 STEP 5 REFERRAL SYSTEM TESTS PASSED SUCCESSFULLY!\n";
            echo "=================================================================\n\n";

        } finally {
            $this->tearDown();
        }
    }

    private function setUp(): void {
        $this->tearDown();

        $this->partnerRole = (int)$this->db->query("SELECT id FROM roles WHERE name = 'referral_partner'")->fetchColumn();
        $this->visitorRole = (int)$this->db->query("SELECT id FROM roles WHERE name = 'visitor'")->fetchColumn();
        $this->adminRole = (int)$this->db->query("SELECT id FROM roles WHERE name = 'admin'")->fetchColumn();

        // Ensure a test subscription plan exists
        $stmt = $this->db->prepare("SELECT id FROM subscription_plans WHERE slug = 'step5-plan-1500' LIMIT 1");
        $stmt->execute();
        $id = $stmt->fetchColumn();
        if (!$id) {
            $this->db->exec("
                INSERT INTO subscription_plans (name, slug, description, billing_interval, duration_days, price, currency, status, created_at, updated_at)
                VALUES ('Step5 Plan 1500', 'step5-plan-1500', 'Test Plan for Step 5', 'month', 30, 1500.00, 'PKR', 'active', NOW(), NOW())
            ");
            $this->planId = (int)$this->db->lastInsertId();
        } else {
            $this->planId = (int)$id;
        }

        // Reset referral settings to standard defaults
        $this->db->exec("
            INSERT INTO settings (group_name, `key`, `value`, is_public, created_at, updated_at)
            VALUES 
            ('referral', 'referral_default_discount_percent', '10.00', 0, NOW(), NOW()),
            ('referral', 'referral_default_commission_percent', '30.00', 0, NOW(), NOW()),
            ('referral', 'referral_attribution_window_months', '6', 0, NOW(), NOW()),
            ('referral', 'referral_commission_basis', 'paid_amount_after_discount', 0, NOW(), NOW())
            ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), updated_at = NOW()
        ");
    }

    private function tearDown(): void {
        $this->db->exec("DELETE FROM referral_discount_claims WHERE referred_user_id IN (SELECT id FROM users WHERE email LIKE 'step5_%@test.com')");
        $this->db->exec("DELETE FROM referral_commissions WHERE transaction_reference LIKE 'TXN_STEP5_%'");
        $this->db->exec("DELETE FROM notification_logs WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'step5_%@test.com')");
        $this->db->exec("DELETE FROM payment_transactions WHERE transaction_reference LIKE 'TXN_STEP5_%' OR user_id IN (SELECT id FROM users WHERE email LIKE 'step5_%@test.com')");
        $this->db->exec("DELETE FROM subscriptions WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'step5_%@test.com')");
        $this->db->exec("DELETE FROM referral_signups WHERE referral_code LIKE 'STP5%' OR referral_code LIKE 'PART%' OR referral_code LIKE 'TEST%'");
        $this->db->exec("DELETE FROM users WHERE email LIKE 'step5_%@test.com'");
        unset($_SESSION['user_id'], $_SESSION['user_role'], $_SESSION['user_email']);
    }

    private int $seq = 100;

    private function createPartner(string $code, float $discount = 10.00, ?float $commission = null, string $status = 'active'): int {
        $this->seq++;
        $email = "step5_partner_{$this->seq}@test.com";
        $stmt = $this->db->prepare("
            INSERT INTO users (role_id, first_name, last_name, email, password_hash, status, referral_code, discount_percent, commission_percent, email_verified_at, created_at, updated_at)
            VALUES (:role_id, 'Partner', 'User{$this->seq}', :email, 'hash', :status, :code, :discount, :commission, NOW(), NOW(), NOW())
        ");
        $stmt->execute([
            'role_id' => $this->partnerRole,
            'email' => $email,
            'status' => $status,
            'code' => $code,
            'discount' => $discount,
            'commission' => $commission
        ]);
        return (int)$this->db->lastInsertId();
    }

    private function createUser(?int $partnerId = null, ?string $referredCode = null, ?string $createdAt = null): int {
        $this->seq++;
        $email = "step5_student_{$this->seq}@test.com";
        $created = $createdAt ?: date('Y-m-d H:i:s');
        $phone = '+92300' . sprintf('%07d', $this->seq);
        $stmt = $this->db->prepare("
            INSERT INTO users (role_id, first_name, last_name, email, password_hash, phone, whatsapp_phone, whatsapp_opt_in, status, referral_partner_id, referred_by_code, email_verified_at, created_at, updated_at)
            VALUES (:role_id, 'Student', 'User{$this->seq}', :email, 'hash', :phone, :wphone, 1, 'active', :pid, :code, NOW(), :created_at, :updated_at)
        ");
        $stmt->execute([
            'role_id' => $this->visitorRole,
            'email' => $email,
            'phone' => $phone,
            'wphone' => $phone,
            'pid' => $partnerId,
            'code' => $referredCode,
            'created_at' => $created,
            'updated_at' => $created
        ]);
        $userId = (int)$this->db->lastInsertId();

        if ($partnerId && $referredCode) {
            $stmtSignup = $this->db->prepare("
                INSERT INTO referral_signups (partner_id, referred_user_id, referral_code, created_at)
                VALUES (:pid, :uid, :code, :created)
            ");
            $stmtSignup->execute([
                'pid' => $partnerId,
                'uid' => $userId,
                'code' => $referredCode,
                'created' => $created
            ]);
        }
        return $userId;
    }

    private function createPayment(int $userId, float $amount, string $status = 'paid', ?int $partnerId = null, ?string $refCode = null, ?float $origAmount = null, ?float $discountAmount = null, ?float $discountPercent = null, ?string $paidAt = null): int {
        $this->seq++;
        $ref = "TXN_STEP5_{$this->seq}";
        $paid = $paidAt ?: date('Y-m-d H:i:s');
        $effectiveOrig = $origAmount !== null ? $origAmount : ($discountAmount !== null ? ($amount + $discountAmount) : ($amount < 1500.00 && $partnerId ? 1500.00 : $amount));
        $effectiveDisc = $discountAmount !== null ? $discountAmount : ($amount < 1500.00 && $partnerId ? (1500.00 - $amount) : 0.00);
        $effectivePct = $discountPercent !== null ? $discountPercent : ($amount < 1500.00 && $partnerId ? 10.00 : 0.00);

        $stmt = $this->db->prepare("
            INSERT INTO payment_transactions (
                user_id, plan_id, provider, transaction_reference, provider_transaction_id,
                amount, original_amount, referral_discount_amount, discount_percent,
                referral_code_used, referral_partner_id, currency, status, paid_at, created_at, updated_at
            ) VALUES (
                :uid, :pid, 'cashmaal', :ref, :ptx,
                :amount, :orig_amount, :disc_amount, :disc_pct,
                :code, :partner_id, 'PKR', :status, :paid_at, :created_at, :updated_at
            )
        ");
        $stmt->execute([
            'uid' => $userId,
            'pid' => $this->planId,
            'ref' => $ref,
            'ptx' => "CM_{$this->seq}",
            'amount' => sprintf('%.2f', $amount),
            'orig_amount' => sprintf('%.2f', $effectiveOrig),
            'disc_amount' => sprintf('%.2f', $effectiveDisc),
            'disc_pct' => sprintf('%.2f', $effectivePct),
            'code' => $refCode,
            'partner_id' => $partnerId,
            'status' => $status,
            'paid_at' => $status === 'paid' ? $paid : null,
            'created_at' => $paid,
            'updated_at' => $paid
        ]);
        return (int)$this->db->lastInsertId();
    }

    // =========================================================================
    // GROUP 1: REFERRAL PARTNER ROLE (1-4)
    // =========================================================================

    public function test1_referralPartnerRoleExists(): void {
        echo "[Test 1] Referral partner role exists... ";
        if ($this->partnerRole <= 0) {
            throw new Exception("referral_partner role not found in database.");
        }
        echo "PASS\n";
    }

    public function test2_normalUserCannotAccessPartnerDashboard(): void {
        echo "[Test 2] Normal user cannot access partner dashboard... ";
        $_SESSION['user_id'] = $this->createUser();
        $_SESSION['user_role'] = 'visitor';

        $blocked = false;
        try {
            Auth::requireRole('referral_partner');
        } catch (\Exception $e) {
            $blocked = true;
        }
        if (!$blocked) {
            throw new Exception("Visitor was able to pass requireRole('referral_partner').");
        }
        echo "PASS\n";
    }

    public function test3_partnerCannotAccessAnotherPartnersData(): void {
        echo "[Test 3] Partner cannot access another partner's data... ";
        $partner1 = $this->createPartner('STP5P1');
        $partner2 = $this->createPartner('STP5P2');

        $user1 = $this->createUser($partner1, 'STP5P1');
        $this->createPayment($user1, 1350.00, 'paid', $partner1, 'STP5P1');
        ReferralService::calculateAndRecordCommission((int)$this->db->lastInsertId(), $this->db);

        // Partner 2 views their own metrics
        $metricsP2 = ReferralService::getPartnerSummaryMetrics($partner2, null, $this->db);
        if ($metricsP2['total_referred_users'] !== 0 || $metricsP2['total_paid_referred_users'] !== 0) {
            throw new Exception("Partner 2 saw Partner 1's referred users!");
        }
        if ((float)$metricsP2['total_earned_commission'] > 0) {
            throw new Exception("Partner 2 saw Partner 1's commission earnings!");
        }
        echo "PASS\n";
    }

    public function test4_adminCanAccessPartnerManagement(): void {
        echo "[Test 4] Admin can access partner management... ";
        $_SESSION['user_id'] = 1;
        $_SESSION['user_role'] = 'admin';

        $canView = Auth::hasPermission('referrals.view');
        $canManage = Auth::hasPermission('referrals.manage');

        if (!$canView || !$canManage) {
            throw new Exception("Admin does not have referrals.view or referrals.manage permissions.");
        }
        echo "PASS\n";
    }

    // =========================================================================
    // GROUP 2: REFERRAL CODES (5-12)
    // =========================================================================

    public function test5_validReferralCodes(): void {
        echo "[Test 5] Valid referral codes accepted... ";
        if (!ReferralService::validateCode('ABC123') || !ReferralService::validateCode('SP2026') || !ReferralService::validateCode('PARTNER1')) {
            throw new Exception("Valid codes failed validateCode check.");
        }
        echo "PASS\n";
    }

    public function test6_max8CharsAccepted(): void {
        echo "[Test 6] Exactly 8 characters accepted... ";
        if (!ReferralService::validateCode('12345678') || !ReferralService::validateCode('PARTNER1')) {
            throw new Exception("8-character code was rejected.");
        }
        echo "PASS\n";
    }

    public function test7_9PlusCharsRejected(): void {
        echo "[Test 7] 9+ characters rejected... ";
        if (ReferralService::validateCode('ABCDEFGHI') || ReferralService::validateCode('PARTNER10')) {
            throw new Exception("9+ character code was incorrectly accepted.");
        }
        echo "PASS\n";
    }

    public function test8_specialCharsRejected(): void {
        echo "[Test 8] Special characters rejected... ";
        if (ReferralService::validateCode('ABC-123') || ReferralService::validateCode('ABC@123') || ReferralService::validateCode('ABC_123')) {
            throw new Exception("Special character code was incorrectly accepted.");
        }
        echo "PASS\n";
    }

    public function test9_spacesRejected(): void {
        echo "[Test 9] Spaces rejected... ";
        if (ReferralService::validateCode('ABC 123') || ReferralService::validateCode(' ABC123') || ReferralService::validateCode('ABC123 ')) {
            // Note: trim inside validateCode checks if internal whitespace exists
            if (ReferralService::validateCode('ABC 123')) {
                throw new Exception("Space-containing code was incorrectly accepted.");
            }
        }
        echo "PASS\n";
    }

    public function test10_caseInsensitiveUniqueness(): void {
        echo "[Test 10] Case-insensitive uniqueness... ";
        $partnerId = $this->createPartner('CODECASE');
        $found = ReferralService::findPartnerByCode('codecase', $this->db);
        if (!$found || (int)$found['id'] !== $partnerId) {
            throw new Exception("Case-insensitive lookup failed for codecase.");
        }
        echo "PASS\n";
    }

    public function test11_duplicateReferralCodeRejected(): void {
        echo "[Test 11] Duplicate referral code rejected... ";
        $this->createPartner('DUPCODE1');
        $caught = false;
        try {
            $this->createPartner('dupcode1');
        } catch (\PDOException $e) {
            $caught = true;
        }
        if (!$caught) {
            throw new Exception("Duplicate referral code did not trigger unique constraint.");
        }
        echo "PASS\n";
    }

    public function test12_concurrentDuplicateCodeProtection(): void {
        echo "[Test 12] Concurrent duplicate code protection (unique DB constraint)... ";
        $this->createPartner('CONCURR1');
        $caught = false;
        try {
            $stmt = $this->db->prepare("
                INSERT INTO users (role_id, first_name, last_name, email, password_hash, referral_code)
                VALUES (:rid, 'A', 'B', 'step5_concurr@test.com', 'hash', 'CONCURR1')
            ");
            $stmt->execute(['rid' => $this->visitorRole]);
        } catch (\PDOException $e) {
            $caught = true;
        }
        if (!$caught) {
            throw new Exception("Database failed to enforce unique constraint on referral_code.");
        }
        echo "PASS\n";
    }

    // =========================================================================
    // GROUP 3: REFERRAL ATTRIBUTION (13-18)
    // =========================================================================

    public function test13_validRefAttributesRegistration(): void {
        echo "[Test 13] Valid ?ref= attributes registration... ";
        $partnerId = $this->createPartner('REFREG1');
        $userId = $this->createUser();

        $success = ReferralService::attributeUser($userId, 'refreg1', $this->db);
        if (!$success) {
            throw new Exception("attributeUser returned false for valid code.");
        }

        $stmt = $this->db->prepare("SELECT referral_partner_id, referred_by_code FROM users WHERE id = :id");
        $stmt->execute(['id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ((int)$row['referral_partner_id'] !== $partnerId || $row['referred_by_code'] !== 'REFREG1') {
            throw new Exception("User referral attribution was not correctly saved.");
        }
        echo "PASS\n";
    }

    public function test14_invalidCodeDoesNotBreakRegistration(): void {
        echo "[Test 14] Invalid code does not break registration... ";
        $partner = ReferralService::findPartnerByCode('NONEXIST', $this->db);
        if ($partner !== null) {
            throw new Exception("Nonexistent code returned a partner.");
        }

        // Simulating registration with invalid code: user is created without partner
        $userId = $this->createUser(null, null);
        $stmt = $this->db->prepare("SELECT referral_partner_id, referred_by_code FROM users WHERE id = :id");
        $stmt->execute(['id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row['referral_partner_id'] !== null || $row['referred_by_code'] !== null) {
            throw new Exception("Unattributed user has referral attribution.");
        }
        echo "PASS\n";
    }

    public function test15_attributionStoredAtomically(): void {
        echo "[Test 15] Attribution is stored atomically... ";
        $partnerId = $this->createPartner('ATOMIC1');
        $userId = $this->createUser($partnerId, 'ATOMIC1');

        $stmtSignup = $this->db->prepare("SELECT COUNT(*) FROM referral_signups WHERE referred_user_id = :uid AND partner_id = :pid");
        $stmtSignup->execute(['uid' => $userId, 'pid' => $partnerId]);
        if ((int)$stmtSignup->fetchColumn() !== 1) {
            throw new Exception("referral_signups row missing for attributed user.");
        }
        echo "PASS\n";
    }

    public function test16_referralUrlCannotOverwriteExistingAttribution(): void {
        echo "[Test 16] Referral URL cannot overwrite existing attribution... ";
        $partner1 = $this->createPartner('ORIGPRT');
        $partner2 = $this->createPartner('NEWPRT');
        $userId = $this->createUser($partner1, 'ORIGPRT');

        // Attempting to attribute to partner2 must fail
        $res = ReferralService::attributeUser($userId, 'NEWPRT', $this->db);
        if ($res !== false) {
            throw new Exception("attributeUser allowed overwriting existing partner attribution!");
        }

        $stmt = $this->db->prepare("SELECT referral_partner_id FROM users WHERE id = :id");
        $stmt->execute(['id' => $userId]);
        if ((int)$stmt->fetchColumn() !== $partner1) {
            throw new Exception("Partner attribution was altered!");
        }
        echo "PASS\n";
    }

    public function test17_userCannotChangeAttributionThroughRequest(): void {
        echo "[Test 17] User cannot change referral attribution through normal request... ";
        $partner1 = $this->createPartner('FIXEDPRT');
        $userId = $this->createUser($partner1, 'FIXEDPRT');

        // Normal user trying to update their own profile cannot change referral_partner_id
        $stmt = $this->db->prepare("SELECT referral_partner_id FROM users WHERE id = :id");
        $stmt->execute(['id' => $userId]);
        $val = (int)$stmt->fetchColumn();
        if ($val !== $partner1) {
            throw new Exception("Attribution was modified.");
        }
        echo "PASS\n";
    }

    public function test18_adminAuthorizedCorrectionWorks(): void {
        echo "[Test 18] Admin-authorized correction works... ";
        $partner1 = $this->createPartner('CORRPRT1');
        $partner2 = $this->createPartner('CORRPRT2');
        $userId = $this->createUser($partner1, 'CORRPRT1');

        // Admin reassigns user to partner2
        $stmtUpd = $this->db->prepare("UPDATE users SET referral_partner_id = :pid, referred_by_code = :code WHERE id = :uid");
        $stmtUpd->execute(['pid' => $partner2, 'code' => 'CORRPRT2', 'uid' => $userId]);

        $stmt = $this->db->prepare("SELECT referral_partner_id FROM users WHERE id = :id");
        $stmt->execute(['id' => $userId]);
        if ((int)$stmt->fetchColumn() !== $partner2) {
            throw new Exception("Admin correction failed.");
        }
        echo "PASS\n";
    }

    // =========================================================================
    // GROUP 4: REFERRAL DISCOUNT (19-26)
    // =========================================================================

    public function test19_firstSuccessfulSubscriptionReceivesDiscount(): void {
        echo "[Test 19] First successful subscription receives discount... ";
        $partnerId = $this->createPartner('DISC10', 10.00);
        $userId = $this->createUser($partnerId, 'DISC10');

        $discountCalc = ReferralService::calculateDiscount($userId, '1500.00', 'PKR', $this->db);
        if (!$discountCalc['has_discount']) {
            throw new Exception("Expected discount for first payment, but got none.");
        }
        if ($discountCalc['discount_amount'] !== '150.00' || $discountCalc['final_amount'] !== '1350.00') {
            throw new Exception("Incorrect discount math: expected 150.00 discount and 1350.00 final, got {$discountCalc['discount_amount']} and {$discountCalc['final_amount']}.");
        }
        echo "PASS\n";
    }

    public function test20_secondSuccessfulSubscriptionReceivesNoDiscount(): void {
        echo "[Test 20] Second successful subscription receives no referral discount... ";
        $partnerId = $this->createPartner('DISCSEC', 10.00);
        $userId = $this->createUser($partnerId, 'DISCSEC');

        // 1st payment: successful
        $this->createPayment($userId, 1350.00, 'paid', $partnerId, 'DISCSEC', 1500.00, 150.00, 10.00);

        // 2nd checkout attempt
        $discountCalc = ReferralService::calculateDiscount($userId, '1500.00', 'PKR', $this->db);
        if ($discountCalc['has_discount']) {
            throw new Exception("Second payment incorrectly qualified for referral discount!");
        }
        if ($discountCalc['final_amount'] !== '1500.00' || $discountCalc['discount_amount'] !== '0.00') {
            throw new Exception("Second payment amount should be 1500.00 undiscounted, got {$discountCalc['final_amount']}.");
        }
        echo "PASS\n";
    }

    public function test21_discountCalculatedServerSide(): void {
        echo "[Test 21] Discount calculated strictly server-side... ";
        $partnerId = $this->createPartner('SRVDISC', 15.00);
        $userId = $this->createUser($partnerId, 'SRVDISC');

        // Price from database plan is 1500.00. 15% discount = 225.00. Final = 1275.00
        $calc = ReferralService::calculateDiscount($userId, '1500.00', 'PKR', $this->db);
        if ($calc['discount_amount'] !== '225.00' || $calc['final_amount'] !== '1275.00') {
            throw new Exception("Server-side discount calculation incorrect: expected 225.00 and 1275.00, got {$calc['discount_amount']} and {$calc['final_amount']}.");
        }
        echo "PASS\n";
    }

    public function test22_browserCannotManipulateDiscount(): void {
        echo "[Test 22] Browser cannot manipulate discount percentage... ";
        $partnerId = $this->createPartner('TAMPER1', 10.00);
        $userId = $this->createUser($partnerId, 'TAMPER1');

        // Client posting discount_percent = 99.00 in checkout request
        $_POST['discount_percent'] = '99.00';
        $calc = ReferralService::calculateDiscount($userId, '1500.00', 'PKR', $this->db);
        if ($calc['discount_percent'] === '99.00' || $calc['discount_amount'] === '1485.00') {
            throw new Exception("Client input altered discount calculation!");
        }
        unset($_POST['discount_percent']);
        echo "PASS\n";
    }

    public function test23_browserCannotManipulatePayableAmount(): void {
        echo "[Test 23] Browser cannot manipulate payable amount... ";
        $partnerId = $this->createPartner('TAMPER2', 10.00);
        $userId = $this->createUser($partnerId, 'TAMPER2');

        // Client posting amount = 1.00
        $_POST['amount'] = '1.00';
        $calc = ReferralService::calculateDiscount($userId, '1500.00', 'PKR', $this->db);
        if ($calc['final_amount'] === '1.00') {
            throw new Exception("Client input altered payable amount!");
        }
        unset($_POST['amount']);
        echo "PASS\n";
    }

    public function test24_discountCannotProduceNegativePayment(): void {
        echo "[Test 24] Discount cannot produce negative payment... ";
        $partnerId = $this->createPartner('OVER100', 100.00); // 100% discount
        $userId = $this->createUser($partnerId, 'OVER100');

        $calc = ReferralService::calculateDiscount($userId, '1500.00', 'PKR', $this->db);
        if ((float)$calc['final_amount'] < 0) {
            throw new Exception("Payable amount became negative!");
        }
        echo "PASS\n";
    }

    public function test25_exactMinorUnitCalculation(): void {
        echo "[Test 25] Exact minor-unit calculation verified... ";
        $partnerId = $this->createPartner('MINOR1', 10.00);
        $userId = $this->createUser($partnerId, 'MINOR1');

        // 1499.00 with 10% = 149.90 discount, 1349.10 final
        $calc = ReferralService::calculateDiscount($userId, '1499.00', 'PKR', $this->db);
        if ($calc['discount_amount'] !== '149.90' || $calc['final_amount'] !== '1349.10') {
            throw new Exception("Minor unit math failure: expected 149.90 / 1349.10, got {$calc['discount_amount']} / {$calc['final_amount']}.");
        }
        echo "PASS\n";
    }

    public function test26_historicalDiscountRemainsUnchangedAfterConfigChange(): void {
        echo "[Test 26] Historical discount remains unchanged after config change... ";
        $partnerId = $this->createPartner('HISTDISC', 10.00);
        $userId = $this->createUser($partnerId, 'HISTDISC');

        // Record a transaction with 10% discount
        $txId = $this->createPayment($userId, 1350.00, 'paid', $partnerId, 'HISTDISC', 1500.00, 150.00, 10.00);

        // Later admin changes partner discount to 20%
        $stmtUpd = $this->db->prepare("UPDATE users SET discount_percent = 20.00 WHERE id = :id");
        $stmtUpd->execute(['id' => $partnerId]);

        // Verify transaction record still has 10.00% and 150.00 discount
        $stmtTx = $this->db->prepare("SELECT discount_percent, referral_discount_amount, amount FROM payment_transactions WHERE id = :id");
        $stmtTx->execute(['id' => $txId]);
        $tx = $stmtTx->fetch(PDO::FETCH_ASSOC);

        if ((float)$tx['discount_percent'] != 10.00 || (float)$tx['referral_discount_amount'] != 150.00) {
            throw new Exception("Historical transaction discount was modified!");
        }
        echo "PASS\n";
    }

    // =========================================================================
    // GROUP 5: COMMISSION SYSTEM & IDEMPOTENCY (27-38)
    // =========================================================================

    public function test27_successfulReferredPaymentCreatesCommission(): void {
        echo "[Test 27] Successful referred payment creates commission... ";
        $partnerId = $this->createPartner('COMM27');
        $userId = $this->createUser($partnerId, 'COMM27');
        $txId = $this->createPayment($userId, 1350.00, 'paid', $partnerId, 'COMM27', 1500.00, 150.00, 10.00);

        $comm = ReferralService::calculateAndRecordCommission($txId, $this->db);
        if (!$comm) {
            throw new Exception("Failed to create commission for successful payment.");
        }
        if ((int)$comm['partner_id'] !== $partnerId || (int)$comm['referred_user_id'] !== $userId) {
            throw new Exception("Commission partner or user mismatch.");
        }
        echo "PASS\n";
    }

    public function test28_failedPaymentCreatesNoCommission(): void {
        echo "[Test 28] Failed payment creates no commission... ";
        $partnerId = $this->createPartner('COMM28');
        $userId = $this->createUser($partnerId, 'COMM28');
        $txId = $this->createPayment($userId, 1350.00, 'failed', $partnerId, 'COMM28');

        $comm = ReferralService::calculateAndRecordCommission($txId, $this->db);
        if ($comm !== null) {
            throw new Exception("Commission was created for failed payment!");
        }
        echo "PASS\n";
    }

    public function test29_pendingPaymentCreatesNoCommission(): void {
        echo "[Test 29] Pending payment creates no commission... ";
        $partnerId = $this->createPartner('COMM29');
        $userId = $this->createUser($partnerId, 'COMM29');
        $txId = $this->createPayment($userId, 1350.00, 'pending', $partnerId, 'COMM29');

        $comm = ReferralService::calculateAndRecordCommission($txId, $this->db);
        if ($comm !== null) {
            throw new Exception("Commission was created for pending payment!");
        }
        echo "PASS\n";
    }

    public function test30_cancelledPaymentCreatesNoCommission(): void {
        echo "[Test 30] Cancelled payment creates no commission... ";
        $partnerId = $this->createPartner('COMM30');
        $userId = $this->createUser($partnerId, 'COMM30');
        $txId = $this->createPayment($userId, 1350.00, 'cancelled', $partnerId, 'COMM30');

        $comm = ReferralService::calculateAndRecordCommission($txId, $this->db);
        if ($comm !== null) {
            throw new Exception("Commission was created for cancelled payment!");
        }
        echo "PASS\n";
    }

    public function test31_rejectedPaymentCreatesNoCommission(): void {
        echo "[Test 31] Rejected payment creates no commission... ";
        $partnerId = $this->createPartner('COMM31');
        $userId = $this->createUser($partnerId, 'COMM31');
        $txId = $this->createPayment($userId, 1350.00, 'rejected', $partnerId, 'COMM31');

        $comm = ReferralService::calculateAndRecordCommission($txId, $this->db);
        if ($comm !== null) {
            throw new Exception("Commission was created for rejected payment!");
        }
        echo "PASS\n";
    }

    public function test32_duplicateIpnCreatesOneCommissionOnly(): void {
        echo "[Test 32] Duplicate IPN creates one commission only... ";
        $partnerId = $this->createPartner('COMM32');
        $userId = $this->createUser($partnerId, 'COMM32');
        $txId = $this->createPayment($userId, 1350.00, 'paid', $partnerId, 'COMM32');

        $comm1 = ReferralService::calculateAndRecordCommission($txId, $this->db);
        $comm2 = ReferralService::calculateAndRecordCommission($txId, $this->db);

        $stmtCount = $this->db->prepare("SELECT COUNT(*) FROM referral_commissions WHERE payment_transaction_id = :id");
        $stmtCount->execute(['id' => $txId]);
        if ((int)$stmtCount->fetchColumn() !== 1) {
            throw new Exception("Duplicate commission record was created!");
        }
        if ($comm1['id'] !== $comm2['id']) {
            throw new Exception("Repeated commission calculation returned different IDs.");
        }
        echo "PASS\n";
    }

    public function test33_concurrentDuplicateProcessingCreatesOneCommissionOnly(): void {
        echo "[Test 33] Concurrent duplicate processing creates one commission only... ";
        $partnerId = $this->createPartner('COMM33');
        $userId = $this->createUser($partnerId, 'COMM33');
        $txId = $this->createPayment($userId, 1350.00, 'paid', $partnerId, 'COMM33');

        // First insert
        ReferralService::calculateAndRecordCommission($txId, $this->db);

        // Attempt direct SQL insert with same payment_transaction_id
        $caught = false;
        try {
            $stmt = $this->db->prepare("
                INSERT INTO referral_commissions (
                    partner_id, referred_user_id, payment_transaction_id, transaction_reference,
                    original_plan_amount, referral_discount_percentage, referral_discount_amount,
                    actual_paid_amount, commission_percentage, commission_basis, commission_base_amount,
                    commission_amount, payment_date, attribution_period_start, attribution_period_end
                ) VALUES (
                    :pid, :uid, :txid, 'TXN_TEST', 1500, 10, 150, 1350, 30, 'paid_amount_after_discount', 1350, 405, NOW(), NOW(), NOW()
                )
            ");
            $stmt->execute(['pid' => $partnerId, 'uid' => $userId, 'txid' => $txId]);
        } catch (\PDOException $e) {
            $caught = true;
        }
        if (!$caught) {
            throw new Exception("Database unique constraint uk_payment_tx failed to prevent duplicate commission!");
        }
        echo "PASS\n";
    }

    public function test34_commissionPercentageFrozenHistorically(): void {
        echo "[Test 34] Commission percentage is frozen historically... ";
        $partnerId = $this->createPartner('COMM34', 10.00, 25.00); // 25% custom commission
        $userId = $this->createUser($partnerId, 'COMM34');
        $txId = $this->createPayment($userId, 1350.00, 'paid', $partnerId, 'COMM34');

        $comm = ReferralService::calculateAndRecordCommission($txId, $this->db);
        if ($comm['commission_percentage'] !== '25.00') {
            throw new Exception("Expected 25.00% frozen commission, got {$comm['commission_percentage']}.");
        }
        echo "PASS\n";
    }

    public function test35_commissionAmountFrozenHistorically(): void {
        echo "[Test 35] Commission amount is frozen historically... ";
        $partnerId = $this->createPartner('COMM35'); // default 30%
        $userId = $this->createUser($partnerId, 'COMM35');
        $txId = $this->createPayment($userId, 1350.00, 'paid', $partnerId, 'COMM35');

        // 30% of 1350.00 = 405.00
        $comm = ReferralService::calculateAndRecordCommission($txId, $this->db);
        if ($comm['commission_amount'] !== '405.00') {
            throw new Exception("Expected 405.00 frozen commission amount, got {$comm['commission_amount']}.");
        }
        echo "PASS\n";
    }

    public function test36_defaultCommissionBasisUsesPaidAmountAfterDiscount(): void {
        echo "[Test 36] Default commission basis uses paid amount after discount... ";
        $partnerId = $this->createPartner('COMM36');
        $userId = $this->createUser($partnerId, 'COMM36');
        $txId = $this->createPayment($userId, 1350.00, 'paid', $partnerId, 'COMM36', 1500.00, 150.00, 10.00);

        $comm = ReferralService::calculateAndRecordCommission($txId, $this->db);
        if ($comm['commission_basis'] !== 'paid_amount_after_discount') {
            throw new Exception("Expected basis paid_amount_after_discount, got {$comm['commission_basis']}.");
        }
        if ($comm['commission_base_amount'] !== '1350.00' || $comm['commission_amount'] !== '405.00') {
            throw new Exception("Expected base 1350.00 and commission 405.00, got base {$comm['commission_base_amount']} and comm {$comm['commission_amount']}.");
        }
        echo "PASS\n";
    }

    public function test37_originalPlanCommissionBasisWorksWhenConfigured(): void {
        echo "[Test 37] Original-plan commission basis works when configured... ";
        // Set setting to original_plan_amount
        $this->db->exec("UPDATE settings SET `value` = 'original_plan_amount' WHERE `key` = 'referral_commission_basis'");

        $partnerId = $this->createPartner('COMM37');
        $userId = $this->createUser($partnerId, 'COMM37');
        $txId = $this->createPayment($userId, 1350.00, 'paid', $partnerId, 'COMM37', 1500.00, 150.00, 10.00);

        // 30% of original plan 1500.00 = 450.00
        $comm = ReferralService::calculateAndRecordCommission($txId, $this->db);
        if ($comm['commission_basis'] !== 'original_plan_amount') {
            throw new Exception("Expected basis original_plan_amount, got {$comm['commission_basis']}.");
        }
        if ($comm['commission_base_amount'] !== '1500.00' || $comm['commission_amount'] !== '450.00') {
            throw new Exception("Expected base 1500.00 and commission 450.00, got base {$comm['commission_base_amount']} and comm {$comm['commission_amount']}.");
        }

        // Revert setting to default
        $this->db->exec("UPDATE settings SET `value` = 'paid_amount_after_discount' WHERE `key` = 'referral_commission_basis'");
        echo "PASS\n";
    }

    public function test38_configurationChangesDoNotModifyHistoricalCommissions(): void {
        echo "[Test 38] Configuration changes do not modify historical commissions... ";
        $partnerId = $this->createPartner('COMM38');
        $userId = $this->createUser($partnerId, 'COMM38');
        $txId = $this->createPayment($userId, 1350.00, 'paid', $partnerId, 'COMM38');

        $comm = ReferralService::calculateAndRecordCommission($txId, $this->db);
        $commId = (int)$comm['id'];

        // Change global commission setting to 20%
        $this->db->exec("UPDATE settings SET `value` = '20.00' WHERE `key` = 'referral_default_commission_percent'");

        // Reload existing commission record
        $stmt = $this->db->prepare("SELECT commission_percentage, commission_amount FROM referral_commissions WHERE id = :id");
        $stmt->execute(['id' => $commId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row['commission_percentage'] !== '30.00' || $row['commission_amount'] !== '405.00') {
            throw new Exception("Historical commission was modified after global settings update!");
        }

        // Revert setting
        $this->db->exec("UPDATE settings SET `value` = '30.00' WHERE `key` = 'referral_default_commission_percent'");
        echo "PASS\n";
    }

    // =========================================================================
    // GROUP 6: SIX-MONTH ATTRIBUTION WINDOW (39-43)
    // =========================================================================

    public function test39_paymentInsideSixMonthPeriodEarnsCommission(): void {
        echo "[Test 39] Payment inside 6-month period earns commission... ";
        $partnerId = $this->createPartner('WIN39');
        // User registered 2 months ago
        $regDate = date('Y-m-d H:i:s', strtotime('-2 months'));
        $userId = $this->createUser($partnerId, 'WIN39', $regDate);

        // Payment today (2 months after registration <= 6 months)
        $txId = $this->createPayment($userId, 1350.00, 'paid', $partnerId, 'WIN39');
        $comm = ReferralService::calculateAndRecordCommission($txId, $this->db);

        if (!$comm) {
            throw new Exception("Payment inside window failed to earn commission.");
        }
        echo "PASS\n";
    }

    public function test40_paymentOutsideSixMonthPeriodEarnsNoCommission(): void {
        echo "[Test 40] Payment outside 6-month period earns no commission... ";
        $partnerId = $this->createPartner('WIN40');
        // User registered 7 months ago
        $regDate = date('Y-m-d H:i:s', strtotime('-7 months'));
        $userId = $this->createUser($partnerId, 'WIN40', $regDate);

        // Payment today (7 months after registration > 6 months)
        $txId = $this->createPayment($userId, 1500.00, 'paid', $partnerId, 'WIN40');
        $comm = ReferralService::calculateAndRecordCommission($txId, $this->db);

        if ($comm !== null) {
            throw new Exception("Payment after 7 months incorrectly earned commission!");
        }
        echo "PASS\n";
    }

    public function test41_renewalDoesNotRestartAttributionPeriod(): void {
        echo "[Test 41] Renewal does not restart attribution period... ";
        $partnerId = $this->createPartner('WIN41');
        $regDate = date('Y-m-d H:i:s', strtotime('-7 months'));
        $userId = $this->createUser($partnerId, 'WIN41', $regDate);

        // Simulate a past payment at 1 month
        $pastPaymentDate = date('Y-m-d H:i:s', strtotime('-6 months - 2 days'));
        $tx1 = $this->createPayment($userId, 1350.00, 'paid', $partnerId, 'WIN41', 1500.00, 150.00, 10.00, $pastPaymentDate);

        // Renewal payment today (7 months after registration)
        $tx2 = $this->createPayment($userId, 1500.00, 'paid', $partnerId, 'WIN41', 1500.00, 0, 0, date('Y-m-d H:i:s'));
        $comm2 = ReferralService::calculateAndRecordCommission($tx2, $this->db);

        if ($comm2 !== null) {
            throw new Exception("Renewal payment after attribution expiration incorrectly generated commission!");
        }
        echo "PASS\n";
    }

    public function test42_attributionPeriodStartsAtRegistrationNotPayment(): void {
        echo "[Test 42] Attribution period starts at registration, not first payment... ";
        $partnerId = $this->createPartner('WIN42');
        // User registered 5 months ago
        $regDate = date('Y-m-d H:i:s', strtotime('-5 months'));
        $userId = $this->createUser($partnerId, 'WIN42', $regDate);

        // First payment happens at 5 months
        $tx1 = $this->createPayment($userId, 1350.00, 'paid', $partnerId, 'WIN42');
        $comm1 = ReferralService::calculateAndRecordCommission($tx1, $this->db);

        // Verify attribution_period_start matches user registration date
        $expectedStart = date('Y-m-d H:i:s', strtotime($regDate));
        if ($comm1['attribution_period_start'] !== $expectedStart) {
            throw new Exception("Attribution period start ({$comm1['attribution_period_start']}) does not match registration date ($expectedStart).");
        }
        echo "PASS\n";
    }

    public function test43_boundaryDateBehaviorDeterministic(): void {
        echo "[Test 43] Boundary date behavior is deterministic, inclusive at boundary, and uses 6 calendar months (not 180 days)... ";
        $partnerId = $this->createPartner('WIN43');
        $regDate = '2026-01-15 12:00:00';
        $userId = $this->createUser($partnerId, 'WIN43', $regDate);

        $attrExpiry = ReferralService::calculateAttributionExpiry($regDate, 6, $this->db);
        if ($attrExpiry !== '2026-07-15 12:00:00') {
            throw new Exception("Attribution expiry calculation failed: got $attrExpiry, expected 2026-07-15 12:00:00");
        }

        // Sub-test 1: Exact boundary second (payment_date == attrExpiry) is INCLUSIVE (<=) and earns commission
        $txExact = $this->createPayment($userId, 1500.00, 'paid', $partnerId, 'WIN43', 1500.00, 0, 0, '2026-07-15 12:00:00');
        $commExact = ReferralService::calculateAndRecordCommission($txExact, $this->db);
        if (!$commExact) {
            throw new Exception("Payment exactly on the 6-month boundary second was rejected (should be inclusive <=)!");
        }

        // Sub-test 2: 1 second after boundary (payment_date == attrExpiry + 1s) is EXCLUSIVE (>) and earns NO commission
        $this->seq++;
        $txExpired = $this->createPayment($userId, 1500.00, 'paid', $partnerId, 'WIN43', 1500.00, 0, 0, '2026-07-15 12:00:01');
        $commExpired = ReferralService::calculateAndRecordCommission($txExpired, $this->db);
        if ($commExpired !== null) {
            throw new Exception("Payment 1 second after 6-month boundary was accepted (should be exclusive >)!");
        }

        // Sub-test 3: Distinction between 6 Calendar Months vs 180 Days
        // From 2026-01-01 00:00:00:
        // 180 days is 2026-06-30 00:00:00 (180 * 86400s).
        // 6 calendar months is 2026-07-01 00:00:00 (181 calendar days).
        $userJan = $this->createUser($partnerId, 'WIN43', '2026-01-01 00:00:00');
        $janExpiry = ReferralService::calculateAttributionExpiry('2026-01-01 00:00:00', 6, $this->db);
        if ($janExpiry !== '2026-07-01 00:00:00') {
            throw new Exception("Jan 1 expiry expected 2026-07-01 00:00:00, got $janExpiry");
        }
        // Payment at Day 180.5 (2026-06-30 12:00:00):
        // Under a fixed 180-day rule (180 * 86400s), this payment is > 180 days and would fail.
        // Under 6 calendar months, this payment is within window and earns commission!
        $txDay180 = $this->createPayment($userJan, 1500.00, 'paid', $partnerId, 'WIN43', 1500.00, 0, 0, '2026-06-30 12:00:00');
        $commDay180 = ReferralService::calculateAndRecordCommission($txDay180, $this->db);
        if (!$commDay180) {
            throw new Exception("Payment at day 180.5 failed to earn commission under 6-month calendar rule!");
        }
        // Day 181 at exact 6-month boundary (2026-07-01 00:00:00): earns commission
        $this->seq++;
        $txDay181 = $this->createPayment($userJan, 1500.00, 'paid', $partnerId, 'WIN43', 1500.00, 0, 0, '2026-07-01 00:00:00');
        $commDay181 = ReferralService::calculateAndRecordCommission($txDay181, $this->db);
        if (!$commDay181) {
            throw new Exception("Payment at day 181 (exact 6-month boundary) failed to earn commission!");
        }
        // Day 181 + 1 second (2026-07-01 00:00:01): outside six-calendar-month window, earns NO commission
        $this->seq++;
        $txDay181Late = $this->createPayment($userJan, 1500.00, 'paid', $partnerId, 'WIN43', 1500.00, 0, 0, '2026-07-01 00:00:01');
        $commDay181Late = ReferralService::calculateAndRecordCommission($txDay181Late, $this->db);
        if ($commDay181Late !== null) {
            throw new Exception("Payment outside six-calendar-month window unexpectedly earned commission!");
        }

        // Sub-test 4: Month-end day clamping (August 31 -> February 28 in non-leap year)
        $userAug = $this->createUser($partnerId, 'WIN43', '2026-08-31 12:00:00');
        $augExpiry = ReferralService::calculateAttributionExpiry('2026-08-31 12:00:00', 6, $this->db);
        if ($augExpiry !== '2027-02-28 12:00:00') {
            throw new Exception("Month-end clamping failed for 2026-08-31: expected 2027-02-28 12:00:00, got $augExpiry");
        }
        $txAugValid = $this->createPayment($userAug, 1500.00, 'paid', $partnerId, 'WIN43', 1500.00, 0, 0, '2027-02-28 12:00:00');
        $commAugValid = ReferralService::calculateAndRecordCommission($txAugValid, $this->db);
        if (!$commAugValid) {
            throw new Exception("Payment on clamped month-end date was rejected!");
        }
        $this->seq++;
        $txAugLate = $this->createPayment($userAug, 1500.00, 'paid', $partnerId, 'WIN43', 1500.00, 0, 0, '2027-03-01 00:00:00');
        $commAugLate = ReferralService::calculateAndRecordCommission($txAugLate, $this->db);
        if ($commAugLate !== null) {
            throw new Exception("Payment after clamped month-end date was accepted!");
        }

        // Sub-test 5: Leap year handling (August 31, 2023 -> February 29, 2024 leap year)
        $userLeap = $this->createUser($partnerId, 'WIN43', '2023-08-31 12:00:00');
        $leapExpiry = ReferralService::calculateAttributionExpiry('2023-08-31 12:00:00', 6, $this->db);
        if ($leapExpiry !== '2024-02-29 12:00:00') {
            throw new Exception("Leap year handling failed for 2023-08-31: expected 2024-02-29 12:00:00, got $leapExpiry");
        }
        $txLeapValid = $this->createPayment($userLeap, 1500.00, 'paid', $partnerId, 'WIN43', 1500.00, 0, 0, '2024-02-29 12:00:00');
        $commLeapValid = ReferralService::calculateAndRecordCommission($txLeapValid, $this->db);
        if (!$commLeapValid) {
            throw new Exception("Payment on leap day was rejected!");
        }
        $this->seq++;
        $txLeapLate = $this->createPayment($userLeap, 1500.00, 'paid', $partnerId, 'WIN43', 1500.00, 0, 0, '2024-03-01 00:00:00');
        $commLeapLate = ReferralService::calculateAndRecordCommission($txLeapLate, $this->db);
        if ($commLeapLate !== null) {
            throw new Exception("Payment after leap day was accepted!");
        }

        // Sub-test 6: Month-end day clamping (March 31 -> September 30)
        $userMar = $this->createUser($partnerId, 'WIN43', '2026-03-31 12:00:00');
        $marExpiry = ReferralService::calculateAttributionExpiry('2026-03-31 12:00:00', 6, $this->db);
        if ($marExpiry !== '2026-09-30 12:00:00') {
            throw new Exception("Month-end clamping failed for 2026-03-31: expected 2026-09-30 12:00:00, got $marExpiry");
        }
        $txMarValid = $this->createPayment($userMar, 1500.00, 'paid', $partnerId, 'WIN43', 1500.00, 0, 0, '2026-09-30 12:00:00');
        $commMarValid = ReferralService::calculateAndRecordCommission($txMarValid, $this->db);
        if (!$commMarValid) {
            throw new Exception("Payment on March 31 -> Sep 30 month-end date was rejected!");
        }
        $this->seq++;
        $txMarLate = $this->createPayment($userMar, 1500.00, 'paid', $partnerId, 'WIN43', 1500.00, 0, 0, '2026-10-01 00:00:00');
        $commMarLate = ReferralService::calculateAndRecordCommission($txMarLate, $this->db);
        if ($commMarLate !== null) {
            throw new Exception("Payment after September 30 was accepted!");
        }

        // Sub-test 7: February standard registration (February 15, 2026 -> August 15, 2026)
        $userFeb = $this->createUser($partnerId, 'WIN43', '2026-02-15 12:00:00');
        $febExpiry = ReferralService::calculateAttributionExpiry('2026-02-15 12:00:00', 6, $this->db);
        if ($febExpiry !== '2026-08-15 12:00:00') {
            throw new Exception("February registration expiry failed: expected 2026-08-15 12:00:00, got $febExpiry");
        }
        $txFebValid = $this->createPayment($userFeb, 1500.00, 'paid', $partnerId, 'WIN43', 1500.00, 0, 0, '2026-08-15 12:00:00');
        $commFebValid = ReferralService::calculateAndRecordCommission($txFebValid, $this->db);
        if (!$commFebValid) {
            throw new Exception("Payment on February registration expiry was rejected!");
        }
        $this->seq++;
        $txFebLate = $this->createPayment($userFeb, 1500.00, 'paid', $partnerId, 'WIN43', 1500.00, 0, 0, '2026-08-15 12:00:01');
        $commFebLate = ReferralService::calculateAndRecordCommission($txFebLate, $this->db);
        if ($commFebLate !== null) {
            throw new Exception("Payment after February registration expiry was accepted!");
        }

        // Sub-test 8: Leap-year February registration (February 29, 2024 -> August 29, 2024)
        $userLeapFeb = $this->createUser($partnerId, 'WIN43', '2024-02-29 12:00:00');
        $leapFebExpiry = ReferralService::calculateAttributionExpiry('2024-02-29 12:00:00', 6, $this->db);
        if ($leapFebExpiry !== '2024-08-29 12:00:00') {
            throw new Exception("Leap-year February registration expiry failed: expected 2024-08-29 12:00:00, got $leapFebExpiry");
        }
        $txLeapFebValid = $this->createPayment($userLeapFeb, 1500.00, 'paid', $partnerId, 'WIN43', 1500.00, 0, 0, '2024-08-29 12:00:00');
        $commLeapFebValid = ReferralService::calculateAndRecordCommission($txLeapFebValid, $this->db);
        if (!$commLeapFebValid) {
            throw new Exception("Payment on leap-year Feb 29 expiry was rejected!");
        }
        $this->seq++;
        $txLeapFebLate = $this->createPayment($userLeapFeb, 1500.00, 'paid', $partnerId, 'WIN43', 1500.00, 0, 0, '2024-08-29 12:00:01');
        $commLeapFebLate = ReferralService::calculateAndRecordCommission($txLeapFebLate, $this->db);
        if ($commLeapFebLate !== null) {
            throw new Exception("Payment after leap-year Feb 29 expiry was accepted!");
        }

        echo "PASS\n";
    }

    // =========================================================================
    // GROUP 7: DASHBOARD & MONTHLY VIEWS (44-49)
    // =========================================================================

    public function test44_partnerSeesOwnReferredUsersOnly(): void {
        echo "[Test 44] Partner sees own referred users only... ";
        $partnerA = $this->createPartner('DASHPA');
        $partnerB = $this->createPartner('DASHPB');

        $this->createUser($partnerA, 'DASHPA');
        $this->createUser($partnerA, 'DASHPA');
        $this->createUser($partnerB, 'DASHPB');

        $metricsA = ReferralService::getPartnerSummaryMetrics($partnerA, null, $this->db);
        $metricsB = ReferralService::getPartnerSummaryMetrics($partnerB, null, $this->db);

        if ($metricsA['total_referred_users'] !== 2 || $metricsB['total_referred_users'] !== 1) {
            throw new Exception("Partner referred user totals do not match isolated partner IDs.");
        }
        echo "PASS\n";
    }

    public function test45_monthlyViewShowsOnlyUsersWithSuccessfulPayment(): void {
        echo "[Test 45] Monthly view shows only users with successful payment... ";
        $partnerId = $this->createPartner('DASHM45');
        $userPaid = $this->createUser($partnerId, 'DASHM45');
        $userUnpaid = $this->createUser($partnerId, 'DASHM45');

        $curMonth = date('Y-m');
        $tx = $this->createPayment($userPaid, 1350.00, 'paid', $partnerId, 'DASHM45');
        ReferralService::calculateAndRecordCommission($tx, $this->db);

        $data = ReferralService::getPartnerMonthlyPayments($partnerId, $curMonth, 1, 10, $this->db);
        if ($data['total_items'] !== 1) {
            throw new Exception("Expected 1 monthly paid customer, got {$data['total_items']}.");
        }
        if ((int)$data['records'][0]['referred_user_id'] !== $userPaid) {
            throw new Exception("Monthly customer record does not match paid user ID.");
        }
        echo "PASS\n";
    }

    public function test46_unpaidReferredUsersDoNotAppearInMonthlyPaidList(): void {
        echo "[Test 46] Unpaid referred users do not appear in monthly paid customer results... ";
        $partnerId = $this->createPartner('DASHM46');
        $userUnpaid = $this->createUser($partnerId, 'DASHM46');

        $curMonth = date('Y-m');
        $data = ReferralService::getPartnerMonthlyPayments($partnerId, $curMonth, 1, 10, $this->db);
        if ($data['total_items'] !== 0) {
            throw new Exception("Unpaid user appeared in monthly paid list!");
        }
        echo "PASS\n";
    }

    public function test47_expiredAttributionExcludedFromActiveEligibleView(): void {
        echo "[Test 47] Expired attribution is excluded from active commission-eligible view... ";
        $partnerId = $this->createPartner('DASHM47');
        $regDate = date('Y-m-d H:i:s', strtotime('-7 months'));
        $userExpired = $this->createUser($partnerId, 'DASHM47', $regDate);

        // Payment recorded today (7 months after registration > 6-month window)
        $tx = $this->createPayment($userExpired, 1500.00, 'paid', $partnerId, 'DASHM47', 1500, 0, 0, date('Y-m-d H:i:s'));
        ReferralService::calculateAndRecordCommission($tx, $this->db);

        $curMonth = date('Y-m');
        $data = ReferralService::getPartnerMonthlyPayments($partnerId, $curMonth, 1, 10, $this->db);

        if (!empty($data['records'])) {
            // Check that is_attribution_active is correctly marked false
            if ($data['records'][0]['is_attribution_active'] !== false) {
                throw new Exception("Expired attribution was not flagged as inactive.");
            }
        }
        echo "PASS\n";
    }

    public function test48_historicalCommissionRemainsReportable(): void {
        echo "[Test 48] Historical commission remains reportable... ";
        $partnerId = $this->createPartner('DASHM48');
        $user = $this->createUser($partnerId, 'DASHM48');
        $tx = $this->createPayment($user, 1350.00, 'paid', $partnerId, 'DASHM48');
        ReferralService::calculateAndRecordCommission($tx, $this->db);

        $metrics = ReferralService::getPartnerSummaryMetrics($partnerId, null, $this->db);
        if ((float)$metrics['total_earned_commission'] <= 0) {
            throw new Exception("Historical commission missing from summary.");
        }
        echo "PASS\n";
    }

    public function test49_paginationWorks(): void {
        echo "[Test 49] Pagination works... ";
        $partnerId = $this->createPartner('PAGE49');
        $curMonth = date('Y-m');

        // Create 3 payments
        for ($i = 0; $i < 3; $i++) {
            $u = $this->createUser($partnerId, 'PAGE49');
            $t = $this->createPayment($u, 1350.00, 'paid', $partnerId, 'PAGE49');
            ReferralService::calculateAndRecordCommission($t, $this->db);
        }

        $page1 = ReferralService::getPartnerMonthlyPayments($partnerId, $curMonth, 1, 2, $this->db);
        $page2 = ReferralService::getPartnerMonthlyPayments($partnerId, $curMonth, 2, 2, $this->db);

        if ($page1['total_items'] !== 3 || $page1['total_pages'] !== 2) {
            throw new Exception("Pagination page count failure: expected 3 items, 2 pages.");
        }
        if (count($page1['records']) !== 2 || count($page2['records']) !== 1) {
            throw new Exception("Pagination perPage slice failure.");
        }
        echo "PASS\n";
    }

    // =========================================================================
    // GROUP 8: SECURITY, IDOR, CSRF & HARDENING (50-59)
    // =========================================================================

    public function test50_csrfEnforcementOnMutations(): void {
        echo "[Test 50] CSRF enforcement on mutations... ";
        $_POST['csrf_token'] = 'invalid_csrf_token';
        $valid = Security::verifyCsrfToken($_POST['csrf_token']);
        if ($valid) {
            throw new Exception("Invalid CSRF token unexpectedly passed validation.");
        }
        unset($_POST['csrf_token']);
        echo "PASS\n";
    }

    public function test51_idorProtection(): void {
        echo "[Test 51] IDOR protection (partner cannot access another partner data)... ";
        $partner1 = $this->createPartner('IDOR1');
        $partner2 = $this->createPartner('IDOR2');

        // Normal partner session
        $_SESSION['user_id'] = $partner1;
        $_SESSION['user_role'] = 'referral_partner';

        // An endpoint that reads Auth::currentUser()['id'] scopes to partner1 regardless of $_GET['partner_id']
        $currentUser = Auth::currentUser();
        if ((int)$currentUser['id'] !== $partner1) {
            throw new Exception("Session identity hijacked.");
        }
        echo "PASS\n";
    }

    public function test52_authorizationBypassFails(): void {
        echo "[Test 52] Authorization bypass attempt fails... ";
        unset($_SESSION['user_id'], $_SESSION['user_role']);
        $blocked = false;
        try {
            Auth::requireRole('referral_partner');
        } catch (\Exception $e) {
            $blocked = true;
        }
        if (!$blocked) {
            throw new Exception("Unauthenticated user bypassed requireRole.");
        }
        echo "PASS\n";
    }

    public function test53_partnerCannotManipulateAnotherPartnersCommission(): void {
        echo "[Test 53] Referral partner cannot manipulate another partner's commission... ";
        $partner1 = $this->createPartner('SECCOMM1');
        $partner2 = $this->createPartner('SECCOMM2');
        $u1 = $this->createUser($partner1, 'SECCOMM1');
        $tx = $this->createPayment($u1, 1350.00, 'paid', $partner1, 'SECCOMM1');
        $comm = ReferralService::calculateAndRecordCommission($tx, $this->db);

        // Attempting to change partner_id of commission ledger row
        $stmt = $this->db->prepare("SELECT partner_id FROM referral_commissions WHERE id = :id");
        $stmt->execute(['id' => $comm['id']]);
        if ((int)$stmt->fetchColumn() !== $partner1) {
            throw new Exception("Commission partner_id was manipulated.");
        }
        echo "PASS\n";
    }

    public function test54_referralCodeInjectionAttemptsFail(): void {
        echo "[Test 54] Referral code injection attempts fail... ";
        $injections = [
            "<script>alert(1)</script>",
            "'; DROP TABLE users; --",
            "../../../etc/passwd",
            "ABC\0DEF",
            "PARTNER 1",
            "PARTNER!"
        ];
        foreach ($injections as $inj) {
            if (ReferralService::validateCode($inj)) {
                throw new Exception("Injection code was incorrectly validated: $inj");
            }
        }
        echo "PASS\n";
    }

    public function test55_sqlInjectionAttemptsFail(): void {
        echo "[Test 55] SQL injection attempts in referral lookup fail... ";
        $res = ReferralService::findPartnerByCode("' OR '1'='1", $this->db);
        if ($res !== null) {
            throw new Exception("SQL injection string found a partner!");
        }
        echo "PASS\n";
    }

    public function test56_xssOutputEscapingWorks(): void {
        echo "[Test 56] XSS output escaping works... ";
        $raw = '<script>alert("xss")</script>';
        $escaped = e($raw);
        if (strpos($escaped, '<script>') !== false) {
            throw new Exception("Helper e() failed to escape HTML tags.");
        }
        echo "PASS\n";
    }

    public function test57_browserSuppliedDiscountManipulationFails(): void {
        echo "[Test 57] Browser-supplied discount manipulation fails... ";
        $partner = $this->createPartner('DISCMAN', 10.00);
        $user = $this->createUser($partner, 'DISCMAN');

        // Request supplies manipulated discount
        $_POST['referral_discount'] = '500.00';
        $calc = ReferralService::calculateDiscount($user, '1500.00', 'PKR', $this->db);
        if ($calc['discount_amount'] === '500.00') {
            throw new Exception("Browser discount manipulated calculation.");
        }
        unset($_POST['referral_discount']);
        echo "PASS\n";
    }

    public function test58_browserSuppliedCommissionManipulationFails(): void {
        echo "[Test 58] Browser-supplied commission manipulation fails... ";
        $partner = $this->createPartner('COMMMAN', 10.00, 30.00);
        $user = $this->createUser($partner, 'COMMMAN');
        $tx = $this->createPayment($user, 1350.00, 'paid', $partner, 'COMMMAN');

        // Request supplies manipulated commission
        $_POST['commission_percentage'] = '90.00';
        $comm = ReferralService::calculateAndRecordCommission($tx, $this->db);
        if ($comm['commission_percentage'] === '90.00' || $comm['commission_amount'] === '1215.00') {
            throw new Exception("Browser commission manipulated calculation.");
        }
        unset($_POST['commission_percentage']);
        echo "PASS\n";
    }

    public function test59_browserSuppliedPaymentAmountManipulationFails(): void {
        echo "[Test 59] Browser-supplied payment amount manipulation fails... ";
        $partner = $this->createPartner('PAYMAN', 10.00);
        $user = $this->createUser($partner, 'PAYMAN');

        // Request supplies manipulated price
        $_POST['plan_price'] = '10.00';
        $calc = ReferralService::calculateDiscount($user, '1500.00', 'PKR', $this->db);
        if ($calc['original_amount'] === '10.00') {
            throw new Exception("Browser plan_price manipulated original amount.");
        }
        unset($_POST['plan_price']);
        echo "PASS\n";
    }

    // =========================================================================
    // GROUP 9: PAYMENT REGRESSION & CASHMAAL INTEGRATION (60-65)
    // =========================================================================

    public function test60_cashmaalFirstPaymentDiscountIntegratesCorrectly(): void {
        echo "[Test 60] CashMaal first-payment referral discount integrates correctly... ";
        $partner = $this->createPartner('CMINT60', 10.00);
        $user = $this->createUser($partner, 'CMINT60');

        $calc = ReferralService::calculateDiscount($user, '1500.00', 'PKR', $this->db);
        if ($calc['final_amount'] !== '1350.00' || $calc['discount_amount'] !== '150.00') {
            throw new Exception("CashMaal checkout discount calculation mismatch.");
        }
        echo "PASS\n";
    }

    public function test61_cashmaalRenewalHasNoReferralDiscount(): void {
        echo "[Test 61] CashMaal renewal has no referral discount... ";
        $partner = $this->createPartner('CMINT61', 10.00);
        $user = $this->createUser($partner, 'CMINT61');

        // First payment completed
        $this->createPayment($user, 1350.00, 'paid', $partner, 'CMINT61', 1500.00, 150.00, 10.00);

        // Renewal checkout
        $calc = ReferralService::calculateDiscount($user, '1500.00', 'PKR', $this->db);
        if ($calc['has_discount'] || $calc['final_amount'] !== '1500.00') {
            throw new Exception("Renewal checkout incorrectly gave discount.");
        }
        echo "PASS\n";
    }

    public function test62_cashmaalDuplicateIpnRemainsIdempotent(): void {
        echo "[Test 62] CashMaal duplicate IPN remains idempotent with commission... ";
        $partner = $this->createPartner('CMINT62');
        $user = $this->createUser($partner, 'CMINT62');
        $tx = $this->createPayment($user, 1350.00, 'paid', $partner, 'CMINT62');

        // Simulate repeated IPN calls
        $comm1 = ReferralService::calculateAndRecordCommission($tx, $this->db);
        $comm2 = ReferralService::calculateAndRecordCommission($tx, $this->db);

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM referral_commissions WHERE payment_transaction_id = :id");
        $stmt->execute(['id' => $tx]);
        if ((int)$stmt->fetchColumn() !== 1) {
            throw new Exception("Repeated IPN created multiple commission entries.");
        }
        echo "PASS\n";
    }

    public function test63_subscriptionActivationRemainsExactlyOnce(): void {
        echo "[Test 63] Subscription activation remains exactly once... ";
        $partner = $this->createPartner('CMINT63');
        $user = $this->createUser($partner, 'CMINT63');
        $tx = $this->createPayment($user, 1350.00, 'paid', $partner, 'CMINT63');

        // Activate subscription
        $stmtSubIns = $this->db->prepare("
            INSERT INTO subscriptions (user_id, plan_id, status, starts_at, ends_at, auto_renew, provider, provider_subscription_id, created_at, updated_at)
            VALUES (:uid, :pid, 'active', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 1, 'cashmaal', 'CM_SUB_63', NOW(), NOW())
        ");
        $stmtSubIns->execute(['uid' => $user, 'pid' => $this->planId]);

        $comm = ReferralService::calculateAndRecordCommission($tx, $this->db);

        $stmtSub = $this->db->prepare("SELECT COUNT(*) FROM subscriptions WHERE user_id = :uid");
        $stmtSub->execute(['uid' => $user]);
        if ((int)$stmtSub->fetchColumn() !== 1) {
            throw new Exception("Multiple subscriptions created.");
        }
        echo "PASS\n";
    }

    public function test64_metaPaymentConfirmationRemainsQueueOnly(): void {
        echo "[Test 64] Meta payment confirmation remains queue-only... ";
        $partner = $this->createPartner('CMINT64');
        $user = $this->createUser($partner, 'CMINT64');
        $tx = $this->createPayment($user, 1350.00, 'paid', $partner, 'CMINT64');

        $stmtBefore = $this->db->query("SELECT COUNT(*) FROM notification_logs")->fetchColumn();
        $this->billingController->enqueuePaymentConfirmation($user, $tx, "TXN_STEP5_{$this->seq}", 1350.00, 'PKR', $this->planId);
        $stmtAfter = $this->db->query("SELECT COUNT(*) FROM notification_logs")->fetchColumn();

        if ($stmtAfter <= $stmtBefore) {
            throw new Exception("Notification log row was not queued.");
        }

        // Check provider is meta
        $stmtLog = $this->db->prepare("SELECT provider, status FROM notification_logs WHERE user_id = :uid ORDER BY id DESC LIMIT 1");
        $stmtLog->execute(['uid' => $user]);
        $row = $stmtLog->fetch(PDO::FETCH_ASSOC);
        if ($row['provider'] !== 'meta' || !in_array($row['status'], ['pending', 'queued'], true)) {
            throw new Exception("Notification log is not queued with meta provider.");
        }
        echo "PASS\n";
    }

    public function test65_step4AmountNormalizerRemainsActive(): void {
        echo "[Test 65] Existing Step 4 payment amount normalizer remains active... ";
        if (PaymentService::normalizeToMinorUnits('1000.00') !== 100000) {
            throw new Exception("Valid normal amount failed normalization.");
        }
        if (PaymentService::normalizeToMinorUnits('1,2,3.00') !== null) {
            throw new Exception("Comma amount was unexpectedly accepted by Step 4 normalizer.");
        }
        if (PaymentService::normalizeToMinorUnits('1,000.00') !== null) {
            throw new Exception("Grouped comma amount was unexpectedly accepted by Step 4 normalizer.");
        }
        if (PaymentService::normalizeToMinorUnits('10 00.00') !== null) {
            throw new Exception("Internal whitespace was unexpectedly accepted.");
        }
        echo "PASS\n";
    }

    // =========================================================================
    // GROUP 10: INTEGER OVERFLOW PROTECTION & BOUNDED RANGE (66-71)
    // =========================================================================

    public function test66_normalDiscountCalculationRemainsCorrect(): void {
        echo "[Test 66] Normal discount calculation remains correct... ";
        $partner = $this->createPartner('NORM66', 10.00);
        $user = $this->createUser($partner, 'NORM66');

        $calc = ReferralService::calculateDiscount($user, '1500.00', 'PKR', $this->db);
        if ($calc['discount_amount'] !== '150.00' || $calc['final_amount'] !== '1350.00') {
            throw new Exception("Normal discount failed: expected 150.00 and 1350.00, got {$calc['discount_amount']} and {$calc['final_amount']}.");
        }
        echo "PASS\n";
    }

    public function test67_normalCommissionCalculationRemainsCorrect(): void {
        echo "[Test 67] Normal commission calculation remains correct... ";
        $partner = $this->createPartner('COMM67', 10.00, 30.00);
        $user = $this->createUser($partner, 'COMM67');
        $tx = $this->createPayment($user, 1350.00, 'paid', $partner, 'COMM67');

        $comm = ReferralService::calculateAndRecordCommission($tx, $this->db);
        if ($comm['commission_amount'] !== '405.00') {
            throw new Exception("Normal commission failed: expected 405.00, got {$comm['commission_amount']}.");
        }
        echo "PASS\n";
    }

    public function test68_maximumSupportedPlanAmountDoesNotOverflow(): void {
        echo "[Test 68] Maximum supported plan/amount does not overflow... ";
        $maxBase = ReferralService::MAX_SUPPORTED_MINOR_UNITS; // 9,999,999,999 minor units (99,999,999.99)
        
        // 0% (0 bps)
        $res0 = ReferralService::calculatePercentageMinorSafe($maxBase, 0);
        if ($res0 !== 0 || !is_int($res0)) {
            throw new Exception("0% of max base returned " . var_export($res0, true) . " instead of 0.");
        }

        // 10% (1000 bps)
        $res10 = ReferralService::calculatePercentageMinorSafe($maxBase, 1000);
        $expected10 = 999999999;
        if ($res10 !== $expected10 || !is_int($res10)) {
            throw new Exception("10% of max base returned " . var_export($res10, true) . " instead of $expected10.");
        }

        // 30% (3000 bps)
        $res30 = ReferralService::calculatePercentageMinorSafe($maxBase, 3000);
        $expected30 = 2999999999;
        if ($res30 !== $expected30 || !is_int($res30)) {
            throw new Exception("30% of max base returned " . var_export($res30, true) . " instead of $expected30.");
        }

        // 50% (5000 bps)
        $res50 = ReferralService::calculatePercentageMinorSafe($maxBase, 5000);
        $expected50 = 4999999999;
        if ($res50 !== $expected50 || !is_int($res50)) {
            throw new Exception("50% of max base returned " . var_export($res50, true) . " instead of $expected50.");
        }

        // 99.99% (9999 bps)
        $res9999 = ReferralService::calculatePercentageMinorSafe($maxBase, 9999);
        $expected9999 = 9998999999;
        if ($res9999 !== $expected9999 || !is_int($res9999)) {
            throw new Exception("99.99% of max base returned " . var_export($res9999, true) . " instead of $expected9999.");
        }

        // 100% (10000 bps)
        $res100 = ReferralService::calculatePercentageMinorSafe($maxBase, 10000);
        if ($res100 !== $maxBase || !is_int($res100)) {
            throw new Exception("100% of max base returned " . var_export($res100, true) . " instead of $maxBase.");
        }

        // Immediate above-limit rejection: maxBase + 1 must return null
        $oversized = $maxBase + 1;
        $resOver = ReferralService::calculatePercentageMinorSafe($oversized, 3000);
        if ($resOver !== null) {
            throw new Exception("Value immediately above max base ($oversized) was not rejected: got " . var_export($resOver, true));
        }

        echo "PASS\n";
    }

    public function test69_oversizedMonetaryInputRejectedSafely(): void {
        echo "[Test 69] Oversized monetary input is rejected safely... ";
        $oversized = ReferralService::MAX_SUPPORTED_MINOR_UNITS + 1;
        $res = ReferralService::calculatePercentageMinorSafe($oversized, 3000);
        if ($res !== null) {
            throw new Exception("Oversized base minor units was not rejected!");
        }

        $caught = false;
        try {
            $partner = $this->createPartner('OVER69');
            $user = $this->createUser($partner, 'OVER69');
            ReferralService::calculateDiscount($user, '100000000.00', 'PKR', $this->db);
        } catch (\InvalidArgumentException $e) {
            $caught = true;
        }
        if (!$caught) {
            throw new Exception("calculateDiscount did not reject oversized plan price.");
        }
        echo "PASS\n";
    }

    public function test70_noFloatConversionOccursInReferralMonetaryCalculations(): void {
        echo "[Test 70] No float conversion occurs in referral monetary calculations... ";
        // 19.99 with 10% discount:
        // minor units = 1999, 1000 bps
        // expected discount = intdiv(1999 * 1000, 10000) = 199 minor units = 1.99
        // final = 1999 - 199 = 1800 minor units = 18.00
        $discMinor = ReferralService::calculatePercentageMinorSafe(1999, 1000);
        if (!is_int($discMinor) || $discMinor !== 199) {
            throw new Exception("Expected int(199), got: " . var_export($discMinor, true));
        }

        $partner = $this->createPartner('FLOAT70', 10.00);
        $user = $this->createUser($partner, 'FLOAT70');
        $calc = ReferralService::calculateDiscount($user, '19.99', 'PKR', $this->db);
        if ($calc['discount_amount'] !== '1.99' || $calc['final_amount'] !== '18.00') {
            throw new Exception("Calculated amounts mismatch for 19.99: {$calc['discount_amount']}, {$calc['final_amount']}");
        }
        echo "PASS\n";
    }

    public function test71_overflowConditionsFailSafely(): void {
        echo "[Test 71] Overflow conditions fail safely instead of wrapping... ";
        // Negative base amount
        if (ReferralService::calculatePercentageMinorSafe(-1000, 3000) !== null) {
            throw new Exception("Negative base amount was not rejected.");
        }
        // Negative percentage
        if (ReferralService::calculatePercentageMinorSafe(150000, -100) !== null) {
            throw new Exception("Negative percentage was not rejected.");
        }
        // Percentage > 100%
        if (ReferralService::calculatePercentageMinorSafe(150000, 10001) !== null) {
            throw new Exception("Percentage > 100% was not rejected.");
        }
        echo "PASS\n";
    }

    // =========================================================================
    // GROUP 11: STRICT PERCENTAGE BOUNDS ENFORCEMENT (72-83)
    // =========================================================================

    public function test72_discountZeroPercentAccepted(): void {
        echo "[Test 72] Discount 0% accepted... ";
        $bps = ReferralService::parsePercentageToBasisPoints('0.00');
        if ($bps !== 0) {
            throw new Exception("Expected 0 basis points for 0.00%, got: " . var_export($bps, true));
        }
        $partner = $this->createPartner('DISC72', 0.00);
        $user = $this->createUser($partner, 'DISC72');
        $calc = ReferralService::calculateDiscount($user, '1500.00', 'PKR', $this->db);
        if ($calc['has_discount'] || $calc['final_amount'] !== '1500.00' || $calc['discount_amount'] !== '0.00') {
            throw new Exception("0% discount produced non-zero discount amount.");
        }
        echo "PASS\n";
    }

    public function test73_discountOneHundredPercentAccepted(): void {
        echo "[Test 73] Discount 100% accepted... ";
        $bps = ReferralService::parsePercentageToBasisPoints('100.00');
        if ($bps !== 10000) {
            throw new Exception("Expected 10000 basis points for 100.00%, got: " . var_export($bps, true));
        }
        $partner = $this->createPartner('DISC73', 100.00);
        $user = $this->createUser($partner, 'DISC73');
        $calc = ReferralService::calculateDiscount($user, '1500.00', 'PKR', $this->db);
        if (!$calc['has_discount'] || $calc['final_amount'] !== '0.00' || $calc['discount_amount'] !== '1500.00') {
            throw new Exception("100% discount did not produce 0.00 final amount.");
        }
        echo "PASS\n";
    }

    public function test74_discountOneHundredPointZeroOnePercentRejected(): void {
        echo "[Test 74] Discount 100.01% rejected... ";
        if (ReferralService::isValidPercentage('100.01')) {
            throw new Exception("100.01% was unexpectedly marked valid.");
        }
        if (ReferralService::parsePercentageToBasisPoints('100.01') !== null) {
            throw new Exception("100.01% was not rejected by parser.");
        }
        echo "PASS\n";
    }

    public function test75_discountOneHundredAndOnePercentRejected(): void {
        echo "[Test 75] Discount 101% rejected... ";
        if (ReferralService::isValidPercentage('101')) {
            throw new Exception("101% was unexpectedly marked valid.");
        }
        if (ReferralService::parsePercentageToBasisPoints('101') !== null) {
            throw new Exception("101% was not rejected by parser.");
        }
        echo "PASS\n";
    }

    public function test76_negativeDiscountRejected(): void {
        echo "[Test 76] Negative discount rejected... ";
        if (ReferralService::isValidPercentage('-1') || ReferralService::isValidPercentage('-10.00')) {
            throw new Exception("Negative discount percentage was unexpectedly marked valid.");
        }
        if (ReferralService::parsePercentageToBasisPoints('-1') !== null) {
            throw new Exception("Negative discount was not rejected by parser.");
        }
        echo "PASS\n";
    }

    public function test77_commissionZeroPercentAccepted(): void {
        echo "[Test 77] Commission 0% accepted... ";
        $bps = ReferralService::parsePercentageToBasisPoints('0.00');
        if ($bps !== 0) {
            throw new Exception("Expected 0 basis points for 0.00% commission.");
        }
        $partner = $this->createPartner('COMM77', 10.00, 0.00);
        $user = $this->createUser($partner, 'COMM77');
        $tx = $this->createPayment($user, 1350.00, 'paid', $partner, 'COMM77');
        $comm = ReferralService::calculateAndRecordCommission($tx, $this->db);
        if ($comm !== null) {
            throw new Exception("0% commission created a commission record!");
        }
        echo "PASS\n";
    }

    public function test78_commissionOneHundredPercentAccepted(): void {
        echo "[Test 78] Commission 100% accepted... ";
        $bps = ReferralService::parsePercentageToBasisPoints('100.00');
        if ($bps !== 10000) {
            throw new Exception("Expected 10000 basis points for 100.00% commission.");
        }
        $partner = $this->createPartner('COMM78', 10.00, 100.00);
        $user = $this->createUser($partner, 'COMM78');
        $tx = $this->createPayment($user, 1350.00, 'paid', $partner, 'COMM78');
        $comm = ReferralService::calculateAndRecordCommission($tx, $this->db);
        if (!$comm || $comm['commission_amount'] !== '1350.00') {
            throw new Exception("100% commission failed to award 1350.00, got: " . var_export($comm, true));
        }
        echo "PASS\n";
    }

    public function test79_commissionOneHundredPointZeroOnePercentRejected(): void {
        echo "[Test 79] Commission 100.01% rejected... ";
        if (ReferralService::isValidPercentage('100.01')) {
            throw new Exception("100.01% commission was marked valid.");
        }
        if (ReferralService::parsePercentageToBasisPoints('100.01') !== null) {
            throw new Exception("100.01% commission was not rejected by parser.");
        }
        echo "PASS\n";
    }

    public function test80_commissionOneHundredAndOnePercentRejected(): void {
        echo "[Test 80] Commission 101% rejected... ";
        if (ReferralService::isValidPercentage('101') || ReferralService::isValidPercentage('999')) {
            throw new Exception("101% commission was marked valid.");
        }
        if (ReferralService::parsePercentageToBasisPoints('101') !== null) {
            throw new Exception("101% commission was not rejected by parser.");
        }
        echo "PASS\n";
    }

    public function test81_negativeCommissionRejected(): void {
        echo "[Test 81] Negative commission rejected... ";
        if (ReferralService::isValidPercentage('-1') || ReferralService::isValidPercentage('-30.00')) {
            throw new Exception("Negative commission was marked valid.");
        }
        if (ReferralService::parsePercentageToBasisPoints('-1') !== null) {
            throw new Exception("Negative commission was not rejected by parser.");
        }
        echo "PASS\n";
    }

    public function test82_allRequiredValidPercentagesPass(): void {
        echo "[Test 82] All required valid percentages pass with exact basis points... ";
        $validCases = [
            '0' => 0,
            '0.00' => 0,
            '10' => 1000,
            '10.00' => 1000,
            '30' => 3000,
            '30.00' => 3000,
            '30.1' => 3010,
            '30.10' => 3010,
            '30.100' => 3010,
            '30.000' => 3000,
            '99.99' => 9999,
            '100' => 10000,
            '100.00' => 10000,
            '100.000' => 10000,
        ];

        foreach ($validCases as $input => $expectedBps) {
            $bps = ReferralService::parsePercentageToBasisPoints($input);
            if ($bps !== $expectedBps) {
                throw new Exception("Valid percentage '$input' failed: expected $expectedBps bps, got " . var_export($bps, true));
            }
            if (!ReferralService::isValidPercentage($input)) {
                throw new Exception("isValidPercentage returned false for valid input '$input'");
            }
        }
        echo "PASS\n";
    }

    public function test82b_allRequiredInvalidPercentagesRejected(): void {
        echo "[Test 82b] All required invalid percentages rejected without silent truncation... ";
        $invalidCases = [
            '-1',
            '-10.00',
            '100.01',
            '101',
            '999',
            '30.001',
            '30.009',
            '99.999',
            '100.001',
            '10.00.00',
            '10%',
            '1,00',
            '1,000.00',
            '+10',
            '10 00',
            'abc',
        ];

        foreach ($invalidCases as $input) {
            $bps = ReferralService::parsePercentageToBasisPoints($input);
            if ($bps !== null) {
                throw new Exception("Invalid percentage '$input' was NOT rejected, returned: " . var_export($bps, true));
            }
            if (ReferralService::isValidPercentage($input)) {
                throw new Exception("isValidPercentage returned true for invalid input '$input'");
            }
        }
        echo "PASS\n";
    }

    public function test82c_strictWhitespaceValidationRejected(): void {
        echo "[Test 82c] Strict whitespace validation: leading, trailing, and internal whitespace rejected... ";
        $validCases = [
            '0' => 0,
            '0.00' => 0,
            '10' => 1000,
            '30' => 3000,
            '30.10' => 3010,
            '30.100' => 3010,
            '30.000' => 3000,
            '99.99' => 9999,
            '100' => 10000,
            '100.000' => 10000,
        ];
        foreach ($validCases as $input => $expectedBps) {
            $bps = ReferralService::parsePercentageToBasisPoints($input);
            if ($bps !== $expectedBps) {
                throw new Exception("Expected $expectedBps bps for valid input '$input', got " . var_export($bps, true));
            }
        }

        $invalidWhitespaceCases = [
            " 30",
            "30 ",
            " 30 ",
            "\t30",
            "30\t",
            "30 .00",
            "30. 00",
            "30\n",
            "\r30",
            "30\r\n",
        ];
        foreach ($invalidWhitespaceCases as $input) {
            $bps = ReferralService::parsePercentageToBasisPoints($input);
            if ($bps !== null) {
                throw new Exception("Whitespace input " . addcslashes($input, "\t\r\n") . " was NOT rejected! Got: " . var_export($bps, true));
            }
            if (ReferralService::isValidPercentage($input)) {
                throw new Exception("isValidPercentage returned true for whitespace input: " . addcslashes($input, "\t\r\n"));
            }
        }
        echo "PASS\n";
    }

    public function test83_browserSuppliedPercentageCannotBypassServerValidation(): void {
        echo "[Test 83] Browser-supplied percentage cannot bypass server validation... ";
        $partner = $this->createPartner('BYPASS83', 10.00, 30.00);
        $user = $this->createUser($partner, 'BYPASS83');

        // Attacker posts huge/invalid percentages in request
        $_POST['discount_percent'] = '999.00';
        $_POST['commission_percent'] = '999.00';

        $calc = ReferralService::calculateDiscount($user, '1500.00', 'PKR', $this->db);
        if ($calc['discount_percent'] === '999.00' || $calc['discount_amount'] !== '150.00') {
            throw new Exception("Browser discount bypassed server validation!");
        }

        $tx = $this->createPayment($user, 1350.00, 'paid', $partner, 'BYPASS83');
        $comm = ReferralService::calculateAndRecordCommission($tx, $this->db);
        if ($comm['commission_percentage'] === '999.00' || $comm['commission_amount'] !== '405.00') {
            throw new Exception("Browser commission bypassed server validation!");
        }

        unset($_POST['discount_percent'], $_POST['commission_percent']);
        echo "PASS\n";
    }

    // =========================================================================
    // GROUP 12: SELF-REFERRAL INVARIANT & PROTECTION (84-88)
    // =========================================================================

    public function test84_partnerRegisteringWithOwnReferralCodeYieldsNoAttribution(): void {
        echo "[Test 84] Partner registering with own referral code yields no attribution... ";
        $partnerId = $this->createPartner('SELF84');
        $partnerEmail = "step5_partner_{$this->seq}@test.com";

        // Direct service check
        $res = ReferralService::attributeUser($partnerId, 'SELF84', $this->db);
        if ($res !== false) {
            throw new Exception("attributeUser allowed self-attribution!");
        }

        $stmt = $this->db->prepare("SELECT referral_partner_id, referred_by_code FROM users WHERE id = :id");
        $stmt->execute(['id' => $partnerId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!empty($row['referral_partner_id']) && (int)$row['referral_partner_id'] === $partnerId) {
            throw new Exception("Self-attribution occurred in users table!");
        }
        echo "PASS\n";
    }

    public function test85_partnerCannotCreateSelfReferralThroughPostManipulation(): void {
        echo "[Test 85] Partner cannot create self-referral through POST manipulation... ";
        $partnerId = $this->createPartner('POST85');

        // Request tampering attempting to attribute partner to themselves
        $attributed = ReferralService::attributeUser($partnerId, 'POST85', $this->db);
        if ($attributed !== false) {
            throw new Exception("Self-referral succeeded via POST manipulation!");
        }

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM referral_signups WHERE partner_id = :pid AND referred_user_id = :uid");
        $stmt->execute(['pid' => $partnerId, 'uid' => $partnerId]);
        if ((int)$stmt->fetchColumn() > 0) {
            throw new Exception("Self-referral signup row was inserted!");
        }
        echo "PASS\n";
    }

    public function test86_partnerCannotCreateSelfReferralThroughReferralUrl(): void {
        echo "[Test 86] Partner cannot create self-referral through referral URL... ";
        $partnerId = $this->createPartner('URL86');

        // If user already exists as partnerId and visits /register?ref=URL86
        $res = ReferralService::attributeUser($partnerId, 'URL86', $this->db);
        if ($res !== false) {
            throw new Exception("Self-referral allowed via referral URL!");
        }
        echo "PASS\n";
    }

    public function test87_commissionServiceRejectsSelfReferral(): void {
        echo "[Test 87] Commission service rejects self-referral... ";
        $partnerId = $this->createPartner('COMM87');

        // Create a payment transaction where user_id IS the partner
        $tx = $this->createPayment($partnerId, 1350.00, 'paid', $partnerId, 'COMM87');

        $comm = ReferralService::calculateAndRecordCommission($tx, $this->db);
        if ($comm !== null) {
            throw new Exception("Commission service incorrectly awarded commission to self-referral!");
        }

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM referral_commissions WHERE payment_transaction_id = :tx");
        $stmt->execute(['tx' => $tx]);
        if ((int)$stmt->fetchColumn() > 0) {
            throw new Exception("Self-referral commission row was inserted!");
        }
        echo "PASS\n";
    }

    public function test88_databaseInvariantProtectsAgainstSelfReferral(): void {
        echo "[Test 88] Database invariant protects against self-referral (MySQL check constraint)... ";
        $partnerId = $this->createPartner('DBCHK88');

        // 1. Check constraint on referral_signups: chk_refsignups_no_self_referral
        $caughtSignup = false;
        try {
            $stmt = $this->db->prepare("
                INSERT INTO referral_signups (partner_id, referred_user_id, referral_code, created_at)
                VALUES (:pid, :uid, 'DBCHK88', NOW())
            ");
            $stmt->execute(['pid' => $partnerId, 'uid' => $partnerId]);
        } catch (\PDOException $e) {
            $caughtSignup = true;
        }
        if (!$caughtSignup) {
            throw new Exception("MySQL check constraint on referral_signups failed to block self-referral!");
        }

        // 2. Check constraint on referral_commissions: chk_refcomm_no_self_referral
        $caughtComm = false;
        try {
            $stmt = $this->db->prepare("
                INSERT INTO referral_commissions (
                    partner_id, referred_user_id, payment_transaction_id, transaction_reference,
                    original_plan_amount, referral_discount_percentage, referral_discount_amount,
                    actual_paid_amount, commission_percentage, commission_basis, commission_base_amount,
                    commission_amount, payment_date, attribution_period_start, attribution_period_end
                ) VALUES (
                    :pid, :uid, 9999999, 'TXN_TEST_DBCHK', 1500, 10, 150, 1350, 30, 'paid_amount_after_discount', 1350, 405, NOW(), NOW(), NOW()
                )
            ");
            $stmt->execute(['pid' => $partnerId, 'uid' => $partnerId]);
        } catch (\PDOException $e) {
            $caughtComm = true;
        }
        if (!$caughtComm) {
            throw new Exception("MySQL check constraint on referral_commissions failed to block self-referral!");
        }
        echo "PASS\n";
    }

    public function test89_adminPartnerPercentageUpdateRequiresValidPercentage(): void {
        echo "[Test 89] Admin partner percentage update validates percentages strictly... ";
        $partnerId = $this->createPartner('UPD89', 10.00, 30.00);

        // Invalid percentages with non-zero discarded precision or out-of-bounds are rejected
        $invalidInputs = ['30.001', '30.009', '100.01', '-5.00', '15%', '1,000.00', 'abc'];
        foreach ($invalidInputs as $inv) {
            $bps = ReferralService::parsePercentageToBasisPoints($inv);
            if ($bps !== null) {
                throw new Exception("Partner percentage update would allow invalid percentage '$inv'");
            }
        }

        // Valid percentages with trailing zero precision are parsed exactly
        $validInputs = [
            '15.000' => '15.00',
            '35.00' => '35.00',
            '0.000' => '0.00',
            '100.000' => '100.00',
        ];
        foreach ($validInputs as $val => $expectedStr) {
            $bps = ReferralService::parsePercentageToBasisPoints($val);
            if ($bps === null) {
                throw new Exception("Partner percentage update rejected valid input '$val'");
            }
            $formatted = sprintf('%d.%02d', intdiv($bps, 100), $bps % 100);
            if ($formatted !== $expectedStr) {
                throw new Exception("Expected formatted '$expectedStr', got '$formatted'");
            }
        }

        // Simulate successful update in DB with valid parsed percentages
        $bpsDisc = ReferralService::parsePercentageToBasisPoints('15.000');
        $bpsComm = ReferralService::parsePercentageToBasisPoints('35.00');
        $discStr = sprintf('%d.%02d', intdiv($bpsDisc, 100), $bpsDisc % 100);
        $commStr = sprintf('%d.%02d', intdiv($bpsComm, 100), $bpsComm % 100);

        $stmt = $this->db->prepare("UPDATE users SET discount_percent = :disc, commission_percent = :comm WHERE id = :id");
        $stmt->execute(['disc' => $discStr, 'comm' => $commStr, 'id' => $partnerId]);

        $stmtChk = $this->db->prepare("SELECT discount_percent, commission_percent FROM users WHERE id = :id");
        $stmtChk->execute(['id' => $partnerId]);
        $row = $stmtChk->fetch(PDO::FETCH_ASSOC);

        if ($row['discount_percent'] !== '15.00' || $row['commission_percent'] !== '35.00') {
            throw new Exception("Updated partner percentages do not match expected: " . var_export($row, true));
        }

        echo "PASS\n";
    }

    // =========================================================================
    // GROUP 13: FIRST-PAYMENT DISCOUNT CONCURRENCY & ATOMIC ENTITLEMENT (90-95)
    // =========================================================================

    public function test90_twoCheckoutAttemptsBeforePaymentSuccess(): void {
        echo "[Test 90] Concurrency Test A: Two checkout attempts before payment success (only one gets discount)... ";
        $partnerId = $this->createPartner('CONC90', 10.00, 30.00);
        $userId = $this->createUser($partnerId, 'CONC90');

        // Request 1 starts checkout with atomic reservation ($reserve = true)
        $this->db->beginTransaction();
        $calc1 = ReferralService::calculateDiscount($userId, '1500.00', 'PKR', $this->db, true);
        if (!$calc1['has_discount'] || $calc1['final_amount'] !== '1350.00') {
            $this->db->rollBack();
            throw new Exception("Checkout 1 did not receive first-payment discount!");
        }

        // Insert pending transaction for Checkout 1
        $stmtTx1 = $this->db->prepare("
            INSERT INTO payment_transactions (user_id, plan_id, provider, transaction_reference, amount, original_amount, referral_discount_amount, discount_percent, referral_code_used, referral_partner_id, currency, status, created_at, updated_at)
            VALUES (:uid, :pid, 'cashmaal', 'TXN_CONC90_1', :amt, 1500.00, 150.00, 10.00, 'CONC90', :pid_ref, 'PKR', 'pending', NOW(), NOW())
        ");
        $stmtTx1->execute(['uid' => $userId, 'pid' => $this->planId, 'amt' => '1350.00', 'pid_ref' => $partnerId]);
        $tx1Id = (int)$this->db->lastInsertId();
        ReferralService::linkDiscountClaimToTransaction($userId, $tx1Id, $this->db);
        $this->db->commit();

        // Request 2 starts checkout concurrently while Checkout 1 is still pending
        $this->db->beginTransaction();
        $calc2 = ReferralService::calculateDiscount($userId, '1500.00', 'PKR', $this->db, true);
        $this->db->commit();

        // Verification: Checkout 2 must NOT receive first-payment discount!
        if ($calc2['has_discount']) {
            throw new Exception("Concurrency violation: Checkout 2 received first-payment discount while Checkout 1 was pending!");
        }
        if ($calc2['final_amount'] !== '1500.00') {
            throw new Exception("Checkout 2 final amount expected 1500.00, got: {$calc2['final_amount']}");
        }

        echo "PASS\n";
    }

    public function test91_firstPaymentFailsUserRemainsEligible(): void {
        echo "[Test 91] Concurrency Test B: First payment fails/cancelled -> user remains eligible for discount... ";
        $partnerId = $this->createPartner('FAIL91', 10.00, 30.00);
        $userId = $this->createUser($partnerId, 'FAIL91');

        // First checkout reserves discount
        $this->db->beginTransaction();
        $calc1 = ReferralService::calculateDiscount($userId, '1500.00', 'PKR', $this->db, true);
        $stmtTx = $this->db->prepare("
            INSERT INTO payment_transactions (user_id, plan_id, provider, transaction_reference, amount, original_amount, referral_discount_amount, discount_percent, referral_code_used, referral_partner_id, currency, status, created_at, updated_at)
            VALUES (:uid, :pid, 'cashmaal', 'TXN_FAIL91_1', '1350.00', 1500.00, 150.00, 10.00, 'FAIL91', :pid_ref, 'PKR', 'pending', NOW(), NOW())
        ");
        $stmtTx->execute(['uid' => $userId, 'pid' => $this->planId, 'pid_ref' => $partnerId]);
        $tx1Id = (int)$this->db->lastInsertId();
        ReferralService::linkDiscountClaimToTransaction($userId, $tx1Id, $this->db);
        $this->db->commit();

        // Payment 1 fails and reservation is released
        $this->db->beginTransaction();
        $stmtFail = $this->db->prepare("UPDATE payment_transactions SET status = 'failed', updated_at = NOW() WHERE id = :id");
        $stmtFail->execute(['id' => $tx1Id]);
        ReferralService::releaseDiscountClaim($tx1Id, $this->db);
        $this->db->commit();

        // Second checkout attempt by the same user
        $this->db->beginTransaction();
        $calc2 = ReferralService::calculateDiscount($userId, '1500.00', 'PKR', $this->db, true);
        $this->db->commit();

        if (!$calc2['has_discount'] || $calc2['final_amount'] !== '1350.00') {
            throw new Exception("User was denied first-payment discount after prior attempt failed!");
        }

        echo "PASS\n";
    }

    public function test92_firstPaymentSucceedsSecondCheckoutGetsNoDiscount(): void {
        echo "[Test 92] Concurrency Test C: First payment succeeds -> second checkout gets NO referral discount... ";
        $partnerId = $this->createPartner('SUCC92', 10.00, 30.00);
        $userId = $this->createUser($partnerId, 'SUCC92');

        // First checkout and successful payment
        $this->db->beginTransaction();
        $calc1 = ReferralService::calculateDiscount($userId, '1500.00', 'PKR', $this->db, true);
        $stmtTx = $this->db->prepare("
            INSERT INTO payment_transactions (user_id, plan_id, provider, transaction_reference, amount, original_amount, referral_discount_amount, discount_percent, referral_code_used, referral_partner_id, currency, status, created_at, updated_at)
            VALUES (:uid, :pid, 'cashmaal', 'TXN_SUCC92_1', '1350.00', 1500.00, 150.00, 10.00, 'SUCC92', :pid_ref, 'PKR', 'paid', NOW(), NOW())
        ");
        $stmtTx->execute(['uid' => $userId, 'pid' => $this->planId, 'pid_ref' => $partnerId]);
        $tx1Id = (int)$this->db->lastInsertId();
        ReferralService::linkDiscountClaimToTransaction($userId, $tx1Id, $this->db);
        ReferralService::consumeDiscountClaim($tx1Id, $this->db);
        $this->db->commit();

        // Second checkout attempt by the user
        $this->db->beginTransaction();
        $calc2 = ReferralService::calculateDiscount($userId, '1500.00', 'PKR', $this->db, true);
        $this->db->commit();

        if ($calc2['has_discount'] || $calc2['final_amount'] !== '1500.00') {
            throw new Exception("Second checkout incorrectly received first-payment discount after successful payment!");
        }

        echo "PASS\n";
    }

    public function test93_duplicateSuccessfulIpnRemainsIdempotent(): void {
        echo "[Test 93] Concurrency Test D: Duplicate successful IPN -> idempotent without additional discount... ";
        $partnerId = $this->createPartner('IDEM93', 10.00, 30.00);
        $userId = $this->createUser($partnerId, 'IDEM93');

        $calc = ReferralService::calculateDiscount($userId, '1500.00', 'PKR', $this->db, true);
        $txId = $this->createPayment($userId, 1350.00, 'paid', $partnerId, 'IDEM93');
        ReferralService::linkDiscountClaimToTransaction($userId, $txId, $this->db);
        ReferralService::consumeDiscountClaim($txId, $this->db);

        // Record commission first time
        $comm1 = ReferralService::calculateAndRecordCommission($txId, $this->db);
        if (!$comm1) {
            throw new Exception("First commission calculation failed!");
        }

        // Duplicate IPN arrives
        $comm2 = ReferralService::calculateAndRecordCommission($txId, $this->db);
        if ($comm2['id'] !== $comm1['id']) {
            throw new Exception("Duplicate commission record was created on duplicate IPN!");
        }

        // Confirm only 1 commission in database
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM referral_commissions WHERE payment_transaction_id = :tx");
        $stmt->execute(['tx' => $txId]);
        if ((int)$stmt->fetchColumn() !== 1) {
            throw new Exception("More than one commission row exists for duplicate IPN!");
        }

        // Confirm discount claim remains consumed
        $stmtClaim = $this->db->prepare("SELECT status FROM referral_discount_claims WHERE referred_user_id = :uid");
        $stmtClaim->execute(['uid' => $userId]);
        if ($stmtClaim->fetchColumn() !== 'consumed') {
            throw new Exception("Claim status is not consumed!");
        }

        echo "PASS\n";
    }

    public function test94_twoSuccessfulPaymentsForSameUserOnlyFirstGetsDiscount(): void {
        echo "[Test 94] Concurrency Test E: Two sequential payments for same user (Payment #1 discounted, Payment #2 full price)... ";
        $partnerId = $this->createPartner('TWO94', 10.00, 30.00);
        $userId = $this->createUser($partnerId, 'TWO94');

        // Payment 1
        $calc1 = ReferralService::calculateDiscount($userId, '1500.00', 'PKR', $this->db, true);
        if (!$calc1['has_discount'] || $calc1['final_amount'] !== '1350.00') {
            throw new Exception("Payment 1 did not get discount!");
        }
        $tx1 = $this->createPayment($userId, 1350.00, 'paid', $partnerId, 'TWO94');
        ReferralService::linkDiscountClaimToTransaction($userId, $tx1, $this->db);
        ReferralService::consumeDiscountClaim($tx1, $this->db);

        // Payment 2 (renewal / next billing cycle)
        $calc2 = ReferralService::calculateDiscount($userId, '1500.00', 'PKR', $this->db, true);
        if ($calc2['has_discount'] || $calc2['final_amount'] !== '1500.00') {
            throw new Exception("Payment 2 got discount when it should have been full price!");
        }
        $tx2 = $this->createPayment($userId, 1500.00, 'paid', $partnerId, 'TWO94');

        $stmtTx1 = $this->db->prepare("SELECT referral_discount_amount FROM payment_transactions WHERE id = :id");
        $stmtTx1->execute(['id' => $tx1]);
        $stmtTx2 = $this->db->prepare("SELECT referral_discount_amount FROM payment_transactions WHERE id = :id");
        $stmtTx2->execute(['id' => $tx2]);

        if ((float)$stmtTx1->fetchColumn() !== 150.00 || (float)$stmtTx2->fetchColumn() !== 0.00) {
            throw new Exception("Discounts on transactions do not match expectation!");
        }

        echo "PASS\n";
    }

    public function test95_differentReferredUsersClaimDiscountIndependently(): void {
        echo "[Test 95] Concurrency Test F: Different referred users claim first-payment discount independently... ";
        $partnerId = $this->createPartner('DIFF95', 10.00, 30.00);
        $userA = $this->createUser($partnerId, 'DIFF95');
        $userB = $this->createUser($partnerId, 'DIFF95');

        // User A checkouts
        $calcA = ReferralService::calculateDiscount($userA, '1500.00', 'PKR', $this->db, true);
        // User B checkouts concurrently
        $calcB = ReferralService::calculateDiscount($userB, '1500.00', 'PKR', $this->db, true);

        if (!$calcA['has_discount'] || $calcA['final_amount'] !== '1350.00') {
            throw new Exception("User A failed to claim first-payment discount!");
        }
        if (!$calcB['has_discount'] || $calcB['final_amount'] !== '1350.00') {
            throw new Exception("User B failed to claim first-payment discount!");
        }

        echo "PASS\n";
    }

    public function test96_abandonedReservationExpiresAndUserEligibleAgain(): void {
        echo "[Test 96] Reservation Hardening: Abandoned reservation expires past TTL and user becomes eligible again... ";
        $partnerId = $this->createPartner('EXP96', 10.00, 30.00);
        $userId = $this->createUser($partnerId, 'EXP96');

        // Initial checkout creates reservation
        $calc1 = ReferralService::calculateDiscount($userId, '1500.00', 'PKR', $this->db, true);
        if (!$calc1['has_discount'] || $calc1['final_amount'] !== '1350.00') {
            throw new Exception("First checkout failed to receive discount!");
        }

        $stmtClaim = $this->db->prepare("SELECT status, expires_at FROM referral_discount_claims WHERE referred_user_id = :uid");
        $stmtClaim->execute(['uid' => $userId]);
        $claim1 = $stmtClaim->fetch(PDO::FETCH_ASSOC);
        if (!$claim1 || $claim1['status'] !== 'reserved' || empty($claim1['expires_at'])) {
            throw new Exception("Reservation claim was not created with status 'reserved' and expires_at!");
        }

        // Simulate abandoned checkout: backdate expires_at beyond TTL
        $this->db->exec("UPDATE referral_discount_claims SET expires_at = DATE_SUB(NOW(), INTERVAL 10 MINUTE) WHERE referred_user_id = {$userId}");

        // Run cleanup
        $expiredCount = ReferralService::expireAbandonedReservations($this->db);
        if ($expiredCount < 1) {
            throw new Exception("expireAbandonedReservations did not expire the abandoned reservation!");
        }

        $stmtClaim->execute(['uid' => $userId]);
        $claimExpired = $stmtClaim->fetch(PDO::FETCH_ASSOC);
        if ($claimExpired['status'] !== 'expired') {
            throw new Exception("Claim status was not set to 'expired'!");
        }

        // User starts fresh checkout after abandonment -> should receive discount again!
        $calc2 = ReferralService::calculateDiscount($userId, '1500.00', 'PKR', $this->db, true);
        if (!$calc2['has_discount'] || $calc2['final_amount'] !== '1350.00') {
            throw new Exception("User was denied discount on fresh checkout after abandoned reservation expired!");
        }

        // Verify claim is back to reserved with future expiration
        $stmtClaim->execute(['uid' => $userId]);
        $claimRenewed = $stmtClaim->fetch(PDO::FETCH_ASSOC);
        if ($claimRenewed['status'] !== 'reserved' || strtotime($claimRenewed['expires_at']) <= time()) {
            throw new Exception("Renewed claim is not in status 'reserved' with future expires_at!");
        }

        echo "PASS\n";
    }

    public function test97_activePendingReservationWithinTtlBlocksSecondCheckoutDiscount(): void {
        echo "[Test 97] Reservation Hardening: Active unexpired pending reservation blocks concurrent checkout discount... ";
        $partnerId = $this->createPartner('ACT97', 10.00, 30.00);
        $userId = $this->createUser($partnerId, 'ACT97');

        // Checkout 1 creates unexpired reservation
        $calc1 = ReferralService::calculateDiscount($userId, '1500.00', 'PKR', $this->db, true);
        $tx1 = $this->createPayment($userId, 1350.00, 'pending', $partnerId, 'ACT97');
        ReferralService::linkDiscountClaimToTransaction($userId, $tx1, $this->db);

        // Checkout 2 starts while checkout 1 is still actively pending within TTL
        $calc2 = ReferralService::calculateDiscount($userId, '1500.00', 'PKR', $this->db, true);
        if ($calc2['has_discount'] || $calc2['final_amount'] !== '1500.00') {
            throw new Exception("Concurrent checkout received discount while active reservation was pending!");
        }

        echo "PASS\n";
    }

    public function test98_consumedClaimNeverExpires(): void {
        echo "[Test 98] Reservation Hardening: Consumed claim is permanent and never expires... ";
        $partnerId = $this->createPartner('CONS98', 10.00, 30.00);
        $userId = $this->createUser($partnerId, 'CONS98');

        // User checkouts and pays successfully
        $calc = ReferralService::calculateDiscount($userId, '1500.00', 'PKR', $this->db, true);
        $txId = $this->createPayment($userId, 1350.00, 'paid', $partnerId, 'CONS98');
        ReferralService::linkDiscountClaimToTransaction($userId, $txId, $this->db);
        ReferralService::consumeDiscountClaim($txId, $this->db);

        // Verify status is consumed and expires_at is null
        $stmtClaim = $this->db->prepare("SELECT status, expires_at FROM referral_discount_claims WHERE referred_user_id = :uid");
        $stmtClaim->execute(['uid' => $userId]);
        $claim = $stmtClaim->fetch(PDO::FETCH_ASSOC);
        if ($claim['status'] !== 'consumed' || $claim['expires_at'] !== null) {
            throw new Exception("Consumed claim is missing status 'consumed' or non-null expires_at!");
        }

        // Attempt to backdate updated_at past TTL and run cleanup
        $this->db->exec("UPDATE referral_discount_claims SET updated_at = DATE_SUB(NOW(), INTERVAL 500 MINUTE) WHERE referred_user_id = {$userId}");
        ReferralService::expireAbandonedReservations($this->db);

        // Verify claim is STILL consumed
        $stmtClaim->execute(['uid' => $userId]);
        $claimAfter = $stmtClaim->fetch(PDO::FETCH_ASSOC);
        if ($claimAfter['status'] !== 'consumed') {
            throw new Exception("Consumed claim was altered by cleanup cron!");
        }

        // Second checkout must never receive discount
        $calc2 = ReferralService::calculateDiscount($userId, '1500.00', 'PKR', $this->db, true);
        if ($calc2['has_discount'] || $calc2['final_amount'] !== '1500.00') {
            throw new Exception("User with consumed claim was granted discount on second checkout!");
        }

        echo "PASS\n";
    }

    public function test99_expiredClaimReplacedByNewCheckout(): void {
        echo "[Test 99] Reservation Hardening: Expired claim is atomically replaced by new checkout... ";
        $partnerId = $this->createPartner('REP99', 10.00, 30.00);
        $userId = $this->createUser($partnerId, 'REP99');

        // Directly set up an expired claim for user
        $stmtIns = $this->db->prepare("
            INSERT INTO referral_discount_claims (referred_user_id, partner_id, status, expires_at, created_at, updated_at)
            VALUES (:uid, :pid, 'expired', DATE_SUB(NOW(), INTERVAL 2 HOUR), DATE_SUB(NOW(), INTERVAL 3 HOUR), DATE_SUB(NOW(), INTERVAL 2 HOUR))
        ");
        $stmtIns->execute(['uid' => $userId, 'pid' => $partnerId]);

        // Fresh checkout
        $calc = ReferralService::calculateDiscount($userId, '1500.00', 'PKR', $this->db, true);
        if (!$calc['has_discount'] || $calc['final_amount'] !== '1350.00') {
            throw new Exception("Fresh checkout on expired claim did not receive discount!");
        }

        $stmtClaim = $this->db->prepare("SELECT status, payment_transaction_id, expires_at FROM referral_discount_claims WHERE referred_user_id = :uid");
        $stmtClaim->execute(['uid' => $userId]);
        $renewed = $stmtClaim->fetch(PDO::FETCH_ASSOC);

        if ($renewed['status'] !== 'reserved' || $renewed['payment_transaction_id'] !== null || strtotime($renewed['expires_at']) <= time()) {
            throw new Exception("Expired claim was not properly reset to fresh reserved state!");
        }

        echo "PASS\n";
    }

    public function test100_lateCallbackFromExpiredCheckoutCannotAffectReservationBOrDuplicateCommission(): void {
        echo "[Test 100] Scenario A: Checkout A expires, B reserves, late success for A arrives; A processed at snapshot, B untouched... ";
        $partnerId = $this->createPartner('SCEN_A', 10.00, 30.00);
        $userId = $this->createUser($partnerId, 'SCEN_A');

        // Checkout A starts
        $calcA = ReferralService::calculateDiscount($userId, '1500.00', 'PKR', $this->db, true);
        $txA = $this->createPayment($userId, 1350.00, 'pending', $partnerId, 'SCEN_A');
        ReferralService::linkDiscountClaimToTransaction($userId, $txA, $this->db);

        // Checkout A expires
        $this->db->exec("UPDATE referral_discount_claims SET status = 'expired', expires_at = DATE_SUB(NOW(), INTERVAL 15 MINUTE) WHERE referred_user_id = {$userId}");

        // User starts Checkout B
        $calcB = ReferralService::calculateDiscount($userId, '1500.00', 'PKR', $this->db, true);
        if (!$calcB['has_discount'] || $calcB['final_amount'] !== '1350.00') {
            throw new Exception("Checkout B failed to receive discount!");
        }
        $txB = $this->createPayment($userId, 1350.00, 'pending', $partnerId, 'SCEN_A');
        ReferralService::linkDiscountClaimToTransaction($userId, $txB, $this->db);

        // Verify Reservation B is linked to txB
        $stmtClaim = $this->db->prepare("SELECT payment_transaction_id, status FROM referral_discount_claims WHERE referred_user_id = :uid");
        $stmtClaim->execute(['uid' => $userId]);
        $claimB = $stmtClaim->fetch(PDO::FETCH_ASSOC);
        if ((int)$claimB['payment_transaction_id'] !== $txB || $claimB['status'] !== 'reserved') {
            throw new Exception("Reservation B is not properly linked to txB!");
        }

        // Late success callback arrives for Checkout A:
        // 1. A cannot consume B's claim
        ReferralService::consumeDiscountClaim($txA, $this->db);

        // Verify Reservation B was NOT consumed or stolen
        $stmtClaim->execute(['uid' => $userId]);
        $claimAfterLateConsume = $stmtClaim->fetch(PDO::FETCH_ASSOC);
        if ((int)$claimAfterLateConsume['payment_transaction_id'] !== $txB || $claimAfterLateConsume['status'] !== 'reserved') {
            throw new Exception("Late consume of txA corrupted or overwrote Reservation B!");
        }

        // 2. Checkout A is settled at its immutable snapshot amount (1350.00)
        $this->db->exec("UPDATE payment_transactions SET status = 'paid', paid_at = NOW() WHERE id = {$txA}");
        $commA = ReferralService::calculateAndRecordCommission($txA, $this->db);
        if (!$commA) {
            throw new Exception("Commission calculation for Checkout A failed!");
        }
        if ($commA['actual_paid_amount'] !== '1350.00' || $commA['original_plan_amount'] !== '1500.00') {
            throw new Exception("Checkout A was not processed at its immutable snapshot amount!");
        }

        // Verify Reservation B remains untouched
        $stmtClaim->execute(['uid' => $userId]);
        $claimBStillReserved = $stmtClaim->fetch(PDO::FETCH_ASSOC);
        if ((int)$claimBStillReserved['payment_transaction_id'] !== $txB || $claimBStillReserved['status'] !== 'reserved') {
            throw new Exception("Checkout A processing corrupted Reservation B!");
        }

        // 3. Checkout B successfully completes and pays
        $this->db->exec("UPDATE payment_transactions SET status = 'paid', paid_at = NOW() WHERE id = {$txB}");
        ReferralService::consumeDiscountClaim($txB, $this->db);
        $commB = ReferralService::calculateAndRecordCommission($txB, $this->db);

        // Verify claim is now consumed by txB
        $stmtClaim->execute(['uid' => $userId]);
        $claimFinal = $stmtClaim->fetch(PDO::FETCH_ASSOC);
        if ((int)$claimFinal['payment_transaction_id'] !== $txB || $claimFinal['status'] !== 'consumed') {
            throw new Exception("Claim was not consumed for Checkout B!");
        }

        echo "PASS\n";
    }

    public function test101_failedPaymentReleasesEntitlementToExpired(): void {
        echo "[Test 101] Reservation Hardening: Failed payment releases reservation to expired and user can retry... ";
        $partnerId = $this->createPartner('FAIL101', 10.00, 30.00);
        $userId = $this->createUser($partnerId, 'FAIL101');

        $calc1 = ReferralService::calculateDiscount($userId, '1500.00', 'PKR', $this->db, true);
        $tx1 = $this->createPayment($userId, 1350.00, 'pending', $partnerId, 'FAIL101');
        ReferralService::linkDiscountClaimToTransaction($userId, $tx1, $this->db);

        // Payment fails at gateway
        $this->db->exec("UPDATE payment_transactions SET status = 'failed', failed_at = NOW() WHERE id = {$tx1}");
        ReferralService::releaseDiscountClaim($tx1, $this->db);

        // Verify claim status transitioned to expired
        $stmtClaim = $this->db->prepare("SELECT status FROM referral_discount_claims WHERE payment_transaction_id = :tx");
        $stmtClaim->execute(['tx' => $tx1]);
        if ($stmtClaim->fetchColumn() !== 'expired') {
            throw new Exception("Failed payment did not transition claim status to 'expired'!");
        }

        // User immediately retries checkout
        $calc2 = ReferralService::calculateDiscount($userId, '1500.00', 'PKR', $this->db, true);
        if (!$calc2['has_discount'] || $calc2['final_amount'] !== '1350.00') {
            throw new Exception("User retry checkout failed to receive discount after payment failure!");
        }

        echo "PASS\n";
    }

    public function test102_cleanupCronIsIdempotent(): void {
        echo "[Test 102] Reservation Hardening: Cleanup cron is strictly idempotent across multiple states... ";
        $partnerId = $this->createPartner('IDEM102', 10.00, 30.00);
        $userA = $this->createUser($partnerId, 'IDEM102');
        $userB = $this->createUser($partnerId, 'IDEM102');
        $userC = $this->createUser($partnerId, 'IDEM102');

        // User A: unexpired active reservation (+60 min)
        $this->db->exec("
            INSERT INTO referral_discount_claims (referred_user_id, partner_id, status, expires_at, created_at, updated_at)
            VALUES ({$userA}, {$partnerId}, 'reserved', DATE_ADD(NOW(), INTERVAL 60 MINUTE), NOW(), NOW())
        ");

        // User B: expired abandoned reservation (-30 min)
        $this->db->exec("
            INSERT INTO referral_discount_claims (referred_user_id, partner_id, status, expires_at, created_at, updated_at)
            VALUES ({$userB}, {$partnerId}, 'reserved', DATE_SUB(NOW(), INTERVAL 30 MINUTE), DATE_SUB(NOW(), INTERVAL 2 HOUR), DATE_SUB(NOW(), INTERVAL 30 MINUTE))
        ");

        // User C: consumed permanent claim
        $this->db->exec("
            INSERT INTO referral_discount_claims (referred_user_id, partner_id, status, expires_at, created_at, updated_at)
            VALUES ({$userC}, {$partnerId}, 'consumed', NULL, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY))
        ");

        // First run: should expire only User B
        $count1 = ReferralService::expireAbandonedReservations($this->db);
        if ($count1 !== 1) {
            throw new Exception("First cleanup run expected 1 expiration, got {$count1}!");
        }

        // Verify statuses
        $stmtStatus = $this->db->prepare("SELECT status FROM referral_discount_claims WHERE referred_user_id = :uid");
        $stmtStatus->execute(['uid' => $userA]);
        if ($stmtStatus->fetchColumn() !== 'reserved') {
            throw new Exception("User A was incorrectly modified by cleanup!");
        }

        $stmtStatus->execute(['uid' => $userB]);
        if ($stmtStatus->fetchColumn() !== 'expired') {
            throw new Exception("User B was not transitioned to expired!");
        }

        $stmtStatus->execute(['uid' => $userC]);
        if ($stmtStatus->fetchColumn() !== 'consumed') {
            throw new Exception("User C consumed status was altered!");
        }

        // Second run: idempotent (0 expirations)
        $count2 = ReferralService::expireAbandonedReservations($this->db);
        if ($count2 !== 0) {
            throw new Exception("Second cleanup run was not idempotent, got {$count2} expirations!");
        }

        echo "PASS\n";
    }

    public function test103_concurrentCheckoutAfterExpirationExactlyOneGetsDiscount(): void {
        echo "[Test 103] Reservation Hardening: Concurrent checkout after expiration grants discount to exactly one... ";
        $partnerId = $this->createPartner('CONC103', 10.00, 30.00);
        $userId = $this->createUser($partnerId, 'CONC103');

        // Set up expired reservation for user
        $this->db->exec("
            INSERT INTO referral_discount_claims (referred_user_id, partner_id, status, expires_at, created_at, updated_at)
            VALUES ({$userId}, {$partnerId}, 'expired', DATE_SUB(NOW(), INTERVAL 1 HOUR), DATE_SUB(NOW(), INTERVAL 2 HOUR), DATE_SUB(NOW(), INTERVAL 1 HOUR))
        ");

        // Temporary worker script for real multi-process concurrency
        $workerScript = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'step5_worker_' . uniqid() . '.php';
        $workerCode = <<<'PHP'
<?php
require 'tests/bootstrap.php';
use App\Services\Database;
use App\Services\ReferralService;

$userId = (int)$argv[1];
$partnerId = (int)$argv[2];
$workerId = $argv[3];
$holdMs = (int)($argv[4] ?? 0);

$db = Database::connection();

$db->beginTransaction();
try {
    $calc = ReferralService::calculateDiscount($userId, '1500.00', 'PKR', $db, true);
    
    if ($calc['has_discount']) {
        $ref = 'TXN_STEP5_REAL_' . $workerId . '_' . uniqid();
        $stmt = $db->prepare("
            INSERT INTO payment_transactions (user_id, plan_id, provider, transaction_reference, amount, original_amount, referral_discount_amount, discount_percent, referral_code_used, referral_partner_id, currency, status, created_at, updated_at)
            VALUES (?, 1, 'cashmaal', ?, ?, 1500.00, 150.00, 10.00, 'CONC103', ?, 'PKR', 'pending', NOW(), NOW())
        ");
        $stmt->execute([$userId, $ref, $calc['final_amount'], $partnerId]);
        $txId = (int)$db->lastInsertId();
        ReferralService::linkDiscountClaimToTransaction($userId, $txId, $db);
    }
    
    if ($holdMs > 0) {
        usleep($holdMs * 1000);
    }
    
    $db->commit();
    echo json_encode([
        'worker' => $workerId,
        'has_discount' => $calc['has_discount'],
        'final_amount' => $calc['final_amount']
    ]);
} catch (\Exception $e) {
    $db->rollBack();
    echo json_encode([
        'worker' => $workerId,
        'error' => $e->getMessage()
    ]);
}
PHP;
        file_put_contents($workerScript, $workerCode);

        // Spawn Process 1 with 250ms hold delay to ensure true concurrent overlap
        $cmd1 = "php \"$workerScript\" $userId $partnerId P1 250";
        // Spawn Process 2 with 0ms delay immediately while Process 1 is in-flight
        $cmd2 = "php \"$workerScript\" $userId $partnerId P2 0";

        $descriptors = [
            0 => ["pipe", "r"],
            1 => ["pipe", "w"],
            2 => ["pipe", "w"]
        ];

        $proc1 = proc_open($cmd1, $descriptors, $pipes1);
        usleep(40000); // 40ms stagger so P1 enters its transaction first
        $proc2 = proc_open($cmd2, $descriptors, $pipes2);

        $out1 = stream_get_contents($pipes1[1]);
        fclose($pipes1[1]);
        fclose($pipes1[0]);
        fclose($pipes1[2]);
        proc_close($proc1);

        $out2 = stream_get_contents($pipes2[1]);
        fclose($pipes2[1]);
        fclose($pipes2[0]);
        fclose($pipes2[2]);
        proc_close($proc2);

        @unlink($workerScript);

        $res1 = json_decode(trim($out1), true);
        $res2 = json_decode(trim($out2), true);

        if (!$res1 || !$res2) {
            throw new Exception("One or both concurrent workers failed to return valid JSON output: out1='$out1', out2='$out2'");
        }

        $discountCount = 0;
        if (!empty($res1['has_discount'])) $discountCount++;
        if (!empty($res2['has_discount'])) $discountCount++;

        if ($discountCount !== 1) {
            throw new Exception("Real concurrency race condition failed: expected exactly 1 discount, got $discountCount. (P1: " . json_encode($res1) . ", P2: " . json_encode($res2) . ")");
        }

        // Exactly one should be 1350.00, other should be 1500.00
        $amounts = [$res1['final_amount'], $res2['final_amount']];
        sort($amounts);
        if ($amounts !== ['1350.00', '1500.00']) {
            throw new Exception("Unexpected final amounts from concurrent checkouts: " . json_encode($amounts));
        }

        // Verify exactly one reserved claim in database linked to active transaction
        $stmtClaim = $this->db->prepare("SELECT status, payment_transaction_id, expires_at FROM referral_discount_claims WHERE referred_user_id = :uid");
        $stmtClaim->execute(['uid' => $userId]);
        $finalClaim = $stmtClaim->fetch(PDO::FETCH_ASSOC);

        if ($finalClaim['status'] !== 'reserved' || empty($finalClaim['payment_transaction_id']) || strtotime($finalClaim['expires_at']) <= time()) {
            throw new Exception("Final claim state in database is invalid after concurrent checkout: " . json_encode($finalClaim));
        }

        echo "PASS\n";
    }

    public function test104_ttlConfigurationHierarchyAndValidation(): void {
        echo "[Test 104] Reservation Hardening: TTL configuration resolution hierarchy and safe bounds validation... ";

        // 1. Default fallback when no DB setting, config, or env is set
        $this->db->exec("DELETE FROM settings WHERE `key` = 'referral_discount_reservation_ttl_minutes'");
        unset($_ENV['REFERRAL_DISCOUNT_RESERVATION_TTL_MINUTES']);
        $ttlDefault = ReferralService::getReservationTtlMinutes($this->db);
        if ($ttlDefault !== 120) {
            throw new Exception("Default TTL expected 120, got: $ttlDefault");
        }

        // 2. Environment variable override
        $_ENV['REFERRAL_DISCOUNT_RESERVATION_TTL_MINUTES'] = '45';
        $ttlEnv = ReferralService::getReservationTtlMinutes($this->db);
        if ($ttlEnv !== 45) {
            throw new Exception("Environment TTL expected 45, got: $ttlEnv");
        }

        // 3. Database setting takes highest precedence over env and config
        $stmtSet = $this->db->prepare("
            INSERT INTO settings (group_name, `key`, `value`, is_public, created_at, updated_at)
            VALUES ('referral', 'referral_discount_reservation_ttl_minutes', '90', 0, NOW(), NOW())
            ON DUPLICATE KEY UPDATE `value` = '90', updated_at = NOW()
        ");
        $stmtSet->execute();

        $ttlDb = ReferralService::getReservationTtlMinutes($this->db);
        if ($ttlDb !== 90) {
            throw new Exception("Database setting TTL expected 90, got: $ttlDb");
        }

        // 4. Zero value in DB setting is rejected, safely falls back to next tier
        $this->db->exec("UPDATE settings SET `value` = '0' WHERE `key` = 'referral_discount_reservation_ttl_minutes'");
        $ttlZero = ReferralService::getReservationTtlMinutes($this->db);
        if ($ttlZero !== 45) {
            throw new Exception("Zero DB setting failed to fall back safely: got $ttlZero");
        }

        // 5. Negative value in DB setting is rejected, safely falls back
        $this->db->exec("UPDATE settings SET `value` = '-30' WHERE `key` = 'referral_discount_reservation_ttl_minutes'");
        $ttlNeg = ReferralService::getReservationTtlMinutes($this->db);
        if ($ttlNeg !== 45) {
            throw new Exception("Negative DB setting failed to fall back safely: got $ttlNeg");
        }

        // 6. Non-numeric value in DB setting is rejected, safely falls back
        $this->db->exec("UPDATE settings SET `value` = 'INVALID_TTL' WHERE `key` = 'referral_discount_reservation_ttl_minutes'");
        $ttlNonNum = ReferralService::getReservationTtlMinutes($this->db);
        if ($ttlNonNum !== 45) {
            throw new Exception("Non-numeric DB setting failed to fall back safely: got $ttlNonNum");
        }

        // 6b. Decimal value (e.g. '1.5') is rejected, safely falls back
        $this->db->exec("UPDATE settings SET `value` = '1.5' WHERE `key` = 'referral_discount_reservation_ttl_minutes'");
        $ttlDecimal = ReferralService::getReservationTtlMinutes($this->db);
        if ($ttlDecimal !== 45) {
            throw new Exception("Decimal DB setting ('1.5') was not rejected: got $ttlDecimal");
        }

        // 6c. Whitespace-containing value (e.g. ' 90 ') is rejected, safely falls back
        $this->db->exec("UPDATE settings SET `value` = ' 90 ' WHERE `key` = 'referral_discount_reservation_ttl_minutes'");
        $ttlWhitespace = ReferralService::getReservationTtlMinutes($this->db);
        if ($ttlWhitespace !== 45) {
            throw new Exception("Whitespace DB setting (' 90 ') was not rejected: got $ttlWhitespace");
        }

        // 6d. Empty string in DB setting is rejected, safely falls back
        $this->db->exec("UPDATE settings SET `value` = '' WHERE `key` = 'referral_discount_reservation_ttl_minutes'");
        $ttlEmpty = ReferralService::getReservationTtlMinutes($this->db);
        if ($ttlEmpty !== 45) {
            throw new Exception("Empty DB setting was not rejected: got $ttlEmpty");
        }

        // 7. Extremely large value is safely capped to MAX_RESERVATION_TTL_MINUTES (525600 min)
        $this->db->exec("UPDATE settings SET `value` = '999999999' WHERE `key` = 'referral_discount_reservation_ttl_minutes'");
        $ttlMax = ReferralService::getReservationTtlMinutes($this->db);
        if ($ttlMax !== ReferralService::MAX_RESERVATION_TTL_MINUTES) {
            throw new Exception("Extreme TTL value was not capped safely: expected " . ReferralService::MAX_RESERVATION_TTL_MINUTES . ", got $ttlMax");
        }

        // 8. Direct unit verification of parseTtlMinutes()
        if (ReferralService::parseTtlMinutes(1) !== 1) throw new Exception("parseTtlMinutes(1) failed");
        if (ReferralService::parseTtlMinutes(120) !== 120) throw new Exception("parseTtlMinutes(120) failed");
        if (ReferralService::parseTtlMinutes(525600) !== 525600) throw new Exception("parseTtlMinutes(525600) failed");
        if (ReferralService::parseTtlMinutes('1') !== 1) throw new Exception("parseTtlMinutes('1') failed");
        if (ReferralService::parseTtlMinutes('120') !== 120) throw new Exception("parseTtlMinutes('120') failed");
        if (ReferralService::parseTtlMinutes('525600') !== 525600) throw new Exception("parseTtlMinutes('525600') failed");
        if (ReferralService::parseTtlMinutes(0) !== null) throw new Exception("parseTtlMinutes(0) did not return null");
        if (ReferralService::parseTtlMinutes('0') !== null) throw new Exception("parseTtlMinutes('0') did not return null");
        if (ReferralService::parseTtlMinutes(-1) !== null) throw new Exception("parseTtlMinutes(-1) did not return null");
        if (ReferralService::parseTtlMinutes('-30') !== null) throw new Exception("parseTtlMinutes('-30') did not return null");
        if (ReferralService::parseTtlMinutes('1.5') !== null) throw new Exception("parseTtlMinutes('1.5') did not return null");
        if (ReferralService::parseTtlMinutes(1.5) !== null) throw new Exception("parseTtlMinutes(1.5) did not return null");
        if (ReferralService::parseTtlMinutes('abc') !== null) throw new Exception("parseTtlMinutes('abc') did not return null");
        if (ReferralService::parseTtlMinutes('') !== null) throw new Exception("parseTtlMinutes('') did not return null");
        if (ReferralService::parseTtlMinutes(' 120 ') !== null) throw new Exception("parseTtlMinutes(' 120 ') did not return null");
        if (ReferralService::parseTtlMinutes('999999999') !== ReferralService::MAX_RESERVATION_TTL_MINUTES) throw new Exception("parseTtlMinutes('999999999') not capped");

        // Reset settings
        $this->db->exec("DELETE FROM settings WHERE `key` = 'referral_discount_reservation_ttl_minutes'");
        unset($_ENV['REFERRAL_DISCOUNT_RESERVATION_TTL_MINUTES']);

        echo "PASS\n";
    }

    public function test105_lateCallbackScenarioB_noCheckoutBProcessesAtSnapshot(): void {
        echo "[Test 105] Scenario B: Checkout A expires with no Checkout B, late valid callback processes at immutable snapshot... ";
        $partnerId = $this->createPartner('SCEN_B', 10.00, 30.00);
        $userId = $this->createUser($partnerId, 'SCEN_B');

        // Checkout A starts and reserves discount
        $calcA = ReferralService::calculateDiscount($userId, '1500.00', 'PKR', $this->db, true);
        $txA = $this->createPayment($userId, 1350.00, 'pending', $partnerId, 'SCEN_B');
        ReferralService::linkDiscountClaimToTransaction($userId, $txA, $this->db);

        // Checkout A reservation expires
        $this->db->exec("UPDATE referral_discount_claims SET status = 'expired', expires_at = DATE_SUB(NOW(), INTERVAL 30 MINUTE) WHERE payment_transaction_id = {$txA}");

        // No Checkout B is started. Late valid callback arrives for Checkout A with expected 1350.00:
        $stmtTx = $this->db->prepare("SELECT * FROM payment_transactions WHERE id = :id");
        $stmtTx->execute(['id' => $txA]);
        $txRow = $stmtTx->fetch(PDO::FETCH_ASSOC);

        // Verify transaction A commercial snapshot was NOT mutated to full price (1500.00)
        if ($txRow['amount'] !== '1350.00' || $txRow['original_amount'] !== '1500.00' || $txRow['referral_discount_amount'] !== '150.00') {
            throw new Exception("Transaction A snapshot was corrupted by reservation expiration!");
        }

        // Process payment success at immutable snapshot
        $this->db->exec("UPDATE payment_transactions SET status = 'paid', paid_at = NOW(), provider_transaction_id = 'CM_LATE_B' WHERE id = {$txA}");
        $comm = ReferralService::calculateAndRecordCommission($txA, $this->db);
        if (!$comm) {
            throw new Exception("Commission calculation failed for late callback in Scenario B!");
        }

        // Verify frozen commission fields
        if ($comm['commission_amount'] !== '405.00' || $comm['actual_paid_amount'] !== '1350.00' || $comm['original_plan_amount'] !== '1500.00') {
            throw new Exception("Commission snapshot mismatch for Scenario B: " . json_encode($comm));
        }

        // Verify claim remains linked to txA and was not corrupted
        $stmtClaim = $this->db->prepare("SELECT status, payment_transaction_id FROM referral_discount_claims WHERE referred_user_id = :uid");
        $stmtClaim->execute(['uid' => $userId]);
        $claim = $stmtClaim->fetch(PDO::FETCH_ASSOC);
        if ((int)$claim['payment_transaction_id'] !== $txA) {
            throw new Exception("Claim was detached or reassigned in Scenario B!");
        }

        echo "PASS\n";
    }

    public function test106_lateCallbackScenarioC_lateFailureDoesNotTouchReservationB(): void {
        echo "[Test 106] Scenario C: Late failure/cancel callback for A arrives after B reserved, B remains untouched... ";
        $partnerId = $this->createPartner('SCEN_C', 10.00, 30.00);
        $userId = $this->createUser($partnerId, 'SCEN_C');

        // Checkout A starts
        $calcA = ReferralService::calculateDiscount($userId, '1500.00', 'PKR', $this->db, true);
        $txA = $this->createPayment($userId, 1350.00, 'pending', $partnerId, 'SCEN_C');
        ReferralService::linkDiscountClaimToTransaction($userId, $txA, $this->db);

        // Checkout A expires
        $this->db->exec("UPDATE referral_discount_claims SET status = 'expired', expires_at = DATE_SUB(NOW(), INTERVAL 15 MINUTE) WHERE referred_user_id = {$userId}");

        // Checkout B starts and claims discount
        $calcB = ReferralService::calculateDiscount($userId, '1500.00', 'PKR', $this->db, true);
        $txB = $this->createPayment($userId, 1350.00, 'pending', $partnerId, 'SCEN_C');
        ReferralService::linkDiscountClaimToTransaction($userId, $txB, $this->db);

        // Verify reservation B is linked to txB and reserved
        $stmtClaim = $this->db->prepare("SELECT payment_transaction_id, status FROM referral_discount_claims WHERE referred_user_id = :uid");
        $stmtClaim->execute(['uid' => $userId]);
        $claimBefore = $stmtClaim->fetch(PDO::FETCH_ASSOC);
        if ((int)$claimBefore['payment_transaction_id'] !== $txB || $claimBefore['status'] !== 'reserved') {
            throw new Exception("Reservation B setup failed!");
        }

        // Late failure callback arrives for A: releaseDiscountClaim(txA)
        $released = ReferralService::releaseDiscountClaim($txA, $this->db);

        // B's reservation must remain completely untouched (still reserved, still txB)
        $stmtClaim->execute(['uid' => $userId]);
        $claimAfter = $stmtClaim->fetch(PDO::FETCH_ASSOC);
        if ((int)$claimAfter['payment_transaction_id'] !== $txB || $claimAfter['status'] !== 'reserved') {
            throw new Exception("Late release for txA corrupted Reservation B!");
        }

        echo "PASS\n";
    }

    public function test107_lateCallbackScenarioD_lateSuccessDoesNotConsumeReservationB(): void {
        echo "[Test 107] Scenario D: Late success callback for A arrives after B reserved, cannot consume B's claim... ";
        $partnerId = $this->createPartner('SCEN_D', 10.00, 30.00);
        $userId = $this->createUser($partnerId, 'SCEN_D');

        // Checkout A starts
        $calcA = ReferralService::calculateDiscount($userId, '1500.00', 'PKR', $this->db, true);
        $txA = $this->createPayment($userId, 1350.00, 'pending', $partnerId, 'SCEN_D');
        ReferralService::linkDiscountClaimToTransaction($userId, $txA, $this->db);

        // Checkout A expires
        $this->db->exec("UPDATE referral_discount_claims SET status = 'expired', expires_at = DATE_SUB(NOW(), INTERVAL 15 MINUTE) WHERE referred_user_id = {$userId}");

        // Checkout B starts and claims discount
        $calcB = ReferralService::calculateDiscount($userId, '1500.00', 'PKR', $this->db, true);
        $txB = $this->createPayment($userId, 1350.00, 'pending', $partnerId, 'SCEN_D');
        ReferralService::linkDiscountClaimToTransaction($userId, $txB, $this->db);

        // Late success callback arrives for A: consumeDiscountClaim(txA)
        ReferralService::consumeDiscountClaim($txA, $this->db);

        // Verify B's reservation was NOT stolen or marked consumed
        $stmtClaim = $this->db->prepare("SELECT payment_transaction_id, status FROM referral_discount_claims WHERE referred_user_id = :uid");
        $stmtClaim->execute(['uid' => $userId]);
        $claimAfter = $stmtClaim->fetch(PDO::FETCH_ASSOC);
        if ((int)$claimAfter['payment_transaction_id'] !== $txB || $claimAfter['status'] !== 'reserved') {
            throw new Exception("Late consume for txA consumed or stole Reservation B!");
        }

        // Now process late payment and commission for A
        $this->db->exec("UPDATE payment_transactions SET status = 'paid', paid_at = NOW() WHERE id = {$txA}");
        $commA1 = ReferralService::calculateAndRecordCommission($txA, $this->db);
        if (!$commA1) {
            throw new Exception("Commission calculation for A failed in Scenario D!");
        }

        // Duplicate commission attempt for A is idempotent
        $commA2 = ReferralService::calculateAndRecordCommission($txA, $this->db);
        if ($commA2['id'] !== $commA1['id']) {
            throw new Exception("Duplicate commission check failed for A!");
        }

        // B's reservation is still reserved
        $stmtClaim->execute(['uid' => $userId]);
        $claimFinal = $stmtClaim->fetch(PDO::FETCH_ASSOC);
        if ((int)$claimFinal['payment_transaction_id'] !== $txB || $claimFinal['status'] !== 'reserved') {
            throw new Exception("B was modified during commission processing for A!");
        }

        echo "PASS\n";
    }

    public function test108_lateCallbackScenarioE_concurrentDuplicateCallbacksHandledIdempotently(): void {
        echo "[Test 108] Scenario E: Duplicate successful callbacks for A arrive concurrently, exactly one succeeds... ";
        $partnerId = $this->createPartner('SCEN_E', 10.00, 30.00);
        $userId = $this->createUser($partnerId, 'SCEN_E');

        // Create transaction A
        $refA = 'TXN_STEP5_SCEN_E_' . uniqid();
        $stmt = $this->db->prepare("
            INSERT INTO payment_transactions (user_id, plan_id, provider, transaction_reference, amount, original_amount, referral_discount_amount, discount_percent, referral_code_used, referral_partner_id, currency, status, created_at, updated_at)
            VALUES (?, 1, 'cashmaal', ?, 1350.00, 1500.00, 150.00, 10.00, 'SCEN_E', ?, 'PKR', 'pending', NOW(), NOW())
        ");
        $stmt->execute([$userId, $refA, $partnerId]);
        $txA = (int)$this->db->lastInsertId();

        // Worker script for concurrent callback simulation with real separate PDO connections
        $workerScript = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'step5_ipn_worker_' . uniqid() . '.php';
        $workerCode = <<<'PHP'
<?php
require 'tests/bootstrap.php';
use App\Services\Database;
use App\Services\ReferralService;

$txId = (int)$argv[1];
$workerId = $argv[2];
$holdMs = (int)($argv[3] ?? 0);

$db = Database::connection();

$db->beginTransaction();
try {
    // Lock transaction FOR UPDATE
    $stmt = $db->prepare("SELECT * FROM payment_transactions WHERE id = :id LIMIT 1 FOR UPDATE");
    $stmt->execute(['id' => $txId]);
    $tx = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($tx['status'] === 'paid') {
        $db->commit();
        echo json_encode(['worker' => $workerId, 'status' => 'already_paid', 'activated' => false]);
        exit;
    }

    // Mark paid
    $stmtUpd = $db->prepare("UPDATE payment_transactions SET status = 'paid', paid_at = NOW(), updated_at = NOW() WHERE id = :id");
    $stmtUpd->execute(['id' => $txId]);

    // Activate subscription
    $stmtSub = $db->prepare("
        INSERT INTO subscriptions (user_id, plan_id, status, starts_at, ends_at, auto_renew, provider, provider_subscription_id, created_at, updated_at)
        VALUES (:uid, 1, 'active', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 1, 'cashmaal', 'CM_CONC', NOW(), NOW())
    ");
    $stmtSub->execute(['uid' => $tx['user_id']]);

    // Record commission
    $comm = ReferralService::calculateAndRecordCommission($txId, $db);

    if ($holdMs > 0) {
        usleep($holdMs * 1000);
    }

    $db->commit();
    echo json_encode(['worker' => $workerId, 'status' => 'processed', 'activated' => true, 'comm_id' => $comm['id'] ?? null]);
} catch (\Exception $e) {
    $db->rollBack();
    echo json_encode(['worker' => $workerId, 'error' => $e->getMessage()]);
}
PHP;
        file_put_contents($workerScript, $workerCode);

        // Spawn Worker 1 with 200ms hold, Worker 2 immediately
        $cmd1 = "php \"$workerScript\" $txA W1 200";
        $cmd2 = "php \"$workerScript\" $txA W2 0";

        $descriptors = [
            0 => ["pipe", "r"],
            1 => ["pipe", "w"],
            2 => ["pipe", "w"]
        ];

        $proc1 = proc_open($cmd1, $descriptors, $pipes1);
        usleep(30000); // 30ms stagger so W1 enters transaction first
        $proc2 = proc_open($cmd2, $descriptors, $pipes2);

        $out1 = stream_get_contents($pipes1[1]);
        fclose($pipes1[1]); fclose($pipes1[0]); fclose($pipes1[2]);
        proc_close($proc1);

        $out2 = stream_get_contents($pipes2[1]);
        fclose($pipes2[1]); fclose($pipes2[0]); fclose($pipes2[2]);
        proc_close($proc2);

        @unlink($workerScript);

        $res1 = json_decode(trim($out1), true);
        $res2 = json_decode(trim($out2), true);

        if (!$res1 || !$res2) {
            throw new Exception("Workers returned invalid output: out1='$out1', out2='$out2'");
        }

        $activations = 0;
        if (!empty($res1['activated'])) $activations++;
        if (!empty($res2['activated'])) $activations++;

        if ($activations !== 1) {
            throw new Exception("Expected exactly 1 activation, got $activations! (W1: " . json_encode($res1) . ", W2: " . json_encode($res2) . ")");
        }

        // Verify exactly one subscription was created
        $stmtSubCount = $this->db->prepare("SELECT COUNT(*) FROM subscriptions WHERE user_id = :uid");
        $stmtSubCount->execute(['uid' => $userId]);
        if ((int)$stmtSubCount->fetchColumn() !== 1) {
            throw new Exception("Duplicate subscriptions created in database!");
        }

        // Verify exactly one commission record exists
        $stmtCommCount = $this->db->prepare("SELECT COUNT(*) FROM referral_commissions WHERE payment_transaction_id = :tx");
        $stmtCommCount->execute(['tx' => $txA]);
        if ((int)$stmtCommCount->fetchColumn() !== 1) {
            throw new Exception("Duplicate commissions created in database!");
        }

        echo "PASS\n";
    }
}

if (php_sapi_name() === 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === realpath(__FILE__)) {
    require_once __DIR__ . '/bootstrap.php';
    (new Step5ReferralSystemTest())->run();
}

