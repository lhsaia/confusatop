<?php
declare(strict_types=1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/elements/login_info.php';

// Apenas administradores
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || (int)($_SESSION['admin_status'] ?? 0) !== 1) {
    header('Location: /index.php');
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/objetos/jogador.php';

$database = new Database();
$db = $database->getConnection();
$jogadorObj = new Jogador($db);

$page_title = "Merge de Atletas - Administração";
$css_filename = "home_redesign";
$css_login = 'login';
$aux_css = 'home_redesign';
$extra_css = 'admin_redesign';
$css_versao = date('h:i:s');
include_once $_SERVER['DOCUMENT_ROOT'] . '/elements/header.php';
?>

<!-- Tom Select / Select2 CSS e JS para busca rápida e moderna nos combos -->
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>

<style>
/* Estilos Customizados para a Tela de Merge de Atletas */
.merge-container {
    max-width: 1250px !important;
    margin: 80px auto 40px auto !important;
    padding: 30px !important;
    background: rgba(15, 23, 42, 0.85) !important;
    backdrop-filter: blur(14px) !important;
    -webkit-backdrop-filter: blur(14px) !important;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
    border-radius: 14px !important;
    box-shadow: 0 16px 45px rgba(0, 0, 0, 0.5) !important;
    font-family: 'Montserrat', sans-serif !important;
    color: #f1f5f9 !important;
}

.merge-header {
    text-align: center;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    padding-bottom: 20px;
    margin-bottom: 25px;
}

.merge-title {
    font-family: 'Outfit', sans-serif !important;
    font-size: 32px !important;
    font-weight: 700 !important;
    background: linear-gradient(135deg, #38bdf8, #818cf8) !important;
    -webkit-background-clip: text !important;
    -webkit-text-fill-color: transparent !important;
    display: inline-flex;
    align-items: center;
    gap: 10px;
}

.merge-selectors-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 25px;
    margin-bottom: 25px;
}

@media (max-width: 850px) {
    .merge-selectors-grid {
        grid-template-columns: 1fr;
    }
}

.selector-box {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    padding: 18px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.selector-box-title {
    font-family: 'Outfit', sans-serif;
    font-size: 16px;
    font-weight: 600;
    color: #38bdf8;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Tom Select Dark Customization */
.ts-control {
    background-color: rgba(15, 23, 42, 0.8) !important;
    border: 1px solid rgba(255, 255, 255, 0.2) !important;
    border-radius: 8px !important;
    color: #f8fafc !important;
    padding: 10px 12px !important;
    font-size: 14px !important;
}

.ts-dropdown {
    background-color: #0f172a !important;
    border: 1px solid rgba(56, 189, 248, 0.3) !important;
    color: #f8fafc !important;
    border-radius: 8px !important;
    box-shadow: 0 10px 25px rgba(0,0,0,0.6) !important;
}

.ts-dropdown .option {
    padding: 8px 12px !important;
    color: #cbd5e1 !important;
    border-bottom: 1px solid rgba(255,255,255,0.05);
}

.ts-dropdown .option:hover,
.ts-dropdown .option.active {
    background-color: rgba(56, 189, 248, 0.15) !important;
    color: #38bdf8 !important;
}

/* Comparison Cards Grid */
.comparison-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 25px;
    margin-bottom: 30px;
}

@media (max-width: 850px) {
    .comparison-grid {
        grid-template-columns: 1fr;
    }
}

.player-card {
    background: rgba(30, 41, 59, 0.5);
    border: 2px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transition: all 0.3s ease;
    position: relative;
}

.player-card.is-main {
    border-color: #10b981 !important;
    box-shadow: 0 0 20px rgba(16, 185, 129, 0.25) !important;
    background: rgba(16, 185, 129, 0.05);
}

.player-card.is-secondary {
    border-color: #ef4444 !important;
    box-shadow: 0 0 20px rgba(239, 68, 68, 0.2) !important;
    background: rgba(239, 68, 68, 0.03);
}

.player-card-banner {
    padding: 16px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.player-card.is-main .player-card-banner {
    background: rgba(16, 185, 129, 0.15);
}

.player-card.is-secondary .player-card-banner {
    background: rgba(239, 68, 68, 0.15);
}

.card-role-badge {
    font-size: 13px;
    font-weight: 700;
    padding: 6px 14px;
    border-radius: 20px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.badge-main {
    background: #10b981;
    color: #ffffff;
}

.badge-secondary {
    background: #ef4444;
    color: #ffffff;
}

.player-header-info {
    display: flex;
    gap: 18px;
    padding: 20px;
    background: rgba(0, 0, 0, 0.2);
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.player-photo-container {
    width: 100px;
    height: 120px;
    border-radius: 8px;
    overflow: hidden;
    background: #0f172a;
    border: 1px solid rgba(255, 255, 255, 0.15);
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.player-photo-container img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.player-main-meta {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.player-name {
    font-family: 'Outfit', sans-serif;
    font-size: 20px;
    font-weight: 700;
    color: #f8fafc;
    margin: 0 0 6px 0;
}

.player-submeta {
    font-size: 13px;
    color: #94a3b8;
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 8px;
}

.pos-chip {
    background: rgba(56, 189, 248, 0.2);
    color: #38bdf8;
    border: 1px solid rgba(56, 189, 248, 0.4);
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 700;
}

.player-data-section {
    padding: 18px 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.section-title {
    font-family: 'Outfit', sans-serif;
    font-size: 14px;
    font-weight: 600;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.data-row {
    display: flex;
    justify-content: space-between;
    padding: 5px 0;
    font-size: 13px;
    border-bottom: 1px dotted rgba(255, 255, 255, 0.05);
}

.data-label {
    color: #94a3b8;
}

.data-value {
    color: #f1f5f9;
    font-weight: 600;
}

/* Attributes Grid */
.attr-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px 14px;
}

.attr-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 13px;
    padding: 4px 8px;
    border-radius: 4px;
    background: rgba(255, 255, 255, 0.02);
}

.attr-val {
    font-weight: 700;
    font-size: 13px;
    padding: 2px 6px;
    border-radius: 4px;
    min-width: 28px;
    text-align: center;
}

.attr-val-high {
    background: rgba(16, 185, 129, 0.2);
    color: #34d399;
}

.attr-val-med {
    background: rgba(56, 189, 248, 0.2);
    color: #38bdf8;
}

.attr-val-low {
    background: rgba(239, 68, 68, 0.2);
    color: #f87171;
}

/* Actions Footer */
.merge-actions-card {
    background: rgba(15, 23, 42, 0.9);
    border: 1px solid rgba(56, 189, 248, 0.3);
    border-radius: 12px;
    padding: 24px;
    text-align: center;
    box-shadow: 0 8px 25px rgba(0,0,0,0.3);
}

.btn-merge-action {
    background: linear-gradient(135deg, #10b981, #059669);
    color: #ffffff;
    border: none;
    padding: 14px 32px;
    font-size: 16px;
    font-weight: 700;
    font-family: 'Outfit', sans-serif;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
}

.btn-merge-action:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.5);
}

.btn-merge-action:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    background: #475569;
    box-shadow: none;
}

.empty-card-placeholder {
    padding: 60px 20px;
    text-align: center;
    color: #64748b;
}

.empty-card-placeholder .material-symbols-outlined {
    font-size: 48px;
    margin-bottom: 12px;
    opacity: 0.5;
}
</style>

<div class="merge-container">
    <div class="merge-header">
        <h1 class="merge-title">
            <span class="material-symbols-outlined" style="font-size: 36px; vertical-align: middle;">merge</span>
            Merge de Atletas Duplicados
        </h1>
        <p style="color: #94a3b8; font-size: 14px; margin: 8px 0 0 0;">
            Selecione dois atletas cadastrados no banco para comparar dados e atributos. Escolha qual atleta será preservado como principal (o outro será absorvido e excluído com migração automática de contratos, partidas e histórico).
        </p>
    </div>

    <!-- Navegação por Abas -->
    <div style="display: flex; gap: 10px; margin-bottom: 25px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 12px; flex-wrap: wrap;">
        <button id="tab-btn-merge" class="admin-btn admin-btn-primary" onclick="trocarAba('merge')" style="display: flex; align-items: center; gap: 6px;">
            <span class="material-symbols-outlined">call_merge</span>
            Merge Manual
        </button>
        <button id="tab-btn-duplicatas" class="admin-btn admin-btn-secondary" onclick="trocarAba('duplicatas')" style="display: flex; align-items: center; gap: 6px;">
            <span class="material-symbols-outlined">find_in_page</span>
            Buscar Possíveis Duplicatas
            <span id="badge-total-duplicatas" style="display: none; background: #ef4444; color: #fff; font-size: 11px; padding: 2px 7px; border-radius: 10px; margin-left: 4px;">0</span>
        </button>
        <button id="tab-btn-historico" class="admin-btn admin-btn-secondary" onclick="trocarAba('historico')" style="display: flex; align-items: center; gap: 6px;">
            <span class="material-symbols-outlined">history</span>
            Histórico de Merges (Logs)
        </button>
    </div>

    <!-- ABA 1: MERGE MANUAL -->
    <div id="aba-merge-manual">
        <!-- Seletor de Atletas -->
        <div class="merge-selectors-grid">
            <div class="selector-box">
                <div class="selector-box-title">
                    <span class="material-symbols-outlined">person_search</span>
                    Atleta 1
                </div>
                <select id="select-atleta-1" placeholder="Digite o nome ou ID do atleta 1...">
                    <option value="">Digite para buscar o Atleta 1...</option>
                </select>
            </div>

            <div class="selector-box">
                <div class="selector-box-title">
                    <span class="material-symbols-outlined">person_search</span>
                    Atleta 2
                </div>
                <select id="select-atleta-2" placeholder="Digite o nome ou ID do atleta 2...">
                    <option value="">Digite para buscar o Atleta 2...</option>
                </select>
            </div>
        </div>

        <!-- Cards Comparativos -->
        <div class="comparison-grid">
            <!-- Card Atleta 1 -->
            <div id="card-atleta-1" class="player-card">
                <div class="empty-card-placeholder">
                    <span class="material-symbols-outlined">sports_soccer</span>
                    <p>Selecione o Atleta 1 para exibir a ficha comparativa</p>
                </div>
            </div>

            <!-- Card Atleta 2 -->
            <div id="card-atleta-2" class="player-card">
                <div class="empty-card-placeholder">
                    <span class="material-symbols-outlined">sports_soccer</span>
                    <p>Selecione o Atleta 2 para exibir a ficha comparativa</p>
                </div>
            </div>
        </div>

        <!-- Botão de Ação / Confirmação de Merge -->
        <div class="merge-actions-card">
            <div style="margin-bottom: 15px; font-size: 14px; color: #cbd5e1;" id="merge-summary-text">
                Selecione ambos os atletas acima e defina qual deles será mantido no sistema.
            </div>
            <button id="btn-executar-merge" class="btn-merge-action" disabled>
                <span class="material-symbols-outlined">call_merge</span>
                Realizar Merge de Atletas
            </button>
        </div>
    </div>

    <!-- ABA 2: BUSCAR POSSÍVEIS DUPLICATAS -->
    <div id="aba-duplicatas" style="display: none;">
        <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; padding: 20px; margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <div>
                    <h3 style="font-size: 18px; color: #38bdf8; margin: 0 0 4px 0;">Algoritmo de Detecção de Duplicatas</h3>
                    <p style="color: #94a3b8; font-size: 13px; margin: 0;">Varre a base em busca de atletas com nomes idênticos, prefixos compatíveis e correspondência fonética (Soundex) dentro do mesmo país.</p>
                </div>
                <button onclick="carregarDuplicatas()" class="admin-btn admin-btn-primary" style="display: flex; align-items: center; gap: 6px;">
                    <span class="material-symbols-outlined">refresh</span>
                    Escanear Banco de Dados
                </button>
            </div>
        </div>

        <div id="container-lista-duplicatas">
            <div class="empty-card-placeholder">
                <span class="material-symbols-outlined">search</span>
                <p>Clique em "Escanear Banco de Dados" para buscar atletas duplicados.</p>
            </div>
        </div>
    </div>

    <!-- ABA 3: HISTÓRICO DE LOGS -->
    <div id="aba-historico" style="display: none;">
        <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; padding: 20px; margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <div>
                    <h3 style="font-size: 18px; color: #fbbf24; margin: 0 0 4px 0;">Auditoria e Histórico de Merges</h3>
                    <p style="color: #94a3b8; font-size: 13px; margin: 0;">Registro imutável de todas as fusões executadas por administradores, detalhando quem foi preservado e o que foi migrado.</p>
                </div>
                <button onclick="carregarHistoricoLogs()" class="admin-btn admin-btn-secondary" style="display: flex; align-items: center; gap: 6px;">
                    <span class="material-symbols-outlined">sync</span>
                    Atualizar Histórico
                </button>
            </div>
        </div>

        <div id="container-lista-logs">
            <div class="empty-card-placeholder">
                <span class="material-symbols-outlined">history</span>
                <p>Carregando histórico de auditoria...</p>
            </div>
        </div>
    </div>

    <div style="margin-top: 30px; text-align: center; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
        <a href="/admin/index.php" class="admin-btn admin-btn-secondary" style="font-size: 13px; text-decoration: none;">
            <span class="material-symbols-outlined" style="font-size: 18px;">arrow_back</span>
            Voltar ao Painel Admin
        </a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let tomSelect1 = null;
    let tomSelect2 = null;

    let dadosAtleta1 = null;
    let dadosAtleta2 = null;

    // 1 = Atleta 1 é o principal; 2 = Atleta 2 é o principal
    let principalEscolhido = 1; 

    function criarConfigTomSelect(num) {
        return {
            valueField: 'value',
            labelField: 'text',
            searchField: ['text', 'nome'],
            create: false,
            maxItems: 1,
            placeholder: `Busque pelo nome ou ID do Atleta ${num}...`,
            preload: 'focus',
            load: function(query, callback) {
                const url = '/admin/api_jogador_merge.php?action=search&q=' + encodeURIComponent(query);
                fetch(url)
                    .then(response => response.json())
                    .then(json => {
                        callback(json.results || []);
                    }).catch(()=>{
                        callback();
                    });
            },
            onChange: function(value) {
                handleSelectChange(num, value);
            }
        };
    }

    // Inicializar TomSelect nos dois campos com AJAX assíncrono on-demand
    tomSelect1 = new TomSelect('#select-atleta-1', criarConfigTomSelect(1));
    tomSelect2 = new TomSelect('#select-atleta-2', criarConfigTomSelect(2));

    // Função para tratar a seleção e impedir atleta duplicado
    function handleSelectChange(targetNum, value) {
        const otherTom = (targetNum === 1) ? tomSelect2 : tomSelect1;
        const currentId = parseInt(value, 10) || 0;

        // Atualizar bloqueio no outro select com segurança
        try {
            if (otherTom && typeof otherTom.enableOption === 'function' && typeof otherTom.disableOption === 'function') {
                if (otherTom.options && typeof otherTom.options === 'object') {
                    Object.keys(otherTom.options).forEach(optVal => {
                        try { otherTom.enableOption(optVal); } catch(e) {}
                    });
                }
                if (currentId > 0) {
                    try { otherTom.disableOption(currentId.toString()); } catch(e) {}
                }
            }
        } catch(err) {
            console.warn('Aviso ao sincronizar opções do select:', err);
        }

        // Buscar dados do atleta selecionado
        if (currentId > 0) {
            carregarDadosAtleta(targetNum, currentId);
        } else {
            if (targetNum === 1) {
                dadosAtleta1 = null;
                renderizarPlaceholder(1);
            } else {
                dadosAtleta2 = null;
                renderizarPlaceholder(2);
            }
            atualizarBotaoMerge();
        }
    }

    function renderizarPlaceholder(num) {
        const container = document.getElementById(`card-atleta-${num}`);
        container.className = 'player-card';
        container.innerHTML = `
            <div class="empty-card-placeholder">
                <span class="material-symbols-outlined">sports_soccer</span>
                <p>Selecione o Atleta ${num} para exibir a ficha comparativa</p>
            </div>
        `;
    }

    function carregarDadosAtleta(num, idJogador) {
        const container = document.getElementById(`card-atleta-${num}`);
        container.innerHTML = `
            <div class="empty-card-placeholder">
                <span class="material-symbols-outlined" style="animation: spin 1s linear infinite;">sync</span>
                <p>Carregando dados do atleta #${idJogador}...</p>
            </div>
        `;

        fetch(`/admin/api_jogador_merge.php?action=get&id=${idJogador}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (num === 1) dadosAtleta1 = data;
                    if (num === 2) dadosAtleta2 = data;
                    renderizarCards();
                } else {
                    container.innerHTML = `<div class="empty-card-placeholder" style="color: #ef4444;"><p>Erro: ${data.error}</p></div>`;
                }
                atualizarBotaoMerge();
            })
            .catch(err => {
                container.innerHTML = `<div class="empty-card-placeholder" style="color: #ef4444;"><p>Erro de comunicação com o servidor.</p></div>`;
                atualizarBotaoMerge();
            });
    }

    // Alternar qual atleta será o principal
    window.definirPrincipal = function(num) {
        principalEscolhido = num;
        renderizarCards();
        atualizarBotaoMerge();
    };

    function renderizarCards() {
        if (dadosAtleta1) renderizarCardAtleta(1, dadosAtleta1, principalEscolhido === 1);
        if (dadosAtleta2) renderizarCardAtleta(2, dadosAtleta2, principalEscolhido === 2);
    }

    function getAttrClass(val) {
        if (val >= 75) return 'attr-val-high';
        if (val >= 50) return 'attr-val-med';
        return 'attr-val-low';
    }

    function renderizarCardAtleta(num, data, isMain) {
        const container = document.getElementById(`card-atleta-${num}`);
        container.className = `player-card ${isMain ? 'is-main' : 'is-secondary'}`;

        const fotoHtml = data.foto 
            ? `<img src="${data.foto}" alt="${data.nome}">`
            : `<span class="material-symbols-outlined" style="font-size: 48px; color: #475569;">person</span>`;

        const posChips = data.posicoes.map(p => `<span class="pos-chip">${p}</span>`).join(' ') || '<span style="color:#64748b; font-size:12px;">Nenhuma</span>';

        const contractsCount = (data.contratos && data.contratos.length) ? data.contratos.length : 0;
        let contratosListHtml = '';
        if (contractsCount > 0) {
            contratosListHtml = data.contratos.map(c => {
                const tipoNome = (c.tipoContrato == 0) ? 'Profissional' : ((c.tipoContrato == 1) ? 'Empréstimo' : 'Seleção');
                return `<div style="font-size: 12px; color: #cbd5e1; margin-bottom: 3px;">• <strong>${tipoNome}:</strong> ${c.nomeClube || 'Sem clube'} (Fim: ${c.encerramento || 'Indeterminado'})</div>`;
            }).join('');
        } else {
            contratosListHtml = '<div style="font-size: 12px; color: #64748b;">Nenhum contrato ativo</div>';
        }

        container.innerHTML = `
            <div class="player-card-banner">
                <div>
                    <span class="card-role-badge ${isMain ? 'badge-main' : 'badge-secondary'}">
                        <span class="material-symbols-outlined" style="font-size: 18px;">${isMain ? 'verified' : 'cancel'}</span>
                        ${isMain ? 'Atleta Principal (Será Preservado)' : 'Atleta Secundário (Será Excluído)'}
                    </span>
                </div>
                <div>
                    <label style="cursor: pointer; display: flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; color: #f8fafc;">
                        <input type="radio" name="radio_principal" ${isMain ? 'checked' : ''} onchange="definirPrincipal(${num})">
                        Preservar este
                    </label>
                </div>
            </div>

            <div class="player-header-info">
                <div class="player-photo-container">
                    ${fotoHtml}
                </div>
                <div class="player-main-meta">
                    <h2 class="player-name">#${data.id} - ${data.nome}</h2>
                    <div class="player-submeta">
                        <span><strong>País:</strong> ${data.bandeiraPais ? `<img src="/images/bandeiras/${data.bandeiraPais}" style="height:12px; vertical-align:middle;">` : ''} ${data.pais}</span>
                        <span><strong>Idade:</strong> ${data.idade} anos (${data.nascimento || '-'})</span>
                        <span><strong>Gênero:</strong> ${data.sexo}</span>
                    </div>
                    <div style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                        <div>
                            <span style="font-size: 11px; color: #94a3b8; text-transform: uppercase;">Nível Geral</span>
                            <div style="font-size: 20px; font-weight: 800; color: #38bdf8;">${data.nivel}</div>
                        </div>
                        <div>
                            <span style="font-size: 11px; color: #94a3b8; text-transform: uppercase;">Valor Mercado</span>
                            <div style="font-size: 16px; font-weight: 700; color: #34d399;">$ ${data.valor_formatado}</div>
                        </div>
                        <div style="flex: 1;">
                            <span style="font-size: 11px; color: #94a3b8; text-transform: uppercase; display: block; margin-bottom: 2px;">Posições</span>
                            <div>${posChips}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="player-data-section">
                <div class="section-title">
                    <span class="material-symbols-outlined" style="font-size: 18px; color: #38bdf8;">badge</span>
                    Vínculo & Contratos (${contractsCount})
                </div>
                ${contratosListHtml}
            </div>

            <div class="player-data-section">
                <div class="section-title">
                    <span class="material-symbols-outlined" style="font-size: 18px; color: #fbbf24;">analytics</span>
                    Histórico & Partidas
                </div>
                <div class="data-row">
                    <span class="data-label">Jogos em Clubes:</span>
                    <span class="data-value">${(data.stats && data.stats.jogosTime) || 0} jogos</span>
                </div>
                <div class="data-row">
                    <span class="data-label">Gols em Clubes / Seleção:</span>
                    <span class="data-value">${(data.stats && data.stats.golsTime) || 0} / ${(data.stats && data.stats.golsSelecao) || 0}</span>
                </div>
                <div class="data-row">
                    <span class="data-label">Cartões Amarelos / Vermelhos:</span>
                    <span class="data-value">${(((data.stats && data.stats.amarelosTime) || 0) + ((data.stats && data.stats.amarelosSelecao) || 0))} A / ${(((data.stats && data.stats.vermelhosTime) || 0) + ((data.stats && data.stats.vermelhosSelecao) || 0))} V</span>
                </div>
                <div class="data-row">
                    <span class="data-label">Histórico de Transferências:</span>
                    <span class="data-value">${(data.stats && data.stats.transferencias) || 0} registro(s)</span>
                </div>
            </div>

            <div class="player-data-section" style="border-bottom: none; flex: 1;">
                <div class="section-title">
                    <span class="material-symbols-outlined" style="font-size: 18px; color: #10b981;">tune</span>
                    Atributos Técnicos e Físicos
                </div>
                <div class="attr-grid">
                    <div class="attr-item"><span class="data-label">Marcação:</span><span class="attr-val ${getAttrClass(data.atributos?.Marcacao || 0)}">${data.atributos?.Marcacao || 0}</span></div>
                    <div class="attr-item"><span class="data-label">Desarme:</span><span class="attr-val ${getAttrClass(data.atributos?.Desarme || 0)}">${data.atributos?.Desarme || 0}</span></div>
                    <div class="attr-item"><span class="data-label">Visão Jogo:</span><span class="attr-val ${getAttrClass(data.atributos?.VisaoJogo || 0)}">${data.atributos?.VisaoJogo || 0}</span></div>
                    <div class="attr-item"><span class="data-label">Movimentação:</span><span class="attr-val ${getAttrClass(data.atributos?.Movimentacao || 0)}">${data.atributos?.Movimentacao || 0}</span></div>
                    <div class="attr-item"><span class="data-label">Cruzamentos:</span><span class="attr-val ${getAttrClass(data.atributos?.Cruzamentos || 0)}">${data.atributos?.Cruzamentos || 0}</span></div>
                    <div class="attr-item"><span class="data-label">Cabeceamento:</span><span class="attr-val ${getAttrClass(data.atributos?.Cabeceamento || 0)}">${data.atributos?.Cabeceamento || 0}</span></div>
                    <div class="attr-item"><span class="data-label">Técnica:</span><span class="attr-val ${getAttrClass(data.atributos?.Tecnica || 0)}">${data.atributos?.Tecnica || 0}</span></div>
                    <div class="attr-item"><span class="data-label">Controle Bola:</span><span class="attr-val ${getAttrClass(data.atributos?.ControleBola || 0)}">${data.atributos?.ControleBola || 0}</span></div>
                    <div class="attr-item"><span class="data-label">Finalização:</span><span class="attr-val ${getAttrClass(data.atributos?.Finalizacao || 0)}">${data.atributos?.Finalizacao || 0}</span></div>
                    <div class="attr-item"><span class="data-label">Faro de Gol:</span><span class="attr-val ${getAttrClass(data.atributos?.FaroGol || 0)}">${data.atributos?.FaroGol || 0}</span></div>
                    <div class="attr-item"><span class="data-label">Velocidade:</span><span class="attr-val ${getAttrClass(data.atributos?.Velocidade || 0)}">${data.atributos?.Velocidade || 0}</span></div>
                    <div class="attr-item"><span class="data-label">Força:</span><span class="attr-val ${getAttrClass(data.atributos?.Forca || 0)}">${data.atributos?.Forca || 0}</span></div>
                </div>

                <div class="section-title" style="margin-top: 15px;">
                    <span class="material-symbols-outlined" style="font-size: 18px; color: #818cf8;">front_hand</span>
                    Goleiro & Mental
                </div>
                <div class="attr-grid">
                    <div class="attr-item"><span class="data-label">Reflexos:</span><span class="attr-val ${getAttrClass(data.atributos?.Reflexos || 0)}">${data.atributos?.Reflexos || 0}</span></div>
                    <div class="attr-item"><span class="data-label">Segurança:</span><span class="attr-val ${getAttrClass(data.atributos?.Seguranca || 0)}">${data.atributos?.Seguranca || 0}</span></div>
                    <div class="attr-item"><span class="data-label">Saídas:</span><span class="attr-val ${getAttrClass(data.atributos?.Saidas || 0)}">${data.atributos?.Saidas || 0}</span></div>
                    <div class="attr-item"><span class="data-label">Jogo Aéreo:</span><span class="attr-val ${getAttrClass(data.atributos?.JogoAereo || 0)}">${data.atributos?.JogoAereo || 0}</span></div>
                    <div class="attr-item"><span class="data-label">Lançamentos:</span><span class="attr-val ${getAttrClass(data.atributos?.Lancamentos || 0)}">${data.atributos?.Lancamentos || 0}</span></div>
                    <div class="attr-item"><span class="data-label">Defesa Pênaltis:</span><span class="attr-val ${getAttrClass(data.atributos?.DefesaPenaltis || 0)}">${data.atributos?.DefesaPenaltis || 0}</span></div>
                </div>
            </div>
        `;
    }

    function atualizarBotaoMerge() {
        const btn = document.getElementById('btn-executar-merge');
        const summary = document.getElementById('merge-summary-text');

        if (dadosAtleta1 && dadosAtleta2 && dadosAtleta1.id !== dadosAtleta2.id) {
            btn.disabled = false;
            const principal = (principalEscolhido === 1) ? dadosAtleta1 : dadosAtleta2;
            const secundario = (principalEscolhido === 1) ? dadosAtleta2 : dadosAtleta1;
            summary.innerHTML = `O atleta <strong style="color: #34d399;">#${principal.id} (${principal.nome})</strong> será preservado como principal. O atleta <strong style="color: #f87171;">#${secundario.id} (${secundario.nome})</strong> terá seus contratos, partidas e histórico migrados e será removido.`;
        } else {
            btn.disabled = true;
            summary.innerHTML = `Selecione ambos os atletas acima e defina qual deles será mantido no sistema.`;
        }
    }

    // Funções de Gerenciamento de Abas
    window.trocarAba = function(aba) {
        document.getElementById('aba-merge-manual').style.display = (aba === 'merge') ? 'block' : 'none';
        document.getElementById('aba-duplicatas').style.display = (aba === 'duplicatas') ? 'block' : 'none';
        document.getElementById('aba-historico').style.display = (aba === 'historico') ? 'block' : 'none';

        document.getElementById('tab-btn-merge').className = (aba === 'merge') ? 'admin-btn admin-btn-primary' : 'admin-btn admin-btn-secondary';
        document.getElementById('tab-btn-duplicatas').className = (aba === 'duplicatas') ? 'admin-btn admin-btn-primary' : 'admin-btn admin-btn-secondary';
        document.getElementById('tab-btn-historico').className = (aba === 'historico') ? 'admin-btn admin-btn-primary' : 'admin-btn admin-btn-secondary';

        if (aba === 'duplicatas') {
            carregarDuplicatas();
        } else if (aba === 'historico') {
            carregarHistoricoLogs();
        }
    };

    // Função para carregar possíveis duplicatas
    window.carregarDuplicatas = function() {
        const container = document.getElementById('container-lista-duplicatas');
        container.innerHTML = `
            <div class="empty-card-placeholder">
                <span class="material-symbols-outlined" style="animation: spin 1s linear infinite;">sync</span>
                <p>Analisando banco de dados em busca de duplicatas...</p>
            </div>
        `;

        fetch('/admin/api_jogador_merge.php?action=duplicatas')
            .then(res => res.json())
            .then(data => {
                if (!data.success || !data.duplicatas || data.duplicatas.length === 0) {
                    container.innerHTML = `
                        <div class="empty-card-placeholder" style="color: #10b981;">
                            <span class="material-symbols-outlined" style="font-size: 48px; color: #10b981;">check_circle</span>
                            <p>Nenhum atleta duplicado evidente foi encontrado no banco de dados.</p>
                        </div>
                    `;
                    document.getElementById('badge-total-duplicatas').style.display = 'none';
                    return;
                }

                const badge = document.getElementById('badge-total-duplicatas');
                badge.textContent = data.duplicatas.length;
                badge.style.display = 'inline-block';

                let html = `
                    <div style="display: grid; gap: 12px;">
                `;

                data.duplicatas.forEach(d => {
                    const scoreBadge = (d.match_score >= 100) 
                        ? '<span style="background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.4); padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 700;">Nome Idêntico</span>'
                        : '<span style="background: rgba(245, 158, 11, 0.2); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.4); padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 700;">Similar / Fonético</span>';

                    html += `
                        <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; padding: 14px 18px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                            <div style="display: flex; align-items: center; gap: 15px; flex: 1; min-width: 280px;">
                                <div>
                                    ${scoreBadge}
                                </div>
                                <div style="flex: 1;">
                                    <div style="display: flex; gap: 20px; align-items: center; flex-wrap: wrap;">
                                        <div>
                                            <strong style="color: #38bdf8;">#${d.id1} - ${d.nome1}</strong>
                                            <span style="font-size: 12px; color: #94a3b8; display: block;">${d.pais1 || 'Sem País'} • OVR: ${d.nivel1} • ${d.clube1 || 'Sem clube'}</span>
                                        </div>
                                        <span class="material-symbols-outlined" style="color: #64748b;">compare_arrows</span>
                                        <div>
                                            <strong style="color: #a855f7;">#${d.id2} - ${d.nome2}</strong>
                                            <span style="font-size: 12px; color: #94a3b8; display: block;">${d.pais2 || 'Sem País'} • OVR: ${d.nivel2} • ${d.clube2 || 'Sem clube'}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <button onclick="prepararMerge(${d.id1}, ${d.id2})" class="admin-btn admin-btn-primary" style="padding: 6px 14px; font-size: 13px; display: flex; align-items: center; gap: 6px;">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">call_merge</span>
                                    Comparar e Fundir
                                </button>
                            </div>
                        </div>
                    `;
                });

                html += `</div>`;
                container.innerHTML = html;
            })
            .catch(err => {
                container.innerHTML = `<div class="empty-card-placeholder" style="color: #ef4444;"><p>Erro ao buscar duplicatas.</p></div>`;
            });
    };

    // Preencher os selects da Aba 1 e abrir a comparação
    window.prepararMerge = function(id1, id2) {
        trocarAba('merge');
        
        // Carrega opções individuais se não existirem no TomSelect
        [ { tom: tomSelect1, id: id1, num: 1 }, { tom: tomSelect2, id: id2, num: 2 } ].forEach(item => {
            if (item.tom) {
                fetch(`/admin/api_jogador_merge.php?action=search&id=${item.id}`)
                    .then(r => r.json())
                    .then(res => {
                        if (res.results && res.results.length > 0) {
                            item.tom.addOption(res.results[0]);
                        }
                        item.tom.setValue(item.id.toString());
                    })
                    .catch(() => {
                        item.tom.setValue(item.id.toString());
                    });
            }
        });
    };

    // Função para carregar histórico de logs
    window.carregarHistoricoLogs = function() {
        const container = document.getElementById('container-lista-logs');
        container.innerHTML = `
            <div class="empty-card-placeholder">
                <span class="material-symbols-outlined" style="animation: spin 1s linear infinite;">sync</span>
                <p>Carregando histórico de auditoria...</p>
            </div>
        `;

        fetch('/admin/api_jogador_merge.php?action=logs')
            .then(res => res.json())
            .then(data => {
                if (!data.success || !data.logs || data.logs.length === 0) {
                    container.innerHTML = `
                        <div class="empty-card-placeholder">
                            <span class="material-symbols-outlined">info</span>
                            <p>Nenhum merge foi registrado no histórico até o momento.</p>
                        </div>
                    `;
                    return;
                }

                let html = `
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 13px; text-align: left;">
                            <thead>
                                <tr style="border-bottom: 2px solid rgba(255,255,255,0.1); color: #94a3b8; font-family: 'Outfit', sans-serif;">
                                    <th style="padding: 10px 12px;">Data/Hora</th>
                                    <th style="padding: 10px 12px;">Atleta Preservado</th>
                                    <th style="padding: 10px 12px;">Atleta Absorvido/Excluído</th>
                                    <th style="padding: 10px 12px;">Admin</th>
                                    <th style="padding: 10px 12px;">Detalhes da Migração</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                data.logs.forEach(l => {
                    let det = null;
                    try {
                        det = JSON.parse(l.detalhes);
                    } catch(e) {}

                    let detHtml = '-';
                    if (det) {
                        detHtml = `<span style="color: #cbd5e1; font-size: 12px;">
                            ${det.contratos_migrados || 0} contratos migrados • ${det.transferencias || 0} transf. • ${det.jogos_clube || 0} jogos clube
                        </span>`;
                    }

                    html += `
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 10px 12px; color: #94a3b8; white-space: nowrap;">${l.data_merge}</td>
                            <td style="padding: 10px 12px; color: #34d399; font-weight: 600;">
                                #${l.id_principal} - ${l.nome_principal}
                            </td>
                            <td style="padding: 10px 12px; color: #f87171; text-decoration: line-through;">
                                #${l.id_secundario} - ${l.nome_secundario}
                            </td>
                            <td style="padding: 10px 12px; color: #38bdf8;">
                                ${l.nome_usuario || 'Admin'}
                            </td>
                            <td style="padding: 10px 12px;">
                                ${detHtml}
                            </td>
                        </tr>
                    `;
                });

                html += `</tbody></table></div>`;
                container.innerHTML = html;
            })
            .catch(err => {
                container.innerHTML = `<div class="empty-card-placeholder" style="color: #ef4444;"><p>Erro ao carregar histórico de logs.</p></div>`;
            });
    };

    // Auto-carregar contagem de duplicatas em background ao abrir a página
    setTimeout(() => {
        fetch('/admin/api_jogador_merge.php?action=duplicatas')
            .then(r => r.json())
            .then(d => {
                if (d.success && d.duplicatas && d.duplicatas.length > 0) {
                    const badge = document.getElementById('badge-total-duplicatas');
                    badge.textContent = d.duplicatas.length;
                    badge.style.display = 'inline-block';
                }
            }).catch(() => {});
    }, 500);

    // Execução do Merge
    document.getElementById('btn-executar-merge').addEventListener('click', function() {
        if (!dadosAtleta1 || !dadosAtleta2) return;

        const idPrincipal = (principalEscolhido === 1) ? dadosAtleta1.id : dadosAtleta2.id;
        const idSecundario = (principalEscolhido === 1) ? dadosAtleta2.id : dadosAtleta1.id;
        const nomePrincipal = (principalEscolhido === 1) ? dadosAtleta1.nome : dadosAtleta2.nome;
        const nomeSecundario = (principalEscolhido === 1) ? dadosAtleta2.nome : dadosAtleta1.nome;

        const confirmMsg = `ATENÇÃO: Confirma a fusão dos atletas?\n\n` +
            `• PRESERVAR: #${idPrincipal} - ${nomePrincipal}\n` +
            `• ABSORVER E EXCLUIR: #${idSecundario} - ${nomeSecundario}\n\n` +
            `Todos os contratos, jogos, gols, cartões e transferências de #${idSecundario} serão migrados para #${idPrincipal}.\nEsta ação não pode ser desfeita.`;

        if (!confirm(confirmMsg)) {
            return;
        }

        const btn = document.getElementById('btn-executar-merge');
        btn.disabled = true;
        btn.innerHTML = `<span class="material-symbols-outlined" style="animation: spin 1s linear infinite;">sync</span> Executando Merge...`;

        const formData = new FormData();
        formData.append('action', 'merge');
        formData.append('id_principal', idPrincipal);
        formData.append('id_secundario', idSecundario);

        fetch('/admin/api_jogador_merge.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert(`Merge realizado com sucesso! O atleta #${idSecundario} foi fundido em #${idPrincipal}.`);
                window.location.reload();
            } else {
                alert(`Erro ao realizar merge: ${data.error}`);
                btn.disabled = false;
                btn.innerHTML = `<span class="material-symbols-outlined">call_merge</span> Realizar Merge de Atletas`;
            }
        })
        .catch(err => {
            alert('Erro de comunicação com o servidor ao processar merge.');
            btn.disabled = false;
            btn.innerHTML = `<span class="material-symbols-outlined">call_merge</span> Realizar Merge de Atletas`;
        });
    });
});
</script>

<style>
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>

<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/elements/footer.php';
?>
