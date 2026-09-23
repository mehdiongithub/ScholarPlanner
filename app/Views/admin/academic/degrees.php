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
</style>

<!-- Header & Add Button Bar -->
<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <div>
        <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0; color: #1e293b;">Academic Configuration</h1>
        <p style="margin: 4px 0 0 0; color: #64748b; font-size: 0.875rem;">Manage Fields of Study, Academic Degrees, and Funding Types.</p>
    </div>
    <button type="button" class="btn btn-primary" onclick="openAddDegreeModal()" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; font-weight: 600; border-radius: 8px; cursor: pointer; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
        <i data-lucide="plus" style="width: 18px; height: 18px;"></i>
        <span>Add Degree Level</span>
    </button>
</div>

<!-- Navigation Tabs -->
<div class="location-nav" style="margin-bottom: 24px;">
    <a href="<?= url('/admin/academic/fields') ?>" class="location-nav-link">Fields of Study</a>
    <a href="<?= url('/admin/academic/degrees') ?>" class="location-nav-link active">Degrees</a>
    <a href="<?= url('/admin/academic/funding') ?>" class="location-nav-link">Funding Types</a>
</div>

<!-- Full Width Data Table Card -->
<div class="data-table-card">
    <div style="overflow-x: auto;">
        <table class="employees-table" id="degrees-datatable" style="width:100%">
            <thead>
                <tr>
                    <th style="width: 45%;">Degree Level Name</th>
                    <th style="width: 20%;">Sort Order</th>
                    <th style="width: 15%;">Status</th>
                    <th style="width: 20%; text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>

