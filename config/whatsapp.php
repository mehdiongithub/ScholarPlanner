<?php
return [
    'api_url' => $_ENV['WHATSAPP_API_URL'] ?? '',
    'access_token' => $_ENV['WHATSAPP_ACCESS_TOKEN'] ?? '',
    'phone_number_id' => $_ENV['WHATSAPP_PHONE_NUMBER_ID'] ?? '',
    'business_account_id' => $_ENV['WHATSAPP_BUSINESS_ACCOUNT_ID'] ?? '',
    'webhook_verify_token' => $_ENV['WHATSAPP_WEBHOOK_VERIFY_TOKEN'] ?? '',

    // Controlled Live Test Mode Configuration
    'test_mode' => filter_var($_ENV['WHATSAPP_TEST_MODE'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'test_recipient' => $_ENV['WHATSAPP_TEST_RECIPIENT'] ?? '+923251371826',
    'test_template_new_match' => $_ENV['WHATSAPP_TEST_TEMPLATE_NEW_MATCH'] ?? 'new_match_v2',

    // Template approval tracking (gates in-review templates from reaching Meta API)
    'template_status' => [
        'new_match_v2' => $_ENV['META_TEMPLATE_STATUS_NEW_MATCH_V2'] ?? 'ACTIVE',
        'new_match' => $_ENV['META_TEMPLATE_STATUS_NEW_MATCH'] ?? (filter_var($_ENV['WHATSAPP_TEST_MODE'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'IN_REVIEW' : 'ACTIVE'),
        'deadline_soon' => $_ENV['META_TEMPLATE_STATUS_DEADLINE_SOON'] ?? (filter_var($_ENV['WHATSAPP_TEST_MODE'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'IN_REVIEW' : 'ACTIVE'),
        'deadline_today' => $_ENV['META_TEMPLATE_STATUS_DEADLINE_TODAY'] ?? (filter_var($_ENV['WHATSAPP_TEST_MODE'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'IN_REVIEW' : 'ACTIVE'),
        'confirmation_msg' => $_ENV['META_TEMPLATE_STATUS_CONFIRMATION_MSG'] ?? (filter_var($_ENV['WHATSAPP_TEST_MODE'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'IN_REVIEW' : 'ACTIVE'),
    ],


    'wacrm' => [
        'base_url' => $_ENV['WACRM_BASE_URL'] ?? '',
        'api_key' => $_ENV['WACRM_API_KEY'] ?? '',
        'timeout' => (int)($_ENV['WACRM_API_TIMEOUT'] ?? 15),
        'templates' => [
            'new_match' => $_ENV['WACRM_TEMPLATE_NEW_MATCH'] ?? 'new_match_v2',
            'deadline_soon' => $_ENV['WACRM_TEMPLATE_DEADLINE_SOON'] ?? 'deadline_soon',
            'deadline_today' => $_ENV['WACRM_TEMPLATE_DEADLINE_TODAY'] ?? 'deadline_today',
        ]
    ]
];


