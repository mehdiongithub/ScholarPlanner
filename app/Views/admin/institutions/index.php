<?php include ROOT_PATH . '/app/Views/layouts/admin_header.php'; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <div>
        <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0; color: #1e293b;">Manage Institutions</h1>
        <p style="margin: 4px 0 0 0; color: #64748b; font-size: 0.875rem;">Manage higher education institutions and coverage settings.</p>
    </div>
    <a href="<?= url('/admin/institutions/create') ?>" class="btn btn-primary">
        <i data-lucide="plus" style="width: 16px; height: 16px;"></i>
        <span>Add Institution</span>
    </a>
</div>

<?php if (isset($_SESSION['admin_success'])): ?>
    <div class="alert alert-success">
        <?= e($_SESSION['admin_success']) ?>
        <?php unset($_SESSION['admin_success']); ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['admin_errors'])): ?>
    <div class="alert alert-danger">
        <?php foreach ($_SESSION['admin_errors'] as $err): ?>
            <p><?= e($err) ?></p>
        <?php endforeach; ?>
        <?php unset($_SESSION['admin_errors']); ?>
    </div>
<?php endif; ?>

<div class="data-table-card">
    <div style="overflow-x: auto;">
        <table class="admin-table" id="institutions-datatable" style="width:100%">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Country</th>
                    <th>Coverage</th>
                    <th>Status</th>
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
    ScholarMatchDataTable('#institutions-datatable', {
        ajax: {
            url: '<?= url("/admin/institutions/data") ?>',
            type: 'GET'
        },
        columns: [
            { 
                data: 'name',
                render: function(data, type, row) {
                    return '<strong style="color: var(--text-900);">' + data + '</strong>';
                }
            },
            { 
                data: 'institution_type',
                render: function(data, type, row) {
                    if (!data) return '';
                    return data.charAt(0).toUpperCase() + data.slice(1);
                }
            },
            { 
                data: 'country_name',
                render: function(data, type, row) {
                    return data ? data : 'N/A';
                }
            },
            { 
                data: 'coverage_type',
                render: function(data, type, row) {
                    if (data === 'state') {
                        return 'State Specific (' + (row.state_name ? row.state_name : 'None') + ')';
                    } else if (data === 'multi_state') {
                        return 'Multiple States';
                    } else {
                        return 'National';
                    }
                }
            },
            { 
                data: 'status',
                render: function(data, type, row) {
                    return '<span class="status-badge status-' + data + '">' + data + '</span>';
                }
            },
            {
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    var editUrl = '<?= url("/admin/institutions") ?>' + '/' + row.record_id + '/edit';
                    var deleteUrl = '<?= url("/admin/institutions") ?>' + '/' + row.record_id + '/delete';
                    
                    return '<div style="display:flex; gap:8px;">' +
                           '<a href="' + editUrl + '" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.75rem;">Edit</a>' +
                           '<form action="' + deleteUrl + '" method="POST" onsubmit="return confirm(\'Are you sure you want to delete this institution?\');" style="display:inline;">' +
                           '<input type="hidden" name="csrf_token" value="<?= Security::csrfToken() ?>">' +
                           '<button type="submit" class="btn btn-danger" style="padding: 6px 12px; font-size: 0.75rem;">Delete</button>' +
                           '</form>' +
                           '</div>';
                }
            }
        ]
    });
});
</script>

<?php include ROOT_PATH . '/app/Views/layouts/admin_footer.php'; ?>
