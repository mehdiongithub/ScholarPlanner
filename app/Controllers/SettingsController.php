<?php

namespace App\Controllers;

use App\Services\Auth;
use App\Services\Database;
use App\Services\SubscriptionService;
use App\Helpers\Security;
use PDO;

class SettingsController {
    /**
     * Display student settings hub with child tabs (Profile, Reminders, Plan)
     */
    public function index(): void {
        Auth::requireAuth();
        $userId = Auth::userId();
        $db = Database::connection();

        // 1. Fetch user core info & student profile
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

        // 4. Fetch academic education records
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

        // 6. Check Active Plan & Subscription Status
        $activePlan = SubscriptionService::getActivePlan($userId);
        $isSubscribed = ($activePlan['plan_slug'] !== 'free');

        // Fetch latest subscription row
        $stmtSub = $db->prepare("
            SELECT s.*, p.name as plan_name, p.slug as plan_slug, p.price, p.currency
            FROM subscriptions s
            JOIN subscription_plans p ON s.plan_id = p.id
            WHERE s.user_id = :uid
            ORDER BY s.id DESC
            LIMIT 1
        ");
        $stmtSub->execute(['uid' => $userId]);
        $subscription = $stmtSub->fetch(PDO::FETCH_ASSOC) ?: null;

        // Available active plans
        $plans = $db->query("SELECT * FROM subscription_plans WHERE status = 'active' ORDER BY price ASC")->fetchAll(PDO::FETCH_ASSOC);

        // Fetch user payment transactions
        $stmtTx = $db->prepare("
            SELECT * FROM payment_transactions 
            WHERE user_id = :uid 
            ORDER BY id DESC 
            LIMIT 20
        ");
        $stmtTx->execute(['uid' => $userId]);
        $transactions = $stmtTx->fetchAll(PDO::FETCH_ASSOC);

        // 7. Fetch notification preferences
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

        // 8. Fetch user preferences for reminder scope, days, channel
        $stmtUserPref = $db->prepare("SELECT * FROM user_preferences WHERE user_id = :user_id LIMIT 1");
        $stmtUserPref->execute(['user_id' => $userId]);
        $userPref = $stmtUserPref->fetch(PDO::FETCH_ASSOC) ?: [
            'deadline_reminder_scope' => 'off',
            'deadline_reminder_days' => '3,1',
            'preferred_channel' => 'email',
            'allow_multi_channel' => 0
        ];

        // 9. Fetch specific scholarship reminders
        $stmtReminders = $db->prepare("
            SELECT usr.*, s.title, s.provider_name, s.application_deadline, s.slug, s.status as sch_status
            FROM user_scholarship_reminders usr
            JOIN scholarships s ON usr.scholarship_id = s.id
            WHERE usr.user_id = :user_id
            ORDER BY s.application_deadline ASC
        ");
        $stmtReminders->execute(['user_id' => $userId]);
        $selectedReminders = $stmtReminders->fetchAll(PDO::FETCH_ASSOC);

        // 10. Determine Active Tab (profile, reminders) - plan tab hidden for now
        $activeTab = trim($_GET['tab'] ?? 'profile');
        if ($activeTab === 'reminders' && !$isSubscribed) {
            $activeTab = 'profile';
        }
        if (!in_array($activeTab, ['profile', 'reminders'], true) || $activeTab === 'plan') {
            $activeTab = 'profile';
        }

        view('profile.settings', [
            'user' => $user,
            'countries' => $countries,
            'fieldsOfStudy' => $fieldsOfStudy,
            'states' => $states,
            'cities' => $cities,
            'education' => $education,
            'prefCountries' => $prefCountries,
            'prefFields' => $prefFields,
            'prefDegrees' => $prefDegrees,
            'activePlan' => $activePlan,
            'isSubscribed' => $isSubscribed,
            'subscription' => $subscription,
            'plans' => $plans,
            'transactions' => $transactions,
            'prefMap' => $prefMap,
            'userPref' => $userPref,
            'selectedReminders' => $selectedReminders,
            'activeTab' => $activeTab,
            'csrf_token' => Security::csrfToken(),
            'title' => 'Settings',
            'success_message' => $_GET['success'] ?? null,
            'errors' => $_SESSION['profile_errors'] ?? []
        ]);
        unset($_SESSION['profile_errors']);
    }
}