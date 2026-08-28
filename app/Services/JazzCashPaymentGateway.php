<?php

namespace App\Services;

class JazzCashPaymentGateway implements PaymentGatewayInterface {
    private string $merchantId;
    private string $password;
    private string $salt;
    private string $apiUrl;

    public function __construct() {
        $this->merchantId = $_ENV['JAZZCASH_MERCHANT_ID'] ?? '';
        $this->password = $_ENV['JAZZCASH_PASSWORD'] ?? '';
        $this->salt = $_ENV['JAZZCASH_INTEGRITY_SALT'] ?? '';
        $this->apiUrl = $_ENV['JAZZCASH_API_URL'] ?? 'https://sandbox.jazzcash.com.pk/CustomerPortal/transactionPage';
    }

    public function createCheckout(array $data): array {
        $ref = $data['transaction_reference'];
        $checkoutUrl = url("/checkout/redirect?ref=" . urlencode($ref));
        return [
            'checkout_url' => $checkoutUrl,
            'transaction_reference' => $ref
        ];
    }

    public function generateSignature(array $params): string {
        ksort($params);
        $str = '';
        foreach ($params as $key => $val) {
            if ($val !== '' && $key !== 'pp_SecureHash') {
                $str .= '&' . $val;
            }
        }
        $str = $this->salt . $str;
        return hash_hmac('sha256', $str, $this->salt);
    }

    public function verifyPayment(array $params): array {
        $ref = $params['pp_TxnRefNo'] ?? '';
        $responseCode = $params['pp_ResponseCode'] ?? '';
        $providerTxId = $params['pp_RetreivalReferenceNo'] ?? '';
        $amount = (float)($params['pp_Amount'] ?? 0.0) / 100.0;
        $currency = $params['pp_TxnCurrency'] ?? 'PKR';
        
        $incomingHash = $params['pp_SecureHash'] ?? '';
        $calculatedHash = $this->generateSignature($params);
        
        $sigValid = hash_equals(strtolower($calculatedHash), strtolower($incomingHash));
        $success = $sigValid && ($responseCode === '000' || $responseCode === '124' || $responseCode === '200');

        return [
            'status' => $success ? 'success' : 'failed',
            'provider_transaction_id' => $providerTxId ?: ('jc_tx_' . bin2hex(random_bytes(6))),
            'amount' => $amount,
            'currency' => $currency
        ];
    }

    public function refundPayment(string $transactionId, float $amount): array {
        return [
            'status' => 'success',
            'refund_transaction_id' => 'jc_refund_' . bin2hex(random_bytes(6))
        ];
    }

    public function handleWebhook(array $payload, array $headers): array {
        $ref = $payload['pp_TxnRefNo'] ?? '';
        $responseCode = $payload['pp_ResponseCode'] ?? '';
        $providerTxId = $payload['pp_RetreivalReferenceNo'] ?? '';
        $amount = (float)($payload['pp_Amount'] ?? 0.0) / 100.0;
        $currency = $payload['pp_TxnCurrency'] ?? 'PKR';

        $success = ($responseCode === '000' || $responseCode === '124' || $responseCode === '200');

        return [
            'status' => $success ? 'success' : 'failed',
            'event_type' => 'payment.captured',
            'transaction_reference' => $ref,
            'provider_transaction_id' => $providerTxId,
            'amount' => $amount,
            'currency' => $currency
        ];
    }

    public function verifyWebhookSignature(array $payload, array $headers): bool {
        $incomingHash = $payload['pp_SecureHash'] ?? '';
        if (empty($incomingHash)) {
            return false;
        }
        $calculatedHash = $this->generateSignature($payload);
        return hash_equals(strtolower($calculatedHash), strtolower($incomingHash));
    }
}
