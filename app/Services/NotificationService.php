<?php

namespace App\Services;

use App\Services\Database;
use PDO;

class NotificationService {
    private PDO $db;
    private NotificationQueueService $queueService;
    public static ?bool $simulateSunday = null;
    public static ?bool $simulateCutoffPassed = null;

    public const TIMEZONE = 'Asia/Karachi';

    public static function getKarachiDateTime(?string $time = null): \DateTime {
        return new \DateTime($time ?? 'now', new \DateTimeZone(self::TIMEZONE));
    }

    public static function getKarachiCalendarDay(): string {
        return self::getKarachiDateTime()->format('Y-m-d');
    }

    public static function getCutoffDateTime(string $calendarDay): \DateTime {
        return new \DateTime("{$calendarDay} 23:59:59", new \DateTimeZone(self::TIMEZONE));
    }

    public static function isPastCutoff(string $calendarDay, ?\DateTime $now = null): bool {
        if (self::$simulateCutoffPassed !== null) {
            return self::$simulateCutoffPassed;
        }
        $now = $now ?? self::getKarachiDateTime();
        $cutoff = self::getCutoffDateTime($calendarDay);
        return $now > $cutoff;
    }

    public function __construct() {
        $this->db = Database::connection();
        $this->queueService = new NotificationQueueService();
    }

    /**
     * Normalize phone number via active WhatsApp provider format.
     */
    public static function normalizePhoneNumber(?string $phone): ?string {
        if ($phone === null || trim($phone) === '') {
            return null;
        }
        return \App\Services\WhatsApp\WacrmWhatsAppProvider::normalizePhoneNumber($phone);
    }

