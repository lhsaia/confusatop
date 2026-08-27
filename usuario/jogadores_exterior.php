<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Jogadores no Exterior - ".($_SESSION['nomereal'] ?? '');
$css_filename = "home_redesign";
$aux_css = "home_redesign";
$extra_css = "jogadores_exterior_redesign";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){

    include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");

    $database = new Database();
    $db = $database->getConnection();
    $time = new Time($db);
?>

<main class="propostas-container">
    <div class="propostas-card">
        <div class="propostas-header">
            <h2 class="propostas-title">
                <span>🌍 Jogadores no Exterior</span>
            </h2>
            <div class="header-actions-container">
                <a href="/usuario/index.php" class="btn-voltar">
                    <span class="material-symbols-outlined" style="font-size: 18px;">arrow_back</span> Voltar
                </a>
            </div>
        </div>

        <div class="controls-bar">
            <div class="search-container">
                <span class="material-symbols-outlined search-icon">search</span>
                <input type="text" id="search-box" placeholder="Buscar por jogador, clube, país ou mentalidade..." autocomplete="off">
            </div>
            <div class="pagination-wrapper" id="paginacaoContainer"></div>
        </div>

        <div id="errorbox"></div>

        <div class="tbl_user_data">
            <table id="tabelaPrincipal" class="table">
                <thead>
                    <tr>
                        <th id="Nome" class="sortable" style="width: 46%; text-align: left; padding-left: 16px;">Jogador / Clube <span class="material-symbols-outlined sort-icon hidden">arrow_upward</span></th>
                        <th id="Idade" class="sortable" style="width: 12%; text-align: center;">Idade <span class="material-symbols-outlined sort-icon hidden">arrow_upward</span></th>
                        <th id="siglaPais" class="sortable" style="width: 10%; text-align: center;">Nac. <span class="material-symbols-outlined sort-icon hidden">arrow_upward</span></th>
                        <th id="Nivel" class="sortable" style="width: 11%; text-align: center;">Nível <span class="material-symbols-outlined sort-icon hidden">arrow_upward</span></th>
                        <th id="valor" class="sortable" style="width: 11%; text-align: center;">Valor <span class="material-symbols-outlined sort-icon hidden">arrow_upward</span></th>
                        <th style="width: 10%; text-align: center; white-space: nowrap;">Opções</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td colspan="6" style="text-align:center; padding: 2rem; color: #64748b;">Carregando jogadores no exterior...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Modal de Repatriação -->
<div id="modalProposta" class="modal">
    <div class="modal-card">
        <div class="modal-header">
            <h3 class="modal-title">✈️ Repatriar Jogador</h3>
            <button type="button" class="modal-close" onclick="$('#modalProposta').hide();">&times;</button>
        </div>
        <form id="formProposta" method="POST" action="/jogadores/fazer_proposta.php">
            <div class="modal-body">
                <label for="nomeJogadorTransf">Jogador</label>
                <input id="nomeJogadorTransf" type="text" name="nomeJogador" disabled>

                <label for="clubeDestinoTransf">Clube de destino</label>
                <select id="clubeDestinoTransf" name="clubeDestinoTransf" required>
                    <?php
                    $stmt = $time->read($_SESSION['user_id']);
                    $closed_countries = [];
                    $query_closed = "SELECT pais FROM janelas WHERE CASE WHEN padraoAbertura IS NULL THEN 1 ELSE CAST(SUBSTR(padraoAbertura, MONTH(NOW()), 1) AS UNSIGNED) END = 0";
                    $stmt_closed = $db->prepare($query_closed);
                    $stmt_closed->execute();
                    while ($row_closed = $stmt_closed->fetch(PDO::FETCH_ASSOC)) {
                        $closed_countries[] = (int)$row_closed['pais'];
                    }

                    echo "<option value=''>Selecione o clube...</option>";
                    while ($row_category = $stmt->fetch(PDO::FETCH_ASSOC)){
                        extract($row_category);
                        if (in_array((int)$paisTime, $closed_countries)) {
                            continue;
                        }
                        echo "<option value='{$id}'>{$nome}</option>";
                    }
                    ?>
                </select>

                <input type="hidden" value="" name="idJogadorTransf" id="idJogadorTransf" required>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-modal-cancel" onclick="$('#modalProposta').hide();">Cancelar</button>
                <button type="submit" name="newsubmit" class="btn-modal-submit">Repatriar</button>
            </div>
        </form>
    </div>
</div>

<script>
var localData = [];
var filteredData = [];
var currentPage = 1;
var recordsPerPage = 18;
var activeSort = 'Nome';
var asc = true;

function loadExpatData(searchTerm = '') {
    $.ajax({
        url: '/usuario/search_expat.php',
        type: 'POST',
        data: { searchText: searchTerm },
        dataType: 'json',
        success: function(data) {
            localData = data || [];
            filteredData = [...localData];
            sortResults(activeSort, false);
            renderTable(filteredData, 1);
        },
        error: function() {
            $('#tabelaPrincipal tbody').html('<tr><td colspan="6" style="text-align:center; padding: 2rem; color: #ef4444;">Erro ao carregar dados. Tente novamente.</td></tr>');
        }
    });
}

function renderTable(data, page) {
    currentPage = parseInt(page) || 1;
    var tbody = $('#tabelaPrincipal tbody');
    tbody.empty();

    if (!data || data.length === 0) {
        tbody.html('<tr><td colspan="6" style="text-align:center; padding: 2rem; color: #64748b;">Nenhum jogador no exterior encontrado.</td></tr>');
        $('#paginacaoContainer').empty();
        return;
    }

    var totalPages = Math.ceil(data.length / recordsPerPage);
    if (currentPage > totalPages) currentPage = totalPages;

    var start = (currentPage - 1) * recordsPerPage;
    var end = start + recordsPerPage;
    var pageData = data.slice(start, end);

    pageData.forEach(function(item) {
        var valorNum = parseFloat(item.valor || 0);
        var valorFormatado = "";
        if (valorNum >= 1000000) {
            valorFormatado = "F$ " + (valorNum / 1000000).toLocaleString('pt-BR', { minimumFractionDigits: 1, maximumFractionDigits: 2 }) + " M";
        } else if (valorNum > 0) {
            valorFormatado = "F$ " + (valorNum / 1000).toLocaleString('pt-BR') + " k";
        } else {
            valorFormatado = "F$ -";
        }
        
        var disp = parseInt(item.disponibilidade);
        var statusBadge = "";
        if (disp === -2) {
            statusBadge = "<span class='status-badge status-expat'>EXPATRIADO</span>";
        } else if (disp === 1) {
            statusBadge = "<span class='status-badge status-disp'>DISP.</span>";
        }

        var nascStr = "";
        if (item.Nascimento) {
            var parts = item.Nascimento.split("-");
            if (parts.length === 3) {
                nascStr = parts[2] + "/" + parts[1] + "/" + parts[0];
            } else {
                nascStr = item.Nascimento;
            }
        }

        var paisHtml = "-";
        if (item.idPais && item.idPais != 0) {
            var flagSrc = item.bandeiraPais ? "/images/bandeiras/" + item.bandeiraPais : "/images/bandeiras/flag.png";
            paisHtml = "<a href='/ligas/paisstatus.php?country=" + item.idPais + "' title='" + (item.siglaPais || "") + "'><img src='" + flagSrc + "' class='bandeira' alt='" + (item.siglaPais || "") + "'> <strong>" + (item.siglaPais || "") + "</strong></a>";
        }

        var clubeHtml = "Sem Clube";
        if (item.clubeVinculado) {
            var escudoSrc = item.escudoClubeVinculado ? "/images/escudos/" + item.escudoClubeVinculado : "/images/escudos/shield.png";
            clubeHtml = "<a href='/ligas/teamstatus.php?team=" + item.idClubeVinculado + "'><img class='minithumb' src='" + escudoSrc + "' alt='Clube'>" + item.clubeVinculado + "</a>";
        }

        var posicoesFormatadas = item.posicoesFormatadas || item.StringPosicoes || '';
        var posBadge = posicoesFormatadas ? "<span class='posicao-badge'>(" + posicoesFormatadas + ")</span>" : "";

        var metaTags = "<span class='player-meta-tags'>Mentalidade: <strong style='color:#1e293b;'>" + (item.Mentalidade || "Neutro") + "</strong> | Falta: <strong style='color:#0284c7;'>" + (item.CobradorFalta || "Normal") + "</strong></span>";

        var acoesHtml = "<div style='display: flex; gap: 4px; justify-content: center;'>";
        if (item.podeRepatriar) {
            acoesHtml += "<button type='button' class='btn-action-icon btn-repatriar' title='Repatriar jogador' data-id='" + item.ID + "' data-nome='" + item.Nome + "'><span class='material-symbols-outlined' style='font-size: 18px;'>flight_land</span></button>";
        }
        acoesHtml += "<button type='button' class='btn-action-icon btn-incorporar' title='Incorporar modificador de nível' data-id='" + item.ID + "' data-nivel='" + item.Nivel + "' data-mod='" + (item.modificadorNivel || 0) + "'><span class='material-symbols-outlined' style='font-size: 18px;'>person_add</span></button>";
        acoesHtml += "</div>";

        var modNivel = item.modificadorNivel && parseInt(item.modificadorNivel) !== 0 ? " <span style='font-size:0.75rem; color:" + (parseInt(item.modificadorNivel) > 0 ? "#16a34a" : "#dc2626") + "; font-weight:600;'>(" + (parseInt(item.modificadorNivel) > 0 ? "+" + item.modificadorNivel : item.modificadorNivel) + ")</span>" : "";

        var row = "<tr id='row-" + item.ID + "'>" +
            "<td style='text-align: left; padding-left: 16px;'>" +
                "<div>" +
                    "<a href='/ligas/playerstatus.php?player=" + item.ID + "' class='player-name-link'>" + item.Nome + "</a>" +
                    posBadge + statusBadge +
                "</div>" +
                "<span class='player-subinfo'>" + clubeHtml + "</span>" +
                metaTags +
            "</td>" +
            "<td><strong>" + (item.Idade || "-") + "</strong> <span style='font-size:0.75rem; color:#64748b; display:block;'>" + nascStr + "</span></td>" +
            "<td>" + paisHtml + "</td>" +
            "<td><strong style='font-size:0.95rem;'>" + item.Nivel + "</strong>" + modNivel + "</td>" +
            "<td><strong>" + valorFormatado + "</strong></td>" +
            "<td style='text-align: center; white-space: nowrap;'>" + acoesHtml + "</td>" +
            "</tr>";

        tbody.append(row);
    });

    renderPagination(currentPage, totalPages);
}

function renderPagination(current_page, total_pages) {
    if (total_pages <= 1) {
        $('#paginacaoContainer').empty();
        return;
    }

    var pgn = '<ul class="pagination">';
    var prev_page = parseInt(current_page) - 1;
    var next_page = parseInt(current_page) + 1;

    if (current_page > 1) {
        pgn += '<li><button class="pagination_link" id="inicio" title="Primeira Página">&laquo;</button></li>';
        pgn += '<li><button class="pagination_link" id="' + prev_page + '" title="Página Anterior">&lsaquo;</button></li>';
    }

    var range = 2;
    var initial_num = parseInt(current_page) - range;
    var condition_limit_num = parseInt(current_page) + range + 1;

    for (var x = initial_num; x < condition_limit_num; x++) {
        if ((x > 0) && (x <= total_pages)) {
            if (x == current_page) {
                pgn += '<li><button class="pagination_link" id="' + x + '" disabled>' + x + '<span class="sr-only">(current)</span></button></li>';
            } else {
                pgn += '<li><button class="pagination_link" id="' + x + '">' + x + '</button></li>';
            }
        }
    }

    if (current_page < total_pages) {
        pgn += '<li><button class="pagination_link" id="' + next_page + '" title="Próxima Página">&rsaquo;</button></li>';
        pgn += '<li><button class="pagination_link" id="final" title="Última Página">&raquo;</button></li>';
    }

    pgn += '</ul>';
    $('#paginacaoContainer').html(pgn);
}

function sortResults(prop, toggle) {
    if (toggle) {
        if (activeSort === prop) {
            asc = !asc;
        } else {
            asc = true;
            activeSort = prop;
        }
    }

    filteredData.sort(function(a, b) {
        var valA = a[prop] !== undefined && a[prop] !== null ? a[prop] : '';
        var valB = b[prop] !== undefined && b[prop] !== null ? b[prop] : '';

        var numA = parseFloat(valA);
        var numB = parseFloat(valB);

        if (!isNaN(numA) && !isNaN(numB) && typeof valA !== 'string') {
            return asc ? numA - numB : numB - numA;
        }

        valA = valA.toString().toLowerCase();
        valB = valB.toString().toLowerCase();

        if (asc) {
            return valA.localeCompare(valB);
        } else {
            return valB.localeCompare(valA);
        }
    });

    $('#tabelaPrincipal th .sort-icon').addClass('hidden');
    var activeTh = $('#tabelaPrincipal th#' + prop);
    var icon = activeTh.find('.sort-icon');
    icon.removeClass('hidden');
    icon.text(asc ? 'arrow_upward' : 'arrow_downward');
}

$(document).ready(function() {
    loadExpatData();

    // Debounce search
    var searchTimeout;
    $('#search-box').on('keyup', function() {
        clearTimeout(searchTimeout);
        var term = $(this).val().toLowerCase().trim();
        searchTimeout = setTimeout(function() {
            if (term === '') {
                filteredData = [...localData];
            } else {
                filteredData = localData.filter(function(item) {
                    var nome = (item.Nome || '').toLowerCase();
                    var clube = (item.clubeVinculado || '').toLowerCase();
                    var pais = (item.siglaPais || '').toLowerCase();
                    var ment = (item.Mentalidade || '').toLowerCase();
                    var pos = (item.posicoesFormatadas || item.StringPosicoes || '').toLowerCase();
                    return nome.includes(term) || clube.includes(term) || pais.includes(term) || ment.includes(term) || pos.includes(term);
                });
            }
            renderTable(filteredData, 1);
        }, 250);
    });

    // Sort Click
    $('#tabelaPrincipal th.sortable').on('click', function() {
        var prop = $(this).attr('id');
        sortResults(prop, true);
        renderTable(filteredData, 1);
    });

    // Pagination Click
    $(document).on('click', '.pagination_link', function() {
        var id = $(this).attr('id');
        var totalPages = Math.ceil(filteredData.length / recordsPerPage);
        if (id === 'inicio') {
            renderTable(filteredData, 1);
        } else if (id === 'final') {
            renderTable(filteredData, totalPages);
        } else {
            renderTable(filteredData, parseInt(id));
        }
        $('html, body').animate({ scrollTop: $('#tabelaPrincipal').offset().top - 80 }, 200);
    });

    // Repatriar Click (Abrir Modal)
    $(document).on('click', '.btn-repatriar', function() {
        var id = $(this).data('id');
        var nome = $(this).data('nome');
        $('#nomeJogadorTransf').val(nome);
        $('#idJogadorTransf').val(id);
        $('#modalProposta').fadeIn(200);
    });

    // Incorporar Click
    $(document).on('click', '.btn-incorporar', function() {
        var id = $(this).data('id');
        var nivel = parseInt($(this).data('nivel')) || 0;
        var mod = parseInt($(this).data('mod')) || 0;
        var novoNivel = nivel + mod;

        if (confirm("Deseja incorporar o modificador (" + (mod >= 0 ? "+" + mod : mod) + ") ao nível do jogador? O novo nível será " + novoNivel + ".")) {
            var formData = {
                'idJogador': id,
                'novoNivel': novoNivel,
                'alteracao': 6
            };

            $.ajax({
                type: 'POST',
                url: '/jogadores/editar_jogador.php',
                data: formData,
                dataType: 'json',
                encode: true
            }).done(function(data) {
                if (!data.success) {
                    $('#errorbox').html('<div class="alert alert-danger"><span class="closebtn">&times;</span>Não foi possível incorporar modificador: ' + data.error + '</div>');
                } else {
                    $('#errorbox').html('<div class="alert alert-success"><span class="closebtn">&times;</span>Modificador incorporado com sucesso!</div>');
                    loadExpatData($('#search-box').val());
                }
            }).fail(function() {
                $('#errorbox').html('<div class="alert alert-danger"><span class="closebtn">&times;</span>Erro de comunicação com o servidor.</div>');
            });
        }
    });

    // Form Repatriação Submit
    $('#formProposta').submit(function(event) {
        event.preventDefault();
        var formData = {
            'idJogador': $('input[name=idJogadorTransf]').val(),
            'idTime': $('select[name=clubeDestinoTransf]').val(),
            'alteracao': 5
        };

        $.ajax({
            type: 'POST',
            url: '/jogadores/editar_jogador.php',
            data: formData,
            dataType: 'json',
            encode: true
        }).done(function(data) {
            $('#modalProposta').hide();
            if (!data.success) {
                $('#errorbox').html('<div class="alert alert-danger"><span class="closebtn">&times;</span>Não foi possível realizar a repatriação: ' + data.error + '</div>');
            } else {
                $('#errorbox').html('<div class="alert alert-success"><span class="closebtn">&times;</span>O jogador foi repatriado com sucesso!</div>');
                loadExpatData($('#search-box').val());
            }
        }).fail(function() {
            $('#modalProposta').hide();
            $('#errorbox').html('<div class="alert alert-danger"><span class="closebtn">&times;</span>Erro ao processar repatriação.</div>');
        });
    });

    $(document).on('click', '.closebtn', function(){
        $(this).parent().fadeOut(300, function(){ $(this).remove(); });
    });
});
</script>

<?php
} else {
    echo "<main class='propostas-container'><div class='propostas-card'><p>Usuário sem permissão, por favor faça o login.</p></div></main>";
}

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>
