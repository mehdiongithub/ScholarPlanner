<?php
$title = 'Create your account';
$subtitle = 'Get personalized scholarship matches and alerts';
include ROOT_PATH . '/app/Views/layouts/auth_header.php';
?>

<style>
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        margin-bottom: 16px;
    }
    @media (max-width: 480px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

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

<form action="<?= url('/register') ?>" method="POST" novalidate>
    <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">

    <div class="form-grid">
        <div class="form-group">
            <label for="first_name" class="form-label">First Name</label>
            <input type="text" id="first_name" name="first_name" class="form-input" required 
                   value="<?= e($old['first_name'] ?? '') ?>" autocomplete="given-name" autofocus>
            <?php if (!empty($errors['first_name'])): ?>
                <span style="font-size: 0.75rem; color: #b91c1c; margin-top: 4px; display: block;">
                    <?= e($errors['first_name']) ?>
                </span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="last_name" class="form-label">Last Name</label>
            <input type="text" id="last_name" name="last_name" class="form-input" required 
                   value="<?= e($old['last_name'] ?? '') ?>" autocomplete="family-name">
            <?php if (!empty($errors['last_name'])): ?>
                <span style="font-size: 0.75rem; color: #b91c1c; margin-top: 4px; display: block;">
                    <?= e($errors['last_name']) ?>
                </span>
            <?php endif; ?>
        </div>
    </div>

    <div class="form-group">
        <label for="email" class="form-label">Email Address</label>
        <input type="email" id="email" name="email" class="form-input" required 
               value="<?= e($old['email'] ?? '') ?>" autocomplete="email">
        <?php if (!empty($errors['email'])): ?>
            <span style="font-size: 0.75rem; color: #b91c1c; margin-top: 4px; display: block;">
                <?= e($errors['email']) ?>
            </span>
        <?php endif; ?>
    </div>

    <div class="form-grid">
        <div class="form-group">
            <label for="password" class="form-label">Password</label>
            <input type="password" id="password" name="password" class="form-input" required 
                   autocomplete="new-password">
            <?php if (!empty($errors['password'])): ?>
                <span style="font-size: 0.75rem; color: #b91c1c; margin-top: 4px; display: block;">
                    <?= e($errors['password']) ?>
                </span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="confirm_password" class="form-label">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" class="form-input" required 
                   autocomplete="new-password">
            <?php if (!empty($errors['confirm_password'])): ?>
                <span style="font-size: 0.75rem; color: #b91c1c; margin-top: 4px; display: block;">
                    <?= e($errors['confirm_password']) ?>
                </span>
            <?php endif; ?>
        </div>
    </div>

    <div class="form-group">
        <label for="referral_code" class="form-label">Referral Code (Optional)</label>
        <input type="text" id="referral_code" name="referral_code" class="form-input" 
               value="<?= e($old['referral_code'] ?? '') ?>" placeholder="e.g. PARTNER10">
        <?php if (!empty($errors['referral_code'])): ?>
            <span style="font-size: 0.75rem; color: #b91c1c; margin-top: 4px; display: block;">
                <?= e($errors['referral_code']) ?>
            </span>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label class="checkbox-label">
            <input type="checkbox" name="terms" class="checkbox-input" value="1"
                   <?= isset($old['terms']) ? 'checked' : '' ?>>
            <span>I agree to the Terms & Conditions and Privacy Policy.</span>
        </label>
        <?php if (!empty($errors['terms'])): ?>
            <span style="font-size: 0.75rem; color: #b91c1c; margin-top: 4px; display: block;">
                <?= e($errors['terms']) ?>
            </span>
        <?php endif; ?>
    </div>

    <button type="submit" class="btn-submit">Sign Up</button>
</form>

<div class="auth-footer">
    Already have an account? <a href="<?= url('/login') ?>" class="auth-link">Log in</a>
</div>

<?php include ROOT_PATH . '/app/Views/layouts/auth_footer.php'; ?>
