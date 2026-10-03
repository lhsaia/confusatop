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

$idOrigem = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($idOrigem <= 0) {
    header('Location: /admin/origens.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Buscar detalhes da origem
$stmt = $db->prepare("
    SELECT 
        o.ID, 
        o.Origem, 
        o.nomeM, 
        o.nomeF, 
        o.sobrenomeM, 
        o.sobrenomeF,
        COUNT(d.pais) AS total_paises
    FROM gen_origens o
    LEFT JOIN demografia d ON d.origem = o.ID
    WHERE o.ID = ?
    GROUP BY o.ID, o.Origem, o.nomeM, o.nomeF, o.sobrenomeM, o.sobrenomeF
    LIMIT 1
");
$stmt->execute([$idOrigem]);
$origem = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$origem) {
    header('Location: /admin/origens.php');
    exit;
}

$page_title = "Editar Origem - " . htmlspecialchars($origem['Origem']);
$css_filename = "home_redesign";
$css_login = 'login';
$aux_css = 'home_redesign';
$extra_css = 'admin_origens';
$css_versao = date('h:i:s');
include_once $_SERVER['DOCUMENT_ROOT'] . '/elements/header.php';
?>

<div class="admin-origens-container">

    <!-- Cabeçalho -->
    <div class="origens-header">
        <div>
            <div style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: #94a3b8; margin-bottom: 6px;">
                <a href="/admin/index.php" style="color: var(--origem-cyan); text-decoration: none; display: flex; align-items: center; gap: 4px;">
                    Painel Admin
                </a>
                <span>/</span>
                <a href="/admin/origens.php" style="color: var(--origem-cyan); text-decoration: none;">
                    Origens
                </a>
                <span>/</span>
                <span style="color: #cbd5e1;"><?php echo htmlspecialchars($origem['Origem']); ?></span>
            </div>
            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                <h1 class="origens-title" id="tituloOrigem">
                    <?php echo htmlspecialchars($origem['Origem']); ?>
                </h1>
                <button type="button" class="btn-action-icon btn-origem-secondary" id="btnEditarNomeOrigem" title="Renomear Origem">
                    <span class="material-symbols-outlined" style="font-size: 18px;">edit</span>
                </button>
            </div>
            <p class="origens-subtitle">
                ID #<?php echo (int)$origem['ID']; ?> &bull; Usado em <strong><?php echo (int)$origem['total_paises']; ?></strong> país(es).
            </p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="/admin/origens.php" class="btn-origem btn-origem-secondary">
                <span class="material-symbols-outlined">arrow_back</span>
                Voltar à Lista
            </a>
        </div>
    </div>

    <!-- Alert / Feedback -->
    <div id="alertFeedback" class="origem-alert">
        <span id="alertFeedbackTexto"></span>
        <button type="button" class="modal-close-btn" onclick="$('#alertFeedback').removeClass('show');">&times;</button>
    </div>

    <!-- Faixa de Contadores da Origem -->
    <div class="origem-stats-strip">
        <div class="origem-stat-box">
            <div class="origem-stat-icon" style="background: rgba(2, 132, 199, 0.15); color: #38bdf8;">
                <span class="material-symbols-outlined">male</span>
            </div>
            <div>
                <div class="origem-stat-number" id="statNomeM"><?php echo number_format((int)$origem['nomeM'], 0, ',', '.'); ?></div>
                <div class="origem-stat-label">Nomes Masculinos</div>
            </div>
        </div>

        <div class="origem-stat-box">
            <div class="origem-stat-icon" style="background: rgba(236, 72, 153, 0.15); color: #f472b6;">
                <span class="material-symbols-outlined">female</span>
            </div>
            <div>
                <div class="origem-stat-number" id="statNomeF"><?php echo number_format((int)$origem['nomeF'], 0, ',', '.'); ?></div>
                <div class="origem-stat-label">Nomes Femininos</div>
            </div>
        </div>

        <div class="origem-stat-box">
            <div class="origem-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #34d399;">
                <span class="material-symbols-outlined">badge</span>
            </div>
            <div>
                <div class="origem-stat-number" id="statSobrenomeM"><?php echo number_format((int)$origem['sobrenomeM'], 0, ',', '.'); ?></div>
                <div class="origem-stat-label">Sobrenomes Masc.</div>
            </div>
        </div>

        <div class="origem-stat-box">
            <div class="origem-stat-icon" style="background: rgba(168, 85, 247, 0.15); color: #c084fc;">
                <span class="material-symbols-outlined">badge</span>
            </div>
            <div>
                <div class="origem-stat-number" id="statSobrenomeF"><?php echo number_format((int)$origem['sobrenomeF'], 0, ',', '.'); ?></div>
                <div class="origem-stat-label">Sobrenomes Fem.</div>
            </div>
        </div>
    </div>

    <!-- Navegação por Abas -->
    <div class="origens-tabs">
        <button type="button" class="origem-tab-btn active" data-tab="tab-nomes">
            <span class="material-symbols-outlined" style="font-size: 20px;">person</span>
            Nomes Próprios
        </button>
        <button type="button" class="origem-tab-btn" data-tab="tab-sobrenomes">
            <span class="material-symbols-outlined" style="font-size: 20px;">family_restroom</span>
            Sobrenomes
        </button>
        <button type="button" class="origem-tab-btn" data-tab="tab-importar">
            <span class="material-symbols-outlined" style="font-size: 20px;">upload_file</span>
            Importação em Lote
        </button>
    </div>

    <!-- ABA 1: NOMES PRÓPRIOS -->
    <div id="tab-nomes" class="tab-pane active">
        
        <!-- Formulário Adicionar Nome -->
        <div class="form-card">
            <h3 style="font-family: 'Outfit', sans-serif; font-size: 16px; margin: 0 0 14px 0; color: #f8fafc; display: flex; align-items: center; gap: 8px;">
                <span class="material-symbols-outlined" style="color: var(--origem-cyan); font-size: 20px;">person_add</span>
                Adicionar Novo Nome Próprio
            </h3>
            <form id="formAdicionarNome" class="form-grid">
                <div class="form-group" style="flex: 2;">
                    <label class="form-label" for="inputNovoNome">Nome</label>
                    <input type="text" id="inputNovoNome" class="form-input" placeholder="Ex: Lucas, Sofia, Carlos..." required />
                </div>
                <div class="form-group" style="flex: 1.5;">
                    <label class="form-label">Gênero Válido</label>
                    <div class="gender-checks">
                        <label class="gender-checkbox-label">
                            <input type="checkbox" id="checkNomeM" checked />
                            <span style="color: #38bdf8;">Masculino</span>
                        </label>
                        <label class="gender-checkbox-label">
                            <input type="checkbox" id="checkNomeF" />
                            <span style="color: #f472b6;">Feminino</span>
                        </label>
                    </div>
                </div>
                <div class="form-group" style="flex: 1; min-width: 140px;">
                    <button type="submit" class="btn-origem btn-origem-primary" id="btnSalvarNome" style="width: 100%; height: 42px;">
                        <span class="material-symbols-outlined">add</span> Adicionar
                    </button>
                </div>
            </form>
        </div>

        <!-- Filtros de Busca de Nomes -->
        <div class="origens-toolbar">
            <div class="origens-search-wrapper">
                <span class="material-symbols-outlined origens-search-icon">search</span>
                <input type="text" id="buscaNome" class="origens-search-input" placeholder="Buscar nome cadastrado..." />
            </div>
            <div style="display: flex; gap: 10px; align-items: center;">
                <select id="filtroGeneroNome" class="form-select" style="width: auto; min-width: 160px;">
                    <option value="todos">Todos os Gêneros</option>
                    <option value="somente_m">Apenas Masculino</option>
                    <option value="somente_f">Apenas Feminino</option>
                    <option value="ambos">Unissex (M e F)</option>
                </select>
                <span style="color: #94a3b8; font-size: 13px; white-space: nowrap;">
                    <strong id="totalNomesListados" style="color: #f8fafc;">0</strong> nomes
                </span>
            </div>
        </div>

        <!-- Tabela de Nomes -->
        <div class="origens-table-wrapper">
            <table class="origens-table" id="tabelaNomes">
                <thead>
                    <tr>
                        <th style="width: 70px; text-align: center;">ID</th>
                        <th>Nome Próprio</th>
                        <th style="text-align: center; width: 140px;">Gênero</th>
                        <th style="width: 120px; text-align: center;">Ações</th>
                    </tr>
                </thead>
                <tbody id="tbodyNomes">
                    <tr>
                        <td colspan="4" style="text-align: center; color: #94a3b8; padding: 24px;">Carregando nomes...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Paginação Nomes -->
        <div class="origens-pagination" id="paginacaoNomes"></div>
    </div>

    <!-- ABA 2: SOBRENOMES -->
    <div id="tab-sobrenomes" class="tab-pane">
        
        <!-- Formulário Adicionar Sobrenome -->
        <div class="form-card">
            <h3 style="font-family: 'Outfit', sans-serif; font-size: 16px; margin: 0 0 14px 0; color: #f8fafc; display: flex; align-items: center; gap: 8px;">
                <span class="material-symbols-outlined" style="color: var(--origem-emerald); font-size: 20px;">badge</span>
                Adicionar Novo Sobrenome
            </h3>
            <form id="formAdicionarSobrenome" class="form-grid">
                <div class="form-group" style="flex: 2;">
                    <label class="form-label" for="inputNovoSobrenome">Sobrenome</label>
                    <input type="text" id="inputNovoSobrenome" class="form-input" placeholder="Ex: Silva, Müller, Rossi..." required />
                </div>
                <div class="form-group" style="flex: 1.5;">
                    <label class="form-label">Gênero Válido</label>
                    <div class="gender-checks">
                        <label class="gender-checkbox-label">
                            <input type="checkbox" id="checkSobrenomeM" checked />
                            <span style="color: #38bdf8;">Masculino</span>
                        </label>
                        <label class="gender-checkbox-label">
                            <input type="checkbox" id="checkSobrenomeF" checked />
                            <span style="color: #f472b6;">Feminino</span>
                        </label>
                    </div>
                </div>
                <div class="form-group" style="flex: 1; min-width: 140px;">
                    <button type="submit" class="btn-origem btn-origem-success" id="btnSalvarSobrenome" style="width: 100%; height: 42px;">
                        <span class="material-symbols-outlined">add</span> Adicionar
                    </button>
                </div>
            </form>
        </div>

        <!-- Filtros de Busca de Sobrenomes -->
        <div class="origens-toolbar">
            <div class="origens-search-wrapper">
                <span class="material-symbols-outlined origens-search-icon">search</span>
                <input type="text" id="buscaSobrenome" class="origens-search-input" placeholder="Buscar sobrenome cadastrado..." />
            </div>
            <div style="display: flex; gap: 10px; align-items: center;">
                <select id="filtroGeneroSobrenome" class="form-select" style="width: auto; min-width: 160px;">
                    <option value="todos">Todos os Gêneros</option>
                    <option value="ambos">Ambos (M e F)</option>
                    <option value="somente_m">Apenas Masculino</option>
                    <option value="somente_f">Apenas Feminino</option>
                </select>
                <span style="color: #94a3b8; font-size: 13px; white-space: nowrap;">
                    <strong id="totalSobrenomesListados" style="color: #f8fafc;">0</strong> sobrenomes
                </span>
            </div>
        </div>

        <!-- Tabela de Sobrenomes -->
        <div class="origens-table-wrapper">
            <table class="origens-table" id="tabelaSobrenomes">
                <thead>
                    <tr>
                        <th style="width: 70px; text-align: center;">ID</th>
                        <th>Sobrenome</th>
                        <th style="text-align: center; width: 140px;">Gênero</th>
                        <th style="width: 120px; text-align: center;">Ações</th>
                    </tr>
                </thead>
                <tbody id="tbodySobrenomes">
                    <tr>
                        <td colspan="4" style="text-align: center; color: #94a3b8; padding: 24px;">Carregando sobrenomes...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Paginação Sobrenomes -->
        <div class="origens-pagination" id="paginacaoSobrenomes"></div>
    </div>

    <!-- ABA 3: IMPORTAÇÃO EM LOTE -->
    <div id="tab-importar" class="tab-pane">
        <div class="form-card" style="max-width: 800px; margin: 0 auto;">
            <h3 style="font-family: 'Outfit', sans-serif; font-size: 18px; margin: 0 0 10px 0; color: #f8fafc; display: flex; align-items: center; gap: 8px;">
                <span class="material-symbols-outlined" style="color: var(--origem-indigo); font-size: 24px;">upload_file</span>
                Importar Lista de Nomes ou Sobrenomes
            </h3>
            <p style="color: #94a3b8; font-size: 13.5px; margin-bottom: 18px; line-height: 1.5;">
                Cole abaixo uma lista de nomes ou sobrenomes (um por linha, ou separados por vírgula / ponto e vírgula). Marcadores de lista como traços (<code>-</code>), bullets (<code>•</code>, <code>*</code>) e numerações (<code>1.</code>) são <strong>removidos automaticamente</strong> pelo sistema, que também ignora termos duplicados.
            </p>

            <form id="formImportarLote">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div class="form-group">
                        <label class="form-label">Tipo de Cadastro</label>
                        <div style="display: flex; gap: 18px; padding: 8px 0;">
                            <label style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer;">
                                <input type="radio" name="tipoImportacao" value="nome" checked style="accent-color: #0284c7;" />
                                <span>Nomes Próprios</span>
                            </label>
                            <label style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer;">
                                <input type="radio" name="tipoImportacao" value="sobrenome" style="accent-color: #10b981;" />
                                <span>Sobrenomes</span>
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Gênero dos Registros</label>
                        <div class="gender-checks">
                            <label class="gender-checkbox-label">
                                <input type="checkbox" id="importCheckM" checked />
                                <span style="color: #38bdf8;">Masculino</span>
                            </label>
                            <label class="gender-checkbox-label">
                                <input type="checkbox" id="importCheckF" />
                                <span style="color: #f472b6;">Feminino</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Detecção Automática Eslava/Russa -->
                <div class="form-group" style="margin-bottom: 16px; background: rgba(56, 189, 248, 0.05); border: 1px solid rgba(56, 189, 248, 0.2); border-radius: 8px; padding: 12px 16px;">
                    <label class="gender-checkbox-label" style="font-weight: 600; color: #f8fafc;">
                        <input type="checkbox" id="importCheckAutoEslavo" />
                        <span style="color: #38bdf8;">Detectar gênero automaticamente por sufixos eslavos/russos</span>
                    </label>
                    <div style="font-size: 12px; color: #94a3b8; margin-top: 4px; margin-left: 22px; line-height: 1.4;">
                        Classifica automaticamente cada termo como <strong>Masculino</strong> (<em>-ov, -ev, -in, -sky, -ski</em>) ou <strong>Feminino</strong> (<em>-ova, -eva, -ina, -skaya, -aya</em>), ideal para listas misturadas.
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 18px;">
                    <label class="form-label" for="textareaLote">Lista de Termos</label>
                    <textarea id="textareaLote" class="form-textarea" rows="10" placeholder="Cole aqui os nomes ou sobrenomes...&#10;Exemplo (com ou sem marcadores):&#10;- Gabriel&#10;- Lucas&#10;- Mateus&#10;- Felipe" required></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 12px; align-items: center;">
                    <span id="importStatusInfo" style="font-size: 13px; color: #94a3b8;"></span>
                    <button type="submit" class="btn-origem btn-origem-primary" id="btnExecutarImportacao">
                        <span class="material-symbols-outlined">send</span>
                        Processar Importação
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<!-- MODAL: EDITAR NOME / SOBRENOME -->
<div class="origem-modal-backdrop" id="modalEditarItem">
    <div class="origem-modal">
        <div class="modal-header">
            <h3 class="modal-title" id="modalEditarItemTitulo">✏️ Editar Registro</h3>
            <button type="button" class="modal-close-btn" onclick="fecharModal('#modalEditarItem')">&times;</button>
        </div>
        <form id="formEditarItem">
            <input type="hidden" id="editItemId" />
            <input type="hidden" id="editItemTipo" /> <!-- 'nome' ou 'sobrenome' -->

            <div class="form-group" style="margin-bottom: 14px;">
                <label class="form-label" for="editItemTexto">Texto</label>
                <input type="text" id="editItemTexto" class="form-input" required autofocus />
            </div>

            <div class="form-group">
                <label class="form-label">Gênero Válido</label>
                <div class="gender-checks">
                    <label class="gender-checkbox-label">
                        <input type="checkbox" id="editItemCheckM" />
                        <span style="color: #38bdf8;">Masculino</span>
                    </label>
                    <label class="gender-checkbox-label">
                        <input type="checkbox" id="editItemCheckF" />
                        <span style="color: #f472b6;">Feminino</span>
                    </label>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-origem btn-origem-secondary" onclick="fecharModal('#modalEditarItem')">Cancelar</button>
                <button type="submit" class="btn-origem btn-origem-primary" id="btnSalvarEdicaoItem">
                    <span class="material-symbols-outlined">save</span> Salvar Alterações
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: RENOMEAR ORIGEM -->
<div class="origem-modal-backdrop" id="modalRenomearOrigem">
    <div class="origem-modal">
        <div class="modal-header">
            <h3 class="modal-title">✏️ Renomear Origem</h3>
            <button type="button" class="modal-close-btn" onclick="fecharModal('#modalRenomearOrigem')">&times;</button>
        </div>
        <form id="formRenomearOrigem">
            <div class="form-group">
                <label class="form-label" for="renomearNomeOrigem">Nome da Origem</label>
                <input type="text" id="renomearNomeOrigem" class="form-input" value="<?php echo htmlspecialchars($origem['Origem']); ?>" required autofocus />
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-origem btn-origem-secondary" onclick="fecharModal('#modalRenomearOrigem')">Cancelar</button>
                <button type="submit" class="btn-origem btn-origem-primary" id="btnSalvarRenomear">
                    <span class="material-symbols-outlined">save</span> Salvar Nome
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const ID_ORIGEM = <?php echo $idOrigem; ?>;
let paginaNomesAtual = 1;
let paginaSobrenomesAtual = 1;

function exibirAlerta(texto, tipo = 'success') {
    const $alert = $('#alertFeedback');
    $alert.removeClass('origem-alert-success origem-alert-danger').addClass(tipo === 'success' ? 'origem-alert-success' : 'origem-alert-danger');
    $('#alertFeedbackTexto').text(texto);
    $alert.addClass('show');
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function fecharModal(seletor) {
    $(seletor).removeClass('show');
}

function renderizarBadgeGenero(m, f) {
    m = parseInt(m, 10);
    f = parseInt(f, 10);
    if (m === 1 && f === 1) {
        return '<span class="badge-count badge-purple"><span class="material-symbols-outlined" style="font-size: 13px;">wc</span> Unissex</span>';
    } else if (m === 1) {
        return '<span class="badge-count badge-blue"><span class="material-symbols-outlined" style="font-size: 13px;">male</span> Masc</span>';
    } else if (f === 1) {
        return '<span class="badge-count badge-pink"><span class="material-symbols-outlined" style="font-size: 13px;">female</span> Fem</span>';
    }
    return '<span style="color: #64748b; font-size: 12px;">Nenhum</span>';
}

// -------------------------------------------------------------
// CARREGAR LISTA DE NOMES
// -------------------------------------------------------------
function carregarNomes(pagina = 1) {
    paginaNomesAtual = pagina;
    const busca = $('#buscaNome').val().trim();
    const genero = $('#filtroGeneroNome').val();

    $('#tbodyNomes').html('<tr><td colspan="4" style="text-align: center; color: #94a3b8; padding: 20px;">Carregando nomes...</td></tr>');

    $.ajax({
        url: '/admin/api_origens.php',
        method: 'GET',
        dataType: 'json',
        data: {
            action: 'listar_nomes',
            origem: ID_ORIGEM,
            busca: busca,
            genero: genero,
            pagina: pagina
        },
        success: function(res) {
            if (!res.success) {
                $('#tbodyNomes').html('<tr><td colspan="4" style="text-align: center; color: #f87171; padding: 20px;">' + (res.error || 'Erro ao carregar nomes.') + '</td></tr>');
                return;
            }

            $('#totalNomesListados').text(res.total);
            if (res.itens.length === 0) {
                $('#tbodyNomes').html('<tr><td colspan="4" style="text-align: center; color: #64748b; padding: 24px;">Nenhum nome encontrado com os filtros selecionados.</td></tr>');
                $('#paginacaoNomes').empty();
                return;
            }

            let html = '';
            res.itens.forEach(function(item){
                html += `
                    <tr data-id="${item.ID}">
                        <td style="text-align: center; color: #64748b; font-size: 13px;">#${item.ID}</td>
                        <td><strong style="color: #f8fafc; font-size: 14.5px;">${escapeHtml(item.Nome)}</strong></td>
                        <td style="text-align: center;">${renderizarBadgeGenero(item.M, item.F)}</td>
                        <td style="text-align: center; white-space: nowrap;">
                            <div style="display: inline-flex; gap: 6px;">
                                <button type="button" class="btn-action-icon btn-origem-secondary btn-editar-nome" data-id="${item.ID}" data-nome="${escapeHtml(item.Nome)}" data-m="${item.M}" data-f="${item.F}" title="Editar">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">edit</span>
                                </button>
                                <button type="button" class="btn-action-icon btn-origem-danger btn-excluir-nome" data-id="${item.ID}" data-nome="${escapeHtml(item.Nome)}" title="Excluir">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">delete</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });

            $('#tbodyNomes').html(html);
            renderizarPaginacao('#paginacaoNomes', res.pagina, res.total_paginas, 'carregarNomes');
        },
        error: function() {
            $('#tbodyNomes').html('<tr><td colspan="4" style="text-align: center; color: #f87171; padding: 20px;">Erro de conexão ao buscar nomes.</td></tr>');
        }
    });
}

// -------------------------------------------------------------
// CARREGAR LISTA DE SOBRENOMES
// -------------------------------------------------------------
function carregarSobrenomes(pagina = 1) {
    paginaSobrenomesAtual = pagina;
    const busca = $('#buscaSobrenome').val().trim();
    const genero = $('#filtroGeneroSobrenome').val();

    $('#tbodySobrenomes').html('<tr><td colspan="4" style="text-align: center; color: #94a3b8; padding: 20px;">Carregando sobrenomes...</td></tr>');

    $.ajax({
        url: '/admin/api_origens.php',
        method: 'GET',
        dataType: 'json',
        data: {
            action: 'listar_sobrenomes',
            origem: ID_ORIGEM,
            busca: busca,
            genero: genero,
            pagina: pagina
        },
        success: function(res) {
            if (!res.success) {
                $('#tbodySobrenomes').html('<tr><td colspan="4" style="text-align: center; color: #f87171; padding: 20px;">' + (res.error || 'Erro ao carregar sobrenomes.') + '</td></tr>');
                return;
            }

            $('#totalSobrenomesListados').text(res.total);
            if (res.itens.length === 0) {
                $('#tbodySobrenomes').html('<tr><td colspan="4" style="text-align: center; color: #64748b; padding: 24px;">Nenhum sobrenome encontrado com os filtros selecionados.</td></tr>');
                $('#paginacaoSobrenomes').empty();
                return;
            }

            let html = '';
            res.itens.forEach(function(item){
                html += `
                    <tr data-id="${item.ID}">
                        <td style="text-align: center; color: #64748b; font-size: 13px;">#${item.ID}</td>
                        <td><strong style="color: #f8fafc; font-size: 14.5px;">${escapeHtml(item.Sobrenome)}</strong></td>
                        <td style="text-align: center;">${renderizarBadgeGenero(item.M, item.F)}</td>
                        <td style="text-align: center; white-space: nowrap;">
                            <div style="display: inline-flex; gap: 6px;">
                                <button type="button" class="btn-action-icon btn-origem-secondary btn-editar-sobrenome" data-id="${item.ID}" data-sobrenome="${escapeHtml(item.Sobrenome)}" data-m="${item.M}" data-f="${item.F}" title="Editar">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">edit</span>
                                </button>
                                <button type="button" class="btn-action-icon btn-origem-danger btn-excluir-sobrenome" data-id="${item.ID}" data-sobrenome="${escapeHtml(item.Sobrenome)}" title="Excluir">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">delete</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });

            $('#tbodySobrenomes').html(html);
            renderizarPaginacao('#paginacaoSobrenomes', res.pagina, res.total_paginas, 'carregarSobrenomes');
        },
        error: function() {
            $('#tbodySobrenomes').html('<tr><td colspan="4" style="text-align: center; color: #f87171; padding: 20px;">Erro de conexão ao buscar sobrenomes.</td></tr>');
        }
    });
}

function renderizarPaginacao(seletor, paginaAtual, totalPaginas, fnCallbackNome) {
    const $container = $(seletor);
    $container.empty();
    if (totalPaginas <= 1) return;

    // Botão Anterior
    const $prev = $('<button class="page-btn"><span class="material-symbols-outlined" style="font-size: 16px;">chevron_left</span></button>');
    if (paginaAtual <= 1) {
        $prev.prop('disabled', true);
    } else {
        $prev.on('click', () => window[fnCallbackNome](paginaAtual - 1));
    }
    $container.append($prev);

    // Páginas
    const range = 2;
    let start = Math.max(1, paginaAtual - range);
    let end = Math.min(totalPaginas, paginaAtual + range);

    if (start > 1) {
        $container.append($('<button class="page-btn">1</button>').on('click', () => window[fnCallbackNome](1)));
        if (start > 2) $container.append('<span style="color:#64748b; padding: 0 4px;">...</span>');
    }

    for (let p = start; p <= end; p++) {
        const $btn = $('<button class="page-btn' + (p === paginaAtual ? ' active' : '') + '">' + p + '</button>');
        if (p !== paginaAtual) {
            $btn.on('click', ((page) => () => window[fnCallbackNome](page))(p));
        }
        $container.append($btn);
    }

    if (end < totalPaginas) {
        if (end < totalPaginas - 1) $container.append('<span style="color:#64748b; padding: 0 4px;">...</span>');
        $container.append($('<button class="page-btn">' + totalPaginas + '</button>').on('click', () => window[fnCallbackNome](totalPaginas)));
    }

    // Botão Próximo
    const $next = $('<button class="page-btn"><span class="material-symbols-outlined" style="font-size: 16px;">chevron_right</span></button>');
    if (paginaAtual >= totalPaginas) {
        $next.prop('disabled', true);
    } else {
        $next.on('click', () => window[fnCallbackNome](paginaAtual + 1));
    }
    $container.append($next);
}

function escapeHtml(text) {
    if (!text) return '';
    return text.replace(/[&<>"']/g, function(m) {
        return ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        })[m];
    });
}

$(document).ready(function(){

    // Alternar Abas
    $('.origem-tab-btn').on('click', function(){
        $('.origem-tab-btn').removeClass('active');
        $(this).addClass('active');
        $('.tab-pane').removeClass('active');
        const tabId = $(this).attr('data-tab');
        $('#' + tabId).addClass('active');
    });

    // Iniciar carregamentos
    carregarNomes(1);
    carregarSobrenomes(1);

    // Filtros de Nomes
    let timerBuscaNome;
    $('#buscaNome').on('input', function(){
        clearTimeout(timerBuscaNome);
        timerBuscaNome = setTimeout(() => carregarNomes(1), 300);
    });
    $('#filtroGeneroNome').on('change', () => carregarNomes(1));

    // Filtros de Sobrenomes
    let timerBuscaSobrenome;
    $('#buscaSobrenome').on('input', function(){
        clearTimeout(timerBuscaSobrenome);
        timerBuscaSobrenome = setTimeout(() => carregarSobrenomes(1), 300);
    });
    $('#filtroGeneroSobrenome').on('change', () => carregarSobrenomes(1));

    // Adicionar Nome
    $('#formAdicionarNome').on('submit', function(e){
        e.preventDefault();
        const nome = $('#inputNovoNome').val().trim();
        const m = $('#checkNomeM').is(':checked') ? 1 : 0;
        const f = $('#checkNomeF').is(':checked') ? 1 : 0;

        if (!nome) return;
        if (m === 0 && f === 0) {
            alert('Marque ao menos um gênero (Masculino ou Feminino).');
            return;
        }

        const $btn = $('#btnSalvarNome');
        $btn.prop('disabled', true).text('Salvando...');

        $.ajax({
            url: '/admin/api_origens.php',
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'adicionar_nome',
                origem: ID_ORIGEM,
                nome: nome,
                m: m,
                f: f
            },
            success: function(res) {
                if (res.success) {
                    $('#inputNovoNome').val('').focus();
                    exibirAlerta(res.message || 'Nome adicionado com sucesso!', 'success');
                    carregarNomes(1);
                } else {
                    exibirAlerta(res.error || 'Erro ao adicionar nome.', 'danger');
                }
            },
            error: function(xhr) {
                const res = xhr.responseJSON || {};
                exibirAlerta(res.error || 'Erro ao adicionar nome.', 'danger');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<span class="material-symbols-outlined">add</span> Adicionar');
            }
        });
    });

    // Adicionar Sobrenome
    $('#formAdicionarSobrenome').on('submit', function(e){
        e.preventDefault();
        const sobrenome = $('#inputNovoSobrenome').val().trim();
        const m = $('#checkSobrenomeM').is(':checked') ? 1 : 0;
        const f = $('#checkSobrenomeF').is(':checked') ? 1 : 0;

        if (!sobrenome) return;
        if (m === 0 && f === 0) {
            alert('Marque ao menos um gênero (Masculino ou Feminino).');
            return;
        }

        const $btn = $('#btnSalvarSobrenome');
        $btn.prop('disabled', true).text('Salvando...');

        $.ajax({
            url: '/admin/api_origens.php',
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'adicionar_sobrenome',
                origem: ID_ORIGEM,
                sobrenome: sobrenome,
                m: m,
                f: f
            },
            success: function(res) {
                if (res.success) {
                    $('#inputNovoSobrenome').val('').focus();
                    exibirAlerta(res.message || 'Sobrenome adicionado com sucesso!', 'success');
                    carregarSobrenomes(1);
                } else {
                    exibirAlerta(res.error || 'Erro ao adicionar sobrenome.', 'danger');
                }
            },
            error: function(xhr) {
                const res = xhr.responseJSON || {};
                exibirAlerta(res.error || 'Erro ao adicionar sobrenome.', 'danger');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<span class="material-symbols-outlined">add</span> Adicionar');
            }
        });
    });

    // Abrir Modal Edição Nome
    $(document).on('click', '.btn-editar-nome', function(){
        const id = $(this).attr('data-id');
        const nome = $(this).attr('data-nome');
        const m = $(this).attr('data-m') === '1';
        const f = $(this).attr('data-f') === '1';

        $('#modalEditarItemTitulo').text('✏️ Editar Nome Próprio');
        $('#editItemId').val(id);
        $('#editItemTipo').val('nome');
        $('#editItemTexto').val(nome);
        $('#editItemCheckM').prop('checked', m);
        $('#editItemCheckF').prop('checked', f);

        $('#modalEditarItem').addClass('show');
        setTimeout(() => $('#editItemTexto').focus(), 100);
    });

    // Abrir Modal Edição Sobrenome
    $(document).on('click', '.btn-editar-sobrenome', function(){
        const id = $(this).attr('data-id');
        const sobrenome = $(this).attr('data-sobrenome');
        const m = $(this).attr('data-m') === '1';
        const f = $(this).attr('data-f') === '1';

        $('#modalEditarItemTitulo').text('✏️ Editar Sobrenome');
        $('#editItemId').val(id);
        $('#editItemTipo').val('sobrenome');
        $('#editItemTexto').val(sobrenome);
        $('#editItemCheckM').prop('checked', m);
        $('#editItemCheckF').prop('checked', f);

        $('#modalEditarItem').addClass('show');
        setTimeout(() => $('#editItemTexto').focus(), 100);
    });

    // Salvar Edição do Item (Nome ou Sobrenome)
    $('#formEditarItem').on('submit', function(e){
        e.preventDefault();
        const id = $('#editItemId').val();
        const tipo = $('#editItemTipo').val();
        const texto = $('#editItemTexto').val().trim();
        const m = $('#editItemCheckM').is(':checked') ? 1 : 0;
        const f = $('#editItemCheckF').is(':checked') ? 1 : 0;

        if (!texto) return;
        if (m === 0 && f === 0) {
            alert('Marque ao menos um gênero.');
            return;
        }

        const action = (tipo === 'sobrenome') ? 'editar_sobrenome' : 'editar_nome';
        const payload = {
            action: action,
            id: id,
            origem: ID_ORIGEM,
            m: m,
            f: f
        };
        if (tipo === 'sobrenome') {
            payload.sobrenome = texto;
        } else {
            payload.nome = texto;
        }

        const $btn = $('#btnSalvarEdicaoItem');
        $btn.prop('disabled', true).text('Salvando...');

        $.ajax({
            url: '/admin/api_origens.php',
            method: 'POST',
            dataType: 'json',
            data: payload,
            success: function(res) {
                fecharModal('#modalEditarItem');
                if (res.success) {
                    exibirAlerta(res.message || 'Atualizado com sucesso!', 'success');
                    if (tipo === 'sobrenome') {
                        carregarSobrenomes(paginaSobrenomesAtual);
                    } else {
                        carregarNomes(paginaNomesAtual);
                    }
                } else {
                    exibirAlerta(res.error || 'Erro ao salvar.', 'danger');
                }
            },
            error: function(xhr) {
                const res = xhr.responseJSON || {};
                exibirAlerta(res.error || 'Erro ao salvar alterações.', 'danger');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<span class="material-symbols-outlined">save</span> Salvar Alterações');
            }
        });
    });

    // Excluir Nome
    $(document).on('click', '.btn-excluir-nome', function(){
        const id = $(this).attr('data-id');
        const nome = $(this).attr('data-nome');

        if (!confirm('Excluir o nome "' + nome + '" (# ' + id + ')?')) return;

        $.ajax({
            url: '/admin/api_origens.php',
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'excluir_nome',
                id: id,
                origem: ID_ORIGEM
            },
            success: function(res) {
                if (res.success) {
                    exibirAlerta(res.message || 'Nome excluído.', 'success');
                    carregarNomes(paginaNomesAtual);
                } else {
                    exibirAlerta(res.error || 'Erro ao excluir.', 'danger');
                }
            },
            error: function(xhr) {
                const res = xhr.responseJSON || {};
                exibirAlerta(res.error || 'Erro ao excluir nome.', 'danger');
            }
        });
    });

    // Excluir Sobrenome
    $(document).on('click', '.btn-excluir-sobrenome', function(){
        const id = $(this).attr('data-id');
        const sobrenome = $(this).attr('data-sobrenome');

        if (!confirm('Excluir o sobrenome "' + sobrenome + '" (# ' + id + ')?')) return;

        $.ajax({
            url: '/admin/api_origens.php',
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'excluir_sobrenome',
                id: id,
                origem: ID_ORIGEM
            },
            success: function(res) {
                if (res.success) {
                    exibirAlerta(res.message || 'Sobrenome excluído.', 'success');
                    carregarSobrenomes(paginaSobrenomesAtual);
                } else {
                    exibirAlerta(res.error || 'Erro ao excluir.', 'danger');
                }
            },
            error: function(xhr) {
                const res = xhr.responseJSON || {};
                exibirAlerta(res.error || 'Erro ao excluir sobrenome.', 'danger');
            }
        });
    });

    // Importação em Lote
    $('#formImportarLote').on('submit', function(e){
        e.preventDefault();
        const tipo = $('input[name="tipoImportacao"]:checked').val();
        const texto = $('#textareaLote').val().trim();
        const m = $('#importCheckM').is(':checked') ? 1 : 0;
        const f = $('#importCheckF').is(':checked') ? 1 : 0;
        const autoEslavo = $('#importCheckAutoEslavo').is(':checked') ? 1 : 0;

        if (!texto) {
            alert('Cole ao menos um termo na caixa de texto.');
            return;
        }
        if (!autoEslavo && m === 0 && f === 0) {
            alert('Selecione ao menos um gênero para os itens (ou marque a detecção automática).');
            return;
        }

        const $btn = $('#btnExecutarImportacao');
        const $status = $('#importStatusInfo');
        $btn.prop('disabled', true).text('Processando importação...');
        $status.text('Importando termos...');

        $.ajax({
            url: '/admin/api_origens.php',
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'importar_lote',
                origem: ID_ORIGEM,
                tipo: tipo,
                texto: texto,
                m: m,
                f: f,
                auto_eslavo: autoEslavo
            },
            success: function(res) {
                if (res.success) {
                    $('#textareaLote').val('');
                    exibirAlerta(res.message, 'success');
                    $status.text('');
                    if (tipo === 'sobrenome') {
                        carregarSobrenomes(1);
                        $('.origem-tab-btn[data-tab="tab-sobrenomes"]').click();
                    } else {
                        carregarNomes(1);
                        $('.origem-tab-btn[data-tab="tab-nomes"]').click();
                    }
                } else {
                    exibirAlerta(res.error || 'Erro ao importar lote.', 'danger');
                    $status.text('Erro na importação.');
                }
            },
            error: function(xhr) {
                const res = xhr.responseJSON || {};
                exibirAlerta(res.error || 'Erro ao importar lista.', 'danger');
                $status.text('Erro de comunicação.');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<span class="material-symbols-outlined">send</span> Processar Importação');
            }
        });
    });

    // Abrir Modal Renomear Origem
    $('#btnEditarNomeOrigem').on('click', function(){
        $('#modalRenomearOrigem').addClass('show');
        setTimeout(() => $('#renomearNomeOrigem').focus(), 100);
    });

    // Salvar Renomeação da Origem
    $('#formRenomearOrigem').on('submit', function(e){
        e.preventDefault();
        const nome = $('#renomearNomeOrigem').val().trim();
        if (!nome) return;

        const $btn = $('#btnSalvarRenomear');
        $btn.prop('disabled', true).text('Salvando...');

        $.ajax({
            url: '/admin/api_origens.php',
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'renomear_origem',
                id: ID_ORIGEM,
                origem: nome
            },
            success: function(res) {
                fecharModal('#modalRenomearOrigem');
                if (res.success) {
                    $('#tituloOrigem').text(nome);
                    exibirAlerta(res.message || 'Origem renomeada com sucesso!', 'success');
                } else {
                    exibirAlerta(res.error || 'Erro ao renomear origem.', 'danger');
                }
            },
            error: function(xhr) {
                const res = xhr.responseJSON || {};
                exibirAlerta(res.error || 'Erro ao renomear origem.', 'danger');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<span class="material-symbols-outlined">save</span> Salvar Nome');
            }
        });
    });

    // Fechar modais ao clicar no backdrop
    $('.origem-modal-backdrop').on('click', function(e){
        if (e.target === this) {
            $(this).removeClass('show');
        }
    });

});
</script>

<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/elements/footer.php';
?>
