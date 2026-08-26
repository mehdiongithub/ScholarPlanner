<?php
return [
    'mailer' => $_ENV['MAIL_MAILER'] ?? 'smtp',
    'host' => $_ENV['MAIL_HOST'] ?? 'smtp.mailtrap.io',
    'port' => $_ENV['MAIL_PORT'] ?? '2525',
    'username' => $_ENV['MAIL_USERNAME'] ?? null,
    'password' => $_ENV['MAIL_PASSWORD'] ?? null,
    'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? null,
    'from_address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@scholarmatch.com',
    'from_name' => $_ENV['MAIL_FROM_NAME'] ?? 'ScholarMatch',
];
