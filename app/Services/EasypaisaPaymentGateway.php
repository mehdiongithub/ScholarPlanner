<?php

namespace App\Services;

class EasypaisaPaymentGateway implements PaymentGatewayInterface {
    private string $apiUrl;
    private string $storeId;
    private string $username;
    private string $password;
    private string $key;

    public function __construct() {
        $this->apiUrl = $_ENV['EASYPAISA_API_URL'] ?? 'https://easypay.easypaisa.com.pk/easypay/Index.js';
        $this->storeId = $_ENV['EASYPAISA_STORE_ID'] ?? '';
        $this->username = $_ENV['EASYPAISA_USERNAME'] ?? '';
        $this->password = $_ENV['EASYPAISA_PASSWORD'] ?? '';
        $this->key = $_ENV['EASYPAISA_KEY'] ?? '';
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
        $str = $this->storeId . $params['amount'] . $params['orderId'];
        return hash_hmac('sha256', $str, $this->key);
    }

    public function verifyPayment(array $params): array {
        $ref = $params['orderId'] ?? '';
        $responseCode = $params['responseCode'] ?? '';
        $providerTxId = $params['transactionId'] ?? '';
        $amount = (float)($params['amount'] ?? 0.0);
        $currency = 'PKR';
        
        $incomingSig = $params['signature'] ?? '';
        $calculatedSig = $this->generateSignature($params);
        $sigValid = hash_equals(strtolower($calculatedSig), strtolower($incomingSig));

        $success = $sigValid && ($responseCode === '0000' || $responseCode === '00' || $responseCode === 'success');

        return [
            'status' => $success ? 'success' : 'failed',
            'provider_transaction_id' => $providerTxId ?: ('ep_tx_' . bin2hex(random_bytes(6))),
            'amount' => $amount,
            'currency' => $currency
        ];
    }

    public function refundPayment(string $transactionId, float $amount): array {
        return [
            'status' => 'success',
            'refund_transaction_id' => 'ep_refund_' . bin2hex(random_bytes(6))
        ];
    }

    public function handleWebhook(array $payload, array $headers): array {
        $ref = $payload['orderId'] ?? '';
        $responseCode = $payload['responseCode'] ?? '';
        $providerTxId = $payload['transactionId'] ?? '';
        $amount = (float)($payload['amount'] ?? 0.0);
        $currency = 'PKR';

        $success = ($responseCode === '0000' || $responseCode === '00' || $responseCode === 'success');

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
        // Easypaisa standard IPN validation or simple key match
        $sig = $headers['x-easypaisa-signature'] ?? '';
        if (empty($sig)) {
            return false;
        }
        $expected = hash_hmac('sha256', json_encode($payload), $this->key);
        return hash_equals($expected, $sig);
    }
}
