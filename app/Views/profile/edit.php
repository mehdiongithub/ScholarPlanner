<?php
$profileErrors = $_SESSION['profile_errors'] ?? [];
unset($_SESSION['profile_errors']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile | ScholarMatch</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
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
        .btn-danger {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fee2e2;
        }
        .btn-danger:hover {
            background: #fee2e2;
        }
        .profile-content {
            max-width: 1100px;
            width: 100%;
            margin: 40px auto;
            padding: 0 20px;
            flex-grow: 1;
        }
        .alert {
            padding: 12px 16px;
            border-radius: var(--radius-lg);
            font-size: 0.875rem;
            margin-bottom: 24px;
            line-height: 1.5;
        }
        .alert-danger {
            background: #fef2f2;
            border: 1px solid #fee2e2;
            color: #b91c1c;
        }
        .alert-success {
            background: #ecfdf5;
            border: 1px solid #d1fae5;
            color: #065f46;
        }
        .tab-menu {
            display: flex;
            gap: 8px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 32px;
            overflow-x: auto;
            padding-bottom: 4px;
        }
        .tab-btn {
            background: none;
            border: none;
            padding: 10px 16px;
            font-size: 0.9375rem;
            font-weight: 500;
            color: var(--text-500);
            cursor: pointer;
            border-bottom: 2px solid transparent;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        .tab-btn:hover {
            color: var(--primary);
        }
        .tab-btn.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
            font-weight: 600;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
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
            justify-content: space-between;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group.full-width {
            grid-column: span 2;
        }
        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-700);
            margin-bottom: 6px;
        }
        .form-input {
            width: 100%;
            padding: 10px 14px;
            font-size: 0.9375rem;
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            background: var(--bg-white);
            color: var(--text-900);
            transition: all 0.2s ease;
        }
        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }
        .field-error {
            font-size: 0.75rem;
            color: #b91c1c;
            margin-top: 4px;
            display: block;
        }
        .checkbox-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 12px;
        }
        .checkbox-card {
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 12px 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .checkbox-card:hover {
            background: #f8fafc;
        }
        .checkbox-card input {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }
        .checkbox-card span {
            font-size: 0.875rem;
            color: var(--text-700);
            font-weight: 500;
        }
        .education-list {
            margin-bottom: 24px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .education-row {
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            padding: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            background: var(--bg-white);
        }
        .education-row-info h4 {
            font-size: 0.9375rem;
            font-weight: 700;
            color: var(--text-900);
        }
        .education-row-info p {
            font-size: 0.8125rem;
            color: var(--text-500);
            margin-top: 4px;
        }
        .education-row-actions {
            display: flex;
            gap: 8px;
            flex-shrink: 0;
        }
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            .form-group.full-width {
                grid-column: span 1;
            }
            .education-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 14px;
            }
            .education-row-actions {
                width: 100%;
                justify-content: flex-end;
            }
        }
        @media (max-width: 280px) {
            .card {
                padding: 16px;
            }
            .tab-btn {
                padding: 8px 10px;
                font-size: 0.8125rem;
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
                <a href="<?= url('/profile') ?>" class="nav-link">My Profile</a>
                <a href="<?= url('/documents') ?>" class="nav-link">Documents</a>
                <a href="<?= url('/applications') ?>" class="nav-link">Applications</a>
            </div>
        </header>

        <main class="profile-content">
            <!-- Alerts -->
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success" role="alert">
                    <?= e($success_message) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($profileErrors['csrf'])): ?>
                <div class="alert alert-danger" role="alert">
                    <?= e($profileErrors['csrf']) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($profileErrors['unauthorized'])): ?>
                <div class="alert alert-danger" role="alert">
                    <?= e($profileErrors['unauthorized']) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($profileErrors['system'])): ?>
                <div class="alert alert-danger" role="alert">
                    <?= e($profileErrors['system']) ?>
                </div>
            <?php endif; ?>

            <!-- Tab Menu -->
            <div class="tab-menu" role="tablist">
                <button class="tab-btn active" onclick="switchTab('personal')" role="tab" aria-selected="true">
                    <i data-lucide="user"></i>
                    <span>Personal Info</span>
                </button>
                <button class="tab-btn" onclick="switchTab('education')" role="tab" aria-selected="false">
                    <i data-lucide="book-open"></i>
                    <span>Education History</span>
                </button>
                <button class="tab-btn" onclick="switchTab('preferences')" role="tab" aria-selected="false">
                    <i data-lucide="sliders"></i>
                    <span>Preferences & Alerts</span>
                </button>
            </div>

            <!-- Tab 1: Personal Info -->
            <div id="tab-personal" class="tab-content active">
                <div class="card">
                    <h2 class="card-title">Personal & Location Information</h2>
                    
                    <form action="<?= url('/profile/update') ?>" method="POST" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">

                        <div class="form-grid">
                            <div class="form-group">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" id="first_name" name="first_name" class="form-input" required 
                                       value="<?= e($user['first_name']) ?>">
                                <?php if (!empty($profileErrors['first_name'])): ?>
                                    <span class="field-error"><?= e($profileErrors['first_name']) ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" id="last_name" name="last_name" class="form-input" required 
                                       value="<?= e($user['last_name']) ?>">
                                <?php if (!empty($profileErrors['last_name'])): ?>
                                    <span class="field-error"><?= e($profileErrors['last_name']) ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" id="email" name="email" class="form-input" required 
                                       value="<?= e($user['email']) ?>">
                                <?php if (!empty($profileErrors['email'])): ?>
                                    <span class="field-error"><?= e($profileErrors['email']) ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="phone" class="form-label">Mobile Number</label>
                                <input type="tel" id="phone" name="phone" class="form-input" required 
                                       value="<?= e($user['phone']) ?>">
                                <?php if (!empty($profileErrors['phone'])): ?>
                                    <span class="field-error"><?= e($profileErrors['phone']) ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="date_of_birth" class="form-label">Date of Birth</label>
                                <input type="date" id="date_of_birth" name="date_of_birth" class="form-input" 
                                       value="<?= e($user['date_of_birth'] ?? '') ?>">
                                <?php if (!empty($profileErrors['date_of_birth'])): ?>
                                    <span class="field-error"><?= e($profileErrors['date_of_birth']) ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="gender" class="form-label">Gender</label>
                                <select id="gender" name="gender" class="form-input" style="height: auto;">
                                    <option value="">-- Select Gender --</option>
                                    <option value="male" <?= ($user['gender'] ?? '') === 'male' ? 'selected' : '' ?>>Male</option>
                                    <option value="female" <?= ($user['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
                                    <option value="other" <?= ($user['gender'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
                                </select>
                                <?php if (!empty($profileErrors['gender'])): ?>
                                    <span class="field-error"><?= e($profileErrors['gender']) ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="nationality_country_id" class="form-label">Nationality</label>
                                <select id="nationality_country_id" name="nationality_country_id" class="form-input" style="height: auto;">
                                    <option value="">-- Choose Country --</option>
                                    <?php foreach ($countries as $c): ?>
                                        <option value="<?= e($c['id']) ?>" <?= ($user['nationality_country_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                                            <?= e($c['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="residence_country_id" class="form-label">Country of Residence</label>
                                <select id="residence_country_id" name="residence_country_id" class="form-input" style="height: auto;">
                                    <option value="">-- Choose Country --</option>
                                    <?php foreach ($countries as $c): ?>
                                        <option value="<?= e($c['id']) ?>" <?= ($user['residence_country_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                                            <?= e($c['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="residence_state_id" class="form-label">State / Province</label>
                                <select id="residence_state_id" name="residence_state_id" class="form-input" style="height: auto;">
                                    <option value="">-- Select State --</option>
                                    <?php foreach ($states as $s): ?>
                                        <option value="<?= e($s['id']) ?>" <?= ($user['residence_state_id'] ?? '') == $s['id'] ? 'selected' : '' ?>>
                                            <?= e($s['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="city_id" class="form-label">City</label>
                                <select id="city_id" name="city_id" class="form-input" style="height: auto;">
                                    <option value="">-- Select City --</option>
                                    <?php foreach ($cities as $ct): ?>
                                        <option value="<?= e($ct['id']) ?>" <?= ($user['city_id'] ?? '') == $ct['id'] ? 'selected' : '' ?>>
                                            <?= e($ct['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group full-width">
                                <label for="bio" class="form-label">Short Bio</label>
                                <textarea id="bio" name="bio" class="form-input" rows="4" style="font-family: inherit;"><?= e($user['bio'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <button type="submit" class="btn-action btn-primary">Save Personal Info</button>
                    </form>
                </div>
            </div>

            <!-- Tab 2: Education History -->
            <div id="tab-education" class="tab-content">
                <!-- List existing records -->
                <div class="card">
                    <h2 class="card-title">My Academic Degrees</h2>
                    
                    <?php if (empty($education)): ?>
                        <p style="color: var(--text-500); font-size: 0.9375rem; text-align: center; padding: 10px 0;">
                            No degrees added. Fill the form below to add one.
                        </p>
                    <?php else: ?>
                        <div class="education-list">
                            <?php foreach ($education as $edu): ?>
                                <div class="education-row">
                                    <div class="education-row-info">
                                        <h4>
                                            <?= e($edu['degree_title']) ?> in <?= e($edu['field_of_study']) ?>
                                            <?php if ($edu['is_current']): ?>
                                                <span style="font-size: 0.6875rem; background: #ecfdf5; color: #047857; padding: 2px 6px; border-radius: var(--radius-sm); margin-left: 8px;">Current</span>
                                            <?php endif; ?>
                                        </h4>
                                        <p><?= e($edu['institution_name']) ?> (<?= !empty($edu['start_date']) ? date('Y', strtotime($edu['start_date'])) : 'N/A' ?> – <?= $edu['is_current'] ? 'Present' : (!empty($edu['end_date']) ? date('Y', strtotime($edu['end_date'])) : 'N/A') ?>)</p>
                                        <?php if ($edu['cgpa'] !== null): ?>
                                            <p style="font-size: 0.75rem; color: var(--text-500); margin-top: 4px;">CGPA: <strong><?= e($edu['cgpa']) ?> / <?= e($edu['cgpa_scale']) ?></strong></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="education-row-actions">
                                        <button class="btn-action btn-secondary" onclick="loadEduEditor(<?= e(json_encode($edu)) ?>)">Edit</button>
                                        <form action="<?= url('/profile/education/delete') ?>" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this education record?');">
                                            <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                                            <input type="hidden" name="id" value="<?= e($edu['id']) ?>">
                                            <button type="submit" class="btn-action btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Add/Edit form -->
                <div class="card">
                    <h2 class="card-title" id="edu-form-title">Add Academic Degree</h2>
                    
                    <?php if (!empty($profileErrors['education'])): ?>
                        <div class="alert alert-danger"><?= e($profileErrors['education']) ?></div>
                    <?php endif; ?>

                    <form id="edu-form" action="<?= url('/profile/education/add') ?>" method="POST" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                        <input type="hidden" id="edu_id" name="id" value="">

                        <div class="form-grid">
                            <div class="form-group">
                                <label for="institution_name" class="form-label">Institution Name</label>
                                <input type="text" id="institution_name" name="institution_name" class="form-input" required>
                                <?php if (!empty($profileErrors['institution_name'])): ?>
                                    <span class="field-error"><?= e($profileErrors['institution_name']) ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="degree_level" class="form-label">Degree Level</label>
                                <select id="degree_level" name="degree_level" class="form-input" style="height: auto;" required>
                                    <option value="">-- Select Level --</option>
                                    <option value="High School">High School</option>
                                    <option value="Diploma">Diploma</option>
                                    <option value="Associate Degree">Associate Degree</option>
                                    <option value="Bachelor's">Bachelor's</option>
                                    <option value="Master's">Master's</option>
                                    <option value="MPhil">MPhil</option>
                                    <option value="PhD">PhD</option>
                                    <option value="Postdoctoral">Postdoctoral</option>
                                </select>
                                <?php if (!empty($profileErrors['degree_level'])): ?>
                                    <span class="field-error"><?= e($profileErrors['degree_level']) ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="degree_title" class="form-label">Degree Title (e.g. BS, MS)</label>
                                <input type="text" id="degree_title" name="degree_title" class="form-input" required>
                                <?php if (!empty($profileErrors['degree_title'])): ?>
                                    <span class="field-error"><?= e($profileErrors['degree_title']) ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="field_of_study" class="form-label">Field of Study</label>
                                <input type="text" id="field_of_study" name="field_of_study" class="form-input" required>
                                <?php if (!empty($profileErrors['field_of_study'])): ?>
                                    <span class="field-error"><?= e($profileErrors['field_of_study']) ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="edu_country_id" class="form-label">Country of Institution</label>
                                <select id="edu_country_id" name="country_id" class="form-input" style="height: auto;">
                                    <option value="">-- Choose Country --</option>
                                    <?php foreach ($countries as $c): ?>
                                        <option value="<?= e($c['id']) ?>"><?= e($c['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="graduation_status" class="form-label">Graduation Status</label>
                                <select id="graduation_status" name="graduation_status" class="form-input" style="height: auto;">
                                    <option value="graduated">Graduated</option>
                                    <option value="ongoing">Ongoing</option>
                                    <option value="incomplete">Incomplete</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" id="start_date" name="start_date" class="form-input">
                                <?php if (!empty($profileErrors['start_date'])): ?>
                                    <span class="field-error"><?= e($profileErrors['start_date']) ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="end_date" class="form-label">End Date (Expected / Actual)</label>
                                <input type="date" id="end_date" name="end_date" class="form-input">
                            </div>

                            <div class="form-group">
                                <label for="cgpa" class="form-label">CGPA</label>
                                <input type="number" id="cgpa" name="cgpa" step="0.01" min="0" class="form-input">
                                <?php if (!empty($profileErrors['cgpa'])): ?>
                                    <span class="field-error"><?= e($profileErrors['cgpa']) ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="cgpa_scale" class="form-label">CGPA Scale (e.g. 4.0 or 10.0)</label>
                                <input type="number" id="cgpa_scale" name="cgpa_scale" step="0.1" min="0" class="form-input">
                                <?php if (!empty($profileErrors['cgpa_scale'])): ?>
                                    <span class="field-error"><?= e($profileErrors['cgpa_scale']) ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="percentage" class="form-label">Percentage (Optional)</label>
                                <input type="number" id="percentage" name="percentage" step="0.01" min="0" max="100" class="form-input">
                                <?php if (!empty($profileErrors['percentage'])): ?>
                                    <span class="field-error"><?= e($profileErrors['percentage']) ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="result_status" class="form-label">Result Status</label>
                                <select id="result_status" name="result_status" class="form-input" style="height: auto;">
                                    <option value="declared">Declared</option>
                                    <option value="awaited">Awaited</option>
                                </select>
                            </div>

                            <div class="form-group full-width">
                                <label class="checkbox-card" style="display:inline-flex;">
                                    <input type="checkbox" id="is_current" name="is_current" value="1">
                                    <span>Mark this degree as my current education</span>
                                </label>
                            </div>
                        </div>

                        <div style="display:flex; gap:12px;">
                            <button type="submit" class="btn-action btn-primary" id="btn-edu-save">Add Degree</button>
                            <button type="button" class="btn-action btn-secondary" id="btn-edu-cancel" style="display:none;" onclick="cancelEduEdit()">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tab 3: Preferences & Alerts -->
            <div id="tab-preferences" class="tab-content">
                <div class="card">
                    <h2 class="card-title">Scholarship Preferences & Notification Toggles</h2>
                    
                    <?php if (!empty($profileErrors['preferences'])): ?>
                        <div class="alert alert-danger"><?= e($profileErrors['preferences']) ?></div>
                    <?php endif; ?>

                    <form action="<?= url('/profile/preferences/update') ?>" method="POST" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">

                        <!-- Preferred study destinations -->
                        <div class="form-group">
                            <label class="form-label" style="font-weight: 700;">Preferred Study Countries (Select Multiple)</label>
                            <div class="checkbox-grid" style="margin-top: 10px;">
                                <?php foreach ($countries as $c): ?>
                                    <label class="checkbox-card">
                                        <input type="checkbox" name="preferred_countries[]" value="<?= e($c['id']) ?>"
                                               <?= in_array($c['id'], $prefCountries) ? 'checked' : '' ?>>
                                        <span><?= e($c['name']) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Preferred fields of study -->
                        <div class="form-group">
                            <label class="form-label" style="font-weight: 700;">Preferred Fields of Study (Select Multiple)</label>
                            <div class="checkbox-grid" style="margin-top: 10px;">
                                <?php foreach ($fieldsOfStudy as $f): ?>
                                    <label class="checkbox-card">
                                        <input type="checkbox" name="preferred_fields[]" value="<?= e($f['id']) ?>"
                                               <?= in_array($f['id'], $prefFields) ? 'checked' : '' ?>>
                                        <span><?= e($f['name']) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Target degree levels -->
                        <div class="form-group">
                            <label class="form-label" style="font-weight: 700;">Target Degree Levels (Select Multiple)</label>
                            <div class="checkbox-grid" style="margin-top: 10px;">
                                <?php 
                                $degreeLevelsList = ['High School', 'Diploma', 'Associate Degree', 'Bachelor\'s', 'Master\'s', 'MPhil', 'PhD', 'Postdoctoral'];
                                foreach ($degreeLevelsList as $lvl): 
                                ?>
                                    <label class="checkbox-card">
                                        <input type="checkbox" name="preferred_degrees[]" value="<?= e($lvl) ?>"
                                               <?= in_array($lvl, $prefDegrees) ? 'checked' : '' ?>>
                                        <span><?= e($lvl) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Alerts and Digests toggles -->
                        <div class="form-group" style="margin-top: 32px; border-top: 1px solid var(--border); padding-top: 24px;">
                            <label class="form-label" style="font-weight: 700; margin-bottom:16px;">Notification Alert Settings</label>
                            
                            <div style="display:flex; flex-direction:column; gap:14px;">
                                <label class="checkbox-card" style="border:none; padding:0;">
                                    <input type="checkbox" name="email_alerts" value="1" <?= ($notificationSettings['email_alerts']['email'] ?? false) ? 'checked' : '' ?>>
                                    <span>Receive scholarship match notifications via Email</span>
                                </label>

                                <label class="checkbox-card" style="border:none; padding:0;">
                                    <input type="checkbox" name="whatsapp_alerts" value="1" <?= ($notificationSettings['whatsapp_alerts']['whatsapp'] ?? false) ? 'checked' : '' ?>>
                                    <span>Receive instant match alerts on WhatsApp</span>
                                </label>

                                <label class="checkbox-card" style="border:none; padding:0;">
                                    <input type="checkbox" name="daily_alerts" value="1" <?= ($notificationSettings['daily_alerts']['email'] ?? false) ? 'checked' : '' ?>>
                                    <span>Opt-in to Daily Alert digests</span>
                                </label>

                                <label class="checkbox-card" style="border:none; padding:0;">
                                    <input type="checkbox" name="weekly_digest" value="1" <?= ($notificationSettings['weekly_digest']['email'] ?? false) ? 'checked' : '' ?>>
                                    <span>Opt-in to Weekly Digest summaries</span>
                                </label>

                                <label class="checkbox-card" style="border:none; padding:0;">
                                    <input type="checkbox" name="deadline_reminders" value="1" <?= ($notificationSettings['deadline_reminders']['email'] ?? false) ? 'checked' : '' ?>>
                                    <span>Enable automatic scholarship deadline reminders</span>
                                </label>

                                <label class="checkbox-card" style="border:none; padding:0;">
                                    <input type="checkbox" name="new_scholarship_alerts" value="1" <?= ($notificationSettings['new_scholarship_alerts']['email'] ?? false) ? 'checked' : '' ?>>
                                    <span>Alert me when any new scholarships are listed</span>
                                </label>

                                <label class="checkbox-card" style="border:none; padding:0;">
                                    <input type="checkbox" name="matching_scholarship_alerts" value="1" <?= ($notificationSettings['matching_scholarship_alerts']['email'] ?? false) ? 'checked' : '' ?>>
                                    <span>Alert me only when high-relevance matches are found</span>
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="btn-action btn-primary" style="margin-top: 24px;">Save Preferences</button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <!-- JavaScript logic -->
    <script>
        lucide.createIcons();

        // 1. Tab Switcher
        function switchTab(tabId) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));

            const targetBtn = Array.from(document.querySelectorAll('.tab-btn')).find(b => b.onclick.toString().includes(tabId));
            if(targetBtn) targetBtn.classList.add('active');
            
            const targetContent = document.getElementById('tab-' + tabId);
            if(targetContent) targetContent.classList.add('active');

            // Save active tab to session storage
            sessionStorage.setItem('active_profile_tab', tabId);
        }

        // Restore active tab on load
        window.addEventListener('DOMContentLoaded', () => {
            const activeTab = sessionStorage.getItem('active_profile_tab');
            if(activeTab) {
                switchTab(activeTab);
            }
        });

        // 2. AJAX Cascading Selection
        const countrySelect = document.getElementById('residence_country_id');
        const stateSelect = document.getElementById('residence_state_id');
        const citySelect = document.getElementById('city_id');

        if(countrySelect) {
            countrySelect.addEventListener('change', function() {
                const countryId = this.value;
                
                // Clear dropdowns
                stateSelect.innerHTML = '<option value="">-- Loading States --</option>';
                citySelect.innerHTML = '<option value="">-- Select City --</option>';

                if(!countryId) {
                    stateSelect.innerHTML = '<option value="">-- Select State --</option>';
                    return;
                }

                fetch('<?= url("/api/states?country_id=") ?>' + countryId)
                    .then(response => response.json())
                    .then(states => {
                        stateSelect.innerHTML = '<option value="">-- Select State --</option>';
                        states.forEach(s => {
                            stateSelect.innerHTML += `<option value="${s.id}">${s.name}</option>`;
                        });
                    })
                    .catch(err => {
                        stateSelect.innerHTML = '<option value="">-- Select State --</option>';
                    });
            });
        }

        if(stateSelect) {
            stateSelect.addEventListener('change', function() {
                const stateId = this.value;
                
                citySelect.innerHTML = '<option value="">-- Loading Cities --</option>';

                if(!stateId) {
                    citySelect.innerHTML = '<option value="">-- Select City --</option>';
                    return;
                }

                fetch('<?= url("/api/cities?state_id=") ?>' + stateId)
                    .then(response => response.json())
                    .then(cities => {
                        citySelect.innerHTML = '<option value="">-- Select City --</option>';
                        cities.forEach(c => {
                            citySelect.innerHTML += `<option value="${c.id}">${c.name}</option>`;
                        });
                    })
                    .catch(err => {
                        citySelect.innerHTML = '<option value="">-- Select City --</option>';
                    });
            });
        }

        // 3. Education Inline Editor Loader
        function loadEduEditor(edu) {
            // Change action to update
            document.getElementById('edu-form').action = '<?= url("/profile/education/update") ?>';
            document.getElementById('edu-form-title').innerText = 'Edit Academic Degree';
            document.getElementById('btn-edu-save').innerText = 'Save Changes';
            document.getElementById('btn-edu-cancel').style.display = 'inline-flex';

            // Fill inputs
            document.getElementById('edu_id').value = edu.id;
            document.getElementById('institution_name').value = edu.institution_name;
            document.getElementById('degree_level').value = edu.degree_level;
            document.getElementById('degree_title').value = edu.degree_title;
            document.getElementById('field_of_study').value = edu.field_of_study;
            document.getElementById('edu_country_id').value = edu.country_id || '';
            document.getElementById('graduation_status').value = edu.graduation_status;
            document.getElementById('start_date').value = edu.start_date || '';
            document.getElementById('end_date').value = edu.end_date || '';
            document.getElementById('cgpa').value = edu.cgpa || '';
            document.getElementById('cgpa_scale').value = edu.cgpa_scale || '';
            document.getElementById('percentage').value = edu.percentage || '';
            document.getElementById('result_status').value = edu.result_status;
            document.getElementById('is_current').checked = parseInt(edu.is_current) === 1;

            // Scroll to form
            document.getElementById('edu-form-title').scrollIntoView({ behavior: 'smooth' });
        }

        function cancelEduEdit() {
            // Restore action to add
            document.getElementById('edu-form').action = '<?= url("/profile/education/add") ?>';
            document.getElementById('edu-form-title').innerText = 'Add Academic Degree';
            document.getElementById('btn-edu-save').innerText = 'Add Degree';
            document.getElementById('btn-edu-cancel').style.display = 'none';

            // Reset inputs
            document.getElementById('edu_id').value = '';
            document.getElementById('edu-form').reset();
        }
    </script>
</body>
</html>
