<?php
$profileErrors = $_SESSION['profile_errors'] ?? [];
unset($_SESSION['profile_errors']);

// Toast session check
$verifySuccessToast = $_SESSION['verify_success_toast'] ?? null;
unset($_SESSION['verify_success_toast']);

$currentCompletion = $user['profile_completion_percentage'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Profile Onboarding | ScholarMatch</title>
    
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- Global CSS -->
    <link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">
    
    <!-- External Dependencies (jQuery, Select2, Flatpickr) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

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
        .profile-content {
            max-width: 900px;
            width: 100%;
            margin: 40px auto;
            padding: 0 20px;
            flex-grow: 1;
        }
        
        /* Wizard Tracker Styles */
        .wizard-progress-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            position: relative;
            background: #fff;
            padding: 18px 24px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid var(--border);
            flex-wrap: wrap;
            gap: 12px;
        }
        .step-indicator-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-400);
            position: relative;
            z-index: 2;
        }
        .step-indicator-item.active {
            color: var(--primary);
            font-weight: 700;
        }
        .step-indicator-item.completed {
            color: #059669;
        }
        .step-number {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            border: 2px solid var(--text-300);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 700;
            background: #fff;
            transition: all 0.2s;
        }
        .step-indicator-item.active .step-number {
            border-color: var(--primary);
            background: var(--primary);
            color: #fff;
        }
        .step-indicator-item.completed .step-number {
            border-color: #059669;
            background: #059669;
            color: #fff;
        }
        
        /* Wizard panel styles */
        .wizard-step-panel {
            display: none;
        }
        .wizard-step-panel.active {
            display: block;
        }
        .card {
            background: #fff;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.02);
            border: 1px solid var(--border);
            margin-bottom: 24px;
        }
        .card-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-900);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .card-subtitle {
            font-size: 0.875rem;
            color: var(--text-500);
            margin-bottom: 24px;
        }
        
        /* Select2 global style overrides - Fixes narrow dropdowns */
        .select2-container {
            width: 100% !important;
        }
        .select2-container .select2-selection--single,
        .select2-container--default .select2-selection--multiple {
            border: 1px solid var(--border) !important;
            border-radius: 8px !important;
            min-height: 42px !important;
            padding: 4px 8px !important;
            font-size: 0.875rem !important;
            font-family: inherit !important;
            box-shadow: none !important;
            width: 100% !important;
            display: flex !important;
            align-items: center !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px !important;
            right: 8px !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 34px !important;
            padding-left: 0 !important;
            color: var(--text-800) !important;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__rendered {
            display: flex !important;
            flex-wrap: wrap !important;
            gap: 4px !important;
            padding: 0 !important;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: var(--primary-light, #eff6ff) !important;
            border: 1px solid var(--primary-border, #bfdbfe) !important;
            color: var(--primary, #2563eb) !important;
            border-radius: 4px !important;
            padding: 2px 6px !important;
            margin: 0 !important;
            font-size: 0.75rem !important;
            font-weight: 500 !important;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: var(--primary, #2563eb) !important;
            margin-right: 4px !important;
        }
        
        /* Field layouts */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        .form-group.full-width {
            grid-column: span 2;
        }
        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-700);
            margin-bottom: 6px;
        }
        .form-input {
            width: 100%;
            padding: 10px 14px;
            font-size: 0.875rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: #fff;
            transition: all 0.2s;
            min-height: 42px;
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
        
        /* Floating Toast style */
        .toast-notification {
            position: fixed;
            top: 24px;
            right: 24px;
            background: #ecfdf5;
            border: 1px solid #d1fae5;
            color: #065f46;
            padding: 16px 20px;
            border-radius: 10px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            gap: 12px;
            z-index: 9999;
            transition: all 0.3s ease;
        }
        .toast-close {
            background: none;
            border: none;
            color: #047857;
            cursor: pointer;
            padding: 0;
            display: flex;
            align-items: center;
        }
        
        /* Buttons */
        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            font-size: 0.875rem;
            font-weight: 600;
            border-radius: 8px;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s;
            min-height: 42px;
        }
        .btn-primary {
            background: var(--primary);
            color: #fff;
            border: none;
        }
        .btn-primary:hover {
            background: #1d4ed8;
        }
        .btn-secondary {
            background: #fff;
            color: var(--text-700);
            border: 1px solid var(--border);
        }
        .btn-secondary:hover {
            background: #f8fafc;
        }
        .btn-danger {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fee2e2;
        }
        .btn-danger:hover {
            background: #fee2e2;
        }
        
        /* Education cards and badges */
        .education-row {
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            background: #fff;
        }
        .education-list {
            margin-bottom: 24px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        /* Responsive scaling down to 280px */
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
            }
            .education-row-actions {
                width: 100%;
                justify-content: flex-end;
            }
        }
        
        @media (max-width: 480px) {
            .wizard-progress-bar {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            .card {
                padding: 20px;
            }
        }
        
        @media (max-width: 280px) {
            .profile-content {
                padding: 0 10px;
            }
            .card {
                padding: 15px;
            }
            .btn-action {
                width: 100%;
                justify-content: center;
            }
            .step-indicator-item {
                font-size: 0.75rem;
            }
        }
    </style>
</head>
<body>

    <!-- Email Verification Toast Alert -->
    <?php if (!empty($verifySuccessToast)): ?>
        <div class="toast-notification" id="verify-toast">
            <i data-lucide="check-circle" style="width: 20px; height: 20px; color: #10b981;"></i>
            <span style="font-size: 0.875rem; font-weight: 500;"><?= e($verifySuccessToast) ?></span>
            <button class="toast-close" onclick="document.getElementById('verify-toast').remove()">
                <i data-lucide="x" style="width: 16px; height: 16px;"></i>
            </button>
        </div>
        <script>
            setTimeout(() => {
                const t = document.getElementById('verify-toast');
                if (t) {
                    t.style.opacity = '0';
                    t.style.transform = 'translateY(-10px)';
                    setTimeout(() => t.remove(), 300);
                }
            }, 5000);
        </script>
    <?php endif; ?>

    <div class="profile-layout">
        <header class="profile-header" role="banner">
            <a href="/" class="logo-box">
                <i data-lucide="graduation-cap"></i>
                <span>ScholarMatch</span>
            </a>
            
            <div class="nav-links">
                <a href="<?= url('/dashboard') ?>" class="nav-link">Dashboard</a>
                <a href="<?= url('/profile') ?>" class="nav-link">My Profile</a>
                <a href="<?= url('/logout') ?>" class="nav-link" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Log Out</a>
            </div>
            <form id="logout-form" action="<?= url('/logout') ?>" method="POST" style="display: none;">
                <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
            </form>
        </header>

        <main class="profile-content">
            
            <!-- Wizard Step Indicators -->
            <nav class="wizard-progress-bar" aria-label="Onboarding Progress">
                <div class="step-indicator-item active" id="indicator-1">
                    <span class="step-number">1</span>
                    <span>Personal Info</span>
                </div>
                <div class="step-indicator-item" id="indicator-2">
                    <span class="step-number">2</span>
                    <span>Education</span>
                </div>
                <div class="step-indicator-item" id="indicator-3">
                    <span class="step-number">3</span>
                    <span>Preferences</span>
                </div>
                <div class="step-indicator-item" id="indicator-4">
                    <span class="step-number">4</span>
                    <span>Review & Complete</span>
                </div>
            </nav>

            <!-- Dynamic Completion Progress Bar -->
            <div style="margin-bottom: 24px; background:#fff; padding:15px; border-radius:8px; border:1px solid var(--border); box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                <div style="display:flex; justify-content:space-between; margin-bottom:6px; font-size:0.875rem; font-weight:600; color:var(--text-700);">
                    <span>Profile Onboarding Progress</span>
                    <span id="completion-value"><?= e($currentCompletion) ?>%</span>
                </div>
                <div style="background:#e2e8f0; height:8px; border-radius:4px; overflow:hidden;">
                    <div id="completion-fill" style="background:var(--primary); height:100%; width: <?= e($currentCompletion) ?>%; transition: width 0.4s ease;"></div>
                </div>
            </div>

            <!-- STEP 1 PANEL: Personal Details -->
            <section class="wizard-step-panel active" id="step-1" aria-labelledby="step1-title">
                <div class="card">
                    <h2 class="card-title" id="step1-title"><i data-lucide="user"></i> Personal & Location Details</h2>
                    <p class="card-subtitle">Please fill out your identity and residency locations to customize your scholarship match parameters.</p>
                    
                    <form id="personal-info-form" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">

                        <div class="form-grid">
                            <div class="form-group">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" id="first_name" name="first_name" class="form-input" required value="<?= e($user['first_name'] ?? '') ?>">
                            </div>

                            <div class="form-group">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" id="last_name" name="last_name" class="form-input" required value="<?= e($user['last_name'] ?? '') ?>">
                            </div>

                            <div class="form-group">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" id="email" name="email" class="form-input" readonly disabled value="<?= e($user['email'] ?? '') ?>">
                            </div>

                            <div class="form-group">
                                <label for="phone" class="form-label">Mobile Number</label>
                                <input type="tel" id="phone" name="phone" class="form-input" placeholder="+923001234567" required value="<?= e($user['phone'] ?? '') ?>">
                            </div>

                            <div class="form-group">
                                <label for="date_of_birth" class="form-label">Date of Birth</label>
                                <input type="text" id="date_of_birth" name="date_of_birth" class="form-input">
                            </div>

                            <div class="form-group">
                                <label for="gender" class="form-label">Gender</label>
                                <select id="gender" name="gender" class="form-input select-search">
                                    <option value="">-- Select Gender --</option>
                                    <option value="male" <?= ($user['gender'] ?? '') === 'male' ? 'selected' : '' ?>>Male</option>
                                    <option value="female" <?= ($user['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
                                    <option value="other" <?= ($user['gender'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="nationality_country_id" class="form-label">Nationality</label>
                                <select id="nationality_country_id" name="nationality_country_id" class="form-input select-search">
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
                                <select id="residence_country_id" name="residence_country_id" class="form-input select-search">
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
                                <select id="residence_state_id" name="residence_state_id" class="form-input select-search">
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
                                <select id="city_id" name="city_id" class="form-input select-search">
                                    <option value="">-- Select City --</option>
                                    <?php foreach ($cities as $ct): ?>
                                        <option value="<?= e($ct['id']) ?>" <?= ($user['city_id'] ?? '') == $ct['id'] ? 'selected' : '' ?>>
                                            <?= e($ct['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Address and Postal Code Fields -->
                            <div class="form-group">
                                <label for="address" class="form-label">Address</label>
                                <input type="text" id="address" name="address" class="form-input" placeholder="123 Street Name" value="<?= e($user['address'] ?? '') ?>">
                            </div>

                            <div class="form-group">
                                <label for="postal_code" class="form-label">Postal Code</label>
                                <input type="text" id="postal_code" name="postal_code" class="form-input" placeholder="75500" value="<?= e($user['postal_code'] ?? '') ?>">
                            </div>

                            <div class="form-group full-width">
                                <label for="bio" class="form-label">Short Bio (Describe your goals)</label>
                                <textarea id="bio" name="bio" class="form-input" rows="3" style="font-family: inherit; height:auto;"><?= e($user['bio'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <div style="display:flex; justify-content:flex-end; margin-top:30px;">
                            <button type="button" class="btn-action btn-primary" onclick="nextStep1()">Next: Academic History <i data-lucide="arrow-right"></i></button>
                        </div>
                    </form>
                </div>
            </section>

            <!-- STEP 2 PANEL: Academic History -->
            <section class="wizard-step-panel" id="step-2" aria-labelledby="step2-title">
                <div class="card">
                    <h2 class="card-title" id="step2-title"><i data-lucide="book-open"></i> Academic Degrees & Records</h2>
                    <p class="card-subtitle">Provide your education history. We match scholarships based on your degree level, grades, and universities.</p>
                    
                    <!-- Dynamic Education Cards List -->
                    <div id="education-cards-list" class="education-list">
                        <!-- Loaded dynamically via AJAX -->
                    </div>
                </div>

                <!-- Academic Form card (collapsible/reset state) -->
                <div class="card">
                    <h3 class="card-title" id="edu-form-title">Add Academic Degree</h3>
                    
                    <form id="edu-form" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                        <input type="hidden" id="edu_id" name="id" value="">

                        <div class="form-grid">
                            <!-- Initial Questions (Step 2 Onboarding Entry) -->
                            <div class="form-group">
                                <label for="institution_type" class="form-label">Institution Type</label>
                                <select id="institution_type" name="institution_type" class="form-input select-search" required>
                                    <option value="">-- Choose Type --</option>
                                    <option value="university">University</option>
                                    <option value="college">College / Intermediate</option>
                                    <option value="school">School</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="degree_level" class="form-label">Degree Level</label>
                                <select id="degree_level" name="degree_level" class="form-input select-search" required>
                                    <option value="">-- Choose Level --</option>
                                    <option value="High School">High School</option>
                                    <option value="Diploma">Diploma</option>
                                    <option value="Associate Degree">Associate Degree</option>
                                    <option value="Bachelor's">Bachelor's</option>
                                    <option value="Master's">Master's</option>
                                    <option value="MPhil">MPhil</option>
                                    <option value="PhD">PhD</option>
                                    <option value="Postdoctoral">Postdoctoral</option>
                                </select>
                            </div>

                            <!-- Cascading University Location Fields Group -->
                            <div class="form-group" id="edu_country_group" style="display:none;">
                                <label for="edu_country_id" class="form-label">Country of Institution</label>
                                <select id="edu_country_id" name="country_id" class="form-input select-search">
                                    <option value="">-- Select Country --</option>
                                    <?php foreach ($countries as $c): ?>
                                        <option value="<?= e($c['id']) ?>"><?= e($c['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group" id="edu_state_group" style="display:none;">
                                <label for="edu_state_id" class="form-label">State / Province</label>
                                <select id="edu_state_id" name="state_id" class="form-input select-search">
                                    <option value="">-- Select State --</option>
                                </select>
                            </div>

                            <div class="form-group" id="edu_city_group" style="display:none;">
                                <label for="edu_city_id" class="form-label">City (Optional)</label>
                                <select id="edu_city_id" name="city_id" class="form-input select-search">
                                    <option value="">-- Select City --</option>
                                </select>
                            </div>

                            <!-- Autocomplete Selector Group -->
                            <div class="form-group" id="institution_select_group" style="display:none;">
                                <label for="institution_select" class="form-label" id="institution_select_label">Institution Name</label>
                                <select id="institution_select" name="institution_select" class="form-input select-search">
                                    <option value="">-- Search --</option>
                                </select>
                            </div>

                            <!-- Custom/Text Entry Fallback Input (Full line width) -->
                            <div class="form-group full-width" id="custom_institution_group" style="display:none;">
                                <label for="custom_institution_name" class="form-label" id="custom_institution_label">Institution Name</label>
                                <input type="text" id="custom_institution_name" name="custom_institution_name" class="form-input" placeholder="Enter full name of your institution">
                            </div>


                            <!-- Dynamic Academic Details Group -->
                            <div class="form-group full-width" style="display:none;" id="academic_fields_group">
                                <div class="form-grid" style="margin-top:20px; border-top: 1px solid var(--border); padding-top:20px;">
                                    
                                    <div class="form-group" id="degree_title_group">
                                        <label for="degree_title" class="form-label">Degree / Program Title (e.g., BS CS, FSc Pre-Med)</label>
                                        <input type="text" id="degree_title" name="degree_title" class="form-input">
                                    </div>

                                    <div class="form-group">
                                        <label for="field_of_study" class="form-label">Field of Study</label>
                                        <select id="field_of_study" name="field_of_study" class="form-input select-search">
                                            <option value="">-- Choose Field --</option>
                                            <?php foreach ($fieldsOfStudy as $f): ?>
                                                <option value="<?= e($f['name']) ?>"><?= e($f['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="graduation_status" class="form-label">Graduation Status</label>
                                        <select id="graduation_status" name="graduation_status" class="form-input select-search">
                                            <option value="graduated">Graduated</option>
                                            <option value="ongoing">Currently Studying</option>
                                            <option value="incomplete">Incomplete</option>
                                        </select>
                                    </div>

                                    <!-- Current Semester Selector (Ongoing / University) -->
                                    <div class="form-group" id="current_semester_group" style="display:none;">
                                        <label for="current_semester" class="form-label">Current Semester / Research Phase</label>
                                        <select id="current_semester" name="current_semester" class="form-input select-search">
                                            <option value="">-- Choose Semester --</option>
                                            <option value="1">1st Semester</option>
                                            <option value="2">2nd Semester</option>
                                            <option value="3">3rd Semester</option>
                                            <option value="4">4th Semester</option>
                                            <option value="5">5th Semester</option>
                                            <option value="6">6th Semester</option>
                                            <option value="7">7th Semester</option>
                                            <option value="8">8th Semester</option>
                                            <option value="9">9th Semester</option>
                                            <option value="10">10th Semester</option>
                                            <option value="Coursework">Coursework</option>
                                            <option value="Research">Research Phase</option>
                                            <option value="Thesis">Thesis Writing</option>
                                            <option value="Dissertation">Dissertation</option>
                                            <option value="Final Stage">Final Stage</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="start_date" class="form-label" id="start_date_label">Start Date</label>
                                        <input type="text" id="start_date" name="start_date" class="form-input">
                                    </div>

                                    <div class="form-group">
                                        <label for="end_date" class="form-label" id="end_date_label">End Date / Graduation Date</label>
                                        <input type="text" id="end_date" name="end_date" class="form-input">
                                    </div>

                                    <!-- passing_year dropdown selector -->
                                    <div class="form-group" id="passing_year_group" style="display:none;">
                                        <label for="passing_year" class="form-label" id="passing_year_label">Graduation / Passing Year</label>
                                        <select id="passing_year" name="passing_year" class="form-input select-search">
                                            <option value="">-- Choose Year --</option>
                                            <?php 
                                            $currYr = (int)date('Y');
                                            for ($y = $currYr + 10; $y >= $currYr - 50; $y--):
                                            ?>
                                                <option value="<?= $y ?>"><?= $y ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="result_status" class="form-label">Result Type</label>
                                        <select id="result_status" name="result_status" class="form-input select-search">
                                            <option value="cgpa">CGPA</option>
                                            <option value="percentage">Percentage</option>
                                            <option value="awaited">Result Awaited</option>
                                        </select>
                                    </div>

                                    <!-- CGPA Inputs -->
                                    <div class="form-group" id="cgpa_fields_group">
                                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                                            <div>
                                                <label for="cgpa" class="form-label">Obtained CGPA</label>
                                                <input type="number" id="cgpa" name="cgpa" step="0.01" min="0" class="form-input" placeholder="3.5">
                                            </div>
                                            <div>
                                                <label for="cgpa_scale" class="form-label">CGPA Scale</label>
                                                <input type="number" id="cgpa_scale" name="cgpa_scale" step="0.1" min="0" class="form-input" placeholder="4.0">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Percentage Input -->
                                    <div class="form-group" id="percentage_field_group" style="display:none;">
                                        <label for="percentage" class="form-label">Obtained Percentage (%)</label>
                                        <input type="number" id="percentage" name="percentage" step="0.01" min="0" max="100" class="form-input" placeholder="85.0">
                                    </div>

                                    <div class="form-group full-width">
                                        <label class="checkbox-card" style="display:inline-flex; align-items:center; gap:8px;">
                                            <input type="checkbox" id="is_current" name="is_current" value="1">
                                            <span>Mark this degree as my current active education</span>
                                        </label>
                                    </div>
                                    
                                </div>
                            </div>
                        </div>

                        <div style="display:flex; gap:12px; margin-top:30px;">
                            <button type="button" class="btn-action btn-primary" id="btn-edu-save" onclick="saveEduRecord()">Add Degree</button>
                            <button type="button" class="btn-action btn-secondary" id="btn-edu-cancel" style="display:none;" onclick="cancelEduEdit()">Cancel</button>
                        </div>
                    </form>
                </div>

                <div style="display:flex; justify-content:space-between; margin-top:30px;">
                    <button type="button" class="btn-action btn-secondary" onclick="showStep(1)"><i data-lucide="arrow-left"></i> Previous</button>
                    <button type="button" class="btn-action btn-primary" onclick="showStep(3)">Next: Preferences <i data-lucide="arrow-right"></i></button>
                </div>
            </section>

            <!-- STEP 3 PANEL: Scholarship Preferences -->
            <section class="wizard-step-panel" id="step-3" aria-labelledby="step3-title">
                <div class="card">
                    <h2 class="card-title" id="step3-title"><i data-lucide="sliders"></i> Scholarship Matching Preferences</h2>
                    <p class="card-subtitle">Tell us what scholarships you're looking for so we can match you with relevant opportunities.</p>
                    
                    <form id="preferences-form" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">

                        <div class="form-grid">
                            <!-- Preferred study destinations -->
                            <div class="form-group full-width">
                                <label class="form-label">Preferred Study Countries (Select Multiple)</label>
                                <select id="preferred_countries" name="preferred_countries[]" class="form-input select-search" multiple style="width:100%;">
                                    <?php foreach ($countries as $c): ?>
                                        <option value="<?= e($c['id']) ?>" <?= in_array($c['id'], $prefCountries) ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Preferred fields of study -->
                            <div class="form-group full-width">
                                <label class="form-label">Preferred Fields of Study (Select Max 3)</label>
                                <select id="preferred_fields" name="preferred_fields[]" class="form-input select-search" multiple style="width:100%;">
                                    <?php foreach ($fieldsOfStudy as $f): ?>
                                        <option value="<?= e($f['id']) ?>" <?= in_array($f['id'], $prefFields) ? 'selected' : '' ?>><?= e($f['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="card-subtitle" style="margin-top:4px; display:block;" id="fields-limit-warning">You can select up to 3 fields of study.</span>
                            </div>

                            <!-- Target degree levels -->
                            <div class="form-group full-width">
                                <label class="form-label">Target Scholarship Degree Levels (Select Multiple)</label>
                                <select id="preferred_degrees" name="preferred_degrees[]" class="form-input select-search" multiple style="width:100%;">
                                    <?php 
                                    $degreeLevelsList = ['High School', 'Diploma', 'Associate Degree', 'Bachelor\'s', 'Master\'s', 'MPhil', 'PhD', 'Postdoctoral'];
                                    foreach ($degreeLevelsList as $lvl): 
                                    ?>
                                        <option value="<?= e($lvl) ?>" <?= in_array($lvl, $prefDegrees) ? 'selected' : '' ?>><?= e($lvl) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Funding Preference & Target Start Year -->
                            <div class="form-group">
                                <label for="preferred_funding_type" class="form-label">Funding Preference</label>
                                <select id="preferred_funding_type" name="preferred_funding_type" class="form-input select-search">
                                    <option value="">-- Choose Funding Preference --</option>
                                    <option value="Fully Funded" <?= ($user['preferred_funding_type'] ?? '') === 'Fully Funded' ? 'selected' : '' ?>>Fully Funded</option>
                                    <option value="Partially Funded" <?= ($user['preferred_funding_type'] ?? '') === 'Partially Funded' ? 'selected' : '' ?>>Partially Funded</option>
                                    <option value="Tuition Fee Waiver" <?= ($user['preferred_funding_type'] ?? '') === 'Tuition Fee Waiver' ? 'selected' : '' ?>>Tuition Fee Waiver</option>
                                    <option value="Stipend" <?= ($user['preferred_funding_type'] ?? '') === 'Stipend' ? 'selected' : '' ?>>Stipend</option>
                                    <option value="Any Funding" <?= ($user['preferred_funding_type'] ?? '') === 'Any Funding' ? 'selected' : '' ?>>Any Funding</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="preferred_start_year" class="form-label">Target Admission Year</label>
                                <select id="preferred_start_year" name="preferred_start_year" class="form-input select-search">
                                    <option value="">-- Choose Start Year --</option>
                                    <?php 
                                    $currYear = (int)date('Y');
                                    for ($y = $currYear; $y <= $currYear + 5; $y++):
                                    ?>
                                        <option value="<?= $y ?>" <?= ($user['preferred_start_year'] ?? '') == $y ? 'selected' : '' ?>><?= $y ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>

                        <div style="display:flex; justify-content:space-between; margin-top:30px;">
                            <button type="button" class="btn-action btn-secondary" onclick="showStep(2)"><i data-lucide="arrow-left"></i> Previous</button>
                            <button type="button" class="btn-action btn-primary" onclick="nextStep3()">Next: Review Profile <i data-lucide="arrow-right"></i></button>
                        </div>
                    </form>
                </div>
            </section>

            <!-- STEP 4 PANEL: Summary Review & Final Complete -->
            <section class="wizard-step-panel" id="step-4" aria-labelledby="step4-title">
                <div class="card">
                    <h2 class="card-title" id="step4-title"><i data-lucide="check-circle-2"></i> Review Profile & Onboarding Completion</h2>
                    <p class="card-subtitle">Verify your information before finalizing. You can edit any section instantly.</p>
                    
                    <div style="display:flex; flex-direction:column; gap:24px;">
                        
                        <!-- Personal Info Summary -->
                        <div style="border: 1px solid var(--border); border-radius:8px; padding:18px; position:relative;">
                            <button type="button" class="btn-action btn-secondary" style="position:absolute; top:12px; right:12px; min-height:30px; padding:4px 10px; font-size:0.75rem;" onclick="showStep(1)"><i data-lucide="edit"></i> Edit</button>
                            <h3 style="margin:0 0 12px; font-size:1rem; font-weight:700; color:var(--text-800);">Identity & Residency</h3>
                            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:12px; font-size:0.875rem;">
                                <div><strong>Full Name:</strong> <span id="sum-name"></span></div>
                                <div><strong>Email:</strong> <span id="sum-email"></span></div>
                                <div><strong>Phone:</strong> <span id="sum-phone"></span></div>
                                <div><strong>Birth Date:</strong> <span id="sum-dob"></span></div>
                                <div><strong>Gender:</strong> <span id="sum-gender"></span></div>
                                <div><strong>Residence Location:</strong> <span id="sum-location"></span></div>
                            </div>
                        </div>

                        <!-- Academic Degrees Summary -->
                        <div style="border: 1px solid var(--border); border-radius:8px; padding:18px; position:relative;">
                            <button type="button" class="btn-action btn-secondary" style="position:absolute; top:12px; right:12px; min-height:30px; padding:4px 10px; font-size:0.75rem;" onclick="showStep(2)"><i data-lucide="edit"></i> Edit</button>
                            <h3 style="margin:0 0 12px; font-size:1rem; font-weight:700; color:var(--text-800);">Academic History</h3>
                            <div id="sum-education-list" style="display:flex; flex-direction:column; gap:8px; font-size:0.875rem;">
                                <!-- Rendered dynamically -->
                            </div>
                        </div>

                        <!-- Matches Preferences Summary -->
                        <div style="border: 1px solid var(--border); border-radius:8px; padding:18px; position:relative;">
                            <button type="button" class="btn-action btn-secondary" style="position:absolute; top:12px; right:12px; min-height:30px; padding:4px 10px; font-size:0.75rem;" onclick="showStep(3)"><i data-lucide="edit"></i> Edit</button>
                            <h3 style="margin:0 0 12px; font-size:1rem; font-weight:700; color:var(--text-800);">Scholarship Matching Preferences</h3>
                            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:12px; font-size:0.875rem;">
                                <div><strong>Destinations:</strong> <span id="sum-countries"></span></div>
                                <div><strong>Fields:</strong> <span id="sum-fields"></span></div>
                                <div><strong>Degree Levels:</strong> <span id="sum-degrees"></span></div>
                                <div><strong>Funding Tiers:</strong> <span id="sum-funding"></span></div>
                                <div><strong>Admission Year:</strong> <span id="sum-start-year"></span></div>
                            </div>
                        </div>
                    </div>

                    <!-- Final Submission Button -->
                    <form id="final-wizard-form" style="margin-top:30px;">
                        <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                        
                        <div style="display:flex; justify-content:space-between;">
                            <button type="button" class="btn-action btn-secondary" onclick="showStep(3)"><i data-lucide="arrow-left"></i> Previous</button>
                            <button type="button" class="btn-action btn-primary" style="background:#059669;" onclick="submitFinalWizard()"><i data-lucide="check"></i> Complete Profile & Onboarding</button>
                        </div>
                    </form>
                </div>
            </section>

        </main>
    </div>

    <!-- Onboarding Script -->
    <script>
        lucide.createIcons();

        // 1. Initial State & Shared Variables
        let currentStep = 1;
        let eduRecords = <?= json_encode($education) ?>;
        
        // Datepicker boundaries: 100 years ago up to current date
        flatpickr("#date_of_birth", {
            dateFormat: "Y-m-d",
            maxDate: "today",
            defaultDate: "<?= $user['date_of_birth'] ?? '' ?>",
            yearRange: [new Date().getFullYear() - 100, new Date().getFullYear()]
        });

        // Initialize datepicker variables
        let startPicker = flatpickr("#start_date", { 
            dateFormat: "Y-m-d",
            onChange: function(selectedDates, dateStr, instance) {
                endPicker.set('minDate', dateStr);
            }
        });

        let endPicker = flatpickr("#end_date", { 
            dateFormat: "Y-m-d",
            onChange: function(selectedDates, dateStr, instance) {
                startPicker.set('maxDate', dateStr);
            }
        });

        // Initialize Select2 multi-select boxes with full width
        function initSelect2(selector, placeholder = "Select option...") {
            $(selector).select2({
                placeholder: placeholder,
                allowClear: true,
                width: '100%'
            });
        }

        initSelect2('.select-search');

        // Preferred Fields selection length enforcement (Max 3)
        $('#preferred_fields').select2({
            placeholder: "Select preferred fields...",
            maximumSelectionLength: 3,
            allowClear: true,
            width: '100%'
        }).on('select2:opening', function(e) {
            if ($(this).select2('data').length >= 3) {
                e.preventDefault();
                alert("You can select up to 3 fields of study.");
            }
        });

        // 2. Cascading Locations (Step 1 Residence)
        $('#residence_country_id').on('change', function() {
            const countryId = this.value;
            $('#residence_state_id').html('<option value="">-- Loading States --</option>').trigger('change');
            $('#city_id').html('<option value="">-- Select City --</option>').trigger('change');

            if (!countryId) {
                $('#residence_state_id').html('<option value="">-- Select State --</option>').trigger('change');
                return;
            }

            fetch('<?= url("/api/states?country_id=") ?>' + countryId)
                .then(res => res.json())
                .then(states => {
                    let html = '<option value="">-- Select State --</option>';
                    states.forEach(s => html += `<option value="${s.id}">${s.name}</option>`);
                    $('#residence_state_id').html(html).trigger('change');
                });
        });

        $('#residence_state_id').on('change', function() {
            const stateId = this.value;
            $('#city_id').html('<option value="">-- Loading Cities --</option>').trigger('change');

            if (!stateId) {
                $('#city_id').html('<option value="">-- Select City --</option>').trigger('change');
                return;
            }

            fetch('<?= url("/api/cities?state_id=") ?>' + stateId)
                .then(res => res.json())
                .then(cities => {
                    let html = '<option value="">-- Select City --</option>';
                    cities.forEach(c => html += `<option value="${c.id}">${c.name}</option>`);
                    $('#city_id').html(html).trigger('change');
                });
        });

        // 3. Cascading Education Form Locations (Step 2 Education)
        $('#edu_country_id').on('change', function() {
            const countryId = this.value;
            
            // Clear state, city, and university selection
            $('#edu_state_id').val('').html('<option value="">-- Select State --</option>').trigger('change');
            $('#edu_city_id').val('').html('<option value="">-- Select City --</option>').trigger('change');
            $('#institution_select').val(null).trigger('change');

            if (!countryId) {
                return;
            }

            // Set loading state
            $('#edu_state_id').html('<option value="">-- Loading States --</option>');

            fetch('<?= url("/api/states?country_id=") ?>' + countryId)
                .then(res => res.json())
                .then(states => {
                    let html = '<option value="">-- Select State --</option>';
                    states.forEach(s => html += `<option value="${s.id}">${s.name}</option>`);
                    $('#edu_state_id').html(html);
                });
        });

        $('#edu_state_id').on('change', function() {
            const stateId = this.value;
            
            // Clear city and university selection
            $('#edu_city_id').val('').html('<option value="">-- Select City --</option>').trigger('change');
            $('#institution_select').val(null).trigger('change');

            if (!stateId) {
                return;
            }

            // Set loading state
            $('#edu_city_id').html('<option value="">-- Loading Cities --</option>');

            fetch('<?= url("/api/cities?state_id=") ?>' + stateId)
                .then(res => res.json())
                .then(cities => {
                    let html = '<option value="">-- Select City --</option>';
                    cities.forEach(c => html += `<option value="${c.id}">${c.name}</option>`);
                    $('#edu_city_id').html(html);
                });

            // Reload Universities/Colleges list
            initInstitutionAutocomplete();
        });

        $('#edu_city_id').on('change', function() {
            initInstitutionAutocomplete();
        });

        // 4. Institution Autocomplete via Select2 AJAX Search
        function initInstitutionAutocomplete() {
            const type = $('#institution_type').val();
            const countryId = $('#edu_country_id').val();
            const stateId = $('#edu_state_id').val();
            const cityId = $('#edu_city_id').val();

            if (!type) return;
            if (type !== 'university') return;

            // Clear previous Select2 option
            $('#institution_select').val(null).trigger('change');

            let labelText = "Institution Name";
            if (type === 'university') labelText = "University Name";
            if (type === 'college') labelText = "College Name";
            $('#institution_select_label').text(labelText);

            $('#institution_select').select2({
                placeholder: "Search or select " + type + "...",
                allowClear: true,
                width: '100%',
                ajax: {
                    url: '<?= url("/api/institutions") ?>',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            search: params.term,
                            type: type,
                            country_id: countryId,
                            state_id: stateId,
                            city_id: cityId
                        };
                    },
                    processResults: function (data) {
                        let results = data.map(item => ({
                            id: item.id,
                            text: item.name
                        }));
                        // Add "Other" fallback tag option
                        results.push({
                            id: 'other',
                            text: 'Other — My ' + type + ' is not listed'
                        });
                        return {
                            results: results
                        };
                    },
                    cache: true
                }
            });
        }

        // Toggle custom entry box
        $('#institution_select').on('change', function() {
            const type = $('#institution_type').val();
            if (type !== 'university') return;
            if (this.value === 'other') {
                $('#custom_institution_group').show();
                
                let labelText = "Institution Name";
                let placeholderText = "Enter full name of your institution";
                if (type === 'university') {
                    labelText = "University Name";
                    placeholderText = "Enter full name of your university";
                } else if (type === 'college') {
                    labelText = "College Name";
                    placeholderText = "Enter full name of your college";
                } else if (type === 'school') {
                    labelText = "School Name";
                    placeholderText = "Enter full name of your school";
                }
                
                $('#custom_institution_label').text(labelText);
                $('#custom_institution_name').attr('placeholder', placeholderText).attr('required', true);
            } else {
                $('#custom_institution_group').hide();
                $('#custom_institution_name').val('').removeAttr('required');
            }
            checkRevealAcademicFields();
        });


        // 5. Dynamic Form Conditional Fields Engine
        function handleFormConditions() {
            const type = $('#institution_type').val();
            const level = $('#degree_level').val();
            const status = $('#graduation_status').val();
            const resultStatus = $('#result_status').val();

            if (!type || !level) {
                // If the first two entry questions are not chosen, hide everything else
                $('#edu_country_group').hide();
                $('#edu_state_group').hide();
                $('#edu_city_group').hide();
                $('#institution_select_group').hide();
                $('#custom_institution_group').hide();
                $('#academic_fields_group').hide();
                return;
            }

            if (type === 'school' || type === 'college' || type === 'other') {
                // School, College, and Other show location selectors but hide autocomplete, showing text input directly
                $('#edu_country_group').show();
                $('#edu_state_group').show();
                $('#edu_city_group').show();
                $('#institution_select_group').hide();
                
                $('#custom_institution_group').show();
                
                let labelText = "Institution Name";
                let placeholderText = "Enter full name of your institution";
                if (type === 'school') {
                    labelText = "School Name";
                    placeholderText = "Enter school name";
                } else if (type === 'college') {
                    labelText = "College Name";
                    placeholderText = "Enter college name";
                }
                
                $('#custom_institution_label').text(labelText);
                $('#custom_institution_name').attr('placeholder', placeholderText).attr('required', true);
                
                // Degree Title is not strictly needed for schools
                if (type === 'school') {
                    $('#degree_title_group').hide();
                } else {
                    $('#degree_title_group').show();
                }
            } else if (type === 'university') {
                // University requires location cascades first
                $('#edu_country_group').show();
                $('#edu_state_group').show();
                $('#edu_city_group').show();
                $('#institution_select_group').show();
                $('#institution_select_label').text("University Name");
                
                $('#degree_title_group').show();
                initInstitutionAutocomplete();
            }



            // Graduation Status conditional fields
            if (status === 'ongoing') {
                if (type === 'university') {
                    $('#current_semester_group').show();
                    $('#current_semester').attr('required', true);
                } else {
                    $('#current_semester_group').hide();
                    $('#current_semester').val('').removeAttr('required');
                }
                
                $('#start_date_label').text("Start Date");
                $('#end_date_label').text("Expected Graduation Date");
                $('#passing_year_label').text("Expected Graduation Year");
                $('#passing_year_group').show();
                if (type === 'university') {
                    $('#passing_year').attr('required', true);
                } else {
                    $('#passing_year').removeAttr('required');
                }
            } else if (status === 'graduated') {
                $('#current_semester_group').hide();
                $('#current_semester').val('').removeAttr('required');
                
                $('#start_date_label').text("Start Date");
                $('#end_date_label').text("End Date / Graduation Date");
                $('#passing_year_label').text("Graduation / Passing Year");
                $('#passing_year_group').show();
                if (type === 'university') {
                    $('#passing_year').attr('required', true);
                } else {
                    $('#passing_year').removeAttr('required');
                }
            } else {
                // Incomplete
                $('#current_semester_group').hide();
                $('#current_semester').val('').removeAttr('required');
                
                $('#start_date_label').text("Start Date");
                $('#end_date_label').text("End Date (Actual / Expected)");
                $('#passing_year_group').hide();
                $('#passing_year').val('').removeAttr('required');
            }

            // Result status conditional fields
            if (resultStatus === 'cgpa') {
                $('#cgpa_fields_group').show();
                $('#percentage_field_group').hide();
                $('#percentage').val('');
                if (type === 'university') {
                    $('#cgpa').attr('required', true);
                    $('#cgpa_scale').attr('required', true);
                } else {
                    $('#cgpa').removeAttr('required');
                    $('#cgpa_scale').removeAttr('required');
                }
            } else if (resultStatus === 'percentage') {
                $('#cgpa_fields_group').hide();
                $('#cgpa').val('');
                $('#cgpa_scale').val('');
                $('#percentage_field_group').show();
                if (type === 'university') {
                    $('#percentage').attr('required', true);
                } else {
                    $('#percentage').removeAttr('required');
                }
            } else {
                // Awaited
                $('#cgpa_fields_group').hide();
                $('#cgpa').val('').removeAttr('required');
                $('#cgpa_scale').val('').removeAttr('required');
                $('#percentage_field_group').hide();
                $('#percentage').val('').removeAttr('required');
            }

            checkRevealAcademicFields();
        }

        // 4.1 Degree Level filtering mapping based on Institution Type
        const degreeLevelsMap = {
            'school': [
                { value: 'High School', text: 'High School' }
            ],
            'college': [
                { value: 'Intermediate / College', text: 'Intermediate / College' }
            ],
            'university': [
                { value: "Bachelor's", text: "Bachelor's" },
                { value: "Master's", text: "Master's" },
                { value: 'MPhil', text: 'MPhil' },
                { value: 'PhD', text: 'PhD' },
                { value: 'Postdoctoral', text: 'Postdoctoral' }
            ],
            'other': [
                { value: 'Diploma', text: 'Diploma' },
                { value: 'Associate Degree', text: 'Associate Degree' },
                { value: 'Certification', text: 'Certification' },
                { value: 'Vocational', text: 'Vocational' },
                { value: 'Sports / Athletic Certification', text: 'Sports / Athletic Certification' },
                { value: 'Other', text: 'Other' }
            ]
        };

        $('#institution_type').on('change', function() {
            const selectedType = this.value;
            const levelSelect = $('#degree_level');
            
            // Remember selected value if any
            const prevVal = levelSelect.val();
            
            // Clear current options
            levelSelect.html('<option value="">-- Choose Level --</option>');
            
            if (selectedType && degreeLevelsMap[selectedType]) {
                degreeLevelsMap[selectedType].forEach(opt => {
                    const isSelected = (opt.value === prevVal) ? 'selected' : '';
                    levelSelect.append(`<option value="${opt.value}" ${isSelected}>${opt.text}</option>`);
                });
            }
            
            // Re-trigger Select2 rendering
            levelSelect.trigger('change.select2');
            
            // Re-run visibility rules
            handleFormConditions();
        });

        // Trigger dynamic layout updates on selection changes
        $('#degree_level').on('change', handleFormConditions);

        $('#graduation_status, #result_status').on('change', handleFormConditions);
        $('#custom_institution_name').on('input', checkRevealAcademicFields);

        function checkRevealAcademicFields() {
            const level = $('#degree_level').val();
            const type = $('#institution_type').val();
            
            if (level && type === 'university') {
                $('#academic_fields_group').show();
            } else {
                $('#academic_fields_group').hide();
            }
            
            // Re-align layouts and trigger Select2 rendering after display change
            $('.select-search').trigger('change.select2');
        }

        // 6. Wizard Panel Switching & UI indicators
        function showStep(step) {
            document.querySelectorAll('.wizard-step-panel').forEach(panel => panel.classList.remove('active'));
            document.getElementById('step-' + step).classList.add('active');

            // Set indicators
            document.querySelectorAll('.step-indicator-item').forEach((item, idx) => {
                if (idx + 1 < step) {
                    item.classList.add('completed');
                    item.classList.remove('active');
                } else if (idx + 1 === step) {
                    item.classList.add('active');
                    item.classList.remove('completed');
                } else {
                    item.classList.remove('active', 'completed');
                }
            });

            currentStep = step;
            sessionStorage.setItem('active_wizard_step', step);

            // Step 3 dynamic target degrees filtering
            if (step === 3) {
                updatePreferredDegreesOptions();
            }

            // Step 4 summaries generation
            if (step === 4) {
                generateReviewSummary();
            }
        }

        function updateProgressIndicator(completionPct) {
            $('#completion-value').text(completionPct + '%');
            $('#completion-fill').css('width', completionPct + '%');
        }

        // Restore active step on page reload
        window.addEventListener('DOMContentLoaded', () => {
            const savedStep = sessionStorage.getItem('active_wizard_step');
            if (savedStep) {
                showStep(parseInt(savedStep));
            }
            renderEducationCards();
            handleFormConditions();
            updatePreferredDegreesOptions();
        });

        // 7. Action: Save Step 1 Demographics via AJAX
        function nextStep1() {
            const form = document.getElementById('personal-info-form');
            if (!form.reportValidity()) return;

            const formData = new FormData(form);
            fetch('<?= url("/profile/update") ?>', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    updateProgressIndicator(data.completion);
                    showStep(2);
                } else {
                    displayErrors('personal-info-form', data.errors);
                }
            })
            .catch(err => alert('Failed to update details. Try again.'));
        }

        // 8. Action: Save/Update Step 2 Education record via AJAX
        function saveEduRecord() {
            const form = document.getElementById('edu-form');
            if (!form.reportValidity()) return;

            // Manual validator check for conditional groups
            const type = $('#institution_type').val();
            const selectVal = $('#institution_select').val();
            const customVal = $('#custom_institution_name').val().trim();
            const status = $('#graduation_status').val();
            const semester = $('#current_semester').val();
            const year = $('#passing_year').val();

            if (type === 'university' && !selectVal) {
                alert("Please select or search your university name.");
                return;
            }
            if ((type === 'school' || type === 'college' || type === 'other' || selectVal === 'other') && !customVal) {
                alert("Please enter the name of your institution.");
                return;
            }
            if (type === 'school' || type === 'college' || type === 'other' || selectVal === 'other') {
                const countryVal = $('#edu_country_id').val();
                const stateVal = $('#edu_state_id').val();
                if (!countryVal) {
                    alert("Please select the country of your institution.");
                    return;
                }
                if (!stateVal) {
                    alert("Please select the state of your institution.");
                    return;
                }
            }
            if (type === 'university') {
                if (status === 'ongoing' && !semester) {
                    alert("Please select your current semester.");
                    return;
                }
                if ((status === 'ongoing' || status === 'graduated') && !year) {
                    alert("Please select your graduation/passing year.");
                    return;
                }
            }

            const formData = new FormData(form);
            const isEditing = $('#edu_id').val() !== '';
            const targetUrl = isEditing ? '<?= url("/profile/education/update") ?>' : '<?= url("/profile/education/add") ?>';

            fetch(targetUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    eduRecords = data.education;
                    updateProgressIndicator(data.completion);
                    renderEducationCards();
                    cancelEduEdit();
                } else {
                    displayErrors('edu-form', data.errors);
                }
            })
            .catch(err => alert('Failed to save academic record. Please try again.'));
        }

        function deleteEduRecord(id) {
            if (!confirm('Are you sure you want to delete this education record?')) return;

            const formData = new FormData();
            formData.append('id', id);
            formData.append('csrf_token', '<?= e($csrf_token) ?>');

            fetch('<?= url("/profile/education/delete") ?>', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    eduRecords = data.education;
                    updateProgressIndicator(data.completion);
                    renderEducationCards();
                } else {
                    alert('Deletion failed.');
                }
            })
            .catch(err => alert('Deletion failed.'));
        }

        // Loader to editor
        function editEduRecord(edu) {
            $('#edu-form-title').text('Edit Academic Degree');
            $('#btn-edu-save').text('Save Changes');
            $('#btn-edu-cancel').show();

            $('#edu_id').val(edu.id);
            
            let resolvedType = edu.institution_type;
            if (!resolvedType) {
                if (edu.degree_level === 'High School') {
                    resolvedType = 'school';
                } else if (edu.degree_level === 'Intermediate / College') {
                    resolvedType = 'college';
                } else if (['Bachelor\'s', 'Master\'s', 'MPhil', 'PhD', 'Postdoctoral'].includes(edu.degree_level)) {
                    resolvedType = 'university';
                } else {
                    resolvedType = 'other';
                }
            }
            $('#institution_type').val(resolvedType).trigger('change');
            
            setTimeout(() => {
                $('#degree_level').val(edu.degree_level).trigger('change');
            }, 100);
            
            setTimeout(() => {

                // If it is university/college location setup
                if (edu.country_id) {
                    $('#edu_country_id').val(edu.country_id).trigger('change');
                }
                
                if (resolvedType === 'school' || resolvedType === 'college' || resolvedType === 'other') {
                    $('#custom_institution_name').val(edu.institution_name).trigger('input');
                } else {
                    let optionVal = edu.institution_id || 'other';
                    let optionText = edu.institution_name || 'Other — My university is not listed';
                    
                    let newOption = new Option(optionText, optionVal, true, true);
                    $('#institution_select').append(newOption).trigger('change');

                    if (optionVal === 'other') {
                        $('#custom_institution_name').val(edu.institution_name).trigger('input');
                    }
                }

                
                $('#degree_title').val(edu.degree_title);
                $('#field_of_study').val(edu.field_of_study).trigger('change');
                $('#graduation_status').val(edu.graduation_status).trigger('change');
                
                setTimeout(() => {
                    $('#current_semester').val(edu.current_semester || '').trigger('change');
                    $('#passing_year').val(edu.passing_year || '').trigger('change');
                }, 200);

                $('#start_date').val(edu.start_date || '');
                $('#end_date').val(edu.end_date || '');
                
                startPicker.setDate(edu.start_date || '');
                endPicker.setDate(edu.end_date || '');

                if (edu.cgpa) {
                    $('#result_status').val('cgpa').trigger('change');
                    $('#cgpa').val(edu.cgpa);
                    $('#cgpa_scale').val(edu.cgpa_scale);
                } else if (edu.percentage) {
                    $('#result_status').val('percentage').trigger('change');
                    $('#percentage').val(edu.percentage);
                } else {
                    $('#result_status').val('awaited').trigger('change');
                }

                $('#is_current').prop('checked', parseInt(edu.is_current) === 1);
            }, 300);

            document.getElementById('edu-form-title').scrollIntoView({ behavior: 'smooth' });
        }

        function cancelEduEdit() {
            $('#edu-form-title').text('Add Academic Degree');
            $('#btn-edu-save').text('Add Degree');
            $('#btn-edu-cancel').hide();
            $('#edu_id').val('');
            
            document.getElementById('edu-form').reset();
            
            // Clear Select2 values
            $('#institution_type').val('').trigger('change');
            $('#degree_level').val('').trigger('change');
            $('#edu_country_id').val('').trigger('change');
            $('#institution_select').val(null).trigger('change');
            $('#custom_institution_group').hide();
            $('#academic_fields_group').hide();
        }

        function updatePreferredDegreesOptions() {
            const select = $('#preferred_degrees');
            if (select.length === 0) return;
            const selectedVals = select.val() || []; // Keep current selection
            
            // Map of institution types to their target degree levels
            const mapping = {
                'school': ['High School', 'Diploma', 'Associate Degree', 'Bachelor\'s'],
                'college': ['Diploma', 'Associate Degree', 'Bachelor\'s'],
                'university': ['Bachelor\'s', 'Master\'s', 'MPhil', 'PhD', 'Postdoctoral'],
                'other': ['Diploma', 'Associate Degree', 'Certification', 'Vocational', 'Other']
            };

            // Collect all unique target levels based on the user's education history
            let allowedDegrees = new Set();
            
            if (eduRecords && eduRecords.length > 0) {
                eduRecords.forEach(edu => {
                    let type = edu.institution_type;
                    // Fallback to resolving type if not present
                    if (!type) {
                        if (edu.degree_level === 'High School') {
                            type = 'school';
                        } else if (edu.degree_level === 'Intermediate / College') {
                            type = 'college';
                        } else if (['Bachelor\'s', 'Master\'s', 'MPhil', 'PhD', 'Postdoctoral'].includes(edu.degree_level)) {
                            type = 'university';
                        } else {
                            type = 'other';
                        }
                    }
                    if (mapping[type]) {
                        mapping[type].forEach(deg => allowedDegrees.add(deg));
                    }
                });
            } else {
                // If no education records yet, allow all degrees as a fallback
                const allDegrees = [
                    'High School', 'Diploma', 'Associate Degree', 'Bachelor\'s', 
                    'Master\'s', 'MPhil', 'PhD', 'Postdoctoral', 'Certification', 'Vocational', 'Other'
                ];
                allDegrees.forEach(deg => allowedDegrees.add(deg));
            }

            // Clear options and rebuild
            select.empty();
            allowedDegrees.forEach(deg => {
                const isSelected = selectedVals.includes(deg) ? 'selected' : '';
                select.append(`<option value="${deg}" ${isSelected}>${deg}</option>`);
            });

            // Re-trigger Select2 rendering
            select.trigger('change.select2');
        }

        function renderEducationCards() {
            const container = document.getElementById('education-cards-list');
            if (eduRecords.length === 0) {
                container.innerHTML = `<p style="color: var(--text-500); font-size: 0.9375rem; text-align: center; padding: 10px 0;">No degrees added yet. Add one below.</p>`;
                updatePreferredDegreesOptions();
                return;
            }

            let html = '';
            eduRecords.forEach(edu => {
                html += `
                    <div class="education-row" style="margin-bottom:12px;">
                        <div>
                            <h4 style="margin:0; font-size:1rem; font-weight:600; color:var(--text-900);">
                                ${escapeHtml(edu.degree_title)} in ${escapeHtml(edu.field_of_study)}
                                ${parseInt(edu.is_current) === 1 ? '<span style="font-size: 0.6875rem; background: #ecfdf5; color: #047857; padding: 2px 6px; border-radius: 4px; margin-left: 8px;">Current</span>' : ''}
                            </h4>
                            <p style="margin:4px 0 0; font-size:0.8125rem; color:var(--text-500);">${escapeHtml(edu.institution_name)} (${edu.start_date ? new Date(edu.start_date).getFullYear() : 'N/A'} – ${parseInt(edu.is_current) === 1 ? 'Present' : (edu.end_date ? new Date(edu.end_date).getFullYear() : 'N/A')})</p>
                            ${edu.current_semester ? `<p style="margin:4px 0 0; font-size:0.75rem; color:var(--text-500);">Current Semester / Phase: <strong>${edu.current_semester}</strong></p>` : ''}
                            ${edu.cgpa ? `<p style="margin:4px 0 0; font-size:0.75rem; color:var(--text-500);">CGPA: <strong>${edu.cgpa} / ${edu.cgpa_scale}</strong></p>` : ''}
                            ${edu.percentage ? `<p style="margin:4px 0 0; font-size:0.75rem; color:var(--text-500);">Percentage: <strong>${edu.percentage}%</strong></p>` : ''}
                        </div>
                        <div class="education-row-actions">
                            <button type="button" class="btn-action btn-secondary" onclick='editEduRecord(${JSON.stringify(edu).replace(/'/g, "&#39;")})'>Edit</button>
                            <button type="button" class="btn-action btn-danger" onclick="deleteEduRecord(${edu.id})">Delete</button>
                        </div>
                    </div>
                `;
            });
            container.innerHTML = html;
            updatePreferredDegreesOptions();
        }

        // 9. Action: Save Step 3 Preferences via AJAX
        function nextStep3() {
            const form = document.getElementById('preferences-form');
            if (!form.reportValidity()) return;

            const formData = new FormData(form);
            fetch('<?= url("/profile/preferences/update") ?>', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    updateProgressIndicator(data.completion);
                    showStep(4);
                } else {
                    displayErrors('preferences-form', data.errors);
                }
            })
            .catch(err => alert('Failed to save preferences. Try again.'));
        }

        // 10. Summary Review population for Step 4
        function generateReviewSummary() {
            $('#sum-name').text($('#first_name').val() + ' ' + $('#last_name').val());
            $('#sum-email').text($('#email').val());
            $('#sum-phone').text($('#phone').val());
            $('#sum-dob').text($('#date_of_birth').val() || 'N/A');
            $('#sum-gender').text($('#gender').find('option:selected').text() || 'N/A');
            
            let addressVal = $('#address').val() ? ' (' + $('#address').val() + ', ' + $('#postal_code').val() + ')' : '';
            $('#sum-location').text(
                $('#residence_country_id').find('option:selected').text() + ' → ' +
                $('#residence_state_id').find('option:selected').text() + ' → ' +
                $('#city_id').find('option:selected').text() + addressVal
            );

            // Populate Education Summary
            const eduList = document.getElementById('sum-education-list');
            if (eduRecords.length === 0) {
                eduList.innerHTML = '<span style="color:#b91c1c; font-weight:600;"><i data-lucide="alert-triangle" style="width:14px;height:14px;display:inline;"></i> No education history added. At least one academic record is required for matches.</span>';
            } else {
                let html = '';
                eduRecords.forEach(edu => {
                    html += `<div>• <strong>${escapeHtml(edu.degree_level)}:</strong> ${escapeHtml(edu.degree_title)} in ${escapeHtml(edu.field_of_study)} at ${escapeHtml(edu.institution_name)} (${edu.cgpa ? `CGPA ${edu.cgpa}/${edu.cgpa_scale}` : (edu.percentage ? `Percentage ${edu.percentage}%` : 'Ongoing')})</div>`;
                });
                eduList.innerHTML = html;
            }

            // Preferences
            let countries = $('#preferred_countries').find('option:selected').map(function() { return this.text; }).get().join(', ');
            let fields = $('#preferred_fields').find('option:selected').map(function() { return this.text; }).get().join(', ');
            let degrees = $('#preferred_degrees').find('option:selected').map(function() { return this.text; }).get().join(', ');

            $('#sum-countries').text(countries || 'Any Country');
            $('#sum-fields').text(fields || 'Any Field');
            $('#sum-degrees').text(degrees || 'Any Level');
            $('#sum-funding').text($('#preferred_funding_type').val() || 'Any Funding Type');
            $('#sum-start-year').text($('#preferred_start_year').val() || 'N/A');
        }

        // 11. Action: Submit final Complete Profile
        function submitFinalWizard() {
            const form = document.getElementById('final-wizard-form');
            const formData = new FormData(form);

            fetch('<?= url("/profile/complete") ?>', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.redirect_url) {
                    sessionStorage.removeItem('active_wizard_step');
                    window.location.href = data.redirect_url;
                } else {
                    alert('Submission failed. Please check your data.');
                }
            })
            .catch(err => alert('Failed to finalize onboarding wizard. Please try again.'));
        }

        // Helper Error displayer
        function displayErrors(formId, errors) {
            const form = document.getElementById(formId);
            form.querySelectorAll('.field-error').forEach(e => e.remove());
            form.querySelectorAll('.alert-danger').forEach(e => e.remove());

            for (const [key, msg] of Object.entries(errors)) {
                let input = form.querySelector(`[name="${key}"]`) || form.querySelector(`[name="${key}[]"]`);
                if (!input && key === 'custom_institution_name') {
                    input = document.getElementById('custom_institution_name');
                }
                if (!input && key === 'edu_country_id') {
                    input = document.getElementById('edu_country_id');
                }
                if (!input && key === 'edu_state_id') {
                    input = document.getElementById('edu_state_id');
                }
                
                if (input) {
                    const errorSpan = document.createElement('span');
                    errorSpan.className = 'field-error';
                    errorSpan.innerText = msg;
                    input.parentNode.appendChild(errorSpan);
                } else {
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-danger';
                    alertDiv.innerText = msg;
                    form.prepend(alertDiv);
                }
            }
        }

        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/&/g, '&amp;')
                      .replace(/</g, '&lt;')
                      .replace(/>/g, '&gt;')
                      .replace(/"/g, '&quot;')
                      .replace(/'/g, '&#039;');
        }
    </script>
</body>
</html>
