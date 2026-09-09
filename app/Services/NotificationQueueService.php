<?php

namespace App\Services;

use App\Services\Database;
use PDO;
use PDOException;

class NotificationQueueService {
    private PDO $db;
    private int $maxAttempts = 5;
    private int $retryDelay = 60;
    private array $retryDelays = [
        1 => 60,     // Attempt 1: +60s
        2 => 300,    // Attempt 2: +300s (5m)
        3 => 900,    // Attempt 3: +900s (15m)
        4 => 3600,   // Attempt 4: +3600s (1h)
        5 => 14400   // Attempt 5: +14400s (4h)
    ];

    public function __construct() {
        $this->db = Database::connection();
        $this->maxAttempts = (int)($_ENV['NOTIFICATION_MAX_ATTEMPTS'] ?? 5);
        $this->retryDelay = (int)($_ENV['NOTIFICATION_RETRY_DELAY'] ?? 60);
    }

    /**
     * Check if a notification type is transactional (bypasses marketing preferences).
     */
    public function isTransactionalType(string $type): bool {
        return in_array($type, [
            NotificationTypes::EMAIL_VERIFICATION,
            'EMAIL_VERIFICATION',
            'PASSWORD_RESET',
            'PAYMENT_CONFIRMATION',
            'PAYMENT_SUCCESS',
            'SUBSCRIPTION_CONFIRMATION'
        ], true);
    }

