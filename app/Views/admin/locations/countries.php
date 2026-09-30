<?php include ROOT_PATH . '/app/Views/layouts/admin_header.php'; ?>

<style>
/* Modal overlay & flex centering */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(4px);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 20px;
    overflow-y: auto;
}
.modal-overlay.active {
    display: flex !important;
}
.modal-box {
    background: #ffffff;
    border-radius: 16px;
    width: 100%;
    max-width: 540px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    overflow: hidden;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    position: relative;
    border: 1px solid #e2e8f0;
    animation: modalSlideIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(12px) scale(0.98);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}
.modal-box-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
    border-bottom: 1px solid #f1f5f9;
    background: #ffffff;
}
.modal-body-scroll {
    padding: 24px;
    overflow-y: auto;
    flex: 1;
}
.modal-box-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
    padding: 16px 24px;
    border-top: 1px solid #f1f5f9;
    background: #f8fafc;
}
.btn-action-edit {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #2563eb;
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    cursor: pointer;
    transition: all 0.15s ease;
    text-decoration: none;
}
.btn-action-edit:hover {
    background: #dbeafe;
    color: #1d4ed8;
}
.btn-action-delete {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #dc2626;
    background: #fef2f2;
    border: 1px solid #fecaca;
    cursor: pointer;
    transition: all 0.15s ease;
}
.btn-action-delete:hover {
    background: #fee2e2;
    color: #b91c1c;
}
.data-table-card {
    background: #ffffff;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.status-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: capitalize;
}
.status-pill.active {
    background: #dcfce7;
    color: #15803d;
    border: 1px solid #bbf7d0;
}
.status-pill.inactive {
    background: #f1f5f9;
    color: #64748b;
    border: 1px solid #e2e8f0;
}
.status-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
}
</style>

<!-- Header & Add Button Bar -->
<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <div>
        <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0; color: #1e293b;">Location Management</h1>
        <p style="margin: 4px 0 0 0; color: #64748b; font-size: 0.875rem;">Manage Geographic Countries, States/Provinces, and Cities lookups.</p>
    </div>
    <button type="button" class="btn btn-primary" onclick="openAddCountryModal()" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; font-weight: 600; border-radius: 8px; cursor: pointer; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
        <i data-lucide="plus" style="width: 18px; height: 18px;"></i>
        <span>Add New Country</span>
    </button>
</div>

<!-- Navigation Tabs -->
<div class="location-nav" style="margin-bottom: 24px;">
    <a href="<?= url('/admin/locations/countries') ?>" class="location-nav-link active">Countries</a>
    <a href="<?= url('/admin/locations/states') ?>" class="location-nav-link">States / Provinces</a>
    <a href="<?= url('/admin/locations/cities') ?>" class="location-nav-link">Cities</a>
</div>

<!-- Full Width Data Table Card -->
<div class="data-table-card">
    <div style="overflow-x: auto;">
        <table class="employees-table" id="countries-datatable" style="width:100%">
            <thead>
                <tr>
                    <th>Country Name</th>
                    <th style="width: 110px;">ISO (2)</th>
                    <th style="width: 110px;">ISO (3)</th>
                    <th style="width: 120px;">Currency</th>
                    <th style="width: 120px;">Dial Code</th>
                    <th style="width: 110px;">Status</th>
                    <th style="width: 180px; text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>

