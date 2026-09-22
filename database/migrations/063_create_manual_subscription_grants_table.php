<?php

return [
    'up' => "CREATE TABLE IF NOT EXISTS manual_subscription_grants (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        plan_id BIGINT UNSIGNED NOT NULL,
        duration_days INT UNSIGNED NOT NULL DEFAULT 30,
        activation_token VARCHAR(64) NOT NULL UNIQUE,
        status ENUM('pending', 'activated', 'revoked', 'expired') NOT NULL DEFAULT 'pending',
        subscription_id BIGINT UNSIGNED NULL,
        created_by BIGINT UNSIGNED NULL,
        admin_notes TEXT NULL,
        activated_at TIMESTAMP NULL DEFAULT NULL,
        token_expires_at TIMESTAMP NULL DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_msg_user (user_id),
        INDEX idx_msg_token (activation_token),
        INDEX idx_msg_status (status),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (plan_id) REFERENCES subscription_plans(id) ON DELETE RESTRICT,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
        FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    'down' => "DROP TABLE IF EXISTS manual_subscription_grants;"
];
