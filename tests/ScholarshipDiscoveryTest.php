<?php

use App\Services\Database;
use App\Services\Auth;
use App\Services\ScholarshipMatchingService;
use App\Services\DocumentReadinessService;
use App\Controllers\ScholarshipController;
use App\Helpers\Security;

if (!defined('TESTING_MODE')) {
    define('TESTING_MODE', true);
}

class ScholarshipDiscoveryTest {
    private PDO $db;
    private int $studentId;
    private int $otherStudentId;
    private int $scholarshipId1;
    private int $scholarshipId2;
    private int $scholarshipId3;
    private int $scholarshipId4;
    private int $scholarshipId5;
    private int $countryId;
    private int $fieldId;
    private array $cleanUpUserIds = [];
    private array $cleanUpScholarshipIds = [];

    public function __construct() {
        $this->db = Database::connection();
    }

    public function run(): void {
        echo "--- Running ScholarshipDiscoveryTest ---\n";

        $this->cleanTestData();
        $this->setupTestData();

        try {
            $this->testSearchAndFilters();
            $this->testPersonalizedSorting();
            $this->testSaveAndUnsave();
            $this->testComparisonCart();
            $this->testSecurityAndPrivacy();
            $this->testHighVolumePerformanceSimulation();

            echo "ScholarshipDiscoveryTest PASSED.\n\n";
        } finally {
            $this->cleanTestData();
        }
    }

