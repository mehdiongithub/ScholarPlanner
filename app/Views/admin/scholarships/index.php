<?php include ROOT_PATH . '/app/Views/layouts/admin_header.php'; ?>

<style>
    .thumb-img {
        width: 60px;
        height: 38px;
        object-fit: cover;
        border-radius: 6px;
        border: 1px solid var(--border-slate-200);
        background-color: #f8fafc;
    }
    .status-badge.draft {
        background-color: #fef3c7;
        color: #b45309;
        border: 1px solid #fde68a;
    }
    .status-badge.published {
        background-color: #dcfce7;
        color: #15803d;
        border: 1px solid #bbf7d0;
    }
    .status-badge.archived {
        background-color: #f1f5f9;
        color: #64748b;
        border: 1px solid #e2e8f0;
    }
</style>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <div>
        <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0; color: #1e293b;">Scholarship Registry</h1>
        <p style="margin: 4px 0 0 0; color: #64748b; font-size: 0.875rem;">Create, edit, duplicate, publish or unpublish scholarship opportunity listings.</p>
    </div>
    <a href="<?= url('/admin/scholarships/create') ?>" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px;">
        <i data-lucide="plus-circle" style="width: 16px; height: 16px;"></i>
        <span>Add Scholarship</span>
    </a>
</div>

