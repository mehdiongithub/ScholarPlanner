<style>
        .page-layout {
            min-height: 100vh;
            background: #f8fafc;
            display: flex;
            flex-direction: column;
        }
        .main-header {
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
        }
        .nav-link:hover {
            color: var(--primary);
        }
        .page-content {
            max-width: 1200px;
            width: 100%;
            margin: 40px auto;
            padding: 0 20px;
            flex-grow: 1;
        }
        .list-layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 32px;
        }
        .filters-aside {
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius-2xl);
            padding: 24px;
            box-shadow: var(--shadow-sm);
            height: fit-content;
        }
        .filter-section-title {
            font-size: 0.875rem;
            font-weight: 700;
            color: var(--text-800);
            text-transform: uppercase;
            margin-bottom: 16px;
            margin-top: 24px;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--border);
        }
        .filter-section-title:first-child {
            margin-top: 0;
        }
        .form-control {
            padding: 10px 14px;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            width: 100%;
            background: var(--bg-white);
            margin-bottom: 12px;
        }
        .results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }
        .scholarship-card {
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius-2xl);
            padding: 24px;
            box-shadow: var(--shadow-sm);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
        }
        .scholarship-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
        }
        .card-provider {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-500);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 8px;
        }
        .card-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--text-900);
            margin-bottom: 12px;
            line-height: 1.4;
        }
        .card-desc {
            font-size: 0.875rem;
            color: var(--text-600);
            margin-bottom: 20px;
            line-height: 1.5;
            flex-grow: 1;
        }
        .card-meta {
            display: flex;
            align-items: center;
            gap: 16px;
            font-size: 0.75rem;
            color: var(--text-500);
            border-top: 1px solid var(--border);
            padding-top: 16px;
            margin-top: auto;
        }
        .card-meta-item {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            font-size: 0.75rem;
            font-weight: 500;
            border-radius: var(--radius-full);
        }
        .badge-verified {
            background: #d1fae5;
            color: #065f46;
            font-size: 0.7rem;
            margin-left: 8px;
        }
        .pagination {
            display: flex;
            align-items: center;
            justify-content: space-between;
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
        .btn-action-compare {
            padding: 4px 8px;
            font-size: 0.75rem;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
<?php
$title = 'My Bookmarked Scholarships';
include ROOT_PATH . '/app/Views/layouts/student_header.php';
?>
<script>
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>

        <main class="page-content">
            <div style="margin-bottom: 24px;">
                <h1 style="font-size: 2rem; font-weight: 700; color: var(--text-900);">My Bookmarked Scholarships</h1>
                <p style="color: var(--text-500);">Opportunities you have saved for tracking and comparison.</p>
            </div>

            <!-- Flash alerts -->
            <?php if (isset($_SESSION['discovery_success'])): ?>
                <div class="alert-success-box" role="alert" style="margin-bottom: 20px; background: #ecfdf5; border: 1px solid #a7f3d0; color: #047857; padding: 12px 16px; border-radius: var(--radius-lg); font-size: 0.875rem;">
                    <?= e($_SESSION['discovery_success']) ?>
                    <?php unset($_SESSION['discovery_success']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['discovery_errors'])): ?>
                <div class="alert-error-box" role="alert" style="margin-bottom: 20px; background: #fef2f2; border: 1px solid #fca5a5; color: #b91c1c; padding: 12px 16px; border-radius: var(--radius-lg); font-size: 0.875rem;">
                    <ul style="margin: 0; padding-left: 20px;">
                        <?php foreach ($_SESSION['discovery_errors'] as $err): ?>
                            <li><?= e($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php unset($_SESSION['discovery_errors']); ?>
                </div>
            <?php endif; ?>

            <!-- Catalog Section -->
            <div class="list-layout">
                <!-- Filters Sidebar -->
                <aside class="filters-aside">
                    <form method="GET" action="<?= url('/saved-scholarships') ?>">
                        <h3 class="filter-section-title" style="margin-top:0;">Search Bookmarks</h3>
                        <input type="text" name="search" class="form-control" placeholder="Search keywords..." value="<?= e($search) ?>" onchange="this.form.submit();">

                        <h3 class="filter-section-title">Host Country</h3>
                        <select name="country_id" class="form-control select2" onchange="this.form.submit();">
                            <option value="">All Countries</option>
                            <?php foreach ($countries as $c): ?>
                                <option value="<?= e($c['id']) ?>" <?= $countryId === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>

                        <h3 class="filter-section-title">Degree Level</h3>
                        <select name="degree" class="form-control select2" onchange="this.form.submit();">
                            <option value="">All Levels</option>
                            <option value="Bachelor's" <?= $degree === "Bachelor's" ? 'selected' : '' ?>>Bachelor's</option>
                            <option value="Master's" <?= $degree === "Master's" ? 'selected' : '' ?>>Master's</option>
                            <option value="PhD" <?= $degree === 'PhD' ? 'selected' : '' ?>>PhD</option>
                            <option value="Diploma" <?= $degree === 'Diploma' ? 'selected' : '' ?>>Diploma</option>
                        </select>

                        <h3 class="filter-section-title">Funding Mode</h3>
                        <select name="funding_type" class="form-control select2" onchange="this.form.submit();">
                            <option value="">All Types</option>
                            <option value="Fully Funded" <?= $funding === 'Fully Funded' ? 'selected' : '' ?>>Fully Funded</option>
                            <option value="Partially Funded" <?= $funding === 'Partially Funded' ? 'selected' : '' ?>>Partially Funded</option>
                            <option value="Tuition Waiver" <?= $funding === 'Tuition Waiver' ? 'selected' : '' ?>>Tuition Waiver</option>
                        </select>
                    </form>
                </aside>

                <!-- Cards Catalog -->
                <div class="results-column">
                    <div style="margin-bottom:20px; font-size:0.875rem; color:var(--text-600); display:flex; align-items:center; justify-content:space-between;">
                        <span>Found <strong><?= e($totalCount) ?></strong> bookmarked scholarships</span>
                        
                        <form method="GET" action="<?= url('/saved-scholarships') ?>" style="display:flex; align-items:center; gap:8px;">
                            <input type="hidden" name="search" value="<?= e($search) ?>">
                            <input type="hidden" name="country_id" value="<?= e($countryId ?? '') ?>">
                            <input type="hidden" name="degree" value="<?= e($degree) ?>">
                            <input type="hidden" name="funding_type" value="<?= e($funding) ?>">
                            
                            <select name="sort" class="form-control" style="margin-bottom:0; padding:6px 12px; font-size:0.75rem;" onchange="this.form.submit();">
                                <option value="saved_at" <?= $sort === 'saved_at' ? 'selected' : '' ?>>Date Bookmarked</option>
                                <option value="title" <?= $sort === 'title' ? 'selected' : '' ?>>Alphabetical (Title)</option>
                                <option value="application_deadline" <?= $sort === 'application_deadline' ? 'selected' : '' ?>>Closing Date</option>
                            </select>
                        </form>
                    </div>

                    <div class="results-grid">
                        <?php if (empty($scholarships)): ?>
                            <div style="grid-column: span 3; text-align:center; padding:64px 20px; color:var(--text-500); background:var(--bg-white); border-radius:var(--radius-xl); border:1px solid var(--border);">
                                <i data-lucide="bookmark" style="width:48px; height:48px; margin: 0 auto 16px; color:var(--text-400);"></i>
                                <h3 style="font-weight:600; color:var(--text-800); margin-bottom:8px;">No Bookmarks Saved</h3>
                                <p style="font-size:0.875rem;">Your bookmarked scholarships will show up here.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($scholarships as $s): ?>
                                <?php 
                                    $sid = (int)$s['id'];
                                    $isSaved = true;
                                    $hasApplied = isset($appliedStates[$sid]);
                                    $appStatus = $hasApplied ? $appliedStates[$sid]['status'] : 'not_applied';
                                    $matchScore = isset($matches[$sid]) ? $matches[$sid]['match_score'] : null;
                                    $matchStatus = isset($matches[$sid]) ? $matches[$sid]['match_status'] : 'new';
                                    $readiness = isset($readinessStates[$sid]) ? $readinessStates[$sid]['readiness_percentage'] : 0;
                                ?>
                                <div class="scholarship-card" style="border-top: 4px solid var(--primary);">
                                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:8px;">
                                        <div class="card-provider"><?= e($s['provider_name']) ?></div>
                                        <div style="display:flex; gap:6px;">
                                            <!-- Bookmark action button -->
                                            <form method="POST" action="<?= url('/scholarships/' . $sid . '/unsave') ?>" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                                                <button type="submit" style="background:none; border:none; color:#ef4444; cursor:pointer;" title="Remove Bookmark">
                                                    <i data-lucide="bookmark" style="width:18px; height:18px; fill:#ef4444;"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>

                                    <div>
                                        <h2 class="card-title">
                                            <a href="<?= url('/scholarships/' . $s['slug']) ?>" style="text-decoration:none; color:inherit;"><?= e($s['title']) ?></a>
                                            <?php if ($s['verification_status'] === 'verified'): ?>
                                                <span class="badge badge-verified" title="Verified source"><i data-lucide="check" style="width:10px; height:10px; margin-right:2px;"></i>Verified</span>
                                            <?php endif; ?>
                                        </h2>
                                        
                                        <!-- Eligibility match score indicator -->
                                        <?php if ($matchScore !== null): ?>
                                            <div style="font-size:0.8125rem; font-weight:600; color: #0d9488; margin-bottom:12px; display:inline-flex; align-items:center; gap:4px; background:#f0fdfa; padding:4px 8px; border-radius:4px;">
                                                <i data-lucide="award" style="width:14px; height:14px;"></i>
                                                <span><?= $matchScore ?>% Match (<?= str_replace('_', ' ', $matchStatus) ?>)</span>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Document readiness indicator -->
                                        <div style="font-size:0.75rem; color:var(--text-600); margin-bottom:12px;">
                                            <strong>Doc Readiness:</strong> <span><?= $readiness ?>%</span>
                                        </div>

                                        <p class="card-desc" style="margin-bottom:12px;"><?= e($s['short_description'] ?: substr(strip_tags($s['description']), 0, 140) . '...') ?></p>
                                    </div>

                                    <!-- Application Integration Actions -->
                                    <div style="margin-top:12px; margin-bottom:16px; padding:10px; background:#f8fafc; border: 1px solid var(--border); border-radius:var(--radius-md);">
                                        <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.8125rem; font-weight:500;">
                                            <span style="color:var(--text-600);">Application:</span>
                                            <?php if ($hasApplied): ?>
                                                <span class="badge-status status-<?= $appStatus ?>" style="font-size:0.75rem; padding:2px 8px; font-weight:600; border-radius:4px;"><?= str_replace('_', ' ', $appStatus) ?></span>
                                            <?php else: ?>
                                                <span style="color:var(--text-400); font-style:italic;">Not Tracked</span>
                                            <?php endif; ?>
                                        </div>

                                        <div style="display:flex; gap:8px; margin-top:8px;">
                                            <?php if ($hasApplied): ?>
                                                <a href="<?= url('/applications/' . $appliedStates[$sid]['id']) ?>" class="btn btn-secondary btn-action-compare" style="flex-grow:1; text-align:center;">View Application</a>
                                            <?php else: ?>
                                                <a href="<?= url('/scholarships/' . $s['slug']) ?>" class="btn btn-primary btn-action-compare" style="flex-grow:1; text-align:center; background: var(--primary);">Track App</a>
                                            <?php endif; ?>

                                            <?php if (!empty($s['official_application_url'])): ?>
                                                <a href="<?= e($s['official_application_url']) ?>" target="_blank" rel="noopener" class="btn btn-secondary btn-action-compare" title="Official Website">
                                                    <i data-lucide="external-link" style="width:12px; height:12px;"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="card-meta">
                                        <div class="card-meta-item">
                                            <i data-lucide="map-pin" style="width:12px; height:12px;"></i>
                                            <span><?= e($s['country_name'] ?? 'Multiple Countries') ?></span>
                                        </div>
                                        <div class="card-meta-item" style="margin-left:auto; color: <?= !empty($s['application_deadline']) && strtotime($s['application_deadline']) < time() ? '#ef4444' : 'inherit' ?>;">
                                            <i data-lucide="calendar" style="width:12px; height:12px;"></i>
                                            <span>
                                                <?php if (empty($s['application_deadline'])): ?>
                                                    Open
                                                <?php elseif (strtotime($s['application_deadline']) < time()): ?>
                                                    Expired
                                                <?php else: ?>
                                                    Closes <?= e(date('M d, Y', strtotime($s['application_deadline']))) ?>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Pagination links -->
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination">
                            <span style="font-size:0.875rem; color:var(--text-600);">Page <?= e($page) ?> of <?= e($totalPages) ?></span>
                            <div class="pagination-links">
                                <a href="?page=<?= e($page - 1) ?>&search=<?= e($search) ?>&country_id=<?= e($countryId ?? '') ?>&degree=<?= e($degree) ?>&funding_type=<?= e($funding) ?>&sort=<?= e($sort) ?>" class="pagination-btn <?= $page <= 1 ? 'disabled' : '' ?>">Previous</a>
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <a href="?page=<?= e($i) ?>&search=<?= e($search) ?>&country_id=<?= e($countryId ?? '') ?>&degree=<?= e($degree) ?>&funding_type=<?= e($funding) ?>&sort=<?= e($sort) ?>" class="pagination-btn <?= $page === $i ? 'active' : '' ?>"><?= e($i) ?></a>
                                <?php endfor; ?>
                                <a href="?page=<?= e($page + 1) ?>&search=<?= e($search) ?>&country_id=<?= e($countryId ?? '') ?>&degree=<?= e($degree) ?>&funding_type=<?= e($funding) ?>&sort=<?= e($sort) ?>" class="pagination-btn <?= $page >= $totalPages ? 'disabled' : '' ?>">Next</a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

<?php include ROOT_PATH . '/app/Views/layouts/student_footer.php'; ?>
