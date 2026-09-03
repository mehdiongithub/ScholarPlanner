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
echo " STEP 7: CONTROLLED REAL WHATSAPP MESSAGE TEST\n";
echo "==================================================\n\n";

$db = Database::connection();
$scheduler = new NotificationSchedulerService($db);
$wacrm = new WacrmWhatsAppProvider();

// 1. Verify WACRM Account & Device Readiness
echo "[1] Verifying WACRM Connectivity...\n";
$connStatus = $wacrm->testConnection();
echo "  - WACRM API Connection Status: {$connStatus}\n";
if ($connStatus !== 'CONNECTED') {
    echo "❌ WACRM API is not connected. Aborting.\n";
    exit(1);
}
echo "✔ WACRM account authentication verified.\n\n";

// 2. Identify / Setup Controlled Test User
echo "[2] Setting Up Controlled Test User & Fixture...\n";
// Look for admin or specific controlled recipient
// We use Ghulam Mehdi's registered user #3306 or create dedicated controlled fixture
$targetPhone = '+923163261056'; // Ghulam Mehdi controlled administrator number

// Clean previous step7 logs
$db->exec("DELETE FROM notification_logs WHERE user_id IN (SELECT id FROM users WHERE email = 'step7-live-test@scholarmatch.com')");
$db->exec("DELETE FROM notification_preferences WHERE user_id IN (SELECT id FROM users WHERE email = 'step7-live-test@scholarmatch.com')");
$db->exec("DELETE FROM subscriptions WHERE user_id IN (SELECT id FROM users WHERE email = 'step7-live-test@scholarmatch.com')");
$db->exec("DELETE FROM education_records WHERE user_id IN (SELECT id FROM users WHERE email = 'step7-live-test@scholarmatch.com')");
$db->exec("DELETE FROM student_profiles WHERE user_id IN (SELECT id FROM users WHERE email = 'step7-live-test@scholarmatch.com')");
$db->exec("DELETE FROM user_preferences WHERE user_id IN (SELECT id FROM users WHERE email = 'step7-live-test@scholarmatch.com')");
$db->exec("DELETE FROM users WHERE email = 'step7-live-test@scholarmatch.com'");
$db->exec("DELETE FROM scholarships WHERE slug = 'scholarplanner-wacrm-integration-test'");

$roleId = $db->query("SELECT id FROM roles WHERE name = 'visitor' LIMIT 1")->fetchColumn();
$planId = $db->query("SELECT id FROM subscription_plans WHERE slug = 'premium-monthly' LIMIT 1")->fetchColumn();

