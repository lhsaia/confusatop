<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

$page_title = "Time Traveler";
$aux_css = 'meuspaises_redesign';
$extra_css = 'time_traveler';
$css_filename = "time_traveler";
$css_versao = date('h:i:s');

include_once($_SERVER['DOCUMENT_ROOT'] . "/elements/login_info.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/objetos/time.php");

$database = new Database();
$db = $database->getConnection();

$timeObj = new Time($db);

// Carregar lista de todos os clubes para a seleção
$stmtTimes = $db->query("
    SELECT c.ID, c.Nome, c.Escudo, p.nome as nomePais, p.bandeira as bandeiraPais 
    FROM clube c 
    LEFT JOIN paises p ON c.Pais = p.id 
    WHERE c.status = 0 
    ORDER BY c.Nome ASC
");
$todosClubes = $stmtTimes->fetchAll(PDO::FETCH_ASSOC);

$preselectedTeam = isset($_GET['team']) ? (int)$_GET['team'] : 0;
$preselectedDate = isset($_GET['date']) ? trim($_GET['date']) : date('Y-m-d', strtotime('-1 year'));

include_once($_SERVER['DOCUMENT_ROOT'] . "/elements/header.php");
?>

<div class="tt-container">
    <!-- Header -->
    <div class="tt-hero-header">
        <div class="tt-title-wrapper">
            <h1><span class="material-symbols-outlined tt-icon-main">history_toggle_off</span> Time Traveler</h1>
            <p>Reconstitua o elenco exato de qualquer clube do CONFUSA em qualquer data do passado.</p>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="tt-card">
        <form id="ttForm" onsubmit="event.preventDefault(); loadTimeTravelerData();">
            <div class="tt-filter-grid">
                <div class="tt-form-group">
                    <label><span class="material-symbols-outlined">shield</span> Escolha o Clube</label>
                    <select id="ttSelectTeam" class="tt-select select2-enable no-capture" required>
                        <option value="">Selecione um clube...</option>
                        <?php foreach ($todosClubes as $c): ?>
                            <option value="<?php echo $c['ID']; ?>" <?php echo ($preselectedTeam == $c['ID']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['Nome']); ?> (<?php echo htmlspecialchars($c['nomePais'] ?: 'Sem País'); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="tt-form-group">
                    <label><span class="material-symbols-outlined">calendar_month</span> Data no Passado</label>
                    <input type="date" id="ttInputDate" class="tt-input" value="<?php echo htmlspecialchars($preselectedDate); ?>" max="<?php echo date('Y-m-d'); ?>" required onkeydown="return false;">
                </div>

                <button type="submit" class="tt-btn-travel" id="ttBtnSubmit">
                    <span class="material-symbols-outlined">travel_explore</span> Viajar no Tempo
                </button>
            </div>

            <!-- Presets -->
            <div class="tt-presets-bar">
                <span class="tt-presets-label">Atalhos de época:</span>
                <button type="button" class="tt-preset-btn" onclick="setPresetDate(1, 'year')">Há 1 Ano</button>
                <button type="button" class="tt-preset-btn" onclick="setPresetDate(2, 'year')">Há 2 Anos</button>
                <button type="button" class="tt-preset-btn" onclick="setPresetDate(3, 'year')">Há 3 Anos</button>
                <button type="button" class="tt-preset-btn" onclick="setPresetDate(5, 'year')">Há 5 Anos</button>
                <button type="button" class="tt-preset-btn" onclick="setPresetDate(6, 'month')">Há 6 Meses</button>
            </div>
        </form>
    </div>

    <!-- Loader -->
    <div class="tt-loader" id="ttLoader">
        <img src="/images/loaders/loader_style1.gif" alt="Carregando...">
        <p style="margin-top: 12px; color: #64748b; font-weight: 600;">Reconstituindo linha temporal do clube...</p>
    </div>

    <!-- Results Container -->
    <div id="ttResults" style="display: none;">
        <!-- Era & Club Summary Card -->
        <div class="tt-card tt-summary-card">
            <div class="tt-club-escudo-wrapper">
                <img id="ttClubEscudo" src="/images/destaques/placeholder.png" alt="Escudo">
            </div>
            <div class="tt-club-info">
                <h2>
                    <span id="ttClubNome">Nome do Clube</span>
                    <span class="tt-date-badge" id="ttBadgeDate">DD/MM/AAAA</span>
                </h2>
                <div class="tt-club-meta">
                    <span><span class="material-symbols-outlined">stadium</span> <span id="ttClubEstadio">Estádio</span></span>
                    <span><span class="material-symbols-outlined">public</span> <span id="ttClubPais">País</span></span>
                    <span id="ttClubLigaWrapper"><span class="material-symbols-outlined">trophy</span> <span id="ttClubLiga">Liga</span></span>
                </div>
            </div>
            <div class="tt-export-actions">
                <a id="ttBtnExportYmt" href="#" class="tt-btn-ymt" target="_blank" title="Baixar arquivo .ymt para o simulador">
                    <span class="material-symbols-outlined">download</span> Baixar Clube (.ymt)
                </a>
            </div>
        </div>

        <!-- Metrics Bar -->
        <div class="tt-stats-grid">
            <div class="tt-stat-card">
                <div class="tt-stat-icon blue">
                    <span class="material-symbols-outlined">groups</span>
                </div>
                <div class="tt-stat-content">
                    <div class="tt-stat-value" id="ttStatTotal">0</div>
                    <div class="tt-stat-label">Jogadores no Elenco</div>
                </div>
            </div>

            <div class="tt-stat-card">
                <div class="tt-stat-icon green">
                    <span class="material-symbols-outlined">stars</span>
                </div>
                <div class="tt-stat-content">
                    <div class="tt-stat-value" id="ttStatNivelMedio">0.0</div>
                    <div class="tt-stat-label">Média Geral / 11 Ideal (<span id="ttStatNivel11">0.0</span>)</div>
                </div>
            </div>

            <div class="tt-stat-card">
                <div class="tt-stat-icon amber">
                    <span class="material-symbols-outlined">cake</span>
                </div>
                <div class="tt-stat-content">
                    <div class="tt-stat-value" id="ttStatIdadeMedia">0.0</div>
                    <div class="tt-stat-label">Média de Idade na Época</div>
                </div>
            </div>

            <div class="tt-stat-card">
                <div class="tt-stat-icon purple">
                    <span class="material-symbols-outlined">swap_horiz</span>
                </div>
                <div class="tt-stat-content">
                    <div class="tt-stat-value"><span id="ttStatRemanescentes" style="color:#10b981;">0</span> / <span id="ttStatExJogadores" style="color:#f59e0b;">0</span></div>
                    <div class="tt-stat-label">Ainda no Clube / Já Saíram</div>
                </div>
            </div>
        </div>

        <!-- Coach & Timeline Grid -->
        <div class="tt-two-col">
            <!-- Técnico -->
            <div class="tt-card" style="margin-bottom: 0;">
                <div class="tt-card-header">
                    <h3><span class="material-symbols-outlined">sports</span> Comandante da Época</h3>
                </div>
                <div id="ttCoachContainer">
                    <!-- Preenchido via JS -->
                </div>
            </div>

            <!-- Linha do Tempo de Mudanças Posteriores -->
            <div class="tt-card" style="margin-bottom: 0;">
                <div class="tt-card-header">
                    <h3><span class="material-symbols-outlined">timeline</span> O que mudou desde então?</h3>
                </div>
                <div class="tt-timeline-list" id="ttTimelineContainer">
                    <!-- Preenchido via JS -->
                </div>
            </div>
        </div>

        <!-- Sector Groups of Players -->
        <div id="ttSectorsContainer">
            <!-- Setores renderizados via JS -->
        </div>
    </div>

    <!-- Empty Initial State -->
    <div class="tt-empty-state" id="ttEmptyInitial">
        <span class="material-symbols-outlined">schedule</span>
        <h3>Escolha um clube e uma data para começar a viagem no tempo.</h3>
        <p>Você poderá analisar o elenco do passado, ver o que mudou e exportar o arquivo .ymt.</p>
    </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/select2@4.0.12/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.12/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    // Configurar Select2 com suporte completo a busca e z-index
    $('#ttSelectTeam').select2({
        width: '100%',
        placeholder: 'Pesquisar clube pelo nome...',
        allowClear: false,
        dropdownAutoWidth: true,
        language: {
            noResults: function() {
                return "Nenhum clube encontrado";
            },
            searching: function() {
                return "Pesquisando...";
            }
        }
    });

    // Focar automaticamente no campo de busca ao abrir o dropdown
    $(document).on('select2:open', function() {
        setTimeout(function() {
            let searchField = document.querySelector('.select2-container--open .select2-search__field');
            if (searchField) {
                searchField.focus();
            }
        }, 50);
    });

    // Impedir data futura no input date de forma estrita
    let todayStr = new Date().toISOString().split('T')[0];
    $('#ttInputDate').attr('max', todayStr);
    $('#ttInputDate').on('input change blur', function() {
        if ($(this).val() && $(this).val() > todayStr) {
            $(this).val(todayStr);
            alert('Não é possível selecionar datas no futuro.');
        }
    });

    // Se já tiver clube selecionado na URL, disparar busca inicial
    let initialTeam = <?php echo $preselectedTeam; ?>;
    if (initialTeam > 0) {
        loadTimeTravelerData();
    }
});

