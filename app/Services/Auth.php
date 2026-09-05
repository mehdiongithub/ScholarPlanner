<?php

namespace App\Services;

use PDO;
use Exception;
use App\Helpers\Security;

class Auth {
    private static ?array $currentUser = null;

    /**
     * Authenticate user credentials, check rate limits, and start session
     */
    public static function login(string $email, string $password, bool $rememberMe = false): bool {
        $db = Database::connection();
        $email = strtolower(trim($email));
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        // 1. Rate Limit Verification (5 failed attempts within 15 minutes)
        if (self::isThrottled($email, $ip)) {
            self::logAudit(null, 'login_throttled', 'auth', 'users', null, $ip, [
                'email' => $email,
                'reason' => 'Rate limit exceeded'
            ]);
            throw new Exception("Too many failed login attempts. Please try again in 15 minutes.");
        }

        // 2. Fetch User with Role
        $stmt = $db->prepare("
            SELECT u.*, r.name as role_name 
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE u.email = :email
            LIMIT 1
        ");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        // 3. Verify User Status & Passwords
        if (!$user || !password_verify($password, $user['password_hash'])) {
            // Track failure
            self::recordFailedAttempt($email, $ip);
            self::logAudit(null, 'login_failed', 'auth', 'users', null, $ip, ['email' => $email]);
            return false;
        }

        // Check if user is suspended
        if ($user['status'] === 'suspended') {
            self::logAudit($user['id'], 'login_suspended_blocked', 'auth', 'users', $user['id'], $ip);
            throw new Exception("Your account has been suspended. Please contact support.");
        }

        // Allow pending users to authenticate so they can complete email verification
        if ($user['status'] === 'pending') {
            Security::startSession();
            if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
                session_regenerate_id(true); // Prevent session fixation
            }

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role_name'] = $user['role_name'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['user_email'] = $user['email'];

            // Update login metrics
            $upd = $db->prepare("UPDATE users SET last_login_at = NOW(), last_login_ip = :ip WHERE id = :id");
            $upd->execute(['ip' => $ip, 'id' => $user['id']]);

            // Clean previous failed attempts
            $del = $db->prepare("DELETE FROM login_attempts WHERE email = :email OR ip_address = :ip");
            $del->execute(['email' => $email, 'ip' => $ip]);

            self::logAudit($user['id'], 'login_pending_verify', 'auth', 'users', $user['id'], $ip);
            self::$currentUser = $user;
            return true;
        }

        // Check if user is active
        if ($user['status'] !== 'active') {
            self::logAudit($user['id'], 'login_inactive_blocked', 'auth', 'users', $user['id'], $ip);
            throw new Exception("Your account is not active. Current status: " . ucfirst($user['status']));
        }

        // 4. Successful Authentication Setup
        Security::startSession();
        if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
            session_regenerate_id(true); // Prevent session fixation
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role_name'] = $user['role_name'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['user_email'] = $user['email'];

        // Update login metrics
        $upd = $db->prepare("UPDATE users SET last_login_at = NOW(), last_login_ip = :ip WHERE id = :id");
        $upd->execute(['ip' => $ip, 'id' => $user['id']]);

        // Clean previous failed attempts for this IP and email
        $del = $db->prepare("DELETE FROM login_attempts WHERE email = :email OR ip_address = :ip");
        $del->execute(['email' => $email, 'ip' => $ip]);

        // 5. Handle Remember Me persistent session
        if ($rememberMe) {
            self::createRememberMeToken((int)$user['id']);
        }

        self::logAudit($user['id'], 'login_success', 'auth', 'users', $user['id'], $ip);
        self::$currentUser = $user;

        return true;
    }

    /**
     * Check if user is throttled due to multiple failed login attempts
     */
    private static function isThrottled(string $email, string $ip): bool {
        $db = Database::connection();
        $stmt = $db->prepare("
            SELECT COUNT(*) 
            FROM login_attempts 
            WHERE (email = :email OR ip_address = :ip) 
              AND attempted_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)
        ");
        $stmt->execute(['email' => $email, 'ip' => $ip]);
        return (int)$stmt->fetchColumn() >= 5;
    }

    /**
     * Record a failed login attempt
     */
    private static function recordFailedAttempt(string $email, string $ip): void {
        $db = Database::connection();
        $stmt = $db->prepare("INSERT INTO login_attempts (email, ip_address) VALUES (:email, :ip)");
        $stmt->execute(['email' => $email, 'ip' => $ip]);
    }

