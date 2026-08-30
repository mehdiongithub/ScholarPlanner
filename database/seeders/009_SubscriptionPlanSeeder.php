<?php
return function(PDO $db) {
    $plans = [
        [
            'name' => 'Free',
            'slug' => 'free',
            'description' => 'Basic access to scholarship lists.',
            'billing_interval' => 'month',
            'price' => 0.00,
            'currency' => 'PKR',
            'max_matches' => 5,
            'whatsapp_alerts' => 0,
            'email_alerts' => 0,
            'deadline_reminders' => 0,
            'application_tracking' => 0
        ],
        [
            'name' => 'Premium Monthly',
            'slug' => 'premium-monthly',
            'description' => 'Get matching scholarships with instant notifications.',
            'billing_interval' => 'month',
            'price' => 1499.00,
            'currency' => 'PKR',
            'max_matches' => 9999,
            'whatsapp_alerts' => 1,
            'email_alerts' => 1,
            'deadline_reminders' => 1,
            'application_tracking' => 1
        ]
    ];
    
    $stmt = $db->prepare("INSERT INTO subscription_plans (name, slug, description, billing_interval, price, currency, max_matches, whatsapp_alerts, email_alerts, deadline_reminders, application_tracking) 
                          VALUES (:name, :slug, :description, :billing_interval, :price, :currency, :max_matches, :whatsapp_alerts, :email_alerts, :deadline_reminders, :application_tracking) 
                          ON DUPLICATE KEY UPDATE price = VALUES(price), description = VALUES(description)");
    foreach ($plans as $p) {
        $stmt->execute($p);
    }
};
