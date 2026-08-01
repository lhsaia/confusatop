<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/competicao_clube.php");

$database = new Database();
$db = $database->getConnection();

$matchId = isset($_POST['matchId']) ? $_POST['matchId'] : die();

// Puxar info da partida para saber qual a competição
$stmt = $db->prepare("SELECT competicao FROM competicao_jogos WHERE id = :id");
$stmt->bindParam(':id', $matchId);
$stmt->execute();
$match = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$match) {
    die(json_encode(array("success" => false, "error" => "Partida não encontrada.")));
}
$idCompeticao = $match['competicao'];
$competicao = new Competicao_clube($db);
$compInfo = $competicao->readInfo($idCompeticao);
$dono = isset($compInfo['dono']) ? (int)$compInfo['dono'] : 0;
$isAdmin = (isset($_SESSION['admin_status']) && $_SESSION['admin_status'] == 1);

if (!$isAdmin && $_SESSION['user_id'] != $dono) {
    die(json_encode(array("success" => false, "error" => "Você não tem permissão para apagar jogos nesta competição.")));
}

$query = "DELETE FROM competicao_jogos WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $matchId);

if($stmt->execute()){
    echo json_encode(array("success" => true));
} else {
    echo json_encode(array("success" => false, "error" => "Erro ao apagar jogo."));
}
?>
