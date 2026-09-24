<?php include ROOT_PATH . '/app/Views/layouts/admin_header.php'; ?>

<?php
$formatNextRun = function(?string $rawRun): string {
    if (empty($rawRun) || $rawRun === 'None scheduled') {
        return 'None scheduled';
    }
    $ts = strtotime($rawRun);
    if (!$ts) {
        return 'None scheduled';
    }
    $datePart = date('Y-m-d', $ts);
    $today = date('Y-m-d');
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    if ($datePart === $today) {
        return 'Today, ' . date('g:i A', $ts);
    } elseif ($datePart === $tomorrow) {
        return 'Tomorrow, ' . date('g:i A', $ts);
    }
    return date('D, M j - g:i A', $ts);
};
?>

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

/* Master Toggle Banner */
.master-banner {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 18px 24px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.master-banner.disabled-state {
    background: #fffbeb;
    border-color: #fde68a;
}

/* Timer Cards */
.timers-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(460px, 1fr));
    gap: 20px;
    margin-bottom: 28px;
}
.timer-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}
.timer-card-header {
    padding: 20px 24px;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
}
.timer-card-body {
    padding: 22px 24px;
    flex: 1;
}
.timer-card-footer {
    padding: 16px 24px;
    background: #f8fafc;
    border-top: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
}

/* Day Badges */
.days-pills-row {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 6px;
}
.day-pill {
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.725rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    background-color: #f1f5f9;
    color: #94a3b8;
    border: 1px solid #e2e8f0;
}
.day-pill.active-day {
    background-color: #eff6ff;
    color: #1d4ed8;
    border-color: #bfdbfe;
}

/* Status Badges */
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 12px;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 700;
}
.status-badge.active {
    background-color: #dcfce7;
    color: #15803d;
    border: 1px solid #bbf7d0;
}
.status-badge.paused {
    background-color: #f1f5f9;
    color: #64748b;
    border: 1px solid #e2e8f0;
}
.status-badge.success {
    background-color: #dcfce7;
    color: #15803d;
}
.status-badge.failed {
    background-color: #fef2f2;
    color: #dc2626;
}

.time-display-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    color: #166534;
    padding: 6px 14px;
    border-radius: 8px;
    font-size: 1.1rem;
    font-weight: 800;
    font-family: monospace;
}

/* Action Buttons */
.action-btn {
    border: none;
    border-radius: 8px;
    padding: 8px 16px;
    font-size: 0.8125rem;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.15s ease;
}
.action-btn.edit {
    background-color: #eff6ff;
    color: #1d4ed8;
}
.action-btn.edit:hover {
    background-color: #dbeafe;
}
.action-btn.toggle {
    background-color: #f8fafc;
    color: #475569;
    border: 1px solid #cbd5e1;
}
.action-btn.toggle:hover {
    background-color: #f1f5f9;
    color: #0f172a;
}
.action-btn.run {
    background-color: #10b981;
    color: #ffffff;
}
.action-btn.run:hover {
    background-color: #059669;
}

