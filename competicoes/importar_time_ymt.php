<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
ini_set( 'display_errors', true );
error_reporting( E_ALL );

if(!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true){
    die(json_encode(['success' => false, 'error' => 'Acesso negado.']));
}

if($_SESSION['emTestes'] ?? false){
    die(json_encode(['success' => false, 'error' => 'Usuários em período de testes não podem importar arquivos YMT.']));
}
	
$pais_time = $_POST['pais_time'];
$idCompeticao = $_POST['id_competicao'];
$codigo_time = $_POST['codigo_time'];

$codigo_time = -1 * $codigo_time;

include($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include($_SERVER['DOCUMENT_ROOT']."/config/sqliteDatabase.php");
include($_SERVER['DOCUMENT_ROOT']."/objetos/jogador.php");
include($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
include($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");
include($_SERVER['DOCUMENT_ROOT']."/objetos/estadio.php");
include($_SERVER['DOCUMENT_ROOT']."/objetos/clima.php");
include($_SERVER['DOCUMENT_ROOT']."/objetos/tecnico.php");
include($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
include($_SERVER['DOCUMENT_ROOT']."/objetos/competicao_clube.php");

//conexao com MySQL
$database = new Database();
$db = $database->getConnection();

//conexao com SQLite
$lite_database = new SQLiteDatabase();
$lite_database->fileName = $_SERVER['DOCUMENT_ROOT']."/competicoes/databases/".$idCompeticao."-database.db3";
$ldb = $lite_database->getConnection();

$pais = new Pais($db);
$usuario = new Usuario($db);
$competicao = new Competicao_clube($db);

$compInfo = $competicao->readInfo($idCompeticao);
if($compInfo && isset($compInfo['tipo']) && intval($compInfo['tipo']) === 1){
    die(json_encode(['success' => false, 'error' => 'Competições nacionais não permitem a importação de arquivos .ymt. Utilize os clubes cadastrados no país da competição.']));
}

$jogador = new Jogador($ldb);
$time = new Time($ldb);
$estadio = new Estadio($ldb);
$clima = new Clima($ldb);
$tecnico = new Tecnico($ldb);

$correct_extension = 'ymt';
$max_file_size = 10485760; // 10MB

$upload_success = null;
$upload_error = '';
$is_success = false;

if (!empty($_FILES['files'])) {

	$filesToUpload = array();
	$fileExt = [];
	$forbiddenFile = [];
	$fileSizeCheck = [];

	$fileName = (string) $_FILES['files']['name'];
	$fileExt = substr($fileName,-3);
	$countOfDots = (int)substr_count($fileName,".");

	if($countOfDots>01){
		$forbiddenFile = 1;
	} else {
		$forbiddenFile = 0;
	}
	
		$filePath = $_FILES['files']['tmp_name'];
		$forbidden = $forbiddenFile;
		$importExt = $fileExt;
		$importSize = $_FILES['files']['size'];
		$originalName = $_FILES['files']['name'];
		$error_msg = '';

		if($filePath != "" && $forbidden == 0 && $importExt == $correct_extension && $importSize <= $max_file_size){

			if(simplexml_load_string(file_get_contents($filePath)) == false){
				$content = file_get_contents($filePath);
				$xml = simplexml_load_string(function_exists('mb_convert_encoding') ? mb_convert_encoding($content, 'UTF-8', 'ISO-8859-1') : utf8_encode($content));

			} else {
				$xml = simplexml_load_string(file_get_contents($filePath));
			}

		//  definir informações do time
		if(!function_exists('changeName')){
			function changeName($node, $name) {

			   $stringname = "<".$name."></".$name.">";
			   $newnode = new SimpleXMLElement($stringname);
		 
			   $newnode = dom_import_simplexml($newnode);

			   //$node = $newnode->ownerDocument->importNode($node, true);

			   foreach ($node->childNodes as $child){
				   $child = $newnode->ownerDocument->importNode($child, true);
				   $newnode->appendChild($child);
			   }

			   return $newnode;
		   }
		}	

			//clima
			$clima->id = $codigo_time;
			$clima->nome = (string)$xml->clima->Nome;
			$clima->tempVerao = (string)$xml->clima->TempVerao;
			$clima->estiloVerao = (string)$xml->clima->EstiloVerao;
			$clima->tempOutono = (string)$xml->clima->TempOutono;
			$clima->estiloOutono = (string)$xml->clima->EstiloOutono;
			$clima->tempInverno = (string)$xml->clima->TempInverno;
			$clima->estiloInverno = (string)$xml->clima->EstiloInverno;
			$clima->tempPrimavera = (string)$xml->clima->TempPrimavera;
			$clima->estiloPrimavera = (string)$xml->clima->EstiloPrimavera;
			$clima->hemisferio = (string)$xml->clima->Hemisferio;

			$clima->createSqlite();
			$codigo_clima = $clima->id;
		
			//criar e vincular estadio
			$estadio->id = $codigo_time;
			$estadio->nome = (string)$xml->estadio->Nome;
			$estadio->capacidade = (int)$xml->estadio->Capacidade;
			$estadio->altitude = (string)$xml->estadio->Altitude;
			$estadio->caldeirao = (string)$xml->estadio->Caldeirao;
			$estadio->clima = $codigo_clima;       
			
			$estadio->createSqlite();
			$codigo_estadio = $codigo_time;
			
			$time->id = $codigo_time;
			$time->nome = (string)$xml->clube->Nome;
			$time->sigla = (string)$xml->clube->TresLetras;
			$time->uniforme1cor1 = (string)$xml->clube->Uni1Cor1;
			$time->uniforme1cor2 = (string)$xml->clube->Uni1Cor2;
			$time->uniforme1cor3 = (string)$xml->clube->Uni1Cor3;
			$time->uniforme2cor1 = (string)$xml->clube->Uni2Cor1;
			$time->uniforme2cor2 = (string)$xml->clube->Uni2Cor2;
			$time->uniforme2cor3 = (string)$xml->clube->Uni2Cor3;

			$upload_dir = "/images/escudos/";
			$formato_arquivo = (string)$xml->formatoEscudoBase64[0];
			$conferencia_arquivo = (string)$xml->escudoBase64[0];

			if($formato_arquivo !== "null" && strlen($conferencia_arquivo) > 0){
				$escudo_nome = "externo-" . $idCompeticao . "-" . abs($codigo_time) . "." . $formato_arquivo;
				$output_file = $_SERVER['DOCUMENT_ROOT'] . $upload_dir . $escudo_nome;
				$preEscudo = (string)$xml->escudoBase64[0];
				$preEscudoDecoded = base64_decode($preEscudo);
				$escudo_file = fopen($output_file, "wb");
				fwrite($escudo_file, $preEscudoDecoded);
				fclose($escudo_file);
				$time->escudo = $escudo_nome;
			} else {
				$time->escudo = $time->escudoPadrao();
			}
			
			$upload_dir = "/competicoes/temp_uniformes/";
			$formato_arquivo = (string)$xml->formatoUniforme1Base64[0];
			if($formato_arquivo !== "null"){
				$output_file = $_SERVER['DOCUMENT_ROOT'] .$upload_dir .$_SESSION['user_id'] ."-" . $time->nome . "-1." . $formato_arquivo;
				$preUniforme1 = (string)$xml->uniforme1Base64[0];
				$preUniforme1Decoded = base64_decode($preUniforme1);
				$uniforme1_file = fopen($output_file, "wb");
				fwrite($uniforme1_file, $preUniforme1Decoded);
				fclose($uniforme1_file);
				$time->uniforme1 = $upload_dir . $_SESSION['user_id']. "-". $time->nome. "-1." . $formato_arquivo;
			} else {
				$time->uniforme1 = $time->uniforme1Padrao();
			}

			$upload_dir = "/competicoes/temp_uniformes/";
			$formato_arquivo = (string)$xml->formatoUniforme2Base64[0];
			if($formato_arquivo !== "null"){
			
			$output_file = $_SERVER['DOCUMENT_ROOT'] .$upload_dir .$_SESSION['user_id'] ."-" . $time->nome . "-2." . $formato_arquivo;
			$preUniforme2 = (string)$xml->uniforme2Base64[0];
			$preUniforme2Decoded = base64_decode($preUniforme2);
			$uniforme2_file = fopen($output_file, "wb");
			fwrite($uniforme2_file, $preUniforme2Decoded);
			fclose($uniforme2_file);
			$time->uniforme2 = $upload_dir . $_SESSION['user_id']. "-". $time->nome. "-2." . $formato_arquivo;
			} else {
				$time->uniforme2 = $time->uniforme2Padrao();
			}

			$time->id = $codigo_time;
			$time->maxTorcedores = (string)$xml->clube->MaxTorcedores;
			$time->fidelidade = (string)$xml->clube->Fidelidade;
			$time->estadio = $codigo_estadio;

		 if($time->createSqlite()){
			 $is_success = true;
		 } else {
			 $is_success = false;
			 $error_msg .= 'Houve erros durante a inserção do time, possivelmente duplicado.';
			 die(json_encode([ 'success'=> $is_success, 'error'=> $error_msg, 'errors'=> $error_msg ]));

		 }

		//importar tecnico
		$tecnico->id = $codigo_time;
		$tecnico->nome = (string)$xml->tecnico->Nome;
		$tecnico->nascimento = (int)$xml->tecnico->Idade;
		$tecnico->nivel = (int)$xml->tecnico->Nivel;
		$tecnico->mentalidade = (int)$xml->tecnico->Mentalidade;
		$tecnico->estilo = (int)$xml->tecnico->Estilo;

		if($tecnico->createSqlite()){
		   $codigo_tecnico = $codigo_time;
		} else {
			$error_msg .= 'Houve erros durante a inserção do tecnico. ';
		}

		//importar jogadores

		$total_de_jogadores = $xml->elenco->Jogador->int->count();
		$count_goleiro = 0;
		$array_jogadores = array();

		for($j = 0;$j<$total_de_jogadores;$j++){

		 //importar e vincular jogadores
		 $dom_info = dom_import_simplexml($xml->jogadores->jogador[$j]);
		 $dom_nacionalidade = dom_import_simplexml($xml->nacionalidades->string[$j]);
		 $dom_nacionalidade = changeName($dom_nacionalidade, 'nacionalidade');
		 $dom_posicoes = dom_import_simplexml($xml->posicoesJogador->posicoes[$j]);
		 $dom_posicoes = changeName($dom_posicoes, 'posicoesJogador');

		//verificacao se é goleiro ou não
		if($xml->posicoesJogador->posicoes[$j]->G == 'true'){
		$verificacao_goleiro = true;
		} else {
		$verificacao_goleiro = false;
		}

		 if($verificacao_goleiro){
			$dom_atributos = dom_import_simplexml($xml->atributosGoleiro->atributosGoleiro[$count_goleiro]);
			$count_goleiro++;
		 } else {
			$dom_atributos = dom_import_simplexml($xml->atributosJogador->atributosJogador[$j-$count_goleiro]);
		 }
		 
		 //criacao de um xml de jogador
		 $xmlJogador = new SimpleXMLElement("<jogadorExportacao></jogadorExportacao>");

		 //adição das crianças
		 $dom_jogador = dom_import_simplexml($xmlJogador);

		 // Import the node, and all its children, to the document
			$dom_info = $dom_jogador->ownerDocument->importNode($dom_info, true);
			$dom_jogador->appendChild($dom_info);
			$dom_nacionalidade = $dom_jogador->ownerDocument->importNode($dom_nacionalidade, true);
			$dom_jogador->appendChild($dom_nacionalidade);
			$dom_posicoes = $dom_jogador->ownerDocument->importNode($dom_posicoes, true);
			$dom_jogador->appendChild($dom_posicoes);
			$dom_atributos = $dom_jogador->ownerDocument->importNode($dom_atributos, true);
			$dom_jogador->appendChild($dom_atributos);


		 $novoXml = simplexml_import_dom($dom_jogador);

		 $array_jogadores[] = $novoXml;
		}

		$capitaoId = $xml->escalacao->Capitao;

		$penaltisId = array();
		foreach($xml->escalacao->Penalti->int as $cobrador){
			$penaltisId[] = $cobrador;
		}
		$titularesPos = array();
		foreach($xml->escalacao->Pos->string as $posicaoTitular){
			$titularesPos[] = $posicaoTitular;
		}

		$titularesId = array();
		foreach($xml->escalacao->Jogador->int as $idTitular){
			$titularesId[] = $idTitular;
		}
		
		$capitaoNewId = 0;
		$titularesNewArray = array();
		$penaltisNewArray = array();
		
		 foreach($array_jogadores as $key => $xmlIterator){

			 $xml = $xmlIterator;
			 
			 // inicia tratamento jogador

			//  definir informações do jogador
			$jogador->id = $codigo_time * 1000 - $key;
			$jogador->nomeJogador = (string)$xml->jogador->Nome;
			$jogador->nascimento = (int)$xml->jogador->Idade;
			$jogador->nivel = (int)$xml->jogador->Nivel;
			$jogador->mentalidade = (int)$xml->jogador->Mentalidade;
			$jogador->cobradorFalta = (int)$xml->jogador->CobradorFalta;
			$jogador->condicao = (string)$xml->jogador->apto;

			//determinacao goleiro ou linha
			if($xml->posicoesJogador->G == 'true'){
				$isGoleiro = true;
			} else {
				$isGoleiro = false;
			}

			//determinacao dos atributos
			if($isGoleiro){
				$jogador->marcacao = (float)0.0;
				$jogador->desarme = (float)0.0;
				$jogador->visaoJogo = (float)0.0;
				$jogador->movimentacao = (float)0.0;
				$jogador->cruzamentos = (float)0.0;
				$jogador->cabeceamento = (float)0.0;
				$jogador->tecnica = (float)0.0;
				$jogador->controleBola = (float)0.0;
				$jogador->finalizacao = (float)0.0;
				$jogador->faroGol = (float)0.0;
				$jogador->velocidade = (float)0.0;
				$jogador->forca = (float)0.0;
				$jogador->reflexos = (float)$xml->atributosGoleiro->Reflexos;
				$jogador->seguranca = (float)$xml->atributosGoleiro->Seguranca;
				$jogador->saidas = (float)$xml->atributosGoleiro->Saidas;
				$jogador->jogoAereo = (float)$xml->atributosGoleiro->JogoAereo;
				$jogador->lancamentos = (float)$xml->atributosGoleiro->Lancamentos;
				$jogador->defesaPenaltis = (float)$xml->atributosGoleiro->DefesaPenaltis;
				
			} else {
				$jogador->marcacao = (float)$xml->atributosJogador->Marcacao;
				$jogador->desarme = (float)$xml->atributosJogador->Desarme;
				$jogador->visaoJogo = (float)$xml->atributosJogador->VisaoJogo;
				$jogador->movimentacao = (float)$xml->atributosJogador->Movimentacao;
				$jogador->cruzamentos = (float)$xml->atributosJogador->Cruzamentos;
				$jogador->cabeceamento = (float)$xml->atributosJogador->Cabeceamento;
				$jogador->tecnica = (float)$xml->atributosJogador->Tecnica;
				$jogador->controleBola = (float)$xml->atributosJogador->ControleBola;
				$jogador->finalizacao = (float)$xml->atributosJogador->Finalizacao;
				$jogador->faroGol = (float)$xml->atributosJogador->FaroGol;
				$jogador->velocidade = (float)$xml->atributosJogador->Velocidade;
				$jogador->forca = (float)$xml->atributosJogador->Forca;
				$jogador->reflexos = (float)0.0;
				$jogador->seguranca = (float)0.0;
				$jogador->saidas = (float)0.0;
				$jogador->jogoAereo = (float)0.0;
				$jogador->lancamentos = (float)0.0;
				$jogador->defesaPenaltis = (float)0.0;

			}

			$jogador->pais = $xml->nacionalidade[0];

			//stringposicoes
			$stringPos = '';
			$counter = 0;
			foreach($xml->posicoesJogador->children() as $novaPosicao){
				if($counter > 0){
					$stringPos .= ($novaPosicao == 'true' ? '1': '0');
				}
				$counter++;
			} 

			$jogador->stringPosicoes = $stringPos;
	   
			
				
		 if($jogador->createSqlite()){
			 
			$codigo_jogador = $jogador->id;
		
		 } else {
			 $error_msg .= 'Não foi possível inserir o jogador';
		 }
			 
			 // termina tratamento jogador

			 //verificar se é capitao ou penaltis (+ posicao base)
			$idVerificacao = $xml->jogador->ID;
			if(strcmp($idVerificacao,$capitaoId) == 0){
				$isCapitao = 1;
			} else {
				$isCapitao = 0;
			}
			
			if($isCapitao == 1){
				$capitaoNewId = $jogador->id;
			}

			$isPenalti = 0;
			foreach($penaltisId as $numero => $cobrador){
				if(strcmp($idVerificacao,$cobrador) == 0){
					$isPenalti = $numero+1;
				}
			}
			
			if($isPenalti > 0){
				$penaltisNewArray[$isPenalti] = $jogador->id;
			}

			$titularidade = 0;
			$posicaoBase = '';
			foreach($titularesId as $numero => $titular){
				if(strcmp($idVerificacao, $titular) == 0){
					$titularidade = 1;
					$posicaoBase = $titularesPos[$numero];
					
					$titularesNewArray += [ $jogador->id => $posicaoBase ];
				}
			}
			
			
		 

		 }
		 
		 // ajustar elencos e escalacao
		 
		 $time->inserirElencosSqlite($codigo_time, $array_jogadores);
		 $time->inserirEscalacaoSqlite($codigo_time, $capitaoNewId, $penaltisNewArray, $titularesNewArray);
		 

			if($xml === false){
				foreach(libxml_get_errors() as $error) {
					echo "\t", $error->message;
				}
			}
		} else {
			if($filePath == ""){
				$error_msg .= "Nome ".$filePath." inválido. ";
			}
			if($forbidden == 1){
				$error_msg .= "Nome com muitos pontos. ";
			}
			if($importExt != $correct_extension){
				$error_msg .= "Extensão ".$importExt." incorreta. ";
			}
			if($importSize > $max_file_size){
				$error_msg .= "Arquivo muito grande. ";
			}

		}
		
if($is_success){
	$competicao->gravarImportacao($idCompeticao, abs($codigo_time), $pais_time);
	
	$stSlot = $db->prepare("SELECT slot FROM competicao_times WHERE id_competicao = :idComp AND codigo_time = :cod LIMIT 1");
	$codTimeAbs = abs($codigo_time);
	$stSlot->bindParam(':idComp', $idCompeticao);
	$stSlot->bindParam(':cod', $codTimeAbs);
	$stSlot->execute();
	$rSlot = $stSlot->fetch(PDO::FETCH_ASSOC);
	if($rSlot && !empty($rSlot['slot'])){
		$competicao->definirSlotTime($idCompeticao, $codTimeAbs, $rSlot['slot']);
	} else {
		$competicao->atualizarJogosPorSlot($idCompeticao, $codTimeAbs, $codigo_time);
	}
}

die(json_encode([ 'success'=> $is_success, 'error'=> $error_msg, 'errors'=> $error_msg ]));

 }

?>
