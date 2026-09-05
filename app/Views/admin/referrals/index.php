<?php include ROOT_PATH . '/app/Views/layouts/admin_header.php'; ?>

<div style="margin-bottom: 24px;">
    <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0; color: #1e293b;">Referral Partner Program</h1>
    <p style="margin: 4px 0 0 0; color: #64748b; font-size: 0.875rem;">Manage marketing partners, customize commission and discount parameters, and audit commission earnings.</p>
</div>

<?php if (!empty($errors)): ?>
    <div style="background-color: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 0.875rem;">
        <?= e($errors) ?>
    </div>
<?php endif; ?>

<?php if (!empty($success)): ?>
    <div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 0.875rem;">
        <?= e($success) ?>
    </div>
<?php endif; ?>

<div class="referral-layout" style="display: grid; grid-template-columns: 360px 1fr; gap: 24px; align-items: start;">
    <!-- Left Column: Forms -->
    <div>
        <!-- Quick Add Form -->
        <div class="form-card" style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 24px;">
            <h2 style="font-size: 1.125rem; font-weight: 700; color: #1e293b; margin-top: 0; margin-bottom: 16px;">Add Referral Partner</h2>
            <form action="<?= url('/admin/referrals') ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Security::csrfToken() ?>">
                
                <div class="form-group" style="margin-bottom: 12px;">
                    <label class="form-label" for="first_name" style="font-size: 0.8125rem; font-weight: 600;">First Name</label>
                    <input type="text" name="first_name" id="first_name" class="form-control" placeholder="e.g. John" required>
                </div>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label class="form-label" for="last_name" style="font-size: 0.8125rem; font-weight: 600;">Last Name</label>
                    <input type="text" name="last_name" id="last_name" class="form-control" placeholder="e.g. Doe" required>
                </div>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label class="form-label" for="email" style="font-size: 0.8125rem; font-weight: 600;">Email Address</label>
                    <input type="email" name="email" id="email" class="form-control" placeholder="e.g. partner@example.com" required>
                </div>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label class="form-label" for="password" style="font-size: 0.8125rem; font-weight: 600;">Password</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Password for login" required>
                </div>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label class="form-label" for="referral_code" style="font-size: 0.8125rem; font-weight: 600;">Referral Code (Optional, max 8 chars)</label>
                    <input type="text" name="referral_code" id="referral_code" class="form-control" placeholder="e.g. PARTNER1" maxlength="8" style="text-transform: uppercase;">
                    <small style="color: #64748b; font-size: 0.75rem;">Leave blank to auto-generate an 8-character code.</small>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                    <div class="form-group">
                        <label class="form-label" for="discount_percent" style="font-size: 0.8125rem; font-weight: 600;">Discount (%)</label>
                        <input type="number" name="discount_percent" id="discount_percent" class="form-control" value="10.00" step="0.01" min="0" max="100" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="commission_percent" style="font-size: 0.8125rem; font-weight: 600;">Commission (%)</label>
                        <input type="number" name="commission_percent" id="commission_percent" class="form-control" placeholder="30.00" step="0.01" min="0" max="100">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">Create Partner</button>
            </form>
        </div>

        <!-- Global Referral Settings Card -->
        <div class="form-card" style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px;">
            <h2 style="font-size: 1.125rem; font-weight: 700; color: #1e293b; margin-top: 0; margin-bottom: 16px;">Global Configuration</h2>
            <form action="<?= url('/admin/referrals/settings') ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Security::csrfToken() ?>">

                <div class="form-group" style="margin-bottom: 12px;">
                    <label class="form-label" for="cfg_discount" style="font-size: 0.8125rem; font-weight: 600;">Default 1st-Payment Discount (%)</label>
                    <input type="number" name="referral_default_discount_percent" id="cfg_discount" class="form-control" value="<?= e($default_discount ?? '10.00') ?>" step="0.01" min="0" max="100" required>
                </div>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label class="form-label" for="cfg_commission" style="font-size: 0.8125rem; font-weight: 600;">Default Commission (%)</label>
                    <input type="number" name="referral_default_commission_percent" id="cfg_commission" class="form-control" value="<?= e($default_commission ?? '30.00') ?>" step="0.01" min="0" max="100" required>
                </div>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label class="form-label" for="cfg_window" style="font-size: 0.8125rem; font-weight: 600;">Attribution Window (Months)</label>
                    <input type="number" name="referral_attribution_window_months" id="cfg_window" class="form-control" value="<?= e($window_months ?? '6') ?>" min="1" max="36" required>
                    <small style="color: #64748b; font-size: 0.75rem;">Window begins on user's registration date.</small>
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label" for="cfg_basis" style="font-size: 0.8125rem; font-weight: 600;">Commission Calculation Basis</label>
                    <select name="referral_commission_basis" id="cfg_basis" class="form-control">
                        <option value="paid_amount_after_discount" <?= ($commission_basis ?? '') === 'paid_amount_after_discount' ? 'selected' : '' ?>>Paid Amount (After Discount)</option>
                        <option value="original_plan_amount" <?= ($commission_basis ?? '') === 'original_plan_amount' ? 'selected' : '' ?>>Original Plan Amount</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-secondary" style="width: 100%; justify-content: center;">Save Settings</button>
            </form>
        </div>
    </div>

    <!-- Right Column: Data Table Card -->
    <div>
        <div class="data-table-card" style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px;">
            <h2 style="font-size: 1.125rem; font-weight: 700; color: #1e293b; margin-top: 0; margin-bottom: 16px;">Active Referral Partners</h2>
            <div style="overflow-x: auto;">
                <table class="partners-table" id="partners-datatable" style="width:100%">
                    <thead>
                        <tr>
                            <th>Partner Name</th>
                            <th>Referral Code</th>
                            <th>Discount</th>
                            <th>Commission</th>
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
                    return data ? '<span class="code-badge" style="background: #eff6ff; color: #1d4ed8; padding: 2px 8px; border-radius: 4px; font-family: monospace; font-weight: 600;">' + data + '</span>' : '<span style="color:#94a3b8">-</span>';
                }
            },
            { 
                data: 'discount_percent',
                render: function(data, type, row) {
                    return parseFloat(data || 0).toFixed(2) + '%';
                }
            },
            {
                data: 'commission_percent',
                render: function(data, type, row) {
                    return data ? parseFloat(data).toFixed(2) + '%' : '<span style="color: #64748b;">Default</span>';
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
                    return '<strong style="color: #10b981;">Rs ' + data + '</strong>';
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
                                     '<button type="submit" class="action-link danger" style="background:none; border:none; padding:0; cursor:pointer; font-weight:600; color: #ef4444;">Revoke</button>' +
                                     '</form>';
                    
                    var copyLinkBtn = '<a href="javascript:void(0)" onclick="copyPartnerLink(\'' + row.referral_code + '\')" class="action-link" style="margin-right:8px; color: #2563eb; text-decoration: none;">Copy Link</a>';
                    
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
