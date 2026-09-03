<?php
return [
    'cashmaal' => [
        'web_id' => $_ENV['CASHMAAL_WEB_ID'] ?? '',
        'ipn_key' => $_ENV['CASHMAAL_IPN_KEY'] ?? '',
        'pay_url' => $_ENV['CASHMAAL_PAY_URL'] ?? 'https://cmaal.com/Pay/',
        'verify_url' => $_ENV['CASHMAAL_VERIFY_URL'] ?? 'https://api.cmaal.com/verify_v2',
        'timeout' => (int)($_ENV['CASHMAAL_TIMEOUT'] ?? 15),
        'currency' => $_ENV['CASHMAAL_CURRENCY'] ?? 'PKR',
        'verify_api' => filter_var($_ENV['CASHMAAL_VERIFY_API'] ?? false, FILTER_VALIDATE_BOOLEAN),
    ],
    'easypaisa' => [
        'api_url' => $_ENV['EASYPAISA_API_URL'] ?? '',
        'store_id' => $_ENV['EASYPAISA_STORE_ID'] ?? '',
        'username' => $_ENV['EASYPAISA_USERNAME'] ?? '',
        'password' => $_ENV['EASYPAISA_PASSWORD'] ?? '',
        'key' => $_ENV['EASYPAISA_KEY'] ?? '',
    ],
    'jazzcash' => [
        'merchant_id' => $_ENV['JAZZCASH_MERCHANT_ID'] ?? '',
        'password' => $_ENV['JAZZCASH_PASSWORD'] ?? '',
        'integrity_salt' => $_ENV['JAZZCASH_INTEGRITY_SALT'] ?? '',
        'api_url' => $_ENV['JAZZCASH_API_URL'] ?? '',
    ],
    'currency' => $_ENV['PAYMENT_CURRENCY'] ?? 'PKR',
];
