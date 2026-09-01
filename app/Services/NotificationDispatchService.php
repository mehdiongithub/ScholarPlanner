<?php

namespace App\Services;

use PDO;
use Exception;
use App\Services\WhatsApp\WhatsAppProviderInterface;
use App\Services\WhatsApp\WacrmWhatsAppProvider;

class NotificationDispatchService {
    private PDO $db;
    private WhatsAppProviderInterface $provider;
    private int $maxAttempts;
    private int $retryDelay;

    public function __construct(
        ?PDO $db = null,
        ?WhatsAppProviderInterface $provider = null,
        int $maxAttempts = 3,
        int $retryDelay = 300
    ) {
        $this->db = $db ?? Database::connection();
        $this->provider = $provider ?? new WacrmWhatsAppProvider();
        $this->maxAttempts = $maxAttempts;
        $this->retryDelay = $retryDelay;
    }

    /**
     * Dispatch a batch of claimed notification records.
     */
    public function dispatchBatch(array $claimedItems, ?string $claimToken = null): array {
        $metrics = [
            'total' => count($claimedItems),
            'sent' => 0,
            'failed' => 0,
            'cancelled' => 0,
            'retrying' => 0,
            'skipped' => 0
        ];

        foreach ($claimedItems as $item) {
            $result = $this->dispatchItem($item, $claimToken);
            $status = $result['status'] ?? 'unknown';

            if (isset($metrics[$status])) {
                $metrics[$status]++;
            } else {
                $metrics['skipped']++;
            }
        }

        return $metrics;
    }

