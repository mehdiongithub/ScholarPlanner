<?php
return function(PDO $db) {
    // Seed key cities for Punjab and Sindh
    $punjabId = $db->query("SELECT id FROM states WHERE code = 'PK-PB'")->fetchColumn();
    $sindhId = $db->query("SELECT id FROM states WHERE code = 'PK-SD'")->fetchColumn();
    $ictId = $db->query("SELECT id FROM states WHERE code = 'PK-IS'")->fetchColumn();
    
    $cities = [];
    
    if ($punjabId) {
        $cities[] = ['state_id' => $punjabId, 'name' => 'Lahore'];
        $cities[] = ['state_id' => $punjabId, 'name' => 'Rawalpindi'];
        $cities[] = ['state_id' => $punjabId, 'name' => 'Faisalabad'];
        $cities[] = ['state_id' => $punjabId, 'name' => 'Multan'];
    }
    
    if ($sindhId) {
        $cities[] = ['state_id' => $sindhId, 'name' => 'Karachi'];
        $cities[] = ['state_id' => $sindhId, 'name' => 'Hyderabad'];
    }
    
    if ($ictId) {
        $cities[] = ['state_id' => $ictId, 'name' => 'Islamabad'];
    }
    
    $stmt = $db->prepare("INSERT INTO cities (state_id, name) VALUES (:state_id, :name) ON DUPLICATE KEY UPDATE name = VALUES(name)");
    foreach ($cities as $c) {
        $stmt->execute($c);
    }
};