function setPresetDate(amount, unit) {
    let d = new Date();
    if (unit === 'year') {
        d.setFullYear(d.getFullYear() - amount);
    } else if (unit === 'month') {
        d.setMonth(d.getMonth() - amount);
    }
    let yyyy = d.getFullYear();
    let mm = String(d.getMonth() + 1).padStart(2, '0');
    let dd = String(d.getDate()).padStart(2, '0');
    let presetDateStr = yyyy + '-' + mm + '-' + dd;
    let todayStr = new Date().toISOString().split('T')[0];
    if (presetDateStr > todayStr) {
        presetDateStr = todayStr;
    }
    $('#ttInputDate').val(presetDateStr);
    loadTimeTravelerData();
}

function loadTimeTravelerData() {
    let teamId = $('#ttSelectTeam').val();
    let targetDate = $('#ttInputDate').val();
    let todayStr = new Date().toISOString().split('T')[0];

    if (!teamId || !targetDate) {
        alert('Por favor, selecione um clube e uma data.');
        return;
    }

    if (targetDate > todayStr) {
        $('#ttInputDate').val(todayStr);
        alert('Datas no futuro não são permitidas.');
        return;
    }

    $('#ttEmptyInitial').hide();
    $('#ttResults').hide();
    $('#ttLoader').show();

    $.ajax({
        url: '/api/times/time_traveler_data.php',
        type: 'GET',
        data: { team: teamId, date: targetDate },
        dataType: 'json',
        success: function(response) {
            $('#ttLoader').hide();
            if (response && response.success) {
                renderTimeTraveler(response);
                $('#ttResults').fadeIn(200);
            } else {
                alert(response.error || 'Erro ao carregar dados do time na data selecionada.');
                $('#ttEmptyInitial').show();
            }
        },
        error: function() {
            $('#ttLoader').hide();
            alert('Falha na comunicação com o servidor.');
            $('#ttEmptyInitial').show();
        }
    });
}

