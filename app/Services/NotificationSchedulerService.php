<?php

namespace App\Services;

use PDO;
use Exception;
use InvalidArgumentException;
use DateTime;
use DateTimeZone;
use DateTimeInterface;

class NotificationSchedulerService {
    private PDO $db;

    public const ALLOWED_WEEKDAYS = [
        'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'
    ];

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? Database::connection();
    }

    /**
     * Fetch all notification scheduler settings with safe defaults.
     */
    public function getSettings(): array {
        $stmt = $this->db->query("SELECT `key`, `value` FROM settings WHERE group_name = 'notifications'");
        $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $allowedDaysRaw = $rows['whatsapp_allowed_days'] ?? 'Monday,Tuesday,Wednesday,Thursday,Friday';
        $allowedDays = array_values(array_filter(array_map('trim', explode(',', $allowedDaysRaw))));

        return [
            'whatsapp_notifications_enabled' => ($rows['whatsapp_notifications_enabled'] ?? '1') === '1',
            'whatsapp_allowed_days' => !empty($allowedDays) ? $allowedDays : ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
            'whatsapp_send_time' => $rows['whatsapp_send_time'] ?? '10:00',
            'whatsapp_timezone' => $rows['whatsapp_timezone'] ?? ($_ENV['APP_TIMEZONE'] ?? 'Asia/Karachi'),
            'whatsapp_batch_size' => (int)($rows['whatsapp_batch_size'] ?? 50),
            'whatsapp_new_match_enabled' => ($rows['whatsapp_new_match_enabled'] ?? '1') === '1',
            'whatsapp_deadline_reminder_enabled' => ($rows['whatsapp_deadline_reminder_enabled'] ?? '1') === '1'
        ];
    }

    /**
     * Validate incoming scheduler settings.
     */
    public function validateSettings(array $input): array {
        $errors = [];
        $sanitized = [];

        // 1. WhatsApp Notifications Enabled (Boolean)
        $enabledVal = $input['whatsapp_notifications_enabled'] ?? null;
        if ($enabledVal === null || $enabledVal === '') {
            $sanitized['whatsapp_notifications_enabled'] = '0';
        } else {
            $sanitized['whatsapp_notifications_enabled'] = in_array($enabledVal, [1, '1', true, 'true', 'on', 'yes'], true) ? '1' : '0';
        }

        // 2. Allowed Days (Set of valid weekdays)
        $daysInput = $input['whatsapp_allowed_days'] ?? [];
        if (is_string($daysInput)) {
            $daysInput = explode(',', $daysInput);
        }

        if (!is_array($daysInput) || empty($daysInput)) {
            $errors['whatsapp_allowed_days'] = 'At least one valid weekday must be selected.';
        } else {
            $validDays = [];
            foreach ($daysInput as $d) {
                $trimmed = trim($d);
                if (in_array($trimmed, self::ALLOWED_WEEKDAYS, true)) {
                    $validDays[] = $trimmed;
                } else {
                    $errors['whatsapp_allowed_days'] = "Invalid weekday specified: '{$trimmed}'.";
                    break;
                }
            }
            if (empty($errors['whatsapp_allowed_days'])) {
                if (empty($validDays)) {
                    $errors['whatsapp_allowed_days'] = 'At least one valid weekday must be selected.';
                } else {
                    // Retain unique canonical order
                    $orderedDays = array_values(array_intersect(self::ALLOWED_WEEKDAYS, array_unique($validDays)));
                    $sanitized['whatsapp_allowed_days'] = implode(',', $orderedDays);
                }
            }
        }

        // 3. Send Time (24-hour HH:MM format)
        $timeInput = trim((string)($input['whatsapp_send_time'] ?? ''));
        if (!preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/', $timeInput)) {
            $errors['whatsapp_send_time'] = 'Send time must be a valid 24-hour time between 00:00 and 23:59.';
        } else {
            $sanitized['whatsapp_send_time'] = $timeInput;
        }

        // 4. Timezone (Valid PHP timezone identifier)
        $tzInput = trim((string)($input['whatsapp_timezone'] ?? ''));
        if (empty($tzInput) || !in_array($tzInput, timezone_identifiers_list(), true)) {
            $errors['whatsapp_timezone'] = 'Invalid timezone identifier specified.';
        } else {
            $sanitized['whatsapp_timezone'] = $tzInput;
        }

        // 5. Batch Size (Positive bounded integer 1 to 500)
        $batchRaw = $input['whatsapp_batch_size'] ?? null;
        if (!is_numeric($batchRaw) || (int)$batchRaw < 1 || (int)$batchRaw > 500 || (string)(int)$batchRaw !== (string)$batchRaw) {
            $errors['whatsapp_batch_size'] = 'Batch size must be a positive integer between 1 and 500.';
        } else {
            $sanitized['whatsapp_batch_size'] = (string)(int)$batchRaw;
        }

        // 6. Notification Types (NEW_MATCH and DEADLINE_REMINDER toggles)
        $newMatchVal = $input['whatsapp_new_match_enabled'] ?? null;
        $sanitized['whatsapp_new_match_enabled'] = in_array($newMatchVal, [1, '1', true, 'true', 'on', 'yes'], true) ? '1' : '0';

        $deadlineVal = $input['whatsapp_deadline_reminder_enabled'] ?? null;
        $sanitized['whatsapp_deadline_reminder_enabled'] = in_array($deadlineVal, [1, '1', true, 'true', 'on', 'yes'], true) ? '1' : '0';

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'sanitized' => $sanitized
        ];
    }

    /**
     * Update settings in database.
     */
    public function updateSettings(array $input): array {
        $validation = $this->validateSettings($input);
        if (!$validation['valid']) {
            throw new InvalidArgumentException(implode(' ', $validation['errors']));
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                INSERT INTO settings (`key`, `value`, `type`, `group_name`, `is_public`) 
                VALUES (:key, :val, :type, 'notifications', 1)
                ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `updated_at` = NOW()
            ");

            foreach ($validation['sanitized'] as $k => $v) {
                $type = is_numeric($v) ? (strpos($k, 'batch') !== false ? 'integer' : 'boolean') : 'string';
                $stmt->execute([
                    'key' => $k,
                    'val' => $v,
                    'type' => $type
                ]);
            }

            $this->db->commit();
            return $validation['sanitized'];
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Evaluate if the notification schedule is due to run.
     */
    public function isScheduleDue(?DateTimeInterface $customTime = null, ?array $overrideSettings = null): array {
        $settings = $overrideSettings ?? $this->getSettings();

        $tzName = $settings['whatsapp_timezone'] ?? 'Asia/Karachi';
        try {
            $tz = new DateTimeZone($tzName);
        } catch (Exception $e) {
            $tz = new DateTimeZone('UTC');
        }

        $now = $customTime ? (clone $customTime)->setTimezone($tz) : new DateTime('now', $tz);

        // 1. Enabled Check
        if (!$settings['whatsapp_notifications_enabled']) {
            return [
                'due' => false,
                'reason' => 'notifications_disabled',
                'now' => $now,
                'current_weekday' => $now->format('l'),
                'current_time' => $now->format('H:i'),
                'settings' => $settings,
                'allowed_types' => []
            ];
        }

        // 2. Allowed Day Check
        $currentWeekday = $now->format('l');
        if (!in_array($currentWeekday, $settings['whatsapp_allowed_days'], true)) {
            return [
                'due' => false,
                'reason' => 'day_not_allowed',
                'now' => $now,
                'current_weekday' => $currentWeekday,
                'current_time' => $now->format('H:i'),
                'settings' => $settings,
                'allowed_types' => []
            ];
        }

        // 3. Send Time Window Check
        $configuredSendTime = $settings['whatsapp_send_time'];
        $currentTimeStr = $now->format('H:i');

        if ($currentTimeStr < $configuredSendTime) {
            return [
                'due' => false,
                'reason' => 'before_send_time',
                'now' => $now,
                'current_weekday' => $currentWeekday,
                'current_time' => $currentTimeStr,
                'settings' => $settings,
                'allowed_types' => []
            ];
        }

        // 4. Allowed Types
        $allowedTypes = [];
        if ($settings['whatsapp_new_match_enabled']) {
            $allowedTypes[] = NotificationTypes::NEW_MATCH;
        }
        if ($settings['whatsapp_deadline_reminder_enabled']) {
            $allowedTypes[] = NotificationTypes::DEADLINE_REMINDER;
            $allowedTypes[] = NotificationTypes::SCHOLARSHIP_DEADLINE_SOON;
            $allowedTypes[] = NotificationTypes::SCHOLARSHIP_DEADLINE_TODAY;
        }

        if (empty($allowedTypes)) {
            return [
                'due' => false,
                'reason' => 'no_notification_types_enabled',
                'now' => $now,
                'current_weekday' => $currentWeekday,
                'current_time' => $currentTimeStr,
                'settings' => $settings,
                'allowed_types' => []
            ];
        }

        return [
            'due' => true,
            'reason' => 'ready',
            'now' => $now,
            'current_weekday' => $currentWeekday,
            'current_time' => $currentTimeStr,
            'settings' => $settings,
            'allowed_types' => $allowedTypes
        ];
    }

    /**
     * Acquire a database advisory lock.
     */
    public function acquireLock(string $lockName = 'scholarship_notification_scheduler', int $timeoutSeconds = 0): bool {
        try {
            $stmt = $this->db->prepare("SELECT GET_LOCK(:name, :timeout)");
            $stmt->execute([
                'name' => $lockName,
                'timeout' => $timeoutSeconds
            ]);
            return (int)$stmt->fetchColumn() === 1;
        } catch (Exception $e) {
            Logger::error("Lock acquisition failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Release a database advisory lock.
     */
    public function releaseLock(string $lockName = 'scholarship_notification_scheduler'): bool {
        try {
            $stmt = $this->db->prepare("SELECT RELEASE_LOCK(:name)");
            $stmt->execute(['name' => $lockName]);
            return (int)$stmt->fetchColumn() === 1;
        } catch (Exception $e) {
            Logger::error("Lock release failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Recover stale notifications stuck in 'processing' state due to worker crash.
     */
    public function recoverStaleProcessing(int $timeoutMinutes = 15, int $maxAttempts = 3): array {
        // 1. Recover rows with attempts < maxAttempts back to pending
        $stmtRecover = $this->db->prepare("
            UPDATE notification_logs 
            SET status = 'pending',
                attempts = attempts + 1,
                processing_started_at = NULL,
                updated_at = NOW()
            WHERE status = 'processing'
              AND processing_started_at IS NOT NULL
              AND processing_started_at < DATE_SUB(NOW(), INTERVAL :mins MINUTE)
              AND attempts < :max_attempts
        ");
        $stmtRecover->execute([
            'mins' => $timeoutMinutes,
            'max_attempts' => $maxAttempts
        ]);
        $recoveredCount = $stmtRecover->rowCount();

        // 2. Mark rows with attempts >= maxAttempts as failed
        $stmtFail = $this->db->prepare("
            UPDATE notification_logs 
            SET status = 'failed',
                failed_at = NOW(),
                error_message = 'Exceeded maximum processing attempts (worker crash timeout)',
                updated_at = NOW()
            WHERE status = 'processing'
              AND processing_started_at IS NOT NULL
              AND processing_started_at < DATE_SUB(NOW(), INTERVAL :mins MINUTE)
              AND attempts >= :max_attempts
        ");
        $stmtFail->execute([
            'mins' => $timeoutMinutes,
            'max_attempts' => $maxAttempts
        ]);
        $failedCount = $stmtFail->rowCount();

        if ($recoveredCount > 0 || $failedCount > 0) {
            Logger::info("Stale processing recovery: {$recoveredCount} reset to pending, {$failedCount} marked failed.");
        }

        return [
            'recovered' => $recoveredCount,
            'failed' => $failedCount
        ];
    }

    /**
     * Atomically claim a batch of pending notifications for worker processing.
     */
    public function claimPendingBatch(int $batchSize, array $allowedTypes = [], string $channel = 'whatsapp'): array {
        if ($batchSize <= 0 || empty($allowedTypes)) {
            return [];
        }

        $claimToken = 'claim_' . bin2hex(random_bytes(16));
        $placeholders = implode(',', array_fill(0, count($allowedTypes), '?'));

        // Atomic lock & claim via UPDATE with unique claimToken
        $sqlClaim = "
            UPDATE notification_logs
            SET status = 'processing',
                processing_started_at = NOW(),
                provider_message_id = ?,
                updated_at = NOW()
            WHERE status = 'pending'
              AND channel = ?
              AND (available_at IS NULL OR available_at <= NOW())
              AND notification_type IN ({$placeholders})
            ORDER BY id ASC
            LIMIT {$batchSize}
        ";

        $params = array_merge([$claimToken, $channel], $allowedTypes);
        $stmtClaim = $this->db->prepare($sqlClaim);
        $stmtClaim->execute($params);

        $claimedCount = $stmtClaim->rowCount();
        if ($claimedCount === 0) {
            return [];
        }

        // Fetch claimed rows using claimToken
        $stmtFetch = $this->db->prepare("
            SELECT * FROM notification_logs 
            WHERE status = 'processing' 
              AND provider_message_id = :token
            ORDER BY id ASC
        ");
        $stmtFetch->execute(['token' => $claimToken]);
        return $stmtFetch->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Validate candidate before dispatch.
     */
    public function validateCandidate(array $row): array {
        $userId = (int)($row['user_id'] ?? 0);
        $schId = (int)($row['scholarship_id'] ?? 0);
        $type = $row['notification_type'] ?? '';

        // 1. User validation
        $stmtUser = $this->db->prepare("SELECT id, phone, whatsapp_phone, whatsapp_opt_in, status FROM users WHERE id = :id LIMIT 1");
        $stmtUser->execute(['id' => $userId]);
        $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

        if (!$user || $user['status'] !== 'active') {
            return ['valid' => false, 'reason' => 'User is not active or does not exist.'];
        }

        // 2. Paid Subscription check
        if (!SubscriptionService::can($userId, 'whatsapp_alerts')) {
            return ['valid' => false, 'reason' => 'User does not have an active paid subscription for WhatsApp alerts.'];
        }

        // 3. Opt-in check
        $notifService = new NotificationService();
        if (!$notifService->hasWhatsAppOptIn($userId, $type)) {
            return ['valid' => false, 'reason' => 'User does not have WhatsApp opt-in enabled for this notification type.'];
        }

        // 4. Phone number validation
        $recipient = $user['whatsapp_phone'] ?: $user['phone'];
        if (empty($recipient) || \App\Services\WhatsApp\WacrmWhatsAppProvider::normalizePhoneNumber($recipient) === null) {
            return ['valid' => false, 'reason' => 'User does not have a valid E.164 phone number.'];
        }

        // 5. Scholarship validity (if applicable)
        if ($schId > 0) {
            $stmtSch = $this->db->prepare("SELECT id, status, application_deadline FROM scholarships WHERE id = :id LIMIT 1");
            $stmtSch->execute(['id' => $schId]);
            $sch = $stmtSch->fetch(PDO::FETCH_ASSOC);

            if (!$sch || $sch['status'] !== 'published') {
                return ['valid' => false, 'reason' => 'Scholarship is not published or does not exist.'];
            }

            if ($sch['application_deadline'] !== null && strtotime($sch['application_deadline']) < strtotime(date('Y-m-d'))) {
                return ['valid' => false, 'reason' => 'Scholarship application deadline has passed.'];
            }
        }

        return ['valid' => true, 'reason' => 'Candidate is eligible.'];
    }

    /**
     * Run a dry-run check reporting what would be processed without modifying data.
     */
    public function runDryRun(): array {
        $schedule = $this->isScheduleDue();
        $settings = $schedule['settings'];
        $allowedTypes = $schedule['allowed_types'];

        $pendingStats = [];
        $sampleCandidates = [];

        if (!empty($allowedTypes)) {
            $placeholders = implode(',', array_fill(0, count($allowedTypes), '?'));
            $stmtCount = $this->db->prepare("
                SELECT notification_type, COUNT(*) as count 
                FROM notification_logs 
                WHERE status = 'pending' 
                  AND channel = 'whatsapp' 
                  AND (available_at IS NULL OR available_at <= NOW())
                  AND notification_type IN ({$placeholders})
                GROUP BY notification_type
            ");
            $stmtCount->execute($allowedTypes);
            $pendingStats = $stmtCount->fetchAll(PDO::FETCH_KEY_PAIR);

            // Fetch sample candidates
            $stmtSample = $this->db->prepare("
                SELECT id, user_id, scholarship_id, notification_type, recipient, created_at 
                FROM notification_logs 
                WHERE status = 'pending' 
                  AND channel = 'whatsapp' 
                  AND (available_at IS NULL OR available_at <= NOW())
                  AND notification_type IN ({$placeholders})
                ORDER BY id ASC
                LIMIT 10
            ");
            $stmtSample->execute($allowedTypes);
            $sampleCandidates = $stmtSample->fetchAll(PDO::FETCH_ASSOC);
        }

        return [
            'dry_run' => true,
            'schedule_due' => $schedule['due'],
            'schedule_reason' => $schedule['reason'],
            'current_time' => $schedule['current_time'],
            'current_weekday' => $schedule['current_weekday'],
            'timezone' => $schedule['now']->getTimezone()->getName(),
            'configured_send_time' => $settings['whatsapp_send_time'],
            'allowed_days' => $settings['whatsapp_allowed_days'],
            'batch_size' => $settings['whatsapp_batch_size'],
            'allowed_types' => $allowedTypes,
            'pending_breakdown' => $pendingStats,
            'total_pending' => array_sum($pendingStats),
            'sample_candidates' => $sampleCandidates
        ];
    }
}
