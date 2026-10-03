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

$database = new Database();
$db = $database->getConnection();

// Buscar todas as origens com contagem de países vinculados
$query = "
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
    GROUP BY o.ID, o.Origem, o.nomeM, o.nomeF, o.sobrenomeM, o.sobrenomeF
    ORDER BY o.Origem ASC
";
$stmt = $db->query($query);
$origens = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Totais gerais para os cards de estatísticas
$totalOrigens = count($origens);
$totalNomeM = 0;
$totalNomeF = 0;
$totalSobrenomeM = 0;
$totalSobrenomeF = 0;

foreach ($origens as $o) {
    $totalNomeM += (int)$o['nomeM'];
    $totalNomeF += (int)$o['nomeF'];
    $totalSobrenomeM += (int)$o['sobrenomeM'];
    $totalSobrenomeF += (int)$o['sobrenomeF'];
}

$page_title = "Gestão de Origens e Demografia";
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
                    <span class="material-symbols-outlined" style="font-size: 16px;">arrow_back</span> Painel Admin
                </a>
                <span>/</span>
                <span>Origens Demográficas</span>
            </div>
            <h1 class="origens-title">Gestão de Origens & Demografia</h1>
            <p class="origens-subtitle">
                Cadastre e mantenha as culturas, primeiros nomes e sobrenomes usados na geração de atletas e seleções.
            </p>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <button type="button" class="btn-origem btn-origem-primary" id="btnAbrirModalCriar">
                <span class="material-symbols-outlined">add_circle</span>
                Nova Origem
            </button>
        </div>
    </div>

    <!-- Alert / Feedback -->
    <div id="alertFeedback" class="origem-alert">
        <span id="alertFeedbackTexto"></span>
        <button type="button" class="modal-close-btn" onclick="$('#alertFeedback').removeClass('show');">&times;</button>
    </div>

    <!-- Faixa de Estatísticas Globais -->
    <div class="origem-stats-strip">
        <div class="origem-stat-box">
            <div class="origem-stat-icon" style="background: rgba(56, 189, 248, 0.15); color: #38bdf8;">
                <span class="material-symbols-outlined">public</span>
            </div>
            <div>
                <div class="origem-stat-number"><?php echo number_format($totalOrigens, 0, ',', '.'); ?></div>
                <div class="origem-stat-label">Origens Culturais</div>
            </div>
        </div>

        <div class="origem-stat-box">
            <div class="origem-stat-icon" style="background: rgba(2, 132, 199, 0.15); color: #38bdf8;">
                <span class="material-symbols-outlined">male</span>
            </div>
            <div>
                <div class="origem-stat-number"><?php echo number_format($totalNomeM, 0, ',', '.'); ?></div>
                <div class="origem-stat-label">Nomes Masc.</div>
            </div>
        </div>

        <div class="origem-stat-box">
            <div class="origem-stat-icon" style="background: rgba(236, 72, 153, 0.15); color: #f472b6;">
                <span class="material-symbols-outlined">female</span>
            </div>
            <div>
                <div class="origem-stat-number"><?php echo number_format($totalNomeF, 0, ',', '.'); ?></div>
                <div class="origem-stat-label">Nomes Fem.</div>
            </div>
        </div>

        <div class="origem-stat-box">
            <div class="origem-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #34d399;">
                <span class="material-symbols-outlined">badge</span>
            </div>
            <div>
                <div class="origem-stat-number"><?php echo number_format($totalSobrenomeM + $totalSobrenomeF, 0, ',', '.'); ?></div>
                <div class="origem-stat-label">Sobrenomes</div>
            </div>
        </div>
    </div>

    <!-- Barra de Busca -->
    <div class="origens-toolbar">
        <div class="origens-search-wrapper">
            <span class="material-symbols-outlined origens-search-icon">search</span>
            <input type="text" id="filtroOrigens" class="origens-search-input" placeholder="Buscar origem por nome ou ID..." />
        </div>
        <div style="color: #94a3b8; font-size: 13.5px;">
            Exibindo <strong id="contadorVisiveis" style="color: #f8fafc;"><?php echo $totalOrigens; ?></strong> origens
        </div>
    </div>

    <!-- Tabela de Origens -->
    <div class="origens-table-wrapper">
        <table class="origens-table" id="tabelaOrigens">
            <thead>
                <tr>
                    <th style="width: 70px; text-align: center;">ID</th>
                    <th>Origem Cultural</th>
                    <th style="text-align: center;">Nomes Masc.</th>
                    <th style="text-align: center;">Nomes Fem.</th>
                    <th style="text-align: center;">Sobrenomes Masc.</th>
                    <th style="text-align: center;">Sobrenomes Fem.</th>
                    <th style="text-align: center;">Países Vinculados</th>
                    <th style="width: 140px; text-align: center;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($origens as $o): ?>
                    <tr class="linha-origem" data-id="<?php echo (int)$o['ID']; ?>" data-nome="<?php echo htmlspecialchars(mb_strtolower($o['Origem'])); ?>">
                        <td style="text-align: center; color: #64748b; font-weight: 600; font-size: 13px;">
                            #<?php echo (int)$o['ID']; ?>
                        </td>
                        <td>
                            <strong class="nome-origem-txt" style="color: #f8fafc; font-size: 15px;"><?php echo htmlspecialchars($o['Origem']); ?></strong>
                        </td>
                        <td style="text-align: center;">
                            <span class="badge-count badge-blue">
                                <span class="material-symbols-outlined" style="font-size: 14px;">male</span>
                                <?php echo number_format((int)$o['nomeM'], 0, ',', '.'); ?>
                            </span>
                        </td>
                        <td style="text-align: center;">
                            <span class="badge-count badge-pink">
                                <span class="material-symbols-outlined" style="font-size: 14px;">female</span>
                                <?php echo number_format((int)$o['nomeF'], 0, ',', '.'); ?>
                            </span>
                        </td>
                        <td style="text-align: center;">
                            <span class="badge-count badge-emerald">
                                <?php echo number_format((int)$o['sobrenomeM'], 0, ',', '.'); ?>
                            </span>
                        </td>
                        <td style="text-align: center;">
                            <span class="badge-count badge-purple">
                                <?php echo number_format((int)$o['sobrenomeF'], 0, ',', '.'); ?>
                            </span>
                        </td>
                        <td style="text-align: center;">
                            <?php if ((int)$o['total_paises'] > 0): ?>
                                <span class="badge-count badge-amber" title="Presente na demografia de <?php echo (int)$o['total_paises']; ?> países">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">flag</span>
                                    <?php echo (int)$o['total_paises']; ?> país(es)
                                </span>
                            <?php else: ?>
                                <span style="color: #64748b; font-size: 12px;">0 países</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: center; white-space: nowrap;">
                            <div style="display: inline-flex; gap: 6px; align-items: center;">
                                <a href="/admin/editar_origem.php?id=<?php echo (int)$o['ID']; ?>" class="btn-action-icon btn-origem-primary" title="Gerenciar nomes e acervo">
                                    <span class="material-symbols-outlined" style="font-size: 17px;">edit_note</span>
                                </a>
                                <button type="button" class="btn-action-icon btn-origem-secondary btn-renomear-origem" data-id="<?php echo (int)$o['ID']; ?>" data-nome="<?php echo htmlspecialchars($o['Origem']); ?>" title="Renomear Origem">
                                    <span class="material-symbols-outlined" style="font-size: 17px;">edit</span>
                                </button>
                                <button type="button" class="btn-action-icon btn-origem-danger btn-excluir-origem" data-id="<?php echo (int)$o['ID']; ?>" data-nome="<?php echo htmlspecialchars($o['Origem']); ?>" data-paises="<?php echo (int)$o['total_paises']; ?>" title="Excluir Origem">
                                    <span class="material-symbols-outlined" style="font-size: 17px;">delete</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>

