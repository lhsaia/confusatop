<?php
require 'db.php';
require 'lib/JWT.php';
require 'lib/Key.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$data = json_decode(file_get_contents("php://input"), true);

$refresh = $data['refresh_token'] ?? null;

$stmt = $pdo->prepare("SELECT * FROM refresh_tokens WHERE token = ?");
$stmt->execute([$refresh]);
$row = $stmt->fetch();

if (
    !$row ||
    $row['revoked'] ||
    strtotime($row['expires_at']) < time()
) {
    http_response_code(401);
    exit("invalid refresh");
}

// rotação (revoga antigo)
$pdo->prepare("UPDATE refresh_tokens SET revoked = 1 WHERE token = ?")
    ->execute([$refresh]);

$newRefresh = bin2hex(random_bytes(32));
$newRefreshExpiresAt = date('Y-m-d H:i:s', time() + (7 * 24 * 60 * 60));

$pdo->prepare("
    INSERT INTO refresh_tokens (token, user_id, client_id, expires_at)
    VALUES (?, ?, ?, ?)
")->execute([$newRefresh, $row['user_id'], $row['client_id'], $newRefreshExpiresAt]);

// novo JWT
$privateKey = file_get_contents("private.key");

// busca nomeusuario
$stmtUser = $pdo->prepare("SELECT nome FROM usuarios WHERE ID = ?");
$stmtUser->execute([$row['user_id']]);
$nomeusuario = $stmtUser->fetchColumn();

$payload = [
    "iss" => "https://confusa.top",
    "aud" => $row['client_id'],
    "sub" => $row['user_id'],
    "nome" => $nomeusuario,
    "iat" => time(),
    "exp" => time() + 900
];

$jwt = JWT::encode($payload, $privateKey, 'RS256');

echo json_encode([
    "access_token" => $jwt,
    "refresh_token" => $newRefresh,
    "expires_in" => 900
]);