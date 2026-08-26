<?php
namespace App\Services;

use App\Services\Database;
use PDO;

class DocumentReadinessService {
    private PDO $db;

    public function __construct() {
        $this->db = Database::connection();
    }

    /**
     * Calculate document readiness for a specific scholarship
     */
    public function calculateForScholarship(int $userId, int $scholarshipId): array {
        // 1. Fetch requirements for this scholarship
        $stmt = $this->db->prepare("
            SELECT sd.document_id, d.name, sd.is_required
            FROM scholarship_documents sd
            JOIN documents d ON sd.document_id = d.id
            WHERE sd.scholarship_id = :sid
        ");
        $stmt->execute(['sid' => $scholarshipId]);
        $requirements = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. Fetch user's uploaded documents
        $stmtDocs = $this->db->prepare("
            SELECT document_id, status, rejection_reason
            FROM user_documents
            WHERE user_id = :uid
        ");
        $stmtDocs->execute(['uid' => $userId]);
        $uploaded = $stmtDocs->fetchAll(PDO::FETCH_ASSOC);

        $uploadedMap = [];
        foreach ($uploaded as $up) {
            $uploadedMap[$up['document_id']] = $up;
        }

        $requiredCount = 0;
        $uploadedCount = 0;
        $approvedCount = 0;
        $rejectedCount = 0;
        $missingCount = 0;

        $details = [];

        foreach ($requirements as $req) {
            $docId = $req['document_id'];
            $isRequired = (bool)$req['is_required'];

            if ($isRequired) {
                $requiredCount++;
            }

            if (isset($uploadedMap[$docId])) {
                $up = $uploadedMap[$docId];
                $status = $up['status'];
                
                if ($isRequired) {
                    $uploadedCount++;
                    if ($status === 'approved') {
                        $approvedCount++;
                    } elseif ($status === 'rejected') {
                        $rejectedCount++;
                    }
                }
                
                $details[] = [
                    'document_id' => $docId,
                    'name' => $req['name'],
                    'is_required' => $isRequired,
                    'status' => $status,
                    'rejection_reason' => $up['rejection_reason']
                ];
            } else {
                if ($isRequired) {
                    $missingCount++;
                }
                $details[] = [
                    'document_id' => $docId,
                    'name' => $req['name'],
                    'is_required' => $isRequired,
                    'status' => 'missing',
                    'rejection_reason' => null
                ];
            }
        }

        $percentage = 0;
        if ($requiredCount > 0) {
            $percentage = round(($approvedCount / $requiredCount) * 100);
        } else {
            $percentage = 100;
        }

        return [
            'required_count' => $requiredCount,
            'uploaded_count' => $uploadedCount,
            'approved_count' => $approvedCount,
            'rejected_count' => $rejectedCount,
            'missing_count' => $missingCount,
            'readiness_percentage' => (int)$percentage,
            'details' => $details
        ];
    }

    /**
     * Calculate global document readiness (across all pre-seeded documents)
     */
    public function calculateGlobal(int $userId): array {
        $stmt = $this->db->query("SELECT id, name FROM documents");
        $allDocs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmtDocs = $this->db->prepare("
            SELECT document_id, status, rejection_reason
            FROM user_documents
            WHERE user_id = :uid
        ");
        $stmtDocs->execute(['uid' => $userId]);
        $uploaded = $stmtDocs->fetchAll(PDO::FETCH_ASSOC);

        $uploadedMap = [];
        foreach ($uploaded as $up) {
            $uploadedMap[$up['document_id']] = $up;
        }

        $totalCount = count($allDocs);
        $uploadedCount = 0;
        $approvedCount = 0;
        $rejectedCount = 0;
        $missingCount = 0;

        foreach ($allDocs as $doc) {
            $docId = $doc['id'];
            if (isset($uploadedMap[$docId])) {
                $status = $uploadedMap[$docId]['status'];
                $uploadedCount++;
                if ($status === 'approved') {
                    $approvedCount++;
                } elseif ($status === 'rejected') {
                    $rejectedCount++;
                }
            } else {
                $missingCount++;
            }
        }

        $percentage = 0;
        if ($totalCount > 0) {
            $percentage = round(($approvedCount / $totalCount) * 100);
        }

        return [
            'required_count' => $totalCount,
            'uploaded_count' => $uploadedCount,
            'approved_count' => $approvedCount,
            'rejected_count' => $rejectedCount,
            'missing_count' => $missingCount,
            'readiness_percentage' => (int)$percentage
        ];
    }
}
