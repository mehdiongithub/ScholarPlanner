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
</div>

<form id="forgot-form" action="<?= url('/forgot-password') ?>" method="POST" novalidate>
    <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">

    <div class="form-group">
        <label for="email" class="form-label">Email Address</label>
        <input type="email" id="email" name="email" class="form-input <?= !empty($errors['email']) ? 'is-invalid' : '' ?>" required 
               value="<?= e($old['email'] ?? '') ?>" autocomplete="email" autofocus>
        <span class="form-error" id="error-email"><?= e($errors['email'] ?? '') ?></span>
    </div>

    <button type="submit" class="btn-submit">Send Reset Link</button>
</form>

<div class="auth-footer">
    Back to <a href="<?= url('/login') ?>" class="auth-link">Log in</a>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('forgot-form');
    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            // Clear previous errors & messages
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

            const submitBtn = form.querySelector('button[type="submit"]');
            const originalBtnHtml = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner"></span> Sending...';

            try {
                const formData = new FormData(form);
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const data = await response.json().catch(function() { return null; });

                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnHtml;

                if (response.ok && data && data.success) {
                    if (alertContainer) {
                        alertContainer.innerHTML = '<div class="alert alert-success" role="alert">' + data.message + '</div>';
                        if (data.dev_reset_link) {
                            alertContainer.innerHTML += '<div class="dev-box"><strong>[LOCAL DEV ONLY] Reset Link:</strong><br><a href="' + data.dev_reset_link + '" style="color: #b45309; text-decoration: underline; font-weight: 500;">' + data.dev_reset_link + '</a></div>';
                        }
                    }
                    return;
                }

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
                        if (field === 'csrf' && alertContainer) {
                            alertContainer.innerHTML = '<div class="alert alert-danger" role="alert">' + message + '</div>';
                        }
                    }
                } else {
                    if (alertContainer) {
                        alertContainer.innerHTML = '<div class="alert alert-danger" role="alert">An unexpected error occurred. Please try again.</div>';
                    }
                }
            } catch (err) {
                console.error('Forgot password submit error:', err);
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
