<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us — ScholarMatch</title>
    <meta name="description" content="Get in touch with ScholarMatch. Send us a message about scholarships, your account, partnerships, or general questions.">
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
            --whatsapp: #25D366;
            --whatsapp-dark: #128C7E;
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
        .btn-primary:disabled{opacity:0.6;pointer-events:none;transform:none}
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
        .contact-hero{background:linear-gradient(180deg,var(--primary-50) 0%,var(--bg-100) 100%);padding:calc(var(--header-height) + clamp(40px,7vw,64px)) 0 clamp(40px,7vw,64px);text-align:center}
        .contact-hero h1{margin-bottom:10px}
        .contact-hero p{font-size:clamp(0.9375rem,1.5vw,1.0625rem);color:var(--text-500);max-width:520px;margin:0 auto;line-height:1.7}

        /* ===== LAYOUT ===== */
        .contact-layout{padding:clamp(24px,4vw,48px) 0 clamp(48px,8vw,80px)}
        .contact-grid{display:grid;grid-template-columns:1fr;gap:24px}
        @media(min-width:768px){.contact-grid{grid-template-columns:1fr 380px;gap:clamp(24px,4vw,40px);align-items:start}}

        /* ===== CONTACT INFO CARDS ===== */
        .info-cards{display:grid;grid-template-columns:1fr;gap:12px;margin-bottom:32px}
        @media(min-width:640px){.info-cards{grid-template-columns:repeat(3,1fr)}}
        @media(min-width:1024px){.info-cards{grid-template-columns:1fr;margin-bottom:0}}

        .info-card{display:flex;align-items:flex-start;gap:14px;padding:18px;background:var(--bg-white);border:1px solid var(--border);border-radius:var(--radius-lg);transition:all 200ms}
        .info-card:hover{border-color:var(--primary-200);box-shadow:var(--shadow-xs)}
        .info-card-icon{width:40px;height:40px;border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;flex-shrink:0}
        .info-card-icon.blue{background:var(--primary-50);color:var(--primary-light)}
        .info-card-icon.green{background:var(--accent-50);color:var(--accent)}
        .info-card-icon.amber{background:#fef3c7;color:#d97706}
        .info-card-icon i{width:20px;height:20px}
        .info-card-label{font-size:0.75rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:var(--text-400);margin-bottom:2px}
        .info-card-value{font-size:0.875rem;color:var(--text-700);font-weight:500;line-height:1.4}
        .info-card-note{font-size:0.75rem;color:var(--text-400);margin-top:2px}

        /* ===== FORM CARD ===== */
        .form-card{background:var(--bg-white);border:1px solid var(--border);border-radius:var(--radius-xl);padding:clamp(20px,3vw,32px);box-shadow:var(--shadow-sm)}
        .form-card h2{margin-bottom:4px}
        .form-card>p{font-size:0.875rem;color:var(--text-500);margin-bottom:24px}

        .form-group{margin-bottom:18px}
        .form-label{display:block;font-size:0.8125rem;font-weight:500;color:var(--text-700);margin-bottom:6px}
        .form-label .required{color:#dc2626;margin-left:2px}

        .form-input,.form-select,.form-textarea{
            width:100%;padding:12px 14px;font-size:0.9375rem;font-family:var(--font);
            color:var(--text-900);background:var(--bg-white);border:1.5px solid var(--border);
            border-radius:var(--radius-md);transition:all 200ms;min-height:44px;
        }
        .form-input::placeholder,.form-textarea::placeholder{color:var(--text-300)}
        .form-input:focus,.form-select:focus,.form-textarea:focus{
            border-color:var(--primary-light);outline:none;
            box-shadow:0 0 0 3px rgba(59,130,246,0.1);
        }
        .form-input.error,.form-select.error,.form-textarea.error{border-color:#dc2626}
        .form-input.error:focus,.form-select.error:focus,.form-textarea.error:focus{box-shadow:0 0 0 3px rgba(220,38,38,0.1)}

        .form-select{
            appearance:none;
            background:var(--bg-white) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") no-repeat right 12px center;
            padding-right:36px;cursor:pointer;
        }

        .form-textarea{resize:vertical;min-height:120px;line-height:1.6}

        .form-error{font-size:0.75rem;color:#dc2626;margin-top:4px;display:none;align-items:center;gap:4px}
        .form-error.visible{display:flex}
        .form-error i{width:14px;height:14px;flex-shrink:0}

        .form-row{display:grid;grid-template-columns:1fr;gap:16px}
        @media(min-width:540px){.form-row{grid-template-columns:1fr 1fr}}

        .form-submit-area{display:flex;flex-direction:column;gap:10px;margin-top:24px}
        .form-submit-area .btn{width:100%}
        @media(min-width:480px){.form-submit-area{flex-direction:row}.form-submit-area .btn{width:auto}}

        .form-note{font-size:0.75rem;color:var(--text-400);display:flex;align-items:center;gap:4px}
        .form-note i{width:14px;height:14px;flex-shrink:0}

        /* Success state */
        .form-success{display:none;text-align:center;padding:40px 20px}
        .form-success.active{display:block}
        .form-success-icon{width:64px;height:64px;border-radius:50%;background:var(--accent-50);color:var(--accent);display:flex;align-items:center;justify-content:center;margin:0 auto 20px}
        .form-success-icon i{width:32px;height:32px}
        .form-success h3{font-size:1.25rem;margin-bottom:8px}
        .form-success p{font-size:0.9375rem;color:var(--text-500);max-width:380px;margin:0 auto 24px;line-height:1.7}

        /* ===== SIDEBAR ===== */
        .contact-sidebar{display:flex;flex-direction:column;gap:20px}

        .sidebar-card{background:var(--bg-white);border:1px solid var(--border);border-radius:var(--radius-xl);padding:24px;box-shadow:var(--shadow-xs)}
        .sidebar-card-title{font-size:0.8125rem;font-weight:600;text-transform:uppercase;letter-spacing:0.06em;color:var(--text-400);margin-bottom:16px}

        /* FAQ links */
        .faq-link-list{display:flex;flex-direction:column;gap:2px}
        .faq-link-item{display:flex;align-items:center;gap:8px;padding:9px 0;font-size:0.875rem;color:var(--text-600);transition:color 150ms;border-bottom:1px solid var(--border-light)}
        .faq-link-item:last-child{border-bottom:none}
        .faq-link-item:hover{color:var(--primary)}
        .faq-link-item i{width:16px;height:16px;color:var(--text-300);flex-shrink:0}

        /* WhatsApp card */
        .wa-contact-card{background:linear-gradient(135deg,#f0fdf4,#ecfdf5);border-color:rgba(37,211,102,0.25)}
        .wa-contact-icon{width:48px;height:48px;border-radius:50%;background:var(--whatsapp);color:#fff;display:flex;align-items:center;justify-content:center;margin-bottom:14px}
        .wa-contact-icon i{width:24px;height:24px}
        .wa-contact-card h3{margin-bottom:6px;color:var(--whatsapp-dark)}
        .wa-contact-card p{font-size:0.8125rem;color:var(--text-600);line-height:1.6;margin-bottom:16px}
        .wa-contact-btn{display:inline-flex;align-items:center;gap:8px;padding:10px 20px;background:var(--whatsapp);color:#fff;border-radius:var(--radius-md);font-size:0.875rem;font-weight:600;transition:all 200ms;min-height:44px}
        .wa-contact-btn:hover{background:#1fb855;transform:translateY(-1px);box-shadow:0 4px 12px rgba(37,211,102,0.3)}
        .wa-contact-btn i{width:18px;height:18px}

        /* Response time card */
        .response-card{text-align:center}
        .response-time{font-size:2rem;font-weight:700;color:var(--primary);line-height:1}
        .response-label{font-size:0.8125rem;color:var(--text-500);margin-top:4px}
        .response-detail{font-size:0.8125rem;color:var(--text-400);margin-top:12px;line-height:1.55}

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
        .reveal-delay-2{transition-delay:200ms}
        @media(prefers-reduced-motion:reduce){.reveal{opacity:1;transform:none;transition:none}html{scroll-behavior:auto}*,*::before,*::after{transition-duration:0.01ms!important;animation-duration:0.01ms!important}}

        @media(max-width:320px){
            .form-row{grid-template-columns:1fr}
            .form-submit-area .btn{width:100%}
            .info-cards{grid-template-columns:1fr}
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
            <a href="/contact" class="active">Contact</a>
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
                    <span class="current">Contact</span>
                </nav>
            </div>
        </div>

        <!-- Hero -->
        <section class="contact-hero">
            <div class="container reveal">
                <h1>Get in Touch</h1>
                <p>Have a question about ScholarMatch, need help with your account, or want to discuss a partnership? We'd like to hear from you.</p>
            </div>
        </section>

        <!-- Contact Content -->
        <section class="contact-layout">
            <div class="container">
                <div class="contact-grid">

                    <!-- LEFT: Form -->
                    <div>
                        <!-- Info cards (mobile/tablet) -->
                        <div class="info-cards reveal" id="infoCardsMobile">
                            <div class="info-card">
                                <div class="info-card-icon blue"><i data-lucide="mail"></i></div>
                                <div>
                                    <div class="info-card-label">Email</div>
                                    <div class="info-card-value">support@scolarmatch.com</div>
                                </div>
                            </div>
                            <div class="info-card">
                                <div class="info-card-icon green"><i data-lucide="message-circle"></i></div>
                                <div>
                                    <div class="info-card-label">WhatsApp</div>
                                    <div class="info-card-value">+92 3XX XXXXXXX</div>
                                    <div class="info-card-note">For quick questions</div>
                                </div>
                            </div>
                            <div class="info-card">
                                <div class="info-card-icon amber"><i data-lucide="clock"></i></div>
                                <div>
                                    <div class="info-card-label">Response Time</div>
                                    <div class="info-card-value">Within 24–48 hours</div>
                                </div>
                            </div>
                        </div>

                        <!-- Form -->
                        <div class="form-card reveal reveal-delay-1" id="formCard">
                            <h2>Send Us a Message</h2>
                            <p>Fill out the form below and we'll get back to you as soon as possible.</p>

                            <form id="contactForm" novalidate>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label" for="firstName">First Name <span class="required">*</span></label>
                                        <input type="text" class="form-input" id="firstName" name="first_name" placeholder="e.g., Ahmed" required autocomplete="given-name">
                                        <div class="form-error" id="firstNameError"><i data-lucide="alert-circle"></i><span>Please enter your first name</span></div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label" for="lastName">Last Name <span class="required">*</span></label>
                                        <input type="text" class="form-input" id="lastName" name="last_name" placeholder="e.g., Khan" required autocomplete="family-name">
                                        <div class="form-error" id="lastNameError"><i data-lucide="alert-circle"></i><span>Please enter your last name</span></div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="email">Email Address <span class="required">*</span></label>
                                    <input type="email" class="form-input" id="email" name="email" placeholder="e.g., ahmed@example.com" required autocomplete="email">
                                    <div class="form-error" id="emailError"><i data-lucide="alert-circle"></i><span>Please enter a valid email address</span></div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="subject">Subject <span class="required">*</span></label>
                                    <select class="form-select" id="subject" name="subject" required>
                                        <option value="" disabled selected>Select a topic</option>
                                        <option value="general">General Question</option>
                                        <option value="account">Account Issue</option>
                                        <option value="subscription">Subscription & Payment</option>
                                        <option value="scholarship">Scholarship Information</option>
                                        <option value="whatsapp">WhatsApp Alerts</option>
                                        <option value="partnership">Partnership / Institution</option>
                                        <option value="bug">Bug Report</option>
                                        <option value="feedback">Feedback / Suggestion</option>
                                        <option value="other">Other</option>
                                    </select>
                                    <div class="form-error" id="subjectError"><i data-lucide="alert-circle"></i><span>Please select a subject</span></div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="message">Message <span class="required">*</span></label>
                                    <textarea class="form-textarea" id="message" name="message" placeholder="Tell us how we can help you..." required rows="5"></textarea>
                                    <div class="form-error" id="messageError"><i data-lucide="alert-circle"></i><span>Please enter your message (at least 10 characters)</span></div>
                                </div>

                                <div class="form-submit-area">
                                    <button type="submit" class="btn btn-primary" id="submitBtn">
                                        <i data-lucide="send" style="width:18px;height:18px"></i>
                                        Send Message
                                    </button>
                                    <span class="form-note">
                                        <i data-lucide="shield-check"></i>
                                        Your information is kept private and secure.
                                    </span>
                                </div>
                            </form>

                            <!-- Success state -->
                            <div class="form-success" id="formSuccess">
                                <div class="form-success-icon">
                                    <i data-lucide="check-circle-2"></i>
                                </div>
                                <h3>Message Sent Successfully</h3>
                                <p>Thank you for reaching out. We'll review your message and get back to you within 24–48 hours. Check your email for a confirmation.</p>
                                <button class="btn btn-secondary" id="sendAnotherBtn" type="button">
                                    <i data-lucide="rotate-ccw" style="width:16px;height:16px"></i>
                                    Send Another Message
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT: Sidebar -->
                    <aside class="contact-sidebar">

                        <!-- Info cards (desktop only) -->
                        <div class="info-cards reveal" id="infoCardsDesktop" style="display:none">
                            <div class="info-card">
                                <div class="info-card-icon blue"><i data-lucide="mail"></i></div>
                                <div>
                                    <div class="info-card-label">Email</div>
                                    <div class="info-card-value">support@scolarmatch.com</div>
                                </div>
                            </div>
                            <div class="info-card">
                                <div class="info-card-icon green"><i data-lucide="message-circle"></i></div>
                                <div>
                                    <div class="info-card-label">WhatsApp</div>
                                    <div class="info-card-value">+92 3XX XXXXXXX</div>
                                    <div class="info-card-note">For quick questions</div>
                                </div>
                            </div>
                            <div class="info-card">
                                <div class="info-card-icon amber"><i data-lucide="clock"></i></div>
                                <div>
                                    <div class="info-card-label">Response Time</div>
                                    <div class="info-card-value">Within 24–48 hours</div>
                                </div>
                            </div>
                        </div>

                        <!-- WhatsApp card -->
                        <div class="sidebar-card wa-contact-card reveal">
                            <div class="wa-contact-icon"><i data-lucide="message-circle"></i></div>
                            <h3>Quick Question? WhatsApp Us</h3>
                            <p>For fast responses about your account, subscription, or a general question — send us a message on WhatsApp.</p>
                            <a href="#" class="wa-contact-btn">
                                <i data-lucide="message-circle"></i>
                                Open WhatsApp
                            </a>
                        </div>

                        <!-- Response time -->
                        <div class="sidebar-card response-card reveal reveal-delay-1">
                            <div class="sidebar-card-title">Expected Response</div>
                            <div class="response-time">24–48h</div>
                            <div class="response-label">Typical response time</div>
                            <p class="response-detail">We read every message. Complex issues or partnership inquiries may take slightly longer. WhatsApp messages are often answered faster.</p>
                        </div>

                        <!-- Before contacting -->
                        <div class="sidebar-card reveal reveal-delay-2">
                            <div class="sidebar-card-title">Before You Write</div>
                            <div class="faq-link-list">
                                <a href="/faq" class="faq-link-item">
                                    <i data-lucide="help-circle"></i>
                                    Check our FAQ first
                                </a>
                                <a href="/pricing" class="faq-link-item">
                                    <i data-lucide="credit-card"></i>
                                    Pricing & plan details
                                </a>
                                <a href="/how-it-works" class="faq-link-item">
                                    <i data-lucide="layers"></i>
                                    How the platform works
                                </a>
                                <a href="/about" class="faq-link-item">
                                    <i data-lucide="info"></i>
                                    About ScholarMatch
                                </a>
                                <a href="/privacy" class="faq-link-item">
                                    <i data-lucide="shield"></i>
                                    Privacy policy
                                </a>
                            </div>
                        </div>
                    </aside>

                </div><!-- /contact-grid -->
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

        // ===== RESPONSIVE INFO CARDS =====
        // Show desktop info cards at 1024+, hide mobile ones
        function handleInfoCardsResize() {
            var desktop = document.getElementById('infoCardsDesktop');
            var mobile = document.getElementById('infoCardsMobile');
            if (window.innerWidth >= 1024) {
                desktop.style.display = 'grid';
                mobile.style.display = 'none';
            } else {
                desktop.style.display = 'none';
                mobile.style.display = 'grid';
            }
        }
        handleInfoCardsResize();
        window.addEventListener('resize', handleInfoCardsResize);

        // ===== FORM VALIDATION & SUBMIT =====
        var form = document.getElementById('contactForm');
        var formCard = document.getElementById('formCard');
        var formSuccess = document.getElementById('formSuccess');
        var submitBtn = document.getElementById('submitBtn');
        var sendAnotherBtn = document.getElementById('sendAnotherBtn');

        function showError(id) {
            var el = document.getElementById(id);
            el.classList.add('visible');
            var input = el.previousElementSibling;
            if (input) input.classList.add('error');
        }

        function clearError(id) {
            var el = document.getElementById(id);
            el.classList.remove('visible');
            var input = el.previousElementSibling;
            if (input) input.classList.remove('error');
        }

        function clearAllErrors() {
            document.querySelectorAll('.form-error').forEach(function(e){e.classList.remove('visible')});
            document.querySelectorAll('.form-input,.form-select,.form-textarea').forEach(function(e){e.classList.remove('error')});
        }

        // Clear error on input
        ['firstName','lastName','email','subject','message'].forEach(function(id){
            var el = document.getElementById(id);
            el.addEventListener('input', function(){ clearError(id + 'Error') });
            el.addEventListener('change', function(){ clearError(id + 'Error') });
        });

        function validateForm() {
            clearAllErrors();
            var valid = true;
            var firstName = document.getElementById('firstName').value.trim();
            var lastName = document.getElementById('lastName').value.trim();
            var email = document.getElementById('email').value.trim();
            var subject = document.getElementById('subject').value;
            var message = document.getElementById('message').value.trim();

            if (!firstName) { showError('firstNameError'); valid = false; }
            if (!lastName) { showError('lastNameError'); valid = false; }
            if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { showError('emailError'); valid = false; }
            if (!subject) { showError('subjectError'); valid = false; }
            if (!message || message.length < 10) { showError('messageError'); valid = false; }

            return valid;
        }

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            if (!validateForm()) return;

            // Simulate submission
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span style="display:inline-flex;align-items:center;gap:8px"><span style="width:16px;height:16px;border:2px solid rgba(255,255,255,0.3);border-top-color:#fff;border-radius:50%;animation:spin 0.6s linear infinite"></span> Sending...</span>';

            setTimeout(function() {
                form.style.display = 'none';
                formSuccess.classList.add('active');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i data-lucide="send" style="width:18px;height:18px"></i> Send Message';
                lucide.createIcons();
                formCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 1500);
        });

        sendAnotherBtn.addEventListener('click', function() {
            form.reset();
            clearAllErrors();
            formSuccess.classList.remove('active');
            form.style.display = '';
            lucide.createIcons();
        });

        // Spinner keyframes (injected)
        var spinStyle = document.createElement('style');
        spinStyle.textContent = '@keyframes spin{to{transform:rotate(360deg)}}';
        document.head.appendChild(spinStyle);

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
