<?php
return [
    'up' => "CREATE TABLE payment_transactions (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            subscription_id BIGINT UNSIGNED DEFAULT NULL,
            provider VARCHAR(50) NOT NULL,
            transaction_reference VARCHAR(100) NOT NULL UNIQUE,
            provider_transaction_id VARCHAR(100) DEFAULT NULL UNIQUE,
            amount DECIMAL(10,2) NOT NULL,
            currency CHAR(3) NOT NULL,
            status VARCHAR(20) NOT NULL,
            payment_method VARCHAR(50) DEFAULT NULL,
            gateway_response_code VARCHAR(20) DEFAULT NULL,
            gateway_response_message VARCHAR(255) DEFAULT NULL,
            paid_at TIMESTAMP NULL DEFAULT NULL,
            failed_at TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_payments_reference (transaction_reference),
            INDEX idx_payments_provider_tx (provider_transaction_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
            FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    'down' => "DROP TABLE IF EXISTS payment_transactions;"
];
