<?php

use App\Services\Database;

class DatabaseMigrationTest {
    /**
     * Verify database migrations execution, seeder counts, tables existence, and index constraints.
     */
    public function run(): void {
        echo "--- Running DatabaseMigrationTest ---\n";
        
        try {
            $db = Database::connection();
        } catch (\Exception $e) {
            echo "⚠ Database Connection Skipped (MySQL server offline or .env config not set): " . $e->getMessage() . "\n";
            echo "DatabaseMigrationTest Skipped.\n\n";
            return;
        }
        
        // 1. Run migrations via CLI
        echo "Running migration runner...\n";
        $migrationOutput = shell_exec('php "' . ROOT_PATH . '/database/migration_runner.php"');
        echo $migrationOutput . "\n";
        
        // 2. Run seeders via CLI
        echo "Running seeder runner...\n";
        $seederOutput = shell_exec('php "' . ROOT_PATH . '/database/seeder_runner.php"');
        echo $seederOutput . "\n";
        
        // 3. Assert all 33 database tables + migrations table exist
        $tables = [
            'migrations', 'countries', 'states', 'cities', 'roles', 'permissions', 
            'role_permissions', 'users', 'student_profiles', 'education_records', 
            'fields_of_study', 'documents', 'scholarship_categories', 'scholarships', 
            'scholarship_category_map', 'scholarship_eligibility_rules', 'scholarship_benefits', 
            'scholarship_documents', 'scholarship_sources', 'scholarship_deadlines', 
            'user_preferences', 'notification_preferences', 'scholarship_matches', 
            'saved_scholarships', 'scholarship_applications', 'notification_logs', 
            'subscription_plans', 'subscriptions', 'payment_transactions', 
            'payment_webhook_logs', 'employee_assignments', 'audit_logs', 
            'settings', 'contact_messages'
        ];
        
        $dbName = config('database.database');
        foreach ($tables as $table) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = :dbName AND table_name = :table");
            $stmt->execute(['dbName' => $dbName, 'table' => $table]);
            $exists = (int)$stmt->fetchColumn() > 0;
            if (!$exists) {
                throw new \Exception("Database Migration Failure: Table '$table' is missing in schema.");
            }
            echo "✔ Verified Table Existence: $table\n";
        }
        
        // 4. Assert indexing and unique keys
        echo "Verifying index constraints...\n";
        $stmt = $db->query("SHOW INDEX FROM users WHERE Key_name = 'email'");
        if ($stmt->rowCount() === 0) {
            throw new \Exception("Constraint Failure: Unique index on users.email is missing.");
        }
        echo "✔ Unique constraint users.email verified.\n";
        
        $stmt = $db->query("SHOW INDEX FROM scholarships WHERE Key_name = 'slug'");
        if ($stmt->rowCount() === 0) {
            throw new \Exception("Constraint Failure: Unique index on scholarships.slug is missing.");
        }
        echo "✔ Unique constraint scholarships.slug verified.\n";
        
        // 5. Assert default seeded metrics
        $rolesCount = $db->query("SELECT COUNT(*) FROM roles")->fetchColumn();
        if ($rolesCount < 3) {
            throw new \Exception("Seeding Failure: Roles count is $rolesCount (expected 3).");
        }
        echo "✔ Seeder verified: roles count is $rolesCount.\n";
        
        $countriesCount = $db->query("SELECT COUNT(*) FROM countries")->fetchColumn();
        if ($countriesCount < 12) {
             throw new \Exception("Seeding Failure: Countries count is $countriesCount (expected 12).");
        }
        echo "✔ Seeder verified: countries count is $countriesCount.\n";

        $adminEmail = $_ENV['ADMIN_EMAIL'] ?? 'admin@scholarmatch.com';
        $adminUser = $db->prepare("SELECT id FROM users WHERE email = :email");
        $adminUser->execute(['email' => $adminEmail]);
        if (!$adminUser->fetchColumn()) {
            throw new \Exception("Seeding Failure: Seeded Administrator account '$adminEmail' is missing.");
        }
        echo "✔ Seeder verified: Admin user '$adminEmail' exists.\n";

        echo "DatabaseMigrationTest PASSED.\n\n";
    }
}