/* Modal styles */
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
    max-width: 560px;
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
                <i data-lucide="timer" style="width: 28px; height: 28px; color: #2563eb;"></i>
                <span>Alert Timers & WhatsApp Automation</span>
            </h1>
            <p style="margin: 4px 0 0 0; font-size: 0.875rem; color: #64748b;">
                Configure daily dispatch timings, specific days of the week, and trigger on-demand WhatsApp scholarship notifications.
            </p>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <button type="button" class="btn" onclick="openTestWaModal()" style="background-color: #dcfce7; color: #166534; border: 1px solid #bbf7d0; font-weight: 600; padding: 10px 16px; font-size: 0.875rem; display: inline-flex; align-items: center; gap: 6px;">
                <i data-lucide="message-circle" style="width: 16px; height: 16px;"></i>
                <span>Send Test WhatsApp</span>
            </button>
            <button type="button" class="btn" onclick="openDryRunModal()" style="background-color: #f1f5f9; color: #334155; font-weight: 600; padding: 10px 16px; font-size: 0.875rem; display: inline-flex; align-items: center; gap: 6px;">
                <i data-lucide="eye" style="width: 16px; height: 16px;"></i>
                <span>Dry-Run Inspection</span>
            </button>
            <a href="<?= url('/admin/manual-subscriptions') ?>" class="btn" style="background-color: #f1f5f9; color: #334155; font-weight: 600; padding: 10px 16px; font-size: 0.875rem; display: inline-flex; align-items: center; gap: 6px;">
                <i data-lucide="sparkles" style="width: 16px; height: 16px;"></i>
                <span>Active User Plans</span>
            </a>
        </div>
    </div>

    <!-- Alert / Toast Container -->
    <div id="timersPageAlert" style="display: none; margin-bottom: 20px; padding: 12px 18px; border-radius: 8px; font-size: 0.875rem;"></div>

    <!-- Metric Cards Grid -->
    <div class="metric-cards-grid">
        <div class="metric-card">
            <div class="metric-icon" style="background-color: #eff6ff; color: #2563eb;">
                <i data-lucide="timer" style="width: 24px; height: 24px;"></i>
            </div>
            <div class="metric-info">
                <h3 id="statActiveTimers"><?= (int)($activeTimersCount ?? 0) ?> / 2</h3>
                <p>Active Alert Timers</p>
            </div>
        </div>
        <div class="metric-card">
            <div class="metric-icon" style="background-color: #dcfce7; color: #15803d;">
                <i data-lucide="send" style="width: 24px; height: 24px;"></i>
            </div>
            <div class="metric-info">
                <h3><?= (int)($sentToday ?? 0) ?></h3>
                <p>WhatsApp Sent Today</p>
            </div>
        </div>
        <div class="metric-card">
            <div class="metric-icon" style="background-color: #fef3c7; color: #b45309;">
                <i data-lucide="inbox" style="width: 24px; height: 24px;"></i>
            </div>
            <div class="metric-info">
                <h3><?= (int)($pendingQueue ?? 0) ?></h3>
                <p>Outbox Queue Pending</p>
            </div>
        </div>
        <div class="metric-card">
            <div class="metric-icon" style="background-color: #f1f5f9; color: #475569;">
                <i data-lucide="calendar" style="width: 24px; height: 24px;"></i>
            </div>
            <div class="metric-info">
                <h3 style="font-size: 1.15rem;"><?= !empty($matchingNextRun) ? $formatNextRun($matchingNextRun) : 'N/A' ?></h3>
                <p>Next Match Dispatch</p>
            </div>
        </div>
    </div>

    <!-- Master WhatsApp Control Banner -->
    <?php $masterOn = !empty($settings['whatsapp_notifications_enabled']); ?>
    <div class="master-banner <?= !$masterOn ? 'disabled-state' : '' ?>" id="masterBanner">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div style="width: 42px; height: 42px; border-radius: 10px; background: <?= $masterOn ? '#dcfce7' : '#fee2e2' ?>; color: <?= $masterOn ? '#15803d' : '#dc2626' ?>; display: flex; align-items: center; justify-content: center;">
                <i data-lucide="<?= $masterOn ? 'check-circle' : 'alert-circle' ?>" style="width: 22px; height: 22px;"></i>
            </div>
            <div>
                <h4 style="margin: 0; font-size: 1rem; font-weight: 700; color: #0f172a;">
                    Master WhatsApp Outbound Automation: <span style="color: <?= $masterOn ? '#15803d' : '#dc2626' ?>;"><?= $masterOn ? 'ENABLED' : 'PAUSED' ?></span>
                </h4>
                <p style="margin: 2px 0 0 0; font-size: 0.8125rem; color: #64748b;">
                    When enabled, background cron workers automatically dispatch WhatsApp matching digests and deadline reminders according to the timers below.
                </p>
            </div>
        </div>
        <div>
            <button type="button" class="btn" onclick="toggleTimerStatus('master')" style="background-color: <?= $masterOn ? '#fee2e2' : '#dcfce7' ?>; color: <?= $masterOn ? '#b91c1c' : '#15803d' ?>; border: 1px solid <?= $masterOn ? '#fecaca' : '#bbf7d0' ?>; font-weight: 700; font-size: 0.8125rem; padding: 8px 16px;">
                <i data-lucide="power" style="width: 14px; height: 14px; display: inline-block; vertical-align: middle; margin-right: 4px;"></i>
                <span><?= $masterOn ? 'Pause All WhatsApp Messages' : 'Enable All WhatsApp Messages' ?></span>
            </button>
        </div>
    </div>

    <!-- Timers Grid -->
    <div class="timers-grid">

        <!-- Timer 1: Daily Scholarship Matching Alerts -->
        <?php 
        $matchActive = !empty($settings['matching_scheduler_enabled']) && $masterOn; 
        $matchDays = $settings['matching_allowed_days'] ?? ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $weekdays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        ?>
        <div class="timer-card">
            <div class="timer-card-header">
                <div>
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                        <span style="background: #eff6ff; color: #2563eb; font-size: 0.7rem; font-weight: 700; padding: 2px 8px; border-radius: 6px; text-transform: uppercase;">Daily Digest</span>
                        <span style="background: #f0fdf4; color: #166534; font-size: 0.7rem; font-weight: 700; padding: 2px 8px; border-radius: 6px;">WhatsApp &bull; High Priority</span>
                    </div>
                    <h3 style="margin: 0; font-size: 1.15rem; font-weight: 700; color: #0f172a;">Daily Scholarship Matching Alerts</h3>
                    <p style="margin: 4px 0 0 0; font-size: 0.8rem; color: #64748b;">
                        Evaluates eligible opportunities for verified students and enqueues WhatsApp matching summaries.
                    </p>
                </div>
                <div>
                    <span class="status-badge <?= $matchActive ? 'active' : 'paused' ?>" id="badge-matching">
                        <i data-lucide="<?= $matchActive ? 'check' : 'power' ?>" style="width: 12px; height: 12px;"></i>
                        <span><?= $matchActive ? 'Active' : 'Paused' ?></span>
                    </span>
                </div>
            </div>

            <div class="timer-card-body">
                <!-- Daily Timing Display -->
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 18px;">
                    <div>
                        <div style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; color: #64748b;">Daily Dispatch Time</div>
                        <div style="font-size: 0.8rem; color: #475569; margin-top: 2px;">Timezone: <strong><?= e($settings['matching_timezone'] ?? 'Asia/Karachi') ?></strong></div>
                    </div>
                    <div class="time-display-badge">
                        <i data-lucide="clock" style="width: 18px; height: 18px;"></i>
                        <span id="display-time-matching"><?= date('g:i A', strtotime('2000-01-01 ' . ($settings['matching_send_time'] ?? '08:00'))) ?></span>
                    </div>
                </div>

                <!-- Days of Week Display -->
                <div style="margin-bottom: 18px;">
                    <div style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; color: #64748b; margin-bottom: 6px;">Scheduled Days of Week</div>
                    <div class="days-pills-row" id="display-days-matching">
                        <?php foreach ($weekdays as $w): ?>
                            <?php $isAllowed = in_array($w, $matchDays, true); ?>
                            <span class="day-pill <?= $isAllowed ? 'active-day' : '' ?>">
                                <?= substr($w, 0, 3) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Next & Last Run Info -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 0.8rem;">
                    <div style="background: #ffffff; border: 1px solid #f1f5f9; border-radius: 8px; padding: 10px 12px;">
                        <span style="color: #64748b; display: block; font-size: 0.725rem;">Next Scheduled Run:</span>
                        <strong style="color: #0f172a; font-size: 0.8125rem;" id="display-next-matching">
                            <?= $formatNextRun($matchingNextRun) ?>
                        </strong>
                    </div>
                    <div style="background: #ffffff; border: 1px solid #f1f5f9; border-radius: 8px; padding: 10px 12px;">
                        <span style="color: #64748b; display: block; font-size: 0.725rem;">Last Execution:</span>
                        <strong style="color: #0f172a; font-size: 0.8125rem;">
                            <?= !empty($settings['matching_last_run_at']) ? date('M j, g:i A', strtotime($settings['matching_last_run_at'])) : 'Never' ?>
                            <?php if (!empty($settings['matching_last_run_status'])): ?>
                                <span style="font-size: 0.675rem; padding: 1px 6px; border-radius: 4px; font-weight: 700; background: <?= $settings['matching_last_run_status'] === 'SUCCESS' ? '#dcfce7' : '#fee2e2' ?>; color: <?= $settings['matching_last_run_status'] === 'SUCCESS' ? '#15803d' : '#dc2626' ?>;">
                                    <?= e($settings['matching_last_run_status']) ?>
                                </span>
                            <?php endif; ?>
                        </strong>
                    </div>
                </div>
            </div>

            <div class="timer-card-footer">
                <div style="display: flex; gap: 8px;">
                    <button type="button" class="action-btn edit" onclick="openEditTimerModal('matching')">
                        <i data-lucide="edit-3" style="width: 14px; height: 14px;"></i>
                        <span>Edit Timing & Days</span>
                    </button>
                    <button type="button" class="action-btn toggle" onclick="toggleTimerStatus('matching')">
                        <i data-lucide="power" style="width: 14px; height: 14px;"></i>
                        <span id="btn-text-matching"><?= $matchActive ? 'Pause' : 'Activate' ?></span>
                    </button>
                </div>
                <div>
                    <button type="button" class="action-btn run" onclick="openRunNowConfirm('matching')">
                        <i data-lucide="play" style="width: 14px; height: 14px;"></i>
                        <span>Run Now</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Timer 2: Scholarship Application Deadline Reminders -->
        <?php 
        $deadActive = !empty($settings['deadline_scheduler_enabled']) && $masterOn; 
        $deadDays = $settings['deadline_allowed_days'] ?? ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        ?>
        <div class="timer-card">
            <div class="timer-card-header">
                <div>
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                        <span style="background: #fef3c7; color: #b45309; font-size: 0.7rem; font-weight: 700; padding: 2px 8px; border-radius: 6px; text-transform: uppercase;">Urgent Countdown</span>
                        <span style="background: #f0fdf4; color: #166534; font-size: 0.7rem; font-weight: 700; padding: 2px 8px; border-radius: 6px;">WhatsApp &bull; Reminders</span>
                    </div>
                    <h3 style="margin: 0; font-size: 1.15rem; font-weight: 700; color: #0f172a;">Application Deadline Reminders</h3>
                    <p style="margin: 4px 0 0 0; font-size: 0.8rem; color: #64748b;">
                        Sends automated 7-day, 3-day, and 24-hour urgency countdown alerts for tracked scholarships.
                    </p>
                </div>
                <div>
                    <span class="status-badge <?= $deadActive ? 'active' : 'paused' ?>" id="badge-deadline">
                        <i data-lucide="<?= $deadActive ? 'check' : 'power' ?>" style="width: 12px; height: 12px;"></i>
                        <span><?= $deadActive ? 'Active' : 'Paused' ?></span>
                    </span>
                </div>
            </div>

            <div class="timer-card-body">
                <!-- Daily Timing Display -->
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 18px;">
                    <div>
                        <div style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; color: #64748b;">Daily Dispatch Time</div>
                        <div style="font-size: 0.8rem; color: #475569; margin-top: 2px;">Timezone: <strong><?= e($settings['deadline_timezone'] ?? 'Asia/Karachi') ?></strong></div>
                    </div>
                    <div class="time-display-badge">
                        <i data-lucide="clock" style="width: 18px; height: 18px;"></i>
                        <span id="display-time-deadline"><?= date('g:i A', strtotime('2000-01-01 ' . ($settings['deadline_send_time'] ?? '09:00'))) ?></span>
                    </div>
                </div>

                <!-- Days of Week Display -->
                <div style="margin-bottom: 18px;">
                    <div style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; color: #64748b; margin-bottom: 6px;">Scheduled Days of Week</div>
                    <div class="days-pills-row" id="display-days-deadline">
                        <?php foreach ($weekdays as $w): ?>
                            <?php $isAllowed = in_array($w, $deadDays, true); ?>
                            <span class="day-pill <?= $isAllowed ? 'active-day' : '' ?>">
                                <?= substr($w, 0, 3) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Next & Last Run Info -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 0.8rem;">
                    <div style="background: #ffffff; border: 1px solid #f1f5f9; border-radius: 8px; padding: 10px 12px;">
                        <span style="color: #64748b; display: block; font-size: 0.725rem;">Next Scheduled Run:</span>
                        <strong style="color: #0f172a; font-size: 0.8125rem;" id="display-next-deadline">
                            <?= $formatNextRun($deadlineNextRun) ?>
                        </strong>
                    </div>
                    <div style="background: #ffffff; border: 1px solid #f1f5f9; border-radius: 8px; padding: 10px 12px;">
                        <span style="color: #64748b; display: block; font-size: 0.725rem;">Last Execution:</span>
                        <strong style="color: #0f172a; font-size: 0.8125rem;">
                            <?= !empty($settings['deadline_last_run_at']) ? date('M j, g:i A', strtotime($settings['deadline_last_run_at'])) : 'Never' ?>
                            <?php if (!empty($settings['deadline_last_run_status'])): ?>
                                <span style="font-size: 0.675rem; padding: 1px 6px; border-radius: 4px; font-weight: 700; background: <?= $settings['deadline_last_run_status'] === 'SUCCESS' ? '#dcfce7' : '#fee2e2' ?>; color: <?= $settings['deadline_last_run_status'] === 'SUCCESS' ? '#15803d' : '#dc2626' ?>;">
                                    <?= e($settings['deadline_last_run_status']) ?>
                                </span>
                            <?php endif; ?>
                        </strong>
                    </div>
                </div>
            </div>

            <div class="timer-card-footer">
                <div style="display: flex; gap: 8px;">
                    <button type="button" class="action-btn edit" onclick="openEditTimerModal('deadline')">
                        <i data-lucide="edit-3" style="width: 14px; height: 14px;"></i>
                        <span>Edit Timing & Days</span>
                    </button>
                    <button type="button" class="action-btn toggle" onclick="toggleTimerStatus('deadline')">
                        <i data-lucide="power" style="width: 14px; height: 14px;"></i>
                        <span id="btn-text-deadline"><?= $deadActive ? 'Pause' : 'Activate' ?></span>
                    </button>
                </div>
                <div>
                    <button type="button" class="action-btn run" onclick="openRunNowConfirm('deadline')">
                        <i data-lucide="play" style="width: 14px; height: 14px;"></i>
                        <span>Run Now</span>
                    </button>
                </div>
            </div>
        </div>

    </div>

    <!-- Automated Linux & Web Cron Setup Guide + Health Status -->
    <div style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 22px 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-top: 10px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 14px; margin-bottom: 16px; border-bottom: 1px solid #f1f5f9; padding-bottom: 14px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 10px; background: <?= !empty($cronActive) ? '#dcfce7' : '#fee2e2' ?>; color: <?= !empty($cronActive) ? '#15803d' : '#dc2626' ?>; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i data-lucide="<?= !empty($cronActive) ? 'activity' : 'alert-triangle' ?>" style="width: 22px; height: 22px;"></i>
                </div>
                <div>
                    <h4 style="margin: 0; font-size: 1.05rem; font-weight: 700; color: #0f172a;">
                        Server Cron Worker: 
                        <span style="color: <?= !empty($cronActive) ? '#15803d' : '#dc2626' ?>;">
                            <?= !empty($cronActive) ? '🟢 Active & Running' : '⚠️ Inactive / Not Detected' ?>
                        </span>
                    </h4>
                    <p style="margin: 2px 0 0 0; font-size: 0.8125rem; color: #64748b;">
                        Last background check-in: <strong><?= e($cronHeartbeatDisplay ?? 'Never') ?></strong>
                    </p>
                </div>
            </div>
            <div style="display: flex; gap: 8px;">
                <button type="button" class="btn" onclick="triggerWebCronTick()" style="background-color: #f8fafc; border: 1px solid #cbd5e1; color: #334155; font-size: 0.8rem; font-weight: 600; padding: 7px 14px; display: inline-flex; align-items: center; gap: 5px;">
                    <i data-lucide="refresh-cw" style="width: 14px; height: 14px;"></i>
                    <span>Trigger Web Cron Tick</span>
                </button>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 18px;">
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px 16px;">
                <div style="font-size: 0.825rem; font-weight: 700; color: #1e293b; margin-bottom: 6px; display: flex; align-items: center; gap: 6px;">
                    <i data-lucide="terminal" style="width: 15px; height: 15px; color: #2563eb;"></i>
                    <span>Option 1: Hostinger / cPanel Linux Command (Recommended)</span>
                </div>
                <p style="margin: 0 0 8px 0; font-size: 0.775rem; color: #64748b;">Run every minute (* * * * *) in Hostinger Cron Jobs:</p>
                <div style="position: relative;">
                    <pre style="background: #0f172a; color: #38bdf8; padding: 10px 12px; border-radius: 6px; font-size: 0.75rem; margin: 0; overflow-x: auto; white-space: pre-wrap; word-break: break-all;">* * * * * /usr/bin/php <?= ROOT_PATH ?>/cron/scheduler.php &gt; /dev/null 2&gt;&amp;1</pre>
                </div>
            </div>

            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px 16px;">
                <div style="font-size: 0.825rem; font-weight: 700; color: #1e293b; margin-bottom: 6px; display: flex; align-items: center; gap: 6px;">
                    <i data-lucide="globe" style="width: 15px; height: 15px; color: #059669;"></i>
                    <span>Option 2: Hostinger URL Cron / Cron-Job.org (Web Runner)</span>
                </div>
                <p style="margin: 0 0 8px 0; font-size: 0.775rem; color: #64748b;">If CLI crons are unavailable, ping this URL every minute:</p>
                <div style="position: relative;">
                    <pre style="background: #0f172a; color: #4ade80; padding: 10px 12px; border-radius: 6px; font-size: 0.75rem; margin: 0; overflow-x: auto; white-space: pre-wrap; word-break: break-all;"><?= url('/cron/run?token=' . ($cronSecretToken ?? '')) ?></pre>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Modal 1: Edit Timer Schedule (Timing & Days) -->
