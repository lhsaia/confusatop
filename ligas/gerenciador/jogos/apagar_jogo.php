<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
header('Content-Type: application/json');

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogos_clube.php");

if(!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']){
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit;
}

if($_SESSION['emTestes'] ?? false){
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Usuários em período de testes não podem excluir jogos.']);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$jogo = new Jogo($db);

$match_id = isset($_POST['match_id']) ? (int)$_POST['match_id'] : 0;

if($match_id <= 0){
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de jogo inválido.']);
    exit;
}

// Check ownership
$match_info = $jogo->getSingleMatchInfo($match_id);
if(!$match_info){
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Jogo não encontrado.']);
    exit;
}

$isOwner = ($_SESSION['user_id'] == $match_info['dono']);
$isAdmin = ($_SESSION['admin_status'] == '1');

if(!$isOwner && !$isAdmin){
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Você não tem permissão para apagar este jogo.']);
    exit;
}

try {
    $db->beginTransaction();

    // Delete Events
    $jogo->limparEventos($match_id);

    // Delete Lineup
    $jogo->limparEscalacao($match_id);

    // Delete Match
    $query = "DELETE FROM jogos_clube WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$match_id]);

    $db->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao apagar jogo: ' . $e->getMessage()]);
}
?>
