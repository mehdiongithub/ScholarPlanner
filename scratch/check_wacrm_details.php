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

echo "=== SEARCH CONTACTS FOR 923251371826 ===\n";
$contacts = getUrl($baseUrl . '/api/v1/contacts?search=923251371826', $apiKey);
echo json_encode($contacts, JSON_PRETTY_PRINT) . "\n\n";

if (!empty($contacts['data']['data'])) {
    foreach ($contacts['data']['data'] as $contact) {
        $cid = $contact['id'];
        echo "=== CONVERSATIONS FOR CONTACT {$cid} ===\n";
        $convs = getUrl($baseUrl . "/api/v1/conversations?contact_id={$cid}", $apiKey);
        echo json_encode($convs, JSON_PRETTY_PRINT) . "\n";
        if (!empty($convs['data']['data'])) {
            foreach ($convs['data']['data'] as $conv) {
                $convId = $conv['id'];
                echo "=== MESSAGES FOR CONVERSATION {$convId} ===\n";
                $msgs = getUrl($baseUrl . "/api/v1/conversations/{$convId}/messages", $apiKey);
                echo json_encode($msgs, JSON_PRETTY_PRINT) . "\n";
            }
        }
    }
}
