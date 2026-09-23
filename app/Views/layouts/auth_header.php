<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Sign In') ?> | ScholarPlanner</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/webp" href="<?= asset('assets/images/logo.webp') ?>">
    <link rel="apple-touch-icon" href="<?= asset('assets/images/logo.webp') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script defer src="<?= asset('assets/js/lucide.min.js') ?>"></script>
    <link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/admin.css') ?>">
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
        .auth-logo-img {
            height: 48px;
            width: auto;
            max-width: 220px;
            object-fit: contain;
            display: block;
            margin: 0 auto;
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
        }
        
        /* Auth Form Elements */
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
        .form-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 12px;
        }
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.875rem;
            color: var(--text-600);
            cursor: pointer;
        }
        .checkbox-input {
            width: 16px;
            height: 16px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
            cursor: pointer;
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
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
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
            .form-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
        }
    </style>
</head>
<body>

<div class="auth-wrapper">
    <div class="auth-card">
        <a href="<?= url('/') ?>" class="auth-logo" aria-label="ScholarPlanner Home">
            <img src="<?= asset('assets/images/logo.webp') ?>" alt="ScholarPlanner Logo" class="auth-logo-img">
        </a>
        
        <h1 class="auth-title"><?= e($title ?? 'Welcome Back') ?></h1>
        <?php if (!empty($subtitle)): ?>
            <p class="auth-subtitle"><?= e($subtitle) ?></p>
        <?php endif; ?>
