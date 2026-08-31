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
            return ['text' => 'Deadline Passed', 'class' => 'passed', 'color' => '#ef4444'];
        } elseif ($days === 0) {
            return ['text' => 'Closing Today', 'class' => 'today', 'color' => '#f97316'];
        } elseif ($days === 1) {
            return ['text' => '1 day left', 'class' => 'urgent', 'color' => '#ef4444'];
        } elseif ($days <= 7) {
            return ['text' => $days . ' days left', 'class' => 'urgent', 'color' => '#ef4444'];
        } else {
            return ['text' => $days . ' days left', 'class' => 'normal', 'color' => '#10b981'];
        }
    }
}
?>

<style>
    .matches-container {
        max-width: 1000px;
        width: 100%;
        margin: 0 auto;
        padding-bottom: 40px;
    }
    
    .matches-header-card {
        background: var(--bg-white);
        border: 1px solid var(--border);
        border-radius: var(--radius-2xl);
        padding: 32px;
        margin-bottom: 24px;
        box-shadow: var(--shadow-sm);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 24px;
        flex-wrap: wrap;
    }
    
    .header-text-col {
        flex-grow: 1;
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
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: conic-gradient(var(--primary) calc(var(--percentage) * 1%), var(--border) 0);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .strength-indicator::after {
        content: '';
        position: absolute;
        width: 38px;
        height: 38px;
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
    
    /* Control bar styling */
    .matches-control-bar {
        background: var(--bg-white);
        border: 1px solid var(--border);
        border-radius: var(--radius-xl);
        padding: 16px 20px;
        margin-bottom: 24px;
        box-shadow: var(--shadow-sm);
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
    }
    
    .sort-select {
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        padding: 8px 12px;
        font-size: 0.8125rem;
        color: var(--text-800);
        outline: none;
        cursor: pointer;
    }
    
    /* Card layout */
    .match-cards-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .match-card {
        background: var(--bg-white);
        border: 1px solid var(--border);
        border-radius: var(--radius-xl);
        padding: 24px;
        box-shadow: var(--shadow-sm);
        transition: all 0.2s;
        position: relative;
    }
    
    .match-card:hover {
        box-shadow: var(--shadow-md);
        border-color: var(--primary-light);
    }
    
    .match-score-badge {
        position: absolute;
        top: 24px;
        right: 24px;
        padding: 8px 14px;
        border-radius: 10px;
        font-size: 0.875rem;
        font-weight: 750;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .match-score-badge.high {
        background: #ecfdf5;
        color: #047857;
    }
    
    .match-score-badge.medium {
        background: #fffbeb;
        color: #b45309;
    }
    
    .match-score-badge.low {
        background: #fef2f2;
        color: #b91c1c;
    }
    
    .match-badge-level {
        font-size: 0.6875rem;
        font-weight: 700;
        text-transform: uppercase;
        padding: 4px 8px;
        border-radius: 6px;
        margin-right: 6px;
        display: inline-block;
    }
    
    .match-badge-level.highly-recommended {
        background: #eff6ff;
        color: #1d4ed8;
    }
    
    .match-badge-level.eligible {
        background: #f0fdf4;
        color: #166534;
    }
    
    .match-badge-level.possibly-eligible {
        background: #fff7ed;
        color: #c2410c;
    }
    
    .match-badge-level.insufficient-data {
        background: #f1f5f9;
        color: #475569;
    }
    
    .card-title {
        font-size: 1.25rem;
        font-weight: 750;
        color: var(--text-900);
        margin: 0 0 6px 0;
        padding-right: 120px; /* Leave space for score badge */
        line-height: 1.4;
    }
    
    .card-title a {
        text-decoration: none;
        color: inherit;
        transition: color 0.15s;
    }
    
    .card-title a:hover {
        color: var(--primary);
    }
    
    .card-provider {
        font-size: 0.875rem;
        color: var(--text-500);
        font-weight: 500;
        margin-bottom: 16px;
    }
    
    .card-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 16px;
    }
    
    .card-tag {
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--text-700);
        background: var(--bg-100);
        padding: 4px 10px;
        border-radius: 6px;
    }
    
    .card-meta-row {
        display: flex;
        align-items: center;
        gap: 20px;
        border-top: 1px solid var(--border-light);
        padding-top: 16px;
        margin-bottom: 16px;
        flex-wrap: wrap;
    }
    
    .meta-item {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.8125rem;
        color: var(--text-600);
        font-weight: 500;
    }
    
    .meta-item i {
        width: 15px;
        height: 15px;
        color: var(--text-400);
    }
    
    /* Toggle criteria styles */
    .criteria-accordion {
        background: #f8fafc;
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        margin-bottom: 16px;
        overflow: hidden;
    }
    
    .criteria-header {
        padding: 12px 16px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--text-700);
        transition: background 0.15s;
    }
    
    .criteria-header:hover {
        background: #f1f5f9;
    }
    
    .criteria-body {
        padding: 16px;
        border-top: 1px solid var(--border-light);
        display: none;
    }
    
    .criteria-group-title {
        font-size: 0.75rem;
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
        margin-bottom: 16px;
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
        width: 14px;
        height: 14px;
        margin-top: 2px;
        flex-shrink: 0;
    }
    
    .criteria-item.success i { color: #10b981; }
    .criteria-item.fail i { color: #ef4444; }
    .criteria-item.warning i { color: #64748b; }
    
    .action-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }
    
    .empty-state {
        text-align: center;
        padding: 64px 24px;
        background: var(--bg-white);
        border-radius: var(--radius-2xl);
        border: 1px solid var(--border);
        box-shadow: var(--shadow-sm);
    }
    
    .empty-state-icon {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        background: var(--primary-50);
        color: var(--primary);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
    }
    
    .empty-state-icon i {
        width: 24px;
        height: 24px;
    }
    
    .empty-state h3 {
        font-size: 1.125rem;
        font-weight: 700;
        color: var(--text-800);
        margin: 0 0 8px 0;
    }
    
    .empty-state p {
        font-size: 0.875rem;
        color: var(--text-500);
        margin: 0 0 24px 0;
        line-height: 1.5;
    }
</style>

<div class="matches-container">
    
    <!-- Top banner card -->
    <div class="matches-header-card">
        <div class="header-text-col">
            <h1 class="matches-title">Scholarship Matches</h1>
            <p class="matches-subtitle">Personalized matching results based on your academic background, degree goals, and eligibility criteria details.</p>
        </div>
        
        <div class="matches-strength-widget">
            <div class="strength-indicator" style="--percentage: <?= (int)$completion ?>">
                <span class="strength-val"><?= (int)$completion ?>%</span>
            </div>
            <div>
                <div style="font-size:0.875rem; font-weight:700; color:var(--text-900);">Profile Strength</div>
                <div style="font-size:0.75rem; color:var(--text-500); margin-top:2px;">
                    <?php if ($completion < 100): ?>
                        <a href="/profile/edit" style="color:var(--primary); font-weight:600; text-decoration:none;">Complete Profile to Match More</a>
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
                <p>Try switching filters or update your educational history details to run the matching engine again.</p>
                <a href="/profile/edit" class="btn btn-primary">Update Profile Details</a>
            </div>
        <?php else: ?>
            <?php foreach ($matches as $m): ?>
                <?php 
                $score = (int)$m['match_score'];
                $scoreClass = 'high';
                if ($score < 60) $scoreClass = 'low';
                elseif ($score < 80) $scoreClass = 'medium';
                
                $recLevel = strtolower($m['recommendation_level']);
                $recClass = str_replace('_', '-', $recLevel);
                $eligStatus = strtolower($m['eligibility_status']);
                $eligClass = str_replace('_', '-', $eligStatus);
                ?>
                <div class="match-card">
                    <div class="match-score-badge <?= $scoreClass ?>">
                        <i data-lucide="target" style="width:16px;height:16px;"></i>
                        <span><?= $score ?>% Match</span>
                    </div>
                    
                    <h2 class="card-title">
                        <a href="<?= url('/scholarships/' . $m['slug']) ?>"><?= e($m['title']) ?></a>
                    </h2>
                    <div class="card-provider"><?= e($m['provider_name']) ?></div>
                    
                    <div class="card-tags">
                        <span class="card-tag"><?= e($m['degree_level']) ?></span>
                        <span class="card-tag"><?= e($m['field_of_study']) ?></span>
                    </div>
                    
                    <div class="card-meta-row">
                        <div class="meta-item">
                            <i data-lucide="map-pin"></i>
                            <span><?= e($m['host_country_name'] ?? 'Global') ?></span>
                        </div>
                        <div class="meta-item">
                            <i data-lucide="banknote"></i>
                            <span><?= e($m['funding_type']) ?></span>
                        </div>
                        <div class="meta-item">
                            <i data-lucide="calendar"></i>
                            <?php if (empty($m['application_deadline'])): ?>
                                <span style="font-style:italic;">Rolling Deadline</span>
                            <?php else: ?>
                                <?php $dl = getDaysLeftText($m['application_deadline']); ?>
                                <span style="color: <?= $dl['color'] ?>; font-weight:600;"><?= e($dl['text']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Criteria Accordion panel -->
                    <div class="criteria-accordion">
                        <div class="criteria-header" onclick="toggleCriteria(this)">
                            <span>View Matching Eligibility Parameters</span>
                            <i data-lucide="chevron-down" style="width:16px; height:16px; transition: transform 0.2s;"></i>
                        </div>
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
                    
                    <div class="action-row">
                        <div>
                            <span class="match-badge-level <?= $recClass ?>"><?= str_replace('_', ' ', $m['recommendation_level']) ?></span>
                            <span class="match-badge-level <?= $eligClass ?>"><?= str_replace('_', ' ', $m['eligibility_status']) ?></span>
                        </div>
                        
                        <div style="display:flex; gap:10px; align-items:center;">
                            <button class="btn btn-secondary btn-bookmark-toggle" data-id="<?= (int)$m['scholarship_id'] ?>" data-saved="<?= $m['is_saved'] ? 'true' : 'false' ?>" style="padding:10px; min-width:40px;">
                                <i data-lucide="<?= $m['is_saved'] ? 'bookmark-check' : 'bookmark' ?>" style="width:18px; height:18px;"></i>
                            </button>
                            <a href="<?= url('/scholarships/' . $m['slug']) ?>" class="btn btn-primary">
                                <span>Apply / View Details</span>
                                <i data-lucide="arrow-right" style="width:16px;height:16px;"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
    function toggleCriteria(header) {
        const body = header.nextElementSibling;
        const icon = header.querySelector('[data-lucide="chevron-down"]');
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
            const scholarshipId = toggleBtn.getAttribute('data-id');
            const isSaved = toggleBtn.getAttribute('data-saved') === 'true';
            const action = isSaved ? 'unsave' : 'save';

            toggleBtn.disabled = true;

            const formData = new FormData();
            formData.append('csrf_token', '<?= e($csrf_token) ?>');

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
                toggleBtn.innerHTML = `<i data-lucide="${newSavedState ? 'bookmark-check' : 'bookmark'}" style="width:18px; height:18px;"></i>`;
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
</script>

<?php include ROOT_PATH . '/app/Views/layouts/student_footer.php'; ?>
