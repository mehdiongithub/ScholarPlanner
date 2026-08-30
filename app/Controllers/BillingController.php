<?php

namespace App\Controllers;

use App\Services\Auth;
use App\Services\Database;
use App\Services\PaymentService;
use App\Services\SubscriptionService;
use App\Helpers\Security;
use PDO;
use Exception;
use RuntimeException;

class BillingController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::connection();
    }

    /**
     * Helper to safely redirect, throwing exception in tests to prevent process exit.
     */
    private function redirect(string $url): void {
        header("Location: " . $url);
        if (defined('TESTING_MODE') && TESTING_MODE) {
            throw new RuntimeException("Redirect to " . $url);
        }
        exit();
    }

    /**
     * Helper to safely abort/respond with error code.
     */
    private function abort(int $code, string $message = ''): void {
        http_response_code($code);
        echo $message;
        if (defined('TESTING_MODE') && TESTING_MODE) {
            throw new RuntimeException("Abort $code: $message");
        }
        exit();
    }

    /**
     * GET /pricing
     */
    public function pricing(): void {
        $userId = Auth::userId();
        $plan = SubscriptionService::getActivePlan($userId ?: 0);
        $error = $_GET['error'] ?? '';

        view('billing.pricing', [
            'current_plan' => $plan['plan_slug'],
            'error' => $error,
            'csrf_token' => Security::csrfToken()
        ]);
    }

    /**
     * GET /checkout
     */
    public function checkout(): void {
        Auth::requireAuth();
        $planSlug = trim($_GET['plan'] ?? '');

        $stmt = $this->db->prepare("SELECT * FROM subscription_plans WHERE slug = :slug AND status = 'active' LIMIT 1");
        $stmt->execute(['slug' => $planSlug]);
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$plan) {
            $this->redirect(url('/pricing?error=Invalid subscription plan selected.'));
        }

        view('billing.checkout', [
            'plan' => $plan,
            'csrf_token' => Security::csrfToken()
        ]);
    }

    /**
     * POST /checkout
     */
    public function processCheckout(): void {
        Auth::requireAuth();
        $csrf = $_POST['csrf_token'] ?? '';
        if (!Security::verifyCsrfToken($csrf)) {
            $this->redirect(url('/pricing?error=CSRF token verification failed.'));
        }

        $planSlug = trim($_POST['plan_slug'] ?? '');
        $provider = trim($_POST['payment_provider'] ?? 'mock');

        $stmt = $this->db->prepare("SELECT * FROM subscription_plans WHERE slug = :slug AND status = 'active' LIMIT 1");
        $stmt->execute(['slug' => $planSlug]);
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$plan) {
            $this->redirect(url('/pricing?error=Invalid subscription plan selected.'));
        }

        $userId = Auth::userId();
        $ref = 'TXN_' . strtoupper(bin2hex(random_bytes(8)));

        // Create transaction record
        $this->db->beginTransaction();
        try {
            // Check if user already has an active subscription for this plan to link it
            $stmtSub = $this->db->prepare("SELECT id FROM subscriptions WHERE user_id = :uid AND plan_id = :pid ORDER BY id DESC LIMIT 1");
            $stmtSub->execute(['uid' => $userId, 'pid' => $plan['id']]);
            $subId = $stmtSub->fetchColumn() ?: null;

            $stmtTx = $this->db->prepare("
                INSERT INTO payment_transactions (
                    user_id, subscription_id, provider, transaction_reference, 
                    amount, currency, status, created_at, updated_at
                ) VALUES (
                    :uid, :sub_id, :provider, :ref, :amount, :currency, 'pending', NOW(), NOW()
                )
            ");
            $stmtTx->execute([
                'uid' => $userId,
                'sub_id' => $subId,
                'provider' => $provider,
                'ref' => $ref,
                'amount' => $plan['price'],
                'currency' => $plan['currency']
            ]);

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            $cleanMsg = \App\Services\Logger::redactSensitiveString($e->getMessage());
            $this->redirect(url('/pricing?error=Database failure creating transaction: ' . urlencode($cleanMsg)));
        }

        // Fetch configured gateway
        // Temporarily override provider env for checkout creation resolver
        $_ENV['PAYMENT_PROVIDER'] = $provider;
        $gateway = PaymentService::gateway();

        $checkoutData = $gateway->createCheckout([
            'user_id' => $userId,
            'amount' => $plan['price'],
            'currency' => $plan['currency'],
            'email' => $_SESSION['user_email'] ?? '',
            'callback_url' => url("/checkout/callback"),
            'transaction_reference' => $ref
        ]);

        Auth::logAudit($userId, 'payment_created', 'billing', 'payment_transactions', 0, null, ['ref' => $ref]);

        $this->redirect($checkoutData['checkout_url']);
    }

    /**
     * GET /checkout/callback
     */
    public function callback(): void {
        Auth::requireAuth();
        $ref = trim($_GET['ref'] ?? $_GET['pp_TxnRefNo'] ?? $_GET['orderId'] ?? '');
        
        $stmt = $this->db->prepare("SELECT * FROM payment_transactions WHERE transaction_reference = :ref LIMIT 1");
        $stmt->execute(['ref' => $ref]);
        $tx = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tx) {
            $_SESSION['billing_error'] = "Transaction reference '$ref' not found.";
            $this->redirect(url('/pricing'));
        }

        // Security check: IDOR validation
        if ((int)$tx['user_id'] !== Auth::userId()) {
            $this->abort(403, "Access Denied: Payment transaction ownership mismatch.");
        }

        if ($tx['status'] === 'paid' || $tx['status'] === 'success') {
            $_SESSION['billing_success'] = "Payment verified successfully. Welcome to Premium!";
            $this->redirect(url('/billing'));
        }

        // Server side verification
        $_ENV['PAYMENT_PROVIDER'] = $tx['provider'];
        $gateway = PaymentService::gateway();
        $res = $gateway->verifyPayment(array_merge($_GET, $_POST));

        $this->db->beginTransaction();
        try {
            // Re-fetch with FOR UPDATE to prevent race conditions with concurrent webhooks
            $stmtLock = $this->db->prepare("SELECT * FROM payment_transactions WHERE id = :id LIMIT 1 FOR UPDATE");
            $stmtLock->execute(['id' => $tx['id']]);
            $txLocked = $stmtLock->fetch(PDO::FETCH_ASSOC);

            if ($txLocked['status'] === 'paid' || $txLocked['status'] === 'success') {
                $this->db->commit();
                $_SESSION['billing_success'] = "Payment verified successfully. Welcome to Premium!";
                $this->redirect(url('/billing'));
            }

            if ($res['status'] === 'success') {
                // Update transaction status
                $stmtUpd = $this->db->prepare("
                    UPDATE payment_transactions 
                    SET status = 'paid', provider_transaction_id = :ptx, paid_at = NOW(), updated_at = NOW() 
                    WHERE id = :id
                ");
                $stmtUpd->execute([
                    'ptx' => $res['provider_transaction_id'],
                    'id' => $tx['id']
                ]);

                // Activate/create subscription
                $stmtPlan = $this->db->prepare("
                    SELECT p.* FROM subscription_plans p 
                    WHERE p.price = :price AND p.currency = :currency AND p.status = 'active'
                    LIMIT 1
                ");
                $stmtPlan->execute(['price' => $res['amount'], 'currency' => $res['currency']]);
                $plan = $stmtPlan->fetch(PDO::FETCH_ASSOC);

                if (!$plan) {
                    $plan = $this->db->query("SELECT * FROM subscription_plans WHERE slug = 'premium-monthly' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
                }

                $stmtSub = $this->db->prepare("
                    SELECT * FROM subscriptions 
                    WHERE user_id = :uid AND plan_id = :pid 
                    ORDER BY id DESC LIMIT 1
                ");
                $stmtSub->execute(['uid' => $tx['user_id'], 'pid' => $plan['id']]);
                $sub = $stmtSub->fetch(PDO::FETCH_ASSOC);

                $endsAt = date('Y-m-d H:i:s', time() + 30 * 86400);

                if ($sub) {
                    $stmtSubUpd = $this->db->prepare("
                        UPDATE subscriptions 
                        SET status = 'active', starts_at = NOW(), ends_at = :ends, cancelled_at = NULL, auto_renew = 1, updated_at = NOW() 
                        WHERE id = :id
                    ");
                    $stmtSubUpd->execute(['ends' => $endsAt, 'id' => $sub['id']]);
                    $subId = $sub['id'];
                } else {
                    $stmtSubIns = $this->db->prepare("
                        INSERT INTO subscriptions (
                            user_id, plan_id, status, starts_at, ends_at, auto_renew, provider, provider_subscription_id, created_at, updated_at
                        ) VALUES (
                            :uid, :pid, 'active', NOW(), :ends, 1, :provider, :sub_id, NOW(), NOW()
                        )
                    ");
                    $stmtSubIns->execute([
                        'uid' => $tx['user_id'],
                        'pid' => $plan['id'],
                        'ends' => $endsAt,
                        'provider' => $tx['provider'],
                        'sub_id' => $res['provider_transaction_id']
                    ]);
                    $subId = $this->db->lastInsertId();
                }

                $stmtLink = $this->db->prepare("UPDATE payment_transactions SET subscription_id = :sub_id WHERE id = :id");
                $stmtLink->execute(['sub_id' => $subId, 'id' => $tx['id']]);

                Auth::logAudit($tx['user_id'], 'payment_verified', 'billing', 'payment_transactions', $tx['id']);
                Auth::logAudit($tx['user_id'], 'subscription_activated', 'subscriptions', 'subscriptions', $subId);

                $_SESSION['billing_success'] = "Payment verified successfully. Welcome to Premium!";
            } else {
                $stmtUpd = $this->db->prepare("
                    UPDATE payment_transactions 
                    SET status = 'failed', failed_at = NOW(), updated_at = NOW() 
                    WHERE id = :id
                ");
                $stmtUpd->execute(['id' => $tx['id']]);

                Auth::logAudit($tx['user_id'], 'payment_failed', 'billing', 'payment_transactions', $tx['id']);

                $_SESSION['billing_error'] = "Payment verification failed. Please try again.";
            }

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            $_SESSION['billing_error'] = "Internal error during callback validation: " . \App\Services\Logger::redactSensitiveString($e->getMessage());
        }

        $this->redirect(url('/billing'));
    }

    /**
     * POST /api/payments/webhook
     */
    public function webhook(): void {
        header('Content-Type: application/json');
        
        $body = file_get_contents('php://input');
        $payload = json_decode($body, true) ?: $_POST;
        
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) <> 'HTTP_') {
                continue;
            }
            $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
            $headers[$header] = $value;
        }

        $provider = $payload['provider'] ?? $payload['pp_Version'] ?? ($_GET['provider'] ?? 'mock');
        $provider = strpos($provider, 'mock') !== false ? 'mock' : (strpos($provider, '1.1') !== false ? 'jazzcash' : $provider);

        $_ENV['PAYMENT_PROVIDER'] = $provider;
        $gateway = PaymentService::gateway();

        // 1. Webhook Signature Verification
        if (!$gateway->verifyWebhookSignature($payload, $headers)) {
            $this->abort(400, json_encode(['error' => 'Invalid webhook signature']));
        }

        $res = $gateway->handleWebhook($payload, $headers);
        $ref = $res['transaction_reference'];
        $eventId = $payload['event_id'] ?? $payload['pp_TxnRefNo'] ?? $ref;

        $payloadHash = hash('sha256', json_encode($payload));

        // Start transaction for checking idempotency log and locking transaction row
        $this->db->beginTransaction();
        try {
            // 2. Webhook Idempotency Check with FOR UPDATE lock
            $stmtCheck = $this->db->prepare("SELECT id, processing_status FROM payment_webhook_logs WHERE external_event_id = :evt LIMIT 1 FOR UPDATE");
            $stmtCheck->execute(['evt' => $eventId]);
            $existingLog = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($existingLog) {
                if ($existingLog['processing_status'] === 'processed') {
                    $this->db->commit();
                    http_response_code(200);
                    echo json_encode(['message' => 'Event already processed']);
                    if (defined('TESTING_MODE') && TESTING_MODE) { return; }
                    exit();
                }
            }

            // Create initial pending log record if not exists
            if (!$existingLog) {
                $stmtLog = $this->db->prepare("
                    INSERT INTO payment_webhook_logs (
                        provider, event_type, external_event_id, transaction_reference, payload_hash, processing_status, created_at
                    ) VALUES (
                        :provider, :type, :evt, :ref, :hash, 'pending', NOW()
                    )
                ");
                $stmtLog->execute([
                    'provider' => $provider,
                    'type' => $res['event_type'],
                    'evt' => $eventId,
                    'ref' => $ref,
                    'hash' => $payloadHash
                ]);
                $webhookLogId = $this->db->lastInsertId();
            } else {
                $webhookLogId = $existingLog['id'];
            }

            // Lock the transaction record to prevent race conditions with redirect callback
            $stmtTx = $this->db->prepare("SELECT * FROM payment_transactions WHERE transaction_reference = :ref LIMIT 1 FOR UPDATE");
            $stmtTx->execute(['ref' => $ref]);
            $tx = $stmtTx->fetch(PDO::FETCH_ASSOC);

            if (!$tx) {
                $stmtFail = $this->db->prepare("UPDATE payment_webhook_logs SET processing_status = 'failed', failure_reason = 'Transaction ref not found' WHERE id = :id");
                $stmtFail->execute(['id' => $webhookLogId]);
                $this->db->commit();
                $this->abort(404, json_encode(['error' => 'Transaction not found']));
            }

            // Check if already paid (either by concurrent webhook or redirect callback)
            if ($tx['status'] === 'paid' || $tx['status'] === 'success') {
                $stmtProcessed = $this->db->prepare("UPDATE payment_webhook_logs SET processing_status = 'processed', processed_at = NOW() WHERE id = :id");
                $stmtProcessed->execute(['id' => $webhookLogId]);
                $this->db->commit();
                http_response_code(200);
                echo json_encode(['status' => 'success', 'message' => 'Already paid']);
                if (defined('TESTING_MODE') && TESTING_MODE) { return; }
                exit();
            }

            // Amount & Currency Verification
            if ((float)$tx['amount'] !== (float)$res['amount'] || $tx['currency'] !== $res['currency']) {
                $stmtFail = $this->db->prepare("UPDATE payment_webhook_logs SET processing_status = 'failed', failure_reason = 'Amount or currency mismatch' WHERE id = :id");
                $stmtFail->execute(['id' => $webhookLogId]);
                $this->db->commit();
                $this->abort(400, json_encode(['error' => 'Amount or currency mismatch']));
            }

            if ($res['status'] === 'success') {
                $stmtUpd = $this->db->prepare("
                    UPDATE payment_transactions 
                    SET status = 'paid', provider_transaction_id = :ptx, paid_at = NOW(), updated_at = NOW() 
                    WHERE id = :id
                ");
                $stmtUpd->execute([
                    'ptx' => $res['provider_transaction_id'],
                    'id' => $tx['id']
                ]);

                // Subscription activation
                $stmtPlan = $this->db->prepare("SELECT id FROM subscription_plans WHERE price = :price AND currency = :currency AND status = 'active' LIMIT 1");
                $stmtPlan->execute(['price' => $res['amount'], 'currency' => $res['currency']]);
                $planId = $stmtPlan->fetchColumn();

                if (!$planId) {
                    $planId = $this->db->query("SELECT id FROM subscription_plans WHERE slug = 'premium-monthly' LIMIT 1")->fetchColumn();
                }

                $stmtSub = $this->db->prepare("SELECT id FROM subscriptions WHERE user_id = :uid AND plan_id = :pid ORDER BY id DESC LIMIT 1");
                $stmtSub->execute(['uid' => $tx['user_id'], 'pid' => $planId]);
                $sub = $stmtSub->fetch(PDO::FETCH_ASSOC);

                $endsAt = date('Y-m-d H:i:s', time() + 30 * 86400);

                if ($sub) {
                    $stmtSubUpd = $this->db->prepare("
                        UPDATE subscriptions 
                        SET status = 'active', starts_at = NOW(), ends_at = :ends, cancelled_at = NULL, auto_renew = 1, updated_at = NOW() 
                        WHERE id = :id
                    ");
                    $stmtSubUpd->execute(['ends' => $endsAt, 'id' => $sub['id']]);
                    $subId = $sub['id'];
                } else {
                    $stmtSubIns = $this->db->prepare("
                        INSERT INTO subscriptions (
                            user_id, plan_id, status, starts_at, ends_at, auto_renew, provider, provider_subscription_id, created_at, updated_at
                        ) VALUES (
                            :uid, :pid, 'active', NOW(), :ends, 1, :provider, :sub_id, NOW(), NOW()
                        )
                    ");
                    $stmtSubIns->execute([
                        'uid' => $tx['user_id'],
                        'pid' => $planId,
                        'ends' => $endsAt,
                        'provider' => $provider,
                        'sub_id' => $res['provider_transaction_id']
                    ]);
                    $subId = $this->db->lastInsertId();
                }

                $stmtLink = $this->db->prepare("UPDATE payment_transactions SET subscription_id = :sub_id WHERE id = :id");
                $stmtLink->execute(['sub_id' => $subId, 'id' => $tx['id']]);

                Auth::logAudit($tx['user_id'], 'webhook_payment_verified', 'billing', 'payment_transactions', $tx['id']);
            } else {
                $stmtUpd = $this->db->prepare("
                    UPDATE payment_transactions 
                    SET status = 'failed', failed_at = NOW(), updated_at = NOW() 
                    WHERE id = :id
                ");
                $stmtUpd->execute(['id' => $tx['id']]);
            }

            // Mark webhook log as processed
            $stmtProcessed = $this->db->prepare("UPDATE payment_webhook_logs SET processing_status = 'processed', processed_at = NOW() WHERE id = :id");
            $stmtProcessed->execute(['id' => $webhookLogId]);

            $this->db->commit();
            
            http_response_code(200);
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            $this->db->rollBack();
            $cleanErr = \App\Services\Logger::redactSensitiveString($e->getMessage());
            $stmtFail = $this->db->prepare("UPDATE payment_webhook_logs SET processing_status = 'failed', failure_reason = :err WHERE id = :id");
            $stmtFail->execute(['err' => $cleanErr, 'id' => $webhookLogId]);
            $this->abort(500, json_encode(['error' => 'Webhook execution error: ' . $cleanErr]));
        }
        if (defined('TESTING_MODE') && TESTING_MODE) { return; }
        exit();
    }

    /**
     * POST /checkout/cancel
     */
    public function cancel(): void {
        Auth::requireAuth();
        $csrf = $_POST['csrf_token'] ?? '';
        if (!Security::verifyCsrfToken($csrf)) {
            $_SESSION['billing_error'] = 'CSRF verification failed.';
            $this->redirect(url('/billing'));
        }

        $userId = Auth::userId();
        
        $stmt = $this->db->prepare("
            SELECT id, status FROM subscriptions 
            WHERE user_id = :uid AND status = 'active'
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute(['uid' => $userId]);
        $sub = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$sub) {
            $_SESSION['billing_error'] = 'No active subscription found to cancel.';
            $this->redirect(url('/billing'));
        }

        // State Machine transition to cancelled
        if (SubscriptionService::transition($sub['id'], 'cancelled')) {
            $stmtUpd = $this->db->prepare("UPDATE subscriptions SET auto_renew = 0, cancelled_at = NOW() WHERE id = :id");
            $stmtUpd->execute(['id' => $sub['id']]);
            
            $_SESSION['billing_success'] = 'Subscription auto-renewal cancelled successfully.';
        } else {
            $_SESSION['billing_error'] = 'Failed to transition subscription status.';
        }

        $this->redirect(url('/billing'));
    }

    /**
     * POST /admin/billing/refund
     */
    public function refund(): void {
        Auth::requireRole('admin');
        
        $csrf = $_POST['csrf_token'] ?? '';
        if (!Security::verifyCsrfToken($csrf)) {
            $_SESSION['intelligence_error'] = 'CSRF verification failed.';
            $this->redirect(url('/admin/intelligence?tab=billing'));
        }

        $txIdToken = $_POST['transaction_id'] ?? '';
        $txId = decode_id($txIdToken);
        if ($txId === null) {
            $_SESSION['intelligence_error'] = 'Invalid transaction ID.';
            $this->redirect(url('/admin/intelligence?tab=billing'));
        }
        
        $stmt = $this->db->prepare("SELECT * FROM payment_transactions WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $txId]);
        $tx = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tx || $tx['status'] !== 'paid') {
            $_SESSION['intelligence_error'] = 'Transaction not found or not eligible for refund.';
            $this->redirect(url('/admin/intelligence?tab=billing'));
        }

        $_ENV['PAYMENT_PROVIDER'] = $tx['provider'];
        $gateway = PaymentService::gateway();

        $res = $gateway->refundPayment($tx['provider_transaction_id'] ?: '', (float)$tx['amount']);

        if ($res['status'] === 'success') {
            $this->db->beginTransaction();
            try {
                $stmtUpd = $this->db->prepare("UPDATE payment_transactions SET status = 'refunded', updated_at = NOW() WHERE id = :id");
                $stmtUpd->execute(['id' => $tx['id']]);

                if ($tx['subscription_id']) {
                    SubscriptionService::transition($tx['subscription_id'], 'expired');
                }

                Auth::logAudit(Auth::userId(), 'payment_refunded', 'billing', 'payment_transactions', $tx['id'], null, ['amount' => $tx['amount']]);
                
                $this->db->commit();
                $_SESSION['intelligence_success'] = 'Transaction refunded successfully.';
            } catch (Exception $e) {
                $this->db->rollBack();
                $_SESSION['intelligence_error'] = 'Refund update database failed: ' . \App\Services\Logger::redactSensitiveString($e->getMessage());
            }
        } else {
            $_SESSION['intelligence_error'] = 'Payment provider gateway rejected refund.';
        }

        $this->redirect(url('/admin/intelligence?tab=billing'));
    }

    public function mockScreen(): void {
        Auth::requireAuth();
        $ref = trim($_GET['ref'] ?? '');
        $stmt = $this->db->prepare("SELECT * FROM payment_transactions WHERE transaction_reference = :ref LIMIT 1");
        $stmt->execute(['ref' => $ref]);
        $tx = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tx) {
            $this->abort(404, "Invalid transaction reference");
        }

        if ((int)$tx['user_id'] !== Auth::userId()) {
            $this->abort(403, "Access Denied: Payment transaction ownership mismatch.");
        }

        view('billing.mock_screen', [
            'transaction' => $tx
        ]);
    }

    /**
     * GET /billing
     */
    public function billing(): void {
        Auth::requireAuth();
        $userId = Auth::userId();

        $subscription = $this->db->prepare("
            SELECT * FROM subscriptions 
            WHERE user_id = :uid 
            ORDER BY id DESC LIMIT 1
        ");
        $subscription->execute(['uid' => $userId]);
        $sub = $subscription->fetch(PDO::FETCH_ASSOC) ?: null;

        $plan = SubscriptionService::getActivePlan($userId);

        $stmtTx = $this->db->prepare("
            SELECT * FROM payment_transactions 
            WHERE user_id = :uid 
            ORDER BY id DESC
        ");
        $stmtTx->execute(['uid' => $userId]);
        $transactions = $stmtTx->fetchAll(PDO::FETCH_ASSOC);

        view('billing.billing', [
            'subscription' => $sub,
            'plan' => $plan,
            'transactions' => $transactions,
            'csrf_token' => Security::csrfToken()
        ]);
    }

    /**
     * GET /checkout/redirect
     * Hosted redirect page utility for POST based checkouts (JazzCash/Easypaisa sandbox auto submit forms)
     */
    public function redirectRedirect(): void {
        Auth::requireAuth();
        $ref = trim($_GET['ref'] ?? '');
        $stmt = $this->db->prepare("SELECT * FROM payment_transactions WHERE transaction_reference = :ref LIMIT 1");
        $stmt->execute(['ref' => $ref]);
        $tx = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tx) {
            $this->abort(404, "Transaction reference not found.");
        }

        if ((int)$tx['user_id'] !== Auth::userId()) {
            $this->abort(403, "Access Denied: Payment transaction ownership mismatch.");
        }

        if ($tx['provider'] === 'jazzcash') {
            $salt = $_ENV['JAZZCASH_INTEGRITY_SALT'] ?? '';
            $merchantId = $_ENV['JAZZCASH_MERCHANT_ID'] ?? '';
            $password = $_ENV['JAZZCASH_PASSWORD'] ?? '';
            $apiUrl = $_ENV['JAZZCASH_API_URL'] ?? 'https://sandbox.jazzcash.com.pk/CustomerPortal/transactionPage';

            $postParams = [
                'pp_Version' => '1.1',
                'pp_TxnType' => 'MWALLET',
                'pp_Language' => 'EN',
                'pp_MerchantID' => $merchantId,
                'pp_SubMerchantID' => '',
                'pp_Password' => $password,
                'pp_TxnRefNo' => $tx['transaction_reference'],
                'pp_Amount' => (string)($tx['amount'] * 100), // convert to Paisa
                'pp_TxnCurrency' => $tx['currency'],
                'pp_TxnDateTime' => date('YmdHis'),
                'pp_BillReference' => 'billRef123',
                'pp_Description' => 'Premium Subscription plan purchase',
                'pp_TxnExpiryDateTime' => date('YmdHis', time() + 3600),
                'pp_ReturnURL' => url("/checkout/callback"),
                'pp_SecureHash' => ''
            ];

            // Generate signature
            ksort($postParams);
            $str = '';
            foreach ($postParams as $key => $val) {
                if ($val !== '' && $key !== 'pp_SecureHash') {
                    $str .= '&' . $val;
                }
            }
            $str = $salt . $str;
            $postParams['pp_SecureHash'] = hash_hmac('sha256', $str, $salt);

            $this->renderAutoPostForm($apiUrl, $postParams);
        } elseif ($tx['provider'] === 'easypaisa') {
            $apiUrl = $_ENV['EASYPAISA_API_URL'] ?? 'https://easypay.easypaisa.com.pk/easypay/Index.js';
            $storeId = $_ENV['EASYPAISA_STORE_ID'] ?? '';

            $postParams = [
                'storeId' => $storeId,
                'amount' => $tx['amount'],
                'postBackURL' => url("/checkout/callback"),
                'orderId' => $tx['transaction_reference'],
                'transactionRefNumber' => $tx['transaction_reference']
            ];

            $this->renderAutoPostForm($apiUrl, $postParams);
        } else {
            $this->redirect(url("/checkout/mock-screen?ref=" . urlencode($tx['transaction_reference'])));
        }
    }

    private function renderAutoPostForm(string $url, array $params): void {
        echo "<!DOCTYPE html>
        <html>
        <head><title>Redirecting to Payment Gateway...</title></head>
        <body onload=\"document.forms[0].submit()\">
            <div style=\"text-align:center; margin-top:100px; font-family:sans-serif;\">
                <h3>Redirecting to Sandbox Payment Gateway...</h3>
                <p>Please wait a moment. Do not refresh this page.</p>
                <form method=\"POST\" action=\"" . e($url) . "\">";
                foreach ($params as $k => $v) {
                    echo "<input type=\"hidden\" name=\"" . e($k) . "\" value=\"" . e($v) . "\">";
                }
        echo "  </form>
            </div>
        </body>
        </html>";
        if (defined('TESTING_MODE') && TESTING_MODE) { return; }
        exit();
    }
}
