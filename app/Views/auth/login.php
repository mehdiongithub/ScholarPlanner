<?php
$title = 'Welcome back';
$subtitle = 'Sign in to search matched scholarships';
include ROOT_PATH . '/app/Views/layouts/auth_header.php';
?>

<?php if (!empty($success_message)): ?>
    <div class="alert alert-success" role="alert">
        <?= e($success_message) ?>
    </div>
<?php endif; ?>

<?php if (!empty($errors['auth'])): ?>
    <div class="alert alert-danger" role="alert">
        <?= e($errors['auth']) ?>
    </div>
<?php endif; ?>

<?php if (!empty($errors['csrf'])): ?>
    <div class="alert alert-danger" role="alert">
        <?= e($errors['csrf']) ?>
    </div>
<?php endif; ?>

<form action="<?= url('/login') ?>" method="POST" novalidate>
    <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">

    <div class="form-group">
        <label for="email" class="form-label">Email Address</label>
        <input type="email" id="email" name="email" class="form-input" required 
               value="<?= e($old['email'] ?? '') ?>" autocomplete="email" autofocus>
        <?php if (!empty($errors['email'])): ?>
            <span style="font-size: 0.75rem; color: #b91c1c; margin-top: 4px; display: block;">
                <?= e($errors['email']) ?>
            </span>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label for="password" class="form-label">Password</label>
        <input type="password" id="password" name="password" class="form-input" required 
               autocomplete="current-password">
        <?php if (!empty($errors['password'])): ?>
            <span style="font-size: 0.75rem; color: #b91c1c; margin-top: 4px; display: block;">
                <?= e($errors['password']) ?>
            </span>
        <?php endif; ?>
    </div>

    <div class="form-row">
        <label class="checkbox-label">
            <input type="checkbox" name="remember_me" class="checkbox-input">
            <span>Remember me</span>
        </label>
        <a href="<?= url('/forgot-password') ?>" class="auth-link">Forgot password?</a>
    </div>

    <button type="submit" class="btn-submit">Sign In</button>
</form>

<div class="auth-footer">
    Don't have an account? <a href="<?= url('/register') ?>" class="auth-link">Sign up</a>
</div>

<?php include ROOT_PATH . '/app/Views/layouts/auth_footer.php'; ?>
