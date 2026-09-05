<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    die("Acesso negado.");
}

$idTime = isset($_GET['team']) ? (int)$_GET['team'] : 0;
$dataAlvo = isset($_GET['date']) ? trim($_GET['date']) : '';

if ($idTime <= 0 || empty($dataAlvo) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataAlvo)) {
    die("Parâmetros inválidos.");
}

include_once($_SERVER['DOCUMENT_ROOT'] . "/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/objetos/time.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/objetos/jogador.php");

$database = new Database();
$db = $database->getConnection();

$timeObj = new Time($db);
$infoClube = $timeObj->readInfo($idTime);
if (!$infoClube || empty($infoClube['Nome'])) {
    die("Clube não encontrado.");
}

// Obter dados do elenco histórico chamando a mesma lógica
$candidatosIds = [];
$stmtAtual = $db->prepare("SELECT jogador FROM contratos_jogador WHERE clube = ? AND tipoContrato = 0");
$stmtAtual->execute([$idTime]);
while ($row = $stmtAtual->fetch(PDO::FETCH_ASSOC)) {
    $candidatosIds[(int)$row['jogador']] = true;
}

$stmtTransf = $db->prepare("
    SELECT DISTINCT jogador 
    FROM transferencias 
    WHERE (clubeOrigem = ? OR clubeDestino = ?) 
      AND status_execucao = 1
");
$stmtTransf->execute([$idTime, $idTime]);
while ($row = $stmtTransf->fetch(PDO::FETCH_ASSOC)) {
    $candidatosIds[(int)$row['jogador']] = true;
}

$candidatosList = array_keys($candidatosIds);
$elencoIds = [];

if (!empty($candidatosList)) {
    $inPlaceholders = implode(',', array_fill(0, count($candidatosList), '?'));

    $queryJogadores = "
        SELECT j.ID as idJogador, cj.clube as clubeAtualId
        FROM jogador j
        LEFT JOIN contratos_jogador cj ON (cj.jogador = j.ID AND cj.tipoContrato = 0)
        WHERE j.ID IN ($inPlaceholders)
    ";
    $stmtJ = $db->prepare($queryJogadores);
    $stmtJ->execute($candidatosList);
    $jogadoresMap = [];
    while ($row = $stmtJ->fetch(PDO::FETCH_ASSOC)) {
        $jogadoresMap[(int)$row['idJogador']] = $row;
    }

    $queryAllTransf = "
        SELECT t.jogador as idJogador, t.clubeOrigem, t.clubeDestino, t.dataConclusao, t.data as dataCriacao
        FROM transferencias t
        WHERE t.jogador IN ($inPlaceholders)
          AND t.status_execucao = 1
        ORDER BY COALESCE(NULLIF(t.dataConclusao, '0000-00-00 00:00:00'), t.data) ASC, t.ID ASC
    ";
    $stmtT = $db->prepare($queryAllTransf);
    $stmtT->execute($candidatosList);
    $transfPorJogador = [];
    while ($t = $stmtT->fetch(PDO::FETCH_ASSOC)) {
        $jId = (int)$t['idJogador'];
        if (!isset($transfPorJogador[$jId])) {
            $transfPorJogador[$jId] = [];
        }
        $transfPorJogador[$jId][] = $t;
    }

    $dateTimeAlvo = $dataAlvo . ' 23:59:59';

    foreach ($jogadoresMap as $jId => $jData) {
        $transfs = $transfPorJogador[$jId] ?? [];
        $clubeAtual = (int)($jData['clubeAtualId'] ?? 0);

        $transfAteData = [];
        $transfPosData = [];

        foreach ($transfs as $t) {
            $dt = !empty($t['dataConclusao']) && $t['dataConclusao'] !== '0000-00-00 00:00:00' ? $t['dataConclusao'] : $t['dataCriacao'];
            if ($dt <= $dateTimeAlvo) {
                $transfAteData[] = $t;
            } else {
                $transfPosData[] = $t;
            }
        }

        $estavaNoClube = false;
        if (!empty($transfAteData)) {
            $ultimaAte = end($transfAteData);
            if ((int)$ultimaAte['clubeDestino'] === $idTime) {
                $estavaNoClube = true;
            }
        } else {
            if (!empty($transfPosData)) {
                $primeiraPos = $transfPosData[0];
                if ((int)$primeiraPos['clubeOrigem'] === $idTime) {
                    $estavaNoClube = true;
                }
            } else {
                if ($clubeAtual === $idTime) {
                    $estavaNoClube = true;
                }
            }
        }

        if ($estavaNoClube) {
            $elencoIds[] = $jId;
        }
    }
}

// Montar arquivo XML no formato .ymt oficial do simulador
$nomeTime = htmlspecialchars($infoClube['Nome'] ?? 'Clube');
$siglaTime = htmlspecialchars($infoClube['TresLetras'] ?? 'CLU');
$estadioId = (int)($infoClube['Estadio'] ?? 0);
$escudoTime = !empty($infoClube['Escudo']) ? "Escudos/team" . $idTime . ".png" : "null";
$uni1 = !empty($infoClube['Uniforme1']) ? "Uniformes/1-team" . $idTime . ".png" : "null";
$uni2 = !empty($infoClube['Uniforme2']) ? "Uniformes/2-team" . $idTime . ".png" : "null";
$uni1cor1 = $infoClube['Uni1Cor1'] ?? '255255255';
$uni1cor2 = $infoClube['Uni1Cor2'] ?? '000000000';
$uni1cor3 = $infoClube['Uni1Cor3'] ?? '000000000';
$uni2cor1 = $infoClube['Uni2Cor1'] ?? '255255255';
$uni2cor2 = $infoClube['Uni2Cor2'] ?? '000000000';
$uni2cor3 = $infoClube['Uni2Cor3'] ?? '000000000';
$maxTorcedores = $infoClube['MaxTorcedores'] ?? 50000;
$fidelidade = $infoClube['Fidelidade'] ?? 80;

$xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
$xml .= "<clubeExportado>\n";
$xml .= " <clube>\n";
$xml .= "  <ID>" . $idTime . "</ID>\n";
$xml .= "  <Nome>" . $nomeTime . " (" . date('d.m.Y', strtotime($dataAlvo)) . ")</Nome>\n";
$xml .= "  <TresLetras>" . $siglaTime . "</TresLetras>\n";
$xml .= "  <bdEstadio>" . $estadioId . "</bdEstadio>\n";
$xml .= "  <Escudo>" . $escudoTime . "</Escudo>\n";
$xml .= "  <Uni1Cor1>" . $uni1cor1 . "</Uni1Cor1>\n";
$xml .= "  <Uni1Cor2>" . $uni1cor2 . "</Uni1Cor2>\n";
$xml .= "  <Uni1Cor3>" . $uni1cor3 . "</Uni1Cor3>\n";
$xml .= "  <Uniforme1>" . $uni1 . "</Uniforme1>\n";
$xml .= "  <Uni2Cor1>" . $uni2cor1 . "</Uni2Cor1>\n";
$xml .= "  <Uni2Cor2>" . $uni2cor2 . "</Uni2Cor2>\n";
$xml .= "  <Uni2Cor3>" . $uni2cor3 . "</Uni2Cor3>\n";
$xml .= "  <Uniforme2>" . $uni2 . "</Uniforme2>\n";
$xml .= "  <MaxTorcedores>" . $maxTorcedores . "</MaxTorcedores>\n";
$xml .= "  <Fidelidade>" . $fidelidade . "</Fidelidade>\n";
$xml .= "  <numJogadores>" . count($elencoIds) . "</numJogadores>\n";
$xml .= "  <numReservas>0</numReservas>\n";
$xml .= "  <Moral>100</Moral>\n";
$xml .= "  <bonusContraAtaque>0</bonusContraAtaque>\n";
$xml .= "  <cobPenaltis/>\n";
$xml .= " </clube>\n";
$xml .= " <elenco>\n";
$xml .= "  <Clube>" . $idTime . "</Clube>\n";
$xml .= "  <Jogador>\n";
foreach ($elencoIds as $jogId) {
    $xml .= "   <int>" . (int)$jogId . "</int>\n";
}
$xml .= "  </Jogador>\n";
$xml .= " </elenco>\n";
$xml .= "</clubeExportado>";

$safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $infoClube['Nome']);
$filename = $safeName . "_" . str_replace('-', '', $dataAlvo) . ".ymt";

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($xml));
echo $xml;
exit;
