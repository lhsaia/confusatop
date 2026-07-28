<?php

// ini_set( 'display_errors', true );
// error_reporting( E_ALL );
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

//declaracoes de parametros
$page_title = "Importar jogo";
$css_filename = "indexRanking";
$aux_css = "main";
$css_login = 'login';
$css_versao = date('h:i:s');
$_SESSION['jogadorTime'] = 4;

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<style>
/* ---- Tabela de revisão ---- */
.import-review-container {
    margin: 20px auto;
    max-width: 1150px;
    padding: 22px;
    background: #fff;
    border-radius: 8px;
    border: 1px solid #ddd;
    box-shadow: 0 2px 8px rgba(0,0,0,0.07);
    font-family: 'Montserrat', sans-serif;
}
.import-review-title {
    font-size: 1.1rem;
    font-weight: 700;
    margin-bottom: 14px;
    color: #1A1469;
    border-bottom: 2px solid #FF800E;
    padding-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.import-review-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.87rem;
}
.import-review-table th {
    background-color: #f4f4f4;
    color: #444;
    font-weight: 700;
    padding: 10px 8px;
    text-align: left;
    border-bottom: 2px solid #ddd;
    white-space: nowrap;
}
.import-review-table td {
    padding: 9px 8px;
    border-bottom: 1px solid #eee;
    vertical-align: middle;
    color: #333;
}
.import-review-row:hover td {
    background-color: #fffaf5;
}
.import-review-row.row-error td {
    background-color: #fff5f5;
}

