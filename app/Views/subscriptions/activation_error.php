<?php
$title = $error_title ?? 'Activation Issue';
$subtitle = 'Subscription activation could not be completed';
include ROOT_PATH . '/app/Views/layouts/auth_header.php';
?>

<div style="text-align: center;">
    <div style="width: 64px; height: 64px; border-radius: 50%; background-color: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px auto; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.15);">
        <i data-lucide="alert-triangle" style="width: 32px; height: 32px;"></i>
    </div>

    <h2 style="font-size: 1.375rem; font-weight: 800; color: #0f172a; margin: 0 0 8px 0;">
        <?= e($error_title ?? 'Activation Link Error') ?>
    </h2>
    <p style="font-size: 0.9375rem; color: #64748b; margin: 0 0 24px 0; line-height: 1.5;">
        <?= e($error_message ?? 'The activation link you followed is invalid, expired, or has already been used.') ?>
    </p>

    <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px; text-align: left; margin-bottom: 24px;">
        <div style="font-size: 0.8125rem; font-weight: 700; color: #334155; margin-bottom: 6px;">Need Assistance?</div>
        <p style="font-size: 0.8125rem; color: #64748b; margin: 0; line-height: 1.4;">
            If you believe this is an error, please contact the administrator or reach out to support at <a href="mailto:support@scholarplanner.com" style="color: #2563eb; text-decoration: underline;">support@scholarplanner.com</a>.
        </p>
    </div>

    <div style="display: flex; gap: 12px; flex-direction: column;">
        <a href="<?= url('/login') ?>" class="btn btn-primary" style="width: 100%; padding: 12px; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
            <i data-lucide="log-in" style="width: 16px; height: 16px;"></i>
            <span>Log In to ScholarPlanner</span>
        </a>
        <a href="<?= url('/') ?>" style="color: #64748b; font-size: 0.875rem; text-decoration: none; font-weight: 500;">
            Back to Homepage
        </a>
    </div>
</div>

<?php include ROOT_PATH . '/app/Views/layouts/auth_footer.php'; ?>
