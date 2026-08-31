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
</style>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <div>
        <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0; color: #1e293b;">Scholarship Registry</h1>
        <p style="margin: 4px 0 0 0; color: #64748b; font-size: 0.875rem;">Create, edit, duplicate, publish or unpublish scholarship opportunity listings.</p>
    </div>
    <a href="<?= url('/admin/scholarships/create') ?>" class="btn btn-primary">
        <i data-lucide="plus-circle" style="width: 16px; height: 16px;"></i>
        <span>Add Scholarship</span>
    </a>
</div>

<!-- Filters -->
<div class="filter-card">
    <form class="filter-form">
        <div class="form-group" style="margin: 0;">
            <label class="form-label" style="font-size: 0.8125rem; font-weight: 600;">Search Listings</label>
            <input type="text" name="search" class="form-control" placeholder="Search by title, provider..." value="<?= e($search ?? '') ?>">
        </div>

        <div class="form-group" style="margin: 0;">
            <label class="form-label" style="font-size: 0.8125rem; font-weight: 600;">Location Scope</label>
            <select name="country_id" class="form-control">
                <option value="">All Countries</option>
                <option value="multi" <?= ($selectedCountry ?? '') === 'multi' ? 'selected' : '' ?>>Multi-Country Scope</option>
                <?php foreach ($countries as $c): ?>
                    <option value="<?= e($c['id']) ?>" <?= ($selectedCountry ?? '') == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group" style="margin: 0;">
            <label class="form-label" style="font-size: 0.8125rem; font-weight: 600;">Funding Style</label>
            <select name="funding_type" class="form-control">
                <option value="">All Types</option>
                <option value="full" <?= ($selectedFunding ?? '') === 'full' ? 'selected' : '' ?>>Fully Funded</option>
                <option value="partial" <?= ($selectedFunding ?? '') === 'partial' ? 'selected' : '' ?>>Partially Funded</option>
                <option value="tuition" <?= ($selectedFunding ?? '') === 'tuition' ? 'selected' : '' ?>>Tuition Waiver</option>
            </select>
        </div>

        <div class="form-group" style="margin: 0;">
            <label class="form-label" style="font-size: 0.8125rem; font-weight: 600;">Publication Status</label>
            <select name="status" class="form-control">
                <option value="">All Statuses</option>
                <option value="published" <?= ($selectedStatus ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
                <option value="draft" <?= ($selectedStatus ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
            </select>
        </div>

        <div style="display: flex; gap: 8px;">
            <a href="<?= url('/admin/scholarships') ?>" class="btn btn-secondary" style="padding: 10px 14px;" title="Clear Filters">Clear</a>
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
        <table class="sch-table" id="scholarships-datatable" style="width:100%">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Scholarship Details</th>
                    <th>Provider</th>
                    <th>Country</th>
                    <th>Funding</th>
                    <th>Status</th>
                    <th>Deadline</th>
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
    var table = ScholarMatchDataTable('#scholarships-datatable', {
        ajax: {
            url: '<?= url("/admin/scholarships/data") ?>',
            type: 'GET',
            data: function(d) {
                d.search_query = $('input[name="search"]').val();
                d.country_id = $('select[name="country_id"]').val();
                d.funding_type = $('select[name="funding_type"]').val();
                d.status = $('select[name="status"]').val();
            }
        },
        columns: [
            {
                data: 'cover_image',
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
                    return '<strong>' + data + '</strong><div style="font-size: 0.75rem; color: #64748b; margin-top: 2px;">Slug: <code>' + row.slug + '</code></div>';
                }
            },
            { data: 'provider_name' },
            { 
                data: 'country_name',
                render: function(data, type, row) {
                    return data ? data : 'Multi-Country';
                }
            },
            { data: 'funding_type' },
            { 
                data: 'status',
                render: function(data, type, row) {
                    var cls = data === 'published' ? 'active' : (data === 'draft' ? 'pending' : 'suspended');
                    return '<span class="status-badge ' + cls + '">' + data + '</span>';
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
                    var publicUrl = '<?= url("/scholarships") ?>' + '/' + row.slug;
                    var editUrl = '<?= url("/admin/scholarships") ?>' + '/' + row.record_id + '/edit';
                    
                    var duplicateForm = '<form action="<?= url("/admin/scholarships") ?>/' + row.record_id + '/duplicate" method="POST" style="display:inline;">' +
                        '<input type="hidden" name="csrf_token" value="<?= \App\Helpers\Security::csrfToken() ?>">' +
                        '<button type="submit" class="action-link" style="background:none; border:none; cursor:pointer; font-family:inherit; color: #7c3aed;">Duplicate</button>' +
                        '</form>';

                    var pubForm = '';
                    if (row.status !== 'published') {
                        pubForm = '<form action="<?= url("/admin/scholarships") ?>/' + row.record_id + '/publish" method="POST" style="display:inline;">' +
                            '<input type="hidden" name="csrf_token" value="<?= \App\Helpers\Security::csrfToken() ?>">' +
                            '<button type="submit" class="action-link" style="background:none; border:none; cursor:pointer; font-family:inherit; color: #16a34a;">Publish</button>' +
                            '</form>';
                    } else {
                        pubForm = '<form action="<?= url("/admin/scholarships") ?>/' + row.record_id + '/unpublish" method="POST" style="display:inline;">' +
                            '<input type="hidden" name="csrf_token" value="<?= \App\Helpers\Security::csrfToken() ?>">' +
                            '<button type="submit" class="action-link" style="background:none; border:none; cursor:pointer; font-family:inherit; color: #ca8a04;">Unpublish</button>' +
                            '</form>';
                    }

                    var archiveForm = '';
                    if (row.status !== 'archived') {
                        archiveForm = '<form action="<?= url("/admin/scholarships") ?>/' + row.record_id + '/archive" method="POST" style="display:inline;">' +
                            '<input type="hidden" name="csrf_token" value="<?= \App\Helpers\Security::csrfToken() ?>">' +
                            '<button type="submit" class="action-link" style="background:none; border:none; cursor:pointer; font-family:inherit; color: #64748b;">Archive</button>' +
                            '</form>';
                    }

                    var deleteForm = '<form action="<?= url("/admin/scholarships") ?>/' + row.record_id + '/delete" method="POST" onsubmit="return confirm(\'Are you sure you want to permanently delete this scholarship?\');" style="display:inline;">' +
                        '<input type="hidden" name="csrf_token" value="<?= \App\Helpers\Security::csrfToken() ?>">' +
                        '<button type="submit" class="action-link danger" style="background:none; border:none; cursor:pointer; font-family:inherit;">Delete</button>' +
                        '</form>';

                    return '<div style="display: flex; gap: 6px;">' +
                           '<a href="' + publicUrl + '" target="_blank" class="action-link">View</a>' +
                           '<a href="' + editUrl + '" class="action-link" style="color: var(--primary);">Edit</a>' +
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

    // Handle filter form submission
    $('.filter-form').on('submit', function(e) {
        e.preventDefault();
        table.draw();
    });
});
</script>

<?php include ROOT_PATH . '/app/Views/layouts/admin_footer.php'; ?>
