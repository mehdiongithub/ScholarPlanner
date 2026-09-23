<?php
require __DIR__ . '/../tests/bootstrap.php';

function getActiveNav(string $uri): string {
    $appUrlPath = parse_url(config('app.url', ''), PHP_URL_PATH) ?: '';
    $path = parse_url($uri, PHP_URL_PATH) ?: '/';
    if ($appUrlPath && str_starts_with($path, $appUrlPath)) {
        $path = substr($path, strlen($appUrlPath));
    }
    $path = '/' . ltrim($path, '/');

    if ($path === '/' || $path === '') return 'home';
    if (str_starts_with($path, '/scholarships')) return 'scholarships';
    if (str_starts_with($path, '/pricing')) return 'pricing';
    if (str_starts_with($path, '/about')) return 'about';
    if (str_starts_with($path, '/faq')) return 'faq';
    if (str_starts_with($path, '/contact')) return 'contact';
    return '';
}

echo "/scholarship/scholarships -> " . getActiveNav('/scholarship/scholarships') . "\n";
echo "/scholarship/scholarships/fully-funded-master-s-scholarship-in-computer-science-in-germany -> " . getActiveNav('/scholarship/scholarships/fully-funded-master-s-scholarship-in-computer-science-in-germany') . "\n";
echo "/scholarship/pricing -> " . getActiveNav('/scholarship/pricing') . "\n";
echo "/scholarship/ -> " . getActiveNav('/scholarship/') . "\n";
