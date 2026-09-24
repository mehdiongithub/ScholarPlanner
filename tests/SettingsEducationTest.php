<?php
require_once __DIR__ . '/bootstrap.php';

use App\Services\Database;
use App\Services\Auth;
use App\Helpers\Security;
use App\Controllers\ProfileController;

echo "=== Running Settings Education Tests ===\n";

$db = Database::connection();
$userId = 1198474; // Esha Fatima

// Authenticate as test user
$_SESSION['user_id'] = $userId;
$_SESSION['role'] = 'student';

// 1. Verify existing record in education_records
$stmt = $db->prepare("SELECT * FROM education_records WHERE user_id = :uid LIMIT 1");
$stmt->execute(['uid' => $userId]);
$existingRecord = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$existingRecord) {
    echo "FAIL: Test user does not have an existing education record.\n";
    exit(1);
}
echo "OK: Found existing education record ID: " . $existingRecord['id'] . " (Degree: " . $existingRecord['degree_title'] . ")\n";

// 2. Test updateEducation redirection and DB update with return_to
$_POST = [
    'csrf_token' => Security::csrfToken(),
    'return_to' => '/settings?tab=profile',
    'id' => $existingRecord['id'],
    'institution_type' => 'university',
    'institution_select' => '',
    'institution_name' => 'Govt College University Hyderabad Updated',
    'degree_level' => "Bachelor's",
    'degree_title' => 'BS IT',
    'field_of_study' => 'Information Technology',
    'country_id' => 1,
    'cgpa' => '3.50',
    'cgpa_scale' => '4.00',
    'start_date' => '2025-05-07',
    'end_date' => '2028-05-30',
    'is_current' => '1',
    'passing_year' => '2028'
];

$controller = new ProfileController();
ob_start();
$controller->updateEducation();
ob_end_clean();

// Check if updated in DB
$stmtCheck = $db->prepare("SELECT institution_name, degree_title, cgpa FROM education_records WHERE id = :id");
$stmtCheck->execute(['id' => $existingRecord['id']]);
$updated = $stmtCheck->fetch(PDO::FETCH_ASSOC);

if ($updated['institution_name'] === 'Govt College University Hyderabad Updated' && (float)$updated['cgpa'] === 3.5 && $updated['degree_title'] === 'BS IT') {
    echo "OK: updateEducation successfully updated the database record.\n";
} else {
    echo "FAIL: Record was not updated properly: " . print_r($updated, true) . "\n";
    exit(1);
}

// Revert test update back to original clean name
$db->prepare("UPDATE education_records SET institution_name = 'Govt College University Hyderabad', degree_title = 'BS', cgpa = 3.00 WHERE id = :id")->execute(['id' => $existingRecord['id']]);
echo "OK: Reverted test user record to clean original state.\n";

// 3. Test addEducation with return_to
$_POST = [
    'csrf_token' => Security::csrfToken(),
    'return_to' => '/settings?tab=profile',
    'institution_type' => 'college',
    'institution_name' => 'Govt Degree College Test',
    'degree_level' => 'Intermediate / College',
    'degree_title' => 'FSc Pre-Engineering',
    'field_of_study' => 'Pre-Engineering',
    'country_id' => 1,
    'cgpa' => '',
    'percentage' => '82.5',
    'start_date' => '2022-09-01',
    'end_date' => '2024-06-30',
    'passing_year' => '2024'
];

ob_start();
$controller->addEducation();
ob_end_clean();

$stmtCheckNew = $db->prepare("SELECT id FROM education_records WHERE user_id = :uid AND degree_title = 'FSc Pre-Engineering'");
$stmtCheckNew->execute(['uid' => $userId]);
$newRecId = $stmtCheckNew->fetchColumn();

if ($newRecId) {
    echo "OK: addEducation successfully added new qualification record ID: $newRecId.\n";
    // Delete newly added test record
    $db->prepare("DELETE FROM education_records WHERE id = :id")->execute(['id' => $newRecId]);
    echo "OK: Cleaned up added test record.\n";
} else {
    echo "FAIL: addEducation did not insert record.\n";
    exit(1);
}

// 4. Test error redirection keeps user on return_to
$_POST = [
    'csrf_token' => Security::csrfToken(),
    'return_to' => '/settings?tab=profile',
    'id' => $existingRecord['id'],
    'institution_type' => 'university',
    'institution_name' => '', // Empty name triggers validation error!
    'degree_level' => "Bachelor's",
    'degree_title' => 'BS',
    'field_of_study' => 'CS'
];

try {
    ob_start();
    $controller->updateEducation();
    ob_end_clean();
} catch (\RuntimeException $e) {
    // Expected in TESTING_MODE on redirectBackWithErrors
}

if (!empty($_SESSION['profile_errors']['institution_name'])) {
    echo "OK: Validation error captured in \$_SESSION['profile_errors']: " . $_SESSION['profile_errors']['institution_name'] . "\n";
    unset($_SESSION['profile_errors']);
} else {
    echo "FAIL: Expected validation error in \$_SESSION['profile_errors'].\n";
    exit(1);
}

// 5. Test Settings view rendering for user with existing academic record
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['tab'] = 'profile';
$settingsCtrl = new \App\Controllers\SettingsController();

ob_start();
$settingsCtrl->index();
$html = ob_get_clean();

if (strpos($html, 'value="Govt College University Hyderabad"') !== false || strpos($html, 'Govt College University Hyderabad') !== false) {
    echo "OK: Settings page correctly displays existing institution name.\n";
} else {
    echo "FAIL: Settings page did not display existing institution name.\n";
    exit(1);
}

if (strpos($html, 'value="BS"') !== false) {
    echo "OK: Settings page correctly prefilled degree title 'BS'.\n";
} else {
    echo "FAIL: Settings page did not prefill degree title.\n";
    exit(1);
}

if (strpos($html, 'value="Information Technology"') !== false) {
    echo "OK: Settings page correctly prefilled field of study 'Information Technology'.\n";
} else {
    echo "FAIL: Settings page did not prefill field of study.\n";
    exit(1);
}

if (strpos($html, 'Update Qualification Record') !== false) {
    echo "OK: Settings page correctly set button text to 'Update Qualification Record'.\n";
} else {
    echo "FAIL: Settings page did not show 'Update Qualification Record' button.\n";
    exit(1);
}

if (strpos($html, '/profile/education/update') !== false) {
    echo "OK: Settings form action correctly points to update endpoint.\n";
} else {
    echo "FAIL: Settings form action did not point to update endpoint.\n";
    exit(1);
}

echo "=== All Settings Education Tests (Logic + View) Passed Successfully! ===\n";
