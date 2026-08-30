<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Application | Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@0.460.0"></script>
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

        .alert-error {
            background: #fff5f5;
            border: 1px solid #fed7d7;
            padding: 16px;
            border-radius: var(--radius-lg);
            margin-bottom: 24px;
            color: #c53030;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="detail-layout">
        <header class="detail-header" role="banner">
            <a href="/" class="logo-box">
                <i data-lucide="graduation-cap"></i>
                <span>ScholarMatch Admin</span>
            </a>
            
            <div class="nav-links">
                <a href="/admin/scholarships" class="nav-link">Scholarships</a>
                <a href="/admin/notifications" class="nav-link">Notifications</a>
                <a href="/admin/documents" class="nav-link">Documents</a>
                <a href="/admin/applications" class="nav-link active">Applications</a>
            </div>
            
            <div style="font-size: 0.875rem; font-weight: 600; color: var(--text-800);">
                Staff Panel
            </div>
        </header>

        <main class="detail-content">
            <a href="/admin/applications" class="back-link">
                <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i>
                <span>Back to Applications</span>
            </a>

            <!-- Error message -->
            <?php if (isset($_SESSION['admin_app_error'])): ?>
                <div class="alert-error" role="alert">
                    <?= e($_SESSION['admin_app_error']) ?>
                    <?php unset($_SESSION['admin_app_error']); ?>
                </div>
            <?php endif; ?>

            <div style="margin-bottom: 32px; display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 20px;">
                <div>
                    <h1 style="font-size: 2rem; font-weight: 700; color: var(--text-900); margin-bottom: 8px;"><?= e($app['scholarship_title']) ?></h1>
                    <p style="color: var(--text-500); font-size: 1rem; display: flex; align-items: center; gap: 8px;">
                        <i data-lucide="user" style="width: 18px; height: 18px;"></i>
                        <span>Applicant: <strong><?= e($app['first_name']) ?> <?= e($app['last_name']) ?></strong> (<?= e($app['user_email']) ?>)</span>
                    </p>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 0.875rem; color: var(--text-500); margin-bottom: 6px;">Ref Number: <strong><?= $app['application_reference'] ? e($app['application_reference']) : 'None' ?></strong></div>
                    <span class="badge-status status-<?= e($app['status']) ?>"><?= str_replace('_', ' ', e($app['status'])) ?></span>
                </div>
            </div>

            <div class="grid-layout">
                <!-- Left panel -->
                <div>
                    <!-- Document integration checklist -->
                    <div class="card-panel">
                        <h2 class="panel-title">
                            <i data-lucide="check-square"></i>
                            <span>Applicant Documents Verification Checklist</span>
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
                                        <?php if ($doc['status'] !== 'missing'): ?>
                                            <a href="/admin/documents" class="btn-action" style="font-size: 0.75rem; font-weight: 600; padding: 4px 10px; border-radius: 9999px;">View File</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Application status history logs -->
                    <div class="card-panel">
                        <h2 class="panel-title">
                            <i data-lucide="history"></i>
                            <span>Application Tracking History Timeline</span>
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

                    <!-- Communication Center / Notifications History -->
                    <div class="card-panel">
                        <h2 class="panel-title">
                            <i data-lucide="mail"></i>
                            <span>Communication Center (Notification Logs)</span>
                        </h2>

                        <?php if (empty($notifications)): ?>
                            <p style="color: var(--text-500); font-size: 0.875rem;">No notification updates have been sent for this application yet.</p>
                        <?php else: ?>
                            <div style="display: flex; flex-direction: column; gap: 12px;">
                                <?php foreach ($notifications as $n): ?>
                                    <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 12px 16px; font-size: 0.875rem;">
                                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px; flex-wrap: wrap; gap: 8px;">
                                            <span style="font-weight: 600; color: var(--text-800); text-transform: uppercase; font-size: 0.75rem; background: #e2e8f0; padding: 2px 8px; border-radius: 4px;">
                                                <?= e(str_replace('_', ' ', $n['notification_type'])) ?>
                                            </span>
                                            <span style="font-size: 0.75rem; color: var(--text-400);"><?= e($n['created_at']) ?></span>
                                        </div>
                                        <div style="font-weight: 500; color: var(--text-700); margin-bottom: 4px;"><?= e($n['subject']) ?></div>
                                        <div style="font-size: 0.8125rem; color: var(--text-500);">
                                            <strong>Channel:</strong> <?= ucfirst(e($n['channel'])) ?> &bull; 
                                            <strong>Recipient:</strong> <?= e($n['recipient']) ?> &bull;
                                            <strong>Status:</strong> 
                                            <span style="font-weight: 600; color: <?= $n['status'] === 'sent' ? '#16a34a' : ($n['status'] === 'failed' ? '#dc2626' : '#d97706') ?>;">
                                                <?= ucfirst(e($n['status'])) ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right sidebar panel -->
                <div>
                    <!-- Private Notes (Masked for privacy) -->
                    <div class="card-panel" style="background: #fafafa; border-style: dashed;">
                        <h2 class="panel-title" style="border: none; padding: 0; margin-bottom: 12px; font-size: 1.1rem; color: var(--text-700);">
                            <i data-lucide="lock" style="color: var(--text-400);"></i>
                            <span>Private Notes</span>
                        </h2>
                        <p style="font-size: 0.8125rem; font-family: monospace; color: var(--text-500); margin: 0; word-break: break-all;">
                            <?= e($app['personal_notes']) ?>
                        </p>
                    </div>

                    <!-- Review Actions Form -->
                    <div class="card-panel">
                        <h2 class="panel-title" style="border: none; padding: 0; margin-bottom: 16px;">
                            <i data-lucide="sliders"></i>
                            <span>Admin Action Review</span>
                        </h2>

                        <form method="POST" action="/admin/applications/<?= e($app['id']) ?>/status">
                            <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">

                            <div class="form-group">
                                <label class="form-label" for="status">Transition Status</label>
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

                            <div class="form-group">
                                <label class="form-label" for="notes">Review Note / Decision Reason (Student Visible)</label>
                                <textarea id="notes" name="notes" class="form-textarea" placeholder="Add decision explanation (this is visible to the applicant)..."></textarea>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="internal_notes">Staff-Only Internal Notes (Private to Staff)</label>
                                <textarea id="internal_notes" name="internal_notes" class="form-textarea" placeholder="Private internal staff-only notes..."><?= e($app['internal_notes'] ?? '') ?></textarea>
                            </div>

                            <button type="submit" class="btn-submit">
                                <i data-lucide="check" style="width: 16px; height: 16px;"></i>
                                <span>Save Decision</span>
                            </button>
                        </form>
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
