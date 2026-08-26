<?php
return [
    'up' => "CREATE TABLE user_preferences (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL UNIQUE,
            preferred_country BIGINT UNSIGNED DEFAULT NULL,
            preferred_study_level VARCHAR(50) DEFAULT NULL,
            funding_preferences VARCHAR(50) DEFAULT NULL,
            notification_frequency VARCHAR(20) DEFAULT 'daily',
            timezone VARCHAR(50) DEFAULT 'UTC',
            language VARCHAR(10) DEFAULT 'en',
            daily_alert_enabled TINYINT(1) DEFAULT 1,
            email_enabled TINYINT(1) DEFAULT 1,
            whatsapp_enabled TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (preferred_country) REFERENCES countries(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    'down' => "DROP TABLE IF EXISTS user_preferences;"
];
