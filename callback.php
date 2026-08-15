<?php

$code = $_GET['code'] ?? null;

if (!$code) {
    die("sem code");
}

// CONFIG
$client_id = "teste";
$client_secret = "a7d861bc3e2b710880321acdc44f10fc9609138be87ab3b197913befbfeb7e51";

// chama o /token do Site A
$response = file_get_contents("http://127.0.0.7/auth/token.php", false, stream_context_create([
    'http' => [
        'method' => 'POST',
		'ignore_errors' => true, 
        'header' => "Content-Type: application/json",
        'content' => json_encode([
            "code" => $code,
            "client_id" => $client_id,
            "client_secret" => $client_secret
        ])
    ]
]));

echo "<pre>";
var_dump($response);

// $data = json_decode($response, true);

// if (!$data) {
    // die("erro ao obter token");
// }

// echo "<pre>";
// print_r($data);