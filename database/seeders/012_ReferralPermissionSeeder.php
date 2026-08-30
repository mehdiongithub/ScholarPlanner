<?php
return function(PDO $db) {
    $permissions = [
        ['name' => 'referrals.view', 'description' => 'View referrals logs and details.'],
        ['name' => 'referrals.manage', 'description' => 'Manage referral partners, discount percentages, and settings.']
    ];
    
    $insPerm = $db->prepare("INSERT INTO permissions (name, description) VALUES (:name, :description) ON DUPLICATE KEY UPDATE description = VALUES(description)");
    foreach ($permissions as $p) {
        $insPerm->execute($p);
    }
    
    // Map referrals.manage to the admin role
    $adminId = $db->query("SELECT id FROM roles WHERE name = 'admin'")->fetchColumn();
    $permId = $db->query("SELECT id FROM permissions WHERE name = 'referrals.manage'")->fetchColumn();
    
    if ($adminId && $permId) {
        $insMap = $db->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)");
        $insMap->execute(['role_id' => $adminId, 'permission_id' => $permId]);
    }
};
