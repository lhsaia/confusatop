<?php

// ini_set( 'display_errors', true );
// error_reporting( E_ALL );
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

	$idCompeticao = isset($_GET['id']) ? $_GET['id'] : 0;

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Lista de Jogos - ".($_SESSION['nomereal'] ?? '');
$css_filename = "home_redesign";
$aux_css = "lista_jogos_redesign";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){
	
	include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/config/sqliteDatabase.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/arbitros.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/estadio.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogador.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/competicao_clube.php");

	$database = new Database();
	$db = $database->getConnection();
	
	$compDatabase = new SQLiteDatabase();
	$compDatabase->fileName = $_SERVER['DOCUMENT_ROOT']."/competicoes/databases/".$idCompeticao."-database.db3";
	$cdb = $compDatabase->getConnection();
	
	$usuario = new Usuario($db);
	$time = new Time($cdb);
	$arbitro = new TrioArbitragem($cdb);
	$estadio = new Estadio($cdb);
	$jogador = new Jogador($db);
	$pais = new Pais($db);
	$competicao = new Competicao_clube($db);
	
	$options = $competicao->getOptions($idCompeticao);
	$compInfo = $competicao->readInfo($idCompeticao);
	$donoCompeticao = isset($compInfo['dono']) ? (int)$compInfo['dono'] : 0;
	
	// query caixa de seleção países desse dono
	$stmtPais = $pais->read();
	$listaPaises = array();
	while ($row_pais = $stmtPais->fetch(PDO::FETCH_ASSOC)){
		extract($row_pais);
		$addArray = array($id, $sigla);
		$listaPaises[] = $addArray;
	}
	
		// query caixa de seleção de posições
	$stmt = $competicao->lerFases();
	$listaFases = array();
	while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
		extract($row);
		$addArray = array($id, $nome);
		$listaFases[] = $addArray;
	}
	
	//query lista times do SQLite da competição
	$stmt = $time->carregarListaTimesSqlite();
	$listaTimes = array();
	if($stmt){
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
			extract($row);
			$addArray = array($ID, $Nome);
			$listaTimes[] = $addArray;
		}
	}
	// Fallback para MariaDB se $listaTimes estiver vazio
	if(empty($listaTimes)){
		include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");
		$timeMaria = new Time($db);
		$stmtMaria = $timeMaria->read(null, false);
		if($stmtMaria){
			while ($rowM = $stmtMaria->fetch(PDO::FETCH_ASSOC)){
				$listaTimes[] = array($rowM['id'], $rowM['nome']);
			}
		}
	}
	
	//query lista árbitros
	$stmt = $arbitro->carregarListaArbitrosSqlite();
	$listaArbitros = array();
	while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
		extract($row);
		$addArray = array($ID, $Arbitro, $Auxiliar1, $Auxiliar2, $Estilo);
		$listaArbitros[] = $addArray;
	}
	
	//query lista estádios
	$stmt = $estadio->carregarListaEstadiosSqlite();
	$listaEstadios = array();
	while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
		extract($row);
		$addArray = array($ID, $Nome);
		$listaEstadios[] = $addArray;
	}
	
	echo '<div class="bg"></div><div class="bg bg2"></div><div class="bg bg3"></div>';
?>

<style>
#toolbar {
  display: flex !important;
  gap: 10px !important;
  align-items: center !important;
  height: 100% !important;
  margin: 0 !important;
  padding: 0 !important;
}

#gerar_tabela, #exportar_excel, #importar_excel {
  border: none !important;
  color: #ffffff !important;
  border-radius: 8px !important;
  padding: 6px 14px !important;
  font-weight: 600 !important;
  font-family: 'Kanit', sans-serif !important;
  display: inline-flex !important;
  align-items: center !important;
  gap: 6px !important;
  transition: all 0.2s ease !important;
  margin: 0 !important;
  cursor: pointer !important;
  height: 32px !important;
  box-sizing: border-box !important;
  line-height: 1 !important;
}

/* Estilo do Ícone (tamanho padrão idêntico ao criar competição) */
#toolbar > div span.material-symbols-outlined {
  padding-left: 0 !important;
  font-size: 1.15rem !important;
  display: inline-block !important;
  vertical-align: middle !important;
}

