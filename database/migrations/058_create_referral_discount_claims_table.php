<?php

return [
    'up' => function(PDO $db) {
        $db->exec("
            CREATE TABLE IF NOT EXISTS referral_discount_claims (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                referred_user_id BIGINT UNSIGNED NOT NULL UNIQUE,
                partner_id BIGINT UNSIGNED NOT NULL,
                payment_transaction_id BIGINT UNSIGNED NULL,
                status ENUM('reserved', 'consumed') NOT NULL DEFAULT 'reserved',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (referred_user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (partner_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (payment_transaction_id) REFERENCES payment_transactions(id) ON DELETE SET NULL,
                INDEX idx_claim_tx (payment_transaction_id),
                INDEX idx_claim_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    },
    'down' => function(PDO $db) {
        $db->exec("DROP TABLE IF EXISTS referral_discount_claims");
    }
];
