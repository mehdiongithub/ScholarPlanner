<?php
require __DIR__ . '/../config/bootstrap.php';

// Test page 1: /scholarships
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/scholarship/scholarships';
$_SERVER['HTTP_HOST'] = 'localhost';

ob_start();
$title = 'Search Scholarships Worldwide | ScholarPlanner';
$needsSelect2 = true;
include ROOT_PATH . '/app/Views/layouts/public_header.php';
$header1 = ob_get_clean();

// Test page 2: /scholarships/fully-funded-master-s-scholarship-in-computer-science-in-germany
$_SERVER['REQUEST_URI'] = '/scholarship/scholarships/fully-funded-master-s-scholarship-in-computer-science-in-germany';
ob_start();
$title = 'Fully Funded Master’s Scholarship in Computer Science in Germany | ScholarPlanner';
include ROOT_PATH . '/app/Views/layouts/public_header.php';
$header2 = ob_get_clean();

echo "Header 1 length: " . strlen($header1) . "\n";
echo "Header 2 length: " . strlen($header2) . "\n";
echo "Are headers identical (excluding title/canonical/Select2)?\n";

// Let's extract <header class="header" id="header" role="banner"> ... </header>
preg_match('/<header class="header".*?<\/header>/s', $header1, $m1);
preg_match('/<header class="header".*?<\/header>/s', $header2, $m2);

if (isset($m1[0], $m2[0])) {
    echo "Navbar 1 == Navbar 2: " . ($m1[0] === $m2[0] ? "YES" : "NO") . "\n";
    if ($m1[0] !== $m2[0]) {
        echo "Navbar 1:\n" . $m1[0] . "\n---\nNavbar 2:\n" . $m2[0] . "\n";
    }
} else {
    echo "Could not find headers in one or both!\n";
}
