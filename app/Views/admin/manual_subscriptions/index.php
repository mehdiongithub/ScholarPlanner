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

/* Tab Navigation */
.user-plans-tabs {
    display: flex;
    gap: 10px;
    border-bottom: 2px solid #e2e8f0;
    margin-bottom: 24px;
}
.user-plans-tabs .tab-btn {
    background: none;
    border: none;
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
    padding: 12px 18px;
    font-size: 0.875rem;
    font-weight: 600;
    color: #64748b;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
}
.user-plans-tabs .tab-btn:hover {
    color: #0f172a;
    border-bottom-color: #cbd5e1;
}
.user-plans-tabs .tab-btn.active {
    color: #2563eb;
    border-bottom-color: #2563eb;
}
.user-plans-tabs .tab-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 2px 8px;
    font-size: 0.75rem;
    font-weight: 700;
    border-radius: 9999px;
    background-color: #f1f5f9;
    color: #475569;
}
.user-plans-tabs .tab-btn.active .tab-badge {
    background-color: #eff6ff;
    color: #1d4ed8;
}

.status-badge.free {
    background-color: #f1f5f9;
    color: #475569;
    border: 1px solid #cbd5e1;
}
.status-badge.pending {
    background-color: #fef3c7;
    color: #92400e;
    border: 1px solid #fde68a;
}
.status-badge.activated {
    background-color: #dcfce7;
    color: #166534;
    border: 1px solid #bbf7d0;
}
.status-badge.revoked {
    background-color: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}
.status-badge.expired {
    background-color: #f1f5f9;
    color: #64748b;
    border: 1px solid #cbd5e1;
}

.action-btn-sm {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 11px;
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: 6px;
    text-decoration: none;
    cursor: pointer;
    border: 1px solid transparent;
    transition: all 0.15s ease;
}
.action-btn-sm.activate {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    color: #ffffff !important;
    border-color: #1d4ed8;
    box-shadow: 0 2px 4px rgba(37,99,235,0.2);
}
.action-btn-sm.activate:hover {
    background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
    color: #ffffff !important;
}
.action-btn-sm.copy {
    background-color: #f8fafc;
    color: #334155;
    border-color: #cbd5e1;
}
.action-btn-sm.copy:hover {
    background-color: #e2e8f0;
    color: #0f172a;
}
.action-btn-sm.resend {
    background-color: #eff6ff;
    color: #1d4ed8;
    border-color: #bfdbfe;
}
.action-btn-sm.resend:hover {
    background-color: #dbeafe;
}
.action-btn-sm.revoke {
    background-color: #fef2f2;
    color: #b91c1c;
    border-color: #fecaca;
}
.action-btn-sm.revoke:hover {
    background-color: #fee2e2;
}

