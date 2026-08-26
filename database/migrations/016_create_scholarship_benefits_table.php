<?php
return [
    'up' => "CREATE TABLE scholarship_benefits (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            scholarship_id BIGINT UNSIGNED NOT NULL,
            benefit_type VARCHAR(50) NOT NULL,
            title VARCHAR(150) NOT NULL,
            description TEXT DEFAULT NULL,
            amount DECIMAL(12,2) DEFAULT NULL,
            currency CHAR(3) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (scholarship_id) REFERENCES scholarships(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    'down' => "DROP TABLE IF EXISTS scholarship_benefits;"
];
