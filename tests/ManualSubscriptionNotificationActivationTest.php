<?php
require_once __DIR__ . '/bootstrap.php';

use App\Services\Database;
use App\Services\Auth;
use App\Services\SubscriptionService;
use App\Controllers\AdminController;
use App\Controllers\ProfileController;

echo "=== Running Manual Subscription Notification Activation Test ===\n";

$db = Database::connection();

// 1. Find or create a test student
$stmt = $db->query("SELECT u.id, u.email, u.phone FROM users u JOIN roles r ON u.role_id = r.id WHERE r.name IN ('student', 'visitor') AND u.status = 'active' LIMIT 1");
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    echo "FAIL: No active student found in database.\n";
    exit(1);
}

$testUserId = (int)$student['id'];
echo "Testing with Student ID: {$testUserId} ({$student['email']})\n";

// Find an active paid plan
$stmtPlan = $db->query("SELECT id, name FROM subscription_plans WHERE slug != 'free' AND status = 'active' LIMIT 1");
$plan = $stmtPlan->fetch(PDO::FETCH_ASSOC);
if (!$plan) {
    echo "FAIL: No active paid plan found.\n";
    exit(1);
}
$planId = (int)$plan['id'];

// Find an admin user
$stmtAdmin = $db->query("SELECT u.id FROM users u JOIN roles r ON u.role_id = r.id WHERE r.name = 'admin' AND u.status = 'active' LIMIT 1");
$admin = $stmtAdmin->fetch(PDO::FETCH_ASSOC);
$adminId = $admin ? (int)$admin['id'] : 1;

// 2. Direct activation test
SubscriptionService::activateManualSubscriptionNotifications($testUserId, $db);

// 3. Verify notification_preferences
$stmtNP = $db->prepare("SELECT notification_type, email_enabled, whatsapp_enabled FROM notification_preferences WHERE user_id = :uid");
$stmtNP->execute(['uid' => $testUserId]);
$npRows = $stmtNP->fetchAll(PDO::FETCH_ASSOC);

$npMap = [];
foreach ($npRows as $r) {
    $npMap[$r['notification_type']] = [
        'email' => (int)$r['email_enabled'],
        'whatsapp' => (int)$r['whatsapp_enabled']
    ];
}

$asserts = [
    'email_alerts' => ['email' => 1, 'whatsapp' => 0],
    'whatsapp_alerts' => ['email' => 0, 'whatsapp' => 1],
    'daily_alerts' => ['email' => 0, 'whatsapp' => 0],
    'weekly_digest' => ['email' => 0, 'whatsapp' => 0],
    'deadline_reminders' => ['email' => 1, 'whatsapp' => 1],
    'matching_scholarship_alerts' => ['email' => 1, 'whatsapp' => 1],
];

foreach ($asserts as $type => $expected) {
    if (!isset($npMap[$type])) {
        echo "FAIL: Missing notification preference for '{$type}'\n";
        exit(1);
    }
    if ($npMap[$type]['email'] !== $expected['email'] || $npMap[$type]['whatsapp'] !== $expected['whatsapp']) {
        echo "FAIL: Mismatch for '{$type}'. Expected: " . json_encode($expected) . ", Got: " . json_encode($npMap[$type]) . "\n";
        exit(1);
    }
}
echo "OK: notification_preferences matches requirement (Email: ON, WhatsApp: ON, Daily: OFF, Weekly: OFF, Deadline: ON).\n";

// 4. Verify user_preferences
$stmtUP = $db->prepare("SELECT * FROM user_preferences WHERE user_id = :uid");
$stmtUP->execute(['uid' => $testUserId]);
$up = $stmtUP->fetch(PDO::FETCH_ASSOC);

if (!$up) {
    echo "FAIL: user_preferences row missing for user {$testUserId}\n";
    exit(1);
}

if ((int)$up['email_enabled'] !== 1 || (int)$up['whatsapp_enabled'] !== 1 || (int)$up['daily_alert_enabled'] !== 0) {
    echo "FAIL: user_preferences flags mismatch. email={$up['email_enabled']}, whatsapp={$up['whatsapp_enabled']}, daily={$up['daily_alert_enabled']}\n";
    exit(1);
}
if ($up['deadline_reminder_scope'] !== 'all') {
    echo "FAIL: deadline_reminder_scope is {$up['deadline_reminder_scope']}, expected 'all'\n";
    exit(1);
}
echo "OK: user_preferences table synchronized properly.\n";

