<?php

return [
    'up' => function(PDO $db) {
        // 1. Create scholarship_states table for province/state targeting
        $db->exec("
            CREATE TABLE IF NOT EXISTS scholarship_states (
                scholarship_id BIGINT UNSIGNED NOT NULL,
                state_id BIGINT UNSIGNED NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (scholarship_id, state_id),
                CONSTRAINT fk_sst_scholarship FOREIGN KEY (scholarship_id) REFERENCES scholarships(id) ON DELETE CASCADE,
                CONSTRAINT fk_sst_state FOREIGN KEY (state_id) REFERENCES states(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 2. Create scholarship_institutions table for specific school/college/university targeting
        $db->exec("
            CREATE TABLE IF NOT EXISTS scholarship_institutions (
                scholarship_id BIGINT UNSIGNED NOT NULL,
                institution_id BIGINT UNSIGNED NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (scholarship_id, institution_id),
                CONSTRAINT fk_si_scholarship FOREIGN KEY (scholarship_id) REFERENCES scholarships(id) ON DELETE CASCADE,
                CONSTRAINT fk_si_institution FOREIGN KEY (institution_id) REFERENCES institutions(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 3. Create user_scholarship_reminders table for user-selected scholarship deadline alerts
        $db->exec("
            CREATE TABLE IF NOT EXISTS user_scholarship_reminders (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id BIGINT UNSIGNED NOT NULL,
                scholarship_id BIGINT UNSIGNED NOT NULL,
                reminder_days VARCHAR(50) DEFAULT '3,1',
                is_enabled TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_user_sch_reminder (user_id, scholarship_id),
                CONSTRAINT fk_usr_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                CONSTRAINT fk_usr_scholarship FOREIGN KEY (scholarship_id) REFERENCES scholarships(id) ON DELETE CASCADE,
                INDEX idx_usr_lookup (user_id, is_enabled)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 4. Add deadline reminder preferences to user_preferences table if not present
        $cols = $db->query("SHOW COLUMNS FROM user_preferences LIKE 'deadline_reminder_scope'")->fetchAll();
        if (empty($cols)) {
            $db->exec("
                ALTER TABLE user_preferences 
                ADD COLUMN deadline_reminder_scope VARCHAR(20) NOT NULL DEFAULT 'off' AFTER whatsapp_enabled,
                ADD COLUMN deadline_reminder_days VARCHAR(50) NOT NULL DEFAULT '3,1' AFTER deadline_reminder_scope
            ");
        }
    },

    'down' => function(PDO $db) {
        $db->exec("DROP TABLE IF EXISTS user_scholarship_reminders;");
        $db->exec("DROP TABLE IF EXISTS scholarship_institutions;");
        $db->exec("DROP TABLE IF EXISTS scholarship_states;");
        
        $cols = $db->query("SHOW COLUMNS FROM user_preferences LIKE 'deadline_reminder_scope'")->fetchAll();
        if (!empty($cols)) {
            $db->exec("
                ALTER TABLE user_preferences 
                DROP COLUMN deadline_reminder_days,
                DROP COLUMN deadline_reminder_scope
            ");
        }
    }
];