    /**
     * Trigger a new notification alert checking constraints and opt-outs.
     */
    public function sendNotification(
        int $userId,
        string $type,
        array $payloadData,
        ?int $scholarshipId = null,
        ?string $idempotencyKey = null,
        ?string $availableAt = null
    ): void {
        // 1. Fetch user contact details and main opt-in tags
        $stmt = $this->db->prepare("SELECT email, phone, whatsapp_phone, email_opt_in, whatsapp_opt_in FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            \App\Services\Logger::warning("Attempted to send notification to non-existent user ID: $userId");
            return;
        }

        // 2. Fetch user notification preferences
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

        // 3. Map type to preference trigger toggles
        $prefKey = null;
        if ($type === 'NEW_MATCH') {
            $prefKey = 'matching_scholarship_alerts';
        } elseif ($type === 'SCHOLARSHIP_DEADLINE_SOON' || $type === 'SCHOLARSHIP_DEADLINE_TODAY') {
            $prefKey = 'deadline_reminders';
        } elseif ($type === 'DAILY_MATCH_DIGEST') {
            $prefKey = 'daily_alerts';
        } elseif ($type === 'WEEKLY_MATCH_DIGEST') {
            $prefKey = 'weekly_digest';
        }

        $emailAlertsEnabled = false;
        $whatsappAlertsEnabled = false;

        if ($prefKey) {
            $emailAlertsEnabled = $prefMap[$prefKey]['email'] ?? false;
            $whatsappAlertsEnabled = $prefMap[$prefKey]['whatsapp'] ?? false;
        } else {
            // Account and system messages default to true
            $emailAlertsEnabled = true;
            $whatsappAlertsEnabled = false;
        }

        // Check general email and whatsapp checkbox switches
        $generalEmail = (bool)($prefMap['email_alerts']['email'] ?? true);
        $generalWhatsapp = (bool)($prefMap['whatsapp_alerts']['whatsapp'] ?? false);

        // Combine settings with user level opt-ins (from registration/db settings)
        $sendEmail = $emailAlertsEnabled && $generalEmail && (bool)$user['email_opt_in'];
        $sendWhatsapp = $whatsappAlertsEnabled && $generalWhatsapp && (bool)$user['whatsapp_opt_in'];

        $isTransactional = $this->queueService->isTransactionalType($type);

        // 3.5. Scholarship Trust Gate: Only published and verified scholarships with non-passed deadline
        if ($scholarshipId && !$isTransactional) {
            $stmtSch = $this->db->prepare("
                SELECT id, title, provider_name, status, verification_status, funding_type, application_deadline, slug 
                FROM scholarships 
                WHERE id = :id 
                LIMIT 1
            ");
            $stmtSch->execute(['id' => $scholarshipId]);
            $sch = $stmtSch->fetch(PDO::FETCH_ASSOC);
            if (!$sch || $sch['status'] !== 'published' || $sch['verification_status'] !== 'verified') {
                \App\Services\Logger::info("Suppressed notification for unverified/unpublished scholarship ID: $scholarshipId");
                return;
            }
            if ($sch['application_deadline'] !== null && strtotime($sch['application_deadline']) < strtotime(date('Y-m-d'))) {
                \App\Services\Logger::info("Suppressed notification for expired scholarship ID: $scholarshipId");
                return;
            }

            if (empty($payloadData['title'])) {
                $payloadData['title'] = $sch['title'];
                $payloadData['provider'] = $sch['provider_name'];
                $payloadData['funding'] = $sch['funding_type'];
                $payloadData['deadline'] = $sch['application_deadline'];
                $payloadData['slug'] = $sch['slug'];
            }
            if (!empty($payloadData['slug']) && empty($payloadData['detail_url'])) {
                $payloadData['detail_url'] = url('/scholarships/' . $payloadData['slug']);
            }
        }

        // 4. Fetch user preferences (preferred channel and multi-channel flag)
        $stmtUp = $this->db->prepare("SELECT preferred_channel, allow_multi_channel FROM user_preferences WHERE user_id = :uid LIMIT 1");
        $stmtUp->execute(['uid' => $userId]);
        $userPref = $stmtUp->fetch(PDO::FETCH_ASSOC);

        $preferredChannel = $userPref['preferred_channel'] ?? 'email';
        $allowMultiChannel = (bool)($userPref['allow_multi_channel'] ?? 0);

        $rawPhone = $user['whatsapp_phone'] ?: $user['phone'];
        $normalizedPhone = !empty($rawPhone) ? \App\Services\WhatsApp\WacrmWhatsAppProvider::normalizePhoneNumber((string)$rawPhone) : null;
        $whatsappRecipient = $normalizedPhone;

        $isTransactional = $this->queueService->isTransactionalType($type);
        $isSunday = self::$simulateSunday !== null ? self::$simulateSunday : (((int)date('w') === 0 && !defined('BYPASS_SUNDAY_RULE')) || (defined('SIMULATE_SUNDAY') && SIMULATE_SUNDAY));

        $emailPossible = $sendEmail && !empty($user['email']);
        $whatsappPossible = $sendWhatsapp && !empty($whatsappRecipient) && ($isTransactional || !$isSunday);

        // Check lifetime 25-message cap on scholarship WhatsApp with explicit whitelist
        if ($whatsappPossible && !$isTransactional) {
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
                $whatsappPossible = false;
            }
        }

        // If multi-channel delivery is NOT explicitly enabled (DEFAULT = OFF):
        // Deliver via exactly ONE preferred channel with strict business event idempotency.
        // Fall back to email if preferred was whatsapp but WhatsApp is not possible (e.g. invalid/missing phone, Sunday, or limit reached).
        if (!$allowMultiChannel) {
            $chosenChannel = null;
            $chosenRecipient = null;
            $chosenSubject = null;

            if ($preferredChannel === 'whatsapp' && $whatsappPossible) {
                $chosenChannel = 'whatsapp';
                $chosenRecipient = $whatsappRecipient;
            } elseif ($emailPossible) {
                $chosenChannel = 'email';
                $chosenRecipient = $user['email'];
                $chosenSubject = $payloadData['subject'] ?? $this->getDefaultSubject($type, $payloadData);
            } elseif ($whatsappPossible) {
                $chosenChannel = 'whatsapp';
                $chosenRecipient = $whatsappRecipient;
            }

            if ($chosenChannel !== null) {
                // Single event key (e.g. "new_match_{userId}_{scholarshipId}")
                $this->queueService->enqueue(
                    $userId,
                    $scholarshipId,
                    $type,
                    $chosenChannel,
                    $chosenRecipient,
                    $chosenSubject,
                    $payloadData,
                    $idempotencyKey,
                    $availableAt
                );
            }
        } else {
            // Multi-channel delivery explicitly enabled by user
            if ($emailPossible) {
                $subject = $payloadData['subject'] ?? $this->getDefaultSubject($type, $payloadData);
                $this->queueService->enqueue(
                    $userId,
                    $scholarshipId,
                    $type,
                    'email',
                    $user['email'],
                    $subject,
                    $payloadData,
                    $idempotencyKey ? $idempotencyKey . '_email' : null,
                    $availableAt
                );
            }

            if ($whatsappPossible) {
                $this->queueService->enqueue(
                    $userId,
                    $scholarshipId,
                    $type,
                    'whatsapp',
                    $whatsappRecipient,
                    null,
                    $payloadData,
                    $idempotencyKey ? $idempotencyKey . '_whatsapp' : null,
                    $availableAt
                );
            }
        }
    }

