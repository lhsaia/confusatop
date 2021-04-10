<?php

//ini_set( 'display_errors', true );
//error_reporting( E_ALL );
session_start();

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

// page given in URL parameter, default page is one
// $page = isset($_GET['page']) ? $_GET['page'] : 1;

// set number of records per page
$records_per_page = 100;
$from_record_num = 0;

// calculate for the query LIMIT clause
// $from_record_num = ($records_per_page * $page) - $records_per_page;

//estabelecer conexão com banco de dados
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogador.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/tecnico.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/liga.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/estadio.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/federacoes.php");

$database = new Database();
$db = $database->getConnection();

$federacao2 = new Federacao($db);
$pais = new Pais($db);
$jogador = new Jogador($db);
$time = new Time($db);
$tecnico = new Tecnico($db);
$liga = new Liga($db);
$estadio = new Estadio($db);

// query caixa de seleção países desse dono
$stmtPais = $pais->read();
$listaPaises = array();
while ($row_pais = $stmtPais->fetch(PDO::FETCH_ASSOC)){
    extract($row_pais);
    $addArray = array($id, $sigla, $bandeira);
    $listaPaises[] = $addArray;
}

// query caixa de seleção de posições
$stmtPos = $jogador->selectPosicoes();
$listaPosicoes = array();
while ($row_pos = $stmtPos->fetch(PDO::FETCH_ASSOC)){
    extract($row_pos);
    $addArray = array($ID, $Sigla);
    $listaPosicoes[] = $addArray;
}



//lista de times da pessoa
$lista_times = array();

$id = $_GET['team'];
$idTime = $id;

// query times
$info = $time->readInfo($id);
$nome_time = $info['Nome'];
$sigla_time = $info['TresLetras'];
$estadio_time = $info['Estadio'];
$estadio_capacidade = $info['Capacidade'];
$escudo_time = $info['Escudo'];
$foto_estadio = $info['fotoEstadio'];
$uniforme1_time = $info['Uniforme1'];
$uniforme2_time = $info['Uniforme2'];
$pais_time = $info['Pais'];
$liga_time = $info['liga'];
$liga_id = $info['liga_id'];
$pais_id = $info['pais_id'];
$donoPais = $info['donoPais'];
$status_time = $info['status'];

$extra_info = $time->readExtraInfo($id);
$apelido_time = $extra_info['apelido'];
$fundacao_time = $extra_info['fundacao'];
$cidade_time = $extra_info['cidade'];
$patrocinio_time = $extra_info['patrocinio'];
$material_esportivo_time = $extra_info['material_esportivo'];
$titulos_time = $extra_info['titulos'];
$sobre_titulo = $extra_info['sobre_titulo'];
$sobre_subtitulo = $extra_info['sobre_subtitulo'];
$sobre_texto = $extra_info['sobre_texto'];

if(empty($titulos_time)){
	$titulos_time = "14x Campeonato Nacional Serie A, 2x Campeonato Nacional Serie B, 3x Taça Nacional, 1x Campeonato Continental";
}

if(empty($sobre_titulo)){
	$sobre_titulo = "Lorem ipsum dolor sit amet!";
}

if(empty($sobre_subtitulo)){
	$sobre_subtitulo = "Vestibulum dapibus mauris eget tristique mattis 2021";
}



if(empty($sobre_texto)){
	$sobre_texto = "<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum dapibus mauris eget tristique mattis. Nullam efficitur euismod bibendum. Mauris ultricies sed dui non gravida. Praesent non sem malesuada, tincidunt diam ut, interdum sem. Proin sit amet luctus lacus, ut pulvinar orci. Morbi auctor consequat eros sit amet feugiat. Maecenas vitae enim ac lorem viverra commodo. Pellentesque feugiat, nisl in sodales malesuada, ipsum magna fermentum diam, sed dapibus eros nunc a augue.</p>
<p></p>
<p>Maecenas dolor leo, varius eget dignissim eu, maximus nec nisi. Nunc id odio vitae purus pellentesque congue mattis in ligula. Maecenas vulputate dolor in augue dignissim rutrum. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vivamus sagittis ullamcorper porttitor. Fusce ipsum mauris, vestibulum at nulla eget, suscipit tincidunt odio. Pellentesque eu dui mi. Proin lectus turpis, ornare vel est at, suscipit dapibus metus. Fusce sit amet neque efficitur, venenatis turpis consequat, mattis ante. Duis ultricies leo dapibus, ornare arcu ultricies, posuere sem. Sed tempus sapien in metus ultricies, porta dignissim metus porta.</p>";
}


