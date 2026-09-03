<?php

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

$baseUrl = rtrim($_ENV['WACRM_BASE_URL'] ?? '', '/');
$apiKey = $_ENV['WACRM_API_KEY'] ?? '';

$url = $baseUrl . '/api/v1/messages';

$payloads = [
    'camel_templateName' => [
        'to' => '+923163261056',
        'type' => 'template',
        'templateName' => 'new_match',
        'params' => ['A', 'B', 'C', 'D']
    ],
    'template_object_name' => [
        'to' => '+923163261056',
        'type' => 'template',
        'template' => [
            'name' => 'new_match'
        ]
    ],
    'type_template_only' => [
        'to' => '+923163261056',
        'type' => 'template'
    ],
    'empty_payload' => [
    ]
];

foreach ($payloads as $label => $pl) {
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
