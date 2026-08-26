<?php
return [
    'name' => $_ENV['APP_NAME'] ?? 'ScholarMatch',
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'url' => $_ENV['APP_URL'] ?? 'http://localhost',
    'session_lifetime' => (int)($_ENV['SESSION_LIFETIME'] ?? 2592000),
    'session_secure' => filter_var($_ENV['SESSION_SECURE'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'session_samesite' => $_ENV['SESSION_SAME_SITE'] ?? 'Lax',
];
