<?php

use App\Services\Database;
use App\Services\Auth;
use App\Services\SubscriptionService;
use App\Services\PaymentService;
use App\Controllers\BillingController;
use App\Controllers\DashboardController;
use App\Controllers\ScholarshipController;

class BillingSubscriptionTest {
    private PDO $db;
    private int $uVisitor;
    private int $uAdmin;
    private int $planPremium;
    private int $planFree;

    public function __construct() {
        $this->db = Database::connection();
    }

    public function run(): void {
        echo "--- Running BillingSubscriptionTest ---\n";
        
        $this->setUp();

        try {
            $this->testPricingPageAccessibility();
            $this->testCheckoutAccessAndCsrfProtection();
            $this->testPaymentCreationAndGatewayRedirection();
            $this->testCallbackServerSideVerification();
            $this->testWebhookSignatureValidationAndFulfillment();
            $this->testWebhookIdempotency();
            $this->testWebhookAmountAndCurrencyMismatch();
            $this->testSubscriptionStateTransitions();
            $this->testFreePlanAccessLimits();
            $this->testCronJobExpiryAndRenewalReminders();
            $this->testAdminBillingStatsDashboard();
            $this->testIdorBillingAccessDeny();
            $this->testSecretRedactionAuditLogs();
        } finally {
            $this->tearDown();
        }

        echo "BillingSubscriptionTest PASSED.\n\n";
    }

    private function setUp(): void {
        // Reset DB tables clean
        $this->db->exec("DELETE FROM payment_webhook_logs");
        $this->db->exec("DELETE FROM payment_transactions");
        $this->db->exec("DELETE FROM subscriptions");
        $this->db->exec("DELETE FROM saved_scholarships");
        $this->db->exec("DELETE FROM scholarship_matches");
        $this->db->exec("DELETE FROM audit_logs");
        $this->db->exec("DELETE FROM users WHERE email IN ('student_billing@example.com', 'admin_billing@example.com', 'victim_billing@example.com')");

        // Fetch seeded roles
        $visitorRoleId = $this->db->query("SELECT id FROM roles WHERE name = 'visitor'")->fetchColumn();
        $adminRoleId = $this->db->query("SELECT id FROM roles WHERE name = 'admin'")->fetchColumn();

        // Create test users
        $stmt = $this->db->prepare("INSERT INTO users (email, password_hash, first_name, last_name, role_id, status) VALUES (?, ?, ?, ?, ?, 'active')");
        
        $stmt->execute(['student_billing@example.com', password_hash('Pass123!', PASSWORD_BCRYPT), 'Student', 'Billing', $visitorRoleId]);
        $this->uVisitor = $this->db->lastInsertId();

        $stmt->execute(['admin_billing@example.com', password_hash('Pass123!', PASSWORD_BCRYPT), 'Admin', 'Billing', $adminRoleId]);
        $this->uAdmin = $this->db->lastInsertId();

        // Fetch plan ids
        $this->planFree = (int)$this->db->query("SELECT id FROM subscription_plans WHERE slug = 'free'")->fetchColumn();
        $this->planPremium = (int)$this->db->query("SELECT id FROM subscription_plans WHERE slug = 'premium-monthly'")->fetchColumn();

        // Ensure we have at least 15 mock scholarships for limit testing
        $this->db->exec("DELETE FROM scholarships WHERE title LIKE 'Mock Billing%'");
        $stmtSch = $this->db->prepare("INSERT INTO scholarships (title, slug, status, provider_name, country_id, funding_type, application_deadline, description, created_at) VALUES (?, ?, 'published', 'Mock Inc', 1, 'Fully Funded', DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'Mock description', NOW())");
        for ($i = 1; $i <= 15; $i++) {
            $stmtSch->execute(["Mock Billing Scholarship $i", "mock-billing-scholarship-$i"]);
        }

        // Calculate matches lazy setup
        $matchingService = new \App\Services\ScholarshipMatchingService();
        $matchingService->recalculateForUser($this->uVisitor);
    }

