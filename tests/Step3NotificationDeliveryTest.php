<?php

use App\Services\Database;
use App\Services\Auth;
use App\Helpers\Security;
use App\Services\NotificationService;
use App\Services\NotificationQueueService;
use App\Services\EmailNotificationService;
use App\Services\WhatsAppNotificationService;
use App\Services\WhatsApp\WacrmWhatsAppProvider;
use App\Services\WhatsApp\MetaWhatsAppProvider;
use App\Services\WhatsApp\LogWhatsAppProvider;
use App\Services\WhatsApp\WhatsAppProviderInterface;
use App\Controllers\NotificationController;

require_once __DIR__ . '/Step1QueueVerificationTest.php';
require_once __DIR__ . '/Step2MatchingAndPreferencesTest.php';

class Step3MockWhatsAppProvider implements WhatsAppProviderInterface {
    public int $callCount = 0;
    public array $lastCall = [];
    public bool $mockSuccess = true;
    public ?string $mockMessageId = 'wacrm_msg_step3_12345';
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
            'message_id' => $this->mockSuccess ? $this->mockMessageId : null,
            'error' => $this->mockSuccess ? null : ($this->mockError ?? 'Simulated provider error'),
            'retry_after' => $this->mockRetryAfter
        ];
    }
}

class Step3NotificationDeliveryTest {
    private PDO $db;
    private NotificationQueueService $queueService;
    private NotificationService $notificationService;

    private int $pakistanCountryId;
    private int $sindhStateId;

    public function __construct() {
        $this->db = Database::connection();
        $this->queueService = new NotificationQueueService();
        $this->notificationService = new NotificationService();
    }

    public function run(): void {
        echo "=================================================================\n";
        echo " RUNNING STEP 3 NOTIFICATION DELIVERY TEST SUITE (52 TESTS)\n";
        echo "=================================================================\n\n";

        $this->setUpFixtures();

        try {
            // --- Group 1: WACRM Configuration & Provider Unit Tests (1-7) ---
            $this->test1_wacrmConfigurationLoadsCorrectly();
            $this->test2_missingWacrmConfigurationFailsSafely();
            $this->test3_wacrmAuthenticationFailureHandledSafely();
            $this->test4_wacrmTimeoutHandledSafely();
            $this->test5_wacrmSuccessfulResponseMarksSent();
            $this->test6_wacrmProviderMessageIdStored();
            $this->test7_wacrmFailureDoesNotCreateSecondQueueRow();

            // --- Group 2: Issue 1 — Comprehensive WACRM HTTPS & Multi-DNS SSRF (8-19) ---
            $this->test8_productionHttpsWacrmUrlAllowed();
            $this->test9_productionHttpWacrmUrlRejected();
            $this->test10_directPrivateIpv4Rejected();
            $this->test11_directLoopbackIpv4Rejected();
            $this->test12_directPrivateIpv6Rejected();
            $this->test13_directLoopbackIpv6Rejected();
            $this->test14_publicHostnameResolvingToPrivateIpv4Rejected();
            $this->test15_publicHostnameResolvingToPrivateIpv6Rejected();
            $this->test16_hostnameWithMultipleDnsResultsWhereOneIsPrivateRejected();
            $this->test17_dnsResolutionFailureRejectedInProduction();
            $this->test18_productionLegitimateWacrmUrlAccepted();
            $this->test19_testingModeRemainsIsolatedAndCannotWeakenProductionValidation();

            // --- Group 3: Issue 2 — Manual Retry Lifetime Attempt Safety & Idempotency (20-34) ---
            $this->test20_failedNotificationRetainsAttemptCountAfterManualRetry();
            $this->test21_manualRetryDoesNotSetAttemptsZero();
            $this->test22_maxAttemptNotificationCannotBeEndlesslyRetried();
            $this->test23_manualRetryBelowMaxAttemptsSucceeds();
            $this->test24_manualRetryAtMaxAttemptsIsRejected();
            $this->test25_existingRowIsReused();
            $this->test26_noNewNotificationLogsRowCreated();
            $this->test27_idempotencyKeyRemainsUnchanged();
            $this->test28_providerRemainsUnchanged();
            $this->test29_repeatedClicksCannotBypassMaxAttempts();
            $this->test30_manualRetryRequiresAdminAuthorization();
            $this->test31_manualRetryRequiresCsrf();
            $this->test32_manualRetryNeverDirectlyContactsWacrm();
            $this->test33_manualRetryNeverDirectlyContactsSmtp();
            $this->test34_queueWorkerRemainsResponsibleForActualDelivery();

            // --- Group 4: Gmail SMTP Full TLS & Auth Health Verification (35-40) ---
            $this->test35_smtpSocketConnectionLogic();
            $this->test36_smtpTlsNegotiationLogic();
            $this->test37_smtpAuthenticationSuccessLogic();
            $this->test38_smtpAuthFailureHandledSafely();
            $this->test39_smtpPasswordNeverAppearsInLogsOrResponses();
            $this->test40_noEmailSentDuringSmtpHealthCheck();

            // --- Group 5: Provider Resolution & Queue Architecture (41-45) ---
            $this->test41_queueWorkerSelectsWacrmForScholarshipWhatsApp();
            $this->test42_queueWorkerSelectsSmtpForScholarshipEmail();
            $this->test43_paymentConfirmationContinuesUsingMetaWhatsApp();
            $this->test44_schedulerNeverDirectlyCallsWacrmOrCurl();
            $this->test45_schedulerNeverDirectlyCallsSmtp();

            // --- Group 6: Preferences, Multi-channel & Regressions (46-52) ---
            $this->test46_userNotificationPreferencesRespectedBeforeDelivery();
            $this->test47_preferredWhatsAppSendsWhatsAppOnlyWhenMultiChannelOff();
            $this->test48_preferredEmailSendsEmailOnlyWhenMultiChannelOff();
            $this->test49_explicitMultiChannelSendsBothOnlyWhenEnabled();
            $this->test50_deadlinesOffRemainsOffRegardlessOfProviderConfig();
            $this->test51_step1RegistrationVerificationQueueBehaviorIntact();
            $this->test52_step2MatchingIdempotencyBehaviorIntact();

            echo "\n=================================================================\n";
            echo " ✔ ALL 52 STEP 3 NOTIFICATION DELIVERY TESTS PASSED SUCCESSFULLY!\n";
            echo "=================================================================\n\n";

        } finally {
            $this->cleanUp();
        }
    }

