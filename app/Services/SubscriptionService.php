<?php

namespace App\Services;

use App\Services\Database;
use PDO;

class SubscriptionService {
    public static array $transitions = [
        'pending' => ['active', 'failed', 'cancelled'],
        'active' => ['past_due', 'cancelled', 'expired'],
        'past_due' => ['active', 'cancelled', 'expired'],
        'cancelled' => ['expired', 'active'],
        'expired' => ['active'],
        'failed' => ['pending']
    ];

    /**
     * Determine if a user has access to a specific feature.
     */
    public static function can(?int $userId, string $feature): bool {
        if (!$userId) {
            return false;
        }

        $plan = self::getActivePlan($userId);
        $slug = $plan['plan_slug'];

        if ($slug === 'premium-monthly') {
            return true;
        }

        // Free plan permissions mapping
        switch ($feature) {
            case 'public_discovery':
            case 'basic_search':
            case 'basic_details':
                return true;
            case 'premium_matching':
            case 'advanced_search':
            case 'application_tracking':
            case 'document_readiness':
            case 'deadline_alerts':
            case 'whatsapp_alerts':
            case 'premium_alerts':
            case 'advanced_dashboard':
            default:
                return false;
        }
    }

    /**
     * Get numeric limit for a feature constraint.
     */
    public static function getLimit(?int $userId, string $limitName): int {
        if (!$userId) {
            if ($limitName === 'saved_scholarships') return 0;
            if ($limitName === 'comparisons') return 0;
            return 0;
        }

        $plan = self::getActivePlan($userId);
        $slug = $plan['plan_slug'];

        if ($slug === 'premium-monthly') {
            if ($limitName === 'saved_scholarships') return 999999;
            if ($limitName === 'comparisons') return 4; // Max 4 side-by-side
            if ($limitName === 'max_matches') return 9999;
            return 999999;
        }

        // Free plan limits
        switch ($limitName) {
            case 'saved_scholarships':
                return 10;
            case 'comparisons':
                return 3;
            case 'max_matches':
                return 5;
            default:
                return 0;
        }
    }

    /**
     * Fetch active subscription plan details for a user.
     */
    public static function getActivePlan(int $userId): array {
        $db = Database::connection();
        
        // Fetch active or cancelled (grace period) subscriptions
        $stmt = $db->prepare("
            SELECT s.*, p.slug as plan_slug, p.name as plan_name, p.price, p.currency
            FROM subscriptions s
            JOIN subscription_plans p ON s.plan_id = p.id
            WHERE s.user_id = :user_id 
              AND s.status IN ('active', 'cancelled') 
              AND s.starts_at <= NOW()
              AND s.ends_at >= NOW()
            ORDER BY s.id DESC LIMIT 1
        ");
        $stmt->execute(['user_id' => $userId]);
        $sub = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($sub) {
            return $sub;
        }

        // Default to Free Plan
        $stmtFree = $db->prepare("SELECT *, 'free' as plan_slug, 'Free' as plan_name FROM subscription_plans WHERE slug = 'free' LIMIT 1");
        $stmtFree->execute();
        $free = $stmtFree->fetch(PDO::FETCH_ASSOC);

        if ($free) {
            return $free;
        }

        // Fallback fallback if seeder didn't run
        return [
            'plan_slug' => 'free',
            'plan_name' => 'Free',
            'price' => 0.00,
            'currency' => 'PKR',
            'max_matches' => 5
        ];
    }

    /**
     * Transition a subscription to a new state safely.
     */
    public static function transition(int $subscriptionId, string $newStatus): bool {
        $db = Database::connection();
        $stmt = $db->prepare("SELECT status, user_id FROM subscriptions WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $subscriptionId]);
        $sub = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$sub) {
            return false;
        }

        $currentStatus = $sub['status'];
        if ($currentStatus === $newStatus) {
            return true;
        }

        $allowed = self::$transitions[$currentStatus] ?? [];
        if (!in_array($newStatus, $allowed)) {
            return false;
        }

        $upd = $db->prepare("UPDATE subscriptions SET status = :status, updated_at = NOW() WHERE id = :id");
        $success = $upd->execute(['status' => $newStatus, 'id' => $subscriptionId]);

        if ($success) {
            // Log audit
            Auth::logAudit(
                $sub['user_id'],
                'subscription_status_transitioned',
                'subscriptions',
                'subscriptions',
                $subscriptionId,
                null,
                ['from' => $currentStatus, 'to' => $newStatus]
            );
        }

        return $success;
    }
}
