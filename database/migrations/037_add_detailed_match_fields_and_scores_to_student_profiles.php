<?php

return [
    'up' => function(PDO $db) {
        // 1. Add score columns to student_profiles table
        $db->exec("ALTER TABLE student_profiles 
            ADD COLUMN ielts_score DECIMAL(3,1) DEFAULT NULL AFTER city_id,
            ADD COLUMN toefl_score INT DEFAULT NULL AFTER ielts_score,
            ADD COLUMN pte_score INT DEFAULT NULL AFTER toefl_score,
            ADD COLUMN duolingo_score INT DEFAULT NULL AFTER pte_score
        ");

        // 2. Add extra detailed result columns to scholarship_matches table
        $db->exec("ALTER TABLE scholarship_matches
            ADD COLUMN eligibility_status VARCHAR(30) DEFAULT NULL AFTER match_status,
            ADD COLUMN recommendation_level VARCHAR(30) DEFAULT NULL AFTER eligibility_status,
            ADD COLUMN matched_criteria JSON DEFAULT NULL AFTER recommendation_level,
            ADD COLUMN failed_criteria JSON DEFAULT NULL AFTER matched_criteria,
            ADD COLUMN missing_criteria JSON DEFAULT NULL AFTER failed_criteria,
            ADD COLUMN calculated_at TIMESTAMP DEFAULT NULL AFTER engine_version
        ");
    },

    'down' => function(PDO $db) {
        $db->exec("ALTER TABLE scholarship_matches
            DROP COLUMN calculated_at,
            DROP COLUMN missing_criteria,
            DROP COLUMN failed_criteria,
            DROP COLUMN matched_criteria,
            DROP COLUMN recommendation_level,
            DROP COLUMN eligibility_status
        ");

        $db->exec("ALTER TABLE student_profiles
            DROP COLUMN duolingo_score,
            DROP COLUMN pte_score,
            DROP COLUMN toefl_score,
            DROP COLUMN ielts_score
        ");
    }
];
