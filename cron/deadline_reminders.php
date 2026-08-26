<?php

require_once dirname(__DIR__) . '/tests/bootstrap.php';

use App\Services\Database;
use App\Services\NotificationService;
use App\Services\NotificationQueueService;

if (php_sapi_name() !== 'cli') {
    die("This script must be run via the command line.\n");
}

echo "Starting Deadline Reminders process...\n";

$db = Database::connection();
$notificationService = new NotificationService();
$queueService = new NotificationQueueService();

$reminderDaysStr = $_ENV['DEADLINE_REMINDER_DAYS'] ?? '14,7,3,1';
$reminderDays = array_map('intval', explode(',', $reminderDaysStr));

echo "Configured reminder offsets: " . implode(', ', $reminderDays) . " days.\n";

foreach ($reminderDays as $days) {
    $targetDate = date('Y-m-d', strtotime("+$days days"));
    echo "Checking scholarships closing on $targetDate ($days days from now)...\n";

    $stmt = $db->prepare("
        SELECT s.*, c.name as country_name 
        FROM scholarships s
        LEFT JOIN countries c ON s.country_id = c.id
        WHERE s.status = 'published'
          AND s.application_deadline IS NOT NULL
          AND DATE(s.application_deadline) = :target_date
    ");
    $stmt->execute(['target_date' => $targetDate]);
    $scholarships = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Found " . count($scholarships) . " scholarships closing on $targetDate.\n";

    foreach ($scholarships as $s) {
        $sid = (int)$s['id'];
        $deadlineUnix = strtotime($s['application_deadline']);

        // Find users who are ELIGIBLE for this scholarship, OR who have an active application tracker for this scholarship
        $stmtUsers = $db->prepare("
            SELECT DISTINCT u.id as user_id, COALESCE(sm.match_score, 70) as match_score
            FROM users u
            LEFT JOIN scholarship_matches sm ON sm.user_id = u.id AND sm.scholarship_id = :sid1
            LEFT JOIN scholarship_applications sa ON sa.user_id = u.id AND sa.scholarship_id = :sid2
            WHERE (sm.eligibility_status = 'ELIGIBLE' AND sm.scholarship_id = :sid3)
               OR (sa.status IN ('interested', 'planning', 'documents_pending', 'ready_to_apply') AND sa.scholarship_id = :sid4)
        ");
        $stmtUsers->execute([
            'sid1' => $sid,
            'sid2' => $sid,
            'sid3' => $sid,
            'sid4' => $sid
        ]);
        $matches = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

        foreach ($matches as $match) {
            $userId = (int)$match['user_id'];

            // Build unique key containing the deadline timestamp. If deadline changes, a new reminder is allowed.
            $idempotencyKey = "deadline_reminder_{$userId}_{$sid}_{$days}_{$deadlineUnix}";

            $type = ($days === 1) ? 'SCHOLARSHIP_DEADLINE_TODAY' : 'SCHOLARSHIP_DEADLINE_SOON';

            $payload = [
                'title' => $s['title'],
                'provider' => $s['provider_name'],
                'degree' => 'Master\'s',
                'field' => 'Computer Science',
                'country' => $s['country_name'] ?? 'Multiple Countries',
                'funding' => $s['funding_type'],
                'deadline' => date('Y-m-d', $deadlineUnix),
                'score' => $match['match_score'],
                'summary' => "Reminder: The application deadline for {$s['title']} is in {$days} day(s)!",
                'detail_url' => url('/scholarships/' . $s['slug']),
                'official_apply_url' => $s['official_application_url'] ?? $s['official_website'] ?? ''
            ];

            $notificationService->sendNotification($userId, $type, $payload, $sid, $idempotencyKey);
        }
    }
}

// Process queue items immediately after enqueuing
echo "Processing notification queue...\n";
$processed = $queueService->processQueue();
echo "Deadline Reminders process completed. Dispatched $processed pending notifications.\n";
