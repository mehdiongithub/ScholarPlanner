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
    <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0; color: #1e293b;">Student Subscriptions</h1>
    <p style="margin: 4px 0 0 0; color: #64748b; font-size: 0.875rem;">Monitor student premium subscriptions and active membership cycles.</p>
</div>

<div class="data-table-card">
    <div style="overflow-x: auto;">
        <table class="employees-table">
            <thead>
                <tr>
                    <th>Student Details</th>
                    <th>Subscribed Plan</th>
                    <th>Status</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($subscriptions)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: #94a3b8; padding: 32px;">No active or historical subscriptions found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($subscriptions as $sub): ?>
                        <tr>
                            <td>
                                <strong><?= e($sub['first_name'] . ' ' . $sub['last_name']) ?></strong>
                                <div style="font-size: 0.75rem; color: #64748b;"><?= e($sub['user_email']) ?></div>
                            </td>
                            <td><strong><?= e($sub['plan_name']) ?></strong></td>
                            <td>
                                <span class="status-badge <?= $sub['status'] === 'active' ? 'active' : 'suspended' ?>"><?= e($sub['status']) ?></span>
                            </td>
                            <td><?= $sub['starts_at'] ? date('M d, Y', strtotime($sub['starts_at'])) : '-' ?></td>
                            <td><?= $sub['ends_at'] ? date('M d, Y', strtotime($sub['ends_at'])) : 'Ongoing / Lifetime' ?></td>
                            <td style="color: #64748b; font-size: 0.8125rem;"><?= date('M d, Y H:i', strtotime($sub['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include ROOT_PATH . '/app/Views/layouts/admin_footer.php'; ?>
