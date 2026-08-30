<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/competicao_clube.php");

$database = new Database();
$db = $database->getConnection();
$competicao = new Competicao_clube($db);

$idPartida = isset($_POST['idPartida']) ? $_POST['idPartida'] : die();

// Puxar info da partida para saber qual a competição e verificar permissão
$stmtPartida = $db->prepare("SELECT competicao_id as competicao FROM jogos_clube WHERE id = :id");
$stmtPartida->bindParam(':id', $idPartida);
$stmtPartida->execute();
$partida = $stmtPartida->fetch(PDO::FETCH_ASSOC);
if (!$partida) {
    die(json_encode(array("success" => false, "error" => "Partida não encontrada.")));
}
$idCompeticao = $partida['competicao'];
$compInfo = $competicao->readInfo($idCompeticao);
$dono = isset($compInfo['dono']) ? (int)$compInfo['dono'] : 0;
$isAdmin = (isset($_SESSION['admin_status']) && $_SESSION['admin_status'] == 1);

if (!$isAdmin && $_SESSION['user_id'] != $dono) {
    die(json_encode(array("success" => false, "error" => "Você não tem permissão para editar jogos nesta competição.")));
}

$arbitro = isset($_POST['arbitro']) ? $_POST['arbitro'] : 0;
$estadio = isset($_POST['estadio']) ? $_POST['estadio'] : 0;
$fase = isset($_POST['fase']) ? $_POST['fase'] : 0;
$data = isset($_POST['data']) ? $_POST['data'] : '';
$hora = isset($_POST['hora']) ? $_POST['hora'] : '';
$neutro = isset($_POST['neutro']) ? $_POST['neutro'] : 0;
$timeA_id = isset($_POST['timeA_id']) ? intval($_POST['timeA_id']) : null;
$timeB_id = isset($_POST['timeB_id']) ? intval($_POST['timeB_id']) : null;
$grupo = isset($_POST['grupo']) ? trim($_POST['grupo']) : null;

$datetime = $data . " " . $hora . ":00";

$subQuery = "";
if ($timeA_id !== null) {
    if ($timeA_id > 0) {
        $subQuery .= ", timeA_id = :timeA, timeA_nome = NULL";
    } else {
        $subQuery .= ", timeA_id = 0";
    }
}
if ($timeB_id !== null) {
    if ($timeB_id > 0) {
        $subQuery .= ", timeB_id = :timeB, timeB_nome = NULL";
    } else {
        $subQuery .= ", timeB_id = 0";
    }
}
if ($grupo !== null) {
    $subQuery .= ", grupo = :grupo";
}

$query = "UPDATE jogos_clube 
          SET arbitro_id = :arbitro, 
              estadio_id = :estadio, 
              fase = :fase, 
              data = :data, 
              neutro = :neutro 
              " . $subQuery . "
          WHERE id = :id";

$stmt = $db->prepare($query);
$stmt->bindParam(':arbitro', $arbitro);
$stmt->bindParam(':estadio', $estadio);
$stmt->bindParam(':fase', $fase);
$stmt->bindParam(':data', $datetime);
$stmt->bindParam(':neutro', $neutro);
if ($timeA_id !== null) {
    $stmt->bindParam(':timeA', $timeA_id);
}
if ($timeB_id !== null) {
    $stmt->bindParam(':timeB', $timeB_id);
}
if ($grupo !== null) {
    $stmt->bindParam(':grupo', $grupo);
}
$stmt->bindParam(':id', $idPartida);

if($stmt->execute()){
    echo json_encode(array("success" => true));
} else {
    echo json_encode(array("success" => false, "error" => "Erro ao atualizar banco de dados."));
}
?>
