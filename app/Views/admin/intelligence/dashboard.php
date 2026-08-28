<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Platform Operations & Operational Intelligence | ScholarMatch</title>
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
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.75rem;
            border-radius: var(--radius-md);
        }
        .btn-danger {
            background: #ef4444;
            color: var(--bg-white);
        }
        .btn-danger:hover {
            background: #dc2626;
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
        .alert-warning {
            background: #fffbeb;
            color: #92400e;
            border: 1px solid #fde68a;
        }
        .tab-menu {
            display: flex;
            gap: 8px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 32px;
            flex-wrap: wrap;
        }
        .tab-link {
            padding: 12px 20px;
            font-size: 0.9375rem;
            font-weight: 600;
            color: var(--text-600);
            text-decoration: none;
            border-bottom: 2px solid transparent;
            margin-bottom: -1px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .tab-link:hover {
            color: var(--primary);
        }
        .tab-link.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }
        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }
        .card {
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            padding: 24px;
            box-shadow: var(--shadow-sm);
        }
        .card-title-sm {
            font-size: 0.8125rem;
            text-transform: uppercase;
            font-weight: 600;
            color: var(--text-400);
            letter-spacing: 0.05em;
            margin-bottom: 8px;
        }
        .card-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-900);
        }
        .card-value.danger { color: #dc2626; }
        .card-value.warning { color: #d97706; }
        .card-value.success { color: #16a34a; }
        
        .progress-bar-container {
            width: 100%;
            height: 8px;
            background: #e2e8f0;
            border-radius: var(--radius-full);
            overflow: hidden;
            margin-top: 8px;
        }
        .progress-bar {
            height: 100%;
            background: var(--primary);
        }
        
        .table-container {
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            margin-bottom: 24px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }
        .table th, .table td {
            padding: 16px 24px;
            border-bottom: 1px solid var(--border);
            font-size: 0.875rem;
        }
        .table th {
            background: #f8fafc;
            font-weight: 600;
            color: var(--text-700);
        }
        .table td {
            color: var(--text-600);
            vertical-align: middle;
        }
        .table tr:last-child td {
            border-bottom: none;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: var(--radius-md);
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-warning { background: #fffbeb; color: #92400e; }
        .badge-success { background: #ecfdf5; color: #065f46; }
        .badge-info { background: #eff6ff; color: #1e40af; }
        .badge-neutral { background: #f1f5f9; color: #475569; }

        .filter-section {
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
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--text-700);
        }
        .form-control {
            padding: 10px 14px;
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            font-size: 0.875rem;
            outline: none;
            background: var(--bg-white);
            width: 100%;
        }
        .form-control:focus {
            border-color: var(--primary);
        }

        .pagination {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 16px;
            flex-wrap: wrap;
            gap: 16px;
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

        /* Analytics Graphics using pure CSS */
        .chart-bar-horizontal {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 12px;
        }
        .chart-label {
            width: 180px;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-700);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .chart-track {
            flex-grow: 1;
            height: 16px;
            background: #e2e8f0;
            border-radius: var(--radius-full);
            overflow: hidden;
        }
        .chart-fill {
            height: 100%;
            background: var(--primary);
            border-radius: var(--radius-full);
        }
        .chart-value {
            width: 60px;
            text-align: right;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-800);
        }

        .alert-card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 24px;
        }
        .alert-card {
            border: 1px solid transparent;
            border-radius: var(--radius-xl);
            padding: 20px;
            display: flex;
            gap: 16px;
            box-shadow: var(--shadow-sm);
        }
        .alert-card-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .alert-card.danger {
            background: #fff5f5;
            border-color: #feb2b2;
        }
        .alert-card.danger .alert-card-icon {
            background: #fed7d7;
            color: #c53030;
        }
        .alert-card.warning {
            background: #fffdf5;
            border-color: #fef3c7;
        }
        .alert-card.warning .alert-card-icon {
            background: #fef3c7;
            color: #d97706;
        }
        .alert-card-content h3 {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-900);
            margin-bottom: 4px;
        }
        .alert-card-content p {
            font-size: 0.875rem;
            color: var(--text-600);
            margin-bottom: 12px;
            line-height: 1.5;
        }

        @media (max-width: 768px) {
            .filter-form {
                grid-template-columns: 1fr;
            }
            .chart-bar-horizontal {
                flex-direction: column;
                align-items: flex-start;
                gap: 4px;
            }
            .chart-label {
                width: 100%;
            }
            .chart-value {
                width: 100%;
                text-align: left;
                font-size: 0.75rem;
            }
        }
        @media (max-width: 280px) {
            .tab-link {
                padding: 8px 12px;
                font-size: 0.8125rem;
            }
            .card-value {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <header class="admin-header" role="banner">
            <a href="/" class="logo-box">
                <i data-lucide="graduation-cap"></i>
                <span>ScholarMatch Admin Center</span>
            </a>
            <div class="nav-links">
                <a href="<?= url('/dashboard') ?>" class="nav-link">Dashboard</a>
                <a href="<?= url('/admin/scholarships') ?>" class="nav-link">Scholarships</a>
                <a href="<?= url('/admin/applications') ?>" class="nav-link">Applications</a>
                <a href="<?= url('/admin/documents') ?>" class="nav-link">Documents</a>
                <a href="<?= url('/admin/notifications') ?>" class="nav-link">Outbox</a>
                <a href="<?= url('/admin/intelligence') ?>" class="nav-link" style="color:var(--primary); font-weight:700;">Ops Panel</a>
                <form action="<?= url('/logout') ?>" method="POST" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="<?= e($csrf_token ?? '') ?>">
                    <button type="submit" class="nav-link" style="background:none; border:none; cursor:pointer; font-weight:500;">Log Out</button>
                </form>
            </div>
        </header>

        <main class="admin-content">
            <div class="header-section">
                <div>
                    <h1 class="page-title">Operational Intelligence & Operations Console</h1>
                    <p style="color:var(--text-500); font-size:0.9375rem; margin-top:4px;">Monitor scholarship quality metrics, dynamic analytics, system warnings, and bulk utility controls</p>
                </div>
            </div>

            <!-- Toast Feedbacks -->
            <?php if (!empty($_SESSION['intelligence_success'])): ?>
                <div class="alert alert-success" role="alert">
                    <?= e($_SESSION['intelligence_success']) ?>
                    <?php unset($_SESSION['intelligence_success']); ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($_SESSION['intelligence_error'])): ?>
                <div class="alert alert-danger" role="alert">
                    <?= e($_SESSION['intelligence_error']) ?>
                    <?php unset($_SESSION['intelligence_error']); ?>
                </div>
            <?php endif; ?>

            <!-- Navigation Tabs -->
            <nav class="tab-menu" aria-label="Operational Tabbed Submenu">
                <a href="?tab=alerts" class="tab-link <?= $tab === 'alerts' ? 'active' : '' ?>">
                    <i data-lucide="shield-alert"></i>
                    <span>Actionable Warnings</span>
                </a>
                <a href="?tab=quality" class="tab-link <?= $tab === 'quality' ? 'active' : '' ?>">
                    <i data-lucide="award"></i>
                    <span>Scholarship Quality</span>
                </a>
                <a href="?tab=analytics" class="tab-link <?= $tab === 'analytics' ? 'active' : '' ?>">
                    <i data-lucide="bar-chart-3"></i>
                    <span>Performance Analytics</span>
                </a>
                <a href="?tab=notifications" class="tab-link <?= $tab === 'notifications' ? 'active' : '' ?>">
                    <i data-lucide="send"></i>
                    <span>Outbox Metrics</span>
                </a>
                <?php if (\App\Services\Auth::hasRole('admin')): ?>
                    <a href="?tab=billing" class="tab-link <?= $tab === 'billing' ? 'active' : '' ?>">
                        <i data-lucide="receipt"></i>
                        <span>Billing & Monetization</span>
                    </a>
                <?php endif; ?>
            </nav>

            <!-- TAB CONTENT: ACTIONABLE WARNING ALERTS -->
            <?php if ($tab === 'alerts'): ?>
                <div class="alert-card-grid">
                    <!-- Expired Opportunity warning -->
                    <?php if ($alerts['expired_published'] > 0): ?>
                        <div class="alert-card danger">
                            <div class="alert-card-icon">
                                <i data-lucide="calendar-x"></i>
                            </div>
                            <div class="alert-card-content">
                                <h3>Expired Published Scholarships</h3>
                                <p>There are <strong><?= e($alerts['expired_published']) ?></strong> published scholarship listings whose application deadlines have already passed in real-time but are still marked open.</p>
                                <a href="?tab=quality&issue=expired" class="btn btn-secondary btn-sm">Review Expired</a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Failed Notification alerts -->
                    <?php if ($alerts['failed_notifications'] > 0): ?>
                        <div class="alert-card danger">
                            <div class="alert-card-icon">
                                <i data-lucide="alert-octagon"></i>
                            </div>
                            <div class="alert-card-content">
                                <h3>Failed Queue Reminders</h3>
                                <p>Platform workers encountered critical failures dispatching <strong><?= e($alerts['failed_notifications']) ?></strong> notification attempts. They have reached maximum retry thresholds.</p>
                                <a href="?tab=notifications&notif_status=failed" class="btn btn-secondary btn-sm">Inspect Queue failures</a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Stale notifications -->
                    <?php if ($alerts['stale_notifications'] > 0): ?>
                        <div class="alert-card warning">
                            <div class="alert-card-icon">
                                <i data-lucide="clock-alert"></i>
                            </div>
                            <div class="alert-card-content">
                                <h3>Stale Notification Jobs</h3>
                                <p>Detected <strong><?= e($alerts['stale_notifications']) ?></strong> notification job leases locked in 'processing' status past their lease expiry dates.</p>
                                <a href="/admin/notifications" class="btn btn-secondary btn-sm">Open Outbox Queue</a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Incomplete listing records -->
                    <?php if ($alerts['incomplete_scholarships'] > 0): ?>
                        <div class="alert-card warning">
                            <div class="alert-card-icon">
                                <i data-lucide="file-warning"></i>
                            </div>
                            <div class="alert-card-content">
                                <h3>Incomplete Opportunity Records</h3>
                                <p>There are <strong><?= e($alerts['incomplete_scholarships']) ?></strong> published opportunities missing critical details (short descriptions, study levels, or source URLs).</p>
                                <a href="?tab=quality&completeness=medium" class="btn btn-secondary btn-sm">View Low Quality Listings</a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Applications Attention -->
                    <?php if ($alerts['applications_attention'] > 0): ?>
                        <div class="alert-card warning">
                            <div class="alert-card-icon">
                                <i data-lucide="alert-triangle"></i>
                            </div>
                            <div class="alert-card-content">
                                <h3>Applications Requiring Attention</h3>
                                <p>There are <strong><?= e($alerts['applications_attention']) ?></strong> non-submitted student applications tracking opportunities that are already expired or closed/archived.</p>
                                <a href="<?= url('/admin/applications') ?>" class="btn btn-secondary btn-sm">Manage Applications</a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Document review backlogs -->
                    <?php if ($alerts['document_backlog'] > 0): ?>
                        <div class="alert-card warning">
                            <div class="alert-card-icon">
                                <i data-lucide="folder-search"></i>
                            </div>
                            <div class="alert-card-content">
                                <h3>Document Review Backlog</h3>
                                <p>Detected <strong><?= e($alerts['document_backlog']) ?></strong> newly submitted student qualification documents awaiting staff verification and review checks.</p>
                                <a href="<?= url('/admin/documents') ?>" class="btn btn-secondary btn-sm">Review Documents</a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($alerts['sys_config_warnings'])): ?>
                    <div style="margin-top:32px;">
                        <h3 class="card-title-sm">System Configuration Warnings</h3>
                        <?php foreach ($alerts['sys_config_warnings'] as $warn): ?>
                            <div class="alert alert-warning" role="alert" style="margin-bottom:12px;">
                                <i data-lucide="server" style="width:16px; height:16px; display:inline-block; vertical-align:middle; margin-right:8px;"></i>
                                <span><?= e($warn) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($alerts['expired_published'] == 0 && $alerts['failed_notifications'] == 0 && $alerts['stale_notifications'] == 0 && $alerts['incomplete_scholarships'] == 0 && $alerts['applications_attention'] == 0 && $alerts['document_backlog'] == 0): ?>
                    <div class="card" style="text-align:center; padding:48px 24px; color:var(--text-400);">
                        <i data-lucide="check-circle" style="width:48px; height:48px; color:#10b981; margin:0 auto 16px;"></i>
                        <h3>System Status: Healthy</h3>
                        <p>No actionable operational warnings or queue backlogs detected.</p>
                    </div>
                <?php endif; ?>

            <!-- TAB CONTENT: SCHOLARSHIP QUALITY MANAGEMENT -->
            <?php elseif ($tab === 'quality'): ?>
                <div class="card-grid">
                    <div class="card">
                        <div class="card-title-sm">Total Catalog Items</div>
                        <div class="card-value"><?= e($quality_stats['total']) ?></div>
                        <div style="font-size:0.75rem; color:var(--text-500); margin-top:8px;">
                            <?= e($quality_stats['published']) ?> published &bull; <?= e($quality_stats['drafts']) ?> drafts &bull; <?= e($quality_stats['archived']) ?> archived
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-title-sm">Avg Completeness Score</div>
                        <div class="card-value <?= $quality_stats['avg_completeness'] < 70 ? 'warning' : 'success' ?>"><?= e($quality_stats['avg_completeness']) ?>%</div>
                        <div class="progress-bar-container">
                            <div class="progress-bar" style="width: <?= e($quality_stats['avg_completeness']) ?>%; background: <?= $quality_stats['avg_completeness'] < 70 ? '#d97706' : '#16a34a' ?>;"></div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-title-sm">Alert Issues Detected</div>
                        <div class="card-value danger"><?= e($quality_stats['expired'] + $quality_stats['missing_info'] + $quality_stats['invalid_urls'] + $quality_stats['no_docs'] + $quality_stats['duplicates']) ?></div>
                        <div style="font-size:0.75rem; color:var(--text-500); margin-top:8px;">
                            <?= e($quality_stats['expired']) ?> expired published &bull; <?= e($quality_stats['duplicates']) ?> duplicate titles &bull; <?= e($quality_stats['no_docs']) ?> no documents
                        </div>
                    </div>
                </div>

                <div class="filter-section">
                    <h3 style="font-size:1rem; font-weight:700; color:var(--text-800); margin-bottom:16px;">Quality Filter Criteria</h3>
                    <form method="GET" class="filter-form">
                        <input type="hidden" name="tab" value="quality">
                        
                        <div class="form-group">
                            <label class="form-label" for="search">Title / Provider Search</label>
                            <input type="text" id="search" name="search" class="form-control" placeholder="Search title..." value="<?= e($quality_list['filters']['search']) ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="completeness">Completeness Score</label>
                            <select id="completeness" name="completeness" class="form-control">
                                <option value="all" <?= $quality_list['filters']['completeness'] === 'all' ? 'selected' : '' ?>>All Scores</option>
                                <option value="low" <?= $quality_list['filters']['completeness'] === 'low' ? 'selected' : '' ?>>Low (&lt; 50%)</option>
                                <option value="medium" <?= $quality_list['filters']['completeness'] === 'medium' ? 'selected' : '' ?>>Medium (&lt; 80%)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="status">Listing Status</label>
                            <select id="status" name="status" class="form-control">
                                <option value="all" <?= $quality_list['filters']['status'] === 'all' ? 'selected' : '' ?>>All Statuses</option>
                                <option value="draft" <?= $quality_list['filters']['status'] === 'draft' ? 'selected' : '' ?>>Drafts Only</option>
                                <option value="published" <?= $quality_list['filters']['status'] === 'published' ? 'selected' : '' ?>>Published Only</option>
                                <option value="archived" <?= $quality_list['filters']['status'] === 'archived' ? 'selected' : '' ?>>Archived Only</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="issue">Quality Alert Issues</label>
                            <select id="issue" name="issue" class="form-control">
                                <option value="all" <?= $quality_list['filters']['issue'] === 'all' ? 'selected' : '' ?>>All Opportunities</option>
                                <option value="expired" <?= $quality_list['filters']['issue'] === 'expired' ? 'selected' : '' ?>>Expired Published</option>
                                <option value="closing_soon" <?= $quality_list['filters']['issue'] === 'closing_soon' ? 'selected' : '' ?>>Closing Soon</option>
                                <option value="invalid_urls" <?= $quality_list['filters']['issue'] === 'invalid_urls' ? 'selected' : '' ?>>Invalid URLs</option>
                                <option value="no_docs" <?= $quality_list['filters']['issue'] === 'no_docs' ? 'selected' : '' ?>>No Documents</option>
                                <option value="duplicates" <?= $quality_list['filters']['issue'] === 'duplicates' ? 'selected' : '' ?>>Potential Duplicates</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="sort">Sort By</label>
                            <select id="sort" name="sort" class="form-control">
                                <option value="completeness_score" <?= $quality_list['filters']['sort'] === 'completeness_score' ? 'selected' : '' ?>>Completeness (Highest)</option>
                                <option value="completeness_score_asc" <?= $quality_list['filters']['sort'] === 'completeness_score_asc' ? 'selected' : '' ?>>Completeness (Lowest)</option>
                                <option value="title" <?= $quality_list['filters']['sort'] === 'title' ? 'selected' : '' ?>>Alphabetical (Title)</option>
                                <option value="provider" <?= $quality_list['filters']['sort'] === 'provider' ? 'selected' : '' ?>>Alphabetical (Provider)</option>
                                <option value="status" <?= $quality_list['filters']['sort'] === 'status' ? 'selected' : '' ?>>State Status</option>
                                <option value="verification_status" <?= $quality_list['filters']['sort'] === 'verification_status' ? 'selected' : '' ?>>Verification Status</option>
                                <option value="deadline" <?= $quality_list['filters']['sort'] === 'deadline' ? 'selected' : '' ?>>Deadline Soon</option>
                            </select>
                        </div>

                        <div style="display:flex; gap:8px;">
                            <button type="submit" class="btn btn-primary" style="padding:10px 16px;">
                                <i data-lucide="filter"></i>Apply
                            </button>
                            <a href="?tab=quality" class="btn btn-secondary" style="padding:10px 16px;">Reset</a>
                        </div>
                    </form>
                </div>

                <!-- SAFE BULK UTILITIES GATED GLOBO-TRANSACTIONS FORM -->
                <form id="bulkActionForm" method="POST" action="<?= url('/admin/intelligence/quality/bulk') ?>">
                    <input type="hidden" name="csrf_token" value="<?= e($csrf_token ?? '') ?>">
                    
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; flex-wrap:wrap; gap:8px;">
                        <span style="font-size:0.875rem; color:var(--text-500); font-weight:500;">
                            Found <strong><?= e($quality_list['total_items']) ?></strong> matching listings
                        </span>
                        
                        <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                            <span style="font-size:0.8125rem; font-weight:600; color:var(--text-600);">Bulk Operations:</span>
                            <select name="bulk_action" id="bulkActionSelect" class="form-control" style="width:auto; padding:6px 12px; font-size:0.8125rem;">
                                <option value="">-- Select Action --</option>
                                <option value="verify">Bulk Verify Selected</option>
                                <option value="archive">Bulk Archive Selected (If Expired)</option>
                                <option value="delete_drafts">Bulk Delete Selected (Drafts Only)</option>
                            </select>
                            <button type="submit" class="btn btn-primary btn-sm" onclick="return confirmBulkAction();">Apply Bulk Action</button>
                        </div>
                    </div>

                    <div class="table-container">
                        <table class="table" style="table-layout:fixed;">
                            <thead>
                                <tr>
                                    <th style="width:40px;"><input type="checkbox" id="selectAllCheckbox" onclick="toggleSelectAll(this)"></th>
                                    <th style="width:25%;">Scholarship Opportunity</th>
                                    <th style="width:20%;">Provider</th>
                                    <th style="width:12%;">State</th>
                                    <th style="width:12%;">Verification</th>
                                    <th style="width:15%;">Completeness Score</th>
                                    <th style="width:16%;">Validation Warnings</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($quality_list['items'])): ?>
                                    <tr>
                                        <td colspan="7" style="text-align:center; color:var(--text-400);">No scholarship opportunities found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($quality_list['items'] as $s): ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" name="scholarship_ids[]" value="<?= e($s['id']) ?>" class="scholarship-checkbox">
                                            </td>
                                            <td>
                                                <div style="font-weight:600; color:var(--text-800); text-overflow:ellipsis; overflow:hidden; white-space:nowrap;" title="<?= e($s['title']) ?>">
                                                    <?= e($s['title']) ?>
                                                </div>
                                                <div style="font-size:0.75rem; color:var(--text-400); margin-top:4px;">
                                                    Deadline: <?= e($s['application_deadline'] ?? 'Rolling') ?>
                                                </div>
                                            </td>
                                            <td style="text-overflow:ellipsis; overflow:hidden; white-space:nowrap;"><?= e($s['provider_name']) ?></td>
                                            <td>
                                                <span class="badge badge-neutral"><?= e($s['status']) ?></span>
                                            </td>
                                            <td>
                                                <span class="badge <?= $s['verification_status'] === 'verified' ? 'badge-success' : 'badge-warning' ?>">
                                                    <?= e($s['verification_status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div style="display:flex; align-items:center; gap:8px;">
                                                    <span style="font-weight:700; color:var(--text-800); min-width:32px;"><?= e($s['completeness_score']) ?>%</span>
                                                    <div class="progress-bar-container" style="margin-top:0; flex-grow:1;">
                                                        <div class="progress-bar" style="width: <?= e($s['completeness_score']) ?>%; background: <?= $s['completeness_score'] < 50 ? '#ef4444' : ($s['completeness_score'] < 80 ? '#d97706' : '#16a34a') ?>;"></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td style="font-size:0.75rem; color:#b91c1c; font-weight:500;">
                                                <?php if (empty($s['warnings'])): ?>
                                                    <span style="color:#16a34a; font-weight:600;">Perfect &bull; Good</span>
                                                <?php else: ?>
                                                    <?= e(implode(', ', $s['warnings'])) ?>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </form>

                <!-- Paginated navigation controls -->
                <?php if ($quality_list['total_pages'] > 1): ?>
                    <div class="pagination">
                        <span style="font-size:0.875rem; color:var(--text-500);">Page <?= e($quality_list['page']) ?> of <?= e($quality_list['total_pages']) ?></span>
                        <div class="pagination-links">
                            <a href="?tab=quality&page=<?= e($quality_list['page'] - 1) ?>&search=<?= e($quality_list['filters']['search']) ?>&completeness=<?= e($quality_list['filters']['completeness']) ?>&status=<?= e($quality_list['filters']['status']) ?>&issue=<?= e($quality_list['filters']['issue']) ?>&sort=<?= e($quality_list['filters']['sort']) ?>" class="pagination-btn <?= $quality_list['page'] <= 1 ? 'disabled' : '' ?>">Previous</a>
                            <?php for ($i = 1; $i <= $quality_list['total_pages']; $i++): ?>
                                <a href="?tab=quality&page=<?= e($i) ?>&search=<?= e($quality_list['filters']['search']) ?>&completeness=<?= e($quality_list['filters']['completeness']) ?>&status=<?= e($quality_list['filters']['status']) ?>&issue=<?= e($quality_list['filters']['issue']) ?>&sort=<?= e($quality_list['filters']['sort']) ?>" class="pagination-btn <?= $quality_list['page'] === $i ? 'active' : '' ?>"><?= e($i) ?></a>
                            <?php endfor; ?>
                            <a href="?tab=quality&page=<?= e($quality_list['page'] + 1) ?>&search=<?= e($quality_list['filters']['search']) ?>&completeness=<?= e($quality_list['filters']['completeness']) ?>&status=<?= e($quality_list['filters']['status']) ?>&issue=<?= e($quality_list['filters']['issue']) ?>&sort=<?= e($quality_list['filters']['sort']) ?>" class="pagination-btn <?= $quality_list['page'] >= $quality_list['total_pages'] ? 'disabled' : '' ?>">Next</a>
                        </div>
                    </div>
                <?php endif; ?>

            <!-- TAB CONTENT: PERFORMANCE & APPLICATION ANALYTICS -->
            <?php elseif ($tab === 'analytics'): ?>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:24px; margin-bottom:32px; align-items:start;">
                    <!-- Funnel conversion -->
                    <div class="card" style="grid-column: 1 / -1;">
                        <h3 class="section-title">
                            <i data-lucide="funnel"></i>
                            <span>Application Tracking Conversion Funnel (Unique Count: <?= e($app_analytics['funnel']['interested']) ?> students)</span>
                        </h3>
                        <div style="margin-top:16px;">
                            <!-- Stage 1 -->
                            <div class="chart-bar-horizontal">
                                <div class="chart-label">Stage 1: Interested Bookmarks</div>
                                <div class="chart-track">
                                    <div class="chart-fill" style="width: 100%; background: #94a3b8;"></div>
                                </div>
                                <div class="chart-value"><?= e($app_analytics['funnel']['interested']) ?></div>
                            </div>
                            <!-- Stage 2 -->
                            <div class="chart-bar-horizontal">
                                <div class="chart-label">Stage 2: Active Planning</div>
                                <div class="chart-track">
                                    <?php 
                                        $planningPercent = $app_analytics['funnel']['interested'] > 0 
                                            ? round(($app_analytics['funnel']['planning'] / $app_analytics['funnel']['interested']) * 100) 
                                            : 0;
                                    ?>
                                    <div class="chart-fill" style="width: <?= e($planningPercent) ?>%; background: #d97706;"></div>
                                </div>
                                <div class="chart-value"><?= e($app_analytics['funnel']['planning']) ?> (<?= e($planningPercent) ?>%)</div>
                            </div>
                            <!-- Stage 3 -->
                            <div class="chart-bar-horizontal">
                                <div class="chart-label">Stage 3: Submitted App</div>
                                <div class="chart-track">
                                    <?php 
                                        $subPercent = $app_analytics['funnel']['interested'] > 0 
                                            ? round(($app_analytics['funnel']['submitted'] / $app_analytics['funnel']['interested']) * 100) 
                                            : 0;
                                    ?>
                                    <div class="chart-fill" style="width: <?= e($subPercent) ?>%; background: var(--primary);"></div>
                                </div>
                                <div class="chart-value"><?= e($app_analytics['funnel']['submitted']) ?> (<?= e($subPercent) ?>%)</div>
                            </div>
                            <!-- Stage 4 -->
                            <div class="chart-bar-horizontal">
                                <div class="chart-label">Stage 4: Administrative Outcome</div>
                                <div class="chart-track">
                                    <?php 
                                        $outPercent = $app_analytics['funnel']['interested'] > 0 
                                            ? round(($app_analytics['funnel']['outcome'] / $app_analytics['funnel']['interested']) * 100) 
                                            : 0;
                                    ?>
                                    <div class="chart-fill" style="width: <?= e($outPercent) ?>%; background: #16a34a;"></div>
                                </div>
                                <div class="chart-value"><?= e($app_analytics['funnel']['outcome']) ?> (<?= e($outPercent) ?>%)</div>
                            </div>
                        </div>
                    </div>

                    <!-- Application status aggregates -->
                    <div class="card">
                        <h3 class="section-title">
                            <i data-lucide="file-check-2"></i>
                            <span>Tracking States Breakdown</span>
                        </h3>
                        <table class="table" style="margin-top:12px;">
                            <thead>
                                <tr>
                                    <th>Status Value</th>
                                    <th style="text-align:right;">Applications Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="badge badge-neutral">Interested</span></td>
                                    <td style="text-align:right; font-weight:600;"><?= e($app_analytics['status_counts']['interested'] ?? 0) ?></td>
                                </tr>
                                <tr>
                                    <td><span class="badge badge-neutral">Planning</span></td>
                                    <td style="text-align:right; font-weight:600;"><?= e($app_analytics['status_counts']['planning'] ?? 0) ?></td>
                                </tr>
                                <tr>
                                    <td><span class="badge badge-neutral">Documents Pending</span></td>
                                    <td style="text-align:right; font-weight:600;"><?= e($app_analytics['status_counts']['documents_pending'] ?? 0) ?></td>
                                </tr>
                                <tr>
                                    <td><span class="badge badge-neutral">Ready to Apply</span></td>
                                    <td style="text-align:right; font-weight:600;"><?= e($app_analytics['status_counts']['ready_to_apply'] ?? 0) ?></td>
                                </tr>
                                <tr>
                                    <td><span class="badge badge-info">Applied</span></td>
                                    <td style="text-align:right; font-weight:600;"><?= e($app_analytics['status_counts']['applied'] ?? 0) ?></td>
                                </tr>
                                <tr>
                                    <td><span class="badge badge-warning">Under Review / Interview</span></td>
                                    <td style="text-align:right; font-weight:600;"><?= e($app_analytics['status_counts']['interview'] ?? 0) ?></td>
                                </tr>
                                <tr>
                                    <td><span class="badge badge-success">Accepted / Approved</span></td>
                                    <td style="text-align:right; font-weight:600;"><?= e($app_analytics['status_counts']['accepted'] ?? 0) ?></td>
                                </tr>
                                <tr>
                                    <td><span class="badge badge-danger">Rejected</span></td>
                                    <td style="text-align:right; font-weight:600;"><?= e($app_analytics['status_counts']['rejected'] ?? 0) ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Matching engine analytics -->
                    <div class="card">
                        <h3 class="section-title">
                            <i data-lucide="sparkles"></i>
                            <span>Matching Engine Analytics</span>
                        </h3>
                        <div style="margin-bottom:24px;">
                            <div class="card-title-sm">Average Match Score</div>
                            <div class="card-value warning"><?= e($match_analytics['avg_score']) ?>%</div>
                            <div style="font-size:0.75rem; color:var(--text-500); margin-top:4px;">
                                Computed across <strong><?= e($match_analytics['matched_students']) ?></strong> unique student match tables
                            </div>
                        </div>

                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Match Eligibility Status</th>
                                    <th style="text-align:right;">Total Mapped Matches</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="badge badge-success">Eligible</span></td>
                                    <td style="text-align:right; font-weight:600;"><?= e($match_analytics['eligibility_stats']['ELIGIBLE'] ?? 0) ?></td>
                                </tr>
                                <tr>
                                    <td><span class="badge badge-warning">Possibly Eligible</span></td>
                                    <td style="text-align:right; font-weight:600;"><?= e($match_analytics['eligibility_stats']['POSSIBLY_ELIGIBLE'] ?? 0) ?></td>
                                </tr>
                                <tr>
                                    <td><span class="badge badge-neutral">Insufficient Data</span></td>
                                    <td style="text-align:right; font-weight:600;"><?= e($match_analytics['eligibility_stats']['INSUFFICIENT_DATA'] ?? 0) ?></td>
                                </tr>
                                <tr>
                                    <td><span class="badge badge-danger">Not Eligible</span></td>
                                    <td style="text-align:right; font-weight:600;"><?= e($match_analytics['eligibility_stats']['NOT_ELIGIBLE'] ?? 0) ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Most popular study countries & Top fields -->
                    <div class="card">
                        <h3 class="section-title">
                            <i data-lucide="globe"></i>
                            <span>Top Host Countries (Target Destinations)</span>
                        </h3>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Country Name</th>
                                    <th style="text-align:right;">Applications Received</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($app_analytics['country_stats'])): ?>
                                    <tr>
                                        <td colspan="2" style="text-align:center; color:var(--text-400);">No country maps recorded.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($app_analytics['country_stats'] as $cs): ?>
                                        <tr>
                                            <td><?= e($cs['country_name']) ?></td>
                                            <td style="text-align:right; font-weight:600;"><?= e($cs['cnt']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="card">
                        <h3 class="section-title">
                            <i data-lucide="graduation-cap"></i>
                            <span>Top Disciplines / Fields of Study</span>
                        </h3>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Field Name</th>
                                    <th style="text-align:right;">Applications Received</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($app_analytics['field_stats'])): ?>
                                    <tr>
                                        <td colspan="2" style="text-align:center; color:var(--text-400);">No fields tracking recorded.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($app_analytics['field_stats'] as $fs): ?>
                                        <tr>
                                            <td><?= e($fs['field_name']) ?></td>
                                            <td style="text-align:right; font-weight:600;"><?= e($fs['cnt']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Most matched vs least matched -->
                    <div class="card">
                        <h3 class="section-title">
                            <i data-lucide="chevrons-up"></i>
                            <span>Highest Recommendation Matches</span>
                        </h3>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Scholarship Name</th>
                                    <th style="text-align:right;">Eligible Matches</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($match_analytics['top_matches'] as $tm): ?>
                                    <tr>
                                        <td><?= e($tm['title']) ?></td>
                                        <td style="text-align:right; font-weight:600;"><?= e($tm['cnt']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="card">
                        <h3 class="section-title">
                            <i data-lucide="chevrons-down"></i>
                            <span>Lowest Recommendation Matches (Needs Promotion)</span>
                        </h3>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Scholarship Name</th>
                                    <th style="text-align:right;">Eligible Matches</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($match_analytics['low_matches'] as $lm): ?>
                                    <tr>
                                        <td><?= e($lm['title']) ?></td>
                                        <td style="text-align:right; font-weight:600;"><?= e($lm['cnt']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <!-- TAB CONTENT: OUTBOX METRICS & QUEUE SEARCH -->
            <?php elseif ($tab === 'notifications'): ?>
                <div class="card-grid">
                    <div class="card">
                        <div class="card-title-sm">Total Dispatches Logged</div>
                        <div class="card-value"><?= e($notif_analytics['total']) ?></div>
                        <div style="font-size:0.75rem; color:var(--text-500); margin-top:8px;">
                            Email: <?= e($notif_analytics['channel_counts']['email'] ?? 0) ?> &bull; WhatsApp: <?= e($notif_analytics['channel_counts']['whatsapp'] ?? 0) ?>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-title-sm">Delivery Success Rate</div>
                        <div class="card-value success"><?= e($notif_analytics['success_rate']) ?>%</div>
                        <div class="progress-bar-container">
                            <div class="progress-bar" style="width: <?= e($notif_analytics['success_rate']) ?>%; background:#16a34a;"></div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-title-sm">Queue Processing Statuses</div>
                        <div style="font-size:0.875rem; color:var(--text-600); display:flex; flex-direction:column; gap:4px; margin-top:8px;">
                            <div>Queued/Pending: <strong><?= e($notif_analytics['status_counts']['pending'] ?? 0) ?></strong></div>
                            <div>Retrying: <strong><?= e($notif_analytics['status_counts']['retrying'] ?? 0) ?></strong></div>
                            <div>Skipped/Canceled: <strong><?= e($notif_analytics['status_counts']['skipped'] ?? 0) ?></strong></div>
                            <div style="color:#ef4444;">Failed Exhausted: <strong><?= e($notif_analytics['status_counts']['failed'] ?? 0) ?></strong></div>
                        </div>
                    </div>
                </div>

                <div class="filter-section">
                    <h3 style="font-size:1rem; font-weight:700; color:var(--text-800); margin-bottom:16px;">Queue Search Controls</h3>
                    <form method="GET" class="filter-form">
                        <input type="hidden" name="tab" value="notifications">
                        
                        <div class="form-group">
                            <label class="form-label" for="notif_search">Recipient or Subject Search</label>
                            <input type="text" id="notif_search" name="notif_search" class="form-control" placeholder="Search email/recipient..." value="<?= e($notif_list['filters']['search']) ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="notif_status">Dispatch Status</label>
                            <select id="notif_status" name="notif_status" class="form-control">
                                <option value="all" <?= $notif_list['filters']['status'] === 'all' ? 'selected' : '' ?>>All Log Entries</option>
                                <option value="pending" <?= $notif_list['filters']['status'] === 'pending' ? 'selected' : '' ?>>Queued/Pending</option>
                                <option value="processing" <?= $notif_list['filters']['status'] === 'processing' ? 'selected' : '' ?>>Processing Lease</option>
                                <option value="sent" <?= $notif_list['filters']['status'] === 'sent' ? 'selected' : '' ?>>Delivered/Sent</option>
                                <option value="failed" <?= $notif_list['filters']['status'] === 'failed' ? 'selected' : '' ?>>Failed Exhausted</option>
                                <option value="retrying" <?= $notif_list['filters']['status'] === 'retrying' ? 'selected' : '' ?>>Retrying Backoff</option>
                                <option value="skipped" <?= $notif_list['filters']['status'] === 'skipped' ? 'selected' : '' ?>>Skipped Opt-out</option>
                            </select>
                        </div>

                        <div style="display:flex; gap:8px;">
                            <button type="submit" class="btn btn-primary" style="padding:10px 16px;">Search Log</button>
                            <a href="?tab=notifications" class="btn btn-secondary" style="padding:10px 16px;">Reset</a>
                        </div>
                    </form>
                </div>

                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Dispatch Channel</th>
                                <th>Recipient</th>
                                <th>Alert Type</th>
                                <th>Status</th>
                                <th>Attempts</th>
                                <th>Created At</th>
                                <th>Error Log</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($notif_list['items'])): ?>
                                <tr>
                                    <td colspan="7" style="text-align:center; color:var(--text-400);">No dispatch queue logs matching filter.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($notif_list['items'] as $n): ?>
                                    <tr>
                                        <td>
                                            <span class="badge badge-neutral" style="text-transform:uppercase;"><?= e($n['channel']) ?></span>
                                        </td>
                                        <td><?= e($n['recipient']) ?></td>
                                        <td><?= e(str_replace('_', ' ', $n['notification_type'])) ?></td>
                                        <td>
                                            <span class="badge <?= $n['status'] === 'sent' ? 'badge-success' : ($n['status'] === 'failed' ? 'badge-danger' : 'badge-warning') ?>">
                                                <?= e($n['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= e($n['attempts']) ?></td>
                                        <td><?= e(date('M d, Y H:i', strtotime($n['created_at']))) ?></td>
                                        <td style="font-size:0.75rem; color:#b91c1c; font-style:italic;">
                                            <?= e($n['error_message'] ?? 'None') ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($notif_list['total_pages'] > 1): ?>
                    <div class="pagination">
                        <span style="font-size:0.875rem; color:var(--text-500);">Page <?= e($notif_list['page']) ?> of <?= e($notif_list['total_pages']) ?></span>
                        <div class="pagination-links">
                            <a href="?tab=notifications&notif_page=<?= e($notif_list['page'] - 1) ?>&notif_search=<?= e($notif_list['filters']['search']) ?>&notif_status=<?= e($notif_list['filters']['status']) ?>" class="pagination-btn <?= $notif_list['page'] <= 1 ? 'disabled' : '' ?>">Previous</a>
                            <?php for ($i = 1; $i <= $notif_list['total_pages']; $i++): ?>
                                <a href="?tab=notifications&notif_page=<?= e($i) ?>&notif_search=<?= e($notif_list['filters']['search']) ?>&notif_status=<?= e($notif_list['filters']['status']) ?>" class="pagination-btn <?= $notif_list['page'] === $i ? 'active' : '' ?>"><?= e($i) ?></a>
                            <?php endfor; ?>
                            <a href="?tab=notifications&notif_page=<?= e($notif_list['page'] + 1) ?>&notif_search=<?= e($notif_list['filters']['search']) ?>&notif_status=<?= e($notif_list['filters']['status']) ?>" class="pagination-btn <?= $notif_list['page'] >= $notif_list['total_pages'] ? 'disabled' : '' ?>">Next</a>
                        </div>
                    </div>
                <?php endif; ?>

            <?php elseif ($tab === 'billing'): ?>
                <!-- Aggregate Stats Cards -->
                <div class="metrics-grid" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:20px; margin-bottom:30px;">
                    <div class="metric-card" style="background:var(--bg-white); border:1px solid var(--border); border-radius:var(--radius-xl); padding:24px; box-shadow:var(--shadow-sm);">
                        <div style="font-size:0.75rem; font-weight:700; color:var(--text-400); text-transform:uppercase; margin-bottom:8px;">Total Subscribers</div>
                        <div style="font-size:1.75rem; font-weight:800; color:var(--text-900);"><?= e($billing_stats['total_subscribers']) ?></div>
                    </div>
                    <div class="metric-card" style="background:var(--bg-white); border:1px solid var(--border); border-radius:var(--radius-xl); padding:24px; box-shadow:var(--shadow-sm);">
                        <div style="font-size:0.75rem; font-weight:700; color:#059669; text-transform:uppercase; margin-bottom:8px;">Active Subs</div>
                        <div style="font-size:1.75rem; font-weight:800; color:#059669;"><?= e($billing_stats['active_subscriptions']) ?></div>
                    </div>
                    <div class="metric-card" style="background:var(--bg-white); border:1px solid var(--border); border-radius:var(--radius-xl); padding:24px; box-shadow:var(--shadow-sm);">
                        <div style="font-size:0.75rem; font-weight:700; color:#475569; text-transform:uppercase; margin-bottom:8px;">Expired Subs</div>
                        <div style="font-size:1.75rem; font-weight:800; color:#475569;"><?= e($billing_stats['expired_subscriptions']) ?></div>
                    </div>
                    <div class="metric-card" style="background:var(--bg-white); border:1px solid var(--border); border-radius:var(--radius-xl); padding:24px; box-shadow:var(--shadow-sm);">
                        <div style="font-size:0.75rem; font-weight:700; color:#dc2626; text-transform:uppercase; margin-bottom:8px;">Cancelled Subs</div>
                        <div style="font-size:1.75rem; font-weight:800; color:#dc2626;"><?= e($billing_stats['cancelled_subscriptions']) ?></div>
                    </div>
                    <div class="metric-card" style="background:var(--bg-white); border:1px solid var(--border); border-radius:var(--radius-xl); padding:24px; box-shadow:var(--shadow-sm);">
                        <div style="font-size:0.75rem; font-weight:700; color:var(--primary); text-transform:uppercase; margin-bottom:8px;">Revenue Totals</div>
                        <div style="font-size:1.75rem; font-weight:800; color:var(--primary);">PKR <?= number_format($billing_stats['revenue_totals'], 2) ?></div>
                    </div>
                    <div class="metric-card" style="background:var(--bg-white); border:1px solid var(--border); border-radius:var(--radius-xl); padding:24px; box-shadow:var(--shadow-sm);">
                        <div style="font-size:0.75rem; font-weight:700; color:#d97706; text-transform:uppercase; margin-bottom:8px;">Success Rate</div>
                        <div style="font-size:1.75rem; font-weight:800; color:#d97706;"><?= e($billing_stats['payment_success_rate']) ?>%</div>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: 2fr 1fr; gap:30px; align-items:start; margin-bottom:40px;">
                    <!-- Recent Transactions list -->
                    <div style="background:var(--bg-white); border:1px solid var(--border); border-radius:var(--radius-2xl); padding:32px; box-shadow:var(--shadow-sm); overflow:hidden;">
                        <h3 style="font-size:1.125rem; font-weight:700; color:var(--text-900); margin-bottom:20px; display:flex; align-items:center; gap:8px;">
                            <i data-lucide="receipt"></i>
                            <span>Recent Transactions Log</span>
                        </h3>
                        <div style="overflow-x:auto;">
                            <table class="invoice-table" style="width:100%; border-collapse:collapse; font-size:0.875rem; text-align:left;">
                                <thead>
                                    <tr style="background:#f8fafc; color:var(--text-600);">
                                        <th style="padding:14px 16px; border-bottom:1px solid var(--border);">Date</th>
                                        <th style="padding:14px 16px; border-bottom:1px solid var(--border);">Email</th>
                                        <th style="padding:14px 16px; border-bottom:1px solid var(--border);">Reference ID</th>
                                        <th style="padding:14px 16px; border-bottom:1px solid var(--border);">Provider</th>
                                        <th style="padding:14px 16px; border-bottom:1px solid var(--border);">Amount</th>
                                        <th style="padding:14px 16px; border-bottom:1px solid var(--border);">Status</th>
                                        <th style="padding:14px 16px; border-bottom:1px solid var(--border);">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($billing_stats['transactions'])): ?>
                                        <tr>
                                            <td colspan="7" style="text-align:center; padding:24px; color:var(--text-400);">No transactions found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($billing_stats['transactions'] as $tx): ?>
                                            <tr>
                                                <td style="padding:14px 16px; border-bottom:1px solid var(--border);"><?= e(date('M d, Y', strtotime($tx['created_at']))) ?></td>
                                                <td style="padding:14px 16px; border-bottom:1px solid var(--border);"><?= e($tx['email']) ?></td>
                                                <td style="padding:14px 16px; border-bottom:1px solid var(--border); font-family:monospace; font-size:0.8125rem;"><?= e($tx['transaction_reference']) ?></td>
                                                <td style="padding:14px 16px; border-bottom:1px solid var(--border);"><?= e(ucfirst($tx['provider'])) ?></td>
                                                <td style="padding:14px 16px; border-bottom:1px solid var(--border); font-weight:600;">PKR <?= number_format($tx['amount'], 2) ?></td>
                                                <td style="padding:14px 16px; border-bottom:1px solid var(--border);">
                                                    <span class="sub-badge <?= e($tx['status'] === 'paid' ? 'active' : ($tx['status'] === 'refunded' ? 'expired' : $tx['status'])) ?>">
                                                        <?= e($tx['status']) ?>
                                                    </span>
                                                </td>
                                                <td style="padding:14px 16px; border-bottom:1px solid var(--border);">
                                                    <?php if ($tx['status'] === 'paid'): ?>
                                                        <form method="POST" action="<?= url('/admin/billing/refund') ?>" onsubmit="return confirm('Are you sure you want to refund this payment? It will revoke their premium status immediately.');" style="display:inline;">
                                                            <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                                                            <input type="hidden" name="transaction_id" value="<?= e($tx['id']) ?>">
                                                            <button type="submit" class="btn btn-secondary btn-sm" style="border-color:#ef4444; color:#ef4444; padding:2px 8px; font-size:0.6875rem;">
                                                                Refund
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <span style="color:var(--text-400); font-size:0.75rem; font-style:italic;">None</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Provider Distribution Breakdown -->
                    <div style="background:var(--bg-white); border:1px solid var(--border); border-radius:var(--radius-2xl); padding:32px; box-shadow:var(--shadow-sm);">
                        <h3 style="font-size:1.125rem; font-weight:700; color:var(--text-900); margin-bottom:20px; display:flex; align-items:center; gap:8px;">
                            <i data-lucide="wallet"></i>
                            <span>Gateway Provider Distribution</span>
                        </h3>
                        <ul style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:16px;">
                            <?php if (empty($billing_stats['provider_breakdown'])): ?>
                                <li style="text-align:center; padding:16px; color:var(--text-400);">No gateway usage logged.</li>
                            <?php else: ?>
                                <?php foreach ($billing_stats['provider_breakdown'] as $b): ?>
                                    <li style="border:1px solid var(--border); border-radius:var(--radius-lg); padding:16px; display:flex; justify-content:space-between; align-items:center;">
                                        <div>
                                            <div style="font-weight:700; color:var(--text-800);"><?= e(ucfirst($b['provider'])) ?></div>
                                            <div style="font-size:0.75rem; color:var(--text-400);"><?= e($b['count']) ?> successful checkout transactions</div>
                                        </div>
                                        <div style="font-size:1.125rem; font-weight:800; color:var(--primary);">PKR <?= number_format($b['total'], 2) ?></div>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>

            <?php endif; ?>

        </main>
    </div>

    <script>
        lucide.createIcons();

        function toggleSelectAll(masterCheckbox) {
            const checkboxes = document.querySelectorAll('.scholarship-checkbox');
            checkboxes.forEach(cb => cb.checked = masterCheckbox.checked);
        }

        function confirmBulkAction() {
            const action = document.getElementById('bulkActionSelect').value;
            if (!action) {
                alert('Please select a bulk action from the dropdown menu first.');
                return false;
            }

            const checkedCount = document.querySelectorAll('.scholarship-checkbox:checked').length;
            if (checkedCount === 0) {
                alert('Please check at least one scholarship checkbox from the table.');
                return false;
            }

            return confirm(`Are you absolutely sure you want to run the selected bulk operation on the ${checkedCount} checked scholarship opportunities?`);
        }
    </script>
</body>
</html>
