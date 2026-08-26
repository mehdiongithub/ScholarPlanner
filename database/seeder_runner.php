<?php
/**
 * Database Seeder Runner
 *
 * Core PHP SaaS Scholarship Platform
 * Usage:
 *   php database/seeder_runner.php
 */

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/vendor/autoload.php';

// Load Env variables
try {
    if (file_exists(ROOT_PATH . '/.env')) {
        $dotenv = \Dotenv\Dotenv::createImmutable(ROOT_PATH);
        $dotenv->load();
    }
} catch (\Exception $e) {
    // Continue with default values
}

use App\Services\Database;
use App\Services\Logger;

try {
    $db = Database::connection();
} catch (\Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

$seedersDir = ROOT_PATH . '/database/seeders';
if (!file_exists($seedersDir)) {
    mkdir($seedersDir, 0755, true);
}

$files = glob($seedersDir . '/*.php');
sort($files); // Sort files alphabetically

if (empty($files)) {
    echo "✔ No seeders found.\n";
    exit(0);
}

echo "Found " . count($files) . " seeders. Starting execution...\n\n";

foreach ($files as $file) {
    $name = basename($file);
    echo "Seeding: $name... ";
    
    $seeder = require $file;
    if (!is_callable($seeder)) {
        echo "❌ Invalid seeder format! Seeder must return a callable.\n";
        exit(1);
    }
    
    try {
        $db->beginTransaction();
        
        $seeder($db);
        
        $db->commit();
        echo "✔ SUCCESS\n";
    } catch (\Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        echo "❌ FAILED!\n";
        echo "Error: " . $e->getMessage() . "\n";
        Logger::error("Seeding failed: $name | " . $e->getMessage());
        exit(1);
    }
}

echo "\n✔ Seeding completed successfully!\n";
exit(0);
