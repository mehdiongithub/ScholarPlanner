<?php
$img1Path = 'C:/Users/Hamza Ali/.gemini/antigravity/brain/0cb3e8de-0cb5-4329-9b1e-f9482cb907d7/.user_uploaded/media_1790147645309.png';
$img2Path = 'C:/Users/Hamza Ali/.gemini/antigravity/brain/0cb3e8de-0cb5-4329-9b1e-f9482cb907d7/.user_uploaded/media_1790147655426.png';

$im1 = imagecreatefrompng($img1Path);
$im2 = imagecreatefrompng($img2Path);

for ($y = 80; $y <= 120; $y += 5) {
    $diffXs = [];
    for ($x = 0; $x < 1024; $x++) {
        if (imagecolorat($im1, $x, $y) !== imagecolorat($im2, $x, $y)) {
            $diffXs[] = $x;
        }
    }
    if (!empty($diffXs)) {
        echo "Y=$y diff X range: " . min($diffXs) . " to " . max($diffXs) . " (count: " . count($diffXs) . ")\n";
    }
}

for ($y = 125; $y <= 155; $y += 5) {
    $diffXs = [];
    for ($x = 0; $x < 1024; $x++) {
        if (imagecolorat($im1, $x, $y) !== imagecolorat($im2, $x, $y)) {
            $diffXs[] = $x;
        }
    }
    if (!empty($diffXs)) {
        echo "Y=$y diff X range: " . min($diffXs) . " to " . max($diffXs) . " (count: " . count($diffXs) . ")\n";
    }
}
