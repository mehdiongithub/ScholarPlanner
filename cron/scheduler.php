<?php
/**
 * Master Application Cron Scheduler & Dispatcher
 *
 * Designed for Hostinger and production Linux/cPanel crons:
 *   * * * * * /usr/bin/php /home/username/public_html/cron/scheduler.php > /dev/null 2>&1
 *
 * This single master scheduler is triggered every minute.
 * The application evaluates Admin Panel configurations to decide:
 *   1. When Automatic Scholarship Matching is due (time, day, timezone).
 *   2. When Deadline Reminders are due (time, day, timezone).
 *   3. When Subscription lifecycle maintenance is due.
 *   4. When Referral claim expiration is due.
 *   5. Dispatches pending WhatsApp and Email queues automatically.
 *
 * CLI Options:
 *   php cron/scheduler.php                   (Standard 1-minute tick)
 *   php cron/scheduler.php --dry-run         (Inspect status without modifying data)
 *   php cron/scheduler.php --force-matching  (Force trigger matching algorithm now)
 *   php cron/scheduler.php --force-deadline  (Force trigger deadline reminders now)
 *   php cron/scheduler.php --force-all       (Force trigger matching, deadline & queue now)
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo "Access Denied: CLI runtime execution context only.\n";
    exit(1);
}

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/vendor/autoload.php';

// Load environment variables
try {
    if (file_exists(ROOT_PATH . '/.env')) {
        $dotenv = \Dotenv\Dotenv::createImmutable(ROOT_PATH);
        $dotenv->load();
    }
} catch (\Exception $e) {
    // Fail-safe silently
}

use App\Services\Database;
use App\Services\Logger;
use App\Services\NotificationSchedulerService;
use App\Services\NotificationQueueService;
use App\Services\SubscriptionService;
use App\Services\ReferralService;

// Initialize Database Connection
try {
    $db = Database::connection();
} catch (\Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Parse CLI Options
$options = getopt('', ['dry-run', 'force-matching', 'force-deadline', 'force-all', 'batch-size:']);
$isDryRun = isset($options['dry-run']);
$forceMatching = isset($options['force-matching']) || isset($options['force-all']);
$forceDeadline = isset($options['force-deadline']) || isset($options['force-all']);
$customBatchSize = isset($options['batch-size']) ? (int)$options['batch-size'] : null;

$scheduler = new NotificationSchedulerService($db);
$settings = $scheduler->getSettings();

// Default Runtime Timezone for scheduler output
$tzName = $settings['matching_timezone'] ?? ($_ENV['APP_TIMEZONE'] ?? 'Asia/Karachi');
date_default_timezone_set($tzName);

echo "=================================================================\n";
echo " ScholarPlanner — Master Application Cron Scheduler\n";
echo " Time: " . date('Y-m-d H:i:s (l)') . " | Timezone: {$tzName}\n";
echo "=================================================================\n";

// DRY-RUN INSPECTION
if ($isDryRun) {
    echo "\n[DRY-RUN INSPECTION MODE ACTIVE — NO JOBS WILL BE EXECUTED]\n\n";
    $dryRunReport = $scheduler->runDryRun();

    echo "Master Automatic WhatsApp: " . ($dryRunReport['master_enabled'] ? 'ENABLED' : 'DISABLED') . "\n\n";

    echo "--- Matching Schedule ---\n";
    echo "  • Status:      " . ($dryRunReport['matching']['enabled'] ? 'ENABLED' : 'DISABLED') . "\n";
    echo "  • Configured:  {$dryRunReport['matching']['send_time']} ({$dryRunReport['matching']['timezone']}) on [" . implode(', ', $dryRunReport['matching']['allowed_days']) . "]\n";
    echo "  • Due Now:     " . ($dryRunReport['matching']['due'] ? 'YES (READY)' : 'NO (' . strtoupper($dryRunReport['matching']['reason']) . ')') . "\n";
    echo "  • Next Run:    {$dryRunReport['matching']['next_run']}\n";
    echo "  • Last Run:    " . ($dryRunReport['matching']['last_run_at'] ?: 'Never') . " (" . ($dryRunReport['matching']['last_run_status'] ?: 'N/A') . ")\n\n";

    echo "--- Deadline Reminder Schedule ---\n";
    echo "  • Status:      " . ($dryRunReport['deadline']['enabled'] ? 'ENABLED' : 'DISABLED') . "\n";
    echo "  • Configured:  {$dryRunReport['deadline']['send_time']} ({$dryRunReport['deadline']['timezone']}) on [" . implode(', ', $dryRunReport['deadline']['allowed_days']) . "]\n";
    echo "  • Due Now:     " . ($dryRunReport['deadline']['due'] ? 'YES (READY)' : 'NO (' . strtoupper($dryRunReport['deadline']['reason']) . ')') . "\n";
    echo "  • Next Run:    {$dryRunReport['deadline']['next_run']}\n";
    echo "  • Last Run:    " . ($dryRunReport['deadline']['last_run_at'] ?: 'Never') . " (" . ($dryRunReport['deadline']['last_run_status'] ?: 'N/A') . ")\n\n";

    echo "Total Pending Outbox Queue: {$dryRunReport['total_pending']}\n";
    echo "✔ Dry-run inspection complete.\n";
    exit(0);
}

// 1. Acquire Master Scheduler Concurrency Lock
if (!$scheduler->acquireLock('app_master_scheduler', 0)) {
    echo "ℹ Master scheduler skipped: Another scheduler process holds the active lock.\n";
    exit(0);
}

try {
    // 2. Recover Stale Worker Jobs (self-healing after worker timeouts/crashes)
    $recovery = $scheduler->recoverStaleProcessing(15, 3);
    if ($recovery['recovered'] > 0 || $recovery['failed'] > 0) {
        echo "ℹ Recovered {$recovery['recovered']} stale jobs, marked {$recovery['failed']} expired jobs as failed.\n";
    }

    // 3. Evaluate & Execute Automatic Scholarship Matching Schedule
    $matchingDue = $scheduler->isMatchingScheduleDue();
    if ($matchingDue['due'] || $forceMatching) {
        $slotKey = $matchingDue['slot'] ?? ('matching_forced_' . date('Y-m-d_H:i'));
        echo "🚀 Executing Automatic Scholarship Matching (Slot: {$slotKey})...\n";

        try {
            $matchingResult = $scheduler->runMatchingJob();
            $scheduler->recordJobExecution('matching', 'SUCCESS', $slotKey);
            echo "✔ Matching completed: {$matchingResult['users_processed']} users evaluated, {$matchingResult['matches_found']} matches found, {$matchingResult['whatsapp_batches']} WhatsApp digest batches enqueued, {$matchingResult['emails_enqueued']} emails enqueued.\n";
            Logger::info("Master Scheduler: Automatic Matching completed successfully.", $matchingResult);
        } catch (\Exception $e) {
            $scheduler->recordJobExecution('matching', 'FAILED', $slotKey, $e->getMessage());
            echo "❌ Matching failed: " . $e->getMessage() . "\n";
            Logger::error("Master Scheduler: Automatic Matching failed: " . $e->getMessage());
        }
    } else {
        echo "ℹ Automatic Matching not due ({$matchingDue['reason']}).\n";
    }

    // 4. Evaluate & Execute Deadline Reminders Schedule
    $deadlineDue = $scheduler->isDeadlineScheduleDue();
    if ($deadlineDue['due'] || $forceDeadline) {
        $slotKey = $deadlineDue['slot'] ?? ('deadline_forced_' . date('Y-m-d_H:i'));
        echo "🚀 Executing Deadline Reminders Schedule (Slot: {$slotKey})...\n";

        try {
            $deadlineResult = $scheduler->runDeadlineRemindersJob();
            $scheduler->recordJobExecution('deadline', 'SUCCESS', $slotKey);
            echo "✔ Deadline reminders completed: {$deadlineResult['users_processed']} users checked, {$deadlineResult['reminders_enqueued']} reminders enqueued.\n";
            Logger::info("Master Scheduler: Deadline Reminders completed successfully.", $deadlineResult);
        } catch (\Exception $e) {
            $scheduler->recordJobExecution('deadline', 'FAILED', $slotKey, $e->getMessage());
            echo "❌ Deadline reminders failed: " . $e->getMessage() . "\n";
            Logger::error("Master Scheduler: Deadline Reminders failed: " . $e->getMessage());
        }
    } else {
        echo "ℹ Deadline Reminders not due ({$deadlineDue['reason']}).\n";
    }

    // 5. Subscription Expiry Lifecycle (Daily check)
    $todayDateStr = date('Y-m-d');
    $lastSubRunDate = $db->query("SELECT `value` FROM settings WHERE `key` = 'sub_expiry_last_date' LIMIT 1")->fetchColumn();
    $currentHour = (int)date('H');
    $currentMin = (int)date('i');

    if ($lastSubRunDate !== $todayDateStr || isset($options['force-all'])) {
        try {
            $subResults = SubscriptionService::processDailyLifecycle($db);

            // Update last run marker
            $stmtMark = $db->prepare("
                INSERT INTO settings (`key`, `value`, `type`, `group_name`, `is_public`) 
                VALUES ('sub_expiry_last_date', :d, 'string', 'notifications_runtime', 1)
                ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `updated_at` = NOW()
            ");
            $stmtMark->execute(['d' => $todayDateStr]);

            echo "ℹ Subscriptions maintenance: {$subResults['evaluated']} evaluated, {$subResults['protected']} protected, {$subResults['expired']} expired, {$subResults['reminders']} reminders.\n";
        } catch (\Exception $e) {
            Logger::error("Master Scheduler: Subscription expiry maintenance error: " . $e->getMessage());
        }
    }

    // 6. Referral Reservations Cleanup (Periodic)
    try {
        $ttl = ReferralService::getReservationTtlMinutes($db);
        $expiredClaims = ReferralService::expireAbandonedReservations($db, $ttl);
        if ($expiredClaims > 0) {
            echo "ℹ Referral cleanup: {$expiredClaims} abandoned reservations expired.\n";
        }
    } catch (\Exception $e) {
        Logger::error("Master Scheduler: Referral cleanup error: " . $e->getMessage());
    }

    // 7. Notification Queue Worker (Auto-dispatches pending outbox queue)
    $batchSize = $customBatchSize ?: (int)($settings['whatsapp_batch_size'] ?? 50);
    echo "Processing pending notification queue (Batch size: {$batchSize})...\n";

    try {
        $queueService = new NotificationQueueService();
        $processedCount = $queueService->processQueue($batchSize);
        echo "✔ Queue Worker: Dispatched {$processedCount} notification(s).\n";
    } catch (\Exception $e) {
        echo "❌ Queue Worker Error: " . $e->getMessage() . "\n";
        Logger::error("Master Scheduler: Queue Worker Error: " . $e->getMessage());
    }

} catch (\Exception $e) {
    echo "❌ Scheduler Critical Exception: " . $e->getMessage() . "\n";
    Logger::error("Master Scheduler Critical Exception: " . $e->getMessage());
} finally {
    // 8. Always release master lock
    $scheduler->releaseLock('app_master_scheduler');
}

echo "Scheduler cycle finished.\n";
exit(0);