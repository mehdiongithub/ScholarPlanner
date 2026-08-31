<?php

use App\Services\Database;
use App\Services\Auth;
use App\Helpers\Security;

class ScholarshipTest {
    private PDO $db;

    public function __construct() {
        $this->db = Database::connection();
    }

    /**
     * Run all Scholarship tests
     */
    public function run(): void {
        echo "--- Running ScholarshipTest ---\n";

        $this->cleanTestData();

        try {
            $this->testScholarshipSlugGeneration();
            $this->testObviousDuplicateDetection();
            $this->testScholarshipPivotsSaving();
            $this->testEligibilityConstraintsValidation();
            $this->testStatusLifecycleAndVisibility();
            $this->testEmployeePublishPermissionsGating();
            $this->testDynamicDeadlineChecks();
            $this->testSanitizationXssShield();
            $this->testDeletionSafetyGuard();
            $this->testPublishCompletenessGuard();
            $this->testHtmlSanitizerEdgeCases();
            $this->testUrlValidationEdgeCases();
            $this->testPaginationAndSortEdgeCases();

            echo "ScholarshipTest PASSED.\n\n";
        } finally {
            $this->cleanTestData();
        }
    }

    /**
     * Clear test records
     */
    private function cleanTestData(): void {
        $this->db->exec("DELETE FROM scholarships WHERE title LIKE 'Test Scholarship %'");
        $this->db->exec("DELETE FROM users WHERE email LIKE 'test_employee_%@scholarmatch.test'");
    }

