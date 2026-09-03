<?php
/**
 * Test Bootstrap File
 */

define('ROOT_PATH', dirname(__DIR__));

// Load composer dependencies
require_once ROOT_PATH . '/vendor/autoload.php';

// Mock server environments for CLI test runner
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['SCRIPT_NAME'] = '/index.php';

// Load env variables
try {
    if (file_exists(ROOT_PATH . '/.env')) {
        $dotenv = \Dotenv\Dotenv::createImmutable(ROOT_PATH);
        $dotenv->load();
    }
} catch (\Exception $e) {
    // Tests can run with default fallback environments if .env is missing
}

$_ENV['APP_ENV'] = 'testing';
$_ENV['APP_DEBUG'] = 'true';
$_ENV['MAIL_MAILER'] = 'log';
if (!defined('TESTING_MODE')) {
    define('TESTING_MODE', true);
}

// Start logger
\App\Services\Logger::init();

date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'UTC');
