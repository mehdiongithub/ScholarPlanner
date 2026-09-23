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
    <button type="button" class="btn btn-primary" onclick="openAddFieldModal()" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; font-weight: 600; border-radius: 8px; cursor: pointer; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
        <i data-lucide="plus" style="width: 18px; height: 18px;"></i>
        <span>Add Field of Study</span>
    </button>
</div>

<!-- Navigation Tabs -->
<div class="location-nav" style="margin-bottom: 24px;">
    <a href="<?= url('/admin/academic/fields') ?>" class="location-nav-link active">Fields of Study</a>
    <a href="<?= url('/admin/academic/degrees') ?>" class="location-nav-link">Degrees</a>
    <a href="<?= url('/admin/academic/funding') ?>" class="location-nav-link">Funding Types</a>
</div>

<!-- Full Width Data Table Card -->
<div class="data-table-card">
    <div style="overflow-x: auto;">
        <table class="employees-table" id="fields-datatable" style="width:100%">
            <thead>
                <tr>
                    <th style="width: 32%;">Field Name</th>
                    <th>Description</th>
                    <th style="width: 180px; text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>

<!-- Add / Edit Field of Study Modal -->
<div id="fieldModal" class="modal-overlay" style="display: none;">
    <div class="modal-box">
        <!-- Header -->
        <div class="modal-box-header">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div id="fieldModalIcon" style="width: 40px; height: 40px; border-radius: 10px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i data-lucide="book-open" style="width: 20px; height: 20px;"></i>
                </div>
                <div>
                    <h3 id="fieldModalTitle" style="margin: 0; font-size: 1.15rem; font-weight: 700; color: #0f172a;">Add New Field of Study</h3>
                    <p id="fieldModalSubtitle" style="margin: 2px 0 0 0; font-size: 0.8125rem; color: #64748b;">Configure academic field name and description details.</p>
                </div>
            </div>
            <button type="button" onclick="closeFieldModal()" aria-label="Close" style="background: none; border: none; cursor: pointer; color: #94a3b8; padding: 6px; border-radius: 6px; display: flex; align-items: center; justify-content: center; transition: background 0.15s;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='none'">
                <i data-lucide="x" style="width: 20px; height: 20px;"></i>
            </button>
        </div>

        <!-- Form Body -->
        <form id="fieldForm" onsubmit="submitFieldForm(event)">
            <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Security::csrfToken() ?>">
            <input type="hidden" id="fieldRecordId" name="record_id" value="">

            <div class="modal-body-scroll">
                <!-- In-modal Alert Notification -->
                <div id="fieldModalAlert" style="display: none; margin-bottom: 16px; padding: 12px 14px; border-radius: 8px; font-size: 0.875rem; line-height: 1.4;"></div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label" for="modal_name" style="font-weight: 600; font-size: 0.875rem; color: #334155; margin-bottom: 6px; display: block;">
                        Field Name <span style="color: #ef4444;">*</span>
                    </label>
                    <input type="text" name="name" id="modal_name" class="form-control" placeholder="e.g. Computer Science & Artificial Intelligence" required maxlength="100" style="padding: 10px 14px; font-size: 0.875rem; border: 1px solid #cbd5e1; border-radius: 8px; width: 100%; box-sizing: border-box;">
                    <small style="color: #64748b; font-size: 0.75rem; margin-top: 4px; display: block;">Must be unique across all fields of study.</small>
                </div>

                <div class="form-group" style="margin-bottom: 8px;">
                    <label class="form-label" for="modal_description" style="font-weight: 600; font-size: 0.875rem; color: #334155; margin-bottom: 6px; display: block;">
                        Description <span style="color: #94a3b8; font-weight: normal;">(Optional)</span>
                    </label>
                    <textarea name="description" id="modal_description" class="form-control" placeholder="Short description of this study sector, relevant disciplines, or notes..." style="padding: 10px 14px; font-size: 0.875rem; border: 1px solid #cbd5e1; border-radius: 8px; width: 100%; min-height: 110px; resize: vertical; box-sizing: border-box;"></textarea>
                </div>
            </div>

            <!-- Footer -->
            <div class="modal-box-footer">
                <button type="button" class="btn btn-secondary" onclick="closeFieldModal()" style="padding: 9px 18px; font-size: 0.875rem; font-weight: 600; border-radius: 8px; border: 1px solid #cbd5e1; background: #ffffff; color: #475569; cursor: pointer;">Cancel</button>
                <button type="submit" id="fieldSubmitBtn" class="btn btn-primary" style="padding: 9px 20px; font-size: 0.875rem; font-weight: 600; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px; cursor: pointer;">
                    <span id="fieldSubmitBtnText">Add Field</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    var table = ScholarPlannerDataTable('#fields-datatable', {
        ajax: {
            url: '<?= url("/admin/academic/fields/data") ?>',
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
                data: 'description',
                render: function(data, type, row) {
                    if (!data) return '<span style="color: #94a3b8;">-</span>';
                    var safeDesc = $('<div>').text(data).html();
                    return '<span style="color: #475569;">' + safeDesc + '</span>';
                }
            },
            {
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    var safeName = $('<div>').text(row.name || '').html();
                    var safeDesc = $('<div>').text(row.description || '').html();
                    return '<div style="display: flex; gap: 8px; justify-content: center; align-items: center;">' +
                           '<button type="button" class="btn-action-edit btn-edit-field" data-id="' + row.record_id + '" data-name="' + safeName + '" data-description="' + safeDesc + '">' +
                           '<i data-lucide="edit-2" style="width: 14px; height: 14px;"></i> Edit</button>' +
                           '<button type="button" class="btn-action-delete btn-delete-field" data-id="' + row.record_id + '" data-name="' + safeName + '">' +
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
    $(document).on('click', '.btn-edit-field', function(e) {
        e.preventDefault();
        var recordId = $(this).attr('data-id');
        var name = $(this).attr('data-name');
        var description = $(this).attr('data-description');
        openEditFieldModal(recordId, name, description);
    });

    // Delete button click delegation
    $(document).on('click', '.btn-delete-field', function(e) {
        e.preventDefault();
        var recordId = $(this).attr('data-id');
        var name = $(this).attr('data-name');
        deleteField(recordId, name);
    });

    // Close modal on escape
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' || e.keyCode === 27) {
            if ($('#fieldModal').is(':visible')) {
                closeFieldModal();
            }
        }
    });

    // Close modal on click outside box
    $('#fieldModal').on('click', function(e) {
        if (e.target === this) {
            closeFieldModal();
        }
    });
});

