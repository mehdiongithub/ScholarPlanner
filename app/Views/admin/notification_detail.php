<?php include ROOT_PATH . '/app/Views/layouts/admin_header.php'; ?>

<style>
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--text-600);
            text-decoration: none;
            font-size: 0.875rem;
            margin-bottom: 24px;
            font-weight: 500;
        }
        .detail-card {
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius-2xl);
            padding: 32px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 24px;
        }
        .detail-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border);
            padding-bottom: 20px;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }
        .detail-title {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--text-900);
            margin: 0;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: var(--radius-md);
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-processing { background: #e0f2fe; color: #0369a1; }
        .status-sent { background: #d1fae5; color: #065f46; }
        .status-failed { background: #fee2e2; color: #991b1b; }
        .status-retrying { background: #f3e8ff; color: #6b21a8; }
        .status-skipped { background: #e2e8f0; color: #475569; }

        .meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }
        .meta-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .meta-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-500);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .meta-value {
            font-weight: 600;
            color: var(--text-900);
            font-size: 0.9375rem;
            word-break: break-all;
        }
        .payload-box {
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            padding: 20px;
            font-family: monospace;
            font-size: 0.8125rem;
            color: var(--text-800);
            white-space: pre-wrap;
            overflow-x: auto;
            margin-top: 12px;
        }
        .alert-banner {
            border-radius: var(--radius-xl);
            padding: 16px;
            margin-bottom: 24px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        
        .btn-retry {
            background: var(--primary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: var(--radius-md);
            font-weight: 600;
            cursor: pointer;
            font-size: 0.875rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        .btn-retry:hover {
            opacity: 0.9;
        }
</style>
            <a href="<?= url('/admin/notifications') ?>" class="btn-back">
                <i data-lucide="arrow-left" style="width:16px; height:16px;"></i>
                <span>Back to Log Center</span>
            </a>

            <!-- Success/Error alert banners -->
            <?php if (isset($_SESSION['notification_success'])): ?>
                <div class="alert-banner alert-success">
                    <?= e($_SESSION['notification_success']) ?>
                    <?php unset($_SESSION['notification_success']); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['notification_error'])): ?>
                <div class="alert-banner alert-error">
                    <?= e($_SESSION['notification_error']) ?>
                    <?php unset($_SESSION['notification_error']); ?>
                </div>
            <?php endif; ?>

            <div class="detail-card">
                <div class="detail-header">
                    <div>
                        <span style="font-size:0.75rem; color:var(--text-500); font-weight:600; text-transform:uppercase;">Outbox Log Entry</span>
                        <h2 class="detail-title">Notification ID #<?= (int)$log['id'] ?></h2>
                    </div>
                    <span class="status-badge status-<?= $log['status'] ?>"><?= e($log['status']) ?></span>
                </div>

                <div class="meta-grid">
                    <div class="meta-item">
                        <span class="meta-label">Recipient User</span>
                        <span class="meta-value"><?= e($log['first_name'] . ' ' . $log['last_name']) ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Destination Recipient</span>
                        <span class="meta-value"><?= e($log['recipient']) ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Channel</span>
                        <span class="meta-value" style="text-transform:uppercase;"><?= e($log['channel']) ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Provider</span>
                        <span class="meta-value" style="text-transform:uppercase;"><?= e($log['provider'] ?? 'Default') ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Notification Type</span>
                        <span class="meta-value"><?= e($log['notification_type']) ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Enqueued At</span>
                        <span class="meta-value"><?= e(date('Y-m-d H:i:s', strtotime($log['created_at']))) ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Available At</span>
                        <span class="meta-value"><?= e(date('Y-m-d H:i:s', strtotime($log['available_at']))) ?></span>
                    </div>
                    <?php if ($log['sent_at']): ?>
                        <div class="meta-item">
                            <span class="meta-label">Dispatched At</span>
                            <span class="meta-value"><?= e(date('Y-m-d H:i:s', strtotime($log['sent_at']))) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($log['failed_at']): ?>
                        <div class="meta-item">
                            <span class="meta-label">Failed At</span>
                            <span class="meta-value" style="color:red;"><?= e(date('Y-m-d H:i:s', strtotime($log['failed_at']))) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($log['provider_message_id']): ?>
                        <div class="meta-item">
                            <span class="meta-label">Provider Msg ID</span>
                            <span class="meta-value"><?= e($log['provider_message_id']) ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="meta-item">
                        <span class="meta-label">Delivery Attempts</span>
                        <span class="meta-value"><?= (int)$log['attempts'] ?></span>
                    </div>
                    <div class="meta-item" style="grid-column: span 2;">
                        <span class="meta-label">Idempotency Key</span>
                        <span class="meta-value" style="font-family:monospace; font-size:0.8125rem;"><?= e($log['idempotency_key']) ?></span>
                    </div>
                </div>

                <!-- Error Message banner if failed -->
                <?php if ($log['error_message']): ?>
                    <div style="margin-bottom:24px;">
                        <div class="meta-label" style="color:#b91c1c;">Error Log History</div>
                        <div style="background:#fef2f2; border:1px solid #fca5a5; padding:16px; border-radius:8px; color:#b91c1c; font-family:monospace; font-size:0.875rem; margin-top:6px; word-break:break-all;">
                            <?= e($log['error_message']) ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Payload JSON view -->
                <div>
                    <div class="meta-label">Notification Payload Data</div>
                    <div class="payload-box"><?= e(json_encode(json_decode($log['payload']), JSON_PRETTY_PRINT)) ?></div>
                </div>

                <!-- Action section (Retry if failed or retrying) -->
                <?php if (in_array($log['status'], ['failed', 'retrying'])): ?>
                    <div style="margin-top:32px; border-top:1px solid var(--border); padding-top:24px; display:flex; justify-content:flex-end;">
                        <form action="<?= url('/admin/notifications/' . $log['id'] . '/retry') ?>" method="POST" style="margin:0;">
                            <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Security::csrfToken() ?>">
                            <button type="submit" class="btn-retry">
                                <i data-lucide="rotate-ccw" style="width:16px; height:16px;"></i>
                                <span>Re-enqueue Notification</span>
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
<?php include ROOT_PATH . '/app/Views/layouts/admin_footer.php'; ?>
