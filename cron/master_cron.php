<?php
/**
 * ScholarPlanner — Hostinger Master Cron Orchestration Entrypoint
 *
 * Designed specifically for Hostinger cPanel / Shared Hosting Cron Jobs:
 *   * * * * * cd /home/u123456789/public_html && /usr/bin/php cron/master_cron.php > /dev/null 2>&1
 *
 * Orchestrates:
 *   1. Stale Queue Item Recovery (every minute)
 *   2. Scheduled Scholarship Matching Alerts (at configured time / days)
 *   3. Scheduled Deadline Reminders (at configured time / days)
 *   4. Daily Subscription Protection Lifecycle (at midnight)
 *   5. Abandoned Referral Claim Cleanup
 *   6. Outbox Notification Queue Worker (batch-size dispatch)
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo "Access Denied: CLI execution context only.\n";
    exit(1);
}

// Forward execution to scheduler.php
require_once __DIR__ . '/scheduler.php';
