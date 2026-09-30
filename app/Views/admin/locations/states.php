<?php 
// Fallback: Ensure countries list is always available even if controller cache or isolated view
if (empty($countries)) {
    try {
        $db = \App\Services\Database::connection();
        $countries = $db->query("SELECT id, name FROM countries ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (\Exception $e) {
        $countries = [];
    }
}
include ROOT_PATH . '/app/Views/layouts/admin_header.php'; 
?>

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
    overflow: visible;
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
    border-top-left-radius: 16px;
    border-top-right-radius: 16px;
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
    border-bottom-left-radius: 16px;
    border-bottom-right-radius: 16px;
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

/* Ensure Select2 inside modal appears ABOVE modal overlay */
.select2-container--open {
    z-index: 100005 !important;
}
.select2-dropdown {
    z-index: 100005 !important;
    border: 1px solid #cbd5e1 !important;
    border-radius: 8px !important;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15) !important;
}
#stateModal .select2-container .select2-selection--single {
    height: 42px !important;
    border: 1px solid #cbd5e1 !important;
    border-radius: 8px !important;
    padding: 6px 12px !important;
    display: flex !important;
    align-items: center !important;
    background: #ffffff !important;
}
#stateModal .select2-container--default .select2-selection--single .select2-selection__rendered {
    color: #0f172a !important;
    font-size: 0.875rem !important;
    line-height: normal !important;
    padding-left: 0 !important;
    padding-right: 20px !important;
}
#stateModal .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 40px !important;
    right: 8px !important;
}
#stateModal .select2-container--default .select2-selection--single:focus,
#stateModal .select2-container--default.select2-container--open .select2-selection--single {
    border-color: #2563eb !important;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15) !important;
}
</style>

<!-- Header & Add Button Bar -->
<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <div>
        <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0; color: #1e293b;">Location Management</h1>
        <p style="margin: 4px 0 0 0; color: #64748b; font-size: 0.875rem;">Manage Geographic Countries, States/Provinces, and Cities lookups.</p>
    </div>
    <button type="button" class="btn btn-primary" onclick="openAddStateModal()" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; font-weight: 600; border-radius: 8px; cursor: pointer; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
        <i data-lucide="plus" style="width: 18px; height: 18px;"></i>
        <span>Add New State</span>
    </button>
</div>

<!-- Navigation Tabs -->
<div class="location-nav" style="margin-bottom: 24px;">
    <a href="<?= url('/admin/locations/countries') ?>" class="location-nav-link">Countries</a>
    <a href="<?= url('/admin/locations/states') ?>" class="location-nav-link active">States / Provinces</a>
    <a href="<?= url('/admin/locations/cities') ?>" class="location-nav-link">Cities</a>
</div>

<!-- Full Width Data Table Card -->
<div class="data-table-card">
    <div style="overflow-x: auto;">
        <table class="employees-table" id="states-datatable" style="width:100%">
            <thead>
                <tr>
                    <th>State / Province Name</th>
                    <th style="width: 130px;">State Code</th>
                    <th>Parent Country</th>
                    <th style="width: 120px;">Status</th>
                    <th style="width: 180px; text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>

