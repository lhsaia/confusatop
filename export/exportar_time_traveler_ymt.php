<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
require_once $_SERVER['DOCUMENT_ROOT'] . "/lib/functions.php";

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
include_once($_SERVER['DOCUMENT_ROOT'] . "/objetos/tecnico.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/objetos/estadio.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/objetos/clima.php");

$database = new Database();
$db = $database->getConnection();

$timeObj = new Time($db);
$jogadorObj = new Jogador($db);
$tecnicoObj = new Tecnico($db);
$estadioObj = new Estadio($db);
$climaObj = new Clima($db);

$infoClube = $timeObj->readInfo($idTime);
if (!$infoClube || empty($infoClube['Nome'])) {
    die("Clube não encontrado.");
}

// 1. Obter dados do elenco histórico chamando a lógica de back-tracking
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
$elencoHistorico = [];

if (!empty($candidatosList)) {
    $inPlaceholders = implode(',', array_fill(0, count($candidatosList), '?'));

    $queryJogadores = "
        SELECT 
            j.ID as idJogador,
            j.Nome as nomeJogador,
            j.Nascimento,
            j.Nivel,
            j.Mentalidade,
            j.CobradorFalta,
            j.StringPosicoes,
            j.Determinacao,
            j.DeterminacaoOriginal,
            j.Marcacao, j.Desarme, j.VisaoJogo, j.Movimentacao, j.Cruzamentos, j.Cabeceamento,
            j.Tecnica, j.ControleBola, j.Finalizacao, j.FaroGol, j.Velocidade, j.Forca,
            j.Reflexos, j.Seguranca, j.Saidas, j.JogoAereo, j.Lancamentos, j.DefesaPenaltis,
            p.bandeira as Nacionalidade,
            cj.clube as clubeAtualId,
            COALESCE(cj.capitao, 0) as capitao,
            COALESCE(cj.cobrancaPenalti, 0) as cobrancaPenalti,
            COALESCE(cj.titularidade, -1) as titularidade,
            COALESCE(cj.posicaoBase, 0) as posicaoBase,
            COALESCE(pos.Sigla, '') as siglaPosicao
        FROM jogador j
        LEFT JOIN paises p ON j.Pais = p.id
        LEFT JOIN contratos_jogador cj ON (cj.jogador = j.ID AND cj.tipoContrato = 0)
        LEFT JOIN posicoes pos ON pos.ID = cj.posicaoBase
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
            // Calcular idade na época
            $nasc = $jData['Nascimento'];
            $idade = 20;
            if ($nasc && $nasc !== '0000-00-00') {
                $diff = (new DateTime($nasc))->diff(new DateTime($dataAlvo));
                $idade = max(15, $diff->y);
            }
            $jData['Idade'] = $idade;

            // Se não tiver sigla de posição calculada
            if (empty($jData['siglaPosicao'])) {
                $posStr = $jData['StringPosicoes'] ?? '000000000000000';
                if (isset($posStr[0]) && $posStr[0] === '1') {
                    $jData['siglaPosicao'] = 'G';
                } else {
                    $mapPos = [1=>'LD', 2=>'LE', 3=>'Z', 4=>'AD', 5=>'AE', 6=>'V', 7=>'MD', 8=>'ME', 9=>'MC', 10=>'PD', 11=>'PE', 12=>'MA', 13=>'Am', 14=>'Aa'];
                    $sig = 'MC';
                    for ($idx = 1; $idx < 15; $idx++) {
                        if (isset($posStr[$idx]) && $posStr[$idx] === '1') {
                            $sig = $mapPos[$idx];
                            break;
                        }
                    }
                    $jData['siglaPosicao'] = $sig;
                }
            }

            // Normalizar atributos usando adjustAttributes para garantir consistência
            $isGoleiro = (isset($jData['StringPosicoes'][0]) && $jData['StringPosicoes'][0] === '1');
            if ($isGoleiro) {
                $modificados = adjustAttributes(
                    true,
                    $jData['Nivel'],
                    0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                    $jData['Reflexos'], $jData['Seguranca'], $jData['Saidas'], $jData['JogoAereo'], $jData['Lancamentos'], $jData['DefesaPenaltis']
                );
                $jData['Reflexos'] = $modificados['reflexos'];
                $jData['Seguranca'] = $modificados['seguranca'];
                $jData['Saidas'] = $modificados['saidas'];
                $jData['JogoAereo'] = $modificados['jogoAereo'];
                $jData['Lancamentos'] = $modificados['lancamentos'];
                $jData['DefesaPenaltis'] = $modificados['defesaPenaltis'];
            } else {
                $modificados = adjustAttributes(
                    false,
                    $jData['Nivel'],
                    $jData['Marcacao'], $jData['Desarme'], $jData['VisaoJogo'], $jData['Movimentacao'], $jData['Cruzamentos'],
                    $jData['Cabeceamento'], $jData['Tecnica'], $jData['ControleBola'], $jData['Finalizacao'], $jData['FaroGol'],
                    $jData['Velocidade'], $jData['Forca'], 0, 0, 0, 0, 0, 0
                );
                $jData['Marcacao'] = $modificados['marcacao'];
                $jData['Desarme'] = $modificados['desarme'];
                $jData['VisaoJogo'] = $modificados['visaoJogo'];
                $jData['Movimentacao'] = $modificados['movimentacao'];
                $jData['Cruzamentos'] = $modificados['cruzamentos'];
                $jData['Cabeceamento'] = $modificados['cabeceamento'];
                $jData['Tecnica'] = $modificados['tecnica'];
                $jData['ControleBola'] = $modificados['controleBola'];
                $jData['Finalizacao'] = $modificados['finalizacao'];
                $jData['FaroGol'] = $modificados['faroGol'];
                $jData['Velocidade'] = $modificados['velocidade'];
                $jData['Forca'] = $modificados['forca'];
            }

            $elencoHistorico[] = $jData;
        }
    }
}