// Create controlled test user with target phone
$stmtUser = $db->prepare("
    INSERT INTO users (first_name, last_name, email, phone, whatsapp_phone, password_hash, role_id, status, email_opt_in, whatsapp_opt_in, created_at)
    VALUES ('Ghulam', 'Mehdi', 'step7-live-test@scholarmatch.com', :phone, :wa_phone, 'hash', :role_id, 'active', 1, 1, NOW())
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

// 3. Create Test Scholarship
$stmtSch = $db->prepare("
    INSERT INTO scholarships (title, slug, status, provider_name, country_id, funding_type, application_deadline, description, created_at)
    VALUES ('ScholarPlanner WACRM Integration Test', 'scholarplanner-wacrm-integration-test', 'published', 'ScholarPlanner Official', 1, 'Fully Funded', DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'Live WACRM single-recipient dispatch test.', NOW())
");
$stmtSch->execute();
$schId = (int)$db->lastInsertId();

$db->exec("INSERT INTO scholarship_degree_levels (scholarship_id, degree_level) VALUES ({$schId}, 'Bachelor')");
$db->exec("INSERT INTO scholarship_eligible_nationalities (scholarship_id, country_id) VALUES ({$schId}, 1)");
$db->exec("INSERT INTO scholarship_eligibility_rules (scholarship_id, minimum_cgpa, cgpa_scale) VALUES ({$schId}, 3.00, 4.00)");

echo "  - Test User Created: ID #{$userId} (Recipient: {$targetPhone})\n";
echo "  - Test Scholarship Created: ID #{$schId} ('ScholarPlanner WACRM Integration Test')\n";
echo "✔ Test fixture setup complete.\n\n";

// 4. Evaluate Match & Enqueue NEW_MATCH
echo "[3] Running Scholarship Matching Engine...\n";
$matchingService = new ScholarshipMatchingService();
$match = $matchingService->matchUserAndScholarship($userId, $schId);
if (($match['eligibility_status'] ?? '') !== 'ELIGIBLE') {
    echo "❌ Matching failed for test user. Status: " . ($match['eligibility_status'] ?? 'UNKNOWN') . "\n";
    exit(1);
}
echo "  - Match Status: ELIGIBLE (Score: " . ($match['match_score'] ?? '100') . "%)\n";

$notifService = new NotificationService();
$enqueued = $notifService->createNewMatchNotification($userId, $schId);
if (!$enqueued) {
    echo "❌ Failed to create NEW_MATCH notification.\n";
    exit(1);
}

$pendingCheck = $db->query("SELECT id, status, recipient, idempotency_key FROM notification_logs WHERE user_id = {$userId} AND scholarship_id = {$schId}")->fetch(PDO::FETCH_ASSOC);
echo "  - Notification ID: #{$pendingCheck['id']}\n";
echo "  - Status: {$pendingCheck['status']}\n";
echo "  - Recipient: {$pendingCheck['recipient']}\n";
echo "  - Idempotency Key: {$pendingCheck['idempotency_key']}\n";
echo "✔ Exactly 1 NEW_MATCH notification enqueued in pending state.\n\n";

// 5. Configure Admin Settings for Controlled Execution
echo "[4] Configuring Admin Settings for Single Batch Execution...\n";
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
echo "✔ Admin settings set to batch size 1, window open.\n\n";

// 6. Run Dry Run Inspection
echo "[5] Running Dry-Run Inspection...\n";
$dryRun = $scheduler->runDryRun();
echo "  - Schedule Due: " . ($dryRun['schedule_due'] ? 'YES' : 'NO') . "\n";
echo "  - Total Pending Detected: {$dryRun['total_pending']}\n";

$postDryStatus = $db->query("SELECT status, sent_at, provider_message_id FROM notification_logs WHERE id = {$pendingCheck['id']}")->fetch(PDO::FETCH_ASSOC);
if ($postDryStatus['status'] !== 'pending' || !empty($postDryStatus['sent_at']) || !empty($postDryStatus['provider_message_id'])) {
    echo "❌ Dry-run violated state rules by altering database records.\n";
    exit(1);
}
echo "✔ Dry-run passed: 0 messages sent, notification remained pending.\n\n";

// 7. Controlled Real Message Dispatch
echo "[6] Executing Controlled Real WACRM Dispatch for Notification #{$pendingCheck['id']}...\n";
$claimed = $scheduler->claimPendingBatch(1, [NotificationTypes::NEW_MATCH]);
if (count($claimed) !== 1 || (int)$claimed[0]['id'] !== (int)$pendingCheck['id']) {
    echo "❌ Failed to claim the targeted test notification. Claim count: " . count($claimed) . "\n";
    exit(1);
}

echo "  - Claimed Item ID: #{$claimed[0]['id']} (Status: {$claimed[0]['status']}, Claim Token: {$claimed[0]['provider_message_id']})\n";

$dispatcher = new NotificationDispatchService($db, $wacrm);
$dispatchRes = $dispatcher->dispatchBatch($claimed);

echo "  - Dispatch Results: " . json_encode($dispatchRes) . "\n";

$finalRow = $db->query("SELECT status, attempts, sent_at, provider, provider_message_id, error_message FROM notification_logs WHERE id = {$pendingCheck['id']}")->fetch(PDO::FETCH_ASSOC);
echo "  - Final DB Status: {$finalRow['status']}\n";
echo "  - Sent At: {$finalRow['sent_at']}\n";
echo "  - Provider: {$finalRow['provider']}\n";
echo "  - Provider Message ID: {$finalRow['provider_message_id']}\n";
if (!empty($finalRow['error_message'])) {
    echo "  - Error Message: {$finalRow['error_message']}\n";
}

if ($finalRow['status'] !== 'sent') {
    echo "❌ Real dispatch did not complete as 'sent'.\n";
    exit(1);
}

echo "✔ Real message accepted by WACRM and recorded as sent.\n\n";

// 8. Verify Duplicate Send Protection
echo "[7] Verifying Duplicate-Send Protection...\n";
$nextClaim = $scheduler->claimPendingBatch(1, [NotificationTypes::NEW_MATCH]);
echo "  - Subsequent Claim Attempt: " . count($nextClaim) . " items claimed (Expected: 0)\n";
if (count($nextClaim) !== 0) {
    echo "❌ Duplicate claim occurred.\n";
    exit(1);
}

$reMatch = $notifService->createNewMatchNotification($userId, $schId);
echo "  - Re-enqueue Attempt: " . ($reMatch === false ? 'BLOCKED (Safe)' : 'FAILED (Duplicate Created)') . "\n";
if ($reMatch !== false) {
    echo "❌ Duplicate notification was created.\n";
    exit(1);
}
echo "✔ Duplicate send protection verified.\n\n";

echo "==================================================\n";
echo " STEP 7 TEST DISPATCH COMPLETED SUCCESSFULLY\n";
echo "==================================================\n";
