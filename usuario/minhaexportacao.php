<?php

session_start();

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Exportação de database HYMT - ".$_SESSION['nomereal'];
$css_filename = "indexRanking";
$aux_css = "usuario";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){

   //coletar opções do usuário, caso existam

   //estabelecer conexão com banco de dados
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/liga.php");

$database = new Database();
$db = $database->getConnection();

$liga = new Liga($db);
$pais = new Pais($db);

?>


<div id="quadro-container">
<div align="center" id="quadroTimes">
<button id='importar_time'  >Exportar database</button>
<h2>Exportação de database HYMT - <?php echo $_SESSION['nomereal']?></h2>

<div style='clear:both;'></div>


<?php

// query caixa de seleção países desse dono
$stmtPais = $pais->read($_SESSION['user_id']);

// query caixa de seleção ligas desse dono
$stmtLiga = $liga->read($_SESSION['user_id']);


echo "<hr>";
echo "<div id='errorbox'></div>";
echo "<div id='inputsExportacao'>";
echo '<label for="todosPaises">Todos os países&nbsp;&nbsp;<input type="checkbox" id="todosPaises" name="todosPaises" value="todospaises"></label>';


echo "<select multiple class='comboPaises form-control' id='paises' name='comboPaises[]'>";

	while ($row_pais = $stmtPais->fetch(PDO::FETCH_ASSOC)){
		extract($row_pais);
		echo "<option value='{$id}'>{$nome}</option>";
	}
	

echo "</select>";

echo '<label for="todasLigas">Todas as ligas&nbsp;&nbsp;<input type="checkbox" id="todasLigas" name="todasLigas" value="todasligas"></label>';

echo "<select multiple class='comboLigas form-control' id='ligas' name='comboLigas[]'>";

	while ($row_liga = $stmtLiga->fetch(PDO::FETCH_ASSOC)){
		echo "<option pais-liga='{$row_liga['Pais']}' value='{$row_liga['id']}'>{$row_liga['nome']}</option>";
	}
	

echo "</select>";

echo "<select class='comboOpcoes form-control' id='opcoes' name='comboOpcoes[]'>";
	echo "<option value='0'>Pacote completo (Hexacolor 2.12)</option>";
	echo "<option value='1'>database.db3 e imagens</option>";
	echo "<option value='2'>Apenas database.db3</option>";
echo "</select>";

echo "</div>";


echo('</div>');
echo('</div>');

?>

<script>

$(document).ready(function() {
    //set initial state.
	$('#todosPaises').prop("checked", "checked");
	$('#todasLigas').prop("checked", "checked");
	selectCountries(true, true);
	
	function selectCountries(isCheckedLeagues, isCheckedCountries){
		if(isCheckedCountries){
			$('#paises option').prop('selected', true);
			$('#paises').prop('disabled', 'disabled');
			$('#paises').trigger("change");
			selectLeagues(isCheckedLeagues, isCheckedCountries);
		} else {
			$('#paises option').prop('selected', false);
			$('#paises').prop('disabled', false);
			$('#paises').trigger("change");
			selectLeagues(isCheckedLeagues, isCheckedCountries);
		}
	}
	
	function selectLeagues(isCheckedLeagues, isCheckedCountries){
		if(isCheckedCountries && isCheckedLeagues){
			$('#ligas option').prop('selected', true);
			$('#ligas').prop('disabled', 'disabled');
		} else if(isCheckedLeagues) { 
			$('#ligas').prop('disabled', 'disabled');
			selectedCountries = $('#paises').val();
			$("#ligas > option").each(function() {
				let pais_liga = $(this).attr("pais-liga");
				if(selectedCountries.includes(pais_liga)){
					$(this).prop('selected', true);
				} else {
					$(this).prop('selected', false);
				}
			});
		} else {
			$('#ligas option').prop('selected', false);		
			$('#ligas').prop('disabled', false);
		}

	}


    $('#todosPaises').change(function() {
        let isCheckedCountries = this.checked;
		let isCheckedLeagues = $("#todasLigas").prop("checked");
		selectCountries(isCheckedLeagues, isCheckedCountries);
    });
	
	$('#todasLigas').change(function() {
		let isCheckedCountries = $("#todosPaises").prop("checked");
		let isCheckedLeagues = this.checked;
		selectLeagues(isCheckedLeagues, isCheckedCountries);
		
    });
	
	
	$('#paises').change(function() {
        selectedCountries = $(this).val();
		$("#ligas > option").each(function() {
			let pais_liga = $(this).attr("pais-liga");
			if(selectedCountries.includes(pais_liga)){
				$(this).show();
			} else {
				$(this).hide();
			}
		});
		let isCheckedLeagues = $("#todasLigas").prop("checked");
		selectLeagues(isCheckedLeagues, false);
    });
	
	var $loading = $('#loadingDiv').hide();
	$(document)
		.ajaxStart(function () {
			$('html, body').css("cursor", "wait");
		})
		.ajaxStop(function () {
			$('html, body').css("cursor", "pointer");
		});
	
	$("#importar_time").on("click",function(){
		let paisesSelecionados = $('#paises').val();
		let ligasSelecionadas = $('#ligas').val();
		let opcaoSelecionada = $('#opcoes').val();
		
		let ligaPais = [];
		$('#ligas option:selected').each(function() {
			let paisSelecionado = $(this).attr("pais-liga");
			let ligaSelecionada = $(this).val();
			ligaPais.push({pais: paisSelecionado, liga: ligaSelecionada});
		});
		
		ligaPais.sort(function (a, b) {
			if (a.pais > b.pais) {
				return 1;
			}
			if (a.pais < b.pais) {
				return -1;
			}
			// a must be equal to b
				return 0;
		});
		
		var groupBy = function(xs, key) {
			return xs.reduce(function(rv, x) {
				(rv[x[key]] = rv[x[key]] || []).push(x);
				return rv;
			}, {});
		};

		ligaPais = groupBy(ligaPais, 'pais');
		
		//console.log(ligaPais);

		var formData = {
			'ligasSelecionadas' : ligasSelecionadas
		}
		
		if(paisesSelecionados.length == 0 || ligasSelecionadas.length == 0) {
			$("#errorbox").empty();
			$('#errorbox').append("<div class='alert alert-danger'>Nenhuma liga selecionada!<div>");
			return false;
		}
	
		$.ajax({
			type: 'POST',
			url: 'verificar_exportacao.php', // the url where we want to POST
			data: formData,
			dataType: 'json',
			encode: true
		}).done(function(response) {
			if(response.success){
				$("#errorbox").empty();
				$('#errorbox').append("<div class='alert alert-success'>Banco de dados verificado, a exportação iniciará em instantes! Aguarde.</div>");
				$('#exportar_hymt').addClass('disabled');
				$('html, body').css("cursor", "wait");
				optionString = opcaoSelecionada.toString();
				urlToOpen = 'exportar_database_imp3.php?data=' + encodeURIComponent(JSON.stringify(ligaPais)) + '&option=' + optionString;
				if(urlToOpen.length < 2000){
					window.location = urlToOpen;
				} else {
					$("#errorbox").empty();
					$('#errorbox').append("<div class='alert alert-danger'>Selecione um número menor de ligas</div>");
				}
				

			} else {
				$("#errorbox").empty();
				$('#errorbox').append("<div class='alert alert-danger'>Banco de dados não pode ser exportado pelos seguintes motivos:</br>"+response.errors+"</div>");
			}
		}).fail(function(response) {
			$("#errorbox").empty();
			$('#errorbox').append("<div class='alert alert-danger'>Houve um erro não esperado na exportação dos dados, por favor contacte o admin.<div>");
        });

	});

});

</script>

<?php

} else {
    echo "Usuário, por favor refaça o login.";
}

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
