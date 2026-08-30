<?php include ROOT_PATH . '/app/Views/layouts/admin_header.php'; ?>

<div style="margin-bottom: 24px;">
    <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0; color: #1e293b;">Academic Configuration</h1>
    <p style="margin: 4px 0 0 0; color: #64748b; font-size: 0.875rem;">Manage Fields of Study, Academic Degrees, and Funding Types.</p>
</div>

<!-- Navigation Tabs -->
<div class="location-nav">
    <a href="<?= url('/admin/academic/fields') ?>" class="location-nav-link">Fields of Study</a>
    <a href="<?= url('/admin/academic/degrees') ?>" class="location-nav-link">Degrees</a>
    <a href="<?= url('/admin/academic/funding') ?>" class="location-nav-link active">Funding Types</a>
</div>

<div class="location-layout">
    <!-- Quick Add Form -->
    <div>
        <div class="form-card">
            <h2 style="font-size: 1.125rem; font-weight: 700; color: #1e293b; margin-top: 0; margin-bottom: 16px;">Add New Funding Type</h2>
            <form action="<?= url('/admin/academic/funding') ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= Security::csrfToken() ?>">
                
                <div class="form-group">
                    <label class="form-label" for="name">Funding Type Name</label>
                    <input type="text" name="name" id="name" class="form-control" placeholder="e.g. Fully Funded" required>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; margin-top: 12px;">Add Funding Type</button>
            </form>
        </div>
    </div>

    <!-- Data Table -->
    <div>
        <div class="data-table-card">
            <div style="overflow-x: auto;">
                <table class="employees-table" id="funding-datatable" style="width:100%">
                    <thead>
                        <tr>
                            <th>Funding Type Name</th>
                            <th>Status</th>
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
    ScholarMatchDataTable('#funding-datatable', {
        ajax: {
            url: '<?= url("/admin/academic/funding/data") ?>',
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
                data: 'status',
                render: function(data, type, row) {
                    var cls = data === 'active' ? 'active' : 'inactive';
                    return '<span class="status-badge ' + cls + '">' + data + '</span>';
                }
            },
            {
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    var editUrl = '<?= url("/admin/academic/funding") ?>' + '/' + row.record_id + '/edit';
                    return '<a href="' + editUrl + '" class="action-link">Edit</a>' +
                           '<a href="javascript:void(0)" onclick="deleteFunding(\'' + row.record_id + '\')" class="action-link danger">Delete</a>';
                }
            }
        ]
    });
});

function deleteFunding(recordId) {
    if (!confirm('Are you sure you want to delete this funding type?')) {
        return;
    }
    const formData = new FormData();
    formData.append('csrf_token', '<?= Security::csrfToken() ?>');

    fetch('<?= url("/admin/academic/funding") ?>/' + recordId + '/delete', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            $('#funding-datatable').DataTable().ajax.reload(null, false);
        } else {
            alert(data.error || 'Failed to delete funding type.');
        }
    })
    .catch(err => {
        console.error(err);
        alert('An error occurred during communication.');
    });
}
</script>

<?php include ROOT_PATH . '/app/Views/layouts/admin_footer.php'; ?>
