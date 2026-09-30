<?php include ROOT_PATH . '/app/Views/layouts/admin_header.php'; ?>

<?php
$currentUser = $user ?? \App\Services\Auth::currentUser() ?? [];
$firstName = $currentUser['first_name'] ?? '';
$lastName = $currentUser['last_name'] ?? '';
$fullName = trim($firstName . ' ' . $lastName);
if (empty($fullName)) {
    $fullName = 'Administrator';
}

$initials = '';
if (!empty($firstName)) $initials .= strtoupper($firstName[0]);
if (!empty($lastName)) $initials .= strtoupper($lastName[0]);
if (empty($initials)) $initials = 'AD';

$roleName = strtoupper($currentUser['role_name'] ?? $_SESSION['role_name'] ?? 'Admin');
$email = $currentUser['email'] ?? '';
$phone = $currentUser['phone'] ?? '';
$status = $currentUser['status'] ?? 'active';
$createdAt = !empty($currentUser['created_at']) ? date('M d, Y', strtotime($currentUser['created_at'])) : 'N/A';
$lastLogin = !empty($currentUser['last_login_at']) ? date('M d, Y - h:i A', strtotime($currentUser['last_login_at'])) : 'Recent session';
$lastIp = $currentUser['last_login_ip'] ?? '127.0.0.1';
?>

<style>
/* ============================================================
   Profile Page Modern Styling & UI/UX Design System
   ============================================================ */
:root {
    --pf-primary: #2563eb;
    --pf-primary-hover: #1d4ed8;
    --pf-primary-light: #eff6ff;
    --pf-primary-border: #bfdbfe;
    --pf-slate-900: #0f172a;
    --pf-slate-800: #1e293b;
    --pf-slate-700: #334155;
    --pf-slate-600: #475569;
    --pf-slate-500: #64748b;
    --pf-slate-400: #94a3b8;
    --pf-slate-200: #e2e8f0;
    --pf-slate-100: #f1f5f9;
    --pf-slate-50: #f8fafc;
    --pf-emerald-600: #059669;
    --pf-emerald-50: #ecfdf5;
    --pf-emerald-200: #a7f3d0;
}

.profile-container {
    width: 100%;
    max-width: 1120px;
    margin: 0 auto;
    padding-bottom: 40px;
}

/* Breadcrumb & Header */
.profile-breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.8125rem;
    color: var(--pf-slate-500);
    margin-bottom: 12px;
}
.profile-breadcrumb a {
    color: var(--pf-slate-500);
    text-decoration: none;
    transition: color 150ms;
}
.profile-breadcrumb a:hover {
    color: var(--pf-primary);
}
.profile-breadcrumb i {
    width: 14px;
    height: 14px;
    color: var(--pf-slate-400);
}

.profile-page-header {
    margin-bottom: 24px;
}
.profile-page-title {
    font-size: 1.625rem;
    font-weight: 700;
    color: var(--pf-slate-900);
    letter-spacing: -0.02em;
    margin: 0 0 6px 0;
}
.profile-page-subtitle {
    font-size: 0.875rem;
    color: var(--pf-slate-500);
    margin: 0;
    line-height: 1.5;
}

/* Hero Profile Overview Card */
.profile-hero-card {
    background: #ffffff;
    border: 1px solid var(--pf-slate-200);
    border-radius: 16px;
    padding: 24px 28px;
    margin-bottom: 24px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
    position: relative;
    overflow: hidden;
}
.profile-hero-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #2563eb 0%, #3b82f6 50%, #60a5fa 100%);
}

.profile-hero-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    flex-wrap: wrap;
}

.profile-user-summary {
    display: flex;
    align-items: center;
    gap: 20px;
}

.profile-avatar-wrapper {
    position: relative;
    flex-shrink: 0;
}

.profile-avatar-circle {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    background: linear-gradient(135deg, #1e40af 0%, #2563eb 50%, #3b82f6 100%);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
    border: 3px solid #ffffff;
}

.profile-user-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.profile-display-name {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--pf-slate-900);
    line-height: 1.3;
}

.profile-badges-row {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    margin-top: 2px;
}

.badge-role {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 10px;
    background: var(--pf-primary-light);
    color: var(--pf-primary);
    border: 1px solid var(--pf-primary-border);
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}
.badge-role i {
    width: 12px;
    height: 12px;
}

