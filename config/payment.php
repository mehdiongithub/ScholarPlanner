<?php
return [
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
