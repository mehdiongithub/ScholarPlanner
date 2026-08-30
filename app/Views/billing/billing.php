<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing & Subscription | ScholarMatch</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@0.460.0"></script>
    <link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">
    <style>
        .billing-layout {
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
        .billing-container {
            max-width: 900px;
            width: 100%;
            margin: 40px auto;
            padding: 0 20px;
            flex-grow: 1;
        }
        .billing-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 30px;
        }
        .billing-section {
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius-2xl);
            padding: 32px;
            box-shadow: var(--shadow-sm);
        }
        .billing-section h2 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-900);
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .sub-card {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 20px;
        }
        .sub-badge {
            font-size: 0.75rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 9999px;
            text-transform: uppercase;
        }
        .sub-badge.active { background: #d1fae5; color: #065f46; }
        .sub-badge.cancelled { background: #fee2e2; color: #991b1b; }
        .sub-badge.expired { background: #f3f4f6; color: #374151; }
        .sub-badge.past_due { background: #fef3c7; color: #92400e; }
        .sub-badge.failed { background: #fee2e2; color: #991b1b; }
        .sub-badge.pending { background: #eff6ff; color: #1e40af; }

        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
            text-align: left;
        }
        .invoice-table th, .invoice-table td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border);
        }
        .invoice-table th {
            background: #f8fafc;
            color: var(--text-600);
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="billing-layout">
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

        <main class="billing-container">
            <div style="margin-bottom: 32px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
                <div>
                    <h1 style="font-size: 2rem; font-weight: 800; color: var(--text-900);">Billing & Plan Management</h1>
                    <p style="color: var(--text-500);">Review your active subscription, expiration date, and billing statements history.</p>
                </div>
            </div>

            <?php if (!empty($_SESSION['billing_success'])): ?>
                <div style="background:#d1fae5; border:1px solid #a7f3d0; color:#065f46; border-radius:var(--radius-lg); padding:16px; margin-bottom:30px; font-size:0.9375rem; display:flex; align-items:center; gap:12px;">
                    <i data-lucide="check-circle"></i>
                    <span><?= e($_SESSION['billing_success']); unset($_SESSION['billing_success']); ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($_SESSION['billing_error'])): ?>
                <div style="background:#fee2e2; border:1px solid #fecaca; color:#b91c1c; border-radius:var(--radius-lg); padding:16px; margin-bottom:30px; font-size:0.9375rem; display:flex; align-items:center; gap:12px;">
                    <i data-lucide="alert-circle"></i>
                    <span><?= e($_SESSION['billing_error']); unset($_SESSION['billing_error']); ?></span>
                </div>
            <?php endif; ?>

            <div class="billing-grid">
                <!-- Active Subscription Panel -->
                <div class="billing-section">
                    <h2>
                        <i data-lucide="award" style="color:var(--primary)"></i>
                        <span>Current Subscription</span>
                    </h2>
                    
                    <div class="sub-card">
                        <div>
                            <div style="display:flex; align-items:center; gap:12px; margin-bottom:8px;">
                                <span style="font-size: 1.5rem; font-weight: 700; color: var(--text-900);"><?= e($plan['plan_name']) ?></span>
                                <span class="sub-badge <?= e($subscription['status'] ?? 'expired') ?>"><?= e($subscription['status'] ?? 'No Subscription') ?></span>
                            </div>
                            
                            <?php if (!empty($subscription['id'])): ?>
                                <p style="color:var(--text-500); font-size:0.875rem; margin:4px 0;">
                                    <strong>Billing Term:</strong> Monthly (PKR <?= number_format($plan['price'], 2) ?>)
                                </p>
                                <p style="color:var(--text-500); font-size:0.875rem; margin:4px 0;">
                                    <strong>Starts On:</strong> <?= e(date('M d, Y', strtotime($subscription['starts_at']))) ?>
                                </p>
                                <p style="color:var(--text-500); font-size:0.875rem; margin:4px 0;">
                                    <strong>Renewal/Expiry On:</strong> <?= e(date('M d, Y', strtotime($subscription['ends_at']))) ?>
                                </p>
                            <?php else: ?>
                                <p style="color:var(--text-500); font-size:0.875rem;">
                                    You are currently on the Free guest plan. Select Premium to unlock advanced features.
                                </p>
                            <?php endif; ?>
                        </div>

                        <div>
                            <?php if ($plan['plan_slug'] === 'free'): ?>
                                <a href="<?= url('/pricing') ?>" class="btn btn-primary">
                                    <i data-lucide="zap" style="width:16px; height:16px;"></i>
                                    <span>Upgrade Plan</span>
                                </a>
                            <?php elseif ($subscription['status'] === 'active'): ?>
                                <form method="POST" action="<?= url('/checkout/cancel') ?>" onsubmit="return confirm('Are you sure you want to cancel auto-renewal? You will keep premium access until the end of the paid period.');">
                                    <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                                    <button type="submit" class="btn btn-secondary" style="border-color:#ef4444; color:#ef4444;">
                                        <i data-lucide="slash" style="width:16px; height:16px;"></i>
                                        <span>Cancel Auto-Renew</span>
                                    </button>
                                </form>
                            <?php elseif ($subscription['status'] === 'cancelled'): ?>
                                <div style="font-size:0.875rem; color:#b91c1c; font-weight:600; margin-bottom:8px;">Auto-renewal has been cancelled.</div>
                                <a href="<?= url('/pricing') ?>" class="btn btn-primary">
                                    <i data-lucide="refresh-cw" style="width:16px; height:16px;"></i>
                                    <span>Resubscribe Plan</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Invoices / Transactions Panel -->
                <div class="billing-section">
                    <h2>
                        <i data-lucide="file-text" style="color:var(--primary)"></i>
                        <span>Payment Statements & Invoices</span>
                    </h2>

                    <?php if (empty($transactions)): ?>
                        <div style="text-align:center; padding:32px; color:var(--text-400);">
                            <i data-lucide="receipt" style="width:36px; height:36px; stroke-width:1.5; margin-bottom:12px;"></i>
                            <p>No billing statement history found.</p>
                        </div>
                    <?php else: ?>
                        <div style="overflow-x:auto;">
                            <table class="invoice-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Reference ID</th>
                                        <th>Provider</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transactions as $t): ?>
                                        <tr>
                                            <td><?= e(date('M d, Y', strtotime($t['created_at']))) ?></td>
                                            <td style="font-family:monospace;"><?= e($t['transaction_reference']) ?></td>
                                            <td><?= e(ucfirst($t['provider'])) ?></td>
                                            <td style="font-weight:600;"><?= e($t['currency']) ?> <?= number_format($t['amount'], 2) ?></td>
                                            <td>
                                                <span class="sub-badge <?= e($t['status'] === 'paid' || $t['status'] === 'success' ? 'active' : $t['status']) ?>">
                                                    <?= e($t['status']) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
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
