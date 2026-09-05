<?php

namespace App\Services;

use Exception;
use RuntimeException;

class CashMaalPaymentGateway implements PaymentGatewayInterface {
    private string $webId;
    private string $ipnKey;
    private string $payUrl;
    private string $verifyUrl;
    private int $timeout;
    private string $currency;

    public function __construct(?array $overrideConfig = null) {
        $config = require ROOT_PATH . '/config/payment.php';
        $cm = $overrideConfig ?? ($config['cashmaal'] ?? []);

        $this->webId = trim((string)($cm['web_id'] ?? ''));
        $this->ipnKey = trim((string)($cm['ipn_key'] ?? ''));
        $this->payUrl = trim((string)($cm['pay_url'] ?? 'https://cmaal.com/Pay/'));
        $this->verifyUrl = trim((string)($cm['verify_url'] ?? 'https://api.cmaal.com/verify_v2'));
        $this->timeout = (int)($cm['timeout'] ?? 15);
        $this->currency = strtoupper(trim((string)($cm['currency'] ?? 'PKR')));
    }

    public function getWebId(): string {
        return $this->webId;
    }

    public function getIpnKey(): string {
        return $this->ipnKey;
    }

    public function getPayUrl(): string {
        return $this->payUrl;
    }

    public function getVerifyUrl(): string {
        return $this->verifyUrl;
    }

    /**
     * Check if CashMaal credentials are configured.
     */
    public function isConfigured(): bool {
        return !empty($this->webId) && !empty($this->ipnKey);
    }

    /**
     * Create checkout transaction / URL.
     *
     * @param array $data Contains keys: user_id, amount, currency, email, callback_url, transaction_reference, plan_name (optional)
     * @return array Contains keys: checkout_url, transaction_reference, post_data (for form submission)
     */
    public function createCheckout(array $data): array {
        if (!$this->isConfigured()) {
            throw new RuntimeException("CashMaal configuration is incomplete. Merchant web_id and ipn_key are required.");
        }

        $ref = $data['transaction_reference'] ?? '';
        if (empty($ref)) {
            throw new RuntimeException("Transaction reference is required to initiate CashMaal payment.");
        }

        $normAmount = self::normalizeToMinorUnits($data['amount'] ?? null, 2);
        if ($normAmount === null || $normAmount <= 0) {
            throw new RuntimeException("Invalid payment amount.");
        }
        $amount = sprintf('%d.%02d', intdiv($normAmount, 100), $normAmount % 100);

        $currency = strtoupper(trim((string)($data['currency'] ?? $this->currency)));
        $email = trim((string)($data['email'] ?? ''));
        $callbackUrl = $data['callback_url'] ?? url('/checkout/callback');
        $cancelUrl = $data['cancel_url'] ?? url('/pricing?cancelled=1');
        $addiInfo = trim((string)($data['plan_name'] ?? 'ScholarPlanner Subscription'));

        // CashMaal pay parameters
        $postData = [
            'pay_method' => '',
            'amount' => $amount,
            'currency' => $currency,
            'succes_url' => $callbackUrl,
            'cancel_url' => $cancelUrl,
            'client_email' => $email,
            'web_id' => $this->webId,
            'order_id' => $ref,
            'addi_info' => $addiInfo
        ];

        // Query string format for redirect / checkout URL
        $checkoutUrl = $this->payUrl . (strpos($this->payUrl, '?') === false ? '?' : '&') . http_build_query($postData);

        return [
            'checkout_url' => $checkoutUrl,
            'transaction_reference' => $ref,
            'post_data' => $postData
        ];
    }

