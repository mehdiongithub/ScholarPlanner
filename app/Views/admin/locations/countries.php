<?php include ROOT_PATH . '/app/Views/layouts/admin_header.php'; ?>

<div style="margin-bottom: 24px;">
    <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0; color: #1e293b;">Location Management</h1>
    <p style="margin: 4px 0 0 0; color: #64748b; font-size: 0.875rem;">Manage Geographic Countries, States/Provinces, and Cities lookups.</p>
</div>

<!-- Navigation Tabs -->
<div class="location-nav">
    <a href="<?= url('/admin/locations/countries') ?>" class="location-nav-link active">Countries</a>
    <a href="<?= url('/admin/locations/states') ?>" class="location-nav-link">States / Provinces</a>
    <a href="<?= url('/admin/locations/cities') ?>" class="location-nav-link">Cities</a>
</div>

<div class="location-layout">
    <!-- Quick Add Form -->
    <div>
        <div class="form-card">
            <h2 style="font-size: 1.125rem; font-weight: 700; color: #1e293b; margin-top: 0; margin-bottom: 16px;">Add New Country</h2>
            <form action="<?= url('/admin/locations/countries') ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= Security::csrfToken() ?>">
                
                <div class="form-group">
                    <label class="form-label" for="name">Country Name</label>
                    <input type="text" name="name" id="name" class="form-control" placeholder="e.g. Pakistan" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="iso_code">ISO 2-Letter Code</label>
                    <input type="text" name="iso_code" id="iso_code" class="form-control" placeholder="e.g. PK" required maxlength="2">
                </div>

                <div class="form-group">
                    <label class="form-label" for="currency_code">Currency Code</label>
                    <input type="text" name="currency_code" id="currency_code" class="form-control" placeholder="e.g. PKR" maxlength="3">
                </div>

                <div class="form-group">
                    <label class="form-label" for="dial_code">Dial Code</label>
                    <input type="text" name="dial_code" id="dial_code" class="form-control" placeholder="e.g. +92">
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; margin-top: 12px;">Add Country</button>
            </form>
        </div>
    </div>

    <!-- Data Table -->
    <div>
        <div class="data-table-card">
            <div style="overflow-x: auto;">
                <table class="employees-table" id="countries-datatable" style="width:100%">
                    <thead>
                        <tr>
                            <th>Country Name</th>
                            <th>ISO Code</th>
                            <th>Currency</th>
                            <th>Dial Code</th>
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
    ScholarMatchDataTable('#countries-datatable', {
        ajax: {
            url: '<?= url("/admin/locations/countries/data") ?>',
            type: 'GET'
        },
        columns: [
            { 
                data: 'name',
                render: function(data, type, row) {
                    return '<strong>' + data + '</strong>';
                }
            },
            { 
                data: 'iso_code',
                render: function(data, type, row) {
                    return '<code>' + data + '</code>';
                }
            },
            { 
                data: 'currency_code',
                render: function(data, type, row) {
                    return data ? data : '-';
                }
            },
            { 
                data: 'dial_code',
                render: function(data, type, row) {
                    return data ? data : '-';
                }
            },
            {
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    var editUrl = '<?= url("/admin/locations/countries") ?>' + '/' + row.record_id + '/edit';
                    return '<a href="' + editUrl + '" class="action-link">Edit</a>' +
                           '<a href="javascript:void(0)" onclick="deleteCountry(\'' + row.record_id + '\')" class="action-link danger">Delete</a>';
                }
            }
        ]
    });
});

function deleteCountry(recordId) {
    if (!confirm('Are you sure you want to delete this country? This will fail if states are mapped.')) {
        return;
    }
    const formData = new FormData();
    formData.append('csrf_token', '<?= Security::csrfToken() ?>');

    fetch('<?= url("/admin/locations/countries") ?>/' + recordId + '/delete', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            $('#countries-datatable').DataTable().ajax.reload(null, false);
        } else {
            alert(data.error || 'Failed to delete country.');
        }
    })
    .catch(err => {
        console.error(err);
        alert('An error occurred during communication.');
    });
}
</script>

<?php include ROOT_PATH . '/app/Views/layouts/admin_footer.php'; ?>
