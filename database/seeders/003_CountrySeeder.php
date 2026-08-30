<?php
return function(PDO $db) {
    $countries = [
        ['name' => 'Pakistan', 'iso2' => 'PK', 'iso3' => 'PAK', 'phone_code' => '92', 'currency_code' => 'PKR'],
        ['name' => 'India', 'iso2' => 'IN', 'iso3' => 'IND', 'phone_code' => '91', 'currency_code' => 'INR'],
        ['name' => 'Bangladesh', 'iso2' => 'BD', 'iso3' => 'BGD', 'phone_code' => '880', 'currency_code' => 'BDT'],
        ['name' => 'United States', 'iso2' => 'US', 'iso3' => 'USA', 'phone_code' => '1', 'currency_code' => 'USD'],
        ['name' => 'United Kingdom', 'iso2' => 'GB', 'iso3' => 'GBR', 'phone_code' => '44', 'currency_code' => 'GBP'],
        ['name' => 'Germany', 'iso2' => 'DE', 'iso3' => 'DEU', 'phone_code' => '49', 'currency_code' => 'EUR'],
        ['name' => 'Turkey', 'iso2' => 'TR', 'iso3' => 'TUR', 'phone_code' => '90', 'currency_code' => 'TRY'],
        ['name' => 'China', 'iso2' => 'CN', 'iso3' => 'CHN', 'phone_code' => '86', 'currency_code' => 'CNY'],
        ['name' => 'Canada', 'iso2' => 'CA', 'iso3' => 'CAN', 'phone_code' => '1', 'currency_code' => 'CAD'],
        ['name' => 'Australia', 'iso2' => 'AU', 'iso3' => 'AUS', 'phone_code' => '61', 'currency_code' => 'AUD'],
        ['name' => 'Saudi Arabia', 'iso2' => 'SA', 'iso3' => 'SAU', 'phone_code' => '966', 'currency_code' => 'SAR'],
        ['name' => 'United Arab Emirates', 'iso2' => 'AE', 'iso3' => 'ARE', 'phone_code' => '971', 'currency_code' => 'AED']
    ];
    
    $stmt = $db->prepare("INSERT INTO countries (name, iso2, iso3, phone_code, currency_code) VALUES (:name, :iso2, :iso3, :phone_code, :currency_code) ON DUPLICATE KEY UPDATE name = VALUES(name)");
    foreach ($countries as $c) {
        $stmt->execute($c);
    }
};
