<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// CLI/Cron runner for next-day matches simulation
if (php_sapi_name() !== 'cli' && !isset($_GET['cron_key'])) {
    // Permitir execução via CLI ou via Web se cron_key estiver presente
    header('HTTP/1.0 403 Forbidden');
    die("Acesso restrito ao agendador (Cron CLI).");
}

if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
}

date_default_timezone_set('America/Sao_Paulo');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/sqliteDatabase.php';
require_once __DIR__ . '/../objetos/competicao_clube.php';
require_once __DIR__ . '/../objetos/time.php';

$database = new Database();
$db = $database->getConnection();
$competicaoObj = new Competicao_clube($db);

// Selecionar jogos pendentes (status = 0) agendados para as próximas 24 horas
$inicioBusca = date('Y-m-d H:i:s'); // Momento atual
$fimBusca    = date('Y-m-d H:i:s', strtotime('+24 hours')); // 24 horas à frente

echo "[" . date('Y-m-d H:i:s') . "] Iniciando Cron de Simulação para partidas entre {$inicioBusca} e {$fimBusca}...\n";

// Buscar jogos programados para as próximas X horas
$query = "SELECT id, competicao_id AS competicao, timeA_id AS timeA, timeB_id AS timeB, estadio_id AS estadio, neutro, fase, data 
          FROM jogos_clube 
          WHERE status = 0 
            AND simulador_interno = 1
            AND data >= :inicio 
            AND data <= :fim 
          ORDER BY data ASC";

$stmt = $db->prepare($query);
$stmt->bindParam(':inicio', $inicioBusca);
$stmt->bindParam(':fim', $fimBusca);
$stmt->execute();

