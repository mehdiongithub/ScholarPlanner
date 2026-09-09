<?php

namespace App\Services\WhatsApp {
    class CurlMockRegistry {
        public static $response = null;
        public static $httpCode = 200;
        public static $error = '';
        public static $headersCaptured = [];
        public static $calledUrl = '';
        public static $calledPostFields = null;
        public static $calledHeaders = [];
        
        public static function reset() {
            self::$response = null;
            self::$httpCode = 200;
            self::$error = '';
            self::$headersCaptured = [];
            self::$calledUrl = '';
            self::$calledPostFields = null;
            self::$calledHeaders = [];
        }
    }

    // Check if the functions are already defined to prevent collision on multiple loads
    if (!function_exists('App\Services\WhatsApp\curl_init')) {
        function curl_init($url = null) {
            CurlMockRegistry::$calledUrl = $url;
            return 'mock_curl_handle';
        }

        function curl_setopt($ch, $option, $value) {
            if ($option === CURLOPT_POSTFIELDS) {
                CurlMockRegistry::$calledPostFields = $value;
            }
            if ($option === CURLOPT_HTTPHEADER) {
                CurlMockRegistry::$calledHeaders = $value;
            }
            if ($option === CURLOPT_HEADERFUNCTION) {
                foreach (CurlMockRegistry::$headersCaptured as $hdr) {
                    $value($ch, $hdr);
                }
            }
        }

        function curl_exec($ch) {
            return CurlMockRegistry::$response;
        }

        function curl_getinfo($ch, $opt) {
            if ($opt === CURLINFO_HTTP_CODE) {
                return CurlMockRegistry::$httpCode;
            }
            return 0;
        }

        function curl_error($ch) {
            return CurlMockRegistry::$error;
        }

        function curl_close($ch) {
            // no-op
        }
    }
}

namespace {
    if (!defined('TESTING_MODE')) {
        define('TESTING_MODE', true);
    }

    use App\Services\Database;
    use App\Services\Auth;
    use App\Services\WhatsApp\WacrmWhatsAppProvider;
    use App\Services\WhatsApp\CurlMockRegistry;
    use App\Controllers\AdminController;

    class WacrmWhatsAppProviderTest {
        private PDO $db;

        public function __construct() {
            $this->db = Database::connection();
        }

        public function run(): void {
            echo "--- Running WacrmWhatsAppProviderTest ---\n";

            $this->testProviderConfigurationLoading();
            $this->testMissingConfigurationValidation();
            $this->testSuccessfulSend();
            $this->testHttpErrorCodesHandling();
            $this->testRateLimitCapture();
            $this->testCurlErrorsAndCredentialsRedaction();
            $this->testMalformedJsonResponseHandling();
            $this->testPhoneNumberValidation();
            $this->testTemplateNameMapping();
            $this->testDirectTextMessageSending();
            $this->testAdminConnectionTestAccessControl();

            echo "WacrmWhatsAppProviderTest PASSED.\n\n";
        }

        private function testProviderConfigurationLoading(): void {
            $provider = new WacrmWhatsAppProvider();
            
            $reflector = new ReflectionClass($provider);
            $timeoutProp = $reflector->getProperty('timeout');
            $timeoutProp->setAccessible(true);
            
            $timeoutVal = $timeoutProp->getValue($provider);
            if ($timeoutVal !== 15 && $timeoutVal !== 10) {
                // Ensure timeout loads properly (15 is default)
                if (!is_int($timeoutVal)) {
                    throw new Exception("Provider failed to load config timeout weight.");
                }
            }

            $templatesProp = $reflector->getProperty('templates');
            $templatesProp->setAccessible(true);
            $templatesVal = $templatesProp->getValue($provider);

            if (!is_array($templatesVal) || !isset($templatesVal['new_match'])) {
                throw new Exception("Provider templates config array missing key structure.");
            }

            echo "✔ Configuration variables successfully loaded.\n";
        }

