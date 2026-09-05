<?php

return [
    'up' => function(PDO $db) {
        // 1. Modify status column to include 'expired'
        $db->exec("
            ALTER TABLE referral_discount_claims 
            MODIFY COLUMN status ENUM('reserved', 'consumed', 'expired') NOT NULL DEFAULT 'reserved'
        ");

        // 2. Add expires_at column if not present
        $stmt = $db->query("SHOW COLUMNS FROM referral_discount_claims LIKE 'expires_at'");
        if (!$stmt->fetch()) {
            $db->exec("
                ALTER TABLE referral_discount_claims 
                ADD COLUMN expires_at TIMESTAMP NULL DEFAULT NULL AFTER status
            ");
        }

        // 3. Add index on (status, expires_at) for efficient cleanup cron queries
        $stmtIdx = $db->query("SHOW INDEX FROM referral_discount_claims WHERE Key_name = 'idx_claim_status_expires'");
        if (!$stmtIdx->fetch()) {
            $db->exec("
                ALTER TABLE referral_discount_claims 
                ADD INDEX idx_claim_status_expires (status, expires_at)
            ");
        }
    },
    'down' => function(PDO $db) {
        $stmtIdx = $db->query("SHOW INDEX FROM referral_discount_claims WHERE Key_name = 'idx_claim_status_expires'");
        if ($stmtIdx->fetch()) {
            $db->exec("ALTER TABLE referral_discount_claims DROP INDEX idx_claim_status_expires");
        }

        $stmt = $db->query("SHOW COLUMNS FROM referral_discount_claims LIKE 'expires_at'");
        if ($stmt->fetch()) {
            $db->exec("ALTER TABLE referral_discount_claims DROP COLUMN expires_at");
        }

        $db->exec("
            ALTER TABLE referral_discount_claims 
            MODIFY COLUMN status ENUM('reserved', 'consumed') NOT NULL DEFAULT 'reserved'
        ");
    }
];
