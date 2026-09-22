<?php include ROOT_PATH . '/app/Views/layouts/admin_header.php'; ?>

<style>
.metric-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}
.metric-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.metric-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.metric-info h3 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.2;
}
.metric-info p {
    margin: 4px 0 0 0;
    font-size: 0.8125rem;
    color: #64748b;
    font-weight: 500;
}

.table-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    overflow: hidden;
}
.table-header-bar {
    padding: 18px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 14px;
    border-bottom: 1px solid #f1f5f9;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 10px;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
}
.status-badge.active {
    background-color: #dcfce7;
    color: #15803d;
    border: 1px solid #bbf7d0;
}
.status-badge.inactive {
    background-color: #f1f5f9;
    color: #64748b;
    border: 1px solid #e2e8f0;
}

.feature-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 7px;
    border-radius: 6px;
    font-size: 0.7rem;
    font-weight: 600;
    margin: 2px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    color: #475569;
}
.feature-pill.enabled {
    background: #eff6ff;
    border-color: #bfdbfe;
    color: #1d4ed8;
}

.action-btn-sm {
    border: none;
    border-radius: 6px;
    padding: 6px 10px;
    font-size: 0.775rem;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: all 0.15s ease;
}
.action-btn-sm.edit {
    background-color: #eff6ff;
    color: #1d4ed8;
}
.action-btn-sm.edit:hover {
    background-color: #dbeafe;
    color: #1e40af;
}
.action-btn-sm.toggle {
    background-color: #f8fafc;
    color: #475569;
    border: 1px solid #cbd5e1;
}
.action-btn-sm.toggle:hover {
    background-color: #f1f5f9;
    color: #0f172a;
}
.action-btn-sm.delete {
    background-color: #fef2f2;
    color: #dc2626;
}
.action-btn-sm.delete:hover {
    background-color: #fee2e2;
    color: #b91c1c;
}

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
    border-radius: 14px;
    width: 100%;
    max-width: 620px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    overflow: hidden;
    max-height: 85vh;
    display: flex;
    flex-direction: column;
    position: relative;
}
.modal-box form {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-height: 0;
    overflow: hidden;
    margin: 0;
}
.modal-body-scroll {
    overflow-y: auto;
    overflow-x: hidden;
    -webkit-overflow-scrolling: touch;
    flex: 1;
    min-height: 0;
    padding: 24px;
}
.modal-body-scroll::-webkit-scrollbar {
    width: 6px;
}
.modal-body-scroll::-webkit-scrollbar-track {
    background: #f1f5f9;
}
.modal-body-scroll::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}
.modal-body-scroll::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
.modal-box-header {
    padding: 20px 24px;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
}
.modal-box-footer {
    padding: 16px 24px;
    background: #f8fafc;
    border-top: 1px solid #f1f5f9;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    flex-shrink: 0;
}
</style>

