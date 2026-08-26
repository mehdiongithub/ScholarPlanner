<?php

return [
    'up' => function(PDO $db) {
        $columns = $db->query("DESCRIBE notification_logs")->fetchAll(PDO::FETCH_COLUMN);

        // 1. Add payload column if missing
        if (!in_array('payload', $columns)) {
            $db->exec("ALTER TABLE notification_logs ADD COLUMN payload LONGTEXT DEFAULT NULL AFTER recipient");
        }

        // 2. Add attempts column if missing
        if (!in_array('attempts', $columns)) {
            $db->exec("ALTER TABLE notification_logs ADD COLUMN attempts INT UNSIGNED DEFAULT 0 AFTER status");
        }

        // 3. Add available_at column if missing
        if (!in_array('available_at', $columns)) {
            $db->exec("ALTER TABLE notification_logs ADD COLUMN available_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER attempts");
        }

        // 4. Add error_message column if missing
        if (!in_array('error_message', $columns)) {
            $db->exec("ALTER TABLE notification_logs ADD COLUMN error_message TEXT DEFAULT NULL AFTER failed_at");
        }

        // 5. Add idempotency_key column if missing
        if (!in_array('idempotency_key', $columns)) {
            $db->exec("ALTER TABLE notification_logs ADD COLUMN idempotency_key VARCHAR(255) DEFAULT NULL AFTER provider_message_id");
        }

        // 6. Add updated_at column if missing
        if (!in_array('updated_at', $columns)) {
            $db->exec("ALTER TABLE notification_logs ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
        }

        // 7. Change status default if not already altered (default 'pending')
        $db->exec("ALTER TABLE notification_logs CHANGE COLUMN status status VARCHAR(20) NOT NULL DEFAULT 'pending'");

        // 8. Backfill existing null/empty idempotency_keys determinantwise before adding UNIQUE key
        $db->exec("UPDATE notification_logs SET idempotency_key = CONCAT('legacy_', id) WHERE idempotency_key IS NULL OR idempotency_key = ''");

        // 9. Inspect existing indexes to avoid duplicate indexes
        $indexes = $db->query("SHOW INDEX FROM notification_logs")->fetchAll(PDO::FETCH_ASSOC);
        $indexNames = array_column($indexes, 'Key_name');

        if (!in_array('uq_notif_idempotency', $indexNames)) {
            $db->exec("ALTER TABLE notification_logs ADD UNIQUE KEY uq_notif_idempotency (idempotency_key)");
        }
        if (!in_array('idx_notif_status_avail', $indexNames)) {
            $db->exec("ALTER TABLE notification_logs ADD INDEX idx_notif_status_avail (status, available_at)");
        }
        if (!in_array('idx_notif_channel', $indexNames)) {
            $db->exec("ALTER TABLE notification_logs ADD INDEX idx_notif_channel (channel)");
        }
    },

    'down' => function(PDO $db) {
        $columns = $db->query("DESCRIBE notification_logs")->fetchAll(PDO::FETCH_COLUMN);
        $indexes = $db->query("SHOW INDEX FROM notification_logs")->fetchAll(PDO::FETCH_ASSOC);
        $indexNames = array_column($indexes, 'Key_name');

        if (in_array('uq_notif_idempotency', $indexNames)) {
            $db->exec("ALTER TABLE notification_logs DROP INDEX uq_notif_idempotency");
        }
        if (in_array('idx_notif_status_avail', $indexNames)) {
            $db->exec("ALTER TABLE notification_logs DROP INDEX idx_notif_status_avail");
        }
        if (in_array('idx_notif_channel', $indexNames)) {
            $db->exec("ALTER TABLE notification_logs DROP INDEX idx_notif_channel");
        }

        if (in_array('payload', $columns)) {
            $db->exec("ALTER TABLE notification_logs DROP COLUMN payload");
        }
        if (in_array('attempts', $columns)) {
            $db->exec("ALTER TABLE notification_logs DROP COLUMN attempts");
        }
        if (in_array('available_at', $columns)) {
            $db->exec("ALTER TABLE notification_logs DROP COLUMN available_at");
        }
        if (in_array('error_message', $columns)) {
            $db->exec("ALTER TABLE notification_logs DROP COLUMN error_message");
        }
        if (in_array('idempotency_key', $columns)) {
            $db->exec("ALTER TABLE notification_logs DROP COLUMN idempotency_key");
        }
        if (in_array('updated_at', $columns)) {
            $db->exec("ALTER TABLE notification_logs DROP COLUMN updated_at");
        }

        $db->exec("ALTER TABLE notification_logs CHANGE COLUMN status status VARCHAR(20) DEFAULT 'sent'");
    }
];
