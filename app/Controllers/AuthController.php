<?php

namespace App\Controllers;

use App\Services\Database;
use App\Services\Auth;
use App\Services\Logger;
use App\Helpers\Security;
use Exception;

class AuthController {
    public static ?string $lastGeneratedCode = null;

    private function halt(string $message = 'Halt execution'): void {
        if (defined('TESTING_MODE') && TESTING_MODE) {
            throw new \RuntimeException($message);
        }
        exit();
    }

    /**
     * Display registration form
     */
    public function showRegister(): void {
        if (Auth::isAuthenticated()) {
            $this->redirectBasedOnRole();
        }

        view('auth.register', [
            'csrf_token' => Security::csrfToken(),
            'errors' => [],
            'old' => []
        ]);
    }

    /**
     * Process registration POST request
     */
    public function register(): void {
        if (Auth::isAuthenticated()) {
            $this->redirectBasedOnRole();
        }

        $errors = [];
        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            $errors['csrf'] = "CSRF verification failed. Please try again.";
        }

        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $terms = isset($_POST['terms']) ? 1 : 0;

        // Validation checks
        if (empty($firstName)) $errors['first_name'] = "First name is required.";
        if (empty($lastName)) $errors['last_name'] = "Last name is required.";
        
        if (empty($email)) {
            $errors['email'] = "Email address is required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "Please enter a valid email address.";
        }

