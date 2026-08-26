<?php

namespace App\Services;

use App\Services\WhatsApp\WhatsAppProviderInterface;
use App\Services\WhatsApp\LogWhatsAppProvider;
use App\Services\WhatsApp\MetaWhatsAppProvider;

class WhatsAppNotificationService {
    private WhatsAppProviderInterface $provider;

    public function __construct() {
        $providerType = strtolower($_ENV['WHATSAPP_PROVIDER'] ?? 'log');
        if ($providerType === 'meta') {
            $this->provider = new MetaWhatsAppProvider();
        } else {
            $this->provider = new LogWhatsAppProvider();
        }
    }

    /**
     * Send a template message through the selected WhatsApp provider.
     */
    public function sendMessage(string $recipient, string $templateName, array $parameters): array {
        // Clean recipient phone number (retain digits only)
        $cleanPhone = preg_replace('/[^0-9]/', '', $recipient);
        if (empty($cleanPhone)) {
            return [
                'success' => false,
                'message_id' => null,
                'error' => 'Missing recipient phone number.'
            ];
        }

        return $this->provider->sendTemplateMessage('+' . $cleanPhone, $templateName, $parameters);
    }
}
