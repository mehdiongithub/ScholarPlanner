<?php

namespace App\Services\WhatsApp;

use Exception;

class WacrmWhatsAppProvider implements WhatsAppProviderInterface {
    private string $baseUrl;
    private string $apiKey;
    private int $timeout;
    private array $templates;
    private bool $testMode;
    private string $testRecipient;
    private string $testTemplateNewMatch;
    private array $templateStatus;

    public function __construct() {
        $config = require ROOT_PATH . '/config/whatsapp.php';
        $wacrmConfig = $config['wacrm'] ?? [];
        $this->baseUrl = $wacrmConfig['base_url'] ?? '';
        $this->apiKey = $wacrmConfig['api_key'] ?? '';
        $this->timeout = $wacrmConfig['timeout'] ?? 15;
        $this->templates = $wacrmConfig['templates'] ?? [];
        $this->testMode = filter_var($_ENV['WHATSAPP_TEST_MODE'] ?? ($config['test_mode'] ?? false), FILTER_VALIDATE_BOOLEAN);
        $this->testRecipient = $_ENV['WHATSAPP_TEST_RECIPIENT'] ?? ($config['test_recipient'] ?? '+923251371826');
        $this->testTemplateNewMatch = $_ENV['WHATSAPP_TEST_TEMPLATE_NEW_MATCH'] ?? ($config['test_template_new_match'] ?? 'new_match_v2');
        $this->templateStatus = $config['template_status'] ?? [];
    }


    /**
     * Determine if an IP address is a safe, routable, public IP.
     * Rejects private, loopback, link-local, reserved, multicast, and unspecified IPv4/IPv6.
     */
    public static function isSafeIp(string $ip): bool {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        // Native PHP filter check for private and reserved ranges
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return false;
        }

        // Strict IPv4 subnet checks
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $long = ip2long($ip);
            if ($long === false) return false;

            // 0.0.0.0/8 (Current network)
            if (($long & 0xFF000000) === 0x00000000) return false;
            // 127.0.0.0/8 (Loopback)
            if (($long & 0xFF000000) === 0x7F000000) return false;
            // 10.0.0.0/8 (Private)
            if (($long & 0xFF000000) === 0x0A000000) return false;
            // 172.16.0.0/12 (Private)
            if (($long & 0xFFF00000) === 0xAC100000) return false;
            // 192.168.0.0/16 (Private)
            if (($long & 0xFFFF0000) === 0xC0A80000) return false;
            // 169.254.0.0/16 (Link-local)
            if (($long & 0xFFFF0000) === 0xA9FE0000) return false;
            // 100.64.0.0/10 (Carrier-grade NAT)
            if (($long & 0xFFC00000) === 0x64400000) return false;
            // 198.18.0.0/15 (Benchmarking)
            if (($long & 0xFFFE0000) === 0xC6120000) return false;
            // 224.0.0.0/4 (Multicast) and 240.0.0.0/4 (Reserved)
            if ((($long >> 28) & 0x0F) >= 14) return false;

