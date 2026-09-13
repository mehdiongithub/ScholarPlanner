<?php

return [
    'up' => function(PDO $db) {
        $ptIndexes = $db->query("SHOW INDEX FROM payment_transactions")->fetchAll(PDO::FETCH_ASSOC);
        $ptIndexNames = array_column($ptIndexes, 'Key_name');
        if (!in_array('idx_pt_partner_status_paid', $ptIndexNames)) {
            $db->exec("ALTER TABLE payment_transactions ADD INDEX idx_pt_partner_status_paid (referral_partner_id, status, paid_at)");
        }
    },
    'down' => function(PDO $db) {
        $ptIndexes = $db->query("SHOW INDEX FROM payment_transactions")->fetchAll(PDO::FETCH_ASSOC);
        $ptIndexNames = array_column($ptIndexes, 'Key_name');
        if (in_array('idx_pt_partner_status_paid', $ptIndexNames)) {
            $db->exec("ALTER TABLE payment_transactions DROP INDEX idx_pt_partner_status_paid");
        }
    }
];
