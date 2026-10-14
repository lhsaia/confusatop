<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('America/Sao_Paulo');

// 1. Verificação de Acesso e Permissão
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Você precisa estar logado para simular amistosos.']);
    exit;
}

if (!empty($_SESSION['emTestes'])) {
    echo json_encode(['success' => false, 'error' => 'Usuários em período de testes não têm permissão para simular partidas oficiais.']);
    exit;
}

// 2. Parâmetros Recebidos
$paisA_id = isset($_POST['paisA']) ? (int)$_POST['paisA'] : 0;
$paisB_id = isset($_POST['paisB']) ? (int)$_POST['paisB'] : 0;
$categoria = isset($_POST['categoria']) ? (int)$_POST['categoria'] : 1;
$dataHoraInput = isset($_POST['data_hora']) ? trim((string)$_POST['data_hora']) : '';
$estadioIdInput = isset($_POST['estadio_id']) ? (int)$_POST['estadio_id'] : 0;
$neutro = isset($_POST['neutro']) ? ((int)$_POST['neutro'] === 1 ? 1 : 0) : 0;
$arbitroIdInput = isset($_POST['arbitro_id']) ? (int)$_POST['arbitro_id'] : 0;
$subirLive = isset($_POST['subir_live']) ? ((int)$_POST['subir_live'] === 1 ? 1 : 0) : 0;

if ($paisA_id <= 0 || $paisB_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Selecione as duas seleções para o confronto.']);
    exit;
}

if ($paisA_id === $paisB_id) {
    echo json_encode(['success' => false, 'error' => 'A seleção mandante e visitante não podem ser a mesma.']);
    exit;
}

if (!in_array($categoria, [1, 2, 3, 4])) {
    $categoria = 1;
}

// Data e Hora da Partida
if (!empty($dataHoraInput)) {
    $tsDataHora = strtotime($dataHoraInput);
    if ($tsDataHora === false) {
        $dataHora = date('Y-m-d H:i:s');
    } else {
        $dataHora = date('Y-m-d H:i:s', $tsDataHora);
    }
} else {
    $dataHora = date('Y-m-d H:i:s');
}

// 3. Lock de Concorrência
$lockFile = $_SERVER['DOCUMENT_ROOT'] . '/competicoes/hexacolor/simulation.lock';
$lockHandle = @fopen($lockFile, 'c+');
if (!$lockHandle) {
    echo json_encode(['success' => false, 'error' => 'Não foi possível inicializar o arquivo de lock de simulação.']);
    exit;
}

$startTime = time();
$acquired = false;
while (time() - $startTime < 45) {
    if (flock($lockHandle, LOCK_EX | LOCK_NB)) {
        $acquired = true;
        break;
    }
    usleep(500000); // 0.5s
}

if (!$acquired) {
    fclose($lockHandle);
    echo json_encode(['success' => false, 'error' => 'O simulador está ocupado processando outra partida no momento. Tente novamente em alguns segundos.']);
    exit;
}

register_shutdown_function(function() use ($lockHandle) {
    if (is_resource($lockHandle)) {
        flock($lockHandle, LOCK_UN);
        fclose($lockHandle);
    }
});

// 4. Conexão ao Banco MariaDB e Objetos
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/sqliteDatabase.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/objetos/competicao_clube.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/objetos/time.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/objetos/jogador.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/objetos/tecnico.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/objetos/estadio.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/objetos/clima.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/objetos/arbitros.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/competicoes/hexacolor/simulador_helper.php';

$database = new Database();
$db = $database->getConnection();
$competicaoObj = new Competicao_clube($db);
$timeObj = new Time($db);
$jogadorObj = new Jogador($db);
$tecnicoObj = new Tecnico($db);

// 5. Validar Países e Localizar Clubes de Seleção
$stPaisA = $db->prepare("SELECT id, nome, bandeira FROM paises WHERE id = ? LIMIT 1");
$stPaisA->execute([$paisA_id]);
$infoPaisA = $stPaisA->fetch(PDO::FETCH_ASSOC);

$stPaisB = $db->prepare("SELECT id, nome, bandeira FROM paises WHERE id = ? LIMIT 1");
$stPaisB->execute([$paisB_id]);
$infoPaisB = $stPaisB->fetch(PDO::FETCH_ASSOC);

if (!$infoPaisA || !$infoPaisB) {
    echo json_encode(['success' => false, 'error' => 'Um dos países selecionados não foi encontrado no banco de dados.']);
    exit;
}

