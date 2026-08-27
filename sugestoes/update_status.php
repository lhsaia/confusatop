<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

header('Content-Type: application/json; charset=utf-8');

$isAdmin = (isset($_SESSION['admin_status']) && $_SESSION['admin_status'] == 1);

if (!$isAdmin) {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado. Apenas administradores podem alterar status.']);
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$status = isset($_POST['status']) ? (int)$_POST['status'] : -1;

if ($id <= 0 || $status < 0 || $status > 3) {
    echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos.']);
    exit;
}

include_once($_SERVER['DOCUMENT_ROOT'] . "/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/objetos/suggestion.php");

$database = new Database();
$db = $database->getConnection();

$suggestion = new Suggestion($db);

if ($suggestion->updateStatus($id, $status)) {
    echo json_encode(['success' => true, 'id' => $id, 'status' => $status]);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar status no banco de dados.']);
}
?>
