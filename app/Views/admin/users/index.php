<?php include ROOT_PATH . '/app/Views/layouts/admin_header.php'; ?>

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
    var table = ScholarPlannerDataTable('#users-datatable', {
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
                    
                    var userName = ((row.first_name || '') + ' ' + (row.last_name || '')).trim() || row.email || 'User';
                    var safeUserName = $('<div>').text(userName).html();
                    var actionBtn = '';
                    if (row.status === 'suspended') {
                        actionBtn = '<a href="javascript:void(0)" class="action-link btn-toggle-user-status" data-id="' + row.record_id + '" data-action="activate" data-name="' + safeUserName + '" style="color: #22c55e;">Activate</a>';
                    } else {
                        actionBtn = '<a href="javascript:void(0)" class="action-link btn-toggle-user-status" data-id="' + row.record_id + '" data-action="suspend" data-name="' + safeUserName + '" style="color: #eab308;">Suspend</a>';
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

    $(document).on('click', '.btn-toggle-user-status', function(e) {
        e.preventDefault();
        var recordId = $(this).attr('data-id');
        var action = $(this).attr('data-action');
        var name = $(this).attr('data-name');
        updateUserStatus(recordId, action, name);
    });

    // Handle filter form submission
    $('.filter-form').on('submit', function(e) {
        e.preventDefault();
        table.draw();
    });
});

function updateUserStatus(recordId, action, userName) {
    var isSuspend = action === 'suspend';
    var title = isSuspend ? 'Suspend User Account' : 'Activate User Account';
    var userLabel = userName ? ' <strong>"' + adminEscapeHtml(userName) + '"</strong>' : ' this user account';
    var msg = 'Are you sure you want to ' + action + userLabel + '?';
    var subtext = isSuspend ? 'The user will be immediately blocked from logging in.' : 'The user will regain access to their account.';
    var btnText = isSuspend ? 'Yes, Suspend' : 'Yes, Activate';
    var btnClass = isSuspend ? 'btn-danger' : 'btn-primary';
    var icon = isSuspend ? 'user-minus' : 'user-check';

    adminConfirm({
        title: title,
        message: msg,
        subtext: subtext,
        confirmText: btnText,
        confirmClass: btnClass,
        icon: icon
    }, function() {
        const formData = new FormData();
        formData.append('csrf_token', '<?= \App\Helpers\Security::csrfToken() ?>');

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
                adminConfirm({
                    title: 'Action Failed',
                    message: data.error || 'Failed to update user status.',
                    subtext: '',
                    confirmText: 'OK',
                    confirmClass: 'btn-primary',
                    icon: 'alert-triangle'
                });
            }
        })
        .catch(err => {
            console.error(err);
            adminConfirm({
                title: 'Error',
                message: 'An error occurred during communication.',
                subtext: '',
                confirmText: 'OK',
                confirmClass: 'btn-danger',
                icon: 'alert-triangle'
            });
        });
    });
}
</script>

<?php include ROOT_PATH . '/app/Views/layouts/admin_footer.php'; ?>
