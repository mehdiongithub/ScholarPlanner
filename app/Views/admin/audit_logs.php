<?php include ROOT_PATH . '/app/Views/layouts/admin_header.php'; ?>

<style>
    .filter-card {
        background: #fff;
        border: 1px solid var(--border-slate-200);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 24px;
    }
    .filter-form {
        display: grid;
        grid-template-columns: 2fr 1fr auto;
        gap: 16px;
        align-items: end;
    }
    @media (max-width: 768px) {
        .filter-form {
            grid-template-columns: 1fr;
        }
    }
    .data-table-card {
        background: #fff;
        border: 1px solid var(--border-slate-200);
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }
    .logs-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.875rem;
    }
    .logs-table th, .logs-table td {
        padding: 12px 14px;
        text-align: left;
        border-bottom: 1px solid var(--border-slate-200);
    }
    .logs-table th {
        background-color: var(--bg-slate-50);
        font-weight: 600;
        color: #475569;
        font-size: 0.8125rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .logs-table tbody tr:hover {
        background-color: #fafafb;
    }
    
    /* Pagination */
    .pagination-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 24px;
    }
    .pagination-links {
        display: flex;
        gap: 6px;
    }
    .page-link {
        padding: 8px 14px;
        background-color: #fff;
        border: 1px solid var(--border-slate-200);
        border-radius: 6px;
        color: #334155;
        text-decoration: none;
        font-size: 0.875rem;
        font-weight: 500;
        transition: all 0.2s;
    }
    .page-link:hover {
        background-color: var(--bg-slate-50);
        border-color: #cbd5e1;
    }
    .page-link.active {
        background-color: var(--primary);
        color: #fff;
        border-color: var(--primary);
    }
</style>

<div style="margin-bottom: 24px;">
    <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0; color: #1e293b;">System Audit Trails</h1>
    <p style="margin: 4px 0 0 0; color: #64748b; font-size: 0.875rem;">Immutable logging of administrative events and actions for security tracking.</p>
</div>

<!-- Filters -->
<div class="filter-card">
    <form method="GET" action="/admin/audit-logs" class="filter-form">
        <div class="form-group" style="margin: 0;">
            <label class="form-label" style="font-size: 0.8125rem; font-weight: 600;">Search Logs</label>
            <input type="text" name="search" class="form-control" placeholder="Search by email, action, metadata..." value="<?= e($search) ?>">
        </div>

        <div class="form-group" style="margin: 0;">
            <label class="form-label" style="font-size: 0.8125rem; font-weight: 600;">Module</label>
            <select name="module" class="form-control">
                <option value="">All Modules</option>
                <?php foreach ($modules as $mod): ?>
                    <option value="<?= e($mod) ?>" <?= $selectedModule === $mod ? 'selected' : '' ?>><?= e(strtoupper($mod)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <button type="submit" class="btn btn-primary" style="padding: 10px 18px; width: 100%;">
                <i data-lucide="filter" style="width: 16px; height: 16px;"></i>
                <span>Filter</span>
            </button>
        </div>
    </form>
</div>

<!-- Logs List -->
<div class="data-table-card">
    <div style="overflow-x: auto;">
        <table class="logs-table">
            <thead>
                <tr>
                    <th>Actor / User</th>
                    <th>Action Executed</th>
                    <th>Module Group</th>
                    <th>Resource Target</th>
                    <th>Client IP & UA</th>
                    <th>Logged At</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: #94a3b8; padding: 32px;">No audit log records found matching constraints.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td>
                                <strong><?= e($log['first_name'] . ' ' . $log['last_name']) ?></strong>
                                <div style="font-size: 0.75rem; color: #64748b;"><?= e($log['actor_email'] ?: 'System / CLI') ?></div>
                            </td>
                            <td>
                                <code style="background-color: var(--bg-slate-100); padding: 4px 8px; border-radius: 6px; font-weight: 600; color: #b91c1c; font-size: 0.75rem;"><?= e($log['action']) ?></code>
                            </td>
                            <td><span style="font-weight: 600; text-transform: uppercase; font-size: 0.75rem; color: #475569;"><?= e($log['module']) ?></span></td>
                            <td>
                                <span style="font-size: 0.8125rem; font-weight: 500; color: #334155;"><?= e($log['resource_type']) ?> #<?= $log['resource_id'] ?: '-' ?></span>
                            </td>
                            <td>
                                <div style="font-size: 0.8125rem; font-weight: 600; color: #475569;"><?= e($log['ip_address'] ?? '127.0.0.1') ?></div>
                                <div style="font-size: 0.6875rem; color: #94a3b8; max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= e($log['user_agent']) ?>"><?= e($log['user_agent']) ?></div>
                            </td>
                            <td style="color: #64748b; font-size: 0.8125rem;"><?= e(date('M d, Y H:i:s', strtotime($log['created_at']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination-bar">
            <span style="font-size: 0.8125rem; color: #64748b;">Showing page <?= $page ?> of <?= $totalPages ?> (Total: <?= $totalCount ?> records)</span>
            <div class="pagination-links">
                <?php if ($page > 1): ?>
                    <a href="?search=<?= urlencode($search) ?>&module=<?= urlencode($selectedModule) ?>&page=<?= $page - 1 ?>" class="page-link">&larr; Previous</a>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?search=<?= urlencode($search) ?>&module=<?= urlencode($selectedModule) ?>&page=<?= $i ?>" class="page-link <?= $page === $i ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                    <a href="?search=<?= urlencode($search) ?>&module=<?= urlencode($selectedModule) ?>&page=<?= $page + 1 ?>" class="page-link">Next &rarr;</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include ROOT_PATH . '/app/Views/layouts/admin_footer.php'; ?>