<!-- Filters -->
<div class="filter-card" style="margin-bottom: 24px;">
    <form class="filter-form" id="scholarship-filter-form">
        <div class="form-group" style="margin: 0;">
            <label class="form-label" style="font-size: 0.8125rem; font-weight: 600;">Search Listings</label>
            <input type="text" name="search" id="filter_search" class="form-control" placeholder="Search by title, provider, slug..." value="<?= e($search ?? '') ?>">
        </div>

        <div class="form-group" style="margin: 0;">
            <label class="form-label" style="font-size: 0.8125rem; font-weight: 600;">Location Scope</label>
            <select name="country_id" id="filter_country_id" class="form-control">
                <option value="">All Countries</option>
                <option value="multi" <?= ($selectedCountry ?? $countryId ?? '') === 'multi' ? 'selected' : '' ?>>Multi-Country Scope</option>
                <?php foreach (($countries ?? []) as $c): ?>
                    <option value="<?= e($c['id']) ?>" <?= ($selectedCountry ?? $countryId ?? '') == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group" style="margin: 0;">
            <label class="form-label" style="font-size: 0.8125rem; font-weight: 600;">Funding Style</label>
            <select name="funding_type" id="filter_funding_type" class="form-control">
                <option value="">All Types</option>
                <?php if (!empty($fundings)): ?>
                    <?php foreach ($fundings as $f): ?>
                        <option value="<?= e($f['name']) ?>" <?= ($selectedFunding ?? $funding ?? '') === $f['name'] ? 'selected' : '' ?>><?= e($f['name']) ?></option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <option value="Fully Funded" <?= ($selectedFunding ?? $funding ?? '') === 'Fully Funded' ? 'selected' : '' ?>>Fully Funded</option>
                    <option value="Partial Funding" <?= ($selectedFunding ?? $funding ?? '') === 'Partial Funding' ? 'selected' : '' ?>>Partially Funded</option>
                    <option value="Tuition Only" <?= ($selectedFunding ?? $funding ?? '') === 'Tuition Only' ? 'selected' : '' ?>>Tuition Only</option>
                    <option value="Stipend Only" <?= ($selectedFunding ?? $funding ?? '') === 'Stipend Only' ? 'selected' : '' ?>>Stipend Only</option>
                <?php endif; ?>
            </select>
        </div>

        <div class="form-group" style="margin: 0;">
            <label class="form-label" style="font-size: 0.8125rem; font-weight: 600;">Publication Status</label>
            <select name="status" id="filter_status" class="form-control">
                <option value="">All Statuses (Draft & Published)</option>
                <option value="published" <?= ($selectedStatus ?? $status ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
                <option value="draft" <?= ($selectedStatus ?? $status ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                <option value="archived" <?= ($selectedStatus ?? $status ?? '') === 'archived' ? 'selected' : '' ?>>Archived</option>
            </select>
        </div>

        <div style="display: flex; gap: 8px;">
            <button type="button" id="btn-clear-filters" class="btn btn-secondary" style="padding: 10px 14px;" title="Clear Filters">Clear</button>
            <button type="submit" class="btn btn-primary" style="padding: 10px 18px; flex-grow: 1;">Filter</button>
        </div>
    </form>
</div>

<!-- Alert notifications -->
<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success" style="margin-bottom: 24px; padding: 12px 16px; border-radius: 8px; font-size: 0.875rem;">
        <?= e($_GET['success']) ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger" style="margin-bottom: 24px; padding: 12px 16px; border-radius: 8px; font-size: 0.875rem; background-color: #fef2f2; color: #b91c1c; border: 1px solid #fee2e2;">
        <?= e($_GET['error']) ?>
    </div>
<?php endif; ?>

<!-- Table Card -->
<div class="data-table-card">
    <div style="overflow-x: auto;">
        <table class="sch-table employees-table" id="scholarships-datatable" style="width:100%">
            <thead>
                <tr>
                    <th style="width: 70px;">Image</th>
                    <th>Scholarship Details</th>
                    <th>Provider</th>
                    <th>Country</th>
                    <th>Funding</th>
                    <th style="width: 100px;">Status</th>
                    <th style="width: 110px;">Deadline</th>
                    <th style="width: 250px; text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>

<script>
$(document).ready(function() {
    var table = ScholarPlannerDataTable('#scholarships-datatable', {
        ajax: {
            url: '<?= url("/admin/scholarships/data") ?>',
            type: 'GET',
            data: function(d) {
                d.search_query = $('#filter_search').val();
                d.country_id = $('#filter_country_id').val();
                d.funding_type = $('#filter_funding_type').val();
                d.status = $('#filter_status').val();
            }
        },
        order: [[1, 'asc']],
        columns: [
            {
                data: 'cover_image',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    var defaultImg = '<?= url("/assets/images/default-scholarship.svg") ?>';
                    var src = data ? '<?= url("/") ?>' + '/' + data : defaultImg;
                    src = src.replace(/\/\/+/g, '/').replace(':/', '://');
                    return '<img src="' + src + '" class="thumb-img" alt="Cover">';
                }
            },
            {
                data: 'title',
                render: function(data, type, row) {
                    var safeTitle = $('<div>').text(data || '').html();
                    var safeSlug = $('<div>').text(row.slug || '').html();
                    return '<strong style="color: #0f172a;">' + safeTitle + '</strong><div style="font-size: 0.75rem; color: #64748b; margin-top: 2px;">Slug: <code style="background: #f1f5f9; padding: 1px 4px; border-radius: 4px;">' + safeSlug + '</code></div>';
                }
            },
            { 
                data: 'provider_name',
                render: function(data, type, row) {
                    return $('<div>').text(data || '').html();
                }
            },
            { 
                data: 'country_name',
                render: function(data, type, row) {
                    return data ? $('<div>').text(data).html() : '<span style="color: #64748b; font-style: italic;">Multi-Country</span>';
                }
            },
            { 
                data: 'funding_type',
                render: function(data, type, row) {
                    return $('<div>').text(data || '-').html();
                }
            },
            { 
                data: 'status',
                render: function(data, type, row) {
                    var statusVal = (data || 'draft').toLowerCase();
                    var cls = 'draft';
                    var label = 'Draft';
                    if (statusVal === 'published') {
                        cls = 'published';
                        label = 'Published';
                    } else if (statusVal === 'archived') {
                        cls = 'archived';
                        label = 'Archived';
                    } else {
                        cls = 'draft';
                        label = 'Draft';
                    }
                    return '<span class="status-badge ' + cls + '">' + label + '</span>';
                }
            },
            { 
                data: 'application_deadline',
                render: function(data, type, row) {
                    if (data) {
                        var date = new Date(data);
                        return date.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
                    }
                    return '<span style="color: #94a3b8;">Rolling</span>';
                }
            },
            {
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    var publicUrl = '<?= url("/scholarships") ?>' + '/' + encodeURIComponent(row.slug);
                    var editUrl = '<?= url("/admin/scholarships") ?>' + '/' + row.record_id + '/edit';
                    
                    var duplicateForm = '<form action="<?= url("/admin/scholarships") ?>/' + row.record_id + '/duplicate" method="POST" style="display:inline;">' +
                        '<input type="hidden" name="csrf_token" value="<?= \App\Helpers\Security::csrfToken() ?>">' +
                        '<button type="submit" class="action-link" style="background:none; border:none; cursor:pointer; font-family:inherit; color: #7c3aed; padding: 2px 4px;">Duplicate</button>' +
                        '</form>';

                    var pubForm = '';
                    if (row.status !== 'published') {
                        pubForm = '<form action="<?= url("/admin/scholarships") ?>/' + row.record_id + '/publish" method="POST" style="display:inline;">' +
                            '<input type="hidden" name="csrf_token" value="<?= \App\Helpers\Security::csrfToken() ?>">' +
                            '<button type="submit" class="action-link" style="background:none; border:none; cursor:pointer; font-family:inherit; color: #16a34a; padding: 2px 4px; font-weight: 600;">Publish</button>' +
                            '</form>';
                    } else {
                        pubForm = '<form action="<?= url("/admin/scholarships") ?>/' + row.record_id + '/unpublish" method="POST" style="display:inline;">' +
                            '<input type="hidden" name="csrf_token" value="<?= \App\Helpers\Security::csrfToken() ?>">' +
                            '<button type="submit" class="action-link" style="background:none; border:none; cursor:pointer; font-family:inherit; color: #ca8a04; padding: 2px 4px;">Unpublish</button>' +
                            '</form>';
                    }

                    var archiveForm = '';
                    if (row.status !== 'archived') {
                        archiveForm = '<form action="<?= url("/admin/scholarships") ?>/' + row.record_id + '/archive" method="POST" style="display:inline;">' +
                            '<input type="hidden" name="csrf_token" value="<?= \App\Helpers\Security::csrfToken() ?>">' +
                            '<button type="submit" class="action-link" style="background:none; border:none; cursor:pointer; font-family:inherit; color: #64748b; padding: 2px 4px;">Archive</button>' +
                            '</form>';
                    }

                    var safeTitle = $('<div>').text(row.title || '').html();
                    var deleteForm = '<form action="<?= url("/admin/scholarships") ?>/' + row.record_id + '/delete" method="POST" style="display:inline;">' +
                        '<input type="hidden" name="csrf_token" value="<?= \App\Helpers\Security::csrfToken() ?>">' +
                        '<button type="button" class="action-link danger btn-delete-scholarship" style="background:none; border:none; cursor:pointer; font-family:inherit; padding: 2px 4px;" data-title="' + safeTitle + '">Delete</button>' +
                        '</form>';

                    return '<div style="display: flex; gap: 6px; justify-content: center; align-items: center; flex-wrap: wrap;">' +
                           '<a href="' + publicUrl + '" target="_blank" class="action-link" style="padding: 2px 4px;">View</a>' +
                           '<a href="' + editUrl + '" class="action-link" style="color: var(--primary); padding: 2px 4px; font-weight: 600;">Edit</a>' +
                           duplicateForm +
                           pubForm +
                           archiveForm +
                           deleteForm +
                           '</div>';
                }
            }
        ],
        drawCallback: function() {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }
    });

    // Delete confirmation handler
    $(document).on('click', '.btn-delete-scholarship', function(e) {
        e.preventDefault();
        adminConfirmDelete(this, 'scholarship', $(this).attr('data-title'));
    });

    // Handle filter form submission
    $('#scholarship-filter-form').on('submit', function(e) {
        e.preventDefault();
        table.draw();
    });

    // Instant filter on select change
    $('#scholarship-filter-form select').on('change', function() {
        table.draw();
    });

    // Handle Clear Filters button
    $('#btn-clear-filters').on('click', function(e) {
        e.preventDefault();
        $('#filter_search').val('');
        $('#filter_country_id').val('');
        $('#filter_funding_type').val('');
        $('#filter_status').val('');
        table.draw();
    });
});
</script>

<?php include ROOT_PATH . '/app/Views/layouts/admin_footer.php'; ?>
