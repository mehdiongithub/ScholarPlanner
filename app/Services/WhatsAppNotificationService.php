<?php

namespace App\Services;

use App\Services\WhatsApp\WhatsAppProviderInterface;
use App\Services\WhatsApp\LogWhatsAppProvider;
use App\Services\WhatsApp\MetaWhatsAppProvider;
use App\Services\WhatsApp\WacrmWhatsAppProvider;

class WhatsAppNotificationService {
    private ?WhatsAppProviderInterface $provider;

    public function __construct(?WhatsAppProviderInterface $provider = null) {
        $this->provider = $provider;
    }

    /**
     * Resolve the appropriate WhatsApp provider for a given provider name or notification type.
     */
    public function getProviderFor(?string $providerName = null, ?string $notificationType = null): WhatsAppProviderInterface {
        if ($this->provider !== null) {
            return $this->provider;
        }

        // Explicit provider request takes precedence
        if ($providerName !== null && $providerName !== '') {
            $p = strtolower(trim($providerName));
            if ($p === 'meta') {
                return new MetaWhatsAppProvider();
            }
            if ($p === 'wacrm') {
                return new WacrmWhatsAppProvider();
            }
            if ($p === 'log') {
                return new LogWhatsAppProvider();
            }
        }

        // Notification type routing:
        // Payment success confirmation routes to Meta WhatsApp Provider
        if ($notificationType !== null && (
            strpos($notificationType, 'PAYMENT') !== false ||
            strpos($notificationType, 'CASHMAAL') !== false ||
            strpos($notificationType, 'SUBSCRIPTION_CONFIRMATION') !== false
        )) {
            return new MetaWhatsAppProvider();
        }

        // Scholarship notifications route to WACRM (or log if configured in environment)
        $envProvider = strtolower($_ENV['WHATSAPP_PROVIDER'] ?? 'wacrm');
        if ($envProvider === 'meta') {
            return new MetaWhatsAppProvider();
        } elseif ($envProvider === 'log') {
            return new LogWhatsAppProvider();
        } else {
            return new WacrmWhatsAppProvider();
        }
    }

    /**
     * Send a template message through the resolved WhatsApp provider.
     */
    public function sendMessage(string $recipient, string $templateName, array $parameters, ?string $providerName = null, ?string $notificationType = null): array {
        // Clean and normalize recipient phone number
        $normalizedPhone = WacrmWhatsAppProvider::normalizePhoneNumber($recipient);
        if ($normalizedPhone === null) {
            return [
                'success' => false,
                'message_id' => null,
                'error' => 'Invalid or missing recipient phone number format.'
            ];
        }

        $provider = $this->getProviderFor($providerName, $notificationType);
        return $provider->sendTemplateMessage($normalizedPhone, $templateName, $parameters);
    }
}
