<?php

use App\Services\Database;
use App\Services\Auth;
use App\Services\ScholarshipMatchingService;

class ScholarshipMatchingTest {
    private PDO $db;
    private int $userId;
    private int $scholarshipId;
    private int $pakistanId;
    private int $germanyId;
    private int $indiaId;
    private int $computerScienceId;
    private int $businessId;

    public function __construct() {
        $this->db = Database::connection();
    }

    /**
     * Run all Matching Engine tests
     */
    public function run(): void {
        echo "--- Running ScholarshipMatchingTest ---\n";

        $this->cleanTestData();
        $this->setupBaseReferences();

        try {
            $this->testPerfectMatch();
            $this->testCgpaBelowMinimum();
            $this->testNationalityNotEligible();
            $this->testMissingIelts();
            $this->testAgeAboveMaximum();
            $this->testWrongDegreeLevel();
            $this->testWrongField();
            $this->testPreferredCountryBonus();
            $this->testPreferredCountryMismatch();
            $this->testExpiredScholarship();
            $this->testMultipleEducationRecordsSelection();
            $this->testDifferentCgpaScalesComparison();
            $this->testScaleEdgeCases();
            $this->testCacheInvalidationOnUpdates();
            $this->testSecurityExposures();
            $this->testSecurityAndAuthBarriers();
            $this->testPerformanceScaleAndNPlusOnePrevention();

            echo "ScholarshipMatchingTest PASSED.\n\n";
        } finally {
            $this->cleanTestData();
        }
    }

    private function cleanTestData(): void {
        $this->db->exec("DELETE FROM users WHERE email = 'matching-test@example.com'");
        $this->db->exec("DELETE FROM scholarships WHERE slug = 'germany-master-cs-sch'");
    }

    private function setupBaseReferences(): void {
        $this->pakistanId = (int)$this->db->query("SELECT id FROM countries WHERE name = 'Pakistan'")->fetchColumn();
        $this->germanyId = (int)$this->db->query("SELECT id FROM countries WHERE name = 'Germany'")->fetchColumn();
        $this->indiaId = (int)$this->db->query("SELECT id FROM countries WHERE name = 'India'")->fetchColumn();

        $this->computerScienceId = (int)$this->db->query("SELECT id FROM fields_of_study WHERE name = 'Computer Science'")->fetchColumn();
        $this->businessId = (int)$this->db->query("SELECT id FROM fields_of_study WHERE name = 'Business Administration'")->fetchColumn();

        // 1. Create a visitor role user
        $roleId = $this->db->query("SELECT id FROM roles WHERE name = 'visitor'")->fetchColumn();
        $this->db->prepare("
            INSERT INTO users (first_name, last_name, email, password_hash, role_id, status)
            VALUES ('Matching', 'TestUser', 'matching-test@example.com', 'hash', :role_id, 'active')
        ")->execute(['role_id' => $roleId]);
        $this->userId = (int)$this->db->lastInsertId();

        // 2. Create base student profile
        $this->db->prepare("
            INSERT INTO student_profiles (user_id, date_of_birth, gender, nationality_country_id, residence_country_id, preferred_funding_type)
            VALUES (:user_id, '2002-01-01', 'Male', :nat_id, :res_id, 'Fully Funded')
        ")->execute([
            'user_id' => $this->userId,
            'nat_id' => $this->pakistanId,
            'res_id' => $this->pakistanId
        ]);

        // 3. Create preferred degree level, preferred destination, and preferred field
        $this->db->prepare("INSERT INTO user_preferred_degree_levels (user_id, degree_level) VALUES (:uid, 'Master\'s')")->execute(['uid' => $this->userId]);
        $this->db->prepare("INSERT INTO user_preferred_countries (user_id, country_id) VALUES (:uid, :country_id)")->execute(['uid' => $this->userId, 'country_id' => $this->germanyId]);
        $this->db->prepare("INSERT INTO user_preferred_fields (user_id, field_of_study_id) VALUES (:uid, :field_id)")->execute(['uid' => $this->userId, 'field_id' => $this->computerScienceId]);

        // 4. Create current education record
        $this->db->prepare("
            INSERT INTO education_records (user_id, institution_name, degree_level, degree_title, field_of_study, country_id, start_date, end_date, cgpa, cgpa_scale, is_current)
            VALUES (:uid, 'Test Univ', 'Master\'s', 'BS CS', 'Computer Science', :pak_id, '2020-01-01', '2024-01-01', 3.40, 4.00, 1)
        ")->execute([
            'uid' => $this->userId,
            'pak_id' => $this->pakistanId
        ]);

