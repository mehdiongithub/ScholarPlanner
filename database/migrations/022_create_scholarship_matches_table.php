<?php
return [
    'up' => "CREATE TABLE scholarship_matches (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            scholarship_id BIGINT UNSIGNED NOT NULL,
            match_score INT NOT NULL,
            match_status VARCHAR(20) DEFAULT 'new',
            matched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            reasons TEXT DEFAULT NULL,
            mismatch_reasons TEXT DEFAULT NULL,
            engine_version VARCHAR(20) DEFAULT '1.0',
            viewed_at TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_matches_user_scholarship (user_id, scholarship_id),
            INDEX idx_matches_user (user_id),
            INDEX idx_matches_scholarship (scholarship_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (scholarship_id) REFERENCES scholarships(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    'down' => "DROP TABLE IF EXISTS scholarship_matches;"
];
