<?php
return [
    'up' => "CREATE TABLE settings (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `key` VARCHAR(50) NOT NULL UNIQUE,
            `value` TEXT DEFAULT NULL,
            `type` VARCHAR(20) DEFAULT 'string',
            `group_name` VARCHAR(50) DEFAULT 'general',
            `is_public` TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    'down' => "DROP TABLE IF EXISTS settings;"
];
