<?php include ROOT_PATH . '/app/Views/layouts/admin_header.php'; ?>

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
    ScholarMatchDataTable('#subscriptions-datatable', {
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
