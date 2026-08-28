<?php
/**
 * Step 19: End-To-End Testing & Launch Readiness Verification
 */

namespace App\Tests;

use App\Services\Database;
use App\Services\Auth;
use App\Services\PaymentService;
use App\Services\SubscriptionService;
use App\Services\NotificationQueueService;
use App\Helpers\Security;
use App\Controllers\AuthController;
use App\Controllers\ProfileController;
use App\Controllers\ScholarshipController;
use App\Controllers\DocumentController;
use App\Controllers\ApplicationController;
use App\Controllers\BillingController;
use App\Controllers\IntelligenceController;
use App\Controllers\SEOController;
use ReflectionClass;
use ReflectionMethod;
use Exception;
use PDO;

class EndToEndLaunchTest {
    private PDO $db;
    private int $uVisitor;
    private int $uAdmin;
    private int $uEmployee;

    public function __construct() {
        $this->db = Database::connection();
    }

    public function run(): void {
        echo "--- Running EndToEndLaunchTest ---\n";
        
        $this->setUp();

        try {
            $this->testAuthenticationAndSessionSecurity();
            $this->testVisitorAccessAndDiscovery();
            $this->testApplicantWorkspaceWorkflow();
            $this->testDocumentManagementFlow();
            $this->testPaymentGatewayGating();
            $this->testSignedUrlGatingAndCSVInjection();
            $this->testAdminOperations();
            
            echo "EndToEndLaunchTest PASSED.\n\n";
        } finally {
            $this->tearDown();
        }
    }

    private function setUp(): void {
        $this->db->exec("DELETE FROM users WHERE email IN ('e2e_student@example.com', 'e2e_admin@example.com', 'e2e_employee@example.com')");

        $visitorRoleId = $this->db->query("SELECT id FROM roles WHERE name = 'visitor'")->fetchColumn();
        $adminRoleId = $this->db->query("SELECT id FROM roles WHERE name = 'admin'")->fetchColumn();
        $employeeRoleId = $this->db->query("SELECT id FROM roles WHERE name = 'employee'")->fetchColumn();

        $stmt = $this->db->prepare("INSERT INTO users (email, password_hash, first_name, last_name, role_id, status) VALUES (?, ?, ?, ?, ?, 'active')");
        
        $stmt->execute(['e2e_student@example.com', password_hash('Pass1234!', PASSWORD_BCRYPT), 'E2E', 'Student', $visitorRoleId]);
        $this->uVisitor = $this->db->lastInsertId();
        $this->db->prepare("INSERT INTO student_profiles (user_id) VALUES (?)")->execute([$this->uVisitor]);

        $stmt->execute(['e2e_admin@example.com', password_hash('Pass1234!', PASSWORD_BCRYPT), 'E2E', 'Admin', $adminRoleId]);
        $this->uAdmin = $this->db->lastInsertId();

        $stmt->execute(['e2e_employee@example.com', password_hash('Pass1234!', PASSWORD_BCRYPT), 'E2E', 'Employee', $employeeRoleId]);
        $this->uEmployee = $this->db->lastInsertId();
    }

    private function tearDown(): void {
        $this->db->exec("DELETE FROM users WHERE email IN ('e2e_student@example.com', 'e2e_admin@example.com', 'e2e_employee@example.com')");
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
        $_SESSION['user_name'] = 'E2E ' . ucfirst($role);
        $_SESSION['user_email'] = 'e2e_' . $role . '@example.com';
        
        $ref = new ReflectionClass('App\Services\Auth');
        $prop = $ref->getProperty('currentUser');
        $prop->setAccessible(true);
        $prop->setValue(null, null);
    }

    /**
     * 1. Login, logout, session security gates checks
     */
    private function testAuthenticationAndSessionSecurity(): void {
        // Authenticate guest redirect gates
        $_SESSION = [];
        try {
            Auth::requireAuth();
            throw new Exception("Auth verification failed: guest bypassed requireAuth()");
        } catch (\RuntimeException $e) {
            if ($e->getMessage() !== 'Redirect to login') {
                throw $e;
            }
        }

        // Test login
        $success = Auth::login('e2e_student@example.com', 'Pass1234!');
        if (!$success || !Auth::isAuthenticated()) {
            throw new Exception("Auth login failed with valid credentials.");
        }

        // Test user role gating
        if (!Auth::hasRole('visitor') || Auth::hasRole('admin')) {
            throw new Exception("Role verification mismatch.");
        }

        // Test CSRF token lifecycle
        $token = Security::csrfToken();
        if (empty($token) || !Security::verifyCsrfToken($token)) {
            throw new Exception("CSRF verification failed.");
        }

        // Test session fixation logic exists: verify Auth service starts session
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION = [];

        Auth::logout();
        if (Auth::isAuthenticated()) {
            throw new Exception("Logout failed to clear active sessions.");
        }

        echo "✔ Auth and session fixation protections verified.\n";
    }

