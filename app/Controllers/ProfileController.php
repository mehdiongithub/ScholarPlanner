<?php

namespace App\Controllers;

use App\Services\Auth;
use App\Services\Database;
use App\Services\ProfileCompletionService;
use App\Helpers\Security;
use App\Helpers\AgeCalculator;
use Exception;
use PDO;

class ProfileController {
    /**
     * Display the read-only profile dashboard
     */
    public function show(): void {
        Auth::requireAuth();
        $userId = Auth::userId();
        $db = Database::connection();

        // 1. Fetch user core info
        $stmtUser = $db->prepare("
            SELECT u.id, u.first_name, u.last_name, u.email, u.phone, u.status,
                   r.name as role_name,
                   p.date_of_birth, p.gender, p.bio, p.profile_completion_percentage,
                   c_nat.name as nationality_country,
                   c_res.name as residence_country,
                   s.name as residence_state,
                   city.name as city_name
            FROM users u
            LEFT JOIN student_profiles p ON u.id = p.user_id
            LEFT JOIN roles r ON u.role_id = r.id
            LEFT JOIN countries c_nat ON p.nationality_country_id = c_nat.id
            LEFT JOIN countries c_res ON p.residence_country_id = c_res.id
            LEFT JOIN states s ON p.residence_state_id = s.id
            LEFT JOIN cities city ON p.city_id = city.id
            WHERE u.id = :user_id
            LIMIT 1
        ");
        $stmtUser->execute(['user_id' => $userId]);
        $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            Auth::logout();
            header("Location: " . url('/login'));
            $this->halt("Redirect to login");
        }

        // 2. Calculate dynamic age
        $age = AgeCalculator::calculateAge($user['date_of_birth'] ?? null);

        // 3. Fetch education records
        $stmtEdu = $db->prepare("
            SELECT er.*, c.name as country_name, inst.institution_type
            FROM education_records er
            LEFT JOIN countries c ON er.country_id = c.id
            LEFT JOIN institutions inst ON er.institution_id = inst.id
            WHERE er.user_id = :user_id 
            ORDER BY er.start_date DESC
        ");
        $stmtEdu->execute(['user_id' => $userId]);
        $education = $stmtEdu->fetchAll(PDO::FETCH_ASSOC);


        // 4. Fetch preferred countries
        $stmtPrefCountries = $db->prepare("
            SELECT c.id, c.name 
            FROM user_preferred_countries upc
            JOIN countries c ON upc.country_id = c.id
            WHERE upc.user_id = :user_id
            ORDER BY c.name ASC
        ");
        $stmtPrefCountries->execute(['user_id' => $userId]);
        $prefCountries = $stmtPrefCountries->fetchAll(PDO::FETCH_ASSOC);

        // 5. Fetch preferred fields of study
        $stmtPrefFields = $db->prepare("
            SELECT f.id, f.name 
            FROM user_preferred_fields upf
            JOIN fields_of_study f ON upf.field_of_study_id = f.id
            WHERE upf.user_id = :user_id
            ORDER BY f.name ASC
        ");
        $stmtPrefFields->execute(['user_id' => $userId]);
        $prefFields = $stmtPrefFields->fetchAll(PDO::FETCH_ASSOC);

        // 6. Fetch preferred degree levels
        $stmtPrefDegrees = $db->prepare("
            SELECT degree_level 
            FROM user_preferred_degree_levels 
            WHERE user_id = :user_id
            ORDER BY degree_level ASC
        ");
        $stmtPrefDegrees->execute(['user_id' => $userId]);
        $prefDegrees = $stmtPrefDegrees->fetchAll(PDO::FETCH_COLUMN);

        // 7. Calculate completeness & fetch preferences
        $completion = ProfileCompletionService::calculate($userId);

        $stmtNotify = $db->prepare("SELECT * FROM notification_preferences WHERE user_id = :user_id");
        $stmtNotify->execute(['user_id' => $userId]);
        $notifyData = $stmtNotify->fetchAll(PDO::FETCH_ASSOC);

        $notificationSettings = [];
        foreach ($notifyData as $n) {
            $notificationSettings[$n['notification_type']] = [
                'email' => (bool)$n['email_enabled'],
                'whatsapp' => (bool)$n['whatsapp_enabled']
            ];
        }

        $readinessService = new \App\Services\DocumentReadinessService();
        $docReadiness = $readinessService->calculateGlobal($userId);

        view('profile.show', [
            'user' => $user,
            'age' => $age,
            'education' => $education,
            'prefCountries' => $prefCountries,
            'prefFields' => $prefFields,
            'prefDegrees' => $prefDegrees,
            'completion' => $completion,
            'docReadiness' => $docReadiness,
            'notificationSettings' => $notificationSettings
        ]);
    }

    /**
     * Display the editable profile forms
     */
    public function edit(): void {
        Auth::requireAuth();
        $userId = Auth::userId();
        $db = Database::connection();

        // 1. Fetch user core info
        $stmtUser = $db->prepare("
            SELECT u.id, u.first_name, u.last_name, u.email, u.phone,
                   p.date_of_birth, p.gender, p.bio, p.profile_completion_percentage,
                   p.nationality_country_id, p.residence_country_id, p.residence_state_id, p.city_id,
                   p.preferred_funding_type, p.preferred_start_year,
                   p.ielts_score, p.toefl_score, p.pte_score, p.duolingo_score,
                   p.address, p.postal_code
            FROM users u
            LEFT JOIN student_profiles p ON u.id = p.user_id
            WHERE u.id = :user_id
            LIMIT 1
        ");


        $stmtUser->execute(['user_id' => $userId]);
        $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

        // 2. Fetch all countries and fields of study
        $countries = $db->query("SELECT id, name FROM countries ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $fieldsOfStudy = $db->query("SELECT id, name FROM fields_of_study ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

        // 3. Load pre-selected states and cities
        $states = [];
        $cities = [];

        if (!empty($user['residence_country_id'])) {
            $stmtStates = $db->prepare("SELECT id, name FROM states WHERE country_id = :country_id ORDER BY name ASC");
            $stmtStates->execute(['country_id' => $user['residence_country_id']]);
            $states = $stmtStates->fetchAll(PDO::FETCH_ASSOC);
        }

        if (!empty($user['residence_state_id'])) {
            $stmtCities = $db->prepare("SELECT id, name FROM cities WHERE state_id = :state_id ORDER BY name ASC");
            $stmtCities->execute(['state_id' => $user['residence_state_id']]);
            $cities = $stmtCities->fetchAll(PDO::FETCH_ASSOC);
        }

        // 4. Fetch education records
        $stmtEdu = $db->prepare("
            SELECT er.*, c.name as country_name, inst.institution_type
            FROM education_records er
            LEFT JOIN countries c ON er.country_id = c.id
            LEFT JOIN institutions inst ON er.institution_id = inst.id
            WHERE er.user_id = :user_id 
            ORDER BY er.start_date DESC
        ");
        $stmtEdu->execute(['user_id' => $userId]);
        $education = $stmtEdu->fetchAll(PDO::FETCH_ASSOC);


        // 5. Fetch selected preferences
        $prefCountries = $db->query("SELECT country_id FROM user_preferred_countries WHERE user_id = $userId")->fetchAll(PDO::FETCH_COLUMN);
        $prefFields = $db->query("SELECT field_of_study_id FROM user_preferred_fields WHERE user_id = $userId")->fetchAll(PDO::FETCH_COLUMN);
        $prefDegrees = $db->query("SELECT degree_level FROM user_preferred_degree_levels WHERE user_id = $userId")->fetchAll(PDO::FETCH_COLUMN);

        // 6. Fetch notification preferences
        $stmtNotify = $db->prepare("SELECT * FROM notification_preferences WHERE user_id = :user_id");
        $stmtNotify->execute(['user_id' => $userId]);
        $notifyData = $stmtNotify->fetchAll(PDO::FETCH_ASSOC);

        $notificationSettings = [];
        foreach ($notifyData as $n) {
            $notificationSettings[$n['notification_type']] = [
                'email' => (bool)$n['email_enabled'],
                'whatsapp' => (bool)$n['whatsapp_enabled']
            ];
        }

        view('profile.edit', [
            'user' => $user,
            'countries' => $countries,
            'fieldsOfStudy' => $fieldsOfStudy,
            'states' => $states,
            'cities' => $cities,
            'education' => $education,
            'prefCountries' => $prefCountries,
            'prefFields' => $prefFields,
            'prefDegrees' => $prefDegrees,
            'notificationSettings' => $notificationSettings,
            'csrf_token' => Security::csrfToken(),
            'errors' => [],
            'success_message' => $_GET['success'] ?? null
        ]);
    }

    /**
     * POST /profile/update
     * Handle updating names, email, phone, and demographics
     */
    public function update(): void {
        Auth::requireAuth();
        $userId = Auth::userId();
        $db = Database::connection();

        // CSRF Shield
        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            $this->redirectBackWithErrors(['csrf' => 'CSRF verification failed. Please try again.']);
        }

        // Whitelisted inputs
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? Auth::currentUser()['email'] ?? ''));
        $phone = trim($_POST['phone'] ?? '');
        $dob = trim($_POST['date_of_birth'] ?? '');
        $gender = trim($_POST['gender'] ?? '');
        $nationalityCountryId = !empty($_POST['nationality_country_id']) ? (int)$_POST['nationality_country_id'] : null;
        $residenceCountryId = !empty($_POST['residence_country_id']) ? (int)$_POST['residence_country_id'] : null;
        $residenceStateId = !empty($_POST['residence_state_id']) ? (int)$_POST['residence_state_id'] : null;
        $cityId = !empty($_POST['city_id']) ? (int)$_POST['city_id'] : null;
        $bio = trim($_POST['bio'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $postalCode = trim($_POST['postal_code'] ?? '');

        // Validation
        $errors = [];
        if (strlen($address) > 255) {
            $errors['address'] = 'Address must not exceed 255 characters.';
        }
        if (strlen($postalCode) > 20) {
            $errors['postal_code'] = 'Postal Code must not exceed 20 characters.';
        }
        if (empty($firstName)) {

            $errors['first_name'] = 'First name is required.';
        } elseif (strlen($firstName) > 50) {
            $errors['first_name'] = 'First name must not exceed 50 characters.';
        }

        if (empty($lastName)) {
            $errors['last_name'] = 'Last name is required.';
        } elseif (strlen($lastName) > 50) {
            $errors['last_name'] = 'Last name must not exceed 50 characters.';
        }

        if (strlen($bio) > 1000) {
            $errors['bio'] = 'Bio must not exceed 1000 characters.';
        }
        
        if (empty($email)) {
            $errors['email'] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format.';
        } else {
            // Check email uniqueness
            $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = :email AND id != :id");
            $stmt->execute(['email' => $email, 'id' => $userId]);
            if ((int)$stmt->fetchColumn() > 0) {
                $errors['email'] = 'Email already in use.';
            }
        }

        if (empty($phone)) {
            $errors['phone'] = 'Mobile phone number is required.';
        } elseif (!preg_match('/^\+?[0-9]{10,15}$/', $phone)) {
            $errors['phone'] = 'Phone must be in international format (e.g. +923001234567).';
        } else {
            // Check phone uniqueness
            $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE phone = :phone AND id != :id");
            $stmt->execute(['phone' => $phone, 'id' => $userId]);
            if ((int)$stmt->fetchColumn() > 0) {
                $errors['phone'] = 'Phone number already in use.';
            }
        }

        if (!empty($dob)) {
            if (strtotime($dob) > time()) {
                $errors['date_of_birth'] = 'Date of birth cannot be in the future.';
            }
        }

        if (!empty($gender) && !in_array($gender, ['male', 'female', 'other'])) {
            $errors['gender'] = 'Invalid gender selected.';
        }

        if (!empty($errors)) {
            $this->redirectBackWithErrors($errors);
        }

        // Apply changes inside transaction
        try {
            $db->beginTransaction();

            // Update user details
            $stmtUser = $db->prepare("
                UPDATE users 
                SET first_name = :first_name, last_name = :last_name, email = :email, phone = :phone, updated_at = NOW() 
                WHERE id = :id
            ");
            $stmtUser->execute([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone' => $phone,
                'id' => $userId
            ]);

            // Update user session name/email cache
            $_SESSION['user_name'] = $firstName . ' ' . $lastName;
            $_SESSION['user_email'] = $email;

            // Update student profile details
            $stmtProfile = $db->prepare("
                UPDATE student_profiles 
                SET date_of_birth = :dob, gender = :gender, 
                    nationality_country_id = :nat_id, residence_country_id = :res_id, 
                    residence_state_id = :state_id, city_id = :city_id, bio = :bio, 
                    address = :address, postal_code = :postal_code, updated_at = NOW() 
                WHERE user_id = :user_id
            ");
            $stmtProfile->execute([
                'dob' => $dob ?: null,
                'gender' => $gender ?: null,
                'nat_id' => $nationalityCountryId,
                'res_id' => $residenceCountryId,
                'state_id' => $residenceStateId,
                'city_id' => $cityId,
                'bio' => $bio ?: null,
                'address' => $address ?: null,
                'postal_code' => $postalCode ?: null,
                'user_id' => $userId
            ]);


            $db->commit();
            $completion = ProfileCompletionService::calculate($userId);
            $this->invalidateMatches($userId);

            $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Profile updated successfully.', 'completion' => $completion]);
                $this->halt("AJAX success response");
            }


            header("Location: " . url('/profile/edit?success=Profile updated successfully.'));
            $this->halt("Redirect profile update success");


        } catch (Exception $e) {
            $db->rollBack();
            \App\Services\Logger::error("Profile update failed for user $userId: " . $e->getMessage());
            $this->redirectBackWithErrors(['system' => 'Failed to save changes. Please try again later.']);
        }
    }

    /**
     * POST /profile/education/add
     */
    public function addEducation(): void {
        Auth::requireAuth();
        $userId = Auth::userId();
        $db = Database::connection();

        // CSRF Shield
        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            $this->redirectBackWithErrors(['csrf' => 'CSRF verification failed. Please try again.']);
        }

        // Whitelisted inputs
        $institutionSelect = $_POST['institution_select'] ?? null;
        $customInstName = trim($_POST['custom_institution_name'] ?? '');
        $degreeLevel = trim($_POST['degree_level'] ?? '');
        $degreeTitle = trim($_POST['degree_title'] ?? '');
        $fieldOfStudy = trim($_POST['field_of_study'] ?? '');
        $countryId = !empty($_POST['country_id']) ? (int)$_POST['country_id'] : null;
        $stateId = !empty($_POST['state_id']) ? (int)$_POST['state_id'] : null;
        $cityId = !empty($_POST['city_id']) ? (int)$_POST['city_id'] : null;
        $startDate = trim($_POST['start_date'] ?? '');
        $endDate = trim($_POST['end_date'] ?? '');
        $graduationStatus = trim($_POST['graduation_status'] ?? 'graduated');
        $cgpa = !empty($_POST['cgpa']) ? (float)$_POST['cgpa'] : null;
        $cgpaScale = !empty($_POST['cgpa_scale']) ? (float)$_POST['cgpa_scale'] : null;
        $percentage = !empty($_POST['percentage']) ? (float)$_POST['percentage'] : null;
        $resultStatus = trim($_POST['result_status'] ?? 'declared');
        $isCurrent = isset($_POST['is_current']) ? 1 : 0;
        $institutionType = trim($_POST['institution_type'] ?? 'university');
        $currentSemester = !empty($_POST['current_semester']) ? trim($_POST['current_semester']) : null;
        $passingYear = !empty($_POST['passing_year']) ? (int)$_POST['passing_year'] : null;

        if (in_array($institutionType, ['school', 'college', 'other'])) {
            $institutionSelect = 'other';
            $degreeTitle = $degreeLevel ?: 'N/A';
            $fieldOfStudy = 'General';
        }

        $institutionId = null;
        $institutionName = '';
        $errors = [];


        // Conditional validations for semester and passing year
        if ($graduationStatus === 'ongoing' && $institutionType === 'university') {
            if (empty($currentSemester)) {
                $errors['current_semester'] = 'Current semester is required.';
            }
        }
        if ($institutionType === 'university' && ($graduationStatus === 'graduated' || $graduationStatus === 'ongoing')) {
            if (empty($passingYear)) {
                $errors['passing_year'] = 'Graduation / Passing Year is required.';
            } elseif ($passingYear < (int)date('Y') - 100 || $passingYear > (int)date('Y') + 10) {
                $errors['passing_year'] = 'Please enter a valid passing year.';
            }
        }

        if ($institutionSelect !== null && $institutionSelect !== '') {
            if ($institutionSelect === 'other') {
                $institutionName = $customInstName;
                if (empty($institutionName)) {
                    $errors['custom_institution_name'] = 'Custom institution name is required.';
                }
                if (in_array($institutionType, ['university', 'school', 'college', 'other'])) {
                    if ($countryId === null || $countryId <= 0) {
                        $errors['edu_country_id'] = 'Institution country is required.';
                    }
                    if ($stateId === null || $stateId <= 0) {
                        $errors['edu_state_id'] = 'Institution state is required.';
                    }
                }
            } else {
                $institutionId = (int)$institutionSelect;
                $stmtName = $db->prepare("SELECT name FROM institutions WHERE id = :id LIMIT 1");
                $stmtName->execute(['id' => $institutionId]);
                $institutionName = $stmtName->fetchColumn() ?: '';
            }
        } else {
            $institutionName = trim($_POST['institution_name'] ?? '');
        }

        // Standard validation
        $errors = array_merge($errors, $this->validateEducation($institutionName, $degreeLevel, $degreeTitle, $fieldOfStudy, $cgpa, $cgpaScale, $percentage, $startDate, $endDate));

        if (!empty($errors)) {
            $this->redirectBackWithErrors($errors);
        }


        try {
            $db->beginTransaction();

            // Resolve/Create Pending Institution if needed
            if ($institutionSelect === 'other') {
                $type = trim($_POST['institution_type'] ?? '');
                if (empty($type)) {
                    if ($degreeLevel === 'High School') {
                        $type = 'school';
                    } elseif (in_array($degreeLevel, ['Intermediate / College', 'Diploma', 'Associate Degree'])) {
                        $type = 'college';
                    } else {
                        $type = 'university';
                    }
                }


                $stmtExist = $db->prepare("
                    SELECT id FROM institutions 
                    WHERE LOWER(name) = :name 
                      AND institution_type = :type 
                      AND (country_id = :country OR (country_id IS NULL AND :country2 IS NULL))
                      AND (state_id = :state OR (state_id IS NULL AND :state2 IS NULL))
                    LIMIT 1
                ");
                $stmtExist->execute([
                    'name' => strtolower($institutionName),
                    'type' => $type,
                    'country' => $countryId,
                    'country2' => $countryId,
                    'state' => $stateId,
                    'state2' => $stateId
                ]);
                $existingInstId = $stmtExist->fetchColumn();

                if ($existingInstId) {
                    $institutionId = (int)$existingInstId;
                } else {
                    $stmtNew = $db->prepare("
                        INSERT INTO institutions (name, institution_type, country_id, state_id, city_id, coverage_type, status, created_by, created_at, updated_at) 
                        VALUES (:name, :type, :country_id, :state_id, :city_id, 'state', 'pending', :created_by, NOW(), NOW())
                    ");
                    $stmtNew->execute([
                        'name' => $institutionName,
                        'type' => $type,
                        'country_id' => $countryId,
                        'state_id' => $stateId,
                        'city_id' => $cityId,
                        'created_by' => $userId
                    ]);

                    $institutionId = (int)$db->lastInsertId();

                    if ($stateId !== null) {
                        $stmtInstState = $db->prepare("
                            INSERT IGNORE INTO institution_states (institution_id, state_id) 
                            VALUES (:inst_id, :state_id)
                        ");
                        $stmtInstState->execute([
                            'inst_id' => $institutionId,
                            'state_id' => $stateId
                        ]);
                    }
                }
            }

            // Enforce single current check
            if ($isCurrent === 1) {
                $db->prepare("UPDATE education_records SET is_current = 0 WHERE user_id = :uid")->execute(['uid' => $userId]);
            }

            $stmt = $db->prepare("
                INSERT INTO education_records 
                (user_id, institution_id, institution_name, degree_level, degree_title, field_of_study, country_id, 
                 start_date, end_date, graduation_status, cgpa, cgpa_scale, percentage, result_status, is_current, 
                 current_semester, passing_year, created_at, updated_at) 
                VALUES 
                (:uid, :inst_id, :inst, :lvl, :title, :field, :country, :start, :end, :status, :cgpa, :scale, :pct, :res_status, :is_curr, 
                 :semester, :passing_yr, NOW(), NOW())
            ");

            $stmt->execute([
                'uid' => $userId,
                'inst_id' => $institutionId,
                'inst' => $institutionName,
                'lvl' => $degreeLevel,
                'title' => $degreeTitle,
                'field' => $fieldOfStudy,
                'country' => $countryId,
                'start' => $startDate ?: null,
                'end' => $endDate ?: null,
                'status' => $graduationStatus,
                'cgpa' => $cgpa,
                'scale' => $cgpaScale,
                'pct' => $percentage,
                'res_status' => $resultStatus,
                'is_curr' => $isCurrent,
                'semester' => $currentSemester,
                'passing_yr' => $passingYear
            ]);


            $db->commit();
            $completion = ProfileCompletionService::calculate($userId);
            $this->invalidateMatches($userId);

            $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
            if ($isAjax) {
                // Fetch updated education records
                $stmtEdu = $db->prepare("
                    SELECT er.*, c.name as country_name, inst.institution_type
                    FROM education_records er
                    LEFT JOIN countries c ON er.country_id = c.id
                    LEFT JOIN institutions inst ON er.institution_id = inst.id
                    WHERE er.user_id = :user_id 
                    ORDER BY er.start_date DESC
                ");
                $stmtEdu->execute(['user_id' => $userId]);

                $education = $stmtEdu->fetchAll(PDO::FETCH_ASSOC);

                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true, 
                    'message' => 'Education record added successfully.',
                    'education' => $education,
                    'completion' => $completion
                ]);
                $this->halt("AJAX success response");
            }


            header("Location: " . url('/profile/edit?success=Education record added successfully.'));
            if (defined('TESTING_MODE') && TESTING_MODE) {
                return;
            }
            exit();

        } catch (Exception $e) {
            $db->rollBack();
            \App\Services\Logger::error("Failed to add education for user $userId: " . $e->getMessage());
            $this->redirectBackWithErrors(['education' => 'An error occurred while saving your education history. Please try again.']);
        }
    }


    /**
     * POST /profile/education/update
     */
    public function updateEducation(): void {
        Auth::requireAuth();
        $userId = Auth::userId();
        $db = Database::connection();

        // CSRF Shield
        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            $this->redirectBackWithErrors(['csrf' => 'CSRF verification failed. Please try again.']);
        }

        $id = (int)($_POST['id'] ?? 0);

        // IDOR Guard: Verify ownership
        $stmtCheck = $db->prepare("SELECT user_id FROM education_records WHERE id = :id LIMIT 1");
        $stmtCheck->execute(['id' => $id]);
        $recordOwner = $stmtCheck->fetchColumn();

        if (!$recordOwner || (int)$recordOwner !== $userId) {
            $errors['unauthorized'] = "Unauthorized access attempt.";
            $this->redirectBackWithErrors($errors);
        }

        // Whitelisted inputs
        $institutionSelect = $_POST['institution_select'] ?? null;
        $customInstName = trim($_POST['custom_institution_name'] ?? '');
        $degreeLevel = trim($_POST['degree_level'] ?? '');
        $degreeTitle = trim($_POST['degree_title'] ?? '');
        $fieldOfStudy = trim($_POST['field_of_study'] ?? '');
        $countryId = !empty($_POST['country_id']) ? (int)$_POST['country_id'] : null;
        $stateId = !empty($_POST['state_id']) ? (int)$_POST['state_id'] : null;
        $cityId = !empty($_POST['city_id']) ? (int)$_POST['city_id'] : null;
        $startDate = trim($_POST['start_date'] ?? '');
        $endDate = trim($_POST['end_date'] ?? '');
        $graduationStatus = trim($_POST['graduation_status'] ?? 'graduated');
        $cgpa = !empty($_POST['cgpa']) ? (float)$_POST['cgpa'] : null;
        $cgpaScale = !empty($_POST['cgpa_scale']) ? (float)$_POST['cgpa_scale'] : null;
        $percentage = !empty($_POST['percentage']) ? (float)$_POST['percentage'] : null;
        $resultStatus = trim($_POST['result_status'] ?? 'declared');
        $isCurrent = isset($_POST['is_current']) ? 1 : 0;
        $institutionType = trim($_POST['institution_type'] ?? 'university');
        $currentSemester = !empty($_POST['current_semester']) ? trim($_POST['current_semester']) : null;
        $passingYear = !empty($_POST['passing_year']) ? (int)$_POST['passing_year'] : null;

        if (in_array($institutionType, ['school', 'college', 'other'])) {
            $institutionSelect = 'other';
            $degreeTitle = $degreeLevel ?: 'N/A';
            $fieldOfStudy = 'General';
        }

        $institutionId = null;
        $institutionName = '';
        $errors = [];


        // Conditional validations for semester and passing year
        if ($graduationStatus === 'ongoing' && $institutionType === 'university') {
            if (empty($currentSemester)) {
                $errors['current_semester'] = 'Current semester is required.';
            }
        }
        if ($institutionType === 'university' && ($graduationStatus === 'graduated' || $graduationStatus === 'ongoing')) {
            if (empty($passingYear)) {
                $errors['passing_year'] = 'Graduation / Passing Year is required.';
            } elseif ($passingYear < (int)date('Y') - 100 || $passingYear > (int)date('Y') + 10) {
                $errors['passing_year'] = 'Please enter a valid passing year.';
            }
        }

        if ($institutionSelect !== null && $institutionSelect !== '') {
            if ($institutionSelect === 'other') {
                $institutionName = $customInstName;
                if (empty($institutionName)) {
                    $errors['custom_institution_name'] = 'Custom institution name is required.';
                }
                if (in_array($institutionType, ['university', 'school', 'college', 'other'])) {
                    if ($countryId === null || $countryId <= 0) {
                        $errors['edu_country_id'] = 'Institution country is required.';
                    }
                    if ($stateId === null || $stateId <= 0) {
                        $errors['edu_state_id'] = 'Institution state is required.';
                    }
                }
            } else {
                $institutionId = (int)$institutionSelect;
                $stmtName = $db->prepare("SELECT name FROM institutions WHERE id = :id LIMIT 1");
                $stmtName->execute(['id' => $institutionId]);
                $institutionName = $stmtName->fetchColumn() ?: '';
            }
        } else {
            $institutionName = trim($_POST['institution_name'] ?? '');
        }

        // Standard validation
        $errors = array_merge($errors, $this->validateEducation($institutionName, $degreeLevel, $degreeTitle, $fieldOfStudy, $cgpa, $cgpaScale, $percentage, $startDate, $endDate));

        if (!empty($errors)) {
            $this->redirectBackWithErrors($errors);
        }


        try {
            $db->beginTransaction();

            // Resolve/Create Pending Institution if needed
            if ($institutionSelect === 'other') {
                $type = trim($_POST['institution_type'] ?? '');
                if (empty($type)) {
                    if ($degreeLevel === 'High School') {
                        $type = 'school';
                    } elseif (in_array($degreeLevel, ['Intermediate / College', 'Diploma', 'Associate Degree'])) {
                        $type = 'college';
                    } else {
                        $type = 'university';
                    }
                }



                $stmtExist = $db->prepare("
                    SELECT id FROM institutions 
                    WHERE LOWER(name) = :name 
                      AND institution_type = :type 
                      AND (country_id = :country OR (country_id IS NULL AND :country2 IS NULL))
                      AND (state_id = :state OR (state_id IS NULL AND :state2 IS NULL))
                    LIMIT 1
                ");
                $stmtExist->execute([
                    'name' => strtolower($institutionName),
                    'type' => $type,
                    'country' => $countryId,
                    'country2' => $countryId,
                    'state' => $stateId,
                    'state2' => $stateId
                ]);
                $existingInstId = $stmtExist->fetchColumn();

                if ($existingInstId) {
                    $institutionId = (int)$existingInstId;
                } else {
                    $stmtNew = $db->prepare("
                        INSERT INTO institutions (name, institution_type, country_id, state_id, city_id, coverage_type, status, created_by, created_at, updated_at) 
                        VALUES (:name, :type, :country_id, :state_id, :city_id, 'state', 'pending', :created_by, NOW(), NOW())
                    ");
                    $stmtNew->execute([
                        'name' => $institutionName,
                        'type' => $type,
                        'country_id' => $countryId,
                        'state_id' => $stateId,
                        'city_id' => $cityId,
                        'created_by' => $userId
                    ]);

                    $institutionId = (int)$db->lastInsertId();

                    if ($stateId !== null) {
                        $stmtInstState = $db->prepare("
                            INSERT IGNORE INTO institution_states (institution_id, state_id) 
                            VALUES (:inst_id, :state_id)
                        ");
                        $stmtInstState->execute([
                            'inst_id' => $institutionId,
                            'state_id' => $stateId
                        ]);
                    }
                }
            }

            // Enforce single current check
            if ($isCurrent === 1) {
                $db->prepare("UPDATE education_records SET is_current = 0 WHERE user_id = :uid")->execute(['uid' => $userId]);
            }

            $stmt = $db->prepare("
                UPDATE education_records 
                SET institution_id = :inst_id, institution_name = :inst, degree_level = :lvl, degree_title = :title, field_of_study = :field, 
                    country_id = :country, start_date = :start, end_date = :end, graduation_status = :status, 
                    cgpa = :cgpa, cgpa_scale = :scale, percentage = :pct, result_status = :res_status, is_current = :is_curr, 
                    current_semester = :semester, passing_year = :passing_yr, updated_at = NOW() 
                WHERE id = :id AND user_id = :uid
            ");

            $stmt->execute([
                'inst_id' => $institutionId,
                'inst' => $institutionName,
                'lvl' => $degreeLevel,
                'title' => $degreeTitle,
                'field' => $fieldOfStudy,
                'country' => $countryId,
                'start' => $startDate ?: null,
                'end' => $endDate ?: null,
                'status' => $graduationStatus,
                'cgpa' => $cgpa,
                'scale' => $cgpaScale,
                'pct' => $percentage,
                'res_status' => $resultStatus,
                'is_curr' => $isCurrent,
                'semester' => $currentSemester,
                'passing_yr' => $passingYear,
                'id' => $id,
                'uid' => $userId
            ]);


            $db->commit();
            $completion = ProfileCompletionService::calculate($userId);
            $this->invalidateMatches($userId);

            $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
            if ($isAjax) {
                // Fetch updated education records
                $stmtEdu = $db->prepare("
                    SELECT er.*, c.name as country_name, inst.institution_type
                    FROM education_records er
                    LEFT JOIN countries c ON er.country_id = c.id
                    LEFT JOIN institutions inst ON er.institution_id = inst.id
                    WHERE er.user_id = :user_id 
                    ORDER BY er.start_date DESC
                ");
                $stmtEdu->execute(['user_id' => $userId]);

                $education = $stmtEdu->fetchAll(PDO::FETCH_ASSOC);

                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true, 
                    'message' => 'Education record updated successfully.',
                    'education' => $education,
                    'completion' => $completion
                ]);
                $this->halt("AJAX success response");
            }


            header("Location: " . url('/profile/edit?success=Education record updated successfully.'));
            if (defined('TESTING_MODE') && TESTING_MODE) {
                return;
            }
            exit();

        } catch (Exception $e) {
            $db->rollBack();
            \App\Services\Logger::error("Failed to update education $id for user $userId: " . $e->getMessage());
            $this->redirectBackWithErrors(['education' => 'An error occurred while saving your education history. Please try again.']);
        }
    }


    /**
     * POST /profile/education/delete
     */
    public function deleteEducation(): void {
        Auth::requireAuth();
        $userId = Auth::userId();
        $db = Database::connection();

        // CSRF Shield
        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            $this->redirectBackWithErrors(['csrf' => 'CSRF verification failed. Please try again.']);
        }

        $id = (int)($_POST['id'] ?? 0);

        // IDOR Guard: Verify ownership
        $stmtCheck = $db->prepare("SELECT user_id FROM education_records WHERE id = :id LIMIT 1");
        $stmtCheck->execute(['id' => $id]);
        $recordOwner = $stmtCheck->fetchColumn();

        if (!$recordOwner || (int)$recordOwner !== $userId) {
            $errors['unauthorized'] = "Unauthorized access attempt.";
            $this->redirectBackWithErrors($errors);
        }

        try {
            $stmt = $db->prepare("DELETE FROM education_records WHERE id = :id AND user_id = :uid");
            $stmt->execute(['id' => $id, 'uid' => $userId]);

            $completion = ProfileCompletionService::calculate($userId);
            $this->invalidateMatches($userId);

            $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
            if ($isAjax) {
                // Fetch updated education records
                $stmtEdu = $db->prepare("
                    SELECT er.*, c.name as country_name, inst.institution_type
                    FROM education_records er
                    LEFT JOIN countries c ON er.country_id = c.id
                    LEFT JOIN institutions inst ON er.institution_id = inst.id
                    WHERE er.user_id = :user_id 
                    ORDER BY er.start_date DESC
                ");
                $stmtEdu->execute(['user_id' => $userId]);

                $education = $stmtEdu->fetchAll(PDO::FETCH_ASSOC);

                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true, 
                    'message' => 'Education record deleted successfully.',
                    'education' => $education,
                    'completion' => $completion
                ]);
                $this->halt("AJAX success response");
            }


            header("Location: " . url('/profile/edit?success=Education record deleted successfully.'));
            if (defined('TESTING_MODE') && TESTING_MODE) {
                return;
            }
            exit();


        } catch (Exception $e) {
            \App\Services\Logger::error("Failed to delete education $id for user $userId: " . $e->getMessage());
            $this->redirectBackWithErrors(['education' => 'An error occurred while deleting your education record. Please try again.']);
        }
    }

    /**
     * POST /profile/preferences/update
     * Handle many-to-many preference links and alert checks
     */
    public function updatePreferences(): void {
        Auth::requireAuth();
        $userId = Auth::userId();
        $db = Database::connection();

        // CSRF Shield
        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            $this->redirectBackWithErrors(['csrf' => 'CSRF verification failed. Please try again.']);
        }

        // Whitelisted and sanitized preference inputs
        $prefCountries = (array)($_POST['preferred_countries'] ?? []);
        $prefFields = (array)($_POST['preferred_fields'] ?? []);
        $prefDegrees = (array)($_POST['preferred_degrees'] ?? []);

        // Deduplicate and sanitize
        $prefCountries = array_unique(array_filter(array_map('intval', $prefCountries)));
        $prefFields = array_unique(array_filter(array_map('intval', $prefFields)));
        $prefDegrees = array_unique(array_filter(array_map('trim', $prefDegrees)));

        // Server-side validation of preferred fields (max 3)
        if (count($prefFields) > 3) {
            $this->redirectBackWithErrors(['preferences' => 'You can select up to 3 fields of study.']);
        }

        // Validate degree levels
        $allowedDegrees = ['High School', 'Intermediate / College', 'Diploma', 'Associate Degree', 'Bachelor\'s', 'Master\'s', 'MPhil', 'PhD', 'Postdoctoral', 'Certification', 'Vocational', 'Other'];
        foreach ($prefDegrees as $lvl) {
            if (!in_array($lvl, $allowedDegrees)) {
                $this->redirectBackWithErrors(['preferences' => 'Invalid target degree level selection.']);
            }
        }


        // Sanitize and extract student profile preferences & language scores
        $prefFunding = trim($_POST['preferred_funding_type'] ?? '');
        $prefStartYear = !empty($_POST['preferred_start_year']) ? (int)$_POST['preferred_start_year'] : null;

        $ielts = !empty($_POST['ielts_score']) ? (float)$_POST['ielts_score'] : null;
        $toefl = !empty($_POST['toefl_score']) ? (int)$_POST['toefl_score'] : null;
        $pte = !empty($_POST['pte_score']) ? (int)$_POST['pte_score'] : null;
        $duolingo = !empty($_POST['duolingo_score']) ? (int)$_POST['duolingo_score'] : null;

        try {

            $db->beginTransaction();

            // 1. Preferred Countries (Pivot Update)
            $db->prepare("DELETE FROM user_preferred_countries WHERE user_id = :uid")->execute(['uid' => $userId]);
            if (!empty($prefCountries)) {
                $stmtCountry = $db->prepare("INSERT INTO user_preferred_countries (user_id, country_id) VALUES (:uid, :country_id)");
                foreach ($prefCountries as $cId) {
                    $stmtCountry->execute(['uid' => $userId, 'country_id' => $cId]);
                }
            }

            // 2. Preferred Fields of Study (Pivot Update)
            $db->prepare("DELETE FROM user_preferred_fields WHERE user_id = :uid")->execute(['uid' => $userId]);
            if (!empty($prefFields)) {
                $stmtField = $db->prepare("INSERT INTO user_preferred_fields (user_id, field_of_study_id) VALUES (:uid, :field_id)");
                foreach ($prefFields as $fId) {
                    $stmtField->execute(['uid' => $userId, 'field_id' => $fId]);
                }
            }

            // 3. Preferred Degree Levels (Pivot Update)
            $db->prepare("DELETE FROM user_preferred_degree_levels WHERE user_id = :uid")->execute(['uid' => $userId]);
            if (!empty($prefDegrees)) {
                $stmtDegree = $db->prepare("INSERT INTO user_preferred_degree_levels (user_id, degree_level) VALUES (:uid, :lvl)");
                foreach ($prefDegrees as $lvl) {
                    $stmtDegree->execute(['uid' => $userId, 'lvl' => $lvl]);
                }
            }

            // 4. Update student profile details (funding, year, scores)
            $stmtProf = $db->prepare("
                UPDATE student_profiles 
                SET preferred_funding_type = :funding,
                    preferred_start_year = :start_year,
                    ielts_score = :ielts,
                    toefl_score = :toefl,
                    pte_score = :pte,
                    duolingo_score = :duolingo,
                    updated_at = NOW()
                WHERE user_id = :uid
            ");
            $stmtProf->execute([
                'funding' => $prefFunding ?: null,
                'start_year' => $prefStartYear,
                'ielts' => $ielts,
                'toefl' => $toefl,
                'pte' => $pte,
                'duolingo' => $duolingo,
                'uid' => $userId
            ]);




            $db->commit();
            $completion = ProfileCompletionService::calculate($userId);
            $this->invalidateMatches($userId);

            $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Preferences updated successfully.', 'completion' => $completion]);
                $this->halt("AJAX success response");
            }

            header("Location: " . url('/profile/edit?success=Preferences updated successfully.'));
            exit();

        } catch (Exception $e) {
            $db->rollBack();
            \App\Services\Logger::error("Failed to save preferences for user $userId: " . $e->getMessage());
            $this->redirectBackWithErrors(['preferences' => 'An error occurred while saving your preferences. Please try again.']);
        }
    }


    /**
     * AJAX Endpoint: States Lookup
     */
    public function getStates(): void {
        if (!Auth::isAuthenticated() && !Auth::checkRememberMe()) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized access. Please log in.']);
            exit();
        }

        header('Content-Type: application/json');
        $countryId = $_GET['country_id'] ?? null;
        if ($countryId === null || !is_numeric($countryId) || (int)$countryId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid country ID.']);
            exit();
        }

        try {
            $db = Database::connection();
            $stmt = $db->prepare("SELECT id, name FROM states WHERE country_id = :country_id ORDER BY name ASC");
            $stmt->execute(['country_id' => (int)$countryId]);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            exit();
        } catch (Exception $e) {
            \App\Services\Logger::error("Failed to load states: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error occurred.']);
            exit();
        }
    }

    /**
     * AJAX Endpoint: Cities Lookup
     */
    public function getCities(): void {
        if (!Auth::isAuthenticated() && !Auth::checkRememberMe()) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized access. Please log in.']);
            exit();
        }

        header('Content-Type: application/json');
        $stateId = $_GET['state_id'] ?? null;
        if ($stateId === null || !is_numeric($stateId) || (int)$stateId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid state ID.']);
            exit();
        }

        try {
            $db = Database::connection();
            $stmt = $db->prepare("SELECT id, name FROM cities WHERE state_id = :state_id ORDER BY name ASC");
            $stmt->execute(['state_id' => (int)$stateId]);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            exit();
        } catch (Exception $e) {
            \App\Services\Logger::error("Failed to load cities: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error occurred.']);
            exit();
        }
    }

    /**
     * AJAX Endpoint: Institutions Lookup by Type and Location
     */
    public function getInstitutions(): void {
        if (!Auth::isAuthenticated() && !Auth::checkRememberMe()) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized access. Please log in.']);
            exit();
        }

        header('Content-Type: application/json');
        
        $type = trim($_GET['type'] ?? 'university');
        $countryId = !empty($_GET['country_id']) ? (int)$_GET['country_id'] : null;
        $stateId = !empty($_GET['state_id']) ? (int)$_GET['state_id'] : null;
        $cityId = !empty($_GET['city_id']) ? (int)$_GET['city_id'] : null;
        $search = trim($_GET['search'] ?? '');

        if (!in_array($type, ['school', 'college', 'university', 'other'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid institution type.']);
            exit();
        }

        try {
            $db = Database::connection();
            
            if ($type === 'university' && $countryId !== null && $stateId !== null) {
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

                if ($cityId !== null) {
                    $query .= " AND (i.city_id = :city_id OR i.coverage_type IN ('national', 'multi_state'))";
                    $params['city_id'] = $cityId;
                }

                if ($search !== '') {
                    $query .= " AND i.name LIKE :search";
                    $params['search'] = '%' . $search . '%';
                }

                $query .= " ORDER BY i.name ASC LIMIT 100";
            } else {
                $query = "SELECT id, name FROM institutions WHERE institution_type = :type AND status = 'approved'";
                $params = ['type' => $type];

                if ($countryId !== null) {
                    $query .= " AND country_id = :country_id";
                    $params['country_id'] = $countryId;
                }

                if ($stateId !== null) {
                    $query .= " AND state_id = :state_id";
                    $params['state_id'] = $stateId;
                }

                if ($cityId !== null) {
                    $query .= " AND city_id = :city_id";
                    $params['city_id'] = $cityId;
                }

                if ($search !== '') {
                    $query .= " AND name LIKE :search";
                    $params['search'] = '%' . $search . '%';
                }

                $query .= " ORDER BY name ASC LIMIT 100";
            }

            $stmt = $db->prepare($query);
            $stmt->execute($params);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            exit();
        } catch (Exception $e) {
            \App\Services\Logger::error("Failed to load institutions: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error occurred.']);
            exit();
        }
    }

    // Helper functions

    private function halt(string $message = 'Halt execution'): void {
        if (defined('TESTING_MODE') && TESTING_MODE) {
            throw new \RuntimeException($message);
        }
        exit();
    }

    private function redirectBackWithErrors(array $errors): void {
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'errors' => $errors]);
            $this->halt("AJAX errors response");
        }
        $_SESSION['profile_errors'] = $errors;
        header("Location: " . url('/profile/edit'));
        $this->halt("Redirect back with errors");
    }


    private function validateEducation(string $institution, string $level, string $title, string $field, ?float $cgpa, ?float $scale, ?float $pct, string $start, string $end): array {
        $errors = [];
        if (empty($institution)) {
            $errors['institution_name'] = 'Institution name is required.';
        } elseif (strlen($institution) > 150) {
            $errors['institution_name'] = 'Institution name must not exceed 150 characters.';
        }

        if (empty($level)) $errors['degree_level'] = 'Degree level is required.';

        if (empty($title)) {
            $errors['degree_title'] = 'Degree title is required.';
        } elseif (strlen($title) > 150) {
            $errors['degree_title'] = 'Degree title must not exceed 150 characters.';
        }

        if (empty($field)) {
            $errors['field_of_study'] = 'Field of study is required.';
        } elseif (strlen($field) > 100) {
            $errors['field_of_study'] = 'Field of study must not exceed 100 characters.';
        }

        $allowedLevels = ['High School', 'Intermediate / College', 'Diploma', 'Associate Degree', 'Bachelor\'s', 'Master\'s', 'MPhil', 'PhD', 'Postdoctoral', 'Certification', 'Vocational', 'Other'];
        if (!empty($level) && !in_array($level, $allowedLevels)) {
            $errors['degree_level'] = 'Invalid degree level selected.';
        }


        if ($cgpa !== null) {
            if ($cgpa < 0 || ($scale !== null && $cgpa > $scale)) {
                $errors['cgpa'] = 'CGPA value must be between 0 and the CGPA scale.';
            }
        }

        if ($scale !== null && $scale <= 0) {
            $errors['cgpa_scale'] = 'CGPA scale must be a positive number (e.g. 4.0 or 10.0).';
        }

        if ($pct !== null && ($pct < 0 || $pct > 100)) {
            $errors['percentage'] = 'Percentage must be between 0 and 100.';
        }

        if (!empty($start) && !empty($end)) {
            if (strtotime($start) > strtotime($end)) {
                $errors['start_date'] = 'Start date cannot be after the end date.';
            }
        }

        return $errors;
    }

    private function invalidateMatches(int $userId): void {
        $db = \App\Services\Database::connection();
        $stmt = $db->prepare("DELETE FROM scholarship_matches WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $userId]);
    }

    /**
     * Display profile creation placeholder page (Compatibility Redirect)
     */
    public function complete(): void {
        Auth::requireAuth();
        header("Location: " . url('/profile/edit'));
        $this->halt("Redirect to profile edit");
    }

    /**
     * POST /profile/complete
     * Finalize profile, recalculate completeness, and redirect to dashboard
     */
    public function completeWizard(): void {
        Auth::requireAuth();
        $userId = Auth::userId();
        
        // CSRF Check
        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            $this->redirectBackWithErrors(['csrf' => 'CSRF verification failed. Please try again.']);
        }

        // Recalculate completeness
        $db = Database::connection();
        $stmtCheckPrefs = $db->prepare("SELECT COUNT(*) FROM notification_preferences WHERE user_id = :uid");
        $stmtCheckPrefs->execute(['uid' => $userId]);
        $hasPrefs = (int)$stmtCheckPrefs->fetchColumn() > 0;

        if (!$hasPrefs) {
            $db->prepare("
                INSERT INTO notification_preferences (user_id, notification_type, email_enabled, whatsapp_enabled, created_at, updated_at)
                VALUES (:uid, 'email_alerts', 1, 0, NOW(), NOW())
            ")->execute(['uid' => $userId]);
        }

        ProfileCompletionService::calculate($userId);
        $this->invalidateMatches($userId);


        // Success toast session
        $_SESSION['dashboard_success'] = "Profile completed successfully!";

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'redirect_url' => url('/dashboard')]);
            $this->halt("AJAX complete success");
        }

        header("Location: " . url('/dashboard'));
        $this->halt("Redirect to dashboard");
    }

    /**
     * Display notification center & preferences for the current logged-in user
     */
    public function notifications(): void {
        Auth::requireAuth();
        $userId = Auth::userId();
        $db = Database::connection();

        // 1. Fetch recent outbox logs
        $stmt = $db->prepare("
            SELECT * 
            FROM notification_logs 
            WHERE user_id = :user_id 
            ORDER BY created_at DESC 
            LIMIT 50
        ");
        $stmt->execute(['user_id' => $userId]);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. Fetch notification preferences
        $stmtPrefs = $db->prepare("SELECT * FROM notification_preferences WHERE user_id = :user_id");
        $stmtPrefs->execute(['user_id' => $userId]);
        $rawPrefs = $stmtPrefs->fetchAll(PDO::FETCH_ASSOC);

        $prefMap = [];
        foreach ($rawPrefs as $p) {
            $prefMap[$p['notification_type']] = [
                'email' => (bool)$p['email_enabled'],
                'whatsapp' => (bool)$p['whatsapp_enabled']
            ];
        }

        // 3. Fetch user_preferences for reminder scope and days
        $stmtUserPref = $db->prepare("SELECT deadline_reminder_scope, deadline_reminder_days FROM user_preferences WHERE user_id = :user_id LIMIT 1");
        $stmtUserPref->execute(['user_id' => $userId]);
        $userPref = $stmtUserPref->fetch(PDO::FETCH_ASSOC) ?: [
            'deadline_reminder_scope' => 'off',
            'deadline_reminder_days' => '3,1'
        ];

        // 4. Fetch specific scholarship reminders
        $stmtReminders = $db->prepare("
            SELECT usr.*, s.title, s.provider_name, s.application_deadline, s.slug, s.status as sch_status
            FROM user_scholarship_reminders usr
            JOIN scholarships s ON usr.scholarship_id = s.id
            WHERE usr.user_id = :user_id
            ORDER BY s.application_deadline ASC
        ");
        $stmtReminders->execute(['user_id' => $userId]);
        $selectedReminders = $stmtReminders->fetchAll(PDO::FETCH_ASSOC);

        view('profile.notifications', [
            'logs' => $logs,
            'prefMap' => $prefMap,
            'userPref' => $userPref,
            'selectedReminders' => $selectedReminders,
            'csrf_token' => Security::csrfToken(),
            'title' => 'Notification Settings & Reminders',
            'success_message' => $_GET['success'] ?? null
        ]);
    }

    /**
     * Update user notification settings & deadline reminder preferences
     */
    public function updateNotificationSettings(): void {
        Auth::requireAuth();
        $userId = Auth::userId();
        $db = Database::connection();

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            $this->redirectBackWithErrors(['csrf' => 'CSRF verification failed. Please try again.']);
        }

        // 1. Channel toggles
        $newMatchEmail = isset($_POST['new_match_email']) ? 1 : 0;
        $newMatchWhatsapp = isset($_POST['new_match_whatsapp']) ? 1 : 0;
        $deadlineEmail = isset($_POST['deadline_email']) ? 1 : 0;
        $deadlineWhatsapp = isset($_POST['deadline_whatsapp']) ? 1 : 0;

        // 2. Deadline reminder scope: 'off' (default), 'all', 'selected'
        $scope = trim($_POST['deadline_reminder_scope'] ?? 'off');
        if (!in_array($scope, ['off', 'all', 'selected'], true)) {
            $scope = 'off';
        }

        // 3. Reminder timing offsets: array of days [1, 3, 7]
        $rawDays = (array)($_POST['reminder_days'] ?? [3, 1]);
        $sanitizedDays = [];
        foreach ($rawDays as $d) {
            $val = (int)$d;
            if ($val > 0 && $val <= 90) {
                $sanitizedDays[] = $val;
            }
        }
        $sanitizedDays = array_unique($sanitizedDays);
        rsort($sanitizedDays);
        if (empty($sanitizedDays)) {
            $sanitizedDays = [3, 1];
        }
        // 4. Preferred channel and multi-channel delivery setting
        $preferredChannel = strtolower(trim($_POST['preferred_channel'] ?? 'email'));
        if (!in_array($preferredChannel, ['email', 'whatsapp'], true)) {
            $preferredChannel = 'email';
        }
        $allowMultiChannel = isset($_POST['allow_multi_channel']) ? 1 : 0;

        $db->beginTransaction();
        try {
            // Upsert matching_scholarship_alerts preference
            $stmtUpsertMatch = $db->prepare("
                INSERT INTO notification_preferences (user_id, notification_type, email_enabled, whatsapp_enabled, created_at, updated_at)
                VALUES (:uid, 'matching_scholarship_alerts', :email, :whatsapp, NOW(), NOW())
                ON DUPLICATE KEY UPDATE 
                    email_enabled = VALUES(email_enabled),
                    whatsapp_enabled = VALUES(whatsapp_enabled),
                    updated_at = NOW()
            ");
            $stmtUpsertMatch->execute([
                'uid' => $userId,
                'email' => $newMatchEmail,
                'whatsapp' => $newMatchWhatsapp
            ]);

            // Upsert deadline_reminders preference
            $stmtUpsertDeadline = $db->prepare("
                INSERT INTO notification_preferences (user_id, notification_type, email_enabled, whatsapp_enabled, created_at, updated_at)
                VALUES (:uid, 'deadline_reminders', :email, :whatsapp, NOW(), NOW())
                ON DUPLICATE KEY UPDATE 
                    email_enabled = VALUES(email_enabled),
                    whatsapp_enabled = VALUES(whatsapp_enabled),
                    updated_at = NOW()
            ");
            $stmtUpsertDeadline->execute([
                'uid' => $userId,
                'email' => $deadlineEmail,
                'whatsapp' => $deadlineWhatsapp
            ]);

            // Update user_preferences for scope, timing, preferred channel and multi-channel
            $stmtUserPref = $db->prepare("
                INSERT INTO user_preferences (user_id, deadline_reminder_scope, deadline_reminder_days, preferred_channel, allow_multi_channel, created_at, updated_at)
                VALUES (:uid, :scope, :days, :pchannel, :multichannel, NOW(), NOW())
                ON DUPLICATE KEY UPDATE 
                    deadline_reminder_scope = VALUES(deadline_reminder_scope),
                    deadline_reminder_days = VALUES(deadline_reminder_days),
                    preferred_channel = VALUES(preferred_channel),
                    allow_multi_channel = VALUES(allow_multi_channel),
                    updated_at = NOW()
            ");
            $stmtUserPref->execute([
                'uid' => $userId,
                'scope' => $scope,
                'days' => $reminderDaysStr,
                'pchannel' => $preferredChannel,
                'multichannel' => $allowMultiChannel
            ]);

            $db->commit();
            Auth::logAudit($userId, 'notification_settings_updated', 'profile', 'users', $userId);

            $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Notification preferences saved successfully.']);
                $this->halt("AJAX notification preferences success");
            }

            header("Location: " . url('/notifications?success=Notification settings saved successfully.'));
            $this->halt("Redirect notifications");

        } catch (Exception $e) {
            $db->rollBack();
            \App\Services\Logger::error("Failed to update notification settings for user $userId: " . $e->getMessage());
            $this->redirectBackWithErrors(['notifications' => 'Could not save notification settings. Please try again.']);
        }
    }

    /**
     * AJAX/POST Endpoint: Toggle deadline reminder for a specific scholarship
     */
    public function toggleScholarshipReminder(): void {
        Auth::requireAuth();
        $userId = Auth::userId();
        $db = Database::connection();

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'CSRF verification failed.']);
            $this->halt();
        }

        $scholarshipId = (int)($_POST['scholarship_id'] ?? 0);
        if ($scholarshipId <= 0) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Invalid scholarship ID.']);
            $this->halt();
        }

        // Validate scholarship exists and is published
        $stmtSch = $db->prepare("SELECT id, title, application_deadline, status FROM scholarships WHERE id = :id LIMIT 1");
        $stmtSch->execute(['id' => $scholarshipId]);
        $sch = $stmtSch->fetch(PDO::FETCH_ASSOC);

        if (!$sch || $sch['status'] !== 'published') {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Scholarship not found or not published.']);
            $this->halt();
        }

        $isEnabled = isset($_POST['is_enabled']) ? (int)(bool)$_POST['is_enabled'] : 1;
        $reminderDays = trim($_POST['reminder_days'] ?? '3,1');

        $stmt = $db->prepare("
            INSERT INTO user_scholarship_reminders (user_id, scholarship_id, reminder_days, is_enabled, created_at, updated_at)
            VALUES (:uid, :sid, :days, :enabled, NOW(), NOW())
            ON DUPLICATE KEY UPDATE 
                is_enabled = VALUES(is_enabled),
                reminder_days = VALUES(reminder_days),
                updated_at = NOW()
        ");
        $stmt->execute([
            'uid' => $userId,
            'sid' => $scholarshipId,
            'days' => $reminderDays,
            'enabled' => $isEnabled
        ]);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'is_enabled' => $isEnabled,
            'message' => $isEnabled ? 'Deadline reminder enabled for this scholarship.' : 'Deadline reminder disabled for this scholarship.'
        ]);
        $this->halt();
    }
}