<!-- MODAL: CRIAR NOVA ORIGEM -->
<div class="origem-modal-backdrop" id="modalCriarOrigem">
    <div class="origem-modal">
        <div class="modal-header">
            <h3 class="modal-title">➕ Cadastrar Nova Origem</h3>
            <button type="button" class="modal-close-btn" onclick="fecharModal('#modalCriarOrigem')">&times;</button>
        </div>
        <form id="formCriarOrigem">
            <div class="form-group">
                <label class="form-label" for="novoNomeOrigem">Nome da Origem / Cultura / País</label>
                <input type="text" id="novoNomeOrigem" class="form-input" placeholder="Ex: Argentina, Suécia, Galícia..." required autofocus />
                <span style="font-size: 12px; color: #94a3b8; margin-top: 4px;">
                    Após cadastrar, você poderá adicionar nomes e sobrenomes individualmente ou em lote.
                </span>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-origem btn-origem-secondary" onclick="fecharModal('#modalCriarOrigem')">Cancelar</button>
                <button type="submit" class="btn-origem btn-origem-primary" id="btnSalvarNovaOrigem">
                    <span class="material-symbols-outlined">check</span> Cadastrar
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
            <input type="hidden" id="renomearIdOrigem" />
            <div class="form-group">
                <label class="form-label" for="renomearNomeOrigem">Nome da Origem</label>
                <input type="text" id="renomearNomeOrigem" class="form-input" required autofocus />
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

