<?php

if (!defined('TESTING_MODE')) {
    define('TESTING_MODE', true);
}

use App\Services\Database;
use App\Services\Auth;
use App\Services\NotificationService;
use App\Services\NotificationQueueService;
use App\Services\NotificationTypes;
use App\Services\SubscriptionService;

class NotificationFoundationTest {
    private PDO $db;
    private int $userId;
    private int $schId;
    private int $premiumPlanId;
    private NotificationService $notifService;

    public function __construct() {
        $this->db = Database::connection();
        $this->notifService = new NotificationService();
    }

    public function run(): void {
        echo "--- Running NotificationFoundationTest ---\n";

        $this->cleanTestData();
        $this->setupTestData();

        try {
            $this->test1_ActivePaidUserMatchingScholarship();
            $this->test2_FreeUserMatchingScholarship();
            $this->test3_ExpiredSubscription();
            $this->test4_PaymentSubscriptionInactive();
            $this->test5_NoWhatsAppOptIn();
            $this->test6_MissingInvalidPhoneNumber();
            $this->test7_UnpublishedScholarship();
            $this->test8_ExpiredScholarship();
            $this->test9_NonMatchingScholarship();
            $this->test10_ValidMatchExactlyOneNotification();
            $this->test11_AttemptToCreateSameNewMatchTwice();
            $this->test12_TwoConcurrentCreationAttempts();
            $this->test13_ScholarshipCreatedBeforeUserRegistration();
            $this->test14_DifferentScholarshipForSameUser();
            $this->test15_SameScholarshipButDifferentUser();
            $this->test16_SameUserSameScholarshipNewMatchWhatsAppDuplicate();
            $this->test17_SameUserSameScholarshipDeadlineReminderWhatsAppSeparate();

            echo "NotificationFoundationTest PASSED.\n\n";
        } finally {
            $this->cleanTestData();
        }
    }

    private function cleanTestData(): void {
        $this->db->exec("DELETE FROM notification_logs WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'notif-found-%')");
        $this->db->exec("DELETE FROM notification_preferences WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'notif-found-%')");
        $this->db->exec("DELETE FROM subscriptions WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'notif-found-%')");
        $this->db->exec("DELETE FROM education_records WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'notif-found-%')");
        $this->db->exec("DELETE FROM student_profiles WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'notif-found-%')");
        $this->db->exec("DELETE FROM user_preferences WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'notif-found-%')");
        $this->db->exec("DELETE FROM users WHERE email LIKE 'notif-found-%'");
        $this->db->exec("DELETE FROM scholarships WHERE slug LIKE 'notif-found-%'");
    }

