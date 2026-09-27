<?php
$title = 'My Scholarship Matches';
include ROOT_PATH . '/app/Views/layouts/student_header.php';
?>
<script>
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>
<?php

// Local helper to calculate deadline days remaining
if (!function_exists('getDaysLeftText')) {
    function getDaysLeftText(string $deadlineDate): array {
        $deadlineUnix = strtotime($deadlineDate);
        $todayUnix = strtotime(date('Y-m-d'));
        $diffSec = $deadlineUnix - $todayUnix;
        $days = (int)round($diffSec / 86400);
        
        if ($days < 0) {
            return ['text' => 'Deadline Passed', 'class' => 'passed', 'color' => '#ef4444', 'bg' => '#fef2f2'];
        } elseif ($days === 0) {
            return ['text' => 'Closing Today', 'class' => 'today', 'color' => '#ea580c', 'bg' => '#fff7ed'];
        } elseif ($days === 1) {
            return ['text' => '1 day left', 'class' => 'urgent', 'color' => '#dc2626', 'bg' => '#fef2f2'];
        } elseif ($days <= 7) {
            return ['text' => $days . ' days left', 'class' => 'urgent', 'color' => '#dc2626', 'bg' => '#fef2f2'];
        } elseif ($days <= 30) {
            return ['text' => $days . ' days left', 'class' => 'warning', 'color' => '#d97706', 'bg' => '#fffbeb'];
        } else {
            return ['text' => $days . ' days left', 'class' => 'normal', 'color' => '#059669', 'bg' => '#ecfdf5'];
        }
    }
}
?>

