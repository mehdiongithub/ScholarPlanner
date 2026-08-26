<?php

return [
    'up' => function(PDO $db) {
        // 1. Add extra metadata columns to scholarships table
        $db->exec("ALTER TABLE scholarships 
            ADD COLUMN is_featured TINYINT(1) DEFAULT 0 AFTER application_deadline,
            ADD COLUMN quality_status VARCHAR(50) DEFAULT 'good' AFTER is_featured,
            ADD COLUMN recurring_interval VARCHAR(50) DEFAULT 'non-recurring' AFTER quality_status
        ");

        // 2. Create scholarship_fields mapping table (many-to-many disciplines)
        $db->exec("
            CREATE TABLE IF NOT EXISTS scholarship_fields (
                scholarship_id BIGINT UNSIGNED NOT NULL,
                field_of_study_id BIGINT UNSIGNED NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (scholarship_id, field_of_study_id),
                CONSTRAINT fk_sf_scholarship FOREIGN KEY (scholarship_id) REFERENCES scholarships(id) ON DELETE CASCADE,
                CONSTRAINT fk_sf_field FOREIGN KEY (field_of_study_id) REFERENCES fields_of_study(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // 3. Create scholarship_countries mapping table (many-to-many target study countries)
        $db->exec("
            CREATE TABLE IF NOT EXISTS scholarship_countries (
                scholarship_id BIGINT UNSIGNED NOT NULL,
                country_id BIGINT UNSIGNED NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (scholarship_id, country_id),
                CONSTRAINT fk_sc_scholarship FOREIGN KEY (scholarship_id) REFERENCES scholarships(id) ON DELETE CASCADE,
                CONSTRAINT fk_sc_country FOREIGN KEY (country_id) REFERENCES countries(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // 4. Create scholarship_eligible_nationalities mapping table
        $db->exec("
            CREATE TABLE IF NOT EXISTS scholarship_eligible_nationalities (
                scholarship_id BIGINT UNSIGNED NOT NULL,
                country_id BIGINT UNSIGNED NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (scholarship_id, country_id),
                CONSTRAINT fk_sen_scholarship FOREIGN KEY (scholarship_id) REFERENCES scholarships(id) ON DELETE CASCADE,
                CONSTRAINT fk_sen_country FOREIGN KEY (country_id) REFERENCES countries(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // 5. Create scholarship_degree_levels mapping table (many-to-many study levels)
        $db->exec("
            CREATE TABLE IF NOT EXISTS scholarship_degree_levels (
                scholarship_id BIGINT UNSIGNED NOT NULL,
                degree_level VARCHAR(50) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (scholarship_id, degree_level),
                CONSTRAINT fk_sdl_scholarship FOREIGN KEY (scholarship_id) REFERENCES scholarships(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // 6. Create scholarship_languages table (dynamic IELTS/TOEFL requirements)
        $db->exec("
            CREATE TABLE IF NOT EXISTS scholarship_languages (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                scholarship_id BIGINT UNSIGNED NOT NULL,
                test_name VARCHAR(50) NOT NULL,
                minimum_score VARCHAR(20) NOT NULL,
                is_required TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_sl_scholarship FOREIGN KEY (scholarship_id) REFERENCES scholarships(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    },

    'down' => function(PDO $db) {
        $db->exec("DROP TABLE IF EXISTS scholarship_languages");
        $db->exec("DROP TABLE IF EXISTS scholarship_degree_levels");
        $db->exec("DROP TABLE IF EXISTS scholarship_eligible_nationalities");
        $db->exec("DROP TABLE IF EXISTS scholarship_countries");
        $db->exec("DROP TABLE IF EXISTS scholarship_fields");
        $db->exec("ALTER TABLE scholarships 
            DROP COLUMN recurring_interval,
            DROP COLUMN quality_status,
            DROP COLUMN is_featured
        ");
    }
];
