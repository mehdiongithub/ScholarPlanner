<?php include ROOT_PATH . '/app/Views/layouts/admin_header.php'; ?>

<style>
        .header-title-box h1 {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--text-900);
            margin: 0;
        }
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }
        .metric-card {
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            padding: 16px;
            text-align: center;
            box-shadow: var(--shadow-sm);
        }
        .metric-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-500);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 6px;
        }
        .metric-value {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text-900);
        }
        .filter-card {
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius-2xl);
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: var(--shadow-sm);
        }
        .filter-form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            align-items: flex-end;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .form-label {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--text-700);
            text-transform: uppercase;
        }
        .form-control {
            padding: 8px 12px;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            background: var(--bg-white);
            color: var(--text-900);
        }
        .btn-filter {
            background: var(--primary);
            color: white;
            border: none;
            padding: 9px 16px;
            border-radius: var(--radius-md);
            font-weight: 600;
            cursor: pointer;
            font-size: 0.875rem;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-filter:hover {
            opacity: 0.9;
        }
        .logs-card {
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius-2xl);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            margin-bottom: 24px;
        }
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .logs-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
            text-align: left;
        }
        .logs-table th {
            background: #f8fafc;
            padding: 14px 16px;
            font-weight: 600;
            color: var(--text-700);
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }
        .logs-table td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border);
            color: var(--text-800);
            vertical-align: middle;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: var(--radius-sm);
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

        .pagination-box {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 24px;
            background: #f8fafc;
            border-top: 1px solid var(--border);
            flex-wrap: wrap;
            gap: 12px;
        }
        .btn-page {
            border: 1px solid var(--border);
            background: var(--bg-white);
            color: var(--text-700);
            padding: 6px 12px;
            border-radius: var(--radius-md);
            text-decoration: none;
            font-size: 0.8125rem;
            font-weight: 500;
        }
        .btn-page:hover {
            background: #f1f5f9;
        }
        .btn-page.disabled {
            opacity: 0.5;
            pointer-events: none;
        }
        @media (max-width: 576px) {
            .page-content {
                padding: 16px 12px;
            }
            .header-title-box h1 {
                font-size: 1.5rem;
            }
            .filter-card {
                padding: 16px;
            }
        }
