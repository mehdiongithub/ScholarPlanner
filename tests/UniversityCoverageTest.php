<?php

use App\Services\Database;
use App\Services\Auth;

class UniversityCoverageTest {
    private PDO $db;
    
    // Test data storage variables
    private $pkId;
    private $inId;
    private $punjabPkId;
    private $sindhPkId;
    private $islamabadPkId;
    private $balochistanPkId;
    private $punjabInId;
    
    public function __construct() {
        $this->db = Database::connection();
    }

    public function run(): void {
        echo "--- Running UniversityCoverageTest ---\n";
        
        $this->cleanTestData();
        $this->setupTestData();

        try {
            $this->runTestCases();
            echo "UniversityCoverageTest PASSED.\n\n";
        } finally {
            $this->cleanTestData();
        }
    }

    private function cleanTestData(): void {
        $this->db->exec("DELETE FROM institution_states WHERE institution_id IN (SELECT id FROM institutions WHERE name LIKE 'Test % University')");
        $this->db->exec("DELETE FROM institutions WHERE name LIKE 'Test % University'");
        $this->db->exec("DELETE FROM states WHERE name = 'Punjab' AND country_id = (SELECT id FROM countries WHERE iso2 = 'IN' LIMIT 1)");
    }

    private function setupTestData(): void {
        $pkId = $this->db->query("SELECT id FROM countries WHERE iso2 = 'PK' LIMIT 1")->fetchColumn();
        $inId = $this->db->query("SELECT id FROM countries WHERE iso2 = 'IN' LIMIT 1")->fetchColumn();

        if (!$pkId) throw new \Exception("Pakistan country record not found in seed database.");
        if (!$inId) throw new \Exception("India country record not found in seed database.");

        $punjabPkId = $this->db->query("SELECT id FROM states WHERE country_id = {$pkId} AND name LIKE '%Punjab%' LIMIT 1")->fetchColumn();
        $sindhPkId = $this->db->query("SELECT id FROM states WHERE country_id = {$pkId} AND name LIKE '%Sindh%' LIMIT 1")->fetchColumn();
        $islamabadPkId = $this->db->query("SELECT id FROM states WHERE country_id = {$pkId} AND name LIKE '%Islamabad%' LIMIT 1")->fetchColumn();
        $balochistanPkId = $this->db->query("SELECT id FROM states WHERE country_id = {$pkId} AND name LIKE '%Balochistan%' LIMIT 1")->fetchColumn();

        // Setup Indian Punjab State
        $stmtPunjabIn = $this->db->prepare("INSERT INTO states (country_id, name, created_at, updated_at) VALUES (:cid, 'Punjab', NOW(), NOW())");
        $stmtPunjabIn->execute(['cid' => $inId]);
        $punjabInId = $this->db->lastInsertId();

        // 1. Institution A: Test Lahore University (Punjab Specific PK)
        $this->db->prepare("INSERT INTO institutions (name, institution_type, country_id, state_id, coverage_type, status) VALUES ('Test Lahore University', 'university', :cid, :sid, 'state', 'approved')")
            ->execute(['cid' => $pkId, 'sid' => $punjabPkId]);
        $lahoreUniId = $this->db->lastInsertId();
        $this->db->prepare("INSERT INTO institution_states (institution_id, state_id) VALUES (:inst_id, :sid)")
            ->execute(['inst_id' => $lahoreUniId, 'sid' => $punjabPkId]);

        // 2. Institution B: Test Karachi University (Sindh Specific PK)
        $this->db->prepare("INSERT INTO institutions (name, institution_type, country_id, state_id, coverage_type, status) VALUES ('Test Karachi University', 'university', :cid, :sid, 'state', 'approved')")
            ->execute(['cid' => $pkId, 'sid' => $sindhPkId]);
        $karachiUniId = $this->db->lastInsertId();
        $this->db->prepare("INSERT INTO institution_states (institution_id, state_id) VALUES (:inst_id, :sid)")
            ->execute(['inst_id' => $karachiUniId, 'sid' => $sindhPkId]);

        // 3. Institution C: Test National Virtual University (National PK)
        $this->db->prepare("INSERT INTO institutions (name, institution_type, country_id, coverage_type, status) VALUES ('Test National Virtual University', 'university', :cid, 'national', 'approved')")
            ->execute(['cid' => $pkId]);

        // 4. Institution D: Test MultiState University X (Punjab, Sindh, Islamabad PK)
        $this->db->prepare("INSERT INTO institutions (name, institution_type, country_id, coverage_type, status) VALUES ('Test MultiState University X', 'university', :cid, 'multi_state', 'approved')")
            ->execute(['cid' => $pkId]);
        $multiUniId = $this->db->lastInsertId();
        
        $stmtRel = $this->db->prepare("INSERT INTO institution_states (institution_id, state_id) VALUES (:inst_id, :sid)");
        $stmtRel->execute(['inst_id' => $multiUniId, 'sid' => $punjabPkId]);
        $stmtRel->execute(['inst_id' => $multiUniId, 'sid' => $sindhPkId]);
        $stmtRel->execute(['inst_id' => $multiUniId, 'sid' => $islamabadPkId]);

        // 5. Institution E: Test Indian Punjab University (Punjab IN)
        $this->db->prepare("INSERT INTO institutions (name, institution_type, country_id, state_id, coverage_type, status) VALUES ('Test Indian Punjab University', 'university', :cid, :sid, 'state', 'approved')")
            ->execute(['cid' => $inId, 'sid' => $punjabInId]);
        $indianPunjabUniId = $this->db->lastInsertId();
        $this->db->prepare("INSERT INTO institution_states (institution_id, state_id) VALUES (:inst_id, :sid)")
            ->execute(['inst_id' => $indianPunjabUniId, 'sid' => $punjabInId]);

        // Save variables for test use
        $this->pkId = $pkId;
        $this->inId = $inId;
        $this->punjabPkId = $punjabPkId;
        $this->sindhPkId = $sindhPkId;
        $this->islamabadPkId = $islamabadPkId;
        $this->balochistanPkId = $balochistanPkId;
        $this->punjabInId = $punjabInId;
    }

