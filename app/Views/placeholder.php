<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> | ScholarMatch</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@0.460.0"></script>
    <link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">
    <style>
        .placeholder-container {
            min-height: 80vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }
        .placeholder-card {
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius-2xl);
            padding: clamp(32px, 6vw, 48px);
            max-width: 560px;
            width: 100%;
            text-align: center;
            box-shadow: var(--shadow-lg);
        }
        .placeholder-icon {
            width: 64px;
            height: 64px;
            background: var(--primary-50);
            color: var(--primary);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
        }
        .placeholder-icon i {
            width: 32px;
            height: 32px;
        }
        .placeholder-card h1 {
            font-size: 1.75rem;
            color: var(--text-900);
            margin-bottom: 16px;
        }
        .placeholder-card p {
            color: var(--text-500);
            font-size: 0.9375rem;
            line-height: 1.6;
            margin: 0 auto 24px;
        }
        .placeholder-badge {
            display: inline-block;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--accent);
            background: var(--accent-50);
            padding: 6px 14px;
            border-radius: 100px;
            margin-bottom: 24px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .placeholder-actions {
            display: flex;
            justify-content: center;
            gap: 12px;
        }
    </style>
</head>
<body>
    <header class="header scrolled" id="header" role="banner" style="position: relative;">
        <div class="header-inner">
            <a href="<?= url('/') ?>" class="logo" aria-label="ScholarMatch Home">
                <div class="logo-icon">
                    <i data-lucide="graduation-cap"></i>
                </div>
                <span class="logo-text">ScholarMatch</span>
            </a>
            <div class="header-actions" style="display: flex;">
                <a href="<?= url('/') ?>" class="btn btn-secondary btn-sm">Back to Home</a>
            </div>
        </div>
    </header>

    <main class="placeholder-container">
        <div class="placeholder-card">
            <div class="placeholder-icon">
                <i data-lucide="cog"></i>
            </div>
            <h1><?= e($title) ?></h1>
            <span class="placeholder-badge"><?= e($step) ?></span>
            <p><?= e($message) ?></p>
            <div class="placeholder-actions">
                <a href="<?= url('/') ?>" class="btn btn-primary">
                    <i data-lucide="arrow-left" style="width:16px;height:16px"></i>
                    Back to Homepage
                </a>
            </div>
        </div>
    </main>

    <footer class="footer" role="contentinfo" style="padding: 24px 0;">
        <div class="container" style="text-align: center; font-size: 0.75rem; color: var(--text-500);">
            &copy; 2025 ScholarMatch. All rights reserved.
        </div>
    </footer>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
