<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
include_once($_SERVER['DOCUMENT_ROOT'] . "/elements/login_info.php");

$page_title = "Simulador de Amistosos de Seleções";
$css_filename = "indexRanking";
$css_login = 'login';
$aux_css = "home_redesign";
$extra_css = "amistosos_redesign";
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT'] . "/elements/header.php");
include_once 'ranking_header.php';

// Conexão com o banco de dados
include_once($_SERVER['DOCUMENT_ROOT'] . "/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/objetos/estadio.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/objetos/arbitros.php");

$database = new Database();
$db = $database->getConnection();

$is_logged_in = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
$is_em_testes = !empty($_SESSION['emTestes']);

// 1. Verificação de Restrição de Acesso
if (!$is_logged_in || $is_em_testes) {
    echo "<div class='amistosos-container'><div class='amistosos-card' style='text-align: center; padding: 3.5rem 1.5rem;'>
        <div style='width: 72px; height: 72px; margin: 0 auto 1.5rem auto; border-radius: 50%; background: rgba(239, 68, 68, 0.1); color: #ef4444; display: flex; align-items: center; justify-content: center;'>
            <span class='material-symbols-outlined' style='font-size: 2.5rem;'>lock</span>
        </div>
        <h2 style='font-family: Outfit, sans-serif; font-size: 1.6rem; color: #0f172a; margin-bottom: 0.5rem;'>Acesso Restrito</h2>
        <p style='color: #64748b; font-size: 0.95rem; max-width: 520px; margin: 0 auto 1.75rem auto; line-height: 1.5;'>";
    
    if (!$is_logged_in) {
        echo "Você precisa estar conectado à sua conta no CONFUSA.top para gerar e simular amistosos oficiais de seleções.";
    } else {
        echo "Contas em período de testes não possuem permissão para simular partidas que impactam diretamente o Ranking Oficial de Seleções.";
    }

    echo "</p>
        <a href='/ranking' class='ranking-nav-link active' style='display: inline-flex; width: auto !important; margin: 0 auto;'>
            <span class='material-symbols-outlined nav-icon'>arrow_back</span>
            <span>Voltar ao Ranking</span>
        </a>
    </div></div>";
    include_once($_SERVER['DOCUMENT_ROOT'] . "/elements/footer.php");
    exit;
}

