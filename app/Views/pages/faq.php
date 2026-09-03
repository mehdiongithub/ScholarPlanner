<?php
$title = $title ?? 'Frequently Asked Questions — ScholarPlanner';
$description = $description ?? 'Find answers to common questions about ScholarPlanner — how matching works, WhatsApp alerts, pricing, subscriptions, and privacy.';
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

    /* ===== FAQ HERO ===== */
    .faq-hero { background: linear-gradient(180deg, #eff6ff 0%, #f8fafc 100%); padding: clamp(40px, 6vw, 64px) 0 32px; text-align: center; border-bottom: 1px solid #e2e8f0; }
    .faq-hero h1 { font-size: clamp(2rem, 4vw, 2.75rem); color: #0f172a; margin-bottom: 12px; font-weight: 700; }
    .faq-hero p { font-size: clamp(1rem, 2vw, 1.125rem); color: #64748b; max-width: 640px; margin: 0 auto 28px; line-height: 1.6; }

    /* Search bar */
    .faq-search-wrap { max-width: 540px; margin: 0 auto 24px; position: relative; }
    .faq-search-input { width: 100%; height: 50px; padding: 0 20px 0 48px; border-radius: 100px; border: 1px solid #cbd5e1; background: #ffffff; font-size: 0.9375rem; color: #0f172a; box-shadow: 0 2px 4px rgba(0,0,0,0.04); outline: none; transition: all 200ms; }
    .faq-search-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.15); }
    .faq-search-icon { position: absolute; left: 18px; top: 50%; transform: translateY(-50%); color: #94a3b8; width: 20px; height: 20px; }

    /* Category pills */
    .faq-cat-nav { display: flex; flex-wrap: wrap; justify-content: center; gap: 8px; margin-top: 16px; }
    .faq-cat-btn { display: inline-flex; align-items: center; gap: 6px; font-size: 0.875rem; font-weight: 500; padding: 8px 16px; border-radius: 100px; border: 1px solid #e2e8f0; background: #ffffff; color: #64748b; cursor: pointer; transition: all 150ms; }
    .faq-cat-btn:hover { border-color: #3b82f6; color: #1e40af; }
    .faq-cat-btn.active { background: #1e40af; border-color: #1e40af; color: #ffffff; }
    .faq-cat-btn i { width: 16px; height: 16px; }

    /* ===== FAQ LAYOUT ===== */
    .faq-layout { padding: clamp(32px, 5vw, 60px) 0 clamp(48px, 8vw, 80px); background: #f8fafc; }
    .faq-grid { display: grid; grid-template-columns: 1fr; gap: 32px; }
    @media (min-width: 1024px) {
        .faq-grid { grid-template-columns: 260px 1fr; align-items: start; }
    }

    /* Sidebar (desktop) */
    .faq-sidebar { display: none; position: sticky; top: 90px; }
    @media (min-width: 1024px) { .faq-sidebar { display: block; } }
    .faq-sidebar-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 18px 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
    .faq-sidebar-title { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8; padding: 0 12px 10px; }
    .faq-sidebar-link { display: flex; align-items: center; justify-content: space-between; width: 100%; padding: 10px 12px; font-size: 0.875rem; font-weight: 500; color: #64748b; border-radius: 8px; border: none; background: none; cursor: pointer; transition: all 150ms; text-align: left; }
    .faq-sidebar-link:hover { background: #f1f5f9; color: #0f172a; }
    .faq-sidebar-link.active { background: #eff6ff; color: #1e40af; font-weight: 600; }
    .faq-sidebar-link span.faq-sidebar-count { font-size: 0.75rem; background: #e2e8f0; color: #475569; padding: 2px 8px; border-radius: 100px; font-weight: 600; }
    .faq-sidebar-link.active span.faq-sidebar-count { background: #bfdbfe; color: #1e3a8a; }

    /* Category Blocks & Accordion */
    .faq-category { margin-bottom: 32px; scroll-margin-top: 100px; }
    .faq-category-header { display: flex; align-items: center; gap: 10px; margin-bottom: 16px; padding-bottom: 8px; border-bottom: 2px solid #e2e8f0; }
    .faq-category-header h2 { font-size: 1.25rem; font-weight: 700; color: #0f172a; margin: 0; }
    .faq-category-header i { width: 22px; height: 22px; color: #1e40af; }

    .faq-list { display: flex; flex-direction: column; gap: 12px; }
    .faq-item { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; transition: border-color 150ms, box-shadow 150ms; }
    .faq-item:hover { border-color: #cbd5e1; }
    .faq-item.open { border-color: #93c5fd; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }

    .faq-question { display: flex; align-items: center; justify-content: space-between; width: 100%; padding: 18px 20px; font-size: 1rem; font-weight: 600; color: #0f172a; text-align: left; background: none; border: none; cursor: pointer; gap: 16px; }
    .faq-icon { width: 20px; height: 20px; color: #64748b; transition: transform 200ms ease; flex-shrink: 0; }
    .faq-item.open .faq-icon { transform: rotate(180deg); color: #1e40af; }

    .faq-answer { display: none; padding: 0 20px 20px; color: #475569; font-size: 0.9375rem; line-height: 1.7; }
    .faq-item.open .faq-answer { display: block; }
    .faq-answer p { margin-bottom: 10px; }
    .faq-answer p:last-child { margin-bottom: 0; }
    .faq-answer ul { margin: 8px 0 10px 20px; list-style: disc; }
    .faq-answer li { margin-bottom: 4px; }

    /* CTA Box */
    .faq-cta { background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%); color: #ffffff; border-radius: 20px; padding: clamp(32px, 5vw, 48px); text-align: center; margin-top: 48px; }
    .faq-cta h2 { font-size: clamp(1.5rem, 3vw, 2rem); margin-bottom: 10px; color: #ffffff; }
    .faq-cta p { font-size: 1rem; color: #bfdbfe; max-width: 520px; margin: 0 auto 24px; }
    .faq-cta-actions { display: flex; flex-wrap: wrap; justify-content: center; gap: 12px; }
    .faq-cta .btn-primary { background: #ffffff; color: #1e40af; }
    .faq-cta .btn-primary:hover { background: #f8fafc; color: #1e3a8a; }
    .faq-cta .btn-secondary { background: rgba(255,255,255,0.12); color: #ffffff; border: 1px solid rgba(255,255,255,0.25); }
    .faq-cta .btn-secondary:hover { background: rgba(255,255,255,0.2); }
</style>

<div class="breadcrumb-bar">
    <div class="container">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="<?= url('/') ?>">Home</a>
            <i data-lucide="chevron-right"></i>
            <span class="current">FAQ</span>
        </nav>
    </div>
</div>

<!-- FAQ Hero -->
<section class="faq-hero">
    <div class="container">
        <h1>Frequently Asked Questions</h1>
        <p>Find answers to common questions about ScholarPlanner. Have a specific question not covered here? Feel free to contact our support team.</p>

        <!-- Search Bar -->
        <div class="faq-search-wrap">
            <i data-lucide="search" class="faq-search-icon"></i>
            <input type="text" id="faqSearchInput" class="faq-search-input" placeholder="Search questions (e.g., WhatsApp, matching, subscription)...">
        </div>

        <!-- Mobile category pills -->
        <div class="faq-cat-nav" id="mobileCatNav">
            <button class="faq-cat-btn active" data-cat="all"><i data-lucide="layers"></i> All</button>
            <button class="faq-cat-btn" data-cat="general"><i data-lucide="info"></i> General</button>
            <button class="faq-cat-btn" data-cat="matching"><i data-lucide="target"></i> Matching</button>
            <button class="faq-cat-btn" data-cat="alerts"><i data-lucide="bell"></i> Alerts</button>
            <button class="faq-cat-btn" data-cat="pricing"><i data-lucide="credit-card"></i> Pricing</button>
            <button class="faq-cat-btn" data-cat="privacy"><i data-lucide="shield"></i> Privacy</button>
            <button class="faq-cat-btn" data-cat="scholarships"><i data-lucide="book-open"></i> Scholarships</button>
        </div>
    </div>
</section>

<!-- FAQ Content Area -->
<section class="faq-layout">
    <div class="container">
        <div class="faq-grid">

            <!-- Sidebar (desktop) -->
            <aside class="faq-sidebar" id="faqSidebar">
                <div class="faq-sidebar-card">
                    <div class="faq-sidebar-title">Categories</div>
                    <button class="faq-sidebar-link active" data-cat="all">
                        <span><i data-lucide="layers" style="width:16px;height:16px;vertical-align:middle;margin-right:6px"></i> All Questions</span>
                        <span class="faq-sidebar-count">14</span>
                    </button>
                    <button class="faq-sidebar-link" data-cat="general">
                        <span><i data-lucide="info" style="width:16px;height:16px;vertical-align:middle;margin-right:6px"></i> General</span>
                        <span class="faq-sidebar-count">2</span>
                    </button>
                    <button class="faq-sidebar-link" data-cat="matching">
                        <span><i data-lucide="target" style="width:16px;height:16px;vertical-align:middle;margin-right:6px"></i> How Matching Works</span>
                        <span class="faq-sidebar-count">2</span>
                    </button>
                    <button class="faq-sidebar-link" data-cat="alerts">
                        <span><i data-lucide="bell" style="width:16px;height:16px;vertical-align:middle;margin-right:6px"></i> WhatsApp Alerts</span>
                        <span class="faq-sidebar-count">3</span>
                    </button>
                    <button class="faq-sidebar-link" data-cat="pricing">
                        <span><i data-lucide="credit-card" style="width:16px;height:16px;vertical-align:middle;margin-right:6px"></i> Subscriptions</span>
                        <span class="faq-sidebar-count">3</span>
                    </button>
                    <button class="faq-sidebar-link" data-cat="privacy">
                        <span><i data-lucide="shield" style="width:16px;height:16px;vertical-align:middle;margin-right:6px"></i> Privacy & Data</span>
                        <span class="faq-sidebar-count">2</span>
                    </button>
                    <button class="faq-sidebar-link" data-cat="scholarships">
                        <span><i data-lucide="book-open" style="width:16px;height:16px;vertical-align:middle;margin-right:6px"></i> Scholarship Info</span>
                        <span class="faq-sidebar-count">2</span>
                    </button>
                </div>
            </aside>

            <!-- FAQ Questions List -->
            <div id="faqContent">

                <!-- GENERAL -->
                <div class="faq-category" data-category="general" id="cat-general">
                    <div class="faq-category-header">
                        <i data-lucide="info"></i>
                        <h2>General</h2>
                    </div>
                    <div class="faq-list">
                        <div class="faq-item">
                            <button class="faq-question">
                                What is ScholarPlanner?
                                <i data-lucide="chevron-down" class="faq-icon"></i>
                            </button>
                            <div class="faq-answer">
                                <p>ScholarPlanner is a premier scholarship matching and notification platform. You create an academic profile, and our matching engine scans hundreds of verified international scholarship opportunities. When a scholarship aligns with your degree, GPA, and preferences, you receive automated alerts via WhatsApp and email.</p>
                            </div>
                        </div>
                        <div class="faq-item">
                            <button class="faq-question">
                                Who is ScholarPlanner for?
                                <i data-lucide="chevron-down" class="faq-icon"></i>
                            </button>
                            <div class="faq-answer">
                                <p>ScholarPlanner is designed for students seeking undergraduate, master's, PhD, and research scholarships across Europe, North America, Asia, and worldwide.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- MATCHING -->
                <div class="faq-category" data-category="matching" id="cat-matching">
                    <div class="faq-category-header">
                        <i data-lucide="target"></i>
                        <h2>How Matching Works</h2>
                    </div>
                    <div class="faq-list">
                        <div class="faq-item">
                            <button class="faq-question">
                                How does scholarship matching work?
                                <i data-lucide="chevron-down" class="faq-icon"></i>
                            </button>
                            <div class="faq-answer">
                                <p>When you fill in your academic profile, our matching engine compares your GPA, degree level, field of study, nationality, and funding preferences against scholarship eligibility criteria, generating an eligibility score.</p>
                            </div>
                        </div>
                        <div class="faq-item">
                            <button class="faq-question">
                                Does a high match score guarantee I will win the scholarship?
                                <i data-lucide="chevron-down" class="faq-icon"></i>
                            </button>
                            <div class="faq-answer">
                                <p>No. A high match score indicates that your profile satisfies the formal eligibility criteria of the scholarship. The final admission and award decisions rest solely with the official scholarship provider.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ALERTS -->
                <div class="faq-category" data-category="alerts" id="cat-alerts">
                    <div class="faq-category-header">
                        <i data-lucide="bell"></i>
                        <h2>WhatsApp & Email Alerts</h2>
                    </div>
                    <div class="faq-list">
                        <div class="faq-item">
                            <button class="faq-question">
                                How do WhatsApp scholarship notifications work?
                                <i data-lucide="chevron-down" class="faq-icon"></i>
                            </button>
                            <div class="faq-answer">
                                <p>Premium subscribers receive instant WhatsApp notifications whenever a newly published scholarship matches their qualifications, along with upcoming deadline reminders and direct portal links.</p>
                            </div>
                        </div>
                        <div class="faq-item">
                            <button class="faq-question">
                                Can I turn off WhatsApp alerts if needed?
                                <i data-lucide="chevron-down" class="faq-icon"></i>
                            </button>
                            <div class="faq-answer">
                                <p>Yes. You can enable or disable WhatsApp alerts at any time with a single click inside your account settings.</p>
                            </div>
                        </div>
                        <div class="faq-item">
                            <button class="faq-question">
                                Will I also receive email reminders?
                                <i data-lucide="chevron-down" class="faq-icon"></i>
                            </button>
                            <div class="faq-answer">
                                <p>Yes, email summaries and notifications are sent alongside WhatsApp alerts to ensure you never miss an application deadline.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PRICING -->
                <div class="faq-category" data-category="pricing" id="cat-pricing">
                    <div class="faq-category-header">
                        <i data-lucide="credit-card"></i>
                        <h2>Subscriptions & Payments</h2>
                    </div>
                    <div class="faq-list">
                        <div class="faq-item">
                            <button class="faq-question">
                                What is included in the Free vs. Premium plan?
                                <i data-lucide="chevron-down" class="faq-icon"></i>
                            </button>
                            <div class="faq-answer">
                                <p>The Free plan allows you to search the public scholarship database and build a profile. The Premium plan unlocks real-time WhatsApp alerts, automated matching, deadline countdown tracking, and document checklists.</p>
                            </div>
                        </div>
                        <div class="faq-item">
                            <button class="faq-question">
                                What payment methods do you accept?
                                <i data-lucide="chevron-down" class="faq-icon"></i>
                            </button>
                            <div class="faq-answer">
                                <p>We accept local and international payment methods including JazzCash, CashMaal, debit/credit cards, and direct bank transfers.</p>
                            </div>
                        </div>
                        <div class="faq-item">
                            <button class="faq-question">
                                Can I cancel my subscription anytime?
                                <i data-lucide="chevron-down" class="faq-icon"></i>
                            </button>
                            <div class="faq-answer">
                                <p>Yes, you can cancel your subscription at any time with zero penalties from your dashboard settings.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PRIVACY -->
                <div class="faq-category" data-category="privacy" id="cat-privacy">
                    <div class="faq-category-header">
                        <i data-lucide="shield"></i>
                        <h2>Privacy & Data</h2>
                    </div>
                    <div class="faq-list">
                        <div class="faq-item">
                            <button class="faq-question">
                                Is my academic and contact information secure?
                                <i data-lucide="chevron-down" class="faq-icon"></i>
                            </button>
                            <div class="faq-answer">
                                <p>Yes. We use industry-standard encryption, strict access controls, and we never sell your personal contact details to third-party advertisers.</p>
                            </div>
                        </div>
                        <div class="faq-item">
                            <button class="faq-question">
                                Can I update or delete my profile?
                                <i data-lucide="chevron-down" class="faq-icon"></i>
                            </button>
                            <div class="faq-answer">
                                <p>Yes, you can update your profile information or request account deletion at any time via your dashboard.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SCHOLARSHIPS -->
                <div class="faq-category" data-category="scholarships" id="cat-scholarships">
                    <div class="faq-category-header">
                        <i data-lucide="book-open"></i>
                        <h2>Scholarship Information</h2>
                    </div>
                    <div class="faq-list">
                        <div class="faq-item">
                            <button class="faq-question">
                                How often are new scholarships added?
                                <i data-lucide="chevron-down" class="faq-icon"></i>
                            </button>
                            <div class="faq-answer">
                                <p>Our research team continuously verifies and adds international opportunities on a weekly and daily basis.</p>
                            </div>
                        </div>
                        <div class="faq-item">
                            <button class="faq-question">
                                What happens when a scholarship deadline expires?
                                <i data-lucide="chevron-down" class="faq-icon"></i>
                            </button>
                            <div class="faq-answer">
                                <p>Expired scholarships are automatically closed and removed from active match notifications so students only focus on open opportunities.</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div><!-- /#faqContent -->
        </div><!-- /.faq-grid -->

        <!-- CTA Box -->
        <div class="faq-cta">
            <h2>Still Have a Question?</h2>
            <p>Our dedicated support team is ready to help you navigate your scholarship search.</p>
            <div class="faq-cta-actions">
                <a href="<?= url('/contact') ?>" class="btn btn-primary">
                    <i data-lucide="mail" style="width:18px;height:18px"></i> Contact Us
                </a>
                <a href="<?= url('/register') ?>" class="btn btn-secondary">
                    <i data-lucide="user-plus" style="width:18px;height:18px"></i> Create Free Account
                </a>
            </div>
        </div>

    </div>
</section>

<script>
    // Accordion interaction
    document.querySelectorAll('.faq-question').forEach(function(button) {
        button.addEventListener('click', function() {
            var item = this.closest('.faq-item');
            item.classList.toggle('open');
        });
    });

    // Category filtering (desktop & mobile)
    var allCatButtons = document.querySelectorAll('.faq-sidebar-link, .faq-cat-btn');
    allCatButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var cat = this.dataset.cat;
            allCatButtons.forEach(function(b) {
                b.classList.toggle('active', b.dataset.cat === cat);
            });

            document.querySelectorAll('.faq-category').forEach(function(sec) {
                if (cat === 'all' || sec.dataset.category === cat) {
                    sec.style.display = 'block';
                } else {
                    sec.style.display = 'none';
                }
            });
        });
    });

    // Live search filter
    var searchInput = document.getElementById('faqSearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            var query = this.value.toLowerCase().trim();
            document.querySelectorAll('.faq-item').forEach(function(item) {
                var text = item.textContent.toLowerCase();
                if (query === '' || text.indexOf(query) !== -1) {
                    item.style.display = 'block';
                    if (query !== '') item.classList.add('open');
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }
</script>

<?php include ROOT_PATH . '/app/Views/layouts/public_footer.php'; ?>
