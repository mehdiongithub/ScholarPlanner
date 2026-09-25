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

$res1 = getUrl($baseUrl . "/api/v1/messages/714cdc72-8edb-4114-b7f4-496d0482f4fa", $apiKey);
$res2 = getUrl($baseUrl . "/api/v1/messages/adad85e8-bf11-4095-aa77-80f4daf23c1b", $apiKey);

file_put_contents(__DIR__ . '/msg_utf8.json', json_encode(['failed' => $res1, 'today' => $res2], JSON_PRETTY_PRINT));
echo "Saved to msg_utf8.json\n";