<!-- Add / Edit Degree Level Modal -->
<div id="degreeModal" class="modal-overlay" style="display: none;">
    <div class="modal-box">
        <!-- Header -->
        <div class="modal-box-header">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div id="degreeModalIcon" style="width: 40px; height: 40px; border-radius: 10px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i data-lucide="graduation-cap" style="width: 20px; height: 20px;"></i>
                </div>
                <div>
                    <h3 id="degreeModalTitle" style="margin: 0; font-size: 1.15rem; font-weight: 700; color: #0f172a;">Add New Degree Level</h3>
                    <p id="degreeModalSubtitle" style="margin: 2px 0 0 0; font-size: 0.8125rem; color: #64748b;">Configure degree level name, display order, and status.</p>
                </div>
            </div>
            <button type="button" onclick="closeDegreeModal()" aria-label="Close" style="background: none; border: none; cursor: pointer; color: #94a3b8; padding: 6px; border-radius: 6px; display: flex; align-items: center; justify-content: center; transition: background 0.15s;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='none'">
                <i data-lucide="x" style="width: 20px; height: 20px;"></i>
            </button>
        </div>

        <!-- Form Body -->
        <form id="degreeForm" onsubmit="submitDegreeForm(event)">
            <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Security::csrfToken() ?>">
            <input type="hidden" id="degreeRecordId" name="record_id" value="">

            <div class="modal-body-scroll">
                <!-- In-modal Alert Notification -->
                <div id="degreeModalAlert" style="display: none; margin-bottom: 16px; padding: 12px 14px; border-radius: 8px; font-size: 0.875rem; line-height: 1.4;"></div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label" for="modal_degree_name" style="font-weight: 600; font-size: 0.875rem; color: #334155; margin-bottom: 6px; display: block;">
                        Degree Level Name <span style="color: #ef4444;">*</span>
                    </label>
                    <input type="text" name="name" id="modal_degree_name" class="form-control" placeholder="e.g. Bachelor's Degree" required maxlength="50" style="padding: 10px 14px; font-size: 0.875rem; border: 1px solid #cbd5e1; border-radius: 8px; width: 100%; box-sizing: border-box;">
                    <small style="color: #64748b; font-size: 0.75rem; margin-top: 4px; display: block;">Must be unique across all degree levels.</small>
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label" for="modal_degree_sort_order" style="font-weight: 600; font-size: 0.875rem; color: #334155; margin-bottom: 6px; display: block;">
                        Sort Order Weight <span style="color: #ef4444;">*</span>
                    </label>
                    <input type="number" name="sort_order" id="modal_degree_sort_order" class="form-control" placeholder="e.g. 1" required value="0" min="0" style="padding: 10px 14px; font-size: 0.875rem; border: 1px solid #cbd5e1; border-radius: 8px; width: 100%; box-sizing: border-box;">
                    <small style="color: #64748b; font-size: 0.75rem; margin-top: 4px; display: block;">Controls the sequence order in student selection dropdowns.</small>
                </div>

                <div class="form-group" style="margin-bottom: 8px;">
                    <label class="form-label" for="modal_degree_status" style="font-weight: 600; font-size: 0.875rem; color: #334155; margin-bottom: 6px; display: block;">
                        Status
                    </label>
                    <select name="status" id="modal_degree_status" class="form-control" style="padding: 10px 14px; font-size: 0.875rem; border: 1px solid #cbd5e1; border-radius: 8px; width: 100%; box-sizing: border-box; background: #ffffff;">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    <small style="color: #64748b; font-size: 0.75rem; margin-top: 4px; display: block;">Inactive degrees cannot be selected by students or assigned to new scholarships.</small>
                </div>
            </div>

            <!-- Footer -->
            <div class="modal-box-footer">
                <button type="button" class="btn btn-secondary" onclick="closeDegreeModal()" style="padding: 9px 18px; font-size: 0.875rem; font-weight: 600; border-radius: 8px; border: 1px solid #cbd5e1; background: #ffffff; color: #475569; cursor: pointer;">Cancel</button>
                <button type="submit" id="degreeSubmitBtn" class="btn btn-primary" style="padding: 9px 20px; font-size: 0.875rem; font-weight: 600; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px; cursor: pointer;">
                    <span id="degreeSubmitBtnText">Add Degree</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    var table = ScholarPlannerDataTable('#degrees-datatable', {
        ajax: {
            url: '<?= url("/admin/academic/degrees/data") ?>',
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
                data: 'sort_order',
                render: function(data, type, row) {
                    var safeSort = $('<div>').text(data !== null && data !== undefined ? data : '0').html();
                    return '<span style="display: inline-block; padding: 2px 8px; border-radius: 6px; font-weight: 600; font-size: 0.8125rem; font-family: monospace; background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0;">' + safeSort + '</span>';
                }
            },
            { 
                data: 'status',
                render: function(data, type, row) {
                    var statusVal = (data || 'active').toLowerCase();
                    var cls = statusVal === 'active' ? 'active' : 'inactive';
                    var label = statusVal.charAt(0).toUpperCase() + statusVal.slice(1);
                    return '<span class="status-badge ' + cls + '">' + label + '</span>';
                }
            },
            {
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    var safeName = $('<div>').text(row.name || '').html();
                    var safeSort = $('<div>').text(row.sort_order !== null && row.sort_order !== undefined ? row.sort_order : '0').html();
                    var safeStatus = $('<div>').text(row.status || 'active').html();
                    return '<div style="display: flex; gap: 8px; justify-content: center; align-items: center;">' +
                           '<button type="button" class="btn-action-edit btn-edit-degree" data-id="' + row.record_id + '" data-name="' + safeName + '" data-sort-order="' + safeSort + '" data-status="' + safeStatus + '">' +
                           '<i data-lucide="edit-2" style="width: 14px; height: 14px;"></i> Edit</button>' +
                           '<button type="button" class="btn-action-delete btn-delete-degree" data-id="' + row.record_id + '" data-name="' + safeName + '">' +
                           '<i data-lucide="trash-2" style="width: 14px; height: 14px;"></i> Delete</button>' +
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

    // Edit button click delegation
    $(document).on('click', '.btn-edit-degree', function(e) {
        e.preventDefault();
        var recordId = $(this).attr('data-id');
        var name = $(this).attr('data-name');
        var sortOrder = $(this).attr('data-sort-order');
        var status = $(this).attr('data-status');
        openEditDegreeModal(recordId, name, sortOrder, status);
    });

    // Delete button click delegation
    $(document).on('click', '.btn-delete-degree', function(e) {
        e.preventDefault();
        var recordId = $(this).attr('data-id');
        var name = $(this).attr('data-name');
        deleteDegree(recordId, name);
    });

    // Close modal on escape
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' || e.keyCode === 27) {
            if ($('#degreeModal').is(':visible')) {
                closeDegreeModal();
            }
        }
    });

    // Close modal on click outside box
    $('#degreeModal').on('click', function(e) {
        if (e.target === this) {
            closeDegreeModal();
        }
    });
});

