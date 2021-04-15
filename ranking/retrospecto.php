<?php

session_start();

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Retrospecto";
$css_filename = "indexRanking";
$css_login = 'login';
$aux_css = 'jogoserecordes';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");
include_once 'ranking_header.php';

echo "<div id='ranking-container'>";
echo "<div  id='ranking'>";
echo "<h2> Retrospecto </h2>";
echo "<hr>";

//query informacoes
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogos.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
$database = new Database();
$db = $database->getConnection();

$jogo = new Jogo($db);
$pais = new Pais($db);

$info_stmt = $jogo->recuperarInfoGeral();
$info = $info_stmt->fetch(PDO::FETCH_ASSOC);

$stmtPais = $pais->read(null,true,false);
$listaPaises = array();
while ($row_pais = $stmtPais->fetch(PDO::FETCH_ASSOC)){
    extract($row_pais);
    $addArray = array($id, $sigla, $nome, $bandeira, $dono);
    $listaPaises[] = $addArray;
}

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){
	
	$ownedCountries = array_filter($listaPaises, function ($var) {
		return ($var[4] == $_SESSION['user_id']);
	});

	$key1 = array_rand($ownedCountries);
	$random1 = $ownedCountries[$key1];
} else {
	$key1 = array_rand($listaPaises);
	$random1 =$listaPaises[$key1];
}

$key2 = array_rand($listaPaises);
  
$random2 = $listaPaises[$key2];

echo "<div id='adversarios'>";
echo "<select id='selectPais1' class='selectPaises'>";

foreach($listaPaises as $item){
	echo "<option ".($item[0] == $random1 ? " selected " : "" )." data-flag='{$item[3]}' value='{$item[0]}'>{$item[2]}</option>";
}

echo "</select>";
echo "<span id='equis'>X</span>";
echo "<select id='selectPais2' class='selectPaises'>";

foreach($listaPaises as $item){
	echo "<option data-flag='{$item[3]}' value='{$item[0]}'>{$item[2]}</option>";
}

echo "</select>";
echo "</div>";

echo "<hr>";

echo "<div id='linhaJogos'>";
echo "<div id='vitoriasTimeA'></div>";
echo ' <div id="gaugeJogos" class="gauge" style="width: 200px; --rotation:123deg; --rotationa: 179deg; --color:#5cb85c; --background:#e9ecef; --backgrounda:red"> ';
echo '   <div class="percentage1"></div>';
echo '   <div class="percentage2"></div>';
echo '   <div class="mask"></div>';
echo '   <span id="totalJogos" class="value"></span>';
echo ' </div>';
echo "<div id='vitoriasTimeB'></div>";
echo "</div>";

echo "<div id='linhaGols'>";
echo "<div id='golsTimeA'></div>";
echo ' <div  id="gaugeGols"  class="gauge" style="width: 200px; --rotation:150deg; --rotationa: 150deg; --color:#5cb85c; --background:#e9ecef; --backgrounda:red"> ';
echo '   <div class="percentage1"></div>';
echo '   <div class="percentage2"></div>';
echo '   <div class="mask"></div>';
echo '   <span id="totalGols" class="value"></span>';
echo ' </div>';
echo "<div id='golsTimeB'></div>";
echo "</div>";

echo "<div id='linhaMaioresVitorias'>";
echo "<div id='maiorVitoriaA'></div>";
echo "<div id='maiorVitoriaB'></div>";
echo "</div>";

echo "<div id='linhaMaioresSequencias'>";
echo "<div id='maiorSequenciaVitorias'></div>";
echo "<div id='maiorJejumVitorias'> </div>";
echo "<div id='maiorInvencibilidade'></div>";
echo "</div>";

echo "<div style='clear:both; float:center'></div>";

echo "<hr>";

echo "<div class='tbl_user_data'><img id='loading' src='/images/icons/ajax-loader.gif'></div>";


echo('</div>');
echo('</div>');


echo "</div>";

?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.0.12/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.12/dist/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/color-thief/2.3.0/color-thief.umd.js"></script>

<script>

var localData = [];
var asc = true;
var activeSort = '';

var dictFases = {0: "N/A", 1: "Fase pré",2: "Fase de grupos",3: "Oitavas-de-final",4: "Quartas-de-final",5: "Semi-final",6: "Disputa de terceiro lugar",7: "Repescagem",8: "Final"};

