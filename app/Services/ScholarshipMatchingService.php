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
        } else {
            $stmt = $this->db->prepare("SELECT * FROM student_profiles WHERE user_id = :user_id LIMIT 1");
            $stmt->execute(['user_id' => $userId]);
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$profile) {
                return $this->emptyResponse('INSUFFICIENT_DATA', 'No student profile exists. Please complete your profile.');
            }

            $stmtEdu = $this->db->prepare("
                SELECT * FROM education_records 
                WHERE user_id = :user_id 
                ORDER BY is_current DESC, end_date DESC, start_date DESC 
                LIMIT 1
            ");
            $stmtEdu->execute(['user_id' => $userId]);
            $education = $stmtEdu->fetch(PDO::FETCH_ASSOC) ?: null;

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

        // Evaluator 2: Age
        $ageRes = $this->evaluateAge($profile, $rules['minimum_age'] ?? null, $rules['maximum_age'] ?? null);
        $evaluations['age'] = $ageRes;

        // Evaluator 3: Degree
        $degRes = $this->evaluateDegree($education, $schDegrees);
        $evaluations['degree'] = $degRes;

        // Evaluator 4: Field of study
        $fieldRes = $this->evaluateField($education, $schFields, $userFieldId);
        $evaluations['field'] = $fieldRes;

        // Evaluator 5: CGPA & scale
        $cgpaRes = $this->evaluateCgpa($education, $rules['minimum_cgpa'] ?? null, $rules['cgpa_scale'] ?? null);
        $evaluations['cgpa'] = $cgpaRes;

        // Evaluator 6: Percentage
        $pctRes = $this->evaluatePercentage($education, $rules['minimum_percentage'] ?? null);
        $evaluations['percentage'] = $pctRes;

        // Evaluator 7: Gender
        $genderRes = $this->evaluateGender($profile, $rules['gender_requirement'] ?? null);
        $evaluations['gender'] = $genderRes;

        // Evaluator 8: Language tests (IELTS, TOEFL, PTE, Duolingo)
        $langRes = $this->evaluateLanguages($profile, $schLanguages);
        $evaluations['languages'] = $langRes;

        // Evaluator 9: Deadline
        $deadlineRes = $this->evaluateDeadline($scholarship['application_deadline']);
        $evaluations['deadline'] = $deadlineRes;

        // Evaluator 10: Country match preference (Soft)
        $countryPrefRes = $this->evaluateCountryPreference($scholarship, $prefCountries, $schCountries);
        $evaluations['country_pref'] = $countryPrefRes;

        // Evaluator 11: Field match preference (Soft)
        $fieldPrefRes = $this->evaluateFieldPreference($schFields, $prefFields);
        $evaluations['field_pref'] = $fieldPrefRes;

        // Evaluator 12: Funding preference (Soft)
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

        // 5. Determine eligibility status
        $hardKeys = ['nationality', 'age', 'degree', 'field', 'cgpa', 'percentage', 'gender', 'languages', 'deadline'];
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

        if ($natRes['status'] === 'MATCHED') $score += 12;
        if ($ageRes['status'] === 'MATCHED') $score += 12;
        if ($degRes['status'] === 'MATCHED') $score += 12;
        if ($fieldRes['status'] === 'MATCHED') $score += 12;

        if ($cgpaRes['status'] === 'MATCHED' || $pctRes['status'] === 'MATCHED') {
            $score += 12;
        }

        if ($langRes['status'] === 'MATCHED') $score += 10;

        if ($countryPrefRes['status'] === 'MATCHED') $score += 10;
        if ($fieldPrefRes['status'] === 'MATCHED') $score += 10;
        if ($fundingPrefRes['status'] === 'MATCHED') $score += 10;

        // If NOT_ELIGIBLE, final score is capped to 0
        $finalScore = ($eligibilityStatus === 'NOT_ELIGIBLE') ? 0 : $score;

        // 7. Recommendation Level
        $recLevel = 'NOT_RECOMMENDED';
        if ($eligibilityStatus === 'ELIGIBLE') {
            if ($finalScore >= 85) {
                $recLevel = 'HIGHLY_RECOMMENDED';
            } elseif ($finalScore >= 70) {
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
            SELECT * FROM education_records 
            WHERE user_id = :user_id 
            ORDER BY is_current DESC, end_date DESC, start_date DESC 
            LIMIT 1
        ");
        $stmtEdu->execute(['user_id' => $userId]);
        $education = $stmtEdu->fetch(PDO::FETCH_ASSOC) ?: null;

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
            'userFieldId' => $userFieldId
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
                    'languages' => $languagesMap[$sid] ?? []
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

    private function evaluateNationality(array $profile, array $eligibleNationalities): array {
        if (empty($eligibleNationalities)) {
            return ['status' => 'MATCHED', 'message' => 'Open to all nationalities.'];
        }
        if (empty($profile['nationality_country_id'])) {
            return ['status' => 'MISSING', 'message' => 'Nationality is not provided.'];
        }
        if (in_array((int)$profile['nationality_country_id'], $eligibleNationalities)) {
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

    private function evaluateDegree(?array $education, array $requiredDegrees): array {
        if (empty($requiredDegrees)) {
            return ['status' => 'MATCHED', 'message' => 'Open to all degree levels.'];
        }
        if (!$education) {
            return ['status' => 'MISSING', 'message' => 'Education history is not provided.'];
        }

        if (in_array($education['degree_level'], $requiredDegrees)) {
            return ['status' => 'MATCHED', 'message' => "Current degree level ({$education['degree_level']}) matches target degrees."];
        }

        return ['status' => 'FAILED', 'message' => "Degree level ({$education['degree_level']}) does not match requirements."];
    }

    private function evaluateField(?array $education, array $requiredFields, ?int $userFieldId): array {
        if (empty($requiredFields)) {
            return ['status' => 'MATCHED', 'message' => 'Open to all fields of study.'];
        }
        if (!$education) {
            return ['status' => 'MISSING', 'message' => 'Education history is not provided.'];
        }

        if ($userFieldId !== null && in_array($userFieldId, $requiredFields)) {
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

        if (in_array((int)$scholarship['country_id'], $prefCountries)) {
            return ['status' => 'MATCHED', 'message' => 'Host country matches your preferred destinations.'];
        }

        foreach ($schCountries as $cid) {
            if (in_array((int)$cid, $prefCountries)) {
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
            if (in_array((int)$fid, $prefFields)) {
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

        if (strtolower($scholarship['funding_type']) === strtolower($pref)) {
            return ['status' => 'MATCHED', 'message' => "Funding mode matches your preference ($pref)."];
        }

        return ['status' => 'FAILED', 'message' => "Funding mode does not match preferred mode ($pref)."];
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
