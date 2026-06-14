<?php
require 'db.php';

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

$client_id = $_GET['client_id'] ?? null;
$redirect_uri = $_GET['redirect_uri'] ?? null;

$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    header("Location: /auth/login.php?client_id=" . urlencode($client_id) . "&redirect_uri=" . urlencode($redirect_uri));
    exit;
}

$client_id = $_GET['client_id'] ?? null;
$redirect_uri = $_GET['redirect_uri'] ?? null;

$stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
$stmt->execute([$client_id]);
$client = $stmt->fetch();

if (!$client || $client['redirect_uri'] !== $redirect_uri) {
    http_response_code(400);
    exit("invalid client");
}

$code = bin2hex(random_bytes(32));
$expiresAt = date('Y-m-d H:i:s', time() + 60);

$pdo->prepare("
    INSERT INTO auth_codes (code, user_id, client_id, expires_at)
    VALUES (?, ?, ?, ?)
")->execute([$code, $userId, $client_id, $expiresAt]);

header("Location: $redirect_uri?code=$code");
exit;