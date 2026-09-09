<?php
/**
 * Cron Job: Referral Discount Claims Cleanup
 * Expires abandoned first-payment discount reservations older than configured TTL.
 * Runs periodically via command line interface (e.g. every 10-30 minutes).
 * Usage: php cron/referral_cleanup.php
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
use App\Services\ReferralService;
use App\Services\Logger;

try {
    $db = Database::connection();
} catch (\Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

$lockStmt = $db->prepare("SELECT GET_LOCK('cron_referral_cleanup', 0)");
$lockStmt->execute();
if ((int)$lockStmt->fetchColumn() !== 1) {
    echo "ℹ Another referral cleanup process is currently running. Exiting.\n";
    exit(0);
}

echo "Starting referral discount reservations cleanup job...\n";

try {
    $ttl = ReferralService::getReservationTtlMinutes($db);
    echo "Configured reservation TTL: {$ttl} minutes.\n";

    $expiredCount = ReferralService::expireAbandonedReservations($db, $ttl);

    echo "✔ Successfully processed referral claims cleanup. Expired {$expiredCount} abandoned reservations.\n";
    Logger::info("Referral cleanup cron: expired {$expiredCount} abandoned reservations with TTL {$ttl}m.");
    $db->query("SELECT RELEASE_LOCK('cron_referral_cleanup')");
    exit(0);
} catch (\Exception $e) {
    $db->query("SELECT RELEASE_LOCK('cron_referral_cleanup')");
    echo "❌ Error during referral claims cleanup: " . $e->getMessage() . "\n";
    Logger::error("Referral cleanup cron error: " . $e->getMessage());
    exit(1);
}
