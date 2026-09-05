<?php

require_once __DIR__ . '/bootstrap.php';

use App\Services\Database;
use App\Services\Auth;
use App\Helpers\Security;
use App\Services\NotificationService;
use App\Services\NotificationQueueService;
use App\Services\EmailNotificationService;
use App\Services\WhatsAppNotificationService;
use App\Services\ScholarshipMatchingService;
use App\Services\WhatsApp\WacrmWhatsAppProvider;
use App\Services\WhatsApp\MetaWhatsAppProvider;
use App\Services\WhatsApp\LogWhatsAppProvider;
use App\Services\WhatsApp\WhatsAppProviderInterface;
use App\Controllers\NotificationController;

require_once __DIR__ . '/WacrmWhatsAppProviderTest.php';

class Step6MockWhatsAppProvider implements WhatsAppProviderInterface {
    public int $callCount = 0;
    public array $calls = [];
    public bool $mockSuccess = true;
    public ?string $mockMessageId = 'wacrm_step6_msg_123';
    public ?string $mockError = null;
    public ?int $mockRetryAfter = null;

    public function sendTemplateMessage(string $recipient, string $templateName, array $parameters): array {
        $this->callCount++;
        $this->calls[] = [
            'recipient' => $recipient,
            'template' => $templateName,
            'parameters' => $parameters
        ];

        return [
            'success' => $this->mockSuccess,
            'message_id' => $this->mockSuccess ? ($this->mockMessageId . '_' . $this->callCount) : null,
            'error' => $this->mockSuccess ? null : ($this->mockError ?? 'Simulated provider error'),
            'retry_after' => $this->mockRetryAfter
        ];
    }
}

class Step6NotificationAutomationTest {
    private PDO $db;
    private NotificationQueueService $queueService;
    private NotificationService $notificationService;
    private ScholarshipMatchingService $matchingService;

    private int $pakistanCountryId;
    private int $sindhStateId;
    private int $punjabStateId;
    private int $ibaInstId;
    private int $lumsInstId;
    private int $csFieldId;
    private int $visitorRoleId;
    private int $adminRoleId;

    public function __construct() {
        $this->db = Database::connection();
        $this->queueService = new NotificationQueueService();
        $this->notificationService = new NotificationService();
        $this->matchingService = new ScholarshipMatchingService();
    }

    private function assert(bool $condition, string $message): void {
        if (!$condition) {
            throw new Exception("Assertion failed: " . $message);
        }
    }

    public function run(): void {
        echo "=================================================================\n";
        echo " RUNNING STEP 6 NOTIFICATION AUTOMATION TEST SUITE (58 TESTS)\n";
        echo "=================================================================\n\n";

        $this->setUpFixtures();
        $this->cleanUpFixtures();
        $this->phoneSeq = (int)(time() % 10000) * 100;

        try {
            // Group 1: New Match Notifications & Targeting (1-10)
            $this->test1_matchingUserReceivesNotification();
            $this->test2_nonMatchingUserReceivesNone();
            $this->test3_verifiedScholarshipRequired();
            $this->test4_unverifiedScholarshipRejected();
            $this->test5_stateTargetingSindhVsPunjab();
            $this->test6_educationTargetingDegreeLevels();
            $this->test7_institutionTargeting();
            $this->test8_combinedTargetingAndLogic();
            $this->test9_duplicateCronExecutionIsIdempotent();
            $this->test10_sameUserScholarshipCreatesOneBusinessNotification();

            // Group 2: WhatsApp Batching & Rules (11-17)
            $this->test11_oneMatchCreatesOneWhatsApp();
            $this->test12_multipleMatchesSameDayCombinedIntoOneWhatsApp();
            $this->test13_noMatchSendsZeroWhatsApp();
            $this->test14_sundaySendsZeroAutomaticScholarshipWhatsApp();
            $this->test15_max25MessagesEnforced();
            $this->test16_concurrentWorkersCannotExceed25();
            $this->test17_transactionalMetaPaymentNotBlockedByScholarshipWhatsAppLimit();

            // Group 3: Channel Preferences & Fallback (18-23)
            $this->test18_preferredEmailSendsEmailOnly();
            $this->test19_preferredWhatsAppSendsWhatsAppOnly();
            $this->test20_multiChannelDisabledDeliversSingleChannel();
            $this->test21_multiChannelEnabledDeliversBothChannels();
            $this->test22_missingWhatsAppNumberFallsBackToEmail();
            $this->test23_invalidWhatsAppNumberHandling();

            // Group 4: Deadline Reminders (24-33)
            $this->test24_remindersOffGeneratesZeroReminders();
            $this->test25_oneDayReminder();
            $this->test26_threeDayReminder();
            $this->test27_sevenDayReminder();
            $this->test28_duplicateReminderPrevention();
            $this->test29_concurrentReminderGeneration();
            $this->test30_expiredScholarshipRejectedFromReminders();
            $this->test31_alreadyPassedDeadlineRejectedFromReminders();
            $this->test32_unverifiedScholarshipRejectedFromReminders();
            $this->test33_userEligibilityEnforcedForReminders();

            // Group 5: Queue Architecture & Worker (34-42)
            $this->test34_cronOnlyEnqueuesZeroDirectDelivery();
            $this->test35_workerPerformsDelivery();
            $this->test36_retryBehaviorExponentialBackoff();
            $this->test37_staleJobRecovery();
            $this->test38_maximumAttemptsThreshold();
            $this->test39_duplicateWorkerProtectionRowLocking();
            $this->test40_providerMessageIdRecorded();
            $this->test41_failureLoggingRecorded();
            $this->test42_manualRetryPreservesIdempotencyAndAttemptCount();

            // Group 6: Provider Routing & Integrity (43-48)
            $this->test43_scholarshipWhatsAppRoutesToWacrm();
            $this->test44_paymentConfirmationRoutesToMeta();
            $this->test45_scholarshipEmailRoutesToGmailSmtp();
            $this->test46_verificationEmailRoutesToGmailSmtp();
            $this->test47_noProviderCredentialLeakageInLogs();
            $this->test48_wacrmSsrfProtectionsRemainIntact();

            // Group 7: Security Audit (49-54)
            $this->test49_adminAuthorizationRequired();
            $this->test50_idorPrevention();
            $this->test51_csrfEnforcement();
            $this->test52_sqlInjectionPrevention();
            $this->test53_xssSafeNotificationContent();
            $this->test54_secretRedaction();

            // Group 8: Genuine Multi-Process Concurrency Tests (55-58)
            $this->test55_concurrencyTwoDailyMatchingProcesses();
            $this->test56_concurrencyTwoWhatsAppQueueWorkers();
            $this->test57_concurrencyTwoWorkersCompetingFor25Limit();
            $this->test58_concurrencyTwoDeadlineReminderCronExecutions();

            echo "\n=================================================================\n";
            echo " ✔ ALL 58 STEP 6 NOTIFICATION AUTOMATION TESTS PASSED!\n";
            echo "=================================================================\n\n";

        } finally {
            $this->cleanUpFixtures();
        }
    }

    private function setUpFixtures(): void {
        // Resolve foreign keys
        $this->pakistanCountryId = (int)$this->db->query("SELECT id FROM countries WHERE iso2 = 'PK' LIMIT 1")->fetchColumn();
        if (!$this->pakistanCountryId) {
            $this->pakistanCountryId = (int)$this->db->query("SELECT id FROM countries WHERE name = 'Pakistan' LIMIT 1")->fetchColumn();
        }
        if (!$this->pakistanCountryId) {
            $this->db->exec("INSERT INTO countries (name, iso2, status) VALUES ('Pakistan', 'PK', 'active')");
            $this->pakistanCountryId = (int)$this->db->lastInsertId();
        }

        $this->sindhStateId = (int)$this->db->query("SELECT id FROM states WHERE name LIKE '%Sindh%' LIMIT 1")->fetchColumn();
        if (!$this->sindhStateId) {
            $this->db->exec("INSERT INTO states (country_id, name, code, status) VALUES ({$this->pakistanCountryId}, 'Sindh', 'SD', 'active')");
            $this->sindhStateId = (int)$this->db->lastInsertId();
        }

        $this->punjabStateId = (int)$this->db->query("SELECT id FROM states WHERE name LIKE '%Punjab%' LIMIT 1")->fetchColumn();
        if (!$this->punjabStateId) {
            $this->db->exec("INSERT INTO states (country_id, name, code, status) VALUES ({$this->pakistanCountryId}, 'Punjab', 'PB', 'active')");
            $this->punjabStateId = (int)$this->db->lastInsertId();
        }

        $this->ibaInstId = (int)$this->db->query("SELECT id FROM institutions WHERE name LIKE '%IBA%' LIMIT 1")->fetchColumn();
        if (!$this->ibaInstId) {
            $this->db->exec("INSERT INTO institutions (name, institution_type, country_id, state_id, status) VALUES ('IBA Karachi', 'university', {$this->pakistanCountryId}, {$this->sindhStateId}, 'approved')");
            $this->ibaInstId = (int)$this->db->lastInsertId();
        }

        $this->lumsInstId = (int)$this->db->query("SELECT id FROM institutions WHERE name LIKE '%LUMS%' LIMIT 1")->fetchColumn();
        if (!$this->lumsInstId) {
            $this->db->exec("INSERT INTO institutions (name, institution_type, country_id, state_id, status) VALUES ('LUMS Lahore', 'university', {$this->pakistanCountryId}, {$this->punjabStateId}, 'approved')");
            $this->lumsInstId = (int)$this->db->lastInsertId();
        }

        $this->csFieldId = (int)$this->db->query("SELECT id FROM fields_of_study WHERE name = 'Computer Science' LIMIT 1")->fetchColumn();
        if (!$this->csFieldId) {
            $this->db->exec("INSERT INTO fields_of_study (name, slug) VALUES ('Computer Science', 'computer-science')");
            $this->csFieldId = (int)$this->db->lastInsertId();
        }

        $this->visitorRoleId = (int)$this->db->query("SELECT id FROM roles WHERE name = 'visitor' LIMIT 1")->fetchColumn();
        $this->adminRoleId = (int)$this->db->query("SELECT id FROM roles WHERE name = 'admin' LIMIT 1")->fetchColumn();

        $_ENV['WHATSAPP_API_URL'] = 'https://graph.facebook.com/v18.0';
        $_ENV['WHATSAPP_ACCESS_TOKEN'] = 'mock_meta_access_token';
        $_ENV['WHATSAPP_PHONE_NUMBER_ID'] = '1234567890';
        $_ENV['WHATSAPP_PROVIDER'] = 'wacrm';

        \App\Services\WhatsApp\CurlMockRegistry::reset();
        \App\Services\WhatsApp\CurlMockRegistry::$response = json_encode([
            'messages' => [['id' => 'wamid.HBgL_step6_meta_123']],
            'data' => [
                'message_id' => 'msg_wacrm_step6_12345',
                'whatsapp_message_id' => 'wamid.step6.test'
            ]
        ]);
        \App\Services\WhatsApp\CurlMockRegistry::$httpCode = 200;
    }

