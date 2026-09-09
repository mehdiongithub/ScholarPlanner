<?php

namespace App\Services;

use App\Services\Database;
use App\Services\Auth;
use App\Services\Logger;
use App\Services\NotificationTypes;
use App\Services\NotificationQueueService;
use PDO;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use DateTimeInterface;

class SubscriptionService {
    public static array $transitions = [
        'pending' => ['active', 'failed', 'cancelled'],
        'active' => ['past_due', 'cancelled', 'expired', 'protected'],
        'protected' => ['expired', 'active', 'cancelled'],
        'past_due' => ['active', 'cancelled', 'expired'],
        'cancelled' => ['expired', 'active', 'protected'],
        'expired' => ['active'],
        'failed' => ['pending']
    ];

    /**
     * Determine if a user has access to a specific feature.
     */
    public static function can(?int $userId, string $feature): bool {
        if (!$userId) {
            return false;
        }

        $plan = self::getActivePlan($userId);
        $slug = $plan['plan_slug'];

        if ($slug === 'premium-monthly') {
            return true;
        }

        // Free plan permissions mapping
        switch ($feature) {
            case 'public_discovery':
            case 'basic_search':
            case 'basic_details':
                return true;
            case 'premium_matching':
            case 'advanced_search':
            case 'application_tracking':
            case 'document_readiness':
            case 'deadline_alerts':
            case 'whatsapp_alerts':
            case 'premium_alerts':
            case 'advanced_dashboard':
            default:
                return false;
        }
    }

    /**
     * Get numeric limit for a feature constraint.
     */
    public static function getLimit(?int $userId, string $limitName): int {
        if (!$userId) {
            if ($limitName === 'saved_scholarships') return 0;
            if ($limitName === 'comparisons') return 0;
            return 0;
        }

        $plan = self::getActivePlan($userId);
        $slug = $plan['plan_slug'];

        if ($slug === 'premium-monthly') {
            if ($limitName === 'saved_scholarships') return 999999;
            if ($limitName === 'comparisons') return 4; // Max 4 side-by-side
            if ($limitName === 'max_matches') return 9999;
            return 999999;
        }

        // Free plan limits
        switch ($limitName) {
            case 'saved_scholarships':
                return 10;
            case 'comparisons':
                return 3;
            case 'max_matches':
                return 5;
            default:
                return 0;
        }
    }

