<?php include ROOT_PATH . '/app/Views/layouts/admin_header.php'; ?>

<style>
    .data-table-card {
        background: #fff;
        border: 1px solid var(--border-slate-200);
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }
    .employees-table {
        width: 100%;
        border-collapse: collapse;
    }
    .employees-table th, .employees-table td {
        padding: 14px 16px;
        text-align: left;
        border-bottom: 1px solid var(--border-slate-200);
    }
    .employees-table th {
        background-color: var(--bg-slate-50);
        font-weight: 600;
        color: #475569;
        font-size: 0.8125rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    /* Badge styles */
    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    .status-badge.active { background-color: #dcfce7; color: #15803d; }
    .status-badge.suspended { background-color: #fee2e2; color: #b91c1c; }
</style>

<div style="margin-bottom: 24px;">
    <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0; color: #1e293b;">Student Subscriptions</h1>
    <p style="margin: 4px 0 0 0; color: #64748b; font-size: 0.875rem;">Monitor student premium subscriptions and active membership cycles.</p>
</div>

<div class="data-table-card">
    <div style="overflow-x: auto;">
        <table class="employees-table" id="subscriptions-datatable" style="width:100%">
            <thead>
                <tr>
                    <th>Student Details</th>
                    <th>Subscribed Plan</th>
                    <th>Status</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#subscriptions-datatable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: '<?= url("/admin/subscriptions/data") ?>',
            type: 'GET'
        },
        columns: [
            { 
                data: 'student_name',
                render: function(data, type, row) {
                    return '<strong>' + data + '</strong><div style="font-size: 0.75rem; color: #64748b;">' + row.email + '</div>';
                }
            },
            { 
                data: 'plan_name',
                render: function(data, type, row) {
                    return '<strong>' + data + '</strong>';
                }
            },
            { 
                data: 'status',
                render: function(data, type, row) {
                    var cls = data === 'active' ? 'active' : 'suspended';
                    return '<span class="status-badge ' + cls + '">' + data + '</span>';
                }
            },
            { 
                data: 'starts_at',
                render: function(data, type, row) {
                    if (!data) return '-';
                    var date = new Date(data);
                    return date.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
                }
            },
            { 
                data: 'ends_at',
                render: function(data, type, row) {
                    if (!data) return 'Ongoing / Lifetime';
                    var date = new Date(data);
                    return date.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
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
            }
        ]
    });
});
</script>

<?php include ROOT_PATH . '/app/Views/layouts/admin_footer.php'; ?>
