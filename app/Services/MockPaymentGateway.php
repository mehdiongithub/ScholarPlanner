<?php

namespace App\Services;

class MockPaymentGateway implements PaymentGatewayInterface {
    public function createCheckout(array $data): array {
        $ref = $data['transaction_reference'];
        $checkoutUrl = url("/checkout/mock-screen?ref=" . urlencode($ref));
        return [
            'checkout_url' => $checkoutUrl,
            'transaction_reference' => $ref
        ];
    }

    public function verifyPayment(array $params): array {
        $ref = $params['ref'] ?? '';
        $status = $params['status'] ?? 'failed';

        // Fetch transaction details from DB to return amount/currency
        $db = Database::connection();
        $stmt = $db->prepare("SELECT amount, currency FROM payment_transactions WHERE transaction_reference = :ref LIMIT 1");
        $stmt->execute(['ref' => $ref]);
        $tx = $stmt->fetch();
        
        $amount = $tx ? (float)$tx['amount'] : 0.0;
        $currency = $tx ? $tx['currency'] : 'PKR';

        return [
            'status' => $status === 'success' ? 'success' : 'failed',
            'provider_transaction_id' => 'mock_tx_' . bin2hex(random_bytes(6)),
            'amount' => $amount,
            'currency' => $currency
        ];
    }

    public function refundPayment(string $transactionId, float $amount): array {
        return [
            'status' => 'success',
            'refund_transaction_id' => 'mock_refund_' . bin2hex(random_bytes(6))
        ];
    }

    public function handleWebhook(array $payload, array $headers): array {
        $ref = $payload['transaction_reference'] ?? '';
        $status = $payload['status'] ?? 'failed';
        $amount = (float)($payload['amount'] ?? 0.0);
        $currency = $payload['currency'] ?? 'PKR';
        $providerTxId = $payload['provider_transaction_id'] ?? ('mock_tx_' . bin2hex(random_bytes(6)));

        return [
            'status' => $status === 'success' ? 'success' : 'failed',
            'event_type' => 'payment.captured',
            'transaction_reference' => $ref,
            'provider_transaction_id' => $providerTxId,
            'amount' => $amount,
            'currency' => $currency
        ];
    }

    public function verifyWebhookSignature(array $payload, array $headers): bool {
        // Simple mock signature check
        $secret = $_ENV['PAYMENT_WEBHOOK_SECRET'] ?? 'mock_secret';
        $sig = $headers['x-mock-signature'] ?? $headers['X-Mock-Signature'] ?? '';
        
        if (empty($sig)) {
            return false;
        }

        $expectedSig = hash_hmac('sha256', json_encode($payload), $secret);
        return hash_equals($expectedSig, $sig);
    }
}
