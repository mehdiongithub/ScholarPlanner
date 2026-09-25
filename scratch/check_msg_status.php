<?php
$apiKey = 'wacrm_live_urD9K3NPYqi0pSrmWyvregOO5eK7g12FWI4WWIgrQVw';

$endpoints = [
    '/api/v1/conversations/5bae2d72-1826-4d14-a7fd-4477f4c5addc/messages',
    '/api/v1/messages/adad85e8-bf11-4095-aa77-80f4daf23c1b',
    '/api/v1/conversations/5bae2d72-1826-4d14-a7fd-4477f4c5addc'
];

foreach ($endpoints as $ep) {
    $url = 'https://wacrm.scholarplanner.com' . $ep;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Accept: application/json'
    ]);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo "$ep -> HTTP $code\n";
    echo $res . "\n\n";
}
