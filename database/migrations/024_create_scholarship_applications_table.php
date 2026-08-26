<?php
return [
    'up' => "CREATE TABLE scholarship_applications (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            scholarship_id BIGINT UNSIGNED NOT NULL,
            status VARCHAR(50) DEFAULT 'interested',
            applied_at TIMESTAMP NULL DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            outcome VARCHAR(50) DEFAULT NULL,
            outcome_date DATE DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_applications_user_scholarship (user_id, scholarship_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (scholarship_id) REFERENCES scholarships(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    'down' => "DROP TABLE IF EXISTS scholarship_applications;"
];
