<?php include ROOT_PATH . '/app/Views/layouts/admin_header.php'; ?>

<style>
    .data-table-card {
        background: #fff;
        border: 1px solid var(--border-slate-200);
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }
</style>

<div style="margin-bottom: 24px;">
    <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0; color: #1e293b;">Payment Transactions Ledger</h1>
    <p style="margin: 4px 0 0 0; color: #64748b; font-size: 0.875rem;">Audit system revenues, invoice status codes, and issue refunds.</p>
</div>

<div class="data-table-card">
    <div style="overflow-x: auto;">
        <table class="employees-table">
            <thead>
                <tr>
                    <th>Student Details</th>
                    <th>Invoice / Reference</th>
                    <th>Gateway Provider</th>
                    <th>Billing Amount</th>
                    <th>Payment Status</th>
                    <th>Processed At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($payments)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: #94a3b8; padding: 32px;">No payment transactions recorded in registry.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($payments as $pay): ?>
                        <tr>
                            <td>
                                <strong><?= e($pay['first_name'] . ' ' . $pay['last_name']) ?></strong>
                                <div style="font-size: 0.75rem; color: #64748b;"><?= e($pay['user_email']) ?></div>
                            </td>
                            <td><code><?= e($pay['reference_id']) ?></code></td>
                            <td><span style="text-transform: uppercase; font-size: 0.75rem; font-weight: 600; color: #64748b;"><?= e($pay['gateway_name']) ?></span></td>
                            <td><strong>$<?= number_format($pay['amount'], 2) ?></strong></td>
                            <td>
                                <span class="status-badge <?= $pay['status'] === 'paid' ? 'active' : ($pay['status'] === 'refunded' ? 'pending' : 'suspended') ?>"><?= e($pay['status']) ?></span>
                            </td>
                            <td style="color: #64748b; font-size: 0.8125rem;"><?= date('M d, Y H:i', strtotime($pay['created_at'])) ?></td>
                            <td>
                                <?php if ($pay['status'] === 'paid'): ?>
                                    <form action="/admin/billing/refund" method="POST" onsubmit="return confirm('Issue a refund of $<?= $pay['amount'] ?> for reference <?= $pay['reference_id'] ?>?');" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?= Security::csrfToken() ?>">
                                        <input type="hidden" name="transaction_id" value="<?= $pay['id'] ?>">
                                        <button type="submit" class="action-link danger" style="background: none; border: none; cursor: pointer; font-family: inherit;">Refund</button>
                                    </form>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include ROOT_PATH . '/app/Views/layouts/admin_footer.php'; ?>
