<?php include ROOT_PATH . '/app/Views/layouts/student_header.php'; ?>
<script>
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>

<div style="margin-bottom: 24px;">
    <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0; color: #1e293b;">Notification & Deadline Reminder Settings</h1>
    <p style="margin: 4px 0 0 0; color: #64748b; font-size: 0.875rem;">Control matching alerts, deadline reminders, timing preferences, and view your notification history.</p>
</div>

<?php if (!empty($success_message)): ?>
    <div style="background-color:#dcfce7; border:1px solid #86efac; color:#166534; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-size:0.875rem; display:flex; align-items:center; gap:8px;">
        <i data-lucide="check-circle" style="width:18px; height:18px;"></i>
        <span><?= e($success_message) ?></span>
    </div>
<?php endif; ?>

<!-- 1. Notification Preferences Card -->
<div class="data-table-card" style="background:#fff; border:1px solid var(--border); border-radius:12px; padding:24px; margin-bottom: 24px;">
    <h2 style="font-size:1.125rem; font-weight:600; color:#1e293b; margin-top:0; margin-bottom:16px; display:flex; align-items:center; gap:8px;">
        <i data-lucide="settings" style="width:20px; height:20px; color:#3b82f6;"></i>
        Alert & Reminder Preferences
    </h2>

    <form method="POST" action="<?= url('/profile/notifications/update') ?>">
        <input type="hidden" name="csrf_token" value="<?= e($csrf_token ?? '') ?>">

        <!-- Section A: New Scholarship Alerts -->
        <div style="border-bottom:1px solid #f1f5f9; padding-bottom:18px; margin-bottom:18px;">
            <div style="font-weight:600; color:#334155; margin-bottom:6px;">New Matching Scholarship Notifications</div>
            <p style="font-size:0.8125rem; color:#64748b; margin-top:0; margin-bottom:12px;">
                Receive alerts when a newly published scholarship matches your eligibility criteria.
            </p>
            <div style="display:flex; gap:24px; flex-wrap:wrap;">
                <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; color:#1e293b;">
                    <input type="checkbox" name="new_match_email" value="1" <?= (!empty($prefMap['matching_scholarship_alerts']['email']) || !isset($prefMap['matching_scholarship_alerts'])) ? 'checked' : '' ?> style="width:16px; height:16px;">
                    <span>Email Alerts</span>
                </label>
                <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; color:#1e293b;">
                    <input type="checkbox" name="new_match_whatsapp" value="1" <?= !empty($prefMap['matching_scholarship_alerts']['whatsapp']) ? 'checked' : '' ?> style="width:16px; height:16px;">
                    <span>WhatsApp Alerts</span>
                </label>
            </div>
        </div>

        <!-- Section B: Delivery Channel Policy -->
        <div style="border-bottom:1px solid #f1f5f9; padding-bottom:18px; margin-bottom:18px;">
            <div style="font-weight:600; color:#334155; margin-bottom:6px;">Preferred Delivery Channel Policy</div>
            <p style="font-size:0.8125rem; color:#64748b; margin-top:0; margin-bottom:12px;">
                By default, ScholarMatch delivers exactly one notification per matching opportunity to your preferred channel to prevent duplicate messages.
            </p>
            <?php 
                $preferredChannel = $userPref['preferred_channel'] ?? 'email';
                $allowMultiChannel = (bool)($userPref['allow_multi_channel'] ?? 0);
            ?>
            <div style="display:flex; gap:24px; flex-wrap:wrap; margin-bottom:12px;">
                <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; color:#1e293b;">
                    <input type="radio" name="preferred_channel" value="email" <?= $preferredChannel === 'email' ? 'checked' : '' ?> style="width:16px; height:16px;">
                    <span>Email (Default)</span>
                </label>
                <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; color:#1e293b;">
                    <input type="radio" name="preferred_channel" value="whatsapp" <?= $preferredChannel === 'whatsapp' ? 'checked' : '' ?> style="width:16px; height:16px;">
                    <span>WhatsApp</span>
                </label>
            </div>

            <label style="display:flex; align-items:flex-start; gap:8px; cursor:pointer; font-size:0.8125rem; color:#475569; background:#f8fafc; padding:10px; border-radius:6px; border:1px solid #e2e8f0;">
                <input type="checkbox" name="allow_multi_channel" value="1" <?= $allowMultiChannel ? 'checked' : '' ?> style="margin-top:2px;">
                <div>
                    <strong>Send on both Email and WhatsApp simultaneously</strong>
                    <div style="color:#64748b; font-size:0.75rem;">When enabled, you will receive duplicate alerts on both channels. (Default = OFF).</div>
                </div>
            </label>
        </div>

        <!-- Section B: Deadline Reminders (User Controlled, Default OFF) -->
        <div style="border-bottom:1px solid #f1f5f9; padding-bottom:18px; margin-bottom:18px;">
            <div style="font-weight:600; color:#334155; margin-bottom:6px;">Scholarship Deadline Reminders</div>
            <p style="font-size:0.8125rem; color:#64748b; margin-top:0; margin-bottom:12px;">
                Choose how and when you would like to receive reminders before scholarship applications close.
            </p>

            <?php $currentScope = $userPref['deadline_reminder_scope'] ?? 'off'; ?>
            <div style="display:flex; flex-direction:column; gap:10px; margin-bottom:16px;">
                <label style="display:flex; align-items:flex-start; gap:10px; cursor:pointer; padding:10px; border:1px solid <?= $currentScope === 'off' ? '#3b82f6' : '#e2e8f0' ?>; border-radius:8px; background:<?= $currentScope === 'off' ? '#eff6ff' : '#fff' ?>;">
                    <input type="radio" name="deadline_reminder_scope" value="off" <?= $currentScope === 'off' ? 'checked' : '' ?> style="margin-top:3px;">
                    <div>
                        <div style="font-size:0.875rem; font-weight:600; color:#1e293b;">Option A: Do not send deadline reminders (Default - OFF)</div>
                        <div style="font-size:0.75rem; color:#64748b;">You will not receive any deadline reminder notifications.</div>
                    </div>
                </label>

                <label style="display:flex; align-items:flex-start; gap:10px; cursor:pointer; padding:10px; border:1px solid <?= $currentScope === 'all' ? '#3b82f6' : '#e2e8f0' ?>; border-radius:8px; background:<?= $currentScope === 'all' ? '#eff6ff' : '#fff' ?>;">
                    <input type="radio" name="deadline_reminder_scope" value="all" <?= $currentScope === 'all' ? 'checked' : '' ?> style="margin-top:3px;">
                    <div>
                        <div style="font-size:0.875rem; font-weight:600; color:#1e293b;">Option B: Send deadline reminders for all eligible scholarships</div>
                        <div style="font-size:0.75rem; color:#64748b;">Automatically receive deadline alerts for all scholarships that match your profile.</div>
                    </div>
                </label>

                <label style="display:flex; align-items:flex-start; gap:10px; cursor:pointer; padding:10px; border:1px solid <?= $currentScope === 'selected' ? '#3b82f6' : '#e2e8f0' ?>; border-radius:8px; background:<?= $currentScope === 'selected' ? '#eff6ff' : '#fff' ?>;">
                    <input type="radio" name="deadline_reminder_scope" value="selected" <?= $currentScope === 'selected' ? 'checked' : '' ?> style="margin-top:3px;">
                    <div>
                        <div style="font-size:0.875rem; font-weight:600; color:#1e293b;">Option C: Send deadline reminders only for scholarships I select</div>
                        <div style="font-size:0.75rem; color:#64748b;">Only receive reminders for scholarships where you specifically turn on the reminder bell.</div>
                    </div>
                </label>
            </div>

            <!-- Section C: Deadline Delivery Channels -->
            <div style="margin-bottom:16px;">
                <div style="font-size:0.8125rem; font-weight:600; color:#475569; margin-bottom:6px;">Deadline Channels:</div>
                <div style="display:flex; gap:24px; flex-wrap:wrap;">
                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; color:#1e293b;">
                        <input type="checkbox" name="deadline_email" value="1" <?= (!empty($prefMap['deadline_reminders']['email']) || !isset($prefMap['deadline_reminders'])) ? 'checked' : '' ?> style="width:16px; height:16px;">
                        <span>Email</span>
                    </label>
                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; color:#1e293b;">
                        <input type="checkbox" name="deadline_whatsapp" value="1" <?= !empty($prefMap['deadline_reminders']['whatsapp']) ? 'checked' : '' ?> style="width:16px; height:16px;">
                        <span>WhatsApp</span>
                    </label>
                </div>
            </div>

            <!-- Section D: Reminder Timing Offsets -->
            <div>
                <div style="font-size:0.8125rem; font-weight:600; color:#475569; margin-bottom:6px;">Reminder Timing (Days Before Deadline):</div>
                <?php
                    $configuredDays = explode(',', $userPref['deadline_reminder_days'] ?? '3,1');
                    $configuredDays = array_map('intval', $configuredDays);
                ?>
                <div style="display:flex; gap:20px; flex-wrap:wrap;">
                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; color:#1e293b;">
                        <input type="checkbox" name="reminder_days[]" value="1" <?= in_array(1, $configuredDays, true) ? 'checked' : '' ?> style="width:16px; height:16px;">
                        <span>1 Day Before</span>
                    </label>
                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; color:#1e293b;">
                        <input type="checkbox" name="reminder_days[]" value="3" <?= in_array(3, $configuredDays, true) ? 'checked' : '' ?> style="width:16px; height:16px;">
                        <span>3 Days Before</span>
                    </label>
                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; color:#1e293b;">
                        <input type="checkbox" name="reminder_days[]" value="7" <?= in_array(7, $configuredDays, true) ? 'checked' : '' ?> style="width:16px; height:16px;">
                        <span>7 Days Before</span>
                    </label>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary" style="padding:8px 20px;">
            Save Notification Settings
        </button>
    </form>
