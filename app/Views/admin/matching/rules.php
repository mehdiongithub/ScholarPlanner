<?php include ROOT_PATH . '/app/Views/layouts/admin_header.php'; ?>

<style>
    .rules-card {
        background: #fff;
        border: 1px solid var(--border-slate-200);
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 24px;
    }
    .rule-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
        font-weight: 700;
        font-size: 1.125rem;
        color: #1e293b;
    }
    .rule-header i {
        color: var(--primary);
    }
    .rule-desc {
        color: #64748b;
        font-size: 0.875rem;
        line-height: 1.5;
        margin-bottom: 16px;
    }
    .criteria-tag {
        background-color: var(--bg-slate-50);
        border: 1px solid var(--border-slate-200);
        padding: 8px 12px;
        border-radius: 8px;
        font-size: 0.8125rem;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
</style>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <div>
        <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0; color: #1e293b;">Matching Engine Configuration</h1>
        <p style="margin: 4px 0 0 0; color: #64748b; font-size: 0.875rem;">View matching rules applied globally to calculate score matching percentages.</p>
    </div>
    <a href="/admin/matching/stats" class="btn btn-secondary">
        <i data-lucide="bar-chart" style="width: 16px; height: 16px;"></i>
        <span>Matching Analytics</span>
    </a>
</div>

<!-- Rule Cards -->
<div class="rules-card">
    <div class="rule-header">
        <i data-lucide="book-open"></i>
        <span>1. Education Level Mapping</span>
    </div>
    <p class="rule-desc">Checks if the target scholarship degree levels intersect with the student's active education record or planned target levels. Intersecting values score matching points.</p>
    <div class="criteria-tag"><i data-lucide="check" style="color: #22c55e;"></i> Core Constraint (Pass/Fail)</div>
</div>

<div class="rules-card">
    <div class="rule-header">
        <i data-lucide="graduation-cap"></i>
        <span>2. Fields of Study Alignment</span>
    </div>
    <p class="rule-desc">Validates if the scholarship target disciplines map to the student's field of study or preferences. Intersections calculate score weight distribution.</p>
    <div class="criteria-tag"><i data-lucide="percent" style="color: #2563eb;"></i> Weighted Score Contribution</div>
</div>

<div class="rules-card">
    <div class="rule-header">
        <i data-lucide="map"></i>
        <span>3. Geographic Coverage (National vs State-Specific)</span>
    </div>
    <p class="rule-desc">Processes the country and state parameters. If a scholarship is restricted to a specific region (e.g. Punjab, Pakistan), it will only match students registered in Punjab, or universities operating Punjab-wide/nationwide.</p>
    <div class="criteria-tag"><i data-lucide="shield-alert" style="color: #ea580c;"></i> Strict Location Boundary</div>
</div>

<div class="rules-card">
    <div class="rule-header">
        <i data-lucide="user-check"></i>
        <span>4. Age & Gender Requirements</span>
    </div>
    <p class="rule-desc">Enforces age ceiling parameters (maximum age limit) and gender filters (e.g. women-only scholarships). Students who fall outside these limits are filtered out.</p>
    <div class="criteria-tag"><i data-lucide="check" style="color: #22c55e;"></i> Core Constraint (Pass/Fail)</div>
</div>

<div class="rules-card">
    <div class="rule-header">
        <i data-lucide="award"></i>
        <span>5. GPA / Percentage Thresholds</span>
    </div>
    <p class="rule-desc">Verifies if the student's obtained CGPA or high school percentage meets or exceeds the minimum scholarship academic threshold requirement.</p>
    <div class="criteria-tag"><i data-lucide="check" style="color: #22c55e;"></i> Core Constraint (Pass/Fail)</div>
</div>

<div class="rules-card">
    <div class="rule-header">
        <i data-lucide="globe"></i>
        <span>6. English Proficiency Score Thresholds</span>
    </div>
    <p class="rule-desc">Audits IELTS band score, TOEFL, PTE, or Duolingo thresholds. If the student has entered language test scores, they are verified against scholarship minimum criteria.</p>
    <div class="criteria-tag"><i data-lucide="sliders" style="color: #475569;"></i> Conditional Constraint</div>
</div>

<?php include ROOT_PATH . '/app/Views/layouts/admin_footer.php'; ?>
