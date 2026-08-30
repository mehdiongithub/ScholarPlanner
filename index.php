<?php
/**
 * Front Controller & Application Bootstrap
 *
 * Core PHP SaaS Scholarship Platform
 * Entry point that routes requests to correct controllers.
 */

// Define application paths
define('ROOT_PATH', __DIR__);

// Load Composer Autoloader
$autoloaderPath = ROOT_PATH . '/vendor/autoload.php';
if (!file_exists($autoloaderPath)) {
    http_response_code(500);
    echo "<h1>Composer Autoloader Not Found</h1>";
    echo "<p>Please run <code>composer install</code> to initialize dependencies.</p>";
    exit;
}
require $autoloaderPath;

// Load Environment Variables
try {
    if (file_exists(ROOT_PATH . '/.env')) {
        $dotenv = \Dotenv\Dotenv::createImmutable(ROOT_PATH);
        $dotenv->load();
    }
} catch (\Exception $e) {
    error_log("Dotenv load error: " . $e->getMessage());
}

// Configure error reporting dynamically based on debug settings
$debug = false;
try {
    $debug = config('app.debug', false);
} catch (\Exception $e) {
    // Fail-safe if configuration is unreadable
}

if ($debug) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(0);
}

// Set Application Timezone
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'UTC');

// Centralized Unhandled Exception Handler
set_exception_handler(function ($exception) use ($debug) {
    try {
        \App\Services\Logger::error("Unhandled Exception: " . $exception->getMessage(), [
            'file' => $exception->getFile(),
            'line' => $exception->getLine()
        ]);
    } catch (\Exception $e) {
        error_log("Logger failure during exception logging: " . $e->getMessage());
    }
    
    http_response_code(500);
    if ($debug) {
        echo "<h1>500 Internal Server Error</h1>";
        echo "<p><strong>Exception:</strong> " . htmlspecialchars($exception->getMessage()) . "</p>";
        echo "<p><strong>File:</strong> " . htmlspecialchars($exception->getFile()) . " on line " . $exception->getLine() . "</p>";
        echo "<pre>" . htmlspecialchars($exception->getTraceAsString()) . "</pre>";
    } else {
        echo "<h1>500 Internal Server Error</h1>";
        echo "<p>Something went wrong on our end. Please try again later.</p>";
    }
    exit;
});

// Convert PHP runtime errors to Exceptions
set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return;
    }
    throw new \ErrorException($message, 0, $severity, $file, $line);
});

// Initialize Logger
\App\Services\Logger::init();

// Send Security Headers
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");

// Load Router and Dispatch request
$router = new \App\Services\Router();

// Register Web Routes
require ROOT_PATH . '/routes/web.php';

// Dispatch current request
$router->dispatch();
