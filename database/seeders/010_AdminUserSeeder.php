<?php
return function(PDO $db) {
    // 1. Fetch admin role
    $adminRoleId = $db->query("SELECT id FROM roles WHERE name = 'admin'")->fetchColumn();
    if (!$adminRoleId) return;
    
    // 2. Fetch credentials from env or generate secure random credentials
    $email = $_ENV['ADMIN_EMAIL'] ?? 'admin@scholarmatch.com';
    $password = $_ENV['ADMIN_PASSWORD'] ?? null;
    
    if (empty($password)) {
        // Generate random secure password
        $password = bin2hex(random_bytes(6));
        echo "\n[SECURITY WARNING] ADMIN_PASSWORD not found in env. Generated random admin password: {$password}\n";
    }
    
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
    
    // Check if email already exists
    $exists = $db->prepare("SELECT id FROM users WHERE email = :email");
    $exists->execute(['email' => $email]);
    $userId = $exists->fetchColumn();
    
    if ($userId) {
        // Update password
        $upd = $db->prepare("UPDATE users SET password_hash = :hash, role_id = :role WHERE id = :id");
        $upd->execute(['hash' => $passwordHash, 'role' => $adminRoleId, 'id' => $userId]);
        echo "✔ Admin user updated successfully.\n";
    } else {
        // Create user
        $ins = $db->prepare("INSERT INTO users (role_id, first_name, last_name, email, password_hash, status, email_verified_at) 
                             VALUES (:role_id, 'System', 'Administrator', :email, :hash, 'active', NOW())");
        $ins->execute([
            'role_id' => $adminRoleId,
            'email' => $email,
            'hash' => $passwordHash
        ]);
        echo "✔ Admin user created successfully.\n";
    }
};