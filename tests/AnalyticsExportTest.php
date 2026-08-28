<?php

use App\Services\Database;
use App\Services\Auth;
use App\Controllers\IntelligenceController;

class AnalyticsExportTest {
    private PDO $db;
    private int $uVisitor;
    private int $uAdmin;
    private int $uEmployee;

    public function __construct() {
        $this->db = Database::connection();
    }

    public function run(): void {
        echo "--- Running AnalyticsExportTest ---\n";
        
        $this->setUp();

        try {
            $this->testAnalyticsAuthorization();
            $this->testDateRangeValidation();
            $this->testCSVInjectionProtection();
            $this->testSignedDownloadTokens();
            $this->testAggregateCalculations();
        } finally {
            $this->tearDown();
        }

        echo "AnalyticsExportTest PASSED.\n\n";
    }

    private function setUp(): void {
        $this->db->exec("DELETE FROM users WHERE email IN ('student_test@example.com', 'admin_test@example.com', 'employee_test@example.com')");

        $visitorRoleId = $this->db->query("SELECT id FROM roles WHERE name = 'visitor'")->fetchColumn();
        $adminRoleId = $this->db->query("SELECT id FROM roles WHERE name = 'admin'")->fetchColumn();
        $employeeRoleId = $this->db->query("SELECT id FROM roles WHERE name = 'employee'")->fetchColumn();

        $stmt = $this->db->prepare("INSERT INTO users (email, password_hash, first_name, last_name, role_id, status) VALUES (?, ?, ?, ?, ?, 'active')");
        
        $stmt->execute(['student_test@example.com', password_hash('Pass123!', PASSWORD_BCRYPT), 'Student', 'Test', $visitorRoleId]);
        $this->uVisitor = $this->db->lastInsertId();

        $stmt->execute(['admin_test@example.com', password_hash('Pass123!', PASSWORD_BCRYPT), 'Admin', 'Test', $adminRoleId]);
        $this->uAdmin = $this->db->lastInsertId();

        $stmt->execute(['employee_test@example.com', password_hash('Pass123!', PASSWORD_BCRYPT), 'Employee', 'Test', $employeeRoleId]);
        $this->uEmployee = $this->db->lastInsertId();
    }

