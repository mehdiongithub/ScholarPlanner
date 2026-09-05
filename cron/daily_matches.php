<?php

require_once dirname(__DIR__) . '/tests/bootstrap.php';

use App\Services\Database;
use App\Services\ScholarshipMatchingService;
use App\Services\NotificationService;
use App\Services\NotificationQueueService;
use App\Services\SubscriptionService;

if (php_sapi_name() !== 'cli') {
    die("This script must be run via the command line.\n");
}

echo "Starting Daily Matches process...\n";

$db = Database::connection();
$matchingService = new ScholarshipMatchingService();
$notificationService = new NotificationService();
$queueService = new NotificationQueueService();

$batchSize = (int)($_ENV['DAILY_MATCH_BATCH_SIZE'] ?? 100);
$calendarDay = \App\Services\NotificationService::getKarachiCalendarDay();
$isSunday = \App\Services\NotificationService::$simulateSunday !== null 
    ? \App\Services\NotificationService::$simulateSunday 
    : (((int)\App\Services\NotificationService::getKarachiDateTime()->format('w') === 0 && !defined('BYPASS_SUNDAY_RULE')) || (defined('SIMULATE_SUNDAY') && SIMULATE_SUNDAY));

// Find active visitor users (and users without explicit staff role)
$stmt = $db->prepare("
    SELECT id, email, first_name, phone, whatsapp_phone, email_opt_in, whatsapp_opt_in 
    FROM users 
    WHERE (role_id = (SELECT id FROM roles WHERE name = 'visitor' LIMIT 1) OR role_id IS NULL)
      AND status = 'active'
");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($users) . " active users.\n";

$userBatches = array_chunk($users, $batchSize);

foreach ($userBatches as $batchIndex => $batch) {
    echo "Processing batch " . ($batchIndex + 1) . " of " . count($userBatches) . "...\n";

    foreach ($batch as $user) {
        $userId = (int)$user['id'];

        try {
            // 1. Recalculate matches for this user
            $matchingService->recalculateForUser($userId);

            // 2. Fetch matches for user where they are ELIGIBLE and scholarship is published AND verified
            $stmtMatches = $db->prepare("
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
                // If no scholarships match, send nothing
                continue;
            }

            // 3. User channel preferences & opt-in resolution
            $stmtUp = $db->prepare("SELECT preferred_channel, allow_multi_channel FROM user_preferences WHERE user_id = :uid LIMIT 1");
            $stmtUp->execute(['uid' => $userId]);
            $userPref = $stmtUp->fetch(PDO::FETCH_ASSOC) ?: [];

            $preferredChannel = $userPref['preferred_channel'] ?? 'email';
            $allowMultiChannel = (bool)($userPref['allow_multi_channel'] ?? 0);

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

            $matchAlertsEmail = $prefMap['matching_scholarship_alerts']['email'] ?? true;
            $matchAlertsWa = $prefMap['matching_scholarship_alerts']['whatsapp'] ?? false;
            $genEmail = (bool)($prefMap['email_alerts']['email'] ?? true);
            $genWa = (bool)($prefMap['whatsapp_alerts']['whatsapp'] ?? false);

            $rawPhone = $user['whatsapp_phone'] ?: $user['phone'];
            $normalizedPhone = !empty($rawPhone) ? NotificationService::normalizePhoneNumber((string)$rawPhone) : null;

            // Subscription check for alerts
            $canPremiumAlerts = SubscriptionService::can($userId, 'premium_alerts');
            $canWhatsAppAlerts = SubscriptionService::can($userId, 'whatsapp_alerts');

            // Testing bypass compatibility
            if (defined('TESTING_MODE') && TESTING_MODE && ($user['email'] ?? '') !== 'student_billing@example.com') {
                $canPremiumAlerts = true;
                $canWhatsAppAlerts = true;
            }

            $emailPossible = $canPremiumAlerts && $matchAlertsEmail && $genEmail && (bool)$user['email_opt_in'] && !empty($user['email']);
            $waPossible = $canWhatsAppAlerts && $matchAlertsWa && $genWa && (bool)$user['whatsapp_opt_in'] && !empty($normalizedPhone) && !$isSunday;

            // Enforce lifetime 25 WhatsApp limit using explicit whitelist
            if ($waPossible) {
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
                if ((int)$cntStmt->fetchColumn() >= 25) {
                    $waPossible = false;
                }
            }

            // Channel routing & fallback logic
            $sendEmail = false;
            $sendWhatsApp = false;

            if ($allowMultiChannel) {
                $sendEmail = $emailPossible;
                $sendWhatsApp = $waPossible;
            } else {
                if ($preferredChannel === 'whatsapp' && $waPossible) {
                    $sendWhatsApp = true;
                } elseif ($emailPossible) {
                    // Falls back to email if whatsapp was preferred but not possible (e.g. invalid phone/Sunday/limit), or if email preferred
                    $sendEmail = true;
                } elseif ($waPossible) {
                    $sendWhatsApp = true;
                }
            }

            // 4. Identify NEW matches that haven't been notified yet
            $newMatchesForUser = [];
            foreach ($matches as $match) {
                $schId = (int)$match['scholarship_id'];
                $idempotencyKey = "new_match_{$userId}_{$schId}";

                // Check if user was already notified of this match
                $stmtCheck = $db->prepare("SELECT COUNT(*) FROM notification_logs WHERE user_id = :uid AND scholarship_id = :sid AND (idempotency_key = :key OR notification_type = 'NEW_MATCH')");
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

                // If sending email, enqueue each new match with the business idempotency key
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
                }
                if ($sendWhatsApp && !$sendEmail) {
                    // Record match idempotency key so repeated cron runs do not duplicate this match
                    try {
                        $stmtLog = $db->prepare("
                            INSERT INTO notification_logs (
                                user_id, scholarship_id, notification_type, channel, provider, 
                                recipient, payload, idempotency_key, status, available_at, created_at, updated_at
                            ) VALUES (
                                :uid, :sid, 'NEW_MATCH', 'whatsapp', 'wacrm', 
                                :rcpt, :payload, :key, 'batched', NOW(), NOW(), NOW()
                            )
                        ");
                        $stmtLog->execute([
                            'uid' => $userId,
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

            // 5. If WhatsApp is active and user has >= 1 new matches, enqueue ONE combined WhatsApp job per user/day
            if ($sendWhatsApp && !empty($newMatchesForUser)) {
                $notificationService->enqueueDailyWhatsAppBatch($userId, $newMatchesForUser, $calendarDay, $normalizedPhone);
            }

        } catch (\Exception $e) {
            echo "Error processing user ID $userId: " . $e->getMessage() . "\n";
        }
    }
}

// Finished enqueuing matching notifications
echo "Daily Matches process completed. Matching notifications enqueued successfully.\n";