        private function testMissingConfigurationValidation(): void {
            $provider = new WacrmWhatsAppProvider();
            
            $reflector = new ReflectionClass($provider);
            $urlProp = $reflector->getProperty('baseUrl');
            $urlProp->setAccessible(true);
            $keyProp = $reflector->getProperty('apiKey');
            $keyProp->setAccessible(true);

            // Save old config values
            $oldUrl = $urlProp->getValue($provider);
            $oldKey = $keyProp->getValue($provider);

            // Test missing URL
            $urlProp->setValue($provider, '');
            $resUrl = $provider->sendTemplateMessage('+923001234567', 'new_scholarship_match', ['SchTitle']);
            if ($resUrl['success'] !== false || strpos($resUrl['error'], 'missing base URL') === false) {
                throw new Exception("Failed to block message send when base URL is missing.");
            }

            // Test missing Key
            $urlProp->setValue($provider, 'https://test-crm.example.com');
            $keyProp->setValue($provider, '');
            $resKey = $provider->sendTemplateMessage('+923001234567', 'new_scholarship_match', ['SchTitle']);
            if ($resKey['success'] !== false || strpos($resKey['error'], 'missing base URL or API key') === false) {
                throw new Exception("Failed to block message send when API key is missing.");
            }

            // Restore config values
            $urlProp->setValue($provider, $oldUrl);
            $keyProp->setValue($provider, $oldKey);

            echo "✔ Missing configuration validations verified.\n";
        }

        private function testSuccessfulSend(): void {
            CurlMockRegistry::reset();
            CurlMockRegistry::$response = json_encode([
                'data' => [
                    'message_id' => 'msg_wacrm_test_12345',
                    'whatsapp_message_id' => 'wamid.HBgLOTEzMDA...'
                ]
            ]);
            CurlMockRegistry::$httpCode = 201;

            $provider = new WacrmWhatsAppProvider();
            
            // Set mock credentials to ensure cURL executes
            $reflector = new ReflectionClass($provider);
            $urlProp = $reflector->getProperty('baseUrl');
            $urlProp->setAccessible(true);
            $keyProp = $reflector->getProperty('apiKey');
            $keyProp->setAccessible(true);

            $oldUrl = $urlProp->getValue($provider);
            $oldKey = $keyProp->getValue($provider);

            $urlProp->setValue($provider, 'https://mock-wacrm.local');
            $keyProp->setValue($provider, 'wacrm_live_secret_key_123');

            $res = $provider->sendTemplateMessage('+923001234567', 'new_scholarship_match', ['SchTitle']);

            if (!$res['success']) {
                throw new Exception("Provider failed to process a successful response: " . ($res['error'] ?? ''));
            }

            if ($res['message_id'] !== 'msg_wacrm_test_12345') {
                throw new Exception("Unexpected message ID returned: " . $res['message_id']);
            }

            // Verify payload structure sent to WACRM
            $postFields = json_decode(CurlMockRegistry::$calledPostFields, true);
            if ($postFields['to'] !== '+923001234567' || ($postFields['template']['name'] !== 'new_match' && $postFields['template']['name'] !== 'new_match_v2')) {
                throw new Exception("Incorrect payload format submitted to cURL handler.");
            }

            // Verify Bearer Auth Header
            $authHeaderFound = false;
            foreach (CurlMockRegistry::$calledHeaders as $hdr) {
                if (strpos($hdr, 'Authorization: Bearer wacrm_live_secret_key_123') !== false) {
                    $authHeaderFound = true;
                    break;
                }
            }
            if (!$authHeaderFound) {
                throw new Exception("Correct WACRM Authorization header not sent via cURL.");
            }

            // Restore config values
            $urlProp->setValue($provider, $oldUrl);
            $keyProp->setValue($provider, $oldKey);

            echo "✔ Successful payload sending and Bearer token headers verified.\n";
        }