    /**
     * Verify payment status from callback / IPN params.
     *
     * @param array $params Query string / POST params returned to callback or IPN
     * @return array Contains keys: status ('success'|'failed'|'pending'|'cancelled'), provider_transaction_id, amount, currency, transaction_reference, error
     */
    public function verifyPayment(array $params): array {
        if (!$this->isConfigured()) {
            return [
                'status' => 'failed',
                'provider_transaction_id' => null,
                'amount' => 0.0,
                'currency' => $this->currency,
                'transaction_reference' => '',
                'error' => 'CashMaal configuration is incomplete.'
            ];
        }

        $incomingKey = trim((string)($params['ipn_key'] ?? ''));
        $incomingWebId = trim((string)($params['web_id'] ?? ''));
        $cmTid = trim((string)($params['CM_TID'] ?? ($params['cm_tid'] ?? ($params['transaction_id'] ?? ''))));
        $orderId = trim((string)($params['order_id'] ?? ($params['ref'] ?? '')));
        $rawStatus = (string)($params['status'] ?? '');
        $amount = trim((string)($params['Amount'] ?? ($params['amount'] ?? '0.00')));
        $currency = strtoupper(trim((string)($params['currency'] ?? $this->currency)));

        // 1. IPN Key verification using timing-safe hash_equals
        if (empty($incomingKey) || !hash_equals($this->ipnKey, $incomingKey)) {
            return [
                'status' => 'failed',
                'provider_transaction_id' => $cmTid ?: null,
                'amount' => $amount,
                'currency' => $currency,
                'transaction_reference' => $orderId,
                'error' => 'Invalid or missing CashMaal IPN key.'
            ];
        }

        // 2. Web ID check if provided in payload
        if (!empty($incomingWebId) && strcasecmp($incomingWebId, $this->webId) !== 0) {
            return [
                'status' => 'failed',
                'provider_transaction_id' => $cmTid ?: null,
                'amount' => $amount,
                'currency' => $currency,
                'transaction_reference' => $orderId,
                'error' => 'Invalid web_id.'
            ];
        }

        // 3. Status verification
        // In CashMaal: 1 = Successful, 2 = Pending, 3 = Rejected, 0 = Cancelled
        if ($rawStatus === '1') {
            return [
                'status' => 'success',
                'provider_transaction_id' => $cmTid,
                'amount' => $amount,
                'currency' => $currency,
                'transaction_reference' => $orderId,
                'error' => null
            ];
        } elseif ($rawStatus === '2') {
            return [
                'status' => 'pending',
                'provider_transaction_id' => $cmTid,
                'amount' => $amount,
                'currency' => $currency,
                'transaction_reference' => $orderId,
                'error' => 'Payment status is pending in CashMaal.'
            ];
        } elseif ($rawStatus === '0') {
            return [
                'status' => 'cancelled',
                'provider_transaction_id' => $cmTid,
                'amount' => $amount,
                'currency' => $currency,
                'transaction_reference' => $orderId,
                'error' => 'Payment was cancelled by the customer.'
            ];
        } else {
            return [
                'status' => 'failed',
                'provider_transaction_id' => $cmTid,
                'amount' => $amount,
                'currency' => $currency,
                'transaction_reference' => $orderId,
                'error' => 'Payment was rejected or failed in CashMaal (status: ' . $rawStatus . ').'
            ];
        }
    }

    /**
     * Query CashMaal server-side verification API for independent verification.
     * Maps documented CashMaal response fields:
     * status, transaction_id, order_id, PKR_amount, USD_amount, receiver_account, etc.
     */
    public function verifyTransactionWithApi(string $cmTid, ?string $expectedOrderId = null, $expectedAmount = null, string $currency = 'PKR'): array {
        if (empty($cmTid) || !$this->isConfigured()) {
            return [
                'verified' => false,
                'status' => 'failed',
                'error' => 'Missing CM_TID or unconfigured merchant'
            ];
        }

        $url = $this->verifyUrl . '?CM_TID=' . urlencode($cmTid) . '&web_id=' . urlencode($this->webId);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err || $httpCode !== 200 || empty($response)) {
            return [
                'verified' => false,
                'status' => 'failed',
                'error' => 'Verification API connection failed: ' . ($err ?: "HTTP $httpCode")
            ];
        }

        $data = json_decode((string)$response, true);
        if (!is_array($data)) {
            return [
                'verified' => false,
                'status' => 'failed',
                'error' => 'Invalid JSON from verification endpoint'
            ];
        }

