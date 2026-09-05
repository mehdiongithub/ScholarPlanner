<?php

namespace App\Services;

class NotificationTypes {
    public const NEW_MATCH = 'NEW_MATCH';
    public const DEADLINE_REMINDER = 'DEADLINE_REMINDER';
    public const EMAIL_VERIFICATION = 'EMAIL_VERIFICATION';

    // Existing types (for backwards compatibility/existing code)
    public const SCHOLARSHIP_DEADLINE_SOON = 'SCHOLARSHIP_DEADLINE_SOON';
    public const SCHOLARSHIP_DEADLINE_TODAY = 'SCHOLARSHIP_DEADLINE_TODAY';
    public const DAILY_MATCH_DIGEST = 'DAILY_MATCH_DIGEST';
    public const WEEKLY_MATCH_DIGEST = 'WEEKLY_MATCH_DIGEST';

    public const PAYMENT_CONFIRMATION = 'PAYMENT_CONFIRMATION';
    public const PAYMENT_SUCCESS = 'PAYMENT_SUCCESS';
    public const SUBSCRIPTION_CONFIRMATION = 'SUBSCRIPTION_CONFIRMATION';
    public const PASSWORD_RESET = 'PASSWORD_RESET';

    /**
     * Explicit whitelist of automatic scholarship notification types subject to the 25-message lifetime limit.
     */
    public const SCHOLARSHIP_TYPES = [
        self::NEW_MATCH,
        self::DEADLINE_REMINDER,
        self::SCHOLARSHIP_DEADLINE_SOON,
        self::SCHOLARSHIP_DEADLINE_TODAY,
        self::DAILY_MATCH_DIGEST,
        self::WEEKLY_MATCH_DIGEST
    ];

    /**
     * Check if a given notification type is an automatic scholarship notification.
     */
    public static function isScholarshipType(string $type): bool {
        return in_array($type, self::SCHOLARSHIP_TYPES, true);
    }
}
