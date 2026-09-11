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

// Valores padrão do Hexacolor
$hexa_balizamento = 0;
$hexa_var = 1;
$hexa_subs = 3;
$hexa_paradas = 3;
$hexa_sub_extra = 1;
$hexa_limitar_lesoes = 0;
$hexa_tempo_limite = 180;
$hexa_cor1 = '#ffffff';
$hexa_cor2 = '#000000';
$hexa_cor3 = '#000000';

$hexa_gols = 10;
$hexa_faltas = 10;
$hexa_impedimentos = 5;
$hexa_cartoes = 5;
$hexa_estilo = 3;
$hexa_bandeiras = 1;

if ($sdb) {
    try {
        $sqliteDb->prepareTables();
        $selectedArbitros = array_column($sdb->query("SELECT ID FROM trioarbitragem")->fetchAll(PDO::FETCH_ASSOC), 'ID');
    } catch(Exception $e) {}
    try {
        $selectedEstadios = array_column($sdb->query("SELECT ID FROM estadio")->fetchAll(PDO::FETCH_ASSOC), 'ID');
    } catch(Exception $e) {}
    
    // Carregar tabela opcoes do Hexacolor
    try {
        $stmtHexaOp = $sdb->query("SELECT parametro, valor, valorLong FROM opcoes");
        if ($stmtHexaOp) {
            $rowsHexaOp = $stmtHexaOp->fetchAll(PDO::FETCH_ASSOC);
            $opMap = [];
            foreach ($rowsHexaOp as $r) {
                $opMap[$r['parametro']] = $r['valor'];
            }
            if (isset($opMap['balizamento'])) $hexa_balizamento = intval($opMap['balizamento']);
            if (isset($opMap['VAR'])) $hexa_var = intval($opMap['VAR']);
            if (isset($opMap['substituicoes'])) $hexa_subs = intval($opMap['substituicoes']);
            if (isset($opMap['paradas'])) $hexa_paradas = intval($opMap['paradas']);
            if (isset($opMap['subExtraProrrogacao'])) $hexa_sub_extra = intval($opMap['subExtraProrrogacao']);
            if (isset($opMap['limitarLesoes'])) $hexa_limitar_lesoes = intval($opMap['limitarLesoes']);
            if (isset($opMap['tempoLimite'])) $hexa_tempo_limite = intval($opMap['tempoLimite']);
            
            // Cores (ARGB decimal para hex)
            if (isset($opMap['partidaCor1'])) {
                $val = intval($opMap['partidaCor1']);
                $hexa_cor1 = sprintf('#%06X', $val & 0xFFFFFF);
            }
            if (isset($opMap['partidaCor2'])) {
                $val = intval($opMap['partidaCor2']);
                $hexa_cor2 = sprintf('#%06X', $val & 0xFFFFFF);
            }
            if (isset($opMap['partidaCor3'])) {
                $val = intval($opMap['partidaCor3']);
                $hexa_cor3 = sprintf('#%06X', $val & 0xFFFFFF);
            }
        }
    } catch(Exception $e) {}

    // Carregar tabela parametros e paispadrao do Hexacolor
    try {
        $stmtHexaParam = $sdb->query("SELECT * FROM parametros WHERE padrao = 1 LIMIT 1");
        if ($stmtHexaParam) {
            $rowParam = $stmtHexaParam->fetch(PDO::FETCH_ASSOC);
            if ($rowParam) {
                $hexa_gols = intval($rowParam['Gols']);
                $hexa_faltas = intval($rowParam['Faltas']);
                $hexa_impedimentos = intval($rowParam['Impedimentos']);
                $hexa_cartoes = intval($rowParam['Cartoes']);
                if (isset($rowParam['Chao'])) {
                    $chaoVal = floatval($rowParam['Chao']);
                    $calcEstilo = round((1.6 - $chaoVal) / 0.2);
                    if ($calcEstilo >= 1 && $calcEstilo <= 5) {
                        $hexa_estilo = (int)$calcEstilo;
                    }
                }
            }
        }
        $stmtHexaPais = $sdb->query("SELECT ExibirBandeiras FROM paispadrao LIMIT 1");
        if ($stmtHexaPais) {
            $rowPais = $stmtHexaPais->fetch(PDO::FETCH_ASSOC);
            if ($rowPais && isset($rowPais['ExibirBandeiras'])) {
                $hexa_bandeiras = intval($rowPais['ExibirBandeiras']);
            }
        }
    } catch(Exception $e) {}
}

