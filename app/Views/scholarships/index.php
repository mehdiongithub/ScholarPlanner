<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Scholarships | ScholarMatch</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">
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
        .search-hero {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            border-radius: var(--radius-2xl);
            padding: 48px;
            color: var(--bg-white);
            margin-bottom: 32px;
            box-shadow: var(--shadow-md);
        }
        .hero-title {
            font-size: 2.25rem;
            font-weight: 800;
            margin-bottom: 12px;
            letter-spacing: -0.025em;
        }
        .hero-subtitle {
            font-size: 1.125rem;
            color: #dbeafe;
            margin-bottom: 32px;
        }
        .search-bar-wrapper {
            background: var(--bg-white);
            border-radius: var(--radius-xl);
            padding: 8px;
            display: flex;
            align-items: center;
            box-shadow: var(--shadow-lg);
            max-width: 800px;
            gap: 8px;
            flex-wrap: wrap;
        }
        .search-input-box {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-grow: 1;
            padding: 8px 12px;
            min-width: 200px;
        }
        .search-input-box i {
            color: var(--text-400);
        }
        .search-input-box input {
            border: none;
            outline: none;
            font-size: 1rem;
            width: 100%;
            color: var(--text-800);
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
            background: var(--primary-dark);
        }
        .btn-secondary {
            background: var(--bg-white);
            color: var(--text-700);
            border: 1px solid var(--border);
        }
        .btn-secondary:hover {
            background: #f1f5f9;
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
        .filter-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: var(--text-700);
            text-decoration: none;
            font-size: 0.875rem;
            padding: 6px 0;
            transition: color 0.2s;
        }
        .filter-link:hover, .filter-link.active {
            color: var(--primary);
            font-weight: 600;
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
        .featured-badge {
            position: absolute;
            top: 16px;
            right: 16px;
            background: #fef3c7;
            color: #d97706;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: var(--radius-full);
            display: flex;
            align-items: center;
            gap: 4px;
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
        @media (max-width: 992px) {
            .list-layout {
                grid-template-columns: 1fr;
            }
            .search-hero {
                padding: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="page-layout">
        <header class="main-header" role="banner">
            <a href="/" class="logo-box">
                <i data-lucide="graduation-cap"></i>
                <span>ScholarMatch</span>
            </a>
            <div class="nav-links">
                <a href="<?= url('/scholarships') ?>" class="nav-link">Search Scholarships</a>
                <?php if (\App\Services\Auth::isAuthenticated()): ?>
                    <a href="<?= url('/dashboard') ?>" class="nav-link">Dashboard</a>
                <?php else: ?>
                    <a href="<?= url('/login') ?>" class="nav-link">Log In</a>
                    <a href="<?= url('/register') ?>" class="btn btn-primary" style="padding: 6px 16px;">Sign Up</a>
                <?php endif; ?>
            </div>
        </header>

        <main class="page-content">
            <!-- Hero banner section -->
            <div class="search-hero">
                <h1 class="hero-title">Find Your Perfect Scholarship</h1>
                <p class="hero-subtitle">Search over verified opportunities matched to your qualifications and preferences.</p>
                
                <form method="GET" action="<?= url('/scholarships') ?>">
                    <div class="search-bar-wrapper">
                        <div class="search-input-box">
                            <i data-lucide="search"></i>
                            <input type="text" name="search" placeholder="Search by title, provider, university..." value="<?= e($search) ?>">
                        </div>
                        <button type="submit" class="btn btn-primary">Search</button>
                    </div>
                </form>
            </div>

            <!-- Main Catalog Section -->
            <div class="list-layout">
                <!-- Filters Sidebar -->
                <aside class="filters-aside">
                    <form method="GET" action="<?= url('/scholarships') ?>">
                        <input type="hidden" name="search" value="<?= e($search) ?>">

                        <h3 class="filter-section-title" style="margin-top:0;">Host Country</h3>
                        <select name="country_id" class="form-control" onchange="this.form.submit();">
                            <option value="">All Countries</option>
                            <?php foreach ($countries as $c): ?>
                                <option value="<?= e($c['id']) ?>" <?= $countryId === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>

                        <h3 class="filter-section-title">Degree Level</h3>
                        <select name="degree" class="form-control" onchange="this.form.submit();">
                            <option value="">All Levels</option>
                            <option value="Bachelor's" <?= $degree === "Bachelor's" ? 'selected' : '' ?>>Bachelor's</option>
                            <option value="Master's" <?= $degree === "Master's" ? 'selected' : '' ?>>Master's</option>
                            <option value="PhD" <?= $degree === 'PhD' ? 'selected' : '' ?>>PhD</option>
                            <option value="Diploma" <?= $degree === 'Diploma' ? 'selected' : '' ?>>Diploma</option>
                        </select>

                        <h3 class="filter-section-title">Discipline / Field</h3>
                        <select name="field_id" class="form-control" onchange="this.form.submit();">
                            <option value="">All Disciplines</option>
                            <?php foreach ($fields as $f): ?>
                                <option value="<?= e($f['id']) ?>" <?= $fieldId === (int)$f['id'] ? 'selected' : '' ?>><?= e($f['name']) ?></option>
                            <?php endforeach; ?>
                        </select>

                        <h3 class="filter-section-title">Funding Mode</h3>
                        <select name="funding_type" class="form-control" onchange="this.form.submit();">
                            <option value="">All Types</option>
                            <option value="Fully Funded" <?= $funding === 'Fully Funded' ? 'selected' : '' ?>>Fully Funded</option>
                            <option value="Partially Funded" <?= $funding === 'Partially Funded' ? 'selected' : '' ?>>Partially Funded</option>
                            <option value="Tuition Waiver" <?= $funding === 'Tuition Waiver' ? 'selected' : '' ?>>Tuition Waiver</option>
                        </select>

                        <h3 class="filter-section-title">Status Check</h3>
                        <div style="display:flex; flex-direction:column; gap:12px; margin-top:8px;">
                            <label class="checkbox-label">
                                <input type="checkbox" name="verified" value="1" <?= $verified === '1' ? 'checked' : '' ?> onchange="this.form.submit();">
                                <span>Verified Sources</span>
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" name="featured" value="1" <?= $featured === '1' ? 'checked' : '' ?> onchange="this.form.submit();">
                                <span>Featured Listings</span>
                            </label>
                        </div>
                    </form>
                </aside>

                <!-- Cards catalog -->
                <div class="results-column">
                    <div style="margin-bottom:20px; font-size:0.875rem; color:var(--text-600); display:flex; align-items:center; justify-content:space-between;">
                        <span>Found <strong><?= e($totalCount) ?></strong> scholarships</span>
                        
                        <!-- Sort choices -->
                        <form method="GET" action="<?= url('/scholarships') ?>" style="display:flex; align-items:center; gap:8px;">
                            <input type="hidden" name="search" value="<?= e($search) ?>">
                            <input type="hidden" name="country_id" value="<?= e($countryId) ?>">
                            <input type="hidden" name="degree" value="<?= e($degree) ?>">
                            <input type="hidden" name="field_id" value="<?= e($fieldId) ?>">
                            <input type="hidden" name="funding_type" value="<?= e($funding) ?>">
                            
                            <select name="sort" class="form-control" style="margin-bottom:0; padding:6px 12px; font-size:0.75rem;" onchange="this.form.submit();">
                                <option value="published_at" <?= $sort === 'published_at' ? 'selected' : '' ?>>Recently Published</option>
                                <option value="title" <?= $sort === 'title' ? 'selected' : '' ?>>Alphabetical (Title)</option>
                                <option value="application_deadline" <?= $sort === 'application_deadline' ? 'selected' : '' ?>>Closing Date</option>
                            </select>
                        </form>
                    </div>

                    <div class="results-grid">
                        <?php if (empty($scholarships)): ?>
                            <div style="grid-column: span 3; text-align:center; padding:64px 20px; color:var(--text-500); background:var(--bg-white); border-radius:var(--radius-xl); border:1px solid var(--border);">
                                <i data-lucide="info" style="width:48px; height:48px; margin: 0 auto 16px; color:var(--text-400);"></i>
                                <h3 style="font-weight:600; color:var(--text-800); margin-bottom:8px;">No Scholarships Found</h3>
                                <p style="font-size:0.875rem;">Try modifying your search tags or filters.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($scholarships as $s): ?>
                                <div class="scholarship-card">
                                    <?php if ($s['is_featured']): ?>
                                        <div class="featured-badge">
                                            <i data-lucide="sparkles" style="width:12px; height:12px;"></i>
                                            <span>Featured</span>
                                        </div>
                                    <?php endif; ?>

                                    <div>
                                        <div class="card-provider"><?= e($s['provider_name']) ?></div>
                                        <h2 class="card-title">
                                            <a href="<?= url('/scholarships/' . $s['slug']) ?>" style="text-decoration:none; color:inherit;"><?= e($s['title']) ?></a>
                                            <?php if ($s['verification_status'] === 'verified'): ?>
                                                <span class="badge badge-verified" title="Verified source"><i data-lucide="check" style="width:10px; height:10px; margin-right:2px;"></i>Verified</span>
                                            <?php endif; ?>
                                        </h2>
                                        <p class="card-desc"><?= e($s['short_description'] ?: substr(strip_tags($s['description']), 0, 160) . '...') ?></p>
                                    </div>

                                    <div class="card-meta">
                                        <div class="card-meta-item">
                                            <i data-lucide="map-pin" style="width:12px; height:12px;"></i>
                                            <span><?= e($s['country_name'] ?? 'Multiple Countries') ?></span>
                                        </div>
                                        <div class="card-meta-item">
                                            <i data-lucide="banknote" style="width:12px; height:12px;"></i>
                                            <span><?= e($s['funding_type']) ?></span>
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
                                <a href="?page=<?= e($page - 1) ?>&search=<?= e($search) ?>&country_id=<?= e($countryId) ?>&degree=<?= e($degree) ?>&funding_type=<?= e($funding) ?>&field_id=<?= e($fieldId) ?>&verified=<?= e($verified) ?>&featured=<?= e($featured) ?>&sort=<?= e($sort) ?>" class="pagination-btn <?= $page <= 1 ? 'disabled' : '' ?>">Previous</a>
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <a href="?page=<?= e($i) ?>&search=<?= e($search) ?>&country_id=<?= e($countryId) ?>&degree=<?= e($degree) ?>&funding_type=<?= e($funding) ?>&field_id=<?= e($fieldId) ?>&verified=<?= e($verified) ?>&featured=<?= e($featured) ?>&sort=<?= e($sort) ?>" class="pagination-btn <?= $page === $i ? 'active' : '' ?>"><?= e($i) ?></a>
                                <?php endfor; ?>
                                <a href="?page=<?= e($page + 1) ?>&search=<?= e($search) ?>&country_id=<?= e($countryId) ?>&degree=<?= e($degree) ?>&funding_type=<?= e($funding) ?>&field_id=<?= e($fieldId) ?>&verified=<?= e($verified) ?>&featured=<?= e($featured) ?>&sort=<?= e($sort) ?>" class="pagination-btn <?= $page >= $totalPages ? 'disabled' : '' ?>">Next</a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
