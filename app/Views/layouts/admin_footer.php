        </div> <!-- .admin-content -->
    </main> <!-- .admin-main -->
</div> <!-- .admin-layout -->

<!-- Global Admin Action & Delete Confirmation Modal -->
<div id="adminGlobalConfirmModal" class="admin-confirm-overlay" style="display: none;">
    <div class="admin-confirm-modal" role="dialog" aria-modal="true" aria-labelledby="adminConfirmTitle">
        <button type="button" class="admin-confirm-close" id="adminConfirmCloseBtn" aria-label="Close">
            <i data-lucide="x" style="width: 18px; height: 18px;"></i>
        </button>

        <div class="admin-confirm-header">
            <div class="admin-confirm-icon-wrap" id="adminConfirmIconWrap">
                <i data-lucide="trash-2" id="adminConfirmIcon" style="width: 24px; height: 24px;"></i>
            </div>
            <div class="admin-confirm-header-text">
                <h3 id="adminConfirmTitle" class="admin-confirm-title">Confirm Deletion</h3>
                <p id="adminConfirmSubtext" class="admin-confirm-subtext">This action cannot be undone.</p>
            </div>
        </div>

        <div class="admin-confirm-body">
            <p id="adminConfirmMessage" class="admin-confirm-message">Are you sure you want to delete this item?</p>
        </div>

        <div class="admin-confirm-footer">
            <button type="button" class="btn btn-secondary admin-confirm-btn-cancel" id="adminConfirmCancelBtn">
                Cancel
            </button>
            <button type="button" class="btn btn-danger admin-confirm-btn-action" id="adminConfirmActionBtn">
                <i data-lucide="trash-2" id="adminConfirmActionBtnIcon" style="width: 16px; height: 16px;"></i>
                <span id="adminConfirmActionBtnText">Yes, Delete</span>
            </button>
        </div>
    </div>
</div>

<style>
.admin-confirm-overlay {
    position: fixed;
    inset: 0;
    background-color: rgba(15, 23, 42, 0.65);
    backdrop-filter: blur(4px);
    z-index: 999999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
    animation: adminConfirmFadeIn 0.15s ease-out forwards;
}
.admin-confirm-overlay.active {
    display: flex !important;
}
.admin-confirm-modal {
    background: #ffffff;
    border-radius: 16px;
    width: 100%;
    max-width: 480px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25), 0 0 0 1px rgba(15, 23, 42, 0.05);
    overflow: hidden;
    position: relative;
    animation: adminConfirmScaleIn 0.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