function openAddFieldModal() {
    $('#fieldModalTitle').text('Add New Field of Study');
    $('#fieldModalSubtitle').text('Configure academic field name and description details.');
    $('#fieldSubmitBtnText').text('Add Field');
    $('#fieldRecordId').val('');
    $('#modal_name').val('');
    $('#modal_description').val('');
    $('#fieldModalAlert').hide().removeClass('alert-danger alert-success').empty();
    
    var formAction = '<?= url("/admin/academic/fields") ?>';
    $('#fieldForm').attr('action', formAction).attr('data-mode', 'add');
    
    $('#fieldModal').css('display', 'flex').addClass('active');
    setTimeout(function() {
        $('#modal_name').focus();
    }, 100);

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

function openEditFieldModal(recordId, name, description) {
    $('#fieldModalTitle').text('Edit Field of Study');
    $('#fieldModalSubtitle').text('Update academic field name and description details.');
    $('#fieldSubmitBtnText').text('Save Changes');
    $('#fieldRecordId').val(recordId);
    $('#modal_name').val(name || '');
    $('#modal_description').val(description || '');
    $('#fieldModalAlert').hide().removeClass('alert-danger alert-success').empty();

    var formAction = '<?= url("/admin/academic/fields") ?>/' + recordId + '/update';
    $('#fieldForm').attr('action', formAction).attr('data-mode', 'edit');

    $('#fieldModal').css('display', 'flex').addClass('active');
    setTimeout(function() {
        $('#modal_name').focus();
    }, 100);

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

function closeFieldModal() {
    $('#fieldModal').hide().removeClass('active');
    $('#fieldForm')[0].reset();
    $('#fieldRecordId').val('');
    $('#fieldModalAlert').hide().empty();
    $('#fieldSubmitBtn').prop('disabled', false);
}

function submitFieldForm(event) {
    event.preventDefault();
    var form = document.getElementById('fieldForm');
    var actionUrl = form.getAttribute('action');
    var formData = new FormData(form);
    var submitBtn = document.getElementById('fieldSubmitBtn');
    var submitBtnText = document.getElementById('fieldSubmitBtnText');
    var alertBox = document.getElementById('fieldModalAlert');

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
            closeFieldModal();
            $('#fields-datatable').DataTable().ajax.reload(null, false);
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

function deleteField(recordId, name) {
    adminConfirm({
        title: 'Delete Field of Study',
        message: 'Are you sure you want to delete field of study ' + (name ? '<strong>"' + adminEscapeHtml(name) + '"</strong>' : 'this record') + '?',
        subtext: 'This action cannot be undone and will permanently remove this field of study.',
        confirmText: 'Yes, Delete',
        confirmClass: 'btn-danger',
        icon: 'trash-2'
    }, function() {
        const formData = new FormData();
        formData.append('csrf_token', '<?= \App\Helpers\Security::csrfToken() ?>');

        fetch('<?= url("/admin/academic/fields") ?>/' + recordId + '/delete', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                $('#fields-datatable').DataTable().ajax.reload(null, false);
            } else {
                adminConfirm({
                    title: 'Delete Failed',
                    message: data.error || 'Failed to delete field.',
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