    private function queryUniversities(int $countryId, int $stateId, string $search = ''): array {
        $query = "SELECT DISTINCT i.id, i.name 
                  FROM institutions i 
                  WHERE i.institution_type = 'university' 
                    AND i.status = 'approved' 
                    AND i.country_id = :country_id 
                    AND (
                        i.coverage_type = 'national'
                        OR EXISTS (
                            SELECT 1 
                            FROM institution_states ist 
                            WHERE ist.institution_id = i.id 
                              AND ist.state_id = :state_id
                        )
                    )";
        $params = [
            'country_id' => $countryId,
            'state_id' => $stateId
        ];
        if ($search !== '') {
            $query .= " AND i.name LIKE :search";
            $params['search'] = '%' . $search . '%';
        }
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_COLUMN, 1);
    }

    private function runTestCases(): void {
        // TEST 1: Country = Pakistan, State = Punjab PK
        $unis1 = $this->queryUniversities($this->pkId, $this->punjabPkId);
        $this->assertContains('Test Lahore University', $unis1, "Punjab specific PK university should appear.");
        $this->assertContains('Test National Virtual University', $unis1, "National PK university should appear.");
        $this->assertContains('Test MultiState University X', $unis1, "Multi-state PK university should appear in Punjab.");
        $this->assertNotContains('Test Karachi University', $unis1, "Sindh specific PK university should not appear.");
        $this->assertNotContains('Test Indian Punjab University', $unis1, "Indian university should not appear in Pakistan search.");
        echo "✔ TEST 1: Country = Pakistan, State = Punjab passed.\n";

        // TEST 2: Country = Pakistan, State = Sindh PK
        $unis2 = $this->queryUniversities($this->pkId, $this->sindhPkId);
        $this->assertContains('Test Karachi University', $unis2, "Sindh specific PK university should appear.");
        $this->assertContains('Test National Virtual University', $unis2, "National PK university should appear.");
        $this->assertContains('Test MultiState University X', $unis2, "Multi-state PK university should appear in Sindh.");
        $this->assertNotContains('Test Lahore University', $unis2, "Punjab specific PK university should not appear.");
        echo "✔ TEST 2: Country = Pakistan, State = Sindh passed.\n";

        // TEST 3: Virtual University of Pakistan (represented as Test National Virtual University)
        // Check presence in Balochistan PK (where no explicit campuses/states are mapped)
        $unis3 = $this->queryUniversities($this->pkId, $this->balochistanPkId);
        $this->assertContains('Test National Virtual University', $unis3, "National university must appear in all states of Pakistan.");
        $this->assertNotContains('Test Lahore University', $unis3, "Punjab specific PK university should not appear in Balochistan.");
        $this->assertNotContains('Test MultiState University X', $unis3, "Multi-state university without Balochistan should not appear.");
        echo "✔ TEST 3: National university appears in all states passed.\n";

        // TEST 4: Punjab-only university (Test Lahore University)
        $unisPunjabOnly = $this->queryUniversities($this->pkId, $this->punjabPkId);
        $unisSindhOnly = $this->queryUniversities($this->pkId, $this->sindhPkId);
        $this->assertContains('Test Lahore University', $unisPunjabOnly);
        $this->assertNotContains('Test Lahore University', $unisSindhOnly);
        echo "✔ TEST 4: State-specific university constraint passed.\n";

        // TEST 5: Multi-state university (Test MultiState University X)
        // Mapped to Punjab, Sindh, Islamabad. Not Balochistan.
        $unisMultPunjab = $this->queryUniversities($this->pkId, $this->punjabPkId);
        $unisMultSindh = $this->queryUniversities($this->pkId, $this->sindhPkId);
        $unisMultIslamabad = $this->queryUniversities($this->pkId, $this->islamabadPkId);
        $unisMultBalochistan = $this->queryUniversities($this->pkId, $this->balochistanPkId);
        $this->assertContains('Test MultiState University X', $unisMultPunjab);
        $this->assertContains('Test MultiState University X', $unisMultSindh);
        $this->assertContains('Test MultiState University X', $unisMultIslamabad);
        $this->assertNotContains('Test MultiState University X', $unisMultBalochistan);
        echo "✔ TEST 5: Multi-state university boundaries passed.\n";

        // TEST 6: Country = India, State = Punjab IN
        $unisIndia = $this->queryUniversities($this->inId, $this->punjabInId);
        $this->assertContains('Test Indian Punjab University', $unisIndia, "Indian Punjab university should appear.");
        $this->assertNotContains('Test Lahore University', $unisIndia, "Pakistani Punjab university should not appear in India search.");
        $this->assertNotContains('Test National Virtual University', $unisIndia, "Pakistani national university should not appear in India.");
        echo "✔ TEST 6: Country boundaries respected passed.\n";

        // TEST 7: Search: "Virtual"
        $unisSearchVirt = $this->queryUniversities($this->pkId, $this->punjabPkId, 'Virtual');
        $this->assertContains('Test National Virtual University', $unisSearchVirt);
        $this->assertNotContains('Test Lahore University', $unisSearchVirt, "Search filter should exclude unrelated universities.");
        echo "✔ TEST 7: Search query filtering passed.\n";
    }

    private function assertContains(string $needle, array $haystack, string $msg = ''): void {
        if (!in_array($needle, $haystack)) {
            throw new \Exception("Assertion Failed: Array does not contain '$needle'. $msg");
        }
    }

    private function assertNotContains(string $needle, array $haystack, string $msg = ''): void {
        if (in_array($needle, $haystack)) {
            throw new \Exception("Assertion Failed: Array contains '$needle' but it should not. $msg");
        }
    }
}
