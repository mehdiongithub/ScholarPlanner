<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Checkout | ScholarPlanner</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/webp" href="<?= asset('assets/images/logo.webp') ?>">
    <link rel="apple-touch-icon" href="<?= asset('assets/images/logo.webp') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@0.460.0"></script>
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
            gap: 14px;
            font-weight: 600;
            color: var(--text-800);
            cursor: pointer;
            width: 100%;
        }
        .method-card:has(input[type="radio"]:checked) {
            border-color: var(--primary);
            background: #f0fdf4;
            box-shadow: 0 0 0 1px var(--primary);
        }
        .payment-method-logo {
            height: 30px;
            width: auto;
            max-width: 120px;
            object-fit: contain;
            flex-shrink: 0;
        }
        .payment-method-details {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .payment-method-title {
            font-size: 0.9375rem;
            font-weight: 600;
            color: var(--text-900);
        }
        .payment-method-desc {
            font-size: 0.75rem;
            color: var(--text-500);
            font-weight: 400;
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
                <img src="<?= asset('assets/images/logo.webp') ?>" alt="ScholarPlanner Logo" class="logo-box-img">
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
                        <!-- JazzCash Option (via CashMaal) -->
                        <label class="method-card">
                            <input type="radio" name="payment_provider" value="jazzcash" checked>
                            <div class="method-label">
                                <img src="<?= asset('assets/images/jazzcash.svg') ?>" alt="JazzCash" class="payment-method-logo">
                                <div class="payment-method-details">
                                    <span class="payment-method-title">JazzCash Sandbox Wallet</span>
                                    <span class="payment-method-desc">Pay instantly with your JazzCash mobile account</span>
                                </div>
                            </div>
                        </label>

                        <!-- Easypaisa Option (via CashMaal) -->
                        <label class="method-card">
                            <input type="radio" name="payment_provider" value="easypaisa">
                            <div class="method-label">
                                <img src="<?= asset('assets/images/easypaisa.svg') ?>" alt="Easypaisa" class="payment-method-logo">
                                <div class="payment-method-details">
                                    <span class="payment-method-title">Easypaisa Sandbox Wallet</span>
                                    <span class="payment-method-desc">Pay instantly with your Easypaisa mobile account</span>
                                </div>
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
