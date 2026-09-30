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
#cityModal .select2-container .select2-selection--single {
    height: 42px !important;
    border: 1px solid #cbd5e1 !important;
    border-radius: 8px !important;
    padding: 6px 12px !important;
    display: flex !important;
    align-items: center !important;
    background: #ffffff !important;
}
#cityModal .select2-container--default .select2-selection--single .select2-selection__rendered {
    color: #0f172a !important;
    font-size: 0.875rem !important;
    line-height: normal !important;
    padding-left: 0 !important;
    padding-right: 20px !important;
}
#cityModal .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 40px !important;
    right: 8px !important;
}
#cityModal .select2-container--default .select2-selection--single:focus,
#cityModal .select2-container--default.select2-container--open .select2-selection--single {
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
    <button type="button" class="btn btn-primary" onclick="openAddCityModal()" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; font-weight: 600; border-radius: 8px; cursor: pointer; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
        <i data-lucide="plus" style="width: 18px; height: 18px;"></i>
        <span>Add New City</span>
    </button>
</div>

<!-- Navigation Tabs -->
<div class="location-nav" style="margin-bottom: 24px;">
    <a href="<?= url('/admin/locations/countries') ?>" class="location-nav-link">Countries</a>
    <a href="<?= url('/admin/locations/states') ?>" class="location-nav-link">States / Provinces</a>
    <a href="<?= url('/admin/locations/cities') ?>" class="location-nav-link active">Cities</a>
</div>

<!-- Full Width Data Table Card -->
<div class="data-table-card">
    <div style="overflow-x: auto;">
        <table class="employees-table" id="cities-datatable" style="width:100%">
            <thead>
                <tr>
                    <th>City Name</th>
                    <th>Parent State / Province</th>
                    <th>Country</th>
                    <th style="width: 120px;">Status</th>
                    <th style="width: 180px; text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>