<!-- Add / Edit State Modal -->
<div id="stateModal" class="modal-overlay" style="display: none;">
    <div class="modal-box">
        <!-- Header -->
        <div class="modal-box-header">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div id="stateModalIcon" style="width: 40px; height: 40px; border-radius: 10px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i data-lucide="map-pin" style="width: 20px; height: 20px;"></i>
                </div>
                <div>
                    <h3 id="stateModalTitle" style="margin: 0; font-size: 1.15rem; font-weight: 700; color: #0f172a;">Add New State / Province</h3>
                    <p id="stateModalSubtitle" style="margin: 2px 0 0 0; font-size: 0.8125rem; color: #64748b;">Configure regional state/province and parent country bindings.</p>
                </div>
            </div>
            <button type="button" onclick="closeStateModal()" aria-label="Close" style="background: none; border: none; cursor: pointer; color: #94a3b8; padding: 6px; border-radius: 6px; display: flex; align-items: center; justify-content: center; transition: background 0.15s;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='none'">
                <i data-lucide="x" style="width: 20px; height: 20px;"></i>
            </button>
        </div>

        <!-- Form Body -->
        <form id="stateForm" onsubmit="submitStateForm(event)">
            <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Security::csrfToken() ?>">
            <input type="hidden" id="stateRecordId" name="record_id" value="">
            <input type="hidden" name="is_ajax" value="1">

            <div class="modal-body-scroll">
                <!-- In-modal Alert Notification -->
                <div id="stateModalAlert" style="display: none; margin-bottom: 16px; padding: 12px 14px; border-radius: 8px; font-size: 0.875rem; line-height: 1.4;"></div>

                <!-- Parent Country -->
                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label" for="modal_country_id" style="font-weight: 600; font-size: 0.875rem; color: #334155; margin-bottom: 6px; display: block;">
                        Parent Country <span style="color: #ef4444;">*</span>
                    </label>
                    <select name="country_id" id="modal_country_id" class="form-control" required style="width: 100%;">
                        <option value="">-- Select Country --</option>
                        <?php foreach ($countries as $c): ?>
                            <option value="<?= e($c['id']) ?>"><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color: #64748b; font-size: 0.75rem; margin-top: 4px; display: block;">Select the sovereign country this state or province belongs to.</small>
                </div>

                <!-- State Name -->
                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label" for="modal_name" style="font-weight: 600; font-size: 0.875rem; color: #334155; margin-bottom: 6px; display: block;">
                        State / Province Name <span style="color: #ef4444;">*</span>
                    </label>
                    <input type="text" name="name" id="modal_name" class="form-control" placeholder="e.g. Punjab, California, Ontario" required maxlength="100" style="padding: 10px 14px; font-size: 0.875rem; border: 1px solid #cbd5e1; border-radius: 8px; width: 100%; box-sizing: border-box;">
                    <small style="color: #64748b; font-size: 0.75rem; margin-top: 4px; display: block;">Full state, province, territory, or administrative division name.</small>
                </div>

                <!-- 2-Column Grid: Code & Status -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 8px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" for="modal_code" style="font-weight: 600; font-size: 0.875rem; color: #334155; margin-bottom: 6px; display: block;">
                            State Code
                        </label>
                        <input type="text" name="code" id="modal_code" class="form-control" placeholder="e.g. PB, CA, NY" maxlength="20" style="padding: 10px 14px; font-size: 0.875rem; border: 1px solid #cbd5e1; border-radius: 8px; width: 100%; box-sizing: border-box; text-transform: uppercase;">
                        <small style="color: #64748b; font-size: 0.75rem; margin-top: 4px; display: block;">Abbreviation or postal code (optional).</small>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" for="modal_status" style="font-weight: 600; font-size: 0.875rem; color: #334155; margin-bottom: 6px; display: block;">
                            Status
                        </label>
                        <select name="status" id="modal_status" class="form-control no-select2" style="padding: 10px 14px; font-size: 0.875rem; border: 1px solid #cbd5e1; border-radius: 8px; width: 100%; box-sizing: border-box;">
                            <option value="active">Active (Visible)</option>
                            <option value="inactive">Inactive (Hidden)</option>
                        </select>
                        <small style="color: #64748b; font-size: 0.75rem; margin-top: 4px; display: block;">Active in scholarship dropdowns.</small>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="modal-box-footer">
                <button type="button" class="btn btn-secondary" onclick="closeStateModal()" style="padding: 9px 18px; font-size: 0.875rem; font-weight: 600; border-radius: 8px; border: 1px solid #cbd5e1; background: #ffffff; color: #475569; cursor: pointer;">Cancel</button>
                <button type="submit" id="stateSubmitBtn" class="btn btn-primary" style="padding: 9px 20px; font-size: 0.875rem; font-weight: 600; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px; cursor: pointer;">
                    <span id="stateSubmitBtnText">Add State</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function initCountrySelect2() {
    if (typeof $ !== 'undefined' && typeof $.fn.select2 !== 'undefined') {
        try {
            if ($('#modal_country_id').hasClass('select2-hidden-accessible')) {
                $('#modal_country_id').select2('destroy');
            }
            $('#modal_country_id').select2({
                dropdownParent: $('#stateModal'),
                width: '100%',
                placeholder: '-- Select Country --',
                allowClear: false
            });
        } catch (e) {
            console.error("Select2 initialization error:", e);
        }
    }
}