// 2. Consulta de Países Oficiais Ranqueáveis
$stPaises = $db->query("
    SELECT p.id, p.nome, p.bandeira, p.pontos, p.federacao, f.nome as nomeFederacao
    FROM paises p
    LEFT JOIN federacoes f ON p.federacao = f.id
    WHERE p.dono > 0 AND p.ranqueavel = 0 AND p.ativo = 1
    ORDER BY p.nome ASC
");
$todosPaises = $stPaises->fetchAll(PDO::FETCH_ASSOC);

// 3. Consulta de Estádios
$stEstadios = $db->query("SELECT id, Nome, Capacidade FROM estadio ORDER BY Nome ASC");
$todosEstadios = $stEstadios->fetchAll(PDO::FETCH_ASSOC);

// 4. Consulta de Árbitros
$stArbitros = $db->query("SELECT id, nomeArbitro, pais FROM arbitros ORDER BY nomeArbitro ASC");
$todosArbitros = $stArbitros->fetchAll(PDO::FETCH_ASSOC);

$dataHoraDefault = date('Y-m-d\TH:i');
?>

<div class="amistosos-container">
    <div class="amistosos-card">
        
        <!-- Cabeçalho -->
        <div class="amistosos-header">
            <div class="amistosos-title-group">
                <h1 class="amistosos-main-title">
                    <span class="material-symbols-outlined">sports_soccer</span>
                    Simulador de Amistosos de Seleções
                </h1>
                <span class="amistosos-subtitle">Gere, configure e simule confrontos internacionais válidos para o Ranking Oficial</span>
            </div>
            <div style="display: flex; gap: 0.5rem; align-items: center;">
                <span class="badge-info-pill badge-pts" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">
                    <span class="material-symbols-outlined" style="font-size: 1rem;">verified</span>
                    Ranking Oficial
                </span>
            </div>
        </div>

        <!-- Seletor de Categoria -->
        <div class="amistosos-categories">
            <button type="button" class="category-pill active" data-cat="1">
                <span class="material-symbols-outlined" style="font-size: 1.1rem;">military_tech</span>
                Seleção Principal
            </button>
            <button type="button" class="category-pill" data-cat="2">
                <span class="material-symbols-outlined" style="font-size: 1.1rem;">workspace_premium</span>
                Olímpica (Sub-23/21)
            </button>
            <button type="button" class="category-pill" data-cat="3">
                <span class="material-symbols-outlined" style="font-size: 1.1rem;">sports_handball</span>
                Sub-20
            </button>
            <button type="button" class="category-pill" data-cat="4">
                <span class="material-symbols-outlined" style="font-size: 1.1rem;">school</span>
                Sub-18
            </button>
        </div>

        <form id="formSimularAmistoso" onsubmit="return false;">
            <input type="hidden" id="categoriaInput" name="categoria" value="1">

            <!-- Grid de Matchup (Mandante x Visitante) -->
            <div class="matchup-grid">
                
                <!-- Mandante -->
                <div class="team-selection-card">
                    <div class="team-card-label">
                        <span class="material-symbols-outlined">home</span>
                        Seleção Mandante (Time A)
                    </div>

                    <div class="team-preview-box" id="previewMandante">
                        <img src="/images/bandeiras/world.png" alt="Bandeira" class="team-flag-img" id="imgMandante">
                        <div class="team-info-box">
                            <h3 class="team-name-title" id="nomeMandante">Selecione o País</h3>
                            <div class="team-badges-row">
                                <span class="badge-info-pill badge-pts" id="ptsMandante">0 pts</span>
                                <span class="badge-info-pill badge-squad" id="fedMandante">CONFUSA</span>
                            </div>
                        </div>
                    </div>

                    <div class="config-group">
                        <label for="selectPaisA" class="config-label">
                            <span class="material-symbols-outlined" style="font-size: 0.95rem;">flag</span>
                            Escolha o País Mandante
                        </label>
                        <select id="selectPaisA" name="paisA" class="config-select" required>
                            <option value="">-- Selecione a Seleção --</option>
                            <?php foreach ($todosPaises as $p): ?>
                                <option value="<?php echo $p['id']; ?>" 
                                        data-nome="<?php echo htmlspecialchars($p['nome']); ?>"
                                        data-bandeira="<?php echo htmlspecialchars($p['bandeira'] ?: 'world.png'); ?>"
                                        data-pts="<?php echo number_format($p['pontos'], 0, ',', '.'); ?>"
                                        data-fed="<?php echo htmlspecialchars($p['nomeFederacao'] ?: 'CONFUSA'); ?>">
                                    <?php echo htmlspecialchars($p['nome']) . " (" . number_format($p['pontos'], 0, ',', '.') . " pts)"; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Centro: Configurações da Partida e VS -->
                <div class="matchup-center">
                    <div class="vs-emblem">VS</div>

                    <div class="config-group">
                        <label for="dataHoraPartida" class="config-label">
                            <span class="material-symbols-outlined" style="font-size: 0.95rem;">calendar_today</span>
                            Data e Horário
                        </label>
                        <input type="datetime-local" id="dataHoraPartida" name="data_hora" class="config-input" value="<?php echo $dataHoraDefault; ?>" required>
                    </div>

                    <div class="config-group" style="width: 100%;">
                        <label class="switch-container" for="checkCampoNeutro">
                            <span class="switch-label-text">
                                <span class="material-symbols-outlined">stadium</span>
                                Campo Neutro
                            </span>
                            <input type="checkbox" id="checkCampoNeutro" name="neutro" value="1" class="switch-input">
                        </label>
                    </div>

                    <div class="config-group">
                        <label for="selectEstadio" class="config-label">
                            <span class="material-symbols-outlined" style="font-size: 0.95rem;">location_on</span>
                            Estádio
                        </label>
                        <select id="selectEstadio" name="estadio_id" class="config-select">
                            <option value="0">Automático (Estádio do Mandante)</option>
                            <?php foreach ($todosEstadios as $est): ?>
                                <option value="<?php echo $est['id']; ?>">
                                    <?php echo htmlspecialchars($est['Nome']) . " (" . number_format($est['Capacidade'], 0, ',', '.') . " lug.)"; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="config-group">
                        <label for="selectArbitro" class="config-label">
                            <span class="material-symbols-outlined" style="font-size: 0.95rem;">sports</span>
                            Trio de Arbitragem
                        </label>
                        <select id="selectArbitro" name="arbitro_id" class="config-select">
                            <option value="0">Sorteio Automático (Oficial)</option>
                            <?php foreach ($todosArbitros as $arb): ?>
                                <option value="<?php echo $arb['id']; ?>">
                                    <?php echo htmlspecialchars($arb['nomeArbitro']) . " [" . htmlspecialchars($arb['pais']) . "]"; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Switch Flag: Enviar para o CONFUSA Live -->
                    <div class="config-group" style="width: 100%;">
                        <label class="switch-container" for="checkSubirLive" title="Transmitir lances e resultado para a plataforma CONFUSA Live">
                            <span class="switch-label-text live-text">
                                <span class="material-symbols-outlined">sensors</span>
                                Enviar para o Live
                            </span>
                            <input type="checkbox" id="checkSubirLive" name="subir_live" value="1" class="switch-input live-switch" checked>
                        </label>
                    </div>

                    <button type="button" id="btnDispararSimulacao" class="btn-simular-amistoso">
                        <span class="material-symbols-outlined">play_arrow</span>
                        <span>Simular Amistoso</span>
                    </button>
                </div>

                <!-- Visitante -->
                <div class="team-selection-card">
                    <div class="team-card-label">
                        <span class="material-symbols-outlined">flight</span>
                        Seleção Visitante (Time B)
                    </div>

                    <div class="team-preview-box" id="previewVisitante">
                        <img src="/images/bandeiras/world.png" alt="Bandeira" class="team-flag-img" id="imgVisitante">
                        <div class="team-info-box">
                            <h3 class="team-name-title" id="nomeVisitante">Selecione o País</h3>
                            <div class="team-badges-row">
                                <span class="badge-info-pill badge-pts" id="ptsVisitante">0 pts</span>
                                <span class="badge-info-pill badge-squad" id="fedVisitante">CONFUSA</span>
                            </div>
                        </div>
                    </div>

                    <div class="config-group">
                        <label for="selectPaisB" class="config-label">
                            <span class="material-symbols-outlined" style="font-size: 0.95rem;">flag</span>
                            Escolha o País Visitante
                        </label>
                        <select id="selectPaisB" name="paisB" class="config-select" required>
                            <option value="">-- Selecione a Seleção --</option>
                            <?php foreach ($todosPaises as $p): ?>
                                <option value="<?php echo $p['id']; ?>" 
                                        data-nome="<?php echo htmlspecialchars($p['nome']); ?>"
                                        data-bandeira="<?php echo htmlspecialchars($p['bandeira'] ?: 'world.png'); ?>"
                                        data-pts="<?php echo number_format($p['pontos'], 0, ',', '.'); ?>"
                                        data-fed="<?php echo htmlspecialchars($p['nomeFederacao'] ?: 'CONFUSA'); ?>">
                                    <?php echo htmlspecialchars($p['nome']) . " (" . number_format($p['pontos'], 0, ',', '.') . " pts)"; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

            </div>
        </form>

        <!-- Retrospecto Histórico (Head to Head) -->
        <div id="h2hContainer" class="h2h-container" style="display: none;">
            <h4 class="h2h-title">
                <span class="material-symbols-outlined" style="font-size: 1.15rem; color: #0284c7;">compare_arrows</span>
                Retrospecto Histórico Oficial
            </h4>
            <div class="h2h-stats-row">
                <div class="h2h-stat-item">
                    <span class="h2h-stat-num" id="h2hVitA">0</span>
                    <span class="h2h-stat-label" id="h2hLabelA">Vitórias Mandante</span>
                </div>
                <div class="h2h-stat-item">
                    <span class="h2h-stat-num" id="h2hEmp">0</span>
                    <span class="h2h-stat-label">Empates</span>
                </div>
                <div class="h2h-stat-item">
                    <span class="h2h-stat-num" id="h2hVitB">0</span>
                    <span class="h2h-stat-label" id="h2hLabelB">Vitórias Visitante</span>
                </div>
                <div class="h2h-stat-item">
                    <span class="h2h-stat-num" id="h2hTotal">0</span>
                    <span class="h2h-stat-label">Total de Jogos</span>
                </div>
            </div>
        </div>

        <!-- Área de Carregamento / Spinner -->
        <div id="loadingSimulacao" style="display: none; text-align: center; padding: 2.5rem 1rem;">
            <div style="display: inline-block; width: 48px; height: 48px; border: 4px solid rgba(2, 132, 199, 0.2); border-top-color: #0284c7; border-radius: 50%; animation: spin 0.8s linear infinite;"></div>
            <h4 style="font-family: Outfit, sans-serif; font-size: 1.25rem; color: #0f172a; margin: 1rem 0 0.35rem 0;">Simulando Partida...</h4>
            <p style="color: #64748b; font-size: 0.9rem; max-width: 480px; margin: 0 auto;">Processando escalações, arbitragem, condições climáticas e lances com a engine Hexacolor.</p>
        </div>

        <!-- Área de Alerta de Erro -->
        <div id="alertaErro" style="display: none; margin-top: 1.5rem; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 12px; padding: 1rem 1.25rem; color: #b91c1c; font-size: 0.9rem;">
            <div style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600;">
                <span class="material-symbols-outlined">error</span>
                <span>Erro na Simulação</span>
            </div>
            <div id="alertaErroMsg" style="margin-top: 0.35rem; line-height: 1.4;"></div>
        </div>

        <!-- Card de Resultado da Simulação -->
        <div id="resultadoContainer" style="display: none;"></div>

    </div>
</div>

<style>
@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selA = document.getElementById('selectPaisA');
    const selB = document.getElementById('selectPaisB');
    const catButtons = document.querySelectorAll('.category-pill');
    const catInput = document.getElementById('categoriaInput');
    const btnSimular = document.getElementById('btnDispararSimulacao');
    const loading = document.getElementById('loadingSimulacao');
    const resBox = document.getElementById('resultadoContainer');
    const errBox = document.getElementById('alertaErro');
    const errMsg = document.getElementById('alertaErroMsg');
    const h2hBox = document.getElementById('h2hContainer');

    // Alternar Categoria
    catButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            catButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            catInput.value = this.getAttribute('data-cat');
        });
    });

    // Atualizar preview Mandante
    function updatePreview(selectEl, imgElId, nomeElId, ptsElId, fedElId) {
        const opt = selectEl.options[selectEl.selectedIndex];
        if (opt && opt.value !== '') {
            document.getElementById(imgElId).src = '/images/bandeiras/' + opt.getAttribute('data-bandeira');
            document.getElementById(nomeElId).innerText = opt.getAttribute('data-nome');
            document.getElementById(ptsElId).innerText = opt.getAttribute('data-pts') + ' pts';
            document.getElementById(fedElId).innerText = opt.getAttribute('data-fed');
        } else {
            document.getElementById(imgElId).src = '/images/bandeiras/world.png';
            document.getElementById(nomeElId).innerText = 'Selecione o País';
            document.getElementById(ptsElId).innerText = '0 pts';
            document.getElementById(fedElId).innerText = 'CONFUSA';
        }
    }

    selA.addEventListener('change', function() {
        updatePreview(this, 'imgMandante', 'nomeMandante', 'ptsMandante', 'fedMandante');
        fetchHeadToHead();
    });

    selB.addEventListener('change', function() {
        updatePreview(this, 'imgVisitante', 'nomeVisitante', 'ptsVisitante', 'fedVisitante');
        fetchHeadToHead();
    });

    // Consulta de Retrospecto Histórico
    function fetchHeadToHead() {
        const idA = selA.value;
        const idB = selB.value;
        if (!idA || !idB || idA === idB) {
            h2hBox.style.display = 'none';
            return;
        }

        const nomeA = selA.options[selA.selectedIndex].getAttribute('data-nome');
        const nomeB = selB.options[selB.selectedIndex].getAttribute('data-nome');
        document.getElementById('h2hLabelA').innerText = 'Vitórias ' + nomeA;
        document.getElementById('h2hLabelB').innerText = 'Vitórias ' + nomeB;

        fetch('/ranking/pesquisaRetrospecto.php?team1=' + encodeURIComponent(idA) + '&team2=' + encodeURIComponent(idB))
            .then(res => res.json())
            .then(data => {
                if (data && data.success) {
                    document.getElementById('h2hVitA').innerText = data.vitorias1 || 0;
                    document.getElementById('h2hEmp').innerText = data.empates || 0;
                    document.getElementById('h2hVitB').innerText = data.vitorias2 || 0;
                    document.getElementById('h2hTotal').innerText = data.totalJogos || 0;
                    h2hBox.style.display = 'block';
                } else {
                    h2hBox.style.display = 'none';
                }
            })
            .catch(() => {
                h2hBox.style.display = 'none';
            });
    }

    // Disparar Simulação
    btnSimular.addEventListener('click', function() {
        errBox.style.display = 'none';
        resBox.style.display = 'none';

        const idA = selA.value;
        const idB = selB.value;

        if (!idA || !idB) {
            errBox.style.display = 'block';
            errMsg.innerText = 'Selecione a seleção mandante e a seleção visitante antes de prosseguir.';
            return;
        }

        if (idA === idB) {
            errBox.style.display = 'block';
            errMsg.innerText = 'A seleção mandante e a seleção visitante não podem ser a mesma.';
            return;
        }

        btnSimular.disabled = true;
        loading.style.display = 'block';

        const formData = new FormData(document.getElementById('formSimularAmistoso'));

        fetch('/ranking/simular_amistoso_ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            loading.style.display = 'none';
            btnSimular.disabled = false;

            if (!data.success) {
                errBox.style.display = 'block';
                errMsg.innerText = data.error || 'Ocorreu uma falha ao tentar simular a partida.';
                return;
            }

            renderResultado(data);
        })
        .catch(err => {
            loading.style.display = 'none';
            btnSimular.disabled = false;
            errBox.style.display = 'block';
            errMsg.innerText = 'Erro na comunicação com o servidor de simulação. Tente novamente.';
        });
    });

    // Renderizar Card de Resultado
    function renderResultado(data) {
        resBox.style.display = 'block';

        if (data.sigilo_temporal) {
            // Caso com Sigilo Temporal Ativo (Não dá spoiler do placar!)
            resBox.innerHTML = `
                <div class="result-card sigilo">
                    <div style="display: flex; align-items: center; justify-content: center; gap: 0.6rem; color: #b45309; font-family: Outfit, sans-serif; font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem;">
                        <span class="material-symbols-outlined" style="font-size: 1.8rem;">schedule</span>
                        Partida Simulada sob Sigilo Temporal
                    </div>
                    
                    <div style="text-align: center; color: #475569; font-size: 0.95rem; max-width: 620px; margin: 0 auto 1.5rem auto; line-height: 1.5;">
                        ${data.message}
                    </div>

                    <div class="scoreboard-display">
                        <div class="score-team">
                            <img src="/images/bandeiras/${data.mandante.bandeira}" alt="${data.mandante.nome}">
                            <strong style="font-size: 1.05rem; color: #0f172a;">${data.mandante.nome}</strong>
                        </div>
                        <div style="display: flex; flex-direction: column; align-items: center; gap: 0.25rem;">
                            <span style="font-family: Outfit, sans-serif; font-size: 1.8rem; font-weight: 800; color: #64748b; letter-spacing: 0.1em;">VS</span>
                            <span class="badge-info-pill badge-pts">Início: ${data.data_formatada}</span>
                        </div>
                        <div class="score-team">
                            <img src="/images/bandeiras/${data.visitante.bandeira}" alt="${data.visitante.nome}">
                            <strong style="font-size: 1.05rem; color: #0f172a;">${data.visitante.nome}</strong>
                        </div>
                    </div>

                    ${data.live_enviado ? `
                        <div style="display: flex; align-items: center; justify-content: center; gap: 0.4rem; color: #dc2626; font-size: 0.85rem; font-weight: 600; margin-bottom: 1.25rem;">
                            <span class="material-symbols-outlined" style="font-size: 1.1rem;">sensors</span>
                            Transmitido para o CONFUSA Live
                        </div>
                    ` : ''}

                    <div style="display: flex; gap: 0.75rem; justify-content: center; flex-wrap: wrap;">
                        <a href="${data.sumula_url}" target="_blank" class="btn-clubes-secondary" style="width: auto !important;">
                            <span class="material-symbols-outlined">description</span>
                            <span>Acompanhar Súmula Temporizada</span>
                        </a>
                        <button type="button" onclick="document.getElementById('resultadoContainer').style.display='none';" class="btn-simular-amistoso">
                            <span class="material-symbols-outlined">restart_alt</span>
                            <span>Novo Amistoso</span>
                        </button>
                    </div>
                </div>
            `;
        } else {
            // Caso Revelação Imediata (Data/Hora já passou na vida real)
            let eventosHtml = '';
            if (data.eventos && data.eventos.length > 0) {
                eventosHtml = '<div class="events-timeline">';
                data.eventos.forEach(ev => {
                    let iconEv = 'sports_soccer';
                    let labelEv = 'Gol';
                    let colorEv = '#0284c7';

                    if (ev.tipo === 'amarelo') {
                        iconEv = 'style';
                        labelEv = 'Cartão Amarelo';
                        colorEv = '#f59e0b';
                    } else if (ev.tipo === 'vermelho') {
                        iconEv = 'style';
                        labelEv = 'Cartão Vermelho';
                        colorEv = '#ef4444';
                    } else if (ev.tipo === 'golContra') {
                        iconEv = 'sports_soccer';
                        labelEv = 'Gol Contra';
                        colorEv = '#ef4444';
                    }

                    eventosHtml += `
                        <div class="event-item">
                            <span class="event-minute">${ev.minuto}'</span>
                            <span class="material-symbols-outlined" style="font-size: 1.15rem; color: ${colorEv};">${iconEv}</span>
                            <strong>${ev.jogador}</strong>
                            <span style="color: #64748b; font-size: 0.8rem; margin-left: auto;">(${labelEv})</span>
                        </div>
                    `;
                });
                eventosHtml += '</div>';
            }

            resBox.innerHTML = `
                <div class="result-card final">
                    <div style="text-align: center; color: #059669; font-family: Outfit, sans-serif; font-size: 1.25rem; font-weight: 700;">
                        <span class="material-symbols-outlined" style="vertical-align: middle; font-size: 1.6rem;">check_circle</span>
                        Partida Encerrada e Registrada no Ranking!
                    </div>

                    <div class="scoreboard-display">
                        <div class="score-team">
                            <img src="/images/bandeiras/${data.mandante.bandeira}" alt="${data.mandante.nome}">
                            <strong style="font-size: 1.15rem; color: #0f172a;">${data.mandante.nome}</strong>
                        </div>
                        <div style="display: flex; flex-direction: column; align-items: center;">
                            <div class="score-numbers">
                                <span>${data.mandante.gols}</span>
                                <span style="color: #94a3b8; font-size: 2rem;">x</span>
                                <span>${data.visitante.gols}</span>
                            </div>
                            ${data.mandante.penaltis !== null && data.visitante.penaltis !== null ? `
                                <div class="score-penalties">
                                    (Pênaltis: ${data.mandante.penaltis} x ${data.visitante.penaltis})
                                </div>
                            ` : ''}
                        </div>
                        <div class="score-team">
                            <img src="/images/bandeiras/${data.visitante.bandeira}" alt="${data.visitante.nome}">
                            <strong style="font-size: 1.15rem; color: #0f172a;">${data.visitante.nome}</strong>
                        </div>
                    </div>

                    ${eventosHtml}

                    ${data.live_enviado ? `
                        <div style="display: flex; align-items: center; justify-content: center; gap: 0.4rem; color: #dc2626; font-size: 0.85rem; font-weight: 600; margin-top: 1rem;">
                            <span class="material-symbols-outlined" style="font-size: 1.1rem;">sensors</span>
                            Transmitido para o CONFUSA Live
                        </div>
                    ` : ''}

                    <div style="display: flex; gap: 0.75rem; justify-content: center; flex-wrap: wrap; margin-top: 1.5rem;">
                        <a href="${data.sumula_url}" target="_blank" class="btn-clubes-secondary" style="width: auto !important;">
                            <span class="material-symbols-outlined">description</span>
                            <span>Ver Súmula Completa</span>
                        </a>
                        <button type="button" onclick="document.getElementById('resultadoContainer').style.display='none';" class="btn-simular-amistoso">
                            <span class="material-symbols-outlined">restart_alt</span>
                            <span>Simular Outro Amistoso</span>
                        </button>
                    </div>
                </div>
            `;
        }

        resBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
});
</script>

<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/elements/footer.php");
?>