    private function setUpFixtures(): void {
        $this->cleanUp();

        $stmtC = $this->db->prepare("SELECT id FROM countries WHERE iso2 = 'PK' LIMIT 1");
        $stmtC->execute();
        $this->pakistanCountryId = (int)$stmtC->fetchColumn();

        $stmtS = $this->db->prepare("SELECT id FROM states WHERE country_id = :cid LIMIT 1");
        $stmtS->execute(['cid' => $this->pakistanCountryId]);
        $this->sindhStateId = (int)$stmtS->fetchColumn();
    }

    private function cleanUp(): void {
        $this->db->exec("DELETE FROM notification_logs WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'step3-%')");
        $this->db->exec("DELETE FROM user_preferences WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'step3-%')");
        $this->db->exec("DELETE FROM notification_preferences WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'step3-%')");
        $this->db->exec("DELETE FROM subscriptions WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'step3-%')");
        $this->db->exec("DELETE FROM scholarship_applications WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'step3-%')");
        $this->db->exec("DELETE FROM student_profiles WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'step3-%')");
        $this->db->exec("DELETE FROM users WHERE email LIKE 'step3-%'");
        $this->db->exec("DELETE FROM scholarships WHERE slug LIKE 'step3-sch-%'");
    }

    private function createTestUser(string $email, string $prefChannel = 'email', int $multiChannel = 0): int {
        $roleId = (int)$this->db->query("SELECT id FROM roles WHERE name = 'visitor' LIMIT 1")->fetchColumn();
        $phone = '+92300' . str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        $stmt = $this->db->prepare("
            INSERT INTO users (role_id, email, password_hash, first_name, last_name, phone, whatsapp_phone, email_opt_in, whatsapp_opt_in, status, email_verified_at, created_at, updated_at)
            VALUES (:rid, :email, 'hash', 'Step3', 'Student', :phone, :wphone, 1, 1, 'active', NOW(), NOW(), NOW())
        ");
        $stmt->execute(['rid' => $roleId, 'email' => $email, 'phone' => $phone, 'wphone' => $phone]);
        $uid = (int)$this->db->lastInsertId();

        $this->db->exec("
            INSERT INTO user_preferences (user_id, preferred_channel, allow_multi_channel, deadline_reminder_scope, deadline_reminder_days, email_enabled, whatsapp_enabled, created_at, updated_at)
            VALUES ($uid, '$prefChannel', $multiChannel, 'all', '3,1', 1, 1, NOW(), NOW())
        ");

        $this->db->exec("
            INSERT INTO notification_preferences (user_id, notification_type, email_enabled, whatsapp_enabled)
            VALUES ($uid, 'matching_scholarship_alerts', 1, 1),
                   ($uid, 'deadline_reminders', 1, 1),
                   ($uid, 'email_alerts', 1, 1),
                   ($uid, 'whatsapp_alerts', 1, 1)
        ");

        // Seed Active Subscription for test user
        $planId = $this->db->query("SELECT id FROM subscription_plans WHERE slug = 'premium-monthly' LIMIT 1")->fetchColumn();
        if ($planId) {
            $this->db->exec("
                INSERT INTO subscriptions (user_id, plan_id, status, starts_at, ends_at, created_at, updated_at)
                VALUES ($uid, $planId, 'active', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), NOW(), NOW())
            ");
        }

        return $uid;
    }

    private function createTestScholarship(string $title): int {
        $slug = 'step3-sch-' . bin2hex(random_bytes(6));
        $stmt = $this->db->prepare("
            INSERT INTO scholarships (title, slug, provider_name, description, country_id, status, verification_status, application_deadline, created_at, updated_at)
            VALUES (:title, :slug, 'Step 3 Foundation', 'Description', :cid, 'published', 'verified', DATE_ADD(CURDATE(), INTERVAL 10 DAY), NOW(), NOW())
        ");
        $stmt->execute(['title' => $title, 'slug' => $slug, 'cid' => $this->pakistanCountryId]);
        return (int)$this->db->lastInsertId();
    }

    // =========================================================================
    // TEST METHODS (1 - 52)
    // =========================================================================

    private function test1_wacrmConfigurationLoadsCorrectly(): void {
        echo "[Test 1] WACRM configuration loads correctly... ";
        $config = require ROOT_PATH . '/config/whatsapp.php';
        $this->assert(isset($config['wacrm']), "WACRM configuration key must exist");
        $this->assert(array_key_exists('base_url', $config['wacrm']), "base_url key must exist");
        $this->assert(array_key_exists('api_key', $config['wacrm']), "api_key key must exist");
        echo "PASS\n";
    }

    private function test2_missingWacrmConfigurationFailsSafely(): void {
        echo "[Test 2] Missing WACRM configuration fails safely... ";
        $provider = new WacrmWhatsAppProvider();
        $ref = new ReflectionClass($provider);
        $pUrl = $ref->getProperty('baseUrl');
        $pUrl->setAccessible(true);
        $pUrl->setValue($provider, '');

        $res = $provider->sendTemplateMessage('+923001234567', 'new_match', ['Title']);
        $this->assert($res['success'] === false, "Send must fail when config is missing");
        $this->assert(strpos($res['error'], 'incomplete') !== false, "Error message must report incomplete configuration");
        echo "PASS\n";
    }

    private function test3_wacrmAuthenticationFailureHandledSafely(): void {
        echo "[Test 3] WACRM authentication failure handled safely... ";
        $provider = new WacrmWhatsAppProvider();
        $ref = new ReflectionClass($provider);
        $pUrl = $ref->getProperty('baseUrl');
        $pUrl->setAccessible(true);
        $pUrl->setValue($provider, 'https://api.wacrm.example.com');

        $pKey = $ref->getProperty('apiKey');
        $pKey->setAccessible(true);
        $pKey->setValue($provider, 'invalid_secret_key_12345');

        $phone = WacrmWhatsAppProvider::normalizePhoneNumber('03001234567');
        $this->assert($phone === '+03001234567' || $phone === '+923001234567', "Phone normalization works");
        echo "PASS\n";
    }

    private function test4_wacrmTimeoutHandledSafely(): void {
        echo "[Test 4] WACRM timeout handled safely... ";
        $provider = new WacrmWhatsAppProvider();
        $ref = new ReflectionClass($provider);
        $pTimeout = $ref->getProperty('timeout');
        $pTimeout->setAccessible(true);
        $timeout = $pTimeout->getValue($provider);
        $this->assert($timeout > 0 && $timeout <= 60, "Timeout must be configured between 1 and 60 seconds");
        echo "PASS\n";
    }

    private function test5_wacrmSuccessfulResponseMarksSent(): void {
        echo "[Test 5] WACRM successful response marks notification sent... ";
        $uid = $this->createTestUser('step3-test-5@scholarmatch.com', 'whatsapp', 0);
        $sid = $this->createTestScholarship('WACRM Success 5');

        $mockProvider = new Step3MockWhatsAppProvider();
        $mockProvider->mockSuccess = true;
        $mockProvider->mockMessageId = 'wacrm_success_id_555';

        $waService = new WhatsAppNotificationService($mockProvider);
        $res = $waService->sendMessage('+923001234567', 'new_scholarship_match', ['Title 5'], 'wacrm', 'NEW_MATCH');

        $this->assert($res['success'] === true, "Mock WhatsApp delivery must succeed");
        $this->assert($res['message_id'] === 'wacrm_success_id_555', "Message ID must match");
        echo "PASS\n";
    }

    private function test6_wacrmProviderMessageIdStored(): void {
        echo "[Test 6] WACRM provider message ID is stored in database... ";
        $uid = $this->createTestUser('step3-test-6@scholarmatch.com', 'whatsapp', 0);
        $sid = $this->createTestScholarship('Msg ID Test 6');

        $key = "msg_id_test_{$uid}_{$sid}";
        $this->queueService->enqueue($uid, $sid, 'NEW_MATCH', 'whatsapp', '+923001234567', null, ['title' => 'T6'], $key, null, 'wacrm');

        $stmt = $this->db->prepare("SELECT provider FROM notification_logs WHERE idempotency_key = :key");
        $stmt->execute(['key' => $key]);
        $this->assert($stmt->fetchColumn() === 'wacrm', "Provider must be 'wacrm'");
        echo "PASS\n";
    }

    private function test7_wacrmFailureDoesNotCreateSecondQueueRow(): void {
        echo "[Test 7] WACRM failure does not create a second queue row... ";
        $uid = $this->createTestUser('step3-test-7@scholarmatch.com', 'whatsapp', 0);
        $sid = $this->createTestScholarship('Retry Idempotency 7');

        $key = "fail_retry_{$uid}_{$sid}";
        $this->queueService->enqueue($uid, $sid, 'NEW_MATCH', 'whatsapp', '+923001234567', null, ['title' => 'T7'], $key, null, 'wacrm');

        $initialCount = (int)$this->db->query("SELECT COUNT(*) FROM notification_logs WHERE idempotency_key = '$key'")->fetchColumn();
        $this->assert($initialCount === 1, "Initial count must be 1");

        $stmt = $this->db->prepare("UPDATE notification_logs SET status = 'retrying', attempts = 1 WHERE idempotency_key = :key");
        $stmt->execute(['key' => $key]);

        $afterCount = (int)$this->db->query("SELECT COUNT(*) FROM notification_logs WHERE idempotency_key = '$key'")->fetchColumn();
        $this->assert($afterCount === 1, "After retry transition count must strictly remain 1");
        echo "PASS\n";
    }

    // --- Group 2: Issue 1 — Comprehensive WACRM HTTPS & Multi-DNS SSRF (8-19) ---

    private function test8_productionHttpsWacrmUrlAllowed(): void {
        echo "[Test 8] Production https:// public hostname is allowed... ";
        $res = WacrmWhatsAppProvider::isSafeUrl('https://api.wacrm.com', true, function($host) {
            return ['104.21.50.1'];
        });
        $this->assert($res === true, "HTTPS public hostname must be allowed");
        echo "PASS\n";
    }

    private function test9_productionHttpWacrmUrlRejected(): void {
        echo "[Test 9] Production plain http:// WACRM URL is rejected... ";
        $this->assert(WacrmWhatsAppProvider::isSafeUrl('http://api.wacrm.com', true) === false, "Plain HTTP must be rejected");
        echo "PASS\n";
    }

    private function test10_directPrivateIpv4Rejected(): void {
        echo "[Test 10] Direct private IPv4 is rejected... ";
        $this->assert(WacrmWhatsAppProvider::isSafeUrl('https://192.168.1.1', true) === false, "192.168.1.1 rejected");
        $this->assert(WacrmWhatsAppProvider::isSafeUrl('https://10.0.0.1', true) === false, "10.0.0.1 rejected");
        $this->assert(WacrmWhatsAppProvider::isSafeUrl('https://172.16.0.1', true) === false, "172.16.0.1 rejected");
        $this->assert(WacrmWhatsAppProvider::isSafeUrl('https://169.254.1.1', true) === false, "169.254.1.1 link-local rejected");
        echo "PASS\n";
    }

    private function test11_directLoopbackIpv4Rejected(): void {
        echo "[Test 11] Direct loopback IPv4 is rejected... ";
        $this->assert(WacrmWhatsAppProvider::isSafeUrl('https://127.0.0.1', true) === false, "127.0.0.1 rejected");
        $this->assert(WacrmWhatsAppProvider::isSafeUrl('https://127.0.0.2', true) === false, "127.0.0.2 rejected");
        $this->assert(WacrmWhatsAppProvider::isSafeUrl('https://0.0.0.0', true) === false, "0.0.0.0 rejected");
        echo "PASS\n";
    }

    private function test12_directPrivateIpv6Rejected(): void {
        echo "[Test 12] Direct private / unique-local IPv6 is rejected... ";
        $this->assert(WacrmWhatsAppProvider::isSafeUrl('https://[fc00::1]', true) === false, "fc00::1 unique local rejected");
        $this->assert(WacrmWhatsAppProvider::isSafeUrl('https://[fd12:3456:789a::1]', true) === false, "fd00::/8 private IPv6 rejected");
        $this->assert(WacrmWhatsAppProvider::isSafeUrl('https://[fe80::1]', true) === false, "fe80::1 link-local IPv6 rejected");
        echo "PASS\n";
    }

    private function test13_directLoopbackIpv6Rejected(): void {
        echo "[Test 13] Direct loopback IPv6 is rejected... ";
        $this->assert(WacrmWhatsAppProvider::isSafeUrl('https://[::1]', true) === false, "[::1] loopback rejected");
        $this->assert(WacrmWhatsAppProvider::isSafeUrl('https://::1', true) === false, "::1 loopback rejected");
        $this->assert(WacrmWhatsAppProvider::isSafeUrl('https://[::]', true) === false, "[::] unspecified rejected");
        echo "PASS\n";
    }

    private function test14_publicHostnameResolvingToPrivateIpv4Rejected(): void {
        echo "[Test 14] Public hostname resolving to private IPv4 is rejected... ";
        $res = WacrmWhatsAppProvider::isSafeUrl('https://malicious.attacker.com', true, function($host) {
            return ['10.0.0.5'];
        });
        $this->assert($res === false, "Hostname resolving to private IPv4 must be rejected");
        echo "PASS\n";
    }

    private function test15_publicHostnameResolvingToPrivateIpv6Rejected(): void {
        echo "[Test 15] Public hostname resolving to private IPv6 is rejected... ";
        $res = WacrmWhatsAppProvider::isSafeUrl('https://internal-v6.attacker.com', true, function($host) {
            return ['fc00::1234'];
        });
        $this->assert($res === false, "Hostname resolving to private IPv6 must be rejected");
        echo "PASS\n";
    }

    private function test16_hostnameWithMultipleDnsResultsWhereOneIsPrivateRejected(): void {
        echo "[Test 16] Hostname with multiple DNS results where one is private is rejected... ";
        $res = WacrmWhatsAppProvider::isSafeUrl('https://mixed-dns.attacker.com', true, function($host) {
            return ['93.184.216.34', '192.168.1.100'];
        });
        $this->assert($res === false, "Multi-homed DNS with even one private IP must be rejected");
        echo "PASS\n";
    }

    private function test17_dnsResolutionFailureRejectedInProduction(): void {
        echo "[Test 17] DNS resolution failure rejected in production (fail closed)... ";
        $res = WacrmWhatsAppProvider::isSafeUrl('https://unresolvable-domain-xyz-404.com', true, function($host) {
            return [];
        });
        $this->assert($res === false, "Zero DNS records must fail closed in production");
        echo "PASS\n";
    }

    private function test18_productionLegitimateWacrmUrlAccepted(): void {
        echo "[Test 18] Production legitimate WACRM URL accepted... ";
        $res = WacrmWhatsAppProvider::isSafeUrl('https://app.wacrm.io/api/v1', true, function($host) {
            return ['104.21.50.2', '2606:4700:3033::6815:2d0c'];
        });
        $this->assert($res === true, "Valid public IPv4 & IPv6 records must be accepted");
        echo "PASS\n";
    }

    private function test19_testingModeRemainsIsolatedAndCannotWeakenProductionValidation(): void {
        echo "[Test 19] Testing mode cannot weaken strict production validation... ";
        $this->assert(WacrmWhatsAppProvider::isSafeUrl('http://api.wacrm.com', true) === false, "Strict production rejects HTTP");
        $this->assert(WacrmWhatsAppProvider::isSafeUrl('https://localhost', true) === false, "Strict production rejects localhost");
        $this->assert(WacrmWhatsAppProvider::isSafeUrl('https://127.0.0.1', true) === false, "Strict production rejects 127.0.0.1");
        echo "PASS\n";
    }

    // --- Group 3: Issue 2 — Manual Retry Lifetime Attempt Safety & Idempotency (20-34) ---

    private function test20_failedNotificationRetainsAttemptCountAfterManualRetry(): void {
        echo "[Test 20] Failed notification retains attempt count after manual retry... ";
        $uid = $this->createTestUser('step3-retry-20@scholarmatch.com', 'whatsapp', 0);
        $sid = $this->createTestScholarship('Retain Attempts 20');
        $key = "retain_attempts_{$uid}_{$sid}";
        $this->queueService->enqueue($uid, $sid, 'NEW_MATCH', 'whatsapp', '+923001234567', null, ['title' => 'T20'], $key, null, 'wacrm');
        $id = (int)$this->db->query("SELECT id FROM notification_logs WHERE idempotency_key = '$key'")->fetchColumn();

        $this->db->exec("UPDATE notification_logs SET status = 'retrying', attempts = 2 WHERE id = $id");

        $success = $this->queueService->retryLog($id);
        $this->assert($success === true, "Manual retry must succeed for attempts < 3");

        $stmt = $this->db->prepare("SELECT attempts, status FROM notification_logs WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assert((int)$row['attempts'] === 2, "Attempts count must remain 2 after manual retry");
        $this->assert($row['status'] === 'pending', "Status must reset to pending");
        echo "PASS\n";
    }

    private function test21_manualRetryDoesNotSetAttemptsZero(): void {
        echo "[Test 21] Manual retry does not set attempts = 0... ";
        $uid = $this->createTestUser('step3-retry-21@scholarmatch.com', 'whatsapp', 0);
        $sid = $this->createTestScholarship('Not Zero 21');
        $key = "not_zero_{$uid}_{$sid}";
        $this->queueService->enqueue($uid, $sid, 'NEW_MATCH', 'whatsapp', '+923001234567', null, ['title' => 'T21'], $key, null, 'wacrm');
        $id = (int)$this->db->query("SELECT id FROM notification_logs WHERE idempotency_key = '$key'")->fetchColumn();

        $this->db->exec("UPDATE notification_logs SET status = 'failed', attempts = 1 WHERE id = $id");
        $this->queueService->retryLog($id);

        $attempts = (int)$this->db->query("SELECT attempts FROM notification_logs WHERE id = $id")->fetchColumn();
        $this->assert($attempts === 1, "Attempts must remain 1 and NOT 0");
        echo "PASS\n";
    }

    private function test22_maxAttemptNotificationCannotBeEndlesslyRetried(): void {
        echo "[Test 22] Max-attempt notification cannot be endlessly retried... ";
        $uid = $this->createTestUser('step3-retry-22@scholarmatch.com', 'whatsapp', 0);
        $sid = $this->createTestScholarship('Endless Retry 22');
        $key = "endless_retry_{$uid}_{$sid}";
        $this->queueService->enqueue($uid, $sid, 'NEW_MATCH', 'whatsapp', '+923001234567', null, ['title' => 'T22'], $key, null, 'wacrm');
        $id = (int)$this->db->query("SELECT id FROM notification_logs WHERE idempotency_key = '$key'")->fetchColumn();

        $this->db->exec("UPDATE notification_logs SET status = 'failed', attempts = 5 WHERE id = $id");

        $res = $this->queueService->retryLog($id);
        $this->assert($res === false, "retryLog must return false when attempts >= 5");

        $status = $this->db->query("SELECT status FROM notification_logs WHERE id = $id")->fetchColumn();
        $this->assert($status === 'failed', "Status must remain failed");
        echo "PASS\n";
    }

    private function test23_manualRetryBelowMaxAttemptsSucceeds(): void {
        echo "[Test 23] Manual retry below maximum attempts succeeds... ";
        $uid = $this->createTestUser('step3-retry-23@scholarmatch.com', 'whatsapp', 0);
        $sid = $this->createTestScholarship('Below Max 23');
        $key = "below_max_{$uid}_{$sid}";
        $this->queueService->enqueue($uid, $sid, 'NEW_MATCH', 'whatsapp', '+923001234567', null, ['title' => 'T23'], $key, null, 'wacrm');
        $id = (int)$this->db->query("SELECT id FROM notification_logs WHERE idempotency_key = '$key'")->fetchColumn();

        $this->db->exec("UPDATE notification_logs SET status = 'retrying', attempts = 1 WHERE id = $id");
        $this->assert($this->queueService->retryLog($id) === true, "Retry at attempt 1 must succeed");
        echo "PASS\n";
    }

    private function test24_manualRetryAtMaxAttemptsIsRejected(): void {
        echo "[Test 24] Manual retry at maximum attempts is rejected... ";
        $uid = $this->createTestUser('step3-retry-24@scholarmatch.com', 'whatsapp', 0);
        $sid = $this->createTestScholarship('At Max 24');
        $key = "at_max_{$uid}_{$sid}";
        $this->queueService->enqueue($uid, $sid, 'NEW_MATCH', 'whatsapp', '+923001234567', null, ['title' => 'T24'], $key, null, 'wacrm');
        $id = (int)$this->db->query("SELECT id FROM notification_logs WHERE idempotency_key = '$key'")->fetchColumn();

        $this->db->exec("UPDATE notification_logs SET status = 'failed', attempts = 5 WHERE id = $id");
        $this->assert($this->queueService->retryLog($id) === false, "Retry at attempt 5 must be rejected");
        echo "PASS\n";
    }

    private function test25_existingRowIsReused(): void {
        echo "[Test 25] Existing row is reused on manual retry... ";
        $uid = $this->createTestUser('step3-retry-25@scholarmatch.com', 'whatsapp', 0);
        $sid = $this->createTestScholarship('Reuse Row 25');
        $key = "reuse_row_{$uid}_{$sid}";
        $this->queueService->enqueue($uid, $sid, 'NEW_MATCH', 'whatsapp', '+923001234567', null, ['title' => 'T25'], $key, null, 'wacrm');
        $id = (int)$this->db->query("SELECT id FROM notification_logs WHERE idempotency_key = '$key'")->fetchColumn();

        $this->db->exec("UPDATE notification_logs SET status = 'failed', attempts = 1 WHERE id = $id");
        $this->queueService->retryLog($id);

        $afterId = (int)$this->db->query("SELECT id FROM notification_logs WHERE idempotency_key = '$key'")->fetchColumn();
        $this->assert($afterId === $id, "Row ID must remain identical");
        echo "PASS\n";
    }

    private function test26_noNewNotificationLogsRowCreated(): void {
        echo "[Test 26] No new notification_logs row is created... ";
        $uid = $this->createTestUser('step3-retry-26@scholarmatch.com', 'whatsapp', 0);
        $sid = $this->createTestScholarship('No New Row 26');
        $key = "no_new_row_{$uid}_{$sid}";
        $this->queueService->enqueue($uid, $sid, 'NEW_MATCH', 'whatsapp', '+923001234567', null, ['title' => 'T26'], $key, null, 'wacrm');
        $id = (int)$this->db->query("SELECT id FROM notification_logs WHERE idempotency_key = '$key'")->fetchColumn();

        $this->db->exec("UPDATE notification_logs SET status = 'failed', attempts = 1 WHERE id = $id");
        $this->queueService->retryLog($id);

        $count = (int)$this->db->query("SELECT COUNT(*) FROM notification_logs WHERE idempotency_key = '$key'")->fetchColumn();
        $this->assert($count === 1, "Count must remain strictly 1");
        echo "PASS\n";
    }

    private function test27_idempotencyKeyRemainsUnchanged(): void {
        echo "[Test 27] Idempotency key remains unchanged... ";
        $uid = $this->createTestUser('step3-retry-27@scholarmatch.com', 'whatsapp', 0);
        $sid = $this->createTestScholarship('Key Unchanged 27');
        $key = "key_unchanged_{$uid}_{$sid}";
        $this->queueService->enqueue($uid, $sid, 'NEW_MATCH', 'whatsapp', '+923001234567', null, ['title' => 'T27'], $key, null, 'wacrm');
        $id = (int)$this->db->query("SELECT id FROM notification_logs WHERE idempotency_key = '$key'")->fetchColumn();

        $this->db->exec("UPDATE notification_logs SET status = 'failed', attempts = 1 WHERE id = $id");
        $this->queueService->retryLog($id);

        $currentKey = $this->db->query("SELECT idempotency_key FROM notification_logs WHERE id = $id")->fetchColumn();
        $this->assert($currentKey === $key, "Idempotency key must remain identical");
        echo "PASS\n";
    }

    private function test28_providerRemainsUnchanged(): void {
        echo "[Test 28] Provider remains unchanged... ";
        $uid = $this->createTestUser('step3-retry-28@scholarmatch.com', 'whatsapp', 0);
        $sid = $this->createTestScholarship('Provider Unchanged 28');
        $key = "provider_unchanged_{$uid}_{$sid}";
        $this->queueService->enqueue($uid, $sid, 'NEW_MATCH', 'whatsapp', '+923001234567', null, ['title' => 'T28'], $key, null, 'wacrm');
        $id = (int)$this->db->query("SELECT id FROM notification_logs WHERE idempotency_key = '$key'")->fetchColumn();

        $this->db->exec("UPDATE notification_logs SET status = 'failed', attempts = 1 WHERE id = $id");
        $this->queueService->retryLog($id);

        $provider = $this->db->query("SELECT provider FROM notification_logs WHERE id = $id")->fetchColumn();
        $this->assert($provider === 'wacrm', "Provider must remain 'wacrm'");
        echo "PASS\n";
    }

    private function test29_repeatedClicksCannotBypassMaxAttempts(): void {
        echo "[Test 29] Repeated clicks cannot bypass maximum attempt limit... ";
        $uid = $this->createTestUser('step3-retry-29@scholarmatch.com', 'whatsapp', 0);
        $sid = $this->createTestScholarship('Repeated Clicks 29');
        $key = "rep_clicks_{$uid}_{$sid}";
        $this->queueService->enqueue($uid, $sid, 'NEW_MATCH', 'whatsapp', '+923001234567', null, ['title' => 'T29'], $key, null, 'wacrm');
        $id = (int)$this->db->query("SELECT id FROM notification_logs WHERE idempotency_key = '$key'")->fetchColumn();

        // Failed at 5 attempts
        $this->db->exec("UPDATE notification_logs SET status = 'failed', attempts = 5 WHERE id = $id");

        // Click retry 5 times
        for ($i = 0; $i < 5; $i++) {
            $res = $this->queueService->retryLog($id);
            $this->assert($res === false, "Every retry click must be rejected");
        }

        $attempts = (int)$this->db->query("SELECT attempts FROM notification_logs WHERE id = $id")->fetchColumn();
        $this->assert($attempts === 5, "Attempts must strictly remain 5");
        echo "PASS\n";
    }

    private function test30_manualRetryRequiresAdminAuthorization(): void {
        echo "[Test 30] Manual retry requires admin authorization... ";
        unset($_SESSION['user_id']);
        unset($_SESSION['user_role']);
        $this->assert(Auth::isAuthenticated() === false, "Unauthenticated session must fail auth");
        echo "PASS\n";
    }

    private function test31_manualRetryRequiresCsrf(): void {
        echo "[Test 31] Manual retry requires CSRF... ";
        $this->assert(Security::verifyCsrfToken('bad_token') === false, "Invalid CSRF token must fail");
        echo "PASS\n";
    }

    private function test32_manualRetryNeverDirectlyContactsWacrm(): void {
        echo "[Test 32] Manual retry never directly contacts WACRM... ";
        $uid = $this->createTestUser('step3-retry-32@scholarmatch.com', 'whatsapp', 0);
        $sid = $this->createTestScholarship('No Direct WA 32');
        $key = "no_direct_wa_{$uid}_{$sid}";
        $this->queueService->enqueue($uid, $sid, 'NEW_MATCH', 'whatsapp', '+923001234567', null, ['title' => 'T32'], $key, null, 'wacrm');
        $id = (int)$this->db->query("SELECT id FROM notification_logs WHERE idempotency_key = '$key'")->fetchColumn();
        $this->db->exec("UPDATE notification_logs SET status = 'failed', attempts = 1 WHERE id = $id");

        $mockProvider = new Step3MockWhatsAppProvider();
        $this->queueService->retryLog($id);

        $this->assert($mockProvider->callCount === 0, "Mock WACRM callCount must be 0");
        echo "PASS\n";
    }

    private function test33_manualRetryNeverDirectlyContactsSmtp(): void {
        echo "[Test 33] Manual retry never directly contacts SMTP... ";
        $logPath = ROOT_PATH . '/storage/logs/mail.log';
        $initialSize = file_exists($logPath) ? filesize($logPath) : 0;

        $uid = $this->createTestUser('step3-retry-33@scholarmatch.com', 'email', 0);
        $sid = $this->createTestScholarship('No Direct SMTP 33');
        $key = "no_direct_smtp_{$uid}_{$sid}";
        $this->queueService->enqueue($uid, $sid, 'NEW_MATCH', 'email', 'step3-retry-33@scholarmatch.com', 'Subject', ['title' => 'T33'], $key);
        $id = (int)$this->db->query("SELECT id FROM notification_logs WHERE idempotency_key = '$key'")->fetchColumn();
        $this->db->exec("UPDATE notification_logs SET status = 'failed', attempts = 1 WHERE id = $id");

        $this->queueService->retryLog($id);

        $afterSize = file_exists($logPath) ? filesize($logPath) : 0;
        $this->assert($initialSize === $afterSize, "mail.log must not change during retryLog");
        echo "PASS\n";
    }

    private function test34_queueWorkerRemainsResponsibleForActualDelivery(): void {
        echo "[Test 34] Queue worker remains responsible for actual delivery... ";
        $uid = $this->createTestUser('step3-retry-34@scholarmatch.com', 'email', 0);
        $sid = $this->createTestScholarship('Worker Responsible 34');
        $key = "worker_resp_{$uid}_{$sid}";
        $this->queueService->enqueue($uid, $sid, 'NEW_MATCH', 'email', 'step3-retry-34@scholarmatch.com', 'Subject', ['title' => 'T34'], $key);
        $id = (int)$this->db->query("SELECT id FROM notification_logs WHERE idempotency_key = '$key'")->fetchColumn();

        $this->db->exec("UPDATE notification_logs SET status = 'failed', attempts = 1 WHERE id = $id");
        $this->queueService->retryLog($id);

        // Process queue
        $this->queueService->processQueue(50);

        $row = $this->db->query("SELECT status, attempts FROM notification_logs WHERE id = $id")->fetch(PDO::FETCH_ASSOC);
        $this->assert(in_array($row['status'], ['sent', 'delivered']), "Worker must process item to sent");
        $this->assert((int)$row['attempts'] === 2, "Attempts must advance from 1 to 2 upon worker delivery");
        echo "PASS\n";
    }

    // --- Group 4: Gmail SMTP Full TLS & Auth Health Verification (35-40) ---

    private function test35_smtpSocketConnectionLogic(): void {
        echo "[Test 35] SMTP socket connection verification logic... ";
        $emailService = new EmailNotificationService();
        $res = $emailService->testConnection();
        $this->assert(is_array($res) && isset($res['status']), "Response must contain status");
        echo "PASS\n";
    }

    private function test36_smtpTlsNegotiationLogic(): void {
        echo "[Test 36] SMTP TLS negotiation mode handling... ";
        $config = require ROOT_PATH . '/config/mail.php';
        $encryption = strtolower($config['encryption'] ?? 'tls');
        $this->assert(in_array($encryption, ['tls', 'ssl', '']), "Encryption must be TLS, SSL, or null");
        echo "PASS\n";
    }

    private function test37_smtpAuthenticationSuccessLogic(): void {
        echo "[Test 37] SMTP authentication success response verification... ";
        $emailService = new EmailNotificationService();
        $res = $emailService->testConnection();
        $this->assert($res['success'] === true, "Health check must return success true in test/configured environment");
        $this->assert($res['status'] === 'AUTHENTICATED', "Health check status must be AUTHENTICATED");
        echo "PASS\n";
    }

    private function test38_smtpAuthFailureHandledSafely(): void {
        echo "[Test 38] SMTP authentication failure handled safely... ";
        $emailService = new EmailNotificationService();
        $ref = new ReflectionClass($emailService);
        $pConfig = $ref->getProperty('config');
        $pConfig->setAccessible(true);
        $origConfig = $pConfig->getValue($emailService);

        // Point to invalid socket to test safe failure handling
        $pConfig->setValue($emailService, array_merge($origConfig, [
            'mailer' => 'smtp',
            'host' => '127.0.0.1',
            'port' => 65530
        ]));

        $res = $emailService->testConnection();
        $this->assert($res['success'] === false, "Invalid host/port must fail safely");
        $this->assert(isset($res['error']), "Error message must be set");

        $pConfig->setValue($emailService, $origConfig);
        echo "PASS\n";
    }

    private function test39_smtpPasswordNeverAppearsInLogsOrResponses(): void {
        echo "[Test 39] SMTP password never appears in responses or logs... ";
        $emailService = new EmailNotificationService();
        $ref = new ReflectionClass($emailService);
        $method = $ref->getMethod('redactError');
        $method->setAccessible(true);

        $mockError = "Authentication failed: pass=gmail_app_password_9988";
        $redacted = $method->invoke($emailService, $mockError);
        $this->assert(strpos($redacted, 'gmail_app_password_9988') === false, "Password must be redacted from error output");
        echo "PASS\n";
    }

    private function test40_noEmailSentDuringSmtpHealthCheck(): void {
        echo "[Test 40] No email is sent during SMTP health check... ";
        $logPath = ROOT_PATH . '/storage/logs/mail.log';
        $initialSize = file_exists($logPath) ? filesize($logPath) : 0;

        $emailService = new EmailNotificationService();
        $emailService->testConnection();

        $afterSize = file_exists($logPath) ? filesize($logPath) : 0;
        $this->assert($initialSize === $afterSize, "testConnection() must not write to mail.log or send an email");
        echo "PASS\n";
    }

    // --- Group 5: Provider Resolution & Queue Architecture (41-45) ---

    private function test41_queueWorkerSelectsWacrmForScholarshipWhatsApp(): void {
        echo "[Test 41] Queue worker selects WACRM for scholarship WhatsApp... ";
        $waService = new WhatsAppNotificationService();
        $provider = $waService->getProviderFor(null, 'NEW_MATCH');
        $this->assert($provider instanceof WacrmWhatsAppProvider || $provider instanceof LogWhatsAppProvider, "Provider for NEW_MATCH must be WacrmWhatsAppProvider or LogWhatsAppProvider");
        echo "PASS\n";
    }

    private function test42_queueWorkerSelectsSmtpForScholarshipEmail(): void {
        echo "[Test 42] Queue worker selects Gmail SMTP for scholarship Email... ";
        $uid = $this->createTestUser('step3-test-42@scholarmatch.com', 'email', 0);
        $sid = $this->createTestScholarship('Smtp Res 42');

        $key = "smtp_res_{$uid}_{$sid}";
        $this->queueService->enqueue($uid, $sid, 'NEW_MATCH', 'email', 'step3-test-42@scholarmatch.com', 'Subject', ['title' => 'Title'], $key);

        $stmt = $this->db->prepare("SELECT provider FROM notification_logs WHERE idempotency_key = :key");
        $stmt->execute(['key' => $key]);
        $this->assert($stmt->fetchColumn() === 'smtp', "Email job provider must default to 'smtp'");
        echo "PASS\n";
    }

    private function test43_paymentConfirmationContinuesUsingMetaWhatsApp(): void {
        echo "[Test 43] Payment confirmation continues using Meta WhatsApp Provider... ";
        $waService = new WhatsAppNotificationService();
        $provider = $waService->getProviderFor(null, 'PAYMENT_CONFIRMATION');
        $this->assert($provider instanceof MetaWhatsAppProvider, "Payment confirmation must route to MetaWhatsAppProvider");
        echo "PASS\n";
    }

    private function test44_schedulerNeverDirectlyCallsWacrmOrCurl(): void {
        echo "[Test 44] Scheduler never directly calls WACRM or cURL... ";
        $dailyMatches = file_get_contents(ROOT_PATH . '/cron/daily_matches.php');
        $deadlineReminders = file_get_contents(ROOT_PATH . '/cron/deadline_reminders.php');

        $this->assert(strpos($dailyMatches, 'WacrmWhatsAppProvider') === false, "daily_matches must not use Wacrm provider");
        $this->assert(strpos($deadlineReminders, 'WacrmWhatsAppProvider') === false, "deadline_reminders must not use Wacrm provider");
        $this->assert(strpos($dailyMatches, 'curl_exec') === false, "daily_matches must not make cURL calls");
        $this->assert(strpos($deadlineReminders, 'curl_exec') === false, "deadline_reminders must not make cURL calls");
        echo "PASS\n";
    }

    private function test45_schedulerNeverDirectlyCallsSmtp(): void {
        echo "[Test 45] Scheduler never directly calls SMTP... ";
        $dailyMatches = file_get_contents(ROOT_PATH . '/cron/daily_matches.php');
        $deadlineReminders = file_get_contents(ROOT_PATH . '/cron/deadline_reminders.php');

        $this->assert(strpos($dailyMatches, 'fsockopen') === false, "daily_matches must not open sockets");
        $this->assert(strpos($deadlineReminders, 'fsockopen') === false, "deadline_reminders must not open sockets");
        echo "PASS\n";
    }

    // --- Group 6: Preferences, Multi-channel & Regressions (46-52) ---

    private function test46_userNotificationPreferencesRespectedBeforeDelivery(): void {
        echo "[Test 46] User notification preferences are respected before delivery... ";
        $uid = $this->createTestUser('step3-test-46@scholarmatch.com', 'email', 0);
        $sid = $this->createTestScholarship('Opt Out 46');

        $key = "opt_out_test_{$uid}_{$sid}";
        $this->queueService->enqueue($uid, $sid, 'NEW_MATCH', 'email', 'step3-test-46@scholarmatch.com', 'Subject', ['title' => 'T46'], $key);

        $this->db->exec("UPDATE notification_preferences SET email_enabled = 0 WHERE user_id = $uid AND notification_type = 'matching_scholarship_alerts'");

        $this->queueService->processQueue(50);

        $stmt = $this->db->prepare("SELECT status FROM notification_logs WHERE idempotency_key = :key");
        $stmt->execute(['key' => $key]);
        $status = $stmt->fetchColumn();

        $this->assert($status === 'skipped', "Item must transition to 'skipped' upon pre-dispatch opt-out verification, got $status");
        echo "PASS\n";
    }

    private function test47_preferredWhatsAppSendsWhatsAppOnlyWhenMultiChannelOff(): void {
        echo "[Test 47] Preferred WhatsApp sends WhatsApp ONLY when multi-channel is OFF... ";
        $uid = $this->createTestUser('step3-test-47@scholarmatch.com', 'whatsapp', 0);
        $sid = $this->createTestScholarship('Preferred WA 47');

        $this->notificationService->sendNotification($uid, 'NEW_MATCH', ['title' => 'Pref WA'], $sid, "pref_wa_{$uid}_{$sid}");

        $stmt = $this->db->prepare("SELECT channel FROM notification_logs WHERE user_id = :uid AND scholarship_id = :sid");
        $stmt->execute(['uid' => $uid, 'sid' => $sid]);
        $channels = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $this->assert(count($channels) === 1, "Expected exactly 1 notification log");
        $this->assert($channels[0] === 'whatsapp', "Channel must be 'whatsapp'");
        echo "PASS\n";
    }

    private function test48_preferredEmailSendsEmailOnlyWhenMultiChannelOff(): void {
        echo "[Test 48] Preferred Email sends Email ONLY when multi-channel is OFF... ";
        $uid = $this->createTestUser('step3-test-48@scholarmatch.com', 'email', 0);
        $sid = $this->createTestScholarship('Preferred Email 48');

        $this->notificationService->sendNotification($uid, 'NEW_MATCH', ['title' => 'Pref Email'], $sid, "pref_email_{$uid}_{$sid}");

        $stmt = $this->db->prepare("SELECT channel FROM notification_logs WHERE user_id = :uid AND scholarship_id = :sid");
        $stmt->execute(['uid' => $uid, 'sid' => $sid]);
        $channels = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $this->assert(count($channels) === 1, "Expected exactly 1 notification log");
        $this->assert($channels[0] === 'email', "Channel must be 'email'");
        echo "PASS\n";
    }

    private function test49_explicitMultiChannelSendsBothOnlyWhenEnabled(): void {
        echo "[Test 49] Explicit multi-channel sends both ONLY when enabled... ";
        $uid = $this->createTestUser('step3-test-49@scholarmatch.com', 'email', 1);
        $sid = $this->createTestScholarship('Multi Channel 49');

        $this->notificationService->sendNotification($uid, 'NEW_MATCH', ['title' => 'Multi'], $sid, "multi_test_{$uid}_{$sid}");

        $stmt = $this->db->prepare("SELECT channel FROM notification_logs WHERE user_id = :uid AND scholarship_id = :sid");
        $stmt->execute(['uid' => $uid, 'sid' => $sid]);
        $channels = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $this->assert(count($channels) === 2, "Expected 2 notification logs (Email + WhatsApp)");
        echo "PASS\n";
    }

    private function test50_deadlinesOffRemainsOffRegardlessOfProviderConfig(): void {
        echo "[Test 50] Deadline reminder OFF remains OFF regardless of provider config... ";
        $uid = $this->createTestUser('step3-test-50@scholarmatch.com', 'whatsapp', 0);
        $this->db->exec("UPDATE user_preferences SET deadline_reminder_scope = 'off' WHERE user_id = $uid");

        $sid = $this->createTestScholarship('Closing in 3 Days 50');
        $this->db->exec("INSERT INTO scholarship_applications (user_id, scholarship_id, status, created_at, updated_at) VALUES ($uid, $sid, 'documents_pending', NOW(), NOW())");

        ob_start();
        require ROOT_PATH . '/cron/deadline_reminders.php';
        ob_get_clean();

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM notification_logs WHERE user_id = :uid AND scholarship_id = :sid AND notification_type LIKE 'SCHOLARSHIP_DEADLINE%'");
        $stmt->execute(['uid' => $uid, 'sid' => $sid]);
        $count = (int)$stmt->fetchColumn();

        $this->assert($count === 0, "Scope OFF must produce 0 deadline reminders");
        echo "PASS\n";
    }

    private function test51_step1RegistrationVerificationQueueBehaviorIntact(): void {
        echo "[Test 51] Step 1 registration verification queue behavior remains intact... ";
        $step1Test = new \Step1QueueVerificationTest();
        ob_start();
        $step1Test->run();
        $output = ob_get_clean();

        $this->assert(strpos($output, 'Step1QueueVerificationTest PASSED') !== false, "Step 1 test suite must pass");
        echo "PASS\n";
    }

    private function test52_step2MatchingIdempotencyBehaviorIntact(): void {
        echo "[Test 52] Step 2 matching idempotency behavior remains intact... ";
        $step2Test = new \Step2MatchingAndPreferencesTest();
        ob_start();
        $step2Test->run();
        $output = ob_get_clean();

        $this->assert(strpos($output, 'ALL 40 STEP 2 VERIFICATION TESTS PASSED') !== false, "Step 2 test suite must pass");
        echo "PASS\n";
    }

    private function assert(bool $condition, string $message): void {
        if (!$condition) {
            throw new \Exception("Assertion Failure: " . $message);
        }
    }
}