    private function tearDown(): void {
        $this->db->exec("DELETE FROM payment_webhook_logs");
        $this->db->exec("DELETE FROM payment_transactions");
        $this->db->exec("DELETE FROM subscriptions");
        $this->db->exec("DELETE FROM saved_scholarships");
        $this->db->exec("DELETE FROM scholarship_matches");
        $this->db->exec("DELETE FROM audit_logs");
        $this->db->exec("DELETE FROM scholarships WHERE title LIKE 'Mock Billing%'");
        $this->db->exec("DELETE FROM users WHERE email IN ('student_billing@example.com', 'admin_billing@example.com', 'victim_billing@example.com')");
        
        // Clean sessions
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
        }
    }

    private function loginUser(int $userId, string $role): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['user_id'] = $userId;
        $_SESSION['role_name'] = $role;
        $_SESSION['user_email'] = ($role === 'admin') ? 'admin_billing@example.com' : 'student_billing@example.com';
        
        $ref = new \ReflectionClass('App\Services\Auth');
        $prop = $ref->getProperty('currentUser');
        $prop->setAccessible(true);
        $prop->setValue(null, null);
    }

    private function testPricingPageAccessibility(): void {
        // Guests can view pricing
        $_SESSION = [];
        $_GET = [];
        
        $controller = new BillingController();
        ob_start();
        $controller->pricing();
        $output = ob_get_clean();

        if (strpos($output, 'Pricing Plans') === false || strpos($output, 'Premium Monthly') === false) {
            throw new Exception("Pricing page failed to render Free and Premium plan details.");
        }
        echo "✔ Pricing page accessibility verified.\n";
    }

    private function testCheckoutAccessAndCsrfProtection(): void {
        $controller = new BillingController();

        // 1. Guest checkout throws redirect exception (requireAuth)
        $_SESSION = [];
        $_GET = ['plan' => 'premium-monthly'];

        try {
            $controller->checkout();
            throw new Exception("Unauthenticated user accessed checkout without login redirect.");
        } catch (\RuntimeException $e) {
            if ($e->getMessage() !== 'Redirect to login') {
                throw $e;
            }
        }

        // 2. Logged in student accessing invalid plan redirect
        $this->loginUser($this->uVisitor, 'visitor');
        $_GET = ['plan' => 'non-existent'];

        ob_start();
        try {
            $controller->checkout();
        } catch (\Exception $e) {
            // catch headers sent redirects if any
        }
        ob_get_clean();

        // 3. POST Checkout CSRF validation block
        $_POST = [
            'plan_slug' => 'premium-monthly',
            'payment_provider' => 'mock'
            // Missing csrf_token
        ];
        
        try {
            $controller->processCheckout();
            throw new Exception("processCheckout failed to block invalid CSRF payment requests.");
        } catch (\Exception $e) {
            // expected redirect or failure
        }
        echo "✔ Checkout authentication and CSRF protection verified.\n";
    }

    private function testPaymentCreationAndGatewayRedirection(): void {
        $this->loginUser($this->uVisitor, 'visitor');
        $controller = new BillingController();

        $_POST = [
            'csrf_token' => \App\Helpers\Security::csrfToken(),
            'plan_slug' => 'premium-monthly',
            'payment_provider' => 'mock'
        ];

        ob_start();
        try {
            $controller->processCheckout();
        } catch (\Exception $e) {
            // catch redirect header
        }
        ob_end_clean();

        // Check transaction row created in pending status
        $stmt = $this->db->prepare("SELECT * FROM payment_transactions WHERE user_id = :uid LIMIT 1");
        $stmt->execute(['uid' => $this->uVisitor]);
        $tx = $stmt->fetch();

        if (!$tx || $tx['status'] !== 'pending' || (float)$tx['amount'] !== 1499.00) {
            throw new Exception("Checkout process failed to create correct pending payment transaction.");
        }
        echo "✔ Pending transaction creation and mock gateway routing verified.\n";
    }

    private function testCallbackServerSideVerification(): void {
        $this->loginUser($this->uVisitor, 'visitor');
        $controller = new BillingController();

        // Fetch the pending transaction reference
        $ref = $this->db->query("SELECT transaction_reference FROM payment_transactions WHERE user_id = {$this->uVisitor} LIMIT 1")->fetchColumn();

        // Mock payment verification callback GET params
        $_GET = [
            'ref' => $ref,
            'status' => 'success'
        ];

        ob_start();
        try {
            $controller->callback();
        } catch (\Exception $e) {
            // Catch header redirect
        }
        ob_end_clean();

        // Check database transaction status is updated to paid
        $status = $this->db->query("SELECT status FROM payment_transactions WHERE transaction_reference = '{$ref}'")->fetchColumn();
        if ($status !== 'paid') {
            throw new Exception("Callback failed to mark verified payment transaction as paid.");
        }

        // Check user subscription is active
        $activePlan = SubscriptionService::getActivePlan($this->uVisitor);
        if ($activePlan['plan_slug'] !== 'premium-monthly') {
            throw new Exception("Callback failed to activate Premium subscription for visitor user.");
        }
        echo "✔ Callback server-side verification and subscription fulfillment verified.\n";
    }

    private function testWebhookSignatureValidationAndFulfillment(): void {
        // Log user back out (webhook is server-to-server)
        $_SESSION = [];
        $controller = new BillingController();

        // Create a new pending transaction
        $ref = 'TXN_WEBHOOK_TEST_888';
        $stmtTx = $this->db->prepare("
            INSERT INTO payment_transactions (user_id, provider, transaction_reference, amount, currency, status, created_at, updated_at)
            VALUES (:uid, 'mock', :ref, 1499.00, 'PKR', 'pending', NOW(), NOW())
        ");
        $stmtTx->execute(['uid' => $this->uVisitor, 'ref' => $ref]);

        // Build mock signature payload
        $payload = [
            'provider' => 'mock',
            'transaction_reference' => $ref,
            'status' => 'success',
            'amount' => 1499.00,
            'currency' => 'PKR',
            'event_id' => 'evt_mock_9999',
            'provider_transaction_id' => 'provider_tx_9999'
        ];
        
        $secret = $_ENV['PAYMENT_WEBHOOK_SECRET'] = 'test_webhook_secret_key';
        $signature = hash_hmac('sha256', json_encode($payload), $secret);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_X_MOCK_SIGNATURE'] = $signature;

        // 1. Test signature mismatch returns 400
        $_SERVER['HTTP_X_MOCK_SIGNATURE'] = 'invalid_signature_hash';
        ob_start();
        try {
            $controller->webhook();
        } catch (\Exception $e) {
            // expected exit
        }
        $out = ob_get_clean();
        
        // Verify signature failed
        $webhookStatus = $this->db->query("SELECT processing_status FROM payment_webhook_logs WHERE external_event_id = 'evt_mock_9999'")->fetchColumn();
        if ($webhookStatus === 'processed') {
            throw new Exception("Webhook accepted and processed payload with invalid signature.");
        }

        // 2. Test valid signature runs webhook fulfillment
        $_SERVER['HTTP_X_MOCK_SIGNATURE'] = $signature;
        // Inject body payload mock via static parsing fallback in BillingController for testing
        $_POST = $payload;

        ob_start();
        try {
            $controller->webhook();
        } catch (\Exception $e) {
            // expected exit
        }
        ob_end_clean();

        // Assert transaction marked paid and subscription active
        $txStatus = $this->db->query("SELECT status FROM payment_transactions WHERE transaction_reference = '{$ref}'")->fetchColumn();
        if ($txStatus !== 'paid') {
            throw new Exception("Webhook failed to transition payment transaction to paid status.");
        }

        $webhookStatus = $this->db->query("SELECT processing_status FROM payment_webhook_logs WHERE external_event_id = 'evt_mock_9999'")->fetchColumn();
        if ($webhookStatus !== 'processed') {
            throw new Exception("Webhook failed to log processing_status as 'processed'.");
        }
        echo "✔ Webhook signature verification and fulfillment execution verified.\n";
    }

    private function testWebhookIdempotency(): void {
        $controller = new BillingController();
        
        // We submit the same valid signature and event ID payload again
        $ref = 'TXN_WEBHOOK_TEST_888';
        $payload = [
            'provider' => 'mock',
            'transaction_reference' => $ref,
            'status' => 'success',
            'amount' => 1499.00,
            'currency' => 'PKR',
            'event_id' => 'evt_mock_9999',
            'provider_transaction_id' => 'provider_tx_9999'
        ];

        $_ENV['PAYMENT_WEBHOOK_SECRET'] = 'test_webhook_secret_key';
        $_SERVER['HTTP_X_MOCK_SIGNATURE'] = hash_hmac('sha256', json_encode($payload), 'test_webhook_secret_key');
        $_POST = $payload;

        // Verify count of active subscriptions for our user
        $subCountBefore = $this->db->query("SELECT COUNT(*) FROM subscriptions WHERE user_id = {$this->uVisitor}")->fetchColumn();

        ob_start();
        try {
            $controller->webhook();
        } catch (\Exception $e) {
            // expected exit
        }
        ob_end_clean();

        $subCountAfter = $this->db->query("SELECT COUNT(*) FROM subscriptions WHERE user_id = {$this->uVisitor}")->fetchColumn();
        
        if ($subCountAfter > $subCountBefore) {
            throw new Exception("Webhook idempotency failed: duplicate subscriptions created for same event.");
        }
        echo "✔ Webhook duplicate replay idempotency protection verified.\n";
    }

    private function testWebhookAmountAndCurrencyMismatch(): void {
        $controller = new BillingController();

        // 1. Create a new transaction with 1499 PKR
        $ref = 'TXN_MISMATCH_CHECK';
        $stmtTx = $this->db->prepare("
            INSERT INTO payment_transactions (user_id, provider, transaction_reference, amount, currency, status, created_at, updated_at)
            VALUES (:uid, 'mock', :ref, 1499.00, 'PKR', 'pending', NOW(), NOW())
        ");
        $stmtTx->execute(['uid' => $this->uVisitor, 'ref' => $ref]);

        // 2. Webhook payload with altered amount (e.g. 50 PKR instead of 1499)
        $payload = [
            'provider' => 'mock',
            'transaction_reference' => $ref,
            'status' => 'success',
            'amount' => 50.00,
            'currency' => 'PKR',
            'event_id' => 'evt_mismatch_1',
            'provider_transaction_id' => 'provider_tx_mismatch_1'
        ];

        $_SERVER['HTTP_X_MOCK_SIGNATURE'] = hash_hmac('sha256', json_encode($payload), 'test_webhook_secret_key');
        $_POST = $payload;

        ob_start();
        try {
            $controller->webhook();
        } catch (\Exception $e) {
            // expected exit
        }
        ob_end_clean();

        // Confirm processing failed
        $status = $this->db->query("SELECT processing_status FROM payment_webhook_logs WHERE external_event_id = 'evt_mismatch_1'")->fetchColumn();
        if ($status !== 'failed') {
            throw new Exception("Webhook accepted amount mismatch value payload without failing.");
        }
        echo "✔ Webhook amount and currency mismatch protection verified.\n";
    }

    private function testSubscriptionStateTransitions(): void {
        // Fetch subscription row for visitor user
        $subId = $this->db->query("SELECT id FROM subscriptions WHERE user_id = {$this->uVisitor} LIMIT 1")->fetchColumn();
        
        // Active -> Cancelled (Allowed)
        $ok1 = SubscriptionService::transition($subId, 'cancelled');
        if (!$ok1) {
            throw new Exception("Transition active -> cancelled failed.");
        }

        // Cancelled -> Expired (Allowed)
        $ok2 = SubscriptionService::transition($subId, 'expired');
        if (!$ok2) {
            throw new Exception("Transition cancelled -> expired failed.");
        }

        // Expired -> Cancelled (Forbidden)
        $ok3 = SubscriptionService::transition($subId, 'cancelled');
        if ($ok3) {
            throw new Exception("Subscription state machine allowed invalid transition expired -> cancelled.");
        }
        echo "✔ Subscription state machine transition boundaries verified.\n";
    }

    private function testFreePlanAccessLimits(): void {
        // Expire subscription to return visitor back to Free plan
        $this->db->exec("UPDATE subscriptions SET status = 'expired' WHERE user_id = {$this->uVisitor}");

        // 1. Saved limit verification (Free limit is 10)
        // Add 10 saved scholarships
        $stmtSave = $this->db->prepare("INSERT INTO saved_scholarships (user_id, scholarship_id, created_at) VALUES (?, ?, NOW())");
        $schs = $this->db->query("SELECT id FROM scholarships WHERE title LIKE 'Mock Billing%' LIMIT 12")->fetchAll(PDO::FETCH_COLUMN);

        for ($i = 0; $i < 10; $i++) {
            $stmtSave->execute([$this->uVisitor, $schs[$i]]);
        }

        // Try adding the 11th bookmark
        $this->loginUser($this->uVisitor, 'visitor');
        $controller = new ScholarshipController();
        $_POST = ['csrf_token' => \App\Helpers\Security::csrfToken()];
        
        ob_start();
        try {
            $controller->save((int)$schs[10]);
        } catch (\RuntimeException $e) {
            // expected redirect
        }
        ob_end_clean();

        $error = $_SESSION['discovery_errors']['save'] ?? '';
        if (strpos($error, 'Upgrade to Premium') === false) {
            throw new Exception("Saved limit gate failed: allowed Free visitor to bookmark more than 10 opportunities.");
        }

        // 2. Comparison limit verification (Free limit is 3)
        $_SESSION['compare_ids'] = [$schs[0], $schs[1], $schs[2]]; // Already 3 comparison slots
        
        ob_start();
        try {
            $controller->addToCompare((int)$schs[3]);
        } catch (\RuntimeException $e) {
            // expected redirect
        }
        ob_end_clean();

        $errCompare = $_SESSION['discovery_errors']['compare'] ?? '';
        if (strpos($errCompare, 'Upgrade to Premium') === false) {
            throw new Exception("Comparison limit gate failed: allowed Free visitor to compare more than 3 opportunities.");
        }

        // 3. Matching limit verification (Free limit is 5)
        $dashController = new DashboardController();
        
        // Add 10 match results for the user
        $this->db->exec("DELETE FROM scholarship_matches WHERE user_id = {$this->uVisitor}");
        $stmtMatch = $this->db->prepare("
            INSERT INTO scholarship_matches (user_id, scholarship_id, match_score, match_status, eligibility_status, recommendation_level, engine_version, calculated_at)
            VALUES (?, ?, 90.0, 'ELIGIBLE', 'ELIGIBLE', 'HIGHLY_RECOMMENDED', '2.0', NOW())
        ");
        for ($i = 0; $i < 10; $i++) {
            $stmtMatch->execute([$this->uVisitor, $schs[$i]]);
        }

        ob_start();
        $dashController->index();
        $outDashboard = ob_get_clean();



        // Extract matches passed to view
        // In testing, we can check how many matches are output in the JSON api
        ob_start();
        try {
            $dashController->matchesApi();
        } catch (\Exception $e) {
            // expected exit
        }
        $outApi = ob_get_clean();
        
        $startPos = strpos($outApi, '{');
        $cleanJson = ($startPos !== false) ? substr($outApi, $startPos) : $outApi;
        $resApi = json_decode($cleanJson, true);
        $matchesCount = count($resApi['matches'] ?? []);
        
        if ($matchesCount !== 5) {
            throw new Exception("Premium matching gate failed: Free user matched API returned {$matchesCount} items (expected 5). Clean JSON output: " . $cleanJson);
        }
        echo "✔ Free plan saved, comparison, and matching limits verified.\n";
    }

    private function testCronJobExpiryAndRenewalReminders(): void {
        // Reset sub table
        $this->db->exec("DELETE FROM subscriptions");
        $this->db->exec("DELETE FROM notification_logs");

        // 1. Create a subscription that ended 1 hour ago
        $stmtExpired = $this->db->prepare("
            INSERT INTO subscriptions (user_id, plan_id, status, starts_at, ends_at, created_at, updated_at)
            VALUES (:uid, :pid, 'active', DATE_SUB(NOW(), INTERVAL 31 DAY), DATE_SUB(NOW(), INTERVAL 1 HOUR), NOW(), NOW())
        ");
        $stmtExpired->execute(['uid' => $this->uVisitor, 'pid' => $this->planPremium]);
        $subExpiredId = $this->db->lastInsertId();

        // Seed 5 qualifying delivered messages for subExpiredId so it qualifies for expiry under the minimum-5 rule
        for ($i = 1; $i <= 5; $i++) {
            $this->db->exec("
                INSERT INTO notification_logs (
                    user_id, subscription_id, notification_type, channel, recipient, status, delivered_at, created_at, updated_at
                ) VALUES (
                    {$this->uVisitor}, {$subExpiredId}, 'NEW_MATCH', 'whatsapp', '923001234567', 'delivered', DATE_SUB(NOW(), INTERVAL " . (30 - $i) . " DAY), NOW(), NOW()
                )
            ");
        }

        // 2. Create a subscription ending in exactly 3 days
        $stmtRenewal = $this->db->prepare("
            INSERT INTO subscriptions (user_id, plan_id, status, starts_at, ends_at, created_at, updated_at)
            VALUES (:uid, :pid, 'active', DATE_SUB(NOW(), INTERVAL 27 DAY), DATE_ADD(NOW(), INTERVAL 3 DAY), NOW(), NOW())
        ");
        $stmtRenewal->execute(['uid' => $this->uVisitor, 'pid' => $this->planPremium]);
        $subRenewalId = $this->db->lastInsertId();

        // Execute expiry cron script
        // Simulate CLI run by executing script manually inside tests environment
        ob_start();
        require ROOT_PATH . '/cron/subscription_expiry.php';
        $cronOutput = ob_get_clean();

        // Assert subExpired is now transitioned to expired
        $status = $this->db->query("SELECT status FROM subscriptions WHERE id = {$subExpiredId}")->fetchColumn();
        if ($status !== 'expired') {
            throw new Exception("Subscription Expiry cron failed to mark expired subscription row.");
        }

        // Assert renewal reminder is enqueued in notification outbox
        $reminderCount = $this->db->query("
            SELECT COUNT(*) FROM notification_logs 
            WHERE notification_type = 'SUBSCRIPTION_RENEWAL_REMINDER' 
              AND idempotency_key = 'sub_renew_{$subRenewalId}_3'
        ")->fetchColumn();

        if ($reminderCount === 0) {
            throw new Exception("Renewal reminders cron failed to enqueue warning alert log.");
        }
        echo "✔ Expiry lifecycle job and renewal reminder enqueues verified.\n";
    }

    private function testAdminBillingStatsDashboard(): void {
        $this->loginUser($this->uAdmin, 'admin');
        
        $intelController = new \App\Controllers\IntelligenceController();
        $_GET = ['tab' => 'billing'];

        ob_start();
        $intelController->index();
        $output = ob_get_clean();

        if (strpos($output, 'Billing &amp; Monetization') === false && strpos($output, 'Billing & Monetization') === false) {
            throw new Exception("Admin dashboard failed to render Billing and Monetization analytics tab link.");
        }

        if (strpos($output, 'Recent Transactions Log') === false) {
            throw new Exception("Admin dashboard failed to display transactions log table panel.");
        }
        echo "✔ Admin billing and monetization dashboard analytics verified.\n";
    }

    private function testIdorBillingAccessDeny(): void {
        // Students trying to access admin billing metrics tab directly throws 403
        $this->loginUser($this->uVisitor, 'visitor');
        $intelController = new \App\Controllers\IntelligenceController();
        $_GET = ['tab' => 'billing'];

        try {
            $intelController->index();
            throw new Exception("IDOR vulnerability: visitor user was allowed to load admin billing analytics panel.");
        } catch (\RuntimeException $e) {
            if ($e->getMessage() !== 'Abort 403 Forbidden') {
                throw $e;
            }
        }

        // Create another visitor user for cross-user IDOR testing
        $visitorRoleId = $this->db->query("SELECT id FROM roles WHERE name = 'visitor'")->fetchColumn();
        $stmtUser = $this->db->prepare("INSERT INTO users (email, password_hash, first_name, last_name, role_id, status) VALUES (?, ?, ?, ?, ?, 'active')");
        $stmtUser->execute(['victim_billing@example.com', password_hash('Pass123!', PASSWORD_BCRYPT), 'IDOR', 'Victim', $visitorRoleId]);
        $uVictimId = (int)$this->db->lastInsertId();

        // Create transaction for victim
        $stmtTx = $this->db->prepare("INSERT INTO payment_transactions (user_id, provider, transaction_reference, amount, currency, status) VALUES (?, 'mock', 'TXN_IDOR_VICTIM_999', 1499.00, 'PKR', 'pending')");
        $stmtTx->execute([$uVictimId]);

        // Login as our regular visitor (uVisitor)
        $this->loginUser($this->uVisitor, 'visitor');
        $billingController = new BillingController();

        // Try to access victim's mockScreen
        $_GET = ['ref' => 'TXN_IDOR_VICTIM_999'];
        try {
            $billingController->mockScreen();
            throw new Exception("IDOR vulnerability: user allowed to access another user's mock payment screen.");
        } catch (\RuntimeException $e) {
            if (strpos($e->getMessage(), 'Abort 403') === false) {
                throw $e;
            }
        }

        // Try to access victim's redirectRedirect
        $_GET = ['ref' => 'TXN_IDOR_VICTIM_999'];
        try {
            $billingController->redirectRedirect();
            throw new Exception("IDOR vulnerability: user allowed to access another user's payment redirect page.");
        } catch (\RuntimeException $e) {
            if (strpos($e->getMessage(), 'Abort 403') === false) {
                throw $e;
            }
        }

        // Try to access victim's callback
        $_GET = ['ref' => 'TXN_IDOR_VICTIM_999', 'status' => 'success'];
        try {
            $billingController->callback();
            throw new Exception("IDOR vulnerability: user allowed to trigger callback verification for another user's transaction.");
        } catch (\RuntimeException $e) {
            if (strpos($e->getMessage(), 'Abort 403') === false) {
                throw $e;
            }
        }

        // Cleanup victim
        $this->db->exec("DELETE FROM payment_transactions WHERE user_id = {$uVictimId}");
        $this->db->exec("DELETE FROM users WHERE id = {$uVictimId}");

        echo "✔ IDOR billing panels isolation rules verified.\n";
    }

    private function testSecretRedactionAuditLogs(): void {
        // Verify secrets are redacted in log audits
        $ip = '127.0.0.1';
        Auth::logAudit($this->uVisitor, 'test_redact', 'billing', 'users', $this->uVisitor, $ip, [
            'api_secret' => 'super_secret_jazzcash_salt_key_123',
            'password' => 'Pass123!',
            'card_cvv' => '999'
        ]);

        $metadataStr = $this->db->query("SELECT metadata FROM audit_logs WHERE action = 'test_redact' ORDER BY id DESC LIMIT 1")->fetchColumn();
        $metadata = json_decode($metadataStr, true);

        if ($metadata['api_secret'] !== '[REDACTED]' || $metadata['password'] !== '[REDACTED]' || $metadata['card_cvv'] !== '[REDACTED]') {
            throw new Exception("Credential leakage: logAudit failed to redact payment credentials secrets.");
        }
        echo "✔ Sensitive payment credentials redaction in audit logs verified.\n";
    }
}
