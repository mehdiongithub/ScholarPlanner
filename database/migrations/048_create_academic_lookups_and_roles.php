<?php
return [
    'up' => function(PDO $db) {
        // 1. Create degree_levels table
        $db->exec("CREATE TABLE IF NOT EXISTS degree_levels (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL UNIQUE,
            sort_order INT DEFAULT 0,
            status VARCHAR(20) DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        // 2. Create funding_types table
        $db->exec("CREATE TABLE IF NOT EXISTS funding_types (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL UNIQUE,
            status VARCHAR(20) DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        // 3. Seed initial lookup lists
        $stmtDeg = $db->prepare("INSERT IGNORE INTO degree_levels (name, sort_order) VALUES (:name, :sort)");
        $degrees = [
            ['name' => "Associate Degree", 'sort' => 1],
            ['name' => "Bachelor's", 'sort' => 2],
            ['name' => "Master's", 'sort' => 3],
            ['name' => "PhD", 'sort' => 4],
            ['name' => "Post-Doctoral", 'sort' => 5]
        ];
        foreach ($degrees as $d) {
            $stmtDeg->execute($d);
        }

        $stmtFund = $db->prepare("INSERT IGNORE INTO funding_types (name) VALUES (:name)");
        $fundings = [
            ['name' => "Fully Funded"],
            ['name' => "Partial Funding"],
            ['name' => "Tuition Only"],
            ['name' => "Stipend Only"]
        ];
        foreach ($fundings as $f) {
            $stmtFund->execute($f);
        }

        // 4. Seed granular roles
        $stmtRole = $db->prepare("INSERT IGNORE INTO roles (name, description) VALUES (:name, :desc)");
        $roles = [
            ['name' => 'super_admin', 'desc' => 'Complete administrative access and settings manager.'],
            ['name' => 'scholarship_manager', 'desc' => 'Manage scholarship records, degree mappings, and matching status.'],
            ['name' => 'content_manager', 'desc' => 'Manage countries, cities, study fields, FAQs, terms, and homepage blocks.'],
            ['name' => 'reviewer', 'desc' => 'Verify and approve scholarship submissions.'],
            ['name' => 'support_staff', 'desc' => 'Review applicant documents and track application states.']
        ];
        foreach ($roles as $r) {
            $stmtRole->execute($r);
        }

        // 5. Map permissions for new roles
        $roleMap = [
            'super_admin' => [
                'users.view', 'users.create', 'users.edit', 'users.delete',
                'scholarships.view', 'scholarships.create', 'scholarships.edit', 'scholarships.delete',
                'scholarships.publish', 'scholarships.verify', 'subscriptions.view', 'payments.view',
                'payments.refund', 'notifications.view', 'notifications.send', 'reports.view',
                'settings.view', 'settings.edit', 'employees.manage', 'roles.manage',
                'audit_logs.view', 'documents.view', 'documents.review', 'documents.download',
                'applications.view', 'applications.review'
            ],
            'scholarship_manager' => [
                'scholarships.view', 'scholarships.create', 'scholarships.edit', 'scholarships.delete',
                'scholarships.publish', 'scholarships.verify', 'documents.view', 'documents.review',
                'documents.download'
            ],
            'content_manager' => [
                'scholarships.view', 'settings.view', 'settings.edit', 'documents.view'
            ],
            'reviewer' => [
                'scholarships.view', 'scholarships.verify', 'documents.view', 'documents.review',
                'documents.download'
            ],
            'support_staff' => [
                'users.view', 'documents.view', 'documents.review', 'documents.download',
                'applications.view', 'applications.review'
            ]
        ];

        $insMap = $db->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)");
        foreach ($roleMap as $roleName => $perms) {
            $roleId = $db->query("SELECT id FROM roles WHERE name = '{$roleName}'")->fetchColumn();
            if ($roleId) {
                foreach ($perms as $permName) {
                    $pId = $db->query("SELECT id FROM permissions WHERE name = '{$permName}'")->fetchColumn();
                    if ($pId) {
                        $insMap->execute(['role_id' => $roleId, 'permission_id' => $pId]);
                    }
                }
            }
        }

        // 6. Seed website configuration settings
        $stmtSetting = $db->prepare("INSERT IGNORE INTO settings (`key`, `value`, `type`, `group_name`, `is_public`) VALUES (:key, :value, :type, :group, :pub)");
        $settings = [
            ['key' => 'site_name', 'value' => 'ScholarMatch', 'type' => 'string', 'group' => 'general', 'pub' => 1],
            ['key' => 'site_tagline', 'value' => 'Unlock Your Academic Future', 'type' => 'string', 'group' => 'general', 'pub' => 1],
            ['key' => 'site_description', 'value' => 'Match with fully-funded international scholarships.', 'type' => 'string', 'group' => 'general', 'pub' => 1],
            ['key' => 'contact_email', 'value' => 'support@scholarmatch.com', 'type' => 'string', 'group' => 'general', 'pub' => 1],
            ['key' => 'support_email', 'value' => 'support@scholarmatch.com', 'type' => 'string', 'group' => 'general', 'pub' => 1],
            ['key' => 'timezone', 'value' => 'Asia/Karachi', 'type' => 'string', 'group' => 'general', 'pub' => 1],
            ['key' => 'maintenance_mode', 'value' => '0', 'type' => 'boolean', 'group' => 'general', 'pub' => 1],
            ['key' => 'hero_title', 'value' => 'Unlock Your Academic Future', 'type' => 'string', 'group' => 'homepage', 'pub' => 1],
            ['key' => 'hero_subtitle', 'value' => 'ScholarMatch matches your profile with thousands of fully-funded scholarships worldwide.', 'type' => 'string', 'group' => 'homepage', 'pub' => 1],
            ['key' => 'cta_text', 'value' => 'Get Started Now', 'type' => 'string', 'group' => 'homepage', 'pub' => 1],
            ['key' => 'terms_conditions', 'value' => 'Default terms & conditions: Use of our services and compliance rules.', 'type' => 'text', 'group' => 'legal', 'pub' => 1],
            ['key' => 'privacy_policy', 'value' => 'Default privacy policy: How we securely collect, use, and process your information.', 'type' => 'text', 'group' => 'legal', 'pub' => 1],
            ['key' => 'seo_meta_title', 'value' => 'ScholarMatch — Scholarship Matching Platform', 'type' => 'string', 'group' => 'seo', 'pub' => 1],
            ['key' => 'seo_meta_description', 'value' => 'Find fully-funded undergraduate and postgraduate scholarships worldwide.', 'type' => 'string', 'group' => 'seo', 'pub' => 1],
            ['key' => 'seo_meta_keywords', 'value' => 'scholarships, matching, education, funding', 'type' => 'string', 'group' => 'seo', 'pub' => 1],
            ['key' => 'smtp_host', 'value' => 'smtp.mailtrap.io', 'type' => 'string', 'group' => 'mail', 'pub' => 0],
            ['key' => 'smtp_port', 'value' => '2525', 'type' => 'string', 'group' => 'mail', 'pub' => 0],
            ['key' => 'smtp_username', 'value' => 'sandbox-smtp-user', 'type' => 'string', 'group' => 'mail', 'pub' => 0],
            ['key' => 'smtp_password', 'value' => 'sandbox-smtp-password', 'type' => 'string', 'group' => 'mail', 'pub' => 0],
            ['key' => 'smtp_encryption', 'value' => 'tls', 'type' => 'string', 'group' => 'mail', 'pub' => 0],
            ['key' => 'payment_sandbox_mode', 'value' => '1', 'type' => 'boolean', 'group' => 'payment', 'pub' => 0],
            ['key' => 'payment_merchant_id', 'value' => 'scholarmatch_merchant', 'type' => 'string', 'group' => 'payment', 'pub' => 0],
            ['key' => 'payment_secret_key', 'value' => 'scholarmatch_secret_key', 'type' => 'string', 'group' => 'payment', 'pub' => 0]
        ];
        foreach ($settings as $s) {
            $stmtSetting->execute($s);
        }
    },

    'down' => function(PDO $db) {
        // Drop lookup tables
        $db->exec("DROP TABLE IF EXISTS funding_types;");
        $db->exec("DROP TABLE IF EXISTS degree_levels;");

        // Clean custom settings
        $db->exec("DELETE FROM settings WHERE group_name IN ('general', 'homepage', 'legal', 'seo', 'mail', 'payment');");

        // Clean new roles
        $db->exec("DELETE FROM roles WHERE name IN ('super_admin', 'scholarship_manager', 'content_manager', 'reviewer', 'support_staff');");
    }
];
