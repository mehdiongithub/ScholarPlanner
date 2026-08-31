<?php

namespace App\Services\WhatsApp;

use Exception;

class WacrmWhatsAppProvider implements WhatsAppProviderInterface {
    private string $baseUrl;
    private string $apiKey;
    private int $timeout;
    private array $templates;

    public function __construct() {
        $config = require ROOT_PATH . '/config/whatsapp.php';
        $wacrmConfig = $config['wacrm'] ?? [];
        $this->baseUrl = $wacrmConfig['base_url'] ?? '';
        $this->apiKey = $wacrmConfig['api_key'] ?? '';
        $this->timeout = $wacrmConfig['timeout'] ?? 15;
        $this->templates = $wacrmConfig['templates'] ?? [];
    }

    /**
     * Normalize and validate phone numbers to E.164 format.
     */
    public static function normalizePhoneNumber(string $phone): ?string {
        $cleaned = preg_replace('/[^\+0-9]/', '', $phone);
        
        if (preg_match('/^\+[0-9]{10,15}$/', $cleaned)) {
            return $cleaned;
        }
        
        if (preg_match('/^[0-9]{10,15}$/', $cleaned)) {
            return '+' . $cleaned;
        }
        
        // Handle without leading symbols but containing E.164 digits
        $digitsOnly = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($digitsOnly) >= 10 && strlen($digitsOnly) <= 15) {
            return '+' . $digitsOnly;
        }
        
        return null;
    }

    /**
     * Send template-based WhatsApp message via WACRM API.
     */
    public function sendTemplateMessage(string $recipient, string $templateName, array $parameters): array {
        if (empty($this->baseUrl) || empty($this->apiKey)) {
            return [
                'success' => false,
                'message_id' => null,
                'error' => 'WACRM configuration is incomplete (missing base URL or API key).'
            ];
        }

        // Validate recipient number
        $normalizedPhone = self::normalizePhoneNumber($recipient);
        if ($normalizedPhone === null) {
            return [
                'success' => false,
                'message_id' => null,
                'error' => 'Invalid recipient phone number format. Must be in E.164 format.'
            ];
        }

        // Map template name
        $nameMap = [
            'new_scholarship_match' => $this->templates['new_match'] ?? 'new_match',
            'deadline_reminder_soon' => $this->templates['deadline_soon'] ?? 'deadline_soon',
            'deadline_reminder_today' => $this->templates['deadline_today'] ?? 'deadline_today',
        ];
        $mappedTemplateName = $nameMap[$templateName] ?? $templateName;

        // WACRM Request Payload
        $payload = [
            'to' => $normalizedPhone,
            'type' => 'template',
            'template' => [
                'name' => $mappedTemplateName,
                'language' => 'en_US',
                'params' => $parameters
            ]
        ];

        $url = rtrim($this->baseUrl, '/') . '/api/v1/messages';
        $retryAfter = null;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);

        // Capture headers for Rate Limiter (Retry-After)
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
                'error' => $this->redactSecrets('cURL error: ' . $curlError),
                'retry_after' => null
            ];
        }

        $resData = json_decode($response, true);
        if ($resData === null && json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'message_id' => null,
                'error' => 'Invalid JSON response from WACRM API.',
                'retry_after' => null
            ];
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            // WACRM success response structure returns { "data": { "message_id": "..." } }
            if (isset($resData['data']['message_id'])) {
                return [
                    'success' => true,
                    'message_id' => $resData['data']['message_id'],
                    'error' => null
                ];
            }
        }

        // Map status codes to specific readable error categories
        $wacrmError = $resData['error']['message'] ?? 'Unknown WACRM API error';
        $errorCode = $resData['error']['code'] ?? 'internal';

        $mappedError = "WACRM Error: HTTP $httpCode ($errorCode) - $wacrmError";
        
        return [
            'success' => false,
            'message_id' => null,
            'error' => $this->redactSecrets($mappedError),
            'retry_after' => $retryAfter
        ];
    }

    /**
     * Server-side WACRM connection verification test.
     */
    public function testConnection(): string {
        if (empty($this->baseUrl) || empty($this->apiKey)) {
            return 'NOT CONNECTED';
        }

        $url = rtrim($this->baseUrl, '/') . '/api/v1/me';
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $data = json_decode($response, true);
            if (isset($data['data']['account']['id'])) {
                return 'CONNECTED';
            }
        }

        return 'NOT CONNECTED';
    }

    /**
     * Redacts API keys or bearer token secrets from strings/exceptions.
     */
    private function redactSecrets(string $message): string {
        if (empty($this->apiKey)) {
            return $message;
        }
        
        $escapedKey = preg_quote($this->apiKey, '/');
        $message = preg_replace('/Bearer\s+' . $escapedKey . '/i', 'Bearer [REDACTED]', $message);
        $message = preg_replace('/' . $escapedKey . '/i', '[REDACTED]', $message);
        
        // General backup auth token header matches
        $patterns = [
            '/(password|pass|secret|token|key|auth|easypaisa|jazzcash)=[^&\s\n]+/i' => '$1=[REDACTED]',
            '/(Authorization|Bearer)\s*:?\s*[a-zA-Z0-9_\-\.]+/i' => '$1 [REDACTED]'
        ];
        
        return preg_replace(array_keys($patterns), array_values($patterns), $message);
    }
}