/* Estilo do Texto (tamanho padrão de botão para manter harmonia) */
#toolbar > div span.btn-text {
  font-size: 0.85rem !important;
  font-family: 'Kanit', sans-serif !important;
  font-weight: 600 !important;
  display: inline-block !important;
  vertical-align: middle !important;
}

#gerar_tabela {
  background: #0284c7 !important;
  box-shadow: 0 2px 8px rgba(2, 132, 199, 0.25) !important;
}
#gerar_tabela:hover {
  background: #0369a1 !important;
  transform: translateY(-1px) !important;
  box-shadow: 0 4px 12px rgba(2, 132, 199, 0.35) !important;
}

#exportar_excel {
  background: #16a34a !important;
  box-shadow: 0 2px 8px rgba(22, 163, 74, 0.25) !important;
}
#exportar_excel:hover {
  background: #15803d !important;
  transform: translateY(-1px) !important;
  box-shadow: 0 4px 12px rgba(22, 163, 74, 0.35) !important;
}

#importar_excel {
  background: #0d9488 !important;
  box-shadow: 0 2px 8px rgba(13, 148, 136, 0.25) !important;
}
#importar_excel:hover {
  background: #0f766e !important;
  transform: translateY(-1px) !important;
  box-shadow: 0 4px 12px rgba(13, 148, 136, 0.35) !important;
}

/* Responsividade: oculta o texto dos botões em telas menores que 1500px para nunca sobrepor o login */
@media (max-width: 1500px) {
  #toolbar > div span.btn-text {
    display: none !important;
  }
  #toolbar > div {
    padding: 6px 10px !important;
    width: 32px !important;
    justify-content: center !important;
  }
}
</style>

<script>

