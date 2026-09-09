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

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}
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

$lockStmt = $db->prepare("SELECT GET_LOCK('cron_subscription_expiry', 0)");
$lockStmt->execute();
if ((int)$lockStmt->fetchColumn() !== 1) {
    echo "ℹ Another subscription expiry process is currently running. Exiting.\n";
    exit(0);
}

try {
    echo "Starting subscription lifecycle maintenance job...\n";

    $results = SubscriptionService::processDailyLifecycle($db);

    echo "✔ Subscriptions evaluated: {$results['evaluated']}\n";
    echo "✔ Subscriptions protected (insufficient delivered messages): {$results['protected']}\n";
    echo "✔ Subscriptions expired (satisfied requirements): {$results['expired']}\n";
    echo "✔ Renewal reminders enqueued: {$results['reminders']}\n";

} finally {
    $db->query("SELECT RELEASE_LOCK('cron_subscription_expiry')");
}

echo "Lifecycle maintenance job completed successfully.\n";
