<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$idCompeticao = $_GET['id'];

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
$federacao_nome = $info['federacao'];
$logo_competicao = $info['logo'];
$total_times = $info['total_times'];
$times_inseridos = $info['times_inseridos'];

$all_options = $competicao->checkOptionsFilled($idCompeticao);

// Consultar status real dos jogos da competição
$stmtJogosStatus = $db->prepare("
    SELECT 
        COUNT(*) as total_jogos,
        SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as jogos_simulados,
        SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as jogos_pendentes,
        SUM(CASE WHEN fase = 8 AND status = 1 THEN 1 ELSE 0 END) as final_simulada
    FROM jogos_clube 
    WHERE competicao_id = :idComp AND simulador_interno = 1
");
$stmtJogosStatus->execute([':idComp' => $idCompeticao]);
$statsJogos = $stmtJogosStatus->fetch(PDO::FETCH_ASSOC);

$totalJogos = (int)($statsJogos['total_jogos'] ?? 0);
$jogosSimulados = (int)($statsJogos['jogos_simulados'] ?? 0);
$jogosPendentes = (int)($statsJogos['jogos_pendentes'] ?? 0);
$finalSimulada = (int)($statsJogos['final_simulada'] ?? 0);

if ($all_options != null) {
	$codigo_status_competicao = 0; // Em criação (opções não preenchidas)
} elseif ($times_inseridos < $total_times) {
	$codigo_status_competicao = 1; // Aguardando times
} elseif ($finalSimulada > 0 || ($totalJogos > 0 && $jogosPendentes === 0 && $jogosSimulados > 0)) {
	$codigo_status_competicao = 4; // Finalizada
} elseif ($jogosSimulados > 0) {
	$codigo_status_competicao = 3; // Em andamento (já teve jogos simulados)
} else {
	$codigo_status_competicao = 2; // Pronta para simular
}

$status_competicao = "";
$status_badge_class = "";

switch($codigo_status_competicao){
	case 0:
		$status_competicao = "Em criação";
		$status_badge_class = "status-em-criacao";
		break;
	case 1:
		$status_competicao = "Aguardando times";
		$status_badge_class = "status-aguardando-times";
		break;
	case 2:
		$status_competicao = "Pronta para simular";	
		$status_badge_class = "status-pronta-simular";
		break;
	case 3: 
		$status_competicao = "Em andamento";
		$status_badge_class = "status-em-andamento";
		break;
	case 4:
		$status_competicao = "Finalizada";
		$status_badge_class = "status-finalizada";
		break;
	default:
		$status_competicao = "Status não disponível";
		$status_badge_class = "status-default";
		break;
}

$page_title = "Tela principal - " . $nome_competicao . " " . $ano_competicao ;
$css_filename = "home_redesign";
$css_login = 'login';
$aux_css = 'competition_status_redesign';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

// Calculate percentage for progress bar
$percentual_times = ($total_times > 0) ? min(100, round(($times_inseridos / $total_times) * 100)) : 0;
?>

<main class="status-container">
	
	<!-- Hero Banner -->
	<section class="competition-hero">
		<div class="hero-main-info">
			<img class="hero-logo" src="/images/competicoes/<?php echo $logo_competicao; ?>" alt="Logo Competição" />
			<div class="hero-text-block">
				<h2 class="hero-title"><?php echo $nome_competicao . " " . $ano_competicao; ?></h2>
				<span class="hero-subtitle">
					<span class="material-symbols-outlined">emoji_events</span>
					Painel da Competição
				</span>
			</div>
		</div>
		
		<div class="hero-badge-container">
			<?php if($federacao_nome != "" && $federacao_nome != "0"): ?>
				<img class="hero-federation-logo" src="/images/<?php echo strtolower($federacao_nome); ?>.png" alt="Federação" />
			<?php else: ?>
				<img class="hero-federation-logo" src="/images/confusalogo.png" alt="Federação" />
			<?php endif; ?>

			<?php if($sede_competicao == "flag.png"): ?>
				<span class="no-fixed-venue">Sem sede fixa</span>
			<?php else: ?>
				<img class="hero-country-flag" src="/images/bandeiras/<?php echo $sede_competicao; ?>" alt="Sede" />
			<?php endif; ?>
		</div>
	</section>

	<!-- Progress & Status Info Card -->
	<section class="status-progress-card">
		<div class="status-header-row">
			<div class="teams-count-text">
				<span class="material-symbols-outlined" style="color: #0284c7;">groups</span>
				<span>Times Inseridos:</span>
				<span><?php echo $times_inseridos; ?> / <?php echo $total_times; ?></span>
			</div>
			
			<span class="status-badge-pill <?php echo $status_badge_class; ?>">
				<?php echo $status_competicao; ?>
			</span>
		</div>
		
		<div class="progress-bar-container">
			<div class="progress-bar-fill" style="width: <?php echo $percentual_times; ?>%;"></div>
		</div>
	</section>

	<!-- Action Modules Grid -->
	<section class="redesign-grid">
		
		<!-- 1. Lista de Times -->
		<?php if($codigo_status_competicao != 0): ?>
			<a href="listatimes.php?id=<?php echo $idCompeticao; ?>" class="hub-card">
				<div class="hub-card-hero-image">
					<img src="/images/competicoes/listatimes.webp" alt="Lista de times" />
				</div>
				<div class="hub-card-body">
					<h3 class="hub-card-title">
						<span>Lista de Times</span>
						<span class="material-symbols-outlined hub-card-arrow">arrow_forward</span>
					</h3>
					<p class="hub-card-desc">Gerencie e veja a lista de times participantes desta competição.</p>
				</div>
			</a>
		<?php else: ?>
			<div class="hub-card disabled-card">
				<div class="hub-card-hero-image">
					<img src="/images/competicoes/listatimes.webp" alt="Lista de times" />
				</div>
				<div class="hub-card-body">
					<h3 class="hub-card-title">
						<span>Lista de Times</span>
						<span class="badge-status">Bloqueado</span>
					</h3>
					<p class="hub-card-desc">Disponível assim que as configurações da competição forem concluídas.</p>
				</div>
			</div>
		<?php endif; ?>

		<!-- 2. Opções -->
		<a href="opcoes.php?id=<?php echo $idCompeticao; ?>" class="hub-card">
			<div class="hub-card-hero-image">
				<img src="/images/competicoes/opcoes.webp" alt="Opções" />
			</div>
			<div class="hub-card-body">
				<h3 class="hub-card-title">
					<span>Configurações</span>
					<span class="material-symbols-outlined hub-card-arrow">arrow_forward</span>
				</h3>
				<p class="hub-card-desc">Ajuste os parâmetros, regras e definições básicas do torneio.</p>
			</div>
		</a>

		<!-- 3. Lista de Jogos -->
		<?php if($codigo_status_competicao != 0): ?>
			<a href="listajogos.php?id=<?php echo $idCompeticao; ?>" class="hub-card">
				<div class="hub-card-hero-image">
					<img src="/images/competicoes/listajogos.webp" alt="Lista de Jogos" />
				</div>
				<div class="hub-card-body">
					<h3 class="hub-card-title">
						<span>Tabela & Rodadas</span>
						<span class="material-symbols-outlined hub-card-arrow">arrow_forward</span>
					</h3>
					<p class="hub-card-desc">Acompanhe os confrontos, simulações de partidas e o andamento dos jogos.</p>
				</div>
			</a>
		<?php else: ?>
			<div class="hub-card disabled-card">
				<div class="hub-card-hero-image">
					<img src="/images/competicoes/listajogos.webp" alt="Lista de Jogos" />
				</div>
				<div class="hub-card-body">
					<h3 class="hub-card-title">
						<span>Tabela & Rodadas</span>
						<span class="badge-status">Bloqueado</span>
					</h3>
					<p class="hub-card-desc">Disponível assim que as configurações da competição forem concluídas.</p>
				</div>
			</div>
		<?php endif; ?>

		<!-- 4. Estatísticas -->
		<?php if($codigo_status_competicao != 0): ?>
			<a href="estatisticas.php?id=<?php echo $idCompeticao; ?>" class="hub-card">
				<div class="hub-card-hero-image">
					<img src="/images/competicoes/estatisticas.webp" alt="Estatísticas" />
				</div>
				<div class="hub-card-body">
					<h3 class="hub-card-title">
						<span>Estatísticas</span>
						<span class="material-symbols-outlined hub-card-arrow">arrow_forward</span>
					</h3>
					<p class="hub-card-desc">Veja artilharia, cartões, melhores elencos e desempenho geral.</p>
				</div>
			</a>
		<?php else: ?>
			<div class="hub-card disabled-card">
				<div class="hub-card-hero-image">
					<img src="/images/competicoes/estatisticas.webp" alt="Estatísticas" />
				</div>
				<div class="hub-card-body">
					<h3 class="hub-card-title">
						<span>Estatísticas</span>
						<span class="badge-status">Bloqueado</span>
					</h3>
					<p class="hub-card-desc">Disponível assim que as configurações da competição forem concluídas.</p>
				</div>
			</div>
		<?php endif; ?>

	</section>

	<div style="margin-top: 30px; text-align: center;">
		<a href="index.php" style="display: inline-block; padding: 10px 20px; background: rgba(0, 0, 0, 0.03); border: 1px solid rgba(0, 0, 0, 0.08); border-radius: 8px; color: #475569; text-decoration: none; font-weight: 600; font-size: 0.9rem; transition: background 0.2s;" onmouseover="this.style.background='rgba(0, 0, 0, 0.06)'" onmouseout="this.style.background='rgba(0, 0, 0, 0.03)'">
			← Voltar para Índice de Competições
		</a>
	</div>

</main>

<?php
include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>