    private function setupTestData(): void {
        // 1. Resolve role and premium plan IDs
        $roleId = $this->db->query("SELECT id FROM roles WHERE name = 'visitor' LIMIT 1")->fetchColumn();
        $this->premiumPlanId = $this->db->query("SELECT id FROM subscription_plans WHERE slug = 'premium-monthly' LIMIT 1")->fetchColumn();

        // 2. Create matching test user
        $stmtUser = $this->db->prepare("
            INSERT INTO users (first_name, last_name, email, phone, whatsapp_phone, password_hash, role_id, status, email_opt_in, whatsapp_opt_in, created_at)
            VALUES ('Notif', 'Tester', 'notif-found-user@example.com', '+923009999999', '+923009999999', 'hash', :role_id, 'active', 1, 1, NOW())
        ");
        $stmtUser->execute(['role_id' => $roleId]);
        $this->userId = $this->db->lastInsertId();

        // Add education profile records to match criteria
        $stmtProfile = $this->db->prepare("
            INSERT INTO student_profiles (user_id, gender, date_of_birth, nationality_country_id, residence_country_id)
            VALUES (:uid, 'male', '2000-01-01', 1, 1)
        ");
        $stmtProfile->execute(['uid' => $this->userId]);

        $stmtEdu = $this->db->prepare("
            INSERT INTO education_records (user_id, institution_name, degree_level, degree_title, field_of_study, cgpa, cgpa_scale, percentage, is_current)
            VALUES (:uid, 'Test University', 'Bachelor', 'Bachelor of Science', 'Computer Science', 3.80, 4.00, 95.00, 1)
        ");
        $stmtEdu->execute(['uid' => $this->userId]);

        // Add user preferences
        $stmtPref = $this->db->prepare("
            INSERT INTO user_preferences (user_id, funding_preferences)
            VALUES (:uid, 'Fully Funded')
        ");
        $stmtPref->execute(['uid' => $this->userId]);

        // Set default preferences in notification_preferences
        $this->db->exec("INSERT INTO notification_preferences (user_id, notification_type, whatsapp_enabled) VALUES ({$this->userId}, 'whatsapp_alerts', 1)");
        $this->db->exec("INSERT INTO notification_preferences (user_id, notification_type, whatsapp_enabled) VALUES ({$this->userId}, 'matching_scholarship_alerts', 1)");
        $this->db->exec("INSERT INTO notification_preferences (user_id, notification_type, whatsapp_enabled) VALUES ({$this->userId}, 'deadline_reminders', 1)");

        // 3. Create test scholarship
        $stmtSch = $this->db->prepare("
            INSERT INTO scholarships (title, slug, status, verification_status, provider_name, country_id, funding_type, application_deadline, description, created_at)
            VALUES ('Notif Match Scholarship', 'notif-found-match-scholarship', 'published', 'verified', 'Wacrm Inc', 1, 'Fully Funded', DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'Description', NOW())
        ");
        $stmtSch->execute();
        $this->schId = $this->db->lastInsertId();

        // Pivot mappings
        $this->db->exec("INSERT INTO scholarship_degree_levels (scholarship_id, degree_level) VALUES ({$this->schId}, 'Bachelor')");
        $this->db->exec("INSERT INTO scholarship_eligible_nationalities (scholarship_id, country_id) VALUES ({$this->schId}, 1)");
        $this->db->exec("INSERT INTO scholarship_eligibility_rules (scholarship_id, minimum_cgpa, cgpa_scale) VALUES ({$this->schId}, 3.00, 4.00)");
    }

    private function setPremiumSubscription(bool $active = true, ?string $endsAt = null, ?string $startsAt = null): void {
        $this->db->exec("DELETE FROM subscriptions WHERE user_id = {$this->userId}");
        if ($active) {
            $starts = $startsAt ?: date('Y-m-d H:i:s');
            $ends = $endsAt ?: date('Y-m-d H:i:s', strtotime('+30 days'));
            $this->db->exec("
                INSERT INTO subscriptions (user_id, plan_id, status, starts_at, ends_at)
                VALUES ({$this->userId}, {$this->premiumPlanId}, 'active', '{$starts}', '{$ends}')
            ");
        }
    }

    private function test1_ActivePaidUserMatchingScholarship(): void {
        $this->setPremiumSubscription(true);
        $this->db->exec("DELETE FROM notification_logs WHERE user_id = {$this->userId}");

        $success = $this->notifService->createNewMatchNotification($this->userId, $this->schId);
        if (!$success) {
            throw new Exception("TEST 1 Failed: Premium subscriber matching notification was not created.");
        }

        $cnt = $this->db->query("SELECT COUNT(*) FROM notification_logs WHERE user_id = {$this->userId} AND scholarship_id = {$this->schId}")->fetchColumn();
        if ($cnt != 1) {
            throw new Exception("TEST 1 Failed: Expected 1 notification log row, found $cnt.");
        }
        echo "✔ TEST 1: Active paid user matching notification enqueued successfully.\n";
    }

    private function test2_FreeUserMatchingScholarship(): void {
        $this->setPremiumSubscription(false);
        $this->db->exec("DELETE FROM notification_logs WHERE user_id = {$this->userId}");

        $success = $this->notifService->createNewMatchNotification($this->userId, $this->schId);
        if ($success) {
            throw new Exception("TEST 2 Failed: Free user matching notification was incorrectly created.");
        }

        $cnt = $this->db->query("SELECT COUNT(*) FROM notification_logs WHERE user_id = {$this->userId}")->fetchColumn();
        if ($cnt != 0) {
            throw new Exception("TEST 2 Failed: Found enqueued notification logs for free user.");
        }
        echo "✔ TEST 2: Free user blocked from matching notification successfully.\n";
    }

    private function test3_ExpiredSubscription(): void {
        // Ends in the past
        $this->setPremiumSubscription(true, date('Y-m-d H:i:s', strtotime('-1 day')));
        $this->db->exec("DELETE FROM notification_logs WHERE user_id = {$this->userId}");

        $success = $this->notifService->createNewMatchNotification($this->userId, $this->schId);
        if ($success) {
            throw new Exception("TEST 3 Failed: Expired subscription user matching notification was incorrectly created.");
        }
        echo "✔ TEST 3: Expired subscription blocked successfully.\n";
    }

    private function test4_PaymentSubscriptionInactive(): void {
        // Status failed
        $this->db->exec("DELETE FROM subscriptions WHERE user_id = {$this->userId}");
        $this->db->exec("
            INSERT INTO subscriptions (user_id, plan_id, status, starts_at, ends_at)
            VALUES ({$this->userId}, {$this->premiumPlanId}, 'failed', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY))
        ");
        $this->db->exec("DELETE FROM notification_logs WHERE user_id = {$this->userId}");

        $success = $this->notifService->createNewMatchNotification($this->userId, $this->schId);
        if ($success) {
            throw new Exception("TEST 4 Failed: Inactive failed subscription user was allowed matching notifications.");
        }
        echo "✔ TEST 4: Failed subscription status blocked successfully.\n";
    }

    private function test5_NoWhatsAppOptIn(): void {
        $this->setPremiumSubscription(true);
        $this->db->exec("DELETE FROM notification_logs WHERE user_id = {$this->userId}");

        // Opt out globally
        $this->db->exec("UPDATE users SET whatsapp_opt_in = 0 WHERE id = {$this->userId}");

        $success = $this->notifService->createNewMatchNotification($this->userId, $this->schId);
        if ($success) {
            throw new Exception("TEST 5 Failed: Allowed notification creation with disabled global opt-in.");
        }

        // Restore global opt-in, disable specific pref
        $this->db->exec("UPDATE users SET whatsapp_opt_in = 1 WHERE id = {$this->userId}");
        $this->db->exec("UPDATE notification_preferences SET whatsapp_enabled = 0 WHERE user_id = {$this->userId} AND notification_type = 'matching_scholarship_alerts'");

        $success2 = $this->notifService->createNewMatchNotification($this->userId, $this->schId);
        if ($success2) {
            throw new Exception("TEST 5 Failed: Allowed notification creation with specific type disabled.");
        }

        // Restore specific pref
        $this->db->exec("UPDATE notification_preferences SET whatsapp_enabled = 1 WHERE user_id = {$this->userId} AND notification_type = 'matching_scholarship_alerts'");
        echo "✔ TEST 5: WhatsApp opt-ins and preference switches gating verified.\n";
    }

    private function test6_MissingInvalidPhoneNumber(): void {
        $this->setPremiumSubscription(true);
        $this->db->exec("DELETE FROM notification_logs WHERE user_id = {$this->userId}");

        // Save old phones
        $stmtOld = $this->db->prepare("SELECT phone, whatsapp_phone FROM users WHERE id = :id");
        $stmtOld->execute(['id' => $this->userId]);
        $old = $stmtOld->fetch(PDO::FETCH_ASSOC);

        // Test empty phone
        $this->db->exec("UPDATE users SET phone = '', whatsapp_phone = '' WHERE id = {$this->userId}");
        $success = $this->notifService->createNewMatchNotification($this->userId, $this->schId);
        if ($success) {
            throw new Exception("TEST 6 Failed: Allowed creation when phone is empty.");
        }

        // Test invalid phone
        $this->db->exec("UPDATE users SET phone = 'invalid_number' WHERE id = {$this->userId}");
        $success2 = $this->notifService->createNewMatchNotification($this->userId, $this->schId);
        if ($success2) {
            throw new Exception("TEST 6 Failed: Allowed creation with invalid phone number format.");
        }

        // Restore phones
        $stmtRest = $this->db->prepare("UPDATE users SET phone = :phone, whatsapp_phone = :wphone WHERE id = :id");
        $stmtRest->execute(['phone' => $old['phone'], 'wphone' => $old['whatsapp_phone'], 'id' => $this->userId]);
        echo "✔ TEST 6: Phone formatting and empty validation checks verified.\n";
    }

    private function test7_UnpublishedScholarship(): void {
        $this->setPremiumSubscription(true);
        $this->db->exec("DELETE FROM notification_logs WHERE user_id = {$this->userId}");

        $this->db->exec("UPDATE scholarships SET status = 'draft' WHERE id = {$this->schId}");
        $success = $this->notifService->createNewMatchNotification($this->userId, $this->schId);
        if ($success) {
            throw new Exception("TEST 7 Failed: Allowed creation for draft/unpublished scholarship.");
        }

        $this->db->exec("UPDATE scholarships SET status = 'published' WHERE id = {$this->schId}");
        echo "✔ TEST 7: Draft/unpublished scholarship gating verified.\n";
    }

    private function test8_ExpiredScholarship(): void {
        $this->setPremiumSubscription(true);
        $this->db->exec("DELETE FROM notification_logs WHERE user_id = {$this->userId}");

        $this->db->exec("UPDATE scholarships SET application_deadline = DATE_SUB(CURDATE(), INTERVAL 1 DAY) WHERE id = {$this->schId}");
        $success = $this->notifService->createNewMatchNotification($this->userId, $this->schId);
        if ($success) {
            throw new Exception("TEST 8 Failed: Allowed creation for expired scholarship.");
        }

        $this->db->exec("UPDATE scholarships SET application_deadline = DATE_ADD(CURDATE(), INTERVAL 30 DAY) WHERE id = {$this->schId}");
        echo "✔ TEST 8: Expired scholarship gating verified.\n";
    }

    private function test9_NonMatchingScholarship(): void {
        $this->setPremiumSubscription(true);
        $this->db->exec("DELETE FROM notification_logs WHERE user_id = {$this->userId}");

        // Change cgpa limit rules to exceed student profile score (3.8)
        $this->db->exec("UPDATE scholarship_eligibility_rules SET minimum_cgpa = 3.95 WHERE scholarship_id = {$this->schId}");

        $success = $this->notifService->createNewMatchNotification($this->userId, $this->schId);
        if ($success) {
            throw new Exception("TEST 9 Failed: Allowed creation when student profile doesn't match scholarship rules.");
        }

        $this->db->exec("UPDATE scholarship_eligibility_rules SET minimum_cgpa = 3.00 WHERE scholarship_id = {$this->schId}");
        echo "✔ TEST 9: Match filters gating verified.\n";
    }

    private function test10_ValidMatchExactlyOneNotification(): void {
        $this->setPremiumSubscription(true);
        $this->db->exec("DELETE FROM notification_logs WHERE user_id = {$this->userId}");

        $this->notifService->createNewMatchNotification($this->userId, $this->schId);
        $cnt = $this->db->query("SELECT COUNT(*) FROM notification_logs WHERE user_id = {$this->userId} AND scholarship_id = {$this->schId}")->fetchColumn();
        if ($cnt != 1) {
            throw new Exception("TEST 10 Failed: Expected exactly 1 notification, found $cnt.");
        }
        echo "✔ TEST 10: Exactly ONE notification log row exists on valid match.\n";
    }

    private function test11_AttemptToCreateSameNewMatchTwice(): void {
        $this->setPremiumSubscription(true);
        $this->db->exec("DELETE FROM notification_logs WHERE user_id = {$this->userId}");

        $res1 = $this->notifService->createNewMatchNotification($this->userId, $this->schId);
        $res2 = $this->notifService->createNewMatchNotification($this->userId, $this->schId);

        if (!$res1 || $res2) {
            throw new Exception("TEST 11 Failed: Double call allowed second notification insert.");
        }

        $cnt = $this->db->query("SELECT COUNT(*) FROM notification_logs WHERE user_id = {$this->userId} AND scholarship_id = {$this->schId}")->fetchColumn();
        if ($cnt != 1) {
            throw new Exception("TEST 11 Failed: Found duplicate rows in database logs.");
        }
        echo "✔ TEST 11: Deduplication checks blocked double insert attempt.\n";
    }

    private function test12_TwoConcurrentCreationAttempts(): void {
        $this->setPremiumSubscription(true);
        $this->db->exec("DELETE FROM notification_logs WHERE user_id = {$this->userId}");

        // Simulating the race condition by trying to run raw SQL inserts with the same idempotency key
        $key = "{$this->userId}_{$this->schId}_NEW_MATCH_whatsapp";
        
        $inserted1 = false;
        $inserted2 = false;

        try {
            $stmt = $this->db->prepare("
                INSERT INTO notification_logs (user_id, scholarship_id, notification_type, channel, recipient, idempotency_key, status)
                VALUES (?, ?, 'NEW_MATCH', 'whatsapp', '+923009999999', ?, 'pending')
            ");
            $inserted1 = $stmt->execute([$this->userId, $this->schId, $key]);
        } catch (PDOException $e) {
            $inserted1 = false;
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO notification_logs (user_id, scholarship_id, notification_type, channel, recipient, idempotency_key, status)
                VALUES (?, ?, 'NEW_MATCH', 'whatsapp', '+923009999999', ?, 'pending')
            ");
            $inserted2 = $stmt->execute([$this->userId, $this->schId, $key]);
        } catch (PDOException $e) {
            $inserted2 = false;
        }

        if ($inserted1 && $inserted2) {
            throw new Exception("TEST 12 Failed: Database constraint allowed concurrent duplicate inserts under race condition.");
        }

        $cnt = $this->db->query("SELECT COUNT(*) FROM notification_logs WHERE idempotency_key = '{$key}'")->fetchColumn();
        if ($cnt != 1) {
            throw new Exception("TEST 12 Failed: Expected 1 row in DB, found $cnt.");
        }
        echo "✔ TEST 12: Database unique index protects against concurrent insertion race conditions.\n";
    }

    private function test13_ScholarshipCreatedBeforeUserRegistration(): void {
        $this->setPremiumSubscription(true);
        $this->db->exec("DELETE FROM notification_logs WHERE user_id = {$this->userId}");

        // Backdate scholarship creation date to be before user creation (which is NOW)
        $this->db->exec("UPDATE scholarships SET created_at = DATE_SUB(NOW(), INTERVAL 5 DAY) WHERE id = {$this->schId}");

        $success = $this->notifService->createNewMatchNotification($this->userId, $this->schId);
        if (!$success) {
            throw new Exception("TEST 13 Failed: Matching scholarship was rejected because it was created before the user registered.");
        }

        echo "✔ TEST 13: Scholarship created before user registration evaluates successfully.\n";
    }

    private function test14_DifferentScholarshipForSameUser(): void {
        $this->setPremiumSubscription(true);
        $this->db->exec("DELETE FROM notification_logs WHERE user_id = {$this->userId}");

        // Create a second matching scholarship
        $stmtSch2 = $this->db->prepare("
            INSERT INTO scholarships (title, slug, status, verification_status, provider_name, country_id, funding_type, application_deadline, description, created_at)
            VALUES ('Second match', 'notif-found-match-2', 'published', 'verified', 'Wacrm Inc', 1, 'Fully Funded', DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'Description', NOW())
        ");
        $stmtSch2->execute();
        $sch2Id = $this->db->lastInsertId();

        $this->db->exec("INSERT INTO scholarship_degree_levels (scholarship_id, degree_level) VALUES ({$sch2Id}, 'Bachelor')");
        $this->db->exec("INSERT INTO scholarship_eligible_nationalities (scholarship_id, country_id) VALUES ({$sch2Id}, 1)");
        $this->db->exec("INSERT INTO scholarship_eligibility_rules (scholarship_id, minimum_cgpa, cgpa_scale) VALUES ({$sch2Id}, 3.00, 4.00)");

        $res1 = $this->notifService->createNewMatchNotification($this->userId, $this->schId);
        $res2 = $this->notifService->createNewMatchNotification($this->userId, $sch2Id);

        if (!$res1 || !$res2) {
            throw new Exception("TEST 14 Failed: Separate notification was blocked for different scholarship on same user.");
        }

        $cnt = $this->db->query("SELECT COUNT(*) FROM notification_logs WHERE user_id = {$this->userId}")->fetchColumn();
        if ($cnt != 2) {
            throw new Exception("TEST 14 Failed: Expected 2 rows in DB, found $cnt.");
        }
        echo "✔ TEST 14: Multiple separate matching notifications allowed for same user.\n";
    }

    private function test15_SameScholarshipButDifferentUser(): void {
        $this->setPremiumSubscription(true);

        $roleId = $this->db->query("SELECT id FROM roles WHERE name = 'visitor' LIMIT 1")->fetchColumn();
        // Create second user
        $stmtUser2 = $this->db->prepare("
            INSERT INTO users (first_name, last_name, email, phone, whatsapp_phone, password_hash, role_id, status, email_opt_in, whatsapp_opt_in, created_at)
            VALUES ('Second', 'User', 'notif-found-user2@example.com', '+923001234568', '+923001234568', 'hash', :role_id, 'active', 1, 1, NOW())
        ");
        $stmtUser2->execute(['role_id' => $roleId]);
        $user2Id = $this->db->lastInsertId();

        // Build premium status & profile mappings
        $this->db->exec("INSERT INTO subscriptions (user_id, plan_id, status, starts_at, ends_at) VALUES ({$user2Id}, {$this->premiumPlanId}, 'active', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY))");
        $this->db->exec("INSERT INTO student_profiles (user_id, gender, date_of_birth, nationality_country_id, residence_country_id) VALUES ({$user2Id}, 'male', '2000-01-01', 1, 1)");
        $this->db->exec("INSERT INTO education_records (user_id, institution_name, degree_level, degree_title, field_of_study, cgpa, cgpa_scale, percentage, is_current) VALUES ({$user2Id}, 'Test University', 'Bachelor', 'Bachelor of Science', 'Computer Science', 3.80, 4.00, 95.00, 1)");
        $this->db->exec("INSERT INTO user_preferences (user_id, funding_preferences) VALUES ({$user2Id}, 'Fully Funded')");
        $this->db->exec("INSERT INTO notification_preferences (user_id, notification_type, whatsapp_enabled) VALUES ({$user2Id}, 'whatsapp_alerts', 1)");
        $this->db->exec("INSERT INTO notification_preferences (user_id, notification_type, whatsapp_enabled) VALUES ({$user2Id}, 'matching_scholarship_alerts', 1)");

        $this->db->exec("DELETE FROM notification_logs WHERE user_id IN ({$this->userId}, {$user2Id})");

        $res1 = $this->notifService->createNewMatchNotification($this->userId, $this->schId);
        $res2 = $this->notifService->createNewMatchNotification($user2Id, $this->schId);

        if (!$res1 || !$res2) {
            throw new Exception("TEST 15 Failed: Failed to generate notification for separate user on same scholarship.");
        }

        $cnt = $this->db->query("SELECT COUNT(*) FROM notification_logs WHERE scholarship_id = {$this->schId}")->fetchColumn();
        if ($cnt != 2) {
            throw new Exception("TEST 15 Failed: Expected 2 rows in DB, found $cnt.");
        }
        echo "✔ TEST 15: Same scholarship enqueues notifications separately for different matching users.\n";
    }

    private function test16_SameUserSameScholarshipNewMatchWhatsAppDuplicate(): void {
        $this->setPremiumSubscription(true);
        $this->db->exec("DELETE FROM notification_logs WHERE user_id = {$this->userId}");

        $this->notifService->createNewMatchNotification($this->userId, $this->schId);
        
        // Attempt duplicate enqueue through manual enqueue to test raw constraints
        $queue = new NotificationQueueService();
        $enqueued = $queue->enqueue(
            $this->userId,
            $this->schId,
            NotificationTypes::NEW_MATCH,
            'whatsapp',
            '+923009999999',
            null,
            []
        );

        if ($enqueued) {
            throw new Exception("TEST 16 Failed: Queue manager allowed duplicate NEW_MATCH enqueue.");
        }

        $cnt = $this->db->query("SELECT COUNT(*) FROM notification_logs WHERE user_id = {$this->userId} AND scholarship_id = {$this->schId}")->fetchColumn();
        if ($cnt != 1) {
            throw new Exception("TEST 16 Failed: Found duplicate row count of $cnt.");
        }
        echo "✔ TEST 16: Duplicate NEW_MATCH WhatsApp notifications are successfully blocked by uniqueness key.\n";
    }

    private function test17_SameUserSameScholarshipDeadlineReminderWhatsAppSeparate(): void {
        $this->setPremiumSubscription(true);
        $this->db->exec("DELETE FROM notification_logs WHERE user_id = {$this->userId}");

        // Create NEW_MATCH
        $this->notifService->createNewMatchNotification($this->userId, $this->schId);

        // Enqueue DEADLINE_REMINDER for same user and scholarship
        $queue = new NotificationQueueService();
        $res = $queue->enqueue(
            $this->userId,
            $this->schId,
            NotificationTypes::DEADLINE_REMINDER,
            'whatsapp',
            '+923009999999',
            null,
            []
        );

        if (!$res) {
            throw new Exception("TEST 17 Failed: Blocked DEADLINE_REMINDER notification from being enqueued alongside NEW_MATCH.");
        }

        $cnt = $this->db->query("SELECT COUNT(*) FROM notification_logs WHERE user_id = {$this->userId} AND scholarship_id = {$this->schId}")->fetchColumn();
        if ($cnt != 2) {
            throw new Exception("TEST 17 Failed: Expected 2 separate notification rows, found $cnt.");
        }
        echo "✔ TEST 17: Co-existence of NEW_MATCH and DEADLINE_REMINDER notifications on the same scholarship verified.\n";
    }
}