        // Password strength validation
        if (empty($password)) {
            $errors['password'] = "Password is required.";
        } elseif (strlen($password) < 8) {
            $errors['password'] = "Password must be at least 8 characters long.";
        } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $errors['password'] = "Password does not meet the required security rules.";
        }

        if ($password !== $confirmPassword) {
            $errors['confirm_password'] = "Password confirmations do not match.";
        }

        if (empty($terms)) {
            $errors['terms'] = "You must agree to the Terms & Conditions and Privacy Policy.";
        }

        $db = Database::connection();

        // Check duplicate email
        if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            if ((int)$stmt->fetchColumn() > 0) {
                $errors['email'] = "This email is already registered.";
            }
        }

        if (!empty($errors)) {
            view('auth.register', [
                'csrf_token' => Security::csrfToken(),
                'errors' => $errors,
                'old' => $_POST
            ]);
            return;
        }

        try {
            $visitorRoleId = $db->query("SELECT id FROM roles WHERE name = 'visitor'")->fetchColumn();
            if (!$visitorRoleId) {
                throw new Exception("Visitor role not found in system.");
            }

            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
            
            // Insert user
            $stmt = $db->prepare("
                INSERT INTO users (role_id, first_name, last_name, email, password_hash, status) 
                VALUES (:role_id, :first_name, :last_name, :email, :password_hash, 'pending')
            ");
            $stmt->execute([
                'role_id' => $visitorRoleId,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'password_hash' => $passwordHash
            ]);
            
            $userId = $db->lastInsertId();

            // Create student profile
            $stmtProfile = $db->prepare("
                INSERT INTO student_profiles (user_id, profile_completion_percentage) 
                VALUES (:user_id, 0)
            ");
            $stmtProfile->execute([
                'user_id' => $userId
            ]);

            // Create default user preferences
            $stmtPref = $db->prepare("
                INSERT INTO user_preferences (user_id) 
                VALUES (:user_id)
            ");
            $stmtPref->execute([
                'user_id' => $userId
            ]);

            Auth::logAudit($userId, 'registration_success', 'auth', 'users', $userId);

            // Log user in as pending
            Security::startSession();
            if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
                session_regenerate_id(true);
            }
            $_SESSION['user_id'] = $userId;
            $_SESSION['role_name'] = 'visitor';
            $_SESSION['user_name'] = $firstName . ' ' . $lastName;
            $_SESSION['user_email'] = $email;

            // Generate secure 6-digit verification code
            $otpCode = (string)random_int(100000, 999999);
            self::$lastGeneratedCode = $otpCode;
            $tokenHash = hash('sha256', $otpCode);

            // Insert verification token
            $stmtToken = $db->prepare("
                INSERT INTO email_verification_tokens (user_id, token_hash, expires_at) 
                VALUES (:user_id, :token_hash, DATE_ADD(NOW(), INTERVAL 10 MINUTE))
            ");
            $stmtToken->execute([
                'user_id' => $userId,
                'token_hash' => $tokenHash
            ]);

            // Send verification email
            $emailService = new \App\Services\EmailNotificationService();
            $subject = "Verify your ScholarMatch account";
            $bodyHTML = '
            <div style="font-family: \'Inter\', sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 12px; background: #ffffff;">
                <div style="text-align: center; margin-bottom: 20px;">
                    <h2 style="color: #2563eb; margin: 0;">ScholarMatch</h2>
                </div>
                <hr style="border: 0; border-top: 1px solid #e2e8f0; margin-bottom: 20px;">
                <h3 style="color: #0f172a; margin-top: 0;">Verify your email</h3>
                <p style="color: #475569; font-size: 16px; line-height: 1.6;">Use the verification code below to verify your ScholarMatch account.</p>
                <div style="text-align: center; margin: 30px 0;">
                    <span style="font-size: 32px; font-weight: 700; color: #2563eb; letter-spacing: 6px; padding: 12px 24px; background: #eff6ff; border-radius: 8px; border: 1px dashed #bfdbfe;">' . $otpCode . '</span>
                </div>
                <p style="color: #64748b; font-size: 14px; line-height: 1.6;">This code is valid for <strong>10 minutes</strong>. If you did not request this code, please ignore this email.</p>
                <hr style="border: 0; border-top: 1px solid #e2e8f0; margin-top: 30px; margin-bottom: 20px;">
                <p style="color: #94a3b8; font-size: 12px; text-align: center; margin: 0;">&copy; ' . date("Y") . ' ScholarMatch. All rights reserved.</p>
            </div>';
            
            $emailService->sendEmail($email, $subject, $bodyHTML);

            header("Location: " . url('/verify-email'));
            $this->halt("Redirect to verify-email");

        } catch (Exception $e) {
            if ($e instanceof \RuntimeException) {
                throw $e;
            }
            $errors['system'] = "We couldn't complete your registration. Please try again.";
            Logger::error("Registration Exception: " . $e->getMessage());
            view('auth.register', [
                'csrf_token' => Security::csrfToken(),
                'errors' => $errors,
                'old' => $_POST
            ]);
        }
    }

    /**
     * Display login form
     */
    public function showLogin(): void {
        if (Auth::isAuthenticated() || Auth::checkRememberMe()) {
            $this->redirectBasedOnRole();
        }

        $successMessage = $_SESSION['registration_success'] ?? $_SESSION['reset_success'] ?? null;
        unset($_SESSION['registration_success'], $_SESSION['reset_success']);

        view('auth.login', [
            'csrf_token' => Security::csrfToken(),
            'success_message' => $successMessage,
            'errors' => [],
            'old' => []
        ]);
    }

    /**
     * Process login POST request
     */
    public function login(): void {
        if (Auth::isAuthenticated()) {
            $this->redirectBasedOnRole();
        }

        $errors = [];
        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            $errors['csrf'] = "CSRF verification failed. Please try again.";
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $rememberMe = isset($_POST['remember_me']);

        if (empty($email)) $errors['email'] = "Email is required.";
        if (empty($password)) $errors['password'] = "Password is required.";

        if (!empty($errors)) {
            view('auth.login', [
                'csrf_token' => Security::csrfToken(),
                'success_message' => null,
                'errors' => $errors,
                'old' => $_POST
            ]);
            return;
        }

        try {
            if (Auth::login($email, $password, $rememberMe)) {
                // Safeguard redirect path against Open Redirect attacks
                $redirectTo = $_POST['redirect_to'] ?? '';
                if (!empty($redirectTo) && strpos($redirectTo, '/') === 0 && strpos($redirectTo, '//') !== 0) {
                    header("Location: " . url($redirectTo));
                    exit();
                }
                
                $this->redirectBasedOnRole();
            } else {
                $errors['auth'] = "Invalid email or password.";
                view('auth.login', [
                    'csrf_token' => Security::csrfToken(),
                    'success_message' => null,
                    'errors' => $errors,
                    'old' => $_POST
                ]);
            }
        } catch (Exception $e) {
            $errors['auth'] = $e->getMessage();
            view('auth.login', [
                'csrf_token' => Security::csrfToken(),
                'success_message' => null,
                'errors' => $errors,
                'old' => $_POST
            ]);
        }
    }

    public function logout(): void {
        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            $homeUrl = url('/');
            header("Location: $homeUrl");
            exit();
        }

        Auth::logout();
        $homeUrl = url('/');
        header("Location: $homeUrl");
        exit();
    }

    /**
     * Render forgot password prompt
     */
    public function showForgot(): void {
        view('auth.forgot', [
            'csrf_token' => Security::csrfToken(),
            'success_message' => null,
            'dev_reset_link' => null,
            'errors' => []
        ]);
    }

    /**
     * Process forgot password request (generates hashed token)
     */
    public function forgot(): void {
        $errors = [];
        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            $errors['csrf'] = "CSRF verification failed. Please try again.";
        }

        $email = strtolower(trim($_POST['email'] ?? ''));
        if (empty($email)) {
            $errors['email'] = "Email is required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "Invalid email format.";
        }

        if (!empty($errors)) {
            view('auth.forgot', [
                'csrf_token' => Security::csrfToken(),
                'success_message' => null,
                'dev_reset_link' => null,
                'errors' => $errors
            ]);
            return;
        }

        $db = Database::connection();
        $stmt = $db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $userId = $stmt->fetchColumn();

        $devResetLink = null;

        if ($userId) {
            // Generate token (expired in 1 hour)
            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
            $expiresAt = date('Y-m-d H:i:s', time() + 3600);

            $ins = $db->prepare("INSERT INTO password_reset_tokens (user_id, token_hash, expires_at) VALUES (:user_id, :token_hash, :expires_at)");
            $ins->execute([
                'user_id' => $userId,
                'token_hash' => $tokenHash,
                'expires_at' => $expiresAt
            ]);

            Auth::logAudit($userId, 'password_reset_requested', 'auth', 'users', $userId);

            // In local development, show the reset link directly on the screen
            if (config('app.env') === 'local') {
                $devResetLink = url('/reset-password?token=' . $token);
            }
        }

        // Output generic success message (Never disclose if email exists for privacy)
        view('auth.forgot', [
            'csrf_token' => Security::csrfToken(),
            'success_message' => "If the email is registered in our system, you will receive a reset link shortly.",
            'dev_reset_link' => $devResetLink,
            'errors' => []
        ]);
    }

    /**
     * Render reset password prompt
     */
    public function showReset(): void {
        $token = $_GET['token'] ?? '';
        if (empty($token)) {
            $homeUrl = url('/');
            header("Location: $homeUrl");
            exit();
        }

        view('auth.reset', [
            'csrf_token' => Security::csrfToken(),
            'token' => $token,
            'errors' => []
        ]);
    }

    /**
     * Process password update
     */
    public function reset(): void {
        $errors = [];
        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            $errors['csrf'] = "CSRF verification failed. Please try again.";
        }

        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($token)) {
            $errors['token'] = "Token is missing.";
        }

        if (empty($password)) {
            $errors['password'] = "New password is required.";
        } elseif (strlen($password) < 8) {
            $errors['password'] = "Password must be at least 8 characters long.";
        } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $errors['password'] = "Password must contain uppercase, lowercase, and numbers.";
        }

        if ($password !== $confirmPassword) {
            $errors['confirm_password'] = "Password confirmations do not match.";
        }

        if (!empty($errors)) {
            view('auth.reset', [
                'csrf_token' => Security::csrfToken(),
                'token' => $token,
                'errors' => $errors
            ]);
            return;
        }

        $db = Database::connection();
        $tokenHash = hash('sha256', $token);

        // Fetch token details
        $stmt = $db->prepare("SELECT * FROM password_reset_tokens WHERE token_hash = :hash AND used_at IS NULL LIMIT 1");
        $stmt->execute(['hash' => $tokenHash]);
        $resetToken = $stmt->fetch();

        if (!$resetToken || strtotime($resetToken['expires_at']) < time()) {
            $errors['token'] = "The reset token is invalid or has expired.";
            view('auth.reset', [
                'csrf_token' => Security::csrfToken(),
                'token' => $token,
                'errors' => $errors
            ]);
            return;
        }

        try {
            $userId = $resetToken['user_id'];
            $newHash = password_hash($password, PASSWORD_BCRYPT);

            // Update user password
            $stmtUser = $db->prepare("UPDATE users SET password_hash = :hash WHERE id = :id");
            $stmtUser->execute(['hash' => $newHash, 'id' => $userId]);

            // Mark token as used
            $stmtToken = $db->prepare("UPDATE password_reset_tokens SET used_at = NOW() WHERE id = :id");
            $stmtToken->execute(['id' => $resetToken['id']]);

            // Invalidate other remember-me sessions for this user
            $stmtRemember = $db->prepare("DELETE FROM remember_tokens WHERE user_id = :user_id");
            $stmtRemember->execute(['user_id' => $userId]);

            Auth::logAudit($userId, 'password_reset_completed', 'auth', 'users', $userId);

            $_SESSION['reset_success'] = "Password reset successfully! Please log in with your new credentials.";
            $loginUrl = url('/login');
            header("Location: $loginUrl");
            exit();
        } catch (Exception $e) {
            $errors['system'] = "An error occurred. Please try again.";
            Logger::error("Password Reset Exception: " . $e->getMessage());
            view('auth.reset', [
                'csrf_token' => Security::csrfToken(),
                'token' => $token,
                'errors' => $errors
            ]);
        }
    }

    /**
     * Dispatch user redirects based on roles
     */
    private function redirectBasedOnRole(): void {
        $user = Auth::currentUser();
        if ($user && $user['status'] === 'pending') {
            $redirectUrl = url('/verify-email');
        } elseif (Auth::hasRole('admin')) {
            $redirectUrl = url('/admin');
        } elseif (Auth::hasRole('employee')) {
            $redirectUrl = url('/employee');
        } else {
            $redirectUrl = url('/dashboard');
        }

        header("Location: $redirectUrl");
        $this->halt("Redirect based on role");
    }

    /**
     * Display email verification page
     */
    public function showVerifyEmail(): void {
        Auth::requireAuth();
        $user = Auth::currentUser();
        if ($user && $user['status'] === 'active') {
            header("Location: " . url('/dashboard'));
            $this->halt("Redirect to dashboard");
        }

        $email = $user['email'] ?? '';
        $obfuscatedEmail = '';
        if (!empty($email)) {
            $parts = explode('@', $email);
            $name = $parts[0];
            $domain = $parts[1] ?? '';
            $len = strlen($name);
            if ($len <= 2) {
                $obfuscatedEmail = substr($name, 0, 1) . '***@' . $domain;
            } else {
                $obfuscatedEmail = substr($name, 0, 1) . str_repeat('*', $len - 2) . substr($name, -1) . '@' . $domain;
            }
        }

        view('auth.verify-email', [
            'csrf_token' => Security::csrfToken(),
            'email' => $obfuscatedEmail,
            'errors' => [],
            'success_message' => $_SESSION['verify_success'] ?? null
        ]);
        unset($_SESSION['verify_success']);
    }

    /**
     * Process verification OTP code submission
     */
    public function verifyEmail(): void {
        Auth::requireAuth();
        $user = Auth::currentUser();
        if ($user && $user['status'] === 'active') {
            header("Location: " . url('/dashboard'));
            $this->halt("Redirect to dashboard");
        }

        $errors = [];
        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            $errors['csrf'] = "CSRF verification failed. Please try again.";
        }

        $code = '';
        if (isset($_POST['code']) && is_array($_POST['code'])) {
            $code = implode('', $_POST['code']);
        } elseif (isset($_POST['code'])) {
            $code = trim($_POST['code']);
        }
        $code = trim($code);

        if (empty($code)) {
            $errors['code'] = "Verification code is required.";
        } elseif (!preg_match('/^[0-9]{6}$/', $code)) {
            $errors['code'] = "The verification code must be exactly 6 digits.";
        }

        if (session_status() === PHP_SESSION_NONE) {
            Security::startSession();
        }
        $_SESSION['verify_attempts'] = ($_SESSION['verify_attempts'] ?? 0) + 1;
        if ($_SESSION['verify_attempts'] > 5) {
            $errors['code'] = "Too many incorrect attempts. Please request a new code or try again later.";
        }

        $db = Database::connection();
        $userId = Auth::userId();

        if (empty($errors)) {
            $stmt = $db->prepare("
                SELECT * FROM email_verification_tokens 
                WHERE user_id = :user_id 
                  AND used_at IS NULL 
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $stmt->execute(['user_id' => $userId]);
            $token = $stmt->fetch();

            if (!$token || strtotime($token['expires_at']) < time()) {
                $errors['code'] = "The verification code is invalid or has expired.";
            } elseif (!hash_equals($token['token_hash'], hash('sha256', $code))) {
                $errors['code'] = "The verification code is invalid or has expired.";
            }

            if (empty($errors)) {
                // Mark token as used
                $updToken = $db->prepare("UPDATE email_verification_tokens SET used_at = NOW() WHERE id = :id");
                $updToken->execute(['id' => $token['id']]);

                // Update user status and verified timestamp
                $updUser = $db->prepare("UPDATE users SET email_verified_at = NOW(), status = 'active' WHERE id = :id");
                $updUser->execute(['id' => $userId]);

                Auth::logAudit($userId, 'email_verified', 'auth', 'users', $userId);
                unset($_SESSION['verify_attempts']);

                if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
                    session_regenerate_id(true);
                }

                $_SESSION['verify_success_toast'] = "Email verified successfully! Let's complete your profile.";
                header("Location: " . url('/profile/edit'));
                $this->halt("Redirect to profile edit");
            }
        }


        $email = $user['email'] ?? '';
        $obfuscatedEmail = '';
        if (!empty($email)) {
            $parts = explode('@', $email);
            $name = $parts[0];
            $domain = $parts[1] ?? '';
            $len = strlen($name);
            if ($len <= 2) {
                $obfuscatedEmail = substr($name, 0, 1) . '***@' . $domain;
            } else {
                $obfuscatedEmail = substr($name, 0, 1) . str_repeat('*', $len - 2) . substr($name, -1) . '@' . $domain;
            }
        }

        view('auth.verify-email', [
            'csrf_token' => Security::csrfToken(),
            'email' => $obfuscatedEmail,
            'errors' => $errors,
            'success_message' => null
        ]);
    }

    /**
     * Resend verification OTP code
     */
    public function resendVerifyEmail(): void {
        Auth::requireAuth();
        $user = Auth::currentUser();
        if ($user && $user['status'] === 'active') {
            header("Location: " . url('/dashboard'));
            $this->halt("Redirect to dashboard");
        }

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'CSRF verification failed. Please try again.']);
                $this->halt();
            } else {
                $errors = ['csrf' => 'CSRF verification failed. Please try again.'];
                $email = $user['email'] ?? '';
                $obfuscatedEmail = '';
                if (!empty($email)) {
                    $parts = explode('@', $email);
                    $name = $parts[0];
                    $domain = $parts[1] ?? '';
                    $len = strlen($name);
                    if ($len <= 2) {
                        $obfuscatedEmail = substr($name, 0, 1) . '***@' . $domain;
                    } else {
                        $obfuscatedEmail = substr($name, 0, 1) . str_repeat('*', $len - 2) . substr($name, -1) . '@' . $domain;
                    }
                }
                view('auth.verify-email', [
                    'csrf_token' => Security::csrfToken(),
                    'email' => $obfuscatedEmail,
                    'errors' => $errors,
                    'success_message' => null
                ]);
                return;
            }
        }

        if (session_status() === PHP_SESSION_NONE) {
            Security::startSession();
        }
        $lastResend = $_SESSION['last_resend_time'] ?? 0;
        $currentTime = time();
        $elapsed = $currentTime - $lastResend;

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');

        if ($elapsed < 60) {
            $remaining = 60 - $elapsed;
            $msg = "Please wait {$remaining} seconds before requesting another code.";
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $msg]);
                $this->halt();
            } else {
                $email = $user['email'] ?? '';
                $obfuscatedEmail = '';
                if (!empty($email)) {
                    $parts = explode('@', $email);
                    $name = $parts[0];
                    $domain = $parts[1] ?? '';
                    $len = strlen($name);
                    if ($len <= 2) {
                        $obfuscatedEmail = substr($name, 0, 1) . '***@' . $domain;
                    } else {
                        $obfuscatedEmail = substr($name, 0, 1) . str_repeat('*', $len - 2) . substr($name, -1) . '@' . $domain;
                    }
                }
                view('auth.verify-email', [
                    'csrf_token' => Security::csrfToken(),
                    'email' => $obfuscatedEmail,
                    'errors' => ['resend' => $msg],
                    'success_message' => null
                ]);
                return;
            }
        }

        $otpCode = (string)random_int(100000, 999999);
        self::$lastGeneratedCode = $otpCode;
        $tokenHash = hash('sha256', $otpCode);

        $db = Database::connection();
        $userId = Auth::userId();

        // Invalidate old tokens
        $stmtInvalidate = $db->prepare("UPDATE email_verification_tokens SET used_at = NOW() WHERE user_id = :user_id AND used_at IS NULL");
        $stmtInvalidate->execute(['user_id' => $userId]);

        // Insert new token
        $stmtToken = $db->prepare("
            INSERT INTO email_verification_tokens (user_id, token_hash, expires_at) 
            VALUES (:user_id, :token_hash, DATE_ADD(NOW(), INTERVAL 10 MINUTE))
        ");
        $stmtToken->execute([
            'user_id' => $userId,
            'token_hash' => $tokenHash
        ]);

        $_SESSION['last_resend_time'] = time();

        // Send email
        $email = $user['email'];
        $emailService = new \App\Services\EmailNotificationService();
        $subject = "Verify your ScholarMatch account";
        $bodyHTML = '
        <div style="font-family: \'Inter\', sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 12px; background: #ffffff;">
            <div style="text-align: center; margin-bottom: 20px;">
                <h2 style="color: #2563eb; margin: 0;">ScholarMatch</h2>
            </div>
            <hr style="border: 0; border-top: 1px solid #e2e8f0; margin-bottom: 20px;">
            <h3 style="color: #0f172a; margin-top: 0;">Verify your email</h3>
            <p style="color: #475569; font-size: 16px; line-height: 1.6;">Use the verification code below to verify your ScholarMatch account.</p>
            <div style="text-align: center; margin: 30px 0;">
                <span style="font-size: 32px; font-weight: 700; color: #2563eb; letter-spacing: 6px; padding: 12px 24px; background: #eff6ff; border-radius: 8px; border: 1px dashed #bfdbfe;">' . $otpCode . '</span>
            </div>
            <p style="color: #64748b; font-size: 14px; line-height: 1.6;">This code is valid for <strong>10 minutes</strong>. If you did not request this code, please ignore this email.</p>
            <hr style="border: 0; border-top: 1px solid #e2e8f0; margin-top: 30px; margin-bottom: 20px;">
            <p style="color: #94a3b8; font-size: 12px; text-align: center; margin: 0;">&copy; ' . date("Y") . ' ScholarMatch. All rights reserved.</p>
        </div>';
        
        $emailService->sendEmail($email, $subject, $bodyHTML);

        $successMsg = "Verification code sent. Please check your email.";

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => $successMsg]);
            $this->halt();
        } else {
            $_SESSION['verify_success'] = $successMsg;
            header("Location: " . url('/verify-email'));
            $this->halt("Redirect to verify-email");
        }
    }
}

