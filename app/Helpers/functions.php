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
    function e(?string $value): string {
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

if (!function_exists('active_route')) {
    /**
     * Check if current URI matches a path prefix for sidebar active state toggles.
     */
    function active_route(string $path): bool {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        if ($uri !== '/') {
            $uri = rtrim($uri, '/');
        }
        if ($path === '/admin') {
            return $uri === '/admin';
        }
        return strpos($uri, $path) === 0;
    }
}

if (!function_exists('encode_id')) {
    function encode_id(int $id): string {
        return \App\Services\UrlIdService::encode($id);
    }
}

if (!function_exists('decode_id')) {
    function decode_id(?string $token): ?int {
        return \App\Services\UrlIdService::decode($token);
    }
}
