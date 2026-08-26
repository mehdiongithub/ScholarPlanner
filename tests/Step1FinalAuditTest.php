<?php

class Step1FinalAuditTest {
    /**
     * Perform critical checks for files, configurations, security foundations, escaping, and autoloader logic.
     */
    public function run(): void {
        echo "--- Running Step1FinalAuditTest ---\n";
        
        // 1. Audit Required Directories
        $requiredDirs = [
            ROOT_PATH . '/app/Controllers',
            ROOT_PATH . '/app/Helpers',
            ROOT_PATH . '/app/Services',
            ROOT_PATH . '/app/Views',
            ROOT_PATH . '/config',
            ROOT_PATH . '/routes',
            ROOT_PATH . '/assets/css',
            ROOT_PATH . '/assets/js',
            ROOT_PATH . '/storage/logs',
            ROOT_PATH . '/storage/cache',
            ROOT_PATH . '/storage/uploads',
            ROOT_PATH . '/tests',
        ];
        
        foreach ($requiredDirs as $dir) {
            if (!file_exists($dir) || !is_dir($dir)) {
                throw new \Exception("Audit Failure: Required folder '$dir' is missing or not a directory.");
            }
            echo "✔ Folder verified: " . str_replace(ROOT_PATH, '', $dir) . "\n";
        }
        
        // 2. Audit Core Files
        $requiredFiles = [
            ROOT_PATH . '/index.php',
            ROOT_PATH . '/.env.example',
            ROOT_PATH . '/.gitignore',
            ROOT_PATH . '/.htaccess',
            ROOT_PATH . '/composer.json',
            ROOT_PATH . '/README.md',
            ROOT_PATH . '/routes/web.php',
            ROOT_PATH . '/app/Helpers/functions.php',
            ROOT_PATH . '/app/Helpers/Security.php',
            ROOT_PATH . '/app/Helpers/View.php',
            ROOT_PATH . '/app/Services/Database.php',
            ROOT_PATH . '/app/Services/Logger.php',
            ROOT_PATH . '/app/Services/Router.php',
            ROOT_PATH . '/app/Controllers/HomeController.php',
            ROOT_PATH . '/app/Controllers/PlaceholderController.php',
            ROOT_PATH . '/app/Views/home.php',
            ROOT_PATH . '/app/Views/placeholder.php',
            ROOT_PATH . '/app/Views/errors/404.php',
            ROOT_PATH . '/assets/css/style.css',
            ROOT_PATH . '/assets/js/main.js',
        ];
        
        foreach ($requiredFiles as $file) {
            if (!file_exists($file)) {
                throw new \Exception("Audit Failure: Critical file '$file' is missing.");
            }
            echo "✔ File verified: " . str_replace(ROOT_PATH, '', $file) . "\n";
        }
        
        // 3. Autoload Check
        $autoloader = ROOT_PATH . '/vendor/autoload.php';
        if (!file_exists($autoloader)) {
            throw new \Exception("Audit Failure: Composer autoloader vendor/autoload.php not found.");
        }
        echo "✔ Composer autoloader mapped correctly.\n";
        
        // 4. Config Integrity Check
        $configs = ['app', 'database', 'mail', 'whatsapp', 'payment'];
        foreach ($configs as $configName) {
            $configPath = ROOT_PATH . "/config/$configName.php";
            if (!file_exists($configPath)) {
                 throw new \Exception("Audit Failure: Config file '$configPath' missing.");
            }
            $configData = require $configPath;
            if (!is_array($configData)) {
                 throw new \Exception("Audit Failure: Config file '$configPath' must return an array.");
            }
            echo "✔ Configuration loader validated: $configName.php\n";
        }
        
        // 5. Security Escaping and Sanitization check
        $rawHTML = '<script>alert("xss")</script>';
        $escaped = e($rawHTML);
        if ($escaped !== '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;') {
             throw new \Exception("Audit Failure: HTML escaping helper returns unescaped string '$escaped'.");
        }
        echo "✔ Output escaping e() verified safe.\n";
        
        // 6. CSRF strength testing
        if (!method_exists(\App\Helpers\Security::class, 'csrfToken') || !method_exists(\App\Helpers\Security::class, 'verifyCsrfToken')) {
            throw new \Exception("Audit Failure: Security token helper methods are missing.");
        }
        
        $token1 = \App\Helpers\Security::csrfToken();
        $token2 = \App\Helpers\Security::csrfToken();
        if ($token1 !== $token2) {
             throw new \Exception("Audit Failure: CSRF session tokens do not match within session lifecycle.");
        }
        if (strlen($token1) !== 64) {
             throw new \Exception("Audit Failure: CSRF token does not contain 64 characters (32 bytes).");
        }
        if (!\App\Helpers\Security::verifyCsrfToken($token1)) {
             throw new \Exception("Audit Failure: Valid CSRF token validation failed.");
        }
        if (\App\Helpers\Security::verifyCsrfToken('hacker-injection-token')) {
             throw new \Exception("Audit Failure: CSRF validated bad tokens.");
        }
        echo "✔ CSRF token strength and cryptographic validation passed.\n";
        
        echo "Step1FinalAuditTest PASSED.\n\n";
    }
}
