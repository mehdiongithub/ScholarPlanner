<?php

namespace App\Services;

class PaymentService {
    /**
     * Resolve the active payment gateway instance.
     */
    public static function gateway(): PaymentGatewayInterface {
        $provider = $_ENV['PAYMENT_PROVIDER'] ?? 'mock';
        $env = $_ENV['APP_ENV'] ?? 'production';
        
        if (strtolower($provider) === 'mock' && $env === 'production') {
            throw new \RuntimeException("Mock payment provider is disabled in production.");
        }
        
        switch (strtolower($provider)) {
            case 'jazzcash':
                return new JazzCashPaymentGateway();
            case 'easypaisa':
                return new EasypaisaPaymentGateway();
            case 'mock':
            default:
                return new MockPaymentGateway();
        }
    }
}
