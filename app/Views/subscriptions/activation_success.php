<?php
$title = !empty($already_active) ? 'Subscription Active' : 'Subscription Activated!';
$subtitle = !empty($already_active) ? 'Your subscription is currently active' : 'Your premium access is now live';
include ROOT_PATH . '/app/Views/layouts/auth_header.php';
?>

<div style="text-align: center;">
    <div style="width: 64px; height: 64px; border-radius: 50%; background-color: #dcfce7; color: #16a34a; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px auto; box-shadow: 0 4px 12px rgba(22, 163, 74, 0.15);">
        <i data-lucide="<?= !empty($already_active) ? 'check' : 'party-popper' ?>" style="width: 32px; height: 32px;"></i>
    </div>

    <h2 style="font-size: 1.5rem; font-weight: 800; color: #0f172a; margin: 0 0 8px 0;">
        <?= !empty($already_active) ? 'Subscription Active' : 'Woohoo! You\'re Activated' ?>
    </h2>
    <p style="font-size: 0.9375rem; color: #64748b; margin: 0 0 24px 0; line-height: 1.5;">
        <?php if (!empty($already_active)): ?>
            Your <strong><?= e($plan_name ?? 'Premium') ?></strong> membership is already active.
        <?php else: ?>
            Welcome, <strong><?= e($student_name ?? 'Student') ?></strong>! Your complimentary <strong><?= e($plan_name ?? 'Premium') ?></strong> subscription has started.
        <?php endif; ?>
    </p>

    <!-- Subscription Timeline Card -->
    <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; text-align: left; margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px dashed #cbd5e1;">
            <span style="font-size: 0.8125rem; font-weight: 600; color: #475569;">Plan Tier</span>
            <span style="display: inline-flex; align-items: center; gap: 4px; background: #eff6ff; color: #1d4ed8; padding: 4px 10px; border-radius: 9999px; font-weight: 700; font-size: 0.8125rem;">
                <i data-lucide="sparkles" style="width: 13px; height: 13px;"></i>
                <?= e($plan_name ?? 'Premium') ?>
            </span>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
            <span style="font-size: 0.8125rem; color: #64748b;">Active Since:</span>
            <strong style="font-size: 0.8125rem; color: #0f172a;">
                <?= !empty($starts_at) ? date('M d, Y h:i A', strtotime($starts_at)) : 'Today' ?>
            </strong>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
            <span style="font-size: 0.8125rem; color: #64748b;">Valid Until:</span>
            <strong style="font-size: 0.8125rem; color: #16a34a;">
                <?= !empty($ends_at) ? date('M d, Y h:i A', strtotime($ends_at)) : '30 Days' ?>
            </strong>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center;">
            <span style="font-size: 0.8125rem; color: #64748b;">Total Duration:</span>
            <strong style="font-size: 0.8125rem; color: #0f172a;">
                <?= (int)($duration_days ?? 30) ?> Days
            </strong>
        </div>
    </div>

    <!-- Perks summary -->
    <div style="text-align: left; margin-bottom: 24px; padding: 0 4px;">
        <div style="font-size: 0.8125rem; font-weight: 700; color: #334155; margin-bottom: 8px;">What's now available:</div>
        <div style="font-size: 0.8125rem; color: #475569; display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
            <i data-lucide="check" style="width: 14px; height: 14px; color: #16a34a;"></i>
            <span>Real-time WhatsApp & Email scholarship alerts</span>
        </div>
        <div style="font-size: 0.8125rem; color: #475569; display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
            <i data-lucide="check" style="width: 14px; height: 14px; color: #16a34a;"></i>
            <span>Unlimited side-by-side scholarship comparisons</span>
        </div>
        <div style="font-size: 0.8125rem; color: #475569; display: flex; align-items: center; gap: 8px;">
            <i data-lucide="check" style="width: 14px; height: 14px; color: #16a34a;"></i>
            <span>Priority document readiness scoring & deadline alerts</span>
        </div>
    </div>

    <!-- CTA Button -->
    <?php if (!empty($is_logged_in)): ?>
        <a href="<?= url('/dashboard') ?>" class="btn btn-primary" style="width: 100%; padding: 12px; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
            <span>Go to Student Dashboard</span>
            <i data-lucide="arrow-right" style="width: 16px; height: 16px;"></i>
        </a>
    <?php else: ?>
        <a href="<?= url('/login') ?>" class="btn btn-primary" style="width: 100%; padding: 12px; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
            <span>Log In to Your Account</span>
            <i data-lucide="log-in" style="width: 16px; height: 16px;"></i>
        </a>
        <p style="margin: 12px 0 0 0; font-size: 0.75rem; color: #94a3b8;">
            Log in with your registered email to enjoy your premium benefits.
        </p>
    <?php endif; ?>
</div>

<?php include ROOT_PATH . '/app/Views/layouts/auth_footer.php'; ?>
