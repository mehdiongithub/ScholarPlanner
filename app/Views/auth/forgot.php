<?php
$title = 'Reset password';
$subtitle = "Enter your email and we'll help you log back in";
include ROOT_PATH . '/app/Views/layouts/auth_header.php';
?>

<style>
    .dev-box {
        background: #fffbeb;
        border: 1px solid #fef3c7;
        padding: 14px;
        border-radius: var(--radius-lg);
        margin-bottom: 20px;
        font-size: 0.875rem;
        color: #92400e;
        word-break: break-all;
    }
</style>

<?php if (!empty($success_message)): ?>
    <div class="alert alert-success" role="alert">
        <?= e($success_message) ?>
    </div>
<?php endif; ?>

<?php if (!empty($dev_reset_link)): ?>
    <div class="dev-box">
        <strong>[LOCAL DEV ONLY] Reset Link:</strong><br>
        <a href="<?= e($dev_reset_link) ?>" style="color: #b45309; text-decoration: underline; font-weight: 500;">
            <?= e($dev_reset_link) ?>
        </a>
    </div>
<?php endif; ?>

<?php if (!empty($errors['csrf'])): ?>
    <div class="alert alert-danger" role="alert">
        <?= e($errors['csrf']) ?>
    </div>
<?php endif; ?>

<form action="<?= url('/forgot-password') ?>" method="POST" novalidate>
    <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">

    <div class="form-group">
        <label for="email" class="form-label">Email Address</label>
        <input type="email" id="email" name="email" class="form-input" required 
               autocomplete="email" autofocus>
        <?php if (!empty($errors['email'])): ?>
            <span style="font-size: 0.75rem; color: #b91c1c; margin-top: 4px; display: block;">
                <?= e($errors['email']) ?>
            </span>
        <?php endif; ?>
    </div>

    <button type="submit" class="btn-submit">Send Reset Link</button>
</form>

<div class="auth-footer">
    Back to <a href="<?= url('/login') ?>" class="auth-link">Log in</a>
</div>

<?php include ROOT_PATH . '/app/Views/layouts/auth_footer.php'; ?>