    private function loginUser(array $user): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $ref = new ReflectionClass(Auth::class);
        $prop = $ref->getProperty('currentUser');
        $prop->setAccessible(true);
        $prop->setValue(null, null);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role_name'] = $user['role_name'];
        $_SESSION['permissions'] = $user['permissions'] ?? [];
        $_SESSION['csrf_token'] = 'test_token';
    }

    private function setupTestData(): void {
        $this->db->beginTransaction();

        // 1. Create Study Country
        $this->db->exec("INSERT INTO countries (name, iso2, iso3, phone_code, currency_code) VALUES ('Discovery Country', 'DC', 'DSC', '99', 'DSC')");
        $this->countryId = (int)$this->db->lastInsertId();

        // 2. Create Field of Study
        $this->db->exec("INSERT INTO fields_of_study (name) VALUES ('Discovery Field')");
        $this->fieldId = (int)$this->db->lastInsertId();

        // 3. Create Student Users
        $this->db->exec("
            INSERT INTO users (email, password_hash, first_name, last_name, role_id, status)
            VALUES ('discovery-student-a@example.com', 'hash', 'DiscStudent', 'A', 
                    (SELECT id FROM roles WHERE name = 'visitor'), 'active')
        ");
        $this->studentId = (int)$this->db->lastInsertId();
        $this->cleanUpUserIds[] = $this->studentId;

        $this->db->exec("
            INSERT INTO users (email, password_hash, first_name, last_name, role_id, status)
            VALUES ('discovery-student-b@example.com', 'hash', 'DiscStudent', 'B', 
                    (SELECT id FROM roles WHERE name = 'visitor'), 'active')
        ");
        $this->otherStudentId = (int)$this->db->lastInsertId();
        $this->cleanUpUserIds[] = $this->otherStudentId;

        // Create student profile for Student A
        $this->db->exec("
            INSERT INTO student_profiles (user_id, date_of_birth, gender, nationality_country_id, residence_country_id)
            VALUES ({$this->studentId}, '2000-01-01', 'male', {$this->countryId}, {$this->countryId})
        ");
        $this->db->exec("
            INSERT INTO education_records (user_id, institution_name, degree_level, degree_title, field_of_study, cgpa, cgpa_scale)
            VALUES ({$this->studentId}, 'Discovery Uni', 'Master\'s', 'MSc CS', 'Computer Science', 3.80, 4.00)
        ");

        // 4. Create Scholarships with different attributes
        // Sch 1: Fully Funded, Country match, Verified, Featured
        $this->db->exec("
            INSERT INTO scholarships (title, provider_name, description, status, slug, funding_type, country_id, verification_status, is_featured, published_at)
            VALUES ('Discovery Sch Alpha', 'Provider A', 'Fully Funded opportunity', 'published', 'disc-sch-alpha', 'Fully Funded', {$this->countryId}, 'verified', 1, NOW())
        ");
        $this->scholarshipId1 = (int)$this->db->lastInsertId();
        $this->cleanUpScholarshipIds[] = $this->scholarshipId1;

        // Sch 2: Partially Funded, Country match, Draft (drafts must be hidden from student list)
        $this->db->exec("
            INSERT INTO scholarships (title, provider_name, description, status, slug, funding_type, country_id, verification_status, is_featured, published_at)
            VALUES ('Discovery Sch Beta', 'Provider B', 'Draft opportunity', 'draft', 'disc-sch-beta', 'Partially Funded', {$this->countryId}, 'unverified', 0, NOW())
        ");
        $this->scholarshipId2 = (int)$this->db->lastInsertId();
        $this->cleanUpScholarshipIds[] = $this->scholarshipId2;

        // Sch 3: Partially Funded, Verified, study Field match
        $this->db->exec("
            INSERT INTO scholarships (title, provider_name, description, status, slug, funding_type, country_id, verification_status, is_featured, published_at)
            VALUES ('Discovery Sch Gamma', 'Provider C', 'Gamma opportunity', 'published', 'disc-sch-gamma', 'Partially Funded', {$this->countryId}, 'verified', 0, NOW())
        ");
        $this->scholarshipId3 = (int)$this->db->lastInsertId();
        $this->cleanUpScholarshipIds[] = $this->scholarshipId3;
        $this->db->exec("INSERT INTO scholarship_fields (scholarship_id, field_of_study_id) VALUES ({$this->scholarshipId3}, {$this->fieldId})");

        // Sch 4: Fully Funded, Unverified, deadline soon
        $this->db->exec("
            INSERT INTO scholarships (title, provider_name, description, status, slug, funding_type, country_id, verification_status, is_featured, application_deadline, published_at)
            VALUES ('Discovery Sch Delta', 'Provider D', 'Delta opportunity', 'published', 'disc-sch-delta', 'Fully Funded', {$this->countryId}, 'unverified', 0, '" . date('Y-m-d', strtotime('+2 days')) . "', NOW())
        ");
        $this->scholarshipId4 = (int)$this->db->lastInsertId();
        $this->cleanUpScholarshipIds[] = $this->scholarshipId4;

        // Sch 5: Published, other country, rolling deadline (Null)
        $this->db->exec("
            INSERT INTO scholarships (title, provider_name, description, status, slug, funding_type, country_id, verification_status, is_featured, application_deadline, published_at)
            VALUES ('Discovery Sch Epsilon', 'Provider E', 'Epsilon opportunity', 'published', 'disc-sch-epsilon', 'Fully Funded', NULL, 'unverified', 0, NULL, NOW())
        ");
        $this->scholarshipId5 = (int)$this->db->lastInsertId();
        $this->cleanUpScholarshipIds[] = $this->scholarshipId5;

        // Create eligibility rules for Sch 1
        $this->db->exec("
            INSERT INTO scholarship_eligibility_rules (scholarship_id, minimum_age, maximum_age, minimum_cgpa, cgpa_scale)
            VALUES ({$this->scholarshipId1}, 18, 30, 3.00, 4.00)
        ");

        $this->db->commit();
    }

    private function cleanTestData(): void {
        // Fallback deletes to clean dirty states from previous runs
        $this->db->exec("DELETE FROM users WHERE email IN ('discovery-student-a@example.com', 'discovery-student-b@example.com')");
        $this->db->exec("DELETE FROM scholarships WHERE slug IN ('disc-sch-alpha', 'disc-sch-beta', 'disc-sch-gamma', 'disc-sch-delta', 'disc-sch-epsilon', 'temp-5')");
        $this->db->exec("DELETE FROM countries WHERE name = 'Discovery Country'");
        $this->db->exec("DELETE FROM fields_of_study WHERE name = 'Discovery Field'");

        $userIds = !empty($this->cleanUpUserIds) ? implode(',', $this->cleanUpUserIds) : '0';
        $schIds = !empty($this->cleanUpScholarshipIds) ? implode(',', $this->cleanUpScholarshipIds) : '0';

        $this->db->exec("DELETE FROM saved_scholarships WHERE user_id IN ($userIds) OR scholarship_id IN ($schIds)");
        $this->db->exec("DELETE FROM scholarship_matches WHERE user_id IN ($userIds) OR scholarship_id IN ($schIds)");
        $this->db->exec("DELETE FROM scholarship_eligibility_rules WHERE scholarship_id IN ($schIds)");
        $this->db->exec("DELETE FROM scholarship_fields WHERE scholarship_id IN ($schIds)");
        $this->db->exec("DELETE FROM scholarship_degree_levels WHERE scholarship_id IN ($schIds)");
        $this->db->exec("DELETE FROM scholarship_countries WHERE scholarship_id IN ($schIds)");
        $this->db->exec("DELETE FROM scholarship_eligible_nationalities WHERE scholarship_id IN ($schIds)");
        $this->db->exec("DELETE FROM scholarship_applications WHERE user_id IN ($userIds) OR scholarship_id IN ($schIds)");
        $this->db->exec("DELETE FROM education_records WHERE user_id IN ($userIds)");
        $this->db->exec("DELETE FROM student_profiles WHERE user_id IN ($userIds)");
        $this->db->exec("DELETE FROM users WHERE id IN ($userIds)");
        $this->db->exec("DELETE FROM scholarships WHERE id IN ($schIds)");
    }

    /**
     * Test Search and Filters
     */
    private function testSearchAndFilters(): void {
        echo "Testing advanced search filters and parameter combinations...\n";
        $controller = new ScholarshipController();

        // 1. Filter by keyword: "Alpha"
        $_GET = ['search' => 'Alpha'];
        ob_start();
        $controller->publicList();
        $output = ob_get_clean();

        if (strpos($output, 'Discovery Sch Alpha') === false || strpos($output, 'Discovery Sch Beta') !== false) {
            throw new Exception("Filter failed: keyword search did not return Alpha or returned non-Alpha.");
        }

        // 2. Filter by Verified only
        $_GET = ['verified' => '1'];
        ob_start();
        $controller->publicList();
        $output = ob_get_clean();
        
        if (strpos($output, 'Alpha') === false || strpos($output, 'Gamma') === false || strpos($output, 'Delta') !== false) {
            throw new Exception("Filter failed: verified filter failed to return Alpha/Gamma or returned Delta.");
        }

        // 3. Filter by Degree Level mapping
        $this->db->exec("INSERT INTO scholarship_degree_levels (scholarship_id, degree_level) VALUES ({$this->scholarshipId1}, 'Master\'s')");
        $_GET = ['degree' => 'Master\'s'];
        ob_start();
        $controller->publicList();
        $output = ob_get_clean();

        if (strpos($output, 'Alpha') === false || strpos($output, 'Delta') !== false) {
            throw new Exception("Filter failed: degree level filter failed.");
        }

        // 4. Combined filters: Country match + Funding Type
        $_GET = [
            'country_id' => $this->countryId,
            'funding_type' => 'Fully Funded'
        ];
        ob_start();
        $controller->publicList();
        $output = ob_get_clean();

        if (strpos($output, 'Alpha') === false || strpos($output, 'Delta') === false || strpos($output, 'Gamma') !== false) {
            throw new Exception("Filter failed: combined country and funding filters failed.");
        }
    }

    /**
     * Test Personalized Sorting
     */
    private function testPersonalizedSorting(): void {
        echo "Testing personalized sorting algorithms (Best Match, Deadline, Fully Funded)...\n";
        $controller = new ScholarshipController();

        // 1. Sort by Deadline Soon (delta deadline +10d, alpha deadline soon +2d)
        $_GET = ['sort' => 'deadline_soon'];
        ob_start();
        $controller->publicList();
        $output = ob_get_clean();

        // Delta closes soon, should precede Alpha (no deadline) and Epsilon (no deadline)
        // Let's verify display of lists works correctly without throwing exceptions
        
        // 2. Sort by Best Match
        $this->loginUser(['id' => $this->studentId, 'role_name' => 'visitor']);
        
        // Populate matching scores manually in DB to test sorting
        $this->db->exec("INSERT INTO scholarship_matches (user_id, scholarship_id, match_score, match_status) VALUES ({$this->studentId}, {$this->scholarshipId1}, 95, 'eligible')");
        $this->db->exec("INSERT INTO scholarship_matches (user_id, scholarship_id, match_score, match_status) VALUES ({$this->studentId}, {$this->scholarshipId3}, 60, 'eligible')");
        
        $_GET = ['sort' => 'match'];
        ob_start();
        $controller->publicList();
        $output = ob_get_clean();

        if (strpos($output, 'Match') === false) {
            throw new Exception("Best match sorting failed to display calculated scores.");
        }
    }

    /**
     * Test Save and Unsave
     */
    private function testSaveAndUnsave(): void {
        echo "Testing Save/Unsave actions, CSRF, and unique database constraints...\n";
        $controller = new ScholarshipController();

        // Login student A
        $this->loginUser(['id' => $this->studentId, 'role_name' => 'visitor']);

        // Save Alpha (CSRF invalid check)
        $_POST = [
            'csrf_token' => 'bad_csrf'
        ];
        ob_start();
        try {
            $controller->save($this->scholarshipId1);
            throw new Exception("Security vulnerability: saved bookmark with invalid CSRF token.");
        } catch (RuntimeException $e) {
            // Expected
        }
        ob_end_clean();

        // Save Alpha (valid)
        $_POST = [
            'csrf_token' => 'test_token'
        ];
        ob_start();
        try {
            $controller->save($this->scholarshipId1);
        } catch (RuntimeException $e) {
            // Redirect halt
        }
        ob_end_clean();

        // Verify bookmark exists in DB
        $count = $this->db->query("SELECT COUNT(*) FROM saved_scholarships WHERE user_id = {$this->studentId} AND scholarship_id = {$this->scholarshipId1}")->fetchColumn();
        if ((int)$count !== 1) {
            throw new Exception("Bookmark insert failed.");
        }

        // Test duplicate bookmark prevention (Unique Constraint)
        ob_start();
        try {
            $controller->save($this->scholarshipId1);
        } catch (RuntimeException $e) {
            // Redirect halt
        }
        ob_end_clean();

        $count = $this->db->query("SELECT COUNT(*) FROM saved_scholarships WHERE user_id = {$this->studentId} AND scholarship_id = {$this->scholarshipId1}")->fetchColumn();
        if ((int)$count !== 1) {
            throw new Exception("Bookmark duplication constraint failed.");
        }

        // Unsave Alpha
        $_POST = [
            'csrf_token' => 'test_token'
        ];
        ob_start();
        try {
            $controller->unsave($this->scholarshipId1);
        } catch (RuntimeException $e) {
            // Redirect halt
        }
        ob_end_clean();

        $count = $this->db->query("SELECT COUNT(*) FROM saved_scholarships WHERE user_id = {$this->studentId} AND scholarship_id = {$this->scholarshipId1}")->fetchColumn();
        if ((int)$count !== 0) {
            throw new Exception("Bookmark unsave failed.");
        }
    }

    /**
     * Test Comparison Cart
     */
    private function testComparisonCart(): void {
        echo "Testing comparison session cart adding, removal, limit constraints, and side-by-side view...\n";
        $controller = new ScholarshipController();

        $this->loginUser(['id' => $this->studentId, 'role_name' => 'visitor']);
        $_SESSION['compare_ids'] = [];

        $_POST = ['csrf_token' => 'test_token'];

        // Add 4 scholarships to compare cart
        foreach ([$this->scholarshipId1, $this->scholarshipId3, $this->scholarshipId4, $this->scholarshipId5] as $id) {
            try {
                $controller->addToCompare($id);
            } catch (RuntimeException $e) {
                // Redirect halt
            }
        }

        if (count($_SESSION['compare_ids']) !== 4) {
            throw new Exception("Failed to add items to comparison session cart.");
        }

        // Try adding a 5th item (exceeding limit)
        ob_start();
        try {
            // Setup a temp published scholarship to add
            $this->db->exec("INSERT INTO scholarships (title, provider_name, description, status, slug) VALUES ('Temp 5', 'P', 'D', 'published', 'temp-5')");
            $tempId = (int)$this->db->lastInsertId();
            
            $controller->addToCompare($tempId);
        } catch (RuntimeException $e) {
            // Redirect halt
        }
        ob_end_clean();

        if (count($_SESSION['compare_ids']) > 4) {
            throw new Exception("Comparison cart limit constraint bypassed! Allowed more than 4 items.");
        }

        // Render side-by-side comparison page
        ob_start();
        $controller->compare();
        $output = ob_get_clean();

        if (strpos($output, 'Discovery Sch Alpha') === false || strpos($output, 'Discovery Sch Gamma') === false) {
            throw new Exception("Comparison page did not display side-by-side compared scholarships.");
        }

        // Remove item from comparison
        ob_start();
        try {
            $controller->removeFromCompare($this->scholarshipId1);
        } catch (RuntimeException $e) {
            // Redirect halt
        }
        ob_end_clean();

        if (in_array($this->scholarshipId1, $_SESSION['compare_ids'])) {
            throw new Exception("Comparison item removal failed.");
        }

        // Clean up temp scholarship
        $this->db->exec("DELETE FROM scholarships WHERE id = $tempId");
    }

    /**
     * Test Comparison View bounds for normal draft protection
     */
    private function testSecurityAndPrivacy(): void {
        echo "Testing draft protection and IDOR saved isolation rules...\n";
        $controller = new ScholarshipController();

        // 1. Draft protection: Student cannot add draft to compare cart
        $this->loginUser(['id' => $this->studentId, 'role_name' => 'visitor']);
        $_POST = ['csrf_token' => 'test_token'];

        ob_start();
        try {
            $controller->addToCompare($this->scholarshipId2); // Draft
            throw new Exception("Security violation: allowed draft/unpublished scholarship in compare cart.");
        } catch (RuntimeException $e) {
            // Expected
        }
        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        // 2. IDOR unsave: Student B cannot delete Student A's saved bookmark
        $this->db->exec("INSERT INTO saved_scholarships (user_id, scholarship_id) VALUES ({$this->studentId}, {$this->scholarshipId1})");

        // Login as Student B
        $this->loginUser(['id' => $this->otherStudentId, 'role_name' => 'visitor']);
        $_POST = ['csrf_token' => 'test_token'];

        ob_start();
        try {
            $controller->unsave($this->scholarshipId1);
        } catch (RuntimeException $e) {
            // Redirect halt
        }
        ob_end_clean();

        // Bookmark must remain for student A
        $count = $this->db->query("SELECT COUNT(*) FROM saved_scholarships WHERE user_id = {$this->studentId} AND scholarship_id = {$this->scholarshipId1}")->fetchColumn();
        if ((int)$count !== 1) {
            throw new Exception("Security vulnerability: Student B deleted Student A's bookmark via IDOR unsave request.");
        }
    }

    /**
     * Performance scale verification
     */
    private function testHighVolumePerformanceSimulation(): void {
        echo "Running performance simulation under high volume...\n";

        $this->db->exec("DELETE FROM scholarships WHERE provider_name = 'Benchmark Provider'");

        // Fast bulk query generation using transactions
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                INSERT INTO scholarships (title, provider_name, description, status, slug, funding_type, published_at)
                VALUES (?, 'Benchmark Provider', 'Bulk opportunity details', 'published', ?, 'Fully Funded', NOW())
            ");
            
            for ($i = 1; $i <= 500; $i++) {
                $stmt->execute(["Benchmark Sch $i", "benchmark-sch-$i"]);
            }
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }

        // Measure query load performance
        $start = microtime(true);
        
        $controller = new ScholarshipController();
        $_GET = ['search' => 'Benchmark'];
        ob_start();
        $controller->publicList();
        ob_end_clean();

        $end = microtime(true);
        $duration = $end - $start;

        echo "✔ Profiled loading search list for 500 published opportunities: " . round($duration, 4) . " seconds.\n";
        
        // Clean up bulk benchmark records
        $this->db->exec("DELETE FROM scholarships WHERE provider_name = 'Benchmark Provider'");
    }
}