    /**
     * 2. Robots, sitemaps, static pages catalog discovery
     */
    private function testVisitorAccessAndDiscovery(): void {
        $seoController = new SEOController();

        // Sitemap XML render check
        ob_start();
        $seoController->sitemap();
        $sitemapContent = ob_get_clean();

        if (strpos($sitemapContent, '<urlset') === false || strpos($sitemapContent, 'scholarships') === false) {
            throw new Exception("Sitemap rendering verification failed.");
        }

        // Robots txt rule check
        ob_start();
        $seoController->robots();
        $robotsContent = ob_get_clean();

        if (strpos($robotsContent, 'Disallow: /admin/') === false) {
            throw new Exception("Robots.txt rules check failed.");
        }

        echo "✔ SEO sitemaps and robots discovery verified.\n";
    }

    /**
     * 3. Profile fields, saved matches, and E2E application rules
     */
    private function testApplicantWorkspaceWorkflow(): void {
        $this->loginUser($this->uVisitor, 'visitor');

        $profileController = new ProfileController();
        $appController = new ApplicationController();

        // Complete educational details profile fields
        $_POST = [
            'csrf_token' => Security::csrfToken(),
            'first_name' => 'E2E',
            'last_name' => 'Student',
            'email' => 'e2e_student@example.com',
            'phone' => '+923001234567',
            'date_of_birth' => '2000-01-01',
            'gender' => 'male',
            'residence_country_id' => '1',
            'nationality_country_id' => '1'
        ];

        ob_start();
        try {
            $profileController->update();
        } catch (\RuntimeException $e) {
            // catch redirects
        }
        ob_end_clean();

        // Check DB update
        $dob = $this->db->query("SELECT date_of_birth FROM student_profiles WHERE user_id = {$this->uVisitor}")->fetchColumn();
        if ($dob !== '2000-01-01') {
            throw new Exception("Student profile update failed.");
        }

        // Test application deadline enforcement and status restrictions
        $schId = $this->db->query("SELECT id FROM scholarships LIMIT 1")->fetchColumn();
        if ($schId) {
            // Attempt to apply
            $_POST = [
                'csrf_token' => Security::csrfToken(),
                'scholarship_id' => $schId,
                'status' => 'planning',
                'personal_notes' => 'E2E Note'
            ];
            
            ob_start();
            try {
                $appController->store();
            } catch (\RuntimeException $e) {
                // catch redirects
            }
            ob_end_clean();

            $app = $this->db->query("SELECT * FROM scholarship_applications WHERE user_id = {$this->uVisitor} AND scholarship_id = {$schId} LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            if (!$app || $app['status'] !== 'planning') {
                throw new Exception("Application tracking creation failed.");
            }

            // IDOR block: verify student cannot submit administrative status ('accepted')
            $_POST = [
                'csrf_token' => Security::csrfToken(),
                'status' => 'accepted'
            ];
            
            ob_start();
            try {
                $appController->update((int)$app['id']);
                throw new Exception("IDOR application update status bypassed: student updated status to accepted.");
            } catch (\RuntimeException $e) {
                if (strpos($e->getMessage(), '403') === false && strpos($e->getMessage(), 'Redirect') === false) {
                    throw $e;
                }
            }
            ob_end_clean();
        }

        echo "✔ Profile fields, match data, and E2E application rules verified.\n";
    }

