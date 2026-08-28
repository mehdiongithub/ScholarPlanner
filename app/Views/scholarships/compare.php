<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compare Scholarships | ScholarMatch</title>
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
        .compare-table-wrapper {
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius-2xl);
            overflow-x: auto;
            box-shadow: var(--shadow-sm);
            margin-bottom: 40px;
        }
        .compare-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
            text-align: left;
        }
        .compare-table th, .compare-table td {
            padding: 16px 24px;
            border-bottom: 1px solid var(--border);
            vertical-align: top;
        }
        .compare-table th {
            background: #f8fafc;
            font-weight: 700;
            color: var(--text-800);
            width: 200px;
            min-width: 180px;
            border-right: 1px solid var(--border);
        }
        .compare-table td {
            min-width: 220px;
            border-right: 1px solid var(--border);
        }
        .compare-table td:last-child, .compare-table th:last-child {
            border-right: none;
        }
        .compare-header-card {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
        }
        .compare-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-900);
            margin-bottom: 8px;
            line-height: 1.4;
        }
        .badge-match {
            background: #f0fdfa;
            color: #0d9488;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.75rem;
        }
        .section-header-row th {
            background: #eff6ff;
            color: #1e40af;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            padding: 10px 24px;
        }
        .section-header-row td {
            background: #eff6ff;
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
                <a href="<?= url('/dashboard') ?>" class="nav-link">Dashboard</a>
            </div>
        </header>

        <main class="page-content">
            <div style="margin-bottom: 32px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
                <div>
                    <h1 style="font-size: 2rem; font-weight: 700; color: var(--text-900);">Scholarship Comparison</h1>
                    <p style="color: var(--text-500);">Comparing up to 4 selected opportunities side-by-side.</p>
                </div>
                <a href="<?= url('/scholarships') ?>" class="btn btn-secondary">
                    <i data-lucide="arrow-left" style="width:16px; height:16px;"></i>
                    <span>Back to Search</span>
                </a>
            </div>

            <div class="compare-table-wrapper">
                <table class="compare-table">
                    <thead>
                        <tr>
                            <th>Scholarship</th>
                            <?php foreach ($comparison as $item): ?>
                                <?php $s = $item['scholarship']; ?>
                                <td>
                                    <div class="compare-header-card">
                                        <div>
                                            <div style="font-size:0.75rem; font-weight:600; color:var(--text-400); text-transform:uppercase; margin-bottom:4px;"><?= e($s['provider_name']) ?></div>
                                            <h2 class="compare-title"><?= e($s['title']) ?></h2>
                                        </div>
                                        
                                        <div style="margin-top:16px; display:flex; flex-direction:column; gap:8px;">
                                            <form method="POST" action="<?= url('/scholarships/' . $s['id'] . '/compare/remove') ?>">
                                                <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                                                <button type="submit" class="btn btn-secondary" style="width:100%; font-size:0.75rem; padding:6px 12px; justify-content:center;">
                                                    <i data-lucide="trash-2" style="width:12px; height:12px; color:#ef4444;"></i>
                                                    <span>Remove</span>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Overview Section -->
                        <tr class="section-header-row">
                            <th>Key Metrics</th>
                            <?php foreach ($comparison as $item): ?>
                                <td></td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <th>Match Score</th>
                            <?php foreach ($comparison as $item): ?>
                                <td>
                                    <span class="badge-match">
                                        <i data-lucide="award" style="width:14px; height:14px;"></i>
                                        <span><?= $item['match']['match_score'] ?>% (<?= str_replace('_', ' ', $item['match']['eligibility_status']) ?>)</span>
                                    </span>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <th>Doc Readiness</th>
                            <?php foreach ($comparison as $item): ?>
                                <td>
                                    <span style="font-weight:600; color:var(--text-700);"><?= $item['readiness']['readiness_percentage'] ?>%</span>
                                    <div style="font-size:0.75rem; color:var(--text-500); margin-top:4px;">
                                        <?= $item['readiness']['missing_count'] ?> missing docs
                                    </div>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <th>Tracking Status</th>
                            <?php foreach ($comparison as $item): ?>
                                <td>
                                    <?php if ($item['applied'] !== 'not_applied'): ?>
                                        <span class="badge-status status-<?= $item['applied'] ?>" style="font-size:0.75rem; padding:4px 8px; font-weight:600; border-radius:4px;"><?= str_replace('_', ' ', $item['applied']) ?></span>
                                    <?php else: ?>
                                        <span style="color:var(--text-400); font-style:italic;">Not Tracked</span>
                                        <div style="margin-top:8px;">
                                            <a href="<?= url('/scholarships/' . $item['scholarship']['slug']) ?>" class="btn btn-primary" style="font-size:0.75rem; padding:4px 8px; background:var(--primary);">Track App</a>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>

                        <!-- Details Section -->
                        <tr class="section-header-row">
                            <th>Details & Mappings</th>
                            <?php foreach ($comparison as $item): ?>
                                <td></td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <th>Country of Study</th>
                            <?php foreach ($comparison as $item): ?>
                                <td><?= e($item['scholarship']['country_name'] ?? 'Multiple Countries') ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <th>Funding Type</th>
                            <?php foreach ($comparison as $item): ?>
                                <td><span style="font-weight:600;"><?= e($item['scholarship']['funding_type']) ?></span></td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <th>Degree Levels</th>
                            <?php foreach ($comparison as $item): ?>
                                <td><?= e($item['degrees'] ?: 'All levels') ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <th>Fields of Study</th>
                            <?php foreach ($comparison as $item): ?>
                                <td><?= e($item['fields'] ?: 'All fields') ?></td>
                            <?php endforeach; ?>
                        </tr>

                        <!-- Benefits Section -->
                        <tr class="section-header-row">
                            <th>Benefits</th>
                            <?php foreach ($comparison as $item): ?>
                                <td></td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <th>Stipend Info</th>
                            <?php foreach ($comparison as $item): ?>
                                <td><?= e($item['stipend']) ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <th>Airfare Coverage</th>
                            <?php foreach ($comparison as $item): ?>
                                <td><?= e($item['airfare']) ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <th>Health Insurance</th>
                            <?php foreach ($comparison as $item): ?>
                                <td><?= e($item['health']) ?></td>
                            <?php endforeach; ?>
                        </tr>

                        <!-- Eligibility Section -->
                        <tr class="section-header-row">
                            <th>Eligibility Rules</th>
                            <?php foreach ($comparison as $item): ?>
                                <td></td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <th>Age Boundaries</th>
                            <?php foreach ($comparison as $item): ?>
                                <?php $r = $item['rules']; ?>
                                <td>
                                    <?php if (!empty($r['minimum_age']) || !empty($r['maximum_age'])): ?>
                                        <?= e($r['minimum_age'] ?? 'Any') ?> to <?= e($r['maximum_age'] ?? 'Any') ?> years
                                    <?php else: ?>
                                        No Age Limits
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <th>Min CGPA</th>
                            <?php foreach ($comparison as $item): ?>
                                <?php $r = $item['rules']; ?>
                                <td>
                                    <?php if (!empty($r['minimum_cgpa'])): ?>
                                        <?= e($r['minimum_cgpa']) ?> / <?= e($r['cgpa_scale'] ?? '4.0') ?>
                                    <?php else: ?>
                                        Not Required
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <th>Gender Restriction</th>
                            <?php foreach ($comparison as $item): ?>
                                <?php $r = $item['rules']; ?>
                                <td><?= e(!empty($r['gender_requirement']) ? ucfirst($r['gender_requirement']) : 'Any Gender') ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <th>Language Tests</th>
                            <?php foreach ($comparison as $item): ?>
                                <?php $r = $item['rules']; ?>
                                <td>
                                    <?php 
                                        $langs = [];
                                        if (!empty($r['ielts_required'])) $langs[] = "IELTS (" . $r['ielts_minimum_score'] . ")";
                                        if (!empty($r['toefl_required'])) $langs[] = "TOEFL (" . $r['toefl_minimum_score'] . ")";
                                    ?>
                                    <?= empty($langs) ? 'None' : implode(', ', $langs) ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <th>Deadline</th>
                            <?php foreach ($comparison as $item): ?>
                                <?php $deadline = $item['scholarship']['application_deadline']; ?>
                                <td style="color: <?= !empty($deadline) && strtotime($deadline) < time() ? '#ef4444' : 'inherit' ?>;">
                                    <?php if (empty($deadline)): ?>
                                        Open
                                    <?php elseif (strtotime($deadline) < time()): ?>
                                        Expired
                                    <?php else: ?>
                                        <?= e(date('M d, Y', strtotime($deadline))) ?>
                                    </td>
                                    <?php endif; ?>
                            <?php endforeach; ?>
                        </tr>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
