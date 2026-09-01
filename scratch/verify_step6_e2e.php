<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../tests/bootstrap.php';

use App\Services\Database;
use App\Services\Auth;
use App\Services\NotificationSchedulerService;
use App\Services\NotificationDispatchService;
use App\Services\NotificationService;
use App\Services\NotificationQueueService;
use App\Services\NotificationTypes;
use App\Services\SubscriptionService;
use App\Services\ScholarshipMatchingService;
use App\Services\WhatsApp\WacrmWhatsAppProvider;
use App\Services\WhatsApp\WhatsAppProviderInterface;

echo "==================================================\n";
echo " STEP 6: CONTROLLED E2E VERIFICATION SUITE\n";
echo "==================================================\n\n";

$db = Database::connection();
$scheduler = new NotificationSchedulerService($db);

// 1. Audit Security & Environment
echo "[1] Auditing Environment & Security Configuration...\n";
$config = require ROOT_PATH . '/config/whatsapp.php';
$wacrmBaseUrl = $config['wacrm']['base_url'] ?? '';
$wacrmApiKey = $config['wacrm']['api_key'] ?? '';

echo "  - WACRM Base URL: " . (empty($wacrmBaseUrl) ? "NOT CONFIGURED (Using Mock/Test Provider)" : "CONFIGURED") . "\n";
echo "  - WACRM API Key:  " . (empty($wacrmApiKey) ? "NOT CONFIGURED (Using Mock/Test Provider)" : "CONFIGURED") . "\n";

$wacrmProvider = new WacrmWhatsAppProvider();
$connStatus = $wacrmProvider->testConnection();
echo "  - WACRM Connectivity Test: {$connStatus}\n";
echo "✔ Security audit completed.\n\n";

// 2. Clean and Setup Controlled Test Fixtures
echo "[2] Setting Up Controlled Test Fixture...\n";
$db->exec("DELETE FROM notification_logs WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'step6-%')");
$db->exec("DELETE FROM notification_preferences WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'step6-%')");
$db->exec("DELETE FROM subscriptions WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'step6-%')");
$db->exec("DELETE FROM education_records WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'step6-%')");
$db->exec("DELETE FROM student_profiles WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'step6-%')");
$db->exec("DELETE FROM user_preferences WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'step6-%')");
$db->exec("DELETE FROM users WHERE email LIKE 'step6-%'");
$db->exec("DELETE FROM scholarships WHERE slug LIKE 'step6-%'");

$roleId = $db->query("SELECT id FROM roles WHERE name = 'visitor' LIMIT 1")->fetchColumn();
$planId = $db->query("SELECT id FROM subscription_plans WHERE slug = 'premium-monthly' LIMIT 1")->fetchColumn();