    /**
     * Dispatch an individual claimed notification item.
     */
    public function dispatchItem(array $item, ?string $claimToken = null): array {
        $id = (int)($item['id'] ?? 0);
        if ($id <= 0) {
            return ['status' => 'skipped', 'error' => 'Invalid notification ID.'];
        }

        // 1. Concurrency & Ownership Verification
        $stmtLock = $this->db->prepare("
            SELECT id, user_id, scholarship_id, notification_type, channel, recipient, payload, status, attempts, provider_message_id 
            FROM notification_logs 
            WHERE id = :id 
            LIMIT 1
        ");
        $stmtLock->execute(['id' => $id]);
        $currentRecord = $stmtLock->fetch(PDO::FETCH_ASSOC);

        if (!$currentRecord || $currentRecord['status'] !== 'processing') {
            Logger::warning("Dispatch skipped for notification #{$id}: Record is not in processing state (status: " . ($currentRecord['status'] ?? 'missing') . ").");
            return ['status' => 'skipped', 'error' => 'Record not in processing state.'];
        }

        // If a claim token was provided, verify it matches
        $expectedToken = $claimToken ?? ($item['provider_message_id'] ?? null);
        if ($expectedToken !== null && $currentRecord['provider_message_id'] !== $expectedToken) {
            Logger::warning("Dispatch skipped for notification #{$id}: Claim token mismatch.");
            return ['status' => 'skipped', 'error' => 'Claim token ownership mismatch.'];
        }

        // 2. Pre-flight Eligibility Re-check
        $eligibility = $this->recheckEligibility($currentRecord);
        if (!$eligibility['valid']) {
            $reason = $eligibility['reason'];
            $this->markCancelled($id, $reason);
            Logger::info("Notification #{$id} cancelled before dispatch: {$reason}");
            return ['status' => 'cancelled', 'reason' => $reason];
        }

        // 3. Template & Parameter Resolution
        $type = $currentRecord['notification_type'];
        $templateName = $this->resolveTemplateName($type);
        $params = $this->buildTemplateParams($currentRecord, $eligibility['scholarship']);
        $recipient = $eligibility['recipient'];

        // 4. Invoke WhatsApp Provider
        try {
            $res = $this->provider->sendTemplateMessage($recipient, $templateName, $params);
        } catch (Exception $e) {
            $res = [
                'success' => false,
                'message_id' => null,
                'error' => 'Provider Exception: ' . $e->getMessage(),
                'retry_after' => null
            ];
        }

        // 5. Success / Failure Transition
        if (!empty($res['success'])) {
            $messageId = $res['message_id'] ?? 'wacrm_' . bin2hex(random_bytes(8));
            $this->markSent($id, $messageId, (int)$currentRecord['attempts'] + 1);
            Logger::info("Notification #{$id} sent successfully via WACRM. Message ID: {$messageId}");
            return [
                'status' => 'sent',
                'notification_id' => $id,
                'message_id' => $messageId
            ];
        }

        // Handle Failure with Retry Policy
        return $this->handleFailure($currentRecord, $res);
    }

    /**
     * Re-verify candidate eligibility immediately before dispatch.
     */
    public function recheckEligibility(array $item): array {
        $userId = (int)($item['user_id'] ?? 0);
        $schId = (int)($item['scholarship_id'] ?? 0);
        $type = $item['notification_type'] ?? '';

        // 1. User Existence & Active Status
        $stmtUser = $this->db->prepare("SELECT id, phone, whatsapp_phone, whatsapp_opt_in, status FROM users WHERE id = :id LIMIT 1");
        $stmtUser->execute(['id' => $userId]);
        $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

        if (!$user || $user['status'] !== 'active') {
            return ['valid' => false, 'reason' => 'User does not exist or account is inactive.', 'user' => null, 'scholarship' => null, 'recipient' => ''];
        }

        // 2. Paid Subscription Gate
        if (!SubscriptionService::can($userId, 'whatsapp_alerts')) {
            return ['valid' => false, 'reason' => 'User no longer has an active paid subscription.', 'user' => $user, 'scholarship' => null, 'recipient' => ''];
        }

        // 3. Opt-in Preference Check
        $notifService = new NotificationService();
        if (!$notifService->hasWhatsAppOptIn($userId, $type)) {
            return ['valid' => false, 'reason' => 'User disabled WhatsApp alerts or opted out before dispatch.', 'user' => $user, 'scholarship' => null, 'recipient' => ''];
        }

        // 4. Phone Number Normalization
        $rawPhone = $user['whatsapp_phone'] ?: ($user['phone'] ?: ($item['recipient'] ?? ''));
        $normalized = WacrmWhatsAppProvider::normalizePhoneNumber($rawPhone);
        if ($normalized === null) {
            return ['valid' => false, 'reason' => 'Invalid recipient phone number format.', 'user' => $user, 'scholarship' => null, 'recipient' => ''];
        }

        // 5. Scholarship Validity (if attached)
        $sch = null;
        if ($schId > 0) {
            $stmtSch = $this->db->prepare("
                SELECT s.id, s.title, s.provider_name, s.funding_type, s.application_deadline, s.slug, s.status, c.name as country_name
                FROM scholarships s
                LEFT JOIN countries c ON s.country_id = c.id
                WHERE s.id = :id 
                LIMIT 1
            ");
            $stmtSch->execute(['id' => $schId]);
            $sch = $stmtSch->fetch(PDO::FETCH_ASSOC);

            if (!$sch || $sch['status'] !== 'published') {
                return ['valid' => false, 'reason' => 'Scholarship is unpublished, draft, or deleted.', 'user' => $user, 'scholarship' => null, 'recipient' => $normalized];
            }

            if ($sch['application_deadline'] !== null && strtotime($sch['application_deadline']) < strtotime(date('Y-m-d'))) {
                return ['valid' => false, 'reason' => 'Scholarship application deadline has passed.', 'user' => $user, 'scholarship' => $sch, 'recipient' => $normalized];
            }
        }

        // 6. Admin Settings Toggle Check
        $scheduler = new NotificationSchedulerService($this->db);
        $settings = $scheduler->getSettings();
        if (!$settings['whatsapp_notifications_enabled']) {
            return ['valid' => false, 'reason' => 'WhatsApp notifications disabled in system settings.', 'user' => $user, 'scholarship' => $sch, 'recipient' => $normalized];
        }
        if ($type === NotificationTypes::NEW_MATCH && !$settings['whatsapp_new_match_enabled']) {
            return ['valid' => false, 'reason' => 'NEW_MATCH notifications disabled in system settings.', 'user' => $user, 'scholarship' => $sch, 'recipient' => $normalized];
        }
        if ($type === NotificationTypes::DEADLINE_REMINDER && !$settings['whatsapp_deadline_reminder_enabled']) {
            return ['valid' => false, 'reason' => 'DEADLINE_REMINDER notifications disabled in system settings.', 'user' => $user, 'scholarship' => $sch, 'recipient' => $normalized];
        }

        return [
            'valid' => true,
            'reason' => 'Eligible',
            'user' => $user,
            'scholarship' => $sch,
            'recipient' => $normalized
        ];
    }

    /**
     * Resolve template identifier based on notification type.
     */
    private function resolveTemplateName(string $type): string {
        switch ($type) {
            case NotificationTypes::NEW_MATCH:
                return 'new_scholarship_match';
            case NotificationTypes::DEADLINE_REMINDER:
            case NotificationTypes::SCHOLARSHIP_DEADLINE_SOON:
                return 'deadline_reminder_soon';
            case NotificationTypes::SCHOLARSHIP_DEADLINE_TODAY:
                return 'deadline_reminder_today';
            default:
                return 'new_scholarship_match';
        }
    }

    /**
     * Build template parameters from trusted scholarship record and payload.
     */
    public function buildTemplateParams(array $item, ?array $scholarship): array {
        $payload = [];
        if (!empty($item['payload'])) {
            $payload = is_array($item['payload']) ? $item['payload'] : (json_decode($item['payload'], true) ?: []);
        }

        $title = $scholarship['title'] ?? ($payload['title'] ?? 'Scholarship Opportunity');
        $provider = $scholarship['provider_name'] ?? ($payload['provider'] ?? 'Scholarship Provider');
        $deadline = 'Open/Rolling';
        if (!empty($scholarship['application_deadline'])) {
            $deadline = date('Y-m-d', strtotime($scholarship['application_deadline']));
        } elseif (!empty($payload['deadline'])) {
            $deadline = date('Y-m-d', strtotime($payload['deadline']));
        }

        $slug = $scholarship['slug'] ?? ($payload['slug'] ?? '');
        $detailUrl = !empty($slug) ? url('/scholarships/' . $slug) : ($payload['detail_url'] ?? url('/dashboard'));
        $country = $scholarship['country_name'] ?? ($payload['country'] ?? 'International');
        $funding = $scholarship['funding_type'] ?? ($payload['funding'] ?? 'Fully Funded');

        return [
            $title,
            $provider,
            $deadline,
            $detailUrl,
            $country,
            $funding
        ];
    }

    /**
     * Handle provider failure with retry categorization.
     */
    private function handleFailure(array $item, array $result): array {
        $id = (int)$item['id'];
        $attempts = (int)($item['attempts'] ?? 0) + 1;
        $errorMsg = $result['error'] ?? 'Unknown WACRM error.';
        $retryAfter = $result['retry_after'] ?? null;

        $isRetryable = $this->isRetryableError($result);

        if ($isRetryable && $attempts < $this->maxAttempts) {
            $delay = ($retryAfter !== null && $retryAfter >= 10 && $retryAfter <= 86400) ? $retryAfter : $this->retryDelay;
            $nextAvail = date('Y-m-d H:i:s', time() + $delay);

            $stmtRetry = $this->db->prepare("
                UPDATE notification_logs 
                SET status = 'pending',
                    attempts = :attempts,
                    available_at = :avail,
                    error_message = :err,
                    updated_at = NOW()
                WHERE id = :id
            ");
            $stmtRetry->execute([
                'attempts' => $attempts,
                'avail' => $nextAvail,
                'err' => $errorMsg,
                'id' => $id
            ]);

            Logger::warning("Notification #{$id} failed transiently (Attempt {$attempts}/{$this->maxAttempts}). Scheduled retry at {$nextAvail}. Error: {$errorMsg}");

            return [
                'status' => 'retrying',
                'attempts' => $attempts,
                'next_available' => $nextAvail,
                'error' => $errorMsg
            ];
        }

        // Permanent Failure or Max Attempts Exceeded
        $stmtFail = $this->db->prepare("
            UPDATE notification_logs 
            SET status = 'failed',
                attempts = :attempts,
                failed_at = NOW(),
                error_message = :err,
                updated_at = NOW()
            WHERE id = :id
        ");
        $stmtFail->execute([
            'attempts' => $attempts,
            'err' => $errorMsg,
            'id' => $id
        ]);

        Logger::error("Notification #{$id} marked permanently failed after {$attempts} attempt(s). Error: {$errorMsg}");

        return [
            'status' => 'failed',
            'attempts' => $attempts,
            'error' => $errorMsg
        ];
    }

    /**
     * Check if a provider error is transient/retryable.
     */
    public function isRetryableError(array $result): bool {
        $error = strtolower($result['error'] ?? '');

        // HTTP 429 rate limit
        if (strpos($error, '429') !== false || strpos($error, 'rate_limited') !== false || ($result['retry_after'] ?? null) !== null) {
            return true;
        }

        // Server errors (500, 502, 503)
        if (strpos($error, '500') !== false || strpos($error, '502') !== false || strpos($error, '503') !== false) {
            return true;
        }

        // Network / timeout errors
        if (
            strpos($error, 'timeout') !== false || 
            strpos($error, 'timed out') !== false || 
            strpos($error, 'connection refused') !== false || 
            strpos($error, 'could not resolve host') !== false ||
            strpos($error, 'curl error: 28') !== false ||
            strpos($error, 'curl error: 7') !== false
        ) {
            return true;
        }

        return false;
    }

    /**
     * Mark notification sent.
     */
    private function markSent(int $id, string $messageId, int $attempts): void {
        $stmt = $this->db->prepare("
            UPDATE notification_logs 
            SET status = 'sent',
                attempts = :attempts,
                sent_at = NOW(),
                provider = 'wacrm',
                provider_message_id = :msg_id,
                error_message = NULL,
                updated_at = NOW()
            WHERE id = :id
        ");
        $stmt->execute([
            'attempts' => $attempts,
            'msg_id' => $messageId,
            'id' => $id
        ]);
    }

    /**
     * Mark notification cancelled due to pre-flight ineligibility.
     */
    private function markCancelled(int $id, string $reason): void {
        $stmt = $this->db->prepare("
            UPDATE notification_logs 
            SET status = 'cancelled',
                error_message = :err,
                updated_at = NOW()
            WHERE id = :id
        ");
        $stmt->execute([
            'err' => 'Cancelled: ' . $reason,
            'id' => $id
        ]);
    }
}
