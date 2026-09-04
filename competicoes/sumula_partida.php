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
		
		// Sigilo Temporal: Se a partida ainda não tiver terminado no tempo real, bloquear a súmula
		$horarioJogo = strtotime($matchInfo['data']);
		$duracaoMinutos = (!empty($matchInfo['timeA_penaltis']) || !empty($matchInfo['timeB_penaltis'])) ? 150 : 120;
		$horarioTermino = $horarioJogo + ($duracaoMinutos * 60);

		// Se já terminou no tempo real e possui arquivo gerado, envia a imagem
		if (time() >= $horarioTermino && !empty($matchInfo['path'])) {
			$baseDir = $_SERVER['DOCUMENT_ROOT'] . "/competicoes/hexacolor/Partidas/" . $nomeComposto;
			$files = glob($baseDir . "/*/" . $matchInfo['path'] . ".png");
			if(empty($files)){
				$files = glob($_SERVER['DOCUMENT_ROOT'] . "/competicoes/hexacolor/Partidas/*/*/" . $matchInfo['path'] . ".png");
			}
			
			if(!empty($files)){
				$filePath = $files[0];
				header("Content-Type: image/png");
				header("Content-Length: " . filesize($filePath));
				readfile($filePath);
				exit;
			}
		}

		// Se chegou aqui: ou o jogo não terminou no tempo real, ou a imagem ainda está sendo gerada
		include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

		$page_title = "Súmula da Partida - CONFUSA.top";
		$css_filename = "home_redesign";
		$aux_css = "home_redesign";
		$css_login = 'login';
		$css_versao = date('h:i:s');
		include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

		$timeA_nome = !empty($matchInfo['timeA_nome']) ? $matchInfo['timeA_nome'] : (!empty($matchInfo['timeA_id']) ? "Time #" . $matchInfo['timeA_id'] : "A definir");
		$timeB_nome = !empty($matchInfo['timeB_nome']) ? $matchInfo['timeB_nome'] : (!empty($matchInfo['timeB_id']) ? "Time #" . $matchInfo['timeB_id'] : "A definir");
		$dataFormatada = date('d/m/Y \à\s H:i', $horarioJogo);
		$horarioFimFormatado = date('H:i', $horarioTermino);
		$emAndamento = (time() >= $horarioJogo && time() < $horarioTermino);
		$jaEncerradoTempoReal = (time() >= $horarioTermino);
?>
<div class="bg"></div><div class="bg bg2"></div><div class="bg bg3"></div>

