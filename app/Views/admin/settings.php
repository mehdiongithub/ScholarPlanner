<?php include ROOT_PATH . '/app/Views/layouts/admin_header.php'; ?>

<style>
    .settings-layout {
        display: grid;
        grid-template-columns: 1fr 3fr;
        gap: 30px;
    }
    @media (max-width: 991px) {
        .settings-layout {
            grid-template-columns: 1fr;
        }
    }
    .settings-nav {
        background: #fff;
        border: 1px solid var(--border-slate-200);
        border-radius: 12px;
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 4px;
        position: sticky;
        top: 90px;
    }
    .settings-nav-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 14px;
        color: #475569;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.875rem;
        transition: all 0.2s;
    }
    .settings-nav-item:hover {
        background-color: var(--bg-slate-50);
        color: var(--primary);
    }
    .settings-nav-item.active {
        background-color: var(--bg-slate-100);
        color: var(--primary);
    }

    .settings-group-card {
        background: #fff;
        border: 1px solid var(--border-slate-200);
        border-radius: 12px;
        padding: 30px;
        margin-bottom: 30px;
    }
    .settings-group-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: #1e293b;
        margin-top: 0;
        margin-bottom: 24px;
        border-bottom: 1px solid var(--border-slate-200);
        padding-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .settings-group-title i {
        color: var(--primary);
    }
</style>

<div style="margin-bottom: 24px;">
    <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0; color: #1e293b;">System Configuration Settings</h1>
    <p style="margin: 4px 0 0 0; color: #64748b; font-size: 0.875rem;">Modify general site metadata, SMTP setups, payment gateway API parameters, and website content.</p>
</div>

