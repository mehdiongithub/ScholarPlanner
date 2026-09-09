<?php

namespace App\Services;

use PDO;
use Exception;
use RuntimeException;
use InvalidArgumentException;

class ReferralService {

    /**
     * Maximum supported monetary amount in minor units for referral calculations.
     * Set to 9,999,999,999 minor units (99,999,999.99 major currency units), corresponding
     * to the DECIMAL(10,2) maximum capacity in the database schema.
     */
    public const MAX_SUPPORTED_MINOR_UNITS = 9999999999;

    /**
     * Maximum supported reservation TTL in minutes (525,600 minutes = 365 days / 1 year).
     * Prevents integer/timestamp overflow and guarantees safe datetime calculation.
     */
    public const MAX_RESERVATION_TTL_MINUTES = 525600;

    /**
     * Validate referral code format:
     * Maximum 8 characters, letters and numbers only, no spaces or special characters.
     */
    public static function validateCode(?string $code): bool {
        if ($code === null) {
            return false;
        }
        $trimmed = trim($code);
        return (bool)preg_match('/^[A-Za-z0-9]{1,8}$/', $trimmed);
    }

    /**
     * Normalize referral code: uppercase and trimmed.
     */
    public static function normalizeCode(?string $code): string {
        return strtoupper(trim((string)$code));
    }

    /**
     * Parse and strictly validate a percentage string/value into integer basis points (0 to 10000).
     *
     * Invariants & Rules:
     * 1. Machine-readable unsigned decimal representation only.
     * 2. Rejects malformed syntax, explicit signs (+, -), commas (,), symbols (%), and whitespace.
     * 3. Rejects negative values (-1, -10.00).
     * 4. Rejects percentages greater than 100.00% (100.01, 101, 999).
     * 5. Rejects non-zero precision beyond 2 decimal places (e.g. 30.001, 30.009, 99.999, 100.001).
     *    Never silently rounds or truncates discarded non-zero digits.
     * 6. Accepts extra decimal places ONLY when all discarded digits are zero (e.g. 30.000 -> 3000 bps, 30.100 -> 3010 bps, 100.000 -> 10000 bps).
     * 7. Converts deterministically to integer basis points using pure integer arithmetic (0 to 10000 bps).
     * 8. Never uses PHP floating-point arithmetic, float, floatval(), ceil(), round(), or IEEE-754 conversions.
     * 9. Preserves exact values.
     *
     * @param mixed $percentage
     * @return int|null Integer basis points (0 to 10000), or null if invalid
     */
    public static function parsePercentageToBasisPoints($percentage): ?int {
        if ($percentage === null) {
            return null;
        }
        if (!is_scalar($percentage)) {
            return null;
        }
        $str = (string)$percentage;
        if ($str === '') {
            return null;
        }

        // 1. Strictly reject ANY whitespace (leading, trailing, internal, spaces, tabs, newlines)
        if (preg_match('/\s/', $str)) {
            return null;
        }

        // 2. Reject explicit signs (+, -), commas (,), or percent symbols (%)
        if (strpos($str, '-') !== false || strpos($str, '+') !== false || strpos($str, ',') !== false || strpos($str, '%') !== false) {
            return null;
        }

        // 3. Reject non-numeric characters (letters, special characters, multiple dots)
        if (!preg_match('/^\d+(\.\d+)?$/', $str)) {
            return null;
        }

        $parts = explode('.', $str, 2);
        $intPart = $parts[0];
        $fracPart = $parts[1] ?? '';

        // 3. Precision inspection beyond 2 decimal places:
        // Reject if discarded precision contains ANY non-zero digit.
        // Allow extra decimal places ONLY if all discarded digits are zero.
        if (strlen($fracPart) > 2) {
            $discarded = substr($fracPart, 2);
            if (ltrim($discarded, '0') !== '') {
                return null; // Non-zero discarded precision rejected!
            }
        }

        // 4. Reject integer part exceeding 100% upfront
        if (strlen($intPart) > 3 || (int)$intPart > 100) {
            return null;
        }

        // 5. Deterministic integer-only basis points calculation (zero floats)
        $intVal = (int)$intPart;
        $d1 = isset($fracPart[0]) ? (int)$fracPart[0] : 0;
        $d2 = isset($fracPart[1]) ? (int)$fracPart[1] : 0;
        $fracBps = ($d1 * 10) + $d2;

        $bps = ($intVal * 100) + $fracBps;

        // 6. Strict range bounds: 0.00% to 100.00% (0 to 10000 basis points)
        if ($bps < 0 || $bps > 10000) {
            return null;
        }

        return $bps;
    }

    /**
     * Validate that a percentage is strictly between 0.00% and 100.00%.
     */
    public static function isValidPercentage($percentage): bool {
        return self::parsePercentageToBasisPoints($percentage) !== null;
    }

    /**
     * Calculate an exact percentage of a monetary minor-unit amount safely without integer overflow.
     *
     * Invariants & Guarantees:
     * - Pure integer arithmetic (zero floats).
     * - Explicit range check on base minor units against MAX_SUPPORTED_MINOR_UNITS.
     * - Explicit range check on percentage basis points (0 to 10000).
     * - Mathematical decomposition: base = (q * 10000) + r
     *   result = (q * pctBps) + intdiv(r * pctBps, 10000)
     * - Since r < 10000 and pctBps <= 10000, r * pctBps < 100,000,000 (never overflows integer type).
     * - Since q = intdiv(base, 10000), q * pctBps <= base <= PHP_INT_MAX (never overflows integer type).
     * - Fails safely (returns null) on negative values, range violations, or overflow.
     *
     * @param int $baseMinor Base monetary amount in minor units (e.g. cents / paisas)
     * @param int $pctBps Percentage in basis points (e.g. 1000 = 10.00%, 10000 = 100.00%)
     * @return int|null Calculated amount in minor units, or null on overflow / invalid bounds
     */
    public static function calculatePercentageMinorSafe(int $baseMinor, int $pctBps): ?int {
        // 1. Explicit bounds checks
        if ($baseMinor < 0 || $baseMinor > self::MAX_SUPPORTED_MINOR_UNITS) {
            return null;
        }

        if ($pctBps < 0 || $pctBps > 10000) {
            return null;
        }

        // Trivial fast paths
        if ($baseMinor === 0 || $pctBps === 0) {
            return 0;
        }
        if ($pctBps === 10000) {
            return $baseMinor;
        }

        // 2. Decompose baseMinor to guarantee no integer overflow before division
        $q = intdiv($baseMinor, 10000);
        $r = $baseMinor % 10000;

        $majorPart = $q * $pctBps;
        $minorPart = intdiv($r * $pctBps, 10000);

        $result = $majorPart + $minorPart;

        // Post-condition validation: result cannot be negative or exceed base amount
        if ($result < 0 || $result > $baseMinor) {
            return null;
        }

        return $result;
    }

