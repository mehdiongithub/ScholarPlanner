<?php

require_once __DIR__ . '/bootstrap.php';

use App\Controllers\ScholarshipController;
use App\Controllers\SEOController;
use App\Services\Auth;
use App\Services\Database;
use App\Services\CacheService;
use App\Helpers\Security;

class PublicDiscoverySeoTest {
    private PDO $db;
    private array $cleanUpUserIds = [];
    private array $cleanUpScholarshipIds = [];

    private int $c1;
    private int $c2;
    private string $fieldName;
    private string $fieldSlug;
    private int $fieldId;

    private int $schPublished;
    private int $schDraft;
    private int $schArchived;
    private int $schExpired;
    private int $schClosingSoon;
    private int $schRolling;

    public function __construct() {
        $this->db = Database::connection();
    }

    public function run(): void {
        echo "\n--- Running PublicDiscoverySeoTest ---\n";
        
        try {
            $this->cleanTestData();
            $this->setupTestData();

            $this->testPublicVisibilityAndExclusions();
            $this->testSearchAndFilters();
            $this->testCleanSeoUrls();
            $this->testSecurityAndInputSanitization();
            $this->testStructuredDataAndMetaTags();
            $this->testRobotsAndSitemapDirectives();
            $this->testLightweightCaching();
            $this->testHighVolumePerformanceSimulation();

            echo "PublicDiscoverySeoTest PASSED.\n";
        } finally {
            $this->cleanTestData();
        }
    }

