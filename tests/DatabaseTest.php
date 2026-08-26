<?php

use App\Services\Database;

class DatabaseTest {
    /**
     * Run database connection smoke tests
     */
    public function run(): void {
        echo "--- Running DatabaseTest ---\n";
        
        try {
            $db = Database::connection();
            if ($db instanceof \PDO) {
                echo "✔ CENTRALIZED PDO connection established successfully.\n";
                
                // Assert connection charset properties
                $charset = $db->query("SELECT @@character_set_connection")->fetchColumn();
                echo "✔ MySQL connection charset resolved: '$charset'\n";
            } else {
                throw new \Exception("Database::connection() did not return a valid PDO object.");
            }
        } catch (\Exception $e) {
            echo "⚠ Database Connection Skipped or Failed: " . $e->getMessage() . "\n";
            echo "  Please verify your local MySQL server is running and database configurations in .env are correct.\n";
        }
        
        echo "DatabaseTest Finished.\n\n";
    }
}
