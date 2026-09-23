<?php
$index = file_get_contents('app/Views/scholarships/index.php');
$show = file_get_contents('app/Views/scholarships/show.php');

// Extract all class names in index.php
preg_match_all('/class=["\']([^"\']+)["\']/', $index, $mIndex);
preg_match_all('/class=["\']([^"\']+)["\']/', $show, $mShow);

$classesIndex = array_unique(explode(' ', implode(' ', $mIndex[1])));
$classesShow = array_unique(explode(' ', implode(' ', $mShow[1])));

echo "Classes in index but not show:\n";
print_r(array_diff($classesIndex, $classesShow));

echo "Classes in show but not index:\n";
print_r(array_diff($classesShow, $classesIndex));