function openAddDegreeModal() {
    $('#degreeModalTitle').text('Add New Degree Level');
    $('#degreeModalSubtitle').text('Configure degree level name, display order, and status.');
    $('#degreeSubmitBtnText').text('Add Degree');
    $('#degreeRecordId').val('');
    $('#modal_degree_name').val('');
    $('#modal_degree_sort_order').val('0');
    $('#modal_degree_status').val('active');
    $('#degreeModalAlert').hide().removeClass('alert-danger alert-success').empty();
    
    var formAction = '<?= url("/admin/academic/degrees") ?>';
    $('#degreeForm').attr('action', formAction).attr('data-mode', 'add');
    
    $('#degreeModal').css('display', 'flex').addClass('active');
    setTimeout(function() {
        $('#modal_degree_name').focus();
    }, 100);

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

function openEditDegreeModal(recordId, name, sortOrder, status) {
    $('#degreeModalTitle').text('Edit Degree Level');
    $('#degreeModalSubtitle').text('Modify sort weight and activation statuses.');
    $('#degreeSubmitBtnText').text('Save Changes');
    $('#degreeRecordId').val(recordId);
    $('#modal_degree_name').val(name || '');
    $('#modal_degree_sort_order').val(sortOrder !== undefined && sortOrder !== null ? sortOrder : '0');
    $('#modal_degree_status').val(status || 'active');
    $('#degreeModalAlert').hide().removeClass('alert-danger alert-success').empty();

    var formAction = '<?= url("/admin/academic/degrees") ?>/' + recordId + '/update';
    $('#degreeForm').attr('action', formAction).attr('data-mode', 'edit');

    $('#degreeModal').css('display', 'flex').addClass('active');
    setTimeout(function() {
        $('#modal_degree_name').focus();
    }, 100);

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

function closeDegreeModal() {
    $('#degreeModal').hide().removeClass('active');
    $('#degreeForm')[0].reset();
    $('#degreeRecordId').val('');
    $('#degreeModalAlert').hide().empty();
    $('#degreeSubmitBtn').prop('disabled', false);
}

function submitDegreeForm(event) {
    event.preventDefault();
    var form = document.getElementById('degreeForm');
    var actionUrl = form.getAttribute('action');
    var formData = new FormData(form);
    var submitBtn = document.getElementById('degreeSubmitBtn');
    var submitBtnText = document.getElementById('degreeSubmitBtnText');
    var alertBox = document.getElementById('degreeModalAlert');

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
        }));
    })
    .then(res => {
        submitBtn.disabled = false;
        submitBtnText.textContent = originalText;

        if (res.ok && res.data.success) {
            closeDegreeModal();
            $('#degrees-datatable').DataTable().ajax.reload(null, false);
        } else {
            var errorMsg = (res.data && res.data.error) ? res.data.error : 'An error occurred while saving.';
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
            .html('<strong>Error:</strong> Failed to communicate with server. Please try again.');
    });
}

function deleteDegree(recordId, name) {
    adminConfirm({
        title: 'Delete Degree Level',
        message: 'Are you sure you want to delete degree level ' + (name ? '<strong>"' + adminEscapeHtml(name) + '"</strong>' : 'this record') + '?',
        subtext: 'This action cannot be undone and will permanently remove this degree level.',
        confirmText: 'Yes, Delete',
        confirmClass: 'btn-danger',
        icon: 'trash-2'
    }, function() {
        const formData = new FormData();
        formData.append('csrf_token', '<?= \App\Helpers\Security::csrfToken() ?>');

        fetch('<?= url("/admin/academic/degrees") ?>/' + recordId + '/delete', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                $('#degrees-datatable').DataTable().ajax.reload(null, false);
            } else {
                adminConfirm({
                    title: 'Delete Failed',
                    message: data.error || 'Failed to delete degree level.',
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
