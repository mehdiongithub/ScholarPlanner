<?php

namespace App\Services;

class PaymentService {
    /**
     * Resolve the active payment gateway instance.
     */
    public static function gateway(?string $providerName = null): PaymentGatewayInterface {
        $provider = $providerName ?? ($_ENV['PAYMENT_PROVIDER'] ?? 'mock');
        $env = $_ENV['APP_ENV'] ?? 'production';
        
        if (strtolower($provider) === 'mock' && $env === 'production') {
            throw new \RuntimeException("Mock payment provider is disabled in production.");
        }
        
        switch (strtolower($provider)) {
            case 'cashmaal':
                return new CashMaalPaymentGateway();
            case 'jazzcash':
                return new JazzCashPaymentGateway();
            case 'easypaisa':
                return new EasypaisaPaymentGateway();
            case 'mock':
            default:
                return new MockPaymentGateway();
        }
    }

    /**
     * Normalize a monetary amount to integer minor units (e.g. cents / paisas).
     * Deterministic and decimal-safe: does NOT convert to PHP floats.
     * Rejects malformed values, comma-containing values, non-numeric strings,
     * negative values, values exceeding safe integer range, and non-zero sub-minor fractions.
     *
     * @param mixed $amount
     * @param int $scale Number of decimal places (default 2 for PKR/USD)
     * @return int|null Integer minor units, or null if invalid
     */
    public static function normalizeToMinorUnits($amount, int $scale = 2): ?int {
        if ($amount === null) {
            return null;
        }
        $str = trim((string)$amount);
        if ($str === '') {
            return null;
        }

        // 1. Reject comma-containing values completely (require machine-readable format)
        if (strpos($str, ',') !== false) {
            return null;
        }

        // 2. Reject internal whitespace or any characters other than digits and optional single decimal point
        if (!preg_match('/^\d+(\.\d+)?$/', $str)) {
            return null;
        }

        $parts = explode('.', $str);
        $intPart = ltrim($parts[0], '0');
        if ($intPart === '') {
            $intPart = '0';
        }

        // 3. Integer range safety: limit integer part to maximum 12 digits (up to 999 billion units)
        // Prevents integer overflow and excessive attacker-controlled values
        if (strlen($intPart) > 12) {
            return null;
        }

        $fracPart = $parts[1] ?? '';
        if (strlen($fracPart) > $scale) {
            $extra = substr($fracPart, $scale);
            if (rtrim($extra, '0') !== '') {
                // Contains non-zero precision beyond supported minor units (e.g. 1000.001)
                return null;
            }
            $fracPart = substr($fracPart, 0, $scale);
        } else {
            $fracPart = str_pad($fracPart, $scale, '0', STR_PAD_RIGHT);
        }

        $minorStr = ($intPart === '0' ? '' : $intPart) . $fracPart;
        if ($minorStr === '') {
            $minorStr = '0';
        }

        // 4. Verify minor units string fits safely within PHP_INT_MAX without float conversion
        $maxIntStr = (string)PHP_INT_MAX;
        if (strlen($minorStr) > strlen($maxIntStr) || (strlen($minorStr) === strlen($maxIntStr) && strcmp($minorStr, $maxIntStr) > 0)) {
            return null;
        }

        return (int)$minorStr;
    }

    /**
     * Strictly compare two monetary amounts for exact equality in integer minor units.
     *
     * @param mixed $expected
     * @param mixed $received
     * @param int $scale
     * @return bool True iff both amounts normalize to the exact same integer minor units
     */
    public static function amountsEqual($expected, $received, int $scale = 2): bool {
        $normExpected = self::normalizeToMinorUnits($expected, $scale);
        $normReceived = self::normalizeToMinorUnits($received, $scale);

        if ($normExpected === null || $normReceived === null) {
            return false;
        }
        return $normExpected === $normReceived;
    }
}