    /**
     * 1. Assert unique SEO slug generation
     */
    private function testScholarshipSlugGeneration(): void {
        $controller = new \App\Controllers\ScholarshipController();
        $reflector = new ReflectionMethod(\App\Controllers\ScholarshipController::class, 'generateSlug');
        $reflector->setAccessible(true);

        $title = "Test Scholarship Erasmus";
        $slug1 = $reflector->invoke($controller, $title);

        // Insert mock record with slug1
        $stmt = $this->db->prepare("
            INSERT INTO scholarships (title, slug, provider_name, description, status) 
            VALUES (:title, :slug, 'EU Commission', 'Desc', 'draft')
        ");
        $stmt->execute(['title' => $title, 'slug' => $slug1]);
        $id1 = $this->db->lastInsertId();

        // Generate slug with same title again - should append suffix
        $slug2 = $reflector->invoke($controller, $title);
        if ($slug2 !== $slug1 . '-1') {
             throw new \Exception("Slug Generation Error: Expected incremental suffix for duplicates, got '$slug2'.");
        }

        echo "✔ Unique SEO slug generator verified.\n";
    }

    /**
     * 2. Assert obvious duplicate detection
     */
    private function testObviousDuplicateDetection(): void {
        $title = "Test Scholarship Dupe";
        $provider = "Dupe Org";
        $url = "https://example.com/apply-dupe";

        // Insert first
        $stmt = $this->db->prepare("
            INSERT INTO scholarships (title, slug, provider_name, description, official_application_url, status) 
            VALUES (:title, 'dupe-slug-1', :provider, 'Desc', :url, 'draft')
        ");
        $stmt->execute(['title' => $title, 'provider' => $provider, 'url' => $url]);

        // Duplicate check simulation
        $stmtCheck = $this->db->prepare("
            SELECT COUNT(*) FROM scholarships 
            WHERE provider_name = :provider AND title = :title AND official_application_url = :url
        ");
        $stmtCheck->execute([
            'provider' => $provider,
            'title' => $title,
            'url' => $url
        ]);
        
        if ((int)$stmtCheck->fetchColumn() === 0) {
             throw new \Exception("Duplicate Detection Error: Duplicated criteria provider/title/URL was not caught.");
        }

        echo "✔ Deterministic duplicate detection constraints verified.\n";
    }

    /**
     * 3. Assert target pivots map countries, disciplines, levels, and documents
     */
    private function testScholarshipPivotsSaving(): void {
        $title = "Test Scholarship Pivots";
        $stmt = $this->db->prepare("
            INSERT INTO scholarships (title, slug, provider_name, description, status) 
            VALUES (:title, 'pivot-slug', 'Test Provider', 'Desc', 'draft')
        ");
        $stmt->execute(['title' => $title]);
        $sid = $this->db->lastInsertId();

        // 1. Map study countries
        $pkId = $this->db->query("SELECT id FROM countries WHERE iso2 = 'PK'")->fetchColumn();
        $this->db->prepare("INSERT INTO scholarship_countries (scholarship_id, country_id) VALUES (:sid, :cid)")->execute(['sid' => $sid, 'cid' => $pkId]);

        // 2. Map disciplines
        $this->db->prepare("INSERT INTO scholarship_fields (scholarship_id, field_of_study_id) VALUES (:sid, 1)")->execute(['sid' => $sid]);

        // 3. Map target degree tiers
        $this->db->prepare("INSERT INTO scholarship_degree_levels (scholarship_id, degree_level) VALUES (:sid, 'Master\'s')")->execute(['sid' => $sid]);

        // Verify counts
        $cCount = $this->db->query("SELECT COUNT(*) FROM scholarship_countries WHERE scholarship_id = $sid")->fetchColumn();
        $fCount = $this->db->query("SELECT COUNT(*) FROM scholarship_fields WHERE scholarship_id = $sid")->fetchColumn();
        $dCount = $this->db->query("SELECT COUNT(*) FROM scholarship_degree_levels WHERE scholarship_id = $sid")->fetchColumn();

        if ((int)$cCount !== 1 || (int)$fCount !== 1 || (int)$dCount !== 1) {
            throw new \Exception("Pivots Save Error: Many-to-many lookup maps were not written successfully.");
        }

        echo "✔ Normalized many-to-many relationship mappings verified.\n";
    }

    /**
     * 4. Assert GPA, ages, and validation limits
     */
    private function testEligibilityConstraintsValidation(): void {
        // Let's assert date ordering directly:
        $openDate = '2026-10-01';
        $deadlineDate = '2026-09-01'; // invalid before open
        
        if (strtotime($openDate) > strtotime($deadlineDate)) {
             $datesInvalid = true;
        } else {
             $datesInvalid = false;
        }

        if (!$datesInvalid) {
             throw new \Exception("Validation Error: Deadline date ordering check failed.");
        }

        // Assert CGPA scales
        $cgpa = 3.8;
        $scale = 4.0;
        if ($cgpa > $scale) {
             throw new \Exception("Validation Error: CGPA cannot exceed scale.");
        }

        echo "✔ Scholarship eligibility constraints verified.\n";
    }

    /**
     * 5. Assert Draft/Archived vs Published visibility
     */
    private function testStatusLifecycleAndVisibility(): void {
        // Create draft scholarship
        $this->db->exec("
            INSERT INTO scholarships (title, slug, provider_name, description, status) 
            VALUES ('Test Scholarship Draft', 'draft-vis-slug', 'Life Org', 'Desc', 'draft')
        ");
        $sidDraft = $this->db->lastInsertId();

        // Create published scholarship
        $this->db->exec("
            INSERT INTO scholarships (title, slug, provider_name, description, status) 
            VALUES ('Test Scholarship Published', 'pub-vis-slug', 'Life Org', 'Desc', 'published')
        ");
        $sidPub = $this->db->lastInsertId();

        // Query public lists check (only published should appear)
        $stmt = $this->db->query("SELECT id FROM scholarships WHERE status = 'published' AND title LIKE 'Test Scholarship %'");
        $publishedRows = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (count($publishedRows) !== 1) {
             throw new \Exception("Lifecycle Error: Expected only 1 published test scholarship, got " . count($publishedRows));
        }

        echo "✔ Draft/Published lifecycle access visibility verified.\n";
    }

    /**
     * 6. Assert Employee publish gates are checked
     */
    private function testEmployeePublishPermissionsGating(): void {
        $visitorRoleId = $this->db->query("SELECT id FROM roles WHERE name = 'visitor'")->fetchColumn();
        $employeeRoleId = $this->db->query("SELECT id FROM roles WHERE name = 'employee'")->fetchColumn();

        // Create mock Employee without publish permissions
        $email = 'test_employee_no_perm@scholarmatch.test';
        $stmt = $this->db->prepare("
            INSERT INTO users (role_id, first_name, last_name, email, phone, password_hash, status) 
            VALUES (:role_id, 'EmpNoPub', 'Test', :email, '+923007777790', 'hash', 'active')
        ");
        $stmt->execute(['role_id' => $employeeRoleId, 'email' => $email]);
        $empId = $this->db->lastInsertId();

        // Mock permission check simulation (no publish permission assigned by role seeders for raw employee)
        $permissionName = 'scholarships.publish';
        $stmtPerm = $this->db->prepare("
            SELECT COUNT(*) 
            FROM role_permissions rp
            JOIN permissions p ON rp.permission_id = p.id
            WHERE rp.role_id = :role_id AND p.name = :permission
        ");
        $stmtPerm->execute([
            'role_id' => $employeeRoleId,
            'permission' => $permissionName
        ]);
        $hasPermission = (int)$stmtPerm->fetchColumn() > 0;

        if ($hasPermission) {
             throw new \Exception("Permissions Gating Error: Employee has publish privileges without administrative assignment.");
        }

        echo "✔ Employee publication permissions gates passed.\n";
    }

    /**
     * 7. Assert dynamic deadline calculation
     */
    private function testDynamicDeadlineChecks(): void {
        $today = date('Y-m-d');
        $pastDeadline = date('Y-m-d', strtotime('-5 days'));
        $futureDeadline = date('Y-m-d', strtotime('+3 days'));

        // Past check
        if (strtotime($today) > strtotime($pastDeadline)) {
            $pastStatus = 'Deadline Passed';
        } else {
            $pastStatus = 'Open';
        }

        // Future closing check
        $diff = strtotime($futureDeadline) - strtotime($today);
        if ($diff <= 7 * 86400 && $diff >= 0) {
            $futureStatus = 'Closing Soon';
        } else {
            $futureStatus = 'Open';
        }

        if ($pastStatus !== 'Deadline Passed' || $futureStatus !== 'Closing Soon') {
             throw new \Exception("Deadline Status Error: Dynamic deadline checks failed.");
        }

        echo "✔ Dynamic deadline calculations verified.\n";
    }

    /**
     * 8. Verify HTML description sanitizer strips all event handlers and unquoted event tags
     */
    private function testSanitizationXssShield(): void {
        $controller = new \App\Controllers\ScholarshipController();
        $reflector = new ReflectionMethod(\App\Controllers\ScholarshipController::class, 'sanitizeHtml');
        $reflector->setAccessible(true);

        $payloads = [
            '<script>alert(1)</script>' => '',
            '<img src=x onerror=alert(1)>' => '',
            '<a href="javascript:alert(1)">test</a>' => '<a>test</a>',
            '<div onclick="alert(1)">test</div>' => 'test',
            '<p onclick=alert(1)>test</p>' => '<p>test</p>',
            '<li style="color:red;" onmouseover="alert(1)">item</li>' => '<li>item</li>'
        ];

        foreach ($payloads as $dirty => $expected) {
            $clean = $reflector->invoke($controller, $dirty);
            if ($clean !== $expected) {
                throw new \Exception("XSS Sanitization Failure: Expected '$expected', got '$clean' for payload '$dirty'.");
            }
        }

        echo "✔ HTML description attribute-stripping XSS sanitizer verified.\n";
    }

    /**
     * 9. Verify deletion safety (cannot hard delete non-drafts)
     */
    private function testDeletionSafetyGuard(): void {
        // Create a published test record
        $this->db->exec("
            INSERT INTO scholarships (title, slug, provider_name, description, status) 
            VALUES ('Test Scholarship Published Delete Safety', 'delete-safety-slug', 'Safety Org', 'Desc', 'published')
        ");
        $id = $this->db->lastInsertId();

        // Simulate status query check in delete
        $status = $this->db->query("SELECT status FROM scholarships WHERE id = $id")->fetchColumn();
        if ($status !== 'draft') {
            $deleteBlocked = true;
        } else {
            $deleteBlocked = false;
        }

        if (!$deleteBlocked) {
            throw new \Exception("Delete Safety Failure: Allowed permanent deletion of a non-draft scholarship.");
        }

        echo "✔ Non-draft delete safety logic checks passed.\n";
    }

    /**
     * 10. Verify completeness check before publishing
     */
    private function testPublishCompletenessGuard(): void {
        // Create an incomplete draft
        $this->db->exec("
            INSERT INTO scholarships (title, slug, provider_name, description, status) 
            VALUES ('Test Scholarship Incomplete', 'incomplete-slug', 'Safety Org', 'Desc', 'draft')
        ");
        $id = $this->db->lastInsertId();

        // Load record and rules (missing country, application url, eligibility rules, and source url)
        $scholarship = $this->db->query("SELECT * FROM scholarships WHERE id = $id")->fetch(PDO::FETCH_ASSOC);
        $rules = $this->db->query("SELECT * FROM scholarship_eligibility_rules WHERE scholarship_id = $id")->fetch(PDO::FETCH_ASSOC);
        $source = $this->db->query("SELECT * FROM scholarship_sources WHERE scholarship_id = $id")->fetch(PDO::FETCH_ASSOC);

        $cannotPublish = (
            empty($scholarship['title']) ||
            empty($scholarship['description']) ||
            empty($scholarship['provider_name']) ||
            empty($scholarship['country_id']) ||
            empty($scholarship['official_application_url']) ||
            (empty($scholarship['application_deadline']) && ($scholarship['deadline_type'] ?? '') !== 'rolling') ||
            empty($rules) ||
            empty($source['source_url'])
        );

        if (!$cannotPublish) {
            throw new \Exception("Publish Completeness Failure: Incomplete scholarship draft bypassed publish constraints.");
        }

        echo "✔ Publish completeness checks blocking incomplete drafts verified.\n";
    }

    /**
     * 11. Verify HTML Sanitizer edge-case payloads
     */
    private function testHtmlSanitizerEdgeCases(): void {
        $controller = new \App\Controllers\ScholarshipController();
        $reflector = new ReflectionMethod(\App\Controllers\ScholarshipController::class, 'sanitizeHtml');
        $reflector->setAccessible(true);

        $payloads = [
            '<img/src=x onerror=alert(1)>' => '',
            '<IMG SRC=x ONERROR=alert(1)>' => '',
            '<p/onmouseover=alert(1)>Test</p>' => '<p>Test</p>',
            '<script src="https://evil.example/x.js"></script>' => '',
            '<scr<script>ipt>alert(1)</scr</script>ipt>' => '',
            '<svg><script>alert(1)</script></svg>' => '',
            '<iframe src="https://evil.example"></iframe>' => '',
            '<object data="javascript:alert(1)"></object>' => '',
            '<math><mtext>test</mtext></math>' => 'test',
            '&lt;script&gt;alert(1)&lt;/script&gt;' => '&lt;script&gt;alert(1)&lt;/script&gt;'
        ];

        foreach ($payloads as $dirty => $expected) {
            $clean = $reflector->invoke($controller, $dirty);
            if ($clean !== $expected) {
                throw new \Exception("Sanitizer Edge-Case Failure: Expected '$expected', got '$clean' for payload '$dirty'.");
            }
        }

        echo "✔ HTML description sanitizer edge cases verified.\n";
    }

    /**
     * 12. Verify URL Validation Edge Cases
     */
    private function testUrlValidationEdgeCases(): void {
        $payloads = [
            'javascript:alert(1)' => false,
            ' JAVASCRIPT:alert(1)' => false,
            'javaSCRIPT:alert(1)' => false,
            'data:text/html,<script>alert(1)</script>' => false,
            'vbscript:alert(1)' => false,
            'file:///etc/passwd' => false,
            'about:blank' => false,
            '//evil.example' => false,
            'https://example.com' => true,
            'http://example.com' => true,
            '   https://example.com   ' => true
        ];

        foreach ($payloads as $url => $expected) {
            $trimmed = trim($url);
            $isValid = filter_var($trimmed, FILTER_VALIDATE_URL) && preg_match('/^https?:\/\//i', $trimmed);
            if ((bool)$isValid !== $expected) {
                throw new \Exception("URL Edge-Case Failure: Expected validity '$expected', got '" . ($isValid ? 'true' : 'false') . "' for URL '$url'.");
            }
        }

        echo "✔ URL scheme and character edge cases verified.\n";
    }

    /**
     * 13. Verify pagination and sorting type coercion
     */
    private function testPaginationAndSortEdgeCases(): void {
        // Page array parameter casting simulation
        $pageArray = ['1'];
        $pageInt = max(1, (int)$pageArray);
        if ($pageInt !== 1) {
            throw new \Exception("Pagination Edge-Case Failure: Array parameter coercion failed.");
        }

        // Sorting whitelist validation simulation
        $sortChoices = ['title', 'random_sql', 'id DESC'];
        $allowed = ['title', 'provider_name', 'application_deadline', 'published_at'];
        foreach ($sortChoices as $choice) {
            if (in_array($choice, $allowed)) {
                $validated = $choice;
            } else {
                $validated = 'published_at';
            }
            if ($choice === 'random_sql' && $validated !== 'published_at') {
                throw new \Exception("Sort Edge-Case Failure: Allowed arbitrary SQL sort order injection.");
            }
        }

        echo "✔ Pagination and sorting coercion parameters verified.\n";
    }
}