<form action="/admin/settings" method="POST" class="settings-layout">
    <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Security::csrfToken() ?>">

    <!-- Left Sticky Navigation -->
    <div>
        <nav class="settings-nav">
            <a href="#general" class="settings-nav-item active" id="nav-general"><i data-lucide="info"></i> General website</a>
            <a href="#mail" class="settings-nav-item" id="nav-mail"><i data-lucide="mail"></i> SMTP Config</a>
            <a href="#payment" class="settings-nav-item" id="nav-payment"><i data-lucide="credit-card"></i> Payment Sandbox</a>
            <a href="#homepage" class="settings-nav-item" id="nav-homepage"><i data-lucide="home"></i> Homepage CMS</a>
            <a href="#legal" class="settings-nav-item" id="nav-legal"><i data-lucide="file-text"></i> Terms & Privacy</a>
            <a href="#referral" class="settings-nav-item" id="nav-referral"><i data-lucide="share-2"></i> Referral System</a>
            <a href="#whatsapp" class="settings-nav-item" id="nav-whatsapp"><i data-lucide="message-square"></i> WhatsApp CRM</a>
            <a href="#notifications" class="settings-nav-item" id="nav-notifications"><i data-lucide="bell"></i> Notification Scheduler</a>
        </nav>
    </div>

    <!-- Right Configuration Fields -->
    <div>
        <!-- Hidden marker for notification scheduler settings submission -->
        <input type="hidden" name="is_notification_settings" value="1">
        <!-- General Website Settings -->
        <section id="general" class="settings-group-card">
            <h2 class="settings-group-title"><i data-lucide="info"></i> General website Configuration</h2>
            <?php foreach ($groups['general'] ?? [] as $s): ?>
                <div class="form-group">
                    <label class="form-label" style="text-transform: capitalize;"><?= str_replace('_', ' ', $s['key']) ?></label>
                    <?php if ($s['key'] === 'maintenance_mode'): ?>
                        <select name="<?= e($s['key']) ?>" class="form-control">
                            <option value="0" <?= $s['value'] === '0' ? 'selected' : '' ?>>Disabled (Site Online)</option>
                            <option value="1" <?= $s['value'] === '1' ? 'selected' : '' ?>>Enabled (Maintenance Mode active)</option>
                        </select>
                    <?php else: ?>
                        <input type="text" name="<?= e($s['key']) ?>" class="form-control" value="<?= e($s['value']) ?>">
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </section>

        <!-- Mail SMTP Configurations -->
        <section id="mail" class="settings-group-card">
            <h2 class="settings-group-title"><i data-lucide="mail"></i> SMTP Mail Server settings</h2>
            <?php foreach ($groups['mail'] ?? [] as $s): ?>
                <div class="form-group">
                    <label class="form-label" style="text-transform: capitalize;"><?= str_replace('_', ' ', $s['key']) ?></label>
                    <?php if (preg_match('/pass/i', $s['key'])): ?>
                        <input type="password" name="<?= e($s['key']) ?>" class="form-control" value="<?= e($s['value']) ?>">
                    <?php else: ?>
                        <input type="text" name="<?= e($s['key']) ?>" class="form-control" value="<?= e($s['value']) ?>">
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </section>

        <!-- Payment Configurations -->
        <section id="payment" class="settings-group-card">
            <h2 class="settings-group-title"><i data-lucide="credit-card"></i> Payment Sandboxed Gateways</h2>
            <?php foreach ($groups['payment'] ?? [] as $s): ?>
                <div class="form-group">
                    <label class="form-label" style="text-transform: capitalize;"><?= str_replace('_', ' ', $s['key']) ?></label>
                    <?php if ($s['key'] === 'payment_sandbox_mode'): ?>
                        <select name="<?= e($s['key']) ?>" class="form-control">
                            <option value="1" <?= $s['value'] === '1' ? 'selected' : '' ?>>Sandbox Mode (Mock Checkout screens)</option>
                            <option value="0" <?= $s['value'] === '0' ? 'selected' : '' ?>>Production Mode (Live Gateways)</option>
                        </select>
                    <?php elseif (preg_match('/key|secret/i', $s['key'])): ?>
                        <input type="password" name="<?= e($s['key']) ?>" class="form-control" value="<?= e($s['value']) ?>">
                    <?php else: ?>
                        <input type="text" name="<?= e($s['key']) ?>" class="form-control" value="<?= e($s['value']) ?>">
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </section>

        <!-- Homepage Content CMS -->
        <section id="homepage" class="settings-group-card">
            <h2 class="settings-group-title"><i data-lucide="home"></i> Homepage Hero & Tagline CMS</h2>
            <?php foreach ($groups['homepage'] ?? [] as $s): ?>
                <div class="form-group">
                    <label class="form-label" style="text-transform: capitalize;"><?= str_replace('_', ' ', $s['key']) ?></label>
                    <?php if ($s['key'] === 'hero_subtitle'): ?>
                        <textarea name="<?= e($s['key']) ?>" class="form-control" style="height: 80px;"><?= e($s['value']) ?></textarea>
                    <?php else: ?>
                        <input type="text" name="<?= e($s['key']) ?>" class="form-control" value="<?= e($s['value']) ?>">
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </section>

        <!-- Legal Content T&C / Privacy -->
        <section id="legal" class="settings-group-card">
            <h2 class="settings-group-title"><i data-lucide="file-text"></i> Terms of service & Privacy policy</h2>
            <?php foreach ($groups['legal'] ?? [] as $s): ?>
                <div class="form-group">
                    <label class="form-label" style="text-transform: capitalize;"><?= str_replace('_', ' ', $s['key']) ?></label>
                    <textarea name="<?= e($s['key']) ?>" class="form-control" style="height: 150px;"><?= e($s['value']) ?></textarea>
                </div>
            <?php endforeach; ?>
        </section>

        <!-- Referral System Configurations -->
        <section id="referral" class="settings-group-card">
            <h2 class="settings-group-title"><i data-lucide="share-2"></i> Referral System settings</h2>
            <?php foreach ($groups['referral'] ?? [] as $s): ?>
                <div class="form-group">
                    <label class="form-label" style="text-transform: capitalize;"><?= str_replace('_', ' ', $s['key']) ?></label>
                    <input type="text" name="<?= e($s['key']) ?>" class="form-control" value="<?= e($s['value']) ?>">
                </div>
            <?php endforeach; ?>
        </section>

        <!-- WhatsApp CRM Configurations -->
        <section id="whatsapp" class="settings-group-card">
            <h2 class="settings-group-title"><i data-lucide="message-square"></i> WhatsApp CRM settings</h2>
            <div class="form-group">
                <label class="form-label">Provider Name (WHATSAPP_PROVIDER)</label>
                <input type="text" class="form-control" value="<?= \App\Helpers\Security::escape(strtoupper($_ENV['WHATSAPP_PROVIDER'] ?? 'LOG')) ?>" disabled>
            </div>
            <div class="form-group">
                <label class="form-label">WACRM Configuration Status</label>
                <div>
                    <span class="status-badge <?= (($_ENV['WHATSAPP_PROVIDER'] ?? '') === 'wacrm' && !empty($_ENV['WACRM_API_KEY'])) ? 'active' : 'inactive' ?>">
                        <?= (($_ENV['WHATSAPP_PROVIDER'] ?? '') === 'wacrm' && !empty($_ENV['WACRM_API_KEY'])) ? 'CONFIGURED' : 'NOT CONFIGURED' ?>
                    </span>
                </div>
            </div>
            <?php if (($_ENV['WHATSAPP_PROVIDER'] ?? '') === 'wacrm'): ?>
                <button type="button" id="btnTestWacrm" class="btn btn-secondary" style="margin-top: 10px; display: inline-flex; align-items: center; gap: 8px;">
                    <i data-lucide="activity"></i> Test WACRM Connection
                </button>
                <div id="wacrmTestResult" style="margin-top: 10px; font-weight: bold; font-size: 0.875rem;"></div>
            <?php endif; ?>
        </section>

        <!-- WhatsApp Notification Scheduler Configurations -->
        <section id="notifications" class="settings-group-card">
            <h2 class="settings-group-title"><i data-lucide="bell"></i> Notification Scheduler & Dispatch Engine</h2>

            <!-- Master Engine Settings -->
            <div style="background: #f8fafc; border: 1px solid var(--border-slate-200); border-radius: 10px; padding: 20px; margin-bottom: 24px;">
                <h3 style="margin-top: 0; margin-bottom: 12px; font-size: 1.05rem; color: #0f172a; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="zap" style="color: #6366f1;"></i> Master Notification Engine
                </h3>
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; align-items: center;">
                    <div>
                        <label style="display: inline-flex; align-items: center; gap: 10px; cursor: pointer;">
                            <input type="checkbox" name="whatsapp_notifications_enabled" value="1" <?= !empty($schedulerSettings['whatsapp_notifications_enabled']) ? 'checked' : '' ?> style="width: 20px; height: 20px;">
                            <span style="font-size: 1rem; color: #1e293b; font-weight: 700;">Enable Master WhatsApp Automation</span>
                        </label>
                        <small style="display: block; color: #64748b; margin-top: 4px;">When unchecked, the master cron dispatcher skips executing all matching algorithms and queue dispatches.</small>
                    </div>
                    <div>
                        <label class="form-label" style="font-weight: 700; margin-bottom: 4px;">Queue Batch Size</label>
                        <input type="number" name="whatsapp_batch_size" class="form-control" value="<?= (int)($schedulerSettings['whatsapp_batch_size'] ?? 50) ?>" min="1" max="500" required>
                        <small style="display: block; color: #64748b; margin-top: 2px;">Max alerts processed per cron tick (1–500).</small>
                    </div>
                </div>
            </div>

            <!-- Schedule Card 1: Automatic Scholarship Matching -->
            <div style="border: 1px solid #cbd5e1; border-radius: 10px; padding: 20px; margin-bottom: 24px; background: #ffffff;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i data-lucide="sparkles" style="color: #3b82f6;"></i>
                        <h3 style="margin: 0; font-size: 1.1rem; color: #1e293b; font-weight: 700;">1. Automatic Scholarship Matching Schedule</h3>
                    </div>
                    <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="matching_scheduler_enabled" value="1" <?= !empty($schedulerSettings['matching_scheduler_enabled']) ? 'checked' : '' ?> style="width: 18px; height: 18px;">
                        <span style="font-weight: 600; font-size: 0.9rem; color: #0f172a;">Active</span>
                    </label>
                </div>

                <!-- Next & Last Run Info -->
                <div style="display: flex; flex-wrap: wrap; gap: 16px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 10px 14px; margin-bottom: 18px; font-size: 0.85rem;">
                    <div><strong>Next Run:</strong> <span style="color: #15803d;"><?= \App\Helpers\Security::escape($matchingNextRun ?? 'None scheduled') ?></span></div>
                    <div>•</div>
                    <div><strong>Last Run:</strong> <?= \App\Helpers\Security::escape($schedulerSettings['matching_last_run_at'] ?? 'Never') ?></div>
                    <div>•</div>
                    <div><strong>Status:</strong> <span style="text-transform: uppercase; font-weight: 600;"><?= \App\Helpers\Security::escape($schedulerSettings['matching_last_run_status'] ?? 'IDLE') ?></span></div>
                </div>

                <!-- Time & Timezone -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" style="font-weight: 700;">Daily Run Time (HH:MM)</label>
                        <input type="time" name="matching_send_time" class="form-control" value="<?= \App\Helpers\Security::escape($schedulerSettings['matching_send_time'] ?? '08:00') ?>" required>
                        <small style="display: block; color: #64748b; margin-top: 4px;">24-hour time in configured timezone (default 08:00 AM).</small>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" style="font-weight: 700;">Timezone</label>
                        <select name="matching_timezone" class="form-control" required>
                            <?php 
                            $matchTz = $schedulerSettings['matching_timezone'] ?? 'Asia/Karachi';
                            $commonTimezones = [
                                'Asia/Karachi' => 'Asia/Karachi (PKT +05:00)',
                                'Asia/Dubai' => 'Asia/Dubai (GST +04:00)',
                                'Asia/Dhaka' => 'Asia/Dhaka (BST +06:00)',
                                'Asia/Riyadh' => 'Asia/Riyadh (AST +03:00)',
                                'UTC' => 'UTC (Universal Coordinated Time)',
                                'Europe/London' => 'Europe/London (GMT/BST)',
                                'Europe/Berlin' => 'Europe/Berlin (CET/CEST)',
                                'America/New_York' => 'America/New_York (EST/EDT)',
                                'America/Chicago' => 'America/Chicago (CST/CDT)',
                                'America/Los_Angeles' => 'America/Los_Angeles (PST/PDT)'
                            ];
                            if (!isset($commonTimezones[$matchTz]) && in_array($matchTz, timezone_identifiers_list(), true)) {
                                $commonTimezones[$matchTz] = $matchTz;
                            }
                            foreach ($commonTimezones as $tzKey => $tzLabel): 
                            ?>
                                <option value="<?= $tzKey ?>" <?= $matchTz === $tzKey ? 'selected' : '' ?>><?= $tzLabel ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small style="display: block; color: #64748b; margin-top: 4px;">Schedule timezone for matching triggers.</small>
                    </div>
                </div>

                <!-- Allowed Days -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" style="font-weight: 700; margin-bottom: 8px; display: block;">Permitted Days of Week</label>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(110px, 1fr)); gap: 8px;">
                        <?php 
                        $allWeekdays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                        $matchDays = $schedulerSettings['matching_allowed_days'] ?? ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                        foreach ($allWeekdays as $day): 
                        ?>
                            <label style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer; background: #f8fafc; padding: 6px 10px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 0.875rem;">
                                <input type="checkbox" name="matching_allowed_days[]" value="<?= $day ?>" <?= in_array($day, $matchDays, true) ? 'checked' : '' ?>>
                                <span><?= $day ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Schedule Card 2: Deadline Reminder Schedule -->
            <div style="border: 1px solid #cbd5e1; border-radius: 10px; padding: 20px; margin-bottom: 24px; background: #ffffff;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i data-lucide="clock" style="color: #f59e0b;"></i>
                        <h3 style="margin: 0; font-size: 1.1rem; color: #1e293b; font-weight: 700;">2. Scholarship Deadline Reminder Schedule</h3>
                    </div>
                    <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="deadline_scheduler_enabled" value="1" <?= !empty($schedulerSettings['deadline_scheduler_enabled']) ? 'checked' : '' ?> style="width: 18px; height: 18px;">
                        <span style="font-weight: 600; font-size: 0.9rem; color: #0f172a;">Active</span>
                    </label>
                </div>

                <!-- Next & Last Run Info -->
                <div style="display: flex; flex-wrap: wrap; gap: 16px; background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 10px 14px; margin-bottom: 18px; font-size: 0.85rem;">
                    <div><strong>Next Run:</strong> <span style="color: #b45309;"><?= \App\Helpers\Security::escape($deadlineNextRun ?? 'None scheduled') ?></span></div>
                    <div>•</div>
                    <div><strong>Last Run:</strong> <?= \App\Helpers\Security::escape($schedulerSettings['deadline_last_run_at'] ?? 'Never') ?></div>
                    <div>•</div>
                    <div><strong>Status:</strong> <span style="text-transform: uppercase; font-weight: 600;"><?= \App\Helpers\Security::escape($schedulerSettings['deadline_last_run_status'] ?? 'IDLE') ?></span></div>
                </div>

                <!-- Time & Timezone -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" style="font-weight: 700;">Daily Run Time (HH:MM)</label>
                        <input type="time" name="deadline_send_time" class="form-control" value="<?= \App\Helpers\Security::escape($schedulerSettings['deadline_send_time'] ?? '09:00') ?>" required>
                        <small style="display: block; color: #64748b; margin-top: 4px;">24-hour time in configured timezone (default 09:00 AM).</small>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" style="font-weight: 700;">Timezone</label>
                        <select name="deadline_timezone" class="form-control" required>
                            <?php 
                            $deadTz = $schedulerSettings['deadline_timezone'] ?? 'Asia/Karachi';
                            if (!isset($commonTimezones[$deadTz]) && in_array($deadTz, timezone_identifiers_list(), true)) {
                                $commonTimezones[$deadTz] = $deadTz;
                            }
                            foreach ($commonTimezones as $tzKey => $tzLabel): 
                            ?>
                                <option value="<?= $tzKey ?>" <?= $deadTz === $tzKey ? 'selected' : '' ?>><?= $tzLabel ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small style="display: block; color: #64748b; margin-top: 4px;">Schedule timezone for deadline reminders.</small>
                    </div>
                </div>

                <!-- Allowed Days -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" style="font-weight: 700; margin-bottom: 8px; display: block;">Permitted Days of Week</label>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(110px, 1fr)); gap: 8px;">
                        <?php 
                        $deadDays = $schedulerSettings['deadline_allowed_days'] ?? ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                        foreach ($allWeekdays as $day): 
                        ?>
                            <label style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer; background: #f8fafc; padding: 6px 10px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 0.875rem;">
                                <input type="checkbox" name="deadline_allowed_days[]" value="<?= $day ?>" <?= in_array($day, $deadDays, true) ? 'checked' : '' ?>>
                                <span><?= $day ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Hostinger Single Production Cron Command Setup Guide -->
            <div style="background: #0f172a; color: #f8fafc; border-radius: 10px; padding: 20px;">
                <h3 style="margin-top: 0; margin-bottom: 8px; font-size: 1rem; color: #38bdf8; display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="terminal"></i> Hostinger & Production Server Cron Setup
                </h3>
                <p style="font-size: 0.85rem; color: #94a3b8; margin-top: 0; margin-bottom: 12px; line-height: 1.5;">
                    Configure <strong>ONE single cron job</strong> in your Hostinger cPanel / server crontab running every minute (<code>* * * * *</code>). The application will automatically evaluate the matching and deadline schedules configured above with database mutex locking and idempotency protection.
                </p>
                <div style="background: #1e293b; border: 1px solid #334155; border-radius: 6px; padding: 12px 14px; font-family: monospace; font-size: 0.85rem; color: #a5f3fc; overflow-x: auto; user-select: all;">
                    * * * * * /usr/bin/php <?= ROOT_PATH ?>/cron/scheduler.php &gt; /dev/null 2&gt;&amp;1
                </div>
                <div style="margin-top: 10px; font-size: 0.78rem; color: #64748b;">
                    💡 You never need to edit your Hostinger crontab when changing schedule times or days — all timing is dynamically evaluated from this Admin Panel.
                </div>
            </div>
        </section>

        <!-- Save Button Bar -->
        <div style="background-color: #fff; border: 1px solid var(--border-slate-200); border-radius: 12px; padding: 20px; display: flex; justify-content: flex-end; position: sticky; bottom: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); z-index: 100;">
            <button type="submit" class="btn btn-primary" style="padding: 12px 24px;">Save System configurations</button>
        </div>
    </div>