// 5. Verify users table master flags
$stmtUser = $db->prepare("SELECT email_opt_in, whatsapp_opt_in FROM users WHERE id = :uid");
$stmtUser->execute(['uid' => $testUserId]);
$userRow = $stmtUser->fetch(PDO::FETCH_ASSOC);

if ((int)$userRow['email_opt_in'] !== 1 || (int)$userRow['whatsapp_opt_in'] !== 1) {
    echo "FAIL: users table opt_in mismatch. email_opt_in={$userRow['email_opt_in']}, whatsapp_opt_in={$userRow['whatsapp_opt_in']}\n";
    exit(1);
}
echo "OK: users master opt-in flags activated.\n";

// 6. Verify profile view mapping simulation
$emailAlertsOn = ($npMap['email_alerts']['email'] ?? 0) === 1;
$whatsappAlertsOn = ($npMap['whatsapp_alerts']['whatsapp'] ?? 0) === 1;
$dailyAlertsOn = ($npMap['daily_alerts']['email'] ?? 0) === 1;
$weeklyDigestOn = ($npMap['weekly_digest']['email'] ?? 0) === 1;
$deadlineRemindersOn = (($npMap['deadline_reminders']['email'] ?? 0) === 1) || (($npMap['deadline_reminders']['whatsapp'] ?? 0) === 1);

echo "Profile status summary:\n";
echo " - Email Notifications: " . ($emailAlertsOn ? "ON" : "OFF") . "\n";
echo " - WhatsApp Notifications: " . ($whatsappAlertsOn ? "ON" : "OFF") . "\n";
echo " - Daily Updates Frequency: " . ($dailyAlertsOn ? "ON" : "OFF") . "\n";
echo " - Weekly Summaries: " . ($weeklyDigestOn ? "ON" : "OFF") . "\n";
echo " - Deadline Reminders: " . ($deadlineRemindersOn ? "ON" : "OFF") . "\n";

if ($emailAlertsOn && $whatsappAlertsOn && !$dailyAlertsOn && !$weeklyDigestOn && $deadlineRemindersOn) {
    echo "SUCCESS: All 5 profile notification statuses strictly match user specification!\n";
} else {
    echo "FAIL: Profile status values do not match expected states.\n";
    exit(1);
}

// 7. Test Transition: turn everything OFF first, then run activateManualSubscriptionNotifications
echo "\n--- Testing Transition from completely OFF state ---\n";
$db->prepare("UPDATE notification_preferences SET email_enabled = 0, whatsapp_enabled = 0 WHERE user_id = :uid")->execute(['uid' => $testUserId]);
$db->prepare("UPDATE user_preferences SET email_enabled = 0, whatsapp_enabled = 0, daily_alert_enabled = 1, deadline_reminder_scope = 'off' WHERE user_id = :uid")->execute(['uid' => $testUserId]);

SubscriptionService::activateManualSubscriptionNotifications($testUserId, $db);

$stmtNP->execute(['uid' => $testUserId]);
$npRows = $stmtNP->fetchAll(PDO::FETCH_ASSOC);
$npMap = [];
foreach ($npRows as $r) {
    $npMap[$r['notification_type']] = [
        'email' => (int)$r['email_enabled'],
        'whatsapp' => (int)$r['whatsapp_enabled']
    ];
}

foreach ($asserts as $type => $expected) {
    if ($npMap[$type]['email'] !== $expected['email'] || $npMap[$type]['whatsapp'] !== $expected['whatsapp']) {
        echo "FAIL: Transition mismatch for '{$type}'. Expected: " . json_encode($expected) . ", Got: " . json_encode($npMap[$type]) . "\n";
        exit(1);
    }
}

$stmtUP->execute(['uid' => $testUserId]);
$up = $stmtUP->fetch(PDO::FETCH_ASSOC);
if ((int)$up['email_enabled'] !== 1 || (int)$up['whatsapp_enabled'] !== 1 || (int)$up['daily_alert_enabled'] !== 0 || $up['deadline_reminder_scope'] !== 'all') {
    echo "FAIL: Transition mismatch in user_preferences table.\n";
    exit(1);
}

echo "SUCCESS: Successfully validated transition from OFF state to activated state!\n";

