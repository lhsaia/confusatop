<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
if(!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true){
    die("Acesso negado.");
}

$idCompeticao = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$idCompeticao) {
    die("ID da competição não fornecido.");
}

require_once $_SERVER['DOCUMENT_ROOT'] . "/lib/simplexlsx/SimpleXLSXGen.php";
include_once $_SERVER['DOCUMENT_ROOT'] . "/config/database.php";
include_once $_SERVER['DOCUMENT_ROOT'] . "/objetos/competicao_clube.php";

$database = new Database();
$db = $database->getConnection();
$competicaoObj = new Competicao_clube($db);

$compInfo = $competicaoObj->readInfo($idCompeticao);
if (!$compInfo) {
    die("Competição não encontrada.");
}

// Check permission
$isAdmin = (isset($_SESSION['admin_status']) && $_SESSION['admin_status'] == 1);
$dono = isset($compInfo['dono']) ? (int)$compInfo['dono'] : 0;
if (!$isAdmin && $_SESSION['user_id'] != $dono) {
    die("Você não tem permissão para exportar esta planilha.");
}

use Shuchkin\SimpleXLSXGen;

// Rows data construction
$rows = [
    // Instructions block
    ["<b>INSTRUÇÕES DE PREENCHIMENTO:</b>", null, null, null, null, null, null, null, null, null, null, null, null, null],
    ["1. Coluna 'ID Partida': NÃO altere o ID de partidas existentes. Deixe em branco se quiser CADASTRAR um novo jogo.", null, null, null, null, null, null, null, null, null, null, null, null, null],
    ["2. Colunas 'Time A ID' e 'Time B ID': Use IDs de times válidos da competição. Os nomes são apenas informativos.", null, null, null, null, null, null, null, null, null, null, null, null, null],
    ["3. 'Data/Hora': Use o formato YYYY-MM-DD HH:MM:SS (Ex: 2026-08-05 15:30:00).", null, null, null, null, null, null, null, null, null, null, null, null, null],
    ["4. 'Neutro': Insira 0 para jogo com mando de campo do Time A, ou 1 para campo neutro.", null, null, null, null, null, null, null, null, null, null, null, null, null],
    ["5. Para salvar as alterações, salve o arquivo e faça o upload através do botão 'Importar Excel'.", null, null, null, null, null, null, null, null, null, null, null, null, null],
    [], // Blank line (row 7)
    // Headers (row 8)
    [
        '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>ID Partida</b></style>',
        '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Time A ID</b></style>',
        '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Time A Nome</b></style>',
        '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Gols A</b></style>',
        '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Time B ID</b></style>',
        '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Time B Nome</b></style>',
        '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Gols B</b></style>',
        '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Data/Hora</b></style>',
        '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Fase ID</b></style>',
        '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Grupo</b></style>',
        '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Estádio ID</b></style>',
        '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Árbitro ID</b></style>',
        '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Neutro (0/1)</b></style>',
        '<style bgcolor="#1A1469" color="#FFFFFF" align="center"><b>Status (0/1)</b></style>'
    ]
];

// Load matches
$stmt = $competicaoObj->carregarListaJogos($idCompeticao);
$matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($matches as $m) {
    $stmtTeamA = $db->prepare("SELECT Nome FROM clube WHERE ID = ?");
    $stmtTeamA->execute([$m['timeA_id']]);
    $tAName = $stmtTeamA->fetchColumn() ?: "Time #".$m['timeA_id'];

    $stmtTeamB = $db->prepare("SELECT Nome FROM clube WHERE ID = ?");
    $stmtTeamB->execute([$m['timeB_id']]);
    $tBName = $stmtTeamB->fetchColumn() ?: "Time #".$m['timeB_id'];

    $rows[] = [
        $m['id'],
        $m['timeA_id'],
        '<style bgcolor="#F1F5F9">' . htmlspecialchars($tAName, ENT_QUOTES, 'UTF-8') . '</style>',
        $m['timeA_gols'],
        $m['timeB_id'],
        '<style bgcolor="#F1F5F9">' . htmlspecialchars($tBName, ENT_QUOTES, 'UTF-8') . '</style>',
        $m['timeB_gols'],
        $m['data'],
        $m['fase'],
        $m['grupo'],
        $m['estadio'],
        $m['arbitro'],
        $m['neutro'],
        $m['status']
    ];
}

$xlsx = SimpleXLSXGen::fromArray($rows);
$xlsx->mergeCells('A1:N1');
$xlsx->mergeCells('A2:N2');
$xlsx->mergeCells('A3:N3');
$xlsx->mergeCells('A4:N4');
$xlsx->mergeCells('A5:N5');
$xlsx->mergeCells('A6:N6');
$xlsx->downloadAs('tabela_competicao_' . $idCompeticao . '.xlsx');
exit();
