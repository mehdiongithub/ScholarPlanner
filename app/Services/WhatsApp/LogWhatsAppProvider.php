<?php

namespace App\Services\WhatsApp;

class LogWhatsAppProvider implements WhatsAppProviderInterface {
    private string $logPath;

    public function __construct() {
        $this->logPath = ROOT_PATH . '/storage/logs/whatsapp.log';
    }

    public function sendTemplateMessage(string $recipient, string $templateName, array $parameters): array {
        $logDir = dirname($this->logPath);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        $logEntry = sprintf(
            "[%s] Recipient: %s | Template: %s | Params: %s\n",
            date('Y-m-d H:i:s'),
            $recipient,
            $templateName,
            json_encode($parameters)
        );

        file_put_contents($this->logPath, $logEntry, FILE_APPEND);

        return [
            'success' => true,
            'message_id' => 'log_' . uniqid(),
            'error' => null
        ];
    }

    public function sendTextMessage(string $recipient, string $text): array {
        $logDir = dirname($this->logPath);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        $logEntry = sprintf(
            "[%s] Recipient: %s | Text: %s\n",
            date('Y-m-d H:i:s'),
            $recipient,
            $text
        );

        file_put_contents($this->logPath, $logEntry, FILE_APPEND);

        return [
            'success' => true,
            'message_id' => 'log_' . uniqid(),
            'error' => null
        ];
    }
}

