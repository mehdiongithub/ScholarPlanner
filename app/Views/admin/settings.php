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
    <input type="hidden" name="csrf_token" value="<?= Security::csrfToken() ?>">

    <!-- Left Sticky Navigation -->
    <div>
        <nav class="settings-nav">
            <a href="#general" class="settings-nav-item active" id="nav-general"><i data-lucide="info"></i> General website</a>
            <a href="#mail" class="settings-nav-item" id="nav-mail"><i data-lucide="mail"></i> SMTP Config</a>
            <a href="#payment" class="settings-nav-item" id="nav-payment"><i data-lucide="credit-card"></i> Payment Sandbox</a>
            <a href="#homepage" class="settings-nav-item" id="nav-homepage"><i data-lucide="home"></i> Homepage CMS</a>
            <a href="#legal" class="settings-nav-item" id="nav-legal"><i data-lucide="file-text"></i> Terms & Privacy</a>
            <a href="#referral" class="settings-nav-item" id="nav-referral"><i data-lucide="share-2"></i> Referral System</a>
        </nav>
    </div>

    <!-- Right Configuration Fields -->
    <div>
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
</script>

<?php include ROOT_PATH . '/app/Views/layouts/admin_footer.php'; ?>
