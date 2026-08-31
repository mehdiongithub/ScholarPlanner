<?php include ROOT_PATH . '/app/Views/layouts/student_header.php'; ?>
<script>
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>

<div style="margin-bottom: 24px;">
    <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0; color: #1e293b;">My Notifications</h1>
    <p style="margin: 4px 0 0 0; color: #64748b; font-size: 0.875rem;">Audit the logs of automated scholarship alerts dispatched to your WhatsApp or email.</p>
</div>

<div class="data-table-card" style="background:#fff; border:1px solid var(--border); border-radius:12px; padding:24px;">
    <?php if (empty($logs)): ?>
        <!-- Empty State -->
        <div style="text-align:center; padding:40px 20px;">
            <div style="width:64px; height:64px; background-color:#eff6ff; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 16px; color:#3b82f6;">
                <i data-lucide="bell-off" style="width:32px; height:32px;"></i>
            </div>
            <h3 style="font-size:1.125rem; font-weight:600; color:var(--text-900); margin-bottom:8px;">No notifications yet</h3>
            <p style="font-size:0.875rem; color:#64748b; max-width:320px; margin:0 auto 20px;">
                You haven't received any automated scholarship alert logs. Alerts trigger when new scholarships matching your criteria are published.
            </p>
            <a href="<?= url('/dashboard') ?>" class="btn btn-primary">Check Matches</a>
        </div>
    <?php else: ?>
        <div style="overflow-x:auto;">
            <table class="admin-table" style="width:100%;">
                <thead>
                    <tr>
                        <th>Recipient & Channel</th>
                        <th>Alert Details</th>
                        <th>Status</th>
                        <th>Sent At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <?php if ($log['channel'] === 'whatsapp'): ?>
                                        <span style="color:#25d366; background:#e8f8ec; padding:4px; border-radius:50%; display:inline-flex;">
                                            <i data-lucide="message-square" style="width:14px; height:14px;"></i>
                                        </span>
                                    <?php else: ?>
                                        <span style="color:#3b82f6; background:#eff6ff; padding:4px; border-radius:50%; display:inline-flex;">
                                            <i data-lucide="mail" style="width:14px; height:14px;"></i>
                                        </span>
                                    <?php echo 'Email'; endif; ?>
                                    <div style="font-size:0.875rem; font-weight:600; color:var(--text-900);">
                                        <?= e($log['recipient']) ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="font-size:0.875rem; font-weight:500; color:var(--text-700);">
                                    <?= e($log['subject'] ?: 'Scholarship Matching Alert') ?>
                                </div>
                                <div style="font-size:0.75rem; color:#64748b; margin-top:2px;">
                                    Type: <code><?= e(str_replace('_', ' ', $log['notification_type'])) ?></code>
                                </div>
                            </td>
                            <td>
                                <?php
                                    $status = strtolower($log['status'] ?? 'sent');
                                    $class = 'pending';
                                    if ($status === 'sent' || $status === 'delivered') $class = 'active';
                                    if ($status === 'failed') $class = 'suspended';
                                ?>
                                <span class="status-badge <?= $class ?>">
                                    <?= e($status) ?>
                                </span>
                            </td>
                            <td style="font-size:0.8125rem; color:#64748b;">
                                <?= date('M d, Y h:i A', strtotime($log['created_at'])) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include ROOT_PATH . '/app/Views/layouts/student_footer.php'; ?>
