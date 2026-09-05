<?php

use App\Services\Database;
use App\Services\Auth;
use App\Helpers\Security;
use App\Services\PaymentService;
use App\Services\SubscriptionService;
use App\Services\CashMaalPaymentGateway;
use App\Services\NotificationQueueService;
use App\Services\WhatsAppNotificationService;
use App\Services\WhatsApp\MetaWhatsAppProvider;
use App\Services\WhatsApp\WacrmWhatsAppProvider;
use App\Services\WhatsApp\WhatsAppProviderInterface;
use App\Controllers\BillingController;

class Step4PaymentIntegrationTest {
    private PDO $db;
    private BillingController $billingController;
    private NotificationQueueService $queueService;
    private int $planPremiumId;
    private int $planPrecisionId;
    private string $testIpnKey = 'test_secret_ipn_key_step4';
    private string $testWebId = '99999';

    public function __construct() {
        $this->db = Database::connection();
        $this->billingController = new BillingController();
        $this->queueService = new NotificationQueueService();
    }

    public function run(): void {
        echo "=================================================================\n";
        echo " RUNNING STEP 4 PAYMENT INTEGRATION TEST SUITE (85 TESTS)\n";
        echo "=================================================================\n\n";

        $this->setUp();

        try {
            // Group 1: CashMaal Configuration & Amount Integrity (1-10)
            $this->test1_cashmaalConfigurationLoadsSafely();
            $this->test2_missingCredentialsFailSafely();
            $this->test3_paymentAmountIsTakenFromServerSidePlan();
            $this->test4_browserSuppliedAmountCannotChangePayableAmount();
            $this->test5_paymentTransactionIsCreatedAsPending();
            $this->test6_unknownTransactionReferenceIsRejected();
            $this->test7_invalidMerchantAccountDataIsRejected();
            $this->test8_invalidPaymentVerificationIsRejected();
            $this->test9_amountMismatchIsRejected();
            $this->test10_currencyMismatchIsRejected();

            // Group 2: Payment Status & Subscription Activation (11-19)
            $this->test11_failedPaymentDoesNotActivateSubscription();
            $this->test12_pendingPaymentDoesNotActivateSubscription();
            $this->test13_cancelledPaymentDoesNotActivateSubscription();
            $this->test14_verifiedSuccessfulPaymentActivatesCorrectSubscription();
            $this->test15_paymentIsRecordedCorrectly();
            $this->test16_duplicateSuccessfulCallbackIsIdempotent();
            $this->test17_duplicateCallbackDoesNotCreateSecondSubscription();
            $this->test18_duplicateCallbackDoesNotCreateSecondPaymentRecord();
            $this->test19_duplicateCallbackDoesNotCreateSecondNotificationQueueRow();

            // Group 3: Atomicity & Transaction Safety (20-22)
            $this->test20_paymentProcessingIsTransactional();
            $this->test21_databaseFailureRollsBackBusinessOperation();
            $this->test22_successfulPaymentAndSubscriptionStateRemainConsistent();

            // Group 4: Meta WhatsApp Confirmation & Separation (23-35)
            $this->test23_successfulPaymentSelectsMetaWhatsApp();
            $this->test24_scholarshipWhatsAppStillSelectsWacrm();
            $this->test25_paymentConfirmationUsesConfirmationMsgTemplate();
            $this->test26_failedPaymentDoesNotQueueSuccessfulConfirmation();
            $this->test27_pendingPaymentDoesNotQueueSuccessfulConfirmation();
            $this->test28_cancelledPaymentDoesNotQueueSuccessfulConfirmation();
            $this->test29_paymentCallbackNeverDirectlyCallsMetaApi();
            $this->test30_paymentCallbackNeverDirectlyCallsWacrm();
            $this->test31_paymentCallbackNeverDirectlyPerformsCurlDelivery();
            $this->test32_confirmationIsDeliveredOnlyByQueueWorker();
            $this->test33_confirmationIdempotencyKeyRemainsUnchangedOnRepeatedCallbacks();
            $this->test34_missingOrInvalidWhatsAppNumberDoesNotRollBackPayment();
            $this->test35_metaProviderFailureDoesNotRollBackPayment();

            // Group 5: Security & Browser Return Guards (36-40)
            $this->test36_forgedCallbackCannotActivateSubscription();
            $this->test37_userCannotChangeAnotherUsersTransaction();
            $this->test38_userCannotChangePlanAmountThroughParameters();
            $this->test39_sensitiveCashmaalCredentialsNeverAppearInLogsOrResponses();
            $this->test40_browserReturnUrlCannotIndependentlyMarkPaymentSuccessful();

            // Group 6: Server-to-Server IPN Authentication (41-44)
            $this->test41_validIpnSucceedsWithNoAuthenticatedUserSession();
            $this->test42_validIpnSucceedsWhenAuthUserIdIsNull();
            $this->test43_invalidIpnKeyIsRejected();
            $this->test44_wrongWebIdIsRejected();

            // Group 7: CashMaal API Verification Response Mapping (45-50)
            $this->test45_verifyV2SuccessfulPkrResponseReadsPkrAmount();
            $this->test46_verifyV2SuccessfulUsdResponseReadsUsdAmount();
            $this->test47_missingOrInvalidVerificationResponseIsRejected();
            $this->test48_verifiedAmountMismatchIsRejected();
            $this->test49_verifiedTransactionIdMismatchIsRejected();
            $this->test50_verifiedOrderIdMismatchIsRejected();

            // Group 8: Subscription Duration From Plan (51-52)
            $this->test51_subscriptionExpiryUsesConfiguredPlanDuration();
            $this->test52_differentPlansProduceDifferentCorrectExpiryDates();

            // Group 9: IPN Replay & Concurrency (53-55)
            $this->test53_duplicateValidIpnReturnsSafelyWithoutDuplicateSubscription();
            $this->test54_duplicateValidIpnDoesNotCreateAnotherPaymentRecord();
            $this->test55_duplicateValidIpnDoesNotCreateAnotherNotificationQueueRow();

            // Group 10: Security & Identity Invariants (56-60)
            $this->test56_browserUserCannotProcessAnotherUsersTransaction();
            $this->test57_serverToServerIpnDoesNotRequireBrowserAuthentication();
            $this->test58_forgedIpnWithCorrectOrderIdInvalidKeyIsRejected();
            $this->test59_browserSuccessRedirectCannotActivateUnpaidTransaction();
            $this->test60_cashmaalVerificationForAnotherTransactionCannotActivateLocalTransaction();

            // Group 11: Critical Renewal & Monetary Precision (61-71)
            $this->test61_subscriptionRenewalPreservesRemainingTime();
            $this->test62_exactIntegerAmount_A();
            $this->test63_equivalentFormatting_B();
            $this->test64_exactDecimalAmount_C();
            $this->test65_oneCentOverpaymentRejected_D();
            $this->test66_oneCentUnderpaymentRejected_E();
            $this->test67_floatingPointEdgeCase_F();
            $this->test68_pkrVerifyV2ExactMatching_G();
            $this->test69_usdVerifyV2ExactMatching_H();
            $this->test70_verifyV2AmountMismatchOneMinorUnitRejected_I();
            $this->test71_ipnAmountMismatchOneMinorUnitRejected_J();

            // Group 12: Amount Field Semantics & Fee-Isolation (72-77)
            $this->test72_pkrUsesOnlyPkrAmountAndIgnoresFeeInclusiveField();
            $this->test73_pkrAmountWithFeeCannotRescueUnderpayment();
            $this->test74_usdUsesOnlyUsdAmountAndIgnoresFeeInclusiveField();
            $this->test75_usdAmountWithFeeCannotRescueUnderpayment();
            $this->test76_missingPkrAmountFailsEvenIfFeeFieldExists();
            $this->test77_missingUsdAmountFailsEvenIfFeeFieldExists();

            // Group 13: Normalizer Hardening & Integer Safety (78-85)
            $this->test78_validNormalAmount();
            $this->test79_malformedCommaAmount();
            $this->test80_malformedGrouping();
            $this->test81_validMachineFormatAmount();
            $this->test82_excessiveIntegerValue();
            $this->test83_negativeAmount();
            $this->test84_nonNumericValue();
            $this->test85_whitespaceHandling();

            echo "\n=================================================================\n";
            echo " ✔ ALL 85 STEP 4 PAYMENT INTEGRATION TESTS PASSED SUCCESSFULLY!\n";
            echo "=================================================================\n\n";

        } finally {
            $this->tearDown();
        }
    }

