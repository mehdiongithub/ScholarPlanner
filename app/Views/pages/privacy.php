<?php
$title = $title ?? 'Privacy Policy — ScholarPlanner';
$description = $description ?? 'Privacy Policy for ScholarPlanner. Learn how we collect, use, store, and protect your personal information.';
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

    .legal-success { background: #ecfdf5; border-left: 4px solid #10b981; padding: 16px 20px; border-radius: 0 8px 8px 0; margin: 16px 0; font-size: 0.9375rem; line-height: 1.7; color: #065f46; }

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
            <span class="current">Privacy Policy</span>
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
                    <button class="toc-link active" data-target="sec-overview">Overview</button>
                    <button class="toc-link" data-target="sec-collect">Information We Collect</button>
                    <button class="toc-link" data-target="sec-use">How We Use Information</button>
                    <button class="toc-link" data-target="sec-whatsapp">WhatsApp Data</button>
                    <button class="toc-link" data-target="sec-cookies">Cookies</button>
                    <button class="toc-link" data-target="sec-share">Third-Party Sharing</button>
                    <button class="toc-link" data-target="sec-storage">Data Storage</button>
                    <button class="toc-link" data-target="sec-security">Data Security</button>
                    <button class="toc-link" data-target="sec-retention">Data Retention</button>
                    <button class="toc-link" data-target="sec-rights">Your Rights</button>
                    <button class="toc-link" data-target="sec-children">Children's Privacy</button>
                    <button class="toc-link" data-target="sec-changes">Changes to This Policy</button>
                    <button class="toc-link" data-target="sec-contact">Contact Us</button>
                </div>
            </aside>

            <!-- Content -->
            <div class="legal-content">

                <div class="legal-content-header">
                    <h1>Privacy Policy</h1>
                    <div class="legal-meta">
                        <span><i data-lucide="calendar"></i> Effective: July 1, 2025</span>
                        <span><i data-lucide="rotate-ccw"></i> Last Updated: September 1, 2026</span>
                    </div>
                </div>

                <!-- Mobile TOC -->
                <div class="mobile-toc" id="mobileToc">
                    <button class="mobile-toc-btn" data-target="sec-overview">Overview</button>
                    <button class="mobile-toc-btn" data-target="sec-collect">Collection</button>
                    <button class="mobile-toc-btn" data-target="sec-use">Usage</button>
                    <button class="mobile-toc-btn" data-target="sec-whatsapp">WhatsApp</button>
                    <button class="mobile-toc-btn" data-target="sec-cookies">Cookies</button>
                    <button class="mobile-toc-btn" data-target="sec-share">Sharing</button>
                    <button class="mobile-toc-btn" data-target="sec-storage">Storage</button>
                    <button class="mobile-toc-btn" data-target="sec-security">Security</button>
                    <button class="mobile-toc-btn" data-target="sec-retention">Retention</button>
                    <button class="mobile-toc-btn" data-target="sec-rights">Your Rights</button>
                    <button class="mobile-toc-btn" data-target="sec-children">Children</button>
                    <button class="mobile-toc-btn" data-target="sec-changes">Changes</button>
                    <button class="mobile-toc-btn" data-target="sec-contact">Contact</button>
                </div>

                <!-- 1. Overview -->
                <div class="legal-section" id="sec-overview">
                    <h2>1. Overview</h2>
                    <p>ScholarPlanner ("we", "us", or "our") is committed to protecting your privacy. This Privacy Policy explains how we collect, use, disclose, store, and protect your personal information when you use our website and services (collectively, the "Service").</p>
                    <div class="legal-highlight">
                        <strong>Summary:</strong> We collect information you provide directly (profile, contact, payment), information generated by your use (activity logs), and limited technical data. We use this information to provide the Service, send notifications, and improve the platform. We do not sell your personal information to third parties.
                    </div>
                    <p>By using the Service, you consent to the practices described in this policy. If you do not agree, please do not use the Service.</p>
                </div>

                <!-- 2. Information We Collect -->
                <div class="legal-section" id="sec-collect">
                    <h2>2. Information We Collect</h2>

                    <h3>2.1 Information You Provide</h3>
                    <div class="legal-table-wrap">
                        <table class="legal-table">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Data Collected</th>
                                    <th>Required?</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Account</td>
                                    <td>Name, email, password</td>
                                    <td>Yes</td>
                                </tr>
                                <tr>
                                    <td>Profile</td>
                                    <td>Education level, degree, field of study, CGPA, country, age, study preferences, funding preferences</td>
                                    <td>For matching</td>
                                </tr>
                                <tr>
                                    <td>WhatsApp</td>
                                    <td>WhatsApp phone number (for alerts)</td>
                                    <td>For alerts</td>
                                </tr>
                                <tr>
                                    <td>Payment</td>
                                    <td>Transaction reference, payment method used, amount, date</td>
                                    <td>For subscriptions</td>
                                </tr>
                                <tr>
                                    <td>Support</td>
                                    <td>Name, email, message content</td>
                                    <td>When contacting us</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <h3>2.2 Information Collected Automatically</h3>
                    <ul>
                        <li><strong>Device information:</strong> Browser type, operating system, device type, screen resolution</li>
                        <li><strong>Usage data:</strong> Pages visited, features used, time spent, click patterns, scholarship interactions</li>
                        <li><strong>Technical data:</strong> IP address (anonymized where possible), log data, error reports</li>
                    </ul>

                    <h3>2.3 Information We Do NOT Collect</h3>
                    <div class="legal-success">
                        <strong>We do not collect:</strong> your national ID number, passport number, academic transcripts, financial records, or any sensitive government documents. We never ask for these and you should never share them through our platform.
                    </div>
                </div>

                <!-- 3. How We Use Information -->
                <div class="legal-section" id="sec-use">
                    <h2>3. How We Use Your Information</h2>
                    <p>We use your personal information for the following purposes:</p>
                    <ol>
                        <li><strong>Provide the Service:</strong> Create and manage your account, generate scholarship matches, display scholarship details, provide application links</li>
                        <li><strong>Send notifications:</strong> Deliver WhatsApp and email alerts about matching scholarships and deadline reminders</li>
                        <li><strong>Process payments:</strong> Process subscription payments and maintain transaction records</li>
                        <li><strong>Improve the Service:</strong> Analyze usage patterns to improve matching accuracy, user experience, and content quality</li>
                        <li><strong>Communicate with you:</strong> Respond to support requests, send account-related notifications, inform you of changes to the Service</li>
                        <li><strong>Prevent abuse:</strong> Detect and prevent fraudulent activity, spam, security threats, and violations of our Terms of Service</li>
                        <li><strong>Comply with law:</strong> Meet legal obligations and respond to lawful requests from authorities</li>
                    </ol>
                    <p>We do not use your personal information to build advertising profiles or sell data to data brokers.</p>
                </div>

                <!-- 4. WhatsApp Data -->
                <div class="legal-section" id="sec-whatsapp">
                    <h2>4. WhatsApp Notification Data</h2>
                    <p>When you subscribe to WhatsApp alerts:</p>
                    <ul>
                        <li>Your WhatsApp phone number is stored securely and used only for sending scholarship notification messages</li>
                        <li>Messages are sent through verified WhatsApp Business communication gateways (WACRM)</li>
                        <li>Message content is limited to scholarship name, match score, key details, and a link to the full opportunity page on our website</li>
                        <li>We do not read your WhatsApp messages, access your contacts, or receive any personal conversations from your device</li>
                    </ul>
                    <div class="legal-warning">
                        <strong>Note:</strong> By providing your WhatsApp number, you acknowledge that notification messages will be transmitted through WhatsApp infrastructure. You can stop WhatsApp notifications at any time from your dashboard notification preferences.
                    </div>
                </div>

                <!-- 5. Cookies -->
                <div class="legal-section" id="sec-cookies">
                    <h2>5. Cookies & Tracking Technologies</h2>
                    <p>We use essential cookies and session storage:</p>
                    <div class="legal-table-wrap">
                        <table class="legal-table">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Purpose</th>
                                    <th>Duration</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Essential</td>
                                    <td>Session authentication, security, CSRF protection</td>
                                    <td>Session</td>
                                </tr>
                                <tr>
                                    <td>Preference</td>
                                    <td>Remember your notification and display preferences</td>
                                    <td>Up to 1 year</td>
                                </tr>
                                <tr>
                                    <td>Analytics</td>
                                    <td>Understand how the Service is used (anonymized)</td>
                                    <td>Up to 2 years</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 6. Third-Party Sharing -->
                <div class="legal-section" id="sec-share">
                    <h2>6. Third-Party Sharing</h2>
                    <div class="legal-highlight">
                        <strong>We do not sell your personal information.</strong>
                    </div>
                    <p>We may share your information only with essential service providers bound by strict confidentiality:</p>
                    <ul>
                        <li><strong>Hosting and Infrastructure:</strong> Secure cloud database and web server providers.</li>
                        <li><strong>Payment Gateways:</strong> CashMaal, JazzCash for subscription processing.</li>
                        <li><strong>Communication Providers:</strong> WhatsApp Business messaging gateways and transactional email SMTP servers.</li>
                        <li><strong>Legal Compliance:</strong> When required by lawful court orders or authorized government requests.</li>
                    </ul>
                </div>

                <!-- 7. Data Storage -->
                <div class="legal-section" id="sec-storage">
                    <h2>7. Data Storage</h2>
                    <p>Your data is stored in high-security databases utilizing encrypted connections. We retain your profile data as long as your account remains active.</p>
                </div>

                <!-- 8. Data Security -->
                <div class="legal-section" id="sec-security">
                    <h2>8. Data Security</h2>
                    <p>We implement enterprise-grade technical and organizational measures to protect your personal information:</p>
                    <ul>
                        <li>Encryption in transit with modern TLS/HTTPS.</li>
                        <li>Strong password hashing (BCrypt with dynamic salting).</li>
                        <li>Strict CSRF tokens and SQL injection parameterized queries.</li>
                        <li>Automated session fixation and brute-force throttling protections.</li>
                    </ul>
                </div>

                <!-- 9. Data Retention -->
                <div class="legal-section" id="sec-retention">
                    <h2>9. Data Retention</h2>
                    <p>Account and profile data are retained while your account is active. You can request complete deletion of your profile and data at any time through our contact or support channels.</p>
                </div>

                <!-- 10. Your Rights -->
                <div class="legal-section" id="sec-rights">
                    <h2>10. Your Rights</h2>
                    <p>You have the right to access, review, modify, export, or delete your personal profile at any time via your <a href="<?= url('/profile') ?>">Profile Dashboard</a>.</p>
                </div>

                <!-- 11. Children's Privacy -->
                <div class="legal-section" id="sec-children">
                    <h2>11. Children's Privacy</h2>
                    <p>Our platform is designed for higher education and university students. We do not knowingly collect personal data from individuals under 16 years of age.</p>
                </div>

                <!-- 12. Changes -->
                <div class="legal-section" id="sec-changes">
                    <h2>12. Changes to This Policy</h2>
                    <p>We may update this Privacy Policy periodically. Significant changes will be announced on the platform or via email alerts.</p>
                </div>

                <!-- 13. Contact -->
                <div class="legal-section" id="sec-contact">
                    <h2>13. Contact Us About Privacy</h2>
                    <p>For any privacy-related inquiries or data requests, please visit our <a href="<?= url('/contact') ?>">Contact Page</a> or reach out to our team.</p>
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

    // Active TOC tracking on scroll
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
