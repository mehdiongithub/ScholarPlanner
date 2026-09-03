<?php

namespace App\Services;

class EmailNotificationService {
    private array $config;
    private string $logPath;

    public function __construct() {
        $this->config = require ROOT_PATH . '/config/mail.php';
        $this->logPath = ROOT_PATH . '/storage/logs/mail.log';
    }

    /**
     * Send email notification
     *
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $body HTML email body
     * @return array Response: ['success' => bool, 'error' => ?string]
     */
    public function sendEmail(string $to, string $subject, string $body): array {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'error' => 'Invalid email recipient address format.'
            ];
        }

        $mailer = $this->config['mailer'] ?? 'smtp';

        if ($mailer === 'log') {
            return $this->logEmail($to, $subject, $body);
        }

        try {
            return $this->sendSmtp($to, $subject, $body);
        } catch (\Exception $e) {
            $redactedErr = $this->redactError($e->getMessage());
            // Log fallback if SMTP fails
            $this->logEmail($to, $subject, $body, "SMTP Failed: " . $redactedErr);
            return [
                'success' => false,
                'error' => $redactedErr
            ];
        }
    }

    private function logEmail(string $to, string $subject, string $body, ?string $prefix = null): array {
        $logDir = dirname($this->logPath);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        // Redact raw 6-digit codes in non-testing log entries
        $loggedBody = $body;
        if (!defined('TESTING_MODE') || !TESTING_MODE) {
            $loggedBody = preg_replace('/\b[0-9]{6}\b/', '[REDACTED]', $body);
        }

        $logEntry = sprintf(
            "[%s]%s TO: %s | SUBJECT: %s\nBODY:\n%s\n----------------------------------------\n",
            date('Y-m-d H:i:s'),
            $prefix ? " [$prefix]" : "",
            $to,
            $subject,
            $loggedBody
        );

        file_put_contents($this->logPath, $logEntry, FILE_APPEND);

        return [
            'success' => true,
            'error' => null
        ];
    }

    private function sendSmtp(string $to, string $subject, string $body): array {
        $host = $this->config['host'] ?? '127.0.0.1';
        $port = (int)($this->config['port'] ?? 25);
        $username = $this->config['username'] ?? '';
        $password = $this->config['password'] ?? '';
        $encryption = $this->config['encryption'] ?? ''; // 'ssl', 'tls', or null
        $fromAddress = $this->config['from_address'] ?? 'noreply@scholarmatch.com';
        $fromName = $this->config['from_name'] ?? 'ScholarMatch';

        $socketHost = $host;
        if (strtolower($encryption) === 'ssl') {
            $socketHost = 'ssl://' . $host;
        }

        $socket = @fsockopen($socketHost, $port, $errno, $errstr, 5);
        if (!$socket) {
            throw new \Exception("Could not connect to SMTP host $host:$port ($errno: $errstr)");
        }

        stream_set_timeout($socket, 5);

        try {
            $this->readSmtpResponse($socket, 220);

            // HELO/EHLO
            $this->writeSmtpCommand($socket, "EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'), 250);

            // STARTTLS if encryption is TLS
            if (strtolower($encryption) === 'tls') {
                $this->writeSmtpCommand($socket, "STARTTLS", 220);
                if (!@stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new \Exception("Failed to start TLS encryption handshake.");
                }
                // Repeat EHLO after TLS
                $this->writeSmtpCommand($socket, "EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'), 250);
            }

            // AUTH LOGIN if username/password are set
            if (!empty($username) && !empty($password)) {
                $this->writeSmtpCommand($socket, "AUTH LOGIN", 334);
                $this->writeSmtpCommand($socket, base64_encode($username), 334);
                $this->writeSmtpCommand($socket, base64_encode($password), 235);
            }

            // MAIL FROM
            $this->writeSmtpCommand($socket, "MAIL FROM:<$fromAddress>", 250);

            // RCPT TO
            $this->writeSmtpCommand($socket, "RCPT TO:<$to>", 250);

            // DATA
            $this->writeSmtpCommand($socket, "DATA", 354);

            // Build Email Headers & Body
            $headers = [
                "MIME-Version: 1.0",
                "Content-Type: text/html; charset=utf-8",
                "From: =?utf-8?B?" . base64_encode($fromName) . "?= <$fromAddress>",
                "To: <$to>",
                "Subject: =?utf-8?B?" . base64_encode($subject) . "?=",
                "Date: " . date('r'),
                "Message-ID: <" . uniqid('', true) . "@" . ($host ?: 'localhost') . ">"
            ];

            $emailData = implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n.";

            $this->writeSmtpCommand($socket, $emailData, 250);

            // QUIT
            $this->writeSmtpCommand($socket, "QUIT", 221);
        } finally {
            fclose($socket);
        }

        return [
            'success' => true,
            'error' => null
        ];
    }

    private function writeSmtpCommand($socket, string $command, int $expectedCode): void {
        fwrite($socket, $command . "\r\n");
        $this->readSmtpResponse($socket, $expectedCode);
    }

    private function readSmtpResponse($socket, int $expectedCode): string {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') {
                break;
            }
        }
        $code = (int)substr($response, 0, 3);
        if ($code !== $expectedCode) {
            throw new \Exception("Expected SMTP code $expectedCode, received: " . trim($response));
        }
        return $response;
    }

    /**
     * Test SMTP connectivity, TLS handshake, and SMTP Authentication without sending an email.
     *
     * @return array Structure: ['success' => bool, 'status' => string, 'message' => ?string, 'error' => ?string]
     */
    public function testConnection(): array {
        $mailer = $this->config['mailer'] ?? 'smtp';

        // In test mode with log driver
        if ($mailer === 'log' && (defined('TESTING_MODE') && TESTING_MODE)) {
            return [
                'success' => true,
                'status' => 'AUTHENTICATED',
                'message' => 'SMTP configuration valid and authentication successful (Log Driver Mode).'
            ];
        }

        $host = $this->config['host'] ?? '127.0.0.1';
        $port = (int)($this->config['port'] ?? 25);
        $username = $this->config['username'] ?? '';
        $password = $this->config['password'] ?? '';
        $encryption = strtolower($this->config['encryption'] ?? '');

        $socketHost = $host;
        if ($encryption === 'ssl') {
            $socketHost = 'ssl://' . $host;
        }

        $socket = @fsockopen($socketHost, $port, $errno, $errstr, 10);
        if (!$socket) {
            return [
                'success' => false,
                'status' => 'CONNECTION_FAILED',
                'error' => $this->redactError("Could not connect to SMTP host $host:$port ($errno: $errstr)")
            ];
        }

        stream_set_timeout($socket, 10);

        try {
            // 1. Initial 220 banner
            $this->readSmtpResponse($socket, 220);

            // 2. EHLO
            $this->writeSmtpCommand($socket, "EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'), 250);

            // 3. STARTTLS if configured
            if ($encryption === 'tls') {
                $this->writeSmtpCommand($socket, "STARTTLS", 220);
                if (!@stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new \Exception("TLS handshake negotiation failed on host $host:$port");
                }
                // EHLO after TLS
                $this->writeSmtpCommand($socket, "EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'), 250);
            }

            // 4. SMTP Authentication
            if (!empty($username) && !empty($password)) {
                $this->writeSmtpCommand($socket, "AUTH LOGIN", 334);
                $this->writeSmtpCommand($socket, base64_encode($username), 334);
                $this->writeSmtpCommand($socket, base64_encode($password), 235);
            }

            // Clean QUIT without sending any email
            $this->writeSmtpCommand($socket, "QUIT", 221);

            return [
                'success' => true,
                'status' => 'AUTHENTICATED',
                'message' => 'SMTP configuration valid and authentication successful.'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'status' => 'AUTH_FAILED',
                'error' => $this->redactError($e->getMessage())
            ];
        } finally {
            if (is_resource($socket)) {
                fclose($socket);
            }
        }
    }

    private function redactError(string $err): string {
        $patterns = [
            '/(password|pass|secret|token|key|auth|easypaisa|jazzcash)=[^&\s\n]+/i' => '$1=[REDACTED]',
            '/(Authorization|Bearer)\s*:?\s*(Bearer\s*)?[a-zA-Z0-9_\-\.]+/i' => '$1 [REDACTED]',
            '/[a-zA-Z0-9+\/]{40,}/' => '[REDACTED_BASE64_STRING]'
        ];
        return preg_replace(array_keys($patterns), array_values($patterns), $err);
    }
}
