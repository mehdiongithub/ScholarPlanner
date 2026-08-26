<?php

namespace App\Controllers;

use App\Services\Database;
use App\Services\Auth;
use App\Services\Logger;
use App\Helpers\Security;
use Exception;

class AuthController {
    /**
     * Display registration form
     */
    public function showRegister(): void {
        if (Auth::isAuthenticated()) {
            $this->redirectBasedOnRole();
        }

        $db = Database::connection();
        $countries = $db->query("SELECT id, name FROM countries WHERE status = 'active' ORDER BY name ASC")->fetchAll();

        view('auth.register', [
            'countries' => $countries,
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
        $phone = trim($_POST['phone'] ?? '');
        $whatsappPhone = trim($_POST['whatsapp_phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $countryId = $_POST['country_id'] ?? null;
        $whatsappOptIn = isset($_POST['whatsapp_opt_in']) ? 1 : 0;

        // Validation checks
        if (empty($firstName)) $errors['first_name'] = "First name is required.";
        if (empty($lastName)) $errors['last_name'] = "Last name is required.";
        
        if (empty($email)) {
            $errors['email'] = "Email address is required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "Invalid email format.";
        }

        if (empty($phone)) {
            $errors['phone'] = "Mobile/phone number is required.";
        } elseif (!preg_match('/^\+?[0-9]{10,15}$/', $phone)) {
            $errors['phone'] = "Phone format must be international (10-15 digits, optional +).";
        }

        if (!empty($whatsappPhone) && !preg_match('/^\+?[0-9]{10,15}$/', $whatsappPhone)) {
            $errors['whatsapp_phone'] = "WhatsApp phone format must be international.";
        }

        // Password strength validation
        if (empty($password)) {
            $errors['password'] = "Password is required.";
        } elseif (strlen($password) < 8) {
            $errors['password'] = "Password must be at least 8 characters long.";
        } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $errors['password'] = "Password must contain uppercase, lowercase, and numbers.";
        }

        if ($password !== $confirmPassword) {
            $errors['confirm_password'] = "Password confirmations do not match.";
        }

        $db = Database::connection();
        if (empty($countryId)) {
            $errors['country_id'] = "Country selection is required.";
        } else {
            // Verify country exists
            $stmt = $db->prepare("SELECT COUNT(*) FROM countries WHERE id = :id AND status = 'active'");
            $stmt->execute(['id' => $countryId]);
            if ((int)$stmt->fetchColumn() === 0) {
                $errors['country_id'] = "Selected country is invalid.";
            }
        }

        // Check duplicate email
        if (!empty($email)) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            if ((int)$stmt->fetchColumn() > 0) {
                $errors['email'] = "This email is already registered.";
            }
        }

        // Check duplicate phone
        if (!empty($phone)) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE phone = :phone");
            $stmt->execute(['phone' => $phone]);
            if ((int)$stmt->fetchColumn() > 0) {
                $errors['phone'] = "This phone number is already registered.";
            }
        }

        if (!empty($errors)) {
            $countries = $db->query("SELECT id, name FROM countries WHERE status = 'active' ORDER BY name ASC")->fetchAll();
            view('auth.register', [
                'countries' => $countries,
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
                INSERT INTO users (role_id, first_name, last_name, email, phone, whatsapp_phone, password_hash, status, whatsapp_opt_in) 
                VALUES (:role_id, :first_name, :last_name, :email, :phone, :whatsapp_phone, :password_hash, 'active', :whatsapp_opt_in)
            ");
            $stmt->execute([
                'role_id' => $visitorRoleId,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone' => $phone,
                'whatsapp_phone' => !empty($whatsappPhone) ? $whatsappPhone : null,
                'password_hash' => $passwordHash,
                'whatsapp_opt_in' => $whatsappOptIn
            ]);
            
            $userId = $db->lastInsertId();

            // Create student profile
            $stmtProfile = $db->prepare("
                INSERT INTO student_profiles (user_id, nationality_country_id, residence_country_id, profile_completion_percentage) 
                VALUES (:user_id, :nat_country_id, :res_country_id, 10)
            ");
            $stmtProfile->execute([
                'user_id' => $userId,
                'nat_country_id' => $countryId,
                'res_country_id' => $countryId
            ]);

            // Create default user preferences
            $stmtPref = $db->prepare("
                INSERT INTO user_preferences (user_id, preferred_country) 
                VALUES (:user_id, :country_id)
            ");
            $stmtPref->execute([
                'user_id' => $userId,
                'country_id' => $countryId
            ]);

            Auth::logAudit($userId, 'registration_success', 'auth', 'users', $userId);

            // Redirect to login
            $_SESSION['registration_success'] = "Registration completed successfully! Please log in.";
            $loginUrl = url('/login');
            header("Location: $loginUrl");
            exit();
        } catch (Exception $e) {
            $errors['system'] = "An error occurred during registration. Please try again.";
            Logger::error("Registration Exception: " . $e->getMessage());
            $countries = $db->query("SELECT id, name FROM countries WHERE status = 'active' ORDER BY name ASC")->fetchAll();
            view('auth.register', [
                'countries' => $countries,
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
        if (Auth::hasRole('admin')) {
            $redirectUrl = url('/admin');
        } elseif (Auth::hasRole('employee')) {
            $redirectUrl = url('/employee');
        } else {
            $redirectUrl = url('/dashboard');
        }

        header("Location: $redirectUrl");
        exit();
    }
}
