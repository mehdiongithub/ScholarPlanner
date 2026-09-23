<?php
$im = imagecreatefrompng('C:/Users/Hamza Ali/.gemini/antigravity/brain/0cb3e8de-0cb5-4329-9b1e-f9482cb907d7/.user_uploaded/media_1790145481893.png');
// Scan y: 120..180, x: 200..500 for non-white pixels
for ($y = 130; $y <= 165; $y += 2) {
    for ($x = 280; $x <= 360; $x += 2) {
        $rgb = imagecolorat($im, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        if ($r < 240 || $g < 240 || $b < 240) {
            echo "Non-white at ($x, $y): rgb($r, $g, $b)\n";
        }
    }
}
