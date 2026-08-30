<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy — ScholarMatch</title>
    <meta name="description" content="Privacy Policy for ScholarMatch. Learn how we collect, use, store, and protect your personal information.">
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
        .mobile-toc-btn{font-size:0.75rem;font-weight:500;color:var(--text-500);background:var(--bg-white);border:1px solid var(--border);padding:6px 12px;border-radius:100px;min-height:32px;transition:all 150ms}
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

        .legal-success{background:var(--accent-50);border-left:3px solid var(--accent);padding:14px 18px;border-radius:0 var(--radius-md) var(--radius-md) 0;margin:14px 0;font-size:0.875rem;line-height:1.7;color:#065f46}

        .legal-table-wrap{overflow-x:auto;margin:14px 0;-webkit-overflow-scrolling:touch}
        .legal-table{width:100%;border-collapse:collapse;min-width:420px;font-size:0.875rem}
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
        <div class="breadcrumb-bar">
            <div class="container">
                <nav class="breadcrumb" aria-label="Breadcrumb">
                    <a href="/">Home</a>
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
                    <div class="legal-content reveal">

                        <div class="legal-content-header">
                            <h1>Privacy Policy</h1>
                            <div class="legal-meta">
                                <span><i data-lucide="calendar"></i> Effective: July 1, 2025</span>
                                <span><i data-lucide="rotate-ccw"></i> Last Updated: July 1, 2025</span>
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
                            <p>ScholarMatch ("we", "us", or "our") is committed to protecting your privacy. This Privacy Policy explains how we collect, use, disclose, store, and protect your personal information when you use our website and services (collectively, the "Service").</p>
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
                                <li>Messages are sent through the WhatsApp Business API provided by Meta Platforms, Inc.</li>
                                <li>Message content is limited to scholarship name, match score, key details, and a link to the full page on our website</li>
                                <li>We do not read your WhatsApp messages, access your contacts, or receive any data from your WhatsApp account beyond delivery confirmations</li>
                            </ul>
                            <div class="legal-warning">
                                <strong>Note:</strong> By providing your WhatsApp number, you acknowledge that messages will be sent to you through Meta's WhatsApp infrastructure. Meta's own privacy practices apply to the delivery and storage of these messages. Please review <a href="https://www.whatsapp.com/legal/privacy-policy" target="_blank" rel="noopener noreferrer">WhatsApp's Privacy Policy</a> for details.
                            </div>
                            <p>You can stop WhatsApp notifications at any time from your dashboard settings or by replying "STOP" to any message. Stopping WhatsApp notifications does not affect your email alerts or subscription.</p>
                        </div>

                        <!-- 5. Cookies -->
                        <div class="legal-section" id="sec-cookies">
                            <h2>5. Cookies & Tracking Technologies</h2>
                            <p>We use a limited number of cookies and similar technologies:</p>
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
                            <p>We do not use advertising cookies or third-party tracking pixels. You can manage cookie preferences through your browser settings. Disabling essential cookies may affect the functionality of the Service.</p>
                        </div>

                        <!-- 6. Third-Party Sharing -->
                        <div class="legal-section" id="sec-share">
                            <h2>6. Third-Party Sharing</h2>
                            <div class="legal-highlight">
                                <strong>We do not sell your personal information.</strong>
                            </div>
                            <p>We may share your information only in the following circumstances:</p>
                            <ul>
                                <li><strong>Service providers:</strong> With companies that help us operate the Service (hosting, payment processing, WhatsApp Business API, email delivery). These providers are bound by data processing agreements and may only use your data as instructed by us.</li>
                                <li><strong>Legal requirements:</strong> When required by law, regulation, legal process, or governmental request</li>
                                <li><strong>Safety:</strong> To protect the rights, property, or safety of ScholarMatch, our users, or the public</li>
                                <li><strong>Business transfers:</strong> In connection with a merger, acquisition, or sale of assets (with notice to affected users)</li>
                            </ul>
                            <p>We do not share your profile data, scholarship match history, or personal details with scholarship providers, universities, or other third parties for their marketing purposes.</p>
                        </div>

                        <!-- 7. Data Storage -->
                        <div class="legal-section" id="sec-storage">
                            <h2>7. Data Storage</h2>
                            <p>Your data is stored on secure servers. We aim to store data within Pakistan where feasible. Due to the nature of our service providers, some data may be processed or stored in other jurisdictions.</p>
                            <p>By using the Service, you acknowledge that your data may be transferred to and processed in countries other than your country of residence. We take appropriate measures to ensure an adequate level of protection regardless of location.</p>
                        </div>

                        <!-- 8. Data Security -->
                        <div class="legal-section" id="sec-security">
                            <h2>8. Data Security</h2>
                            <p>We implement reasonable technical and organizational measures to protect your personal information, including:</p>
                            <ul>
                                <li>Encryption in transit (TLS/HTTPS)</li>
                                <li>Encrypted password storage (hashing)</li>
                                <li>Access controls limiting data access to authorized personnel only</li>
                                <li>Regular security reviews of our systems and processes</li>
                                <li>Logging and monitoring for suspicious activity</li>
                            </ul>
                            <div class="legal-warning">
                                <strong>Important:</strong> No method of transmission over the Internet or electronic storage is 100% secure. While we strive to protect your information, we cannot guarantee absolute security. You are responsible for maintaining the confidentiality of your account credentials.
                            </div>
                        </div>

                        <!-- 9. Data Retention -->
                        <div class="legal-section" id="sec-retention">
                            <h2>9. Data Retention</h2>
                            <div class="legal-table-wrap">
                                <table class="legal-table">
                                    <thead>
                                        <tr>
                                            <th>Data Type</th>
                                            <th>Retention Period</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Account information</td>
                                            <td>While your account is active + 30 days after deletion request or account closure</td>
                                        </tr>
                                        <tr>
                                            <td>Profile data</td>
                                            <td>While your account is active + 30 days after deletion request</td>
                                        </tr>
                                        <tr>
                                            <td>WhatsApp number</td>
                                            <td>Until you remove it or delete your account</td>
                                        </tr>
                                        <tr>
                                            <td>Interaction history</td>
                                            <td>12 months from the interaction date</td>
                                        </tr>
                                        <tr>
                                            <td>Payment records</td>
                                            <td>5 years (as required for tax and accounting purposes)</td>
                                        </tr>
                                        <tr>
                                            <td>Support communications</td>
                                            <td>2 years from the last interaction</td>
                                        </tr>
                                        <tr>
                                            <td>Server logs</td>
                                            <td>90 days</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <p>After the applicable retention period, data is securely deleted or anonymized unless we are legally required to retain it longer.</p>
                        </div>

                        <!-- 10. Your Rights -->
                        <div class="legal-section" id="sec-rights">
                            <h2>10. Your Rights</h2>
                            <p>Depending on your location, you may have the following rights regarding your personal data:</p>

                            <h3>10.1 Access</h3>
                            <p>You can view and download the personal information we hold about you through your account dashboard settings.</p>

                            <h3>10.2 Correction</h3>
                            <p>You can update your profile information at any time through your dashboard. For other data corrections, contact our support team.</p>

                            <h3>10.3 Deletion</h3>
                            <p>You can request deletion of your account and associated data. Upon request, we will delete your data within 30 days, except where retention is required by law (such as payment records).</p>

                            <h3>10.4 Notification Preferences</h3>
                            <p>You can control which notifications you receive (WhatsApp, email) and opt out at any time through your dashboard or the unsubscribe mechanisms provided.</p>

                            <h3>10.5 Data Portability</h3>
                            <p>You can request a copy of your data in a structured, machine-readable format by contacting our support team.</p>

                            <p>To exercise any of these rights, contact us at <a href="/contact">scolarmatch.com/contact</a> or email legal@scolarmatch.com. We will respond within 30 days and may need to verify your identity before processing your request.</p>
                        </div>

                        <!-- 11. Children's Privacy -->
                        <div class="legal-section" id="sec-children">
                            <h2>11. Children's Privacy</h2>
                            <p>The Service is not directed at children under the age of 16. We do not knowingly collect personal information from children under 16. If we become aware that we have collected such information, we will take steps to delete it promptly.</p>
                        </div>

                        <!-- 12. Changes -->
                        <div class="legal-section" id="sec-changes">
                            <h2>12. Changes to This Policy</h2>
                            <p>We may update this Privacy Policy from time to time. Material changes will be communicated through:</p>
                            <ul>
                                <li>A notice on the Service (e.g., a banner notification)</li>
                                <li>An email to the address associated with your account</li>
                                <li>Updating the "Last Updated" date at the top of this page</li>
                            </ul>
                            <p>We encourage you to review this page periodically. Your continued use of the Service after changes are posted constitutes your acceptance of the updated policy.</p>
                        </div>

                        <!-- 13. Contact -->
                        <div class="legal-section" id="sec-contact">
                            <h2>13. Contact Us About Privacy</h2>
                            <p>If you have questions, concerns, or requests regarding this Privacy Policy or our data practices, please contact us:</p>
                            <ul>
                                <li><strong>Email:</strong> legal@scolarmatch.com</li>
                                <li><strong>Contact Page:</strong> <a href="/contact">scolarmatch.com/contact</a></li>
                            </ul>
                            <p>For data access, correction, deletion, or portability requests, we will acknowledge your request within 10 business days and respond substantively within 30 days.</p>
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

                allTocLinks.forEach(function(l){ l.classList.remove('active') });
                this.classList.add('active');

                var headerH = document.querySelector('.header').offsetHeight;
                var top = target.getBoundingClientRect().top + window.scrollY - headerH - 16;
                window.scrollTo({ top: top, behavior: 'smooth' });
            });
        });

        // Active TOC tracking
        var sections = document.querySelectorAll('.legal-section');
        var tocLinksDesktop = document.querySelectorAll('.toc-link');

        function updateActiveToc() {
            var headerH = document.querySelector('.header').offsetHeight;
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