if(isset($_SESSION['user_id']) && $donoPais == $_SESSION["user_id"]){
    $donoLogado = true;
} else {
    $donoLogado = false;
}

if($status_time > 0){
    $is_selecao = true;
} else {
    $is_selecao = false;
}

//outras informações para infoblock
$mediaIdade = number_format($info['mediaIdade'],1);
$estrangeiros = $info['estrangeiros'];
$jogadores_selecao = $info['emSelecao'];
$valor_total_clube = number_format($info['valorTotal']/1000000,1) . "M";
$recorde_transferencia = $time->balancoTransferencias($idTime);
$recorde_transferencia = number_format($recorde_transferencia/1000000,1) . "M";
$nivel_medio = number_format($info['mediaNivel'], 1);
$nivel_medio_onze = number_format($info['mediaNivelOnze'],1);


if($liga_time != ''){
    $liga_time = " - ". $liga_time;
}

//$escudo_imagem = explode(".",$escudo_time);
//$uniforme1_imagem = explode(".",$uniforme1_time);
//$uniforme2_imagem = explode(".",$uniforme2_time);


$page_title = $nome_time;
$css_filename = "team_presentation";
$css_login = 'login';
$aux_css = 'indexRanking';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

function rgb2hsl($r,$g,$b){$r/=255;$g/=255;$b/=255;$max=max($r,$g,$b);$min=min($r,$g,$b);$h;$s;$l=($max+$min)/2;$d=$max-$min;if($d==0){$h=$s=0;}else{$s=$d/(1-abs(2*$l-1));switch($max){case $r:$h=60*fmod((($g-$b)/$d),6);if($b>$g){$h+=360;}break;case $g:$h=60*(($b-$r)/$d+2);break;case $b:$h=60*(($r-$g)/$d+4);break;}}return[round($h,0),round($s*100,0),round($l*100,0)];}

echo "<div style='clear:both; float:center'></div>";

 $pre_color1 = "rgb(".substr($info["Uni1Cor1"],0,3).",".substr($info["Uni1Cor1"],3,3).",".substr($info["Uni1Cor1"],6,3).")";
 $pre_color2 = "rgb(".substr($info["Uni1Cor2"],0,3).",".substr($info["Uni1Cor2"],3,3).",".substr($info["Uni1Cor2"],6,3).")";
 
 // comparacao de luminosidade de cores
$lum_color1 = rgb2hsl(substr($info["Uni1Cor1"],0,3),substr($info["Uni1Cor1"],3,3),substr($info["Uni1Cor1"],6,3))[2];
$lum_color2 = rgb2hsl(substr($info["Uni1Cor2"],0,3),substr($info["Uni1Cor2"],3,3),substr($info["Uni1Cor2"],6,3))[2];

if($lum_color1 > $lum_color2){
	$color1 = $pre_color2;
	$color2 = $pre_color1;
} else {
	$color1 = $pre_color1;
	$color2 = $pre_color2;
}

if($donoLogado){
	$editable = "true";
} else {
	$editable = "false";
}

