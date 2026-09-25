<?php
$apiKey = 'wacrm_live_urD9K3NPYqi0pSrmWyvregOO5eK7g12FWI4WWIgrQVw';

$url = 'https://wacrm.scholarplanner.com/api/v1/conversations/5bae2d72-1826-4d14-a7fd-4477f4c5addc/messages';
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Accept: application/json'
]);
$res = curl_exec($ch);
curl_close($ch);
$data = json_decode($res, true);
echo json_encode($data['data'][0] ?? $data, JSON_PRETTY_PRINT);