<!-- Add / Edit City Modal -->
<div id="cityModal" class="modal-overlay" style="display: none;">
    <div class="modal-box">
        <!-- Header -->
        <div class="modal-box-header">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div id="cityModalIcon" style="width: 40px; height: 40px; border-radius: 10px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i data-lucide="building-2" style="width: 20px; height: 20px;"></i>
                </div>
                <div>
                    <h3 id="cityModalTitle" style="margin: 0; font-size: 1.15rem; font-weight: 700; color: #0f172a;">Add New City</h3>
                    <p id="cityModalSubtitle" style="margin: 2px 0 0 0; font-size: 0.8125rem; color: #64748b;">Configure city details and parent region bindings.</p>
                </div>
            </div>
            <button type="button" onclick="closeCityModal()" aria-label="Close" style="background: none; border: none; cursor: pointer; color: #94a3b8; padding: 6px; border-radius: 6px; display: flex; align-items: center; justify-content: center; transition: background 0.15s;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='none'">
                <i data-lucide="x" style="width: 20px; height: 20px;"></i>
            </button>
        </div>

        <!-- Form Body -->
        <form id="cityForm" onsubmit="submitCityForm(event)">
            <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Security::csrfToken() ?>">
            <input type="hidden" id="cityRecordId" name="record_id" value="">
            <input type="hidden" name="is_ajax" value="1">

            <div class="modal-body-scroll">
                <!-- In-modal Alert Notification -->
                <div id="cityModalAlert" style="display: none; margin-bottom: 16px; padding: 12px 14px; border-radius: 8px; font-size: 0.875rem; line-height: 1.4;"></div>

                <!-- Country Selection -->
                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label" for="modal_country_id" style="font-weight: 600; font-size: 0.875rem; color: #334155; margin-bottom: 6px; display: block;">
                        Country <span style="color: #ef4444;">*</span>
                    </label>
                    <select id="modal_country_id" class="form-control" required style="width: 100%;">
                        <option value="">-- Select Country --</option>
                        <?php foreach ($countries as $c): ?>
                            <option value="<?= e($c['id']) ?>"><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color: #64748b; font-size: 0.75rem; margin-top: 4px; display: block;">Select the sovereign country first to load its states/provinces.</small>
                </div>

                <!-- State / Province Selection -->
                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label" for="modal_state_id" style="font-weight: 600; font-size: 0.875rem; color: #334155; margin-bottom: 6px; display: block;">
                        State / Province <span style="color: #ef4444;">*</span>
                    </label>
                    <select name="state_id" id="modal_state_id" class="form-control" required style="width: 100%;" disabled>
                        <option value="">-- Select Country First --</option>
                    </select>
                    <small style="color: #64748b; font-size: 0.75rem; margin-top: 4px; display: block;">Parent state, province, or territory this city is located within.</small>
                </div>

                <!-- City Name -->
                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label" for="modal_name" style="font-weight: 600; font-size: 0.875rem; color: #334155; margin-bottom: 6px; display: block;">
                        City Name <span style="color: #ef4444;">*</span>
                    </label>
                    <input type="text" name="name" id="modal_name" class="form-control" placeholder="e.g. Lahore, Toronto, Sydney" required maxlength="100" style="padding: 10px 14px; font-size: 0.875rem; border: 1px solid #cbd5e1; border-radius: 8px; width: 100%; box-sizing: border-box;">
                    <small style="color: #64748b; font-size: 0.75rem; margin-top: 4px; display: block;">Official name of the metropolitan city or municipality.</small>
                </div>

                <!-- Status Selection -->
                <div class="form-group" style="margin-bottom: 8px;">
                    <label class="form-label" for="modal_status" style="font-weight: 600; font-size: 0.875rem; color: #334155; margin-bottom: 6px; display: block;">
                        Status
                    </label>
                    <select name="status" id="modal_status" class="form-control no-select2" style="padding: 10px 14px; font-size: 0.875rem; border: 1px solid #cbd5e1; border-radius: 8px; width: 100%; box-sizing: border-box;">
                        <option value="active">Active (Visible in scholarship filters and forms)</option>
                        <option value="inactive">Inactive (Archived / hidden from active listings)</option>
                    </select>
                </div>
            </div>

            <!-- Footer -->
            <div class="modal-box-footer">
                <button type="button" class="btn btn-secondary" onclick="closeCityModal()" style="padding: 9px 18px; font-size: 0.875rem; font-weight: 600; border-radius: 8px; border: 1px solid #cbd5e1; background: #ffffff; color: #475569; cursor: pointer;">Cancel</button>
                <button type="submit" id="citySubmitBtn" class="btn btn-primary" style="padding: 9px 20px; font-size: 0.875rem; font-weight: 600; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px; cursor: pointer;">
                    <span id="citySubmitBtnText">Add City</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function initCitySelect2() {
    if (typeof $ !== 'undefined' && typeof $.fn.select2 !== 'undefined') {
        try {
            if ($('#modal_country_id').hasClass('select2-hidden-accessible')) {
                $('#modal_country_id').select2('destroy');
            }
            $('#modal_country_id').select2({
                dropdownParent: $('#cityModal'),
                width: '100%',
                placeholder: '-- Select Country --',
                allowClear: false
            });

            if ($('#modal_state_id').hasClass('select2-hidden-accessible')) {
                $('#modal_state_id').select2('destroy');
            }
            $('#modal_state_id').select2({
                dropdownParent: $('#cityModal'),
                width: '100%',
                placeholder: '-- Select State / Province --',
                allowClear: false
            });
        } catch (e) {
            console.error("Select2 initialization error:", e);
        }
    }
}

