<?php

// ini_set( 'display_errors', true );
// error_reporting( E_ALL );
header('Content-Type: text/html; charset=utf-8');
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogos_clube.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/competicao.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/liga.php");

$database = new Database();
$db = $database->getConnection();
$jogo = new Jogo($db);
$pais = new Pais($db);
$time = new Time($db);
$liga = new Liga($db);
$competicao = new Competicao($db);

//declaracoes de parametros
$page_title = "Importar Jogo de Clubes";
$css_filename = "home_redesign";
$aux_css = "home_redesign";
$extra_css = "jogos_clubes_redesign";
$css_login = 'login';
$css_versao = date('h:i:s');
$_SESSION['jogadorTime'] = 7;

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");
echo '<link rel="stylesheet" href="/css/importacao_moderna.css?v=' . $css_versao . '">';

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){

// Fetch User's Leagues (Private)
$stmtLigas = $liga->read($_SESSION['user_id']);

// Fetch Cups (Public [0] or User's [user_id])
$queryCups = "SELECT id, nome FROM campeonatos_clube WHERE dono = 0 OR dono = :dono ORDER BY nome";
$stmtCups = $db->prepare($queryCups);
$stmtCups->bindParam(":dono", $_SESSION['user_id']);
$stmtCups->execute();
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
    border-radius: 12px;
    border: 1px solid rgba(0,0,0,0.08);
    box-shadow: 0 4px 16px rgba(0,0,0,0.04);
    font-family: 'Montserrat', sans-serif;
}
.import-review-title {
    font-size: 1.1rem;
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
    border-collapse: collapse;
    font-size: 0.87rem;
}
.import-review-table th {
    background-color: #f8fafc;
    color: #475569;
    font-weight: 700;
    padding: 10px 8px;
    text-align: left;
    border-bottom: 2px solid #e2e8f0;
    white-space: nowrap;
}
.import-review-table td {
    padding: 9px 8px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
    color: #1e293b;
}
.import-review-row:hover td {
    background-color: #f8fafc;
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

.ir-filename { font-weight: 600; color: #0f172a; font-size: 0.82rem; }
.ir-meta     { color: #64748b; font-size: 0.76rem; }
.ir-placar   { font-size: 0.95rem; font-weight: 700; color: #0f172a; white-space: nowrap; }
.ir-pen      { font-size: 0.76rem; color: #64748b; }
.ir-original { color: #94a3b8; font-size: 0.74rem; display: block; margin-top: 2px; }

/* Botões */
.btn-confirm-import {
    background-color: #0284c7;
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 10px 28px;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.2s;
    font-size: 0.92rem;
    font-family: 'Montserrat', sans-serif;
    letter-spacing: 0.02em;
}
.btn-confirm-import:hover { background-color: #0369a1; }
.btn-confirm-import:disabled { background-color: #aaa; cursor: not-allowed; }

.btn-new-import {
    background: none;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 9px 18px;
    cursor: pointer;
    color: #475569;
    font-size: 0.85rem;
    font-family: 'Montserrat', sans-serif;
    transition: all 0.2s;
}
.btn-new-import:hover { border-color: #0284c7; color: #0284c7; }
.import-actions { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin-top: 18px; }
</style>

<div class="clubes-container" style="max-width: 1200px;">
    <!-- Fase de upload: escondida durante a conferência -->
    <div id="upload-phase-wrapper">
        <div class="clubes-card" style="max-width: 900px; margin: 0 auto;">
            <div class="clubes-header-bar">
                <div class="clubes-title-group">
                    <span class="material-symbols-outlined clubes-title-icon">upload_file</span>
                    <div>
                        <h2 class="clubes-main-title">Importar Jogo de Clubes</h2>
                        <p class="clubes-subtitle">Selecione a competição e envie a súmula/arquivo da partida para conferência</p>
                    </div>
                </div>
                <div>
                    <a href="jogos/index.php" class="btn-clubes-secondary">
                        <span class="material-symbols-outlined" style="font-size:1.1rem;">arrow_back</span>
                        <span>Voltar aos Jogos</span>
                    </a>
                </div>
            </div>

            <div style="max-width: 680px; margin: 1.5rem auto;">
                <p class="selecaodeligas">
                    <span>Tipo de Competição:</span>
                    <select id="selecaotipo">
                        <option value="1" selected>Copa</option>
                        <option value="0">Liga</option>
                    </select>
                </p>

                <div id="container_liga" style="display:none;">
                    <p class="selecaodeligas">
                        <span>Liga:</span>
                        <select id="selecaoliga">
                            <?php
                            if($stmtLigas->rowCount() > 0){
                                while ($row_liga = $stmtLigas->fetch(PDO::FETCH_ASSOC)){
                                    echo "<option value='{$row_liga['id']}'>{$row_liga['nome']}</option>";
                                }
                            } else {
                                echo "<option value='0'>Nenhuma liga encontrada</option>";
                            }
                            ?>
                        </select>
                    </p>
                </div>

                <div id="container_copa">
                    <p class="selecaodeligas">
                        <span>Copa:</span>
                        <select id="selecaocopa">
                            <?php
                            while ($row_comp = $stmtCups->fetch(PDO::FETCH_ASSOC)){
                                echo "<option value='{$row_comp['id']}'>{$row_comp['nome']}</option>";
                            }
                            ?>
                        </select>
                    </p>
                </div>

                <p class="selecaodeligas">
                    <span>Fase:</span>
                    <select id="selecaofase">
                        <option value="0">N/A</option>
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
                </p>

                <?php
                include_once($_SERVER['DOCUMENT_ROOT']."/elements/import_box.php");
                ?>
            </div>
        </div>
    </div><!-- /#upload-phase-wrapper -->

    <!-- Tabela de revisão/conferência (aparece após o envio dos arquivos) -->
    <div id="import-review-wrapper" class="import-review-container" style="display:none;">
        <div class="import-review-title">
            <span class="material-symbols-outlined" style="color:#0284c7;">checklist</span>
            Revisar Jogos de Clubes para Importação
        </div>
        <div style="overflow-x:auto;">
            <table class="import-review-table">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Arquivo / Data / Estádio</th>
                        <th>Mandante (Clube A)</th>
                        <th style="text-align:center;">Placar</th>
                        <th>Visitante (Clube B)</th>
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
</div>

<script>
var importedGamesData = [];

$(document).ready(function() {
    updateCompeticaoValues();
});

$('#selecaotipo').on('change', function (e) {
    var tipo = $(this).val();
    $('#competicao_tipo').val(tipo);
    
    if(tipo == '0'){
        $('#container_liga').show();
        $('#container_copa').hide();
    } else {
        $('#container_liga').hide();
        $('#container_copa').show();
    }
    updateCompeticaoValues();
});

$('#selecaoliga, #selecaocopa').on('change', function (e) {
    updateCompeticaoValues();
});

$('#selecaofase').on('change', function (e) {
    var valueSelected = this.value;
    $('input[name="fase_jogo_import"]').val(valueSelected);
});

function updateCompeticaoValues(){
    var tipo = $('#selecaotipo').val();
    $('#competicao_tipo').val(tipo);
    $('input[name="fase_jogo_import"]').val($('#selecaofase').val());
    
    if(tipo == '0'){
        $('input[name="campeonato_jogo_import"]').val($('#selecaoliga').val());
    } else {
        $('input[name="campeonato_jogo_import"]').val($('#selecaocopa').val());
    }
}

$('.box__file').on('click', function() {
    updateCompeticaoValues();
});

function cancelReview() {
    $('#import-review-wrapper').hide();
    $('#upload-phase-wrapper').show();
    importedGamesData = [];
    $('#import-review-tbody').empty();
    $('#btn-confirm-import').show().prop('disabled', false).text('Confirmar e Importar');
    if ($('#importForm').length) {
        $('#importForm')[0].reset();
        $('#importForm').removeClass('is-success is-error is-uploading');
    }
}

window.renderImportReview = function(games, clubs) {
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

    var opts = '<option value="">-- Selecione o Clube --</option>';
    if (Array.isArray(clubs)) {
        clubs.forEach(function(c) {
            opts += '<option value="' + c.id + '" data-escudo="' + (c.escudo || 'sem_escudo.png') + '">' + c.nome + '</option>';
        });
    }

    games.forEach(function(game, index) {
        if (game.error) {
            tbody.append(
                '<tr class="import-review-row row-error">' +
                '<td><span class="ir-badge ir-badge-danger">Erro</span></td>' +
                '<td colspan="4"><span class="ir-filename">' + (game.filename || '') + '</span> &mdash; ' + game.error + '</td>' +
                '</tr>'
            );
            return;
        }

        var badge;
        if (game.is_duplicate) {
            badge = '<span class="ir-badge ir-badge-warn" title="Jogo já existe no banco">Duplicado</span>';
        } else if (!game.timeA_id || !game.timeB_id) {
            badge = '<span class="ir-badge ir-badge-danger" title="Clube não encontrado — mapeie abaixo">Mapear</span>';
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

        var idA = 'selClubeA_' + index, idB = 'selClubeB_' + index;
        var colA = '<select id="' + idA + '" style="width:200px;">' + opts + '</select>'
                 + '<span class="ir-original">' + (game.time1_raw || '') + '</span>';
        var colB = '<select id="' + idB + '" style="width:200px;">' + opts + '</select>'
                 + '<span class="ir-original">' + (game.time2_raw || '') + '</span>';

        tbody.append(
            '<tr class="import-review-row" data-index="' + index + '">' +
            '<td><span class="status-cell" id="sc_clube_' + index + '">' + badge + '</span></td>' +
            '<td>' + infoCol + '</td>' +
            '<td>' + colA + '</td>' +
            '<td style="text-align:center;">' + placarStr + '</td>' +
            '<td>' + colB + '</td>' +
            '</tr>'
        );

        var selA = $('#' + idA), selB = $('#' + idB);

        function initS2Clube() {
            if ($.fn && $.fn.select2) {
                selA.select2({ width: '200px' });
                selB.select2({ width: '200px' });
                if (game.timeA_id) selA.val(game.timeA_id).trigger('change.select2');
                if (game.timeB_id) selB.val(game.timeB_id).trigger('change.select2');
            } else {
                setTimeout(initS2Clube, 50);
            }
        }
        initS2Clube();

        selA.on('change', function() {
            importedGamesData[index].timeA_id = $(this).val();
            refreshClubBadge(index);
        });
        selB.on('change', function() {
            importedGamesData[index].timeB_id = $(this).val();
            refreshClubBadge(index);
        });
    });

    $('#upload-phase-wrapper').hide();
    $('#import-review-wrapper').show();
    $('#btn-confirm-import').show().prop('disabled', false).text('Confirmar e Importar');

    $('html, body').animate({ scrollTop: $('#import-review-wrapper').offset().top - 20 }, 400);
};

function refreshClubBadge(index) {
    var game = importedGamesData[index];
    var cell = $('#sc_clube_' + index);
    if (!game.timeA_id || !game.timeB_id) {
        cell.html('<span class="ir-badge ir-badge-danger" title="Selecione os clubes">Mapear</span>');
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
        alert('Há ' + unmapped.length + ' jogo(s) com clube não mapeado. Por favor, selecione os clubes correspondentes antes de confirmar.');
        return;
    }

    btn.prop('disabled', true).text('Processando importação...');

    $.ajax({
        type: 'POST',
        url: '/ligas/gerenciador/confirmar_importacao.php',
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
            if ($('#importForm').length) {
                $('#importForm')[0].reset();
                $('#importForm').removeClass('is-success is-error is-uploading');
            }
            var imp = data.imported || 0;
            var ski = data.skipped  || 0;
            alert('Importação concluída com sucesso! ' + imp + ' jogo(s) importado(s), ' + ski + ' ignorado(s).');
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
} else {
    echo "<div class='clubes-container'><div class='clubes-card' style='text-align:center; padding:3rem;'>Usuário sem permissão para inserir jogos, por favor faça o login.</div></div>";
}

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>

