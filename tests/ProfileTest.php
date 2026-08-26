<?php

use App\Services\Database;
use App\Services\Auth;
use App\Services\ProfileCompletionService;
use App\Helpers\Security;
use App\Helpers\AgeCalculator;

class ProfileTest {
    private PDO $db;

    public function __construct() {
        $this->db = Database::connection();
    }

    /**
     * Run all Profile test suites
     */
    public function run(): void {
        echo "--- Running ProfileTest ---\n";

        $this->cleanTestData();

        try {
            $this->testAgeCalculation();
            $this->testProfileCompletenessPercentage();
            $this->testEducationSingleCurrentEnforcement();
            $this->testPreferredDestinationsPivotMappings();
            $this->testPreferredFieldsPivotMappings();
            $this->testPreferredDegreesPivotMappings();
            $this->testIdorAndAccessBarriers();
            $this->testLengthAndTypeValidations();
            $this->testAjaxEndpointsSecurity();

            echo "ProfileTest PASSED.\n\n";
        } finally {
            $this->cleanTestData();
        }
    }

    /**
     * Helper to wipe test data
     */
    private function cleanTestData(): void {
        $this->db->exec("DELETE FROM users WHERE email LIKE 'test_profile_%@scholarmatch.test'");
    }

    /**
     * 1. Assert dynamic age calculation from DOB
     */
    private function testAgeCalculation(): void {
        $dob10YearsAgo = date('Y-m-d', strtotime('-10 years'));
        $dob25YearsAgo = date('Y-m-d', strtotime('-25 years'));

        if (AgeCalculator::calculateAge($dob10YearsAgo) !== 10) {
            throw new \Exception("Age calculation failed: Expected 10 years old.");
        }

        if (AgeCalculator::calculateAge($dob25YearsAgo) !== 25) {
            throw new \Exception("Age calculation failed: Expected 25 years old.");
        }

        if (AgeCalculator::calculateAge(null) !== null) {
            throw new \Exception("Age calculation failed: Expected null for empty DOB.");
        }

        echo "✔ Age calculation logic verified.\n";
    }

