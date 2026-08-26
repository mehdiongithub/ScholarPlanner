<?php
return [
    'up' => "CREATE TABLE cities (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            state_id BIGINT UNSIGNED NOT NULL,
            name VARCHAR(100) NOT NULL,
            status VARCHAR(20) DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_cities_state_name (state_id, name),
            FOREIGN KEY (state_id) REFERENCES states(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    'down' => "DROP TABLE IF EXISTS cities;"
];
