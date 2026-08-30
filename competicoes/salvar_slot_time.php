<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

if(!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true){
    die(json_encode(['success' => false, 'error' => 'Login necessário']));
}

$idCompeticao = isset($_POST['id_competicao']) ? intval($_POST['id_competicao']) : 0;
$codigoTime = isset($_POST['codigo_time']) ? intval($_POST['codigo_time']) : 0;
$slotName = isset($_POST['slot']) ? trim($_POST['slot']) : '';

if($idCompeticao <= 0 || $codigoTime <= 0){
    die(json_encode(['success' => false, 'error' => 'Parâmetros inválidos']));
}

include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/config/sqliteDatabase.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/competicao_clube.php");

$database = new Database();
$db = $database->getConnection();
$competicao = new Competicao_clube($db);

$compInfo = $competicao->readInfo($idCompeticao);
$dono = isset($compInfo['dono']) ? (int)$compInfo['dono'] : 0;
$isAdmin = (isset($_SESSION['admin_status']) && $_SESSION['admin_status'] == 1);

if (!$isAdmin && $_SESSION['user_id'] != $dono) {
    die(json_encode(['success' => false, 'error' => 'Apenas o organizador da competição pode definir os slots do sorteio.']));
}

$stCheck = $db->prepare("SELECT slot FROM competicao_times WHERE id_competicao = :idComp AND codigo_time = :cod LIMIT 1");
$stCheck->bindParam(':idComp', $idCompeticao);
$stCheck->bindParam(':cod', $codigoTime);
$stCheck->execute();
$rCheck = $stCheck->fetch(PDO::FETCH_ASSOC);
if ($rCheck && !empty($rCheck['slot'])) {
    die(json_encode(['success' => false, 'error' => 'O slot desta vaga já foi definido e bloqueado no sorteio.']));
}

if($competicao->definirSlotTime($idCompeticao, $codigoTime, $slotName)){
    echo json_encode(['success' => true, 'slot' => $slotName]);
} else {
    echo json_encode(['success' => false, 'error' => 'Falha ao salvar o slot']);
}
?>
