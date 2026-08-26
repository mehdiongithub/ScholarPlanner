<?php

return [
    'up' => function(PDO $db) {
        // 1. Add is_current to education_records
        $db->exec("ALTER TABLE education_records ADD COLUMN is_current TINYINT(1) DEFAULT 0 AFTER result_status");

        // 2. Create user_preferred_countries table
        $db->exec("
            CREATE TABLE IF NOT EXISTS user_preferred_countries (
                user_id BIGINT UNSIGNED NOT NULL,
                country_id BIGINT UNSIGNED NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (user_id, country_id),
                CONSTRAINT fk_upc_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                CONSTRAINT fk_upc_country FOREIGN KEY (country_id) REFERENCES countries(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // 3. Create user_preferred_fields table
        $db->exec("
            CREATE TABLE IF NOT EXISTS user_preferred_fields (
                user_id BIGINT UNSIGNED NOT NULL,
                field_of_study_id BIGINT UNSIGNED NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (user_id, field_of_study_id),
                CONSTRAINT fk_upf_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                CONSTRAINT fk_upf_field FOREIGN KEY (field_of_study_id) REFERENCES fields_of_study(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // 4. Create user_preferred_degree_levels table
        $db->exec("
            CREATE TABLE IF NOT EXISTS user_preferred_degree_levels (
                user_id BIGINT UNSIGNED NOT NULL,
                degree_level VARCHAR(50) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (user_id, degree_level),
                CONSTRAINT fk_updl_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    },

    'down' => function(PDO $db) {
        $db->exec("DROP TABLE IF EXISTS user_preferred_degree_levels");
        $db->exec("DROP TABLE IF EXISTS user_preferred_fields");
        $db->exec("DROP TABLE IF EXISTS user_preferred_countries");
        $db->exec("ALTER TABLE education_records DROP COLUMN is_current");
    }
];
