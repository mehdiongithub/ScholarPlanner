<?php
return [
    'up' => "CREATE TABLE notification_logs (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            scholarship_id BIGINT UNSIGNED DEFAULT NULL,
            notification_type VARCHAR(50) NOT NULL,
            channel VARCHAR(20) NOT NULL,
            recipient VARCHAR(100) NOT NULL,
            subject VARCHAR(200) DEFAULT NULL,
            message_reference VARCHAR(255) DEFAULT NULL,
            provider_message_id VARCHAR(255) DEFAULT NULL,
            status VARCHAR(20) DEFAULT 'sent',
            sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            delivered_at TIMESTAMP NULL DEFAULT NULL,
            failed_at TIMESTAMP NULL DEFAULT NULL,
            failure_reason VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_notif_logs_user (user_id),
            INDEX idx_notif_logs_status (status),
            INDEX idx_notif_logs_created (created_at),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (scholarship_id) REFERENCES scholarships(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    'down' => "DROP TABLE IF EXISTS notification_logs;"
];
