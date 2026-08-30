<?php

return [
    'up' => function(PDO $db) {
        // 1. Create institutions table
        $db->exec("CREATE TABLE institutions (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            institution_type VARCHAR(50) NOT NULL, -- school, college, university, other
            country_id BIGINT UNSIGNED DEFAULT NULL,
            state_id BIGINT UNSIGNED DEFAULT NULL,
            city_id BIGINT UNSIGNED DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'approved', -- approved, pending, rejected, inactive
            created_by BIGINT UNSIGNED DEFAULT NULL,
            reviewed_by BIGINT UNSIGNED DEFAULT NULL,
            reviewed_at TIMESTAMP DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (country_id) REFERENCES countries(id) ON DELETE SET NULL,
            FOREIGN KEY (state_id) REFERENCES states(id) ON DELETE SET NULL,
            FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE SET NULL,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_institution_location (country_id, state_id, city_id),
            INDEX idx_institution_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        // 2. Add institution_id column and foreign key to education_records
        $db->exec("ALTER TABLE education_records 
            ADD COLUMN institution_id BIGINT UNSIGNED DEFAULT NULL AFTER user_id,
            MODIFY COLUMN institution_name VARCHAR(150) DEFAULT NULL
        ");

        $db->exec("ALTER TABLE education_records
            ADD CONSTRAINT fk_education_institution FOREIGN KEY (institution_id) REFERENCES institutions(id) ON DELETE SET NULL
        ");

        // 3. Populate existing unique institution names into institutions table
        $stmt = $db->query("
            SELECT DISTINCT institution_name, country_id, degree_level 
            FROM education_records 
            WHERE institution_name IS NOT NULL AND TRIM(institution_name) != ''
        ");
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmtInsert = $db->prepare("
            INSERT INTO institutions (name, institution_type, country_id, status, created_at, updated_at) 
            VALUES (:name, :type, :country_id, 'approved', NOW(), NOW())
        ");
        
        $stmtUpdate = $db->prepare("
            UPDATE education_records 
            SET institution_id = :inst_id 
            WHERE institution_name = :name 
              AND (country_id = :country_id OR (country_id IS NULL AND :country_id2 IS NULL))
        ");

        foreach ($records as $r) {
            $lvl = $r['degree_level'];
            if ($lvl === 'High School') {
                $type = 'school';
            } elseif (in_array($lvl, ['Intermediate / College', 'Diploma', 'Associate Degree'])) {
                $type = 'college';
            } elseif (in_array($lvl, ['Bachelor\'s', 'Master\'s', 'MPhil', 'PhD', 'Postdoctoral'])) {
                $type = 'university';
            } else {
                $type = 'other';
            }

            $stmtInsert->execute([
                'name' => $r['institution_name'],
                'type' => $type,
                'country_id' => $r['country_id']
            ]);
            $instId = $db->lastInsertId();

            $stmtUpdate->execute([
                'inst_id' => $instId,
                'name' => $r['institution_name'],
                'country_id' => $r['country_id'],
                'country_id2' => $r['country_id']
            ]);
        }

        // 4. Seed default test universities in Pakistan (Sindh, Punjab, Islamabad) for Select2 dropdown filtering testing
        $pakistanId = $db->query("SELECT id FROM countries WHERE iso2 = 'PK' LIMIT 1")->fetchColumn();
        if ($pakistanId) {
            $sindhId = $db->query("SELECT id FROM states WHERE country_id = {$pakistanId} AND name LIKE '%Sindh%' LIMIT 1")->fetchColumn();
            $punjabId = $db->query("SELECT id FROM states WHERE country_id = {$pakistanId} AND name LIKE '%Punjab%' LIMIT 1")->fetchColumn();
            $islamabadId = $db->query("SELECT id FROM states WHERE country_id = {$pakistanId} AND name LIKE '%Islamabad%' LIMIT 1")->fetchColumn();

            $unis = [
                ['name' => 'University of Karachi', 'type' => 'university', 'state' => $sindhId],
                ['name' => 'NED University of Engineering and Technology', 'type' => 'university', 'state' => $sindhId],
                ['name' => 'LUMS (Lahore University of Management Sciences)', 'type' => 'university', 'state' => $punjabId],
                ['name' => 'University of the Punjab', 'type' => 'university', 'state' => $punjabId],
                ['name' => 'NUST (National University of Sciences and Technology)', 'type' => 'university', 'state' => $islamabadId],
                ['name' => 'Quaid-i-Azam University', 'type' => 'university', 'state' => $islamabadId],
            ];

            $stmtSeed = $db->prepare("
                INSERT INTO institutions (name, institution_type, country_id, state_id, status, created_at, updated_at) 
                VALUES (:name, :type, :country_id, :state_id, 'approved', NOW(), NOW())
            ");

            foreach ($unis as $u) {
                if ($u['state']) {
                    $stmtSeed->execute([
                        'name' => $u['name'],
                        'type' => $u['type'],
                        'country_id' => $pakistanId,
                        'state_id' => $u['state']
                    ]);
                }
            }
        }
    },

    'down' => function(PDO $db) {
        // Drop foreign key and column from education_records
        $db->exec("ALTER TABLE education_records DROP FOREIGN KEY fk_education_institution");
        $db->exec("ALTER TABLE education_records DROP COLUMN institution_id");
        $db->exec("DROP TABLE IF EXISTS institutions;");
    }
];
