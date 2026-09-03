<?php
$title = $title ?? 'Contact Us — ScholarPlanner';
$description = $description ?? 'Get in touch with ScholarPlanner. Send us a message about scholarships, your account, partnerships, or general support.';
include ROOT_PATH . '/app/Views/layouts/public_header.php';
?>

<style>
    /* ===== BREADCRUMB ===== */
    .breadcrumb-bar { background: var(--bg-50, #f8fafc); border-bottom: 1px solid var(--border-light, #f1f5f9); padding: 14px 0; }
    .breadcrumb { display: flex; align-items: center; gap: 8px; font-size: 0.875rem; color: #94a3b8; flex-wrap: wrap; }
    .breadcrumb a { color: #64748b; transition: color 150ms; }
    .breadcrumb a:hover { color: #1e40af; }
    .breadcrumb i { width: 14px; height: 14px; flex-shrink: 0; }
    .breadcrumb .current { color: #334155; font-weight: 500; }

    /* ===== HERO ===== */
    .contact-hero { background: linear-gradient(180deg, #eff6ff 0%, #f8fafc 100%); padding: clamp(40px, 6vw, 64px) 0 32px; text-align: center; border-bottom: 1px solid #e2e8f0; }
    .contact-hero h1 { font-size: clamp(2rem, 4vw, 2.75rem); color: #0f172a; margin-bottom: 12px; font-weight: 700; }
    .contact-hero p { font-size: 1.0625rem; color: #64748b; max-width: 600px; margin: 0 auto; line-height: 1.6; }

    /* ===== CONTACT LAYOUT ===== */
    .contact-layout { padding: clamp(32px, 5vw, 60px) 0 clamp(48px, 8vw, 80px); background: #f8fafc; }
    .contact-grid { display: grid; grid-template-columns: 1fr; gap: 32px; }
    @media (min-width: 1024px) {
        .contact-grid { grid-template-columns: 1.25fr 0.75fr; align-items: start; }
    }

    /* Info cards */
    .info-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
    .info-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 20px; display: flex; align-items: center; gap: 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
    .info-card-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .info-card-icon.blue { background: #eff6ff; color: #2563eb; }
    .info-card-icon.green { background: #ecfdf5; color: #059669; }
    .info-card-icon.amber { background: #fffbeb; color: #d97706; }
    .info-card-label { font-size: 0.75rem; text-transform: uppercase; font-weight: 700; color: #94a3b8; letter-spacing: 0.05em; }
    .info-card-value { font-size: 0.9375rem; font-weight: 600; color: #0f172a; margin-top: 2px; }

    /* Form card */
    .form-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 20px; padding: clamp(24px, 4vw, 40px); box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
    .form-card h2 { font-size: 1.35rem; font-weight: 700; color: #0f172a; margin-bottom: 6px; }
    .form-card p { font-size: 0.9375rem; color: #64748b; margin-bottom: 24px; }

    .form-row { display: grid; grid-template-columns: 1fr; gap: 16px; }
    @media (min-width: 640px) { .form-row { grid-template-columns: 1fr 1fr; } }
    .form-group { margin-bottom: 18px; }
    .form-label { display: block; font-size: 0.875rem; font-weight: 600; color: #334155; margin-bottom: 6px; }
    .form-label span.required { color: #ef4444; }
    .form-input, .form-select, .form-textarea { width: 100%; padding: 12px 16px; font-size: 0.9375rem; border-radius: 10px; border: 1px solid #cbd5e1; background: #ffffff; color: #0f172a; outline: none; transition: border-color 150ms, box-shadow 150ms; }
    .form-input:focus, .form-select:focus, .form-textarea:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.15); }
    .form-textarea { resize: vertical; min-height: 120px; }

    .form-submit-area { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px; margin-top: 24px; padding-top: 20px; border-top: 1px solid #f1f5f9; }
    .form-note { display: flex; align-items: center; gap: 6px; font-size: 0.8125rem; color: #94a3b8; }

    /* Flash alerts */
    .alert-success { background: #ecfdf5; border-left: 4px solid #10b981; color: #065f46; padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9375rem; }
    .alert-danger { background: #fef2f2; border-left: 4px solid #ef4444; color: #991b1b; padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9375rem; }

    /* Sidebar Cards */
    .sidebar-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
    .sidebar-card-title { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8; margin-bottom: 14px; }
    .wa-contact-card { background: linear-gradient(135deg, #065f46 0%, #047857 100%); color: #ffffff; }
    .wa-contact-card h3 { font-size: 1.15rem; color: #ffffff; margin-bottom: 8px; }
    .wa-contact-card p { font-size: 0.875rem; color: #d1fae5; line-height: 1.6; margin-bottom: 18px; }
    .wa-contact-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; background: #ffffff; color: #065f46; font-weight: 600; padding: 10px 20px; border-radius: 8px; width: 100%; transition: all 150ms; }
    .wa-contact-btn:hover { background: #ecfdf5; }

    .faq-link-list { display: flex; flex-direction: column; gap: 8px; }
    .faq-link-item { display: flex; align-items: center; gap: 10px; padding: 8px 10px; border-radius: 8px; font-size: 0.875rem; color: #475569; font-weight: 500; transition: background 150ms; }
    .faq-link-item:hover { background: #f1f5f9; color: #1e40af; }
    .faq-link-item i { width: 16px; height: 16px; color: #3b82f6; }
</style>

<div class="breadcrumb-bar">
    <div class="container">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="<?= url('/') ?>">Home</a>
            <i data-lucide="chevron-right"></i>
            <span class="current">Contact Us</span>
        </nav>
    </div>
</div>

<!-- Hero -->
<section class="contact-hero">
    <div class="container">
        <h1>Get in Touch</h1>
        <p>Have a question about scholarship matching, your subscription, or partnership opportunities? Our support team is here to assist you.</p>
    </div>
</section>

<!-- Content -->
<section class="contact-layout">
    <div class="container">
        <div class="contact-grid">

            <!-- Form Area -->
            <div>
                <!-- Info cards -->
                <div class="info-cards">
                    <div class="info-card">
                        <div class="info-card-icon blue"><i data-lucide="mail"></i></div>
                        <div>
                            <div class="info-card-label">Email Support</div>
                            <div class="info-card-value">support@scholarplanner.com</div>
                        </div>
                    </div>
                    <div class="info-card">
                        <div class="info-card-icon green"><i data-lucide="message-circle"></i></div>
                        <div>
                            <div class="info-card-label">WhatsApp Support</div>
                            <div class="info-card-value">+92 316 3261056</div>
                        </div>
                    </div>
                    <div class="info-card">
                        <div class="info-card-icon amber"><i data-lucide="clock"></i></div>
                        <div>
                            <div class="info-card-label">Response Time</div>
                            <div class="info-card-value">Within 24–48 Hours</div>
                        </div>
                    </div>
                </div>

                <div class="form-card">
                    <h2>Send Us a Message</h2>
                    <p>Fill out the details below and we will get back to you promptly.</p>

                    <?php if (!empty($_SESSION['flash_success'])): ?>
                        <div class="alert-success">
                            <?= e($_SESSION['flash_success']) ?>
                            <?php unset($_SESSION['flash_success']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($_SESSION['flash_error'])): ?>
                        <div class="alert-danger">
                            <?= e($_SESSION['flash_error']) ?>
                            <?php unset($_SESSION['flash_error']); ?>
                        </div>
                    <?php endif; ?>

                    <form action="<?= url('/contact') ?>" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="name">Your Name <span class="required">*</span></label>
                                <input type="text" class="form-input" id="name" name="name" placeholder="e.g., Ghulam Mehdi" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="email">Email Address <span class="required">*</span></label>
                                <input type="email" class="form-input" id="email" name="email" placeholder="e.g., student@example.com" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="subject">Subject Topic <span class="required">*</span></label>
                            <select class="form-select" id="subject" name="subject" required>
                                <option value="General Question">General Question</option>
                                <option value="Scholarship Information">Scholarship Information</option>
                                <option value="WhatsApp Notifications">WhatsApp Alerts Issue</option>
                                <option value="Subscription & Billing">Subscription & Payment</option>
                                <option value="Academic Profile Help">Academic Profile Assistance</option>
                                <option value="Partnership">University / Institution Partnership</option>
                                <option value="Other">Other Query</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="message">Your Message <span class="required">*</span></label>
                            <textarea class="form-textarea" id="message" name="message" placeholder="Please describe how we can assist you..." required></textarea>
                        </div>

                        <div class="form-submit-area">
                            <button type="submit" class="btn btn-primary">
                                <i data-lucide="send" style="width:16px;height:16px;margin-right:6px"></i> Send Message
                            </button>
                            <span class="form-note">
                                <i data-lucide="shield-check" style="width:14px;height:14px"></i> Your information is kept strictly confidential.
                            </span>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Sidebar Info -->
            <aside class="contact-sidebar">
                <!-- WhatsApp Assistance Card -->
                <div class="sidebar-card wa-contact-card">
                    <div class="info-card-icon" style="background:rgba(255,255,255,0.2);color:#fff;margin-bottom:12px">
                        <i data-lucide="message-circle"></i>
                    </div>
                    <h3>Need Quick Assistance?</h3>
                    <p>Have a question about your active subscription or WhatsApp alerts? Reach out directly via WhatsApp support.</p>
                    <a href="https://wa.me/923163261056" target="_blank" class="wa-contact-btn">
                        <i data-lucide="message-circle" style="width:18px;height:18px"></i> Chat on WhatsApp
                    </a>
                </div>

                <!-- Helpful Resources Card -->
                <div class="sidebar-card">
                    <div class="sidebar-card-title">Quick Resources</div>
                    <div class="faq-link-list">
                        <a href="<?= url('/faq') ?>" class="faq-link-item">
                            <i data-lucide="help-circle"></i> Frequently Asked Questions
                        </a>
                        <a href="<?= url('/pricing') ?>" class="faq-link-item">
                            <i data-lucide="credit-card"></i> Plans & Pricing Overview
                        </a>
                        <a href="<?= url('/#how-it-works') ?>" class="faq-link-item">
                            <i data-lucide="layers"></i> How Scholarship Matching Works
                        </a>
                        <a href="<?= url('/about') ?>" class="faq-link-item">
                            <i data-lucide="info"></i> About ScholarPlanner
                        </a>
                        <a href="<?= url('/privacy') ?>" class="faq-link-item">
                            <i data-lucide="shield"></i> Privacy Policy & Data Terms
                        </a>
                        <a href="<?= url('/terms') ?>" class="faq-link-item">
                            <i data-lucide="file-text"></i> Terms of Service
                        </a>
                    </div>
                </div>
            </aside>

        </div>
    </div>
</section>

<?php include ROOT_PATH . '/app/Views/layouts/public_footer.php'; ?>