.badge-status-active {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 3px 10px;
    background: var(--pf-emerald-50);
    color: var(--pf-emerald-600);
    border: 1px solid var(--pf-emerald-200);
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
}
.status-pulse-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #10b981;
    box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.25);
}

.profile-meta-chips {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.meta-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: var(--pf-slate-50);
    border: 1px solid var(--pf-slate-200);
    border-radius: 8px;
    font-size: 0.8125rem;
    color: var(--pf-slate-600);
}
.meta-chip i {
    width: 14px;
    height: 14px;
    color: var(--pf-slate-400);
}

/* Two Column Layout */
.profile-grid-layout {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 24px;
    align-items: start;
}

/* Card Styles */
.profile-card {
    background: #ffffff;
    border: 1px solid var(--pf-slate-200);
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    margin-bottom: 24px;
    overflow: hidden;
}

.profile-card-header {
    padding: 20px 24px;
    border-bottom: 1px solid var(--pf-slate-200);
    background: #ffffff;
    display: flex;
    align-items: flex-start;
    gap: 14px;
}

.card-header-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: var(--pf-primary-light);
    color: var(--pf-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.card-header-icon i {
    width: 20px;
    height: 20px;
}

.card-header-text h2 {
    font-size: 1.0625rem;
    font-weight: 700;
    color: var(--pf-slate-900);
    margin: 0 0 3px 0;
    line-height: 1.3;
}
.card-header-text p {
    font-size: 0.8125rem;
    color: var(--pf-slate-500);
    margin: 0;
    line-height: 1.4;
}

.profile-card-body {
    padding: 24px;
}

/* Form Controls & Styling */
.form-field-group {
    margin-bottom: 20px;
}
.form-field-group:last-child {
    margin-bottom: 0;
}

.field-label {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--pf-slate-700);
    margin-bottom: 6px;
}
.label-required {
    color: #ef4444;
    margin-left: 2px;
}
.label-hint {
    font-size: 0.75rem;
    font-weight: 400;
    color: var(--pf-slate-400);
}

.input-icon-wrap {
    position: relative;
    display: flex;
    align-items: center;
    width: 100%;
}

.input-icon-wrap .lead-icon {
    position: absolute;
    left: 14px;
    width: 17px;
    height: 17px;
    color: var(--pf-slate-400);
    pointer-events: none;
    transition: color 150ms ease;
    flex-shrink: 0;
}

.profile-form-control {
    width: 100%;
    height: 44px;
    padding: 10px 14px 10px 42px;
    font-family: inherit;
    font-size: 0.9375rem;
    color: var(--pf-slate-900);
    background-color: #ffffff;
    border: 1px solid var(--pf-slate-200);
    border-radius: 8px;
    box-sizing: border-box;
    transition: all 150ms ease;
    outline: none;
}

.profile-form-control:focus {
    border-color: var(--pf-primary);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    background-color: #ffffff;
}

.profile-form-control:focus + .lead-icon,
.input-icon-wrap:focus-within .lead-icon {
    color: var(--pf-primary);
}

.profile-form-control::placeholder {
    color: #94a3b8;
    font-size: 0.875rem;
}

/* Password with Eye Toggle */
.toggle-password-btn {
    position: absolute;
    right: 8px;
    background: transparent;
    border: none;
    color: var(--pf-slate-400);
    cursor: pointer;
    width: 32px;
    height: 32px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    transition: all 150ms;
}
.toggle-password-btn:hover {
    color: var(--pf-slate-700);
    background-color: var(--pf-slate-100);
}
.toggle-password-btn i {
    width: 16px;
    height: 16px;
}

.field-help-text {
    font-size: 0.75rem;
    color: var(--pf-slate-500);
    margin-top: 6px;
    display: flex;
    align-items: center;
    gap: 4px;
    line-height: 1.4;
}

/* Form 2-Column Responsive Grid */
.form-row-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 20px;
}

/* Password Match Indicator */
.password-check-box {
    background-color: var(--pf-slate-50);
    border: 1px solid var(--pf-slate-200);
    border-radius: 8px;
    padding: 12px 16px;
    margin-top: 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
    font-size: 0.75rem;
}
.check-item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: var(--pf-slate-500);
    font-weight: 500;
    transition: color 150ms;
}
.check-item i {
    width: 14px;
    height: 14px;
}
.check-item.valid {
    color: var(--pf-emerald-600);
    font-weight: 600;
}
.check-item.invalid {
    color: #ef4444;
}

