<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | ScholarMatch</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">
    <style>
        .dashboard-layout {
            min-height: 100vh;
            background: #f8fafc;
            display: flex;
            flex-direction: column;
        }
        .dashboard-header {
            background: var(--bg-white);
            border-bottom: 1px solid var(--border);
            padding: 16px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .logo-box {
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            color: var(--text-900);
            font-weight: 700;
        }
        .logo-box i {
            color: var(--primary);
        }
        .user-menu {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .user-name {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-800);
        }
        .btn-logout {
            background: none;
            border: 1px solid var(--border);
            padding: 6px 12px;
            border-radius: var(--radius-md);
            font-size: 0.8125rem;
            font-weight: 500;
            color: var(--text-600);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }
        .btn-logout:hover {
            background: #f1f5f9;
            color: #b91c1c;
            border-color: #fee2e2;
        }
        .dashboard-content {
            max-width: 1100px;
            width: 100%;
            margin: 40px auto;
            padding: 0 20px;
            flex-grow: 1;
        }
        .welcome-section {
            margin-bottom: 32px;
        }
        .welcome-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-900);
            margin-bottom: 6px;
        }
        .welcome-subtitle {
            color: var(--text-500);
            font-size: 0.9375rem;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }
        .stat-card {
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            padding: 24px;
            box-shadow: var(--shadow-sm);
        }
        .stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }
        .stat-icon {
            width: 44px;
            height: 44px;
            background: var(--primary-50);
            color: var(--primary);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .stat-icon.danger {
            background: #fef2f2;
            color: #ef4444;
        }
        .stat-icon.warning {
            background: #fffbeb;
            color: #d97706;
        }
        .stat-icon.success {
            background: #ecfdf5;
            color: #059669;
        }
        .stat-label {
            font-size: 0.8125rem;
            text-transform: uppercase;
            font-weight: 600;
            color: var(--text-400);
            letter-spacing: 0.05em;
        }
        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-900);
        }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: var(--radius-full);
            font-size: 0.75rem;
            font-weight: 600;
            background: #fee2e2;
            color: #991b1b;
        }
        .info-section {
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius-2xl);
            padding: clamp(24px, 5vw, 40px);
            box-shadow: var(--shadow-sm);
        }
        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-900);
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .section-title i {
            color: var(--primary);
        }
        .placeholder-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .placeholder-item {
            border: 1px dashed var(--border);
            border-radius: var(--radius-lg);
            padding: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            opacity: 0.7;
        }
        .placeholder-item-title {
            font-weight: 600;
            font-size: 0.9375rem;
            color: var(--text-800);
        }
        .placeholder-item-desc {
            font-size: 0.8125rem;
            color: var(--text-500);
            margin-top: 4px;
        }
        @media (max-width: 280px) {
            .dashboard-header {
                padding: 10px;
            }
            .user-name {
                display: none;
            }
            .placeholder-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
        }
    </style>
</head>
<body>

    <div class="dashboard-layout">
        <header class="dashboard-header" role="banner">
            <a href="/" class="logo-box">
                <i data-lucide="graduation-cap"></i>
                <span>ScholarMatch Admin</span>
            </a>
            
            <div class="user-menu">
                <span class="user-name">Admin: <?= e($user['first_name']) ?></span>
                <form action="<?= url('/logout') ?>" method="POST" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <button type="submit" class="btn-logout">
                        <i data-lucide="log-out"></i>
                        <span>Log Out</span>
                    </button>
                </form>
            </div>
        </header>

        <main class="dashboard-content">
            <div class="welcome-section">
                <h1 class="welcome-title">Administrative Dashboard</h1>
                <p class="welcome-subtitle">Oversee platform states, system logs, subscription metrics, and staff allocations</p>
            </div>

            <div class="stats-grid">
                <!-- Users count -->
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-label">Total Users</span>
                        <div class="stat-icon">
                            <i data-lucide="users"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= e($stats['users_count']) ?></div>
                </div>

                <!-- Scholarships count -->
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-label">Scholarships</span>
                        <div class="stat-icon success">
                            <i data-lucide="award"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= e($stats['scholarships_count']) ?></div>
                </div>

                <!-- Matches count -->
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-label">Total Matches</span>
                        <div class="stat-icon warning">
                            <i data-lucide="sparkles"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= e($stats['matches_count']) ?></div>
                </div>

                <!-- Logs count -->
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-label">Audit Logs</span>
                        <div class="stat-icon danger">
                            <i data-lucide="activity"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= e($stats['logs_count']) ?></div>
                </div>
            </div>

            <div class="info-section">
                <h2 class="section-title">
                    <i data-lucide="settings"></i>
                    <span>System Settings & Admin Utilities (Coming in Step 9)</span>
                </h2>

                <div class="placeholder-list">
                    <div class="placeholder-item" style="border-style: solid; opacity: 1;">
                        <div>
                            <div class="placeholder-item-title">Institution & University Manager</div>
                            <div class="placeholder-item-desc">Configure, approve, and edit nationwide, regional, or state-specific educational institutions.</div>
                        </div>
                        <a href="<?= url('/admin/institutions') ?>" class="btn-logout" style="border-color: var(--primary); color: var(--primary);">
                            <i data-lucide="graduation-cap" style="width:14px; height:14px;"></i>
                            <span>Manage Institutions</span>
                        </a>
                    </div>

                    <div class="placeholder-item" style="border-style: solid; opacity: 1;">
                        <div>
                            <div class="placeholder-item-title">Notification & Queue Manager</div>
                            <div class="placeholder-item-desc">Monitor enqueued emails/WhatsApp notifications and manually retry failures.</div>
                        </div>
                        <a href="<?= url('/admin/notifications') ?>" class="btn-logout" style="border-color: var(--primary); color: var(--primary);">
                            <i data-lucide="mail" style="width:14px; height:14px;"></i>
                            <span>Manage Queue</span>
                        </a>
                    </div>

                    <div class="placeholder-item" style="border-style: solid; opacity: 1;">
                        <div>
                            <div class="placeholder-item-title">Matching Engine Diagnostics</div>
                            <div class="placeholder-item-desc">Run step-by-step rule dry-runs for matched student profiles.</div>
                        </div>
                        <a href="<?= url('/admin/matches/test') ?>" class="btn-logout" style="border-color: var(--primary); color: var(--primary);">
                            <i data-lucide="sparkles" style="width:14px; height:14px;"></i>
                            <span>Run Diagnostic</span>
                        </a>
                    </div>

                    <div class="placeholder-item">
                        <div>
                            <div class="placeholder-item-title">Staff Permissions Management</div>
                            <div class="placeholder-item-desc">Delegate and update employee assignments mapping.</div>
                        </div>
                        <span class="badge" style="background: #f1f5f9; color: var(--text-600);">Step 9</span>
                    </div>

                    <div class="placeholder-item">
                        <div>
                            <div class="placeholder-item-title">Payment Transaction Audits</div>
                            <div class="placeholder-item-desc">Track and reconcile gateway transactions from Easypaisa/JazzCash.</div>
                        </div>
                        <span class="badge" style="background: #f1f5f9; color: var(--text-600);">Step 9</span>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
