<?php
return [
    'up' => "CREATE TABLE countries (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            iso2 CHAR(2) NOT NULL UNIQUE,
            iso3 CHAR(3) NOT NULL UNIQUE,
            phone_code VARCHAR(16) NOT NULL,
            currency_code CHAR(3) NOT NULL,
            status VARCHAR(20) DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_countries_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    'down' => "DROP TABLE IF EXISTS countries;"
];
