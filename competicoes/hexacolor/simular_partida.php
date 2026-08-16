<?php  
	require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
	
	date_default_timezone_set('America/Sao_Paulo');

    $idPartida = isset($_POST['matchId']) ? $_POST['matchId'] : 0;
	if(!$idPartida){
		die(json_encode(['success' => false, 'error' => 'ID da partida não informado.']));
	}

	// Lock de concorrência com fila de espera (bloqueante com timeout de 45s)
	$lockFile = __DIR__ . "/simulation.lock";
	$lockHandle = fopen($lockFile, "c+");
	if (!$lockHandle) {
		die(json_encode(['success' => false, 'error' => 'Não foi possível inicializar o arquivo de lock de simulação.']));
	}

	$startTime = time();
	$acquired = false;
	while (time() - $startTime < 45) {
		if (flock($lockHandle, LOCK_EX | LOCK_NB)) {
			$acquired = true;
			break;
		}
		usleep(500000); // espera 0.5 segundos antes de tentar novamente
	}

	if (!$acquired) {
		fclose($lockHandle);
		die(json_encode(['success' => false, 'error' => 'O simulador está ocupado com outras partidas da fila no momento. Por favor, tente novamente em alguns instantes.']));
	}

	// Garante a liberação do lock ao final do script ou interrupções abruptas
	register_shutdown_function(function() use ($lockHandle) {
		flock($lockHandle, LOCK_UN);
		fclose($lockHandle);
	});
	
	//estabelecer conexão com banco de dados
	include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/competicao_clube.php");
	
	include_once($_SERVER['DOCUMENT_ROOT']."/config/sqliteDatabase.php");
	
	$database = new Database();
	$db = $database->getConnection();
	$usuario = new Usuario($db);
	$competicao = new Competicao_clube($db);
	
	// puxar infos da partida do banco de dados
	$matchInfo = $competicao->getMatchInfo($idPartida);
	if(!$matchInfo){
		die(json_encode(['success' => false, 'error' => 'Partida não encontrada.']));
	}
	
	$idCompeticao = $matchInfo['competicao'];
	$sourceDbPath = $_SERVER['DOCUMENT_ROOT']."/competicoes/databases/".$idCompeticao."-database.db3";
	
	$targetDbPath = __DIR__ . "/data/database.db3";
	
	if(!file_exists($sourceDbPath)){
		die(json_encode(['success' => false, 'error' => 'Banco de dados da competição não encontrado.']));
	}

	// Criar pasta data se não existir e copiar banco
	if(!is_dir(__DIR__ . "/data")){
		mkdir(__DIR__ . "/data", 0777, true);
	}
	copy($sourceDbPath, $targetDbPath);
		
	$liteDatabase = new SQLiteDatabase();
	$liteDatabase->fileName = $targetDbPath;
	$ldb = $liteDatabase->getConnection();
	$liteCompeticao = new Competicao_clube($ldb);
	$time = new Time($ldb);
	
	// Garantir tabela de compatibilidade com as versões necessárias
	try {
		$ldb->exec("DROP TABLE IF EXISTS `comp10_temp_table`");
		$ldb->exec("CREATE TABLE IF NOT EXISTS `compatibilidade` (`versao` TEXT)");
		$stmtCheck = $ldb->query("SELECT `versao` FROM `compatibilidade`");
		$existingVersions = $stmtCheck->fetchAll(PDO::FETCH_COLUMN);
		
		$requiredVersions = ['2.8', '2.9.1', '2.10', '2.13'];
		$stmtInsert = $ldb->prepare("INSERT INTO `compatibilidade` (`versao`) VALUES (:versao)");
		foreach ($requiredVersions as $v) {
			if (!in_array($v, $existingVersions)) {
				$stmtInsert->bindValue(':versao', $v, PDO::PARAM_STR);
				$stmtInsert->execute();
			}
		}
	} catch (Exception $e) {
		error_log("Erro ao aplicar compatibilidade no SQLite: " . $e->getMessage());
	}
	
	$competitionInfo = $competicao->readInfo($idCompeticao);
	
	$isAdmin = isset($_SESSION['admin_status']) && $_SESSION['admin_status'] == 1;
	$isDono = isset($_SESSION['user_id']) && isset($competitionInfo['dono']) && $_SESSION['user_id'] == $competitionInfo['dono'];
	if(!$isAdmin && !$isDono){
		die(json_encode(['success' => false, 'error' => 'Apenas administradores ou o criador da competição podem simular partidas.']));
	}
	
	$nomeComposto = $competitionInfo['ano'] . " - " . $competitionInfo['nome'];
	$databasePath = "jdbc:sqlite:data/database.db3";
	
	$cores = $liteCompeticao->getColors();
	
	$fullDate = strtotime(date("j-n-Y",strtotime($matchInfo['data']))) * 1000 + 5*60*60*1000;
	$matchdayIndex = 1;

	// 1. Guardar a escalação original (padrão) na memória para restaurar depois da simulação
	$originalEscalacoes = [];
	try {
		$stmtOrig = $ldb->prepare("SELECT * FROM escalacao WHERE Clube IN (:timeA, :timeB)");
		$stmtOrig->bindValue(':timeA', $matchInfo['timeA_id'], PDO::PARAM_INT);
		$stmtOrig->bindValue(':timeB', $matchInfo['timeB_id'], PDO::PARAM_INT);
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
		$stmtCustomA->bindValue(':clube', $matchInfo['timeA_id'], PDO::PARAM_INT);
		$stmtCustomA->execute();
		$customLineupA = $stmtCustomA->fetch(PDO::FETCH_ASSOC);

		$stmtCustomB = $ldb->prepare("SELECT * FROM escalacao_jogo WHERE Jogo = :jogo AND Clube = :clube LIMIT 1");
		$stmtCustomB->bindValue(':jogo', $matchInfo['id'], PDO::PARAM_INT);
		$stmtCustomB->bindValue(':clube', $matchInfo['timeB_id'], PDO::PARAM_INT);
		$stmtCustomB->execute();
		$customLineupB = $stmtCustomB->fetch(PDO::FETCH_ASSOC);
	} catch (Exception $e) {}
	
	// Obter desfalques (suspensos e lesionados) de cada time no MariaDB e sincronizar no SQLite
	$outTeam1 = array();
	$outTeam2 = array();
	$suspensos = array();
	
	// Obter os IDs de jogadores no elenco de cada time do SQLite
	$team1Players = [];
	try {
		$stmtT1 = $ldb->prepare("SELECT * FROM elenco WHERE Clube = :clube LIMIT 1");
		$stmtT1->bindValue(':clube', $matchInfo['timeA_id']);
		$stmtT1->execute();
		$elRow1 = $stmtT1->fetch(PDO::FETCH_ASSOC);
		if ($elRow1) {
			for ($i = 1; $i <= 23; $i++) {
				if (!empty($elRow1['Jogador' . $i])) {
					$team1Players[] = (int)$elRow1['Jogador' . $i];
				}
			}
		}
	} catch (Exception $e) {}

	$team2Players = [];
	try {
		$stmtT2 = $ldb->prepare("SELECT * FROM elenco WHERE Clube = :clube LIMIT 1");
		$stmtT2->bindValue(':clube', $matchInfo['timeB_id']);
		$stmtT2->execute();
		$elRow2 = $stmtT2->fetch(PDO::FETCH_ASSOC);
		if ($elRow2) {
			for ($i = 1; $i <= 23; $i++) {
				if (!empty($elRow2['Jogador' . $i])) {
					$team2Players[] = (int)$elRow2['Jogador' . $i];
				}
			}
		}
	} catch (Exception $e) {}

	$allMatchPlayers = array_merge($team1Players, $team2Players);
	$lesionados = [];

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

		// Garantir que as colunas existam no SQLite temporário
		try {
			$ldb->exec("ALTER TABLE jogador ADD COLUMN Suspenso INTEGER DEFAULT 0");
		} catch (Exception $e) {}
		try {
			$ldb->exec("ALTER TABLE jogador ADD COLUMN Lesionado INTEGER DEFAULT 0");
		} catch (Exception $e) {}

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
			$stmtUp->bindValue(':clube', $matchInfo['timeA_id'], PDO::PARAM_INT);
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
			$stmtUp->bindValue(':clube', $matchInfo['timeB_id'], PDO::PARAM_INT);
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

	$siglaA = $time->getSigla($matchInfo['timeA_id']);
	$siglaB = $time->getSigla($matchInfo['timeB_id']);

	// Fechar a conexão SQLite temporariamente para evitar locks de arquivo (SQLITE_BUSY) durante a simulação do JAR
	$stmtOrig = null;
	$stmtCustomA = null;
	$stmtCustomB = null;
	$stmtOut1 = null;
	$stmtOut2 = null;
	$stmtUp = null;
	$liteCompeticao = null;
	$time = null;
	$ldb = null;

	// Obter as opções de desempate da competição
	$options = $competicao->getOptions($idCompeticao);
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

	// escrever no JSON
	$json_array = array('calendarName' => $nomeComposto,
						'color1' => isset($cores['partidaCor1']) ? $cores['partidaCor1'] : '#000000', 
						'color2' => isset($cores['partidaCor2']) ? $cores['partidaCor2'] : '#ffffff', 
						'color3' => isset($cores['partidaCor3']) ? $cores['partidaCor3'] : '#cccccc', 
						'matchdayIndex' => $matchdayIndex,
						'matches' => [array(
							'databasePath' => $databasePath,
							'id' => $matchInfo['id'],
							'date' => $fullDate,
							'idTeam1' => $matchInfo['timeA_id'],
							'idTeam2' => $matchInfo['timeB_id'],
							'kitTeam1' => 0,
							'kitTeam2' => 0,
							'idChosenGround' => $matchInfo['estadio'],
							'neutralGround' => boolval($matchInfo['neutro']),
							'outTeam1' => $outTeam1,
							'outTeam2' => $outTeam2,
							'knockoutTiebraker' => $knockoutTiebraker,
							'knockoutAwayGoals' => $knockoutAwayGoals, 
						)]);

	$json = json_encode($json_array, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT);
	
	if(file_put_contents(__DIR__ . "/agenda/json.txt", $json)){
		
		$isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
		
		if($isWindows){
			// Local (Windows)
			$dir = str_replace('\\', '/', __DIR__);
			$jarPath = $dir . "/HexacolorYMTv2.jar";
			$jsonPath = $dir . "/agenda/json.txt";
			$cmd = "java -Dfile.encoding=UTF-8 -Dsun.jnu.encoding=UTF-8 -Djava.awt.headless=true -jar \"$jarPath\" -m \"$jsonPath\" 2>&1";
		} else {
			// Produção (Linux)
			$docRoot = dirname(__DIR__, 2);
			$javaBin = $docRoot . "/java_station/jdk/jdk1.8.0_231/bin/java";
			$libPath = $docRoot . "/competicoes/hexacolor/lib";
			$tmpDir = $docRoot . "/java_station/tmp";
			$jarPath = $docRoot . "/competicoes/hexacolor/HexacolorYMTv2.jar";
			$jsonPath = $docRoot . "/competicoes/hexacolor/agenda/json.txt";
			$cmd = "export LANG=en_US.UTF-8; export LC_ALL=en_US.UTF-8; $javaBin -Dfile.encoding=UTF-8 -Dsun.jnu.encoding=UTF-8 -Djava.awt.headless=true -Djava.library.path=$libPath -Djava.io.tmpdir=$tmpDir -jar $jarPath -m $jsonPath 2>&1";
		}

		$output = shell_exec($cmd . "\n");
		
		// Geração de LOG para debug do motor de simulação no arquivo local
		$logMessage = "[" . date('Y-m-d H:i:s') . "] CMD: " . $cmd . "\nOUTPUT:\n" . $output . "\n----------------------------------------\n";
		file_put_contents(__DIR__ . "/simulation_debug.log", $logMessage, FILE_APPEND);
		
		// Reabrir conexão com banco de dados SQLite para restaurar a escalação e salvar
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

		// Fechar conexões para garantir gravação e evitar locks no Windows
		$ldb = null;
		
		// Copiar o banco atualizado de volta
		if(file_exists($targetDbPath)){
			copy($targetDbPath, $sourceDbPath);
		}

		$path = $siglaA . "x" . $siglaB . " - " . date("j-n-Y",strtotime($matchInfo['data']));
		$completePath = "/Partidas/" . $nomeComposto . "/" . $matchdayIndex . "º Rodada/" . $path . ".hyl";
		
		$hylFile = __DIR__ . $completePath;
		
		if (!file_exists($hylFile)) {
			// Envia apenas em caso de erro real para o Log Central
			error_log("PHP Simulador: [ERRO] A simulação falhou. Comando: " . $cmd . " | Output: " . trim($output));
			die(json_encode([ 'success'=> false, 'error'=> "Erro: O arquivo .hyl da súmula não foi gerado pelo motor. Verifique os logs de erros da engine." ]));
		}
		
		$golsTimeA = 0;
		$golsTimeB = 0;
		$xml = json_decode(file_get_contents($hylFile));
		if ($xml) {
			$golsTimeA = (int)$xml->placarTime1;
			$golsTimeB = (int)$xml->placarTime2;
		} else {
			error_log("PHP Simulador: [ERRO] Súmula corrompida. Output: " . trim($output));
			die(json_encode([ 'success'=> false, 'error'=> "Erro: O arquivo .hyl da súmula foi criado porém está corrompido ou vazio." ]));
		}
		
		// Atualizar o resultado no banco principal MariaDB
		$competicao->uploadMatchResults($idPartida, $golsTimeA, $golsTimeB, $path);

		// Processar desfalques pós jogo (cartões, lesões, suspensões) no MariaDB
		require_once __DIR__ . '/processar_desfalques.php';
		$hyjFile = str_replace('.hyl', '.hyj', $hylFile);
		processarPosJogo($db, $idCompeticao, $idPartida, $hylFile, $hyjFile, $suspensos);

		die(json_encode([ 'success'=> true, 'error'=> ""]));
	} else {
		die(json_encode([ 'success'=> false, 'error'=> "Erro ao escrever arquivo agenda/json.txt"]));
	}
	
	

	

    
   
    
 ?>