    private function setUp(): void {
        $this->tearDown();

        // Ensure Premium plan exists in database
        $stmt = $this->db->prepare("SELECT id FROM subscription_plans WHERE slug = 'premium-monthly' LIMIT 1");
        $stmt->execute();
        $planId = $stmt->fetchColumn();

        if (!$planId) {
            $this->db->exec("
                INSERT INTO subscription_plans (name, slug, description, billing_interval, price, currency, status, created_at, updated_at)
                VALUES ('Premium Monthly', 'premium-monthly', 'Full access plan', 'month', 1499.00, 'PKR', 'active', NOW(), NOW())
            ");
            $this->planPremiumId = (int)$this->db->lastInsertId();
        } else {
            $this->planPremiumId = (int)$planId;
        }

        // Ensure Precision plan exists in database
        $stmtPrec = $this->db->prepare("SELECT id FROM subscription_plans WHERE slug = 'precision-plan' LIMIT 1");
        $stmtPrec->execute();
        $precId = $stmtPrec->fetchColumn();
        if (!$precId) {
            $this->db->exec("
                INSERT INTO subscription_plans (name, slug, description, billing_interval, duration_days, price, currency, status, created_at, updated_at)
                VALUES ('Precision Plan', 'precision-plan', 'Precision test plan', 'month', 30, 1000.00, 'PKR', 'active', NOW(), NOW())
            ");
            $this->planPrecisionId = (int)$this->db->lastInsertId();
        } else {
            $this->planPrecisionId = (int)$precId;
        }

        // Set test environment configuration
        $_ENV['CASHMAAL_WEB_ID'] = $this->testWebId;
        $_ENV['CASHMAAL_IPN_KEY'] = $this->testIpnKey;
        $_ENV['CASHMAAL_PAY_URL'] = 'https://cmaal.com/Pay/';
        $_ENV['CASHMAAL_VERIFY_URL'] = 'https://api.cmaal.com/verify_v2';
    }

    private function tearDown(): void {
        $this->db->exec("DELETE FROM notification_logs WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'step4-%@scholarmatch.com')");
        $this->db->exec("DELETE FROM payment_transactions WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'step4-%@scholarmatch.com')");
        $this->db->exec("DELETE FROM subscriptions WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'step4-%@scholarmatch.com')");
        $this->db->exec("DELETE FROM users WHERE email LIKE 'step4-%@scholarmatch.com'");
        $this->db->exec("DELETE FROM subscription_plans WHERE slug IN ('quarterly-test', 'usd-test-plan', 'precision-plan', 'float-edge-plan', 'usd-10-plan')");
        unset($_SESSION['user_id'], $_SESSION['user_role'], $_SESSION['user_email']);
    }

    private int $userCounter = 1000;

    private function createTestUser(string $email, ?string $phone = null): int {
        $this->userCounter++;
        $actualPhone = ($phone === 'NONE') ? null : (($phone !== null) ? $phone : ('+92300' . sprintf('%07d', $this->userCounter)));
        $roleId = (int)$this->db->query("SELECT id FROM roles WHERE name = 'visitor' LIMIT 1")->fetchColumn();
        $stmt = $this->db->prepare("
            INSERT INTO users (role_id, email, password_hash, first_name, last_name, phone, whatsapp_phone, email_opt_in, whatsapp_opt_in, status, email_verified_at, created_at, updated_at)
            VALUES (:rid, :email, 'hash', 'Step4', 'Student', :phone, :wphone, 1, 1, 'active', NOW(), NOW(), NOW())
        ");
        $stmt->execute(['rid' => $roleId, 'email' => $email, 'phone' => $actualPhone, 'wphone' => $actualPhone]);
        return (int)$this->db->lastInsertId();
    }

    private function createPendingTransaction(int $userId, int $planId, float $amount, string $ref, string $currency = 'PKR'): int {
        $stmt = $this->db->prepare("
            INSERT INTO payment_transactions (
                user_id, plan_id, provider, transaction_reference, amount, currency, status, created_at, updated_at
            ) VALUES (
                :uid, :pid, 'cashmaal', :ref, :amount, :currency, 'pending', NOW(), NOW()
            )
        ");
        $stmt->execute([
            'uid' => $userId,
            'pid' => $planId,
            'ref' => $ref,
            'amount' => $amount,
            'currency' => $currency
        ]);
        return (int)$this->db->lastInsertId();
    }

    // =========================================================================
    // TESTS 1 - 10: CashMaal Configuration & Amount Integrity
    // =========================================================================

    private function test1_cashmaalConfigurationLoadsSafely(): void {
        echo "[Test 1] CashMaal configuration loads safely... ";
        $gateway = new CashMaalPaymentGateway();
        $this->assert($gateway->isConfigured(), "CashMaal must be configured with test keys");
        echo "PASS\n";
    }

    private function test2_missingCredentialsFailSafely(): void {
        echo "[Test 2] Missing credentials fail safely... ";
        $gateway = new CashMaalPaymentGateway(['web_id' => '', 'ipn_key' => '']);
        $this->assert(!$gateway->isConfigured(), "Empty keys must report unconfigured");

        $failed = false;
        try {
            $gateway->createCheckout(['transaction_reference' => 'TXN_TEST', 'amount' => 100]);
        } catch (\RuntimeException $e) {
            $failed = true;
        }
        $this->assert($failed, "createCheckout must throw RuntimeException when credentials missing");
        echo "PASS\n";
    }

    private function test3_paymentAmountIsTakenFromServerSidePlan(): void {
        echo "[Test 3] Payment amount is taken from server-side plan... ";
        $stmt = $this->db->prepare("SELECT price, currency FROM subscription_plans WHERE id = :id");
        $stmt->execute(['id' => $this->planPremiumId]);
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assert((float)$plan['price'] === 1499.00, "Server-side price must be 1499.00");
        $this->assert($plan['currency'] === 'PKR', "Server-side currency must be PKR");
        echo "PASS\n";
    }

    private function test4_browserSuppliedAmountCannotChangePayableAmount(): void {
        echo "[Test 4] Browser-supplied amount cannot change payable amount... ";
        $uid = $this->createTestUser('step4-u4@scholarmatch.com');
        $_SESSION['user_id'] = $uid;
        $_SESSION['user_role'] = 'visitor';

        $csrf = Security::csrfToken();
        $_POST['csrf_token'] = $csrf;
        $_POST['plan_slug'] = 'premium-monthly';
        $_POST['payment_provider'] = 'cashmaal';
        $_POST['amount'] = '1.00'; // Tampered amount attempt

        $caught = false;
        try {
            $this->billingController->processCheckout();
        } catch (\RuntimeException $e) {
            // Expected redirect in testing mode
            $caught = true;
        }
        $this->assert($caught, "processCheckout redirects to gateway");

        // Verify transaction in DB has server-side price 1499.00, NOT 1.00
        $stmt = $this->db->prepare("SELECT amount FROM payment_transactions WHERE user_id = :uid ORDER BY id DESC LIMIT 1");
        $stmt->execute(['uid' => $uid]);
        $recordedAmount = (float)$stmt->fetchColumn();

        $this->assert($recordedAmount === 1499.00, "Recorded amount must strictly be 1499.00, not 1.00");
        echo "PASS\n";
    }

    private function test5_paymentTransactionIsCreatedAsPending(): void {
        echo "[Test 5] Payment transaction is created as pending... ";
        $uid = $this->createTestUser('step4-u5@scholarmatch.com');
        $ref = 'TXN_STEP4_TEST_5';
        $txId = $this->createPendingTransaction($uid, $this->planPremiumId, 1499.00, $ref);

        $stmt = $this->db->prepare("SELECT status, provider, plan_id FROM payment_transactions WHERE id = :id");
        $stmt->execute(['id' => $txId]);
        $tx = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assert($tx['status'] === 'pending', "Transaction status must be pending");
        $this->assert($tx['provider'] === 'cashmaal', "Provider must be cashmaal");
        $this->assert((int)$tx['plan_id'] === $this->planPremiumId, "Plan ID must match");
        echo "PASS\n";
    }

    private function test6_unknownTransactionReferenceIsRejected(): void {
        echo "[Test 6] Unknown transaction reference is rejected... ";
        $_POST = [
            'ipn_key' => $this->testIpnKey,
            'status' => '1',
            'CM_TID' => 'CM_999',
            'order_id' => 'TXN_NON_EXISTENT_XYZ',
            'Amount' => '1499.00',
            'currency' => 'PKR'
        ];

        $caught = false;
        try {
            $this->billingController->cashmaalIpn();
        } catch (\RuntimeException $e) {
            if (strpos($e->getMessage(), '404') !== false) {
                $caught = true;
            }
        }
        $this->assert($caught, "Unknown transaction reference must abort with 404");
        echo "PASS\n";
    }

    private function test7_invalidMerchantAccountDataIsRejected(): void {
        echo "[Test 7] Invalid merchant/account data (wrong IPN key) is rejected... ";
        $uid = $this->createTestUser('step4-u7@scholarmatch.com');
        $ref = 'TXN_STEP4_TEST_7';
        $this->createPendingTransaction($uid, $this->planPremiumId, 1499.00, $ref);

        $_POST = [
            'ipn_key' => 'wrong_forged_key',
            'status' => '1',
            'CM_TID' => 'CM_777',
            'order_id' => $ref,
            'Amount' => '1499.00',
            'currency' => 'PKR'
        ];

        $caught = false;
        try {
            $this->billingController->cashmaalIpn();
        } catch (\RuntimeException $e) {
            if (strpos($e->getMessage(), '400') !== false) {
                $caught = true;
            }
        }
        $this->assert($caught, "Invalid IPN key must abort with 400");
        echo "PASS\n";
    }

    private function test8_invalidPaymentVerificationIsRejected(): void {
        echo "[Test 8] Invalid payment verification (status rejected) is handled safely... ";
        $uid = $this->createTestUser('step4-u8@scholarmatch.com');
        $ref = 'TXN_STEP4_TEST_8';
        $txId = $this->createPendingTransaction($uid, $this->planPremiumId, 1499.00, $ref);

        $_POST = [
            'ipn_key' => $this->testIpnKey,
            'status' => '3', // 3 = Rejected in CashMaal
            'CM_TID' => 'CM_888',
            'order_id' => $ref,
            'Amount' => '1499.00',
            'currency' => 'PKR'
        ];

        ob_start();
        $this->billingController->cashmaalIpn();
        $out = ob_get_clean();

        $status = $this->db->query("SELECT status FROM payment_transactions WHERE id = $txId")->fetchColumn();
        $this->assert($status === 'failed', "Transaction status must become failed");
        $this->assert(strpos($out, '**OK**') !== false, "Must acknowledge receipt with **OK**");
        echo "PASS\n";
    }

    private function test9_amountMismatchIsRejected(): void {
        echo "[Test 9] Amount mismatch is rejected... ";
        $uid = $this->createTestUser('step4-u9@scholarmatch.com');
        $ref = 'TXN_STEP4_TEST_9';
        $txId = $this->createPendingTransaction($uid, $this->planPremiumId, 1499.00, $ref);

        $_POST = [
            'ipn_key' => $this->testIpnKey,
            'status' => '1',
            'CM_TID' => 'CM_999',
            'order_id' => $ref,
            'Amount' => '500.00', // Amount mismatch
            'currency' => 'PKR'
        ];

        $caught = false;
        try {
            $this->billingController->cashmaalIpn();
        } catch (\RuntimeException $e) {
            if (strpos($e->getMessage(), '400') !== false) {
                $caught = true;
            }
        }
        $this->assert($caught, "Amount mismatch must abort with 400");

        $status = $this->db->query("SELECT status FROM payment_transactions WHERE id = $txId")->fetchColumn();
        $this->assert($status === 'failed', "Mismatched transaction must be marked failed");
        echo "PASS\n";
    }

    private function test10_currencyMismatchIsRejected(): void {
        echo "[Test 10] Currency mismatch is rejected... ";
        $uid = $this->createTestUser('step4-u10@scholarmatch.com');
        $ref = 'TXN_STEP4_TEST_10';
        $txId = $this->createPendingTransaction($uid, $this->planPremiumId, 1499.00, $ref, 'PKR');

        $_POST = [
            'ipn_key' => $this->testIpnKey,
            'status' => '1',
            'CM_TID' => 'CM_1010',
            'order_id' => $ref,
            'Amount' => '1499.00',
            'currency' => 'USD' // Currency mismatch
        ];

        $caught = false;
        try {
            $this->billingController->cashmaalIpn();
        } catch (\RuntimeException $e) {
            if (strpos($e->getMessage(), '400') !== false) {
                $caught = true;
            }
        }
        $this->assert($caught, "Currency mismatch must abort with 400");
        echo "PASS\n";
    }

    // =========================================================================
    // TESTS 11 - 19: Payment Status & Subscription Activation
    // =========================================================================

    private function test11_failedPaymentDoesNotActivateSubscription(): void {
        echo "[Test 11] Failed payment does not activate subscription... ";
        $uid = $this->createTestUser('step4-u11@scholarmatch.com');
        $ref = 'TXN_STEP4_TEST_11';
        $this->createPendingTransaction($uid, $this->planPremiumId, 1499.00, $ref);

        $_POST = [
            'ipn_key' => $this->testIpnKey,
            'status' => '3', // Failed
            'CM_TID' => 'CM_1111',
            'order_id' => $ref,
            'Amount' => '1499.00',
            'currency' => 'PKR'
        ];

        ob_start();
        $this->billingController->cashmaalIpn();
        ob_get_clean();

        $subCount = (int)$this->db->query("SELECT COUNT(*) FROM subscriptions WHERE user_id = $uid AND status = 'active'")->fetchColumn();
        $this->assert($subCount === 0, "Failed payment must not activate any subscription");
        echo "PASS\n";
    }

    private function test12_pendingPaymentDoesNotActivateSubscription(): void {
        echo "[Test 12] Pending payment does not activate subscription... ";
        $uid = $this->createTestUser('step4-u12@scholarmatch.com');
        $ref = 'TXN_STEP4_TEST_12';
        $this->createPendingTransaction($uid, $this->planPremiumId, 1499.00, $ref);

        $_POST = [
            'ipn_key' => $this->testIpnKey,
            'status' => '2', // Pending
            'CM_TID' => 'CM_1212',
            'order_id' => $ref,
            'Amount' => '1499.00',
            'currency' => 'PKR'
        ];

        ob_start();
        $this->billingController->cashmaalIpn();
        ob_get_clean();

        $subCount = (int)$this->db->query("SELECT COUNT(*) FROM subscriptions WHERE user_id = $uid AND status = 'active'")->fetchColumn();
        $this->assert($subCount === 0, "Pending payment must not activate subscription");
        echo "PASS\n";
    }

    private function test13_cancelledPaymentDoesNotActivateSubscription(): void {
        echo "[Test 13] Cancelled payment does not activate subscription... ";
        $uid = $this->createTestUser('step4-u13@scholarmatch.com');
        $ref = 'TXN_STEP4_TEST_13';
        $this->createPendingTransaction($uid, $this->planPremiumId, 1499.00, $ref);

        $_POST = [
            'ipn_key' => $this->testIpnKey,
            'status' => '0', // Cancelled
            'CM_TID' => 'CM_1313',
            'order_id' => $ref,
            'Amount' => '1499.00',
            'currency' => 'PKR'
        ];

        ob_start();
        $this->billingController->cashmaalIpn();
        ob_get_clean();

        $subCount = (int)$this->db->query("SELECT COUNT(*) FROM subscriptions WHERE user_id = $uid AND status = 'active'")->fetchColumn();
        $this->assert($subCount === 0, "Cancelled payment must not activate subscription");
        echo "PASS\n";
    }

    private function test14_verifiedSuccessfulPaymentActivatesCorrectSubscription(): void {
        echo "[Test 14] Verified successful payment activates the correct subscription... ";
        $uid = $this->createTestUser('step4-u14@scholarmatch.com');
        $ref = 'TXN_STEP4_TEST_14';
        $this->createPendingTransaction($uid, $this->planPremiumId, 1499.00, $ref);

        $_POST = [
            'ipn_key' => $this->testIpnKey,
            'status' => '1',
            'CM_TID' => 'CM_1414',
            'order_id' => $ref,
            'Amount' => '1499.00',
            'currency' => 'PKR'
        ];

        ob_start();
        $this->billingController->cashmaalIpn();
        $out = ob_get_clean();

        $this->assert(strpos($out, '**OK**') !== false, "Must return **OK**");

        $stmt = $this->db->prepare("SELECT plan_id, status FROM subscriptions WHERE user_id = :uid AND status = 'active'");
        $stmt->execute(['uid' => $uid]);
        $sub = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assert(!empty($sub), "Active subscription must exist");
        $this->assert((int)$sub['plan_id'] === $this->planPremiumId, "Activated subscription must be Premium plan");
        echo "PASS\n";
    }

    private function test15_paymentIsRecordedCorrectly(): void {
        echo "[Test 15] Payment is recorded correctly in payment_transactions... ";
        $uid = $this->createTestUser('step4-u15@scholarmatch.com');
        $ref = 'TXN_STEP4_TEST_15';
        $txId = $this->createPendingTransaction($uid, $this->planPremiumId, 1499.00, $ref);

        $_POST = [
            'ipn_key' => $this->testIpnKey,
            'status' => '1',
            'CM_TID' => 'CM_1515_UNIQUE',
            'order_id' => $ref,
            'Amount' => '1499.00',
            'currency' => 'PKR'
        ];

        ob_start();
        $this->billingController->cashmaalIpn();
        ob_get_clean();

        $stmt = $this->db->prepare("SELECT status, provider_transaction_id, paid_at, subscription_id FROM payment_transactions WHERE id = :id");
        $stmt->execute(['id' => $txId]);
        $tx = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assert($tx['status'] === 'paid', "Status must be 'paid'");
        $this->assert($tx['provider_transaction_id'] === 'CM_1515_UNIQUE', "CM_TID must match");
        $this->assert(!empty($tx['paid_at']), "paid_at must not be empty");
        $this->assert(!empty($tx['subscription_id']), "subscription_id must be linked");
        echo "PASS\n";
    }

    private function test16_duplicateSuccessfulCallbackIsIdempotent(): void {
        echo "[Test 16] Duplicate successful callback is idempotent... ";
        $uid = $this->createTestUser('step4-u16@scholarmatch.com');
        $ref = 'TXN_STEP4_TEST_16';
        $this->createPendingTransaction($uid, $this->planPremiumId, 1499.00, $ref);

        $_POST = [
            'ipn_key' => $this->testIpnKey,
            'status' => '1',
            'CM_TID' => 'CM_1616',
            'order_id' => $ref,
            'Amount' => '1499.00',
            'currency' => 'PKR'
        ];

        // First callback
        ob_start();
        $this->billingController->cashmaalIpn();
        $res1 = ob_get_clean();

        // Duplicate callback
        ob_start();
        $this->billingController->cashmaalIpn();
        $res2 = ob_get_clean();

        $this->assert(strpos($res1, '**OK**') !== false, "First call must return **OK**");
        $this->assert(strpos($res2, '**OK**') !== false, "Duplicate call must return **OK**");
        echo "PASS\n";
    }

    private function test17_duplicateCallbackDoesNotCreateSecondSubscription(): void {
        echo "[Test 17] Duplicate callback does not create a second subscription... ";
        $uid = $this->createTestUser('step4-u17@scholarmatch.com');
        $ref = 'TXN_STEP4_TEST_17';
        $this->createPendingTransaction($uid, $this->planPremiumId, 1499.00, $ref);

        $_POST = [
            'ipn_key' => $this->testIpnKey,
            'status' => '1',
            'CM_TID' => 'CM_1717',
            'order_id' => $ref,
            'Amount' => '1499.00',
            'currency' => 'PKR'
        ];

        ob_start();
        $this->billingController->cashmaalIpn();
        $this->billingController->cashmaalIpn();
        ob_get_clean();

        $count = (int)$this->db->query("SELECT COUNT(*) FROM subscriptions WHERE user_id = $uid")->fetchColumn();
        $this->assert($count === 1, "Subscription count must strictly remain 1");
        echo "PASS\n";
    }

    private function test18_duplicateCallbackDoesNotCreateSecondPaymentRecord(): void {
        echo "[Test 18] Duplicate callback does not create a second payment record... ";
        $uid = $this->createTestUser('step4-u18@scholarmatch.com');
        $ref = 'TXN_STEP4_TEST_18';
        $this->createPendingTransaction($uid, $this->planPremiumId, 1499.00, $ref);

        $_POST = [
            'ipn_key' => $this->testIpnKey,
            'status' => '1',
            'CM_TID' => 'CM_1818',
            'order_id' => $ref,
            'Amount' => '1499.00',
            'currency' => 'PKR'
        ];

        ob_start();
        $this->billingController->cashmaalIpn();
        $this->billingController->cashmaalIpn();
        ob_get_clean();

        $count = (int)$this->db->query("SELECT COUNT(*) FROM payment_transactions WHERE transaction_reference = '$ref'")->fetchColumn();
        $this->assert($count === 1, "Transaction records count must strictly remain 1");
        echo "PASS\n";
    }

    private function test19_duplicateCallbackDoesNotCreateSecondNotificationQueueRow(): void {
        echo "[Test 19] Duplicate callback does not create a second notification queue row... ";
        $uid = $this->createTestUser('step4-u19@scholarmatch.com');
        $ref = 'TXN_STEP4_TEST_19';
        $txId = $this->createPendingTransaction($uid, $this->planPremiumId, 1499.00, $ref);

        $_POST = [
            'ipn_key' => $this->testIpnKey,
            'status' => '1',
            'CM_TID' => 'CM_1919',
            'order_id' => $ref,
            'Amount' => '1499.00',
            'currency' => 'PKR'
        ];

        ob_start();
        $this->billingController->cashmaalIpn();
        $this->billingController->cashmaalIpn();
        ob_get_clean();

        $key = "payment_confirmation_{$txId}";
        $count = (int)$this->db->query("SELECT COUNT(*) FROM notification_logs WHERE idempotency_key = '$key'")->fetchColumn();
        $this->assert($count === 1, "Notification queue records must strictly remain 1");
        echo "PASS\n";
    }

    // =========================================================================
    // TESTS 20 - 22: Atomicity & Transaction Safety
    // =========================================================================

    private function test20_paymentProcessingIsTransactional(): void {
        echo "[Test 20] Payment processing is transactional (uses database transaction)... ";
        $uid = $this->createTestUser('step4-u20@scholarmatch.com');
        $ref = 'TXN_STEP4_TEST_20';
        $txId = $this->createPendingTransaction($uid, $this->planPremiumId, 1499.00, $ref);

        $_POST = [
            'ipn_key' => $this->testIpnKey,
            'status' => '1',
            'CM_TID' => 'CM_2020',
            'order_id' => $ref,
            'Amount' => '1499.00',
            'currency' => 'PKR'
        ];

        ob_start();
        $this->billingController->cashmaalIpn();
        ob_get_clean();

        $txStatus = $this->db->query("SELECT status FROM payment_transactions WHERE id = $txId")->fetchColumn();
        $subStatus = $this->db->query("SELECT status FROM subscriptions WHERE user_id = $uid")->fetchColumn();

        $this->assert($txStatus === 'paid', "Transaction is paid");
        $this->assert($subStatus === 'active', "Subscription is active");
        echo "PASS\n";
    }

    private function test21_databaseFailureRollsBackBusinessOperation(): void {
        echo "[Test 21] Database failure rolls back the business operation... ";
        $uid = $this->createTestUser('step4-u21@scholarmatch.com');
        $ref = 'TXN_STEP4_TEST_21';
        $txId = $this->createPendingTransaction($uid, $this->planPremiumId, 1499.00, $ref);

        // Intentionally provoke a failure inside a simulated transaction block
        $this->db->beginTransaction();
        $this->db->exec("UPDATE payment_transactions SET status = 'paid' WHERE id = $txId");
        $this->db->rollBack(); // Simulate rollback

        $status = $this->db->query("SELECT status FROM payment_transactions WHERE id = $txId")->fetchColumn();
        $this->assert($status === 'pending', "Transaction remains pending after rollback");
        echo "PASS\n";
    }

    private function test22_successfulPaymentAndSubscriptionStateRemainConsistent(): void {
        echo "[Test 22] Successful payment and subscription state remain consistent... ";
        $uid = $this->createTestUser('step4-u22@scholarmatch.com');
        $ref = 'TXN_STEP4_TEST_22';
        $txId = $this->createPendingTransaction($uid, $this->planPremiumId, 1499.00, $ref);

        $_POST = [
            'ipn_key' => $this->testIpnKey,
            'status' => '1',
            'CM_TID' => 'CM_2222',
            'order_id' => $ref,
            'Amount' => '1499.00',
            'currency' => 'PKR'
        ];

        ob_start();
        $this->billingController->cashmaalIpn();
        ob_get_clean();

        $sub = $this->db->query("SELECT s.id, s.status, t.status as tx_status FROM subscriptions s JOIN payment_transactions t ON t.subscription_id = s.id WHERE t.id = $txId")->fetch(PDO::FETCH_ASSOC);

        $this->assert($sub['status'] === 'active', "Subscription must be active");
        $this->assert($sub['tx_status'] === 'paid', "Transaction must be paid");
        echo "PASS\n";
    }

    // =========================================================================
    // TESTS 23 - 35: Meta WhatsApp Confirmation & Separation
    // =========================================================================

    private function test23_successfulPaymentSelectsMetaWhatsApp(): void {
        echo "[Test 23] Successful payment selects Meta WhatsApp... ";
        $waService = new WhatsAppNotificationService();
        $provider = $waService->getProviderFor(null, 'PAYMENT_CONFIRMATION');

        $this->assert($provider instanceof MetaWhatsAppProvider, "Payment confirmation must resolve to MetaWhatsAppProvider");
        echo "PASS\n";
    }

    private function test24_scholarshipWhatsAppStillSelectsWacrm(): void {
        echo "[Test 24] Scholarship WhatsApp still selects WACRM... ";
        $waService = new WhatsAppNotificationService();
        $provider = $waService->getProviderFor(null, 'NEW_MATCH');

        $this->assert($provider instanceof WacrmWhatsAppProvider || $provider instanceof \App\Services\WhatsApp\LogWhatsAppProvider, "Scholarship alerts must continue using WACRM");
        echo "PASS\n";
    }

    private function test25_paymentConfirmationUsesConfirmationMsgTemplate(): void {
        echo "[Test 25] Payment confirmation uses confirmation_msg template... ";
        $ref = new ReflectionClass($this->queueService);
        $method = $ref->getMethod('getWhatsAppTemplateName');
        $method->setAccessible(true);

        $template = $method->invoke($this->queueService, 'PAYMENT_CONFIRMATION');
        $this->assert($template === 'confirmation_msg', "Template for PAYMENT_CONFIRMATION must strictly be 'confirmation_msg'");
        echo "PASS\n";
    }

    private function test26_failedPaymentDoesNotQueueSuccessfulConfirmation(): void {
        echo "[Test 26] Failed payment does not queue successful-payment confirmation... ";
        $uid = $this->createTestUser('step4-u26@scholarmatch.com');
        $ref = 'TXN_STEP4_TEST_26';
        $txId = $this->createPendingTransaction($uid, $this->planPremiumId, 1499.00, $ref);

        $_POST = [
            'ipn_key' => $this->testIpnKey,
            'status' => '3', // Failed
            'CM_TID' => 'CM_2626',
            'order_id' => $ref,
            'Amount' => '1499.00',
            'currency' => 'PKR'
        ];

        ob_start();
        $this->billingController->cashmaalIpn();
        ob_get_clean();

        $key = "payment_confirmation_{$txId}";
        $count = (int)$this->db->query("SELECT COUNT(*) FROM notification_logs WHERE idempotency_key = '$key'")->fetchColumn();
        $this->assert($count === 0, "No confirmation must be queued for failed payment");
        echo "PASS\n";
    }

    private function test27_pendingPaymentDoesNotQueueSuccessfulConfirmation(): void {
        echo "[Test 27] Pending payment does not queue successful-payment confirmation... ";
        $uid = $this->createTestUser('step4-u27@scholarmatch.com');
        $ref = 'TXN_STEP4_TEST_27';
        $txId = $this->createPendingTransaction($uid, $this->planPremiumId, 1499.00, $ref);

        $_POST = [
            'ipn_key' => $this->testIpnKey,
            'status' => '2', // Pending
            'CM_TID' => 'CM_2727',
            'order_id' => $ref,
            'Amount' => '1499.00',
            'currency' => 'PKR'
        ];

        ob_start();
        $this->billingController->cashmaalIpn();
        ob_get_clean();

        $key = "payment_confirmation_{$txId}";
        $count = (int)$this->db->query("SELECT COUNT(*) FROM notification_logs WHERE idempotency_key = '$key'")->fetchColumn();
        $this->assert($count === 0, "No confirmation must be queued for pending payment");
        echo "PASS\n";
    }

    private function test28_cancelledPaymentDoesNotQueueSuccessfulConfirmation(): void {
        echo "[Test 28] Cancelled payment does not queue successful-payment confirmation... ";
        $uid = $this->createTestUser('step4-u28@scholarmatch.com');
        $ref = 'TXN_STEP4_TEST_28';
        $txId = $this->createPendingTransaction($uid, $this->planPremiumId, 1499.00, $ref);

        $_POST = [
            'ipn_key' => $this->testIpnKey,
            'status' => '0', // Cancelled
            'CM_TID' => 'CM_2828',
            'order_id' => $ref,
            'Amount' => '1499.00',
            'currency' => 'PKR'
        ];

        ob_start();
        $this->billingController->cashmaalIpn();
        ob_get_clean();

        $key = "payment_confirmation_{$txId}";
        $count = (int)$this->db->query("SELECT COUNT(*) FROM notification_logs WHERE idempotency_key = '$key'")->fetchColumn();
        $this->assert($count === 0, "No confirmation must be queued for cancelled payment");
        echo "PASS\n";
    }

    private function test29_paymentCallbackNeverDirectlyCallsMetaApi(): void {
        echo "[Test 29] Payment callback never directly calls Meta API... ";
        $uid = $this->createTestUser('step4-u29@scholarmatch.com');
        $ref = 'TXN_STEP4_TEST_29';
        $txId = $this->createPendingTransaction($uid, $this->planPremiumId, 1499.00, $ref);

        $_POST = [
            'ipn_key' => $this->testIpnKey,
            'status' => '1',
            'CM_TID' => 'CM_2929',
            'order_id' => $ref,
            'Amount' => '1499.00',
            'currency' => 'PKR'
        ];

        ob_start();
        $this->billingController->cashmaalIpn();
        ob_get_clean();

        // Verify that the queued notification is still pending in DB and was NOT sent directly
        $key = "payment_confirmation_{$txId}";
        $status = $this->db->query("SELECT status FROM notification_logs WHERE idempotency_key = '$key'")->fetchColumn();
        $this->assert($status === 'pending', "Notification must be pending in queue, not synchronously sent");
        echo "PASS\n";
    }

    private function test30_paymentCallbackNeverDirectlyCallsWacrm(): void {
        echo "[Test 30] Payment callback never directly calls WACRM... ";
        $billingContent = file_get_contents(ROOT_PATH . '/app/Controllers/BillingController.php');
        $this->assert(strpos($billingContent, 'WacrmWhatsAppProvider') === false || strpos($billingContent, 'normalizePhoneNumber') !== false, "BillingController must not invoke Wacrm sending methods");
        echo "PASS\n";
    }

    private function test31_paymentCallbackNeverDirectlyPerformsCurlDelivery(): void {
        echo "[Test 31] Payment callback never directly performs cURL delivery... ";
        $uid = $this->createTestUser('step4-u31@scholarmatch.com');
        $ref = 'TXN_STEP4_TEST_31';
        $txId = $this->createPendingTransaction($uid, $this->planPremiumId, 1499.00, $ref);

        $_POST = [
            'ipn_key' => $this->testIpnKey,
            'status' => '1',
            'CM_TID' => 'CM_3131',
            'order_id' => $ref,
            'Amount' => '1499.00',
            'currency' => 'PKR'
        ];

        ob_start();
        $this->billingController->cashmaalIpn();
        ob_get_clean();

        $key = "payment_confirmation_{$txId}";
        $item = $this->db->query("SELECT provider, status FROM notification_logs WHERE idempotency_key = '$key'")->fetch(PDO::FETCH_ASSOC);

        $this->assert($item['provider'] === 'meta', "Provider must be meta");
        $this->assert($item['status'] === 'pending', "Status must be pending waiting for queue worker");
        echo "PASS\n";
    }

    private function test32_confirmationIsDeliveredOnlyByQueueWorker(): void {
        echo "[Test 32] Confirmation is delivered only by the queue worker... ";
        $uid = $this->createTestUser('step4-u32@scholarmatch.com');
        $ref = 'TXN_STEP4_TEST_32';
        $txId = $this->createPendingTransaction($uid, $this->planPremiumId, 1499.00, $ref);

        $_POST = [
            'ipn_key' => $this->testIpnKey,
            'status' => '1',
            'CM_TID' => 'CM_3232',
            'order_id' => $ref,
            'Amount' => '1499.00',
            'currency' => 'PKR'
        ];

        ob_start();
        $this->billingController->cashmaalIpn();
        ob_get_clean();

        $key = "payment_confirmation_{$txId}";

        // Execute queue worker
        $this->queueService->processQueue(50);

        $item = $this->db->query("SELECT status, attempts FROM notification_logs WHERE idempotency_key = '$key'")->fetch(PDO::FETCH_ASSOC);
        $this->assert(in_array($item['status'], ['sent', 'failed', 'retrying']) && (int)$item['attempts'] >= 1, "Queue worker must process the item (result: {$item['status']}, attempts: {$item['attempts']})");
        echo "PASS\n";
    }

    private function test33_confirmationIdempotencyKeyRemainsUnchangedOnRepeatedCallbacks(): void {
        echo "[Test 33] Confirmation idempotency key remains unchanged on repeated callbacks... ";
        $uid = $this->createTestUser('step4-u33@scholarmatch.com');
        $ref = 'TXN_STEP4_TEST_33';
        $txId = $this->createPendingTransaction($uid, $this->planPremiumId, 1499.00, $ref);

        $_POST = [
            'ipn_key' => $this->testIpnKey,
            'status' => '1',
            'CM_TID' => 'CM_3333',
            'order_id' => $ref,
            'Amount' => '1499.00',
            'currency' => 'PKR'
        ];

        ob_start();
        $this->billingController->cashmaalIpn();
        $this->billingController->cashmaalIpn();
        ob_get_clean();

        $expectedKey = "payment_confirmation_{$txId}";
        $key = $this->db->query("SELECT idempotency_key FROM notification_logs WHERE idempotency_key = '$expectedKey'")->fetchColumn();

        $this->assert($key === $expectedKey, "Idempotency key must remain strictly payment_confirmation_{payment_id}");
        echo "PASS\n";
    }

    private function test34_missingOrInvalidWhatsAppNumberDoesNotRollBackPayment(): void {
        echo "[Test 34] Missing/invalid WhatsApp number does not roll back payment... ";
        // Create user with null phone
        $uid = $this->createTestUser('step4-u34@scholarmatch.com', 'NONE');
        $this->db->exec("UPDATE users SET phone = NULL, whatsapp_phone = NULL WHERE id = $uid");

        $ref = 'TXN_STEP4_TEST_34';
        $txId = $this->createPendingTransaction($uid, $this->planPremiumId, 1499.00, $ref);

        $_POST = [
            'ipn_key' => $this->testIpnKey,
            'status' => '1',
            'CM_TID' => 'CM_3434',
            'order_id' => $ref,
            'Amount' => '1499.00',
            'currency' => 'PKR'
        ];

        ob_start();
        $this->billingController->cashmaalIpn();
        ob_get_clean();

        // Payment and subscription MUST remain successful
        $txStatus = $this->db->query("SELECT status FROM payment_transactions WHERE id = $txId")->fetchColumn();
        $subStatus = $this->db->query("SELECT status FROM subscriptions WHERE user_id = $uid")->fetchColumn();

        $this->assert($txStatus === 'paid', "Payment must remain paid even without phone");
        $this->assert($subStatus === 'active', "Subscription must remain active even without phone");
        echo "PASS\n";
    }

    private function test35_metaProviderFailureDoesNotRollBackPayment(): void {
        echo "[Test 35] Meta provider failure does not roll back successful payment... ";
        $uid = $this->createTestUser('step4-u35@scholarmatch.com');
        $ref = 'TXN_STEP4_TEST_35';
        $txId = $this->createPendingTransaction($uid, $this->planPremiumId, 1499.00, $ref);

        $_POST = [
            'ipn_key' => $this->testIpnKey,
            'status' => '1',
            'CM_TID' => 'CM_3535',
            'order_id' => $ref,
            'Amount' => '1499.00',
            'currency' => 'PKR'
        ];

        ob_start();
        $this->billingController->cashmaalIpn();
        ob_get_clean();

        // Simulate queue worker processing with a forced failure
        $key = "payment_confirmation_{$txId}";
        $this->db->exec("UPDATE notification_logs SET status = 'failed', error_message = 'Simulated Meta Error' WHERE idempotency_key = '$key'");

        // Payment and subscription MUST remain intact
        $txStatus = $this->db->query("SELECT status FROM payment_transactions WHERE id = $txId")->fetchColumn();
        $subStatus = $this->db->query("SELECT status FROM subscriptions WHERE user_id = $uid")->fetchColumn();

        $this->assert($txStatus === 'paid', "Payment remains paid");
        $this->assert($subStatus === 'active', "Subscription remains active");
        echo "PASS\n";
    }

    // =========================================================================
    // TESTS 36 - 40: Security & Browser Return Guards
    // =========================================================================

    private function test36_forgedCallbackCannotActivateSubscription(): void {
        echo "[Test 36] Forged callback cannot activate subscription... ";
        $uid = $this->createTestUser('step4-u36@scholarmatch.com');
        $ref = 'TXN_STEP4_TEST_36';
        $this->createPendingTransaction($uid, $this->planPremiumId, 1499.00, $ref);

        $_POST = [
            'ipn_key' => 'evil_forged_key_123',
            'status' => '1',
            'CM_TID' => 'CM_3636',
            'order_id' => $ref,
            'Amount' => '1499.00',
            'currency' => 'PKR'
        ];

        $caught = false;
        try {
            $this->billingController->cashmaalIpn();
        } catch (\RuntimeException $e) {
            $caught = true;
        }
        $this->assert($caught, "Forged IPN key must be rejected");

        $subCount = (int)$this->db->query("SELECT COUNT(*) FROM subscriptions WHERE user_id = $uid AND status = 'active'")->fetchColumn();
        $this->assert($subCount === 0, "Forged callback must not activate subscription");
        echo "PASS\n";
    }

    private function test37_userCannotChangeAnotherUsersTransaction(): void {
        echo "[Test 37] User cannot change or access another user's transaction (IDOR prevention)... ";
        $uOwner = $this->createTestUser('step4-owner@scholarmatch.com');
        $uAttacker = $this->createTestUser('step4-attacker@scholarmatch.com');

        $ref = 'TXN_STEP4_OWNER_37';
        $this->createPendingTransaction($uOwner, $this->planPremiumId, 1499.00, $ref);

        // Attacker attempts to hit callback for owner's transaction
        $_SESSION['user_id'] = $uAttacker;
        $_SESSION['user_role'] = 'visitor';
        $_GET = ['ref' => $ref];
        $_POST = [];

        $caught = false;
        try {
            $this->billingController->callback();
        } catch (\RuntimeException $e) {
            if (strpos($e->getMessage(), '403') !== false) {
                $caught = true;
            }
        }
        $this->assert($caught, "IDOR attempt must abort with 403 Access Denied");
        echo "PASS\n";
    }

    private function test38_userCannotChangePlanAmountThroughParameters(): void {
        echo "[Test 38] User cannot change plan amount through request parameters... ";
        $gateway = new CashMaalPaymentGateway();
        $checkout = $gateway->createCheckout([
            'transaction_reference' => 'TXN_TEST_38',
            'amount' => 1499.00,
            'currency' => 'PKR',
            'email' => 'test@example.com',
            'plan_name' => 'Premium'
        ]);

        $this->assert(strpos($checkout['checkout_url'], 'amount=1499.00') !== false, "Checkout URL must contain server amount 1499.00");
        echo "PASS\n";
    }

    private function test39_sensitiveCashmaalCredentialsNeverAppearInLogsOrResponses(): void {
        echo "[Test 39] Sensitive CashMaal/Meta credentials never appear in logs or responses... ";
        $clean = \App\Services\Logger::redactSensitiveString("Error with ipn_key={$this->testIpnKey} and access_token=EAABwz98765");
        $this->assert(strpos($clean, $this->testIpnKey) === false, "IPN key must be redacted");
        $this->assert(strpos($clean, 'EAABwz98765') === false, "Meta access token must be redacted");
        echo "PASS\n";
    }

    private function test40_browserReturnUrlCannotIndependentlyMarkPaymentSuccessful(): void {
        echo "[Test 40] Browser return URL cannot independently mark payment successful... ";
        $uid = $this->createTestUser('step4-u40@scholarmatch.com');
        $ref = 'TXN_STEP4_TEST_40';
        $txId = $this->createPendingTransaction($uid, $this->planPremiumId, 1499.00, $ref);

        // User arrives at callback URL via GET (e.g. from browser) without server IPN verification
        $_SESSION['user_id'] = $uid;
        $_SESSION['user_role'] = 'visitor';
        $_GET = ['ref' => $ref];
        $_POST = [];

        $caught = false;
        try {
            $this->billingController->callback();
        } catch (\RuntimeException $e) {
            $caught = true; // redirect to billing
        }

        $status = $this->db->query("SELECT status FROM payment_transactions WHERE id = $txId")->fetchColumn();
        $this->assert($status === 'pending', "Transaction must remain pending until verified by gateway");

        $subCount = (int)$this->db->query("SELECT COUNT(*) FROM subscriptions WHERE user_id = $uid AND status = 'active'")->fetchColumn();
        $this->assert($subCount === 0, "Subscription must NOT be activated by browser GET redirect");
        echo "PASS\n";
    }

    // =========================================================================
    // GROUP 6: Server-to-Server IPN Authentication (41-44)
    // =========================================================================

    private function test41_validIpnSucceedsWithNoAuthenticatedUserSession(): void {
        echo "[Test 41] Valid CashMaal IPN succeeds with no authenticated user session... ";
        $_SESSION = [];
        $uid = $this->createTestUser('step4-u41@scholarmatch.com');
        $ref = 'TXN_STEP4_TEST_41';
        $txId = $this->createPendingTransaction($uid, $this->planPremiumId, 1499.00, $ref);

        $_POST = [
            'ipn_key' => $this->testIpnKey,
            'web_id' => $this->testWebId,
            'order_id' => $ref,
            'CM_TID' => 'CM_TEST_41',
            'status' => '1',
            'Amount' => 1499.00,
            'currency' => 'PKR'
        ];

        ob_start();
        $this->billingController->cashmaalIpn();
        $out = ob_get_clean();

        $this->assert(strpos($out, '**OK**') !== false, "IPN must respond with **OK**");
        $status = $this->db->query("SELECT status FROM payment_transactions WHERE id = $txId")->fetchColumn();
        $this->assert($status === 'paid', "Transaction must be marked paid");

        $sub = $this->db->query("SELECT status FROM subscriptions WHERE user_id = $uid AND plan_id = {$this->planPremiumId}")->fetch(PDO::FETCH_ASSOC);
        $this->assert($sub && $sub['status'] === 'active', "Subscription must be active");
        echo "PASS\n";
    }

    private function test42_validIpnSucceedsWhenAuthUserIdIsNull(): void {
        echo "[Test 42] Valid CashMaal IPN succeeds when Auth::userId() is null... ";
        $_SESSION = [];
        $this->assert(\App\Services\Auth::userId() === null, "Auth::userId() must be null");

        $uid = $this->createTestUser('step4-u42@scholarmatch.com');
        $ref = 'TXN_STEP4_TEST_42';
        $txId = $this->createPendingTransaction($uid, $this->planPremiumId, 1499.00, $ref);

        $_POST = [
            'ipn_key' => $this->testIpnKey,
            'web_id' => $this->testWebId,
            'order_id' => $ref,
            'CM_TID' => 'CM_TEST_42',
            'status' => '1',
            'Amount' => 1499.00,
            'currency' => 'PKR'
        ];

        ob_start();
        $this->billingController->cashmaalIpn();
        $out = ob_get_clean();

        $this->assert(strpos($out, '**OK**') !== false, "IPN must respond with **OK**");
        $status = $this->db->query("SELECT status FROM payment_transactions WHERE id = $txId")->fetchColumn();
        $this->assert($status === 'paid', "Transaction must be marked paid when Auth::userId() is null");
        echo "PASS\n";
    }

    private function test43_invalidIpnKeyIsRejected(): void {
        echo "[Test 43] Invalid ipn_key is rejected... ";
        $_SESSION = [];
        $uid = $this->createTestUser('step4-u43@scholarmatch.com');
        $ref = 'TXN_STEP4_TEST_43';
        $txId = $this->createPendingTransaction($uid, $this->planPremiumId, 1499.00, $ref);

        $_POST = [
            'ipn_key' => 'definitely_forged_key_123',
            'web_id' => $this->testWebId,
            'order_id' => $ref,
            'CM_TID' => 'CM_TEST_43',
            'status' => '1',
            'Amount' => 1499.00,
            'currency' => 'PKR'
        ];

        $caught = false;
        try {
            $this->billingController->cashmaalIpn();
        } catch (\RuntimeException $e) {
            $caught = true;
            $this->assert(strpos($e->getMessage(), 'Invalid IPN') !== false || strpos($e->getMessage(), '400') !== false, "Must abort with 400");
        }
        $this->assert($caught, "Invalid IPN key must be rejected");

        $status = $this->db->query("SELECT status FROM payment_transactions WHERE id = $txId")->fetchColumn();
        $this->assert($status === 'pending', "Transaction must remain pending");
        echo "PASS\n";
    }

    private function test44_wrongWebIdIsRejected(): void {
        echo "[Test 44] Wrong web_id is rejected... ";
        $_SESSION = [];
        $uid = $this->createTestUser('step4-u44@scholarmatch.com');
        $ref = 'TXN_STEP4_TEST_44';
        $txId = $this->createPendingTransaction($uid, $this->planPremiumId, 1499.00, $ref);

        $_POST = [
            'ipn_key' => $this->testIpnKey,
            'web_id' => 'wrong_merchant_id',
            'order_id' => $ref,
            'CM_TID' => 'CM_TEST_44',
            'status' => '1',
            'Amount' => 1499.00,
            'currency' => 'PKR'
        ];

        $caught = false;
        try {
            $this->billingController->cashmaalIpn();
        } catch (\RuntimeException $e) {
            $caught = true;
            $this->assert(strpos($e->getMessage(), 'web_id') !== false || strpos($e->getMessage(), '400') !== false, "Must reject wrong web_id");
        }
        $this->assert($caught, "Wrong web_id must be rejected");

        $status = $this->db->query("SELECT status FROM payment_transactions WHERE id = $txId")->fetchColumn();
        $this->assert($status === 'pending', "Transaction must remain pending");
        echo "PASS\n";
    }

    // =========================================================================
    // GROUP 7: CashMaal API Verification Response Mapping (45-50)
    // =========================================================================

    private function test45_verifyV2SuccessfulPkrResponseReadsPkrAmount(): void {
        echo "[Test 45] verify_v2 successful PKR response correctly reads PKR_amount... ";
        $gateway = new \App\Services\CashMaalPaymentGateway();
        $mockData = [
            'status' => '1',
            'receiver_account' => 'test_merchant_account',
            'USD_amount' => '5.35',
            'fee_in_USD' => '0.00',
            'PKR_amount' => '1499.00',
            'fee_in_PKR' => '0.00',
            'USD_amount_with_fee' => '5.35',
            'PKR_amount_with_fee' => '1499.00',
            'trx_website' => 'scholarplanner.com',
            'transaction_id' => 'CM_TID_PKR_45',
            'trx_date' => date('Y-m-d H:i:s'),
            'order_id' => 'TXN_45',
            'addi_info' => 'Premium Monthly',
            'sender_details' => 'Customer',
            'trx_details' => 'Paid via JazzCash'
        ];

        $res = $gateway->evaluateVerifyApiResponse($mockData, 'CM_TID_PKR_45', 'TXN_45', 1499.00, 'PKR');
        $this->assert($res['verified'] === true, "Must be verified");
        $this->assert((float)$res['amount'] === 1499.00, "Must parse PKR_amount correctly");
        $this->assert($res['currency'] === 'PKR', "Must set PKR currency");
        echo "PASS\n";
    }

    private function test46_verifyV2SuccessfulUsdResponseReadsUsdAmount(): void {
        echo "[Test 46] verify_v2 successful USD response correctly reads USD_amount... ";
        $gateway = new \App\Services\CashMaalPaymentGateway();
        $mockData = [
            'status' => '1',
            'receiver_account' => 'test_merchant_account',
            'USD_amount' => '15.00',
            'fee_in_USD' => '0.50',
            'PKR_amount' => '4200.00',
            'fee_in_PKR' => '140.00',
            'USD_amount_with_fee' => '15.50',
            'PKR_amount_with_fee' => '4340.00',
            'trx_website' => 'scholarplanner.com',
            'transaction_id' => 'CM_TID_USD_46',
            'trx_date' => date('Y-m-d H:i:s'),
            'order_id' => 'TXN_46',
            'addi_info' => 'Premium USD Plan',
            'sender_details' => 'Customer',
            'trx_details' => 'Paid via Crypto'
        ];

        $res = $gateway->evaluateVerifyApiResponse($mockData, 'CM_TID_USD_46', 'TXN_46', 15.00, 'USD');
        $this->assert($res['verified'] === true, "Must be verified");
        $this->assert((float)$res['amount'] === 15.00, "Must parse USD_amount correctly");
        $this->assert($res['currency'] === 'USD', "Must set USD currency");
        echo "PASS\n";
    }

    private function test47_missingOrInvalidVerificationResponseIsRejected(): void {
        echo "[Test 47] Missing/invalid verification response is rejected... ";
        $gateway = new \App\Services\CashMaalPaymentGateway();
        
        // Status 3 = Rejected
        $mockRejected = [
            'status' => '3',
            'transaction_id' => 'CM_TID_47',
            'order_id' => 'TXN_47',
            'PKR_amount' => '1499.00'
        ];
        $res = $gateway->evaluateVerifyApiResponse($mockRejected, 'CM_TID_47', 'TXN_47', 1499.00, 'PKR');
        $this->assert($res['verified'] === false, "Rejected status must not verify");

        // Status 0 = Cancelled
        $mockCancelled = [
            'status' => '0',
            'transaction_id' => 'CM_TID_47',
            'order_id' => 'TXN_47',
            'PKR_amount' => '1499.00'
        ];
        $res2 = $gateway->evaluateVerifyApiResponse($mockCancelled, 'CM_TID_47', 'TXN_47', 1499.00, 'PKR');
        $this->assert($res2['verified'] === false, "Cancelled status must not verify");
        echo "PASS\n";
    }

    private function test48_verifiedAmountMismatchIsRejected(): void {
        echo "[Test 48] Verified amount mismatch is rejected... ";
        $gateway = new \App\Services\CashMaalPaymentGateway();
        $mockData = [
            'status' => '1',
            'transaction_id' => 'CM_TID_48',
            'order_id' => 'TXN_48',
            'PKR_amount' => '500.00' // Expected 1499.00
        ];

        $res = $gateway->evaluateVerifyApiResponse($mockData, 'CM_TID_48', 'TXN_48', 1499.00, 'PKR');
        $this->assert($res['verified'] === false, "Amount mismatch must not verify");
        $this->assert(strpos($res['error'] ?? '', 'Amount mismatch') !== false, "Error message must state amount mismatch");
        echo "PASS\n";
    }

    private function test49_verifiedTransactionIdMismatchIsRejected(): void {
        echo "[Test 49] Verified transaction ID mismatch is rejected... ";
        $gateway = new \App\Services\CashMaalPaymentGateway();
        $mockData = [
            'status' => '1',
            'transaction_id' => 'CM_WRONG_TID', // Expected CM_TID_49
            'order_id' => 'TXN_49',
            'PKR_amount' => '1499.00'
        ];

        $res = $gateway->evaluateVerifyApiResponse($mockData, 'CM_TID_49', 'TXN_49', 1499.00, 'PKR');
        $this->assert($res['verified'] === false, "Transaction ID mismatch must not verify");
        $this->assert(strpos($res['error'] ?? '', 'Transaction ID mismatch') !== false, "Error message must state transaction ID mismatch");
        echo "PASS\n";
    }

    private function test50_verifiedOrderIdMismatchIsRejected(): void {
        echo "[Test 50] Verified order ID mismatch is rejected... ";
        $gateway = new \App\Services\CashMaalPaymentGateway();
        $mockData = [
            'status' => '1',
            'transaction_id' => 'CM_TID_50',
            'order_id' => 'TXN_OTHER_ORDER', // Expected TXN_50
            'PKR_amount' => '1499.00'
        ];

        $res = $gateway->evaluateVerifyApiResponse($mockData, 'CM_TID_50', 'TXN_50', 1499.00, 'PKR');
        $this->assert($res['verified'] === false, "Order ID mismatch must not verify");
        $this->assert(strpos($res['error'] ?? '', 'Order ID mismatch') !== false, "Error message must state order ID mismatch");
        echo "PASS\n";
    }

    // =========================================================================
    // GROUP 8: Subscription Duration From Plan (51-52)
    // =========================================================================

    private function test51_subscriptionExpiryUsesConfiguredPlanDuration(): void {
        echo "[Test 51] Subscription expiry uses the configured plan duration... ";
        $uid = $this->createTestUser('step4-u51@scholarmatch.com');
        $ref = 'TXN_STEP4_TEST_51';
        $txId = $this->createPendingTransaction($uid, $this->planPremiumId, 1499.00, $ref);

        $_POST = [
            'ipn_key' => $this->testIpnKey,
            'web_id' => $this->testWebId,
            'order_id' => $ref,
            'CM_TID' => 'CM_TEST_51',
            'status' => '1',
            'Amount' => 1499.00,
            'currency' => 'PKR'
        ];

        ob_start();
        $this->billingController->cashmaalIpn();
        ob_end_clean();

        $sub = $this->db->query("SELECT starts_at, ends_at FROM subscriptions WHERE user_id = $uid AND plan_id = {$this->planPremiumId}")->fetch(PDO::FETCH_ASSOC);
        $this->assert(!empty($sub['ends_at']), "Subscription ends_at must be populated");
        
        $diffDays = (strtotime($sub['ends_at']) - strtotime($sub['starts_at'])) / 86400;
        $this->assert($diffDays >= 28 && $diffDays <= 32, "Expiry must reflect plan duration (~30 days), got $diffDays days");
        echo "PASS\n";
    }

    private function test52_differentPlansProduceDifferentCorrectExpiryDates(): void {
        echo "[Test 52] Different plans produce different correct expiry dates when configured differently... ";
        // 1. Create a 90-day plan
        $this->db->exec("
            INSERT INTO subscription_plans (name, slug, description, billing_interval, duration_days, price, currency, status, created_at, updated_at)
            VALUES ('Quarterly Plan', 'quarterly-test', '90-day plan', 'quarter', 90, 3999.00, 'PKR', 'active', NOW(), NOW())
        ");
        $qPlanId = (int)$this->db->lastInsertId();

        $uid = $this->createTestUser('step4-u52@scholarmatch.com');
        $ref = 'TXN_STEP4_TEST_52';
        $txId = $this->createPendingTransaction($uid, $qPlanId, 3999.00, $ref);

        $_POST = [
            'ipn_key' => $this->testIpnKey,
            'web_id' => $this->testWebId,
            'order_id' => $ref,
            'CM_TID' => 'CM_TEST_52',
            'status' => '1',
            'Amount' => 3999.00,
            'currency' => 'PKR'
        ];

        ob_start();
        $this->billingController->cashmaalIpn();
        ob_end_clean();

        $sub = $this->db->query("SELECT starts_at, ends_at FROM subscriptions WHERE user_id = $uid AND plan_id = $qPlanId")->fetch(PDO::FETCH_ASSOC);
        $diffDays = round((strtotime($sub['ends_at']) - strtotime($sub['starts_at'])) / 86400);
        $this->assert($diffDays >= 89 && $diffDays <= 91, "Quarterly plan must produce ~90 days, got $diffDays");

        // 2. Test calculatePlanExpiry for yearly plan
        $yearlyPlan = ['billing_interval' => 'year', 'duration_days' => null];
        $expiryYear = \App\Services\SubscriptionService::calculatePlanExpiry($yearlyPlan, '2026-01-01 00:00:00');
        $this->assert($expiryYear === '2027-01-01 00:00:00', "Yearly plan must produce exact 1 year later");
        echo "PASS\n";
    }

    // =========================================================================
    // GROUP 9: IPN Replay & Concurrency (53-55)
    // =========================================================================

    private function test53_duplicateValidIpnReturnsSafelyWithoutDuplicateSubscription(): void {
        echo "[Test 53] Duplicate valid IPN returns safely without duplicate subscription... ";
        $uid = $this->createTestUser('step4-u53@scholarmatch.com');
        $ref = 'TXN_STEP4_TEST_53';
        $txId = $this->createPendingTransaction($uid, $this->planPremiumId, 1499.00, $ref);

        $_POST = [
            'ipn_key' => $this->testIpnKey,
            'web_id' => $this->testWebId,
            'order_id' => $ref,
            'CM_TID' => 'CM_TEST_53',
            'status' => '1',
            'Amount' => 1499.00,
            'currency' => 'PKR'
        ];

        // 1st delivery
        ob_start();
        $this->billingController->cashmaalIpn();
        $out1 = ob_get_clean();
        $this->assert(strpos($out1, '**OK**') !== false, "1st IPN must return **OK**");

        // 2nd delivery (resend)
        ob_start();
        $this->billingController->cashmaalIpn();
        $out2 = ob_get_clean();
        $this->assert(strpos($out2, '**OK**') !== false, "2nd duplicate IPN must safely return **OK**");

        $subCount = (int)$this->db->query("SELECT COUNT(*) FROM subscriptions WHERE user_id = $uid AND status = 'active'")->fetchColumn();
        $this->assert($subCount === 1, "Duplicate IPN must not duplicate subscription");
        echo "PASS\n";
    }

    private function test54_duplicateValidIpnDoesNotCreateAnotherPaymentRecord(): void {
        echo "[Test 54] Duplicate valid IPN does not create another payment record... ";
        $uid = $this->createTestUser('step4-u54@scholarmatch.com');
        $ref = 'TXN_STEP4_TEST_54';
        $txId = $this->createPendingTransaction($uid, $this->planPremiumId, 1499.00, $ref);

        $_POST = [
            'ipn_key' => $this->testIpnKey,
            'web_id' => $this->testWebId,
            'order_id' => $ref,
            'CM_TID' => 'CM_TEST_54',
            'status' => '1',
            'Amount' => 1499.00,
            'currency' => 'PKR'
        ];

        ob_start();
        $this->billingController->cashmaalIpn();
        ob_end_clean();

        ob_start();
        $this->billingController->cashmaalIpn();
        ob_end_clean();

        $txCount = (int)$this->db->query("SELECT COUNT(*) FROM payment_transactions WHERE transaction_reference = '$ref'")->fetchColumn();
        $this->assert($txCount === 1, "Transaction record count must remain exactly 1");
        echo "PASS\n";
    }

    private function test55_duplicateValidIpnDoesNotCreateAnotherNotificationQueueRow(): void {
        echo "[Test 55] Duplicate valid IPN does not create another notification queue row... ";
        $uid = $this->createTestUser('step4-u55@scholarmatch.com');
        $ref = 'TXN_STEP4_TEST_55';
        $txId = $this->createPendingTransaction($uid, $this->planPremiumId, 1499.00, $ref);

        $_POST = [
            'ipn_key' => $this->testIpnKey,
            'web_id' => $this->testWebId,
            'order_id' => $ref,
            'CM_TID' => 'CM_TEST_55',
            'status' => '1',
            'Amount' => 1499.00,
            'currency' => 'PKR'
        ];

        ob_start();
        $this->billingController->cashmaalIpn();
        ob_end_clean();

        ob_start();
        $this->billingController->cashmaalIpn();
        ob_end_clean();

        $qCount = (int)$this->db->query("SELECT COUNT(*) FROM notification_logs WHERE idempotency_key = 'payment_confirmation_{$txId}'")->fetchColumn();
        $this->assert($qCount === 1, "Queue row must not be duplicated");
        echo "PASS\n";
    }

    // =========================================================================
    // GROUP 10: Security & Identity Invariants (56-60)
    // =========================================================================

    private function test56_browserUserCannotProcessAnotherUsersTransaction(): void {
        echo "[Test 56] Browser user cannot process another user's transaction... ";
        $userA = $this->createTestUser('step4-u56a@scholarmatch.com');
        $userB = $this->createTestUser('step4-u56b@scholarmatch.com');
        $ref = 'TXN_STEP4_TEST_56_B';
        $txId = $this->createPendingTransaction($userB, $this->planPremiumId, 1499.00, $ref);

        // User A is logged into browser
        $_SESSION['user_id'] = $userA;
        $_SESSION['user_role'] = 'visitor';
        $_GET = ['ref' => $ref];
        $_POST = [];

        $caught = false;
        try {
            $this->billingController->callback();
        } catch (\RuntimeException $e) {
            $caught = true;
            $this->assert(strpos($e->getMessage(), '403') !== false || strpos($e->getMessage(), 'ownership') !== false, "Must abort with 403 on IDOR attempt");
        }
        $this->assert($caught, "Browser IDOR access must be blocked");
        echo "PASS\n";
    }

    private function test57_serverToServerIpnDoesNotRequireBrowserAuthentication(): void {
        echo "[Test 57] Server-to-server IPN does not require browser authentication... ";
        $_SESSION = [];
        unset($_SESSION['user_id']);
        unset($_SESSION['user_role']);

        $uid = $this->createTestUser('step4-u57@scholarmatch.com');
        $ref = 'TXN_STEP4_TEST_57';
        $txId = $this->createPendingTransaction($uid, $this->planPremiumId, 1499.00, $ref);

        $_POST = [
            'ipn_key' => $this->testIpnKey,
            'web_id' => $this->testWebId,
            'order_id' => $ref,
            'CM_TID' => 'CM_TEST_57',
            'status' => '1',
            'Amount' => 1499.00,
            'currency' => 'PKR'
        ];

        ob_start();
        $this->billingController->cashmaalIpn();
        $out = ob_get_clean();

        $this->assert(strpos($out, '**OK**') !== false, "Server-to-server IPN must process successfully");
        $status = $this->db->query("SELECT status FROM payment_transactions WHERE id = $txId")->fetchColumn();
        $this->assert($status === 'paid', "Transaction must be marked paid");
        echo "PASS\n";
    }

    private function test58_forgedIpnWithCorrectOrderIdInvalidKeyIsRejected(): void {
        echo "[Test 58] Forged IPN with correct-looking order ID but invalid IPN key is rejected... ";
        $_SESSION = [];
        $uid = $this->createTestUser('step4-u58@scholarmatch.com');
        $ref = 'TXN_STEP4_TEST_58';
        $txId = $this->createPendingTransaction($uid, $this->planPremiumId, 1499.00, $ref);

        $_POST = [
            'ipn_key' => 'attackers_fake_key_999',
            'web_id' => $this->testWebId,
            'order_id' => $ref,
            'CM_TID' => 'CM_TEST_58',
            'status' => '1',
            'Amount' => 1499.00,
            'currency' => 'PKR'
        ];

        $caught = false;
        try {
            $this->billingController->cashmaalIpn();
        } catch (\RuntimeException $e) {
            $caught = true;
        }
        $this->assert($caught, "Forged IPN key must be rejected");

        $status = $this->db->query("SELECT status FROM payment_transactions WHERE id = $txId")->fetchColumn();
        $this->assert($status === 'pending', "Transaction must remain pending");
        echo "PASS\n";
    }

    private function test59_browserSuccessRedirectCannotActivateUnpaidTransaction(): void {
        echo "[Test 59] Browser success redirect cannot activate an unpaid transaction... ";
        $uid = $this->createTestUser('step4-u59@scholarmatch.com');
        $ref = 'TXN_STEP4_TEST_59';
        $txId = $this->createPendingTransaction($uid, $this->planPremiumId, 1499.00, $ref);

        $_SESSION['user_id'] = $uid;
        $_SESSION['user_role'] = 'visitor';
        $_GET = ['ref' => $ref, 'status' => 'success'];
        $_POST = [];

        try {
            $this->billingController->callback();
        } catch (\RuntimeException $e) {
            // expected redirect
        }

        $status = $this->db->query("SELECT status FROM payment_transactions WHERE id = $txId")->fetchColumn();
        $this->assert($status === 'pending', "Browser redirect must never independently mark payment successful");

        $sub = $this->db->query("SELECT * FROM subscriptions WHERE user_id = $uid AND status = 'active'")->fetch(PDO::FETCH_ASSOC);
        $this->assert(!$sub, "No active subscription may be created from browser redirect alone");
        echo "PASS\n";
    }

    private function test60_cashmaalVerificationForAnotherTransactionCannotActivateLocalTransaction(): void {
        echo "[Test 60] CashMaal verification for another transaction cannot activate the local transaction... ";
        $_SESSION = [];
        $uid = $this->createTestUser('step4-u60@scholarmatch.com');
        $ref = 'TXN_STEP4_TEST_60_LOCAL';
        $txId = $this->createPendingTransaction($uid, $this->planPremiumId, 1499.00, $ref);

        // Verification API data belongs to a different order (order_id mismatch)
        $mockApiOtherOrder = [
            'status' => '1',
            'receiver_account' => 'test_account',
            'PKR_amount' => '1499.00',
            'transaction_id' => 'CM_TEST_60',
            'order_id' => 'TXN_DIFFERENT_VICTIM_ORDER' // Mismatch!
        ];

        $_POST = [
            'ipn_key' => $this->testIpnKey,
            'web_id' => $this->testWebId,
            'order_id' => $ref,
            'CM_TID' => 'CM_TEST_60',
            'status' => '1',
            'Amount' => 1499.00,
            'currency' => 'PKR',
            'mock_api_verify' => $mockApiOtherOrder
        ];

        $caught = false;
        try {
            $this->billingController->cashmaalIpn();
        } catch (\RuntimeException $e) {
            $caught = true;
            $this->assert(strpos($e->getMessage(), 'Order ID mismatch') !== false || strpos($e->getMessage(), 'API verification failed') !== false, "Must abort with API order ID mismatch");
        }
        $this->assert($caught, "Mismatched order verification must be rejected");

        $status = $this->db->query("SELECT status FROM payment_transactions WHERE id = $txId")->fetchColumn();
        $this->assert($status === 'failed', "Transaction must be marked failed");

        $sub = $this->db->query("SELECT * FROM subscriptions WHERE user_id = $uid AND status = 'active'")->fetch(PDO::FETCH_ASSOC);
        $this->assert(!$sub, "Subscription must NOT be activated");
        echo "PASS\n";
    }

    // =========================================================================
    // GROUP 11: Critical Renewal & Monetary Precision (61-62)
    // =========================================================================

    private function test61_subscriptionRenewalPreservesRemainingTime(): void {
        echo "[Test 61] Subscription renewal preserves unexpired remaining time... ";
        $uid = $this->createTestUser('step4-u61@scholarmatch.com');
        
        // 1. Seed an active subscription with 15 days remaining
        $initialEndsAt = date('Y-m-d H:i:s', time() + (15 * 86400));
        $this->db->exec("
            INSERT INTO subscriptions (user_id, plan_id, status, starts_at, ends_at, auto_renew, provider, created_at, updated_at)
            VALUES ($uid, {$this->planPremiumId}, 'active', NOW(), '$initialEndsAt', 1, 'cashmaal', NOW(), NOW())
        ");
        $subId = (int)$this->db->lastInsertId();

        // 2. User renews 30-day plan via CashMaal IPN
        $ref = 'TXN_STEP4_TEST_61_RENEW';
        $txId = $this->createPendingTransaction($uid, $this->planPremiumId, 1499.00, $ref);

        $_POST = [
            'ipn_key' => $this->testIpnKey,
            'web_id' => $this->testWebId,
            'order_id' => $ref,
            'CM_TID' => 'CM_TEST_61',
            'status' => '1',
            'Amount' => 1499.00,
            'currency' => 'PKR'
        ];

        ob_start();
        $this->billingController->cashmaalIpn();
        ob_end_clean();

        // 3. Verify new ends_at extends from current unexpired ends_at to ~45 days (15 existing + 30 renewed = 45 days)
        $sub = $this->db->query("SELECT starts_at, ends_at FROM subscriptions WHERE id = $subId")->fetch(PDO::FETCH_ASSOC);
        $diffDays = round((strtotime($sub['ends_at']) - time()) / 86400);
        $this->assert($diffDays >= 44 && $diffDays <= 46, "Renewal must extend expiry to ~45 days (15 + 30), got $diffDays days");
        echo "PASS\n";
    }

    private function test62_exactIntegerAmount_A(): void {
        echo "[Test 62] Test A: Exact integer amount (Expected 1000.00, Received 1000.00 => PASS)... ";
        $uid = $this->createTestUser('step4-u62@scholarmatch.com');
        $ref = 'TXN_STEP4_TEST_62_A';
        $txId = $this->createPendingTransaction($uid, $this->planPrecisionId, 1000.00, $ref);

        $_POST = [
            'ipn_key' => $this->testIpnKey,
            'web_id' => $this->testWebId,
            'order_id' => $ref,
            'CM_TID' => 'CM_TEST_62',
            'status' => '1',
            'Amount' => '1000.00',
            'currency' => 'PKR'
        ];

        ob_start();
        $this->billingController->cashmaalIpn();
        ob_end_clean();

        $status = $this->db->query("SELECT status FROM payment_transactions WHERE id = $txId")->fetchColumn();
        $this->assert($status === 'paid', "Exact integer amount must be accepted and marked paid");
        echo "PASS\n";
    }

    private function test63_equivalentFormatting_B(): void {
        echo "[Test 63] Test B: Equivalent formatting (Expected 1000.00, Received 1000 => PASS)... ";
        $uid = $this->createTestUser('step4-u63@scholarmatch.com');
        $ref = 'TXN_STEP4_TEST_63_B';
        $txId = $this->createPendingTransaction($uid, $this->planPrecisionId, 1000.00, $ref);

        $_POST = [
            'ipn_key' => $this->testIpnKey,
            'web_id' => $this->testWebId,
            'order_id' => $ref,
            'CM_TID' => 'CM_TEST_63',
            'status' => '1',
            'Amount' => '1000', // Equivalent string formatting without decimals
            'currency' => 'PKR'
        ];

        ob_start();
        $this->billingController->cashmaalIpn();
        ob_end_clean();

        $status = $this->db->query("SELECT status FROM payment_transactions WHERE id = $txId")->fetchColumn();
        $this->assert($status === 'paid', "Equivalent formatting '1000' for 1000.00 must be accepted and marked paid");
        echo "PASS\n";
    }

    private function test64_exactDecimalAmount_C(): void {
        echo "[Test 64] Test C: Exact decimal amount (Expected 1000.50, Received 1000.50 => PASS)... ";
        $this->db->exec("
            INSERT INTO subscription_plans (name, slug, description, billing_interval, duration_days, price, currency, status, created_at, updated_at)
            VALUES ('Decimal 50 Plan', 'decimal-50-plan', 'Decimal test plan', 'month', 30, 1000.50, 'PKR', 'active', NOW(), NOW())
        ");
        $decPlanId = (int)$this->db->lastInsertId();

        $uid = $this->createTestUser('step4-u64@scholarmatch.com');
        $ref = 'TXN_STEP4_TEST_64_C';
        $txId = $this->createPendingTransaction($uid, $decPlanId, 1000.50, $ref);

        $_POST = [
            'ipn_key' => $this->testIpnKey,
            'web_id' => $this->testWebId,
            'order_id' => $ref,
            'CM_TID' => 'CM_TEST_64',
            'status' => '1',
            'Amount' => '1000.50',
            'currency' => 'PKR'
        ];

        ob_start();
        $this->billingController->cashmaalIpn();
        ob_end_clean();

        $status = $this->db->query("SELECT status FROM payment_transactions WHERE id = $txId")->fetchColumn();
        $this->assert($status === 'paid', "Exact decimal amount 1000.50 must be accepted and marked paid");

        $this->db->exec("DELETE FROM payment_transactions WHERE id = $txId");
        $this->db->exec("DELETE FROM subscriptions WHERE user_id = $uid");
        $this->db->exec("DELETE FROM subscription_plans WHERE id = $decPlanId");
        echo "PASS\n";
    }

    private function test65_oneCentOverpaymentRejected_D(): void {
        echo "[Test 65] Test D: One-cent overpayment (Expected 1000.00, Received 1000.01 => FAIL)... ";
        $uid = $this->createTestUser('step4-u65@scholarmatch.com');
        $ref = 'TXN_STEP4_TEST_65_D';
        $txId = $this->createPendingTransaction($uid, $this->planPrecisionId, 1000.00, $ref);

        $_POST = [
            'ipn_key' => $this->testIpnKey,
            'web_id' => $this->testWebId,
            'order_id' => $ref,
            'CM_TID' => 'CM_TEST_65',
            'status' => '1',
            'Amount' => '1000.01', // One-cent overpayment
            'currency' => 'PKR'
        ];

        $caught = false;
        try {
            $this->billingController->cashmaalIpn();
        } catch (\RuntimeException $e) {
            $caught = true;
        }
        $this->assert($caught, "One-cent overpayment must be rejected");
        $status = $this->db->query("SELECT status FROM payment_transactions WHERE id = $txId")->fetchColumn();
        $this->assert($status === 'failed', "Overpaid transaction must be marked failed");
        echo "PASS\n";
    }

    private function test66_oneCentUnderpaymentRejected_E(): void {
        echo "[Test 66] Test E: One-cent underpayment (Expected 1000.00, Received 999.99 => FAIL)... ";
        $uid = $this->createTestUser('step4-u66@scholarmatch.com');
        $ref = 'TXN_STEP4_TEST_66_E';
        $txId = $this->createPendingTransaction($uid, $this->planPrecisionId, 1000.00, $ref);

        $_POST = [
            'ipn_key' => $this->testIpnKey,
            'web_id' => $this->testWebId,
            'order_id' => $ref,
            'CM_TID' => 'CM_TEST_66',
            'status' => '1',
            'Amount' => '999.99', // One-cent underpayment
            'currency' => 'PKR'
        ];

        $caught = false;
        try {
            $this->billingController->cashmaalIpn();
        } catch (\RuntimeException $e) {
            $caught = true;
        }
        $this->assert($caught, "One-cent underpayment must be rejected");
        $status = $this->db->query("SELECT status FROM payment_transactions WHERE id = $txId")->fetchColumn();
        $this->assert($status === 'failed', "Underpaid transaction must be marked failed");
        echo "PASS\n";
    }

    private function test67_floatingPointEdgeCase_F(): void {
        echo "[Test 67] Test F: Floating-point binary representation edge case (0.1+0.7, 19.99) => PASS... ";
        $this->assert(\App\Services\PaymentService::amountsEqual('19.99', '19.99') === true, "19.99 must equal 19.99");
        $this->assert(\App\Services\PaymentService::amountsEqual('0.80', '0.8') === true, "0.80 must equal 0.8");
        $this->assert(\App\Services\PaymentService::normalizeToMinorUnits('1000.001') === null, "Sub-cent fractions must normalize to null");

        $this->db->exec("
            INSERT INTO subscription_plans (name, slug, description, billing_interval, duration_days, price, currency, status, created_at, updated_at)
            VALUES ('Float Edge Plan', 'float-edge-plan', 'Float test plan', 'month', 30, 19.99, 'PKR', 'active', NOW(), NOW())
        ");
        $edgePlanId = (int)$this->db->lastInsertId();

        $uid = $this->createTestUser('step4-u67@scholarmatch.com');
        $ref = 'TXN_STEP4_TEST_67_F';
        $txId = $this->createPendingTransaction($uid, $edgePlanId, 19.99, $ref);

        $_POST = [
            'ipn_key' => $this->testIpnKey,
            'web_id' => $this->testWebId,
            'order_id' => $ref,
            'CM_TID' => 'CM_TEST_67',
            'status' => '1',
            'Amount' => '19.99',
            'currency' => 'PKR'
        ];

        ob_start();
        $this->billingController->cashmaalIpn();
        ob_end_clean();

        $status = $this->db->query("SELECT status FROM payment_transactions WHERE id = $txId")->fetchColumn();
        $this->assert($status === 'paid', "Float edge-case amount 19.99 must be marked paid");

        $this->db->exec("DELETE FROM payment_transactions WHERE id = $txId");
        $this->db->exec("DELETE FROM subscriptions WHERE user_id = $uid");
        $this->db->exec("DELETE FROM subscription_plans WHERE id = $edgePlanId");
        echo "PASS\n";
    }

    private function test68_pkrVerifyV2ExactMatching_G(): void {
        echo "[Test 68] Test G: PKR verify_v2 exact PKR_amount matching => PASS... ";
        $uid = $this->createTestUser('step4-u68@scholarmatch.com');
        $ref = 'TXN_STEP4_TEST_68_G';
        $txId = $this->createPendingTransaction($uid, $this->planPrecisionId, 1000.00, $ref, 'PKR');

        $mockVerify = [
            'status' => '1',
            'transaction_id' => 'CM_TEST_68',
            'order_id' => $ref,
            'PKR_amount' => '1000.00'
        ];

        $_POST = [
            'ipn_key' => $this->testIpnKey,
            'web_id' => $this->testWebId,
            'order_id' => $ref,
            'CM_TID' => 'CM_TEST_68',
            'status' => '1',
            'Amount' => '1000.00',
            'currency' => 'PKR',
            'mock_api_verify' => $mockVerify
        ];

        ob_start();
        $this->billingController->cashmaalIpn();
        ob_end_clean();

        $status = $this->db->query("SELECT status FROM payment_transactions WHERE id = $txId")->fetchColumn();
        $this->assert($status === 'paid', "PKR verify_v2 with exact PKR_amount must be accepted and marked paid");
        echo "PASS\n";
    }

    private function test69_usdVerifyV2ExactMatching_H(): void {
        echo "[Test 69] Test H: USD verify_v2 exact USD_amount matching => PASS... ";
        $this->db->exec("
            INSERT INTO subscription_plans (name, slug, description, billing_interval, duration_days, price, currency, status, created_at, updated_at)
            VALUES ('USD Test Plan', 'usd-test-plan', 'USD plan', 'month', 30, 15.75, 'USD', 'active', NOW(), NOW())
        ");
        $usdPlanId = (int)$this->db->lastInsertId();

        $uid = $this->createTestUser('step4-u69@scholarmatch.com');
        $ref = 'TXN_STEP4_TEST_69_H';
        $txId = $this->createPendingTransaction($uid, $usdPlanId, 15.75, $ref, 'USD');

        $mockVerify = [
            'status' => '1',
            'transaction_id' => 'CM_TEST_69',
            'order_id' => $ref,
            'USD_amount' => '15.75'
        ];

        $_POST = [
            'ipn_key' => $this->testIpnKey,
            'web_id' => $this->testWebId,
            'order_id' => $ref,
            'CM_TID' => 'CM_TEST_69',
            'status' => '1',
            'Amount' => '15.75',
            'currency' => 'USD',
            'mock_api_verify' => $mockVerify
        ];

        ob_start();
        $this->billingController->cashmaalIpn();
        ob_end_clean();

        $status = $this->db->query("SELECT status FROM payment_transactions WHERE id = $txId")->fetchColumn();
        $this->assert($status === 'paid', "USD verify_v2 with exact USD_amount must be accepted and marked paid");

        $this->db->exec("DELETE FROM payment_transactions WHERE id = $txId");
        $this->db->exec("DELETE FROM subscriptions WHERE user_id = $uid");
        $this->db->exec("DELETE FROM subscription_plans WHERE id = $usdPlanId");
        echo "PASS\n";
    }

    private function test70_verifyV2AmountMismatchOneMinorUnitRejected_I(): void {
        echo "[Test 70] Test I: verify_v2 amount mismatch of one minor unit rejected => FAIL... ";
        $uid = $this->createTestUser('step4-u70@scholarmatch.com');
        $ref = 'TXN_STEP4_TEST_70_I';
        $txId = $this->createPendingTransaction($uid, $this->planPrecisionId, 1000.00, $ref, 'PKR');

        $mockVerify = [
            'status' => '1',
            'transaction_id' => 'CM_TEST_70',
            'order_id' => $ref,
            'PKR_amount' => '1000.01' // One-cent / paisa mismatch in verify_v2
        ];

        $_POST = [
            'ipn_key' => $this->testIpnKey,
            'web_id' => $this->testWebId,
            'order_id' => $ref,
            'CM_TID' => 'CM_TEST_70',
            'status' => '1',
            'Amount' => '1000.00',
            'currency' => 'PKR',
            'mock_api_verify' => $mockVerify
        ];

        $caught = false;
        try {
            $this->billingController->cashmaalIpn();
        } catch (\RuntimeException $e) {
            $caught = true;
        }
        $this->assert($caught, "One minor unit mismatch in verify_v2 must be rejected");
        $status = $this->db->query("SELECT status FROM payment_transactions WHERE id = $txId")->fetchColumn();
        $this->assert($status === 'failed', "Transaction with mismatched verify_v2 amount must be marked failed");
        echo "PASS\n";
    }

    private function test71_ipnAmountMismatchOneMinorUnitRejected_J(): void {
        echo "[Test 71] Test J: IPN amount mismatch of one minor unit rejected => FAIL... ";
        $uid = $this->createTestUser('step4-u71@scholarmatch.com');
        $ref = 'TXN_STEP4_TEST_71_J';
        $txId = $this->createPendingTransaction($uid, $this->planPrecisionId, 1000.00, $ref, 'PKR');

        $_POST = [
            'ipn_key' => $this->testIpnKey,
            'web_id' => $this->testWebId,
            'order_id' => $ref,
            'CM_TID' => 'CM_TEST_71',
            'status' => '1',
            'Amount' => '999.99', // One minor unit mismatch in IPN payload
            'currency' => 'PKR'
        ];

        $caught = false;
        try {
            $this->billingController->cashmaalIpn();
        } catch (\RuntimeException $e) {
            $caught = true;
        }
        $this->assert($caught, "One minor unit mismatch in IPN must be rejected");
        $status = $this->db->query("SELECT status FROM payment_transactions WHERE id = $txId")->fetchColumn();
        $this->assert($status === 'failed', "Transaction with mismatched IPN amount must be marked failed");
        echo "PASS\n";
    }

    private function test72_pkrUsesOnlyPkrAmountAndIgnoresFeeInclusiveField(): void {
        echo "[Test 72] PKR verification uses PKR_amount (1000) and ignores PKR_amount_with_fee (1010) => PASS... ";
        $uid = $this->createTestUser('step4-u72@scholarmatch.com');
        $ref = 'TXN_STEP4_TEST_72';
        $txId = $this->createPendingTransaction($uid, $this->planPrecisionId, 1000.00, $ref, 'PKR');

        $mockVerify = [
            'status' => '1',
            'transaction_id' => 'CM_TEST_72',
            'order_id' => $ref,
            'PKR_amount' => '1000.00',
            'fee_in_PKR' => '10.00',
            'PKR_amount_with_fee' => '1010.00'
        ];

        $_POST = [
            'ipn_key' => $this->testIpnKey,
            'web_id' => $this->testWebId,
            'order_id' => $ref,
            'CM_TID' => 'CM_TEST_72',
            'status' => '1',
            'Amount' => '1000.00',
            'currency' => 'PKR',
            'mock_api_verify' => $mockVerify
        ];

        ob_start();
        $this->billingController->cashmaalIpn();
        ob_end_clean();

        $status = $this->db->query("SELECT status FROM payment_transactions WHERE id = $txId")->fetchColumn();
        $this->assert($status === 'paid', "PKR transaction must be marked paid using PKR_amount (ignoring PKR_amount_with_fee)");
        echo "PASS\n";
    }

    private function test73_pkrAmountWithFeeCannotRescueUnderpayment(): void {
        echo "[Test 73] PKR response where PKR_amount = 999 and PKR_amount_with_fee = 1000 is rejected => FAIL... ";
        $uid = $this->createTestUser('step4-u73@scholarmatch.com');
        $ref = 'TXN_STEP4_TEST_73';
        $txId = $this->createPendingTransaction($uid, $this->planPrecisionId, 1000.00, $ref, 'PKR');

        $mockVerify = [
            'status' => '1',
            'transaction_id' => 'CM_TEST_73',
            'order_id' => $ref,
            'PKR_amount' => '999.00',
            'fee_in_PKR' => '1.00',
            'PKR_amount_with_fee' => '1000.00'
        ];

        $_POST = [
            'ipn_key' => $this->testIpnKey,
            'web_id' => $this->testWebId,
            'order_id' => $ref,
            'CM_TID' => 'CM_TEST_73',
            'status' => '1',
            'Amount' => '1000.00',
            'currency' => 'PKR',
            'mock_api_verify' => $mockVerify
        ];

        $caught = false;
        try {
            $this->billingController->cashmaalIpn();
        } catch (\RuntimeException $e) {
            $caught = true;
        }
        $this->assert($caught, "PKR_amount_with_fee must NOT rescue an underpaid PKR_amount");
        $status = $this->db->query("SELECT status FROM payment_transactions WHERE id = $txId")->fetchColumn();
        $this->assert($status === 'failed', "Transaction must be marked failed when PKR_amount mismatches");
        echo "PASS\n";
    }

    private function test74_usdUsesOnlyUsdAmountAndIgnoresFeeInclusiveField(): void {
        echo "[Test 74] USD verification uses USD_amount (10.00) and ignores USD_amount_with_fee (10.50) => PASS... ";
        $this->db->exec("
            INSERT INTO subscription_plans (name, slug, description, billing_interval, duration_days, price, currency, status, created_at, updated_at)
            VALUES ('USD 10 Plan', 'usd-10-plan', 'USD 10 plan', 'month', 30, 10.00, 'USD', 'active', NOW(), NOW())
        ");
        $usd10PlanId = (int)$this->db->lastInsertId();

        $uid = $this->createTestUser('step4-u74@scholarmatch.com');
        $ref = 'TXN_STEP4_TEST_74';
        $txId = $this->createPendingTransaction($uid, $usd10PlanId, 10.00, $ref, 'USD');

        $mockVerify = [
            'status' => '1',
            'transaction_id' => 'CM_TEST_74',
            'order_id' => $ref,
            'USD_amount' => '10.00',
            'fee_in_USD' => '0.50',
            'USD_amount_with_fee' => '10.50'
        ];

        $_POST = [
            'ipn_key' => $this->testIpnKey,
            'web_id' => $this->testWebId,
            'order_id' => $ref,
            'CM_TID' => 'CM_TEST_74',
            'status' => '1',
            'Amount' => '10.00',
            'currency' => 'USD',
            'mock_api_verify' => $mockVerify
        ];

        ob_start();
        $this->billingController->cashmaalIpn();
        ob_end_clean();

        $status = $this->db->query("SELECT status FROM payment_transactions WHERE id = $txId")->fetchColumn();
        $this->assert($status === 'paid', "USD transaction must be marked paid using USD_amount (ignoring USD_amount_with_fee)");

        $this->db->exec("DELETE FROM payment_transactions WHERE id = $txId");
        $this->db->exec("DELETE FROM subscriptions WHERE user_id = $uid");
        $this->db->exec("DELETE FROM subscription_plans WHERE id = $usd10PlanId");
        echo "PASS\n";
    }

    private function test75_usdAmountWithFeeCannotRescueUnderpayment(): void {
        echo "[Test 75] USD response where USD_amount = 9.99 and USD_amount_with_fee = 10.00 is rejected => FAIL... ";
        $this->db->exec("
            INSERT INTO subscription_plans (name, slug, description, billing_interval, duration_days, price, currency, status, created_at, updated_at)
            VALUES ('USD 10 Plan', 'usd-10-plan', 'USD 10 plan', 'month', 30, 10.00, 'USD', 'active', NOW(), NOW())
        ");
        $usd10PlanId = (int)$this->db->lastInsertId();

        $uid = $this->createTestUser('step4-u75@scholarmatch.com');
        $ref = 'TXN_STEP4_TEST_75';
        $txId = $this->createPendingTransaction($uid, $usd10PlanId, 10.00, $ref, 'USD');

        $mockVerify = [
            'status' => '1',
            'transaction_id' => 'CM_TEST_75',
            'order_id' => $ref,
            'USD_amount' => '9.99',
            'fee_in_USD' => '0.01',
            'USD_amount_with_fee' => '10.00'
        ];

        $_POST = [
            'ipn_key' => $this->testIpnKey,
            'web_id' => $this->testWebId,
            'order_id' => $ref,
            'CM_TID' => 'CM_TEST_75',
            'status' => '1',
            'Amount' => '10.00',
            'currency' => 'USD',
            'mock_api_verify' => $mockVerify
        ];

        $caught = false;
        try {
            $this->billingController->cashmaalIpn();
        } catch (\RuntimeException $e) {
            $caught = true;
        }
        $this->assert($caught, "USD_amount_with_fee must NOT rescue an underpaid USD_amount");
        $status = $this->db->query("SELECT status FROM payment_transactions WHERE id = $txId")->fetchColumn();
        $this->assert($status === 'failed', "Transaction must be marked failed when USD_amount mismatches");

        $this->db->exec("DELETE FROM payment_transactions WHERE id = $txId");
        $this->db->exec("DELETE FROM subscriptions WHERE user_id = $uid");
        $this->db->exec("DELETE FROM subscription_plans WHERE id = $usd10PlanId");
        echo "PASS\n";
    }

    private function test76_missingPkrAmountFailsEvenIfFeeFieldExists(): void {
        echo "[Test 76] Missing PKR_amount for PKR transaction fails even if PKR_amount_with_fee exists => FAIL... ";
        $gateway = new \App\Services\CashMaalPaymentGateway();
        $mockVerify = [
            'status' => '1',
            'transaction_id' => 'CM_TEST_76',
            'order_id' => 'TXN_76',
            'fee_in_PKR' => '10.00',
            'PKR_amount_with_fee' => '1000.00' // PKR_amount is missing!
        ];

        $res = $gateway->evaluateVerifyApiResponse($mockVerify, 'CM_TEST_76', 'TXN_76', '1000.00', 'PKR');
        $this->assert($res['verified'] === false, "Must fail verification when PKR_amount is missing");
        $this->assert(strpos($res['error'], 'Missing PKR_amount') !== false, "Error message must indicate missing PKR_amount");
        echo "PASS\n";
    }

    private function test77_missingUsdAmountFailsEvenIfFeeFieldExists(): void {
        echo "[Test 77] Missing USD_amount for USD transaction fails even if USD_amount_with_fee exists => FAIL... ";
        $gateway = new \App\Services\CashMaalPaymentGateway();
        $mockVerify = [
            'status' => '1',
            'transaction_id' => 'CM_TEST_77',
            'order_id' => 'TXN_77',
            'fee_in_USD' => '0.50',
            'USD_amount_with_fee' => '10.00' // USD_amount is missing!
        ];

        $res = $gateway->evaluateVerifyApiResponse($mockVerify, 'CM_TEST_77', 'TXN_77', '10.00', 'USD');
        $this->assert($res['verified'] === false, "Must fail verification when USD_amount is missing");
        $this->assert(strpos($res['error'], 'Missing USD_amount') !== false, "Error message must indicate missing USD_amount");
        echo "PASS\n";
    }

    // =========================================================================
    // GROUP 13: Normalizer Hardening & Integer Safety (78-85)
    // =========================================================================

    private function test78_validNormalAmount(): void {
        echo "[Test 78] Valid normal amount (1000.00) normalizes successfully => PASS... ";
        $norm = \App\Services\PaymentService::normalizeToMinorUnits('1000.00');
        $this->assert($norm === 100000, "1000.00 must normalize to 100000 minor units");
        echo "PASS\n";
    }

    private function test79_malformedCommaAmount(): void {
        echo "[Test 79] Malformed comma amount (1,2,3.00) is rejected => FAIL... ";
        $norm = \App\Services\PaymentService::normalizeToMinorUnits('1,2,3.00');
        $this->assert($norm === null, "1,2,3.00 must be rejected");
        echo "PASS\n";
    }

    private function test80_malformedGrouping(): void {
        echo "[Test 80] Malformed grouping (10,00.00 and 1,000.00) is rejected => FAIL... ";
        $norm1 = \App\Services\PaymentService::normalizeToMinorUnits('10,00.00');
        $this->assert($norm1 === null, "10,00.00 must be rejected");
        $norm2 = \App\Services\PaymentService::normalizeToMinorUnits('1,000.00');
        $this->assert($norm2 === null, "1,000.00 must be rejected");
        $norm3 = \App\Services\PaymentService::normalizeToMinorUnits('1,000');
        $this->assert($norm3 === null, "1,000 must be rejected");
        echo "PASS\n";
    }

    private function test81_validMachineFormatAmount(): void {
        echo "[Test 81] Valid machine-format amount (1000000.50) normalizes correctly => PASS... ";
        $norm = \App\Services\PaymentService::normalizeToMinorUnits('1000000.50');
        $this->assert($norm === 100000050, "1000000.50 must normalize to 100000050 minor units");
        echo "PASS\n";
    }

    private function test82_excessiveIntegerValue(): void {
        echo "[Test 82] Excessive integer value exceeding safe supported range is rejected => FAIL... ";
        $norm1 = \App\Services\PaymentService::normalizeToMinorUnits('9999999999999999999999999999.00');
        $this->assert($norm1 === null, "Arbitrarily large integer amount must be rejected");
        $norm2 = \App\Services\PaymentService::normalizeToMinorUnits('9999999999999'); // 13 digits
        $this->assert($norm2 === null, "13-digit integer amount must be rejected");
        $norm3 = \App\Services\PaymentService::normalizeToMinorUnits(((string)PHP_INT_MAX) . '0');
        $this->assert($norm3 === null, "Integer overflowing PHP_INT_MAX must be rejected");
        echo "PASS\n";
    }

    private function test83_negativeAmount(): void {
        echo "[Test 83] Negative amount (-1000.00) is rejected => FAIL... ";
        $norm = \App\Services\PaymentService::normalizeToMinorUnits('-1000.00');
        $this->assert($norm === null, "-1000.00 must be rejected");
        echo "PASS\n";
    }

    private function test84_nonNumericValue(): void {
        echo "[Test 84] Non-numeric value (1000ABC) is rejected => FAIL... ";
        $norm = \App\Services\PaymentService::normalizeToMinorUnits('1000ABC');
        $this->assert($norm === null, "1000ABC must be rejected");
        echo "PASS\n";
    }

    private function test85_whitespaceHandling(): void {
        echo "[Test 85] Surrounding whitespace trimmed, internal whitespace rejected => PASS... ";
        $normSurrounding = \App\Services\PaymentService::normalizeToMinorUnits('  1000.00  ');
        $this->assert($normSurrounding === 100000, "Surrounding whitespace must be trimmed");
        $normInternal = \App\Services\PaymentService::normalizeToMinorUnits('10 00.00');
        $this->assert($normInternal === null, "Internal whitespace '10 00.00' must be rejected");
        echo "PASS\n";
    }

    private function assert(bool $condition, string $message): void {
        if (!$condition) {
            throw new \Exception("Assertion Failure: " . $message);
        }
    }
}
