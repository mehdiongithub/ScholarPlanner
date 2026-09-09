<?php

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo "Access Denied: CLI runtime execution context only.\n";
    exit(1);
}

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}
require_once ROOT_PATH . '/vendor/autoload.php';

try {
    if (file_exists(ROOT_PATH . '/.env')) {
        $dotenv = \Dotenv\Dotenv::createImmutable(ROOT_PATH);
        $dotenv->load();
    }
} catch (\Exception $e) {
    // Fail silently
}

use App\Services\Database;
use App\Services\NotificationService;
use App\Services\NotificationQueueService;

echo "Starting Deadline Reminders process...\n";

$db = Database::connection();
$lockStmt = $db->prepare("SELECT GET_LOCK('cron_deadline_reminders', 0)");
$lockStmt->execute();
if ((int)$lockStmt->fetchColumn() !== 1) {
    echo "ℹ Another deadline reminders process is currently running. Exiting.\n";
    exit(0);
}

try {
$notificationService = new NotificationService();
$queueService = new NotificationQueueService();

// 1. Fetch all active users with their explicit reminder preferences
$stmt = $db->query("
    SELECT u.id, u.email, u.first_name, 
           COALESCE(up.deadline_reminder_scope, 'off') as deadline_reminder_scope,
           COALESCE(up.deadline_reminder_days, '3,1') as deadline_reminder_days
    FROM users u
    LEFT JOIN user_preferences up ON u.id = up.user_id
    WHERE u.status = 'active'
");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalEnqueued = 0;

foreach ($users as $user) {
    $userId = (int)$user['id'];
    $scope = strtolower(trim($user['deadline_reminder_scope']));

    // CRITICAL USER TRUST RULE:
    // If deadline_reminder_scope is 'off' (or empty/default), ZERO reminders are ever generated.
    // This remains strictly true even if user has active application trackers, bookmarks, or matching scholarships.
    if ($scope === 'off' || empty($scope)) {
        continue;
    }

    // Parse user reminder days (e.g. "7,3,1")
    $userDays = array_unique(array_filter(array_map('intval', explode(',', $user['deadline_reminder_days']))));
    if (empty($userDays)) {
        $userDays = [3, 1];
    }

    if ($scope === 'all') {
        // Option B: Send deadline reminders for all eligible scholarships
        foreach ($userDays as $days) {
            $targetDate = date('Y-m-d', strtotime("+$days days"));

            $stmtEligible = $db->prepare("
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

                // Deterministic business event key: USER + SCHOLARSHIP + REMINDER_OFFSET + DEADLINE_DATE
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
        // Option C: Send deadline reminders ONLY for scholarships explicitly selected by user in user_scholarship_reminders
        $stmtSelected = $db->prepare("
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

            // Use custom days if specified, otherwise user default days
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
} finally {
    $db->query("SELECT RELEASE_LOCK('cron_deadline_reminders')");
}

// Finished enqueuing deadline reminder notifications without delivering
echo "Deadline Reminders process completed. Processed with user preferences, total reminder events queued: {$totalEnqueued}.\n";