$time_stmt = $jogador->selecionarElencoTime($id,$from_record_num,$records_per_page);
 
 echo "<div id='principal'>";
 echo "<div id='nomeTime' style='background: ".$color1."'><h3 style='color: ".$color2."'>". $nome_time ."</h3></div>";
 echo "<div id='barraVertical' style='border-color: ".$color2."'></div>";
 echo "<div id='escudoTime' ><img src='/images/escudos/".$escudo_time."'></div>";
 echo "<div id='simboloLiga'><img id='' src='/images/ligas/".$info["logoLiga"]."' height='120px'><span>".$info["liga"]." ".date("Y")." </span></div>";
 echo "<div id='quadroInformacoes'><div id='colorFilter' style='background-color: rgb(".substr($info["Uni1Cor1"],0,3).",".substr($info["Uni1Cor1"],3,3).",".substr($info["Uni1Cor1"],6,3).",0.6)'></div> </div>";
 echo "<div id='quadrosPrincipais'>";
 echo "<div id='quadroEsquerdo'>";
 echo "	 <div id='informacaoBase' >
		 <div id='cidadeTime'><i class='fas fa-map-marker-alt' style='color: ".$color1."'></i><span class='infos_time' contenteditable={$editable}> {$cidade_time}</span></div>
		 <div id='fundacaoTime'><i class='far fa-calendar-alt' style='color: ".$color1."'></i><span class='infos_time' contenteditable={$editable}> {$fundacao_time}</span></div>
		 <div id='apelidoTime'><i class='fas fa-signature' style='color: ".$color1."'></i><span class='infos_time' contenteditable={$editable}> {$apelido_time}</span></div>
		 <div id='patrocinioTime'><i class='fas fa-hand-holding-usd' style='color: ".$color1."'></i><span class='infos_time' contenteditable={$editable}> {$patrocinio_time}</span></div>
		 <div id='materialEsportivoTime'> <i class='fas fa-tshirt' style='color: ".$color1."'></i><span class='infos_time' contenteditable={$editable}> {$material_esportivo_time}</span></div>
		 <div id='titulosTime'> <i class='fas fa-trophy' style='color: ".$color1."'></i><span class='infos_time' contenteditable={$editable}> {$titulos_time}</span></div>
		 <div id='nomeEstadio'><i class='fas fa-home' style='color: ".$color1."'></i><span class='infos_time'> ".$estadio_time." (" .$estadio_capacidade .")</span></div>
 </div>";
 echo "<div id='imagensEstadioUniformes'>";
 echo "<div id='fotoEstadio'><img src='/images/estadios/{$foto_estadio}'></div>";
 echo "<div id='uniforme1'><img src='/images/uniformes/".$info["Uniforme1"]."'></div>";
 echo "<div id='uniforme2'><img src='/images/uniformes/".$info["Uniforme2"]."'></div>";
 echo "</div>";
 echo "<div id='sobreTime'><h2 contenteditable={$editable} >{$sobre_titulo}</h2><h3  contenteditable={$editable} style='color: ".$color1."'>{$sobre_subtitulo}</h3><div id='aboutTeam' contenteditable={$editable} >{$sobre_texto}</div></div>";
  echo "</div>";
   echo "<div id='quadroDireito'>";
   echo "<div id='quadroJogadores'>";
  // foreach()

