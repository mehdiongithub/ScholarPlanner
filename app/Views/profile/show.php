<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | ScholarMatch</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@0.460.0"></script>
    <link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">
    <style>
        .profile-layout {
            min-height: 100vh;
            background: #f8fafc;
            display: flex;
            flex-direction: column;
        }
        .profile-header {
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
        .nav-link:hover {
            color: var(--primary);
        }
        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            font-size: 0.875rem;
            font-weight: 600;
            border-radius: var(--radius-lg);
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-primary {
            background: var(--primary);
            color: var(--bg-white);
            border: none;
        }
        .btn-primary:hover {
            background: var(--primary-dark, #1d4ed8);
        }
        .btn-secondary {
            background: var(--bg-white);
            color: var(--text-700);
            border: 1px solid var(--border);
        }
        .btn-secondary:hover {
            background: #f1f5f9;
        }
        .profile-content {
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
            padding: clamp(24px, 5vw, 32px);
            box-shadow: var(--shadow-sm);
            margin-bottom: 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 24px;
        }
        .banner-info {
            flex: 1;
            min-width: 280px;
        }
        .banner-name {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-900);
            margin-bottom: 8px;
        }
        .banner-email {
            color: var(--text-500);
            font-size: 0.9375rem;
            margin-bottom: 12px;
        }
        .completion-wrapper {
            width: 240px;
            flex-shrink: 0;
        }
        .completion-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--text-500);
            margin-bottom: 6px;
        }
        .completion-percent {
            color: var(--primary);
            font-size: 0.9375rem;
            font-weight: 700;
        }
        .progress-track {
            width: 100%;
            height: 10px;
            background: #e2e8f0;
            border-radius: var(--radius-full);
            overflow: hidden;
        }
        .progress-bar {
            height: 100%;
            background: var(--primary);
            border-radius: var(--radius-full);
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
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .info-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .info-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: 600;
            color: var(--text-400);
            letter-spacing: 0.05em;
        }
        .info-value {
            font-size: 0.9375rem;
            color: var(--text-800);
            font-weight: 500;
        }
        .bio-text {
            grid-column: span 2;
            font-size: 0.9375rem;
            color: var(--text-600);
            line-height: 1.6;
            margin-top: 8px;
        }
        .education-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .education-item {
            border-left: 3px solid var(--primary);
            padding-left: 20px;
            position: relative;
        }
        .education-item.current {
            border-left-color: #059669;
        }
        .edu-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: var(--radius-sm);
            font-size: 0.6875rem;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 6px;
        }
        .edu-badge-curr {
            background: #d1fae5;
            color: #065f46;
        }
        .edu-badge-deg {
            background: var(--primary-50);
            color: var(--primary);
        }
        .edu-title {
            font-size: 1.0625rem;
            font-weight: 700;
            color: var(--text-900);
            margin-bottom: 4px;
        }
        .edu-meta {
            font-size: 0.875rem;
            color: var(--text-500);
            margin-bottom: 8px;
        }
        .edu-stats {
            display: flex;
            gap: 20px;
            font-size: 0.8125rem;
            color: var(--text-600);
        }
        .preference-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 12px;
        }
        .pref-tag {
            background: #f1f5f9;
            color: var(--text-700);
            padding: 4px 10px;
            border-radius: var(--radius-md);
            font-size: 0.8125rem;
            font-weight: 500;
            border: 1px solid var(--border);
        }
        .pref-section {
            margin-bottom: 24px;
        }
        .pref-section:last-child {
            margin-bottom: 0;
        }
        .pref-section-title {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-600);
            margin-bottom: 8px;
        }
        .notify-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px dashed var(--border);
        }
        .notify-item:last-child {
            border-bottom: none;
        }
        .notify-label {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-700);
        }
        .notify-status {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: var(--radius-full);
        }
        .status-on {
            background: #ecfdf5;
            color: #047857;
        }
        .status-off {
            background: #f1f5f9;
            color: var(--text-500);
        }
        .document-checklist {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .doc-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 14px;
            border: 1px dashed var(--border);
            border-radius: var(--radius-lg);
            background: #fafafb;
        }
        .doc-name {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-700);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .doc-name i {
            color: var(--text-400);
            width: 16px;
            height: 16px;
        }
        .doc-badge {
            font-size: 0.6875rem;
            font-weight: 600;
            background: #fffbeb;
            color: #b45309;
            padding: 2px 6px;
            border-radius: var(--radius-sm);
        }
        @media (max-width: 992px) {
            .grid-layout {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 480px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
            .bio-text {
                grid-column: span 1;
            }
            .banner-card {
                flex-direction: column;
                align-items: flex-start;
            }
            .completion-wrapper {
                width: 100%;
            }
        }
        @media (max-width: 280px) {
            .profile-header {
                padding: 10px;
            }
            .nav-links {
                gap: 10px;
            }
            .card {
                padding: 16px;
            }
            .edu-stats {
                flex-direction: column;
                gap: 4px;
            }
        }
    </style>
</head>
<body>

    <div class="profile-layout">
        <header class="profile-header" role="banner">
            <a href="/" class="logo-box">
                <i data-lucide="graduation-cap"></i>
                <span>ScholarMatch</span>
            </a>
            
            <div class="nav-links">
                <a href="<?= url('/dashboard') ?>" class="nav-link">Dashboard</a>
                <a href="<?= url('/profile') ?>" class="nav-link active">My Profile</a>
                <a href="<?= url('/documents') ?>" class="nav-link">Documents</a>
                <a href="<?= url('/applications') ?>" class="nav-link">Applications</a>
                <a href="<?= url('/profile/edit') ?>" class="btn-action btn-primary">
                    <i data-lucide="edit-3"></i>
                    <span>Edit Profile</span>
                </a>
            </div>
        </header>

        <main class="profile-content">
            <!-- Banner summary card -->
            <div class="banner-card">
                <div class="banner-info">
                    <h1 class="banner-name"><?= e($user['first_name'] . ' ' . $user['last_name']) ?></h1>
                    <p class="banner-email">
                        <i data-lucide="mail" style="display:inline-block; width:14px; height:14px; vertical-align:middle; margin-right:4px;"></i>
                        <span><?= e($user['email']) ?></span>
                        <?php if ($age !== null): ?>
                            <span style="margin-left: 10px; padding-left: 10px; border-left: 1px solid var(--border);">Age: <strong><?= e($age) ?></strong></span>
                        <?php endif; ?>
                    </p>
                    <div>
                        <span class="pref-tag" style="background:#e0f2fe; color:#0369a1; border-color:#bae6fd;">Role: <?= e(ucfirst($user['role_name'])) ?></span>
                        <span class="pref-tag" style="background:#ecfdf5; color:#047857; border-color:#a7f3d0;">Status: <?= e(ucfirst($user['status'])) ?></span>
                    </div>
                </div>

                <div class="completion-wrapper" style="display: flex; flex-direction: column; gap: 16px; min-width: 250px;">
                    <div>
                        <div class="completion-header">
                            <span>Profile Completion</span>
                            <span class="completion-percent"><?= e($completion) ?>%</span>
                        </div>
                        <div class="progress-track">
                            <div class="progress-bar" style="width: <?= e($completion) ?>%;"></div>
                        </div>
                    </div>
                    <div>
                        <div class="completion-header">
                            <span>Document Readiness</span>
                            <span class="completion-percent"><?= e($docReadiness['readiness_percentage']) ?>%</span>
                        </div>
                        <div class="progress-track">
                            <div class="progress-bar" style="width: <?= e($docReadiness['readiness_percentage']) ?>%; background: #2563eb;"></div>
                        </div>
                        <p style="font-size: 0.75rem; color: var(--text-500); margin-top: 6px;">
                            <a href="<?= url('/documents') ?>" style="color: var(--primary); text-decoration: none; font-weight: 500;">Manage Documents &rarr;</a>
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid-layout">
                <!-- Left details column -->
                <div>
                    <!-- Personal info -->
                    <div class="card">
                        <h2 class="card-title">
                            <i data-lucide="user"></i>
                            <span>Personal Details</span>
                        </h2>

                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Gender</span>
                                <span class="info-value"><?= e(ucfirst($user['gender'] ?? 'Not set')) ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Date of Birth</span>
                                <span class="info-value"><?= !empty($user['date_of_birth']) ? e(date('M d, Y', strtotime($user['date_of_birth']))) : 'Not set' ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Nationality</span>
                                <span class="info-value"><?= e($user['nationality_country'] ?? 'Not set') ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Country of Residence</span>
                                <span class="info-value"><?= e($user['residence_country'] ?? 'Not set') ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">State / Province</span>
                                <span class="info-value"><?= e($user['residence_state'] ?? 'Not set') ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">City</span>
                                <span class="info-value"><?= e($user['city_name'] ?? 'Not set') ?></span>
                            </div>
                            <?php if (!empty($user['bio'])): ?>
                                <div class="bio-text">
                                    <span class="info-label" style="display:block; margin-bottom:4px;">Short Bio</span>
                                    <span><?= e($user['bio']) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Academic history -->
                    <div class="card">
                        <h2 class="card-title">
                            <i data-lucide="book-open"></i>
                            <span>Education History</span>
                        </h2>

                        <?php if (empty($education)): ?>
                            <p style="color: var(--text-500); font-size: 0.9375rem; text-align: center; padding: 20px 0;">
                                No academic records added yet. Click <strong>Edit Profile</strong> to add education.
                            </p>
                        <?php else: ?>
                            <div class="education-list">
                                <?php foreach ($education as $edu): ?>
                                    <div class="education-item <?= $edu['is_current'] ? 'current' : '' ?>">
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <span class="edu-badge edu-badge-deg"><?= e($edu['degree_level']) ?></span>
                                            <?php if ($edu['is_current']): ?>
                                                <span class="edu-badge edu-badge-curr">Current</span>
                                            <?php endif; ?>
                                        </div>
                                        <h3 class="edu-title"><?= e($edu['degree_title']) ?> in <?= e($edu['field_of_study']) ?></h3>
                                        <p class="edu-meta">
                                            <?= e($edu['institution_name']) ?> | <?= e($edu['country_name'] ?? '') ?>
                                        </p>
                                        <div class="edu-stats">
                                            <?php if ($edu['cgpa'] !== null): ?>
                                                <span>CGPA: <strong><?= e($edu['cgpa']) ?> / <?= e($edu['cgpa_scale']) ?></strong></span>
                                            <?php endif; ?>
                                            <?php if ($edu['percentage'] !== null): ?>
                                                <span>Percentage: <strong><?= e($edu['percentage']) ?>%</strong></span>
                                            <?php endif; ?>
                                            <span>
                                                Period: <?= !empty($edu['start_date']) ? e(date('Y', strtotime($edu['start_date']))) : 'N/A' ?> – 
                                                <?= $edu['is_current'] ? 'Present' : (!empty($edu['end_date']) ? e(date('Y', strtotime($edu['end_date']))) : 'N/A') ?>
                                            </span>
                                            <span>Status: <strong><?= e(ucfirst($edu['graduation_status'])) ?></strong></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right sidebar preferences column -->
                <div>
                    <!-- Study preferences -->
                    <div class="card">
                        <h2 class="card-title">
                            <i data-lucide="sliders"></i>
                            <span>Preferences</span>
                        </h2>

                        <!-- Preferred Countries -->
                        <div class="pref-section">
                            <h3 class="pref-section-title">Preferred Study Destinations</h3>
                            <?php if (empty($prefCountries)): ?>
                                <p style="font-size: 0.8125rem; color: var(--text-400);">No countries selected.</p>
                            <?php else: ?>
                                <div class="preference-tags">
                                    <?php foreach ($prefCountries as $c): ?>
                                        <span class="pref-tag"><?= e($c['name']) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Preferred Fields -->
                        <div class="pref-section">
                            <h3 class="pref-section-title">Preferred Fields</h3>
                            <?php if (empty($prefFields)): ?>
                                <p style="font-size: 0.8125rem; color: var(--text-400);">No fields selected.</p>
                            <?php else: ?>
                                <div class="preference-tags">
                                    <?php foreach ($prefFields as $f): ?>
                                        <span class="pref-tag"><?= e($f['name']) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Preferred Degrees -->
                        <div class="pref-section">
                            <h3 class="pref-section-title">Target Degrees</h3>
                            <?php if (empty($prefDegrees)): ?>
                                <p style="font-size: 0.8125rem; color: var(--text-400);">No levels selected.</p>
                            <?php else: ?>
                                <div class="preference-tags">
                                    <?php foreach ($prefDegrees as $lvl): ?>
                                        <span class="pref-tag"><?= e($lvl) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Alerts preferences -->
                    <div class="card">
                        <h2 class="card-title">
                            <i data-lucide="bell"></i>
                            <span>Alert Settings</span>
                        </h2>

                        <div class="notify-item">
                            <span class="notify-label">Email Notifications</span>
                            <span class="notify-status <?= ($notificationSettings['email_alerts']['email'] ?? false) ? 'status-on' : 'status-off' ?>">
                                <?= ($notificationSettings['email_alerts']['email'] ?? false) ? 'ON' : 'OFF' ?>
                            </span>
                        </div>

                        <div class="notify-item">
                            <span class="notify-label">WhatsApp Notifications</span>
                            <span class="notify-status <?= ($notificationSettings['whatsapp_alerts']['whatsapp'] ?? false) ? 'status-on' : 'status-off' ?>">
                                <?= ($notificationSettings['whatsapp_alerts']['whatsapp'] ?? false) ? 'ON' : 'OFF' ?>
                            </span>
                        </div>

                        <div class="notify-item">
                            <span class="notify-label">Daily Updates Frequency</span>
                            <span class="notify-status <?= ($notificationSettings['daily_alerts']['email'] ?? false) ? 'status-on' : 'status-off' ?>">
                                <?= ($notificationSettings['daily_alerts']['email'] ?? false) ? 'ON' : 'OFF' ?>
                            </span>
                        </div>

                        <div class="notify-item">
                            <span class="notify-label">Weekly Summaries</span>
                            <span class="notify-status <?= ($notificationSettings['weekly_digest']['email'] ?? false) ? 'status-on' : 'status-off' ?>">
                                <?= ($notificationSettings['weekly_digest']['email'] ?? false) ? 'ON' : 'OFF' ?>
                            </span>
                        </div>

                        <div class="notify-item">
                            <span class="notify-label">Deadline Reminders</span>
                            <span class="notify-status <?= ($notificationSettings['deadline_reminders']['email'] ?? false) ? 'status-on' : 'status-off' ?>">
                                <?= ($notificationSettings['deadline_reminders']['email'] ?? false) ? 'ON' : 'OFF' ?>
                            </span>
                        </div>
                    </div>

                    <!-- Document checklist placeholder -->
                    <div class="card">
                        <h2 class="card-title">
                            <i data-lucide="file-text"></i>
                            <span>Documents (Coming in Step 8)</span>
                        </h2>

                        <div class="document-checklist">
                            <div class="doc-item">
                                <span class="doc-name">
                                    <i data-lucide="file"></i>
                                    <span>Passport</span>
                                </span>
                                <span class="doc-badge">Not uploaded</span>
                            </div>
                            <div class="doc-item">
                                <span class="doc-name">
                                    <i data-lucide="file"></i>
                                    <span>Academic Transcript</span>
                                </span>
                                <span class="doc-badge">Not uploaded</span>
                            </div>
                            <div class="doc-item">
                                <span class="doc-name">
                                    <i data-lucide="file"></i>
                                    <span>Degree Certificate</span>
                                </span>
                                <span class="doc-badge">Not uploaded</span>
                            </div>
                            <div class="doc-item">
                                <span class="doc-name">
                                    <i data-lucide="file"></i>
                                    <span>Curriculum Vitae (CV)</span>
                                </span>
                                <span class="doc-badge">Not uploaded</span>
                            </div>
                            <div class="doc-item">
                                <span class="doc-name">
                                    <i data-lucide="file"></i>
                                    <span>Recommendation Letter</span>
                                </span>
                                <span class="doc-badge">Not uploaded</span>
                            </div>
                        </div>
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
