<?php
/**
 * Cron Job: Subscription Expiration & Renewals Reminder
 * Runs daily via command line interface.
 * Usage: php cron/subscription_expiry.php
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo "Access Denied: CLI runtime execution context only.\n";
    exit(1);
}

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/vendor/autoload.php';

// Load environmental properties
try {
    if (file_exists(ROOT_PATH . '/.env')) {
        $dotenv = \Dotenv\Dotenv::createImmutable(ROOT_PATH);
        $dotenv->load();
    }
} catch (\Exception $e) {
    // Fail silently, use default values
}

use App\Services\Database;
use App\Services\SubscriptionService;
use App\Services\NotificationQueueService;
use App\Services\Logger;

try {
    $db = Database::connection();
} catch (\Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Starting subscription lifecycle maintenance job...\n";

// 1. Process Expired Subscriptions
$stmtExpired = $db->query("
    SELECT id, user_id, ends_at FROM subscriptions 
    WHERE status IN ('active', 'cancelled') 
      AND ends_at < NOW()
");
$expiredSubs = $stmtExpired->fetchAll(PDO::FETCH_ASSOC);

$expiredCount = 0;
foreach ($expiredSubs as $sub) {
    $subId = (int)$sub['id'];
    $userId = (int)$sub['user_id'];
    
    // Transition status to expired via centralized service
    if (SubscriptionService::transition($subId, 'expired')) {
        // Enqueue expiration notification
        $queueService = new NotificationQueueService();
        
        $stmtUser = $db->prepare("SELECT email FROM users WHERE id = :id LIMIT 1");
        $stmtUser->execute(['id' => $userId]);
        $userEmail = $stmtUser->fetchColumn();

        if ($userEmail) {
            $queueService->enqueue(
                $userId,
                null,
                'SUBSCRIPTION_EXPIRED',
                'email',
                $userEmail,
                'Your Premium Subscription Has Expired',
                [
                    'summary' => 'Your Premium subscription has expired. Upgrade today to restore full personalized matching, advanced filters, and application tools.',
                    'detail_url' => url('/pricing')
                ],
                "sub_expired_alert_{$subId}"
            );
        }
        
        echo "✔ Subscription ID {$subId} transitioned to expired.\n";
        $expiredCount++;
    }
}

echo "Processed {$expiredCount} expired subscriptions.\n";

// 2. Queue Renewal Notifications (7 days, 3 days, 1 day, and 0 days left)
// We calculate intervals based on ends_at date
$stmtReminders = $db->query("
    SELECT s.id as sub_id, s.user_id, s.ends_at, u.email, p.name as plan_name,
           DATEDIFF(s.ends_at, NOW()) as days_left
    FROM subscriptions s
    JOIN users u ON s.user_id = u.id
    JOIN subscription_plans p ON s.plan_id = p.id
    WHERE s.status = 'active'
      AND DATEDIFF(s.ends_at, NOW()) IN (7, 3, 1, 0)
");
$reminders = $stmtReminders->fetchAll(PDO::FETCH_ASSOC);

$remindersCount = 0;
$queueService = new NotificationQueueService();

foreach ($reminders as $r) {
    $daysLeft = (int)$r['days_left'];
    $subId = (int)$r['sub_id'];
    $userId = (int)$r['user_id'];
    $email = $r['email'];
    $planName = $r['plan_name'];

    $subject = '';
    $message = '';

    if ($daysLeft === 0) {
        $subject = "Your {$planName} Plan Renews Today";
        $message = "This is a reminder that your subscription for {$planName} is scheduled to renew today. The automatic charge will be processed shortly.";
    } else {
        $subject = "Renewal Reminder: {$daysLeft} days until subscription renews";
        $message = "Your {$planName} plan is scheduled to automatically renew in {$daysLeft} days. Thank you for using ScholarMatch!";
    }

    // Unique idempotency key to prevent double alert enqueuing on subsequent runs
    $idempotencyKey = "sub_renew_{$subId}_{$daysLeft}";

    $enqueued = $queueService->enqueue(
        $userId,
        null,
        'SUBSCRIPTION_RENEWAL_REMINDER',
        'email',
        $email,
        $subject,
        [
            'summary' => $message,
            'detail_url' => url('/billing')
        ],
        $idempotencyKey
    );

    if ($enqueued) {
        $remindersCount++;
    }
}

echo "Enqueued {$remindersCount} subscription renewal notification reminders.\n";
echo "Lifecycle maintenance job completed successfully.\n";
