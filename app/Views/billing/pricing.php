<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pricing Plans | ScholarMatch</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">
    <style>
        .pricing-layout {
            min-height: 100vh;
            background: #f8fafc;
            display: flex;
            flex-direction: column;
        }
        .main-header {
            background: var(--bg-white);
            border-bottom: 1px solid var(--border);
            padding: 16px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .logo-box {
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            color: var(--text-900);
            font-weight: 700;
        }
        .logo-box i {
            color: var(--primary);
        }
        .nav-links {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .nav-link {
            font-size: 0.875rem;
            color: var(--text-600);
            text-decoration: none;
            font-weight: 500;
        }
        .nav-link:hover {
            color: var(--primary);
        }
        .pricing-container {
            max-width: 1000px;
            width: 100%;
            margin: 60px auto;
            padding: 0 20px;
            flex-grow: 1;
        }
        .pricing-header {
            text-align: center;
            margin-bottom: 50px;
        }
        .pricing-header h1 {
            font-size: 2.25rem;
            color: var(--text-900);
            font-weight: 800;
            margin-bottom: 12px;
        }
        .pricing-header p {
            color: var(--text-500);
            font-size: 1.1rem;
        }
        .pricing-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            margin-bottom: 60px;
        }
        .pricing-card {
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius-2xl);
            padding: 40px 32px;
            display: flex;
            flex-direction: column;
            position: relative;
            box-shadow: var(--shadow-sm);
        }
        .pricing-card.premium {
            border: 2px solid var(--primary);
            box-shadow: var(--shadow-md);
        }
        .pricing-card.premium .badge-popular {
            position: absolute;
            top: 20px;
            right: 20px;
            background: #eff6ff;
            color: var(--primary);
            font-size: 0.75rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 9999px;
            text-transform: uppercase;
        }
        .plan-name {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-900);
            margin-bottom: 8px;
        }
        .plan-desc {
            font-size: 0.875rem;
            color: var(--text-500);
            margin-bottom: 24px;
            min-height: 40px;
        }
        .plan-price {
            display: flex;
            align-items: baseline;
            margin-bottom: 32px;
        }
        .price-num {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--text-900);
        }
        .price-curr {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-500);
            margin-right: 4px;
        }
        .price-interval {
            font-size: 0.875rem;
            color: var(--text-500);
            margin-left: 6px;
        }
        .plan-features {
            list-style: none;
            padding: 0;
            margin: 0 0 40px 0;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .feature-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            font-size: 0.9375rem;
            color: var(--text-700);
        }
        .feature-item i {
            margin-top: 2px;
            flex-shrink: 0;
        }
        .feature-item.enabled i {
            color: #10b981;
        }
        .feature-item.disabled i {
            color: var(--text-300);
        }
        .feature-item.disabled span {
            color: var(--text-400);
            text-decoration: line-through;
        }
        .pricing-alert {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            border-radius: var(--radius-lg);
            padding: 16px;
            margin-bottom: 30px;
            font-size: 0.9375rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }
    </style>
