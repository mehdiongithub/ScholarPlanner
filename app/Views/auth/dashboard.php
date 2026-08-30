<?php
// PHP helpers for dashboard dates and user info
$user = $user ?? [];
$first_name = $user['first_name'] ?? 'Student';
$last_name = $user['last_name'] ?? '';
$fullName = trim($first_name . ' ' . $last_name);

// Circular initials generator
$firstInitial = mb_substr($first_name, 0, 1);
$lastInitial = mb_substr($last_name ?: 'S', 0, 1);
$initials = strtoupper($firstInitial . $lastInitial);

// Greeting calculation
$hour = (int)date('H');
$greeting = 'Good morning';
if ($hour >= 12 && $hour < 17) {
    $greeting = 'Good afternoon';
} elseif ($hour >= 17) {
    $greeting = 'Good evening';
}

// Deadline days left text helper
function getDaysLeftText(string $deadlineDate): array {
    $deadlineUnix = strtotime($deadlineDate);
    $todayUnix = strtotime(date('Y-m-d'));
    $diffSec = $deadlineUnix - $todayUnix;
    $days = (int)round($diffSec / 86400);
    
    if ($days < 0) {
        return ['text' => 'Deadline Passed', 'class' => 'passed'];
    } elseif ($days === 0) {
        return ['text' => 'Closing Today', 'class' => 'today'];
    } elseif ($days === 1) {
        return ['text' => '1 day left', 'class' => 'urgent'];
    } elseif ($days <= 7) {
        return ['text' => $days . ' days left', 'class' => 'urgent'];
    } else {
        return ['text' => $days . ' days left', 'class' => 'normal'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — ScholarMatch</title>
    <meta name="description" content="Your scholarship dashboard. Get personalized alerts.">
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
            --whatsapp-bg: #dcf8c6;
            --whatsapp-light: #e8f8ec;
            --easypaisa: #37b44e;
            --jazzcash: #e31e25;
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
            --shadow-xl: 0 20px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.04);
            --radius-sm: 6px;
            --radius-md: 8px;
            --radius-lg: 12px;
            --radius-xl: 16px;
            --radius-2xl: 20px;
            --font: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            --sidebar-width: 280px;
            --sidebar-collapsed: 0px;
            --header-h: 70px;
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

        /* ===== DASHBOARD LAYOUT ===== */
        .dash-layout {
            display: flex;
            min-height: 100vh;
        }

        /* ===== SIDEBAR ===== */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--text-900);
            color: #fff;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            z-index: 100;
            display: flex;
            flex-direction: column;
            transition: transform 300ms ease;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .sidebar-header {
            padding: 24px 20px 16px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            flex-shrink: 0;
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: #fff;
        }

        .sidebar-logo-icon {
            width: 36px;
            height: 36px;
            background: var(--primary);
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            flex-shrink: 0;
        }

        .sidebar-logo-icon i { width: 18px; height: 18px; }

        .sidebar-logo-text {
            font-size: 1.125rem;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .sidebar-user-card {
            padding: 16px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            background: rgba(255,255,255,0.02);
            flex-shrink: 0;
        }

        .sidebar-user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 8px;
        }

        .sidebar-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary);
            color: #fff;
            font-weight: 600;
            font-size: 0.9375rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid rgba(255,255,255,0.15);
            flex-shrink: 0;
        }

        .sidebar-user-details {
            overflow: hidden;
        }

        .sidebar-username {
            font-size: 0.875rem;
            font-weight: 600;
            color: #fff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sidebar-completion-label {
            font-size: 0.75rem;
            color: var(--text-400);
        }

        .sidebar-progress-container {
            height: 6px;
            background: rgba(255,255,255,0.1);
            border-radius: 100px;
            overflow: hidden;
        }

        .sidebar-progress-bar {
            height: 100%;
            background: var(--primary-light);
            border-radius: 100px;
        }

        .sidebar-nav {
            padding: 16px 12px;
            flex: 1;
        }

        .sidebar-nav-label {
            font-size: 0.6875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: rgba(255,255,255,0.35);
            padding: 12px 12px 6px;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            font-weight: 500;
            color: rgba(255,255,255,0.65);
            transition: all 150ms ease;
            margin-bottom: 2px;
            white-space: nowrap;
        }

        .sidebar-link i { width: 18px; height: 18px; flex-shrink: 0; }

        .sidebar-link:hover {
            background: rgba(255,255,255,0.06);
            color: #fff;
        }

        .sidebar-link.active {
            background: rgba(59,130,246,0.12);
            color: var(--primary-light);
            font-weight: 600;
        }

        .sidebar-link .sidebar-badge {
            margin-left: auto;
            font-size: 0.6875rem;
            font-weight: 600;
            padding: 2px 7px;
            border-radius: 100px;
            background: var(--primary-light);
            color: #fff;
        }

        .sidebar-link-whatsapp {
            background: rgba(37,211,102,0.08);
            border: 1px solid rgba(37,211,102,0.15);
            color: var(--whatsapp) !important;
        }

        .sidebar-link-whatsapp:hover {
            background: rgba(37,211,102,0.15);
            color: #fff !important;
        }

        .sidebar-link-whatsapp::after {
            content: 'PRO';
            margin-left: auto;
            font-size: 0.5625rem;
            font-weight: 700;
            padding: 1px 5px;
            border-radius: 3px;
            background: var(--whatsapp);
            color: #fff;
        }

        .sidebar-footer {
            padding: 16px 20px;
            border-top: 1px solid rgba(255,255,255,0.08);
            background: rgba(0,0,0,0.1);
            flex-shrink: 0;
        }

        /* ===== MOBILE SIDEBAR OVERLAY ===== */
        .sidebar-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 99;
            opacity: 0;
            visibility: hidden;
            transition: all 300ms ease;
        }

        .sidebar-overlay.active { opacity: 1; visibility: visible; }

        /* ===== MAIN CONTENT ===== */
        .dash-main {
            flex: 1;
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ===== TOP HEADER ===== */
        .dash-header {
            height: var(--header-h);
            background: var(--bg-white);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 clamp(16px, 3vw, 32px);
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .dash-header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .mobile-menu-btn {
            display: none;
            width: 40px;
            height: 40px;
            border-radius: var(--radius-sm);
            align-items: center;
            justify-content: center;
            color: var(--text-600);
            transition: background 150ms;
        }

        .mobile-menu-btn:hover { background: var(--bg-50); }
        .mobile-menu-btn i { width: 22px; height: 22px; }

        .dash-header-welcome {
            display: flex;
            flex-direction: column;
        }

        .dash-header-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--text-900);
        }

        .dash-header-subtitle {
            font-size: 0.8125rem;
            color: var(--text-500);
        }

        .dash-header-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .dash-header-btn {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-500);
            transition: all 150ms;
            position: relative;
            border: 1px solid var(--border-light);
        }

        .dash-header-btn:hover { background: var(--bg-50); color: var(--text-700); }
        .dash-header-btn i { width: 18px; height: 18px; }

        .dash-header-btn .notif-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            min-width: 18px;
            height: 18px;
            border-radius: 50%;
            background: var(--jazzcash);
            color: #fff;
            font-size: 0.6875rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid var(--bg-white);
            padding: 0 3px;
        }

        .user-nav-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-50);
            color: var(--primary);
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--primary-100);
        }

        /* ===== DASH CONTENT ===== */
        .dash-content {
            flex: 1;
            padding: clamp(20px, 3vw, 32px);
        }

        /* ===== PROFILE COMPLETION CARD ===== */
        .profile-completion-card {
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            padding: 20px 24px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            box-shadow: var(--shadow-xs);
        }

        .completion-left {
            flex: 1;
        }

        .completion-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .completion-title {
            font-size: 0.9375rem;
            font-weight: 600;
            color: var(--text-900);
        }

        .completion-percent {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--primary);
        }

        .progress-bar-container {
            height: 8px;
            background: var(--bg-100);
            border-radius: 100px;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-light), var(--primary));
            border-radius: 100px;
            transition: width 500ms ease-out;
        }

        .completion-desc {
            font-size: 0.8125rem;
            color: var(--text-500);
            margin-top: 6px;
        }

        /* ===== STATS ROW ===== */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        @media (min-width: 768px) {
            .stats-row { grid-template-columns: repeat(4, 1fr); }
        }

        .stat-card {
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 18px;
            transition: all 200ms ease;
            box-shadow: var(--shadow-xs);
        }

        .stat-card:hover { box-shadow: var(--shadow-sm); }

        .stat-card-icon {
            width: 36px;
            height: 36px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
        }

        .stat-card-icon i { width: 18px; height: 18px; }

        .stat-card-icon.blue { background: var(--primary-50); color: var(--primary); }
        .stat-card-icon.green { background: var(--accent-50); color: var(--accent); }
        .stat-card-icon.amber { background: #fef3c7; color: #d97706; }
        .stat-card-icon.red { background: #fef2f2; color: #dc2626; }

        .stat-card-value {
            font-size: 1.625rem;
            font-weight: 700;
            color: var(--text-900);
            line-height: 1.2;
            margin-bottom: 2px;
        }

        .stat-card-label {
            font-size: 0.8125rem;
            color: var(--text-500);
            font-weight: 500;
        }

        /* ===== WHATSAPP PROMO BLOCK ===== */
        .wa-promo {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 40%, #ecfdf5 100%);
            border: 1px solid rgba(37,211,102,0.25);
            border-radius: var(--radius-xl);
            padding: clamp(24px, 4vw, 36px);
            margin-bottom: 24px;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-xs);
        }

        .wa-promo::before {
            content: '';
            position: absolute;
            top: -60px;
            right: -40px;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(37,211,102,0.06);
            pointer-events: none;
        }

        .wa-promo-inner {
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
            align-items: center;
            position: relative;
            z-index: 1;
        }

        @media (min-width: 992px) {
            .wa-promo-inner {
                grid-template-columns: 1fr 240px;
                gap: 40px;
            }
        }

        .wa-promo-content h2 {
            font-size: clamp(1.25rem, 2.5vw, 1.5rem);
            font-weight: 700;
            color: var(--text-900);
            margin-bottom: 8px;
            line-height: 1.3;
        }

        .wa-promo-content h2 .wa-highlight {
            color: var(--whatsapp-dark);
        }

        .wa-promo-content > p {
            font-size: 0.9375rem;
            color: var(--text-600);
            margin-bottom: 18px;
            max-width: 550px;
        }

        .wa-benefits {
            display: grid;
            grid-template-columns: 1fr;
            gap: 8px;
            margin-bottom: 20px;
        }

        @media (min-width: 600px) {
            .wa-benefits { grid-template-columns: repeat(2, 1fr); }
        }

        .wa-benefit {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.8125rem;
            color: var(--text-700);
            font-weight: 500;
        }

        .wa-benefit-icon {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--whatsapp);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .wa-benefit-icon i { width: 12px; height: 12px; }

        .wa-promo-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 16px;
        }

        .wa-price-tag {
            display: flex;
            align-items: baseline;
            gap: 4px;
        }

        .wa-price-amount {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-900);
        }

        .wa-price-period {
            font-size: 0.8125rem;
            color: var(--text-500);
        }

        .wa-price-original {
            font-size: 0.8125rem;
            color: var(--text-400);
            text-decoration: line-through;
            margin-left: 4px;
        }

        /* Phone mockup */
        .wa-phone-mockup {
            display: none;
        }

        @media (min-width: 992px) {
            .wa-phone-mockup { display: block; }
        }

        .wa-mockup {
            width: 240px;
            background: #e5ddd5;
            border-radius: 20px;
            padding: 12px;
            box-shadow: var(--shadow-lg);
            position: relative;
            flex-shrink: 0;
            border: 4px solid var(--text-900);
        }

        .wa-mockup-header {
            background: #075e54;
            color: #fff;
            padding: 8px 10px;
            border-radius: 12px 12px 0 0;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .wa-mockup-avatar {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }

        .wa-mockup-avatar i { width: 12px; height: 12px; }

        .wa-mockup-name { font-size: 0.6875rem; font-weight: 600; }

        .wa-mockup-body { padding: 8px 4px; }

        .wa-mockup-bubble {
            background: #dcf8c6;
            border-radius: 0 10px 10px 10px;
            padding: 8px 10px;
            box-shadow: 0 1px 1px rgba(0,0,0,0.06);
            margin-bottom: 6px;
            position: relative;
            max-width: 95%;
        }

        .wa-mockup-bubble::before {
            content: '';
            position: absolute;
            top: 0;
            left: -6px;
            border-style: solid;
            border-width: 0 6px 6px 0;
            border-color: transparent #dcf8c6 transparent transparent;
        }

        .wa-mockup-title {
            font-size: 0.6875rem;
            font-weight: 700;
            color: #1a3a1a;
            margin-bottom: 2px;
        }

        .wa-mockup-match {
            display: inline-block;
            font-size: 0.5625rem;
            font-weight: 700;
            color: var(--whatsapp-dark);
            background: rgba(0,0,0,0.05);
            padding: 0px 4px;
            border-radius: 100px;
            margin-bottom: 2px;
        }

        .wa-mockup-detail {
            font-size: 0.625rem;
            color: #2d4a2d;
            line-height: 1.3;
        }

        .wa-mockup-time {
            text-align: right;
            font-size: 0.5rem;
            color: #888;
            margin-top: 2px;
        }

        .wa-mockup-input {
            background: var(--bg-white);
            border-radius: 20px;
            padding: 6px 10px;
            display: flex;
            align-items: center;
            gap: 4px;
            margin-top: 4px;
        }

        .wa-mockup-input-text {
            font-size: 0.625rem;
            color: var(--text-400);
            flex: 1;
        }

        .wa-mockup-input-icon {
            color: var(--whatsapp);
        }

        .wa-mockup-input-icon i { width: 12px; height: 12px; }

        /* ===== GRID LAYOUTS ===== */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }

        @media (min-width: 1200px) {
            .dashboard-grid {
                grid-template-columns: 2fr 1fr;
            }
        }

        .bottom-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }

        @media (min-width: 992px) {
            .bottom-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        /* ===== CARDS & TABLES ===== */
        .section-card {
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            overflow: hidden;
            box-shadow: var(--shadow-xs);
        }

        .section-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-light);
            flex-wrap: wrap;
            gap: 12px;
        }

        .section-card-title {
            font-size: 0.9375rem;
            font-weight: 600;
            color: var(--text-900);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-card-title i { width: 18px; height: 18px; color: var(--text-400); }

        .section-card-count {
            font-size: 0.75rem;
            font-weight: 600;
            background: var(--primary-50);
            color: var(--primary);
            padding: 2px 8px;
            border-radius: 100px;
        }

        .dash-table-wrap {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .dash-table {
            width: 100%;
            border-collapse: collapse;
        }

        .dash-table th {
            text-align: left;
            font-size: 0.6875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--text-400);
            padding: 12px 20px;
            border-bottom: 1px solid var(--border-light);
            background: var(--bg-50);
            white-space: nowrap;
        }

        .dash-table td {
            padding: 14px 20px;
            font-size: 0.875rem;
            color: var(--text-600);
            border-bottom: 1px solid var(--border-light);
            vertical-align: middle;
        }

        .dash-table tbody tr:hover {
            background: var(--bg-50);
        }

        .dash-table tbody tr:last-child td {
            border-bottom: none;
        }

        .table-scholarship-name {
            font-weight: 600;
            color: var(--text-900);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 220px;
        }

        .table-match {
            font-size: 0.75rem;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 100px;
            display: inline-block;
            white-space: nowrap;
        }

        .table-match.high { color: var(--accent); background: var(--accent-50); }
        .table-match.medium { color: #d97706; background: #fef3c7; }
        .table-match.low { color: #dc2626; background: #fef2f2; }

        .table-tag {
            font-size: 0.6875rem;
            font-weight: 500;
            color: var(--text-600);
            background: var(--bg-100);
            padding: 2px 8px;
            border-radius: 100px;
            white-space: nowrap;
            border: 1px solid var(--border);
        }

        .table-deadline {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            white-space: nowrap;
            font-size: 0.8125rem;
        }

        .table-deadline i { width: 14px; height: 14px; color: var(--text-400); }
        .table-deadline.urgent { color: #dc2626; font-weight: 600; }
        .table-deadline.urgent i { color: #dc2626; }

        /* ===== DEADLINE BADGES ===== */
        .deadline-badge {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 100px;
            white-space: nowrap;
        }

        .deadline-badge.urgent { background: #fef2f2; color: #dc2626; border: 1px solid #fee2e2; }
        .deadline-badge.today { background: #fffbeb; color: #d97706; border: 1px solid #fef3c7; }
        .deadline-badge.normal { background: var(--primary-50); color: var(--primary); border: 1px solid var(--primary-100); }
        .deadline-badge.passed { background: var(--bg-100); color: var(--text-400); }

        /* ===== STATUS BADGES ===== */
        .status-badge {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 100px;
            display: inline-block;
            white-space: nowrap;
        }

        .status-interested { background: var(--bg-100); color: var(--text-600); }
        .status-planning { background: var(--primary-50); color: var(--primary); }
        .status-documents-pending { background: #fffbeb; color: #d97706; }
        .status-ready-to-apply { background: var(--accent-50); color: var(--accent); }
        .status-applied { background: #f5f3ff; color: #6d28d9; }
        .status-interview { background: #ecfeff; color: #0891b2; }
        .status-accepted { background: #f0fdf4; color: #16a34a; }
        .status-rejected { background: #fef2f2; color: #dc2626; }
        .status-withdrawn { background: var(--bg-100); color: var(--text-400); }

        /* ===== BUTTONS ===== */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            padding: 10px 20px;
            border-radius: var(--radius-md);
            transition: all 150ms ease;
            white-space: nowrap;
            min-height: 38px;
            line-height: 1.2;
        }

        .btn-primary { background: var(--primary); color: #fff; box-shadow: 0 1px 2px rgba(30,64,175,0.15); }
        .btn-primary:hover { background: var(--primary-dark); }

        .btn-secondary { background: var(--bg-white); color: var(--text-700); border: 1px solid var(--border); }
        .btn-secondary:hover { background: var(--bg-50); border-color: var(--text-400); }

        .btn-whatsapp {
            background: var(--whatsapp);
            color: #fff;
            font-weight: 600;
            font-size: 0.9375rem;
            padding: 10px 20px;
            border-radius: var(--radius-md);
            box-shadow: 0 2px 4px rgba(37,211,102,0.15);
        }

        .btn-whatsapp:hover {
            background: #1fb855;
            transform: translateY(-1px);
        }

        .btn-whatsapp i { width: 16px; height: 16px; }

        .btn-ghost {
            background: transparent;
            color: var(--primary);
            font-weight: 600;
            font-size: 0.8125rem;
            padding: 6px 12px;
            min-height: 32px;
            border-radius: var(--radius-sm);
        }

        .btn-ghost:hover { background: var(--primary-50); }

        .btn-sm { font-size: 0.8125rem; padding: 6px 14px; min-height: 32px; }

        /* ===== EMPTY STATES ===== */
        .empty-state {
            text-align: center;
            padding: 36px 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .empty-state-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: var(--bg-50);
            color: var(--text-400);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
            border: 1px solid var(--border-light);
        }

        .empty-state-icon i { width: 22px; height: 22px; stroke-width: 1.5; }

        .empty-state h3 {
            font-size: 0.9375rem;
            font-weight: 600;
            color: var(--text-900);
            margin-bottom: 6px;
        }

        .empty-state p {
            font-size: 0.8125rem;
            color: var(--text-500);
            margin-bottom: 16px;
            max-width: 320px;
            line-height: 1.5;
        }

        /* ===== MODALS ===== */
        .modal-overlay {
            position: fixed;
            inset: 0;
            z-index: 200;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            opacity: 0;
            visibility: hidden;
            transition: all 300ms ease;
        }

        .modal-overlay.active { opacity: 1; visibility: visible; }

        .modal {
            background: var(--bg-white);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-xl);
            width: 100%;
            max-width: 440px;
            max-height: 90vh;
            overflow-y: auto;
            transform: translateY(20px) scale(0.97);
            transition: transform 300ms ease;
        }

        .modal-overlay.active .modal {
            transform: translateY(0) scale(1);
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 20px 0;
        }

        .modal-header h3 {
            font-size: 1.0625rem;
            font-weight: 700;
            color: var(--text-900);
        }

        .modal-close {
            width: 32px;
            height: 32px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-400);
            transition: all 150ms;
        }

        .modal-close:hover { background: var(--bg-50); color: var(--text-700); }
        .modal-close i { width: 16px; height: 16px; }

        .modal-body { padding: 16px 20px 20px; }

        .modal-features {
            display: flex;
            flex-direction: column;
            gap: 10px;
            background: var(--bg-50);
            border-radius: var(--radius-lg);
            padding: 14px;
            margin-bottom: 16px;
            border: 1px solid var(--border-light);
        }

        .modal-feature {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.8125rem;
            color: var(--text-700);
            font-weight: 500;
        }

        .modal-feature i { width: 16px; height: 16px; color: var(--accent); flex-shrink: 0; }

        /* Phone input */
        .phone-input-group {
            display: flex;
            align-items: stretch;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-md);
            overflow: hidden;
            transition: border-color 200ms;
            margin-bottom: 8px;
        }

        .phone-input-group:focus-within {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }

        .phone-code {
            display: flex;
            align-items: center;
            gap: 4px;
            padding: 0 12px;
            background: var(--bg-50);
            border-right: 1px solid var(--border);
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-700);
        }

        .phone-input {
            border: none;
            outline: none;
            padding: 10px 12px;
            font-size: 0.9375rem;
            width: 100%;
            font-weight: 500;
            color: var(--text-900);
        }

        .phone-hint {
            font-size: 0.75rem;
            color: var(--text-400);
            display: flex;
            align-items: center;
            gap: 4px;
            margin-bottom: 12px;
        }

        .phone-hint i { width: 13px; height: 13px; }

        .phone-error {
            font-size: 0.75rem;
            color: #b91c1c;
            display: none;
            align-items: center;
            gap: 4px;
            margin-bottom: 12px;
            font-weight: 500;
        }

        .phone-error.visible { display: flex; }
        .phone-error i { width: 13px; height: 13px; }

        .modal-price-summary {
            border-top: 1px solid var(--border-light);
            padding-top: 12px;
            margin-bottom: 16px;
        }

        .modal-price-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.8125rem;
            color: var(--text-500);
            padding: 3px 0;
        }

        .modal-price-row .label {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .modal-price-row .label i { width: 14px; height: 14px; }

        .modal-price-row.total {
            border-top: 1px dashed var(--border);
            margin-top: 6px;
            padding-top: 10px;
            font-size: 0.9375rem;
            font-weight: 700;
            color: var(--text-900);
        }

        /* ===== CHECKOUT PAGE ===== */
        .checkout-page {
            display: none;
        }

        .checkout-page.active { display: block; }
        .dashboard-page.hidden { display: none; }

        .checkout-back {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-500);
            padding: 8px 0;
            margin-bottom: 16px;
            transition: color 150ms;
        }

        .checkout-back:hover { color: var(--text-900); }
        .checkout-back i { width: 16px; height: 16px; }

        .checkout-layout {
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
        }

        @media (min-width: 992px) {
            .checkout-layout {
                grid-template-columns: 1fr 340px;
            }
        }

        .checkout-methods {
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            overflow: hidden;
            box-shadow: var(--shadow-xs);
        }

        .checkout-methods-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-light);
        }

        .checkout-methods-header h2 {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-900);
            margin-bottom: 2px;
        }

        .checkout-methods-header p {
            font-size: 0.8125rem;
            color: var(--text-500);
        }

        .checkout-method {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-light);
            cursor: pointer;
            transition: background 150ms;
        }

        .checkout-method:last-child { border-bottom: none; }
        .checkout-method:hover { background: var(--bg-50); }

        .checkout-method.selected {
            background: var(--bg-50);
            border-left: 3px solid var(--primary);
        }

        .checkout-method-top {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 6px;
        }

        .checkout-method-radio {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            border: 2px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: all 150ms;
        }

        .checkout-method.selected .checkout-method-radio {
            border-color: var(--primary);
            background: var(--primary);
        }

        .checkout-method.selected .checkout-method-radio::after {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #fff;
        }

        .checkout-method-logo {
            height: 28px;
            display: flex;
            align-items: center;
            flex-shrink: 0;
        }

        .checkout-method-logo-easypaisa {
            width: 90px;
            height: 26px;
            background: var(--easypaisa);
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .checkout-method-logo-jazzcash {
            width: 90px;
            height: 26px;
            background: var(--jazzcash);
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .checkout-method-name {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-900);
        }

        .checkout-method-desc {
            font-size: 0.8125rem;
            color: var(--text-500);
            margin-left: 30px;
            line-height: 1.4;
        }

        .checkout-summary {
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            padding: 20px;
            box-shadow: var(--shadow-xs);
        }

        .checkout-summary h3 {
            font-size: 0.9375rem;
            font-weight: 700;
            color: var(--text-900);
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--border-light);
        }

        .checkout-summary-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 0;
            font-size: 0.8125rem;
            color: var(--text-600);
        }

        .checkout-summary-item.total {
            border-top: 1px solid var(--border);
            margin-top: 6px;
            padding-top: 10px;
            font-weight: 700;
            color: var(--text-900);
            font-size: 1rem;
        }

        .checkout-summary-phone {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 10px;
            background: var(--whatsapp-light);
            border-radius: var(--radius-md);
            margin: 12px 0;
            font-size: 0.8125rem;
            color: var(--whatsapp-dark);
            font-weight: 600;
        }

        .checkout-summary-phone i { width: 16px; height: 16px; color: var(--whatsapp); }

        .checkout-summary-features {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 16px;
            margin-top: 12px;
        }

        .checkout-summary-feature {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.75rem;
            color: var(--text-500);
        }

        .checkout-summary-feature i { width: 14px; height: 14px; color: var(--accent); flex-shrink: 0; }

        .checkout-summary .btn { width: 100%; }

        .checkout-secure {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            font-size: 0.6875rem;
            color: var(--text-400);
            margin-top: 10px;
            font-weight: 500;
        }

        .checkout-secure i { width: 12px; height: 12px; }

        /* SUCCESS STATE */
        .checkout-success {
            display: none;
            text-align: center;
            padding: 36px 16px;
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-sm);
        }

        .checkout-success.active { display: block; }

        .checkout-success-icon {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: var(--accent-50);
            color: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
        }

        .checkout-success-icon i { width: 28px; height: 28px; }

        .checkout-success h2 {
            font-size: 1.25rem;
            margin-bottom: 6px;
            font-weight: 700;
        }

        .checkout-success p {
            font-size: 0.875rem;
            color: var(--text-500);
            max-width: 380px;
            margin: 0 auto 20px;
            line-height: 1.5;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 1023px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.open { transform: translateX(0); }
            .dash-main { margin-left: 0; }
            .mobile-menu-btn { display: flex; }
        }

        @media (max-width: 599px) {
            .stats-row { grid-template-columns: 1fr 1fr; gap: 12px; }
            .stat-card { padding: 14px; }
            .stat-card-value { font-size: 1.375rem; }
            .wa-promo { padding: 20px; }
            .wa-price-amount { font-size: 1.25rem; }
            .btn-whatsapp { padding: 10px 16px; font-size: 0.875rem; }
            .dash-table th, .dash-table td { padding: 10px 12px; }
            .profile-completion-card { flex-direction: column; align-items: stretch; gap: 14px; }
        }

        @media (max-width: 280px) {
            .stats-row { grid-template-columns: 1fr; }
            .wa-promo-actions { flex-direction: column; align-items: flex-start; gap: 10px; }
            .sidebar { width: 240px; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                transition-duration: 0.01ms !important;
                animation-duration: 0.01ms !important;
            }
            html { scroll-behavior: auto; }
        }
    </style>
</head>
<body>

<div class="dash-layout">

    <!-- ===== SIDEBAR ===== -->
    <aside class="sidebar" id="sidebar" role="navigation" aria-label="Dashboard navigation">
        <div class="sidebar-header">
            <a href="/" class="sidebar-logo">
                <div class="sidebar-logo-icon"><i data-lucide="graduation-cap"></i></div>
                <span class="sidebar-logo-text">ScholarMatch</span>
            </a>
        </div>

        <!-- User profile mini-card -->
        <div class="sidebar-user-card">
            <div class="sidebar-user-info">
                <div class="sidebar-avatar"><?= e($initials) ?></div>
                <div class="sidebar-user-details">
                    <div class="sidebar-username"><?= e($fullName) ?></div>
                    <div class="sidebar-completion-label">Profile <?= (int)$completion ?>% complete</div>
                </div>
            </div>
            <div class="sidebar-progress-container">
                <div class="sidebar-progress-bar" style="width: <?= (int)$completion ?>%"></div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="sidebar-nav-label">Main</div>
            <a href="/dashboard" class="sidebar-link active">
                <i data-lucide="layout-dashboard"></i>
                Dashboard
            </a>
            <a href="/scholarships" class="sidebar-link">
                <i data-lucide="graduation-cap"></i>
                Scholarships
            </a>
            <a href="#matches-section" class="sidebar-link">
                <i data-lucide="target"></i>
                My Matches
                <?php if ($matchesCount > 0): ?>
                    <span class="sidebar-badge"><?= $matchesCount ?></span>
                <?php endif; ?>
            </a>
            <a href="/saved-scholarships" class="sidebar-link">
                <i data-lucide="bookmark"></i>
                Saved Scholarships
            </a>
            <a href="/applications" class="sidebar-link">
                <i data-lucide="file-check"></i>
                Applications
            </a>

            <div class="sidebar-nav-label" style="margin-top:8px">Alerts & Billing</div>
            <a href="#" class="sidebar-link sidebar-link-whatsapp" id="sidebarWaLink">
                <i data-lucide="message-square"></i>
                WhatsApp Alerts
            </a>
            <a href="/billing" class="sidebar-link">
                <i data-lucide="credit-card"></i>
                Subscription
            </a>

            <div class="sidebar-nav-label" style="margin-top:8px">Settings</div>
            <a href="/profile/edit" class="sidebar-link">
                <i data-lucide="user"></i>
                Profile settings
            </a>
            <a href="#" onclick="event.preventDefault(); document.getElementById('logoutForm').submit();" class="sidebar-link" style="margin-top:8px; color:rgba(255,255,255,0.4)">
                <i data-lucide="log-out"></i>
                Log Out
            </a>
        </nav>

        <div class="sidebar-footer">
            <span style="font-size:0.6875rem; color:rgba(255,255,255,0.35);">ScholarMatch Student Hub v2.0</span>
        </div>
    </aside>

    <!-- Sidebar overlay (mobile drawer backdrop) -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- ===== MAIN CONTENT ===== -->
    <div class="dash-main">

        <!-- Topbar -->
        <header class="dash-header">
            <div class="dash-header-left">
                <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Open menu">
                    <i data-lucide="menu"></i>
                </button>
                <div class="dash-header-welcome">
                    <span class="dash-header-title"><?= e($greeting) ?>, <?= e($first_name) ?>!</span>
                    <span class="dash-header-subtitle">Find scholarships that match your academic goals.</span>
                </div>
            </div>
            <div class="dash-header-right">
                <button class="dash-header-btn" aria-label="Settings" onclick="window.location.href='/profile/edit'">
                    <i data-lucide="settings"></i>
                </button>
                <div class="user-nav-avatar"><?= e($initials) ?></div>
            </div>
        </header>

        <!-- ===== DASHBOARD PAGE ===== -->
        <div class="dash-content dashboard-page" id="dashboardPage">

            <!-- Profile Completion Banner -->
            <div class="profile-completion-card">
                <div class="completion-left">
                    <div class="completion-meta">
                        <span class="completion-title">Profile Strength</span>
                        <span class="completion-percent"><?= (int)$completion ?>%</span>
                    </div>
                    <div class="progress-bar-container">
                        <div class="progress-bar" style="width: <?= (int)$completion ?>%"></div>
                    </div>
                    <div class="completion-desc">
                        <?php if ($completion < 100): ?>
                            Complete your profile parameters to match with more funding opportunities.
                        <?php else: ?>
                            Your profile is complete! The matching engine has verified your eligibility criteria.
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($completion < 100): ?>
                    <a href="/profile/edit" class="btn btn-primary">Complete Profile</a>
                <?php else: ?>
                    <span style="font-size: 0.75rem; font-weight:700; color: var(--accent); background: var(--accent-50); border: 1px solid rgba(5,150,105,0.25); padding: 6px 12px; border-radius: 100px; display: inline-flex; align-items:center; gap: 4px;">
                        <i data-lucide="check" style="width:14px; height:14px;"></i> Profile Complete
                    </span>
                <?php endif; ?>
            </div>

            <!-- Statistics Summary Cards -->
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-card-icon blue"><i data-lucide="target"></i></div>
                    <div class="stat-card-value"><?= $matchesCount ?></div>
                    <div class="stat-card-label">Scholarship Matches</div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-icon green"><i data-lucide="bookmark"></i></div>
                    <div class="stat-card-value"><?= $savedCount ?></div>
                    <div class="stat-card-label">Saved Bookmarks</div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-icon amber"><i data-lucide="file-check"></i></div>
                    <div class="stat-card-value"><?= $appsCount ?></div>
                    <div class="stat-card-label">Active Trackers</div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-icon red"><i data-lucide="calendar"></i></div>
                    <div class="stat-card-value"><?= $deadlinesCount ?></div>
                    <div class="stat-card-label">Upcoming Deadlines</div>
                </div>
            </div>

            <!-- WhatsApp Alert Promotion Conversion Block -->
            <div class="wa-promo" id="waPromo">
                <div class="wa-promo-inner">
                    <div class="wa-promo-content">
                        <h2>Never Miss a Scholarship.<br><span class="wa-highlight">Get Alerts directly on WhatsApp.</span></h2>
                        <p>Connect your phone number and receive instant notifications when matching scholarships are added or closing soon. No login required — alerts come straight to your chat.</p>

                        <div class="wa-benefits">
                            <div class="wa-benefit">
                                <div class="wa-benefit-icon"><i data-lucide="check"></i></div>
                                Instant alerts for new eligibility matches
                            </div>
                            <div class="wa-benefit">
                                <div class="wa-benefit-icon"><i data-lucide="check"></i></div>
                                Urgent closing date reminders
                            </div>
                            <div class="wa-benefit">
                                <div class="wa-benefit-icon"><i data-lucide="check"></i></div>
                                Dynamic score updates when rules change
                            </div>
                            <div class="wa-benefit">
                                <div class="wa-benefit-icon"><i data-lucide="check"></i></div>
                                Cancel subscription at any time
                            </div>
                        </div>

                        <div class="wa-promo-actions">
                            <button class="btn btn-whatsapp" id="openWaModal" type="button">
                                <i data-lucide="message-square"></i>
                                Activate WhatsApp Alerts
                            </button>
                            <div class="wa-price-tag">
                                <span class="wa-price-amount">PKR 1,499</span>
                                <span class="wa-price-period">/month</span>
                                <span class="wa-price-original">PKR 2,499</span>
                            </div>
                        </div>
                    </div>

                    <!-- Phone Mockup -->
                    <div class="wa-phone-mockup">
                        <div class="wa-mockup">
                            <div class="wa-mockup-header">
                                <div class="wa-mockup-avatar"><i data-lucide="graduation-cap"></i></div>
                                <div class="wa-mockup-name">ScholarMatch Alerts</div>
                            </div>
                            <div class="wa-mockup-body">
                                <div class="wa-mockup-bubble">
                                    <div class="wa-mockup-title">🎓 New Scholarship Match</div>
                                    <div class="wa-mockup-match">94% Match Score</div>
                                    <div class="wa-mockup-detail">UK Commonwealth Master's Program<br>Fully Funded · Closes 15 Oct</div>
                                    <div class="wa-mockup-time">10:32 AM</div>
                                </div>
                                <div class="wa-mockup-bubble" style="animation: mockFloat 3s ease-in-out infinite;">
                                    <div class="wa-mockup-title">⏰ Deadline Reminder</div>
                                    <div class="wa-mockup-detail">HEC Germany Scholarship closes in 5 days!</div>
                                    <div class="wa-mockup-time">2:15 PM</div>
                                </div>
                            </div>
                            <div class="wa-mockup-input">
                                <span class="wa-mockup-input-text">Type a message</span>
                                <span class="wa-mockup-input-icon"><i data-lucide="send"></i></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dashboard Columns Grid (Matches & Deadlines) -->
            <div class="dashboard-grid">
                
                <!-- Matches Table Card -->
                <div class="section-card" id="matches-section">
                    <div class="section-card-header">
                        <span class="section-card-title">
                            <i data-lucide="target"></i>
                            Recommended Scholarships
                            <span class="section-card-count"><?= count($matches) ?></span>
                        </span>
                        <a href="/scholarships" class="btn btn-ghost btn-sm">Explore All</a>
                    </div>
                    
                    <div class="matches-list-container">
                        <?php if (empty($matches)): ?>
                            <div class="empty-state">
                                <div class="empty-state-icon"><i data-lucide="award"></i></div>
                                <h3>No Scholarship Matches</h3>
                                <p>We couldn't find matching scholarships. Update your education details or preferred degree levels to match.</p>
                                <a href="/profile/edit" class="btn btn-primary">Update Academic History</a>
                            </div>
                        <?php else: ?>
                            <div class="dash-table-wrap">
                                <table class="dash-table">
                                    <thead>
                                        <tr>
                                            <th>Scholarship</th>
                                            <th>Country</th>
                                            <th>Target Level</th>
                                            <th>Match</th>
                                            <th>Deadline</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($matches, 0, 5) as $m): ?>
                                            <tr>
                                                <td>
                                                    <div class="table-scholarship-name" title="<?= e($m['title']) ?>"><?= e($m['title']) ?></div>
                                                    <div style="font-size:0.75rem; color:var(--text-500);"><?= e($m['provider_name']) ?></div>
                                                </td>
                                                <td><?= e($m['host_country_name'] ?? 'Global') ?></td>
                                                <td><span class="table-tag"><?= e($m['degree_level']) ?></span></td>
                                                <td>
                                                    <?php 
                                                    $score = (int)$m['match_score'];
                                                    $scoreClass = 'high';
                                                    if ($score < 60) $scoreClass = 'low';
                                                    elseif ($score < 80) $scoreClass = 'medium';
                                                    ?>
                                                    <span class="table-match <?= $scoreClass ?>"><?= $score ?>% Match</span>
                                                </td>
                                                <td>
                                                    <?php if (empty($m['application_deadline'])): ?>
                                                        <span style="color:var(--text-400); font-style:italic;">Rolling</span>
                                                    <?php else: ?>
                                                        <?php 
                                                        $dl = getDaysLeftText($m['application_deadline']);
                                                        ?>
                                                        <span class="table-deadline <?= $dl['class'] === 'urgent' ? 'urgent' : '' ?>">
                                                            <i data-lucide="clock"></i>
                                                            <?= e($dl['text']) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div style="display:flex; gap:6px; align-items:center;">
                                                        <a href="/scholarships/<?= e($m['slug']) ?>" class="btn btn-ghost btn-sm" style="padding:4px 8px;">View</a>
                                                        <button class="btn btn-ghost btn-sm btn-bookmark-toggle" data-id="<?= (int)$m['scholarship_id'] ?>" data-saved="<?= $m['is_saved'] ? 'true' : 'false' ?>" aria-label="Bookmark icon" style="padding:4px; min-width:32px;">
                                                            <i data-lucide="<?= $m['is_saved'] ? 'bookmark-check' : 'bookmark' ?>" style="width:16px; height:16px;"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php if (count($matches) > 5): ?>
                                <div style="padding: 10px; text-align: center; border-top: 1px solid var(--border-light)">
                                    <a href="/scholarships" class="btn btn-ghost btn-sm" style="color: var(--primary)">View All Recommended Matches</a>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right Column Widget (Upcoming Deadlines) -->
                <div>
                    <div class="section-card" style="margin-bottom: 24px;">
                        <div class="section-card-header">
                            <span class="section-card-title">
                                <i data-lucide="clock"></i>
                                Upcoming Deadlines
                            </span>
                        </div>
                        <div style="padding: 16px;">
                            <?php if (empty($upcomingDeadlines)): ?>
                                <div style="text-align: center; padding: 24px; color: var(--text-400);">
                                    <i data-lucide="calendar-check" style="width: 32px; height: 32px; margin-bottom: 8px; stroke-width: 1.5; opacity: 0.5;"></i>
                                    <p style="font-size: 0.8125rem;">No closing deadlines found.</p>
                                </div>
                            <?php else: ?>
                                <div style="display: flex; flex-direction: column; gap: 10px;">
                                    <?php foreach ($upcomingDeadlines as $d): ?>
                                        <?php 
                                        $dl = getDaysLeftText($d['application_deadline']);
                                        ?>
                                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 12px; background: var(--bg-50); border-radius: var(--radius-md); border: 1px solid var(--border-light)">
                                            <div style="overflow: hidden; text-overflow: ellipsis; padding-right: 8px;">
                                                <a href="/scholarships/<?= e($d['slug']) ?>" style="font-weight: 600; color: var(--text-900); font-size: 0.8125rem; white-space:nowrap;" class="table-scholarship-name" title="<?= e($d['title']) ?>"><?= e($d['title']) ?></a>
                                                <div style="font-size: 0.6875rem; color: var(--text-500);"><?= e($d['provider_name']) ?></div>
                                            </div>
                                            <span class="deadline-badge <?= e($dl['class']) ?>"><?= e($dl['text']) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Quick Actions Widget -->
                    <div class="section-card">
                        <div class="section-card-header">
                            <span class="section-card-title">
                                <i data-lucide="zap"></i>
                                Quick Actions
                            </span>
                        </div>
                        <div style="padding: 16px; display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
                            <a href="/scholarships" class="btn btn-secondary btn-sm" style="justify-content: flex-start; text-align: left;">
                                <i data-lucide="search" style="width: 14px; height: 14px; color: var(--primary);"></i>
                                Find
                            </a>
                            <a href="/profile/edit" class="btn btn-secondary btn-sm" style="justify-content: flex-start; text-align: left;">
                                <i data-lucide="user" style="width: 14px; height: 14px; color: var(--accent);"></i>
                                Edit Profile
                            </a>
                            <a href="/saved-scholarships" class="btn btn-secondary btn-sm" style="justify-content: flex-start; text-align: left;">
                                <i data-lucide="bookmark" style="width: 14px; height: 14px; color: var(--primary-light);"></i>
                                Bookmarks
                            </a>
                            <a href="/applications" class="btn btn-secondary btn-sm" style="justify-content: flex-start; text-align: left;">
                                <i data-lucide="file-text" style="width: 14px; height: 14px; color: #d97706;"></i>
                                Trackers
                            </a>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Bottom Row Grid (Saved Bookmarks & Application Tracker) -->
            <div class="bottom-grid">
                
                <!-- Saved Bookmarks List -->
                <div class="section-card" id="saved-bookmarks-widget">
                    <div class="section-card-header">
                        <span class="section-card-title">
                            <i data-lucide="bookmark"></i>
                            Saved Scholarships
                        </span>
                        <a href="/saved-scholarships" class="btn btn-ghost btn-sm">Manage</a>
                    </div>
                    <div style="padding: 16px;">
                        <?php if (empty($savedScholarships)): ?>
                            <div class="empty-state" style="padding: 20px 0;">
                                <div class="empty-state-icon"><i data-lucide="bookmark"></i></div>
                                <h3>No Bookmarks Saved</h3>
                                <p>Bookmark interesting scholarships during searches to track them here.</p>
                                <a href="/scholarships" class="btn btn-secondary btn-sm">Search Catalog</a>
                            </div>
                        <?php else: ?>
                            <div style="display: flex; flex-direction: column; gap: 10px;" id="savedListContainer">
                                <?php foreach ($savedScholarships as $s): ?>
                                    <div class="saved-item-row" data-id="<?= (int)$s['scholarship_id'] ?>" style="display: flex; align-items: center; justify-content: space-between; padding: 10px 12px; background: var(--bg-50); border-radius: var(--radius-md); border: 1px solid var(--border-light)">
                                        <div>
                                            <a href="/scholarships/<?= e($s['slug']) ?>" style="font-weight: 600; color: var(--text-900); font-size: 0.8125rem;"><?= e($s['title']) ?></a>
                                            <div style="font-size: 0.6875rem; color: var(--text-500);"><?= e($s['provider_name']) ?> · <?= e($s['host_country_name'] ?? 'Global') ?></div>
                                        </div>
                                        <button class="btn btn-ghost btn-sm btn-unsave-quick" data-id="<?= (int)$s['scholarship_id'] ?>" aria-label="Remove bookmark" style="padding:4px; min-height: unset; height: 28px;">
                                            <i data-lucide="trash-2" style="width: 14px; height: 14px; color: var(--jazzcash)"></i>
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Tracked Applications -->
                <div class="section-card">
                    <div class="section-card-header">
                        <span class="section-card-title">
                            <i data-lucide="file-text"></i>
                            Recent Applications
                        </span>
                        <a href="/applications" class="btn btn-ghost btn-sm">All Application Trackers</a>
                    </div>
                    <div style="padding: 16px;">
                        <?php if (empty($recentApplications)): ?>
                            <div class="empty-state" style="padding: 20px 0;">
                                <div class="empty-state-icon"><i data-lucide="file-plus"></i></div>
                                <h3>No Tracked Applications</h3>
                                <p>Log and track submissions, interviews, and accepted states.</p>
                                <a href="/scholarships" class="btn btn-secondary btn-sm">Explore Scholarships</a>
                            </div>
                        <?php else: ?>
                            <div class="dash-table-wrap">
                                <table class="dash-table">
                                    <thead>
                                        <tr>
                                            <th>Scholarship</th>
                                            <th>Status</th>
                                            <th>Last Update</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentApplications as $a): ?>
                                            <tr>
                                                <td>
                                                    <a href="/scholarships/<?= e($a['slug']) ?>" style="font-weight: 600; color: var(--text-900); font-size: 0.8125rem;" class="table-scholarship-name" title="<?= e($a['title']) ?>"><?= e($a['title']) ?></a>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $statusLabels = [
                                                        'interested' => 'Interested',
                                                        'planning' => 'Planning',
                                                        'documents_pending' => 'Docs Pending',
                                                        'ready_to_apply' => 'Ready to Apply',
                                                        'applied' => 'Applied',
                                                        'interview' => 'Interviewing',
                                                        'accepted' => 'Accepted',
                                                        'rejected' => 'Rejected',
                                                        'withdrawn' => 'Withdrawn'
                                                    ];
                                                    $statusClass = str_replace('_', '-', $a['status']);
                                                    ?>
                                                    <span class="status-badge status-<?= $statusClass ?>">
                                                        <?= e($statusLabels[$a['status']] ?? ucfirst($a['status'])) ?>
                                                    </span>
                                                </td>
                                                <td style="font-size: 0.75rem; color: var(--text-500); white-space:nowrap;">
                                                    <?= date('j M Y', strtotime($a['created_at'])) ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

        </div><!-- /dash-content dashboard-page -->

        <!-- ===== CHECKOUT PAGE ===== -->
        <div class="dash-content checkout-page" id="checkoutPage">
            <a href="#" class="checkout-back" id="checkoutBack">
                <i data-lucide="arrow-left"></i>
                Back to Dashboard
            </a>

            <!-- Success state (hidden by default) -->
            <div class="checkout-success" id="checkoutSuccess">
                <div class="checkout-success-icon">
                    <i data-lucide="circle-check"></i>
                </div>
                <h2>Payment Instructions Sent</h2>
                <p>Follow the instructions sent to your WhatsApp number to complete the payment. Your WhatsApp alerts will be activated once payment is confirmed.</p>
                <a href="#" class="btn btn-primary" id="successBackBtn">Go to Dashboard</a>
            </div>

            <!-- Checkout form -->
            <div id="checkoutForm">
                <div class="checkout-layout">
                    <div>
                        <!-- Payment Methods -->
                        <div class="checkout-methods">
                            <div class="checkout-methods-header">
                                <h2>Select Payment Method</h2>
                                <p>Choose how you'd like to pay for your subscription.</p>
                            </div>

                            <div class="checkout-method selected" data-method="easypaisa" id="methodEasypaisa" role="radio" aria-checked="true" tabindex="0">
                                <div class="checkout-method-top">
                                    <div class="checkout-method-radio"></div>
                                    <div class="checkout-method-logo-easypaisa">Easypaisa</div>
                                    <span class="checkout-method-name" style="margin-left:auto">Easypaisa Wallet</span>
                                </div>
                                <p class="checkout-method-desc">Pay directly from your Easypaisa mobile wallet. You'll receive payment details after selecting this method.</p>
                            </div>

                            <div class="checkout-method" data-method="jazzcash" id="methodJazzcash" role="radio" aria-checked="false" tabindex="0">
                                <div class="checkout-method-top">
                                    <div class="checkout-method-radio"></div>
                                    <div class="checkout-method-logo-jazzcash">JazzCash</div>
                                    <span class="checkout-method-name" style="margin-left:auto">JazzCash Wallet</span>
                                </div>
                                <p class="checkout-method-desc">Pay directly from your JazzCash mobile wallet. You'll receive payment details after selecting this method.</p>
                            </div>
                        </div>

                        <!-- Payment instructions card -->
                        <div class="section-card" style="margin-top:20px" id="paymentInstructions">
                            <div class="section-card-header">
                                <span class="section-card-title">
                                    <i data-lucide="info"></i>
                                    Payment Instructions
                                </span>
                            </div>
                            <div style="padding:20px 24px" id="paymentInstructionsBody">
                                <div style="display:flex;flex-direction:column;gap:12px">
                                    <div style="display:flex;align-items:flex-start;gap:12px;font-size:0.875rem;color:var(--text-600)">
                                        <span style="width:24px;height:24px;border-radius:50%;background:var(--primary-50);color:var(--primary);display:flex;align-items:center;justify-content:center;font-size:0.75rem;font-weight:700;flex-shrink:0">1</span>
                                        <span>Open your <strong id="payMethodName">Easypaisa</strong> app on your phone</span>
                                    </div>
                                    <div style="display:flex;align-items:flex-start;gap:12px;font-size:0.875rem;color:var(--text-600)">
                                        <span style="width:24px;height:24px;border-radius:50%;background:var(--primary-50);color:var(--primary);display:flex;align-items:center;justify-content:center;font-size:0.75rem;font-weight:700;flex-shrink:0">2</span>
                                        <span>Go to <strong>"Send Money"</strong> and enter the account number below</span>
                                    </div>
                                    <div style="display:flex;align-items:flex-start;gap:12px;font-size:0.875rem;color:var(--text-600)">
                                        <span style="width:24px;height:24px;border-radius:50%;background:var(--primary-50);color:var(--primary);display:flex;align-items:center;justify-content:center;font-size:0.75rem;font-weight:700;flex-shrink:0">3</span>
                                        <span>Send exactly <strong>PKR 1,499</strong> to the following number:</span>
                                    </div>
                                    <div style="background:var(--bg-50);border:1px dashed var(--border);border-radius:var(--radius-md);padding:14px 16px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
                                        <span style="font-size:1.125rem;font-weight:700;color:var(--text-900);letter-spacing:0.02em">03XX-XXXXXXX</span>
                                        <button class="btn btn-sm btn-secondary" id="copyNumberBtn" type="button" style="min-height:32px;padding:6px 12px;font-size:0.75rem">
                                            <i data-lucide="copy" style="width:14px;height:14px"></i>
                                            Copy
                                        </button>
                                    </div>
                                    <div style="display:flex;align-items:flex-start;gap:12px;font-size:0.875rem;color:var(--text-600)">
                                        <span style="width:24px;height:24px;border-radius:50%;background:var(--primary-50);color:var(--primary);display:flex;align-items:center;justify-content:center;font-size:0.75rem;font-weight:700;flex-shrink:0">4</span>
                                        <span>After sending, click <strong>"I Have Sent Payment"</strong> below. We'll verify and activate your alerts.</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Summary Sidebar -->
                    <div class="checkout-summary">
                        <h3>Order Summary</h3>

                        <div class="checkout-summary-phone" id="summaryPhone">
                            <i data-lucide="message-square"></i>
                            <span id="summaryPhoneNum">+92 3XX XXXXXXX</span>
                        </div>

                        <div class="checkout-summary-item">
                            <span>WhatsApp Alerts — Premium</span>
                            <span>PKR 1,499</span>
                        </div>
                        <div class="checkout-summary-item">
                            <span>Email Alerts — Premium</span>
                            <span>Included</span>
                        </div>
                        <div class="checkout-summary-item">
                            <span>Billing Cycle</span>
                            <span>Monthly</span>
                        </div>
                        <div class="checkout-summary-item total">
                            <span>Total</span>
                            <span>PKR 1,499</span>
                        </div>

                        <div class="checkout-summary-features">
                            <div class="checkout-summary-feature">
                                <i data-lucide="check"></i>
                                Personalized scholarship matching
                            </div>
                            <div class="checkout-summary-feature">
                                <i data-lucide="check"></i>
                                WhatsApp alerts
                            </div>
                            <div class="checkout-summary-feature">
                                <i data-lucide="check"></i>
                                Email alerts
                            </div>
                            <div class="checkout-summary-feature">
                                <i data-lucide="check"></i>
                                Deadline reminders
                            </div>
                            <div class="checkout-summary-feature">
                                <i data-lucide="check"></i>
                                Application tracking
                            </div>
                            <div class="checkout-summary-feature">
                                <i data-lucide="check"></i>
                                Cancel anytime
                            </div>
                        </div>

                        <button class="btn btn-primary" id="confirmPayBtn" type="button">
                            <i data-lucide="shield-check" style="width:18px;height:18px"></i>
                            I Have Sent Payment
                        </button>

                        <div class="checkout-secure">
                            <i data-lucide="lock"></i>
                            Secure transaction · Cancel anytime
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- /checkout-page -->

    </div><!-- /dash-main -->
