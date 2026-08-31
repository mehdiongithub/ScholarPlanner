<?php
$title = 'Set new password';
$subtitle = 'Please create a strong password containing mixed casing and numbers';
include ROOT_PATH . '/app/Views/layouts/auth_header.php';
?>

<?php if (!empty($errors['token'])): ?>
    <div class="alert alert-danger" role="alert">
        <?= e($errors['token']) ?>
    </div>
<?php endif; ?>

<?php if (!empty($errors['system'])): ?>
    <div class="alert alert-danger" role="alert">
        <?= e($errors['system']) ?>
    </div>
<?php endif; ?>

<?php if (!empty($errors['csrf'])): ?>
    <div class="alert alert-danger" role="alert">
        <?= e($errors['csrf']) ?>
    </div>
<?php endif; ?>

<form action="<?= url('/reset-password') ?>" method="POST" novalidate>
    <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
    <input type="hidden" name="token" value="<?= e($token) ?>">

    <div class="form-group">
        <label for="password" class="form-label">New Password</label>
        <input type="password" id="password" name="password" class="form-input" required 
               autocomplete="new-password" autofocus>
        <?php if (!empty($errors['password'])): ?>
            <span style="font-size: 0.75rem; color: #b91c1c; margin-top: 4px; display: block;">
                <?= e($errors['password']) ?>
            </span>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label for="confirm_password" class="form-label">Confirm New Password</label>
        <input type="password" id="confirm_password" name="confirm_password" class="form-input" required 
               autocomplete="new-password">
        <?php if (!empty($errors['confirm_password'])): ?>
            <span style="font-size: 0.75rem; color: #b91c1c; margin-top: 4px; display: block;">
                <?= e($errors['confirm_password']) ?>
            </span>
        <?php endif; ?>
    </div>

    <button type="submit" class="btn-submit">Update Password</button>
</form>

<?php include ROOT_PATH . '/app/Views/layouts/auth_footer.php'; ?>
