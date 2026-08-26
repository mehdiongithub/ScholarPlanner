<?php
return [
    'up' => "CREATE TABLE education_records (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            institution_name VARCHAR(150) NOT NULL,
            degree_level VARCHAR(50) NOT NULL,
            degree_title VARCHAR(150) NOT NULL,
            field_of_study VARCHAR(100) NOT NULL,
            country_id BIGINT UNSIGNED DEFAULT NULL,
            start_date DATE DEFAULT NULL,
            end_date DATE DEFAULT NULL,
            graduation_status VARCHAR(50) DEFAULT 'graduated',
            cgpa DECIMAL(4,2) DEFAULT NULL,
            cgpa_scale DECIMAL(4,2) DEFAULT NULL,
            percentage DECIMAL(5,2) DEFAULT NULL,
            result_status VARCHAR(50) DEFAULT 'declared',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_education_user (user_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (country_id) REFERENCES countries(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    'down' => "DROP TABLE IF EXISTS education_records;"
];