$(document).ready(function($){

	    $("#selectPais1").select2({
			templateResult: function (country) {
								if (!country.id) {
									return country.text;
								}
								var baseUrl = "/images/bandeiras/";
								var $country = $(
									'<span><img src="' + baseUrl + '/' + country.element.attributes[0].value + '" class="bandeira" /><span  class="opcaoPaisNome"> ' + country.text + '</span></span>'
								);
								return $country;
							},
			templateSelection: function (country) {
								if (!country.id) {
									return country.text;
								}
								var baseUrl = "/images/bandeiras/";
								var $country = $(
									'<span><span> ' + country.text + '</span><img src="' + baseUrl + '/' + country.element.attributes[0].value + '" id="bs01" class="bandeiraSelect" /></span>'
								);
								return $country;
							},
			width:'resolve',
			height:'resolve'
		});
	
		$("#selectPais2").select2({
			templateResult: function (country) {
								if (!country.id) {
									return country.text;
								}
								var baseUrl = "/images/bandeiras/";
								var $country = $(
									'<span><img src="' + baseUrl + '/' + country.element.attributes[0].value + '" class="bandeira" /><span class="opcaoPaisNome"> ' + country.text + '</span></span>'
								);
								return $country;
							},
			templateSelection: function (country) {
								if (!country.id) {
									return country.text;
								}
								var baseUrl = "/images/bandeiras/";
								var $country = $(
									'<span><img id="bs02"  src="' + baseUrl + '/' + country.element.attributes[0].value + '" class="bandeiraSelect" /><span> ' + country.text + '</span></span>'
								);
								return $country;
							},
			width:'resolve',
			height:'resolve'
		});
		
		let random1 = <?php echo $random1[0] ?>;
		let random2 = <?php echo $random2[0] ?>;
		//console.log(random1);
		//console.log(random2);
		
		$('#selectPais1').val(random1);
		$('#selectPais2').val(random2);
		$('#selectPais1').trigger('change');
		$('#selectPais2').trigger('change');
	

load_data();

$(".selectPaises").on("change",function(){
	load_data();
});

function load_data(){

var times =[$("#selectPais1").val(),$("#selectPais2").val()];

$('#loading').show();  // show loading indicator

$.ajax({
    url:"pesquisaRetrospecto.php",
    method:"POST",
    cache:false,
    data:{times:times}
}).done(function(data){
	$('#loading').hide();  // hide loading indicator
	json_data = JSON.parse(data);
	jogos_data = json_data.jogos;
	updateTable(jogos_data,1,0,0);
	localData = jogos_data;
}).done(function(data){
	updateCharts(jogos_data);
});

}

function updateCharts(ajax_data){
	let timeA = $("#selectPais1").val();
	let timeB = $("#selectPais2").val();
	let golsA = 0;
	let golsB = 0;
	let vitoriasA = 0;
	let vitoriasB = 0;
	let empates = 0;
	let jogos = 0;
	
	//detecção de cor
	var rgbToHex = function (rgb) { 
		var hex = Number(rgb).toString(16);
		if (hex.length < 2) {
			hex = "0" + hex;
		}
		return hex;
	};
	
	var fullColorHex = function(r,g,b) {   
		var red = rgbToHex(r);
		var green = rgbToHex(g);
		var blue = rgbToHex(b);
		return red+green+blue;
	};
	
	const colorThief = new ColorThief();
    const img = document.querySelector('#bs01');

    // Make sure image is finished loading
    if (img.complete) {
      colorA = colorThief.getColor(img);
	  paletteA = colorThief.getPalette(img);
    } else {
      img.addEventListener('load', function() {
        colorA = colorThief.getColor(img);
		paletteA = colorThief.getPalette(img);
      });
    }
	
	const colorThief2 = new ColorThief();
	const img2 = document.querySelector('#bs02');
	
	// Make sure image is finished loading
    if (img2.complete) {
      colorB = colorThief2.getColor(img2);
	  paletteB = colorThief.getPalette(img2);
    } else {
      img2.addEventListener('load', function() {
        colorB = colorThief2.getColor(img2);
		paletteB = colorThief.getPalette(img2);
      });
    }
	
	//gols
	golsA = ajax_data.reduce(function (acumulador, item) {
		if(item.idA == timeA){
			return acumulador + parseInt(item.timeAgols);
		} else if (item.idB == timeA){
			return acumulador + parseInt(item.timeBgols);
		} else {
			return acumulador;
		}
		
	}, 0);
	
	golsB = ajax_data.reduce(function (acumulador, item) {
		if(item.idA == timeB){
			return acumulador + parseInt(item.timeAgols);
		} else if (item.idB == timeB){
			return acumulador + parseInt(item.timeBgols);
		} else {
			return acumulador;
		}
		
	}, 0);
	
	let totalGols = golsA + golsB;
	
	let anguloA = (golsA/totalGols) * 180;
	let anguloB = 180 - ((golsB/totalGols) * 180);
	

	//jogos
		
	vitoriasA = ajax_data.reduce(function (acumulador, item) {
		if(item.idA == timeA && item.timeAgols > item.timeBgols){
			return acumulador + 1;
		} else if (item.idB == timeA && item.timeBgols > item.timeAgols){
			return acumulador + 1;
		} else {
			return acumulador;
		}
		
	}, 0);
	
	vitoriasB = ajax_data.reduce(function (acumulador, item) {
		if(item.idA == timeB && item.timeAgols > item.timeBgols){
			return acumulador + 1;
		} else if (item.idB == timeB && item.timeBgols > item.timeAgols){
			return acumulador + 1;
		} else {
			return acumulador;
		}
		
	}, 0);
	
	empates = ajax_data.reduce(function (acumulador, item) {
		if(item.timeAgols == item.timeBgols){
			return acumulador + 1;
		} else {
			return acumulador;
		}
		
	}, 0);
	
	jogos = ajax_data.length;
	
	let anguloA2 = (vitoriasA/jogos) * 180;
	let anguloB2 = 180 - ((vitoriasB/jogos) * 180);
	
	//avaliacao maior vitoria cada time
	var maiorVitoriaA = {id: null, difGol: 0, golPro: 0, home: true};
	var maiorVitoriaB = {id: null, difGol: 0, golPro: 0, home: true};
	
	var maiorSequenciaVitorias = {timeA: 0, timeB: 0};
	var maiorJejumVitorias = {timeA: 0, timeB: 0};
	
	var sequenciaVitoriasA = 0;
	var sequenciaVitoriasB = 0;
	var jejumVitoriasA = 0;
	var jejumVitoriasB = 0;

	ajax_data.forEach(function(item, i) {
		if((item.idA == timeA && item.timeAgols > item.timeBgols) || (item.idB == timeA && item.timeBgols > item.timeAgols)){
			sequenciaVitoriasA++;
			jejumVitoriasA = 0;
			if(sequenciaVitoriasA > maiorSequenciaVitorias.timeA){
				maiorSequenciaVitorias.timeA = sequenciaVitoriasA;
			}
			let difGol = Math.abs(item.timeAgols - item.timeBgols);
			let golPro = Math.max(item.timeAgols, item.timeBgols);
			let id = i;
			
			

			if(difGol > maiorVitoriaA.difGol){
				maiorVitoriaA.difGol = difGol;
				maiorVitoriaA.golPro = golPro;
				maiorVitoriaA.id = i;
				
				if(item.idA == timeA){
					maiorVitoriaA.home = true;
				} else {
					maiorVitoriaA.home = false;
				}

			} else if(difGol == maiorVitoriaA.difGol){
				if(golPro > maiorVitoriaA.golPro){
					maiorVitoriaA.difGol = difGol;
					maiorVitoriaA.golPro = golPro;
					maiorVitoriaA.id = i;
					
					if(item.idA == timeA){
						maiorVitoriaA.home = true;
					} else {
						maiorVitoriaA.home = false;
					}
				}
			}
		} else {
			sequenciaVitoriasA = 0;
			jejumVitoriasA++;
			if(jejumVitoriasA > maiorJejumVitorias.timeA){
				maiorJejumVitorias.timeA = jejumVitoriasA;
			}
		}
	})	
	
	
		ajax_data.forEach(function(item, i) {
		if((item.idA == timeB && item.timeAgols > item.timeBgols) || (item.idB == timeB && item.timeBgols > item.timeAgols)){
			sequenciaVitoriasB++;
			jejumVitoriasB = 0;
			if(sequenciaVitoriasB > maiorSequenciaVitorias.timeB){
				maiorSequenciaVitorias.timeB = sequenciaVitoriasB;
			}
			let difGol = Math.abs(item.timeAgols - item.timeBgols);
			let golPro = Math.max(item.timeAgols, item.timeBgols);
			let id = i;

			if(difGol > maiorVitoriaB.difGol){
					maiorVitoriaB.difGol = difGol;
					maiorVitoriaB.golPro = golPro;
					maiorVitoriaB.id = i;
					
					if(item.idA == timeB){
						maiorVitoriaB.home = true;
					} else {
						maiorVitoriaB.home = false;
					}
			} else if(difGol == maiorVitoriaB.difGol){
				if(golPro > maiorVitoriaB.golPro){
					maiorVitoriaB.difGol = difGol;
					maiorVitoriaB.golPro = golPro;
					maiorVitoriaB.id = i;
					
					if(item.idA == timeB){
						maiorVitoriaB.home = true;
					} else {
						maiorVitoriaB.home = false;
					}
				}
			}
		}  else {
			sequenciaVitoriasB = 0;
			jejumVitoriasB++;
			if(jejumVitoriasB > maiorJejumVitorias.timeB){
				maiorJejumVitorias.timeB = jejumVitoriasB;
			}
		}
	})	
	
	//avaliacao maiores sequencias gerais (de vitorias, jejum, invencibilidade)
	
	//preenchimento informações
	if(jogos == 0){
		$("#totalJogos").html("Nunca<br>jogaram");
	} else if(jogos == 1){
		$("#totalJogos").html(jogos.toString() + " <br>Jogo" );
	} else {
		$("#totalJogos").html(jogos.toString() + " <br>Jogos" );
	}
	
	if(totalGols == 0){
		$("#totalGols").html("Não há<br>gols");
	} else if(totalGols == 1){
		$("#totalGols").html(totalGols.toString() + " <br>Gol" );
	} else {
		$("#totalGols").html(totalGols.toString() + " <br>Gols" );
	}
	
	if(vitoriasA == 0){
		$("#vitoriasTimeA").html("Nunca<br>Venceu");
	} else if(vitoriasA == 1){
		$("#vitoriasTimeA").html("<span class='largeNumber'>" + vitoriasA.toString() + " </span><span>Vitória</span>" );
	} else {
		$("#vitoriasTimeA").html("<span class='largeNumber'>" + vitoriasA.toString() + " </span><span>Vitórias" );
	}
	
	if(vitoriasB == 0){
		$("#vitoriasTimeB").html("Nunca<br>Venceu");
	} else if(vitoriasB == 1){
		$("#vitoriasTimeB").html("<span class='largeNumber'>" + vitoriasB.toString() + " </span><span>Vitória</span>" );
	} else {
		$("#vitoriasTimeB").html("<span class='largeNumber'>" + vitoriasB.toString() + " </span><span>Vitórias</span>" );
	}
	
	if(golsA == 0){
		$("#golsTimeA").html("Nunca<br>Marcou");
	} else if(golsA == 1){
		$("#golsTimeA").html("<span class='largeNumber'>" + golsA.toString() + " </span><span>Gol</span>" );
	} else {
		$("#golsTimeA").html("<span class='largeNumber'>" + golsA.toString() + " </span><span>Gols" );
	}
	
	if(golsB == 0){
		$("#golsTimeB").html("Nunca<br>Marcou");
	} else if(golsB == 1){
		$("#golsTimeB").html("<span class='largeNumber'>" + golsB.toString() + " </span><span>Gol</span>" );
	} else {
		$("#golsTimeB").html("<span class='largeNumber'>" + golsB.toString() + " </span><span>Gols</span>" );
	}
	
	let subquery = "";
	
	if(maiorVitoriaA.home){
		query1 = "timeAgols";
		query2 = "timeBgols";
	} else {
		query1 = "timeBgols";
		query2 = "timeAgols";
	}
	
	let idJogo = maiorVitoriaA.id;
	
	let nomePaisA = $("#selectPais1 :selected").text();
	let nomePaisB = $("#selectPais2 :selected").text();
	let bandeiraPaisA = $("#selectPais1 :selected").attr("data-flag");
	let bandeiraPaisB = $("#selectPais2 :selected").attr("data-flag");
	
	if(maiorVitoriaA.id != null){
		$("#maiorVitoriaA").html("<span>Maior vitória</span><span>"+nomePaisA+"</span><span class='golsPartida'><img src='/images/bandeiras/"+bandeiraPaisA+"' class='bandeira'/>" + ajax_data[idJogo][query1] + "X" + ajax_data[idJogo][query2] + "<img src='/images/bandeiras/"+bandeiraPaisB+"' class='bandeira'/></span><span class='dataPartida'>"+ajax_data[idJogo]["data"]+"</span><span class='estadioPartida'>"+ajax_data[idJogo]["estadio"]+"</span>");
	} else {
		$("#maiorVitoriaA").html("");
	}
	
	
	if(maiorVitoriaB.home){
		query1 = "timeAgols";
		query2 = "timeBgols";
	} else {
		query1 = "timeBgols";
		query2 = "timeAgols";
	}
	
	idJogo = maiorVitoriaB.id;
	

	if(maiorVitoriaB.id != null){
			$("#maiorVitoriaB").html("<span>Maior vitória</span><span>"+nomePaisB+"</span><span class='golsPartida'><img src='/images/bandeiras/"+bandeiraPaisB+"' class='bandeira'/>" + ajax_data[idJogo][query1] + "X" + ajax_data[idJogo][query2] + "<img src='/images/bandeiras/"+bandeiraPaisA+"' class='bandeira'/></span><span class='dataPartida'>"+ajax_data[idJogo]["data"]+"</span><span class='estadioPartida'>"+ajax_data[idJogo]["estadio"]+"</span>");
	} else {
		$("#maiorVitoriaB").html("");
	}

	$("#linhaMaioresSequencias").show();
	$("#linhaMaioresVitorias").show();
	
	if(maiorSequenciaVitorias.timeA > maiorSequenciaVitorias.timeB){
		$("#maiorSequenciaVitorias").html("<span>Maior sequência de vitórias <img src='/images/bandeiras/"+bandeiraPaisA+"' class='bandeira'/> "+maiorSequenciaVitorias.timeA+" vitórias</span>");
	} else if(maiorSequenciaVitorias.timeA < maiorSequenciaVitorias.timeB){
		$("#maiorSequenciaVitorias").html("<span>Maior sequência de vitórias <img src='/images/bandeiras/"+bandeiraPaisB+"' class='bandeira'/> "+maiorSequenciaVitorias.timeB+" vitórias</span>");
	} else if(maiorSequenciaVitorias.timeA > 0){
		$("#maiorSequenciaVitorias").html("<span>Maior sequência de vitórias <img src='/images/bandeiras/"+bandeiraPaisA+"' class='bandeira'/><img src='/images/bandeiras/"+bandeiraPaisB+"' class='bandeira'/> "+maiorSequenciaVitorias.timeB+" vitórias</span>");
	} else {
		$("#maiorSequenciaVitorias").html("");
		$("#linhaMaioresSequencias").hide();
		$("#linhaMaioresVitorias").hide();
	}
	
	if(maiorJejumVitorias.timeA > maiorJejumVitorias.timeB){
		$("#maiorJejumVitorias").html("<span>Maior jejum de vitórias <img src='/images/bandeiras/"+bandeiraPaisA+"' class='bandeira'/> "+maiorJejumVitorias.timeA+" jogos</span>");
		$("#maiorInvencibilidade").html("<span>Maior invencibilidade <img src='/images/bandeiras/"+bandeiraPaisB+"' class='bandeira'/> "+maiorJejumVitorias.timeA+" jogos</span>");
	} else if(maiorJejumVitorias.timeA < maiorJejumVitorias.timeB){
		$("#maiorJejumVitorias").html("<span>Maior jejum de vitórias <img src='/images/bandeiras/"+bandeiraPaisB+"' class='bandeira'/> "+maiorJejumVitorias.timeB+" jogos</span>");
		$("#maiorInvencibilidade").html("<span>Maior invencibilidade <img src='/images/bandeiras/"+bandeiraPaisA+"' class='bandeira'/> "+maiorJejumVitorias.timeB+" jogos</span>");
	} else if(maiorJejumVitorias.timeA > 0){
		$("#maiorJejumVitorias").html("<span>Maior jejum de vitórias <img src='/images/bandeiras/"+bandeiraPaisA+"' class='bandeira'/><img src='/images/bandeiras/"+bandeiraPaisB+"' class='bandeira'/> "+maiorJejumVitorias.timeB+" jogos</span>");
		$("#maiorInvencibilidade").html("<span>Maior invencibilidade <img src='/images/bandeiras/"+bandeiraPaisA+"' class='bandeira'/><img src='/images/bandeiras/"+bandeiraPaisB+"' class='bandeira'/> "+maiorJejumVitorias.timeB+" jogos</span>");
	} else {
		$("#maiorJejumVitorias").html("");
		$("#maiorInvencibilidade").html("");
	}
	
	//preenchimento gauges
	
	setTimeout(() => { 
	
		var brightness1 = (299*colorA[0] + 587*colorA[1] + 114*colorA[2]) / 1000
		var brightness2 = (299*colorB[0] + 587*colorB[1] + 114*colorB[2]) / 1000
		
		
		
		while(Math.abs(brightness1 - brightness2) < 15 || brightness2 > 200){
			const random = Math.floor(Math.random() * paletteB.length);
			colorB = paletteB[random];
			brightness2 = (299*colorB[0] + 587*colorB[1] + 114*colorB[2]) / 1000
		}

		color1 = "#" + fullColorHex(colorA[0],colorA[1],colorA[2]);
		color2 = "#" + fullColorHex(colorB[0],colorB[1],colorB[2]);
	
		
		$('#gaugeJogos').css({ "--rotation": anguloA2 + "deg", "--rotationa": anguloB2 + "deg" , "--color": color1, "--backgrounda": color2});
		$('#gaugeGols').css({ "--rotation": anguloA + "deg", "--rotationa": anguloB + "deg" , "--color": color1, "--backgrounda": color2});
		$("#vitoriasTimeA").css({color: color1});
		$("#vitoriasTimeB").css({color: color2});
		$("#golsTimeA").css({color: color1});
		$("#golsTimeB").css({color: color2});
	}, 100);
	

	

}


function updateTable(ajax_data, current_page, highlighted, direction){

    var results_per_page = 13;
    var total_results = ajax_data.length;
    var total_pages = Math.ceil(total_results/results_per_page);

    var treated_page;
    if(current_page == 'final'){
        treated_page = total_pages;
    } else if(current_page == 'inicio'){
        treated_page = 1;
    } else {
        treated_page = current_page;
    }

    var from_result_num = (results_per_page * treated_page) - results_per_page;

    var pgn = pagination(treated_page,total_pages);

    //criar tabela dinamicamente
    var tbl = '';
    tbl += pgn;
    tbl += "<hr>";
    tbl += "<table id='tabelajogos' class='table'>";
        tbl += "<thead id='headings'>";
            tbl += "<tr>";
                tbl += "<th asc='' id='nomeA' class='headings' width='24%'><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspTime A</th>";
                tbl +=  "<th asc='' id='timeAgols' class='headings' width='5%'><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspGols</th>";
                tbl +=  "<th asc='' id='timeApenaltis' class='headings' width='5%' class='penaltybox'><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbsp</th>";
                tbl +=  "<th asc='' id='timeBgols' class='headings' width='5%'><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspGols</th>";
                tbl +=  "<th asc='' id='nomeB' class='headings' width='24%'><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspTime B</th>";
                tbl +=  "<th asc='' id='data' class='headings' width='14%'><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspData</th>";
                tbl +=  "<th asc='' id='campeonato' class='headings' width='14%'><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspCampeonato</th>";
                tbl +=  "<th asc='' id='fase' class='headings' width='5%'><i class='ascending fa fa-sort-up hidden'></i><i class='descending fa fa-sort-down hidden'></i>&nbspFase</th>";
            tbl +=  "</tr>";
        tbl +=  "</thead>";
        tbl +=  "<tbody>";

        // criar linhas
        $.each(ajax_data, function(index, val){

            var pen = '';
            if(index>=(from_result_num-1) && index<=(from_result_num+results_per_page-2)){

            if(val['timeApenaltis'] != 0 && val['timeBpenaltis'] != 0){
                pen = "("+val['timeApenaltis']+") pen. ("+val['timeBpenaltis']+")";
            }

            tbl += "<tr id='"+val['id']+"' data-href='match_info.php?match_id="+val['id']+"'>";
                tbl += "<td class='esquerdo nopadding'><img src='/images/bandeiras/"+val['bandeiraA']+"' class='bandeira'>    <a href='./teamstatus.php?team="+val['idA']+"'>"+val['nomeA']+"</a></td>";
                tbl +=  "<td class='nopadding'>"+val['timeAgols']+"</td>";
                tbl +=  "<td class='penaltybox nopadding'>"+pen+"</td>";
                tbl +=  "<td class='nopadding'>"+val['timeBgols']+"</td>";
                tbl +=  "<td class='direito nopadding'><a href='./teamstatus.php?team="+val['idB']+"'>"+val['nomeB']+"</a>    <img src='/images/bandeiras/"+val['bandeiraB']+"' class='bandeira'>  </td>";
                tbl +=  "<td>"+val['data']+"</td>";
                tbl +=  "<td>"+val['campeonato']+"</td>";
                tbl +=  "<td>"+dictFases[val['fase']]+"</td>";
            tbl +=  "</tr>";
            }
        });

        tbl += '</tbody>';
    tbl += '</table>';

    //mostrar dados da tabela
    $(document).find('.tbl_user_data').html(tbl);
    addFilters();

    $(document).find('#'+highlighted).addClass('highlighted');

    if(direction == 1){
        asc = activeDirection;
    }
    if(asc){
        $(document).find('#'+highlighted).find('.descending').addClass('hidden');
        $(document).find('#'+highlighted).find('.ascending').removeClass('hidden');
    } else {
        $(document).find('#'+highlighted).find('.ascending').addClass('hidden');
        $(document).find('#'+highlighted).find('.descending').removeClass('hidden');
    }

    activeSort = highlighted;
    activeDirection = asc;

    $('*[data-href]').on('click', function() {
        window.location = $(this).data("href");
    });
}

$(document).on('click', '.pagination_link', function(){
    var page = $(this).attr('id');
    updateTable(localData, page,activeSort, 1);
});


function pagination(current_page, total_pages){
var pgn = '';
pgn += "<ul class='pagination'>";

// button for first page
if(current_page>1){
    pgn +=  "<li><button class='pagination_link' id='inicio' title='Ir para o início'>";
    pgn +=  "Inicio";
    pgn +=  "</button></li>";
}

// range of links to show
const range = 2;

// display links to 'range of pages' around 'current page'
var initial_num = current_page - range;
var condition_limit_num = (+current_page + +range)  + +1;

// teste com While
var x;
if(initial_num > 0){
    x = initial_num;
} else {
    x = 1;
}

while(x <= total_pages && x < condition_limit_num){
    if (x == current_page) {
            pgn += "<li><button class='pagination_link' id='"+x+"' disabled>"+x+"<span class=\"sr-only\">(current)</span></button></li>";
        }
        else {
            pgn += "<li><button class='pagination_link' id='"+x+"'>"+x+"</button></li>";
        }
    x = x+1;
}

// button for last page
if(current_page<total_pages){
    pgn += "<li><button class='pagination_link' id='final' title='Última página é "+total_pages+".'>";
    pgn += "Final";
    pgn += "</button></li>";
}

pgn += "</ul>";

return pgn;
}

$(".toggleButton").click(function() {

var modalType = $(this).attr("id");

if(modalType !== 'retornar'){
    $(".modalOverlay").show();
    $(".moreInfoModal").show();
    $("#modal"+modalType).show();
    $('#retornar').show();
} else {
    $(".modalOverlay").hide();
    $(".moreInfoModal").hide();
    $(".modal-guts").hide();
    $('#retornar').hide();
}
});

$('.modalOverlay').click(function(e){
    $('*[id*=odal]').each(function() {
    $(this).hide();
    $('#retornar').hide();
    });
});

function addFilters(){
    $(document).find('.headings').click(function(){
       treatResults(this);


    });
}

function treatResults(item){
    var id = $(item).attr('id');

    sortResults(id, asc);

    if(asc){
        asc = false;
    } else {
        asc = true;
    }

}

function sortResults(prop, asc) {

if(prop == 'pontos'){

    localData = localData.sort(
        function(a,b){
            if (asc) return a[prop] - b[prop];
            if (!asc) return b[prop] - a[prop];
            else return 0;
        }
    );
} else {
    localData = localData.sort(
        function(a, b) {
            if (((a[prop] < b[prop]) && (!asc))||((a[prop] > b[prop]) && (asc))) return 1;
            else if (((a[prop] > b[prop]) && (!asc))||((a[prop] < b[prop]) && (asc))) return -1;
            else return 0;
        }
    );
}


    updateTable(localData, 1,prop,0);

    }

});




</script>

<?php

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
