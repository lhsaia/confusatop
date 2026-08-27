<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Meus climas - ".($_SESSION['nomereal'] ?? '');
$css_filename = "home_redesign";
$aux_css = "meusclimas_redesign";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){
    include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/clima.php");

    $database = new Database();
    $db = $database->getConnection();

    $usuario = new Usuario($db);
    $pais = new Pais($db);
    $clima = new Clima($db);

    // query caixa de seleção países desse dono
    $stmtPais = $pais->read($_SESSION['user_id']);
    $listaPaises = array();
    while ($row_pais = $stmtPais->fetch(PDO::FETCH_ASSOC)){
        extract($row_pais);
        $addArray = array($id, $nome);
        $listaPaises[] = $addArray;
    }
?>

<script>
var localData = [];
var asc = true;
var activeSort = '';
var activeDirection = true;

var listaPaises = <?php echo json_encode($listaPaises); ?>;
var logged = '<?php echo (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true) ? "true" : "false"; ?>';
var admin = '<?php echo (isset($_SESSION['admin_status']) && $_SESSION['admin_status'] == 1) ? "true" : "false"; ?>';
var user_id = '<?php echo $_SESSION['user_id'] ?? 0; ?>';

var tempOptions = ['Muito Frio', 'Frio', 'Normal', 'Quente', 'Muito Quente'];
var estOptions = ['Neve Forte', 'Neve', 'Neve Ocasional', 'Neblina', 'Chuvoso', 'Ventos Fortes', 'Equilibrado', 'Seco', 'Árido'];

$(document).ready(function($){

    function delay(fn, ms){
        let timer = 0;
        return function(...args){
            clearTimeout(timer);
            timer = setTimeout(fn.bind(this, ...args), ms || 0);
        };
    }

    load_data();

    $('#caixa_pesquisa').keyup(delay(function(e){
        load_data();
    }, 400));

    function load_data(){
        var searchText = $('#caixa_pesquisa').val();
        $('#loading').show();

        $.ajax({
            url: "search_climate.php",
            method: "POST",
            cache: false,
            data: { searchText: searchText },
            success: function(data){
                $('#loading').hide();
                localData = JSON.parse(data);
                activeSort = '';
                asc = true;
                updateTable(localData, 1, '', 2);
            },
            error: function(){
                $('#loading').hide();
            }
        });
    }

    function buildOptions(list, selectedValue){
        var html = '';
        list.forEach(function(item){
            html += "<option value='" + item + "' " + (selectedValue === item ? 'selected' : '') + ">" + item + "</option>";
        });
        return html;
    }

    function updateTable(ajax_data, current_page, highlighted, direction){
        var results_per_page = 18;
        var total_results = ajax_data.length;
        var total_pages = Math.ceil(total_results / results_per_page);

        var treated_page;
        if(current_page == 'final'){
            treated_page = total_pages;
        } else if(current_page == 'inicio'){
            treated_page = 1;
        } else {
            treated_page = parseInt(current_page) || 1;
        }

        var from_result_num = (results_per_page * treated_page) - results_per_page;
        var pgn = pagination(treated_page, total_pages);

        var tbl = '';
        tbl += pgn;
        tbl += "<div class='tbl_user_data'>";
        tbl += "<table id='tabelaPrincipal' class='table'>";
        tbl += "<thead>";
        tbl += "<tr>";
        tbl += "<th id='Nome' class='headings' width='15%'><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbsp;Clima</th>";
        tbl += "<th width='8%'>Temp. Verão</th>";
        tbl += "<th width='9%'>Est. Verão</th>";
        tbl += "<th width='8%'>Temp. Outono</th>";
        tbl += "<th width='9%'>Est. Outono</th>";
        tbl += "<th width='8%'>Temp. Inverno</th>";
        tbl += "<th width='9%'>Est. Inverno</th>";
        tbl += "<th width='8%'>Temp. Prim.</th>";
        tbl += "<th width='9%'>Est. Prim.</th>";
        tbl += "<th width='6%'>Hemisf.</th>";
        tbl += "<th id='siglaPais' class='headings' width='6%'><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbsp;País</th>";
        tbl += "<th width='5%'>Opções</th>";
        tbl += "</tr>";
        tbl += "</thead>";
        tbl += "<tbody>";

        if(total_results === 0){
            tbl += "<tr><td colspan='12' style='text-align:center; padding: 2rem;'>Nenhum clima encontrado.</td></tr>";
        } else {
            $.each(ajax_data, function(index, val){
                if(index >= (from_result_num) && index < (from_result_num + results_per_page)){
                    tbl += "<tr id='" + val['ID'] + "'>";
                    tbl += "<td><span class='nomeClima nomeEditavel' id='nom" + val['ID'] + "'>" + val['Nome'] + "</span></td>";

                    // Verão
                    tbl += "<td><span class='nomeTempVer'>" + val['TempVerao'] + "</span>";
                    tbl += "<select class='comboTempVer editavel' id='seltempver" + val['ID'] + "' hidden>" + buildOptions(tempOptions, val['TempVerao']) + "</select></td>";

                    tbl += "<td><span class='nomeEstVer'>" + val['EstiloVerao'] + "</span>";
                    tbl += "<select class='comboEstVer editavel' id='selestver" + val['ID'] + "' hidden>" + buildOptions(estOptions, val['EstiloVerao']) + "</select></td>";

                    // Outono
                    tbl += "<td><span class='nomeTempOut'>" + val['TempOutono'] + "</span>";
                    tbl += "<select class='comboTempOut editavel' id='seltempout" + val['ID'] + "' hidden>" + buildOptions(tempOptions, val['TempOutono']) + "</select></td>";

                    tbl += "<td><span class='nomeEstOut'>" + val['EstiloOutono'] + "</span>";
                    tbl += "<select class='comboEstOut editavel' id='selestout" + val['ID'] + "' hidden>" + buildOptions(estOptions, val['EstiloOutono']) + "</select></td>";

                    // Inverno
                    tbl += "<td><span class='nomeTempInv'>" + val['TempInverno'] + "</span>";
                    tbl += "<select class='comboTempInv editavel' id='seltempinv" + val['ID'] + "' hidden>" + buildOptions(tempOptions, val['TempInverno']) + "</select></td>";

                    tbl += "<td><span class='nomeEstInv'>" + val['EstiloInverno'] + "</span>";
                    tbl += "<select class='comboEstInv editavel' id='selestinv" + val['ID'] + "' hidden>" + buildOptions(estOptions, val['EstiloInverno']) + "</select></td>";

                    // Primavera
                    tbl += "<td><span class='nomeTempPri'>" + val['TempPrimavera'] + "</span>";
                    tbl += "<select class='comboTempPri editavel' id='seltemppri" + val['ID'] + "' hidden>" + buildOptions(tempOptions, val['TempPrimavera']) + "</select></td>";

                    tbl += "<td><span class='nomeEstPri'>" + val['EstiloPrimavera'] + "</span>";
                    tbl += "<select class='comboEstPri editavel' id='selestpri" + val['ID'] + "' hidden>" + buildOptions(estOptions, val['EstiloPrimavera']) + "</select></td>";

                    // Hemisfério
                    var hemisferioTexto = (val['Hemisferio'] === 1 || val['Hemisferio'] === '1' || val['Hemisferio'] === 'Sul') ? 'Sul' : 'Norte';
                    tbl += "<td><span class='nomeHem'>" + hemisferioTexto + "</span>";
                    tbl += "<select class='comboHemisferio editavel' id='selhem" + val['ID'] + "' hidden>";
                    tbl += "<option value='Norte' " + (hemisferioTexto === 'Norte' ? 'selected' : '') + ">Norte</option>";
                    tbl += "<option value='Sul' " + (hemisferioTexto === 'Sul' ? 'selected' : '') + ">Sul</option>";
                    tbl += "</select></td>";

                    // País
                    tbl += "<td><img src='/images/bandeiras/" + (val['bandeiraPais'] || 'flag.png') + "' class='bandeira nomePais' id='ban" + val['ID'] + "'> <span class='nomePais' id='pai" + val['ID'] + "'>" + (val['siglaPais'] || '') + "</span>";
                    tbl += "<select class='comboPais editavel' id='selpai" + val['ID'] + "' hidden>";
                    listaPaises.forEach(function(p){
                        tbl += "<option value='" + p[0] + "' " + (val['idPais'] == p[0] ? 'selected' : '') + ">" + p[1] + "</option>";
                    });
                    tbl += "</select></td>";

                    // Ações
                    tbl += "<td class='actions-col'>";
                    if(logged === "true"){
                        tbl += "<a id='edi" + val['ID'] + "' title='Editar' class='clickable editar'><span class='material-symbols-outlined inlineButton'>edit</span></a>";
                        tbl += "<a hidden id='sal" + val['ID'] + "' title='Salvar' class='clickable salvar'><span class='material-symbols-outlined inlineButton positive'>check</span></a>";
                        tbl += "<a hidden id='can" + val['ID'] + "' title='Cancelar' class='clickable cancelar'><span class='material-symbols-outlined inlineButton negative'>close</span></a>";
                    }
                    tbl += "</td>";

                    tbl += "</tr>";
                }
            });
        }

        tbl += "</tbody>";
        tbl += "</table>";
        tbl += "</div>";

        $('#table-container').html(tbl);

        var showAsc = (direction === 2) ? activeDirection : (direction === 1);

        if(highlighted){
            $('#' + highlighted).addClass('highlighted');
            if(showAsc){
                $('#' + highlighted).find('.descending').addClass('hidden');
                $('#' + highlighted).find('.ascending').removeClass('hidden');
            } else {
                $('#' + highlighted).find('.ascending').addClass('hidden');
                $('#' + highlighted).find('.descending').removeClass('hidden');
            }
            activeSort = highlighted;
            activeDirection = showAsc;
        }

        addFilters();
        bindEvents();
    }

    function bindEvents(){
        $('.editar').off('click').on('click', function(){
            var tbl_row = $(this).closest('tr');

            tbl_row.find('span').each(function(){
                $(this).attr('original_entry', $(this).html());
            });

            tbl_row.find('.nomeEditavel').attr('contenteditable', 'true').addClass('editavel');
            tbl_row.find('.salvar').show();
            tbl_row.find('.cancelar').show();
            tbl_row.find('.editar').hide();

            tbl_row.find('span:not(.nomeEditavel)').hide();
            tbl_row.find('.bandeira').hide();
            tbl_row.find('select').show();
        });

        $('.cancelar').off('click').on('click', function(){
            var tbl_row = $(this).closest('tr');

            tbl_row.find('.nomeEditavel').attr('contenteditable', 'false').removeClass('editavel');
            tbl_row.find('select').hide();
            tbl_row.find('span:not(.nomeEditavel)').show();
            tbl_row.find('.bandeira').show();

            tbl_row.find('.salvar').hide();
            tbl_row.find('.cancelar').hide();
            tbl_row.find('.editar').show();

            tbl_row.find('span').each(function(){
                $(this).html($(this).attr('original_entry'));
            });
        });

        $('.salvar').off('click').on('click', function(){
            var tbl_row = $(this).closest('tr');
            var id = tbl_row.attr("id");

            var nomeClima = tbl_row.find('#nom' + id).text().trim();
            var tempVerao = tbl_row.find('#seltempver' + id).val();
            var estiloVerao = tbl_row.find('#selestver' + id).val();
            var tempOutono = tbl_row.find('#seltempout' + id).val();
            var estiloOutono = tbl_row.find('#selestout' + id).val();
            var tempInverno = tbl_row.find('#seltempinv' + id).val();
            var estiloInverno = tbl_row.find('#selestinv' + id).val();
            var tempPrimavera = tbl_row.find('#seltemppri' + id).val();
            var estiloPrimavera = tbl_row.find('#selestpri' + id).val();
            var hemisferio = tbl_row.find('#selhem' + id).val();
            var pais = tbl_row.find('#selpai' + id).val();

            $.ajax({
                url: 'alterar_clima.php',
                type: "POST",
                dataType: 'json',
                data: {
                    id: id,
                    nomeClima: nomeClima,
                    tempVerao: tempVerao,
                    estiloVerao: estiloVerao,
                    tempOutono: tempOutono,
                    estiloOutono: estiloOutono,
                    tempInverno: tempInverno,
                    estiloInverno: estiloInverno,
                    tempPrimavera: tempPrimavera,
                    estiloPrimavera: estiloPrimavera,
                    hemisferio: hemisferio,
                    pais: pais
                },
                success: function(data){
                    if(data && data.error && data.error !== ''){
                        alert(data.error);
                    }
                    load_data();
                },
                error: function(xhr){
                    var msg = "Erro, o procedimento não foi realizado. Tente novamente.";
                    try {
                        var res = JSON.parse(xhr.responseText);
                        if(res && res.error) msg = res.error;
                    } catch(e){}
                    alert(msg);
                    load_data();
                }
            });
        });
    }

    function addFilters(){
        $('.headings').off('click').on('click', function(){
            var column = $(this).attr('id');
            if(!column) return;

            if(activeSort !== column){
                asc = true;
            }

            localData.sort(function(a, b){
                var valA = (a[column] || '').toString().toLowerCase();
                var valB = (b[column] || '').toString().toLowerCase();

                if(asc){
                    return (valA > valB) ? 1 : ((valA < valB) ? -1 : 0);
                } else {
                    return (valA < valB) ? 1 : ((valA > valB) ? -1 : 0);
                }
            });

            var currentAsc = asc;
            asc = !asc;
            updateTable(localData, 1, column, currentAsc ? 1 : 0);
        });

        $('.page_link').off('click').on('click', function(e){
            e.preventDefault();
            var target_page = $(this).attr('id');
            updateTable(localData, target_page, activeSort, 2);
        });
    }

    function pagination(current_page, total_pages){
        var pgn = '<ul class="pagination">';
        if(total_pages > 1){
            var prev_page = parseInt(current_page) - 1;
            var next_page = parseInt(current_page) + 1;

            if(current_page > 1){
                pgn += '<li><a class="page_link" id="inicio" title="Primeira Página">&laquo;</a></li>';
                pgn += '<li><a class="page_link" id="' + prev_page + '" title="Página Anterior">&lsaquo;</a></li>';
            }

            var range = 2;
            var initial_num = current_page - range;
            var condition_limit_num = (current_page + range) + 1;

            for (var x = initial_num; x < condition_limit_num; x++) {
                if ((x > 0) && (x <= total_pages)) {
                    if (x == current_page) {
                        pgn += '<li class="active"><a>' + x + ' <span class="sr-only">(current)</span></a></li>';
                    } else {
                        pgn += '<li><a class="page_link" id="' + x + '">' + x + '</a></li>';
                    }
                }
            }

            if(current_page < total_pages){
                pgn += '<li><a class="page_link" id="' + next_page + '" title="Próxima Página">&rsaquo;</a></li>';
                pgn += '<li><a class="page_link" id="final" title="Última Página">&raquo;</a></li>';
            }
        }
        pgn += '</ul>';
        return pgn;
    }

});
</script>

<main class="propostas-container">
    <div class="propostas-card">
        <div class="header-actions-container">
            <h2 class="propostas-title">Quadro de climas - <?php echo htmlspecialchars($_SESSION['nomereal'] ?? ''); ?></h2>
            <div class="header-buttons-wrapper">
                <div id="search_wrapper">
                    <span class="material-symbols-outlined">search</span>
                    <input type="text" id="caixa_pesquisa" placeholder="Buscar clima..." autocomplete="off">
                </div>
                <button class="btn-action-primary" onclick="window.location='/ligas/criar_clima.php';">
                    <span class="material-symbols-outlined">add</span> Criar clima
                </button>
            </div>
        </div>

        <div id="loading" style="display:none;">
            <img src="/images/loading.gif" alt="Carregando...">
        </div>

        <div id="table-container"></div>
    </div>
</main>

<?php
} else {
    echo "<main class='propostas-container'><div class='propostas-card'><p>Usuário, por favor refaça o login.</p></div></main>";
}

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>
