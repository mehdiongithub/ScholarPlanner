<?php include ROOT_PATH . '/app/Views/layouts/admin_header.php'; ?>

<div style="margin-bottom: 24px;">
    <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0; color: #1e293b;">Payment Transactions Ledger</h1>
    <p style="margin: 4px 0 0 0; color: #64748b; font-size: 0.875rem;">Audit system revenues, invoice status codes, and issue refunds.</p>
</div>

<div class="data-table-card">
    <div style="overflow-x: auto;">
        <table class="employees-table" id="payments-datatable" style="width:100%">
            <thead>
                <tr>
                    <th>Student Details</th>
                    <th>Invoice / Reference</th>
                    <th>Gateway Provider</th>
                    <th>Billing Amount</th>
                    <th>Payment Status</th>
                    <th>Processed At</th>
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
    ScholarPlannerDataTable('#payments-datatable', {
        ajax: {
            url: '<?= url("/admin/payments/data") ?>',
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
                data: 'reference_id',
                render: function(data, type, row) {
                    return '<code>' + data + '</code>';
                }
            },
            { 
                data: 'gateway_name',
                render: function(data, type, row) {
                    return '<span style="text-transform: uppercase; font-size: 0.75rem; font-weight: 600; color: #64748b;">' + data + '</span>';
                }
            },
            { 
                data: 'amount',
                render: function(data, type, row) {
                    return '<strong>$' + parseFloat(data).toFixed(2) + '</strong>';
                }
            },
            { 
                data: 'status',
                render: function(data, type, row) {
                    var cls = data === 'paid' ? 'active' : (data === 'refunded' ? 'pending' : 'suspended');
                    return '<span class="status-badge ' + cls + '">' + data + '</span>';
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
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    if (row.status === 'paid') {
                        var safeAmount = $('<div>').text(row.amount || '0').html();
                        var safeRef = $('<div>').text(row.reference_id || '').html();
                        var refundUrl = '<?= url("/admin/billing/refund") ?>';
                        return '<form action="' + refundUrl + '" method="POST" style="display: inline;">' +
                            '<input type="hidden" name="csrf_token" value="<?= \App\Helpers\Security::csrfToken() ?>">' +
                            '<input type="hidden" name="transaction_id" value="' + row.record_id + '">' +
                            '<button type="button" class="action-link danger btn-refund-payment" style="background: none; border: none; cursor: pointer; font-family: inherit;" data-amount="' + safeAmount + '" data-ref="' + safeRef + '">Refund</button>' +
                            '</form>';
                    } else {
                        return '-';
                    }
                }
            }
        ]
    });

    $(document).on('click', '.btn-refund-payment', function(e) {
        e.preventDefault();
        confirmRefund(this, $(this).attr('data-amount'), $(this).attr('data-ref'));
    });
});

function confirmRefund(btn, amount, refId) {
    var $form = $(btn).closest('form');
    adminConfirm({
        title: 'Confirm Refund',
        message: 'Are you sure you want to issue a refund of <strong>$' + adminEscapeHtml(amount) + '</strong> for reference <strong>' + adminEscapeHtml(refId) + '</strong>?',
        subtext: 'This will refund the payment and immediately revoke their premium access.',
        confirmText: 'Yes, Issue Refund',
        confirmClass: 'btn-danger',
        icon: 'refresh-ccw'
    }, function() {
        $form.data('admin-confirmed', true);
        $form[0].submit();
    });
}
</script>

<?php include ROOT_PATH . '/app/Views/layouts/admin_footer.php'; ?>
