<?php
return [
    'up' => "CREATE TABLE users (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            role_id BIGINT UNSIGNED NOT NULL,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            phone VARCHAR(30) DEFAULT NULL UNIQUE,
            whatsapp_phone VARCHAR(30) DEFAULT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            status VARCHAR(20) DEFAULT 'pending',
            email_verified_at TIMESTAMP NULL DEFAULT NULL,
            phone_verified_at TIMESTAMP NULL DEFAULT NULL,
            whatsapp_opt_in TINYINT(1) DEFAULT 0,
            email_opt_in TINYINT(1) DEFAULT 1,
            last_login_at TIMESTAMP NULL DEFAULT NULL,
            last_login_ip VARCHAR(45) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_users_role (role_id),
            INDEX idx_users_status (status),
            FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    'down' => "DROP TABLE IF EXISTS users;"
];
