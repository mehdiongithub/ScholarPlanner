<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied (403) | ScholarMatch</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">
    <style>
        .error-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            background: #f8fafc;
        }
        .error-card {
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius-2xl);
            padding: clamp(32px, 6vw, 48px);
            max-width: 500px;
            width: 100%;
            text-align: center;
            box-shadow: var(--shadow-lg);
        }
        .error-icon {
            width: 64px;
            height: 64px;
            background: #fef2f2;
            color: #ef4444;
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
        }
        .error-icon i {
            width: 32px;
            height: 32px;
        }
        .error-card h1 {
            font-size: 1.75rem;
            color: var(--text-900);
            margin-bottom: 16px;
            font-weight: 700;
        }
        .error-card p {
            color: var(--text-500);
            font-size: 0.9375rem;
            line-height: 1.6;
            margin: 0 auto 24px;
        }
        .btn-home {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: var(--primary);
            color: var(--bg-white);
            text-decoration: none;
            border-radius: var(--radius-lg);
            font-size: 0.9375rem;
            font-weight: 600;
            transition: background 0.2s;
        }
        .btn-home:hover {
            background: var(--primary-dark, #1d4ed8);
        }
        @media (max-width: 280px) {
            .error-card {
                padding: 20px 16px;
            }
        }
    </style>
</head>
<body>

    <div class="error-container">
        <div class="error-card">
            <div class="error-icon">
                <i data-lucide="shield-alert"></i>
            </div>
            
            <h1>403 — Access Denied</h1>
            <p>You do not have permission or role clearance to access this resource. Please make sure you are logged in with the correct account privileges.</p>
            
            <a href="/" class="btn-home">
                <i data-lucide="home"></i>
                <span>Return to Homepage</span>
            </a>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
