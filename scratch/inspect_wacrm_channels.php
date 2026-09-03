<?php

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

$baseUrl = rtrim($_ENV['WACRM_BASE_URL'] ?? '', '/');
$apiKey = $_ENV['WACRM_API_KEY'] ?? '';

$endpoints = [
    '/api/v1/me',
    '/api/v1/channels',
    '/api/v1/devices',
    '/api/v1/integrations',
    '/api/v1/whatsapp',
    '/api/v1/templates'
];

foreach ($endpoints as $ep) {
    $url = $baseUrl . $ep;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Accept: application/json'
    ]);

    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $json = json_decode($res, true);
    if ($json !== null) {
        unset($json['token'], $json['api_key']);
    }

    echo "=== GET {$ep} (HTTP {$httpCode}) ===\n";
    echo substr(json_encode($json ?: $res, JSON_PRETTY_PRINT), 0, 500) . "\n\n";
}