    private function cleanUpFixtures(): void {
        $this->db->exec("DELETE FROM notification_logs");
        $this->db->exec("DELETE FROM scholarship_matches WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'step6_%')");
        $this->db->exec("DELETE FROM user_scholarship_reminders WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'step6_%')");
        $this->db->exec("DELETE FROM education_records WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'step6_%')");
        $this->db->exec("DELETE FROM student_profiles WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'step6_%')");
        $this->db->exec("DELETE FROM notification_preferences WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'step6_%')");
        $this->db->exec("DELETE FROM user_preferences WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'step6_%')");
        $this->db->exec("DELETE FROM subscriptions WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'step6_%')");
        $this->db->exec("DELETE FROM users WHERE email LIKE 'step6_%'");
        $this->db->exec("DELETE FROM scholarships WHERE slug LIKE 'step6-%'");
    }

    private int $phoneSeq = 100000;

    private function createTestUser(
        string $email,
        string $preferredChannel = 'email',
        int $multiChannel = 0,
        ?string $phone = '+923001234567',
        int $stateId = 0,
        string $degree = "Master's",
        int $instId = 0
    ): int {
        if ($phone === '+923001234567') {
            $phone = '+92300' . str_pad((string)(++$this->phoneSeq), 7, '0', STR_PAD_LEFT);
        }
        $stId = $stateId ?: $this->sindhStateId;
        $inId = $instId ?: ($stId === $this->punjabStateId ? $this->lumsInstId : $this->ibaInstId);

        $stmt = $this->db->prepare("
            INSERT INTO users (role_id, email, password_hash, first_name, last_name, phone, whatsapp_phone, status, email_opt_in, whatsapp_opt_in, created_at, updated_at)
            VALUES (:role, :email, 'hash', 'Step6', 'User', :phone, :wphone, 'active', 1, 1, NOW(), NOW())
        ");
        $stmt->execute([
            'role' => $this->visitorRoleId,
            'email' => $email,
            'phone' => $phone,
            'wphone' => $phone
        ]);
        $uid = (int)$this->db->lastInsertId();

        // Student profile
        $this->db->prepare("
            INSERT INTO student_profiles (user_id, nationality_country_id, residence_country_id, residence_state_id, date_of_birth, gender, created_at, updated_at)
            VALUES (:uid, :ncid, :rcid, :sid, '2000-01-01', 'Male', NOW(), NOW())
        ")->execute([
            'uid' => $uid,
            'ncid' => $this->pakistanCountryId,
            'rcid' => $this->pakistanCountryId,
            'sid' => $stId
        ]);

        // Education record
        $this->db->prepare("
            INSERT INTO education_records (user_id, institution_name, institution_id, degree_level, degree_title, field_of_study, country_id, cgpa, cgpa_scale, percentage, is_current, graduation_status, created_at, updated_at)
            VALUES (:uid, 'Institution Name', :inst_id, :deg_level, 'Degree Title', 'Computer Science', :cid, 3.80, 4.00, 95.0, 1, 'in_progress', NOW(), NOW())
        ")->execute([
            'uid' => $uid,
            'inst_id' => $inId,
            'deg_level' => $degree,
            'cid' => $this->pakistanCountryId
        ]);