<div id="editTimerModal" class="modal-overlay" style="display: none;">
    <div class="modal-box">
        <div class="modal-box-header">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 38px; height: 38px; border-radius: 10px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center;">
                    <i data-lucide="timer" style="width: 20px; height: 20px;"></i>
                </div>
                <div>
                    <h3 id="editModalHeaderTitle" style="margin: 0; font-size: 1.15rem; font-weight: 700; color: #0f172a;">Configure Alert Schedule</h3>
                    <p style="margin: 2px 0 0 0; font-size: 0.8rem; color: #64748b;">Set daily dispatch time, timezone, and active days.</p>
                </div>
            </div>
            <button type="button" onclick="closeEditModal()" style="background: none; border: none; cursor: pointer; color: #94a3b8; padding: 4px;">
                <i data-lucide="x" style="width: 20px; height: 20px;"></i>
            </button>
        </div>

        <form id="editTimerForm" onsubmit="submitTimerSchedule(event)">
            <input type="hidden" name="csrf_token" value="<?= e($csrf_token ?? '') ?>">
            <input type="hidden" name="timer_type" id="modalTimerTypeInput" value="matching">

            <div class="modal-body-scroll">
                <div id="editModalAlert" style="display: none; margin-bottom: 16px; padding: 10px 14px; border-radius: 8px; font-size: 0.8125rem;"></div>

                <!-- Daily Time & Timezone -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 18px;">
                    <div>
                        <label class="form-label" style="font-weight: 600; font-size: 0.8125rem; color: #334155;">Daily Dispatch Time (24h) <span style="color: #ef4444;">*</span></label>
                        <input type="time" name="send_time" id="modalSendTimeInput" class="form-control" required style="font-size: 1rem; font-weight: 700; padding: 8px 12px;">
                        <small style="color: #64748b; font-size: 0.725rem;">e.g. 08:00 AM or 18:30 (6:30 PM)</small>
                    </div>
                    <div>
                        <label class="form-label" style="font-weight: 600; font-size: 0.8125rem; color: #334155;">Operating Timezone <span style="color: #ef4444;">*</span></label>
                        <select name="timezone" id="modalTimezoneInput" class="form-control" style="font-size: 0.85rem; padding: 9px 12px;">
                            <option value="Asia/Karachi">Asia/Karachi (PKT, UTC+5)</option>
                            <option value="UTC">UTC (Universal)</option>
                            <option value="Asia/Dubai">Asia/Dubai (GST, UTC+4)</option>
                            <option value="Asia/Riyadh">Asia/Riyadh (AST, UTC+3)</option>
                            <option value="Europe/London">Europe/London (GMT/BST)</option>
                            <option value="America/New_York">America/New_York (EST/EDT)</option>
                        </select>
                    </div>
                </div>

                <!-- Days of Week Quick Presets -->
                <div style="margin-bottom: 14px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <label class="form-label" style="font-weight: 600; font-size: 0.8125rem; color: #334155; margin: 0;">Days of the Week <span style="color: #ef4444;">*</span></label>
                        <div style="display: flex; gap: 6px;">
                            <button type="button" class="btn btn-sm" onclick="setDayPreset('all')" style="padding: 2px 8px; font-size: 0.725rem; background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;">All 7 Days</button>
                            <button type="button" class="btn btn-sm" onclick="setDayPreset('weekdays')" style="padding: 2px 8px; font-size: 0.725rem; background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1;">Mon-Fri</button>
                            <button type="button" class="btn btn-sm" onclick="setDayPreset('weekends')" style="padding: 2px 8px; font-size: 0.725rem; background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1;">Sat-Sun</button>
                        </div>
                    </div>

                    <!-- Individual Day Checkboxes -->
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px;">
                        <?php foreach ($weekdays as $d): ?>
                            <label style="display: flex; align-items: center; gap: 8px; font-size: 0.8125rem; color: #334155; cursor: pointer;">
                                <input type="checkbox" name="allowed_days[]" value="<?= e($d) ?>" class="day-checkbox" id="check-day-<?= strtolower($d) ?>">
                                <span><?= e($d) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Status Checkbox -->
                <div style="margin-top: 16px; padding: 12px 14px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px;">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; margin: 0;">
                        <input type="checkbox" name="is_active" id="modalIsActiveInput" value="1" style="width: 18px; height: 18px;">
                        <div>
                            <div style="font-size: 0.85rem; font-weight: 700; color: #166534;">Enable this Alert Timer</div>
                            <div style="font-size: 0.75rem; color: #15803d;">When unchecked, this specific timer is paused.</div>
                        </div>
                    </label>
                </div>
            </div>

            <div class="modal-box-footer">
                <button type="button" class="btn" onclick="closeEditModal()" style="background-color: #ffffff; border: 1px solid #cbd5e1; color: #475569; padding: 9px 18px; font-weight: 600; font-size: 0.875rem;">
                    Cancel
                </button>
                <button type="submit" id="btnSaveSchedule" class="btn btn-primary" style="padding: 9px 22px; font-weight: 600; font-size: 0.875rem; display: inline-flex; align-items: center; gap: 6px;">
                    <i data-lucide="check" style="width: 16px; height: 16px;"></i>
                    <span>Save Schedule</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: Run Now Confirmation & Live Result -->
