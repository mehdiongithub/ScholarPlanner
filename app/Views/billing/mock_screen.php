<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mock Payment Gateway Terminal | ScholarMatch</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@0.460.0"></script>
    <link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">
    <style>
        .terminal-layout {
            min-height: 100vh;
            background: #0f172a;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .terminal-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: var(--radius-2xl);
            padding: 40px;
            max-width: 480px;
            width: 100%;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
            color: #f8fafc;
        }
        .terminal-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .terminal-header h1 {
            font-size: 1.5rem;
            font-weight: 800;
            color: #f1f5f9;
            margin-bottom: 6px;
        }
        .sandbox-badge {
            background: #b45309;
            color: #fef3c7;
            font-size: 0.6875rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 9999px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .amount-display {
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: var(--radius-lg);
            padding: 20px;
            text-align: center;
            margin-bottom: 24px;
        }
        .amount-display .amount-val {
            font-size: 2rem;
            font-weight: 800;
            color: #38bdf8;
        }
        .field-group {
            margin-bottom: 20px;
        }
        .field-group label {
            display: block;
            font-size: 0.8125rem;
            font-weight: 600;
            color: #94a3b8;
            margin-bottom: 8px;
            text-transform: uppercase;
        }
        .field-group input {
            width: 100%;
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: var(--radius-md);
            padding: 12px;
            color: #f8fafc;
            font-size: 0.9375rem;
        }
        .btn-success-sim {
            background: #10b981;
            color: white;
            border: none;
            width: 100%;
            padding: 12px;
            border-radius: var(--radius-md);
            font-weight: 700;
            cursor: pointer;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-fail-sim {
            background: #ef4444;
            color: white;
            border: none;
            width: 100%;
            padding: 12px;
            border-radius: var(--radius-md);
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
    </style>
</head>
<body>
    <div class="terminal-layout">
        <div class="terminal-card">
            <div class="terminal-header">
                <div>
                    <span class="sandbox-badge">Mock Sandbox Payment Terminal</span>
                </div>
                <h1 style="margin-top: 10px;">ScholarMatch Fulfill Terminal</h1>
                <p style="color:#94a3b8; font-size:0.875rem;">Simulating external provider billing interfaces</p>
            </div>

            <div class="amount-display">
                <div style="font-size:0.75rem; font-weight:700; color:#64748b; text-transform:uppercase; margin-bottom:4px;">Billing Amount</div>
                <div class="amount-val">PKR <?= number_format($transaction['amount'], 2) ?></div>
                <div style="font-size:0.75rem; color:#64748b; font-family:monospace; margin-top:6px;">Ref: <?= e($transaction['transaction_reference']) ?></div>
            </div>

            <div class="field-group">
                <label>Cardholder Name</label>
                <input type="text" value="Jane Doe" readonly>
            </div>

            <div class="field-group">
                <label>Card Number</label>
                <input type="text" value="4111 •••• •••• 1111" readonly>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:32px;">
                <div class="field-group" style="margin-bottom:0;">
                    <label>Expiration</label>
                    <input type="text" value="12 / 29" readonly>
                </div>
                <div class="field-group" style="margin-bottom:0;">
                    <label>CVV</label>
                    <input type="text" value="123" readonly>
                </div>
            </div>

            <!-- Simulation Action Triggers -->
            <a href="<?= url('/checkout/callback?ref=' . urlencode($transaction['transaction_reference']) . '&status=success') ?>" class="btn-success-sim" style="text-decoration:none;">
                <i data-lucide="check-circle" style="width:18px; height:18px;"></i>
                <span>Simulate Payment Success</span>
            </a>

            <a href="<?= url('/checkout/callback?ref=' . urlencode($transaction['transaction_reference']) . '&status=failed') ?>" class="btn-fail-sim" style="text-decoration:none;">
                <i data-lucide="alert-triangle" style="width:18px; height:18px;"></i>
                <span>Simulate Payment Failure</span>
            </a>
        </div>
    </div>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
