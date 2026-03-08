<?php
require 'db.php';
require 'lib/JWT.php';
require 'lib/Key.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$data = json_decode(file_get_contents("php://input"), true);

$code = $data['code'] ?? null;
$client_id = $data['client_id'] ?? null;
$client_secret = $data['client_secret'] ?? null;

// valida client
$stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
$stmt->execute([$client_id]);
$client = $stmt->fetch();

if (!$client || !password_verify($client_secret, $client['secret'])) {
    http_response_code(401);
    exit("invalid client");
}

// valida code
$stmt = $pdo->prepare("SELECT * FROM auth_codes WHERE code = ?");
$stmt->execute([$code]);
$authCode = $stmt->fetch();

if (!$authCode) {
    http_response_code(400);
    exit("invalid code: code not found in db");
}
if ($authCode['used'] == 1) {
    http_response_code(400);
    exit("invalid code: code already used");
}
if (strtotime($authCode['expires_at']) < time()) {
    http_response_code(400);
    exit("invalid code: code expired (db time: " . $authCode['expires_at'] . ", php time: " . date('Y-m-d H:i:s') . ")");
}
if ($authCode['client_id'] != $client_id) {
    http_response_code(400);
    exit("invalid code: client_id mismatch (db: {$authCode['client_id']}, req: {$client_id})");
}

// marca como usado
$pdo->prepare("UPDATE auth_codes SET used = 1 WHERE code = ?")
    ->execute([$code]);

$user_id = $authCode['user_id'];

// busca nomeusuario
$stmtUser = $pdo->prepare("SELECT nome FROM usuarios WHERE ID = ?");
$stmtUser->execute([$user_id]);
$nomeusuario = $stmtUser->fetchColumn();

// JWT
$privateKey = file_get_contents("private.key");

$payload = [
    "iss" => "https://confusa.top",
    "aud" => $client_id,
    "sub" => $user_id,
    "nome" => $nomeusuario,
    "iat" => time(),
    "exp" => time() + 900
];

$jwt = JWT::encode($payload, $privateKey, 'RS256');

// refresh token
$refresh = bin2hex(random_bytes(32));
$refreshExpiresAt = date('Y-m-d H:i:s', time() + (7 * 24 * 60 * 60)); // 7 days

$pdo->prepare("
    INSERT INTO refresh_tokens (token, user_id, client_id, expires_at)
    VALUES (?, ?, ?, ?)
")->execute([$refresh, $user_id, $client_id, $refreshExpiresAt]);

echo json_encode([
    "access_token" => $jwt,
    "refresh_token" => $refresh,
    "expires_in" => 900
]);