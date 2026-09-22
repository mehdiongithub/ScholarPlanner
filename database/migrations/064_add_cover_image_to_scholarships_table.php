<?php

return [
    'up' => function(PDO $db) {
        $cols = $db->query("SHOW COLUMNS FROM scholarships LIKE 'cover_image'")->fetchAll();
        if (empty($cols)) {
            $db->exec("
                ALTER TABLE scholarships 
                ADD COLUMN cover_image VARCHAR(255) DEFAULT NULL AFTER application_deadline
            ");
        }
    },
    'down' => function(PDO $db) {
        $cols = $db->query("SHOW COLUMNS FROM scholarships LIKE 'cover_image'")->fetchAll();
        if (!empty($cols)) {
            $db->exec("
                ALTER TABLE scholarships 
                DROP COLUMN cover_image
            ");
        }
    }
];
