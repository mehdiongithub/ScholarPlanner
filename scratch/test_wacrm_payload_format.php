<?php

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

$baseUrl = rtrim($_ENV['WACRM_BASE_URL'] ?? '', '/');
$apiKey = $_ENV['WACRM_API_KEY'] ?? '';

$url = $baseUrl . '/api/v1/messages';

$tests = [
    'flat_template_name_params' => [
        'to' => '+923163261056',
        'type' => 'template',
        'template_name' => 'new_match',
        'params' => ['Test Title', 'Test Provider', '2026-09-30', 'https://scholarplanner.com']
    ],
    'flat_template_name_parameters' => [
        'to' => '+923163261056',
        'type' => 'template',
        'template_name' => 'new_match',
        'parameters' => ['Test Title', 'Test Provider', '2026-09-30', 'https://scholarplanner.com']
    ],
    'flat_template_name_components' => [
        'to' => '+923163261056',
        'type' => 'template',
        'template_name' => 'new_match',
        'language' => 'en_US',
        'body_params' => ['Test Title', 'Test Provider', '2026-09-30', 'https://scholarplanner.com']
    ]
];

foreach ($tests as $label => $pl) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($pl));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json',
        'Accept: application/json'
    ]);

    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "=== POST /api/v1/messages ({$label}) HTTP {$httpCode} ===\n";
    echo $res . "\n\n";
}
