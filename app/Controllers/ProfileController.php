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
            SELECT er.*, c.name as country_name 
            FROM education_records er
            LEFT JOIN countries c ON er.country_id = c.id
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
                   p.nationality_country_id, p.residence_country_id, p.residence_state_id, p.city_id
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
        $stmtEdu = $db->prepare("SELECT * FROM education_records WHERE user_id = :user_id ORDER BY start_date DESC");
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
        $email = strtolower(trim($_POST['email'] ?? ''));
        $phone = trim($_POST['phone'] ?? '');
        $dob = trim($_POST['date_of_birth'] ?? '');
        $gender = trim($_POST['gender'] ?? '');
        $nationalityCountryId = !empty($_POST['nationality_country_id']) ? (int)$_POST['nationality_country_id'] : null;
        $residenceCountryId = !empty($_POST['residence_country_id']) ? (int)$_POST['residence_country_id'] : null;
        $residenceStateId = !empty($_POST['residence_state_id']) ? (int)$_POST['residence_state_id'] : null;
        $cityId = !empty($_POST['city_id']) ? (int)$_POST['city_id'] : null;
        $bio = trim($_POST['bio'] ?? '');

        // Validation
        $errors = [];
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
                    residence_state_id = :state_id, city_id = :city_id, bio = :bio, updated_at = NOW() 
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
                'user_id' => $userId
            ]);

            $db->commit();
            ProfileCompletionService::calculate($userId);
            $this->invalidateMatches($userId);

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
        $institutionName = trim($_POST['institution_name'] ?? '');
        $degreeLevel = trim($_POST['degree_level'] ?? '');
        $degreeTitle = trim($_POST['degree_title'] ?? '');
        $fieldOfStudy = trim($_POST['field_of_study'] ?? '');
        $countryId = !empty($_POST['country_id']) ? (int)$_POST['country_id'] : null;
        $startDate = trim($_POST['start_date'] ?? '');
        $endDate = trim($_POST['end_date'] ?? '');
        $graduationStatus = trim($_POST['graduation_status'] ?? 'graduated');
        $cgpa = !empty($_POST['cgpa']) ? (float)$_POST['cgpa'] : null;
        $cgpaScale = !empty($_POST['cgpa_scale']) ? (float)$_POST['cgpa_scale'] : null;
        $percentage = !empty($_POST['percentage']) ? (float)$_POST['percentage'] : null;
        $resultStatus = trim($_POST['result_status'] ?? 'declared');
        $isCurrent = isset($_POST['is_current']) ? 1 : 0;

        // Validation
        $errors = $this->validateEducation($institutionName, $degreeLevel, $degreeTitle, $fieldOfStudy, $cgpa, $cgpaScale, $percentage, $startDate, $endDate);

        if (!empty($errors)) {
            $this->redirectBackWithErrors($errors);
        }

        try {
            $db->beginTransaction();

            // Enforce single current check
            if ($isCurrent === 1) {
                $db->prepare("UPDATE education_records SET is_current = 0 WHERE user_id = :uid")->execute(['uid' => $userId]);
            }

            $stmt = $db->prepare("
                INSERT INTO education_records 
                (user_id, institution_name, degree_level, degree_title, field_of_study, country_id, 
                 start_date, end_date, graduation_status, cgpa, cgpa_scale, percentage, result_status, is_current, created_at, updated_at) 
                VALUES 
                (:uid, :inst, :lvl, :title, :field, :country, :start, :end, :status, :cgpa, :scale, :pct, :res_status, :is_curr, NOW(), NOW())
            ");

            $stmt->execute([
                'uid' => $userId,
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
                'is_curr' => $isCurrent
            ]);

            $db->commit();
            ProfileCompletionService::calculate($userId);
            $this->invalidateMatches($userId);

            header("Location: " . url('/profile/edit?success=Education record added successfully.'));
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
        $institutionName = trim($_POST['institution_name'] ?? '');
        $degreeLevel = trim($_POST['degree_level'] ?? '');
        $degreeTitle = trim($_POST['degree_title'] ?? '');
        $fieldOfStudy = trim($_POST['field_of_study'] ?? '');
        $countryId = !empty($_POST['country_id']) ? (int)$_POST['country_id'] : null;
        $startDate = trim($_POST['start_date'] ?? '');
        $endDate = trim($_POST['end_date'] ?? '');
        $graduationStatus = trim($_POST['graduation_status'] ?? 'graduated');
        $cgpa = !empty($_POST['cgpa']) ? (float)$_POST['cgpa'] : null;
        $cgpaScale = !empty($_POST['cgpa_scale']) ? (float)$_POST['cgpa_scale'] : null;
        $percentage = !empty($_POST['percentage']) ? (float)$_POST['percentage'] : null;
        $resultStatus = trim($_POST['result_status'] ?? 'declared');
        $isCurrent = isset($_POST['is_current']) ? 1 : 0;

        // Validation
        $errors = $this->validateEducation($institutionName, $degreeLevel, $degreeTitle, $fieldOfStudy, $cgpa, $cgpaScale, $percentage, $startDate, $endDate);

        if (!empty($errors)) {
            $this->redirectBackWithErrors($errors);
        }

        try {
            $db->beginTransaction();

            // Enforce single current check
            if ($isCurrent === 1) {
                $db->prepare("UPDATE education_records SET is_current = 0 WHERE user_id = :uid")->execute(['uid' => $userId]);
            }

            $stmt = $db->prepare("
                UPDATE education_records 
                SET institution_name = :inst, degree_level = :lvl, degree_title = :title, field_of_study = :field, 
                    country_id = :country, start_date = :start, end_date = :end, graduation_status = :status, 
                    cgpa = :cgpa, cgpa_scale = :scale, percentage = :pct, result_status = :res_status, is_current = :is_curr, updated_at = NOW() 
                WHERE id = :id AND user_id = :uid
            ");

            $stmt->execute([
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
                'id' => $id,
                'uid' => $userId
            ]);

            $db->commit();
            ProfileCompletionService::calculate($userId);
            $this->invalidateMatches($userId);

            header("Location: " . url('/profile/edit?success=Education record updated successfully.'));
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

            ProfileCompletionService::calculate($userId);
            $this->invalidateMatches($userId);

            header("Location: " . url('/profile/edit?success=Education record deleted successfully.'));
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

        // Validate degree levels
        $allowedDegrees = ['High School', 'Diploma', 'Associate Degree', 'Bachelor\'s', 'Master\'s', 'MPhil', 'PhD', 'Postdoctoral'];
        foreach ($prefDegrees as $lvl) {
            if (!in_array($lvl, $allowedDegrees)) {
                $this->redirectBackWithErrors(['preferences' => 'Invalid target degree level selection.']);
            }
        }

        // Alerts triggers
        $alerts = [
            'whatsapp_alerts' => isset($_POST['whatsapp_alerts']) ? 1 : 0,
            'email_alerts' => isset($_POST['email_alerts']) ? 1 : 0,
            'daily_alerts' => isset($_POST['daily_alerts']) ? 1 : 0,
            'weekly_digest' => isset($_POST['weekly_digest']) ? 1 : 0,
            'deadline_reminders' => isset($_POST['deadline_reminders']) ? 1 : 0,
            'new_scholarship_alerts' => isset($_POST['new_scholarship_alerts']) ? 1 : 0,
            'matching_scholarship_alerts' => isset($_POST['matching_scholarship_alerts']) ? 1 : 0
        ];

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

            // 4. Alerts Options Mapping
            $db->prepare("DELETE FROM notification_preferences WHERE user_id = :uid")->execute(['uid' => $userId]);
            $stmtNotify = $db->prepare("
                INSERT INTO notification_preferences (user_id, notification_type, email_enabled, whatsapp_enabled, created_at, updated_at) 
                VALUES (:uid, :type, :email, :whatsapp, NOW(), NOW())
            ");

            foreach ($alerts as $type => $enabled) {
                $stmtNotify->execute([
                    'uid' => $userId,
                    'type' => $type,
                    'email' => $enabled,
                    'whatsapp' => $enabled
                ]);
            }

            $db->commit();
            ProfileCompletionService::calculate($userId);
            $this->invalidateMatches($userId);

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

    // Helper functions
    private function halt(string $message = 'Halt execution'): void {
        if (defined('TESTING_MODE') && TESTING_MODE) {
            throw new \RuntimeException($message);
        }
        exit();
    }

    private function redirectBackWithErrors(array $errors): void {
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

        $allowedLevels = ['High School', 'Diploma', 'Associate Degree', 'Bachelor\'s', 'Master\'s', 'MPhil', 'PhD', 'Postdoctoral'];
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
}