<!-- Add / Edit Country Modal -->
<div id="countryModal" class="modal-overlay" style="display: none;">
    <div class="modal-box">
        <!-- Header -->
        <div class="modal-box-header">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div id="countryModalIcon" style="width: 40px; height: 40px; border-radius: 10px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i data-lucide="globe" style="width: 20px; height: 20px;"></i>
                </div>
                <div>
                    <h3 id="countryModalTitle" style="margin: 0; font-size: 1.15rem; font-weight: 700; color: #0f172a;">Add New Country</h3>
                    <p id="countryModalSubtitle" style="margin: 2px 0 0 0; font-size: 0.8125rem; color: #64748b;">Configure geographic country details, codes, and currency.</p>
                </div>
            </div>
            <button type="button" onclick="closeCountryModal()" aria-label="Close" style="background: none; border: none; cursor: pointer; color: #94a3b8; padding: 6px; border-radius: 6px; display: flex; align-items: center; justify-content: center; transition: background 0.15s;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='none'">
                <i data-lucide="x" style="width: 20px; height: 20px;"></i>
            </button>
        </div>

        <!-- Form Body -->
        <form id="countryForm" onsubmit="submitCountryForm(event)">
            <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Security::csrfToken() ?>">
            <input type="hidden" id="countryRecordId" name="record_id" value="">
            <input type="hidden" name="is_ajax" value="1">

            <div class="modal-body-scroll">
                <!-- In-modal Alert Notification -->
                <div id="countryModalAlert" style="display: none; margin-bottom: 16px; padding: 12px 14px; border-radius: 8px; font-size: 0.875rem; line-height: 1.4;"></div>

                <!-- Country Name -->
                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label" for="modal_name" style="font-weight: 600; font-size: 0.875rem; color: #334155; margin-bottom: 6px; display: block;">
                        Country Name <span style="color: #ef4444;">*</span>
                    </label>
                    <input type="text" name="name" id="modal_name" class="form-control" placeholder="e.g. Pakistan" required maxlength="100" style="padding: 10px 14px; font-size: 0.875rem; border: 1px solid #cbd5e1; border-radius: 8px; width: 100%; box-sizing: border-box;">
                    <small style="color: #64748b; font-size: 0.75rem; margin-top: 4px; display: block;">Full international country name.</small>
                </div>

                <!-- 2-Column Grid: ISO Codes -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" for="modal_iso2" style="font-weight: 600; font-size: 0.875rem; color: #334155; margin-bottom: 6px; display: block;">
                            ISO 2-Letter Code <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="text" name="iso2" id="modal_iso2" class="form-control" placeholder="e.g. PK" required maxlength="2" style="padding: 10px 14px; font-size: 0.875rem; border: 1px solid #cbd5e1; border-radius: 8px; width: 100%; box-sizing: border-box; text-transform: uppercase;">
                        <small style="color: #64748b; font-size: 0.75rem; margin-top: 4px; display: block;">2-character alpha code (ISO 3166-1 alpha-2).</small>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" for="modal_iso3" style="font-weight: 600; font-size: 0.875rem; color: #334155; margin-bottom: 6px; display: block;">
                            ISO 3-Letter Code
                        </label>
                        <input type="text" name="iso3" id="modal_iso3" class="form-control" placeholder="e.g. PAK" maxlength="3" style="padding: 10px 14px; font-size: 0.875rem; border: 1px solid #cbd5e1; border-radius: 8px; width: 100%; box-sizing: border-box; text-transform: uppercase;">
                        <small style="color: #64748b; font-size: 0.75rem; margin-top: 4px; display: block;">3-character alpha code (ISO 3166-1 alpha-3).</small>
                    </div>
                </div>

                <!-- 2-Column Grid: Dial Code & Currency -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" for="modal_phone_code" style="font-weight: 600; font-size: 0.875rem; color: #334155; margin-bottom: 6px; display: block;">
                            Dial / Calling Code <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="text" name="phone_code" id="modal_phone_code" class="form-control" placeholder="e.g. 92 or +92" required maxlength="16" style="padding: 10px 14px; font-size: 0.875rem; border: 1px solid #cbd5e1; border-radius: 8px; width: 100%; box-sizing: border-box;">
                        <small style="color: #64748b; font-size: 0.75rem; margin-top: 4px; display: block;">International telephone dialing prefix.</small>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" for="modal_currency_code" style="font-weight: 600; font-size: 0.875rem; color: #334155; margin-bottom: 6px; display: block;">
                            Currency Code <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="text" name="currency_code" id="modal_currency_code" class="form-control" placeholder="e.g. PKR" required maxlength="3" style="padding: 10px 14px; font-size: 0.875rem; border: 1px solid #cbd5e1; border-radius: 8px; width: 100%; box-sizing: border-box; text-transform: uppercase;">
                        <small style="color: #64748b; font-size: 0.75rem; margin-top: 4px; display: block;">3-letter currency code (ISO 4217).</small>
                    </div>
                </div>

                <!-- Status Selection -->
                <div class="form-group" style="margin-bottom: 8px;">
                    <label class="form-label" for="modal_status" style="font-weight: 600; font-size: 0.875rem; color: #334155; margin-bottom: 6px; display: block;">
                        Status
                    </label>
                    <select name="status" id="modal_status" class="form-control" style="padding: 10px 14px; font-size: 0.875rem; border: 1px solid #cbd5e1; border-radius: 8px; width: 100%; box-sizing: border-box;">
                        <option value="active">Active (Visible in scholarship filters and forms)</option>
                        <option value="inactive">Inactive (Archived / hidden from active listings)</option>
                    </select>
                </div>
            </div>

            <!-- Footer -->
            <div class="modal-box-footer">
                <button type="button" class="btn btn-secondary" onclick="closeCountryModal()" style="padding: 9px 18px; font-size: 0.875rem; font-weight: 600; border-radius: 8px; border: 1px solid #cbd5e1; background: #ffffff; color: #475569; cursor: pointer;">Cancel</button>
                <button type="submit" id="countrySubmitBtn" class="btn btn-primary" style="padding: 9px 20px; font-size: 0.875rem; font-weight: 600; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px; cursor: pointer;">
                    <span id="countrySubmitBtnText">Add Country</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    var table = ScholarPlannerDataTable('#countries-datatable', {
        ajax: {
            url: '<?= url("/admin/locations/countries/data") ?>',
            type: 'GET'
        },
        columns: [
            { 
                data: 'name',
                render: function(data, type, row) {
                    var safeName = $('<div>').text(data || '').html();
                    return '<strong style="color: #0f172a; font-weight: 600;">' + safeName + '</strong>';
                }
            },
            { 
                data: 'iso2',
                render: function(data, type, row) {
                    var code = data || row.iso_code || '-';
                    return '<code style="background: #f1f5f9; padding: 3px 8px; border-radius: 6px; font-size: 0.8125rem; color: #0f172a; font-weight: 600;">' + $('<div>').text(code).html() + '</code>';
                }
            },
            { 
                data: 'iso3',
                render: function(data, type, row) {
                    var code = data || '-';
                    return '<code style="background: #f8fafc; padding: 3px 8px; border-radius: 6px; font-size: 0.8125rem; color: #475569; border: 1px solid #e2e8f0;">' + $('<div>').text(code).html() + '</code>';
                }
            },
            { 
                data: 'currency_code',
                render: function(data, type, row) {
                    return data ? '<span style="font-weight: 600; color: #334155;">' + $('<div>').text(data).html() + '</span>' : '<span style="color: #94a3b8;">-</span>';
                }
            },
            { 
                data: 'phone_code',
                render: function(data, type, row) {
                    var dial = data || row.dial_code || '';
                    if (!dial) return '<span style="color: #94a3b8;">-</span>';
                    var formatted = dial.startsWith('+') ? dial : '+' + dial;
                    return '<span style="color: #0369a1; font-weight: 600;">' + $('<div>').text(formatted).html() + '</span>';
                }
            },
            {
                data: 'status',
                render: function(data, type, row) {
                    var status = (data || 'active').toLowerCase();
                    var isAct = status === 'active';
                    return '<span class="status-pill ' + (isAct ? 'active' : 'inactive') + '">' +
                           '<span class="status-dot"></span>' +
                           (isAct ? 'Active' : 'Inactive') +
                           '</span>';
                }
            },
            {
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    var safeRow = JSON.stringify(row).replace(/"/g, '&quot;');
                    var safeName = $('<div>').text(row.name || '').html();
                    return '<div style="display: flex; gap: 8px; justify-content: center; align-items: center;">' +
                           '<button type="button" class="btn-action-edit" onclick="openEditCountryModalFromRow(' + safeRow + ')">' +
                           '<i data-lucide="edit-2" style="width: 13px; height: 13px;"></i>' +
                           '<span>Edit</span>' +
                           '</button>' +
                           '<button type="button" class="btn-action-delete btn-delete-country" data-id="' + row.record_id + '" data-name="' + safeName + '">' +
                           '<i data-lucide="trash-2" style="width: 13px; height: 13px;"></i>' +
                           '<span>Delete</span>' +
                           '</button>' +
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

    $(document).on('click', '.btn-delete-country', function(e) {
        e.preventDefault();
        var recordId = $(this).attr('data-id');
        var name = $(this).attr('data-name');
        deleteCountry(recordId, name);
    });

    // Auto-uppercase code fields
    $('#modal_iso2, #modal_iso3, #modal_currency_code').on('input', function() {
        this.value = this.value.toUpperCase();
    });

    // Close modal on escape
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' || e.keyCode === 27) {
            if ($('#countryModal').is(':visible')) {
                closeCountryModal();
            }
        }
    });

    // Close modal on click outside box
    $('#countryModal').on('click', function(e) {
        if (e.target === this) {
            closeCountryModal();
        }
    });
});

