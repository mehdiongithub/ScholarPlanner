<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ — ScholarMatch</title>
    <meta name="description" content="Frequently asked questions about ScholarMatch — how matching works, WhatsApp alerts, pricing, subscriptions, privacy, and more.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@0.460.0"></script>
    <style>
        :root {
            --primary: #1e40af;
            --primary-light: #3b82f6;
            --primary-dark: #1e3a8a;
            --primary-50: #eff6ff;
            --primary-100: #dbeafe;
            --primary-200: #bfdbfe;
            --accent: #059669;
            --accent-light: #10b981;
            --accent-50: #ecfdf5;
            --bg-white: #ffffff;
            --bg-50: #f8fafc;
            --bg-100: #f1f5f9;
            --bg-200: #e2e8f0;
            --text-900: #0f172a;
            --text-700: #334155;
            --text-600: #475569;
            --text-500: #64748b;
            --text-400: #94a3b8;
            --text-300: #cbd5e1;
            --border: #e2e8f0;
            --border-light: #f1f5f9;
            --shadow-xs: 0 1px 2px rgba(0,0,0,0.04);
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.07), 0 2px 4px -2px rgba(0,0,0,0.05);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.08), 0 4px 6px -4px rgba(0,0,0,0.04);
            --shadow-xl: 0 20px 25px -5px rgba(0,0,0,0.08), 0 8px 10px -6px rgba(0,0,0,0.04);
            --radius-sm: 6px;
            --radius-md: 8px;
            --radius-lg: 12px;
            --radius-xl: 16px;
            --radius-2xl: 20px;
            --font: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            --header-height: 64px;
        }
        *,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
        html{scroll-behavior:smooth}
        body{font-family:var(--font);font-size:15px;line-height:1.6;color:var(--text-600);background:var(--bg-100);-webkit-font-smoothing:antialiased;overflow-x:hidden}
        a{color:inherit;text-decoration:none}
        button{font-family:inherit;cursor:pointer;border:none;background:none}
        ul{list-style:none}
        img{max-width:100%;display:block}
        :focus-visible{outline:2px solid var(--primary-light);outline-offset:2px;border-radius:var(--radius-sm)}
        h1,h2,h3,h4{color:var(--text-900);line-height:1.25;font-weight:600}
        h1{font-size:clamp(1.75rem,4vw,2.5rem);letter-spacing:-0.025em;font-weight:700}
        h2{font-size:clamp(1.125rem,2.5vw,1.5rem);letter-spacing:-0.02em}
        h3{font-size:0.9375rem;letter-spacing:-0.01em}
        .container{width:100%;max-width:1200px;margin:0 auto;padding:0 clamp(16px,4vw,24px)}

        .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;font-size:0.9375rem;font-weight:500;padding:12px 24px;border-radius:var(--radius-md);transition:all 200ms ease;white-space:nowrap;min-height:44px;line-height:1.2}
        .btn-primary{background:var(--primary);color:#fff;box-shadow:0 1px 2px rgba(30,64,175,0.2)}
        .btn-primary:hover{background:var(--primary-dark);box-shadow:0 4px 12px rgba(30,64,175,0.3);transform:translateY(-1px)}
        .btn-secondary{background:var(--bg-white);color:var(--text-700);border:1px solid var(--border)}
        .btn-secondary:hover{background:var(--bg-50);border-color:var(--text-300)}
        .btn-sm{font-size:0.8125rem;padding:8px 16px;min-height:36px}
        .btn-ghost{background:transparent;color:var(--primary-light);font-weight:500}
        .btn-ghost:hover{background:var(--primary-50)}

        /* ===== HEADER ===== */
        .header{position:sticky;top:0;z-index:100;height:var(--header-height);background:rgba(255,255,255,0.92);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);border-bottom:1px solid var(--border)}
        .header-inner{display:flex;align-items:center;justify-content:space-between;height:100%}
        .logo{display:flex;align-items:center;gap:10px;flex-shrink:0}
        .logo-icon{width:32px;height:32px;background:var(--primary);border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;color:#fff}
        .logo-icon i{width:16px;height:16px}
        .logo-text{font-size:1rem;font-weight:600;color:var(--text-900);letter-spacing:-0.02em}
        .nav-desktop{display:none;align-items:center;gap:4px}
        .nav-desktop a{font-size:0.875rem;font-weight:450;color:var(--text-500);padding:8px 14px;border-radius:var(--radius-sm);transition:all 150ms}
        .nav-desktop a:hover{color:var(--text-900);background:var(--bg-50)}
        .nav-desktop a.active{color:var(--primary);background:var(--primary-50);font-weight:500}
        .header-actions{display:none;align-items:center;gap:8px}
        .header-actions .btn-login{font-size:0.875rem;font-weight:500;color:var(--text-600);padding:8px 16px;border-radius:var(--radius-sm);min-height:40px;transition:all 150ms}
        .header-actions .btn-login:hover{color:var(--text-900);background:var(--bg-50)}
        .mobile-menu-btn{display:flex;align-items:center;justify-content:center;width:44px;height:44px;border-radius:var(--radius-sm);color:var(--text-700);transition:background 150ms}
        .mobile-menu-btn:hover{background:var(--bg-50)}
        .mobile-menu-btn i{width:22px;height:22px}
        @media(min-width:1024px){.nav-desktop{display:flex}.header-actions{display:flex}.mobile-menu-btn{display:none}}

        .mobile-menu-overlay{position:fixed;inset:0;z-index:999;background:rgba(0,0,0,0.3);opacity:0;visibility:hidden;transition:all 300ms ease}
        .mobile-menu-overlay.active{opacity:1;visibility:visible}
        .mobile-menu{position:fixed;top:0;right:0;bottom:0;z-index:1001;width:min(320px,85vw);background:var(--bg-white);box-shadow:var(--shadow-xl);transform:translateX(100%);transition:transform 300ms ease;display:flex;flex-direction:column;overflow-y:auto}
        .mobile-menu.active{transform:translateX(0)}
        .mobile-menu-header{display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--border);flex-shrink:0}
        .mobile-menu-close{display:flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:var(--radius-sm);color:var(--text-500);transition:background 150ms}
        .mobile-menu-close:hover{background:var(--bg-50)}
        .mobile-menu-nav{padding:12px 16px;flex:1}
        .mobile-menu-nav a{display:flex;align-items:center;padding:12px 12px;font-size:0.9375rem;font-weight:450;color:var(--text-700);border-radius:var(--radius-sm);transition:all 150ms}
        .mobile-menu-nav a:hover{background:var(--bg-50);color:var(--text-900)}
        .mobile-menu-nav a.active{color:var(--primary);background:var(--primary-50);font-weight:500}
        .mobile-menu-footer{padding:16px 20px;border-top:1px solid var(--border);display:flex;flex-direction:column;gap:8px;flex-shrink:0}
        .mobile-menu-footer .btn{width:100%}
        body.menu-open{overflow:hidden}

        /* ===== BREADCRUMB ===== */
        .breadcrumb-bar{background:var(--bg-50);border-bottom:1px solid var(--border-light);padding:12px 0}
        .breadcrumb{display:flex;align-items:center;gap:6px;font-size:0.8125rem;color:var(--text-400);flex-wrap:wrap}
        .breadcrumb a{color:var(--text-500);transition:color 150ms}
        .breadcrumb a:hover{color:var(--primary)}
        .breadcrumb i{width:14px;height:14px;flex-shrink:0}
        .breadcrumb .current{color:var(--text-700);font-weight:500}

        /* ===== HERO ===== */
        .faq-hero{background:linear-gradient(180deg,var(--primary-50) 0%,var(--bg-100) 100%);padding:calc(var(--header-height) + clamp(40px,7vw,64px)) 0 clamp(40px,7vw,64px);text-align:center}
        .faq-hero h1{margin-bottom:10px}
        .faq-hero p{font-size:clamp(0.9375rem,1.5vw,1.0625rem);color:var(--text-500);max-width:520px;margin:0 auto 24px;line-height:1.7}

        /* Quick category nav */
        .faq-cat-nav{display:flex;flex-wrap:wrap;justify-content:center;gap:8px;margin-bottom:4px}
        .faq-cat-btn{font-size:0.8125rem;font-weight:500;color:var(--text-500);background:var(--bg-white);border:1px solid var(--border);padding:8px 16px;border-radius:100px;min-height:40px;transition:all 150ms;display:flex;align-items:center;gap:6px}
        .faq-cat-btn:hover{border-color:var(--primary-200);color:var(--primary)}
        .faq-cat-btn.active{background:var(--primary);color:#fff;border-color:var(--primary)}
        .faq-cat-btn i{width:15px;height:15px}

        /* ===== FAQ LAYOUT ===== */
        .faq-layout{padding:clamp(24px,4vw,48px) 0 clamp(48px,8vw,80px)}
        .faq-grid{display:grid;grid-template-columns:1fr;gap:24px}
        @media(min-width:1024px){.faq-grid{grid-template-columns:220px 1fr;gap:clamp(24px,3vw,40px);align-items:start}}

        /* Sidebar category nav (desktop) */
        .faq-sidebar{display:none;position:sticky;top:calc(var(--header-height) + 24px)}
        @media(min-width:1024px){.faq-sidebar{display:block}}

        .faq-sidebar-card{background:var(--bg-white);border:1px solid var(--border);border-radius:var(--radius-xl);padding:16px;box-shadow:var(--shadow-xs)}

        .faq-sidebar-title{font-size:0.6875rem;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;color:var(--text-400);padding:0 12px 10px}

        .faq-sidebar-link{display:flex;align-items:center;gap:8px;width:100%;padding:9px 12px;border-radius:var(--radius-sm);font-size:0.8125rem;font-weight:450;color:var(--text-600);transition:all 150ms;text-align:left}
        .faq-sidebar-link:hover{background:var(--bg-50);color:var(--text-900)}
        .faq-sidebar-link.active{background:var(--primary-50);color:var(--primary);font-weight:500}
        .faq-sidebar-link i{width:16px;height:16px;flex-shrink:0;opacity:0.7}
        .faq-sidebar-link.active i{opacity:1}

        .faq-sidebar-count{margin-left:auto;font-size:0.6875rem;font-weight:500;color:var(--text-400);background:var(--bg-100);padding:1px 7px;border-radius:100px}
        .faq-sidebar-link.active .faq-sidebar-count{background:var(--primary-100);color:var(--primary)}

        /* ===== FAQ CATEGORY ===== */
        .faq-category{margin-bottom:32px;scroll-margin-top:calc(var(--header-height) + 24px)}
        .faq-category:last-child{margin-bottom:0}

        .faq-category-header{display:flex;align-items:center;gap:10px;margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid var(--border-light)}
        .faq-category-header i{width:20px;height:20px;color:var(--primary-light);flex-shrink:0}
        .faq-category-header h2{font-size:1.0625rem}

        /* ===== FAQ ITEMS ===== */
        .faq-list{display:flex;flex-direction:column;gap:6px}

        .faq-item{background:var(--bg-white);border:1px solid var(--border-light);border-radius:var(--radius-lg);overflow:hidden;transition:border-color 200ms}
        .faq-item.active{border-color:var(--primary-200)}

        .faq-question{width:100%;display:flex;align-items:center;justify-content:space-between;gap:12px;padding:16px 20px;font-size:0.9375rem;font-weight:500;color:var(--text-900);text-align:left;min-height:52px;transition:color 150ms;line-height:1.4}
        .faq-question:hover{color:var(--primary)}

        .faq-icon{width:20px;height:20px;color:var(--text-400);flex-shrink:0;transition:transform 300ms ease}
        .faq-item.active .faq-icon{transform:rotate(180deg)}

        .faq-answer{max-height:0;overflow:hidden;transition:max-height 300ms ease}
        .faq-item.active .faq-answer{max-height:600px}

        .faq-answer-inner{padding:0 20px 18px;font-size:0.875rem;color:var(--text-500);line-height:1.75}
        .faq-answer-inner p{margin-bottom:8px}
        .faq-answer-inner p:last-child{margin-bottom:0}
        .faq-answer-inner strong{color:var(--text-700);font-weight:600}
        .faq-answer-inner ul{margin:8px 0;padding-left:20px;list-style:disc}
        .faq-answer-inner li{margin-bottom:4px}
        .faq-answer-inner a{color:var(--primary-light);font-weight:500;text-decoration:underline;text-underline-offset:2px}
        .faq-answer-inner a:hover{color:var(--primary)}

        /* ===== STILL HAVE QUESTIONS ===== */
        .faq-cta{background:var(--bg-white);border:1px solid var(--border);border-radius:var(--radius-2xl);padding:clamp(28px,4vw,48px);text-align:center;margin-top:16px;position:relative;overflow:hidden}
        .faq-cta::before{content:'';position:absolute;top:-40px;right:-30px;width:160px;height:160px;border-radius:50%;background:var(--primary-50);opacity:0.5;pointer-events:none}
        .faq-cta h2{margin-bottom:8px;position:relative}
        .faq-cta p{font-size:0.9375rem;color:var(--text-500);max-width:440px;margin:0 auto 24px;position:relative;line-height:1.7}
        .faq-cta-actions{display:flex;flex-wrap:wrap;justify-content:center;gap:12px;position:relative}

        /* ===== FOOTER ===== */
        .footer{background:var(--text-900);color:var(--text-400);padding:clamp(40px,6vw,64px) 0 24px}
        .footer-grid{display:grid;grid-template-columns:1fr;gap:32px;margin-bottom:40px}
        @media(min-width:640px){.footer-grid{grid-template-columns:repeat(2,1fr)}}
        @media(min-width:960px){.footer-grid{grid-template-columns:2fr 1fr 1fr 1fr;gap:24px}}
        .footer-brand p{font-size:0.8125rem;line-height:1.6;margin-top:12px;max-width:280px}
        .footer-logo{display:flex;align-items:center;gap:8px;margin-bottom:4px}
        .footer-logo-icon{width:28px;height:28px;background:var(--primary);border-radius:var(--radius-sm);display:flex;align-items:center;justify-content:center;color:#fff}
        .footer-logo-icon i{width:14px;height:14px}
        .footer-logo-text{font-size:0.9375rem;font-weight:600;color:#fff}
        .footer-col h4{font-size:0.75rem;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;color:var(--text-300);margin-bottom:14px}
        .footer-col a{display:block;font-size:0.8125rem;color:var(--text-400);padding:4px 0;transition:color 150ms}
        .footer-col a:hover{color:#fff}
        .footer-bottom{padding-top:24px;border-top:1px solid rgba(255,255,255,0.08);display:flex;flex-direction:column;gap:8px;align-items:center;text-align:center;font-size:0.75rem;color:var(--text-500)}
        @media(min-width:640px){.footer-bottom{flex-direction:row;justify-content:space-between;text-align:left}}

        /* ===== REVEAL ===== */
        .reveal{opacity:0;transform:translateY(20px);transition:opacity 600ms ease,transform 600ms ease}
        .reveal.revealed{opacity:1;transform:translateY(0)}
        .reveal-delay-1{transition-delay:100ms}
        @media(prefers-reduced-motion:reduce){.reveal{opacity:1;transform:none;transition:none}html{scroll-behavior:auto}*,*::before,*::after{transition-duration:0.01ms!important;animation-duration:0.01ms!important}}

        /* ===== HIDDEN HELPER ===== */
        .faq-category-hidden{display:none}

        @media(max-width:320px){
            .faq-cat-nav{gap:6px}
            .faq-cat-btn{font-size:0.75rem;padding:6px 12px}
            .faq-question{padding:14px 16px;font-size:0.875rem}
            .faq-answer-inner{padding:0 16px 14px;font-size:0.8125rem}
            .faq-cta-actions .btn{width:100%}
        }
    </style>
</head>
<body>

    <!-- HEADER -->
    <header class="header" role="banner">
        <div class="header-inner container">
            <a href="/" class="logo" aria-label="ScholarMatch Home">
                <div class="logo-icon"><i data-lucide="graduation-cap"></i></div>
                <span class="logo-text">ScholarMatch</span>
            </a>
            <nav class="nav-desktop" aria-label="Main navigation">
                <a href="/">Home</a>
                <a href="/scholarships">Scholarships</a>
                <a href="/how-it-works">How It Works</a>
                <a href="/features">Features</a>
                <a href="/pricing">Pricing</a>
                <a href="/faq" class="active">FAQ</a>
                <a href="/about">About</a>
            </nav>
            <div class="header-actions">
                <a href="/login" class="btn-login">Log In</a>
                <a href="/register" class="btn btn-primary btn-sm">Get Started</a>
            </div>
            <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Open menu" aria-expanded="false"><i data-lucide="menu"></i></button>
        </div>
    </header>

    <div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>
    <nav class="mobile-menu" id="mobileMenu" aria-label="Mobile navigation" aria-hidden="true">
        <div class="mobile-menu-header">
            <span class="logo-text">ScholarMatch</span>
            <button class="mobile-menu-close" id="mobileMenuClose" aria-label="Close menu"><i data-lucide="x"></i></button>
        </div>
        <div class="mobile-menu-nav">
            <a href="/">Home</a>
            <a href="/scholarships">Scholarships</a>
            <a href="/how-it-works">How It Works</a>
            <a href="/features">Features</a>
            <a href="/pricing">Pricing</a>
            <a href="/faq" class="active">FAQ</a>
            <a href="/about">About</a>
            <a href="/contact">Contact</a>
        </div>
        <div class="mobile-menu-footer">
            <a href="/login" class="btn btn-secondary">Log In</a>
            <a href="/register" class="btn btn-primary">Get Started</a>
        </div>
    </nav>

    <main>
        <!-- Breadcrumb -->
        <div class="breadcrumb-bar">
            <div class="container">
                <nav class="breadcrumb" aria-label="Breadcrumb">
                    <a href="/">Home</a>
                    <i data-lucide="chevron-right"></i>
                    <span class="current">FAQ</span>
                </nav>
            </div>
        </div>

        <!-- Hero -->
        <section class="faq-hero">
            <div class="container reveal">
                <h1>Frequently Asked Questions</h1>
                <p>Find answers to common questions about ScholarMatch. Can't find what you're looking for? Contact us directly.</p>

                <!-- Mobile category pills -->
                <div class="faq-cat-nav" id="mobileCatNav">
                    <button class="faq-cat-btn active" data-cat="all">
                        <i data-lucide="layers"></i> All
                    </button>
                    <button class="faq-cat-btn" data-cat="general">
                        <i data-lucide="info"></i> General
                    </button>
                    <button class="faq-cat-btn" data-cat="matching">
                        <i data-lucide="target"></i> Matching
                    </button>
                    <button class="faq-cat-btn" data-cat="alerts">
                        <i data-lucide="bell"></i> Alerts
                    </button>
                    <button class="faq-cat-btn" data-cat="pricing">
                        <i data-lucide="credit-card"></i> Pricing
                    </button>
                    <button class="faq-cat-btn" data-cat="privacy">
                        <i data-lucide="shield"></i> Privacy
                    </button>
                    <button class="faq-cat-btn" data-cat="scholarships">
                        <i data-lucide="book-open"></i> Scholarships
                    </button>
                </div>
            </div>
        </section>

        <!-- FAQ Content -->
        <section class="faq-layout">
            <div class="container">
                <div class="faq-grid">

                    <!-- Sidebar (desktop) -->
                    <aside class="faq-sidebar" id="faqSidebar">
                        <div class="faq-sidebar-card">
                            <div class="faq-sidebar-title">Categories</div>
                            <button class="faq-sidebar-link active" data-cat="all">
                                <i data-lucide="layers"></i> All Questions
                                <span class="faq-sidebar-count" id="countAll">15</span>
                            </button>
                            <button class="faq-sidebar-link" data-cat="general">
                                <i data-lucide="info"></i> General
                                <span class="faq-sidebar-count">2</span>
                            </button>
                            <button class="faq-sidebar-link" data-cat="matching">
                                <i data-lucide="target"></i> How Matching Works
                                <span class="faq-sidebar-count">2</span>
                            </button>
                            <button class="faq-sidebar-link" data-cat="alerts">
                                <i data-lucide="bell"></i> WhatsApp & Email
                                <span class="faq-sidebar-count">3</span>
                            </button>
                            <button class="faq-sidebar-link" data-cat="pricing">
                                <i data-lucide="credit-card"></i> Pricing & Payment
                                <span class="faq-sidebar-count">3</span>
                            </button>
                            <button class="faq-sidebar-link" data-cat="privacy">
                                <i data-lucide="shield"></i> Privacy & Data
                                <span class="faq-sidebar-count">2</span>
                            </button>
                            <button class="faq-sidebar-link" data-cat="scholarships">
                                <i data-lucide="book-open"></i> Scholarship Info
                                <span class="faq-sidebar-count">3</span>
                            </button>
                        </div>
                    </aside>

                    <!-- FAQ Content Area -->
                    <div id="faqContent">

                        <!-- GENERAL -->
                        <div class="faq-category reveal" data-category="general" id="cat-general">
                            <div class="faq-category-header">
                                <i data-lucide="info"></i>
                                <h2>General</h2>
                            </div>
                            <div class="faq-list" role="list">
                                <div class="faq-item" role="listitem">
                                    <button class="faq-question" aria-expanded="false">
                                        What is ScholarMatch?
                                        <i data-lucide="chevron-down" class="faq-icon"></i>
                                    </button>
                                    <div class="faq-answer" role="region">
                                        <div class="faq-answer-inner">
                                            <p>ScholarMatch is a scholarship discovery platform. You create a profile with your education details, and the system finds scholarship opportunities that match your qualifications. When new matching scholarships are found, you receive alerts through WhatsApp and email.</p>
                                            <p>We are not a scholarship provider. We help you discover opportunities — you apply directly through the official scholarship source.</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="faq-item" role="listitem">
                                    <button class="faq-question" aria-expanded="false">
                                        Who is ScholarMatch for?
                                        <i data-lucide="chevron-down" class="faq-icon"></i>
                                    </button>
                                    <div class="faq-answer" role="region">
                                        <div class="faq-answer-inner">
                                            <p>ScholarMatch is built for students — primarily in Pakistan, India, and Bangladesh — who are looking for scholarship opportunities for undergraduate, master's, PhD, or research programs. It's useful for anyone who wants a more organized way to find scholarships instead of manually searching multiple websites and groups.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- MATCHING -->
                        <div class="faq-category reveal" data-category="matching" id="cat-matching">
                            <div class="faq-category-header">
                                <i data-lucide="target"></i>
                                <h2>How Matching Works</h2>
                            </div>
                            <div class="faq-list" role="list">
                                <div class="faq-item" role="listitem">
                                    <button class="faq-question" aria-expanded="false">
                                        How does scholarship matching work?
                                        <i data-lucide="chevron-down" class="faq-icon"></i>
                                    </button>
                                    <div class="faq-answer" role="region">
                                        <div class="faq-answer-inner">
                                            <p>When you create your profile, you provide information like your field of study, degree level, CGPA, country, and study preferences. Our system compares these details against the eligibility criteria of each scholarship in our database.</p>
                                            <p>The result is a match percentage — a score indicating how well your profile aligns with the scholarship's stated requirements. A higher percentage means more criteria are met.</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="faq-item" role="listitem">
                                    <button class="faq-question" aria-expanded="false">
                                        Does a match guarantee that I will receive the scholarship?
                                        <i data-lucide="chevron-down" class="faq-icon"></i>
                                    </button>
                                    <div class="faq-answer" role="region">
                                        <div class="faq-answer-inner">
                                            <p><strong>No.</strong> The platform identifies opportunities that appear relevant based on available eligibility criteria. The scholarship provider makes the final eligibility and selection decision.</p>
                                            <p>A 90% match means your profile meets most of the stated criteria we have on record — but the provider may consider additional factors not listed, or criteria may change. Always verify eligibility directly with the scholarship provider before applying.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ALERTS -->
                        <div class="faq-category reveal" data-category="alerts" id="cat-alerts">
                            <div class="faq-category-header">
                                <i data-lucide="bell"></i>
                                <h2>WhatsApp & Email Alerts</h2>
                            </div>
                            <div class="faq-list" role="list">
                                <div class="faq-item" role="listitem">
                                    <button class="faq-question" aria-expanded="false">
                                        How do I receive WhatsApp alerts?
                                        <i data-lucide="chevron-down" class="faq-icon"></i>
                                    </button>
                                    <div class="faq-answer" role="region">
                                        <div class="faq-answer-inner">
                                            <p>WhatsApp alerts are available on the Premium plan. After subscribing, you connect your WhatsApp number in your dashboard. When new scholarships match your profile, you receive a concise notification with key details — scholarship name, match score, funding type, deadline — and a link to view the full information on the website.</p>
                                            <p>The WhatsApp alert contains a summary only. Complete details including eligibility, required documents, and the official application link are always on the website.</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="faq-item" role="listitem">
                                    <button class="faq-question" aria-expanded="false">
                                        Will I receive email alerts?
                                        <i data-lucide="chevron-down" class="faq-icon"></i>
                                    </button>
                                    <div class="faq-answer" role="region">
                                        <div class="faq-answer-inner">
                                            <p>Yes, Premium subscribers receive email notifications with details about matched scholarships. Each email includes the scholarship title, match percentage, deadline, country, funding type, and a link to the full scholarship page.</p>
                                            <p>Email alerts are included in the Premium plan at no extra cost. You can manage your email alert preferences from your dashboard settings.</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="faq-item" role="listitem">
                                    <button class="faq-question" aria-expanded="false">
                                        Can I stop WhatsApp or email notifications?
                                        <i data-lucide="chevron-down" class="faq-icon"></i>
                                    </button>
                                    <div class="faq-answer" role="region">
                                        <div class="faq-answer-inner">
                                            <p>Yes, you have full control:</p>
                                            <ul>
                                                <li><strong>WhatsApp:</strong> Disable from your dashboard settings, or reply "STOP" to any WhatsApp message to unsubscribe from WhatsApp alerts.</li>
                                                <li><strong>Email:</strong> Disable from your dashboard settings, or use the unsubscribe link at the bottom of any email.</li>
                                            </ul>
                                            <p>Disabling one channel does not affect the other. You can turn off WhatsApp but keep email, or vice versa.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- PRICING -->
                        <div class="faq-category reveal" data-category="pricing" id="cat-pricing">
                            <div class="faq-category-header">
                                <i data-lucide="credit-card"></i>
                                <h2>Pricing & Payment</h2>
                            </div>
                            <div class="faq-list" role="list">
                                <div class="faq-item" role="listitem">
                                    <button class="faq-question" aria-expanded="false">
                                        What does the free plan include?
                                        <i data-lucide="chevron-down" class="faq-icon"></i>
                                    </button>
                                    <div class="faq-answer" role="region">
                                        <div class="faq-answer-inner">
                                            <p>The free plan includes:</p>
                                            <ul>
                                                <li>Basic profile creation</li>
                                                <li>Limited scholarship matches</li>
                                                <li>Access to the scholarship listing on the website</li>
                                                <li>Limited alert visibility</li>
                                            </ul>
                                            <p>WhatsApp alerts, email alerts, deadline reminders, and application tracking require a Premium subscription.</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="faq-item" role="listitem">
                                    <button class="faq-question" aria-expanded="false">
                                        Can I cancel my subscription?
                                        <i data-lucide="chevron-down" class="faq-icon"></i>
                                    </button>
                                    <div class="faq-answer" role="region">
                                        <div class="faq-answer-inner">
                                            <p>Yes, you can cancel your Premium subscription at any time from your account settings. After cancellation:</p>
                                            <ul>
                                                <li>You retain Premium features until the end of your current billing period.</li>
                                                <li>Your account then reverts to the Free plan.</li>
                                                <li>No further charges will be made.</li>
                                            </ul>
                                            <p>There is no cancellation fee. You can resubscribe at any time.</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="faq-item" role="listitem">
                                    <button class="faq-question" aria-expanded="false">
                                        What payment methods are available?
                                        <i data-lucide="chevron-down" class="faq-icon"></i>
                                    </button>
                                    <div class="faq-answer" role="region">
                                        <div class="faq-answer-inner">
                                            <p>Currently, we accept payments through:</p>
                                            <ul>
                                                <li><strong>Easypaisa</strong> — Pay directly from your Easypaisa mobile wallet</li>
                                                <li><strong>JazzCash</strong> — Pay directly from your JazzCash mobile wallet</li>
                                            </ul>
                                            <p>Additional payment methods may be added in the future. Pricing is displayed in PKR for Pakistani users. Other currencies will be available based on your region during registration.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- PRIVACY -->
                        <div class="faq-category reveal" data-category="privacy" id="cat-privacy">
                            <div class="faq-category-header">
                                <i data-lucide="shield"></i>
                                <h2>Privacy & Data</h2>
                            </div>
                            <div class="faq-list" role="list">
                                <div class="faq-item" role="listitem">
                                    <button class="faq-question" aria-expanded="false">
                                        Is my personal information safe?
                                        <i data-lucide="chevron-down" class="faq-icon"></i>
                                    </button>
                                    <div class="faq-answer" role="region">
                                        <div class="faq-answer-inner">
                                            <p>Your profile information is used only for scholarship matching and sending you relevant alerts. We do not sell, share, or publicly display your personal data.</p>
                                            <p>You can control what notifications you receive, unsubscribe from any channel at any time, and request deletion of your account and data. For complete details, please read our <a href="/privacy">Privacy Policy</a>.</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="faq-item" role="listitem">
                                    <button class="faq-question" aria-expanded="false">
                                        Can I change my profile later?
                                        <i data-lucide="chevron-down" class="faq-icon"></i>
                                    </button>
                                    <div class="faq-answer" role="region">
                                        <div class="faq-answer-inner">
                                            <p>Yes, you can update your profile information at any time from your dashboard. When you update your profile, your scholarship matches will be recalculated based on your new information, and you may see different results.</p>
                                            <p>We recommend keeping your profile up to date, especially when your education level, CGPA, or study preferences change.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SCHOLARSHIPS -->
                        <div class="faq-category reveal" data-category="scholarships" id="cat-scholarships">
                            <div class="faq-category-header">
                                <i data-lucide="book-open"></i>
                                <h2>Scholarship Information</h2>
                            </div>
                            <div class="faq-list" role="list">
                                <div class="faq-item" role="listitem">
                                    <button class="faq-question" aria-expanded="false">
                                        Where does the scholarship information come from?
                                        <i data-lucide="chevron-down" class="faq-icon"></i>
                                    </button>
                                    <div class="faq-answer" role="region">
                                        <div class="faq-answer-inner">
                                            <p>Scholarship information is collected from official sources including:</p>
                                            <ul>
                                                <li>Government scholarship portals</li>
                                                <li>University scholarship pages</li>
                                                <li>Recognized international scholarship programs</li>
                                                <li>Official organization websites</li>
                                            </ul>
                                            <p>Where applicable, the source and verification status of each scholarship is displayed on the scholarship detail page. If a scholarship's information has not been independently verified, this will be noted.</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="faq-item" role="listitem">
                                    <button class="faq-question" aria-expanded="false">
                                        What happens when a scholarship deadline passes?
                                        <i data-lucide="chevron-down" class="faq-icon"></i>
                                    </button>
                                    <div class="faq-answer" role="region">
                                        <div class="faq-answer-inner">
                                            <p>Scholarships with passed deadlines are marked as "Closed" and are no longer shown in your active matches. They are removed from the main listing and will not appear in your alerts.</p>
                                            <p>You may still be able to view closed scholarships in your history for reference, but the application link will no longer be active.</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="faq-item" role="listitem">
                                    <button class="faq-question" aria-expanded="false">
                                        How often are scholarships updated?
                                        <i data-lucide="chevron-down" class="faq-icon"></i>
                                    </button>
                                    <div class="faq-answer" role="region">
                                        <div class="faq-answer-inner">
                                            <p>Our team regularly researches and adds new scholarship opportunities. There is no fixed schedule — additions depend on when new opportunities are announced by providers.</p>
                                            <p>When new scholarships are added that match your profile, you will receive an alert (if you have an active Premium subscription with notifications enabled).</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div><!-- /faqContent -->
                </div><!-- /faq-grid -->

                <!-- Still have questions CTA -->
                <div class="faq-cta reveal">
                    <h2>Still Have a Question?</h2>
                    <p>If you couldn't find the answer you were looking for, send us a message and we'll get back to you.</p>
                    <div class="faq-cta-actions">
                        <a href="/contact" class="btn btn-primary">
                            <i data-lucide="mail" style="width:18px;height:18px"></i>
                            Contact Us
                        </a>
                        <a href="/register" class="btn btn-secondary">
                            <i data-lucide="user-plus" style="width:18px;height:18px"></i>
                            Create Your Profile
                        </a>
                    </div>
                </div>

            </div><!-- /container -->
        </section>
    </main>

    <!-- FOOTER -->
    <footer class="footer" role="contentinfo">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <div class="footer-logo">
                        <div class="footer-logo-icon"><i data-lucide="graduation-cap"></i></div>
                        <span class="footer-logo-text">ScholarMatch</span>
                    </div>
                    <p>Find scholarships that match your profile. Receive personalized alerts through WhatsApp and email.</p>
                </div>
                <div class="footer-col">
                    <h4>Product</h4>
                    <a href="/scholarships">Scholarships</a>
                    <a href="/how-it-works">How It Works</a>
                    <a href="/features">Features</a>
                    <a href="/pricing">Pricing</a>
                    <a href="/faq">FAQ</a>
                </div>
                <div class="footer-col">
                    <h4>Company</h4>
                    <a href="/about">About</a>
                    <a href="/contact">Contact</a>
                    <a href="#">Careers</a>
                </div>
                <div class="footer-col">
                    <h4>Legal</h4>
                    <a href="/privacy">Privacy Policy</a>
                    <a href="/terms">Terms of Service</a>
                    <a href="#">Cookie Policy</a>
                    <a href="#">Refund Policy</a>
                </div>
            </div>
            <div class="footer-bottom">
                <span>&copy; 2025 ScholarMatch. All rights reserved.</span>
                <span><a href="#" style="color:var(--text-400);transition:color 150ms" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='var(--text-400)'">Help Center</a></span>
            </div>
        </div>
    </footer>

    <script>
        lucide.createIcons();

        // ===== MOBILE MENU =====
        const mobileMenuBtn=document.getElementById('mobileMenuBtn'),
              mobileMenuClose=document.getElementById('mobileMenuClose'),
              mobileMenu=document.getElementById('mobileMenu'),
              mobileMenuOverlay=document.getElementById('mobileMenuOverlay');

        function openMenu(){mobileMenu.classList.add('active');mobileMenuOverlay.classList.add('active');mobileMenu.setAttribute('aria-hidden','false');mobileMenuBtn.setAttribute('aria-expanded','true');document.body.classList.add('menu-open')}
        function closeMenu(){mobileMenu.classList.remove('active');mobileMenuOverlay.classList.remove('active');mobileMenu.setAttribute('aria-hidden','true');mobileMenuBtn.setAttribute('aria-expanded','false');document.body.classList.remove('menu-open')}

        mobileMenuBtn.addEventListener('click',openMenu);
        mobileMenuClose.addEventListener('click',closeMenu);
        mobileMenuOverlay.addEventListener('click',closeMenu);
        document.addEventListener('keydown',function(e){if(e.key==='Escape')closeMenu()});
        mobileMenu.querySelectorAll('a').forEach(function(l){l.addEventListener('click',closeMenu)});

        // ===== FAQ ACCORDION =====
        document.querySelectorAll('.faq-question').forEach(function(btn){
            btn.addEventListener('click',function(){
                var item=this.closest('.faq-item');
                var isActive=item.classList.contains('active');
                var expanded=!isActive;

                // Close siblings in same list
                item.closest('.faq-list').querySelectorAll('.faq-item.active').forEach(function(ai){
                    if(ai!==item){
                        ai.classList.remove('active');
                        ai.querySelector('.faq-question').setAttribute('aria-expanded','false');
                    }
                });

                item.classList.toggle('active');
                this.setAttribute('aria-expanded',String(expanded));
            });
        });

        // ===== CATEGORY FILTERING =====
        var allCatBtns = document.querySelectorAll('.faq-cat-btn, .faq-sidebar-link');
        var allCategories = document.querySelectorAll('.faq-category');

        function filterFaq(cat) {
            // Update active states on all nav elements
            allCatBtns.forEach(function(b){
                b.classList.toggle('active', b.dataset.cat === cat);
            });

            // Show/hide categories
            allCategories.forEach(function(c){
                if(cat === 'all') {
                    c.classList.remove('faq-category-hidden');
                } else {
                    c.classList.toggle('faq-category-hidden', c.dataset.category !== cat);
                }
            });
        }

        allCatBtns.forEach(function(btn){
            btn.addEventListener('click', function(){
                filterFaq(this.dataset.cat);

                // On mobile, scroll to content
                if(window.innerWidth < 1024) {
                    var target = this.dataset.cat === 'all'
                        ? document.getElementById('faqContent')
                        : document.getElementById('cat-' + this.dataset.cat);
                    if(target) {
                        var headerH = document.querySelector('.header').offsetHeight;
                        var top = target.getBoundingClientRect().top + window.scrollY - headerH - 16;
                        window.scrollTo({top: top, behavior: 'smooth'});
                    }
                }
            });
        });

        // ===== SCROLL REVEAL =====
        var pr=window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if(!pr){
            var o=new IntersectionObserver(function(e){
                e.forEach(function(x){if(x.isIntersecting){x.target.classList.add('revealed');o.unobserve(x.target)}})
            },{threshold:0.05,rootMargin:'0px 0px -20px 0px'});
            document.querySelectorAll('.reveal').forEach(function(el){o.observe(el)});
        }else{
            document.querySelectorAll('.reveal').forEach(function(el){el.classList.add('revealed')});
        }
    </script>
</body>
</html>
