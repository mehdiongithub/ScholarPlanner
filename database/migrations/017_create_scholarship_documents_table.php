<?php
return [
    'up' => "CREATE TABLE scholarship_documents (
            scholarship_id BIGINT UNSIGNED NOT NULL,
            document_id BIGINT UNSIGNED NOT NULL,
            is_required TINYINT(1) DEFAULT 1,
            description VARCHAR(255) DEFAULT NULL,
            sort_order INT DEFAULT 0,
            PRIMARY KEY (scholarship_id, document_id),
            FOREIGN KEY (scholarship_id) REFERENCES scholarships(id) ON DELETE CASCADE,
            FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    'down' => "DROP TABLE IF EXISTS scholarship_documents;"
];