.admin-confirm-close {
    position: absolute;
    top: 16px;
    right: 16px;
    background: transparent;
    border: none;
    color: #94a3b8;
    cursor: pointer;
    padding: 4px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s ease;
}
.admin-confirm-close:hover {
    color: #0f172a;
    background: #f1f5f9;
}
.admin-confirm-header {
    padding: 24px 24px 16px 24px;
    display: flex;
    align-items: center;
    gap: 16px;
    border-bottom: 1px solid #f1f5f9;
}
.admin-confirm-icon-wrap {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    background: #fef2f2;
    color: #dc2626;
    border: 1px solid #fecaca;
}
.admin-confirm-icon-wrap.warning {
    background: #fffbeb;
    color: #d97706;
    border-color: #fde68a;
}
.admin-confirm-icon-wrap.primary {
    background: #eff6ff;
    color: #2563eb;
    border-color: #bfdbfe;
}
.admin-confirm-header-text {
    flex: 1;
    min-width: 0;
}
.admin-confirm-title {
    margin: 0;
    font-size: 1.15rem;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.3;
}
.admin-confirm-subtext {
    margin: 4px 0 0 0;
    font-size: 0.8rem;
    color: #64748b;
    font-weight: 500;
}
.admin-confirm-body {
    padding: 20px 24px;
}
.admin-confirm-message {
    margin: 0;
    font-size: 0.925rem;
    color: #334155;
    line-height: 1.55;
}
.admin-confirm-message strong {
    color: #0f172a;
}
.admin-confirm-footer {
    padding: 16px 24px;
    background: #f8fafc;
    border-top: 1px solid #f1f5f9;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}
.admin-confirm-btn-cancel {
    background-color: #ffffff !important;
    border: 1px solid #cbd5e1 !important;
    color: #475569 !important;
    padding: 9px 18px !important;
    font-weight: 600 !important;
    font-size: 0.875rem !important;
    border-radius: 8px !important;
    cursor: pointer;
    transition: all 0.15s ease;
}
.admin-confirm-btn-cancel:hover {
    background-color: #f1f5f9 !important;
    color: #0f172a !important;
}
.admin-confirm-btn-action {
    display: inline-flex !important;
    align-items: center !important;
    gap: 6px !important;
    padding: 9px 22px !important;
    font-weight: 600 !important;
    font-size: 0.875rem !important;
    border-radius: 8px !important;
    cursor: pointer;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    transition: all 0.15s ease;
}
@keyframes adminConfirmFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
@keyframes adminConfirmScaleIn {
    from { opacity: 0; transform: scale(0.95) translateY(10px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
</style>

<script>
    // Initialize Lucide Icons
    try {
        lucide.createIcons();
    } catch (e) {
        console.error("Lucide icons error:", e);
    }

    // Global HTML Escape helper
    window.adminEscapeHtml = function(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    };

    // Global Admin Confirmation Modal Function
    window.adminConfirm = function(options, onConfirm, onCancel) {
        if (typeof options === 'string') {
            options = { message: options };
        }
        options = options || {};

        var title = options.title || 'Confirm Deletion';
        var message = options.message || 'Are you sure you want to delete this item?';
        var subtext = (options.subtext !== undefined) ? options.subtext : 'This action cannot be undone and will permanently remove this record.';
        var confirmText = options.confirmText || 'Yes, Delete';
        var confirmClass = options.confirmClass || 'btn-danger';
        var iconName = options.icon || (confirmClass.indexOf('danger') !== -1 ? 'trash-2' : (confirmClass.indexOf('warning') !== -1 ? 'alert-triangle' : 'help-circle'));

        var modal = $('#adminGlobalConfirmModal');
        $('#adminConfirmTitle').text(title);
        $('#adminConfirmMessage').html(message);
        if (subtext) {
            $('#adminConfirmSubtext').text(subtext).show();
        } else {
            $('#adminConfirmSubtext').hide();
        }

        var iconWrap = $('#adminConfirmIconWrap');
        iconWrap.removeClass('warning primary');
        if (confirmClass.indexOf('warning') !== -1) {
            iconWrap.addClass('warning');
        } else if (confirmClass.indexOf('primary') !== -1) {
            iconWrap.addClass('primary');
        }

        var actionBtn = $('#adminConfirmActionBtn');
        actionBtn.removeClass('btn-danger btn-primary btn-warning btn-secondary').addClass(confirmClass);
        $('#adminConfirmActionBtnText').text(confirmText);

        $('#adminConfirmIcon').attr('data-lucide', iconName);
        $('#adminConfirmActionBtnIcon').attr('data-lucide', iconName);
        if (typeof lucide !== 'undefined') lucide.createIcons();

        modal.addClass('active');

        return new Promise(function(resolve) {
            function cleanup(confirmed) {
                modal.removeClass('active');
                actionBtn.off('click.adminConfirm');
                $('#adminConfirmCancelBtn').off('click.adminConfirm');
                $('#adminConfirmCloseBtn').off('click.adminConfirm');
                modal.off('click.adminConfirm');
                $(document).off('keydown.adminConfirm');

                if (confirmed) {
                    if (typeof onConfirm === 'function') onConfirm();
                    resolve(true);
                } else {
                    if (typeof onCancel === 'function') onCancel();
                    resolve(false);
                }
            }

            actionBtn.on('click.adminConfirm', function() { cleanup(true); });
            $('#adminConfirmCancelBtn').on('click.adminConfirm', function() { cleanup(false); });
            $('#adminConfirmCloseBtn').on('click.adminConfirm', function() { cleanup(false); });

            modal.on('click.adminConfirm', function(e) {
                if ($(e.target).is('#adminGlobalConfirmModal')) {
                    cleanup(false);
                }
            });

            $(document).on('keydown.adminConfirm', function(e) {
                if (e.key === 'Escape' || e.keyCode === 27) {
                    cleanup(false);
                }
            });
        });
    };

    // Helper specifically for Delete actions on table rows / forms
    window.adminConfirmDelete = function(element, entityType, entityName) {
        var $form = $(element).closest('form');
        var label = entityType ? entityType : 'record';
        var nameHtml = entityName ? ' <strong style="color: #0f172a;">"' + adminEscapeHtml(entityName) + '"</strong>' : '';

        adminConfirm({
            title: 'Delete ' + (label.charAt(0).toUpperCase() + label.slice(1)),
            message: 'Are you sure you want to delete this ' + label + nameHtml + '?',
            subtext: 'This action cannot be undone and will permanently remove this record.',
            confirmText: 'Yes, Delete',
            confirmClass: 'btn-danger',
            icon: 'trash-2'
        }, function() {
            if ($form.length) {
                $form.data('admin-confirmed', true);
                $form[0].submit();
            }
        });
    };

    // Global Delegated Interceptor: automatically catches any form with confirm() in onsubmit or data-confirm
    $(document).on('submit', 'form', function(e) {
        var $form = $(this);
        if ($form.data('admin-confirmed')) {
            $form.removeData('admin-confirmed');
            return true;
        }

        var onsubmitStr = $form.attr('onsubmit') || '';
        var dataConfirm = $form.attr('data-confirm');
        var confirmMsg = dataConfirm;

        if (!confirmMsg && onsubmitStr.indexOf('confirm(') !== -1) {
            var match = onsubmitStr.match(/confirm\s*\(\s*(['"`])(.*?)\1\s*\)/);
            if (match && match[2]) {
                confirmMsg = match[2].replace(/\\'/g, "'").replace(/\\"/g, '"');
            } else {
                confirmMsg = 'Are you sure you want to proceed with this action?';
            }
        }

        if (confirmMsg) {
            e.preventDefault();
            e.stopImmediatePropagation();

            var isDelete = confirmMsg.toLowerCase().indexOf('delete') !== -1 || ($form.attr('action') && $form.attr('action').indexOf('delete') !== -1);
            var isRevoke = confirmMsg.toLowerCase().indexOf('revoke') !== -1;
            var isSuspend = confirmMsg.toLowerCase().indexOf('suspend') !== -1;

            var title = isDelete ? 'Confirm Deletion' : (isRevoke ? 'Confirm Revoke' : (isSuspend ? 'Confirm Suspension' : 'Confirm Action'));
            var btnText = isDelete ? 'Yes, Delete' : (isRevoke ? 'Yes, Revoke' : (isSuspend ? 'Yes, Suspend' : 'Yes, Proceed'));

            adminConfirm({
                title: title,
                message: confirmMsg,
                confirmText: btnText,
                confirmClass: (isDelete || isRevoke || isSuspend) ? 'btn-danger' : 'btn-primary'
            }, function() {
                $form.data('admin-confirmed', true);
                $form[0].submit();
            });

            return false;
        }
    });

    // Responsive Sidebar toggle drawer logic
    try {
        const mobileToggle = document.getElementById('mobileToggle');
        const adminSidebar = document.getElementById('adminSidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        if (mobileToggle && adminSidebar && sidebarOverlay) {
            mobileToggle.addEventListener('click', () => {
                adminSidebar.classList.toggle('open');
                sidebarOverlay.classList.toggle('open');
            });

            sidebarOverlay.addEventListener('click', () => {
                adminSidebar.classList.remove('open');
                sidebarOverlay.classList.remove('open');
            });
        }
    } catch (e) {
        console.error("Responsive sidebar error:", e);
    }

    // Auto-fade notifications alerts after 5 seconds
    try {
        const alerts = document.querySelectorAll('.admin-alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            }, 5000);
        });
    } catch (e) {
        console.error("Alerts auto-fade error:", e);
    }

    // Initialize Select2 globally on all forms and filters, excluding DataTables length menus
    try {
        if (typeof $ !== 'undefined') {
            $(document).ready(function() {
                if (typeof $.fn.select2 !== 'undefined') {
                    $('select').not('.dataTables_length select, [name$="_length"], .dt-input, .no-select2').select2({
                        width: '100%'
                    });
                }
            });
        }
    } catch (e) {
        console.error("Global Select2 error:", e);
    }
</script>
</body>
</html>
