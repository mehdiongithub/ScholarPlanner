<?php include ROOT_PATH . '/app/Views/layouts/admin_header.php'; ?>

<style>
    .filter-card {
        background: #fff;
        border: 1px solid var(--border-slate-200);
        border-radius: 12px;
        padding: 20px 24px;
        margin-bottom: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }
    .filter-form {
        display: flex;
        align-items: flex-end;
        flex-wrap: wrap;
        gap: 16px;
    }
    .form-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
        min-width: 180px;
        flex-grow: 1;
    }
    .form-group label {
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--text-600);
        text-transform: uppercase;
    }
    .form-group select, .form-group input {
        padding: 8px 12px;
        border-radius: 6px;
        border: 1px solid var(--border-slate-200);
        font-size: 0.875rem;
        background: white;
        color: var(--text-800);
    }
    .btn-filter-submit {
        background: var(--primary);
        color: white;
        border: none;
        padding: 9px 20px;
        border-radius: 6px;
        font-size: 0.875rem;
        font-weight: 600;
        cursor: pointer;
    }
    .btn-filter-submit:hover {
        background: var(--primary-hover);
    }
    .btn-filter-reset {
        background: #f1f5f9;
        color: var(--text-700);
        border: 1px solid var(--border-slate-200);
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 0.875rem;
        font-weight: 500;
        text-decoration: none;
        text-align: center;
    }
    .btn-filter-reset:hover {
        background: #e2e8f0;
    }
    .records-card {
        background: #fff;
        border: 1px solid var(--border-slate-200);
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }
    .records-table {
        width: 100%;
        border-collapse: collapse;
    }
    .records-table th, .records-table td {
        padding: 14px 16px;
        text-align: left;
        border-bottom: 1px solid var(--border-slate-200);
    }
    .records-table th {
        background-color: var(--bg-slate-50);
        font-weight: 600;
        color: #475569;
        font-size: 0.8125rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: capitalize;
    }
    .badge-uploaded { background: #e0f2fe; color: #0369a1; }
    .badge-approved { background: #d1fae5; color: #065f46; }
    .badge-rejected { background: #fef2f2; color: #991b1b; }
    .actions-cell {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .btn-action-view {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 0.8125rem;
        font-weight: 600;
        text-decoration: none;
        background: var(--primary);
        color: white;
    }
    .btn-action-view:hover {
        background: var(--primary-hover);
    }
    .btn-action-dl {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 0.8125rem;
        font-weight: 500;
        text-decoration: none;
        border: 1px solid var(--border-slate-200);
        background: white;
        color: var(--text-700);
    }
    .btn-action-dl:hover {
        background: #f1f5f9;
    }
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 20px;
        margin-bottom: 24px;
    }
    .stat-card {
        background: #fff;
        border: 1px solid var(--border-slate-200);
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }
    .stat-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;
    }
    .stat-icon {
        width: 36px;
        height: 36px;
        background: #f0f9ff;
        color: var(--primary);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .stat-icon.success { background: #ecfdf5; color: #059669; }
    .stat-icon.warning { background: #fffbeb; color: #d97706; }
    .stat-icon.danger { background: #fef2f2; color: #dc2626; }
    .stat-label {
        font-size: 0.725rem;
        text-transform: uppercase;
        font-weight: 600;
        color: #64748b;
        letter-spacing: 0.05em;
    }
    .stat-value {
        font-size: 1.375rem;
        font-weight: 700;
        color: #1e293b;
    }
</style>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <div>
        <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0; color: #1e293b;">User Uploaded Documents</h1>
        <p style="margin: 4px 0 0 0; color: #64748b; font-size: 0.875rem;">Manage verification process, view uploads, and track submissions.</p>
    </div>
</div>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon"><i data-lucide="files" style="width: 20px; height: 20px;"></i></div>
            <span class="stat-value"><?= e($stats['total_count'] ?? 0) ?></span>
        </div>
        <div class="stat-label">Total Submissions</div>
    </div>
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon warning"><i data-lucide="clock" style="width: 20px; height: 20px;"></i></div>
            <span class="stat-value" style="color: #d97706;"><?= e($stats['pending_count'] ?? 0) ?></span>
        </div>
        <div class="stat-label">Pending Verification</div>
    </div>
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon success"><i data-lucide="check-circle" style="width: 20px; height: 20px;"></i></div>
            <span class="stat-value" style="color: #059669;"><?= e($stats['approved_count'] ?? 0) ?></span>
        </div>
        <div class="stat-label">Approved Documents</div>
    </div>
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon danger"><i data-lucide="x-circle" style="width: 20px; height: 20px;"></i></div>
            <span class="stat-value" style="color: #dc2626;"><?= e($stats['rejected_count'] ?? 0) ?></span>
        </div>
        <div class="stat-label">Rejected / Flagged</div>
    </div>
</div>

<!-- Filters -->
<div class="filter-card">
    <form class="filter-form">
        <div class="form-group" style="margin: 0;">
            <label class="form-label" style="font-size: 0.8125rem; font-weight: 600;">Search Users</label>
            <input type="text" name="search" id="search" class="form-control" placeholder="Search by name, email..." value="<?= e($search ?? '') ?>">
        </div>

        <div class="form-group" style="margin: 0;">
            <label class="form-label" style="font-size: 0.8125rem; font-weight: 600;">Verification Status</label>
            <select name="status" id="status" class="form-control">
                <option value="">All Statuses</option>
                <option value="uploaded" <?= ($selectedStatus ?? '') === 'uploaded' ? 'selected' : '' ?>>Uploaded (Pending Review)</option>
                <option value="approved" <?= ($selectedStatus ?? '') === 'approved' ? 'selected' : '' ?>>Approved</option>
                <option value="rejected" <?= ($selectedStatus ?? '') === 'rejected' ? 'selected' : '' ?>>Rejected</option>
            </select>
        </div>

        <div class="form-group" style="margin: 0;">
            <label class="form-label" style="font-size: 0.8125rem; font-weight: 600;">Document Requirement</label>
            <select name="document_id" id="document_id" class="form-control">
                <option value="">All Types</option>
                <?php foreach ($availableDocs as $doc): ?>
                    <option value="<?= e($doc['id']) ?>" <?= ($selectedDoc ?? '') == $doc['id'] ? 'selected' : '' ?>><?= e($doc['name']) ?></option>
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

<!-- Records List -->
<div class="data-table-card">
    <div style="overflow-x: auto;">
        <table class="admin-table" id="documents-datatable" style="width:100%">
            <thead>
                <tr>
                    <th>Student Details</th>
                    <th>Required Document Type</th>
                    <th>Uploaded Filename</th>
                    <th>Uploaded At</th>
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
    var table = ScholarMatchDataTable('#documents-datatable', {
        ajax: {
            url: '<?= url("/admin/documents/data") ?>',
            type: 'GET',
            data: function(d) {
                d.search_query = $('#search').val();
                d.status = $('#status').val();
                d.document_id = $('#document_id').val();
            }
        },
        columns: [
            {
                data: 'student_name',
                render: function(data, type, row) {
                    return '<div style="font-weight: 600; color: var(--text-800);">' + data + '</div>' +
                           '<div style="font-size: 0.75rem; color: var(--text-500);">' + row.user_email + '</div>';
                }
            },
            {
                data: 'document_name',
                render: function(data, type, row) {
                    return '<div style="font-weight: 500; color: var(--text-700);">' + data + '</div>';
                }
            },
            {
                data: 'original_filename',
                render: function(data, type, row) {
                    return '<div style="font-size: 0.8125rem; word-break: break-all; max-width: 250px;">' + data + '</div>';
                }
            },
            {
                data: 'created_at',
                render: function(data, type, row) {
                    if (!data) return '';
                    var date = new Date(data);
                    return date.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' }) + ' ' + 
                           date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: false });
                }
            },
            {
                data: 'verification_status',
                render: function(data, type, row) {
                    var badgeClass = 'badge-uploaded';
                    var label = 'Under Review';
                    if (data === 'approved') {
                        badgeClass = 'badge-approved';
                        label = 'approved';
                    } else if (data === 'rejected') {
                        badgeClass = 'badge-rejected';
                        label = 'rejected';
                    }
                    return '<span class="badge ' + badgeClass + '">' + label + '</span>';
                }
            },
            {
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    var reviewUrl = '<?= url("/admin/documents") ?>' + '/' + row.record_id;
                    var dlUrl = '<?= url("/admin/documents") ?>' + '/' + row.record_id + '/download';
                    return '<div class="actions-cell" style="justify-content: flex-end;">' +
                           '<a href="' + reviewUrl + '" class="btn-action-view">' +
                           '<i data-lucide="edit-3" style="width: 12px; height: 12px;"></i>' +
                           '<span>Review</span>' +
                           '</a>' +
                           '<a href="' + dlUrl + '" class="btn-action-dl" target="_blank">' +
                           '<i data-lucide="download" style="width: 12px; height: 12px;"></i>' +
                           '<span>Download</span>' +
                           '</a>' +
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