$lista_titulares = array();
$lista_reservas = array();

        while ($row = $time_stmt->fetch(PDO::FETCH_ASSOC)){

            extract($row);


            $Nascimento = date("d-m-Y", strtotime($Nascimento));

            //calcular posicao se não tiver base definida
            if($posicaoBase == 0){
                //$posicaoBase = $jogador->nomePosicaoPorCodigo((strpos($StringPosicoes, "1"))+1);
                $posicaoBase = '';
            } else {
                $posicaoBase = $jogador->nomePosicaoPorCodigo($posicaoBase);
            }

            $stringPosicoes = $jogador->listaPosicoes($StringPosicoes);

            switch($titularidade){
                case 1:
                    $titular = 'titular';
                    break;
                case 0:
                    $titular = 'reserva';
                    break;
                case -1:
                    $titular = 'suplente';
                    break;
                default:
                    $titular = 'suplente';
                    break;
                }

                if($titular == 'titular'){
                    $lista_titulares[] = ['nome' => $nomeJogador, 'nivel' => $Nivel, 'mod' => $ModificadorNivel, 'posicaoBase' => $posicaoBase, 'stringPosicoes' => $stringPosicoes, 'idJogador' => $idJogador, 'mentalidade' => $mentalidade, 'capitao' => $capitao, 'cobrancaPenalti' => $cobrancaPenalti, 'cobradorFalta' => $cobradorFalta, 'foto' => $foto, 'nascimento' => $Nascimento, 'nacionalidade' => $bandeiraPais];
                } else if($titular == 'reserva'){
                    $lista_reservas[] = ['nome' => $nomeJogador, 'nivel' => $Nivel, 'mod' => $ModificadorNivel, 'posicaoBase' => $posicaoBase, 'stringPosicoes' => $stringPosicoes, 'idJogador' => $idJogador];
                } else {
                    $lista_suplentes[] = ['nome' => $nomeJogador, 'nivel' => $Nivel, 'mod' => $ModificadorNivel, 'posicaoBase' => $posicaoBase, 'stringPosicoes' => $stringPosicoes, 'idJogador' => $idJogador];
                }
		}
	

	//adicionar quadros de jogadores individuais
	foreach($lista_titulares as $ficha){
		
		if(strpos($ficha['posicaoBase'], "Atacante") !== false){
			$posicao = "Atacante";
		} else {
			$posicao = $ficha['posicaoBase'];
		}
		
		$dadosTransferencia = $jogador->ultimaTransferencia($ficha['idJogador'], $idTime);
		if(strlen($ficha['nome'])>16){
			$temp_nome = explode(" ", $ficha["nome"]);
			$sobrenome_jogador = end($temp_nome);
			$primeira_letra = mb_substr($temp_nome[0], 0 ,1);
			$nomeAbreviado = $primeira_letra . ". " . $sobrenome_jogador;
		} else {
			$nomeAbreviado = $ficha['nome'];
		}
		
		echo "<div class='ficha_individual' style='border-color: {$color1}'>";
		echo "<div class='nomeBandeira'><span class='nome_individual'>".mb_strtoupper($nomeAbreviado)."</span><img class='bandeiraIndividual' src='/images/bandeiras/{$ficha['nacionalidade']}'></div>";
		echo "<div class='outras_infos'>";
		echo "<div class='infos_individuais'>";
		echo "<span class='nascimento_individual'>".$ficha['nascimento']."</span>";
		echo "<span class='posicao_individual'>".$posicao."</span>";
		echo "<span class='desde_individual'>No clube desde: ".substr($dadosTransferencia["Data"],-4)."</span>";
		echo "</div>";
		echo "<div class='foto_individual'><img src='/images/jogadores/{$ficha['foto']}'></div>";
		echo "</div></div>";
		
	}
	
	//adicionar quadro do técnico
		
		$stmtTec = $tecnico->infoTecnico($idTime);
		$rowTec = $stmtTec->fetch(PDO::FETCH_ASSOC);

		$transferenciaTecnico = $tecnico->ultimaTransferencia($rowTec['ID'], $idTime);
		
		if(strlen($rowTec['Nome'])>16){
			$temp_nome = explode(" ", $rowTec["Nome"]);
			$sobrenome_jogador = end($temp_nome);
			$primeira_letra = $temp_nome[0][0];
			$nomeAbreviado = $primeira_letra . ". " . $sobrenome_jogador;
		} else {
			$nomeAbreviado = $rowTec['Nome'];
		}
		
		echo "<div class='ficha_individual' style='border-color: {$color1}'>";
		echo "<div class='nomeBandeira'><span class='nome_individual'>".mb_strtoupper($nomeAbreviado)."</span><img class='bandeiraIndividual' src='/images/bandeiras/{$rowTec['bandeiraPais']}'></div>";
		echo "<div class='outras_infos'>";
		echo "<div class='infos_individuais'>";
		echo "<span class='nascimento_individual'>".$rowTec['Nascimento']."</span>";
		echo "<span class='posicao_individual'>Técnico</span>";
		echo "<span class='desde_individual'>No clube desde: ".substr($transferenciaTecnico["Data"],-4)."</span>";
		echo "</div>";
		echo "<div class='foto_individual'><img src='/images/tecnicos/{$rowTec['foto']}'></div>";
		echo "</div></div>";
		
		
	
   echo "</div>";
   echo "<div id='quadroCampo'>";
   echo "<div id='soccerfield'></div>";
   
   //adicionar jogadores reservas
   echo "<div id=quadroReservas>";
		echo "<span>Reservas</span>";
		
		foreach($lista_reservas as $ficha){
			
		if(strlen($ficha['nome'])>12){
			$temp_nome = explode(" ", $ficha["nome"]);
			$sobrenome_jogador = end($temp_nome);
			$primeira_letra = $temp_nome[0][0];
			$nomeAbreviado = $primeira_letra . ". " . $sobrenome_jogador;
		} else {
			$nomeAbreviado = $ficha['nome'];
		}
		
		
			echo "<span>".$nomeAbreviado. " (".explode("-",$ficha['stringPosicoes'])[0].")</span>";
		}
   
   echo "</div>";
  echo "</div>";
echo "</div>";
echo "</div>";
 ?>
 
 
 <script src="/js/dom-to-image.min.js"></script>
 <script src="/js/FileSaver.min.js"></script>


<script>



