<?php
$title = $title ?? 'Terms of Service — ScholarPlanner';
$description = $description ?? 'Terms of Service for ScholarPlanner. Read the terms governing your use of our scholarship discovery and notification platform.';
include ROOT_PATH . '/app/Views/layouts/public_header.php';
?>

<style>
    /* ===== BREADCRUMB ===== */
    .breadcrumb-bar { background: var(--bg-50, #f8fafc); border-bottom: 1px solid var(--border-light, #f1f5f9); padding: 14px 0; }
    .breadcrumb { display: flex; align-items: center; gap: 8px; font-size: 0.875rem; color: var(--text-400, #94a3b8); flex-wrap: wrap; }
    .breadcrumb a { color: var(--text-500, #64748b); transition: color 150ms; }
    .breadcrumb a:hover { color: var(--primary, #1e40af); }
    .breadcrumb i { width: 14px; height: 14px; flex-shrink: 0; }
    .breadcrumb .current { color: var(--text-700, #334155); font-weight: 500; }

    /* ===== LEGAL LAYOUT ===== */
    .legal-layout { padding: clamp(32px, 5vw, 56px) 0 clamp(48px, 8vw, 80px); background: #f8fafc; }
    .legal-grid { display: grid; grid-template-columns: 1fr; gap: clamp(24px, 4vw, 40px); }
    @media (min-width: 1024px) {
        .legal-grid { grid-template-columns: 240px 1fr; align-items: start; }
    }

    /* Sidebar TOC */
    .legal-toc { display: none; position: sticky; top: calc(var(--header-height, 70px) + 24px); }
    @media (min-width: 1024px) { .legal-toc { display: block; } }
    .toc-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 18px 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); max-height: calc(100vh - 120px); overflow-y: auto; }
    .toc-title { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8; padding: 0 12px 10px; }
    .toc-link { display: block; padding: 8px 12px; font-size: 0.875rem; font-weight: 450; color: #64748b; border-radius: 6px; transition: all 150ms; text-align: left; width: 100%; border: none; background: none; cursor: pointer; line-height: 1.4; }
    .toc-link:hover { background: #f1f5f9; color: #0f172a; }
    .toc-link.active { background: #eff6ff; color: #1e40af; font-weight: 600; }

    /* Mobile TOC */
    .mobile-toc { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 24px; }
    @media (min-width: 1024px) { .mobile-toc { display: none; } }
    .mobile-toc-btn { font-size: 0.8125rem; font-weight: 500; color: #64748b; background: #ffffff; border: 1px solid #e2e8f0; padding: 6px 14px; border-radius: 100px; cursor: pointer; transition: all 150ms; }
    .mobile-toc-btn:hover { border-color: #3b82f6; color: #1e40af; }

    /* Content */
    .legal-content { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: clamp(24px, 4vw, 44px); box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
    .legal-content-header { padding-bottom: 20px; border-bottom: 1px solid #f1f5f9; margin-bottom: 28px; }
    .legal-content-header h1 { font-size: clamp(1.75rem, 3.5vw, 2.25rem); color: #0f172a; margin-bottom: 8px; font-weight: 700; }
    .legal-meta { font-size: 0.875rem; color: #94a3b8; display: flex; flex-wrap: wrap; gap: 20px; }
    .legal-meta span { display: flex; align-items: center; gap: 6px; }
    .legal-meta i { width: 15px; height: 15px; }

    .legal-section { margin-bottom: 36px; scroll-margin-top: 100px; }
    .legal-section:last-child { margin-bottom: 0; }
    .legal-section h2 { font-size: 1.25rem; font-weight: 700; color: #0f172a; margin-bottom: 14px; padding-bottom: 8px; border-bottom: 1px solid #f1f5f9; }
    .legal-section h3 { font-size: 1rem; font-weight: 600; color: #1e293b; margin-top: 20px; margin-bottom: 10px; }
    .legal-section p { margin-bottom: 14px; line-height: 1.75; color: #475569; }
    .legal-section p:last-child { margin-bottom: 0; }

    .legal-section ul { margin: 10px 0 16px 24px; list-style: disc; }
    .legal-section li { margin-bottom: 8px; line-height: 1.65; color: #475569; }

    .legal-section ol { margin: 10px 0 16px 24px; list-style: decimal; }
    .legal-section ol li { margin-bottom: 8px; line-height: 1.65; color: #475569; }

    .legal-section strong { color: #0f172a; font-weight: 600; }
    .legal-section a { color: #2563eb; text-decoration: underline; text-underline-offset: 2px; }
    .legal-section a:hover { color: #1d4ed8; }

    .legal-highlight { background: #eff6ff; border-left: 4px solid #2563eb; padding: 16px 20px; border-radius: 0 8px 8px 0; margin: 16px 0; font-size: 0.9375rem; line-height: 1.7; color: #1e40af; }
    .legal-highlight strong { color: #1e3a8a; }

    .legal-warning { background: #fffbeb; border-left: 4px solid #f59e0b; padding: 16px 20px; border-radius: 0 8px 8px 0; margin: 16px 0; font-size: 0.9375rem; line-height: 1.7; color: #92400e; }

    .legal-table-wrap { overflow-x: auto; margin: 16px 0; -webkit-overflow-scrolling: touch; }
    .legal-table { width: 100%; border-collapse: collapse; min-width: 480px; font-size: 0.875rem; }
    .legal-table th { text-align: left; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; padding: 12px 16px; border-bottom: 2px solid #e2e8f0; background: #f8fafc; }
    .legal-table td { padding: 12px 16px; border-bottom: 1px solid #f1f5f9; color: #475569; line-height: 1.5; }
</style>

<div class="breadcrumb-bar">
    <div class="container">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="<?= url('/') ?>">Home</a>
            <i data-lucide="chevron-right"></i>
            <span class="current">Terms of Service</span>
        </nav>
    </div>
</div>

<section class="legal-layout">
    <div class="container">
        <div class="legal-grid">

            <!-- Sidebar TOC -->
            <aside class="legal-toc" id="legalToc">
                <div class="toc-card">
                    <div class="toc-title">Contents</div>
                    <button class="toc-link active" data-target="sec-acceptance">Acceptance of Terms</button>
                    <button class="toc-link" data-target="sec-service">Description of Service</button>
                    <button class="toc-link" data-target="sec-account">User Accounts</button>
                    <button class="toc-link" data-target="sec-conduct">User Conduct</button>
                    <button class="toc-link" data-target="sec-scholarships">Scholarship Information</button>
                    <button class="toc-link" data-target="sec-subscription">Subscriptions & Payments</button>
                    <button class="toc-link" data-target="sec-refunds">Refund Policy</button>
                    <button class="toc-link" data-target="sec-intellectual">Intellectual Property</button>
                    <button class="toc-link" data-target="sec-privacy">Privacy</button>
                    <button class="toc-link" data-target="sec-limitation">Limitation of Liability</button>
                    <button class="toc-link" data-target="sec-disclaimer">Disclaimers</button>
                    <button class="toc-link" data-target="sec-termination">Termination</button>
                    <button class="toc-link" data-target="sec-changes">Changes to Terms</button>
                    <button class="toc-link" data-target="sec-governing">Governing Law</button>
                    <button class="toc-link" data-target="sec-contact">Contact</button>
                </div>
            </aside>

            <!-- Content -->
            <div class="legal-content">

                <div class="legal-content-header">
                    <h1>Terms of Service</h1>
                    <div class="legal-meta">
                        <span><i data-lucide="calendar"></i> Effective: July 1, 2025</span>
                        <span><i data-lucide="rotate-ccw"></i> Last Updated: September 1, 2026</span>
                    </div>
                </div>

                <!-- Mobile TOC -->
                <div class="mobile-toc" id="mobileToc">
                    <button class="mobile-toc-btn" data-target="sec-acceptance">Acceptance</button>
                    <button class="mobile-toc-btn" data-target="sec-service">Service</button>
                    <button class="mobile-toc-btn" data-target="sec-account">Accounts</button>
                    <button class="mobile-toc-btn" data-target="sec-conduct">Conduct</button>
                    <button class="mobile-toc-btn" data-target="sec-scholarships">Scholarships</button>
                    <button class="mobile-toc-btn" data-target="sec-subscription">Subscriptions</button>
                    <button class="mobile-toc-btn" data-target="sec-refunds">Refunds</button>
                    <button class="mobile-toc-btn" data-target="sec-intellectual">IP</button>
                    <button class="mobile-toc-btn" data-target="sec-privacy">Privacy</button>
                    <button class="mobile-toc-btn" data-target="sec-limitation">Liability</button>
                    <button class="mobile-toc-btn" data-target="sec-disclaimer">Disclaimers</button>
                    <button class="mobile-toc-btn" data-target="sec-termination">Termination</button>
                    <button class="mobile-toc-btn" data-target="sec-changes">Changes</button>
                    <button class="mobile-toc-btn" data-target="sec-governing">Law</button>
                    <button class="mobile-toc-btn" data-target="sec-contact">Contact</button>
                </div>

                <!-- 1. Acceptance -->
                <div class="legal-section" id="sec-acceptance">
                    <h2>1. Acceptance of Terms</h2>
                    <p>By accessing or using the ScholarPlanner platform ("Service"), you agree to be bound by these Terms of Service ("Terms"). If you do not agree to these Terms, you may not use the Service.</p>
                    <p>These Terms constitute a legally binding agreement between you ("User") and ScholarPlanner. By creating an account or using any part of the Service, you acknowledge that you have read, understood, and agree to be bound by these Terms.</p>
                </div>

                <!-- 2. Description -->
                <div class="legal-section" id="sec-service">
                    <h2>2. Description of Service</h2>
                    <p>ScholarPlanner provides a comprehensive scholarship discovery platform that allows students to:</p>
                    <ul>
                        <li>Build academic student profiles including GPA, education background, and preferences.</li>
                        <li>Browse verified international and domestic scholarship opportunities.</li>
                        <li>Receive automated qualification matching scores.</li>
                        <li>Receive timely WhatsApp and email alerts for newly matching scholarships and deadline reminders.</li>
                        <li>Track application statuses and prepare required application documents.</li>
                    </ul>
                    <div class="legal-highlight">
                        <strong>Important:</strong> ScholarPlanner is an independent scholarship matching and notification service. We do not award scholarships, represent official universities, or guarantee acceptance decisions.
                    </div>
                </div>

                <!-- 3. User Accounts -->
                <div class="legal-section" id="sec-account">
                    <h2>3. User Accounts</h2>
                    <p>When registering an account on ScholarPlanner, you agree to provide authentic and accurate academic information. You are responsible for safeguarding your login credentials.</p>
                </div>

                <!-- 4. User Conduct -->
                <div class="legal-section" id="sec-conduct">
                    <h2>4. User Conduct</h2>
                    <p>You agree not to use automated bots, scrapers, or unauthorized penetration testing tools against our systems, and not to provide fraudulent profile information or violate applicable communication laws.</p>
                </div>

                <!-- 5. Scholarship Information -->
                <div class="legal-section" id="sec-scholarships">
                    <h2>5. Scholarship Information</h2>
                    <div class="legal-warning">
                        <strong>Notice:</strong> Scholarship criteria, funding amounts, and deadlines are determined solely by official scholarship bodies and may change. Students should always consult the official application portal before submitting applications.
                    </div>
                </div>

                <!-- 6. Subscriptions -->
                <div class="legal-section" id="sec-subscription">
                    <h2>6. Subscriptions & Payments</h2>
                    <p>ScholarPlanner offers Free and Premium subscription tiers. Premium subscriptions unlock direct WhatsApp scholarship alerts, advanced dashboard tools, document readiness checks, and unlimited scholarship comparisons.</p>
                    <p>Payments are processed via authorized payment partners (JazzCash, CashMaal, cards). Subscriptions are billed per designated billing cycles.</p>
                </div>

                <!-- 7. Refunds -->
                <div class="legal-section" id="sec-refunds">
                    <h2>7. Refund Policy</h2>
                    <div class="legal-table-wrap">
                        <table class="legal-table">
                            <thead>
                                <tr>
                                    <th>Scenario</th>
                                    <th>Policy</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Initial subscription cancellation within 48 hours</td>
                                    <td>Eligible for full refund upon support review</td>
                                </tr>
                                <tr>
                                    <td>Mid-cycle subscription cancellation</td>
                                    <td>Access remains active until the end of the paid billing period</td>
                                </tr>
                                <tr>
                                    <td>Terms of service violation termination</td>
                                    <td>No refund provided</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 8. Intellectual Property -->
                <div class="legal-section" id="sec-intellectual">
                    <h2>8. Intellectual Property</h2>
                    <p>The ScholarPlanner brand, software platform, matching engine algorithms, and website assets are the exclusive intellectual property of ScholarPlanner.</p>
                </div>

                <!-- 9. Privacy -->
                <div class="legal-section" id="sec-privacy">
                    <h2>9. Privacy</h2>
                    <p>Your privacy is paramount. Please review our <a href="<?= url('/privacy') ?>">Privacy Policy</a> for full details on data protection, security, and WhatsApp message consent.</p>
                </div>

                <!-- 10. Limitation of Liability -->
                <div class="legal-section" id="sec-limitation">
                    <h2>10. Limitation of Liability</h2>
                    <p>To the maximum extent permitted by applicable law, ScholarPlanner shall not be liable for any indirect, consequential, or incidental damages resulting from scholarship admissions decisions or platform usage.</p>
                </div>

                <!-- 11. Disclaimers -->
                <div class="legal-section" id="sec-disclaimer">
                    <h2>11. Disclaimers</h2>
                    <p>The Service is provided on an "as is" and "as available" basis without warranties of any kind regarding third-party university outcomes.</p>
                </div>

                <!-- 12. Termination -->
                <div class="legal-section" id="sec-termination">
                    <h2>12. Termination</h2>
                    <p>You may cancel your account at any time. We reserve the right to suspend accounts that violate platform policies or abuse communication channels.</p>
                </div>

                <!-- 13. Changes -->
                <div class="legal-section" id="sec-changes">
                    <h2>13. Changes to Terms</h2>
                    <p>We may update these Terms periodically. Notice of significant changes will be published on the website.</p>
                </div>

                <!-- 14. Governing Law -->
                <div class="legal-section" id="sec-governing">
                    <h2>14. Governing Law</h2>
                    <p>These Terms are governed by and construed in accordance with the laws of Pakistan.</p>
                </div>

                <!-- 15. Contact -->
                <div class="legal-section" id="sec-contact">
                    <h2>15. Contact Information</h2>
                    <p>If you have any questions regarding these Terms, please reach out through our <a href="<?= url('/contact') ?>">Contact Page</a>.</p>
                </div>

            </div><!-- /legal-content -->
        </div><!-- /legal-grid -->
    </div><!-- /container -->
</section>

<script>
    // ===== TOC NAVIGATION =====
    var allTocLinks = document.querySelectorAll('.toc-link, .mobile-toc-btn');
    allTocLinks.forEach(function(link){
        link.addEventListener('click', function(){
            var targetId = this.dataset.target;
            var target = document.getElementById(targetId);
            if(!target) return;

            allTocLinks.forEach(function(l){ l.classList.remove('active') });
            this.classList.add('active');

            var header = document.querySelector('.header');
            var headerH = header ? header.offsetHeight : 70;
            var top = target.getBoundingClientRect().top + window.scrollY - headerH - 16;
            window.scrollTo({ top: top, behavior: 'smooth' });
        });
    });

    // Active TOC tracking
    var sections = document.querySelectorAll('.legal-section');
    var tocLinksDesktop = document.querySelectorAll('.toc-link');

    function updateActiveToc() {
        var header = document.querySelector('.header');
        var headerH = header ? header.offsetHeight : 70;
        var current = '';
        sections.forEach(function(sec){
            var rect = sec.getBoundingClientRect();
            if(rect.top <= headerH + 80) current = sec.id;
        });
        if(current) {
            tocLinksDesktop.forEach(function(l){
                l.classList.toggle('active', l.dataset.target === current);
            });
        }
    }

    var tocThrottle = false;
    window.addEventListener('scroll', function(){
        if(!tocThrottle){
            tocThrottle = true;
            requestAnimationFrame(function(){ updateActiveToc(); tocThrottle = false; });
        }
    }, { passive: true });
</script>

<?php include ROOT_PATH . '/app/Views/layouts/public_footer.php'; ?>
