<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/competicao_clube.php");

$database = new Database();
$db = $database->getConnection();
$competicao = new Competicao_clube($db);

$idPartida = isset($_POST['idPartida']) ? $_POST['idPartida'] : die();
$arbitro = isset($_POST['arbitro']) ? $_POST['arbitro'] : 0;
$estadio = isset($_POST['estadio']) ? $_POST['estadio'] : 0;
$fase = isset($_POST['fase']) ? $_POST['fase'] : 0;
$data = isset($_POST['data']) ? $_POST['data'] : '';
$hora = isset($_POST['hora']) ? $_POST['hora'] : '';
$neutro = isset($_POST['neutro']) ? $_POST['neutro'] : 0;

$datetime = $data . " " . $hora . ":00";

$query = "UPDATE competicao_jogos 
          SET arbitro = :arbitro, 
              estadio = :estadio, 
              fase = :fase, 
              data = :data, 
              neutro = :neutro 
          WHERE id = :id";

$stmt = $db->prepare($query);
$stmt->bindParam(':arbitro', $arbitro);
$stmt->bindParam(':estadio', $estadio);
$stmt->bindParam(':fase', $fase);
$stmt->bindParam(':data', $datetime);
$stmt->bindParam(':neutro', $neutro);
$stmt->bindParam(':id', $idPartida);

if($stmt->execute()){
    echo json_encode(array("success" => true));
} else {
    echo json_encode(array("success" => false, "error" => "Erro ao atualizar banco de dados."));
}
?>
