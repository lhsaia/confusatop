<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(403);
    die("Acesso negado. Você precisa estar autenticado para realizar esta operação.");
}

$idJogo = isset($_GET['jogo']) ? intval($_GET['jogo']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);
if (!$idJogo) {
    http_response_code(400);
    die("ID da partida não informado.");
}

$tipo = isset($_GET['tipo']) ? strtolower(trim($_GET['tipo'])) : 'hyl';
if (!in_array($tipo, ['hyl', 'hyj'])) {
    http_response_code(400);
    die("Tipo de arquivo inválido. Permitidos: hyl ou hyj.");
}

require_once $_SERVER['DOCUMENT_ROOT'] . "/config/database.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/objetos/competicao_clube.php";
require_once __DIR__ . '/sumula_helper.php';

$database = new Database();
$db = $database->getConnection();
$competicaoObj = new Competicao_clube($db);

$matchInfo = $competicaoObj->getMatchInfo($idJogo);
if (!$matchInfo) {
    http_response_code(404);
    die("Partida não encontrada.");
}

$idCompeticao = (int)($matchInfo['competicao'] ?? 0);
$compInfo = $competicaoObj->readInfo($idCompeticao);
if (!$compInfo) {
    http_response_code(404);
    die("Competição vinculada não encontrada.");
}

// Verificação de permissões: apenas Administrador ou o Dono da Competição
$isAdmin = (isset($_SESSION['admin_status']) && (int)$_SESSION['admin_status'] === 1) || !empty($_SESSION['impersonated']);
$donoComp = (int)($compInfo['dono'] ?? 0);
$userId = (int)($_SESSION['user_id'] ?? 0);

if (!$isAdmin && $userId !== $donoComp) {
    http_response_code(403);
    die("Acesso negado. Apenas o dono da competição ou administradores podem baixar as súmulas brutas (.{$tipo}).");
}

if ((int)$matchInfo['status'] !== 1 || empty($matchInfo['path'])) {
    http_response_code(400);
    die("Esta partida ainda não foi simulada ou não possui arquivo de súmula registrado.");
}

$nomeComposto = ($compInfo['ano'] ?? '') . " - " . ($compInfo['nome'] ?? '');
$caminhoArquivo = encontrarArquivoSumula($nomeComposto, $matchInfo['path'], $tipo);

if (!$caminhoArquivo || !file_exists($caminhoArquivo)) {
    http_response_code(404);
    die("O arquivo .{$tipo} da partida não foi localizado no servidor.");
}

$nomeDownload = basename($caminhoArquivo);

// Headers para forçar o download do arquivo
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $nomeDownload . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . filesize($caminhoArquivo));

// Limpa qualquer buffer de saída ativo antes do envio do arquivo
if (ob_get_level()) {
    ob_end_clean();
}

readfile($caminhoArquivo);
exit;