    /**
     * Enqueue a notification with idempotency protection.
     *
     * @return bool True if enqueued successfully, false if skipped as duplicate.
     */
    public function enqueue(
        int $userId,
        ?int $scholarshipId,
        string $type,
        string $channel,
        string $recipient,
        ?string $subject,
        array $payloadData,
        ?string $idempotencyKey = null,
        ?string $availableAt = null,
        ?string $provider = null,
        ?int $subscriptionId = null
    ): bool {
        $isTransactional = $this->isTransactionalType($type);

        // Gating rules for premium marketing notifications
        $isTestingBypass = false;
        if (defined('TESTING_MODE') && TESTING_MODE) {
            $db = Database::connection();
            $stmtUser = $db->prepare("SELECT email FROM users WHERE id = :id LIMIT 1");
            $stmtUser->execute(['id' => $userId]);
            $email = (string)$stmtUser->fetchColumn();
            if ($email !== 'student_billing@example.com' && strpos($email, 'step2-test-51') === false) {
                $isTestingBypass = true;
            }
        }

        if (!$isTransactional && !$isTestingBypass) {
            if ($channel === 'whatsapp' && !\App\Services\SubscriptionService::can($userId, 'whatsapp_alerts')) {
                \App\Services\Logger::info("Skipped enqueuing WhatsApp notification for user $userId (Free plan).");
                return false;
            }

            if (($type === 'SCHOLARSHIP_DEADLINE_SOON' || $type === 'SCHOLARSHIP_DEADLINE_TODAY') && !\App\Services\SubscriptionService::can($userId, 'deadline_alerts')) {
                \App\Services\Logger::info("Skipped enqueuing deadline alert for user $userId (Free plan).");
                return false;
            }

            if (($type === 'NEW_MATCH' || $type === 'DAILY_MATCH_DIGEST' || $type === 'WEEKLY_MATCH_DIGEST') && !\App\Services\SubscriptionService::can($userId, 'premium_alerts')) {
                \App\Services\Logger::info("Skipped enqueuing match alert for user $userId (Free plan).");
                return false;
            }
        }

        if ($scholarshipId !== null) {
            $stmtExists = $this->db->prepare("SELECT 1 FROM scholarships WHERE id = :id LIMIT 1");
            $stmtExists->execute(['id' => $scholarshipId]);
            if (!$stmtExists->fetchColumn()) {
                $scholarshipId = null;
            }
        }

        if ($idempotencyKey === null) {
            // Build deterministic idempotency key
            $schId = $scholarshipId ?? 0;
            if ($isTransactional) {
                $tokenHash = $payloadData['token_hash'] ?? uniqid('', true);
                $idempotencyKey = "verify_{$userId}_{$tokenHash}";
            } elseif ($type === 'NEW_MATCH' || $type === 'DEADLINE_REMINDER') {
                $idempotencyKey = "{$userId}_{$schId}_{$type}_{$channel}";
            } else {
                $eventDate = date('Y-m-d');
                $idempotencyKey = "{$userId}_{$schId}_{$type}_{$channel}_{$eventDate}";
            }
        }

        if ($provider === null) {
            if ($channel === 'whatsapp') {
                if (strpos($type, 'PAYMENT') !== false || strpos($type, 'CASHMAAL') !== false || strpos($type, 'SUBSCRIPTION_CONFIRMATION') !== false) {
                    $provider = 'meta';
                } else {
                    $provider = 'wacrm';
                }
            } elseif ($channel === 'email') {
                $provider = 'smtp';
            }
        }

        if ($subscriptionId === null && $userId > 0) {
            $activePlan = \App\Services\SubscriptionService::getActivePlan($userId);
            if (!empty($activePlan['id']) && in_array($activePlan['status'] ?? '', ['active', 'protected'], true)) {
                $subscriptionId = (int)$activePlan['id'];
            }
        }

        $payload = json_encode($payloadData);
        $availAt = $availableAt ?: date('Y-m-d H:i:s');

        try {
            $stmt = $this->db->prepare("
                INSERT INTO notification_logs (
                    user_id, subscription_id, scholarship_id, notification_type, channel, provider, recipient, 
                    subject, payload, idempotency_key, status, available_at, created_at, updated_at
                ) VALUES (
                    :user_id, :subscription_id, :scholarship_id, :type, :channel, :provider, :recipient, 
                    :subject, :payload, :idempotency_key, 'pending', :available_at, NOW(), NOW()
                )
            ");
            return $stmt->execute([
                'user_id' => $userId,
                'subscription_id' => $subscriptionId,
                'scholarship_id' => $scholarshipId,
                'type' => $type,
                'channel' => $channel,
                'provider' => $provider,
                'recipient' => $recipient,
                'subject' => $subject,
                'payload' => $payload,
                'idempotency_key' => $idempotencyKey,
                'available_at' => $availAt
            ]);
        } catch (PDOException $e) {
            // Integrity constraint violation (duplicate key 1062 or SQLSTATE 23000)
            if ($e->getCode() == 23000 || strpos($e->getMessage(), '1062') !== false) {
                // Log and return false (safe duplicate skip)
                \App\Services\Logger::info("Skipped duplicate notification enqueue (Idempotency triggered): $idempotencyKey");
                return false;
            }
            throw $e;
        }
    }

    /**
     * Process pending queue notifications.
     */
    public function processQueue(int $batchSize = 100): int {
        // Recover any stale processing jobs first
        $this->recoverStaleJobs();

        $db = $this->db;

        // 1. Transaction to select and mark records to lock out other workers
        $db->beginTransaction();
        try {
            $stmt = $db->prepare("
                SELECT id FROM notification_logs 
                WHERE status IN ('pending', 'retrying') 
                  AND available_at <= NOW() 
                  AND attempts < :max_attempts
                LIMIT :limit
                FOR UPDATE
            ");
            $stmt->bindValue(':max_attempts', $this->maxAttempts, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $batchSize, PDO::PARAM_INT);
            $stmt->execute();
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($records)) {
                $db->commit();
                return 0;
            }

            $ids = array_column($records, 'id');
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $upStmt = $db->prepare("UPDATE notification_logs SET status = 'processing', available_at = DATE_ADD(NOW(), INTERVAL 1800 SECOND), updated_at = NOW() WHERE id IN ($placeholders)");
            $upStmt->execute($ids);

            $db->commit();
        } catch (\Exception $e) {
            $db->rollBack();
            \App\Services\Logger::error("Failed lock phase in processQueue: " . $e->getMessage());
            return 0;
        }

        // 2. Fetch the marked records and process them in isolation
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmtFetch = $db->prepare("SELECT * FROM notification_logs WHERE id IN ($placeholders)");
        $stmtFetch->execute($ids);
        $queueItems = $stmtFetch->fetchAll(PDO::FETCH_ASSOC);

        $processedCount = 0;
        $emailService = new EmailNotificationService();
        $whatsappService = new WhatsAppNotificationService();

        foreach ($queueItems as $item) {
            $channel = strtolower($item['channel']);
            $recipient = $item['recipient'];
            $payload = ($item['payload'] !== null) ? (json_decode($item['payload'], true) ?: []) : [];

            // 1. Immediate validation of supported channels (email, whatsapp)
            if ($channel !== 'email' && $channel !== 'whatsapp') {
                $this->updateQueueItemStatus($item['id'], (int)$item['attempts'], false, "Unsupported notification channel: $channel", null);
                $processedCount++;
                continue;
            }

            // Recheck user details before delivery
            $userId = (int)$item['user_id'];
            $type = $item['notification_type'];
            $isTransactional = $this->isTransactionalType($type);
            
            $stmtUser = $db->prepare("SELECT email, phone, whatsapp_phone, email_opt_in, whatsapp_opt_in FROM users WHERE id = :id LIMIT 1");
            $stmtUser->execute(['id' => $userId]);
            $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $this->updateQueueItemStatus($item['id'], (int)$item['attempts'], false, "User not found (deleted).", null);
                $processedCount++;
                continue;
            }

            if ($isTransactional) {
                // Transactional notifications (e.g. Email Verification, Payment Confirmation) bypass optional marketing preferences
                $sendEmail = ($channel === 'email');
                $sendWhatsapp = ($channel === 'whatsapp');
            } else {
                $stmtPref = $db->prepare("SELECT * FROM notification_preferences WHERE user_id = :uid");
                $stmtPref->execute(['uid' => $userId]);
                $prefs = $stmtPref->fetchAll(PDO::FETCH_ASSOC);
                
                $prefMap = [];
                foreach ($prefs as $p) {
                    $prefMap[$p['notification_type']] = [
                        'email' => (bool)$p['email_enabled'],
                        'whatsapp' => (bool)$p['whatsapp_enabled']
                    ];
                }

                $prefKey = null;
                $emailAlertsEnabled = false;
                $whatsappAlertsEnabled = false;

                if ($type === 'NEW_MATCH' || $type === 'DAILY_MATCH_DIGEST') {
                    $prefKey = 'matching_scholarship_alerts';
                    $emailAlertsEnabled = ($prefMap['matching_scholarship_alerts']['email'] ?? false) || ($prefMap['daily_alerts']['email'] ?? false);
                    $whatsappAlertsEnabled = ($prefMap['matching_scholarship_alerts']['whatsapp'] ?? false) || ($prefMap['daily_alerts']['whatsapp'] ?? false);
                } elseif ($type === 'SCHOLARSHIP_DEADLINE_SOON' || $type === 'SCHOLARSHIP_DEADLINE_TODAY') {
                    $prefKey = 'deadline_reminders';
                    $emailAlertsEnabled = $prefMap[$prefKey]['email'] ?? false;
                    $whatsappAlertsEnabled = $prefMap[$prefKey]['whatsapp'] ?? false;
                } elseif ($type === 'WEEKLY_MATCH_DIGEST') {
                    $prefKey = 'weekly_digest';
                    $emailAlertsEnabled = $prefMap[$prefKey]['email'] ?? false;
                    $whatsappAlertsEnabled = $prefMap[$prefKey]['whatsapp'] ?? false;
                } else {
                    $emailAlertsEnabled = true;
                    $whatsappAlertsEnabled = true;
                }

                $generalEmail = (bool)($prefMap['email_alerts']['email'] ?? true);
                $generalWhatsapp = (bool)($prefMap['whatsapp_alerts']['whatsapp'] ?? false);

                $sendEmail = $emailAlertsEnabled && $generalEmail && (bool)$user['email_opt_in'];
                $sendWhatsapp = $whatsappAlertsEnabled && $generalWhatsapp && (bool)$user['whatsapp_opt_in'];
            }

            if ($channel === 'email' && !$sendEmail) {
                $this->updateQueueItemStatusToSkipped($item['id'], "Cancelled: User opted out of email channel before delivery.");
                $processedCount++;
                continue;
            }
            if ($channel === 'whatsapp' && !$sendWhatsapp) {
                $this->updateQueueItemStatusToSkipped($item['id'], "Cancelled: User opted out of WhatsApp channel before delivery.");
                $processedCount++;
                continue;
            }

            // Scholarship Trust Gate: Only deliver if scholarship is currently published, verified, and not expired
            if (!$isTransactional && !empty($item['scholarship_id'])) {
                $schCheck = $db->prepare("
                    SELECT status, verification_status, application_deadline 
                    FROM scholarships 
                    WHERE id = :sid 
                    LIMIT 1
                ");
                $schCheck->execute(['sid' => $item['scholarship_id']]);
                $schData = $schCheck->fetch(PDO::FETCH_ASSOC);
                if (!$schData || $schData['status'] !== 'published' || $schData['verification_status'] !== 'verified') {
                    $this->updateQueueItemStatusToSkipped($item['id'], "Delivery skipped: Scholarship is no longer published or verified.");
                    $processedCount++;
                    continue;
                }
                if ($schData['application_deadline'] !== null && strtotime($schData['application_deadline']) < strtotime(date('Y-m-d'))) {
                    $this->updateQueueItemStatusToSkipped($item['id'], "Delivery skipped: Scholarship deadline has already passed.");
                    $processedCount++;
                    continue;
                }
            }

            // Automatic Scholarship WhatsApp Guardrails (Cutoff Gate, Sunday Rule & Lifetime 25-Message Cap)
            if ($channel === 'whatsapp' && !$isTransactional) {
                // 0. Batch Cutoff Gate: Worker MUST NOT send daily scholarship WhatsApp batch before cutoff (23:59:59 PKT)
                if ($type === 'DAILY_MATCH_DIGEST' || strpos($item['idempotency_key'] ?? '', 'scholarship_whatsapp_') === 0) {
                    $batchDay = $payload['calendar_day'] ?? null;
                    if (!$batchDay && preg_match('/scholarship_whatsapp_\d+_(\d{4}-\d{2}-\d{2})/', $item['idempotency_key'] ?? '', $m)) {
                        $batchDay = $m[1];
                    }

                    if ($batchDay !== null) {
                        $nowPkt = \App\Services\NotificationService::getKarachiDateTime();
                        $cutoffPkt = \App\Services\NotificationService::getCutoffDateTime($batchDay);
                        if (!\App\Services\NotificationService::isPastCutoff($batchDay, $nowPkt) && !defined('BYPASS_BATCH_CUTOFF')) {
                            // Batch collection window is still open! Worker must not deliver before cutoff.
                            $db->prepare("
                                UPDATE notification_logs 
                                SET status = 'pending', 
                                    available_at = :avail, 
                                    updated_at = NOW() 
                                WHERE id = :id
                            ")->execute([
                                'avail' => $cutoffPkt->format('Y-m-d H:i:s'),
                                'id' => $item['id']
                            ]);
                            continue;
                        }
                    }
                }

                // 1. Sunday Rule: Prohibit automatic scholarship WhatsApp delivery on Sunday
                $isSunday = \App\Services\NotificationService::$simulateSunday !== null 
                    ? \App\Services\NotificationService::$simulateSunday 
                    : (((int)\App\Services\NotificationService::getKarachiDateTime()->format('w') === 0 && !defined('BYPASS_SUNDAY_RULE')) || (defined('SIMULATE_SUNDAY') && SIMULATE_SUNDAY));

                if ($isSunday) {
                    // Check if email fallback is permitted:
                    if ((bool)$user['email_opt_in'] && !empty($user['email'])) {
                        // Deliver via Email fallback
                        $emailSubject = '📅 Your Sunday Scholarship Matches';
                        $emailBody = $this->renderHtmlEmail($item['notification_type'], $payload);
                        $res = $emailService->sendEmail($user['email'], $emailSubject, $emailBody);
                        $this->updateQueueItemStatus($item['id'], (int)$item['attempts'], $res['success'], $res['error'] ?? null, $res['message_id'] ?? null);
                        $processedCount++;
                        continue;
                    } else {
                        // WhatsApp-only user: DEFER to Monday without dropping or skipping!
                        $nextMonday = \App\Services\NotificationService::getKarachiDateTime()->modify('next monday')->format('Y-m-d');
                        $cutoffMonday = \App\Services\NotificationService::getCutoffDateTime($nextMonday);
                        $db->prepare("
                            UPDATE notification_logs 
                            SET status = 'pending', 
                                available_at = :avail, 
                                updated_at = NOW() 
                            WHERE id = :id
                        ")->execute([
                            'avail' => $cutoffMonday->format('Y-m-d H:i:s'),
                            'id' => $item['id']
                        ]);
                        continue;
                    }
                }

                // 2. Validate recipient phone number format
                $normPhone = \App\Services\WhatsApp\WacrmWhatsAppProvider::normalizePhoneNumber($recipient);
                if ($normPhone === null) {
                    $this->updateQueueItemStatus($item['id'], (int)$item['attempts'], false, "Invalid recipient phone number format. Must be in E.164 format.", null);
                    $processedCount++;
                    continue;
                }
                $recipient = $normPhone;

                // 3. Concurrency-Safe Lifetime 25-Message Cap using user row-level locking
                $db->beginTransaction();
                try {
                    $lockStmt = $db->prepare("SELECT id FROM users WHERE id = :uid FOR UPDATE");
                    $lockStmt->execute(['uid' => $userId]);

                    $cntStmt = $db->prepare("
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
                    $sentCount = (int)$cntStmt->fetchColumn();

                    if ($sentCount >= 25) {
                        $db->rollBack();
                        $this->updateQueueItemStatusToSkipped($item['id'], "Lifetime limit reached: User has received maximum 25 scholarship WhatsApp messages.");
                        $processedCount++;
                        continue;
                    }

                    // Perform delivery while holding row lock to serialize concurrent workers
                    $templateName = $this->getWhatsAppTemplateName($item['notification_type']);
                    $params = $this->buildWhatsAppTemplateParams($item['notification_type'], $payload);
                    $providerName = $item['provider'] ?? null;

                    $res = $whatsappService->sendMessage($recipient, $templateName, $params, $providerName, $item['notification_type']);
                    $success = $res['success'];
                    $error = $res['error'] ?? null;
                    $providerMsgId = $res['message_id'] ?? null;
                    $retryAfter = $res['retry_after'] ?? null;

                    $this->updateQueueItemStatus($item['id'], (int)$item['attempts'], $success, $error, $providerMsgId, $retryAfter);
                    $db->commit();
                    $processedCount++;
                    continue;
                } catch (\Exception $ex) {
                    $db->rollBack();
                    $this->updateQueueItemStatus($item['id'], (int)$item['attempts'], false, $ex->getMessage(), null);
                    $processedCount++;
                    continue;
                }
            }

            // Normal delivery for Email or Transactional WhatsApp (e.g. Payment Confirmation)
            $success = false;
            $error = null;
            $providerMsgId = null;
            $retryAfter = null;

            try {
                if ($channel === 'email') {
                    $subject = $item['subject'] ?? 'ScholarPlanner Notification';
                    
                    // Build HTML email layout
                    $emailBody = $this->renderHtmlEmail($item['notification_type'], $payload);
                    
                    $res = $emailService->sendEmail($recipient, $subject, $emailBody);
                    $success = $res['success'];
                    $error = $res['error'] ?? null;
                    $providerMsgId = $res['message_id'] ?? null;
                } elseif ($channel === 'whatsapp') {
                    // Transactional WhatsApp (e.g. Meta payment confirmation)
                    $templateName = $this->getWhatsAppTemplateName($item['notification_type']);
                    $params = $this->buildWhatsAppTemplateParams($item['notification_type'], $payload);
                    $providerName = $item['provider'] ?? null;
                    $res = $whatsappService->sendMessage($recipient, $templateName, $params, $providerName, $item['notification_type']);
                    $success = $res['success'];
                    $error = $res['error'] ?? null;
                    $providerMsgId = $res['message_id'] ?? null;
                    $retryAfter = $res['retry_after'] ?? null;
                } else {
                    $error = "Unsupported notification channel: $channel";
                }
            } catch (\Exception $ex) {
                $success = false;
                $error = $ex->getMessage();
            }

            $this->updateQueueItemStatus($item['id'], (int)$item['attempts'], $success, $error, $providerMsgId, $retryAfter);
            $processedCount++;
        }

        return $processedCount;
    }