</style>
            <div class="header-title-box">
                <h1>Notification Log Center</h1>
            </div>

            <!-- Summary metrics -->
            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-label">Total Logs</div>
                    <div class="metric-value"><?= (int)($summary['total'] ?? 0) ?></div>
                </div>
                <div class="metric-card">
                    <div class="metric-label">Pending</div>
                    <div class="metric-value" style="color:#b45309;"><?= (int)($summary['pending'] ?? 0) ?></div>
                </div>
                <div class="metric-card">
                    <div class="metric-label">Retrying</div>
                    <div class="metric-value" style="color:#7e22ce;"><?= (int)($summary['retrying'] ?? 0) ?></div>
                </div>
                <div class="metric-card">
                    <div class="metric-label">Sent</div>
                    <div class="metric-value" style="color:#047857;"><?= (int)($summary['sent'] ?? 0) ?></div>
                </div>
                <div class="metric-card">
                    <div class="metric-label">Failed</div>
                    <div class="metric-value" style="color:#b91c1c;"><?= (int)($summary['failed'] ?? 0) ?></div>
                </div>
                <div class="metric-card">
                    <div class="metric-label">Skipped</div>
                    <div class="metric-value" style="color:#4b5563;"><?= (int)($summary['skipped'] ?? 0) ?></div>
                </div>
                <div class="metric-card">
                    <div class="metric-label">WhatsApp</div>
                    <div class="metric-value"><?= (int)($summary['whatsapp_count'] ?? 0) ?></div>
                </div>
                <div class="metric-card">
                    <div class="metric-label">Emails</div>
                    <div class="metric-value"><?= (int)($summary['email_count'] ?? 0) ?></div>
                </div>
            </div>

            <!-- Filters -->
            <div class="filter-card">
                <form method="GET" action="<?= url('/admin/notifications') ?>">
                    <div class="filter-form-grid">
                        <div class="form-group">
                            <label class="form-label">Search User</label>
                            <input type="text" name="user" class="form-control" placeholder="Name or Email" value="<?= e($filters['user'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Channel</label>
                            <select name="channel" class="form-control">
                                <option value="">All Channels</option>
                                <option value="email" <?= ($filters['channel'] ?? '') === 'email' ? 'selected' : '' ?>>Email</option>
                                <option value="whatsapp" <?= ($filters['channel'] ?? '') === 'whatsapp' ? 'selected' : '' ?>>WhatsApp</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-control">
                                <option value="">All Statuses</option>
                                <option value="pending" <?= ($filters['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="processing" <?= ($filters['status'] ?? '') === 'processing' ? 'selected' : '' ?>>Processing</option>
                                <option value="sent" <?= ($filters['status'] ?? '') === 'sent' ? 'selected' : '' ?>>Sent</option>
                                <option value="failed" <?= ($filters['status'] ?? '') === 'failed' ? 'selected' : '' ?>>Failed</option>
                                <option value="retrying" <?= ($filters['status'] ?? '') === 'retrying' ? 'selected' : '' ?>>Retrying</option>
                                <option value="skipped" <?= ($filters['status'] ?? '') === 'skipped' ? 'selected' : '' ?>>Skipped</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Type</label>
                            <select name="type" class="form-control">
                                <option value="">All Types</option>
                                <option value="NEW_MATCH" <?= ($filters['type'] ?? '') === 'NEW_MATCH' ? 'selected' : '' ?>>NEW_MATCH</option>
                                <option value="SCHOLARSHIP_DEADLINE_SOON" <?= ($filters['type'] ?? '') === 'SCHOLARSHIP_DEADLINE_SOON' ? 'selected' : '' ?>>DEADLINE_SOON</option>
                                <option value="SCHOLARSHIP_DEADLINE_TODAY" <?= ($filters['type'] ?? '') === 'SCHOLARSHIP_DEADLINE_TODAY' ? 'selected' : '' ?>>DEADLINE_TODAY</option>
                                <option value="DAILY_MATCH_DIGEST" <?= ($filters['type'] ?? '') === 'DAILY_MATCH_DIGEST' ? 'selected' : '' ?>>DAILY_DIGEST</option>
                                <option value="WEEKLY_MATCH_DIGEST" <?= ($filters['type'] ?? '') === 'WEEKLY_MATCH_DIGEST' ? 'selected' : '' ?>>WEEKLY_DIGEST</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Created Date</label>
                            <input type="date" name="date" class="form-control" value="<?= e($filters['date'] ?? '') ?>">
                        </div>
                        <div>
                            <button type="submit" class="btn-filter" style="width: 100%;">
                                <i data-lucide="filter" style="width:16px; height:16px;"></i>
                                <span>Filter</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Logs Table -->
            <div class="logs-card">
                <div class="table-responsive">
                    <table class="logs-table">
                        <thead>
                            <tr>
                                <th>Recipient</th>
                                <th>Channel</th>
                                <th>Type</th>
                                <th>Subject / Info</th>
                                <th>Status</th>
                                <th>Attempts</th>
                                <th>Available At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="8" style="text-align:center; padding:32px; color:var(--text-400);">No notification logs found matching the filters.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight:600; color:var(--text-900);"><?= e($log['first_name'] . ' ' . $log['last_name']) ?></div>
                                            <div style="font-size:0.75rem; color:var(--text-500);"><?= e($log['recipient']) ?></div>
                                        </td>
                                        <td>
                                            <span style="display:flex; align-items:center; gap:6px;">
                                                <?php if ($log['channel'] === 'email'): ?>
                                                    <i data-lucide="mail" style="width:14px; height:14px; color:#3b82f6;"></i> Email
                                                <?php else: ?>
                                                    <i data-lucide="message-square" style="width:14px; height:14px; color:#10b981;"></i> WhatsApp
                                                <?php endif; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span style="font-family:monospace; font-size:0.75rem; font-weight:600;"><?= e($log['notification_type']) ?></span>
                                        </td>
                                        <td>
                                            <?php if ($log['scholarship_title']): ?>
                                                <div style="font-size:0.75rem; color:var(--text-500);">Scholarship:</div>
                                                <div style="max-width:250px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-weight:500;" title="<?= e($log['scholarship_title']) ?>">
                                                    <?= e($log['scholarship_title']) ?>
                                                </div>
                                            <?php else: ?>
                                                <div style="max-width:250px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?= e($log['subject']) ?>">
                                                    <?= e($log['subject'] ?? 'System Alert') ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?= $log['status'] ?>"><?= e($log['status']) ?></span>
                                        </td>
                                        <td style="font-weight:600; text-align:center;"><?= (int)$log['attempts'] ?></td>
                                        <td style="font-size:0.75rem; color:var(--text-600);"><?= e(date('M d, H:i', strtotime($log['available_at']))) ?></td>
                                        <td>
                                            <a href="<?= url('/admin/notifications/' . $log['id']) ?>" class="btn-page" style="padding:4px 8px; font-size:0.75rem;">
                                                <i data-lucide="eye" style="width:12px; height:12px; display:inline; vertical-align:middle; margin-right:4px;"></i>Inspect
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination footer -->
                <?php if ($pagination['total'] > 1): ?>
                    <div class="pagination-box">
                        <span style="font-size:0.8125rem; color:var(--text-500);">Showing <?= count($logs) ?> of <?= $pagination['count'] ?> records</span>
                        <div style="display:flex; gap:8px;">
                            <a href="<?= url('/admin/notifications?' . http_build_query(array_merge($filters, ['page' => $pagination['current'] - 1]))) ?>" 
                               class="btn-page <?= $pagination['current'] <= 1 ? 'disabled' : '' ?>">Previous</a>
                            
                            <span style="align-self:center; font-size:0.8125rem; font-weight:600;">Page <?= $pagination['current'] ?> of <?= $pagination['total'] ?></span>

                            <a href="<?= url('/admin/notifications?' . http_build_query(array_merge($filters, ['page' => $pagination['current'] + 1]))) ?>" 
                               class="btn-page <?= $pagination['current'] >= $pagination['total'] ? 'disabled' : '' ?>">Next</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
<?php include ROOT_PATH . '/app/Views/layouts/admin_footer.php'; ?>
