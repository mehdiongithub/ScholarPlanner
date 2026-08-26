<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Applicant Documents | Admin</title>
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
        .filter-card {
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            padding: 20px 24px;
            margin-bottom: 24px;
            box-shadow: var(--shadow-sm);
        }
        .filter-form {
            display: flex;
            align-items: flex-end;
            flex-wrap: wrap;
            gap: 16px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            min-width: 180px;
            flex-grow: 1;
        }
        .form-group label {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-600);
            text-transform: uppercase;
        }
        .form-group select, .form-group input {
            padding: 8px 12px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
            font-size: 0.875rem;
            background: white;
            color: var(--text-800);
        }
        .btn-filter-submit {
            background: var(--primary);
            color: white;
            border: none;
            padding: 9px 20px;
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-filter-submit:hover {
            background: var(--primary-hover);
        }
        .btn-filter-reset {
            background: #f1f5f9;
            color: var(--text-700);
            border: 1px solid var(--border);
            padding: 8px 16px;
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            text-align: center;
        }
        .btn-filter-reset:hover {
            background: #e2e8f0;
        }
        .records-card {
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius-2xl);
            padding: clamp(20px, 4vw, 32px);
            box-shadow: var(--shadow-sm);
        }
        .records-table {
            width: 100%;
            border-collapse: collapse;
        }
        .records-table th {
            text-align: left;
            padding: 12px 16px;
            border-bottom: 2px solid var(--border);
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--text-500);
            text-transform: uppercase;
        }
        .records-table td {
            padding: 16px;
            border-bottom: 1px solid var(--border);
            font-size: 0.875rem;
            color: var(--text-700);
            vertical-align: middle;
        }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: var(--radius-full);
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: capitalize;
        }
        .badge-uploaded { background: #e0f2fe; color: #0369a1; }
        .badge-approved { background: #d1fae5; color: #065f46; }
        .badge-rejected { background: #fef2f2; color: #991b1b; }
        
        .actions-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .btn-action-view {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 6px 12px;
            border-radius: var(--radius-md);
            font-size: 0.8125rem;
            font-weight: 600;
            text-decoration: none;
            background: var(--primary);
            color: white;
        }
        .btn-action-view:hover {
            background: var(--primary-hover);
        }
        .btn-action-dl {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 6px 12px;
            border-radius: var(--radius-md);
            font-size: 0.8125rem;
            font-weight: 500;
            text-decoration: none;
            border: 1px solid var(--border);
            background: white;
            color: var(--text-700);
        }
        .btn-action-dl:hover {
            background: #f1f5f9;
        }
        .pagination-container {
            margin-top: 24px;
            display: flex;
            justify-content: center;
            gap: 8px;
        }
        .page-link {
            padding: 6px 12px;
            border: 1px solid var(--border);
            background: white;
            border-radius: var(--radius-md);
            text-decoration: none;
            font-size: 0.875rem;
            color: var(--text-700);
        }
        .page-link.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        .page-link:hover:not(.active) {
            background: #f1f5f9;
        }
        .alert-success {
            background: #ecfdf5;
            border: 1px solid #d1fae5;
            color: #065f46;
            padding: 12px 16px;
            border-radius: var(--radius-lg);
            margin-bottom: 24px;
            font-size: 0.875rem;
        }
        .alert-error {
            background: #fef2f2;
            border: 1px solid #fee2e2;
            color: #b91c1c;
            padding: 12px 16px;
            border-radius: var(--radius-lg);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            padding: 20px;
            box-shadow: var(--shadow-sm);
        }
        .stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }
        .stat-icon {
            width: 36px;
            height: 36px;
            background: var(--primary-50);
            color: var(--primary);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .stat-icon.success { background: #ecfdf5; color: #059669; }
        .stat-icon.warning { background: #fffbeb; color: #d97706; }
        .stat-icon.danger { background: #fef2f2; color: #dc2626; }
        .stat-label {
            font-size: 0.725rem;
            text-transform: uppercase;
            font-weight: 600;
            color: var(--text-400);
            letter-spacing: 0.05em;
        }
        .stat-value {
            font-size: 1.375rem;
            font-weight: 700;
            color: var(--text-900);
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
                <a href="/admin/documents" class="nav-link active">Documents</a>
            </div>
            
            <div style="font-size: 0.875rem; font-weight: 600; color: var(--text-800);">
                Staff Panel
            </div>
        </header>

        <main class="admin-content">
            <div class="welcome-section">
                <h1 class="welcome-title">Manage Documents</h1>
                <p class="welcome-subtitle">Verify, approve, or reject files uploaded by scholarship applicants</p>
            </div>

            <!-- Statistics Dashboard -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-label">Total Uploaded</span>
                        <div class="stat-icon">
                            <i data-lucide="files"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= e($stats['total_uploaded']) ?></div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-label">Pending Review</span>
                        <div class="stat-icon warning">
                            <i data-lucide="clock"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= e($stats['pending_review']) ?></div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-label">Approved</span>
                        <div class="stat-icon success">
                            <i data-lucide="check-circle"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= e($stats['approved']) ?></div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-label">Rejected</span>
                        <div class="stat-icon danger">
                            <i data-lucide="x-circle"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= e($stats['rejected']) ?></div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-label">Missing Required</span>
                        <div class="stat-icon danger">
                            <i data-lucide="alert-triangle"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= e($stats['missing']) ?></div>
                </div>
            </div>

            <!-- Filters -->
            <div class="filter-card">
                <form action="" method="GET" class="filter-form">
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select name="status" id="status">
                            <option value="">All Statuses</option>
                            <option value="uploaded" <?= $status === 'uploaded' ? 'selected' : '' ?>>Under Review</option>
                            <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Approved</option>
                            <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="document_id">Document Type</label>
                        <select name="document_id" id="document_id">
                            <option value="">All Types</option>
                            <?php foreach ($docTypes as $dt): ?>
                                <option value="<?= e($dt['id']) ?>" <?= $docId == $dt['id'] ? 'selected' : '' ?>><?= e($dt['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="search">Search Applicant/File</label>
                        <input type="text" name="search" id="search" value="<?= e($search ?? '') ?>" placeholder="Name, email, or filename...">
                    </div>

                    <button type="submit" class="btn-filter-submit">Filter</button>
                    <a href="<?= url('/admin/documents') ?>" class="btn-filter-reset">Reset</a>
                </form>
            </div>

            <!-- Records List -->
            <div class="records-card">
                <div style="overflow-x: auto;">
                    <table class="records-table">
                        <thead>
                            <tr>
                                <th>Applicant</th>
                                <th>Document Type</th>
                                <th>Filename</th>
                                <th>Uploaded Date</th>
                                <th>Status</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($records)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-400);">
                                        No uploaded documents found matching your filter criteria.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($records as $rec): 
                                    $badgeClass = 'badge-uploaded';
                                    if ($rec['status'] === 'approved') $badgeClass = 'badge-approved';
                                    elseif ($rec['status'] === 'rejected') $badgeClass = 'badge-rejected';
                                ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight: 600; color: var(--text-800);"><?= e($rec['first_name'] . ' ' . $rec['last_name']) ?></div>
                                            <div style="font-size: 0.75rem; color: var(--text-500);"><?= e($rec['user_email']) ?></div>
                                        </td>
                                        <td>
                                            <div style="font-weight: 500; color: var(--text-700);"><?= e($rec['doc_type_name']) ?></div>
                                        </td>
                                        <td>
                                            <div style="font-size: 0.8125rem; word-break: break-all; max-width: 250px;"><?= e($rec['original_filename']) ?></div>
                                        </td>
                                        <td>
                                            <div style="font-size: 0.8125rem; color: var(--text-600);"><?= date('d M Y H:i', strtotime($rec['uploaded_at'])) ?></div>
                                        </td>
                                        <td>
                                            <span class="badge <?= $badgeClass ?>"><?= $rec['status'] === 'uploaded' ? 'Under Review' : e($rec['status']) ?></span>
                                        </td>
                                        <td style="text-align: right;">
                                            <div class="actions-cell" style="justify-content: flex-end;">
                                                <a href="<?= url('/admin/documents/' . $rec['id']) ?>" class="btn-action-view">
                                                    <i data-lucide="edit-3" style="width: 12px; height: 12px;"></i>
                                                    <span>Review</span>
                                                </a>
                                                <a href="<?= url('/admin/documents/' . $rec['id'] . '/download') ?>" class="btn-action-dl" target="_blank">
                                                    <i data-lucide="download" style="width: 12px; height: 12px;"></i>
                                                    <span>Download</span>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination-container">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?page=<?= $i ?>&status=<?= e($status) ?>&document_id=<?= e($docId) ?>&search=<?= e($search) ?>" class="page-link <?= $page == $i ? 'active' : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
