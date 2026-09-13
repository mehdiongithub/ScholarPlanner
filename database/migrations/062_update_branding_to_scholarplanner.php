<?php

return [
    'up' => function(PDO $db) {
        $updates = [
            'site_name' => 'ScholarPlanner',
            'contact_email' => 'support@scholarplanner.com',
            'support_email' => 'support@scholarplanner.com',
            'hero_subtitle' => 'ScholarPlanner matches your profile with thousands of fully-funded scholarships worldwide.',
            'seo_meta_title' => 'ScholarPlanner — Scholarship Matching Platform',
            'seo_meta_description' => 'Find fully-funded undergraduate and postgraduate scholarships worldwide.'
        ];

        $stmt = $db->prepare("UPDATE settings SET `value` = :val WHERE `key` = :key");
        foreach ($updates as $key => $val) {
            $stmt->execute(['val' => $val, 'key' => $key]);
        }
    },
    'down' => function(PDO $db) {
        $reverts = [
            'site_name' => 'ScholarMatch',
            'contact_email' => 'support@scholarmatch.com',
            'support_email' => 'support@scholarmatch.com',
            'hero_subtitle' => 'ScholarMatch matches your profile with thousands of fully-funded scholarships worldwide.',
            'seo_meta_title' => 'ScholarMatch — Scholarship Matching Platform',
            'seo_meta_description' => 'Find fully-funded undergraduate and postgraduate scholarships worldwide.'
        ];

        $stmt = $db->prepare("UPDATE settings SET `value` = :val WHERE `key` = :key");
        foreach ($reverts as $key => $val) {
            $stmt->execute(['val' => $val, 'key' => $key]);
        }
    }
];