    /**
     * Find active referral partner user by referral code (case-insensitive).
     */
    public static function findPartnerByCode(string $code, ?PDO $db = null): ?array {
        if (!self::validateCode($code)) {
            return null;
        }
        $db = $db ?? Database::connection();
        $normalized = self::normalizeCode($code);

        $stmt = $db->prepare("
            SELECT u.id, u.first_name, u.last_name, u.email, u.referral_code, u.discount_percent, u.commission_percent, u.status, u.created_at
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE UPPER(u.referral_code) = :code 
              AND r.name = 'referral_partner' 
              AND u.status = 'active'
            LIMIT 1
        ");
        $stmt->execute(['code' => $normalized]);
        $partner = $stmt->fetch(PDO::FETCH_ASSOC);

        return $partner ?: null;
    }

    /**
     * Generate a unique, random 8-character uppercase alphanumeric referral code.
     */
    public static function generateUniqueCode(?PDO $db = null): string {
        $db = $db ?? Database::connection();
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // omit ambiguous chars like O, 0, I, 1
        $maxAttempts = 50;

        for ($i = 0; $i < $maxAttempts; $i++) {
            $code = '';
            for ($c = 0; $c < 8; $c++) {
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }
            $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE UPPER(referral_code) = :code");
            $stmt->execute(['code' => $code]);
            if ((int)$stmt->fetchColumn() === 0) {
                return $code;
            }
        }

        // Fallback to random hex slice
        return strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    }

    /**
     * Attribute a newly registering user to a referral partner.
     * Enforces:
     * - Code format validation (1-8 alphanumeric chars)
     * - Self-referral prevention: partner cannot refer themselves
     * - Email identity matching prevention
     * - Immutability: if user already has a referral partner, returns false without modifying.
     */
    public static function attributeUser(int $userId, string $referralCode, ?PDO $db = null): bool {
        if (!self::validateCode($referralCode)) {
            return false;
        }
        $db = $db ?? Database::connection();
        $partner = self::findPartnerByCode($referralCode, $db);
        if (!$partner) {
            return false;
        }

        $partnerId = (int)$partner['id'];

        // Self-referral prevention: partner cannot refer themselves!
        if ($partnerId === $userId) {
            return false;
        }

        // Verify user exists and check existing attribution + email matching
        $stmtCheck = $db->prepare("SELECT email, referral_partner_id, referred_by_code FROM users WHERE id = :id LIMIT 1");
        $stmtCheck->execute(['id' => $userId]);
        $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if (!$existing) {
            return false;
        }

        if (!empty($existing['referral_partner_id']) || !empty($existing['referred_by_code'])) {
            return false; // Immutable!
        }

        // Check if user email matches partner email (cannot refer own email address)
        if (!empty($existing['email']) && strtolower(trim($existing['email'])) === strtolower(trim($partner['email']))) {
            return false;
        }

        $normalizedCode = self::normalizeCode($partner['referral_code']);

        // Update user record atomically
        $stmtUpd = $db->prepare("
            UPDATE users 
            SET referral_partner_id = :partner_id, referred_by_code = :code 
            WHERE id = :id AND referral_partner_id IS NULL
        ");
        $stmtUpd->execute([
            'partner_id' => $partnerId,
            'code' => $normalizedCode,
            'id' => $userId
        ]);

        // Insert into referral_signups table
        $stmtSignup = $db->prepare("
            INSERT INTO referral_signups (partner_id, referred_user_id, referral_code, created_at)
            VALUES (:partner_id, :referred_user_id, :code, NOW())
            ON DUPLICATE KEY UPDATE referral_code = VALUES(referral_code)
        ");
        $stmtSignup->execute([
            'partner_id' => $partnerId,
            'referred_user_id' => $userId,
            'code' => $normalizedCode
        ]);
        return true;
    }

    /**
     * Calculate referral discount for checkout.
     * Rule: discount applies ONLY to the user's FIRST successful subscription payment.
     * Calculated strictly server-side using minor-unit integer arithmetic without overflow.
     *
     * Concurrency & Entitlement Invariants:
     * - When $reserve === true:
     *   Acquires row-level locks on referral_discount_claims to prevent concurrent checkouts
     *   from simultaneously claiming the first-payment discount.
     *   Inserts or updates a claim with status = 'reserved' within the checkout transaction.
     * - When an active claim (status = 'reserved') exists for an in-flight pending transaction:
     *   Concurrent checkouts receive NO discount (full plan price).
     * - When an active claim exists for a failed/cancelled transaction:
     *   The reservation is released and the user remains eligible for their first-payment discount.
     * - When a claim is consumed (status = 'consumed') or user has a prior paid transaction:
     *   Future checkouts receive NO discount.
     *
     * @param int $userId
     * @param mixed $planPrice
     * @param string|null $currency
     * @param PDO|null $db
     * @param bool $reserve Whether to atomically reserve the claim in referral_discount_claims
     * @return array
     */
    public static function calculateDiscount(int $userId, $planPrice, ?string $currency = 'PKR', ?PDO $db = null, bool $reserve = false): array {
        $db = $db ?? Database::connection();

        $planMinor = PaymentService::normalizeToMinorUnits($planPrice, 2);
        if ($planMinor === null || $planMinor <= 0) {
            throw new InvalidArgumentException("Invalid plan price for discount calculation.");
        }
        if ($planMinor > self::MAX_SUPPORTED_MINOR_UNITS) {
            throw new InvalidArgumentException("Plan price exceeds maximum supported monetary range.");
        }
        $origStr = sprintf('%d.%02d', intdiv($planMinor, 100), $planMinor % 100);

        $defaultResult = [
            'has_discount' => false,
            'discount_percent' => '0.00',
            'discount_amount' => '0.00',
            'final_amount' => $origStr,
            'original_amount' => $origStr,
            'partner_id' => null,
            'referral_code' => null
        ];

        // 1. Fetch user referral attribution
        $stmtUser = $db->prepare("
            SELECT u.referral_partner_id, u.referred_by_code, u.created_at,
                   p.id AS partner_id, p.referral_code AS partner_code, p.discount_percent AS partner_discount, p.status AS partner_status
            FROM users u
            LEFT JOIN users p ON (u.referral_partner_id = p.id OR (u.referral_partner_id IS NULL AND UPPER(u.referred_by_code) = UPPER(p.referral_code)))
            WHERE u.id = :id LIMIT 1" . ($reserve ? " FOR UPDATE" : "")
        );
        $stmtUser->execute(['id' => $userId]);
        $userRef = $stmtUser->fetch(PDO::FETCH_ASSOC);

        if (!$userRef || empty($userRef['partner_id']) || $userRef['partner_status'] !== 'active') {
            return $defaultResult;
        }

        $partnerId = (int)$userRef['partner_id'];
        $referralCode = self::normalizeCode($userRef['partner_code'] ?: $userRef['referred_by_code']);

        // Self-referral prevention: partner cannot receive discount on own account
        if ($partnerId === $userId) {
            return $defaultResult;
        }

        // 2. Authoritative First-Payment Check:
        // Query database for ANY prior successful subscription payment by this user
        $stmtPrior = $db->prepare("
            SELECT COUNT(*) 
            FROM payment_transactions 
            WHERE user_id = :uid AND status IN ('paid', 'success')" . ($reserve ? " FOR UPDATE" : "")
        );
        $stmtPrior->execute(['uid' => $userId]);
        $priorPaidCount = (int)$stmtPrior->fetchColumn();

        if ($priorPaidCount > 0) {
            // Not first payment: NO referral discount!
            $defaultResult['partner_id'] = $partnerId;
            $defaultResult['referral_code'] = $referralCode;
            return $defaultResult;
        }

        // 3. Check Atomic First-Payment Discount Claims:
        // Inspects referral_discount_claims table to prevent concurrent checkouts from double-claiming.
        $stmtClaim = $db->prepare("
            SELECT c.*, t.status AS tx_status, (c.expires_at IS NOT NULL AND c.expires_at <= NOW()) AS is_db_expired 
            FROM referral_discount_claims c 
            LEFT JOIN payment_transactions t ON c.payment_transaction_id = t.id 
            WHERE c.referred_user_id = :uid" . ($reserve ? " FOR UPDATE" : "")
        );
        $stmtClaim->execute(['uid' => $userId]);
        $claim = $stmtClaim->fetch(PDO::FETCH_ASSOC);

        if ($claim) {
            if ($claim['status'] === 'consumed') {
                // Entitlement already consumed permanently: NO discount!
                $defaultResult['partner_id'] = $partnerId;
                $defaultResult['referral_code'] = $referralCode;
                return $defaultResult;
            }

            if ($claim['status'] === 'reserved') {
                $txStatus = $claim['tx_status'] ?? null;
                $isExpired = false;
                if ($claim['expires_at'] !== null) {
                    $isExpired = (bool)$claim['is_db_expired'];
                } else {
                    $ttl = self::getReservationTtlMinutes($db);
                    $claimTime = !empty($claim['updated_at']) ? strtotime($claim['updated_at']) : strtotime($claim['created_at']);
                    $isExpired = (time() - $claimTime >= ($ttl * 60));
                }

                $isFailedOrCancelled = in_array($txStatus, ['failed', 'cancelled', 'canceled', 'rejected'], true);

                if (!$isExpired && !$isFailedOrCancelled) {
                    // Active pending unexpired reservation exists: concurrent checkout must NOT receive discount!
                    $defaultResult['partner_id'] = $partnerId;
                    $defaultResult['referral_code'] = $referralCode;
                    return $defaultResult;
                }
                // If it IS expired or linked transaction failed/cancelled: allow user to claim discount on this fresh checkout!
            }
        }

        // 4. Determine discount percentage: partner override if set > 0, else global default
        $discountPct = null;
        if (!empty($userRef['partner_discount'])) {
            $discountPct = (string)$userRef['partner_discount'];
        } else {
            $discountPct = self::getSetting('referral_default_discount_percent', '10.00', $db);
        }

        $discPctBps = self::parsePercentageToBasisPoints($discountPct);
        if ($discPctBps === null || $discPctBps <= 0) {
            $defaultResult['partner_id'] = $partnerId;
            $defaultResult['referral_code'] = $referralCode;
            return $defaultResult;
        }

        // Safe integer percentage calculation without overflow
        $discountAmountMinor = self::calculatePercentageMinorSafe($planMinor, $discPctBps);
        if ($discountAmountMinor === null) {
            $discountAmountMinor = 0; // Fails safely on overflow
        }
        $finalAmountMinor = max(0, $planMinor - $discountAmountMinor);

        $discPctStr = sprintf('%d.%02d', intdiv($discPctBps, 100), $discPctBps % 100);
        $discAmountStr = sprintf('%d.%02d', intdiv($discountAmountMinor, 100), $discountAmountMinor % 100);
        $finalAmountStr = sprintf('%d.%02d', intdiv($finalAmountMinor, 100), $finalAmountMinor % 100);

        // 5. If $reserve is true, record the reservation atomically in referral_discount_claims
        if ($reserve && $discountAmountMinor > 0) {
            $ttl = self::getReservationTtlMinutes($db);
            $expiresAt = date('Y-m-d H:i:s', time() + ($ttl * 60));

            $stmtReserve = $db->prepare("
                INSERT INTO referral_discount_claims (referred_user_id, partner_id, status, expires_at, created_at, updated_at)
                VALUES (:uid, :pid, 'reserved', :expires_at, NOW(), NOW())
                ON DUPLICATE KEY UPDATE 
                    partner_id = VALUES(partner_id),
                    payment_transaction_id = NULL,
                    status = IF(status = 'consumed', 'consumed', 'reserved'),
                    expires_at = IF(status = 'consumed', NULL, VALUES(expires_at)),
                    updated_at = NOW()
            ");
            $stmtReserve->execute([
                'uid' => $userId,
                'pid' => $partnerId,
                'expires_at' => $expiresAt
            ]);

            // Re-check in case another transaction concurrently marked it consumed
            $stmtVerify = $db->prepare("SELECT status FROM referral_discount_claims WHERE referred_user_id = :uid");
            $stmtVerify->execute(['uid' => $userId]);
            if ($stmtVerify->fetchColumn() === 'consumed') {
                $defaultResult['partner_id'] = $partnerId;
                $defaultResult['referral_code'] = $referralCode;
                return $defaultResult;
            }
        }

        return [
            'has_discount' => ($discountAmountMinor > 0),
            'discount_percent' => $discPctStr,
            'discount_amount' => $discAmountStr,
            'final_amount' => $finalAmountStr,
            'original_amount' => $origStr,
            'partner_id' => $partnerId,
            'referral_code' => $referralCode
        ];
    }

    /**
     * Parse and strictly validate a TTL value in integer minutes.
     * Rejects:
     * - zero, negative values
     * - decimals/floats (e.g. 1.5)
     * - non-numeric strings (abc)
     * - empty strings, whitespace-padded strings (' 120 ')
     * - non-scalar types
     * Caps values exceeding MAX_RESERVATION_TTL_MINUTES (525,600 minutes / 1 year) safely.
     *
     * @param mixed $val
     * @return int|null Validated positive integer minutes, or null if invalid
     */
    public static function parseTtlMinutes($val): ?int {
        if ($val === null || $val === '') {
            return null;
        }
        if (is_int($val)) {
            if ($val <= 0) {
                return null;
            }
            return min($val, self::MAX_RESERVATION_TTL_MINUTES);
        }
        if (is_string($val)) {
            if (!preg_match('/^[1-9]\d*$/', $val)) {
                return null;
            }
            $intVal = (int)$val;
            if ($intVal <= 0) {
                return null;
            }
            return min($intVal, self::MAX_RESERVATION_TTL_MINUTES);
        }
        return null;
    }

    /**
     * Get the configured reservation TTL in minutes for referral discount checkouts.
     * Checks database settings first, then config/referral.php, then env, defaulting to 120 minutes.
     *
     * @param PDO|null $db
     * @return int TTL in minutes (strictly positive integer, minimum 1)
     */
    public static function getReservationTtlMinutes(?PDO $db = null): int {
        $db = $db ?? Database::connection();

        // 1. Database setting (highest precedence)
        $dbSetting = self::getSetting('referral_discount_reservation_ttl_minutes', '', $db);
        $ttlDb = self::parseTtlMinutes($dbSetting);
        if ($ttlDb !== null) {
            return $ttlDb;
        }

        // 2. Config file (config/referral.php)
        if (function_exists('config')) {
            $cfg = config('referral.reservation_ttl_minutes');
            $ttlCfg = self::parseTtlMinutes($cfg);
            if ($ttlCfg !== null) {
                return $ttlCfg;
            }
        }

        // 3. Environment variable ($_ENV)
        if (isset($_ENV['REFERRAL_DISCOUNT_RESERVATION_TTL_MINUTES'])) {
            $ttlEnv = self::parseTtlMinutes($_ENV['REFERRAL_DISCOUNT_RESERVATION_TTL_MINUTES']);
            if ($ttlEnv !== null) {
                return $ttlEnv;
            }
        }

        // 4. Hardcoded default fallback (120 minutes / 2 hours)
        return 120;
    }

    /**
     * Link an active discount reservation to a created payment transaction.
     */
    public static function linkDiscountClaimToTransaction(int $userId, int $txId, ?PDO $db = null): bool {
        $db = $db ?? Database::connection();
        $stmt = $db->prepare("
            UPDATE referral_discount_claims 
            SET payment_transaction_id = :tx_id, updated_at = NOW() 
            WHERE referred_user_id = :uid AND status = 'reserved'
        ");
        return $stmt->execute(['tx_id' => $txId, 'uid' => $userId]);
    }

    /**
     * Permanently consume the referral discount claim upon successful payment verification.
     * Binds strictly to payment_transaction_id and status = 'reserved' to enforce strict
     * state lifecycle (reserved -> consumed). Expired claims cannot be consumed.
     * Does NOT touch other checkouts or rewrite payment transactions.
     */
    public static function consumeDiscountClaim(int $txId, ?PDO $db = null): bool {
        $db = $db ?? Database::connection();
        $stmt = $db->prepare("
            UPDATE referral_discount_claims 
            SET status = 'consumed', expires_at = NULL, updated_at = NOW() 
            WHERE payment_transaction_id = :tx_id AND status = 'reserved'
        ");
        return $stmt->execute(['tx_id' => $txId]);
    }

    /**
     * Safely release an unconsumed reservation if a payment transaction fails or is cancelled.
     * Transitions status to 'expired' strictly matching the given payment_transaction_id.
     */
    public static function releaseDiscountClaim(int $txId, ?PDO $db = null): bool {
        $db = $db ?? Database::connection();
        $stmt = $db->prepare("
            UPDATE referral_discount_claims 
            SET status = 'expired', updated_at = NOW() 
            WHERE payment_transaction_id = :tx_id AND status = 'reserved'
        ");
        return $stmt->execute(['tx_id' => $txId]);
    }

    /**
     * Release any unconsumed reservation for a given user.
     */
    public static function releaseDiscountClaimForUser(int $userId, ?PDO $db = null): bool {
        $db = $db ?? Database::connection();
        $stmt = $db->prepare("
            UPDATE referral_discount_claims 
            SET status = 'expired', updated_at = NOW() 
            WHERE referred_user_id = :uid AND status = 'reserved'
        ");
        return $stmt->execute(['uid' => $userId]);
    }

    /**
     * Expire abandoned referral discount reservations that have exceeded their TTL.
     * Idempotent: safe to run repeatedly via cron/referral_cleanup.php.
     * Uses index idx_claim_status_expires (status, expires_at).
     *
     * @param PDO|null $db
     * @param int|null $ttlMinutes Optional override for TTL in minutes
     * @return int Number of reservations transitioned to 'expired'
     */
    public static function expireAbandonedReservations(?PDO $db = null, ?int $ttlMinutes = null): int {
        $db = $db ?? Database::connection();
        $ttl = ($ttlMinutes !== null && $ttlMinutes > 0) ? $ttlMinutes : self::getReservationTtlMinutes($db);

        $stmt = $db->prepare("
            UPDATE referral_discount_claims
            SET status = 'expired', updated_at = NOW()
            WHERE status = 'reserved'
              AND (
                  (expires_at IS NOT NULL AND expires_at <= NOW())
                  OR
                  (expires_at IS NULL AND updated_at <= DATE_SUB(NOW(), INTERVAL :ttl MINUTE))
              )
        ");
        $stmt->execute(['ttl' => $ttl]);
        return (int)$stmt->rowCount();
    }

    /**
     * Calculate attribution expiry timestamp using strict calendar-month semantics.
     * Evaluates via MySQL DATE_ADD(created_at, INTERVAL :window MONTH) to ensure
     * 100% engine parity, month-end day clamping (e.g. Aug 31 -> Feb 28/29), leap-year handling,
     * with an exact PHP DateTimeImmutable fallback.
     *
     * @param string $registeredAt MySQL datetime string (e.g. '2026-01-31 12:00:00')
     * @param int $windowMonths Number of calendar months (default 6)
     * @param PDO|null $db
     * @return string Formatted datetime 'Y-m-d H:i:s'
     */
    public static function calculateAttributionExpiry(string $registeredAt, int $windowMonths = 6, ?PDO $db = null): string {
        if ($windowMonths <= 0) {
            $windowMonths = 6;
        }
        try {
            $db = $db ?? Database::connection();
            $stmt = $db->prepare("SELECT DATE_FORMAT(DATE_ADD(:reg, INTERVAL :window MONTH), '%Y-%m-%d %H:%i:%s')");
            $stmt->execute(['reg' => $registeredAt, 'window' => $windowMonths]);
            $res = $stmt->fetchColumn();
            if ($res) {
                return (string)$res;
            }
        } catch (\Throwable $e) {
            // Safe fallback to PHP DateTimeImmutable with explicit calendar-month clamping
        }

        $dt = new \DateTimeImmutable($registeredAt);
        $targetMonth = (int)$dt->format('n') + $windowMonths;
        $targetYear = (int)$dt->format('Y') + intdiv($targetMonth - 1, 12);
        $targetMonth = (($targetMonth - 1) % 12) + 1;
        $day = (int)$dt->format('j');
        $daysInTargetMonth = (int)(new \DateTimeImmutable(sprintf('%04d-%02d-01', $targetYear, $targetMonth)))->format('t');
        $clampedDay = min($day, $daysInTargetMonth);
        return sprintf('%04d-%02d-%02d %s', $targetYear, $targetMonth, $clampedDay, $dt->format('H:i:s'));
    }

    /**
     * Record qualifying commission for a successfully verified payment transaction.
     * Fully idempotent (unique constraint on payment_transaction_id).
     * Enforces the 6-month attribution window from user registration.
     * Freezes historical commission basis, percentage, and amounts.
     * Enforces self-referral prevention (partner_id != referred_user_id).
     */
    public static function calculateAndRecordCommission(int $paymentTxId, ?PDO $db = null): ?array {
        $db = $db ?? Database::connection();

        // 1. Check idempotency: if commission already recorded for this transaction, return it
        $stmtExisting = $db->prepare("SELECT * FROM referral_commissions WHERE payment_transaction_id = :id LIMIT 1");
        $stmtExisting->execute(['id' => $paymentTxId]);
        $existingComm = $stmtExisting->fetch(PDO::FETCH_ASSOC);
        if ($existingComm) {
            return $existingComm;
        }

        // 2. Fetch payment transaction with user details
        $stmtTx = $db->prepare("
            SELECT pt.*, u.created_at AS user_registered_at, u.referral_partner_id AS user_partner_id, u.referred_by_code AS user_ref_code
            FROM payment_transactions pt
            JOIN users u ON pt.user_id = u.id
            WHERE pt.id = :id
            LIMIT 1
        ");
        $stmtTx->execute(['id' => $paymentTxId]);
        $tx = $stmtTx->fetch(PDO::FETCH_ASSOC);

        if (!$tx) {
            return null;
        }

        // Payment MUST be successful
        if (!in_array($tx['status'], ['paid', 'success'], true)) {
            return null;
        }

        // 3. Resolve referral partner
        $partnerId = !empty($tx['referral_partner_id']) ? (int)$tx['referral_partner_id'] : (!empty($tx['user_partner_id']) ? (int)$tx['user_partner_id'] : null);
        if (!$partnerId && !empty($tx['referral_code_used'])) {
            $partner = self::findPartnerByCode($tx['referral_code_used'], $db);
            if ($partner) {
                $partnerId = (int)$partner['id'];
            }
        }
        if (!$partnerId && !empty($tx['user_ref_code'])) {
            $partner = self::findPartnerByCode($tx['user_ref_code'], $db);
            if ($partner) {
                $partnerId = (int)$partner['id'];
            }
        }

        if (!$partnerId) {
            return null; // No partner attributed
        }

        // SELF-REFERRAL PREVENTION (DEFENSE-IN-DEPTH):
        // Partner must never earn commission on their own payment
        if ($partnerId === (int)$tx['user_id']) {
            Logger::warning("Self-referral commission rejected for payment {$paymentTxId}: partner {$partnerId} is user {$tx['user_id']}.");
            return null;
        }

        // Verify partner role is referral_partner and active
        $stmtPartner = $db->prepare("
            SELECT u.id, u.commission_percent, u.status 
            FROM users u 
            JOIN roles r ON u.role_id = r.id 
            WHERE u.id = :id AND r.name = 'referral_partner' AND u.status = 'active'
            LIMIT 1
        ");
        $stmtPartner->execute(['id' => $partnerId]);
        $partnerData = $stmtPartner->fetch(PDO::FETCH_ASSOC);
        if (!$partnerData) {
            return null;
        }

        // 4. Six-Calendar-Month Attribution Window Check:
        // Window is strictly 6 calendar months from the referred user's original registration date
        // Evaluates calendar months using MySQL DATE_ADD(created_at, INTERVAL :window MONTH)
        // Inclusive boundary rule: payment timestamp <= attribution expiry => eligible
        // Exclusive boundary rule: payment timestamp > attribution expiry => ineligible
        $registeredAt = $tx['user_registered_at'];
        $windowMonths = (int)self::getSetting('referral_attribution_window_months', '6', $db);
        if ($windowMonths <= 0) {
            $windowMonths = 6;
        }

        $attrEndStr = self::calculateAttributionExpiry($registeredAt, $windowMonths, $db);
        $paymentDateStr = !empty($tx['paid_at']) ? $tx['paid_at'] : $tx['created_at'];

        $paymentTimestamp = strtotime($paymentDateStr);
        $attributionEndTimestamp = strtotime($attrEndStr);

        // Strict boundary evaluation: payment strictly after expiry earns NO commission
        if ($paymentTimestamp > $attributionEndTimestamp) {
            return null;
        }

        // 5. Determine commission basis
        // Options: 'paid_amount_after_discount' (default) or 'original_plan_amount'
        $basis = self::getSetting('referral_commission_basis', 'paid_amount_after_discount', $db);
        if ($basis !== 'original_plan_amount') {
            $basis = 'paid_amount_after_discount';
        }

        $actualPaidAmount = (string)$tx['amount'];
        $originalPlanAmount = !empty($tx['original_amount']) ? (string)$tx['original_amount'] : (string)$tx['amount'];
        $discountPercentage = !empty($tx['discount_percent']) ? (string)$tx['discount_percent'] : '0.00';
        $discountAmount = !empty($tx['referral_discount_amount']) ? (string)$tx['referral_discount_amount'] : '0.00';

        $commissionBaseAmount = ($basis === 'original_plan_amount') ? $originalPlanAmount : $actualPaidAmount;

        // 6. Determine commission percentage with strict server-side validation
        $commPercent = null;
        if (!empty($partnerData['commission_percent'])) {
            $commPercent = (string)$partnerData['commission_percent'];
        } else {
            $commPercent = self::getSetting('referral_default_commission_percent', '30.00', $db);
        }

        $commPctBps = self::parsePercentageToBasisPoints($commPercent);
        if ($commPctBps === null || $commPctBps <= 0) {
            return null; // Invalid or 0% commission
        }

        // 7. Calculate commission amount in integer minor units safely without overflow
        $baseMinor = PaymentService::normalizeToMinorUnits($commissionBaseAmount, 2);
        if ($baseMinor === null || $baseMinor <= 0 || $baseMinor > self::MAX_SUPPORTED_MINOR_UNITS) {
            return null;
        }

        $commissionAmountMinor = self::calculatePercentageMinorSafe($baseMinor, $commPctBps);
        if ($commissionAmountMinor === null) {
            return null; // Fails safely on overflow
        }

        $commAmountStr = sprintf('%d.%02d', intdiv($commissionAmountMinor, 100), $commissionAmountMinor % 100);
        $commPctStr = sprintf('%d.%02d', intdiv($commPctBps, 100), $commPctBps % 100);
        $baseAmountStr = sprintf('%d.%02d', intdiv($baseMinor, 100), $baseMinor % 100);

        $paymentDateStr = date('Y-m-d H:i:s', $paymentTimestamp);
        $attrStartStr = date('Y-m-d H:i:s', strtotime($registeredAt));
        $attrEndStr = date('Y-m-d H:i:s', $attributionEndTimestamp);

        // 8. Insert into referral_commissions with idempotency safety
        $stmtInsert = $db->prepare("
            INSERT INTO referral_commissions (
                partner_id, referred_user_id, payment_transaction_id, transaction_reference,
                original_plan_amount, referral_discount_percentage, referral_discount_amount,
                actual_paid_amount, commission_percentage, commission_basis, commission_base_amount,
                commission_amount, payment_date, attribution_period_start, attribution_period_end,
                status, created_at, updated_at
            ) VALUES (
                :partner_id, :referred_user_id, :payment_transaction_id, :transaction_reference,
                :original_plan_amount, :referral_discount_percentage, :referral_discount_amount,
                :actual_paid_amount, :commission_percentage, :commission_basis, :commission_base_amount,
                :commission_amount, :payment_date, :attribution_period_start, :attribution_period_end,
                'earned', NOW(), NOW()
            )
            ON DUPLICATE KEY UPDATE updated_at = NOW()
        ");

        $stmtInsert->execute([
            'partner_id' => $partnerId,
            'referred_user_id' => (int)$tx['user_id'],
            'payment_transaction_id' => $paymentTxId,
            'transaction_reference' => $tx['transaction_reference'],
            'original_plan_amount' => $originalPlanAmount,
            'referral_discount_percentage' => $discountPercentage,
            'referral_discount_amount' => $discountAmount,
            'actual_paid_amount' => $actualPaidAmount,
            'commission_percentage' => $commPctStr,
            'commission_basis' => $basis,
            'commission_base_amount' => $baseAmountStr,
            'commission_amount' => $commAmountStr,
            'payment_date' => $paymentDateStr,
            'attribution_period_start' => $attrStartStr,
            'attribution_period_end' => $attrEndStr
        ]);

        $commId = (int)$db->lastInsertId();

        // 9. Permanently consume first-payment discount claim
        self::consumeDiscountClaim($paymentTxId, $db);

        // 10. Fetch inserted record
        $stmtComm = $db->prepare("SELECT * FROM referral_commissions WHERE payment_transaction_id = :id LIMIT 1");
        $stmtComm->execute(['id' => $paymentTxId]);
        $record = $stmtComm->fetch(PDO::FETCH_ASSOC);

        // Log audit event
        Auth::logAudit($partnerId, 'commission_earned', 'referrals', 'referral_commissions', (int)($record['id'] ?? $commId), null, [
            'payment_id' => $paymentTxId,
            'amount' => $commAmountStr,
            'basis' => $basis
        ]);

        return $record ?: null;
    }

    /**
     * Read setting with fallback.
     */
    public static function getSetting(string $key, string $default = '', ?PDO $db = null): string {
        $db = $db ?? Database::connection();
        $stmt = $db->prepare("SELECT `value` FROM settings WHERE `key` = :key LIMIT 1");
        $stmt->execute(['key' => $key]);
        $val = $stmt->fetchColumn();
        return ($val !== false && $val !== null && $val !== '') ? (string)$val : $default;
    }

    /**
     * Compute summary metrics for a partner dashboard:
     * - Total referred users
     * - Total paid users
     * - Current-month paid users
     * - Current-month payments
     * - Current-month commission
     * - Total earned commission
     */
    public static function getPartnerSummaryMetrics(int $partnerId, ?string $month = null, ?PDO $db = null): array {
        $db = $db ?? Database::connection();
        $targetMonth = $month ?: date('Y-m');

        // 1. Total referred users (all-time signups attributed to partner)
        $stmtSignups = $db->prepare("
            SELECT COUNT(DISTINCT referred_user_id) 
            FROM referral_signups 
            WHERE partner_id = :pid
        ");
        $stmtSignups->execute(['pid' => $partnerId]);
        $totalReferred = (int)$stmtSignups->fetchColumn();

        // 2. Total all-time paid users (attributed users who completed >= 1 payment)
        $stmtPaidUsers = $db->prepare("
            SELECT COUNT(DISTINCT pt.user_id) 
            FROM payment_transactions pt
            JOIN users u ON pt.user_id = u.id
            WHERE (pt.referral_partner_id = :pid OR (pt.referral_partner_id IS NULL AND u.referral_partner_id = :pid2))
              AND pt.status IN ('paid', 'success')
        ");
        $stmtPaidUsers->execute(['pid' => $partnerId, 'pid2' => $partnerId]);
        $totalPaidUsers = (int)$stmtPaidUsers->fetchColumn();

        // 3. Current-month paid users & payments
        $stmtMonth = $db->prepare("
            SELECT COUNT(DISTINCT pt.user_id) AS month_users, COUNT(pt.id) AS month_payments
            FROM payment_transactions pt
            JOIN users u ON pt.user_id = u.id
            WHERE (pt.referral_partner_id = :pid OR (pt.referral_partner_id IS NULL AND u.referral_partner_id = :pid2))
              AND pt.status IN ('paid', 'success')
              AND DATE_FORMAT(COALESCE(pt.paid_at, pt.created_at), '%Y-%m') = :month
        ");
        $stmtMonth->execute(['pid' => $partnerId, 'pid2' => $partnerId, 'month' => $targetMonth]);
        $monthStats = $stmtMonth->fetch(PDO::FETCH_ASSOC);

        $curMonthPaidUsers = (int)($monthStats['month_users'] ?? 0);
        $curMonthPayments = (int)($monthStats['month_payments'] ?? 0);

        // 4. Current-month commission from referral_commissions
        $stmtMonthComm = $db->prepare("
            SELECT COALESCE(SUM(commission_amount), 0.00) 
            FROM referral_commissions 
            WHERE partner_id = :pid 
              AND DATE_FORMAT(payment_date, '%Y-%m') = :month
        ");
        $stmtMonthComm->execute(['pid' => $partnerId, 'month' => $targetMonth]);
        $rawMonthComm = (string)$stmtMonthComm->fetchColumn();
        $monthCommMinor = PaymentService::normalizeToMinorUnits($rawMonthComm !== '' ? $rawMonthComm : '0.00', 2) ?? 0;
        $curMonthCommission = sprintf('%d.%02d', intdiv($monthCommMinor, 100), $monthCommMinor % 100);

        // 5. Total lifetime earned commission
        $stmtTotalComm = $db->prepare("
            SELECT COALESCE(SUM(commission_amount), 0.00) 
            FROM referral_commissions 
            WHERE partner_id = :pid
        ");
        $stmtTotalComm->execute(['pid' => $partnerId]);
        $rawTotalComm = (string)$stmtTotalComm->fetchColumn();
        $totalCommMinor = PaymentService::normalizeToMinorUnits($rawTotalComm !== '' ? $rawTotalComm : '0.00', 2) ?? 0;
        $totalEarnedCommission = sprintf('%d.%02d', intdiv($totalCommMinor, 100), $totalCommMinor % 100);

        return [
            'total_referred_users' => $totalReferred,
            'total_paid_referred_users' => $totalPaidUsers,
            'current_month_paid_users' => $curMonthPaidUsers,
            'current_month_payments' => $curMonthPayments,
            'current_month_commission' => $curMonthCommission,
            'total_earned_commission' => $totalEarnedCommission,
            'target_month' => $targetMonth
        ];
    }

    /**
     * Paginated list of monthly paying customer payments for partner dashboard.
     * Shows only users with successful payments in the selected month.
     * Unpaid referred users are excluded.
     * Flags active vs expired attribution status based on 6-month window.
     */
    public static function getPartnerMonthlyPayments(int $partnerId, string $month, int $page = 1, int $perPage = 10, ?PDO $db = null): array {
        $db = $db ?? Database::connection();
        $offset = max(0, ($page - 1) * $perPage);

        // Count total matching payments
        $stmtCount = $db->prepare("
            SELECT COUNT(pt.id)
            FROM payment_transactions pt
            JOIN users u ON pt.user_id = u.id
            WHERE (pt.referral_partner_id = :pid OR (pt.referral_partner_id IS NULL AND u.referral_partner_id = :pid2))
              AND pt.status IN ('paid', 'success')
              AND DATE_FORMAT(COALESCE(pt.paid_at, pt.created_at), '%Y-%m') = :month
        ");
        $stmtCount->execute(['pid' => $partnerId, 'pid2' => $partnerId, 'month' => $month]);
        $totalItems = (int)$stmtCount->fetchColumn();

        // Fetch records
        $stmt = $db->prepare("
            SELECT 
                pt.id AS payment_id,
                pt.transaction_reference,
                pt.amount AS paid_amount,
                pt.original_amount,
                pt.referral_discount_amount,
                pt.discount_percent,
                pt.paid_at,
                pt.created_at AS payment_created_at,
                u.id AS referred_user_id,
                u.first_name,
                u.last_name,
                u.email,
                u.created_at AS user_registered_at,
                sp.name AS plan_name,
                rc.id AS commission_id,
                rc.commission_percentage,
                rc.commission_amount,
                rc.attribution_period_start,
                rc.attribution_period_end
            FROM payment_transactions pt
            JOIN users u ON pt.user_id = u.id
            LEFT JOIN subscription_plans sp ON pt.plan_id = sp.id
            LEFT JOIN referral_commissions rc ON pt.id = rc.payment_transaction_id
            WHERE (pt.referral_partner_id = :pid OR (pt.referral_partner_id IS NULL AND u.referral_partner_id = :pid2))
              AND pt.status IN ('paid', 'success')
              AND DATE_FORMAT(COALESCE(pt.paid_at, pt.created_at), '%Y-%m') = :month
            ORDER BY COALESCE(pt.paid_at, pt.created_at) DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':pid', $partnerId, PDO::PARAM_INT);
        $stmt->bindValue(':pid2', $partnerId, PDO::PARAM_INT);
        $stmt->bindValue(':month', $month, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $windowMonths = (int)self::getSetting('referral_attribution_window_months', '6', $db);
        if ($windowMonths <= 0) $windowMonths = 6;

        // Decorate with attribution status, display name, and active subscription status
        foreach ($records as &$rec) {
            $rec['display_name'] = trim(($rec['first_name'] ?? '') . ' ' . ($rec['last_name'] ?? '')) ?: ($rec['email'] ?? 'Referred User');
            $rec['payment_date'] = !empty($rec['paid_at']) ? $rec['paid_at'] : $rec['payment_created_at'];
            $rec['status'] = !empty($rec['commission_id']) ? 'earned' : 'paid';

            $attrEndStr = self::calculateAttributionExpiry($rec['user_registered_at'], $windowMonths, $db);
            $paymentTimestamp = !empty($rec['paid_at']) ? strtotime($rec['paid_at']) : strtotime($rec['payment_created_at']);
            $attrEndTimestamp = strtotime($attrEndStr);

            $rec['is_attribution_active'] = ($paymentTimestamp <= $attrEndTimestamp);
            $rec['attribution_end_date'] = $attrEndStr;

            // Check current subscription status for this referred user
            $stmtSubStatus = $db->prepare("
                SELECT status, ends_at FROM subscriptions 
                WHERE user_id = :uid 
                ORDER BY id DESC LIMIT 1
            ");
            $stmtSubStatus->execute(['uid' => (int)$rec['referred_user_id']]);
            $userSub = $stmtSubStatus->fetch(PDO::FETCH_ASSOC);

            $isSubActive = false;
            if ($userSub) {
                if ($userSub['status'] === 'protected') {
                    $isSubActive = true;
                } elseif ($userSub['status'] === 'active' && strtotime($userSub['ends_at']) >= time()) {
                    $isSubActive = true;
                }
            }
            $rec['is_subscription_active'] = $isSubActive;
            $rec['is_current_active_customer'] = $rec['is_attribution_active'] && $isSubActive;
        }
        unset($rec);

        return [
            'records' => $records,
            'total_items' => $totalItems,
            'current_page' => $page,
            'per_page' => $perPage,
            'total_pages' => $perPage > 0 ? intdiv($totalItems + $perPage - 1, $perPage) : 1
        ];
    }

    /**
     * Check if a referred user is currently an active qualifying referral customer for a partner.
     *
     * Invariants:
     * 1. User must be attributed to the partner.
     * 2. User must have a verified paid subscription.
     * 3. Current subscription must be 'active' or 'protected' (not expired).
     * 4. Current time must be within 6 calendar months of the referred user's registration date.
     * 5. When subscription expires -> returns false (no longer active).
     * 6. When user repurchases -> returns true if still within 6-month window.
     */
    public static function isUserActiveReferralCustomer(int $userId, int $partnerId, ?PDO $db = null): bool {
        $db = $db ?? Database::connection();

        // 1. Verify attribution
        $stmtUser = $db->prepare("
            SELECT id, created_at, referral_partner_id 
            FROM users 
            WHERE id = :uid AND referral_partner_id = :pid 
            LIMIT 1
        ");
        $stmtUser->execute(['uid' => $userId, 'pid' => $partnerId]);
        $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return false;
        }

        // 2. Verify 6-calendar-month window from registration
        $windowMonths = (int)self::getSetting('referral_attribution_window_months', '6', $db);
        if ($windowMonths <= 0) $windowMonths = 6;
        $attrExpiryStr = self::calculateAttributionExpiry($user['created_at'], $windowMonths, $db);
        if (time() > strtotime($attrExpiryStr)) {
            return false; // Registration was more than 6 months ago
        }

        // 3. Verify user has an active or protected paid subscription
        $stmtSub = $db->prepare("
            SELECT id, status, ends_at 
            FROM subscriptions 
            WHERE user_id = :uid 
            ORDER BY id DESC LIMIT 1
        ");
        $stmtSub->execute(['uid' => $userId]);
        $sub = $stmtSub->fetch(PDO::FETCH_ASSOC);

        if (!$sub) {
            return false;
        }

        if ($sub['status'] === 'protected') {
            return true;
        }

        if ($sub['status'] === 'active' && strtotime($sub['ends_at']) >= time()) {
            return true;
        }

        return false;
    }

    /**
     * Paginated list of currently active referred customers for partner dashboard.
     * Shows only customers whose subscription is currently active/protected and within the 6-month window.
     * Expired subscriptions and users past 6 months are excluded.
     */
    public static function getPartnerActiveCustomers(int $partnerId, int $page = 1, int $perPage = 15, ?PDO $db = null): array {
        $db = $db ?? Database::connection();
        $offset = max(0, ($page - 1) * $perPage);
        $windowMonths = (int)self::getSetting('referral_attribution_window_months', '6', $db);
        if ($windowMonths <= 0) $windowMonths = 6;

        // Query active customers with subquery for active/protected subscription
        $sql = "
            SELECT 
                u.id AS referred_user_id,
                u.first_name,
                u.last_name,
                u.email,
                u.created_at AS user_registered_at,
                s.id AS subscription_id,
                s.status AS subscription_status,
                s.starts_at AS subscription_starts_at,
                s.ends_at AS subscription_ends_at,
                s.normal_ends_at,
                sp.name AS plan_name
            FROM users u
            JOIN (
                SELECT s1.*
                FROM subscriptions s1
                JOIN (
                    SELECT user_id, MAX(id) AS max_id 
                    FROM subscriptions 
                    GROUP BY user_id
                ) s2 ON s1.id = s2.max_id
                WHERE (s1.status = 'protected' OR (s1.status = 'active' AND s1.ends_at >= NOW()))
            ) s ON u.id = s.user_id
            JOIN subscription_plans sp ON s.plan_id = sp.id
            WHERE u.referral_partner_id = :pid
              AND DATE_ADD(u.created_at, INTERVAL :window MONTH) >= NOW()
            ORDER BY s.starts_at DESC
        ";

        // Count
        $countSql = "SELECT COUNT(*) FROM ($sql) AS active_cust";
        $stmtCount = $db->prepare($countSql);
        $stmtCount->execute(['pid' => $partnerId, 'window' => $windowMonths]);
        $totalItems = (int)$stmtCount->fetchColumn();

        // Fetch
        $fetchSql = $sql . " LIMIT :limit OFFSET :offset";
        $stmt = $db->prepare($fetchSql);
        $stmt->bindValue(':pid', $partnerId, PDO::PARAM_INT);
        $stmt->bindValue(':window', $windowMonths, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($records as &$rec) {
            $rec['display_name'] = trim(($rec['first_name'] ?? '') . ' ' . ($rec['last_name'] ?? '')) ?: ($rec['email'] ?? 'Referred User');
            $rec['attribution_end_date'] = self::calculateAttributionExpiry($rec['user_registered_at'], $windowMonths, $db);
            $rec['is_attribution_active'] = true;
            $rec['is_subscription_active'] = true;
        }
        unset($rec);

        return [
            'records' => $records,
            'total_items' => $totalItems,
            'current_page' => $page,
            'per_page' => $perPage,
            'total_pages' => $perPage > 0 ? intdiv($totalItems + $perPage - 1, $perPage) : 1
        ];
    }
}