$(document).ready(function(){

    // Filtro instantâneo na tabela
    $('#filtroOrigens').on('input', function(){
        const termo = $(this).val().toLowerCase().trim();
        let visiveis = 0;

        $('.linha-origem').each(function(){
            const id = $(this).attr('data-id');
            const nome = $(this).attr('data-nome');
            if (id.includes(termo) || nome.includes(termo)) {
                $(this).show();
                visiveis++;
            } else {
                $(this).hide();
            }
        });

        $('#contadorVisiveis').text(visiveis);
    });

    // Abrir Modal Criar
    $('#btnAbrirModalCriar').on('click', function(){
        $('#novoNomeOrigem').val('');
        $('#modalCriarOrigem').addClass('show');
        setTimeout(() => $('#novoNomeOrigem').focus(), 100);
    });

    // Submeter Criação de Origem
    $('#formCriarOrigem').on('submit', function(e){
        e.preventDefault();
        const nome = $('#novoNomeOrigem').val().trim();
        if (!nome) return;

        const $btn = $('#btnSalvarNovaOrigem');
        $btn.prop('disabled', true).text('Cadastrando...');

        $.ajax({
            url: '/admin/api_origens.php',
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'criar_origem',
                origem: nome
            },
            success: function(res) {
                fecharModal('#modalCriarOrigem');
                if (res.success) {
                    // Redireciona diretamente para a tela de gerenciamento da nova origem
                    window.location.href = '/admin/editar_origem.php?id=' + res.id;
                } else {
                    exibirAlerta(res.error || 'Erro ao criar origem.', 'danger');
                }
            },
            error: function(xhr) {
                const res = xhr.responseJSON || {};
                exibirAlerta(res.error || 'Erro de comunicação ao criar origem.', 'danger');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<span class="material-symbols-outlined">check</span> Cadastrar');
            }
        });
    });

    // Abrir Modal Renomear
    $(document).on('click', '.btn-renomear-origem', function(){
        const id = $(this).attr('data-id');
        const nome = $(this).attr('data-nome');
        $('#renomearIdOrigem').val(id);
        $('#renomearNomeOrigem').val(nome);
        $('#modalRenomearOrigem').addClass('show');
        setTimeout(() => $('#renomearNomeOrigem').focus(), 100);
    });

    // Submeter Renomeação
    $('#formRenomearOrigem').on('submit', function(e){
        e.preventDefault();
        const id = $('#renomearIdOrigem').val();
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
                id: id,
                origem: nome
            },
            success: function(res) {
                fecharModal('#modalRenomearOrigem');
                if (res.success) {
                    const $linha = $('.linha-origem[data-id="' + id + '"]');
                    $linha.find('.nome-origem-txt').text(nome);
                    $linha.attr('data-nome', nome.toLowerCase());
                    $linha.find('.btn-renomear-origem').attr('data-nome', nome);
                    $linha.find('.btn-excluir-origem').attr('data-nome', nome);
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

    // Excluir Origem
    $(document).on('click', '.btn-excluir-origem', function(){
        const id = $(this).attr('data-id');
        const nome = $(this).attr('data-nome');
        const totalPaises = parseInt($(this).attr('data-paises') || '0', 10);

        if (totalPaises > 0) {
            alert('Não é possível excluir "' + nome + '" porque esta origem está sendo utilizada por ' + totalPaises + ' país(es) no sistema. Remova a demografia desses países antes de excluir.');
            return;
        }

        if (!confirm('Tem certeza que deseja excluir a origem "' + nome + '" (# ' + id + ')?\n\nTODOS os nomes e sobrenomes cadastrados para esta cultura também serão apagados definitivamente.')) {
            return;
        }

        $.ajax({
            url: '/admin/api_origens.php',
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'excluir_origem',
                id: id
            },
            success: function(res) {
                if (res.success) {
                    $('.linha-origem[data-id="' + id + '"]').fadeOut(300, function(){
                        $(this).remove();
                        const vis = $('.linha-origem:visible').length;
                        $('#contadorVisiveis').text(vis);
                    });
                    exibirAlerta(res.message || 'Origem excluída com sucesso.', 'success');
                } else {
                    exibirAlerta(res.error || 'Erro ao excluir origem.', 'danger');
                }
            },
            error: function(xhr) {
                const res = xhr.responseJSON || {};
                exibirAlerta(res.error || 'Erro ao excluir origem.', 'danger');
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
