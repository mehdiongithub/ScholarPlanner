<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Search Scholarships | ScholarMatch') ?></title>
    <meta name="description" content="<?= e($metaDescription ?? 'Search over verified opportunities matched to your qualifications.') ?>">
    <link rel="canonical" href="<?= e($canonicalUrl ?? url('/scholarships')) ?>">
    <meta name="robots" content="<?= e($robotsDirective ?? 'index, follow') ?>">
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
                    <a href="<?= url('/saved-scholarships') ?>" class="nav-link">Bookmarks</a>
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

            <!-- Comparison Bar Indicator -->
            <?php if (!empty($_SESSION['compare_ids'])): ?>
                <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:12px; padding:16px; margin-bottom:24px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <i data-lucide="columns" style="color:#1d4ed8; width:20px; height:20px;"></i>
                        <span style="font-weight:600; color:#1e3a8a;">You have <?= count($_SESSION['compare_ids']) ?> scholarship(s) selected for comparison.</span>
                    </div>
                    <a href="<?= url('/scholarships/compare') ?>" class="btn btn-primary" style="background:#2563eb;">Compare Now</a>
                </div>
            <?php endif; ?>

            <!-- Main Catalog Section -->
            <div class="list-layout">
                <!-- Filters Sidebar -->
                <aside class="filters-aside">
                    <form method="GET" action="<?= url('/scholarships') ?>">
                        <input type="hidden" name="search" value="<?= e($search) ?>">

                        <h3 class="filter-section-title" style="margin-top:0;">Host Country</h3>
                        <select name="country_id" class="form-control" onchange="this.form.submit();">
                            <option value="">All Host Countries</option>
                            <?php foreach ($countries as $c): ?>
                                <option value="<?= e($c['id']) ?>" <?= $countryId === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>

                        <h3 class="filter-section-title">Study Destination</h3>
                        <select name="study_destination_id" class="form-control" onchange="this.form.submit();">
                            <option value="">All Destinations</option>
                            <?php foreach ($countries as $c): ?>
                                <option value="<?= e($c['id']) ?>" <?= ($studyDestinationId ?? null) === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
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

                        <h3 class="filter-section-title">Deadline Status</h3>
                        <select name="deadline_status" class="form-control" onchange="this.form.submit();">
                            <option value="">All Deadlines</option>
                            <option value="open" <?= ($deadlineStatus ?? '') === 'open' ? 'selected' : '' ?>>Open</option>
                            <option value="closing_soon" <?= ($deadlineStatus ?? '') === 'closing_soon' ? 'selected' : '' ?>>Closing Soon</option>
                            <option value="rolling" <?= ($deadlineStatus ?? '') === 'rolling' ? 'selected' : '' ?>>Rolling / No Deadline</option>
                        </select>

                        <h3 class="filter-section-title">Nationality Eligibility</h3>
                        <input type="text" name="nationality" class="form-control" placeholder="E.g. Pakistan, India..." value="<?= e($nationality) ?>" onchange="this.form.submit();">

                        <h3 class="filter-section-title">Status Check</h3>
                        <div style="display:flex; flex-direction:column; gap:12px; margin-top:8px;">
                            <label class="checkbox-label">
                                <input type="checkbox" name="fully_funded" value="1" <?= ($fullyFunded ?? '') === '1' ? 'checked' : '' ?> onchange="this.form.submit();">
                                <span>Fully Funded Only</span>
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" name="verified" value="1" <?= $verified === '1' ? 'checked' : '' ?> onchange="this.form.submit();">
                                <span>Verified Sources</span>
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" name="featured" value="1" <?= $featured === '1' ? 'checked' : '' ?> onchange="this.form.submit();">
                                <span>Featured Listings</span>
                            </label>
                        </div>

                        <div style="margin-top: 24px;">
                            <a href="<?= url('/scholarships') ?>" class="btn btn-secondary" style="width: 100%; text-align: center; text-decoration: none; display: block; font-size: 0.875rem; padding: 8px 12px; border-radius: var(--radius-md); box-sizing: border-box;">Clear All Filters</a>
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
                            <input type="hidden" name="country_id" value="<?= e($countryId ?? '') ?>">
                            <input type="hidden" name="study_destination_id" value="<?= e($studyDestinationId ?? '') ?>">
                            <input type="hidden" name="degree" value="<?= e($degree) ?>">
                            <input type="hidden" name="field_id" value="<?= e($fieldId ?? '') ?>">
                            <input type="hidden" name="funding_type" value="<?= e($funding) ?>">
                            <input type="hidden" name="deadline_status" value="<?= e($deadlineStatus ?? '') ?>">
                            <input type="hidden" name="fully_funded" value="<?= e($fullyFunded ?? '') ?>">
                            <input type="hidden" name="verified" value="<?= e($verified) ?>">
                            <input type="hidden" name="featured" value="<?= e($featured) ?>">
                            <input type="hidden" name="nationality" value="<?= e($nationality) ?>">
                            
                            <select name="sort" class="form-control" style="margin-bottom:0; padding:6px 12px; font-size:0.75rem;" onchange="this.form.submit();">
                                <option value="published_at" <?= $sort === 'published_at' ? 'selected' : '' ?>>Recently Published</option>
                                <?php if (\App\Services\Auth::isAuthenticated()): ?>
                                    <option value="match" <?= $sort === 'match' ? 'selected' : '' ?>>Best Match</option>
                                <?php endif; ?>
                                <option value="deadline_soon" <?= $sort === 'deadline_soon' ? 'selected' : '' ?>>Deadline Soon</option>
                                <option value="featured" <?= $sort === 'featured' ? 'selected' : '' ?>>Featured First</option>
                                <option value="fully_funded" <?= $sort === 'fully_funded' ? 'selected' : '' ?>>Fully Funded First</option>
                                <option value="title" <?= $sort === 'title' ? 'selected' : '' ?>>Alphabetical (Title)</option>
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
                                <?php 
                                    $sid = (int)$s['id'];
                                    $isSaved = in_array($sid, $savedIds ?? []);
                                    $hasApplied = isset($appliedStates[$sid]);
                                    $appStatus = $hasApplied ? $appliedStates[$sid]['status'] : 'not_applied';
                                    $matchScore = isset($matches[$sid]) ? $matches[$sid]['match_score'] : (isset($s['match_score']) ? (int)$s['match_score'] : null);
                                    $matchStatus = isset($matches[$sid]) ? $matches[$sid]['match_status'] : 'new';
                                    $readiness = isset($readinessStates[$sid]) ? $readinessStates[$sid]['readiness_percentage'] : 0;
                                ?>
                                <div class="scholarship-card" style="border-top: 4px solid <?= $s['is_featured'] ? '#f59e0b' : '#3b82f6' ?>;">
                                    <?php if ($s['is_featured']): ?>
                                        <div class="featured-badge">
                                            <i data-lucide="sparkles" style="width:12px; height:12px;"></i>
                                            <span>Featured</span>
                                        </div>
                                    <?php endif; ?>

                                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:8px;">
                                        <div class="card-provider"><?= e($s['provider_name']) ?></div>
                                        
                                        <?php if (\App\Services\Auth::isAuthenticated()): ?>
                                            <div style="display:flex; gap:8px; align-items:center;">
                                                <!-- Bookmark Action -->
                                                <?php if ($isSaved): ?>
                                                    <form method="POST" action="<?= url('/scholarships/' . $sid . '/unsave') ?>" style="display:inline;">
                                                        <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                                                        <button type="submit" style="background:none; border:none; color:#ef4444; cursor:pointer;" title="Remove Bookmark">
                                                            <i data-lucide="bookmark" style="width:18px; height:18px; fill:#ef4444;"></i>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="POST" action="<?= url('/scholarships/' . $sid . '/save') ?>" style="display:inline;">
                                                        <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                                                        <button type="submit" style="background:none; border:none; color:#94a3b8; cursor:pointer;" title="Save Bookmark">
                                                            <i data-lucide="bookmark" style="width:18px; height:18px;"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>

                                                <!-- Compare Action -->
                                                <?php 
                                                $compareIds = $_SESSION['compare_ids'] ?? [];
                                                $inCompare = in_array($sid, $compareIds);
                                                ?>
                                                <?php if ($inCompare): ?>
                                                    <form method="POST" action="<?= url('/scholarships/' . $sid . '/compare/remove') ?>" style="display:inline;">
                                                        <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                                                        <button type="submit" style="background:none; border:none; color:#2563eb; cursor:pointer;" title="Remove from Compare">
                                                            <i data-lucide="columns" style="width:18px; height:18px; fill:#2563eb;"></i>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="POST" action="<?= url('/scholarships/' . $sid . '/compare/add') ?>" style="display:inline;">
                                                        <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                                                        <button type="submit" style="background:none; border:none; color:#94a3b8; cursor:pointer;" title="Add to Compare">
                                                            <i data-lucide="columns" style="width:18px; height:18px;"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div>
                                        <h2 class="card-title">
                                            <a href="<?= url('/scholarships/' . $s['slug']) ?>" style="text-decoration:none; color:inherit;"><?= e($s['title']) ?></a>
                                            <?php if ($s['verification_status'] === 'verified'): ?>
                                                <span class="badge badge-verified" title="Verified source"><i data-lucide="check" style="width:10px; height:10px; margin-right:2px;"></i>Verified</span>
                                            <?php endif; ?>
                                        </h2>

                                        <!-- Match Score / Readiness Badges -->
                                        <?php if (\App\Services\Auth::isAuthenticated() && \App\Services\Auth::currentUser()['role_name'] === 'visitor'): ?>
                                            <div style="display:flex; flex-wrap:wrap; gap:8px; margin-bottom:12px;">
                                                <?php if ($matchScore !== null): ?>
                                                    <div style="font-size:0.75rem; font-weight:600; color: #0d9488; display:inline-flex; align-items:center; gap:4px; background:#f0fdfa; padding:4px 8px; border-radius:4px;">
                                                        <i data-lucide="award" style="width:14px; height:14px;"></i>
                                                        <span><?= $matchScore ?>% Match</span>
                                                    </div>
                                                <?php endif; ?>
                                                <div style="font-size:0.75rem; font-weight:600; color: #2563eb; display:inline-flex; align-items:center; gap:4px; background:#eff6ff; padding:4px 8px; border-radius:4px;">
                                                    <i data-lucide="file-text" style="width:14px; height:14px;"></i>
                                                    <span><?= $readiness ?>% Docs Ready</span>
                                                </div>
                                            </div>

                                            <!-- Warnings for missing docs or closing soon -->
                                            <?php 
                                                $missingCount = isset($readinessStates[$sid]) ? $readinessStates[$sid]['missing_count'] : 0;
                                                $deadlineTime = !empty($s['application_deadline']) ? strtotime($s['application_deadline']) : null;
                                                $isClosingSoon = $deadlineTime && ($deadlineTime - time() <= 7 * 86400) && ($deadlineTime > time());
                                            ?>
                                            <?php if ($missingCount > 0 || $isClosingSoon): ?>
                                                <div style="display:flex; flex-direction:column; gap:4px; margin-bottom:12px;">
                                                    <?php if ($missingCount > 0): ?>
                                                        <div style="font-size:0.7rem; color:#b91c1c; display:flex; align-items:center; gap:4px; font-weight:500;">
                                                            <i data-lucide="alert-triangle" style="width:12px; height:12px; color:#ef4444;"></i>
                                                            <span>Missing <?= $missingCount ?> required document(s)</span>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if ($isClosingSoon): ?>
                                                        <div style="font-size:0.7rem; color:#d97706; display:flex; align-items:center; gap:4px; font-weight:500;">
                                                            <i data-lucide="clock" style="width:12px; height:12px; color:#f59e0b;"></i>
                                                            <span>Closing Soon! Approaching Deadline</span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>

                                        <p class="card-desc"><?= e($s['short_description'] ?: substr(strip_tags($s['description']), 0, 160) . '...') ?></p>
                                    </div>

                                    <!-- Application Integration Actions -->
                                    <?php if (\App\Services\Auth::isAuthenticated() && \App\Services\Auth::currentUser()['role_name'] === 'visitor'): ?>
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
                                                    <a href="<?= url('/applications/' . $appliedStates[$sid]['id']) ?>" class="btn btn-secondary btn-action-compare" style="flex-grow:1; text-align:center; font-size:0.75rem;">View App</a>
                                                <?php else: ?>
                                                    <a href="<?= url('/scholarships/' . $s['slug']) ?>" class="btn btn-primary btn-action-compare" style="flex-grow:1; text-align:center; font-size:0.75rem; background: var(--primary);">Track App</a>
                                                <?php endif; ?>

                                                <?php if (!empty($s['official_application_url'])): ?>
                                                    <a href="<?= e($s['official_application_url']) ?>" target="_blank" rel="noopener" class="btn btn-secondary btn-action-compare" title="Official Website">
                                                        <i data-lucide="external-link" style="width:12px; height:12px;"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

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
                                <a href="?page=<?= e($page - 1) ?>&search=<?= e($search) ?>&country_id=<?= e($countryId ?? '') ?>&degree=<?= e($degree) ?>&funding_type=<?= e($funding) ?>&field_id=<?= e($fieldId ?? '') ?>&verified=<?= e($verified) ?>&featured=<?= e($featured) ?>&nationality=<?= e($nationality) ?>&sort=<?= e($sort) ?>" class="pagination-btn <?= $page <= 1 ? 'disabled' : '' ?>">Previous</a>
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <a href="?page=<?= e($i) ?>&search=<?= e($search) ?>&country_id=<?= e($countryId ?? '') ?>&degree=<?= e($degree) ?>&funding_type=<?= e($funding) ?>&field_id=<?= e($fieldId ?? '') ?>&verified=<?= e($verified) ?>&featured=<?= e($featured) ?>&nationality=<?= e($nationality) ?>&sort=<?= e($sort) ?>" class="pagination-btn <?= $page === $i ? 'active' : '' ?>"><?= e($i) ?></a>
                                <?php endfor; ?>
                                <a href="?page=<?= e($page + 1) ?>&search=<?= e($search) ?>&country_id=<?= e($countryId ?? '') ?>&degree=<?= e($degree) ?>&funding_type=<?= e($funding) ?>&field_id=<?= e($fieldId ?? '') ?>&verified=<?= e($verified) ?>&featured=<?= e($featured) ?>&nationality=<?= e($nationality) ?>&sort=<?= e($sort) ?>" class="pagination-btn <?= $page >= $totalPages ? 'disabled' : '' ?>">Next</a>
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
