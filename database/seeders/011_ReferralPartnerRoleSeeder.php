<?php
return function(PDO $db) {
    $roles = [
        ['name' => 'referral_partner', 'description' => 'Referral Partner who can refer students and earn commission.']
    ];
    
    $stmt = $db->prepare("INSERT INTO roles (name, description) VALUES (:name, :description) ON DUPLICATE KEY UPDATE description = VALUES(description)");
    foreach ($roles as $role) {
        $stmt->execute($role);
    }
};
