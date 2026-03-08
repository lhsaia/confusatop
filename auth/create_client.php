<?php
require 'db.php';

$client_id = "coiso";
$secret = bin2hex(random_bytes(32));
$hash = password_hash($secret, PASSWORD_DEFAULT);

$pdo->prepare("
    INSERT INTO clients (id, secret, redirect_uri, name)
    VALUES (?, ?, ?, ?)
")->execute([
    $client_id,
    $hash,
    "http://localhost/callback",
	"Novo COISO"
]);

echo "CLIENT_ID: $client_id\n";
echo "CLIENT_SECRET: $secret\n";