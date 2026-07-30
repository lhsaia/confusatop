<?php
// CLI/Cron runner for next-day matches simulation
if (php_sapi_name() !== 'cli' && !isset($_GET['cron_key'])) {
    // Permitir execução via CLI ou via Web se cron_key estiver presente
    header('HTTP/1.0 403 Forbidden');
    die("Acesso restrito ao agendador (Cron CLI).");
}

date_default_timezone_set('America/Sao_Paulo');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/sqliteDatabase.php';
require_once __DIR__ . '/../objetos/competicao_clube.php';
require_once __DIR__ . '/../objetos/time.php';

$database = new Database();
$db = $database->getConnection();
$competicaoObj = new Competicao_clube($db);

// Selecionar jogos pendentes (status = 0) agendados para amanhã
$amanhaInicio = date('Y-m-d 00:00:00', strtotime('+1 day'));
$amanhaFim    = date('Y-m-d 23:59:59', strtotime('+1 day'));

echo "[" . date('Y-m-d H:i:s') . "] Iniciando Cron de Simulação para partidas entre {$amanhaInicio} e {$amanhaFim}...\n";

$query = "SELECT id, competicao, timeA, timeB, estadio, neutro, data 
          FROM competicao_jogos 
          WHERE status = 0 
            AND data >= :inicio 
            AND data <= :fim 
          ORDER BY data ASC";

$stmt = $db->prepare($query);
$stmt->bindParam(':inicio', $amanhaInicio);
$stmt->bindParam(':fim', $amanhaFim);
$stmt->execute();

$partidas = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($partidas)) {
    echo "[" . date('Y-m-d H:i:s') . "] Nenhuma partida pendente encontrada para amanhã.\n";
    exit(0);
}

echo "[" . date('Y-m-d H:i:s') . "] Encontrada(s) " . count($partidas) . " partida(s) para simular.\n";

$hexacolorDir = __DIR__ . '/hexacolor';

