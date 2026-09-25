<?php

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

$baseUrl = rtrim($_ENV['WACRM_BASE_URL'] ?? '', '/');
$apiKey = $_ENV['WACRM_API_KEY'] ?? '';

// Try various template endpoints
$endpoints = [
    '/api/v1/whatsapp/templates',
    '/api/v1/settings/templates',
    '/api/templates',
    '/api/v1/meta/templates'
];

foreach ($endpoints as $ep) {
    $ch = curl_init($baseUrl . $ep);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Accept: application/json'
    ]);
    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo "=== GET $ep (HTTP $httpCode) ===\n";
    if ($httpCode !== 404) {
        echo substr($res, 0, 500) . "\n";
    }
}