    private function tearDown(): void {
        $this->db->exec("DELETE FROM users WHERE email IN ('student_test@example.com', 'admin_test@example.com', 'employee_test@example.com')");
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
        }
    }

    private function loginUser(int $userId, string $role): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['user_id'] = $userId;
        $_SESSION['role_name'] = $role;
        $_SESSION['user_email'] = $role . '_test@example.com';
        
        $ref = new \ReflectionClass('App\Services\Auth');
        $prop = $ref->getProperty('currentUser');
        $prop->setAccessible(true);
        $prop->setValue(null, null);
    }

    private function testAnalyticsAuthorization(): void {
        // Guests/unauthenticated users must be denied
        $_SESSION = [];
        try {
            $controller = new IntelligenceController();
            $controller->index();
            throw new Exception("Index allowed for unauthenticated visitors.");
        } catch (RuntimeException $e) {
            // Success: redirected or aborted
        }

        // Students (visitor role) must be denied
        $this->loginUser($this->uVisitor, 'visitor');
        try {
            $controller = new IntelligenceController();
            $controller->index();
            throw new Exception("Index allowed for student role.");
        } catch (RuntimeException $e) {
            // Success
        }

        // Admins can access
        $this->loginUser($this->uAdmin, 'admin');
        $_GET['tab'] = 'alerts';
        ob_start();
        try {
            $controller = new IntelligenceController();
            $controller->index();
        } finally {
            ob_end_clean();
        }
    }

    private function testDateRangeValidation(): void {
        $this->loginUser($this->uAdmin, 'admin');
        $controller = new IntelligenceController();

        // 1. Validate yesterday range parsing
        $_GET['range'] = 'yesterday';
        $ref = new ReflectionMethod('App\Controllers\IntelligenceController', 'parseDateRange');
        $ref->setAccessible(true);
        $range = $ref->invoke($controller);
        if ($range['range'] !== 'yesterday') {
            throw new Exception("Expected range 'yesterday', got " . $range['range']);
        }
        $expectedYest = date('Y-m-d', strtotime('-1 day'));
        if ($range['start_date'] !== $expectedYest || $range['end_date'] !== $expectedYest) {
            throw new Exception("Yesterday date mismatch: " . $range['start_date']);
        }

        // 2. Validate custom date range swaps if out of order
        $_GET['range'] = 'custom';
        $_GET['start_date'] = '2026-08-28';
        $_GET['end_date'] = '2026-08-01';
        $range = $ref->invoke($controller);
        if ($range['start_date'] !== '2026-08-01' || $range['end_date'] !== '2026-08-28') {
            throw new Exception("Date range swap failed: " . $range['start_date'] . ' to ' . $range['end_date']);
        }

        // 3. Validate custom date range cap to 366 days
        $_GET['start_date'] = '2025-01-01';
        $_GET['end_date'] = '2026-08-01'; // 577 days gap
        $range = $ref->invoke($controller);
        $diff = (strtotime($range['end_date']) - strtotime($range['start_date'])) / 86400;
        if ($diff > 366) {
            throw new Exception("Date range capping failed: difference of " . $diff . " days");
        }

        // 4. Validate invalid format fallbacks to last_30_days
        $_GET['start_date'] = 'invalid-date';
        $range = $ref->invoke($controller);
        if ($range['range'] !== 'last_30_days') {
            throw new Exception("Expected fallback to last_30_days on invalid format, got " . $range['range']);
        }
    }

    private function testCSVInjectionProtection(): void {
        $controller = new IntelligenceController();
        $ref = new ReflectionMethod('App\Controllers\IntelligenceController', 'sanitizeCSVCell');
        $ref->setAccessible(true);

        $safe = $ref->invoke($controller, 'normal string');
        if ($safe !== 'normal string') {
            throw new Exception("Expected unchanged cell value");
        }

        $formula = $ref->invoke($controller, '=HYPERLINK("http://evil.com")');
        if ($formula !== "'=HYPERLINK(\"http://evil.com\")") {
            throw new Exception("CSV formula injection escaping failed, got: " . $formula);
        }

        $plus = $ref->invoke($controller, '+cmd');
        if ($plus !== "'+cmd") {
            throw new Exception("Plus prefix escaping failed");
        }

        $formulaWithSpace = $ref->invoke($controller, '   =HYPERLINK("http://evil.com")');
        if ($formulaWithSpace !== "'   =HYPERLINK(\"http://evil.com\")") {
            throw new Exception("CSV formula injection escaping with leading whitespace failed, got: " . $formulaWithSpace);
        }
    }

    private function testSignedDownloadTokens(): void {
        $this->loginUser($this->uAdmin, 'admin');

        $expires = time() + 3600;
        $secret = $_ENV['APP_KEY'] ?? 'fallback_signing_secret_key_999';
        
        // 1. Test valid signature
        $token = hash_hmac('sha256', "type=student&expires={$expires}", $secret);
        $_GET = [
            'type' => 'student',
            'expires' => $expires,
            'token' => $token
        ];

        $controller = new IntelligenceController();
        ob_start();
        try {
            $controller->downloadExport();
        } finally {
            ob_end_clean();
        }

        // 2. Test expired signature link
        $_GET['expires'] = time() - 100;
        $_GET['token'] = hash_hmac('sha256', "type=student&expires=" . $_GET['expires'], $secret);
        try {
            $controller->downloadExport();
            throw new Exception("Allowed expired link.");
        } catch (RuntimeException $e) {
            if ($e->getCode() !== 403 || $e->getMessage() !== "Expired download link.") {
                throw $e;
            }
        }

        // 3. Test tampered signature link
        $_GET['expires'] = $expires;
        $_GET['token'] = 'invalid_token_999';
        try {
            $controller->downloadExport();
            throw new Exception("Allowed invalid token.");
        } catch (RuntimeException $e) {
            if ($e->getCode() !== 403 || $e->getMessage() !== "Invalid download signature.") {
                throw $e;
            }
        }

        // 4. Test IDOR role authorization (visitor denied)
        $this->loginUser($this->uVisitor, 'visitor');
        $token = hash_hmac('sha256', "type=student&expires={$expires}", $secret);
        $_GET = [
            'type' => 'student',
            'expires' => $expires,
            'token' => $token
        ];
        try {
            $controller->downloadExport();
            throw new Exception("Allowed visitor to download export.");
        } catch (RuntimeException $e) {
            // Success: visitor is denied role-wise
        }
    }

    private function testAggregateCalculations(): void {
        $this->loginUser($this->uAdmin, 'admin');
        $controller = new IntelligenceController();

        $dateRange = [
            'start' => date('Y-m-d') . ' 00:00:00',
            'end' => date('Y-m-d') . ' 23:59:59',
            'range' => 'today'
        ];

        $refApp = new ReflectionMethod('App\Controllers\IntelligenceController', 'calculateApplicationAnalytics');
        $refApp->setAccessible(true);
        $appStats = $refApp->invoke($controller, $dateRange);

        if (!isset($appStats['funnel']['registered'])) {
            throw new Exception("Missing funnel registered metric");
        }
        if (!isset($appStats['reg_timeline'])) {
            throw new Exception("Missing registration timeline");
        }

        $refMatch = new ReflectionMethod('App\Controllers\IntelligenceController', 'calculateMatchingAnalytics');
        $refMatch->setAccessible(true);
        $matchStats = $refMatch->invoke($controller, $dateRange);

        if (!isset($matchStats['doc_readiness']['complete_profiles'])) {
            throw new Exception("Missing document readiness metrics");
        }
    }
}
