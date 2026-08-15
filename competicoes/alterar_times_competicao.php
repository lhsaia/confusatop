<?php

//ini_set( 'display_errors', true );
//error_reporting( E_ALL );
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){

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
			$paisTime = $_POST['pais_time'];
			
			if($competicao->alterarPaisTime($idCompeticao,$codigoTime, $paisTime )){
				$is_success = true;
				$error_msg = "";
			} else {
				$is_success = false;
				$error_msg = "Falha ao alterar país do time";
			}
			break;
		case 1:
			//alterar time baseado no portal
			$idCompeticao = $_POST['codigo_competicao'];
			$codigoTime = $_POST['codigo_time'];
			$paisTime = $_POST['pais_time'];
			$timePortal = $_POST['time_portal'];
			
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
						$nomeTecnico = str_replace("'", "''", $row['Nome']);
						$megaQuery .= "INSERT OR IGNORE INTO tecnico VALUES ('{$row['ID']}', '{$nomeTecnico}', '{$row['Idade']}', '{$row['Nivel']}', '{$row['Mentalidade']}', '{$row['Estilo']}'); ";
					}

					//buscar posicoes dos jogadores e adicionar na query
					$stmt = $jogador->exportacao(null,$timePortal);

					while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
						$megaQuery .= "INSERT OR IGNORE INTO posicaojogador VALUES ('{$row['idJogador']}', '{$row['StringPosicoes'][0]}', '{$row['StringPosicoes'][1]}', '{$row['StringPosicoes'][2]}', '{$row['StringPosicoes'][3]}', '{$row['StringPosicoes'][4]}', '{$row['StringPosicoes'][5]}', '{$row['StringPosicoes'][6]}', '{$row['StringPosicoes'][7]}', '{$row['StringPosicoes'][8]}', '{$row['StringPosicoes'][9]}', '{$row['StringPosicoes'][10]}', '{$row['StringPosicoes'][11]}', '{$row['StringPosicoes'][12]}', '{$row['StringPosicoes'][13]}', '{$row['StringPosicoes'][14]}'); ";

						$nomeJogador = str_replace("'", "''", $row['nomeJogador']);
						$megaQuery .= "INSERT OR IGNORE INTO jogador VALUES ('{$row['idJogador']}', '{$nomeJogador}', '{$row['Idade']}', '{$row['Nivel']}', '0' , '0', '{$row['Mentalidade']}', '{$row['CobradorFalta']}'); ";

						$testeNacionalidade = ($row['Nacionalidade'] != null ? $row['Nacionalidade'] : '-');
						$megaQuery .= "INSERT OR IGNORE INTO nacionalidades VALUES ('{$row['idJogador']}', '{$testeNacionalidade}'); ";

						if($row['StringPosicoes'][0] == 1){
							$megaQuery .= "INSERT OR IGNORE INTO atributosgoleiro VALUES ('{$row['idJogador']}', '{$row['Reflexos']}', '{$row['Seguranca']}', '{$row['Saidas']}', '{$row['JogoAereo']}', '{$row['Lancamentos']}', '{$row['DefesaPenaltis']}', '1', '1'); ";

							$somaZero = abs(($row['Nivel'] * 0.50) - ($row['somaAtributos']));
							if($somaZero > 0.5){
								$megaQuery .= "INSERT OR IGNORE INTO jogadorpendente VALUES ('{$row['idJogador']}'); ";
							}

						} else {
							$megaQuery .= "INSERT OR IGNORE INTO atributosjogador VALUES ('{$row['idJogador']}', '{$row['Marcacao']}', '{$row['Desarme']}', '{$row['VisaoJogo']}', '{$row['Movimentacao']}', '{$row['Cruzamentos']}', '{$row['Cabeceamento']}', '{$row['Tecnica']}', '{$row['ControleBola']}', '{$row['Finalizacao']}', '{$row['FaroGol']}', '{$row['Velocidade']}', '{$row['Forca']}', '1', '1'); ";

							$somaZero = abs(($row['Nivel'] * 0.65) - ($row['somaAtributos']));
							if($somaZero > 0.5){
								$megaQuery .= "INSERT OR IGNORE INTO jogadorpendente VALUES ('{$row['idJogador']}'); ";
							}
						}

					}

					//buscar estadio e adicionar na query
					$stmt = $estadio->exportacao(null, $timePortal);

					while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
						$nomeEstadio = str_replace("'", "''", $row['Nome']);
						$megaQuery .= "INSERT or IGNORE INTO estadio VALUES ('{$row['ID']}', '{$nomeEstadio}', '{$row['Capacidade']}', '{$row['Clima']}', '{$row['Altitude']}', '{$row['Caldeirao']}'); ";

					}

					//buscar climas e adicionar na query
					$stmt = $novoClima->exportacao(null, $timePortal);

					while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
						$nomeClima = str_replace("'", "''", $row['nomeClima']);
						$megaQuery .= "INSERT or IGNORE INTO clima VALUES ('{$row['idClima']}', '{$nomeClima}', '{$row['TempVerao']}', '{$row['EstiloVerao']}', '{$row['TempOutono']}', '{$row['EstiloOutono']}', '{$row['TempInverno']}', '{$row['EstiloInverno']}', '{$row['TempPrimavera']}', '{$row['EstiloPrimavera']}', '{$row['Hemisferio']}'); ";

					}

					//buscar clubes e adicionar na query
					$stmt = $time->exportacao(null, $timePortal);

					while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){

						//tratar uniforme e simbolo
						$escudoTratado = "../../images/escudos/" . $row['Escudo'];
						$uni1Tratado = "../../images/uniformes/" . $row['Uniforme1'];
						$uni2Tratado = "../../images/uniformes/" . $row['Uniforme2'];

						$nomeExportado = str_replace("'", "''", $row['Nome']);
						$nomeExportado = html_entity_decode($nomeExportado);

						$megaQuery .= "INSERT OR REPLACE INTO clube VALUES ('{$row['ID']}', '{$nomeExportado}', '{$row['TresLetras']}', '{$row['Estadio']}', '{$escudoTratado}', '{$row['Uni1Cor1']}', '{$row['Uni1Cor2']}', '{$row['Uni1Cor3']}', '{$uni1Tratado}', '{$row['Uni2Cor1']}', '{$row['Uni2Cor2']}', '{$row['Uni2Cor3']}', '{$uni2Tratado}', '{$row['MaxTorcedores']}', '{$row['Fidelidade']}'); ";

						$elenco = array();
						$newStmt = $time->getElenco($row['ID']);
						$elenco[] = $row['ID'];
						while($newRow = $newStmt->fetch(PDO::FETCH_ASSOC)){
							$elenco[] = $newRow['ID'];
						}
						$total_jogadores = $time->getSizeElenco($row['ID']);
						while ($total_jogadores < 23){
							$elenco[] = '0';
							$total_jogadores++;
						}
						$tecStmt = $time->getTecnico($row['ID']);
						while($tecRow  = $tecStmt->fetch(PDO::FETCH_ASSOC)){
							$elenco[] = $tecRow['tecnico'];
						}

						$megaQuery .= "INSERT OR REPLACE INTO elenco VALUES ('{$elenco[0]}', '{$elenco[1]}', '{$elenco[2]}', '{$elenco[3]}', '{$elenco[4]}', '{$elenco[5]}', '{$elenco[6]}', '{$elenco[7]}', '{$elenco[8]}', '{$elenco[9]}', '{$elenco[10]}', '{$elenco[11]}', '{$elenco[12]}', '{$elenco[13]}', '{$elenco[14]}', '{$elenco[15]}', '{$elenco[16]}', '{$elenco[17]}', '{$elenco[18]}', '{$elenco[19]}', '{$elenco[20]}', '{$elenco[21]}', '{$elenco[22]}', '{$elenco[23]}', '{$elenco[24]}'); ";

						$escalacao = array();
						$escalacao[] = $row['ID'];
						$escStmt = $time->getEscalacao($row['ID']);
						while($escRow = $escStmt->fetch(PDO::FETCH_ASSOC)){
							$escalacao[] = $escRow['posicaoBase'];
							$escalacao[] = $escRow['jogador'];
						}
						$capStmt = $time->getCapitao($row['ID']);
						while($capRow = $capStmt->fetch(PDO::FETCH_ASSOC)){
							$escalacao[] = $capRow['jogador'];
						}
						$penStmt = $time->getPenaltis($row['ID']);
						while($penRow = $penStmt->fetch(PDO::FETCH_ASSOC)){
							$escalacao[] = $penRow['jogador'];
						}

						$megaQuery .= "INSERT OR REPLACE INTO escalacao VALUES ('{$escalacao[0]}', '{$escalacao[1]}', '{$escalacao[2]}', '{$escalacao[3]}', '{$escalacao[4]}', '{$escalacao[5]}', '{$escalacao[6]}', '{$escalacao[7]}', '{$escalacao[8]}', '{$escalacao[9]}', '{$escalacao[10]}', '{$escalacao[11]}', '{$escalacao[12]}', '{$escalacao[13]}', '{$escalacao[14]}', '{$escalacao[15]}', '{$escalacao[16]}', '{$escalacao[17]}', '{$escalacao[18]}', '{$escalacao[19]}', '{$escalacao[20]}', '{$escalacao[21]}', '{$escalacao[22]}', '{$escalacao[23]}', '{$escalacao[24]}', '{$escalacao[25]}', '{$escalacao[26]}'); ";

					}
					//testes
					// echo '<pre>' , var_dump($megaQuery) , '</pre>';
					// die();

				$megaQuery .= "COMMIT; ";
				try {
					$lite_database->directRun($megaQuery);
					file_put_contents($logFile, "SQLITE: Sucesso na exportação\n", FILE_APPEND);
				} catch (Exception $e) {
					file_put_contents($logFile, "SQLITE ERRO: " . $e->getMessage() . "\n", FILE_APPEND);
				}

			// fim do codigo para copiar time para o banco de dados
			
			if($competicao->alterarTimePortal($idCompeticao,$codigoTime, $paisTime, $timePortal )){
				$is_success = true;
				$error_msg = "";
				file_put_contents($logFile, "MYSQL: Sucesso ao vincular time portal\n", FILE_APPEND);
			} else {
				$is_success = false;
				$error_msg = "Falha ao alterar time com base no portal";
				file_put_contents($logFile, "MYSQL ERRO: Falha ao vincular time portal\n", FILE_APPEND);
			}
			break;
		case 2:
		
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
