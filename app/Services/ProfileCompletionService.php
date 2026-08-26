<?php

namespace App\Services;

use App\Services\Database;
use PDO;

class ProfileCompletionService {
    /**
     * Calculate and cache the profile completion percentage for a user.
     */
    public static function calculate(int $userId): int {
        $db = Database::connection();
        $percentage = 0;

        // 1. Fetch user & core profile details
        $stmt = $db->prepare("
            SELECT u.first_name, u.last_name, u.email, u.phone,
                   p.date_of_birth, p.gender, p.nationality_country_id,
                   p.residence_country_id, p.residence_state_id, p.city_id
            FROM users u
            LEFT JOIN student_profiles p ON u.id = p.user_id
            WHERE u.id = :user_id
            LIMIT 1
        ");
        $stmt->execute(['user_id' => $userId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            // Personal Fields (30% total, 5% each)
            if (!empty(trim($data['first_name'] ?? ''))) $percentage += 5;
            if (!empty(trim($data['last_name'] ?? ''))) $percentage += 5;
            if (!empty(trim($data['email'] ?? ''))) $percentage += 5;
            if (!empty(trim($data['phone'] ?? ''))) $percentage += 5;
            if (!empty($data['date_of_birth'])) $percentage += 5;
            if (!empty($data['gender'])) $percentage += 5;

            // Location Fields (20% total, 5% each)
            if (!empty($data['nationality_country_id'])) $percentage += 5;
            if (!empty($data['residence_country_id'])) $percentage += 5;
            if (!empty($data['residence_state_id'])) $percentage += 5;
            if (!empty($data['city_id'])) $percentage += 5;
        }

        // 2. Academic Records (25% total - if they have >= 1 record)
        $stmtEdu = $db->prepare("SELECT COUNT(*) FROM education_records WHERE user_id = :user_id");
        $stmtEdu->execute(['user_id' => $userId]);
        if ((int)$stmtEdu->fetchColumn() > 0) {
            $percentage += 25;
        }

        // 3. Scholarship Preferences (25% total)
        // Preferred study destinations (8%)
        $stmtCountries = $db->prepare("SELECT COUNT(*) FROM user_preferred_countries WHERE user_id = :user_id");
        $stmtCountries->execute(['user_id' => $userId]);
        if ((int)$stmtCountries->fetchColumn() > 0) {
            $percentage += 8;
        }

        // Preferred fields of study (8%)
        $stmtFields = $db->prepare("SELECT COUNT(*) FROM user_preferred_fields WHERE user_id = :user_id");
        $stmtFields->execute(['user_id' => $userId]);
        if ((int)$stmtFields->fetchColumn() > 0) {
            $percentage += 8;
        }

        // Preferred degree levels (9%)
        $stmtDegrees = $db->prepare("SELECT COUNT(*) FROM user_preferred_degree_levels WHERE user_id = :user_id");
        $stmtDegrees->execute(['user_id' => $userId]);
        if ((int)$stmtDegrees->fetchColumn() > 0) {
            $percentage += 9;
        }

        // 4. Update cached value in database
        $upd = $db->prepare("
            UPDATE student_profiles 
            SET profile_completion_percentage = :pct 
            WHERE user_id = :user_id
        ");
        $upd->execute([
            'pct' => $percentage,
            'user_id' => $userId
        ]);

        return $percentage;
    }
}
