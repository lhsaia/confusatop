<?php
declare(strict_types=1);

header('Content-Type: application/json');

// Lê JSON enviado pelo Worker
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

// Validação básica
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid JSON']);
    exit;
}

// Segurança: shared secret
if (
    !isset($data['secret']) ||
    !hash_equals(getenv('SHARED_SECRET'), $data['secret'])
) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Forbidden']);
    exit;
}

// Campos obrigatórios
$title = trim($data['title'] ?? '');
$desc  = trim($data['desc']  ?? '');

if ($title === '' || $desc === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing fields']);
    exit;
}

// Cria issue
$url = criarIssueGitHub($title, $desc);

if ($url === null) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'GitHub error']);
    exit;
}

// Sucesso
echo json_encode([
    'ok'  => true,
    'url' => $url
]);
exit;


/**
 * Cria uma issue no GitHub
 */
function criarIssueGitHub(string $title, string $body): ?string
{
    $token = getenv('GITHUB_TOKEN');
    $repo  = getenv('GITHUB_REPO'); // ex: usuario/repositorio

    if (!$token || !$repo) {
        return null;
    }

    $payload = json_encode([
        'title' => "[Discord] " . $title,
        'body'  => $body
    ]);

    $ch = curl_init("https://api.github.com/repos/$repo/issues");

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            "Authorization: Bearer $token",
            "User-Agent: Discord-Issue-Bridge",
            "Accept: application/vnd.github+json",
            "Content-Type: application/json"
        ],
        CURLOPT_TIMEOUT        => 10
    ]);

    $response = curl_exec($ch);
    $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($status !== 201 || !$response) {
        return null;
    }

    $json = json_decode($response, true);
    return $json['html_url'] ?? null;
}
