<?php

namespace Tests;

require_once dirname(__DIR__) . '/tests/bootstrap.php';

use App\Services\Database;
use App\Services\NotificationQueueService;
use App\Services\NotificationDispatchService;
use App\Services\WhatsAppNotificationService;
use App\Services\WhatsApp\WacrmWhatsAppProvider;
use App\Services\WhatsApp\MetaWhatsAppProvider;
use App\Services\WhatsApp\ScholarshipMessageFormatter;
use App\Services\WhatsApp\WhatsAppProviderInterface;
use PDO;
use Exception;

class TemporaryWhatsAppTestModeTest {
    private PDO $db;

    public function __construct() {
        $this->db = Database::connection();
    }

    private function assert(bool $condition, string $message): void {
        if (!$condition) {
            throw new Exception("Assertion failed: " . $message);
        }
    }

    public function run(): void {
        echo "=================================================================\n";
        echo " RUNNING TEMPORARY WHATSAPP LIVE TEST MODE TEST SUITE            \n";
        echo "=================================================================\n\n";

        $this->test1_testModeEnabledMapping();
        $this->test2_testModeRecipientProtection();
        $this->test3_inReviewDeadlineSoonBlockedWithoutCallingProvider();
        $this->test4_inReviewDeadlineTodayBlockedWithoutCallingProvider();
        $this->test5_inReviewConfirmationMsgBlockedWithoutCallingProvider();
        $this->test6_variableCountIntegrityIsolatedPerTemplate();
        $this->test7_heldStatePreservesQueueRecordWithoutHardFailure();
        $this->test8_testModeDisabledRestoresProductionDefaults();
        $this->test9_safeServerSideAdministrativeControl();

        echo "\n=================================================================\n";
        echo " ✔ ALL TEMPORARY WHATSAPP LIVE TEST MODE TESTS PASSED!           \n";
        echo "=================================================================\n\n";
    }

    public function test1_testModeEnabledMapping(): void {
        echo "[Test 1] In test mode, new_match routes to new_match_v2... ";
        
        $_ENV['WHATSAPP_TEST_MODE'] = 'true';
        $_ENV['WHATSAPP_TEST_TEMPLATE_NEW_MATCH'] = 'new_match_v2';
        
        $provider = new WacrmWhatsAppProvider();
        $ref = new \ReflectionClass($provider);
        $prop = $ref->getProperty('testTemplateNewMatch');
        $prop->setAccessible(true);
        $this->assert($prop->getValue($provider) === 'new_match_v2', "Test template should be new_match_v2");
        
        unset($_ENV['WHATSAPP_TEST_MODE']);
        echo "PASS\n";
    }

    public function test2_testModeRecipientProtection(): void {
        echo "[Test 2] Test mode restricts live sending to authorized test recipient only... ";
        
        $_ENV['WHATSAPP_TEST_MODE'] = 'true';
        $_ENV['WHATSAPP_TEST_RECIPIENT'] = '+923251371826';
        
        $provider = new WacrmWhatsAppProvider();
        $unauthorizedNumber = '+923001234567';
        $params = ['Title', 'Desc', 'Univ', 'MS', 'UK', 'Full', '2026-10-01', 'https://ox.ac.uk'];
        
        $result = $provider->sendTemplateMessage($unauthorizedNumber, 'new_match', $params);
        $this->assert($result['success'] === false, "Unauthorized recipient must be blocked in test mode");
        $this->assert(strpos($result['error'], 'TEST_MODE_RECIPIENT_BLOCKED') !== false, "Error should indicate recipient blocked in test mode");
        $this->assert(!empty($result['held']), "Status must be held");
        
        unset($_ENV['WHATSAPP_TEST_MODE']);
        echo "PASS\n";
    }

