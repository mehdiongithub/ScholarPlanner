<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us — ScholarMatch</title>
    <meta name="description" content="Learn about ScholarMatch — a scholarship discovery platform that matches opportunities to your profile and sends personalized alerts through WhatsApp and email.">
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
        body{font-family:var(--font);font-size:15px;line-height:1.6;color:var(--text-600);background:var(--bg-white);-webkit-font-smoothing:antialiased;overflow-x:hidden}
        a{color:inherit;text-decoration:none}
        button{font-family:inherit;cursor:pointer;border:none;background:none}
        ul{list-style:none}
        img{max-width:100%;display:block}
        :focus-visible{outline:2px solid var(--primary-light);outline-offset:2px;border-radius:var(--radius-sm)}
        h1,h2,h3,h4{color:var(--text-900);line-height:1.25;font-weight:600}
        h1{font-size:clamp(1.75rem,4.5vw,2.75rem);letter-spacing:-0.03em;font-weight:700}
        h2{font-size:clamp(1.25rem,3vw,1.75rem);letter-spacing:-0.02em}
        h3{font-size:clamp(0.9375rem,2vw,1.125rem);letter-spacing:-0.015em}
        .container{width:100%;max-width:1200px;margin:0 auto;padding:0 clamp(16px,4vw,24px)}

        .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;font-size:0.9375rem;font-weight:500;padding:12px 24px;border-radius:var(--radius-md);transition:all 200ms ease;white-space:nowrap;min-height:44px;line-height:1.2}
        .btn-primary{background:var(--primary);color:#fff;box-shadow:0 1px 2px rgba(30,64,175,0.2)}
        .btn-primary:hover{background:var(--primary-dark);box-shadow:0 4px 12px rgba(30,64,175,0.3);transform:translateY(-1px)}
        .btn-secondary{background:var(--bg-white);color:var(--text-700);border:1px solid var(--border)}
        .btn-secondary:hover{background:var(--bg-50);border-color:var(--text-300)}
        .btn-sm{font-size:0.8125rem;padding:8px 16px;min-height:36px}
        .btn-outline-wa{background:transparent;color:var(--whatsapp-dark,#128C7E);border:1.5px solid rgba(37,211,102,0.4);font-weight:500}
        .btn-outline-wa:hover{background:rgba(37,211,102,0.06);border-color:#25D366}

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

        /* ===== SECTIONS ===== */
        .section{padding:clamp(48px,8vw,96px) 0}
        .section-alt{background:var(--bg-50)}
        .section-header{max-width:680px;margin-bottom:40px}
        .section-header.center{text-align:center;margin-left:auto;margin-right:auto}
        .section-header p{font-size:clamp(0.9375rem,1.5vw,1.0625rem);color:var(--text-500);margin-top:10px;line-height:1.7}
        .section-label{display:inline-flex;align-items:center;gap:6px;font-size:0.75rem;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;color:var(--primary-light);margin-bottom:12px}
        .section-label i{width:14px;height:14px}

        /* ===== HERO ===== */
        .about-hero{padding:calc(var(--header-height) + clamp(40px,7vw,72px)) 0 clamp(48px,8vw,80px);background:linear-gradient(180deg,var(--primary-50) 0%,var(--bg-white) 100%);position:relative;overflow:hidden}
        .about-hero::before{content:'';position:absolute;top:5%;right:-8%;width:clamp(280px,35vw,500px);height:clamp(280px,35vw,500px);border-radius:50%;background:var(--primary-100);opacity:0.25;pointer-events:none}
        .about-hero::after{content:'';position:absolute;bottom:-15%;left:10%;width:clamp(150px,20vw,250px);height:clamp(150px,20vw,250px);border-radius:50%;background:var(--primary-50);opacity:0.4;pointer-events:none}

        .hero-grid{display:grid;grid-template-columns:1fr;gap:40px;align-items:center;position:relative;z-index:1}
        @media(min-width:768px){.hero-grid{grid-template-columns:1.1fr 0.9fr;gap:clamp(40px,6vw,80px)}}

        .hero-text p{font-size:clamp(0.9375rem,1.5vw,1.0625rem);color:var(--text-500);line-height:1.75;margin-bottom:16px;max-width:58ch}
        .hero-text p:last-of-type{margin-bottom:28px}

        .hero-actions{display:flex;flex-wrap:wrap;gap:12px}

        /* Hero visual */
        .hero-visual{display:flex;justify-content:center}
        .hero-card-stack{position:relative;width:100%;max-width:420px}
        .hero-card-main{background:var(--bg-white);border:1px solid var(--border);border-radius:var(--radius-2xl);padding:24px;box-shadow:var(--shadow-lg);position:relative;z-index:2}
        .hero-card-bg{position:absolute;top:16px;left:16px;right:-16px;bottom:-16px;background:var(--primary-100);border-radius:var(--radius-2xl);opacity:0.4;z-index:1}
        .hero-card-header{display:flex;align-items:center;gap:12px;margin-bottom:20px}
        .hero-card-logo{width:42px;height:42px;background:var(--primary);border-radius:var(--radius-lg);display:flex;align-items:center;justify-content:center;color:#fff;flex-shrink:0}
        .hero-card-logo i{width:20px;height:20px}
        .hero-card-name{font-size:1.0625rem;font-weight:700;color:var(--text-900)}
        .hero-card-sub{font-size:0.8125rem;color:var(--text-400)}

        .hero-card-metrics{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px}
        .hero-metric{background:var(--bg-50);border-radius:var(--radius-lg);padding:16px 12px;text-align:center}
        .hero-metric-val{font-size:1.375rem;font-weight:700;color:var(--text-900);line-height:1.2}
        .hero-metric-lbl{font-size:0.6875rem;color:var(--text-400);margin-top:2px;text-transform:uppercase;letter-spacing:0.04em;font-weight:500}
        .hero-card-note{text-align:center;font-size:0.6875rem;color:var(--text-300);font-style:italic}

        /* Floating mini-cards */
        .hero-float{position:absolute;z-index:3;border-radius:var(--radius-lg);box-shadow:var(--shadow-lg);background:var(--bg-white);border:1px solid var(--border);padding:10px 14px;display:flex;align-items:center;gap:8px;font-size:0.75rem;font-weight:500;color:var(--text-700);white-space:nowrap}
        .hero-float-1{top:20px;right:-12px;animation:floatA 4s ease-in-out infinite}
        .hero-float-2{bottom:40px;left:-16px;animation:floatB 5s ease-in-out infinite}
        .hero-float-icon{width:28px;height:28px;border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;flex-shrink:0}
        .hero-float-icon.green{background:var(--accent-50);color:var(--accent)}
        .hero-float-icon.blue{background:var(--primary-50);color:var(--primary-light)}
        .hero-float-icon i{width:14px;height:14px}

        @keyframes floatA{0%,100%{transform:translateY(0)}50%{transform:translateY(-6px)}}
        @keyframes floatB{0%,100%{transform:translateY(0)}50%{transform:translateY(-8px)}}

        @media(max-width:400px){.hero-float{display:none}}

        /* ===== PROBLEM STRIP ===== */
        .problem-strip{background:var(--text-900);padding:clamp(28px,4vw,48px) 0;position:relative;overflow:hidden}
        .problem-strip::before{content:'';position:absolute;inset:0;background:linear-gradient(135deg,rgba(30,64,175,0.15),transparent);pointer-events:none}
        .strip-grid{display:grid;grid-template-columns:1fr;gap:24px;position:relative;z-index:1}
        @media(min-width:768px){.strip-grid{grid-template-columns:1fr 1fr 1fr}}
        .strip-item{text-align:center;padding:0 16px}
        .strip-num{font-size:2rem;font-weight:700;color:var(--primary-200);line-height:1;margin-bottom:6px}
        .strip-label{font-size:0.875rem;color:rgba(255,255,255,0.7);line-height:1.5}

        /* ===== WHAT WE DO ===== */
        .do-grid{display:grid;grid-template-columns:1fr;gap:20px}
        @media(min-width:640px){.do-grid{grid-template-columns:repeat(3,1fr)}}
        .do-card{padding:28px 24px;border-radius:var(--radius-xl);border:1px solid var(--border-light);background:var(--bg-white);transition:all 200ms}
        .do-card:hover{border-color:var(--primary-200);box-shadow:var(--shadow-sm)}
        .do-icon{width:48px;height:48px;border-radius:var(--radius-lg);background:var(--primary-50);color:var(--primary-light);display:flex;align-items:center;justify-content:center;margin-bottom:16px}
        .do-icon i{width:24px;height:24px}
        .do-card h3{margin-bottom:8px}
        .do-card p{font-size:0.875rem;color:var(--text-500);line-height:1.65}

        /* ===== STORY ===== */
        .story-grid{display:grid;grid-template-columns:1fr;gap:40px;align-items:start}
        @media(min-width:768px){.story-grid{grid-template-columns:1fr 1fr;gap:clamp(40px,6vw,72px)}}
        .story-text p{font-size:0.9375rem;color:var(--text-600);line-height:1.75;margin-bottom:16px}
        .story-text p:last-child{margin-bottom:0}
        .story-text p strong{color:var(--text-900);font-weight:600}
        .story-visual{display:flex;flex-direction:column;gap:16px}
        .story-point{display:flex;align-items:flex-start;gap:14px;padding:18px;background:var(--bg-white);border:1px solid var(--border-light);border-radius:var(--radius-lg);transition:all 200ms}
        .story-point:hover{border-color:var(--primary-200)}
        .story-point-icon{width:36px;height:36px;border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;flex-shrink:0}
        .story-point-icon.blue{background:var(--primary-50);color:var(--primary-light)}
        .story-point-icon.green{background:var(--accent-50);color:var(--accent)}
        .story-point-icon.amber{background:#fef3c7;color:#d97706}
        .story-point-icon i{width:18px;height:18px}
        .story-point h4{font-size:0.875rem;margin-bottom:2px}
        .story-point p{font-size:0.8125rem;color:var(--text-500);line-height:1.5}

        /* ===== TRANSPARENCY ===== */
        .trans-grid{display:grid;grid-template-columns:1fr;gap:16px;max-width:860px;margin:0 auto}
        @media(min-width:640px){.trans-grid{grid-template-columns:1fr 1fr}}
        .trans-card{display:flex;align-items:flex-start;gap:14px;padding:20px;border-radius:var(--radius-xl);background:var(--bg-white);border:1px solid #fecaca;transition:all 200ms}
        .trans-card:hover{box-shadow:var(--shadow-xs)}
        .trans-x{width:32px;height:32px;border-radius:50%;background:#fef2f2;color:#dc2626;display:flex;align-items:center;justify-content:center;flex-shrink:0}
        .trans-x i{width:16px;height:16px}
        .trans-card h4{font-size:0.9375rem;color:#991b1b;margin-bottom:4px}
        .trans-card p{font-size:0.8125rem;color:#7f1d1d;line-height:1.55}

        /* ===== VALUES ===== */
        .val-grid{display:grid;grid-template-columns:1fr;gap:16px}
        @media(min-width:640px){.val-grid{grid-template-columns:repeat(2,1fr)}}
        .val-item{display:flex;align-items:flex-start;gap:14px;padding:20px;border-radius:var(--radius-lg);background:var(--bg-white);border:1px solid var(--border-light);transition:all 200ms}
        .val-item:hover{border-color:var(--primary-200)}
        .val-num{width:32px;height:32px;border-radius:50%;background:var(--primary-50);color:var(--primary);display:flex;align-items:center;justify-content:center;font-size:0.8125rem;font-weight:700;flex-shrink:0}
        .val-item h4{font-size:0.9375rem;margin-bottom:4px}
        .val-item p{font-size:0.8125rem;color:var(--text-500);line-height:1.55}

        /* ===== WHO IS THIS FOR ===== */
        .for-grid{display:grid;grid-template-columns:1fr;gap:16px}
        @media(min-width:640px){.for-grid{grid-template-columns:repeat(2,1fr)}}
        @media(min-width:960px){.for-grid{grid-template-columns:repeat(4,1fr)}}
        .for-card{text-align:center;padding:28px 16px;border-radius:var(--radius-xl);border:1px solid var(--border-light);background:var(--bg-white);transition:all 200ms}
        .for-card:hover{border-color:var(--primary-200);box-shadow:var(--shadow-xs)}
        .for-icon{width:48px;height:48px;border-radius:50%;background:var(--primary-50);color:var(--primary-light);display:flex;align-items:center;justify-content:center;margin:0 auto 14px}
        .for-icon i{width:22px;height:22px}
        .for-card h4{font-size:0.875rem;margin-bottom:6px}
        .for-card p{font-size:0.8125rem;color:var(--text-500);line-height:1.55;margin:0 auto}

        /* ===== PROCESS ===== */
        .proc-grid{display:grid;grid-template-columns:1fr;gap:24px;position:relative}
        @media(min-width:768px){.proc-grid{grid-template-columns:repeat(4,1fr);gap:20px}}
        .proc-card{text-align:center;position:relative}
        .proc-num{width:48px;height:48px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.125rem;font-weight:700;margin:0 auto 14px}
        .proc-card h3{font-size:0.9375rem;margin-bottom:6px}
        .proc-card p{font-size:0.8125rem;color:var(--text-500);margin:0 auto;line-height:1.55;max-width:22ch}
        @media(min-width:768px){.proc-card:not(:last-child)::after{content:'';position:absolute;top:24px;right:-10px;width:20px;height:2px;background:var(--primary-200)}}

        /* ===== TEAM ===== */
        .team-grid{display:grid;grid-template-columns:1fr;gap:20px}
        @media(min-width:640px){.team-grid{grid-template-columns:repeat(2,1fr)}}
        @media(min-width:960px){.team-grid{grid-template-columns:repeat(3,1fr)}}
        .team-card{text-align:center;padding:32px 20px;border-radius:var(--radius-xl);border:1px solid var(--border-light);background:var(--bg-white);transition:all 200ms}
        .team-card:hover{box-shadow:var(--shadow-sm)}
        .team-avatar{width:72px;height:72px;border-radius:50%;background:var(--bg-100);display:flex;align-items:center;justify-content:center;margin:0 auto 14px;font-size:1.25rem;font-weight:600;color:var(--text-300);border:2px dashed var(--border)}
        .team-card h4{font-size:0.9375rem;margin-bottom:2px}
        .team-role{font-size:0.8125rem;color:var(--text-400)}

        /* ===== CTA ===== */
        .cta-block{background:linear-gradient(135deg,var(--primary-dark) 0%,var(--primary) 100%);border-radius:var(--radius-2xl);padding:clamp(40px,6vw,72px) clamp(24px,5vw,48px);text-align:center;position:relative;overflow:hidden}
        .cta-block::before{content:'';position:absolute;top:-50%;right:-15%;width:350px;height:350px;border-radius:50%;background:rgba(255,255,255,0.04);pointer-events:none}
        .cta-block h2{color:#fff;margin-bottom:10px;position:relative}
        .cta-block p{color:rgba(255,255,255,0.8);font-size:clamp(0.9375rem,1.5vw,1.0625rem);margin:0 auto 28px;max-width:480px;position:relative}
        .cta-block .btn{position:relative}
        .cta-block .btn-primary{background:#fff;color:var(--primary)}
        .cta-block .btn-primary:hover{background:var(--primary-50)}

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
        .reveal-delay-3{transition-delay:300ms}
        @media(prefers-reduced-motion:reduce){.reveal{opacity:1;transform:none;transition:none}html{scroll-behavior:auto}*,*::before,*::after{transition-duration:0.01ms!important;animation-duration:0.01ms!important}}
        @media(max-width:320px){.hero-actions .btn{width:100%}.do-grid,.trans-grid,.val-grid,.for-grid,.team-grid{grid-template-columns:1fr}}
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
                <a href="/about" class="active">About</a>
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
            <a href="/about" class="active">About</a>
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
                    <span class="current">About</span>
                </nav>
            </div>
        </div>

        <!-- ===== HERO ===== -->
        <section class="about-hero">
            <div class="container">
                <div class="hero-grid">
                    <div class="hero-text reveal">
                        <div class="section-label"><i data-lucide="info"></i> About ScholarMatch</div>
                        <h1>We Built This Because Searching for Scholarships Shouldn't Be This Hard.</h1>
                        <p>ScholarMatch is a scholarship discovery platform. Students create a profile, we match them with relevant scholarship opportunities, and we send personalized alerts through WhatsApp and email.</p>
                        <p>We are not a scholarship provider. We do not award funding, guarantee acceptance, or process applications. We help you find opportunities — you apply directly through the official source.</p>
                        <div class="hero-actions">
                            <a href="/register" class="btn btn-primary">Create Your Profile</a>
                            <a href="/contact" class="btn btn-secondary">Get in Touch</a>
                        </div>
                    </div>

                    <div class="hero-visual reveal reveal-delay-2">
                        <div class="hero-card-stack">
                            <div class="hero-card-bg"></div>

                            <!-- Floating cards -->
                            <div class="hero-float hero-float-1">
                                <div class="hero-float-icon green"><i data-lucide="check-circle-2"></i></div>
                                94% Match Found
                            </div>
                            <div class="hero-float hero-float-2">
                                <div class="hero-float-icon blue"><i data-lucide="message-circle"></i></div>
                                WhatsApp Alert Sent
                            </div>

                            <div class="hero-card-main">
                                <div class="hero-card-header">
                                    <div class="hero-card-logo"><i data-lucide="graduation-cap"></i></div>
                                    <div>
                                        <div class="hero-card-name">ScholarMatch</div>
                                        <div class="hero-card-sub">Scholarship Discovery Platform</div>
                                    </div>
                                </div>
                                <div class="hero-card-metrics">
                                    <div class="hero-metric">
                                        <div class="hero-metric-val">—</div>
                                        <div class="hero-metric-lbl">Scholarships</div>
                                    </div>
                                    <div class="hero-metric">
                                        <div class="hero-metric-val">—</div>
                                        <div class="hero-metric-lbl">Countries</div>
                                    </div>
                                    <div class="hero-metric">
                                        <div class="hero-metric-val">—</div>
                                        <div class="hero-metric-lbl">Study Levels</div>
                                    </div>
                                    <div class="hero-metric">
                                        <div class="hero-metric-val">—</div>
                                        <div class="hero-metric-lbl">Fields</div>
                                    </div>
                                </div>
                                <p class="hero-card-note">Statistics will be updated as the platform grows.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== PROBLEM STRIP ===== -->
        <section class="problem-strip">
            <div class="container">
                <div class="strip-grid">
                    <div class="strip-item reveal">
                        <div class="strip-num">10+</div>
                        <div class="strip-label">Websites students check<br>for scholarships</div>
                    </div>
                    <div class="strip-item reveal reveal-delay-1">
                        <div class="strip-num">Hours</div>
                        <div class="strip-label">Spent per week<br>searching and filtering</div>
                    </div>
                    <div class="strip-item reveal reveal-delay-2">
                        <div class="strip-num">Missed</div>
                        <div class="strip-label">Deadlines that pass<br>before students find out</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== WHAT WE DO ===== -->
        <section class="section">
            <div class="container">
                <div class="section-header center reveal">
                    <div class="section-label"><i data-lucide="target"></i> What We Do</div>
                    <h2>Three Things, Done Well</h2>
                    <p>Everything we build serves one purpose: help students discover and act on scholarship opportunities more efficiently.</p>
                </div>
                <div class="do-grid">
                    <div class="do-card reveal">
                        <div class="do-icon"><i data-lucide="search"></i></div>
                        <h3>Discover</h3>
                        <p>We collect scholarship information from official sources — government portals, university websites, recognized organizations — and organize it into a searchable, filterable database.</p>
                    </div>
                    <div class="do-card reveal reveal-delay-1">
                        <div class="do-icon"><i data-lucide="bell"></i></div>
                        <h3>Notify</h3>
                        <p>Instead of students checking websites repeatedly, we send concise alerts through WhatsApp and email when new scholarships match their profile. Short alerts where students already are.</p>
                    </div>
                    <div class="do-card reveal reveal-delay-2">
                        <div class="do-icon"><i data-lucide="file-text"></i></div>
                        <h3>Inform</h3>
                        <p>Each scholarship page presents eligibility, benefits, documents, deadlines, and the official application link in a structured format — so students can prepare and apply with clarity.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== OUR STORY ===== -->
        <section class="section section-alt">
            <div class="container">
                <div class="story-grid">
                    <div class="story-text reveal">
                        <div class="section-label"><i data-lucide="book-open"></i> Our Story</div>
                        <h2>Why We Built This</h2>
                        <p>The idea came from a simple observation: <strong>students in Pakistan, India, and Bangladesh spend an enormous amount of time searching for scholarships</strong> — scrolling through WhatsApp groups, checking random websites, asking friends, and often finding out about opportunities after the deadline has passed.</p>
                        <p>We asked: what if a student could describe their education, field, and goals once — and then have relevant opportunities come to them instead?</p>
                        <p>That's what ScholarMatch does. It's not a revolutionary concept. It's a <strong>practical one</strong>. Take the information that's already out there, structure it properly, match it to individual profiles, and deliver it through channels students actually use — WhatsApp and email.</p>
                        <p>We're not trying to replace scholarship providers. We're trying to <strong>connect students with them more efficiently</strong>. Every scholarship on our platform links directly to the official application source.</p>
                    </div>
                    <div class="story-visual reveal reveal-delay-2">
                        <div class="story-point">
                            <div class="story-point-icon blue"><i data-lucide="eye"></i></div>
                            <div>
                                <h4>Observed the Problem</h4>
                                <p>Students searching across dozens of sources, missing deadlines, unsure about eligibility.</p>
                            </div>
                        </div>
                        <div class="story-point">
                            <div class="story-point-icon green"><i data-lucide="lightbulb"></i></div>
                            <div>
                                <h4>Designed a Solution</h4>
                                <p>Profile-based matching with alerts delivered through WhatsApp and email.</p>
                            </div>
                        </div>
                        <div class="story-point">
                            <div class="story-point-icon amber"><i data-lucide="code-2"></i></div>
                            <div>
                                <h4>Built the Platform</h4>
                                <p>Structured scholarship data, matching engine, notification system, and clean user experience.</p>
                            </div>
                        </div>
                        <div class="story-point">
                            <div class="story-point-icon blue"><i data-lucide="rocket"></i></div>
                            <div>
                                <h4>Launched for Students</h4>
                                <p>Making it available to students who need a better way to find scholarships.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== TRANSPARENCY ===== -->
        <section class="section">
            <div class="container">
                <div class="section-header center reveal">
                    <div class="section-label"><i data-lucide="shield-alert"></i> Transparency</div>
                    <h2>What We Don't Do</h2>
                    <p>We believe being honest about what we are not is just as important as explaining what we are.</p>
                </div>
                <div class="trans-grid">
                    <div class="trans-card reveal">
                        <div class="trans-x"><i data-lucide="x"></i></div>
                        <div>
                            <h4>We Don't Award Scholarships</h4>
                            <p>All scholarships are offered by their respective providers. We only direct you to the official source.</p>
                        </div>
                    </div>
                    <div class="trans-card reveal reveal-delay-1">
                        <div class="trans-x"><i data-lucide="x"></i></div>
                        <div>
                            <h4>We Don't Guarantee Acceptance</h4>
                            <p>Match scores show alignment with stated criteria. The provider makes all final decisions.</p>
                        </div>
                    </div>
                    <div class="trans-card reveal">
                        <div class="trans-x"><i data-lucide="x"></i></div>
                        <div>
                            <h4>We Don't Process Applications</h4>
                            <p>Every scholarship links to the official application. You apply directly through the provider.</p>
                        </div>
                    </div>
                    <div class="trans-card reveal reveal-delay-1">
                        <div class="trans-x"><i data-lucide="x"></i></div>
                        <div>
                            <h4>We Don't Make Unverified Claims</h4>
                            <p>Source and verification status is shown where available. We don't claim all data is verified unless it is.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== VALUES ===== -->
        <section class="section section-alt">
            <div class="container">
                <div class="section-header center reveal">
                    <div class="section-label"><i data-lucide="heart"></i> Principles</div>
                    <h2>What Guides Our Decisions</h2>
                </div>
                <div class="val-grid">
                    <div class="val-item reveal">
                        <div class="val-num">1</div>
                        <div>
                            <h4>Accuracy Over Speed</h4>
                            <p>We prefer correct information late over incorrect information early. Every listing is checked before publishing.</p>
                        </div>
                    </div>
                    <div class="val-item reveal reveal-delay-1">
                        <div class="val-num">2</div>
                        <div>
                            <h4>Student Privacy</h4>
                            <p>Profile data is used only for matching and alerts. We do not sell, share, or publicly display personal information.</p>
                        </div>
                    </div>
                    <div class="val-item reveal">
                        <div class="val-num">3</div>
                        <div>
                            <h4>Honest Communication</h4>
                            <p>We don't overpromise. If a match is uncertain, we say so. Trust is built through honesty, not marketing.</p>
                        </div>
                    </div>
                    <div class="val-item reveal reveal-delay-1">
                        <div class="val-num">4</div>
                        <div>
                            <h4>Accessible Design</h4>
                            <p>The platform works across devices, screen sizes, and connection speeds — because students access the internet differently.</p>
                        </div>
                    </div>
                    <div class="val-item reveal">
                        <div class="val-num">5</div>
                        <div>
                            <h4>Official Sources Always</h4>
                            <p>We always direct students to the original provider for applications. We never replace the official channel.</p>
                        </div>
                    </div>
                    <div class="val-item reveal reveal-delay-1">
                        <div class="val-num">6</div>
                        <div>
                            <h4>Continuous Improvement</h4>
                            <p>We build based on real student feedback, not assumptions about what students need.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== WHO IS THIS FOR ===== -->
        <section class="section">
            <div class="container">
                <div class="section-header center reveal">
                    <div class="section-label"><i data-lucide="users"></i> Audience</div>
                    <h2>Who Is ScholarMatch For?</h2>
                    <p>Built primarily for students in South Asia, but useful for any student looking for structured scholarship discovery.</p>
                </div>
                <div class="for-grid">
                    <div class="for-card reveal">
                        <div class="for-icon"><i data-lucide="graduation-cap"></i></div>
                        <h4>Undergraduate Students</h4>
                        <p>Looking for bachelor's degree funding locally or internationally.</p>
                    </div>
                    <div class="for-card reveal reveal-delay-1">
                        <div class="for-icon"><i data-lucide="book-open"></i></div>
                        <h4>Graduate Students</h4>
                        <p>Seeking master's or PhD scholarships that match their field and profile.</p>
                    </div>
                    <div class="for-card reveal reveal-delay-2">
                        <div class="for-icon"><i data-lucide="briefcase"></i></div>
                        <h4>Working Professionals</h4>
                        <p>Exploring funded opportunities to advance their education and career.</p>
                    </div>
                    <div class="for-card reveal reveal-delay-3">
                        <div class="for-icon"><i data-lucide="globe"></i></div>
                        <h4>International Students</h4>
                        <p>From Pakistan, India, Bangladesh, and other countries seeking study abroad funding.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== HOW WE OPERATE ===== -->
        <section class="section section-alt">
            <div class="container">
                <div class="section-header center reveal">
                    <div class="section-label"><i data-lucide="settings-2"></i> Process</div>
                    <h2>How We Operate</h2>
                    <p>From source to your screen — how scholarships flow through ScholarMatch.</p>
                </div>
                <div class="proc-grid">
                    <div class="proc-card reveal">
                        <div class="proc-num">1</div>
                        <h3>Research</h3>
                        <p>We identify opportunities from official government portals, university websites, and recognized organizations.</p>
                    </div>
                    <div class="proc-card reveal reveal-delay-1">
                        <div class="proc-num">2</div>
                        <h3>Structure</h3>
                        <p>Each scholarship is organized into a consistent format — eligibility, benefits, documents, deadlines.</p>
                    </div>
                    <div class="proc-card reveal reveal-delay-2">
                        <div class="proc-num">3</div>
                        <h3>Match</h3>
                        <p>Structured data is compared against student profiles to generate relevance scores and feeds.</p>
                    </div>
                    <div class="proc-card reveal">
                        <div class="proc-num">4</div>
                        <h3>Alert</h3>
                        <p>Matched scholarships are delivered via WhatsApp and email with links to full details.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== TEAM ===== -->
        <section class="section">
            <div class="container">
                <div class="section-header center reveal">
                    <div class="section-label"><i data-lucide="users"></i> Team</div>
                    <h2>The People Behind This</h2>
                    <p>A small, focused team building something practical for students.</p>
                </div>
                <div class="team-grid">
                    <div class="team-card reveal">
                        <div class="team-avatar">—</div>
                        <h4>Position Open</h4>
                        <p class="team-role">Founder & Lead</p>
                    </div>
                    <div class="team-card reveal reveal-delay-1">
                        <div class="team-avatar">—</div>
                        <h4>Position Open</h4>
                        <p class="team-role">Content & Research</p>
                    </div>
                    <div class="team-card reveal reveal-delay-2">
                        <div class="team-avatar">—</div>
                        <h4>Position Open</h4>
                        <p class="team-role">Engineering</p>
                    </div>
                </div>
                <p style="text-align:center;font-size:0.8125rem;color:var(--text-400);margin-top:24px" class="reveal">
                    Team profiles will be added as the team grows.
                </p>
            </div>
        </section>

        <!-- ===== CTA ===== -->
        <section class="section">
            <div class="container">
                <div class="cta-block reveal">
                    <h2>Have a Question or Suggestion?</h2>
                    <p>We're a small team that reads every message. Whether you're a student, institution, or organization — we'd like to hear from you.</p>
                    <div style="display:flex;flex-wrap:wrap;justify-content:center;gap:12px;position:relative">
                        <a href="/contact" class="btn btn-primary">
                            <i data-lucide="mail" style="width:18px;height:18px"></i>
                            Contact Us
                        </a>
                        <a href="/faq" class="btn btn-secondary" style="background:rgba(255,255,255,0.1);color:#fff;border-color:rgba(255,255,255,0.25)">
                            Read FAQ
                        </a>
                    </div>
                </div>
            </div>
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
        const mobileMenuBtn=document.getElementById('mobileMenuBtn'),mobileMenuClose=document.getElementById('mobileMenuClose'),mobileMenu=document.getElementById('mobileMenu'),mobileMenuOverlay=document.getElementById('mobileMenuOverlay');
        function openMenu(){mobileMenu.classList.add('active');mobileMenuOverlay.classList.add('active');mobileMenu.setAttribute('aria-hidden','false');mobileMenuBtn.setAttribute('aria-expanded','true');document.body.classList.add('menu-open')}
        function closeMenu(){mobileMenu.classList.remove('active');mobileMenuOverlay.classList.remove('active');mobileMenu.setAttribute('aria-hidden','true');mobileMenuBtn.setAttribute('aria-expanded','false');document.body.classList.remove('menu-open')}
        mobileMenuBtn.addEventListener('click',openMenu);mobileMenuClose.addEventListener('click',closeMenu);mobileMenuOverlay.addEventListener('click',closeMenu);
        document.addEventListener('keydown',function(e){if(e.key==='Escape')closeMenu()});
        mobileMenu.querySelectorAll('a').forEach(function(l){l.addEventListener('click',closeMenu)});
        var pr=window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if(!pr){var o=new IntersectionObserver(function(e){e.forEach(function(x){if(x.isIntersecting){x.target.classList.add('revealed');o.unobserve(x.target)}})},{threshold:0.05,rootMargin:'0px 0px -20px 0px'});document.querySelectorAll('.reveal').forEach(function(el){o.observe(el)})}else{document.querySelectorAll('.reveal').forEach(function(el){el.classList.add('revealed')})}
    </script>
</body>
</html>
