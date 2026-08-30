<?php
return function(PDO $db) {
    $permissions = [
        ['name' => 'users.view', 'description' => 'View users details.'],
        ['name' => 'users.create', 'description' => 'Create new user accounts.'],
        ['name' => 'users.edit', 'description' => 'Modify user profile settings.'],
        ['name' => 'users.delete', 'description' => 'Remove user accounts from database.'],
        ['name' => 'scholarships.view', 'description' => 'View scholarships directory.'],
        ['name' => 'scholarships.create', 'description' => 'Create new scholarships.'],
        ['name' => 'scholarships.edit', 'description' => 'Modify scholarship details.'],
        ['name' => 'scholarships.delete', 'description' => 'Delete scholarship listings.'],
        ['name' => 'scholarships.publish', 'description' => 'Publish draft scholarships.'],
        ['name' => 'scholarships.verify', 'description' => 'Verify status of scholarships.'],
        ['name' => 'subscriptions.view', 'description' => 'View student subscriptions.'],
        ['name' => 'payments.view', 'description' => 'View payment histories.'],
        ['name' => 'payments.refund', 'description' => 'Issue payment refunds.'],
        ['name' => 'notifications.view', 'description' => 'View notification log files.'],
        ['name' => 'notifications.send', 'description' => 'Dispatch custom alerts.'],
        ['name' => 'reports.view', 'description' => 'View system performance reports.'],
        ['name' => 'settings.view', 'description' => 'View app configurations.'],
        ['name' => 'settings.edit', 'description' => 'Modify app settings.'],
        ['name' => 'employees.manage', 'description' => 'Manage employee profiles and roles.'],
        ['name' => 'roles.manage', 'description' => 'Manage permissions mappings.'],
        ['name' => 'audit_logs.view', 'description' => 'View administrative audit logs.'],
        ['name' => 'documents.view', 'description' => 'View applicant uploaded documents.'],
        ['name' => 'documents.review', 'description' => 'Approve or reject applicant documents.'],
        ['name' => 'documents.download', 'description' => 'Securely download applicant documents.'],
        ['name' => 'applications.view', 'description' => 'View applicant scholarship applications.'],
        ['name' => 'applications.review', 'description' => 'Review and transition scholarship application statuses.']
    ];
    
    // Insert permissions
    $insPerm = $db->prepare("INSERT INTO permissions (name, description) VALUES (:name, :description) ON DUPLICATE KEY UPDATE description = VALUES(description)");
    foreach ($permissions as $p) {
        $insPerm->execute($p);
    }
    
    // Link admin role to all permissions
    $adminId = $db->query("SELECT id FROM roles WHERE name = 'admin'")->fetchColumn();
    $employeeId = $db->query("SELECT id FROM roles WHERE name = 'employee'")->fetchColumn();
    
    $allPermIds = $db->query("SELECT id FROM permissions")->fetchAll(PDO::FETCH_COLUMN);
    
    // Clear old mappings first
    $db->exec("DELETE FROM role_permissions");
    
    // Map Admin
    $insMap = $db->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)");
    foreach ($allPermIds as $permId) {
        $insMap->execute(['role_id' => $adminId, 'permission_id' => $permId]);
    }
    
    // Map Employee (only view and verify permissions)
    $employeePerms = [
        'users.view', 'scholarships.view', 'scholarships.create', 
        'scholarships.edit', 'scholarships.verify', 'notifications.view',
        'documents.view', 'documents.review', 'documents.download',
        'applications.view', 'applications.review'
    ];
    foreach ($employeePerms as $permName) {
        $pId = $db->query("SELECT id FROM permissions WHERE name = '{$permName}'")->fetchColumn();
        if ($pId) {
            $insMap->execute(['role_id' => $employeeId, 'permission_id' => $pId]);
        }
    }
};
