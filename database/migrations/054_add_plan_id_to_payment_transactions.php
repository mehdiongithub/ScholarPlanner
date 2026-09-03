<?php

return [
    'up' => function(PDO $db) {
        // Check if plan_id already exists to ensure idempotency
        $stmt = $db->query("SHOW COLUMNS FROM payment_transactions LIKE 'plan_id'");
        if (!$stmt->fetch()) {
            $db->exec("ALTER TABLE payment_transactions 
                ADD COLUMN plan_id BIGINT UNSIGNED DEFAULT NULL AFTER subscription_id,
                ADD CONSTRAINT fk_payment_transactions_plan FOREIGN KEY (plan_id) REFERENCES subscription_plans(id) ON DELETE SET NULL
            ");
        }
    },
    'down' => function(PDO $db) {
        $stmt = $db->query("SHOW COLUMNS FROM payment_transactions LIKE 'plan_id'");
        if ($stmt->fetch()) {
            $db->exec("ALTER TABLE payment_transactions 
                DROP FOREIGN KEY fk_payment_transactions_plan,
                DROP COLUMN plan_id
            ");
        }
    }
];
