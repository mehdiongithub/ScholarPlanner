<?php
/**
 * Core PHP SaaS Testing Suite Runner
 *
 * Runs smoke test suite files in order and checks assertions.
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/ConfigTest.php';
require_once __DIR__ . '/DatabaseTest.php';
require_once __DIR__ . '/HomepageTest.php';
require_once __DIR__ . '/Step1FinalAuditTest.php';
require_once __DIR__ . '/DatabaseMigrationTest.php';
require_once __DIR__ . '/AuthenticationTest.php';
require_once __DIR__ . '/ProfileTest.php';
require_once __DIR__ . '/ScholarshipTest.php';
require_once __DIR__ . '/ScholarshipMatchingTest.php';
require_once __DIR__ . '/NotificationTest.php';
require_once __DIR__ . '/DocumentTest.php';
require_once __DIR__ . '/ApplicationTest.php';

$exitCode = 0;
echo "========================================\n";
echo "    STARTING PLATFORM SMOKE TESTS       \n";
echo "========================================\n\n";

try {
    // 1. Config tests
    $configTest = new ConfigTest();
    $configTest->run();
    
    // 2. Database connection checks
    $dbTest = new DatabaseTest();
    $dbTest->run();
    
    // 3. Folder assets and render tests
    $homeTest = new HomepageTest();
    $homeTest->run();
    
    // 4. Structural Audit Test
    $auditTest = new Step1FinalAuditTest();
    $auditTest->run();
    
    // 5. Database Schema & Migration Verification
    $dbMigrationTest = new DatabaseMigrationTest();
    $dbMigrationTest->run();
    
    // 6. Production Authentication & Security Guard Verification
    $authTest = new AuthenticationTest();
    $authTest->run();
    
    // 7. Applicant Profile & Preferences Verification
    $profileTest = new ProfileTest();
    $profileTest->run();
    
    // 8. Scholarship Database & Management Verification
    $scholarshipTest = new ScholarshipTest();
    $scholarshipTest->run();

    // 9. Personalized Scholarship Matching & Recommendation Engine Verification
    $matchingTest = new ScholarshipMatchingTest();
    $matchingTest->run();

    // 10. Notification & Alert Queue Engine Verification
    $notifTest = new NotificationTest();
    $notifTest->run();

    // 11. Secure Document Management Verification
    $docTest = new DocumentTest();
    $docTest->run();

    // 12. Application Tracker & Workflow Verification
    $appTest = new ApplicationTest();
    $appTest->run();
    
    echo "========================================\n";
    echo "    ALL TEST SUITES PASSED OVERALL       \n";
    echo "========================================\n";
} catch (\Exception $e) {
    echo "\n❌ TEST SUITE FAILURE: " . $e->getMessage() . "\n";
    echo "Location: " . $e->getFile() . " on line " . $e->getLine() . "\n";
    echo "========================================\n";
    $exitCode = 1;
}

exit($exitCode);
