<?php include ROOT_PATH . '/app/Views/layouts/admin_header.php'; ?>

<style>
    .form-card {
        background: #fff;
        border: 1px solid var(--border-slate-200);
        border-radius: 12px;
        padding: 30px;
        max-width: 600px;
        margin: 0 auto;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }
</style>

<div class="form-card">
    <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0 0 8px 0; color: #1e293b;">My Admin Profile Settings</h1>
    <p style="margin: 0 0 24px 0; color: #64748b; font-size: 0.875rem;">Modify personal administrative details and change login passwords securely.</p>

    <form action="/admin/profile" method="POST">
        <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Security::csrfToken() ?>">

        <div class="form-group">
            <label class="form-label" for="first_name">First Name</label>
            <input type="text" name="first_name" id="first_name" class="form-control" value="<?= e($user['first_name']) ?>" required>
        </div>

        <div class="form-group">
            <label class="form-label" for="last_name">Last Name</label>
            <input type="text" name="last_name" id="last_name" class="form-control" value="<?= e($user['last_name']) ?>">
        </div>

        <div class="form-group">
            <label class="form-label" for="email">Email Address</label>
            <input type="email" name="email" id="email" class="form-control" value="<?= e($user['email']) ?>" required>
        </div>

        <div style="margin-top: 32px; border-top: 1px solid var(--border-slate-200); padding-top: 24px;">
            <h3 style="font-size: 1.125rem; font-weight: 700; color: #1e293b; margin-top: 0; margin-bottom: 16px;">Update Password</h3>
            
            <div class="form-group">
                <label class="form-label" for="current_password">Current Password</label>
                <input type="password" name="current_password" id="current_password" class="form-control" placeholder="Enter current password to verify">
            </div>

            <div class="form-group">
                <label class="form-label" for="new_password">New Password (min 8 chars)</label>
                <input type="password" name="new_password" id="new_password" class="form-control" placeholder="Enter new password">
            </div>
        </div>

        <div style="margin-top: 30px; display: flex; gap: 12px; justify-content: flex-end;">
            <button type="submit" class="btn btn-primary">Save Profile Settings</button>
        </div>
    </form>
</div>

<?php include ROOT_PATH . '/app/Views/layouts/admin_footer.php'; ?>
