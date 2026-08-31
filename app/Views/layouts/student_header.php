<?php
use App\Helpers\Security;
use App\Helpers\Navigation;
use App\Services\Auth;
use App\Services\Database;

$user = $user ?? Auth::currentUser() ?? [];
$userId = $user['id'] ?? null;
$first_name = $user['first_name'] ?? 'Student';
$last_name = $user['last_name'] ?? '';
$fullName = trim($first_name . ' ' . $last_name);

$firstInitial = mb_substr($first_name, 0, 1);
$lastInitial = mb_substr($last_name ?: 'S', 0, 1);
$initials = strtoupper($firstInitial . $lastInitial);

// Get student sidebar badges
$matchesCount = 0;
$savedCount = 0;
$appsCount = 0;

if ($userId) {
    try {
        $db = Database::connection();
        
        $stmtCount = $db->prepare("
            SELECT COUNT(*) 
            FROM scholarship_matches m 
            JOIN scholarships s ON m.scholarship_id = s.id 
            WHERE m.user_id = :uid AND s.status = 'published'
        ");
        $stmtCount->execute(['uid' => $userId]);
        $matchesCount = (int)$stmtCount->fetchColumn();

        $stmtSaved = $db->prepare("
            SELECT COUNT(*) 
            FROM saved_scholarships ss 
            JOIN scholarships s ON ss.scholarship_id = s.id 
            WHERE ss.user_id = :uid AND s.status = 'published'
        ");
        $stmtSaved->execute(['uid' => $userId]);
        $savedCount = (int)$stmtSaved->fetchColumn();

        $stmtApps = $db->prepare("
            SELECT COUNT(*) 
            FROM scholarship_applications 
            WHERE user_id = :uid
        ");
        $stmtApps->execute(['uid' => $userId]);
        $appsCount = (int)$stmtApps->fetchColumn();
    } catch (\Exception $e) {
        // Fail silently
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Student Dashboard') ?> | ScholarMatch</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@0.460.0"></script>
    
    <!-- CSS Dependencies -->
    <link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/admin.css') ?>?v=1.2">
    
    <!-- External JS Dependencies -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    
    <!-- Core JS Helper -->
    <script src="<?= asset('assets/js/main.js') ?>"></script>
</head>
<body>

<div class="admin-layout">
    <!-- Sidebar Overlay backdrop for mobile drawers -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Student Sidebar Navigation -->
    <aside class="admin-sidebar" id="adminSidebar">
        <a href="<?= url('/dashboard') ?>" class="sidebar-brand">
            <i data-lucide="graduation-cap"></i>
            <span>ScholarMatch</span>
        </a>

        <!-- User profile details container -->
        <div class="sidebar-user-card" style="padding:16px 20px; border-bottom: 1px solid rgba(255,255,255,0.05); background: rgba(255,255,255,0.02);">
            <div style="display:flex; align-items:center; gap:12px; margin-bottom:8px">
                <div class="avatar-circle" style="background-color: var(--primary); border: 2px solid rgba(255,255,255,0.1); width:38px; height:38px"><?= e($initials) ?></div>
                <div style="overflow:hidden">
                    <div style="font-size:0.875rem; font-weight:600; color:#fff; white-space:nowrap; overflow:hidden; text-overflow:ellipsis"><?= e($fullName) ?></div>
                    <div style="font-size:0.75rem; color:#94a3b8">Student Member</div>
                </div>
            </div>
        </div>

        <div class="sidebar-menu">
            <?php
            $sidebarNav = Navigation::getSidebarMenu('visitor');

            foreach ($sidebarNav as $navItem) {
                if ($navItem['type'] === 'section') {
                    echo '<div class="menu-label">' . e($navItem['label']) . '</div>';
                } elseif ($navItem['type'] === 'link') {
                    $activeClass = Navigation::isActive($navItem) ? 'active' : '';
                    
                    // Assign dynamic badges
                    $badgeVal = '';
                    if ($navItem['label'] === 'Scholarship Matches' && $matchesCount > 0) {
                        $badgeVal = '<span class="sidebar-badge" style="background-color:var(--primary); color:#fff; padding:2px 6px; border-radius:10px; font-size:0.75rem; margin-left:auto">' . $matchesCount . '</span>';
                    } elseif ($navItem['label'] === 'Applications' && $appsCount > 0) {
                        $badgeVal = '<span class="sidebar-badge" style="background-color:#10b981; color:#fff; padding:2px 6px; border-radius:10px; font-size:0.75rem; margin-left:auto">' . $appsCount . '</span>';
                    } elseif ($navItem['label'] === 'Saved Scholarships' && $savedCount > 0) {
                        $badgeVal = '<span class="sidebar-badge" style="background-color:#f59e0b; color:#fff; padding:2px 6px; border-radius:10px; font-size:0.75rem; margin-left:auto">' . $savedCount . '</span>';
                    }
                    
                    echo '<a href="' . url($navItem['url']) . '" class="menu-item ' . $activeClass . '" style="display:flex; align-items:center">';
                    echo '<i data-lucide="' . $navItem['icon'] . '"></i>';
                    echo '<span style="flex-grow:1">' . e($navItem['label']) . '</span>';
                    echo $badgeVal;
                    echo '</a>';
                }
            }
            ?>
            <a href="<?= url('/checkout?plan=premium-monthly') ?>" class="menu-item sidebar-link-whatsapp" id="sidebarWaLink" style="display:flex; align-items:center; margin-top:12px; border-top:1px solid rgba(255,255,255,0.05); padding-top:16px; color:#25D366">
                <i data-lucide="message-square"></i>
                <span>WhatsApp Alerts</span>
            </a>
        </div>
        
        <div style="padding:16px 20px; font-size:0.6875rem; color:#64748b; border-top: 1px solid rgba(255,255,255,0.05)">
            ScholarMatch Student Portal v2.0
        </div>
    </aside>

    <!-- Main Dashboard Section -->
    <main class="admin-main">
        <header class="admin-header">
            <div class="header-left">
                <button class="mobile-toggle" id="mobileToggle" aria-label="Toggle Sidebar" style="display:block">
                    <i data-lucide="menu"></i>
                </button>
                <div style="font-weight: 500; font-size: 0.875rem; color: #64748b; display:flex; align-items:center; gap:8px">
                    <span>Student Dashboard</span>
                </div>
            </div>

            <div class="header-right">
                <a href="<?= url('/profile') ?>" class="user-profile-btn">
                    <div class="avatar-circle" style="background-color: var(--primary); width:32px; height:32px"><?= e($initials) ?></div>
                    <span class="user-name-label" style="max-width:120px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis"><?= e($first_name) ?></span>
                </a>

                <form action="<?= url('/logout') ?>" method="POST" class="logout-form" id="logoutForm">
                    <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Security::csrfToken() ?>">
                    <button type="submit" class="logout-btn">
                        <i data-lucide="log-out"></i>
                        <span class="user-name-label">Logout</span>
                    </button>
                </form>
            </div>
        </header>

        <div class="admin-content">
            <!-- Messages alerts -->
            <?php if (!empty($_SESSION['success'])): ?>
                <div class="admin-alert admin-alert-success" style="margin-bottom:24px">
                    <i data-lucide="circle-check"></i>
                    <span><?= e($_SESSION['success']) ?></span>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (!empty($_SESSION['errors'])): ?>
                <div class="admin-alert admin-alert-error" style="margin-bottom:24px">
                    <i data-lucide="alert-circle"></i>
                    <span>
                        <?php 
                        if (is_array($_SESSION['errors'])) {
                            echo implode('<br>', array_map('e', $_SESSION['errors']));
                        } else {
                            echo e($_SESSION['errors']);
                        }
                        ?>
                    </span>
                </div>
                <?php unset($_SESSION['errors']); ?>
            <?php endif; ?>
