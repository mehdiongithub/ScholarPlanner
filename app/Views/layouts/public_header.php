<?php use App\Helpers\Security; 
$user = \App\Services\Auth::currentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Personalized Scholarship Alerts | ScholarMatch') ?></title>
    <meta name="description" content="<?= e($description ?? 'Discover scholarship opportunities matched to your education, academic background and goals.') ?>">
    <link rel="canonical" href="<?= e(($_ENV['APP_URL'] ?? 'http://localhost') . parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH)) ?>">
    <meta property="og:title" content="<?= e($title ?? 'Personalized Scholarship Alerts | ScholarMatch') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@0.460.0"></script>
    <link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/admin.css') ?>">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <!-- Owl Carousel CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.min.css">
</head>
<body>

    <!-- Public Website Header -->
    <header class="header" id="header" role="banner">
        <div class="header-inner">
            <a href="<?= url('/') ?>" class="logo" aria-label="ScholarMatch Home">
                <div class="logo-icon">
                    <i data-lucide="graduation-cap"></i>
                </div>
                <span class="logo-text">ScholarMatch</span>
            </a>

            <nav class="nav-desktop" aria-label="Main navigation">
                <a href="<?= url('/') ?>">Home</a>
                <a href="<?= url('/scholarships') ?>">Scholarships</a>
                <a href="<?= url('/pricing') ?>">Pricing</a>
                <a href="<?= url('/about') ?>">About Us</a>
                <a href="<?= url('/faq') ?>">FAQ</a>
                <a href="<?= url('/contact') ?>">Contact</a>
            </nav>

            <div class="header-actions">
                <?php if ($user): ?>
                    <a href="<?= url($user['role_name'] === 'visitor' ? '/dashboard' : '/admin') ?>" class="btn btn-primary btn-get-started">
                        <span>Go to Dashboard</span>
                        <i data-lucide="arrow-right" style="width:16px;height:16px;margin-left:4px"></i>
                    </a>
                <?php else: ?>
                    <a href="<?= url('/login') ?>" class="btn-login">Log In</a>
                    <a href="<?= url('/register') ?>" class="btn btn-primary btn-get-started">Get Started</a>
                <?php endif; ?>
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
        <div class="mobile-menu-body">
            <a href="<?= url('/') ?>">Home</a>
            <a href="<?= url('/scholarships') ?>">Scholarships</a>
            <a href="<?= url('/pricing') ?>">Pricing</a>
            <a href="<?= url('/about') ?>">About Us</a>
            <a href="<?= url('/faq') ?>">FAQ</a>
            <a href="<?= url('/contact') ?>">Contact</a>
            <hr style="border:0;border-top:1px solid var(--border);margin:16px 0">
            <?php if ($user): ?>
                <a href="<?= url($user['role_name'] === 'visitor' ? '/dashboard' : '/admin') ?>" class="btn btn-primary" style="justify-content:center">Dashboard</a>
            <?php else: ?>
                <a href="<?= url('/login') ?>" class="btn-login" style="display:block;margin-bottom:12px">Log In</a>
                <a href="<?= url('/register') ?>" class="btn btn-primary" style="justify-content:center">Get Started</a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Main Content Container for Public Pages -->
    <main class="public-main-content">
