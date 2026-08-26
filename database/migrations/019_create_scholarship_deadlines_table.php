<?php
return [
    'up' => "CREATE TABLE scholarship_deadlines (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            scholarship_id BIGINT UNSIGNED NOT NULL,
            deadline_type VARCHAR(50) DEFAULT 'single',
            deadline_date DATE NOT NULL,
            timezone VARCHAR(50) DEFAULT 'UTC',
            status VARCHAR(20) DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (scholarship_id) REFERENCES scholarships(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    'down' => "DROP TABLE IF EXISTS scholarship_deadlines;"
];