$(document).ready(function() {
    // Initialize Select2 with modal parent
    initCountrySelect2();

    var table = ScholarPlannerDataTable('#states-datatable', {
        ajax: {
            url: '<?= url("/admin/locations/states/data") ?>',
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
                data: 'code',
                render: function(data, type, row) {
                    var code = data || '-';
                    if (!data) return '<span style="color: #94a3b8;">-</span>';
                    return '<code style="background: #f1f5f9; padding: 3px 8px; border-radius: 6px; font-size: 0.8125rem; color: #0f172a; font-weight: 600;">' + $('<div>').text(code).html() + '</code>';
                }
            },
            { 
                data: 'country_name',
                render: function(data, type, row) {
                    var safeCountry = $('<div>').text(data || '').html();
                    return '<span style="color: #334155; font-weight: 500;">' + safeCountry + '</span>';
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
                           '<button type="button" class="btn-action-edit" onclick="openEditStateModalFromRow(' + safeRow + ')">' +
                           '<i data-lucide="edit-2" style="width: 13px; height: 13px;"></i>' +
                           '<span>Edit</span>' +
                           '</button>' +
                           '<button type="button" class="btn-action-delete btn-delete-state" data-id="' + row.record_id + '" data-name="' + safeName + '">' +
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

    $(document).on('click', '.btn-delete-state', function(e) {
        e.preventDefault();
        var recordId = $(this).attr('data-id');
        var name = $(this).attr('data-name');
        deleteState(recordId, name);
    });

    // Auto-uppercase state code
    $('#modal_code').on('input', function() {
        this.value = this.value.toUpperCase();
    });

    // Close modal on escape key
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' || e.keyCode === 27) {
            if ($('#stateModal').is(':visible')) {
                closeStateModal();
            }
        }
    });

    // Close modal on click outside box
    $('#stateModal').on('click', function(e) {
        if (e.target === this) {
            closeStateModal();
        }
    });
});

function openAddStateModal() {
    $('#stateModalTitle').text('Add New State / Province');
    $('#stateModalSubtitle').text('Configure regional state/province and parent country bindings.');
    $('#stateSubmitBtnText').text('Add State');
    $('#stateRecordId').val('');
    
    // Reset country
    $('#modal_country_id').val('');
    if (typeof $.fn.select2 !== 'undefined' && $('#modal_country_id').hasClass('select2-hidden-accessible')) {
        $('#modal_country_id').val('').trigger('change');
    }
    
    $('#modal_name').val('');
    $('#modal_code').val('');
    $('#modal_status').val('active');
    $('#stateModalAlert').hide().removeClass('alert-danger alert-success').empty();

    var formAction = '<?= url("/admin/locations/states") ?>';
    $('#stateForm').attr('action', formAction).attr('data-mode', 'add');

    $('#stateModal').css('display', 'flex').addClass('active');
    
    // Ensure Select2 is correctly bound to stateModal
    initCountrySelect2();

    setTimeout(function() {
        $('#modal_name').focus();
    }, 100);

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

function openEditStateModalFromRow(row) {
    if (!row) return;
    $('#stateModalTitle').text('Edit State Details');
    $('#stateModalSubtitle').text('Update regional state/province and parent country bindings.');
    $('#stateSubmitBtnText').text('Save Changes');
    $('#stateRecordId').val(row.record_id);

    // Resolve target country ID: either by raw ID / property or by country name text
    var targetCountryId = row.country_id || '';
    if (!targetCountryId && row.country_name) {
        $('#modal_country_id option').each(function() {
            if ($(this).text().trim().toLowerCase() === String(row.country_name).trim().toLowerCase()) {
                targetCountryId = $(this).val();
                return false;
            }
        });
    }

    $('#modal_country_id').val(targetCountryId);
    if (typeof $.fn.select2 !== 'undefined' && $('#modal_country_id').hasClass('select2-hidden-accessible')) {
        $('#modal_country_id').val(targetCountryId).trigger('change');
    }

    $('#modal_name').val(row.name || '');
    $('#modal_code').val(row.code || '');
    $('#modal_status').val(row.status || 'active');
    $('#stateModalAlert').hide().removeClass('alert-danger alert-success').empty();

    var formAction = '<?= url("/admin/locations/states") ?>/' + row.record_id + '/update';
    $('#stateForm').attr('action', formAction).attr('data-mode', 'edit');

    $('#stateModal').css('display', 'flex').addClass('active');

    // Ensure Select2 dropdown opens above stateModal
    initCountrySelect2();

    setTimeout(function() {
        $('#modal_name').focus();
    }, 100);

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

function closeStateModal() {
    $('#stateModal').hide().removeClass('active');
    $('#stateForm')[0].reset();
    $('#stateRecordId').val('');
    $('#modal_country_id').val('');
    if (typeof $.fn.select2 !== 'undefined' && $('#modal_country_id').hasClass('select2-hidden-accessible')) {
        $('#modal_country_id').val('').trigger('change');
    }
    $('#stateModalAlert').hide().empty();
    $('#stateSubmitBtn').prop('disabled', false);
}

function submitStateForm(event) {
    event.preventDefault();
    var form = document.getElementById('stateForm');
    var actionUrl = form.getAttribute('action');
    var formData = new FormData(form);
    var submitBtn = document.getElementById('stateSubmitBtn');
    var submitBtnText = document.getElementById('stateSubmitBtnText');
    var alertBox = document.getElementById('stateModalAlert');

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
            closeStateModal();
            $('#states-datatable').DataTable().ajax.reload(null, false);
        } else {
            var errorMsg = (res.data && res.data.error) ? res.data.error : 'An error occurred while saving the state.';
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

function deleteState(recordId, name) {
    adminConfirm({
        title: 'Delete State / Province',
        message: 'Are you sure you want to delete state ' + (name ? '<strong>"' + adminEscapeHtml(name) + '"</strong>' : 'this record') + '?',
        subtext: 'This operation will fail if cities are mapped to this state.',
        confirmText: 'Yes, Delete',
        confirmClass: 'btn-danger',
        icon: 'trash-2'
    }, function() {
        const formData = new FormData();
        formData.append('csrf_token', '<?= \App\Helpers\Security::csrfToken() ?>');

        fetch('<?= url("/admin/locations/states") ?>/' + recordId + '/delete', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json().catch(() => ({ success: false, error: 'Server returned error.' })))
        .then(data => {
            if (data.success) {
                $('#states-datatable').DataTable().ajax.reload(null, false);
            } else {
                adminConfirm({
                    title: 'Delete Failed',
                    message: data.error || 'Failed to delete state.',
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
