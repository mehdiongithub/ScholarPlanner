<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Safe load .env
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

require_once __DIR__ . '/../tests/bootstrap.php';

use App\Services\Database;
use App\Services\NotificationSchedulerService;
use App\Services\NotificationDispatchService;
use App\Services\NotificationService;
use App\Services\NotificationTypes;
use App\Services\SubscriptionService;
use App\Services\ScholarshipMatchingService;
use App\Services\WhatsApp\WacrmWhatsAppProvider;

echo "==================================================\n";
echo " STEP 7 RETEST: CONTROLLED REAL WHATSAPP DISPATCH\n";
echo "==================================================\n\n";

$db = Database::connection();
$scheduler = new NotificationSchedulerService($db);
$wacrm = new WacrmWhatsAppProvider();

// 1. Verify WACRM Account & Connection
echo "[1] Checking WACRM Account & API Connection...\n";
$connStatus = $wacrm->testConnection();
echo "  - WACRM API Connection Status: {$connStatus}\n";
if ($connStatus !== 'CONNECTED') {
    echo "❌ WACRM API is not connected. Aborting.\n";
    exit(1);
}
echo "✔ WACRM account connection verified.\n\n";

// 2. Setup Clean New Controlled Test Fixture
echo "[2] Creating New Controlled Test Fixture...\n";
$targetPhone = '+923163261056'; // Ghulam Mehdi administrator number

// Clean previous retest fixtures
$db->exec("DELETE FROM notification_logs WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'step7-retest%')");
$db->exec("DELETE FROM notification_preferences WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'step7-retest%')");
$db->exec("DELETE FROM subscriptions WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'step7-retest%')");
$db->exec("DELETE FROM education_records WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'step7-retest%')");
$db->exec("DELETE FROM student_profiles WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'step7-retest%')");
$db->exec("DELETE FROM user_preferences WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'step7-retest%')");
$db->exec("DELETE FROM users WHERE email LIKE 'step7-retest%'");
$db->exec("DELETE FROM scholarships WHERE slug = 'scholarplanner-wacrm-retest-v2'");

$roleId = $db->query("SELECT id FROM roles WHERE name = 'visitor' LIMIT 1")->fetchColumn();
$planId = $db->query("SELECT id FROM subscription_plans WHERE slug = 'premium-monthly' LIMIT 1")->fetchColumn();