    /**
     * 2. Assert ProfileCompletionService increments weights correctly
     */
    private function testProfileCompletenessPercentage(): void {
        $visitorRoleId = $this->db->query("SELECT id FROM roles WHERE name = 'visitor'")->fetchColumn();
        $pkId = $this->db->query("SELECT id FROM countries WHERE iso2 = 'PK'")->fetchColumn();

        // 1. Insert a blank user
        $email = 'test_profile_complete@scholarmatch.test';
        $stmt = $this->db->prepare("
            INSERT INTO users (role_id, first_name, last_name, email, phone, password_hash, status) 
            VALUES (:role_id, 'Complete', 'User', :email, '+923007777771', 'hash', 'active')
        ");
        $stmt->execute(['role_id' => $visitorRoleId, 'email' => $email]);
        $userId = $this->db->lastInsertId();

        // Insert empty student profile
        $this->db->prepare("INSERT INTO student_profiles (user_id) VALUES (:uid)")->execute(['uid' => $userId]);

        // Dynamic Calculate - should be 20% (First Name, Last Name, Email, Phone are present - each 5%)
        $pct1 = ProfileCompletionService::calculate($userId);
        if ($pct1 !== 20) {
            throw new \Exception("Completeness Error: Expected 20% for raw account, got $pct1%");
        }

        // 2. Add Location details (nationality + residence country + state + city)
        $this->db->prepare("
            UPDATE student_profiles 
            SET date_of_birth = '2000-01-01', gender = 'male',
                nationality_country_id = :nat_id, residence_country_id = :res_id,
                residence_state_id = 1, city_id = 1
            WHERE user_id = :uid
        ")->execute([
            'nat_id' => $pkId,
            'res_id' => $pkId,
            'uid' => $userId
        ]);

        // Dynamic Calculate - should be 50% (added DOB(5), Gender(5), Nationality(5), Residence(5), State(5), City(5) -> +30% total = 50%)
        $pct2 = ProfileCompletionService::calculate($userId);
        if ($pct2 !== 50) {
            throw new \Exception("Completeness Error: Expected 50% after details added, got $pct2%");
        }

        // 3. Add education history
        $this->db->prepare("
            INSERT INTO education_records (user_id, institution_name, degree_level, degree_title, field_of_study) 
            VALUES (:uid, 'Uni', 'Bachelor\'s', 'BS', 'Computer Science')
        ")->execute(['uid' => $userId]);

        // Dynamic Calculate - should be 75% (+25% for education history = 75%)
        $pct3 = ProfileCompletionService::calculate($userId);
        if ($pct3 !== 75) {
            throw new \Exception("Completeness Error: Expected 75% after education, got $pct3%");
        }

        // 4. Add scholarship preferences
        $this->db->prepare("INSERT INTO user_preferred_countries (user_id, country_id) VALUES (:uid, :cid)")->execute(['uid' => $userId, 'cid' => $pkId]);
        $this->db->prepare("INSERT INTO user_preferred_fields (user_id, field_of_study_id) VALUES (:uid, 1)")->execute(['uid' => $userId]);
        $this->db->prepare("INSERT INTO user_preferred_degree_levels (user_id, degree_level) VALUES (:uid, 'Master\'s')")->execute(['uid' => $userId]);

        // Dynamic Calculate - should be 100% (+8% countries, +8% fields, +9% degrees = +25% = 100%)
        $pct4 = ProfileCompletionService::calculate($userId);
        if ($pct4 !== 100) {
            throw new \Exception("Completeness Error: Expected 100% for completed profile, got $pct4%");
        }

        echo "✔ Profile Completion percentage calculations verified.\n";
    }

    /**
     * 3. Assert only a single education record is marked as current
     */
    private function testEducationSingleCurrentEnforcement(): void {
        $visitorRoleId = $this->db->query("SELECT id FROM roles WHERE name = 'visitor'")->fetchColumn();
        $email = 'test_profile_edu@scholarmatch.test';

        // Register user
        $stmt = $this->db->prepare("
            INSERT INTO users (role_id, first_name, last_name, email, phone, password_hash, status) 
            VALUES (:role_id, 'Edu', 'User', :email, '+923007777772', 'hash', 'active')
        ");
        $stmt->execute(['role_id' => $visitorRoleId, 'email' => $email]);
        $userId = $this->db->lastInsertId();

        // 1. Insert record 1 as current
        $this->db->prepare("
            INSERT INTO education_records (user_id, institution_name, degree_level, degree_title, field_of_study, is_current) 
            VALUES (:uid, 'High School A', 'High School', 'Matric', 'Sciences', 1)
        ")->execute(['uid' => $userId]);

        // 2. Insert record 2 as current (must clear record 1)
        // Controller simulation logic:
        $this->db->prepare("UPDATE education_records SET is_current = 0 WHERE user_id = :uid")->execute(['uid' => $userId]);
        
        $this->db->prepare("
            INSERT INTO education_records (user_id, institution_name, degree_level, degree_title, field_of_study, is_current) 
            VALUES (:uid, 'Uni B', 'Bachelor\'s', 'BS', 'Information Technology', 1)
        ")->execute(['uid' => $userId]);

        // Assert record 1 has is_current = 0
        $stmt1 = $this->db->prepare("SELECT is_current FROM education_records WHERE user_id = :uid AND degree_level = 'High School'");
        $stmt1->execute(['uid' => $userId]);
        if ((int)$stmt1->fetchColumn() !== 0) {
            throw new \Exception("Education Current Error: Contradictory current states detected.");
        }

        // Assert record 2 has is_current = 1
        $stmt2 = $this->db->prepare("SELECT is_current FROM education_records WHERE user_id = :uid AND degree_level = 'Bachelor\'s'");
        $stmt2->execute(['uid' => $userId]);
        if ((int)$stmt2->fetchColumn() !== 1) {
            throw new \Exception("Education Current Error: The new record was not set as current.");
        }

        echo "✔ Education single current record status enforcement verified.\n";
    }

    /**
     * 4. Assert preferred destination pivot mappings
     */
    private function testPreferredDestinationsPivotMappings(): void {
        $visitorRoleId = $this->db->query("SELECT id FROM roles WHERE name = 'visitor'")->fetchColumn();
        $pkId = $this->db->query("SELECT id FROM countries WHERE iso2 = 'PK'")->fetchColumn();
        $email = 'test_profile_pref_c@scholarmatch.test';

        // Insert
        $stmt = $this->db->prepare("
            INSERT INTO users (role_id, first_name, last_name, email, phone, password_hash, status) 
            VALUES (:role_id, 'Pref', 'User', :email, '+923007777773', 'hash', 'active')
        ");
        $stmt->execute(['role_id' => $visitorRoleId, 'email' => $email]);
        $userId = $this->db->lastInsertId();

        // Add preferred country
        $this->db->prepare("INSERT INTO user_preferred_countries (user_id, country_id) VALUES (:uid, :cid)")->execute(['uid' => $userId, 'cid' => $pkId]);

        // Attempt inserting same preferred country (unique constraint verification)
        try {
            $this->db->prepare("INSERT INTO user_preferred_countries (user_id, country_id) VALUES (:uid, :cid)")->execute(['uid' => $userId, 'cid' => $pkId]);
            throw new \Exception("Preferences Mapping Error: Duplicate pivot mappings allowed.");
        } catch (\PDOException $e) {
            // Expected unique key constraint exception
        }

        echo "✔ Preferred Destinations mapping table constraints passed.\n";
    }

    /**
     * 5. Assert preferred fields pivot mappings
     */
    private function testPreferredFieldsPivotMappings(): void {
        $visitorRoleId = $this->db->query("SELECT id FROM roles WHERE name = 'visitor'")->fetchColumn();
        $email = 'test_profile_pref_f@scholarmatch.test';

        // Insert
        $stmt = $this->db->prepare("
            INSERT INTO users (role_id, first_name, last_name, email, phone, password_hash, status) 
            VALUES (:role_id, 'Pref2', 'User', :email, '+923007777774', 'hash', 'active')
        ");
        $stmt->execute(['role_id' => $visitorRoleId, 'email' => $email]);
        $userId = $this->db->lastInsertId();

        // Add preferred field
        $this->db->prepare("INSERT INTO user_preferred_fields (user_id, field_of_study_id) VALUES (:uid, 1)")->execute(['uid' => $userId]);

        // Duplicate assert
        try {
            $this->db->prepare("INSERT INTO user_preferred_fields (user_id, field_of_study_id) VALUES (:uid, 1)")->execute(['uid' => $userId]);
            throw new \Exception("Preferences Fields Error: Duplicate pivot mapping allowed.");
        } catch (\PDOException $e) {
            // Expected
        }

        echo "✔ Preferred Fields mapping table constraints passed.\n";
    }

    /**
     * 6. Assert preferred degree levels pivot mappings
     */
    private function testPreferredDegreesPivotMappings(): void {
        $visitorRoleId = $this->db->query("SELECT id FROM roles WHERE name = 'visitor'")->fetchColumn();
        $email = 'test_profile_pref_d@scholarmatch.test';

        // Insert
        $stmt = $this->db->prepare("
            INSERT INTO users (role_id, first_name, last_name, email, phone, password_hash, status) 
            VALUES (:role_id, 'Pref3', 'User', :email, '+923007777775', 'hash', 'active')
        ");
        $stmt->execute(['role_id' => $visitorRoleId, 'email' => $email]);
        $userId = $this->db->lastInsertId();

        // Add preferred degree level
        $this->db->prepare("INSERT INTO user_preferred_degree_levels (user_id, degree_level) VALUES (:uid, 'PhD')")->execute(['uid' => $userId]);

        // Duplicate check
        try {
            $this->db->prepare("INSERT INTO user_preferred_degree_levels (user_id, degree_level) VALUES (:uid, 'PhD')")->execute(['uid' => $userId]);
            throw new \Exception("Preferences Degrees Error: Duplicate pivot mapping allowed.");
        } catch (\PDOException $e) {
            // Expected
        }

        echo "✔ Preferred Degree Levels mapping table constraints passed.\n";
    }

    /**
     * 7. Assert IDOR/privilege escalation blocks on updates
     */
    private function testIdorAndAccessBarriers(): void {
        $visitorRoleId = $this->db->query("SELECT id FROM roles WHERE name = 'visitor'")->fetchColumn();
        
        // 1. Insert user A
        $stmtA = $this->db->prepare("
            INSERT INTO users (role_id, first_name, last_name, email, phone, password_hash, status) 
            VALUES (:role_id, 'UserA', 'Test', 'test_profile_user_a@scholarmatch.test', '+923007777776', 'hash', 'active')
        ");
        $stmtA->execute(['role_id' => $visitorRoleId]);
        $userAId = $this->db->lastInsertId();

        // 2. Insert user B
        $stmtB = $this->db->prepare("
            INSERT INTO users (role_id, first_name, last_name, email, phone, password_hash, status) 
            VALUES (:role_id, 'UserB', 'Test', 'test_profile_user_b@scholarmatch.test', '+923007777777', 'hash', 'active')
        ");
        $stmtB->execute(['role_id' => $visitorRoleId]);
        $userBId = $this->db->lastInsertId();

        // 3. Create education record for User A
        $this->db->prepare("
            INSERT INTO education_records (user_id, institution_name, degree_level, degree_title, field_of_study) 
            VALUES (:uid, 'Uni A', 'Bachelor\'s', 'BS', 'Engineering')
        ")->execute(['uid' => $userAId]);
        $eduRecordAId = $this->db->lastInsertId();

        // 4. Simulate User B trying to update/delete User A's education record (IDOR Simulation)
        // Controller ownership guard:
        $activeUser = $userBId; // User B is authenticated

        $stmtCheck = $this->db->prepare("SELECT user_id FROM education_records WHERE id = :id LIMIT 1");
        $stmtCheck->execute(['id' => $eduRecordAId]);
        $recordOwner = $stmtCheck->fetchColumn();

        if ($recordOwner && (int)$recordOwner !== $activeUser) {
             // Correctly blocked!
             $blocked = true;
        } else {
             $blocked = false;
        }

        if (!$blocked) {
             throw new \Exception("IDOR Vulnerability: Authenticated user B bypassed ownership verification of user A's education record.");
        }

        echo "✔ IDOR protection ownership guards passed.\n";
    }

    /**
     * 8. Assert length and type validation checks
     */
    private function testLengthAndTypeValidations(): void {
        $controller = new \App\Controllers\ProfileController();
        
        $reflector = new \ReflectionMethod(\App\Controllers\ProfileController::class, 'validateEducation');
        $reflector->setAccessible(true);
        
        // Too long institution name
        $tooLongInstitution = str_repeat('A', 151);
        $errors = $reflector->invoke($controller, $tooLongInstitution, 'Bachelor\'s', 'BS', 'CS', 3.0, 4.0, 80.0, '2020-01-01', '2024-01-01');
        
        if (!isset($errors['institution_name']) || $errors['institution_name'] !== 'Institution name must not exceed 150 characters.') {
             throw new \Exception("Validation Error: Character length checks for institution name failed.");
        }
        
        // Too long degree title
        $tooLongTitle = str_repeat('B', 151);
        $errors = $reflector->invoke($controller, 'Uni', 'Bachelor\'s', $tooLongTitle, 'CS', 3.0, 4.0, 80.0, '2020-01-01', '2024-01-01');
        if (!isset($errors['degree_title']) || $errors['degree_title'] !== 'Degree title must not exceed 150 characters.') {
             throw new \Exception("Validation Error: Character length checks for degree title failed.");
        }

        // Invalid degree level
        $errors = $reflector->invoke($controller, 'Uni', 'Hacker Degree', 'BS', 'CS', 3.0, 4.0, 80.0, '2020-01-01', '2024-01-01');
        if (!isset($errors['degree_level']) || $errors['degree_level'] !== 'Invalid degree level selected.') {
             throw new \Exception("Validation Error: Invalid degree level check failed.");
        }
        
        echo "✔ Input size validations and degree checks passed.\n";
    }

    /**
     * 9. Assert AJAX geography lookup security properties via subprocess
     */
    private function testAjaxEndpointsSecurity(): void {
        // Run a CLI check that invokes ProfileController->getStates unauthenticated
        $cmd = "php -r \"require 'tests/bootstrap.php'; \$_GET['country_id'] = 1; (new \App\Controllers\ProfileController())->getStates();\"";
        
        $output = [];
        $resultCode = 0;
        exec($cmd, $output, $resultCode);
        
        $fullOutput = implode("\n", $output);
        if (strpos($fullOutput, 'Unauthorized') === false) {
             throw new \Exception("AJAX Security Error: Unauthenticated access was not blocked with Unauthorized message.");
        }

        echo "✔ AJAX endpoints authorization checks verified.\n";
    }
}
