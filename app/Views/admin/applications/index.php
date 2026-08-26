<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Applicant Trackers | Admin</title>
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
            transition: color 0.2s;
        }
        .nav-link:hover, .nav-link.active {
            color: var(--primary);
        }
        .admin-content {
            max-width: 1200px;
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
        .search-filter-bar {
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            padding: 20px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 32px;
        }
        .filter-form {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }
        .input-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            flex-grow: 1;
            min-width: 240px;
        }
        .input-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-500);
            text-transform: uppercase;
        }
        .input-field {
            padding: 10px 14px;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            width: 100%;
            background: var(--bg-white);
        }
        .btn-filter {
            background: var(--primary);
            color: white;
            border: none;
            padding: 11px 24px;
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            align-self: flex-end;
        }
        .btn-filter:hover {
            background: var(--primary-hover);
        }
        .btn-clear {
            background: #f1f5f9;
            color: var(--text-700);
            border: 1px solid var(--border);
            padding: 10px 20px;
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            align-self: flex-end;
        }
        .btn-clear:hover {
            background: #e2e8f0;
        }

        .data-table-container {
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            margin-bottom: 32px;
        }
        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.875rem;
        }
        .data-table th {
            background: #f8fafc;
            padding: 16px 24px;
            font-weight: 600;
            color: var(--text-500);
            border-bottom: 1px solid var(--border);
            text-transform: uppercase;
            font-size: 0.75rem;
        }
        .data-table td {
            padding: 16px 24px;
            border-bottom: 1px solid var(--border);
            color: var(--text-700);
        }
        .data-table tr:last-child td {
            border-bottom: none;
        }

        .badge-status {
            padding: 6px 12px;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            display: inline-block;
            text-align: center;
        }
        .status-interested { background: #eff6ff; color: #1d4ed8; }
        .status-planning { background: #fef3c7; color: #d97706; }
        .status-documents_pending { background: #fff7ed; color: #ea580c; }
        .status-ready_to_apply { background: #ecfdf5; color: #059669; }
        .status-applied { background: #f5f3ff; color: #7c3aed; }
        .status-interview { background: #f0fdfa; color: #0d9488; }
        .status-accepted { background: #f0fdf4; color: #16a34a; }
        .status-rejected { background: #fef2f2; color: #dc2626; }
        .status-withdrawn { background: #f1f5f9; color: #475569; }

        .btn-action {
            background: var(--primary);
            color: white;
            padding: 6px 14px;
            border-radius: var(--radius-md);
            text-decoration: none;
            font-size: 0.8125rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-action:hover {
            background: var(--primary-hover);
        }

        .pagination {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .page-link {
            padding: 8px 16px;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            text-decoration: none;
            color: var(--text-700);
            font-size: 0.875rem;
            background: white;
            font-weight: 500;
        }
        .page-link:hover, .page-link.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        .alert-success {
            background: #f0fff4;
            border: 1px solid #c6f6d5;
            padding: 16px;
            border-radius: var(--radius-lg);
            margin-bottom: 24px;
            color: #22543d;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <header class="admin-header" role="banner">
            <a href="/" class="logo-box">
                <i data-lucide="graduation-cap"></i>
                <span>ScholarMatch Admin</span>
            </a>
            
            <div class="nav-links">
                <a href="/admin/scholarships" class="nav-link">Scholarships</a>
                <a href="/admin/notifications" class="nav-link">Notifications</a>
                <a href="/admin/documents" class="nav-link">Documents</a>
                <a href="/admin/applications" class="nav-link active">Applications</a>
            </div>
            
            <div style="font-size: 0.875rem; font-weight: 600; color: var(--text-800);">
                Staff Panel
            </div>
        </header>

        <main class="admin-content">
            <div class="welcome-section">
                <h1 class="welcome-title">Manage Applications</h1>
                <p class="welcome-subtitle">Review, evaluate, and transition tracking statuses for scholarship applications</p>
            </div>

            <!-- Success message -->
            <?php if (isset($_SESSION['admin_app_success'])): ?>
                <div class="alert-success" role="alert">
                    <?= e($_SESSION['admin_app_success']) ?>
                    <?php unset($_SESSION['admin_app_success']); ?>
                </div>
            <?php endif; ?>

            <!-- Filters -->
            <div class="search-filter-bar">
                <form class="filter-form" method="GET" action="/admin/applications">
                    <div class="input-group">
                        <label class="input-label" for="search">Search Applicants / Scholarships</label>
                        <input type="text" id="search" name="search" class="input-field" placeholder="Search by name, email or title..." value="<?= e($search) ?>">
                    </div>

                    <div class="input-group">
                        <label class="input-label" for="status">Status</label>
                        <select id="status" name="status" class="input-field">
                            <option value="">All Statuses</option>
                            <option value="interested" <?= $status === 'interested' ? 'selected' : '' ?>>Interested</option>
                            <option value="planning" <?= $status === 'planning' ? 'selected' : '' ?>>Planning</option>
                            <option value="documents_pending" <?= $status === 'documents_pending' ? 'selected' : '' ?>>Documents Pending</option>
                            <option value="ready_to_apply" <?= $status === 'ready_to_apply' ? 'selected' : '' ?>>Ready to Apply</option>
                            <option value="applied" <?= $status === 'applied' ? 'selected' : '' ?>>Applied</option>
                            <option value="interview" <?= $status === 'interview' ? 'selected' : '' ?>>Interview</option>
                            <option value="accepted" <?= $status === 'accepted' ? 'selected' : '' ?>>Accepted</option>
                            <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                            <option value="withdrawn" <?= $status === 'withdrawn' ? 'selected' : '' ?>>Withdrawn</option>
                        </select>
                    </div>

                    <button type="submit" class="btn-filter">
                        <i data-lucide="filter" style="width: 16px; height: 16px;"></i>
                        <span>Apply Filters</span>
                    </button>
                    <?php if ($search !== '' || $status !== ''): ?>
                        <a href="/admin/applications" class="btn-clear">
                            <i data-lucide="x" style="width: 16px; height: 16px;"></i>
                            <span>Clear</span>
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Table -->
            <div class="data-table-container">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Applicant</th>
                                <th>Scholarship Opportunity</th>
                                <th>Tracking Status</th>
                                <th>Date Tracked</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($applications)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; color: var(--text-400); padding: 40px;">
                                        No tracked applications found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($applications as $app): ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight: 600; color: var(--text-900);"><?= e($app['first_name']) ?> <?= e($app['last_name']) ?></div>
                                            <div style="font-size: 0.75rem; color: var(--text-500);"><?= e($app['user_email']) ?></div>
                                        </td>
                                        <td>
                                            <div style="font-weight: 500; color: var(--text-800);"><?= e($app['scholarship_title']) ?></div>
                                            <div style="font-size: 0.75rem; color: var(--text-500);">Deadline: <?= $app['application_deadline'] ? e($app['application_deadline']) : 'Rolling' ?></div>
                                        </td>
                                        <td>
                                            <span class="badge-status status-<?= e($app['status']) ?>"><?= str_replace('_', ' ', e($app['status'])) ?></span>
                                        </td>
                                        <td><?= e($app['created_at']) ?></td>
                                        <td>
                                            <a href="/admin/applications/<?= e($app['id']) ?>" class="btn-action">
                                                <i data-lucide="check-square" style="width: 14px; height: 14px;"></i>
                                                <span>Review</span>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav class="pagination" role="navigation" aria-label="Pagination Navigation">
                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <a href="/admin/applications?search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&page=<?= $p ?>" class="page-link <?= $page === $p ? 'active' : '' ?>">
                            <?= $p ?>
                        </a>
                    <?php endfor; ?>
                </nav>
            <?php endif; ?>
        </main>
    </div>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
