<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($scholarship['title']) ?> | ScholarMatch Opportunities</title>
    <meta name="description" content="<?= e($scholarship['short_description'] ?: substr(strip_tags($scholarship['description']), 0, 160)) ?>">
    <link rel="canonical" href="<?= e(url('/scholarships/' . $scholarship['slug'])) ?>">
    <meta name="robots" content="index, follow">
    
    <!-- Open Graph Protocol -->
    <meta property="og:title" content="<?= e($scholarship['title']) ?> | ScholarMatch">
    <meta property="og:description" content="<?= e($scholarship['short_description'] ?: substr(strip_tags($scholarship['description']), 0, 160)) ?>">
    <meta property="og:url" content="<?= e(url('/scholarships/' . $scholarship['slug'])) ?>">
    <meta property="og:type" content="article">
    
    <!-- Schema.org JSON-LD Structured Data -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Grant",
      "name": <?= json_encode($scholarship['title'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
      "description": <?= json_encode($scholarship['short_description'] ?: substr(strip_tags($scholarship['description']), 0, 200), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
      "sponsor": {
        "@type": "Organization",
        "name": <?= json_encode($scholarship['provider_name'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
      },
      "recipient": {
        "@type": "EducationalAudience",
        "educationalRole": <?= json_encode($scholarship['study_level'] ?? 'All levels', JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
      },
      "amount": {
        "@type": "MonetaryAmount",
        "currency": "USD",
        "description": <?= json_encode($scholarship['funding_type'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
      }
      <?php if (!empty($scholarship['application_deadline'])): ?>,
      "endDate": "<?= date('Y-m-d', strtotime($scholarship['application_deadline'])) ?>"
      <?php endif; ?>
    }
    </script>
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
            max-width: 1100px;
            width: 100%;
            margin: 40px auto;
            padding: 0 20px;
            flex-grow: 1;
        }
        .banner-card {
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius-2xl);
            padding: clamp(24px, 5vw, 40px);
            box-shadow: var(--shadow-sm);
            margin-bottom: 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 24px;
            position: relative;
        }
        .banner-info {
            flex-grow: 1;
            max-width: 700px;
        }
        .banner-provider {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-500);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 8px;
        }
        .banner-title {
            font-size: clamp(1.5rem, 4vw, 2.25rem);
            font-weight: 800;
            color: var(--text-900);
            line-height: 1.25;
            letter-spacing: -0.02em;
            margin-bottom: 16px;
        }
        .meta-tags-container {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }
        .meta-tag {
            padding: 4px 12px;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: var(--radius-full);
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
        }
        .meta-tag-featured {
            background: #fef3c7;
            color: #d97706;
            border-color: #fde68a;
        }
        .meta-tag-verified {
            background: #d1fae5;
            color: #065f46;
            border-color: #a7f3d0;
        }
        .deadline-badge {
            padding: 12px 24px;
            border-radius: var(--radius-xl);
            font-size: 0.875rem;
            font-weight: 700;
            text-align: center;
            min-width: 150px;
            box-shadow: var(--shadow-sm);
        }
        .deadline-badge-open {
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }
        .deadline-badge-closing {
            background: #fffbeb;
            color: #d97706;
            border: 1px solid #fde68a;
        }
        .deadline-badge-passed {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
        }
        .grid-layout {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 32px;
        }
        .card {
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius-2xl);
            padding: clamp(20px, 4vw, 32px);
            box-shadow: var(--shadow-sm);
            margin-bottom: 32px;
        }
        .card-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-900);
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 12px;
        }
        .card-title i {
            color: var(--primary);
        }
        .description-content {
            font-size: 0.975rem;
            line-height: 1.7;
            color: var(--text-700);
        }
        .description-content p {
            margin-bottom: 16px;
        }
        .description-content ul, .description-content ol {
            margin-left: 24px;
            margin-bottom: 16px;
        }
        .description-content li {
            margin-bottom: 8px;
        }
        .list-unstyled {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .list-item-checklist {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 8px 0;
            font-size: 0.875rem;
            color: var(--text-700);
        }
        .list-item-checklist i {
            color: var(--primary);
            flex-shrink: 0;
            margin-top: 2px;
        }
        .apply-box {
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius-2xl);
            padding: 24px;
            box-shadow: var(--shadow-sm);
            text-align: center;
            height: fit-content;
            position: sticky;
            top: 24px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 24px;
            font-size: 0.875rem;
            font-weight: 600;
            border-radius: var(--radius-lg);
            text-decoration: none;
            cursor: pointer;
            border: none;
            width: 100%;
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
        .spec-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            font-size: 0.875rem;
            color: var(--text-700);
            margin-bottom: 20px;
        }
        .spec-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .spec-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-500);
            text-transform: uppercase;
        }
        .spec-value {
            font-weight: 600;
            color: var(--text-900);
        }
        @media (max-width: 992px) {
            .grid-layout {
                grid-template-columns: 1fr;
            }
            .apply-box {
                position: static;
                margin-top: 24px;
            }
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
            margin: 0;
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
                    <a href="<?= url('/documents') ?>" class="nav-link">Documents</a>
                    <a href="<?= url('/applications') ?>" class="nav-link">Applications</a>
                <?php else: ?>
                    <a href="<?= url('/login') ?>" class="nav-link">Log In</a>
                <?php endif; ?>
            </div>
        </header>

        <main class="page-content">
            <!-- Header Banner -->
            <div class="banner-card">
                <div class="banner-info">
                    <div class="banner-provider"><?= e($scholarship['provider_name']) ?></div>
                    <h1 class="banner-title"><?= e($scholarship['title']) ?></h1>
                    
                    <div class="meta-tags-container">
                        <?php if ($scholarship['is_featured']): ?>
                            <span class="meta-tag meta-tag-featured"><i data-lucide="sparkles" style="width:12px; height:12px; display:inline; vertical-align:middle; margin-right:4px;"></i>Featured</span>
                        <?php endif; ?>
                        <?php if ($scholarship['verification_status'] === 'verified'): ?>
                            <span class="meta-tag meta-tag-verified"><i data-lucide="check" style="width:12px; height:12px; display:inline; vertical-align:middle; margin-right:4px;"></i>Verified Opportunity</span>
                        <?php endif; ?>
                        <span class="meta-tag"><?= e($scholarship['funding_type']) ?></span>
                        <span class="meta-tag"><?= e($scholarship['country_name'] ?? 'Multiple Countries') ?></span>
                    </div>
                </div>

                <!-- Deadline Badge -->
                <?php
                $badgeClass = 'deadline-badge-open';
                if ($deadlineStatus === 'Closing Soon') {
                    $badgeClass = 'deadline-badge-closing';
                } elseif ($deadlineStatus === 'Deadline Passed') {
                    $badgeClass = 'deadline-badge-passed';
                }
                ?>
                <div class="deadline-badge <?= $badgeClass ?>">
                    <div style="font-size:0.75rem; text-transform:uppercase; letter-spacing:0.05em; font-weight:600; opacity:0.8;">Status</div>
                    <div style="font-size:1.1rem; margin-top:2px; font-weight:800;"><?= e($deadlineStatus) ?></div>
                </div>
            </div>

            <!-- Detail Grid Layout -->
            <div class="grid-layout">
                <!-- Main detail column -->
                <div class="detail-main">
                    <!-- Description -->
                    <div class="card">
                        <h2 class="card-title">
                            <i data-lucide="file-text"></i>
                            <span>Description</span>
                        </h2>
                        <div class="description-content">
                            <!-- Safe output of sanitized description -->
                            <?= $scholarship['description'] ?>
                        </div>
                    </div>

                    <!-- Eligibility criteria -->
                    <div class="card">
                        <h2 class="card-title">
                            <i data-lucide="user-check"></i>
                            <span>Eligibility Criteria</span>
                        </h2>
                        <ul class="list-unstyled">
                            <?php if (!empty($degrees)): ?>
                                <li class="list-item-checklist">
                                    <i data-lucide="graduation-cap"></i>
                                    <span>Target Study Tiers: <strong><?= e(implode(', ', $degrees)) ?></strong></span>
                                </li>
                            <?php endif; ?>
                            <?php if (!empty($fields)): ?>
                                <li class="list-item-checklist">
                                    <i data-lucide="book-open"></i>
                                    <span>Eligible Disciplines: <strong><?= e(implode(', ', $fields)) ?></strong></span>
                                </li>
                            <?php endif; ?>
                            <?php if (!empty($rules['minimum_cgpa'])): ?>
                                <li class="list-item-checklist">
                                    <i data-lucide="award"></i>
                                    <span>Minimum Academic Score: <strong><?= e($rules['minimum_cgpa']) ?> CGPA (out of <?= e($rules['cgpa_scale'] ?? '4.0') ?>)</strong> or equivalent.</span>
                                </li>
                            <?php endif; ?>
                            <?php if (!empty($rules['minimum_percentage'])): ?>
                                <li class="list-item-checklist">
                                    <i data-lucide="percent"></i>
                                    <span>Minimum Percentage Required: <strong><?= e($rules['minimum_percentage']) ?>%</strong></span>
                                </li>
                            <?php endif; ?>
                            <?php if (!empty($rules['minimum_age']) || !empty($rules['maximum_age'])): ?>
                                <li class="list-item-checklist">
                                    <i data-lucide="calendar-days"></i>
                                    <span>Age Restriction: 
                                        <?php if (!empty($rules['minimum_age']) && !empty($rules['maximum_age'])): ?>
                                            Between <strong><?= e($rules['minimum_age']) ?> and <?= e($rules['maximum_age']) ?> years old.</strong>
                                        <?php elseif (!empty($rules['minimum_age'])): ?>
                                            Must be at least <strong><?= e($rules['minimum_age']) ?> years old.</strong>
                                        <?php else: ?>
                                            Must not exceed <strong><?= e($rules['maximum_age']) ?> years old.</strong>
                                        <?php endif; ?>
                                    </span>
                                </li>
                            <?php endif; ?>
                            <?php if (!empty($nationalities)): ?>
                                <li class="list-item-checklist">
                                    <i data-lucide="globe-2"></i>
                                    <span>Eligible Nationalities: <strong><?= e(implode(', ', $nationalities)) ?></strong></span>
                                </li>
                            <?php else: ?>
                                <li class="list-item-checklist">
                                    <i data-lucide="globe-2"></i>
                                    <span>Eligible Nationalities: <strong>Open to All Nationalities</strong></span>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <!-- Benefits -->
                    <?php if (!empty($benefits)): ?>
                        <div class="card">
                            <h2 class="card-title">
                                <i data-lucide="gift"></i>
                                <span>Scholarship Inclusions & Benefits</span>
                            </h2>
                            <ul class="list-unstyled">
                                <?php foreach ($benefits as $b): ?>
                                    <li class="list-item-checklist">
                                        <i data-lucide="check-circle-2"></i>
                                        <span>
                                            <strong><?= e($b['benefit_type']) ?></strong>: <?= e($b['title']) ?>
                                            <?php if ($b['amount'] !== null): ?>
                                                (<?= e(number_format($b['amount'], 0)) ?> <?= e($b['currency']) ?>)
                                            <?php endif; ?>
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <!-- Language Criteria -->
                    <?php if (!empty($languages)): ?>
                        <div class="card">
                            <h2 class="card-title">
                                <i data-lucide="message-square"></i>
                                <span>Language Test Requirements</span>
                            </h2>
                            <ul class="list-unstyled">
                                <?php foreach ($languages as $l): ?>
                                    <li class="list-item-checklist">
                                        <i data-lucide="clipboard-list"></i>
                                        <span>
                                            <strong><?= e($l['test_name']) ?></strong> (Score: <strong><?= e($l['minimum_score']) ?></strong>) &mdash; 
                                            <?= $l['is_required'] ? '<span style="color:#ef4444; font-weight:600;">Mandatory</span>' : '<span style="color:#64748b;">Optional</span>' ?>
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <!-- Required Documents -->
                    <?php if (!empty($requiredDocs)): ?>
                        <div class="card">
                            <h2 class="card-title">
                                <i data-lucide="files"></i>
                                <span>Application Documents & Readiness</span>
                            </h2>
                            
                            <?php if (isset($docReadiness) && $docReadiness !== null): ?>
                                <div style="margin-bottom: 20px; background: #f8fafc; border: 1px solid var(--border); padding: 16px; border-radius: var(--radius-xl);">
                                    <div style="display: flex; justify-content: space-between; font-size: 0.875rem; font-weight: 600; margin-bottom: 6px;">
                                        <span>Your Document Readiness for this Scholarship</span>
                                        <span><?= e($docReadiness['readiness_percentage']) ?>%</span>
                                    </div>
                                    <div class="progress-bar-container" style="background:#e2e8f0; height:8px; border-radius:4px; overflow:hidden;">
                                        <div class="progress-bar-fill" style="width: <?= e($docReadiness['readiness_percentage']) ?>%; height:100%; background:var(--primary); transition: width 0.3s;"></div>
                                    </div>
                                    <div style="font-size:0.75rem; color:var(--text-500); margin-top:8px;">
                                        <a href="<?= url('/documents') ?>" style="color:var(--primary); text-decoration:none; font-weight:600;">Upload missing documents &rarr;</a>
                                    </div>
                                </div>

                                <ul class="list-unstyled">
                                    <?php foreach ($docReadiness['details'] as $detail): 
                                        $status = $detail['status'];
                                        $statusClass = 'color: #475569;';
                                        $statusLabel = 'Missing';
                                        $icon = 'alert-circle';
                                        
                                        if ($status === 'approved') {
                                            $statusClass = 'color: #059669; font-weight: 600;';
                                            $statusLabel = 'Approved';
                                            $icon = 'check-circle';
                                        } elseif ($status === 'rejected') {
                                            $statusClass = 'color: #dc2626; font-weight: 600;';
                                            $statusLabel = 'Rejected';
                                            $icon = 'x-circle';
                                        } elseif ($status === 'uploaded') {
                                            $statusClass = 'color: #0284c7; font-weight: 600;';
                                            $statusLabel = 'Under Review';
                                            $icon = 'clock';
                                        }
                                    ?>
                                        <li style="display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-bottom: 1px dashed var(--border);">
                                            <span style="display: flex; align-items: center; gap: 8px;">
                                                <i data-lucide="file" style="color:var(--text-400); width:16px; height:16px;"></i>
                                                <span><?= e($detail['name']) ?> <?= $detail['is_required'] ? '<span style="color:#ef4444; font-size:0.75rem; font-weight:500;">(Required)</span>' : '<span style="color:#64748b; font-size:0.75rem;">(Optional)</span>' ?></span>
                                            </span>
                                            <span style="display: flex; align-items: center; gap: 6px; font-size: 0.8125rem; <?= $statusClass ?>">
                                                <i data-lucide="<?= $icon ?>" style="width:14px; height:14px;"></i>
                                                <span><?= $statusLabel ?></span>
                                            </span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <ul class="list-unstyled">
                                    <?php foreach ($requiredDocs as $docName): ?>
                                        <li class="list-item-checklist">
                                            <i data-lucide="file"></i>
                                            <span><?= e($docName) ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                                <p style="font-size:0.8125rem; color:var(--text-500); margin-top:12px;">
                                    <a href="<?= url('/login') ?>" style="color:var(--primary); font-weight:600; text-decoration:none;">Log in</a> to track your document readiness checklist!
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Sidebar Box -->
                <div class="detail-sidebar">
                    <?php if (isset($matchResult) && $matchResult !== null): 
                        $status = $matchResult['eligibility_status'];
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
                    ?>
                        <div class="card" style="padding: 24px; margin-bottom: 24px; border: 1px solid var(--border);">
                            <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-900); margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                                <i data-lucide="award" style="color: var(--primary);"></i>
                                <span>Your Eligibility Match</span>
                            </h3>
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                                <span style="font-size: 1.75rem; font-weight: 800; color: var(--primary);"><?= e($matchResult['match_score']) ?>%</span>
                                <span class="rec-status-badge <?= $badgeClass ?>"><?= e($statusLabel) ?></span>
                            </div>

                            <div style="font-size: 0.8125rem; margin-bottom: 16px; font-weight: 600; color: var(--text-600);">
                                Recommendation: <span style="text-transform: uppercase; color: var(--primary);"><?= e(str_replace('_', ' ', $matchResult['recommendation_level'])) ?></span>
                            </div>

                            <ul class="rec-criteria-list" style="margin-bottom: 0;">
                                <?php foreach ($matchResult['matched_criteria'] as $rule => $msg): ?>
                                    <li class="rec-criteria-item crit-success">
                                        <i data-lucide="check-circle" style="width: 14px; height: 14px; margin-top: 1px;"></i>
                                        <span><?= e($msg) ?></span>
                                    </li>
                                <?php endforeach; ?>

                                <?php foreach ($matchResult['missing_criteria'] as $rule => $msg): ?>
                                    <li class="rec-criteria-item crit-warning">
                                        <i data-lucide="alert-circle" style="width: 14px; height: 14px; margin-top: 1px;"></i>
                                        <span><?= e($msg) ?></span>
                                    </li>
                                <?php endforeach; ?>

                                <?php foreach ($matchResult['failed_criteria'] as $rule => $msg): ?>
                                    <li class="rec-criteria-item crit-danger">
                                        <i data-lucide="x-circle" style="width: 14px; height: 14px; margin-top: 1px;"></i>
                                        <span><?= e($msg) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php elseif (!\App\Services\Auth::isAuthenticated()): ?>
                        <div class="card" style="padding: 24px; margin-bottom: 24px; text-align: center; border: 1px dashed var(--border); background: #fafafa;">
                            <i data-lucide="help-circle" style="color: var(--text-400); width: 36px; height: 36px; margin-bottom: 12px; margin-inline: auto;"></i>
                            <h3 style="font-size: 0.9375rem; font-weight: 700; color: var(--text-900); margin-bottom: 6px;">Want to see your Match?</h3>
                            <p style="font-size: 0.8125rem; color: var(--text-500); margin-bottom: 16px;">Log in or create a profile to calculate your personalized eligibility percentage.</p>
                            <a href="<?= url('/register') ?>" class="btn btn-secondary" style="font-size: 0.8125rem;">
                                <span>Get Started</span>
                                <i data-lucide="arrow-right" style="width: 14px; height: 14px;"></i>
                            </a>
                        </div>
                    <?php endif; ?>

                    <div class="apply-box">
                        <h3 style="font-size:1.1rem; font-weight:700; color:var(--text-900); margin-bottom:16px;">Quick Info</h3>
                        
                        <div class="spec-grid">
                            <div class="spec-item">
                                <span class="spec-label">Host country</span>
                                <span class="spec-value"><?= e($scholarship['country_name'] ?? 'Multiple Countries') ?></span>
                            </div>
                            <div class="spec-item">
                                <span class="spec-label">Funding mode</span>
                                <span class="spec-value"><?= e($scholarship['funding_type']) ?></span>
                            </div>
                            <div class="spec-item" style="grid-column: span 2;">
                                <span class="spec-label">Provider</span>
                                <span class="spec-value"><?= e($scholarship['provider_name']) ?></span>
                            </div>
                            <div class="spec-item" style="grid-column: span 2;">
                                <span class="spec-label">Closing Date</span>
                                <span class="spec-value">
                                    <?= $scholarship['application_deadline'] ? e(date('M d, Y', strtotime($scholarship['application_deadline']))) : 'Open/Rolling' ?>
                                </span>
                            </div>
                        </div>

                        <?php if (!empty($scholarship['official_application_url'])): ?>
                            <a href="<?= e($scholarship['official_application_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary" style="margin-bottom:12px;">
                                <span>Apply on Official Website</span>
                                <i data-lucide="external-link" style="width:16px; height:16px;"></i>
                            </a>
                        <?php elseif (!empty($scholarship['official_website'])): ?>
                            <a href="<?= e($scholarship['official_website']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary" style="margin-bottom:12px;">
                                <span>Visit Official Portal</span>
                                <i data-lucide="external-link" style="width:16px; height:16px;"></i>
                            </a>
                        <?php endif; ?>

                        <?php if (\App\Services\Auth::isAuthenticated() && \App\Services\Auth::currentUser()['role_name'] === 'visitor'): ?>
                            <?php
                                $db = \App\Services\Database::connection();
                                $stmtTrack = $db->prepare("SELECT id FROM scholarship_applications WHERE user_id = :uid AND scholarship_id = :sid LIMIT 1");
                                $stmtTrack->execute(['uid' => \App\Services\Auth::userId(), 'sid' => $scholarship['id']]);
                                $trackingAppId = $stmtTrack->fetchColumn();
                            ?>
                            <?php if ($trackingAppId): ?>
                                <a href="/applications/<?= e($trackingAppId) ?>" class="btn btn-secondary" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none;">
                                    <i data-lucide="folder-check" style="width:16px; height:16px;"></i>
                                    <span>View in Tracker</span>
                                </a>
                            <?php else: ?>
                                <form method="POST" action="/applications" style="width: 100%;">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="scholarship_id" value="<?= e($scholarship['id']) ?>">
                                    <input type="hidden" name="status" value="interested">
                                    <button type="submit" class="btn btn-outline" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px; border: 1px solid var(--border); background: white; color: var(--text-700); padding: 12px; border-radius: var(--radius-md); font-weight: 600; cursor: pointer;">
                                        <i data-lucide="folder-plus" style="width:16px; height:16px;"></i>
                                        <span>Add to Tracker</span>
                                    </button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if (!empty($source['source_name'])): ?>
                            <div style="font-size:0.75rem; color:var(--text-500); margin-top:16px; text-align:left; border-top: 1px solid var(--border); padding-top:16px;">
                                <div style="display:flex; align-items:center; gap:4px; font-weight:600; color:var(--text-700); margin-bottom:4px;">
                                    <i data-lucide="shield-check" style="width:14px; height:14px; color:#059669;"></i>
                                    <span>Verified Source Link</span>
                                </div>
                                <a href="<?= e($source['source_url']) ?>" target="_blank" rel="noopener noreferrer" style="color:var(--primary); text-decoration:none; word-break:break-all;"><?= e($source['source_name']) ?></a>
                                <?php if (!empty($source['last_checked_at'])): ?>
                                    <div style="margin-top:4px;">Last verified: <?= e(date('M d, Y', strtotime($source['last_checked_at']))) ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
