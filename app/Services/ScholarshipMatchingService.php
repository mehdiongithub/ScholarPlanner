<?php

namespace App\Services;

use App\Services\Database;
use App\Helpers\AgeCalculator;
use DateTime;
use PDO;

class ScholarshipMatchingService {
    private PDO $db;

    public function __construct() {
        $this->db = Database::connection();
    }

    /**
     * Match a user against a specific scholarship, supporting preloaded datasets to resolve N+1 queries.
     */
    public function matchUserAndScholarship(int $userId, int $scholarshipId, ?array $preloadedUserData = null, ?array $preloadedSchData = null): array {
        // 1. Resolve User Profile & Education & Preferences
        if ($preloadedUserData !== null) {
            $profile = $preloadedUserData['profile'];
            $education = $preloadedUserData['education'];
            $preferences = $preloadedUserData['preferences'];
            $prefCountries = $preloadedUserData['prefCountries'];
            $prefFields = $preloadedUserData['prefFields'];
            $prefDegrees = $preloadedUserData['prefDegrees'];
            $userFieldId = $preloadedUserData['userFieldId'];
            $userInstStateId = $preloadedUserData['userInstStateId'] ?? null;
        } else {
            $stmt = $this->db->prepare("SELECT * FROM student_profiles WHERE user_id = :user_id LIMIT 1");
            $stmt->execute(['user_id' => $userId]);
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$profile) {
                return $this->emptyResponse('INSUFFICIENT_DATA', 'No student profile exists. Please complete your profile.');
            }

            $stmtEdu = $this->db->prepare("
                SELECT er.*, inst.state_id as inst_state_id 
                FROM education_records er
                LEFT JOIN institutions inst ON er.institution_id = inst.id
                WHERE er.user_id = :user_id 
                ORDER BY er.is_current DESC, er.end_date DESC, er.start_date DESC 
                LIMIT 1
            ");
            $stmtEdu->execute(['user_id' => $userId]);
            $education = $stmtEdu->fetch(PDO::FETCH_ASSOC) ?: null;

            $userInstStateId = $education['inst_state_id'] ?? null;

            $stmtPref = $this->db->prepare("SELECT * FROM user_preferences WHERE user_id = :user_id LIMIT 1");
            $stmtPref->execute(['user_id' => $userId]);
            $preferences = $stmtPref->fetch(PDO::FETCH_ASSOC) ?: null;

            $stmtPrefCountries = $this->db->prepare("SELECT country_id FROM user_preferred_countries WHERE user_id = :user_id");
            $stmtPrefCountries->execute(['user_id' => $userId]);
            $prefCountries = $stmtPrefCountries->fetchAll(PDO::FETCH_COLUMN);

            $stmtPrefFields = $this->db->prepare("SELECT field_of_study_id FROM user_preferred_fields WHERE user_id = :user_id");
            $stmtPrefFields->execute(['user_id' => $userId]);
            $prefFields = $stmtPrefFields->fetchAll(PDO::FETCH_COLUMN);

            $stmtPrefDegrees = $this->db->prepare("SELECT degree_level FROM user_preferred_degree_levels WHERE user_id = :user_id");
            $stmtPrefDegrees->execute(['user_id' => $userId]);
            $prefDegrees = $stmtPrefDegrees->fetchAll(PDO::FETCH_COLUMN);

            $userFieldId = null;
            if ($education) {
                $stmtField = $this->db->prepare("SELECT id FROM fields_of_study WHERE name = :name LIMIT 1");
                $stmtField->execute(['name' => $education['field_of_study']]);
                $val = $stmtField->fetchColumn();
                $userFieldId = $val ? (int)$val : null;
            }
        }

        if (!$profile) {
            return $this->emptyResponse('INSUFFICIENT_DATA', 'No student profile exists. Please complete your profile.');
        }

        // 2. Resolve Scholarship Rules & Pivots
        if ($preloadedSchData !== null) {
            $scholarship = $preloadedSchData['scholarship'];
            $rules = $preloadedSchData['rules'];
            $schCountries = $preloadedSchData['countries'];
            $schFields = $preloadedSchData['fields'];
            $schDegrees = $preloadedSchData['degrees'];
            $schNationalities = $preloadedSchData['nationalities'];
            $schLanguages = $preloadedSchData['languages'];
            $schStates = $preloadedSchData['states'] ?? [];
            $schInstitutions = $preloadedSchData['institutions'] ?? [];
        } else {
            $stmtSch = $this->db->prepare("SELECT * FROM scholarships WHERE id = :id LIMIT 1");
            $stmtSch->execute(['id' => $scholarshipId]);
            $scholarship = $stmtSch->fetch(PDO::FETCH_ASSOC);

            if (!$scholarship || $scholarship['status'] !== 'published') {
                return $this->emptyResponse('NOT_ELIGIBLE', 'Scholarship is not available.');
            }

            $stmtRules = $this->db->prepare("SELECT * FROM scholarship_eligibility_rules WHERE scholarship_id = :id LIMIT 1");
            $stmtRules->execute(['id' => $scholarshipId]);
            $rules = $stmtRules->fetch(PDO::FETCH_ASSOC) ?: [];

            $schCountries = $this->db->query("SELECT country_id FROM scholarship_countries WHERE scholarship_id = $scholarshipId")->fetchAll(PDO::FETCH_COLUMN);
            $schFields = $this->db->query("SELECT field_of_study_id FROM scholarship_fields WHERE scholarship_id = $scholarshipId")->fetchAll(PDO::FETCH_COLUMN);
            $schDegrees = $this->db->query("SELECT degree_level FROM scholarship_degree_levels WHERE scholarship_id = $scholarshipId")->fetchAll(PDO::FETCH_COLUMN);
            $schNationalities = $this->db->query("SELECT country_id FROM scholarship_eligible_nationalities WHERE scholarship_id = $scholarshipId")->fetchAll(PDO::FETCH_COLUMN);
            $schLanguages = $this->db->query("SELECT * FROM scholarship_languages WHERE scholarship_id = $scholarshipId")->fetchAll(PDO::FETCH_ASSOC);
            $schStates = $this->db->query("SELECT state_id FROM scholarship_states WHERE scholarship_id = $scholarshipId")->fetchAll(PDO::FETCH_COLUMN);
            $schInstitutions = $this->db->query("SELECT institution_id FROM scholarship_institutions WHERE scholarship_id = $scholarshipId")->fetchAll(PDO::FETCH_COLUMN);
        }

        if (!$scholarship || $scholarship['status'] !== 'published') {
            return $this->emptyResponse('NOT_ELIGIBLE', 'Scholarship is not available.');
        }

        // 3. Evaluate Rules
        $evaluations = [];
        $matched = [];
        $failed = [];
        $missing = [];

        // Evaluator 1: Nationality
        $natRes = $this->evaluateNationality($profile, $schNationalities);
        $evaluations['nationality'] = $natRes;

        // Evaluator 2: Geographic Province / State
        $stateRes = $this->evaluateState($profile, $education, $schStates, $userInstStateId);
        $evaluations['state'] = $stateRes;

        // Evaluator 3: Education Level / Study Level
        $degRes = $this->evaluateEducationLevel($education, $schDegrees);
        $evaluations['degree'] = $degRes;

        // Evaluator 4: Specific Educational Institution
        $instRes = $this->evaluateInstitution($education, $schInstitutions);
        $evaluations['institution'] = $instRes;

        // Evaluator 5: Age
        $ageRes = $this->evaluateAge($profile, $rules['minimum_age'] ?? null, $rules['maximum_age'] ?? null);
        $evaluations['age'] = $ageRes;

        // Evaluator 6: Field of study
        $fieldRes = $this->evaluateField($education, $schFields, $userFieldId);
        $evaluations['field'] = $fieldRes;

        // Evaluator 7: CGPA & scale
        $cgpaRes = $this->evaluateCgpa($education, $rules['minimum_cgpa'] ?? null, $rules['cgpa_scale'] ?? null);
        $evaluations['cgpa'] = $cgpaRes;

        // Evaluator 8: Percentage
        $pctRes = $this->evaluatePercentage($education, $rules['minimum_percentage'] ?? null);
        $evaluations['percentage'] = $pctRes;

        // Evaluator 9: Gender
        $genderRes = $this->evaluateGender($profile, $rules['gender_requirement'] ?? null);
        $evaluations['gender'] = $genderRes;

        // Evaluator 10: Language tests (IELTS, TOEFL, PTE, Duolingo)
        $langRes = $this->evaluateLanguages($profile, $schLanguages);
        $evaluations['languages'] = $langRes;

        // Evaluator 11: Deadline
        $deadlineRes = $this->evaluateDeadline($scholarship['application_deadline']);
        $evaluations['deadline'] = $deadlineRes;

        // Evaluator 12: Country match preference (Soft)
        $countryPrefRes = $this->evaluateCountryPreference($scholarship, $prefCountries, $schCountries);
        $evaluations['country_pref'] = $countryPrefRes;

        // Evaluator 13: Field match preference (Soft)
        $fieldPrefRes = $this->evaluateFieldPreference($schFields, $prefFields);
        $evaluations['field_pref'] = $fieldPrefRes;

        // Evaluator 14: Funding preference (Soft)
        $fundingPrefRes = $this->evaluateFundingPreference($scholarship, $profile, $preferences);
        $evaluations['funding_pref'] = $fundingPrefRes;

        // 4. Partition into matched, failed, missing arrays
        foreach ($evaluations as $key => $res) {
            if ($res['status'] === 'MATCHED') {
                $matched[$key] = $res['message'];
            } elseif ($res['status'] === 'FAILED') {
                $failed[$key] = $res['message'];
            } else {
                $missing[$key] = $res['message'];
            }
        }

        // 5. Determine eligibility status using strict AND logic across all hard criteria
        $hardKeys = ['nationality', 'state', 'degree', 'institution', 'age', 'field', 'cgpa', 'percentage', 'gender', 'languages', 'deadline'];
        $hasFailedHard = false;
        $hasMissingHard = false;

        foreach ($hardKeys as $k) {
            if (isset($failed[$k])) {
                $hasFailedHard = true;
            }
            if (isset($missing[$k])) {
                $hasMissingHard = true;
            }
        }

        $eligibilityStatus = 'ELIGIBLE';
        if ($hasFailedHard) {
            $eligibilityStatus = 'NOT_ELIGIBLE';
        } elseif ($hasMissingHard) {
            $eligibilityStatus = 'INSUFFICIENT_DATA';
        }

        // 6. Calculate Score based on weights (Eligibility: 70%, Preferences: 30%)
        $score = 0;

        if ($natRes['status'] === 'MATCHED') $score += 10;
        if ($stateRes['status'] === 'MATCHED') $score += 10;
        if ($degRes['status'] === 'MATCHED') $score += 10;
        if ($instRes['status'] === 'MATCHED') $score += 10;
        if ($ageRes['status'] === 'MATCHED') $score += 10;
        if ($fieldRes['status'] === 'MATCHED') $score += 10;

        if ($cgpaRes['status'] === 'MATCHED' || $pctRes['status'] === 'MATCHED') {
            $score += 10;
        }

        if ($countryPrefRes['status'] === 'MATCHED') $score += 10;
        if ($fieldPrefRes['status'] === 'MATCHED') $score += 10;
        if ($fundingPrefRes['status'] === 'MATCHED') $score += 10;

        // If NOT_ELIGIBLE, final score is capped to 0
        $finalScore = ($eligibilityStatus === 'NOT_ELIGIBLE') ? 0 : min(100, $score);

        // 7. Recommendation Level
        $recLevel = 'NOT_RECOMMENDED';
        if ($eligibilityStatus === 'ELIGIBLE') {
            if ($finalScore >= 80) {
                $recLevel = 'HIGHLY_RECOMMENDED';
            } elseif ($finalScore >= 65) {
                $recLevel = 'RECOMMENDED';
            } else {
                $recLevel = 'POSSIBLE_MATCH';
            }
        } elseif ($eligibilityStatus === 'INSUFFICIENT_DATA' || $eligibilityStatus === 'POSSIBLY_ELIGIBLE') {
            if ($finalScore >= 50) {
                $recLevel = 'POSSIBLE_MATCH';
            } else {
                $recLevel = 'LOW_MATCH';
            }
        }

        return [
            'user_id' => $userId,
            'scholarship_id' => $scholarshipId,
            'match_score' => $finalScore,
            'eligibility_status' => $eligibilityStatus,
            'recommendation_level' => $recLevel,
            'matched_criteria' => $matched,
            'failed_criteria' => $failed,
            'missing_criteria' => $missing,
            'calculated_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Save/Cache match results to database
     */
    public function saveMatch(array $match): bool {
        $stmt = $this->db->prepare("
            INSERT INTO scholarship_matches (
                user_id, scholarship_id, match_score, match_status, 
                eligibility_status, recommendation_level, 
                matched_criteria, failed_criteria, missing_criteria, 
                engine_version, calculated_at
            ) 
            VALUES (
                :user_id, :scholarship_id, :match_score, :match_status, 
                :eligibility_status, :recommendation_level, 
                :matched_criteria, :failed_criteria, :missing_criteria, 
                '2.0', :calculated_at
            )
            ON DUPLICATE KEY UPDATE 
                match_score = VALUES(match_score),
                match_status = VALUES(match_status),
                eligibility_status = VALUES(eligibility_status),
                recommendation_level = VALUES(recommendation_level),
                matched_criteria = VALUES(matched_criteria),
                failed_criteria = VALUES(failed_criteria),
                missing_criteria = VALUES(missing_criteria),
                calculated_at = VALUES(calculated_at),
                updated_at = NOW()
        ");

        return $stmt->execute([
            'user_id' => $match['user_id'],
            'scholarship_id' => $match['scholarship_id'],
            'match_score' => $match['match_score'],
            'match_status' => $match['eligibility_status'],
            'eligibility_status' => $match['eligibility_status'],
            'recommendation_level' => $match['recommendation_level'],
            'matched_criteria' => json_encode($match['matched_criteria']),
            'failed_criteria' => json_encode($match['failed_criteria']),
            'missing_criteria' => json_encode($match['missing_criteria']),
            'calculated_at' => $match['calculated_at']
        ]);
    }

    /**
     * Recalculate matches for a user in bulk using preloaded data
     */
    public function recalculateForUser(int $userId): void {
        // 1. Preload user data
        $stmt = $this->db->prepare("SELECT * FROM student_profiles WHERE user_id = :user_id LIMIT 1");
        $stmt->execute(['user_id' => $userId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$profile) {
            return;
        }

        $stmtEdu = $this->db->prepare("
            SELECT er.*, inst.state_id as inst_state_id 
            FROM education_records er
            LEFT JOIN institutions inst ON er.institution_id = inst.id
            WHERE er.user_id = :user_id 
            ORDER BY er.is_current DESC, er.end_date DESC, er.start_date DESC 
            LIMIT 1
        ");
        $stmtEdu->execute(['user_id' => $userId]);
        $education = $stmtEdu->fetch(PDO::FETCH_ASSOC) ?: null;

        $userInstStateId = $education['inst_state_id'] ?? null;

        $stmtPref = $this->db->prepare("SELECT * FROM user_preferences WHERE user_id = :user_id LIMIT 1");
        $stmtPref->execute(['user_id' => $userId]);
        $preferences = $stmtPref->fetch(PDO::FETCH_ASSOC) ?: null;

        $stmtPrefCountries = $this->db->prepare("SELECT country_id FROM user_preferred_countries WHERE user_id = :user_id");
        $stmtPrefCountries->execute(['user_id' => $userId]);
        $prefCountries = $stmtPrefCountries->fetchAll(PDO::FETCH_COLUMN);

        $stmtPrefFields = $this->db->prepare("SELECT field_of_study_id FROM user_preferred_fields WHERE user_id = :user_id");
        $stmtPrefFields->execute(['user_id' => $userId]);
        $prefFields = $stmtPrefFields->fetchAll(PDO::FETCH_COLUMN);

        $stmtPrefDegrees = $this->db->prepare("SELECT degree_level FROM user_preferred_degree_levels WHERE user_id = :user_id");
        $stmtPrefDegrees->execute(['user_id' => $userId]);
        $prefDegrees = $stmtPrefDegrees->fetchAll(PDO::FETCH_COLUMN);

        $userFieldId = null;
        if ($education) {
            $stmtField = $this->db->prepare("SELECT id FROM fields_of_study WHERE name = :name LIMIT 1");
            $stmtField->execute(['name' => $education['field_of_study']]);
            $val = $stmtField->fetchColumn();
            $userFieldId = $val ? (int)$val : null;
        }

        $userData = [
            'profile' => $profile,
            'education' => $education,
            'preferences' => $preferences,
            'prefCountries' => $prefCountries,
            'prefFields' => $prefFields,
            'prefDegrees' => $prefDegrees,
            'userFieldId' => $userFieldId,
            'userInstStateId' => $userInstStateId
        ];

        // 2. Preload scholarship data in bulk
        $scholarships = $this->db->query("SELECT * FROM scholarships WHERE status = 'published'")->fetchAll(PDO::FETCH_ASSOC);
        $schIds = array_column($scholarships, 'id');

        if (empty($schIds)) {
            return;
        }

        $schIdsString = implode(',', array_map('intval', $schIds));

        // Preload rules
        $rawRules = $this->db->query("SELECT * FROM scholarship_eligibility_rules WHERE scholarship_id IN ($schIdsString)")->fetchAll(PDO::FETCH_ASSOC);
        $rulesMap = [];
        foreach ($rawRules as $r) {
            $rulesMap[$r['scholarship_id']] = $r;
        }

        // Preload host countries
        $rawCountries = $this->db->query("SELECT * FROM scholarship_countries WHERE scholarship_id IN ($schIdsString)")->fetchAll(PDO::FETCH_ASSOC);
        $countriesMap = [];
        foreach ($rawCountries as $c) {
            $countriesMap[$c['scholarship_id']][] = (int)$c['country_id'];
        }

        // Preload fields of study
        $rawFields = $this->db->query("SELECT * FROM scholarship_fields WHERE scholarship_id IN ($schIdsString)")->fetchAll(PDO::FETCH_ASSOC);
        $fieldsMap = [];
        foreach ($rawFields as $f) {
            $fieldsMap[$f['scholarship_id']][] = (int)$f['field_of_study_id'];
        }

        // Preload degree levels
        $rawDegrees = $this->db->query("SELECT * FROM scholarship_degree_levels WHERE scholarship_id IN ($schIdsString)")->fetchAll(PDO::FETCH_ASSOC);
        $degreesMap = [];
        foreach ($rawDegrees as $d) {
            $degreesMap[$d['scholarship_id']][] = $d['degree_level'];
        }

        // Preload nationalities
        $rawNationalities = $this->db->query("SELECT * FROM scholarship_eligible_nationalities WHERE scholarship_id IN ($schIdsString)")->fetchAll(PDO::FETCH_ASSOC);
        $nationalitiesMap = [];
        foreach ($rawNationalities as $n) {
            $nationalitiesMap[$n['scholarship_id']][] = (int)$n['country_id'];
        }

        // Preload languages
        $rawLanguages = $this->db->query("SELECT * FROM scholarship_languages WHERE scholarship_id IN ($schIdsString)")->fetchAll(PDO::FETCH_ASSOC);
        $languagesMap = [];
        foreach ($rawLanguages as $l) {
            $languagesMap[$l['scholarship_id']][] = $l;
        }

        // Preload states (provinces)
        $rawStates = $this->db->query("SELECT * FROM scholarship_states WHERE scholarship_id IN ($schIdsString)")->fetchAll(PDO::FETCH_ASSOC);
        $statesMap = [];
        foreach ($rawStates as $st) {
            $statesMap[$st['scholarship_id']][] = (int)$st['state_id'];
        }

        // Preload institutions
        $rawInstitutions = $this->db->query("SELECT * FROM scholarship_institutions WHERE scholarship_id IN ($schIdsString)")->fetchAll(PDO::FETCH_ASSOC);
        $institutionsMap = [];
        foreach ($rawInstitutions as $inst) {
            $institutionsMap[$inst['scholarship_id']][] = (int)$inst['institution_id'];
        }

        // 3. Execute matching and save results in bulk
        $this->db->beginTransaction();
        try {
            foreach ($scholarships as $s) {
                $sid = (int)$s['id'];
                $schData = [
                    'scholarship' => $s,
                    'rules' => $rulesMap[$sid] ?? [],
                    'countries' => $countriesMap[$sid] ?? [],
                    'fields' => $fieldsMap[$sid] ?? [],
                    'degrees' => $degreesMap[$sid] ?? [],
                    'nationalities' => $nationalitiesMap[$sid] ?? [],
                    'languages' => $languagesMap[$sid] ?? [],
                    'states' => $statesMap[$sid] ?? [],
                    'institutions' => $institutionsMap[$sid] ?? []
                ];

                $match = $this->matchUserAndScholarship($userId, $sid, $userData, $schData);
                $this->saveMatch($match);
            }
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // --- Core Rule Evaluators ---

    /**
     * Evaluates geographic province / state eligibility
     */
    private function evaluateState(array $profile, ?array $education, array $schStates, ?int $userInstStateId = null): array {
        if (empty($schStates)) {
            return ['status' => 'MATCHED', 'message' => 'Open to all provinces/regions (Pakistan-wide/Global).'];
        }

        $userStateId = !empty($profile['residence_state_id']) ? (int)$profile['residence_state_id'] : null;

        // Check user residence state
        if ($userStateId !== null && in_array($userStateId, $schStates, true)) {
            return ['status' => 'MATCHED', 'message' => 'Your province/state of residence meets the geographic criteria.'];
        }

        // Check user institution state
        if ($userInstStateId !== null && in_array((int)$userInstStateId, $schStates, true)) {
            return ['status' => 'MATCHED', 'message' => 'Your educational institution province/state meets the geographic criteria.'];
        }

        if ($userStateId === null && $userInstStateId === null) {
            return ['status' => 'MISSING', 'message' => 'Province/State is not specified in your profile.'];
        }

        return ['status' => 'FAILED', 'message' => 'Your province/state does not meet the geographic restrictions for this opportunity.'];
    }

    /**
     * Evaluates education level / study level eligibility (School, College, University)
     */
    private function evaluateEducationLevel(?array $education, array $schDegrees): array {
        if (empty($schDegrees)) {
            return ['status' => 'MATCHED', 'message' => 'Open to all education levels.'];
        }
        if (!$education || empty($education['degree_level'])) {
            return ['status' => 'MISSING', 'message' => 'Education level is not specified in your profile.'];
        }

        $userDegree = trim($education['degree_level']);

        // Check exact match
        if (in_array($userDegree, $schDegrees, true)) {
            return ['status' => 'MATCHED', 'message' => "Education level ($userDegree) matches the required study level."];
        }

        // Canonical mapping groups
        $schoolGroup = ['School', 'High School', 'Matric', 'Matriculation', 'O-Level', 'Secondary School', 'Middle School'];
        $collegeGroup = ['College', 'Intermediate', 'HSSC', 'FSc', 'FA', 'ICS', 'ICom', 'A-Level', 'Higher Secondary', 'Intermediate / College'];
        $undergradGroup = ['Bachelor\'s', 'Undergraduate', 'Associate Degree', 'BS', 'BSc', 'BBA', 'MBBS', 'B.Ed', 'LLB'];
        $mastersGroup = ['Master\'s', 'MPhil', 'Graduate', 'Postgraduate', 'MS', 'MSc', 'MBA', 'LLM', 'M.Ed'];
        $phdGroup = ['PhD', 'Doctorate', 'Postdoctoral'];

        foreach ($schDegrees as $reqDegree) {
            $req = trim($reqDegree);
            if (in_array($req, $schoolGroup, true) && in_array($userDegree, $schoolGroup, true)) {
                return ['status' => 'MATCHED', 'message' => "School education level ($userDegree) matches requirement ($req)."];
            }
            if (in_array($req, $collegeGroup, true) && in_array($userDegree, $collegeGroup, true)) {
                return ['status' => 'MATCHED', 'message' => "College education level ($userDegree) matches requirement ($req)."];
            }
            if (in_array($req, $undergradGroup, true) && in_array($userDegree, $undergradGroup, true)) {
                return ['status' => 'MATCHED', 'message' => "Undergraduate education level ($userDegree) matches requirement ($req)."];
            }
            if (in_array($req, $mastersGroup, true) && in_array($userDegree, $mastersGroup, true)) {
                return ['status' => 'MATCHED', 'message' => "Master's/MPhil education level ($userDegree) matches requirement ($req)."];
            }
            if (in_array($req, $phdGroup, true) && in_array($userDegree, $phdGroup, true)) {
                return ['status' => 'MATCHED', 'message' => "Doctoral education level ($userDegree) matches requirement ($req)."];
            }
        }

        return ['status' => 'FAILED', 'message' => "Your education level ($userDegree) is not eligible for this opportunity."];
    }

    /**
     * Evaluates specific school / college / university institution restriction
     */
    private function evaluateInstitution(?array $education, array $schInstitutions): array {
        if (empty($schInstitutions)) {
            return ['status' => 'MATCHED', 'message' => 'Open to all educational institutions.'];
        }
        if (!$education || (empty($education['institution_id']) && empty($education['institution_name']))) {
            return ['status' => 'MISSING', 'message' => 'Educational institution is not specified in your profile.'];
        }

        $userInstId = (int)($education['institution_id'] ?? 0);
        if ($userInstId > 0 && in_array($userInstId, $schInstitutions, true)) {
            return ['status' => 'MATCHED', 'message' => 'Your educational institution meets the specific institution requirement.'];
        }

        return ['status' => 'FAILED', 'message' => 'This opportunity is restricted to students of specific educational institutions.'];
    }

    private function evaluateNationality(array $profile, array $eligibleNationalities): array {
        if (empty($eligibleNationalities)) {
            return ['status' => 'MATCHED', 'message' => 'Open to all nationalities.'];
        }
        if (empty($profile['nationality_country_id'])) {
            return ['status' => 'MISSING', 'message' => 'Nationality is not provided.'];
        }
        if (in_array((int)$profile['nationality_country_id'], $eligibleNationalities, true)) {
            return ['status' => 'MATCHED', 'message' => 'Your nationality meets the eligibility requirements.'];
        }
        return ['status' => 'FAILED', 'message' => 'Your nationality is not eligible for this scholarship.'];
    }

    private function evaluateAge(array $profile, ?int $minAge, ?int $maxAge): array {
        if ($minAge === null && $maxAge === null) {
            return ['status' => 'MATCHED', 'message' => 'No age limits specified.'];
        }
        if (empty($profile['date_of_birth'])) {
            return ['status' => 'MISSING', 'message' => 'Date of birth is not provided.'];
        }

        $age = AgeCalculator::calculateAge($profile['date_of_birth']);
        if ($age === null || $age < 0) {
            return ['status' => 'MISSING', 'message' => 'Invalid date of birth.'];
        }

        if ($minAge !== null && $age < $minAge) {
            return ['status' => 'FAILED', 'message' => "Age ($age) is below the minimum required age of $minAge."];
        }
        if ($maxAge !== null && $age > $maxAge) {
            return ['status' => 'FAILED', 'message' => "Age ($age) exceeds the maximum allowed age of $maxAge."];
        }

        return ['status' => 'MATCHED', 'message' => "Age ($age) is within the eligible range."];
    }

    private function evaluateField(?array $education, array $requiredFields, ?int $userFieldId): array {
        if (empty($requiredFields)) {
            return ['status' => 'MATCHED', 'message' => 'Open to all fields of study.'];
        }
        if (!$education) {
            return ['status' => 'MISSING', 'message' => 'Education history is not provided.'];
        }

        if ($userFieldId !== null && in_array($userFieldId, $requiredFields, true)) {
            return ['status' => 'MATCHED', 'message' => "Your field of study ({$education['field_of_study']}) is eligible."];
        }

        return ['status' => 'FAILED', 'message' => "Field of study ({$education['field_of_study']}) is not in the eligible disciplines list."];
    }

    private function evaluateCgpa(?array $education, ?float $minCgpa, ?float $cgpaScale): array {
        if ($minCgpa === null) {
            return ['status' => 'MATCHED', 'message' => 'No minimum CGPA required.'];
        }
        if (!$education || empty($education['cgpa'])) {
            return ['status' => 'MISSING', 'message' => 'CGPA is not provided.'];
        }

        $userCgpa = (float)$education['cgpa'];
        $userScale = (float)($education['cgpa_scale'] ?? 4.0);
        $schMin = (float)$minCgpa;
        $schScale = (float)($cgpaScale ?? 4.0);

        // Validation bounds checks
        if ($userCgpa < 0 || $schMin < 0 || $userScale <= 0 || $schScale <= 0) {
            return ['status' => 'FAILED', 'message' => 'Academic records contain negative values or zero scales, which are invalid.'];
        }
        if ($userCgpa > $userScale) {
            return ['status' => 'FAILED', 'message' => "Your CGPA ($userCgpa) cannot be greater than its scale ($userScale)."];
        }
        if ($schMin > $schScale) {
            return ['status' => 'FAILED', 'message' => "The minimum CGPA ($schMin) cannot be greater than its scale ($schScale)."];
        }

        $userPct = ($userCgpa / $userScale) * 100;
        $schPct = ($schMin / $schScale) * 100;

        if ($userPct >= $schPct) {
            return ['status' => 'MATCHED', 'message' => "CGPA matches requirement (Normalized score meets threshold)."];
        }

        return ['status' => 'FAILED', 'message' => "Your CGPA ({$userCgpa}/{$userScale}) does not meet the minimum requirement of $minCgpa on scale $cgpaScale."];
    }

    private function evaluatePercentage(?array $education, ?float $minPercentage): array {
        if ($minPercentage === null) {
            return ['status' => 'MATCHED', 'message' => 'No minimum percentage required.'];
        }
        if ($minPercentage < 0 || $minPercentage > 100) {
            return ['status' => 'FAILED', 'message' => 'Minimum percentage required must be between 0% and 100%.'];
        }

        $percentage = null;
        if ($education) {
            if (!empty($education['percentage'])) {
                $percentage = (float)$education['percentage'];
            } elseif (!empty($education['cgpa'])) {
                $userCgpa = (float)$education['cgpa'];
                $userScale = (float)($education['cgpa_scale'] ?? 4.0);
                if ($userScale <= 0 || $userCgpa < 0 || $userCgpa > $userScale) {
                    return ['status' => 'FAILED', 'message' => 'Your CGPA is greater than its scale or contains invalid values.'];
                }
                $percentage = ($userCgpa / $userScale) * 100;
            }
        }

        if ($percentage === null) {
            return ['status' => 'MISSING', 'message' => 'Academic percentage is not provided.'];
        }

        if ($percentage < 0 || $percentage > 100) {
            return ['status' => 'FAILED', 'message' => 'Your academic percentage value is invalid.'];
        }

        if ($percentage >= $minPercentage) {
            return ['status' => 'MATCHED', 'message' => "Academic percentage (" . number_format($percentage, 1) . "%) meets the minimum requirement of $minPercentage%."];
        }

        return ['status' => 'FAILED', 'message' => "Academic percentage (" . number_format($percentage, 1) . "%) is below the minimum requirement of $minPercentage%."];
    }

    private function evaluateGender(array $profile, ?string $genderRequirement): array {
        if (empty($genderRequirement)) {
            return ['status' => 'MATCHED', 'message' => 'No gender restrictions.'];
        }
        if (empty($profile['gender'])) {
            return ['status' => 'MISSING', 'message' => 'Gender is not specified.'];
        }

        if (strtolower($profile['gender']) === strtolower($genderRequirement)) {
            return ['status' => 'MATCHED', 'message' => 'Gender criteria met.'];
        }

        return ['status' => 'FAILED', 'message' => "This opportunity is restricted to $genderRequirement candidates."];
    }

    private function evaluateLanguages(array $profile, array $schLanguages): array {
        if (empty($schLanguages)) {
            return ['status' => 'MATCHED', 'message' => 'No language test requirements.'];
        }

        foreach ($schLanguages as $req) {
            $testName = strtolower($req['test_name']);
            $minScore = (float)$req['minimum_score'];
            $isRequired = (bool)$req['is_required'];

            $userScore = null;
            if ($testName === 'ielts' && !empty($profile['ielts_score'])) {
                $userScore = (float)$profile['ielts_score'];
            } elseif ($testName === 'toefl' && !empty($profile['toefl_score'])) {
                $userScore = (float)$profile['toefl_score'];
            } elseif ($testName === 'pte' && !empty($profile['pte_score'])) {
                $userScore = (float)$profile['pte_score'];
            } elseif ($testName === 'duolingo' && !empty($profile['duolingo_score'])) {
                $userScore = (float)$profile['duolingo_score'];
            }

            if ($userScore === null) {
                if ($isRequired) {
                    return ['status' => 'MISSING', 'message' => "Score for required test " . strtoupper($testName) . " ($minScore) is missing."];
                }
                continue;
            }

            if ($userScore < $minScore) {
                return ['status' => 'FAILED', 'message' => "Your " . strtoupper($testName) . " score ($userScore) is below the minimum required score of $minScore."];
            }
        }

        return ['status' => 'MATCHED', 'message' => 'Language requirements met.'];
    }

    private function evaluateDeadline(?string $deadline): array {
        if (empty($deadline)) {
            return ['status' => 'MATCHED', 'message' => 'Open/Rolling admission.'];
        }

        $today = strtotime(date('Y-m-d'));
        $deadlineTime = strtotime($deadline);

        if ($today > $deadlineTime) {
            return ['status' => 'FAILED', 'message' => 'Scholarship application deadline has passed.'];
        }

        return ['status' => 'MATCHED', 'message' => 'Application window is open.'];
    }

    private function evaluateCountryPreference(array $scholarship, array $prefCountries, array $schCountries): array {
        if (empty($prefCountries)) {
            return ['status' => 'MATCHED', 'message' => 'No country destination preferences set.'];
        }

        if (in_array((int)$scholarship['country_id'], $prefCountries, true)) {
            return ['status' => 'MATCHED', 'message' => 'Host country matches your preferred destinations.'];
        }

        foreach ($schCountries as $cid) {
            if (in_array((int)$cid, $prefCountries, true)) {
                return ['status' => 'MATCHED', 'message' => 'Host country matches preferred destinations.'];
            }
        }

        return ['status' => 'FAILED', 'message' => 'Host country does not match your preferred destinations.'];
    }

    private function evaluateFieldPreference(array $schFields, array $prefFields): array {
        if (empty($prefFields) || empty($schFields)) {
            return ['status' => 'MATCHED', 'message' => 'No discipline preferences set.'];
        }

        foreach ($schFields as $fid) {
            if (in_array((int)$fid, $prefFields, true)) {
                return ['status' => 'MATCHED', 'message' => 'Scholarship matches your preferred disciplines.'];
            }
        }

        return ['status' => 'FAILED', 'message' => 'Scholarship does not match preferred disciplines.'];
    }

    private function evaluateFundingPreference(array $scholarship, array $profile, ?array $preferences): array {
        $pref = null;
        if (!empty($profile['preferred_funding_type'])) {
            $pref = $profile['preferred_funding_type'];
        } elseif ($preferences && !empty($preferences['funding_preferences'])) {
            $pref = $preferences['funding_preferences'];
        }

        if (empty($pref)) {
            return ['status' => 'MATCHED', 'message' => 'No funding mode preferences set.'];
        }

        if (strtolower($scholarship['funding_type'] ?? '') === strtolower($pref)) {
            return ['status' => 'MATCHED', 'message' => "Funding mode matches your preference ($pref)."];
        }

        return ['status' => 'FAILED', 'message' => "Funding mode does not match preferred mode ($pref)."];
    }

    /**
     * Recalculate matches for a single scholarship across all users
     */
    public function recalculateForScholarship(int $scholarshipId): array {
        // 1. Fetch scholarship and rules
        $stmtSch = $this->db->prepare("
            SELECT s.*, c.name as country_name 
            FROM scholarships s
            LEFT JOIN countries c ON s.country_id = c.id
            WHERE s.id = :id LIMIT 1
        ");
        $stmtSch->execute(['id' => $scholarshipId]);
        $scholarship = $stmtSch->fetch(PDO::FETCH_ASSOC);

        if (!$scholarship || $scholarship['status'] !== 'published') {
            return ['matched' => 0, 'queued' => 0, 'emails_sent' => 0, 'failures' => 0];
        }

        $stmtRules = $this->db->prepare("SELECT * FROM scholarship_eligibility_rules WHERE scholarship_id = :id LIMIT 1");
        $stmtRules->execute(['id' => $scholarshipId]);
        $rules = $stmtRules->fetch(PDO::FETCH_ASSOC) ?: [];

        $schCountries = $this->db->query("SELECT country_id FROM scholarship_countries WHERE scholarship_id = $scholarshipId")->fetchAll(PDO::FETCH_COLUMN);
        $schFields = $this->db->query("SELECT field_of_study_id FROM scholarship_fields WHERE scholarship_id = $scholarshipId")->fetchAll(PDO::FETCH_COLUMN);
        $schDegrees = $this->db->query("SELECT degree_level FROM scholarship_degree_levels WHERE scholarship_id = $scholarshipId")->fetchAll(PDO::FETCH_COLUMN);
        $schNationalities = $this->db->query("SELECT country_id FROM scholarship_eligible_nationalities WHERE scholarship_id = $scholarshipId")->fetchAll(PDO::FETCH_COLUMN);
        $schLanguages = $this->db->query("SELECT * FROM scholarship_languages WHERE scholarship_id = $scholarshipId")->fetchAll(PDO::FETCH_ASSOC);
        $schStates = $this->db->query("SELECT state_id FROM scholarship_states WHERE scholarship_id = $scholarshipId")->fetchAll(PDO::FETCH_COLUMN);
        $schInstitutions = $this->db->query("SELECT institution_id FROM scholarship_institutions WHERE scholarship_id = $scholarshipId")->fetchAll(PDO::FETCH_COLUMN);

        $schData = [
            'scholarship' => $scholarship,
            'rules' => $rules,
            'countries' => $schCountries,
            'fields' => $schFields,
            'degrees' => $schDegrees,
            'nationalities' => $schNationalities,
            'languages' => $schLanguages,
            'states' => $schStates,
            'institutions' => $schInstitutions
        ];

        // 2. Fetch all visitor user IDs and emails
        $users = $this->db->query("
            SELECT u.id, u.email 
            FROM users u 
            JOIN roles r ON u.role_id = r.id 
            WHERE r.name = 'visitor' AND u.status = 'active'
        ")->fetchAll(PDO::FETCH_ASSOC);

        if (empty($users)) {
            return ['matched' => 0, 'queued' => 0, 'emails_sent' => 0, 'failures' => 0];
        }

        $matchedCount = 0;
        $queuedCount = 0;
        $emailsSent = 0;
        $failuresCount = 0;

        // Process in chunks of 200 users to prevent timeouts
        $chunks = array_chunk($users, 200);
        foreach ($chunks as $chunk) {
            $chunkUserIds = array_column($chunk, 'id');
            $userIdsString = implode(',', array_map('intval', $chunkUserIds));

            // Preload profiles
            $profiles = $this->db->query("SELECT * FROM student_profiles WHERE user_id IN ($userIdsString)")->fetchAll(PDO::FETCH_ASSOC);
            $profilesMap = [];
            foreach ($profiles as $p) {
                $profilesMap[$p['user_id']] = $p;
            }

            // Preload educations with institution state
            $educations = $this->db->query("
                SELECT er.*, inst.state_id as inst_state_id 
                FROM education_records er
                LEFT JOIN institutions inst ON er.institution_id = inst.id
                WHERE er.user_id IN ($userIdsString)
                ORDER BY er.is_current DESC, er.end_date DESC, er.start_date DESC
            ")->fetchAll(PDO::FETCH_ASSOC);
            $eduMap = [];
            foreach ($educations as $e) {
                if (!isset($eduMap[$e['user_id']])) {
                    $eduMap[$e['user_id']] = $e; // keep only latest/current
                }
            }

            // Preload preferences
            $preferences = $this->db->query("SELECT * FROM user_preferences WHERE user_id IN ($userIdsString)")->fetchAll(PDO::FETCH_ASSOC);
            $prefMap = [];
            foreach ($preferences as $pr) {
                $prefMap[$pr['user_id']] = $pr;
            }

            // Preload preferred countries
            $prefCountries = $this->db->query("SELECT user_id, country_id FROM user_preferred_countries WHERE user_id IN ($userIdsString)")->fetchAll(PDO::FETCH_ASSOC);
            $prefCountriesMap = [];
            foreach ($prefCountries as $pc) {
                $prefCountriesMap[$pc['user_id']][] = (int)$pc['country_id'];
            }

            // Preload preferred fields
            $prefFields = $this->db->query("SELECT user_id, field_of_study_id FROM user_preferred_fields WHERE user_id IN ($userIdsString)")->fetchAll(PDO::FETCH_ASSOC);
            $prefFieldsMap = [];
            foreach ($prefFields as $pf) {
                $prefFieldsMap[$pf['user_id']][] = (int)$pf['field_of_study_id'];
            }

            // Preload preferred degrees
            $prefDegrees = $this->db->query("SELECT user_id, degree_level FROM user_preferred_degree_levels WHERE user_id IN ($userIdsString)")->fetchAll(PDO::FETCH_ASSOC);
            $prefDegreesMap = [];
            foreach ($prefDegrees as $pd) {
                $prefDegreesMap[$pd['user_id']][] = $pd['degree_level'];
            }

            // Preload user field IDs from education names
            $userFieldIds = [];
            foreach ($chunk as $u) {
                $uid = (int)$u['id'];
                if (isset($eduMap[$uid])) {
                    $stmtF = $this->db->prepare("SELECT id FROM fields_of_study WHERE name = :name LIMIT 1");
                    $stmtF->execute(['name' => $eduMap[$uid]['field_of_study']]);
                    $val = $stmtF->fetchColumn();
                    $userFieldIds[$uid] = $val ? (int)$val : null;
                } else {
                    $userFieldIds[$uid] = null;
                }
            }

            // Execute chunk matches
            $this->db->beginTransaction();
            try {
                $notifQueue = new \App\Services\NotificationQueueService();
                foreach ($chunk as $u) {
                    $uid = (int)$u['id'];
                    $uemail = $u['email'];
                    if (!isset($profilesMap[$uid])) {
                        continue;
                    }

                    $userData = [
                        'profile' => $profilesMap[$uid],
                        'education' => $eduMap[$uid] ?? null,
                        'preferences' => $prefMap[$uid] ?? null,
                        'prefCountries' => $prefCountriesMap[$uid] ?? [],
                        'prefFields' => $prefFieldsMap[$uid] ?? [],
                        'prefDegrees' => $prefDegreesMap[$uid] ?? [],
                        'userFieldId' => $userFieldIds[$uid] ?? null,
                        'userInstStateId' => $eduMap[$uid]['inst_state_id'] ?? null
                    ];

                    $match = $this->matchUserAndScholarship($uid, $scholarshipId, $userData, $schData);
                    
                    if ($match['eligibility_status'] === 'ELIGIBLE') {
                        $match['user_id'] = $uid;
                        $match['scholarship_id'] = $scholarshipId;
                        $this->saveMatch($match);
                        $matchedCount++;

                        // Enqueue notification with deterministic idempotency key
                        $idempotencyKey = "new_match_{$uid}_{$scholarshipId}";
                        $enqueued = $notifQueue->enqueue(
                            $uid,
                            $scholarshipId,
                            'NEW_MATCH',
                            'email',
                            $uemail,
                            'New Scholarship Match: ' . $scholarship['title'],
                            [
                                'title' => $scholarship['title'],
                                'provider' => $scholarship['provider_name'],
                                'country' => $scholarship['country_name'] ?? 'Multi-Country',
                                'deadline' => $scholarship['application_deadline'],
                                'score' => $match['match_score'],
                                'detail_url' => url('/scholarships/' . $scholarship['slug'])
                            ],
                            $idempotencyKey
                        );
                        if ($enqueued) {
                            $queuedCount++;
                            $emailsSent++;
                        }
                    }
                }
                $this->db->commit();
            } catch (\Exception $e) {
                $this->db->rollBack();
                $failuresCount += count($chunk);
                \App\Services\Logger::error("Failed to match chunk for scholarship $scholarshipId: " . $e->getMessage());
            }
        }

        return [
            'matched' => $matchedCount,
            'queued' => $queuedCount,
            'emails_sent' => $emailsSent,
            'failures' => $failuresCount
        ];
    }

    private function emptyResponse(string $status, string $message): array {
        return [
            'match_score' => 0,
            'eligibility_status' => $status,
            'recommendation_level' => 'NOT_RECOMMENDED',
            'matched_criteria' => [],
            'failed_criteria' => ['system' => $message],
            'missing_criteria' => [],
            'calculated_at' => date('Y-m-d H:i:s')
        ];
    }
}