.modal-overlay {
    position: fixed;
    inset: 0;
    background-color: rgba(15, 23, 42, 0.65);
    backdrop-filter: blur(4px);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 99999;
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
    max-width: 560px;
    box-shadow: 0 20px 25px -5px rgba(0,0,0,0.15), 0 10px 10px -5px rgba(0,0,0,0.06);
    overflow: hidden;
    max-height: 85vh;
    display: flex;
    flex-direction: column;
    position: relative;
    animation: modalIn 0.2s ease-out forwards;
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
@keyframes modalIn {
    from { opacity: 0; transform: scale(0.96) translateY(10px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
@keyframes slideInRight {
    from { opacity: 0; transform: translateX(30px); }
    to { opacity: 1; transform: translateX(0); }
}
.spin {
    animation: spin 1s linear infinite;
    display: inline-block;
}
@keyframes spin {
    100% { transform: rotate(360deg); }
}
</style>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <div>
        <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0; color: #1e293b;">Active User Plans</h1>
        <p style="margin: 4px 0 0 0; color: #64748b; font-size: 0.875rem;">
            View unsubscribed users, grant complimentary plans, and send instant one-click activation links.
        </p>
    </div>
    <div>
        <button type="button" class="btn btn-primary" onclick="openGrantModal()" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; font-weight: 600;">
            <i data-lucide="sparkles" style="width: 18px; height: 18px;"></i>
            <span>+ Grant Plan to Student</span>
        </button>
    </div>
</div>

<!-- Metrics Cards -->
<div class="metric-cards-grid">
    <div class="metric-card">
        <div class="metric-icon" style="background-color: #fef3c7; color: #d97706;">
            <i data-lucide="user-x" style="width: 24px; height: 24px;"></i>
        </div>
        <div class="metric-info">
            <h3><?= number_format($stats['unsubscribed'] ?? 0) ?></h3>
            <p>Unsubscribed Users</p>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-icon" style="background-color: #eff6ff; color: #2563eb;">
            <i data-lucide="clock" style="width: 24px; height: 24px;"></i>
        </div>
        <div class="metric-info">
            <h3><?= number_format($stats['pending'] ?? 0) ?></h3>
            <p>Pending Activation</p>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-icon" style="background-color: #dcfce7; color: #16a34a;">
            <i data-lucide="check-circle-2" style="width: 24px; height: 24px;"></i>
        </div>
        <div class="metric-info">
            <h3><?= number_format($stats['activated'] ?? 0) ?></h3>
            <p>Active & Running Plans</p>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-icon" style="background-color: #f3e8ff; color: #7c3aed;">
            <i data-lucide="layers" style="width: 24px; height: 24px;"></i>
        </div>
        <div class="metric-info">
            <h3><?= number_format($stats['total'] ?? 0) ?></h3>
            <p>Total Grants Issued</p>
        </div>
    </div>
</div>

<!-- Tab Navigation -->
<div class="user-plans-tabs">
    <button type="button" class="tab-btn active" id="tabUnsubscribedBtn" onclick="switchPlanTab('unsubscribed')">
        <i data-lucide="user-x" style="width: 16px; height: 16px;"></i>
        <span>Users Without Subscription (Unsubscribed)</span>
        <span class="tab-badge" id="badgeUnsubscribedCount"><?= number_format($stats['unsubscribed'] ?? 0) ?></span>
    </button>
    <button type="button" class="tab-btn" id="tabGrantsBtn" onclick="switchPlanTab('grants')">
        <i data-lucide="badge-check" style="width: 16px; height: 16px;"></i>
        <span>Active & Granted Plans History</span>
        <span class="tab-badge" id="badgeGrantsCount"><?= number_format($stats['total'] ?? 0) ?></span>
    </button>
</div>

<!-- TAB 1: UNSUBSCRIBED USERS -->
<div id="unsubscribedTabPanel">
    <div class="data-table-card">
        <div style="padding: 16px 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <div>
                <h3 style="margin: 0; font-size: 1rem; font-weight: 700; color: #0f172a;">Unsubscribed Registered Students</h3>
                <p style="margin: 2px 0 0 0; font-size: 0.75rem; color: #64748b;">
                    These users have not purchased a subscription. You can manually activate a plan and dispatch an activation link directly to them.
                </p>
            </div>
            <button type="button" onclick="window.unsubTable.ajax.reload(null, false)" class="btn" style="background: #f8fafc; border: 1px solid #cbd5e1; font-size: 0.75rem; padding: 6px 12px; display: inline-flex; align-items: center; gap: 4px;">
                <i data-lucide="refresh-cw" style="width: 13px; height: 13px;"></i>
                <span>Refresh</span>
            </button>
        </div>
        <div style="overflow-x: auto;">
            <table class="employees-table" id="unsubscribed-users-datatable" style="width:100%">
                <thead>
                    <tr>
                        <th>Student Details</th>
                        <th>Joined Date</th>
                        <th>Status</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- TAB 2: ACTIVE & GRANTED PLANS -->
<div id="grantsTabPanel" style="display: none;">
    <!-- Status Filter -->
    <div class="filter-card">
        <form class="filter-form" id="manual-filter-form" onsubmit="event.preventDefault(); window.manualTable.draw();">
            <div class="form-group" style="margin: 0; min-width: 220px;">
                <label class="form-label" style="font-size: 0.8125rem; font-weight: 600;">Status</label>
                <select name="status" class="form-control" id="status-filter">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending Activation</option>
                    <option value="activated">Activated & Running</option>
                    <option value="revoked">Revoked</option>
                    <option value="expired">Expired</option>
                </select>
            </div>

            <div style="align-self: flex-end;">
                <button type="submit" class="btn btn-primary" style="padding: 10px 18px;">
                    <i data-lucide="filter" style="width: 16px; height: 16px;"></i>
                    <span>Filter</span>
                </button>
            </div>
        </form>
    </div>

    <!-- DataTable Grid -->
    <div class="data-table-card">
        <div style="overflow-x: auto;">
            <table class="employees-table" id="manual-subs-datatable" style="width:100%">
                <thead>
                    <tr>
                        <th>Student Details</th>
                        <th>Plan & Duration</th>
                        <th>Status</th>
                        <th>Validity Period</th>
                        <th>Issued Date</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Grant Subscription Modal (Custom / Manual from header) -->
<div id="grantModal" class="modal-overlay" style="display: none;">
    <div class="modal-box">
        <div class="modal-box-header">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 36px; height: 36px; border-radius: 8px; background-color: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center;">
                    <i data-lucide="sparkles" style="width: 20px; height: 20px;"></i>
                </div>
                <div>
                    <h2 style="margin: 0; font-size: 1.125rem; font-weight: 700; color: #0f172a;">Grant Subscription Plan</h2>
                    <p style="margin: 2px 0 0 0; font-size: 0.75rem; color: #64748b;">Timer starts when the student clicks the link</p>
                </div>
            </div>
            <button type="button" onclick="closeGrantModal()" style="background: none; border: none; cursor: pointer; color: #94a3b8; padding: 4px;">
                <i data-lucide="x" style="width: 20px; height: 20px;"></i>
            </button>
        </div>

        <form id="grantSubscriptionForm" onsubmit="submitGrantForm(event)">
            <input type="hidden" name="csrf_token" value="<?= e($csrf_token ?? '') ?>">
            <input type="hidden" name="user_id" id="targetUserIdInput" value="">

            <div class="modal-body-scroll">
                <!-- General Student Select -->
                <div id="generalStudentSelectGroup" class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label" style="font-weight: 600; font-size: 0.8125rem;">Select Student <span style="color: #ef4444;">*</span></label>
                    <select name="fallback_user_id" id="fallbackUserSelect" class="form-control" style="width: 100%;" onchange="$('#targetUserIdInput').val(this.value)">
                        <option value="">-- Choose a student user --</option>
                        <?php foreach ($students as $stu): ?>
                            <option value="<?= (int)$stu['id'] ?>">
                                <?= e(trim($stu['first_name'] . ' ' . $stu['last_name'])) ?> (<?= e($stu['email']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Alert Container inside modal -->
                <div id="modalAlert" style="display: none; margin-bottom: 16px; padding: 12px 16px; border-radius: 8px; font-size: 0.875rem;"></div>

                <!-- Plan Select -->
                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label" style="font-weight: 600; font-size: 0.8125rem;">Subscription Plan <span style="color: #ef4444;">*</span></label>
                    <select name="plan_id" id="planSelect" class="form-control" required onchange="onPlanChange(this)">
                        <option value="">-- Choose tier --</option>
                        <?php foreach ($plans as $p): ?>
                            <option value="<?= (int)$p['id'] ?>" data-duration="<?= (int)($p['duration_days'] ?: 30) ?>">
                                <?= e($p['name']) ?> (<?= e($p['currency'] ?? 'PKR') ?> <?= number_format($p['price'], 2) ?> &bull; <?= (int)($p['duration_days'] ?: 30) ?> Days)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Duration in Days -->
                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label" style="font-weight: 600; font-size: 0.8125rem;">Duration (Days) <span style="color: #ef4444;">*</span></label>
                    <input type="number" name="duration_days" id="durationDaysInput" class="form-control" value="30" min="1" max="3650" required>
                    <small style="display: block; margin-top: 4px; color: #64748b; font-size: 0.75rem;">
                        The user will have active access for this exact number of days after opening the activation URL.
                    </small>
                </div>

                <!-- Admin Notes / Reason -->
                <div class="form-group" style="margin-bottom: 20px;">
                    <label class="form-label" style="font-weight: 600; font-size: 0.8125rem;">Admin Note / Reason (Optional)</label>
                    <textarea name="admin_notes" class="form-control" rows="2" placeholder="e.g. Complimentary grant, incentive, VIP access..."></textarea>
                </div>

                <!-- Send Email Checkbox -->
                <div style="margin-bottom: 24px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 16px; display: flex; align-items: flex-start; gap: 10px;">
                    <input type="checkbox" name="send_email" id="sendEmailCheckbox" value="1" checked style="margin-top: 3px; cursor: pointer; width: 16px; height: 16px;">
                    <label for="sendEmailCheckbox" style="font-size: 0.8125rem; color: #334155; cursor: pointer; margin: 0; line-height: 1.4;">
                        <strong>Send activation email to student</strong><br>
                        <span style="color: #64748b; font-size: 0.75rem;">Dispatches a branded email via background queue containing the one-click activation link.</span>
                    </label>
                </div>

                <!-- Result Box after creation -->
                <div id="grantSuccessBox" style="display: none; margin-bottom: 20px; background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 16px;">
                    <div style="display: flex; align-items: center; gap: 8px; color: #166534; font-weight: 700; margin-bottom: 8px; font-size: 0.875rem;">
                        <i data-lucide="check-circle" style="width: 18px; height: 18px;"></i>
                        <span>Plan Granted Successfully!</span>
                    </div>
                    <p style="margin: 0 0 10px 0; font-size: 0.8125rem; color: #166534;">
                        You can copy the activation link below and share it directly with the student (the countdown starts only when they click it):
                    </p>
                    <div style="display: flex; gap: 8px;">
                        <input type="text" id="generatedLinkInput" readonly style="flex: 1; padding: 8px 12px; font-size: 0.75rem; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; color: #334155;">
                        <button type="button" onclick="copyGeneratedLink()" class="btn btn-primary" style="padding: 8px 14px; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 4px;">
                            <i data-lucide="copy" style="width: 14px; height: 14px;"></i>
                            <span id="copyBtnText">Copy</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="modal-box-footer">
                <button type="button" class="btn" onclick="closeGrantModal()" style="background-color: #f1f5f9; color: #475569; padding: 10px 18px; font-weight: 600;">
                    Close
                </button>
                <button type="submit" id="submitGrantBtn" class="btn btn-primary" style="padding: 10px 22px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                    <i data-lucide="send" style="width: 16px; height: 16px;"></i>
                    <span>Issue Grant & Link</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Dedicated Activate Plan Confirmation Popup Modal -->
<div id="confirmActivateModal" class="modal-overlay" style="display: none;">
    <div class="modal-box" style="max-width: 520px;">
        <div class="modal-box-header">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div style="width: 44px; height: 44px; border-radius: 12px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i data-lucide="sparkles" style="width: 22px; height: 22px;"></i>
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 1.15rem; font-weight: 700; color: #0f172a;" id="confirmModalTitle">Activate Subscription Plan</h3>
                    <p style="margin: 2px 0 0 0; font-size: 0.8rem; color: #64748b;" id="confirmModalQuestion">
                        Are you sure you want to activate a subscription plan for <strong id="confirmStudentName" style="color: #1e40af;">Student</strong>?
                    </p>
                </div>
            </div>
            <button type="button" onclick="closeConfirmModal()" style="background: none; border: none; cursor: pointer; color: #94a3b8; padding: 4px;">
                <i data-lucide="x" style="width: 20px; height: 20px;"></i>
            </button>
        </div>

        <form id="confirmActivateForm" onsubmit="submitConfirmActivation(event)">
            <input type="hidden" name="csrf_token" value="<?= e($csrf_token ?? '') ?>">
            <input type="hidden" name="user_id" id="confirmUserIdInput" value="">
            <input type="hidden" name="send_email" value="1">

            <div class="modal-body-scroll">
                <!-- Target Student Summary Card -->
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px; margin-bottom: 16px; display: flex; align-items: center; gap: 12px;">
                    <div style="width: 34px; height: 34px; border-radius: 50%; background: #2563eb; color: #ffffff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.8125rem; flex-shrink: 0;">
                        <i data-lucide="user" style="width: 16px; height: 16px;"></i>
                    </div>
                    <div style="min-width: 0; flex: 1;">
                        <div id="confirmStudentSummary" style="font-weight: 700; font-size: 0.9375rem; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Student Name</div>
                        <div id="confirmStudentEmail" style="font-size: 0.8125rem; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">email@example.com</div>
                    </div>
                </div>

                <!-- Plan Selection Dropdown -->
                <div class="form-group" style="margin-bottom: 14px;">
                    <label class="form-label" style="font-weight: 600; font-size: 0.8125rem; color: #334155;">Subscription Plan <span style="color: #ef4444;">*</span></label>
                    <select name="plan_id" id="confirmPlanSelect" class="form-control no-select2" required onchange="onConfirmPlanChange(this)">
                        <?php 
                        $selectedAlready = false;
                        foreach ($plans as $p): 
                            $isPaidDefault = (!$selectedAlready && ((float)$p['price'] > 0 || count($plans) === 1));
                            if ($isPaidDefault) { $selectedAlready = true; }
                        ?>
                            <option value="<?= (int)$p['id'] ?>" data-duration="<?= (int)($p['duration_days'] ?: 30) ?>" <?= $isPaidDefault ? 'selected' : '' ?>>
                                <?= e($p['name']) ?> (<?= e($p['currency'] ?? 'PKR') ?> <?= number_format($p['price'], 2) ?> &bull; <?= (int)($p['duration_days'] ?: 30) ?> Days)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Duration (Days) -->
                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label" style="font-weight: 600; font-size: 0.8125rem; color: #334155;">Duration (Days) <span style="color: #ef4444;">*</span></label>
                    <input type="number" name="duration_days" id="confirmDurationDaysInput" class="form-control" value="30" min="1" max="3650" required>
                </div>

                <!-- Information Callout -->
                <div style="background-color: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 12px 14px; margin-bottom: 18px; display: flex; align-items: flex-start; gap: 10px;">
                    <i data-lucide="mail" style="width: 16px; height: 16px; color: #2563eb; flex-shrink: 0; margin-top: 2px;"></i>
                    <div style="font-size: 0.775rem; color: #1e40af; line-height: 1.45;">
                        On confirmation, an activation email with a secure link will be <strong>queued and sent to the student</strong>. Their subscription countdown begins strictly when they click the email link.
                    </div>
                </div>

                <div id="confirmModalAlert" style="display: none; margin-bottom: 16px; padding: 10px 14px; border-radius: 8px; font-size: 0.8125rem;"></div>
            </div>

            <!-- Modal Footer Buttons -->
            <div class="modal-box-footer">
                <button type="button" class="btn" onclick="closeConfirmModal()" style="background-color: #f1f5f9; color: #475569; padding: 10px 18px; font-weight: 600; font-size: 0.875rem;">
                    Cancel
                </button>
                <button type="submit" id="btnConfirmActivateSubmit" class="btn btn-primary" style="padding: 10px 22px; font-weight: 600; font-size: 0.875rem; display: inline-flex; align-items: center; gap: 6px;">
                    <i data-lucide="send" style="width: 15px; height: 15px;"></i>
                    <span>Yes, Activate & Send Email</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    // 1. Unsubscribed Users Table (Default view)
    window.unsubTable = ScholarPlannerDataTable('#unsubscribed-users-datatable', {
        ajax: {
            url: '<?= url("/admin/manual-subscriptions/data?view=unsubscribed") ?>',
            type: 'GET'
        },
        columns: [
            {
                data: 'student_name',
                render: function(data, type, row) {
                    var phoneHtml = row.phone ? ' &bull; ' + row.phone : '';
                    return '<strong>' + data + '</strong><div style="font-size: 0.75rem; color: #64748b;">' + row.email + phoneHtml + '</div>';
                }
            },
            {
                data: 'joined_date_display',
                render: function(data) {
                    return '<span style="font-size: 0.8125rem; color: #475569;">' + data + '</span>';
                }
            },
            {
                data: 'has_pending_grant',
                render: function(data, type, row) {
                    if (data) {
                        return '<span class="status-badge pending">Activation Link Sent</span>' +
                               '<div style="font-size: 0.72rem; color: #64748b; margin-top: 3px;">' + (row.pending_plan_name || 'Plan') + ' (' + row.pending_duration + ' days)</div>';
                    }
                    return '<span class="status-badge free">No Active Plan</span>';
                }
            },
            {
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    var actions = '<div style="display: flex; justify-content: flex-end; gap: 6px; flex-wrap: wrap;">';
                    
                    if (row.has_pending_grant && row.activation_url) {
                        actions += '<button type="button" class="action-btn-sm copy" onclick="copyActivationUrl(\'' + row.activation_url + '\', this)" title="Copy activation link"><i data-lucide="copy" style="width:12px;height:12px;"></i> Copy Link</button>';
                        actions += '<button type="button" class="action-btn-sm resend" onclick="resendActivationEmail(\'' + row.pending_grant_encoded + '\')" title="Resend email"><i data-lucide="mail" style="width:12px;height:12px;"></i> Resend</button>';
                        actions += '<button type="button" class="action-btn-sm activate btn-open-activate-confirm" data-user-id="' + row.user_id_encoded + '" data-student-name="' + escapeAttr(row.student_name) + '" data-student-email="' + escapeAttr(row.email) + '" data-is-change="1" title="Send new or change plan"><i data-lucide="sparkles" style="width:12px;height:12px;"></i> Change Plan</button>';
                    } else {
                        actions += '<button type="button" class="action-btn-sm activate btn-open-activate-confirm" data-user-id="' + row.user_id_encoded + '" data-student-name="' + escapeAttr(row.student_name) + '" data-student-email="' + escapeAttr(row.email) + '" data-is-change="0"><i data-lucide="sparkles" style="width:12px;height:12px;"></i> + Activate Plan</button>';
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

    // 2. Grants History Table
    window.manualTable = ScholarPlannerDataTable('#manual-subs-datatable', {
        ajax: {
            url: '<?= url("/admin/manual-subscriptions/data?view=grants") ?>',
            type: 'GET',
            data: function(d) {
                d.status = $('#status-filter').val();
            }
        },
        columns: [
            {
                data: 'student_name',
                render: function(data, type, row) {
                    return '<strong>' + data + '</strong><div style="font-size: 0.75rem; color: #64748b;">' + row.email + '</div>';
                }
            },
            {
                data: 'plan_display',
                render: function(data, type, row) {
                    var notes = row.admin_notes ? '<div style="font-size: 0.7rem; color: #94a3b8; font-style: italic;">"' + row.admin_notes + '"</div>' : '';
                    return '<strong>' + data + '</strong>' + notes;
                }
            },
            {
                data: 'status',
                render: function(data, type, row) {
                    var label = data.charAt(0).toUpperCase() + data.slice(1);
                    if (data === 'pending') label = 'Pending Activation';
                    return '<span class="status-badge ' + data + '">' + label + '</span>';
                }
            },
            {
                data: 'validity_display',
                render: function(data, type, row) {
                    if (row.status === 'activated') {
                        return '<div style="font-weight: 600; color: #16a34a; font-size: 0.8125rem;">' + data + '</div>' +
                               '<div style="font-size: 0.7rem; color: #64748b;">Activated: ' + (row.activated_at ? new Date(row.activated_at).toLocaleDateString() : '-') + '</div>';
                    } else if (row.status === 'pending') {
                        return '<span style="color: #d97706; font-size: 0.8125rem; font-weight: 500;">Timer starts upon click (' + row.duration_days + ' days)</span>';
                    }
                    return '<span style="color: #94a3b8; font-size: 0.8125rem;">N/A</span>';
                }
            },
            {
                data: 'created_at',
                render: function(data, type, row) {
                    if (!data) return '';
                    var d = new Date(data);
                    return '<span style="font-size: 0.8125rem; color: #475569;">' + 
                           d.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' }) + 
                           '</span>';
                }
            },
            {
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    var actions = '<div style="display: flex; justify-content: flex-end; gap: 6px; flex-wrap: wrap;">';
                    
                    if (row.status === 'pending') {
                        actions += '<button type="button" class="action-btn-sm copy" onclick="copyActivationUrl(\'' + row.activation_url + '\', this)" title="Copy activation link"><i data-lucide="copy" style="width:12px;height:12px;"></i> Copy Link</button>';
                        actions += '<button type="button" class="action-btn-sm resend" onclick="resendActivationEmail(\'' + row.record_id + '\')" title="Resend email"><i data-lucide="mail" style="width:12px;height:12px;"></i> Resend</button>';
                        actions += '<button type="button" class="action-btn-sm revoke" onclick="revokeGrant(\'' + row.record_id + '\')" title="Revoke link"><i data-lucide="trash-2" style="width:12px;height:12px;"></i> Revoke</button>';
                    } else if (row.status === 'activated') {
                        actions += '<span style="font-size: 0.75rem; color: #16a34a; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;"><i data-lucide="check" style="width: 14px; height: 14px;"></i> Active</span>';
                    } else {
                        actions += '<span style="font-size: 0.75rem; color: #94a3b8;">' + row.status + '</span>';
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

    // Delegated click handler for + Activate Plan and Change Plan buttons
    $(document).on('click', '.btn-open-activate-confirm', function(e) {
        e.preventDefault();
        var userId = $(this).attr('data-user-id');
        var studentName = $(this).attr('data-student-name');
        var studentEmail = $(this).attr('data-student-email');
        var isChange = $(this).attr('data-is-change') === '1';

        openActivateConfirmModal(userId, studentName, studentEmail, isChange);
    });
});

function switchPlanTab(tab) {
    if (tab === 'unsubscribed') {
        $('#tabUnsubscribedBtn').addClass('active');
        $('#tabGrantsBtn').removeClass('active');
        $('#unsubscribedTabPanel').show();
        $('#grantsTabPanel').hide();
        window.unsubTable.columns.adjust().draw();
    } else {
        $('#tabGrantsBtn').addClass('active');
        $('#tabUnsubscribedBtn').removeClass('active');
        $('#grantsTabPanel').show();
        $('#unsubscribedTabPanel').hide();
        window.manualTable.columns.adjust().draw();
    }
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

/**
 * Open the Confirmation Popup asking:
 * "Are you sure you want to activate plan for {Student Name}?"
 */
function openActivateConfirmModal(userId, studentName, studentEmail, isChange) {
    $('#confirmActivateForm')[0].reset();
    $('#confirmUserIdInput').val(userId);
    $('#confirmStudentName').text(studentName);
    $('#confirmStudentSummary').text(studentName);
    $('#confirmStudentEmail').text(studentEmail);
    $('#confirmModalAlert').hide().text('');
    $('#btnConfirmActivateSubmit').prop('disabled', false).html('<i data-lucide="send" style="width:15px;height:15px;"></i> <span>Yes, Activate & Send Email</span>');

    if (isChange) {
        $('#confirmModalTitle').text('Change / Re-issue Plan');
        $('#confirmModalQuestion').html('Are you sure you want to change or re-issue a subscription plan for <strong style="color: #1e40af;">' + studentName + '</strong>? This will revoke any prior pending link and enqueue a fresh activation email.');
    } else {
        $('#confirmModalTitle').text('Activate Subscription Plan');
        $('#confirmModalQuestion').html('Are you sure you want to activate a subscription plan for <strong style="color: #1e40af;">' + studentName + '</strong>?');
    }

    var selOpt = $('#confirmPlanSelect').find(':selected');
    var dur = selOpt.data('duration') || 30;
    $('#confirmDurationDaysInput').val(dur);

    $('#confirmActivateModal').addClass('active').css('display', 'flex');
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function closeConfirmModal() {
    $('#confirmActivateModal').removeClass('active').css('display', 'none');
}

function onConfirmPlanChange(select) {
    var opt = $(select).find(':selected');
    var duration = opt.data('duration');
    if (duration) {
        $('#confirmDurationDaysInput').val(duration);
    }
}

/**
 * Handle confirmation form submission: activates plan and enqueues activation email
 */
function submitConfirmActivation(e) {
    e.preventDefault();
    var form = $('#confirmActivateForm');
    var btn = $('#btnConfirmActivateSubmit');
    var alertBox = $('#confirmModalAlert');

    btn.prop('disabled', true).html('<i data-lucide="loader" style="width:15px;height:15px;" class="spin"></i> <span>Activating & Queueing Email...</span>');
    alertBox.hide().removeClass('alert-danger alert-success');

    $.ajax({
        url: '<?= url("/admin/manual-subscriptions") ?>',
        type: 'POST',
        data: form.serialize(),
        dataType: 'json',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        success: function(res) {
            btn.prop('disabled', false).html('<i data-lucide="send" style="width:15px;height:15px;"></i> <span>Yes, Activate & Send Email</span>');
            if (res.success) {
                closeConfirmModal();

                var studentName = $('#confirmStudentName').text();
                showTopNotice(
                    'Plan activated successfully for <strong>' + studentName + '</strong>! Activation email has been queued for background delivery.',
                    res.activation_url
                );

                if (window.unsubTable) window.unsubTable.ajax.reload(null, false);
                if (window.manualTable) window.manualTable.ajax.reload(null, false);
            } else {
                alertBox.addClass('alert-danger').css({'background-color': '#fee2e2', 'color': '#991b1b'}).text(res.error || 'Failed to activate plan.').show();
            }
        },
        error: function(xhr) {
            btn.prop('disabled', false).html('<i data-lucide="send" style="width:15px;height:15px;"></i> <span>Yes, Activate & Send Email</span>');
            var errMsg = 'An unexpected error occurred. Please try again.';
            if (xhr.responseJSON && xhr.responseJSON.error) {
                errMsg = xhr.responseJSON.error;
            }
            alertBox.addClass('alert-danger').css({'background-color': '#fee2e2', 'color': '#991b1b'}).text(errMsg).show();
        }
    });
}

function showTopNotice(messageHtml, copyUrl) {
    $('#topNoticeBanner').remove();
    var copyBtnHtml = '';
    if (copyUrl) {
        copyBtnHtml = '<button type="button" class="btn" onclick="copyActivationUrl(\'' + copyUrl + '\', this)" style="background: #ffffff; color: #166534; border: 1px solid #86efac; font-size: 0.75rem; padding: 5px 12px; font-weight: 600; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px; margin-left: 10px; cursor: pointer;">' +
                      '<i data-lucide="copy" style="width: 13px; height: 13px;"></i> Copy Link</button>';
    }

    var banner = $('<div id="topNoticeBanner" style="position: fixed; top: 24px; right: 24px; z-index: 999999; max-width: 520px; background-color: #f0fdf4; border: 1px solid #86efac; border-radius: 10px; padding: 14px 18px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); display: flex; align-items: center; gap: 12px; animation: slideInRight 0.25s ease-out forwards;">' +
        '<div style="width: 32px; height: 32px; border-radius: 50%; background: #dcfce7; color: #16a34a; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">' +
            '<i data-lucide="check" style="width: 18px; height: 18px;"></i>' +
        '</div>' +
        '<div style="flex: 1; font-size: 0.875rem; color: #166534; line-height: 1.4;">' + messageHtml + '</div>' +
        copyBtnHtml +
        '<button type="button" onclick="$(this).closest(\'#topNoticeBanner\').fadeOut(200, function(){ $(this).remove(); })" style="background: none; border: none; cursor: pointer; color: #166534; padding: 4px; font-size: 1.1rem; line-height: 1;">&times;</button>' +
    '</div>');

    $('body').append(banner);
    if (typeof lucide !== 'undefined') lucide.createIcons();

    setTimeout(function() {
        if ($('#topNoticeBanner').length) {
            $('#topNoticeBanner').fadeOut(400, function() { $(this).remove(); });
        }
    }, 9000);
}

// Global Grant modal from header button "+ Grant Plan to Student"
function openGrantModal() {
    $('#grantSubscriptionForm')[0].reset();
    $('#targetUserIdInput').val('');
    $('#generalStudentSelectGroup').show();
    $('#modalAlert').hide().text('');
    $('#grantSuccessBox').hide();
    $('#submitGrantBtn').show().prop('disabled', false);
    $('#durationDaysInput').val(30);
    $('#grantModal').addClass('active').css('display', 'flex');
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function closeGrantModal() {
    $('#grantModal').removeClass('active').css('display', 'none');
}

function onPlanChange(select) {
    var opt = $(select).find(':selected');
    var duration = opt.data('duration');
    if (duration) {
        $('#durationDaysInput').val(duration);
    }
}

function submitGrantForm(e) {
    e.preventDefault();
    var form = $('#grantSubscriptionForm');
    var btn = $('#submitGrantBtn');
    var alertBox = $('#modalAlert');
    var successBox = $('#grantSuccessBox');

    var targetUser = $('#targetUserIdInput').val();
    if (!targetUser) {
        targetUser = $('#fallbackUserSelect').val();
        $('#targetUserIdInput').val(targetUser);
    }
    if (!targetUser) {
        alertBox.addClass('alert-danger').css({'background-color': '#fee2e2', 'color': '#991b1b'}).text('Please select a student user.').show();
        return;
    }

    btn.prop('disabled', true).html('<i data-lucide="loader" style="width:16px;height:16px;" class="spin"></i> Creating...');
    alertBox.hide().removeClass('alert-danger alert-success');

    $.ajax({
        url: '<?= url("/admin/manual-subscriptions") ?>',
        type: 'POST',
        data: form.serialize(),
        dataType: 'json',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        success: function(res) {
            btn.prop('disabled', false).html('<i data-lucide="send" style="width:16px;height:16px;"></i> Issue Grant & Link');
            if (res.success) {
                $('#generatedLinkInput').val(res.activation_url);
                successBox.slideDown();
                btn.hide();
                if (window.unsubTable) window.unsubTable.ajax.reload(null, false);
                if (window.manualTable) window.manualTable.ajax.reload(null, false);
                if (typeof lucide !== 'undefined') lucide.createIcons();
            } else {
                alertBox.addClass('alert-danger').css({'background-color': '#fee2e2', 'color': '#991b1b'}).text(res.error || 'Failed to create subscription grant.').show();
            }
        },
        error: function(xhr) {
            btn.prop('disabled', false).html('<i data-lucide="send" style="width:16px;height:16px;"></i> Issue Grant & Link');
            var errMsg = 'An unexpected error occurred. Please try again.';
            if (xhr.responseJSON && xhr.responseJSON.error) {
                errMsg = xhr.responseJSON.error;
            }
            alertBox.addClass('alert-danger').css({'background-color': '#fee2e2', 'color': '#991b1b'}).text(errMsg).show();
        }
    });
}

function copyGeneratedLink() {
    var copyText = document.getElementById("generatedLinkInput");
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(copyText.value).then(function() {
        $('#copyBtnText').text('Copied!');
        setTimeout(function() {
            $('#copyBtnText').text('Copy');
        }, 2000);
    });
}

function copyActivationUrl(url, btnElement) {
    navigator.clipboard.writeText(url).then(function() {
        var originalText = $(btnElement).html();
        $(btnElement).html('<i data-lucide="check" style="width:12px;height:12px;"></i> Copied!');
        setTimeout(function() {
            $(btnElement).html(originalText);
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }, 1800);
    });
}

function resendActivationEmail(recordId) {
    adminConfirm({
        title: 'Resend Activation Email',
        message: 'Are you sure you want to resend the subscription activation email to this student?',
        subtext: 'A secure activation link will be emailed to the student.',
        confirmText: 'Yes, Resend Email',
        confirmClass: 'btn-primary',
        icon: 'mail'
    }, function() {
        $.ajax({
            url: '<?= url("/admin/manual-subscriptions") ?>/' + recordId + '/resend',
            type: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    showTopNotice('Activation email queued for background delivery!', null);
                } else {
                    showTopNotice(res.error || 'Failed to resend email.', 'error');
                }
            },
            error: function(xhr) {
                showTopNotice(xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : 'Failed to resend activation email.', 'error');
            }
        });
    });
}

function revokeGrant(recordId) {
    adminConfirm({
        title: 'Revoke Activation Link',
        message: 'Are you sure you want to revoke this pending activation link?',
        subtext: 'The student will no longer be able to claim or activate this subscription.',
        confirmText: 'Yes, Revoke Link',
        confirmClass: 'btn-danger',
        icon: 'slash'
    }, function() {
        $.ajax({
            url: '<?= url("/admin/manual-subscriptions") ?>/' + recordId + '/revoke',
            type: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    if (window.unsubTable) window.unsubTable.ajax.reload(null, false);
                    if (window.manualTable) window.manualTable.ajax.reload(null, false);
                    showTopNotice('Activation link revoked successfully!', null);
                } else {
                    showTopNotice(res.error || 'Failed to revoke grant.', 'error');
                }
            },
            error: function(xhr) {
                showTopNotice(xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : 'Failed to revoke grant.', 'error');
            }
        });
    });
}

function escapeAttr(text) {
    if (!text) return '';
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}
</script>

<?php include ROOT_PATH . '/app/Views/layouts/admin_footer.php'; ?>