            return true;
        }

        // Strict IPv6 subnet checks
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $bin = inet_pton($ip);
            if ($bin === false) return false;

            // :: (unspecified)
            if ($bin === str_repeat("\0", 16)) return false;
            // ::1 (loopback)
            if ($bin === (str_repeat("\0", 15) . "\1")) return false;

            $b0 = ord($bin[0]);
            $b1 = ord($bin[1]);

            // fe80::/10 (link-local)
            if ($b0 === 0xfe && ($b1 & 0xc0) === 0x80) return false;
            // fc00::/7 (unique local / private)
            if (($b0 & 0xfe) === 0xfc) return false;
            // fec0::/10 (site-local)
            if ($b0 === 0xfe && ($b1 & 0xc0) === 0xc0) return false;
            // ff00::/8 (multicast)
            if ($b0 === 0xff) return false;

            // ::ffff:0:0/96 (IPv4-mapped IPv6)
            if (substr($bin, 0, 10) === str_repeat("\0", 10) && substr($bin, 10, 2) === "\xff\xff") {
                $mappedIpv4 = inet_ntop(substr($bin, 12, 4));
                return self::isSafeIp($mappedIpv4);
            }

            // 64:ff9b::/96 (IPv4/IPv6 translation)
            if (substr($bin, 0, 4) === "\x00\x64\xff\x9b") {
                $mappedIpv4 = inet_ntop(substr($bin, 12, 4));
                return self::isSafeIp($mappedIpv4);
            }

            // 2001:db8::/32 (documentation)
            if (substr($bin, 0, 4) === "\x20\x01\x0d\xb8") return false;

            return true;
        }

        return false;
    }

    /**
     * Resolve and validate a WACRM URL for production SSRF protection and DNS rebinding mitigation.
     *
     * @param string $url The endpoint URL
     * @param bool $strictProduction If true, enforces production HTTPS & DNS checks regardless of test mode
     * @param callable|null $dnsResolver Optional resolver callback for behavioral testing: fn(string $host): array
     * @return array Structure: ['safe' => bool, 'pinned_ip' => ?string, 'port' => int, 'host' => string, 'error' => ?string]
     */
    public static function resolveAndValidate(string $url, bool $strictProduction = false, ?callable $dnsResolver = null): array {
        $defaultPort = 443;
        if (empty($url)) {
            return ['safe' => false, 'pinned_ip' => null, 'port' => $defaultPort, 'host' => '', 'error' => 'URL is empty.'];
        }

        $parts = parse_url($url);
        if (!$parts || empty($parts['scheme']) || empty($parts['host'])) {
            return ['safe' => false, 'pinned_ip' => null, 'port' => $defaultPort, 'host' => '', 'error' => 'Malformed URL structure.'];
        }

        $scheme = strtolower($parts['scheme']);
        $port = (int)($parts['port'] ?? ($scheme === 'http' ? 80 : 443));
        $isTesting = defined('TESTING_MODE') && TESTING_MODE && !$strictProduction;

        // In production, scheme MUST strictly be https://
        if (!$isTesting && $scheme !== 'https') {
            return ['safe' => false, 'pinned_ip' => null, 'port' => $port, 'host' => $parts['host'], 'error' => 'Production WACRM URL must use HTTPS.'];
        }

        if (!in_array($scheme, ['http', 'https'], true)) {
            return ['safe' => false, 'pinned_ip' => null, 'port' => $port, 'host' => $parts['host'], 'error' => 'Scheme must be http or https.'];
        }

        $rawHost = strtolower(trim($parts['host'], '[]'));

        // Reject colon or malformed IPv6 host string
        if ($rawHost === '' || $rawHost === ':' || (strpos($rawHost, ':') !== false && !filter_var($rawHost, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6))) {
            return ['safe' => false, 'pinned_ip' => null, 'port' => $port, 'host' => $rawHost, 'error' => 'Invalid hostname format.'];
        }

        // Reject explicit localhost or loopback strings
        if ($rawHost === 'localhost' || $rawHost === '127.0.0.1' || $rawHost === '::1' || $rawHost === '0.0.0.0') {
            if ($isTesting) {
                return ['safe' => true, 'pinned_ip' => $rawHost, 'port' => $port, 'host' => $rawHost, 'error' => null];
            }
            return ['safe' => false, 'pinned_ip' => null, 'port' => $port, 'host' => $rawHost, 'error' => 'Localhost and loopback addresses are prohibited.'];
        }

        // Direct IP address provided in URL
        if (filter_var($rawHost, FILTER_VALIDATE_IP)) {
            $isSafe = self::isSafeIp($rawHost);
            if (!$isSafe) {
                if ($isTesting) {
                    return ['safe' => true, 'pinned_ip' => $rawHost, 'port' => $port, 'host' => $rawHost, 'error' => null];
                }
                return ['safe' => false, 'pinned_ip' => null, 'port' => $port, 'host' => $rawHost, 'error' => 'Direct private or reserved IP addresses are prohibited.'];
            }
            return ['safe' => true, 'pinned_ip' => $rawHost, 'port' => $port, 'host' => $rawHost, 'error' => null];
        }

        // Hostname syntax check
        if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $rawHost)) {
            return ['safe' => false, 'pinned_ip' => null, 'port' => $port, 'host' => $rawHost, 'error' => 'Invalid characters in hostname.'];
        }

        // In testing mode, immediately bypass DNS query timeouts for mock hostnames
        if ($isTesting && $dnsResolver === null && (str_ends_with($rawHost, '.local') || str_ends_with($rawHost, '.test') || str_ends_with($rawHost, '.example.com') || str_starts_with($rawHost, 'mock-') || $rawHost === 'mock.local')) {
            return ['safe' => true, 'pinned_ip' => null, 'port' => $port, 'host' => $rawHost, 'error' => null];
        }

        // Resolve DNS records (IPv4 A and IPv6 AAAA)
        if ($dnsResolver !== null) {
            $ips = (array)$dnsResolver($rawHost);
        } else {
            $ips = [];
            $records = @dns_get_record($rawHost, DNS_A + DNS_AAAA);
            if (is_array($records)) {
                foreach ($records as $r) {
                    if ($r['type'] === 'A' && !empty($r['ip'])) {
                        $ips[] = $r['ip'];
                    } elseif ($r['type'] === 'AAAA' && !empty($r['ipv6'])) {
                        $ips[] = $r['ipv6'];
                    }
                }
            }
            if (empty($ips)) {
                $v4s = @gethostbynamel($rawHost);
                if (is_array($v4s)) {
                    $ips = array_merge($ips, $v4s);
                }
            }
        }

        // If resolution yielded no records
        if (empty($ips)) {
            if ($isTesting) {
                // In test mode, allow mock hostnames if not strict
                return ['safe' => true, 'pinned_ip' => null, 'port' => $port, 'host' => $rawHost, 'error' => null];
            }
            return ['safe' => false, 'pinned_ip' => null, 'port' => $port, 'host' => $rawHost, 'error' => 'DNS resolution failed. Fail closed in production.'];
        }

        // ALL resolved addresses must be safe. If ANY resolved IP is private/unsafe, REJECT!
        foreach ($ips as $resolvedIp) {
            if (!self::isSafeIp($resolvedIp)) {
                if ($isTesting) {
                    return ['safe' => true, 'pinned_ip' => null, 'port' => $port, 'host' => $rawHost, 'error' => null];
                }
                return [
                    'safe' => false,
                    'pinned_ip' => null,
                    'port' => $port,
                    'host' => $rawHost,
                    'error' => "Hostname resolves to private/reserved IP: $resolvedIp."
                ];
            }
        }

        return [
            'safe' => true,
            'pinned_ip' => $ips[0] ?? null,
            'port' => $port,
            'host' => $rawHost,
            'error' => null
        ];
    }

    /**
     * Anti-SSRF URL validation helper.
     */
    public static function isSafeUrl(string $url, bool $strictProduction = false, ?callable $dnsResolver = null): bool {
        return self::resolveAndValidate($url, $strictProduction, $dnsResolver)['safe'];
    }

    /**
     * Normalize and validate phone numbers to E.164 format.
     * Handles local Pakistan numbers (03xx -> +923xx), international 00xx -> +xx,
     * duplicate country-code/trunk formatting (+9203xx -> +923xx), and strict E.164.
     */
    public static function normalizePhoneNumber(string $phone): ?string {
        $trimmed = trim($phone);
        if ($trimmed === '') {
            return null;
        }

        // 1. Remove all non-digit and non-plus characters
        $cleaned = preg_replace('/[^\+0-9]/', '', $trimmed);
        if ($cleaned === '' || $cleaned === '+') {
            return null;
        }

        // 2. Convert leading international prefix '00' to '+'
        if (str_starts_with($cleaned, '00')) {
            $cleaned = '+' . substr($cleaned, 2);
        }

        // 3. Handle local Pakistan numbers: '03001234567' (11 digits starting with 03) -> '+923001234567'
        if (preg_match('/^0(3[0-9]{9})$/', $cleaned, $m)) {
            $cleaned = '+92' . $m[1];
        }

        // 4. Handle duplicate formatting: '+9203001234567' or '9203001234567' (extra 0 after 92)
        if (preg_match('/^\+?920(3[0-9]{9})$/', $cleaned, $m)) {
            $cleaned = '+92' . $m[1];
        }

        // 5. If missing leading '+', add it if digits start with valid country code
        if (!str_starts_with($cleaned, '+')) {
            $cleaned = '+' . $cleaned;
        }

        // 6. Strict E.164 validation: '+' followed by 10 to 15 digits (first digit after '+' cannot be 0)
        if (preg_match('/^\+[1-9][0-9]{9,14}$/', $cleaned)) {
            return $cleaned;
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

        $validation = self::resolveAndValidate($this->baseUrl);
        if (!$validation['safe']) {
            return [
                'success' => false,
                'message_id' => null,
                'error' => 'Invalid or unsafe WACRM base URL: ' . ($validation['error'] ?? 'SSRF/DNS validation failed.')
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
            'new_scholarship_match' => $this->templates['new_match'] ?? 'new_match_v2',
            'new_match' => $this->templates['new_match'] ?? 'new_match_v2',
            'daily_match_digest' => $this->templates['new_match'] ?? 'new_match_v2',
            'weekly_match_digest' => $this->templates['new_match'] ?? 'new_match_v2',
            'deadline_reminder_soon' => $this->templates['deadline_soon'] ?? 'deadline_soon',
            'deadline_soon' => $this->templates['deadline_soon'] ?? 'deadline_soon',
            'deadline_reminder_today' => $this->templates['deadline_today'] ?? 'deadline_today',
            'deadline_today' => $this->templates['deadline_today'] ?? 'deadline_today',
        ];
        if ($this->testMode) {
            $nameMap['new_scholarship_match'] = $this->testTemplateNewMatch;
            $nameMap['new_match'] = $this->testTemplateNewMatch;
            $nameMap['daily_match_digest'] = $this->testTemplateNewMatch;
            $nameMap['weekly_match_digest'] = $this->testTemplateNewMatch;
        }
        $mappedTemplateName = $nameMap[$templateName] ?? $templateName;


        // In-Review Meta Template Gate: Do not call Meta/WACRM if template is still in review
        $envKey = 'META_TEMPLATE_STATUS_' . strtoupper($mappedTemplateName);
        $rawStatus = $_ENV[$envKey] ?? ($this->templateStatus[$mappedTemplateName] ?? ($this->templateStatus[$templateName] ?? 'ACTIVE'));
        if (strtoupper($rawStatus) === 'IN_REVIEW' && !defined('BYPASS_TEMPLATE_REVIEW_GATE')) {
            return [
                'success' => false,
                'message_id' => null,
                'error' => "META_TEMPLATE_IN_REVIEW: Template '{$mappedTemplateName}' is currently under Meta review.",
                'held' => true,
                'status' => 'held'
            ];
        }


        // Test mode recipient guardrail (ensures live test mode only dispatches to authorized recipient)
        if ($this->testMode && $normalizedPhone !== self::normalizePhoneNumber($this->testRecipient) && !defined('BYPASS_TEST_RECIPIENT_GATE')) {
            return [
                'success' => false,
                'message_id' => null,
                'error' => "TEST_MODE_RECIPIENT_BLOCKED: Test mode active. Dispatch restricted to authorized test recipient.",
                'held' => true,
                'status' => 'held'
            ];
        }


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

        // Pin validated IP to mitigate DNS rebinding / TOCTOU attacks
        if (!empty($validation['pinned_ip']) && !empty($validation['host']) && filter_var($validation['pinned_ip'], FILTER_VALIDATE_IP)) {
            $pinnedEntry = sprintf("%s:%d:%s", $validation['host'], $validation['port'], $validation['pinned_ip']);
            curl_setopt($ch, CURLOPT_RESOLVE, [$pinnedEntry]);
        }

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

        if ($response === false || $response === null || $response === '') {
            $resData = null;
        } else {
            $resData = json_decode((string)$response, true);
        }
        if ($resData === null && json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'message_id' => null,
                'error' => 'Invalid JSON response from WACRM API.',
                'retry_after' => null
            ];
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            // WACRM success response structure returns { "data": { "message_id": "...", "whatsapp_message_id": "..." } }
            if (isset($resData['data']['message_id'])) {
                return [
                    'success' => true,
                    'message_id' => $resData['data']['message_id'],
                    'whatsapp_message_id' => $resData['data']['whatsapp_message_id'] ?? null,
                    'conversation_id' => $resData['data']['conversation_id'] ?? null,
                    'contact_id' => $resData['data']['contact_id'] ?? null,
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
            'whatsapp_message_id' => null,
            'error' => $this->redactSecrets($mappedError),
            'retry_after' => $retryAfter
        ];
    }

    /**
     * Send direct text WhatsApp message via WACRM API.
     */
    public function sendTextMessage(string $recipient, string $text): array {
        if (empty($this->baseUrl) || empty($this->apiKey)) {
            return [
                'success' => false,
                'message_id' => null,
                'whatsapp_message_id' => null,
                'error' => 'WACRM configuration is incomplete (missing base URL or API key).'
            ];
        }

        $validation = self::resolveAndValidate($this->baseUrl);
        if (!$validation['safe']) {
            return [
                'success' => false,
                'message_id' => null,
                'whatsapp_message_id' => null,
                'error' => 'Invalid or unsafe WACRM base URL: ' . ($validation['error'] ?? 'SSRF/DNS validation failed.')
            ];
        }

        // Validate recipient number
        $normalizedPhone = self::normalizePhoneNumber($recipient);
        if ($normalizedPhone === null) {
            return [
                'success' => false,
                'message_id' => null,
                'whatsapp_message_id' => null,
                'error' => 'Invalid recipient phone number format. Must be in E.164 format.'
            ];
        }

        // WACRM Text Payload
        $payload = [
            'to' => $normalizedPhone,
            'type' => 'text',
            'text' => $text
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

        // Pin validated IP to mitigate DNS rebinding / TOCTOU attacks
        if (!empty($validation['pinned_ip']) && !empty($validation['host']) && filter_var($validation['pinned_ip'], FILTER_VALIDATE_IP)) {
            $pinnedEntry = sprintf("%s:%d:%s", $validation['host'], $validation['port'], $validation['pinned_ip']);
            curl_setopt($ch, CURLOPT_RESOLVE, [$pinnedEntry]);
        }

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
                'whatsapp_message_id' => null,
                'error' => $this->redactSecrets('cURL error: ' . $curlError),
                'retry_after' => null
            ];
        }

        if ($response === false || $response === null || $response === '') {
            $resData = null;
        } else {
            $resData = json_decode((string)$response, true);
        }
        if ($resData === null && json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'message_id' => null,
                'whatsapp_message_id' => null,
                'error' => 'Invalid JSON response from WACRM API.',
                'retry_after' => null
            ];
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            if (isset($resData['data']['message_id'])) {
                return [
                    'success' => true,
                    'message_id' => $resData['data']['message_id'],
                    'whatsapp_message_id' => $resData['data']['whatsapp_message_id'] ?? null,
                    'conversation_id' => $resData['data']['conversation_id'] ?? null,
                    'contact_id' => $resData['data']['contact_id'] ?? null,
                    'error' => null
                ];
            }
        }

        $wacrmError = $resData['error']['message'] ?? 'Unknown WACRM API error';
        $errorCode = $resData['error']['code'] ?? 'internal';

        return [
            'success' => false,
            'message_id' => null,
            'whatsapp_message_id' => null,
            'error' => $this->redactSecrets("WACRM Error: HTTP $httpCode ($errorCode) - $wacrmError"),
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

        $validation = self::resolveAndValidate($this->baseUrl);
        if (!$validation['safe']) {
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

        if (!empty($validation['pinned_ip']) && !empty($validation['host']) && filter_var($validation['pinned_ip'], FILTER_VALIDATE_IP)) {
            $pinnedEntry = sprintf("%s:%d:%s", $validation['host'], $validation['port'], $validation['pinned_ip']);
            curl_setopt($ch, CURLOPT_RESOLVE, [$pinnedEntry]);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && !empty($response)) {
            $data = json_decode((string)$response, true);
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
        if (!empty($this->apiKey)) {
            $escapedKey = preg_quote($this->apiKey, '/');
            $message = preg_replace('/Bearer\s+' . $escapedKey . '/i', 'Bearer [REDACTED]', $message);
            $message = preg_replace('/' . $escapedKey . '/i', '[REDACTED]', $message);
        }
        
        // General backup auth token header matches
        $patterns = [
            '/(password|pass|secret|token|key|auth|easypaisa|jazzcash)=[^&\s\n]+/i' => '$1=[REDACTED]',
            '/(Authorization|Bearer)\s*:?\s*[a-zA-Z0-9_\-\.]+/i' => '$1 [REDACTED]'
        ];
        
        return (string)preg_replace(array_keys($patterns), array_values($patterns), $message);
    }
}
