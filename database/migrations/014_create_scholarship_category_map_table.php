<?php
return [
    'up' => "CREATE TABLE scholarship_category_map (
            scholarship_id BIGINT UNSIGNED NOT NULL,
            category_id BIGINT UNSIGNED NOT NULL,
            PRIMARY KEY (scholarship_id, category_id),
            FOREIGN KEY (scholarship_id) REFERENCES scholarships(id) ON DELETE CASCADE,
            FOREIGN KEY (category_id) REFERENCES scholarship_categories(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    'down' => "DROP TABLE IF EXISTS scholarship_category_map;"
];
