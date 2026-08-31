<?php include ROOT_PATH . '/app/Views/layouts/admin_header.php'; ?>

<div style="margin-bottom: 24px;">
    <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0; color: #1e293b;">Referral Partner Program</h1>
    <p style="margin: 4px 0 0 0; color: #64748b; font-size: 0.875rem;">Manage professional marketing and referral partners, customize commission and discount parameters, and track payout metrics.</p>
</div>

<div class="referral-layout">
    <!-- Quick Add Form -->
    <div>
        <div class="form-card">
            <h2 style="font-size: 1.125rem; font-weight: 700; color: #1e293b; margin-top: 0; margin-bottom: 16px;">Add Referral Partner</h2>
            <form action="<?= url('/admin/referrals') ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Security::csrfToken() ?>">
                
                <div class="form-group">
                    <label class="form-label" for="first_name">First Name</label>
                    <input type="text" name="first_name" id="first_name" class="form-control" placeholder="e.g. John" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="last_name">Last Name</label>
                    <input type="text" name="last_name" id="last_name" class="form-control" placeholder="e.g. Doe" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input type="email" name="email" id="email" class="form-control" placeholder="e.g. partner@example.com" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Password for dashboard login" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="discount_percent">Student Discount (%)</label>
                    <input type="number" name="discount_percent" id="discount_percent" class="form-control" value="10.00" step="0.01" min="0" max="100" required>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; margin-top: 12px;">Add Partner</button>
            </form>
        </div>
    </div>

    <!-- Data Table Card -->
    <div>
        <div class="data-table-card">
            <div style="overflow-x: auto;">
                <table class="partners-table" id="partners-datatable" style="width:100%">
                    <thead>
                        <tr>
                            <th>Partner Name</th>
                            <th>Referral Code</th>
                            <th>Student Discount</th>
                            <th>Conversions</th>
                            <th>Total Earned</th>
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
    ScholarMatchDataTable('#partners-datatable', {
        ajax: {
            url: '<?= url("/admin/referrals/data") ?>',
            type: 'GET'
        },
        columns: [
            { 
                data: 'partner_name',
                render: function(data, type, row) {
                    return '<strong>' + data + '</strong><div style="font-size: 0.75rem; color: #64748b;">' + row.email + '</div>';
                }
            },
            { 
                data: 'referral_code',
                render: function(data, type, row) {
                    return data ? '<span class="code-badge">' + data + '</span>' : '<span style="color:#94a3b8">-</span>';
                }
            },
            { 
                data: 'discount_percent',
                render: function(data, type, row) {
                    return parseFloat(data).toFixed(2) + '%';
                }
            },
            { 
                data: 'conversions',
                render: function(data, type, row) {
                    return '<strong>' + data + '</strong>';
                }
            },
            { 
                data: 'payouts',
                render: function(data, type, row) {
                    return '$' + data;
                }
            },
            { 
                data: 'status',
                render: function(data, type, row) {
                    var cls = row.status === 'active' ? 'status-approved' : 'status-rejected';
                    return '<span class="status-badge ' + cls + '">' + row.status + '</span>';
                }
            },
            {
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    var deleteForm = '<form action="<?= url("/admin/referrals") ?>/' + row.record_id + '/delete" method="POST" onsubmit="return confirm(\'Are you sure you want to revoke this referral partner? This clears their partner code and suspends their access, but preserves historical data.\');" style="display:inline;">' +
                                     '<input type="hidden" name="csrf_token" value="<?= \App\Helpers\Security::csrfToken() ?>">' +
                                     '<button type="submit" class="action-link danger" style="background:none; border:none; padding:0; cursor:pointer; font-weight:600;">Revoke Partner</button>' +
                                     '</form>';
                    
                    var copyLinkBtn = '<a href="javascript:void(0)" onclick="copyPartnerLink(\'' + row.referral_code + '\')" class="action-link" style="margin-right:8px;">Copy Link</a>';
                    
                    return row.referral_code ? (copyLinkBtn + deleteForm) : '<span style="color:#94a3b8">Revoked</span>';
                }
            }
        ]
    });
});

function copyPartnerLink(code) {
    if (!code) return;
    var url = '<?= url("/register") ?>?ref=' + code;
    navigator.clipboard.writeText(url).then(function() {
        alert('Copied registration link: ' + url);
    }, function() {
        alert('Failed to copy. Link is: ' + url);
    });
}
</script>

<?php include ROOT_PATH . '/app/Views/layouts/admin_footer.php'; ?>
