        </div> <!-- .admin-content -->
    </main> <!-- .admin-main -->
</div> <!-- .admin-layout -->

<!-- ===== WHATSAPP SUBSCRIPTION MODAL ===== -->
<div class="modal-overlay" id="waModal" role="dialog" aria-modal="true" aria-label="Activate WhatsApp Alerts" style="z-index: 2000;">
    <div class="modal">
        <div class="modal-header">
            <h3>Activate WhatsApp Alerts</h3>
            <button class="modal-close" id="waModalClose" aria-label="Close">
                <i data-lucide="x"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="modal-features" style="display:flex; flex-direction:column; gap:10px; margin-bottom:20px">
                <div class="modal-feature" style="display:flex; align-items:center; gap:8px; font-size:0.875rem">
                    <i data-lucide="circle-check" style="color:#10b981; width:16px; height:16px"></i>
                    Instant WhatsApp notifications for new matches
                </div>
                <div class="modal-feature" style="display:flex; align-items:center; gap:8px; font-size:0.875rem">
                    <i data-lucide="circle-check" style="color:#10b981; width:16px; height:16px"></i>
                    Deadline reminders before closing dates
                </div>
                <div class="modal-feature" style="display:flex; align-items:center; gap:8px; font-size:0.875rem">
                    <i data-lucide="circle-check" style="color:#10b981; width:16px; height:16px"></i>
                    Email alerts included at no extra cost
                </div>
            </div>

            <!-- Phone input -->
            <label style="font-size:0.875rem; font-weight:600; color:var(--text-700); display:block; margin-bottom:6px">
                Your WhatsApp Number
            </label>
            <div class="phone-input-group" id="phoneInputGroup" style="display:flex; border:1px solid var(--border); border-radius:8px; overflow:hidden">
                <div class="phone-code" style="padding:10px 14px; background:#f1f5f9; border-right:1px solid var(--border); font-size:0.9375rem">
                    🇵🇰 +92
                </div>
                <input
                    type="tel"
                    id="waPhoneInput"
                    placeholder="300 1234567"
                    style="flex-grow:1; border:none; padding:10px 14px; font-size:0.9375rem; outline:none"
                    value="<?= e($user['phone'] ?? '') ?>"
                >
            </div>
            <div class="error-msg" id="phoneError" style="color:#ef4444; font-size:0.75rem; margin-top:4px; display:none">
                Please enter a valid 10-digit mobile number (e.g. 300 1234567)
            </div>

            <button type="button" class="btn btn-primary" id="btnSaveWaAlerts" style="width:100%; justify-content:center; margin-top:20px; padding:12px">
                Activate Alerts
            </button>
        </div>
    </div>
</div>

<!-- Core & Vendor Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script defer src="<?= asset('assets/js/lucide.min.js') ?>"></script>
<script defer src="<?= asset('assets/js/main.js') ?>"></script>

