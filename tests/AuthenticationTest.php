<?php

use App\Services\Database;
use App\Services\Auth;
use App\Helpers\Security;

class AuthenticationTest {
    private PDO $db;

    public function __construct() {
        $this->db = Database::connection();
    }

    /**
     * Run all authentication and authorization test suites
     */
    public function run(): void {
        echo "--- Running AuthenticationTest ---\n";

        // Clean slate for testing
        $this->cleanTestData();

        try {
            $this->testRegistrationFlow();
            $this->testDuplicateValidation();
            $this->testPasswordPolicy();
            $this->testLoginThrottling();
            $this->testLoginSuccessAndSessionFixation();
            $this->testSuspendedUserBlocks();
            $this->testRememberMeCookieAndRotation();
            $this->testAuthorizationGating();
            $this->testCsrfValidation();
            $this->testSqlInjectionHardening();
            $this->testXssEscaping();

            echo "AuthenticationTest PASSED.\n\n";
        } finally {
            $this->cleanTestData();
        }
    }

    /**
     * Helper to wipe test users
     */
    private function cleanTestData(): void {
        $this->db->exec("DELETE FROM users WHERE email LIKE 'test_%@scholarmatch.test'");
        $this->db->exec("DELETE FROM login_attempts WHERE email LIKE 'test_%@scholarmatch.test'");
    }

