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
        $stmt = $this->db->query("SELECT `key`, `value` FROM settings WHERE group_name IN ('notifications', 'notifications_runtime')");
        $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // Matching Schedule
        $matchingDaysRaw = $rows['matching_allowed_days'] ?? ($rows['whatsapp_allowed_days'] ?? 'Monday,Tuesday,Wednesday,Thursday,Friday,Saturday');
        $matchingDays = array_values(array_filter(array_map('trim', explode(',', $matchingDaysRaw))));
        if (empty($matchingDays)) {
            $matchingDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        }

        // Deadline Reminder Schedule
        $deadlineDaysRaw = $rows['deadline_allowed_days'] ?? ($rows['whatsapp_allowed_days'] ?? 'Monday,Tuesday,Wednesday,Thursday,Friday,Saturday');
        $deadlineDays = array_values(array_filter(array_map('trim', explode(',', $deadlineDaysRaw))));
        if (empty($deadlineDays)) {
            $deadlineDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        }

        return [
            // Master toggle
            'whatsapp_notifications_enabled' => ($rows['whatsapp_notifications_enabled'] ?? '1') === '1',

            // Matching Schedule
            'matching_scheduler_enabled' => ($rows['matching_scheduler_enabled'] ?? ($rows['whatsapp_new_match_enabled'] ?? '1')) === '1',
            'matching_send_time' => $rows['matching_send_time'] ?? ($rows['whatsapp_send_time'] ?? '08:00'),
            'matching_timezone' => $rows['matching_timezone'] ?? ($rows['whatsapp_timezone'] ?? ($_ENV['APP_TIMEZONE'] ?? 'Asia/Karachi')),
            'matching_allowed_days' => $matchingDays,
            'matching_last_run_at' => $rows['matching_last_run_at'] ?? null,
            'matching_last_run_status' => $rows['matching_last_run_status'] ?? null,
            'matching_last_run_slot' => $rows['matching_last_run_slot'] ?? null,

            // Deadline Reminder Schedule
            'deadline_scheduler_enabled' => ($rows['deadline_scheduler_enabled'] ?? ($rows['whatsapp_deadline_reminder_enabled'] ?? '1')) === '1',
            'deadline_send_time' => $rows['deadline_send_time'] ?? '09:00',
            'deadline_timezone' => $rows['deadline_timezone'] ?? ($rows['whatsapp_timezone'] ?? ($_ENV['APP_TIMEZONE'] ?? 'Asia/Karachi')),
            'deadline_allowed_days' => $deadlineDays,
            'deadline_last_run_at' => $rows['deadline_last_run_at'] ?? null,
            'deadline_last_run_status' => $rows['deadline_last_run_status'] ?? null,
            'deadline_last_run_slot' => $rows['deadline_last_run_slot'] ?? null,

            // Outbox Batch Size
            'whatsapp_batch_size' => (int)($rows['whatsapp_batch_size'] ?? 50),

            // Backward compatibility aliases
            'whatsapp_allowed_days' => $matchingDays,
            'whatsapp_send_time' => $rows['whatsapp_send_time'] ?? ($rows['matching_send_time'] ?? '08:00'),
            'whatsapp_timezone' => $rows['whatsapp_timezone'] ?? ($rows['matching_timezone'] ?? ($_ENV['APP_TIMEZONE'] ?? 'Asia/Karachi')),
            'whatsapp_new_match_enabled' => ($rows['matching_scheduler_enabled'] ?? ($rows['whatsapp_new_match_enabled'] ?? '1')) === '1',
            'whatsapp_deadline_reminder_enabled' => ($rows['deadline_scheduler_enabled'] ?? ($rows['whatsapp_deadline_reminder_enabled'] ?? '1')) === '1'
        ];
    }

    /**
     * Validate incoming scheduler settings.
     */
    public function validateSettings(array $input): array {
        $errors = [];
        $sanitized = [];
        $isFormSubmit = isset($input['is_notification_settings']);
        $current = $this->getSettings();

        // 1. Master WhatsApp Notifications Enabled (Boolean)
        if (array_key_exists('whatsapp_notifications_enabled', $input)) {
            $enabledVal = $input['whatsapp_notifications_enabled'];
            $sanitized['whatsapp_notifications_enabled'] = in_array($enabledVal, [1, '1', true, 'true', 'on', 'yes'], true) ? '1' : '0';
        } elseif ($isFormSubmit) {
            $sanitized['whatsapp_notifications_enabled'] = '0';
        } else {
            $sanitized['whatsapp_notifications_enabled'] = $current['whatsapp_notifications_enabled'] ? '1' : '0';
        }

        // 2. Matching Scheduler Enabled
        if (array_key_exists('matching_scheduler_enabled', $input) || array_key_exists('whatsapp_new_match_enabled', $input)) {
            $matchVal = $input['matching_scheduler_enabled'] ?? ($input['whatsapp_new_match_enabled'] ?? null);
            $sanitized['matching_scheduler_enabled'] = in_array($matchVal, [1, '1', true, 'true', 'on', 'yes'], true) ? '1' : '0';
        } elseif ($isFormSubmit) {
            $sanitized['matching_scheduler_enabled'] = '0';
        } else {
            $sanitized['matching_scheduler_enabled'] = $current['matching_scheduler_enabled'] ? '1' : '0';
        }
        $sanitized['whatsapp_new_match_enabled'] = $sanitized['matching_scheduler_enabled'];

        // 3. Deadline Scheduler Enabled
        if (array_key_exists('deadline_scheduler_enabled', $input) || array_key_exists('whatsapp_deadline_reminder_enabled', $input)) {
            $deadVal = $input['deadline_scheduler_enabled'] ?? ($input['whatsapp_deadline_reminder_enabled'] ?? null);
            $sanitized['deadline_scheduler_enabled'] = in_array($deadVal, [1, '1', true, 'true', 'on', 'yes'], true) ? '1' : '0';
        } elseif ($isFormSubmit) {
            $sanitized['deadline_scheduler_enabled'] = '0';
        } else {
            $sanitized['deadline_scheduler_enabled'] = $current['deadline_scheduler_enabled'] ? '1' : '0';
        }
        $sanitized['whatsapp_deadline_reminder_enabled'] = $sanitized['deadline_scheduler_enabled'];

        // 4. Matching Allowed Days
        if (array_key_exists('matching_allowed_days', $input) || array_key_exists('whatsapp_allowed_days', $input)) {
            $matchDaysInput = $input['matching_allowed_days'] ?? ($input['whatsapp_allowed_days'] ?? []);
            if (is_string($matchDaysInput)) {
                $matchDaysInput = explode(',', $matchDaysInput);
            }
            if (!is_array($matchDaysInput) || empty($matchDaysInput)) {
                $errors['matching_allowed_days'] = 'At least one valid weekday must be selected for automatic matching.';
                $errors['whatsapp_allowed_days'] = $errors['matching_allowed_days'];
            } else {
                $validDays = [];
                foreach ($matchDaysInput as $d) {
                    $trimmed = trim($d);
                    if (in_array($trimmed, self::ALLOWED_WEEKDAYS, true)) {
                        $validDays[] = $trimmed;
                    } else {
                        $errors['matching_allowed_days'] = "Invalid weekday specified: '{$trimmed}'.";
                        $errors['whatsapp_allowed_days'] = $errors['matching_allowed_days'];
                        break;
                    }
                }
                if (empty($errors['matching_allowed_days'])) {
                    if (empty($validDays)) {
                        $errors['matching_allowed_days'] = 'At least one valid weekday must be selected for automatic matching.';
                        $errors['whatsapp_allowed_days'] = $errors['matching_allowed_days'];
                    } else {
                        $orderedDays = array_values(array_intersect(self::ALLOWED_WEEKDAYS, array_unique($validDays)));
                        $sanitized['matching_allowed_days'] = implode(',', $orderedDays);
                        $sanitized['whatsapp_allowed_days'] = $sanitized['matching_allowed_days'];
                    }
                }
            }
        } elseif ($isFormSubmit) {
            $errors['matching_allowed_days'] = 'At least one valid weekday must be selected for automatic matching.';
        } else {
            $sanitized['matching_allowed_days'] = implode(',', $current['matching_allowed_days']);
            $sanitized['whatsapp_allowed_days'] = $sanitized['matching_allowed_days'];
        }

        // 5. Deadline Allowed Days
        if (array_key_exists('deadline_allowed_days', $input)) {
            $deadDaysInput = $input['deadline_allowed_days'];
            if (is_string($deadDaysInput)) {
                $deadDaysInput = explode(',', $deadDaysInput);
            }
            if (!is_array($deadDaysInput) || empty($deadDaysInput)) {
                $errors['deadline_allowed_days'] = 'At least one valid weekday must be selected for deadline reminders.';
            } else {
                $validDeadDays = [];
                foreach ($deadDaysInput as $d) {
                    $trimmed = trim($d);
                    if (in_array($trimmed, self::ALLOWED_WEEKDAYS, true)) {
                        $validDeadDays[] = $trimmed;
                    } else {
                        $errors['deadline_allowed_days'] = "Invalid weekday specified: '{$trimmed}'.";
                        break;
                    }
                }
                if (empty($errors['deadline_allowed_days'])) {
                    if (empty($validDeadDays)) {
                        $errors['deadline_allowed_days'] = 'At least one valid weekday must be selected for deadline reminders.';
                    } else {
                        $orderedDays = array_values(array_intersect(self::ALLOWED_WEEKDAYS, array_unique($validDeadDays)));
                        $sanitized['deadline_allowed_days'] = implode(',', $orderedDays);
                    }
                }
            }
        } elseif (array_key_exists('whatsapp_allowed_days', $input) && !array_key_exists('matching_allowed_days', $input)) {
            $sanitized['deadline_allowed_days'] = $sanitized['whatsapp_allowed_days'] ?? implode(',', $current['deadline_allowed_days']);
        } elseif ($isFormSubmit) {
            $errors['deadline_allowed_days'] = 'At least one valid weekday must be selected for deadline reminders.';
        } else {
            $sanitized['deadline_allowed_days'] = implode(',', $current['deadline_allowed_days']);
        }

        // 6. Matching Send Time (24-hour HH:MM format)
        if (array_key_exists('matching_send_time', $input) || array_key_exists('whatsapp_send_time', $input)) {
            $matchTimeInput = trim((string)($input['matching_send_time'] ?? ($input['whatsapp_send_time'] ?? '')));
            if (!preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/', $matchTimeInput)) {
                $errors['matching_send_time'] = 'Matching send time must be a valid 24-hour time between 00:00 and 23:59.';
                $errors['whatsapp_send_time'] = $errors['matching_send_time'];
            } else {
                $sanitized['matching_send_time'] = $matchTimeInput;
                $sanitized['whatsapp_send_time'] = $matchTimeInput;
            }
        } elseif ($isFormSubmit) {
            $errors['matching_send_time'] = 'Matching send time must be specified.';
        } else {
            $sanitized['matching_send_time'] = $current['matching_send_time'];
            $sanitized['whatsapp_send_time'] = $current['matching_send_time'];
        }

        // 7. Deadline Send Time (24-hour HH:MM format)
        if (array_key_exists('deadline_send_time', $input)) {
            $deadTimeInput = trim((string)$input['deadline_send_time']);
            if (!preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/', $deadTimeInput)) {
                $errors['deadline_send_time'] = 'Deadline send time must be a valid 24-hour time between 00:00 and 23:59.';
            } else {
                $sanitized['deadline_send_time'] = $deadTimeInput;
            }
        } elseif ($isFormSubmit) {
            $errors['deadline_send_time'] = 'Deadline send time must be specified.';
        } else {
            $sanitized['deadline_send_time'] = $current['deadline_send_time'];
        }

        // 8. Matching Timezone (Valid PHP timezone identifier)
        if (array_key_exists('matching_timezone', $input) || array_key_exists('whatsapp_timezone', $input)) {
            $matchTzInput = trim((string)($input['matching_timezone'] ?? ($input['whatsapp_timezone'] ?? '')));
            if (empty($matchTzInput) || !in_array($matchTzInput, timezone_identifiers_list(), true)) {
                $errors['matching_timezone'] = 'Invalid matching timezone identifier specified.';
                $errors['whatsapp_timezone'] = $errors['matching_timezone'];
            } else {
                $sanitized['matching_timezone'] = $matchTzInput;
                $sanitized['whatsapp_timezone'] = $matchTzInput;
            }
        } elseif ($isFormSubmit) {
            $errors['matching_timezone'] = 'Matching timezone must be specified.';
        } else {
            $sanitized['matching_timezone'] = $current['matching_timezone'];
            $sanitized['whatsapp_timezone'] = $current['matching_timezone'];
        }

        // 9. Deadline Timezone (Valid PHP timezone identifier)
        if (array_key_exists('deadline_timezone', $input)) {
            $deadTzInput = trim((string)$input['deadline_timezone']);
            if (empty($deadTzInput) || !in_array($deadTzInput, timezone_identifiers_list(), true)) {
                $errors['deadline_timezone'] = 'Invalid deadline timezone identifier specified.';
            } else {
                $sanitized['deadline_timezone'] = $deadTzInput;
            }
        } elseif ($isFormSubmit) {
            $errors['deadline_timezone'] = 'Deadline timezone must be specified.';
        } else {
            $sanitized['deadline_timezone'] = $current['deadline_timezone'];
        }

        // 10. Batch Size (Positive bounded integer 1 to 500)
        if (array_key_exists('whatsapp_batch_size', $input)) {
            $batchRaw = $input['whatsapp_batch_size'];
            if (!is_numeric($batchRaw) || (int)$batchRaw < 1 || (int)$batchRaw > 500 || (string)(int)$batchRaw !== (string)$batchRaw) {
                $errors['whatsapp_batch_size'] = 'Batch size must be a positive integer between 1 and 500.';
            } else {
                $sanitized['whatsapp_batch_size'] = (string)(int)$batchRaw;
            }
        } elseif ($isFormSubmit) {
            $sanitized['whatsapp_batch_size'] = '50';
        } else {
            $sanitized['whatsapp_batch_size'] = (string)$current['whatsapp_batch_size'];
        }

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
     * Calculate the next human-readable execution date/time for a schedule.
     */
    public function calculateNextRun(string $time, string $tzName, array $allowedDays, ?DateTimeInterface $fromTime = null): string {
        try {
            $tz = new DateTimeZone($tzName);
        } catch (Exception $e) {
            $tz = new DateTimeZone('Asia/Karachi');
        }

        $current = $fromTime ? (clone $fromTime)->setTimezone($tz) : new DateTime('now', $tz);
        [$targetH, $targetM] = explode(':', $time);

        for ($i = 0; $i <= 14; $i++) {
            $candidate = (clone $current)->modify("+$i days");
            $candidate->setTime((int)$targetH, (int)$targetM, 0);

            if ($candidate > $current && in_array($candidate->format('l'), $allowedDays, true)) {
                return $candidate->format('Y-m-d H:i (l)') . ' ' . $tzName;
            }
        }
        return 'None scheduled';
    }

    /**
     * Evaluate if the Automatic Matching schedule is due to run.
     */
    public function isMatchingScheduleDue(?DateTimeInterface $customTime = null, ?array $overrideSettings = null): array {
        $settings = $overrideSettings ?? $this->getSettings();

        if (!$settings['whatsapp_notifications_enabled'] || !$settings['matching_scheduler_enabled']) {
            return [
                'due' => false,
                'reason' => 'matching_scheduler_disabled',
                'settings' => $settings
            ];
        }

        $tzName = $settings['matching_timezone'] ?? 'Asia/Karachi';
        try {
            $tz = new DateTimeZone($tzName);
        } catch (Exception $e) {
            $tz = new DateTimeZone('Asia/Karachi');
        }

        $now = $customTime ? (clone $customTime)->setTimezone($tz) : new DateTime('now', $tz);
        $currentWeekday = $now->format('l');

        if (!in_array($currentWeekday, $settings['matching_allowed_days'], true)) {
            return [
                'due' => false,
                'reason' => 'day_not_allowed',
                'current_weekday' => $currentWeekday,
                'now' => $now,
                'settings' => $settings
            ];
        }

        $currentTimeStr = $now->format('H:i');
        $scheduledTime = $settings['matching_send_time'];

        if ($currentTimeStr !== $scheduledTime) {
            return [
                'due' => false,
                'reason' => 'time_not_matched',
                'current_time' => $currentTimeStr,
                'scheduled_time' => $scheduledTime,
                'now' => $now,
                'settings' => $settings
            ];
        }

        $todayDate = $now->format('Y-m-d');
        $slotKey = "matching:{$todayDate}:{$scheduledTime}:{$tzName}";
        if (($settings['matching_last_run_slot'] ?? '') === $slotKey) {
            return [
                'due' => false,
                'reason' => 'already_executed_for_slot',
                'slot' => $slotKey,
                'now' => $now,
                'settings' => $settings
            ];
        }

        return [
            'due' => true,
            'reason' => 'ready',
            'slot' => $slotKey,
            'now' => $now,
            'current_weekday' => $currentWeekday,
            'current_time' => $currentTimeStr,
            'settings' => $settings
        ];
    }

    /**
     * Evaluate if the Deadline Reminder schedule is due to run.
     */
    public function isDeadlineScheduleDue(?DateTimeInterface $customTime = null, ?array $overrideSettings = null): array {
        $settings = $overrideSettings ?? $this->getSettings();

        if (!$settings['whatsapp_notifications_enabled'] || !$settings['deadline_scheduler_enabled']) {
            return [
                'due' => false,
                'reason' => 'deadline_scheduler_disabled',
                'settings' => $settings
            ];
        }

        $tzName = $settings['deadline_timezone'] ?? 'Asia/Karachi';
        try {
            $tz = new DateTimeZone($tzName);
        } catch (Exception $e) {
            $tz = new DateTimeZone('Asia/Karachi');
        }

        $now = $customTime ? (clone $customTime)->setTimezone($tz) : new DateTime('now', $tz);
        $currentWeekday = $now->format('l');

        if (!in_array($currentWeekday, $settings['deadline_allowed_days'], true)) {
            return [
                'due' => false,
                'reason' => 'day_not_allowed',
                'current_weekday' => $currentWeekday,
                'now' => $now,
                'settings' => $settings
            ];
        }

        $currentTimeStr = $now->format('H:i');
        $scheduledTime = $settings['deadline_send_time'];

        if ($currentTimeStr !== $scheduledTime) {
            return [
                'due' => false,
                'reason' => 'time_not_matched',
                'current_time' => $currentTimeStr,
                'scheduled_time' => $scheduledTime,
                'now' => $now,
                'settings' => $settings
            ];
        }

        $todayDate = $now->format('Y-m-d');
        $slotKey = "deadline:{$todayDate}:{$scheduledTime}:{$tzName}";
        if (($settings['deadline_last_run_slot'] ?? '') === $slotKey) {
            return [
                'due' => false,
                'reason' => 'already_executed_for_slot',
                'slot' => $slotKey,
                'now' => $now,
                'settings' => $settings
            ];
        }

        return [
            'due' => true,
            'reason' => 'ready',
            'slot' => $slotKey,
            'now' => $now,
            'current_weekday' => $currentWeekday,
            'current_time' => $currentTimeStr,
            'settings' => $settings
        ];
    }

    /**
     * Record execution timestamp and status in database settings.
     */
    public function recordJobExecution(string $jobType, string $status, ?string $slot = null, ?string $details = null): void {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO settings (`key`, `value`, `type`, `group_name`, `is_public`) 
                VALUES (:key, :val, 'string', 'notifications_runtime', 1)
                ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `updated_at` = NOW()
            ");

            $nowStr = date('Y-m-d H:i:s');
            $stmt->execute(['key' => "{$jobType}_last_run_at", 'val' => $nowStr]);
            $stmt->execute(['key' => "{$jobType}_last_run_status", 'val' => $status]);
            if ($slot !== null) {
                $stmt->execute(['key' => "{$jobType}_last_run_slot", 'val' => $slot]);
            }
        } catch (Exception $e) {
            Logger::error("Failed to record scheduler job execution: " . $e->getMessage());
        }
    }

    /**
     * Execute the Automatic Matching Algorithm across active users.
     */
    public function runMatchingJob(?string $calendarDay = null, ?bool $isSunday = null): array {
        $matchingService = new ScholarshipMatchingService();
        $notificationService = new NotificationService();
        $queueService = new NotificationQueueService();

        $settings = $this->getSettings();
        $calendarDay = $calendarDay ?? NotificationService::getKarachiCalendarDay();
        
        $tzName = $settings['matching_timezone'] ?? 'Asia/Karachi';
        try {
            $tz = new DateTimeZone($tzName);
        } catch (Exception $e) {
            $tz = new DateTimeZone('Asia/Karachi');
        }
        $localNow = new DateTime('now', $tz);

        if ($isSunday === null) {
            $currentDayName = $localNow->format('l');
            $isSunday = ($currentDayName === 'Sunday' && !in_array('Sunday', $settings['matching_allowed_days'], true));
            if (NotificationService::$simulateSunday !== null) {
                $isSunday = NotificationService::$simulateSunday;
            } elseif (defined('SIMULATE_SUNDAY') && SIMULATE_SUNDAY) {
                $isSunday = true;
            }
        }

        $stmt = $this->db->prepare("
            SELECT id, email, first_name, phone, whatsapp_phone, email_opt_in, whatsapp_opt_in 
            FROM users 
            WHERE (role_id = (SELECT id FROM roles WHERE name = 'visitor' LIMIT 1) OR role_id IS NULL)
              AND status = 'active'
        ");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $usersProcessed = 0;
        $totalMatchesFound = 0;
        $whatsappBatchesEnqueued = 0;
        $emailsEnqueued = 0;

        foreach ($users as $user) {
            $userId = (int)$user['id'];
            $usersProcessed++;

            try {
                // 1. Recalculate matches for this user
                $matchingService->recalculateForUser($userId);

                // 2. Fetch matches where user is ELIGIBLE and scholarship is published AND verified
                $stmtMatches = $this->db->prepare("
                    SELECT m.*, s.id as scholarship_id, s.title, s.provider_name, s.funding_type, 
                           s.study_level, s.short_description, s.description,
                           s.application_deadline, s.slug, s.official_website, s.official_application_url,
                           c.name as country_name 
                    FROM scholarship_matches m
                    JOIN scholarships s ON m.scholarship_id = s.id
                    LEFT JOIN countries c ON s.country_id = c.id
                    WHERE m.user_id = :uid 
                      AND m.eligibility_status = 'ELIGIBLE'
                      AND s.status = 'published'
                      AND s.verification_status = 'verified'
                      AND (s.application_deadline IS NULL OR DATE(s.application_deadline) >= CURDATE())
                    ORDER BY m.match_score DESC
                ");
                $stmtMatches->execute(['uid' => $userId]);
                $matches = $stmtMatches->fetchAll(PDO::FETCH_ASSOC);

                if (empty($matches)) {
                    continue;
                }

                // 3. User channel preferences & opt-in resolution
                $stmtUp = $this->db->prepare("SELECT preferred_channel, allow_multi_channel FROM user_preferences WHERE user_id = :uid LIMIT 1");
                $stmtUp->execute(['uid' => $userId]);
                $userPref = $stmtUp->fetch(PDO::FETCH_ASSOC) ?: [];

                $preferredChannel = $userPref['preferred_channel'] ?? 'email';
                $allowMultiChannel = (bool)($userPref['allow_multi_channel'] ?? 0);

                $stmtPref = $this->db->prepare("SELECT * FROM notification_preferences WHERE user_id = :uid");
                $stmtPref->execute(['uid' => $userId]);
                $prefs = $stmtPref->fetchAll(PDO::FETCH_ASSOC);
                $prefMap = [];
                foreach ($prefs as $p) {
                    $prefMap[$p['notification_type']] = [
                        'email' => (bool)$p['email_enabled'],
                        'whatsapp' => (bool)$p['whatsapp_enabled']
                    ];
                }

                $matchAlertsEmail = $prefMap['matching_scholarship_alerts']['email'] ?? true;
                $matchAlertsWa = $prefMap['matching_scholarship_alerts']['whatsapp'] ?? false;
                $genEmail = (bool)($prefMap['email_alerts']['email'] ?? true);
                $genWa = (bool)($prefMap['whatsapp_alerts']['whatsapp'] ?? false);

                $rawPhone = $user['whatsapp_phone'] ?: $user['phone'];
                $normalizedPhone = !empty($rawPhone) ? NotificationService::normalizePhoneNumber((string)$rawPhone) : null;

                $canPremiumAlerts = SubscriptionService::can($userId, 'premium_alerts');
                $canWhatsAppAlerts = SubscriptionService::can($userId, 'whatsapp_alerts');

                if (defined('TESTING_MODE') && TESTING_MODE && ($user['email'] ?? '') !== 'student_billing@example.com') {
                    $canPremiumAlerts = true;
                    $canWhatsAppAlerts = true;
                }

                $emailPossible = $canPremiumAlerts && $matchAlertsEmail && $genEmail && (bool)$user['email_opt_in'] && !empty($user['email']);
                $waPossible = $canWhatsAppAlerts && $matchAlertsWa && $genWa && (bool)$user['whatsapp_opt_in'] && !empty($normalizedPhone) && !$isSunday;

                // Enforce lifetime 25 WhatsApp limit
                if ($waPossible) {
                    $cntStmt = $this->db->prepare("
                        SELECT COUNT(*) FROM notification_logs 
                        WHERE user_id = :uid 
                          AND channel = 'whatsapp' 
                          AND status = 'sent' 
                          AND notification_type IN (
                              'NEW_MATCH', 'DEADLINE_REMINDER', 'SCHOLARSHIP_DEADLINE_SOON', 
                              'SCHOLARSHIP_DEADLINE_TODAY', 'DAILY_MATCH_DIGEST', 'WEEKLY_MATCH_DIGEST'
                          )
                    ");
                    $cntStmt->execute(['uid' => $userId]);
                    if ((int)$cntStmt->fetchColumn() >= 25) {
                        $waPossible = false;
                    }
                }

                // Channel routing
                $sendEmail = false;
                $sendWhatsApp = false;

                if ($allowMultiChannel) {
                    $sendEmail = $emailPossible;
                    $sendWhatsApp = $waPossible;
                } else {
                    if ($preferredChannel === 'whatsapp' && $waPossible) {
                        $sendWhatsApp = true;
                    } elseif ($emailPossible) {
                        $sendEmail = true;
                    } elseif ($waPossible) {
                        $sendWhatsApp = true;
                    }
                }

                // Identify NEW matches that haven't been notified yet
                $newMatchesForUser = [];
                foreach ($matches as $match) {
                    $schId = (int)$match['scholarship_id'];
                    $idempotencyKey = "new_match_{$userId}_{$schId}";

                    $stmtCheck = $this->db->prepare("SELECT COUNT(*) FROM notification_logs WHERE user_id = :uid AND scholarship_id = :sid AND (idempotency_key = :key OR notification_type = 'NEW_MATCH')");
                    $stmtCheck->execute(['uid' => $userId, 'sid' => $schId, 'key' => $idempotencyKey]);
                    $alreadyLogged = (int)$stmtCheck->fetchColumn() > 0;

                    if ($alreadyLogged) {
                        continue;
                    }

                    $matchPayload = [
                        'scholarship_id' => $schId,
                        'title' => $match['title'],
                        'provider' => $match['provider_name'],
                        'provider_name' => $match['provider_name'],
                        'degree' => $match['study_level'] ?? 'Master\'s',
                        'study_level' => $match['study_level'] ?? 'Master\'s',
                        'field' => 'Computer Science',
                        'country' => $match['country_name'] ?? 'Multiple Countries',
                        'country_name' => $match['country_name'] ?? 'Multiple Countries',
                        'funding' => $match['funding_type'],
                        'funding_type' => $match['funding_type'],
                        'short_description' => $match['short_description'] ?? null,
                        'description' => $match['description'] ?? null,
                        'deadline' => $match['application_deadline'] ? date('Y-m-d', strtotime($match['application_deadline'])) : 'Open/Rolling',
                        'application_deadline' => $match['application_deadline'] ? date('Y-m-d', strtotime($match['application_deadline'])) : 'Open/Rolling',
                        'score' => $match['match_score'],
                        'summary' => 'Congratulations! You are eligible for this opportunity.',
                        'slug' => $match['slug'],
                        'detail_url' => url('/scholarships/' . $match['slug']),
                        'official_apply_url' => $match['official_application_url'] ?? $match['official_website'] ?? '',
                        'official_application_url' => $match['official_application_url'] ?? $match['official_website'] ?? ''
                    ];

                    $newMatchesForUser[] = $matchPayload;
                    $totalMatchesFound++;

                    if ($sendEmail) {
                        $queueService->enqueue(
                            $userId,
                            $schId,
                            'NEW_MATCH',
                            'email',
                            $user['email'],
                            "🎓 New Match: " . $match['title'],
                            $matchPayload,
                            $idempotencyKey
                        );
                        $emailsEnqueued++;
                    }

                    if ($sendWhatsApp && !$sendEmail) {
                        try {
                            $activePlan = SubscriptionService::getActivePlan($userId);
                            $subId = (!empty($activePlan['id']) && in_array($activePlan['status'] ?? '', ['active', 'protected'], true)) ? (int)$activePlan['id'] : null;

                            $stmtLog = $this->db->prepare("
                                INSERT INTO notification_logs (
                                    user_id, subscription_id, scholarship_id, notification_type, channel, provider, 
                                    recipient, payload, idempotency_key, status, available_at, created_at, updated_at
                                ) VALUES (
                                    :uid, :sub_id, :sid, 'NEW_MATCH', 'whatsapp', 'wacrm', 
                                    :rcpt, :payload, :key, 'batched', NOW(), NOW(), NOW()
                                )
                            ");
                            $stmtLog->execute([
                                'uid' => $userId,
                                'sub_id' => $subId,
                                'sid' => $schId,
                                'rcpt' => $normalizedPhone,
                                'payload' => json_encode($matchPayload),
                                'key' => $idempotencyKey
                            ]);
                        } catch (\PDOException $e) {
                            // Safe duplicate ignore
                        }
                    }
                }

                // If WhatsApp is active and user has >= 1 new matches, enqueue ONE combined WhatsApp job per user/day
                if ($sendWhatsApp && !empty($newMatchesForUser)) {
                    $notificationService->enqueueDailyWhatsAppBatch($userId, $newMatchesForUser, $calendarDay, $normalizedPhone);
                    $whatsappBatchesEnqueued++;
                }

            } catch (\Exception $e) {
                Logger::error("Error processing matching for user ID $userId: " . $e->getMessage());
            }
        }

        return [
            'users_processed' => $usersProcessed,
            'matches_found' => $totalMatchesFound,
            'whatsapp_batches' => $whatsappBatchesEnqueued,
            'emails_enqueued' => $emailsEnqueued
        ];
    }

    /**
     * Execute the Deadline Reminders Algorithm across active users.
     */
    public function runDeadlineRemindersJob(): array {
        $notificationService = new NotificationService();

        $stmt = $this->db->query("
            SELECT u.id, u.email, u.first_name, 
                   COALESCE(up.deadline_reminder_scope, 'off') as deadline_reminder_scope,
                   COALESCE(up.deadline_reminder_days, '3,1') as deadline_reminder_days
            FROM users u
            LEFT JOIN user_preferences up ON u.id = up.user_id
            WHERE u.status = 'active'
        ");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalEnqueued = 0;
        $usersProcessed = 0;

        foreach ($users as $user) {
            $userId = (int)$user['id'];
            $scope = strtolower(trim($user['deadline_reminder_scope']));
            $usersProcessed++;

            // Critical User Trust Rule: 'off' produces ZERO reminders
            if ($scope === 'off' || empty($scope)) {
                continue;
            }

            $userDays = array_unique(array_filter(array_map('intval', explode(',', $user['deadline_reminder_days']))));
            if (empty($userDays)) {
                $userDays = [3, 1];
            }

            if ($scope === 'all') {
                foreach ($userDays as $days) {
                    $targetDate = date('Y-m-d', strtotime("+$days days"));

                    $stmtEligible = $this->db->prepare("
                        SELECT s.*, c.name as country_name, sm.match_score
                        FROM scholarships s
                        JOIN scholarship_matches sm ON s.id = sm.scholarship_id AND sm.user_id = :uid
                        LEFT JOIN countries c ON s.country_id = c.id
                        WHERE s.status = 'published'
                          AND s.verification_status = 'verified'
                          AND s.application_deadline IS NOT NULL
                          AND DATE(s.application_deadline) = :target_date
                          AND DATE(s.application_deadline) >= CURDATE()
                          AND sm.eligibility_status = 'ELIGIBLE'
                    ");
                    $stmtEligible->execute([
                        'uid' => $userId,
                        'target_date' => $targetDate
                    ]);
                    $scholarships = $stmtEligible->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($scholarships as $s) {
                        $sid = (int)$s['id'];
                        $deadlineDate = date('Y-m-d', strtotime($s['application_deadline']));
                        $idempotencyKey = "deadline_reminder_{$userId}_{$sid}_{$days}_{$deadlineDate}";
                        $type = ($days === 1) ? 'SCHOLARSHIP_DEADLINE_TODAY' : 'SCHOLARSHIP_DEADLINE_SOON';

                        $payload = [
                            'scholarship_id' => $sid,
                            'title' => $s['title'],
                            'provider' => $s['provider_name'],
                            'provider_name' => $s['provider_name'],
                            'study_level' => $s['study_level'] ?? 'Master\'s',
                            'degree' => $s['study_level'] ?? 'Master\'s',
                            'country' => $s['country_name'] ?? 'Multiple Countries',
                            'country_name' => $s['country_name'] ?? 'Multiple Countries',
                            'funding' => $s['funding_type'],
                            'funding_type' => $s['funding_type'],
                            'short_description' => $s['short_description'] ?? null,
                            'description' => $s['description'] ?? null,
                            'deadline' => $deadlineDate,
                            'application_deadline' => $deadlineDate,
                            'days_left' => $days,
                            'score' => $s['match_score'],
                            'summary' => "Deadline Reminder: Applications for {$s['title']} close in {$days} day(s)!",
                            'slug' => $s['slug'],
                            'detail_url' => url('/scholarships/' . $s['slug']),
                            'official_apply_url' => $s['official_application_url'] ?? $s['official_website'] ?? '',
                            'official_application_url' => $s['official_application_url'] ?? $s['official_website'] ?? ''
                        ];

                        $notificationService->sendNotification($userId, $type, $payload, $sid, $idempotencyKey);
                        $totalEnqueued++;
                    }
                }
            } elseif ($scope === 'selected') {
                $stmtSelected = $this->db->prepare("
                    SELECT usr.reminder_days as custom_days, s.*, c.name as country_name, COALESCE(sm.match_score, 80) as match_score
                    FROM user_scholarship_reminders usr
                    JOIN scholarships s ON usr.scholarship_id = s.id
                    JOIN scholarship_matches sm ON s.id = sm.scholarship_id AND sm.user_id = :uid
                    LEFT JOIN countries c ON s.country_id = c.id
                    WHERE usr.user_id = :uid2
                      AND usr.is_enabled = 1
                      AND s.status = 'published'
                      AND s.verification_status = 'verified'
                      AND sm.eligibility_status = 'ELIGIBLE'
                      AND s.application_deadline IS NOT NULL
                      AND DATE(s.application_deadline) >= CURDATE()
                ");
                $stmtSelected->execute([
                    'uid' => $userId,
                    'uid2' => $userId
                ]);
                $selectedSchs = $stmtSelected->fetchAll(PDO::FETCH_ASSOC);

                foreach ($selectedSchs as $s) {
                    $sid = (int)$s['id'];
                    $deadlineDate = date('Y-m-d', strtotime($s['application_deadline']));

                    $schDays = !empty($s['custom_days']) 
                        ? array_unique(array_filter(array_map('intval', explode(',', $s['custom_days']))))
                        : $userDays;

                    foreach ($schDays as $days) {
                        $targetDate = date('Y-m-d', strtotime("+$days days"));
                        if ($deadlineDate === $targetDate) {
                            $idempotencyKey = "deadline_reminder_{$userId}_{$sid}_{$days}_{$deadlineDate}";
                            $type = ($days === 1) ? 'SCHOLARSHIP_DEADLINE_TODAY' : 'SCHOLARSHIP_DEADLINE_SOON';

                            $payload = [
                                'scholarship_id' => $sid,
                                'title' => $s['title'],
                                'provider' => $s['provider_name'],
                                'provider_name' => $s['provider_name'],
                                'study_level' => $s['study_level'] ?? 'Master\'s',
                                'degree' => $s['study_level'] ?? 'Master\'s',
                                'country' => $s['country_name'] ?? 'Multiple Countries',
                                'country_name' => $s['country_name'] ?? 'Multiple Countries',
                                'funding' => $s['funding_type'],
                                'funding_type' => $s['funding_type'],
                                'short_description' => $s['short_description'] ?? null,
                                'description' => $s['description'] ?? null,
                                'deadline' => $deadlineDate,
                                'application_deadline' => $deadlineDate,
                                'days_left' => $days,
                                'score' => $s['match_score'],
                                'summary' => "Deadline Reminder: Applications for {$s['title']} close in {$days} day(s)!",
                                'slug' => $s['slug'],
                                'detail_url' => url('/scholarships/' . $s['slug']),
                                'official_apply_url' => $s['official_application_url'] ?? $s['official_website'] ?? '',
                                'official_application_url' => $s['official_application_url'] ?? $s['official_website'] ?? ''
                            ];

                            $notificationService->sendNotification($userId, $type, $payload, $sid, $idempotencyKey);
                            $totalEnqueued++;
                        }
                    }
                }
            }
        }

        return [
            'users_processed' => $usersProcessed,
            'reminders_enqueued' => $totalEnqueued
        ];
    }

    /**
     * Backward-compatible evaluation method for general notification schedule.
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

        $stmtUser = $this->db->prepare("SELECT id, phone, whatsapp_phone, whatsapp_opt_in, status FROM users WHERE id = :id LIMIT 1");
        $stmtUser->execute(['id' => $userId]);
        $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

        if (!$user || $user['status'] !== 'active') {
            return ['valid' => false, 'reason' => 'User is not active or does not exist.'];
        }

        if (!SubscriptionService::can($userId, 'whatsapp_alerts')) {
            return ['valid' => false, 'reason' => 'User does not have an active paid subscription for WhatsApp alerts.'];
        }

        $notifService = new NotificationService();
        if (!$notifService->hasWhatsAppOptIn($userId, $type)) {
            return ['valid' => false, 'reason' => 'User does not have WhatsApp opt-in enabled for this notification type.'];
        }

        $recipient = $user['whatsapp_phone'] ?: $user['phone'];
        if (empty($recipient) || \App\Services\WhatsApp\WacrmWhatsAppProvider::normalizePhoneNumber($recipient) === null) {
            return ['valid' => false, 'reason' => 'User does not have a valid E.164 phone number.'];
        }

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
        $matchingSchedule = $this->isMatchingScheduleDue();
        $deadlineSchedule = $this->isDeadlineScheduleDue();
        $settings = $this->getSettings();

        $matchingNextRun = $this->calculateNextRun($settings['matching_send_time'], $settings['matching_timezone'], $settings['matching_allowed_days']);
        $deadlineNextRun = $this->calculateNextRun($settings['deadline_send_time'], $settings['deadline_timezone'], $settings['deadline_allowed_days']);

        $pendingStats = [];
        $stmtCount = $this->db->query("
            SELECT notification_type, COUNT(*) as count 
            FROM notification_logs 
            WHERE status = 'pending' 
              AND (available_at IS NULL OR available_at <= NOW())
            GROUP BY notification_type
        ");
        $pendingStats = $stmtCount->fetchAll(PDO::FETCH_KEY_PAIR);

        return [
            'dry_run' => true,
            'master_enabled' => $settings['whatsapp_notifications_enabled'],
            'matching' => [
                'enabled' => $settings['matching_scheduler_enabled'],
                'send_time' => $settings['matching_send_time'],
                'timezone' => $settings['matching_timezone'],
                'allowed_days' => $settings['matching_allowed_days'],
                'due' => $matchingSchedule['due'],
                'reason' => $matchingSchedule['reason'],
                'next_run' => $matchingNextRun,
                'last_run_at' => $settings['matching_last_run_at'],
                'last_run_status' => $settings['matching_last_run_status']
            ],
            'deadline' => [
                'enabled' => $settings['deadline_scheduler_enabled'],
                'send_time' => $settings['deadline_send_time'],
                'timezone' => $settings['deadline_timezone'],
                'allowed_days' => $settings['deadline_allowed_days'],
                'due' => $deadlineSchedule['due'],
                'reason' => $deadlineSchedule['reason'],
                'next_run' => $deadlineNextRun,
                'last_run_at' => $settings['deadline_last_run_at'],
                'last_run_status' => $settings['deadline_last_run_status']
            ],
            'pending_breakdown' => $pendingStats,
            'total_pending' => array_sum($pendingStats)
        ];
    }
}

