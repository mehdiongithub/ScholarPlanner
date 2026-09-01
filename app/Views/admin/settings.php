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
            <h2 class="settings-group-title"><i data-lucide="bell"></i> Notification Scheduler & Dispatch</h2>

            <!-- Master Toggle -->
            <div class="form-group" style="margin-bottom: 20px;">
                <label class="form-label" style="font-weight: 700; display: block; margin-bottom: 8px;">Automatic WhatsApp Notifications</label>
                <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="whatsapp_notifications_enabled" value="1" <?= !empty($schedulerSettings['whatsapp_notifications_enabled']) ? 'checked' : '' ?> style="width: 18px; height: 18px;">
                    <span style="font-size: 0.95rem; color: #1e293b; font-weight: 600;">Enable Automatic Notification Engine</span>
                </label>
                <small style="display: block; color: #64748b; margin-top: 4px;">When disabled, the cron worker skips dispatching all scheduled alerts.</small>
            </div>

            <!-- Allowed Types -->
            <div class="form-group" style="margin-bottom: 20px;">
                <label class="form-label" style="font-weight: 700; display: block; margin-bottom: 8px;">Enabled Notification Types</label>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="whatsapp_new_match_enabled" value="1" <?= !empty($schedulerSettings['whatsapp_new_match_enabled']) ? 'checked' : '' ?> style="width: 16px; height: 16px;">
                        <span><strong>NEW_MATCH</strong> — Send alert when a newly matching scholarship is found for paid users</span>
                    </label>
                    <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="whatsapp_deadline_reminder_enabled" value="1" <?= !empty($schedulerSettings['whatsapp_deadline_reminder_enabled']) ? 'checked' : '' ?> style="width: 16px; height: 16px;">
                        <span><strong>DEADLINE_REMINDER</strong> — Send reminder alerts for saved/matched upcoming scholarship deadlines</span>
                    </label>
                </div>
            </div>

            <!-- Allowed Days Checkboxes -->
            <div class="form-group" style="margin-bottom: 20px;">
                <label class="form-label" style="font-weight: 700; display: block; margin-bottom: 8px;">Allowed Dispatch Days</label>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 10px;">
                    <?php 
                    $allDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                    $selectedDays = $schedulerSettings['whatsapp_allowed_days'] ?? ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
                    foreach ($allDays as $day): 
                    ?>
                        <label style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer; background: #f8fafc; padding: 8px 12px; border: 1px solid var(--border-slate-200); border-radius: 8px;">
                            <input type="checkbox" name="whatsapp_allowed_days[]" value="<?= $day ?>" <?= in_array($day, $selectedDays, true) ? 'checked' : '' ?>>
                            <span style="font-size: 0.875rem;"><?= $day ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <small style="display: block; color: #64748b; margin-top: 6px;">Select the days of the week on which automated WhatsApp alerts are permitted to send.</small>
            </div>

            <!-- Time, Timezone, Batch Size in Grid -->
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                <div class="form-group">
                    <label class="form-label" style="font-weight: 700;">Daily Send Time (HH:MM)</label>
                    <input type="time" name="whatsapp_send_time" class="form-control" value="<?= \App\Helpers\Security::escape($schedulerSettings['whatsapp_send_time'] ?? '10:00') ?>" required>
                    <small style="display: block; color: #64748b; margin-top: 4px;">24-hour format (e.g., 10:00 for 10:00 AM).</small>
                </div>

                <div class="form-group">
                    <label class="form-label" style="font-weight: 700;">Timezone</label>
                    <select name="whatsapp_timezone" class="form-control" required>
                        <?php 
                        $currentTimezone = $schedulerSettings['whatsapp_timezone'] ?? 'Asia/Karachi';
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
                        // If current timezone is not in common list, add it
                        if (!isset($commonTimezones[$currentTimezone]) && in_array($currentTimezone, timezone_identifiers_list(), true)) {
                            $commonTimezones[$currentTimezone] = $currentTimezone;
                        }
                        foreach ($commonTimezones as $tzKey => $tzLabel): 
                        ?>
                            <option value="<?= $tzKey ?>" <?= $currentTimezone === $tzKey ? 'selected' : '' ?>><?= $tzLabel ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small style="display: block; color: #64748b; margin-top: 4px;">Timezone used for schedule calculations.</small>
                </div>

                <div class="form-group">
                    <label class="form-label" style="font-weight: 700;">Batch Size</label>
                    <input type="number" name="whatsapp_batch_size" class="form-control" value="<?= (int)($schedulerSettings['whatsapp_batch_size'] ?? 50) ?>" min="1" max="500" required>
                    <small style="display: block; color: #64748b; margin-top: 4px;">Max alerts processed per iteration (1 - 500).</small>
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
