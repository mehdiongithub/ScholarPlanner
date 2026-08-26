<?php
return [
    'up' => "CREATE TABLE subscriptions (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            plan_id BIGINT UNSIGNED NOT NULL,
            status VARCHAR(20) NOT NULL,
            starts_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            ends_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            trial_ends_at TIMESTAMP NULL DEFAULT NULL,
            cancelled_at TIMESTAMP NULL DEFAULT NULL,
            auto_renew TINYINT(1) DEFAULT 1,
            provider VARCHAR(50) DEFAULT NULL,
            provider_subscription_id VARCHAR(100) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_subscriptions_user (user_id),
            INDEX idx_subscriptions_status (status),
            INDEX idx_subscriptions_ends (ends_at),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
            FOREIGN KEY (plan_id) REFERENCES subscription_plans(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    'down' => "DROP TABLE IF EXISTS subscriptions;"
];