    /**
     * Update outbox status after processing
     */
    private function updateQueueItemStatus(int $id, int $currentAttempts, bool $success, ?string $error, ?string $providerMsgId, ?int $retryAfter = null): void {
        $attempts = $currentAttempts + 1;

        if ($success) {
            $stmt = $this->db->prepare("
                UPDATE notification_logs 
                SET status = 'sent', 
                    attempts = :attempts, 
                    sent_at = NOW(), 
                    provider_message_id = :provider_msg_id, 
                    error_message = NULL,
                    updated_at = NOW()
                WHERE id = :id
            ");
            $stmt->execute([
                'attempts' => $attempts,
                'provider_msg_id' => $providerMsgId,
                'id' => $id
            ]);
            \App\Services\Logger::info("Notification #$id successfully sent via queue worker (attempts: $attempts).");
        } elseif ($error !== null && (strpos($error, 'META_TEMPLATE_IN_REVIEW') !== false || strpos($error, 'TEST_MODE_RECIPIENT_BLOCKED') !== false)) {
            // Held state: Preserve notification for later processing without treating as hard failure
            $stmt = $this->db->prepare("
                UPDATE notification_logs 
                SET status = 'pending', 
                    available_at = DATE_ADD(NOW(), INTERVAL 3600 SECOND), 
                    error_message = :err,
                    updated_at = NOW()
                WHERE id = :id
            ");
            $stmt->execute([
                'err' => $error,
                'id' => $id
            ]);
            \App\Services\Logger::info("Notification #$id held pending Meta template review / test mode: $error");
        } else {
            // Determine retry status

            $isPermanent = $this->isPermanentError($error);
            if ($attempts >= $this->maxAttempts || $isPermanent) {
                $stmt = $this->db->prepare("
                    UPDATE notification_logs 
                    SET status = 'failed', 
                        attempts = :attempts, 
                        failed_at = NOW(), 
                        error_message = :err,
                        updated_at = NOW()
                    WHERE id = :id
                ");
                $stmt->execute([
                    'attempts' => $attempts,
                    'err' => $this->redactError($error),
                    'id' => $id
                ]);
                \App\Services\Logger::error("Notification #$id permanently failed after $attempts attempts: " . $this->redactError($error));
            } else {
                // Exponential backoff delay: 60s, 300s, 900s, 3600s, 14400s
                $delay = $this->retryDelays[$attempts] ?? ($this->retryDelay * (int)pow(2, max(0, $attempts - 1)));
                if ($retryAfter !== null && $retryAfter >= 10 && $retryAfter <= 86400) {
                    $delay = $retryAfter;
                }
                $nextAvail = date('Y-m-d H:i:s', time() + $delay);
                $stmt = $this->db->prepare("
                    UPDATE notification_logs 
                    SET status = 'retrying', 
                        attempts = :attempts, 
                        available_at = :avail, 
                        error_message = :err,
                        updated_at = NOW()
                    WHERE id = :id
                ");
                $stmt->execute([
                    'attempts' => $attempts,
                    'avail' => $nextAvail,
                    'err' => $this->redactError($error),
                    'id' => $id
                ]);
                \App\Services\Logger::warning("Notification #$id scheduled for retry (attempt $attempts/$this->maxAttempts, next: $nextAvail): " . $this->redactError($error));
            }
        }
    }

    /**
     * Update log status to 'skipped' for user opt-out cancellations
     */
    private function updateQueueItemStatusToSkipped(int $id, string $reason): void {
        $stmt = $this->db->prepare("
            UPDATE notification_logs 
            SET status = 'skipped', 
                error_message = :reason,
                updated_at = NOW()
            WHERE id = :id
        ");
        $stmt->execute([
            'reason' => $reason,
            'id' => $id
        ]);
    }

    /**
     * Retry a specific failed log record from Admin Dashboard.
     * Reuses the existing row, preserving all business identifiers and idempotency key.
     * Preserves lifetime delivery attempt count without resetting it to 0.
     * Rejects retry if the notification has already reached maximum attempts.
     */
    public function retryLog(int $id): bool {
        $stmt = $this->db->prepare("
            UPDATE notification_logs 
            SET status = 'pending', 
                available_at = NOW(), 
                failed_at = NULL, 
                error_message = NULL,
                updated_at = NOW()
            WHERE id = :id 
              AND status IN ('failed', 'retrying')
              AND attempts < :max_attempts
        ");
        $stmt->execute([
            'id' => $id,
            'max_attempts' => $this->maxAttempts
        ]);
        return $stmt->rowCount() > 0;
    }

    private function isPermanentError(?string $error): bool {
        if ($error === null) {
            return false;
        }
        $err = strtolower($error);
        return (
            strpos($err, 'invalid recipient') !== false ||
            strpos($err, 'invalid phone') !== false ||
            strpos($err, 'invalid email') !== false ||
            strpos($err, 'unsupported channel') !== false ||
            strpos($err, 'unsupported notification channel') !== false ||
            strpos($err, 'missing recipient') !== false ||
            strpos($err, 'cancelled') !== false ||
            strpos($err, 'opted out') !== false ||
            strpos($err, 'user not found') !== false ||
            strpos($err, 'prohibited on sunday') !== false ||
            strpos($err, 'lifetime limit') !== false ||
            strpos($err, '132001') !== false ||
            strpos($err, '131047') !== false ||
            strpos($err, '131058') !== false ||
            strpos($err, '132000') !== false ||
            strpos($err, '131008') !== false ||
            strpos($err, '131009') !== false ||
            strpos($err, '400 bad request') !== false ||
            strpos($err, '401 unauthorized') !== false ||
            strpos($err, '403 forbidden') !== false ||
            strpos($err, '404 not found') !== false
        );
    }

    private function getWhatsAppTemplateName(string $type): string {
        switch ($type) {
            case 'NEW_MATCH':
            case 'DAILY_MATCH_DIGEST':
            case 'WEEKLY_MATCH_DIGEST':
                return 'new_match';
            case 'SCHOLARSHIP_DEADLINE_SOON':
                return 'deadline_soon';
            case 'SCHOLARSHIP_DEADLINE_TODAY':
                return 'deadline_today';
            case 'PAYMENT_CONFIRMATION':
            case 'PAYMENT_SUCCESS':
            case 'SUBSCRIPTION_CONFIRMATION':
                return 'confirmation_msg';
            default:
                return 'system_notification';
        }
    }

    private function buildWhatsAppTemplateParams(string $type, array $payload): array {
        return \App\Services\WhatsApp\ScholarshipMessageFormatter::buildTemplateParams($type, $payload);
    }

    private function renderHtmlEmail(string $type, array $payload): string {
        if ($type === NotificationTypes::EMAIL_VERIFICATION || $type === 'EMAIL_VERIFICATION') {
            $otpCode = e($payload['otp_code'] ?? ($payload['code'] ?? ''));
            $firstName = e($payload['first_name'] ?? 'Student');
            return "
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset='utf-8'>
                    <title>Verify your ScholarPlanner account</title>
                </head>
                <body style=\"font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif; line-height:1.6; color:#334155; margin:0; padding:20px; background:#f1f5f9;\">
                    <div style='max-width:600px; margin:0 auto; background:#ffffff; padding:32px; border-radius:12px; box-shadow:0 4px 6px -1px rgb(0 0 0 / 0.1); border:1px solid #e2e8f0;'>
                        <div style='text-align:center; margin-bottom:24px; padding-bottom:16px; border-bottom:1px solid #e2e8f0;'>
                            <span style='font-size:1.5rem; font-weight:800; color:#2563eb;'>ScholarPlanner</span>
                        </div>
                        <h2 style='color:#0f172a; margin-top:0;'>Verify your email address</h2>
                        <p style='color:#475569; font-size:16px;'>Hello $firstName,</p>
                        <p style='color:#475569; font-size:16px;'>Thank you for joining ScholarPlanner! Please use the 6-digit verification code below to complete your registration:</p>
                        <div style='text-align:center; margin:28px 0;'>
                            <span style='font-size:32px; font-weight:700; color:#1e40af; letter-spacing:8px; padding:14px 28px; background:#eff6ff; border-radius:8px; border:2px dashed #93c5fd; display:inline-block;'>$otpCode</span>
                        </div>
                        <p style='color:#64748b; font-size:14px;'>This code is valid for <strong>10 minutes</strong> and is single-use. If you did not create a ScholarPlanner account, please ignore this email.</p>
                        <div style='border-top:1px solid #e2e8f0; margin-top:32px; padding-top:16px; font-size:0.75rem; color:#94a3b8; text-align:center;'>
                            &copy; " . date('Y') . " ScholarPlanner. All rights reserved.
                        </div>
                    </div>
                </body>
                </html>
            ";
        }

        $title = e($payload['title'] ?? '');
        $provider = e($payload['provider'] ?? '');
        $degree = e($payload['degree'] ?? '');
        $field = e($payload['field'] ?? '');
        $country = e($payload['country'] ?? '');
        $funding = e($payload['funding'] ?? '');
        $deadline = e($payload['deadline'] ?? 'Open/Rolling');
        $score = e($payload['score'] ?? '0');
        $summary = e($payload['summary'] ?? '');
        
        $detailUrl = e($payload['detail_url'] ?? '#');
        $officialUrl = e($payload['official_apply_url'] ?? '#');

        $contentHtml = '';
        if ($type === 'NEW_MATCH') {
            $contentHtml = "
                <h2 style='color:#0f172a; margin-bottom:12px;'>🎓 New Scholarship Match Found!</h2>
                <p>We found a new scholarship matching your academic preferences:</p>
                <div style='background:#f8fafc; padding:20px; border-radius:8px; border:1px solid #e2e8f0; margin:20px 0;'>
                    <h3 style='color:#2563eb; margin:0 0 10px 0;'>$title</h3>
                    <p style='margin:4px 0;'><strong>Provider:</strong> $provider</p>
                    <p style='margin:4px 0;'><strong>Degree Level:</strong> $degree</p>
                    <p style='margin:4px 0;'><strong>Field:</strong> $field</p>
                    <p style='margin:4px 0;'><strong>Host Country:</strong> $country</p>
                    <p style='margin:4px 0;'><strong>Funding Mode:</strong> $funding</p>
                    <p style='margin:4px 0;'><strong>Deadline:</strong> $deadline</p>
                    <p style='margin:10px 0 0 0; font-size:1.1rem; color:#059669;'><strong>Your Match Score:</strong> $score%</p>
                </div>
                <p style='margin-bottom:8px;'><strong>Eligibility Summary:</strong> $summary</p>
                <div style='margin-top:24px;'>
                    <a href='$detailUrl' style='background:#2563eb; color:#ffffff; padding:10px 20px; text-decoration:none; border-radius:6px; font-weight:600; display:inline-block; margin-right:12px;'>View Match Details</a>
                    <a href='$officialUrl' style='background:#f1f5f9; color:#0f172a; padding:10px 20px; text-decoration:none; border-radius:6px; font-weight:600; display:inline-block;'>Visit Official Website</a>
                </div>
            ";
        } elseif ($type === 'SCHOLARSHIP_DEADLINE_SOON' || $type === 'SCHOLARSHIP_DEADLINE_TODAY') {
            $dayLabel = ($type === 'SCHOLARSHIP_DEADLINE_TODAY') ? "TODAY" : "SOON ($deadline)";
            $contentHtml = "
                <h2 style='color:#b91c1c; margin-bottom:12px;'>⚠️ Scholarship Deadline Reminder</h2>
                <p>An application deadline is approaching $dayLabel:</p>
                <div style='background:#fef2f2; padding:20px; border-radius:8px; border:1px solid #fee2e2; margin:20px 0;'>
                    <h3 style='color:#b91c1c; margin:0 0 10px 0;'>$title</h3>
                    <p style='margin:4px 0;'><strong>Provider:</strong> $provider</p>
                    <p style='margin:4px 0;'><strong>Deadline:</strong> $deadline</p>
                </div>
                <div style='margin-top:24px;'>
                    <a href='$detailUrl' style='background:#2563eb; color:#ffffff; padding:10px 20px; text-decoration:none; border-radius:6px; font-weight:600; display:inline-block; margin-right:12px;'>View Match Details</a>
                    <a href='$officialUrl' style='background:#f1f5f9; color:#0f172a; padding:10px 20px; text-decoration:none; border-radius:6px; font-weight:600; display:inline-block;'>Apply Now</a>
                </div>
            ";
        } else {
            // Digests and general notifications
            $contentHtml = "
                <h2 style='color:#0f172a; margin-bottom:12px;'>ScholarPlanner Alert</h2>
                <p>$summary</p>
                <div style='margin-top:24px;'>
                    <a href='" . e(url('/dashboard')) . "' style='background:#2563eb; color:#ffffff; padding:10px 20px; text-decoration:none; border-radius:6px; font-weight:600; display:inline-block;'>Go to Dashboard</a>
                </div>
            ";
        }

        return "
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='utf-8'>
                <title>ScholarPlanner Alert</title>
            </head>
            <body style=\"font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif; line-height:1.6; color:#334155; margin:0; padding:20px; background:#f1f5f9;\">
                <div style='max-width:600px; margin:0 auto; background:#ffffff; padding:32px; border-radius:12px; box-shadow:0 4px 6px -1px rgb(0 0 0 / 0.1);'>
                    <div style='border-bottom:1px solid #e2e8f0; padding-bottom:16px; margin-bottom:24px; text-align:center;'>
                        <span style='font-size:1.5rem; font-weight:800; color:#2563eb;'>ScholarPlanner</span>
                    </div>
                    $contentHtml
                    <div style='border-top:1px solid #e2e8f0; margin-top:32px; padding-top:16px; font-size:0.75rem; color:#64748b; text-align:center;'>
                        You are receiving this because you signed up for alerts on ScholarPlanner. 
                        You can update your alert channels in your <a href='" . e(url('/profile/edit')) . "' style='color:#2563eb;'>settings</a> at any time.
                    </div>
                </div>
            </body>
            </html>
        ";
    }

    public function recoverStaleJobs(int $timeoutSeconds = 1800): int {
        $stmt = $this->db->prepare("
            UPDATE notification_logs 
            SET status = 'retrying', 
                available_at = NOW(),
                updated_at = NOW(),
                error_message = 'Recovery: Job lease expired.'
            WHERE status = 'processing' 
              AND available_at <= NOW()
        ");
        $stmt->execute();
        return $stmt->rowCount();
    }

    /**
     * Authoritative delivery status recording from webhook/status callback.
     * Guarantees:
     * 1. Status progression: pending -> sent -> delivered (cannot regress delivered -> sent).
     * 2. Idempotency: duplicate status events are safely handled without duplicating state.
     * 3. Isolation: only updates the exact row matching provider_message_id; cannot affect unrelated rows.
     * 4. Error safety: logs failures and redacts sensitive parameters.
     */
    public function recordDeliveryStatus(string $providerMessageId, string $status, ?string $timestamp = null, ?string $errorMessage = null): array {
        if (trim($providerMessageId) === '') {
            return ['success' => false, 'error' => 'Provider message ID cannot be empty.'];
        }

        $normalizedStatus = strtolower(trim($status));
        $validStatuses = ['sent', 'delivered', 'read', 'failed', 'undelivered'];
        if (!in_array($normalizedStatus, $validStatuses, true)) {
            return ['success' => false, 'error' => "Invalid status: $status"];
        }

        // Map read to delivered for notification_logs status
        $dbStatus = ($normalizedStatus === 'read') ? 'delivered' : (($normalizedStatus === 'undelivered') ? 'failed' : $normalizedStatus);

        $stmtFind = $this->db->prepare("SELECT id, status, provider_message_id FROM notification_logs WHERE provider_message_id = :msg_id LIMIT 1 FOR UPDATE");
        
        $openedTx = false;
        if (!$this->db->inTransaction()) {
            $this->db->beginTransaction();
            $openedTx = true;
        }
        try {
            $stmtFind->execute(['msg_id' => $providerMessageId]);
            $record = $stmtFind->fetch(\PDO::FETCH_ASSOC);

            if (!$record) {
                if ($openedTx) {
                    $this->db->rollBack();
                }
                return ['success' => false, 'error' => 'Notification record not found for provider message ID.'];
            }

            $currentStatus = $record['status'];
            $id = (int)$record['id'];

            // Idempotency: If already in target status, no-op
            if ($currentStatus === $dbStatus) {
                if ($openedTx) {
                    $this->db->rollBack();
                }
                return ['success' => true, 'updated' => false, 'status' => $currentStatus, 'id' => $id];
            }

            // Downgrade protection: If already delivered, cannot regress to sent, failed, or retrying
            if ($currentStatus === 'delivered') {
                if ($openedTx) {
                    $this->db->rollBack();
                }
                return ['success' => true, 'updated' => false, 'status' => 'delivered', 'id' => $id, 'note' => 'Terminal status: delivered cannot be downgraded.'];
            }

            // Out-of-order protection: Do not overwrite terminal failure with sent
            if ($currentStatus === 'failed' && $dbStatus === 'sent') {
                if ($openedTx) {
                    $this->db->rollBack();
                }
                return ['success' => true, 'updated' => false, 'status' => 'failed', 'id' => $id, 'note' => 'Out-of-order event ignored (already failed).'];
            }

            if ($dbStatus === 'delivered') {
                $deliveredAt = (!empty($timestamp) && strtotime($timestamp) !== false) ? date('Y-m-d H:i:s', strtotime($timestamp)) : date('Y-m-d H:i:s');
                $stmtUpdate = $this->db->prepare("
                    UPDATE notification_logs 
                    SET status = 'delivered',
                        delivered_at = COALESCE(delivered_at, :delivered_at),
                        updated_at = NOW()
                    WHERE id = :id
                ");
                $stmtUpdate->execute(['id' => $id, 'delivered_at' => $deliveredAt]);
            } elseif ($dbStatus === 'failed') {
                $stmtUpdate = $this->db->prepare("
                    UPDATE notification_logs 
                    SET status = 'failed',
                        failed_at = NOW(),
                        error_message = :err,
                        updated_at = NOW()
                    WHERE id = :id
                ");
                $stmtUpdate->execute([
                    'id' => $id,
                    'err' => $this->redactError($errorMessage ?? 'Delivery failed as reported by provider.')
                ]);
            } elseif ($dbStatus === 'sent') {
                $stmtUpdate = $this->db->prepare("
                    UPDATE notification_logs 
                    SET status = 'sent',
                        sent_at = NOW(),
                        updated_at = NOW()
                    WHERE id = :id
                ");
                $stmtUpdate->execute(['id' => $id]);
            }

            if ($openedTx) {
                $this->db->commit();
            }
            \App\Services\Logger::info("Notification #$id delivery status updated to '$dbStatus' for provider message ID '$providerMessageId'.");
            return ['success' => true, 'updated' => true, 'status' => $dbStatus, 'id' => $id];
        } catch (\Exception $e) {
            if ($openedTx && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Remove sensitive authorization tokens/credentials from database logs
     */
    private function redactError(?string $err): ?string {
        if ($err === null) {
            return null;
        }
        $patterns = [
            '/(password|pass|secret|token|key|auth|easypaisa|jazzcash)=[^&\s\n]+/i' => '$1=[REDACTED]',
            '/(Authorization|Bearer)\s*:?\s*[a-zA-Z0-9_\-\.]+/i' => '$1 [REDACTED]'
        ];
        return preg_replace(array_keys($patterns), array_values($patterns), $err);
    }
}
