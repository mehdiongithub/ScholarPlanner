<?php

namespace App\Controllers;

use App\Services\Auth;
use App\Services\Database;
use App\Helpers\Security;
use App\Helpers\View;
use PDO;
use Exception;
use RuntimeException;

class IntelligenceController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::connection();
    }

    /**
     * GET /admin/intelligence
     * Displays admin operations dashboard, analytics and warning alerts
     */
    public function index(): void {
        Auth::requireRole(['admin', 'employee']);

        // Default tab selection
        $tab = trim($_GET['tab'] ?? 'alerts');
        if (!in_array($tab, ['alerts', 'quality', 'analytics', 'notifications', 'billing'])) {
            $tab = 'alerts';
        }

        // Validate specific permissions for tabs
        if ($tab === 'quality') {
            Auth::requirePermission('scholarships.verify');
        } elseif ($tab === 'billing') {
            Auth::requireRole('admin'); // Only admins can access billing metrics
        } else {
            Auth::requirePermission('reports.view');
        }

        $dateRange = $this->parseDateRange();
        $data['date_range'] = $dateRange;
        $dateSuffix = "_" . $dateRange['range'] . "_" . md5($dateRange['start'] . $dateRange['end']);

        $data = array_merge($data, [
            'tab' => $tab,
            'csrf_token' => Security::csrfToken(),
            'user' => Auth::currentUser()
        ]);

        // 1. Alerts Tab calculations
        if ($tab === 'alerts') {
            $data['alerts'] = \App\Services\CacheService::get('admin_operational_alerts', function() {
                return $this->calculateOperationalAlerts();
            }, 300);
        } 
        // 2. Quality Tab calculations
        elseif ($tab === 'quality') {
            $data['quality_stats'] = \App\Services\CacheService::get('admin_quality_stats', function() {
                return $this->calculateQualityStats();
            }, 300);
            $data['quality_list'] = $this->fetchQualityList();
        } 
        // 3. Analytics Tab calculations
        elseif ($tab === 'analytics') {
            $data['app_analytics'] = \App\Services\CacheService::get('admin_app_analytics' . $dateSuffix, function() use ($dateRange) {
                return $this->calculateApplicationAnalytics($dateRange);
            }, 300);
            $data['match_analytics'] = \App\Services\CacheService::get('admin_match_analytics' . $dateSuffix, function() use ($dateRange) {
                return $this->calculateMatchingAnalytics($dateRange);
            }, 300);
        } 
        // 4. Notifications Tab calculations
        elseif ($tab === 'notifications') {
            $data['notif_analytics'] = \App\Services\CacheService::get('admin_notif_analytics' . $dateSuffix, function() use ($dateRange) {
                return $this->calculateNotificationAnalytics($dateRange);
            }, 300);
            $data['notif_list'] = $this->fetchNotificationQueueList();
        }
        // 5. Billing Tab calculations
        elseif ($tab === 'billing') {
            $data['billing_stats'] = \App\Services\CacheService::get('admin_billing_stats' . $dateSuffix, function() use ($dateRange) {
                return $this->calculateBillingStats($dateRange);
            }, 300);
        }

        View::render('admin.intelligence.dashboard', $data);
    }

    /**
     * POST /admin/intelligence/quality/bulk
     * Safely executes selected bulk actions (verify, archive, delete drafts) inside transactions
     */
    public function bulkAction(): void {
        Auth::requireRole(['admin', 'employee']);
        Auth::requirePermission('scholarships.verify');

        $csrf = $_POST['csrf_token'] ?? null;
        if (!Security::verifyCsrfToken($csrf)) {
            $_SESSION['intelligence_error'] = 'CSRF verification failed.';
            $this->redirectBackToIntelligence('quality');
        }

        $action = trim($_POST['bulk_action'] ?? '');
        $ids = $_POST['scholarship_ids'] ?? [];

        if (!in_array($action, ['verify', 'archive', 'delete_drafts'])) {
            $_SESSION['intelligence_error'] = 'Invalid bulk action selected.';
            $this->redirectBackToIntelligence('quality');
        }

        if (empty($ids) || !is_array($ids)) {
            $_SESSION['intelligence_error'] = 'No scholarships selected for the action.';
            $this->redirectBackToIntelligence('quality');
        }

        // Sanitize IDs
        $cleanedIds = array_map('intval', $ids);
        $placeholders = implode(',', $cleanedIds);

        $this->db->beginTransaction();
        try {
            if ($action === 'verify') {
                $stmt = $this->db->prepare("
                    UPDATE scholarships 
                    SET verification_status = 'verified', 
                        verified_at = NOW(), 
                        verified_by = :uid 
                    WHERE id IN ($placeholders)
                ");
                $stmt->execute(['uid' => Auth::userId()]);
                $affected = $stmt->rowCount();

                // Log audit action using core helper
                Auth::logAudit(Auth::userId(), 'bulk_verify', 'scholarships', 'scholarships', 0, null, ['count' => $affected]);

                $_SESSION['intelligence_success'] = "Successfully verified $affected scholarships.";
            } 
            elseif ($action === 'archive') {
                $stmt = $this->db->prepare("
                    UPDATE scholarships 
                    SET status = 'archived' 
                    WHERE id IN ($placeholders) AND status = 'published' AND application_deadline < CURDATE()
                ");
                $stmt->execute();
                $affected = $stmt->rowCount();

                Auth::logAudit(Auth::userId(), 'bulk_archive', 'scholarships', 'scholarships', 0, null, ['count' => $affected]);

                $_SESSION['intelligence_success'] = "Successfully archived $affected expired scholarships.";
            } 
            elseif ($action === 'delete_drafts') {
                // Drafts can only be deleted if they are not published or archived
                $stmt = $this->db->prepare("
                    DELETE FROM scholarships 
                    WHERE id IN ($placeholders) AND status = 'draft'
                ");
                $stmt->execute();
                $affected = $stmt->rowCount();

                Auth::logAudit(Auth::userId(), 'bulk_delete_drafts', 'scholarships', 'scholarships', 0, null, ['count' => $affected]);

                $_SESSION['intelligence_success'] = "Successfully deleted $affected draft scholarships.";
            }

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            $_SESSION['intelligence_error'] = 'Database transaction failed: ' . $e->getMessage();
        }

        $this->redirectBackToIntelligence('quality');
    }

    /**
     * Compute operational warning alerts
     */
    private function calculateOperationalAlerts(): array {
        // A. Expired published scholarships
        $expiredCount = $this->db->query("
            SELECT COUNT(*) FROM scholarships 
            WHERE status = 'published' AND application_deadline < CURDATE()
        ")->fetchColumn();

        // B. Scholarships closing soon (within 7 days)
        $closingSoonCount = $this->db->query("
            SELECT COUNT(*) FROM scholarships 
            WHERE status = 'published' AND application_deadline >= CURDATE() AND application_deadline <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        ")->fetchColumn();

        // C. Failed notification log attempts
        $failedNotifCount = $this->db->query("
            SELECT COUNT(*) FROM notification_logs WHERE status = 'failed'
        ")->fetchColumn();

        // D. Stale notification outbox processing jobs
        $staleNotifCount = $this->db->query("
            SELECT COUNT(*) FROM notification_logs 
            WHERE status = 'processing' AND available_at <= NOW()
        ")->fetchColumn();

        // E. Incomplete published scholarship records (missing required description or URLs)
        $incompleteCount = $this->db->query("
            SELECT COUNT(*) FROM scholarships 
            WHERE status = 'published' AND (short_description IS NULL OR short_description = '' OR official_website IS NULL OR official_website = '' OR official_application_url IS NULL OR official_application_url = '')
        ")->fetchColumn();

        // F. Applications requiring attention (e.g. applications on expired/archived opportunities with status not submitted)
        $appAttentionCount = $this->db->query("
            SELECT COUNT(*) FROM scholarship_applications sa
            JOIN scholarships s ON sa.scholarship_id = s.id
            WHERE sa.status IN ('interested', 'planning', 'documents_pending', 'ready_to_apply') 
              AND (s.application_deadline < CURDATE() OR s.status = 'archived')
        ")->fetchColumn();

        // G. Document review backlog (pending reviews in user documents)
        $docBacklog = $this->db->query("
            SELECT COUNT(*) FROM user_documents WHERE status = 'submitted'
        ")->fetchColumn();

        // H. System configuration warnings
        $sysConfigWarnings = [];
        if (empty($_ENV['SMTP_HOST']) || $_ENV['SMTP_HOST'] === 'mailhog') {
            $sysConfigWarnings[] = 'SMTP Host is set to local Mailhog instance (testing config).';
        }
        if (empty($_ENV['WHATSAPP_TOKEN']) || $_ENV['WHATSAPP_TOKEN'] === 'mock_token') {
            $sysConfigWarnings[] = 'WhatsApp Business Service Token is set to mock verification client.';
        }

        return [
            'expired_published' => $expiredCount,
            'closing_soon' => $closingSoonCount,
            'failed_notifications' => $failedNotifCount,
            'stale_notifications' => $staleNotifCount,
            'incomplete_scholarships' => $incompleteCount,
            'applications_attention' => $appAttentionCount,
            'document_backlog' => $docBacklog,
            'sys_config_warnings' => $sysConfigWarnings
        ];
    }

    /**
     * Compute Quality Dashboard statistics
     */
    private function calculateQualityStats(): array {
        $total = $this->db->query("SELECT COUNT(*) FROM scholarships")->fetchColumn();
        
        $statuses = $this->db->query("
            SELECT status, COUNT(*) as cnt FROM scholarships GROUP BY status
        ")->fetchAll(PDO::FETCH_KEY_PAIR);

        $expired = $this->db->query("
            SELECT COUNT(*) FROM scholarships WHERE status = 'published' AND application_deadline < CURDATE()
        ")->fetchColumn();

        $closingSoon = $this->db->query("
            SELECT COUNT(*) FROM scholarships 
            WHERE status = 'published' AND application_deadline >= CURDATE() AND application_deadline <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        ")->fetchColumn();

        $missingInfo = $this->db->query("
            SELECT COUNT(*) FROM scholarships 
            WHERE status = 'published' AND (short_description IS NULL OR official_website IS NULL OR official_application_url IS NULL OR country_id IS NULL OR study_level IS NULL OR funding_type IS NULL OR application_deadline IS NULL)
        ")->fetchColumn();

        $invalidUrls = $this->db->query("
            SELECT COUNT(*) FROM scholarships 
            WHERE status = 'published' AND (
                (official_website IS NOT NULL AND official_website NOT LIKE 'http://%' AND official_website NOT LIKE 'https://%') OR
                (official_application_url IS NOT NULL AND official_application_url NOT LIKE 'http://%' AND official_application_url NOT LIKE 'https://%')
            )
        ")->fetchColumn();

        $noDocs = $this->db->query("
            SELECT COUNT(*) FROM scholarships s 
            WHERE s.status = 'published' 
              AND NOT EXISTS (SELECT 1 FROM scholarship_documents sd WHERE sd.scholarship_id = s.id)
        ")->fetchColumn();

        $duplicates = $this->db->query("
            SELECT COUNT(*) FROM scholarships s1 
            WHERE EXISTS (SELECT 1 FROM scholarships s2 WHERE s2.id <> s1.id AND LOWER(s2.title) = LOWER(s1.title))
        ")->fetchColumn();

        // Calculate average completeness score
        $avgCompleteness = $this->db->query("
            SELECT AVG(
                (CASE WHEN short_description IS NOT NULL AND short_description <> '' THEN 10 ELSE 0 END) +
                (CASE WHEN official_website IS NOT NULL AND official_website <> '' THEN 10 ELSE 0 END) +
                (CASE WHEN official_application_url IS NOT NULL AND official_application_url <> '' THEN 10 ELSE 0 END) +
                (CASE WHEN country_id IS NOT NULL THEN 10 ELSE 0 END) +
                (CASE WHEN study_level IS NOT NULL AND study_level <> '' THEN 10 ELSE 0 END) +
                (CASE WHEN funding_type IS NOT NULL AND funding_type <> '' THEN 10 ELSE 0 END) +
                (CASE WHEN application_open_date IS NOT NULL THEN 10 ELSE 0 END) +
                (CASE WHEN application_deadline IS NOT NULL THEN 10 ELSE 0 END) +
                (CASE WHEN EXISTS (SELECT 1 FROM scholarship_eligibility_rules WHERE scholarship_id = s.id) THEN 10 ELSE 0 END) +
                (CASE WHEN EXISTS (SELECT 1 FROM scholarship_documents WHERE scholarship_id = s.id) THEN 10 ELSE 0 END)
            ) as avg_score
            FROM scholarships s
        ")->fetchColumn() ?: 0;

        return [
            'total' => $total,
            'drafts' => $statuses['draft'] ?? 0,
            'published' => $statuses['published'] ?? 0,
            'archived' => $statuses['archived'] ?? 0,
            'expired' => $expired,
            'closing_soon' => $closingSoon,
            'missing_info' => $missingInfo,
            'invalid_urls' => $invalidUrls,
            'no_docs' => $noDocs,
            'duplicates' => $duplicates,
            'avg_completeness' => round($avgCompleteness, 1)
        ];
    }

    /**
     * Fetch list of scholarships with computed quality scores and issues
     */
    private function fetchQualityList(): array {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $filterCompleteness = trim($_GET['completeness'] ?? 'all');
        $filterStatus = trim($_GET['status'] ?? 'all');
        $filterIssue = trim($_GET['issue'] ?? 'all');
        $search = trim($_GET['search'] ?? '');
        $sort = trim($_GET['sort'] ?? 'completeness_score');

        // Whitelist sorting columns
        $whitelistedSorts = [
            'title' => 's.title ASC',
            'provider' => 's.provider_name ASC',
            'status' => 's.status ASC',
            'verification_status' => 's.verification_status ASC',
            'deadline' => 's.application_deadline ASC',
            'completeness_score' => 'completeness_score DESC',
            'completeness_score_asc' => 'completeness_score ASC'
        ];
        $orderBy = $whitelistedSorts[$sort] ?? 'completeness_score DESC';

        $whereClauses = ["1=1"];
        $params = [];

        if ($search !== '') {
            $whereClauses[] = "(s.title LIKE :search OR s.provider_name LIKE :search)";
            $params['search'] = "%$search%";
        }

        if ($filterStatus !== 'all') {
            $whereClauses[] = "s.status = :status";
            $params['status'] = $filterStatus;
        }

        if ($filterIssue === 'expired') {
            $whereClauses[] = "s.status = 'published' AND s.application_deadline < CURDATE()";
        } elseif ($filterIssue === 'closing_soon') {
            $whereClauses[] = "s.status = 'published' AND s.application_deadline >= CURDATE() AND s.application_deadline <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
        } elseif ($filterIssue === 'invalid_urls') {
            $whereClauses[] = "s.status = 'published' AND (
                (s.official_website IS NOT NULL AND s.official_website NOT LIKE 'http://%' AND s.official_website NOT LIKE 'https://%') OR
                (s.official_application_url IS NOT NULL AND s.official_application_url NOT LIKE 'http://%' AND s.official_application_url NOT LIKE 'https://%')
            )";
        } elseif ($filterIssue === 'no_docs') {
            $whereClauses[] = "s.status = 'published' AND NOT EXISTS (SELECT 1 FROM scholarship_documents sd WHERE sd.scholarship_id = s.id)";
        } elseif ($filterIssue === 'duplicates') {
            $whereClauses[] = "EXISTS (SELECT 1 FROM scholarships s2 WHERE s2.id <> s.id AND LOWER(s2.title) = LOWER(s.title))";
        }

        $whereSql = implode(' AND ', $whereClauses);

        // Core aggregate query computing completeness score dynamically
        $sql = "
            SELECT s.id, s.title, s.provider_name, s.status, s.verification_status, s.application_deadline, s.official_website, s.official_application_url,
                   (
                     (CASE WHEN s.short_description IS NOT NULL AND s.short_description <> '' THEN 10 ELSE 0 END) +
                     (CASE WHEN s.official_website IS NOT NULL AND s.official_website <> '' THEN 10 ELSE 0 END) +
                     (CASE WHEN s.official_application_url IS NOT NULL AND s.official_application_url <> '' THEN 10 ELSE 0 END) +
                     (CASE WHEN s.country_id IS NOT NULL THEN 10 ELSE 0 END) +
                     (CASE WHEN s.study_level IS NOT NULL AND s.study_level <> '' THEN 10 ELSE 0 END) +
                     (CASE WHEN s.funding_type IS NOT NULL AND s.funding_type <> '' THEN 10 ELSE 0 END) +
                     (CASE WHEN s.application_open_date IS NOT NULL THEN 10 ELSE 0 END) +
                     (CASE WHEN s.application_deadline IS NOT NULL THEN 10 ELSE 0 END) +
                     (CASE WHEN EXISTS (SELECT 1 FROM scholarship_eligibility_rules WHERE scholarship_id = s.id) THEN 10 ELSE 0 END) +
                     (CASE WHEN EXISTS (SELECT 1 FROM scholarship_documents WHERE scholarship_id = s.id) THEN 10 ELSE 0 END)
                   ) as completeness_score
            FROM scholarships s
            WHERE $whereSql
        ";

        // Filter by completeness score (needs HAVING clause since it's computed)
        if ($filterCompleteness === 'low') {
            $sql .= " HAVING completeness_score < 50";
        } elseif ($filterCompleteness === 'medium') {
            $sql .= " HAVING completeness_score < 80";
        }

        // Get total item counts first
        $countSql = "SELECT COUNT(*) FROM ($sql) as temp";
        $stmtCount = $this->db->prepare($countSql);
        $stmtCount->execute($params);
        $totalItems = (int)$stmtCount->fetchColumn();

        // Add sorting, limits, and fetch rows
        $sql .= " ORDER BY $orderBy LIMIT :limit OFFSET :offset";
        $stmtRows = $this->db->prepare($sql);
        
        // Bind parameters safely
        foreach ($params as $key => $val) {
            $stmtRows->bindValue($key, $val);
        }
        $stmtRows->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmtRows->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmtRows->execute();
        $items = $stmtRows->fetchAll(PDO::FETCH_ASSOC);

        // Process validation warning lists and duplicate checks
        $dupeCounts = [];
        if (!empty($items)) {
            $titles = array_column($items, 'title');
            $placeholders = implode(',', array_fill(0, count($titles), '?'));
            $stmtDupe = $this->db->prepare("
                SELECT LOWER(title) as lower_title, COUNT(*) as cnt 
                FROM scholarships 
                WHERE LOWER(title) IN ($placeholders) 
                GROUP BY LOWER(title)
            ");
            $stmtDupe->execute(array_map('strtolower', $titles));
            $dupeCounts = $stmtDupe->fetchAll(PDO::FETCH_KEY_PAIR);
        }

        foreach ($items as &$item) {
            $warnings = [];
            if (empty($item['application_deadline'])) {
                $warnings[] = 'Missing deadline';
            }
            if (empty($item['official_website']) || (strpos($item['official_website'], 'http') !== 0)) {
                $warnings[] = 'Invalid/Missing website URL';
            }
            if (empty($item['official_application_url']) || (strpos($item['official_application_url'], 'http') !== 0)) {
                $warnings[] = 'Invalid/Missing application URL';
            }

            // Check duplicate by title using pre-fetched counts
            $lowerTitle = strtolower($item['title']);
            $cnt = $dupeCounts[$lowerTitle] ?? 1;
            if ($cnt > 1) {
                $warnings[] = 'Duplicate title detected';
            }

            $item['warnings'] = $warnings;
        }

        $totalPages = ceil($totalItems / $limit);

        return [
            'items' => $items,
            'total_items' => $totalItems,
            'total_pages' => $totalPages,
            'page' => $page,
            'filters' => [
                'completeness' => $filterCompleteness,
                'status' => $filterStatus,
                'issue' => $filterIssue,
                'search' => $search,
                'sort' => $sort
            ]
        ];
    }

    /**
     * Compute Application stats and Conversion funnel percentages
     */
    private function calculateApplicationAnalytics(array $dateRange = []): array {
        $db = $this->db;
        $start = $dateRange['start'] ?? '1970-01-01 00:00:00';
        $end = $dateRange['end'] ?? '9999-12-31 23:59:59';

        // Total visitor role id
        $visitorRoleId = $db->query("SELECT id FROM roles WHERE name = 'visitor'")->fetchColumn();

        // 1. Total registered visitor students
        $totalRegistered = (int)$db->query("SELECT COUNT(*) FROM users WHERE role_id = $visitorRoleId")->fetchColumn();
        $registeredInRange = (int)$db->query("SELECT COUNT(*) FROM users WHERE role_id = $visitorRoleId AND created_at BETWEEN '$start' AND '$end'")->fetchColumn();

        // New registrations timeline by date
        $registrationsTimeline = $db->query("
            SELECT DATE(created_at) as reg_date, COUNT(*) as count 
            FROM users 
            WHERE role_id = $visitorRoleId AND created_at BETWEEN '$start' AND '$end' 
            GROUP BY DATE(created_at) 
            ORDER BY reg_date ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        // 2. Profile Completed
        $totalCompleted = (int)$db->query("SELECT COUNT(DISTINCT user_id) FROM student_profiles")->fetchColumn();

        // 3. Scholarship Viewed (aggregate sum of views_count on scholarships)
        $totalViews = (int)$db->query("SELECT COALESCE(SUM(views_count), 0) FROM scholarships")->fetchColumn();

        // 4. Scholarship Saved (aggregate saves count)
        $totalSaves = (int)$db->query("SELECT COUNT(*) FROM saved_scholarships")->fetchColumn();

        // 5. Application Started (status IN ('interested', 'planning', 'documents_pending', 'ready_to_apply'))
        $totalStarted = (int)$db->query("
            SELECT COUNT(*) FROM scholarship_applications 
            WHERE status IN ('interested', 'planning', 'documents_pending', 'ready_to_apply') 
              AND created_at BETWEEN '$start' AND '$end'
        ")->fetchColumn();

        // 6. Application Submitted (status IN ('applied', 'interview', 'accepted', 'rejected'))
        $totalSubmitted = (int)$db->query("
            SELECT COUNT(*) FROM scholarship_applications 
            WHERE status IN ('applied', 'interview', 'accepted', 'rejected') 
              AND created_at BETWEEN '$start' AND '$end'
        ")->fetchColumn();

        // 7. Under Review
        $totalReview = (int)$db->query("
            SELECT COUNT(*) FROM scholarship_applications 
            WHERE status = 'applied'
              AND created_at BETWEEN '$start' AND '$end'
        ")->fetchColumn();

        // 8. Outcome: Accepted / Rejected
        $totalAccepted = (int)$db->query("
            SELECT COUNT(*) FROM scholarship_applications 
            WHERE status = 'accepted'
              AND created_at BETWEEN '$start' AND '$end'
        ")->fetchColumn();

        $totalRejected = (int)$db->query("
            SELECT COUNT(*) FROM scholarship_applications 
            WHERE status = 'rejected'
              AND created_at BETWEEN '$start' AND '$end'
        ")->fetchColumn();

        $statusCounts = $db->query("
            SELECT status, COUNT(*) as cnt 
            FROM scholarship_applications 
            WHERE created_at BETWEEN '$start' AND '$end' 
            GROUP BY status
        ")->fetchAll(PDO::FETCH_KEY_PAIR);

        // Top scholarships applied
        $topScholarships = $db->query("
            SELECT s.title, COUNT(sa.id) as cnt 
            FROM scholarship_applications sa 
            JOIN scholarships s ON sa.scholarship_id = s.id 
            WHERE sa.created_at BETWEEN '$start' AND '$end'
            GROUP BY sa.scholarship_id 
            ORDER BY cnt DESC 
            LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Group by Host Country
        $countryStats = $db->query("
            SELECT c.name as country_name, COUNT(sa.id) as cnt 
            FROM scholarship_applications sa 
            JOIN scholarships s ON sa.scholarship_id = s.id 
            JOIN countries c ON s.country_id = c.id 
            WHERE sa.created_at BETWEEN '$start' AND '$end'
            GROUP BY s.country_id 
            ORDER BY cnt DESC 
            LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Group by Field of Study
        $fieldStats = $db->query("
            SELECT f.name as field_name, COUNT(sa.id) as cnt 
            FROM scholarship_applications sa 
            JOIN scholarship_fields sf ON sa.scholarship_id = sf.scholarship_id 
            JOIN fields_of_study f ON sf.field_of_study_id = f.id 
            WHERE sa.created_at BETWEEN '$start' AND '$end'
            GROUP BY sf.field_of_study_id 
            ORDER BY cnt DESC 
            LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Active vs Expired Opportunity Application Counts
        $activeScholarshipApps = $db->query("
            SELECT COUNT(sa.id) FROM scholarship_applications sa
            JOIN scholarships s ON sa.scholarship_id = s.id
            WHERE sa.created_at BETWEEN '$start' AND '$end' AND s.application_deadline >= CURDATE()
        ")->fetchColumn();

        $expiredScholarshipApps = $db->query("
            SELECT COUNT(sa.id) FROM scholarship_applications sa
            JOIN scholarships s ON sa.scholarship_id = s.id
            WHERE sa.created_at BETWEEN '$start' AND '$end' AND s.application_deadline < CURDATE()
        ")->fetchColumn();

        $totalInterestedRaw = (int)$db->query("SELECT COUNT(*) FROM scholarship_applications WHERE created_at BETWEEN '$start' AND '$end'")->fetchColumn();
        $totalPlanningRaw = (int)$db->query("
            SELECT COUNT(*) FROM scholarship_applications 
            WHERE status IN ('planning', 'documents_pending', 'ready_to_apply', 'applied', 'interview', 'accepted', 'rejected')
              AND created_at BETWEEN '$start' AND '$end'
        ")->fetchColumn();
        $totalOutcomeRaw = $totalAccepted + $totalRejected;

        return [
            'total_students' => $totalRegistered,
            'students_range' => $registeredInRange,
            'reg_timeline' => $registrationsTimeline,
            'status_counts' => $statusCounts,
            'funnel' => [
                // Legacy keys
                'interested' => $totalInterestedRaw,
                'planning' => $totalPlanningRaw,
                'submitted' => $totalSubmitted,
                'outcome' => $totalOutcomeRaw,
                
                // Detailed keys
                'registered' => $totalRegistered,
                'profile_completed' => $totalCompleted,
                'viewed' => $totalViews,
                'saved' => $totalSaves,
                'started' => $totalStarted,
                'review' => $totalReview,
                'accepted' => $totalAccepted,
                'rejected' => $totalRejected,
                'conversion_rate' => $totalStarted > 0 ? round(($totalSubmitted / $totalStarted) * 100, 1) : 0
            ],
            'top_scholarships' => $topScholarships,
            'country_stats' => $countryStats,
            'field_stats' => $fieldStats,
            'active_apps' => $activeScholarshipApps,
            'expired_apps' => $expiredScholarshipApps
        ];
    }

    /**
     * Compute Match score analytics
     */
    private function calculateMatchingAnalytics(array $dateRange = []): array {
        $db = $this->db;
        $start = $dateRange['start'] ?? '1970-01-01 00:00:00';
        $end = $dateRange['end'] ?? '9999-12-31 23:59:59';

        // Distinct matched students in date range
        $matchedStudents = $db->query("
            SELECT COUNT(DISTINCT user_id) 
            FROM scholarship_matches
            WHERE created_at BETWEEN '$start' AND '$end'
        ")->fetchColumn();

        // Status counts
        $eligibilityStats = $db->query("
            SELECT eligibility_status, COUNT(*) as cnt 
            FROM scholarship_matches 
            WHERE created_at BETWEEN '$start' AND '$end'
            GROUP BY eligibility_status
        ")->fetchAll(PDO::FETCH_KEY_PAIR);

        // Average score
        $avgScore = $db->query("
            SELECT AVG(match_score) 
            FROM scholarship_matches
            WHERE created_at BETWEEN '$start' AND '$end'
        ")->fetchColumn() ?: 0;

        // Top matched scholarships
        $topMatches = $db->query("
            SELECT s.title, COUNT(m.id) as cnt 
            FROM scholarship_matches m 
            JOIN scholarships s ON m.scholarship_id = s.id 
            WHERE m.created_at BETWEEN '$start' AND '$end'
            GROUP BY m.scholarship_id 
            ORDER BY cnt DESC 
            LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Low matches
        $lowMatches = $db->query("
            SELECT s.title, COUNT(m.id) as cnt 
            FROM scholarships s 
            LEFT JOIN scholarship_matches m ON m.scholarship_id = s.id AND m.created_at BETWEEN '$start' AND '$end'
            WHERE s.status = 'published' 
            GROUP BY s.id 
            ORDER BY cnt ASC 
            LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Document readiness aggregates
        $completeProfiles = $db->query("
            SELECT COUNT(*) FROM student_profiles 
            WHERE profile_completion_percentage >= 80
        ")->fetchColumn();

        $incompleteProfiles = $db->query("
            SELECT COUNT(*) FROM student_profiles 
            WHERE profile_completion_percentage < 80
        ")->fetchColumn();

        $missingDocs = $db->query("
            SELECT COUNT(*) FROM user_documents WHERE status = 'missing'
        ")->fetchColumn();

        $rejectedDocs = $db->query("
            SELECT COUNT(*) FROM user_documents WHERE status = 'rejected'
        ")->fetchColumn();

        $pendingDocs = $db->query("
            SELECT COUNT(*) FROM user_documents WHERE status = 'submitted'
        ")->fetchColumn();

        $readyToApply = $db->query("
            SELECT COUNT(DISTINCT user_id) 
            FROM scholarship_applications 
            WHERE status = 'ready_to_apply'
        ")->fetchColumn();

        return [
            'matched_students' => $matchedStudents,
            'eligibility_stats' => $eligibilityStats,
            'avg_score' => round($avgScore, 1),
            'top_matches' => $topMatches,
            'low_matches' => $lowMatches,
            'doc_readiness' => [
                'complete_profiles' => $completeProfiles,
                'incomplete_profiles' => $incompleteProfiles,
                'missing_docs' => $missingDocs,
                'rejected_docs' => $rejectedDocs,
                'pending_docs' => $pendingDocs,
                'ready_to_apply' => $readyToApply
            ]
        ];
    }

    /**
     * Compute Notification Analytics outbox counts
     */
    private function calculateNotificationAnalytics(array $dateRange = []): array {
        $db = $this->db;
        $start = $dateRange['start'] ?? '1970-01-01 00:00:00';
        $end = $dateRange['end'] ?? '9999-12-31 23:59:59';

        $statusCounts = $db->query("
            SELECT status, COUNT(*) as cnt 
            FROM notification_logs 
            WHERE created_at BETWEEN '$start' AND '$end'
            GROUP BY status
        ")->fetchAll(PDO::FETCH_KEY_PAIR);

        $channelCounts = $db->query("
            SELECT channel, COUNT(*) as cnt 
            FROM notification_logs 
            WHERE created_at BETWEEN '$start' AND '$end'
            GROUP BY channel
        ")->fetchAll(PDO::FETCH_KEY_PAIR);

        $total = array_sum($statusCounts);
        $sent = $statusCounts['sent'] ?? 0;
        $failed = $statusCounts['failed'] ?? 0;

        $successRate = $total > 0 ? round(($sent / $total) * 100, 1) : 0;
        $failureRate = $total > 0 ? round(($failed / $total) * 100, 1) : 0;

        return [
            'status_counts' => $statusCounts,
            'channel_counts' => $channelCounts,
            'success_rate' => $successRate,
            'failure_rate' => $failureRate,
            'total' => $total
        ];
    }

    /**
     * Fetch list of notification logs for outbox log search
     */
    private function fetchNotificationQueueList(): array {
        $page = max(1, (int)($_GET['notif_page'] ?? 1));
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $search = trim($_GET['notif_search'] ?? '');
        $status = trim($_GET['notif_status'] ?? 'all');

        $whereClauses = ["1=1"];
        $params = [];

        if ($search !== '') {
            $whereClauses[] = "(recipient LIKE :search OR subject LIKE :search OR error_message LIKE :search)";
            $params['search'] = "%$search%";
        }

        if ($status !== 'all') {
            $whereClauses[] = "status = :status";
            $params['status'] = $status;
        }

        $whereSql = implode(' AND ', $whereClauses);

        $countSql = "SELECT COUNT(*) FROM notification_logs WHERE $whereSql";
        $stmtCount = $this->db->prepare($countSql);
        $stmtCount->execute($params);
        $totalItems = (int)$stmtCount->fetchColumn();

        $sql = "
            SELECT id, notification_type, channel, recipient, subject, status, attempts, available_at, failed_at, error_message, created_at 
            FROM notification_logs
            WHERE $whereSql
            ORDER BY created_at DESC 
            LIMIT :limit OFFSET :offset
        ";

        $stmtRows = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmtRows->bindValue($key, $val);
        }
        $stmtRows->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmtRows->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmtRows->execute();
        $items = $stmtRows->fetchAll(PDO::FETCH_ASSOC);

        $totalPages = ceil($totalItems / $limit);

        return [
            'items' => $items,
            'total_items' => $totalItems,
            'total_pages' => $totalPages,
            'page' => $page,
            'filters' => [
                'search' => $search,
                'status' => $status
            ]
        ];
    }

    private function calculateBillingStats(array $dateRange = []): array {
        $db = $this->db;
        $start = $dateRange['start'] ?? '1970-01-01 00:00:00';
        $end = $dateRange['end'] ?? '9999-12-31 23:59:59';

        $totalSubscribers = (int)$db->query("
            SELECT COUNT(DISTINCT user_id) 
            FROM subscriptions 
            WHERE status IN ('active', 'cancelled', 'past_due') AND ends_at >= NOW() AND created_at BETWEEN '$start' AND '$end'
        ")->fetchColumn();

        $activeCount = (int)$db->query("
            SELECT COUNT(*) 
            FROM subscriptions 
            WHERE status = 'active' AND created_at BETWEEN '$start' AND '$end'
        ")->fetchColumn();

        $expiredCount = (int)$db->query("
            SELECT COUNT(*) 
            FROM subscriptions 
            WHERE status = 'expired' AND created_at BETWEEN '$start' AND '$end'
        ")->fetchColumn();

        $cancelledCount = (int)$db->query("
            SELECT COUNT(*) 
            FROM subscriptions 
            WHERE status = 'cancelled' AND created_at BETWEEN '$start' AND '$end'
        ")->fetchColumn();
        
        $revenueTotal = (float)$db->query("
            SELECT COALESCE(SUM(amount), 0.00) 
            FROM payment_transactions 
            WHERE status = 'paid' AND created_at BETWEEN '$start' AND '$end'
        ")->fetchColumn();

        $failedCount = (int)$db->query("
            SELECT COUNT(*) 
            FROM payment_transactions 
            WHERE status = 'failed' AND created_at BETWEEN '$start' AND '$end'
        ")->fetchColumn();
        
        $totalTxCount = (int)$db->query("
            SELECT COUNT(*) 
            FROM payment_transactions 
            WHERE created_at BETWEEN '$start' AND '$end'
        ")->fetchColumn();

        $paidTxCount = (int)$db->query("
            SELECT COUNT(*) 
            FROM payment_transactions 
            WHERE status = 'paid' AND created_at BETWEEN '$start' AND '$end'
        ")->fetchColumn();
        
        $successRate = $totalTxCount > 0 ? round(($paidTxCount / $totalTxCount) * 100, 1) : 100.0;
        
        $breakdown = $db->query("
            SELECT provider, COUNT(*) as count, SUM(amount) as total 
            FROM payment_transactions 
            WHERE status = 'paid' AND created_at BETWEEN '$start' AND '$end'
            GROUP BY provider
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        $txList = $db->query("
            SELECT pt.*, u.email 
            FROM payment_transactions pt
            JOIN users u ON pt.user_id = u.id
            WHERE pt.created_at BETWEEN '$start' AND '$end'
            ORDER BY pt.id DESC
            LIMIT 50
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'total_subscribers' => $totalSubscribers,
            'active_subscriptions' => $activeCount,
            'expired_subscriptions' => $expiredCount,
            'cancelled_subscriptions' => $cancelledCount,
            'revenue_totals' => $revenueTotal,
            'failed_payments' => $failedCount,
            'payment_success_rate' => $successRate,
            'provider_breakdown' => $breakdown,
            'transactions' => $txList
        ];
    }

    /**
     * Bypasses header redirect and throws RuntimeException in test cases
     */
    private function redirectBackToIntelligence(string $tab): void {
        $url = url('/admin/intelligence?tab=' . $tab);
        header("Location: " . $url);
        if (defined('TESTING_MODE') && TESTING_MODE) {
            throw new RuntimeException("Redirect to intelligence page");
        }
        exit();
    }

    private function parseDateRange(): array {
        $range = trim($_GET['range'] ?? 'last_30_days');
        $startDate = trim($_GET['start_date'] ?? '');
        $endDate = trim($_GET['end_date'] ?? '');

        $start = null;
        $end = null;
        $today = date('Y-m-d');
        
        switch ($range) {
            case 'today':
                $start = $today . ' 00:00:00';
                $end = $today . ' 23:59:59';
                break;
            case 'yesterday':
                $yesterday = date('Y-m-d', strtotime('-1 day'));
                $start = $yesterday . ' 00:00:00';
                $end = $yesterday . ' 23:59:59';
                break;
            case 'last_7_days':
                $start = date('Y-m-d', strtotime('-6 days')) . ' 00:00:00';
                $end = $today . ' 23:59:59';
                break;
            case 'last_30_days':
            default:
                $range = 'last_30_days';
                $start = date('Y-m-d', strtotime('-29 days')) . ' 00:00:00';
                $end = $today . ' 23:59:59';
                break;
            case 'current_month':
                $start = date('Y-m-01') . ' 00:00:00';
                $end = date('Y-m-t') . ' 23:59:59';
                break;
            case 'previous_month':
                $start = date('Y-m-01', strtotime('-1 month')) . ' 00:00:00';
                $end = date('Y-m-t', strtotime('-1 month')) . ' 23:59:59';
                break;
            case 'custom':
                if (!$this->isValidDate($startDate) || !$this->isValidDate($endDate)) {
                    $range = 'last_30_days';
                    $start = date('Y-m-d', strtotime('-29 days')) . ' 00:00:00';
                    $end = $today . ' 23:59:59';
                } else {
                    if (strtotime($startDate) > strtotime($endDate)) {
                        $temp = $startDate;
                        $startDate = $endDate;
                        $endDate = $temp;
                    }
                    
                    // Cap range to 366 days
                    $diff = (strtotime($endDate) - strtotime($startDate)) / 86400;
                    if ($diff > 366) {
                        $startDate = date('Y-m-d', strtotime($endDate . ' -366 days'));
                    }

                    $start = $startDate . ' 00:00:00';
                    $end = $endDate . ' 23:59:59';
                }
                break;
        }

        return [
            'range' => $range,
            'start' => $start,
            'end' => $end,
            'start_date' => date('Y-m-d', strtotime($start)),
            'end_date' => date('Y-m-d', strtotime($end))
        ];
    }

    private function isValidDate(string $date): bool {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }
        $parts = explode('-', $date);
        return checkdate((int)$parts[1], (int)$parts[2], (int)$parts[0]);
    }

    public function downloadExport(): void {
        Auth::requireRole(['admin', 'employee']);
        
        $type = trim($_GET['type'] ?? '');
        $expires = (int)($_GET['expires'] ?? 0);
        $token = trim($_GET['token'] ?? '');

        // 1. Verify Expiration
        if (time() > $expires) {
            $this->dieWithError(403, "Expired download link.");
        }

        // 2. Verify Signature
        $secret = $_ENV['APP_KEY'] ?? 'fallback_signing_secret_key_999';
        $expectedToken = hash_hmac('sha256', "type={$type}&expires={$expires}", $secret);
        
        if (!hash_equals($expectedToken, $token)) {
            $this->dieWithError(403, "Invalid download signature.");
        }

        // 3. Check specific permission depending on type
        if ($type === 'payment' || $type === 'subscription') {
            Auth::requireRole('admin');
        } else {
            Auth::requirePermission('reports.view');
        }

        // 4. Stream CSV response
        $this->generateCSV($type);
    }

    private function dieWithError(int $code, string $message): void {
        http_response_code($code);
        if (defined('TESTING_MODE') && TESTING_MODE) {
            throw new \RuntimeException($message, $code);
        }
        echo "<h1>Error $code</h1><p>" . e($message) . "</p>";
        exit();
    }

    private function generateCSV(string $type): void {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $type . '_export_' . date('Ymd_His') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        $db = $this->db;
        $db->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);

        try {
            switch ($type) {
                case 'student':
                    fputcsv($output, ['Student ID', 'First Name', 'Last Name', 'Email', 'Phone', 'Status', 'Registered At']);
                    $stmt = $db->query("
                        SELECT id, first_name, last_name, email, phone, status, created_at 
                        FROM users 
                        WHERE role_id = (SELECT id FROM roles WHERE name = 'visitor')
                        ORDER BY id ASC
                    ");
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        fputcsv($output, array_map([$this, 'sanitizeCSVCell'], [
                            $row['id'],
                            $row['first_name'],
                            $row['last_name'],
                            $row['email'],
                            $row['phone'] ?? '',
                            $row['status'],
                            $row['created_at']
                        ]));
                    }
                    break;
                case 'application':
                    fputcsv($output, ['Application ID', 'Student Email', 'Scholarship Title', 'Status', 'Applied At']);
                    $stmt = $db->query("
                        SELECT sa.id, u.email, s.title, sa.status, sa.created_at 
                        FROM scholarship_applications sa
                        JOIN users u ON sa.user_id = u.id
                        JOIN scholarships s ON sa.scholarship_id = s.id
                        ORDER BY sa.id ASC
                    ");
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        fputcsv($output, array_map([$this, 'sanitizeCSVCell'], [
                            $row['id'],
                            $row['email'],
                            $row['title'],
                            $row['status'],
                            $row['created_at']
                        ]));
                    }
                    break;
                case 'scholarship':
                    fputcsv($output, ['Scholarship ID', 'Title', 'Provider', 'Status', 'Views', 'Saves', 'Matches', 'Applications', 'Completeness %']);
                    $stmt = $db->query("
                        SELECT s.id, s.title, s.provider_name, s.status, s.views_count,
                            (SELECT COUNT(*) FROM saved_scholarships ss WHERE ss.scholarship_id = s.id) as saves_cnt,
                            (SELECT COUNT(*) FROM scholarship_matches sm WHERE sm.scholarship_id = s.id) as matches_cnt,
                            (SELECT COUNT(*) FROM scholarship_applications sa WHERE sa.scholarship_id = s.id) as apps_cnt,
                            (
                                (CASE WHEN s.short_description IS NOT NULL AND s.short_description <> '' THEN 10 ELSE 0 END) +
                                (CASE WHEN s.official_website IS NOT NULL AND s.official_website <> '' THEN 10 ELSE 0 END) +
                                (CASE WHEN s.official_application_url IS NOT NULL AND s.official_application_url <> '' THEN 10 ELSE 0 END) +
                                (CASE WHEN s.country_id IS NOT NULL THEN 10 ELSE 0 END) +
                                (CASE WHEN s.study_level IS NOT NULL AND s.study_level <> '' THEN 10 ELSE 0 END) +
                                (CASE WHEN s.funding_type IS NOT NULL AND s.funding_type <> '' THEN 10 ELSE 0 END) +
                                (CASE WHEN s.application_open_date IS NOT NULL THEN 10 ELSE 0 END) +
                                (CASE WHEN s.application_deadline IS NOT NULL THEN 10 ELSE 0 END) +
                                (CASE WHEN EXISTS (SELECT 1 FROM scholarship_eligibility_rules WHERE scholarship_id = s.id) THEN 10 ELSE 0 END) +
                                (CASE WHEN EXISTS (SELECT 1 FROM scholarship_documents WHERE scholarship_id = s.id) THEN 10 ELSE 0 END)
                            ) as completeness
                        FROM scholarships s
                        ORDER BY s.id ASC
                    ");
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        fputcsv($output, array_map([$this, 'sanitizeCSVCell'], [
                            $row['id'],
                            $row['title'],
                            $row['provider_name'],
                            $row['status'],
                            $row['views_count'],
                            $row['saves_cnt'],
                            $row['matches_cnt'],
                            $row['apps_cnt'],
                            $row['completeness']
                        ]));
                    }
                    break;
                case 'notification':
                    fputcsv($output, ['Log ID', 'Type', 'Channel', 'Recipient', 'Status', 'Attempts', 'Created At']);
                    $stmt = $db->query("
                        SELECT id, notification_type, channel, recipient, status, attempts, created_at 
                        FROM notification_logs 
                        ORDER BY id ASC
                    ");
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        fputcsv($output, array_map([$this, 'sanitizeCSVCell'], [
                            $row['id'],
                            $row['notification_type'],
                            $row['channel'],
                            $row['recipient'],
                            $row['status'],
                            $row['attempts'],
                            $row['created_at']
                        ]));
                    }
                    break;
                case 'subscription':
                    fputcsv($output, ['Subscription ID', 'User Email', 'Plan ID', 'Status', 'Ends At']);
                    $stmt = $db->query("
                        SELECT s.id, u.email, s.plan_id, s.status, s.ends_at 
                        FROM subscriptions s
                        JOIN users u ON s.user_id = u.id
                        ORDER BY s.id ASC
                    ");
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        fputcsv($output, array_map([$this, 'sanitizeCSVCell'], [
                            $row['id'],
                            $row['email'],
                            $row['plan_id'],
                            $row['status'],
                            $row['ends_at']
                        ]));
                    }
                    break;
                case 'payment':
                    fputcsv($output, ['Tx ID', 'User Email', 'Reference', 'Amount', 'Currency', 'Status', 'Provider', 'Created At']);
                    $stmt = $db->query("
                        SELECT pt.id, u.email, pt.transaction_reference, pt.amount, pt.currency, pt.status, pt.provider, pt.created_at 
                        FROM payment_transactions pt
                        JOIN users u ON pt.user_id = u.id
                        ORDER BY pt.id ASC
                    ");
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        fputcsv($output, array_map([$this, 'sanitizeCSVCell'], [
                            $row['id'],
                            $row['email'],
                            $row['transaction_reference'],
                            $row['amount'],
                            $row['currency'],
                            $row['status'],
                            $row['provider'],
                            $row['created_at']
                        ]));
                    }
                    break;
                default:
                    $this->dieWithError(400, "Unknown export type.");
            }
            
            Auth::logAudit(
                Auth::userId() !== null ? (int)Auth::userId() : null,
                'admin.export',
                'exports',
                'analytics',
                0,
                null,
                ['export_type' => $type]
            );
            
        } finally {
            $db->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
            fclose($output);
        }
        if (defined('TESTING_MODE') && TESTING_MODE) {
            return;
        }
        exit();
    }

    private function sanitizeCSVCell($value) {
        $str = (string)$value;
        $trimmed = trim($str);
        if ($trimmed !== '' && in_array($trimmed[0], ['=', '+', '-', '@'], true)) {
            return "'" . $str;
        }
        return $str;
    }
}
