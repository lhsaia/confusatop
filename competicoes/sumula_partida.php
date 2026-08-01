<?php

ini_set( 'display_errors', true );
error_reporting( E_ALL );
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

$idMatch = $_GET['id'];

// Redirecionamento para a súmula em imagem (Executado antes de enviar qualquer cabeçalho/HTML)
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/competicao_clube.php");

$database = new Database();
$db = $database->getConnection();
$competicao = new Competicao_clube($db);

$matchInfo = $competicao->getMatchInfo($idMatch);
if($matchInfo){
	$idCompeticao = $matchInfo['competicao'];
	$competitionInfo = $competicao->readInfo($idCompeticao);
	if($competitionInfo){
		$nomeComposto = $competitionInfo['ano'] . " - " . $competitionInfo['nome'];
		
		// Encontrar o arquivo PNG dinamicamente em qualquer rodada usando glob
		$baseDir = $_SERVER['DOCUMENT_ROOT'] . "/competicoes/hexacolor/Partidas/" . $nomeComposto;
		// Buscamos em qualquer subdiretório (*º Rodada ou similar) pelo arquivo de imagem correto
		$files = glob($baseDir . "/*/" . $matchInfo['path'] . ".png");
		if(empty($files)){
			// Busca de segurança em todo o diretório de Partidas caso o nome da competição tenha divergência sutil
			$files = glob($_SERVER['DOCUMENT_ROOT'] . "/competicoes/hexacolor/Partidas/*/*/" . $matchInfo['path'] . ".png");
		}
		
		if(!empty($files)){
			$filePath = $files[0];
			header("Content-Type: image/png");
			header("Content-Length: " . filesize($filePath));
			readfile($filePath);
			exit;
		} else {
			die("Erro: Arquivo de imagem da súmula nao encontrado no servidor.");
		}
	}
}

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Súmula";
$css_filename = "newindex";
$aux_css = "sumula_partida";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){
	
	include_once($_SERVER['DOCUMENT_ROOT']."/config/sqliteDatabase.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
	//include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/arbitros.php");
	//include_once($_SERVER['DOCUMENT_ROOT']."/objetos/estadio.php");
	//include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
	//include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogador.php");
	
	$usuario = new Usuario($db);
	//$time = new Time($cdb);
	$arbitro = new TrioArbitragem($db);
	//$estadio = new Estadio($cdb);
	//$jogador = new Jogador($db);
	//$pais = new Pais($db);
	
	$compDatabase = new SQLiteDatabase();
	$compDatabase->fileName = $_SERVER['DOCUMENT_ROOT']."/competicoes/databases/".$idCompeticao."-database.db3";
	$cdb = $compDatabase->getConnection();
	
	$lite_competicao = new Competicao_clube($cdb);
	
	$path_hyl = "/hexacolor/Partidas/" . $nomeComposto . "/1º Rodada/" . $matchInfo['path'] . ".hyl";
	$path_hyj = "/hexacolor/Partidas/" . $nomeComposto . "/1º Rodada/" . $matchInfo['path'] . ".hyj";
	
	// read path
	$xml_hyj = json_decode(file_get_contents( str_replace('\\', '/', __DIR__ . $path_hyj)));
	$xml_hyl = json_decode(file_get_contents( str_replace('\\', '/', __DIR__ . $path_hyl)));

	// get data from hyl
	$golsTimeA = (int)$xml_hyl->placarTime1;
	$golsTimeB = (int)$xml_hyl->placarTime2;
	
	$escudoTimeA = (string) $xml_hyl->escudoTime1;
	$escudoTimeB = (string) $xml_hyl->escudoTime2;
	
	$nomeTimeA = (string) $xml_hyl->time1;
	$nomeTimeB = (string) $xml_hyl->time2;
	
	$eventosJogo = $xml_hyl->eventos;
	
	function hex($rgb_color){
		return "#" . str_pad(dechex(substr($rgb_color, 0, 3)),2,"0", STR_PAD_LEFT) . str_pad(dechex(substr($rgb_color, 3, 3)),2,"0", STR_PAD_LEFT) . str_pad(dechex(substr($rgb_color, 6, 3)),2,"0", STR_PAD_LEFT);
	}
	
	function hex_java($java_color){
		return sprintf("#%06X", (0xFFFFFF & $java_color));

	}
	
	function gols($var){
		if($var->tipoEvento == "gol"){
			return true;
		} else {
			return false;
		}
	}
	
	function vermelhos($var){
		if($var->tipoEvento == "vermelho"){
			return true;
		} else {
			return false;
		}
	}
	
	function golsContra($var){
		if($var->tipoEvento == "golContra"){
			return true;
		} else {
			return false;
		}
	}
	
	function substituicoes($var){
		if($var->tipoEvento == "substituicao"){
			return true;
		} else {
			return false;
		}
	}
	
	function lesoes($var){
		if($var->tipoEvento == "lesao"){
			return true;
		} else {
			return false;
		}
	}
	
	function penaltisDefendidos($var){
		if( strpos($var->narracao, "pênalti") !== false && strpos($var->narracao, "defende") !== false){
			return true;
		} else {
			return false;
		}
	}
	
	function penaltisPerdidos($var){
		if( strpos($var->narracao, "pênalti") !== false && (strpos($var->narracao, "pra fora") !== false || strpos($var->narracao, "na trave") !== false || strpos($var->narracao, "no travessão") !== false)){
			return true;
		} else {
			return false;
		}
	}

		
	
	$golsJogo = array_filter($eventosJogo, "gols");
	$vermelhosJogo = array_filter($eventosJogo, "vermelhos");
	$substituicoesJogo = array_filter($eventosJogo, "substituicoes");
	$lesoesJogo = array_filter($eventosJogo, "lesoes");
	$golsContraJogo = array_filter($eventosJogo, "golsContra");
	$penaltisDefendidos = array_filter($eventosJogo, "penaltisDefendidos");
	$penaltisPerdidos = array_filter($eventosJogo, "penaltisPerdidos");
	
	$kitTime1 = $xml_hyl->uniformeTime1;
	$kitTime2 = $xml_hyl->uniformeTime2;
	
	if($kitTime1 == 0){
		$kitTime1 = 1;
	}
	
	if($kitTime2 == 0){
		if($xml_hyl->trocarUniformeTime2){
			$kitTime2 = 2;
		} else {
			$kitTime2 = 1;
		}
	}
	
	// siglas times
	$siglaA = (string)$xml_hyl->tresLetrasTime1;
	$siglaB = (string)$xml_hyl->tresLetrasTime2;
	
	// get data from hyj
	
	$clima = (string)$xml_hyj->clima;
	$temperatura = (int)$xml_hyj->temperatura;
	$publico = (int)$xml_hyj->publico;
	$idArbitragem = (int)$xml_hyj->idArbitragem;
	$idEstadio = (int)$xml_hyj->idEstadio;
	$data = (string)$xml_hyj->data;
	
	switch($clima){
		case 'CeuLimpo':
			$climaCerto = "Céu Limpo";
			break;
		default:
			$climaCerto = $clima;
	}
	
	$tecnicoTimeA = (int)$xml_hyj->time1->idTreinador;
	$tecnicoTimeB = (int)$xml_hyj->time2->idTreinador;
	
	$idTimeA = (int)$xml_hyj->time1->idTime;
	$idTimeB = (int)$xml_hyj->time2->idTime;
	
	//stats time A
	$statsTimeA['chutes'] = (int)$xml_hyj->time1->chutes;
	$statsTimeA['chutesGol'] = (int)$xml_hyj->time1->chutesGol;
	$statsTimeA['escanteios'] = (int)$xml_hyj->time1->escanteios;
	$statsTimeA['faltas'] = (int)$xml_hyj->time1->faltas;
	$statsTimeA['penaltis'] = (int)$xml_hyj->time1->penaltis;
	$statsTimeA['impedimentos'] = (int)$xml_hyj->time1->impedimentos;
	$statsTimeA['amarelos'] = (int)$xml_hyj->time1->amarelos;
	$statsTimeA['vermelhos'] = (int)$xml_hyj->time1->vermelhos;
	$statsTimeA['posseBola'] = (int)$xml_hyj->time1->posseBola;
	$statsTimeA['placarPenaltis'] = (int)$xml_hyj->time1->placarPenaltis;
	$statsTimeA['placarProrrogacao'] = (int)$xml_hyj->time1->placarProrrogacao;
	
	//stats time B
	$statsTimeB['chutes'] = (int)$xml_hyj->time2->chutes;
	$statsTimeB['chutesGol'] = (int)$xml_hyj->time2->chutesGol;
	$statsTimeB['escanteios'] = (int)$xml_hyj->time2->escanteios;
	$statsTimeB['faltas'] = (int)$xml_hyj->time2->faltas;
	$statsTimeB['penaltis'] = (int)$xml_hyj->time2->penaltis;
	$statsTimeB['impedimentos'] = (int)$xml_hyj->time2->impedimentos;
	$statsTimeB['amarelos'] = (int)$xml_hyj->time2->amarelos;
	$statsTimeB['vermelhos'] = (int)$xml_hyj->time2->vermelhos;
	$statsTimeB['posseBola'] = (int)$xml_hyj->time2->posseBola;
	$statsTimeB['placarPenaltis'] = (int)$xml_hyj->time2->placarPenaltis;
	$statsTimeB['placarProrrogacao'] = (int)$xml_hyj->time2->placarProrrogacao;
	
	//jogadores
	$jogadoresTimeA = json_decode(json_encode($xml_hyj->time1->jogadores), true);
	$jogadoresTimeB = json_decode(json_encode($xml_hyj->time2->jogadores), true);

	
	// get additional data from DBs
		//cores dos times
		$coresTimeA = $lite_competicao->getTeamColors($kitTime1 , $idTimeA);
		$coresTimeB = $lite_competicao->getTeamColors($kitTime2 , $idTimeB);
		
		//uniformes dos times
		$uniformeTimeA = $lite_competicao->getTeamUniform($kitTime1 , $idTimeA);
		$uniformeTimeB = $lite_competicao->getTeamUniform($kitTime2 , $idTimeB);
		
		//nome, nivel e idade (na data do jogo!) jogadores
		$escalacaoTimeA = $lite_competicao->getTeamPlayers($idTimeA);
		$escalacaoTimeB = $lite_competicao->getTeamPlayers($idTimeB);
		
		//estadio
		$nomeEstadio = $lite_competicao->getNomeEstadio($idEstadio);
		
		//arbitro
		$trioArbitragem = $arbitro->getTrioArbitragem($idArbitragem);
		
			
		//cores da competicao
		$coresJogo = $lite_competicao->getColors();
		

		
		//tecnicos
		

	// show data
	
	//echo '<div class="bg"></div><div class="bg bg2"></div><div class="bg bg3"></div>';



	
	// definicao caixa texto
	$caixaTextoA = "";
	
	//gols
	$hasGol = true;
	
	foreach($golsJogo as $unicoGol){
		
		if($unicoGol->time == 1){
			if($hasGol){
				$caixaTextoA .= " --- Gols --- <br>";
			}
			$hasGol = false;
			$caixaTextoA .= $escalacaoTimeA[$unicoGol->idJogador]['nome'] . " - " . $unicoGol->minutos . "<br>";
		}
		
	}
	
	//vermelhos
	$hasVermelho = true;
	
	foreach($vermelhosJogo as $unicoVermelho){
		
		if($unicoVermelho->time == 2){
			if($hasVermelho){
				$caixaTextoA .= " --- Cartões Vermelhos --- <br>";
			}
			$hasVermelho = false;
			$caixaTextoA .= $escalacaoTimeA[$unicoVermelho->idJogador]['nome'] . " - " . $unicoVermelho->minutos . "<br>";
		}
		
	}
	
	//lesoes
	$hasLesao = true;
	
	foreach($lesoesJogo as $unicaLesao){
		
		if($unicaLesao->time == 1){
			if($hasLesao){
				$caixaTextoA .= " --- Lesões --- <br>";
			}
			$hasLesao = false;
			$caixaTextoA .= $escalacaoTimeA[$unicaLesao->idJogador]['nome'] . " - " . $unicaLesao->minutos . " (" .$unicaLesao->duracao ."dias)<br>";
		}
		
	}
	
	//time B
	$caixaTextoB = "";
	
	//gols
	$hasGol = true;
	foreach($golsJogo as $unicoGol){
		
		if($unicoGol->time == 2){
			if($hasGol){
				$caixaTextoB .= " --- Gols --- <br>";
			}
			$hasGol = false;
			$caixaTextoB .= $escalacaoTimeB[$unicoGol->idJogador]['nome'] . " - " . $unicoGol->minutos . "<br>";
		}
		
	}
	
		//vermelhos
	$hasVermelho = true;
	
	foreach($vermelhosJogo as $unicoVermelho){
		
		if($unicoVermelho->time == 1){
			if($hasVermelho){
				$caixaTextoB .= " --- Cartões Vermelhos --- <br>";
			}
			$hasVermelho = false;
			$caixaTextoB .= $escalacaoTimeB[$unicoVermelho->idJogador]['nome'] . " - " . $unicoVermelho->minutos . "<br>";
		}
		
	}
	
	//lesoes
	$hasLesao = true;
	
	foreach($lesoesJogo as $unicaLesao){
		
		if($unicaLesao->time == 2){
			if($hasLesao){
				$caixaTextoB .= " --- Lesões --- <br>";
			}
			$hasLesao = false;
			$caixaTextoB .= $escalacaoTimeB[$unicaLesao->idJogador]['nome'] . " - " . $unicaLesao->minutos . " (" .$unicaLesao->duracao ."dias)<br>";
		}
		
	}
	
	// //penaltis perdidos
	// $hasPP = true;
	
	// foreach($penaltisPerdidos as $unicoPenalti){
		
		// if($unicoPenalti->time == 2){
			// if($hasPP){
				// $caixaTextoB .= " --- Pênaltis perdidos --- <br>";
			// }
			// $hasPP = false;
			// $caixaTextoB .= $escalacaoTimeB[$unicoPenalti->idJogador]['nome'] . " - " . $unicoPenalti->minutos . "<br>";
		// }
		
	// }
	
	// echo "<pre>";
	// var_dump($nomeEstadio);
	// echo "</pre>";
	

?>

<script>

 $(document).ready(function($){
	 
	//$("#toolbar").html('<div id="inserir_jogo"><span class="material-symbols-outlined">add_circle</span><span>Jogo</span></div><div id="inserir_grupo"><span class="material-symbols-outlined">add_circle</span><span>Grupo</span></div>');

	// allow print sumula
	
});

</script>

<?php
$random_loader = rand(1,5);


echo "<div style='clear:both;'></div>";
echo "<div class='tbl_user_data'><div id='loading' hidden><img src='/images/loaders/loader_style{$random_loader}.gif'/></div></div>";
echo '<div id="errorbox"></div>';
echo '<div id="quadro_sumula">';
echo "<div id='faixa_superior'>
<div id='imagens_timeA'>
<img class='uniforme_sumula' src='".$uniformeTimeA['uniforme']."'/>
<img class='escudo_sumula' src='data:image/png;base64, ".$escudoTimeA."' />
</div>
<div id='score_timeA'>
<div id='barraPrincipal_timeA'>
<div id='nome_timeA' style='background-color: ".hex($coresTimeA['cor1'])."; color: ".hex($coresTimeA['cor2'])."'><span>".$nomeTimeA."</span></div>
<div id='gols_timeA' style='background-color: ".hex($coresTimeA['cor2'])."; color: ".hex($coresTimeA['cor1'])."'><span>".$golsTimeA."</span></div>
</div>
<div id='caixaTexto_timeA'>
<p>
".$caixaTextoA."</p></div>
</div>
<div id='x_central'><span>X</span></div>
<div id='score_timeB'>
<div id='barraPrincipal_timeB'>
<div id='gols_timeA' style='background-color: ".hex($coresTimeB['cor2'])."; color: ".hex($coresTimeB['cor1'])."'><span>".$golsTimeB."</span></div>
<div id='nome_timeA' style='background-color: ".hex($coresTimeB['cor1'])."; color: ".hex($coresTimeB['cor2'])."'><span>".$nomeTimeB."</span></div>
</div>
<div id='caixaTexto_timeB'>
<p>
".$caixaTextoB."</p>
</div>
</div>
<div id='imagens_timeB'>
<img class='uniforme_sumula' src='".$uniformeTimeB['uniforme']."'/>
<img class='escudo_sumula' src='data:image/png;base64, ".$escudoTimeB."' />
</div>
</div>";
echo "<div id='bloco_inferior'>
<div id='estatisticas_timeA'></div>
<div id='estatisticas_jogo'>
<table id='tabela_stats_jogo' style='background-color: ".hex_java($coresJogo['partidaCor1'])."; color:".hex_java($coresJogo['partidaCor2'])."; border-color:".hex_java($coresJogo['partidaCor3'])."; '><tbody>
<tr><td><span>Estádio: ".$nomeEstadio."</span></td></tr>
<tr><td><span>".$publico." pessoas</span></td></tr>
<tr><td><span>".str_replace("-","/",$data)." - " . $temperatura . "°C - " . $climaCerto  . "</span></td></tr>
<tr><td><span>Árbitro: ".$trioArbitragem['nomeArbitro']." </span></td></tr>
<tr><td><span>Auxiliar 1: ".$trioArbitragem['nomeAuxiliarUm']." </span></td></tr>
<tr><td><span>Auxiliar 2: ".$trioArbitragem['nomeAuxiliarDois']." </span></td></tr>
</tbody></table>
<div id='empty_space'></div>
<table id='tabela_stats_times' style='background-color: ".hex_java($coresJogo['partidaCor1'])."; color:".hex_java($coresJogo['partidaCor2'])."; border-color:".hex_java($coresJogo['partidaCor3'])."; '><tbody>
<tr><td class='empty_cell'></td><td class='bordered_cell'><span>Estatísticas</span></td><td class='empty_cell'></td></tr>
<tr><td class='bordered_cell' style='background-color: ".hex($coresTimeA['cor1'])."; color: ".hex($coresTimeA['cor2'])."; border-color: ".hex($coresTimeA['cor3']).";'><span>".$siglaA."</span></td><td class='empty_cell'></td><td class='bordered_cell' style='background-color: ".hex($coresTimeB['cor1'])."; color: ".hex($coresTimeB['cor2'])."; border-color: ".hex($coresTimeB['cor3']).";'><span>".$siglaB."</span></td></tr>
<tr><td class='bordered_cell'><span>".$statsTimeA['chutes']."</span></td><td class='bordered_cell'><span>Chutes</span></td><td class='bordered_cell'><span>".$statsTimeB['chutes']."</span></td></tr>
<tr><td class='bordered_cell'><span>".$statsTimeA['chutesGol']."</span></td><td class='bordered_cell'><span>Chutes a Gol</span></td><td class='bordered_cell'><span>".$statsTimeB['chutesGol']."</span></td></tr>
<tr><td class='bordered_cell'><span>".$statsTimeA['faltas']."</span></td><td class='bordered_cell'><span>Faltas</span></td><td class='bordered_cell'><span>".$statsTimeB['faltas']."</span></td></tr>
<tr><td class='bordered_cell'><span>".$statsTimeA['penaltis']."</span></td><td class='bordered_cell'><span>Pênaltis</span></td><td class='bordered_cell'><span>".$statsTimeB['penaltis']."</span></td></tr>
<tr><td class='bordered_cell'><span>".$statsTimeA['escanteios']."</span></td><td class='bordered_cell'><span>Escanteios</span></td><td class='bordered_cell'><span>".$statsTimeB['escanteios']."</span></td></tr>
<tr><td class='bordered_cell'><span>".$statsTimeA['impedimentos']."</span></td><td class='bordered_cell'><span>Impedimentos</span></td><td class='bordered_cell'><span>".$statsTimeB['impedimentos']."</span></td></tr>
<tr><td class='bordered_cell'><span>".$statsTimeA['amarelos']."</span></td><td class='bordered_cell'><span>Cartões Amarelos</span></td><td class='bordered_cell'><span>".$statsTimeB['amarelos']."</span></td></tr>
<tr><td class='bordered_cell'><span>".$statsTimeA['vermelhos']."</span></td><td class='bordered_cell'><span>Cartões Vermelhos</span></td><td class='bordered_cell'><span>".$statsTimeB['vermelhos']."</span></td></tr>
<tr><td class='bordered_cell'><span>".$statsTimeA['posseBola']."</span></td><td class='bordered_cell'><span>Posse de Bola</span></td><td class='bordered_cell'><span>".$statsTimeB['posseBola']."</span></td></tr>
</tbody></table>
<div id='empty_space'></div>
<div id='div_disclaimer' style='background-color: ".hex_java($coresJogo['partidaCor1'])."; color:".hex_java($coresJogo['partidaCor2'])."; border-color:".hex_java($coresJogo['partidaCor3'])."; '><p class='disclaimer'>Faltas, pênaltis e escanteios se referem a <br> incidentes a favor da equipe, e não cometidos <br> por ela.</p></div>
</div>
<div id='estatisticas_timeB'></div>
</div>";
echo '</div>';




} else {
    echo "Usuário, por favor refaça o login.";
}

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>

