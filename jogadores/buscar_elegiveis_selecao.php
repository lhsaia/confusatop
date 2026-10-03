<?php
if (!ob_start("ob_gzhandler")) {
    ob_start();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Usuário não logado']);
    exit;
}

$idSelecao = isset($_GET['idSelecao']) ? (int)$_GET['idSelecao'] : (isset($_POST['idSelecao']) ? (int)$_POST['idSelecao'] : 0);

if ($idSelecao <= 0) {
    echo json_encode(['success' => false, 'error' => 'Seleção não informada']);
    exit;
}

include_once($_SERVER['DOCUMENT_ROOT'] . "/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/objetos/time.php");

$database = new Database();
$db = $database->getConnection();
$time = new Time($db);

$infoSelecao = $time->readInfo($idSelecao);
if (!$infoSelecao || empty($infoSelecao['id'])) {
    echo json_encode(['success' => false, 'error' => 'Seleção não encontrada']);
    exit;
}

$statusSelecao = (int)($infoSelecao['status'] ?? 0);
if ($statusSelecao <= 0) {
    echo json_encode(['success' => false, 'error' => 'O clube informado não é uma seleção nacional']);
    exit;
}

$paisId = (int)($infoSelecao['pais_id'] ?? 0);
$sexoSelecao = (int)($infoSelecao['Sexo'] ?? 0);
$donoPais = (int)($infoSelecao['donoPais'] ?? 0);
$userLogado = (int)($_SESSION['user_id'] ?? 0);
$isImpersonated = !empty($_SESSION['impersonated']);

if ($donoPais !== $userLogado && !$isImpersonated) {
    echo json_encode(['success' => false, 'error' => 'Você não tem permissão para gerenciar convocações desta seleção']);
    exit;
}

// Filtro por categoria (limite de idade)
// status: 1 = Principal (sem limite), 2 = Olímpica (Sub-23/21), 3 = Sub-20 (<=20), 4 = Sub-18 (<=18)
$idadeMax = 999;
$dataMinima = '1900-01-01';
$temLimiteIdade = 0;
if ($statusSelecao == 2) {
    $idadeMax = 23;
    $temLimiteIdade = 1;
    $dataMinima = date('Y-m-d', strtotime("-23 years"));
} else if ($statusSelecao == 3) {
    $idadeMax = 20;
    $temLimiteIdade = 1;
    $dataMinima = date('Y-m-d', strtotime("-20 years"));
} else if ($statusSelecao == 4) {
    $idadeMax = 18;
    $temLimiteIdade = 1;
    $dataMinima = date('Y-m-d', strtotime("-18 years"));
}

// Mapa de posições carregado de uma só vez na memória (evita centenas de consultas repetidas no loop)
$mapPosicoes = [];
try {
    $stmtPos = $db->query("SELECT ID, Sigla FROM posicoes ORDER BY ID ASC");
    while ($pRow = $stmtPos->fetch(PDO::FETCH_ASSOC)) {
        $mapPosicoes[(int)$pRow['ID']] = $pRow['Sigla'];
    }
} catch (Exception $e) {
    // Fallback estático padrão caso tabela não responda
    $mapPosicoes = [
        1 => 'G', 2 => 'LD', 3 => 'Z', 4 => 'LE', 5 => 'L',
        6 => 'V', 7 => 'MD', 8 => 'MC', 9 => 'ME', 10 => 'MAD',
        11 => 'MAC', 12 => 'MAE', 13 => 'PD', 14 => 'CA', 15 => 'PE'
    ];
}

// Query ultrarrápida com JOINs diretos, filtro por país, gênero e idade indexada
$query = "
    SELECT 
        j.ID as idJogador,
        j.Nome as nomeJogador,
        j.Sexo as sexoJogador,
        j.Nascimento,
        TIMESTAMPDIFF(YEAR, j.Nascimento, CURDATE()) as idade,
        j.Nivel,
        j.StringPosicoes,
        j.valor,
        j.disponibilidade,
        j.foto,
        b.Nome as clubeAtual,
        b.Escudo as escudoClubeAtual,
        b.ID as idClubeAtual,
        IF(cj_conv.clube IS NOT NULL, 1, 0) as convocadoAqui,
        sel.Nome as outraSelecao
    FROM jogador j
    LEFT JOIN contratos_jogador c ON j.ID = c.jogador AND c.tipoContrato = 0
    LEFT JOIN clube b ON c.clube = b.ID
    LEFT JOIN contratos_jogador cj_conv ON j.ID = cj_conv.jogador AND cj_conv.clube = :idSelecao AND cj_conv.tipoContrato = :statusSelecao
    LEFT JOIN contratos_jogador cj_outra ON j.ID = cj_outra.jogador AND cj_outra.tipoContrato > 0 AND cj_outra.clube <> :idSelecao2
    LEFT JOIN clube sel ON cj_outra.clube = sel.ID
    WHERE j.Pais = :paisId
      AND j.Sexo = :sexoSelecao
      AND j.disponibilidade >= 0
      AND (:temLimiteIdade = 0 OR j.Nascimento >= :dataMinima)
    GROUP BY j.ID
    ORDER BY convocadoAqui DESC, j.Nivel DESC, j.Nome ASC
";

$stmt = $db->prepare($query);
$stmt->bindParam(':idSelecao', $idSelecao, PDO::PARAM_INT);
$stmt->bindParam(':statusSelecao', $statusSelecao, PDO::PARAM_INT);
$stmt->bindParam(':idSelecao2', $idSelecao, PDO::PARAM_INT);
$stmt->bindParam(':paisId', $paisId, PDO::PARAM_INT);
$stmt->bindParam(':sexoSelecao', $sexoSelecao, PDO::PARAM_INT);
$stmt->bindParam(':temLimiteIdade', $temLimiteIdade, PDO::PARAM_INT);
$stmt->bindParam(':dataMinima', $dataMinima, PDO::PARAM_STR);
$stmt->execute();

$jogadores = [];
$totalConvocados = 0;

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Formatação de posições em memória (O(1) sem I/O de banco)
    $strPos = $row['StringPosicoes'] ?? '';
    $posList = [];
    if (!empty($strPos)) {
        $len = strlen($strPos);
        for ($i = 0; $i < $len; $i++) {
            if ($strPos[$i] === '1' && isset($mapPosicoes[$i + 1])) {
                $posList[] = $mapPosicoes[$i + 1];
            }
        }
    }
    $row['posicoesFormatadas'] = implode('-', $posList);
    $row['convocadoAqui'] = ((int)$row['convocadoAqui'] > 0);
    if ($row['convocadoAqui']) {
        $totalConvocados++;
    }
    $jogadores[] = $row;
}

echo json_encode([
    'success' => true,
    'selecao' => [
        'id' => $idSelecao,
        'nome' => $infoSelecao['Nome'] ?? '',
        'status' => $statusSelecao,
        'sexo' => $sexoSelecao,
        'idadeMax' => $idadeMax,
        'totalConvocados' => $totalConvocados,
        'totalElegiveis' => count($jogadores)
    ],
    'jogadores' => $jogadores
]);
