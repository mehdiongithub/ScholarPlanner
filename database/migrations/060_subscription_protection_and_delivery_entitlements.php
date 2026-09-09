<?php

return [
    'up' => function(PDO $db) {
        // 1. Update subscriptions table
        $subCols = $db->query("DESCRIBE subscriptions")->fetchAll(PDO::FETCH_COLUMN);

        if (!in_array('normal_ends_at', $subCols)) {
            $db->exec("ALTER TABLE subscriptions ADD COLUMN normal_ends_at TIMESTAMP NULL DEFAULT NULL AFTER ends_at");
            $db->exec("UPDATE subscriptions SET normal_ends_at = ends_at WHERE normal_ends_at IS NULL");
        }

        if (!in_array('final_expired_at', $subCols)) {
            $db->exec("ALTER TABLE subscriptions ADD COLUMN final_expired_at TIMESTAMP NULL DEFAULT NULL AFTER trial_ends_at");
        }

        if (!in_array('expiry_reason', $subCols)) {
            $db->exec("ALTER TABLE subscriptions ADD COLUMN expiry_reason VARCHAR(100) DEFAULT NULL AFTER final_expired_at");
        }

        if (!in_array('minimum_delivered_required', $subCols)) {
            $db->exec("ALTER TABLE subscriptions ADD COLUMN minimum_delivered_required INT UNSIGNED NOT NULL DEFAULT 5 AFTER expiry_reason");
        }

        $subIndexes = $db->query("SHOW INDEX FROM subscriptions")->fetchAll(PDO::FETCH_ASSOC);
        $subIndexNames = array_column($subIndexes, 'Key_name');
        if (!in_array('idx_sub_status_normal_ends', $subIndexNames)) {
            $db->exec("ALTER TABLE subscriptions ADD INDEX idx_sub_status_normal_ends (status, normal_ends_at)");
        }

        // 2. Update subscription_usage table
        $usageCols = $db->query("DESCRIBE subscription_usage")->fetchAll(PDO::FETCH_COLUMN);

        if (!in_array('subscription_id', $usageCols)) {
            $db->exec("ALTER TABLE subscription_usage ADD COLUMN subscription_id BIGINT UNSIGNED NULL AFTER user_id");
        }

        if (!in_array('qualifying_delivered_count', $usageCols)) {
            $db->exec("ALTER TABLE subscription_usage ADD COLUMN qualifying_delivered_count INT NOT NULL DEFAULT 0 AFTER usage_count");
        }

        if (!in_array('minimum_required', $usageCols)) {
            $db->exec("ALTER TABLE subscription_usage ADD COLUMN minimum_required INT NOT NULL DEFAULT 5 AFTER qualifying_delivered_count");
        }

        if (!in_array('protected_state', $usageCols)) {
            $db->exec("ALTER TABLE subscription_usage ADD COLUMN protected_state TINYINT(1) NOT NULL DEFAULT 0 AFTER minimum_required");
        }

        if (!in_array('final_expired_state', $usageCols)) {
            $db->exec("ALTER TABLE subscription_usage ADD COLUMN final_expired_state TINYINT(1) NOT NULL DEFAULT 0 AFTER protected_state");
        }

        $usageIndexes = $db->query("SHOW INDEX FROM subscription_usage")->fetchAll(PDO::FETCH_ASSOC);
        $usageIndexNames = array_column($usageIndexes, 'Key_name');
        if (!in_array('idx_sub_usage_sub', $usageIndexNames)) {
            $db->exec("ALTER TABLE subscription_usage ADD INDEX idx_sub_usage_sub (subscription_id)");
        }

        // 3. Update notification_logs table
        $notifCols = $db->query("DESCRIBE notification_logs")->fetchAll(PDO::FETCH_COLUMN);

        if (!in_array('subscription_id', $notifCols)) {
            $db->exec("ALTER TABLE notification_logs ADD COLUMN subscription_id BIGINT UNSIGNED NULL AFTER scholarship_id");
        }

        if (!in_array('delivered_at', $notifCols)) {
            $db->exec("ALTER TABLE notification_logs ADD COLUMN delivered_at TIMESTAMP NULL DEFAULT NULL AFTER sent_at");
        }

        $notifIndexes = $db->query("SHOW INDEX FROM notification_logs")->fetchAll(PDO::FETCH_ASSOC);
        $notifIndexNames = array_column($notifIndexes, 'Key_name');
        if (!in_array('idx_notif_sub_status', $notifIndexNames)) {
            $db->exec("ALTER TABLE notification_logs ADD INDEX idx_notif_sub_status (subscription_id, status)");
        }
    },

    'down' => function(PDO $db) {
        // Drop notification_logs columns & indexes
        $notifIndexes = $db->query("SHOW INDEX FROM notification_logs")->fetchAll(PDO::FETCH_ASSOC);
        $notifIndexNames = array_column($notifIndexes, 'Key_name');
        if (in_array('idx_notif_sub_status', $notifIndexNames)) {
            $db->exec("ALTER TABLE notification_logs DROP INDEX idx_notif_sub_status");
        }

        $notifCols = $db->query("DESCRIBE notification_logs")->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('delivered_at', $notifCols)) {
            $db->exec("ALTER TABLE notification_logs DROP COLUMN delivered_at");
        }
        if (in_array('subscription_id', $notifCols)) {
            $db->exec("ALTER TABLE notification_logs DROP COLUMN subscription_id");
        }

        // Drop subscription_usage columns & indexes
        $usageIndexes = $db->query("SHOW INDEX FROM subscription_usage")->fetchAll(PDO::FETCH_ASSOC);
        $usageIndexNames = array_column($usageIndexes, 'Key_name');
        if (in_array('idx_sub_usage_sub', $usageIndexNames)) {
            $db->exec("ALTER TABLE subscription_usage DROP INDEX idx_sub_usage_sub");
        }

        $usageCols = $db->query("DESCRIBE subscription_usage")->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('final_expired_state', $usageCols)) {
            $db->exec("ALTER TABLE subscription_usage DROP COLUMN final_expired_state");
        }
        if (in_array('protected_state', $usageCols)) {
            $db->exec("ALTER TABLE subscription_usage DROP COLUMN protected_state");
        }
        if (in_array('minimum_required', $usageCols)) {
            $db->exec("ALTER TABLE subscription_usage DROP COLUMN minimum_required");
        }
        if (in_array('qualifying_delivered_count', $usageCols)) {
            $db->exec("ALTER TABLE subscription_usage DROP COLUMN qualifying_delivered_count");
        }
        if (in_array('subscription_id', $usageCols)) {
            $db->exec("ALTER TABLE subscription_usage DROP COLUMN subscription_id");
        }

        // Drop subscriptions columns & indexes
        $subIndexes = $db->query("SHOW INDEX FROM subscriptions")->fetchAll(PDO::FETCH_ASSOC);
        $subIndexNames = array_column($subIndexes, 'Key_name');
        if (in_array('idx_sub_status_normal_ends', $subIndexNames)) {
            $db->exec("ALTER TABLE subscriptions DROP INDEX idx_sub_status_normal_ends");
        }

        $subCols = $db->query("DESCRIBE subscriptions")->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('minimum_delivered_required', $subCols)) {
            $db->exec("ALTER TABLE subscriptions DROP COLUMN minimum_delivered_required");
        }
        if (in_array('expiry_reason', $subCols)) {
            $db->exec("ALTER TABLE subscriptions DROP COLUMN expiry_reason");
        }
        if (in_array('final_expired_at', $subCols)) {
            $db->exec("ALTER TABLE subscriptions DROP COLUMN final_expired_at");
        }
        if (in_array('normal_ends_at', $subCols)) {
            $db->exec("ALTER TABLE subscriptions DROP COLUMN normal_ends_at");
        }
    }
];
