<?php
return [
    'api_url' => $_ENV['WHATSAPP_API_URL'] ?? '',
    'access_token' => $_ENV['WHATSAPP_ACCESS_TOKEN'] ?? '',
    'phone_number_id' => $_ENV['WHATSAPP_PHONE_NUMBER_ID'] ?? '',
    'business_account_id' => $_ENV['WHATSAPP_BUSINESS_ACCOUNT_ID'] ?? '',
    'webhook_verify_token' => $_ENV['WHATSAPP_WEBHOOK_VERIFY_TOKEN'] ?? '',

    'wacrm' => [
        'base_url' => $_ENV['WACRM_BASE_URL'] ?? '',
        'api_key' => $_ENV['WACRM_API_KEY'] ?? '',
        'timeout' => (int)($_ENV['WACRM_API_TIMEOUT'] ?? 15),
        'templates' => [
            'new_match' => $_ENV['WACRM_TEMPLATE_NEW_MATCH'] ?? 'new_match',
            'deadline_soon' => $_ENV['WACRM_TEMPLATE_DEADLINE_SOON'] ?? 'deadline_soon',
            'deadline_today' => $_ENV['WACRM_TEMPLATE_DEADLINE_TODAY'] ?? 'deadline_today',
        ]
    ]
];