<style>
    .matches-container {
        max-width: 1040px;
        width: 100%;
        margin: 0 auto;
        padding-bottom: 48px;
    }
    
    /* Top banner card */
    .matches-header-card {
        background: var(--bg-white);
        border: 1px solid var(--border);
        border-radius: var(--radius-2xl);
        padding: 28px 32px;
        margin-bottom: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 24px;
        flex-wrap: wrap;
    }
    
    .header-text-col {
        flex: 1;
        min-width: 260px;
    }
    
    .matches-title {
        font-size: 1.75rem;
        font-weight: 800;
        color: var(--text-900);
        margin: 0 0 8px 0;
        letter-spacing: -0.025em;
    }
    
    .matches-subtitle {
        font-size: 0.9375rem;
        color: var(--text-500);
        margin: 0;
        line-height: 1.5;
    }
    
    .matches-strength-widget {
        display: flex;
        align-items: center;
        gap: 16px;
        background: #f8fafc;
        border: 1px solid var(--border);
        padding: 14px 20px;
        border-radius: var(--radius-xl);
    }
    
    .strength-indicator {
        position: relative;
        width: 52px;
        height: 52px;
        border-radius: 50%;
        background: conic-gradient(var(--primary) calc(var(--percentage) * 1%), var(--border) 0);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    
    .strength-indicator::after {
        content: '';
        position: absolute;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #f8fafc;
    }
    
    .strength-val {
        position: relative;
        z-index: 2;
        font-size: 0.8125rem;
        font-weight: 750;
        color: var(--text-900);
    }
    
    /* Control filter & sort bar */
    .matches-control-bar {
        background: var(--bg-white);
        border: 1px solid var(--border);
        border-radius: var(--radius-xl);
        padding: 16px 20px;
        margin-bottom: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
    }
    
    .filter-btn-group {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }
    
    .filter-pill {
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--text-600);
        background: var(--bg-50);
        border: 1px solid var(--border);
        padding: 8px 16px;
        border-radius: 100px;
        text-decoration: none;
        transition: all 0.2s;
    }
    
    .filter-pill:hover {
        border-color: var(--primary-light);
        color: var(--primary);
        background: var(--bg-white);
    }
    
    .filter-pill.active {
        background: var(--primary);
        border-color: var(--primary);
        color: #fff;
    }
    
    .sort-control-group {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .sort-control-label {
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--text-500);
        white-space: nowrap;
    }
    
    .sort-select {
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        padding: 8px 12px;
        font-size: 0.8125rem;
        color: var(--text-800);
        outline: none;
        cursor: pointer;
        background: var(--bg-white);
    }
    
    /* Matches Card Grid */
    .match-cards-grid {
        display: flex;
        flex-direction: column;
        gap: 24px;
    }
    
    /* Ensure global styles never force horizontal table layout */
    .match-card,
    .scholarship-match-card {
        display: flex !important;
        flex-direction: column !important;
        align-items: stretch !important;
        background: var(--bg-white);
        border: 1px solid var(--border);
        border-radius: var(--radius-2xl);
        padding: 26px 30px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04), 0 1px 2px rgba(0, 0, 0, 0.02);
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        position: relative;
        overflow: hidden;
        gap: 16px;
    }
    
    .scholarship-match-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 28px -6px rgba(0, 0, 0, 0.08), 0 4px 12px -2px rgba(0, 0, 0, 0.04);
        border-color: #93c5fd;
    }
    
    /* Full clickable card overlay */
    .card-clickable-overlay {
        position: absolute;
        inset: 0;
        z-index: 1;
        cursor: pointer;
        display: block;
    }
    
    /* Elevate all interactive children above the overlay link */
    .card-top-actions,
    .card-title-link,
    .btn-bookmark-toggle,
    .criteria-accordion,
    .btn-apply-card,
    .card-badge-link {
        position: relative;
        z-index: 3;
    }
    
    /* Card Top Header Row */
    .card-top-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }
    
    .card-badges-left {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    
    /* Prominent Degree Badge */
    .card-degree-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #f0fdf4;
        color: #15803d;
        border: 1px solid #bbf7d0;
        padding: 5px 12px;
        border-radius: 100px;
        font-size: 0.8125rem;
        font-weight: 700;
        letter-spacing: 0.01em;
    }
    
    .card-degree-badge i {
        width: 15px;
        height: 15px;
    }
    
    .match-badge-level {
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        padding: 5px 10px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    
    .match-badge-level.highly-recommended {
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
    }
    
    .match-badge-level.eligible {
        background: #ecfdf5;
        color: #047857;
        border: 1px solid #a7f3d0;
    }
    
    .match-badge-level.possibly-eligible {
        background: #fff7ed;
        color: #c2410c;
        border: 1px solid #fed7aa;
    }
    
    .match-badge-level.insufficient-data {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
    }
    
    .card-top-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-left: auto;
    }
    
    /* Match Score Pill */
    .match-score-badge {
        padding: 6px 14px;
        border-radius: 100px;
        font-size: 0.875rem;
        font-weight: 800;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        letter-spacing: -0.01em;
    }
    
    .match-score-badge.high {
        background: #ecfdf5;
        color: #047857;
        border: 1px solid #a7f3d0;
    }
    
    .match-score-badge.medium {
        background: #fffbeb;
        color: #b45309;
        border: 1px solid #fde68a;
    }
    
    .match-score-badge.low {
        background: #fef2f2;
        color: #b91c1c;
        border: 1px solid #fecaca;
    }
    
    .match-score-badge i {
        width: 15px;
        height: 15px;
    }
    
    /* Bookmark Button */
    .btn-bookmark-toggle {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        border: 1px solid var(--border);
        background: var(--bg-50);
        color: var(--text-500);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
        flex-shrink: 0;
    }
    
    .btn-bookmark-toggle:hover {
        background: #eff6ff;
        border-color: #bfdbfe;
        color: var(--primary);
        transform: scale(1.06);
    }
    
    .btn-bookmark-toggle[data-saved="true"] {
        background: #ecfdf5;
        border-color: #a7f3d0;
        color: #059669;
    }
    
    .btn-bookmark-toggle i {
        width: 18px;
        height: 18px;
    }
    
    /* Title and Organization Header */
    .card-title-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    
    .card-title {
        font-size: 1.375rem;
        font-weight: 800;
        color: var(--text-900);
        line-height: 1.35;
        margin: 0;
        letter-spacing: -0.015em;
    }
    
    .card-title-link {
        text-decoration: none;
        color: inherit;
        transition: color 0.15s ease;
    }
    
    .card-title-link:hover {
        color: var(--primary);
    }
    
    .card-provider-info {
        font-size: 0.875rem;
        color: var(--text-600);
        font-weight: 550;
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    
    .card-provider-info i {
        width: 15px;
        height: 15px;
        color: var(--text-400);
    }
    
    .provider-separator {
        color: var(--text-300);
    }
    
    /* Short Description */
    .card-short-desc {
        font-size: 0.9375rem;
        color: var(--text-700);
        line-height: 1.6;
        margin: 0;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    /* Metadata Pills Grid */
    .card-meta-pills {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        padding-top: 4px;
    }
    
    .meta-pill {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        font-size: 0.8125rem;
        font-weight: 550;
        padding: 6px 12px;
        border-radius: 8px;
        background: #f8fafc;
        border: 1px solid var(--border);
        color: var(--text-700);
    }
    
    .meta-pill i {
        width: 15px;
        height: 15px;
        color: var(--text-500);
        flex-shrink: 0;
    }
    
    .meta-pill.deadline-pill {
        font-weight: 600;
    }
    
    .meta-pill.deadline-pill.normal {
        background: #ecfdf5;
        border-color: #a7f3d0;
        color: #065f46;
    }
    .meta-pill.deadline-pill.normal i { color: #059669; }
    
    .meta-pill.deadline-pill.warning {
        background: #fffbeb;
        border-color: #fde68a;
        color: #92400e;
    }
    .meta-pill.deadline-pill.warning i { color: #d97706; }
    
    .meta-pill.deadline-pill.urgent,
    .meta-pill.deadline-pill.today,
    .meta-pill.deadline-pill.passed {
        background: #fef2f2;
        border-color: #fecaca;
        color: #991b1b;
    }
    .meta-pill.deadline-pill.urgent i,
    .meta-pill.deadline-pill.today i,
    .meta-pill.deadline-pill.passed i { color: #dc2626; }
    
    .meta-pill.deadline-pill.rolling {
        background: #eff6ff;
        border-color: #bfdbfe;
        color: #1e40af;
    }
    .meta-pill.deadline-pill.rolling i { color: #3b82f6; }
    
    /* Fields of Study Tags */
    .card-fields-row {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        font-size: 0.8125rem;
    }
    
    .fields-label {
        font-weight: 600;
        color: var(--text-500);
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    
    .fields-label i {
        width: 14px;
        height: 14px;
    }
    
    .fields-tag-list {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }
    
    .field-tag {
        background: #f1f5f9;
        color: var(--text-700);
        padding: 3px 10px;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 500;
    }
    
    .field-tag-more {
        background: #e2e8f0;
        color: var(--text-600);
        font-weight: 600;
    }
    
    /* Matching Criteria Accordion */
    .criteria-accordion {
        background: #f8fafc;
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        overflow: hidden;
        margin-top: 4px;
    }
    
    .criteria-header {
        width: 100%;
        padding: 10px 16px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--text-700);
        background: transparent;
        border: none;
        text-align: left;
        transition: background 0.15s ease;
    }
    
    .criteria-header:hover {
        background: #f1f5f9;
    }
    
    .criteria-header-left {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .criteria-header-left i {
        width: 15px;
        height: 15px;
        color: var(--primary);
    }
    
    .criteria-chevron {
        width: 16px;
        height: 16px;
        color: var(--text-400);
        transition: transform 0.2s ease;
    }
    
    .criteria-body {
        padding: 16px;
        border-top: 1px solid var(--border);
        background: var(--bg-white);
        display: none;
    }
    
    .criteria-group-title {
        font-size: 0.6875rem;
        font-weight: 700;
        color: var(--text-500);
        text-transform: uppercase;
        margin-bottom: 8px;
        letter-spacing: 0.05em;
    }
    
    .criteria-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-bottom: 12px;
    }
    
    .criteria-list:last-child {
        margin-bottom: 0;
    }
    
    .criteria-item {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        font-size: 0.8125rem;
        color: var(--text-700);
        line-height: 1.4;
    }
    
    .criteria-item i {
        width: 15px;
        height: 15px;
        margin-top: 1px;
        flex-shrink: 0;
    }
    
    .criteria-item.success i { color: #10b981; }
    .criteria-item.fail i { color: #ef4444; }
    .criteria-item.warning i { color: #64748b; }
    
    /* Card Footer Action Row */
    .card-footer-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding-top: 14px;
        border-top: 1px solid var(--border);
        margin-top: 2px;
        flex-wrap: wrap;
    }
    
    .footer-left-info {
        font-size: 0.8125rem;
        color: var(--text-400);
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .btn-apply-card {
        padding: 10px 22px;
        font-size: 0.875rem;
        font-weight: 600;
        border-radius: var(--radius-lg);
        display: inline-flex;
        align-items: center;
        gap: 8px;
        white-space: nowrap;
        text-decoration: none;
        box-shadow: 0 1px 2px rgba(30, 64, 175, 0.15);
        transition: all 0.2s ease;
    }
    
    .btn-apply-card:hover {
        transform: translateX(2px);
        box-shadow: 0 4px 12px rgba(30, 64, 175, 0.25);
    }
    
    .btn-apply-card i {
        width: 16px;
        height: 16px;
        transition: transform 0.2s ease;
    }
    
    .btn-apply-card:hover i {
        transform: translateX(3px);
    }
    
    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 64px 24px;
        background: var(--bg-white);
        border-radius: var(--radius-2xl);
        border: 1px solid var(--border);
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }
    
    .empty-state-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: var(--primary-50);
        color: var(--primary);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
    }
    
    .empty-state-icon i {
        width: 28px;
        height: 28px;
    }
    
    .empty-state h3 {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--text-900);
        margin: 0 0 8px 0;
    }
    
    .empty-state p {
        font-size: 0.9375rem;
        color: var(--text-500);
        margin: 0 0 24px 0;
        line-height: 1.5;
        max-width: 480px;
        margin-left: auto;
        margin-right: auto;
    }
    
    /* Responsive tweaks */
    @media (max-width: 768px) {
        .scholarship-match-card {
            padding: 20px 18px;
            gap: 14px;
        }
        .card-title {
            font-size: 1.1875rem;
        }
        .matches-header-card {
            padding: 20px;
        }
        .card-footer-row {
            flex-direction: column;
            align-items: stretch;
            gap: 12px;
        }
        .btn-apply-card {
            width: 100%;
            justify-content: center;
        }
        .card-top-actions {
            margin-left: 0;
            width: 100%;
            justify-content: space-between;
        }
    }
</style>

<div class="matches-container">
    
    <!-- Top banner card -->
    <div class="matches-header-card">
        <div class="header-text-col">
            <h1 class="matches-title">Scholarship Matches</h1>
            <p class="matches-subtitle">Personalized matching results based on your academic profile, target degrees, and eligibility parameters.</p>
        </div>
        
        <div class="matches-strength-widget">
            <div class="strength-indicator" style="--percentage: <?= (int)$completion ?>">
                <span class="strength-val"><?= (int)$completion ?>%</span>
            </div>
            <div>
                <div style="font-size:0.875rem; font-weight:700; color:var(--text-900);">Profile Strength</div>
                <div style="font-size:0.75rem; color:var(--text-500); margin-top:2px;">
                    <?php if ($completion < 100): ?>
                        <a href="<?= url('/profile/edit') ?>" style="color:var(--primary); font-weight:600; text-decoration:none;">Complete Profile to Match More</a>
                    <?php else: ?>
                        Profile matches fully verified
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Controls filter & sort bar -->
    <div class="matches-control-bar">
        <div class="filter-btn-group">
            <a href="?filter=all&sort=<?= e($sort) ?>" class="filter-pill <?= $filter === 'all' ? 'active' : '' ?>">All Matches</a>
            <a href="?filter=highly_recommended&sort=<?= e($sort) ?>" class="filter-pill <?= $filter === 'highly_recommended' ? 'active' : '' ?>">Highly Recommended</a>
            <a href="?filter=eligible&sort=<?= e($sort) ?>" class="filter-pill <?= $filter === 'eligible' ? 'active' : '' ?>">Eligible</a>
            <a href="?filter=possibly_eligible&sort=<?= e($sort) ?>" class="filter-pill <?= $filter === 'possibly_eligible' ? 'active' : '' ?>">Possibly Eligible</a>
            <a href="?filter=closing_soon&sort=<?= e($sort) ?>" class="filter-pill <?= $filter === 'closing_soon' ? 'active' : '' ?>">Closing Soon</a>
        </div>
        
        <div class="sort-control-group">
            <span class="sort-control-label">Sort by</span>
            <select name="sort" class="sort-select" onchange="window.location.href='?filter=<?= e($filter) ?>&sort=' + this.value;">
                <option value="match_score" <?= $sort === 'match_score' ? 'selected' : '' ?>>Match Score</option>
                <option value="deadline" <?= $sort === 'deadline' ? 'selected' : '' ?>>Closing Date</option>
                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest Added</option>
            </select>
        </div>
    </div>
    
    <!-- Matches Listings Grid -->
    <div class="match-cards-grid">
        <?php if (empty($matches)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i data-lucide="award"></i>
                </div>
                <h3>No Matches Found</h3>
                <p>Try switching filters or update your educational profile details to trigger the matching engine.</p>
                <a href="<?= url('/profile/edit') ?>" class="btn btn-primary">Update Profile Details</a>
            </div>
        <?php else: ?>
            <?php foreach ($matches as $m): ?>
                <?php 
                $score = (int)$m['match_score'];
                $scoreClass = 'high';
                if ($score < 60) $scoreClass = 'low';
                elseif ($score < 80) $scoreClass = 'medium';
                
                $recLevel = strtolower($m['recommendation_level'] ?? '');
                $recClass = str_replace('_', '-', $recLevel);
                $eligStatus = strtolower($m['eligibility_status'] ?? '');
                $eligClass = str_replace('_', '-', $eligStatus);

                // Degrees array
                $degreesList = [];
                if (!empty($m['degree_level'])) {
                    $degreesList = array_filter(array_map('trim', explode(',', $m['degree_level'])));
                }

                // Deadline
                $hasDeadline = !empty($m['application_deadline']);
                $dl = $hasDeadline 
                    ? getDaysLeftText($m['application_deadline']) 
                    : ['text' => 'Rolling Admissions', 'class' => 'rolling', 'color' => '#3b82f6'];
                $deadlineDateFormatted = $hasDeadline ? date('M j, Y', strtotime($m['application_deadline'])) : null;

                // Short description excerpt
                $shortDesc = '';
                if (!empty($m['short_description'])) {
                    $shortDesc = trim($m['short_description']);
                } elseif (!empty($m['description'])) {
                    $shortDesc = trim(strip_tags((string)$m['description']));
                }
                if (empty($shortDesc)) {
                    $shortDesc = 'Explore official eligibility requirements, host country benefits, and complete application instructions for this scholarship.';
                } elseif (mb_strlen($shortDesc) > 230) {
                    $shortDesc = mb_substr($shortDesc, 0, 227) . '...';
                }

                // Scholarship target detail URL
                $detailUrl = url('/scholarships/' . $m['slug']);
                ?>
                <div class="scholarship-match-card">
                    
                    <!-- Entire card clickable overlay link -->
                    <a href="<?= $detailUrl ?>" class="card-clickable-overlay" aria-label="View scholarship details for <?= e($m['title']) ?>"></a>

                    <!-- Top Row: Degree Level, Recommendations & Score Badge -->
                    <div class="card-top-row">
                        <div class="card-badges-left">
                            <?php if (!empty($degreesList)): ?>
                                <?php foreach ($degreesList as $deg): ?>
                                    <span class="card-degree-badge">
                                        <i data-lucide="graduation-cap"></i>
                                        <span><?= e($deg) ?></span>
                                    </span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span class="card-degree-badge">
                                    <i data-lucide="graduation-cap"></i>
                                    <span>All Degrees</span>
                                </span>
                            <?php endif; ?>

                            <?php if (!empty($m['recommendation_level'])): ?>
                                <span class="match-badge-level <?= $recClass ?>">
                                    <?= str_replace('_', ' ', $m['recommendation_level']) ?>
                                </span>
                            <?php endif; ?>

                            <?php if (!empty($m['eligibility_status'])): ?>
                                <span class="match-badge-level <?= $eligClass ?>">
                                    <?= str_replace('_', ' ', $m['eligibility_status']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="card-top-actions">
                            <div class="match-score-badge <?= $scoreClass ?>">
                                <i data-lucide="target"></i>
                                <span><?= $score ?>% Match</span>
                            </div>

                            <button type="button" 
                                    class="btn-bookmark-toggle" 
                                    data-id="<?= (int)$m['scholarship_id'] ?>" 
                                    data-saved="<?= $m['is_saved'] ? 'true' : 'false' ?>" 
                                    title="<?= $m['is_saved'] ? 'Remove from saved' : 'Save scholarship' ?>"
                                    aria-label="Bookmark scholarship">
                                <i data-lucide="<?= $m['is_saved'] ? 'bookmark-check' : 'bookmark' ?>"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Title & Organization -->
                    <div class="card-title-group">
                        <h2 class="card-title">
                            <a href="<?= $detailUrl ?>" class="card-title-link"><?= e($m['title']) ?></a>
                        </h2>
                        <div class="card-provider-info">
                            <i data-lucide="building-2"></i>
                            <span><?= e($m['provider_name']) ?></span>
                            <?php if (!empty($m['host_country_name'])): ?>
                                <span class="provider-separator">•</span>
                                <span class="provider-country"><i data-lucide="globe"></i> <?= e($m['host_country_name']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Short Description -->
                    <p class="card-short-desc">
                        <?= e($shortDesc) ?>
                    </p>
                    
                    <!-- Metadata Highlights Pills -->
                    <div class="card-meta-pills">
                        <?php if (!empty($m['degree_level'])): ?>
                            <div class="meta-pill">
                                <i data-lucide="graduation-cap"></i>
                                <span><?= e($m['degree_level']) ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($m['host_country_name'])): ?>
                            <div class="meta-pill">
                                <i data-lucide="map-pin"></i>
                                <span><?= e($m['host_country_name']) ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($m['funding_type'])): ?>
                            <div class="meta-pill">
                                <i data-lucide="banknote"></i>
                                <span><?= e($m['funding_type']) ?></span>
                            </div>
                        <?php endif; ?>

                        <div class="meta-pill deadline-pill <?= $dl['class'] ?>">
                            <i data-lucide="calendar"></i>
                            <span>
                                <?php if ($deadlineDateFormatted): ?>
                                    <strong><?= e($deadlineDateFormatted) ?></strong> &bull; <?= e($dl['text']) ?>
                                <?php else: ?>
                                    <strong>Rolling Admissions</strong>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>

                    <!-- Fields of Study Tags -->
                    <?php if (!empty($m['field_of_study'])): ?>
                        <?php 
                            $rawFields = array_filter(array_map('trim', explode(',', $m['field_of_study'])));
                            $displayFields = array_slice($rawFields, 0, 4);
                            $moreCount = count($rawFields) - count($displayFields);
                        ?>
                        <div class="card-fields-row">
                            <span class="fields-label"><i data-lucide="book-open"></i> Fields:</span>
                            <div class="fields-tag-list">
                                <?php foreach ($displayFields as $fName): ?>
                                    <span class="field-tag"><?= e($fName) ?></span>
                                <?php endforeach; ?>
                                <?php if ($moreCount > 0): ?>
                                    <span class="field-tag field-tag-more">+<?= $moreCount ?> more</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Criteria Accordion panel -->
                    <div class="criteria-accordion">
                        <button type="button" class="criteria-header" onclick="toggleCriteria(this, event)">
                            <span class="criteria-header-left">
                                <i data-lucide="sparkles"></i>
                                <span>Why you matched (<?= count($m['matched_criteria']) ?> matched criteria)</span>
                            </span>
                            <i data-lucide="chevron-down" class="criteria-chevron"></i>
                        </button>
                        <div class="criteria-body">
                            <?php if (!empty($m['matched_criteria'])): ?>
                                <div class="criteria-group-title">Matched Parameters</div>
                                <div class="criteria-list">
                                    <?php foreach ($m['matched_criteria'] as $item): ?>
                                        <div class="criteria-item success">
                                            <i data-lucide="check-circle-2"></i>
                                            <span><?= e($item) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($m['failed_criteria'])): ?>
                                <div class="criteria-group-title" style="margin-top:12px;">Failed Requirements</div>
                                <div class="criteria-list">
                                    <?php foreach ($m['failed_criteria'] as $item): ?>
                                        <div class="criteria-item fail">
                                            <i data-lucide="x-circle"></i>
                                            <span><?= e($item) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($m['missing_criteria'])): ?>
                                <div class="criteria-group-title" style="margin-top:12px;">Missing Data (Profile incomplete)</div>
                                <div class="criteria-list">
                                    <?php foreach ($m['missing_criteria'] as $item): ?>
                                        <div class="criteria-item warning">
                                            <i data-lucide="alert-circle"></i>
                                            <span><?= e($item) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Card Footer Actions -->
                    <div class="card-footer-row">
                        <div class="footer-left-info">
                            <i data-lucide="info" style="width:14px; height:14px;"></i>
                            <span>Click anywhere on card or button to view eligibility details</span>
                        </div>
                        
                        <a href="<?= $detailUrl ?>" class="btn btn-primary btn-apply-card">
                            <span>Apply / View Details</span>
                            <i data-lucide="arrow-right"></i>
                        </a>
                    </div>

                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
    function toggleCriteria(header, event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        const body = header.nextElementSibling;
        const icon = header.querySelector('.criteria-chevron');
        const isOpen = body.style.display === 'block';
        
        body.style.display = isOpen ? 'none' : 'block';
        if (icon) {
            icon.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(180deg)';
        }
    }

    // ===== AJAX BOOKMARK TOGGLE (SAVE/UNSAVE) =====
    document.addEventListener('click', function(e) {
        const toggleBtn = e.target.closest('.btn-bookmark-toggle');
        if (toggleBtn) {
            e.preventDefault();
            e.stopPropagation();
            const scholarshipId = toggleBtn.getAttribute('data-id');
            const isSaved = toggleBtn.getAttribute('data-saved') === 'true';
            const action = isSaved ? 'unsave' : 'save';

            toggleBtn.disabled = true;

            const formData = new FormData();
            formData.append('csrf_token', '<?= e($csrf_token ?? '') ?>');

            fetch(`/scholarships/${scholarshipId}/${action}`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(data => { throw new Error(data.error || 'Server error'); });
                }
                return response.json();
            })
            .then(data => {
                const newSavedState = !isSaved;
                toggleBtn.setAttribute('data-saved', newSavedState ? 'true' : 'false');
                toggleBtn.setAttribute('title', newSavedState ? 'Remove from saved' : 'Save scholarship');
                toggleBtn.innerHTML = `<i data-lucide="${newSavedState ? 'bookmark-check' : 'bookmark'}"></i>`;
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
                if (typeof showToast === 'function') {
                    showToast(data.message || (newSavedState ? 'Scholarship bookmarked!' : 'Bookmark removed!'), 'success');
                }
            })
            .catch(err => {
                if (typeof showToast === 'function') {
                    showToast(err.message || 'Bookmark action failed.', 'error');
                }
            })
            .finally(() => {
                toggleBtn.disabled = false;
            });
        }
    });

    // Re-run lucide icons creation
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>

<?php include ROOT_PATH . '/app/Views/layouts/student_footer.php'; ?>
