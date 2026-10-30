<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(403);
    die("Acesso negado. Você precisa estar autenticado para realizar esta operação.");
}

$idCompeticao = isset($_GET['id']) ? intval($_GET['id']) : (isset($_GET['comp']) ? intval($_GET['comp']) : 0);
if (!$idCompeticao) {
    http_response_code(400);
    die("ID da competição não informado.");
}

$tipo = isset($_GET['tipo']) ? strtolower(trim($_GET['tipo'])) : 'todos';
if (!in_array($tipo, ['todos', 'hyl', 'hyj'])) {
    $tipo = 'todos';
}

require_once $_SERVER['DOCUMENT_ROOT'] . "/config/database.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/objetos/competicao_clube.php";
require_once __DIR__ . '/sumula_helper.php';

$database = new Database();
$db = $database->getConnection();
$competicaoObj = new Competicao_clube($db);

$compInfo = $competicaoObj->readInfo($idCompeticao);
if (!$compInfo) {
    http_response_code(404);
    die("Competição não encontrada.");
}

// Verificação de permissão: apenas Administrador ou o Dono da Competição
$isAdmin = (isset($_SESSION['admin_status']) && (int)$_SESSION['admin_status'] === 1) || !empty($_SESSION['impersonated']);
$donoComp = (int)($compInfo['dono'] ?? 0);
$userId = (int)($_SESSION['user_id'] ?? 0);

if (!$isAdmin && $userId !== $donoComp) {
    http_response_code(403);
    die("Acesso negado. Apenas o dono da competição ou administradores podem baixar o pacote de súmulas.");
}

$nomeComposto = ($compInfo['ano'] ?? '') . " - " . ($compInfo['nome'] ?? '');

// Buscar caminhos de todas as partidas simuladas no banco de dados
$stmtJogos = $db->prepare("SELECT path FROM jogos_clube WHERE competicao_id = :id AND status = 1 AND path IS NOT NULL AND path != ''");
$stmtJogos->execute([':id' => $idCompeticao]);
$jogosPaths = $stmtJogos->fetchAll(PDO::FETCH_COLUMN);

// Coletar todos os arquivos da competição
$arquivos = coletarArquivosSumulasCompeticao($nomeComposto, $jogosPaths, $tipo);

if (empty($arquivos)) {
    http_response_code(404);
    die("Nenhum arquivo de súmula (.hyl ou .hyj) foi encontrado para esta competição no servidor.");
}

if (!class_exists('ZipArchive')) {
    http_response_code(500);
    die("A extensão ZipArchive não está habilitada neste servidor PHP.");
}

$tempZip = tempnam(sys_get_temp_dir(), 'sumulas_comp_');
$zip = new ZipArchive();

if ($zip->open($tempZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    http_response_code(500);
    die("Erro ao criar arquivo ZIP temporário.");
}

foreach ($arquivos as $relPath => $absPath) {
    if (file_exists($absPath)) {
        $zip->addFile($absPath, $relPath);
    }
}

$zip->close();

if (!file_exists($tempZip) || filesize($tempZip) === 0) {
    @unlink($tempZip);
    http_response_code(500);
    die("Erro ao consolidar os arquivos no pacote ZIP.");
}

$safeCompName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $nomeComposto);
$tipoSuffix = ($tipo === 'hyl') ? '_hyl' : (($tipo === 'hyj') ? '_hyj' : '');
$downloadName = "Sumulas_{$safeCompName}{$tipoSuffix}.zip";

// Headers de download
header('Content-Description: File Transfer');
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . filesize($tempZip));

if (ob_get_level()) {
    ob_end_clean();
}

readfile($tempZip);
@unlink($tempZip);
exit;