<div id="runNowModal" class="modal-overlay" style="display: none;">
    <div class="modal-box" style="max-width: 500px;">
        <div class="modal-box-header">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 44px; height: 44px; border-radius: 12px; background: #ecfdf5; color: #059669; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i data-lucide="rocket" style="width: 22px; height: 22px;"></i>
                </div>
                <div>
                    <h3 id="runNowTitle" style="margin: 0; font-size: 1.15rem; font-weight: 700; color: #0f172a;">Run Alert Timer Now</h3>
                    <p style="margin: 2px 0 0 0; font-size: 0.8rem; color: #64748b;">Trigger immediate evaluation and WhatsApp dispatch.</p>
                </div>
            </div>
            <button type="button" onclick="closeRunNowModal()" style="background: none; border: none; cursor: pointer; color: #94a3b8; padding: 4px;">
                <i data-lucide="x" style="width: 20px; height: 20px;"></i>
            </button>
        </div>

        <div class="modal-body-scroll">
            <div id="runNowConfirmContent">
                <p style="margin: 0 0 16px 0; font-size: 0.875rem; color: #334155; line-height: 1.5;">
                    Are you sure you want to trigger <strong id="runNowTargetName" style="color: #1d4ed8;">Scholarship Matching Alerts</strong> now?
                </p>
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px; font-size: 0.8rem; color: #475569;">
                    <i data-lucide="zap" style="width: 14px; height: 14px; color: #f59e0b; display: inline-block; vertical-align: middle; margin-right: 4px;"></i>
                    This will immediately evaluate all eligible students, generate personalized recommendations, and enqueue WhatsApp messages into the delivery outbox.
                </div>
            </div>

            <div id="runNowLoading" style="display: none; text-align: center; padding: 24px 0;">
                <div class="spinner-border text-primary" role="status" style="width: 2.5rem; height: 2.5rem; margin-bottom: 14px;"></div>
                <h4 style="margin: 0; font-size: 1rem; font-weight: 700; color: #0f172a;">Evaluating & Dispatching...</h4>
                <p style="margin: 4px 0 0 0; font-size: 0.8rem; color: #64748b;">Processing student profiles and creating notification records.</p>
            </div>

            <div id="runNowResult" style="display: none;"></div>
        </div>

        <div class="modal-box-footer" id="runNowFooter">
            <button type="button" class="btn" onclick="closeRunNowModal()" style="background-color: #ffffff; border: 1px solid #cbd5e1; color: #475569; padding: 9px 18px; font-weight: 600;">
                Cancel
            </button>
            <button type="button" id="btnExecuteRunNow" onclick="executeRunNow()" class="btn btn-primary" style="padding: 9px 22px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                <i data-lucide="play" style="width: 15px; height: 15px;"></i>
                <span>Yes, Run Alert Now</span>
            </button>
        </div>
    </div>
