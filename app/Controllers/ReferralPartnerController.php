<?php

namespace App\Controllers;

use App\Services\Database;
use App\Services\Auth;
use App\Services\ReferralService;
use App\Helpers\Security;
use App\Helpers\View;
use PDO;

class ReferralPartnerController {
    
    /**
     * GET /referral-partner
     * Partner dashboard overview (summary metrics, referral link, monthly overview)
     */
    public function index(): void {
        Auth::requireRole('referral_partner');
        
        $partner = Auth::currentUser();
        $db = Database::connection();
        $partnerId = (int)$partner['id'];
        
        $month = trim($_GET['month'] ?? date('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }

        // Fetch authoritative metrics from ReferralService
        $metrics = ReferralService::getPartnerSummaryMetrics($partnerId, $month, $db);

        // Fetch monthly payments/commissions preview
        $monthlyData = ReferralService::getPartnerMonthlyPayments($partnerId, $month, 1, 10, $db);

        view('referral_partner.dashboard', [
            'title' => 'Overview Dashboard',
            'partner' => $partner,
            'total_signups' => $metrics['total_referred_users'],
            'total_paid_users' => $metrics['total_paid_referred_users'],
            'current_month_paid_users' => $metrics['current_month_paid_users'],
            'current_month_payments' => $metrics['current_month_payments'],
            'current_month_commission' => $metrics['current_month_commission'],
            'total_earned_commission' => $metrics['total_earned_commission'],
            'selected_month' => $month,
            'monthly_records' => $monthlyData['records'],
            'csrf_token' => Security::csrfToken()
        ]);
    }

    /**
     * GET /referral-partner/students
     * Monthly paid customers and referred students list with pagination
     * Filterable by month; unpaid users are excluded from monthly paid view
     */
    public function students(): void {
        Auth::requireRole('referral_partner');
        
        $partner = Auth::currentUser();
        $db = Database::connection();
        $partnerId = (int)$partner['id'];

        $month = trim($_GET['month'] ?? date('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }

        $currentPage = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 15;

        $monthlyData = ReferralService::getPartnerMonthlyPayments($partnerId, $month, $currentPage, $perPage, $db);

        view('referral_partner.students', [
            'title' => 'Monthly Customer Payments',
            'partner' => $partner,
            'payments' => $monthlyData['records'],
            'current_page' => $monthlyData['current_page'],
            'per_page' => $monthlyData['per_page'],
            'total_items' => $monthlyData['total_items'],
            'total_pages' => $monthlyData['total_pages'],
            'selected_month' => $month,
            'csrf_token' => Security::csrfToken()
        ]);
    }

    /**
     * GET /referral-partner/profile
     * Partner profile settings
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
            $this->redirect(url("/referral-partner/profile"));
        }

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $_SESSION['partner_errors'] = 'All password fields are required.';
            $this->redirect(url("/referral-partner/profile"));
        }

        // Verify current password hash
        $stmtUser = $db->prepare("SELECT password_hash FROM users WHERE id = :id LIMIT 1");
        $stmtUser->execute(['id' => $partner['id']]);
        $userHash = $stmtUser->fetchColumn();

        if (!password_verify($currentPassword, $userHash)) {
            $_SESSION['partner_errors'] = 'Incorrect current password.';
            $this->redirect(url("/referral-partner/profile"));
        }

        if (strlen($newPassword) < 8) {
            $_SESSION['partner_errors'] = 'New password must be at least 8 characters long.';
            $this->redirect(url("/referral-partner/profile"));
        }

        if ($newPassword !== $confirmPassword) {
            $_SESSION['partner_errors'] = 'New passwords do not match.';
            $this->redirect(url("/referral-partner/profile"));
        }

        // Save new hashed password
        $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmtUpd = $db->prepare("UPDATE users SET password_hash = :hash WHERE id = :id");
        $stmtUpd->execute(['hash' => $newHash, 'id' => $partner['id']]);

        $_SESSION['partner_success'] = 'Password changed successfully.';
        $this->redirect(url("/referral-partner/profile"));
    }

    private function halt(string $message = 'Halt execution'): void {
        if (defined('TESTING_MODE') && TESTING_MODE) {
            throw new \RuntimeException($message);
        }
        exit();
    }

    private function redirect(string $url, string $message = 'Redirect'): void {
        if (!headers_sent()) {
            header("Location: " . $url);
        }
        $this->halt($message);
    }
}
