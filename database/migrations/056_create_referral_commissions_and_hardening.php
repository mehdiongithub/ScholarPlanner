<?php

return [
    'up' => function(PDO $db) {
        // 1. Modify referral_code on users table to VARCHAR(8)
        $db->exec("ALTER TABLE users MODIFY COLUMN referral_code VARCHAR(8) DEFAULT NULL");

        // 2. Add referral_partner_id to users if not present
        $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'referral_partner_id'");
        if (!$stmt->fetch()) {
            $db->exec("ALTER TABLE users 
                ADD COLUMN referral_partner_id BIGINT UNSIGNED DEFAULT NULL AFTER referred_by_code,
                ADD CONSTRAINT fk_users_referral_partner FOREIGN KEY (referral_partner_id) REFERENCES users(id) ON DELETE SET NULL
            ");
        }

        // 3. Add commission_percent to users if not present
        $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'commission_percent'");
        if (!$stmt->fetch()) {
            $db->exec("ALTER TABLE users 
                ADD COLUMN commission_percent DECIMAL(5,2) DEFAULT NULL AFTER discount_percent
            ");
        }

        // 4. Add discount & original amount columns to payment_transactions if not present
        $stmt = $db->query("SHOW COLUMNS FROM payment_transactions LIKE 'referral_discount_amount'");
        if (!$stmt->fetch()) {
            $db->exec("ALTER TABLE payment_transactions 
                ADD COLUMN original_amount DECIMAL(10,2) DEFAULT NULL AFTER amount,
                ADD COLUMN referral_discount_amount DECIMAL(10,2) DEFAULT 0.00 AFTER original_amount,
                ADD COLUMN referral_partner_id BIGINT UNSIGNED DEFAULT NULL AFTER referral_code_used,
                ADD CONSTRAINT fk_payment_tx_partner FOREIGN KEY (referral_partner_id) REFERENCES users(id) ON DELETE SET NULL
            ");
        }

        // 5. Create referral_commissions table
        $db->exec("
            CREATE TABLE IF NOT EXISTS referral_commissions (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                partner_id BIGINT UNSIGNED NOT NULL,
                referred_user_id BIGINT UNSIGNED NOT NULL,
                payment_transaction_id BIGINT UNSIGNED NOT NULL UNIQUE,
                transaction_reference VARCHAR(100) NOT NULL,
                original_plan_amount DECIMAL(10,2) NOT NULL,
                referral_discount_percentage DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                referral_discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                actual_paid_amount DECIMAL(10,2) NOT NULL,
                commission_percentage DECIMAL(5,2) NOT NULL,
                commission_basis VARCHAR(50) NOT NULL,
                commission_base_amount DECIMAL(10,2) NOT NULL,
                commission_amount DECIMAL(10,2) NOT NULL,
                payment_date TIMESTAMP NOT NULL,
                attribution_period_start TIMESTAMP NOT NULL,
                attribution_period_end TIMESTAMP NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'earned',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (partner_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (referred_user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (payment_transaction_id) REFERENCES payment_transactions(id) ON DELETE CASCADE,
                INDEX idx_partner_payment_date (partner_id, payment_date),
                INDEX idx_referred_user (referred_user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 6. Update/insert settings
        $settings = [
            ['referral', 'referral_default_discount_percent', '10.00'],
            ['referral', 'referral_default_commission_percent', '30.00'],
            ['referral', 'referral_attribution_window_months', '6'],
            ['referral', 'referral_commission_basis', 'paid_amount_after_discount']
        ];
        $stmtSetting = $db->prepare("
            INSERT INTO settings (group_name, `key`, `value`, is_public, created_at, updated_at)
            VALUES (?, ?, ?, 0, NOW(), NOW())
            ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), updated_at = NOW()
        ");
        foreach ($settings as $s) {
            $stmtSetting->execute($s);
        }
    },
    'down' => function(PDO $db) {
        $db->exec("DROP TABLE IF EXISTS referral_commissions");

        $stmt = $db->query("SHOW COLUMNS FROM payment_transactions LIKE 'referral_discount_amount'");
        if ($stmt->fetch()) {
            $db->exec("ALTER TABLE payment_transactions 
                DROP FOREIGN KEY fk_payment_tx_partner,
                DROP COLUMN referral_partner_id,
                DROP COLUMN referral_discount_amount,
                DROP COLUMN original_amount
            ");
        }

        $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'commission_percent'");
        if ($stmt->fetch()) {
            $db->exec("ALTER TABLE users DROP COLUMN commission_percent");
        }

        $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'referral_partner_id'");
        if ($stmt->fetch()) {
            $db->exec("ALTER TABLE users 
                DROP FOREIGN KEY fk_users_referral_partner,
                DROP COLUMN referral_partner_id
            ");
        }

        $db->exec("ALTER TABLE users MODIFY COLUMN referral_code VARCHAR(50) DEFAULT NULL");
    }
];
