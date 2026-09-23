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
     * Resolve public assets paths, handling subdirectory routing and cache busting.
     */
    function asset(string $path): string {
        $cleanPath = ltrim($path, '/');

        $scriptName = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        $prefix = ($scriptName !== '/' && $scriptName !== '\\' && !empty($scriptName))
            ? rtrim($scriptName, '/') . '/'
            : '/';

        $url = $prefix . $cleanPath;

        // Append file modification timestamp query string for immutable cache busting
        $fullPath = ROOT_PATH . '/' . $cleanPath;
        if (file_exists($fullPath)) {
            $url .= '?v=' . filemtime($fullPath);
        }

        return $url;
    }
}

if (!function_exists('url')) {
    /**
     * Resolve internal paths, handling subdirectory routing.
     */
    function url(string $path): string {
        $scriptName = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        $scriptName = str_replace('\\', '/', $scriptName);
        if ($scriptName !== '/' && !empty($scriptName) && $scriptName !== '.') {
            return rtrim($scriptName, '/') . '/' . ltrim($path, '/');
        }
        return '/' . ltrim($path, '/');
    }
}

if (!function_exists('absolute_url')) {
    /**
     * Resolve fully-qualified absolute URL with scheme and host for emails and external links.
     */
    function absolute_url(string $path): string {
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        $configuredAppUrl = rtrim(config('app.url', $_ENV['APP_URL'] ?? 'http://localhost/scholarship'), '/');
        $cleanPath = '/' . ltrim($path, '/');

        if (!empty($_SERVER['HTTP_HOST'])) {
            $isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
                || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
            $scheme = $isHttps ? 'https://' : 'http://';

            $subDir = '';
            if (!empty($_SERVER['SCRIPT_NAME'])) {
                $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
                if ($dir !== '/' && $dir !== '.' && !empty($dir)) {
                    $subDir = '/' . trim($dir, '/');
                }
            }
            if (empty($subDir) && !empty($configuredAppUrl)) {
                $p = parse_url($configuredAppUrl, PHP_URL_PATH);
                if (!empty($p) && $p !== '/') {
                    $subDir = '/' . trim($p, '/');
                }
            }
            $base = $scheme . $_SERVER['HTTP_HOST'] . $subDir;
        } else {
            $base = $configuredAppUrl ?: 'http://localhost/scholarship';
        }

        // Avoid duplicating subpath if $cleanPath already includes the base subpath
        $basePath = parse_url($base, PHP_URL_PATH) ?? '';
        if (!empty($basePath) && $basePath !== '/') {
            $basePath = '/' . trim($basePath, '/');
            if (strpos($cleanPath, $basePath . '/') === 0) {
                $cleanPath = substr($cleanPath, strlen($basePath));
            } elseif ($cleanPath === $basePath) {
                $cleanPath = '/';
            }
        }

        return rtrim($base, '/') . '/' . ltrim($cleanPath, '/');
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
        $id = \App\Services\UrlIdService::decode($token);
        if ($id === null && $token !== null && is_numeric($token) && (int)$token > 0) {
            return (int)$token;
        }
        return $id;
    }
}