</form>

<script>
    // Smooth scrolling & active navigation class tracking
    const sections = document.querySelectorAll('section');
    const navItems = document.querySelectorAll('.settings-nav-item');

    window.addEventListener('scroll', () => {
        let current = '';
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            if (pageYOffset >= sectionTop - 120) {
                current = section.getAttribute('id');
            }
        });

        navItems.forEach(item => {
            item.classList.remove('active');
            if (item.getAttribute('href') === '#' + current) {
                item.classList.add('active');
            }
        });
    });

    // Test connection button handler
    const btnTestWacrm = document.getElementById('btnTestWacrm');
    if (btnTestWacrm) {
        btnTestWacrm.addEventListener('click', () => {
            const resultDiv = document.getElementById('wacrmTestResult');
            resultDiv.style.color = '#475569';
            resultDiv.textContent = 'Testing connection...';
            btnTestWacrm.disabled = true;

            const formData = new FormData();
            formData.append('csrf_token', '<?= \App\Helpers\Security::csrfToken() ?>');

            fetch('/admin/settings/wacrm/test-connection', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                btnTestWacrm.disabled = false;
                if (data.status === 'CONNECTED') {
                    resultDiv.style.color = '#10b981';
                    resultDiv.textContent = '✔ CONNECTED';
                } else {
                    resultDiv.style.color = '#ef4444';
                    resultDiv.textContent = '❌ NOT CONNECTED' + (data.error ? ' (' + data.error + ')' : '');
                }
            })
            .catch(err => {
                btnTestWacrm.disabled = false;
                resultDiv.style.color = '#ef4444';
                resultDiv.textContent = '❌ Error testing connection.';
            });
        });
    }
</script>

<?php include ROOT_PATH . '/app/Views/layouts/admin_footer.php'; ?>
