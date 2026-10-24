<?php

//ini_set( 'display_errors', true );
//error_reporting( E_ALL );
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){

    if($_SESSION['emTestes'] ?? false){
        die(json_encode(['success' => false, 'error' => 'Usuários em período de testes não podem alterar competições.']));
    }

    $tipo = $_POST['tipo_alteracao'];
    
    // LOG DE DEBUG
    $logFile = 'debug_save.log';
    $logData = date('[Y-m-d H:i:s] ') . "INÍCIO DA REQUISIÇÃO\n";
    $logData .= "POST: " . json_encode($_POST) . "\n";
    file_put_contents($logFile, $logData, FILE_APPEND);
    file_put_contents('debug_save.txt', date('[Y-m-d H:i:s] ') . "Tipo: $tipo | Dados: " . json_encode($_POST) . "\n", FILE_APPEND);

    //conferir informações sobre o dono do time e do jogador vs o usuário logado!

    //estabelecer conexão com banco de dados
    include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/competicao_clube.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/config/sqliteDatabase.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogador.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/tecnico.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/estadio.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/clima.php");
    $database = new Database();
    $db = $database->getConnection();
    $competicao = new Competicao_clube($db);
    $usuario = new Usuario($db);
	$pais = new Pais($db);

	switch($tipo){
		case 0:
			//alterar pais do time
			$idCompeticao = $_POST['codigo_competicao'];
			$codigoTime = $_POST['codigo_time'];
			$paisTime = intval($_POST['pais_time']);
			
			if($paisTime > 0){
				$stCheckPais = $db->prepare("SELECT federacao, dono FROM paises WHERE id = ? LIMIT 1");
				$stCheckPais->execute([$paisTime]);
				$rCheckPais = $stCheckPais->fetch(PDO::FETCH_ASSOC);
				if(!$rCheckPais || empty($rCheckPais['federacao']) || intval($rCheckPais['dono']) === 0 || !in_array(intval($rCheckPais['federacao']), [1, 2, 3, 4])){
					die(json_encode(['success' => false, 'error' => 'Apenas países membros oficiais da CONFUSA são permitidos em competições.']));
				}
			}
			
			$prevTeamId = 0;
			try {
				$stPrev = $db->prepare("SELECT id_time_portal, has_team FROM competicao_times WHERE id_competicao = :idComp AND codigo_time = :cod LIMIT 1");
				$stPrev->bindParam(':idComp', $idCompeticao);
				$stPrev->bindParam(':cod', $codigoTime);
				$stPrev->execute();
				$rPrev = $stPrev->fetch(PDO::FETCH_ASSOC);
				if ($rPrev) {
					if (!empty($rPrev['id_time_portal'])) {
						$prevTeamId = intval($rPrev['id_time_portal']);
					} else if ($rPrev['has_team'] == 1 || $rPrev['has_team'] == '1') {
						$prevTeamId = -1 * abs(intval($codigoTime));
					}
				}
			} catch(Exception $e) {}

			if($competicao->alterarPaisTime($idCompeticao,$codigoTime, $paisTime )){
				$is_success = true;
				$error_msg = "";
				if ($paisTime == 0 && $prevTeamId != 0) {
					$competicao->atualizarJogosPorSlot($idCompeticao, $codigoTime, 0, $prevTeamId);
				}
			} else {
				$is_success = false;
				$error_msg = "Falha ao alterar país do time";
			}
			break;
		case 1:
		case 'sync_time':
			//alterar time baseado no portal
			$idCompeticao = $_POST['codigo_competicao'];
			$codigoTime = $_POST['codigo_time'];
			$paisTime = intval($_POST['pais_time']);
			$timePortal = intval($_POST['time_portal']);
			
			if($timePortal > 0){
				$stCheckClub = $db->prepare("SELECT p.federacao, p.dono FROM clube c JOIN paises p ON c.Pais = p.id WHERE c.ID = ? LIMIT 1");
				$stCheckClub->execute([$timePortal]);
				$rCheckClub = $stCheckClub->fetch(PDO::FETCH_ASSOC);
				if(!$rCheckClub || empty($rCheckClub['federacao']) || intval($rCheckClub['dono']) === 0 || !in_array(intval($rCheckClub['federacao']), [1, 2, 3, 4])){
					die(json_encode(['success' => false, 'error' => 'Apenas clubes de países membros oficiais da CONFUSA são permitidos em competições.']));
				}

				// Conferência das condições obrigatórias do clube
				$timeObjCheck = new Time($db);
				$errorConferencia = "";
				if($elencoMenor = $timeObjCheck->verificarElencoMenor(null, [$timePortal])){
					$errorConferencia .= "O time possui menos de 11 jogadores no elenco.<br>";
				}
				if($elencoMaior = $timeObjCheck->verificarElencoMaior(null, [$timePortal])){
					$errorConferencia .= "O time possui mais de 23 jogadores no elenco.<br>";
				}
				if($capitaoTime = $timeObjCheck->verificarCapitao(null, [$timePortal])){
					$errorConferencia .= "O time está sem capitão definido.<br>";
				}
				if($penaltisTime = $timeObjCheck->verificarPenaltis(null, [$timePortal])){
					$errorConferencia .= "O time está sem todos os cobradores de pênaltis definidos.<br>";
				}
				if($goleirosTime = $timeObjCheck->verificarGoleiros(null, [$timePortal])){
					$errorConferencia .= "O time possui número incorreto de goleiros escalados.<br>";
				}
				if($escalacaoTime = $timeObjCheck->verificarEscalacoes(null, [$timePortal])){
					$errorConferencia .= "O time não possui os 11 titulares escalados.<br>";
				}
				if($aposentadosTime = $timeObjCheck->verificarAposentados(null, [$timePortal])){
					$errorConferencia .= "O time possui jogadores acima da idade limite.<br>";
				}
				if($tecnicosTimes = $timeObjCheck->verificarTecnicos(null, [$timePortal])){
					foreach($tecnicosTimes as $tecErr){
						$qtd = intval($tecErr[1] ?? 0);
						if($qtd === 0){
							$errorConferencia .= "O time está sem técnico definido.<br>";
						} else if($qtd > 1){
							$errorConferencia .= "O time possui {$qtd} técnicos vinculados simultaneamente (duplicidade). Remova os técnicos excedentes no painel da equipe.<br>";
						} else {
							$errorConferencia .= "Problema no cadastro de técnico do time.<br>";
						}
					}
				}
				if($climaEstadioTimes = $timeObjCheck->verificarClimaEstadio(null, [$timePortal])){
					$errorConferencia .= "O time está sem estádio associado ou o estádio está sem clima cadastrado.<br>";
				}
				if(!empty($errorConferencia)){
					die(json_encode(['success' => false, 'error' => $errorConferencia, 'errors' => $errorConferencia]));
				}
			}
			
			$prevTeamId = 0;
			$prevSlot = null;
			try {
				$stPrev = $db->prepare("SELECT id_time_portal, has_team, slot FROM competicao_times WHERE id_competicao = :idComp AND codigo_time = :cod LIMIT 1");
				$stPrev->bindParam(':idComp', $idCompeticao);
				$stPrev->bindParam(':cod', $codigoTime);
				$stPrev->execute();
				$rPrev = $stPrev->fetch(PDO::FETCH_ASSOC);
				if ($rPrev) {
					if (!empty($rPrev['id_time_portal'])) {
						$prevTeamId = intval($rPrev['id_time_portal']);
					} else if ($rPrev['has_team'] == 1 || $rPrev['has_team'] == '1') {
						$prevTeamId = -1 * abs(intval($codigoTime));
					}
					if (!empty($rPrev['slot'])) {
						$prevSlot = $rPrev['slot'];
					}
				}
			} catch(Exception $e) {}
			
			// codigo para efetivamente copiar o time para o banco de dados

				$error_msg = "";

				$lite_database = new SQLiteDatabase();
				$lite_database->fileName = $_SERVER['DOCUMENT_ROOT']."/competicoes/databases/".$idCompeticao . "-database.db3";
				$lite_database->getConnection();
				// Garante que as tabelas existam (para competições novas ou banco vazio)
				if($lite_database->conn !== null){
					$lite_database->prepareTables();
				}

					$megaQuery = "BEGIN TRANSACTION; ";

					$time = new Time($db);
					$jogador = new Jogador($db);
					$tecnico = new Tecnico($db);
					$estadio = new Estadio($db);
					$novoClima = new Clima($db);

					//buscar tecnico e adicionar na query
					$stmt = $tecnico->exportacao(null, $timePortal);

					while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
						$nomeTecnico = html_entity_decode((string)($row['Nome'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
						$nomeTecnico = str_replace("'", "''", $nomeTecnico);
						$megaQuery .= "INSERT OR REPLACE INTO tecnico (ID, Nome, Idade, Nivel, Mentalidade, Estilo) VALUES ('{$row['ID']}', '{$nomeTecnico}', '{$row['Idade']}', '{$row['Nivel']}', '{$row['Mentalidade']}', '{$row['Estilo']}'); ";
					}

					//buscar posicoes dos jogadores e adicionar na query
					$stmt = $jogador->exportacao(null,$timePortal);

					while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
						$sp = str_pad((string)($row['StringPosicoes'] ?? ''), 15, '0');
						$megaQuery .= "INSERT OR REPLACE INTO posicaojogador (Jogador, G, LD, LE, Z, AD, AE, V, MD, ME, MC, PD, PE, MA, Am, Aa) VALUES ('{$row['idJogador']}', '{$sp[0]}', '{$sp[1]}', '{$sp[2]}', '{$sp[3]}', '{$sp[4]}', '{$sp[5]}', '{$sp[6]}', '{$sp[7]}', '{$sp[8]}', '{$sp[9]}', '{$sp[10]}', '{$sp[11]}', '{$sp[12]}', '{$sp[13]}', '{$sp[14]}'); ";

						$nomeJogador = html_entity_decode((string)($row['nomeJogador'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
						$nomeJogador = str_replace("'", "''", $nomeJogador);
						$megaQuery .= "INSERT OR REPLACE INTO jogador (ID, Nome, Idade, Nivel, Potencial, CrescBase, Mentalidade, CobradorFalta) VALUES ('{$row['idJogador']}', '{$nomeJogador}', '{$row['Idade']}', '{$row['Nivel']}', '0' , '0', '{$row['Mentalidade']}', '{$row['CobradorFalta']}'); ";

						$testeNacionalidade = ($row['Nacionalidade'] != null ? $row['Nacionalidade'] : '-');
						$megaQuery .= "INSERT OR REPLACE INTO nacionalidades (ID_Jogador, Nacionalidade) VALUES ('{$row['idJogador']}', '{$testeNacionalidade}'); ";

						if($sp[0] == 1){
							$megaQuery .= "INSERT OR REPLACE INTO atributosgoleiro (Goleiro, Reflexos, Seguranca, Saidas, JogoAereo, Lancamentos, DefesaPenaltis, Determinacao, determinacaoOriginal) VALUES ('{$row['idJogador']}', '{$row['Reflexos']}', '{$row['Seguranca']}', '{$row['Saidas']}', '{$row['JogoAereo']}', '{$row['Lancamentos']}', '{$row['DefesaPenaltis']}', '1', '1'); ";

							$somaZero = abs(($row['Nivel'] * 0.50) - ($row['somaAtributos']));
							if($somaZero > 0.5){
								$megaQuery .= "INSERT OR REPLACE INTO jogadorpendente (idJogador) VALUES ('{$row['idJogador']}'); ";
							}

						} else {
							$megaQuery .= "INSERT OR REPLACE INTO atributosjogador (Jogador, Marcacao, Desarme, VisaoJogo, Movimentacao, Cruzamentos, Cabeceamento, Tecnica, ControleBola, Finalizacao, FaroGol, Velocidade, Forca, Determinacao, determinacaoOriginal) VALUES ('{$row['idJogador']}', '{$row['Marcacao']}', '{$row['Desarme']}', '{$row['VisaoJogo']}', '{$row['Movimentacao']}', '{$row['Cruzamentos']}', '{$row['Cabeceamento']}', '{$row['Tecnica']}', '{$row['ControleBola']}', '{$row['Finalizacao']}', '{$row['FaroGol']}', '{$row['Velocidade']}', '{$row['Forca']}', '1', '1'); ";

							$somaZero = abs(($row['Nivel'] * 0.65) - ($row['somaAtributos']));
							if($somaZero > 0.5){
								$megaQuery .= "INSERT OR REPLACE INTO jogadorpendente (idJogador) VALUES ('{$row['idJogador']}'); ";
							}
						}

					}

					//buscar estadio e adicionar na query
					$stmt = $estadio->exportacao(null, $timePortal);

					while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
						$nomeEstadio = html_entity_decode((string)($row['Nome'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
						$nomeEstadio = str_replace("'", "''", $nomeEstadio);
						$megaQuery .= "INSERT OR REPLACE INTO estadio (ID, Nome, Capacidade, Clima, Altitude, Caldeirao) VALUES ('{$row['ID']}', '{$nomeEstadio}', '{$row['Capacidade']}', '{$row['Clima']}', '{$row['Altitude']}', '{$row['Caldeirao']}'); ";

					}

					//buscar climas e adicionar na query
					$stmt = $novoClima->exportacao(null, $timePortal);

					while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
						$nomeClima = html_entity_decode((string)($row['nomeClima'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
						$nomeClima = str_replace("'", "''", $nomeClima);
						$megaQuery .= "INSERT OR REPLACE INTO clima (ID, Nome, TempVerao, EstiloVerao, TempOutono, EstiloOutono, TempInverno, EstiloInverno, TempPrimavera, EstiloPrimavera, Hemisferio) VALUES ('{$row['idClima']}', '{$nomeClima}', '{$row['TempVerao']}', '{$row['EstiloVerao']}', '{$row['TempOutono']}', '{$row['EstiloOutono']}', '{$row['TempInverno']}', '{$row['EstiloInverno']}', '{$row['TempPrimavera']}', '{$row['EstiloPrimavera']}', '{$row['Hemisferio']}'); ";

					}

					//buscar clubes e adicionar na query
					$stmt = $time->exportacao(null, $timePortal);

					while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){

						//tratar uniforme e simbolo
						$escudoTratado = "../../images/escudos/" . $row['Escudo'];
						$uni1Tratado = "../../images/uniformes/" . $row['Uniforme1'];
						$uni2Tratado = "../../images/uniformes/" . $row['Uniforme2'];

						$nomeExportado = html_entity_decode((string)($row['Nome'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
						$nomeExportado = str_replace("'", "''", $nomeExportado);

						$megaQuery .= "INSERT OR REPLACE INTO clube (ID, Nome, TresLetras, Estadio, Escudo, Uni1Cor1, Uni1Cor2, Uni1Cor3, Uniforme1, Uni2Cor1, Uni2Cor2, Uni2Cor3, Uniforme2, MaxTorcedores, Fidelidade) VALUES ('{$row['ID']}', '{$nomeExportado}', '{$row['TresLetras']}', '{$row['Estadio']}', '{$escudoTratado}', '{$row['Uni1Cor1']}', '{$row['Uni1Cor2']}', '{$row['Uni1Cor3']}', '{$uni1Tratado}', '{$row['Uni2Cor1']}', '{$row['Uni2Cor2']}', '{$row['Uni2Cor3']}', '{$uni2Tratado}', '{$row['MaxTorcedores']}', '{$row['Fidelidade']}'); ";

						$elenco = array();
						$elenco[] = $row['ID'];
						$newStmt = $time->getElenco($row['ID']);
						$jogadoresElenco = array();
						while($newRow = $newStmt->fetch(PDO::FETCH_ASSOC)){
							$jogadoresElenco[] = $newRow['ID'];
						}
						for ($i = 0; $i < 23; $i++) {
							$elenco[] = isset($jogadoresElenco[$i]) ? $jogadoresElenco[$i] : '0';
						}
						$tecStmt = $time->getTecnico($row['ID']);
						$idTecnico = '0';
						if($tecRow = $tecStmt->fetch(PDO::FETCH_ASSOC)){
							$idTecnico = !empty($tecRow['tecnico']) ? $tecRow['tecnico'] : '0';
						}
						$elenco[] = $idTecnico;

						$megaQuery .= "INSERT OR REPLACE INTO elenco (Clube, Jogador1, Jogador2, Jogador3, Jogador4, Jogador5, Jogador6, Jogador7, Jogador8, Jogador9, Jogador10, Jogador11, Jogador12, Jogador13, Jogador14, Jogador15, Jogador16, Jogador17, Jogador18, Jogador19, Jogador20, Jogador21, Jogador22, Jogador23, Tecnico) VALUES ('{$elenco[0]}', '{$elenco[1]}', '{$elenco[2]}', '{$elenco[3]}', '{$elenco[4]}', '{$elenco[5]}', '{$elenco[6]}', '{$elenco[7]}', '{$elenco[8]}', '{$elenco[9]}', '{$elenco[10]}', '{$elenco[11]}', '{$elenco[12]}', '{$elenco[13]}', '{$elenco[14]}', '{$elenco[15]}', '{$elenco[16]}', '{$elenco[17]}', '{$elenco[18]}', '{$elenco[19]}', '{$elenco[20]}', '{$elenco[21]}', '{$elenco[22]}', '{$elenco[23]}', '{$elenco[24]}'); ";

						$escalacao = array();
						$escalacao[] = $row['ID'];
						$escStmt = $time->getEscalacao($row['ID']);
						$titulares = array();
						while($escRow = $escStmt->fetch(PDO::FETCH_ASSOC)){
							$titulares[] = [
								'posicao' => $escRow['posicaoBase'] ?? '',
								'jogador' => $escRow['jogador'] ?? '0'
							];
						}
						for ($i = 0; $i < 11; $i++) {
							$escalacao[] = isset($titulares[$i]['posicao']) ? $titulares[$i]['posicao'] : '';
							$escalacao[] = isset($titulares[$i]['jogador']) ? $titulares[$i]['jogador'] : '0';
						}

						$capStmt = $time->getCapitao($row['ID']);
						$idCapitao = '0';
						if($capRow = $capStmt->fetch(PDO::FETCH_ASSOC)){
							$idCapitao = !empty($capRow['jogador']) ? $capRow['jogador'] : '0';
						}
						$escalacao[] = $idCapitao;

						$penStmt = $time->getPenaltis($row['ID']);
						$batedoresPenalti = array();
						while($penRow = $penStmt->fetch(PDO::FETCH_ASSOC)){
							$batedoresPenalti[] = !empty($penRow['jogador']) ? $penRow['jogador'] : '0';
						}
						for ($i = 0; $i < 3; $i++) {
							$escalacao[] = isset($batedoresPenalti[$i]) ? $batedoresPenalti[$i] : '0';
						}

						$megaQuery .= "INSERT OR REPLACE INTO escalacao (Clube, Pos1, Jogador1, Pos2, Jogador2, Pos3, Jogador3, Pos4, Jogador4, Pos5, Jogador5, Pos6, Jogador6, Pos7, Jogador7, Pos8, Jogador8, Pos9, Jogador9, Pos10, Jogador10, Pos11, Jogador11, Capitao, Penalti1, Penalti2, Penalti3) VALUES ('{$escalacao[0]}', '{$escalacao[1]}', '{$escalacao[2]}', '{$escalacao[3]}', '{$escalacao[4]}', '{$escalacao[5]}', '{$escalacao[6]}', '{$escalacao[7]}', '{$escalacao[8]}', '{$escalacao[9]}', '{$escalacao[10]}', '{$escalacao[11]}', '{$escalacao[12]}', '{$escalacao[13]}', '{$escalacao[14]}', '{$escalacao[15]}', '{$escalacao[16]}', '{$escalacao[17]}', '{$escalacao[18]}', '{$escalacao[19]}', '{$escalacao[20]}', '{$escalacao[21]}', '{$escalacao[22]}', '{$escalacao[23]}', '{$escalacao[24]}', '{$escalacao[25]}', '{$escalacao[26]}'); ";

					}
					//testes
					// echo '<pre>' , var_dump($megaQuery) , '</pre>';
					// die();

				$megaQuery .= "COMMIT; ";
				try {
					$lite_database->directRun($megaQuery);
					file_put_contents($logFile, "SQLITE: Sucesso na exportação\n", FILE_APPEND);
				} catch (\Throwable $e) {
					file_put_contents($logFile, "SQLITE ERRO: " . $e->getMessage() . "\n", FILE_APPEND);
					die(json_encode([
						'success' => false,
						'error' => "Erro ao gravar dados no banco SQLite da competição: " . $e->getMessage(),
						'errors' => "Erro ao gravar dados no banco SQLite da competição: " . $e->getMessage()
					]));
				}

			// fim do codigo para copiar time para o banco de dados
			
			if($competicao->alterarTimePortal($idCompeticao,$codigoTime, $paisTime, $timePortal )){
				$is_success = true;
				$error_msg = "";
				file_put_contents($logFile, "MYSQL: Sucesso ao vincular time portal\n", FILE_APPEND);
				
				if(!empty($prevSlot)){
					$competicao->definirSlotTime($idCompeticao, $codigoTime, $prevSlot);
				} else {
					$competicao->atualizarJogosPorSlot($idCompeticao, $codigoTime, $timePortal, $prevTeamId);
				}
			} else {
				$is_success = false;
				$error_msg = "Falha ao alterar time com base no portal";
				file_put_contents($logFile, "MYSQL ERRO: Falha ao vincular time portal\n", FILE_APPEND);
			}
			break;
		case 2:
		
			break;
		case 3:
		case 'bulk_pais':
			// Definir mesmo país para todas as vagas da competição
			$idCompeticao = isset($_POST['codigo_competicao']) ? intval($_POST['codigo_competicao']) : 0;
			$paisTime = isset($_POST['pais_time']) ? intval($_POST['pais_time']) : 0;
			$numTimes = isset($_POST['numero_times']) ? intval($_POST['numero_times']) : 0;
			
			if ($numTimes <= 0 && $idCompeticao > 0) {
				$opts = $competicao->getOptions($idCompeticao);
				$numTimes = isset($opts['numero_times']) ? intval($opts['numero_times']) : 0;
			}
			
			if ($idCompeticao > 0 && $numTimes > 0) {
				if ($competicao->definirMesmoPaisTodos($idCompeticao, $paisTime, $numTimes)) {
					$is_success = true;
					$error_msg = "";
				} else {
					$is_success = false;
					$error_msg = "Falha ao definir o país em lote para a competição.";
				}
			} else {
				$is_success = false;
				$error_msg = "Parâmetros inválidos para alteração em lote.";
			}
			break;
		default:
			break;
	}

} else {
    $is_success = false;
    $error_msg = "Usuário não tem acesso para realizar essa ação";
}

die(json_encode([ 'success'=> $is_success, 'error'=> $error_msg]));


?>
