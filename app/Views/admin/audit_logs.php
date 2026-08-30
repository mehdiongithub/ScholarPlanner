<?php include ROOT_PATH . '/app/Views/layouts/admin_header.php'; ?>

<style>
    .filter-card {
        background: #fff;
        border: 1px solid var(--border-slate-200);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 24px;
    }
    .filter-form {
        display: grid;
        grid-template-columns: 2fr 1fr auto;
        gap: 16px;
        align-items: end;
    }
    @media (max-width: 768px) {
        .filter-form {
            grid-template-columns: 1fr;
        }
    }
    .data-table-card {
        background: #fff;
        border: 1px solid var(--border-slate-200);
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }
    .logs-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.875rem;
    }
    .logs-table th, .logs-table td {
        padding: 12px 14px;
        text-align: left;
        border-bottom: 1px solid var(--border-slate-200);
    }
    .logs-table th {
        background-color: var(--bg-slate-50);
        font-weight: 600;
        color: #475569;
        font-size: 0.8125rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .logs-table tbody tr:hover {
        background-color: #fafafb;
    }
</style>

<div style="margin-bottom: 24px;">
    <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0; color: #1e293b;">System Audit Trails</h1>
    <p style="margin: 4px 0 0 0; color: #64748b; font-size: 0.875rem;">Immutable logging of administrative events and actions for security tracking.</p>
</div>

<!-- Filters -->
<div class="filter-card">
    <form class="filter-form">
        <div class="form-group" style="margin: 0;">
            <label class="form-label" style="font-size: 0.8125rem; font-weight: 600;">Search Logs</label>
            <input type="text" name="search" class="form-control" placeholder="Search by email, action, metadata..." value="<?= e($search ?? '') ?>">
        </div>

        <div class="form-group" style="margin: 0;">
            <label class="form-label" style="font-size: 0.8125rem; font-weight: 600;">Module</label>
            <select name="module" class="form-control">
                <option value="">All Modules</option>
                <?php foreach ($modules as $mod): ?>
                    <option value="<?= e($mod) ?>" <?= ($selectedModule ?? '') === $mod ? 'selected' : '' ?>><?= e(strtoupper($mod)) ?></option>
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

<!-- Logs List -->
<div class="data-table-card">
    <div style="overflow-x: auto;">
        <table class="logs-table" id="logs-datatable" style="width:100%">
            <thead>
                <tr>
                    <th>Actor / User</th>
                    <th>Action Executed</th>
                    <th>Module Group</th>
                    <th>Resource Target</th>
                    <th>Client IP & UA</th>
                    <th>Logged At</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>

<script>
$(document).ready(function() {
    var table = $('#logs-datatable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: '<?= url("/admin/audit-logs/data") ?>',
            type: 'GET',
            data: function(d) {
                d.search_query = $('input[name="search"]').val();
                d.module = $('select[name="module"]').val();
            }
        },
        columns: [
            { 
                data: 'actor',
                render: function(data, type, row) {
                    var email = row.actor_email ? row.actor_email : 'System / CLI';
                    return '<strong>' + data + '</strong><div style="font-size: 0.75rem; color: #64748b;">' + email + '</div>';
                }
            },
            { 
                data: 'action',
                render: function(data, type, row) {
                    return '<code style="background-color: var(--bg-slate-100); padding: 4px 8px; border-radius: 6px; font-weight: 600; color: #b91c1c; font-size: 0.75rem;">' + data + '</code>';
                }
            },
            { 
                data: 'module',
                render: function(data, type, row) {
                    return '<span style="font-weight: 600; text-transform: uppercase; font-size: 0.75rem; color: #475569;">' + data + '</span>';
                }
            },
            { 
                data: 'resource_type',
                render: function(data, type, row) {
                    var resId = row.resource_id ? row.resource_id : '-';
                    return '<span style="font-size: 0.8125rem; font-weight: 500; color: #334155;">' + data + ' #' + resId + '</span>';
                }
            },
            { 
                data: 'ip_address',
                render: function(data, type, row) {
                    var ip = data ? data : '127.0.0.1';
                    var ua = row.user_agent ? row.user_agent : '';
                    return '<div style="font-size: 0.8125rem; font-weight: 600; color: #475569;">' + ip + '</div>' +
                           '<div style="font-size: 0.6875rem; color: #94a3b8; max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="' + ua + '">' + ua + '</div>';
                }
            },
            { 
                data: 'created_at',
                render: function(data, type, row) {
                    if (!data) return '';
                    var date = new Date(data);
                    return date.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' }) + ' ' + 
                           date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
                }
            }
        ]
    });

    // Handle filter form submission
    $('.filter-form').on('submit', function(e) {
        e.preventDefault();
        table.draw();
    });
});
</script>

<?php include ROOT_PATH . '/app/Views/layouts/admin_footer.php'; ?>