        private function testHttpErrorCodesHandling(): void {
            $errorScenarios = [
                400 => 'bad_request',
                401 => 'unauthorized',
                403 => 'forbidden',
                404 => 'not_found',
                500 => 'internal'
            ];

            $provider = new WacrmWhatsAppProvider();
            $reflector = new ReflectionClass($provider);
            $urlProp = $reflector->getProperty('baseUrl');
            $urlProp->setAccessible(true);
            $keyProp = $reflector->getProperty('apiKey');
            $keyProp->setAccessible(true);

            $oldUrl = $urlProp->getValue($provider);
            $oldKey = $keyProp->getValue($provider);

            $urlProp->setValue($provider, 'https://mock-wacrm.local');
            $keyProp->setValue($provider, 'test_key');

            foreach ($errorScenarios as $code => $errCode) {
                CurlMockRegistry::reset();
                CurlMockRegistry::$httpCode = $code;
                CurlMockRegistry::$response = json_encode([
                    'error' => [
                        'code' => $errCode,
                        'message' => 'Simulated api error description'
                    ]
                ]);

                $res = $provider->sendTemplateMessage('+923001234567', 'new_scholarship_match', ['SchTitle']);
                if ($res['success'] !== false) {
                    throw new Exception("Provider claimed success on HTTP status $code.");
                }

                if (strpos($res['error'], "HTTP $code ($errCode)") === false) {
                    throw new Exception("Incorrect mapped error message for HTTP code $code: " . $res['error']);
                }
            }

            // Restore config values
            $urlProp->setValue($provider, $oldUrl);
            $keyProp->setValue($provider, $oldKey);

            echo "✔ HTTP error code boundaries verified.\n";
        }

        private function testRateLimitCapture(): void {
            CurlMockRegistry::reset();
            CurlMockRegistry::$httpCode = 429;
            CurlMockRegistry::$response = json_encode([
                'error' => [
                    'code' => 'rate_limited',
                    'message' => 'Too many requests'
                ]
            ]);
            // Simulate receiving the Retry-After header
            CurlMockRegistry::$headersCaptured = [
                "HTTP/1.1 429 Too Many Requests\r\n",
                "Retry-After: 45\r\n",
                "Content-Type: application/json\r\n"
            ];

            $provider = new WacrmWhatsAppProvider();
            $reflector = new ReflectionClass($provider);
            $urlProp = $reflector->getProperty('baseUrl');
            $urlProp->setAccessible(true);
            $keyProp = $reflector->getProperty('apiKey');
            $keyProp->setAccessible(true);

            $oldUrl = $urlProp->getValue($provider);
            $oldKey = $keyProp->getValue($provider);

            $urlProp->setValue($provider, 'https://mock-wacrm.local');
            $keyProp->setValue($provider, 'test_key');

            $res = $provider->sendTemplateMessage('+923001234567', 'new_scholarship_match', ['SchTitle']);

            if ($res['success'] !== false) {
                throw new Exception("Claimed success on rate limited request.");
            }

            if (($res['retry_after'] ?? null) !== 45) {
                throw new Exception("Failed to extract Retry-After rate-limit header value. Got: " . var_export($res['retry_after'] ?? null, true));
            }

            // Restore config values
            $urlProp->setValue($provider, $oldUrl);
            $keyProp->setValue($provider, $oldKey);

            echo "✔ HTTP 429 rate limits and Retry-After capture verified.\n";
        }

        private function testCurlErrorsAndCredentialsRedaction(): void {
            CurlMockRegistry::reset();
            CurlMockRegistry::$error = 'Timeout occurred while trying to connect to server with Bearer secret_api_key_123';
            CurlMockRegistry::$httpCode = 0;

            $provider = new WacrmWhatsAppProvider();
            $reflector = new ReflectionClass($provider);
            $urlProp = $reflector->getProperty('baseUrl');
            $urlProp->setAccessible(true);
            $keyProp = $reflector->getProperty('apiKey');
            $keyProp->setAccessible(true);

            $oldUrl = $urlProp->getValue($provider);
            $oldKey = $keyProp->getValue($provider);

            $urlProp->setValue($provider, 'https://mock-wacrm.local');
            $keyProp->setValue($provider, 'secret_api_key_123');

            $res = $provider->sendTemplateMessage('+923001234567', 'new_scholarship_match', ['SchTitle']);

            if ($res['success'] !== false) {
                throw new Exception("Claimed success during connection timeout.");
            }

            if (strpos($res['error'], 'secret_api_key_123') !== false) {
                throw new Exception("Credentials leaked inside error messages! Raw error: " . $res['error']);
            }

            if (strpos($res['error'], 'Bearer [REDACTED]') === false) {
                throw new Exception("Expected credentials to be redacted from curl errors.");
            }

            echo "✔ Timeout checks and API key credentials logs redactions verified.\n";
        }

