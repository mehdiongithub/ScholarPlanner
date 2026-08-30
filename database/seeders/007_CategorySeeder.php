<?php
return function(PDO $db) {
    $categories = [
        'Fully Funded', 'Partial Funding', 'Undergraduate', "Master's", 'PhD', 
        'Research', 'Government', 'University', 'Merit Based', 'Need Based', 'International'
    ];
    
    $stmt = $db->prepare("INSERT INTO scholarship_categories (name) VALUES (:name) ON DUPLICATE KEY UPDATE name = VALUES(name)");
    foreach ($categories as $c) {
        $stmt->execute(['name' => $c]);
    }
};
