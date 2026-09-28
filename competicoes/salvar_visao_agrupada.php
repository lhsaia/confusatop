<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Login necessário']);
    exit;
}

if ($_SESSION['emTestes'] ?? false) {
    echo json_encode(['success' => false, 'error' => 'Usuários em período de teste não podem salvar visões agrupadas.']);
    exit;
}

$idComp1 = isset($_POST['comp1']) ? (int)$_POST['comp1'] : 0;
$idComp2 = isset($_POST['comp2']) ? (int)$_POST['comp2'] : 0;
$nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
$userId = (int)($_SESSION['user_id'] ?? 0);

if ($idComp1 <= 0 || $idComp2 <= 0) {
    echo json_encode(['success' => false, 'error' => 'Selecione duas competições válidas para salvar.']);
    exit;
}

include_once($_SERVER['DOCUMENT_ROOT'] . "/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/objetos/competicao_clube.php");

$database = new Database();
$db = $database->getConnection();
$competicao = new Competicao_clube($db);

if ($nome === '') {
    $info1 = $competicao->readInfo($idComp1);
    $info2 = $competicao->readInfo($idComp2);
    $nome1 = $info1['nome'] ?? 'Torneio 1';
    $nome2 = $info2['nome'] ?? 'Torneio 2';
    $ano = $info1['ano'] ?? ($info2['ano'] ?? date('Y'));
    $nome = "{$nome1} + {$nome2} ({$ano})";
}

$res = $competicao->salvarCompeticaoAgrupada($nome, $idComp1, $idComp2, $userId);

if ($res) {
    echo json_encode(['success' => true, 'message' => 'Visão agregada salva com sucesso na sua lista de competições!']);
} else {
    echo json_encode(['success' => false, 'error' => 'Erro ao salvar visão agregada no banco de dados.']);
}
?>