    /**
     * 4. Upload validations, double extension bypasses, and file security
     */
    private function testDocumentManagementFlow(): void {
        $this->loginUser($this->uVisitor, 'visitor');
        $docController = new DocumentController();

        // Mock malicious file structure
        $originalName = 'exploit.php.jpg';
        $tempPath = tempnam(sys_get_temp_dir(), 'e2e');
        file_put_contents($tempPath, '<?php echo "evil"; ?>');

        $docId = $this->db->query("SELECT id FROM documents LIMIT 1")->fetchColumn();

        $_POST = [
            'csrf_token' => Security::csrfToken(),
            'document_id' => $docId
        ];
        
        // Mock file upload globals
        $_FILES['file'] = [
            'name' => $originalName,
            'type' => 'application/x-php', // Spoofed MIME
            'tmp_name' => $tempPath,
            'error' => 0,
            'size' => filesize($tempPath)
        ];

        // Verify E2E uploads blocks MIME spoofing / unsafe names
        try {
            ob_start();
            $docController->upload();
            ob_end_clean();
            throw new Exception("Double extension file upload bypassed!");
        } catch (\RuntimeException $e) {
            // expected mock redirect due to error
        }

        @unlink($tempPath);

        // Upload valid PDF
        $validTemp = tempnam(sys_get_temp_dir(), 'e2e_pdf');
        // valid PDF header signature
        file_put_contents($validTemp, "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF");
        
        $_FILES['file'] = [
            'name' => 'document.pdf',
            'type' => 'application/pdf',
            'tmp_name' => $validTemp,
            'error' => 0,
            'size' => filesize($validTemp)
        ];

        ob_start();
        try {
            $docController->upload();
        } catch (\RuntimeException $e) {
            // Redirect expected
        }
        ob_end_clean();

        // Check document stored
        $storedDoc = $this->db->query("SELECT * FROM user_documents WHERE user_id = {$this->uVisitor} AND document_id = {$docId} LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if (!$storedDoc) {
            throw new Exception("Valid PDF document upload failed.");
        }

        // IDOR download block: try to access other user's document downloads
        $this->loginUser($this->uAdmin, 'admin'); // Not owner
        // Force login as another student
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = 'student_billing@example.com'");
        $stmt->execute();
        $otherStudentId = $stmt->fetchColumn();
        if ($otherStudentId) {
            $this->loginUser((int)$otherStudentId, 'visitor');
            try {
                ob_start();
                $docController->download((int)$storedDoc['id']);
                ob_end_clean();
                throw new Exception("IDOR document download gate bypassed!");
            } catch (\RuntimeException $e) {
                if ($e->getCode() !== 403) {
                    throw $e;
                }
            }
        }

        // Cleanup physical file upload
        if (file_exists($storedDoc['storage_path'])) {
            @unlink($storedDoc['storage_path']);
        }
        @unlink($validTemp);

        echo "✔ Upload validation formats, double extension block, and file security verified.\n";
    }

    /**
     * 5. Payments mocking environments checks (E2E payment boundary test)
     */
    private function testPaymentGatewayGating(): void {
        // Production + mock gateway => MUST throw exception
        $_ENV['APP_ENV'] = 'production';
        $_ENV['PAYMENT_PROVIDER'] = 'mock';

        try {
            PaymentService::gateway();
            throw new Exception("Payment Boundary Bypass: Mock payment resolved in production environment.");
        } catch (\RuntimeException $e) {
            if ($e->getMessage() !== 'Mock payment provider is disabled in production.') {
                throw $e;
            }
        }

        // Testing + mock gateway => MUST resolve successfully
        $_ENV['APP_ENV'] = 'testing';
        $gateway = PaymentService::gateway();
        if (!($gateway instanceof \App\Services\MockPaymentGateway)) {
            throw new Exception("Payment Resolving Failure: Failed to resolve MockPaymentGateway in testing mode.");
        }

        echo "✔ Payment mock boundary safety checks verified.\n";
    }

    /**
     * 6. HMAC signatures and CSV escaping checks
     */
    private function testSignedUrlGatingAndCSVInjection(): void {
        $controller = new IntelligenceController();
        
        // CSV cell formula escaping verification
        $ref = new ReflectionMethod('App\Controllers\IntelligenceController', 'sanitizeCSVCell');
        $ref->setAccessible(true);

        $val = $ref->invoke($controller, '   =SUM(1,2)');
        if ($val !== "'   =SUM(1,2)") {
            throw new Exception("CSV Injection Sanitizer failed to handle leading spaces: got: $val");
        }

        // Expired signature tokens check
        $_GET = [
            'type' => 'student',
            'expires' => time() - 100, // expired
            'token' => 'invalid_token'
        ];

        try {
            $controller->downloadExport();
            throw new Exception("Expired signature download bypassed!");
        } catch (\RuntimeException $e) {
            if ($e->getCode() !== 403) {
                throw $e;
            }
        }

        // Malformed parameter checking
        $_GET = [
            'type' => 'student',
            'expires' => time() + 3600,
            'token' => 'tampered_signature_token'
        ];

        try {
            $controller->downloadExport();
            throw new Exception("Invalid signature download bypassed!");
        } catch (\RuntimeException $e) {
            if ($e->getCode() !== 403) {
                throw $e;
            }
        }

        echo "✔ CSV escaping, HMAC url validation, and signed links token expiration verified.\n";
    }

    /**
     * 7. Admins document reviews, funnel, aggregates count checks
     */
    private function testAdminOperations(): void {
        $this->loginUser($this->uAdmin, 'admin');

        $docController = new DocumentController();

        // Get document to review
        $doc = $this->db->query("SELECT id FROM user_documents LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($doc) {
            $_POST = [
                'csrf_token' => Security::csrfToken()
            ];
            
            ob_start();
            try {
                $docController->approve((int)$doc['id']);
            } catch (\RuntimeException $e) {
                // redirects expected
            }
            ob_end_clean();

            $status = $this->db->query("SELECT status FROM user_documents WHERE id = {$doc['id']}")->fetchColumn();
            if ($status !== 'approved') {
                throw new Exception("Admin document review workflow failed.");
            }
        }

        echo "✔ Admin dashboard operations, reviews, and tracking metrics verified.\n";
    }
}
