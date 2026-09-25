<?php

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(ROOT_PATH);
$dotenv->safeLoad();

use App\Services\Database;

try {
    $db = Database::connection();

    echo "=== NOTIFICATION_LOG (WHATSAPP) ===\n";
    $stmt = $db->query("SELECT id, type, channel, recipient, status, attempts, error_message, created_at, sent_at FROM notification_log WHERE channel = 'whatsapp' ORDER BY id DESC LIMIT 5");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT) . "\n\n";

    echo "=== NOTIFICATION_QUEUE (WHATSAPP) ===\n";
    $stmt = $db->query("SELECT id, type, channel, recipient, status, attempts, available_at, created_at FROM notification_queue WHERE channel = 'whatsapp' ORDER BY id DESC LIMIT 5");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT) . "\n";
} catch (Exception $e) {
    echo "DB Error: " . $e->getMessage() . "\n";
}