</div><!-- /dash-layout -->

<!-- Hidden secure POST logout form -->
<form id="logoutForm" action="/logout" method="POST" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
</form>

<!-- ===== WHATSAPP SUBSCRIPTION MODAL ===== -->
<div class="modal-overlay" id="waModal" role="dialog" aria-modal="true" aria-label="Activate WhatsApp Alerts">
    <div class="modal">
        <div class="modal-header">
            <h3>Activate WhatsApp Alerts</h3>
            <button class="modal-close" id="waModalClose" aria-label="Close">
                <i data-lucide="x"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="modal-features">
                <div class="modal-feature">
                    <i data-lucide="circle-check"></i>
                    Instant WhatsApp notifications for new matches
                </div>
                <div class="modal-feature">
                    <i data-lucide="circle-check"></i>
                    Deadline reminders before closing dates
                </div>
                <div class="modal-feature">
                    <i data-lucide="circle-check"></i>
                    Email alerts included at no extra cost
                </div>
                <div class="modal-feature">
                    <i data-lucide="circle-check"></i>
                    Daily matching updates
                </div>
            </div>

            <!-- Phone input -->
            <label style="font-size:0.875rem;font-weight:650;color:var(--text-700);display:block;margin-bottom:6px">
                Your WhatsApp Number
            </label>
            <div class="phone-input-group" id="phoneInputGroup">
                <div class="phone-code">
                    🇵🇰 +92
                </div>
                <input
                    type="tel"
                    class="phone-input"
                    id="phoneInput"
                    placeholder="3XX XXXXXXX"
                    maxlength="11"
                    inputmode="numeric"
                    autocomplete="tel-national"
                    aria-label="WhatsApp phone number"
                >
            </div>
            <p class="phone-hint">
                <i data-lucide="info"></i>
                Enter your 10-digit number without the 0 prefix (e.g., 3XX XXXXXXX)
            </p>
            <p class="phone-error" id="phoneError">
                <i data-lucide="alert-circle"></i>
                <span id="phoneErrorText">Please enter a valid Pakistani phone number</span>
            </p>

            <!-- Price summary -->
            <div class="modal-price-summary">
                <div class="modal-price-row">
                    <span class="label"><i data-lucide="message-square"></i> WhatsApp Alerts</span>
                    <span>Included</span>
                </div>
                <div class="modal-price-row">
                    <span class="label"><i data-lucide="mail"></i> Email Alerts</span>
                    <span>Included</span>
                </div>
                <div class="modal-price-row">
                    <span class="label"><i data-lucide="calendar"></i> Billing</span>
                    <span>Monthly</span>
                </div>
                <div class="modal-price-row total">
                    <span>Total</span>
                    <span>PKR 1,499 /mo</span>
                </div>
            </div>

            <button class="btn btn-whatsapp" id="proceedCheckout" type="button" style="width:100%">
                <i data-lucide="arrow-right"></i>
                Proceed to Checkout
            </button>

            <p style="text-align:center;font-size:0.75rem;color:var(--text-400);margin-top:12px">
                Cancel anytime from your dashboard. No hidden charges.
            </p>
        </div>
    </div>
