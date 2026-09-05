<?php include ROOT_PATH . '/app/Views/layouts/referral_partner_header.php'; ?>

<style>
    .card {
        background: white;
        border: 1px solid var(--border-slate-200);
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
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
    .badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .badge-active { background: #d1fae5; color: #065f46; }
    .badge-expired { background: #fee2e2; color: #991b1b; }
    .badge-earned { background: #e0e7ff; color: #3730a3; }
    
    .pagination-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 20px;
        flex-wrap: wrap;
        gap: 12px;
    }
    .pagination-links {
        display: flex;
        gap: 6px;
    }
    .page-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 32px;
        height: 32px;
        padding: 0 8px;
        border: 1px solid var(--border-slate-200);
        border-radius: 6px;
        color: #475569;
        text-decoration: none;
        font-size: 0.875rem;
        font-weight: 500;
    }
    .page-link:hover {
        background-color: var(--bg-slate-50);
        color: var(--primary);
    }
    .page-link.active {
        background-color: var(--primary);
        border-color: var(--primary);
        color: white;
    }
    .page-link.disabled {
        color: #94a3b8;
        pointer-events: none;
        background-color: #fafafa;
    }
</style>

<div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
    <div>
        <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0; color: #1e293b;">Monthly Paid Customers</h1>
        <p style="margin: 4px 0 0 0; color: #64748b; font-size: 0.875rem;">Referred users who made a successful payment in <?= e($selected_month) ?> during their 6-month attribution window.</p>
    </div>
    <div>
        <form method="GET" action="<?= url('/referral-partner/students') ?>" style="display: flex; gap: 8px; align-items: center;">
            <label for="month" style="font-size: 0.875rem; color: #64748b; font-weight: 500;">Month:</label>
            <input type="month" name="month" id="month" value="<?= e($selected_month) ?>" class="form-input" style="padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 6px;" onchange="this.form.submit()">
        </form>
    </div>
</div>

<div class="card">
    <div style="overflow-x: auto;">
        <?php if (empty($payments)): ?>
            <p style="text-align: center; color: #64748b; padding: 40px 0; margin: 0; font-size: 0.875rem;">
                No paid conversions recorded for <?= e($selected_month) ?>.
            </p>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Referred Customer</th>
                        <th>Plan</th>
                        <th>Original Price</th>
                        <th>Referral Discount</th>
                        <th>Actual Paid</th>
                        <th>Commission</th>
                        <th>Status</th>
                        <th>Attribution Window</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $p): ?>
                        <tr>
                            <td><?= e(date('M d, Y H:i', strtotime($p['payment_date']))) ?></td>
                            <td><strong style="color: #0f172a;"><?= e($p['display_name']) ?></strong></td>
                            <td><?= e($p['plan_name']) ?></td>
                            <td>Rs <?= e($p['original_plan_amount']) ?></td>
                            <td><?= e($p['referral_discount_percentage']) ?>% (Rs <?= e($p['referral_discount_amount']) ?>)</td>
                            <td><strong>Rs <?= e($p['actual_paid_amount']) ?></strong></td>
                            <td style="color: #10b981; font-weight: 600;">Rs <?= e($p['commission_amount']) ?></td>
                            <td><span class="badge badge-earned"><?= e(ucfirst($p['status'])) ?></span></td>
                            <td>
                                <?php if ($p['is_attribution_active']): ?>
                                    <span class="badge badge-active">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-expired">Expired</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($total_pages > 1): ?>
                <div class="pagination-bar">
                    <span style="font-size: 0.875rem; color: #64748b;">
                        Showing <?= (($current_page - 1) * $per_page) + 1 ?> to <?= min($current_page * $per_page, $total_items) ?> of <?= $total_items ?> entries
                    </span>
                    <div class="pagination-links">
                        <a href="?month=<?= urlencode($selected_month) ?>&page=<?= max(1, $current_page - 1) ?>" class="page-link <?= $current_page <= 1 ? 'disabled' : '' ?>">Prev</a>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?month=<?= urlencode($selected_month) ?>&page=<?= $i ?>" class="page-link <?= $current_page === $i ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                        <a href="?month=<?= urlencode($selected_month) ?>&page=<?= min($total_pages, $current_page + 1) ?>" class="page-link <?= $current_page >= $total_pages ? 'disabled' : '' ?>">Next</a>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php include ROOT_PATH . '/app/Views/layouts/referral_partner_footer.php'; ?>