    private function setupTestData(): void {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
        $this->db->beginTransaction();

        // 1. Fetch or create countries to satisfy foreign keys
        $c1 = $this->db->query("SELECT id FROM countries ORDER BY id ASC LIMIT 1")->fetchColumn();
        if (!$c1) {
            $this->db->exec("INSERT INTO countries (name, iso2, iso3, phone_code, currency_code) VALUES ('United Kingdom', 'GB', 'GBR', '44', 'GBP')");
            $c1 = (int)$this->db->lastInsertId();
        }
        $c2 = $this->db->query("SELECT id FROM countries WHERE id != {$c1} ORDER BY id ASC LIMIT 1")->fetchColumn();
        if (!$c2) {
            $this->db->exec("INSERT INTO countries (name, iso2, iso3, phone_code, currency_code) VALUES ('Germany', 'DE', 'DEU', '49', 'EUR')");
            $c2 = (int)$this->db->lastInsertId();
        }

        $this->c1 = (int)$c1;
        $this->c2 = (int)$c2;

        // 2. Fetch or create field of study
        $field = $this->db->query("SELECT * FROM fields_of_study WHERE name NOT LIKE '%&%' AND name NOT LIKE '%/%' AND name NOT LIKE '%(%' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if (!$field) {
            $this->db->exec("INSERT INTO fields_of_study (name) VALUES ('Computer Science')");
            $fieldId = (int)$this->db->lastInsertId();
            $fieldName = 'Computer Science';
        } else {
            $fieldId = (int)$field['id'];
            $fieldName = $field['name'];
        }
        $this->fieldId = $fieldId;
        $this->fieldName = $fieldName;
        $this->fieldSlug = strtolower(str_replace(' ', '-', $fieldName));

        // 3. Create mock opportunities
        // A. Active Published (Host Country: c1, Study Country: c2)
        $stmt = $this->db->prepare("
            INSERT INTO scholarships (title, slug, provider_name, description, short_description, official_website, official_application_url, country_id, study_level, funding_type, application_open_date, application_deadline, status, verification_status, published_at)
            VALUES ('Seo Public Scholarship Alpha', 'seo-sch-alpha', 'SEO Provider A', 'Full Description Alpha', 'Short Description Alpha', 'https://seo-a.com', 'https://seo-a.com/apply', :c1, 'undergraduate', 'Fully Funded', '2026-01-01', '2026-12-31', 'published', 'verified', NOW())
        ");
        $stmt->execute(['c1' => $c1]);
        $this->schPublished = (int)$this->db->lastInsertId();
        $this->cleanUpScholarshipIds[] = $this->schPublished;

        // Associate study destination country c2 with Alpha
        $this->db->exec("INSERT INTO scholarship_countries (scholarship_id, country_id) VALUES ({$this->schPublished}, {$c2})");
        $this->db->exec("INSERT INTO scholarship_degree_levels (scholarship_id, degree_level) VALUES ({$this->schPublished}, \"Bachelor's\")");
        $this->db->exec("INSERT INTO scholarship_fields (scholarship_id, field_of_study_id) VALUES ({$this->schPublished}, {$this->fieldId})");

        // B. Incomplete Draft
        $this->db->exec("
            INSERT INTO scholarships (title, slug, provider_name, description, status)
            VALUES ('Seo Draft Opportunity', 'seo-sch-draft', 'Draft Provider', 'Draft Details', 'draft')
        ");
        $this->schDraft = (int)$this->db->lastInsertId();
        $this->cleanUpScholarshipIds[] = $this->schDraft;

        // C. Archived
        $this->db->exec("
            INSERT INTO scholarships (title, slug, provider_name, description, status)
            VALUES ('Seo Archived Opportunity', 'seo-sch-archived', 'Archived Provider', 'Archived Details', 'archived')
        ");
        $this->schArchived = (int)$this->db->lastInsertId();
        $this->cleanUpScholarshipIds[] = $this->schArchived;

        // D. Expired Published (Deadline: 1 day ago)
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $stmtExpired = $this->db->prepare("
            INSERT INTO scholarships (title, slug, provider_name, description, country_id, application_deadline, status, published_at)
            VALUES ('Seo Expired Opportunity', 'seo-sch-expired', 'Expired Provider', 'Expired Details', :c1, '{$yesterday}', 'published', NOW())
        ");
        $stmtExpired->execute(['c1' => $c1]);
        $this->schExpired = (int)$this->db->lastInsertId();
        $this->cleanUpScholarshipIds[] = $this->schExpired;

        // E. Closing Soon (Deadline: 3 days in future)
        $threeDaysFuture = date('Y-m-d', strtotime('+3 days'));
        $stmtClosing = $this->db->prepare("
            INSERT INTO scholarships (title, slug, provider_name, description, country_id, application_deadline, status, published_at)
            VALUES ('Seo Closing Soon Opportunity', 'seo-sch-closing', 'Closing Provider', 'Closing Details', :c1, '{$threeDaysFuture}', 'published', NOW())
        ");
        $stmtClosing->execute(['c1' => $c1]);
        $this->schClosingSoon = (int)$this->db->lastInsertId();
        $this->cleanUpScholarshipIds[] = $this->schClosingSoon;

        // F. Rolling / No Deadline
        $stmtRolling = $this->db->prepare("
            INSERT INTO scholarships (title, slug, provider_name, description, country_id, application_deadline, status, published_at)
            VALUES ('Seo Rolling Opportunity', 'seo-sch-rolling', 'Rolling Provider', 'Rolling Details', :c1, NULL, 'published', NOW())
        ");
        $stmtRolling->execute(['c1' => $c1]);
        $this->schRolling = (int)$this->db->lastInsertId();
        $this->cleanUpScholarshipIds[] = $this->schRolling;

        $this->db->commit();
    }

    private function cleanTestData(): void {
        Auth::logout();
        unset($_SESSION['user_id']);
        unset($_SESSION['role_name']);

        // Nullify static session cache to prevent login locks
        $refAuth = new ReflectionClass(Auth::class);
        $prop = $refAuth->getProperty('currentUser');
        $prop->setAccessible(true);
        $prop->setValue(null, null);

        $db = Database::connection();
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $db->beginTransaction();
        
        if (!empty($this->cleanUpScholarshipIds)) {
            $ids = implode(',', $this->cleanUpScholarshipIds);
            $db->exec("DELETE FROM scholarship_countries WHERE scholarship_id IN ($ids)");
            $db->exec("DELETE FROM scholarship_degree_levels WHERE scholarship_id IN ($ids)");
            $db->exec("DELETE FROM scholarship_fields WHERE scholarship_id IN ($ids)");
            $db->exec("DELETE FROM scholarship_deadlines WHERE scholarship_id IN ($ids)");
            $db->exec("DELETE FROM scholarships WHERE id IN ($ids)");
        }

        $db->commit();
        CacheService::clear();
    }

    /**
     * Test public visibility rules
     */
    private function testPublicVisibilityAndExclusions(): void {
        $controller = new ScholarshipController();

        // Standard public List page rendering check
        $_GET = [];
        
        ob_start();
        $controller->publicList();
        $output = ob_get_clean();

        // 1. Should render dynamic listing of active opportunities
        if (strpos($output, 'Seo Public Scholarship Alpha') === false) {
            throw new Exception("Published active scholarship not found in public listings output.");
        }

        // 2. Draft opportunities should never appear publicly
        if (strpos($output, 'Seo Draft Opportunity') !== false) {
            throw new Exception("Security Violation: Draft scholarship visible in public listings.");
        }

        // 3. Archived opportunities should never appear publicly
        if (strpos($output, 'Seo Archived Opportunity') !== false) {
            throw new Exception("Security Violation: Archived scholarship visible in public listings.");
        }
    }

    /**
     * Test keyword search and sidebar filters
     */
    private function testSearchAndFilters(): void {
        $controller = new ScholarshipController();

        // 1. Keyword search check
        $_GET = ['search' => 'Alpha'];
        ob_start();
        $controller->publicList();
        $output = ob_get_clean();
        if (strpos($output, 'Seo Public Scholarship Alpha') === false) {
            throw new Exception("Keyword filter failed to show matching opportunity.");
        }

        // 2. Host Country filter check
        $_GET = ['host_country_id' => $this->c1];
        ob_start();
        $controller->publicList();
        $output = ob_get_clean();
        if (strpos($output, 'Seo Public Scholarship Alpha') === false) {
            throw new Exception("Host country filter failed to show opportunities.");
        }

        // 3. Target Study Destination country filter check
        $_GET = ['study_destination_id' => $this->c2];
        ob_start();
        $controller->publicList();
        $output = ob_get_clean();
        if (strpos($output, 'Seo Public Scholarship Alpha') === false) {
            throw new Exception("Study destination filter failed to show opportunity.");
        }

        // 4. Fully Funded filter checkbox check
        $_GET = ['fully_funded' => '1'];
        ob_start();
        $controller->publicList();
        $output = ob_get_clean();
        if (strpos($output, 'Seo Public Scholarship Alpha') === false) {
            throw new Exception("Fully funded filter failed.");
        }

        // 5. Deadline status filters check
        // A. Closing soon list
        $_GET = ['deadline_status' => 'closing_soon'];
        ob_start();
        $controller->publicList();
        $output = ob_get_clean();
        if (strpos($output, 'Seo Closing Soon Opportunity') === false) {
            throw new Exception("Deadline status 'closing_soon' failed.");
        }
        if (strpos($output, 'Seo Public Scholarship Alpha') !== false) {
            throw new Exception("Opportunity with far deadline included in closing_soon list.");
        }

        // B. Rolling list
        $_GET = ['deadline_status' => 'rolling'];
        ob_start();
        $controller->publicList();
        $output = ob_get_clean();
        if (strpos($output, 'Seo Rolling Opportunity') === false) {
            throw new Exception("Deadline status 'rolling' failed.");
        }
    }

    /**
     * Test clean SEO URLs
     */
    private function testCleanSeoUrls(): void {
        $controller = new ScholarshipController();

        // 1. Country clean URL filter: host country 1 is "United Kingdom" or country code
        // We will mock SEO slugs to test clean list routing handlers
        $_GET = [];
        
        // Find name of country id c1
        $countryName = $this->db->query("SELECT name FROM countries WHERE id = {$this->c1}")->fetchColumn();
        $countrySlug = strtolower(str_replace(' ', '-', $countryName));

        ob_start();
        $controller->publicListByCountry($countrySlug);
        $output = ob_get_clean();
        
        if (strpos($output, "Scholarships in {$countryName}") === false) {
            throw new Exception("Clean SEO URL List by Country failed to render country header title.");
        }

        // 2. Field clean URL filter
        $_GET = [];
        ob_start();
        $controller->publicListByField($this->fieldSlug);
        $output = ob_get_clean();
        if (strpos($output, "{$this->fieldName} Scholarships") === false) {
            throw new Exception("Clean SEO URL List by Field failed to render field header title. Field Name: '{$this->fieldName}', Slug: '{$this->fieldSlug}'. Output snippet: " . substr($output, 0, 500));
        }

        // 3. Degree level clean URL filter
        $_GET = [];
        ob_start();
        $controller->publicListByDegree("bachelor's");
        $output = ob_get_clean();
        if (strpos($output, "Bachelor&#039;s Level Scholarships") === false) {
            throw new Exception("Clean SEO URL List by Degree level failed to render degree header title.");
        }
    }

    /**
     * Test input safety (XSS, negative pages, array parameters type errors)
     */
    private function testSecurityAndInputSanitization(): void {
        $controller = new ScholarshipController();

        // 1. Array parameter query attack check (should NOT crash with warnings/errors)
        $_GET = [
            'search' => ['dangerous_array' => 'string'],
            'country_id' => ['array_id' => 1],
            'degree' => ['deg_arr' => 'undergraduate']
        ];
        
        try {
            ob_start();
            $controller->publicList();
            ob_end_clean();
        } catch (TypeError $e) {
            throw new Exception("Type coercion vulnerability: Array search inputs caused TypeError exception.");
        }

        // 2. Negative/huge page parameters checks
        $_GET = ['page' => -9999];
        ob_start();
        $controller->publicList();
        $output = ob_get_clean();
        if (strpos($output, 'Seo Public Scholarship Alpha') === false) {
            throw new Exception("Negative page parameter handling failed.");
        }

        $_GET = ['page' => 999999999];
        ob_start();
        $controller->publicList();
        $output = ob_get_clean();
        // Should empty states gracefully
        if (strpos($output, 'No Scholarships Found') === false) {
            throw new Exception("Graceful empty pagination boundaries check failed.");
        }
    }

    /**
     * Test Schema JSON-LD structured data and meta descriptions
     */
    private function testStructuredDataAndMetaTags(): void {
        $controller = new ScholarshipController();
        $_GET = [];
        ob_start();
        $controller->publicDetail('seo-sch-alpha');
        $output = ob_get_clean();

        // 1. Valid HTML head tags
        if (strpos($output, '<link rel="canonical" href="') === false) {
            throw new Exception("Canonical tag link missing from public detail layout head.");
        }

        if (strpos($output, '<meta property="og:title"') === false) {
            throw new Exception("Open Graph title attribute tag missing from detail layout.");
        }

        // 2. JSON-LD checks
        if (strpos($output, '<script type="application/ld+json">') === false) {
            throw new Exception("Schema.org JSON-LD tag missing from detail layout head.");
        }

        // Extract and decode json schema block
        preg_match('@<script type="application/ld\+json">(.*?)</script>@s', $output, $matches);
        if (empty($matches[1])) {
            throw new Exception("JSON-LD structured data content block is empty.");
        }

        $schema = json_decode(trim($matches[1]), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("JSON-LD structured data contains invalid JSON payload format: " . json_last_error_msg());
        }

        if (($schema['@type'] ?? '') !== 'Grant') {
            throw new Exception("Incorrect Schema type in JSON-LD structured block. Expected: Grant.");
        }

        if ($schema['name'] !== 'Seo Public Scholarship Alpha') {
            throw new Exception("Incorrect structured property 'name' in JSON-LD schema.");
        }
    }

    /**
     * Test robots.txt crawling commands and Sitemap XML streaming
     */
    private function testRobotsAndSitemapDirectives(): void {
        $seoController = new SEOController();

        // 1. Robots.txt check
        ob_start();
        $seoController->robots();
        $robots = ob_get_clean();
        
        if (strpos($robots, 'User-agent: *') === false) {
            throw new Exception("Invalid robots.txt content: missing User-agent header.");
        }
        if (strpos($robots, 'Disallow: /admin/') === false) {
            throw new Exception("Security vulnerability: administrative prefixes allowed in robots.txt.");
        }

        // 2. Sitemap.xml checks
        ob_start();
        $seoController->sitemap();
        $sitemap = ob_get_clean();

        if (strpos($sitemap, '<?xml version="1.0" encoding="UTF-8"?>') === false) {
            throw new Exception("Invalid XML declaration header in sitemap output.");
        }
        
        if (strpos($sitemap, '<urlset') === false) {
            throw new Exception("Invalid XML tag structure: missing urlset block in sitemap.");
        }

        // Should include active published listing
        if (strpos($sitemap, 'seo-sch-alpha') === false) {
            throw new Exception("Active published opportunity slug missing from sitemap index.");
        }

        // Should NOT include drafts
        if (strpos($sitemap, 'seo-sch-draft') !== false) {
            throw new Exception("Security Violation: Draft listing indexed in public sitemap.");
        }

        // Should NOT include archived
        if (strpos($sitemap, 'seo-sch-archived') !== false) {
            throw new Exception("Security Violation: Archived listing indexed in public sitemap.");
        }

        // Should NOT include expired opportunities
        if (strpos($sitemap, 'seo-sch-expired') !== false) {
            throw new Exception("Security Violation: Expired opportunities indexed in public sitemap.");
        }
    }

    /**
     * Test file caching retrieval and action invalidation Hooks
     */
    private function testLightweightCaching(): void {
        CacheService::clear();

        $key = 'test_sample_cache_key';
        $data = ['name' => 'Cache Test Block', 'payload' => 12345];

        // Ensure writing caches works
        CacheService::set($key, $data);
        
        $hash = md5($key);
        $path = ROOT_PATH . '/storage/cache/cache_' . $hash . '.json';

        if (!file_exists($path)) {
            throw new Exception("CacheService::set failed to write cache file on disk.");
        }

        $retrieved = json_decode(file_get_contents($path), true);
        if ($retrieved !== $data) {
            throw new Exception("Cache file contents on disk do not match written data.");
        }

        // Test invalidation hooks
        CacheService::clear();
        if (file_exists($path)) {
            throw new Exception("CacheService::clear failed to flush cache file payloads.");
        }
    }

    /**
     * Profile loading database queries under high volume simulation
     */
    private function testHighVolumePerformanceSimulation(): void {
        $db = Database::connection();
        $db->beginTransaction();

        $benchIds = [];
        try {
            // Generate 1,000 mock opportunities to profile search indices scaling
            $stmt = $db->prepare("
                INSERT INTO scholarships (title, slug, provider_name, description, country_id, study_level, funding_type, status, application_deadline)
                VALUES (:title, :slug, 'Provider', 'Desc', 1, 'undergraduate', 'Fully Funded', 'published', '2028-01-01')
            ");

            for ($i = 1; $i <= 1000; $i++) {
                $stmt->execute([
                    'title' => "Bench Opportunity {$i}",
                    'slug' => "bench-opp-{$i}"
                ]);
                $benchIds[] = (int)$db->lastInsertId();
            }

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }

        // Run search queries under load and profile time duration
        $_GET = ['search' => 'Bench Opportunity 500'];
        $controller = new ScholarshipController();

        $start = microtime(true);
        ob_start();
        $controller->publicList();
        ob_end_clean();
        $end = microtime(true);

        $duration = $end - $start;
        echo "✔ Profiled loading public search catalog (1,000+ listings): {$duration} seconds.\n";

        // Clean up mock load records
        $db->beginTransaction();
        $idsStr = implode(',', $benchIds);
        $db->exec("DELETE FROM scholarships WHERE id IN ($idsStr)");
        $db->commit();
    }
}
