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
echo json_encode(array_keys($data));
if (isset($data['data'])) {
    echo " data keys: " . json_encode(array_keys($data['data']));
    $list = isset($data['data']['data']) ? $data['data']['data'] : (is_array($data['data']) ? $data['data'] : []);
    echo " count: " . count($list) . "\n";
    foreach (array_slice($list, 0, 10) as $m) {
        echo "ID: {$m['id']} | Type: {$m['content_type']} | Template: " . ($m['template_name'] ?? 'none') . " | Status: {$m['status']} | Date: {$m['created_at']} | WAMID: {$m['whatsapp_message_id']}\n";
    }
}
