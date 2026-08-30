<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scholarships — ScholarMatch</title>
    <meta name="description" content="Browse scholarship opportunities matched to your profile. Filter by country, level, funding type, and field of study.">
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

        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            font-family: var(--font);
            font-size: 15px;
            line-height: 1.6;
            color: var(--text-600);
            background: var(--bg-100);
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }
        a { color: inherit; text-decoration: none; }
        button { font-family: inherit; cursor: pointer; border: none; background: none; }
        ul { list-style: none; }
        img { max-width: 100%; display: block; }

        :focus-visible {
            outline: 2px solid var(--primary-light);
            outline-offset: 2px;
            border-radius: var(--radius-sm);
        }

        .sr-only {
            position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px;
            overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; border: 0;
        }

        h1, h2, h3, h4 { color: var(--text-900); line-height: 1.25; font-weight: 600; }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 clamp(16px, 4vw, 24px);
        }

        /* ===== BUTTONS ===== */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 0.9375rem;
            font-weight: 500;
            padding: 12px 24px;
            border-radius: var(--radius-md);
            transition: all 200ms ease;
            white-space: nowrap;
            min-height: 44px;
            line-height: 1.2;
        }
        .btn-primary { background: var(--primary); color: #fff; box-shadow: 0 1px 2px rgba(30,64,175,0.2); }
        .btn-primary:hover { background: var(--primary-dark); box-shadow: 0 4px 12px rgba(30,64,175,0.3); transform: translateY(-1px); }
        .btn-secondary { background: var(--bg-white); color: var(--text-700); border: 1px solid var(--border); }
        .btn-secondary:hover { background: var(--bg-50); border-color: var(--text-300); }
        .btn-sm { font-size: 0.8125rem; padding: 8px 16px; min-height: 36px; }
        .btn-ghost { background: transparent; color: var(--primary-light); font-weight: 500; }
        .btn-ghost:hover { background: var(--primary-50); }

        /* ===== HEADER ===== */
        .header {
            position: sticky;
            top: 0;
            z-index: 100;
            height: var(--header-height);
            background: rgba(255,255,255,0.92);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
        }

        .header-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 100%;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }

        .logo-icon {
            width: 32px;
            height: 32px;
            background: var(--primary);
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }

        .logo-icon i { width: 16px; height: 16px; }

        .logo-text {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-900);
            letter-spacing: -0.02em;
        }

        .nav-desktop {
            display: none;
            align-items: center;
            gap: 4px;
        }

        .nav-desktop a {
            font-size: 0.875rem;
            font-weight: 450;
            color: var(--text-500);
            padding: 8px 14px;
            border-radius: var(--radius-sm);
            transition: all 150ms ease;
        }

        .nav-desktop a:hover { color: var(--text-900); background: var(--bg-50); }
        .nav-desktop a.active { color: var(--primary); background: var(--primary-50); font-weight: 500; }

        .header-actions {
            display: none;
            align-items: center;
            gap: 8px;
        }

        .header-actions .btn-login {
            font-size: 0.875rem; font-weight: 500; color: var(--text-600);
            padding: 8px 16px; border-radius: var(--radius-sm); min-height: 40px;
            transition: all 150ms;
        }
        .header-actions .btn-login:hover { color: var(--text-900); background: var(--bg-50); }

        .mobile-menu-btn {
            display: flex; align-items: center; justify-content: center;
            width: 44px; height: 44px; border-radius: var(--radius-sm);
            color: var(--text-700); transition: background 150ms;
        }
        .mobile-menu-btn:hover { background: var(--bg-50); }
        .mobile-menu-btn i { width: 22px; height: 22px; }

        @media (min-width: 1024px) {
            .nav-desktop { display: flex; }
            .header-actions { display: flex; }
            .mobile-menu-btn { display: none; }
        }

        /* Mobile menu */
        .mobile-menu-overlay {
            position: fixed; inset: 0; z-index: 999;
            background: rgba(0,0,0,0.3); opacity: 0; visibility: hidden;
            transition: all 300ms ease;
        }
        .mobile-menu-overlay.active { opacity: 1; visibility: visible; }

        .mobile-menu {
            position: fixed; top: 0; right: 0; bottom: 0; z-index: 1001;
            width: min(320px, 85vw); background: var(--bg-white);
            box-shadow: var(--shadow-xl); transform: translateX(100%);
            transition: transform 300ms ease; display: flex; flex-direction: column; overflow-y: auto;
        }
        .mobile-menu.active { transform: translateX(0); }

        .mobile-menu-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 16px 20px; border-bottom: 1px solid var(--border); flex-shrink: 0;
        }

        .mobile-menu-close {
            display: flex; align-items: center; justify-content: center;
            width: 40px; height: 40px; border-radius: var(--radius-sm);
            color: var(--text-500); transition: background 150ms;
        }
        .mobile-menu-close:hover { background: var(--bg-50); }

        .mobile-menu-nav { padding: 12px 16px; flex: 1; }
        .mobile-menu-nav a {
            display: flex; align-items: center; padding: 12px 12px;
            font-size: 0.9375rem; font-weight: 450; color: var(--text-700);
            border-radius: var(--radius-sm); transition: all 150ms;
        }
        .mobile-menu-nav a:hover { background: var(--bg-50); color: var(--text-900); }
        .mobile-menu-nav a.active { color: var(--primary); background: var(--primary-50); font-weight: 500; }

        .mobile-menu-footer {
            padding: 16px 20px; border-top: 1px solid var(--border);
            display: flex; flex-direction: column; gap: 8px; flex-shrink: 0;
        }
        .mobile-menu-footer .btn { width: 100%; }

        body.menu-open { overflow: hidden; }

        /* ===== PAGE HERO ===== */
        .page-hero {
            background: linear-gradient(180deg, var(--primary-50) 0%, var(--bg-100) 100%);
            padding: calc(var(--header-height) + clamp(28px, 4vw, 48px)) 0 clamp(28px, 4vw, 48px);
            border-bottom: 1px solid var(--border-light);
        }

        .page-hero h1 {
            font-size: clamp(1.5rem, 3vw, 2rem);
            font-weight: 700;
            letter-spacing: -0.025em;
            margin-bottom: 8px;
        }

        .page-hero p {
            font-size: clamp(0.875rem, 1.5vw, 1rem);
            color: var(--text-500);
            max-width: 520px;
        }

        .page-hero-breadcrumb {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.8125rem;
            color: var(--text-400);
            margin-bottom: 16px;
        }

        .page-hero-breadcrumb a { color: var(--text-500); transition: color 150ms; }
        .page-hero-breadcrumb a:hover { color: var(--primary); }
        .page-hero-breadcrumb i { width: 14px; height: 14px; }

        /* ===== SEARCH BAR ===== */
        .search-bar {
            margin-top: 20px;
            max-width: 560px;
        }

        .search-input-wrap {
            display: flex;
            align-items: center;
            background: var(--bg-white);
            border: 1.5px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 0 16px;
            transition: all 200ms;
            box-shadow: var(--shadow-sm);
        }

        .search-input-wrap:focus-within {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1), var(--shadow-sm);
        }

        .search-input-wrap i { width: 20px; height: 20px; color: var(--text-400); flex-shrink: 0; }

        .search-input {
            flex: 1;
            border: none;
            outline: none;
            padding: 14px 12px;
            font-size: 0.9375rem;
            font-family: var(--font);
            color: var(--text-900);
            background: transparent;
            min-width: 0;
        }

        .search-input::placeholder { color: var(--text-300); }

        .search-clear {
            width: 32px; height: 32px; border-radius: var(--radius-sm);
            display: none; align-items: center; justify-content: center;
            color: var(--text-400); transition: all 150ms;
        }
        .search-clear.visible { display: flex; }
        .search-clear:hover { background: var(--bg-50); color: var(--text-700); }
        .search-clear i { width: 16px; height: 16px; }

        /* ===== MAIN LAYOUT ===== */
        .scholarships-layout {
            padding: clamp(20px, 3vw, 32px) 0 clamp(40px, 6vw, 72px);
        }

        .scholarships-grid-layout {
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
        }

        @media (min-width: 1024px) {
            .scholarships-grid-layout {
                grid-template-columns: 240px 1fr;
                gap: clamp(24px, 3vw, 32px);
            }
        }

        /* ===== SIDEBAR FILTERS ===== */
        .filter-sidebar {
            display: none;
        }

        @media (min-width: 1024px) {
            .filter-sidebar { display: block; }
        }

        .filter-card {
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            padding: 20px;
            position: sticky;
            top: calc(var(--header-height) + 24px);
        }

        .filter-card-title {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-900);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-card-title i { width: 16px; height: 16px; color: var(--text-400); }

        .filter-group {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-light);
        }

        .filter-group:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }

        .filter-group-label {
            font-size: 0.6875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--text-400);
            margin-bottom: 10px;
        }

        .filter-option {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 6px 0;
            font-size: 0.8125rem;
            color: var(--text-600);
            cursor: pointer;
            transition: color 150ms;
            user-select: none;
        }

        .filter-option:hover { color: var(--text-900); }

        .filter-checkbox {
            width: 18px;
            height: 18px;
            border-radius: 4px;
            border: 1.5px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: all 150ms;
        }

        .filter-option.active .filter-checkbox {
            background: var(--primary);
            border-color: var(--primary);
        }

        .filter-option.active .filter-checkbox::after {
            content: '';
            width: 10px;
            height: 10px;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='20 6 9 17 4 12'%3E%3C/polyline%3E%3C/svg%3E") no-repeat center/contain;
        }

        .filter-count {
            margin-left: auto;
            font-size: 0.6875rem;
            font-weight: 500;
            color: var(--text-400);
            background: var(--bg-100);
            padding: 1px 6px;
            border-radius: 100px;
        }

        .filter-clear-btn {
            width: 100%;
            padding: 8px;
            font-size: 0.8125rem;
            font-weight: 500;
            color: var(--primary-light);
            border-radius: var(--radius-sm);
            transition: all 150ms;
            margin-top: 8px;
        }
        .filter-clear-btn:hover { background: var(--primary-50); }

        /* ===== MOBILE FILTER DRAWER ===== */
        .mobile-filter-btn {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 10px 16px;
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-700);
            min-height: 44px;
            margin-bottom: 16px;
            transition: all 150ms;
        }
        .mobile-filter-btn:hover { border-color: var(--text-300); }
        .mobile-filter-btn i { width: 18px; height: 18px; }

        @media (min-width: 1024px) {
            .mobile-filter-btn { display: none; }
        }

        .mobile-filter-drawer-overlay {
            position: fixed; inset: 0; z-index: 200;
            background: rgba(0,0,0,0.4); opacity: 0; visibility: hidden;
            transition: all 300ms ease;
        }
        .mobile-filter-drawer-overlay.active { opacity: 1; visibility: visible; }

        .mobile-filter-drawer {
            position: fixed; bottom: 0; left: 0; right: 0; z-index: 201;
            background: var(--bg-white); border-radius: var(--radius-2xl) var(--radius-2xl) 0 0;
            box-shadow: 0 -8px 30px rgba(0,0,0,0.12);
            transform: translateY(100%);
            transition: transform 300ms ease;
            max-height: 80vh;
            overflow-y: auto;
        }
        .mobile-filter-drawer.active { transform: translateY(0); }

        .mobile-filter-drawer-handle {
            display: flex; justify-content: center; padding: 12px;
            position: sticky; top: 0; background: var(--bg-white); z-index: 1;
        }

        .mobile-filter-drawer-handle span {
            width: 36px; height: 4px; border-radius: 100px; background: var(--text-300);
        }

        .mobile-filter-drawer-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 20px 16px; border-bottom: 1px solid var(--border-light);
            position: sticky; top: 28px; background: var(--bg-white); z-index: 1;
        }

        .mobile-filter-drawer-header h3 { font-size: 1rem; }

        .mobile-filter-drawer-body { padding: 20px; }

        .mobile-filter-drawer-footer {
            display: flex; gap: 12px; padding: 16px 20px;
            border-top: 1px solid var(--border-light);
            position: sticky; bottom: 0; background: var(--bg-white);
        }

        .mobile-filter-drawer-footer .btn { flex: 1; }

        /* ===== RESULTS HEADER ===== */
        .results-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
        }

        .results-count {
            font-size: 0.875rem;
            color: var(--text-500);
        }

        .results-count strong {
            color: var(--text-900);
            font-weight: 600;
        }

        .results-sort {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .results-sort label {
            font-size: 0.8125rem;
            color: var(--text-500);
            white-space: nowrap;
        }

        .results-sort-select {
            padding: 8px 32px 8px 12px;
            font-size: 0.8125rem;
            font-family: var(--font);
            color: var(--text-700);
            background: var(--bg-white) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") no-repeat right 10px center;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            appearance: none;
            cursor: pointer;
            min-height: 40px;
            transition: border-color 150ms;
        }

        .results-sort-select:focus {
            border-color: var(--primary-light);
            outline: none;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }

        /* Active filter tags */
        .active-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 16px;
        }

        .active-filter-tag {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--primary);
            background: var(--primary-50);
            padding: 4px 10px;
            border-radius: 100px;
            cursor: pointer;
            transition: all 150ms;
        }

        .active-filter-tag:hover { background: var(--primary-100); }
        .active-filter-tag i { width: 12px; height: 12px; }

        .clear-all-filters {
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--text-500);
            padding: 4px 8px;
            cursor: pointer;
            transition: color 150ms;
        }
        .clear-all-filters:hover { color: var(--text-900); }

        /* ===== SCHOLARSHIP CARDS ===== */
        .scholarship-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .scholarship-card {
            display: grid;
            grid-template-columns: 1fr;
            gap: 16px;
            padding: clamp(16px, 3vw, 24px);
            border-radius: var(--radius-xl);
            border: 1px solid var(--border);
            background: var(--bg-white);
            transition: all 200ms ease;
            position: relative;
        }

        .scholarship-card:hover {
            box-shadow: var(--shadow-md);
            border-color: var(--primary-200);
        }

        @media (min-width: 640px) {
            .scholarship-card {
                grid-template-columns: 1fr auto;
                align-items: center;
            }
        }

        .scholarship-card-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .scholarship-card-country {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.8125rem;
            color: var(--text-500);
        }

        .scholarship-card-country i { width: 14px; height: 14px; }

        .scholarship-card-match {
            font-size: 0.75rem;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 100px;
            flex-shrink: 0;
        }

        .match-high { color: var(--accent); background: var(--accent-50); }
        .match-medium { color: #d97706; background: #fef3c7; }
        .match-low { color: var(--text-500); background: var(--bg-100); }

        .scholarship-card-title {
            font-size: clamp(1rem, 1.5vw, 1.125rem);
            font-weight: 600;
            color: var(--text-900);
            margin-bottom: 6px;
            line-height: 1.35;
        }

        .scholarship-card-title a {
            transition: color 150ms;
        }

        .scholarship-card-title a:hover {
            color: var(--primary);
        }

        .scholarship-card-desc {
            font-size: 0.8125rem;
            color: var(--text-500);
            line-height: 1.6;
            margin-bottom: 12px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .scholarship-card-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 14px;
        }

        .scholarship-tag {
            font-size: 0.6875rem;
            font-weight: 500;
            color: var(--text-500);
            background: var(--bg-100);
            padding: 3px 10px;
            border-radius: 100px;
            white-space: nowrap;
        }

        .scholarship-tag-funded {
            color: var(--accent);
            background: var(--accent-50);
        }

        .scholarship-card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
        }

        .scholarship-deadline {
            font-size: 0.8125rem;
            color: var(--text-500);
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .scholarship-deadline i { width: 14px; height: 14px; }
        .scholarship-deadline.urgent { color: #dc2626; font-weight: 500; }
        .scholarship-deadline.urgent i { color: #dc2626; }

        .scholarship-card-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }

        .btn-save {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-400);
            border: 1px solid var(--border);
            transition: all 150ms;
            background: var(--bg-white);
        }

        .btn-save:hover { border-color: var(--primary-200); color: var(--primary); background: var(--primary-50); }
        .btn-save.saved { color: var(--primary); border-color: var(--primary-200); background: var(--primary-50); }
        .btn-save i { width: 18px; height: 18px; }

        /* ===== PAGINATION ===== */
        .pagination {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            margin-top: 32px;
            flex-wrap: wrap;
        }

        .page-btn {
            min-width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-600);
            transition: all 150ms;
            padding: 0 4px;
        }

        .page-btn:hover { background: var(--bg-50); color: var(--text-900); }

        .page-btn.active {
            background: var(--primary);
            color: #fff;
            box-shadow: 0 1px 2px rgba(30,64,175,0.2);
        }

        .page-btn.disabled {
            color: var(--text-300);
            pointer-events: none;
        }

        .page-btn i { width: 18px; height: 18px; }

        /* ===== EMPTY STATE ===== */
        .empty-state {
            text-align: center;
            padding: 60px 24px;
        }

        .empty-state-icon {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: var(--bg-100);
            color: var(--text-400);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
        }

        .empty-state-icon i { width: 24px; height: 24px; }

        .empty-state h3 {
            font-size: 1.0625rem;
            margin-bottom: 6px;
        }

        .empty-state p {
            font-size: 0.875rem;
            color: var(--text-500);
            margin: 0 auto;
        }

        /* ===== CTA BANNER ===== */
        .cta-banner {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
            border-radius: var(--radius-2xl);
            padding: clamp(28px, 4vw, 48px) clamp(20px, 4vw, 40px);
            text-align: center;
            margin-top: 40px;
            position: relative;
            overflow: hidden;
        }

        .cta-banner::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -15%;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(255,255,255,0.04);
            pointer-events: none;
        }

        .cta-banner h2 {
            color: #fff;
            font-size: clamp(1.125rem, 2.5vw, 1.5rem);
            margin-bottom: 8px;
            position: relative;
        }

        .cta-banner p {
            color: rgba(255,255,255,0.8);
            font-size: 0.9375rem;
            margin: 0 auto 24px;
            max-width: 440px;
            position: relative;
        }

        .cta-banner .btn {
            position: relative;
        }

        .cta-banner .btn-primary {
            background: #fff;
            color: var(--primary);
        }

        .cta-banner .btn-primary:hover {
            background: var(--primary-50);
        }

        /* ===== SCROLL REVEAL ===== */
        .reveal {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 600ms ease, transform 600ms ease;
        }
        .reveal.revealed { opacity: 1; transform: translateY(0); }
        .reveal-delay-1 { transition-delay: 100ms; }
        .reveal-delay-2 { transition-delay: 200ms; }

        @media (prefers-reduced-motion: reduce) {
            .reveal { opacity: 1; transform: none; transition: none; }
            html { scroll-behavior: auto; }
            *, *::before, *::after { transition-duration: 0.01ms !important; animation-duration: 0.01ms !important; }
        }

        /* ===== RESPONSIVE 280px ===== */
        @media (max-width: 320px) {
            .results-header { flex-direction: column; align-items: flex-start; }
            .scholarship-card-footer { flex-direction: column; align-items: flex-start; }
            .scholarship-card-actions { width: 100%; }
            .scholarship-card-actions .btn { flex: 1; }
            .pagination { gap: 2px; }
            .page-btn { min-width: 36px; height: 36px; font-size: 0.8125rem; }
        }
    </style>
