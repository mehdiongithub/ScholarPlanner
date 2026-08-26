<?php
return [
    'up' => "
        -- 1. Add application_reference if not exists
        SET @dbname = DATABASE();
        SET @tablename = 'scholarship_applications';
        SET @columnname = 'application_reference';
        SET @preparedStatement = (SELECT IF(
          (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
           WHERE table_schema = @dbname
             AND table_name = @tablename
             AND column_name = @columnname) > 0,
          'SELECT 1',
          'ALTER TABLE scholarship_applications ADD COLUMN application_reference VARCHAR(100) DEFAULT NULL AFTER status'
        ));
        PREPARE alterIfNotExists FROM @preparedStatement;
        EXECUTE alterIfNotExists;
        DEALLOCATE PREPARE alterIfNotExists;

        -- 2. Rename notes to personal_notes if notes exists and personal_notes does not
        SET @columnname2 = 'notes';
        SET @preparedStatement2 = (SELECT IF(
          (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
           WHERE table_schema = @dbname
             AND table_name = @tablename
             AND column_name = @columnname2) > 0,
          'ALTER TABLE scholarship_applications CHANGE COLUMN notes personal_notes TEXT DEFAULT NULL',
          'SELECT 1'
        ));
        PREPARE alterRename FROM @preparedStatement2;
        EXECUTE alterRename;
        DEALLOCATE PREPARE alterRename;

        -- 3. Create status history table
        CREATE TABLE IF NOT EXISTS scholarship_application_history (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            application_id BIGINT UNSIGNED NOT NULL,
            old_status VARCHAR(50) NOT NULL,
            new_status VARCHAR(50) NOT NULL,
            changed_by BIGINT UNSIGNED NOT NULL,
            changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            notes TEXT DEFAULT NULL,
            FOREIGN KEY (application_id) REFERENCES scholarship_applications(id) ON DELETE CASCADE,
            FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_sah_application (application_id),
            INDEX idx_sah_changed_by (changed_by)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",
    'down' => "
        DROP TABLE IF EXISTS scholarship_application_history;
        
        -- Revert column changes if needed
        SET @dbname = DATABASE();
        SET @tablename = 'scholarship_applications';
        SET @columnname = 'personal_notes';
        SET @preparedStatement = (SELECT IF(
          (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
           WHERE table_schema = @dbname
             AND table_name = @tablename
             AND column_name = @columnname) > 0,
          'ALTER TABLE scholarship_applications CHANGE COLUMN personal_notes notes TEXT DEFAULT NULL',
          'SELECT 1'
        ));
        PREPARE alterRevertNotes FROM @preparedStatement;
        EXECUTE alterRevertNotes;
        DEALLOCATE PREPARE alterRevertNotes;

        SET @columnname2 = 'application_reference';
        SET @preparedStatement2 = (SELECT IF(
          (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
           WHERE table_schema = @dbname
             AND table_name = @tablename
             AND column_name = @columnname2) > 0,
          'ALTER TABLE scholarship_applications DROP COLUMN application_reference',
          'SELECT 1'
        ));
        PREPARE alterRevertRef FROM @preparedStatement2;
        EXECUTE alterRevertRef;
        DEALLOCATE PREPARE alterRevertRef;
    "
];
