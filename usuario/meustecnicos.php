<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Meus técnicos - ".$_SESSION['nomereal'];
$css_filename = "indexRanking";
$aux_css = "usuario";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){
	
	//estabelecer conexão com banco de dados
	include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/tecnico.php");

	$database = new Database();
	$db = $database->getConnection();
	
	$usuario = new Usuario($db);
	$time = new Time($db);
	$pais = new Pais($db);
	$tecnico = new Tecnico($db);
	
	// query caixa de seleção países desse dono
	$stmtPais = $pais->read($_SESSION['user_id']);
	$listaPaises = array();
	while ($row_pais = $stmtPais->fetch(PDO::FETCH_ASSOC)){
		extract($row_pais);
		$addArray = array($id, $nome);
		$listaPaises[] = $addArray;
	}
?>

<script>

var localData = [];
var asc = true;
var activeSort = '';
var activeDirection = true;

var listaPaises =  <?php echo json_encode($listaPaises); ?>;
var listaMentalidades = [
    [1, "Retranca"],
    [2, "Defensiva"],
    [3, "Balanceada"],
    [4, "Ofensiva"],
    [5, "Ataque Total"]
];
var listaEstilos = [
    [1, "Explorar contra-ataques"],
    [2, "Cadenciar o jogo"],
    [3, "Neutro"],
    [4, "Atacar pelas laterais"],
    [5, "Impôr ritmo ofensivo"]
];

var logged ='<?php if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){
	echo "true";
 } else {
	echo "false";
 };?>';
 
var admin ='<?php if(isset($_SESSION['admin_status']) && $_SESSION['admin_status'] == 1){
	echo "true";
 } else {
	echo "false";
 };?>';

var user_id ='<?php if(isset($_SESSION['user_id']) ){
	echo $_SESSION['user_id'];
 } else {
	echo $_SESSION['user_id'];
 };?>';

