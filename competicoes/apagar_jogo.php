<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");

$database = new Database();
$db = $database->getConnection();

$matchId = isset($_POST['matchId']) ? $_POST['matchId'] : die();

$query = "DELETE FROM competicao_jogos WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $matchId);

if($stmt->execute()){
    echo json_encode(array("success" => true));
} else {
    echo json_encode(array("success" => false, "error" => "Erro ao apagar jogo."));
}
?>
