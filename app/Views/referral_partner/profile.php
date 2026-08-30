<?php include ROOT_PATH . '/app/Views/layouts/referral_partner_header.php'; ?>

<style>
    .card {
        background: white;
        border: 1px solid var(--border-slate-200);
        border-radius: 12px;
        padding: 28px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        max-width: 500px;
    }
    .card-title {
        font-size: 1.125rem;
        font-weight: 700;
        margin-top: 0;
        margin-bottom: 16px;
    }
</style>

<div style="margin-bottom: 24px;">
    <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0; color: #1e293b;">Profile Settings</h1>
    <p style="margin: 4px 0 0 0; color: #64748b; font-size: 0.875rem;">Manage your login credentials and change password.</p>
</div>

<div class="card">
    <h2 class="card-title">Change Password</h2>
    <form action="<?= url('/referral-partner/profile') ?>" method="POST">
        <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">

        <div class="form-group">
            <label class="form-label" for="current_password">Current Password</label>
            <input type="password" name="current_password" id="current_password" class="form-control" placeholder="Enter your current password" required>
        </div>

        <div class="form-group">
            <label class="form-label" for="new_password">New Password</label>
            <input type="password" name="new_password" id="new_password" class="form-control" placeholder="At least 8 characters" required>
        </div>

        <div class="form-group">
            <label class="form-label" for="confirm_password">Confirm New Password</label>
            <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Repeat new password" required>
        </div>

        <button type="submit" class="btn btn-primary" style="margin-top: 12px; width: 100%; justify-content: center;">Update Password</button>
    </form>
</div>

<?php include ROOT_PATH . '/app/Views/layouts/referral_partner_footer.php'; ?>
