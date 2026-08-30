<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service — ScholarMatch</title>
    <meta name="description" content="Terms of Service for ScholarMatch. Read our terms governing your use of the scholarship discovery platform.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
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
        .container{width:100%;max-width:1200px;margin:0 auto;padding:0 clamp(16px,4vw,24px)}

        .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;font-size:0.9375rem;font-weight:500;padding:12px 24px;border-radius:var(--radius-md);transition:all 200ms ease;white-space:nowrap;min-height:44px;line-height:1.2}
        .btn-primary{background:var(--primary);color:#fff;box-shadow:0 1px 2px rgba(30,64,175,0.2)}
        .btn-primary:hover{background:var(--primary-dark);box-shadow:0 4px 12px rgba(30,64,175,0.3);transform:translateY(-1px)}
        .btn-secondary{background:var(--bg-white);color:var(--text-700);border:1px solid var(--border)}
        .btn-secondary:hover{background:var(--bg-50);border-color:var(--text-300)}
        .btn-sm{font-size:0.8125rem;padding:8px 16px;min-height:36px}

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

        /* ===== LEGAL LAYOUT ===== */
        .legal-layout{padding:calc(var(--header-height) + clamp(32px,5vw,56px)) 0 clamp(48px,8vw,80px)}

        .legal-grid{display:grid;grid-template-columns:1fr;gap:clamp(24px,4vw,40px)}
        @media(min-width:1024px){.legal-grid{grid-template-columns:200px 1fr;align-items:start}}

        /* Sidebar TOC */
        .legal-toc{display:none;position:sticky;top:calc(var(--header-height) + 24px)}
        @media(min-width:1024px){.legal-toc{display:block}}

        .toc-card{background:var(--bg-white);border:1px solid var(--border);border-radius:var(--radius-xl);padding:16px;box-shadow:var(--shadow-xs);max-height:calc(100vh - var(--header-height) - 48px);overflow-y:auto}
        .toc-title{font-size:0.6875rem;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;color:var(--text-400);padding:0 12px 10px}
        .toc-link{display:block;padding:7px 12px;font-size:0.8125rem;font-weight:450;color:var(--text-500);border-radius:var(--radius-sm);transition:all 150ms;text-align:left;width:100%;line-height:1.4}
        .toc-link:hover{background:var(--bg-50);color:var(--text-900)}
        .toc-link.active{background:var(--primary-50);color:var(--primary);font-weight:500}

        /* Mobile TOC */
        .mobile-toc{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:24px}
        @media(min-width:1024px){.mobile-toc{display:none}}
        .mobile-toc-btn{font-size:0.75rem;font-weight:500;color:var(--text-500);background:var(--bg-white);border:1px solid var(--border);padding:6px 12px;border-radius:100px;min-height:32px;transition:all 150px}
        .mobile-toc-btn:hover{border-color:var(--primary-200);color:var(--primary)}

        /* Content */
        .legal-content{background:var(--bg-white);border:1px solid var(--border);border-radius:var(--radius-xl);padding:clamp(20px,3vw,36px);box-shadow:var(--shadow-sm)}

        .legal-content-header{padding-bottom:20px;border-bottom:1px solid var(--border-light);margin-bottom:24px}
        .legal-content-header h1{font-size:clamp(1.5rem,3.5vw,2rem);margin-bottom:6px}
        .legal-meta{font-size:0.8125rem;color:var(--text-400);display:flex;flex-wrap:wrap;gap:16px}
        .legal-meta span{display:flex;align-items:center;gap:4px}
        .legal-meta i{width:14px;height:14px}

        .legal-section{margin-bottom:32px;scroll-margin-top:calc(var(--header-height) + 24px)}
        .legal-section:last-child{margin-bottom:0}

        .legal-section h2{font-size:1.125rem;font-weight:600;margin-bottom:12px;padding-bottom:8px;border-bottom:1px solid var(--border-light)}

        .legal-section h3{font-size:0.9375rem;font-weight:600;margin-top:18px;margin-bottom:8px}

        .legal-section p{margin-bottom:12px;line-height:1.75;max-width:72ch}
        .legal-section p:last-child{margin-bottom:0}

        .legal-section ul{margin:8px 0 14px 20px;list-style:disc}
        .legal-section li{margin-bottom:6px;line-height:1.65;padding-left:4px}
        .legal-section li::marker{color:var(--text-300);font-size:0.875rem}

        .legal-section ol{margin:8px 0 14px 20px;list-style:decimal}
        .legal-section ol li{margin-bottom:6px;line-height:1.65;padding-left:4px}
        .legal-section ol li::marker{color:var(--text-400);font-size:0.8125rem;font-weight:500}

        .legal-section strong{color:var(--text-900);font-weight:600}

        .legal-section a{color:var(--primary-light);text-decoration:underline;text-underline-offset:2px}
        .legal-section a:hover{color:var(--primary)}

        .legal-highlight{background:var(--primary-50);border-left:3px solid var(--primary);padding:14px 18px;border-radius:0 var(--radius-md) var(--radius-md) 0;margin:14px 0;font-size:0.875rem;line-height:1.7;color:var(--text-700)}
        .legal-highlight strong{color:var(--primary-dark)}

        .legal-warning{background:#fffbeb;border-left:3px solid #f59e0b;padding:14px 18px;border-radius:0 var(--radius-md) var(--radius-md) 0;margin:14px 0;font-size:0.875rem;line-height:1.7;color:#92400e}

        .legal-table-wrap{overflow-x:auto;margin:14px 0;-webkit-overflow-scrolling:touch}
        .legal-table{width:100%;border-collapse:collapse;min-width:400px;font-size:0.875rem}
        .legal-table th{text-align:left;font-size:0.75rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:var(--text-400);padding:10px 14px;border-bottom:2px solid var(--border);background:var(--bg-50);white-space:nowrap}
        .legal-table td{padding:10px 14px;border-bottom:1px solid var(--border-light);color:var(--text-600);line-height:1.5}
        .legal-table tr:last-child td{border-bottom:none}

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

        .reveal{opacity:0;transform:translateY(20px);transition:opacity 600ms ease,transform 600ms ease}
        .reveal.revealed{opacity:1;transform:translateY(0)}
        @media(prefers-reduced-motion:reduce){.reveal{opacity:1;transform:none;transition:none}html{scroll-behavior:auto}*,*::before,*::after{transition-duration:0.01ms!important;animation-duration:0.01ms!important}}

        @media(max-width:320px){
            .legal-meta{flex-direction:column;gap:4px}
            .legal-table{font-size:0.8125rem}
            .legal-table th,.legal-table td{padding:8px 10px}
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
                <a href="/faq">FAQ</a>
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
            <a href="/faq">FAQ</a>
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
                    <span class="current">Terms of Service</span>
                </nav>
            </div>
        </div>

        <section class="legal-layout">
            <div class="container">
                <div class="legal-grid">

                    <!-- Sidebar TOC (desktop) -->
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
                    <div class="legal-content reveal">

                        <div class="legal-content-header">
                            <h1>Terms of Service</h1>
                            <div class="legal-meta">
                                <span><i data-lucide="calendar"></i> Effective: July 1, 2025</span>
                                <span><i data-lucide="rotate-ccw"></i> Last Updated: July 1, 2025</span>
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
                            <p>By accessing or using the ScholarMatch platform ("Service"), you agree to be bound by these Terms of Service ("Terms"). If you do not agree to these Terms, you may not use the Service.</p>
                            <p>These Terms constitute a legally binding agreement between you ("User") and ScholarMatch. By creating an account or using any part of the Service, you acknowledge that you have read, understood, and agree to be bound by these Terms.</p>
                            <p>You must be at least 16 years of age to use this Service. If you are under 18, you should use the Service only with the involvement of a parent or guardian.</p>
                        </div>

                        <!-- 2. Description -->
                        <div class="legal-section" id="sec-service">
                            <h2>2. Description of Service</h2>
                            <p>ScholarMatch provides a scholarship discovery platform that allows users to:</p>
                            <ul>
                                <li>Create a profile with education and preference information</li>
                                <li>View scholarship listings organized from publicly available sources</li>
                                <li>Receive match scores indicating alignment between their profile and scholarship criteria</li>
                                <li>Receive notifications through WhatsApp and email (Premium plan) about matching scholarships</li>
                                <li>View structured scholarship information including eligibility, benefits, documents, and deadlines</li>
                                <li>Access official application links for each scholarship</li>
                            </ul>
                            <div class="legal-highlight">
                                <strong>Important:</strong> ScholarMatch is a discovery and notification platform. We do not award scholarships, process scholarship applications, guarantee acceptance, or make admission decisions on behalf of any scholarship provider.
                            </div>
                        </div>

                        <!-- 3. User Accounts -->
                        <div class="legal-section" id="sec-account">
                            <h2>3. User Accounts</h2>
                            <p>To access certain features of the Service, you must create an account. When creating an account, you agree to:</p>
                            <ul>
                                <li>Provide accurate, current, and complete information</li>
                                <li>Maintain and promptly update your information to keep it accurate</li>
                                <li>Maintain the security of your account credentials</li>
                                <li>Notify us immediately of any unauthorized use of your account</li>
                                <li>Not share your account credentials with any third party</li>
                            </ul>
                            <p>You are responsible for all activity that occurs under your account. ScholarMatch is not liable for any loss or damage arising from your failure to maintain account security.</p>
                            <p>We reserve the right to suspend or terminate accounts that violate these Terms or that have been inactive for an extended period.</p>
                        </div>

                        <!-- 4. User Conduct -->
                        <div class="legal-section" id="sec-conduct">
                            <h2>4. User Conduct</h2>
                            <p>You agree not to:</p>
                            <ul>
                                <li>Use the Service for any unlawful purpose or in violation of any applicable laws</li>
                                <li>Attempt to gain unauthorized access to any part of the Service, other accounts, or computer systems</li>
                                <li>Use automated tools, bots, or scraping methods to access the Service without our permission</li>
                                <li>Reproduce, duplicate, sell, or exploit any portion of the Service for commercial purposes</li>
                                <li>Impersonate any person or entity, or misrepresent your affiliation with any person or entity</li>
                                <li>Interfere with or disrupt the Service, servers, or networks connected to the Service</li>
                                <li>Use the Service to send unsolicited mass communications (spam)</li>
                                <li>Provide false, misleading, or fraudulent information in your profile</li>
                                <li>Attempt to reverse-engineer, decompile, or disassemble any part of the Service</li>
                            </ul>
                        </div>

                        <!-- 5. Scholarship Information -->
                        <div class="legal-section" id="sec-scholarships">
                            <h2>5. Scholarship Information</h2>
                            <div class="legal-warning">
                                <strong>Disclaimer:</strong> All scholarship information on the platform is collected from publicly available sources and is provided for informational purposes only. ScholarMatch does not verify the accuracy, completeness, or current status of every listing.
                            </div>
                            <p>You understand and agree that:</p>
                            <ul>
                                <li>Scholarship eligibility criteria, deadlines, benefits, and requirements may change without notice from the provider</li>
                                <li>Match scores are based on available data and do not guarantee eligibility or acceptance</li>
                                <li>Final eligibility and selection decisions are made solely by the scholarship provider</li>
                                <li>You should always verify scholarship details directly with the official source before applying</li>
                                <li>ScholarMatch is not responsible for the content, policies, or actions of any linked external websites</li>
                                <li>The inclusion of a scholarship does not constitute an endorsement or partnership with the provider</li>
                            </ul>
                        </div>

                        <!-- 6. Subscriptions -->
                        <div class="legal-section" id="sec-subscription">
                            <h2>6. Subscriptions & Payments</h2>
                            <h3>6.1 Plans</h3>
                            <p>The Service offers Free and Premium subscription plans. Premium features include personalized matching, WhatsApp alerts, email alerts, deadline reminders, and application tracking.</p>

                            <h3>6.2 Pricing</h3>
                            <p>Premium subscription pricing is displayed on the <a href="/pricing">Pricing page</a> and may change with notice. Price changes take effect at the start of your next billing cycle.</p>

                            <h3>6.3 Billing Cycle</h3>
                            <p>Subscriptions are billed on a monthly basis unless otherwise stated. Payment is due at the beginning of each billing period.</p>

                            <h3>6.4 Payment Methods</h3>
                            <p>We accept payments through designated mobile wallet services (Easypaisa, JazzCash). You are responsible for any fees charged by your payment provider.</p>

                            <h3>6.5 Auto-Renewal</h3>
                            <p>Unless you cancel before the end of your current billing period, your subscription will automatically renew at the then-current rate.</p>

                            <h3>6.6 No Refund for Partial Use</h3>
                            <p>You will not receive a refund for any portion of a billing period that has already begun. See our <a href="#sec-refunds">Refund Policy</a> for details.</p>
                        </div>

                        <!-- 7. Refunds -->
                        <div class="legal-section" id="sec-refunds">
                            <h2>7. Refund Policy</h2>
                            <div class="legal-table-wrap">
                                <table class="legal-table">
                                    <thead>
                                        <tr>
                                            <th>Scenario</th>
                                            <th>Refund</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Change of mind within 48 hours of first payment</td>
                                            <td>Full refund may be requested</td>
                                        </tr>
                                        <tr>
                                            <td>After 48 hours of first payment</td>
                                            <td>No refund; service remains active until period ends</td>
                                        </tr>
                                        <tr>
                                            <td>Subsequent renewal payments</td>
                                            <td>No refund; cancel before next billing to avoid charges</td>
                                        </tr>
                                        <tr>
                                            <td>Service outage exceeding 24 hours</td>
                                            <td>Proportional credit applied to next period</td>
                                        </tr>
                                        <tr>
                                            <td>Account terminated for Terms violation</td>
                                            <td>No refund</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <p>To request a refund, contact <a href="/contact">our support team</a> within the applicable window. Refund requests are reviewed on a case-by-case basis.</p>
                        </div>

                        <!-- 8. Intellectual Property -->
                        <div class="legal-section" id="sec-intellectual">
                            <h2>8. Intellectual Property</h2>
                            <p>The Service, including its design, text, graphics, logos, code, and overall appearance, is the property of ScholarMatch and is protected by intellectual property laws.</p>
                            <p>You may not:</p>
                            <ul>
                                <li>Copy, modify, distribute, or create derivative works from the Service</li>
                                <li>Use any trademarks, service marks, or logos displayed on the Service</li>
                                <li>Frame or embed the Service on any other website without written permission</li>
                            </ul>
                            <p>Scholarship names, provider names, and organization names referenced on the platform belong to their respective owners and are used for identification purposes only.</p>
                        </div>

                        <!-- 9. Privacy -->
                        <div class="legal-section" id="sec-privacy">
                            <h2>9. Privacy</h2>
                            <p>Your use of the Service is also governed by our <a href="/privacy">Privacy Policy</a>, which explains how we collect, use, store, and protect your personal information. By using the Service, you consent to the practices described in the Privacy Policy.</p>
                        </div>

                        <!-- 10. Limitation of Liability -->
                        <div class="legal-section" id="sec-limitation">
                            <h2>10. Limitation of Liability</h2>
                            <div class="legal-highlight">
                                <strong>To the maximum extent permitted by law:</strong> ScholarMatch, its founders, employees, and affiliates shall not be liable for any indirect, incidental, special, consequential, or punitive damages arising from your use of or inability to use the Service.
                            </div>
                            <p>This includes, but is not limited to, damages for:</p>
                            <ul>
                                <li>Loss of scholarships, funding, or educational opportunities</li>
                                <li>Loss of profits, data, or other intangible losses</li>
                                <li>Personal injury or property damage arising from interactions with third-party scholarship providers</li>
                                <li>Any actions or omissions by scholarship providers, educational institutions, or government bodies</li>
                                <li>Any errors, inaccuracies, or omissions in scholarship information</li>
                            </ul>
                            <p>In no event shall our total liability exceed the amount you paid to us in the twelve (12) months preceding the claim.</p>
                        </div>

                        <!-- 11. Disclaimers -->
                        <div class="legal-section" id="sec-disclaimer">
                            <h2>11. Disclaimers</h2>
                            <p>The Service is provided on an "AS IS" and "AS AVAILABLE" basis without warranties of any kind, either express or implied.</p>
                            <p>ScholarMatch does not warrant that:</p>
                            <ul>
                                <li>The Service will be uninterrupted, timely, secure, or error-free</li>
                                <li>The results obtained from the Service will be accurate or reliable</li>
                                <li>The quality of any scholarship information, match scores, or other content will meet your expectations</li>
                                <li>Any errors in the Service will be corrected</li>
                            </ul>
                            <p>Any content downloaded or otherwise obtained through the Service is done at your own discretion and risk, and you are solely responsible for any damage to your computer system or loss of data.</p>
                        </div>

                        <!-- 12. Termination -->
                        <div class="legal-section" id="sec-termination">
                            <h2>12. Termination</h2>
                            <h3>12.1 By You</h3>
                            <p>You may stop using the Service at any time. You may cancel your Premium subscription from your account settings. Your account data will be retained according to our Privacy Policy unless you request deletion.</p>

                            <h3>12.2 By ScholarMatch</h3>
                            <p>We may suspend or terminate your access to the Service, with or without notice, for conduct that we believe violates these Terms, is harmful to other users or the platform, or for any other reason at our discretion.</p>

                            <h3>12.3 Effect of Termination</h3>
                            <p>Upon termination:</p>
                            <ul>
                                <li>Your right to use the Service ceases immediately</li>
                                <li>Premium features remain available until the end of the paid billing period (if terminated by us for non-violation reasons)</li>
                                <li>Provisions that by their nature should survive termination will remain in effect</li>
                            </ul>
                        </div>

                        <!-- 13. Changes -->
                        <div class="legal-section" id="sec-changes">
                            <h2>13. Changes to Terms</h2>
                            <p>We reserve the right to modify these Terms at any time. Material changes will be communicated through:</p>
                            <ul>
                                <li>A notice on the Service (e.g., a banner or notification)</li>
                                <li>An email to the address associated with your account</li>
                            </ul>
                            <p>Your continued use of the Service after changes are posted constitutes your acceptance of the revised Terms. If you do not agree with the changes, you should stop using the Service and cancel your subscription.</p>
                        </div>

                        <!-- 14. Governing Law -->
                        <div class="legal-section" id="sec-governing">
                            <h2>14. Governing Law</h2>
                            <p>These Terms shall be governed by and construed in accordance with the laws of Pakistan, without regard to its conflict of law provisions. Any disputes arising under or in connection with these Terms shall be subject to the exclusive jurisdiction of the courts of Pakistan.</p>
                            <p>If any provision of these Terms is found to be unenforceable or invalid, that provision shall be limited or eliminated to the minimum extent necessary, and the remaining provisions shall remain in full force and effect.</p>
                        </div>

                        <!-- 15. Contact -->
                        <div class="legal-section" id="sec-contact">
                            <h2>15. Contact Information</h2>
                            <p>For questions about these Terms, please contact us:</p>
                            <ul>
                                <li><strong>Email:</strong> legal@scolarmatch.com</li>
                                <li><strong>Contact Page:</strong> <a href="/contact">scolarmatch.com/contact</a></li>
                            </ul>
                            <p>For general support questions, please visit our <a href="/contact">Contact page</a> or <a href="/faq">FAQ</a>.</p>
                        </div>

                    </div><!-- /legal-content -->
                </div><!-- /legal-grid -->
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
                    <a href="/terms">Terms of Service</a>
                    <a href="/privacy">Privacy Policy</a>
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

        // ===== TOC NAVIGATION =====
        var allTocLinks = document.querySelectorAll('.toc-link, .mobile-toc-btn');

        allTocLinks.forEach(function(link){
            link.addEventListener('click', function(){
                var targetId = this.dataset.target;
                var target = document.getElementById(targetId);
                if(!target) return;

                // Update active states
                allTocLinks.forEach(function(l){ l.classList.remove('active') });
                this.classList.add('active');

                // Scroll to section
                var headerH = document.querySelector('.header').offsetHeight;
                var top = target.getBoundingClientRect().top + window.scrollY - headerH - 16;
                window.scrollTo({ top: top, behavior: 'smooth' });
            });
        });

        // Active TOC tracking on scroll
        var sections = document.querySelectorAll('.legal-section');
        var tocLinksDesktop = document.querySelectorAll('.toc-link');

        function updateActiveToc() {
            var headerH = document.querySelector('.header').offsetHeight;
            var current = '';

            sections.forEach(function(sec){
                var rect = sec.getBoundingClientRect();
                if(rect.top <= headerH + 80) {
                    current = sec.id;
                }
            });

            if(current) {
                tocLinksDesktop.forEach(function(l){
                    l.classList.toggle('active', l.dataset.target === current);
                });
            }
        }

        var tocThrottle = false;
        window.addEventListener('scroll', function(){
            if(!tocThrottle) {
                tocThrottle = true;
                requestAnimationFrame(function(){
                    updateActiveToc();
                    tocThrottle = false;
                });
            }
        }, { passive: true });

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