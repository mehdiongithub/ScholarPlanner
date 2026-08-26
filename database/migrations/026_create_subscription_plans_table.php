<?php
return [
    'up' => "CREATE TABLE subscription_plans (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL,
            slug VARCHAR(50) NOT NULL UNIQUE,
            description VARCHAR(255) DEFAULT NULL,
            billing_interval VARCHAR(20) DEFAULT 'month',
            price DECIMAL(10,2) NOT NULL,
            currency CHAR(3) NOT NULL DEFAULT 'PKR',
            country_code CHAR(2) DEFAULT NULL,
            max_matches INT DEFAULT NULL,
            whatsapp_alerts TINYINT(1) DEFAULT 0,
            email_alerts TINYINT(1) DEFAULT 0,
            deadline_reminders TINYINT(1) DEFAULT 0,
            application_tracking TINYINT(1) DEFAULT 0,
            status VARCHAR(20) DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    'down' => "DROP TABLE IF EXISTS subscription_plans;"
];
