<?php
return [
    'up' => "CREATE TABLE payment_webhook_logs (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            provider VARCHAR(50) NOT NULL,
            event_type VARCHAR(100) NOT NULL,
            external_event_id VARCHAR(100) DEFAULT NULL,
            transaction_reference VARCHAR(100) DEFAULT NULL,
            payload_hash CHAR(64) NOT NULL,
            processing_status VARCHAR(20) DEFAULT 'pending',
            processed_at TIMESTAMP NULL DEFAULT NULL,
            failure_reason VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    'down' => "DROP TABLE IF EXISTS payment_webhook_logs;"
];
