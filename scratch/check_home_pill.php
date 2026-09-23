<?php
$im = imagecreatefrompng('C:/Users/Hamza Ali/.gemini/antigravity/brain/0cb3e8de-0cb5-4329-9b1e-f9482cb907d7/.user_uploaded/media_1790145481893.png');
// Let's inspect the bounding box of the blue pill around "Home"
// In media_1790145481893.png:
// Let's find any blue outline pixels in y: 120..160, x: 250..400
for ($y = 125; $y <= 155; $y++) {
    for ($x = 280; $x <= 360; $x++) {
        $rgb = imagecolorat($im, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        if ($b > 200 && $r < 100) { // blue-ish
            echo "Blue pixel at ($x, $y): rgb($r, $g, $b)\n";
            break 2;
        }
    }
}
