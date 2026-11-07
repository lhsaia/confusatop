<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

if(!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true){
    die(json_encode(['success' => false, 'error' => 'Acesso negado.']));
}

if($_SESSION['emTestes'] ?? false){
    die(json_encode(['success' => false, 'error' => 'Usuários em período de testes não podem apagar partidas.']));
}

include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/competicao_clube.php");
require_once __DIR__ . "/sumula_helper.php";

$database = new Database();
$db = $database->getConnection();

$matchId = isset($_POST['matchId']) ? intval($_POST['matchId']) : 0;
if ($matchId <= 0) {
    die(json_encode(['success' => false, 'error' => 'Partida inválida.']));
}

// Puxar info da partida para saber qual a competição e validar simulador_interno
$stmt = $db->prepare("SELECT id, competicao_id as competicao, simulador_interno, status, path FROM jogos_clube WHERE id = :id");
$stmt->bindParam(':id', $matchId, PDO::PARAM_INT);
$stmt->execute();
$match = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$match) {
    die(json_encode(array("success" => false, "error" => "Partida não encontrada.")));
}

// Blindagem: certificar-se de que a partida pertence ao simulador interno
if (intval($match['simulador_interno'] ?? 0) !== 1) {
    die(json_encode(array("success" => false, "error" => "Operação permitida apenas para partidas do simulador interno.")));
}

$idCompeticao = (int)$match['competicao'];
$competicao = new Competicao_clube($db);
$compInfo = $competicao->readInfo($idCompeticao);
$dono = isset($compInfo['dono']) ? (int)$compInfo['dono'] : 0;
$isAdmin = (isset($_SESSION['admin_status']) && (int)$_SESSION['admin_status'] === 1) || !empty($_SESSION['impersonated']);

if (!$isAdmin && (int)$_SESSION['user_id'] !== $dono) {
    die(json_encode(array("success" => false, "error" => "Você não tem permissão para apagar jogos nesta competição.")));
}

// Se o jogo já foi simulado/encerrado, APENAS ADMIN pode apagar
$isSimulado = (intval($match['status'] ?? 0) === 1);
if ($isSimulado && !$isAdmin) {
    die(json_encode(array("success" => false, "error" => "Apenas administradores podem apagar partidas que já foram simuladas.")));
}

// 1. Limpar eventos e escalações associados a este jogo
$stmtEv = $db->prepare("DELETE FROM jogos_clube_eventos WHERE id_jogo = :id");
$stmtEv->bindParam(':id', $matchId, PDO::PARAM_INT);
$stmtEv->execute();

$stmtEsc = $db->prepare("DELETE FROM jogos_clube_escalacao WHERE id_partida = :id");
$stmtEsc->bindParam(':id', $matchId, PDO::PARAM_INT);
$stmtEsc->execute();

// 2. Apagar a partida com blindagem estrita (id e simulador_interno = 1)
$query = "DELETE FROM jogos_clube WHERE id = :id AND simulador_interno = 1";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $matchId, PDO::PARAM_INT);

if($stmt->execute()){
    // 3. Se a partida possuía arquivo gerado (path), remover fisicamente os arquivos .hyl, .hyj, .png, .pdf
    if (!empty($match['path'])) {
        $nomeComposto = ($compInfo['ano'] ?? '') . " - " . ($compInfo['nome'] ?? '');
        apagarArquivosPartidaSimulada($nomeComposto, $match['path']);
    }

    echo json_encode(array("success" => true));
} else {
    echo json_encode(array("success" => false, "error" => "Erro ao apagar jogo."));
}
?>
