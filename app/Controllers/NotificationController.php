<?php

namespace App\Controllers;

use App\Services\Database;
use App\Services\Auth;
use App\Helpers\Security;
use App\Services\NotificationQueueService;
use PDO;

class NotificationController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::connection();
    }

    /**
     * GET /admin/notifications
     * List outbox alerts with filters
     */
    public function index(): void {
        Auth::requireRole(['admin', 'employee']);
        Auth::requirePermission('notifications.view');

        $status = $_GET['status'] ?? null;
        $channel = $_GET['channel'] ?? null;
        $type = $_GET['type'] ?? null;
        $searchUser = $_GET['user'] ?? null;
        $date = $_GET['date'] ?? null;

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $query = "
            SELECT nl.*, u.first_name, u.last_name, u.email as user_email, s.title as scholarship_title 
            FROM notification_logs nl
            JOIN users u ON nl.user_id = u.id
            LEFT JOIN scholarships s ON nl.scholarship_id = s.id
            WHERE 1=1
        ";
        $params = [];

        if ($status !== null && $status !== '') {
            $query .= " AND nl.status = :status";
            $params['status'] = $status;
        }
        if ($channel !== null && $channel !== '') {
            $query .= " AND nl.channel = :channel";
            $params['channel'] = $channel;
        }
        if ($type !== null && $type !== '') {
            $query .= " AND nl.notification_type = :type";
            $params['type'] = $type;
        }
        if ($searchUser !== null && $searchUser !== '') {
            $query .= " AND (u.first_name LIKE :user OR u.last_name LIKE :user OR u.email LIKE :user)";
            $params['user'] = '%' . $searchUser . '%';
        }
        if ($date !== null && $date !== '') {
            $query .= " AND DATE(nl.created_at) = :date";
            $params['date'] = $date;
        }

        // Count queries for pagination
        $countQuery = str_replace(
            "SELECT nl.*, u.first_name, u.last_name, u.email as user_email, s.title as scholarship_title",
            "SELECT COUNT(*)",
            $query
        );
        $countStmt = $this->db->prepare($countQuery);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();
        $totalPages = ceil($total / $limit);

        // Sorting & pagination
        $query .= " ORDER BY nl.created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($query);

        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Summary counts
        $summary = $this->db->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status='processing' THEN 1 ELSE 0 END) as processing,
                SUM(CASE WHEN status='sent' THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status='failed' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status='retrying' THEN 1 ELSE 0 END) as retrying,
                SUM(CASE WHEN status='skipped' THEN 1 ELSE 0 END) as skipped,
                SUM(CASE WHEN channel='whatsapp' THEN 1 ELSE 0 END) as whatsapp_count,
                SUM(CASE WHEN channel='email' THEN 1 ELSE 0 END) as email_count
            FROM notification_logs
        ")->fetch(PDO::FETCH_ASSOC);

        view('admin.notifications', [
            'logs' => $logs,
            'summary' => $summary,
            'filters' => [
                'status' => $status,
                'channel' => $channel,
                'type' => $type,
                'user' => $searchUser,
                'date' => $date
            ],
            'pagination' => [
                'current' => $page,
                'total' => $totalPages,
                'count' => $total
            ]
        ]);
    }

    /**
     * GET /admin/notifications/{id}
     * Inspect individual outbox logs
     */
    public function show(int $id): void {
        Auth::requireRole(['admin', 'employee']);
        Auth::requirePermission('notifications.view');

        $stmt = $this->db->prepare("
            SELECT nl.*, u.first_name, u.last_name, u.email as user_email, s.title as scholarship_title 
            FROM notification_logs nl
            JOIN users u ON nl.user_id = u.id
            LEFT JOIN scholarships s ON nl.scholarship_id = s.id
            WHERE nl.id = :id LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $log = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$log) {
            http_response_code(404);
            view('errors.404');
            exit();
        }

        view('admin.notification_detail', ['log' => $log]);
    }

    /**
     * POST /admin/notifications/{id}/retry
     * Force retry of failed queue messages
     */
    public function retry(int $id): void {
        Auth::requireRole(['admin', 'employee']);
        Auth::requirePermission('notifications.retry');

        // CSRF Guard
        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            $_SESSION['notification_error'] = 'CSRF verification failed. Please try again.';
            header("Location: " . url("/admin/notifications"));
            exit();
        }

        $queueService = new NotificationQueueService();
        $success = $queueService->retryLog($id);

        if ($success) {
            $_SESSION['notification_success'] = "Notification enqueued for processing successfully.";
        } else {
            $_SESSION['notification_error'] = "Could not retry the notification. Only failed or retrying notifications can be retried.";
        }

        header("Location: " . url("/admin/notifications/$id"));
        exit();
    }
}
