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
        grid-template-columns: 2fr 1fr 1fr 1fr auto;
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
    .users-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }
    .users-table th, .users-table td {
        padding: 14px 16px;
        text-align: left;
        border-bottom: 1px solid var(--border-slate-200);
    }
    .users-table th {
        background-color: var(--bg-slate-50);
        font-weight: 600;
        color: #475569;
        font-size: 0.8125rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .users-table tbody tr:hover {
        background-color: #fafafb;
    }
    .action-link {
        color: var(--primary);
        text-decoration: none;
        font-weight: 600;
        font-size: 0.8125rem;
        margin-right: 12px;
    }
    .action-link:hover {
        text-decoration: underline;
    }
    .action-link.danger {
        color: #ef4444;
    }

    /* Badge styles */
    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    .status-badge.active { background-color: #dcfce7; color: #15803d; }
    .status-badge.pending { background-color: #fef3c7; color: #d97706; }
    .status-badge.suspended { background-color: #fee2e2; color: #b91c1c; }
    .status-badge.deleted { background-color: #f1f5f9; color: #64748b; }

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

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <div>
        <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0; color: #1e293b;">Students & Platform Visitors</h1>
        <p style="margin: 4px 0 0 0; color: #64748b; font-size: 0.875rem;">Manage register student user base and view progress wizards.</p>
    </div>
</div>

<!-- Filters -->
<div class="filter-card">
    <form method="GET" action="/admin/users" class="filter-form">
        <div class="form-group" style="margin: 0;">
            <label class="form-label" style="font-size: 0.8125rem; font-weight: 600;">Search Users</label>
            <input type="text" name="search" class="form-control" placeholder="Search by name or email..." value="<?= e($search) ?>">
        </div>

        <div class="form-group" style="margin: 0;">
            <label class="form-label" style="font-size: 0.8125rem; font-weight: 600;">Status</label>
            <select name="status" class="form-control">
                <option value="">All Statuses</option>
                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="suspended" <?= $status === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                <option value="deleted" <?= $status === 'deleted' ? 'selected' : '' ?>>Deleted</option>
            </select>
        </div>

        <div class="form-group" style="margin: 0;">
            <label class="form-label" style="font-size: 0.8125rem; font-weight: 600;">Verification</label>
            <select name="verified" class="form-control">
                <option value="">All Verification</option>
                <option value="1" <?= $verified === '1' ? 'selected' : '' ?>>Email Verified</option>
                <option value="0" <?= $verified === '0' ? 'selected' : '' ?>>Unverified</option>
            </select>
        </div>

        <div class="form-group" style="margin: 0;">
            <label class="form-label" style="font-size: 0.8125rem; font-weight: 600;">Subscription Plan</label>
            <select name="plan" class="form-control">
                <option value="">All Plans</option>
                <option value="free" <?= $plan === 'free' ? 'selected' : '' ?>>Free / Guest</option>
                <?php foreach ($plans as $pName): ?>
                    <option value="<?= e($pName) ?>" <?= $plan === $pName ? 'selected' : '' ?>><?= e($pName) ?></option>
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

<!-- Grid -->
<div class="data-table-card">
    <div style="overflow-x: auto;">
        <table class="users-table">
            <thead>
                <tr>
                    <th>User Details</th>
                    <th>Email Verification</th>
                    <th>Status</th>
                    <th>Profile Completion</th>
                    <th>Active Plan</th>
                    <th>Joined Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: #94a3b8; padding: 32px;">No student records found matching filters.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $u): ?>
                        <tr id="user-row-<?= $u['id'] ?>">
                            <td>
                                <strong><?= e($u['first_name'] . ' ' . $u['last_name']) ?></strong>
                                <div style="font-size: 0.75rem; color: #64748b;"><?= e($u['email']) ?></div>
                            </td>
                            <td>
                                <?php if ($u['email_verified_at']): ?>
                                    <span style="color: #22c55e; font-size: 0.8125rem; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                                        <i data-lucide="check-circle" style="width: 14px; height: 14px;"></i> Verified
                                    </span>
                                <?php else: ?>
                                    <span style="color: #64748b; font-size: 0.8125rem;">Unverified</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge <?= e($u['status']) ?>" id="status-badge-<?= $u['id'] ?>"><?= e($u['status']) ?></span>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <div style="width: 80px; height: 6px; background-color: #f1f5f9; border-radius: 3px; overflow: hidden;">
                                        <div style="width: <?= (int)($u['completion'] ?? 0) ?>%; height: 100%; background-color: var(--primary);"></div>
                                    </div>
                                    <span style="font-size: 0.75rem; font-weight: 600;"><?= (int)($u['completion'] ?? 0) ?>%</span>
                                </div>
                            </td>
                            <td>
                                <span style="font-size: 0.8125rem; font-weight: 500; color: #475569;"><?= e($u['plan_name'] ?: 'None (Free)') ?></span>
                            </td>
                            <td style="color: #64748b; font-size: 0.8125rem;"><?= e(date('M d, Y', strtotime($u['created_at']))) ?></td>
                            <td>
                                <a href="/admin/users/<?= $u['id'] ?>" class="action-link">View Details</a>
                                <a href="/admin/users/<?= $u['id'] ?>/edit" class="action-link">Edit</a>
                                
                                <span id="toggle-suspend-btn-box-<?= $u['id'] ?>" style="display: inline;">
                                    <?php if ($u['status'] === 'suspended'): ?>
                                        <a href="javascript:void(0)" onclick="updateUserStatus(<?= $u['id'] ?>, 'activate')" class="action-link" style="color: #22c55e;">Activate</a>
                                    <?php else: ?>
                                        <a href="javascript:void(0)" onclick="updateUserStatus(<?= $u['id'] ?>, 'suspend')" class="action-link" style="color: #eab308;">Suspend</a>
                                    <?php endif; ?>
                                </span>
                            </td>
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
                    <a href="?search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&verified=<?= urlencode($verified) ?>&plan=<?= urlencode($plan) ?>&page=<?= $page - 1 ?>" class="page-link">&larr; Previous</a>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&verified=<?= urlencode($verified) ?>&plan=<?= urlencode($plan) ?>&page=<?= $i ?>" class="page-link <?= $page === $i ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                    <a href="?search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&verified=<?= urlencode($verified) ?>&plan=<?= urlencode($plan) ?>&page=<?= $page + 1 ?>" class="page-link">Next &rarr;</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    function updateUserStatus(userId, action) {
        if (!confirm('Are you sure you want to ' + action + ' this user account?')) {
            return;
        }

        const formData = new FormData();
        formData.append('csrf_token', '<?= Security::csrfToken() ?>');

        fetch('/admin/users/' + userId + '/' + action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update Badge UI
                const badge = document.getElementById('status-badge-' + userId);
                const buttonBox = document.getElementById('toggle-suspend-btn-box-' + userId);
                
                if (action === 'suspend') {
                    badge.className = 'status-badge suspended';
                    badge.innerText = 'suspended';
                    buttonBox.innerHTML = '<a href="javascript:void(0)" onclick="updateUserStatus(' + userId + ', \'activate\')" class="action-link" style="color: #22c55e;">Activate</a>';
                } else {
                    badge.className = 'status-badge active';
                    badge.innerText = 'active';
                    buttonBox.innerHTML = '<a href="javascript:void(0)" onclick="updateUserStatus(' + userId + ', \'suspend\')" class="action-link" style="color: #eab308;">Suspend</a>';
                }
            } else {
                alert(data.error || 'Failed to update user status.');
            }
        })
        .catch(err => {
            console.error(err);
            alert('An error occurred during communication.');
        });
    }
</script>

<?php include ROOT_PATH . '/app/Views/layouts/admin_footer.php'; ?>
