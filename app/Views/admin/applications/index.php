<?php include ROOT_PATH . '/app/Views/layouts/admin_header.php'; ?>

<style>
    .badge-status {
        padding: 4px 10px;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        display: inline-block;
    }
    .status-interested { background: #eff6ff; color: #1d4ed8; }
    .status-planning { background: #fef3c7; color: #d97706; }
    .status-documents_pending { background: #fff7ed; color: #ea580c; }
    .status-ready_to_apply { background: #ecfdf5; color: #059669; }
    .status-applied { background: #f5f3ff; color: #7c3aed; }
    .status-interview { background: #f0fdfa; color: #0d9488; }
    .status-accepted { background: #f0fdf4; color: #16a34a; }
    .status-rejected { background: #fef2f2; color: #dc2626; }
    .status-withdrawn { background: #f1f5f9; color: #475569; }
</style>

<div style="margin-bottom: 24px;">
    <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0; color: #1e293b;">Manage Applications</h1>
    <p style="margin: 4px 0 0 0; color: #64748b; font-size: 0.875rem;">Review, evaluate, and transition tracking statuses for scholarship applications</p>
</div>

<!-- Success message -->
<?php if (isset($_SESSION['admin_app_success'])): ?>
    <div class="alert alert-success" style="margin-bottom: 24px; padding: 12px 16px; border-radius: 8px; font-size: 0.875rem;">
        <?= e($_SESSION['admin_app_success']) ?>
        <?php unset($_SESSION['admin_app_success']); ?>
    </div>
<?php endif; ?>

<!-- Filters -->
<div class="filter-card">
    <form class="filter-form">
        <div class="form-group" style="margin: 0;">
            <label class="form-label" style="font-size: 0.8125rem; font-weight: 600;">Search Applicants / Scholarships</label>
            <input type="text" name="search" class="form-control" placeholder="Search by name, email or title..." value="<?= e($search ?? '') ?>">
        </div>

        <div class="form-group" style="margin: 0;">
            <label class="form-label" style="font-size: 0.8125rem; font-weight: 600;">Status</label>
            <select name="status" class="form-control">
                <option value="">All Statuses</option>
                <option value="interested" <?= ($selectedStatus ?? '') === 'interested' ? 'selected' : '' ?>>Interested</option>
                <option value="planning" <?= ($selectedStatus ?? '') === 'planning' ? 'selected' : '' ?>>Planning</option>
                <option value="documents_pending" <?= ($selectedStatus ?? '') === 'documents_pending' ? 'selected' : '' ?>>Documents Pending</option>
                <option value="ready_to_apply" <?= ($selectedStatus ?? '') === 'ready_to_apply' ? 'selected' : '' ?>>Ready to Apply</option>
                <option value="applied" <?= ($selectedStatus ?? '') === 'applied' ? 'selected' : '' ?>>Applied</option>
                <option value="interview" <?= ($selectedStatus ?? '') === 'interview' ? 'selected' : '' ?>>Interviewing</option>
                <option value="accepted" <?= ($selectedStatus ?? '') === 'accepted' ? 'selected' : '' ?>>Accepted</option>
                <option value="rejected" <?= ($selectedStatus ?? '') === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                <option value="withdrawn" <?= ($selectedStatus ?? '') === 'withdrawn' ? 'selected' : '' ?>>Withdrawn</option>
            </select>
        </div>

        <div class="form-group" style="margin: 0;">
            <label class="form-label" style="font-size: 0.8125rem; font-weight: 600;">Scholarship Listing</label>
            <select name="scholarship_id" class="form-control">
                <option value="">All Scholarships</option>
                <?php foreach ($scholarships as $s): ?>
                    <option value="<?= e($s['id']) ?>" <?= ($selectedScholarship ?? '') == $s['id'] ? 'selected' : '' ?>><?= e($s['title']) ?></option>
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

<!-- List Grid -->
<div class="data-table-card">
    <div style="overflow-x: auto;">
        <table class="admin-table" id="applications-datatable" style="width:100%">
            <thead>
                <tr>
                    <th>Applicant Details</th>
                    <th>Scholarship Opp</th>
                    <th>Tracking Status</th>
                    <th>Date Tracked</th>
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
    var table = ScholarMatchDataTable('#applications-datatable', {
        ajax: {
            url: '<?= url("/admin/applications/data") ?>',
            type: 'GET',
            data: function(d) {
                d.search_query = $('input[name="search"]').val();
                d.status = $('select[name="status"]').val();
                d.scholarship_id = $('select[name="scholarship_id"]').val();
            }
        },
        columns: [
            {
                data: 'student_name',
                render: function(data, type, row) {
                    return '<div style="font-weight: 600; color: var(--text-900);">' + data + '</div>' +
                           '<div style="font-size: 0.75rem; color: var(--text-500);">' + row.user_email + '</div>';
                }
            },
            {
                data: 'scholarship_title',
                render: function(data, type, row) {
                    var deadline = row.application_deadline ? row.application_deadline : 'Rolling';
                    return '<div style="font-weight: 500; color: var(--text-800);">' + data + '</div>' +
                           '<div style="font-size: 0.75rem; color: var(--text-500);">Deadline: ' + deadline + '</div>';
                }
            },
            {
                data: 'status',
                render: function(data, type, row) {
                    var cleanStatus = data.replace(/_/g, ' ');
                    return '<span class="badge-status status-' + data + '">' + cleanStatus + '</span>';
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
                    var reviewUrl = '<?= url("/admin/applications") ?>' + '/' + row.record_id;
                    return '<a href="' + reviewUrl + '" class="btn-action">' +
                           '<i data-lucide="check-square" style="width: 14px; height: 14px;"></i>' +
                           '<span>Review</span>' +
                           '</a>';
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