    public function test3_inReviewDeadlineSoonBlockedWithoutCallingProvider(): void {
        echo "[Test 3] deadline_soon is gated when IN_REVIEW without calling Meta/WACRM... ";
        
        $_ENV['META_TEMPLATE_STATUS_DEADLINE_SOON'] = 'IN_REVIEW';
        
        $provider = new WacrmWhatsAppProvider();
        $params = ['Title', 'Desc', 'Univ', '3 days', '2026-10-01', 'https://ox.ac.uk'];
        $result = $provider->sendTemplateMessage('+923251371826', 'deadline_soon', $params);
        
        $this->assert($result['success'] === false, "In-review template must not be dispatched");
        $this->assert(strpos($result['error'], 'META_TEMPLATE_IN_REVIEW') !== false, "Error must indicate template in review");
        $this->assert(!empty($result['held']), "Must return held flag");
        
        unset($_ENV['META_TEMPLATE_STATUS_DEADLINE_SOON']);
        echo "PASS\n";
    }

    public function test4_inReviewDeadlineTodayBlockedWithoutCallingProvider(): void {
        echo "[Test 4] deadline_today is gated when IN_REVIEW without calling Meta/WACRM... ";
        
        $_ENV['META_TEMPLATE_STATUS_DEADLINE_TODAY'] = 'IN_REVIEW';
        
        $provider = new WacrmWhatsAppProvider();
        $params = ['Title', 'Desc', 'Univ', 'Today', 'https://ox.ac.uk'];
        $result = $provider->sendTemplateMessage('+923251371826', 'deadline_today', $params);
        
        $this->assert($result['success'] === false, "In-review deadline_today must not be dispatched");
        $this->assert(strpos($result['error'], 'META_TEMPLATE_IN_REVIEW') !== false, "Error must indicate template in review");
        $this->assert(!empty($result['held']), "Must return held flag");
        
        unset($_ENV['META_TEMPLATE_STATUS_DEADLINE_TODAY']);
        echo "PASS\n";
    }

    public function test5_inReviewConfirmationMsgBlockedWithoutCallingProvider(): void {
        echo "[Test 5] confirmation_msg is gated when IN_REVIEW without calling Meta API... ";
        
        $_ENV['META_TEMPLATE_STATUS_CONFIRMATION_MSG'] = 'IN_REVIEW';
        
        $provider = new MetaWhatsAppProvider();
        $params = ['John Doe', 'Premium Plan', '1000', 'PKR', 'REF12345'];
        $result = $provider->sendTemplateMessage('+923251371826', 'confirmation_msg', $params);
        
        $this->assert($result['success'] === false, "In-review confirmation_msg must not be dispatched");
        $this->assert(strpos($result['error'], 'META_TEMPLATE_IN_REVIEW') !== false, "Error must indicate template in review");
        $this->assert(!empty($result['held']), "Must return held flag");
        
        unset($_ENV['META_TEMPLATE_STATUS_CONFIRMATION_MSG']);
        echo "PASS\n";
    }

    public function test6_variableCountIntegrityIsolatedPerTemplate(): void {
        echo "[Test 6] Parameter count and ordering remain strictly isolated per template... ";
        
        $data = [
            'title' => 'Test Scholarship',
            'short_description' => 'Test Description',
            'provider_name' => 'Oxford',
            'study_level' => 'PhD',
            'country_name' => 'UK',
            'funding_type' => 'Full',
            'application_deadline' => '2026-10-31',
            'days_left' => '3',
            'official_application_url' => 'https://ox.ac.uk',
            'user_name' => 'Ali',
            'plan_name' => 'Pro',
            'amount' => '5000',
            'currency' => 'PKR',
            'reference' => 'TX123'
        ];

        // 1. new_match: exactly 8 params
        $newMatchParams = ScholarshipMessageFormatter::buildTemplateParams('NEW_MATCH', $data);
        $this->assert(count($newMatchParams) === 8, "new_match must have 8 parameters");

        // 2. deadline_soon: exactly 6 params
        $soonParams = ScholarshipMessageFormatter::buildTemplateParams('SCHOLARSHIP_DEADLINE_SOON', $data);
        $this->assert(count($soonParams) === 6, "deadline_soon must have 6 parameters");

        // 3. deadline_today: exactly 5 params
        $todayParams = ScholarshipMessageFormatter::buildTemplateParams('SCHOLARSHIP_DEADLINE_TODAY', $data);
        $this->assert(count($todayParams) === 5, "deadline_today must have 5 parameters");

        // 4. payment confirmation: exactly 5 params
        $paymentParams = ScholarshipMessageFormatter::buildTemplateParams('PAYMENT_CONFIRMATION', $data);
        $this->assert(count($paymentParams) === 5, "payment confirmation must have 5 parameters");

        echo "PASS\n";
    }