foreach ($partidas as $matchInfo) {
    $idPartida = $matchInfo['id'];
    $idCompeticao = $matchInfo['competicao'];
    
    echo "-> Processando Partida ID #{$idPartida} (Competição #{$idCompeticao})...\n";
    
    $sourceDbPath = __DIR__ . "/databases/{$idCompeticao}-database.db3";
    $targetDbPath = $hexacolorDir . "/data/database.db3";
    
    if (!file_exists($sourceDbPath)) {
        echo "   [ERRO] Banco da competição não encontrado: {$sourceDbPath}\n";
        continue;
    }
    
    if (!is_dir($hexacolorDir . "/data")) {
        mkdir($hexacolorDir . "/data", 0777, true);
    }
    
    // 1. Copiar SQLite da competição para data/database.db3
    copy($sourceDbPath, $targetDbPath);
    
    $liteDatabase = new SQLiteDatabase();
    $liteDatabase->fileName = $targetDbPath;
    $ldb = $liteDatabase->getConnection();
    $liteCompeticao = new Competicao_clube($ldb);
    $timeObj = new Time($ldb);
    
    // Garantir que as colunas Suspenso e Lesionado existam na tabela jogador do SQLite temporário
    try {
        $ldb->exec("ALTER TABLE jogador ADD COLUMN Suspenso INTEGER DEFAULT 0");
    } catch (Exception $e) {}
    try {
        $ldb->exec("ALTER TABLE jogador ADD COLUMN Lesionado INTEGER DEFAULT 0");
    } catch (Exception $e) {}

    // Obter desfalques (suspensos e lesionados) de cada time no MariaDB e sincronizar no SQLite
    $outTeam1 = array();
    $outTeam2 = array();

    // 1. Obter os IDs de jogadores no elenco de cada time
    $team1Players = [];
    $stmtT1 = $ldb->prepare("SELECT * FROM elenco WHERE Clube = :clube LIMIT 1");
    $stmtT1->bindValue(':clube', $matchInfo['timeA']);
    $stmtT1->execute();
    $elRow1 = $stmtT1->fetch(PDO::FETCH_ASSOC);
    if ($elRow1) {
        for ($i = 1; $i <= 23; $i++) {
            if (!empty($elRow1['Jogador' . $i])) {
                $team1Players[] = (int)$elRow1['Jogador' . $i];
            }
        }
    }

    $team2Players = [];
    $stmtT2 = $ldb->prepare("SELECT * FROM elenco WHERE Clube = :clube LIMIT 1");
    $stmtT2->bindValue(':clube', $matchInfo['timeB']);
    $stmtT2->execute();
    $elRow2 = $stmtT2->fetch(PDO::FETCH_ASSOC);
    if ($elRow2) {
        for ($i = 1; $i <= 23; $i++) {
            if (!empty($elRow2['Jogador' . $i])) {
                $team2Players[] = (int)$elRow2['Jogador' . $i];
            }
        }
    }

    $allMatchPlayers = array_merge($team1Players, $team2Players);
    $lesionados = [];
    $suspensos = [];

    if (!empty($allMatchPlayers)) {
        $inClause = implode(',', $allMatchPlayers);
        // Consultar status dinâmicos no MariaDB principal
        $querySt = "SELECT j.ID, 
                           IF(j.lesionado_ate IS NOT NULL AND j.lesionado_ate >= CURDATE(), 1, 0) as lesionado,
                           COALESCE(cs.suspenso, 0) as suspenso 
                    FROM jogador j 
                    LEFT JOIN competicao_suspensos cs ON j.ID = cs.id_jogador AND cs.id_competicao = :comp
                    WHERE j.ID IN ($inClause)";
        $stmtSt = $db->prepare($querySt);
        $stmtSt->bindValue(':comp', $idCompeticao, PDO::PARAM_INT);
        $stmtSt->execute();
        while ($rowSt = $stmtSt->fetch(PDO::FETCH_ASSOC)) {
            $pId = (int)$rowSt['ID'];
            if ($rowSt['lesionado'] == 1) {
                $lesionados[] = $pId;
                if (in_array($pId, $team1Players)) $outTeam1[] = $pId;
                if (in_array($pId, $team2Players)) $outTeam2[] = $pId;
            }
            if ($rowSt['suspenso'] == 1) {
                $suspensos[] = $pId;
                if (in_array($pId, $team1Players)) $outTeam1[] = $pId;
                if (in_array($pId, $team2Players)) $outTeam2[] = $pId;
            }
        }

        // Remover duplicatas nos desfalques
        $outTeam1 = array_values(array_unique($outTeam1));
        $outTeam2 = array_values(array_unique($outTeam2));

        // Resetar status no SQLite temporário
        $ldb->exec("UPDATE jogador SET Suspenso = 0, Lesionado = 0");

        // Atualizar lesionados no SQLite temporário
        if (!empty($lesionados)) {
            $inLes = implode(',', $lesionados);
            $ldb->exec("UPDATE jogador SET Lesionado = 1 WHERE ID IN ($inLes)");
        }

        // Atualizar suspensos no SQLite temporário
        if (!empty($suspensos)) {
            $inSus = implode(',', $suspensos);
            $ldb->exec("UPDATE jogador SET Suspenso = 1 WHERE ID IN ($inSus)");
        }
    }
    
    $competitionInfo = $competicaoObj->readInfo($idCompeticao);
    $nomeComposto = $competitionInfo['ano'] . " - " . $competitionInfo['nome'];
    $databasePath = "jdbc:sqlite:data/database.db3";
    $cores = $liteCompeticao->getColors();
    
    $fullDate = strtotime(date("j-n-Y", strtotime($matchInfo['data']))) * 1000 + 5*60*60*1000;
    $matchdayIndex = 1;
    
    $json_array = array(
        'calendarName' => $nomeComposto,
        'color1' => isset($cores['partidaCor1']) ? $cores['partidaCor1'] : '#000000',
        'color2' => isset($cores['partidaCor2']) ? $cores['partidaCor2'] : '#ffffff',
        'color3' => isset($cores['partidaCor3']) ? $cores['partidaCor3'] : '#cccccc',
        'matchdayIndex' => $matchdayIndex,
        'matches' => [array(
            'databasePath' => $databasePath,
            'id' => $matchInfo['id'],
            'date' => $fullDate,
            'idTeam1' => $matchInfo['timeA'],
            'idTeam2' => $matchInfo['timeB'],
            'kitTeam1' => 0,
            'kitTeam2' => 0,
            'idChosenGround' => $matchInfo['estadio'],
            'neutralGround' => boolval($matchInfo['neutro']),
            'outTeam1' => $outTeam1,
            'outTeam2' => $outTeam2,
            'knockoutTiebraker' => 0,
            'knockoutAwayGoals' => false,
        )]
    );
    
    $json = json_encode($json_array, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT);
    file_put_contents($hexacolorDir . "/agenda/json.txt", $json);
    
    // Executar simulador JAR
    $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    $dir = str_replace('\\', '/', $hexacolorDir);
    
    if ($isWindows) {
        $jarPath = $dir . "/HexacolorYMTv2.jar";
        $jsonPath = $dir . "/agenda/json.txt";
        $cmd = "java -jar \"$jarPath\" -m \"$jsonPath\" 2>&1";
    } else {
        $javaBin = "/java_station/jdk/jdk1.8.0_231/bin/java";
        $libPath = "/competicoes/hexacolor/lib";
        $tmpDir = "/java_station/tmp";
        $jarPath = "/competicoes/hexacolor/HexacolorLite.jar";
        $jsonPath = "/competicoes/hexacolor/agenda/json.txt";
        $cmd = "export DISPLAY=:0.0; $javaBin -Djava.library.path=$libPath -Djava.io.tmpdir=$tmpDir -jar $jarPath -m $jsonPath 2>&1";
    }
    
    $output = shell_exec($cmd . "\n");
    
    // 2. Copiar banco SQLite atualizado de volta
    if (file_exists($targetDbPath)) {
        copy($targetDbPath, $sourceDbPath);
    }
    
    // Ler gols gravados no arquivo de partida .hyl
    $siglaA = $timeObj->getSigla($matchInfo['timeA']);
    $siglaB = $timeObj->getSigla($matchInfo['timeB']);
    $path = $siglaA . "x" . $siglaB . " - " . date("j-n-Y", strtotime($matchInfo['data']));
    $completePath = "/Partidas/" . $nomeComposto . "/" . $matchdayIndex . "º Rodada/" . $path . ".hyl";
    
    $hylFile = $hexacolorDir . $completePath;
    $golsTimeA = 0;
    $golsTimeB = 0;
    if (file_exists($hylFile)) {
        $xml = json_decode(file_get_contents($hylFile));
        if ($xml) {
            $golsTimeA = (int)$xml->placarTime1;
            $golsTimeB = (int)$xml->placarTime2;
        }
    }
    
    // Atualizar resultado no MariaDB
    $competicaoObj->uploadMatchResults($idPartida, $golsTimeA, $golsTimeB, $path);
    echo "   [SUCESSO] Partida #{$idPartida} simulada! Placar: {$siglaA} {$golsTimeA} x {$golsTimeB} {$siglaB}\n";
}

echo "[" . date('Y-m-d H:i:s') . "] Processamento do Cron concluído com sucesso.\n";
?>
