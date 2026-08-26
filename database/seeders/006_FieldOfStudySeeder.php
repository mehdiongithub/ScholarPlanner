<?php
return function(PDO $db) {
    $fields = [
        'Computer Science', 'Information Technology', 'Software Engineering',
        'Business Administration', 'Electrical Engineering', 'Mechanical Engineering',
        'Civil Engineering', 'Medicine & Surgery', 'Biotechnology', 'Law', 
        'Education', 'Natural Sciences', 'Social Sciences', 'Arts & Humanities'
    ];
    
    $stmt = $db->prepare("INSERT INTO fields_of_study (name) VALUES (:name) ON DUPLICATE KEY UPDATE name = VALUES(name)");
    foreach ($fields as $f) {
        $stmt->execute(['name' => $f]);
    }
};