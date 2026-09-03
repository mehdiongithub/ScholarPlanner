<?php
/**
 * Cron Job: Notification Queue Worker
 * Runs periodically (e.g., every minute) via CLI to dispatch pending emails and alerts.
 * 
 * Usage:
 *   php cron/queue_worker.php
 *   php cron/queue_worker.php --batch-size=50
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo "Access Denied: CLI runtime execution context only.\n";
    exit(1);
}

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/vendor/autoload.php';

// Load env variables
try {
    if (file_exists(ROOT_PATH . '/.env')) {
        $dotenv = \Dotenv\Dotenv::createImmutable(ROOT_PATH);
        $dotenv->load();
    }
} catch (\Exception $e) {
    // Fail silently
}

use App\Services\Database;
use App\Services\NotificationQueueService;
use App\Services\Logger;

try {
    Database::connection();
} catch (\Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Set Timezone
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'Asia/Karachi');

// Parse CLI options
$options = getopt('', ['batch-size:']);
$batchSize = isset($options['batch-size']) ? max(1, (int)$options['batch-size']) : 100;

echo "==================================================\n";
echo " ScholarPlanner — Notification Queue Worker        \n";
echo " Batch Size: {$batchSize} | Time: " . date('Y-m-d H:i:s') . "\n";
echo "==================================================\n";

try {
    $queueService = new NotificationQueueService();
    $processed = $queueService->processQueue($batchSize);
    echo "✔ Processed {$processed} notification(s) from outbox queue.\n";
} catch (\Exception $e) {
    Logger::error("Queue Worker Exception: " . $e->getMessage());
    echo "❌ Execution failed: " . $e->getMessage() . "\n";
    exit(1);
}

exit(0);
