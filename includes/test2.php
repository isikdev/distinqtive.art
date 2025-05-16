<?php

$url = 'https://api.cdek.ru/v2/oauth/token'; // Или api.edu.cdek.ru для теста
$client_id = 's74VR4n4xnjT6EWSeRpg09aCtU8MugRN';
$client_secret = 'Ef75sxzDIsd66GXFGoQDADCbFOSYgLT0';

$data = http_build_query([
	                         'grant_type'    => 'client_credentials',
	                         'client_id'     => $client_id,
	                         'client_secret' => $client_secret,
                         ]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

$response = curl_exec($ch);
curl_close($ch);

echo $response;
