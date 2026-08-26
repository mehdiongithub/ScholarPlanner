<?php
return function(PDO $db) {
    $docs = [
        ['name' => 'Passport', 'description' => 'Valid passport copy showing photograph and validity.'],
        ['name' => 'CV / Resume', 'description' => 'Up-to-date Curriculum Vitae detailing academic and work history.'],
        ['name' => 'Academic Transcripts', 'description' => 'Attested transcripts of previous degrees.'],
        ['name' => 'Recommendation Letters', 'description' => 'Letters from professors or academic supervisors.'],
        ['name' => 'Motivation Letter', 'description' => 'Statement of purpose explaining why you match this scholarship.'],
        ['name' => 'IELTS Certificate', 'description' => 'English language proficiency test certificate.']
    ];
    
    $stmt = $db->prepare("INSERT INTO documents (name, description) VALUES (:name, :description) ON DUPLICATE KEY UPDATE description = VALUES(description)");
    foreach ($docs as $d) {
        $stmt->execute($d);
    }
};