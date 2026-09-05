<?php

return [
    'up' => function(PDO $db) {
        // 1. Add CHECK constraint on referral_signups to disallow self-referral
        try {
            $db->exec("ALTER TABLE referral_signups ADD CONSTRAINT chk_refsignups_no_self_referral CHECK (partner_id <> referred_user_id)");
        } catch (\Throwable $e) {
            // Constraint may already exist or table not ready
        }

        // 2. Add CHECK constraint on referral_commissions to disallow self-referral
        try {
            $db->exec("ALTER TABLE referral_commissions ADD CONSTRAINT chk_refcomm_no_self_referral CHECK (partner_id <> referred_user_id)");
        } catch (\Throwable $e) {
            // Constraint may already exist or table not ready
        }
    },
    'down' => function(PDO $db) {
        try {
            $db->exec("ALTER TABLE referral_signups DROP CHECK chk_refsignups_no_self_referral");
        } catch (\Throwable $e) {}

        try {
            $db->exec("ALTER TABLE referral_commissions DROP CHECK chk_refcomm_no_self_referral");
        } catch (\Throwable $e) {}
    }
];