<div class="content-wrapper" style="padding: 24px 32px;">

    <!-- Top Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
        <div>
            <h1 style="margin: 0; font-size: 1.625rem; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 10px;">
                <i data-lucide="package" style="width: 28px; height: 28px; color: #2563eb;"></i>
                <span>Subscription Plans Management</span>
            </h1>
            <p style="margin: 4px 0 0 0; font-size: 0.875rem; color: #64748b;">
                Create, configure, and manage subscription tiers. Changes immediately reflect across pricing, checkout, and feature permissions.
            </p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="<?= url('/admin/manual-subscriptions') ?>" class="btn" style="background-color: #f1f5f9; color: #334155; font-weight: 600; padding: 10px 16px; font-size: 0.875rem; display: inline-flex; align-items: center; gap: 6px;">
                <i data-lucide="user-check" style="width: 16px; height: 16px;"></i>
                <span>Active User Plans</span>
            </a>
            <button type="button" class="btn btn-primary" onclick="openCreatePlanModal()" style="padding: 10px 20px; font-weight: 600; font-size: 0.875rem; display: inline-flex; align-items: center; gap: 6px;">
                <i data-lucide="plus-circle" style="width: 17px; height: 17px;"></i>
                <span>+ Create Subscription Plan</span>
            </button>
        </div>
    </div>

    <!-- Feedback Alerts -->
    <div id="plansPageAlert" style="display: none; margin-bottom: 20px; padding: 12px 18px; border-radius: 8px; font-size: 0.875rem;"></div>

    <?php if (!empty($_SESSION['admin_success'])): ?>
        <div style="background-color: #dcfce7; border: 1px solid #bbf7d0; color: #15803d; border-radius: 8px; padding: 12px 18px; margin-bottom: 20px; font-size: 0.875rem; display: flex; align-items: center; gap: 10px;">
            <i data-lucide="check-circle" style="width: 18px; height: 18px; flex-shrink: 0;"></i>
            <span><?= e($_SESSION['admin_success']) ?></span>
        </div>
        <?php unset($_SESSION['admin_success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['admin_errors'])): ?>
        <div style="background-color: #fef2f2; border: 1px solid #fecaca; color: #dc2626; border-radius: 8px; padding: 12px 18px; margin-bottom: 20px; font-size: 0.875rem; display: flex; align-items: center; gap: 10px;">
            <i data-lucide="alert-triangle" style="width: 18px; height: 18px; flex-shrink: 0;"></i>
            <span><?= e($_SESSION['admin_errors']) ?></span>
        </div>
        <?php unset($_SESSION['admin_errors']); ?>
    <?php endif; ?>

    <!-- Metric Cards Grid -->
    <div class="metric-cards-grid">
        <div class="metric-card">
            <div class="metric-icon" style="background-color: #eff6ff; color: #2563eb;">
                <i data-lucide="package" style="width: 24px; height: 24px;"></i>
            </div>
            <div class="metric-info">
                <h3 id="statTotalPlans"><?= (int)($totalPlans ?? 0) ?></h3>
                <p>Total Plans</p>
            </div>
        </div>
        <div class="metric-card">
            <div class="metric-icon" style="background-color: #dcfce7; color: #15803d;">
                <i data-lucide="check-circle" style="width: 24px; height: 24px;"></i>
            </div>
            <div class="metric-info">
                <h3 id="statActivePlans"><?= (int)($activePlans ?? 0) ?></h3>
                <p>Active Tiers</p>
            </div>
        </div>
        <div class="metric-card">
            <div class="metric-icon" style="background-color: #f1f5f9; color: #64748b;">
                <i data-lucide="power" style="width: 24px; height: 24px;"></i>
            </div>
            <div class="metric-info">
                <h3 id="statInactivePlans"><?= (int)($inactivePlans ?? 0) ?></h3>
                <p>Inactive Tiers</p>
            </div>
        </div>
        <div class="metric-card">
            <div class="metric-icon" style="background-color: #fef3c7; color: #b45309;">
                <i data-lucide="credit-card" style="width: 24px; height: 24px;"></i>
            </div>
            <div class="metric-info">
                <h3 id="statPaidPlans"><?= (int)($paidPlans ?? 0) ?></h3>
                <p>Paid Subscriptions</p>
            </div>
        </div>
    </div>

    <!-- Plans Table Card -->
    <div class="table-card">
        <div class="table-header-bar">
            <div>
                <h2 style="margin: 0; font-size: 1.1rem; font-weight: 700; color: #0f172a;">All Subscription Plans</h2>
                <span style="font-size: 0.8125rem; color: #64748b;">Manage pricing, intervals, feature access, and visibility.</span>
            </div>
            <div style="display: flex; align-items: center; gap: 10px;">
                <select id="statusFilter" class="form-control" style="font-size: 0.8125rem; padding: 7px 12px; width: 140px;" onchange="reloadPlansTable()">
                    <option value="">All Statuses</option>
                    <option value="active">Active Only</option>
                    <option value="inactive">Inactive Only</option>
                </select>
            </div>
        </div>

        <div style="padding: 16px 20px;">
            <div class="table-responsive">
                <table id="plans-datatable" class="table table-hover" style="width: 100%;">
                    <thead>
                        <tr>
                            <th style="font-size: 0.8rem; text-transform: uppercase; color: #64748b; font-weight: 700;">Plan & Description</th>
                            <th style="font-size: 0.8rem; text-transform: uppercase; color: #64748b; font-weight: 700;">Price</th>
                            <th style="font-size: 0.8rem; text-transform: uppercase; color: #64748b; font-weight: 700;">Interval & Duration</th>
                            <th style="font-size: 0.8rem; text-transform: uppercase; color: #64748b; font-weight: 700;">Included Features</th>
                            <th style="font-size: 0.8rem; text-transform: uppercase; color: #64748b; font-weight: 700;">Subscribers</th>
                            <th style="font-size: 0.8rem; text-transform: uppercase; color: #64748b; font-weight: 700;">Status</th>
                            <th style="font-size: 0.8rem; text-transform: uppercase; color: #64748b; font-weight: 700; text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Plan Modal (Create & Edit) -->
<div id="planModal" class="modal-overlay" style="display: none;">
    <div class="modal-box">
        <!-- Header -->
        <div class="modal-box-header">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 38px; height: 38px; border-radius: 10px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center;">
                    <i data-lucide="package" style="width: 20px; height: 20px;"></i>
                </div>
                <div>
                    <h3 id="planModalTitle" style="margin: 0; font-size: 1.15rem; font-weight: 700; color: #0f172a;">Create Subscription Plan</h3>
                    <p style="margin: 2px 0 0 0; font-size: 0.8rem; color: #64748b;">Configure tier pricing, billing cycle, and feature flags.</p>
                </div>
            </div>
            <button type="button" onclick="closePlanModal()" style="background: none; border: none; cursor: pointer; color: #94a3b8; padding: 4px;">
                <i data-lucide="x" style="width: 20px; height: 20px;"></i>
            </button>
        </div>

        <!-- Form Body -->
        <form id="planForm" onsubmit="submitPlanForm(event)">
            <input type="hidden" name="csrf_token" value="<?= e($csrf_token ?? '') ?>">
            <input type="hidden" id="planFormId" value="">

            <div class="modal-body-scroll">
                <div id="planModalAlert" style="display: none; margin-bottom: 16px; padding: 10px 14px; border-radius: 8px; font-size: 0.8125rem;"></div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                    <!-- Plan Name -->
                    <div>
                        <label class="form-label" style="font-weight: 600; font-size: 0.8125rem; color: #334155;">Plan Name <span style="color: #ef4444;">*</span></label>
                        <input type="text" name="name" id="planNameInput" class="form-control" placeholder="e.g. Pro Scholar" required maxlength="50" oninput="autoGenerateSlug(this.value)">
                    </div>
                    <!-- Slug -->
                    <div>
                        <label class="form-label" style="font-weight: 600; font-size: 0.8125rem; color: #334155;">Slug (URL Identifier) <span style="color: #ef4444;">*</span></label>
                        <input type="text" name="slug" id="planSlugInput" class="form-control" placeholder="e.g. pro-scholar" required maxlength="50">
                    </div>
                </div>

                <!-- Description -->
                <div class="form-group" style="margin-bottom: 14px;">
                    <label class="form-label" style="font-weight: 600; font-size: 0.8125rem; color: #334155;">Short Description</label>
                    <textarea name="description" id="planDescInput" class="form-control" rows="2" placeholder="Brief summary of what this tier offers to students..." maxlength="255"></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                    <!-- Price -->
                    <div>
                        <label class="form-label" style="font-weight: 600; font-size: 0.8125rem; color: #334155;">Price <span style="color: #ef4444;">*</span></label>
                        <input type="number" name="price" id="planPriceInput" class="form-control" placeholder="0.00" step="0.01" min="0" required value="0">
                        <small style="color: #64748b; font-size: 0.725rem;">0 for free tier</small>
                    </div>
                    <!-- Currency -->
                    <div>
                        <label class="form-label" style="font-weight: 600; font-size: 0.8125rem; color: #334155;">Currency <span style="color: #ef4444;">*</span></label>
                        <select name="currency" id="planCurrencyInput" class="form-control">
                            <option value="PKR" selected>PKR (Rs)</option>
                            <option value="USD">USD ($)</option>
                            <option value="EUR">EUR (€)</option>
                            <option value="GBP">GBP (£)</option>
                        </select>
                    </div>
                    <!-- Status -->
                    <div>
                        <label class="form-label" style="font-weight: 600; font-size: 0.8125rem; color: #334155;">Status</label>
                        <select name="status" id="planStatusInput" class="form-control">
                            <option value="active" selected>Active (Visible)</option>
                            <option value="inactive">Inactive (Hidden)</option>
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; margin-bottom: 18px;">
                    <!-- Billing Interval -->
                    <div>
                        <label class="form-label" style="font-weight: 600; font-size: 0.8125rem; color: #334155;">Billing Cycle</label>
                        <select name="billing_interval" id="planIntervalInput" class="form-control">
                            <option value="month" selected>Monthly</option>
                            <option value="year">Yearly</option>
                            <option value="quarter">Quarterly</option>
                            <option value="day">Daily</option>
                            <option value="one-time">One-time</option>
                        </select>
                    </div>
                    <!-- Duration in Days -->
                    <div>
                        <label class="form-label" style="font-weight: 600; font-size: 0.8125rem; color: #334155;">Duration (Days) <span style="color: #ef4444;">*</span></label>
                        <input type="number" name="duration_days" id="planDurationInput" class="form-control" value="30" min="1" max="3650" required>
                    </div>
                    <!-- Max Matches -->
                    <div>
                        <label class="form-label" style="font-weight: 600; font-size: 0.8125rem; color: #334155;">Max Matches</label>
                        <input type="number" name="max_matches" id="planMaxMatchesInput" class="form-control" placeholder="Blank = Unlimited" min="1">
                    </div>
                </div>

                <!-- Feature Entitlements Checklist -->
                <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; margin-bottom: 8px;">
                    <div style="font-weight: 700; font-size: 0.8125rem; color: #1e293b; margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
                        <i data-lucide="shield-check" style="width: 16px; height: 16px; color: #2563eb;"></i>
                        <span>Feature Entitlements & Permissions</span>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 0.8125rem; color: #334155; cursor: pointer;">
                            <input type="checkbox" name="whatsapp_alerts" id="featureWhatsapp" value="1">
                            <span>WhatsApp Alerts</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 0.8125rem; color: #334155; cursor: pointer;">
                            <input type="checkbox" name="email_alerts" id="featureEmail" value="1" checked>
                            <span>Email Notifications</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 0.8125rem; color: #334155; cursor: pointer;">
                            <input type="checkbox" name="deadline_reminders" id="featureDeadlines" value="1">
                            <span>Deadline Reminders</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 0.8125rem; color: #334155; cursor: pointer;">
                            <input type="checkbox" name="application_tracking" id="featureTracking" value="1">
                            <span>Application Tracking</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="modal-box-footer">
                <button type="button" class="btn" onclick="closePlanModal()" style="background-color: #ffffff; border: 1px solid #cbd5e1; color: #475569; padding: 9px 18px; font-weight: 600; font-size: 0.875rem;">
                    Cancel
                </button>
                <button type="submit" id="btnSubmitPlan" class="btn btn-primary" style="padding: 9px 22px; font-weight: 600; font-size: 0.875rem; display: inline-flex; align-items: center; gap: 6px;">
                    <i data-lucide="check" style="width: 16px; height: 16px;"></i>
                    <span id="btnSubmitPlanText">Save Plan</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
var isEditingMode = false;

function escapeAttr(str) {
    if (!str) return '';
    return String(str).replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

function autoGenerateSlug(val) {
    if (isEditingMode) return;
    var slug = val.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
    $('#planSlugInput').val(slug);
}

function reloadPlansTable() {
    if (window.plansTable) {
        window.plansTable.ajax.reload(null, false);
    }
}

function openCreatePlanModal() {
    isEditingMode = false;
    $('#planModalTitle').text('Create Subscription Plan');
    $('#btnSubmitPlanText').text('Create Plan');
    $('#planFormId').val('');
    $('#planForm')[0].reset();
    $('#planModalAlert').hide();
    $('#planPriceInput').val('0.00');
    $('#planCurrencyInput').val('PKR');
    $('#planDurationInput').val('30');
    $('#planIntervalInput').val('month');
    $('#planStatusInput').val('active');
    $('#featureEmail').prop('checked', true);
    $('#featureWhatsapp').prop('checked', false);
    $('#featureDeadlines').prop('checked', false);
    $('#featureTracking').prop('checked', false);

    $('#planModal').addClass('active');
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function openEditPlanModal(encodedId) {
    isEditingMode = true;
    $('#planModalTitle').text('Edit Subscription Plan');
    $('#btnSubmitPlanText').text('Update Plan');
    $('#planFormId').val(encodedId);
    $('#planModalAlert').hide();

    $.ajax({
        url: '<?= url("/admin/plans/") ?>' + encodedId + '/edit',
        type: 'GET',
        dataType: 'json',
        success: function(res) {
            if (res.success && res.plan) {
                var p = res.plan;
                $('#planNameInput').val(p.name);
                $('#planSlugInput').val(p.slug);
                $('#planDescInput').val(p.description || '');
                $('#planPriceInput').val(parseFloat(p.price).toFixed(2));
                $('#planCurrencyInput').val(p.currency || 'PKR');
                $('#planIntervalInput').val(p.billing_interval || 'month');
                $('#planDurationInput').val(p.duration_days || 30);
                $('#planMaxMatchesInput').val(p.max_matches !== null ? p.max_matches : '');
                $('#planStatusInput').val(p.status || 'active');

                $('#featureWhatsapp').prop('checked', parseInt(p.whatsapp_alerts) === 1);
                $('#featureEmail').prop('checked', parseInt(p.email_alerts) === 1);
                $('#featureDeadlines').prop('checked', parseInt(p.deadline_reminders) === 1);
                $('#featureTracking').prop('checked', parseInt(p.application_tracking) === 1);

                $('#planModal').addClass('active');
                if (typeof lucide !== 'undefined') lucide.createIcons();
            } else {
                alert(res.error || 'Failed to load plan details.');
            }
        },
        error: function(xhr) {
            alert('Failed to load plan: ' + (xhr.responseJSON?.error || 'Unknown error'));
        }
    });
}

function closePlanModal() {
    $('#planModal').removeClass('active');
}

function submitPlanForm(e) {
    e.preventDefault();
    var alertBox = $('#planModalAlert');
    var btn = $('#btnSubmitPlan');
    var encodedId = $('#planFormId').val();

    var targetUrl = isEditingMode && encodedId 
        ? '<?= url("/admin/plans/") ?>' + encodedId + '/update'
        : '<?= url("/admin/plans") ?>';

    btn.prop('disabled', true).css('opacity', '0.7');
    alertBox.hide().removeClass('bg-danger bg-success');

    $.ajax({
        url: targetUrl,
        type: 'POST',
        data: $('#planForm').serialize(),
        dataType: 'json',
        success: function(res) {
            btn.prop('disabled', false).css('opacity', '1');
            if (res.success) {
                closePlanModal();
                showPageToast(res.message || 'Plan saved successfully!', 'success');
                reloadPlansTable();
            } else {
                alertBox.show().css({
                    'background-color': '#fef2f2',
                    'border': '1px solid #fecaca',
                    'color': '#dc2626'
                }).html('<i data-lucide="alert-circle" style="width:14px;height:14px;display:inline-block;vertical-align:middle;margin-right:4px;"></i> ' + (res.error || 'Operation failed.'));
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }
        },
        error: function(xhr) {
            btn.prop('disabled', false).css('opacity', '1');
            var msg = xhr.responseJSON?.error || 'An unexpected error occurred.';
            alertBox.show().css({
                'background-color': '#fef2f2',
                'border': '1px solid #fecaca',
                'color': '#dc2626'
            }).html('<i data-lucide="alert-circle" style="width:14px;height:14px;display:inline-block;vertical-align:middle;margin-right:4px;"></i> ' + msg);
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    });
}

function togglePlanStatus(encodedId, planName) {
    adminConfirm({
        title: 'Change Plan Status',
        message: 'Are you sure you want to change the active status of plan <strong>"' + adminEscapeHtml(planName) + '"</strong>?',
        subtext: 'Toggling will enable or disable public subscription to this tier.',
        confirmText: 'Yes, Change Status',
        confirmClass: 'btn-primary',
        icon: 'toggle-left'
    }, function() {
        $.ajax({
            url: '<?= url("/admin/plans/") ?>' + encodedId + '/toggle-status',
            type: 'POST',
            data: {
                csrf_token: '<?= e($csrf_token ?? '') ?>'
            },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    showPageToast(res.message || 'Status updated successfully.', 'success');
                    reloadPlansTable();
                } else {
                    showPageToast(res.error || 'Failed to toggle status.', 'error');
                }
            },
            error: function(xhr) {
                showPageToast(xhr.responseJSON?.error || 'Failed to toggle status.', 'error');
            }
        });
    });
}

function deletePlan(encodedId, planName) {
    adminConfirm({
        title: 'Delete Subscription Plan',
        message: 'Are you sure you want to delete plan <strong>"' + adminEscapeHtml(planName) + '"</strong>?',
        subtext: 'This action cannot be undone. Note: Plans with existing subscribers cannot be deleted.',
        confirmText: 'Yes, Delete Plan',
        confirmClass: 'btn-danger',
        icon: 'trash-2'
    }, function() {
        $.ajax({
            url: '<?= url("/admin/plans/") ?>' + encodedId + '/delete',
            type: 'POST',
            data: {
                csrf_token: '<?= e($csrf_token ?? '') ?>'
            },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    showPageToast(res.message || 'Plan deleted successfully.', 'success');
                    reloadPlansTable();
                } else {
                    showPageToast(res.error || 'Failed to delete plan.', 'error');
                }
            },
            error: function(xhr) {
                showPageToast(xhr.responseJSON?.error || 'Failed to delete plan.', 'error');
            }
        });
    });
}

function showPageToast(msg, type) {
    var alertBox = $('#plansPageAlert');
    var isSuccess = (type === 'success');
    alertBox.css({
        'display': 'flex',
        'align-items': 'center',
        'gap': '10px',
        'background-color': isSuccess ? '#dcfce7' : '#fef2f2',
        'border': isSuccess ? '1px solid #bbf7d0' : '1px solid #fecaca',
        'color': isSuccess ? '#15803d' : '#dc2626'
    }).html((isSuccess ? '<i data-lucide="check-circle" style="width:18px;height:18px;flex-shrink:0;"></i>' : '<i data-lucide="alert-circle" style="width:18px;height:18px;flex-shrink:0;"></i>') + '<span>' + msg + '</span>');
    if (typeof lucide !== 'undefined') lucide.createIcons();

    setTimeout(function() {
        alertBox.fadeOut();
    }, 6000);
}

$(document).ready(function() {
    window.plansTable = ScholarPlannerDataTable('#plans-datatable', {
        ajax: {
            url: '<?= url("/admin/plans/data") ?>',
            type: 'GET',
            data: function(d) {
                d.status = $('#statusFilter').val();
            }
        },
        columns: [
            {
                data: 'name',
                render: function(data, type, row) {
                    var desc = row.description ? '<div style="font-size: 0.75rem; color: #64748b; margin-top: 2px;">' + row.description + '</div>' : '';
                    return '<div style="font-weight: 700; color: #0f172a;">' + data + '</div>' +
                           '<div style="font-size: 0.72rem; color: #94a3b8; font-family: monospace;">slug: ' + row.slug + '</div>' +
                           desc;
                }
            },
            {
                data: 'formatted_price',
                render: function(data, type, row) {
                    var color = (parseFloat(row.price) > 0) ? '#0f172a' : '#16a34a';
                    return '<span style="font-weight: 700; color: ' + color + ';">' + data + '</span>';
                }
            },
            {
                data: 'billing_interval',
                render: function(data, type, row) {
                    var intervalDisplay = (data || 'month').charAt(0).toUpperCase() + (data || 'month').slice(1);
                    return '<div><strong>' + intervalDisplay + '</strong></div><div style="font-size: 0.75rem; color: #64748b;">' + (row.duration_days || 30) + ' Days</div>';
                }
            },
            {
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    var html = '<div style="display: flex; flex-wrap: wrap; max-width: 250px;">';
                    if (parseInt(row.whatsapp_alerts) === 1) {
                        html += '<span class="feature-pill enabled"><i data-lucide="message-square" style="width:10px;height:10px;"></i> WhatsApp</span>';
                    }
                    if (parseInt(row.email_alerts) === 1) {
                        html += '<span class="feature-pill enabled"><i data-lucide="mail" style="width:10px;height:10px;"></i> Email</span>';
                    }
                    if (parseInt(row.deadline_reminders) === 1) {
                        html += '<span class="feature-pill enabled"><i data-lucide="calendar" style="width:10px;height:10px;"></i> Deadlines</span>';
                    }
                    if (parseInt(row.application_tracking) === 1) {
                        html += '<span class="feature-pill enabled"><i data-lucide="clipboard-list" style="width:10px;height:10px;"></i> Tracking</span>';
                    }
                    if (row.max_matches !== null && row.max_matches !== '') {
                        html += '<span class="feature-pill"><i data-lucide="sparkles" style="width:10px;height:10px;"></i> ' + row.max_matches + ' Matches</span>';
                    } else {
                        html += '<span class="feature-pill enabled"><i data-lucide="infinity" style="width:10px;height:10px;"></i> Unlimited</span>';
                    }
                    html += '</div>';
                    return html;
                }
            },
            {
                data: 'subscriber_count',
                render: function(data) {
                    var count = parseInt(data) || 0;
                    return '<span style="font-weight: 700; color: ' + (count > 0 ? '#2563eb' : '#94a3b8') + ';">' + count + ' Active</span>';
                }
            },
            {
                data: 'status',
                render: function(data) {
                    if (data === 'active') {
                        return '<span class="status-badge active"><i data-lucide="check" style="width:12px;height:12px;"></i> Active</span>';
                    }
                    return '<span class="status-badge inactive"><i data-lucide="power" style="width:12px;height:12px;"></i> Inactive</span>';
                }
            },
            {
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    var actions = '<div style="display: flex; justify-content: flex-end; gap: 6px; flex-wrap: nowrap;">';
                    actions += '<button type="button" class="action-btn-sm edit" onclick="openEditPlanModal(\'' + row.encoded_id + '\')" title="Edit Plan"><i data-lucide="pencil" style="width:12px;height:12px;"></i> Edit</button>';
                    actions += '<button type="button" class="action-btn-sm toggle" onclick="togglePlanStatus(\'' + row.encoded_id + '\', \'' + escapeAttr(row.name) + '\')" title="Toggle Status"><i data-lucide="power" style="width:12px;height:12px;"></i> Status</button>';
                    if (row.slug !== 'free' && (!row.subscriber_count || parseInt(row.subscriber_count) === 0)) {
                        actions += '<button type="button" class="action-btn-sm delete" onclick="deletePlan(\'' + row.encoded_id + '\', \'' + escapeAttr(row.name) + '\')" title="Delete Plan"><i data-lucide="trash-2" style="width:12px;height:12px;"></i></button>';
                    }
                    actions += '</div>';
                    return actions;
                }
            }
        ],
        drawCallback: function() {
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    });

    // Close modal on Escape key
    $(document).keyup(function(e) {
        if (e.key === "Escape") {
            closePlanModal();
        }
    });
});
</script>

<?php include ROOT_PATH . '/app/Views/layouts/admin_footer.php'; ?>