/* Form Actions Card / Footer */
.form-actions-card {
    background: #ffffff;
    border: 1px solid var(--pf-slate-200);
    border-radius: 12px;
    padding: 16px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
}

.form-actions-left {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.8125rem;
    color: var(--pf-slate-500);
}
.form-actions-left i {
    width: 16px;
    height: 16px;
    color: var(--pf-slate-400);
}

.form-actions-right {
    display: flex;
    align-items: center;
    gap: 12px;
}

.btn-profile-secondary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    height: 42px;
    padding: 0 18px;
    background: #ffffff;
    color: var(--pf-slate-700);
    border: 1px solid var(--pf-slate-200);
    border-radius: 8px;
    font-family: inherit;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 150ms ease;
    text-decoration: none;
}
.btn-profile-secondary:hover {
    background: var(--pf-slate-50);
    border-color: #cbd5e1;
    color: var(--pf-slate-900);
}

.btn-profile-primary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    height: 42px;
    padding: 0 22px;
    background: var(--pf-primary);
    color: #ffffff;
    border: 1px solid var(--pf-primary);
    border-radius: 8px;
    font-family: inherit;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 1px 2px rgba(37, 99, 235, 0.2);
    transition: all 150ms ease;
}
.btn-profile-primary:hover {
    background: var(--pf-primary-hover);
    border-color: var(--pf-primary-hover);
    box-shadow: 0 4px 10px rgba(37, 99, 235, 0.25);
    transform: translateY(-1px);
}
.btn-profile-primary:active {
    transform: translateY(0);
}

@keyframes pfSpin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
.spin {
    animation: pfSpin 0.8s linear infinite;
    display: inline-block;
}

/* Sidebar Info Cards */
.sidebar-info-list {
    display: flex;
    flex-direction: column;
}
.sidebar-info-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid var(--pf-slate-100);
    font-size: 0.8125rem;
}
.sidebar-info-row:first-child {
    padding-top: 0;
}
.sidebar-info-row:last-child {
    border-bottom: none;
    padding-bottom: 0;
}
.sidebar-label {
    color: var(--pf-slate-500);
    display: flex;
    align-items: center;
    gap: 6px;
    font-weight: 500;
}
.sidebar-label i {
    width: 14px;
    height: 14px;
    color: var(--pf-slate-400);
}
.sidebar-val {
    color: var(--pf-slate-800);
    font-weight: 600;
    text-align: right;
}

.guidelines-list {
    margin: 0;
    padding: 0;
    list-style: none;
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.guideline-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    font-size: 0.8125rem;
    color: var(--pf-slate-600);
    line-height: 1.45;
}
.guideline-icon {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: var(--pf-emerald-50);
    color: var(--pf-emerald-600);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    margin-top: 2px;
}
.guideline-icon i {
    width: 11px;
    height: 11px;
}

/* ============================================================
   Responsive Media Queries for Desktop, Tablet, & Mobile
   ============================================================ */
