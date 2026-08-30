<?php
return function(PDO $db) {
    $roles = [
        ['name' => 'admin', 'description' => 'System Administrator with full access rights.'],
        ['name' => 'employee', 'description' => 'Staff member with delegated scholarship verification access.'],
        ['name' => 'visitor', 'description' => 'Student/Customer account discovering scholarships.']
    ];
    
    $stmt = $db->prepare("INSERT INTO roles (name, description) VALUES (:name, :description) ON DUPLICATE KEY UPDATE description = VALUES(description)");
    foreach ($roles as $role) {
        $stmt->execute($role);
    }
};
