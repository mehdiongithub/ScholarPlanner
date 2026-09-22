<?php
$title = 'Set new password';
$subtitle = 'Please create a strong password containing mixed casing and numbers';
include ROOT_PATH . '/app/Views/layouts/auth_header.php';
?>

<style>
    .password-input-wrapper {
        position: relative;
        display: flex;
        align-items: center;
        width: 100%;
    }
    .password-input-wrapper .form-input {
        padding-right: 42px;
    }
    .password-toggle-btn {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        background: transparent;
        border: none;
        padding: 4px;
        cursor: pointer;
        color: #64748b;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 4px;
        transition: color 0.2s, background-color 0.2s;
    }
    .password-toggle-btn:hover {
        color: #0f172a;
        background-color: #f1f5f9;
    }
    .password-toggle-btn:focus {
        outline: 2px solid var(--primary, #2563eb);
    }
    .form-error {
        font-size: 0.75rem;
        color: #b91c1c;
        margin-top: 4px;
        display: block;
    }
    .form-error:empty {
        display: none;
    }
    .form-input.is-invalid {
        border-color: #ef4444;
        background-color: #fffbfb;
    }
    .spinner {
        display: inline-block;
        width: 16px;
        height: 16px;
        border: 2px solid #ffffff;
        border-top-color: transparent;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
        vertical-align: middle;
        margin-right: 6px;
    }
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>

<div id="alert-container">
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
</div>

<form id="reset-form" action="<?= url('/reset-password') ?>" method="POST" novalidate>
    <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
    <input type="hidden" name="token" value="<?= e($token) ?>">

    <div class="form-group">
        <label for="password" class="form-label">New Password</label>
        <div class="password-input-wrapper">
            <input type="password" id="password" name="password" class="form-input <?= !empty($errors['password']) ? 'is-invalid' : '' ?>" required 
                   autocomplete="new-password" autofocus>
            <button type="button" class="password-toggle-btn" data-target="password" aria-label="Toggle new password visibility">
                <i data-lucide="eye" style="width: 18px; height: 18px;"></i>
            </button>
        </div>
        <span class="form-error" id="error-password"><?= e($errors['password'] ?? '') ?></span>
    </div>

    <div class="form-group">
        <label for="confirm_password" class="form-label">Confirm New Password</label>
        <div class="password-input-wrapper">
            <input type="password" id="confirm_password" name="confirm_password" class="form-input <?= !empty($errors['confirm_password']) ? 'is-invalid' : '' ?>" required 
                   autocomplete="new-password">
            <button type="button" class="password-toggle-btn" data-target="confirm_password" aria-label="Toggle confirm password visibility">
                <i data-lucide="eye" style="width: 18px; height: 18px;"></i>
            </button>
        </div>
        <span class="form-error" id="error-confirm_password"><?= e($errors['confirm_password'] ?? '') ?></span>
    </div>

    <button type="submit" class="btn-submit">Update Password</button>
</form>

<div class="auth-footer">
    Back to <a href="<?= url('/login') ?>" class="auth-link">Log in</a>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password visibility toggle
    const eyeSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-eye"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>';
    const eyeOffSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-eye-off"><path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49"/><path d="M14.084 14.158a3 3 0 0 1-4.242-4.242"/><path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143"/><line x1="2" x2="22" y1="2" y2="22"/></svg>';

    document.querySelectorAll('.password-toggle-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            if (!input) return;

            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            this.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
            this.innerHTML = isPassword ? eyeOffSvg : eyeSvg;
        });
    });

    // AJAX Form Submission
    const resetForm = document.getElementById('reset-form');
    if (resetForm) {
        resetForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            // Clear previous errors & highlights
            document.querySelectorAll('.form-error').forEach(function(el) {
                el.textContent = '';
            });
            document.querySelectorAll('.form-input').forEach(function(inp) {
                inp.classList.remove('is-invalid');
            });
            const alertContainer = document.getElementById('alert-container');
            if (alertContainer) {
                alertContainer.innerHTML = '';
            }

            const submitBtn = resetForm.querySelector('button[type="submit"]');
            const originalBtnHtml = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner"></span> Updating Password...';

            try {
                const formData = new FormData(resetForm);
                const response = await fetch(resetForm.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const data = await response.json().catch(function() { return null; });

                if (response.ok && data && data.success) {
                    if (alertContainer) {
                        alertContainer.innerHTML = '<div class="alert alert-success" role="alert">' + data.message + '</div>';
                    }
                    setTimeout(function() {
                        window.location.href = data.redirect || '<?= url("/login") ?>';
                    }, 1200);
                    return;
                }

                // Restore submit button
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnHtml;

                if (data && data.errors) {
                    for (const [field, message] of Object.entries(data.errors)) {
                        const errorSpan = document.getElementById('error-' + field);
                        const fieldInput = document.getElementById(field);
                        if (errorSpan) {
                            errorSpan.textContent = message;
                        }
                        if (fieldInput) {
                            fieldInput.classList.add('is-invalid');
                        }
                        if ((field === 'token' || field === 'system' || field === 'csrf') && alertContainer) {
                            alertContainer.innerHTML += '<div class="alert alert-danger" role="alert">' + message + '</div>';
                        }
                    }
                } else {
                    if (alertContainer) {
                        alertContainer.innerHTML = '<div class="alert alert-danger" role="alert">An unexpected error occurred. Please try again.</div>';
                    }
                }
            } catch (err) {
                console.error('Reset submit error:', err);
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnHtml;
                if (alertContainer) {
                    alertContainer.innerHTML = '<div class="alert alert-danger" role="alert">Network error. Please check your connection and try again.</div>';
                }
            }
        });
    }
});
</script>

<?php include ROOT_PATH . '/app/Views/layouts/auth_footer.php'; ?>
