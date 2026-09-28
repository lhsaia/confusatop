<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Login necessário']);
    exit;
}

$idAgrupada = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$userId = (int)($_SESSION['user_id'] ?? 0);
$isAdmin = (isset($_SESSION['admin_status']) && $_SESSION['admin_status'] == 1);

if ($idAgrupada <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID inválido.']);
    exit;
}

include_once($_SERVER['DOCUMENT_ROOT'] . "/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/objetos/competicao_clube.php");

$database = new Database();
$db = $database->getConnection();
$competicao = new Competicao_clube($db);

$res = $competicao->excluirCompeticaoAgrupada($idAgrupada, $userId, $isAdmin);

if ($res) {
    echo json_encode(['success' => true, 'message' => 'Visão agregada removida com sucesso!']);
} else {
    echo json_encode(['success' => false, 'error' => 'Não foi possível remover a visão agregada.']);
}
?>
