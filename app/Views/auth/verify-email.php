<?php
$title = 'Verify your email';
$subtitle = 'Enter the 6-digit verification code we sent to ' . $email;
include ROOT_PATH . '/app/Views/layouts/auth_header.php';
?>

<style>
    .otp-container {
        display: flex;
        justify-content: space-between;
        gap: 8px;
        margin: 24px 0;
    }
    .otp-field {
        width: 100%;
        height: 52px;
        font-size: 1.25rem;
        font-weight: 700;
        text-align: center;
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        background: var(--bg-white);
        color: var(--text-900);
        transition: all 0.2s ease;
    }
    .otp-field:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
    }
    @media (max-width: 280px) {
        .otp-container {
            gap: 4px;
        }
        .otp-field {
            height: 40px;
            font-size: 1rem;
        }
    }
</style>

<?php if (!empty($errors['code'])): ?>
    <div class="alert alert-danger" role="alert">
        <?= e($errors['code']) ?>
    </div>
<?php endif; ?>

<?php if (!empty($errors['csrf'])): ?>
    <div class="alert alert-danger" role="alert">
        <?= e($errors['csrf']) ?>
    </div>
<?php endif; ?>

<?php if (!empty($errors['resend'])): ?>
    <div class="alert alert-danger" role="alert">
        <?= e($errors['resend']) ?>
    </div>
<?php endif; ?>

<?php if (!empty($success_message)): ?>
    <div class="alert alert-success" role="alert">
        <?= e($success_message) ?>
    </div>
<?php endif; ?>

<form action="<?= url('/verify-email') ?>" method="POST" id="otp-form" novalidate>
    <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">

    <div class="otp-container">
        <input type="text" name="code[]" maxlength="1" class="otp-field" required pattern="[0-9]" inputmode="numeric" aria-label="Digit 1" autofocus>
        <input type="text" name="code[]" maxlength="1" class="otp-field" required pattern="[0-9]" inputmode="numeric" aria-label="Digit 2">
        <input type="text" name="code[]" maxlength="1" class="otp-field" required pattern="[0-9]" inputmode="numeric" aria-label="Digit 3">
        <input type="text" name="code[]" maxlength="1" class="otp-field" required pattern="[0-9]" inputmode="numeric" aria-label="Digit 4">
        <input type="text" name="code[]" maxlength="1" class="otp-field" required pattern="[0-9]" inputmode="numeric" aria-label="Digit 5">
        <input type="text" name="code[]" maxlength="1" class="otp-field" required pattern="[0-9]" inputmode="numeric" aria-label="Digit 6">
    </div>

    <button type="submit" class="btn-submit">Verify Email</button>
</form>

<div class="auth-footer">
    Didn't receive the code?
    <form action="<?= url('/verify-email/resend') ?>" method="POST" class="resend-form" id="resend-form">
        <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
        <button type="submit" class="resend-btn" id="resend-btn">Resend code</button>
    </form>
    <span id="cooldown-timer" style="display: none; font-size: 0.875rem; color: var(--text-400);"></span>
</div>

<div class="auth-footer" style="margin-top: 16px;">
    <form action="<?= url('/logout') ?>" method="POST" class="resend-form">
        <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
        <button type="submit" class="resend-btn" style="color: var(--text-500); font-weight: normal;">Log out</button>
    </form>
</div>

<?php include ROOT_PATH . '/app/Views/layouts/auth_footer.php'; ?>

<script>
    const form = document.getElementById('otp-form');
    const inputs = Array.from(form.querySelectorAll('.otp-field'));

    inputs.forEach((input, index) => {
        // Move focus on keydown/input
        input.addEventListener('input', (e) => {
            const val = e.target.value;
            if (val.length === 1 && index < inputs.length - 1) {
                inputs[index + 1].focus();
            }
        });

        input.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && !input.value && index > 0) {
                inputs[index - 1].focus();
            }
        });

        // Handle pasting of full 6 digit code
        input.addEventListener('paste', (e) => {
            e.preventDefault();
            const pasteData = e.clipboardData.getData('text').trim();
            if (/^[0-9]{6}$/.test(pasteData)) {
                for (let i = 0; i < inputs.length; i++) {
                    inputs[i].value = pasteData[i];
                }
                inputs[inputs.length - 1].focus();
            }
        });
    });

    // Cooldown timer handler
    const resendBtn = document.getElementById('resend-btn');
    if (resendBtn) {
        resendBtn.addEventListener('click', () => {
            setTimeout(() => {
                resendBtn.disabled = true;
            }, 50);
        });
    }
</script>
