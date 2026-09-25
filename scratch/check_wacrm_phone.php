<?php

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

$baseUrl = rtrim($_ENV['WACRM_BASE_URL'] ?? '', '/');
$apiKey = $_ENV['WACRM_API_KEY'] ?? '';

function getUrl($url, $apiKey) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Accept: application/json'
    ]);
    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $httpCode, 'data' => json_decode($res, true) ?: $res];
}

echo "=== WHATSAPP SETTINGS ===\n";
echo json_encode(getUrl($baseUrl . "/api/v1/whatsapp", $apiKey), JSON_PRETTY_PRINT) . "\n\n";

echo "=== INTEGRATIONS ===\n";
echo json_encode(getUrl($baseUrl . "/api/v1/integrations", $apiKey), JSON_PRETTY_PRINT) . "\n\n";
