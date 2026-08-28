<?php

namespace App\Controllers;

use App\Services\Database;
use PDO;

class HomeController {
    /**
     * Display SaaS homepage with dynamic listings and categories
     */
    public function index() {
        $db = Database::connection();

        // 1. Popular Countries (select countries with most scholarships or alphabetical limit 6)
        $countries = $db->query("
            SELECT c.id, c.name, COUNT(s.id) as scholarship_count 
            FROM countries c
            JOIN scholarships s ON s.country_id = c.id
            WHERE s.status = 'published' AND (s.application_deadline IS NULL OR s.application_deadline >= CURDATE())
            GROUP BY c.id, c.name
            ORDER BY scholarship_count DESC, c.name ASC
            LIMIT 6
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Fallback if no published scholarships exist yet to populate layout cards
        if (empty($countries)) {
            $countries = $db->query("SELECT id, name FROM countries ORDER BY name ASC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);
        }

        // 2. Popular Fields of Study
        $fields = $db->query("
            SELECT f.id, f.name, COUNT(sf.scholarship_id) as scholarship_count
            FROM fields_of_study f
            JOIN scholarship_fields sf ON sf.field_of_study_id = f.id
            JOIN scholarships s ON sf.scholarship_id = s.id
            WHERE s.status = 'published' AND (s.application_deadline IS NULL OR s.application_deadline >= CURDATE())
            GROUP BY f.id, f.name
            ORDER BY scholarship_count DESC, f.name ASC
            LIMIT 6
        ")->fetchAll(PDO::FETCH_ASSOC);

        if (empty($fields)) {
            $fields = $db->query("SELECT id, name FROM fields_of_study ORDER BY name ASC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);
        }

        // 3. Recently Added (published opportunities, sorted by publish date DESC)
        $recentScholarships = $db->query("
            SELECT s.*, c.name as country_name 
            FROM scholarships s
            LEFT JOIN countries c ON s.country_id = c.id
            WHERE s.status = 'published' AND (s.application_deadline IS NULL OR s.application_deadline >= CURDATE())
            ORDER BY s.published_at DESC, s.id DESC
            LIMIT 3
        ")->fetchAll(PDO::FETCH_ASSOC);

        // 4. Closing Soon (published opportunities, deadline >= today, within 7 days)
        $closingScholarships = $db->query("
            SELECT s.*, c.name as country_name 
            FROM scholarships s
            LEFT JOIN countries c ON s.country_id = c.id
            WHERE s.status = 'published' 
              AND s.application_deadline >= CURDATE() 
              AND s.application_deadline <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
            ORDER BY s.application_deadline ASC
            LIMIT 3
        ")->fetchAll(PDO::FETCH_ASSOC);

        return view('home', [
            'countries' => $countries,
            'fields' => $fields,
            'recentScholarships' => $recentScholarships,
            'closingScholarships' => $closingScholarships
        ]);
    }
}