$(document).ready(function($){

	var loadingInterval;
	function showLoading(isSimulation) {
		if (isSimulation) {
			$('#loading-step').show();
			$('#loading-bar-container').show();
			$('#loading-title').text("Simulando Partida");
			startLoadingProgress();
		} else {
			$('#loading-step').hide();
			$('#loading-bar-container').hide();
			$('#loading-title').text("Carregando Dados");
		}
		$('#loading').css('display', 'flex').hide().fadeIn(200);
	}
	
	function hideLoading() {
		clearInterval(loadingInterval);
		$('#loading-bar').css('width', '100%');
		setTimeout(function() {
			$('#loading').fadeOut(200);
		}, 200);
	}
	
	function startLoadingProgress() {
		var steps = [
			{ time: 0, text: "Preparando conexão com o banco de dados...", progress: 15 },
			{ time: 1000, text: "Carregando elencos e táticas das equipes...", progress: 30 },
			{ time: 2200, text: "Verificando departamento médico e suspensões...", progress: 45 },
			{ time: 3500, text: "Inicializando o motor de simulação Hexacolor...", progress: 65 },
			{ time: 5500, text: "Processando lances, gols e cartões em tempo real...", progress: 80 },
			{ time: 7000, text: "Finalizando simulação e consolidando a súmula...", progress: 95 }
		];
		
		var stepIndex = 0;
		$('#loading-step').text(steps[0].text);
		$('#loading-bar').css('width', steps[0].progress + '%');
		
		clearInterval(loadingInterval);
		loadingInterval = setInterval(function() {
			stepIndex++;
			if (stepIndex < steps.length) {
				$('#loading-step').text(steps[stepIndex].text);
				$('#loading-bar').css('width', steps[stepIndex].progress + '%');
			} else {
				clearInterval(loadingInterval);
			}
		}, 1200);
	}

	 var listaTimes =  <?php echo json_encode($listaTimes); ?>;
	 var listaFases =  <?php echo json_encode($listaFases); ?>;
	 var listaArbitros =  <?php echo json_encode($listaArbitros); ?>;
	 var listaEstadios =  <?php echo json_encode($listaEstadios); ?>;

	 var codigo_competicao = '<?php echo $idCompeticao ?>';
	 var localData = [];
	 var currentPage = 1;
	 var logged ='<?php echo (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true) ? "true" : "false"; ?>';
	 var admin ='<?php echo ((isset($_SESSION['admin_status']) && $_SESSION['admin_status'] == 1) || (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $donoCompeticao)) ? "true" : "false"; ?>';
	 var user_id ='<?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0; ?>';
	 
	$("#new_match").hide();
	$('body').append('<input type="file" id="excel_file_input" style="display:none;" accept=".xls,.xlsx"/>');
	 
	let btnHtml = '<div id="gerar_tabela"><span class="material-symbols-outlined">grid_view</span><span class="btn-text">Gerar Tabela</span></div>';
	btnHtml += '<div id="exportar_excel"><span class="material-symbols-outlined">download</span><span class="btn-text">Exportar Excel</span></div>';
	btnHtml += '<div id="importar_excel"><span class="material-symbols-outlined">upload</span><span class="btn-text">Importar Excel</span></div>';
	$("#toolbar").html(btnHtml);

	$(document).on('click', '#exportar_excel', function(){
		window.location.href = 'exportar_excel.php?id=' + codigo_competicao;
	});

	$(document).on('click', '#importar_excel', function(){
		$('#excel_file_input').trigger('click');
	});

	$(document).on('change', '#excel_file_input', function(){
		var file_data = $('#excel_file_input').prop('files')[0];
		if (!file_data) return;
		
		var form_data = new FormData();
		form_data.append('planilha_excel', file_data);
		form_data.append('id', codigo_competicao);
		
		showLoading();
		$.ajax({
			url: 'importar_excel.php',
			dataType: 'json',
			cache: false,
			contentType: false,
			processData: false,
			data: form_data,
			type: 'post',
			success: function(response){
				hideLoading();
				$('#excel_file_input').val('');
				if(response.success){
					alert(response.message);
					load_data();
				} else {
					alert(response.error);
				}
			},
			error: function(xhr, status, error){
				hideLoading();
				$('#excel_file_input').val('');
				alert('Erro ao enviar arquivo para importação: ' + error);
			}
		});
	});
	 
	$(document).on('click', '#gerar_tabela', function(){
		if(confirm("Deseja gerar a tabela de jogos? Isso apagará os jogos atuais desta competição!")){
			showLoading();
			$.ajax({
				url: "gerar_tabela.php",
				method: "POST",
				data: {id: codigo_competicao, tipo: '<?php echo isset($options['tipocompeticao']) ? (int)$options['tipocompeticao'] : 0; ?>'},
				success: function(data){
					hideLoading();
					try {
						let res = typeof data === 'object' ? data : JSON.parse(data);
						if(res.success){
							alert("Sucesso! Foram gerados " + (res.total_jogos || 0) + " jogos para a competição.");
							location.reload();
						} else {
							alert("Erro ao gerar tabela: " + res.error);
						}
					} catch(err) {
						console.error("Resposta inválida do gerar_tabela:", data);
						alert("Erro na resposta do servidor: " + data);
					}
				},
				error: function(xhr, status, error){
					hideLoading();
					alert("Erro de conexão ao gerar tabela: " + error);
				}
			});
		}
	});
	 
	function selectElement(id, valueToSelect) {    
		let element = document.getElementById(id);
		element.value = valueToSelect;
	}
	 
	load_data();

	function load_data(){

	showLoading();  // show loading indicator

	$.ajax({
		url:"refresh_match_list.php",
		method:"POST",
		cache:false,
		data:{codigo_competicao:codigo_competicao},
		success:function(data){
			console.log("API de Jogos retornou:", data);
			hideLoading();
			try {
				var ajax_data = JSON.parse(data);
				console.log("Total de jogos processados:", ajax_data.length);
				updateTable(ajax_data, currentPage);
				localData = ajax_data;
			} catch(e) {
				console.error("Erro ao processar JSON de jogos:", e);
			}
		}
	});
	}
	
	
	function updateTable(ajax_data, current_page){
		console.log("Iniciando updateTable com " + ajax_data.length + " jogos.");

		var results_per_page = 18;
		var total_results = ajax_data.length;
		var total_pages = Math.ceil(total_results/results_per_page);

		var treated_page;
		if(current_page == 'final'){
			treated_page = total_pages;
		} else if(current_page == 'inicio'){
			treated_page = 1;
		} else {
			treated_page = parseInt(current_page);
		}

		if (isNaN(treated_page) || treated_page < 1) {
			treated_page = 1;
		}
		if (total_pages > 0 && treated_page > total_pages) {
			treated_page = total_pages;
		}
		currentPage = treated_page;

		var from_result_num = (results_per_page * treated_page) - results_per_page;

		var pgn = pagination(treated_page,total_pages);

		//criar tabela dinamicamente
		var tbl = '';
		tbl += pgn;
		tbl += "<hr>";
		tbl += "<table id='tabelaPrincipal' class='table'>";
			tbl += "<thead id='headings'>";
				tbl += "<tr>";
					tbl += "<th asc='' class='headings' width='10%'>Time A</th>";
					tbl += "<th asc='' class='headings' width='5%'>X</th>";
					tbl += "<th asc='' class='headings' width='10%'>Time B</th>";
					tbl += "<th asc='' class='headings' width='10%'>Fase</th>";
					tbl += "<th asc='' class='headings' width='5%'>Grupo</th>";
					tbl += "<th asc='' class='headings' width='10%'>Árbitro</th>";
					tbl += "<th asc='' class='headings' width='10%'>Estádio</th>";
					tbl += "<th asc='' class='headings' width='5%'>Data</th>";
					tbl += "<th asc='' class='headings' width='5%'>Hora</th>";
					tbl += "<th asc='' class='headings' width='5%'>Neutro</th>";
					tbl += "<th asc='' class='headings' width='5%'>Simular</th>";
					tbl += "<th asc='' class='headings' width='5%'>Status</th>";
					tbl += "<th asc='' class='headings' width='10%' class=''>Opções</th>";
				tbl += "</tr>";
			tbl +=  "</thead>";
			tbl +=  "<tbody>";

			// criar linhas
			$.each(ajax_data, function(index, val){

				if(index>=(from_result_num-1) && index<=(from_result_num+results_per_page-2)){
				
				var dataDisplay = "";
				var horaDisplay = "";
				var hora = "";
				try {
					var rawDate = val['data'] || "";
					var options = { year: 'numeric', month: '2-digit', day: '2-digit'};
					var data = new Date(rawDate.replace(/-/g, '\/'));
					dataDisplay = data.toLocaleDateString("pt-BR", options);
					hora = data.toLocaleTimeString("pt-BR", {hour: '2-digit', minute:'2-digit'});
					horaDisplay = hora;
				} catch(dateErr) {
					dataDisplay = val['data'] || "";
					horaDisplay = "00:00";
					hora = "00:00";
				}
				
				// Criação das variáveis de exibição
				let teamA = listaTimes.find(t => t[0] == val['timeA_id']);
				let teamB = listaTimes.find(t => t[0] == val['timeB_id']);
				let nomeTimeA = teamA ? teamA[1] : "Time " + val['timeA_id'];
				let nomeTimeB = teamB ? teamB[1] : "Time " + val['timeB_id'];
				
				let faseObj = listaFases.find(f => f[0] == val['fase']);
				let fase = faseObj ? faseObj[1] : "Fase " + val['fase'];
				
				let arbObj = listaArbitros.find(a => a[0] == val['arbitro']);
				let arbitro = arbObj ? arbObj[1] : "N/A";
				
				let estObj = listaEstadios.find(e => e[0] == val['estadio']);
				let estadio = estObj ? estObj[1] : "N/A";
				
				let grupo = val['grupo'] || "";
				let scoreDisplay = (val['status'] == 1) ? (val['timeA_gols'] + " X " + val['timeB_gols']) : "VS";
				let statusClass = (val['status'] == 1) ? "status-simulado" : "status-agendado";
				let statusText = (val['status'] == 1) ? "Simulado" : "Agendado";

				let arbOptions = "<option value='0'>N/A</option>";
				$.each(listaArbitros, function(i, a){
					arbOptions += "<option value='"+a[0]+"' "+ (a[0] == val['arbitro'] ? 'selected' : '') +">"+a[1]+"</option>";
				});

				let estOptions = "<option value='0'>N/A</option>";
				$.each(listaEstadios, function(i, e){
					estOptions += "<option value='"+e[0]+"' "+ (e[0] == val['estadio'] ? 'selected' : '') +">"+e[1]+"</option>";
				});

				let faseOptions = "";
				$.each(listaFases, function(i, f){
					faseOptions += "<option value='"+f[0]+"' "+ (f[0] == val['fase'] ? 'selected' : '') +">"+f[1]+"</option>";
				});

				let dateOnly = val['data'] ? val['data'].substring(0,10) : "";

				// Geração dos links de escalação
				let escLinkA = "";
				let escLinkB = "";
				if(logged == "true"){
					escLinkA = " <a href='/competicoes/escalacao_jogo.php?comp="+codigo_competicao+"&team="+val['timeA_id']+"&jogo="+val['id']+"' title='Escalação "+nomeTimeA+"' class='clickable lineup-btn'><span class='material-symbols-outlined'>assignment</span></a>";
					escLinkB = " <a href='/competicoes/escalacao_jogo.php?comp="+codigo_competicao+"&team="+val['timeB_id']+"&jogo="+val['id']+"' title='Escalação "+nomeTimeB+"' class='clickable lineup-btn'><span class='material-symbols-outlined'>assignment</span></a>";
				}

					// Geração da tabela
				tbl += "<tr id='"+val['id']+"'>";
					tbl += "<td class='time-casa' data-label='Time A'><div style='display: inline-flex; align-items: center; justify-content: flex-end; gap: 4px;'><span class='nomeTimeA' id='timeA"+ val['id']+"'>"+ nomeTimeA +"</span>" + escLinkA + "</div><select id='selTimeA"+val['id']+"' class='comboTimeA' style='display:none;'></select></td>";
					let scoreClass = (val['status'] == 1) ? 'gameScore placar-celula' : 'placar-celula-agendado';
					tbl += "<td class='" + scoreClass + "' data-label='Placar'><span>"+scoreDisplay+"</span></td>";
					tbl += "<td class='time-fora' data-label='Time B'><div style='display: inline-flex; align-items: center; justify-content: flex-start; gap: 4px;'><span class='nomeTimeB' id='timeB"+ val['id']+"'>"+ nomeTimeB +"</span>" + escLinkB + "</div><select id='selTimeB"+val['id']+"' class='comboTimeB' style='display:none;'></select></td>";
					tbl += "<td data-label='Fase'><span class='fase' id='fase"+ val['id']+"'>"+ fase +"</span><select id='selFase"+val['id']+"' class='comboFase editavel' style='display:none;'>"+faseOptions+"</select></td>";
					tbl += "<td data-label='Grupo'><span class='grupo' id='grupo"+ val['id']+"'>"+ grupo +"</span></td>";
					tbl += "<td data-label='Árbitro'><span class='arbitro' id='arbitro"+ val['id']+"'>"+ arbitro +"</span><select id='selArbitro"+val['id']+"' class='comboArbitro editavel' style='display:none;' disabled>"+arbOptions+"</select></td>";
					tbl += "<td data-label='Estádio'><span class='estadio' id='estadio"+ val['id']+"'>"+ estadio +"</span><select id='selEstadio"+val['id']+"' class='comboEstadio editavel' style='display:none;'>"+estOptions+"</select></td>";
					tbl += "<td data-label='Data'><span class='dataPartida' id='dat"+ val['id']+"'>"+ dataDisplay+" </span><input id='selDat"+val['id']+"' class='dataEditavel editavel' type='date' value='"+dateOnly+"' style='display:none;'/></td>";
					tbl += "<td data-label='Hora'><span class='horaPartida' id='hor"+ val['id']+"'>"+ horaDisplay+" </span><input id='selHor"+val['id']+"' class='horaEditavel editavel' type='time' value='"+hora+"' style='display:none;'/></td>";
					tbl += "<td data-label='Neutro'><input type='checkbox' class='neutro' id='alt"+ val['id']+"' "+ (val['neutro'] == 1? 'checked' : '')+" disabled></td>";
					tbl += "<td data-label='Simular'>" + (val['status'] == 0 ? "<a title='Simular' class='clickable simular-match'><span class='material-symbols-outlined inlineButton simular-btn'>sports_soccer</span></a>" : "") + "</td>";
					tbl += "<td data-label='Status'><span class='status-badge "+statusClass+"' id='status"+ val['id']+"'>"+ statusText +"</span></td>";

					let optionsString = "<td class='wide' data-label='Opções'>";
					if(logged == "true" && val['status'] == 0){
						if(admin == "true" || user_id === val['idDonoPais']){
							optionsString += "<a id='edi"+val['id']+"' title='Editar jogo' class='clickable editar'><span class='material-symbols-outlined inlineButton'>edit</span></a>";
							optionsString += "<a id='apa"+val['id']+"' title='Apagar jogo' class='clickable apagar'><span class='material-symbols-outlined inlineButton negativo'>delete</span></a>";
							optionsString += "<a id='sal"+val['id']+"' title='Salvar' class='clickable salvar' style='display:none;'><span class='material-symbols-outlined inlineButton positive'>check</span></a>";
							optionsString += "<a id='can"+val['id']+"' title='Cancelar' class='clickable cancelar' style='display:none;'><span class='material-symbols-outlined inlineButton negative'>close</span></a>";
						}
					}
					optionsString += "</td>";
					tbl += optionsString;
				tbl += "</tr>";


				}
			});

			tbl += '</tbody>';
		tbl += '</table>';
		
		

		//mostrar dados da tabela
		$(document).find('.tbl_user_data').html(tbl);
	}

	$(document).on("click", ".gameScore", function(){
		var tbl_row = $(this).closest("tr");
		var matchId = tbl_row.attr("id");
		
		let href = "./sumula_partida.php?id=" + matchId;
		
		window.open(href, '_blank');
	
	});

	$(document).on("click", ".editar", function(){
		var tbl_row = $(this).closest("tr");

		// Esconde apenas os spans de dados, não os ícones dos botões
		tbl_row.find('span.arbitro, span.estadio, span.fase, span.dataPartida, span.horaPartida, span.nomeTimeA, span.nomeTimeB').hide();
		
		tbl_row.find('.editavel').show();
		tbl_row.find('.gameScore span').show(); // Mantém o placar visível
		
		tbl_row.find(".salvar").show();
		tbl_row.find(".cancelar").show();
		tbl_row.find(".editar").hide();
		tbl_row.find(".apagar").hide();
		
		tbl_row.find('.neutro').prop('disabled', false);
	});

	$(document).on("click", ".cancelar", function(){
		var tbl_row = $(this).closest("tr");

		tbl_row.find('span').show();
		tbl_row.find('.editavel').hide();
		
		tbl_row.find(".salvar").hide();
		tbl_row.find(".cancelar").hide();
		tbl_row.find(".editar").show();
		tbl_row.find(".apagar").show();
		
		tbl_row.find('.neutro').prop('disabled', true);
	});
	// Código de edição de jogadores removido por ser irrelevante para partidas

	
	$(document).on('click', '.simular-match', function(){
        var tbl_row =  $(this).closest('tr');
        var matchId = tbl_row.prop('id');
        var r = confirm("Você tem certeza que deseja simular manualmente? Essa ação não pode ser desfeita!");
        if (r) {
			showLoading(true);
            $.ajax({
                type: "POST",
                url: '/competicoes/hexacolor/simular_partida.php',
                data: {matchId:matchId},
                dataType: 'json',
                success: function(data) {
					hideLoading();
                  console.log(data.error);
                  if(!data.success){
                    $('#errorbox').append('<div class="alert alert-danger">Não foi possível simular a partida. '+ data.error +'</div>');
                  } else {
                    load_data();
                  }


                },
                error: function(data) {
					hideLoading();
                    successmessage = 'Error';
                    $('#errorbox').append('<div class="alert alert-danger">Não foi possível simular a partida.</div>');
                }
            });
        }


    });
	

	$(document).on("click", ".salvar", function(){
		var tbl_row = $(this).closest("tr");
		var matchId = tbl_row.attr("id");
		
		var arbitro = tbl_row.find(".comboArbitro").val();
		var estadio = tbl_row.find(".comboEstadio").val();
		var fase = tbl_row.find(".comboFase").val();
		var data = tbl_row.find(".dataEditavel").val();
		var hora = tbl_row.find(".horaEditavel").val();
		var neutro = tbl_row.find(".neutro").is(":checked") ? 1 : 0;

		$.ajax({
			type: "POST",
			url: 'editar_jogo.php',
			data: {
				idPartida: matchId,
				arbitro: arbitro,
				estadio: estadio,
				fase: fase,
				data: data,
				hora: hora,
				neutro: neutro
			},
			dataType: 'json',
			success: function(data) {
				if(!data.success){
					$('#errorbox').append('<div class="alert alert-danger">Não foi possível salvar as alterações. '+ data.error +'</div>');
				} else {
					load_data();
				}
			},
			error: function() {
				$('#errorbox').append('<div class="alert alert-danger">Erro de comunicação com o servidor.</div>');
			}
		});
	});

	$(document).on("click", ".apagar", function(){
		var tbl_row = $(this).closest("tr");
		var matchId = tbl_row.attr("id");
		
		if(confirm("Deseja apagar este jogo?")){
			$.ajax({
				type: "POST",
				url: 'apagar_jogo.php',
				data: {matchId: matchId},
				dataType: 'json',
				success: function(data) {
					if(!data.success){
						$('#errorbox').append('<div class="alert alert-danger">Não foi possível apagar o jogo.</div>');
					} else {
						load_data();
					}
				}
			});
		}
	});


	    $('#confirmar_insercao').click(function(){
        $("#new_match").hide();
		
        //coleta de valores
        let timeA = $("#new_time_a").val();
		let timeB = $("#new_time_b").val();
		let fase = $("#new_fase").val();
		let arbitro = $("#new_arbitro").val();
		let estadio = $("#new_estadio").val();
		let datetime = $("#new_date").val() + " " + $("#new_time").val() + ":00";
		let hora = $("#new_time").val();
		let neutro = $("#new_neutro").is(":checked");

		var formData = new FormData();
		
		formData.append('codigo_competicao',codigo_competicao);
		formData.append('timeA',timeA);
		formData.append('timeB',timeB);
		formData.append('fase',fase);
		formData.append('arbitro',arbitro);
		formData.append('estadio',estadio);
		formData.append('datetime',datetime);
		formData.append('neutro',neutro);


		// for (var key of formData.entries()) {
			 // console.log(key[0] + ', ' + key[1]);
		 // }

   
		$.ajax({
				type        : 'POST', // define the type of HTTP verb we want to use (POST for our form)
				url         : '/competicoes/inserir_jogo.php', // the url where we want to POST
				data        : formData, // our data object
				processData : false,
				contentType : false,
				cache: false,
				dataType    : 'json', // what type of data do we expect back from the server
							//encode          : true
			})

						.done(function(data) {

				// log data to the console so we can see
				console.log(data);


				if (! data.success) {
					$('#errorbox').append('<div class="alert alert-danger">Não foi possível inserir o jogo, '+data.error+'</div>');

				} else {

					//$('#errorbox').append("<div class='alert alert-success'>A ação foi concluída com sucesso!</div>");

					load_data();

				}

				// here we will handle errors and validation messages
				}).fail(function(jqXHR, textStatus, errorThrown ){
					console.log("Erro");
					console.log(jqXHR);
					console.log(textStatus);
					console.log(errorThrown);
					$('#modalProposta').hide();
					$('#errorbox').append('<div class="alert alert-danger">Não foi possível inserir o jogo, '+errorThrown+'</div>');
				});


    });

	$(document).on('click', '.pagination_link', function(){
		var page = $(this).attr('id');
		updateTable(localData, page);
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
				pgn += "<li><button class='pagination_link' id='"+x+"' disabled>"+x+"</button></li>";
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

});

</script>

?>

<main class="propostas-container">
    <div id='errorbox'></div>
    
    <div class="propostas-card">
        <h2 class="propostas-title">Lista de Jogos</h2>
        <hr>

        <div id="new_match">
            <select id="new_time_a">
                <?php foreach($listaTimes as $time): ?>
                    <option value='<?php echo $time[0]; ?>'><?php echo $time[1]; ?></option>
                <?php endforeach; ?>
            </select>
            
            <span id="cross">X</span>
            
            <select id="new_time_b">
                <?php foreach($listaTimes as $time): ?>
                    <option value='<?php echo $time[0]; ?>'><?php echo $time[1]; ?></option>
                <?php endforeach; ?>
            </select>
            
            <select id="new_fase">
                <?php foreach($listaFases as $fase): ?>
                    <option value='<?php echo $fase[0]; ?>'><?php echo $fase[1]; ?></option>
                <?php endforeach; ?>
            </select>
            
            <select id="new_arbitro" disabled>
                <option value="0">Auto Árbitro</option>
                <?php foreach($listaArbitros as $arbitro): ?>
                    <option value='<?php echo $arbitro[0]; ?>'><?php echo $arbitro[1]; ?></option>
                <?php endforeach; ?>
            </select>
            
            <select id="new_estadio">
                <option value="0">Auto Estádio</option>
                <?php foreach($listaEstadios as $estadio): ?>
                    <option value='<?php echo $estadio[0]; ?>'><?php echo $estadio[1]; ?></option>
                <?php endforeach; ?>
            </select>
            
            <input type="date" id="new_date"/>
            <input type="time" id="new_time"/>
            <label for="new_neutro">
                Campo neutro
                <input type="checkbox" id="new_neutro"/>
            </label>
            <a id="confirmar_insercao" title="Salvar" class="clickable">
                <span class="material-symbols-outlined inlineButton positive">check</span>
            </a>
        </div>

        <div style='clear:both;'></div>
        
        <?php $random_loader = rand(1,5); ?>
        <div id='loading' style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(9, 13, 22, 0.85); backdrop-filter:blur(10px); -webkit-backdrop-filter:blur(10px); z-index:99999; flex-direction:column; justify-content:center; align-items:center; color:#fff; font-family:'Montserrat', sans-serif;">
            <div style="background:rgba(15, 23, 42, 0.8); padding:35px 40px; border-radius:18px; border:1px solid rgba(255,255,255,0.1); text-align:center; box-shadow:0 15px 35px rgba(0,0,0,0.6); max-width:420px; width:90%; box-sizing:border-box;">
                <img src="/images/loaders/loader_style<?php echo $random_loader; ?>.gif" style="height:70px; margin-bottom:20px;"/>
                <h3 id="loading-title" style="font-family:'Kanit', sans-serif; color:#38bdf8; margin:0 0 10px 0; font-size:1.4rem; font-weight:600; text-transform:uppercase; letter-spacing:1px;">Simulando Partida</h3>
                <p id="loading-step" style="margin:0; font-size:0.95rem; color:#cbd5e1; min-height:24px;">Iniciando...</p>
                <div id="loading-bar-container" style="width:100%; background:rgba(255,255,255,0.08); height:6px; border-radius:3px; margin-top:20px; overflow:hidden; border:1px solid rgba(255,255,255,0.05);">
                    <div id="loading-bar" style="width:5%; height:100%; background:linear-gradient(90deg, #38bdf8, #34d399); border-radius:3px; transition:width 0.4s ease-in-out;"></div>
                </div>
            </div>
        </div>
        
        <div class='tbl_user_data'></div>
        
        <div style='height: 50px; clear: both;'></div>
        <div style="margin-top: 30px;">
            <a href="competitionstatus.php?id=<?php echo $idCompeticao; ?>" style="display: inline-block; padding: 10px 20px; background: rgba(0, 0, 0, 0.03); border: 1px solid rgba(0, 0, 0, 0.08); border-radius: 8px; color: #475569; text-decoration: none; font-weight: 600; font-size: 0.9rem; transition: background 0.2s;" onmouseover="this.style.background='rgba(0, 0, 0, 0.06)'" onmouseout="this.style.background='rgba(0, 0, 0, 0.03)'">
                ← Voltar para a Competição
            </a>
        </div>
    </div>
</main>

<?php
} else {
    echo "Usuário, por favor refaça o login.";
}

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
