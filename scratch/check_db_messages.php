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

$id1 = '83dfb7f9-2a4a-45da-a529-d609924c720c';
$id2 = '36fba7d7-3af3-404e-bb36-6d1b06a39120';

// Check in conversation for 923251371826
$res1 = getUrl($baseUrl . "/api/v1/conversations/5bae2d72-1826-4d14-a7fd-4477f4c5addc/messages", $apiKey);
echo "=== MESSAGES IN CONV 5bae2d72-1826-4d14-a7fd-4477f4c5addc (+923251371826) ===\n";
$list = $res1['data']['data'] ?? [];
if (isset($list['data'])) $list = $list['data'];
foreach ($list as $m) {
    if ($m['id'] === $id1 || strpos($m['created_at'], '2026-09-25T06:42') !== false || $m['status'] !== 'delivered') {
        echo "ID: {$m['id']} | Status: {$m['status']} | Created: {$m['created_at']} | WAMID: {$m['whatsapp_message_id']}\n";
    }
}

// Check in conversation for 923121303371
echo "\n=== SEARCH FOR CONTACT 923121303371 ===\n";
$c2 = getUrl($baseUrl . "/api/v1/contacts?search=923121303371", $apiKey);
if (!empty($c2['data']['data'])) {
    $cid2 = $c2['data']['data'][0]['id'];
    $conv2 = getUrl($baseUrl . "/api/v1/conversations?contact_id={$cid2}", $apiKey);
    if (!empty($conv2['data']['data'])) {
        $convId2 = $conv2['data']['data'][0]['id'];
        $m2 = getUrl($baseUrl . "/api/v1/conversations/{$convId2}/messages", $apiKey);
        $list2 = $m2['data']['data'] ?? [];
        if (isset($list2['data'])) $list2 = $list2['data'];
        foreach ($list2 as $m) {
            echo "ID: {$m['id']} | Status: {$m['status']} | Created: {$m['created_at']} | WAMID: {$m['whatsapp_message_id']}\n";
        }
    }
}
