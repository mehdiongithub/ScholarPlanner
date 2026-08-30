<?php

return [
    'up' => function(PDO $db) {
        // 1. Add coverage_type to institutions table
        $db->exec("ALTER TABLE institutions ADD COLUMN coverage_type VARCHAR(20) NOT NULL DEFAULT 'state' AFTER city_id");

        // 2. Create institution_states table
        $db->exec("CREATE TABLE institution_states (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            institution_id BIGINT UNSIGNED NOT NULL,
            state_id BIGINT UNSIGNED NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (institution_id) REFERENCES institutions(id) ON DELETE CASCADE,
            FOREIGN KEY (state_id) REFERENCES states(id) ON DELETE CASCADE,
            UNIQUE KEY unique_institution_state (institution_id, state_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 3. Backfill existing records: for each institution with state_id, insert into institution_states
        $stmt = $db->query("SELECT id, state_id FROM institutions WHERE state_id IS NOT NULL");
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmtInsert = $db->prepare("INSERT IGNORE INTO institution_states (institution_id, state_id) VALUES (:inst_id, :state_id)");
        foreach ($records as $r) {
            $stmtInsert->execute([
                'inst_id' => $r['id'],
                'state_id' => $r['state_id']
            ]);
        }

        // 4. Backfill "Virtual University of Pakistan" as national coverage type
        $db->exec("UPDATE institutions SET coverage_type = 'national' WHERE LOWER(name) LIKE '%virtual university%' AND institution_type = 'university'");

        // 5. Add performance indexes
        $db->exec("CREATE INDEX idx_institutions_type_status_coverage ON institutions(institution_type, status, coverage_type)");
        $db->exec("CREATE INDEX idx_institutions_country ON institutions(country_id)");
        $db->exec("CREATE INDEX idx_institution_states_state ON institution_states(state_id)");
    },

    'down' => function(PDO $db) {
        $db->exec("DROP TABLE IF EXISTS institution_states");
        $db->exec("ALTER TABLE institutions DROP COLUMN coverage_type");
    }
];
