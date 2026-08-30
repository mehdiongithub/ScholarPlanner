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
</style>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <div>
        <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0; color: #1e293b;">Students & Platform Visitors</h1>
        <p style="margin: 4px 0 0 0; color: #64748b; font-size: 0.875rem;">Manage registered student user base and view progress wizards.</p>
    </div>
</div>

<!-- Filters -->
<div class="filter-card">
    <form class="filter-form">
        <div class="form-group" style="margin: 0;">
            <label class="form-label" style="font-size: 0.8125rem; font-weight: 600;">Search Users</label>
            <input type="text" name="search" class="form-control" placeholder="Search by name or email..." value="<?= e($search ?? '') ?>">
        </div>

        <div class="form-group" style="margin: 0;">
            <label class="form-label" style="font-size: 0.8125rem; font-weight: 600;">Status</label>
            <select name="status" class="form-control">
                <option value="">All Statuses</option>
                <option value="active" <?= ($status ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="pending" <?= ($status ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="suspended" <?= ($status ?? '') === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                <option value="deleted" <?= ($status ?? '') === 'deleted' ? 'selected' : '' ?>>Deleted</option>
            </select>
        </div>

        <div class="form-group" style="margin: 0;">
            <label class="form-label" style="font-size: 0.8125rem; font-weight: 600;">Verification</label>
            <select name="verified" class="form-control">
                <option value="">All Verification</option>
                <option value="1" <?= ($verified ?? '') === '1' ? 'selected' : '' ?>>Email Verified</option>
                <option value="0" <?= ($verified ?? '') === '0' ? 'selected' : '' ?>>Unverified</option>
            </select>
        </div>

        <div class="form-group" style="margin: 0;">
            <label class="form-label" style="font-size: 0.8125rem; font-weight: 600;">Subscription Plan</label>
            <select name="plan" class="form-control">
                <option value="">All Plans</option>
                <option value="free" <?= ($plan ?? '') === 'free' ? 'selected' : '' ?>>Free / Guest</option>
                <?php foreach ($plans as $pName): ?>
                    <option value="<?= e($pName) ?>" <?= ($plan ?? '') === $pName ? 'selected' : '' ?>><?= e($pName) ?></option>
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
        <table class="users-table" id="users-datatable" style="width:100%">
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
            </tbody>
        </table>
    </div>
</div>

<script>
$(document).ready(function() {
    var table = $('#users-datatable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: '<?= url("/admin/users/data") ?>',
            type: 'GET',
            data: function(d) {
                d.search_query = $('input[name="search"]').val();
                d.status = $('select[name="status"]').val();
                d.verified = $('select[name="verified"]').val();
                d.plan = $('select[name="plan"]').val();
            }
        },
        columns: [
            { 
                data: 'student_name',
                render: function(data, type, row) {
                    return '<strong>' + data + '</strong><div style="font-size: 0.75rem; color: #64748b;">' + row.email + '</div>';
                }
            },
            { 
                data: 'email_verified_at',
                render: function(data, type, row) {
                    if (data) {
                        return '<span style="color: #22c55e; font-size: 0.8125rem; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;"><i data-lucide="check-circle" style="width: 14px; height: 14px;"></i> Verified</span>';
                    } else {
                        return '<span style="color: #64748b; font-size: 0.8125rem;">Unverified</span>';
                    }
                }
            },
            { 
                data: 'status',
                render: function(data, type, row) {
                    return '<span class="status-badge ' + data + '" id="status-badge-' + row.record_id + '">' + data + '</span>';
                }
            },
            { 
                data: 'completion',
                render: function(data, type, row) {
                    var completion = parseInt(data) || 0;
                    return '<div style="display: flex; align-items: center; gap: 8px;">' +
                        '<div style="width: 80px; height: 6px; background-color: #f1f5f9; border-radius: 3px; overflow: hidden;">' +
                        '<div style="width: ' + completion + '%; height: 100%; background-color: var(--primary);"></div>' +
                        '</div>' +
                        '<span style="font-size: 0.75rem; font-weight: 600;">' + completion + '%</span>' +
                        '</div>';
                }
            },
            { 
                data: 'plan_name',
                render: function(data, type, row) {
                    return '<span style="font-size: 0.8125rem; font-weight: 500; color: #475569;">' + (data ? data : 'None (Free)') + '</span>';
                }
            },
            { 
                data: 'created_at',
                render: function(data, type, row) {
                    if (!data) return '';
                    var date = new Date(data);
                    return date.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
                }
            },
            {
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    var detailUrl = '<?= url("/admin/users") ?>' + '/' + row.record_id;
                    var editUrl = '<?= url("/admin/users") ?>' + '/' + row.record_id + '/edit';
                    
                    var actionBtn = '';
                    if (row.status === 'suspended') {
                        actionBtn = '<a href="javascript:void(0)" onclick="updateUserStatus(\'' + row.record_id + '\', \'activate\')" class="action-link" style="color: #22c55e;">Activate</a>';
                    } else {
                        actionBtn = '<a href="javascript:void(0)" onclick="updateUserStatus(\'' + row.record_id + '\', \'suspend\')" class="action-link" style="color: #eab308;">Suspend</a>';
                    }

                    return '<a href="' + detailUrl + '" class="action-link">View Details</a>' +
                           '<a href="' + editUrl + '" class="action-link">Edit</a>' +
                           '<span id="toggle-suspend-btn-box-' + row.record_id + '" style="display: inline;">' + actionBtn + '</span>';
                }
            }
        ],
        drawCallback: function() {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }
    });

    // Handle filter form submission
    $('.filter-form').on('submit', function(e) {
        e.preventDefault();
        table.draw();
    });
});

function updateUserStatus(recordId, action) {
    if (!confirm('Are you sure you want to ' + action + ' this user account?')) {
        return;
    }

    const formData = new FormData();
    formData.append('csrf_token', '<?= Security::csrfToken() ?>');

    fetch('<?= url("/admin/users") ?>/' + recordId + '/' + action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            $('#users-datatable').DataTable().ajax.reload(null, false);
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
