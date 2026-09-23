<?php
$im2 = imagecreatefrompng('C:/Users/Hamza Ali/.gemini/antigravity/brain/0cb3e8de-0cb5-4329-9b1e-f9482cb907d7/.user_uploaded/media_1790147655426.png');
$im3 = imagecreatefrompng('C:/Users/Hamza Ali/.gemini/antigravity/brain/0cb3e8de-0cb5-4329-9b1e-f9482cb907d7/.user_uploaded/media_1790147676312.png');
$diff = 0;
for ($y=0; $y<576; $y++) {
    for ($x=0; $x<1024; $x++) {
        if (imagecolorat($im2, $x, $y) !== imagecolorat($im3, $x, $y)) $diff++;
    }
}
echo "Diff between img 2 and 3: $diff\n";
