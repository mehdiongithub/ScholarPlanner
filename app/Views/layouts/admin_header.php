<?php use App\Helpers\Security; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    $navRole = $_SESSION['role_name'] ?? 'admin';
    $defaultTitle = ($navRole === 'employee') ? 'Employee Portal' : 'Admin Dashboard';
    $portalSuffix = ($navRole === 'employee') ? 'ScholarPlanner Portal' : 'ScholarPlanner Admin';
    ?>
    <title><?= $title ?? $defaultTitle ?> | <?= $portalSuffix ?></title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/webp" href="<?= asset('assets/images/logo.webp') ?>">
    <link rel="apple-touch-icon" href="<?= asset('assets/images/logo.webp') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="<?= asset('assets/js/lucide.min.js') ?>"></script>
    <link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/admin.css') ?>">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
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
        <a href="<?= url('/admin') ?>" class="sidebar-brand">
            <img src="<?= asset('assets/images/logo.webp') ?>" alt="ScholarPlanner Admin Logo" class="sidebar-brand-logo">
        </a>

        <div class="sidebar-menu">
            <?php
            $currentRole = $_SESSION['role_name'] ?? 'admin';
            $sidebarNav = \App\Helpers\Navigation::getSidebarMenu($currentRole);

            foreach ($sidebarNav as $navItem) {
                if ($navItem['type'] === 'section') {
                    echo '<div class="menu-label">' . e($navItem['label']) . '</div>';
                } elseif ($navItem['type'] === 'link') {
                    $activeClass = \App\Helpers\Navigation::isActive($navItem) ? 'active' : '';
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
                <?php 
                    $headerUser = $user ?? \App\Services\Auth::currentUser() ?? [];
                    $initials = '';
                    if (!empty($headerUser['first_name'])) $initials .= strtoupper($headerUser['first_name'][0]);
                    if (!empty($headerUser['last_name'])) $initials .= strtoupper($headerUser['last_name'][0]);
                    if (empty($initials)) {
                        $initials = ($navRole === 'employee') ? 'EM' : 'AD';
                    }
                    $displayName = !empty($headerUser['first_name']) ? $headerUser['first_name'] : (($navRole === 'employee') ? 'Staff' : 'Admin');
                ?>
                <a href="<?= url('/admin/profile') ?>" class="user-profile-btn">
                    <div class="avatar-circle">
                        <?= e($initials) ?>
                    </div>
                    <span class="user-name-label"><?= e($displayName) ?></span>
                </a>

                <form action="<?= url('/logout') ?>" method="POST" class="logout-form">
                    <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Security::csrfToken() ?>">
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
                    <i data-lucide="circle-check"></i>
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
