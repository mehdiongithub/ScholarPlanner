<?php

namespace App\Helpers;

class Security {
    public static function escape(?string $value): string {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }

    /**
     * Start secure session
     */
    public static function startSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            // Only set cookie configurations if headers are not yet sent
            if (!headers_sent()) {
                ini_set('session.cookie_httponly', '1');
                ini_set('session.use_only_cookies', '1');
                
                $secure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || config('app.session_secure', false);
                if ($secure) {
                    ini_set('session.cookie_secure', '1');
                }
                
                $lifetime = config('app.session_lifetime', 0); // 0 means session cookie expires on browser close
                $samesite = config('app.session_samesite', 'Lax');
                
                session_set_cookie_params([
                    'lifetime' => $lifetime,
                    'path' => '/',
                    'domain' => '',
                    'secure' => $secure,
                    'httponly' => true,
                    'samesite' => $samesite
                ]);
                session_start();
            } else {
                // Suppress header-sent cookie warnings during CLI test runs
                @session_start();
            }
        }
    }

    /**
     * Generate CSRF token
     */
    public static function csrfToken(): string {
        self::startSession();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function generateCsrfToken(): string {
        return self::csrfToken();
    }

    /**
     * Verify CSRF token
     */
    public static function verifyCsrfToken(?string $token): bool {
        self::startSession();
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}
