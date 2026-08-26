<?php
return [
    'api_url' => $_ENV['WHATSAPP_API_URL'] ?? '',
    'access_token' => $_ENV['WHATSAPP_ACCESS_TOKEN'] ?? '',
    'phone_number_id' => $_ENV['WHATSAPP_PHONE_NUMBER_ID'] ?? '',
    'business_account_id' => $_ENV['WHATSAPP_BUSINESS_ACCOUNT_ID'] ?? '',
    'webhook_verify_token' => $_ENV['WHATSAPP_WEBHOOK_VERIFY_TOKEN'] ?? '',
];