</div>

<!-- Modal 3: Dry-Run Inspection Modal -->
<div id="dryRunModal" class="modal-overlay" style="display: none;">
    <div class="modal-box" style="max-width: 600px;">
        <div class="modal-box-header">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 38px; height: 38px; border-radius: 10px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center;">
                    <i data-lucide="eye" style="width: 20px; height: 20px;"></i>
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 1.15rem; font-weight: 700; color: #0f172a;">Dry-Run Status Inspection</h3>
                    <p style="margin: 2px 0 0 0; font-size: 0.8rem; color: #64748b;">Inspect current scheduler due status without sending messages.</p>
                </div>
            </div>
            <button type="button" onclick="closeDryRunModal()" style="background: none; border: none; cursor: pointer; color: #94a3b8; padding: 4px;">
                <i data-lucide="x" style="width: 20px; height: 20px;"></i>
            </button>
        </div>

        <div class="modal-body-scroll" id="dryRunModalBody">
            <div style="text-align: center; padding: 30px;">
                <div class="spinner-border text-primary" role="status"></div>
                <div style="margin-top: 10px; font-size: 0.85rem; color: #64748b;">Loading inspection report...</div>
            </div>
        </div>

        <div class="modal-box-footer">
            <button type="button" class="btn" onclick="closeDryRunModal()" style="background-color: #f1f5f9; color: #475569; padding: 8px 18px; font-weight: 600;">
                Close
            </button>
        </div>
    </div>
