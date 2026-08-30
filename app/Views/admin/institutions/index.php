<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Institutions | ScholarMatch</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">
    <style>
        .admin-layout {
            min-height: 100vh;
            background: #f8fafc;
            display: flex;
            flex-direction: column;
        }
        .admin-header {
            background: var(--bg-white);
            border-bottom: 1px solid var(--border);
            padding: 16px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
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
        .nav-links {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .nav-link {
            font-size: 0.875rem;
            color: var(--text-600);
            text-decoration: none;
            font-weight: 500;
        }
        .nav-link:hover {
            color: var(--primary);
        }
        .admin-content {
            max-width: 1200px;
            width: 100%;
            margin: 40px auto;
            padding: 0 20px;
            flex-grow: 1;
        }
        .header-section {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }
        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-900);
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            font-size: 0.875rem;
            font-weight: 600;
            border-radius: var(--radius-lg);
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }
        .btn-primary {
            background: var(--primary);
            color: var(--bg-white);
        }
        .btn-primary:hover {
            background: var(--primary-dark, #1d4ed8);
        }
        .btn-secondary {
            background: var(--bg-white);
            color: var(--text-700);
            border: 1px solid var(--border);
        }
        .btn-secondary:hover {
            background: #f1f5f9;
        }
        .btn-danger {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fee2e2;
        }
        .btn-danger:hover {
            background: #fca5a5;
        }
        .alert {
            padding: 12px 16px;
            border-radius: var(--radius-lg);
            font-size: 0.875rem;
            margin-bottom: 20px;
            line-height: 1.5;
            border: 1px solid transparent;
        }
        .alert-danger {
            background: #fef2f2;
            border-color: #fee2e2;
            color: #b91c1c;
        }
        .alert-success {
            background: #ecfdf5;
            border-color: #d1fae5;
            color: #065f46;
        }
        .table-card {
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius-2xl);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }
        .table-responsive {
            overflow-x: auto;
            width: 100%;
        }
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.875rem;
        }
        .admin-table th {
            background: #f8fafc;
            padding: 14px 20px;
            font-weight: 600;
            color: var(--text-500);
            border-bottom: 1px solid var(--border);
        }
        .admin-table td {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            color: var(--text-700);
            vertical-align: middle;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: var(--radius-full);
            font-size: 0.75rem;
            font-weight: 600;
        }
        .status-approved {
            background: #ecfdf5;
            color: #047857;
        }
        .status-pending {
            background: #fffbeb;
            color: #b45309;
        }
        .status-inactive {
            background: #f1f5f9;
            color: #475569;
        }
        .status-rejected {
            background: #fef2f2;
            color: #b91c1c;
        }
        .action-btns {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .action-link {
            text-decoration: none;
            color: var(--primary);
            font-weight: 600;
        }
        .action-link:hover {
            text-decoration: underline;
        }
        @media (max-width: 280px) {
            .admin-header {
                padding: 10px;
            }
            .header-section {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>

    <div class="admin-layout">
        <header class="admin-header" role="banner">
            <a href="/admin" class="logo-box">
                <i data-lucide="graduation-cap"></i>
                <span>ScholarMatch Admin</span>
            </a>
            
            <nav class="nav-links" role="navigation">
                <a href="/admin" class="nav-link">Dashboard</a>
                <a href="/admin/scholarships" class="nav-link">Scholarships</a>
                <a href="/admin/institutions" class="nav-link" style="color: var(--primary); font-weight: 600;">Institutions</a>
                <a href="/admin/notifications" class="nav-link">Notifications</a>
            </nav>
        </header>

        <main class="admin-content">
            <div class="header-section">
                <div>
                    <h1 class="page-title">Manage Institutions</h1>
                </div>
                <a href="/admin/institutions/create" class="btn btn-primary">
                    <i data-lucide="plus" style="width: 16px; height: 16px;"></i>
                    <span>Add Institution</span>
                </a>
            </div>

            <?php if (isset($_SESSION['admin_success'])): ?>
                <div class="alert alert-success">
                    <?= e($_SESSION['admin_success']) ?>
                    <?php unset($_SESSION['admin_success']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['admin_errors'])): ?>
                <div class="alert alert-danger">
                    <?php foreach ($_SESSION['admin_errors'] as $err): ?>
                        <p><?= e($err) ?></p>
                    <?php endforeach; ?>
                    <?php unset($_SESSION['admin_errors']); ?>
                </div>
            <?php endif; ?>

            <div class="table-card">
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Country</th>
                                <th>Coverage</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($institutions)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; color: var(--text-400); padding: 40px;">
                                        No institutions found. Click "Add Institution" to create one.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($institutions as $inst): ?>
                                    <tr>
                                        <td><?= e($inst['id']) ?></td>
                                        <td style="font-weight: 600; color: var(--text-900);"><?= e($inst['name']) ?></td>
                                        <td style="text-transform: capitalize;"><?= e($inst['institution_type']) ?></td>
                                        <td><?= e($inst['country_name'] ?? 'N/A') ?></td>
                                        <td style="text-transform: capitalize; font-size: 0.8125rem;">
                                            <?php 
                                            if ($inst['coverage_type'] === 'state') {
                                                echo 'State Specific (' . e($inst['state_name'] ?? 'None') . ')';
                                            } elseif ($inst['coverage_type'] === 'multi_state') {
                                                echo 'Multiple States';
                                            } else {
                                                echo 'National';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?= e($inst['status']) ?>">
                                                <?= e($inst['status']) ?>
                                            </span>
                                        </td>
                                        <td class="action-btns">
                                            <a href="/admin/institutions/<?= e($inst['id']) ?>/edit" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.75rem;">
                                                Edit
                                            </a>
                                            <form action="/admin/institutions/<?= e($inst['id']) ?>/delete" method="POST" onsubmit="return confirm('Are you sure you want to delete this institution?');" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                <button type="submit" class="btn btn-danger" style="padding: 6px 12px; font-size: 0.75rem;">
                                                    Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
