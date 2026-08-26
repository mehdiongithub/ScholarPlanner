<?php

if (!function_exists('config')) {
    /**
     * Get a configuration value using dot notation.
     */
    function config(string $key, $default = null) {
        static $configs = [];
        
        $parts = explode('.', $key);
        $filename = $parts[0];
        
        if (!isset($configs[$filename])) {
            $path = ROOT_PATH . '/config/' . $filename . '.php';
            if (file_exists($path)) {
                $configs[$filename] = require $path;
            } else {
                $configs[$filename] = [];
            }
        }
        
        $value = $configs[$filename];
        for ($i = 1; $i < count($parts); $i++) {
            if (is_array($value) && isset($value[$parts[$i]])) {
                $value = $value[$parts[$i]];
            } else {
                return $default;
            }
        }
        
        return $value;
    }
}

if (!function_exists('e')) {
    /**
     * Escape HTML entities in a string.
     */
    function e(string $value): string {
        return \App\Helpers\Security::escape($value);
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Generate or fetch the CSRF token.
     */
    function csrf_token(): string {
        return \App\Helpers\Security::csrfToken();
    }
}

if (!function_exists('view')) {
    /**
     * Render a view file.
     */
    function view(string $name, array $data = []): void {
        \App\Helpers\View::render($name, $data);
    }
}

if (!function_exists('asset')) {
    /**
     * Resolve public assets paths, handling subdirectory routing.
     */
    function asset(string $path): string {
        $scriptName = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        if ($scriptName !== '/' && $scriptName !== '\\' && !empty($scriptName)) {
            return rtrim($scriptName, '/') . '/' . ltrim($path, '/');
        }
        return '/' . ltrim($path, '/');
    }
}

if (!function_exists('url')) {
    /**
     * Resolve internal paths, handling subdirectory routing.
     */
    function url(string $path): string {
        $scriptName = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        if ($scriptName !== '/' && $scriptName !== '\\' && !empty($scriptName)) {
            return rtrim($scriptName, '/') . '/' . ltrim($path, '/');
        }
        return '/' . ltrim($path, '/');
    }
}
