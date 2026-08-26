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
}
