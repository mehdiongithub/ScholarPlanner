<?php
$img1Path = 'C:/Users/Hamza Ali/.gemini/antigravity/brain/0cb3e8de-0cb5-4329-9b1e-f9482cb907d7/.user_uploaded/media_1790147645309.png';
$img2Path = 'C:/Users/Hamza Ali/.gemini/antigravity/brain/0cb3e8de-0cb5-4329-9b1e-f9482cb907d7/.user_uploaded/media_1790147655426.png';

$im1 = imagecreatefrompng($img1Path);
$im2 = imagecreatefrompng($img2Path);

$w1 = imagesx($im1); $h1 = imagesy($im1);
$w2 = imagesx($im2); $h2 = imagesy($im2);

echo "Img 1: {$w1}x{$h1}\n";
echo "Img 2: {$w2}x{$h2}\n";

// Let's find where the navbar is in both images.
// Browser bar is at the top. Let's see where the navbar starts.
// In the browser, the page viewport usually starts below the address bar.
// Let's compare pixels row by row between y = 80 and y = 250
$diffCount = 0;
$firstDiffY = -1;

for ($y = 80; $y < min($h1, $h2, 250); $y++) {
    $rowDiff = 0;
    for ($x = 0; $x < min($w1, $w2); $x++) {
        $rgb1 = imagecolorat($im1, $x, $y);
        $rgb2 = imagecolorat($im2, $x, $y);
        if ($rgb1 !== $rgb2) {
            $rowDiff++;
            $diffCount++;
            if ($firstDiffY === -1) {
                $firstDiffY = $y;
            }
        }
    }
    if ($rowDiff > 0) {
        echo "Row $y has $rowDiff different pixels\n";
    }
}

echo "First diff at Y = $firstDiffY, Total diffs: $diffCount\n";
