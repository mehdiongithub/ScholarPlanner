<?php
return function(PDO $db) {
    $pkId = $db->query("SELECT id FROM countries WHERE iso2 = 'PK'")->fetchColumn();
    if (!$pkId) return;
    
    $states = [
        ['country_id' => $pkId, 'name' => 'Punjab', 'code' => 'PK-PB'],
        ['country_id' => $pkId, 'name' => 'Sindh', 'code' => 'PK-SD'],
        ['country_id' => $pkId, 'name' => 'Khyber Pakhtunkhwa', 'code' => 'PK-KP'],
        ['country_id' => $pkId, 'name' => 'Balochistan', 'code' => 'PK-BA'],
        ['country_id' => $pkId, 'name' => 'Gilgit-Baltistan', 'code' => 'PK-GB'],
        ['country_id' => $pkId, 'name' => 'Azad Jammu & Kashmir', 'code' => 'PK-JK'],
        ['country_id' => $pkId, 'name' => 'Islamabad Capital Territory', 'code' => 'PK-IS']
    ];
    
    $stmt = $db->prepare("INSERT INTO states (country_id, name, code) VALUES (:country_id, :name, :code) ON DUPLICATE KEY UPDATE code = VALUES(code)");
    foreach ($states as $s) {
        $stmt->execute($s);
    }
};