<main class="propostas-container" style="max-width: 720px; margin: 40px auto; padding: 0 15px; font-family: 'Montserrat', sans-serif;">
    <div class="propostas-card" style="background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border-radius: 18px; border: 1px solid rgba(0, 0, 0, 0.08); box-shadow: 0 10px 30px rgba(0, 0, 0, 0.06); padding: 40px 30px; text-align: center;">
        
        <div style="width: 76px; height: 76px; margin: 0 auto 22px; border-radius: 50%; background: linear-gradient(135deg, rgba(2, 132, 199, 0.15), rgba(56, 189, 248, 0.25)); display: flex; align-items: center; justify-content: center; color: #0284c7; box-shadow: 0 4px 15px rgba(2, 132, 199, 0.18);">
            <span class="material-symbols-outlined" style="font-size: 40px;">
                <?php echo $jaEncerradoTempoReal ? 'pending_actions' : ($emAndamento ? 'sports_soccer' : 'schedule'); ?>
            </span>
        </div>

        <h2 style="font-family: 'Outfit', sans-serif; font-size: 1.65rem; font-weight: 700; color: #0f172a; margin: 0 0 10px 0;">
            <?php 
                if ($jaEncerradoTempoReal) {
                    echo 'Processando Súmula Oficial';
                } elseif ($emAndamento) {
                    echo 'Partida em Andamento';
                } else {
                    echo 'Partida Agendada';
                }
            ?>
        </h2>

        <div style="display: inline-block; padding: 6px 18px; background: rgba(2, 132, 199, 0.1); border-radius: 20px; color: #0284c7; font-weight: 600; font-size: 0.85rem; margin-bottom: 24px;">
            <?php echo htmlspecialchars($nomeComposto); ?>
        </div>

        <div style="background: rgba(241, 245, 249, 0.75); border: 1px solid rgba(0, 0, 0, 0.06); border-radius: 14px; padding: 20px; margin-bottom: 24px;">
            <div style="font-family: 'Outfit', sans-serif; font-size: 1.3rem; font-weight: 600; color: #1e293b; display: flex; align-items: center; justify-content: center; gap: 15px; flex-wrap: wrap;">
                <span><?php echo htmlspecialchars($timeA_nome); ?></span>
                <span style="font-size: 0.85rem; font-weight: 700; color: #64748b; padding: 3px 10px; background: #ffffff; border-radius: 8px; border: 1px solid #cbd5e1; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">VS</span>
                <span><?php echo htmlspecialchars($timeB_nome); ?></span>
            </div>
            <div style="margin-top: 12px; font-size: 0.92rem; color: #475569; display: flex; align-items: center; justify-content: center; gap: 6px;">
                <span class="material-symbols-outlined" style="font-size: 18px; color: #0284c7;">event</span>
                <span>Data & Horário: <strong><?php echo $dataFormatada; ?></strong> <?php if(!$jaEncerradoTempoReal): ?>(término aprox. às <strong><?php echo $horarioFimFormatado; ?></strong>)<?php endif; ?></span>
            </div>
        </div>

        <p style="color: #475569; font-size: 1rem; line-height: 1.65; margin: 0 auto 30px; max-width: 520px;">
            <?php if($jaEncerradoTempoReal): ?>
                A partida já encerrou seu horário regulamentar. O arquivo visual da súmula está sendo gerado e estará disponível em instantes.
            <?php elseif($emAndamento): ?>
                A partida está acontecendo agora! O placar e a súmula oficial com todos os lances, gols e cartões serão liberados automaticamente após o término do jogo.
            <?php else: ?>
                A partida ainda está agendada. A súmula oficial será liberada automaticamente após o término do confronto.
            <?php endif; ?>
        </p>

        <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
            <a href="/competicoes/listajogos.php?id=<?php echo $idCompeticao; ?>" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 22px; background: #0284c7; color: #ffffff; text-decoration: none; border-radius: 10px; font-weight: 600; font-family: 'Outfit', sans-serif; font-size: 0.95rem; box-shadow: 0 3px 10px rgba(2, 132, 199, 0.25); transition: all 0.2s ease;">
                <span class="material-symbols-outlined" style="font-size: 20px;">arrow_back</span>
                <span>Voltar para a Lista de Jogos</span>
            </a>
            <a href="/competicoes/competitionstatus.php?id=<?php echo $idCompeticao; ?>" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 22px; background: rgba(0, 0, 0, 0.04); border: 1px solid rgba(0, 0, 0, 0.08); color: #475569; text-decoration: none; border-radius: 10px; font-weight: 600; font-family: 'Outfit', sans-serif; font-size: 0.95rem; transition: all 0.2s ease;">
                <span class="material-symbols-outlined" style="font-size: 20px;">trophy</span>
                <span>Ver Competição</span>
            </a>
        </div>
    </div>
</main>

