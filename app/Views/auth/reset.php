<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password | ScholarMatch</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">
    <style>
        .auth-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            background: #f8fafc;
        }
        .auth-card {
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius-2xl);
            padding: clamp(24px, 5vw, 40px);
            max-width: 440px;
            width: 100%;
            box-shadow: var(--shadow-xl);
        }
        .auth-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 24px;
            text-decoration: none;
            color: var(--text-900);
        }
        .auth-logo .logo-icon {
            width: 40px;
            height: 40px;
            background: var(--primary);
            color: var(--bg-white);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .auth-logo .logo-text {
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: -0.025em;
        }
        .auth-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-900);
            text-align: center;
            margin-bottom: 8px;
        }
        .auth-subtitle {
            font-size: 0.875rem;
            color: var(--text-500);
            text-align: center;
            margin-bottom: 24px;
            line-height: 1.5;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-700);
            margin-bottom: 6px;
        }
        .form-input {
            width: 100%;
            padding: 10px 14px;
            font-size: 0.9375rem;
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            background: var(--bg-white);
            color: var(--text-900);
            transition: all 0.2s ease;
        }
        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }
        .btn-submit {
            width: 100%;
            padding: 11px;
            background: var(--primary);
            color: var(--bg-white);
            border: none;
            border-radius: var(--radius-lg);
            font-size: 0.9375rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-submit:hover {
            background: var(--primary-dark, #1d4ed8);
        }
        .alert {
            padding: 12px 16px;
            border-radius: var(--radius-lg);
            font-size: 0.875rem;
            margin-bottom: 20px;
            line-height: 1.5;
        }
        .alert-danger {
            background: #fef2f2;
            border: 1px solid #fee2e2;
            color: #b91c1c;
        }
        .auth-footer {
            margin-top: 24px;
            text-align: center;
            font-size: 0.875rem;
            color: var(--text-500);
        }
        @media (max-width: 320px) {
            .auth-card {
                padding: 16px;
            }
        }
    </style>
</head>
<body>

    <div class="auth-wrapper">
        <div class="auth-card">
            <a href="/" class="auth-logo">
                <div class="logo-icon">
                    <i data-lucide="graduation-cap"></i>
                </div>
                <span class="logo-text">ScholarMatch</span>
            </a>

            <h1 class="auth-title">Set new password</h1>
            <p class="auth-subtitle">Please create a strong password containing mixed casing and numbers</p>

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
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
