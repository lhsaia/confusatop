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
	$tipoCompeticao = isset($options['tipocompeticao']) ? (int)$options['tipocompeticao'] : 0;
	$numTeamsComp = isset($options['numero_times']) ? (int)$options['numero_times'] : 0;

	// Carregar vagas / slots para identificação de BYEs
	$assignedSlotTeams = [];
	$stmtTimesSlots = $competicao->carregarListaTimes($idCompeticao);
	while ($rSlot = $stmtTimesSlots->fetch(PDO::FETCH_ASSOC)) {
		$sName = !empty($rSlot['slot']) ? $rSlot['slot'] : ("Slot " . $rSlot['codigo_time']);
		if (!empty($rSlot['id_time_portal']) && intval($rSlot['id_time_portal']) > 0) {
			$assignedSlotTeams[$sName] = intval($rSlot['id_time_portal']);
		} else if ($rSlot['has_team'] == 1 || $rSlot['has_team'] == '1') {
			$assignedSlotTeams[$sName] = -1 * abs(intval($rSlot['codigo_time']));
		}
	}
	
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
  font-family: 'Outfit', sans-serif !important;
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
  font-family: 'Outfit', sans-serif !important;
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
	 var assignedSlotTeams = <?php echo json_encode($assignedSlotTeams); ?>;
	 var tipoCompeticao = <?php echo (int)$tipoCompeticao; ?>;
	 var numTeamsComp = <?php echo (int)$numTeamsComp; ?>;

	 var codigo_competicao = '<?php echo $idCompeticao ?>';
	 var localData = [];
	 var currentPage = 1;
	 var logged ='<?php echo (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true) ? "true" : "false"; ?>';
	 var admin ='<?php echo ((isset($_SESSION['admin_status']) && $_SESSION['admin_status'] == 1) || (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $donoCompeticao)) ? "true" : "false"; ?>';
	 var is_admin_user ='<?php echo (isset($_SESSION['admin_status']) && $_SESSION['admin_status'] == 1) ? "true" : "false"; ?>';
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
						// console.error("Resposta inválida do gerar_tabela:", data);
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
			// console.log("API de Jogos retornou:", data);
			hideLoading();
			try {
				var ajax_data = JSON.parse(data);
				// console.log("Total de jogos processados:", ajax_data.length);
				updateTable(ajax_data, currentPage);
				localData = ajax_data;
			} catch(e) {
				// console.error("Erro ao processar JSON de jogos:", e);
			}
		}
	});
	}
	
	
	function updateTable(ajax_data, current_page){
		// console.log("Iniciando updateTable com " + ajax_data.length + " jogos.");

		// Calcular e mesclar BYEs na lista de itens se for torneio de mata-mata com BYEs
		let allItems = [];
		if (ajax_data && ajax_data.length > 0) {
			allItems = ajax_data.slice(0);
		}

		if (tipoCompeticao == 1 && allItems.length > 0) {
			// Encontrar a primeira fase de mata-mata
			let fasesPresentes = [];
			$.each(allItems, function(i, jg){
				let f = parseInt(jg['fase']);
				if (f > 2 && !fasesPresentes.includes(f)) {
					fasesPresentes.push(f);
				}
			});

			let faseOrder = {10: 1, 9: 2, 3: 3, 4: 4, 5: 5, 6: 6, 8: 7};
			fasesPresentes.sort(function(a, b){
				return (faseOrder[a] || 100) - (faseOrder[b] || 100);
			});

			let primeiraFaseId = fasesPresentes.length > 0 ? fasesPresentes[0] : 0;

			if (primeiraFaseId > 0) {
				let jogandoPrimeiraFaseIds = {};
				let jogandoPrimeiraFaseNomes = {};
				let firstDate = allItems[0] && allItems[0]['data'] ? allItems[0]['data'] : new Date().toISOString().slice(0, 19).replace('T', ' ');

				$.each(allItems, function(i, jg){
					if (parseInt(jg['fase']) == primeiraFaseId) {
						if (parseInt(jg['timeA_id']) > 0) jogandoPrimeiraFaseIds[parseInt(jg['timeA_id'])] = true;
						if (jg['timeA_nome']) jogandoPrimeiraFaseNomes[$.trim(jg['timeA_nome'])] = true;
						if (parseInt(jg['timeB_id']) > 0) jogandoPrimeiraFaseIds[parseInt(jg['timeB_id'])] = true;
						if (jg['timeB_nome']) jogandoPrimeiraFaseNomes[$.trim(jg['timeB_nome'])] = true;
					}
				});

				let byeList = [];
				if (assignedSlotTeams && Object.keys(assignedSlotTeams).length > 0) {
					$.each(assignedSlotTeams, function(slotName, cId){
						let cIdInt = parseInt(cId);
						let isNoJogo = false;
						if (cIdInt > 0 && jogandoPrimeiraFaseIds[cIdInt]) isNoJogo = true;
						if (jogandoPrimeiraFaseNomes[$.trim(slotName)]) isNoJogo = true;

						if (!isNoJogo) {
							let tObj = (cIdInt > 0) ? listaTimes.find(t => t[0] == cIdInt) : null;
							let tNome = tObj ? tObj[1] : slotName;

							byeList.push({
								is_bye: true,
								id: 'bye_' + slotName,
								timeA_id: (cIdInt > 0) ? cIdInt : 0,
								timeA_nome: tNome,
								fase: primeiraFaseId,
								data: firstDate,
								status: 1
							});
						}
					});
				} else if (listaTimes && listaTimes.length > 0) {
					$.each(listaTimes, function(i, tObj){
						let cId = parseInt(tObj[0]);
						if (!jogandoPrimeiraFaseIds[cId]) {
							byeList.push({
								is_bye: true,
								id: 'bye_' + cId,
								timeA_id: cId,
								timeA_nome: tObj[1],
								fase: primeiraFaseId,
								data: firstDate,
								status: 1
							});
						}
					});
				}

				if (byeList.length > 0) {
					allItems = byeList.concat(allItems);
				}
			}
		}

		var results_per_page = 18;
		var total_results = allItems.length;
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
					tbl += "<th asc='' class='headings' width='5%'>Live</th>";
					tbl += "<th asc='' class='headings' width='5%'>Simular</th>";
					tbl += "<th asc='' class='headings' width='5%'>Status</th>";
					tbl += "<th asc='' class='headings' width='10%' class=''>Opções</th>";
				tbl += "</tr>";
			tbl +=  "</thead>";
			tbl +=  "<tbody>";

			// criar linhas
			$.each(allItems, function(index, val){

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
				let nomeTimeA = (teamA && parseInt(val['timeA_id']) > 0) ? teamA[1] : (val['timeA_nome'] ? val['timeA_nome'] : (parseInt(val['timeA_id']) > 0 ? "Time " + val['timeA_id'] : "A definir"));
				let nomeTimeB = (teamB && parseInt(val['timeB_id']) > 0) ? teamB[1] : (val['timeB_nome'] ? val['timeB_nome'] : (parseInt(val['timeB_id']) > 0 ? "Time " + val['timeB_id'] : "A definir"));
				
				let faseObj = listaFases.find(f => f[0] == val['fase']);
				let fase = faseObj ? faseObj[1] : "Fase " + val['fase'];
				
				let arbitro = "Aleatório";
				
				let estObj = listaEstadios.find(e => e[0] == val['estadio']);
				let estadio = estObj ? estObj[1] : "N/A";
				
				let grupo = val['grupo'] || "";
				
				// Sigilo Temporal no Frontend (se o jogo terminou no tempo real)
				let gameDateObj = new Date(val['data'] ? val['data'].replace(/-/g, '/') : Date.now());
				let duracaoMs = (val['timeA_penaltis'] || val['timeB_penaltis']) ? (150 * 60 * 1000) : (120 * 60 * 1000);
				let jaTerminou = (Date.now() >= (gameDateObj.getTime() + duracaoMs));
				let podeVerResultado = jaTerminou;

				let scoreDisplay = "VS";
				let statusClass = "status-agendado";
				let statusText = "Agendado";

				if (val['status'] == 1) {
					statusClass = "status-simulado";
					if (jaTerminou) {
						let penStr = (val['timeA_penaltis'] !== null && val['timeA_penaltis'] !== '' && val['timeA_penaltis'] !== undefined) ? " ("+val['timeA_penaltis']+"x"+val['timeB_penaltis']+")" : "";
						scoreDisplay = val['timeA_gols'] + " X " + val['timeB_gols'] + penStr;
						statusText = "Encerrado";
					} else {
						scoreDisplay = "VS";
						statusText = "Simulado";
					}
				}

				let arbOptions = "<option value='0' selected>Aleatório</option>";

				let estOptions = "<option value='0'>N/A</option>";
				$.each(listaEstadios, function(i, e){
					estOptions += "<option value='"+e[0]+"' "+ (e[0] == val['estadio'] ? 'selected' : '') +">"+e[1]+"</option>";
				});

				let faseOptions = "";
				$.each(listaFases, function(i, f){
					faseOptions += "<option value='"+f[0]+"' "+ (f[0] == val['fase'] ? 'selected' : '') +">"+f[1]+"</option>";
				});

				let dateOnly = val['data'] ? val['data'].substring(0,10) : "";

				// Geração dos links de escalação (apenas se o jogo não foi simulado/encerrado e se o time estiver definido)
				let escLinkA = "";
				let escLinkB = "";
				if(logged == "true" && val['status'] == 0){
					if (parseInt(val['timeA_id']) > 0) {
						escLinkA = " <a href='/competicoes/escalacao_jogo.php?comp="+codigo_competicao+"&team="+val['timeA_id']+"&jogo="+val['id']+"' title='Escalação "+nomeTimeA+"' class='clickable lineup-btn'><span class='material-symbols-outlined'>assignment</span></a>";
					}
					if (parseInt(val['timeB_id']) > 0) {
						escLinkB = " <a href='/competicoes/escalacao_jogo.php?comp="+codigo_competicao+"&team="+val['timeB_id']+"&jogo="+val['id']+"' title='Escalação "+nomeTimeB+"' class='clickable lineup-btn'><span class='material-symbols-outlined'>assignment</span></a>";
					}
				}

				let timeAOptions = "<option value='0' "+ (parseInt(val['timeA_id']) == 0 || !val['timeA_id'] ? 'selected' : '') +">A definir / Manter Slot</option>";
				$.each(listaTimes, function(i, t){
					timeAOptions += "<option value='"+t[0]+"' "+ (t[0] == val['timeA_id'] ? 'selected' : '') +">"+t[1]+"</option>";
				});

				let timeBOptions = "<option value='0' "+ (parseInt(val['timeB_id']) == 0 || !val['timeB_id'] ? 'selected' : '') +">A definir / Manter Slot</option>";
				$.each(listaTimes, function(i, t){
					timeBOptions += "<option value='"+t[0]+"' "+ (t[0] == val['timeB_id'] ? 'selected' : '') +">"+t[1]+"</option>";
				});

				// Geração da tabela
				if (val['is_bye']) {
					tbl += "<tr id='"+val['id']+"' class='bye-row'>";
						tbl += "<td class='time-casa' data-label='Time A'><div style='display: inline-flex; align-items: center; justify-content: flex-end; gap: 4px;'><span class='nomeTimeA'>"+ nomeTimeA +"</span></div></td>";
						tbl += "<td class='placar-celula-agendado' data-label='Placar'><span class='bye-badge-table'>BYE</span></td>";
						tbl += "<td class='time-fora' data-label='Time B' style='color: #64748b; font-style: italic;'>Avança direto</td>";
						tbl += "<td data-label='Fase'><span class='fase'>"+ fase +"</span></td>";
						tbl += "<td data-label='Grupo'>-</td>";
						tbl += "<td data-label='Árbitro'>-</td>";
						tbl += "<td data-label='Estádio'>-</td>";
						tbl += "<td data-label='Data'>-</td>";
						tbl += "<td data-label='Hora'>-</td>";
						tbl += "<td data-label='Neutro'>-</td>";
						tbl += "<td data-label='Live'>-</td>";
						tbl += "<td data-label='Simular'>-</td>";
						tbl += "<td data-label='Status'><span class='status-badge status-simulado'>Classificado</span></td>";
						tbl += "<td class='wide' data-label='Opções'>-</td>";
					tbl += "</tr>";
				} else {
					let isLiveChecked = (val['subir_live'] == 1 || val['subir_live'] === '1' || val['subir_live'] === true);
					tbl += "<tr id='"+val['id']+"'>";
						tbl += "<td class='time-casa' data-label='Time A'><div style='display: inline-flex; align-items: center; justify-content: flex-end; gap: 4px;'><span class='nomeTimeA' id='timeA"+ val['id']+"'>"+ nomeTimeA +"</span>" + escLinkA + "</div><select id='selTimeA"+val['id']+"' class='comboTimeA editavel' style='display:none;'>"+timeAOptions+"</select></td>";
						let scoreClass = (val['status'] == 1 && podeVerResultado) ? 'gameScore placar-celula' : 'placar-celula-agendado';
						tbl += "<td class='" + scoreClass + "' data-label='Placar'><span>"+scoreDisplay+"</span></td>";
						tbl += "<td class='time-fora' data-label='Time B'><div style='display: inline-flex; align-items: center; justify-content: flex-start; gap: 4px;'><span class='nomeTimeB' id='timeB"+ val['id']+"'>"+ nomeTimeB +"</span>" + escLinkB + "</div><select id='selTimeB"+val['id']+"' class='comboTimeB editavel' style='display:none;'>"+timeBOptions+"</select></td>";
						tbl += "<td data-label='Fase'><span class='fase' id='fase"+ val['id']+"'>"+ fase +"</span><select id='selFase"+val['id']+"' class='comboFase editavel' style='display:none;'>"+faseOptions+"</select></td>";
						tbl += "<td data-label='Grupo'><span class='grupo' id='grupo"+ val['id']+"'>"+ grupo +"</span><input id='selGrupo"+val['id']+"' class='grupoEditavel editavel' type='text' value='"+grupo+"' style='display:none; max-width:60px;'/></td>";
						tbl += "<td data-label='Árbitro'><span class='arbitro' id='arbitro"+ val['id']+"'>"+ arbitro +"</span><select id='selArbitro"+val['id']+"' class='comboArbitro editavel' style='display:none;' disabled>"+arbOptions+"</select></td>";
						tbl += "<td data-label='Estádio'><span class='estadio' id='estadio"+ val['id']+"'>"+ estadio +"</span><select id='selEstadio"+val['id']+"' class='comboEstadio editavel' style='display:none;'>"+estOptions+"</select></td>";
						tbl += "<td data-label='Data'><span class='dataPartida' id='dat"+ val['id']+"'>"+ dataDisplay+" </span><input id='selDat"+val['id']+"' class='dataEditavel editavel' type='date' value='"+dateOnly+"' style='display:none;'/></td>";
						tbl += "<td data-label='Hora'><span class='horaPartida' id='hor"+ val['id']+"'>"+ horaDisplay+" </span><input id='selHor"+val['id']+"' class='horaEditavel editavel' type='time' value='"+hora+"' style='display:none;'/></td>";
						tbl += "<td data-label='Neutro'><input type='checkbox' class='neutro' id='alt"+ val['id']+"' "+ (val['neutro'] == 1? 'checked' : '')+" disabled></td>";
						tbl += "<td data-label='Live'><input type='checkbox' class='subir_live_chk' id='live"+ val['id']+"' "+ (isLiveChecked ? 'checked' : '')+" disabled></td>";
						let temEstadioValido = parseInt(val['estadio']) > 0 && estObj !== undefined;
						let simularBtnHtml = "";
						if (logged == "true" && is_admin_user == "true" && val['status'] == 0) {
							if (temEstadioValido) {
								simularBtnHtml = "<a title='Simular partida' class='clickable simular-match'><span class='material-symbols-outlined inlineButton simular-btn'>sports_soccer</span></a>";
							} else {
								simularBtnHtml = "<span title='Estádio não definido (N/A). Defina um estádio válido para simular.' style='opacity: 0.35; cursor: not-allowed;'><span class='material-symbols-outlined inlineButton'>sports_soccer</span></span>";
							}
						}
						tbl += "<td data-label='Simular'>" + simularBtnHtml + "</td>";
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
		tbl_row.find('span.arbitro, span.estadio, span.fase, span.grupo, span.dataPartida, span.horaPartida, span.nomeTimeA, span.nomeTimeB, a.lineup-btn').hide();
		
		tbl_row.find('.editavel').show();
		tbl_row.find('.gameScore span').show(); // Mantém o placar visível
		
		tbl_row.find(".salvar").show();
		tbl_row.find(".cancelar").show();
		tbl_row.find(".editar").hide();
		tbl_row.find(".apagar").hide();
		
		tbl_row.find('.neutro').prop('disabled', false);
		tbl_row.find('.subir_live_chk').prop('disabled', false);
	});

	$(document).on("click", ".cancelar", function(){
		var tbl_row = $(this).closest("tr");

		tbl_row.find('span, a.lineup-btn').show();
		tbl_row.find('.editavel').hide();
		
		tbl_row.find(".salvar").hide();
		tbl_row.find(".cancelar").hide();
		tbl_row.find(".editar").show();
		tbl_row.find(".apagar").show();
		
		tbl_row.find('.neutro').prop('disabled', true);
		tbl_row.find('.subir_live_chk').prop('disabled', true);
	});

	$(document).on("click", ".salvar", function(){
		var tbl_row = $(this).closest("tr");
		var matchId = tbl_row.attr("id");
		
		var timeA_id = tbl_row.find(".comboTimeA").val();
		var timeB_id = tbl_row.find(".comboTimeB").val();
		var arbitro = tbl_row.find(".comboArbitro").val();
		var estadio = tbl_row.find(".comboEstadio").val();
		var fase = tbl_row.find(".comboFase").val();
		var grupo = tbl_row.find(".grupoEditavel").val();
		var data = tbl_row.find(".dataEditavel").val();
		var hora = tbl_row.find(".horaEditavel").val();
		var neutro = tbl_row.find(".neutro").is(":checked") ? 1 : 0;
		var subir_live = tbl_row.find(".subir_live_chk").is(":checked") ? 1 : 0;

		$.ajax({
			type: "POST",
			url: 'editar_jogo.php',
			data: {
				idPartida: matchId,
				timeA_id: timeA_id,
				timeB_id: timeB_id,
				arbitro: arbitro,
				estadio: estadio,
				fase: fase,
				grupo: grupo,
				data: data,
				hora: hora,
				neutro: neutro,
				subir_live: subir_live
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

	$(document).on("click", ".simular-match", function(){
		if (is_admin_user !== "true") {
			alert('Apenas administradores do sistema podem simular partidas manualmente.');
			return;
		}
		var tbl_row = $(this).closest("tr");
		var matchId = tbl_row.attr("id");
		
		if(confirm("Deseja simular esta partida agora?")){
			showLoading(true);
			$.ajax({
				type: "POST",
				url: '/competicoes/hexacolor/simular_partida.php',
				data: { matchId: matchId },
				dataType: 'json',
				success: function(data) {
					hideLoading();
					if(!data.success){
						alert('Não foi possível simular a partida: ' + (data.error || 'Erro desconhecido.'));
					} else {
						load_data();
					}
				},
				error: function(xhr, status, error) {
					hideLoading();
					alert('Erro de comunicação com o simulador: ' + error);
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
		let subir_live = $("#new_subir_live").is(":checked") ? 1 : 0;

		var formData = new FormData();
		
		formData.append('codigo_competicao',codigo_competicao);
		formData.append('timeA',timeA);
		formData.append('timeB',timeB);
		formData.append('fase',fase);
		formData.append('arbitro',arbitro);
		formData.append('estadio',estadio);
		formData.append('datetime',datetime);
		formData.append('neutro',neutro);
		formData.append('subir_live',subir_live);


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
				// console.log(data);


				if (! data.success) {
					$('#errorbox').append('<div class="alert alert-danger">Não foi possível inserir o jogo, '+data.error+'</div>');

				} else {

					//$('#errorbox').append("<div class='alert alert-success'>A ação foi concluída com sucesso!</div>");

					load_data();

				}

				// here we will handle errors and validation messages
				}).fail(function(jqXHR, textStatus, errorThrown ){
					// console.log("Erro");
					// console.log(jqXHR);
					// console.log(textStatus);
					// console.log(errorThrown);
					$('#modalProposta').hide();
					$('#errorbox').append('<div class="alert alert-danger">Não foi possível inserir o jogo, '+errorThrown+'</div>');
				});


    });

	$(document).on('click', '.pagination_link', function(){
		var page = $(this).attr('id');
		updateTable(localData, page);
	});

	function pagination(current_page, total_pages){
		var pgn = '<ul class="pagination">';
		if(total_pages > 1){
			var prev_page = parseInt(current_page) - 1;
			var next_page = parseInt(current_page) + 1;

			if(current_page > 1){
				pgn += '<li><button class="pagination_link" id="inicio" title="Primeira Página">&laquo;</button></li>';
				pgn += '<li><button class="pagination_link" id="' + prev_page + '" title="Página Anterior">&lsaquo;</button></li>';
			}

			var range = 2;
			var initial_num = parseInt(current_page) - range;
			var condition_limit_num = parseInt(current_page) + range + 1;

			for (var x = initial_num; x < condition_limit_num; x++) {
				if ((x > 0) && (x <= total_pages)) {
					if (x == current_page) {
						pgn += '<li><button class="pagination_link" id="' + x + '" disabled>' + x + '<span class="sr-only">(current)</span></button></li>';
					} else {
						pgn += '<li><button class="pagination_link" id="' + x + '">' + x + '</button></li>';
					}
				}
			}

			if(current_page < total_pages){
				pgn += '<li><button class="pagination_link" id="' + next_page + '" title="Próxima Página">&rsaquo;</button></li>';
				pgn += '<li><button class="pagination_link" id="final" title="Última Página">&raquo;</button></li>';
			}
		}
		pgn += '</ul>';
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
            <label for="new_subir_live">
                Live
                <input type="checkbox" id="new_subir_live" <?php echo (!empty($options['subir_live']) ? 'checked' : ''); ?>/>
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
                <h3 id="loading-title" style="font-family:'Outfit', sans-serif; color:#38bdf8; margin:0 0 10px 0; font-size:1.4rem; font-weight:600; text-transform:uppercase; letter-spacing:1px;">Simulando Partida</h3>
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
