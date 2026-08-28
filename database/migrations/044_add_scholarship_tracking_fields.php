<?php
return [
    'up' => "
        ALTER TABLE scholarships 
        ADD COLUMN views_count INT UNSIGNED DEFAULT 0 AFTER status,
        ADD COLUMN compared_count INT UNSIGNED DEFAULT 0 AFTER views_count;
    ",
    'down' => "
        ALTER TABLE scholarships 
        DROP COLUMN views_count,
        DROP COLUMN compared_count;
    "
];