$partidas = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($partidas)) {
    echo "[" . date('Y-m-d H:i:s') . "] Nenhuma partida pendente encontrada nas próximas 24 horas.\n";
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
    
    $idEstadio = isset($matchInfo['estadio']) ? (int)$matchInfo['estadio'] : 0;
    if ($idEstadio <= 0) {
        echo "   [ERRO] A Partida #{$idPartida} não possui estádio definido. Pulando...\n";
        continue;
    }

    $liteDatabase = new SQLiteDatabase();
    $liteDatabase->fileName = $targetDbPath;
    $ldb = $liteDatabase->getConnection();
    $liteCompeticao = new Competicao_clube($ldb);
    $timeObj = new Time($ldb);

    // Validar se o estádio existe na tabela estadio do SQLite
    $stmtEstCheck = $ldb->prepare("SELECT 1 FROM estadio WHERE ID = :idEst LIMIT 1");
    $stmtEstCheck->bindValue(':idEst', $idEstadio, PDO::PARAM_INT);
    $stmtEstCheck->execute();
    if (!$stmtEstCheck->fetch()) {
        $ldb = null;
        echo "   [ERRO] O estádio #{$idEstadio} da Partida #{$idPartida} não existe no banco SQLite. Pulando...\n";
        continue;
    }

    // Garantir tabela de compatibilidade com as versões necessárias e limpeza de pendências
    try {
        $ldb->exec("DROP TABLE IF EXISTS `comp10_temp_table`");
        $ldb->exec("CREATE TABLE IF NOT EXISTS `compatibilidade` (`versao` TEXT)");
        $stmtCheck = $ldb->query("SELECT `versao` FROM `compatibilidade`");
        $existingVersions = $stmtCheck->fetchAll(PDO::FETCH_COLUMN);
        
        $requiredVersions = ['2.8', '2.9.1', '2.10', '2.13', '2.14'];
        $stmtInsert = $ldb->prepare("INSERT INTO `compatibilidade` (`versao`) VALUES (:versao)");
        foreach ($requiredVersions as $v) {
            if (!in_array($v, $existingVersions)) {
                $stmtInsert->bindValue(':versao', $v, PDO::PARAM_STR);
                $stmtInsert->execute();
            }
        }
        // Evita que a engine Java tente abrir diálogo interativo de sincronização web (HeadlessException)
        $ldb->exec("DELETE FROM `jogadorpendente`");
    } catch (Exception $e) {
        error_log("Erro ao aplicar compatibilidade no SQLite: " . $e->getMessage());
    }

    // 1. Guardar a escalação original (padrão) na memória para restaurar depois da simulação
    $originalEscalacoes = [];
    try {
        $stmtOrig = $ldb->prepare("SELECT * FROM escalacao WHERE Clube IN (:timeA, :timeB)");
        $stmtOrig->bindValue(':timeA', $matchInfo['timeA'], PDO::PARAM_INT);
        $stmtOrig->bindValue(':timeB', $matchInfo['timeB'], PDO::PARAM_INT);
        $stmtOrig->execute();
        while ($rowOrig = $stmtOrig->fetch(PDO::FETCH_ASSOC)) {
            $originalEscalacoes[(int)$rowOrig['Clube']] = $rowOrig;
        }
    } catch (Exception $e) {}

    // 2. Criar a tabela escalacao_jogo se não existir e buscar escalações específicas para a partida
    $ldb->exec("CREATE TABLE IF NOT EXISTS `escalacao_jogo` (
        `Jogo`	int ( 10 ) NOT NULL,
        `Clube`	int ( 5 ) NOT NULL,
        `Jogador1`	int ( 5 ) NOT NULL,
        `Jogador2`	int ( 5 ) NOT NULL,
        `Jogador3`	int ( 5 ) NOT NULL,
        `Jogador4`	int ( 5 ) NOT NULL,
        `Jogador5`	int ( 5 ) NOT NULL,
        `Jogador6`	int ( 5 ) NOT NULL,
        `Jogador7`	int ( 5 ) NOT NULL,
        `Jogador8`	int ( 5 ) NOT NULL,
        `Jogador9`	int ( 5 ) NOT NULL,
        `Jogador10`	int ( 5 ) NOT NULL,
        `Jogador11`	int ( 5 ) NOT NULL,
        `Capitao`	int ( 5 ) NOT NULL,
        `Penalti1`	int ( 5 ) DEFAULT NULL,
        `Penalti2`	int ( 5 ) DEFAULT NULL,
        `Penalti3`	int ( 5 ) DEFAULT NULL,
        `Indisponiveis`	text,
        PRIMARY KEY(`Jogo`,`Clube`)
    );");

    $customLineupA = null;
    $customLineupB = null;
    try {
        $stmtCustomA = $ldb->prepare("SELECT * FROM escalacao_jogo WHERE Jogo = :jogo AND Clube = :clube LIMIT 1");
        $stmtCustomA->bindValue(':jogo', $matchInfo['id'], PDO::PARAM_INT);
        $stmtCustomA->bindValue(':clube', $matchInfo['timeA'], PDO::PARAM_INT);
        $stmtCustomA->execute();
        $customLineupA = $stmtCustomA->fetch(PDO::FETCH_ASSOC);

        $stmtCustomB = $ldb->prepare("SELECT * FROM escalacao_jogo WHERE Jogo = :jogo AND Clube = :clube LIMIT 1");
        $stmtCustomB->bindValue(':jogo', $matchInfo['id'], PDO::PARAM_INT);
        $stmtCustomB->bindValue(':clube', $matchInfo['timeB'], PDO::PARAM_INT);
        $stmtCustomB->execute();
        $customLineupB = $stmtCustomB->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
    
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
        $validPlayerIds = array_filter(array_map('intval', $allMatchPlayers), function($id) { return $id > 0; });
        if (!empty($validPlayerIds)) {
            $inClause = implode(',', array_unique($validPlayerIds));
            // Garantir que a coluna lesionado_ate exista no MariaDB competicao_suspensos
            try {
                $db->exec("ALTER TABLE competicao_suspensos ADD COLUMN lesionado_ate DATE DEFAULT NULL");
            } catch (Exception $e) {}

            // Consultar status dinâmicos no MariaDB principal (inclui checagem para jogadores de times importados .ymt)
            try {
                $querySt = "SELECT val.ID,
                                   IF((cs.lesionado_ate IS NOT NULL AND cs.lesionado_ate >= CURDATE()) OR (j.lesionado_ate IS NOT NULL AND j.lesionado_ate >= CURDATE()), 1, 0) as lesionado,
                                   COALESCE(cs.suspenso, 0) as suspenso
                            FROM (
                                SELECT ID FROM jogador WHERE ID IN ($inClause)
                                UNION
                                SELECT id_jogador AS ID FROM competicao_suspensos WHERE id_competicao = :comp AND id_jogador IN ($inClause)
                            ) val
                            LEFT JOIN jogador j ON val.ID = j.ID
                            LEFT JOIN competicao_suspensos cs ON val.ID = cs.id_jogador AND cs.id_competicao = :comp2";
                $stmtSt = $db->prepare($querySt);
                $stmtSt->bindValue(':comp', $idCompeticao, PDO::PARAM_INT);
                $stmtSt->bindValue(':comp2', $idCompeticao, PDO::PARAM_INT);
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
            } catch (Exception $e) {
                error_log("Erro ao consultar status de desfalques no MariaDB: " . $e->getMessage());
            }
        }

        // Remover duplicatas nos desfalques
        $outTeam1 = array_values(array_unique($outTeam1));
        $outTeam2 = array_values(array_unique($outTeam2));

        // Resetar status no SQLite temporário
        $ldb->exec("UPDATE jogador SET Suspenso = 0, Lesionado = 0");

        // Atualizar lesionados no SQLite temporário
        if (!empty($lesionados)) {
            $inLes = implode(',', array_map('intval', $lesionados));
            $ldb->exec("UPDATE jogador SET Lesionado = 1 WHERE ID IN ($inLes)");
        }

        // Atualizar suspensos no SQLite temporário
        if (!empty($suspensos)) {
            $inSus = implode(',', array_map('intval', $suspensos));
            $ldb->exec("UPDATE jogador SET Suspenso = 1 WHERE ID IN ($inSus)");
        }
    }

    // 3. Aplicar as escalações temporárias na tabela escalacao do banco temporário
    try {
        $updateFields = [];
        for ($i = 1; $i <= 11; $i++) {
            $updateFields[] = "Jogador{$i} = :jog{$i}";
        }
        $updateFields[] = "Capitao = :capitao";
        $updateFields[] = "Penalti1 = :pen1";
        $updateFields[] = "Penalti2 = :pen2";
        $updateFields[] = "Penalti3 = :pen3";
        $stmtUp = $ldb->prepare("UPDATE escalacao SET " . implode(', ', $updateFields) . " WHERE Clube = :clube");

        if ($customLineupA) {
            $stmtUp->bindValue(':clube', $matchInfo['timeA'], PDO::PARAM_INT);
            for ($i = 1; $i <= 11; $i++) {
                $stmtUp->bindValue(':jog' . $i, $customLineupA['Jogador' . $i], PDO::PARAM_INT);
            }
            $stmtUp->bindValue(':capitao', $customLineupA['Capitao'], PDO::PARAM_INT);
            $stmtUp->bindValue(':pen1', $customLineupA['Penalti1'], PDO::PARAM_INT);
            $stmtUp->bindValue(':pen2', $customLineupA['Penalti2'], PDO::PARAM_INT);
            $stmtUp->bindValue(':pen3', $customLineupA['Penalti3'], PDO::PARAM_INT);
            $stmtUp->execute();

            if (!empty($customLineupA['Indisponiveis'])) {
                $indispA = array_map('intval', explode(',', $customLineupA['Indisponiveis']));
                $outTeam1 = array_merge($outTeam1, $indispA);
            }
        }

        if ($customLineupB) {
            $stmtUp->bindValue(':clube', $matchInfo['timeB'], PDO::PARAM_INT);
            for ($i = 1; $i <= 11; $i++) {
                $stmtUp->bindValue(':jog' . $i, $customLineupB['Jogador' . $i], PDO::PARAM_INT);
            }
            $stmtUp->bindValue(':capitao', $customLineupB['Capitao'], PDO::PARAM_INT);
            $stmtUp->bindValue(':pen1', $customLineupB['Penalti1'], PDO::PARAM_INT);
            $stmtUp->bindValue(':pen2', $customLineupB['Penalti2'], PDO::PARAM_INT);
            $stmtUp->bindValue(':pen3', $customLineupB['Penalti3'], PDO::PARAM_INT);
            $stmtUp->execute();

            if (!empty($customLineupB['Indisponiveis'])) {
                $indispB = array_map('intval', explode(',', $customLineupB['Indisponiveis']));
                $outTeam2 = array_merge($outTeam2, $indispB);
            }
        }

        $outTeam1 = array_values(array_unique($outTeam1));
        $outTeam2 = array_values(array_unique($outTeam2));
    } catch (Exception $e) {}
    
    $competitionInfo = $competicaoObj->readInfo($idCompeticao);
    $nomeComposto = $competitionInfo['ano'] . " - " . $competitionInfo['nome'];
    $databasePath = "jdbc:sqlite:data/database.db3";
    $cores = $liteCompeticao->getColors();
    
    $fullDate = strtotime(date("j-n-Y", strtotime($matchInfo['data']))) * 1000 + 5*60*60*1000;
    $matchdayIndex = 1;
    
    // Obter as opções de desempate da competição
    $options = $competicaoObj->getOptions($idCompeticao);
    $faseJogo = isset($matchInfo['fase']) ? intval($matchInfo['fase']) : 0;
    
    $knockoutTiebraker = 0;
    $knockoutAwayGoals = false;
    
    if ($faseJogo > 2) {
        if ($faseJogo == 8) { // Final
            $tieOption = isset($options['criteriodesempatefinal']) ? intval($options['criteriodesempatefinal']) : 0;
            $knockoutTiebraker = ($tieOption === 0) ? 1 : 2;
            $knockoutAwayGoals = false;
        } else { // Outras fases de mata-mata
            $tieOption = isset($options['criteriodesempate']) ? intval($options['criteriodesempate']) : 0;
            $knockoutTiebraker = ($tieOption === 0) ? 1 : 2;
            $knockoutAwayGoals = isset($options['golfora']) && $options['golfora'] == 1;
        }
    }

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
            'knockoutTiebraker' => $knockoutTiebraker,
            'knockoutAwayGoals' => $knockoutAwayGoals,
        )]
    );
    
    $siglaA = $timeObj->getSigla($matchInfo['timeA']);
    $siglaB = $timeObj->getSigla($matchInfo['timeB']);
    $path = $siglaA . "x" . $siglaB . " - " . date("j-n-Y", strtotime($matchInfo['data']));
    $completePath = "/Partidas/" . $nomeComposto . "/" . $matchdayIndex . "º Rodada/" . $path . ".hyl";

    // Fechar todas as referências ao SQLite temporariamente para evitar locks de arquivo (SQLITE_BUSY) durante a simulação
    $stmtOrig = null;
    $stmtCustomA = null;
    $stmtCustomB = null;
    $stmtOut1 = null;
    $stmtOut2 = null;
    $stmtUp = null;
    $liteCompeticao = null;
    $timeObj = null;
    $ldb = null;

    $json = json_encode($json_array, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT);
    file_put_contents($hexacolorDir . "/agenda/json.txt", $json);
    
    // Executar simulador JAR
    $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    $dir = str_replace('\\', '/', $hexacolorDir);
    
    if ($isWindows) {
        $jarPath = $dir . "/HexacolorYMTv2.jar";
        $jsonPath = $dir . "/agenda/json.txt";
        $cmd = "cd /d \"$dir\" && java -Dfile.encoding=UTF-8 -Dsun.jnu.encoding=UTF-8 -Djava.awt.headless=true -jar \"$jarPath\" -m \"$jsonPath\" 2>&1";
    } else {
        $docRoot = dirname(__DIR__); // raiz do site /home/lhsaia/confusa.top
        $javaBin = $docRoot . "/java_station/jdk/jdk1.8.0_231/bin/java";
        $libPath = $hexacolorDir . "/lib";
        $tmpDir = $docRoot . "/java_station/tmp";
        $jarPath = $hexacolorDir . "/HexacolorYMTv2.jar";
        $jsonPath = $hexacolorDir . "/agenda/json.txt";
        $cmd = "cd {$hexacolorDir} && export LANG=en_US.UTF-8; export LC_ALL=en_US.UTF-8; $javaBin -Dfile.encoding=UTF-8 -Dsun.jnu.encoding=UTF-8 -Djava.awt.headless=true -Djava.library.path=$libPath -Djava.io.tmpdir=$tmpDir -jar $jarPath -m $jsonPath 2>&1";
    }
    
    $output = shell_exec($cmd . "\n");
    $output = ($output !== null) ? (string)$output : '';
    
    // Caso o motor execute apenas a rotina de compatibilidade/migração no primeiro disparo, reenviar a simulação
    $tentativas = 1;
    while ($tentativas < 3 && stripos($output, 'compatibilidade com o portal web') !== false) {
        $outputRetry = shell_exec($cmd . "\n");
        $outputRetryStr = ($outputRetry !== null) ? (string)$outputRetry : '';
        $output .= "\n[REENVIO AUTOMÁTICO #" . $tentativas . "]:\n" . $outputRetryStr;
        $tentativas++;
    }
    
    // Geração de LOG para debug do motor de simulação
    $logMessage = "[" . date('Y-m-d H:i:s') . "] CMD: " . $cmd . "\nOUTPUT:\n" . $output . "\n----------------------------------------\n";
    file_put_contents($hexacolorDir . "/simulation_debug.log", $logMessage, FILE_APPEND);
    
    // Reabrir conexão SQLite temporária para restaurar a escalação e salvar
    $liteDatabase = new SQLiteDatabase();
    $liteDatabase->fileName = $targetDbPath;
    $ldb = $liteDatabase->getConnection();

    // 4. Restaurar a escalação original/padrão no banco temporário antes de salvar de volta
    try {
        if (!empty($originalEscalacoes)) {
            $stmtRestore = $ldb->prepare("UPDATE escalacao SET 
                Jogador1 = :jog1, Jogador2 = :jog2, Jogador3 = :jog3, Jogador4 = :jog4, Jogador5 = :jog5, 
                Jogador6 = :jog6, Jogador7 = :jog7, Jogador8 = :jog8, Jogador9 = :jog9, Jogador10 = :jog10, Jogador11 = :jog11, 
                Capitao = :capitao, Penalti1 = :pen1, Penalti2 = :pen2, Penalti3 = :pen3
                WHERE Clube = :clube");
            foreach ($originalEscalacoes as $clubeId => $rowOrig) {
                $stmtRestore->bindValue(':clube', $clubeId, PDO::PARAM_INT);
                for ($i = 1; $i <= 11; $i++) {
                    $stmtRestore->bindValue(':jog' . $i, $rowOrig['Jogador' . $i], PDO::PARAM_INT);
                }
                $stmtRestore->bindValue(':capitao', $rowOrig['Capitao'], PDO::PARAM_INT);
                $stmtRestore->bindValue(':pen1', $rowOrig['Penalti1'], PDO::PARAM_INT);
                $stmtRestore->bindValue(':pen2', $rowOrig['Penalti2'], PDO::PARAM_INT);
                $stmtRestore->bindValue(':pen3', $rowOrig['Penalti3'], PDO::PARAM_INT);
                $stmtRestore->execute();
            }
        }
    } catch (Exception $e) {}

    // Fechar conexão SQLite para evitar locks no Windows
    $ldb = null;

    // 2. Copiar banco SQLite atualizado de volta
    if (file_exists($targetDbPath)) {
        copy($targetDbPath, $sourceDbPath);
    }
    
    $hylFile = $hexacolorDir . $completePath;
    
    if (!file_exists($hylFile)) {
        error_log("PHP Simulador: [ERRO] Cron falhou na partida #{$idPartida}. Comando: " . $cmd . " | Output: " . trim($output));
        echo "   [ERRO] A simulação da Partida #{$idPartida} falhou. O arquivo .hyl não foi gerado.\n";
        if (!empty($output)) {
            echo "   [DETALHE ENGINE]: " . trim($output) . "\n";
        }
        echo "   Pulando...\n";
        continue;
    }
    
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
    } else {
        error_log("PHP Simulador: [ERRO] Cron gerou súmula vazia/corrompida na partida #{$idPartida}. Output: " . trim($output));
        echo "   [ERRO] O arquivo .hyl para a Partida #{$idPartida} foi gerado mas está corrompido ou vazio. Pulando...\n";
        continue;
    }
    
    // Fallback para .hyj se penaltis nao vieram no .hyl
    $hyjFile = str_replace('.hyl', '.hyj', $hylFile);
    if ($penA === null && file_exists($hyjFile)) {
        $jsonHyj = json_decode(file_get_contents($hyjFile));
        if ($jsonHyj && isset($jsonHyj->penaltis) && $jsonHyj->penaltis) {
            $penA = isset($jsonHyj->time1->placarPenaltis) ? (int)$jsonHyj->time1->placarPenaltis : 0;
            $penB = isset($jsonHyj->time2->placarPenaltis) ? (int)$jsonHyj->time2->placarPenaltis : 0;
        }
    }

    // Atualizar resultado no MariaDB
    $competicaoObj->uploadMatchResults($idPartida, $golsTimeA, $golsTimeB, $path, $penA, $penB);

    // Processar desfalques pós jogo (cartões, lesões, suspensões) no MariaDB
    require_once __DIR__ . '/hexacolor/processar_desfalques.php';
    processarPosJogo($db, $idCompeticao, $idPartida, $hylFile, $hyjFile, $suspensos);

    // Se for partida de mata-mata, verificar se todos os confrontos da fase terminaram e avançar automaticamente
    if (isset($matchInfo['fase']) && (int)$matchInfo['fase'] > 2) {
        try {
            $competicaoObj->verificarEAvancarMataMata($idCompeticao, (int)$matchInfo['fase']);
        } catch (\Throwable $e) {
            error_log("PHP Simulador: [ERRO AVANÇO MATA-MATA CRON] " . $e->getMessage());
        }
    }

    // Se a opção subir_live estiver ativada na competição, enviar partida para o CONFUSA Live
    if (!empty($options['subir_live'])) {
        try {
            require_once $docRoot . '/lib/ConfusaLiveUploader.php';
            $faseNome = "Rodada " . (isset($matchInfo['fase']) ? $matchInfo['fase'] : '1');
            try {
                $stmtFase = $db->prepare("SELECT nome FROM fase WHERE id = :faseId LIMIT 1");
                $stmtFase->bindValue(':faseId', $matchInfo['fase'], PDO::PARAM_INT);
                $stmtFase->execute();
                if ($rowFase = $stmtFase->fetch(PDO::FETCH_ASSOC)) {
                    $faseNome = $rowFase['nome'];
                }
            } catch (\Throwable $e) {}

            if (!empty($matchInfo['grupo'])) {
                $faseNome .= " - Grupo " . $matchInfo['grupo'];
            }

            $liveResult = ConfusaLiveUploader::enviarPartida($hylFile, $nomeComposto, $faseNome, $matchInfo['data']);
            if ($liveResult['success']) {
                echo "   [LIVE] Partida #{$idPartida} enviada com sucesso para o CONFUSA Live.\n";
            } else {
                echo "   [LIVE AVISO] Não foi possível enviar a partida #{$idPartida} para o Live: {$liveResult['message']}\n";
            }
        } catch (\Throwable $e) {
            echo "   [LIVE EXCEÇÃO] Erro ao disparar upload: " . $e->getMessage() . "\n";
        }
    }

    $penMsg = ($penA !== null) ? " (Pên: {$penA}x{$penB})" : "";
    echo "   [SUCESSO] Partida #{$idPartida} simulada! Placar: {$siglaA} {$golsTimeA} x {$golsTimeB} {$siglaB}{$penMsg}\n";
}

echo "[" . date('Y-m-d H:i:s') . "] Processamento do Cron concluído com sucesso.\n";
?>