<script>
    // Global Toast Notification Helper
    window.showToast = function(message, type = 'success') {
        const oldToast = document.querySelector('.toast-notification');
        if (oldToast) oldToast.remove();

        const toast = document.createElement('div');
        toast.className = `toast-notification ${type}`;
        toast.style.position = 'fixed';
        toast.style.top = '24px';
        toast.style.right = '24px';
        toast.style.padding = '14px 18px';
        toast.style.borderRadius = '10px';
        toast.style.boxShadow = '0 10px 15px -3px rgba(0, 0, 0, 0.1)';
        toast.style.display = 'flex';
        toast.style.alignItems = 'center';
        toast.style.gap = '10px';
        toast.style.zIndex = '9999';
        toast.style.transition = 'all 0.3s ease';

        if (type === 'success') {
            toast.style.background = '#ecfdf5';
            toast.style.border = '1px solid #d1fae5';
            toast.style.color = '#065f46';
        } else {
            toast.style.background = '#fef2f2';
            toast.style.border = '1px solid #fee2e2';
            toast.style.color = '#991b1b';
        }

        toast.innerHTML = `
            <span style="font-size: 0.875rem; font-weight: 600;">${message}</span>
            <button class="toast-close" style="background: none; border: none; cursor: pointer; color: inherit; display:flex; align-items:center;">
                <i data-lucide="x" style="width: 14px; height: 14px;"></i>
            </button>
        `;

        document.body.appendChild(toast);
        if (typeof lucide !== 'undefined' && lucide.createIcons) lucide.createIcons();

        toast.querySelector('.toast-close').addEventListener('click', () => {
            toast.remove();
        });

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-10px)';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    };

    // Initialize Lucide Icons
    function initLucideSafe() {
        if (typeof lucide !== 'undefined' && lucide.createIcons) {
            try {
                lucide.createIcons();
            } catch (e) {
                console.error("Lucide load error:", e);
            }
        } else {
            setTimeout(initLucideSafe, 50);
        }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLucideSafe);
    } else {
        initLucideSafe();
    }

    // Toggle Mobile Sidebar Drawer
    try {
        const mobileToggle = document.getElementById('mobileToggle');
        const adminSidebar = document.getElementById('adminSidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        if (mobileToggle && adminSidebar && sidebarOverlay) {
            mobileToggle.addEventListener('click', () => {
                adminSidebar.classList.add('open');
                sidebarOverlay.classList.add('open');
            });

            sidebarOverlay.addEventListener('click', () => {
                adminSidebar.classList.remove('open');
                sidebarOverlay.classList.remove('open');
            });
        }
    } catch (e) {
        console.error("Responsive sidebar error:", e);
    }

    // Escape key closes modal & drawers
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const adminSidebar = document.getElementById('adminSidebar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            if (adminSidebar) adminSidebar.classList.remove('open');
            if (sidebarOverlay) sidebarOverlay.classList.remove('open');
            
            const waModal = document.getElementById('waModal');
            if (waModal) waModal.classList.remove('active');
        }
    });

    // Auto-fade alerts
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

    // Initialize Select2 Globally on student layout
    try {
        if (typeof $ !== 'undefined' && typeof $.fn.select2 !== 'undefined') {
            $(document).ready(function() {
                $('select').not('.dataTables_length select, [name$="_length"], .dt-input').select2({
                    width: '100%'
                });
            });
        }
    } catch (e) {
        console.error("Select2 initialization error:", e);
    }

    // Initialize Flatpickr Datepicker Globally
    try {
        if (typeof flatpickr !== 'undefined') {
            flatpickr('.datepicker', {
                dateFormat: 'Y-m-d',
                allowInput: true
            });
        }
    } catch (e) {
        console.error("Flatpickr initialization error:", e);
    }

  

    // Dynamic WhatsApp Subscription Modal Interaction
    try {
        const waModal = document.getElementById('waModal');
        const waModalClose = document.getElementById('waModalClose');
        const sidebarWaLink = document.getElementById('sidebarWaLink');
        const btnSaveWaAlerts = document.getElementById('btnSaveWaAlerts');
        const waPhoneInput = document.getElementById('waPhoneInput');
        const phoneError = document.getElementById('phoneError');



        if (waModalClose) {
            waModalClose.addEventListener('click', function() {
                waModal.classList.remove('active');
            });
        }

        if (btnSaveWaAlerts) {
            btnSaveWaAlerts.addEventListener('click', function() {
                const phone = waPhoneInput.value.replace(/[\s\-]/g, '');
                if (!/^3\d{9}$/.test(phone)) {
                    phoneError.style.display = 'block';
                    return;
                }
                phoneError.style.display = 'none';
                
                // Show loading spinner or disabling button
                btnSaveWaAlerts.disabled = true;
                btnSaveWaAlerts.textContent = 'Activating...';
                
                const formData = new FormData();
                formData.append('phone', phone);
                formData.append('csrf_token', '<?= \App\Helpers\Security::csrfToken() ?>');

                fetch('<?= url("/profile/preferences/update") ?>', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(res => res.json())
                .then(data => {
                    btnSaveWaAlerts.disabled = false;
                    btnSaveWaAlerts.textContent = 'Activate Alerts';
                    if (data.success) {
                        showToast('WhatsApp alerts activated successfully!', 'success');
                        waModal.classList.remove('active');
                    } else {
                        showToast(data.message || 'Failed to update WhatsApp alerts.', 'error');
                    }
                })
                .catch(err => {
                    btnSaveWaAlerts.disabled = false;
                    btnSaveWaAlerts.textContent = 'Activate Alerts';
                    showToast('Communication error occurred.', 'error');
                });
            });
        }
    } catch (e) {
        console.error("WhatsApp modal error:", e);
    }
</script>
</body>
</html>