</div>

<!-- Modal 4: Send Test WhatsApp Message Modal -->
<div id="testWaModal" class="modal-overlay" style="display: none;">
    <div class="modal-box" style="max-width: 500px;">
        <div class="modal-box-header">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 38px; height: 38px; border-radius: 10px; background: #dcfce7; color: #15803d; display: flex; align-items: center; justify-content: center;">
                    <i data-lucide="message-circle" style="width: 20px; height: 20px;"></i>
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 1.15rem; font-weight: 700; color: #0f172a;">Send Test WhatsApp Alert</h3>
                    <p style="margin: 2px 0 0 0; font-size: 0.8rem; color: #64748b;">Verify WhatsApp provider connectivity and instant delivery.</p>
                </div>
            </div>
            <button type="button" onclick="closeTestWaModal()" style="background: none; border: none; cursor: pointer; color: #94a3b8; padding: 4px;">
                <i data-lucide="x" style="width: 20px; height: 20px;"></i>
            </button>
        </div>

        <form id="testWaForm" onsubmit="executeSendTestWa(event)">
            <input type="hidden" name="csrf_token" value="<?= e($csrf_token ?? '') ?>">

            <div class="modal-body-scroll">
                <div id="testWaAlert" style="display: none; margin-bottom: 16px; padding: 12px 14px; border-radius: 8px; font-size: 0.825rem;"></div>

                <div style="margin-bottom: 14px;">
                    <label class="form-label" style="font-weight: 600; font-size: 0.8125rem; color: #334155;">Recipient WhatsApp Phone Number <span style="color: #ef4444;">*</span></label>
                    <input type="text" name="phone" id="testWaPhoneInput" class="form-control" required 
                           placeholder="+923251371826" 
                           value="<?= e($user['whatsapp_phone'] ?? $user['phone'] ?? '+923251371826') ?>" 
                           style="font-size: 0.95rem; font-weight: 600; padding: 10px 14px;">
                    <small style="color: #64748b; font-size: 0.75rem; display: block; margin-top: 4px;">
                        Include international country code prefix (e.g. <code>+923001234567</code>).
                    </small>
                </div>

                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px; font-size: 0.8rem; color: #475569;">
                    <i data-lucide="info" style="width: 14px; height: 14px; color: #2563eb; display: inline-block; vertical-align: middle; margin-right: 4px;"></i>
                    This sends an immediate live verification alert using your configured WhatsApp gateway (WACRM/UltraMsg).
                </div>
            </div>

            <div class="modal-box-footer">
                <button type="button" class="btn" onclick="closeTestWaModal()" style="background-color: #ffffff; border: 1px solid #cbd5e1; color: #475569; padding: 9px 18px; font-weight: 600;">
                    Cancel
                </button>
                <button type="submit" id="btnSubmitTestWa" class="btn btn-primary" style="padding: 9px 22px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                    <i data-lucide="send" style="width: 15px; height: 15px;"></i>
                    <span>Send Test Message Now</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
var currentEditingTimer = 'matching';
var currentRunTimer = 'matching';

function showToast(msg, type) {
    var alertBox = $('#timersPageAlert');
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

function openEditTimerModal(type) {
    currentEditingTimer = type;
    $('#modalTimerTypeInput').val(type);
    $('#editModalAlert').hide();

    var title = (type === 'matching') ? 'Daily Scholarship Matching Alerts' : 'Scholarship Deadline Reminders';
    $('#editModalHeaderTitle').text('Edit Schedule: ' + title);

    // Fetch current data from server
    $.ajax({
        url: '<?= url("/admin/alert-timers/data") ?>',
        type: 'GET',
        dataType: 'json',
        success: function(res) {
            if (res.success && res.timers) {
                var timerData = res.timers.find(function(t) { return t.id === type; });
                if (timerData) {
                    $('#modalSendTimeInput').val(timerData.send_time);
                    $('#modalTimezoneInput').val(timerData.timezone);
                    $('#modalIsActiveInput').prop('checked', timerData.scheduler_enabled);

                    // Uncheck all days first
                    $('.day-checkbox').prop('checked', false);
                    if (Array.isArray(timerData.allowed_days)) {
                        timerData.allowed_days.forEach(function(day) {
                            $('#check-day-' + day.toLowerCase()).prop('checked', true);
                        });
                    }

                    $('#editTimerModal').addClass('active');
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                }
            }
        },
        error: function() {
            alert('Failed to load timer schedule details.');
        }
    });
}

function closeEditModal() {
    $('#editTimerModal').removeClass('active');
}

function setDayPreset(preset) {
    $('.day-checkbox').prop('checked', false);
    if (preset === 'all') {
        $('.day-checkbox').prop('checked', true);
    } else if (preset === 'weekdays') {
        ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'].forEach(function(d) {
            $('#check-day-' + d).prop('checked', true);
        });
    } else if (preset === 'weekends') {
        ['saturday', 'sunday'].forEach(function(d) {
            $('#check-day-' + d).prop('checked', true);
        });
    }
}

