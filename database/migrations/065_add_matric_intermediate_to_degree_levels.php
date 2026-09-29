<?php
/**
 * Migration: 065_add_matric_intermediate_to_degree_levels
 * Adds Matric and Intermediate degree levels and normalizes sort orders.
 */

return [
    'up' => function(PDO $db) {
        // 1. Insert 'Matric' and 'Intermediate' if not already present
        $stmtCheck = $db->prepare("SELECT id FROM degree_levels WHERE name = :name LIMIT 1");
        $stmtInsert = $db->prepare("INSERT INTO degree_levels (name, sort_order, status, created_at) VALUES (:name, :sort, 'active', NOW())");

        $levels = [
            ['name' => 'Matric', 'sort' => 1],
            ['name' => 'Intermediate', 'sort' => 2],
            ['name' => 'Associate Degree', 'sort' => 3],
            ['name' => "Bachelor's", 'sort' => 4],
            ['name' => "Master's", 'sort' => 5],
            ['name' => 'PhD', 'sort' => 6],
            ['name' => 'Post-Doctoral', 'sort' => 7]
        ];

        foreach ($levels as $lvl) {
            $stmtCheck->execute(['name' => $lvl['name']]);
            $existingId = $stmtCheck->fetchColumn();
            if ($existingId) {
                // Update sort order and ensure active status
                $stmtUpdate = $db->prepare("UPDATE degree_levels SET sort_order = :sort, status = 'active' WHERE id = :id");
                $stmtUpdate->execute(['sort' => $lvl['sort'], 'id' => $existingId]);
            } else {
                $stmtInsert->execute(['name' => $lvl['name'], 'sort' => $lvl['sort']]);
            }
        }
    },
    'down' => function(PDO $db) {
        $db->exec("DELETE FROM degree_levels WHERE name IN ('Matric', 'Intermediate')");
    }
];
