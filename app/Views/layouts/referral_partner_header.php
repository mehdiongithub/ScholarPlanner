<?php use App\Helpers\Security; 
$user = \App\Services\Auth::currentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Referral Partner Portal' ?> | ScholarMatch</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@0.460.0"></script>
    <link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/admin.css') ?>">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="<?= asset('assets/js/main.js') ?>"></script>
</head>
<body>

<div class="admin-layout">
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar Navigation -->
    <aside class="admin-sidebar" id="adminSidebar">
        <a href="<?= url('/referral-partner') ?>" class="sidebar-brand">
            <i data-lucide="graduation-cap"></i>
            <span>Partner Portal</span>
        </a>

        <div class="sidebar-menu">
            <?php
            $sidebarNav = [
                [
                    'type' => 'link',
                    'label' => 'Dashboard',
                    'icon' => 'layout-dashboard',
                    'url' => '/referral-partner',
                    'active_prefix' => '/referral-partner',
                    'exact' => true
                ],
                [
                    'type' => 'link',
                    'label' => 'Referred Students',
                    'icon' => 'users',
                    'url' => '/referral-partner/students',
                    'active_prefix' => '/referral-partner/students'
                ],
                [
                    'type' => 'link',
                    'label' => 'My Referral Link',
                    'icon' => 'share-2',
                    'url' => '/referral-partner#sharing-link',
                    'active_prefix' => '/referral-partner#sharing-link'
                ],
                [
                    'type' => 'link',
                    'label' => 'Profile & Password',
                    'icon' => 'user',
                    'url' => '/referral-partner/profile',
                    'active_prefix' => '/referral-partner/profile'
                ]
            ];

            if (!function_exists('is_partner_nav_active')) {
                function is_partner_nav_active(array $navItem): bool {
                    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
                    $basePath = dirname($_SERVER['SCRIPT_NAME'] ?? '');
                    $basePath = ($basePath === '/' || $basePath === '\\') ? '' : rtrim($basePath, '/');
                    
                    $prefix = $basePath . $navItem['active_prefix'];
                    
                    if (!empty($navItem['exact'])) {
                        return $uri === $prefix || $uri === $prefix . '/';
                    }
                    
                    return strpos($uri, $prefix) === 0;
                }
            }

            foreach ($sidebarNav as $navItem) {
                $activeClass = is_partner_nav_active($navItem) ? 'active' : '';
                echo '<a href="' . url($navItem['url']) . '" class="menu-item ' . $activeClass . '">';
                echo '<i data-lucide="' . $navItem['icon'] . '"></i>';
                echo '<span>' . e($navItem['label']) . '</span>';
                echo '</a>';
            }
            ?>
            
            <div style="margin-top: auto; padding-top: 20px;">
                <form action="<?= url('/logout') ?>" method="POST" class="logout-form">
                    <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Security::csrfToken() ?>">
                    <button type="submit" class="menu-item" style="width: 100%; border: none; background: none; text-align: left; cursor: pointer; color: #fca5a5;">
                        <i data-lucide="log-out"></i>
                        <span>Log Out</span>
                    </button>
                </form>
            </div>
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
                    Welcome, <span style="font-weight: 700; color: #1e293b;"><?= e($user['first_name'] . ' ' . $user['last_name']) ?></span>
                </div>
            </div>

            <div class="header-right">
                <div style="font-weight: 500; font-size: 0.875rem; color: #64748b;">
                    Role: <span class="badge badge-secondary" style="font-weight: 700; text-transform: uppercase;"><?= e($_SESSION['role_name'] ?? 'Partner') ?></span>
                </div>
            </div>
        </header>

        <div class="admin-content">
            <!-- Messages alerts -->
            <?php if (!empty($_SESSION['partner_success'])): ?>
                <div class="admin-alert admin-alert-success">
                    <i data-lucide="circle-check"></i>
                    <span><?= e($_SESSION['partner_success']) ?></span>
                </div>
                <?php unset($_SESSION['partner_success']); ?>
            <?php endif; ?>

            <?php if (!empty($_SESSION['partner_errors'])): ?>
                <div class="admin-alert admin-alert-error">
                    <i data-lucide="alert-circle"></i>
                    <span><?= e($_SESSION['partner_errors']) ?></span>
                </div>
                <?php unset($_SESSION['partner_errors']); ?>
            <?php endif; ?>
