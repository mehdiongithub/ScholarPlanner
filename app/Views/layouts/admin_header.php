<?php use App\Helpers\Security; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Admin Dashboard' ?> | ScholarMatch Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <style>
        :root {
            --sidebar-width: 260px;
            --header-height: 70px;
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --bg-slate-50: #f8fafc;
            --bg-slate-100: #f1f5f9;
            --bg-slate-800: #1e293b;
            --border-slate-200: #e2e8f0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-slate-50);
            color: #334155;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        /* Layout Structure */
        .admin-layout {
            display: flex;
            min-height: 100vh;
            position: relative;
        }

        /* Left Sidebar */
        .admin-sidebar {
            width: var(--sidebar-width);
            background-color: var(--bg-slate-800);
            color: #f8fafc;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 1000;
            transition: transform 0.3s ease;
            overflow-y: auto;
            border-right: 1px solid rgba(255, 255, 255, 0.05);
        }

        .sidebar-brand {
            padding: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.25rem;
            font-weight: 700;
            color: #fff;
            text-decoration: none;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .sidebar-brand i {
            color: #3b82f6;
        }

        .sidebar-menu {
            flex-grow: 1;
            padding: 20px 12px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .menu-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            padding: 12px 12px 6px 12px;
            font-weight: 600;
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            color: #cbd5e1;
            text-decoration: none;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s;
        }

        .menu-item:hover {
            background-color: rgba(255, 255, 255, 0.05);
            color: #fff;
        }

        .menu-item.active {
            background-color: var(--primary);
            color: #fff;
        }

        .menu-item i {
            width: 18px;
            height: 18px;
        }

        /* Main Content Wrapper */
        .admin-main {
            flex-grow: 1;
            margin-left: var(--sidebar-width);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            width: calc(100% - var(--sidebar-width));
            transition: margin-left 0.3s ease, width 0.3s ease;
        }

        /* Top Navigation Header */
        .admin-header {
            height: var(--header-height);
            background-color: #fff;
            border-bottom: 1px solid var(--border-slate-200);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            position: sticky;
            top: 0;
            z-index: 900;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .mobile-toggle {
            display: none;
            background: none;
            border: none;
            color: #475569;
            cursor: pointer;
            padding: 8px;
            border-radius: 6px;
        }

        .mobile-toggle:hover {
            background-color: var(--bg-slate-100);
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-profile-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: #1e293b;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .avatar-circle {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background-color: #3b82f6;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.9375rem;
        }

        .logout-form {
            display: inline;
        }

        .logout-btn {
            background: none;
            border: 1px solid var(--border-slate-200);
            color: #ef4444;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 0.8125rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }

        .logout-btn:hover {
            background-color: #fef2f2;
            border-color: #fca5a5;
        }

        /* Dynamic Content Area */
        .admin-content {
            padding: 32px 24px;
            flex-grow: 1;
            max-width: 1200px;
            width: 100%;
            box-sizing: border-box;
            margin: 0 auto;
        }

        /* Responsive Overlay */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(2px);
            z-index: 950;
        }

        /* Alerts and Layout Blocks */
        .admin-alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 24px;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .admin-alert-success {
            background-color: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .admin-alert-error {
            background-color: #fef2f2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        /* Mobile Breakpoints */
        @media (max-width: 991px) {
            .admin-sidebar {
                transform: translateX(-100%);
            }

            .admin-sidebar.open {
                transform: translateX(0);
            }

            .admin-main {
                margin-left: 0;
                width: 100%;
            }

            .mobile-toggle {
                display: block;
            }

            .sidebar-overlay.open {
                display: block;
            }
        }
    </style>
</head>
<body>

<div class="admin-layout">
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar Navigation -->
    <aside class="admin-sidebar" id="adminSidebar">
        <a href="<?= url('/admin') ?>" class="sidebar-brand">
            <i data-lucide="graduation-cap"></i>
            <span>ScholarMatch</span>
        </a>

        <div class="sidebar-menu">
            <?php
            // Centralized Admin Sidebar Navigation Configuration
            $sidebarNav = [
                [
                    'type' => 'link',
                    'label' => 'Dashboard',
                    'icon' => 'layout-dashboard',
                    'url' => '/admin',
                    'active_prefix' => '/admin',
                    'exact' => true
                ],
                [
                    'type' => 'section',
                    'label' => 'USER MANAGEMENT'
                ],
                [
                    'type' => 'link',
                    'label' => 'Students / Visitors',
                    'icon' => 'users',
                    'url' => '/admin/users',
                    'active_prefix' => '/admin/users'
                ],
                [
                    'type' => 'link',
                    'label' => 'Staff & Employees',
                    'icon' => 'shield-check',
                    'url' => '/admin/employees',
                    'active_prefix' => '/admin/employees',
                    'exclude_prefix' => '/admin/employees/roles'
                ],
                [
                    'type' => 'link',
                    'label' => 'Roles & Permissions',
                    'icon' => 'lock',
                    'url' => '/admin/employees/roles',
                    'active_prefix' => '/admin/employees/roles'
                ],
                [
                    'type' => 'section',
                    'label' => 'SCHOLARSHIPS & PLATFORM'
                ],
                [
                    'type' => 'link',
                    'label' => 'Scholarships',
                    'icon' => 'award',
                    'url' => '/admin/scholarships',
                    'active_prefix' => '/admin/scholarships'
                ],
                [
                    'type' => 'link',
                    'label' => 'Institutions',
                    'icon' => 'landmark',
                    'url' => '/admin/institutions',
                    'active_prefix' => '/admin/institutions'
                ],
                [
                    'type' => 'link',
                    'label' => 'Applications',
                    'icon' => 'file-text',
                    'url' => '/admin/applications',
                    'active_prefix' => '/admin/applications'
                ],
                [
                    'type' => 'link',
                    'label' => 'Uploaded Documents',
                    'icon' => 'files',
                    'url' => '/admin/documents',
                    'active_prefix' => '/admin/documents'
                ],
                [
                    'type' => 'section',
                    'label' => 'ACADEMIC & LOCATIONS'
                ],
                [
                    'type' => 'link',
                    'label' => 'Countries',
                    'icon' => 'globe',
                    'url' => '/admin/locations/countries',
                    'active_prefix' => '/admin/locations/countries'
                ],
                [
                    'type' => 'link',
                    'label' => 'States / Provinces',
                    'icon' => 'map-pin',
                    'url' => '/admin/locations/states',
                    'active_prefix' => '/admin/locations/states'
                ],
                [
                    'type' => 'link',
                    'label' => 'Cities',
                    'icon' => 'navigation',
                    'url' => '/admin/locations/cities',
                    'active_prefix' => '/admin/locations/cities'
                ],
                [
                    'type' => 'link',
                    'label' => 'Fields of Study',
                    'icon' => 'book-open',
                    'url' => '/admin/academic/fields',
                    'active_prefix' => '/admin/academic/fields'
                ],
                [
                    'type' => 'link',
                    'label' => 'Degree Levels',
                    'icon' => 'award',
                    'url' => '/admin/academic/degrees',
                    'active_prefix' => '/admin/academic/degrees'
                ],
                [
                    'type' => 'link',
                    'label' => 'Funding Types',
                    'icon' => 'banknote',
                    'url' => '/admin/academic/funding',
                    'active_prefix' => '/admin/academic/funding'
                ],
                [
                    'type' => 'section',
                    'label' => 'MATCHING'
                ],
                [
                    'type' => 'link',
                    'label' => 'Matching Rules',
                    'icon' => 'git-branch',
                    'url' => '/admin/matching/rules',
                    'active_prefix' => '/admin/matching/rules'
                ],
                [
                    'type' => 'link',
                    'label' => 'Matching Statistics',
                    'icon' => 'bar-chart-2',
                    'url' => '/admin/matching/stats',
                    'active_prefix' => '/admin/matching/stats'
                ],
                [
                    'type' => 'link',
                    'label' => 'Intelligence',
                    'icon' => 'brain',
                    'url' => '/admin/intelligence',
                    'active_prefix' => '/admin/intelligence'
                ],
                [
                    'type' => 'section',
                    'label' => 'COMMUNICATION'
                ],
                [
                    'type' => 'link',
                    'label' => 'Notifications',
                    'icon' => 'bell',
                    'url' => '/admin/notifications',
                    'active_prefix' => '/admin/notifications'
                ],
                [
                    'type' => 'section',
                    'label' => 'BILLING'
                ],
                [
                    'type' => 'link',
                    'label' => 'Subscriptions',
                    'icon' => 'refresh-cw',
                    'url' => '/admin/subscriptions',
                    'active_prefix' => '/admin/subscriptions'
                ],
                [
                    'type' => 'link',
                    'label' => 'Payments',
                    'icon' => 'dollar-sign',
                    'url' => '/admin/payments',
                    'active_prefix' => '/admin/payments'
                ],
                [
                    'type' => 'section',
                    'label' => 'SYSTEM'
                ],
                [
                    'type' => 'link',
                    'label' => 'Settings',
                    'icon' => 'settings',
                    'url' => '/admin/settings',
                    'active_prefix' => '/admin/settings'
                ],
                [
                    'type' => 'link',
                    'label' => 'Audit Logs',
                    'icon' => 'history',
                    'url' => '/admin/audit-logs',
                    'active_prefix' => '/admin/audit-logs'
                ],
                [
                    'type' => 'link',
                    'label' => 'Admin Profile',
                    'icon' => 'user',
                    'url' => '/admin/profile',
                    'active_prefix' => '/admin/profile'
                ]
            ];

            if (!function_exists('is_nav_active')) {
                function is_nav_active(array $navItem): bool {
                    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
                    $basePath = dirname($_SERVER['SCRIPT_NAME'] ?? '');
                    $basePath = ($basePath === '/' || $basePath === '\\') ? '' : rtrim($basePath, '/');
                    
                    $prefix = $basePath . $navItem['active_prefix'];
                    $exclude = isset($navItem['exclude_prefix']) ? $basePath . $navItem['exclude_prefix'] : null;
                    
                    if ($exclude !== null && strpos($uri, $exclude) === 0) {
                        return false;
                    }
                    
                    if (!empty($navItem['exact'])) {
                        return $uri === $prefix || $uri === $prefix . '/';
                    }
                    
                    return strpos($uri, $prefix) === 0;
                }
            }

            foreach ($sidebarNav as $navItem) {
                if ($navItem['type'] === 'section') {
                    echo '<div class="menu-label">' . e($navItem['label']) . '</div>';
                } elseif ($navItem['type'] === 'link') {
                    $activeClass = is_nav_active($navItem) ? 'active' : '';
                    echo '<a href="' . url($navItem['url']) . '" class="menu-item ' . $activeClass . '">';
                    echo '<i data-lucide="' . $navItem['icon'] . '"></i>';
                    echo '<span>' . e($navItem['label']) . '</span>';
                    echo '</a>';
                }
            }
            ?>
        </div>
    </aside>

    <!-- Main Section -->
    <main class="admin-main">
        <header class="admin-header">
            <div class="header-left">
                <button class="mobile-toggle" id="mobileToggle" aria-label="Toggle Sidebar">
                    <i data-lucide="menu"></i>
                </button>
                <div style="font-weight: 500; font-size: 0.875rem; color: #64748b;">
                    Role: <span class="badge badge-secondary" style="font-weight: 700; text-transform: uppercase;"><?= e($_SESSION['role_name'] ?? 'Staff') ?></span>
                </div>
            </div>

            <div class="header-right">
                <a href="<?= url('/admin/profile') ?>" class="user-profile-btn">
                    <div class="avatar-circle">
                        <?php 
                            $initials = '';
                            if (!empty($user['first_name'])) $initials .= strtoupper($user['first_name'][0]);
                            if (!empty($user['last_name'])) $initials .= strtoupper($user['last_name'][0]);
                            echo $initials ?: 'AD';
                        ?>
                    </div>
                    <span class="user-name-label"><?= e($user['first_name'] ?? 'Admin') ?></span>
                </a>

                <form action="<?= url('/logout') ?>" method="POST" class="logout-form">
                    <input type="hidden" name="csrf_token" value="<?= Security::csrfToken() ?>">
                    <button type="submit" class="logout-btn">
                        <i data-lucide="log-out"></i>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </header>

        <div class="admin-content">
            <!-- Messages alerts -->
            <?php if (!empty($_SESSION['admin_success'])): ?>
                <div class="admin-alert admin-alert-success">
                    <i data-lucide="check-circle-2"></i>
                    <span><?= e($_SESSION['admin_success']) ?></span>
                </div>
                <?php unset($_SESSION['admin_success']); ?>
            <?php endif; ?>

            <?php if (!empty($_SESSION['admin_errors'])): ?>
                <div class="admin-alert admin-alert-error">
                    <i data-lucide="alert-circle"></i>
                    <span><?= e($_SESSION['admin_errors']) ?></span>
                </div>
                <?php unset($_SESSION['admin_errors']); ?>
            <?php endif; ?>
