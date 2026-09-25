<?php

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

$baseUrl = rtrim($_ENV['WACRM_BASE_URL'] ?? '', '/');
$apiKey = $_ENV['WACRM_API_KEY'] ?? '';

$ch = curl_init($baseUrl . "/api/v1/conversations/5bae2d72-1826-4d14-a7fd-4477f4c5addc/messages");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Accept: application/json'
]);
$res = curl_exec($ch);
curl_close($ch);

$data = json_decode($res, true);
$list = $data['data'] ?? [];
if (isset($list['data'])) $list = $list['data'];

echo "=== FIRST MESSAGE FULL JSON ===\n";
echo json_encode($list[0] ?? null, JSON_PRETTY_PRINT) . "\n\n";

echo "=== FAILED MESSAGE JSON (idx 2) ===\n";
echo json_encode($list[2] ?? null, JSON_PRETTY_PRINT) . "\n\n";
