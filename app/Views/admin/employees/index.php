<?php include ROOT_PATH . '/app/Views/layouts/admin_header.php'; ?>

<style>
    .data-table-card {
        background: #fff;
        border: 1px solid var(--border-slate-200);
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }
    .employees-table {
        width: 100%;
        border-collapse: collapse;
    }
    .employees-table th, .employees-table td {
        padding: 14px 16px;
        text-align: left;
        border-bottom: 1px solid var(--border-slate-200);
    }
    .employees-table th {
        background-color: var(--bg-slate-50);
        font-weight: 600;
        color: #475569;
        font-size: 0.8125rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .employees-table tbody tr:hover {
        background-color: #fafafb;
    }
    .edit-inline-form {
        display: flex;
        align-items: center;
        gap: 8px;
    }
</style>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <div>
        <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0; color: #1e293b;">Staff & Employees</h1>
        <p style="margin: 4px 0 0 0; color: #64748b; font-size: 0.875rem;">Manage backoffice administrators, reviewers, and support employees.</p>
    </div>
    <a href="/admin/employees/create" class="btn btn-primary">
        <i data-lucide="user-plus" style="width: 16px; height: 16px;"></i>
        <span>Add Employee</span>
    </a>
</div>

<div class="data-table-card">
    <div style="overflow-x: auto;">
        <table class="employees-table">
            <thead>
                <tr>
                    <th>Employee Name</th>
                    <th>Email Address</th>
                    <th>Role Group</th>
                    <th>Status</th>
                    <th>Update Status & Role</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($employees as $emp): ?>
                    <tr>
                        <td>
                            <strong><?= e($emp['first_name'] . ' ' . $emp['last_name']) ?></strong>
                        </td>
                        <td><?= e($emp['email']) ?></td>
                        <td>
                            <span class="badge badge-secondary" style="font-weight: 700; text-transform: uppercase;"><?= e($emp['role_name']) ?></span>
                        </td>
                        <td>
                            <span class="status-badge <?= e($emp['status']) ?>"><?= e($emp['status']) ?></span>
                        </td>
                        <td>
                            <form action="/admin/employees/<?= $emp['id'] ?>/update" method="POST" class="edit-inline-form">
                                <input type="hidden" name="csrf_token" value="<?= Security::csrfToken() ?>">
                                
                                <select name="role_id" class="form-control" style="padding: 4px 8px; font-size: 0.8125rem; width: auto; min-width: 140px;">
                                    <?php foreach ($roles as $r): ?>
                                        <option value="<?= $r['id'] ?>" <?= $emp['role_id'] == $r['id'] ? 'selected' : '' ?>><?= e($r['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>

                                <select name="status" class="form-control" style="padding: 4px 8px; font-size: 0.8125rem; width: auto;">
                                    <option value="active" <?= $emp['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="suspended" <?= $emp['status'] === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                                </select>

                                <button type="submit" class="btn btn-secondary btn-sm">Update</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include ROOT_PATH . '/app/Views/layouts/admin_footer.php'; ?>