        private function testMalformedJsonResponseHandling(): void {
            CurlMockRegistry::reset();
            CurlMockRegistry::$response = 'Not valid json text response';
            CurlMockRegistry::$httpCode = 200;

            $provider = new WacrmWhatsAppProvider();
            $reflector = new ReflectionClass($provider);
            $urlProp = $reflector->getProperty('baseUrl');
            $urlProp->setAccessible(true);
            $keyProp = $reflector->getProperty('apiKey');
            $keyProp->setAccessible(true);

            $oldUrl = $urlProp->getValue($provider);
            $oldKey = $keyProp->getValue($provider);

            $urlProp->setValue($provider, 'https://mock-wacrm.local');
            $keyProp->setValue($provider, 'test_key');

            $res = $provider->sendTemplateMessage('+923001234567', 'new_scholarship_match', ['SchTitle']);

            if ($res['success'] !== false || strpos($res['error'], 'Invalid JSON response') === false) {
                throw new Exception("Failed to handle malformed JSON response safely.");
            }

            // Restore config values
            $urlProp->setValue($provider, $oldUrl);
            $keyProp->setValue($provider, $oldKey);

            echo "✔ Malformed JSON responses handled correctly.\n";
        }

        private function testPhoneNumberValidation(): void {
            // E.164 normalization checks
            $validNumbers = [
                '+923001234567' => '+923001234567',
                '923001234567' => '+923001234567',
                '+14155552671' => '+14155552671'
            ];

            foreach ($validNumbers as $raw => $expected) {
                $norm = WacrmWhatsAppProvider::normalizePhoneNumber($raw);
                if ($norm !== $expected) {
                    throw new Exception("Phone normalization failed for $raw. Expected: $expected, Got: " . var_export($norm, true));
                }
            }

            $invalidNumbers = [
                '12345',
                'invalid-text',
                '+92'
            ];

            foreach ($invalidNumbers as $invalid) {
                $norm = WacrmWhatsAppProvider::normalizePhoneNumber($invalid);
                if ($norm !== null) {
                    throw new Exception("Phone validation incorrectly accepted $invalid as valid E.164: " . var_export($norm, true));
                }
            }

            echo "✔ Phone number validation and E.164 normalization verified.\n";
        }

        private function testTemplateNameMapping(): void {
            $provider = new WacrmWhatsAppProvider();
            $reflector = new ReflectionClass($provider);
            $templatesProp = $reflector->getProperty('templates');
            $templatesProp->setAccessible(true);
            
            $oldTemplates = $templatesProp->getValue($provider);

            $templatesProp->setValue($provider, [
                'new_match' => 'wacrm_new_scholarship_match_v2',
                'deadline_soon' => 'wacrm_deadline_soon_v1'
            ]);

            CurlMockRegistry::reset();
            CurlMockRegistry::$response = json_encode(['data' => ['message_id' => '1']]);
            CurlMockRegistry::$httpCode = 200;

            // Set keys to let provider pass checks
            $urlProp = $reflector->getProperty('baseUrl');
            $urlProp->setAccessible(true);
            $keyProp = $reflector->getProperty('apiKey');
            $keyProp->setAccessible(true);
            $oldUrl = $urlProp->getValue($provider);
            $oldKey = $keyProp->getValue($provider);

            $urlProp->setValue($provider, 'https://mock.local');
            $keyProp->setValue($provider, 'test');

            $provider->sendTemplateMessage('+923001234567', 'new_scholarship_match', ['SchTitle']);
            $postFields = json_decode(CurlMockRegistry::$calledPostFields, true);

            if ($postFields['template']['name'] !== 'wacrm_new_scholarship_match_v2') {
                throw new Exception("Template name map failed to translate. Expected: wacrm_new_scholarship_match_v2, Got: " . $postFields['template']['name']);
            }

            // Restore values
            $templatesProp->setValue($provider, $oldTemplates);
            $urlProp->setValue($provider, $oldUrl);
            $keyProp->setValue($provider, $oldKey);

            echo "✔ Template dynamic configuration mapping verified.\n";
        }

