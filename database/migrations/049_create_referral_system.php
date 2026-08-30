<?php

return [
    'up' => function(PDO $db) {
        // 1. Add referral columns to users table
        $db->exec("ALTER TABLE users 
            ADD COLUMN referral_code VARCHAR(50) DEFAULT NULL UNIQUE AFTER status,
            ADD COLUMN referred_by_code VARCHAR(50) DEFAULT NULL AFTER referral_code,
            ADD COLUMN discount_percent DECIMAL(5,2) DEFAULT 10.00 AFTER referred_by_code
        ");

        // 2. Add discount and referral code used to payment_transactions table
        $db->exec("ALTER TABLE payment_transactions 
            ADD COLUMN discount_percent DECIMAL(5,2) DEFAULT 0.00 AFTER currency,
            ADD COLUMN referral_code_used VARCHAR(50) DEFAULT NULL AFTER discount_percent
        ");

        // 3. Add description column to fields_of_study table
        $db->exec("ALTER TABLE fields_of_study 
            ADD COLUMN description VARCHAR(255) DEFAULT NULL AFTER name
        ");

        // 4. Create referral_signups table
        $db->exec("
            CREATE TABLE IF NOT EXISTS referral_signups (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                partner_id BIGINT UNSIGNED NOT NULL,
                referred_user_id BIGINT UNSIGNED NOT NULL UNIQUE,
                referral_code VARCHAR(50) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (partner_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (referred_user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 5. Insert system setting for attribution window
        $db->exec("
            INSERT INTO settings (group_name, `key`, `value`, is_public, created_at, updated_at)
            VALUES ('referral', 'referral_attribution_window_months', '3', 0, NOW(), NOW())
            ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);
        ");
    },
    'down' => function(PDO $db) {
        // 1. Drop settings row
        $db->exec("DELETE FROM settings WHERE `key` = 'referral_attribution_window_months'");

        // 2. Drop table
        $db->exec("DROP TABLE IF EXISTS referral_signups");

        // 3. Remove description column from fields_of_study
        $db->exec("ALTER TABLE fields_of_study DROP COLUMN description");

        // 4. Remove columns from payment_transactions
        $db->exec("ALTER TABLE payment_transactions 
            DROP COLUMN discount_percent,
            DROP COLUMN referral_code_used
        ");

        // 5. Remove columns from users
        $db->exec("ALTER TABLE users 
            DROP COLUMN referral_code,
            DROP COLUMN referred_by_code,
            DROP COLUMN discount_percent
        ");
    }
];
