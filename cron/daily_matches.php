<?php

require_once dirname(__DIR__) . '/tests/bootstrap.php';

use App\Services\Database;
use App\Services\ScholarshipMatchingService;
use App\Services\NotificationService;
use App\Services\NotificationQueueService;

if (php_sapi_name() !== 'cli') {
    die("This script must be run via the command line.\n");
}

echo "Starting Daily Matches process...\n";

$db = Database::connection();
$matchingService = new ScholarshipMatchingService();
$notificationService = new NotificationService();
$queueService = new NotificationQueueService();

$batchSize = (int)($_ENV['DAILY_MATCH_BATCH_SIZE'] ?? 100);

// Find active visitor users
$stmt = $db->prepare("
    SELECT id, email, first_name FROM users 
    WHERE role_id = (SELECT id FROM roles WHERE name = 'visitor' LIMIT 1)
      AND status = 'active'
");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($users) . " active visitors.\n";

$userBatches = array_chunk($users, $batchSize);

foreach ($userBatches as $batchIndex => $batch) {
    echo "Processing batch " . ($batchIndex + 1) . " of " . count($userBatches) . "...\n";

    foreach ($batch as $user) {
        $userId = (int)$user['id'];

        try {
            // 1. Recalculate matches for this user
            $matchingService->recalculateForUser($userId);

            // 2. Fetch matches for user where they are ELIGIBLE
            $stmtMatches = $db->prepare("
                SELECT m.*, s.title, s.provider_name, s.funding_type, s.application_deadline, s.slug, c.name as country_name 
                FROM scholarship_matches m
                JOIN scholarships s ON m.scholarship_id = s.id
                LEFT JOIN countries c ON s.country_id = c.id
                WHERE m.user_id = :uid 
                  AND m.eligibility_status = 'ELIGIBLE'
                  AND s.status = 'published'
            ");
            $stmtMatches->execute(['uid' => $userId]);
            $matches = $stmtMatches->fetchAll(PDO::FETCH_ASSOC);

            foreach ($matches as $match) {
                $schId = (int)$match['scholarship_id'];

                // Unique idempotency key ensures a user only gets enqueued ONCE for a given scholarship match
                $idempotencyKey = "new_match_{$userId}_{$schId}";

                $payload = [
                    'title' => $match['title'],
                    'provider' => $match['provider_name'],
                    'degree' => 'Master\'s',
                    'field' => 'Computer Science',
                    'country' => $match['country_name'] ?? 'Multiple Countries',
                    'funding' => $match['funding_type'],
                    'deadline' => $match['application_deadline'] ? date('Y-m-d', strtotime($match['application_deadline'])) : 'Open/Rolling',
                    'score' => $match['match_score'],
                    'summary' => 'Congratulations! You are eligible for this opportunity.',
                    'detail_url' => url('/scholarships/' . $match['slug']),
                    'official_apply_url' => $match['official_application_url'] ?? $match['official_website'] ?? ''
                ];

                $notificationService->sendNotification($userId, 'NEW_MATCH', $payload, $schId, $idempotencyKey);
            }

        } catch (\Exception $e) {
            echo "Error processing user ID $userId: " . $e->getMessage() . "\n";
        }
    }
}

// Process queue items immediately after enqueuing
echo "Processing notification queue...\n";
$processed = $queueService->processQueue();
echo "Daily Matches process completed. Dispatched $processed pending notifications.\n";