function loadStatesForCountry(countryId, selectedStateId, callback) {
    var stateSelect = $('#modal_state_id');
    stateSelect.html('<option value="">-- Loading States... --</option>').prop('disabled', true);
    if (typeof $.fn.select2 !== 'undefined' && stateSelect.hasClass('select2-hidden-accessible')) {
        stateSelect.trigger('change');
    }

    if (!countryId) {
        stateSelect.html('<option value="">-- Select Country First --</option>').prop('disabled', true);
        if (typeof $.fn.select2 !== 'undefined' && stateSelect.hasClass('select2-hidden-accessible')) {
            stateSelect.trigger('change');
        }
        if (callback) callback();
        return;
    }

    fetch('<?= url("/api/states") ?>?country_id=' + countryId)
        .then(res => res.json())
        .then(data => {
            var options = '<option value="">-- Select State / Province --</option>';
            if (data && data.length > 0) {
                data.forEach(function(s) {
                    var isSel = (selectedStateId && String(s.id) === String(selectedStateId)) ? ' selected' : '';
                    options += '<option value="' + s.id + '"' + isSel + '>' + $('<div>').text(s.name).html() + '</option>';
                });
                stateSelect.html(options).prop('disabled', false);
            } else {
                stateSelect.html('<option value="">-- No States Found for this Country --</option>').prop('disabled', true);
            }

            if (typeof $.fn.select2 !== 'undefined' && stateSelect.hasClass('select2-hidden-accessible')) {
                stateSelect.trigger('change');
            }
            if (callback) callback();
        })
        .catch(err => {
            console.error("Failed to load states:", err);
            stateSelect.html('<option value="">-- Error Loading States --</option>').prop('disabled', true);
            if (typeof $.fn.select2 !== 'undefined' && stateSelect.hasClass('select2-hidden-accessible')) {
                stateSelect.trigger('change');
            }
            if (callback) callback();
        });
}

