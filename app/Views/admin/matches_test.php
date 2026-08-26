<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Matching Engine Diagnostic | ScholarMatch</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">
    <style>
        .diagnostic-container {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 20px;
        }
        .diagnostic-card {
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius-2xl);
            padding: 32px;
            box-shadow: var(--shadow-sm);
        }
        .header-box {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 16px;
        }
        .header-box h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-900);
        }
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--text-600);
            text-decoration: none;
            font-size: 0.875rem;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .form-group label {
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--text-700);
        }
        .form-group select {
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            background: var(--bg-white);
        }
        .btn-run {
            background: var(--primary);
            color: white;
            padding: 12px 24px;
            border-radius: var(--radius-md);
            font-weight: 600;
            border: none;
            cursor: pointer;
            width: 100%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background 0.2s;
        }
        .btn-run:hover {
            background: var(--primary-hover);
        }
        .result-section {
            margin-top: 32px;
            border-top: 1px solid var(--border);
            padding-top: 24px;
        }
        .result-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .result-pct {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary);
        }
        .status-badge {
            font-size: 0.875rem;
            font-weight: 700;
            padding: 6px 12px;
            border-radius: var(--radius-sm);
            text-transform: uppercase;
        }
        .status-eligible { background: #d1fae5; color: #065f46; }
        .status-possibly { background: #e0f2fe; color: #0369a1; }
        .status-insufficient { background: #fef3c7; color: #92400e; }
        .status-ineligible { background: #fee2e2; color: #991b1b; }

        .rules-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }
        .rules-table th, .rules-table td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid var(--border);
            font-size: 0.875rem;
        }
        .rules-table th {
            background: #f8fafc;
            font-weight: 600;
            color: var(--text-800);
        }
        .rule-status {
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.75rem;
        }
        .rule-matched { color: #10b981; }
        .rule-failed { color: #ef4444; }
        .rule-missing { color: #f59e0b; }
    </style>
</head>
<body>
    <div class="diagnostic-container">
        <div class="diagnostic-card">
            <div class="header-box">
                <h1>Matching Engine Diagnostics</h1>
                <a href="/admin" class="btn-back">
                    <i data-lucide="arrow-left"></i>
                    <span>Back to Admin Panel</span>
                </a>
            </div>

            <form action="" method="GET">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="user_id">Select Test User (Visitor Role)</label>
                        <select name="user_id" id="user_id" required>
                            <option value="">-- Choose User --</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= $u['id'] ?>" <?= $selectedUserId === (int)$u['id'] ? 'selected' : '' ?>>
                                    <?= e($u['first_name'] . ' ' . $u['last_name']) ?> (<?= e($u['email']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="scholarship_id">Select Published Scholarship</label>
                        <select name="scholarship_id" id="scholarship_id" required>
                            <option value="">-- Choose Scholarship --</option>
                            <?php foreach ($scholarships as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= $selectedSchId === (int)$s['id'] ? 'selected' : '' ?>>
                                    <?= e($s['title']) ?> (by <?= e($s['provider_name']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn-run">
                    <i data-lucide="play"></i>
                    <span>Execute Matching Evaluation</span>
                </button>
            </form>

            <?php if ($result): ?>
                <div class="result-section">
                    <div class="result-header">
                        <div>
                            <span class="status-badge <?= $result['eligibility_status'] === 'ELIGIBLE' ? 'status-eligible' : ($result['eligibility_status'] === 'NOT_ELIGIBLE' ? 'status-ineligible' : 'status-insufficient') ?>">
                                <?= e($result['eligibility_status']) ?>
                            </span>
                            <div style="font-size: 0.8125rem; color: var(--text-500); margin-top: 6px;">
                                Recommendation: <strong><?= e($result['recommendation_level']) ?></strong>
                            </div>
                        </div>
                        <div class="result-pct">
                            <?= e($result['match_score']) ?>% Match
                        </div>
                    </div>

                    <h3>Rule-by-Rule Breakdowns</h3>
                    <table class="rules-table">
                        <thead>
                            <tr>
                                <th>Criteria Rule</th>
                                <th>Status</th>
                                <th>Detailed Explanation</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($result['matched_criteria'])): ?>
                                <?php foreach ($result['matched_criteria'] as $rule => $msg): ?>
                                    <tr>
                                        <td><strong><?= e(ucfirst($rule)) ?></strong></td>
                                        <td class="rule-status rule-matched">✓ MATCHED</td>
                                        <td><?= e($msg) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <?php if (!empty($result['missing_criteria'])): ?>
                                <?php foreach ($result['missing_criteria'] as $rule => $msg): ?>
                                    <tr>
                                        <td><strong><?= e(ucfirst($rule)) ?></strong></td>
                                        <td class="rule-status rule-missing">⚠ MISSING</td>
                                        <td><?= e($msg) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <?php if (!empty($result['failed_criteria'])): ?>
                                <?php foreach ($result['failed_criteria'] as $rule => $msg): ?>
                                    <tr>
                                        <td><strong><?= e(ucfirst($rule)) ?></strong></td>
                                        <td class="rule-status rule-failed">✗ FAILED</td>
                                        <td><?= e($msg) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
