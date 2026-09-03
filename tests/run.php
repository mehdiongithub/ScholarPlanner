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
require_once __DIR__ . '/UniversityCoverageTest.php';
require_once __DIR__ . '/ScholarshipTest.php';
require_once __DIR__ . '/ScholarshipMatchingTest.php';
require_once __DIR__ . '/NotificationTest.php';
require_once __DIR__ . '/DocumentTest.php';
require_once __DIR__ . '/ApplicationTest.php';
require_once __DIR__ . '/CommunicationAndManagementTest.php';
require_once __DIR__ . '/ScholarshipDiscoveryTest.php';
require_once __DIR__ . '/PlatformOperationsTest.php';
require_once __DIR__ . '/PublicDiscoverySeoTest.php';
require_once __DIR__ . '/BillingSubscriptionTest.php';
require_once __DIR__ . '/AnalyticsExportTest.php';
require_once __DIR__ . '/EndToEndLaunchTest.php';
require_once __DIR__ . '/WacrmWhatsAppProviderTest.php';
require_once __DIR__ . '/NotificationFoundationTest.php';
require_once __DIR__ . '/NotificationSchedulerTest.php';
require_once __DIR__ . '/WacrmDispatchWorkerTest.php';
require_once __DIR__ . '/PublicPagesTest.php';
require_once __DIR__ . '/Step1QueueVerificationTest.php';
require_once __DIR__ . '/Step2MatchingAndPreferencesTest.php';
require_once __DIR__ . '/Step3NotificationDeliveryTest.php';
require_once __DIR__ . '/Step4PaymentIntegrationTest.php';

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

    // 8. University Coverage state-specific + nationwide query rules
    $coverageTest = new UniversityCoverageTest();
    $coverageTest->run();
    
    // 9. Scholarship Database & Management Verification
    $scholarshipTest = new ScholarshipTest();
    $scholarshipTest->run();

    // 10. Personalized Scholarship Matching & Recommendation Engine Verification
    $matchingTest = new ScholarshipMatchingTest();
    $matchingTest->run();

    // 11. Notification & Alert Queue Engine Verification
    $notifTest = new NotificationTest();
    $notifTest->run();

    // 12. Secure Document Management Verification
    $docTest = new DocumentTest();
    $docTest->run();

    // 13. Application Tracker & Workflow Verification
    $appTest = new ApplicationTest();
    $appTest->run();

    // 14. Communication & Administrative Notes Management Verification
    $cmTest = new CommunicationAndManagementTest();
    $cmTest->run();

    // 15. Advanced Discovery, Saved Scholarships & Comparison Verification
    $discTest = new ScholarshipDiscoveryTest();
    $discTest->run();

    // 16. Operational Intelligence & Platform Operations Verification
    $opsTest = new PlatformOperationsTest();
    $opsTest->run();
    
    // 17. Public Discovery, SEO & Sitemap Verification
    $seoTest = new PublicDiscoverySeoTest();
    $seoTest->run();
    
    // 18. Billing & Subscription Monetization Verification
    $billingTest = new BillingSubscriptionTest();
    $billingTest->run();

    // 19. Analytics Reporting & Secure CSV Exports Verification
    $analyticsTest = new AnalyticsExportTest();
    $analyticsTest->run();

    // 20. End-To-End Testing & Launch Readiness Verification
    $e2eTest = new \App\Tests\EndToEndLaunchTest();
    $e2eTest->run();

    // 21. WACRM WhatsApp Provider Integration Verification
    $wacrmTest = new WacrmWhatsAppProviderTest();
    $wacrmTest->run();

    // 22. Notification Foundation Gating & Idempotency Verification
    $notifFoundationTest = new NotificationFoundationTest();
    $notifFoundationTest->run();

    // 23. Admin Notification Scheduling & Worker Foundation Verification
    $notifSchedulerTest = new NotificationSchedulerTest();
    $notifSchedulerTest->run();

    // 24. WACRM WhatsApp Dispatch Worker Verification
    $dispatchWorkerTest = new WacrmDispatchWorkerTest();
    $dispatchWorkerTest->run();

    // 25. Public Legal & Static Pages Verification
    $publicPagesTest = new PublicPagesTest();
    $publicPagesTest->run();

    // 26. Step 1 Asynchronous Registration & Email Queue Verification
    $step1Test = new Step1QueueVerificationTest();
    $step1Test->run();

    // 27. Step 2 Scholarship Notification Matching & Preference Engine Verification
    $step2Test = new Step2MatchingAndPreferencesTest();
    $step2Test->run();

    // 28. Step 3 Production Notification Delivery: WACRM WhatsApp + Gmail SMTP Verification
    $step3Test = new Step3NotificationDeliveryTest();
    $step3Test->run();

    // 29. Step 4 CashMaal Subscription Payments + Meta WhatsApp Payment Confirmation Verification
    $step4Test = new Step4PaymentIntegrationTest();
    $step4Test->run();
    
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