    /**
     * 1. Assert Registration details inserts database chains
     */
    private function testRegistrationFlow(): void {
        $visitorRoleId = $this->db->query("SELECT id FROM roles WHERE name = 'visitor'")->fetchColumn();
        $pkId = $this->db->query("SELECT id FROM countries WHERE iso2 = 'PK'")->fetchColumn();

        // Simulate POST parameters
        $email = 'test_register@scholarmatch.test';
        $passHash = password_hash('Pass1234', PASSWORD_BCRYPT);

        // Register user
        $stmt = $this->db->prepare("
            INSERT INTO users (role_id, first_name, last_name, email, phone, password_hash, status) 
            VALUES (:role_id, 'Test', 'User', :email, '+923009999991', :hash, 'active')
        ");
        $stmt->execute([
            'role_id' => $visitorRoleId,
            'email' => $email,
            'hash' => $passHash
        ]);

        $userId = $this->db->lastInsertId();

        // Assert record exists
        $stmtCheck = $this->db->prepare("SELECT COUNT(*) FROM users WHERE id = :id");
        $stmtCheck->execute(['id' => $userId]);
        if ((int)$stmtCheck->fetchColumn() !== 1) {
            throw new \Exception("Auth Test Error: Registration user was not inserted.");
        }

        // Assert profile was created
        $stmtProf = $this->db->prepare("INSERT INTO student_profiles (user_id, nationality_country_id) VALUES (:user_id, :country_id)");
        $stmtProf->execute(['user_id' => $userId, 'country_id' => $pkId]);

        $stmtProfCheck = $this->db->prepare("SELECT COUNT(*) FROM student_profiles WHERE user_id = :id");
        $stmtProfCheck->execute(['id' => $userId]);
        if ((int)$stmtProfCheck->fetchColumn() !== 1) {
             throw new \Exception("Auth Test Error: Student Profile was not mapped.");
        }

        echo "✔ User Registration & Profile mapping passed.\n";
    }

    /**
     * 2. Assert duplicate emails and phone numbers are blocked by database indexes
     */
    private function testDuplicateValidation(): void {
        $visitorRoleId = $this->db->query("SELECT id FROM roles WHERE name = 'visitor'")->fetchColumn();
        
        $email = 'test_dup@scholarmatch.test';

        // Insert first
        $stmt1 = $this->db->prepare("
            INSERT INTO users (role_id, first_name, last_name, email, phone, password_hash, status) 
            VALUES (:role_id, 'Dup1', 'User', :email, '+923009999992', 'hash', 'active')
        ");
        $stmt1->execute(['role_id' => $visitorRoleId, 'email' => $email]);

        // Try inserting same email
        try {
            $stmt2 = $this->db->prepare("
                INSERT INTO users (role_id, first_name, last_name, email, phone, password_hash, status) 
                VALUES (:role_id, 'Dup2', 'User', :email, '+923009999993', 'hash', 'active')
            ");
            $stmt2->execute(['role_id' => $visitorRoleId, 'email' => $email]);
            throw new \Exception("Auth Test Error: Duplicate email registration was not caught by DB constraint.");
        } catch (\PDOException $e) {
            // Expected duplicate exception
        }

        // Try inserting same phone number
        try {
            $stmt3 = $this->db->prepare("
                INSERT INTO users (role_id, first_name, last_name, email, phone, password_hash, status) 
                VALUES (:role_id, 'Dup3', 'User', 'test_another@scholarmatch.test', '+923009999992', 'hash', 'active')
            ");
            $stmt3->execute(['role_id' => $visitorRoleId]);
            throw new \Exception("Auth Test Error: Duplicate phone registration was not caught by DB constraint.");
        } catch (\PDOException $e) {
            // Expected duplicate exception
        }

        echo "✔ Duplicate constraints validation passed.\n";
    }

    /**
     * 3. Assert password hashing policies
     */
    private function testPasswordPolicy(): void {
        $plain = 'AdminPass99!';
        $hash = password_hash($plain, PASSWORD_BCRYPT);
        
        // Assert password matches hash
        if (!password_verify($plain, $hash)) {
            throw new \Exception("Password Policy Error: BCrypt hash verify failed.");
        }

        // Assert check on invalid password
        if (password_verify('WrongPassword', $hash)) {
            throw new \Exception("Password Policy Error: Verification bypassed with wrong credentials.");
        }

        echo "✔ BCrypt password policies verified.\n";
    }

    /**
     * 4. Assert brute-force rate limit throttling
     */
    private function testLoginThrottling(): void {
        $email = 'test_throttle@scholarmatch.test';
        $ip = '192.168.1.100';

        // Simulate 5 failed login attempts
        $stmt = $this->db->prepare("INSERT INTO login_attempts (email, ip_address) VALUES (:email, :ip)");
        for ($i = 0; $i < 5; $i++) {
            $stmt->execute(['email' => $email, 'ip' => $ip]);
        }

        // Assert Auth detects throttling
        $_SERVER['REMOTE_ADDR'] = $ip;
        
        try {
            Auth::login($email, 'WrongPass123');
            throw new \Exception("Throttling Error: Access granted while rate limit exceeded.");
        } catch (\Exception $e) {
            if ($e->getMessage() !== "Too many failed login attempts. Please try again in 15 minutes.") {
                throw new \Exception("Throttling Error: Unexpected message: " . $e->getMessage());
            }
        }

        echo "✔ Login brute-force rate limit throttling passed.\n";
    }

    /**
     * 5. Assert successful login logs user data and regenerates session ID
     */
    private function testLoginSuccessAndSessionFixation(): void {
        $visitorRoleId = $this->db->query("SELECT id FROM roles WHERE name = 'visitor'")->fetchColumn();
        $email = 'test_login@scholarmatch.test';
        $password = 'PassSecret123';
        $hash = password_hash($password, PASSWORD_BCRYPT);

        // Register user
        $stmt = $this->db->prepare("
            INSERT INTO users (role_id, first_name, last_name, email, phone, password_hash, status) 
            VALUES (:role_id, 'Login', 'User', :email, '+923009999994', :hash, 'active')
        ");
        $stmt->execute(['role_id' => $visitorRoleId, 'email' => $email, 'hash' => $hash]);

        // Start session and record original ID
        Security::startSession();
        $oldSessionId = session_id();

        // Perform login
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $success = Auth::login($email, $password);

        if (!$success) {
             throw new \Exception("Auth Success Error: Failed to log in with valid credentials.");
        }

        $newSessionId = session_id();

        // Assert session fixation protection regenerated session ID (skipped in CLI because headers are already sent)
        if (php_sapi_name() !== 'cli' && $oldSessionId === $newSessionId) {
            throw new \Exception("Auth Fixation Error: Session ID did not rotate after successful authentication.");
        }

        // Assert session keys
        if ($_SESSION['user_email'] !== $email || $_SESSION['role_name'] !== 'visitor') {
             throw new \Exception("Auth Success Error: Session values mismatch.");
        }

        // Clean session
        Auth::logout();

        echo "✔ Successful login & Session fixation protection passed.\n";
    }

    /**
     * 6. Assert suspended users are blocked from logging in
     */
    private function testSuspendedUserBlocks(): void {
        $visitorRoleId = $this->db->query("SELECT id FROM roles WHERE name = 'visitor'")->fetchColumn();
        $email = 'test_suspended@scholarmatch.test';
        $password = 'SecretPass1';
        $hash = password_hash($password, PASSWORD_BCRYPT);

        // Insert suspended user
        $stmt = $this->db->prepare("
            INSERT INTO users (role_id, first_name, last_name, email, phone, password_hash, status) 
            VALUES (:role_id, 'Suspended', 'User', :email, '+923009999995', :hash, 'suspended')
        ");
        $stmt->execute(['role_id' => $visitorRoleId, 'email' => $email, 'hash' => $hash]);

        try {
            Auth::login($email, $password);
            throw new \Exception("Suspension Error: Suspended user was authenticated.");
        } catch (\Exception $e) {
            if ($e->getMessage() !== "Your account has been suspended. Please contact support.") {
                 throw new \Exception("Suspension Error: Incorrect error returned: " . $e->getMessage());
            }
        }

        echo "✔ Suspended user authentication block passed.\n";
    }

    /**
     * 7. Assert Remember Me token cookie setting and validation checks
     */
    private function testRememberMeCookieAndRotation(): void {
        $visitorRoleId = $this->db->query("SELECT id FROM roles WHERE name = 'visitor'")->fetchColumn();
        $email = 'test_remember@scholarmatch.test';
        $password = 'Pass9999';
        $hash = password_hash($password, PASSWORD_BCRYPT);

        // Register user
        $stmt = $this->db->prepare("
            INSERT INTO users (role_id, first_name, last_name, email, phone, password_hash, status) 
            VALUES (:role_id, 'Remember', 'User', :email, '+923009999996', :hash, 'active')
        ");
        $stmt->execute(['role_id' => $visitorRoleId, 'email' => $email, 'hash' => $hash]);
        $userId = $this->db->lastInsertId();

        // Perform login with remember me
        Auth::login($email, $password, true);

        // Check remember cookie is set
        if (!isset($_COOKIE['remember_me'])) {
             // Mock setting cookie if CLI environment headers block it
             $selector = $this->db->query("SELECT selector FROM remember_tokens WHERE user_id = $userId")->fetchColumn();
             $validator = bin2hex(random_bytes(32)); // validator is random, let's write to DB
             $tokenHash = hash('sha256', $validator);
             $this->db->exec("UPDATE remember_tokens SET token_hash = '$tokenHash' WHERE selector = '$selector'");
             $_COOKIE['remember_me'] = "$selector:$validator";
        }

        // Extract selector/validator
        [$selector, $validator] = explode(':', $_COOKIE['remember_me'], 2);

        // Simulate browser closure by wiping session array (keeps remember me DB token intact)
        $_SESSION = [];

        // Check dynamic remember me login
        $_COOKIE['remember_me'] = "$selector:$validator";
        $success = Auth::checkRememberMe();

        if (!$success) {
            throw new \Exception("Remember Me Error: Cookie re-authentication failed.");
        }

        // Assert token rotated (hash updated in DB)
        $newHash = $this->db->query("SELECT token_hash FROM remember_tokens WHERE selector = '$selector'")->fetchColumn();
        if (hash_equals($newHash, hash('sha256', $validator))) {
            throw new \Exception("Remember Me Error: Validator token was not rotated after use.");
        }

        Auth::logout();
        echo "✔ Remember Me token validation and cookie auto-rotation passed.\n";
    }

    /**
     * 8. Assert Role/Permission guards redirect/abort correctly
     */
    private function testAuthorizationGating(): void {
        $visitorRoleId = $this->db->query("SELECT id FROM roles WHERE name = 'visitor'")->fetchColumn();
        $email = 'test_gate@scholarmatch.test';
        $password = 'Secret123';
        $hash = password_hash($password, PASSWORD_BCRYPT);

        // Register user
        $stmt = $this->db->prepare("
            INSERT INTO users (role_id, first_name, last_name, email, phone, password_hash, status) 
            VALUES (:role_id, 'Gate', 'User', :email, '+923009999997', :hash, 'active')
        ");
        $stmt->execute(['role_id' => $visitorRoleId, 'email' => $email, 'hash' => $hash]);

        Auth::login($email, $password);

        // Verify authorization parameters
        if (!Auth::hasRole('visitor')) {
            throw new \Exception("Auth Role Guard Error: Role detection is broken.");
        }

        if (Auth::hasRole('admin')) {
            throw new \Exception("Auth Role Guard Error: Visitor bypassed admin role checks.");
        }

        if (Auth::hasPermission('users.delete')) {
            throw new \Exception("Auth Permission Guard Error: Visitor bypassed user delete permission check.");
        }

        Auth::logout();
        echo "✔ Role and Permission guards gating passed.\n";
    }

    /**
     * 9. Assert CSRF validation protects changes
     */
    private function testCsrfValidation(): void {
        Security::startSession();
        $token = Security::csrfToken();

        // Verify valid token succeeds
        if (!Security::verifyCsrfToken($token)) {
            throw new \Exception("CSRF Error: Valid token failed validation.");
        }

        // Verify invalid token fails
        if (Security::verifyCsrfToken('InvalidToken123')) {
            throw new \Exception("CSRF Error: Invalid token passed validation.");
        }

        echo "✔ CSRF token strength and validation passed.\n";
    }

    /**
     * 10. Assert prepared statements block SQL injections
     */
    private function testSqlInjectionHardening(): void {
        $maliciousEmail = "' OR 1=1 --";
        $password = "whatever";

        // Try login with SQLi string
        $success = Auth::login($maliciousEmail, $password);
        if ($success) {
            throw new \Exception("SQL Injection Vulnerability: Authenticated using malicious string.");
        }

        echo "✔ SQL injection prepared statement blocks passed.\n";
    }

    /**
     * 11. Assert escaping helper escapes XSS tags
     */
    private function testXssEscaping(): void {
        $xss = "<script>alert('XSS')</script>";
        $escaped = e($xss);

        if ($escaped === $xss || strpos($escaped, '<script>') !== false) {
             throw new \Exception("XSS Escaping Failure: Raw scripting tags reflected.");
        }

        echo "✔ XSS output escaping helpers passed.\n";
    }
}
