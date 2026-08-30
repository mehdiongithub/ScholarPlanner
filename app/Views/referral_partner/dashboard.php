<?php include ROOT_PATH . '/app/Views/layouts/referral_partner_header.php'; ?>

<style>
    .grid-3 {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 24px;
        margin-bottom: 32px;
    }
    @media (max-width: 768px) {
        .grid-3 {
            grid-template-columns: 1fr;
        }
    }
    .metric-card {
        background: white;
        border: 1px solid var(--border-slate-200);
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .metric-title {
        font-size: 0.8125rem;
        text-transform: uppercase;
        font-weight: 600;
        color: #64748b;
        letter-spacing: 0.05em;
    }
    .metric-value {
        font-size: 1.75rem;
        font-weight: 700;
        color: #0f172a;
    }
    .card {
        background: white;
        border: 1px solid var(--border-slate-200);
        border-radius: 12px;
        padding: 28px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        max-width: 600px;
    }
    .card-title {
        font-size: 1.125rem;
        font-weight: 700;
        margin-top: 0;
        margin-bottom: 16px;
    }
    .referral-link-box {
        background-color: var(--bg-slate-100);
        border: 1px solid var(--border-slate-200);
        border-radius: 8px;
        padding: 12px;
        font-family: monospace;
        font-size: 0.875rem;
        word-break: break-all;
        margin-bottom: 16px;
        color: #334155;
    }
</style>

<div style="margin-bottom: 24px;">
    <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0; color: #1e293b;">Partner Program Overview</h1>
    <p style="margin: 4px 0 0 0; color: #64748b; font-size: 0.875rem;">Track signups, conversions, and your dynamic commission logs in real time.</p>
</div>

<!-- Metrics Grid -->
<div class="grid-3">
    <div class="metric-card">
        <span class="metric-title">Attributed Signups</span>
        <span class="metric-value"><?= e($total_signups) ?></span>
    </div>
    <div class="metric-card">
        <span class="metric-title">Paid Conversions</span>
        <span class="metric-value"><?= e($conversions) ?></span>
    </div>
    <div class="metric-card">
        <span class="metric-title">Commissions Earned</span>
        <span class="metric-value" style="color: #10b981;">$<?= e($commission) ?></span>
    </div>
</div>

<!-- Link Sharing Card -->
<div class="card" id="sharing-link">
    <h2 class="card-title">Share Referral Link</h2>
    <p style="font-size: 0.875rem; color: #475569; margin-bottom: 20px; line-height: 1.5;">
        Share this unique link with students. When they sign up using your link, they receive a <strong><?= e(number_format($partner['discount_percent'], 0)) ?>% discount</strong> on any subscription plan, and you earn <strong>$20.00 commission</strong> per paid checkout.
    </p>
    
    <label style="display:block; font-size:0.75rem; text-transform:uppercase; font-weight:600; color:#64748b; margin-bottom:6px;">Your Referral Link</label>
    <div class="referral-link-box" id="refLink">
        <?= e(url("/register")) ?>?ref=<?= e($partner['referral_code']) ?>
    </div>
    
    <button onclick="copyLink()" class="btn btn-primary" style="width: 100%; justify-content: center; display: inline-flex; align-items: center; gap: 8px;">
        <i data-lucide="copy" style="width:16px; height:16px;"></i>
        <span>Copy Link to Clipboard</span>
    </button>
</div>

<script>
    function copyLink() {
        var text = document.getElementById('refLink').innerText.trim();
        navigator.clipboard.writeText(text).then(function() {
            alert('Referral link copied to clipboard!');
        }, function() {
            alert('Failed to copy. Your link is: ' + text);
        });
    }
</script>

<?php include ROOT_PATH . '/app/Views/layouts/referral_partner_footer.php'; ?>
