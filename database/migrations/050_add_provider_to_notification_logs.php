<?php
return [
    'up' => "ALTER TABLE notification_logs ADD COLUMN provider VARCHAR(50) DEFAULT NULL AFTER channel;",
    'down' => "ALTER TABLE notification_logs DROP COLUMN provider;"
];
