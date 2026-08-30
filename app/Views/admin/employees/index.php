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
    <a href="<?= url('/admin/employees/create') ?>" class="btn btn-primary">
        <i data-lucide="user-plus" style="width: 16px; height: 16px;"></i>
        <span>Add Employee</span>
    </a>
</div>

<div class="data-table-card">
    <div style="overflow-x: auto;">
        <table class="employees-table" id="employees-datatable" style="width:100%">
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
            </tbody>
        </table>
    </div>
</div>

<script>
const rolesList = <?= json_encode($roles) ?>;

$(document).ready(function() {
    $('#employees-datatable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: '<?= url("/admin/employees/data") ?>',
            type: 'GET'
        },
        columns: [
            { 
                data: 'employee_name',
                render: function(data, type, row) {
                    return '<strong>' + data + '</strong>';
                }
            },
            { data: 'email' },
            { 
                data: 'role_name',
                render: function(data, type, row) {
                    return '<span class="badge badge-secondary" style="font-weight: 700; text-transform: uppercase;">' + data + '</span>';
                }
            },
            { 
                data: 'status',
                render: function(data, type, row) {
                    return '<span class="status-badge ' + data + '">' + data + '</span>';
                }
            },
            {
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    var roleOptions = '';
                    rolesList.forEach(function(r) {
                        var selected = row.role_id == r.id ? 'selected' : '';
                        roleOptions += '<option value="' + r.id + '" ' + selected + '>' + r.name + '</option>';
                    });

                    var statusOptions = '';
                    var statuses = ['active', 'suspended'];
                    statuses.forEach(function(s) {
                        var selected = row.status === s ? 'selected' : '';
                        statusOptions += '<option value="' + s + '" ' + selected + '>' + s.charAt(0).toUpperCase() + s.slice(1) + '</option>';
                    });

                    var updateUrl = '<?= url("/admin/employees/") ?>' + '/' + row.record_id + '/update';

                    return '<form action="' + updateUrl + '" method="POST" class="edit-inline-form">' +
                        '<input type="hidden" name="csrf_token" value="<?= Security::csrfToken() ?>">' +
                        '<select name="role_id" class="form-control" style="padding: 4px 8px; font-size: 0.8125rem; width: auto; min-width: 140px;">' +
                        roleOptions +
                        '</select>' +
                        '<select name="status" class="form-control" style="padding: 4px 8px; font-size: 0.8125rem; width: auto;">' +
                        statusOptions +
                        '</select>' +
                        '<button type="submit" class="btn btn-secondary btn-sm">Update</button>' +
                        '</form>';
                }
            }
        ],
        drawCallback: function() {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }
    });
});
</script>

<?php include ROOT_PATH . '/app/Views/layouts/admin_footer.php'; ?>
