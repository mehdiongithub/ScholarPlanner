<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | ScholarMatch</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@0.460.0"></script>
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
        .auth-link {
            font-size: 0.875rem;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }
        .auth-link:hover {
            text-decoration: underline;
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
        .alert-success {
            background: #ecfdf5;
            border: 1px solid #d1fae5;
            color: #065f46;
        }
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

            <h1 class="auth-title">Reset password</h1>
            <p class="auth-subtitle">Enter your email and we'll help you log back in</p>

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
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
