<?php

return [
    'up' => function(PDO $db) {
        $stmt = $db->query("SHOW COLUMNS FROM subscription_plans LIKE 'duration_days'");
        if (!$stmt->fetch()) {
            $db->exec("ALTER TABLE subscription_plans 
                ADD COLUMN duration_days INT UNSIGNED DEFAULT NULL AFTER billing_interval
            ");
            // Set 30 days for existing monthly plans
            $db->exec("UPDATE subscription_plans SET duration_days = 30 WHERE billing_interval = 'month' AND duration_days IS NULL");
        }
    },
    'down' => function(PDO $db) {
        $stmt = $db->query("SHOW COLUMNS FROM subscription_plans LIKE 'duration_days'");
        if ($stmt->fetch()) {
            $db->exec("ALTER TABLE subscription_plans DROP COLUMN duration_days");
        }
    }
];