    /**
     * Fetch active subscription plan details for a user.
     * Respects protected state so a protected subscription retains full paid entitlements.
     */
    public static function getActivePlan(int $userId): array {
        $db = Database::connection();
        
        // Fetch candidate (protected, or active/cancelled within validity window)
        $stmt = $db->prepare("
            SELECT s.*, p.slug as plan_slug, p.name as plan_name, p.price, p.currency
            FROM subscriptions s
            JOIN subscription_plans p ON s.plan_id = p.id
            WHERE s.user_id = :user_id 
              AND (
                  s.status = 'protected'
                  OR (s.status IN ('active', 'cancelled') AND s.starts_at <= NOW() AND s.ends_at >= NOW())
              )
            ORDER BY s.id DESC LIMIT 1
        ");
        $stmt->execute(['user_id' => $userId]);
        $sub = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($sub) {
            return $sub;
        }

        // Default to Free Plan
        $stmtFree = $db->prepare("SELECT *, 'free' as plan_slug, 'Free' as plan_name FROM subscription_plans WHERE slug = 'free' LIMIT 1");
        $stmtFree->execute();
        $free = $stmtFree->fetch(PDO::FETCH_ASSOC);

        if ($free) {
            return $free;
        }

        // Fallback if seeder didn't run
        return [
            'plan_slug' => 'free',
            'plan_name' => 'Free',
            'price' => 0.00,
            'currency' => 'PKR',
            'max_matches' => 5
        ];
    }

    /**
     * Transition a subscription to a new state safely.
     */
    public static function transition(int $subscriptionId, string $newStatus, ?string $reason = null, ?PDO $db = null): bool {
        $db = $db ?? Database::connection();
        $stmt = $db->prepare("SELECT status, user_id FROM subscriptions WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $subscriptionId]);
        $sub = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$sub) {
            return false;
        }

        $currentStatus = $sub['status'];
        if ($currentStatus === $newStatus) {
            return true;
        }

        $allowed = self::$transitions[$currentStatus] ?? [];
        if (!in_array($newStatus, $allowed, true)) {
            return false;
        }

        $sql = "UPDATE subscriptions SET status = :status, updated_at = NOW()";
        $params = ['status' => $newStatus, 'id' => $subscriptionId];

        if ($newStatus === 'expired') {
            $sql .= ", final_expired_at = COALESCE(final_expired_at, NOW())";
            if ($reason !== null) {
                $sql .= ", expiry_reason = :reason";
                $params['reason'] = $reason;
            }
        }

        $sql .= " WHERE id = :id";
        $upd = $db->prepare($sql);
        $success = $upd->execute($params);

        if ($success) {
            // Log audit
            Auth::logAudit(
                (int)$sub['user_id'],
                'subscription_status_transitioned',
                'subscriptions',
                'subscriptions',
                $subscriptionId,
                null,
                [
                    'from' => $currentStatus,
                    'to' => $newStatus,
                    'reason' => $reason
                ]
            );
        }

        return $success;
    }

    /**
     * Calculate subscription expiry timestamp string based on plan configuration.
     * Enforces calendar-month boundary ending at 23:59:59 for monthly plans.
     *
     * @param array|int $plan Plan array or plan ID
     * @param string|DateTimeInterface|null $fromTime Base time string or DateTime (default now)
     * @return string MySQL datetime format 'Y-m-d H:i:s'
     */
    public static function calculatePlanExpiry($plan, $fromTime = null): string {
        $tzString = config('app.timezone', 'Asia/Karachi');
        $tz = new DateTimeZone($tzString);

        if ($fromTime instanceof DateTimeInterface) {
            $baseDt = new DateTime($fromTime->format('Y-m-d H:i:s'), $tz);
        } elseif (is_string($fromTime) && trim($fromTime) !== '') {
            $baseDt = new DateTime($fromTime, $tz);
        } else {
            $baseDt = new DateTime('now', $tz);
        }

        // If fromTime represents the exact end of a previous period (23:59:59),
        // the new period begins 1 second later at 00:00:00 of the next calendar day.
        if ($baseDt->format('H:i:s') === '23:59:59') {
            $baseDt->modify('+1 second');
        }

        $planData = is_array($plan) ? $plan : null;
        if (!$planData && is_numeric($plan)) {
            $db = Database::connection();
            $stmt = $db->prepare("SELECT * FROM subscription_plans WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => (int)$plan]);
            $planData = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        }

        $interval = strtolower(trim((string)($planData['billing_interval'] ?? 'month')));

        // 1. Calendar-month rule for monthly plans (takes precedence over duration_days for calendar-month plans)
        if ($interval === 'month' || $interval === 'monthly') {
            return self::calculateCalendarMonthExpiry($baseDt, 1, $tz);
        }

        // 2. Explicit duration in days (for custom day plans)
        if (!empty($planData['duration_days']) && (int)$planData['duration_days'] > 0) {
            $days = (int)$planData['duration_days'];
            $targetDt = clone $baseDt;
            $targetDt->modify("+{$days} days");
            return $targetDt->format('Y-m-d H:i:s');
        }

        switch ($interval) {
            case 'year':
            case 'yearly':
            case 'annual':
                $targetDt = clone $baseDt;
                $targetDt->modify('+1 year');
                return $targetDt->format('Y-m-d H:i:s');
            case 'quarter':
            case 'quarterly':
                return self::calculateCalendarMonthExpiry($baseDt, 3, $tz);
            case 'week':
            case 'weekly':
                $targetDt = clone $baseDt;
                $targetDt->modify('+1 week');
                return $targetDt->format('Y-m-d H:i:s');
            case 'day':
            case 'daily':
                $targetDt = clone $baseDt;
                $targetDt->modify('+1 day');
                return $targetDt->format('Y-m-d H:i:s');
            default:
                return self::calculateCalendarMonthExpiry($baseDt, 1, $tz);
        }
    }

    /**
     * Calculate exact calendar-month subscription end date ending at 23:59:59.
     *
     * Rules:
     * - Day D of Month M:
     *   If D == 1: ends on the last day of month (M + months - 1) at 23:59:59.
     *   If D > 1: target day is D - 1 in month (M + months), clamped to the number of days in that target month, at 23:59:59.
     *
     * Examples:
     * - 10 September 2026 -> 9 October 2026 23:59:59
     * - 31 January 2026 -> 28 February 2026 23:59:59
     * - 31 January 2028 (leap year) -> 29 February 2028 23:59:59
     * - 1 February 2026 -> 28 February 2026 23:59:59
     * - 31 March 2026 -> 30 April 2026 23:59:59
     * - 31 August 2026 -> 30 September 2026 23:59:59
     * - 10 December 2026 -> 9 January 2027 23:59:59
     *
     * @param DateTimeInterface $startDt
     * @param int $months
     * @param DateTimeZone|null $tz
     * @return string Formatted datetime 'Y-m-d 23:59:59'
     */
    public static function calculateCalendarMonthExpiry(DateTimeInterface $startDt, int $months = 1, ?DateTimeZone $tz = null): string {
        $tz = $tz ?? new DateTimeZone(config('app.timezone', 'Asia/Karachi'));
        $start = new DateTime($startDt->format('Y-m-d H:i:s'), $tz);

        $y = (int)$start->format('Y');
        $m = (int)$start->format('n');
        $d = (int)$start->format('j');

        if ($d === 1) {
            // Started on day 1: full calendar month(s) entitlement
            $endMonthIdx = $m + $months - 1;
            $targetYear = $y + intdiv($endMonthIdx - 1, 12);
            $targetMonth = (($endMonthIdx - 1) % 12) + 1;
            $daysInTarget = (int)(new DateTime(sprintf('%04d-%02d-01', $targetYear, $targetMonth), $tz))->format('t');
            return sprintf('%04d-%02d-%02d 23:59:59', $targetYear, $targetMonth, $daysInTarget);
        }

        // Started on day D > 1: entitlement ends on day D - 1 in target month
        $targetMonthIdx = $m + $months;
        $targetYear = $y + intdiv($targetMonthIdx - 1, 12);
        $targetMonth = (($targetMonthIdx - 1) % 12) + 1;
        $daysInTarget = (int)(new DateTime(sprintf('%04d-%02d-01', $targetYear, $targetMonth), $tz))->format('t');
        $targetDay = min($d - 1, $daysInTarget);

        return sprintf('%04d-%02d-%02d 23:59:59', $targetYear, $targetMonth, $targetDay);
    }

    /**
     * Calculate exact calendar-year subscription end date ending at 23:59:59.
     *
     * @param DateTimeInterface $startDt
     * @param int $years
     * @param DateTimeZone|null $tz
     * @return string Formatted datetime 'Y-m-d 23:59:59'
     */
    public static function calculateCalendarYearExpiry(DateTimeInterface $startDt, int $years = 1, ?DateTimeZone $tz = null): string {
        $tz = $tz ?? new DateTimeZone(config('app.timezone', 'Asia/Karachi'));
        $start = new DateTime($startDt->format('Y-m-d H:i:s'), $tz);

        $y = (int)$start->format('Y');
        $m = (int)$start->format('n');
        $d = (int)$start->format('j');

        $targetYear = $y + $years;

        if ($d === 1) {
            // If started on 1 Jan, ends 31 Dec of targetYear - 1
            $endYear = $targetYear - 1;
            $endMonth = ($m === 1) ? 12 : $m - 1;
            if ($m === 1) {
                // Whole calendar year
            }
            $daysInTarget = (int)(new DateTime(sprintf('%04d-%02d-01', $endYear, 12), $tz))->format('t');
            return sprintf('%04d-%02d-%02d 23:59:59', $endYear, 12, $daysInTarget);
        }

        $daysInTarget = (int)(new DateTime(sprintf('%04d-%02d-01', $targetYear, $m), $tz))->format('t');
        $targetDay = min($d - 1, $daysInTarget);

        return sprintf('%04d-%02d-%02d 23:59:59', $targetYear, $m, $targetDay);
    }

    /**
     * Determine whether a notification log qualifies toward the minimum 5 delivered messages guarantee.
     *
     * Rules:
     * - Must have confirmed status = 'delivered'
     * - Must have non-empty delivered_at timestamp
     * - Must represent an actual scholarship notification (NotificationTypes::isScholarshipType)
     * - Must not be a system/transactional message (verification, password reset, receipt, etc.)
     * - Must match user_id and subscription entitlement window
     */
    public static function isQualifyingDeliveredMessage(array $log, ?array $subscription = null): bool {
        // 1. Must have a non-empty subscription_id
        if (empty($log['subscription_id'])) {
            return false;
        }

        // 2. Status must be delivered
        if (($log['status'] ?? '') !== 'delivered') {
            return false;
        }

        // 3. Must have valid confirmed delivery timestamp
        if (empty($log['delivered_at'])) {
            return false;
        }

        // 4. Must be an actual scholarship notification type
        $type = $log['notification_type'] ?? '';
        if (!NotificationTypes::isScholarshipType($type)) {
            return false;
        }

        // 5. Must not be transactional, administrative, or system notification
        $excludedTypes = [
            'EMAIL_VERIFICATION',
            'PASSWORD_RESET',
            'PAYMENT_CONFIRMATION',
            'PAYMENT_SUCCESS',
            'SUBSCRIPTION_CONFIRMATION',
            'SUBSCRIPTION_EXPIRED',
            'SUBSCRIPTION_RENEWAL_REMINDER',
            'ADMIN_BROADCAST',
            'INTERNAL_NOTE',
            'SYSTEM_ALERT',
            'REFERRAL_PAYOUT'
        ];
        if (in_array($type, $excludedTypes, true)) {
            return false;
        }

        // 6. Subscription relationship check
        if ($subscription) {
            if ((int)($log['user_id'] ?? 0) !== (int)$subscription['user_id']) {
                return false;
            }

            if ((int)$log['subscription_id'] !== (int)$subscription['id']) {
                return false;
            }

            $deliveredTs = strtotime($log['delivered_at']);
            $startsTs = strtotime($subscription['starts_at']);

            if ($deliveredTs < $startsTs) {
                return false;
            }

            if (!empty($subscription['final_expired_at'])) {
                $finalTs = strtotime($subscription['final_expired_at']);
                if ($deliveredTs > $finalTs) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Authoritatively count qualifying delivered scholarship messages for a specific subscription.
     * Uses notification_logs as the single source of truth with strict subscription_id ownership.
     */
    public static function countQualifyingDeliveredMessages(int $subscriptionId, ?PDO $db = null): int {
        $db = $db ?? Database::connection();

        $stmtSub = $db->prepare("SELECT * FROM subscriptions WHERE id = :id LIMIT 1");
        $stmtSub->execute(['id' => $subscriptionId]);
        $sub = $stmtSub->fetch(PDO::FETCH_ASSOC);

        if (!$sub) {
            return 0;
        }

        $scholarshipTypes = NotificationTypes::SCHOLARSHIP_TYPES;
        $inClause = "'" . implode("','", array_map('addslashes', $scholarshipTypes)) . "'";

        $params = [
            'user_id' => (int)$sub['user_id'],
            'sub_id' => $subscriptionId,
            'starts_at' => $sub['starts_at']
        ];

        $finalExpiredClause = '';
        if (!empty($sub['final_expired_at'])) {
            $finalExpiredClause = 'AND nl.delivered_at <= :final_expired_at';
            $params['final_expired_at'] = $sub['final_expired_at'];
        }

        $sql = "
            SELECT COUNT(DISTINCT COALESCE(nl.idempotency_key, nl.provider_message_id, CAST(nl.id AS CHAR)))
            FROM notification_logs nl
            WHERE nl.subscription_id = :sub_id
              AND nl.user_id = :user_id
              AND nl.status = 'delivered'
              AND nl.delivered_at IS NOT NULL
              AND nl.delivered_at >= :starts_at
              AND nl.notification_type IN ($inClause)
              $finalExpiredClause
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Synchronize and record subscription usage metrics in subscription_usage table.
     */
    public static function syncSubscriptionUsage(int $subscriptionId, ?PDO $db = null): array {
        $db = $db ?? Database::connection();

        $stmtSub = $db->prepare("SELECT * FROM subscriptions WHERE id = :id LIMIT 1");
        $stmtSub->execute(['id' => $subscriptionId]);
        $sub = $stmtSub->fetch(PDO::FETCH_ASSOC);

        if (!$sub) {
            return [];
        }

        $userId = (int)$sub['user_id'];
        $count = self::countQualifyingDeliveredMessages($subscriptionId, $db);
        $minRequired = (int)($sub['minimum_delivered_required'] ?? 5);
        $isProtected = ($sub['status'] === 'protected') ? 1 : 0;
        $isFinalExpired = ($sub['status'] === 'expired') ? 1 : 0;

        $stmtUsage = $db->prepare("
            INSERT INTO subscription_usage (
                user_id, subscription_id, feature, usage_count, qualifying_delivered_count,
                minimum_required, protected_state, final_expired_state, period_start, period_end, created_at, updated_at
            ) VALUES (
                :user_id, :sub_id, 'qualifying_scholarship_alerts', :count, :count2,
                :min_req, :protected_state, :final_expired_state, :period_start, :period_end, NOW(), NOW()
            )
            ON DUPLICATE KEY UPDATE
                subscription_id = VALUES(subscription_id),
                usage_count = VALUES(usage_count),
                qualifying_delivered_count = VALUES(qualifying_delivered_count),
                minimum_required = VALUES(minimum_required),
                protected_state = VALUES(protected_state),
                final_expired_state = VALUES(final_expired_state),
                period_end = VALUES(period_end),
                updated_at = NOW()
        ");

        $stmtUsage->execute([
            'user_id' => $userId,
            'sub_id' => $subscriptionId,
            'count' => $count,
            'count2' => $count,
            'min_req' => $minRequired,
            'protected_state' => $isProtected,
            'final_expired_state' => $isFinalExpired,
            'period_start' => $sub['starts_at'],
            'period_end' => $sub['ends_at']
        ]);

        return [
            'subscription_id' => $subscriptionId,
            'user_id' => $userId,
            'qualifying_delivered_count' => $count,
            'minimum_required' => $minRequired,
            'protected_state' => (bool)$isProtected,
            'final_expired_state' => (bool)$isFinalExpired
        ];
    }

    /**
     * Daily Subscription Lifecycle Engine.
     *
     * Responsibilities:
     * 1. Evaluates all subscriptions whose normal expiry date (ends_at) has arrived.
     * 2. Counts qualifying delivered messages via countQualifyingDeliveredMessages().
     * 3. If count < 5:
     *    - DO NOT expire. Keep subscription active/protected.
     *    - Transitions status to 'protected' if not already protected.
     *    - Updates subscription_usage.
     *    - Logs audit deferral.
     *    - Does NOT send expiration notification.
     * 4. If count >= 5:
     *    - Transitions subscription to 'expired'.
     *    - Sets final_expired_at = NOW(), expiry_reason = 'minimum_delivery_satisfied' (or 'normal_completion').
     *    - Updates subscription_usage.
     *    - Enqueues personalized expiration notification ("Hello {User Name}") with idempotency key.
     *    - Logs audit expiration.
     * 5. Enqueues renewal reminders (7, 3, 1, 0 days) with unique idempotency keys.
     *
     * Idempotent: can be safely executed repeatedly on the same day without duplicating alerts or transitions.
     */
    public static function processDailyLifecycle(?PDO $db = null): array {
        $db = $db ?? Database::connection();

        $metrics = [
            'evaluated' => 0,
            'protected' => 0,
            'expired' => 0,
            'reminders' => 0
        ];

        // 1. Fetch candidate subscriptions whose normal ends_at has passed
        $stmtCandidates = $db->query("
            SELECT id, user_id, plan_id, status, starts_at, ends_at, normal_ends_at, minimum_delivered_required
            FROM subscriptions 
            WHERE status IN ('active', 'protected', 'cancelled') 
              AND ends_at <= NOW()
            ORDER BY id ASC
        ");
        $candidates = $stmtCandidates->fetchAll(PDO::FETCH_ASSOC);

        $queueService = new NotificationQueueService();

        foreach ($candidates as $candidate) {
            $metrics['evaluated']++;
            $subId = (int)$candidate['id'];
            $userId = (int)$candidate['user_id'];
            $currentStatus = $candidate['status'];
            $minRequired = (int)($candidate['minimum_delivered_required'] ?? 5);

            // Execute evaluation within isolated transaction with row lock
            $db->beginTransaction();
            try {
                $stmtLock = $db->prepare("SELECT * FROM subscriptions WHERE id = :id FOR UPDATE");
                $stmtLock->execute(['id' => $subId]);
                $lockedSub = $stmtLock->fetch(PDO::FETCH_ASSOC);

                if (!$lockedSub || !in_array($lockedSub['status'], ['active', 'protected', 'cancelled'], true)) {
                    $db->commit();
                    continue;
                }

                $deliveredCount = self::countQualifyingDeliveredMessages($subId, $db);

                if ($deliveredCount < $minRequired) {
                    // DEFICIT: Subscription remains protected!
                    if ($lockedSub['status'] !== 'protected') {
                        $stmtUpd = $db->prepare("UPDATE subscriptions SET status = 'protected', updated_at = NOW() WHERE id = :id");
                        $stmtUpd->execute(['id' => $subId]);

                        Auth::logAudit(
                            $userId,
                            'subscription_expiry_deferred_protection',
                            'subscriptions',
                            'subscriptions',
                            $subId,
                            null,
                            [
                                'normal_ends_at' => $lockedSub['normal_ends_at'] ?: $lockedSub['ends_at'],
                                'qualifying_delivered' => $deliveredCount,
                                'minimum_required' => $minRequired
                            ]
                        );
                    }

                    self::syncSubscriptionUsage($subId, $db);
                    $db->commit();
                    $metrics['protected']++;

                } else {
                    // SATISFIED (count >= minRequired): Transition to expired
                    $reason = ($lockedSub['status'] === 'protected') ? 'minimum_delivery_satisfied' : 'normal_completion';
                    $stmtExp = $db->prepare("
                        UPDATE subscriptions 
                        SET status = 'expired', 
                            final_expired_at = NOW(), 
                            expiry_reason = :reason, 
                            updated_at = NOW() 
                        WHERE id = :id
                    ");
                    $stmtExp->execute([
                        'reason' => $reason,
                        'id' => $subId
                    ]);

                    self::syncSubscriptionUsage($subId, $db);

                    // Fetch user details for dynamic name insertion
                    $stmtUser = $db->prepare("SELECT id, email, first_name, last_name FROM users WHERE id = :id LIMIT 1");
                    $stmtUser->execute(['id' => $userId]);
                    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

                    if ($user && !empty($user['email'])) {
                        $userName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: 'Scholar';
                        
                        $subject = "Your ScholarPlanner Subscription Has Ended";
                        $bodySummary = "Hello {$userName},\n\n"
                            . "Your ScholarPlanner subscription has now ended. We hope the scholarship alerts and personalized opportunities were helpful to you.\n\n"
                            . "To continue receiving premium scholarship notifications and access to your subscription benefits, please purchase a new subscription.\n\n"
                            . "Visit your subscription page to choose a plan and continue your scholarship journey.";

                        // Enqueue notification with guaranteed idempotency key
                        $queueService->enqueue(
                            $userId,
                            null,
                            'SUBSCRIPTION_EXPIRED',
                            'email',
                            $user['email'],
                            $subject,
                            [
                                'summary' => $bodySummary,
                                'detail_url' => url('/pricing'),
                                'user_name' => $userName,
                                'subscription_id' => $subId
                            ],
                            "sub_expired_alert_{$subId}",
                            null,
                            $subId
                        );
                    }

                    Auth::logAudit(
                        $userId,
                        'subscription_expired',
                        'subscriptions',
                        'subscriptions',
                        $subId,
                        null,
                        [
                            'normal_ends_at' => $lockedSub['normal_ends_at'] ?: $lockedSub['ends_at'],
                            'final_expired_at' => date('Y-m-d H:i:s'),
                            'qualifying_delivered' => $deliveredCount,
                            'minimum_required' => $minRequired,
                            'reason' => $reason
                        ]
                    );

                    $db->commit();
                    $metrics['expired']++;
                }

            } catch (\Throwable $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                Logger::error("Error processing subscription lifecycle for #{$subId}: " . $e->getMessage());
            }
        }

        // 2. Queue Renewal Notifications (7 days, 3 days, 1 day, and 0 days left)
        $stmtReminders = $db->query("
            SELECT s.id as sub_id, s.user_id, s.ends_at, u.email, u.first_name, u.last_name, p.name as plan_name,
                   DATEDIFF(s.ends_at, NOW()) as days_left
            FROM subscriptions s
            JOIN users u ON s.user_id = u.id
            JOIN subscription_plans p ON s.plan_id = p.id
            WHERE s.status = 'active'
              AND DATEDIFF(s.ends_at, NOW()) IN (7, 3, 1, 0)
        ");
        $reminders = $stmtReminders->fetchAll(PDO::FETCH_ASSOC);

        foreach ($reminders as $r) {
            $daysLeft = (int)$r['days_left'];
            $subId = (int)$r['sub_id'];
            $userId = (int)$r['user_id'];
            $email = $r['email'];
            $planName = $r['plan_name'];
            $userName = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? '')) ?: 'Scholar';

            if ($daysLeft === 0) {
                $subject = "Your {$planName} Plan Renews Today";
                $message = "Hello {$userName},\n\nThis is a reminder that your subscription for {$planName} is scheduled to renew today. Thank you for choosing ScholarPlanner!";
            } else {
                $subject = "Renewal Reminder: {$daysLeft} days until subscription renews";
                $message = "Hello {$userName},\n\nYour {$planName} plan is scheduled to renew in {$daysLeft} days. Thank you for using ScholarPlanner!";
            }

            $idempotencyKey = "sub_renew_{$subId}_{$daysLeft}";

            $enqueued = $queueService->enqueue(
                $userId,
                null,
                'SUBSCRIPTION_RENEWAL_REMINDER',
                'email',
                $email,
                $subject,
                [
                    'summary' => $message,
                    'detail_url' => url('/billing'),
                    'user_name' => $userName,
                    'subscription_id' => $subId
                ],
                $idempotencyKey,
                null,
                $subId
            );

            if ($enqueued) {
                $metrics['reminders']++;
            }
        }

        return $metrics;
    }
}
