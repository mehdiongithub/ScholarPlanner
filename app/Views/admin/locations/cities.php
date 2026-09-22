<?php include ROOT_PATH . '/app/Views/layouts/admin_header.php'; ?>

<div style="margin-bottom: 24px;">
    <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0; color: #1e293b;">Location Management</h1>
    <p style="margin: 4px 0 0 0; color: #64748b; font-size: 0.875rem;">Manage Geographic Countries, States/Provinces, and Cities lookups.</p>
</div>

<!-- Navigation Tabs -->
<div class="location-nav">
    <a href="<?= url('/admin/locations/countries') ?>" class="location-nav-link">Countries</a>
    <a href="<?= url('/admin/locations/states') ?>" class="location-nav-link">States / Provinces</a>
    <a href="<?= url('/admin/locations/cities') ?>" class="location-nav-link active">Cities</a>
</div>

<div class="location-layout">
    <!-- Quick Add Form -->
    <div>
        <div class="form-card">
            <h2 style="font-size: 1.125rem; font-weight: 700; color: #1e293b; margin-top: 0; margin-bottom: 16px;">Add New City</h2>
            <form action="<?= url('/admin/locations/cities') ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Security::csrfToken() ?>">
                
                <div class="form-group">
                    <label class="form-label" for="name">City Name</label>
                    <input type="text" name="name" id="name" class="form-control" placeholder="e.g. Lahore" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="country_select">Country</label>
                    <select id="country_select" class="form-control" required>
                        <option value="">-- Choose Country --</option>
                        <?php foreach ($countries as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="state_select">State / Province</label>
                    <select name="state_id" id="state_select" class="form-control" required disabled>
                        <option value="">-- Select Country First --</option>
                    </select>
                </div>

                <button type="submit" id="addCitySubmit" class="btn btn-primary" style="width: 100%; justify-content: center; margin-top: 12px;" disabled>Add City</button>
            </form>
        </div>
    </div>

    <!-- Data Table -->
    <div>
        <div class="data-table-card">
            <div style="overflow-x: auto;">
                <table class="employees-table" id="cities-datatable" style="width:100%">
                    <thead>
                        <tr>
                            <th>City Name</th>
                            <th>Parent State / Province</th>
                            <th>Country</th>
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
    ScholarPlannerDataTable('#cities-datatable', {
        ajax: {
            url: '<?= url("/admin/locations/cities/data") ?>',
            type: 'GET'
        },
        columns: [
            { 
                data: 'name',
                render: function(data, type, row) {
                    return '<strong>' + data + '</strong>';
                }
            },
            { data: 'state_name' },
            { data: 'country_name' },
            {
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    var editUrl = '<?= url("/admin/locations/cities") ?>' + '/' + row.record_id + '/edit';
                    var safeName = $('<div>').text(row.name || '').html();
                    return '<a href="' + editUrl + '" class="action-link">Edit</a>' +
                           '<a href="javascript:void(0)" class="action-link danger btn-delete-city" data-id="' + row.record_id + '" data-name="' + safeName + '">Delete</a>';
                }
            }
        ]
    });

    $(document).on('click', '.btn-delete-city', function(e) {
        e.preventDefault();
        var recordId = $(this).attr('data-id');
        var name = $(this).attr('data-name');
        deleteCity(recordId, name);
    });
});

function deleteCity(recordId, name) {
    adminConfirm({
        title: 'Delete City',
        message: 'Are you sure you want to delete city ' + (name ? '<strong>"' + adminEscapeHtml(name) + '"</strong>' : 'this record') + '?',
        subtext: 'This action cannot be undone.',
        confirmText: 'Yes, Delete',
        confirmClass: 'btn-danger',
        icon: 'trash-2'
    }, function() {
        const formData = new FormData();
        formData.append('csrf_token', '<?= \App\Helpers\Security::csrfToken() ?>');

        fetch('<?= url("/admin/locations/cities") ?>/' + recordId + '/delete', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                $('#cities-datatable').DataTable().ajax.reload(null, false);
            } else {
                adminConfirm({
                    title: 'Delete Failed',
                    message: data.error || 'Failed to delete city.',
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

// Cascading state selection script
const countrySelect = document.getElementById('country_select');
const stateSelect = document.getElementById('state_select');
const submitBtn = document.getElementById('addCitySubmit');

if (countrySelect && stateSelect) {
    countrySelect.addEventListener('change', function() {
        const countryId = this.value;
        stateSelect.innerHTML = '<option value="">-- Loading States... --</option>';
        stateSelect.disabled = true;
        submitBtn.disabled = true;

        if (countryId === '') {
            stateSelect.innerHTML = '<option value="">-- Select Country First --</option>';
            return;
        }

        fetch('<?= url("/api/states") ?>?country_id=' + countryId)
            .then(res => res.json())
            .then(data => {
                stateSelect.innerHTML = '<option value="">-- Choose State --</option>';
                if (data && data.length > 0) {
                    data.forEach(state => {
                        const opt = document.createElement('option');
                        opt.value = state.id;
                        opt.innerText = state.name;
                        stateSelect.appendChild(opt);
                    });
                    stateSelect.disabled = false;
                    submitBtn.disabled = false;
                } else {
                    stateSelect.innerHTML = '<option value="">-- No States Found --</option>';
                }
            })
            .catch(err => {
                console.error(err);
                stateSelect.innerHTML = '<option value="">-- Error Loading States --</option>';
            });
    });
}
</script>

<?php include ROOT_PATH . '/app/Views/layouts/admin_footer.php'; ?>
