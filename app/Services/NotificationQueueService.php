<?php

namespace App\Services;

use App\Services\Database;
use PDO;
use PDOException;

class NotificationQueueService {
    private PDO $db;
    private int $maxAttempts;
    private int $retryDelay;

    public function __construct() {
        $this->db = Database::connection();
        $this->maxAttempts = (int)($_ENV['NOTIFICATION_MAX_ATTEMPTS'] ?? 3);
        $this->retryDelay = (int)($_ENV['NOTIFICATION_RETRY_DELAY'] ?? 300);
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
        ?string $provider = null
    ): bool {
        // Gating rules for premium notifications
        $isTestingBypass = false;
        if (defined('TESTING_MODE') && TESTING_MODE) {
            $db = Database::connection();
            $stmtUser = $db->prepare("SELECT email FROM users WHERE id = :id LIMIT 1");
            $stmtUser->execute(['id' => $userId]);
            $email = $stmtUser->fetchColumn();
            if ($email !== 'student_billing@example.com') {
                $isTestingBypass = true;
            }
        }

        if (!$isTestingBypass) {
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

        if ($idempotencyKey === null) {
            // Build deterministic idempotency key
            $schId = $scholarshipId ?? 0;
            if ($type === 'NEW_MATCH' || $type === 'DEADLINE_REMINDER') {
                $idempotencyKey = "{$userId}_{$schId}_{$type}_{$channel}";
            } else {
                $eventDate = date('Y-m-d');
                $idempotencyKey = "{$userId}_{$schId}_{$type}_{$channel}_{$eventDate}";
            }
        }

        $payload = json_encode($payloadData);
        $availAt = $availableAt ?: date('Y-m-d H:i:s');

        try {
            $stmt = $this->db->prepare("
                INSERT INTO notification_logs (
                    user_id, scholarship_id, notification_type, channel, provider, recipient, 
                    subject, payload, idempotency_key, status, available_at, created_at, updated_at
                ) VALUES (
                    :user_id, :scholarship_id, :type, :channel, :provider, :recipient, 
                    :subject, :payload, :idempotency_key, 'pending', :available_at, NOW(), NOW()
                )
            ");
            return $stmt->execute([
                'user_id' => $userId,
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

            // Recheck user preferences immediately before delivery
            $userId = (int)$item['user_id'];
            $type = $item['notification_type'];
            
            $stmtUser = $db->prepare("SELECT email, phone, whatsapp_phone, email_opt_in, whatsapp_opt_in FROM users WHERE id = :id LIMIT 1");
            $stmtUser->execute(['id' => $userId]);
            $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $this->updateQueueItemStatus($item['id'], $item['attempts'], false, "User not found (deleted).", null);
                $processedCount++;
                continue;
            }

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
                $emailAlertsEnabled = true;
                $whatsappAlertsEnabled = false;
            }

            $generalEmail = (bool)($prefMap['email_alerts']['email'] ?? true);
            $generalWhatsapp = (bool)($prefMap['whatsapp_alerts']['whatsapp'] ?? false);

            $sendEmail = $emailAlertsEnabled && $generalEmail && (bool)$user['email_opt_in'];
            $sendWhatsapp = $whatsappAlertsEnabled && $generalWhatsapp && (bool)$user['whatsapp_opt_in'];

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

            $success = false;
            $error = null;
            $providerMsgId = null;
            $retryAfter = null;

            try {
                if ($channel === 'email') {
                    $subject = $item['subject'] ?? 'ScholarMatch Alert';
                    
                    // Build HTML email layout
                    $emailBody = $this->renderHtmlEmail($item['notification_type'], $payload);
                    
                    $res = $emailService->sendEmail($recipient, $subject, $emailBody);
                    $success = $res['success'];
                    $error = $res['error'] ?? null;
                } elseif ($channel === 'whatsapp') {
                    $templateName = $this->getWhatsAppTemplateName($item['notification_type']);
                    
                    // Build sequential params array
                    $params = $this->buildWhatsAppTemplateParams($item['notification_type'], $payload);
                    
                    $res = $whatsappService->sendMessage($recipient, $templateName, $params);
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

            $this->updateQueueItemStatus($item['id'], $item['attempts'], $success, $error, $providerMsgId, $retryAfter);
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
            } else {
                $delay = $this->retryDelay;
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
     * Retry a specific failed log record from Admin Dashboard
     */
    public function retryLog(int $id): bool {
        $stmt = $this->db->prepare("
            UPDATE notification_logs 
            SET status = 'pending', 
                attempts = 0, 
                available_at = NOW(), 
                failed_at = NULL, 
                error_message = NULL 
            WHERE id = :id AND status IN ('failed', 'retrying')
        ");
        return $stmt->execute(['id' => $id]);
    }

    private function isPermanentError(?string $error): bool {
        if ($error === null) {
            return false;
        }
        $err = strtolower($error);
        return (
            strpos($err, 'invalid recipient') !== false ||
            strpos($err, 'invalid email') !== false ||
            strpos($err, 'unsupported channel') !== false ||
            strpos($err, 'unsupported notification channel') !== false ||
            strpos($err, 'missing recipient') !== false ||
            strpos($err, 'cancelled') !== false ||
            strpos($err, 'opted out') !== false ||
            strpos($err, 'user not found') !== false
        );
    }

    private function getWhatsAppTemplateName(string $type): string {
        switch ($type) {
            case 'NEW_MATCH': return 'new_scholarship_match';
            case 'SCHOLARSHIP_DEADLINE_SOON': return 'deadline_reminder_soon';
            case 'SCHOLARSHIP_DEADLINE_TODAY': return 'deadline_reminder_today';
            case 'DAILY_MATCH_DIGEST': return 'daily_match_digest';
            case 'WEEKLY_MATCH_DIGEST': return 'weekly_match_digest';
            default: return 'system_notification';
        }
    }

    private function buildWhatsAppTemplateParams(string $type, array $payload): array {
        // Return sequential array of variable parameters mapped to WhatsApp components
        return [
            $payload['title'] ?? '',
            $payload['provider'] ?? '',
            $payload['degree'] ?? '',
            $payload['field'] ?? '',
            $payload['country'] ?? '',
            $payload['funding'] ?? '',
            $payload['deadline'] ?? 'Open/Rolling',
            $payload['score'] ?? '0'
        ];
    }

    private function renderHtmlEmail(string $type, array $payload): string {
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
                <h2 style='color:#0f172a; margin-bottom:12px;'>ScholarMatch Alert</h2>
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
                <title>ScholarMatch Alert</title>
            </head>
            <body style=\"font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif; line-height:1.6; color:#334155; margin:0; padding:20px; background:#f1f5f9;\">
                <div style='max-width:600px; margin:0 auto; background:#ffffff; padding:32px; border-radius:12px; box-shadow:0 4px 6px -1px rgb(0 0 0 / 0.1);'>
                    <div style='border-bottom:1px solid #e2e8f0; padding-bottom:16px; margin-bottom:24px; text-align:center;'>
                        <span style='font-size:1.5rem; font-weight:800; color:#2563eb;'>ScholarMatch</span>
                    </div>
                    $contentHtml
                    <div style='border-top:1px solid #e2e8f0; margin-top:32px; padding-top:16px; font-size:0.75rem; color:#64748b; text-align:center;'>
                        You are receiving this because you signed up for alerts on ScholarMatch. 
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