</div>

<script>
    // Initialize Lucide icons
    lucide.createIcons();

    // ===== TOAST NOTIFICATION HELPER =====
    function showToast(message, type = 'success') {
        // Remove existing toast if present
        const oldToast = document.querySelector('.toast-notification');
        if (oldToast) oldToast.remove();

        const toast = document.createElement('div');
        toast.className = `toast-notification ${type}`;
        toast.style.position = 'fixed';
        toast.style.top = '24px';
        toast.style.right = '24px';
        toast.style.padding = '14px 18px';
        toast.style.borderRadius = '10px';
        toast.style.boxShadow = '0 10px 15px -3px rgba(0, 0, 0, 0.1)';
        toast.style.display = 'flex';
        toast.style.alignItems = 'center';
        toast.style.gap = '10px';
        toast.style.zIndex = '9999';
        toast.style.transition = 'all 0.3s ease';

        if (type === 'success') {
            toast.style.background = '#ecfdf5';
            toast.style.border = '1px solid #d1fae5';
            toast.style.color = '#065f46';
        } else {
            toast.style.background = '#fef2f2';
            toast.style.border = '1px solid #fee2e2';
            toast.style.color = '#991b1b';
        }

        toast.innerHTML = `
            <span style="font-size: 0.875rem; font-weight: 600;">${message}</span>
            <button class="toast-close" style="background: none; border: none; cursor: pointer; color: inherit; display:flex; align-items:center;">
                <i data-lucide="x" style="width: 14px; height: 14px;"></i>
            </button>
        `;

        document.body.appendChild(toast);
        lucide.createIcons();

        toast.querySelector('.toast-close').addEventListener('click', () => {
            toast.remove();
        });

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-10px)';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }

    // ===== STATE =====
    let selectedMethod = 'easypaisa';
    let userPhone = '';

    // ===== ELEMENTS =====
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const waModal = document.getElementById('waModal');
    const waModalClose = document.getElementById('waModalClose');
    const openWaModal = document.getElementById('openWaModal');
    const sidebarWaLink = document.getElementById('sidebarWaLink');
    const phoneInput = document.getElementById('phoneInput');
    const phoneError = document.getElementById('phoneError');
    const phoneErrorText = document.getElementById('phoneErrorText');
    const proceedCheckout = document.getElementById('proceedCheckout');
    const dashboardPage = document.getElementById('dashboardPage');
    const checkoutPage = document.getElementById('checkoutPage');
    const checkoutBack = document.getElementById('checkoutBack');
    const methodEasypaisa = document.getElementById('methodEasypaisa');
    const methodJazzcash = document.getElementById('methodJazzcash');
    const payMethodName = document.getElementById('payMethodName');
    const summaryPhoneNum = document.getElementById('summaryPhoneNum');
    const confirmPayBtn = document.getElementById('confirmPayBtn');
    const checkoutSuccess = document.getElementById('checkoutSuccess');
    const checkoutForm = document.getElementById('checkoutForm');
    const successBackBtn = document.getElementById('successBackBtn');
    const copyNumberBtn = document.getElementById('copyNumberBtn');

    // ===== SIDEBAR TOGGLE (MOBILE) =====
    function openSidebar() {
        sidebar.classList.add('open');
        sidebarOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        sidebar.classList.remove('open');
        sidebarOverlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    if (mobileMenuBtn) mobileMenuBtn.addEventListener('click', openSidebar);
    if (sidebarOverlay) sidebarOverlay.addEventListener('click', closeSidebar);

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeSidebar();
            closeWaModal();
        }
    });

    // Close sidebar when a link is clicked on mobile
    sidebar.querySelectorAll('.sidebar-link').forEach(function(link) {
        link.addEventListener('click', function() {
            if (window.innerWidth < 1024) {
                closeSidebar();
            }
        });
    });

    // ===== WHATSAPP MODAL =====
    function openWaModalFn() {
        waModal.classList.add('active');
        document.body.style.overflow = 'hidden';
        setTimeout(function() { phoneInput.focus(); }, 300);
    }

    function closeWaModal() {
        waModal.classList.remove('active');
        document.body.style.overflow = '';
        phoneError.classList.remove('visible');
    }

    if (openWaModal) openWaModal.addEventListener('click', openWaModalFn);
    if (sidebarWaLink) {
        sidebarWaLink.addEventListener('click', function(e) {
            e.preventDefault();
            openWaModalFn();
        });
    }
    if (waModalClose) waModalClose.addEventListener('click', closeWaModal);

    waModal.addEventListener('click', function(e) {
        if (e.target === waModal) closeWaModal();
    });

    // ===== PHONE VALIDATION =====
    function validatePhone(num) {
        var cleaned = num.replace(/[\s\-]/g, '');
        if (!/^3\d{9}$/.test(cleaned)) return false;
        return true;
    }

    function formatPhone(num) {
        var cleaned = num.replace(/[\s\-]/g, '');
        if (cleaned.length <= 3) return cleaned;
        if (cleaned.length <= 6) return cleaned.slice(0, 3) + ' ' + cleaned.slice(3);
        return cleaned.slice(0, 3) + ' ' + cleaned.slice(3, 7) + ' ' + cleaned.slice(7);
    }

    phoneInput.addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9\s]/g, '');
        phoneError.classList.remove('visible');
    });

    // ===== PROCEED TO CHECKOUT =====
    proceedCheckout.addEventListener('click', function() {
        var raw = phoneInput.value.trim();

        if (!validatePhone(raw)) {
            phoneErrorText.textContent = 'Please enter a valid Pakistani phone number (e.g., 3XX XXXXXXX)';
            phoneError.classList.add('visible');
            phoneInput.focus();
            return;
        }

        phoneError.classList.remove('visible');
        userPhone = formatPhone(raw);
        summaryPhoneNum.textContent = '+92 ' + userPhone;

        closeWaModal();

        setTimeout(function() {
            dashboardPage.classList.add('hidden');
            checkoutPage.classList.add('active');
            checkoutSuccess.classList.remove('active');
            checkoutForm.style.display = '';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }, 200);
    });

    // ===== CHECKOUT BACK =====
    checkoutBack.addEventListener('click', function(e) {
        e.preventDefault();
        checkoutPage.classList.remove('active');
        dashboardPage.classList.remove('hidden');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    successBackBtn.addEventListener('click', function(e) {
        e.preventDefault();
        checkoutPage.classList.remove('active');
        checkoutSuccess.classList.remove('active');
        checkoutForm.style.display = '';
        dashboardPage.classList.remove('hidden');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    // ===== PAYMENT METHOD SELECTION =====
    function selectMethod(method) {
        selectedMethod = method;

        methodEasypaisa.classList.toggle('selected', method === 'easypaisa');
        methodJazzcash.classList.toggle('selected', method === 'jazzcash');

        methodEasypaisa.setAttribute('aria-checked', String(method === 'easypaisa'));
        methodJazzcash.setAttribute('aria-checked', String(method === 'jazzcash'));

        var name = method === 'easypaisa' ? 'Easypaisa' : 'JazzCash';
        payMethodName.textContent = name;
    }

    methodEasypaisa.addEventListener('click', function() { selectMethod('easypaisa'); });
    methodJazzcash.addEventListener('click', function() { selectMethod('jazzcash'); });

    [methodEasypaisa, methodJazzcash].forEach(function(el) {
        el.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
        });
    });

    // ===== CONFIRM PAYMENT =====
    confirmPayBtn.addEventListener('click', function() {
        checkoutForm.style.display = 'none';
        checkoutSuccess.classList.add('active');
        window.scrollTo({ top: 0, behavior: 'smooth' });
        lucide.createIcons();
    });

    // ===== COPY NUMBER =====
    copyNumberBtn.addEventListener('click', function() {
        var text = '03XXXXXXXXX';
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(function() {
                copyNumberBtn.innerHTML = '<i data-lucide="check" style="width:14px;height:14px"></i> Copied';
                lucide.createIcons();
                setTimeout(function() {
                    copyNumberBtn.innerHTML = '<i data-lucide="copy" style="width:14px;height:14px"></i> Copy';
                    lucide.createIcons();
                }, 2000);
            });
        } else {
            var ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.left = '-9999px';
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
            copyNumberBtn.innerHTML = '<i data-lucide="check" style="width:14px;height:14px"></i> Copied';
            lucide.createIcons();
            setTimeout(function() {
                copyNumberBtn.innerHTML = '<i data-lucide="copy" style="width:14px;height:14px"></i> Copy';
                lucide.createIcons();
            }, 2000);
        }
    });

    phoneInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            proceedCheckout.click();
        }
    });

    // ===== AJAX BOOKMARK TOGGLE (SAVE/UNSAVE) =====
    document.addEventListener('click', function(e) {
        // Toggle bookmark on recommended matches table
        const toggleBtn = e.target.closest('.btn-bookmark-toggle');
        if (toggleBtn) {
            e.preventDefault();
            const scholarshipId = toggleBtn.getAttribute('data-id');
            const isSaved = toggleBtn.getAttribute('data-saved') === 'true';
            const action = isSaved ? 'unsave' : 'save';

            // Disable buttons during action
            toggleBtn.disabled = true;

            const formData = new FormData();
            formData.append('csrf_token', '<?= e($csrf_token) ?>');

            fetch(`/scholarships/${scholarshipId}/${action}`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(data => { throw new Error(data.error || 'Server error'); });
                }
                return response.json();
            })
            .then(data => {
                const newSavedState = !isSaved;
                toggleBtn.setAttribute('data-saved', newSavedState ? 'true' : 'false');
                toggleBtn.innerHTML = `<i data-lucide="${newSavedState ? 'bookmark-check' : 'bookmark'}" style="width:16px; height:16px;"></i>`;
                lucide.createIcons();
                showToast(data.message || (newSavedState ? 'Scholarship bookmarked!' : 'Bookmark removed!'), 'success');
                
                // Lazy reload list widgets or update counts count dynamically
                setTimeout(() => window.location.reload(), 1000);
            })
            .catch(err => {
                showToast(err.message || 'Verification failed.', 'error');
            })
            .finally(() => {
                toggleBtn.disabled = false;
            });
        }

        // Quick unsave button in bottom saved widget
        const unsaveBtn = e.target.closest('.btn-unsave-quick');
        if (unsaveBtn) {
            e.preventDefault();
            const scholarshipId = unsaveBtn.getAttribute('data-id');

            unsaveBtn.disabled = true;

            const formData = new FormData();
            formData.append('csrf_token', '<?= e($csrf_token) ?>');

            fetch(`/scholarships/${scholarshipId}/unsave`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(data => { throw new Error(data.error || 'Server error'); });
                }
                return response.json();
            })
            .then(data => {
                const row = unsaveBtn.closest('.saved-item-row');
                if (row) {
                    row.style.opacity = '0';
                    setTimeout(() => {
                        row.remove();
                        // If no bookmarks left, show empty state
                        const savedContainer = document.getElementById('savedListContainer');
                        if (savedContainer && savedContainer.children.length === 0) {
                            window.location.reload();
                        }
                    }, 300);
                }
                showToast(data.message || 'Bookmark removed!', 'success');
                
                // Reload after delay to sync other stats/counts
                setTimeout(() => window.location.reload(), 1000);
            })
            .catch(err => {
                showToast(err.message || 'Failed to remove bookmark.', 'error');
            })
            .finally(() => {
                unsaveBtn.disabled = false;
            });
        }
    });
</script>

</body>
</html>
