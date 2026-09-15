<?php
$title = 'Settings';
include ROOT_PATH . '/app/Views/layouts/student_header.php';

$currentCompletion = $user['profile_completion_percentage'] ?? 0;
?>

<style>
    /* =========================================================
       UXCEL-INSPIRED NAVIGATIONAL TAB BAR (FOLDER DIVIDER TABS)
       ========================================================= */
    .settings-nav-wrapper {
        margin-top: 10px;
        margin-bottom: 28px;
        border-bottom: 2px solid #e2e8f0;
    }
    .settings-tabs-nav {
        display: flex;
        align-items: flex-end;
        gap: 6px;
        overflow-x: auto;
        scrollbar-width: none;
        margin-bottom: -2px;
        padding-left: 4px;
    }
    .settings-tabs-nav::-webkit-scrollbar {
        display: none;
    }
    .settings-tab-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 22px;
        font-size: 0.9375rem;
        font-weight: 600;
        color: #64748b;
        background: #f1f5f9;
        border: 1px solid #cbd5e1;
        border-bottom: 2px solid #cbd5e1;
        border-radius: 10px 10px 0 0;
        cursor: pointer;
        text-decoration: none;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        white-space: nowrap;
        position: relative;
    }
    .settings-tab-btn:hover {
        color: var(--primary, #2563eb);
        background: #f8fafc;
        border-color: #94a3b8;
    }
    .settings-tab-btn.active {
        color: var(--primary, #2563eb);
        background: #ffffff;
        border-color: #cbd5e1;
        border-bottom: 2px solid #ffffff !important;
        box-shadow: 0 -3px 8px rgba(0, 0, 0, 0.04);
        font-weight: 700;
        z-index: 2;
    }
    .settings-tab-btn i {
        width: 18px;
        height: 18px;
        flex-shrink: 0;
    }
    .tab-badge {
        font-size: 0.6875rem;
        padding: 2px 7px;
        border-radius: 10px;
        font-weight: 700;
        letter-spacing: 0.03em;
        text-transform: uppercase;
    }
    .tab-badge-pro {
        background: #fef3c7;
        color: #b45309;
        border: 1px solid #fde68a;
    }
    .tab-badge-active {
        background: #dcfce7;
        color: #15803d;
        border: 1px solid #bbf7d0;
    }

    /* Tab Panels */
    .settings-tab-panel {
        display: none;
        animation: fadeInTab 0.25s ease-in-out;
    }
    .settings-tab-panel.active {
        display: block;
    }
    @keyframes fadeInTab {
        from { opacity: 0; transform: translateY(4px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Card styling */
    .settings-card {
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    }
    .settings-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 14px;
        border-bottom: 1px solid #f1f5f9;
    }
    .settings-card-title {
        font-size: 1.125rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .settings-card-subtitle {
        margin: 4px 0 0;
        color: #64748b;
        font-size: 0.875rem;
    }

    /* Forms */
    .settings-form-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 18px;
    }
    .settings-form-group.full-width {
        grid-column: span 2;
    }
    .settings-form-label {
        display: block;
        font-size: 0.875rem;
        font-weight: 600;
        color: #334155;
        margin-bottom: 6px;
    }
    .settings-form-input {
        width: 100%;
        padding: 10px 14px;
        font-size: 0.875rem;
        border: 1px solid var(--border);
        border-radius: 8px;
        background: #fff;
        color: #1e293b;
        transition: border-color 0.2s, box-shadow 0.2s;
        min-height: 42px;
    }
    .settings-form-input:focus {
        outline: none;
        border-color: var(--primary, #2563eb);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }

    /* Select2 Tweaks */
    .select2-container--default .select2-selection--single {
        border: 1px solid var(--border) !important;
        border-radius: 8px !important;
        height: 42px !important;
        padding: 6px 12px !important;
        font-size: 0.875rem !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #1e293b !important;
        line-height: 28px !important;
    }
    .select2-container--default .select2-selection--multiple {
        border: 1px solid var(--border) !important;
        border-radius: 8px !important;
        min-height: 42px !important;
        padding: 4px 8px !important;
    }

    /* Education Record Rows */
    .edu-record-card {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 16px;
        margin-bottom: 12px;
        background: #f8fafc;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
    }
    .edu-record-title {
        font-size: 0.9375rem;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 4px;
    }
    .edu-record-meta {
        font-size: 0.8125rem;
        color: #64748b;
        margin: 0;
    }

    /* Plans Grid */
    .plans-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
        margin-top: 16px;
    }
    .plan-pricing-card {
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        padding: 24px;
        background: #fff;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        position: relative;
        transition: transform 0.2s, border-color 0.2s;
    }
    .plan-pricing-card.featured {
        border-color: var(--primary, #2563eb);
        box-shadow: 0 8px 24px -4px rgba(37, 99, 235, 0.12);
    }
    .plan-price-tag {
        font-size: 1.75rem;
        font-weight: 800;
        color: #0f172a;
        margin: 12px 0 6px;
    }
    .plan-price-currency {
        font-size: 0.875rem;
        font-weight: 600;
        color: #64748b;
    }

    @media (max-width: 768px) {
        .settings-form-grid {
            grid-template-columns: 1fr;
        }
        .settings-form-group.full-width {
            grid-column: span 1;
        }
        .edu-record-card {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>

<!-- Header Title -->
<div style="margin-bottom: 18px;">
    <h1 style="font-size: 1.625rem; font-weight: 800; margin: 0; color: #0f172a; letter-spacing: -0.02em;">Account & Application Settings</h1>
    <p style="margin: 4px 0 0; color: #64748b; font-size: 0.875rem;">Manage your academic profile, WhatsApp alerts, deadline reminders, and subscription tier.</p>
</div>

<!-- Success / Flash Messages -->
<?php if (!empty($success_message)): ?>
    <div style="background-color:#dcfce7; border:1px solid #86efac; color:#166534; padding:12px 18px; border-radius:8px; margin-bottom:20px; font-size:0.875rem; display:flex; align-items:center; gap:10px; font-weight:500;">
        <i data-lucide="circle-check" style="width:20px; height:20px; color:#15803d; flex-shrink:0;"></i>
        <span><?= e($success_message) ?></span>
    </div>
<?php endif; ?>

<?php if (!empty($errors['system'])): ?>
    <div style="background-color:#fef2f2; border:1px solid #fca5a5; color:#991b1b; padding:12px 18px; border-radius:8px; margin-bottom:20px; font-size:0.875rem; display:flex; align-items:center; gap:10px;">
        <i data-lucide="alert-circle" style="width:20px; height:20px; color:#dc2626; flex-shrink:0;"></i>
        <span><?= e($errors['system']) ?></span>
    </div>
<?php endif; ?>

<!-- UXCEL NAVIGATIONAL TAB BAR -->
<nav class="settings-nav-wrapper" aria-label="Settings Modules">
    <div class="settings-tabs-nav" role="tablist">
        <!-- Tab 1: Profile Details -->
        <button type="button" 
                class="settings-tab-btn <?= $activeTab === 'profile' ? 'active' : '' ?>" 
                data-tab="profile" 
                role="tab" 
                aria-selected="<?= $activeTab === 'profile' ? 'true' : 'false' ?>" 
                aria-controls="panel-profile">
            <i data-lucide="user"></i>
            <span>Profile Details</span>
        </button>

        <!-- Tab 2: WhatsApp & Deadline Reminders (Subscription-Gated) -->
        <?php if ($isSubscribed): ?>
            <button type="button" 
                    class="settings-tab-btn <?= $activeTab === 'reminders' ? 'active' : '' ?>" 
                    data-tab="reminders" 
                    role="tab" 
                    aria-selected="<?= $activeTab === 'reminders' ? 'true' : 'false' ?>" 
                    aria-controls="panel-reminders">
                <i data-lucide="bell"></i>
                <span>WhatsApp & Reminders</span>
                <span class="tab-badge tab-badge-pro">PRO</span>
            </button>
        <?php endif; ?>

        <!-- Tab 3: Plans & Subscription -->
        <button type="button" 
                class="settings-tab-btn <?= $activeTab === 'plan' ? 'active' : '' ?>" 
                data-tab="plan" 
                role="tab" 
                aria-selected="<?= $activeTab === 'plan' ? 'true' : 'false' ?>" 
                aria-controls="panel-plan">
            <i data-lucide="credit-card"></i>
            <span>Plans & Subscription</span>
            <?php if ($isSubscribed): ?>
                <span class="tab-badge tab-badge-active">ACTIVE</span>
            <?php endif; ?>
        </button>
    </div>
</nav>


<!-- =========================================================
     TAB 1: USER PROFILE & ACADEMIC SETTINGS
     ========================================================= -->
<section id="panel-profile" class="settings-tab-panel <?= $activeTab === 'profile' ? 'active' : '' ?>" role="tabpanel" aria-labelledby="tab-profile">
    
    <!-- Profile Completion Status Banner -->
    <div style="background:#fff; border:1px solid var(--border); border-radius:12px; padding:18px 24px; margin-bottom:24px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:16px;">
        <div style="flex-grow:1; min-width:240px;">
            <div style="display:flex; justify-content:space-between; margin-bottom:6px; font-size:0.875rem; font-weight:700; color:#334155;">
                <span>Profile Completion Score</span>
                <span style="color:var(--primary, #2563eb);"><?= e($currentCompletion) ?>%</span>
            </div>
            <div style="height:8px; width:100%; background:#e2e8f0; border-radius:999px; overflow:hidden;">
                <div style="height:100%; width:<?= min(100, (int)$currentCompletion) ?>%; background:var(--primary, #2563eb); border-radius:999px; transition:width 0.4s ease;"></div>
            </div>
        </div>
        <div style="font-size:0.8125rem; color:#64748b;">
            <?= (int)$currentCompletion >= 80 ? '🌟 Excellent! Your profile is ready for maximum scholarship matching.' : '💡 Complete education and preferences to unlock high-match scholarships.' ?>
        </div>
    </div>

    <!-- Section 1A: Personal & Contact Information -->
    <div class="settings-card">
        <div class="settings-card-header">
            <div>
                <h2 class="settings-card-title"><i data-lucide="user-check" style="color:var(--primary, #2563eb);"></i> Personal & Demographic Information</h2>
                <p class="settings-card-subtitle">Keep your basic demographic and contact details up to date.</p>
            </div>
        </div>

        <form action="<?= url('/profile/update') ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
            <input type="hidden" name="return_to" value="<?= url('/settings?tab=profile') ?>">

            <div class="settings-form-grid">
                <div class="settings-form-group">
                    <label class="settings-form-label" for="first_name">First Name *</label>
                    <input type="text" id="first_name" name="first_name" class="settings-form-input" required value="<?= e($user['first_name'] ?? '') ?>">
                </div>

                <div class="settings-form-group">
                    <label class="settings-form-label" for="last_name">Last Name *</label>
                    <input type="text" id="last_name" name="last_name" class="settings-form-input" required value="<?= e($user['last_name'] ?? '') ?>">
                </div>

                <div class="settings-form-group">
                    <label class="settings-form-label" for="email">Email Address *</label>
                    <input type="email" id="email" name="email" class="settings-form-input" required value="<?= e($user['email'] ?? '') ?>">
                </div>

                <div class="settings-form-group">
                    <label class="settings-form-label" for="phone">Mobile Phone (WhatsApp) *</label>
                    <input type="tel" id="phone" name="phone" class="settings-form-input" required value="<?= e($user['phone'] ?? '') ?>">
                </div>

                <div class="settings-form-group">
                    <label class="settings-form-label" for="date_of_birth">Date of Birth</label>
                    <input type="text" id="date_of_birth" name="date_of_birth" class="settings-form-input datepicker" placeholder="YYYY-MM-DD" value="<?= e($user['date_of_birth'] ?? '') ?>">
                </div>

                <div class="settings-form-group">
                    <label class="settings-form-label" for="gender">Gender</label>
                    <select id="gender" name="gender" class="settings-form-input">
                        <option value="">-- Select Gender --</option>
                        <option value="male" <?= ($user['gender'] ?? '') === 'male' ? 'selected' : '' ?>>Male</option>
                        <option value="female" <?= ($user['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
                        <option value="other" <?= ($user['gender'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>

                <div class="settings-form-group">
                    <label class="settings-form-label" for="nationality_country_id">Nationality Country</label>
                    <select id="nationality_country_id" name="nationality_country_id" class="settings-form-input select2-field">
                        <option value="">-- Select Nationality --</option>
                        <?php foreach ($countries as $c): ?>
                            <option value="<?= e($c['id']) ?>" <?= ((int)($user['nationality_country_id'] ?? 0) === (int)$c['id']) ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="settings-form-group">
                    <label class="settings-form-label" for="residence_country_id">Country of Residence</label>
                    <select id="residence_country_id" name="residence_country_id" class="settings-form-input select2-field">
                        <option value="">-- Select Country --</option>
                        <?php foreach ($countries as $c): ?>
                            <option value="<?= e($c['id']) ?>" <?= ((int)($user['residence_country_id'] ?? 0) === (int)$c['id']) ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="settings-form-group">
                    <label class="settings-form-label" for="residence_state_id">State / Province</label>
                    <select id="residence_state_id" name="residence_state_id" class="settings-form-input select2-field">
                        <option value="">-- Select State --</option>
                        <?php foreach ($states as $st): ?>
                            <option value="<?= e($st['id']) ?>" <?= ((int)($user['residence_state_id'] ?? 0) === (int)$st['id']) ? 'selected' : '' ?>><?= e($st['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="settings-form-group">
                    <label class="settings-form-label" for="city_id">City</label>
                    <select id="city_id" name="city_id" class="settings-form-input select2-field">
                        <option value="">-- Select City --</option>
                        <?php foreach ($cities as $ct): ?>
                            <option value="<?= e($ct['id']) ?>" <?= ((int)($user['city_id'] ?? 0) === (int)$ct['id']) ? 'selected' : '' ?>><?= e($ct['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="settings-form-group">
                    <label class="settings-form-label" for="postal_code">Postal Code</label>
                    <input type="text" id="postal_code" name="postal_code" class="settings-form-input" value="<?= e($user['postal_code'] ?? '') ?>">
                </div>

                <div class="settings-form-group">
                    <label class="settings-form-label" for="address">Street Address</label>
                    <input type="text" id="address" name="address" class="settings-form-input" value="<?= e($user['address'] ?? '') ?>">
                </div>

                <div class="settings-form-group full-width">
                    <label class="settings-form-label" for="bio">Short Bio / Career Goals</label>
                    <textarea id="bio" name="bio" class="settings-form-input" rows="3" style="height:auto;"><?= e($user['bio'] ?? '') ?></textarea>
                </div>

                <div class="settings-form-group">
                    <label class="settings-form-label" for="ielts_score">IELTS Band Score (Optional)</label>
                    <input type="number" step="0.5" min="0" max="9" id="ielts_score" name="ielts_score" class="settings-form-input" placeholder="e.g. 7.5" value="<?= e($user['ielts_score'] ?? '') ?>">
                </div>

                <div class="settings-form-group">
                    <label class="settings-form-label" for="toefl_score">TOEFL Score (Optional)</label>
                    <input type="number" step="1" min="0" max="120" id="toefl_score" name="toefl_score" class="settings-form-input" placeholder="e.g. 100" value="<?= e($user['toefl_score'] ?? '') ?>">
                </div>
            </div>

            <div style="margin-top: 24px; display:flex; justify-content:flex-end;">
                <button type="submit" class="btn btn-primary" style="padding:10px 24px;">
                    <i data-lucide="save"></i> Save Personal Details
                </button>
            </div>
        </form>
    </div>

    <!-- Section 1B: Academic History & Degrees -->
    <div class="settings-card">
        <div class="settings-card-header">
            <div>
                <h2 class="settings-card-title"><i data-lucide="graduation-cap" style="color:var(--primary, #2563eb);"></i> Academic History & Qualifications</h2>
                <p class="settings-card-subtitle">Manage your degrees, institutions, and CGPA/percentages for matching.</p>
            </div>
        </div>

        <!-- Existing Records List -->
        <div id="educationListContainer">
            <?php if (empty($education)): ?>
                <div style="text-align:center; padding:28px 16px; background:#f8fafc; border:1px dashed #cbd5e1; border-radius:8px; color:#64748b; margin-bottom:20px;">
                    <i data-lucide="book-open" style="width:32px; height:32px; color:#94a3b8; margin-bottom:8px;"></i>
                    <p style="margin:0; font-weight:500;">No academic records added yet. Add your recent qualification below.</p>
                </div>
            <?php else: ?>
                <?php foreach ($education as $edu): ?>
                    <div class="edu-record-card" id="edu-card-<?= e($edu['id']) ?>">
                        <div>
                            <h3 class="edu-record-title">
                                <?= e($edu['degree_title']) ?> in <?= e($edu['field_of_study']) ?>
                                <?php if (!empty($edu['is_current'])): ?>
                                    <span style="font-size:0.6875rem; background:#ecfdf5; color:#047857; padding:2px 8px; border-radius:4px; margin-left:6px; font-weight:700;">CURRENT</span>
                                <?php endif; ?>
                            </h3>
                            <p class="edu-record-meta">
                                <strong><?= e($edu['institution_name']) ?></strong> (<?= e($edu['degree_level']) ?>) • 
                                <?= !empty($edu['start_date']) ? date('Y', strtotime($edu['start_date'])) : 'N/A' ?> – 
                                <?= !empty($edu['is_current']) ? 'Present' : (!empty($edu['end_date']) ? date('Y', strtotime($edu['end_date'])) : 'N/A') ?>
                                <?php if (!empty($edu['cgpa'])): ?>
                                    • CGPA: <strong><?= e($edu['cgpa']) ?> / <?= e($edu['cgpa_scale'] ?? 4) ?></strong>
                                <?php elseif (!empty($edu['percentage'])): ?>
                                    • Percentage: <strong><?= e($edu['percentage']) ?>%</strong>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div style="display:flex; gap:8px;">
                            <form action="<?= url('/profile/education/delete') ?>" method="POST" onsubmit="return confirm('Are you sure you want to delete this qualification?');" style="margin:0;">
                                <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                                <input type="hidden" name="id" value="<?= e($edu['id']) ?>">
                                <input type="hidden" name="return_to" value="<?= url('/settings?tab=profile') ?>">
                                <button type="submit" class="btn btn-secondary btn-sm" style="color:#b91c1c; border-color:#fee2e2;">
                                    <i data-lucide="trash-2"></i> Delete
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Add New Education Record Form -->
        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:20px; margin-top:20px;">
            <h3 style="font-size:1rem; font-weight:700; color:#1e293b; margin:0 0 16px;">
                <i data-lucide="plus-circle" style="width:18px; height:18px; vertical-align:middle; color:var(--primary, #2563eb);"></i> Add Academic Record
            </h3>

            <form action="<?= url('/profile/education/add') ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                <input type="hidden" name="return_to" value="<?= url('/settings?tab=profile') ?>">

                <div class="settings-form-grid">
                    <div class="settings-form-group">
                        <label class="settings-form-label" for="edu_degree_level">Degree Level *</label>
                        <select id="edu_degree_level" name="degree_level" class="settings-form-input" required>
                            <option value="">-- Choose Level --</option>
                            <option value="High School">High School / Intermediate</option>
                            <option value="Associate Degree">Associate Degree</option>
                            <option value="Bachelor's">Bachelor's Degree</option>
                            <option value="Master's">Master's Degree</option>
                            <option value="MPhil">MPhil</option>
                            <option value="PhD">PhD / Doctorate</option>
                            <option value="Diploma">Diploma / Certificate</option>
                        </select>
                    </div>

                    <div class="settings-form-group">
                        <label class="settings-form-label" for="edu_degree_title">Degree / Certificate Title *</label>
                        <input type="text" id="edu_degree_title" name="degree_title" class="settings-form-input" placeholder="e.g. BS Computer Science" required>
                    </div>

                    <div class="settings-form-group">
                        <label class="settings-form-label" for="edu_field_of_study">Field of Study *</label>
                        <input type="text" id="edu_field_of_study" name="field_of_study" class="settings-form-input" placeholder="e.g. Computer Science" required>
                    </div>

                    <div class="settings-form-group">
                        <label class="settings-form-label" for="edu_institution_type">Institution Type *</label>
                        <select id="edu_institution_type" name="institution_type" class="settings-form-input" required>
                            <option value="university">University</option>
                            <option value="college">College</option>
                            <option value="school">School</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="settings-form-group full-width">
                        <label class="settings-form-label" for="edu_institution_name">Institution Name *</label>
                        <input type="text" id="edu_institution_name" name="institution_name" class="settings-form-input" placeholder="Enter full university or college name" required>
                    </div>

                    <div class="settings-form-group">
                        <label class="settings-form-label" for="edu_country_id">Country of Institution</label>
                        <select id="edu_country_id" name="country_id" class="settings-form-input select2-field">
                            <option value="">-- Select Country --</option>
                            <?php foreach ($countries as $c): ?>
                                <option value="<?= e($c['id']) ?>"><?= e($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="settings-form-group">
                        <label class="settings-form-label" for="grading_type">Grading System</label>
                        <select id="grading_type" class="settings-form-input" onchange="toggleGradingFields(this.value)">
                            <option value="cgpa">CGPA (Grade Point Average)</option>
                            <option value="percentage">Percentage (%)</option>
                        </select>
                    </div>

                    <div class="settings-form-group" id="group_cgpa">
                        <label class="settings-form-label" for="edu_cgpa">Obtained CGPA</label>
                        <div style="display:flex; gap:10px;">
                            <input type="number" step="0.01" min="0" max="10" id="edu_cgpa" name="cgpa" class="settings-form-input" placeholder="e.g. 3.65">
                            <select name="cgpa_scale" class="settings-form-input" style="width:120px;">
                                <option value="4.00">/ 4.00</option>
                                <option value="5.00">/ 5.00</option>
                            </select>
                        </div>
                    </div>

                    <div class="settings-form-group" id="group_percentage" style="display:none;">
                        <label class="settings-form-label" for="edu_percentage">Obtained Percentage</label>
                        <input type="number" step="0.1" min="0" max="100" id="edu_percentage" name="percentage" class="settings-form-input" placeholder="e.g. 85.5">
                    </div>

                    <div class="settings-form-group">
                        <label class="settings-form-label" for="edu_start_date">Start Date</label>
                        <input type="text" id="edu_start_date" name="start_date" class="settings-form-input datepicker" placeholder="YYYY-MM-DD">
                    </div>

                    <div class="settings-form-group">
                        <label class="settings-form-label" for="edu_end_date">End Date (or Expected)</label>
                        <input type="text" id="edu_end_date" name="end_date" class="settings-form-input datepicker" placeholder="YYYY-MM-DD">
                    </div>

                    <div class="settings-form-group full-width">
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; font-weight:500;">
                            <input type="checkbox" name="is_current" value="1" style="width:16px; height:16px;">
                            <span>I am currently enrolled / studying in this program</span>
                        </label>
                    </div>
                </div>

                <div style="margin-top:20px; display:flex; justify-content:flex-end;">
                    <button type="submit" class="btn btn-primary" style="padding:10px 20px;">
                        <i data-lucide="plus"></i> Add Qualification Record
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Section 1C: Scholarship Matching Preferences -->
    <div class="settings-card">
        <div class="settings-card-header">
            <div>
                <h2 class="settings-card-title"><i data-lucide="sliders" style="color:var(--primary, #2563eb);"></i> Scholarship Matching Preferences</h2>
                <p class="settings-card-subtitle">Set your target countries, fields of study, and funding criteria for personalized matching.</p>
            </div>
        </div>

        <form action="<?= url('/profile/preferences/update') ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
            <input type="hidden" name="return_to" value="<?= url('/settings?tab=profile') ?>">

            <div class="settings-form-grid">
                <div class="settings-form-group full-width">
                    <label class="settings-form-label" for="preferred_countries">Target Study Destinations (Countries)</label>
                    <select id="preferred_countries" name="preferred_countries[]" class="settings-form-input select2-field" multiple="multiple">
                        <?php foreach ($countries as $c): ?>
                            <option value="<?= e($c['id']) ?>" <?= in_array($c['id'], $prefCountries) ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span style="font-size:0.75rem; color:#64748b; margin-top:4px; display:block;">Leave empty to match opportunities in any destination worldwide.</span>
                </div>

                <div class="settings-form-group full-width">
                    <label class="settings-form-label" for="preferred_fields">Preferred Fields of Study (Up to 3)</label>
                    <select id="preferred_fields" name="preferred_fields[]" class="settings-form-input select2-field" multiple="multiple">
                        <?php foreach ($fieldsOfStudy as $f): ?>
                            <option value="<?= e($f['id']) ?>" <?= in_array($f['id'], $prefFields) ? 'selected' : '' ?>><?= e($f['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="settings-form-group full-width">
                    <label class="settings-form-label" for="preferred_degrees">Target Degree Levels</label>
                    <select id="preferred_degrees" name="preferred_degrees[]" class="settings-form-input select2-field" multiple="multiple">
                        <?php 
                        $allDegreeLevels = ["Bachelor's", "Master's", "PhD", "Associate Degree", "Diploma", "Postdoctoral"];
                        foreach ($allDegreeLevels as $deg): ?>
                            <option value="<?= e($deg) ?>" <?= in_array($deg, $prefDegrees) ? 'selected' : '' ?>><?= e($deg) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="settings-form-group">
                    <label class="settings-form-label" for="preferred_funding_type">Funding Tier Preference</label>
                    <select id="preferred_funding_type" name="preferred_funding_type" class="settings-form-input">
                        <option value="">Any Funding Tier</option>
                        <option value="fully_funded" <?= ($user['preferred_funding_type'] ?? '') === 'fully_funded' ? 'selected' : '' ?>>Fully Funded (Tuition + Stipend)</option>
                        <option value="partial" <?= ($user['preferred_funding_type'] ?? '') === 'partial' ? 'selected' : '' ?>>Partial Funding / Tuition Waiver</option>
                    </select>
                </div>

                <div class="settings-form-group">
                    <label class="settings-form-label" for="preferred_start_year">Target Intake / Admission Year</label>
                    <input type="number" id="preferred_start_year" name="preferred_start_year" class="settings-form-input" min="2024" max="2035" placeholder="e.g. 2026" value="<?= e($user['preferred_start_year'] ?? '') ?>">
                </div>
            </div>

            <div style="margin-top: 24px; display:flex; justify-content:flex-end;">
                <button type="submit" class="btn btn-primary" style="padding:10px 24px;">
                    <i data-lucide="check"></i> Save Matching Preferences
                </button>
            </div>
        </form>
    </div>

</section>


<!-- =========================================================
     TAB 2: WHATSAPP & SCHOLARSHIP DEADLINE REMINDERS
     (ONLY DISPLAYED IF ACTIVE SUBSCRIPTION EXISTS)
     ========================================================= -->
<?php if ($isSubscribed): ?>
<section id="panel-reminders" class="settings-tab-panel <?= $activeTab === 'reminders' ? 'active' : '' ?>" role="tabpanel" aria-labelledby="tab-reminders">
    
    <div class="settings-card">
        <div class="settings-card-header">
            <div>
                <h2 class="settings-card-title">
                    <i data-lucide="message-square" style="color:#25D366;"></i> WhatsApp Alert Delivery & Channels
                </h2>
                <p class="settings-card-subtitle">ScholarPlanner delivers real-time notifications to your verified WhatsApp account.</p>
            </div>
            <span class="tab-badge tab-badge-active" style="padding:6px 12px; font-size:0.75rem;">WHATSAPP ACTIVE</span>
        </div>

        <form action="<?= url('/profile/notifications/update') ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
            <input type="hidden" name="return_to" value="<?= url('/settings?tab=reminders') ?>">

            <!-- Current WhatsApp Number Card -->
            <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:10px; padding:16px 20px; margin-bottom:24px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:16px;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <div style="background:#25D366; color:#fff; width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                        <i data-lucide="phone" style="width:20px; height:20px;"></i>
                    </div>
                    <div>
                        <div style="font-size:0.9375rem; font-weight:700; color:#14532d;">Verified WhatsApp Number: <?= e($user['phone'] ?? 'Not configured') ?></div>
                        <div style="font-size:0.8125rem; color:#166534;">Direct messages will be dispatched to this number via our Meta verified gateway.</div>
                    </div>
                </div>
                <a href="<?= url('/settings?tab=profile') ?>" class="btn btn-secondary btn-sm" style="background:#fff;">Change Number</a>
            </div>

            <!-- New Match Alerts Toggles -->
            <div style="border-bottom:1px solid #f1f5f9; padding-bottom:20px; margin-bottom:20px;">
                <h3 style="font-size:1rem; font-weight:700; color:#1e293b; margin:0 0 8px;">New Matching Scholarship Notifications</h3>
                <p style="font-size:0.8125rem; color:#64748b; margin-top:0; margin-bottom:14px;">
                    Receive instant alerts when a newly published scholarship opportunity matches your profile.
                </p>
                <div style="display:flex; gap:24px; flex-wrap:wrap;">
                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; color:#1e293b;">
                        <input type="checkbox" name="new_match_whatsapp" value="1" <?= !empty($prefMap['matching_scholarship_alerts']['whatsapp']) ? 'checked' : '' ?> style="width:18px; height:18px;">
                        <span>WhatsApp Alerts (Recommended)</span>
                    </label>
                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; color:#1e293b;">
                        <input type="checkbox" name="new_match_email" value="1" <?= (!empty($prefMap['matching_scholarship_alerts']['email']) || !isset($prefMap['matching_scholarship_alerts'])) ? 'checked' : '' ?> style="width:18px; height:18px;">
                        <span>Email Alerts</span>
                    </label>
                </div>
            </div>

            <!-- Delivery Policy -->
            <div style="border-bottom:1px solid #f1f5f9; padding-bottom:20px; margin-bottom:20px;">
                <h3 style="font-size:1rem; font-weight:700; color:#1e293b; margin:0 0 8px;">Primary Delivery Channel</h3>
                <?php 
                    $preferredChannel = $userPref['preferred_channel'] ?? 'whatsapp';
                    $allowMultiChannel = (bool)($userPref['allow_multi_channel'] ?? 0);
                ?>
                <div style="display:flex; gap:24px; flex-wrap:wrap; margin-bottom:14px;">
                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem;">
                        <input type="radio" name="preferred_channel" value="whatsapp" <?= $preferredChannel === 'whatsapp' ? 'checked' : '' ?> style="width:16px; height:16px;">
                        <span>WhatsApp (Instant Alerts)</span>
                    </label>
                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem;">
                        <input type="radio" name="preferred_channel" value="email" <?= $preferredChannel === 'email' ? 'checked' : '' ?> style="width:16px; height:16px;">
                        <span>Email</span>
                    </label>
                </div>

                <label style="display:flex; align-items:flex-start; gap:10px; cursor:pointer; font-size:0.8125rem; color:#475569; background:#f8fafc; padding:12px; border-radius:8px; border:1px solid #e2e8f0;">
                    <input type="checkbox" name="allow_multi_channel" value="1" <?= $allowMultiChannel ? 'checked' : '' ?> style="margin-top:2px;">
                    <div>
                        <strong>Simultaneous Multi-Channel Delivery</strong>
                        <div style="color:#64748b; font-size:0.75rem;">Send alerts to both WhatsApp and Email simultaneously.</div>
                    </div>
                </label>
            </div>

            <!-- SCHOLARSHIP DEADLINE REMINDERS (DEFAULT: INACTIVE / OFF) -->
            <div style="margin-bottom: 24px;">
                <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                    <h3 style="font-size:1rem; font-weight:700; color:#1e293b; margin:0;">Scholarship Deadline Reminders</h3>
                    <span style="font-size:0.6875rem; background:#f1f5f9; color:#475569; padding:2px 8px; border-radius:10px; font-weight:600;">DEFAULT: INACTIVE</span>
                </div>
                <p style="font-size:0.8125rem; color:#64748b; margin-top:0; margin-bottom:14px;">
                    By default, deadline reminders are inactive for your account. You can enable automated reminders before closing dates below.
                </p>

                <?php 
                    $currentScope = $userPref['deadline_reminder_scope'] ?? 'off'; 
                    $activeDays = array_map('intval', explode(',', $userPref['deadline_reminder_days'] ?? '3,1'));
                ?>

                <!-- Scope Options -->
                <div style="display:flex; flex-direction:column; gap:10px; margin-bottom:18px;">
                    <label style="display:flex; align-items:flex-start; gap:12px; cursor:pointer; padding:14px; border:1px solid <?= $currentScope === 'off' ? '#2563eb' : '#e2e8f0' ?>; border-radius:8px; background:<?= $currentScope === 'off' ? '#eff6ff' : '#fff' ?>;">
                        <input type="radio" name="deadline_reminder_scope" value="off" <?= $currentScope === 'off' ? 'checked' : '' ?> style="margin-top:3px;">
                        <div>
                            <div style="font-size:0.875rem; font-weight:700; color:#1e293b;">Inactive: Do not send deadline reminders (Default)</div>
                            <div style="font-size:0.75rem; color:#64748b;">You will not receive any deadline reminders before scholarships close.</div>
                        </div>
                    </label>

                    <label style="display:flex; align-items:flex-start; gap:12px; cursor:pointer; padding:14px; border:1px solid <?= $currentScope === 'all' ? '#2563eb' : '#e2e8f0' ?>; border-radius:8px; background:<?= $currentScope === 'all' ? '#eff6ff' : '#fff' ?>;">
                        <input type="radio" name="deadline_reminder_scope" value="all" <?= $currentScope === 'all' ? 'checked' : '' ?> style="margin-top:3px;">
                        <div>
                            <div style="font-size:0.875rem; font-weight:700; color:#1e293b;">Active: Send reminders for all eligible matching scholarships</div>
                            <div style="font-size:0.75rem; color:#64748b;">Automatically triggers reminders on WhatsApp before application deadlines.</div>
                        </div>
                    </label>

                    <label style="display:flex; align-items:flex-start; gap:12px; cursor:pointer; padding:14px; border:1px solid <?= $currentScope === 'selected' ? '#2563eb' : '#e2e8f0' ?>; border-radius:8px; background:<?= $currentScope === 'selected' ? '#eff6ff' : '#fff' ?>;">
                        <input type="radio" name="deadline_reminder_scope" value="selected" <?= $currentScope === 'selected' ? 'checked' : '' ?> style="margin-top:3px;">
                        <div>
                            <div style="font-size:0.875rem; font-weight:700; color:#1e293b;">Custom: Remind me only for specific scholarships I bookmark/select</div>
                            <div style="font-size:0.75rem; color:#64748b;">Only chosen scholarships will send you deadline countdown alerts.</div>
                        </div>
                    </label>
                </div>

                <!-- Timing Offsets -->
                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:16px;">
                    <div style="font-size:0.875rem; font-weight:600; color:#1e293b; margin-bottom:10px;">
                        Send Countdown Reminders Prior To Closing Date:
                    </div>
                    <div style="display:flex; gap:20px; flex-wrap:wrap;">
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem;">
                            <input type="checkbox" name="reminder_days[]" value="1" <?= in_array(1, $activeDays, true) ? 'checked' : '' ?> style="width:16px; height:16px;">
                            <span>1 Day Before</span>
                        </label>
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem;">
                            <input type="checkbox" name="reminder_days[]" value="3" <?= in_array(3, $activeDays, true) ? 'checked' : '' ?> style="width:16px; height:16px;">
                            <span>3 Days Before</span>
                        </label>
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem;">
                            <input type="checkbox" name="reminder_days[]" value="7" <?= in_array(7, $activeDays, true) ? 'checked' : '' ?> style="width:16px; height:16px;">
                            <span>7 Days Before</span>
                        </label>
                    </div>
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end;">
                <button type="submit" class="btn btn-primary" style="padding:10px 24px;">
                    <i data-lucide="check"></i> Save Reminder & WhatsApp Settings
                </button>
            </div>
        </form>
    </div>

    <!-- Specific Scholarship Reminders List (Option C) -->
    <?php if (!empty($selectedReminders)): ?>
        <div class="settings-card">
            <h3 class="settings-card-title" style="margin-bottom:16px;">
                <i data-lucide="calendar" style="color:var(--primary, #2563eb);"></i> My Selected Scholarship Deadlines
            </h3>
            <div style="display:flex; flex-direction:column; gap:10px;">
                <?php foreach ($selectedReminders as $rem): ?>
                    <div style="display:flex; justify-content:space-between; align-items:center; padding:12px 16px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; flex-wrap:wrap; gap:12px;">
                        <div>
                            <div style="font-size:0.9375rem; font-weight:700; color:#1e293b;"><?= e($rem['title']) ?></div>
                            <div style="font-size:0.8125rem; color:#64748b;">
                                Deadline: <strong><?= !empty($rem['application_deadline']) ? date('M j, Y', strtotime($rem['application_deadline'])) : 'N/A' ?></strong>
                            </div>
                        </div>
                        <span style="font-size:0.75rem; background:#dcfce7; color:#166534; padding:4px 10px; border-radius:12px; font-weight:700;">
                            REMINDER ACTIVE
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

</section>
<?php endif; ?>


<!-- =========================================================
     TAB 3: PLANS & SUBSCRIPTION SETTINGS
     ========================================================= -->
<section id="panel-plan" class="settings-tab-panel <?= $activeTab === 'plan' ? 'active' : '' ?>" role="tabpanel" aria-labelledby="tab-plan">
    
    <!-- Current Plan Status Card -->
    <div class="settings-card">
        <div class="settings-card-header">
            <div>
                <h2 class="settings-card-title"><i data-lucide="award" style="color:var(--primary, #2563eb);"></i> Current Plan & Status</h2>
                <p class="settings-card-subtitle">Review your current subscription tier and access permissions.</p>
            </div>
            <?php if ($isSubscribed): ?>
                <span class="tab-badge tab-badge-active" style="padding:6px 14px; font-size:0.8125rem;">PRO SUBSCRIBER</span>
            <?php else: ?>
                <span style="background:#f1f5f9; color:#475569; padding:6px 14px; border-radius:12px; font-weight:700; font-size:0.8125rem;">FREE PLAN</span>
            <?php endif; ?>
        </div>

        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:16px; margin-bottom:20px;">
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:18px;">
                <div style="font-size:0.75rem; text-transform:uppercase; font-weight:700; color:#64748b;">Current Tier</div>
                <div style="font-size:1.25rem; font-weight:800; color:#0f172a; margin-top:4px;">
                    <?= e($activePlan['plan_name'] ?? 'Free') ?>
                </div>
            </div>

            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:18px;">
                <div style="font-size:0.75rem; text-transform:uppercase; font-weight:700; color:#64748b;">Subscription Status</div>
                <div style="font-size:1.25rem; font-weight:800; color:<?= $isSubscribed ? '#15803d' : '#475569' ?>; margin-top:4px;">
                    <?= $isSubscribed ? 'Active' : 'Free / Not Subscribed' ?>
                </div>
            </div>

            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:18px;">
                <div style="font-size:0.75rem; text-transform:uppercase; font-weight:700; color:#64748b;">Valid Until</div>
                <div style="font-size:1.125rem; font-weight:700; color:#0f172a; margin-top:4px;">
                    <?php if ($isSubscribed && !empty($subscription['ends_at'])): ?>
                        <?= date('F j, Y', strtotime($subscription['ends_at'])) ?>
                    <?php else: ?>
                        Lifetime Free
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Features Matrix for current user -->
        <div style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:18px; margin-bottom:20px;">
            <div style="font-size:0.875rem; font-weight:700; color:#1e293b; margin-bottom:12px;">Active Entitlements:</div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:12px; font-size:0.875rem;">
                <div style="display:flex; align-items:center; gap:8px;">
                    <i data-lucide="check" style="width:16px; height:16px; color:#10b981;"></i>
                    <span>Matching Limit: <strong><?= $isSubscribed ? 'Unlimited' : '5 Scholarships' ?></strong></span>
                </div>
                <div style="display:flex; align-items:center; gap:8px;">
                    <i data-lucide="<?= $isSubscribed ? 'check' : 'x' ?>" style="width:16px; height:16px; color:<?= $isSubscribed ? '#10b981' : '#94a3b8' ?>;"></i>
                    <span>Instant WhatsApp Alerts: <strong><?= $isSubscribed ? 'Enabled' : 'Disabled' ?></strong></span>
                </div>
                <div style="display:flex; align-items:center; gap:8px;">
                    <i data-lucide="<?= $isSubscribed ? 'check' : 'x' ?>" style="width:16px; height:16px; color:<?= $isSubscribed ? '#10b981' : '#94a3b8' ?>;"></i>
                    <span>Deadline Reminders (1, 3, 7 days): <strong><?= $isSubscribed ? 'Enabled' : 'Disabled' ?></strong></span>
                </div>
                <div style="display:flex; align-items:center; gap:8px;">
                    <i data-lucide="<?= $isSubscribed ? 'check' : 'x' ?>" style="width:16px; height:16px; color:<?= $isSubscribed ? '#10b981' : '#94a3b8' ?>;"></i>
                    <span>Application Tracker: <strong><?= $isSubscribed ? 'Full Access' : 'Limited' ?></strong></span>
                </div>
            </div>
        </div>

        <?php if (!$isSubscribed): ?>
            <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:10px; padding:18px 24px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:16px;">
                <div>
                    <h4 style="margin:0 0 4px; font-size:1rem; font-weight:700; color:#1e3a8a;">Ready to get instant WhatsApp alerts & reminders?</h4>
                    <p style="margin:0; font-size:0.875rem; color:#1d4ed8;">Upgrade to ScholarPlanner Premium to unlock deadline notifications directly on your phone.</p>
                </div>
                <a href="<?= url('/checkout?plan=premium-monthly') ?>" class="btn btn-primary" style="padding:10px 22px; font-weight:700;">
                    <i data-lucide="zap"></i> Buy Premium Subscription
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Available Plans Comparison Grid -->
    <div class="settings-card">
        <h2 class="settings-card-title" style="margin-bottom:6px;">Available Subscription Plans</h2>
        <p class="settings-card-subtitle" style="margin-bottom:20px;">Choose a plan that fits your study abroad and scholarship journey.</p>

        <div class="plans-grid">
            <!-- Free Plan -->
            <div class="plan-pricing-card <?= !$isSubscribed ? 'featured' : '' ?>">
                <div>
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <h3 style="margin:0; font-size:1.125rem; font-weight:700; color:#0f172a;">Free Plan</h3>
                        <?php if (!$isSubscribed): ?>
                            <span class="tab-badge" style="background:#e2e8f0; color:#334155;">CURRENT</span>
                        <?php endif; ?>
                    </div>
                    <div class="plan-price-tag">PKR 0 <span class="plan-price-currency">/ month</span></div>
                    <p style="font-size:0.8125rem; color:#64748b; margin:0 0 16px;">Free access for exploring scholarship opportunities.</p>

                    <ul style="list-style:none; padding:0; margin:0 0 24px; display:flex; flex-direction:column; gap:10px; font-size:0.875rem; color:#334155;">
                        <li style="display:flex; align-items:center; gap:8px;"><i data-lucide="check" style="width:16px; height:16px; color:#10b981;"></i> 5 Matched Scholarships</li>
                        <li style="display:flex; align-items:center; gap:8px;"><i data-lucide="check" style="width:16px; height:16px; color:#10b981;"></i> Standard Catalog Search</li>
                        <li style="display:flex; align-items:center; gap:8px;"><i data-lucide="check" style="width:16px; height:16px; color:#10b981;"></i> Basic Email Notifications</li>
                        <li style="display:flex; align-items:center; gap:8px; color:#94a3b8;"><i data-lucide="x" style="width:16px; height:16px; color:#cbd5e1;"></i> No WhatsApp Alerts</li>
                        <li style="display:flex; align-items:center; gap:8px; color:#94a3b8;"><i data-lucide="x" style="width:16px; height:16px; color:#cbd5e1;"></i> No Deadline Reminders</li>
                    </ul>
                </div>

                <div>
                    <?php if (!$isSubscribed): ?>
                        <button type="button" class="btn btn-secondary" style="width:100%; justify-content:center;" disabled>Active Plan</button>
                    <?php else: ?>
                        <span style="font-size:0.8125rem; color:#64748b; display:block; text-align:center;">Standard tier</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Premium Monthly Plan -->
            <div class="plan-pricing-card <?= $isSubscribed ? 'featured' : '' ?>" style="border-color:var(--primary, #2563eb);">
                <div style="position:absolute; top:-12px; right:20px; background:var(--primary, #2563eb); color:#fff; font-size:0.6875rem; font-weight:800; padding:3px 10px; border-radius:12px; letter-spacing:0.05em;">RECOMMENDED</div>

                <div>
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <h3 style="margin:0; font-size:1.125rem; font-weight:700; color:#0f172a;">Premium Monthly</h3>
                        <?php if ($isSubscribed): ?>
                            <span class="tab-badge tab-badge-active">ACTIVE</span>
                        <?php endif; ?>
                    </div>
                    <div class="plan-price-tag" style="color:var(--primary, #2563eb);">PKR 1,499 <span class="plan-price-currency">/ month</span></div>
                    <p style="font-size:0.8125rem; color:#64748b; margin:0 0 16px;">Full access with instant mobile notifications and alerts.</p>

                    <ul style="list-style:none; padding:0; margin:0 0 24px; display:flex; flex-direction:column; gap:10px; font-size:0.875rem; color:#334155;">
                        <li style="display:flex; align-items:center; gap:8px;"><i data-lucide="check" style="width:16px; height:16px; color:#10b981;"></i> <strong>Unlimited</strong> Scholarship Matches</li>
                        <li style="display:flex; align-items:center; gap:8px;"><i data-lucide="check" style="width:16px; height:16px; color:#10b981;"></i> <strong>Instant WhatsApp</strong> Scholarship Alerts</li>
                        <li style="display:flex; align-items:center; gap:8px;"><i data-lucide="check" style="width:16px; height:16px; color:#10b981;"></i> <strong>Deadline Countdown Reminders</strong> (1, 3, 7 days)</li>
                        <li style="display:flex; align-items:center; gap:8px;"><i data-lucide="check" style="width:16px; height:16px; color:#10b981;"></i> Full Application Tracker Access</li>
                        <li style="display:flex; align-items:center; gap:8px;"><i data-lucide="check" style="width:16px; height:16px; color:#10b981;"></i> Priority WhatsApp & Email Support</li>
                    </ul>
                </div>

                <div>
                    <?php if ($isSubscribed): ?>
                        <div style="background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46; padding:10px; border-radius:8px; text-align:center; font-weight:700; font-size:0.875rem;">
                            ✓ You are subscribed to this plan
                        </div>
                    <?php else: ?>
                        <a href="<?= url('/checkout?plan=premium-monthly') ?>" class="btn btn-primary" style="width:100%; justify-content:center; padding:12px; font-weight:700;">
                            <i data-lucide="credit-card"></i> Activate / Buy Subscription
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Billing & Payment Transactions History -->
    <?php if (!empty($transactions)): ?>
        <div class="settings-card">
            <h2 class="settings-card-title" style="margin-bottom:16px;">
                <i data-lucide="receipt" style="color:var(--primary, #2563eb);"></i> Billing & Invoice History
            </h2>
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; font-size:0.875rem; text-align:left;">
                    <thead>
                        <tr style="border-bottom:2px solid #e2e8f0; color:#475569;">
                            <th style="padding:10px 12px;">Invoice Date</th>
                            <th style="padding:10px 12px;">Reference</th>
                            <th style="padding:10px 12px;">Amount</th>
                            <th style="padding:10px 12px;">Method</th>
                            <th style="padding:10px 12px;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $tx): ?>
                            <tr style="border-bottom:1px solid #f1f5f9;">
                                <td style="padding:12px;"><?= date('M j, Y H:i', strtotime($tx['created_at'])) ?></td>
                                <td style="padding:12px; font-family:monospace;"><?= e($tx['transaction_reference'] ?? $tx['reference']) ?></td>
                                <td style="padding:12px; font-weight:600;"><?= e($tx['currency'] ?? 'PKR') ?> <?= number_format($tx['amount'], 2) ?></td>
                                <td style="padding:12px; text-transform:uppercase;"><?= e($tx['payment_provider'] ?? $tx['provider'] ?? 'JazzCash') ?></td>
                                <td style="padding:12px;">
                                    <?php if ($tx['status'] === 'completed'): ?>
                                        <span style="background:#dcfce7; color:#166534; padding:3px 8px; border-radius:4px; font-weight:700; font-size:0.75rem;">PAID</span>
                                    <?php elseif ($tx['status'] === 'pending'): ?>
                                        <span style="background:#fef3c7; color:#b45309; padding:3px 8px; border-radius:4px; font-weight:700; font-size:0.75rem;">PENDING</span>
                                    <?php else: ?>
                                        <span style="background:#fee2e2; color:#991b1b; padding:3px 8px; border-radius:4px; font-weight:700; font-size:0.75rem;"><?= strtoupper(e($tx['status'])) ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

</section>


<!-- SCRIPTING: TABS, SELECT2, FLATPICKR, CASCADING -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 1. Uxcel Navigational Tabs Controller
        const tabButtons = document.querySelectorAll('.settings-tab-btn');
        const tabPanels = document.querySelectorAll('.settings-tab-panel');

        function activateTab(tabKey) {
            let targetButton = document.querySelector(`.settings-tab-btn[data-tab="${tabKey}"]`);
            let targetPanel = document.getElementById(`panel-${tabKey}`);

            // If requested tab is not accessible, default to profile
            if (!targetButton || !targetPanel) {
                tabKey = 'profile';
                targetButton = document.querySelector('.settings-tab-btn[data-tab="profile"]');
                targetPanel = document.getElementById('panel-profile');
            }

            tabButtons.forEach(btn => {
                btn.classList.remove('active');
                btn.setAttribute('aria-selected', 'false');
            });
            tabPanels.forEach(panel => {
                panel.classList.remove('active');
            });

            if (targetButton && targetPanel) {
                targetButton.classList.add('active');
                targetButton.setAttribute('aria-selected', 'true');
                targetPanel.classList.add('active');
                
                // Update URL query param without page reload
                if (window.history && window.history.replaceState) {
                    const newUrl = new URL(window.location.href);
                    newUrl.searchParams.set('tab', tabKey);
                    window.history.replaceState(null, '', newUrl);
                }
            }

            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }

        tabButtons.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const tabKey = this.getAttribute('data-tab');
                activateTab(tabKey);
            });
        });

        // 2. Flatpickr Initializations
        if (typeof flatpickr !== 'undefined') {
            flatpickr('.datepicker', {
                dateFormat: 'Y-m-d',
                allowInput: true
            });
        }

        // 3. Select2 Initializations
        if (typeof $ !== 'undefined' && typeof $.fn.select2 !== 'undefined') {
            $('.select2-field').select2({
                width: '100%'
            });
        }

        // 4. Cascading Locations (Residence)
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

        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });

    function toggleGradingFields(type) {
        if (type === 'percentage') {
            document.getElementById('group_cgpa').style.display = 'none';
            document.getElementById('group_percentage').style.display = 'block';
        } else {
            document.getElementById('group_cgpa').style.display = 'block';
            document.getElementById('group_percentage').style.display = 'none';
        }
    }
</script>

<?php include ROOT_PATH . '/app/Views/layouts/student_footer.php'; ?>