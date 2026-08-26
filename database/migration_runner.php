<?php
/**
 * Database Migration Runner
 *
 * Core PHP SaaS Scholarship Platform
 * Usage:
 *   php database/migration_runner.php
 *   php database/migration_runner.php --rollback
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

// 1. Ensure migrations table exists
try {
    $db->exec("CREATE TABLE IF NOT EXISTS migrations (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        migration VARCHAR(255) NOT NULL UNIQUE,
        batch INT UNSIGNED NOT NULL,
        executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
} catch (\PDOException $e) {
    echo "❌ Failed to create migrations tracking table: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Parse command arguments
$rollback = isset($argv[1]) && $argv[1] === '--rollback';

$migrationsDir = ROOT_PATH . '/database/migrations';
if (!file_exists($migrationsDir)) {
    mkdir($migrationsDir, 0755, true);
}

if ($rollback) {
    // Rollback last batch
    $lastBatch = $db->query("SELECT MAX(batch) FROM migrations")->fetchColumn();
    if (empty($lastBatch)) {
        echo "✔ No migrations to roll back.\n";
        exit(0);
    }
    
    $toRollback = $db->prepare("SELECT migration FROM migrations WHERE batch = :batch ORDER BY id DESC");
    $toRollback->execute(['batch' => $lastBatch]);
    $migrations = $toRollback->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Starting rollback of Batch $lastBatch (" . count($migrations) . " migrations)...\n\n";
    
    foreach ($migrations as $name) {
        $filePath = $migrationsDir . '/' . $name;
        if (!file_exists($filePath)) {
            echo "❌ File not found for migration: $name. Rollback aborted.\n";
            exit(1);
        }
        
        $migration = require $filePath;
        if (!is_array($migration) || !isset($migration['down'])) {
            echo "❌ Invalid rollback format in $name\n";
            exit(1);
        }
        
        echo "Rolling back $name... ";
        
        try {
            if (is_callable($migration['down'])) {
                $migration['down']($db);
            } else {
                $db->exec($migration['down']);
            }
            
            // Delete record
            $del = $db->prepare("DELETE FROM migrations WHERE migration = :migration");
            $del->execute(['migration' => $name]);
            
            echo "✔ SUCCESS\n";
        } catch (\Exception $e) {
            echo "❌ FAILED!\n";
            echo "Error: " . $e->getMessage() . "\n";
            Logger::error("Rollback failed: $name | " . $e->getMessage());
            exit(1);
        }
    }
    
    echo "\n✔ Rollback of Batch $lastBatch completed successfully!\n";
    exit(0);
} else {
    // Run pending migrations
    $completed = $db->query("SELECT migration FROM migrations")->fetchAll(PDO::FETCH_COLUMN);
    
    $files = glob($migrationsDir . '/*.php');
    sort($files); // Natural sort ensures numerical order (001, 002...)
    
    $pending = [];
    foreach ($files as $file) {
        $name = basename($file);
        if (!in_array($name, $completed)) {
            $pending[] = [
                'name' => $name,
                'path' => $file
            ];
        }
    }
    
    if (empty($pending)) {
        echo "✔ No pending migrations.\n";
        exit(0);
    }
    
    $currentBatch = (int)$db->query("SELECT MAX(batch) FROM migrations")->fetchColumn() + 1;
    
    echo "Found " . count($pending) . " pending migrations. Starting Batch $currentBatch...\n\n";
    
    foreach ($pending as $m) {
        echo "Migrating: " . $m['name'] . "... ";
        
        $migration = require $m['path'];
        if (!is_array($migration) || !isset($migration['up'])) {
            echo "❌ Invalid migration format!\n";
            exit(1);
        }
        
        try {
            if (is_callable($migration['up'])) {
                $migration['up']($db);
            } else {
                $db->exec($migration['up']);
            }
            
            // Record migration
            $ins = $db->prepare("INSERT INTO migrations (migration, batch) VALUES (:migration, :batch)");
            $ins->execute([
                'migration' => $m['name'],
                'batch' => $currentBatch
            ]);
            
            echo "✔ SUCCESS\n";
        } catch (\Exception $e) {
            echo "❌ FAILED!\n";
            echo "Error: " . $e->getMessage() . "\n";
            Logger::error("Migration failed: " . $m['name'] . " | " . $e->getMessage());
            exit(1);
        }
    }
    
    echo "\n✔ All migrations run successfully!\n";
    exit(0);
}