</div>

<!-- 2. Selected Scholarship Reminders (Option C) -->
<div class="data-table-card" style="background:#fff; border:1px solid var(--border); border-radius:12px; padding:24px; margin-bottom: 24px;">
    <h2 style="font-size:1.125rem; font-weight:600; color:#1e293b; margin-top:0; margin-bottom:8px; display:flex; align-items:center; gap:8px;">
        <i data-lucide="calendar" style="width:20px; height:20px; color:#10b981;"></i>
        Selected Scholarship Reminders
    </h2>
    <p style="margin:0 0 16px 0; color:#64748b; font-size:0.8125rem;">
        Scholarships you have explicitly selected for reminder notifications.
    </p>

    <?php if (empty($selectedReminders)): ?>
        <div style="padding:20px; text-align:center; color:#64748b; font-size:0.875rem; background:#f8fafc; border-radius:8px;">
            No individual scholarship reminders selected yet. You can enable deadline reminders on any scholarship page or set Option B above to receive reminders for all matching scholarships.
        </div>
    <?php else: ?>
        <div style="overflow-x:auto;">
            <table class="admin-table" style="width:100%;">
                <thead>
                    <tr>
                        <th>Scholarship</th>
                        <th>Provider</th>
                        <th>Deadline</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($selectedReminders as $rem): ?>
                        <tr>
                            <td>
                                <a href="<?= url('/scholarships/' . e($rem['slug'])) ?>" style="font-weight:600; color:#1d4ed8; text-decoration:none;">
                                    <?= e($rem['title']) ?>
                                </a>
                            </td>
                            <td style="font-size:0.875rem; color:#475569;"><?= e($rem['provider_name']) ?></td>
                            <td style="font-size:0.875rem; color:#475569;">
                                <?= $rem['application_deadline'] ? date('M d, Y', strtotime($rem['application_deadline'])) : 'Rolling' ?>
                            </td>
                            <td>
                                <span class="status-badge <?= $rem['is_enabled'] ? 'active' : 'suspended' ?>">
                                    <?= $rem['is_enabled'] ? 'Reminder Active' : 'Muted' ?>
                                </span>
                            </td>
                            <td>
                                <button type="button" onclick="toggleReminder(<?= (int)$rem['scholarship_id'] ?>, <?= $rem['is_enabled'] ? 0 : 1 ?>)" class="btn btn-secondary" style="font-size:0.75rem; padding:4px 8px;">
                                    <?= $rem['is_enabled'] ? 'Disable' : 'Enable' ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- 3. Notification Outbox Delivery History -->