        private function testDirectTextMessageSending(): void {
            CurlMockRegistry::reset();
            CurlMockRegistry::$response = json_encode([
                'data' => [
                    'message_id' => 'msg_text_wacrm_8888',
                    'whatsapp_message_id' => 'wamid.HBgLOTEzMDA...'
                ]
            ]);
            CurlMockRegistry::$httpCode = 201;

            $provider = new WacrmWhatsAppProvider();
            $reflector = new ReflectionClass($provider);
            $urlProp = $reflector->getProperty('baseUrl');
            $urlProp->setAccessible(true);
            $keyProp = $reflector->getProperty('apiKey');
            $keyProp->setAccessible(true);

            $oldUrl = $urlProp->getValue($provider);
            $oldKey = $keyProp->getValue($provider);

            $urlProp->setValue($provider, 'https://mock.local');
            $keyProp->setValue($provider, 'test_key');

            $res = $provider->sendTextMessage('+923001234567', 'ScholarPlanner WACRM direct text test.');

            if (!$res['success']) {
                throw new Exception("Direct text send failed: " . ($res['error'] ?? ''));
            }

            if ($res['message_id'] !== 'msg_text_wacrm_8888') {
                throw new Exception("Unexpected message ID in direct text send: " . $res['message_id']);
            }

            $postFields = json_decode(CurlMockRegistry::$calledPostFields, true);
            if ($postFields['to'] !== '+923001234567' || $postFields['type'] !== 'text' || $postFields['text'] !== 'ScholarPlanner WACRM direct text test.') {
                throw new Exception("Incorrect payload for direct text send: " . CurlMockRegistry::$calledPostFields);
            }

            // Restore values
            $urlProp->setValue($provider, $oldUrl);
            $keyProp->setValue($provider, $oldKey);

            echo "✔ Direct text message sending and HTTP 201 payload processing verified.\n";
        }

        private function testAdminConnectionTestAccessControl(): void {
            // Create controllers and test permissions gating
            $controller = new AdminController();

            // 1. Unauthenticated or non-admin should fail connection testing
            $authBlocked = false;
            try {
                Auth::logout();
                unset($_SESSION['user_id']);
                unset($_SESSION['role_name']);
                $controller->testWacrmConnection();
            } catch (Exception $e) {
                $authBlocked = true;
            }

            if (!$authBlocked) {
                throw new Exception("Gating failed: unauthenticated session accessed connection test.");
            }

            // 2. Authenticated but non-staff (visitor) should fail connection testing
            $visitorUser = $this->db->query("SELECT * FROM users WHERE role_id = (SELECT id FROM roles WHERE name = 'visitor' LIMIT 1) LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            
            $visitorBlocked = false;
            if ($visitorUser) {
                Auth::logout();
                $_SESSION['user_id'] = $visitorUser['id'];
                $_SESSION['role_name'] = 'visitor';

                try {
                    $controller->testWacrmConnection();
                } catch (Exception $e) {
                    $visitorBlocked = true;
                }

                if (!$visitorBlocked) {
                    throw new Exception("Gating failed: visitor user accessed connection testing route.");
                }
            }

            // 3. CSRF token failure test (for admin user)
            $adminUser = $this->db->query("SELECT * FROM users WHERE role_id = (SELECT id FROM roles WHERE name = 'admin' LIMIT 1) LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            if ($adminUser) {
                Auth::logout();
                $_SESSION['user_id'] = $adminUser['id'];
                $_SESSION['role_name'] = 'admin';

                $_POST = ['csrf_token' => 'invalid_csrf_token'];
                
                $csrfBlocked = false;
                ob_start();
                try {
                    $controller->testWacrmConnection();
                } catch (Exception $e) {
                    // It might exit or throw
                    $csrfBlocked = true;
                }
                
                $output = ob_get_clean();

                if (strpos($output, 'CSRF validation failed.') === false && !$csrfBlocked) {
                    throw new Exception("Gating failed: connection test executed without a valid CSRF token. Output: $output");
                }
            }

            echo "✔ Admin authentication, permissions gating, and CSRF protection verified.\n";
        }
    }
}