</head>
<body>
    <div class="pricing-layout">
        <header class="main-header" role="banner">
            <a href="/" class="logo-box">
                <i data-lucide="graduation-cap"></i>
                <span>ScholarMatch</span>
            </a>
            <div class="nav-links">
                <a href="<?= url('/scholarships') ?>" class="nav-link">Search Scholarships</a>
                <a href="<?= url('/dashboard') ?>" class="nav-link">Dashboard</a>
            </div>
        </header>

        <main class="pricing-container">
            <div class="pricing-header">
                <h1>Simple, Transparent Pricing</h1>
                <p>Choose the plan that fits your academic journey and scholarship applications.</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="pricing-alert">
                    <i data-lucide="alert-circle"></i>
                    <span><?= e($error) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($_SESSION['billing_error'])): ?>
                <div class="pricing-alert">
                    <i data-lucide="alert-circle"></i>
                    <span><?= e($_SESSION['billing_error']); unset($_SESSION['billing_error']); ?></span>
                </div>
            <?php endif; ?>

            <div class="pricing-cards">
                <!-- Free Plan Card -->
                <div class="pricing-card">
                    <h2 class="plan-name">Free Guest</h2>
                    <p class="plan-desc">Discover and match basic scholarships with essential tools.</p>
                    <div class="plan-price">
                        <span class="price-curr">PKR</span>
                        <span class="price-num">0</span>
                        <span class="price-interval">/ month</span>
                    </div>
                    <ul class="plan-features">
                        <li class="feature-item enabled">
                            <i data-lucide="check" style="width:18px;height:18px"></i>
                            <span>Public scholarship discovery & basic search</span>
                        </li>
                        <li class="feature-item enabled">
                            <i data-lucide="check" style="width:18px;height:18px"></i>
                            <span>Basic matching (Up to 5 recommendations)</span>
                        </li>
                        <li class="feature-item enabled">
                            <i data-lucide="check" style="width:18px;height:18px"></i>
                            <span>Limited Saved Scholarships (Up to 10)</span>
                        </li>
                        <li class="feature-item enabled">
                            <i data-lucide="check" style="width:18px;height:18px"></i>
                            <span>Limited side-by-side comparison (Up to 3)</span>
                        </li>
                        <li class="feature-item disabled">
                            <i data-lucide="x" style="width:18px;height:18px"></i>
                            <span>Personalized matching (Unlimited recommendations)</span>
                        </li>
                        <li class="feature-item disabled">
                            <i data-lucide="x" style="width:18px;height:18px"></i>
                            <span>Application workflow tracker</span>
                        </li>
                        <li class="feature-item disabled">
                            <i data-lucide="x" style="width:18px;height:18px"></i>
                            <span>Document readiness insights panel</span>
                        </li>
                        <li class="feature-item disabled">
                            <i data-lucide="x" style="width:18px;height:18px"></i>
                            <span>Email & WhatsApp deadline alerts</span>
                        </li>
                    </ul>

                    <?php if ($current_plan === 'free'): ?>
                        <button class="btn btn-secondary" style="width:100%; justify-content:center;" disabled>
                            <span>Your Current Plan</span>
                        </button>
                    <?php else: ?>
                        <a href="<?= url('/billing') ?>" class="btn btn-secondary" style="width:100%; justify-content:center;">
                            <span>Manage Plan</span>
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Premium Plan Card -->
                <div class="pricing-card premium">
                    <span class="badge-popular">Popular</span>
                    <h2 class="plan-name">Premium Monthly</h2>
                    <p class="plan-desc">Unlock advanced filters, full recommendation alerts, and tracking.</p>
                    <div class="plan-price">
                        <span class="price-curr">PKR</span>
                        <span class="price-num">1,499</span>
                        <span class="price-interval">/ month</span>
                    </div>
                    <ul class="plan-features">
                        <li class="feature-item enabled">
                            <i data-lucide="check" style="width:18px;height:18px"></i>
                            <span>Public scholarship discovery & search</span>
                        </li>
                        <li class="feature-item enabled">
                            <i data-lucide="check" style="width:18px;height:18px"></i>
                            <span>Personalized matching (All eligible recommendations)</span>
                        </li>
                        <li class="feature-item enabled">
                            <i data-lucide="check" style="width:18px;height:18px"></i>
                            <span>Advanced search filtering & sorting controls</span>
                        </li>
                        <li class="feature-item enabled">
                            <i data-lucide="check" style="width:18px;height:18px"></i>
                            <span>Unlimited saved scholarships</span>
                        </li>
                        <li class="feature-item enabled">
                            <i data-lucide="check" style="width:18px;height:18px"></i>
                            <span>Full side-by-side comparison (Up to 4)</span>
                        </li>
                        <li class="feature-item enabled">
                            <i data-lucide="check" style="width:18px;height:18px"></i>
                            <span>Application workflow tracking</span>
                        </li>
                        <li class="feature-item enabled">
                            <i data-lucide="check" style="width:18px;height:18px"></i>
                            <span>Document readiness diagnostics</span>
                        </li>
                        <li class="feature-item enabled">
                            <i data-lucide="check" style="width:18px;height:18px"></i>
                            <span>Email & WhatsApp deadline reminders</span>
                        </li>
                    </ul>

                    <?php if ($current_plan === 'premium-monthly'): ?>
                        <button class="btn btn-secondary" style="width:100%; justify-content:center; border-color:#10b981; color:#10b981; cursor:default;" disabled>
                            <i data-lucide="check-circle" style="width:16px;height:16px;color:#10b981;"></i>
                            <span>Active Subscription</span>
                        </button>
                    <?php else: ?>
                        <a href="<?= url('/checkout?plan=premium-monthly') ?>" class="btn btn-primary" style="width:100%; justify-content:center;">
                            <span>Upgrade to Premium</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
