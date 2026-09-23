<?php
function getPage($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, $res];
}

[$c1, $r1] = getPage('http://localhost/scholarship/scholarships');
[$c2, $r2] = getPage('http://localhost/scholarship/scholarships/fully-funded-master-s-scholarship-in-computer-science-in-germany');

echo "URL 1 Code: $c1, Length: " . strlen($r1) . "\n";
echo "URL 2 Code: $c2, Length: " . strlen($r2) . "\n";

// Extract header 1
preg_match('/<header class="header"[^>]*>.*?<\/header>/s', $r1, $h1);
preg_match('/<header class="header"[^>]*>.*?<\/header>/s', $r2, $h2);

echo "Navbar 1 exists: " . (isset($h1[0]) ? 'YES' : 'NO') . "\n";
echo "Navbar 2 exists: " . (isset($h2[0]) ? 'YES' : 'NO') . "\n";

if (isset($h1[0], $h2[0])) {
    echo "Identical? " . ($h1[0] === $h2[0] ? 'YES' : 'NO') . "\n";
    if ($h1[0] !== $h2[0]) {
        echo "DIFF:\n";
        echo "--- H1 ---\n" . $h1[0] . "\n";
        echo "--- H2 ---\n" . $h2[0] . "\n";
    }
}
