<?php include ROOT_PATH . '/app/Views/layouts/admin_header.php'; ?>

<div style="margin-bottom: 24px;">
    <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0; color: #1e293b;">Location Management</h1>
    <p style="margin: 4px 0 0 0; color: #64748b; font-size: 0.875rem;">Manage Geographic Countries, States/Provinces, and Cities lookups.</p>
</div>

<!-- Navigation Tabs -->
<div class="location-nav">
    <a href="<?= url('/admin/locations/countries') ?>" class="location-nav-link">Countries</a>
    <a href="<?= url('/admin/locations/states') ?>" class="location-nav-link active">States / Provinces</a>
    <a href="<?= url('/admin/locations/cities') ?>" class="location-nav-link">Cities</a>
</div>

<div class="location-layout">
    <!-- Quick Add Form -->
    <div>
        <div class="form-card">
            <h2 style="font-size: 1.125rem; font-weight: 700; color: #1e293b; margin-top: 0; margin-bottom: 16px;">Add New State / Province</h2>
            <form action="<?= url('/admin/locations/states') ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= Security::csrfToken() ?>">
                
                <div class="form-group">
                    <label class="form-label" for="name">State Name</label>
                    <input type="text" name="name" id="name" class="form-control" placeholder="e.g. Punjab" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="country_id">Parent Country</label>
                    <select name="country_id" id="country_id" class="form-control" required>
                        <option value="">-- Select Country --</option>
                        <?php foreach ($countries as $c): ?>
                            <option value="<?= e($c['id']) ?>"><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; margin-top: 12px;">Add State</button>
            </form>
        </div>
    </div>

    <!-- Data Table -->
    <div>
        <div class="data-table-card">
            <div style="overflow-x: auto;">
                <table class="employees-table" id="states-datatable" style="width:100%">
                    <thead>
                        <tr>
                            <th>State / Province Name</th>
                            <th>Parent Country</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    ScholarMatchDataTable('#states-datatable', {
        ajax: {
            url: '<?= url("/admin/locations/states/data") ?>',
            type: 'GET'
        },
        columns: [
            { 
                data: 'name',
                render: function(data, type, row) {
                    return '<strong>' + data + '</strong>';
                }
            },
            { data: 'country_name' },
            {
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    var editUrl = '<?= url("/admin/locations/states") ?>' + '/' + row.record_id + '/edit';
                    return '<a href="' + editUrl + '" class="action-link">Edit</a>' +
                           '<a href="javascript:void(0)" onclick="deleteState(\'' + row.record_id + '\')" class="action-link danger">Delete</a>';
                }
            }
        ]
    });
});

function deleteState(recordId) {
    if (!confirm('Are you sure you want to delete this state? This will fail if cities are mapped.')) {
        return;
    }
    const formData = new FormData();
    formData.append('csrf_token', '<?= Security::csrfToken() ?>');

    fetch('<?= url("/admin/locations/states") ?>/' + recordId + '/delete', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            $('#states-datatable').DataTable().ajax.reload(null, false);
        } else {
            alert(data.error || 'Failed to delete state.');
        }
    })
    .catch(err => {
        console.error(err);
        alert('An error occurred during communication.');
    });
}
</script>

<?php include ROOT_PATH . '/app/Views/layouts/admin_footer.php'; ?>
