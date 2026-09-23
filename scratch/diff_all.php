<?php
$im1 = imagecreatefrompng('C:/Users/Hamza Ali/.gemini/antigravity/brain/0cb3e8de-0cb5-4329-9b1e-f9482cb907d7/.user_uploaded/media_1790147645309.png');
$im2 = imagecreatefrompng('C:/Users/Hamza Ali/.gemini/antigravity/brain/0cb3e8de-0cb5-4329-9b1e-f9482cb907d7/.user_uploaded/media_1790147655426.png');

echo "Comparing y from 0 to 200:\n";
for ($y = 0; $y <= 200; $y++) {
    $diffs = 0;
    for ($x = 0; $x < 1024; $x++) {
        if (imagecolorat($im1, $x, $y) !== imagecolorat($im2, $x, $y)) {
            $diffs++;
        }
    }
    if ($diffs > 0) {
        echo "y = $y: $diffs different pixels\n";
    }
}
