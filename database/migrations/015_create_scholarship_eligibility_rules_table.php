<?php
return [
    'up' => "CREATE TABLE scholarship_eligibility_rules (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            scholarship_id BIGINT UNSIGNED NOT NULL UNIQUE,
            minimum_age INT DEFAULT NULL,
            maximum_age INT DEFAULT NULL,
            minimum_cgpa DECIMAL(4,2) DEFAULT NULL,
            cgpa_scale DECIMAL(4,2) DEFAULT NULL,
            minimum_percentage DECIMAL(5,2) DEFAULT NULL,
            gender_requirement VARCHAR(20) DEFAULT NULL,
            nationality_requirement VARCHAR(100) DEFAULT NULL,
            residence_requirement VARCHAR(100) DEFAULT NULL,
            degree_requirement VARCHAR(100) DEFAULT NULL,
            study_level_requirement VARCHAR(100) DEFAULT NULL,
            language_requirement VARCHAR(100) DEFAULT NULL,
            ielts_required TINYINT(1) DEFAULT 0,
            ielts_minimum_score DECIMAL(3,1) DEFAULT NULL,
            toefl_required TINYINT(1) DEFAULT 0,
            toefl_minimum_score INT DEFAULT NULL,
            work_experience_required TINYINT(1) DEFAULT 0,
            minimum_work_experience_months INT DEFAULT NULL,
            admission_required TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (scholarship_id) REFERENCES scholarships(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    'down' => "DROP TABLE IF EXISTS scholarship_eligibility_rules;"
];
