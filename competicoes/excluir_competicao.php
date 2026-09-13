<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Usuário não autenticado.']);
    exit;
}

$idCompeticao = isset($_POST['id']) ? intval($_POST['id']) : 0;
if ($idCompeticao <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID da competição inválido.']);
    exit;
}

include_once($_SERVER['DOCUMENT_ROOT'] . "/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/objetos/competicao_clube.php");

$database = new Database();
$db = $database->getConnection();
$competicao = new Competicao_clube($db);

$userId = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
$isAdmin = isset($_SESSION['admin_status']) && intval($_SESSION['admin_status']) === 1;

$result = $competicao->excluir($idCompeticao, $userId, $isAdmin);

echo json_encode($result);