    /**
     * Set remember-me cookie and token mapping
     */
    private static function createRememberMeToken(int $userId): void {
        $selector = bin2hex(random_bytes(8)); // 16 chars
        $validator = bin2hex(random_bytes(32)); // 64 chars
        $tokenHash = hash('sha256', $validator);
        
        $lifetime = config('app.session_lifetime', 2592000); // 30 days default
        $expiresAt = date('Y-m-d H:i:s', time() + $lifetime);

        $db = Database::connection();
        $stmt = $db->prepare("INSERT INTO remember_tokens (user_id, selector, token_hash, expires_at) VALUES (:user_id, :selector, :token_hash, :expires_at)");
        $stmt->execute([
            'user_id' => $userId,
            'selector' => $selector,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt
        ]);

        $secure = config('app.session_secure', false);
        $samesite = config('app.session_samesite', 'Lax');

        // Set cookie formatted as selector:validator
        if (!headers_sent()) {
            setcookie(
                'remember_me',
                "$selector:$validator",
                [
                    'expires' => time() + $lifetime,
                    'path' => '/',
                    'domain' => '',
                    'secure' => $secure,
                    'httponly' => true,
                    'samesite' => $samesite
                ]
            );
        }
    }

    /**
     * Check remember me cookie and auto-reauthenticate user (with token rotation)
     */
    public static function checkRememberMe(): bool {
        if (self::isAuthenticated()) {
            return true;
        }

        $cookie = $_COOKIE['remember_me'] ?? null;
        if (!$cookie || strpos($cookie, ':') === false) {
            return false;
        }

        [$selector, $validator] = explode(':', $cookie, 2);
        
        $db = Database::connection();
        $stmt = $db->prepare("SELECT * FROM remember_tokens WHERE selector = :selector LIMIT 1");
        $stmt->execute(['selector' => $selector]);
        $token = $stmt->fetch();

        if (!$token || strtotime($token['expires_at']) < time()) {
            self::clearRememberMeCookie();
            return false;
        }

        // Verify validator hash
        if (!hash_equals($token['token_hash'], hash('sha256', $validator))) {
            // Theft warning: Delete all tokens for this user as a defense action
            $del = $db->prepare("DELETE FROM remember_tokens WHERE user_id = :user_id");
            $del->execute(['user_id' => $token['user_id']]);
            self::clearRememberMeCookie();
            self::logAudit($token['user_id'], 'remember_token_theft_detected', 'auth', 'users', $token['user_id']);
            return false;
        }

        // Load user data
        $stmtUser = $db->prepare("
            SELECT u.*, r.name as role_name 
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE u.id = :id
            LIMIT 1
        ");
        $stmtUser->execute(['id' => $token['user_id']]);
        $user = $stmtUser->fetch();

        if (!$user || $user['status'] !== 'active') {
            self::clearRememberMeCookie();
            return false;
        }

        // Login user
        Security::startSession();
        if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
            session_regenerate_id(true);
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role_name'] = $user['role_name'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['user_email'] = $user['email'];

        // Rotate Validator token (set new one to prevent replay attacks)
        $newValidator = bin2hex(random_bytes(32));
        $newTokenHash = hash('sha256', $newValidator);
        
        $upd = $db->prepare("UPDATE remember_tokens SET token_hash = :hash WHERE id = :id");
        $upd->execute(['hash' => $newTokenHash, 'id' => $token['id']]);

        $lifetime = config('app.session_lifetime', 2592000);
        $secure = config('app.session_secure', false);
        $samesite = config('app.session_samesite', 'Lax');

        if (!headers_sent()) {
            setcookie(
                'remember_me',
                "$selector:$newValidator",
                [
                    'expires' => time() + $lifetime,
                    'path' => '/',
                    'domain' => '',
                    'secure' => $secure,
                    'httponly' => true,
                    'samesite' => $samesite
                ]
            );
        }

        self::logAudit($user['id'], 'login_remember_me_success', 'auth', 'users', $user['id']);
        self::$currentUser = $user;
        return true;
    }

    /**
     * Clear remember-me cookie and remove token from database
     */
    private static function clearRememberMeCookie(): void {
        if (isset($_COOKIE['remember_me'])) {
            [$selector, ] = explode(':', $_COOKIE['remember_me'], 2);
            $db = Database::connection();
            $stmt = $db->prepare("DELETE FROM remember_tokens WHERE selector = :selector");
            $stmt->execute(['selector' => $selector]);
        }
        
        // Remove cookie
        if (!headers_sent()) {
            setcookie('remember_me', '', [
                'expires' => time() - 3600,
                'path' => '/',
                'httponly' => true
            ]);
        }
    }

    /**
     * Terminate user session and clear remembers
     */
    public static function logout(): void {
        Security::startSession();
        $userId = $_SESSION['user_id'] ?? null;

        if ($userId) {
            self::logAudit($userId, 'logout', 'auth', 'users', $userId);
        }

        self::clearRememberMeCookie();

        // Flush session variables
        $_SESSION = [];

        if (ini_get("session.use_cookies") && !headers_sent()) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), 
                '', 
                time() - 42000,
                $params["path"], 
                $params["domain"],
                $params["secure"], 
                $params["httponly"]
            );
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            if (defined('TESTING_MODE') && TESTING_MODE) {
                $_SESSION = [];
            } else {
                session_destroy();
            }
        }
        self::$currentUser = null;
    }

    /**
     * Determine if a user is authenticated
     */
    public static function isAuthenticated(): bool {
        Security::startSession();
        return isset($_SESSION['user_id']);
    }

    /**
     * Get details of the currently authenticated user
     */
    public static function currentUser(): ?array {
        if (!self::isAuthenticated()) {
            self::$currentUser = null;
            return null;
        }

        if (self::$currentUser !== null && (int)self::$currentUser['id'] === (int)$_SESSION['user_id']) {
            return self::$currentUser;
        }

        $db = Database::connection();
        $stmt = $db->prepare("
            SELECT u.*, r.name as role_name 
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE u.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $_SESSION['user_id']]);
        $user = $stmt->fetch();

        self::$currentUser = $user ?: null;
        return self::$currentUser;
    }

    /**
     * Get current user ID
     */
    public static function userId(): ?int {
        Security::startSession();
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Check if user is in target role(s)
     */
    public static function hasRole($roles): bool {
        $user = self::currentUser();
        if (!$user) {
            return false;
        }

        $roles = is_array($roles) ? $roles : [$roles];
        return in_array($user['role_name'], $roles);
    }

    /**
     * Check if user has permission (Admin gets bypass)
     */
    public static function hasPermission(string $permission): bool {
        $user = self::currentUser();
        if (!$user) {
            return false;
        }

        // 1. Admin always has full access
        if ($user['role_name'] === 'admin') {
            return true;
        }

        // 2. Fetch role permissions mapping
        $db = Database::connection();
        $stmt = $db->prepare("
            SELECT COUNT(*) 
            FROM role_permissions rp
            JOIN permissions p ON rp.permission_id = p.id
            WHERE rp.role_id = :role_id AND p.name = :permission
        ");
        $stmt->execute([
            'role_id' => $user['role_id'],
            'permission' => $permission
        ]);

        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Enforce authentication, redirect to login on failure
     */
    public static function requireAuth(): void {
        // Attempt remember-me automatic login first
        self::checkRememberMe();

        if (!self::isAuthenticated()) {
            if (defined('TESTING_MODE') && TESTING_MODE) {
                throw new \RuntimeException("Redirect to login");
            }
            $redirectUrl = url('/login');
            header("Location: $redirectUrl");
            exit();
        }

        // Redirect pending/unverified users to email verification
        $user = self::currentUser();
        if ($user && $user['status'] === 'pending') {
            $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
            if (strpos($currentPath, '/verify-email') === false && strpos($currentPath, '/logout') === false) {
                if (defined('TESTING_MODE') && TESTING_MODE) {
                    throw new \RuntimeException("Redirect to verify-email");
                }
                $verifyUrl = url('/verify-email');
                header("Location: $verifyUrl");
                exit();
            }
        }
    }

    /**
     * Enforce role membership
     */
    public static function requireRole($roles): void {
        self::requireAuth();

        if (!self::hasRole($roles)) {
            self::abort403();
        }
    }

    /**
     * Enforce permission credentials
     */
    public static function requirePermission(string $permission): void {
        self::requireAuth();

        if (!self::hasPermission($permission)) {
            self::abort403();
        }
    }

    /**
     * Redirect to a custom 403 error page
     */
    public static function abort403(): void {
        if (!headers_sent()) {
            http_response_code(403);
        }
        if (defined('TESTING_MODE') && TESTING_MODE) {
            throw new \RuntimeException("Abort 403 Forbidden");
        }
        try {
            view('errors.403');
        } catch (\Exception $e) {
            echo "<h1>403 Forbidden</h1><p>You do not have permission to access this resource.</p>";
        }
        exit();
    }

    /**
     * Helper to write audit logs without leaking secrets
     */
    public static function logAudit(?int $userId, string $action, string $module, string $resType, ?int $resId, ?string $ip = null, ?array $metadata = null): void {
        try {
            $db = Database::connection();
            $ip = $ip ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'CLI';
            
            // Scrub metadata of any sensitive keys
            if ($metadata !== null) {
                foreach ($metadata as $key => $val) {
                    if (preg_match('/pass|token|secret|key|cvv/i', $key)) {
                        $metadata[$key] = '[REDACTED]';
                    }
                }
                $metadataStr = json_encode($metadata);
            } else {
                $metadataStr = null;
            }

            if ($userId !== null) {
                $checkStmt = $db->prepare("SELECT 1 FROM users WHERE id = ?");
                $checkStmt->execute([$userId]);
                if (!$checkStmt->fetchColumn()) {
                    $userId = null;
                }
            }

            $stmt = $db->prepare("
                INSERT INTO audit_logs (user_id, action, module, resource_type, resource_id, ip_address, user_agent, metadata) 
                VALUES (:user_id, :action, :module, :res_type, :res_id, :ip, :ua, :metadata)
            ");
            $stmt->execute([
                'user_id' => $userId,
                'action' => $action,
                'module' => $module,
                'res_type' => $resType,
                'res_id' => $resId,
                'ip' => $ip,
                'ua' => $ua,
                'metadata' => $metadataStr
            ]);
        } catch (\Exception $e) {
            // Ignore audit log failures in production to prevent complete blocks
            Logger::error("Audit Log Failure: " . $e->getMessage());
        }
    }
}
