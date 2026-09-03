<?php

return [
    'up' => function(PDO $db) {
        $cols = $db->query("SHOW COLUMNS FROM user_preferences LIKE 'preferred_channel'")->fetchAll();
        if (empty($cols)) {
            $db->exec("
                ALTER TABLE user_preferences 
                ADD COLUMN preferred_channel VARCHAR(20) NOT NULL DEFAULT 'email' AFTER deadline_reminder_days,
                ADD COLUMN allow_multi_channel TINYINT(1) NOT NULL DEFAULT 0 AFTER preferred_channel
            ");
        }
    },

    'down' => function(PDO $db) {
        $cols = $db->query("SHOW COLUMNS FROM user_preferences LIKE 'preferred_channel'")->fetchAll();
        if (!empty($cols)) {
            $db->exec("
                ALTER TABLE user_preferences 
                DROP COLUMN allow_multi_channel,
                DROP COLUMN preferred_channel
            ");
        }
    }
];
