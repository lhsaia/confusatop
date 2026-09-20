<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
header('Content-Type: application/json; charset=utf-8');

$isAdmin = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true && isset($_SESSION['admin_status']) && (int)$_SESSION['admin_status'] === 1;

if (!$isAdmin) {
    echo json_encode(['success' => false, 'error' => 'Acesso negado. Apenas administradores podem recalcular valores de atletas.']);
    exit;
}

$idJogador = isset($_POST['idJogador']) ? (int)$_POST['idJogador'] : 0;

if ($idJogador <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID do atleta inválido.']);
    exit;
}

include_once($_SERVER['DOCUMENT_ROOT'] . "/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/objetos/jogador.php");

$database = new Database();
$db = $database->getConnection();
$jogador = new Jogador($db);

$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
$resultado = $jogador->recalcularPasseIndividual($idJogador, $userId);

if ($resultado && !empty($resultado['success'])) {
    $valorNum = (float)$resultado['valor'];
    if ($valorNum == 0) {
        $valorFormatado = "F$ -";
    } else {
        $valorFormatado = "F$ " . round($valorNum / 1000000, 2) . " M";
    }
    echo json_encode([
        'success' => true,
        'idJogador' => $idJogador,
        'valor' => $valorNum,
        'valor_formatado' => $valorFormatado,
        'salario' => (float)$resultado['salario']
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => $resultado['error'] ?? 'Erro ao recalcular passe do atleta.'
    ]);
}