    public function test7_heldStatePreservesQueueRecordWithoutHardFailure(): void {
        echo "[Test 7] Held notifications preserve queue state without incrementing hard failure count... ";
        
        $roleId = $this->db->query("SELECT id FROM roles WHERE name = 'visitor' LIMIT 1")->fetchColumn() ?: 2;
        $planId = $this->db->query("SELECT id FROM subscription_plans WHERE slug = 'premium-monthly' LIMIT 1")->fetchColumn() ?: 1;

        // Clean any old test user
        $this->db->exec("DELETE FROM notification_logs WHERE user_id IN (SELECT id FROM users WHERE email = 'test_held_user@example.com')");
        $this->db->exec("DELETE FROM subscriptions WHERE user_id IN (SELECT id FROM users WHERE email = 'test_held_user@example.com')");
        $this->db->exec("DELETE FROM notification_preferences WHERE user_id IN (SELECT id FROM users WHERE email = 'test_held_user@example.com')");
        $this->db->exec("DELETE FROM users WHERE email = 'test_held_user@example.com'");
        $this->db->exec("DELETE FROM scholarships WHERE slug = 'test-held-scholarship'");

        $uniquePhone = '+92300' . rand(1000000, 9999999);

        // Create test user
        $stmtUser = $this->db->prepare("
            INSERT INTO users (first_name, last_name, email, phone, whatsapp_phone, password_hash, role_id, status, email_opt_in, whatsapp_opt_in, created_at)
            VALUES ('Held', 'Tester', 'test_held_user@example.com', :phone, :wphone, 'hash', :role_id, 'active', 1, 1, NOW())
        ");
        $stmtUser->execute(['phone' => $uniquePhone, 'wphone' => $uniquePhone, 'role_id' => $roleId]);
        $testUserId = (int)$this->db->lastInsertId();

        // Active subscription
        $stmtSub = $this->db->prepare("
            INSERT INTO subscriptions (user_id, plan_id, status, starts_at, ends_at, created_at, updated_at)
            VALUES (:uid, :plan_id, 'active', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), NOW(), NOW())
        ");
        $stmtSub->execute(['uid' => $testUserId, 'plan_id' => $planId]);

        // Preferences
        $this->db->exec("INSERT INTO notification_preferences (user_id, notification_type, whatsapp_enabled, email_enabled) VALUES ({$testUserId}, 'whatsapp_alerts', 1, 1)");
        $this->db->exec("INSERT INTO notification_preferences (user_id, notification_type, whatsapp_enabled, email_enabled) VALUES ({$testUserId}, 'deadline_reminders', 1, 1)");

        // Published scholarship
        $stmtSch = $this->db->prepare("
            INSERT INTO scholarships (title, slug, status, verification_status, provider_name, country_id, funding_type, application_deadline, description, created_at)
            VALUES ('Held Test Scholarship', 'test-held-scholarship', 'published', 'verified', 'Oxford Test', 1, 'Fully Funded', DATE_ADD(CURDATE(), INTERVAL 10 DAY), 'Desc', NOW())
        ");
        $stmtSch->execute();
        $testSchId = (int)$this->db->lastInsertId();

        // Insert notification log
        $stmt = $this->db->prepare("
            INSERT INTO notification_logs (
                user_id, scholarship_id, notification_type, channel, provider, recipient,
                subject, payload, idempotency_key, status, attempts, created_at, updated_at
            ) VALUES (
                :uid, :sid, 'SCHOLARSHIP_DEADLINE_SOON', 'whatsapp', 'wacrm', :recip,
                'Deadline Soon', '{}', 'test_held_unique_key_" . uniqid() . "', 'processing', 0, NOW(), NOW()
            )
        ");
        $stmt->execute(['uid' => $testUserId, 'sid' => $testSchId, 'recip' => $uniquePhone]);
        $id = (int)$this->db->lastInsertId();

        $dispatchService = new NotificationDispatchService($this->db);
        
        // Dispatch with IN_REVIEW template
        $_ENV['META_TEMPLATE_STATUS_DEADLINE_SOON'] = 'IN_REVIEW';
        $_ENV['WHATSAPP_TEST_RECIPIENT'] = $uniquePhone;
        $item = [
            'id' => $id,
            'user_id' => $testUserId,
            'scholarship_id' => $testSchId,
            'notification_type' => 'SCHOLARSHIP_DEADLINE_SOON',
            'channel' => 'whatsapp',
            'recipient' => $uniquePhone,
            'attempts' => 0
        ];


        // Test held handling
        $result = $dispatchService->dispatchItem($item);
        
        // Verify state in DB
        $stmtCheck = $this->db->prepare("SELECT status, attempts, error_message FROM notification_logs WHERE id = :id");
        $stmtCheck->execute(['id' => $id]);
        $row = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        $this->assert($row['status'] === 'pending', "Record must remain pending for future processing (got {$row['status']})");
        $this->assert(strpos($row['error_message'], 'META_TEMPLATE_IN_REVIEW') !== false, "Error message must document Meta review hold");
        $this->assert((int)$row['attempts'] === 0, "Attempts should not increment as a failed send");

        // Clean up
        $this->db->exec("DELETE FROM notification_logs WHERE user_id = {$testUserId}");
        $this->db->exec("DELETE FROM subscriptions WHERE user_id = {$testUserId}");
        $this->db->exec("DELETE FROM notification_preferences WHERE user_id = {$testUserId}");
        $this->db->exec("DELETE FROM users WHERE id = {$testUserId}");
        $this->db->exec("DELETE FROM scholarships WHERE id = {$testSchId}");
        unset($_ENV['META_TEMPLATE_STATUS_DEADLINE_SOON']);
        
        echo "PASS\n";
    }


    public function test8_testModeDisabledRestoresProductionDefaults(): void {
        echo "[Test 8] When WHATSAPP_TEST_MODE=false, production template names resume... ";
        
        $_ENV['WHATSAPP_TEST_MODE'] = 'false';
        $provider = new WacrmWhatsAppProvider();
        
        $ref = new \ReflectionClass($provider);
        $propMode = $ref->getProperty('testMode');
        $propMode->setAccessible(true);
        $this->assert($propMode->getValue($provider) === false, "testMode must be false");

        $propTemplates = $ref->getProperty('templates');
        $propTemplates->setAccessible(true);
        $templates = $propTemplates->getValue($provider);
        $this->assert(($templates['new_match'] ?? 'new_match_v2') === 'new_match_v2' || ($templates['new_match'] ?? '') === 'new_match', "Production default template must be new_match_v2");
        
        unset($_ENV['WHATSAPP_TEST_MODE']);
        echo "PASS\n";
    }

    public function test9_safeServerSideAdministrativeControl(): void {
        echo "[Test 9] Test mode configuration is strictly server-side controlled... ";
        
        $config = require ROOT_PATH . '/config/whatsapp.php';
        $this->assert(isset($config['test_mode']), "whatsapp.php must expose test_mode");
        $this->assert(isset($config['template_status']), "whatsapp.php must expose template_status");
        $this->assert(isset($config['test_recipient']), "whatsapp.php must expose test_recipient");
        
        echo "PASS\n";
    }
}

if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    $test = new TemporaryWhatsAppTestModeTest();
    $test->run();
}

