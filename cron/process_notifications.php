<?php
/**
 * Unified CLI Cron Entry Point: Notification Scheduler & Worker
 * 
 * Usage:
 *   php cron/process_notifications.php             (Standard scheduled run)
 *   php cron/process_notifications.php --dry-run   (Inspect schedule & eligible pending queue without making changes)
 *   php cron/process_notifications.php --force     (Bypass day/time window check for manual testing)
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo "Access Denied: CLI runtime execution context only.\n";
    exit(1);
}

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/vendor/autoload.php';

// 1. Load environment variables
try {
    if (file_exists(ROOT_PATH . '/.env')) {
        $dotenv = \Dotenv\Dotenv::createImmutable(ROOT_PATH);
        $dotenv->load();
    }
} catch (\Exception $e) {
    // Continue with system env
}

use App\Services\Database;
use App\Services\Logger;
use App\Services\NotificationSchedulerService;

// 2. Initialize Database
try {
    $db = Database::connection();
} catch (\Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// 3. Parse CLI options
$options = getopt('', ['dry-run', 'force', 'batch-size:']);
$isDryRun = isset($options['dry-run']);
$isForced = isset($options['force']);
$customBatchSize = isset($options['batch-size']) ? (int)$options['batch-size'] : null;

$scheduler = new NotificationSchedulerService($db);
$settings = $scheduler->getSettings();

// 4. Set Runtime Timezone
$tzName = $settings['whatsapp_timezone'] ?? ($_ENV['APP_TIMEZONE'] ?? 'Asia/Karachi');
date_default_timezone_set($tzName);

echo "==================================================\n";
echo " ScholarMatch — Notification Scheduler & Worker   \n";
echo " Timezone: {$tzName} | Current Time: " . date('Y-m-d H:i:s (l)') . "\n";
echo "==================================================\n";

// 5. Evaluate Schedule Due
$schedule = $scheduler->isScheduleDue();

if ($isDryRun) {
    echo "\n[DRY RUN MODE ACTIVE — NO DATA WILL BE MODIFIED]\n\n";
    $dryRunReport = $scheduler->runDryRun();
    echo "Schedule Due Status: " . ($dryRunReport['schedule_due'] ? 'YES (READY)' : 'NO (' . strtoupper($dryRunReport['schedule_reason']) . ')') . "\n";
    echo "Current Local Time:  {$dryRunReport['current_time']} ({$dryRunReport['current_weekday']})\n";
    echo "Configured Window:   Daily at {$dryRunReport['configured_send_time']} on [" . implode(', ', $dryRunReport['allowed_days']) . "]\n";
    echo "Allowed Alert Types: [" . implode(', ', $dryRunReport['allowed_types']) . "]\n";
    echo "Configured Batch:    {$dryRunReport['batch_size']}\n";
    echo "Total Pending Alerts: {$dryRunReport['total_pending']}\n";

    if (!empty($dryRunReport['pending_breakdown'])) {
        echo "\nPending Alerts by Type:\n";
        foreach ($dryRunReport['pending_breakdown'] as $type => $cnt) {
            echo "  - {$type}: {$cnt}\n";
        }
    }

    if (!empty($dryRunReport['sample_candidates'])) {
        echo "\nSample Pending Candidates (Top " . count($dryRunReport['sample_candidates']) . "):\n";
        foreach ($dryRunReport['sample_candidates'] as $idx => $cand) {
            $num = $idx + 1;
            echo "  {$num}. ID #{$cand['id']} | User #{$cand['user_id']} | Sch #{$cand['scholarship_id']} | Type: {$cand['notification_type']} | Recipient: {$cand['recipient']}\n";
        }
    }
    echo "\n✔ Dry-run inspection complete.\n";
    exit(0);
}

// 6. Check Schedule Conditions (unless forced)
if (!$schedule['due'] && !$isForced) {
    $reason = $schedule['reason'];
    Logger::info("Notification scheduler skipped. Reason: {$reason}.");
    echo "ℹ Scheduler skipped: " . strtoupper($reason) . "\n";
    echo "  - Current Time: " . date('H:i (l)') . "\n";
    echo "  - Configured Send Time: {$settings['whatsapp_send_time']}\n";
    echo "  - Allowed Days: [" . implode(', ', $settings['whatsapp_allowed_days']) . "]\n";
    exit(0);
}

// 7. Acquire Scheduler Lock
echo "Acquiring scheduler concurrency lock...\n";
if (!$scheduler->acquireLock('scholarship_notification_scheduler', 0)) {
    Logger::info("Scheduler execution skipped: Another scheduler process holds the active lock.");
    echo "ℹ Lock unavailable: Another scheduler worker process is actively running. Exiting.\n";
    exit(0);
}

echo "✔ Concurrency lock acquired.\n";

try {
    // 8. Stale Processing Recovery
    $recovery = $scheduler->recoverStaleProcessing(15, 3);
    if ($recovery['recovered'] > 0 || $recovery['failed'] > 0) {
        echo "ℹ Recovered {$recovery['recovered']} stale processing jobs, marked {$recovery['failed']} expired jobs as failed.\n";
    }

    // 9. Determine Batch Size and Allowed Types
    $batchSize = ($customBatchSize && $customBatchSize > 0) ? $customBatchSize : $settings['whatsapp_batch_size'];
    $allowedTypes = $schedule['allowed_types'];
    if (empty($allowedTypes) && $isForced) {
        $allowedTypes = [\App\Services\NotificationTypes::NEW_MATCH, \App\Services\NotificationTypes::DEADLINE_REMINDER];
    }

    echo "Claiming pending notification batch (Batch Size: {$batchSize})...\n";
    $claimedBatch = $scheduler->claimPendingBatch($batchSize, $allowedTypes, 'whatsapp');
    $claimedCount = count($claimedBatch);
    echo "✔ Claimed {$claimedCount} notifications for processing.\n";

    if ($claimedCount > 0) {
        $dispatchService = new \App\Services\NotificationDispatchService($db);
        $results = $dispatchService->dispatchBatch($claimedBatch);

        echo "✔ Dispatch completed for {$claimedCount} items:\n";
        echo "  - Sent: {$results['sent']}\n";
        echo "  - Scheduled for Retry: {$results['retrying']}\n";
        echo "  - Failed: {$results['failed']}\n";
        echo "  - Cancelled: {$results['cancelled']}\n";

        Logger::info("Notification dispatch batch finished. Total: {$claimedCount}, Sent: {$results['sent']}, Retrying: {$results['retrying']}, Failed: {$results['failed']}, Cancelled: {$results['cancelled']}.");
    }

} catch (\Exception $e) {
    Logger::error("Scheduler Exception: " . $e->getMessage());
    echo "❌ Scheduler Error: " . $e->getMessage() . "\n";
} finally {
    // 10. Always release lock
    $scheduler->releaseLock('scholarship_notification_scheduler');
    echo "✔ Scheduler lock released.\n";
}

echo "Scheduler cycle completed.\n";
exit(0);
