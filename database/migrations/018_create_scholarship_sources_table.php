<?php
return [
    'up' => "CREATE TABLE scholarship_sources (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            scholarship_id BIGINT UNSIGNED NOT NULL,
            source_name VARCHAR(150) NOT NULL,
            source_url VARCHAR(255) NOT NULL,
            source_type VARCHAR(50) DEFAULT NULL,
            last_checked_at TIMESTAMP NULL DEFAULT NULL,
            verification_status VARCHAR(20) DEFAULT 'unverified',
            notes VARCHAR(500) DEFAULT NULL,
            checked_by BIGINT UNSIGNED DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (scholarship_id) REFERENCES scholarships(id) ON DELETE CASCADE,
            FOREIGN KEY (checked_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    'down' => "DROP TABLE IF EXISTS scholarship_sources;"
];