<div class="data-table-card" style="background:#fff; border:1px solid var(--border); border-radius:12px; padding:24px;">
    <h2 style="font-size:1.125rem; font-weight:600; color:#1e293b; margin-top:0; margin-bottom:8px; display:flex; align-items:center; gap:8px;">
        <i data-lucide="inbox" style="width:20px; height:20px; color:#6366f1;"></i>
        Dispatched Notification Logs
    </h2>
    <p style="margin:0 0 16px 0; color:#64748b; font-size:0.8125rem;">
        Audit trail of all alerts and reminders queued and dispatched to your account.
    </p>

    <?php if (empty($logs)): ?>
        <div style="text-align:center; padding:30px 20px; color:#64748b;">
            <div style="width:48px; height:48px; background-color:#eff6ff; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 12px; color:#3b82f6;">
                <i data-lucide="bell-off" style="width:24px; height:24px;"></i>
            </div>
            <h3 style="font-size:1rem; font-weight:600; color:var(--text-900); margin-bottom:4px;">No notifications dispatched yet</h3>
            <p style="font-size:0.8125rem; color:#64748b; max-width:320px; margin:0 auto;">
                Alerts will appear here when new matching scholarships or deadline reminders are queued.
            </p>
        </div>
    <?php else: ?>
        <div style="overflow-x:auto;">
            <table class="admin-table" style="width:100%;">
                <thead>
                    <tr>
                        <th>Recipient & Channel</th>
                        <th>Alert Details</th>
                        <th>Status</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <?php if ($log['channel'] === 'whatsapp'): ?>
                                        <span style="color:#25d366; background:#e8f8ec; padding:4px; border-radius:50%; display:inline-flex;">
                                            <i data-lucide="message-square" style="width:14px; height:14px;"></i>
                                        </span>
                                    <?php else: ?>
                                        <span style="color:#3b82f6; background:#eff6ff; padding:4px; border-radius:50%; display:inline-flex;">
                                            <i data-lucide="mail" style="width:14px; height:14px;"></i>
                                        </span>
                                    <?php endif; ?>
                                    <div style="font-size:0.875rem; font-weight:600; color:var(--text-900);">
                                        <?= e($log['recipient']) ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="font-size:0.875rem; font-weight:500; color:var(--text-700);">
                                    <?= e($log['subject'] ?: 'Scholarship Matching Alert') ?>
                                </div>
                                <div style="font-size:0.75rem; color:#64748b; margin-top:2px;">
                                    Type: <code><?= e(str_replace('_', ' ', $log['notification_type'])) ?></code>
                                </div>
                            </td>
                            <td>
                                <?php
                                    $status = strtolower($log['status'] ?? 'sent');
                                    $class = 'pending';
                                    if ($status === 'sent' || $status === 'delivered') $class = 'active';
                                    if ($status === 'failed') $class = 'suspended';
                                ?>
                                <span class="status-badge <?= $class ?>">
                                    <?= e($status) ?>
                                </span>
                            </td>
                            <td style="font-size:0.8125rem; color:#64748b;">
                                <?= date('M d, Y h:i A', strtotime($log['created_at'])) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
function toggleReminder(scholarshipId, isEnabled) {
    const formData = new FormData();
    formData.append('csrf_token', '<?= e($csrf_token ?? '') ?>');
    formData.append('scholarship_id', scholarshipId);
    formData.append('is_enabled', isEnabled);

    fetch('<?= url('/profile/scholarships/reminder/toggle') ?>', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert(data.error || 'Failed to update reminder.');
        }
    })
    .catch(err => {
        console.error(err);
        alert('An error occurred while updating the reminder.');
    });
}
</script>

<?php include ROOT_PATH . '/app/Views/layouts/student_footer.php'; ?>
