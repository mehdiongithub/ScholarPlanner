<?php
return [
    'up' => "
        ALTER TABLE scholarship_applications ADD COLUMN internal_notes TEXT DEFAULT NULL AFTER personal_notes;
        ALTER TABLE scholarship_applications ADD INDEX idx_applications_status (status);
    ",
    'down' => "
        ALTER TABLE scholarship_applications DROP INDEX idx_applications_status;
        ALTER TABLE scholarship_applications DROP COLUMN internal_notes;
    "
];