function submitTimerSchedule(e) {
    e.preventDefault();
    var btn = $('#btnSaveSchedule');
    var alertBox = $('#editModalAlert');

    btn.prop('disabled', true).css('opacity', '0.7');
    alertBox.hide();

    $.ajax({
        url: '<?= url("/admin/alert-timers/update") ?>',
        type: 'POST',
        data: $('#editTimerForm').serialize(),
        dataType: 'json',
        success: function(res) {
            btn.prop('disabled', false).css('opacity', '1');
            if (res.success) {
                closeEditModal();
                showToast(res.message || 'Schedule updated successfully!', 'success');
                setTimeout(function() {
                    location.reload();
                }, 1200);
            } else {
                alertBox.show().css({
                    'background-color': '#fef2f2',
                    'border': '1px solid #fecaca',
                    'color': '#dc2626'
                }).html('<i data-lucide="alert-circle" style="width:14px;height:14px;display:inline-block;vertical-align:middle;margin-right:4px;"></i> ' + (res.error || 'Failed to update schedule.'));
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

function toggleTimerStatus(type) {
    var label = (type === 'matching') ? 'Daily Matching Alerts' : ((type === 'deadline') ? 'Deadline Reminders' : 'Master WhatsApp Automation');
    adminConfirm({
        title: 'Toggle Automation Status',
        message: 'Are you sure you want to toggle the status of <strong>"' + adminEscapeHtml(label) + '"</strong>?',
        subtext: 'This will enable or disable automatic message dispatch for this timer.',
        confirmText: 'Yes, Toggle Status',
        confirmClass: 'btn-primary',
        icon: 'power'
    }, function() {
        $.ajax({
            url: '<?= url("/admin/alert-timers/toggle-status") ?>',
            type: 'POST',
            data: {
                csrf_token: '<?= e($csrf_token ?? '') ?>',
                timer_type: type
            },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    showToast(res.message, 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    showToast(res.error || 'Failed to toggle status.', 'error');
                }
            },
            error: function(xhr) {
                showToast(xhr.responseJSON?.error || 'Failed to toggle status.', 'error');
            }
        });
    });
}

function openRunNowConfirm(type) {
    currentRunTimer = type;
    var name = (type === 'matching') ? 'Daily Scholarship Matching Alerts' : 'Scholarship Deadline Reminders';
    $('#runNowTargetName').text(name);
    $('#runNowConfirmContent').show();
    $('#runNowLoading').hide();
    $('#runNowResult').hide();
    $('#runNowFooter').show();
    $('#runNowModal').addClass('active');
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function closeRunNowModal() {
    $('#runNowModal').removeClass('active');
}

function executeRunNow() {
    $('#runNowConfirmContent').hide();
    $('#runNowFooter').hide();
    $('#runNowLoading').show();

    $.ajax({
        url: '<?= url("/admin/alert-timers/run-now") ?>',
        type: 'POST',
        data: {
            csrf_token: '<?= e($csrf_token ?? '') ?>',
            timer_type: currentRunTimer
        },
        dataType: 'json',
        success: function(res) {
            $('#runNowLoading').hide();
            if (res.success) {
                var d = res.details || {};
                var summaryHtml = '<div style="background:#dcfce7; border:1px solid #bbf7d0; border-radius:10px; padding:16px; margin-bottom:16px;">' +
                    '<div style="font-weight:700; color:#15803d; font-size:1rem; margin-bottom:8px;"><i data-lucide="check-circle" style="width:18px;height:18px;display:inline-block;vertical-align:middle;margin-right:6px;"></i> Dispatch Triggered Successfully!</div>' +
                    '<p style="margin:0; font-size:0.85rem; color:#166534;">' + res.message + '</p>' +
                '</div>' +
                '<div style="display:flex; justify-content:flex-end;">' +
                    '<button type="button" class="btn btn-primary" onclick="location.reload()">OK, Reload Dashboard</button>' +
                '</div>';

                $('#runNowResult').html(summaryHtml).show();
                if (typeof lucide !== 'undefined') lucide.createIcons();
            } else {
                var errHtml = '<div style="background:#fee2e2; border:1px solid #fecaca; border-radius:10px; padding:16px; margin-bottom:16px; color:#dc2626;">' +
                    '<strong>Execution Failed:</strong> ' + (res.error || 'Unknown error') +
                '</div>' +
                '<div style="display:flex; justify-content:flex-end;">' +
                    '<button type="button" class="btn" onclick="closeRunNowModal()">Close</button>' +
                '</div>';
                $('#runNowResult').html(errHtml).show();
            }
        },
        error: function(xhr) {
            $('#runNowLoading').hide();
            var errHtml = '<div style="background:#fee2e2; border:1px solid #fecaca; border-radius:10px; padding:16px; margin-bottom:16px; color:#dc2626;">' +
                '<strong>Execution Error:</strong> ' + (xhr.responseJSON?.error || 'Server error occurred.') +
            '</div>' +
            '<div style="display:flex; justify-content:flex-end;">' +
                '<button type="button" class="btn" onclick="closeRunNowModal()">Close</button>' +
            '</div>';
            $('#runNowResult').html(errHtml).show();
        }
    });
}

function openDryRunModal() {
    $('#dryRunModal').addClass('active');
    $('#dryRunModalBody').html('<div style="text-align: center; padding: 30px;"><div class="spinner-border text-primary" role="status"></div><div style="margin-top: 10px; font-size: 0.85rem; color: #64748b;">Inspecting background scheduler status...</div></div>');

    $.ajax({
        url: '<?= url("/admin/alert-timers/dry-run") ?>',
        type: 'GET',
        dataType: 'json',
        success: function(res) {
            if (res.success && res.report) {
                var r = res.report;
                var html = '<div style="font-size:0.875rem;">';
                
                html += '<div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:14px; margin-bottom:16px;">' +
                    '<div style="display:flex; justify-content:space-between; align-items:center;">' +
                        '<strong>Master WhatsApp Automation:</strong>' +
                        '<span class="status-badge ' + (r.master_enabled ? 'active' : 'paused') + '">' + (r.master_enabled ? 'ENABLED' : 'PAUSED') + '</span>' +
                    '</div>' +
                    '<div style="font-size:0.775rem; color:#64748b; margin-top:4px;">Pending Outbox Queue Messages: <strong>' + r.total_pending + '</strong></div>' +
                '</div>';

                // Matching Schedule inspection
                html += '<div style="border:1px solid #e2e8f0; border-radius:10px; padding:16px; margin-bottom:14px;">' +
                    '<h4 style="margin:0 0 10px 0; font-size:0.95rem; font-weight:700; color:#0f172a;">1. Daily Scholarship Matching Schedule</h4>' +
                    '<table style="width:100%; font-size:0.8rem; line-height:1.7;">' +
                        '<tr><td style="color:#64748b; width:140px;">Status:</td><td><strong>' + (r.matching.enabled ? '<span style="color:#16a34a;">Enabled</span>' : '<span style="color:#dc2626;">Disabled</span>') + '</strong></td></tr>' +
                        '<tr><td style="color:#64748b;">Configured Time:</td><td>' + r.matching.send_time + ' (' + r.matching.timezone + ')</td></tr>' +
                        '<tr><td style="color:#64748b;">Allowed Days:</td><td>' + r.matching.allowed_days.join(', ') + '</td></tr>' +
                        '<tr><td style="color:#64748b;">Due Right Now:</td><td><strong>' + (r.matching.due ? '<span style="color:#16a34a;">YES (Ready)</span>' : '<span style="color:#64748b;">NO (' + r.matching.reason.toUpperCase() + ')</span>') + '</strong></td></tr>' +
                        '<tr><td style="color:#64748b;">Next Run:</td><td>' + r.matching.next_run + '</td></tr>' +
                    '</table>' +
                '</div>';

                // Deadline Schedule inspection
                html += '<div style="border:1px solid #e2e8f0; border-radius:10px; padding:16px;">' +
                    '<h4 style="margin:0 0 10px 0; font-size:0.95rem; font-weight:700; color:#0f172a;">2. Deadline Reminders Schedule</h4>' +
                    '<table style="width:100%; font-size:0.8rem; line-height:1.7;">' +
                        '<tr><td style="color:#64748b; width:140px;">Status:</td><td><strong>' + (r.deadline.enabled ? '<span style="color:#16a34a;">Enabled</span>' : '<span style="color:#dc2626;">Disabled</span>') + '</strong></td></tr>' +
                        '<tr><td style="color:#64748b;">Configured Time:</td><td>' + r.deadline.send_time + ' (' + r.deadline.timezone + ')</td></tr>' +
                        '<tr><td style="color:#64748b;">Allowed Days:</td><td>' + r.deadline.allowed_days.join(', ') + '</td></tr>' +
                        '<tr><td style="color:#64748b;">Due Right Now:</td><td><strong>' + (r.deadline.due ? '<span style="color:#16a34a;">YES (Ready)</span>' : '<span style="color:#64748b;">NO (' + r.deadline.reason.toUpperCase() + ')</span>') + '</strong></td></tr>' +
                        '<tr><td style="color:#64748b;">Next Run:</td><td>' + r.deadline.next_run + '</td></tr>' +
                    '</table>' +
                '</div>';

                html += '</div>';
                $('#dryRunModalBody').html(html);
            }
        },
        error: function() {
            $('#dryRunModalBody').html('<div class="text-danger" style="padding:20px; text-align:center;">Failed to run dry-run inspection.</div>');
        }
    });
}

function closeDryRunModal() {
    $('#dryRunModal').removeClass('active');
}

function openTestWaModal() {
    $('#testWaAlert').hide();
    $('#testWaModal').addClass('active');
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function closeTestWaModal() {
    $('#testWaModal').removeClass('active');
}

function executeSendTestWa(e) {
    e.preventDefault();
    var btn = $('#btnSubmitTestWa');
    var alertBox = $('#testWaAlert');
    btn.prop('disabled', true).css('opacity', '0.7');
    alertBox.hide();

    $.ajax({
        url: '<?= url("/admin/alert-timers/send-test") ?>',
        type: 'POST',
        data: $('#testWaForm').serialize(),
        dataType: 'json',
        success: function(res) {
            btn.prop('disabled', false).css('opacity', '1');
            if (res.success) {
                alertBox.show().css({
                    'background-color': '#dcfce7',
                    'border': '1px solid #bbf7d0',
                    'color': '#15803d'
                }).html('<i data-lucide="check-circle" style="width:14px;height:14px;display:inline-block;vertical-align:middle;margin-right:4px;"></i> ' + res.message);
                if (typeof lucide !== 'undefined') lucide.createIcons();
                showToast(res.message, 'success');
            } else {
                alertBox.show().css({
                    'background-color': '#fee2e2',
                    'border': '1px solid #fecaca',
                    'color': '#dc2626'
                }).html('<i data-lucide="alert-circle" style="width:14px;height:14px;display:inline-block;vertical-align:middle;margin-right:4px;"></i> ' + (res.error || 'Failed to dispatch test message.'));
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }
        },
        error: function(xhr) {
            btn.prop('disabled', false).css('opacity', '1');
            var msg = xhr.responseJSON?.error || 'Failed to dispatch test message.';
            alertBox.show().css({
                'background-color': '#fee2e2',
                'border': '1px solid #fecaca',
                'color': '#dc2626'
            }).html('<i data-lucide="alert-circle" style="width:14px;height:14px;display:inline-block;vertical-align:middle;margin-right:4px;"></i> ' + msg);
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    });
}

function triggerWebCronTick() {
    showToast('Executing web cron scheduler tick...', 'success');
    $.ajax({
        url: '<?= url("/cron/run?token=" . ($cronSecretToken ?? "")) ?>',
        type: 'GET',
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                showToast('Web cron executed successfully! Outbox dispatched: ' + (res.outbox_dispatched || 0) + ' messages.', 'success');
                setTimeout(function() {
                    location.reload();
                }, 1500);
            } else {
                showToast(res.error || 'Cron tick failed.', 'error');
            }
        },
        error: function(xhr) {
            showToast(xhr.responseJSON?.error || 'Cron tick execution failed.', 'error');
        }
    });
}

// Escape key to close modals
$(document).keyup(function(e) {
    if (e.key === "Escape") {
        closeEditModal();
        closeRunNowModal();
        closeDryRunModal();
        closeTestWaModal();
    }
});
</script>

<?php include ROOT_PATH . '/app/Views/layouts/admin_footer.php'; ?>