    private function getDefaultSubject(string $type, array $payload): string {
        $title = $payload['title'] ?? 'Opportunity';
        switch ($type) {
            case 'NEW_MATCH':
                return "🎓 New Match: {$title}";
            case 'SCHOLARSHIP_DEADLINE_SOON':
                return "⏰ Deadline Closing Soon: {$title}";
            case 'SCHOLARSHIP_DEADLINE_TODAY':
                return "⚠️ LAST DAY to Apply: {$title}";
            case 'DAILY_MATCH_DIGEST':
                return "📅 Your Daily Scholarship Matches";
            case 'WEEKLY_MATCH_DIGEST':
                return "📰 Your Weekly Scholarship Digest";
            default:
                return "ScholarMatch Notification";
        }
    }

    /**
     * Verify WhatsApp Opt-in settings for a user.
     */
    public function hasWhatsAppOptIn(int $userId, string $type): bool {
        $stmt = $this->db->prepare("SELECT whatsapp_opt_in FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $userId]);
        $userOptIn = $stmt->fetchColumn();
        if (!$userOptIn) {
            return false;
        }

        $stmtPref = $this->db->prepare("SELECT * FROM notification_preferences WHERE user_id = :uid");
        $stmtPref->execute(['uid' => $userId]);
        $prefs = $stmtPref->fetchAll(PDO::FETCH_ASSOC);

        $prefMap = [];
        foreach ($prefs as $p) {
            $prefMap[$p['notification_type']] = (bool)$p['whatsapp_enabled'];
        }

        $generalWhatsapp = $prefMap['whatsapp_alerts'] ?? false;
        if (!$generalWhatsapp) {
            return false;
        }

        $prefKey = null;
        if ($type === 'NEW_MATCH') {
            $prefKey = 'matching_scholarship_alerts';
        } elseif ($type === 'SCHOLARSHIP_DEADLINE_SOON' || $type === 'SCHOLARSHIP_DEADLINE_TODAY' || $type === 'DEADLINE_REMINDER') {
            $prefKey = 'deadline_reminders';
        }

        if ($prefKey) {
            return $prefMap[$prefKey] ?? false;
        }

        return false;
    }

    /**
     * Create premium NEW_MATCH WhatsApp notification event.
     */
    public function createNewMatchNotification(int $userId, int $scholarshipId): bool {
        // 1. User exists
        $stmtUser = $this->db->prepare("SELECT id, phone, whatsapp_phone, whatsapp_opt_in FROM users WHERE id = :id LIMIT 1");
        $stmtUser->execute(['id' => $userId]);
        $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            return false;
        }

        // 2. User is eligible for premium notifications (Premium Subscription check)
        if (!SubscriptionService::can($userId, 'whatsapp_alerts')) {
            return false;
        }

        // 3. User has WhatsApp opt-in
        if (!$this->hasWhatsAppOptIn($userId, NotificationTypes::NEW_MATCH)) {
            return false;
        }

        // 4. User has a valid WhatsApp number
        $recipient = $user['whatsapp_phone'] ?: $user['phone'];
        if (empty($recipient)) {
            return false;
        }
        $normalized = \App\Services\WhatsApp\WacrmWhatsAppProvider::normalizePhoneNumber($recipient);
        if ($normalized === null) {
            return false;
        }

        // 5. Scholarship exists
        $stmtSch = $this->db->prepare("SELECT id, title, provider_name, funding_type, application_deadline, slug, status FROM scholarships WHERE id = :id LIMIT 1");
        $stmtSch->execute(['id' => $scholarshipId]);
        $sch = $stmtSch->fetch(PDO::FETCH_ASSOC);
        if (!$sch) {
            return false;
        }

        // 6. Scholarship is published
        if ($sch['status'] !== 'published') {
            return false;
        }

        // 7. Scholarship has not expired
        if ($sch['application_deadline'] !== null && strtotime($sch['application_deadline']) < strtotime(date('Y-m-d'))) {
            return false;
        }

        // 8. Scholarship matches the user
        $matchingService = new ScholarshipMatchingService();
        $match = $matchingService->matchUserAndScholarship($userId, $scholarshipId);
        if (($match['eligibility_status'] ?? '') !== 'ELIGIBLE') {
            return false;
        }

        // 9. NEW_MATCH notification does not already exist (Concurrency & Idempotency safe check)
        $stmtCheck = $this->db->prepare("
            SELECT COUNT(*) FROM notification_logs 
            WHERE user_id = :user_id 
              AND scholarship_id = :scholarship_id 
              AND notification_type = :type 
              AND channel = 'whatsapp'
        ");
        $stmtCheck->execute([
            'user_id' => $userId,
            'scholarship_id' => $scholarshipId,
            'type' => NotificationTypes::NEW_MATCH
        ]);
        if ((int)$stmtCheck->fetchColumn() > 0) {
            return false;
        }

        $payloadData = [
            'title' => $sch['title'],
            'provider' => $sch['provider_name'],
            'funding' => $sch['funding_type'],
            'deadline' => $sch['application_deadline'],
            'slug' => $sch['slug']
        ];
        
        $activeProvider = $_ENV['WHATSAPP_PROVIDER'] ?? 'wacrm';

        return $this->queueService->enqueue(
            $userId,
            $scholarshipId,
            NotificationTypes::NEW_MATCH,
            'whatsapp',
            $normalized,
            null,
            $payloadData,
            null,
            null,
            $activeProvider
        );
    }

    /**
     * Enqueue and aggregate daily WhatsApp batch for scholarships discovered on a specific calendar day.
     * Enforces Asia/Karachi collection window (00:00:00 - 23:59:59 PKT).
     * After cutoff (23:59:59 PKT), the batch is immutable; subsequent matches are allocated to the next open day.
     * On Sunday, automatic scholarship WhatsApp is deferred to Monday or falls back to email.
     */
    public function enqueueDailyWhatsAppBatch(int $userId, array $matches, ?string $calendarDay = null, ?string $recipientPhone = null): bool {
        if (empty($matches)) {
            return false;
        }

        $nowPkt = self::getKarachiDateTime();
        if ($calendarDay === null) {
            $calendarDay = $nowPkt->format('Y-m-d');
        }

        $inTx = $this->db->inTransaction();
        if (!$inTx) {
            $this->db->beginTransaction();
        }

        try {
            // Check Sunday Rule in Asia/Karachi
            $targetDt = new \DateTime("{$calendarDay} 12:00:00", new \DateTimeZone(self::TIMEZONE));
            $pktDayOfWeek = (int)$targetDt->format('w');
            $isSunday = self::$simulateSunday !== null 
                ? self::$simulateSunday 
                : (($pktDayOfWeek === 0 && !defined('BYPASS_SUNDAY_RULE')) || (defined('SIMULATE_SUNDAY') && SIMULATE_SUNDAY));

            // Fetch user contact details and opt-in settings
            $stmtUser = $this->db->prepare("SELECT email, phone, whatsapp_phone, email_opt_in, whatsapp_opt_in FROM users WHERE id = :id LIMIT 1");
            $stmtUser->execute(['id' => $userId]);
            $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                if (!$inTx) $this->db->rollBack();
                return false;
            }

            // Sunday Deferment & Fallback Handling
            if ($isSunday) {
                $emailOptIn = (bool)$user['email_opt_in'];
                if ($emailOptIn && !empty($user['email'])) {
                    // Deliver Sunday matches via Email fallback
                    $emailPayload = [
                        'calendar_day' => $calendarDay,
                        'match_count' => count($matches),
                        'matches' => $matches,
                        'is_sunday_fallback' => true
                    ];
                    if (count($matches) === 1) {
                        $m = $matches[0];
                        $emailPayload['title'] = $m['title'] ?? '';
                        $emailPayload['provider'] = $m['provider'] ?? '';
                        $emailPayload['degree'] = $m['degree'] ?? '';
                        $emailPayload['field'] = $m['field'] ?? '';
                        $emailPayload['country'] = $m['country'] ?? '';
                        $emailPayload['funding'] = $m['funding'] ?? '';
                        $emailPayload['deadline'] = $m['deadline'] ?? 'Open/Rolling';
                        $emailPayload['score'] = $m['score'] ?? '0';
                    }
                    $firstSchId = !empty($matches[0]['scholarship_id']) ? (int)$matches[0]['scholarship_id'] : null;
                    $this->queueService->enqueue(
                        $userId,
                        $firstSchId,
                        'DAILY_MATCH_DIGEST',
                        'email',
                        $user['email'],
                        '📅 Your Sunday Scholarship Matches',
                        $emailPayload,
                        "daily_match_digest_email_{$userId}_{$calendarDay}"
                    );
                    if (!$inTx) $this->db->commit();
                    return true;
                } else {
                    // WhatsApp-only user with email disabled: DEFER to Monday without losing matches!
                    $nextMondayDt = clone $targetDt;
                    $nextMondayDt->modify('next monday');
                    $calendarDay = $nextMondayDt->format('Y-m-d');
                }
            }

            // Check if requested calendarDay is already past its hard cutoff (23:59:59 PKT)
            $cutoffPkt = self::getCutoffDateTime($calendarDay);
            if (self::isPastCutoff($calendarDay, $nowPkt) && !defined('BYPASS_BATCH_CUTOFF')) {
                // The batch for $calendarDay has closed. New matches belong to current open day!
                $calendarDay = $nowPkt->format('Y-m-d');
                $cutoffPkt = self::getCutoffDateTime($calendarDay);
            }

            // Lifetime 25-message limit check using explicit whitelist
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
                \App\Services\Logger::info("Skipped enqueuing daily WhatsApp batch for user $userId: Lifetime 25 limit reached.");
                if (!$inTx) $this->db->commit();
                return false;
            }

            if ($recipientPhone === null) {
                $raw = $user['whatsapp_phone'] ?: $user['phone'];
                $recipientPhone = !empty($raw) ? \App\Services\WhatsApp\WacrmWhatsAppProvider::normalizePhoneNumber((string)$raw) : null;
            }

            if ($recipientPhone === null) {
                // Missing/invalid phone -> email fallback if permitted
                if ((bool)$user['email_opt_in'] && !empty($user['email'])) {
                    $emailPayload = [
                        'calendar_day' => $calendarDay,
                        'match_count' => count($matches),
                        'matches' => $matches
                    ];
                    $firstSchId = !empty($matches[0]['scholarship_id']) ? (int)$matches[0]['scholarship_id'] : null;
                    $this->queueService->enqueue(
                        $userId,
                        $firstSchId,
                        'DAILY_MATCH_DIGEST',
                        'email',
                        $user['email'],
                        '📅 Your Daily Scholarship Matches',
                        $emailPayload,
                        "daily_match_digest_email_{$userId}_{$calendarDay}"
                    );
                }
                if (!$inTx) $this->db->commit();
                return true;
            }

            $idempotencyKey = "scholarship_whatsapp_{$userId}_{$calendarDay}";

            $stmtLock = $this->db->prepare("
                SELECT id, status, payload, scholarship_id, attempts 
                FROM notification_logs 
                WHERE idempotency_key = :key 
                FOR UPDATE
            ");
            $stmtLock->execute(['key' => $idempotencyKey]);
            $existing = $stmtLock->fetch(PDO::FETCH_ASSOC);

            if (!$existing) {
                // First batch for this calendar day: create pending batch record
                $payload = [
                    'calendar_day' => $calendarDay,
                    'match_count' => count($matches),
                    'matches' => $matches
                ];

                if (count($matches) === 1) {
                    $m = $matches[0];
                    $payload['title'] = $m['title'] ?? '';
                    $payload['provider'] = $m['provider'] ?? '';
                    $payload['degree'] = $m['degree'] ?? '';
                    $payload['field'] = $m['field'] ?? '';
                    $payload['country'] = $m['country'] ?? '';
                    $payload['funding'] = $m['funding'] ?? '';
                    $payload['deadline'] = $m['deadline'] ?? 'Open/Rolling';
                    $payload['score'] = $m['score'] ?? '0';
                }

                $firstSchId = !empty($matches[0]['scholarship_id']) ? (int)$matches[0]['scholarship_id'] : null;
                if ($firstSchId !== null) {
                    $stmtExists = $this->db->prepare("SELECT 1 FROM scholarships WHERE id = :id LIMIT 1");
                    $stmtExists->execute(['id' => $firstSchId]);
                    if (!$stmtExists->fetchColumn()) {
                        $firstSchId = null;
                    }
                }

                // Available at cutoff time: 23:59:59 PKT
                $availableAt = $cutoffPkt->format('Y-m-d H:i:s');

                $stmtIns = $this->db->prepare("
                    INSERT INTO notification_logs (
                        user_id, scholarship_id, notification_type, channel, provider,
                        recipient, payload, idempotency_key, status, available_at, created_at, updated_at
                    ) VALUES (
                        :uid, :sid, 'DAILY_MATCH_DIGEST', 'whatsapp', 'wacrm',
                        :rcpt, :payload, :key, 'pending', :avail, NOW(), NOW()
                    )
                ");
                $stmtIns->execute([
                    'uid' => $userId,
                    'sid' => $firstSchId,
                    'rcpt' => $recipientPhone,
                    'payload' => json_encode($payload),
                    'key' => $idempotencyKey,
                    'avail' => $availableAt
                ]);
            } else {
                $status = $existing['status'];
                $isImmutable = ($nowPkt > $cutoffPkt && !defined('BYPASS_BATCH_CUTOFF')) || in_array($status, ['processing', 'sent', 'delivered']);

                if ($isImmutable) {
                    // Batch is closed and immutable.
                    // Assign any new un-batched matches to the next open calendar day!
                    $existingPayload = json_decode($existing['payload'] ?? '{}', true) ?: [];
                    $existingMatches = $existingPayload['matches'] ?? [];
                    $existingIds = array_column($existingMatches, 'scholarship_id');
                    $unassigned = [];
                    foreach ($matches as $m) {
                        if (!in_array($m['scholarship_id'] ?? 0, $existingIds)) {
                            $unassigned[] = $m;
                        }
                    }
                    if (!empty($unassigned)) {
                        $nextDayPkt = clone $nowPkt;
                        $nextDayPkt->modify('+1 day');
                        $nextCalendarDay = $nextDayPkt->format('Y-m-d');
                        if (!$inTx) $this->db->commit();
                        return $this->enqueueDailyWhatsAppBatch($userId, $unassigned, $nextCalendarDay, $recipientPhone);
                    }
                } elseif ($status === 'pending' || $status === 'retrying') {
                    // Batch is mutable before cutoff: merge new matches into payload
                    $existingPayload = json_decode($existing['payload'] ?? '{}', true) ?: [];
                    $existingMatches = $existingPayload['matches'] ?? [];
                    $existingIds = [];
                    foreach ($existingMatches as $em) {
                        if (!empty($em['scholarship_id'])) {
                            $existingIds[(int)$em['scholarship_id']] = true;
                        }
                    }

                    $mergedMatches = $existingMatches;
                    $added = false;
                    foreach ($matches as $newM) {
                        $newId = (int)($newM['scholarship_id'] ?? 0);
                        if ($newId > 0 && !isset($existingIds[$newId])) {
                            $mergedMatches[] = $newM;
                            $existingIds[$newId] = true;
                            $added = true;
                        }
                    }

                    if ($added) {
                        $existingPayload['calendar_day'] = $calendarDay;
                        $existingPayload['match_count'] = count($mergedMatches);
                        $existingPayload['matches'] = $mergedMatches;

                        if (count($mergedMatches) === 1) {
                            $m = $mergedMatches[0];
                            $existingPayload['title'] = $m['title'] ?? '';
                            $existingPayload['provider'] = $m['provider'] ?? '';
                            $existingPayload['degree'] = $m['degree'] ?? '';
                            $existingPayload['field'] = $m['field'] ?? '';
                            $existingPayload['country'] = $m['country'] ?? '';
                            $existingPayload['funding'] = $m['funding'] ?? '';
                            $existingPayload['deadline'] = $m['deadline'] ?? 'Open/Rolling';
                            $existingPayload['score'] = $m['score'] ?? '0';
                        }

                        $firstSchId = !empty($mergedMatches[0]['scholarship_id']) ? (int)$mergedMatches[0]['scholarship_id'] : null;
                        if ($firstSchId !== null) {
                            $stmtExists = $this->db->prepare("SELECT 1 FROM scholarships WHERE id = :id LIMIT 1");
                            $stmtExists->execute(['id' => $firstSchId]);
                            if (!$stmtExists->fetchColumn()) {
                                $firstSchId = null;
                            }
                        }

                        $stmtUp = $this->db->prepare("
                            UPDATE notification_logs 
                            SET payload = :payload,
                                scholarship_id = :sid,
                                updated_at = NOW()
                            WHERE id = :id
                        ");
                        $stmtUp->execute([
                            'payload' => json_encode($existingPayload),
                            'sid' => $firstSchId,
                            'id' => $existing['id']
                        ]);
                    }
                }
            }

            if (!$inTx) {
                $this->db->commit();
            }
            return true;
        } catch (\Exception $e) {
            if (!$inTx && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            \App\Services\Logger::error("Failed to aggregate daily WhatsApp batch for user $userId: " . $e->getMessage());
            return false;
        }
    }
}
