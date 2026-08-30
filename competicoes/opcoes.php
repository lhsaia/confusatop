<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
?>
<!DOCTYPE html>

<?php
$idCompeticao = $_GET['id'];

require_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){
	
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/competicao_clube.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");

$database = new Database();
$db = $database->getConnection();

$competicao = new Competicao_clube($db);

$info = $competicao->readInfo($idCompeticao);
$nome_competicao = $info['nome'];
$ano_competicao = $info['ano'];
$sede_competicao = $info['sede'];
$federacao_competicao = $info['federacao'];
$logo_competicao = $info['logo'];

$options = $competicao->getOptions($idCompeticao);

// Obter dados adicionais do MariaDB
$stmtTodosArb = $db->prepare("SELECT id, nomeArbitro FROM arbitros ORDER BY nomeArbitro");
$stmtTodosArb->execute();
$todosArbitros = $stmtTodosArb->fetchAll(PDO::FETCH_ASSOC);

$stmtTodosPaises = $db->prepare("SELECT id, nome FROM paises ORDER BY nome");
$stmtTodosPaises->execute();
$todosPaises = $stmtTodosPaises->fetchAll(PDO::FETCH_ASSOC);

$stmtTodasFeds = $db->prepare("SELECT id, nome FROM federacoes ORDER BY nome");
$stmtTodasFeds->execute();
$todasFederacoes = $stmtTodasFeds->fetchAll(PDO::FETCH_ASSOC);

// Determinar o ID da sede física se houver
$sede_id = 0;
if ($sede_competicao != "flag.png" && !empty($sede_competicao)) {
    $stmtSede = $db->prepare("SELECT id FROM paises WHERE bandeira = :ban LIMIT 1");
    $stmtSede->bindParam(':ban', $sede_competicao);
    $stmtSede->execute();
    $sedeRow = $stmtSede->fetch(PDO::FETCH_ASSOC);
    if ($sedeRow) {
        $sede_id = (int)$sedeRow['id'];
    }
}

// Buscar estádios (filtrar por sede se houver)
if ($sede_id > 0) {
    $stmtTodosEst = $db->prepare("SELECT id, Nome FROM estadio WHERE Pais = :pais ORDER BY Nome");
    $stmtTodosEst->bindParam(':pais', $sede_id, PDO::PARAM_INT);
} else {
    $stmtTodosEst = $db->prepare("SELECT id, Nome FROM estadio ORDER BY Nome");
}
$stmtTodosEst->execute();
$todosEstadios = $stmtTodosEst->fetchAll(PDO::FETCH_ASSOC);

// Buscar árbitros e estádios selecionados no SQLite
require_once $_SERVER['DOCUMENT_ROOT'] . "/config/sqliteDatabase.php";
$sqliteDb = new SQLiteDatabase();
$sqliteDb->fileName = $_SERVER['DOCUMENT_ROOT'] . "/competicoes/databases/" . $idCompeticao . "-database.db3";
$sdb = $sqliteDb->getConnection();

$selectedArbitros = [];
$selectedEstadios = [];

if ($sdb) {
    try {
        $selectedArbitros = array_column($sdb->query("SELECT ID FROM trioarbitragem")->fetchAll(PDO::FETCH_ASSOC), 'ID');
    } catch(Exception $e) {}
    try {
        $selectedEstadios = array_column($sdb->query("SELECT ID FROM estadio")->fetchAll(PDO::FETCH_ASSOC), 'ID');
    } catch(Exception $e) {}
}

$page_title = "Opções - " . $nome_competicao . " " . $ano_competicao;
$page_header = "Opções - " . $nome_competicao . " " . $ano_competicao;

$css_filename = "home_redesign";
$aux_css = 'opcoes_redesign';
$css_login = 'login';
$css_versao = date('h:i:s');
require_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

?>
<main class="propostas-container">
    <div id='errorbox'></div>
    <div class="propostas-card">
        <h2 class="propostas-title"><?php echo $page_title; ?></h2>
        <div id='inscricao'>
	<!-- Configurações Gerais -->
	<div class="opcoes-secao">
		<h3 class="opcoes-secao-titulo">
			<span class="material-symbols-outlined">settings</span>
			Configurações Gerais
		</h3>
		
		<label for='input_numerotimes'>Número de times</label>
		<input type='number' min='4' max='64' name='input_numerotimes' id='input_numerotimes' value='<?php echo $options['numero_times']?>'/>
		
		<label for='input_datalimite'>Limite para envio de fichas</label>
		<input type='date' name='input_datalimite' id='input_datalimite' value='<?php echo $options['limite_fichas']?>'/>
		
		<label for='input_sorteio'>Sorteio</label>
		<select name='input_sorteio' id='input_sorteio'>
			<option value='0' <?php echo ($options['sorteio']==0?"selected":"")?>>Automático (Distribuir times reais)</option>
			<option value='2' <?php echo ($options['sorteio']==2?"selected":"")?>>Intermediário (Gerar grade com slots / placeholders)</option>
			<option value='1' <?php echo ($options['sorteio']==1?"selected":"")?>>Totalmente Manual (Criar jogos um a um)</option>
		</select>
		
		<label for='input_tipocompeticao'>Tipo de competição</label>
		<select name='input_tipocompeticao' id='input_tipocompeticao'>
			<option value='0' <?php echo ($options['tipocompeticao']==0?"selected":"")?>>Misto (Grupos + Mata-mata)</option>
			<option value='1' <?php echo ($options['tipocompeticao']==1?"selected":"")?>>Mata-mata</option>
			<option value='2' <?php echo ($options['tipocompeticao']==2?"selected":"")?>>Pontos Corridos</option>
		</select>

		<!-- Configurações específicas para Torneio Misto (Fase de Grupos) -->
		<div id="secao_misto" style="<?php echo ($options['tipocompeticao']==0 ? '' : 'display:none;'); ?> background: rgba(2, 132, 199, 0.04); border: 1px dashed rgba(2, 132, 199, 0.25); border-radius: 8px; padding: 15px; margin-top: 15px;">
			<div style="display: flex; gap: 15px; flex-wrap: wrap;">
				<div style="flex: 1; min-width: 140px;">
					<label for='input_numgrupos' style="margin-top:0;">Número de grupos</label>
					<input type='number' min='1' max='16' name='input_numgrupos' id='input_numgrupos' value='<?php echo (isset($options['num_grupos']) && intval($options['num_grupos']) > 0) ? intval($options['num_grupos']) : 4; ?>'/>
				</div>
				<div style="flex: 1; min-width: 140px;">
					<label for='input_timesporgrupo' style="margin-top:0;">Times por grupo</label>
					<input type='number' min='2' max='16' name='input_timesporgrupo' id='input_timesporgrupo' value='<?php echo (isset($options['times_por_grupo']) && intval($options['times_por_grupo']) > 0) ? intval($options['times_por_grupo']) : 4; ?>'/>
				</div>
			</div>
			<div style="margin-top: 10px;">
				<label for='input_tipopreliminar'>Fase Preliminar (Se houver times excedentes)</label>
				<select name='input_tipopreliminar' id='input_tipopreliminar'>
					<option value='1' <?php echo (!isset($options['tipo_preliminar']) || $options['tipo_preliminar']==1) ? "selected" : ""; ?>>Ida e Volta</option>
					<option value='0' <?php echo (isset($options['tipo_preliminar']) && $options['tipo_preliminar']==0) ? "selected" : ""; ?>>Apenas Ida (Jogo Único)</option>
				</select>
			</div>
		</div>

		<!-- Configurações específicas para Pontos Corridos -->
		<div id="secao_pontoscorridos" style="<?php echo ($options['tipocompeticao']==2 ? '' : 'display:none;'); ?> background: rgba(2, 132, 199, 0.04); border: 1px dashed rgba(2, 132, 199, 0.25); border-radius: 8px; padding: 15px; margin-top: 15px;">
			<label for='input_turnospontoscorridos' style="margin-top:0;">Número de turnos</label>
			<select name='input_turnospontoscorridos' id='input_turnospontoscorridos'>
				<option value='1' <?php echo (isset($options['turnos_pontos_corridos']) && $options['turnos_pontos_corridos']==1) ? "selected" : ""; ?>>1 Turno (Apenas Ida)</option>
				<option value='2' <?php echo (!isset($options['turnos_pontos_corridos']) || $options['turnos_pontos_corridos']==2) ? "selected" : ""; ?>>2 Turnos (Ida e Volta)</option>
				<option value='3' <?php echo (isset($options['turnos_pontos_corridos']) && $options['turnos_pontos_corridos']==3) ? "selected" : ""; ?>>3 Turnos</option>
				<option value='4' <?php echo (isset($options['turnos_pontos_corridos']) && $options['turnos_pontos_corridos']==4) ? "selected" : ""; ?>>4 Turnos</option>
			</select>
		</div>

		<label for='input_subirjogoslive' style="margin-top: 15px;">
			Subir jogos no Live?
			<input type='checkbox' name='input_subirjogoslive' id='input_subirjogoslive' <?php echo ($options['subir_live']?"checked":"")?> />
		</label>
	</div>

	<!-- Regras de Partida & Desempate -->
	<div class="opcoes-secao">
		<h3 class="opcoes-secao-titulo">
			<span class="material-symbols-outlined">sports_soccer</span>
			Regras de Partida & Desempate
		</h3>
		
		<?php
		$desempate_grupos = isset($options['desempate_grupos']) ? $options['desempate_grupos'] : 'SG,GP,VI,CD';
		$criterios_selecionados = explode(',', $desempate_grupos);
		while (count($criterios_selecionados) < 4) {
			$criterios_selecionados[] = 'SG';
		}
		?>
		<label>Critérios de Desempate (Fase de Grupos) - Ordem de Prioridade</label>
		<div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px;">
			<div style="flex: 1; min-width: 150px;">
				<span style="color: #38bdf8; font-size: 0.85rem; display: block; margin-bottom: 5px; text-shadow: none;">1º Critério</span>
				<select id="desempate_grupo_1" style="width: 100% !important;">
					<option value="SG" <?php echo $criterios_selecionados[0] == 'SG' ? 'selected' : ''; ?>>Saldo de Gols</option>
					<option value="GP" <?php echo $criterios_selecionados[0] == 'GP' ? 'selected' : ''; ?>>Gols Pró</option>
					<option value="VI" <?php echo $criterios_selecionados[0] == 'VI' ? 'selected' : ''; ?>>Vitórias</option>
					<option value="CD" <?php echo $criterios_selecionados[0] == 'CD' ? 'selected' : ''; ?>>Confronto Direto</option>
				</select>
			</div>
			<div style="flex: 1; min-width: 150px;">
				<span style="color: #38bdf8; font-size: 0.85rem; display: block; margin-bottom: 5px; text-shadow: none;">2º Critério</span>
				<select id="desempate_grupo_2" style="width: 100% !important;">
					<option value="SG" <?php echo $criterios_selecionados[1] == 'SG' ? 'selected' : ''; ?>>Saldo de Gols</option>
					<option value="GP" <?php echo $criterios_selecionados[1] == 'GP' ? 'selected' : ''; ?>>Gols Pró</option>
					<option value="VI" <?php echo $criterios_selecionados[1] == 'VI' ? 'selected' : ''; ?>>Vitórias</option>
					<option value="CD" <?php echo $criterios_selecionados[1] == 'CD' ? 'selected' : ''; ?>>Confronto Direto</option>
				</select>
			</div>
			<div style="flex: 1; min-width: 150px;">
				<span style="color: #38bdf8; font-size: 0.85rem; display: block; margin-bottom: 5px; text-shadow: none;">3º Critério</span>
				<select id="desempate_grupo_3" style="width: 100% !important;">
					<option value="SG" <?php echo $criterios_selecionados[2] == 'SG' ? 'selected' : ''; ?>>Saldo de Gols</option>
					<option value="GP" <?php echo $criterios_selecionados[2] == 'GP' ? 'selected' : ''; ?>>Gols Pró</option>
					<option value="VI" <?php echo $criterios_selecionados[2] == 'VI' ? 'selected' : ''; ?>>Vitórias</option>
					<option value="CD" <?php echo $criterios_selecionados[2] == 'CD' ? 'selected' : ''; ?>>Confronto Direto</option>
				</select>
			</div>
			<div style="flex: 1; min-width: 150px;">
				<span style="color: #38bdf8; font-size: 0.85rem; display: block; margin-bottom: 5px; text-shadow: none;">4º Critério</span>
				<select id="desempate_grupo_4" style="width: 100% !important;">
					<option value="SG" <?php echo $criterios_selecionados[3] == 'SG' ? 'selected' : ''; ?>>Saldo de Gols</option>
					<option value="GP" <?php echo $criterios_selecionados[3] == 'GP' ? 'selected' : ''; ?>>Gols Pró</option>
					<option value="VI" <?php echo $criterios_selecionados[3] == 'VI' ? 'selected' : ''; ?>>Vitórias</option>
					<option value="CD" <?php echo $criterios_selecionados[3] == 'CD' ? 'selected' : ''; ?>>Confronto Direto</option>
				</select>
			</div>
		</div>

		<label for='input_criteriosdesempate'>Critérios de desempate (Mata-mata)</label>
		<select name='input_criteriosdesempate' id='input_criteriosdesempate'>
			<option value='0' <?php echo ($options['criteriodesempate']==0?"selected":"")?>>Prorrogação e pênaltis</option>
			<option value='1' <?php echo ($options['criteriodesempate']==1?"selected":"")?>>Pênaltis</option>
		</select>
		
		<label for='input_criteriosdesempatefinal'>Critérios de desempate na final (Mata-mata)</label>
		<select name='input_criteriosdesempatefinal' id='input_criteriosdesempatefinal'>
			<option value='0' <?php echo ($options['criteriodesempatefinal']==0?"selected":"")?>>Prorrogação e pênaltis</option>
			<option value='1' <?php echo ($options['criteriodesempatefinal']==1?"selected":"")?>>Pênaltis</option>
		</select>

		<label for='input_golfora' style="margin-top: 15px;">
			Desempate por gol fora de casa
			<input type='checkbox' name='input_golfora' id='input_golfora' <?php echo ($options['golfora']?"checked":"")?>/>
		</label>

		<label for='input_finalunica' style="margin-top: 15px;">
			Final em jogo único
			<input type='checkbox' name='input_finalunica' id='input_finalunica' <?php echo ($options['finalunica']?"checked":"")?> />
		</label>
	</div>

	<!-- Cartões & Suspensão -->
	<div class="opcoes-secao">
		<h3 class="opcoes-secao-titulo">
			<span class="material-symbols-outlined">gavel</span>
			Cartões & Suspensões
		</h3>
		
		<label for='input_criteriossuspensao'>Critérios de suspensão</label>
		<select name='input_criteriossuspensao' id='input_criteriossuspensao'>
			<option value='0' <?php echo ($options['suspensao']==0?"selected":"")?>>Apenas vermelho suspende</option>
			<option value='1' <?php echo ($options['suspensao']==1?"selected":"")?>>Suspensão por 2 amarelos</option>
			<option value='2' <?php echo ($options['suspensao']==2?"selected":"")?>>Suspensão por 3 amarelos</option>
		</select>
		
		<label for='input_zeraamarelos'>Zerar amarelos</label>
		<select name='input_zeraamarelos' id='input_zeraamarelos'>
			<option value='0' <?php echo ($options['zeraramarelos']==0?"selected":"")?>>Zerar cartões nas quartas</option>
			<option value='1' <?php echo ($options['zeraramarelos']==1?"selected":"")?>>Zerar cartões após fase de grupos</option>
			<option value='2' <?php echo ($options['zeraramarelos']==2?"selected":"")?>>Não zerar cartões</option>
		</select>
	</div>

	<!-- Alterações de Elenco -->
	<div class="opcoes-secao">
		<h3 class="opcoes-secao-titulo">
			<span class="material-symbols-outlined">group_add</span>
			Alterações de Elenco
		</h3>
		
		<label for='input_datainicioalteracoes'>Início das alterações</label>
		<input type='date' name='input_datainicioalteracoes' id='input_datainicioalteracoes' value='<?php echo $options['inicioalteracoes']?>'/>
		
		<label for='input_datafimalteracoes'>Fim das alterações</label>
		<input type='date' name='input_datafimalteracoes' id='input_datafimalteracoes' value='<?php echo $options['fimalteracoes']?>'/>
		
		<label for='input_numeroalteracoes'>Quantos jogadores adicionais?</label>
		<input type='number' min='1' max='23' name='input_numeroalteracoes' id='input_numeroalteracoes' value='<?php echo $options['jogadoresadicionais']?>'/>

		<label for='input_permitiralteracoes' style="margin-top: 15px;">
			Permitir alterações de elenco
			<input type='checkbox' name='input_permitiralteracoes' id='input_permitiralteracoes' <?php echo ($options['alteracoeselenco']?"checked":"")?> />
		</label>
	</div>

	<!-- Importação do Select2 CDN -->
	<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
	<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

	<!-- Arbitragem -->
	<div class="opcoes-secao">
		<h3 class="opcoes-secao-titulo">
			<span class="material-symbols-outlined">sports</span>
			Arbitragem da Competição
		</h3>
		
		<label for='select_arbitros'>Selecionar Árbitros Específicos</label>
		<select name='select_arbitros[]' id='select_arbitros' class='select2-multiple' multiple='multiple' style='width: 92%;'>
			<?php foreach($todosArbitros as $arb): ?>
				<option value='<?php echo $arb['id']; ?>' <?php echo in_array($arb['id'], $selectedArbitros) ? 'selected' : ''; ?>>
					<?php echo htmlspecialchars($arb['nomeArbitro']); ?>
				</option>
			<?php endforeach; ?>
		</select>

		<label for='select_arbitros_pais' style='margin-top: 15px;'>Ou Adicionar todos de um País</label>
		<select name='select_arbitros_pais' id='select_arbitros_pais' style='width: 92%;'>
			<option value='0'>Nenhum país selecionado</option>
			<?php foreach($todosPaises as $pais): ?>
				<option value='<?php echo $pais['id']; ?>'><?php echo htmlspecialchars($pais['nome']); ?></option>
			<?php endforeach; ?>
		</select>

		<label for='select_arbitros_fed' style='margin-top: 15px;'>Ou Adicionar todos de uma Federação</label>
		<select name='select_arbitros_fed' id='select_arbitros_fed' style='width: 92%;'>
			<option value='0'>Nenhuma federação selecionada</option>
			<?php foreach($todasFederacoes as $fed): ?>
				<option value='<?php echo $fed['id']; ?>'><?php echo htmlspecialchars($fed['nome']); ?></option>
			<?php endforeach; ?>
		</select>
	</div>

	<!-- Estádios -->
	<div class="opcoes-secao">
		<h3 class="opcoes-secao-titulo">
			<span class="material-symbols-outlined">stadium</span>
			Estádios da Competição
		</h3>

		<label for='select_estadios'>Selecionar Estádios <?php echo $sede_id > 0 ? '(Apenas da Sede)' : ''; ?></label>
		<select name='select_estadios[]' id='select_estadios' class='select2-multiple' multiple='multiple' style='width: 92%;'>
			<?php foreach($todosEstadios as $est): ?>
				<option value='<?php echo $est['id']; ?>' <?php echo in_array($est['id'], $selectedEstadios) ? 'selected' : ''; ?>>
					<?php echo htmlspecialchars($est['Nome']); ?>
				</option>
			<?php endforeach; ?>
		</select>

		<label for='input_estadiostimes' style='margin-top: 15px;'>
			Disponibilizar estádios dos times da casa?
			<input type='checkbox' name='input_estadiostimes' id='input_estadiostimes' <?php echo (!isset($options['estadios_times']) || $options['estadios_times'] ? 'checked' : ''); ?> />
		</label>
	</div>

    <input type='submit' value='Salvar Configurações' id='salvar' />
</div>
</div>
        <div style="margin-top: 30px;">
            <a href="competitionstatus.php?id=<?php echo $idCompeticao; ?>" style="display: inline-block; padding: 10px 20px; background: rgba(0, 0, 0, 0.03); border: 1px solid rgba(0, 0, 0, 0.08); border-radius: 8px; color: #475569; text-decoration: none; font-weight: 600; font-size: 0.9rem; transition: background 0.2s;" onmouseover="this.style.background='rgba(0, 0, 0, 0.06)'" onmouseout="this.style.background='rgba(0, 0, 0, 0.03)'">
                ← Voltar para a Competição
            </a>
        </div>
</div>
</main>

<script>

$(document).ready(function($){
	
	var idCompeticao = <?php echo $idCompeticao?>;
	
	$("#input_numerotimes").each(function(){
		$(this).keyup(function () {
			if (!$(this).val() || (parseInt($(this).val()) <= 64 && parseInt($(this).val()) >= 4));
			else $(this).val($(this).data("old"));
		});
	});
	
	$("#input_numeroalteracoes").each(function(){
		$(this).keyup(function () {
			if (!$(this).val() || (parseInt($(this).val()) <= 23 && parseInt($(this).val()) >= 1));
			else $(this).val($(this).data("old"));
		});
	});


  

	$("#input_tipocompeticao").on("change", function(){
		let tipo = $(this).val();
		if(tipo == "0"){
			$("#secao_misto").slideDown(200);
			$("#secao_pontoscorridos").slideUp(200);
		} else if(tipo == "2"){
			$("#secao_misto").slideUp(200);
			$("#secao_pontoscorridos").slideDown(200);
		} else {
			$("#secao_misto").slideUp(200);
			$("#secao_pontoscorridos").slideUp(200);
		}
	});

        // Inicializar Select2
        $('.select2-multiple').select2({
            placeholder: "Selecione..."
        });

        $('#salvar').click(function(){
            
			var formData = new FormData();
            
			let numero_times = $("#input_numerotimes").val();
			let data_limite = $("#input_datalimite").val();
			let subir_live = $("#input_subirjogoslive").prop("checked") * 1;
			let sorteio = $("#input_sorteio").val();
			let gol_fora = $("#input_golfora").prop("checked") * 1;
			let final_unica = $("#input_finalunica").prop("checked") * 1;
			let tipo_competicao = $("#input_tipocompeticao").val();
			let num_grupos = $("#input_numgrupos").val();
			let times_por_grupo = $("#input_timesporgrupo").val();
			let tipo_preliminar = $("#input_tipopreliminar").val();
			let turnos_pontos_corridos = $("#input_turnospontoscorridos").val();
			let criterio_desempate = $("#input_criteriosdesempate").val();
			let criterio_desempate_final = $("#input_criteriosdesempatefinal").val();
			let criterio_suspensao = $("#input_criteriossuspensao").val();
			let zerar_amarelos = $("#input_zeraamarelos").val();
			let permitir_alteracoes = $("#input_permitiralteracoes").prop("checked") * 1;
			let inicio_alteracoes = $("#input_datainicioalteracoes").val();
			let fim_alteracoes = $("#input_datafimalteracoes").val();
			let numero_alteracoes = $("#input_numeroalteracoes").val();
			if(numero_alteracoes == "") numero_alteracoes = 0;

			let estadios_times = $("#input_estadiostimes").prop("checked") * 1;
			let arbitros_pais = $("#select_arbitros_pais").val();
			let arbitros_federacao = $("#select_arbitros_fed").val();
			
			let desempate_grupos = [
				$("#desempate_grupo_1").val(),
				$("#desempate_grupo_2").val(),
				$("#desempate_grupo_3").val(),
				$("#desempate_grupo_4").val()
			].join(',');
        
			formData.append('numero_times',numero_times);
			formData.append('data_limite',data_limite);
			formData.append('subir_live',subir_live);
			formData.append('sorteio',sorteio);
			formData.append('gol_fora',gol_fora);
			formData.append('final_unica',final_unica);
			formData.append('tipo_competicao',tipo_competicao);
			formData.append('num_grupos',num_grupos);
			formData.append('times_por_grupo',times_por_grupo);
			formData.append('tipo_preliminar',tipo_preliminar);
			formData.append('turnos_pontos_corridos',turnos_pontos_corridos);
			formData.append('criterio_desempate',criterio_desempate);
			formData.append('criterio_desempate_final',criterio_desempate_final);
			formData.append('criterio_suspensao',criterio_suspensao);
			formData.append('zerar_amarelos',zerar_amarelos);
			formData.append('permitir_alteracoes',permitir_alteracoes);
			formData.append('inicio_alteracoes',inicio_alteracoes);
			formData.append('fim_alteracoes',fim_alteracoes);
			formData.append('numero_alteracoes',numero_alteracoes);
			formData.append('id_competicao',idCompeticao);
			formData.append('estadios_times',estadios_times);
			formData.append('arbitros_pais',arbitros_pais);
			formData.append('arbitros_federacao',arbitros_federacao);
			formData.append('desempate_grupos',desempate_grupos);

			// Árbitros selecionados
			let selectedArbs = $("#select_arbitros").val() || [];
			selectedArbs.forEach(function(val) {
				formData.append('arbitros[]', val);
			});

			// Estádios selecionados
			let selectedEsts = $("#select_estadios").val() || [];
			selectedEsts.forEach(function(val) {
				formData.append('estadios[]', val);
			});

			
			// Display the key/value pairs
			for (var pair of formData.entries()) {
				// console.log(pair[0]+ ', ' + pair[1]); 
			}

          $.ajax({
              method: "POST",
              url: "/competicoes/alteraropcoes.php",
              cache: false,
              contentType: false,
              processData: false,
              data: formData,
              dataType    : 'json',
            }).done(function(data) {
                
                window.location.href = 'competitionstatus.php?id=' + idCompeticao;
          }).fail(function(data) {
              // console.log(data);
            $('#errorbox').html(data.error_msg);
          });
          
            
        });



});
</script>


<?php

} else {
  
  echo "Usuário, por favor refaça o login!";
}

require_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