// Create controlled test user
$stmtUser = $db->prepare("
    INSERT INTO users (first_name, last_name, email, phone, whatsapp_phone, password_hash, role_id, status, email_opt_in, whatsapp_opt_in, created_at)
    VALUES ('Step6', 'Tester', 'step6-test-user@scholarmatch.com', '+923008888888', '+923008888888', 'hash', :role_id, 'active', 1, 1, NOW())
");
$stmtUser->execute(['role_id' => $roleId]);
$userId = (int)$db->lastInsertId();

$db->exec("INSERT INTO student_profiles (user_id, gender, date_of_birth, nationality_country_id, residence_country_id) VALUES ({$userId}, 'male', '2000-01-01', 1, 1)");
$db->exec("INSERT INTO education_records (user_id, institution_name, degree_level, degree_title, field_of_study, cgpa, cgpa_scale, percentage, is_current) VALUES ({$userId}, 'Step6 Test University', 'Bachelor', 'Bachelor of Science', 'Computer Science', 3.80, 4.00, 95.00, 1)");
$db->exec("INSERT INTO user_preferences (user_id, funding_preferences) VALUES ({$userId}, 'Fully Funded')");
$db->exec("INSERT INTO notification_preferences (user_id, notification_type, whatsapp_enabled) VALUES ({$userId}, 'whatsapp_alerts', 1)");
$db->exec("INSERT INTO notification_preferences (user_id, notification_type, whatsapp_enabled) VALUES ({$userId}, 'matching_scholarship_alerts', 1)");
$db->exec("INSERT INTO notification_preferences (user_id, notification_type, whatsapp_enabled) VALUES ({$userId}, 'deadline_reminders', 1)");

// Active premium subscription
$db->exec("INSERT INTO subscriptions (user_id, plan_id, status, starts_at, ends_at) VALUES ({$userId}, {$planId}, 'active', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY))");

// Published scholarship
$stmtSch = $db->prepare("
    INSERT INTO scholarships (title, slug, status, provider_name, country_id, funding_type, application_deadline, description, created_at)
    VALUES ('Step6 Controlled E2E Scholarship', 'step6-controlled-e2e-scholarship', 'published', 'Global Test Foundation', 1, 'Fully Funded', DATE_ADD(CURDATE(), INTERVAL 45 DAY), 'E2E Description', NOW())
");
$stmtSch->execute();
$schId = (int)$db->lastInsertId();

$db->exec("INSERT INTO scholarship_degree_levels (scholarship_id, degree_level) VALUES ({$schId}, 'Bachelor')");
$db->exec("INSERT INTO scholarship_eligible_nationalities (scholarship_id, country_id) VALUES ({$schId}, 1)");
$db->exec("INSERT INTO scholarship_eligibility_rules (scholarship_id, minimum_cgpa, cgpa_scale) VALUES ({$schId}, 3.00, 4.00)");

echo "  - User ID: #{$userId} (+923008888888, Active Premium)\n";
echo "  - Scholarship ID: #{$schId} (Published, Deadline: +45 days)\n";
echo "✔ Controlled test fixture created.\n\n";

// 3. Evaluate Match & Enqueue Notification Foundation
echo "[3] Evaluating Matching & Enqueuing NEW_MATCH Notification...\n";
$matchingService = new ScholarshipMatchingService();
$match = $matchingService->matchUserAndScholarship($userId, $schId);
if (($match['eligibility_status'] ?? '') !== 'ELIGIBLE') {
    throw new Exception("Matching failed for controlled test profile.");
}
echo "  - Matching Result: ELIGIBLE (Score: " . ($match['match_score'] ?? '100') . "%)\n";

$notifService = new NotificationService();
$enqueued = $notifService->createNewMatchNotification($userId, $schId);
if (!$enqueued) {
    throw new Exception("Failed to enqueue NEW_MATCH notification for matching user.");
}

$pendingCount = $db->query("SELECT COUNT(*) FROM notification_logs WHERE user_id = {$userId} AND scholarship_id = {$schId} AND status = 'pending'")->fetchColumn();
if ($pendingCount != 1) {
    throw new Exception("Expected exactly 1 pending notification row, found: {$pendingCount}");
}
echo "✔ Successfully generated 1 pending NEW_MATCH notification log.\n\n";

// 4. Configure Scheduler Admin Settings for Controlled Test
echo "[4] Configuring Admin Scheduler Settings for Controlled Test...\n";
$currentDay = date('l');
$currentTime = date('H:i', time() - 3600); // 1 hour ago so current time is past send time
$scheduler->updateSettings([
    'whatsapp_notifications_enabled' => 1,
    'whatsapp_allowed_days' => [$currentDay],
    'whatsapp_send_time' => $currentTime,
    'whatsapp_timezone' => 'Asia/Karachi',
    'whatsapp_batch_size' => 1,
    'whatsapp_new_match_enabled' => 1,
    'whatsapp_deadline_reminder_enabled' => 0
]);
echo "  - Allowed Days: [{$currentDay}]\n";
echo "  - Send Time: {$currentTime}\n";
echo "  - Batch Size: 1\n";
echo "✔ Settings configured.\n\n";

// 5. Test Dry-Run Execution
echo "[5] Testing Dry-Run Mode...\n";
$dryRunReport = $scheduler->runDryRun();
if (!$dryRunReport['schedule_due'] || $dryRunReport['total_pending'] < 1) {
    throw new Exception("Dry-run failed to detect due schedule and pending notifications.");
}

// Verify no DB modifications occurred during dry-run
$statusAfterDryRun = $db->query("SELECT status, sent_at, provider_message_id FROM notification_logs WHERE user_id = {$userId} AND scholarship_id = {$schId}")->fetch(PDO::FETCH_ASSOC);
if ($statusAfterDryRun['status'] !== 'pending' || !empty($statusAfterDryRun['sent_at']) || !empty($statusAfterDryRun['provider_message_id'])) {
    throw new Exception("Dry-run modified notification record in violation of safety rules.");
}
echo "✔ Dry-run confirmed: schedule due, 1 candidate detected, 0 DB modifications.\n\n";

// 6. Test Controlled Dispatch Execution
echo "[6] Executing Controlled Worker Dispatch (Batch Size = 1)...\n";
$claimedBatch = $scheduler->claimPendingBatch(1, [NotificationTypes::NEW_MATCH]);
if (count($claimedBatch) !== 1) {
    throw new Exception("Failed to claim exactly 1 pending notification for dispatch.");
}

// Use Mock Provider to verify full dispatch pipeline safely
$mockProvider = new class implements WhatsAppProviderInterface {
    public int $calls = 0;
    public function sendTemplateMessage(string $recipient, string $templateName, array $parameters): array {
        $this->calls++;
        return [
            'success' => true,
            'message_id' => 'wacrm_e2e_msg_' . bin2hex(random_bytes(6)),
            'error' => null
        ];
    }
};

$dispatcher = new NotificationDispatchService($db, $mockProvider);
$dispatchResult = $dispatcher->dispatchBatch($claimedBatch);

echo "  - Dispatched Results: " . json_encode($dispatchResult) . "\n";
if ($dispatchResult['sent'] !== 1) {
    throw new Exception("Controlled dispatch failed to mark notification as sent.");
}

$sentRow = $db->query("SELECT status, sent_at, provider, provider_message_id FROM notification_logs WHERE user_id = {$userId} AND scholarship_id = {$schId}")->fetch(PDO::FETCH_ASSOC);
if ($sentRow['status'] !== 'sent' || empty($sentRow['sent_at']) || empty($sentRow['provider_message_id'])) {
    throw new Exception("Database record missing required sent attributes.");
}
echo "  - Final Record Status: {$sentRow['status']}\n";
echo "  - Sent At: {$sentRow['sent_at']}\n";
echo "  - Provider: {$sentRow['provider']}\n";
echo "  - Provider Message ID: {$sentRow['provider_message_id']}\n";
echo "✔ Controlled dispatch completed successfully.\n\n";

// 7. Test Duplicate Send Protection
echo "[7] Verifying Duplicate-Send Protection...\n";
$subsequentClaims = $scheduler->claimPendingBatch(1, [NotificationTypes::NEW_MATCH]);
if (count($subsequentClaims) !== 0) {
    throw new Exception("Duplicate claim occurred on previously sent notification.");
}

// Attempt to re-enqueue matching notification
$reEnqueue = $notifService->createNewMatchNotification($userId, $schId);
if ($reEnqueue !== false) {
    throw new Exception("Idempotency failed: allowed duplicate enqueue of existing match.");
}
$totalLogs = $db->query("SELECT COUNT(*) FROM notification_logs WHERE user_id = {$userId} AND scholarship_id = {$schId}")->fetchColumn();
if ($totalLogs != 1) {
    throw new Exception("Found {$totalLogs} records for user/scholarship match; expected exactly 1.");
}
echo "✔ Duplicate protection verified: 0 duplicate claims, 0 duplicate notifications.\n\n";

// 8. Test Safety Gating Scenarios
echo "[8] Verifying Safety Gating Boundaries...\n";

// A. Paid User Gate: Free User (No active paid subscription)
$db->exec("DELETE FROM subscriptions WHERE user_id = {$userId}");
$freeItem = ['id' => $userId, 'user_id' => $userId, 'scholarship_id' => $schId, 'notification_type' => 'NEW_MATCH', 'status' => 'processing', 'attempts' => 0];
$checkFree = $dispatcher->recheckEligibility($freeItem);
if ($checkFree['valid'] !== false) {
    throw new Exception("Safety Failure: Free user was not blocked.");
}
$db->exec("INSERT INTO subscriptions (user_id, plan_id, status, starts_at, ends_at) VALUES ({$userId}, {$planId}, 'active', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY))");
echo "  ✔ Paid subscription gate verified.\n";

// B. WhatsApp Opt-out Gate
$db->exec("UPDATE users SET whatsapp_opt_in = 0 WHERE id = {$userId}");
$checkOptOut = $dispatcher->recheckEligibility($freeItem);
if ($checkOptOut['valid'] !== false) {
    throw new Exception("Safety Failure: Opted-out user was not blocked.");
}
$db->exec("UPDATE users SET whatsapp_opt_in = 1 WHERE id = {$userId}");
echo "  ✔ WhatsApp opt-out gate verified.\n";

// C. Expired Scholarship Gate
$db->exec("UPDATE scholarships SET application_deadline = DATE_SUB(CURDATE(), INTERVAL 1 DAY) WHERE id = {$schId}");
$checkExpired = $dispatcher->recheckEligibility($freeItem);
if ($checkExpired['valid'] !== false) {
    throw new Exception("Safety Failure: Expired scholarship was not blocked.");
}
$db->exec("UPDATE scholarships SET application_deadline = DATE_ADD(CURDATE(), INTERVAL 30 DAY) WHERE id = {$schId}");
echo "  ✔ Expired scholarship gate verified.\n";

// D. Admin Master Switch
$db->exec("UPDATE settings SET value = '0' WHERE `key` = 'whatsapp_notifications_enabled'");
$checkDisabled = $dispatcher->recheckEligibility($freeItem);
if ($checkDisabled['valid'] !== false) {
    throw new Exception("Safety Failure: Admin master disable switch was not respected.");
}
$db->exec("UPDATE settings SET value = '1' WHERE `key` = 'whatsapp_notifications_enabled'");
echo "  ✔ Admin master toggle verified.\n";

// E. Concurrency Lock across distinct processes/connections
$db2 = new PDO("mysql:host=" . ($_ENV['DB_HOST'] ?? '127.0.0.1') . ";dbname=" . ($_ENV['DB_DATABASE'] ?? 'scholarship') . ";charset=utf8mb4", $_ENV['DB_USERNAME'] ?? 'root', $_ENV['DB_PASSWORD'] ?? '');
$scheduler2 = new NotificationSchedulerService($db2);

$lock1 = $scheduler->acquireLock('scholarship_notification_scheduler', 0);
$lock2 = $scheduler2->acquireLock('scholarship_notification_scheduler', 0);

if (!$lock1 || $lock2) {
    throw new Exception("Concurrency lock failed: second connection was not rejected.");
}
$scheduler->releaseLock('scholarship_notification_scheduler');
echo "  ✔ MySQL advisory concurrency locking verified.\n\n";

// Clean up test data
$db->exec("DELETE FROM notification_logs WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'step6-%')");
$db->exec("DELETE FROM notification_preferences WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'step6-%')");
$db->exec("DELETE FROM subscriptions WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'step6-%')");
$db->exec("DELETE FROM education_records WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'step6-%')");
$db->exec("DELETE FROM student_profiles WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'step6-%')");
$db->exec("DELETE FROM user_preferences WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'step6-%')");
$db->exec("DELETE FROM users WHERE email LIKE 'step6-%'");
$db->exec("DELETE FROM scholarships WHERE slug LIKE 'step6-%'");

echo "==================================================\n";
echo " STEP 6 E2E VERIFICATION COMPLETED SUCCESSFULLY   \n";
echo "==================================================\n";