function openAddCountryModal() {
    $('#countryModalTitle').text('Add New Country');
    $('#countryModalSubtitle').text('Configure geographic country details, codes, and currency.');
    $('#countrySubmitBtnText').text('Add Country');
    $('#countryRecordId').val('');
    $('#modal_name').val('');
    $('#modal_iso2').val('');
    $('#modal_iso3').val('');
    $('#modal_phone_code').val('');
    $('#modal_currency_code').val('');
    $('#modal_status').val('active');
    $('#countryModalAlert').hide().removeClass('alert-danger alert-success').empty();

    var formAction = '<?= url("/admin/locations/countries") ?>';
    $('#countryForm').attr('action', formAction).attr('data-mode', 'add');

    $('#countryModal').css('display', 'flex').addClass('active');
    setTimeout(function() {
        $('#modal_name').focus();
    }, 100);

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

function openEditCountryModalFromRow(row) {
    if (!row) return;
    $('#countryModalTitle').text('Edit Country Details');
    $('#countryModalSubtitle').text('Update geographic country details, codes, and currency.');
    $('#countrySubmitBtnText').text('Save Changes');
    $('#countryRecordId').val(row.record_id);
    $('#modal_name').val(row.name || '');
    $('#modal_iso2').val(row.iso2 || row.iso_code || '');
    $('#modal_iso3').val(row.iso3 || '');
    $('#modal_phone_code').val(row.phone_code || row.dial_code || '');
    $('#modal_currency_code').val(row.currency_code || '');
    $('#modal_status').val(row.status || 'active');
    $('#countryModalAlert').hide().removeClass('alert-danger alert-success').empty();

    var formAction = '<?= url("/admin/locations/countries") ?>/' + row.record_id + '/update';
    $('#countryForm').attr('action', formAction).attr('data-mode', 'edit');

    $('#countryModal').css('display', 'flex').addClass('active');
    setTimeout(function() {
        $('#modal_name').focus();
    }, 100);

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

function closeCountryModal() {
    $('#countryModal').hide().removeClass('active');
    $('#countryForm')[0].reset();
    $('#countryRecordId').val('');
    $('#countryModalAlert').hide().empty();
    $('#countrySubmitBtn').prop('disabled', false);
}

function submitCountryForm(event) {
    event.preventDefault();
    var form = document.getElementById('countryForm');
    var actionUrl = form.getAttribute('action');
    var formData = new FormData(form);
    var submitBtn = document.getElementById('countrySubmitBtn');
    var submitBtnText = document.getElementById('countrySubmitBtnText');
    var alertBox = document.getElementById('countryModalAlert');

    submitBtn.disabled = true;
    var originalText = submitBtnText.textContent;
    submitBtnText.textContent = 'Saving...';
    $(alertBox).hide().empty();

    fetch(actionUrl, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        return response.json().then(data => ({
            status: response.status,
            ok: response.ok,
            data: data
        })).catch(() => ({
            status: response.status,
            ok: response.ok,
            data: { success: false, error: 'Server returned an invalid response (Status ' + response.status + ')' }
        }));
    })
    .then(res => {
        submitBtn.disabled = false;
        submitBtnText.textContent = originalText;

        if (res.ok && res.data.success) {
            closeCountryModal();
            $('#countries-datatable').DataTable().ajax.reload(null, false);
        } else {
            var errorMsg = (res.data && res.data.error) ? res.data.error : 'An error occurred while saving the country.';
            $(alertBox)
                .css({
                    'display': 'block',
                    'background': '#fef2f2',
                    'border': '1px solid #fee2e2',
                    'color': '#991b1b'
                })
                .html('<strong>Error:</strong> ' + $('<div>').text(errorMsg).html());
        }
    })
    .catch(err => {
        console.error(err);
        submitBtn.disabled = false;
        submitBtnText.textContent = originalText;
        $(alertBox)
            .css({
                'display': 'block',
                'background': '#fef2f2',
                'border': '1px solid #fee2e2',
                'color': '#991b1b'
            })
            .html('<strong>Error:</strong> An unexpected network or communication error occurred.');
    });
}

function deleteCountry(recordId, name) {
    adminConfirm({
        title: 'Delete Country',
        message: 'Are you sure you want to delete country ' + (name ? '<strong>"' + adminEscapeHtml(name) + '"</strong>' : 'this record') + '?',
        subtext: 'This operation will fail if states or regions are mapped to this country.',
        confirmText: 'Yes, Delete',
        confirmClass: 'btn-danger',
        icon: 'trash-2'
    }, function() {
        const formData = new FormData();
        formData.append('csrf_token', '<?= \App\Helpers\Security::csrfToken() ?>');

        fetch('<?= url("/admin/locations/countries") ?>/' + recordId + '/delete', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json().catch(() => ({ success: false, error: 'Server returned error.' })))
        .then(data => {
            if (data.success) {
                $('#countries-datatable').DataTable().ajax.reload(null, false);
            } else {
                adminConfirm({
                    title: 'Delete Failed',
                    message: data.error || 'Failed to delete country.',
                    subtext: '',
                    confirmText: 'OK',
                    confirmClass: 'btn-primary',
                    icon: 'alert-triangle'
                });
            }
        })
        .catch(err => {
            console.error(err);
            adminConfirm({
                title: 'Error',
                message: 'An error occurred during communication.',
                subtext: '',
                confirmText: 'OK',
                confirmClass: 'btn-danger',
                icon: 'alert-triangle'
            });
        });
    });
}
</script>

<?php include ROOT_PATH . '/app/Views/layouts/admin_footer.php'; ?>
