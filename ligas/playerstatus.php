<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

// page given in URL parameter, default page is one
$page = isset($_GET['page']) ? $_GET['page'] : 1;

// set number of records per page
$records_per_page = 15;

// calculate for the query LIMIT clause
$from_record_num = ($records_per_page * $page) - $records_per_page;

$id_jogador = $_GET['player'] ?? 0;

//estabelecer conexão com banco de dados
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogador.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/tecnico.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/liga.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/estadio.php");
require($_SERVER['DOCUMENT_ROOT']."/lib/functions.php");

$database = new Database();
$db = $database->getConnection();

$pais = new Pais($db);
$jogador = new Jogador($db);
$time = new Time($db);
$tecnico = new Tecnico($db);
$liga = new Liga($db);
$estadio = new Estadio($db);

// query times
$info = $jogador->readInfo($id_jogador);

$nome_jogador = $info['nome']; //ok entrada pagina
$foto_jogador = $info['foto'];
$pais_jogador = $info['Pais']; //ok
$time_jogador = $info['time']; //ok entrada pagina
$liga_time = $info['liga']; //ok entrada pagina
$pais_time = $info['paisTime']; //ok
$tier_liga = $info['tier']; //ok
$id_time = $info['idTime']; //ok
$id_liga = $info['idLiga']; //ok
$id_pais = $info['idPais']; //ok
$logo_liga = $info['logoLiga']; //ok
$escudo_time = $info['escudoTime']; //ok
$bandeira_pais = $info['bandeiraPais']; //ok
$donoPais = $info['donoPais'];
$idade_jogador = $info['idade']; //ok
$nascimento_jogador = $info['nascimento']; //ok
$posicoes_jogador = str_pad($info['stringPosicoes'] ?? '', 15, '0');
$isGoleiro = ($posicoes_jogador[0] == 1? 1:0);
$valor_jogador = $info['valor']; //ok
$salario_jogador = $info['salario']; //ok
$desde_quando = $info['inicioContrato'];
$ate_quando = $info['fimContrato'];
$nome_pais_time = $info['nomePaisTime']; //ok
$bandeira_pais_time = $info['bandeiraPaisTime']; //ok
$nivel_jogador = $info['Nivel'];
$lesionado_ate = $info['lesionado_ate'] ?? null;


if(isset($_SESSION['user_id']) && $donoPais == $_SESSION["user_id"]){
    $donoLogado = true;
} else {
    $donoLogado = false;
}

$estatisticas_clube = $jogador->obterEstatisticasPorClube($id_jogador);
$personalidade = $jogador->avaliarPersonalidade($id_jogador);

