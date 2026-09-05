<?php include ROOT_PATH . '/app/Views/layouts/referral_partner_header.php'; ?>

<style>
    .grid-metrics {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 16px;
        margin-bottom: 32px;
    }
    .metric-card {
        background: white;
        border: 1px solid var(--border-slate-200);
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .metric-title {
        font-size: 0.75rem;
        text-transform: uppercase;
        font-weight: 600;
        color: #64748b;
        letter-spacing: 0.05em;
    }
    .metric-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #0f172a;
    }
    .card {
        background: white;
        border: 1px solid var(--border-slate-200);
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        margin-bottom: 24px;
    }
    .card-title {
        font-size: 1.125rem;
        font-weight: 700;
        margin-top: 0;
        margin-bottom: 16px;
    }
    .referral-link-box {
        background-color: var(--bg-slate-100);
        border: 1px solid var(--border-slate-200);
        border-radius: 8px;
        padding: 12px;
        font-family: monospace;
        font-size: 0.875rem;
        word-break: break-all;
        margin-bottom: 16px;
        color: #334155;
    }
    .data-table {
        width: 100%;
        border-collapse: collapse;
    }
    .data-table th, .data-table td {
        padding: 12px 14px;
        text-align: left;
        border-bottom: 1px solid var(--border-slate-200);
        font-size: 0.875rem;
    }
    .data-table th {
        background-color: var(--bg-slate-50);
        font-weight: 600;
        color: #475569;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .badge-earned {
        background: #d1fae5;
        color: #065f46;
        padding: 3px 8px;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
    }
</style>

<div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
    <div>
        <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0; color: #1e293b;">Partner Program Overview</h1>
        <p style="margin: 4px 0 0 0; color: #64748b; font-size: 0.875rem;">Authoritative tracking of your referred registrations, conversions, and commissions.</p>
    </div>
    <div>
        <form method="GET" action="<?= url('/referral-partner') ?>" style="display: flex; gap: 8px; align-items: center;">
            <label for="month" style="font-size: 0.875rem; color: #64748b; font-weight: 500;">Month:</label>
            <input type="month" name="month" id="month" value="<?= e($selected_month) ?>" class="form-input" style="padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 6px;" onchange="this.form.submit()">
        </form>
    </div>
</div>

<!-- 6 Authoritative Metrics Grid -->
<div class="grid-metrics">
    <div class="metric-card">
        <span class="metric-title">Total Referred Users</span>
        <span class="metric-value"><?= e($total_signups) ?></span>
    </div>
    <div class="metric-card">
        <span class="metric-title">Total Paid Users</span>
        <span class="metric-value"><?= e($total_paid_users) ?></span>
    </div>
    <div class="metric-card">
        <span class="metric-title">Current Month Paid Users</span>
        <span class="metric-value"><?= e($current_month_paid_users) ?></span>
    </div>
    <div class="metric-card">
        <span class="metric-title">Current Month Payments</span>
        <span class="metric-value"><?= e($current_month_payments) ?></span>
    </div>
    <div class="metric-card">
        <span class="metric-title">Current Month Commission</span>
        <span class="metric-value" style="color: #10b981;">Rs <?= e($current_month_commission) ?></span>
    </div>
    <div class="metric-card">
        <span class="metric-title">Total Earned Commission</span>
        <span class="metric-value" style="color: #2563eb;">Rs <?= e($total_earned_commission) ?></span>
    </div>
</div>

<!-- Referral Link Sharing Card -->
<div class="card" id="sharing-link">
    <h2 class="card-title">Your Unique Referral Link</h2>
    <p style="font-size: 0.875rem; color: #475569; margin-bottom: 16px; line-height: 1.5;">
        Students registering with your link or referral code <strong><?= e($partner['referral_code']) ?></strong> receive a first-payment discount. You earn commission on all qualifying payments during their 6-month attribution window.
    </p>
    
    <label style="display:block; font-size:0.75rem; text-transform:uppercase; font-weight:600; color:#64748b; margin-bottom:6px;">Registration URL</label>
    <div class="referral-link-box" id="refLink">
        <?= e(url("/register")) ?>?ref=<?= e($partner['referral_code']) ?>
    </div>
    
    <button onclick="copyLink()" class="btn btn-primary" style="justify-content: center; display: inline-flex; align-items: center; gap: 8px;">
        <i data-lucide="copy" style="width:16px; height:16px;"></i>
        <span>Copy Referral Link</span>
    </button>
</div>

<!-- Current Month Payments Breakdown -->
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <h2 class="card-title" style="margin-bottom: 0;">Recent Paid Conversions (<?= e($selected_month) ?>)</h2>
        <a href="<?= url('/referral-partner/students?month=' . urlencode($selected_month)) ?>" style="font-size: 0.875rem; color: #2563eb; text-decoration: none; font-weight: 500;">View All &rarr;</a>
    </div>

    <?php if (empty($monthly_records)): ?>
        <p style="color: #64748b; font-size: 0.875rem; margin: 16px 0;">No successful payments recorded for referred students in <?= e($selected_month) ?>.</p>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Student</th>
                        <th>Plan</th>
                        <th>Original</th>
                        <th>Discount</th>
                        <th>Paid</th>
                        <th>Commission</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($monthly_records as $rec): ?>
                        <tr>
                            <td><?= e(date('M d, Y H:i', strtotime($rec['payment_date']))) ?></td>
                            <td><strong><?= e($rec['display_name']) ?></strong></td>
                            <td><?= e($rec['plan_name']) ?></td>
                            <td>Rs <?= e($rec['original_plan_amount']) ?></td>
                            <td><?= e($rec['referral_discount_percentage']) ?>% (Rs <?= e($rec['referral_discount_amount']) ?>)</td>
                            <td><strong>Rs <?= e($rec['actual_paid_amount']) ?></strong></td>
                            <td style="color: #10b981; font-weight: 600;">Rs <?= e($rec['commission_amount']) ?></td>
                            <td><span class="badge-earned"><?= e(ucfirst($rec['status'])) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
    function copyLink() {
        var text = document.getElementById('refLink').innerText.trim();
        navigator.clipboard.writeText(text).then(function() {
            alert('Referral link copied to clipboard!');
        }, function() {
            alert('Failed to copy. Your link is: ' + text);
        });
    }
</script>

<?php include ROOT_PATH . '/app/Views/layouts/referral_partner_footer.php'; ?>
