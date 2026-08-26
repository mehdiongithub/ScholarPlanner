<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | ScholarMatch</title>
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
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
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
        .progress-bar-container {
            width: 100%;
            height: 8px;
            background: #e2e8f0;
            border-radius: var(--radius-full);
            margin-top: 12px;
            overflow: hidden;
        }
        .progress-bar-fill {
            height: 100%;
            background: var(--primary);
            border-radius: var(--radius-full);
            transition: width 0.3s;
        }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: var(--radius-full);
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-active {
            background: #d1fae5;
            color: #065f46;
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
        .module-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }
        .module-card {
            border: 1px dashed var(--border);
            border-radius: var(--radius-xl);
            padding: 20px;
            text-align: center;
            opacity: 0.65;
            position: relative;
        }
        .module-card h3 {
            font-size: 0.9375rem;
            font-weight: 600;
            color: var(--text-800);
            margin-bottom: 8px;
        }
        .module-card p {
            font-size: 0.8125rem;
            color: var(--text-500);
        }
        .module-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            font-size: 0.6875rem;
            background: #f1f5f9;
            color: var(--text-600);
            padding: 2px 6px;
            border-radius: var(--radius-sm);
            font-weight: 600;
        }
        @media (max-width: 280px) {
            .dashboard-header {
                padding: 10px;
            }
            .user-name {
                display: none;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        .matches-filter-bar {
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            padding: 16px 24px;
            margin-bottom: 24px;
            box-shadow: var(--shadow-sm);
        }
        .filter-sort-form {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        .filter-sort-form .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            min-width: 200px;
            flex-grow: 1;
        }
        .filter-sort-form select {
            padding: 8px 12px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
            background: var(--bg-white);
            color: var(--text-800);
            font-size: 0.875rem;
            cursor: pointer;
            width: 100%;
        }
        .recommendations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 24px;
            margin-top: 24px;
        }
        .rec-card {
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius-2xl);
            padding: 24px;
            box-shadow: var(--shadow-sm);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .rec-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        .rec-header {
            margin-bottom: 16px;
        }
        .rec-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--text-900);
            margin-bottom: 4px;
            line-height: 1.4;
        }
        .rec-provider {
            font-size: 0.875rem;
            color: var(--text-500);
            margin-bottom: 8px;
        }
        .rec-meta {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            font-size: 0.8125rem;
            color: var(--text-600);
            margin-bottom: 16px;
        }
        .rec-meta span {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .rec-score-box {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #f8fafc;
            border-radius: var(--radius-xl);
            padding: 12px 16px;
            margin-bottom: 16px;
            border: 1px solid var(--border);
        }
        .rec-score-pct {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary);
        }
        .rec-status-badge {
            font-size: 0.75rem;
            font-weight: 700;
            padding: 4px 8px;
            border-radius: var(--radius-sm);
            text-transform: uppercase;
        }
        .status-eligible { background: #d1fae5; color: #065f46; }
        .status-possibly { background: #e0f2fe; color: #0369a1; }
        .status-insufficient { background: #fef3c7; color: #92400e; }
        .status-ineligible { background: #fee2e2; color: #991b1b; }

        .rec-criteria-list {
            list-style: none;
            padding: 0;
            margin: 0 0 20px 0;
            font-size: 0.8125rem;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .rec-criteria-item {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            line-height: 1.4;
        }
        .rec-criteria-item i {
            margin-top: 2px;
            flex-shrink: 0;
        }
        .crit-success { color: #10b981; }
        .crit-warning { color: #f59e0b; }
        .crit-danger { color: #ef4444; }

        .rec-footer {
            margin-top: auto;
            border-top: 1px solid var(--border);
            padding-top: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .rec-deadline {
            font-size: 0.75rem;
            color: var(--text-500);
        }
        .btn-view-rec {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--primary);
            color: white;
            padding: 8px 16px;
            border-radius: var(--radius-md);
            font-size: 0.8125rem;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.2s;
        }
        .btn-view-rec:hover {
            background: var(--primary-hover);
        }
        .rec-level-badge {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: var(--radius-full);
            display: inline-block;
            margin-bottom: 8px;
            text-transform: uppercase;
        }
        .level-highly-recommended { background: #d1fae5; color: #065f46; }
        .level-recommended { background: #ecfdf5; color: #047857; }
        .level-possible { background: #fef3c7; color: #92400e; }
        .level-low { background: #f1f5f9; color: #475569; }
        .level-not-recommended { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>

    <div class="dashboard-layout">
        <header class="dashboard-header" role="banner">
            <a href="/" class="logo-box">
                <i data-lucide="graduation-cap"></i>
                <span>ScholarMatch</span>
            </a>

            <div style="display: flex; align-items: center; gap: 20px;">
                <a href="<?= url('/dashboard') ?>" class="nav-link active" style="font-size: 0.875rem; color: var(--text-900); text-decoration: none; font-weight: 600;">Dashboard</a>
                <a href="<?= url('/profile') ?>" class="nav-link" style="font-size: 0.875rem; color: var(--text-600); text-decoration: none; font-weight: 500;">My Profile</a>
                <a href="<?= url('/documents') ?>" class="nav-link" style="font-size: 0.875rem; color: var(--text-600); text-decoration: none; font-weight: 500;">Documents</a>
                <a href="<?= url('/applications') ?>" class="nav-link" style="font-size: 0.875rem; color: var(--text-600); text-decoration: none; font-weight: 500;">Applications</a>
            </div>
            
            <div class="user-menu">
                <span class="user-name">Welcome, <?= e($user['first_name']) ?></span>
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
                <h1 class="welcome-title">Student Dashboard</h1>
                <p class="welcome-subtitle">Manage your profile, matching options, and alerts</p>
            </div>

            <div class="stats-grid">
                <!-- Profile completion -->
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-label">Profile Completion</span>
                        <div class="stat-icon">
                            <i data-lucide="user"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= e($completion) ?>%</div>
                    <div class="progress-bar-container">
                        <div class="progress-bar-fill" style="width: <?= e($completion) ?>%;"></div>
                    </div>
                </div>

                <!-- Document readiness -->
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-label">Document Readiness</span>
                        <div class="stat-icon">
                            <i data-lucide="file-check"></i>
                        </div>
                    </div>
                    <div class="stat-value"><a href="<?= url('/documents') ?>" style="text-decoration: none; color: inherit;"><?= e($docReadiness['readiness_percentage']) ?>%</a></div>
                    <div class="progress-bar-container">
                        <div class="progress-bar-fill" style="width: <?= e($docReadiness['readiness_percentage']) ?>%;"></div>
                    </div>
                </div>

                <!-- Subscription status -->
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-label">Subscription Tier</span>
                        <div class="stat-icon success">
                            <i data-lucide="credit-card"></i>
                        </div>
                    </div>
                    <div class="stat-value" style="font-size: 1.25rem;"><?= e($subscription['plan_name']) ?></div>
                    <div style="margin-top: 12px; display: flex; align-items: center; justify-content: space-between;">
                        <span class="badge badge-active"><?= e(ucfirst($subscription['status'] ?? 'active')) ?></span>
                        <?php if (!empty($subscription['ends_at'])): ?>
                            <span style="font-size: 0.75rem; color: var(--text-500);">Expires: <?= e(date('M d, Y', strtotime($subscription['ends_at']))) ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Account info -->
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-label">Status & Details</span>
                        <div class="stat-icon warning">
                            <i data-lucide="shield-check"></i>
                        </div>
                    </div>
                    <div class="stat-value" style="font-size: 1.25rem;"><?= e($user['email']) ?></div>
                    <div style="margin-top: 12px; font-size: 0.8125rem; color: var(--text-600);">
                        Role: <strong><?= e(ucfirst($user['role_name'])) ?></strong> | Status: <strong><?= e(ucfirst($user['status'])) ?></strong>
                    </div>
                </div>
            </div>

            <div class="info-section">
                <h2 class="section-title">
                    <i data-lucide="award"></i>
                    <span>Recommended Scholarships</span>
                </h2>

                <!-- Matches Filter & Sort Bar -->
                <div class="matches-filter-bar">
                    <form action="" method="GET" class="filter-sort-form">
                        <div class="form-group">
                            <label for="filter" style="font-size: 0.8125rem; font-weight: 600; color: var(--text-600);">Filter Recommendations</label>
                            <select name="filter" id="filter" onchange="this.form.submit()">
                                <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>All Matches</option>
                                <option value="highly_recommended" <?= $filter === 'highly_recommended' ? 'selected' : '' ?>>Highly Recommended</option>
                                <option value="eligible" <?= $filter === 'eligible' ? 'selected' : '' ?>>Eligible</option>
                                <option value="possibly_eligible" <?= $filter === 'possibly_eligible' ? 'selected' : '' ?>>Possibly Eligible</option>
                                <option value="missing" <?= $filter === 'missing' ? 'selected' : '' ?>>Missing Information</option>
                                <option value="closing_soon" <?= $filter === 'closing_soon' ? 'selected' : '' ?>>Closing Soon</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="sort" style="font-size: 0.8125rem; font-weight: 600; color: var(--text-600);">Sort By</label>
                            <select name="sort" id="sort" onchange="this.form.submit()">
                                <option value="match_score" <?= $sort === 'match_score' ? 'selected' : '' ?>>Highest Match</option>
                                <option value="deadline" <?= $sort === 'deadline' ? 'selected' : '' ?>>Deadline Soonest</option>
                                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
                            </select>
                        </div>
                    </form>
                </div>

                <!-- Recommendations Grid -->
                <?php if (empty($matches)): ?>
                    <div style="text-align: center; padding: 40px; border: 1px dashed var(--border); border-radius: var(--radius-xl); background: #fafafa;">
                        <i data-lucide="info" style="color: var(--text-400); width: 48px; height: 48px; margin-bottom: 12px;"></i>
                        <p style="font-size: 0.9375rem; color: var(--text-600); font-weight: 500;">No recommended scholarships match your criteria.</p>
                        <p style="font-size: 0.8125rem; color: var(--text-500); margin-top: 4px;">Try adding more details to your profile or adjusting filters.</p>
                    </div>
                <?php else: ?>
                    <div class="recommendations-grid">
                        <?php foreach ($matches as $match): 
                            $status = $match['eligibility_status'];
                            $badgeClass = 'status-eligible';
                            $statusLabel = 'Eligible';
                            if ($status === 'NOT_ELIGIBLE') {
                                $badgeClass = 'status-ineligible';
                                $statusLabel = 'Ineligible';
                            } elseif ($status === 'INSUFFICIENT_DATA') {
                                $badgeClass = 'status-insufficient';
                                $statusLabel = 'Missing Data';
                            } elseif ($status === 'POSSIBLY_ELIGIBLE') {
                                $badgeClass = 'status-possibly';
                                $statusLabel = 'Possibly Eligible';
                            }

                            $rec = $match['recommendation_level'];
                            $recClass = 'level-possible';
                            $recLabel = 'Possible Match';
                            if ($rec === 'HIGHLY_RECOMMENDED') {
                                $recClass = 'level-highly-recommended';
                                $recLabel = 'Highly Recommended';
                            } elseif ($rec === 'RECOMMENDED') {
                                $recClass = 'level-recommended';
                                $recLabel = 'Recommended';
                            } elseif ($rec === 'LOW_MATCH') {
                                $recClass = 'level-low';
                                $recLabel = 'Low Match';
                            } elseif ($rec === 'NOT_RECOMMENDED') {
                                $recClass = 'level-not-recommended';
                                $recLabel = 'Not Recommended';
                            }
                        ?>
                            <article class="rec-card" role="article">
                                <div>
                                    <div class="rec-header">
                                        <span class="rec-level-badge <?= $recClass ?>"><?= e($recLabel) ?></span>
                                        <h3 class="rec-title"><?= e($match['title']) ?></h3>
                                        <p class="rec-provider"><?= e($match['provider_name']) ?></p>
                                    </div>

                                    <div class="rec-meta">
                                        <span>
                                            <i data-lucide="globe"></i>
                                            <?= e($match['host_country_name'] ?: 'International') ?>
                                        </span>
                                        <span>
                                            <i data-lucide="dollar-sign"></i>
                                            <?= e($match['funding_type']) ?>
                                        </span>
                                    </div>

                                    <div class="rec-score-box">
                                        <span class="rec-score-pct"><?= e($match['match_score']) ?>% Match</span>
                                        <span class="rec-status-badge <?= $badgeClass ?>"><?= e($statusLabel) ?></span>
                                    </div>

                                    <ul class="rec-criteria-list">
                                        <!-- Matched / Success items -->
                                        <?php 
                                        $bulletsShown = 0;
                                        foreach ($match['matched_criteria'] as $rule => $msg): 
                                            if ($bulletsShown >= 4) break;
                                        ?>
                                            <li class="rec-criteria-item crit-success">
                                                <i data-lucide="check-circle" style="width: 14px; height: 14px;"></i>
                                                <span><?= e($msg) ?></span>
                                            </li>
                                            <?php $bulletsShown++; ?>
                                        <?php endforeach; ?>

                                        <!-- Missing items -->
                                        <?php foreach ($match['missing_criteria'] as $rule => $msg): 
                                            if ($bulletsShown >= 5) break;
                                        ?>
                                            <li class="rec-criteria-item crit-warning">
                                                <i data-lucide="alert-circle" style="width: 14px; height: 14px;"></i>
                                                <span><?= e($msg) ?></span>
                                            </li>
                                            <?php $bulletsShown++; ?>
                                        <?php endforeach; ?>

                                        <!-- Failed items -->
                                        <?php foreach ($match['failed_criteria'] as $rule => $msg): 
                                            if ($bulletsShown >= 5) break;
                                        ?>
                                            <li class="rec-criteria-item crit-danger">
                                                <i data-lucide="x-circle" style="width: 14px; height: 14px;"></i>
                                                <span><?= e($msg) ?></span>
                                            </li>
                                            <?php $bulletsShown++; ?>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>

                                <div class="rec-footer">
                                    <span class="rec-deadline">
                                        Deadline: <?= $match['application_deadline'] ? e(date('d M Y', strtotime($match['application_deadline']))) : 'Rolling' ?>
                                    </span>
                                    <a href="<?= url('/scholarships/' . $match['slug']) ?>" class="btn-view-rec">
                                        <span>View Scholarship</span>
                                        <i data-lucide="arrow-right" style="width: 14px; height: 14px;"></i>
                                    </a>
                                </div>
                            </article>
                        <?php endforeach; ?>
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
