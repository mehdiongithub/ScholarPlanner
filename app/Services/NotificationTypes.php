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
}