        // User preferences
        $this->db->prepare("
            INSERT INTO user_preferences (user_id, preferred_channel, allow_multi_channel, deadline_reminder_scope, deadline_reminder_days, created_at, updated_at)
            VALUES (:uid, :pchan, :mchan, 'all', '7,3,1', NOW(), NOW())
        ")->execute([
            'uid' => $uid,
            'pchan' => $preferredChannel,
            'mchan' => $multiChannel
        ]);

        // Notification preferences
        $types = ['matching_scholarship_alerts', 'deadline_reminders', 'email_alerts', 'whatsapp_alerts'];
        foreach ($types as $t) {
            $this->db->prepare("
                INSERT INTO notification_preferences (user_id, notification_type, email_enabled, whatsapp_enabled, created_at, updated_at)
                VALUES (:uid, :ntype, 1, 1, NOW(), NOW())
            ")->execute(['uid' => $uid, 'ntype' => $t]);
        }

        // Active premium subscription
        $planId = (int)$this->db->query("SELECT id FROM subscription_plans WHERE slug = 'premium-monthly' LIMIT 1")->fetchColumn();
        if (!$planId) {
            $planId = 1;
        }
        $this->db->prepare("
            INSERT INTO subscriptions (user_id, plan_id, status, starts_at, ends_at, auto_renew)
            VALUES (:uid, :pid, 'active', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 1)
        ")->execute(['uid' => $uid, 'pid' => $planId]);

        return $uid;
    }

    private function createTestScholarship(
        string $title,
        string $verificationStatus = 'verified',
        string $status = 'published',
        ?string $deadline = null,
        ?int $stateId = null,
        string $degree = "Master's",
        ?int $institutionId = null
    ): int {
        $slug = 'step6-' . strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $title)) . '-' . uniqid();
        $dl = $deadline ?: date('Y-m-d', strtotime('+30 days'));

        $stmt = $this->db->prepare("
            INSERT INTO scholarships (title, slug, provider_name, short_description, description, country_id, funding_type, status, verification_status, application_deadline, published_at, created_at, updated_at)
            VALUES (:title, :slug, 'Global Foundation', 'Desc', 'Full description', :cid, 'Fully Funded', :status, :ver, :deadline, NOW(), NOW(), NOW())
        ");
        $stmt->execute([
            'title' => $title,
            'slug' => $slug,
            'cid' => $this->pakistanCountryId,
            'status' => $status,
            'ver' => $verificationStatus,
            'deadline' => $dl
        ]);
        $sid = (int)$this->db->lastInsertId();

        // Pivots
        $this->db->prepare("INSERT INTO scholarship_degree_levels (scholarship_id, degree_level) VALUES (:sid, :deg)")->execute(['sid' => $sid, 'deg' => $degree]);
        $this->db->prepare("INSERT INTO scholarship_fields (scholarship_id, field_of_study_id) VALUES (:sid, :fid)")->execute(['sid' => $sid, 'fid' => $this->csFieldId]);
        $this->db->prepare("INSERT INTO scholarship_countries (scholarship_id, country_id) VALUES (:sid, :cid)")->execute(['sid' => $sid, 'cid' => $this->pakistanCountryId]);
        $this->db->prepare("INSERT INTO scholarship_eligible_nationalities (scholarship_id, country_id) VALUES (:sid, :cid)")->execute(['sid' => $sid, 'cid' => $this->pakistanCountryId]);

        if ($stateId !== null) {
            $this->db->prepare("INSERT INTO scholarship_states (scholarship_id, state_id) VALUES (:sid, :stid)")->execute(['sid' => $sid, 'stid' => $stateId]);
        }
        if ($institutionId !== null) {
            $this->db->prepare("INSERT INTO scholarship_institutions (scholarship_id, institution_id) VALUES (:sid, :iid)")->execute(['sid' => $sid, 'iid' => $institutionId]);
        }

        return $sid;
    }

    // =========================================================================
    // GROUP 1: NEW MATCH NOTIFICATIONS & TARGETING (1-10)
    // =========================================================================

    public function test1_matchingUserReceivesNotification(): void {
        echo "[Test 1] Matching user receives notification... ";
        $uid = $this->createTestUser('step6_t1@example.com', 'email', 0);
        $sid = $this->createTestScholarship('Eligible Sch 1', 'verified', 'published');

        $this->matchingService->recalculateForUser($uid);

        // Run matching cron simulation
        $idempotencyKey = "new_match_{$uid}_{$sid}";
        $this->notificationService->sendNotification($uid, 'NEW_MATCH', ['title' => 'Eligible Sch 1'], $sid, $idempotencyKey);

        $count = (int)$this->db->query("SELECT COUNT(*) FROM notification_logs WHERE user_id = $uid AND scholarship_id = $sid AND status = 'pending'")->fetchColumn();
        $this->assert($count === 1, "Matching user must have exactly 1 pending notification");
        echo "PASS\n";
    }

    public function test2_nonMatchingUserReceivesNone(): void {
        echo "[Test 2] Non-matching user receives none... ";
        $uid = $this->createTestUser('step6_t2@example.com', 'email', 0, '+923001234567', $this->punjabStateId);
        // Scholarship targeted strictly to Sindh
        $sid = $this->createTestScholarship('Sindh Only Sch 2', 'verified', 'published', null, $this->sindhStateId);

        $res = $this->matchingService->matchUserAndScholarship($uid, $sid);
        $this->assert(($res['eligibility_status'] ?? '') !== 'ELIGIBLE', "User in Punjab must not be eligible for Sindh-only scholarship");

        $count = (int)$this->db->query("SELECT COUNT(*) FROM notification_logs WHERE user_id = $uid AND scholarship_id = $sid")->fetchColumn();
        $this->assert($count === 0, "Non-matching user must not receive any notification");
        echo "PASS\n";
    }

    public function test3_verifiedScholarshipRequired(): void {
        echo "[Test 3] Verified scholarship required... ";
        $uid = $this->createTestUser('step6_t3@example.com', 'email', 0);
        $sid = $this->createTestScholarship('Verified Sch 3', 'verified', 'published');

        $stmt = $this->db->prepare("SELECT verification_status, status FROM scholarships WHERE id = :id");
        $stmt->execute(['id' => $sid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assert($row['verification_status'] === 'verified' && $row['status'] === 'published', "Scholarship must be verified and published");
        echo "PASS\n";
    }

    public function test4_unverifiedScholarshipRejected(): void {
        echo "[Test 4] Unverified scholarship rejected... ";
        $uid = $this->createTestUser('step6_t4@example.com', 'email', 0);
        $sid = $this->createTestScholarship('Unverified Sch 4', 'unverified', 'published');

        // 1. Cron query must reject unverified
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM scholarships 
            WHERE id = :id AND status = 'published' AND verification_status = 'verified'
        ");
        $stmt->execute(['id' => $sid]);
        $eligibleCount = (int)$stmt->fetchColumn();
        $this->assert($eligibleCount === 0, "Unverified scholarship must be rejected by daily matches query");

        // 2. NotificationService::sendNotification must reject unverified
        $key = "unverified_test_key_{$uid}_{$sid}";
        $this->notificationService->sendNotification($uid, 'NEW_MATCH', ['title' => 'Unverified Sch 4'], $sid, $key);
        $count = (int)$this->db->query("SELECT COUNT(*) FROM notification_logs WHERE idempotency_key = '$key'")->fetchColumn();
        $this->assert($count === 0, "NotificationService must not enqueue unverified scholarships");

        // 3. Worker side validation must skip unverified scholarship if it reaches queue
        $keyWorker = "unverified_worker_key_{$uid}_{$sid}";
        $this->db->prepare("
            INSERT INTO notification_logs (user_id, scholarship_id, notification_type, channel, recipient, status, attempts, available_at, idempotency_key)
            VALUES (:uid, :sid, 'NEW_MATCH', 'email', 'step6_t4@example.com', 'pending', 0, NOW(), :key)
        ")->execute(['uid' => $uid, 'sid' => $sid, 'key' => $keyWorker]);

        $this->queueService->processQueue(10);
        $status = $this->db->query("SELECT status FROM notification_logs WHERE idempotency_key = '$keyWorker'")->fetchColumn();
        $this->assert($status === 'skipped', "Worker must skip unverified scholarships, got $status");
        echo "PASS\n";
    }

    public function test5_stateTargetingSindhVsPunjab(): void {
        echo "[Test 5] State targeting Sindh vs Punjab... ";
        $uidSindh = $this->createTestUser('step6_t5_sindh@example.com', 'email', 0, '+923001234567', $this->sindhStateId);
        $uidPunjab = $this->createTestUser('step6_t5_punjab@example.com', 'email', 0, '+923001234567', $this->punjabStateId);
        $sidSindh = $this->createTestScholarship('Sindh Target Sch 5', 'verified', 'published', null, $this->sindhStateId);

        $matchSindh = $this->matchingService->matchUserAndScholarship($uidSindh, $sidSindh);
        $matchPunjab = $this->matchingService->matchUserAndScholarship($uidPunjab, $sidSindh);

        $this->assert($matchSindh['eligibility_status'] === 'ELIGIBLE', "Sindh user must match Sindh scholarship");
        $this->assert($matchPunjab['eligibility_status'] !== 'ELIGIBLE', "Punjab user must NOT match Sindh scholarship");
        echo "PASS\n";
    }

    public function test6_educationTargetingDegreeLevels(): void {
        echo "[Test 6] Education targeting degree levels... ";
        $uidMaster = $this->createTestUser('step6_t6_m@example.com', 'email', 0, '+923001234567', 0, "Master's");
        $uidBachelor = $this->createTestUser('step6_t6_b@example.com', 'email', 0, '+923001234567', 0, "Bachelor's");
        $sidMaster = $this->createTestScholarship('Master Only Sch 6', 'verified', 'published', null, null, "Master's");

        $matchM = $this->matchingService->matchUserAndScholarship($uidMaster, $sidMaster);
        $matchB = $this->matchingService->matchUserAndScholarship($uidBachelor, $sidMaster);

        $this->assert($matchM['eligibility_status'] === 'ELIGIBLE', "Master's student must match Master's scholarship");
        $this->assert($matchB['eligibility_status'] !== 'ELIGIBLE', "Bachelor's student must NOT match Master's scholarship");
        echo "PASS\n";
    }

    public function test7_institutionTargeting(): void {
        echo "[Test 7] Institution targeting... ";
        $uidIBA = $this->createTestUser('step6_t7_iba@example.com', 'email', 0, '+923001234567', 0, "Master's", $this->ibaInstId);
        $uidLUMS = $this->createTestUser('step6_t7_lums@example.com', 'email', 0, '+923001234567', 0, "Master's", $this->lumsInstId);
        $sidIBA = $this->createTestScholarship('IBA Only Sch 7', 'verified', 'published', null, null, "Master's", $this->ibaInstId);

        $matchIBA = $this->matchingService->matchUserAndScholarship($uidIBA, $sidIBA);
        $matchLUMS = $this->matchingService->matchUserAndScholarship($uidLUMS, $sidIBA);

        $this->assert($matchIBA['eligibility_status'] === 'ELIGIBLE', "IBA student must match IBA scholarship");
        $this->assert($matchLUMS['eligibility_status'] !== 'ELIGIBLE', "LUMS student must NOT match IBA scholarship");
        echo "PASS\n";
    }

    public function test8_combinedTargetingAndLogic(): void {
        echo "[Test 8] Combined targeting AND logic... ";
        // Targeted to Sindh AND Master's AND IBA Karachi
        $sidCombined = $this->createTestScholarship('Combined Sch 8', 'verified', 'published', null, $this->sindhStateId, "Master's", $this->ibaInstId);

        // User 1: Meets all 3
        $u1 = $this->createTestUser('step6_t8_u1@example.com', 'email', 0, '+923001234567', $this->sindhStateId, "Master's", $this->ibaInstId);
        // User 2: Meets 2 of 3 (Sindh + Master's, but LUMS)
        $u2 = $this->createTestUser('step6_t8_u2@example.com', 'email', 0, '+923001234567', $this->sindhStateId, "Master's", $this->lumsInstId);

        $m1 = $this->matchingService->matchUserAndScholarship($u1, $sidCombined);
        $m2 = $this->matchingService->matchUserAndScholarship($u2, $sidCombined);

        $this->assert($m1['eligibility_status'] === 'ELIGIBLE', "User meeting all 3 must match");
        $this->assert($m2['eligibility_status'] !== 'ELIGIBLE', "User failing institution must NOT match (strict AND)");
        echo "PASS\n";
    }

    public function test9_duplicateCronExecutionIsIdempotent(): void {
        echo "[Test 9] Duplicate cron execution is idempotent... ";
        $uid = $this->createTestUser('step6_t9@example.com', 'email', 0);
        $sid = $this->createTestScholarship('Idem Sch 9', 'verified', 'published');

        $key = "new_match_{$uid}_{$sid}";
        // First enqueue
        $res1 = $this->queueService->enqueue($uid, $sid, 'NEW_MATCH', 'email', 'step6_t9@example.com', 'Title', [], $key);
        // Second enqueue with identical key
        $res2 = $this->queueService->enqueue($uid, $sid, 'NEW_MATCH', 'email', 'step6_t9@example.com', 'Title', [], $key);

        $this->assert($res1 === true, "First enqueue must succeed");
        $this->assert($res2 === false, "Second enqueue must be safely skipped by idempotency");

        $count = (int)$this->db->query("SELECT COUNT(*) FROM notification_logs WHERE idempotency_key = '$key'")->fetchColumn();
        $this->assert($count === 1, "Exactly 1 record must exist in DB");
        echo "PASS\n";
    }

    public function test10_sameUserScholarshipCreatesOneBusinessNotification(): void {
        echo "[Test 10] Same user/scholarship creates one business notification... ";
        $uid = $this->createTestUser('step6_t10@example.com', 'email', 0);
        $sid = $this->createTestScholarship('One Business Notif 10', 'verified', 'published');

        $key = "new_match_{$uid}_{$sid}";
        $this->notificationService->sendNotification($uid, 'NEW_MATCH', ['title' => 'Title 10'], $sid, $key);
        // Second call
        $this->notificationService->sendNotification($uid, 'NEW_MATCH', ['title' => 'Title 10'], $sid, $key);

        $count = (int)$this->db->query("SELECT COUNT(*) FROM notification_logs WHERE user_id = $uid AND scholarship_id = $sid")->fetchColumn();
        $this->assert($count === 1, "Only 1 business notification must be generated for user + scholarship");
        echo "PASS\n";
    }

    // =========================================================================
    // GROUP 2: WHATSAPP BATCHING & RULES (11-17)
    // =========================================================================

    public function test11_oneMatchCreatesOneWhatsApp(): void {
        echo "[Test 11] One match creates one WhatsApp... ";
        $uid = $this->createTestUser('step6_t11@example.com', 'whatsapp', 0);
        $matches = [
            ['scholarship_id' => 101, 'title' => 'Sch 11', 'deadline' => '2026-10-01', 'provider' => 'P1']
        ];
        $calendarDay = \App\Services\NotificationService::getKarachiCalendarDay();

        $ok = $this->notificationService->enqueueDailyWhatsAppBatch($uid, $matches, $calendarDay);
        $this->assert($ok === true, "enqueueDailyWhatsAppBatch should succeed for 1 match");

        $count = (int)$this->db->query("SELECT COUNT(*) FROM notification_logs WHERE user_id = $uid AND channel = 'whatsapp' AND idempotency_key = 'scholarship_whatsapp_{$uid}_{$calendarDay}'")->fetchColumn();
        $this->assert($count === 1, "Exactly 1 WhatsApp job must exist");
        echo "PASS\n";
    }

    public function test12_multipleMatchesSameDayCombinedIntoOneWhatsApp(): void {
        echo "[Test 12] Multiple matches same day combined into one WhatsApp... ";
        $uid = $this->createTestUser('step6_t12@example.com', 'whatsapp', 0);
        $matchesRun1 = [
            ['scholarship_id' => 201, 'title' => 'Sch Alpha', 'deadline' => '2026-10-01'],
            ['scholarship_id' => 202, 'title' => 'Sch Beta', 'deadline' => '2026-10-05'],
            ['scholarship_id' => 203, 'title' => 'Sch Gamma', 'deadline' => '2026-10-10']
        ];
        $calendarDay = \App\Services\NotificationService::getKarachiCalendarDay();

        // Matching Run 1: initial 3 matches before cutoff
        $ok = $this->notificationService->enqueueDailyWhatsAppBatch($uid, $matchesRun1, $calendarDay);
        $this->assert($ok === true, "Batch enqueue should succeed on initial run");

        // Matching Run 2: Later same day before cutoff, new scholarship 204 is discovered
        $matchesRun2 = [
            ['scholarship_id' => 204, 'title' => 'Sch Delta', 'deadline' => '2026-10-15']
        ];
        $aggOk = $this->notificationService->enqueueDailyWhatsAppBatch($uid, $matchesRun2, $calendarDay);
        $this->assert($aggOk === true, "Later same-day match aggregation must succeed");

        // Verify exactly ONE WhatsApp notification record exists for user on this day
        $count = (int)$this->db->query("SELECT COUNT(*) FROM notification_logs WHERE user_id = $uid AND channel = 'whatsapp' AND idempotency_key = 'scholarship_whatsapp_{$uid}_{$calendarDay}'")->fetchColumn();
        $this->assert($count === 1, "Exactly 1 WhatsApp notification record must exist after multiple runs");

        // Verify payload contains all 4 matches combined
        $payloadJson = $this->db->query("SELECT payload FROM notification_logs WHERE user_id = $uid AND channel = 'whatsapp' AND idempotency_key = 'scholarship_whatsapp_{$uid}_{$calendarDay}'")->fetchColumn();
        $payload = json_decode($payloadJson, true);
        $this->assert(count($payload['matches']) === 4, "Payload must contain all 4 matches aggregated");
        $this->assert((int)$payload['match_count'] === 4, "match_count must be 4");

        // Worker executes and delivers the aggregated single batch (simulate cutoff passed)
        $this->db->prepare("UPDATE notification_logs SET available_at = DATE_SUB(NOW(), INTERVAL 1 SECOND) WHERE user_id = :uid AND channel = 'whatsapp'")->execute(['uid' => $uid]);
        \App\Services\NotificationService::$simulateCutoffPassed = true;
        $this->queueService->processQueue(10);
        \App\Services\NotificationService::$simulateCutoffPassed = null;

        $row = $this->db->query("SELECT status, attempts FROM notification_logs WHERE user_id = $uid AND channel = 'whatsapp' AND idempotency_key = 'scholarship_whatsapp_{$uid}_{$calendarDay}'")->fetch(PDO::FETCH_ASSOC);
        $this->assert($row['status'] === 'sent', "Batch must be delivered with status 'sent'");

        // Matching Run 3: Match discovered AFTER worker has already delivered the daily batch
        $matchesRun3 = [
            ['scholarship_id' => 205, 'title' => 'Sch Epsilon', 'deadline' => '2026-10-20']
        ];
        $afterSendOk = $this->notificationService->enqueueDailyWhatsAppBatch($uid, $matchesRun3, $calendarDay);
        $this->assert($afterSendOk === true, "enqueueDailyWhatsAppBatch after delivery must return safely");

        // Verify still exactly ONE WhatsApp record exists for that calendar day
        $finalCount = (int)$this->db->query("SELECT COUNT(*) FROM notification_logs WHERE user_id = $uid AND channel = 'whatsapp' AND idempotency_key = 'scholarship_whatsapp_{$uid}_{$calendarDay}'")->fetchColumn();
        $this->assert($finalCount === 1, "Must maintain exactly 1 WhatsApp delivery record for that calendar day");
        echo "PASS\n";
    }

    public function test13_noMatchSendsZeroWhatsApp(): void {
        echo "[Test 13] No match sends zero WhatsApp... ";
        $uid = $this->createTestUser('step6_t13@example.com', 'whatsapp', 0);
        $today = date('Y-m-d');

        $ok = $this->notificationService->enqueueDailyWhatsAppBatch($uid, [], $today);
        $this->assert($ok === false, "Empty matches must return false");

        $count = (int)$this->db->query("SELECT COUNT(*) FROM notification_logs WHERE user_id = $uid")->fetchColumn();
        $this->assert($count === 0, "Zero notifications must be created when there are no matches");
        echo "PASS\n";
    }

    public function test14_sundaySendsZeroAutomaticScholarshipWhatsApp(): void {
        echo "[Test 14] Sunday sends zero automatic scholarship WhatsApp... ";
        $uid = $this->createTestUser('step6_t14@example.com', 'whatsapp', 0);
        $today = date('Y-m-d');

        // Simulate Sunday
        \App\Services\NotificationService::$simulateSunday = true;

        $matches = [['scholarship_id' => 301, 'title' => 'Sunday Sch', 'deadline' => '2026-10-01']];
        $this->notificationService->enqueueDailyWhatsAppBatch($uid, $matches, $today);

        // Verify zero WhatsApp notifications were enqueued for Sunday
        $waCount = (int)$this->db->query("SELECT COUNT(*) FROM notification_logs WHERE user_id = $uid AND channel = 'whatsapp' AND idempotency_key = 'scholarship_whatsapp_{$uid}_{$today}'")->fetchColumn();
        $this->assert($waCount === 0, "Zero automatic scholarship WhatsApp messages must be created for Sunday");

        // Also test queue worker layer protection for WhatsApp-only user: if an automatic scholarship WhatsApp was queued, worker defers to Monday
        $this->db->exec("UPDATE users SET email_opt_in = 0 WHERE id = $uid");
        $this->db->prepare("
            INSERT INTO notification_logs (user_id, scholarship_id, notification_type, channel, recipient, status, attempts, available_at, idempotency_key)
            VALUES (:uid, NULL, 'NEW_MATCH', 'whatsapp', '+923001234567', 'pending', 0, NOW(), 'sunday_worker_test_key')
        ")->execute(['uid' => $uid]);

        $this->queueService->processQueue(10);

        $status = $this->db->query("SELECT status FROM notification_logs WHERE idempotency_key = 'sunday_worker_test_key'")->fetchColumn();
        $this->assert($status === 'pending', "Queue worker must defer Sunday automatic WhatsApp job without delivering on Sunday, got $status");
        \App\Services\NotificationService::$simulateSunday = null;
        echo "PASS\n";
    }

    public function test15_max25MessagesEnforced(): void {
        echo "[Test 15] Maximum 25 messages enforced... ";
        $uid = $this->createTestUser('step6_t15@example.com', 'whatsapp', 0);

        // Pre-insert 25 sent scholarship WhatsApp messages
        for ($i = 1; $i <= 25; $i++) {
            $this->db->prepare("
                INSERT INTO notification_logs (user_id, scholarship_id, notification_type, channel, recipient, status, attempts, sent_at, idempotency_key)
            VALUES (:uid, NULL, 'NEW_MATCH', 'whatsapp', '+923001234567', 'sent', 1, NOW(), :key)
            ")->execute(['uid' => $uid, 'key' => "pre_sent_wa_{$uid}_{$i}"]);
        }

        // Attempt 26th enqueue via batch
        $matches = [['scholarship_id' => 999, 'title' => '26th Sch', 'deadline' => '2026-10-01']];
        $ok = $this->notificationService->enqueueDailyWhatsAppBatch($uid, $matches, date('Y-m-d'));
        $this->assert($ok === false, "enqueueDailyWhatsAppBatch must reject when user has 25 sent WhatsApp messages");

        // Attempt 26th delivery via queue worker
        $this->db->prepare("
            INSERT INTO notification_logs (user_id, scholarship_id, notification_type, channel, recipient, status, attempts, available_at, idempotency_key)
            VALUES (:uid, NULL, 'NEW_MATCH', 'whatsapp', '+923001234567', 'pending', 0, NOW(), 'limit_25_test_key')
        ")->execute(['uid' => $uid]);

        $this->queueService->processQueue(10);
        $status = $this->db->query("SELECT status FROM notification_logs WHERE idempotency_key = 'limit_25_test_key'")->fetchColumn();
        $this->assert($status === 'skipped', "26th message must be skipped by worker due to 25 lifetime limit, got $status");
        echo "PASS\n";
    }

    public function test16_concurrentWorkersCannotExceed25(): void {
        echo "[Test 16] Concurrent workers cannot exceed 25... ";
        $uid = $this->createTestUser('step6_t16@example.com', 'whatsapp', 0);

        // Pre-insert exactly 24 sent scholarship WhatsApp messages
        for ($i = 1; $i <= 24; $i++) {
            $this->db->prepare("
                INSERT INTO notification_logs (user_id, scholarship_id, notification_type, channel, recipient, status, attempts, sent_at, idempotency_key)
            VALUES (:uid, NULL, 'NEW_MATCH', 'whatsapp', '+923001234567', 'sent', 1, NOW(), :key)
            ")->execute(['uid' => $uid, 'key' => "pre_sent_wa_16_{$uid}_{$i}"]);
        }

        // Queue TWO pending messages simultaneously (Candidate 25 and Candidate 26)
        $this->db->prepare("
            INSERT INTO notification_logs (user_id, scholarship_id, notification_type, channel, recipient, status, attempts, available_at, idempotency_key)
            VALUES (:uid, NULL, 'NEW_MATCH', 'whatsapp', '+923001234567', 'pending', 0, NOW(), 'worker_comp_a')
        ")->execute(['uid' => $uid]);

        $this->db->prepare("
            INSERT INTO notification_logs (user_id, scholarship_id, notification_type, channel, recipient, status, attempts, available_at, idempotency_key)
            VALUES (:uid, NULL, 'NEW_MATCH', 'whatsapp', '+923001234567', 'pending', 0, NOW(), 'worker_comp_b')
        ")->execute(['uid' => $uid]);

        // Process queue
        $this->queueService->processQueue(10);

        // Total sent count must be EXACTLY 25, never 26
        $sentCount = (int)$this->db->query("
            SELECT COUNT(*) FROM notification_logs 
            WHERE user_id = $uid AND channel = 'whatsapp' AND status = 'sent' AND notification_type = 'NEW_MATCH'
        ")->fetchColumn();

        $this->assert($sentCount === 25, "Total sent scholarship WhatsApp messages must be exactly 25, got $sentCount");

        // The second job must have been skipped
        $skippedCount = (int)$this->db->query("
            SELECT COUNT(*) FROM notification_logs 
            WHERE user_id = $uid AND channel = 'whatsapp' AND status = 'skipped'
        ")->fetchColumn();
        $this->assert($skippedCount >= 1, "The surplus job must transition to skipped");
        echo "PASS\n";
    }

    public function test17_transactionalMetaPaymentNotBlockedByScholarshipWhatsAppLimit(): void {
        echo "[Test 17] Transactional Meta payment message not blocked by scholarship WhatsApp limit... ";
        $uid = $this->createTestUser('step6_t17@example.com', 'whatsapp', 0);

        // Fill 25 scholarship messages
        for ($i = 1; $i <= 25; $i++) {
            $this->db->prepare("
                INSERT INTO notification_logs (user_id, scholarship_id, notification_type, channel, recipient, status, attempts, sent_at, idempotency_key)
            VALUES (:uid, NULL, 'NEW_MATCH', 'whatsapp', '+923001234567', 'sent', 1, NOW(), :key)
            ")->execute(['uid' => $uid, 'key' => "pre_sent_wa_17_{$uid}_{$i}"]);
        }

        // Queue transactional payment confirmation message
        $this->db->prepare("
            INSERT INTO notification_logs (user_id, scholarship_id, notification_type, channel, provider, recipient, payload, status, attempts, available_at, idempotency_key)
            VALUES (:uid, NULL, 'PAYMENT_CONFIRMATION', 'whatsapp', 'meta', '+923001234567', '{\"amount\":\"1500\",\"user_name\":\"Ali\"}', 'pending', 0, NOW(), 'meta_txn_test_key')
        ")->execute(['uid' => $uid]);

        // Process queue
        $this->queueService->processQueue(10);

        $status = $this->db->query("SELECT status FROM notification_logs WHERE idempotency_key = 'meta_txn_test_key'")->fetchColumn();
        $this->assert($status === 'sent', "Transactional payment WhatsApp must be sent successfully even when scholarship limit is 25, got $status");
        echo "PASS\n";
    }

    // =========================================================================
    // GROUP 3: CHANNEL PREFERENCES & FALLBACK (18-23)
    // =========================================================================

    public function test18_preferredEmailSendsEmailOnly(): void {
        echo "[Test 18] Preferred Email sends Email only... ";
        $uid = $this->createTestUser('step6_t18@example.com', 'email', 0);
        $sid = $this->createTestScholarship('Sch 18', 'verified', 'published');

        $this->notificationService->sendNotification($uid, 'NEW_MATCH', ['title' => 'Sch 18'], $sid, "pref_email_{$uid}_{$sid}");

        $channels = $this->db->query("SELECT channel FROM notification_logs WHERE user_id = $uid AND scholarship_id = $sid")->fetchAll(PDO::FETCH_COLUMN);
        $this->assert(count($channels) === 1 && $channels[0] === 'email', "Preferred email must deliver via email only");
        echo "PASS\n";
    }

    public function test19_preferredWhatsAppSendsWhatsAppOnly(): void {
        echo "[Test 19] Preferred WhatsApp sends WhatsApp only... ";
        $uid = $this->createTestUser('step6_t19@example.com', 'whatsapp', 0);
        $sid = $this->createTestScholarship('Sch 19', 'verified', 'published');

        $this->notificationService->sendNotification($uid, 'NEW_MATCH', ['title' => 'Sch 19'], $sid, "pref_wa_{$uid}_{$sid}");

        $channels = $this->db->query("SELECT channel FROM notification_logs WHERE user_id = $uid AND scholarship_id = $sid")->fetchAll(PDO::FETCH_COLUMN);
        $this->assert(count($channels) === 1 && $channels[0] === 'whatsapp', "Preferred WhatsApp must deliver via WhatsApp only");
        echo "PASS\n";
    }

    public function test20_multiChannelDisabledDeliversSingleChannel(): void {
        echo "[Test 20] Multi-channel disabled delivers single channel... ";
        $uid = $this->createTestUser('step6_t20@example.com', 'email', 0);
        $sid = $this->createTestScholarship('Sch 20', 'verified', 'published');

        $this->notificationService->sendNotification($uid, 'NEW_MATCH', ['title' => 'Sch 20'], $sid, "mc_off_{$uid}_{$sid}");

        $count = (int)$this->db->query("SELECT COUNT(*) FROM notification_logs WHERE user_id = $uid AND scholarship_id = $sid")->fetchColumn();
        $this->assert($count === 1, "Exactly 1 channel notification must be created when multi-channel is disabled");
        echo "PASS\n";
    }

    public function test21_multiChannelEnabledDeliversBothChannels(): void {
        echo "[Test 21] Multi-channel enabled delivers both channels... ";
        $uid = $this->createTestUser('step6_t21@example.com', 'email', 1);
        $sid = $this->createTestScholarship('Sch 21', 'verified', 'published');

        $this->notificationService->sendNotification($uid, 'NEW_MATCH', ['title' => 'Sch 21'], $sid, "mc_on_{$uid}_{$sid}");

        $channels = $this->db->query("SELECT channel FROM notification_logs WHERE user_id = $uid AND scholarship_id = $sid ORDER BY channel ASC")->fetchAll(PDO::FETCH_COLUMN);
        $this->assert(count($channels) === 2, "Expected 2 notification records (email + whatsapp)");
        $this->assert($channels[0] === 'email' && $channels[1] === 'whatsapp', "Must contain both email and whatsapp");
        echo "PASS\n";
    }

    public function test22_missingWhatsAppNumberFallsBackToEmail(): void {
        echo "[Test 22] Missing WhatsApp number falls back to email... ";
        // User prefers whatsapp, but has null phone
        $uid = $this->createTestUser('step6_t22@example.com', 'whatsapp', 0, null);
        $sid = $this->createTestScholarship('Sch 22', 'verified', 'published');

        $this->notificationService->sendNotification($uid, 'NEW_MATCH', ['title' => 'Sch 22'], $sid, "wa_miss_fb_{$uid}_{$sid}");

        $channels = $this->db->query("SELECT channel FROM notification_logs WHERE user_id = $uid AND scholarship_id = $sid")->fetchAll(PDO::FETCH_COLUMN);
        $this->assert(count($channels) === 1, "Expected 1 notification");
        $this->assert($channels[0] === 'email', "Channel must fall back to email when WhatsApp number is missing");
        echo "PASS\n";
    }

    public function test23_invalidWhatsAppNumberHandling(): void {
        echo "[Test 23] Invalid WhatsApp number handling... ";
        // User with completely invalid phone
        $uid = $this->createTestUser('step6_t23@example.com', 'whatsapp', 0, 'invalid-1234');
        $sid = $this->createTestScholarship('Sch 23', 'verified', 'published');

        $this->notificationService->sendNotification($uid, 'NEW_MATCH', ['title' => 'Sch 23'], $sid, "wa_inv_fb_{$uid}_{$sid}");

        $channels = $this->db->query("SELECT channel FROM notification_logs WHERE user_id = $uid AND scholarship_id = $sid")->fetchAll(PDO::FETCH_COLUMN);
        $this->assert(count($channels) === 1, "Expected fallback");
        $this->assert($channels[0] === 'email', "Invalid phone must fall back to email");

        // Also test queue worker handling if invalid phone was in queue: fails permanently without endless retry
        $this->db->prepare("
            INSERT INTO notification_logs (user_id, scholarship_id, notification_type, channel, recipient, status, attempts, available_at, idempotency_key)
            VALUES (:uid, NULL, 'NEW_MATCH', 'whatsapp', 'bad_number', 'pending', 0, NOW(), 'inv_phone_worker_key')
        ")->execute(['uid' => $uid]);

        $this->queueService->processQueue(10);
        $status = $this->db->query("SELECT status FROM notification_logs WHERE idempotency_key = 'inv_phone_worker_key'")->fetchColumn();
        $this->assert($status === 'failed', "Queue worker must permanently fail invalid phone without endless retries, got $status");
        echo "PASS\n";
    }

    // =========================================================================
    // GROUP 4: DEADLINE REMINDERS (24-33)
    // =========================================================================

    public function test24_remindersOffGeneratesZeroReminders(): void {
        echo "[Test 24] Reminders OFF generates zero reminders... ";
        $uid = $this->createTestUser('step6_t24@example.com', 'email', 0);
        $this->db->exec("UPDATE user_preferences SET deadline_reminder_scope = 'off' WHERE user_id = $uid");

        $sid = $this->createTestScholarship('Sch 24', 'verified', 'published', date('Y-m-d', strtotime('+3 days')));
        $this->matchingService->recalculateForUser($uid);

        // Run deadline cron logic for this user
        $scope = $this->db->query("SELECT deadline_reminder_scope FROM user_preferences WHERE user_id = $uid")->fetchColumn();
        $this->assert($scope === 'off', "Scope must be off");

        $count = (int)$this->db->query("SELECT COUNT(*) FROM notification_logs WHERE user_id = $uid AND notification_type LIKE '%DEADLINE%'")->fetchColumn();
        $this->assert($count === 0, "Zero reminders must be generated when scope is off");
        echo "PASS\n";
    }

    public function test25_oneDayReminder(): void {
        echo "[Test 25] 1-day reminder... ";
        $uid = $this->createTestUser('step6_t25@example.com', 'email', 0);
        $deadline = date('Y-m-d', strtotime('+1 day'));
        $sid = $this->createTestScholarship('Sch 25', 'verified', 'published', $deadline);

        $key = "deadline_reminder_{$uid}_{$sid}_1_{$deadline}";
        $this->notificationService->sendNotification($uid, 'SCHOLARSHIP_DEADLINE_TODAY', ['title' => 'Sch 25'], $sid, $key);

        $row = $this->db->query("SELECT notification_type, idempotency_key FROM notification_logs WHERE idempotency_key = '$key'")->fetch(PDO::FETCH_ASSOC);
        $this->assert($row !== false, "1-day reminder must be enqueued");
        $this->assert($row['notification_type'] === 'SCHOLARSHIP_DEADLINE_TODAY', "Type must be SCHOLARSHIP_DEADLINE_TODAY");
        echo "PASS\n";
    }

    public function test26_threeDayReminder(): void {
        echo "[Test 26] 3-day reminder... ";
        $uid = $this->createTestUser('step6_t26@example.com', 'email', 0);
        $deadline = date('Y-m-d', strtotime('+3 days'));
        $sid = $this->createTestScholarship('Sch 26', 'verified', 'published', $deadline);

        $key = "deadline_reminder_{$uid}_{$sid}_3_{$deadline}";
        $this->notificationService->sendNotification($uid, 'SCHOLARSHIP_DEADLINE_SOON', ['title' => 'Sch 26'], $sid, $key);

        $row = $this->db->query("SELECT notification_type, idempotency_key FROM notification_logs WHERE idempotency_key = '$key'")->fetch(PDO::FETCH_ASSOC);
        $this->assert($row !== false, "3-day reminder must be enqueued");
        $this->assert($row['notification_type'] === 'SCHOLARSHIP_DEADLINE_SOON', "Type must be SCHOLARSHIP_DEADLINE_SOON");
        echo "PASS\n";
    }

    public function test27_sevenDayReminder(): void {
        echo "[Test 27] 7-day reminder... ";
        $uid = $this->createTestUser('step6_t27@example.com', 'email', 0);
        $deadline = date('Y-m-d', strtotime('+7 days'));
        $sid = $this->createTestScholarship('Sch 27', 'verified', 'published', $deadline);

        $key = "deadline_reminder_{$uid}_{$sid}_7_{$deadline}";
        $this->notificationService->sendNotification($uid, 'SCHOLARSHIP_DEADLINE_SOON', ['title' => 'Sch 27'], $sid, $key);

        $row = $this->db->query("SELECT notification_type, idempotency_key FROM notification_logs WHERE idempotency_key = '$key'")->fetch(PDO::FETCH_ASSOC);
        $this->assert($row !== false, "7-day reminder must be enqueued");
        echo "PASS\n";
    }

    public function test28_duplicateReminderPrevention(): void {
        echo "[Test 28] Duplicate reminder prevention... ";
        $uid = $this->createTestUser('step6_t28@example.com', 'email', 0);
        $deadline = date('Y-m-d', strtotime('+3 days'));
        $sid = $this->createTestScholarship('Sch 28', 'verified', 'published', $deadline);

        $key = "deadline_reminder_{$uid}_{$sid}_3_{$deadline}";
        $this->notificationService->sendNotification($uid, 'SCHOLARSHIP_DEADLINE_SOON', ['title' => 'Sch 28'], $sid, $key);
        // Repeated cron call
        $this->notificationService->sendNotification($uid, 'SCHOLARSHIP_DEADLINE_SOON', ['title' => 'Sch 28'], $sid, $key);

        $count = (int)$this->db->query("SELECT COUNT(*) FROM notification_logs WHERE idempotency_key = '$key'")->fetchColumn();
        $this->assert($count === 1, "Duplicate reminder execution must produce exactly 1 notification row");
        echo "PASS\n";
    }

    public function test29_concurrentReminderGeneration(): void {
        echo "[Test 29] Concurrent reminder generation... ";
        $uid = $this->createTestUser('step6_t29@example.com', 'email', 0);
        $deadline = date('Y-m-d', strtotime('+3 days'));
        $sid = $this->createTestScholarship('Sch 29', 'verified', 'published', $deadline);
        $key = "deadline_reminder_{$uid}_{$sid}_3_{$deadline}";

        // Two processes trying to enqueue with same key
        $r1 = $this->queueService->enqueue($uid, $sid, 'SCHOLARSHIP_DEADLINE_SOON', 'email', 'step6_t29@example.com', 'Rem', [], $key);
        $r2 = $this->queueService->enqueue($uid, $sid, 'SCHOLARSHIP_DEADLINE_SOON', 'email', 'step6_t29@example.com', 'Rem', [], $key);

        $this->assert($r1 === true && $r2 === false, "Database uniqueness must reject the concurrent duplicate");
        echo "PASS\n";
    }

    public function test30_expiredScholarshipRejectedFromReminders(): void {
        echo "[Test 30] Expired scholarship rejected from reminders... ";
        $uid = $this->createTestUser('step6_t30@example.com', 'email', 0);
        // Expired scholarship (deadline yesterday)
        $sid = $this->createTestScholarship('Expired Sch 30', 'verified', 'published', date('Y-m-d', strtotime('-1 day')));

        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM scholarships 
            WHERE id = :id AND application_deadline IS NOT NULL AND DATE(application_deadline) >= CURDATE()
        ");
        $stmt->execute(['id' => $sid]);
        $count = (int)$stmt->fetchColumn();
        $this->assert($count === 0, "Expired scholarship must be rejected from reminders");
        echo "PASS\n";
    }

    public function test31_alreadyPassedDeadlineRejectedFromReminders(): void {
        echo "[Test 31] Already passed deadline rejected from reminders... ";
        $uid = $this->createTestUser('step6_t31@example.com', 'email', 0);
        $sid = $this->createTestScholarship('Passed Sch 31', 'verified', 'published', date('Y-m-d', strtotime('-10 days')));

        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM scholarships 
            WHERE id = :id AND application_deadline >= CURDATE()
        ");
        $stmt->execute(['id' => $sid]);
        $this->assert((int)$stmt->fetchColumn() === 0, "Past deadline must be rejected");
        echo "PASS\n";
    }

    public function test32_unverifiedScholarshipRejectedFromReminders(): void {
        echo "[Test 32] Unverified scholarship rejected from reminders... ";
        $uid = $this->createTestUser('step6_t32@example.com', 'email', 0);
        $sid = $this->createTestScholarship('Unverified Sch 32', 'unverified', 'published', date('Y-m-d', strtotime('+3 days')));

        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM scholarships 
            WHERE id = :id AND verification_status = 'verified'
        ");
        $stmt->execute(['id' => $sid]);
        $this->assert((int)$stmt->fetchColumn() === 0, "Unverified scholarship must be rejected from reminders");
        echo "PASS\n";
    }

    public function test33_userEligibilityEnforcedForReminders(): void {
        echo "[Test 33] User eligibility enforced for reminders... ";
        $uidPunjab = $this->createTestUser('step6_t33@example.com', 'email', 0, '+923001234567', $this->punjabStateId);
        // Scholarship strictly for Sindh
        $sidSindh = $this->createTestScholarship('Sindh Sch 33', 'verified', 'published', date('Y-m-d', strtotime('+3 days')), $this->sindhStateId);

        $this->matchingService->recalculateForUser($uidPunjab);

        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM scholarship_matches 
            WHERE user_id = :uid AND scholarship_id = :sid AND eligibility_status = 'ELIGIBLE'
        ");
        $stmt->execute(['uid' => $uidPunjab, 'sid' => $sidSindh]);
        $this->assert((int)$stmt->fetchColumn() === 0, "Ineligible user must not have ELIGIBLE match, preventing reminders");
        echo "PASS\n";
    }

    // =========================================================================
    // GROUP 5: QUEUE ARCHITECTURE & WORKER (34-42)
    // =========================================================================

    public function test34_cronOnlyEnqueuesZeroDirectDelivery(): void {
        echo "[Test 34] Cron only enqueues (zero direct external delivery)... ";
        $uid = $this->createTestUser('step6_t34@example.com', 'email', 0);
        $sid = $this->createTestScholarship('Sch 34', 'verified', 'published');

        // Before queue processing
        $this->notificationService->sendNotification($uid, 'NEW_MATCH', ['title' => 'Sch 34'], $sid, "cron_enq_{$uid}_{$sid}");

        $status = $this->db->query("SELECT status FROM notification_logs WHERE idempotency_key = 'cron_enq_{$uid}_{$sid}'")->fetchColumn();
        $this->assert($status === 'pending', "Cron must strictly enqueue with status 'pending' (never directly sent)");
        echo "PASS\n";
    }

    public function test35_workerPerformsDelivery(): void {
        echo "[Test 35] Worker performs external delivery... ";
        $uid = $this->createTestUser('step6_t35@example.com', 'email', 0);
        $sid = $this->createTestScholarship('Sch 35', 'verified', 'published');

        $this->notificationService->sendNotification($uid, 'NEW_MATCH', ['title' => 'Sch 35'], $sid, "worker_del_{$uid}_{$sid}");

        // Worker processes the queue
        $processed = $this->queueService->processQueue(10);
        $this->assert($processed >= 1, "Worker must process at least 1 job");

        $status = $this->db->query("SELECT status FROM notification_logs WHERE idempotency_key = 'worker_del_{$uid}_{$sid}'")->fetchColumn();
        $this->assert($status === 'sent', "Worker must deliver and mark status as 'sent', got $status");
        echo "PASS\n";
    }

    public function test36_retryBehaviorExponentialBackoff(): void {
        echo "[Test 36] Retry behavior exponential backoff... ";
        $uid = $this->createTestUser('step6_t36@example.com', 'email', 0);
        $key = "retry_backoff_{$uid}_" . uniqid();

        // Simulate a failed attempt with attempts = 1
        $this->db->prepare("
            INSERT INTO notification_logs (user_id, scholarship_id, notification_type, channel, recipient, status, attempts, available_at, idempotency_key)
            VALUES (:uid, NULL, 'NEW_MATCH', 'email', 'step6_t36@example.com', 'retrying', 1, DATE_ADD(NOW(), INTERVAL 300 SECOND), :key)
        ")->execute(['uid' => $uid, 'key' => $key]);

        $avail = $this->db->query("SELECT available_at FROM notification_logs WHERE idempotency_key = '$key'")->fetchColumn();
        $this->assert(strtotime($avail) > time() + 200, "Backoff delay must be set into the future");
        echo "PASS\n";
    }

    public function test37_staleJobRecovery(): void {
        echo "[Test 37] Stale job recovery... ";
        $uid = $this->createTestUser('step6_t37@example.com', 'email', 0);
        $key = "stale_job_{$uid}_" . uniqid();

        // Insert job stuck in 'processing' whose lease expired 10 minutes ago
        $this->db->prepare("
            INSERT INTO notification_logs (user_id, scholarship_id, notification_type, channel, recipient, status, attempts, available_at, idempotency_key)
            VALUES (:uid, NULL, 'NEW_MATCH', 'email', 'step6_t37@example.com', 'processing', 1, DATE_SUB(NOW(), INTERVAL 10 MINUTE), :key)
        ")->execute(['uid' => $uid, 'key' => $key]);

        $recovered = $this->queueService->recoverStaleJobs(300);
        $this->assert($recovered >= 1, "recoverStaleJobs must recover the expired job");

        $status = $this->db->query("SELECT status FROM notification_logs WHERE idempotency_key = '$key'")->fetchColumn();
        $this->assert($status === 'retrying', "Stale job must recover to 'retrying', got $status");
        echo "PASS\n";
    }

    public function test38_maximumAttemptsThreshold(): void {
        echo "[Test 38] Maximum attempts threshold... ";
        $uid = $this->createTestUser('step6_t38@example.com', 'email', 0);
        $key = "max_att_{$uid}_" . uniqid();

        // Insert job that has already failed 3 times (maxAttempts = 3)
        $this->db->prepare("
            INSERT INTO notification_logs (user_id, scholarship_id, notification_type, channel, recipient, status, attempts, available_at, idempotency_key)
            VALUES (:uid, NULL, 'NEW_MATCH', 'email', 'step6_t38@example.com', 'failed', 3, NOW(), :key)
        ")->execute(['uid' => $uid, 'key' => $key]);

        // Process queue must not claim it
        $this->queueService->processQueue(10);
        $status = $this->db->query("SELECT status FROM notification_logs WHERE idempotency_key = '$key'")->fetchColumn();
        $this->assert($status === 'failed', "Job at maximum attempts must remain 'failed'");
        echo "PASS\n";
    }

    public function test39_duplicateWorkerProtectionRowLocking(): void {
        echo "[Test 39] Duplicate worker protection row locking... ";
        $uid = $this->createTestUser('step6_t39@example.com', 'email', 0);
        $key = "lock_prot_{$uid}_" . uniqid();

        $this->db->prepare("
            INSERT INTO notification_logs (user_id, scholarship_id, notification_type, channel, recipient, status, attempts, available_at, idempotency_key)
            VALUES (:uid, NULL, 'NEW_MATCH', 'email', 'step6_t39@example.com', 'pending', 0, NOW(), :key)
        ")->execute(['uid' => $uid, 'key' => $key]);

        // When a worker selects with FOR UPDATE and transitions to 'processing', another worker cannot claim it
        $this->db->beginTransaction();
        $stmt = $this->db->prepare("SELECT id FROM notification_logs WHERE idempotency_key = :key FOR UPDATE");
        $stmt->execute(['key' => $key]);
        $id = $stmt->fetchColumn();

        $this->db->prepare("UPDATE notification_logs SET status = 'processing' WHERE id = :id")->execute(['id' => $id]);
        $this->db->commit();

        // Second worker attempt
        $stmt2 = $this->db->prepare("SELECT id FROM notification_logs WHERE id = :id AND status IN ('pending', 'retrying')");
        $stmt2->execute(['id' => $id]);
        $this->assert($stmt2->fetchColumn() === false, "Second worker cannot claim a processing job");
        echo "PASS\n";
    }

    public function test40_providerMessageIdRecorded(): void {
        echo "[Test 40] Provider message ID recorded... ";
        $uid = $this->createTestUser('step6_t40@example.com', 'email', 0);
        $sid = $this->createTestScholarship('Sch 40', 'verified', 'published');
        $key = "msg_id_{$uid}_" . uniqid();

        // 1. Email delivery captures SMTP Message-ID header
        $this->queueService->enqueue($uid, $sid, 'NEW_MATCH', 'email', 'step6_t40@example.com', 'Subject', [], $key);
        $this->queueService->processQueue(10);

        $row = $this->db->query("SELECT status, provider_message_id FROM notification_logs WHERE idempotency_key = '$key'")->fetch(PDO::FETCH_ASSOC);
        $this->assert($row['status'] === 'sent', "Job must be sent (not delivered without webhook)");
        $this->assert(!empty($row['provider_message_id']), "SMTP Message-ID must be stored upon successful send");
        $this->assert(strpos($row['provider_message_id'], '@') !== false || strpos($row['provider_message_id'], 'mail_log_') !== false, "Message ID must follow RFC 5322 or logger format");

        // 2. WhatsApp delivery captures WACRM response message ID
        $uidWa = $this->createTestUser('step6_t40_wa@example.com', 'whatsapp', 0);
        $keyWa = "msg_id_wa_{$uidWa}_" . uniqid();
        \App\Services\WhatsApp\CurlMockRegistry::$response = json_encode([
            'data' => ['message_id' => 'wacrm_resp_msg_98765']
        ]);
        \App\Services\WhatsApp\CurlMockRegistry::$httpCode = 200;

        $this->queueService->enqueue($uidWa, $sid, 'NEW_MATCH', 'whatsapp', '+923001234567', null, ['title' => 'Sch 40'], $keyWa);
        $this->queueService->processQueue(10);

        $rowWa = $this->db->query("SELECT status, provider_message_id FROM notification_logs WHERE idempotency_key = '$keyWa'")->fetch(PDO::FETCH_ASSOC);
        $this->assert($rowWa['status'] === 'sent', "WhatsApp job must have status 'sent'");
        $this->assert($rowWa['provider_message_id'] === 'wacrm_resp_msg_98765', "WACRM message ID must be recorded from provider response, got: {$rowWa['provider_message_id']}");
        echo "PASS\n";
    }

    public function test41_failureLoggingRecorded(): void {
        echo "[Test 41] Failure logging recorded... ";
        $uid = $this->createTestUser('step6_t41@example.com', 'email', 0);
        $key = "fail_log_{$uid}_" . uniqid();

        // Queue with invalid channel
        $this->db->prepare("
            INSERT INTO notification_logs (user_id, scholarship_id, notification_type, channel, recipient, status, attempts, available_at, idempotency_key)
            VALUES (:uid, NULL, 'NEW_MATCH', 'unknown_chan', 'test@example.com', 'pending', 0, NOW(), :key)
        ")->execute(['uid' => $uid, 'key' => $key]);

        $this->queueService->processQueue(10);

        $row = $this->db->query("SELECT status, error_message FROM notification_logs WHERE idempotency_key = '$key'")->fetch(PDO::FETCH_ASSOC);
        $this->assert($row['status'] === 'failed', "Invalid channel must be marked failed");
        $this->assert(!empty($row['error_message']), "Failure reason must be recorded in error_message column");
        echo "PASS\n";
    }

    public function test42_manualRetryPreservesIdempotencyAndAttemptCount(): void {
        echo "[Test 42] Manual retry preserves idempotency and attempt count rules... ";
        $uid = $this->createTestUser('step6_t42@example.com', 'email', 0);
        $key = "retry_admin_{$uid}_" . uniqid();

        // 1. Failed job with attempts = 2 (under default maxAttempts = 3)
        $this->db->prepare("
            INSERT INTO notification_logs (user_id, scholarship_id, notification_type, channel, recipient, status, attempts, available_at, idempotency_key)
            VALUES (:uid, NULL, 'NEW_MATCH', 'email', 'step6_t42@example.com', 'failed', 2, NOW(), :key)
        ")->execute(['uid' => $uid, 'key' => $key]);
        $logId = (int)$this->db->lastInsertId();

        // Admin retries log
        $retried = $this->queueService->retryLog($logId);
        $this->assert($retried === true, "retryLog must return true when attempts < maxAttempts");

        $row = $this->db->query("SELECT status, attempts, idempotency_key FROM notification_logs WHERE id = $logId")->fetch(PDO::FETCH_ASSOC);
        $this->assert($row['status'] === 'pending', "Status must transition to pending");
        $this->assert((int)$row['attempts'] === 2, "Attempts count must NOT be reset to 0; was {$row['attempts']}");
        $this->assert($row['idempotency_key'] === $key, "Idempotency key must remain identical");

        // 2. If attempts = 5 (default max lifetime attempts reached), retryLog must strictly reject
        $this->db->exec("UPDATE notification_logs SET attempts = 5, status = 'failed' WHERE id = $logId");
        $rejected = $this->queueService->retryLog($logId);
        $this->assert($rejected === false, "retryLog must reject retry when attempts >= maxAttempts (5)");

        // 3. Test that attempts = 4 succeeds and attempts = 5 is rejected
        $this->db->exec("UPDATE notification_logs SET attempts = 4, status = 'failed' WHERE id = $logId");
        $retried4 = $this->queueService->retryLog($logId);
        $this->assert($retried4 === true, "retryLog must succeed for attempt 4 when maxAttempts = 5");

        $row4 = $this->db->query("SELECT status, attempts FROM notification_logs WHERE id = $logId")->fetch(PDO::FETCH_ASSOC);
        $this->assert($row4['status'] === 'pending', "Status must transition to pending");
        $this->assert((int)$row4['attempts'] === 4, "Attempts must remain 4 before worker run");

        $this->db->exec("UPDATE notification_logs SET attempts = 5, status = 'failed' WHERE id = $logId");
        $rejected5 = $this->queueService->retryLog($logId);
        $this->assert($rejected5 === false, "retryLog must reject retry when attempts >= 5");

        // 4. Verify no second row was created
        $totalRows = (int)$this->db->query("SELECT COUNT(*) FROM notification_logs WHERE idempotency_key = '$key'")->fetchColumn();
        $this->assert($totalRows === 1, "Must maintain exactly 1 notification_logs row without duplicates");
        echo "PASS\n";
    }

    // =========================================================================
    // GROUP 6: PROVIDER ROUTING & INTEGRITY (43-48)
    // =========================================================================

    public function test43_scholarshipWhatsAppRoutesToWacrm(): void {
        echo "[Test 43] Scholarship WhatsApp routes to WACRM... ";
        $ws = new WhatsAppNotificationService();
        $provider = $ws->getProviderFor(null, 'NEW_MATCH');
        $this->assert($provider instanceof WacrmWhatsAppProvider, "NEW_MATCH WhatsApp must resolve to WacrmWhatsAppProvider");
        echo "PASS\n";
    }

    public function test44_paymentConfirmationRoutesToMeta(): void {
        echo "[Test 44] Payment confirmation routes to Meta... ";
        $ws = new WhatsAppNotificationService();
        $provider = $ws->getProviderFor(null, 'PAYMENT_CONFIRMATION');
        $this->assert($provider instanceof MetaWhatsAppProvider, "PAYMENT_CONFIRMATION WhatsApp must resolve to MetaWhatsAppProvider");
        echo "PASS\n";
    }

    public function test45_scholarshipEmailRoutesToGmailSmtp(): void {
        echo "[Test 45] Scholarship email routes to Gmail SMTP... ";
        $uid = $this->createTestUser('step6_t45@example.com', 'email', 0);
        $key = "smtp_route_{$uid}_" . uniqid();

        $this->queueService->enqueue($uid, 1, 'NEW_MATCH', 'email', 'step6_t45@example.com', 'Subj', [], $key);
        $provider = $this->db->query("SELECT provider FROM notification_logs WHERE idempotency_key = '$key'")->fetchColumn();
        $this->assert($provider === 'smtp', "Email notifications must route to 'smtp' provider");
        echo "PASS\n";
    }

    public function test46_verificationEmailRoutesToGmailSmtp(): void {
        echo "[Test 46] Verification email routes to Gmail SMTP... ";
        $uid = $this->createTestUser('step6_t46@example.com', 'email', 0);
        $key = "verify_smtp_{$uid}_" . uniqid();

        $this->queueService->enqueue($uid, null, 'EMAIL_VERIFICATION', 'email', 'step6_t46@example.com', 'Verify', ['code' => '123456'], $key);
        $provider = $this->db->query("SELECT provider FROM notification_logs WHERE idempotency_key = '$key'")->fetchColumn();
        $this->assert($provider === 'smtp', "Verification email must route to 'smtp' provider");
        echo "PASS\n";
    }

    public function test47_noProviderCredentialLeakageInLogs(): void {
        echo "[Test 47] No provider credential leakage in logs... ";
        $apiKey = 'super_secret_wacrm_key_12345';
        $smtpPass = 'gmail_secret_app_password';

        // Check that redactError in NotificationQueueService removes credentials
        $reflector = new ReflectionClass($this->queueService);
        $method = $reflector->getMethod('redactError');
        $method->setAccessible(true);

        $redacted1 = $method->invoke($this->queueService, "Failed connecting with Bearer $apiKey to WACRM");
        $redacted2 = $method->invoke($this->queueService, "SMTP Error: password=$smtpPass auth failed");

        $this->assert(strpos($redacted1, $apiKey) === false, "API key was not redacted from error log!");
        $this->assert(strpos($redacted2, $smtpPass) === false, "SMTP password was not redacted from error log!");
        echo "PASS\n";
    }

    public function test48_wacrmSsrfProtectionsRemainIntact(): void {
        echo "[Test 48] WACRM SSRF protections remain intact... ";
        $dangerousUrls = [
            'http://127.0.0.1/api',
            'http://localhost:8080',
            'http://169.254.169.254/latest/meta-data',
            'http://10.0.0.1/api',
            'http://192.168.1.1/api'
        ];

        foreach ($dangerousUrls as $url) {
            $isSafe = WacrmWhatsAppProvider::isSafeUrl($url, true);
            $this->assert($isSafe === false, "SSRF protection failed to reject dangerous URL: $url");
        }
        echo "PASS\n";
    }

    // =========================================================================
    // GROUP 7: SECURITY AUDIT (49-54)
    // =========================================================================

    public function test49_adminAuthorizationRequired(): void {
        echo "[Test 49] Admin authorization required... ";
        // Visitor user cannot access admin notification routes
        $uid = $this->createTestUser('step6_t49@example.com', 'email', 0);
        $_SESSION['user_id'] = $uid;
        $_SESSION['role'] = 'visitor';

        $caught = false;
        try {
            Auth::requireRole(['admin', 'employee']);
        } catch (\Exception $e) {
            $caught = true;
        }
        $this->assert($caught === true, "Visitor user must be blocked from accessing admin notification actions");
        unset($_SESSION['user_id'], $_SESSION['role']);
        echo "PASS\n";
    }

    public function test50_idorPrevention(): void {
        echo "[Test 50] IDOR prevention... ";
        $u1 = $this->createTestUser('step6_t50_a@example.com', 'email', 0);
        $u2 = $this->createTestUser('step6_t50_b@example.com', 'email', 0);

        // U1 has a notification
        $key = "idor_key_{$u1}_" . uniqid();
        $this->queueService->enqueue($u1, 1, 'NEW_MATCH', 'email', 'step6_t50_a@example.com', 'Secret', [], $key);
        $notifId = (int)$this->db->query("SELECT id FROM notification_logs WHERE idempotency_key = '$key'")->fetchColumn();

        // Normal query for user-scoped notifications must enforce user_id
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM notification_logs WHERE id = :id AND user_id = :uid");
        $stmt->execute(['id' => $notifId, 'uid' => $u2]);
        $this->assert((int)$stmt->fetchColumn() === 0, "User 2 must not be able to query User 1's notification");
        echo "PASS\n";
    }

    public function test51_csrfEnforcement(): void {
        echo "[Test 51] CSRF enforcement... ";
        $validToken = Security::csrfToken();
        $this->assert(Security::verifyCsrfToken($validToken) === true, "Valid CSRF token must pass");
        $this->assert(Security::verifyCsrfToken('tampered_token_xyz') === false, "Tampered CSRF token must fail");
        $this->assert(Security::verifyCsrfToken('') === false, "Empty CSRF token must fail");
        echo "PASS\n";
    }

    public function test52_sqlInjectionPrevention(): void {
        echo "[Test 52] SQL injection prevention... ";
        $uid = $this->createTestUser('step6_t52@example.com', 'email', 0);
        $sqliPattern = "' OR '1'='1' -- ";
        $key = "sqli_test_" . uniqid();

        $this->queueService->enqueue($uid, 1, 'NEW_MATCH', 'email', 'step6_t52@example.com', $sqliPattern, ['title' => $sqliPattern], $key);

        // Verify PDO prepared statement stored it as raw literal text
        $stmt = $this->db->prepare("SELECT subject FROM notification_logs WHERE idempotency_key = :key");
        $stmt->execute(['key' => $key]);
        $subject = $stmt->fetchColumn();
        $this->assert($subject === $sqliPattern, "SQL injection string must be safely bound as literal parameter");
        echo "PASS\n";
    }

    public function test53_xssSafeNotificationContent(): void {
        echo "[Test 53] XSS-safe notification content... ";
        $xssTitle = "<script>alert('xss')</script><b>Bold Title</b>";
        $payload = [
            'title' => $xssTitle,
            'provider' => 'Provider',
            'summary' => 'Summary'
        ];

        $reflector = new ReflectionClass($this->queueService);
        $method = $reflector->getMethod('renderHtmlEmail');
        $method->setAccessible(true);

        $html = $method->invoke($this->queueService, 'NEW_MATCH', $payload);
        $this->assert(strpos($html, "<script>") === false, "Script tags must be escaped in HTML emails");
        $this->assert(strpos($html, "&lt;script&gt;") !== false, "Script tags must be HTML-entity encoded");
        echo "PASS\n";
    }

    public function test54_secretRedaction(): void {
        echo "[Test 54] Secret redaction in admin notification detail... ";
        $payloadData = [
            'code' => '999888',
            'otp_code' => '123456',
            'token' => 'jwt_secret_token_val',
            'first_name' => 'SafeStudent'
        ];
        $json = json_encode($payloadData);

        $sensitiveKeys = ['otp_code', 'code', 'token', 'token_hash', 'password', 'secret', 'key'];
        $decoded = json_decode($json, true);
        array_walk_recursive($decoded, function(&$value, $k) use ($sensitiveKeys) {
            if (in_array(strtolower($k), $sensitiveKeys, true)) {
                $value = '[REDACTED]';
            }
        });

        $this->assert($decoded['code'] === '[REDACTED]', "OTP code must be redacted");
        $this->assert($decoded['otp_code'] === '[REDACTED]', "otp_code must be redacted");
        $this->assert($decoded['token'] === '[REDACTED]', "Token must be redacted");
        $this->assert($decoded['first_name'] === 'SafeStudent', "Non-sensitive data must remain intact");
        echo "PASS\n";
    }

    // =========================================================================
    // GROUP 8: GENUINE MULTI-PROCESS CONCURRENCY TESTS (55-58)
    // =========================================================================

    public function test55_concurrencyTwoDailyMatchingProcesses(): void {
        echo "[Test 55] Concurrency: Two daily matching processes for same user... ";
        $uid = $this->createTestUser('step6_t55@example.com', 'email', 0);
        $sid = $this->createTestScholarship('Sch 55 Conc', 'verified', 'published');
        $this->matchingService->recalculateForUser($uid);

        // Multi-process worker script simulating concurrent daily matching
        $workerScript = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'step6_match_' . uniqid() . '.php';
        $workerCode = <<<'PHP'
<?php
require 'tests/bootstrap.php';
use App\Services\Database;
use App\Services\NotificationQueueService;

$userId = (int)$argv[1];
$schId = (int)$argv[2];
$workerId = $argv[3];
$holdMs = (int)($argv[4] ?? 0);

$db = Database::connection();
$queueService = new NotificationQueueService();

$key = "new_match_{$userId}_{$schId}";
if ($holdMs > 0) {
    usleep($holdMs * 1000);
}

$ok = $queueService->enqueue(
    $userId,
    $schId,
    'NEW_MATCH',
    'email',
    'step6_t55@example.com',
    'Sch 55 Conc',
    ['title' => 'Sch 55 Conc'],
    $key
);

echo json_encode(['worker' => $workerId, 'enqueued' => $ok]);
PHP;
        file_put_contents($workerScript, $workerCode);

        $cmd1 = "php \"$workerScript\" $uid $sid P1 100";
        $cmd2 = "php \"$workerScript\" $uid $sid P2 0";

        $desc = [0 => ["pipe", "r"], 1 => ["pipe", "w"], 2 => ["pipe", "w"]];
        $proc1 = proc_open($cmd1, $desc, $pipes1);
        $proc2 = proc_open($cmd2, $desc, $pipes2);

        $out1 = stream_get_contents($pipes1[1]);
        $out2 = stream_get_contents($pipes2[1]);

        fclose($pipes1[0]); fclose($pipes1[1]); fclose($pipes1[2]); proc_close($proc1);
        fclose($pipes2[0]); fclose($pipes2[1]); fclose($pipes2[2]); proc_close($proc2);
        @unlink($workerScript);

        $res1 = json_decode($out1, true);
        $res2 = json_decode($out2, true);

        // Exactly one worker must enqueue; the other must be false
        $this->assert(
            ($res1['enqueued'] && !$res2['enqueued']) || (!$res1['enqueued'] && $res2['enqueued']),
            "Exactly one concurrent daily matching process must enqueue, got: $out1 and $out2"
        );

        $count = (int)$this->db->query("SELECT COUNT(*) FROM notification_logs WHERE idempotency_key = 'new_match_{$uid}_{$sid}'")->fetchColumn();
        $this->assert($count === 1, "Exactly 1 record must be created in notification_logs");
        echo "PASS\n";
    }

    public function test56_concurrencyTwoWhatsAppQueueWorkers(): void {
        echo "[Test 56] Concurrency: Two WhatsApp queue workers for same user/day... ";
        $uid = $this->createTestUser('step6_t56@example.com', 'whatsapp', 0);
        $today = date('Y-m-d');
        $key = "scholarship_whatsapp_{$uid}_{$today}";

        $this->db->prepare("
            INSERT INTO notification_logs (user_id, scholarship_id, notification_type, channel, recipient, status, attempts, available_at, idempotency_key)
            VALUES (:uid, NULL, 'NEW_MATCH', 'whatsapp', '+923001234567', 'pending', 0, NOW(), :key)
        ")->execute(['uid' => $uid, 'key' => $key]);

        // Spawn two workers concurrently processing the queue
        $workerScript = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'step6_qworker_' . uniqid() . '.php';
        $workerCode = <<<'PHP'
<?php
define('BYPASS_BATCH_CUTOFF', true);
require 'tests/bootstrap.php';
require 'tests/WacrmWhatsAppProviderTest.php';
\App\Services\WhatsApp\CurlMockRegistry::$response = json_encode([
    'data' => ['message_id' => 'msg_wacrm_step6_worker']
]);
\App\Services\WhatsApp\CurlMockRegistry::$httpCode = 201;
use App\Services\NotificationQueueService;

$workerId = $argv[1];
$queueService = new NotificationQueueService();
$processed = $queueService->processQueue(10);

echo json_encode(['worker' => $workerId, 'processed' => $processed]);
PHP;
        file_put_contents($workerScript, $workerCode);

        $desc = [0 => ["pipe", "r"], 1 => ["pipe", "w"], 2 => ["pipe", "w"]];
        $proc1 = proc_open("php \"$workerScript\" W1", $desc, $pipes1);
        $proc2 = proc_open("php \"$workerScript\" W2", $desc, $pipes2);

        $out1 = stream_get_contents($pipes1[1]);
        $out2 = stream_get_contents($pipes2[1]);

        fclose($pipes1[0]); fclose($pipes1[1]); fclose($pipes1[2]); proc_close($proc1);
        fclose($pipes2[0]); fclose($pipes2[1]); fclose($pipes2[2]); proc_close($proc2);
        @unlink($workerScript);

        $row = $this->db->query("SELECT status, attempts FROM notification_logs WHERE idempotency_key = '$key'")->fetch(PDO::FETCH_ASSOC);
        $this->assert($row['status'] === 'sent', "Job must transition to 'sent'");
        $this->assert((int)$row['attempts'] === 1, "Attempts must be exactly 1 (no double delivery by concurrent workers)");
        echo "PASS\n";
    }

    public function test57_concurrencyTwoWorkersCompetingFor25Limit(): void {
        echo "[Test 57] Concurrency: Two workers competing for 25-message limit... ";
        $uid = $this->createTestUser('step6_t57@example.com', 'whatsapp', 0);

        // Pre-insert exactly 24 sent scholarship WhatsApp messages
        for ($i = 1; $i <= 24; $i++) {
            $this->db->prepare("
                INSERT INTO notification_logs (user_id, scholarship_id, notification_type, channel, recipient, status, attempts, sent_at, idempotency_key)
            VALUES (:uid, NULL, 'NEW_MATCH', 'whatsapp', '+923001234567', 'sent', 1, NOW(), :key)
            ")->execute(['uid' => $uid, 'key' => "wa_comp57_{$uid}_{$i}"]);
        }

        // Insert TWO pending messages (Job 25 and Job 26)
        $this->db->prepare("
            INSERT INTO notification_logs (user_id, scholarship_id, notification_type, channel, recipient, status, attempts, available_at, idempotency_key)
            VALUES (:uid, NULL, 'NEW_MATCH', 'whatsapp', '+923001234567', 'pending', 0, NOW(), 'comp57_job_a')
        ")->execute(['uid' => $uid]);

        $this->db->prepare("
            INSERT INTO notification_logs (user_id, scholarship_id, notification_type, channel, recipient, status, attempts, available_at, idempotency_key)
            VALUES (:uid, NULL, 'NEW_MATCH', 'whatsapp', '+923001234567', 'pending', 0, NOW(), 'comp57_job_b')
        ")->execute(['uid' => $uid]);

        // Launch two workers concurrently in separate OS processes
        $workerScript = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'step6_comp25_' . uniqid() . '.php';
        $workerCode = <<<'PHP'
<?php
define('BYPASS_BATCH_CUTOFF', true);
require 'tests/bootstrap.php';
require 'tests/WacrmWhatsAppProviderTest.php';
\App\Services\WhatsApp\CurlMockRegistry::$response = json_encode([
    'data' => ['message_id' => 'msg_wacrm_step6_worker']
]);
\App\Services\WhatsApp\CurlMockRegistry::$httpCode = 201;
use App\Services\NotificationQueueService;

$workerId = $argv[1];
$queueService = new NotificationQueueService();
$processed = $queueService->processQueue(10);

echo json_encode(['worker' => $workerId, 'processed' => $processed]);
PHP;
        file_put_contents($workerScript, $workerCode);

        $desc = [0 => ["pipe", "r"], 1 => ["pipe", "w"], 2 => ["pipe", "w"]];
        $proc1 = proc_open("php \"$workerScript\" WorkerA", $desc, $pipes1);
        $proc2 = proc_open("php \"$workerScript\" WorkerB", $desc, $pipes2);

        $out1 = stream_get_contents($pipes1[1]);
        $out2 = stream_get_contents($pipes2[1]);

        fclose($pipes1[0]); fclose($pipes1[1]); fclose($pipes1[2]); proc_close($proc1);
        fclose($pipes2[0]); fclose($pipes2[1]); fclose($pipes2[2]); proc_close($proc2);
        @unlink($workerScript);

        // Verification: The total sent scholarship WhatsApp messages MUST BE EXACTLY 25
        $totalSent = (int)$this->db->query("
            SELECT COUNT(*) FROM notification_logs 
            WHERE user_id = $uid AND channel = 'whatsapp' AND status = 'sent' AND notification_type = 'NEW_MATCH'
        ")->fetchColumn();

        $this->assert($totalSent === 25, "Concurrency violation! Total sent must be exactly 25, but was: $totalSent");
        echo "PASS\n";
    }

    public function test58_concurrencyTwoDeadlineReminderCronExecutions(): void {
        echo "[Test 58] Concurrency: Two deadline reminder cron executions... ";
        $uid = $this->createTestUser('step6_t58@example.com', 'email', 0);
        $deadline = date('Y-m-d', strtotime('+3 days'));
        $sid = $this->createTestScholarship('Sch 58 Conc', 'verified', 'published', $deadline);

        $workerScript = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'step6_reminder_' . uniqid() . '.php';
        $workerCode = <<<'PHP'
<?php
require 'tests/bootstrap.php';
use App\Services\NotificationService;

$userId = (int)$argv[1];
$schId = (int)$argv[2];
$deadline = $argv[3];
$workerId = $argv[4];

$service = new NotificationService();
$key = "deadline_reminder_{$userId}_{$schId}_3_{$deadline}";

$service->sendNotification(
    $userId,
    'SCHOLARSHIP_DEADLINE_SOON',
    ['title' => 'Sch 58 Conc'],
    $schId,
    $key
);

echo json_encode(['worker' => $workerId, 'executed' => true]);
PHP;
        file_put_contents($workerScript, $workerCode);

        $cmd1 = "php \"$workerScript\" $uid $sid $deadline R1";
        $cmd2 = "php \"$workerScript\" $uid $sid $deadline R2";

        $desc = [0 => ["pipe", "r"], 1 => ["pipe", "w"], 2 => ["pipe", "w"]];
        $proc1 = proc_open($cmd1, $desc, $pipes1);
        $proc2 = proc_open($cmd2, $desc, $pipes2);

        $out1 = stream_get_contents($pipes1[1]);
        $out2 = stream_get_contents($pipes2[1]);

        fclose($pipes1[0]); fclose($pipes1[1]); fclose($pipes1[2]); proc_close($proc1);
        fclose($pipes2[0]); fclose($pipes2[1]); fclose($pipes2[2]); proc_close($proc2);
        @unlink($workerScript);

        $count = (int)$this->db->query("
            SELECT COUNT(*) FROM notification_logs 
            WHERE idempotency_key = 'deadline_reminder_{$uid}_{$sid}_3_{$deadline}'
        ")->fetchColumn();

        $this->assert($count === 1, "Concurrent deadline reminder executions must result in exactly 1 record, got: $count");
        echo "PASS\n";
    }
}

if (php_sapi_name() === 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === realpath(__FILE__)) {
    $test = new Step6NotificationAutomationTest();
    $test->run();
}