// Carregar perfis do usuário no MySQL para preenchimento rápido opcional
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/parametros.php");
$parametroObj = new Parametro($db);
$stmtUserProfiles = $parametroObj->readAll(0, 100, $_SESSION['user_id']);
$userProfiles = $stmtUserProfiles ? $stmtUserProfiles->fetchAll(PDO::FETCH_ASSOC) : [];

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
		<input type='number' min='2' max='128' name='input_numerotimes' id='input_numerotimes' value='<?php echo $options['numero_times']?>'/>
		
		<label for='input_datalimite'>Limite para envio de fichas</label>
		<input type='date' name='input_datalimite' id='input_datalimite' value='<?php echo $options['limite_fichas']?>'/>
		<small style="color: #64748b; font-size: 0.8rem; display: block; margin-top: 4px;">Deixe em branco caso não queira estipular data limite para o envio de fichas.</small>
		
		<label for='input_sorteio' style="margin-top: 15px;">Sorteio</label>
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

		<!-- Configurações específicas para Torneio Misto (Fase de Grupos + Mata-Mata) -->
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
			<small id="aviso_capacidade_grupos" style="color: #64748b; font-size: 0.8rem; display: block; margin-top: 6px;">Capacidade dos grupos: <strong id="val_capacidade_grupos">16</strong> times.</small>

			<div class="form-grid-3" style="margin-top: 14px;">
				<div class="form-field-aligned">
					<label for='input_preliminar_formato'>Fase Preliminar (Se houver excedente)</label>
					<select name='input_preliminar_formato' id='input_preliminar_formato'>
						<option value='0' <?php echo (!isset($options['tipo_preliminar']) || $options['tipo_preliminar']==0) ? "selected" : ""; ?>>Apenas Ida (Jogo Único)</option>
						<option value='1' <?php echo (isset($options['tipo_preliminar']) && $options['tipo_preliminar']==1) ? "selected" : ""; ?>>Ida e Volta</option>
					</select>
				</div>
				<div class="form-field-aligned">
					<label for='input_turnosgrupos'>Jogos da Fase de Grupos</label>
					<select name='input_turnosgrupos' id='input_turnosgrupos'>
						<option value='0' <?php echo (isset($options['turnos_pontos_corridos']) && intval($options['turnos_pontos_corridos']) == 1) ? "selected" : ""; ?>>Apenas Ida (1 Turno)</option>
						<option value='1' <?php echo (!isset($options['turnos_pontos_corridos']) || intval($options['turnos_pontos_corridos']) >= 2) ? "selected" : ""; ?>>Ida e Volta (2 Turnos)</option>
					</select>
				</div>
				<div class="form-field-aligned">
					<label for='input_matamata_formato_misto'>Jogos do Mata-Mata (Pós-Grupos)</label>
					<select name='input_matamata_formato_misto' id='input_matamata_formato_misto'>
						<option value='0' <?php echo (!isset($options['tipo_preliminar']) || $options['tipo_preliminar']==0) ? "selected" : ""; ?>>Apenas Ida (Jogo Único)</option>
						<option value='1' <?php echo (isset($options['tipo_preliminar']) && $options['tipo_preliminar']==1) ? "selected" : ""; ?>>Ida e Volta</option>
					</select>
				</div>
			</div>

			<label class="checkbox-label" for='input_finalunica' style="margin-top: 12px;">
				<input type='checkbox' name='input_finalunica' id='input_finalunica' <?php echo ($options['finalunica']?"checked":"")?> />
				Final em jogo único
			</label>
		</div>

		<!-- Configurações específicas para Torneio Mata-Mata -->
		<div id="secao_matamata" style="<?php echo ($options['tipocompeticao']==1 ? '' : 'display:none;'); ?> background: rgba(2, 132, 199, 0.04); border: 1px dashed rgba(2, 132, 199, 0.25); border-radius: 8px; padding: 15px; margin-top: 15px;">
			<label for='input_matamata_formato' style="margin-top:0;">Formato dos Confrontos</label>
			<select name='input_matamata_formato' id='input_matamata_formato'>
				<option value='0' <?php echo (!isset($options['tipo_preliminar']) || $options['tipo_preliminar']==0) ? "selected" : ""; ?>>Apenas Ida (Jogo Único)</option>
				<option value='1' <?php echo (isset($options['tipo_preliminar']) && $options['tipo_preliminar']==1) ? "selected" : ""; ?>>Ida e Volta</option>
			</select>

			<label class="checkbox-label" for='input_finalunica_matamata' style="margin-top: 12px;">
				<input type='checkbox' name='input_finalunica_matamata' id='input_finalunica_matamata' <?php echo ($options['finalunica']?"checked":"")?> />
				Final em jogo único
			</label>
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

		<label class="checkbox-label" for='input_subirjogoslive' style="margin-top: 15px;">
			<input type='checkbox' name='input_subirjogoslive' id='input_subirjogoslive' <?php echo ($options['subir_live']?"checked":"")?> />
			Subir jogos no Live?
		</label>
	</div>

	<!-- Regras de Desempate -->
	<div class="opcoes-secao">
		<h3 class="opcoes-secao-titulo">
			<span class="material-symbols-outlined">sports_soccer</span>
			Regras de Desempate
		</h3>
		
		<?php
		$desempate_grupos = isset($options['desempate_grupos']) ? $options['desempate_grupos'] : 'SG,GP,VI,CD';
		$criterios_selecionados = explode(',', $desempate_grupos);
		while (count($criterios_selecionados) < 4) {
			$criterios_selecionados[] = 'SG';
		}
		?>
		<div id="secao_desempate_grupos" style="<?php echo ($options['tipocompeticao'] == 1 ? 'display:none;' : ''); ?>">
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
		</div>

		<div id="secao_desempate_matamata" style="<?php echo ($options['tipocompeticao'] == 2 ? 'display:none;' : ''); ?>">
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

			<label class="checkbox-label" for='input_golfora' style="margin-top: 15px;">
				<input type='checkbox' name='input_golfora' id='input_golfora' <?php echo ($options['golfora']?"checked":"")?>/>
				Desempate por gol fora de casa
			</label>
		</div>
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

		<label for='input_expulso_dois_amarelos'>Ao ser expulso por 2 amarelos no mesmo jogo</label>
		<select name='input_expulso_dois_amarelos' id='input_expulso_dois_amarelos'>
			<option value='0' <?php echo (!isset($options['expulso_dois_amarelos']) || $options['expulso_dois_amarelos']==0) ? "selected" : ""; ?>>Não contabilizar cartões amarelos (Padrão)</option>
			<option value='1' <?php echo (isset($options['expulso_dois_amarelos']) && $options['expulso_dois_amarelos']==1) ? "selected" : ""; ?>>Contabilizar 1 cartão amarelo</option>
			<option value='2' <?php echo (isset($options['expulso_dois_amarelos']) && $options['expulso_dois_amarelos']==2) ? "selected" : ""; ?>>Contabilizar os 2 cartões amarelos</option>
		</select>
		<small style="color: #64748b; font-size: 0.8rem; display: block; margin-top: 4px; margin-bottom: 5px;">Define quantos amarelos permanecem no acúmulo individual do jogador após a suspensão pelo cartão vermelho.</small>
	</div>

	<!-- Calendário & Agendamento de Jogos -->
	<div class="opcoes-secao">
		<h3 class="opcoes-secao-titulo">
			<span class="material-symbols-outlined">calendar_month</span>
			Calendário & Agendamento de Jogos
		</h3>
		
		<div style="display: flex; gap: 15px; flex-wrap: wrap;">
			<div style="flex: 1; min-width: 180px;">
				<label for='input_datainicial' style="margin-top:0;">Data Inicial da Competição</label>
				<input type='date' name='input_datainicial' id='input_datainicial' value='<?php echo isset($options['data_inicial']) ? $options['data_inicial'] : ''; ?>'/>
				<small style="color: #64748b; font-size: 0.8rem; display: block; margin-top: 4px;">Data de início da primeira rodada (deixe em branco para usar a data de hoje).</small>
			</div>
			<div style="flex: 1; min-width: 180px;">
				<label for='input_maxjogosdia' style="margin-top:0;">Máx. Jogos por Dia</label>
				<input type='number' min='0' max='50' name='input_maxjogosdia' id='input_maxjogosdia' value='<?php echo isset($options['max_jogos_dia']) ? (int)$options['max_jogos_dia'] : 0; ?>'/>
				<small style="color: #64748b; font-size: 0.8rem; display: block; margin-top: 4px;">0 para sem limite (todos os jogos da rodada no mesmo dia).</small>
			</div>
		</div>

		<?php
		$dias_configurados = !empty($options['dias_semana']) ? explode(',', $options['dias_semana']) : [];
		$dias_nomes = [
			'0' => 'Domingo',
			'1' => 'Segunda-feira',
			'2' => 'Terça-feira',
			'3' => 'Quarta-feira',
			'4' => 'Quinta-feira',
			'5' => 'Sexta-feira',
			'6' => 'Sábado'
		];
		?>
		<label style="margin-top: 15px;">Dias de Jogos na Semana (Pode escolher mais de um)</label>
		<div class="dias-semana-grid">
			<?php foreach($dias_nomes as $valDia => $nomeDia): ?>
				<label class="dia-semana-item">
					<input type="checkbox" class="chk_dia_semana" value="<?php echo $valDia; ?>" <?php echo in_array((string)$valDia, $dias_configurados) ? 'checked' : ''; ?>>
					<span><?php echo $nomeDia; ?></span>
				</label>
			<?php endforeach; ?>
		</div>
		<small style="color: #64748b; font-size: 0.8rem; display: block; margin-top: -6px; margin-bottom: 12px;">Se nenhum dia for marcado, as partidas serão agendadas consecutivamente.</small>

		<div style="display: flex; gap: 15px; flex-wrap: wrap;">
			<div style="flex: 1; min-width: 180px;">
				<label for='input_intervalorodadas' style="margin-top:0;">Intervalo entre Rodadas (Semanas)</label>
				<input type='number' min='1' max='52' name='input_intervalorodadas' id='input_intervalorodadas' value='<?php echo (isset($options['intervalo_rodadas']) && intval($options['intervalo_rodadas']) > 0) ? intval($options['intervalo_rodadas']) : 1; ?>'/>
			</div>
			<div style="flex: 2; min-width: 220px;">
				<label for='input_horariosjogos' style="margin-top:0;">Horários de Jogos (Separados por vírgula)</label>
				<input type='text' name='input_horariosjogos' id='input_horariosjogos' placeholder='Ex: 16:00, 19:00, 21:30' value='<?php echo htmlspecialchars(isset($options['horarios_jogos']) && $options['horarios_jogos'] !== '' ? $options['horarios_jogos'] : '16:00'); ?>'/>
				<small style="color: #64748b; font-size: 0.8rem; display: block; margin-top: 4px;">Jogos do mesmo dia serão distribuídos alternadamente entre estes horários.</small>
			</div>
		</div>
	</div>

	<!-- Alterações de Elenco (Apenas para Competições Internacionais) -->
	<?php if(isset($info['tipo']) && intval($info['tipo']) != 1): ?>
	<div class="opcoes-secao">
		<h3 class="opcoes-secao-titulo">
			<span class="material-symbols-outlined">group_add</span>
			Alterações de Elenco
		</h3>
		
		<label class="checkbox-label" for='input_permitiralteracoes' style="margin-bottom: 15px;">
			<input type='checkbox' name='input_permitiralteracoes' id='input_permitiralteracoes' <?php echo ($options['alteracoeselenco']?"checked":"")?> />
			Permitir alterações de elenco
		</label>
		
		<div id="secao_detalhes_alteracoes" style="<?php echo ($options['alteracoeselenco'] ? '' : 'display:none;'); ?>">
			<div style="display: flex; gap: 15px; flex-wrap: wrap;">
				<div style="flex: 1; min-width: 180px;">
					<label for='input_datainicioalteracoes' style="margin-top:0;">Início das alterações</label>
					<input type='date' name='input_datainicioalteracoes' id='input_datainicioalteracoes' value='<?php echo $options['inicioalteracoes']?>'/>
				</div>
				<div style="flex: 1; min-width: 180px;">
					<label for='input_datafimalteracoes' style="margin-top:0;">Fim das alterações</label>
					<input type='date' name='input_datafimalteracoes' id='input_datafimalteracoes' value='<?php echo $options['fimalteracoes']?>'/>
				</div>
			</div>
			
			<label for='input_numeroalteracoes' style="margin-top: 15px;">Quantos jogadores adicionais?</label>
			<input type='number' min='1' max='23' name='input_numeroalteracoes' id='input_numeroalteracoes' value='<?php echo $options['jogadoresadicionais']?>'/>
		</div>
	</div>
	<?php endif; ?>

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

	<!-- Opções do Simulador Hexacolor -->
	<div class="opcoes-secao">
		<h3 class="opcoes-secao-titulo">
			<span class="material-symbols-outlined">tune</span>
			Opções do Simulador Hexacolor
		</h3>

		<label for='input_hexa_balizamento'>Balizamento de Simulação</label>
		<select name='input_hexa_balizamento' id='input_hexa_balizamento'>
			<option value='0' <?php echo ($hexa_balizamento == 0 ? 'selected' : ''); ?>>Balizamento Tradicional (Padrão)</option>
			<option value='1' <?php echo ($hexa_balizamento == 1 ? 'selected' : ''); ?>>Balizamento Internacional 2022</option>
		</select>

		<div class="balizamento-help-box" id="balizamento-desc-0" style="<?php echo ($hexa_balizamento == 0 ? '' : 'display: none;'); ?>">
			<strong>Balizamento Tradicional:</strong>
			<ul>
				<li>Mais propenso a zebras; goleadas entre equipes discrepantes são muito mais moderadas.</li>
				<li>Equipes são consideradas parelhas quando a diferença de nível é inferior a 3.</li>
				<li>Chance realista de vitória com diferença até 15 níveis; placares elásticos ocorrem apenas com grandes distâncias.</li>
			</ul>
		</div>

		<div class="balizamento-help-box" id="balizamento-desc-1" style="<?php echo ($hexa_balizamento == 1 ? '' : 'display: none;'); ?>">
			<strong>Balizamento Internacional 2022:</strong>
			<ul>
				<li>Muito menos propenso a zebras; goleadas entre equipes discrepantes são muito mais expressivas e elásticas.</li>
				<li>Equipes são consideradas parelhas apenas com diferença de nível inferior a 2.</li>
				<li>Diferenças acima de 40 níveis geram placares de ~15 gols; diferenças extremas (>80) chegam a ~30 gols.</li>
			</ul>
		</div>

		<div style="display: flex; gap: 15px; flex-wrap: wrap;">
			<div style="flex: 1; min-width: 140px;">
				<label for='input_hexa_subs' style="margin-top:0;">Número de Substituições</label>
				<input type='number' min='1' max='7' name='input_hexa_subs' id='input_hexa_subs' value='<?php echo $hexa_subs; ?>' />
			</div>
			<div style="flex: 1; min-width: 140px;">
				<label for='input_hexa_paradas' style="margin-top:0;">Paradas para Substituição</label>
				<input type='number' min='1' max='5' name='input_hexa_paradas' id='input_hexa_paradas' value='<?php echo $hexa_paradas; ?>' />
			</div>
		</div>

		<label for='input_hexa_sub_extra' style="margin-top: 15px;">
			Substituição extra na prorrogação?
			<input type='checkbox' name='input_hexa_sub_extra' id='input_hexa_sub_extra' <?php echo ($hexa_sub_extra ? 'checked' : ''); ?> />
		</label>

		<label for='input_hexa_var' style="margin-top: 15px;">
			Utilizar VAR (Árbitro de Vídeo)?
			<input type='checkbox' name='input_hexa_var' id='input_hexa_var' <?php echo ($hexa_var ? 'checked' : ''); ?> />
		</label>

		<label for='input_hexa_limitar_lesoes' style="margin-top: 15px;">
			Limitar tempo máximo de lesões?
			<input type='checkbox' name='input_hexa_limitar_lesoes' id='input_hexa_limitar_lesoes' <?php echo ($hexa_limitar_lesoes ? 'checked' : ''); ?> />
		</label>

		<div id="secao_tempo_lesao" style="<?php echo ($hexa_limitar_lesoes ? '' : 'display: none;'); ?> margin-top: 10px;">
			<label for='input_hexa_tempo_limite' style="margin-top: 0;">Tempo limite de lesão (em dias)</label>
			<input type='number' min='1' max='365' name='input_hexa_tempo_limite' id='input_hexa_tempo_limite' value='<?php echo $hexa_tempo_limite; ?>' />
		</div>

		<label style="margin-top: 20px;">Esquema de Cores do Simulador</label>
		<div class="hexa-cores-grid">
			<div class="hexa-cor-item">
				<label for='input_hexa_cor1'>Cor 1 (Fundo)</label>
				<input type='color' id='input_hexa_cor1' name='input_hexa_cor1' value='<?php echo $hexa_cor1; ?>' />
			</div>
			<div class="hexa-cor-item">
				<label for='input_hexa_cor2'>Cor 2 (Texto)</label>
				<input type='color' id='input_hexa_cor2' name='input_hexa_cor2' value='<?php echo $hexa_cor2; ?>' />
			</div>
			<div class="hexa-cor-item">
				<label for='input_hexa_cor3'>Cor 3 (Destaque)</label>
				<input type='color' id='input_hexa_cor3' name='input_hexa_cor3' value='<?php echo $hexa_cor3; ?>' />
			</div>
		</div>
	</div>

	<!-- Parâmetros de Jogo Hexacolor -->
	<div class="opcoes-secao">
		<h3 class="opcoes-secao-titulo">
			<span class="material-symbols-outlined">speed</span>
			Parâmetros de Jogo Hexacolor
		</h3>

		<?php if(!empty($userProfiles)): ?>
			<label for='select_perfil_hymt' style="margin-top: 0;">Carregar Preset de Perfil HYMT (Opcional)</label>
			<select id='select_perfil_hymt'>
				<option value=''>Selecione para carregar perfil...</option>
				<?php foreach($userProfiles as $prof): ?>
					<option value='<?php echo $prof['ID']; ?>'
						data-gols='<?php echo $prof['Gols']; ?>'
						data-faltas='<?php echo $prof['Faltas']; ?>'
						data-impedimentos='<?php echo $prof['Impedimentos']; ?>'
						data-cartoes='<?php echo $prof['Cartoes']; ?>'
						data-estilo='<?php echo $prof['Estilo']; ?>'
						data-bandeiras='<?php echo $prof['ExibirBandeiras']; ?>'>
						<?php echo htmlspecialchars($prof['Nome']); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<small style="color: #64748b; font-size: 0.8rem; display: block; margin-top: 4px; margin-bottom: 15px;">Ao selecionar um perfil, os sliders abaixo serão preenchidos automaticamente com os valores configurados.</small>
		<?php endif; ?>

		<div class="slider-group-header">
			<label for='input_hexa_gols'>Frequência de Gols (1 a 20)</label>
			<span class="slider-badge-val" id="val_hexa_gols"><?php echo $hexa_gols; ?></span>
		</div>
		<input type='range' class='range-slider-control' min='1' max='20' id='input_hexa_gols' value='<?php echo $hexa_gols; ?>' />

		<div class="slider-group-header">
			<label for='input_hexa_faltas'>Frequência de Faltas (1 a 20)</label>
			<span class="slider-badge-val" id="val_hexa_faltas"><?php echo $hexa_faltas; ?></span>
		</div>
		<input type='range' class='range-slider-control' min='1' max='20' id='input_hexa_faltas' value='<?php echo $hexa_faltas; ?>' />

		<div class="slider-group-header">
			<label for='input_hexa_impedimentos'>Frequência de Impedimentos (1 a 10)</label>
			<span class="slider-badge-val" id="val_hexa_impedimentos"><?php echo $hexa_impedimentos; ?></span>
		</div>
		<input type='range' class='range-slider-control' min='1' max='10' id='input_hexa_impedimentos' value='<?php echo $hexa_impedimentos; ?>' />

		<div class="slider-group-header">
			<label for='input_hexa_cartoes'>Frequência de Cartões (1 a 10)</label>
			<span class="slider-badge-val" id="val_hexa_cartoes"><?php echo $hexa_cartoes; ?></span>
		</div>
		<input type='range' class='range-slider-control' min='1' max='10' id='input_hexa_cartoes' value='<?php echo $hexa_cartoes; ?>' />

		<label for='input_hexa_estilo' style="margin-top: 15px;">Estilo de Jogo Predominante</label>
		<select id='input_hexa_estilo' name='input_hexa_estilo'>
			<option value='1' <?php echo ($hexa_estilo == 1 ? 'selected' : ''); ?>>1 - Pelo chão</option>
			<option value='2' <?php echo ($hexa_estilo == 2 ? 'selected' : ''); ?>>2 - Mais pelo chão</option>
			<option value='3' <?php echo ($hexa_estilo == 3 ? 'selected' : ''); ?>>3 - Intermediário (Padrão)</option>
			<option value='4' <?php echo ($hexa_estilo == 4 ? 'selected' : ''); ?>>4 - Mais pelo alto</option>
			<option value='5' <?php echo ($hexa_estilo == 5 ? 'selected' : ''); ?>>5 - Pelo alto</option>
		</select>

		<label for='input_hexa_bandeiras' style="margin-top: 15px;">
			Exibir bandeiras das nacionalidades no simulador?
			<input type='checkbox' name='input_hexa_bandeiras' id='input_hexa_bandeiras' <?php echo ($hexa_bandeiras ? 'checked' : ''); ?> />
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
	
	$("#input_numerotimes").on("change blur", function(){
		let val = parseInt($(this).val());
		if(isNaN(val) || val < 2) {
			$(this).val(2);
		} else if(val > 128) {
			$(this).val(128);
		}
	});
	
	$("#input_numeroalteracoes").on("change blur", function(){
		let val = parseInt($(this).val());
		if(!isNaN(val)) {
			if(val < 0) $(this).val(0);
			else if(val > 23) $(this).val(23);
		}
	});


  

	function atualizarCapacidadeGrupos() {
		let nGrupos = parseInt($("#input_numgrupos").val()) || 0;
		let tGrupo = parseInt($("#input_timesporgrupo").val()) || 0;
		let cap = nGrupos * tGrupo;
		$("#val_capacidade_grupos").text(cap);
		let nTimes = parseInt($("#input_numerotimes").val()) || 0;
		if (cap > nTimes && $("#input_tipocompeticao").val() == "0") {
			$("#aviso_capacidade_grupos").css("color", "#ef4444").html("Atenção: A capacidade dos grupos (" + cap + " times) é maior que o número total de times (" + nTimes + ")!");
		} else {
			$("#aviso_capacidade_grupos").css("color", "#64748b").html("Capacidade dos grupos: <strong id='val_capacidade_grupos'>" + cap + "</strong> times (Grupos x Times por grupo).");
		}
	}

	$("#input_numgrupos, #input_timesporgrupo, #input_numerotimes").on("input change", function(){
		atualizarCapacidadeGrupos();
	});
	atualizarCapacidadeGrupos();

	$("#input_tipocompeticao").on("change", function(){
		let tipo = $(this).val();
		if(tipo == "0"){
			$("#secao_misto").slideDown(200);
			$("#secao_matamata").slideUp(200);
			$("#secao_pontoscorridos").slideUp(200);
			$("#secao_desempate_grupos").slideDown(200);
			$("#secao_desempate_matamata").slideDown(200);
		} else if(tipo == "1"){
			$("#secao_misto").slideUp(200);
			$("#secao_matamata").slideDown(200);
			$("#secao_pontoscorridos").slideUp(200);
			$("#secao_desempate_grupos").slideUp(200);
			$("#secao_desempate_matamata").slideDown(200);
		} else if(tipo == "2"){
			$("#secao_misto").slideUp(200);
			$("#secao_matamata").slideUp(200);
			$("#secao_pontoscorridos").slideDown(200);
			$("#secao_desempate_grupos").slideDown(200);
			$("#secao_desempate_matamata").slideUp(200);
		}
		atualizarCapacidadeGrupos();
	});

	$("#input_hexa_balizamento").on("change", function(){
		let bVal = $(this).val();
		if(bVal == "1"){
			$("#balizamento-desc-0").hide();
			$("#balizamento-desc-1").slideDown(200);
		} else {
			$("#balizamento-desc-1").hide();
			$("#balizamento-desc-0").slideDown(200);
		}
	});

	$("#input_hexa_limitar_lesoes").on("change", function(){
		if($(this).is(":checked")){
			$("#secao_tempo_lesao").slideDown(200);
		} else {
			$("#secao_tempo_lesao").slideUp(200);
		}
	});

	// Sincronização dos Sliders do Hexacolor com os Badges
	$("#input_hexa_gols").on("input change", function(){
		$("#val_hexa_gols").text($(this).val());
	});
	$("#input_hexa_faltas").on("input change", function(){
		$("#val_hexa_faltas").text($(this).val());
	});
	$("#input_hexa_impedimentos").on("input change", function(){
		$("#val_hexa_impedimentos").text($(this).val());
	});
	$("#input_hexa_cartoes").on("input change", function(){
		$("#val_hexa_cartoes").text($(this).val());
	});

	// Carregar preset de perfil HYMT
	$("#select_perfil_hymt").on("change", function(){
		let opt = $(this).find("option:selected");
		if(opt.val() !== ""){
			let gols = opt.data("gols");
			let faltas = opt.data("faltas");
			let imp = opt.data("impedimentos");
			let car = opt.data("cartoes");
			let estilo = opt.data("estilo");
			let ban = opt.data("bandeiras");

			if(gols !== undefined) {
				$("#input_hexa_gols").val(gols).trigger("change");
			}
			if(faltas !== undefined) {
				$("#input_hexa_faltas").val(faltas).trigger("change");
			}
			if(imp !== undefined) {
				$("#input_hexa_impedimentos").val(imp).trigger("change");
			}
			if(car !== undefined) {
				$("#input_hexa_cartoes").val(car).trigger("change");
			}
			if(estilo !== undefined) {
				$("#input_hexa_estilo").val(estilo);
			}
			if(ban !== undefined) {
				$("#input_hexa_bandeiras").prop("checked", ban == 1);
			}
		}
	});

	// Inicializar Select2
	$('.select2-multiple').select2({
		placeholder: "Selecione..."
	});

	// Toggle da seção de alterações de elenco
	$("#input_permitiralteracoes").on("change", function(){
		if($(this).is(":checked")){
			$("#secao_detalhes_alteracoes").slideDown(200);
		} else {
			$("#secao_detalhes_alteracoes").slideUp(200);
		}
	});

	// Swap inteligente entre os 4 critérios de desempate
	const desempateSelects = ["#desempate_grupo_1", "#desempate_grupo_2", "#desempate_grupo_3", "#desempate_grupo_4"];
	
	desempateSelects.forEach(function(selId){
		$(selId).data("prev", $(selId).val());
		$(selId).on("focus", function(){
			$(this).data("prev", $(this).val());
		});
		$(selId).on("change", function(){
			let newVal = $(this).val();
			let oldVal = $(this).data("prev");
			let currSel = this;

			desempateSelects.forEach(function(otherId){
				let otherEl = $(otherId)[0];
				if(otherEl !== currSel && $(otherId).val() === newVal) {
					$(otherId).val(oldVal);
					$(otherId).data("prev", oldVal);
				}
			});
			$(currSel).data("prev", newVal);
		});
	});

	$('#salvar').click(function(){
		var formData = new FormData();
		
		let numero_times = parseInt($("#input_numerotimes").val()) || 2;
		let data_limite = $("#input_datalimite").val();
		let subir_live = $("#input_subirjogoslive").prop("checked") * 1;
		let sorteio = $("#input_sorteio").val();
		let gol_fora = $("#input_golfora").prop("checked") * 1;
		let tipo_competicao = $("#input_tipocompeticao").val();
		let num_grupos = parseInt($("#input_numgrupos").val()) || 4;
		let times_por_grupo = parseInt($("#input_timesporgrupo").val()) || 4;
		
		let final_unica = 0;
		let tipo_preliminar = 1;
		let turnos_pontos_corridos = 2;

		if (tipo_competicao == "0") { // Misto
			let capGrupos = num_grupos * times_por_grupo;
			if (capGrupos > numero_times) {
				alert("Atenção: O número total de vagas nos grupos (" + num_grupos + " grupos x " + times_por_grupo + " times = " + capGrupos + ") não pode ser maior que o número total de times da competição (" + numero_times + "). Por favor, ajuste o número de grupos ou times por grupo.");
				return false;
			}
			turnos_pontos_corridos = parseInt($("#input_turnosgrupos").val()) == 0 ? 1 : 2; // 1 = 1 turno (apenas ida), 2 = 2 turnos (ida e volta)
			tipo_preliminar = parseInt($("#input_preliminar_formato").val()) || 0; // 0 = apenas ida, 1 = ida e volta
			final_unica = $("#input_finalunica").prop("checked") * 1;
		} else if (tipo_competicao == "1") { // Mata-Mata
			tipo_preliminar = parseInt($("#input_matamata_formato").val()) || 0; // 0 = apenas ida, 1 = ida e volta
			final_unica = $("#input_finalunica_matamata").prop("checked") * 1;
		} else if (tipo_competicao == "2") { // Pontos Corridos
			turnos_pontos_corridos = parseInt($("#input_turnospontoscorridos").val()) || 2;
		}

		let criterio_desempate = $("#input_criteriosdesempate").val();
		let criterio_desempate_final = $("#input_criteriosdesempatefinal").val();
		let criterio_suspensao = $("#input_criteriossuspensao").val();
		let zerar_amarelos = $("#input_zeraamarelos").val();
		let expulso_dois_amarelos = $("#input_expulso_dois_amarelos").val();
		let permitir_alteracoes = $("#input_permitiralteracoes").prop("checked") * 1;
		let inicio_alteracoes = $("#input_datainicioalteracoes").val();
		let fim_alteracoes = $("#input_datafimalteracoes").val();
		let numero_alteracoes = $("#input_numeroalteracoes").val();
		if(numero_alteracoes == "") numero_alteracoes = 0;

		let data_inicial = $("#input_datainicial").val();
		
		// Validação das datas de alterações de elenco
		if (permitir_alteracoes == 1) {
			if (inicio_alteracoes !== "" && data_inicial !== "" && inicio_alteracoes < data_inicial) {
				alert("Atenção: A data de início das alterações de elenco (" + inicio_alteracoes + ") deve ser posterior ou igual à data de início da competição (" + data_inicial + ").");
				return false;
			}
			if (inicio_alteracoes !== "" && fim_alteracoes !== "" && fim_alteracoes < inicio_alteracoes) {
				alert("Atenção: A data de término das alterações de elenco (" + fim_alteracoes + ") deve ser posterior ou igual à data de início das alterações (" + inicio_alteracoes + ").");
				return false;
			}
		}

		let max_jogos_dia = $("#input_maxjogosdia").val();
		let intervalo_rodadas = $("#input_intervalorodadas").val();
		let horarios_jogos = $("#input_horariosjogos").val();
		let dias_semana_arr = [];
		$(".chk_dia_semana:checked").each(function(){
			dias_semana_arr.push($(this).val());
		});
		let dias_semana = dias_semana_arr.join(',');

		let estadios_times = $("#input_estadiostimes").prop("checked") * 1;
		let arbitros_pais = $("#select_arbitros_pais").val();
		let arbitros_federacao = $("#select_arbitros_fed").val();
		
		let desempate_grupos = [
			$("#desempate_grupo_1").val(),
			$("#desempate_grupo_2").val(),
			$("#desempate_grupo_3").val(),
			$("#desempate_grupo_4").val()
		].join(',');

		// Opções e Parâmetros Hexacolor
		let hexa_balizamento = $("#input_hexa_balizamento").val();
		let hexa_subs = $("#input_hexa_subs").val();
		let hexa_paradas = $("#input_hexa_paradas").val();
		let hexa_sub_extra = $("#input_hexa_sub_extra").prop("checked") * 1;
		let hexa_var = $("#input_hexa_var").prop("checked") * 1;
		let hexa_limitar_lesoes = $("#input_hexa_limitar_lesoes").prop("checked") * 1;
		let hexa_tempo_limite = $("#input_hexa_tempo_limite").val();
		let hexa_cor1 = $("#input_hexa_cor1").val();
		let hexa_cor2 = $("#input_hexa_cor2").val();
		let hexa_cor3 = $("#input_hexa_cor3").val();

		let hexa_gols = $("#input_hexa_gols").val();
		let hexa_faltas = $("#input_hexa_faltas").val();
		let hexa_impedimentos = $("#input_hexa_impedimentos").val();
		let hexa_cartoes = $("#input_hexa_cartoes").val();
		let hexa_estilo = $("#input_hexa_estilo").val();
		let hexa_bandeiras = $("#input_hexa_bandeiras").prop("checked") * 1;
	
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
		formData.append('expulso_dois_amarelos',expulso_dois_amarelos);
		formData.append('permitir_alteracoes',permitir_alteracoes);
		formData.append('inicio_alteracoes',inicio_alteracoes);
		formData.append('fim_alteracoes',fim_alteracoes);
		formData.append('numero_alteracoes',numero_alteracoes);
		formData.append('id_competicao',idCompeticao);
		formData.append('estadios_times',estadios_times);
		formData.append('arbitros_pais',arbitros_pais);
		formData.append('arbitros_federacao',arbitros_federacao);
		formData.append('desempate_grupos',desempate_grupos);
		formData.append('data_inicial',data_inicial);
		formData.append('max_jogos_dia',max_jogos_dia);
		formData.append('dias_semana',dias_semana);
		formData.append('intervalo_rodadas',intervalo_rodadas);
		formData.append('horarios_jogos',horarios_jogos);

		// Hexacolor
		formData.append('hexa_balizamento', hexa_balizamento);
		formData.append('hexa_subs', hexa_subs);
		formData.append('hexa_paradas', hexa_paradas);
		formData.append('hexa_sub_extra', hexa_sub_extra);
		formData.append('hexa_var', hexa_var);
		formData.append('hexa_limitar_lesoes', hexa_limitar_lesoes);
		formData.append('hexa_tempo_limite', hexa_tempo_limite);
		formData.append('hexa_cor1', hexa_cor1);
		formData.append('hexa_cor2', hexa_cor2);
		formData.append('hexa_cor3', hexa_cor3);

		formData.append('hexa_gols', hexa_gols);
		formData.append('hexa_faltas', hexa_faltas);
		formData.append('hexa_impedimentos', hexa_impedimentos);
		formData.append('hexa_cartoes', hexa_cartoes);
		formData.append('hexa_estilo', hexa_estilo);
		formData.append('hexa_bandeiras', hexa_bandeiras);

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

		$.ajax({
			method: "POST",
			url: "/competicoes/alteraropcoes.php",
			cache: false,
			contentType: false,
			processData: false,
			data: formData,
			dataType: 'json',
		}).done(function(data) {
			window.location.href = 'competitionstatus.php?id=' + idCompeticao;
		}).fail(function(data) {
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