$(document).ready(function(){
	
	$("#security-test").remove();
	
	var perguntarSaida = false;
	
	var donoLogado = <?php echo $donoLogado?1:0 ?>;
	
	$("#toolbar").html('<div id="irDetalhes"><i class="fas fa-tasks"></i><span>Detalhes</span></div>');
	
			$("#irDetalhes").on("click", function(){
			window.location = "/ligas/teamstatus.php?team=" + <?php echo $idTime ?>;
		});
	
	if(donoLogado){
		$("#toolbar").append('<div id="salvarDados"><i class="far fa-save"></i><span>Salvar</span></div><div id="tirarPrint"><i class="fas fa-print"></i><span>Baixar print</span></div>');
		
		$("#tirarPrint").on("click", function(){
			
			$("#top-bar").children().toggle();
			$("#bottom-bar").children().toggle();
			
			domtoimage.toBlob(document.getElementsByTagName("body")[0])
			//domtoimage.toBlob(document.getElementById("principal"))
				.then(function (blob) {
					window.saveAs(blob, 'Ficha_<?php echo $nome_time ?>.png');
					$("#top-bar").children().toggle();
					$("#bottom-bar").children().toggle();
				});
				
				
			
		});
		
		$("span, h2, h3, #aboutTeam").on('DOMSubtreeModified', function () {
			//alert("HTML is now " + $(this).html());
			if(!perguntarSaida){
				window.addEventListener("beforeunload", function (e) {
			  var confirmationMessage = "\o/";

			  e.returnValue = confirmationMessage;     // Gecko, Trident, Chrome 34+
			  return confirmationMessage;              // Gecko, WebKit, Chrome <34
			});
				perguntarSaida = true;
			}
			
		});
		

		
		$('#salvarDados').click(function(){
			
			let cidade = $("#cidadeTime").text();
			let fundacao = $("#fundacaoTime").text();
			let apelido = $("#apelidoTime").text();
			let patrocinio = $("#patrocinioTime").text();
			let material_esportivo = $("#materialEsportivoTime").text();
			let titulos = $("#titulosTime").text();
			let sobre_titulo = $("#sobreTime h2").text();
			let sobre_subtitulo = $("#sobreTime h3").text();
			let sobre_texto = $("#aboutTeam").html();
			
			let idTime = <?php echo $idTime ?>;

			var formData = new FormData();
			formData.append('id', idTime);
			formData.append('cidade', cidade);
			formData.append('fundacao', fundacao);
			formData.append('apelido', apelido);
			formData.append('patrocinio', patrocinio);
			formData.append('material_esportivo', material_esportivo);
			formData.append('titulos', titulos);
			formData.append('sobre_titulo', sobre_titulo);
			formData.append('sobre_subtitulo', sobre_subtitulo);
			formData.append('sobre_texto', sobre_texto);

		// for (var key of formData.entries()) {
			 // console.log(key[0] + ', ' + key[1]);
		 // }

			 $.ajax({
				 url: 'alterar_sobre_time.php',
				 processData: false,
				contentType: false,
				cache: false,
				type: "POST",
				dataType: 'json',
				 data: formData,
					  success: function(data) {
						  if(data.error != ''){
							alert(data.error)
						  }
						  location.reload();
					  },
					  error: function(data) {
						  successmessage = 'Error';
						  alert("Erro, o procedimento não foi realizado, tente novamente.");
						  //location.reload();
					  }
				  });
		});
	}
	
	var soccerfieldData = [
	<?php 
	
	$zagueiro = 0;
	$volante = 0;
	$meia = 0;
	$armador = 0;
	$atacante = 0;
	
	$zagueiro_at = 1;
	$volante_at = 1;
	$meia_at = 1;
	$armador_at = 1;
	$atacante_at = 1;
	
	foreach($lista_titulares as $jogador_tabela){

		switch($jogador_tabela['posicaoBase']){
			case "Zagueiro":
				$zagueiro++;
				break;
			case "Volante":
				$volante++;
				break;
			case "Meia central":
				$meia++;
				break;
			case "Meia-atacante":
				$armador++;
				break;
			case "Atacante de movimentação":
				$atacante++;
				break;
			case "Atacante de área":
				$atacante++;
				break;
			default:
				break;
    }
		
	}

	
	foreach($lista_titulares as $jogador_tabela){
		
		//de-para posicoes
		$dicionario_posicoes = [
			"Goleiro" => "C_GK",
			"Lateral-direito" => "R_B",
			"Lateral-esquerdo" => "L_B",
			"Zagueiro" => "_B",
			"Ala esquerdo" => "L_DM",
			"Ala direito" => "R_DM",
			"Volante" => "_DM",
			"Meia esquerdo" => "L_M",
			"Meia direito" => "R_M",
			"Meia central" => "_M",
			"Meia-atacante" => "_AM",
			"Ponta direita" => "R_F",
			"Ponta esquerda" => "L_F",
			"Atacante de área" => "_F",
			"Atacante de movimentação" => "_F",
		];
		
		if(strlen($jogador_tabela["nome"]) > 12){
			$temp_nome = explode(" ", $jogador_tabela["nome"]);
			$sobrenome_jogador = end($temp_nome);
			$primeira_letra = $temp_nome[0][0];
			$nome_final = $primeira_letra . ". " . $sobrenome_jogador;
		} else {
			$nome_final = $jogador_tabela["nome"];
		}
		
		$posicao_final = $dicionario_posicoes[$jogador_tabela['posicaoBase']];
		
		$modificador = "";
		
		switch($posicao_final){
			case "_B":
				$modificador = $zagueiro == 1 ? "C" : $modificador;
				$modificador = ($zagueiro == 2 && $zagueiro_at == 1) ? "RC": $modificador;
				$modificador = ($zagueiro == 2 && $zagueiro_at == 2) ? "LC": $modificador;
				$modificador = ($zagueiro > 2 && $zagueiro_at == 1) ? "RC": $modificador;
				$modificador = ($zagueiro > 2 && $zagueiro_at == 2)? "C": $modificador;
				$modificador = ($zagueiro > 2 && $zagueiro_at == 3) ? "LC": $modificador;
				$zagueiro_at++;
				break;
			case "_DM":
				$modificador = $volante == 1 ? "C" : $modificador;
				$modificador = ($volante == 2 && $volante_at == 1) ? "RC": $modificador;
				$modificador = ($volante == 2 && $volante_at == 2) ? "LC": $modificador;
				$modificador = ($volante > 2 && $volante_at == 1) ? "RC": $modificador;
				$modificador = ($volante > 2 && $volante_at == 2)? "C": $modificador;
				$modificador = ($volante > 2 && $volante_at == 3) ? "LC": $modificador;
				$volante_at++;
				break;
			case "_M":
				$modificador = $meia == 1 ? "C" : $modificador;
				$modificador = ($meia == 2 && $meia_at == 1) ? "RC": $modificador;
				$modificador = ($meia == 2 && $meia_at == 2) ? "LC": $modificador;
				$modificador = ($meia > 2 && $meia_at == 1) ? "RC": $modificador;
				$modificador = ($meia > 2 && $meia_at == 2)? "C": $modificador;
				$modificador = ($meia > 2 && $meia_at == 3) ? "LC": $modificador;
				$meia_at++;				
				break;
			case "_AM":
				$modificador = $armador == 1 ? "C" : $modificador;
				$modificador = ($armador == 2 && $armador_at == 1) ? "RC": $modificador;
				$modificador = ($armador == 2 && $armador_at == 2) ? "LC": $modificador;
				$modificador = ($armador > 2 && $armador_at == 1) ? "RC": $modificador;
				$modificador = ($armador > 2 && $armador_at == 2)? "C": $modificador;
				$modificador = ($armador > 2 && $armador_at == 3) ? "LC": $modificador;
				$armador_at++;				
				break;
			case "_F":
				$modificador = $atacante == 1 ? "C" : $modificador;
				$modificador = ($atacante == 2 && $atacante_at == 1) ? "RC": $modificador;
				$modificador = ($atacante == 2 && $atacante_at == 2) ? "LC": $modificador;
				$modificador = ($atacante > 2 && $atacante_at == 1) ? "RC": $modificador;
				$modificador = ($atacante > 2 && $atacante_at == 2)? "C": $modificador;
				$modificador = ($atacante > 2 && $atacante_at == 3) ? "LC": $modificador;
				$atacante_at++;				
				break;
			default:
				break;
    }
		
		$posicao_final = $modificador . $posicao_final;
		if($posicao_final == "C_GK"){
			$uniforme_final = $uniforme2_time;
			} else {
				$uniforme_final = $uniforme1_time;
			}
	
		echo "{name: '" . $nome_final . "', position: '" .$posicao_final . "', img: '/images/uniformes/" .$uniforme_final. "'},";

	}

	?>
];

$("#soccerfield").soccerfield(soccerfieldData,{
  field: {
    width: "550px",
	height: "250px",
    img: '/images/fifa_soccer_field_1.png',
    startHidden: false,
    animate: true,
    fadeTime: 10,
    autoReveal:true
  },
  players: {
	  img: '/images/soccer-player.png',
	  font_size: 8,
	  reveal: true,
	  animate: true,
	  sim: false,
	  timeout: 1000,
	  fadeTime: 1000
  }
});


});

$("select[id^='select']").on("click", function(){
$(this).css("background-color", "white");
});

</script>


<?php

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