$(document).ready(function($){
 
	function selectElement(id, valueToSelect) {    
		let element = document.getElementById(id);
		element.value = valueToSelect;
	}
 
	load_data();

	function delay(fn, ms){
		let timer = 0;
		return function(...args){
			clearTimeout(timer)
			timer = setTimeout(fn.bind(this, ...args), ms || 0)
		}
	}

	$('#caixa_pesquisa').keyup(delay(function(e){
		load_data();
	},800));

	function load_data(){
		var searchText = $('#caixa_pesquisa').val();
		$('#loading').show();

		$.ajax({
			url:"search_coach.php",
			method:"POST",
			cache:false,
			data:{searchText:searchText},
			success:function(data){
				$('#loading').hide();
				updateTable(JSON.parse(data),1,0,0);
				localData = JSON.parse(data);
			}
		});
	}
 
	function updateTable(ajax_data, current_page, highlighted, direction){

		var results_per_page = 18;
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

		var tbl = '';
		tbl += pgn;
		tbl += "<hr>";
		tbl += "<table id='tabelaPrincipal' class='table'>";
			tbl += "<thead id='headings"+user_id+"'>";
				tbl += "<tr>";
					tbl += "<th asc='' class='headings' width='5%'>Foto</th>";
					tbl += "<th asc='' class='headings' width='30%'><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbspNome</th>";
					tbl += "<th asc='' class='headings' width='20%'>Nascimento</th>";
					tbl += "<th asc='' class='headings' width='10%'><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbspNível</th>";
					tbl += "<th asc='' class='headings' width='10%'>Mentalidade</th>";
					tbl += "<th asc='' class='headings' width='10%'>Estilo</th>";
					tbl += "<th asc='' class='headings' width='20%' class='wide'><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbspPaís</th>";
					tbl += "<th asc='' class='headings'>Clube</th>";
					tbl += "<th width='20%' class='wide'>Opções</th>";
				tbl += "</tr>";
			tbl +=  "</thead>";
			tbl +=  "<tbody>";

			$.each(ajax_data, function(index, val){

				if(index>=(from_result_num-1) && index<=(from_result_num+results_per_page-2)){
				
					let genderCode = ""
					let genderClass = ""
					if(val['Sexo'] == 0){
						genderCode = "M";
						genderClass = "genderMas";
					} else {
						genderCode = "F";
						genderClass = "genderFem";
					}
					
					var options = { year: 'numeric', month: '2-digit', day: '2-digit'};
					var dataNascimento = new Date(val['Nascimento'].replace(/-/g, '\/'));
					var nascimentoDisplay = dataNascimento.toLocaleDateString("pt-BR", options);
					
					tbl += "<tr id='"+val['ID']+"' data-sexo='"+val['Sexo']+"' data-dono-pais='"+val['idDonoPais']+"' >";
						tbl += "<td><div class='imageUpload'><img class='playerThumb' src='/images/tecnicos/"+val['foto']+"' /> <input type='file' hidden id='foto"+val['ID']+"' class='hiddenInput custom-file-upload' name='foto' accept='.jpg,.png,.jpeg,.webp'/></div></td>";
						tbl += "<td><span class='nomeEditavel' id='nom"+val['ID']+"'>"+val['Nome']+"</span><span class=' "+genderClass+" genderSign'>"+genderCode+"</span></td>";
						tbl += "<td><span class='nomeNascimento' id='nas"+ val['ID']+"'>"+ nascimentoDisplay + " (" +val['idade']+") "+" </span><input id='selnas"+val['ID']+"' class='nascimentoEditavel editavel' type='date' value='"+val['Nascimento']+"' hidden/></td>";
						tbl += "<td><span class='nivelEditavel' id='niv"+val['ID']+"'>"+val['Nivel']+"</span></td>";

						tbl += "<td class='wide'>";
						tbl += "<select disabled class='comboMentalidade transpBack' id='"+val['Mentalidade']+"'>";
							listaMentalidades.forEach(function(value, key){
								tbl += "<option "+(val['Mentalidade'] == value[0] ? "selected" : "")+" value='"+value[0]+"'>"+value[1]+"</option>";
							});
						tbl += "</select>";	
						tbl += "</td>";

						tbl += "<td class='wide'>";
						tbl += "<select disabled class='comboEstilo transpBack' id='"+val['Estilo']+"'>";
							listaEstilos.forEach(function(value, key){
								tbl += "<option "+(val['Estilo'] == value[0] ? "selected" : "")+" value='"+value[0]+"'>"+value[1]+"</option>";
							});
						tbl += "</select>";	
						tbl += "</td>";

						tbl += "<td class='wide'><img src='/images/bandeiras/"+val['bandeiraPais']+"' class='bandeira nomePais' id='ban"+val['ID']+"'> <span class='nomePais' id='pai"+val['ID']+"'>"+val['siglaPais']+"</span>";
						tbl += "<select class='comboPais editavel' id='"+val['idPais']+"' hidden>";
							listaPaises.forEach(function(value, key){
								tbl += "<option value='"+value[0]+"'>"+value[1]+"</option>";
							});
						tbl += "</select>";
						tbl += "</td>";
						
						if(val['clubeVinculado'] != null){
							tbl += "<td><a href='/ligas/teamstatus.php?team="+val['idClubeVinculado']+"' id='dis"+val['ID']+"'><img class='minithumb' src='/images/escudos/"+val['escudoClubeVinculado']+"'>"+val['clubeVinculado']+"</a><span class='donoClubeVinculado' hidden>"+val['donoClubeVinculado']+"</span></td>";
						} else {
							tbl += "<td></td>";
						}
						
						let optionsString = "<td class='wide'>";
						if(logged == "true"){
							optionsString += "<a id='edi"+val['ID']+"' title='Editar técnico' class='clickable editar'><span class='material-symbols-outlined inlineButton'>edit</span></a>";
							optionsString += "<a hidden id='sal"+val['ID']+"' title='Salvar' class='clickable salvar'><span class='material-symbols-outlined inlineButton positive'>check</span></a>";
							optionsString += "<a hidden id='can"+val['ID']+"' title='Cancelar' class='clickable cancelar'><span class='material-symbols-outlined inlineButton negative'>close</span></a>";
						}
						optionsString += "</td>";
						tbl += optionsString;

					tbl += "</tr>";
				}
			});

			tbl += '</tbody>';
		tbl += '</table>';
		
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
		
		$(".editar").on("click", function(){
			var tbl_row = $(this).closest("tr");

			tbl_row.find('a').each(function(index, val){
				$(this).attr('original_entry', $(this).html());
			});

			tbl_row.find('span').each(function(index, val){
				$(this).attr('original_entry', $(this).html());
			});

			tbl_row.find('input').each(function(index, val){
				$(this).attr('data-original-entry', $(this).val());
			});

			tbl_row.find(".salvar").show();
			tbl_row.find(".cancelar").show();
			tbl_row.find(".editar").hide();
			tbl_row.find('.hiddenInput').show();

			var donoTime = tbl_row.find(".donoClubeVinculado").html();
			var donoJogador = $("#tabelaPrincipal").find('thead').prop("id").replace(/\D/g, "");
			var donoPais = tbl_row.attr("data-dono-pais");

			if (typeof donoTime === 'undefined'){
				donoTime = donoJogador;
			}

			if(donoTime.localeCompare(donoJogador) == 0 || donoTime == 0 || (typeof donoPais !== 'undefined' && donoPais.localeCompare(donoJogador) == 0)){
				var isDono = true;
			} else {
				var isDono = false;
			}

			if(isDono){
				tbl_row.find('.nomeEditavel').attr('contenteditable', 'true').addClass('editavel');
				tbl_row.find('.nomePais').hide();

				var paisId = tbl_row.find('.comboPais').attr('id');
				tbl_row.find('.comboPais').show().val(paisId);

				tbl_row.find('.comboEstilo').removeClass('transpBack');
				tbl_row.find('.comboEstilo').prop('disabled', false);
				tbl_row.find('.comboEstilo').addClass('editavel');

				tbl_row.find('.comboMentalidade').removeClass('transpBack');
				tbl_row.find('.comboMentalidade').prop('disabled', false);
				tbl_row.find('.comboMentalidade').addClass('editavel');

				tbl_row.find('.nomeNascimento').hide();
				tbl_row.find('.nascimentoEditavel').show();
			}

			tbl_row.find('.nivelEditavel').attr('contenteditable', 'true').addClass('editavel');
		});

		$(".cancelar").on("click", function(){
			var tbl_row = $(this).closest("tr");
			tbl_row.find('.nomeEditavel').attr('contenteditable', 'false').removeClass('editavel');
			tbl_row.find('.nivelEditavel').attr('contenteditable', 'false').removeClass('editavel');
			tbl_row.find('.nomeNascimento').show();
			tbl_row.find('.nascimentoEditavel').hide();
			tbl_row.find('.comboPais').hide();
			tbl_row.find('.nomePais').show();
			tbl_row.find('.salvar').hide();
			tbl_row.find('.cancelar').hide();
			tbl_row.find('.editar').show();
			tbl_row.find('.hiddenInput').hide();

			tbl_row.find('a').each(function(index, val){
				$(this).html($(this).attr('original_entry'));
			});

			tbl_row.find('span').each(function(index, val){
				$(this).html($(this).attr('original_entry'));
			});

			tbl_row.find('input').each(function(index, val){
				$(this).val($(this).attr('data-original-entry'));
			});

			var estilo = tbl_row.find('.comboEstilo').attr('id').replace(/\D/g, "");
			var mentalidade = tbl_row.find('.comboMentalidade').attr('id').replace(/\D/g, "");
			tbl_row.find('.comboEstilo').addClass('transpBack');
			tbl_row.find('.comboEstilo').prop('disabled', 'disabled');
			tbl_row.find('.comboEstilo').removeClass('editavel');
			tbl_row.find('.comboEstilo').val(estilo);

			tbl_row.find('.comboMentalidade').addClass('transpBack');
			tbl_row.find('.comboMentalidade').prop('disabled', 'disabled');
			tbl_row.find('.comboMentalidade').removeClass('editavel');
			tbl_row.find('.comboMentalidade').val(mentalidade);
		});

		$(".salvar").on("click", function(){
			var tbl_row = $(this).closest("tr");
			tbl_row.find('.nomeEditavel').attr('contenteditable', 'false').removeClass('editavel');
			tbl_row.find('.nivelEditavel').attr('contenteditable', 'false').removeClass('editavel');
			tbl_row.find('.nomeNascimento').show();
			tbl_row.find('.nascimentoEditavel').hide();
			tbl_row.find('.comboPais').hide();
			tbl_row.find('.nomePais').show();
			tbl_row.find('.salvar').hide();
			tbl_row.find('.cancelar').hide();
			tbl_row.find('.editar').show();
			tbl_row.find('.hiddenInput').hide();

			tbl_row.find('.comboEstilo').addClass('transpBack');
			tbl_row.find('.comboEstilo').prop('disabled', 'disabled');
			tbl_row.find('.comboEstilo').removeClass('editavel');

			tbl_row.find('.comboMentalidade').addClass('transpBack');
			tbl_row.find('.comboMentalidade').prop('disabled', 'disabled');
			tbl_row.find('.comboMentalidade').removeClass('editavel');

			var id = tbl_row.attr('id');

			var donoTime = tbl_row.find(".donoClubeVinculado").html();
			var donoJogador = $("#tabelaPrincipal").find('thead').prop("id").replace(/\D/g, "");
			var donoPais = tbl_row.attr("data-dono-pais");

			if (typeof donoTime === 'undefined'){
				donoTime = donoJogador;
			}

			if(donoTime.localeCompare(donoJogador) == 0 || donoTime == 0 || (typeof donoPais !== 'undefined' && donoPais.localeCompare(donoJogador) == 0)){
				var isDono = true;
			} else {
				var isDono = false;
			}

			var formData = new FormData();

			if(isDono){
				var nome = tbl_row.find('.nomeEditavel').html();
				var nascimento = tbl_row.find(".nascimentoEditavel").val();
				var pais = tbl_row.find('.comboPais').val();
				var estilo = tbl_row.find('.comboEstilo').val();
				var mentalidade = tbl_row.find('.comboMentalidade').val();

				formData.append('pais', pais);
				formData.append('estilo', estilo);
				formData.append('mentalidade', mentalidade);
				formData.append('nascimento', nascimento);
				formData.append('nome', nome);
			}

			var inputFoto = (tbl_row.find('#foto'+id))[0];
			var foto;

			if (inputFoto.files.length > 0) {
				foto = inputFoto.files[0];
			} else {
				foto = null;
			}
			
			if(foto != null){
				formData.append('foto',foto);
			}

			var nivel = tbl_row.find(".nivelEditavel").html();
			var alteracao = 9;

			formData.append('idTecnico', id);
			formData.append('nivel', nivel);
			formData.append('alteracao', alteracao);

			$.ajax({
				url: '/ligas/editar_tecnico.php',
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
					load_data();
				},
				error: function(data) {
					alert("Erro, o procedimento não foi realizado, tente novamente.");
					load_data();
				}
			});
		});
	}

	$(document).on('click', '.pagination_link', function(){
		var page = $(this).attr('id');
		updateTable(localData, page, activeSort, 1);
	});

	function pagination(current_page, total_pages){
		var pgn = '';
		pgn += "<ul class='pagination'>";

		if(current_page>1){
			pgn += "<li><button class='pagination_link' id='inicio' title='Ir para o início'>";
			pgn += "Inicio";
			pgn += "</button></li>";
		}

		const range = 2;
		var initial_num = current_page - range;
		var condition_limit_num = (+current_page + +range) + +1;

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

		if(current_page<total_pages){
			pgn += "<li><button class='pagination_link' id='final' title='Última página é "+total_pages+".'>";
			pgn += "Final";
			pgn += "</button></li>";
		}

		pgn += "</ul>";
		return pgn;
	}

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
		localData = localData.sort(
			function(a, b) {
				if (((a[prop] < b[prop]) && (!asc))||((a[prop] > b[prop]) && (asc))) return 1;
				else if (((a[prop] > b[prop]) && (!asc))||((a[prop] < b[prop]) && (asc))) return -1;
				else return 0;
			}
		);
		updateTable(localData, 1, prop, 0);
	}
});

</script>

<div id="quadro-container">
<div align="center" id="quadroTimes">
<div id='search_wrapper'><input type=text id='caixa_pesquisa' placeholder='Pesquisar...'><i class='fas fa-search'></i></div>
<button id='importar_time' onclick="window.location='/ligas/criar_tecnico.php';">Criar técnico</button>
<button id='importar_time' onclick="window.location='/import/importar_tecnico.php';">Importar técnico</button>
<h2>Quadro de técnicos - <?php echo $_SESSION['nomereal']?></h2>
<div id='error_box'></div>

<hr>

<div style='clear:both;'></div>
<div class='tbl_user_data'><img id='loading' src='/images/icons/ajax-loader.gif'></div>

</div>
</div>

<?php
} else {
    echo "Usuário, por favor refaça o login.";
}

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
