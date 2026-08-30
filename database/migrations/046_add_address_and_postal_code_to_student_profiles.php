<?php

return [
    'up' => function(PDO $db) {
        $q = $db->query("SHOW COLUMNS FROM student_profiles LIKE 'address'");
        if (!$q->fetch()) {
            $db->exec("ALTER TABLE student_profiles ADD COLUMN address VARCHAR(255) DEFAULT NULL AFTER bio");
        }
        
        $q2 = $db->query("SHOW COLUMNS FROM student_profiles LIKE 'postal_code'");
        if (!$q2->fetch()) {
            $db->exec("ALTER TABLE student_profiles ADD COLUMN postal_code VARCHAR(20) DEFAULT NULL AFTER address");
        }
    },

    'down' => function(PDO $db) {
        $db->exec("ALTER TABLE student_profiles DROP COLUMN postal_code");
        $db->exec("ALTER TABLE student_profiles DROP COLUMN address");
    }
];