<?php
		include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
		exit;
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
	
	if (!isset($competitionInfo) || empty($competitionInfo)) {
		$competitionInfo = $competicao->readInfo($idCompeticao);
	}
	if (!isset($nomeComposto) && !empty($competitionInfo)) {
		$nomeComposto = $competitionInfo['ano'] . " - " . $competitionInfo['nome'];
	}
	if (!isset($nomeComposto)) {
		$nomeComposto = "Partidas";
	}
	
	$path_hyl = "/hexacolor/Partidas/" . $nomeComposto . "/1º Rodada/" . ($matchInfo['path'] ?? '') . ".hyl";
	$path_hyj = "/hexacolor/Partidas/" . $nomeComposto . "/1º Rodada/" . ($matchInfo['path'] ?? '') . ".hyj";
	
	$file_hyj_path = str_replace('\\', '/', __DIR__ . $path_hyj);
	$file_hyl_path = str_replace('\\', '/', __DIR__ . $path_hyl);

	// read path
	$xml_hyj = file_exists($file_hyj_path) ? json_decode(file_get_contents($file_hyj_path)) : null;
	$xml_hyl = file_exists($file_hyl_path) ? json_decode(file_get_contents($file_hyl_path)) : null;

	// get data from hyl
	$golsTimeA = (int)($xml_hyl->placarTime1 ?? ($matchInfo['timeA_gols'] ?? 0));
	$golsTimeB = (int)($xml_hyl->placarTime2 ?? ($matchInfo['timeB_gols'] ?? 0));
	
	$escudoTimeA = (string) ($xml_hyl->escudoTime1 ?? '');
	$escudoTimeB = (string) ($xml_hyl->escudoTime2 ?? '');
	
	$nomeTimeA = (string) ($xml_hyl->time1 ?? ($matchInfo['timeA_nome'] ?? 'Time A'));
	$nomeTimeB = (string) ($xml_hyl->time2 ?? ($matchInfo['timeB_nome'] ?? 'Time B'));
	
	$eventosJogo = (isset($xml_hyl->eventos) && is_array($xml_hyl->eventos)) ? $xml_hyl->eventos : [];
	
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
	
	$kitTime1 = (int)($xml_hyl->uniformeTime1 ?? 1);
	$kitTime2 = (int)($xml_hyl->uniformeTime2 ?? 1);
	
	if($kitTime1 == 0){
		$kitTime1 = 1;
	}
	
	if($kitTime2 == 0){
		if(!empty($xml_hyl->trocarUniformeTime2)){
			$kitTime2 = 2;
		} else {
			$kitTime2 = 1;
		}
	}
	
	// siglas times
	$siglaA = (string)($xml_hyl->tresLetrasTime1 ?? substr($nomeTimeA, 0, 3));
	$siglaB = (string)($xml_hyl->tresLetrasTime2 ?? substr($nomeTimeB, 0, 3));
	
	// get data from hyj
	$clima = (string)($xml_hyj->clima ?? 'CeuLimpo');
	$temperatura = (int)($xml_hyj->temperatura ?? 20);
	$publico = (int)($xml_hyj->publico ?? 0);
	$idArbitragem = (int)($xml_hyj->idArbitragem ?? ($matchInfo['arbitro'] ?? 0));
	$idEstadio = (int)($xml_hyj->idEstadio ?? ($matchInfo['estadio'] ?? 0));
	$data = (string)($xml_hyj->data ?? ($matchInfo['data'] ?? ''));
	
	switch($clima){
		case 'CeuLimpo':
			$climaCerto = "Céu Limpo";
			break;
		default:
			$climaCerto = $clima;
	}
	
	$tecnicoTimeA = (int)($xml_hyj->time1->idTreinador ?? 0);
	$tecnicoTimeB = (int)($xml_hyj->time2->idTreinador ?? 0);
	
	$idTimeA = (int)($xml_hyj->time1->idTime ?? ($matchInfo['timeA_id'] ?? 0));
	$idTimeB = (int)($xml_hyj->time2->idTime ?? ($matchInfo['timeB_id'] ?? 0));
	
	//stats time A
	$statsTimeA['chutes'] = (int)($xml_hyj->time1->chutes ?? 0);
	$statsTimeA['chutesGol'] = (int)($xml_hyj->time1->chutesGol ?? 0);
	$statsTimeA['escanteios'] = (int)($xml_hyj->time1->escanteios ?? 0);
	$statsTimeA['faltas'] = (int)($xml_hyj->time1->faltas ?? 0);
	$statsTimeA['penaltis'] = (int)($xml_hyj->time1->penaltis ?? 0);
	$statsTimeA['impedimentos'] = (int)($xml_hyj->time1->impedimentos ?? 0);
	$statsTimeA['amarelos'] = (int)($xml_hyj->time1->amarelos ?? 0);
	$statsTimeA['vermelhos'] = (int)($xml_hyj->time1->vermelhos ?? 0);
	$statsTimeA['posseBola'] = (int)($xml_hyj->time1->posseBola ?? 50);
	$statsTimeA['placarPenaltis'] = (int)($xml_hyj->time1->placarPenaltis ?? 0);
	$statsTimeA['placarProrrogacao'] = (int)($xml_hyj->time1->placarProrrogacao ?? 0);
	
	//stats time B
	$statsTimeB['chutes'] = (int)($xml_hyj->time2->chutes ?? 0);
	$statsTimeB['chutesGol'] = (int)($xml_hyj->time2->chutesGol ?? 0);
	$statsTimeB['escanteios'] = (int)($xml_hyj->time2->escanteios ?? 0);
	$statsTimeB['faltas'] = (int)($xml_hyj->time2->faltas ?? 0);
	$statsTimeB['penaltis'] = (int)($xml_hyj->time2->penaltis ?? 0);
	$statsTimeB['impedimentos'] = (int)($xml_hyj->time2->impedimentos ?? 0);
	$statsTimeB['amarelos'] = (int)($xml_hyj->time2->amarelos ?? 0);
	$statsTimeB['vermelhos'] = (int)($xml_hyj->time2->vermelhos ?? 0);
	$statsTimeB['posseBola'] = (int)($xml_hyj->time2->posseBola ?? 50);
	$statsTimeB['placarPenaltis'] = (int)($xml_hyj->time2->placarPenaltis ?? 0);
	$statsTimeB['placarProrrogacao'] = (int)($xml_hyj->time2->placarProrrogacao ?? 0);
	
	//jogadores
	$jogadoresTimeA = isset($xml_hyj->time1->jogadores) ? json_decode(json_encode($xml_hyj->time1->jogadores), true) : [];
	$jogadoresTimeB = isset($xml_hyj->time2->jogadores) ? json_decode(json_encode($xml_hyj->time2->jogadores), true) : [];

	
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

