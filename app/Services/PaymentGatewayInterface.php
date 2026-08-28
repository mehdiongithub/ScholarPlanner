<?php

namespace App\Services;

interface PaymentGatewayInterface {
    /**
     * Create a checkout transaction / URL.
     *
     * @param array $data Contains keys: user_id, amount, currency, email, callback_url, transaction_reference
     * @return array Contains keys: checkout_url, transaction_reference
     */
    public function createCheckout(array $data): array;

    /**
     * Verify payment status with provider.
     *
     * @param array $params Query string / POST params returned to callback redirect
     * @return array Contains keys: status ('success'|'failed'), provider_transaction_id, amount, currency
     */
    public function verifyPayment(array $params): array;

    /**
     * Refund a paid transaction.
     *
     * @param string $transactionId Provider transaction ID
     * @param float $amount Amount to refund
     * @return array Contains keys: status ('success'|'failed'), refund_transaction_id
     */
    public function refundPayment(string $transactionId, float $amount): array;

    /**
     * Handle webhook delivery event parser.
     *
     * @param array $payload Request body payload
     * @param array $headers Request headers
     * @return array Contains keys: status ('success'|'failed'), event_type, transaction_reference, provider_transaction_id, amount, currency
     */
    public function handleWebhook(array $payload, array $headers): array;

    /**
     * Verify the signature of a webhook request.
     */
    public function verifyWebhookSignature(array $payload, array $headers): bool;
}
