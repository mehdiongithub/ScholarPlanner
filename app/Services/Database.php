<?php

namespace App\Services;

use PDO;
use PDOException;
use Exception;

class Database {
    private static ?PDO $connection = null;

    /**
     * Get or create central PDO database connection
     */
    public static function connection(): PDO {
        if (self::$connection === null) {
            $host = config('database.host', '127.0.0.1');
            $port = config('database.port', '3306');
            $db   = config('database.database', '');
            $user = config('database.username', '');
            $pass = config('database.password', '');
            $charset = config('database.charset', 'utf8mb4');

            $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$connection = new PDO($dsn, $user, $pass, $options);
                $tz = date('P');
                self::$connection->exec("SET time_zone = '$tz'");
            } catch (PDOException $e) {
                // Self-healing: If MySQL database is missing, attempt to create it automatically
                if (strpos($e->getMessage(), 'Unknown database') !== false || $e->getCode() === 1049) {
                    try {
                        $tempDsn = "mysql:host=$host;port=$port;charset=$charset";
                        $tempPdo = new PDO($tempDsn, $user, $pass, $options);
                        $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
                        
                        // Retry connection
                        self::$connection = new PDO($dsn, $user, $pass, $options);
                        $tz = date('P');
                        self::$connection->exec("SET time_zone = '$tz'");
                        return self::$connection;
                    } catch (PDOException $createException) {
                        Logger::error("Database self-healing creation failed: " . $createException->getMessage());
                    }
                }
                
                // Log but do not expose raw connection details to users
                Logger::error("Database Connection Error: " . $e->getMessage());
                throw new Exception("Database connection failed. Please check the logs.");
            }
        }

        return self::$connection;
    }

    /**
     * Prepare and execute sql query with bound parameters
     */
    public static function query(string $sql, array $params = []): \PDOStatement {
        try {
            $stmt = self::connection()->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            Logger::error("SQL execution error: " . $e->getMessage() . " | SQL: " . $sql);
            throw new Exception("Database query failed.");
        }
    }

    /**
     * Fetch a single row matching criteria
     */
    public static function fetch(string $sql, array $params = []) {
        return self::query($sql, $params)->fetch();
    }

    /**
     * Fetch all rows matching criteria
     */
    public static function fetchAll(string $sql, array $params = []): array {
        return self::query($sql, $params)->fetchAll();
    }

    /**
     * Wrap database updates in transaction callback
     */
    public static function transaction(callable $callback) {
        $pdo = self::connection();
        try {
            $pdo->beginTransaction();
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            Logger::error("Database transaction rolled back: " . $e->getMessage());
            throw $e;
        }
    }
}
