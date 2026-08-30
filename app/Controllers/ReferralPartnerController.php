<?php

namespace App\Controllers;

use App\Services\Database;
use App\Services\Auth;
use App\Helpers\Security;
use App\Helpers\View;
use PDO;

class ReferralPartnerController {
    
    /**
     * GET /referral-partner
     * Partner dashboard overview (metrics & link)
     */
    public function index(): void {
        Auth::requireRole('referral_partner');
        
        $partner = Auth::currentUser();
        $db = Database::connection();
        $refCode = $partner['referral_code'];
        
        // Attributed Signups Count
        $stmtSignups = $db->prepare("SELECT COUNT(*) FROM users WHERE referred_by_code = :ref");
        $stmtSignups->execute(['ref' => $refCode]);
        $totalSignups = (int)$stmtSignups->fetchColumn();
        
        // Paid Conversions Count
        $stmtConversions = $db->prepare("
            SELECT COUNT(DISTINCT user_id) 
            FROM payment_transactions 
            WHERE referral_code_used = :ref AND status IN ('paid', 'success')
        ");
        $stmtConversions->execute(['ref' => $refCode]);
        $conversions = (int)$stmtConversions->fetchColumn();
        
        // Dynamically compute commission
        $commission = $conversions * 20.00;
        
        view('referral_partner.dashboard', [
            'title' => 'Overview Dashboard',
            'partner' => $partner,
            'total_signups' => $totalSignups,
            'conversions' => $conversions,
            'commission' => number_format($commission, 2),
            'csrf_token' => Security::csrfToken()
        ]);
    }

    /**
     * GET /referral-partner/students
     * Privacy-compliant referred students list with basic pagination
     */
    public function students(): void {
        Auth::requireRole('referral_partner');
        
        $partner = Auth::currentUser();
        $db = Database::connection();
        $refCode = $partner['referral_code'];

        // Get total count for paging
        $stmtCount = $db->prepare("SELECT COUNT(*) FROM users WHERE referred_by_code = :ref");
        $stmtCount->execute(['ref' => $refCode]);
        $totalItems = (int)$stmtCount->fetchColumn();

        // Pagination variables
        $currentPage = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 10;
        $offset = ($currentPage - 1) * $perPage;
        $totalPages = ceil($totalItems / $perPage);
        
        // Fetch users
        $stmtUsers = $db->prepare("
            SELECT u.first_name, u.last_name, u.email, u.created_at,
                   (
                       SELECT s.status 
                       FROM subscriptions s 
                       WHERE s.user_id = u.id AND s.status = 'active' AND (s.ends_at IS NULL OR s.ends_at > NOW())
                       ORDER BY s.id DESC LIMIT 1
                   ) AS active_sub_status
            FROM users u
            WHERE u.referred_by_code = :ref
            ORDER BY u.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        
        $stmtUsers->bindValue(':ref', $refCode, PDO::PARAM_STR);
        $stmtUsers->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmtUsers->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmtUsers->execute();
        $referredStudents = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);
        
        // Format names with last initial for privacy and map status
        foreach ($referredStudents as &$s) {
            $lastInitial = !empty($s['last_name']) ? ' ' . strtoupper($s['last_name'][0]) . '.' : '';
            $s['display_name'] = trim(($s['first_name'] ?? '')) . $lastInitial;
            $s['sub_status'] = $s['active_sub_status'] ? 'Active' : 'Inactive';
            unset($s['first_name'], $s['last_name'], $s['active_sub_status']);
        }
        
        view('referral_partner.students', [
            'title' => 'Referred Students',
            'students' => $referredStudents,
            'current_page' => $currentPage,
            'per_page' => $perPage,
            'total_items' => $totalItems,
            'total_pages' => $totalPages,
            'offset' => $offset,
            'csrf_token' => Security::csrfToken()
        ]);
    }

    /**
     * GET /referral-partner/profile
     * Minimal profile options (Change Password)
     */
    public function profile(): void {
        Auth::requireRole('referral_partner');
        $partner = Auth::currentUser();
        
        view('referral_partner.profile', [
            'title' => 'Profile Settings',
            'partner' => $partner,
            'csrf_token' => Security::csrfToken()
        ]);
    }

    /**
     * POST /referral-partner/profile
     * Handle partner password change
     */
    public function profileUpdate(): void {
        Auth::requireRole('referral_partner');
        $partner = Auth::currentUser();
        $db = Database::connection();

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            $_SESSION['partner_errors'] = 'CSRF verification failed.';
            header("Location: " . url("/referral-partner/profile"));
            exit();
        }

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $_SESSION['partner_errors'] = 'All password fields are required.';
            header("Location: " . url("/referral-partner/profile"));
            exit();
        }

        // Verify current password hash
        $stmtUser = $db->prepare("SELECT password_hash FROM users WHERE id = :id LIMIT 1");
        $stmtUser->execute(['id' => $partner['id']]);
        $userHash = $stmtUser->fetchColumn();

        if (!password_verify($currentPassword, $userHash)) {
            $_SESSION['partner_errors'] = 'Incorrect current password.';
            header("Location: " . url("/referral-partner/profile"));
            exit();
        }

        if (strlen($newPassword) < 8) {
            $_SESSION['partner_errors'] = 'New password must be at least 8 characters long.';
            header("Location: " . url("/referral-partner/profile"));
            exit();
        }

        if ($newPassword !== $confirmPassword) {
            $_SESSION['partner_errors'] = 'New passwords do not match.';
            header("Location: " . url("/referral-partner/profile"));
            exit();
        }

        // Save new hashed password
        $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmtUpd = $db->prepare("UPDATE users SET password_hash = :hash WHERE id = :id");
        $stmtUpd->execute(['hash' => $newHash, 'id' => $partner['id']]);

        $_SESSION['partner_success'] = 'Password changed successfully.';
        header("Location: " . url("/referral-partner/profile"));
        exit();
    }
}