function renderTimeTraveler(data) {
    let clube = data.clube;
    let stats = data.estatisticas;
    let tec = data.tecnico;
    let elenco = data.elenco;
    let timeline = data.timeline;

    // 1. Dados do Clube & Download
    $('#ttClubNome').text(clube.nome);
    $('#ttBadgeDate').text(data.dataAlvoFormatada);
    $('#ttClubEstadio').text(clube.estadio ? clube.estadio + (clube.capacidade ? ' (' + parseInt(clube.capacidade).toLocaleString('pt-BR') + ' lug.)' : '') : 'Estádio não cadastrado');
    $('#ttClubPais').text(clube.pais || 'País Desconhecido');
    if (clube.liga) {
        $('#ttClubLiga').text(clube.liga);
        $('#ttClubLigaWrapper').show();
    } else {
        $('#ttClubLigaWrapper').hide();
    }

    let escudoSrc = clube.escudo ? '/images/escudos/' + clube.escudo : '/images/destaques/placeholder.png';
    $('#ttClubEscudo').attr('src', escudoSrc).on('error', function() {
        $(this).off('error').attr('src', '/images/destaques/placeholder.png');
    });

    $('#ttBtnExportYmt').attr('href', '/export/exportar_time_traveler_ymt.php?team=' + clube.id + '&date=' + data.dataAlvo);

    // 2. Estatísticas
    $('#ttStatTotal').text(stats.totalJogadores);
    $('#ttStatNivelMedio').text(stats.mediaNivel);
    $('#ttStatNivel11').text(stats.mediaNivel11);
    $('#ttStatIdadeMedia').text(stats.mediaIdade + ' anos');
    $('#ttStatRemanescentes').text(stats.remanescentes);
    $('#ttStatExJogadores').text(stats.exJogadores);

    // 3. Técnico
    let coachHtml = '';
    if (tec && tec.nome) {
        let tecFoto = tec.foto && tec.foto !== 'null' && tec.foto !== 'placeholder.png' ? '/images/tecnicos/' + tec.foto : '';
        let tecFlag = tec.bandeiraPais ? '<img class="tt-flag-img" src="/images/bandeiras/' + tec.bandeiraPais + '" alt="Band" onerror="this.style.display=\'none\';">' : '';
        let tecAvatarHtml = tecFoto 
            ? `<img class="tt-coach-avatar" src="${tecFoto}" alt="${tec.nome}" onerror="this.onerror=null; this.src='/images/destaques/placeholder.png';">`
            : `<div class="tt-coach-avatar tt-avatar-placeholder"><span class="material-symbols-outlined">sports</span></div>`;

        coachHtml = `
            <div class="tt-coach-box">
                ${tecAvatarHtml}
                <div class="tt-coach-details">
                    <h4>${tec.nome}</h4>
                    <p>${tecFlag} ${tec.paisNome || ''} &bull; Nível ${tec.nivel}</p>
                </div>
            </div>
        `;
    } else {
        coachHtml = `
            <div class="tt-coach-box" style="justify-content: center; color: #64748b;">
                <span class="material-symbols-outlined">person_off</span> Sem técnico registrado nesta data
            </div>
        `;
    }
    $('#ttCoachContainer').html(coachHtml);

    // 4. Timeline de Mudanças
    let tlHtml = '';
    let hasChanges = false;

    if (timeline.saidasDepois && timeline.saidasDepois.length > 0) {
        hasChanges = true;
        timeline.saidasDepois.forEach(function(s) {
            let jFoto = s.foto && s.foto !== 'null' && s.foto !== 'placeholder.png' ? '/images/jogadores/' + s.foto : '';
            let jImgHtml = jFoto
                ? `<img src="${jFoto}" alt="${s.nome}" onerror="this.onerror=null; this.style.display='none';">`
                : `<span class="material-symbols-outlined" style="font-size:22px; color:#94a3b8;">account_circle</span>`;

            tlHtml += `
                <div class="tt-timeline-item departed">
                    <div class="tt-tl-player">
                        ${jImgHtml}
                        <div>
                            <div>${s.nome}</div>
                            <small style="color:#b45309; font-weight:500;">Saiu em ${s.data}</small>
                        </div>
                    </div>
                    <div class="tt-tl-info">
                        <strong>&rarr; ${s.destinoNome}</strong>
                    </div>
                </div>
            `;
        });
    }

    if (timeline.chegadasDepois && timeline.chegadasDepois.length > 0) {
        hasChanges = true;
        timeline.chegadasDepois.forEach(function(c) {
            let jFoto = c.foto && c.foto !== 'null' && c.foto !== 'placeholder.png' ? '/images/jogadores/' + c.foto : '';
            let jImgHtml = jFoto
                ? `<img src="${jFoto}" alt="${c.nome}" onerror="this.onerror=null; this.style.display='none';">`
                : `<span class="material-symbols-outlined" style="font-size:22px; color:#94a3b8;">account_circle</span>`;

            tlHtml += `
                <div class="tt-timeline-item arrived">
                    <div class="tt-tl-player">
                        ${jImgHtml}
                        <div>
                            <div>${c.nome}</div>
                            <small style="color:#15803d; font-weight:500;">Chegou em ${c.data}</small>
                        </div>
                    </div>
                    <div class="tt-tl-info">
                        <strong>&larr; de ${c.origemNome}</strong>
                    </div>
                </div>
            `;
        });
    }

    if (!hasChanges) {
        tlHtml = '<p style="color:#64748b; font-size:0.85rem; text-align:center; margin:16px 0;">Nenhuma movimentação de mercado registrada para este clube após esta data.</p>';
    }
    $('#ttTimelineContainer').html(tlHtml);

    // 5. Setores do Elenco
    let setoresMap = {
        'Goleiro': { title: 'Goleiros', icon: 'sports_handball', players: [] },
        'Defensor': { title: 'Defensores', icon: 'shield', players: [] },
        'Meio-campista': { title: 'Meio-campistas', icon: 'hdr_strong', players: [] },
        'Atacante': { title: 'Atacantes', icon: 'sports_soccer', players: [] }
    };

    elenco.forEach(function(p) {
        let set = p.setor || 'Atacante';
        if (setoresMap[set]) {
            setoresMap[set].players.push(p);
        } else {
            setoresMap['Atacante'].players.push(p);
        }
    });

    let sectorsHtml = '';
    for (let key in setoresMap) {
        let group = setoresMap[key];
        if (group.players.length === 0) continue;

        sectorsHtml += `
            <div class="tt-sector-title">
                <span class="material-symbols-outlined" style="color:#0284c7;">${group.icon}</span>
                ${group.title} <span class="tt-sector-badge">${group.players.length}</span>
            </div>
            <div class="tt-players-grid">
        `;

        group.players.forEach(function(p) {
            let jFoto = p.foto && p.foto !== 'null' && p.foto !== 'placeholder.png' ? '/images/jogadores/' + p.foto : '';
            let flag = p.bandeiraPais ? '<img class="tt-flag-img" src="/images/bandeiras/' + p.bandeiraPais + '" alt="' + (p.paisNome || '') + '" onerror="this.style.display=\'none\';">' : '';
            
            let photoHtml = jFoto
                ? `<img class="tt-player-photo" src="${jFoto}" alt="${p.nome}" onerror="this.onerror=null; this.src='/images/destaques/placeholder.png';">`
                : `<div class="tt-player-photo tt-photo-placeholder"><span class="material-symbols-outlined">person</span></div>`;

            let badgeHtml = '';
            if (p.permaneceHoje) {
                badgeHtml = '<span class="tt-badge-stay"><span class="material-symbols-outlined" style="font-size:0.9rem;">check_circle</span> Permanece no clube</span>';
            } else if (p.statusAtualTexto === 'Aposentado') {
                badgeHtml = '<span class="tt-badge-retired"><span class="material-symbols-outlined" style="font-size:0.9rem;">person_off</span> Aposentado</span>';
            } else {
                badgeHtml = `<span class="tt-badge-left"><span class="material-symbols-outlined" style="font-size:0.9rem;">exit_to_app</span> ${p.statusAtualTexto}</span>`;
            }

            sectorsHtml += `
                <div class="tt-player-card">
                    <div class="tt-player-top">
                        ${photoHtml}
                        <div class="tt-player-main-info">
                            <div class="tt-player-name" title="${p.nome}">${p.nome}</div>
                            <div class="tt-player-sub">
                                ${flag} <span>${p.posicaoSigla}</span> &bull; <span>${p.idadeNaEpoca} anos na época</span>
                            </div>
                        </div>
                        <div class="tt-player-level" title="Nível">${p.nivel}</div>
                    </div>
                    <div class="tt-player-bottom">
                        ${badgeHtml}
                    </div>
                </div>
            `;
        });

        sectorsHtml += '</div>';
    }

    if (sectorsHtml === '') {
        sectorsHtml = '<div class="tt-empty-state"><p>Nenhum jogador encontrado neste elenco para a data especificada.</p></div>';
    }

    $('#ttSectorsContainer').html(sectorsHtml);
}
</script>

<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/elements/footer.php");
?>
