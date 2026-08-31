<?php
define('ROOT_PATH', dirname(__DIR__));
require ROOT_PATH . '/vendor/autoload.php';

// Mock session and auth
session_start();
$_SESSION['user_id'] = 1;

try {
    \App\Helpers\View::render('admin.academic.degrees', [
        'degrees' => [],
        'title' => 'Test'
    ]);
    echo "\nSUCCESS: Rendered admin.academic.degrees without namespace errors!\n";
} catch (\Throwable $e) {
    echo "\nERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
