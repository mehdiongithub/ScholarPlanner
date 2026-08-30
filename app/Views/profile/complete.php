<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Profile | ScholarMatch</title>
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
            padding: clamp(20px, 5vw, 40px);
            max-width: 500px;
            width: 100%;
            text-align: center;
            box-shadow: var(--shadow-xl);
        }
        .success-icon {
            width: 64px;
            height: 64px;
            background: #ecfdf5;
            color: #10b981;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
        }
        .auth-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-900);
            margin-bottom: 8px;
        }
        .auth-subtitle {
            font-size: 0.9375rem;
            color: var(--text-500);
            margin-bottom: 24px;
            line-height: 1.5;
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
            text-decoration: none;
            display: inline-block;
        }
        .btn-submit:hover {
            background: var(--primary-dark, #1d4ed8);
        }
    </style>
</head>
<body>

    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="success-icon">
                <i data-lucide="check-circle" style="width: 36px; height: 36px;"></i>
            </div>
            
            <h1 class="auth-title">Email Verified Successfully!</h1>
            <p class="auth-subtitle">
                Your email has been verified. Now let's complete your profile to find matching scholarship opportunities.
            </p>

            <a href="<?= url('/profile/edit') ?>" class="btn-submit">Continue to Profile Completion</a>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
