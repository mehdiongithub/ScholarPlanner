<?php
return [
    'up' => "CREATE TABLE states (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            country_id BIGINT UNSIGNED NOT NULL,
            name VARCHAR(100) NOT NULL,
            code VARCHAR(20) DEFAULT NULL,
            status VARCHAR(20) DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_states_country_name (country_id, name),
            FOREIGN KEY (country_id) REFERENCES countries(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    'down' => "DROP TABLE IF EXISTS states;"
];
