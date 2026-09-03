<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Safe load .env
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

$baseUrl = $_ENV['WACRM_BASE_URL'] ?? '';
$apiKey = $_ENV['WACRM_API_KEY'] ?? '';
$timeout = (int)($_ENV['WACRM_API_TIMEOUT'] ?? 15);

$result = [
    'env' => [
        'base_url_configured' => !empty($baseUrl),
        'base_url_normalized' => rtrim($baseUrl, '/'),
        'api_key_configured' => !empty($apiKey),
        'api_key_length' => strlen($apiKey),
        'timeout_configured' => $timeout > 0,
        'timeout_val' => $timeout
    ],
    'https' => [
        'uses_https' => str_starts_with(strtolower(trim($baseUrl)), 'https://')
    ],
    'api_call' => [
        'endpoint' => rtrim($baseUrl, '/') . '/api/v1/me',
        'http_code' => 0,
        'curl_error' => '',
        'success' => false,
        'data' => null,
        'scopes' => [],
        'has_messages_send_scope' => false
    ]
];

if (!empty($baseUrl) && !empty($apiKey)) {
    $url = rtrim($baseUrl, '/') . '/api/v1/me';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    // Explicitly verify SSL
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Accept: application/json',
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    $result['api_call']['http_code'] = $httpCode;
    $result['api_call']['curl_error'] = $curlError;

    if ($response) {
        $json = json_decode($response, true);
        if ($json !== null) {
            $result['api_call']['raw_json_keys'] = array_keys($json);
            if (isset($json['data'])) {
                // Redact any possible sensitive fields in data
                $safeData = $json['data'];
                unset($safeData['token'], $safeData['api_key'], $safeData['secret'], $safeData['password']);
                $result['api_call']['data'] = $safeData;
            }
            if (isset($json['error'])) {
                $result['api_call']['error'] = $json['error'];
            }
            if (isset($json['scopes']) && is_array($json['scopes'])) {
                $result['api_call']['scopes'] = $json['scopes'];
            } elseif (isset($json['data']['scopes']) && is_array($json['data']['scopes'])) {
                $result['api_call']['scopes'] = $json['data']['scopes'];
            } elseif (isset($json['data']['account']['scopes']) && is_array($json['data']['account']['scopes'])) {
                $result['api_call']['scopes'] = $json['data']['account']['scopes'];
            } elseif (isset($json['data']['permissions']) && is_array($json['data']['permissions'])) {
                $result['api_call']['scopes'] = $json['data']['permissions'];
            }

            if (in_array('messages:send', $result['api_call']['scopes']) || in_array('*', $result['api_call']['scopes']) || in_array('messages.*', $result['api_call']['scopes'])) {
                $result['api_call']['has_messages_send_scope'] = true;
            }
        } else {
            $result['api_call']['response_text_sanitized'] = substr(strip_tags($response), 0, 200);
        }
    }
}

// Print results in sanitized format
echo json_encode($result, JSON_PRETTY_PRINT);
