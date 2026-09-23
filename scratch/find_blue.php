<?php
$im = imagecreatefrompng('C:/Users/Hamza Ali/.gemini/antigravity/brain/0cb3e8de-0cb5-4329-9b1e-f9482cb907d7/.user_uploaded/media_1790145481893.png');
echo "Size: " . imagesx($im) . "x" . imagesy($im) . "\n";
// Find where the navbar is
for ($y = 0; $y < imagesy($im); $y += 5) {
    for ($x = 0; $x < imagesx($im); $x += 20) {
        $rgb = imagecolorat($im, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        if ($b > 180 && $r < 100 && $g < 100) {
            echo "Blue pixel at ($x, $y): rgb($r, $g, $b)\n";
            break 2;
        }
    }
}