$(document).ready(function() {
    // Initialize Select2 with modal parent
    initCitySelect2();

    // Cascading Country change
    $('#modal_country_id').on('change', function() {
        var countryId = $(this).val();
        loadStatesForCountry(countryId);
    });

    var table = ScholarPlannerDataTable('#cities-datatable', {
        ajax: {
            url: '<?= url("/admin/locations/cities/data") ?>',
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
                data: 'state_name',
                render: function(data, type, row) {
                    var safeState = $('<div>').text(data || '').html();
                    return '<span style="color: #334155; font-weight: 500;">' + safeState + '</span>';
                }
            },
            { 
                data: 'country_name',
                render: function(data, type, row) {
                    var safeCountry = $('<div>').text(data || '').html();
                    return '<span style="color: #64748b;">' + safeCountry + '</span>';
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
                           '<button type="button" class="btn-action-edit" onclick="openEditCityModalFromRow(' + safeRow + ')">' +
                           '<i data-lucide="edit-2" style="width: 13px; height: 13px;"></i>' +
                           '<span>Edit</span>' +
                           '</button>' +
                           '<button type="button" class="btn-action-delete btn-delete-city" data-id="' + row.record_id + '" data-name="' + safeName + '">' +
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

    $(document).on('click', '.btn-delete-city', function(e) {
        e.preventDefault();
        var recordId = $(this).attr('data-id');
        var name = $(this).attr('data-name');
        deleteCity(recordId, name);
    });

    // Close modal on escape key
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' || e.keyCode === 27) {
            if ($('#cityModal').is(':visible')) {
                closeCityModal();
            }
        }
    });

    // Close modal on click outside box
    $('#cityModal').on('click', function(e) {
        if (e.target === this) {
            closeCityModal();
        }
    });
});

function openAddCityModal() {
    $('#cityModalTitle').text('Add New City');
    $('#cityModalSubtitle').text('Configure city details and parent region bindings.');
    $('#citySubmitBtnText').text('Add City');
    $('#cityRecordId').val('');
    
    // Reset country
    $('#modal_country_id').val('');
    if (typeof $.fn.select2 !== 'undefined' && $('#modal_country_id').hasClass('select2-hidden-accessible')) {
        $('#modal_country_id').val('').trigger('change');
    }
    
    // Reset states
    $('#modal_state_id').html('<option value="">-- Select Country First --</option>').prop('disabled', true);
    if (typeof $.fn.select2 !== 'undefined' && $('#modal_state_id').hasClass('select2-hidden-accessible')) {
        $('#modal_state_id').trigger('change');
    }

    $('#modal_name').val('');
    $('#modal_status').val('active');
    $('#cityModalAlert').hide().removeClass('alert-danger alert-success').empty();

    var formAction = '<?= url("/admin/locations/cities") ?>';
    $('#cityForm').attr('action', formAction).attr('data-mode', 'add');

    $('#cityModal').css('display', 'flex').addClass('active');
    
    initCitySelect2();

    setTimeout(function() {
        $('#modal_country_id').focus();
    }, 100);

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

function openEditCityModalFromRow(row) {
    if (!row) return;
    $('#cityModalTitle').text('Edit City Details');
    $('#cityModalSubtitle').text('Update city details and parent region bindings.');
    $('#citySubmitBtnText').text('Save Changes');
    $('#cityRecordId').val(row.record_id);

    // Resolve target country ID
    var targetCountryId = row.country_id || '';
    if (!targetCountryId && row.country_name) {
        $('#modal_country_id option').each(function() {
            if ($(this).text().trim().toLowerCase() === String(row.country_name).trim().toLowerCase()) {
                targetCountryId = $(this).val();
                return false;
            }
        });
    }

    var targetStateId = row.state_id || '';

    // Set Country
    $('#modal_country_id').val(targetCountryId);
    if (typeof $.fn.select2 !== 'undefined' && $('#modal_country_id').hasClass('select2-hidden-accessible')) {
        $('#modal_country_id').val(targetCountryId).trigger('change');
    }

    // Load states for this country and pre-select state
    loadStatesForCountry(targetCountryId, targetStateId, function() {
        if (!targetStateId && row.state_name) {
            $('#modal_state_id option').each(function() {
                if ($(this).text().trim().toLowerCase() === String(row.state_name).trim().toLowerCase()) {
                    $(this).prop('selected', true);
                    return false;
                }
            });
            if (typeof $.fn.select2 !== 'undefined' && $('#modal_state_id').hasClass('select2-hidden-accessible')) {
                $('#modal_state_id').trigger('change');
            }
        }
    });

    $('#modal_name').val(row.name || '');
    $('#modal_status').val(row.status || 'active');
    $('#cityModalAlert').hide().removeClass('alert-danger alert-success').empty();

    var formAction = '<?= url("/admin/locations/cities") ?>/' + row.record_id + '/update';
    $('#cityForm').attr('action', formAction).attr('data-mode', 'edit');

    $('#cityModal').css('display', 'flex').addClass('active');

    initCitySelect2();

    setTimeout(function() {
        $('#modal_name').focus();
    }, 100);

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

function closeCityModal() {
    $('#cityModal').hide().removeClass('active');
    $('#cityForm')[0].reset();
    $('#cityRecordId').val('');
    $('#modal_country_id').val('');
    if (typeof $.fn.select2 !== 'undefined' && $('#modal_country_id').hasClass('select2-hidden-accessible')) {
        $('#modal_country_id').val('').trigger('change');
    }
    $('#modal_state_id').html('<option value="">-- Select Country First --</option>').prop('disabled', true);
    if (typeof $.fn.select2 !== 'undefined' && $('#modal_state_id').hasClass('select2-hidden-accessible')) {
        $('#modal_state_id').trigger('change');
    }
    $('#cityModalAlert').hide().empty();
    $('#citySubmitBtn').prop('disabled', false);
}

function submitCityForm(event) {
    event.preventDefault();
    var form = document.getElementById('cityForm');
    var actionUrl = form.getAttribute('action');
    var formData = new FormData(form);
    var submitBtn = document.getElementById('citySubmitBtn');
    var submitBtnText = document.getElementById('citySubmitBtnText');
    var alertBox = document.getElementById('cityModalAlert');

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
            closeCityModal();
            $('#cities-datatable').DataTable().ajax.reload(null, false);
        } else {
            var errorMsg = (res.data && res.data.error) ? res.data.error : 'An error occurred while saving the city.';
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

function deleteCity(recordId, name) {
    adminConfirm({
        title: 'Delete City',
        message: 'Are you sure you want to delete city ' + (name ? '<strong>"' + adminEscapeHtml(name) + '"</strong>' : 'this record') + '?',
        subtext: 'This action cannot be undone.',
        confirmText: 'Yes, Delete',
        confirmClass: 'btn-danger',
        icon: 'trash-2'
    }, function() {
        const formData = new FormData();
        formData.append('csrf_token', '<?= \App\Helpers\Security::csrfToken() ?>');

        fetch('<?= url("/admin/locations/cities") ?>/' + recordId + '/delete', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json().catch(() => ({ success: false, error: 'Server returned error.' })))
        .then(data => {
            if (data.success) {
                $('#cities-datatable').DataTable().ajax.reload(null, false);
            } else {
                adminConfirm({
                    title: 'Delete Failed',
                    message: data.error || 'Failed to delete city.',
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