        return $this->evaluateVerifyApiResponse($data, $cmTid, $expectedOrderId, $expectedAmount, $currency);
    }

    /**
     * Evaluate documented CashMaal verify_v2 JSON response structure.
     * Enforces strict currency binding and deterministic minor units comparison.
     */
    public function evaluateVerifyApiResponse(array $data, string $cmTid, ?string $expectedOrderId = null, $expectedAmount = null, string $currency = 'PKR'): array {
        // 1. Status check: 1 = Success in CashMaal
        $rawStatus = (string)($data['status'] ?? '');
        if ($rawStatus !== '1' && $rawStatus !== 'success') {
            return [
                'verified' => false,
                'status' => 'failed',
                'error' => 'CashMaal API verification reported non-success status: ' . $rawStatus,
                'raw' => $data
            ];
        }

        // 2. Transaction ID identity check
        $verifiedTxId = trim((string)($data['transaction_id'] ?? ($data['CM_TID'] ?? '')));
        if (!empty($verifiedTxId) && !empty($cmTid) && strcasecmp($verifiedTxId, $cmTid) !== 0) {
            return [
                'verified' => false,
                'status' => 'failed',
                'error' => "Transaction ID mismatch: expected $cmTid, got $verifiedTxId",
                'raw' => $data
            ];
        }

        // 3. Order ID identity check (verifying transaction identity!)
        $verifiedOrderId = trim((string)($data['order_id'] ?? ''));
        if ($expectedOrderId !== null && !empty($verifiedOrderId)) {
            if (strcasecmp($verifiedOrderId, trim($expectedOrderId)) !== 0) {
                return [
                    'verified' => false,
                    'status' => 'failed',
                    'error' => "Order ID mismatch: expected $expectedOrderId, got $verifiedOrderId",
                    'raw' => $data
                ];
            }
        }

        // 4. Strict currency binding & deterministic monetary amount comparison
        $curr = strtoupper(trim($currency));
        $verifiedAmountRaw = null;

        if ($curr === 'PKR') {
            if (!isset($data['PKR_amount']) || $data['PKR_amount'] === '') {
                return [
                    'verified' => false,
                    'status' => 'failed',
                    'error' => "Missing PKR_amount in CashMaal verification response for PKR transaction",
                    'raw' => $data
                ];
            }
            $verifiedAmountRaw = (string)$data['PKR_amount'];
        } elseif ($curr === 'USD') {
            if (!isset($data['USD_amount']) || $data['USD_amount'] === '') {
                return [
                    'verified' => false,
                    'status' => 'failed',
                    'error' => "Missing USD_amount in CashMaal verification response for USD transaction",
                    'raw' => $data
                ];
            }
            $verifiedAmountRaw = (string)$data['USD_amount'];
        } else {
            return [
                'verified' => false,
                'status' => 'failed',
                'error' => "Unsupported transaction currency for CashMaal verification: $curr",
                'raw' => $data
            ];
        }

        if ($expectedAmount !== null) {
            $normExpected = self::normalizeToMinorUnits($expectedAmount, 2);
            $normVerified = self::normalizeToMinorUnits($verifiedAmountRaw, 2);

            if ($normExpected === null || $normVerified === null || $normExpected !== $normVerified) {
                return [
                    'verified' => false,
                    'status' => 'failed',
                    'error' => "Amount mismatch: expected $expectedAmount $curr, got $verifiedAmountRaw $curr",
                    'raw' => $data
                ];
            }
        }

        return [
            'verified' => true,
            'status' => 'success',
            'transaction_id' => $verifiedTxId ?: $cmTid,
            'order_id' => $verifiedOrderId ?: $expectedOrderId,
            'amount' => $verifiedAmountRaw,
            'currency' => $curr,
            'receiver_account' => $data['receiver_account'] ?? null,
            'raw' => $data
        ];
    }

    /**
     * Normalize a monetary amount to integer minor units (e.g. cents / paisas).
     * Strictly avoids binary floating point arithmetic.
     */
    public static function normalizeToMinorUnits($amount, int $scale = 2): ?int {
        return PaymentService::normalizeToMinorUnits($amount, $scale);
    }

    /**
     * Strict deterministic equality check in minor units.
     */
    public static function amountsEqual($expected, $received, int $scale = 2): bool {
        return PaymentService::amountsEqual($expected, $received, $scale);
    }

    public function refundPayment(string $transactionId, float $amount): array {
        return [
            'status' => 'failed',
            'error' => 'CashMaal does not support automated API refunds. Manual processing required.'
        ];
    }

    public function handleWebhook(array $payload, array $headers): array {
        return $this->verifyPayment($payload);
    }

    public function verifyWebhookSignature(array $payload, array $headers): bool {
        $ipnKey = (string)($payload['ipn_key'] ?? '');
        if (empty($ipnKey) || empty($this->ipnKey)) {
            return false;
        }
        return hash_equals($this->ipnKey, $ipnKey);
    }
}