$findOrCreateClubSelecao = function($paisId, $paisNome, $cat) use ($db) {
    $st = $db->prepare("SELECT * FROM clube WHERE Pais = :pais AND status = :cat AND (Sexo = 0 OR Sexo IS NULL) LIMIT 1");
    $st->execute([':pais' => $paisId, ':cat' => $cat]);
    $club = $st->fetch(PDO::FETCH_ASSOC);
    if ($club) return $club;

    // Se não existir clube para essa categoria, criar registro
    $sufixo = '';
    if ($cat == 2) $sufixo = ' [U21]';
    elseif ($cat == 3) $sufixo = ' [U20]';
    elseif ($cat == 4) $sufixo = ' [U18]';
    $nomeTime = $paisNome . $sufixo;
    $tresLetras = substr(strtoupper(preg_replace('/[^A-Za-z]/', '', $paisNome)), 0, 3);
    if (strlen($tresLetras) < 3) $tresLetras = str_pad($tresLetras, 3, 'X');

    $stIns = $db->prepare("
        INSERT INTO clube (Nome, TresLetras, Estadio, Escudo, Uni1Cor1, Uni1Cor2, Uni1Cor3, Uniforme1, Uni2Cor1, Uni2Cor2, Uni2Cor3, Uniforme2, MaxTorcedores, Fidelidade, Pais, status, Sexo)
        VALUES (:nome, :tl, 1, 'padrao.png', '#ffffff', '#0284c7', '#0f172a', 'padrao.png', '#0284c7', '#ffffff', '#0f172a', 'padrao.png', 50000, 5, :pais, :cat, 0)
    ");
    $stIns->execute([
        ':nome' => $nomeTime,
        ':tl' => $tresLetras,
        ':pais' => $paisId,
        ':cat' => $cat
    ]);
    $newId = (int)$db->lastInsertId();
    $stNew = $db->prepare("SELECT * FROM clube WHERE ID = ? LIMIT 1");
    $stNew->execute([$newId]);
    return $stNew->fetch(PDO::FETCH_ASSOC);
};

$clubA = $findOrCreateClubSelecao($paisA_id, $infoPaisA['nome'], $categoria);
$clubB = $findOrCreateClubSelecao($paisB_id, $infoPaisB['nome'], $categoria);

$idClubA = (int)$clubA['ID'];
$idClubB = (int)$clubB['ID'];

// 6. Garantir / Localizar Competição Oficial de Amistosos
$nomeCompAmistoso = ($categoria == 1) ? 'Amistosos Internacionais' : 'Amistosos Internacionais de Base';
$campeonatoRankingId = ($categoria == 1) ? 10 : 11;
$anoAtual = (int)date('Y');

$stComp = $db->prepare("
    SELECT cl.id, cl.nome, cl.ano 
    FROM competicao_lista cl
    JOIN competicao_opcoes co ON cl.id = co.id_competicao
    WHERE cl.nome = :nome AND co.tipo_times = 1
    ORDER BY cl.id DESC LIMIT 1
");
$stComp->execute([':nome' => $nomeCompAmistoso]);
$compRow = $stComp->fetch(PDO::FETCH_ASSOC);

if (!$compRow) {
    // Criar competição oficial
    $stInsComp = $db->prepare("
        INSERT INTO competicao_lista (nome, ano, federacao, logo, trofeu, sede, genero, tipo, dono, path)
        VALUES (:nome, :ano, 0, '0.png', NULL, 0, 0, 1, 0, :path)
    ");
    $pathComp = $anoAtual . " - " . $nomeCompAmistoso;
    $stInsComp->execute([
        ':nome' => $nomeCompAmistoso,
        ':ano' => $anoAtual,
        ':path' => $pathComp
    ]);
    $idCompeticao = (int)$db->lastInsertId();

    $stInsOpt = $db->prepare("
        INSERT INTO competicao_opcoes (id_competicao, tipo_times, campeonato_ranking, subir_live)
        VALUES (:idComp, 1, :campRank, :live)
    ");
    $stInsOpt->execute([
        ':idComp' => $idCompeticao,
        ':campRank' => $campeonatoRankingId,
        ':live' => $subirLive
    ]);
    $compRow = ['id' => $idCompeticao, 'nome' => $nomeCompAmistoso, 'ano' => $anoAtual];
}

$idCompeticao = (int)$compRow['id'];
$calendarName = $compRow['ano'] . " - " . $compRow['nome'];

// 7. Resolver Estádio e Clima
$idEstadio = $estadioIdInput;
if ($idEstadio <= 0) {
    $idEstadio = (int)($clubA['Estadio'] ?? 0);
}

$stEst = $db->prepare("
    SELECT e.*, c.Nome as nomeClima, c.TempVerao, c.EstiloVerao, c.TempOutono, c.EstiloOutono, c.TempInverno, c.EstiloInverno, c.TempPrimavera, c.EstiloPrimavera, c.Hemisferio 
    FROM estadio e 
    LEFT JOIN clima c ON e.Clima = c.ID 
    WHERE e.ID = :id LIMIT 1
");
$stEst->execute([':id' => $idEstadio]);
$rowEst = $stEst->fetch(PDO::FETCH_ASSOC);

if (!$rowEst) {
    // Fallback: primeiro estádio cadastrado do país ou do banco
    $stFallbackEst = $db->query("
        SELECT e.*, c.Nome as nomeClima, c.TempVerao, c.EstiloVerao, c.TempOutono, c.EstiloOutono, c.TempInverno, c.EstiloInverno, c.TempPrimavera, c.EstiloPrimavera, c.Hemisferio 
        FROM estadio e 
        LEFT JOIN clima c ON e.Clima = c.ID 
        ORDER BY e.ID ASC LIMIT 1
    ");
    $rowEst = $stFallbackEst->fetch(PDO::FETCH_ASSOC);
    $idEstadio = $rowEst ? (int)$rowEst['ID'] : 1;
}

// 8. Resolver Árbitro
$idArbitro = $arbitroIdInput;
$rowArb = null;
if ($idArbitro > 0) {
    $stArb = $db->prepare("SELECT * FROM arbitros WHERE id = ? LIMIT 1");
    $stArb->execute([$idArbitro]);
    $rowArb = $stArb->fetch(PDO::FETCH_ASSOC);
}
if (!$rowArb) {
    $stArb = $db->query("SELECT * FROM arbitros ORDER BY RAND() LIMIT 1");
    $rowArb = $stArb->fetch(PDO::FETCH_ASSOC);
    $idArbitro = $rowArb ? (int)$rowArb['id'] : 1;
}

// 9. Preparar SQLite para Execução do Motor
$hexacolorDir = $_SERVER['DOCUMENT_ROOT'] . '/competicoes/hexacolor';
$targetDbPath = $hexacolorDir . '/data/database.db3';
if (!is_dir($hexacolorDir . '/data')) {
    @mkdir($hexacolorDir . '/data', 0777, true);
}
if (file_exists($targetDbPath)) {
    @unlink($targetDbPath);
}

$liteDatabase = new SQLiteDatabase();
$liteDatabase->fileName = $targetDbPath;
$ldb = $liteDatabase->getConnection();
if (!$ldb) {
    echo json_encode(['success' => false, 'error' => 'Falha ao inicializar o banco de dados temporário de simulação.']);
    exit;
}

$liteDatabase->prepareTables();
$liteDatabase->initialMainValues();
$liteDatabase->competitionParameters();

// Compatibilidade da engine
$ldb->exec("CREATE TABLE IF NOT EXISTS `compatibilidade` (`versao` TEXT)");
foreach (['2.8', '2.9.1', '2.10', '2.13', '2.14'] as $v) {
    $ldb->exec("INSERT OR IGNORE INTO `compatibilidade` (`versao`) VALUES ('$v')");
}
$ldb->exec("DELETE FROM `jogadorpendente`");

// Inserir Trio de Arbitragem (Apenas o trio escolhido, para que o JAR use ele obrigatoriamente)
$nomeArb = str_replace("'", "''", ($rowArb['nomeArbitro'] ?? 'Árbitro') . " [" . ($rowArb['pais'] ?? 'CONFUSA') . "]");
$nomeAux1 = str_replace("'", "''", ($rowArb['nomeAuxiliarUm'] ?? 'Auxiliar 1') . " [" . ($rowArb['pais'] ?? 'CONFUSA') . "]");
$nomeAux2 = str_replace("'", "''", ($rowArb['nomeAuxiliarDois'] ?? 'Auxiliar 2') . " [" . ($rowArb['pais'] ?? 'CONFUSA') . "]");
$estiloArb = (int)($rowArb['estilo'] ?? 1);
$ldb->exec("INSERT OR REPLACE INTO trioarbitragem VALUES (1, '{$nomeArb}', '{$nomeAux1}', '{$nomeAux2}', {$estiloArb})");

// Inserir Clima & Estádio
if ($rowEst) {
    $climaId = (int)($rowEst['Clima'] ?? 1);
    $nomeClima = str_replace("'", "''", (string)($rowEst['nomeClima'] ?? 'Clima Padrão'));
    $tempV = $rowEst['TempVerao'] ?? '25';
    $estV = $rowEst['EstiloVerao'] ?? 'Bom';
    $tempO = $rowEst['TempOutono'] ?? '20';
    $estO = $rowEst['EstiloOutono'] ?? 'Bom';
    $tempI = $rowEst['TempInverno'] ?? '15';
    $estI = $rowEst['EstiloInverno'] ?? 'Bom';
    $tempP = $rowEst['TempPrimavera'] ?? '22';
    $estP = $rowEst['EstiloPrimavera'] ?? 'Bom';
    $hem = (int)($rowEst['Hemisferio'] ?? 1);
    $ldb->exec("INSERT OR REPLACE INTO clima VALUES ({$climaId}, '{$nomeClima}', '{$tempV}', '{$estV}', '{$tempO}', '{$estO}', '{$tempI}', '{$estI}', '{$tempP}', '{$estP}', {$hem})");

    $nomeEst = str_replace("'", "''", (string)$rowEst['Nome']);
    $capEst = (int)$rowEst['Capacidade'];
    $altEst = (int)$rowEst['Altitude'];
    $caldEst = (int)$rowEst['Caldeirao'];
    $ldb->exec("INSERT OR REPLACE INTO estadio VALUES ({$idEstadio}, '{$nomeEst}', {$capEst}, {$altEst}, {$caldEst}, {$climaId})");
} else {
    $ldb->exec("INSERT OR IGNORE INTO clima VALUES (1, 'Clima Padrão', '25', 'Bom', '20', 'Bom', '15', 'Bom', '22', 'Bom', 1)");
    $ldb->exec("INSERT OR IGNORE INTO estadio VALUES (1, 'Estádio Padrão', 35000, 0, 0, 1)");
    $idEstadio = 1;
}

// Inserir Clubes, Técnicos, Elencos e Jogadores
foreach ([$clubA, $clubB] as $cIdx => $cData) {
    $cId = (int)$cData['ID'];
    $pId = (int)$cData['Pais'];

    // 1. Técnico
    $stTec = $tecnicoObj->exportacao(null, $cId);
    $rowTec = $stTec->fetch(PDO::FETCH_ASSOC);
    $tecnicoId = $rowTec ? (int)$rowTec['ID'] : $cId;
    if ($rowTec) {
        $nomeTec = str_replace("'", "''", (string)$rowTec['Nome']);
        $idadeTec = (int)$rowTec['Idade'];
        $nivelTec = (int)$rowTec['Nivel'];
        $mentTec = (int)$rowTec['Mentalidade'];
        $estTec = (int)$rowTec['Estilo'];
        $ldb->exec("INSERT OR REPLACE INTO tecnico VALUES ({$tecnicoId}, '{$nomeTec}', {$idadeTec}, {$nivelTec}, {$mentTec}, {$estTec})");
    } else {
        $ldb->exec("INSERT OR REPLACE INTO tecnico VALUES ({$tecnicoId}, 'Técnico Interino', 45, 5, 3, 3)");
    }

    // 2. Clube
    $stCl = $timeObj->exportacao(null, $cId);
    $rowCl = $stCl->fetch(PDO::FETCH_ASSOC);
    $nomeCl = str_replace("'", "''", (string)($rowCl['Nome'] ?? $cData['Nome']));
    $siglaCl = !empty($rowCl['TresLetras']) ? $rowCl['TresLetras'] : substr(strtoupper(preg_replace('/[^A-Za-z]/', '', $nomeCl)), 0, 3);
    $escudo = "../../images/escudos/" . ($rowCl['Escudo'] ?? 'padrao.png');
    $uni1 = "../../images/uniformes/" . ($rowCl['Uniforme1'] ?? 'padrao.png');
    $uni2 = "../../images/uniformes/" . ($rowCl['Uniforme2'] ?? 'padrao.png');
    $estCl = ($cIdx === 0) ? $idEstadio : ((int)($rowCl['Estadio'] ?? 0) ?: $idEstadio);
    $maxTorc = (int)($rowCl['MaxTorcedores'] ?? 50000);
    $fidel = (int)($rowCl['Fidelidade'] ?? 5);
    $ldb->exec("INSERT OR REPLACE INTO clube VALUES ({$cId}, '{$nomeCl}', '{$siglaCl}', {$estCl}, '{$escudo}', '{$rowCl['Uni1Cor1']}', '{$rowCl['Uni1Cor2']}', '{$rowCl['Uni1Cor3']}', '{$uni1}', '{$rowCl['Uni2Cor1']}', '{$rowCl['Uni2Cor2']}', '{$rowCl['Uni2Cor3']}', '{$uni2}', {$maxTorc}, {$fidel})");

    // 3. Jogadores (Convocados)
    $stJog = $jogadorObj->exportacao(null, $cId);
    $jogList = $stJog->fetchAll(PDO::FETCH_ASSOC);

    // Se houver menos de 18 convocados, complementar automaticamente com os melhores jogadores disponíveis do país
    if (count($jogList) < 18) {
        $existingIds = array_map(function($j) { return (int)$j['idJogador']; }, $jogList);
        $stExtra = $db->prepare("
            SELECT j.*, p.bandeira as Nacionalidade,
                   (Marcacao + Desarme + VisaoJogo + Movimentacao + Cruzamentos + Cabeceamento + Tecnica + ControleBola + Finalizacao + FaroGol + Velocidade + Forca + Reflexos + Seguranca + Saidas + JogoAereo + Lancamentos + DefesaPenaltis + Determinacao) as somaAtributos
            FROM jogador j 
            LEFT JOIN paises p ON j.Pais = p.id 
            WHERE j.Pais = :pais AND (j.Sexo = 0 OR j.Sexo IS NULL) AND j.disponibilidade >= 0 
            ORDER BY j.Nivel DESC LIMIT 30
        ");
        $stExtra->execute([':pais' => $pId]);
        while ($extra = $stExtra->fetch(PDO::FETCH_ASSOC)) {
            $eId = (int)$extra['ID'];
            if (!in_array($eId, $existingIds)) {
                $extra['idJogador'] = $eId;
                $extra['nomeJogador'] = $extra['Nome'];
                $extra['Idade'] = 25;
                $extra['titularidade'] = 0;
                $jogList[] = $extra;
                $existingIds[] = $eId;
            }
            if (count($jogList) >= 23) break;
        }
    }

    $playerIds = [];
    foreach ($jogList as $row) {
        $jId = (int)$row['idJogador'];
        $playerIds[] = $jId;
        $sp = str_pad((string)($row['StringPosicoes'] ?? ''), 15, '0');
        $ldb->exec("INSERT OR REPLACE INTO posicaojogador VALUES ({$jId}, '{$sp[0]}', '{$sp[1]}', '{$sp[2]}', '{$sp[3]}', '{$sp[4]}', '{$sp[5]}', '{$sp[6]}', '{$sp[7]}', '{$sp[8]}', '{$sp[9]}', '{$sp[10]}', '{$sp[11]}', '{$sp[12]}', '{$sp[13]}', '{$sp[14]}')");

        $nomeJog = str_replace("'", "''", (string)$row['nomeJogador']);
        $idade = (int)($row['Idade'] ?? 25);
        $nivel = (int)($row['Nivel'] ?? 70);
        $ment = (int)($row['Mentalidade'] ?? 3);
        $cob = (int)($row['CobradorFalta'] ?? 0);
        $ldb->exec("INSERT OR REPLACE INTO jogador VALUES ({$jId}, '{$nomeJog}', {$idade}, {$nivel}, 0, 0, {$ment}, {$cob})");

        $nac = !empty($row['Nacionalidade']) ? $row['Nacionalidade'] : '-';
        $ldb->exec("INSERT OR REPLACE INTO nacionalidades VALUES ({$jId}, '{$nac}')");

        if ($sp[0] == '1') {
            $ldb->exec("INSERT OR REPLACE INTO atributosgoleiro VALUES ({$jId}, '{$row['Reflexos']}', '{$row['Seguranca']}', '{$row['Saidas']}', '{$row['JogoAereo']}', '{$row['Lancamentos']}', '{$row['DefesaPenaltis']}', '{$row['Determinacao']}', '{$row['DeterminacaoOriginal']}')");
        } else {
            $ldb->exec("INSERT OR REPLACE INTO atributosjogador VALUES ({$jId}, '{$row['Marcacao']}', '{$row['Desarme']}', '{$row['VisaoJogo']}', '{$row['Movimentacao']}', '{$row['Cruzamentos']}', '{$row['Cabeceamento']}', '{$row['Tecnica']}', '{$row['ControleBola']}', '{$row['Finalizacao']}', '{$row['FaroGol']}', '{$row['Velocidade']}', '{$row['Forca']}', '{$row['Determinacao']}', '{$row['DeterminacaoOriginal']}')");
        }
    }

    // 4. Elenco (23 jogadores + Tecnico)
    $elencoSlots = [];
    for ($i = 0; $i < 23; $i++) {
        $elencoSlots[] = isset($playerIds[$i]) ? $playerIds[$i] : 0;
    }
    $elencoSlots[] = $tecnicoId;
    $elencoStr = implode(', ', $elencoSlots);
    $ldb->exec("INSERT OR REPLACE INTO elenco VALUES ({$cId}, {$elencoStr})");

    // 5. Escalação sanitizada com todas as posições
    sanitizarEscalacaoPreSimulacao($ldb, $cId, []);
}

// Normalizar imagens dos clubes
if (function_exists('normalizarImagensClubesSQLite')) {
    normalizarImagensClubesSQLite($ldb, $hexacolorDir, $_SERVER['DOCUMENT_ROOT']);
}

// Finalizar gravações no SQLite e fechar conexão para o Java acessar
$ldb->exec("PRAGMA wal_checkpoint(TRUNCATE);");
$ldb = null;
$liteDatabase = null;
gc_collect_cycles();

// 10. Montar JSON da Partida
$fullDateMs = strtotime($dataHora) * 1000 + 5 * 60 * 60 * 1000;
$matchdayIndex = 1;

$json_array = [
    'calendarName' => $calendarName,
    'matchdayIndex' => $matchdayIndex,
    'color1' => -16777216,
    'color2' => -1,
    'color3' => -16744193,
    'randomInjuriesAmount' => 0,
    'randomInjuriesMaxDuration' => 0,
    'randomConditionMinimum' => 70,
    'matches' => [
        [
            'databasePath' => 'jdbc:sqlite:data/database.db3',
            'id' => 999999,
            'date' => $fullDateMs,
            'idTeam1' => $idClubA,
            'idTeam2' => $idClubB,
            'idChosenGround' => $idEstadio,
            'neutralGround' => boolval($neutro),
            'outTeam1' => [],
            'outTeam2' => [],
            'knockoutTiebraker' => 0,
            'knockoutAwayGoals' => false
        ]
    ]
];

$jsonPath = $hexacolorDir . '/agenda/json.txt';
if (!is_dir($hexacolorDir . '/agenda')) {
    @mkdir($hexacolorDir . '/agenda', 0777, true);
}
file_put_contents($jsonPath, json_encode($json_array, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT));

// 11. Executar o Motor Java Hexacolor
$isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
if ($isWindows) {
    $dir = str_replace('\\', '/', $hexacolorDir);
    $jarPath = $dir . "/HexacolorYMTv2.jar";
    $jsonPathWin = $dir . "/agenda/json.txt";
    $cmd = "java -Xms32m -Xmx256m -XX:+UseSerialGC -Dfile.encoding=UTF-8 -Dsun.jnu.encoding=UTF-8 -Djava.awt.headless=true -jar \"$jarPath\" -m \"$jsonPathWin\" 2>&1";
} else {
    $docRoot = $_SERVER['DOCUMENT_ROOT'];
    $javaBin = $docRoot . "/java_station/jdk/jdk1.8.0_231/bin/java";
    $libPath = $docRoot . "/competicoes/hexacolor/lib";
    $tmpDir = $docRoot . "/java_station/tmp";
    $jarPath = $docRoot . "/competicoes/hexacolor/HexacolorYMTv2.jar";
    $jsonPathLinux = $docRoot . "/competicoes/hexacolor/agenda/json.txt";
    $cmd = "cd " . $hexacolorDir . " && export LANG=en_US.UTF-8; export LC_ALL=en_US.UTF-8; $javaBin -Xms32m -Xmx256m -XX:+UseSerialGC -Dfile.encoding=UTF-8 -Dsun.jnu.encoding=UTF-8 -Djava.awt.headless=true -Djava.library.path=$libPath -Djava.io.tmpdir=$tmpDir -jar $jarPath -m $jsonPathLinux 2>&1";
}

$output = shell_exec($cmd . "\n");
$output = ($output !== null) ? (string)$output : '';

// Reenvio automático caso a rotina de compatibilidade/migração tenha ocorrido no primeiro disparo
$tentativas = 1;
while ($tentativas < 3 && stripos($output, 'compatibilidade com o portal web') !== false) {
    $outputRetry = shell_exec($cmd . "\n");
    $outputRetryStr = ($outputRetry !== null) ? (string)$outputRetry : '';
    $output .= "\n[REENVIO AUTOMÁTICO #" . $tentativas . "]:\n" . $outputRetryStr;
    $tentativas++;
}

file_put_contents($hexacolorDir . "/simulation_debug.log", "[" . date('Y-m-d H:i:s') . "] AMISTOSO CMD: " . $cmd . "\nOUTPUT:\n" . $output . "\n----------------------------------------\n", FILE_APPEND);

// 12. Localizar o arquivo .hyl gerado
$siglaA = !empty($clubA['TresLetras']) ? $clubA['TresLetras'] : substr(strtoupper(preg_replace('/[^A-Za-z]/', '', $infoPaisA['nome'])), 0, 3);
$siglaB = !empty($clubB['TresLetras']) ? $clubB['TresLetras'] : substr(strtoupper(preg_replace('/[^A-Za-z]/', '', $infoPaisB['nome'])), 0, 3);
$pathRelativo = $siglaA . "x" . $siglaB . " - " . date("j-n-Y", strtotime($dataHora));
$completePath = "/Partidas/" . $calendarName . "/" . $matchdayIndex . "º Rodada/" . $pathRelativo . ".hyl";
$hylFile = $hexacolorDir . $completePath;

if (!file_exists($hylFile)) {
    // Busca flexível caso o nome do arquivo tenha variações
    $foundFiles = glob($hexacolorDir . "/Partidas/" . $calendarName . "/*/" . $siglaA . "x" . $siglaB . "*.hyl");
    if (!empty($foundFiles)) {
        $hylFile = $foundFiles[0];
        $pathRelativo = basename($hylFile, '.hyl');
    }
}

if (!file_exists($hylFile)) {
    echo json_encode([
        'success' => false,
        'error' => 'A simulação falhou ao gerar os arquivos de súmula. Verifique se as duas seleções possuem técnicos e elencos válidos.'
    ]);
    exit;
}

// 13. Parse da Súmula e Dados do Jogo
$golsTimeA = 0;
$golsTimeB = 0;
$penA = null;
$penB = null;

$xml = json_decode(file_get_contents($hylFile));
if ($xml) {
    $golsTimeA = (int)$xml->placarTime1;
    $golsTimeB = (int)$xml->placarTime2;
    if (isset($xml->penaltis) && $xml->penaltis) {
        $penA = isset($xml->placarPenaltisTime1) ? (int)$xml->placarPenaltisTime1 : 0;
        $penB = isset($xml->placarPenaltisTime2) ? (int)$xml->placarPenaltisTime2 : 0;
    }
}

$hyjFile = str_replace('.hyl', '.hyj', $hylFile);
if ($penA === null && file_exists($hyjFile)) {
    $jsonHyj = json_decode(file_get_contents($hyjFile));
    if ($jsonHyj && isset($jsonHyj->penaltis) && $jsonHyj->penaltis) {
        $penA = isset($jsonHyj->time1->placarPenaltis) ? (int)$jsonHyj->time1->placarPenaltis : 0;
        $penB = isset($jsonHyj->time2->placarPenaltis) ? (int)$jsonHyj->time2->placarPenaltis : 0;
    }
}

// 14. Gravar a Partida em jogos_clube
$stInsJogo = $db->prepare("
    INSERT INTO jogos_clube (
        timeA_id, timeA_nome, timeB_id, timeB_nome, data, competicao_id, 
        estadio_id, neutro, arbitro_id, fase, competicao_tipo, simulador_interno, 
        status, subir_live, dono, tipo_times, timeA_gols, timeB_gols, timeA_penaltis, timeB_penaltis, path
    ) VALUES (
        :timeA, :nomeA, :timeB, :nomeB, :data, :comp, 
        :estadio, :neutro, :arbitro, 1, 1, 1, 
        1, :subirLive, 0, 1, :golsA, :golsB, :penA, :penB, :path
    )
");

$stInsJogo->execute([
    ':timeA' => $idClubA,
    ':nomeA' => $infoPaisA['nome'],
    ':timeB' => $idClubB,
    ':nomeB' => $infoPaisB['nome'],
    ':data' => $dataHora,
    ':comp' => $idCompeticao,
    ':estadio' => $idEstadio,
    ':neutro' => $neutro,
    ':arbitro' => $idArbitro,
    ':subirLive' => $subirLive,
    ':golsA' => $golsTimeA,
    ':golsB' => $golsTimeB,
    ':penA' => $penA,
    ':penB' => $penB,
    ':path' => $pathRelativo
]);

$idPartida = (int)$db->lastInsertId();

// 15. Enviar para o CONFUSA Live (se o flag foi selecionado)
$liveEnviado = false;
if ($subirLive === 1) {
    try {
        $uploaderPath = $_SERVER['DOCUMENT_ROOT'] . '/lib/ConfusaLiveUploader.php';
        if (file_exists($uploaderPath)) {
            require_once $uploaderPath;
            $resLive = ConfusaLiveUploader::enviarPartida($hylFile, $calendarName, 'Amistoso', $dataHora);
            $liveEnviado = !empty($resLive['success']);
        }
    } catch (\Throwable $e) {
        error_log("CONFUSA Live Amistoso Error: " . $e->getMessage());
    }
}

// 16. Regra de Sigilo Temporal & Registro no Ranking Oficial
$now = time();
$dtMatch = strtotime($dataHora);
$temPenaltis = ($penA !== null && $penB !== null);
$duracaoMinutos = $temPenaltis ? 150 : 120;
$horarioTermino = $dtMatch + ($duracaoMinutos * 60);
$jaTerminouTempoReal = ($now >= $horarioTermino);

$rankingRegistrado = false;
if ($jaTerminouTempoReal) {
    try {
        $rankingRegistrado = (bool)$competicaoObj->registrarJogoNoRanking($idPartida, $hylFile, $hyjFile, true);
    } catch (\Throwable $e) {
        error_log("CONFUSA Ranking Amistoso Error: " . $e->getMessage());
    }
}

// 17. Formatar Resposta JSON
if ($jaTerminouTempoReal) {
    // Revelação Completa: Partida já encerrou na vida real
    $eventos = [];
    if ($xml && isset($xml->eventos) && is_array($xml->eventos)) {
        foreach ($xml->eventos as $ev) {
            $evArr = (array)$ev;
            $tempoRaw = (int)($evArr['tempo'] ?? 1);
            if ($tempoRaw > 4) continue;
            $eventos[] = [
                'tipo' => $evArr['tipoEvento'] ?? '',
                'minuto' => (int)($evArr['minutos'] ?? 0),
                'tempo' => $tempoRaw,
                'jogador' => (string)($evArr['nomeJogador'] ?? ''),
                'time' => (int)($evArr['time'] ?? 1)
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'sigilo_temporal' => false,
        'idPartida' => $idPartida,
        'mandante' => [
            'id' => $paisA_id,
            'nome' => $infoPaisA['nome'],
            'bandeira' => $infoPaisA['bandeira'],
            'gols' => $golsTimeA,
            'penaltis' => $penA
        ],
        'visitante' => [
            'id' => $paisB_id,
            'nome' => $infoPaisB['nome'],
            'bandeira' => $infoPaisB['bandeira'],
            'gols' => $golsTimeB,
            'penaltis' => $penB
        ],
        'eventos' => $eventos,
        'live_enviado' => $liveEnviado,
        'ranking_registrado' => $rankingRegistrado,
        'sumula_url' => "/competicoes/sumula_partida.php?partida={$idPartida}"
    ]);
} else {
    // Sigilo Temporal Ativo: Partida futura ou em andamento no horário real
    echo json_encode([
        'success' => true,
        'sigilo_temporal' => true,
        'idPartida' => $idPartida,
        'data_formatada' => date('d/m/Y \à\s H:i', $dtMatch),
        'horario_termino_formatado' => date('d/m/Y \à\s H:i', $horarioTermino),
        'mandante' => [
            'nome' => $infoPaisA['nome'],
            'bandeira' => $infoPaisA['bandeira']
        ],
        'visitante' => [
            'nome' => $infoPaisB['nome'],
            'bandeira' => $infoPaisB['bandeira']
        ],
        'live_enviado' => $liveEnviado,
        'message' => 'O amistoso foi simulado a portas fechadas com sucesso. Em conformidade com o Sigilo Temporal, o placar e a súmula serão revelados após o término no tempo real (' . date('H:i', $horarioTermino) . ').',
        'sumula_url' => "/competicoes/sumula_partida.php?partida={$idPartida}"
    ]);
}
