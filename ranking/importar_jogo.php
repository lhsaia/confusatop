<?php

header('Content-Type: text/html; charset=utf-8');
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogos.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/competicao.php");

$database = new Database();
$db = $database->getConnection();
$jogo = new Jogo($db);
$pais = new Pais($db);
$time = new Time($db);
$competicao = new Competicao($db);

// Parametros de layout e CSS
$page_title = "Importar Jogos de Seleções";
$css_filename = "indexRanking";
$aux_css = "home_redesign";
$extra_css = "importacao_moderna";
$css_login = 'login';
$css_versao = date('h:i:s');
$_SESSION['jogadorTime'] = 4;

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");
include_once 'ranking_header.php';
echo '<link rel="stylesheet" href="/css/importacao_moderna.css?v=' . $css_versao . '">';

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true){
?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
/* ---- Estilização da tela de importação moderna ---- */
.import-config-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.form-group-modern {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.form-label-modern {
    display: flex;
    align-items: center;
    gap: 6px;
    font-weight: 600;
    font-size: 0.9rem;
    color: #334155;
}

.form-label-modern .material-symbols-outlined {
    font-size: 1.2rem;
    color: #0284c7;
}

.form-control-modern {
    width: 100%;
    padding: 10px 14px;
    border-radius: 10px;
    border: 1px solid rgba(0, 0, 0, 0.1);
    background: #ffffff;
    font-family: 'Montserrat', sans-serif;
    font-size: 0.95rem;
    color: #0f172a;
    outline: none;
    transition: all 0.2s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
}

.form-control-modern:focus {
    border-color: #0284c7;
    box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
}

/* ---- Tabela de revisão / conferência ---- */
.import-review-container {
    margin-top: 1rem;
    width: 100%;
    font-family: 'Montserrat', sans-serif;
}

.import-review-title {
    font-size: 1.15rem;
    font-weight: 700;
    margin-bottom: 14px;
    color: #0f172a;
    border-bottom: 2px solid #0284c7;
    padding-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-family: 'Outfit', sans-serif;
}

.import-review-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 0.88rem;
}

.import-review-table th {
    background-color: #f8fafc;
    color: #475569;
    font-family: 'Outfit', sans-serif;
    font-weight: 600;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    padding: 12px 10px;
    text-align: left;
    border-bottom: 1px solid rgba(0, 0, 0, 0.08);
    white-space: nowrap;
}

.import-review-table td {
    padding: 12px 10px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.04);
    vertical-align: middle;
    color: #1e293b;
    transition: background 0.15s ease;
}

.import-review-row:hover td {
    background-color: #f8fafc;
}

.import-review-row.row-error td {
    background-color: #fff5f5;
}

/* Badges */
.ir-badge {
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.76rem;
    font-weight: 700;
    display: inline-block;
    white-space: nowrap;
    text-transform: uppercase;
    letter-spacing: 0.02em;
}

