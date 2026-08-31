<?php

namespace App\Services;

use App\Services\Database;
use PDO;

class NotificationService {
    private PDO $db;
    private NotificationQueueService $queueService;

    public function __construct() {
        $this->db = Database::connection();
        $this->queueService = new NotificationQueueService();
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

        // Populate details if missing from payload data
        if ($scholarshipId) {
            if (empty($payloadData['title'])) {
                $stmtSch = $this->db->prepare("SELECT title, provider_name, funding_type, application_deadline, slug FROM scholarships WHERE id = :id LIMIT 1");
                $stmtSch->execute(['id' => $scholarshipId]);
                $sch = $stmtSch->fetch(PDO::FETCH_ASSOC);
                if ($sch) {
                    $payloadData['title'] = $sch['title'];
                    $payloadData['provider'] = $sch['provider_name'];
                    $payloadData['funding'] = $sch['funding_type'];
                    $payloadData['deadline'] = $sch['application_deadline'];
                    $payloadData['slug'] = $sch['slug'];
                }
            }

            if (!empty($payloadData['slug']) && empty($payloadData['detail_url'])) {
                $payloadData['detail_url'] = url('/scholarships/' . $payloadData['slug']);
            }
        }

        // 4. Enqueue Email Channel
        if ($sendEmail && !empty($user['email'])) {
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

        // 5. Enqueue WhatsApp Channel
        $whatsappRecipient = $user['whatsapp_phone'] ?: $user['phone'];
        if ($sendWhatsapp && !empty($whatsappRecipient)) {
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
}
