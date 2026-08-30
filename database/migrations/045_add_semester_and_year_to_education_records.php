<?php

return [
    'up' => function(PDO $db) {
        // Check if current_semester column already exists to prevent duplicate column errors
        $q = $db->query("SHOW COLUMNS FROM education_records LIKE 'current_semester'");
        if (!$q->fetch()) {
            $db->exec("ALTER TABLE education_records ADD COLUMN current_semester VARCHAR(50) DEFAULT NULL AFTER is_current");
        }
        
        $q2 = $db->query("SHOW COLUMNS FROM education_records LIKE 'passing_year'");
        if (!$q2->fetch()) {
            $db->exec("ALTER TABLE education_records ADD COLUMN passing_year INT DEFAULT NULL AFTER current_semester");
        }
    },

    'down' => function(PDO $db) {
        $db->exec("ALTER TABLE education_records DROP COLUMN passing_year");
        $db->exec("ALTER TABLE education_records DROP COLUMN current_semester");
    }
];
