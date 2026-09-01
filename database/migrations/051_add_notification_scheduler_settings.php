<?php

return [
    'up' => function(PDO $db) {
        $columns = $db->query("DESCRIBE notification_logs")->fetchAll(PDO::FETCH_COLUMN);

        // 1. Add processing_started_at column if missing
        if (!in_array('processing_started_at', $columns)) {
            $db->exec("ALTER TABLE notification_logs ADD COLUMN processing_started_at TIMESTAMP NULL DEFAULT NULL AFTER attempts");
        }

        // 2. Seed notification scheduler settings
        $stmtSetting = $db->prepare("INSERT IGNORE INTO settings (`key`, `value`, `type`, `group_name`, `is_public`) VALUES (:key, :value, :type, :group, :pub)");
        $settings = [
            ['key' => 'whatsapp_notifications_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'notifications', 'pub' => 1],
            ['key' => 'whatsapp_allowed_days', 'value' => 'Monday,Tuesday,Wednesday,Thursday,Friday', 'type' => 'string', 'group' => 'notifications', 'pub' => 1],
            ['key' => 'whatsapp_send_time', 'value' => '10:00', 'type' => 'string', 'group' => 'notifications', 'pub' => 1],
            ['key' => 'whatsapp_timezone', 'value' => 'Asia/Karachi', 'type' => 'string', 'group' => 'notifications', 'pub' => 1],
            ['key' => 'whatsapp_batch_size', 'value' => '50', 'type' => 'integer', 'group' => 'notifications', 'pub' => 1],
            ['key' => 'whatsapp_new_match_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'notifications', 'pub' => 1],
            ['key' => 'whatsapp_deadline_reminder_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'notifications', 'pub' => 1]
        ];

        foreach ($settings as $s) {
            $stmtSetting->execute($s);
        }
    },

    'down' => function(PDO $db) {
        $columns = $db->query("DESCRIBE notification_logs")->fetchAll(PDO::FETCH_COLUMN);

        // 1. Drop processing_started_at column if present
        if (in_array('processing_started_at', $columns)) {
            $db->exec("ALTER TABLE notification_logs DROP COLUMN processing_started_at");
        }

        // 2. Remove seeded notification settings
        $db->exec("DELETE FROM settings WHERE group_name = 'notifications'");
    }
];
