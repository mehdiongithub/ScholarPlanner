<?php use App\Helpers\Security; 
$user = \App\Services\Auth::currentUser();

$pageTitle = $title ?? 'Scholarships & Scholarship Alerts | ScholarPlanner';
$pageDescription = $description ?? 'Discover verified scholarships worldwide with ScholarPlanner. Find opportunities that match your education, field of study, destination and goals.';
$canonical = $canonicalUrl ?? ('https://scholarplanner.com' . parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH));
$robots = $robotsDirective ?? 'index, follow';
$og_type = $ogType ?? 'website';
$og_image = $ogImage ?? 'https://scholarplanner.com/assets/images/logo.webp';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($pageDescription) ?>">
    <link rel="canonical" href="<?= e($canonical) ?>">
    <meta name="robots" content="<?= e($robots) ?>">

    <!-- Open Graph Metadata -->
    <meta property="og:site_name" content="ScholarPlanner">
    <meta property="og:type" content="<?= e($og_type) ?>">
    <meta property="og:title" content="<?= e($pageTitle) ?>">
    <meta property="og:description" content="<?= e($pageDescription) ?>">
    <meta property="og:url" content="<?= e($canonical) ?>">
    <meta property="og:image" content="<?= e($og_image) ?>">

    <!-- Twitter Card Metadata -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($pageTitle) ?>">
    <meta name="twitter:description" content="<?= e($pageDescription) ?>">
    <meta name="twitter:image" content="<?= e($og_image) ?>">

    <link rel="icon" type="image/webp" href="<?= asset('assets/images/logo.webp') ?>">
    <link rel="apple-touch-icon" href="<?= asset('assets/images/logo.webp') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" media="print" onload="this.media='all'">
    <noscript>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    </noscript>
    <?php if (!empty($lcpPreload)): ?>
    <link rel="preload" as="image" href="<?= e($lcpPreload) ?>" fetchpriority="high">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">
    <?php if (!empty($needsSelect2)): ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <?php endif; ?>
    <?php if (!empty($needsCarousel)): ?>
    <!-- Owl Carousel CSS (loaded only when carousel present) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.min.css">
    <?php endif; ?>
    <?php if (!empty($schemaJsonLd)): ?>
    <script type="application/ld+json">
    <?= is_array($schemaJsonLd) ? json_encode($schemaJsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : $schemaJsonLd ?>
    </script>
    <?php endif; ?>
</head>
<body>

    <!-- Public Website Header -->
    <header class="header" id="header" role="banner">
        <div class="header-inner">
            <a href="<?= url('/') ?>" class="logo" aria-label="ScholarPlanner Home">
                <img src="<?= asset('assets/images/logo.webp') ?>" alt="ScholarPlanner Logo" class="logo-img" width="184" height="46">
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
                    <a href="<?= url('/login') ?>" class="btn-login">
                        <i data-lucide="log-in" style="width:15px;height:15px"></i>
                        <span>Log In</span>
                    </a>
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
            <a href="<?= url('/') ?>" class="logo" style="text-decoration:none" aria-label="ScholarPlanner Home">
                <img src="<?= asset('assets/images/logo.webp') ?>" alt="ScholarPlanner Logo" class="logo-img" width="152" height="38">
            </a>
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
                <a href="<?= url('/login') ?>" class="btn-login" style="display:flex;margin-bottom:12px;align-items:center;justify-content:center;gap:8px;">
                    <i data-lucide="log-in" style="width:16px;height:16px"></i>
                    <span>Log In</span>
                </a>
                <a href="<?= url('/register') ?>" class="btn btn-primary" style="justify-content:center">Get Started</a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Main Content Container for Public Pages -->
    <main class="public-main-content">
