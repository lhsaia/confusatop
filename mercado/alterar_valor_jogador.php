<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
header('Content-Type: application/json; charset=utf-8');

$isAdmin = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true && isset($_SESSION['admin_status']) && (int)$_SESSION['admin_status'] === 1;

if (!$isAdmin) {
    echo json_encode(['success' => false, 'error' => 'Acesso negado. Apenas administradores podem alterar valores de atletas.']);
    exit;
}

$idJogador = isset($_POST['idJogador']) ? (int)$_POST['idJogador'] : 0;
$valorRaw = isset($_POST['valor']) ? $_POST['valor'] : null;

if ($idJogador <= 0 || $valorRaw === null) {
    echo json_encode(['success' => false, 'error' => 'Parâmetros inválidos.']);
    exit;
}

// Limpar e sanitizar valor recebido
$valorLimpo = trim((string)$valorRaw);
$valorLimpo = str_replace(['F$', 'f$', ' ', 'M', 'm', 'k', 'K', 'R$', '$'], '', $valorLimpo);

// Se tiver formato numérico com vírgula/ponto
if (strpos($valorLimpo, ',') !== false && strpos($valorLimpo, '.') !== false) {
    $valorLimpo = str_replace('.', '', $valorLimpo);
    $valorLimpo = str_replace(',', '.', $valorLimpo);
} elseif (strpos($valorLimpo, ',') !== false) {
    $valorLimpo = str_replace(',', '.', $valorLimpo);
}

$valorNumerico = (float)$valorLimpo;
if ($valorNumerico < 0) {
    $valorNumerico = 0.0;
}

include_once($_SERVER['DOCUMENT_ROOT'] . "/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/objetos/jogador.php");

$database = new Database();
$db = $database->getConnection();
$jogador = new Jogador($db);

if ($jogador->alterarValor($idJogador, $valorNumerico)) {
    if ($valorNumerico == 0) {
        $valorFormatado = "F$ -";
    } else {
        $valorFormatado = "F$ " . round($valorNumerico / 1000000, 2) . " M";
    }
    echo json_encode([
        'success' => true,
        'idJogador' => $idJogador,
        'valor' => $valorNumerico,
        'valor_formatado' => $valorFormatado
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Erro ao atualizar o valor no banco de dados.']);
}
