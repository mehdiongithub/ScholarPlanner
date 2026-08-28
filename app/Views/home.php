<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personalized Scholarship Alerts | Find Scholarships That Match You</title>
    <meta name="description" content="Discover scholarship opportunities matched to your education, academic background and goals. Receive personalized scholarship alerts through WhatsApp and email.">
    <meta property="og:title" content="Personalized Scholarship Alerts | Find Scholarships That Match You">
    <meta property="og:description" content="Discover scholarship opportunities matched to your education, academic background and goals. Receive personalized scholarship alerts through WhatsApp and email.">
    <meta property="og:type" content="website">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">
</head>
<body>

    <!-- ============================================
         HEADER / NAVIGATION
         ============================================ -->
    <header class="header" id="header" role="banner">
        <div class="header-inner">
            <a href="/" class="logo" aria-label="ScholarMatch Home">
                <div class="logo-icon">
                    <i data-lucide="graduation-cap"></i>
                </div>
                <span class="logo-text">ScholarMatch</span>
            </a>

            <nav class="nav-desktop" aria-label="Main navigation">
                <a href="/">Home</a>
                <a href="#scholarships">Scholarships</a>
                <a href="#how-it-works">How It Works</a>
                <a href="#features">Features</a>
                <a href="#pricing">Pricing</a>
                <a href="#faq">FAQ</a>
            </nav>

            <div class="header-actions">
                <a href="<?= url('login') ?>" class="btn-login">Log In</a>
                <a href="<?= url('register') ?>" class="btn btn-primary btn-get-started">Get Started</a>
            </div>

            <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Open menu" aria-expanded="false" aria-controls="mobileMenu">
                <i data-lucide="menu"></i>
            </button>
        </div>
    </header>

    <!-- Mobile menu overlay -->
    <div class="mobile-menu-overlay" id="mobileMenuOverlay" aria-hidden="true"></div>

    <!-- Mobile menu drawer -->
    <nav class="mobile-menu" id="mobileMenu" role="dialog" aria-label="Mobile navigation" aria-hidden="true">
        <div class="mobile-menu-header">
            <span class="logo-text">ScholarMatch</span>
            <button class="mobile-menu-close" id="mobileMenuClose" aria-label="Close menu">
                <i data-lucide="x"></i>
            </button>
        </div>
        <div class="mobile-menu-nav">
            <a href="/">Home</a>
            <a href="#scholarships">Scholarships</a>
            <a href="#how-it-works">How It Works</a>
            <a href="#features">Features</a>
            <a href="#pricing">Pricing</a>
            <a href="#faq">FAQ</a>
            <a href="<?= url('about') ?>">About</a>
            <a href="<?= url('contact') ?>">Contact</a>
        </div>
        <div class="mobile-menu-footer">
            <a href="<?= url('login') ?>" class="btn btn-secondary" style="width:100%">Log In</a>
            <a href="<?= url('register') ?>" class="btn btn-primary" style="width:100%">Get Started</a>
        </div>
    </nav>

    <main>
        <!-- ============================================
             HERO SECTION
             ============================================ -->
        <section class="hero" id="hero">
            <div class="container">
                <div class="hero-inner">
                    <div class="reveal">
                        <div class="hero-trust-badge">
                            <i data-lucide="sparkles"></i>
                            Personalized Scholarship Alerts
                        </div>
                        <h1>Find Scholarships That Match You.</h1>
                        <p class="hero-desc">Tell us about your education, academic background and goals. We help you discover relevant scholarship opportunities and notify you through WhatsApp and email.</p>
                        <div class="hero-buttons">
                            <a href="<?= url('register') ?>" class="btn btn-primary">
                                <i data-lucide="search" style="width:18px;height:18px"></i>
                                Find My Scholarships
                            </a>
                            <a href="#scholarships" class="btn btn-secondary">
                                Explore Scholarships
                            </a>
                        </div>
                        <div class="hero-sub-note">
                            <span><i data-lucide="check-circle-2"></i> Personalized matches</span>
                            <span><i data-lucide="check-circle-2"></i> Deadline alerts</span>
                            <span><i data-lucide="check-circle-2"></i> Detailed requirements</span>
                        </div>
                    </div>

                    <div class="hero-preview reveal reveal-delay-2">
                        <div class="hero-card">
                            <div class="hero-card-header">
                                <div class="hero-card-header-left">
                                    <div class="hero-card-dot active"></div>
                                    <div class="hero-card-dot"></div>
                                    <div class="hero-card-dot"></div>
                                    <span class="hero-card-label">New Scholarship Match</span>
                                </div>
                                <span class="hero-card-match">94% Match</span>
                            </div>
                            <div class="hero-card-body">
                                <div class="hero-card-title">Germany Master's Scholarship</div>
                                <div class="hero-card-country">
                                    <i data-lucide="map-pin" style="width:13px;height:13px"></i>
                                    Germany · Master's Program
                                </div>
                                <div class="hero-card-criteria">
                                    <div class="hero-criteria-item">
                                        <div class="hero-criteria-check"><i data-lucide="check"></i></div>
                                        Computer Science
                                    </div>
                                    <div class="hero-criteria-item">
                                        <div class="hero-criteria-check"><i data-lucide="check"></i></div>
                                        Pakistan Eligible
                                    </div>
                                    <div class="hero-criteria-item">
                                        <div class="hero-criteria-check"><i data-lucide="check"></i></div>
                                        CGPA Requirement Met
                                    </div>
                                    <div class="hero-criteria-item">
                                        <div class="hero-criteria-check"><i data-lucide="check"></i></div>
                                        Master's Program
                                    </div>
                                    <div class="hero-criteria-item">
                                        <div class="hero-criteria-check"><i data-lucide="check"></i></div>
                                        Fully Funded
                                    </div>
                                </div>
                                <div class="hero-card-deadline">
                                    <span class="hero-card-deadline-label">Deadline</span>
                                    <span class="hero-card-deadline-value">18 days remaining</span>
                                </div>
                            </div>
                        </div>

                        <div class="hero-notifications">
                            <div class="hero-notif hero-notif-whatsapp">
                                <div class="hero-notif-icon"><i data-lucide="message-circle"></i></div>
                                <span>New scholarship match available</span>
                            </div>
                            <div class="hero-notif hero-notif-email">
                                <div class="hero-notif-icon"><i data-lucide="mail"></i></div>
                                <span>You have 3 new scholarship matches</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============================================
             TRUST / VALUE BAR
             ============================================ -->
        <section class="trust-bar" aria-label="Key features">
            <div class="container">
                <div class="trust-bar-inner">
                    <div class="trust-bar-item reveal">
                        <div class="trust-bar-icon"><i data-lucide="user-check"></i></div>
                        <span class="trust-bar-label">Personalized Matching</span>
                    </div>
                    <div class="trust-bar-item reveal reveal-delay-1">
                        <div class="trust-bar-icon"><i data-lucide="message-circle"></i></div>
                        <span class="trust-bar-label">WhatsApp Alerts</span>
                    </div>
                    <div class="trust-bar-item reveal reveal-delay-2">
                        <div class="trust-bar-icon"><i data-lucide="mail"></i></div>
                        <span class="trust-bar-label">Email Notifications</span>
                    </div>
                    <div class="trust-bar-item reveal reveal-delay-3">
                        <div class="trust-bar-icon"><i data-lucide="shield-check"></i></div>
                        <span class="trust-bar-label">Verified Information</span>
                    </div>
                    <div class="trust-bar-item reveal reveal-delay-4">
                        <div class="trust-bar-icon"><i data-lucide="clock"></i></div>
                        <span class="trust-bar-label">Deadline Tracking</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============================================
             PROBLEM SECTION
             ============================================ -->
        <section class="section" id="problem">
            <div class="container">
                <div class="section-header reveal">
                    <span class="section-label">
                        <i data-lucide="alert-circle" style="width:14px;height:14px"></i>
                        The Problem
                    </span>
                    <h2>Finding the Right Scholarship Shouldn't Be a Full-Time Job</h2>
                    <p>Students spend countless hours searching, filtering, and tracking scholarships across dozens of sources — often missing deadlines and opportunities.</p>
                </div>

                <div class="problem-grid">
                    <div class="problem-before reveal">
                        <span class="problem-tag">
                            <i data-lucide="x"></i>
                            Before
                        </span>
                        <h3>What Students Currently Do</h3>
                        <div class="problem-list">
                            <div class="problem-list-item">
                                <i data-lucide="x-circle"></i>
                                Search dozens of websites manually
                            </div>
                            <div class="problem-list-item">
                                <i data-lucide="x-circle"></i>
                                Check eligibility criteria one by one
                            </div>
                            <div class="problem-list-item">
                                <i data-lucide="x-circle"></i>
                                Compare requirements across scholarships
                            </div>
                            <div class="problem-list-item">
                                <i data-lucide="x-circle"></i>
                                Try to remember multiple deadlines
                            </div>
                            <div class="problem-list-item">
                                <i data-lucide="x-circle"></i>
                                Scroll through WhatsApp groups for updates
                            </div>
                            <div class="problem-list-item">
                                <i data-lucide="x-circle"></i>
                                Track required documents separately
                            </div>
                        </div>
                        <div class="problem-flow">
                            <span class="problem-flow-step">Search</span>
                            <span class="problem-flow-arrow"><i data-lucide="arrow-right"></i></span>
                            <span class="problem-flow-step">Search</span>
                            <span class="problem-flow-arrow"><i data-lucide="arrow-right"></i></span>
                            <span class="problem-flow-step">Search</span>
                            <span class="problem-flow-arrow"><i data-lucide="arrow-right"></i></span>
                            <span class="problem-flow-step">Missed Deadline</span>
                        </div>
                    </div>

                    <div class="problem-after reveal reveal-delay-2">
                        <span class="problem-tag">
                            <i data-lucide="check"></i>
                            With ScholarMatch
                        </span>
                        <h3>What Students Should Do</h3>
                        <div class="problem-list">
                            <div class="problem-list-item">
                                <i data-lucide="check-circle-2"></i>
                                Create a profile once
                            </div>
                            <div class="problem-list-item">
                                <i data-lucide="check-circle-2"></i>
                                System matches scholarships automatically
                            </div>
                            <div class="problem-list-item">
                                <i data-lucide="check-circle-2"></i>
                                Receive alerts through WhatsApp
                            </div>
                            <div class="problem-list-item">
                                <i data-lucide="check-circle-2"></i>
                                Get email notifications with details
                            </div>
                            <div class="problem-list-item">
                                <i data-lucide="check-circle-2"></i>
                                View complete eligibility and requirements
                            </div>
                            <div class="problem-list-item">
                                <i data-lucide="check-circle-2"></i>
                                Apply through official links
                            </div>
                        </div>
                        <div class="problem-flow">
                            <span class="problem-flow-step">Profile</span>
                            <span class="problem-flow-arrow"><i data-lucide="arrow-right"></i></span>
                            <span class="problem-flow-step">Match</span>
                            <span class="problem-flow-arrow"><i data-lucide="arrow-right"></i></span>
                            <span class="problem-flow-step">Alert</span>
                            <span class="problem-flow-arrow"><i data-lucide="arrow-right"></i></span>
                            <span class="problem-flow-step">Apply</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============================================
             HOW IT WORKS
             ============================================ -->
        <section class="section section-alt" id="how-it-works">
            <div class="container">
                <div class="section-header reveal">
                    <span class="section-label">
                        <i data-lucide="layers" style="width:14px;height:14px"></i>
                        How It Works
                    </span>
                    <h2>Four Simple Steps to Your Scholarships</h2>
                    <p>From profile creation to application — a straightforward process designed to save your time.</p>
                </div>

                <div class="steps-grid">
                    <div class="step-card reveal">
                        <div class="step-icon-wrap">
                            <i data-lucide="user-plus"></i>
                        </div>
                        <h3>Create Your Profile</h3>
                        <p>Add your education, degree, field, CGPA, country, age, study preferences and funding preferences.</p>
                        <div class="step-details">
                            <span class="step-detail-tag">Education</span>
                            <span class="step-detail-tag">Degree</span>
                            <span class="step-detail-tag">Field</span>
                            <span class="step-detail-tag">CGPA</span>
                            <span class="step-detail-tag">Country</span>
                            <span class="step-detail-tag">Preferences</span>
                        </div>
                    </div>

                    <div class="step-card reveal reveal-delay-1">
                        <div class="step-icon-wrap">
                            <i data-lucide="target"></i>
                        </div>
                        <h3>Get Matched</h3>
                        <p>Our system compares scholarship eligibility criteria with your profile to find relevant opportunities.</p>
                    </div>

                    <div class="step-card reveal reveal-delay-2">
                        <div class="step-icon-wrap">
                            <i data-lucide="bell-ring"></i>
                        </div>
                        <h3>Receive Alerts</h3>
                        <p>Get relevant scholarship alerts through WhatsApp, email, and your website dashboard.</p>
                        <div class="step-details">
                            <span class="step-detail-tag">WhatsApp</span>
                            <span class="step-detail-tag">Email</span>
                            <span class="step-detail-tag">Dashboard</span>
                        </div>
                    </div>

                    <div class="step-card reveal reveal-delay-3">
                        <div class="step-icon-wrap">
                            <i data-lucide="external-link"></i>
                        </div>
                        <h3>Review & Apply</h3>
                        <p>View eligibility, requirements, funding, deadline, documents, and apply through the official link.</p>
                        <div class="step-details">
                            <span class="step-detail-tag">Eligibility</span>
                            <span class="step-detail-tag">Documents</span>
                            <span class="step-detail-tag">Deadline</span>
                            <span class="step-detail-tag">Apply</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============================================
             PERSONALIZED MATCHING SECTION
             ============================================ -->
        <section class="section" id="matching">
            <div class="container">
                <div class="section-header reveal">
                    <span class="section-label">
                        <i data-lucide="sparkles" style="width:14px;height:14px"></i>
                        Smart Matching
                    </span>
                    <h2>Your Scholarship Feed. Personalized to You.</h2>
                    <p>Every scholarship you see is matched against your profile — so you spend time applying, not searching.</p>
                </div>

                <div class="matching-grid">
                    <div class="profile-card reveal">
                        <div class="profile-card-title">
                            <i data-lucide="user"></i>
                            Student Profile
                        </div>
                        <div class="profile-field">
                            <div class="profile-field-label">Field</div>
                            <div class="profile-field-value">Computer Science</div>
                        </div>
                        <div class="profile-field">
                            <div class="profile-field-label">Degree</div>
                            <div class="profile-field-value">Bachelor's</div>
                        </div>
                        <div class="profile-field">
                            <div class="profile-field-label">CGPA</div>
                            <div class="profile-field-value">3.20</div>
                        </div>
                        <div class="profile-field">
                            <div class="profile-field-label">Country</div>
                            <div class="profile-field-value">Pakistan</div>
                        </div>
                        <div class="profile-field">
                            <div class="profile-field-label">Preferred Study</div>
                            <div class="profile-field-value">Master's</div>
                        </div>
                        <div class="profile-field">
                            <div class="profile-field-label">Funding</div>
                            <div class="profile-field-value">Fully Funded</div>
                        </div>
                    </div>

                    <div class="reveal reveal-delay-2">
                        <div class="match-list">
                            <div class="match-card">
                                <div class="match-percentage high">94%</div>
                                <div class="match-info">
                                    <div class="match-title">Germany Master's Scholarship</div>
                                    <div class="match-meta">Germany · Master's · Fully Funded</div>
                                </div>
                            </div>
                            <div class="match-card">
                                <div class="match-percentage high">91%</div>
                                <div class="match-info">
                                    <div class="match-title">Turkey Graduate Scholarship</div>
                                    <div class="match-meta">Turkey · Master's · Fully Funded</div>
                                </div>
                            </div>
                            <div class="match-card">
                                <div class="match-percentage medium">87%</div>
                                <div class="match-info">
                                    <div class="match-title">China Government Scholarship</div>
                                    <div class="match-meta">China · Master's · Fully Funded</div>
                                </div>
                            </div>
                            <div class="match-card">
                                <div class="match-percentage medium">84%</div>
                                <div class="match-info">
                                    <div class="match-title">South Korea KGSP</div>
                                    <div class="match-meta">South Korea · Master's · Fully Funded</div>
                                </div>
                            </div>
                        </div>
                        <p class="matching-note">Update your profile anytime your circumstances change to receive updated matches.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============================================
             WHATSAPP + EMAIL ALERT SECTION
             ============================================ -->
        <section class="section section-alt" id="alerts">
            <div class="container">
                <div class="section-header reveal">
                    <span class="section-label">
                        <i data-lucide="bell" style="width:14px;height:14px"></i>
                        Notification System
                    </span>
                    <h2>Get Alerts Where You Already Are</h2>
                    <p>Short, relevant notifications on WhatsApp and email. Complete information when you visit the website.</p>
                </div>

                <div class="alerts-grid">
                    <div class="alert-preview whatsapp-preview reveal">
                        <div class="whatsapp-header">
                            <div class="whatsapp-avatar">
                                <i data-lucide="graduation-cap"></i>
                            </div>
                            <div>
                                <div class="whatsapp-name">ScholarMatch</div>
                                <div class="whatsapp-status">Online</div>
                            </div>
                        </div>
                        <div class="whatsapp-bubble">
                            <div class="whatsapp-msg-emoji">🎓</div>
                            <div class="whatsapp-msg-title">New Scholarship Match</div>
                            <div class="whatsapp-msg-match">94% Match</div>
                            <div class="whatsapp-msg-detail">
                                Germany Master's Scholarship<br>
                                Fully Funded<br>
                                Deadline: 30 September
                            </div>
                            <div class="whatsapp-msg-cta">View Full Details →</div>
                            <div class="whatsapp-time">10:32 AM</div>
                        </div>
                    </div>

                    <div class="alert-preview email-preview reveal reveal-delay-2">
                        <div class="email-window">
                            <div class="email-toolbar">
                                <div class="email-toolbar-dot"></div>
                                <div class="email-toolbar-dot"></div>
                                <div class="email-toolbar-dot"></div>
                            </div>
                            <div class="email-subject-bar">
                                <div class="email-subject">3 New Scholarship Opportunities Match Your Profile</div>
                                <div class="email-from">ScholarMatch &lt;alerts@scolarmatch.com&gt;</div>
                            </div>
                            <div class="email-body">
                                <p class="email-greeting">Hi Ahmed, here are your latest scholarship matches:</p>
                                <div class="email-scholarship-item">
                                    <div class="email-scholarship-name">Germany Master's Scholarship</div>
                                    <div class="email-scholarship-meta">
                                        <span><i data-lucide="target"></i> 94% Match</span>
                                        <span><i data-lucide="map-pin"></i> Germany</span>
                                        <span><i data-lucide="calendar"></i> 30 Sep</span>
                                        <span><i data-lucide="banknote"></i> Fully Funded</span>
                                    </div>
                                </div>
                                <div class="email-scholarship-item">
                                    <div class="email-scholarship-name">Turkey Graduate Scholarship</div>
                                    <div class="email-scholarship-meta">
                                        <span><i data-lucide="target"></i> 91% Match</span>
                                        <span><i data-lucide="map-pin"></i> Turkey</span>
                                        <span><i data-lucide="calendar"></i> 15 Oct</span>
                                        <span><i data-lucide="banknote"></i> Fully Funded</span>
                                    </div>
                                </div>
                                <div class="email-scholarship-item">
                                    <div class="email-scholarship-name">China Government Scholarship</div>
                                    <div class="email-scholarship-meta">
                                        <span><i data-lucide="target"></i> 87% Match</span>
                                        <span><i data-lucide="map-pin"></i> China</span>
                                        <span><i data-lucide="calendar"></i> 20 Nov</span>
                                        <span><i data-lucide="banknote"></i> Fully Funded</span>
                                    </div>
                                </div>
                                <a href="#" class="email-cta-btn">View All Matches</a>
                            </div>
                        </div>
                    </div>
                </div>

                <p class="alerts-note reveal">
                    <strong>Short alerts where you need them.</strong> Complete scholarship information — eligibility, documents, benefits, and official application links — always available on the website.
                </p>
            </div>
        </section>

        <!-- ============================================
             SCHOLARSHIP DETAILS PREVIEW
             ============================================ -->
        <section class="section" id="detail-preview">
            <div class="container">
                <div class="section-header reveal">
                    <span class="section-label">
                        <i data-lucide="file-text" style="width:14px;height:14px"></i>
                        Detail View
                    </span>
                    <h2>Everything You Need Before You Apply</h2>
                    <p>Each scholarship page provides structured, clear information so you can prepare your application confidently.</p>
                </div>

                <div class="detail-preview-card reveal">
                    <div class="detail-preview-header">
                        <div class="detail-preview-badges">
                            <span class="detail-badge detail-badge-active">Active</span>
                            <span class="detail-badge detail-badge-funded">Fully Funded</span>
                        </div>
                        <h3 class="detail-preview-title">Germany Master's Scholarship 2027</h3>
                        <div class="detail-preview-meta">
                            <span><i data-lucide="map-pin"></i> Germany</span>
                            <span><i data-lucide="book-open"></i> Master's</span>
                            <span><i data-lucide="calendar"></i> Deadline: 30 September 2026</span>
                        </div>
                    </div>
                    <div class="detail-preview-body">
                        <div class="detail-section">
                            <div class="detail-section-title">Eligibility</div>
                            <div class="detail-check-list">
                                <div class="detail-check-item">
                                    <div class="detail-check-icon"><i data-lucide="check"></i></div>
                                    Pakistani students eligible
                                </div>
                                <div class="detail-check-item">
                                    <div class="detail-check-icon"><i data-lucide="check"></i></div>
                                    Bachelor's degree required
                                </div>
                                <div class="detail-check-item">
                                    <div class="detail-check-icon"><i data-lucide="check"></i></div>
                                    Computer Science or related field
                                </div>
                                <div class="detail-check-item">
                                    <div class="detail-check-icon"><i data-lucide="check"></i></div>
                                    Minimum CGPA 3.0
                                </div>
                            </div>
                        </div>
                        <div class="detail-section">
                            <div class="detail-section-title">Benefits</div>
                            <div class="detail-bullet-list">
                                <div class="detail-bullet-item">Tuition coverage</div>
                                <div class="detail-bullet-item">Monthly stipend</div>
                                <div class="detail-bullet-item">Accommodation support</div>
                                <div class="detail-bullet-item">Travel allowance</div>
                            </div>
                        </div>
                        <div class="detail-section mb-0">
                            <div class="detail-section-title">Required Documents</div>
                            <div class="detail-bullet-list">
                                <div class="detail-bullet-item">Valid passport</div>
                                <div class="detail-bullet-item">Academic transcripts</div>
                                <div class="detail-bullet-item">CV / Resume</div>
                                <div class="detail-bullet-item">Recommendation letters</div>
                                <div class="detail-bullet-item">Motivation letter</div>
                            </div>
                        </div>
                    </div>
                    <div class="detail-preview-footer">
                        <p class="detail-disclaimer">Eligibility matching is informational. Final eligibility is determined by the scholarship provider.</p>
                        <a href="#" class="btn btn-primary btn-sm">View Full Scholarship</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============================================
             FEATURES GRID
             ============================================ -->
        <section class="section section-alt" id="features">
            <div class="container">
                <div class="section-header reveal">
                    <span class="section-label">
                        <i data-lucide="grid-3x3" style="width:14px;height:14px"></i>
                        Features
                    </span>
                    <h2>Built for Students Who Are Serious About Scholarships</h2>
                    <p>Every feature is designed to help you discover, understand, and act on scholarship opportunities.</p>
                </div>

                <div class="features-grid">
                    <div class="feature-card reveal">
                        <div class="feature-icon"><i data-lucide="user-check"></i></div>
                        <h3>Personalized Scholarship Matching</h3>
                        <p>Only see opportunities relevant to your education, field, and background.</p>
                    </div>
                    <div class="feature-card reveal reveal-delay-1">
                        <div class="feature-icon"><i data-lucide="message-circle"></i></div>
                        <h3>WhatsApp Alerts</h3>
                        <p>Receive concise scholarship notifications directly through WhatsApp.</p>
                    </div>
                    <div class="feature-card reveal reveal-delay-2">
                        <div class="feature-icon"><i data-lucide="mail"></i></div>
                        <h3>Email Alerts</h3>
                        <p>Receive detailed scholarship notifications through email with match scores.</p>
                    </div>
                    <div class="feature-card reveal">
                        <div class="feature-icon"><i data-lucide="clock"></i></div>
                        <h3>Deadline Tracking</h3>
                        <p>Never forget important application deadlines with timely reminders.</p>
                    </div>
                    <div class="feature-card reveal reveal-delay-1">
                        <div class="feature-icon"><i data-lucide="list-checks"></i></div>
                        <h3>Eligibility Details</h3>
                        <p>Clearly understand what each scholarship requires before you apply.</p>
                    </div>
                    <div class="feature-card reveal reveal-delay-2">
                        <div class="feature-icon"><i data-lucide="file-check"></i></div>
                        <h3>Document Checklist</h3>
                        <p>Know exactly what documents you need to prepare for each application.</p>
                    </div>
                    <div class="feature-card reveal">
                        <div class="feature-icon"><i data-lucide="layout-dashboard"></i></div>
                        <h3>Scholarship Dashboard</h3>
                        <p>Save, track, and manage your scholarship opportunities in one place.</p>
                    </div>
                    <div class="feature-card reveal reveal-delay-1">
                        <div class="feature-icon"><i data-lucide="settings-2"></i></div>
                        <h3>Profile Management</h3>
                        <p>Update your academic and personal information whenever your circumstances change.</p>
                    </div>
                    <div class="feature-card reveal reveal-delay-2">
                        <div class="feature-icon"><i data-lucide="external-link"></i></div>
                        <h3>Official Application Links</h3>
                        <p>Direct links to the official application source for every scholarship listed.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============================================
             WHY USE THIS PLATFORM
             ============================================ -->
        <section class="section" id="why">
            <div class="container">
                <div class="section-header reveal">
                    <span class="section-label">
                        <i data-lucide="compass" style="width:14px;height:14px"></i>
                        Why ScholarMatch
                    </span>
                    <h2>Everything You Need Before You Apply</h2>
                    <p>A clear path from discovery to application — designed around how students actually search for scholarships.</p>
                </div>

                <div class="why-grid">
                    <div class="why-card reveal">
                        <div class="why-card-number">1</div>
                        <h3>Match</h3>
                        <p>Find scholarships that align with your profile and goals.</p>
                    </div>
                    <div class="why-card reveal reveal-delay-1">
                        <div class="why-card-number">2</div>
                        <h3>Understand</h3>
                        <p>Review eligibility, benefits, and requirements clearly.</p>
                    </div>
                    <div class="why-card reveal reveal-delay-2">
                        <div class="why-card-number">3</div>
                        <h3>Prepare</h3>
                        <p>Know exactly which documents and information you need.</p>
                    </div>
                    <div class="why-card reveal reveal-delay-3">
                        <div class="why-card-number">4</div>
                        <h3>Apply</h3>
                        <p>Submit your application through the official source.</p>
                    </div>
                    <div class="why-card reveal reveal-delay-4">
                        <div class="why-card-number">5</div>
                        <h3>Track</h3>
                        <p>Monitor deadlines and manage your applications.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============================================
             SCHOLARSHIP CATEGORIES
             ============================================ -->
        <section class="section section-alt" id="categories">
            <div class="container">
                <div class="section-header reveal">
                    <span class="section-label">
                        <i data-lucide="folder-open" style="width:14px;height:14px"></i>
                        Categories
                    </span>
                    <h2>Browse by Category</h2>
                    <p>Explore scholarship opportunities organized by type, level, and funding.</p>
                </div>

                <div class="categories-grid">
                    <a href="#scholarships" class="category-card reveal">
                        <div class="category-icon"><i data-lucide="badge-dollar-sign"></i></div>
                        <span class="category-name">Fully Funded</span>
                    </a>
                    <a href="#scholarships" class="category-card reveal reveal-delay-1">
                        <div class="category-icon"><i data-lucide="book-open"></i></div>
                        <span class="category-name">Undergraduate</span>
                    </a>
                    <a href="#scholarships" class="category-card reveal reveal-delay-2">
                        <div class="category-icon"><i data-lucide="graduation-cap"></i></div>
                        <span class="category-name">Master's</span>
                    </a>
                    <a href="#scholarships" class="category-card reveal">
                        <div class="category-icon"><i data-lucide="award"></i></div>
                        <span class="category-name">PhD</span>
                    </a>
                    <a href="#scholarships" class="category-card reveal reveal-delay-1">
                        <div class="category-icon"><i data-lucide="flask-conical"></i></div>
                        <span class="category-name">Research</span>
                    </a>
                    <a href="#scholarships" class="category-card reveal reveal-delay-2">
                        <div class="category-icon"><i data-lucide="globe"></i></div>
                        <span class="category-name">International</span>
                    </a>
                    <a href="#scholarships" class="category-card reveal">
                        <div class="category-icon"><i data-lucide="landmark"></i></div>
                        <span class="category-name">Government</span>
                    </a>
                    <a href="#scholarships" class="category-card reveal reveal-delay-1">
                        <div class="category-icon"><i data-lucide="building-2"></i></div>
                        <span class="category-name">University</span>
                    </a>
                    <a href="#scholarships" class="category-card reveal reveal-delay-2">
                        <div class="category-icon"><i data-lucide="heart-handshake"></i></div>
                        <span class="category-name">Need-Based</span>
                    </a>
                    <a href="#scholarships" class="category-card reveal">
                        <div class="category-icon"><i data-lucide="trophy"></i></div>
                        <span class="category-name">Merit-Based</span>
                    </a>
                </div>
            </div>
        </section>

        <!-- ============================================
             SCHOLARSHIP PREVIEW CARDS
             ============================================ -->
        <section class="section" id="scholarships">
            <div class="container">
                <div class="section-header reveal">
                    <span class="section-label">
                        <i data-lucide="bookmark" style="width:14px;height:14px"></i>
                        Opportunities
                    </span>
                    <h2>Explore Scholarships</h2>
                    <p>Search over active opportunities. Sign up to get matched directly with your profile requirements.</p>
                </div>

                <!-- Search form on landing page -->
                <div style="max-width: 600px; margin: 0 auto 40px; display: flex; gap: 8px;">
                    <form action="<?= url('/scholarships') ?>" method="GET" style="display: flex; gap: 8px; width: 100%;">
                        <input type="text" name="search" placeholder="Search by title, provider, university..." style="flex-grow: 1; padding: 12px 16px; border: 1px solid var(--border); border-radius: var(--radius-lg); font-size: 0.95rem; outline: none;">
                        <button type="submit" class="btn btn-primary" style="padding: 12px 24px; display: flex; align-items: center; gap: 8px;">
                            <i data-lucide="search" style="width: 16px; height: 16px;"></i> Search
                        </button>
                    </form>
                </div>

                <!-- Popular Categories section -->
                <div class="popular-categories" style="margin-bottom: 50px;">
                    <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 20px; color: var(--text-900);">Browse by Host Country</h3>
                    <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 30px;">
                        <?php foreach ($countries as $c): ?>
                            <a href="<?= url('/scholarships/country/' . strtolower(str_replace(' ', '-', $c['name']))) ?>" style="padding: 8px 16px; border: 1px solid var(--border); border-radius: var(--radius-full); font-size: 0.875rem; text-decoration: none; color: var(--text-700); background: var(--bg-white); font-weight: 500;" onmouseover="this.style.borderColor='var(--primary)'; this.style.color='var(--primary)'" onmouseout="this.style.borderColor='var(--border)'; this.style.color='var(--text-700)'">
                                <?= e($c['name']) ?> (<?= $c['scholarship_count'] ?? 0 ?>)
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 20px; color: var(--text-900);">Browse by Field of Study</h3>
                    <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 30px;">
                        <?php foreach ($fields as $f): ?>
                            <a href="<?= url('/scholarships/field/' . strtolower(str_replace(' ', '-', $f['name']))) ?>" style="padding: 8px 16px; border: 1px solid var(--border); border-radius: var(--radius-full); font-size: 0.875rem; text-decoration: none; color: var(--text-700); background: var(--bg-white); font-weight: 500;" onmouseover="this.style.borderColor='var(--primary)'; this.style.color='var(--primary)'" onmouseout="this.style.borderColor='var(--border)'; this.style.color='var(--text-700)'">
                                <?= e($f['name']) ?> (<?= $f['scholarship_count'] ?? 0 ?>)
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 20px; color: var(--text-900);">Browse by Degree & Funding</h3>
                    <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                        <a href="<?= url('/scholarships/degree/bachelor-s') ?>" style="padding: 8px 16px; border: 1px solid var(--border); border-radius: var(--radius-full); font-size: 0.875rem; text-decoration: none; color: var(--text-700); background: var(--bg-white); font-weight: 500;">Bachelor's</a>
                        <a href="<?= url('/scholarships/degree/master-s') ?>" style="padding: 8px 16px; border: 1px solid var(--border); border-radius: var(--radius-full); font-size: 0.875rem; text-decoration: none; color: var(--text-700); background: var(--bg-white); font-weight: 500;">Master's</a>
                        <a href="<?= url('/scholarships/degree/phd') ?>" style="padding: 8px 16px; border: 1px solid var(--border); border-radius: var(--radius-full); font-size: 0.875rem; text-decoration: none; color: var(--text-700); background: var(--bg-white); font-weight: 500;">PhD</a>
                        <a href="<?= url('/scholarships/degree/diploma') ?>" style="padding: 8px 16px; border: 1px solid var(--border); border-radius: var(--radius-full); font-size: 0.875rem; text-decoration: none; color: var(--text-700); background: var(--bg-white); font-weight: 500;">Diploma</a>
                        <a href="<?= url('/scholarships?funding_type=Fully+Funded') ?>" style="padding: 8px 16px; border: 1px solid var(--border); border-radius: var(--radius-full); font-size: 0.875rem; text-decoration: none; color: var(--text-700); background: var(--bg-white); font-weight: 500;">Fully Funded</a>
                        <a href="<?= url('/scholarships?funding_type=Partially+Funded') ?>" style="padding: 8px 16px; border: 1px solid var(--border); border-radius: var(--radius-full); font-size: 0.875rem; text-decoration: none; color: var(--text-700); background: var(--bg-white); font-weight: 500;">Partially Funded</a>
                    </div>
                </div>

                <div class="listings-row" style="display: grid; grid-template-columns: 1fr; gap: 40px; margin-bottom: 40px;">
                    <!-- Recently Added column -->
                    <div>
                        <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 20px; color: var(--text-900); display: flex; align-items: center; gap: 8px;">
                            <i data-lucide="clock" style="color: var(--primary); width: 20px; height: 20px;"></i> Recently Added Scholarships
                        </h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
                            <?php if (empty($recentScholarships)): ?>
                                <div style="padding: 40px; text-align: center; border: 1px dashed var(--border); border-radius: var(--radius-xl); color: var(--text-500); grid-column: 1 / -1;">No scholarships found.</div>
                            <?php else: ?>
                                <?php foreach ($recentScholarships as $s): ?>
                                    <div class="scholarship-card" style="padding: 24px; border: 1px solid var(--border); border-radius: var(--radius-xl); background: var(--bg-white); position: relative; display: flex; flex-direction: column; box-shadow: var(--shadow-sm);">
                                        <div style="font-size: 0.75rem; font-weight: 600; color: var(--text-500); text-transform: uppercase; margin-bottom: 8px;"><?= e($s['provider_name']) ?></div>
                                        <h4 style="font-size: 1.1rem; font-weight: 700; color: var(--text-900); margin-bottom: 8px; line-height: 1.4;"><?= e($s['title']) ?></h4>
                                        <p style="font-size: 0.875rem; color: var(--text-600); line-height: 1.5; margin-bottom: 16px; flex-grow: 1;"><?= e(substr(strip_tags($s['description']), 0, 100)) ?>...</p>
                                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.8rem; color: var(--text-500); border-top: 1px solid var(--border); padding-top: 12px; margin-top: auto;">
                                            <span><i data-lucide="map-pin" style="width: 14px; height: 14px; display: inline; vertical-align: middle; margin-right: 4px;"></i> <?= e($s['country_name'] ?? 'International') ?></span>
                                            <a href="<?= url('/scholarships/' . e($s['slug'])) ?>" class="btn btn-ghost btn-sm" style="padding: 4px 8px; font-size: 0.75rem;">View Opportunity</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Closing Soon column -->
                    <div>
                        <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 20px; color: var(--text-900); display: flex; align-items: center; gap: 8px;">
                            <i data-lucide="alert-circle" style="color: #ef4444; width: 20px; height: 20px;"></i> Closing Soon (Next 7 Days)
                        </h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
                            <?php if (empty($closingScholarships)): ?>
                                <div style="padding: 40px; text-align: center; border: 1px dashed var(--border); border-radius: var(--radius-xl); color: var(--text-500); grid-column: 1 / -1;">No scholarships closing in the next 7 days.</div>
                            <?php else: ?>
                                <?php foreach ($closingScholarships as $s): ?>
                                    <div class="scholarship-card" style="padding: 24px; border: 1px solid var(--border); border-radius: var(--radius-xl); background: var(--bg-white); position: relative; display: flex; flex-direction: column; box-shadow: var(--shadow-sm);">
                                        <div style="font-size: 0.75rem; font-weight: 600; color: var(--text-500); text-transform: uppercase; margin-bottom: 8px;"><?= e($s['provider_name']) ?></div>
                                        <h4 style="font-size: 1.1rem; font-weight: 700; color: var(--text-900); margin-bottom: 8px; line-height: 1.4;"><?= e($s['title']) ?></h4>
                                        <p style="font-size: 0.875rem; color: var(--text-600); line-height: 1.5; margin-bottom: 16px; flex-grow: 1;"><?= e(substr(strip_tags($s['description']), 0, 100)) ?>...</p>
                                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.8rem; color: var(--text-500); border-top: 1px solid var(--border); padding-top: 12px; margin-top: auto;">
                                            <span style="color: #ef4444; font-weight: 600;"><i data-lucide="calendar" style="width: 14px; height: 14px; display: inline; vertical-align: middle; margin-right: 4px;"></i> <?= date('d M Y', strtotime($s['application_deadline'])) ?></span>
                                            <a href="<?= url('/scholarships/' . e($s['slug'])) ?>" class="btn btn-ghost btn-sm" style="padding: 4px 8px; font-size: 0.75rem;">View Opportunity</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="scholarships-cta reveal">
                    <a href="<?= url('register') ?>" class="btn btn-primary">Create Profile to See Your Matches</a>
                </div>
            </div>
        </section>

        <!-- ============================================
             SECURITY / TRUST SECTION
             ============================================ -->
        <section class="section section-alt" id="trust">
            <div class="container">
                <div class="section-header reveal">
                    <span class="section-label">
                        <i data-lucide="shield" style="width:14px;height:14px"></i>
                        Privacy & Security
                    </span>
                    <h2>Your Information Is Yours</h2>
                    <p>We take your privacy seriously. Your profile data is used only for scholarship matching and alerts.</p>
                </div>

                <div class="trust-features">
                    <div class="trust-feature reveal">
                        <div class="trust-feature-icon"><i data-lucide="lock"></i></div>
                        <div class="trust-feature-text">
                            <h4>Secure Account</h4>
                            <p>Your account is protected with standard security practices.</p>
                        </div>
                    </div>
                    <div class="trust-feature reveal reveal-delay-1">
                        <div class="trust-feature-icon"><i data-lucide="eye-off"></i></div>
                        <div class="trust-feature-text">
                            <h4>Protected Profile</h4>
                            <p>Your personal information is not publicly exposed.</p>
                        </div>
                    </div>
                    <div class="trust-feature reveal reveal-delay-2">
                        <div class="trust-feature-icon"><i data-lucide="bell-off"></i></div>
                        <div class="trust-feature-text">
                            <h4>Controlled Notifications</h4>
                            <p>Choose which alerts you receive and how you receive them.</p>
                        </div>
                    </div>
                    <div class="trust-feature reveal reveal-delay-3">
                        <div class="trust-feature-icon"><i data-lucide="credit-card"></i></div>
                        <div class="trust-feature-text">
                            <h4>Clear Subscription</h4>
                            <p>Transparent billing with easy cancellation anytime.</p>
                        </div>
                    </div>
                    <div class="trust-feature reveal reveal-delay-4">
                        <div class="trust-feature-icon"><i data-lucide="log-out"></i></div>
                        <div class="trust-feature-text">
                            <h4>Easy Unsubscribe</h4>
                            <p>Stop WhatsApp or email notifications with a single click.</p>
                        </div>
                    </div>
                    <div class="trust-feature reveal reveal-delay-5">
                        <div class="trust-feature-icon"><i data-lucide="user-x"></i></div>
                        <div class="trust-feature-text">
                            <h4>Data Control</h4>
                            <p>Request deletion of your account and data at any time.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============================================
             PRICING PREVIEW
             ============================================ -->
        <section class="section" id="pricing">
            <div class="container">
                <div class="section-header reveal">
                    <span class="section-label">
                        <i data-lucide="tag" style="width:14px;height:14px"></i>
                        Pricing
                    </span>
                    <h2>Simple, Transparent Pricing</h2>
                    <p>Start free. Upgrade when you're ready for personalized alerts and matching.</p>
                </div>

                <div class="pricing-grid">
                    <div class="pricing-card reveal">
                        <div class="pricing-plan-name">Free</div>
                        <div class="pricing-price">
                            <span class="pricing-free-label">Free</span>
                        </div>
                        <div class="pricing-features">
                            <div class="pricing-feature">
                                <i data-lucide="check"></i>
                                Basic profile creation
                            </div>
                            <div class="pricing-feature">
                                <i data-lucide="check"></i>
                                Limited scholarship matches
                            </div>
                            <div class="pricing-feature">
                                <i data-lucide="check"></i>
                                Website dashboard access
                            </div>
                            <div class="pricing-feature disabled">
                                <i data-lucide="minus"></i>
                                WhatsApp alerts
                            </div>
                            <div class="pricing-feature disabled">
                                <i data-lucide="minus"></i>
                                Email alerts
                            </div>
                            <div class="pricing-feature disabled">
                                <i data-lucide="minus"></i>
                                Deadline reminders
                            </div>
                            <div class="pricing-feature disabled">
                                <i data-lucide="minus"></i>
                                Application tracking
                            </div>
                        </div>
                        <a href="<?= url('register') ?>" class="btn btn-secondary">Get Started</a>
                    </div>

                    <div class="pricing-card featured reveal reveal-delay-2">
                        <span class="pricing-popular">Most Popular</span>
                        <div class="pricing-plan-name">Premium</div>
                        <div class="pricing-price">
                            <span class="pricing-amount">PKR 1,499</span>
                            <span class="pricing-period"> / month</span>
                        </div>
                        <div class="pricing-cancel">Cancel anytime</div>
                        <div class="pricing-features">
                            <div class="pricing-feature">
                                <i data-lucide="check"></i>
                                Personalized scholarship matching
                            </div>
                            <div class="pricing-feature">
                                <i data-lucide="check"></i>
                                WhatsApp alerts
                            </div>
                            <div class="pricing-feature">
                                <i data-lucide="check"></i>
                                Email alerts
                            </div>
                            <div class="pricing-feature">
                                <i data-lucide="check"></i>
                                Daily matching updates
                            </div>
                            <div class="pricing-feature">
                                <i data-lucide="check"></i>
                                Deadline reminders
                            </div>
                            <div class="pricing-feature">
                                <i data-lucide="check"></i>
                                Detailed scholarship information
                            </div>
                            <div class="pricing-feature">
                                <i data-lucide="check"></i>
                                Application tracking
                            </div>
                            <div class="pricing-feature">
                                <i data-lucide="check"></i>
                                Document checklist
                            </div>
                        </div>
                        <a href="<?= url('register') ?>" class="btn btn-primary">Start Premium</a>
                    </div>
                </div>

                <p class="pricing-note reveal">Pricing shown in PKR. Other currencies available based on your region during registration.</p>
            </div>
        </section>

        <!-- ============================================
             TESTIMONIALS
             ============================================ -->
        <section class="section section-alt" id="testimonials">
            <div class="container">
                <div class="section-header reveal">
                    <span class="section-label">
                        <i data-lucide="message-square" style="width:14px;height:14px"></i>
                        Testimonials
                    </span>
                    <h2>What Students Say</h2>
                    <p>Real feedback from students using ScholarMatch.</p>
                </div>

                <div class="testimonials-grid">
                    <div class="testimonial-card reveal">
                        <div class="testimonial-stars">
                            <i data-lucide="star"></i>
                            <i data-lucide="star"></i>
                            <i data-lucide="star"></i>
                            <i data-lucide="star"></i>
                            <i data-lucide="star"></i>
                        </div>
                        <p class="testimonial-text">"I used to spend hours searching for scholarships. Now I just check my WhatsApp and see relevant opportunities. The matching is really helpful."</p>
                        <div class="testimonial-author">
                            <div class="testimonial-avatar">AH</div>
                            <div>
                                <div class="testimonial-name">Ahmed H.</div>
                                <div class="testimonial-role">Computer Science, Lahore</div>
                            </div>
                        </div>
                    </div>

                    <div class="testimonial-card reveal reveal-delay-1">
                        <div class="testimonial-stars">
                            <i data-lucide="star"></i>
                            <i data-lucide="star"></i>
                            <i data-lucide="star"></i>
                            <i data-lucide="star"></i>
                            <i data-lucide="star"></i>
                        </div>
                        <p class="testimonial-text">"The document checklist feature saved me so much time. I knew exactly what to prepare before the deadline."</p>
                        <div class="testimonial-author">
                            <div class="testimonial-avatar">FS</div>
                            <div>
                                <div class="testimonial-name">Fatima S.</div>
                                <div class="testimonial-role">Biotechnology, Karachi</div>
                            </div>
                        </div>
                    </div>

                    <div class="testimonial-card reveal reveal-delay-2">
                        <div class="testimonial-stars">
                            <i data-lucide="star"></i>
                            <i data-lucide="star"></i>
                            <i data-lucide="star"></i>
                            <i data-lucide="star"></i>
                            <i data-lucide="star"></i>
                        </div>
                        <p class="testimonial-text">"I missed a scholarship deadline last year because I didn't know about it. With ScholarMatch alerts, that won't happen again."</p>
                        <div class="testimonial-author">
                            <div class="testimonial-avatar">MR</div>
                            <div>
                                <div class="testimonial-name">Muhammad R.</div>
                                <div class="testimonial-role">Electrical Engineering, Islamabad</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============================================
             FAQ SECTION
             ============================================ -->
        <section class="section" id="faq">
            <div class="container">
                <div class="section-header reveal">
                    <span class="section-label">
                        <i data-lucide="help-circle" style="width:14px;height:14px"></i>
                        FAQ
                    </span>
                    <h2>Frequently Asked Questions</h2>
                    <p>Answers to common questions about ScholarMatch.</p>
                </div>

                <div class="faq-list" role="list">
                    <div class="faq-item reveal" role="listitem">
                        <button class="faq-question" aria-expanded="false">
                            What is this platform?
                            <i data-lucide="chevron-down" class="faq-icon"></i>
                        </button>
                        <div class="faq-answer" role="region">
                            <div class="faq-answer-inner">
                                <p>ScholarMatch is a scholarship discovery platform that matches scholarship opportunities to your academic profile. You create a profile with your education details, and the system finds relevant scholarships and sends you alerts through WhatsApp and email.</p>
                            </div>
                        </div>
                    </div>

                    <div class="faq-item reveal reveal-delay-1" role="listitem">
                        <button class="faq-question" aria-expanded="false">
                            How does scholarship matching work?
                            <i data-lucide="chevron-down" class="faq-icon"></i>
                        </button>
                        <div class="faq-answer" role="region">
                            <div class="faq-answer-inner">
                                <p>When you create your profile, you provide information like your field of study, degree level, CGPA, country, and preferences. Our system compares these details against scholarship eligibility criteria and shows you opportunities that match your profile, along with a match percentage.</p>
                            </div>
                        </div>
                    </div>

                    <div class="faq-item reveal reveal-delay-2" role="listitem">
                        <button class="faq-question" aria-expanded="false">
                            How do I receive WhatsApp alerts?
                            <i data-lucide="chevron-down" class="faq-icon"></i>
                        </button>
                        <div class="faq-answer" role="region">
                            <div class="faq-answer-inner">
                                <p>After subscribing to a premium plan, you can connect your WhatsApp number in your dashboard settings. When new scholarships match your profile, you'll receive a concise notification with key details and a link to view the full information on the website.</p>
                            </div>
                        </div>
                    </div>

                    <div class="faq-item reveal" role="listitem">
                        <button class="faq-question" aria-expanded="false">
                            Will I receive email alerts?
                            <i data-lucide="chevron-down" class="faq-icon"></i>
                        </button>
                        <div class="faq-answer" role="region">
                            <div class="faq-answer-inner">
                                <p>Yes, premium subscribers receive email notifications with details about matched scholarships, including match percentage, deadline, country, and funding type. You can manage your email alert preferences from your dashboard.</p>
                            </div>
                        </div>
                    </div>

                    <div class="faq-item reveal reveal-delay-1" role="listitem">
                        <button class="faq-question" aria-expanded="false">
                            Can I change my profile later?
                            <i data-lucide="chevron-down" class="faq-icon"></i>
                        </button>
                        <div class="faq-answer" role="region">
                            <div class="faq-answer-inner">
                                <p>Yes, you can update your profile information at any time from your dashboard. When you update your profile, your scholarship matches will be recalculated based on your new information.</p>
                            </div>
                        </div>
                    </div>

                    <div class="faq-item reveal reveal-delay-2" role="listitem">
                        <button class="faq-question" aria-expanded="false">
                            Does a match guarantee that I will receive the scholarship?
                            <i data-lucide="chevron-down" class="faq-icon"></i>
                        </button>
                        <div class="faq-answer" role="region">
                            <div class="faq-answer-inner">
                                <p><strong>No.</strong> The platform identifies opportunities that appear relevant based on available eligibility criteria. The scholarship provider makes the final eligibility and selection decision. We recommend always verifying eligibility directly with the scholarship provider before applying.</p>
                            </div>
                        </div>
                    </div>

                    <div class="faq-item reveal" role="listitem">
                        <button class="faq-question" aria-expanded="false">
                            Can I cancel my subscription?
                            <i data-lucide="chevron-down" class="faq-icon"></i>
                        </button>
                        <div class="faq-answer" role="region">
                            <div class="faq-answer-inner">
                                <p>Yes, you can cancel your premium subscription at any time from your account settings. After cancellation, you will retain premium features until the end of your current billing period, then your account will revert to the free plan.</p>
                            </div>
                        </div>
                    </div>

                    <div class="faq-item reveal reveal-delay-1" role="listitem">
                        <button class="faq-question" aria-expanded="false">
                            What happens when a scholarship deadline passes?
                            <i data-lucide="chevron-down" class="faq-icon"></i>
                        </button>
                        <div class="faq-answer" role="region">
                            <div class="faq-answer-inner">
                                <p>Scholarships with passed deadlines are marked as closed and are no longer shown in your active matches. You can still view them in your scholarship history for reference.</p>
                            </div>
                        </div>
                    </div>

                    <div class="faq-item reveal reveal-delay-2" role="listitem">
                        <button class="faq-question" aria-expanded="false">
                            Can I stop WhatsApp notifications?
                            <i data-lucide="chevron-down" class="faq-icon"></i>
                        </button>
                        <div class="faq-answer" role="region">
                            <div class="faq-answer-inner">
                                <p>Yes, you can disable WhatsApp notifications from your dashboard settings at any time. You can also reply "STOP" to any WhatsApp message to unsubscribe from WhatsApp alerts. Email alerts will continue unless you also disable those separately.</p>
                            </div>
                        </div>
                    </div>

                    <div class="faq-item reveal" role="listitem">
                        <button class="faq-question" aria-expanded="false">
                            Where does the scholarship information come from?
                            <i data-lucide="chevron-down" class="faq-icon"></i>
                        </button>
                        <div class="faq-answer" role="region">
                            <div class="faq-answer-inner">
                                <p>Scholarship information is collected from official sources including government scholarship portals, university websites, and recognized international scholarship programs. Where applicable, the source and verification status of each scholarship is displayed on the scholarship detail page.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============================================
             FINAL CTA
             ============================================ -->
        <section class="section">
            <div class="container">
                <div class="final-cta reveal">
                    <h2>Your Next Scholarship Could Be Closer Than You Think.</h2>
                    <p>Create your profile and start discovering scholarship opportunities that match your goals.</p>
                    <div class="final-cta-buttons">
                        <a href="<?= url('register') ?>" class="btn btn-primary">
                            <i data-lucide="user-plus" style="width:18px;height:18px"></i>
                            Create My Profile
                        </a>
                        <a href="#scholarships" class="btn btn-secondary">
                            Explore Scholarships
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- ============================================
         FOOTER
         ============================================ -->
    <footer class="footer" role="contentinfo">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <div class="footer-logo">
                        <div class="footer-logo-icon">
                            <i data-lucide="graduation-cap"></i>
                        </div>
                        <span class="footer-logo-text">ScholarMatch</span>
                    </div>
                    <p>Find scholarships that match your profile. Receive personalized alerts through WhatsApp and email.</p>
                </div>

                <div class="footer-col">
                    <h4>Product</h4>
                    <a href="#scholarships">Scholarships</a>
                    <a href="#how-it-works">How It Works</a>
                    <a href="#features">Features</a>
                    <a href="#pricing">Pricing</a>
                    <a href="#faq">FAQ</a>
                </div>

                <div class="footer-col">
                    <h4>Resources</h4>
                    <a href="#">Scholarship Guide</a>
                    <a href="#">Application Guide</a>
                    <a href="#">Student Resources</a>
                    <a href="#">Blog</a>
                </div>

                <div class="footer-col">
                    <h4>Company</h4>
                    <a href="#">About</a>
                    <a href="<?= url('contact') ?>">Contact</a>
                    <a href="#">Careers</a>
                </div>

                <div class="footer-col">
                    <h4>Legal</h4>
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                    <a href="#">Cookie Policy</a>
                    <a href="#">Refund Policy</a>
                    <a href="#">WhatsApp Policy</a>
                </div>
            </div>

            <div class="footer-bottom">
                <span>&copy; 2025 ScholarMatch. All rights reserved.</span>
                <span>
                    <a href="#" style="margin-left:16px;color:var(--text-400);transition:color 150ms" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='var(--text-400)'">Help Center</a>
                </span>
            </div>
        </div>
    </footer>

    <!-- ============================================
         JAVASCRIPT
         ============================================ -->
    <script src="<?= asset('assets/js/main.js') ?>"></script>
</body>
</html>