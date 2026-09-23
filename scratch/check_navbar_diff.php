<?php
$im1 = imagecreatefrompng('C:/Users/Hamza Ali/.gemini/antigravity/brain/0cb3e8de-0cb5-4329-9b1e-f9482cb907d7/.user_uploaded/media_1790147645309.png');
$im2 = imagecreatefrompng('C:/Users/Hamza Ali/.gemini/antigravity/brain/0cb3e8de-0cb5-4329-9b1e-f9482cb907d7/.user_uploaded/media_1790147655426.png');

// Find the word "Scholarships" in the navbar.
// The navbar is roughly y=120 to 160.
// Let's sample colors around where "Scholarships" is located.
// "Home" is first, "Scholarships" is second.
// Let's dump all non-white pixels in y=125..155 for both images.
$diffs = [];
for ($y = 125; $y <= 155; $y++) {
    for ($x = 0; $x < 1010; $x++) {
        $c1 = imagecolorat($im1, $x, $y);
        $c2 = imagecolorat($im2, $x, $y);
        if ($c1 !== $c2) {
            $diffs[] = "($x, $y)";
        }
    }
}

echo "Total differences in navbar area (x: 0..1010, y: 125..155): " . count($diffs) . "\n";
