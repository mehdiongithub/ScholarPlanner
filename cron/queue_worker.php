<?php
/**
 * Cron Job: Notification Queue Worker
 * Runs periodically (e.g., every minute) via CLI to dispatch pending alerts.
 * Usage: php cron/queue_worker.php
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
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'UTC');

echo "Running notification queue worker...\n";

try {
    $queueService = new NotificationQueueService();
    $processed = $queueService->processQueue(100);
    echo "✔ Processed {$processed} notifications from outbox queue.\n";
} catch (\Exception $e) {
    Logger::error("Queue Worker Exception: " . $e->getMessage());
    echo "❌ Execution failed: " . $e->getMessage() . "\n";
    exit(1);
}