/* Badges */
.ir-badge {
    padding: 3px 9px;
    border-radius: 4px;
    font-size: 0.76rem;
    font-weight: 700;
    display: inline-block;
    white-space: nowrap;
}
.ir-badge-ok     { background: #e6f9f0; color: #1a7a4a; border: 1px solid #b2dfc8; }
.ir-badge-warn   { background: #fff7e6; color: #b45309; border: 1px solid #fcd9a0; }
.ir-badge-danger { background: #feecec; color: #c0392b; border: 1px solid #f5b8b8; }
.ir-badge-imported { background: #e8f4fd; color: #1565c0; border: 1px solid #90caf9; }

/* Células */
.ir-filename { font-weight: 600; color: #1A1469; font-size: 0.82rem; }
.ir-meta     { color: #888; font-size: 0.76rem; }
.ir-placar   { font-size: 0.95rem; font-weight: 700; color: #1A1469; white-space: nowrap; }
.ir-pen      { font-size: 0.76rem; color: #888; }
.ir-original { color: #aaa; font-size: 0.74rem; display: block; margin-top: 2px; }

/* Select2 no contexto claro */
.select2-container--default .select2-selection--single {
    background-color: #fff !important;
    border: 1px solid #ccc !important;
    border-radius: 4px !important;
    height: 32px !important;
    display: flex !important;
    align-items: center !important;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    color: #333 !important;
    padding-left: 8px !important;
    font-size: 0.84rem !important;
    line-height: 30px !important;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 30px !important;
}
.select2-dropdown {
    background-color: #fff !important;
    border: 1px solid #ccc !important;
    color: #333 !important;
    font-size: 0.84rem;
    z-index: 99999 !important;
}
.select2-container--default .select2-results__option--highlighted[aria-selected] {
    background-color: #FF800E !important;
    color: #fff !important;
}
.select2-container--default .select2-results__option[aria-selected=true] {
    background-color: #fff3e6 !important;
    color: #333 !important;
}
.select2-container--default .select2-search--dropdown .select2-search__field {
    background-color: #fff !important;
    border: 1px solid #ccc !important;
    color: #333 !important;
    border-radius: 3px !important;
    font-size: 0.84rem !important;
}

/* Botões */
.btn-confirm-import {
    background-color: #1A1469;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 10px 28px;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.2s;
    font-size: 0.92rem;
    font-family: 'Montserrat', sans-serif;
    letter-spacing: 0.02em;
}
.btn-confirm-import:hover { background-color: #FF800E; }
.btn-confirm-import:disabled { background-color: #aaa; cursor: not-allowed; }

.btn-new-import {
    background: none;
    border: 1px solid #ccc;
    border-radius: 6px;
    padding: 9px 18px;
    cursor: pointer;
    color: #555;
    font-size: 0.85rem;
    font-family: 'Montserrat', sans-serif;
    transition: all 0.2s;
}
.btn-new-import:hover { border-color: #1A1469; color: #1A1469; }
.import-actions { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin-top: 18px; }
</style>

<!-- Fase de upload: escondida durante a revisão -->
<div id="upload-phase-wrapper">
<?php include_once($_SERVER['DOCUMENT_ROOT']."/elements/import_box.php"); ?>

<p class="selecaodeligas" id="wrap-campeonato">Campeonato:
<select id="selecaocampeonato" class="selecaodeligas comboPais editavel">
<?php
$stmtComp = $competicao->read();
while ($row_comp = $stmtComp->fetch(PDO::FETCH_ASSOC)){
    echo "<option value='{$row_comp['id']}'>{$row_comp['nome']}</option>";
}
?>
</select>
</p>

<p class="selecaodeligas" id="wrap-fase">Fase:
<select id="selecaofase" class="selecaodeligas comboPais editavel">
<option value="0">N/A</option>
<option value="1">Fase pré</option>
<option value="2">Fase de grupos</option>
<option value="3">Oitavas-de-final</option>
<option value="4">Quartas-de-final</option>
<option value="5">Semi-final</option>
<option value="6">Disputa de terceiro lugar</option>
<option value="7">Repescagem</option>
<option value="8">Final</option>
</select>
</p>
</div><!-- /#upload-phase-wrapper -->

<!-- Tabela de revisão (aparece após o upload) -->
<div id="import-review-wrapper" class="import-review-container" style="display:none;">
    <div class="import-review-title">
        ✔ Revisar Jogos para Importação
    </div>
    <div style="overflow-x:auto;">
        <table class="import-review-table">
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Arquivo / Data / Estádio</th>
                    <th>Seleção A</th>
                    <th style="text-align:center;">Placar</th>
                    <th>Seleção B</th>
                </tr>
            </thead>
            <tbody id="import-review-tbody"></tbody>
        </table>
    </div>
    <div class="import-actions">
        <button id="btn-confirm-import" class="btn-confirm-import">Confirmar e Importar</button>
        <button class="btn-new-import" onclick="cancelReview()">↩ Nova importação</button>
    </div>
</div>

<?php
} else {
    echo "Usuário sem permissão para inserir jogos, por favor faça o login.";
}
?>

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
    $('#btn-confirm-import').show().prop('disabled', false).text('Confirmar e Importar');
    // Resetar o form de upload (limpa arquivos selecionados e estado)
    $('#importForm')[0].reset();
    $('#importForm').removeClass('is-success is-error is-uploading');
}

window.renderImportReview = function(games, countries) {
    // Filtrar entradas fantasmas (sem filename)
    games = games.filter(function(g) { return g.filename && g.filename.trim() !== ''; });
    importedGamesData = games;

    var tbody = $('#import-review-tbody');
    tbody.empty();

    if (games.length === 0) {
        tbody.append('<tr><td colspan="5" style="text-align:center;color:#888;padding:20px;">Nenhum jogo válido encontrado nos arquivos.</td></tr>');
        $('#upload-phase-wrapper').hide();
        $('#import-review-wrapper').show();
        $('#btn-confirm-import').hide();
        return;
    }

    // Montar options de países
    var opts = '<option value="">-- País --</option>';
    countries.forEach(function(c) {
        opts += '<option value="' + c.id + '">' + c.nome + ' (' + (c.sigla || '') + ')</option>';
    });

    games.forEach(function(game, index) {
        // Linha de erro (arquivo inválido)
        if (game.error) {
            tbody.append(
                '<tr class="import-review-row row-error">' +
                '<td><span class="ir-badge ir-badge-danger">Erro</span></td>' +
                '<td colspan="4"><span class="ir-filename">' + (game.filename || '') + '</span> &mdash; ' + game.error + '</td>' +
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
                    + '<span class="ir-meta">' + (game.data || '') + ' | ' + (game.estadio || '-') + '</span>';

        var placarStr = '<span class="ir-placar">' + (game.placarTime1 || 0) + ' &times; ' + (game.placarTime2 || 0) + '</span>';
        if (game.placarProrrogacaoTime1 != null && game.placarProrrogacaoTime1 >= 0) {
            placarStr += '<br><span class="ir-pen">Prorr: ' + game.placarProrrogacaoTime1 + '&times;' + game.placarProrrogacaoTime2 + '</span>';
        }
        if (game.placarPenaltisTime1 != null && game.placarPenaltisTime1 >= 0) {
            placarStr += '<br><span class="ir-pen">Pen: ' + game.placarPenaltisTime1 + '&times;' + game.placarPenaltisTime2 + '</span>';
        }

        var idA = 'selA_' + index, idB = 'selB_' + index;
        var colA = '<select id="' + idA + '" style="width:185px;">' + opts + '</select>'
                 + '<span class="ir-original">' + (game.time1_raw || '') + '</span>';
        var colB = '<select id="' + idB + '" style="width:185px;">' + opts + '</select>'
                 + '<span class="ir-original">' + (game.time2_raw || '') + '</span>';

        tbody.append(
            '<tr class="import-review-row" data-index="' + index + '">' +
            '<td><span class="status-cell" id="sc_' + index + '">' + badge + '</span></td>' +
            '<td>' + infoCol + '</td>' +
            '<td>' + colA + '</td>' +
            '<td style="text-align:center;">' + placarStr + '</td>' +
            '<td>' + colB + '</td>' +
            '</tr>'
        );

        var selA = $('#' + idA), selB = $('#' + idB);

        function initS2() {
            if ($.fn && $.fn.select2) {
                selA.select2({ width: '185px' });
                selB.select2({ width: '185px' });
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
    $('#btn-confirm-import').show().prop('disabled', false).text('Confirmar e Importar');

    $('html, body').animate({ scrollTop: $('#import-review-wrapper').offset().top - 20 }, 400);
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

    btn.prop('disabled', true).text('Processando...');

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
                    '<td><span class="ir-badge ' + sc + '">' + st + '</span></td>' +
                    '<td colspan="4"><span class="ir-filename">' + res.filename + '</span> &mdash; ' + desc + '</td>' +
                    '</tr>'
                );
            });
            btn.hide();
            // Resetar o form para nova importação limpa
            $('#importForm')[0].reset();
            $('#importForm').removeClass('is-success is-error is-uploading');
            var imp = data.imported || 0;
            var ski = data.skipped  || 0;
            alert('Importação concluída! ' + imp + ' importado(s), ' + ski + ' ignorado(s).');
        } else {
            alert('Erro ao importar: ' + (data.error || 'Erro desconhecido'));
            btn.prop('disabled', false).text('Confirmar e Importar');
        }
    }).fail(function() {
        alert('Erro ao conectar ao servidor. Tente novamente.');
        btn.prop('disabled', false).text('Confirmar e Importar');
    });
});
</script>

<?php
include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>
