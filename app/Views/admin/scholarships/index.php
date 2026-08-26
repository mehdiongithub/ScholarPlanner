<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Scholarships | ScholarMatch</title>
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
            background: #ef4444;
            color: var(--bg-white);
        }
        .btn-danger:hover {
            background: #dc2626;
        }
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.75rem;
            border-radius: var(--radius-md);
        }
        .alert {
            padding: 16px;
            border-radius: var(--radius-lg);
            margin-bottom: 24px;
            font-size: 0.875rem;
        }
        .alert-success {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .alert-danger {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        .filter-card {
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            padding: 24px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 24px;
        }
        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            align-items: flex-end;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .form-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-600);
            text-transform: uppercase;
        }
        .form-control {
            padding: 10px 14px;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            width: 100%;
        }
        .table-responsive {
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius-2xl);
            box-shadow: var(--shadow-sm);
            overflow-x: auto;
            margin-bottom: 24px;
            -webkit-overflow-scrolling: touch;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.875rem;
        }
        .table th, .table td {
            padding: 16px 24px;
            border-bottom: 1px solid var(--border);
        }
        .table th {
            background: #f8fafc;
            font-weight: 600;
            color: var(--text-600);
            text-transform: uppercase;
            font-size: 0.75rem;
            white-space: nowrap;
        }
        .table tr:last-child td {
            border-bottom: none;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            font-size: 0.75rem;
            font-weight: 500;
            border-radius: var(--radius-full);
            text-transform: capitalize;
        }
        .badge-draft { background: #f1f5f9; color: #475569; }
        .badge-pending { background: #fef3c7; color: #d97706; }
        .badge-published { background: #d1fae5; color: #065f46; }
        .badge-archived { background: #e2e8f0; color: #64748b; }
        .badge-verified { background: #e0f2fe; color: #0369a1; }
        .badge-unverified { background: #ffedd5; color: #c2410c; }
        .actions-cell {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .actions-cell form {
            display: inline;
        }
        .pagination {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
            margin-top: 16px;
        }
        .pagination-links {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .pagination-btn {
            padding: 8px 12px;
            border: 1px solid var(--border);
            background: var(--bg-white);
            color: var(--text-700);
            font-size: 0.875rem;
            text-decoration: none;
            border-radius: var(--radius-md);
        }
        .pagination-btn.active {
            background: var(--primary);
            color: var(--bg-white);
            border-color: var(--primary);
        }
        .pagination-btn.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }
        @media (max-width: 768px) {
            .table th, .table td {
                padding: 12px 16px;
            }
            .filter-form {
                grid-template-columns: 1fr;
            }
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
                <a href="<?= url('/dashboard') ?>" class="nav-link">Dashboard</a>
                <a href="<?= url('/profile') ?>" class="nav-link">Profile</a>
                <form action="<?= url('/logout') ?>" method="POST" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="<?= e($csrf_token ?? '') ?>">
                    <button type="submit" class="nav-link" style="background:none; border:none; cursor:pointer; font-weight:500;">Log Out</button>
                </form>
            </div>
        </header>

        <main class="admin-content">
            <?php if (!empty($_GET['success'])): ?>
                <div class="alert alert-success" role="alert">
                    <?= e($_GET['success']) ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($_GET['error'])): ?>
                <div class="alert alert-danger" role="alert">
                    <?= e($_GET['error']) ?>
                </div>
            <?php endif; ?>

            <div class="header-section">
                <h1 class="page-title">Manage Scholarships</h1>
                <a href="<?= url('/admin/scholarships/create') ?>" class="btn btn-primary">
                    <i data-lucide="plus"></i>
                    <span>Add Scholarship</span>
                </a>
            </div>

            <!-- Filters Section -->
            <div class="filter-card">
                <form method="GET" class="filter-form">
                    <div class="form-group">
                        <label class="form-label" for="search">Search</label>
                        <input type="text" id="search" name="search" class="form-control" placeholder="Title, provider, etc." value="<?= e($search) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="country_id">Country</label>
                        <select id="country_id" name="country_id" class="form-control">
                            <option value="">All Countries</option>
                            <?php foreach ($countries as $c): ?>
                                <option value="<?= e($c['id']) ?>" <?= $countryId === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="funding_type">Funding</label>
                        <select id="funding_type" name="funding_type" class="form-control">
                            <option value="">All Types</option>
                            <option value="Fully Funded" <?= $funding === 'Fully Funded' ? 'selected' : '' ?>>Fully Funded</option>
                            <option value="Partially Funded" <?= $funding === 'Partially Funded' ? 'selected' : '' ?>>Partially Funded</option>
                            <option value="Tuition Waiver" <?= $funding === 'Tuition Waiver' ? 'selected' : '' ?>>Tuition Waiver</option>
                            <option value="Stipend" <?= $funding === 'Stipend' ? 'selected' : '' ?>>Stipend</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="status">Status</label>
                        <select id="status" name="status" class="form-control">
                            <option value="">All Statuses</option>
                            <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
                            <option value="pending_review" <?= $status === 'pending_review' ? 'selected' : '' ?>>Pending Review</option>
                            <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published</option>
                            <option value="archived" <?= $status === 'archived' ? 'selected' : '' ?>>Archived</option>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary" style="width:100%;">
                            <i data-lucide="filter"></i>
                            <span>Filter</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Table Section -->
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Provider</th>
                            <th>Country</th>
                            <th>Funding</th>
                            <th>Status</th>
                            <th>Verification</th>
                            <th>Deadline</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($scholarships)): ?>
                            <tr>
                                <td colspan="8" style="text-align:center; color:var(--text-500); padding:32px;">
                                    No scholarships found matching criteria.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($scholarships as $s): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight:600; color:var(--text-900);"><?= e($s['title']) ?></div>
                                        <div style="font-size:0.75rem; color:var(--text-500); margin-top:2px;">Slug: <?= e($s['slug']) ?></div>
                                    </td>
                                    <td><?= e($s['provider_name']) ?></td>
                                    <td><?= e($s['country_name'] ?? 'Multi-Country') ?></td>
                                    <td><?= e($s['funding_type']) ?></td>
                                    <td>
                                        <span class="badge badge-<?= e($s['status']) ?>"><?= e($s['status']) ?></span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= e($s['verification_status']) ?>"><?= e($s['verification_status']) ?></span>
                                    </td>
                                    <td>
                                        <?= $s['application_deadline'] ? e(date('M d, Y', strtotime($s['application_deadline']))) : '<span style="color:#94a3b8;">None</span>' ?>
                                    </td>
                                    <td>
                                        <div class="actions-cell">
                                            <a href="<?= url('/admin/scholarships/' . $s['id'] . '/edit') ?>" class="btn btn-secondary btn-sm" title="Edit">
                                                <i data-lucide="edit-2" style="width:14px; height:14px;"></i>
                                            </a>
                                            <?php if ($s['status'] !== 'published'): ?>
                                                <form action="<?= url('/admin/scholarships/' . $s['id'] . '/publish') ?>" method="POST">
                                                    <input type="hidden" name="csrf_token" value="<?= e($csrf_token ?? '') ?>">
                                                    <button type="submit" class="btn btn-primary btn-sm" style="background:#059669;" title="Publish">
                                                        <i data-lucide="globe" style="width:14px; height:14px;"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <?php if ($s['status'] === 'published'): ?>
                                                <form action="<?= url('/admin/scholarships/' . $s['id'] . '/archive') ?>" method="POST">
                                                    <input type="hidden" name="csrf_token" value="<?= e($csrf_token ?? '') ?>">
                                                    <button type="submit" class="btn btn-secondary btn-sm" style="color:#64748b;" title="Archive">
                                                        <i data-lucide="archive" style="width:14px; height:14px;"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <form action="<?= url('/admin/scholarships/' . $s['id'] . '/delete') ?>" method="POST" onsubmit="return confirm('Are you sure you want to delete this scholarship?');">
                                                <input type="hidden" name="csrf_token" value="<?= e($csrf_token ?? '') ?>">
                                                <button type="submit" class="btn btn-danger btn-sm" title="Delete">
                                                    <i data-lucide="trash-2" style="width:14px; height:14px;"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Section -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <span style="font-size:0.875rem; color:var(--text-600);">Showing page <?= e($page) ?> of <?= e($totalPages) ?> (Total: <?= e($totalCount) ?>)</span>
                    <div class="pagination-links">
                        <a href="?page=<?= e($page - 1) ?>&search=<?= e($search) ?>&country_id=<?= e($countryId) ?>&funding_type=<?= e($funding) ?>&status=<?= e($status) ?>" class="pagination-btn <?= $page <= 1 ? 'disabled' : '' ?>">Previous</a>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?page=<?= e($i) ?>&search=<?= e($search) ?>&country_id=<?= e($countryId) ?>&funding_type=<?= e($funding) ?>&status=<?= e($status) ?>" class="pagination-btn <?= $page === $i ? 'active' : '' ?>"><?= e($i) ?></a>
                        <?php endfor; ?>
                        <a href="?page=<?= e($page + 1) ?>&search=<?= e($search) ?>&country_id=<?= e($countryId) ?>&funding_type=<?= e($funding) ?>&status=<?= e($status) ?>" class="pagination-btn <?= $page >= $totalPages ? 'disabled' : '' ?>">Next</a>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