if($isGoleiro){
	$attribute_array = adjustAttributes(true, $info['Nivel'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, $info['Reflexos'], $info['Seguranca'],  $info['Saidas'],  $info['JogoAereo'],  $info['Lancamentos'],  $info['DefesaPenaltis']);
} else {
	$attribute_array = adjustAttributes(false, $info['Nivel'], $info['Marcacao'], $info['Desarme'], $info['VisaoJogo'], $info['Movimentacao'], $info['Cruzamentos'], $info['Cabeceamento'], $info['Tecnica'], $info['ControleBola'], $info['Finalizacao'], $info['FaroGol'], $info['Velocidade'], $info['Forca'], 0, 0, 0, 0, 0, 0);
}


$page_title = $nome_jogador;
$css_filename = "home_redesign";
$css_login = 'login';
$aux_css = 'playerstatus_redesign';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");
?>
<style>
.stamp {
    font-size: 0.7rem;
    padding: 2px 6px;
    border-radius: 4px;
    font-weight: bold;
    text-transform: uppercase;
    display: inline-block;
    margin-left: 5px;
    vertical-align: middle;
}
.stamp-definitiva { background-color: #2e7d32; color: white; }
.stamp-semcusto { background-color: #546e7a; color: white; }
.stamp-emprestimo { background-color: #1976d2; color: white; }
.stamp-extensao { background-color: #7b1fa2; color: white; }
.stamp-retorno { background-color: #f57c00; color: white; }
.stamp-fim { background-color: #c62828; color: white; }
</style>


<script>




$("document").ready(function(){
	
	var donoLogado = <?php echo $donoLogado?1:0 ?>;
	var results = <?php echo json_encode($attribute_array); ?>;
	var isGoleiro = <?php echo $isGoleiro; ?>;

	attribute_chart(results, isGoleiro);
	

		
		
		if(donoLogado){
		// Botão inline agora, não precisa colocar no toolbar
		}
	
	  $("#form-atributos :input").change(function() {

    level_distributor();
	$("#container-salvar-atributos").show();
    });
	
function update_personality(personalidade){
	
// add personalidade

$("#personalidade").html(
		"<div class='fundo-barra'><div class='barra-cheia' style='width:" + Object.values(personalidade)[0] + "%'></div><p class='texto-barra'>"+ Object.keys(personalidade)[0] +" ("+ (Object.values(personalidade)[0]).toFixed(2) +"%)</p></div>"
		+ "<div class='fundo-barra'><div class='barra-cheia' style='width:" + Object.values(personalidade)[1] + "%'></div><p class='texto-barra'>"+ Object.keys(personalidade)[1] +" ("+ (Object.values(personalidade)[1]).toFixed(2) +"%)</p></div>"
		+ "<div class='fundo-barra'><div class='barra-cheia' style='width:" + Object.values(personalidade)[2] + "%'></div><p class='texto-barra'>"+ Object.keys(personalidade)[2] +" ("+ (Object.values(personalidade)[2]).toFixed(2) +"%)</p></div>"
);

}
	
	
function attribute_chart(results, isGoleiro){

if(isGoleiro){
	 data = [{
		  type: 'scatterpolar',
		  mode: "markers",
		  r: Object.values(results),
		  theta: ['REF','SEG','SAI','JOG', 'LAN','PEN'],
		  fill: 'toself'
		}]
} else {
	 data = [{
		  type: 'scatterpolar',
		  mode: "markers",
		  r: Object.values(results),
		  theta: ['MAR','DES','VIS','MOV', 'CRU','CAB', 'TEC', 'CON', 'FIN', 'FAR', 'VEL', 'FOR'],
		  fill: 'toself'
		}]
}

 layout = {
  margin: {
   l: 40,
   r: 40,
   b: 40,
   t: 40,
   pad: 4
 },
  polar: {
    radialaxis: {
      visible: true,
      range: [0, 10],
      color:"#000000",
      showline: false,
      linewidth: 0,
      ticks: "",
      showticklabels: false
    },
    angularaxis: {
      color:"#000000",
      type: "category"
    },
    bgcolor: 'rgba(0,0,0,0)',
  },
  showlegend: false,
  paper_bgcolor: 'rgba(0,0,0,0)',
  plot_bgcolor: 'rgba(0,0,0,0)',
  font: {
    color:"#334155"
  },
  gridshape: "linear"


}

if(isGoleiro){
	$("#jogo-aereo").val(results.jogoAereo);
	$("#reflexos").val(results.reflexos);
	$("#lancamentos").val(results.lancamentos);
	$("#penaltis").val(results.defesaPenaltis);
	$("#saida-bola").val(results.saidas);
	$("#seguranca").val(results.seguranca);
} else {
	$("#movimentacao").val(results.movimentacao);
	$("#visao").val(results.visaoJogo);
	$("#desarme").val(results.desarme);
	$("#marcacao").val(results.marcacao);
	$("#forca").val(results.forca);
	$("#velocidade").val(results.velocidade);
	$("#faroGol").val(results.faroGol);
	$("#finalizacao").val(results.finalizacao);
	$("#controle").val(results.controleBola);
	$("#tecnica").val(results.tecnica);
	$("#cabeceamento").val(results.cabeceamento);
	$("#cruzamentos").val(results.cruzamentos);

}

Plotly.newPlot("attribute-chart", data, layout, {staticPlot: true, responsive: true},
{displayModeBar: false});

}


	
function level_distributor(){
  var level = <?php echo $nivel_jogador;?> ;
  
  var formData = new FormData();

  
  if(isGoleiro){
	var jogo_aereo = $("#jogo-aereo").val();
	var saida_bola = $("#saida-bola").val();
	var seguranca = $("#seguranca").val();
	var reflexos = $("#reflexos").val();
	var penaltis = $("#penaltis").val();
	var lancamentos = $("#lancamentos").val();
	
	formData.append('isGoleiro', isGoleiro);
	formData.append('level', level);
	formData.append('jogo_aereo', jogo_aereo);
	formData.append('saida_bola', saida_bola);
	formData.append('seguranca', seguranca);
	formData.append('reflexos', reflexos);
	formData.append('penaltis', penaltis);
	formData.append('lancamentos', lancamentos);

	
  } else {
	var movimentacao = $("#movimentacao").val();
	var visao = $("#visao").val();
	var desarme = $("#desarme").val();
	var marcacao = $("#marcacao").val();
	var forca = $("#forca").val();
	var velocidade = $("#velocidade").val();
	var faroGol = $("#faroGol").val();
	var finalizacao = $("#finalizacao").val();
	var controle = $("#controle").val();
	var tecnica = $("#tecnica").val();
	var cabeceamento = $("#cabeceamento").val();
	var cruzamentos = $("#cruzamentos").val();
	
	formData.append('isGoleiro', isGoleiro);
	formData.append('level', level);
	formData.append('movimentacao', movimentacao);
	formData.append('visao', visao);
	formData.append('desarme', desarme);
	formData.append('marcacao', marcacao);
	formData.append('forca', forca);
	formData.append('velocidade', velocidade);
	formData.append('faroGol', faroGol);
	formData.append('finalizacao', finalizacao);
	formData.append('controle', controle);
	formData.append('tecnica', tecnica);
	formData.append('cabeceamento', cabeceamento);
	formData.append('cruzamentos', cruzamentos);

  }
 

   $.ajax({
    url: '../jogadores/alterar_atributos.php',
    type: 'POST',
    dataType: 'json',
	cache: false,
	processData: false,
	contentType: false,
    data: formData 
  })
  .done(function(data) {
	  
	  //console.log("Nivel: " + level);
	  
	 let attributeValues = Object.values(data.attributeArray);
	 
	  
	  let sumAttributes = attributeValues.reduce((a, b) => a + b, 0);

		//console.log("Soma atributos: " + sumAttributes);
		
		//console.log(data.personalidade);
	  
	  attribute_chart(data.attributeArray, isGoleiro);
	  update_personality (data.personalidade);
	  });
  
  

  
}


		$('#salvarDados').click(function(){
			
			let idJogador = <?php echo $id_jogador;?> ;	
			let level = <?php echo $nivel_jogador;?> ;
			let formData = new FormData();
			
			formData.append('isGoleiro', isGoleiro);
			formData.append('level', level);
			formData.append('idJogador', idJogador);
			formData.append('salvar', true);
		  
		  if(isGoleiro){
			let jogo_aereo = $("#jogo-aereo").val();
			let saida_bola = $("#saida-bola").val();
			let seguranca = $("#seguranca").val();
			let reflexos = $("#reflexos").val();
			let penaltis = $("#penaltis").val();
			let lancamentos = $("#lancamentos").val();
		
			formData.append('jogo_aereo', jogo_aereo);
			formData.append('saida_bola', saida_bola);
			formData.append('seguranca', seguranca);
			formData.append('reflexos', reflexos);
			formData.append('penaltis', penaltis);
			formData.append('lancamentos', lancamentos);
			
		  } else {
			let movimentacao = $("#movimentacao").val();
			let visao = $("#visao").val();
			let desarme = $("#desarme").val();
			let marcacao = $("#marcacao").val();
			let forca = $("#forca").val();
			let velocidade = $("#velocidade").val();
			let faroGol = $("#faroGol").val();
			let finalizacao = $("#finalizacao").val();
			let controle = $("#controle").val();
			let tecnica = $("#tecnica").val();
			let cabeceamento = $("#cabeceamento").val();
			let cruzamentos = $("#cruzamentos").val();
			
			formData.append('movimentacao', movimentacao);
			formData.append('visao', visao);
			formData.append('desarme', desarme);
			formData.append('marcacao', marcacao);
			formData.append('forca', forca);
			formData.append('velocidade', velocidade);
			formData.append('faroGol', faroGol);
			formData.append('finalizacao', finalizacao);
			formData.append('controle', controle);
			formData.append('tecnica', tecnica);
			formData.append('cabeceamento', cabeceamento);
			formData.append('cruzamentos', cruzamentos);
		  }

			// let total = 0;

			// for (var value of formData.values()) {
			 // total += parseInt(value);
			// }

			// console.log(total);

			// for (var key of formData.entries()) {
				 // console.log(key[0] + ', ' + key[1]);
			 // }

			 $.ajax({
				url: '../jogadores/alterar_atributos.php',
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
						  $("#container-salvar-atributos").hide();
						    attribute_chart(data.attributeArray, isGoleiro);
							update_personality (data.personalidade);
						  
					  },
					  error: function(data) {
						  successmessage = 'Error';
						  alert("Erro, o procedimento não foi realizado, tente novamente.");
					  }
				  });
		 });	

});


</script>



<?php

echo "<main class='propostas-container' style='padding-top: 80px; padding-bottom: 60px;'>";
echo "<div class='propostas-card'>";
echo "<div id='quadro-container'>";
echo "<div id='quadro-superior'>";
echo "<div id='quadro-nomes'>";
echo "<h2>" . $nome_jogador;
if (!empty($lesionado_ate) && strtotime($lesionado_ate) >= strtotime(date('Y-m-d'))) {
    echo " <span class='stamp stamp-fim' style='background-color:#d32f2f; color:#fff;'><span class='material-symbols-outlined' style='font-size:0.8rem; vertical-align:middle;'>medical_services</span> Lesionado até " . date('d/m/Y', strtotime($lesionado_ate)) . "</span>";
}
echo " </h2>";
if ($id_time == 0) {
    echo "<h3><span>".$time_jogador."</span></h3>";
} else {
    echo "<h3><a href='paisstatus.php?country=".$pais_time."'><img class='smallthumb' src='/images/bandeiras/{$bandeira_pais_time}'>&nbsp" . $nome_pais_time ."</a><a href='leaguestatus.php?league=".$id_liga."'> - <img class='smallthumb' src='/images/ligas/{$logo_liga}'>&nbsp" . $liga_time ." (tier {$tier_liga})</a><a href='teamstatus.php?team=".$id_time."'> - <img class='smallthumb' src='/images/escudos/{$escudo_time}'>&nbsp".$time_jogador." </a></h3> ";
}
echo "</div>";
echo "<div id='quadro-foto'><img id='bandeiraGrande' class='margin-left' src='/images/jogadores/".$foto_jogador."' height='100px'></div>";
echo "</div>";
echo "<hr>";

if (!empty($nascimento_jogador) && strpos($nascimento_jogador, '-') !== false) {
    $nasc_parts = explode("-", $nascimento_jogador);
    $nascimento_jogador = (count($nasc_parts) === 3) 
        ? $nasc_parts[2] . "/" . $nasc_parts[1] . "/" . $nasc_parts[0] 
        : "-";
} else {
    $nascimento_jogador = "-";
}

$valor_jogador = $valor_jogador / 1000;
$salario_jogador = $salario_jogador / 1000;

if(empty($ate_quando) || $ate_quando == 0 || $ate_quando == "0000-00-00"){
    $ate_quando = "Indeterminado";
}

if(empty($desde_quando)){
    $desde_quando = "-";
} else {
    $desde_quando_parts = explode(" ", $desde_quando);
    $desde_quando_date_parts = explode("-", $desde_quando_parts[0]);
    $desde_quando = (count($desde_quando_date_parts) === 3) 
        ? $desde_quando_date_parts[2] . "/" . $desde_quando_date_parts[1] . "/" . $desde_quando_date_parts[0] 
        : "-";
}

//$posicoes_jogador = "111111111111111";

echo "<div id='info_geral'>";
 echo "<div id='info-jogos' class='info_jogador'>";
  echo "<div id='nacionalidade' class='infoblock large' title='Nacionalidade'><span class='material-symbols-outlined'>flag</span><div><span class='informacao'>{$pais_jogador}&nbsp<img class='smallthumb' src='/images/bandeiras/{$bandeira_pais}'></span><span style='font-size: 0.75rem; color: #64748b;'>Nacionalidade</span></div></div>";
  echo "<div id='idade' class='infoblock large' title='Nascimento (idade)'><span class='material-symbols-outlined'>cake</span><div><span class='informacao'>{$nascimento_jogador} ({$idade_jogador} anos)</span><span style='font-size: 0.75rem; color: #64748b;'>Nascimento (idade)</span></div></div>";
  echo "<div id='valor' class='infoblock large' title='Valor (em F$)'><span class='material-symbols-outlined'>price_change</span><div><span class='informacao'>{$valor_jogador} k</span><span style='font-size: 0.75rem; color: #64748b;'>Valor de Mercado</span></div></div>";
  echo "<div id='salario' class='infoblock large' title='Salário (em F$)'><span class='material-symbols-outlined'>paid</span><div><span class='informacao'>{$salario_jogador} k</span><span style='font-size: 0.75rem; color: #64748b;'>Salário</span></div></div>";
  echo "<div id='inicioContrato' class='infoblock large' title='Início do contrato'><span class='material-symbols-outlined'>contract_edit</span><div><span class='informacao'>{$desde_quando}</span><span style='font-size: 0.75rem; color: #64748b;'>Início do Contrato</span></div></div>";
  echo "<div id='fimContrato' class='infoblock large' title='Fim do contrato'><span class='material-symbols-outlined'>contract_delete</span><div><span class='informacao'>{$ate_quando}</span><span style='font-size: 0.75rem; color: #64748b;'>Fim do Contrato</span></div></div>";
 echo "</div>";

 echo "<div id='info_posicionamento'>";
 echo "<div ".($posicoes_jogador[0] == '1'?"":" hidden ")." class='posicaoCampao posGoleiro'></div>";
 echo "<div ".($posicoes_jogador[1] == '1'?"":" hidden ")." class='posicaoCampao posLD'></div>";
 echo "<div ".($posicoes_jogador[2] == '1'?"":" hidden ")." class='posicaoCampao posLE'></div>";
 echo "<div ".($posicoes_jogador[3] == '1'?"":" hidden ")." class='posicaoCampao posZagueiro'></div>";
 echo "<div ".($posicoes_jogador[4] == '1'?"":" hidden ")." class='posicaoCampao posAD'></div>";
 echo "<div ".($posicoes_jogador[5] == '1'?"":" hidden ")." class='posicaoCampao posAE'></div>";
 echo "<div ".($posicoes_jogador[6] == '1'?"":" hidden ")." class='posicaoCampao posVolante'></div>";
 echo "<div ".($posicoes_jogador[7] == '1'?"":" hidden ")." class='posicaoCampao posMD'></div>";
 echo "<div ".($posicoes_jogador[8] == '1'?"":" hidden ")." class='posicaoCampao posME'></div>";
 echo "<div ".($posicoes_jogador[9] == '1'?"":" hidden ")." class='posicaoCampao posMeia'></div>";
 echo "<div ".($posicoes_jogador[12] == '1'?"":" hidden ")." class='posicaoCampao posArmador'></div>";
 echo "<div ".($posicoes_jogador[13] == '1'?"":" hidden ")." class='posicaoCampao posAtacanteMov'></div>";
 echo "<div ".($posicoes_jogador[14] == '1'?"":" hidden ")." class='posicaoCampao posAtacanteArea'></div>";
 echo "<div ".($posicoes_jogador[10] == '1'?"":" hidden ")." class='posicaoCampao posPD'></div>";
 echo "<div ".($posicoes_jogador[11] == '1'?"":" hidden ")." class='posicaoCampao posPE'></div>";
 echo "</div>";
  $golsTotais = $info['golsTime'] + $info['golsSelecao'];
  $amarelosTotais = $info['amarelosTime'] + $info['amarelosSelecao'];
  $vermelhosTotais = $info['vermelhosTime'] + $info['vermelhosSelecao'];
  echo "<div class='carreira-secao'>";
  echo "  <span class='carreira-titulo'>Marcas na carreira</span>";
  echo "  <div class='carreira-grid'>";
  echo "    <div id='jogosSelecao' class='infoblock small' title='Jogos'><span class='material-symbols-outlined'>calendar_check</span><div><span class='informacao_carreira'>{$info['jogosTime']}</span><span style='font-size:0.65rem; color:#64748b;'>Jogos</span></div></div>";
  echo "    <div id='golsSelecao' class='infoblock small' title='Gols'><span class='material-symbols-outlined'>sports_and_outdoors</span><div><span class='informacao_carreira'>{$golsTotais}</span><span style='font-size:0.65rem; color:#64748b;'>Gols</span></div></div>";
  echo "    <div id='amarelosSelecao' class='infoblock small' title='Amarelos'><span class='material-symbols-outlined' style='font-variation-settings: \"FILL\" 1; color: #fbbf24;'>crop_portrait</span><div><span class='informacao_carreira'>{$amarelosTotais}</span><span style='font-size:0.65rem; color:#64748b;'>Amarelos</span></div></div>";
  echo "    <div id='vermelhosSelecao' class='infoblock small' title='Vermelhos'><span class='material-symbols-outlined' style='font-variation-settings: \"FILL\" 1; color: #ef4444;'>crop_portrait</span><div><span class='informacao_carreira'>{$vermelhosTotais}</span><span style='font-size:0.65rem; color:#64748b;'>Vermelhos</span></div></div>";
  echo "  </div>";
  echo "</div>";
  echo "<div id='secao-atributos'>";
 if($donoLogado){
	 
 ?>
 <form id="form-atributos">
 <div class="form-master-group">
            <div class='form-group'>
              <label>Atributos</label>
            </div>
			
<?php if($posicoes_jogador[0] == "1") { ?>
	
            <div class='form-group'>
              <label for="jogo-aereo">Jogo Aéreo</label>
              <input type="range" min="1" max="10" id="jogo-aereo"/>
            </div>
            <div class='form-group'>
              <label for="saida-bola">Saída de Bola</label>
              <input type="range" min="1" max="10" id="saida-bola"/>
            </div>
            <div class='form-group'>
              <label for="seguranca">Segurança</label>
              <input type="range" min="1" max="10" id="seguranca"/>
            </div>
            <div class='form-group'>
              <label for="reflexos">Reflexos</label>
              <input type="range" min="1" max="10" id="reflexos"/>
            </div>
            <div class='form-group'>
              <label for="penaltis">Defesa de Pênaltis</label>
              <input type="range" min="1" max="10" id="penaltis"/>
            </div>
            <div class='form-group'>
              <label for="lancamentos">Lançamentos</label>
              <input type="range" min="1" max="10" id="lancamentos"/>
            </div>
			
			<?php 
} else {
	?>
	            <div class='form-group'>
              <label for="movimentacao">Movimentação</label>
              <input type="range" min="1" max="7" id="movimentacao"/>
            </div>
            <div class='form-group'>
              <label for="visao">Visão de Jogo</label>
              <input type="range" min="1" max="7" id="visao"/>
            </div>
            <div class='form-group'>
              <label for="desarme">Desarme</label>
              <input type="range" min="1" max="7" id="desarme"/>
            </div>
            <div class='form-group'>
              <label for="marcacao">Marcação</label>
              <input type="range" min="1" max="7" id="marcacao"/>
            </div>
            <div class='form-group'>
              <label for="forca">Força</label>
              <input type="range" min="1" max="5" id="forca"/>
            </div>
            <div class='form-group'>
              <label for="velocidade">Velocidade</label>
              <input type="range" min="1" max="5" id="velocidade"/>
            </div>
			<div class='form-group'>
              <label for="faroGol">Faro de Gol</label>
              <input type="range" min="1" max="7" id="faroGol"/>
            </div>
			<div class='form-group'>
              <label for="finalizacao">Finalização</label>
              <input type="range" min="1" max="7" id="finalizacao"/>
            </div>
						<div class='form-group'>
              <label for="controle">Controle de Bola</label>
              <input type="range" min="1" max="7" id="controle"/>
            </div>
						<div class='form-group'>
              <label for="tecnica">Técnica</label>
              <input type="range" min="1" max="7" id="tecnica"/>
            </div>
						<div class='form-group'>
              <label for="cabeceamento">Cabeceamento</label>
              <input type="range" min="1" max="7" id="cabeceamento"/>
            </div>
						<div class='form-group'>
              <label for="cruzamentos">Cruzamentos</label>
              <input type="range" min="1" max="7" id="cruzamentos"/>
            </div>
			
			
			
			
			
			
	
	<?php
	
}

			
     echo "</div>";
     echo "<div id='container-salvar-atributos' style='display:none; text-align:right; margin-top:20px;'>";
     echo "  <button type='button' id='salvarDados' class='btn-historico'><span class='material-symbols-outlined' style='font-size:1.1rem; vertical-align:middle; color:#fff !important; background:none !important; padding:0 !important;'>save</span> Salvar Atributos</button>";
     echo "</div>";
	 echo "</form>";
	 
 }
		  
 echo "<div id='mostrador-atributos'>";
	 echo "<div id='attribute-chart'></div>";
	 echo "<div id='personalidade'>";
		echo "<div class='fundo-barra'><div class='barra-cheia' style='width:" . array_values($personalidade)[0]. "%'></div><p class='texto-barra'>".array_keys($personalidade)[0] ." (".array_values($personalidade)[0]."%)"."</p></div>";
		echo "<div class='fundo-barra'><div class='barra-cheia' style='width:" . array_values($personalidade)[1]. "%'></div><p class='texto-barra'>".array_keys($personalidade)[1]." (".array_values($personalidade)[1]."%)"."</p></div>";
		echo "<div class='fundo-barra'><div class='barra-cheia' style='width:" . array_values($personalidade)[2]. "%'></div><p class='texto-barra'>".array_keys($personalidade)[2]." (".array_values($personalidade)[2]."%)"."</p></div>";
	 echo "</div>";
 echo "</div>";
 echo "</div>";
 echo "</div>";

 echo "<br>";

//query transferencias jogador
$transferencias_stmt = $jogador->readTransferencias($from_record_num,$records_per_page,$id_jogador);

    // the page where this paging is used
    $page_url = "playerstatus.php?player=" . $id_jogador . "&";

    // count all products in the database to calculate total pages
    $total_rows = $jogador->countAllTransferencias($id_jogador);

echo "<div style='clear:both; float:center'></div>";
echo "<hr>";
echo "<p align='center'>Transferências</p>";
if ($donoLogado) {
    echo "<div align='center' style='margin-bottom: 15px;'>";
    echo "<button type='button' onclick='abrirModalNovo()' class='btn-historico'>+ Adicionar Transferência Histórica</button>";
    echo "</div>";
}

    // paging buttons here
    echo "<div style='clear:both; float:center'></div>";
    echo "<div align='center'>";
    include_once($_SERVER['DOCUMENT_ROOT']."/elements/paging.php");
    echo "</div>";
echo "<hr>";

// display the products if there are any

echo "<table id='tabelaElenco' class='table'>";
echo "<thead>";
echo "<tr>";
echo "<th>Data</th>";
echo "<th>Saiu de</th>";
echo "<th>Foi para</th>";
echo "<th>Valor</th>";
if ($donoLogado) {
    echo "<th>Ações</th>";
}
echo "</tr>";
echo "</thead>";
echo "<tbody>";

        while ($row = $transferencias_stmt->fetch(PDO::FETCH_ASSOC)){

            extract($row);

             //$escudoOrigem = explode(".",$escudoOrigem);
             //$escudoDestino = explode(".",$escudoDestino);
             $valor_raw = $valor;
             $valor = $valor/1000;
             $data = explode(" ",$data);
             $data = explode("-", $data[0]);
             $data = $data[2] . "/" . $data[1] . "/" . $data[0];

             $stamp = "";
             if ($emprestimo == 1) {
                 $stamp = "<span class='stamp stamp-emprestimo'>Empréstimo</span>";
             } else if ($emprestimo == 2) {
                 $stamp = "<span class='stamp stamp-extensao'>Extensão</span>";
             } else if ($emprestimo == 3) {
                 $stamp = "<span class='stamp stamp-retorno'>Retorno</span>";
             } else if ($emprestimo == 4) {
                 $stamp = "<span class='stamp stamp-fim'>Fim de Contrato</span>";
             } else if ($emprestimo == 0) {
                 if ($valor_raw == 0) {
                     $stamp = "<span class='stamp stamp-semcusto'>Sem Custo</span>";
                 } else {
                     $stamp = "<span class='stamp stamp-definitiva'>Definitiva</span>";
                 }
             }

            echo "<tr>";
            echo "<td class='nopadding'>{$data}{$stamp}</td>";
            echo "<td class='nopadding'>";
                if($idOrigem != 0){
                    echo "<a href='/ligas/teamstatus.php?team=".$idOrigem."'>";
                } else {
                echo "<span>";
                }
                echo "<img src='/images/escudos/".$escudoOrigem."' class='minithumb'/>{$nomeOrigem}";
                if($idOrigem != 0){
                    echo "</a>";
                    echo "<br/><a class='posicao' href='/ligas/leaguestatus.php?league=".$idLigaOrigem."'><img src='/images/bandeiras/{$bandeiraOrigem}' class='minithumb' id='ban".$paisOrigem."'/>{$nomeLigaOrigem}</a>";
                } else {
                    echo "</span>";
                }
                echo "</td>";
                echo "<td class='nopadding'>";
                if($idDestino != 0){
                    echo "<a href='/ligas/teamstatus.php?team=".$idDestino."'>";
                } else {
                echo "<span>";
                }
                echo "<img src='/images/escudos/".$escudoDestino."' class='minithumb'/>{$nomeDestino}";
                if($idDestino != 0){
                    echo "</a>";
                    echo "<br/><a class='posicao' href='/ligas/leaguestatus.php?league=".$idLigaDestino."'><img src='/images/bandeiras/{$bandeiraDestino}' class='minithumb' id='ban".$paisDestino."'/>{$nomeLigaDestino}</a>";
                    $jogos_cl = 0;
                    $gols_cl = 0;
                    $amarelos_cl = 0;
                    $vermelhos_cl = 0;
                    if (isset($estatisticas_clube[$idDestino])) {
                        $clStats = $estatisticas_clube[$idDestino];
                        $jogos_cl = $clStats['jogos'];
                        $gols_cl = $clStats['gols'];
                        $amarelos_cl = $clStats['amarelos'];
                        $vermelhos_cl = $clStats['vermelhos'];
                    }
                    echo "<br/><span class='performance-clube-tabela' title='Jogos / Gols / Amarelos / Vermelhos no clube'><span class='material-symbols-outlined' style='font-size:0.75rem; vertical-align:middle; margin-right:2px;'>sports_soccer</span><b>{$jogos_cl}</b>j <b>{$gols_cl}</b>g <b>{$amarelos_cl}</b>CA <b>{$vermelhos_cl}</b>CV</span>";
                } else {
                    echo "</span>";
                }
                echo "</td>";
                echo "<td class='nopadding'>F$ {$valor} k</td>";
                if ($donoLogado) {
                    $data_input = explode(" ", $row['data'])[0];
                    echo "<td class='nopadding' style='white-space: nowrap;'>";
                    echo "  <button type='button' class='btn-tabela-editar btn-tabela-acao' data-id='{$id}' data-origem='{$idOrigem}' data-destino='{$idDestino}' data-valor='{$valor_raw}' data-data='{$data_input}' data-tipo='{$emprestimo}' title='Editar'><span class='material-symbols-outlined' style='font-size:1.1rem; vertical-align:middle;'>edit</span></button>";
                    echo "  <button type='button' class='btn-tabela-excluir btn-tabela-acao' data-id='{$id}' title='Excluir'><span class='material-symbols-outlined' style='font-size:1.1rem; vertical-align:middle;'>delete</span></button>";
                    echo "</td>";
                }

            echo "</tr>";

        }

        echo "</tbody>";




echo "</table>";



echo "</div>";
echo "</div>";
echo "</main>";

if ($donoLogado) {
    ?>
    <div id="modalHistorico" class="modal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.6); overflow-y:auto; padding-top:60px;">
      <form id="formHistorico" method="POST" class="modal-content animate larger" style="background-color:#ffffff; border: 1px solid rgba(0, 0, 0, 0.1); color:#0f172a; border-radius:12px; padding:20px; max-width:500px; margin:0 auto;">
        <input type="hidden" id="idTransferenciaHist" name="idTransferencia" value="">
        <div class="container" style="padding: 10px;">
          <h3 id="modalHistoricoTitulo" style="margin-top:0; color:#0284c7;">Adicionar Transferência Histórica</h3>
          <p style="font-size:0.9rem; opacity:0.8; margin-bottom:20px;">Isso registrará a transferência no histórico do jogador, sem alterar seu clube ou contrato atual.</p>
          
          <div class="form-group" style="margin-bottom:15px;">
              <label for="clubeOrigemHist" style="display:block; margin-bottom:5px; font-weight:bold;">Clube de Origem</label>
              <select id="clubeOrigemHist" name="clubeOrigem" class="form-control" required style="background:#ffffff; border:1px solid #cbd5e1; color:#0f172a; width:100%; padding:8px; border-radius:4px;">
                  <option value="0">Sem clube / Livre</option>
                  <?php
                  $times_origem = $time->read();
                  while ($row_t = $times_origem->fetch(PDO::FETCH_ASSOC)) {
                      echo "<option value='{$row_t['id']}'>{$row_t['nome']}</option>";
                  }
                  ?>
              </select>
          </div>

          <div class="form-group" style="margin-bottom:15px;">
              <label for="clubeDestinoHist" style="display:block; margin-bottom:5px; font-weight:bold;">Clube de Destino</label>
              <select id="clubeDestinoHist" name="clubeDestino" class="form-control" required style="background:#ffffff; border:1px solid #cbd5e1; color:#0f172a; width:100%; padding:8px; border-radius:4px;">
                  <option value="0">Sem clube / Livre</option>
                  <?php
                  $times_destino = $time->read();
                  while ($row_t = $times_destino->fetch(PDO::FETCH_ASSOC)) {
                      echo "<option value='{$row_t['id']}'>{$row_t['nome']}</option>";
                  }
                  ?>
              </select>
          </div>

          <div class="form-group" style="margin-bottom:15px;">
              <label for="valorHist" style="display:block; margin-bottom:5px; font-weight:bold;">Valor (F$)</label>
              <input id="valorHist" type="number" name="valor" class="form-control" required style="background:#ffffff; border:1px solid #cbd5e1; color:#0f172a; width:100%; padding:8px; border-radius:4px;" placeholder="Ex: 5000000">
          </div>

          <div class="form-group" style="margin-bottom:15px;">
              <label for="dataHist" style="display:block; margin-bottom:5px; font-weight:bold;">Data da Transferência</label>
              <input id="dataHist" type="date" name="data" class="form-control" required style="background:#ffffff; border:1px solid #cbd5e1; color:#0f172a; width:100%; padding:8px; border-radius:4px;">
          </div>

          <div class="form-group" style="margin-bottom:20px;">
              <label for="tipoHist" style="display:block; margin-bottom:5px; font-weight:bold;">Tipo de Negócio</label>
              <select id="tipoHist" name="emprestimo" class="form-control" required style="background:#ffffff; border:1px solid #cbd5e1; color:#0f172a; width:100%; padding:8px; border-radius:4px;">
                  <option value="0">Permanente</option>
                  <option value="1">Empréstimo</option>
                  <option value="3">Retorno / Fim de Empréstimo</option>
                  <option value="4">Sem custo / Fim de Contrato</option>
              </select>
          </div>

          <div style="display:flex; justify-content:flex-end; gap:10px;">
              <button type="button" onclick="document.getElementById('modalHistorico').style.display='none'" class="btn-modal-cancelar">Cancelar</button>
              <button type="submit" class="btn-modal-salvar">Salvar</button>
          </div>
        </div>
      </form>
    </div>

    <script>
    function abrirModalNovo() {
        document.getElementById('formHistorico').reset();
        document.getElementById('idTransferenciaHist').value = '';
        document.getElementById('modalHistoricoTitulo').innerText = 'Adicionar Transferência Histórica';
        document.getElementById('modalHistorico').style.display = 'block';
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.btn-tabela-editar').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var id = this.getAttribute('data-id');
                var origem = this.getAttribute('data-origem');
                var destino = this.getAttribute('data-destino');
                var valor = this.getAttribute('data-valor');
                var data = this.getAttribute('data-data');
                var tipo = this.getAttribute('data-tipo');

                document.getElementById('idTransferenciaHist').value = id;
                document.getElementById('clubeOrigemHist').value = origem;
                document.getElementById('clubeDestinoHist').value = destino;
                document.getElementById('valorHist').value = valor;
                document.getElementById('dataHist').value = data;
                document.getElementById('tipoHist').value = tipo;

                document.getElementById('modalHistoricoTitulo').innerText = 'Editar Transferência Histórica';
                document.getElementById('modalHistorico').style.display = 'block';
            });
        });

        document.querySelectorAll('.btn-tabela-excluir').forEach(function(btn) {
            btn.addEventListener('click', function() {
                if (confirm('Deseja realmente apagar esta transferência do histórico? Esta ação é irreversível e não afetará o contrato atual.')) {
                    var idTransferencia = this.getAttribute('data-id');
                    var idJogador = <?php echo (int)$id_jogador; ?>;

                    var formData = new FormData();
                    formData.append('idJogador', idJogador);
                    formData.append('alteracao', 12);
                    formData.append('idTransferencia', idTransferencia);

                    fetch('/jogadores/editar_jogador.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Erro: ' + data.error);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Erro ao processar requisição.');
                    });
                }
            });
        });
    });

    document.getElementById('formHistorico').addEventListener('submit', function(e) {
        e.preventDefault();
        
        var idJogador = <?php echo (int)$id_jogador; ?>;
        var idTransferencia = document.getElementById('idTransferenciaHist').value;
        var alteracao = idTransferencia ? 13 : 11;
        var clubeOrigem = document.getElementById('clubeOrigemHist').value;
        var clubeDestino = document.getElementById('clubeDestinoHist').value;
        var valor = document.getElementById('valorHist').value;
        var data = document.getElementById('dataHist').value;
        var emprestimo = document.getElementById('tipoHist').value;
        
        var formData = new FormData();
        formData.append('idJogador', idJogador);
        formData.append('alteracao', alteracao);
        if (idTransferencia) {
            formData.append('idTransferencia', idTransferencia);
        }
        formData.append('clubeOrigem', clubeOrigem);
        formData.append('clubeDestino', clubeDestino);
        formData.append('valor', valor);
        formData.append('data', data);
        formData.append('emprestimo', emprestimo);
        
        fetch('/jogadores/editar_jogador.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erro: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Erro ao enviar requisição.');
        });
    });
    </script>
    <?php
}

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>
