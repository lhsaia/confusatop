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

function isRedirectUriAllowed($db_uri, $req_uri) {
    if ($db_uri === $req_uri) {
        return true;
    }

    $db_parsed = parse_url($db_uri);
    $req_parsed = parse_url($req_uri);

    if (!$db_parsed || !$req_parsed) {
        return false;
    }

    $db_path = rtrim($db_parsed['path'] ?? '/', '/');
    $req_path = rtrim($req_parsed['path'] ?? '/', '/');
    if ($db_path !== $req_path) {
        return false;
    }

    $db_host = $db_parsed['host'] ?? '';
    $req_host = $req_parsed['host'] ?? '';
    if ($db_host !== $req_host) {
        return false;
    }

    $db_scheme = $db_parsed['scheme'] ?? '';
    $req_scheme = $req_parsed['scheme'] ?? '';
    if ($db_scheme !== $req_scheme) {
        return false;
    }

    $db_query = $db_parsed['query'] ?? '';
    $req_query = $req_parsed['query'] ?? '';
    if ($db_query !== $req_query) {
        return false;
    }

    if ($db_host === 'localhost') {
        return true;
    }

    $db_port = $db_parsed['port'] ?? null;
    $req_port = $req_parsed['port'] ?? null;
    return $db_port === $req_port;
}

if (!$client || !isRedirectUriAllowed($client['redirect_uri'], $redirect_uri)) {
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