.ir-badge-ok     { background: #e6f9f0; color: #059669; border: 1px solid #b2dfc8; }
.ir-badge-warn   { background: #fff7ed; color: #d97706; border: 1px solid #fed7aa; }
.ir-badge-danger { background: #fee2e2; color: #dc2626; border: 1px solid #fca5a5; }
.ir-badge-imported { background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; }

/* Células */
.ir-filename { font-weight: 600; color: #0f172a; font-size: 0.85rem; }
.ir-meta     { color: #64748b; font-size: 0.78rem; margin-top: 2px; display: inline-block; }
.ir-placar   { font-size: 1.05rem; font-weight: 700; color: #0284c7; white-space: nowrap; }
.ir-pen      { font-size: 0.76rem; color: #64748b; font-weight: 500; }
.ir-original { color: #94a3b8; font-size: 0.76rem; display: block; margin-top: 4px; }

/* Select2 no contexto claro */
.select2-container--default .select2-selection--single {
    background-color: #ffffff !important;
    border: 1px solid rgba(0, 0, 0, 0.12) !important;
    border-radius: 8px !important;
    height: 36px !important;
    display: flex !important;
    align-items: center !important;
    transition: all 0.2s ease;
}

.select2-container--default.select2-container--focus .select2-selection--single,
.select2-container--default.select2-container--open .select2-selection--single {
    border-color: #0284c7 !important;
    box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15) !important;
}

.select2-container--default .select2-selection--single .select2-selection__rendered {
    color: #1e293b !important;
    padding-left: 10px !important;
    font-size: 0.85rem !important;
    font-weight: 500;
    line-height: 34px !important;
}

.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 34px !important;
    right: 6px;
}

.select2-dropdown {
    background-color: #ffffff !important;
    border: 1px solid rgba(0, 0, 0, 0.1) !important;
    border-radius: 8px !important;
    color: #1e293b !important;
    font-size: 0.85rem;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1) !important;
    z-index: 99999 !important;
}

.select2-container--default .select2-results__option--highlighted[aria-selected] {
    background-color: #0284c7 !important;
    color: #ffffff !important;
}

.select2-container--default .select2-results__option[aria-selected=true] {
    background-color: #f0f9ff !important;
    color: #0284c7 !important;
    font-weight: 600;
}

.select2-container--default .select2-search--dropdown .select2-search__field {
    background-color: #f8fafc !important;
    border: 1px solid rgba(0, 0, 0, 0.1) !important;
    border-radius: 6px !important;
    color: #1e293b !important;
    font-size: 0.85rem !important;
    padding: 6px 10px !important;
}

/* Botões */
.btn-confirm-import {
    background-color: #0284c7;
    color: #ffffff;
    border: none;
    border-radius: 10px;
    padding: 11px 28px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.92rem;
    font-family: 'Montserrat', sans-serif;
    letter-spacing: 0.02em;
    box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25);
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-confirm-import:hover {
    background-color: #0369a1;
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(2, 132, 199, 0.35);
}

.btn-confirm-import:disabled {
    background-color: #94a3b8;
    box-shadow: none;
    cursor: not-allowed;
    transform: none;
}

.btn-new-import {
    background: transparent;
    border: 1px solid rgba(0, 0, 0, 0.15);
    border-radius: 10px;
    padding: 10px 20px;
    cursor: pointer;
    color: #475569;
    font-size: 0.88rem;
    font-weight: 600;
    font-family: 'Montserrat', sans-serif;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    text-decoration: none !important;
}

.btn-new-import:hover {
    border-color: #0284c7;
    color: #0284c7;
    background: rgba(2, 132, 199, 0.05);
}

.import-actions {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    margin-top: 22px;
    padding-top: 16px;
    border-top: 1px solid rgba(0, 0, 0, 0.06);
}
</style>

<div class="ranking-container">
    <div class="ranking-card">
        <div class="ranking-card-header">
            <div>
                <h2 class="ranking-card-title">
                    <span class="material-symbols-outlined" style="color: #0284c7; font-size: 1.8rem;">upload_file</span>
                    Importar Jogos de Seleções
                </h2>
                <h3 class="ranking-card-date">Envie múltiplas súmulas de partidas para o Ranking oficial</h3>
            </div>
            <div>
                <a href="/ranking" class="btn-new-import">
                    <span class="material-symbols-outlined" style="font-size: 1.15rem;">arrow_back</span>
                    <span>Voltar ao Ranking</span>
                </a>
            </div>
        </div>

        <!-- Fase de upload: escondida durante a revisão -->
        <div id="upload-phase-wrapper">
            <div class="import-config-grid">
                <div class="form-group-modern" id="wrap-campeonato">
                    <label for="selecaocampeonato" class="form-label-modern">
                        <span class="material-symbols-outlined">emoji_events</span>
                        <span>Campeonato / Competição:</span>
                    </label>
                    <select id="selecaocampeonato" class="form-control-modern">
                    <?php
                    $stmtComp = $competicao->read();
                    while ($row_comp = $stmtComp->fetch(PDO::FETCH_ASSOC)){
                        $sel = ($row_comp['id'] == 10) ? 'selected' : '';
                        echo "<option value='{$row_comp['id']}' {$sel}>{$row_comp['nome']}</option>";
                    }
                    ?>
                    </select>
                </div>

                <div class="form-group-modern" id="wrap-fase">
                    <label for="selecaofase" class="form-label-modern">
                        <span class="material-symbols-outlined">format_list_numbered</span>
                        <span>Fase da Partida:</span>
                    </label>
                    <select id="selecaofase" class="form-control-modern">
                        <option value="0">N/A (Geral / Amistoso)</option>
                        <option value="1">Fase pré</option>
                        <option value="2">Fase de grupos</option>
                        <option value="3">Oitavas-de-final</option>
                        <option value="4">Quartas-de-final</option>
                        <option value="5">Semi-final</option>
                        <option value="6">Disputa de terceiro lugar</option>
                        <option value="7">Repescagem</option>
                        <option value="8">Final</option>
                        <option value="9">16-avos-de-final</option>
                        <option value="10">32-avos-de-final</option>
                    </select>
                </div>
            </div>

            <?php include_once($_SERVER['DOCUMENT_ROOT']."/elements/import_box.php"); ?>
        </div><!-- /#upload-phase-wrapper -->

        <!-- Tabela de revisão (aparece após o upload) -->
        <div id="import-review-wrapper" class="import-review-container" style="display:none;">
            <div class="import-review-title">
                <span class="material-symbols-outlined" style="color: #0284c7;">checklist</span>
                <span>Revisar Jogos para Importação</span>
            </div>
            <div class="tbl_user_data">
                <table class="import-review-table table">
                    <thead>
                        <tr>
                            <th style="width: 90px; text-align: center;">Status</th>
                            <th>Arquivo / Data / Estádio</th>
                            <th>Seleção A</th>
                            <th style="text-align:center; width: 100px;">Placar</th>
                            <th>Seleção B</th>
                        </tr>
                    </thead>
                    <tbody id="import-review-tbody"></tbody>
                </table>
            </div>
            <div class="import-actions">
                <button id="btn-confirm-import" class="btn-confirm-import">
                    <span class="material-symbols-outlined">check_circle</span>
                    <span>Confirmar e Importar</span>
                </button>
                <button class="btn-new-import" onclick="cancelReview()">
                    <span class="material-symbols-outlined">refresh</span>
                    <span>Nova importação</span>
                </button>
            </div>
        </div>

    </div>
</div>

<script>
var importedGamesData = [];

$('#selecaofase').on('change', function() {
    $('input[name="fase_jogo_import"]').val(this.value);
});
$('#selecaocampeonato').on('change', function() {
    $('input[name="campeonato_jogo_import"]').val(this.value);
});

function cancelReview() {
    $('#import-review-wrapper').hide();
    $('#upload-phase-wrapper').show();
    importedGamesData = [];
    $('#import-review-tbody').empty();
    $('#btn-confirm-import').show().prop('disabled', false).html('<span class="material-symbols-outlined">check_circle</span><span>Confirmar e Importar</span>');
    // Resetar o form de upload (limpa arquivos selecionados e estado)
    if ($('#importForm').length) {
        $('#importForm')[0].reset();
        $('#importForm').removeClass('is-success is-error is-uploading');
    }
}

window.renderImportReview = function(games, countries) {
    // Filtrar entradas fantasmas (sem filename)
    games = games.filter(function(g) { return g.filename && g.filename.trim() !== ''; });
    importedGamesData = games;

    var tbody = $('#import-review-tbody');
    tbody.empty();

    if (games.length === 0) {
        tbody.append('<tr><td colspan="5" style="text-align:center;color:#64748b;padding:24px;">Nenhum jogo válido encontrado nos arquivos.</td></tr>');
        $('#upload-phase-wrapper').hide();
        $('#import-review-wrapper').show();
        $('#btn-confirm-import').hide();
        return;
    }

    // Montar options de países
    var opts = '<option value="">-- Selecione o País --</option>';
    countries.forEach(function(c) {
        opts += '<option value="' + c.id + '">' + c.nome + ' (' + (c.sigla || '') + ')</option>';
    });

    games.forEach(function(game, index) {
        // Linha de erro (arquivo inválido)
        if (game.error) {
            tbody.append(
                '<tr class="import-review-row row-error">' +
                '<td style="text-align: center;"><span class="ir-badge ir-badge-danger">Erro</span></td>' +
                '<td colspan="4"><span class="ir-filename">' + (game.filename || '') + '</span> &mdash; <span style="color:#dc2626;">' + game.error + '</span></td>' +
                '</tr>'
            );
            return;
        }

        // Badge de status
        var badge;
        if (game.is_duplicate) {
            badge = '<span class="ir-badge ir-badge-warn" title="Jogo já existe no banco">Duplicado</span>';
        } else if (!game.timeA_id || !game.timeB_id) {
            badge = '<span class="ir-badge ir-badge-danger" title="Seleção não encontrada — mapeie abaixo">Mapear</span>';
        } else {
            badge = '<span class="ir-badge ir-badge-ok">Pronto</span>';
        }

        var infoCol = '<span class="ir-filename">' + game.filename + '</span><br>'
                    + '<span class="ir-meta"><span class="material-symbols-outlined" style="font-size:0.85rem; vertical-align:middle;">calendar_today</span> ' + (game.data || '') + ' &bull; <span class="material-symbols-outlined" style="font-size:0.85rem; vertical-align:middle;">stadium</span> ' + (game.estadio || '-') + '</span>';

        var placarStr = '<span class="ir-placar">' + (game.placarTime1 || 0) + ' &times; ' + (game.placarTime2 || 0) + '</span>';
        if (game.placarProrrogacaoTime1 != null && game.placarProrrogacaoTime1 >= 0) {
            placarStr += '<br><span class="ir-pen">Prorr: ' + game.placarProrrogacaoTime1 + '&times;' + game.placarProrrogacaoTime2 + '</span>';
        }
        if (game.placarPenaltisTime1 != null && game.placarPenaltisTime1 >= 0) {
            placarStr += '<br><span class="ir-pen">Pen: ' + game.placarPenaltisTime1 + '&times;' + game.placarPenaltisTime2 + '</span>';
        }

        var idA = 'selA_' + index, idB = 'selB_' + index;
        var colA = '<select id="' + idA + '" style="width:200px;">' + opts + '</select>'
                 + '<span class="ir-original">Detectado: ' + (game.time1_raw || '-') + '</span>';
        var colB = '<select id="' + idB + '" style="width:200px;">' + opts + '</select>'
                 + '<span class="ir-original">Detectado: ' + (game.time2_raw || '-') + '</span>';

        tbody.append(
            '<tr class="import-review-row" data-index="' + index + '">' +
            '<td style="text-align: center;"><span class="status-cell" id="sc_' + index + '">' + badge + '</span></td>' +
            '<td>' + infoCol + '</td>' +
            '<td>' + colA + '</td>' +
            '<td style="text-align:center;">' + placarStr + '</td>' +
            '<td>' + colB + '</td>' +
            '</tr>'
        );

        var selA = $('#' + idA), selB = $('#' + idB);

        function initS2() {
            if ($.fn && $.fn.select2) {
                selA.select2({ width: '200px' });
                selB.select2({ width: '200px' });
                if (game.timeA_id) selA.val(game.timeA_id).trigger('change.select2');
                if (game.timeB_id) selB.val(game.timeB_id).trigger('change.select2');
            } else {
                setTimeout(initS2, 50);
            }
        }
        initS2();

        selA.on('change', function() {
            importedGamesData[index].timeA_id = $(this).val();
            refreshBadge(index);
        });
        selB.on('change', function() {
            importedGamesData[index].timeB_id = $(this).val();
            refreshBadge(index);
        });
    });

    // Trocar fases da interface
    $('#upload-phase-wrapper').hide();
    $('#import-review-wrapper').show();
    $('#btn-confirm-import').show().prop('disabled', false).html('<span class="material-symbols-outlined">check_circle</span><span>Confirmar e Importar</span>');

    $('html, body').animate({ scrollTop: $('#import-review-wrapper').offset().top - 40 }, 400);
};

function refreshBadge(index) {
    var game = importedGamesData[index];
    var cell = $('#sc_' + index);
    if (!game.timeA_id || !game.timeB_id) {
        cell.html('<span class="ir-badge ir-badge-danger" title="Selecione as seleções">Mapear</span>');
    } else {
        cell.html('<span class="ir-badge ir-badge-ok">Pronto</span>');
    }
}

$('#btn-confirm-import').on('click', function() {
    var btn = $(this);

    var unmapped = importedGamesData.filter(function(g) {
        return !g.error && (!g.timeA_id || !g.timeB_id);
    });
    if (unmapped.length > 0) {
        alert('Há ' + unmapped.length + ' jogo(s) com seleção não mapeada. Por favor, selecione as seleções correspondentes antes de confirmar.');
        return;
    }

    btn.prop('disabled', true).html('<span class="material-symbols-outlined" style="animation: spin 1s infinite linear;">sync</span><span>Processando...</span>');

    $.ajax({
        type: 'POST',
        url: '/ranking/confirmar_importacao.php',
        data: JSON.stringify({ games: importedGamesData }),
        contentType: 'application/json',
        dataType: 'json'
    }).done(function(data) {
        if (data.success) {
            var tbody = $('#import-review-tbody');
            tbody.empty();
            data.results.forEach(function(res) {
                var sc   = res.success ? 'ir-badge-ok' : 'ir-badge-danger';
                var st   = res.success ? 'Importado' : 'Falhou';
                var desc = res.success ? ((res.action || 'Importado') + ' com sucesso.') : (res.error || 'Erro desconhecido');
                tbody.append(
                    '<tr class="import-review-row">' +
                    '<td style="text-align: center;"><span class="ir-badge ' + sc + '">' + st + '</span></td>' +
                    '<td colspan="4"><span class="ir-filename">' + res.filename + '</span> &mdash; ' + desc + '</td>' +
                    '</tr>'
                );
            });
            btn.hide();
            // Resetar o form para nova importação limpa
            if ($('#importForm').length) {
                $('#importForm')[0].reset();
                $('#importForm').removeClass('is-success is-error is-uploading');
            }
            var imp = data.imported || 0;
            var ski = data.skipped  || 0;
            alert('Importação concluída! ' + imp + ' importado(s), ' + ski + ' ignorado(s).');
        } else {
            alert('Erro ao importar: ' + (data.error || 'Erro desconhecido'));
            btn.prop('disabled', false).html('<span class="material-symbols-outlined">check_circle</span><span>Confirmar e Importar</span>');
        }
    }).fail(function() {
        alert('Erro ao conectar ao servidor. Tente novamente.');
        btn.prop('disabled', false).html('<span class="material-symbols-outlined">check_circle</span><span>Confirmar e Importar</span>');
    });
});
</script>

<?php
} else {
?>
<div class="ranking-container">
    <div class="ranking-card" style="text-align:center; padding:3.5rem 1.5rem; font-family:'Montserrat',sans-serif;">
        <span class="material-symbols-outlined" style="font-size: 3.5rem; color: #ef4444; margin-bottom: 1rem;">lock</span>
        <h2 style="font-family: 'Outfit', sans-serif; color: #1e293b; margin-bottom: 0.5rem; font-size: 1.5rem;">Acesso Restrito</h2>
        <p style="color: #64748b; font-size: 0.95rem; max-width: 500px; margin: 0 auto 1.5rem auto;">Você precisa estar logado para importar jogos no Ranking oficial.</p>
        <a href="/ranking" class="btn-new-import">
            <span class="material-symbols-outlined" style="font-size: 1.15rem;">arrow_back</span>
            <span>Voltar ao Ranking</span>
        </a>
    </div>
</div>
<?php
}

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>
