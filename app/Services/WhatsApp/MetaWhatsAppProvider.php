<?php

namespace App\Services\WhatsApp;

class MetaWhatsAppProvider implements WhatsAppProviderInterface {
    private string $apiUrl;
    private string $accessToken;
    private string $phoneNumberId;

    public function __construct() {
        $config = require ROOT_PATH . '/config/whatsapp.php';
        $this->apiUrl = $config['api_url'] ?? '';
        $this->accessToken = $config['access_token'] ?? '';
        $this->phoneNumberId = $config['phone_number_id'] ?? '';
    }

    public function sendTemplateMessage(string $recipient, string $templateName, array $parameters): array {
        $config = require ROOT_PATH . '/config/whatsapp.php';
        $templateStatus = $config['template_status'] ?? [];
        $envKey = 'META_TEMPLATE_STATUS_' . strtoupper($templateName);
        $rawStatus = $_ENV[$envKey] ?? ($templateStatus[$templateName] ?? 'ACTIVE');

        // In-Review Meta Template Gate: Do not call Meta Cloud API if template is still in review
        if (strtoupper($rawStatus) === 'IN_REVIEW' && !defined('BYPASS_TEMPLATE_REVIEW_GATE')) {
            return [
                'success' => false,
                'message_id' => null,
                'error' => "META_TEMPLATE_IN_REVIEW: Template '{$templateName}' is currently under Meta review.",
                'held' => true,
                'status' => 'held'
            ];
        }


        // Format body text parameters
        $formattedParams = [];

        foreach ($parameters as $param) {
            $formattedParams[] = [
                'type' => 'text',
                'text' => (string)$param
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $recipient,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => [
                    'code' => 'en_US'
                ],
                'components' => [
                    [
                        'type' => 'body',
                        'parameters' => $formattedParams
                    ]
                ]
            ]
        ];

        if (empty($this->apiUrl) || empty($this->phoneNumberId)) {
            return [
                'success' => false,
                'message_id' => null,
                'error' => 'Meta WhatsApp configuration is incomplete (missing API URL or Phone Number ID).'
            ];
        }

        $url = rtrim($this->apiUrl, '/') . '/' . $this->phoneNumberId . '/messages';

        $retryAfter = null;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($curl, $header) use (&$retryAfter) {
            $len = strlen($header);
            $parts = explode(':', $header, 2);
            if (count($parts) === 2 && strtolower(trim($parts[0])) === 'retry-after') {
                $retryAfter = (int)trim($parts[1]);
            }
            return $len;
        });

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return [
                'success' => false,
                'message_id' => null,
                'error' => $this->redactError('cURL Error: ' . $curlError),
                'retry_after' => null
            ];
        }

        if ($response === false || $response === null || $response === '') {
            $resData = null;
        } else {
            $resData = json_decode((string)$response, true);
        }

        if ($httpCode >= 200 && $httpCode < 300 && isset($resData['messages'][0]['id'])) {
            return [
                'success' => true,
                'message_id' => $resData['messages'][0]['id'],
                'error' => null
            ];
        }

        $errorMsg = $resData['error']['message'] ?? 'Unknown Meta API Error';
        return [
            'success' => false,
            'message_id' => null,
            'error' => $this->redactError("HTTP $httpCode: $errorMsg"),
            'retry_after' => $retryAfter
        ];
    }

    public function sendTextMessage(string $recipient, string $text): array {
        if (empty($this->apiUrl) || empty($this->phoneNumberId)) {
            return [
                'success' => false,
                'message_id' => null,
                'error' => 'Meta WhatsApp configuration is incomplete (missing API URL or Phone Number ID).'
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $recipient,
            'type' => 'text',
            'text' => [
                'body' => $text
            ]
        ];

        $url = rtrim($this->apiUrl, '/') . '/' . $this->phoneNumberId . '/messages';
        $retryAfter = null;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($curl, $header) use (&$retryAfter) {
            $len = strlen($header);
            $parts = explode(':', $header, 2);
            if (count($parts) === 2 && strtolower(trim($parts[0])) === 'retry-after') {
                $retryAfter = (int)trim($parts[1]);
            }
            return $len;
        });

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return [
                'success' => false,
                'message_id' => null,
                'error' => $this->redactError('cURL Error: ' . $curlError),
                'retry_after' => null
            ];
        }

        if ($response === false || $response === null || $response === '') {
            $resData = null;
        } else {
            $resData = json_decode((string)$response, true);
        }

        if ($httpCode >= 200 && $httpCode < 300 && isset($resData['messages'][0]['id'])) {
            return [
                'success' => true,
                'message_id' => $resData['messages'][0]['id'],
                'error' => null
            ];
        }

        $errorMsg = $resData['error']['message'] ?? 'Unknown Meta API Error';
        return [
            'success' => false,
            'message_id' => null,
            'error' => $this->redactError("HTTP $httpCode: $errorMsg"),
            'retry_after' => $retryAfter
        ];
    }

    private function redactError(string $err): string {
        $patterns = [
            '/(password|pass|secret|token|key|auth|easypaisa|jazzcash)=[^&\s\n]+/i' => '$1=[REDACTED]',
            '/(Authorization|Bearer)\s*:?\s*[a-zA-Z0-9_\-\.]+/i' => '$1 [REDACTED]',
            '/[a-zA-Z0-9+\/]{40,}/' => '[REDACTED_BASE64_STRING]'
        ];
        return preg_replace(array_keys($patterns), array_values($patterns), $err);
    }
}

