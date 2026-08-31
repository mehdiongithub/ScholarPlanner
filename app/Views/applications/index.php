<style>
        .tracker-layout {
            min-height: 100vh;
            background: #f8fafc;
            display: flex;
            flex-direction: column;
        }
        .tracker-header {
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
        .tracker-content {
            max-width: 1200px;
            width: 100%;
            margin: 40px auto;
            padding: 0 20px;
            flex-grow: 1;
        }
        .welcome-section {
            margin-bottom: 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
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
            min-width: 200px;
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
        .applications-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-bottom: 40px;
        }
        .application-card {
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            padding: 24px;
            box-shadow: var(--shadow-sm);
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            align-items: center;
            gap: 24px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .application-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        .scholarship-info {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .sch-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--text-900);
            text-decoration: none;
            transition: color 0.2s;
        }
        .sch-title:hover {
            color: var(--primary);
        }
        .sch-provider {
            font-size: 0.875rem;
            color: var(--text-500);
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .metrics-cell {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .metric-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-400);
            text-transform: uppercase;
        }
        .metric-value {
            font-size: 0.9375rem;
            font-weight: 600;
            color: var(--text-800);
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .badge-status {
            padding: 6px 12px;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            display: inline-block;
            text-align: center;
            width: fit-content;
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

        .progress-container {
            width: 100%;
        }
        .progress-text {
            display: flex;
            justify-content: space-between;
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--text-500);
            margin-top: 4px;
        }
        .progress-bg {
            background: #e2e8f0;
            height: 6px;
            border-radius: 9999px;
            overflow: hidden;
            width: 100%;
        }
        .progress-fill {
            background: var(--primary);
            height: 100%;
            border-radius: 9999px;
        }
        .deadline-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: var(--radius-md);
            font-size: 0.8125rem;
            font-weight: 600;
        }
        .deadline-active { background: #f8fafc; color: var(--text-700); }
        .deadline-urgent { background: #fff5f5; color: #e53e3e; border: 1px solid #fed7d7; }
        .deadline-passed { background: #f1f5f9; color: #718096; }

        .actions-cell {
            display: flex;
            align-items: center;
            gap: 12px;
            justify-content: flex-end;
        }
        .btn-view {
            background: var(--primary);
            color: white;
            padding: 8px 16px;
            border-radius: var(--radius-md);
            text-decoration: none;
            font-size: 0.8125rem;
            font-weight: 600;
            transition: background 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-view:hover {
            background: var(--primary-hover);
        }

        .alert-error-box {
            background: #fff5f5;
            border: 1px solid #fed7d7;
            padding: 16px;
            border-radius: var(--radius-lg);
            margin-bottom: 24px;
            color: #c53030;
            font-size: 0.875rem;
        }
        .alert-success-box {
            background: #f0fff4;
            border: 1px solid #c6f6d5;
            padding: 16px;
            border-radius: var(--radius-lg);
            margin-bottom: 24px;
            color: #22543d;
            font-size: 0.875rem;
        }

        .empty-state {
            background: var(--bg-white);
            border: 1px dashed var(--border);
            border-radius: var(--radius-xl);
            padding: 60px 20px;
            text-align: center;
            color: var(--text-500);
        }
        .empty-state i {
            font-size: 3rem;
            color: var(--text-300);
            margin-bottom: 16px;
            display: block;
        }

        .pagination {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 32px;
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

        @media (max-width: 1024px) {
            .application-card {
                grid-template-columns: 1fr 1fr;
                gap: 20px;
            }
            .actions-cell {
                grid-column: span 2;
            }
        }
        @media (max-width: 640px) {
            .application-card {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            .actions-cell {
                grid-column: span 1;
                justify-content: flex-start;
            }
        }
    </style>
</head>
<body>
<?php
$title = 'Application Tracker';
include ROOT_PATH . '/app/Views/layouts/student_header.php';
?>

        <main class="tracker-content">
            <div class="welcome-section">
                <div>
                    <h1 class="welcome-title">Application Tracker</h1>
                    <p class="welcome-subtitle">Manage and track your scholarship application submissions and milestones</p>
                </div>
            </div>

            <!-- Flash alerts -->
            <?php if (isset($_SESSION['application_success'])): ?>
                <div class="alert-success-box" role="alert">
                    <?= e($_SESSION['application_success']) ?>
                    <?php unset($_SESSION['application_success']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['application_errors'])): ?>
                <div class="alert-error-box" role="alert">
                    <ul style="margin: 0; padding-left: 20px;">
                        <?php foreach ($_SESSION['application_errors'] as $err): ?>
                            <li><?= e($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php unset($_SESSION['application_errors']); ?>
                </div>
            <?php endif; ?>

            <!-- Search and filters -->
            <div class="search-filter-bar">
                <form class="filter-form" method="GET" action="/applications">
                    <div class="input-group">
                        <label class="input-label" for="search">Search Opportunities</label>
                        <input type="text" id="search" name="search" class="input-field" placeholder="Search by title or provider..." value="<?= e($search) ?>">
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

                    <div class="input-group">
                        <label class="input-label" for="sort">Sort By</label>
                        <select id="sort" name="sort" class="input-field">
                            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Date Added</option>
                            <option value="deadline" <?= $sort === 'deadline' ? 'selected' : '' ?>>Application Deadline</option>
                        </select>
                    </div>

                    <button type="submit" class="btn-filter">
                        <i data-lucide="filter" style="width: 16px; height: 16px;"></i>
                        <span>Apply Filters</span>
                    </button>
                    <?php if ($search !== '' || $status !== '' || $sort !== 'newest'): ?>
                        <a href="/applications" class="btn-clear">
                            <i data-lucide="x" style="width: 16px; height: 16px;"></i>
                            <span>Clear</span>
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- List -->
            <div class="applications-list">
                <?php if (empty($applications)): ?>
                    <div class="empty-state">
                        <i data-lucide="folder-open"></i>
                        <h3>No Tracked Applications Found</h3>
                        <p>Search scholarships in our catalog to add them to your tracker.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($applications as $app): ?>
                        <div class="application-card">
                            <div class="scholarship-info">
                                <a href="/applications/<?= e($app['id']) ?>" class="sch-title"><?= e($app['title']) ?></a>
                                <div class="sch-provider">
                                    <i data-lucide="building" style="width: 14px; height: 14px;"></i>
                                    <span><?= e($app['provider_name']) ?></span>
                                    <span>•</span>
                                    <span class="badge-status status-<?= e($app['status']) ?>"><?= str_replace('_', ' ', e($app['status'])) ?></span>
                                    <?php if (isset($app['match_score'])): ?>
                                        <span>•</span>
                                        <span class="match-score" style="font-size: 0.8125rem; font-weight: 600; color: #0d9488; display: inline-flex; align-items: center; gap: 4px;">
                                            <i data-lucide="award" style="width: 14px; height: 14px;"></i>
                                            <span><?= e($app['match_score']) ?>% Match</span>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="metrics-cell">
                                <span class="metric-label">Document Readiness</span>
                                <div class="progress-container">
                                    <div class="progress-bg">
                                        <div class="progress-fill" style="width: <?= e($app['readiness']['readiness_percentage']) ?>%;"></div>
                                    </div>
                                    <div class="progress-text">
                                        <span><?= e($app['readiness']['approved_count']) ?>/<?= e($app['readiness']['required_count']) ?> approved</span>
                                        <span><?= e($app['readiness']['readiness_percentage']) ?>%</span>
                                    </div>
                                </div>
                            </div>

                            <div class="metrics-cell">
                                <span class="metric-label">Deadline</span>
                                <?php if ($app['application_deadline'] === null): ?>
                                    <span class="deadline-badge deadline-active">Rolling Deadline</span>
                                <?php else: ?>
                                    <?php if ($app['days_remaining'] < 0): ?>
                                        <span class="deadline-badge deadline-passed">
                                            <i data-lucide="clock-alert" style="width: 14px; height: 14px;"></i>
                                            <span>Deadline Passed</span>
                                        </span>
                                    <?php elseif ($app['days_remaining'] <= 7): ?>
                                        <span class="deadline-badge deadline-urgent">
                                            <i data-lucide="alert-triangle" style="width: 14px; height: 14px;"></i>
                                            <span>Closing in <?= e($app['days_remaining']) ?>d</span>
                                        </span>
                                    <?php else: ?>
                                        <span class="deadline-badge deadline-active">
                                            <i data-lucide="calendar" style="width: 14px; height: 14px;"></i>
                                            <span><?= e($app['application_deadline']) ?></span>
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>

                            <div class="actions-cell">
                                <a href="/applications/<?= e($app['id']) ?>" class="btn-view">
                                    <i data-lucide="external-link" style="width: 14px; height: 14px;"></i>
                                    <span>Track Opportunity</span>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav class="pagination" role="navigation" aria-label="Pagination Navigation">
                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <a href="/applications?search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&sort=<?= urlencode($sort) ?>&page=<?= $p ?>" class="page-link <?= $page === $p ? 'active' : '' ?>">
                            <?= $p ?>
                        </a>
                    <?php endfor; ?>
                </nav>
            <?php endif; ?>
        </main>
    </div>

<?php include ROOT_PATH . '/app/Views/layouts/student_footer.php'; ?>