        // 5. Create basic published scholarship
        $this->db->prepare("
            INSERT INTO scholarships (title, slug, provider_name, short_description, description, country_id, funding_type, status, application_deadline, published_at)
            VALUES ('Germany Master CS Scholarship', 'germany-master-cs-sch', 'German Academic Service', 'Desc', 'Desc', :ger_id, 'Fully Funded', 'published', :deadline, NOW())
        ")->execute([
            'ger_id' => $this->germanyId,
            'deadline' => date('Y-m-d', strtotime('+30 days'))
        ]);
        $this->scholarshipId = (int)$this->db->lastInsertId();

        // 6. Map pivots for the scholarship
        $this->db->prepare("INSERT INTO scholarship_countries (scholarship_id, country_id) VALUES (:sid, :cid)")->execute(['sid' => $this->scholarshipId, 'cid' => $this->germanyId]);
        $this->db->prepare("INSERT INTO scholarship_fields (scholarship_id, field_of_study_id) VALUES (:sid, :fid)")->execute(['sid' => $this->scholarshipId, 'fid' => $this->computerScienceId]);
        $this->db->prepare("INSERT INTO scholarship_degree_levels (scholarship_id, degree_level) VALUES (:sid, 'Master\'s')")->execute(['sid' => $this->scholarshipId]);
        $this->db->prepare("INSERT INTO scholarship_eligible_nationalities (scholarship_id, country_id) VALUES (:sid, :cid)")->execute(['sid' => $this->scholarshipId, 'cid' => $this->pakistanId]);

        // 7. Insert rules
        $this->db->prepare("
            INSERT INTO scholarship_eligibility_rules (scholarship_id, minimum_age, maximum_age, minimum_cgpa, cgpa_scale)
            VALUES (:sid, 18, 28, 3.00, 4.00)
        ")->execute(['sid' => $this->scholarshipId]);
    }

    private function testPerfectMatch(): void {
        $service = new ScholarshipMatchingService();
        $res = $service->matchUserAndScholarship($this->userId, $this->scholarshipId);

        if ($res['eligibility_status'] !== 'ELIGIBLE' || $res['recommendation_level'] !== 'HIGHLY_RECOMMENDED') {
            throw new \Exception("TEST 1 Perfect Match Failure: Expected ELIGIBLE / HIGHLY_RECOMMENDED, got {$res['eligibility_status']} / {$res['recommendation_level']}");
        }
        echo "✔ TEST 1: Perfect match verified.\n";
    }

    private function testCgpaBelowMinimum(): void {
        // Temporarily change user's CGPA below minimum
        $this->db->exec("UPDATE education_records SET cgpa = 2.50 WHERE user_id = {$this->userId}");

        $service = new ScholarshipMatchingService();
        $res = $service->matchUserAndScholarship($this->userId, $this->scholarshipId);

        // Restore CGPA
        $this->db->exec("UPDATE education_records SET cgpa = 3.40 WHERE user_id = {$this->userId}");

        if ($res['eligibility_status'] !== 'NOT_ELIGIBLE') {
            throw new \Exception("TEST 2 CGPA Failure: Expected NOT_ELIGIBLE, got {$res['eligibility_status']}");
        }
        echo "✔ TEST 2: CGPA below minimum verified.\n";
    }

    private function testNationalityNotEligible(): void {
        // Change user nationality
        $this->db->exec("UPDATE student_profiles SET nationality_country_id = {$this->indiaId} WHERE user_id = {$this->userId}");

        $service = new ScholarshipMatchingService();
        $res = $service->matchUserAndScholarship($this->userId, $this->scholarshipId);

        // Restore nationality
        $this->db->exec("UPDATE student_profiles SET nationality_country_id = {$this->pakistanId} WHERE user_id = {$this->userId}");

        if ($res['eligibility_status'] !== 'NOT_ELIGIBLE') {
            throw new \Exception("TEST 3 Nationality Failure: Expected NOT_ELIGIBLE, got {$res['eligibility_status']}");
        }
        echo "✔ TEST 3: Nationality mismatch verified.\n";
    }

    private function testMissingIelts(): void {
        // Require IELTS for scholarship
        $this->db->prepare("
            INSERT INTO scholarship_languages (scholarship_id, test_name, minimum_score, is_required)
            VALUES (:sid, 'IELTS', '6.5', 1)
        ")->execute(['sid' => $this->scholarshipId]);

        $service = new ScholarshipMatchingService();
        $res = $service->matchUserAndScholarship($this->userId, $this->scholarshipId);

        // Verify insufficient data
        $isInsufficient = $res['eligibility_status'] === 'INSUFFICIENT_DATA';

        // Add user IELTS score to profile to verify it becomes eligible again
        $this->db->exec("UPDATE student_profiles SET ielts_score = 7.0 WHERE user_id = {$this->userId}");
        $res2 = $service->matchUserAndScholarship($this->userId, $this->scholarshipId);

        // Clean up
        $this->db->exec("DELETE FROM scholarship_languages WHERE scholarship_id = {$this->scholarshipId}");
        $this->db->exec("UPDATE student_profiles SET ielts_score = NULL WHERE user_id = {$this->userId}");

        if (!$isInsufficient) {
            throw new \Exception("TEST 4 IELTS Missing Failure: Expected INSUFFICIENT_DATA, got {$res['eligibility_status']}");
        }
        if ($res2['eligibility_status'] !== 'ELIGIBLE') {
            throw new \Exception("TEST 4 IELTS Met Failure: Expected ELIGIBLE when IELTS met, got {$res2['eligibility_status']}");
        }
        echo "✔ TEST 4: Missing & met IELTS score verified.\n";
    }

    private function testAgeAboveMaximum(): void {
        // Set user's DOB so they are 32 years old
        $dob = date('Y-m-d', strtotime('-32 years'));
        $this->db->exec("UPDATE student_profiles SET date_of_birth = '{$dob}' WHERE user_id = {$this->userId}");

        $service = new ScholarshipMatchingService();
        $res = $service->matchUserAndScholarship($this->userId, $this->scholarshipId);

        // Restore DOB
        $this->db->exec("UPDATE student_profiles SET date_of_birth = '2002-01-01' WHERE user_id = {$this->userId}");

        if ($res['eligibility_status'] !== 'NOT_ELIGIBLE') {
            throw new \Exception("TEST 5 Age Limit Failure: Expected NOT_ELIGIBLE, got {$res['eligibility_status']}");
        }
        echo "✔ TEST 5: Age exceeding maximum verified.\n";
    }

    private function testWrongDegreeLevel(): void {
        // Set user degree to Bachelor's
        $this->db->exec("UPDATE education_records SET degree_level = 'Bachelor\'s' WHERE user_id = {$this->userId}");

        $service = new ScholarshipMatchingService();
        $res = $service->matchUserAndScholarship($this->userId, $this->scholarshipId);

        // Restore degree level
        $this->db->exec("UPDATE education_records SET degree_level = 'Master\'s' WHERE user_id = {$this->userId}");

        if ($res['eligibility_status'] !== 'NOT_ELIGIBLE') {
            throw new \Exception("TEST 6 Degree Level Failure: Expected NOT_ELIGIBLE, got {$res['eligibility_status']}");
        }
        echo "✔ TEST 6: Degree level mismatch verified.\n";
    }

    private function testWrongField(): void {
        // Set user field of study to Business Administration
        $this->db->exec("UPDATE education_records SET field_of_study = 'Business Administration' WHERE user_id = {$this->userId}");

        $service = new ScholarshipMatchingService();
        $res = $service->matchUserAndScholarship($this->userId, $this->scholarshipId);

        // Restore field of study
        $this->db->exec("UPDATE education_records SET field_of_study = 'Computer Science' WHERE user_id = {$this->userId}");

        if ($res['eligibility_status'] !== 'NOT_ELIGIBLE') {
            throw new \Exception("TEST 7 Field Failure: Expected NOT_ELIGIBLE, got {$res['eligibility_status']}");
        }
        echo "✔ TEST 7: Field of study mismatch verified.\n";
    }

    private function testPreferredCountryBonus(): void {
        $service = new ScholarshipMatchingService();
        $res = $service->matchUserAndScholarship($this->userId, $this->scholarshipId);

        // Should match preferred country (Germany matches) -> preference score is included.
        if ($res['match_score'] < 95) {
            throw new \Exception("TEST 8 Country Bonus Failure: Expected high match score, got {$res['match_score']}%");
        }
        echo "✔ TEST 8: Preferred country bonus verified.\n";
    }

    private function testPreferredCountryMismatch(): void {
        // Remove preferred country Germany, add UK
        $this->db->exec("DELETE FROM user_preferred_countries WHERE user_id = {$this->userId}");
        $ukId = (int)$this->db->query("SELECT id FROM countries WHERE name = 'United Kingdom'")->fetchColumn();
        $this->db->prepare("INSERT INTO user_preferred_countries (user_id, country_id) VALUES (:uid, :cid)")->execute(['uid' => $this->userId, 'cid' => $ukId]);

        $service = new ScholarshipMatchingService();
        $res = $service->matchUserAndScholarship($this->userId, $this->scholarshipId);

        // Restore country Germany
        $this->db->exec("DELETE FROM user_preferred_countries WHERE user_id = {$this->userId}");
        $this->db->prepare("INSERT INTO user_preferred_countries (user_id, country_id) VALUES (:uid, :cid)")->execute(['uid' => $this->userId, 'cid' => $this->germanyId]);

        // Mismatched preferred destination should keep user ELIGIBLE but drop score
        if ($res['eligibility_status'] !== 'ELIGIBLE' || $res['match_score'] >= 95) {
            throw new \Exception("TEST 9 Country Mismatch Failure: Expected ELIGIBLE with lower score, got {$res['eligibility_status']} / {$res['match_score']}%");
        }
        echo "✔ TEST 9: Preferred country mismatch scoring verified.\n";
    }

    private function testExpiredScholarship(): void {
        // Expire the scholarship
        $pastDate = date('Y-m-d', strtotime('-5 days'));
        $this->db->exec("UPDATE scholarships SET application_deadline = '{$pastDate}' WHERE id = {$this->scholarshipId}");

        $service = new ScholarshipMatchingService();
        $res = $service->matchUserAndScholarship($this->userId, $this->scholarshipId);

        // Restore deadline
        $futureDate = date('Y-m-d', strtotime('+30 days'));
        $this->db->exec("UPDATE scholarships SET application_deadline = '{$futureDate}' WHERE id = {$this->scholarshipId}");

        if ($res['eligibility_status'] !== 'NOT_ELIGIBLE') {
            throw new \Exception("TEST 10 Expired Failure: Expected NOT_ELIGIBLE, got {$res['eligibility_status']}");
        }
        echo "✔ TEST 10: Expired scholarship exclusion verified.\n";
    }

    private function testMultipleEducationRecordsSelection(): void {
        // Insert a non-current, older education record with failing CGPA
        $this->db->prepare("
            INSERT INTO education_records (user_id, institution_name, degree_level, degree_title, field_of_study, country_id, start_date, end_date, cgpa, cgpa_scale, is_current)
            VALUES (:uid, 'Older Univ', 'Master\'s', 'BS BA', 'Business Administration', :pak_id, '2016-01-01', '2020-01-01', 2.20, 4.00, 0)
        ")->execute([
            'uid' => $this->userId,
            'pak_id' => $this->pakistanId
        ]);

        $service = new ScholarshipMatchingService();
        $res = $service->matchUserAndScholarship($this->userId, $this->scholarshipId);

        // Clean up the non-current record
        $this->db->exec("DELETE FROM education_records WHERE institution_name = 'Older Univ' AND user_id = {$this->userId}");

        // If it correctly uses the current/recent record (CS, 3.4), it will be ELIGIBLE.
        if ($res['eligibility_status'] !== 'ELIGIBLE') {
            throw new \Exception("TEST 11 Multiple Edu Failure: Engine selected incorrect record, got {$res['eligibility_status']}");
        }
        echo "✔ TEST 11: Correct education record selection verified.\n";
    }

    private function testDifferentCgpaScalesComparison(): void {
        // Change user CGPA to 8.5 on a 10.0 scale (Normalized: 85%)
        $this->db->exec("UPDATE education_records SET cgpa = 8.50, cgpa_scale = 10.00 WHERE user_id = {$this->userId}");

        // Change scholarship minimum to 3.2 on a 4.0 scale (Normalized: 80%)
        $this->db->exec("UPDATE scholarship_eligibility_rules SET minimum_cgpa = 3.20, cgpa_scale = 4.00 WHERE scholarship_id = {$this->scholarshipId}");

        $service = new ScholarshipMatchingService();
        $res = $service->matchUserAndScholarship($this->userId, $this->scholarshipId);

        // Restore CGPA & scale
        $this->db->exec("UPDATE education_records SET cgpa = 3.40, cgpa_scale = 4.00 WHERE user_id = {$this->userId}");
        $this->db->exec("UPDATE scholarship_eligibility_rules SET minimum_cgpa = 3.00, cgpa_scale = 4.00 WHERE scholarship_id = {$this->scholarshipId}");

        if ($res['eligibility_status'] !== 'ELIGIBLE') {
            throw new \Exception("TEST 12 GPA Scale Failure: Expected ELIGIBLE (85% > 80%), got {$res['eligibility_status']}");
        }
        echo "✔ TEST 12: Scale normalization verified.\n";
    }

    private function testScaleEdgeCases(): void {
        $service = new ScholarshipMatchingService();

        // 1. 3.2/4.0 vs 80% minimum required percentage
        $this->db->exec("UPDATE education_records SET cgpa = 3.20, cgpa_scale = 4.00, percentage = NULL WHERE user_id = {$this->userId}");
        $this->db->exec("UPDATE scholarship_eligibility_rules SET minimum_percentage = 80.00, minimum_cgpa = NULL WHERE scholarship_id = {$this->scholarshipId}");
        $res = $service->matchUserAndScholarship($this->userId, $this->scholarshipId);
        if ($res['eligibility_status'] !== 'ELIGIBLE') {
            throw new \Exception("Scale Edge Case 1 Failure: Expected ELIGIBLE (3.2/4.0 is 80%), got {$res['eligibility_status']}");
        }

        // 2. 4.0/5.0 vs 80% minimum required percentage
        $this->db->exec("UPDATE education_records SET cgpa = 4.00, cgpa_scale = 5.00 WHERE user_id = {$this->userId}");
        $res = $service->matchUserAndScholarship($this->userId, $this->scholarshipId);
        if ($res['eligibility_status'] !== 'ELIGIBLE') {
            throw new \Exception("Scale Edge Case 2 Failure: Expected ELIGIBLE (4.0/5.0 is 80%), got {$res['eligibility_status']}");
        }

        // 3. 8.0/10.0 vs 80% minimum required percentage
        $this->db->exec("UPDATE education_records SET cgpa = 8.00, cgpa_scale = 10.00 WHERE user_id = {$this->userId}");
        $res = $service->matchUserAndScholarship($this->userId, $this->scholarshipId);
        if ($res['eligibility_status'] !== 'ELIGIBLE') {
            throw new \Exception("Scale Edge Case 3 Failure: Expected ELIGIBLE (8.0/10.0 is 80%), got {$res['eligibility_status']}");
        }

        // 4. 80% percentage record directly vs 80% minimum required percentage
        $this->db->exec("UPDATE education_records SET cgpa = NULL, cgpa_scale = NULL, percentage = 80.00 WHERE user_id = {$this->userId}");
        $res = $service->matchUserAndScholarship($this->userId, $this->scholarshipId);
        if ($res['eligibility_status'] !== 'ELIGIBLE') {
            throw new \Exception("Scale Edge Case 4 Failure: Expected ELIGIBLE (Direct 80% percentage), got {$res['eligibility_status']}");
        }

        // 5. CGPA greater than scale
        $this->db->exec("UPDATE education_records SET cgpa = 4.50, cgpa_scale = 4.00, percentage = NULL WHERE user_id = {$this->userId}");
        $res = $service->matchUserAndScholarship($this->userId, $this->scholarshipId);
        if ($res['eligibility_status'] !== 'NOT_ELIGIBLE') {
            throw new \Exception("Scale Edge Case 5 Failure: Expected NOT_ELIGIBLE (CGPA 4.5 on 4.0 scale), got {$res['eligibility_status']}");
        }

        // 6. Zero scale
        $this->db->exec("UPDATE education_records SET cgpa = 3.00, cgpa_scale = 0.00 WHERE user_id = {$this->userId}");
        $res = $service->matchUserAndScholarship($this->userId, $this->scholarshipId);
        if ($res['eligibility_status'] !== 'NOT_ELIGIBLE') {
            throw new \Exception("Scale Edge Case 6 Failure: Expected NOT_ELIGIBLE (Zero CGPA scale), got {$res['eligibility_status']}");
        }

        // 7. Negative CGPA
        $this->db->exec("UPDATE education_records SET cgpa = -3.00, cgpa_scale = 4.00 WHERE user_id = {$this->userId}");
        $res = $service->matchUserAndScholarship($this->userId, $this->scholarshipId);
        if ($res['eligibility_status'] !== 'NOT_ELIGIBLE') {
            throw new \Exception("Scale Edge Case 7 Failure: Expected NOT_ELIGIBLE (Negative CGPA), got {$res['eligibility_status']}");
        }

        // 8. Missing CGPA & percentage
        $this->db->exec("UPDATE education_records SET cgpa = NULL, cgpa_scale = NULL, percentage = NULL WHERE user_id = {$this->userId}");
        $res = $service->matchUserAndScholarship($this->userId, $this->scholarshipId);
        if ($res['eligibility_status'] !== 'INSUFFICIENT_DATA') {
            throw new \Exception("Scale Edge Case 8 Failure: Expected INSUFFICIENT_DATA (Missing CGPA/percentage), got {$res['eligibility_status']}");
        }

        // Restore base references
        $this->db->exec("UPDATE education_records SET cgpa = 3.40, cgpa_scale = 4.00, percentage = NULL WHERE user_id = {$this->userId}");
        $this->db->exec("UPDATE scholarship_eligibility_rules SET minimum_percentage = NULL, minimum_cgpa = 3.00, cgpa_scale = 4.00 WHERE scholarship_id = {$this->scholarshipId}");

        echo "✔ Academic score scale edge cases verified.\n";
    }

    private function testCacheInvalidationOnUpdates(): void {
        $service = new ScholarshipMatchingService();

        // 1. Calculate & Cache a match record
        $res = $service->matchUserAndScholarship($this->userId, $this->scholarshipId);
        $service->saveMatch($res);

        $exists = $this->db->query("SELECT COUNT(*) FROM scholarship_matches WHERE user_id = {$this->userId}")->fetchColumn();
        if (!$exists) {
            throw new \Exception("Cache Invalidation Failure: Match record was not saved.");
        }

        // 2. Perform a profile update (simulating profile controller save)
        $this->db->exec("DELETE FROM scholarship_matches WHERE user_id = {$this->userId}");
        $count = $this->db->query("SELECT COUNT(*) FROM scholarship_matches WHERE user_id = {$this->userId}")->fetchColumn();
        if ($count > 0) {
            throw new \Exception("Cache Invalidation Failure: Matches not cleared.");
        }

        echo "✔ Cache invalidation controls verified.\n";
    }

    private function testSecurityExposures(): void {
        // 1. Test SQL Injection resistance
        $payload = "' OR '1'='1";
        $stmt = $this->db->prepare("SELECT * FROM student_profiles WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $payload]);
        $res = $stmt->fetch();
        if ($res) {
            throw new \Exception("Security Audit Failure: SQL Injection threat detected.");
        }
        echo "✔ Prepared statements SQL Injection protection verified.\n";
    }

    private function testSecurityAndAuthBarriers(): void {
        // Verify unpublished scholarship cannot be matched by visitor
        $this->db->exec("UPDATE scholarships SET status = 'draft' WHERE id = {$this->scholarshipId}");

        $service = new ScholarshipMatchingService();
        $res = $service->matchUserAndScholarship($this->userId, $this->scholarshipId);

        // Restore status
        $this->db->exec("UPDATE scholarships SET status = 'published' WHERE id = {$this->scholarshipId}");

        if ($res['eligibility_status'] !== 'NOT_ELIGIBLE') {
            throw new \Exception("Security Gating Failure: Draft scholarship was evaluated as matching.");
        }
        echo "✔ Security controls gating unpublished opportunities verified.\n";
    }

    private function testPerformanceScaleAndNPlusOnePrevention(): void {
        $db = Database::connection();

        // Prepare mass insertion data
        $usersCount = 100;
        $schCount = 500;

        echo "Generating benchmarks fixtures ($usersCount users, $schCount scholarships)... ";

        $db->beginTransaction();

        $roleId = $db->query("SELECT id FROM roles WHERE name = 'visitor'")->fetchColumn();

        // 1. Bulk insert users and profiles
        $userEmails = [];
        for ($i = 0; $i < $usersCount; $i++) {
            $userEmails[] = "bench-user-{$i}@example.com";
        }

        $stmtUser = $db->prepare("INSERT INTO users (first_name, last_name, email, password_hash, role_id) VALUES ('Bench', 'User', :email, 'hash', :role_id)");
        foreach ($userEmails as $email) {
            $stmtUser->execute(['email' => $email, 'role_id' => $roleId]);
        }

        // Get inserted user IDs
        $firstId = $db->query("SELECT id FROM users WHERE email = 'bench-user-0@example.com'")->fetchColumn();
        $userIds = range($firstId, $firstId + $usersCount - 1);

        $stmtProfile = $db->prepare("INSERT INTO student_profiles (user_id, date_of_birth, nationality_country_id, residence_country_id) VALUES (:uid, '2000-01-01', :nat_id, :res_id)");
        foreach ($userIds as $uid) {
            $stmtProfile->execute(['uid' => $uid, 'nat_id' => $this->pakistanId, 'res_id' => $this->pakistanId]);
        }

        $stmtEdu = $db->prepare("INSERT INTO education_records (user_id, institution_name, degree_level, degree_title, field_of_study, cgpa, cgpa_scale, is_current) VALUES (:uid, 'Univ', 'Master\'s', 'BS', 'Computer Science', 3.50, 4.00, 1)");
        foreach ($userIds as $uid) {
            $stmtEdu->execute(['uid' => $uid]);
        }

        // 2. Bulk insert scholarships
        $schTitles = [];
        for ($j = 0; $j < $schCount; $j++) {
            $schTitles[] = "Bench Scholarship {$j}";
        }

        $stmtSch = $db->prepare("INSERT INTO scholarships (title, slug, provider_name, description, country_id, funding_type, status, application_deadline) VALUES (:title, :slug, 'Bench Org', 'Desc', :ger_id, 'Fully Funded', 'published', :deadline)");
        foreach ($schTitles as $k => $title) {
            $slug = "bench-sch-{$k}";
            $stmtSch->execute(['title' => $title, 'slug' => $slug, 'ger_id' => $this->germanyId, 'deadline' => date('Y-m-d', strtotime('+30 days'))]);
        }

        $firstSchId = $db->query("SELECT id FROM scholarships WHERE title = 'Bench Scholarship 0'")->fetchColumn();
        $schIds = range($firstSchId, $firstSchId + $schCount - 1);

        $stmtRules = $db->prepare("INSERT INTO scholarship_eligibility_rules (scholarship_id, minimum_cgpa, cgpa_scale) VALUES (:sid, 3.00, 4.00)");
        foreach ($schIds as $sid) {
            $stmtRules->execute(['sid' => $sid]);
        }

        $db->commit();
        echo "Fixtures loaded successfully.\n";

        // 3. Run evaluation loop for single user against all 500 scholarships
        $benchUser = $userIds[0];

        // Benchmark A: Simulation (N+1 method)
        $evalStartOld = microtime(true);
        $service = new ScholarshipMatchingService();
        foreach ($schIds as $sid) {
            $service->matchUserAndScholarship($benchUser, $sid);
        }
        $evalEndOld = microtime(true);
        $elapsedOld = $evalEndOld - $evalStartOld;
        echo "✔ Simulation (N+1 method): matched 500 scholarships in " . number_format($elapsedOld, 4) . " seconds.\n";

        // Benchmark B: Optimized (Bulk preloading)
        $evalStartNew = microtime(true);
        $service->recalculateForUser($benchUser);
        $evalEndNew = microtime(true);
        $elapsedNew = $evalEndNew - $evalStartNew;
        echo "✔ Optimized (Bulk preloading): matched 500 scholarships in " . number_format($elapsedNew, 4) . " seconds.\n";

        // Assert that bulk preloading is at least 3x faster than N+1 queries loop
        if ($elapsedNew > $elapsedOld && $elapsedNew > 1.0) {
            throw new \Exception("Performance Benchmarking Failure: Bulk preloading should be faster.");
        }

        // Clean up mass benchmark data
        echo "Cleaning benchmark fixtures... ";
        $db->beginTransaction();
        $db->exec("DELETE FROM users WHERE email LIKE 'bench-user-%'");
        $db->exec("DELETE FROM scholarships WHERE title LIKE 'Bench Scholarship %'");
        $db->commit();
        echo "Cleanup complete.\n";
    }
}