</head>
<body>

    <!-- ===== HEADER ===== -->
    <header class="header" role="banner">
        <div class="header-inner container">
            <a href="/" class="logo" aria-label="ScholarMatch Home">
                <div class="logo-icon"><i data-lucide="graduation-cap"></i></div>
                <span class="logo-text">ScholarMatch</span>
            </a>

            <nav class="nav-desktop" aria-label="Main navigation">
                <a href="/">Home</a>
                <a href="/scholarships" class="active">Scholarships</a>
                <a href="/how-it-works">How It Works</a>
                <a href="/features">Features</a>
                <a href="/pricing">Pricing</a>
                <a href="/faq">FAQ</a>
            </nav>

            <div class="header-actions">
                <a href="/login" class="btn-login">Log In</a>
                <a href="/register" class="btn btn-primary btn-sm">Get Started</a>
            </div>

            <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Open menu" aria-expanded="false">
                <i data-lucide="menu"></i>
            </button>
        </div>
    </header>

    <!-- Mobile menu overlay -->
    <div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>

    <!-- Mobile menu -->
    <nav class="mobile-menu" id="mobileMenu" aria-label="Mobile navigation" aria-hidden="true">
        <div class="mobile-menu-header">
            <span class="logo-text">ScholarMatch</span>
            <button class="mobile-menu-close" id="mobileMenuClose" aria-label="Close menu">
                <i data-lucide="x"></i>
            </button>
        </div>
        <div class="mobile-menu-nav">
            <a href="/">Home</a>
            <a href="/scholarships" class="active">Scholarships</a>
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
        <!-- ===== PAGE HERO ===== -->
        <section class="page-hero">
            <div class="container">
                <nav class="page-hero-breadcrumb" aria-label="Breadcrumb">
                    <a href="/">Home</a>
                    <i data-lucide="chevron-right"></i>
                    <span>Scholarships</span>
                </nav>
                <h1>Browse Scholarships</h1>
                <p>Explore scholarship opportunities from around the world. Create a profile to see your personalized match scores.</p>

                <div class="search-bar">
                    <div class="search-input-wrap">
                        <i data-lucide="search"></i>
                        <input
                            type="search"
                            class="search-input"
                            id="searchInput"
                            placeholder="Search scholarships by name, country, or field..."
                            aria-label="Search scholarships"
                        >
                        <button class="search-clear" id="searchClear" aria-label="Clear search">
                            <i data-lucide="x"></i>
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== SCHOLARSHIPS CONTENT ===== -->
        <section class="scholarships-layout">
            <div class="container">
                <div class="scholarships-grid-layout">

                    <!-- Desktop sidebar filters -->
                    <aside class="filter-sidebar" aria-label="Filters">
                        <div class="filter-card">
                            <div class="filter-card-title">
                                <i data-lucide="sliders-horizontal"></i>
                                Filters
                            </div>

                            <div class="filter-group">
                                <div class="filter-group-label">Funding Type</div>
                                <div class="filter-option active" data-filter="funding" data-value="fully-funded">
                                    <div class="filter-checkbox"></div>
                                    Fully Funded
                                    <span class="filter-count">8</span>
                                </div>
                                <div class="filter-option" data-filter="funding" data-value="partially-funded">
                                    <div class="filter-checkbox"></div>
                                    Partially Funded
                                    <span class="filter-count">3</span>
                                </div>
                                <div class="filter-option" data-filter="funding" data-value="tuition-only">
                                    <div class="filter-checkbox"></div>
                                    Tuition Only
                                    <span class="filter-count">2</span>
                                </div>
                            </div>

                            <div class="filter-group">
                                <div class="filter-group-label">Study Level</div>
                                <div class="filter-option" data-filter="level" data-value="undergraduate">
                                    <div class="filter-checkbox"></div>
                                    Undergraduate
                                    <span class="filter-count">2</span>
                                </div>
                                <div class="filter-option active" data-filter="level" data-value="masters">
                                    <div class="filter-checkbox"></div>
                                    Master's
                                    <span class="filter-count">7</span>
                                </div>
                                <div class="filter-option" data-filter="level" data-value="phd">
                                    <div class="filter-checkbox"></div>
                                    PhD
                                    <span class="filter-count">4</span>
                                </div>
                                <div class="filter-option" data-filter="level" data-value="research">
                                    <div class="filter-checkbox"></div>
                                    Research
                                    <span class="filter-count">2</span>
                                </div>
                            </div>

                            <div class="filter-group">
                                <div class="filter-group-label">Region</div>
                                <div class="filter-option active" data-filter="region" data-value="europe">
                                    <div class="filter-checkbox"></div>
                                    Europe
                                    <span class="filter-count">4</span>
                                </div>
                                <div class="filter-option" data-filter="region" data-value="asia">
                                    <div class="filter-checkbox"></div>
                                    Asia
                                    <span class="filter-count">5</span>
                                </div>
                                <div class="filter-option" data-filter="region" data-value="americas">
                                    <div class="filter-checkbox"></div>
                                    Americas
                                    <span class="filter-count">2</span>
                                </div>
                                <div class="filter-option" data-filter="region" data-value="oceania">
                                    <div class="filter-checkbox"></div>
                                    Oceania
                                    <span class="filter-count">2</span>
                                </div>
                            </div>

                            <div class="filter-group">
                                <div class="filter-group-label">Field of Study</div>
                                <div class="filter-option" data-filter="field" data-value="engineering">
                                    <div class="filter-checkbox"></div>
                                    Engineering
                                    <span class="filter-count">5</span>
                                </div>
                                <div class="filter-option" data-filter="field" data-value="cs">
                                    <div class="filter-checkbox"></div>
                                    Computer Science
                                    <span class="filter-count">4</span>
                                </div>
                                <div class="filter-option" data-filter="field" data-value="medical">
                                    <div class="filter-checkbox"></div>
                                    Medical / Health
                                    <span class="filter-count">3</span>
                                </div>
                                <div class="filter-option" data-filter="field" data-value="business">
                                    <div class="filter-checkbox"></div>
                                    Business
                                    <span class="filter-count">2</span>
                                </div>
                                <div class="filter-option" data-filter="field" data-value="all-fields">
                                    <div class="filter-checkbox"></div>
                                    All Fields
                                    <span class="filter-count">6</span>
                                </div>
                            </div>

                            <button class="filter-clear-btn" id="clearFilters">Clear All Filters</button>
                        </div>
                    </aside>

                    <!-- Main content -->
                    <div>
                        <!-- Mobile filter button -->
                        <button class="mobile-filter-btn" id="mobileFilterBtn">
                            <i data-lucide="sliders-horizontal"></i>
                            Filters
                            <span id="mobileFilterCount" style="display:none;font-size:0.6875rem;font-weight:700;background:var(--primary);color:#fff;padding:1px 7px;border-radius:100px">0</span>
                        </button>

                        <!-- Active filter tags -->
                        <div class="active-filters" id="activeFilters" style="display:none">
                            <!-- Populated by JS -->
                        </div>

                        <!-- Results header -->
                        <div class="results-header">
                            <span class="results-count">
                                Showing <strong id="resultsCount">8</strong> scholarships
                            </span>
                            <div class="results-sort">
                                <label for="sortSelect">Sort by:</label>
                                <select class="results-sort-select" id="sortSelect">
                                    <option value="match">Best Match</option>
                                    <option value="deadline-asc">Deadline (Nearest)</option>
                                    <option value="deadline-desc">Deadline (Farthest)</option>
                                    <option value="newest">Newest First</option>
                                </select>
                            </div>
                        </div>

                        <!-- Scholarship list -->
                        <div class="scholarship-list" id="scholarshipList">

                            <!-- Card 1 -->
                            <article class="scholarship-card reveal">
                                <div>
                                    <div class="scholarship-card-top">
                                        <span class="scholarship-card-country">
                                            <i data-lucide="map-pin"></i> Germany
                                        </span>
                                        <span class="scholarship-card-match match-high">94% Match</span>
                                    </div>
                                    <h2 class="scholarship-card-title">
                                        <a href="/scholarships/germany-masters-scholarship">Germany Master's Scholarship 2027</a>
                                    </h2>
                                    <p class="scholarship-card-desc">Fully funded scholarship for international students pursuing master's degrees at German universities across all disciplines.</p>
                                    <div class="scholarship-card-meta">
                                        <span class="scholarship-tag">Master's</span>
                                        <span class="scholarship-tag scholarship-tag-funded">Fully Funded</span>
                                        <span class="scholarship-tag">Computer Science</span>
                                        <span class="scholarship-tag">Engineering</span>
                                    </div>
                                    <div class="scholarship-card-footer">
                                        <span class="scholarship-deadline urgent">
                                            <i data-lucide="calendar"></i> 18 days remaining
                                        </span>
                                    </div>
                                </div>
                                <div class="scholarship-card-actions">
                                    <button class="btn-save" aria-label="Save scholarship" title="Save">
                                        <i data-lucide="bookmark"></i>
                                    </button>
                                    <a href="/scholarships/germany-masters-scholarship" class="btn btn-primary btn-sm">View Details</a>
                                </div>
                            </article>

                            <!-- Card 2 -->
                            <article class="scholarship-card reveal reveal-delay-1">
                                <div>
                                    <div class="scholarship-card-top">
                                        <span class="scholarship-card-country">
                                            <i data-lucide="map-pin"></i> Turkey
                                        </span>
                                        <span class="scholarship-card-match match-high">91% Match</span>
                                    </div>
                                    <h2 class="scholarship-card-title">
                                        <a href="/scholarships/turkey-graduate-scholarship">Turkey Graduate Scholarship Program</a>
                                    </h2>
                                    <p class="scholarship-card-desc">Government-funded scholarship for graduate-level studies at Turkish universities. Covers tuition, accommodation, and monthly stipend.</p>
                                    <div class="scholarship-card-meta">
                                        <span class="scholarship-tag">Master's</span>
                                        <span class="scholarship-tag scholarship-tag-funded">Fully Funded</span>
                                        <span class="scholarship-tag">Engineering</span>
                                        <span class="scholarship-tag">All Fields</span>
                                    </div>
                                    <div class="scholarship-card-footer">
                                        <span class="scholarship-deadline urgent">
                                            <i data-lucide="calendar"></i> 5 days remaining
                                        </span>
                                    </div>
                                </div>
                                <div class="scholarship-card-actions">
                                    <button class="btn-save" aria-label="Save scholarship" title="Save">
                                        <i data-lucide="bookmark"></i>
                                    </button>
                                    <a href="/scholarships/turkey-graduate-scholarship" class="btn btn-primary btn-sm">View Details</a>
                                </div>
                            </article>

                            <!-- Card 3 -->
                            <article class="scholarship-card reveal">
                                <div>
                                    <div class="scholarship-card-top">
                                        <span class="scholarship-card-country">
                                            <i data-lucide="map-pin"></i> China
                                        </span>
                                        <span class="scholarship-card-match match-high">87% Match</span>
                                    </div>
                                    <h2 class="scholarship-card-title">
                                        <a href="/scholarships/china-government-scholarship">China Government Scholarship (CSC)</a>
                                    </h2>
                                    <p class="scholarship-card-desc">CSC scholarship covering tuition, accommodation, stipend, and medical insurance for international students at Chinese universities.</p>
                                    <div class="scholarship-card-meta">
                                        <span class="scholarship-tag">Master's</span>
                                        <span class="scholarship-tag scholarship-tag-funded">Fully Funded</span>
                                        <span class="scholarship-tag">All Fields</span>
                                    </div>
                                    <div class="scholarship-card-footer">
                                        <span class="scholarship-deadline">
                                            <i data-lucide="calendar"></i> 45 days remaining
                                        </span>
                                    </div>
                                </div>
                                <div class="scholarship-card-actions">
                                    <button class="btn-save" aria-label="Save scholarship" title="Save">
                                        <i data-lucide="bookmark"></i>
                                    </button>
                                    <a href="/scholarships/china-government-scholarship" class="btn btn-primary btn-sm">View Details</a>
                                </div>
                            </article>

                            <!-- Card 4 -->
                            <article class="scholarship-card reveal reveal-delay-1">
                                <div>
                                    <div class="scholarship-card-top">
                                        <span class="scholarship-card-country">
                                            <i data-lucide="map-pin"></i> South Korea
                                        </span>
                                        <span class="scholarship-card-match match-medium">84% Match</span>
                                    </div>
                                    <h2 class="scholarship-card-title">
                                        <a href="/scholarships/south-korea-kgsp">South Korea KGSP — Graduate Scholarship</a>
                                    </h2>
                                    <p class="scholarship-card-desc">Korean Government Scholarship Program providing full financial support for international graduate students at Korean universities.</p>
                                    <div class="scholarship-card-meta">
                                        <span class="scholarship-tag">Master's</span>
                                        <span class="scholarship-tag scholarship-tag-funded">Fully Funded</span>
                                        <span class="scholarship-tag">Science</span>
                                        <span class="scholarship-tag">Engineering</span>
                                    </div>
                                    <div class="scholarship-card-footer">
                                        <span class="scholarship-deadline">
                                            <i data-lucide="calendar"></i> 90 days remaining
                                        </span>
                                    </div>
                                </div>
                                <div class="scholarship-card-actions">
                                    <button class="btn-save saved" aria-label="Unsave scholarship" title="Saved">
                                        <i data-lucide="bookmark"></i>
                                    </button>
                                    <a href="/scholarships/south-korea-kgsp" class="btn btn-primary btn-sm">View Details</a>
                                </div>
                            </article>

                            <!-- Card 5 -->
                            <article class="scholarship-card reveal">
                                <div>
                                    <div class="scholarship-card-top">
                                        <span class="scholarship-card-country">
                                            <i data-lucide="map-pin"></i> Japan
                                        </span>
                                        <span class="scholarship-card-match match-medium">82% Match</span>
                                    </div>
                                    <h2 class="scholarship-card-title">
                                        <a href="/scholarships/japan-mext-research">MEXT Research Scholarship 2027</a>
                                    </h2>
                                    <p class="scholarship-card-desc">Japanese Government scholarship for research students to conduct graduate-level research at Japanese universities.</p>
                                    <div class="scholarship-card-meta">
                                        <span class="scholarship-tag">Research</span>
                                        <span class="scholarship-tag scholarship-tag-funded">Fully Funded</span>
                                        <span class="scholarship-tag">All Fields</span>
                                    </div>
                                    <div class="scholarship-card-footer">
                                        <span class="scholarship-deadline">
                                            <i data-lucide="calendar"></i> 120 days remaining
                                        </span>
                                    </div>
                                </div>
                                <div class="scholarship-card-actions">
                                    <button class="btn-save" aria-label="Save scholarship" title="Save">
                                        <i data-lucide="bookmark"></i>
                                    </button>
                                    <a href="/scholarships/japan-mext-research" class="btn btn-primary btn-sm">View Details</a>
                                </div>
                            </article>

                            <!-- Card 6 -->
                            <article class="scholarship-card reveal reveal-delay-1">
                                <div>
                                    <div class="scholarship-card-top">
                                        <span class="scholarship-card-country">
                                            <i data-lucide="map-pin"></i> Australia
                                        </span>
                                        <span class="scholarship-card-match match-medium">79% Match</span>
                                    </div>
                                    <h2 class="scholarship-card-title">
                                        <a href="/scholarships/australia-awards">Australia Awards Scholarship</a>
                                    </h2>
                                    <p class="scholarship-card-desc">Australian Government funded scholarships for students from developing countries to undertake full-time undergraduate or postgraduate study.</p>
                                    <div class="scholarship-card-meta">
                                        <span class="scholarship-tag">Master's</span>
                                        <span class="scholarship-tag scholarship-tag-funded">Fully Funded</span>
                                        <span class="scholarship-tag">Development Studies</span>
                                    </div>
                                    <div class="scholarship-card-footer">
                                        <span class="scholarship-deadline">
                                            <i data-lucide="calendar"></i> 150 days remaining
                                        </span>
                                    </div>
                                </div>
                                <div class="scholarship-card-actions">
                                    <button class="btn-save" aria-label="Save scholarship" title="Save">
                                        <i data-lucide="bookmark"></i>
                                    </button>
                                    <a href="/scholarships/australia-awards" class="btn btn-primary btn-sm">View Details</a>
                                </div>
                            </article>

                            <!-- Card 7 -->
                            <article class="scholarship-card reveal">
                                <div>
                                    <div class="scholarship-card-top">
                                        <span class="scholarship-card-country">
                                            <i data-lucide="map-pin"></i> United Kingdom
                                        </span>
                                        <span class="scholarship-card-match match-medium">76% Match</span>
                                    </div>
                                    <h2 class="scholarship-card-title">
                                        <a href="/scholarships/uk-chevening">Chevening Scholarship 2027</a>
                                    </h2>
                                    <p class="scholarship-card-desc">UK Government scholarship for outstanding professionals to pursue a one-year master's degree at any UK university.</p>
                                    <div class="scholarship-card-meta">
                                        <span class="scholarship-tag">Master's</span>
                                        <span class="scholarship-tag scholarship-tag-funded">Fully Funded</span>
                                        <span class="scholarship-tag">All Fields</span>
                                    </div>
                                    <div class="scholarship-card-footer">
                                        <span class="scholarship-deadline">
                                            <i data-lucide="calendar"></i> 200 days remaining
                                        </span>
                                    </div>
                                </div>
                                <div class="scholarship-card-actions">
                                    <button class="btn-save" aria-label="Save scholarship" title="Save">
                                        <i data-lucide="bookmark"></i>
                                    </button>
                                    <a href="/scholarships/uk-chevening" class="btn btn-primary btn-sm">View Details</a>
                                </div>
                            </article>

                            <!-- Card 8 -->
                            <article class="scholarship-card reveal reveal-delay-1">
                                <div>
                                    <div class="scholarship-card-top">
                                        <span class="scholarship-card-country">
                                            <i data-lucide="map-pin"></i> Canada
                                        </span>
                                        <span class="scholarship-card-match match-low">62% Match</span>
                                    </div>
                                    <h2 class="scholarship-card-title">
                                        <a href="/scholarships/canada-vanier">Vanier Canada Graduate Scholarship</a>
                                    </h2>
                                    <p class="scholarship-card-desc">Canadian Government scholarship for doctoral students demonstrating leadership and high academic achievement in research.</p>
                                    <div class="scholarship-card-meta">
                                        <span class="scholarship-tag">PhD</span>
                                        <span class="scholarship-tag scholarship-tag-funded">Fully Funded</span>
                                        <span class="scholarship-tag">Research</span>
                                        <span class="scholarship-tag">Health Sciences</span>
                                    </div>
                                    <div class="scholarship-card-footer">
                                        <span class="scholarship-deadline">
                                            <i data-lucide="calendar"></i> 250 days remaining
                                        </span>
                                    </div>
                                </div>
                                <div class="scholarship-card-actions">
                                    <button class="btn-save" aria-label="Save scholarship" title="Save">
                                        <i data-lucide="bookmark"></i>
                                    </button>
                                    <a href="/scholarships/canada-vanier" class="btn btn-primary btn-sm">View Details</a>
                                </div>
                            </article>

                        </div><!-- /scholarship-list -->

                        <!-- Pagination -->
                        <nav class="pagination" aria-label="Page navigation">
                            <button class="page-btn disabled" aria-label="Previous page" disabled>
                                <i data-lucide="chevron-left"></i>
                            </button>
                            <button class="page-btn active" aria-current="page">1</button>
                            <button class="page-btn" aria-label="Page 2">2</button>
                            <button class="page-btn" aria-label="Page 3">3</button>
                            <button class="page-btn" aria-label="Next page">
                                <i data-lucide="chevron-right"></i>
                            </button>
                        </nav>

                        <!-- CTA Banner -->
                        <div class="cta-banner reveal">
                            <h2>See Your Personalized Matches</h2>
                            <p>Create a free profile and see which scholarships match your education, field, and goals.</p>
                            <a href="/register" class="btn btn-primary">
                                <i data-lucide="user-plus" style="width:18px;height:18px"></i>
                                Create Free Profile
                            </a>
                        </div>

                    </div><!-- /main content -->
                </div><!-- /grid layout -->
            </div><!-- /container -->
        </section>
    </main>

    <!-- ===== MOBILE FILTER DRAWER ===== -->
    <div class="mobile-filter-drawer-overlay" id="mobileFilterOverlay"></div>
    <div class="mobile-filter-drawer" id="mobileFilterDrawer" role="dialog" aria-label="Filter scholarships" aria-hidden="true">
        <div class="mobile-filter-drawer-handle"><span></span></div>
        <div class="mobile-filter-drawer-header">
            <h3>Filters</h3>
            <button class="mobile-menu-close" id="mobileFilterClose" aria-label="Close filters">
                <i data-lucide="x"></i>
            </button>
        </div>
        <div class="mobile-filter-drawer-body" id="mobileFilterBody">
            <!-- Cloned from desktop filters by JS -->
        </div>
        <div class="mobile-filter-drawer-footer">
            <button class="btn btn-secondary" id="mobileFilterReset">Reset</button>
            <button class="btn btn-primary" id="mobileFilterApply">Apply Filters</button>
        </div>
    </div>

    <!-- ===== FOOTER (minimal for inner pages) ===== -->
    <footer style="background:var(--text-900);padding:24px 0;text-align:center">
        <div class="container">
            <p style="font-size:0.75rem;color:var(--text-500)">&copy; 2025 ScholarMatch. All rights reserved.</p>
        </div>
    </footer>


    <script>
        lucide.createIcons();

        // ===== MOBILE MENU =====
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const mobileMenuClose = document.getElementById('mobileMenuClose');
        const mobileMenu = document.getElementById('mobileMenu');
        const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');

        function openMenu() {
            mobileMenu.classList.add('active');
            mobileMenuOverlay.classList.add('active');
            mobileMenu.setAttribute('aria-hidden', 'false');
            mobileMenuBtn.setAttribute('aria-expanded', 'true');
            document.body.classList.add('menu-open');
        }

        function closeMenu() {
            mobileMenu.classList.remove('active');
            mobileMenuOverlay.classList.remove('active');
            mobileMenu.setAttribute('aria-hidden', 'true');
            mobileMenuBtn.setAttribute('aria-expanded', 'false');
            document.body.classList.remove('menu-open');
        }

        mobileMenuBtn.addEventListener('click', openMenu);
        mobileMenuClose.addEventListener('click', closeMenu);
        mobileMenuOverlay.addEventListener('click', closeMenu);

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeMenu();
                closeFilterDrawer();
            }
        });

        mobileMenu.querySelectorAll('a').forEach(function(l) {
            l.addEventListener('click', closeMenu);
        });

        // ===== SEARCH =====
        const searchInput = document.getElementById('searchInput');
        const searchClear = document.getElementById('searchClear');

        searchInput.addEventListener('input', function() {
            searchClear.classList.toggle('visible', this.value.length > 0);
        });

        searchClear.addEventListener('click', function() {
            searchInput.value = '';
            searchClear.classList.remove('visible');
            searchInput.focus();
        });

        // ===== DESKTOP FILTERS =====
        const filterOptions = document.querySelectorAll('.filter-sidebar .filter-option');
        const clearFiltersBtn = document.getElementById('clearFilters');
        const activeFiltersContainer = document.getElementById('activeFilters');
        const resultsCount = document.getElementById('resultsCount');

        function getActiveFilterLabels() {
            var labels = [];
            filterOptions.forEach(function(opt) {
                if (opt.classList.contains('active')) {
                    var text = opt.textContent.trim().replace(/\d+$/, '').trim();
                    labels.push({ el: opt, text: text, filter: opt.dataset.filter, value: opt.dataset.value });
                }
            });
            return labels;
        }

        function renderActiveFilters() {
            var active = getActiveFilterLabels();
            if (active.length === 0) {
                activeFiltersContainer.style.display = 'none';
                return;
            }
            activeFiltersContainer.style.display = 'flex';
            activeFiltersContainer.innerHTML = '';

            active.forEach(function(f) {
                var tag = document.createElement('span');
                tag.className = 'active-filter-tag';
                tag.innerHTML = f.text + ' <i data-lucide="x"></i>';
                tag.addEventListener('click', function() {
                    f.el.classList.remove('active');
                    renderActiveFilters();
                    updateMobileFilterCount();
                });
                activeFiltersContainer.appendChild(tag);
            });

            var clearAll = document.createElement('span');
            clearAll.className = 'clear-all-filters';
            clearAll.textContent = 'Clear all';
            clearAll.addEventListener('click', function() {
                filterOptions.forEach(function(o) { o.classList.remove('active'); });
                renderActiveFilters();
                updateMobileFilterCount();
            });
            activeFiltersContainer.appendChild(clearAll);

            lucide.createIcons();
        }

        filterOptions.forEach(function(opt) {
            opt.addEventListener('click', function() {
                this.classList.toggle('active');
                renderActiveFilters();
                updateMobileFilterCount();
            });
        });

        clearFiltersBtn.addEventListener('click', function() {
            filterOptions.forEach(function(o) { o.classList.remove('active'); });
            renderActiveFilters();
            updateMobileFilterCount();
        });

        // ===== MOBILE FILTER DRAWER =====
        const mobileFilterBtn = document.getElementById('mobileFilterBtn');
        const mobileFilterOverlay = document.getElementById('mobileFilterOverlay');
        const mobileFilterDrawer = document.getElementById('mobileFilterDrawer');
        const mobileFilterClose = document.getElementById('mobileFilterClose');
        const mobileFilterBody = document.getElementById('mobileFilterBody');
        const mobileFilterReset = document.getElementById('mobileFilterReset');
        const mobileFilterApply = document.getElementById('mobileFilterApply');
        const mobileFilterCountEl = document.getElementById('mobileFilterCount');

        // Clone desktop filter groups into mobile drawer
        var desktopFilterCard = document.querySelector('.filter-card');
        if (desktopFilterCard) {
            var groups = desktopFilterCard.querySelectorAll('.filter-group');
            groups.forEach(function(g) {
                var clone = g.cloneNode(true);
                // Remove active state from clones (they sync on open)
                mobileFilterBody.appendChild(clone);
            });
        }

        function updateMobileFilterCount() {
            var count = getActiveFilterLabels().length;
            if (count > 0) {
                mobileFilterCountEl.style.display = '';
                mobileFilterCountEl.textContent = count;
            } else {
                mobileFilterCountEl.style.display = 'none';
            }
        }

        function openFilterDrawer() {
            // Sync mobile filter states with desktop
            var desktopGroups = document.querySelectorAll('.filter-sidebar .filter-group');
            var mobileGroups = mobileFilterBody.querySelectorAll('.filter-group');
            desktopGroups.forEach(function(dg, i) {
                if (mobileGroups[i]) {
                    var dOpts = dg.querySelectorAll('.filter-option');
                    var mOpts = mobileGroups[i].querySelectorAll('.filter-option');
                    dOpts.forEach(function(d, j) {
                        if (mOpts[j]) {
                            mOpts[j].classList.toggle('active', d.classList.contains('active'));
                        }
                    });
                }
            });

            // Add click handlers to mobile options
            mobileFilterBody.querySelectorAll('.filter-option').forEach(function(opt) {
                opt.onclick = function() {
                    this.classList.toggle('active');
                };
            });

            mobileFilterDrawer.classList.add('active');
            mobileFilterOverlay.classList.add('active');
            mobileFilterDrawer.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }

        function closeFilterDrawer() {
            mobileFilterDrawer.classList.remove('active');
            mobileFilterOverlay.classList.remove('active');
            mobileFilterDrawer.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }

        mobileFilterBtn.addEventListener('click', openFilterDrawer);
        mobileFilterClose.addEventListener('click', closeFilterDrawer);
        mobileFilterOverlay.addEventListener('click', closeFilterDrawer);

        mobileFilterApply.addEventListener('click', function() {
            // Sync mobile selections back to desktop
            var desktopGroups = document.querySelectorAll('.filter-sidebar .filter-group');
            var mobileGroups = mobileFilterBody.querySelectorAll('.filter-group');
            mobileGroups.forEach(function(mg, i) {
                if (desktopGroups[i]) {
                    var mOpts = mg.querySelectorAll('.filter-option');
                    var dOpts = desktopGroups[i].querySelectorAll('.filter-option');
                    mOpts.forEach(function(m, j) {
                        if (dOpts[j]) {
                            dOpts[j].classList.toggle('active', m.classList.contains('active'));
                        }
                    });
                }
            });
            renderActiveFilters();
            updateMobileFilterCount();
            closeFilterDrawer();
        });

        mobileFilterReset.addEventListener('click', function() {
            mobileFilterBody.querySelectorAll('.filter-option').forEach(function(o) {
                o.classList.remove('active');
            });
        });

        // ===== SAVE/BOOKMARK TOGGLE =====
        document.querySelectorAll('.btn-save').forEach(function(btn) {
            btn.addEventListener('click', function() {
                this.classList.toggle('saved');
                var isSaved = this.classList.contains('saved');
                this.setAttribute('aria-label', isSaved ? 'Unsave scholarship' : 'Save scholarship');
                this.setAttribute('title', isSaved ? 'Saved' : 'Save');

                // Swap icon
                var icon = this.querySelector('i');
                if (icon) {
                    icon.setAttribute('data-lucide', isSaved ? 'bookmark-check' : 'bookmark');
                    lucide.createIcons();
                }
            });
        });

        // ===== PAGINATION =====
        document.querySelectorAll('.page-btn:not(.disabled):not(.active)').forEach(function(btn) {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.page-btn').forEach(function(b) {
                    b.classList.remove('active');
                    b.removeAttribute('aria-current');
                });
                this.classList.add('active');
                this.setAttribute('aria-current', 'page');
                // Scroll to top of results
                window.scrollTo({ top: 300, behavior: 'smooth' });
            });
        });

        // ===== SCROLL REVEAL =====
        var prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        if (!prefersReducedMotion) {
            var revealEls = document.querySelectorAll('.reveal');
            var observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('revealed');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.05, rootMargin: '0px 0px -30px 0px' });
            revealEls.forEach(function(el) { observer.observe(el); });
        } else {
            document.querySelectorAll('.reveal').forEach(function(el) {
                el.classList.add('revealed');
            });
        }
    </script>

</body>
</html>