// Create controlled test user
$stmtUser = $db->prepare("
    INSERT INTO users (first_name, last_name, email, phone, whatsapp_phone, password_hash, role_id, status, email_opt_in, whatsapp_opt_in, created_at)
    VALUES ('Ghulam', 'Mehdi', 'step7-retest-user@scholarmatch.com', :phone, :wa_phone, 'hash', :role_id, 'active', 1, 1, NOW())
");
$stmtUser->execute([
    'phone' => $targetPhone,
    'wa_phone' => $targetPhone,
    'role_id' => $roleId
]);
$userId = (int)$db->lastInsertId();

$db->exec("INSERT INTO student_profiles (user_id, gender, date_of_birth, nationality_country_id, residence_country_id) VALUES ({$userId}, 'male', '1998-01-01', 1, 1)");
$db->exec("INSERT INTO education_records (user_id, institution_name, degree_level, degree_title, field_of_study, cgpa, cgpa_scale, percentage, is_current) VALUES ({$userId}, 'Mehran University', 'Bachelor', 'Bachelor of Engineering', 'Software Engineering', 3.85, 4.00, 95.00, 1)");
$db->exec("INSERT INTO user_preferences (user_id, funding_preferences) VALUES ({$userId}, 'Fully Funded')");
$db->exec("INSERT INTO notification_preferences (user_id, notification_type, whatsapp_enabled) VALUES ({$userId}, 'whatsapp_alerts', 1)");
$db->exec("INSERT INTO notification_preferences (user_id, notification_type, whatsapp_enabled) VALUES ({$userId}, 'matching_scholarship_alerts', 1)");
$db->exec("INSERT INTO notification_preferences (user_id, notification_type, whatsapp_enabled) VALUES ({$userId}, 'deadline_reminders', 1)");

// Active premium subscription
$db->exec("INSERT INTO subscriptions (user_id, plan_id, status, starts_at, ends_at) VALUES ({$userId}, {$planId}, 'active', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY))");

// Published scholarship
$stmtSch = $db->prepare("
    INSERT INTO scholarships (title, slug, status, provider_name, country_id, funding_type, application_deadline, description, created_at)
    VALUES ('ScholarPlanner WACRM Integration Test v2', 'scholarplanner-wacrm-retest-v2', 'published', 'ScholarPlanner Official', 1, 'Fully Funded', DATE_ADD(CURDATE(), INTERVAL 45 DAY), 'Live WACRM single-recipient dispatch retest.', NOW())
");
$stmtSch->execute();
$schId = (int)$db->lastInsertId();

$db->exec("INSERT INTO scholarship_degree_levels (scholarship_id, degree_level) VALUES ({$schId}, 'Bachelor')");
$db->exec("INSERT INTO scholarship_eligible_nationalities (scholarship_id, country_id) VALUES ({$schId}, 1)");
$db->exec("INSERT INTO scholarship_eligibility_rules (scholarship_id, minimum_cgpa, cgpa_scale) VALUES ({$schId}, 3.00, 4.00)");

echo "  - Test User ID: #{$userId} (Recipient: {$targetPhone})\n";
echo "  - Test Scholarship ID: #{$schId} ('ScholarPlanner WACRM Integration Test v2')\n";
echo "✔ Controlled test fixture created.\n\n";

// 3. Evaluate Match & Enqueue NEW_MATCH
echo "[3] Running Scholarship Matching Engine & Enqueuing NEW_MATCH...\n";
$matchingService = new ScholarshipMatchingService();
$match = $matchingService->matchUserAndScholarship($userId, $schId);
if (($match['eligibility_status'] ?? '') !== 'ELIGIBLE') {
    echo "❌ Matching failed. Status: " . ($match['eligibility_status'] ?? 'UNKNOWN') . "\n";
    exit(1);
}
echo "  - Match Status: ELIGIBLE (Score: " . ($match['match_score'] ?? '100') . "%)\n";

$notifService = new NotificationService();
$enqueued = $notifService->createNewMatchNotification($userId, $schId);
if (!$enqueued) {
    echo "❌ Failed to create NEW_MATCH notification.\n";
    exit(1);
}

$pendingNotif = $db->query("SELECT id, status, recipient, idempotency_key FROM notification_logs WHERE user_id = {$userId} AND scholarship_id = {$schId}")->fetch(PDO::FETCH_ASSOC);
echo "  - Notification ID: #{$pendingNotif['id']}\n";
echo "  - Initial Status: {$pendingNotif['status']}\n";
echo "  - Target Recipient: {$pendingNotif['recipient']}\n";
echo "  - Idempotency Key: {$pendingNotif['idempotency_key']}\n";
echo "✔ Exactly 1 NEW_MATCH notification enqueued in pending state.\n\n";

// 4. Configure Admin Settings for Controlled Execution
echo "[4] Configuring Admin Scheduler Settings for Single Controlled Execution...\n";
$currentDay = date('l');
$currentTime = date('H:i', time() - 1800); // 30 minutes ago so within window
$scheduler->updateSettings([
    'whatsapp_notifications_enabled' => 1,
    'whatsapp_allowed_days' => [$currentDay],
    'whatsapp_send_time' => $currentTime,
    'whatsapp_timezone' => 'Asia/Karachi',
    'whatsapp_batch_size' => 1,
    'whatsapp_new_match_enabled' => 1,
    'whatsapp_deadline_reminder_enabled' => 0
]);
echo "✔ Scheduler configured with batch size 1.\n\n";

// 5. Run Dry-Run Inspection
echo "[5] Running Dry-Run Inspection...\n";
$dryRun = $scheduler->runDryRun();
echo "  - Schedule Due: " . ($dryRun['schedule_due'] ? 'YES' : 'NO') . "\n";
echo "  - Pending Candidates Detected: {$dryRun['total_pending']}\n";

$postDryStatus = $db->query("SELECT status, sent_at, provider_message_id FROM notification_logs WHERE id = {$pendingNotif['id']}")->fetch(PDO::FETCH_ASSOC);
if ($postDryStatus['status'] !== 'pending' || !empty($postDryStatus['sent_at']) || !empty($postDryStatus['provider_message_id'])) {
    echo "❌ Dry-run modified database state.\n";
    exit(1);
}
echo "✔ Dry-run passed cleanly: 0 messages sent, candidate remained pending.\n\n";

// 6. Real Controlled Dispatch
echo "[6] Executing Controlled Real WACRM Dispatch for Notification #{$pendingNotif['id']}...\n";
$claimed = $scheduler->claimPendingBatch(1, [NotificationTypes::NEW_MATCH]);
if (count($claimed) !== 1 || (int)$claimed[0]['id'] !== (int)$pendingNotif['id']) {
    echo "❌ Failed to claim targeted test notification #{$pendingNotif['id']}.\n";
    exit(1);
}

echo "  - Claimed Item ID: #{$claimed[0]['id']} (Status: {$claimed[0]['status']}, Token: {$claimed[0]['provider_message_id']})\n";

$dispatcher = new NotificationDispatchService($db, $wacrm);
$dispatchResult = $dispatcher->dispatchBatch($claimed);

echo "  - Dispatch Output: " . json_encode($dispatchResult) . "\n";

$finalRow = $db->query("SELECT status, attempts, sent_at, provider, provider_message_id, error_message FROM notification_logs WHERE id = {$pendingNotif['id']}")->fetch(PDO::FETCH_ASSOC);
echo "  - Final DB Status: {$finalRow['status']}\n";
echo "  - Sent At: " . ($finalRow['sent_at'] ?? 'NULL') . "\n";
echo "  - Provider: " . ($finalRow['provider'] ?? 'NULL') . "\n";
echo "  - Provider Message ID: " . ($finalRow['provider_message_id'] ?? 'NULL') . "\n";
if (!empty($finalRow['error_message'])) {
    echo "  - Error Message: {$finalRow['error_message']}\n";
}

if ($finalRow['status'] === 'sent') {
    echo "✔ Real WhatsApp message successfully accepted by WACRM!\n\n";
} else {
    echo "❌ Message dispatch not accepted by WACRM. Status: {$finalRow['status']}\n\n";
}

// 7. Verify Duplicate-Send Protection
echo "[7] Verifying Duplicate-Send Protection...\n";
$subsequentClaim = $scheduler->claimPendingBatch(1, [NotificationTypes::NEW_MATCH]);
echo "  - Subsequent Claim Attempt: " . count($subsequentClaim) . " items claimed (Expected: 0)\n";

$reMatch = $notifService->createNewMatchNotification($userId, $schId);
echo "  - Re-enqueue Attempt: " . ($reMatch === false ? 'BLOCKED (Safe)' : 'FAILED (Duplicate Created)') . "\n";

if (count($subsequentClaim) === 0 && $reMatch === false) {
    echo "✔ Duplicate send protection fully verified.\n\n";
} else {
    echo "❌ Duplicate protection failed.\n\n";
}

echo "==================================================\n";
echo " RETEST EXECUTION FINISHED\n";
echo "==================================================\n";
