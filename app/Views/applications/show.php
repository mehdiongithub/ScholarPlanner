<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Details | ScholarMatch</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">
    <style>
        .detail-layout {
            min-height: 100vh;
            background: #f8fafc;
            display: flex;
            flex-direction: column;
        }
        .detail-header {
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
            transition: color 0.2s;
        }
        .nav-link:hover, .nav-link.active {
            color: var(--primary);
        }
        .detail-content {
            max-width: 1100px;
            width: 100%;
            margin: 40px auto;
            padding: 0 20px;
            flex-grow: 1;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--text-600);
            text-decoration: none;
            font-size: 0.875rem;
            margin-bottom: 24px;
            font-weight: 500;
            transition: color 0.2s;
        }
        .back-link:hover {
            color: var(--primary);
        }
        .grid-layout {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 32px;
        }
        .card-panel {
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            padding: 28px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 32px;
        }
        .panel-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-900);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 12px;
        }
        .badge-status {
            padding: 6px 12px;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            display: inline-block;
            text-align: center;
        }
        .status-interested { background: #eff6ff; color: #1d4ed8; }
        .status-planning { background: #fef3c7; color: #d97706; }
        .status-documents_pending { background: #fff7ed; color: #ea580c; }
        .status-ready_to_apply { background: #ecfdf5; color: #059669; }
        .status-applied { background: #f5f3ff; color: #7c3aed; }
        .status-interview { background: #f0fdfa; color: #0d9488; }
        .status-accepted { background: #f0fdf4; color: #16a34a; }
        .status-rejected { background: #fef2f2; color: #dc2626; }
        .status-withdrawn { background: #f1f5f9; color: #475569; }

        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--text-700);
            margin-bottom: 8px;
        }
        .form-input {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            background: var(--bg-white);
        }
        .form-textarea {
            width: 100%;
            height: 120px;
            padding: 10px 14px;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            resize: vertical;
            background: var(--bg-white);
        }
        .btn-submit {
            background: var(--primary);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background 0.2s;
        }
        .btn-submit:hover {
            background: var(--primary-hover);
        }
        .btn-danger-outline {
            background: #fff5f5;
            color: #e53e3e;
            border: 1px solid #fed7d7;
            padding: 12px 24px;
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            margin-top: 16px;
            transition: background 0.2s;
        }
        .btn-danger-outline:hover {
            background: #fff0f0;
        }

        .checklist-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 16px;
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            margin-bottom: 12px;
            background: #f8fafc;
        }
        .checklist-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .checklist-name {
            font-size: 0.9375rem;
            font-weight: 600;
            color: var(--text-800);
        }
        .checklist-meta {
            font-size: 0.75rem;
            color: var(--text-500);
        }
        .badge-doc {
            font-size: 0.75rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 9999px;
            text-transform: uppercase;
        }
        .doc-approved { background: #dcfce7; color: #15803d; }
        .doc-uploaded { background: #fef9c3; color: #a16207; }
        .doc-rejected { background: #fee2e2; color: #b91c1c; }
        .doc-missing { background: #f1f5f9; color: #475569; }

        .progress-section {
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            padding: 20px;
            margin-bottom: 24px;
        }
        .progress-label {
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--text-500);
            text-transform: uppercase;
            margin-bottom: 8px;
            display: block;
        }
        .progress-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-900);
            margin-bottom: 12px;
        }
        .progress-bar-bg {
            background: #e2e8f0;
            height: 8px;
            border-radius: 9999px;
            overflow: hidden;
            width: 100%;
        }
        .progress-bar-fill {
            background: var(--primary);
            height: 100%;
            border-radius: 9999px;
        }

        .match-score-badge {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: var(--radius-xl);
            padding: 20px;
            text-align: center;
            margin-bottom: 24px;
        }
        .match-score-value {
            font-size: 2.25rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 6px;
        }
        .match-score-label {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-700);
        }

        .deadline-card {
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            padding: 20px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .deadline-icon-box {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .dl-active { background: #e0f2fe; color: #0369a1; }
        .dl-urgent { background: #fee2e2; color: #b91c1c; }
        .dl-passed { background: #f1f5f9; color: #475569; }

        .timeline {
            display: flex;
            flex-direction: column;
            gap: 20px;
            position: relative;
            padding-left: 20px;
            border-left: 2px solid #e2e8f0;
            margin-left: 10px;
        }
        .timeline-item {
            position: relative;
        }
        .timeline-marker {
            position: absolute;
            left: -27px;
            top: 4px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--primary);
            border: 2px solid white;
        }
        .timeline-content {
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 12px 16px;
        }
        .timeline-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 6px;
            font-size: 0.8125rem;
        }
        .timeline-title {
            font-weight: 600;
            color: var(--text-800);
        }
        .timeline-date {
            color: var(--text-400);
        }
        .timeline-notes {
            font-size: 0.875rem;
            color: var(--text-600);
        }

        .alert-error-box {
            background: #fff5f5;
            border: 1px solid #fed7d7;
            padding: 16px;
            border-radius: var(--radius-lg);
            margin-bottom: 24px;
            color: #c53030;
            font-size: 0.875rem;
        }
        .alert-success-box {
            background: #f0fff4;
            border: 1px solid #c6f6d5;
            padding: 16px;
            border-radius: var(--radius-lg);
            margin-bottom: 24px;
            color: #22543d;
            font-size: 0.875rem;
        }

        @media (max-width: 768px) {
            .grid-layout {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="detail-layout">
        <header class="detail-header" role="banner">
            <a href="/" class="logo-box">
                <i data-lucide="graduation-cap"></i>
                <span>ScholarMatch</span>
            </a>
            
            <div class="nav-links">
                <a href="/dashboard" class="nav-link">Dashboard</a>
                <a href="/profile" class="nav-link">My Profile</a>
                <a href="/documents" class="nav-link">Documents</a>
                <a href="/applications" class="nav-link active">Applications</a>
            </div>
            
            <div style="font-size: 0.875rem; font-weight: 600; color: var(--text-800);">
                Active Session
            </div>
        </header>

        <main class="detail-content">
            <a href="/applications" class="back-link">
                <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i>
                <span>Back to Tracker</span>
            </a>

            <!-- Flash alerts -->
            <?php if (isset($_SESSION['application_success'])): ?>
                <div class="alert-success-box" role="alert">
                    <?= e($_SESSION['application_success']) ?>
                    <?php unset($_SESSION['application_success']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['application_errors'])): ?>
                <div class="alert-error-box" role="alert">
                    <ul style="margin: 0; padding-left: 20px;">
                        <?php foreach ($_SESSION['application_errors'] as $err): ?>
                            <li><?= e($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php unset($_SESSION['application_errors']); ?>
                </div>
            <?php endif; ?>

            <div style="margin-bottom: 32px;">
                <h1 style="font-size: 2rem; font-weight: 700; color: var(--text-900); margin-bottom: 8px;"><?= e($app['title']) ?></h1>
                <p style="color: var(--text-500); font-size: 1rem; display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="building" style="width: 18px; height: 18px;"></i>
                    <span><?= e($app['provider_name']) ?></span>
                </p>
            </div>

            <div class="grid-layout">
                <!-- Left panel -->
                <div>
                    <!-- Document integration checklist -->
                    <div class="card-panel">
                        <h2 class="panel-title">
                            <i data-lucide="check-square"></i>
                            <span>Required Documents Checklist</span>
                        </h2>

                        <div class="progress-section">
                            <span class="progress-label">Document Readiness</span>
                            <div class="progress-header">
                                <span><?= e($readiness['readiness_percentage']) ?>%</span>
                                <span style="font-size: 0.9375rem; color: var(--text-500); font-weight: 500;">
                                    <?= e($readiness['approved_count']) ?> of <?= e($readiness['required_count']) ?> approved
                                </span>
                            </div>
                            <div class="progress-bar-bg">
                                <div class="progress-bar-fill" style="width: <?= e($readiness['readiness_percentage']) ?>%;"></div>
                            </div>
                        </div>

                        <?php if (empty($readiness['details'])): ?>
                            <p style="color: var(--text-500); font-size: 0.875rem;">This scholarship has no document requirements.</p>
                        <?php else: ?>
                            <?php foreach ($readiness['details'] as $doc): ?>
                                <div class="checklist-item">
                                    <div class="checklist-info">
                                        <span class="checklist-name"><?= e($doc['name']) ?></span>
                                        <span class="checklist-meta"><?= $doc['is_required'] ? 'Required' : 'Optional' ?></span>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <span class="badge-doc doc-<?= e($doc['status']) ?>"><?= e($doc['status']) ?></span>
                                        <?php if ($doc['status'] === 'missing' || $doc['status'] === 'rejected'): ?>
                                            <a href="/documents" class="btn-action" style="font-size: 0.75rem; font-weight: 600; text-decoration: none; color: var(--primary);">Upload</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if ($doc['status'] === 'rejected' && $doc['rejection_reason']): ?>
                                    <div style="margin-top: -8px; margin-bottom: 12px; padding: 8px 16px; background: #fff5f5; border-left: 3px solid #dc2626; border-radius: var(--radius-md); font-size: 0.8125rem; color: #991b1b;">
                                        <strong>Rejection Reason:</strong> <?= e($doc['rejection_reason']) ?>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Application status history logs -->
                    <div class="card-panel">
                        <h2 class="panel-title">
                            <i data-lucide="history"></i>
                            <span>Application Tracking History</span>
                        </h2>

                        <div class="timeline">
                            <?php foreach ($history as $h): ?>
                                <div class="timeline-item">
                                    <div class="timeline-marker"></div>
                                    <div class="timeline-content">
                                        <div class="timeline-header">
                                            <span class="timeline-title">
                                                Status: <span style="font-weight: 700; text-transform: uppercase; color: var(--primary);"><?= str_replace('_', ' ', e($h['new_status'])) ?></span>
                                            </span>
                                            <span class="timeline-date"><?= e($h['changed_at']) ?></span>
                                        </div>
                                        <p class="timeline-notes"><?= e($h['notes']) ?></p>
                                        <p style="font-size: 0.75rem; color: var(--text-400); margin-top: 4px; margin-bottom: 0;">
                                            Changed by: <?= e($h['first_name']) ?> <?= e($h['last_name']) ?> (<?= ucfirst(e($h['role_name'])) ?>)
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Right sidebar panel -->
                <div>
                    <!-- Matching integration -->
                    <div class="match-score-badge">
                        <div class="match-score-value"><?= e($app['match_score'] ?? 70) ?>%</div>
                        <div class="match-score-label">Eligibility Match Score</div>
                        <div style="font-size: 0.75rem; color: var(--text-500); margin-top: 6px; font-weight: 500;">
                            Status: <?= e($app['eligibility_status'] ?? 'ELIGIBLE') ?>
                        </div>
                    </div>

                    <!-- Deadline cards -->
                    <?php
                        $dlClass = 'dl-active';
                        if ($days_remaining !== null) {
                            if ($days_remaining < 0) { $dlClass = 'dl-passed'; }
                            elseif ($days_remaining <= 7) { $dlClass = 'dl-urgent'; }
                        }
                    ?>
                    <div class="deadline-card">
                        <div class="deadline-icon-box <?= $dlClass ?>">
                            <i data-lucide="calendar"></i>
                        </div>
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 600; color: var(--text-400); text-transform: uppercase;">Deadline</div>
                            <div style="font-size: 1rem; font-weight: 700; color: var(--text-800);">
                                <?= $app['application_deadline'] ? e($app['application_deadline']) : 'Rolling Deadline' ?>
                            </div>
                            <?php if ($days_remaining !== null): ?>
                                <div style="font-size: 0.8125rem; font-weight: 500; margin-top: 2px;">
                                    <?php if ($days_remaining < 0): ?>
                                        <span style="color: #718096;">Expired</span>
                                    <?php else: ?>
                                        <span style="color: <?= $days_remaining <= 7 ? '#e53e3e' : '#0369a1' ?>;"><?= e($days_remaining) ?> days remaining</span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Edit Tracker Form -->
                    <div class="card-panel">
                        <h2 class="panel-title" style="border: none; padding: 0; margin-bottom: 16px;">
                            <i data-lucide="sliders"></i>
                            <span>Update Status</span>
                        </h2>

                        <form method="POST" action="/applications/<?= e($app['id']) ?>/update">
                            <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">

                            <div class="form-group">
                                <label class="form-label" for="status">Workflow Status</label>
                                <select id="status" name="status" class="form-input">
                                    <option value="interested" <?= $app['status'] === 'interested' ? 'selected' : '' ?>>Interested</option>
                                    <option value="planning" <?= $app['status'] === 'planning' ? 'selected' : '' ?>>Planning</option>
                                    <option value="documents_pending" <?= $app['status'] === 'documents_pending' ? 'selected' : '' ?>>Documents Pending</option>
                                    <option value="ready_to_apply" <?= $app['status'] === 'ready_to_apply' ? 'selected' : '' ?>>Ready to Apply</option>
                                    <option value="applied" <?= $app['status'] === 'applied' ? 'selected' : '' ?>>Applied</option>
                                    <option value="interview" <?= $app['status'] === 'interview' ? 'selected' : '' ?>>Interview</option>
                                    <option value="accepted" <?= $app['status'] === 'accepted' ? 'selected' : '' ?>>Accepted</option>
                                    <option value="rejected" <?= $app['status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                                    <option value="withdrawn" <?= $app['status'] === 'withdrawn' ? 'selected' : '' ?>>Withdrawn</option>
                                </select>
                            </div>

                            <div class="form-group" id="applied-at-group" style="display: <?= $app['status'] === 'applied' ? 'block' : 'none' ?>;">
                                <label class="form-label" for="applied_at">Submission Date</label>
                                <input type="date" id="applied_at" name="applied_at" class="form-input" value="<?= $app['applied_at'] ? date('Y-m-d', strtotime($app['applied_at'])) : '' ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="application_reference">Reference/Application Number</label>
                                <input type="text" id="application_reference" name="application_reference" class="form-input" placeholder="e.g. APP-89472" value="<?= e($app['application_reference']) ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="personal_notes">Personal Notes (Private)</label>
                                <textarea id="personal_notes" name="personal_notes" class="form-textarea" placeholder="Add links, contacts, or draft essays..."><?= e($app['personal_notes']) ?></textarea>
                            </div>

                            <button type="submit" class="btn-submit">
                                <i data-lucide="save" style="width: 16px; height: 16px;"></i>
                                <span>Save Changes</span>
                            </button>
                        </form>

                        <form method="POST" action="/applications/<?= e($app['id']) ?>/delete" onsubmit="return confirm('Are you sure you want to remove this scholarship from your tracker?');">
                            <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                            <button type="submit" class="btn-danger-outline">
                                <i data-lucide="trash-2" style="width: 16px; height: 16px;"></i>
                                <span>Remove Tracker Entry</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script>
        lucide.createIcons();
        
        // Toggle submission date visibility
        const statusSelect = document.getElementById('status');
        const appliedAtGroup = document.getElementById('applied-at-group');
        statusSelect.addEventListener('change', function() {
            if (this.value === 'applied') {
                appliedAtGroup.style.display = 'block';
            } else {
                appliedAtGroup.style.display = 'none';
            }
        });
    </script>
</body>
</html>
