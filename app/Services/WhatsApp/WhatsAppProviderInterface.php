<?php

namespace App\Services\WhatsApp;

interface WhatsAppProviderInterface {
    /**
     * Send a template-based message
     *
     * @param string $recipient The recipient phone number (with country code, e.g., +923001234567)
     * @param string $templateName The registered template name
     * @param array $parameters Associative or sequential array of template variables
     * @return array Response structure: ['success' => bool, 'message_id' => ?string, 'error' => ?string]
     */
    public function sendTemplateMessage(string $recipient, string $templateName, array $parameters): array;
}
