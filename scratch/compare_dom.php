<?php
$r1 = file_get_contents('http://localhost/scholarship/scholarships');
$r2 = file_get_contents('http://localhost/scholarship/scholarships/fully-funded-master-s-scholarship-in-computer-science-in-germany');

// Extract from <!DOCTYPE html> up to <div class="page-content">
preg_match('/^(.*?)<div class="page-content">/s', $r1, $m1);
preg_match('/^(.*?)<div class="page-content">/s', $r2, $m2);

echo "Top HTML up to .page-content:\n";
echo "Page 1 length: " . strlen($m1[1] ?? '') . "\n";
echo "Page 2 length: " . strlen($m2[1] ?? '') . "\n";

// Let's strip <style>...</style> and <title>, <meta>, <script> and compare just the DOM structure!
function cleanDom($html) {
    $html = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $html);
    $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);
    $html = preg_replace('/<meta\b[^>]*>/is', '', $html);
    $html = preg_replace('/<title\b[^>]*>.*?<\/title>/is', '', $html);
    $html = preg_replace('/<link\b[^>]*>/is', '', $html);
    $html = preg_replace('/\s+/', ' ', $html);
    return trim($html);
}

$dom1 = cleanDom($m1[1] ?? '');
$dom2 = cleanDom($m2[1] ?? '');

echo "DOM 1:\n" . $dom1 . "\n---\nDOM 2:\n" . $dom2 . "\n";
echo "DOM identical? " . ($dom1 === $dom2 ? 'YES' : 'NO') . "\n";
