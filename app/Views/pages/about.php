<?php
$title = $title ?? 'About Us — ScholarPlanner';
$description = $description ?? 'Learn about ScholarPlanner — a scholarship discovery platform matching opportunities to student profiles and sending personalized WhatsApp alerts.';
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

    /* ===== ABOUT HERO ===== */
    .about-hero { padding: clamp(48px, 7vw, 80px) 0 clamp(40px, 6vw, 64px); background: linear-gradient(180deg, #eff6ff 0%, #f8fafc 100%); }
    .hero-grid { display: grid; grid-template-columns: 1fr; gap: clamp(32px, 5vw, 60px); align-items: center; }
    @media (min-width: 960px) {
        .hero-grid { grid-template-columns: 1.15fr 0.85fr; }
    }
    .section-label { display: inline-flex; align-items: center; gap: 6px; font-size: 0.8125rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: #2563eb; background: #dbeafe; padding: 6px 14px; border-radius: 100px; margin-bottom: 16px; }
    .hero-text h1 { font-size: clamp(2rem, 4vw, 2.85rem); color: #0f172a; line-height: 1.2; margin-bottom: 18px; font-weight: 700; }
    .hero-text p { font-size: 1.0625rem; color: #475569; line-height: 1.7; margin-bottom: 16px; }
    .hero-actions { display: flex; flex-wrap: wrap; gap: 14px; margin-top: 24px; }

    /* Visual Card */
    .hero-card-stack { position: relative; padding: 20px; }
    .hero-card-main { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 20px; padding: 32px 24px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.08); position: relative; z-index: 2; }
    .hero-card-header { display: flex; align-items: center; gap: 14px; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid #f1f5f9; }
    .hero-card-logo { width: 44px; height: 44px; background: #1e40af; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #ffffff; }
    .hero-card-name { font-size: 1.125rem; font-weight: 700; color: #0f172a; }
    .hero-card-sub { font-size: 0.8125rem; color: #64748b; }
    .hero-card-metrics { display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px; }
    .hero-metric { background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 12px; padding: 16px; text-align: center; }
    .hero-metric-val { font-size: 1.5rem; font-weight: 700; color: #1e40af; }
    .hero-metric-lbl { font-size: 0.75rem; color: #64748b; text-transform: uppercase; font-weight: 600; margin-top: 4px; }

    /* ===== PROBLEM STRIP ===== */
    .problem-strip { background: #0f172a; color: #ffffff; padding: clamp(32px, 5vw, 48px) 0; }
    .strip-grid { display: grid; grid-template-columns: 1fr; gap: 24px; text-align: center; }
    @media (min-width: 640px) { .strip-grid { grid-template-columns: repeat(3, 1fr); } }
    .strip-num { font-size: clamp(2rem, 3.5vw, 2.75rem); font-weight: 700; color: #60a5fa; margin-bottom: 4px; }
    .strip-label { font-size: 0.9375rem; color: #94a3b8; line-height: 1.5; }

    /* ===== WHAT WE DO ===== */
    .section { padding: clamp(48px, 7vw, 72px) 0; }
    .section-alt { background: #ffffff; }
    .section-header { margin-bottom: 40px; }
    .section-header.center { text-align: center; max-width: 680px; margin-left: auto; margin-right: auto; }
    .section-header h2 { font-size: clamp(1.75rem, 3.5vw, 2.25rem); color: #0f172a; font-weight: 700; margin-bottom: 10px; }
    .section-header p { font-size: 1rem; color: #64748b; line-height: 1.6; }

    .do-grid { display: grid; grid-template-columns: 1fr; gap: 24px; }
    @media (min-width: 768px) { .do-grid { grid-template-columns: repeat(3, 1fr); } }
    .do-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 28px 22px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
    .do-icon { width: 44px; height: 44px; border-radius: 12px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; margin-bottom: 18px; }
    .do-card h3 { font-size: 1.125rem; font-weight: 700; color: #0f172a; margin-bottom: 8px; }
    .do-card p { font-size: 0.9375rem; color: #64748b; line-height: 1.65; }

    /* ===== STORY ===== */
    .story-grid { display: grid; grid-template-columns: 1fr; gap: clamp(32px, 5vw, 56px); align-items: center; }
    @media (min-width: 960px) { .story-grid { grid-template-columns: 1.1fr 0.9fr; } }
    .story-text h2 { font-size: clamp(1.75rem, 3.5vw, 2.25rem); font-weight: 700; color: #0f172a; margin-bottom: 14px; }
    .story-text p { font-size: 1rem; color: #475569; line-height: 1.75; margin-bottom: 14px; }

    .story-visual { display: flex; flex-direction: column; gap: 14px; }
    .story-point { display: flex; align-items: flex-start; gap: 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px 16px; }
    .story-point-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .story-point-icon.blue { background: #eff6ff; color: #2563eb; }
    .story-point-icon.green { background: #ecfdf5; color: #059669; }
    .story-point-icon.amber { background: #fffbeb; color: #d97706; }
    .story-point h4 { font-size: 0.9375rem; font-weight: 700; color: #0f172a; margin-bottom: 2px; }
    .story-point p { font-size: 0.875rem; color: #64748b; margin: 0; line-height: 1.5; }

    /* ===== CTA ===== */
    .cta-block { background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%); border-radius: 20px; padding: clamp(40px, 6vw, 64px) clamp(24px, 5vw, 48px); text-align: center; color: #ffffff; margin-top: 48px; }
    .cta-block h2 { font-size: clamp(1.75rem, 3.5vw, 2.25rem); margin-bottom: 12px; color: #ffffff; }
    .cta-block p { font-size: 1.0625rem; color: #bfdbfe; max-width: 520px; margin: 0 auto 28px; line-height: 1.6; }
    .cta-block .btn-primary { background: #ffffff; color: #1e40af; }
    .cta-block .btn-primary:hover { background: #f8fafc; }
</style>

<div class="breadcrumb-bar">
    <div class="container">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="<?= url('/') ?>">Home</a>
            <i data-lucide="chevron-right"></i>
            <span class="current">About</span>
        </nav>
    </div>
</div>

<!-- Hero Section -->
<section class="about-hero">
    <div class="container">
        <div class="hero-grid">
            <div class="hero-text">
                <div class="section-label"><i data-lucide="info"></i> About ScholarPlanner</div>
                <h1>We Built This Because Searching for Scholarships Shouldn't Be This Hard.</h1>
                <p>ScholarPlanner is an intelligent scholarship discovery and matching platform. Students build a comprehensive academic profile, our matching engine identifies eligible opportunities, and we deliver personalized alerts directly through WhatsApp and email.</p>
                <p>We believe international higher education should be accessible. We organize fragmented scholarship announcements from across the globe and connect students with official application links.</p>
                <div class="hero-actions">
                    <a href="<?= url('/register') ?>" class="btn btn-primary">Create Your Profile</a>
                    <a href="<?= url('/contact') ?>" class="btn btn-secondary">Get in Touch</a>
                </div>
            </div>

            <div class="hero-visual">
                <div class="hero-card-stack">
                    <div class="hero-card-main">
                        <div class="hero-card-header">
                            <div class="hero-card-logo"><i data-lucide="graduation-cap"></i></div>
                            <div>
                                <div class="hero-card-name">ScholarPlanner</div>
                                <div class="hero-card-sub">Intelligent Scholarship Platform</div>
                            </div>
                        </div>
                        <div class="hero-card-metrics">
                            <div class="hero-metric">
                                <div class="hero-metric-val">100+</div>
                                <div class="hero-metric-lbl">Scholarships</div>
                            </div>
                            <div class="hero-metric">
                                <div class="hero-metric-val">25+</div>
                                <div class="hero-metric-lbl">Countries</div>
                            </div>
                            <div class="hero-metric">
                                <div class="hero-metric-val">4</div>
                                <div class="hero-metric-lbl">Degree Levels</div>
                            </div>
                            <div class="hero-metric">
                                <div class="hero-metric-val">100%</div>
                                <div class="hero-metric-lbl">Verified</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Problem Strip -->
<section class="problem-strip">
    <div class="container">
        <div class="strip-grid">
            <div class="strip-item">
                <div class="strip-num">10+</div>
                <div class="strip-label">Websites students manually check<br>for scholarships</div>
            </div>
            <div class="strip-item">
                <div class="strip-num">Hours</div>
                <div class="strip-label">Wasted each week<br>sifting through expired links</div>
            </div>
            <div class="strip-item">
                <div class="strip-num">Missed</div>
                <div class="strip-label">Deadlines passing before<br>students even discover them</div>
            </div>
        </div>
    </div>
</section>

<!-- What We Do -->
<section class="section">
    <div class="container">
        <div class="section-header center">
            <div class="section-label"><i data-lucide="target"></i> What We Do</div>
            <h2>Three Things, Done Exceptionally Well</h2>
            <p>Everything we build serves one core mission: helping students discover, match, and apply for life-changing scholarship opportunities.</p>
        </div>
        <div class="do-grid">
            <div class="do-card">
                <div class="do-icon"><i data-lucide="search"></i></div>
                <h3>1. Discover</h3>
                <p>We systematically aggregate scholarship listings from official universities, embassies, and government ministries into a structured, searchable catalog.</p>
            </div>
            <div class="do-card">
                <div class="do-icon"><i data-lucide="bell"></i></div>
                <h3>2. Notify</h3>
                <p>Instead of manually checking websites, students receive automated WhatsApp and email alerts matching their field, degree level, and GPA.</p>
            </div>
            <div class="do-card">
                <div class="do-icon"><i data-lucide="file-text"></i></div>
                <h3>3. Track & Prepare</h3>
                <p>Detailed document checklists, deadline reminders, and direct application links empower students to submit strong, timely applications.</p>
            </div>
        </div>
    </div>
</section>

<!-- Our Story -->
<section class="section section-alt">
    <div class="container">
        <div class="story-grid">
            <div class="story-text">
                <div class="section-label"><i data-lucide="book-open"></i> Our Story</div>
                <h2>Why We Built ScholarPlanner</h2>
                <p>Students often miss out on fully funded international scholarships not because they lack academic merit, but because application information is scattered across hundreds of obscure websites.</p>
                <p>We set out to bridge this information gap. By combining academic profiling with automated WhatsApp alerts, we ensure opportunities reach students directly wherever they are.</p>
                <p>Every scholarship listed on our platform links directly to the official university or government application portal.</p>
            </div>
            <div class="story-visual">
                <div class="story-point">
                    <div class="story-point-icon blue"><i data-lucide="eye"></i></div>
                    <div>
                        <h4>Identified the Friction</h4>
                        <p>Students overwhelmed by endless groups, broken links, and missed deadlines.</p>
                    </div>
                </div>
                <div class="story-point">
                    <div class="story-point-icon green"><i data-lucide="lightbulb"></i></div>
                    <div>
                        <h4>Engineered the Match Engine</h4>
                        <p>Academic profile matching comparing GPA, degree, and nationality eligibility.</p>
                    </div>
                </div>
                <div class="story-point">
                    <div class="story-point-icon amber"><i data-lucide="message-circle"></i></div>
                    <div>
                        <h4>Integrated WhatsApp Alerts</h4>
                        <p>Real-time notifications sent straight to the student's mobile device.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- CTA Block -->
        <div class="cta-block">
            <h2>Ready to Discover Your Next Scholarship?</h2>
            <p>Join students who use ScholarPlanner to stay ahead of international scholarship deadlines.</p>
            <a href="<?= url('/register') ?>" class="btn btn-primary">Create Your Free Profile</a>
        </div>
    </div>
</section>

<?php include ROOT_PATH . '/app/Views/layouts/public_footer.php'; ?>
