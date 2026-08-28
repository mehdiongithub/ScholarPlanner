<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Checkout | ScholarMatch</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">
    <style>
        .checkout-layout {
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
        .checkout-container {
            max-width: 600px;
            width: 100%;
            margin: 60px auto;
            padding: 0 20px;
            flex-grow: 1;
        }
        .checkout-card {
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius-2xl);
            padding: 40px;
            box-shadow: var(--shadow-sm);
        }
        .checkout-card h1 {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--text-900);
            margin-bottom: 24px;
            text-align: center;
        }
        .summary-box {
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 24px;
            margin-bottom: 32px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9375rem;
            color: var(--text-600);
            margin-bottom: 12px;
        }
        .summary-row.total {
            border-top: 1px solid var(--border);
            padding-top: 16px;
            margin-top: 16px;
            margin-bottom: 0;
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--text-900);
        }
        .method-options {
            display: flex;
            flex-direction: column;
            gap: 16px;
            margin-bottom: 32px;
        }
        .method-card {
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 18px 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            cursor: pointer;
            position: relative;
            transition: all 0.2s ease;
        }
        .method-card:hover {
            border-color: var(--primary);
            background: #fafafa;
        }
        .method-card input[type="radio"] {
            margin: 0;
            width: 18px;
            height: 18px;
            accent-color: var(--primary);
        }
        .method-label {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
            color: var(--text-800);
            cursor: pointer;
            width: 100%;
        }
        .method-label span {
            font-size: 0.9375rem;
        }
        .secure-footer {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 24px;
            color: var(--text-400);
            font-size: 0.8125rem;
        }
    </style>
</head>
<body>
    <div class="checkout-layout">
        <header class="main-header" role="banner">
            <a href="/" class="logo-box">
                <i data-lucide="graduation-cap"></i>
                <span>ScholarMatch</span>
            </a>
        </header>

        <main class="checkout-container">
            <div class="checkout-card">
                <h1>Secure Checkout</h1>
                
                <form method="POST" action="<?= url('/checkout') ?>">
                    <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                    <input type="hidden" name="plan_slug" value="<?= e($plan['slug']) ?>">

                    <div class="summary-box">
                        <div style="font-size:0.75rem; font-weight:700; color:var(--text-400); text-transform:uppercase; margin-bottom:8px;">Order Summary</div>
                        <div class="summary-row">
                            <span>Plan Name</span>
                            <span style="font-weight:600; color:var(--text-800);"><?= e($plan['name']) ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Billing Interval</span>
                            <span>Monthly</span>
                        </div>
                        <div class="summary-row total">
                            <span>Amount Due</span>
                            <span><?= e($plan['currency']) ?> <?= number_format($plan['price'], 2) ?></span>
                        </div>
                    </div>

                    <div style="font-size:0.875rem; font-weight:700; color:var(--text-800); margin-bottom:16px;">Select Payment Method</div>
                    
                    <div class="method-options">
                        <!-- Mock Sandbox Gateway Option -->
                        <label class="method-card">
                            <input type="radio" name="payment_provider" value="mock" checked>
                            <div class="method-label">
                                <i data-lucide="credit-card" style="color:var(--primary); width:20px; height:20px;"></i>
                                <span>Mock Sandbox Gateway (Credit/Debit Card)</span>
                            </div>
                        </label>

                        <!-- JazzCash Gateway Option -->
                        <label class="method-card">
                            <input type="radio" name="payment_provider" value="jazzcash">
                            <div class="method-label">
                                <i data-lucide="wallet" style="color:#f59e0b; width:20px; height:20px;"></i>
                                <span>JazzCash Sandbox Wallet</span>
                            </div>
                        </label>

                        <!-- Easypaisa Gateway Option -->
                        <label class="method-card">
                            <input type="radio" name="payment_provider" value="easypaisa">
                            <div class="method-label">
                                <i data-lucide="wallet-2" style="color:#10b981; width:20px; height:20px;"></i>
                                <span>Easypaisa Sandbox Wallet</span>
                            </div>
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center; padding:14px; font-size:1rem;">
                        <i data-lucide="shield-check" style="width:18px; height:18px;"></i>
                        <span>Proceed to Pay</span>
                    </button>
                </form>

                <div class="secure-footer">
                    <i data-lucide="lock" style="width:14px; height:14px;"></i>
                    <span>SSL Encrypted Transaction Security</span>
                </div>
            </div>
        </main>
    </div>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