@media (max-width: 991px) {
    .profile-grid-layout {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    .profile-hero-content {
        flex-direction: column;
        align-items: flex-start;
    }
    .profile-meta-chips {
        width: 100%;
        justify-content: flex-start;
    }
}

@media (max-width: 640px) {
    .profile-hero-card {
        padding: 20px 16px;
    }
    .profile-user-summary {
        flex-direction: column;
        align-items: center;
        text-align: center;
        width: 100%;
    }
    .profile-badges-row {
        justify-content: center;
    }
    .profile-meta-chips {
        flex-direction: column;
        align-items: stretch;
        gap: 8px;
    }
    .meta-chip {
        justify-content: center;
    }
    .form-row-2 {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    .profile-card-header {
        padding: 16px;
    }
    .profile-card-body {
        padding: 16px;
    }
    .form-actions-card {
        flex-direction: column;
        padding: 16px;
        align-items: stretch;
    }
    .form-actions-left {
        justify-content: center;
    }
    .form-actions-right {
        flex-direction: column;
        width: 100%;
    }
    .btn-profile-secondary,
    .btn-profile-primary {
        width: 100%;
    }
}
</style>

<div class="profile-container">
    <!-- Breadcrumb Navigation -->
    <div class="profile-breadcrumb">
        <a href="<?= url('/admin') ?>">Dashboard</a>
        <i data-lucide="chevron-right"></i>
        <span>Admin Profile</span>
    </div>

    <!-- Page Header -->
    <div class="profile-page-header">
        <h1 class="profile-page-title">My Admin Profile Settings</h1>
        <p class="profile-page-subtitle">Manage your personal administrative contact details, review credentials, and change your password securely.</p>
    </div>

    <!-- Hero Overview Banner Card -->
    <div class="profile-hero-card">
        <div class="profile-hero-content">
            <div class="profile-user-summary">
                <div class="profile-avatar-wrapper">
                    <div class="profile-avatar-circle" title="<?= e($fullName) ?>">
                        <?= e($initials) ?>
                    </div>
                </div>
                <div class="profile-user-info">
                    <div class="profile-display-name"><?= e($fullName) ?></div>
                    <div class="profile-badges-row">
                        <span class="badge-role">
                            <i data-lucide="shield-check"></i>
                            <?= e($roleName) ?>
                        </span>
                        <span class="badge-status-active">
                            <span class="status-pulse-dot"></span>
                            Active Account
                        </span>
                    </div>
                </div>
            </div>

            <div class="profile-meta-chips">
                <div class="meta-chip" title="Account Email">
                    <i data-lucide="mail"></i>
                    <span><?= e($email) ?></span>
                </div>
                <div class="meta-chip" title="Registered Date">
                    <i data-lucide="calendar"></i>
                    <span>Member since <?= e($createdAt) ?></span>
                </div>
                <div class="meta-chip" title="Last Active">
                    <i data-lucide="clock"></i>
                    <span>Last Login: <?= e($lastLogin) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Two-Column Form & Overview Layout -->
    <div class="profile-grid-layout">
        <!-- Main Form Column -->
        <div class="profile-forms-column">
            <form action="<?= url('/admin/profile') ?>" method="POST" id="profileSettingsForm">
                <input type="hidden" name="csrf_token" value="<?= e($csrf_token ?? \App\Helpers\Security::csrfToken()) ?>">

                <!-- Card 1: Personal & Contact Information -->
                <div class="profile-card">
                    <div class="profile-card-header">
                        <div class="card-header-icon">
                            <i data-lucide="user-cog"></i>
                        </div>
                        <div class="card-header-text">
                            <h2>Personal & Contact Details</h2>
                            <p>Update your administrative name and primary email address used for system logins.</p>
                        </div>
                    </div>

                    <div class="profile-card-body">
                        <!-- First Name & Last Name (2 columns) -->
                        <div class="form-row-2">
                            <div class="form-field-group">
                                <label class="field-label" for="first_name">
                                    <span>First Name <span class="label-required">*</span></span>
                                </label>
                                <div class="input-icon-wrap">
                                    <input type="text" 
                                           name="first_name" 
                                           id="first_name" 
                                           class="profile-form-control" 
                                           value="<?= e($firstName) ?>" 
                                           placeholder="e.g. John" 
                                           required 
                                           maxlength="50" 
                                           autocomplete="given-name">
                                    <i data-lucide="user" class="lead-icon"></i>
                                </div>
                            </div>

                            <div class="form-field-group">
                                <label class="field-label" for="last_name">
                                    <span>Last Name</span>
                                    <span class="label-hint">Optional</span>
                                </label>
                                <div class="input-icon-wrap">
                                    <input type="text" 
                                           name="last_name" 
                                           id="last_name" 
                                           class="profile-form-control" 
                                           value="<?= e($lastName) ?>" 
                                           placeholder="e.g. Doe" 
                                           maxlength="50" 
                                           autocomplete="family-name">
                                    <i data-lucide="user" class="lead-icon"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Email & Phone (2 columns) -->
                        <div class="form-row-2">
                            <div class="form-field-group">
                                <label class="field-label" for="email">
                                    <span>Email Address <span class="label-required">*</span></span>
                                </label>
                                <div class="input-icon-wrap">
                                    <input type="email" 
                                           name="email" 
                                           id="email" 
                                           class="profile-form-control" 
                                           value="<?= e($email) ?>" 
                                           placeholder="admin@scholarplanner.com" 
                                           required 
                                           maxlength="100" 
                                           autocomplete="email">
                                    <i data-lucide="mail" class="lead-icon"></i>
                                </div>
                                <div class="field-help-text">
                                    <i data-lucide="info" style="width: 12px; height: 12px;"></i>
                                    <span>Used for portal logins, alerts, and system notifications.</span>
                                </div>
                            </div>

                            <div class="form-field-group">
                                <label class="field-label" for="phone">
                                    <span>Phone Number</span>
                                    <span class="label-hint">Optional</span>
                                </label>
                                <div class="input-icon-wrap">
                                    <input type="tel" 
                                           name="phone" 
                                           id="phone" 
                                           class="profile-form-control" 
                                           value="<?= e($phone) ?>" 
                                           placeholder="e.g. +1 234 567 8900" 
                                           maxlength="30" 
                                           autocomplete="tel">
                                    <i data-lucide="phone" class="lead-icon"></i>
                                </div>
                                <div class="field-help-text">
                                    <span>For emergency administrative notifications.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Security & Password Update -->
                <div class="profile-card">
                    <div class="profile-card-header">
                        <div class="card-header-icon" style="background: #fef3c7; color: #d97706;">
                            <i data-lucide="shield-alert"></i>
                        </div>
                        <div class="card-header-text">
                            <h2>Security & Password Update</h2>
                            <p>Leave these fields blank if you do not wish to alter your account password.</p>
                        </div>
                    </div>

                    <div class="profile-card-body">
                        <!-- Current Password -->
                        <div class="form-field-group">
                            <label class="field-label" for="current_password">
                                <span>Current Password</span>
                                <span class="label-hint">Required only if changing password</span>
                            </label>
                            <div class="input-icon-wrap">
                                <input type="password" 
                                       name="current_password" 
                                       id="current_password" 
                                       class="profile-form-control" 
                                       placeholder="Enter current password to verify identity" 
                                       autocomplete="current-password">
                                <i data-lucide="key" class="lead-icon"></i>
                                <button type="button" class="toggle-password-btn" data-target="current_password" aria-label="Toggle password visibility">
                                    <i data-lucide="eye"></i>
                                </button>
                            </div>
                        </div>

                        <!-- New Password & Confirm Password (2 columns) -->
                        <div class="form-row-2">
                            <div class="form-field-group">
                                <label class="field-label" for="new_password">
                                    <span>New Password</span>
                                </label>
                                <div class="input-icon-wrap">
                                    <input type="password" 
                                           name="new_password" 
                                           id="new_password" 
                                           class="profile-form-control" 
                                           placeholder="Min. 8 characters" 
                                           autocomplete="new-password">
                                    <i data-lucide="lock" class="lead-icon"></i>
                                    <button type="button" class="toggle-password-btn" data-target="new_password" aria-label="Toggle password visibility">
                                        <i data-lucide="eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="form-field-group">
                                <label class="field-label" for="confirm_password">
                                    <span>Confirm New Password</span>
                                </label>
                                <div class="input-icon-wrap">
                                    <input type="password" 
                                           name="confirm_password" 
                                           id="confirm_password" 
                                           class="profile-form-control" 
                                           placeholder="Re-enter new password" 
                                           autocomplete="new-password">
                                    <i data-lucide="check-check" class="lead-icon"></i>
                                    <button type="button" class="toggle-password-btn" data-target="confirm_password" aria-label="Toggle password visibility">
                                        <i data-lucide="eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Password Helper Checks -->
                        <div class="password-check-box" id="passwordCheckWidget">
                            <span class="check-item" id="chkLength">
                                <i data-lucide="circle-dot"></i>
                                <span>At least 8 characters</span>
                            </span>
                            <span class="check-item" id="chkMatch">
                                <i data-lucide="circle-dot"></i>
                                <span>Passwords match</span>
                            </span>
                            <span class="check-item" id="chkCurrent">
                                <i data-lucide="circle-dot"></i>
                                <span>Current password provided</span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Form Action Buttons -->
                <div class="form-actions-card">
                    <div class="form-actions-left">
                        <i data-lucide="shield"></i>
                        <span>Changes will take effect across your active session immediately.</span>
                    </div>
                    <div class="form-actions-right">
                        <button type="reset" class="btn-profile-secondary" id="btnResetForm">
                            <i data-lucide="rotate-ccw"></i>
                            <span>Discard</span>
                        </button>
                        <button type="submit" class="btn-profile-primary" id="btnSaveProfile">
                            <i data-lucide="save"></i>
                            <span>Save Profile Settings</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Sidebar Info Column -->
        <div class="profile-sidebar-column">
            <!-- Account Overview Details -->
            <div class="profile-card">
                <div class="profile-card-header">
                    <div class="card-header-icon" style="background: #f1f5f9; color: var(--pf-slate-700);">
                        <i data-lucide="badge-check"></i>
                    </div>
                    <div class="card-header-text">
                        <h2>Account Overview</h2>
                        <p>Credentials and activity details</p>
                    </div>
                </div>
                <div class="profile-card-body">
                    <div class="sidebar-info-list">
                        <div class="sidebar-info-row">
                            <span class="sidebar-label">
                                <i data-lucide="hash"></i>
                                <span>User ID</span>
                            </span>
                            <span class="sidebar-val">
                                <code style="background: var(--pf-slate-100); padding: 2px 6px; border-radius: 4px; font-size: 0.8125rem;">#<?= e($currentUser['id'] ?? '') ?></code>
                            </span>
                        </div>
                        <div class="sidebar-info-row">
                            <span class="sidebar-label">
                                <i data-lucide="shield"></i>
                                <span>Role</span>
                            </span>
                            <span class="sidebar-val">Admin</span>
                        </div>
                        <div class="sidebar-info-row">
                            <span class="sidebar-label">
                                <i data-lucide="check-circle-2"></i>
                                <span>Status</span>
                            </span>
                            <span class="sidebar-val" style="color: var(--pf-emerald-600);"><?= ucfirst(e($status)) ?></span>
                        </div>
                        <div class="sidebar-info-row">
                            <span class="sidebar-label">
                                <i data-lucide="globe"></i>
                                <span>Login IP</span>
                            </span>
                            <span class="sidebar-val"><?= e($lastIp) ?></span>
                        </div>
                        <div class="sidebar-info-row">
                            <span class="sidebar-label">
                                <i data-lucide="calendar-clock"></i>
                                <span>Registered</span>
                            </span>
                            <span class="sidebar-val"><?= e($createdAt) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Security Guidelines Card -->
            <div class="profile-card">
                <div class="profile-card-header">
                    <div class="card-header-icon" style="background: var(--pf-emerald-50); color: var(--pf-emerald-600);">
                        <i data-lucide="lock"></i>
                    </div>
                    <div class="card-header-text">
                        <h2>Security Guidelines</h2>
                        <p>Best practices for admin safety</p>
                    </div>
                </div>
                <div class="profile-card-body">
                    <ul class="guidelines-list">
                        <li class="guideline-item">
                            <span class="guideline-icon">
                                <i data-lucide="check"></i>
                            </span>
                            <span>Choose a password with 8+ characters, including letters, digits, and symbols.</span>
                        </li>
                        <li class="guideline-item">
                            <span class="guideline-icon">
                                <i data-lucide="check"></i>
                            </span>
                            <span>Never share your administrative account login or passwords with anyone.</span>
                        </li>
                        <li class="guideline-item">
                            <span class="guideline-icon">
                                <i data-lucide="check"></i>
                            </span>
                            <span>Always log out of the ScholarPlanner administrative portal when finished.</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Password Visibility Toggle Functionality
    const toggleBtns = document.querySelectorAll('.toggle-password-btn');
    toggleBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            if (!input) return;

            const isPassword = input.getAttribute('type') === 'password';
            input.setAttribute('type', isPassword ? 'text' : 'password');

            // Switch Lucide Icon
            const icon = this.querySelector('i');
            if (icon) {
                icon.setAttribute('data-lucide', isPassword ? 'eye-off' : 'eye');
                if (window.lucide && typeof lucide.createIcons === 'function') {
                    lucide.createIcons();
                }
            }
        });
    });

    // 2. Real-time Live Password Validation Checks
    const currPassInput = document.getElementById('current_password');
    const newPassInput = document.getElementById('new_password');
    const confirmPassInput = document.getElementById('confirm_password');

    const chkLength = document.getElementById('chkLength');
    const chkMatch = document.getElementById('chkMatch');
    const chkCurrent = document.getElementById('chkCurrent');

    function updatePasswordIndicators() {
        const currVal = currPassInput ? currPassInput.value : '';
        const newVal = newPassInput ? newPassInput.value : '';
        const confirmVal = confirmPassInput ? confirmPassInput.value : '';

        // If no password change is attempted, keep neutral
        if (newVal === '' && confirmVal === '' && currVal === '') {
            resetIndicator(chkLength, 'At least 8 characters');
            resetIndicator(chkMatch, 'Passwords match');
            resetIndicator(chkCurrent, 'Current password provided');
            return;
        }

        // Length Check
        if (newVal.length >= 8) {
            setIndicator(chkLength, true, 'At least 8 characters');
        } else if (newVal.length > 0) {
            setIndicator(chkLength, false, 'Min 8 characters (' + newVal.length + '/8)');
        } else {
            resetIndicator(chkLength, 'At least 8 characters');
        }

        // Match Check
        if (newVal !== '' && confirmVal !== '') {
            if (newVal === confirmVal) {
                setIndicator(chkMatch, true, 'Passwords match');
            } else {
                setIndicator(chkMatch, false, 'Passwords do not match');
            }
        } else {
            resetIndicator(chkMatch, 'Passwords match');
        }

        // Current Password Check
        if (currVal.length > 0) {
            setIndicator(chkCurrent, true, 'Current password provided');
        } else if (newVal.length > 0 || confirmVal.length > 0) {
            setIndicator(chkCurrent, false, 'Current password required');
        } else {
            resetIndicator(chkCurrent, 'Current password provided');
        }
    }

    function setIndicator(el, isValid, text) {
        if (!el) return;
        el.classList.remove('valid', 'invalid');
        el.classList.add(isValid ? 'valid' : 'invalid');
        const icon = el.querySelector('i');
        if (icon) {
            icon.setAttribute('data-lucide', isValid ? 'circle-check' : 'circle-x');
        }
        const span = el.querySelector('span');
        if (span) span.textContent = text;
        if (window.lucide && typeof lucide.createIcons === 'function') {
            lucide.createIcons();
        }
    }

    function resetIndicator(el, text) {
        if (!el) return;
        el.classList.remove('valid', 'invalid');
        const icon = el.querySelector('i');
        if (icon) {
            icon.setAttribute('data-lucide', 'circle-dot');
        }
        const span = el.querySelector('span');
        if (span) span.textContent = text;
        if (window.lucide && typeof lucide.createIcons === 'function') {
            lucide.createIcons();
        }
    }

    if (currPassInput) currPassInput.addEventListener('input', updatePasswordIndicators);
    if (newPassInput) newPassInput.addEventListener('input', updatePasswordIndicators);
    if (confirmPassInput) confirmPassInput.addEventListener('input', updatePasswordIndicators);

    // 3. Reset Button Handler
    const resetBtn = document.getElementById('btnResetForm');
    if (resetBtn) {
        resetBtn.addEventListener('click', function () {
            setTimeout(updatePasswordIndicators, 50);
        });
    }

    // 4. Form Submission Guard
    const profileForm = document.getElementById('profileSettingsForm');
    if (profileForm) {
        profileForm.addEventListener('submit', function (e) {
            const newVal = newPassInput ? newPassInput.value : '';
            const confirmVal = confirmPassInput ? confirmPassInput.value : '';
            const currVal = currPassInput ? currPassInput.value : '';

            if (newVal !== '' || confirmVal !== '') {
                if (currVal === '') {
                    e.preventDefault();
                    alert('Please enter your current password to authorize a password change.');
                    currPassInput.focus();
                    return false;
                }
                if (newVal.length < 8) {
                    e.preventDefault();
                    alert('Your new password must be at least 8 characters long.');
                    newPassInput.focus();
                    return false;
                }
                if (newVal !== confirmVal) {
                    e.preventDefault();
                    alert('New password and password confirmation do not match.');
                    confirmPassInput.focus();
                    return false;
                }
            }

            const btnSave = document.getElementById('btnSaveProfile');
            if (btnSave) {
                btnSave.disabled = true;
                btnSave.innerHTML = '<i data-lucide="loader" class="spin"></i> Saving Settings...';
                if (window.lucide && typeof lucide.createIcons === 'function') {
                    lucide.createIcons();
                }
            }
        });
    }

    // Initialize Lucide icons on page load
    if (window.lucide && typeof lucide.createIcons === 'function') {
        lucide.createIcons();
    }
});
</script>

<?php include ROOT_PATH . '/app/Views/layouts/admin_footer.php'; ?>
