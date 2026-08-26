<?php
return [
    'up' => "CREATE TABLE student_profiles (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL UNIQUE,
            date_of_birth DATE DEFAULT NULL,
            gender VARCHAR(20) DEFAULT NULL,
            nationality_country_id BIGINT UNSIGNED DEFAULT NULL,
            residence_country_id BIGINT UNSIGNED DEFAULT NULL,
            residence_state_id BIGINT UNSIGNED DEFAULT NULL,
            city_id BIGINT UNSIGNED DEFAULT NULL,
            profile_photo VARCHAR(255) DEFAULT NULL,
            bio TEXT DEFAULT NULL,
            preferred_study_level VARCHAR(50) DEFAULT NULL,
            preferred_funding_type VARCHAR(50) DEFAULT NULL,
            preferred_destination VARCHAR(100) DEFAULT NULL,
            preferred_start_year INT DEFAULT NULL,
            preferred_currency CHAR(3) DEFAULT 'PKR',
            profile_completion_percentage INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (nationality_country_id) REFERENCES countries(id) ON DELETE SET NULL,
            FOREIGN KEY (residence_country_id) REFERENCES countries(id) ON DELETE SET NULL,
            FOREIGN KEY (residence_state_id) REFERENCES states(id) ON DELETE SET NULL,
            FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    'down' => "DROP TABLE IF EXISTS student_profiles;"
];