// 2. Obter Técnico da Época
$tecnicoEpoca = null;
$stmtTecTransf = $db->prepare("
    SELECT 
        tt.tecnico, tt.clubeOrigem, tt.clubeDestino,
        COALESCE(NULLIF(tt.dataConclusao, '0000-00-00 00:00:00'), tt.data) as dtTransf,
        t.Nome as nomeTecnico, t.Nascimento, t.Nivel, t.Mentalidade, t.Estilo,
        p.sigla as siglaPais
    FROM transferencias_tecnico tt
    JOIN tecnico t ON tt.tecnico = t.ID
    LEFT JOIN paises p ON t.Pais = p.id
    WHERE (tt.clubeOrigem = ? OR tt.clubeDestino = ?) AND tt.status_execucao = 1
    ORDER BY dtTransf ASC, tt.ID ASC
");
$stmtTecTransf->execute([$idTime, $idTime]);
$transfsTec = $stmtTecTransf->fetchAll(PDO::FETCH_ASSOC);
$dateTimeAlvo = $dataAlvo . ' 23:59:59';

if (!empty($transfsTec)) {
    foreach ($transfsTec as $tt) {
        if ($tt['dtTransf'] <= $dateTimeAlvo) {
            if ((int)$tt['clubeDestino'] === $idTime) {
                $tecnicoEpoca = [
                    'id' => (int)$tt['tecnico'],
                    'Nome' => $tt['nomeTecnico'] . ($tt['siglaPais'] ? ' [' . $tt['siglaPais'] . ']' : ''),
                    'Idade' => 45,
                    'Nivel' => (int)$tt['Nivel'],
                    'Mentalidade' => (int)$tt['Mentalidade'],
                    'Estilo' => (int)$tt['Estilo']
                ];
            } else if ((int)$tt['clubeOrigem'] === $idTime) {
                $tecnicoEpoca = null;
            }
        }
    }
}

if (!$tecnicoEpoca) {
    $stmtTecAtual = $db->prepare("
        SELECT 
            t.ID as id,
            CONCAT(t.Nome, CASE WHEN p.sigla IS NOT NULL THEN CONCAT(' [', p.sigla, ']') ELSE '' END) as Nome,
            FLOOR((DATEDIFF(CURDATE(), t.Nascimento))/365) as Idade,
            t.Nivel, t.Mentalidade, t.Estilo
        FROM contratos_tecnico ct
        JOIN tecnico t ON ct.tecnico = t.ID
        LEFT JOIN paises p ON t.Pais = p.id
        WHERE ct.clube = ?
        LIMIT 1
    ");
    $stmtTecAtual->execute([$idTime]);
    $tecAtual = $stmtTecAtual->fetch(PDO::FETCH_ASSOC);
    if ($tecAtual) {
        $stmtCheck = $db->prepare("
            SELECT ID FROM transferencias_tecnico 
            WHERE tecnico = ? AND clubeDestino = ? AND status_execucao = 1 
              AND COALESCE(NULLIF(dataConclusao, '0000-00-00 00:00:00'), data) > ?
        ");
        $stmtCheck->execute([$tecAtual['id'], $idTime, $dateTimeAlvo]);
        if ($stmtCheck->rowCount() === 0) {
            $tecnicoEpoca = [
                'id' => (int)$tecAtual['id'],
                'Nome' => $tecAtual['Nome'],
                'Idade' => (int)($tecAtual['Idade'] ?: 45),
                'Nivel' => (int)$tecAtual['Nivel'],
                'Mentalidade' => (int)$tecAtual['Mentalidade'],
                'Estilo' => (int)$tecAtual['Estilo']
            ];
        }
    }
}

if (!$tecnicoEpoca) {
    $tecnicoEpoca = [
        'id' => 0,
        'Nome' => 'Comandante Interino',
        'Idade' => 45,
        'Nivel' => 50,
        'Mentalidade' => 4,
        'Estilo' => 1
    ];
}

// 3. Obter Estádio e Clima
$estadioInfo = $estadioObj->coletarEstadioTime($idTime);
$climaInfo = $climaObj->coletarClimaTime($idTime);

$estadioRow = !empty($estadioInfo) ? $estadioInfo[0] : [
    'id' => $idTime,
    'Nome' => $infoClube['Estadio'] ?: 'Estádio Municipal',
    'Capacidade' => $infoClube['Capacidade'] ?: 50000,
    'Clima' => 1,
    'Caldeirao' => 0,
    'Altitude' => 0
];

$climaRow = !empty($climaInfo) ? $climaInfo[0] : [
    'id' => 1,
    'Nome' => 'Temperado',
    'TempVerao' => '28',
    'EstiloVerao' => 'Sol',
    'TempOutono' => '20',
    'EstiloOutono' => 'Nublado',
    'TempInverno' => '12',
    'EstiloInverno' => 'Chuva',
    'TempPrimavera' => '22',
    'EstiloPrimavera' => 'Sol',
    'Hemisferio' => '1'
];

// 4. Imagens em Base64
$escudoPath = $_SERVER['DOCUMENT_ROOT'] . "/images/escudos/" . $infoClube["Escudo"];
$escudoBase64 = "";
$formatoEscudo = "null";
if (!empty($infoClube["Escudo"]) && $infoClube["Escudo"] != "0.png" && file_exists($escudoPath) && is_file($escudoPath)) {
    $escContent = @file_get_contents($escudoPath);
    if ($escContent !== false) {
        $escudoBase64 = base64_encode($escContent);
        $formatoEscudo = pathinfo($infoClube["Escudo"], PATHINFO_EXTENSION) ?: "png";
    }
}

$uni1Path = $_SERVER['DOCUMENT_ROOT'] . "/images/uniformes/" . $infoClube["Uniforme1"];
$uni1Base64 = "";
$formatoUni1 = "null";
if (!empty($infoClube["Uniforme1"]) && $infoClube["Uniforme1"] != "semclube1.png" && file_exists($uni1Path) && is_file($uni1Path)) {
    $u1Content = @file_get_contents($uni1Path);
    if ($u1Content !== false) {
        $uni1Base64 = base64_encode($u1Content);
        $formatoUni1 = pathinfo($infoClube["Uniforme1"], PATHINFO_EXTENSION) ?: "png";
    }
}

$uni2Path = $_SERVER['DOCUMENT_ROOT'] . "/images/uniformes/" . $infoClube["Uniforme2"];
$uni2Base64 = "";
$formatoUni2 = "null";
if (!empty($infoClube["Uniforme2"]) && $infoClube["Uniforme2"] != "semclube2.png" && file_exists($uni2Path) && is_file($uni2Path)) {
    $u2Content = @file_get_contents($uni2Path);
    if ($u2Content !== false) {
        $uni2Base64 = base64_encode($u2Content);
        $formatoUni2 = pathinfo($infoClube["Uniforme2"], PATHINFO_EXTENSION) ?: "png";
    }
}

$arquivoEsc = ($escudoBase64 !== "") ? "Escudos/team" . $idTime . ".png" : "null";
$arquivoUni1 = ($uni1Base64 !== "") ? "Uniformes/1-team" . $idTime . ".png" : "null";
$arquivoUni2 = ($uni2Base64 !== "") ? "Uniformes/2-team" . $idTime . ".png" : "null";

// 5. Organizar Escalação (Garantir 11 titulares)
$titulares = [];
$outros = [];

foreach ($elencoHistorico as $jog) {
    if ((int)$jog['titularidade'] === 1 && count($titulares) < 11) {
        $titulares[] = $jog;
    } else {
        $outros[] = $jog;
    }
}

while (count($titulares) < 11 && !empty($outros)) {
    $titulares[] = array_shift($outros);
}

$capitaoId = 0;
foreach ($titulares as $t) {
    if ((int)$t['capitao'] === 1) {
        $capitaoId = (int)$t['idJogador'];
        break;
    }
}
if ($capitaoId === 0 && !empty($titulares)) {
    $capitaoId = (int)$titulares[0]['idJogador'];
}

$penaltis = [];
for ($posCob = 1; $posCob <= 3; $posCob++) {
    foreach ($titulares as $t) {
        if ((int)$t['cobrancaPenalti'] === $posCob) {
            $penaltis[] = (int)$t['idJogador'];
            break;
        }
    }
}
if (empty($penaltis) && !empty($titulares)) {
    for ($i = 0; $i < min(3, count($titulares)); $i++) {
        $penaltis[] = (int)$titulares[$i]['idJogador'];
    }
}

// 6. Montar Estrutura XML Oficial do .YMT
$nomeTime = $infoClube['Nome'] ?? 'Clube';
$siglaTime = $infoClube['TresLetras'] ?? 'CLU';
$estadioId = (int)($infoClube['Estadio'] ?? 0);
$uni1cor1 = $infoClube['Uni1Cor1'] ?? '255255255';
$uni1cor2 = $infoClube['Uni1Cor2'] ?? '000000000';
$uni1cor3 = $infoClube['Uni1Cor3'] ?? '000000000';
$uni2cor1 = $infoClube['Uni2Cor1'] ?? '255255255';
$uni2cor2 = $infoClube['Uni2Cor2'] ?? '000000000';
$uni2cor3 = $infoClube['Uni2Cor3'] ?? '000000000';
$maxTorcedores = $infoClube['MaxTorcedores'] ?? 50000;
$fidelidade = $infoClube['Fidelidade'] ?? 80;

$xml = "<clubeExportado>\n";
$xml .= " <clube>\n";
$xml .= "  <ID>" . $idTime . "</ID>\n";
$xml .= "  <Nome>" . htmlspecialchars($nomeTime) . "</Nome>\n";
$xml .= "  <TresLetras>" . htmlspecialchars($siglaTime) . "</TresLetras>\n";
$xml .= "  <bdEstadio>" . $estadioId . "</bdEstadio>\n";
$xml .= "  <Escudo>" . $arquivoEsc . "</Escudo>\n";
$xml .= "  <Uni1Cor1>" . $uni1cor1 . "</Uni1Cor1>\n";
$xml .= "  <Uni1Cor2>" . $uni1cor2 . "</Uni1Cor2>\n";
$xml .= "  <Uni1Cor3>" . $uni1cor3 . "</Uni1Cor3>\n";
$xml .= "  <Uniforme1>" . $arquivoUni1 . "</Uniforme1>\n";
$xml .= "  <Uni2Cor1>" . $uni2cor1 . "</Uni2Cor1>\n";
$xml .= "  <Uni2Cor2>" . $uni2cor2 . "</Uni2Cor2>\n";
$xml .= "  <Uni2Cor3>" . $uni2cor3 . "</Uni2Cor3>\n";
$xml .= "  <Uniforme2>" . $arquivoUni2 . "</Uniforme2>\n";
$xml .= "  <MaxTorcedores>" . $maxTorcedores . "</MaxTorcedores>\n";
$xml .= "  <Fidelidade>" . $fidelidade . "</Fidelidade>\n";
$xml .= "  <numJogadores>" . count($elencoHistorico) . "</numJogadores>\n";
$xml .= "  <numReservas>0</numReservas>\n";
$xml .= "  <Moral>100</Moral>\n";
$xml .= "  <bonusContraAtaque>0</bonusContraAtaque>\n";
$xml .= "  <cobPenaltis/>\n";
$xml .= " </clube>\n";

$xml .= " <elenco>\n";
$xml .= "  <Clube>" . $idTime . "</Clube>\n";
$xml .= "  <Jogador>\n";
foreach ($elencoHistorico as $j) {
    $xml .= "   <int>" . (int)$j['idJogador'] . "</int>\n";
}
$xml .= "  </Jogador>\n";
$xml .= "  <Tecnico>" . $tecnicoEpoca['id'] . "</Tecnico>\n";
$xml .= " </elenco>\n";

$xml .= " <escalacao>\n";
$xml .= "  <Clube>" . $idTime . "</Clube>\n";
$xml .= "  <Pos>\n";
for ($i = 0; $i < min(11, count($titulares)); $i++) {
    $xml .= "   <string>" . $titulares[$i]['siglaPosicao'] . "</string>\n";
}
$xml .= "  </Pos>\n";
$xml .= "  <Jogador>\n";
for ($i = 0; $i < min(11, count($titulares)); $i++) {
    $xml .= "   <int>" . (int)$titulares[$i]['idJogador'] . "</int>\n";
}
$xml .= "  </Jogador>\n";
$xml .= "  <Capitao>" . $capitaoId . "</Capitao>\n";
$xml .= "  <Penalti>\n";
foreach ($penaltis as $pId) {
    $xml .= "   <int>" . $pId . "</int>\n";
}
$xml .= "  </Penalti>\n";
$xml .= "  <JogadorImportado/>\n";
$xml .= "  <CapitaoOriginal>0</CapitaoOriginal>\n";
$xml .= "  <PenaltisOriginais/>\n";
$xml .= " </escalacao>\n";

$xml .= " <jogadores>\n";
foreach ($elencoHistorico as $j) {
    $xml .= "  <jogador>\n";
    $xml .= "   <ID>" . (int)$j['idJogador'] . "</ID>\n";
    $xml .= "   <Nome>" . htmlspecialchars($j['nomeJogador']) . "</Nome>\n";
    $xml .= "   <Idade>" . (int)$j['Idade'] . "</Idade>\n";
    $xml .= "   <Nivel>" . (int)$j['Nivel'] . "</Nivel>\n";
    $xml .= "   <Potencial>0</Potencial>\n";
    $xml .= "   <CrescBase>0</CrescBase>\n";
    $xml .= "   <Mentalidade>" . (int)($j['Mentalidade'] ?: 1) . "</Mentalidade>\n";
    $xml .= "   <CobradorFalta>" . (int)($j['CobradorFalta'] ?: 0) . "</CobradorFalta>\n";
    $xml .= "   <apto>true</apto>\n";
    $xml .= "  </jogador>\n";
}
$xml .= " </jogadores>\n";

$xml .= " <nacionalidades>\n";
foreach ($elencoHistorico as $j) {
    $xml .= "  <string>" . htmlspecialchars($j['Nacionalidade'] ?: '-') . "</string>\n";
}
$xml .= " </nacionalidades>\n";

$xml .= " <tecnico>\n";
$xml .= "  <ID>" . $tecnicoEpoca['id'] . "</ID>\n";
$xml .= "  <Nome>" . htmlspecialchars($tecnicoEpoca['Nome']) . "</Nome>\n";
$xml .= "  <Idade>" . $tecnicoEpoca['Idade'] . "</Idade>\n";
$xml .= "  <Nivel>" . $tecnicoEpoca['Nivel'] . "</Nivel>\n";
$xml .= "  <Mentalidade>" . $tecnicoEpoca['Mentalidade'] . "</Mentalidade>\n";
$xml .= "  <Estilo>" . $tecnicoEpoca['Estilo'] . "</Estilo>\n";
$xml .= " </tecnico>\n";

$xml .= " <estadio>\n";
$xml .= "  <ID>" . (int)$estadioRow['id'] . "</ID>\n";
$xml .= "  <Nome>" . htmlspecialchars($estadioRow['Nome']) . "</Nome>\n";
$xml .= "  <Capacidade>" . (int)$estadioRow['Capacidade'] . "</Capacidade>\n";
$xml .= "  <bdClima>" . (int)$estadioRow['Clima'] . "</bdClima>\n";
$xml .= "  <Altitude>" . (int)$estadioRow['Altitude'] . "</Altitude>\n";
$xml .= "  <Caldeirao>" . (int)$estadioRow['Caldeirao'] . "</Caldeirao>\n";
$xml .= " </estadio>\n";

$xml .= " <clima>\n";
$xml .= "  <ID>" . (int)$climaRow['id'] . "</ID>\n";
$xml .= "  <Nome>" . htmlspecialchars($climaRow['Nome']) . "</Nome>\n";
$xml .= "  <TempVerao>" . $climaRow['TempVerao'] . "</TempVerao>\n";
$xml .= "  <EstiloVerao>" . $climaRow['EstiloVerao'] . "</EstiloVerao>\n";
$xml .= "  <TempOutono>" . $climaRow['TempOutono'] . "</TempOutono>\n";
$xml .= "  <EstiloOutono>" . $climaRow['EstiloOutono'] . "</EstiloOutono>\n";
$xml .= "  <TempInverno>" . $climaRow['TempInverno'] . "</TempInverno>\n";
$xml .= "  <EstiloInverno>" . $climaRow['EstiloInverno'] . "</EstiloInverno>\n";
$xml .= "  <TempPrimavera>" . $climaRow['TempPrimavera'] . "</TempPrimavera>\n";
$xml .= "  <EstiloPrimavera>" . $climaRow['EstiloPrimavera'] . "</EstiloPrimavera>\n";
$xml .= "  <Hemisferio>" . $climaRow['Hemisferio'] . "</Hemisferio>\n";
$xml .= " </clima>\n";

$xml .= " <atributosJogador>\n";
foreach ($elencoHistorico as $j) {
    $posStr = $j['StringPosicoes'] ?? '000000000000000';
    if (!isset($posStr[0]) || $posStr[0] !== '1') {
        $xml .= "  <atributosJogador>\n";
        $xml .= "   <Jogador>" . (int)$j['idJogador'] . "</Jogador>\n";
        $xml .= "   <Marcacao>" . $j['Marcacao'] . "</Marcacao>\n";
        $xml .= "   <Desarme>" . $j['Desarme'] . "</Desarme>\n";
        $xml .= "   <VisaoJogo>" . $j['VisaoJogo'] . "</VisaoJogo>\n";
        $xml .= "   <Movimentacao>" . $j['Movimentacao'] . "</Movimentacao>\n";
        $xml .= "   <Cruzamentos>" . $j['Cruzamentos'] . "</Cruzamentos>\n";
        $xml .= "   <Cabeceamento>" . $j['Cabeceamento'] . "</Cabeceamento>\n";
        $xml .= "   <Tecnica>" . $j['Tecnica'] . "</Tecnica>\n";
        $xml .= "   <ControleBola>" . $j['ControleBola'] . "</ControleBola>\n";
        $xml .= "   <Finalizacao>" . $j['Finalizacao'] . "</Finalizacao>\n";
        $xml .= "   <FaroGol>" . $j['FaroGol'] . "</FaroGol>\n";
        $xml .= "   <Velocidade>" . $j['Velocidade'] . "</Velocidade>\n";
        $xml .= "   <Forca>" . $j['Forca'] . "</Forca>\n";
        $xml .= "   <Determinacao>" . (int)($j['Determinacao'] ?: 1) . "</Determinacao>\n";
        $xml .= "   <DeterminacaoOriginal>" . (int)($j['DeterminacaoOriginal'] ?: 1) . "</DeterminacaoOriginal>\n";
        $xml .= "   <CondicaoFisica>100.0</CondicaoFisica>\n";
        $xml .= "   <modificador>1.0</modificador>\n";
        $xml .= "  </atributosJogador>\n";
    }
}
$xml .= " </atributosJogador>\n";

$xml .= " <atributosGoleiro>\n";
foreach ($elencoHistorico as $j) {
    $posStr = $j['StringPosicoes'] ?? '000000000000000';
    if (isset($posStr[0]) && $posStr[0] === '1') {
        $xml .= "  <atributosGoleiro>\n";
        $xml .= "   <Goleiro>" . (int)$j['idJogador'] . "</Goleiro>\n";
        $xml .= "   <Reflexos>" . $j['Reflexos'] . "</Reflexos>\n";
        $xml .= "   <Seguranca>" . $j['Seguranca'] . "</Seguranca>\n";
        $xml .= "   <Saidas>" . $j['Saidas'] . "</Saidas>\n";
        $xml .= "   <JogoAereo>" . $j['JogoAereo'] . "</JogoAereo>\n";
        $xml .= "   <Lancamentos>" . $j['Lancamentos'] . "</Lancamentos>\n";
        $xml .= "   <DefesaPenaltis>" . $j['DefesaPenaltis'] . "</DefesaPenaltis>\n";
        $xml .= "   <Determinacao>" . (int)($j['Determinacao'] ?: 1) . "</Determinacao>\n";
        $xml .= "   <DeterminacaoOriginal>" . (int)($j['DeterminacaoOriginal'] ?: 1) . "</DeterminacaoOriginal>\n";
        $xml .= "   <CondicaoFisica>100.0</CondicaoFisica>\n";
        $xml .= "  </atributosGoleiro>\n";
    }
}
$xml .= " </atributosGoleiro>\n";

$xml .= " <posicoesJogador>\n";
foreach ($elencoHistorico as $j) {
    $posStr = str_pad($j['StringPosicoes'] ?? '0', 15, '0');
    $xml .= "  <posicoes>\n";
    $xml .= "   <Jogador>" . (int)$j['idJogador'] . "</Jogador>\n";
    $xml .= "   <G>" . ($posStr[0] === '1' ? 'true' : 'false') . "</G>\n";
    $xml .= "   <LD>" . ($posStr[1] === '1' ? 'true' : 'false') . "</LD>\n";
    $xml .= "   <LE>" . ($posStr[2] === '1' ? 'true' : 'false') . "</LE>\n";
    $xml .= "   <Z>" . ($posStr[3] === '1' ? 'true' : 'false') . "</Z>\n";
    $xml .= "   <AD>" . ($posStr[4] === '1' ? 'true' : 'false') . "</AD>\n";
    $xml .= "   <AE>" . ($posStr[5] === '1' ? 'true' : 'false') . "</AE>\n";
    $xml .= "   <V>" . ($posStr[6] === '1' ? 'true' : 'false') . "</V>\n";
    $xml .= "   <MD>" . ($posStr[7] === '1' ? 'true' : 'false') . "</MD>\n";
    $xml .= "   <ME>" . ($posStr[8] === '1' ? 'true' : 'false') . "</ME>\n";
    $xml .= "   <MC>" . ($posStr[9] === '1' ? 'true' : 'false') . "</MC>\n";
    $xml .= "   <PD>" . ($posStr[10] === '1' ? 'true' : 'false') . "</PD>\n";
    $xml .= "   <PE>" . ($posStr[11] === '1' ? 'true' : 'false') . "</PE>\n";
    $xml .= "   <MA>" . ($posStr[12] === '1' ? 'true' : 'false') . "</MA>\n";
    $xml .= "   <Am>" . ($posStr[13] === '1' ? 'true' : 'false') . "</Am>\n";
    $xml .= "   <Aa>" . ($posStr[14] === '1' ? 'true' : 'false') . "</Aa>\n";
    $xml .= "  </posicoes>\n";
}
$xml .= " </posicoesJogador>\n";

$xml .= " <escudoBase64>" . $escudoBase64 . "</escudoBase64>\n";
$xml .= " <uniforme1Base64>" . $uni1Base64 . "</uniforme1Base64>\n";
$xml .= " <uniforme2Base64>" . $uni2Base64 . "</uniforme2Base64>\n";
$xml .= " <formatoEscudoBase64>" . $formatoEscudo . "</formatoEscudoBase64>\n";
$xml .= " <formatoUniforme1Base64>" . $formatoUni1 . "</formatoUniforme1Base64>\n";
$xml .= " <formatoUniforme2Base64>" . $formatoUni2 . "</formatoUniforme2Base64>\n";
$xml .= "</clubeExportado>";

$safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $infoClube['Nome']);
$filename = $safeName . "_" . str_replace('-', '', $dataAlvo) . ".ymt";

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($xml));
echo $xml;
exit;
