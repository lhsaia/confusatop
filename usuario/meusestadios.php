<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Meus estádios - ".($_SESSION['nomereal'] ?? '');
$css_filename = "home_redesign";
$aux_css = "meusestadios_redesign";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){
    include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/estadio.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/clima.php");

    $database = new Database();
    $db = $database->getConnection();

    $usuario = new Usuario($db);
    $pais = new Pais($db);
    $estadio = new Estadio($db);
    $clima = new Clima($db);

    // query caixa de seleção países desse dono
    $stmtPais = $pais->read($_SESSION['user_id']);
    $listaPaises = array();
    while ($row_pais = $stmtPais->fetch(PDO::FETCH_ASSOC)){
        extract($row_pais);
        $addArray = array($id, $nome);
        $listaPaises[] = $addArray;
    }

    // query caixa de seleção climas desse dono
    $stmtClima = $clima->read($_SESSION['user_id']);
    $listaClimas = array();
    while ($row_clima = $stmtClima->fetch(PDO::FETCH_ASSOC)){
        extract($row_clima);
        $addArray = array($ID, $Nome);
        $listaClimas[] = $addArray;
    }
?>

<script>
var localData = [];
var asc = true;
var activeSort = '';
var activeDirection = true;

var listaPaises = <?php echo json_encode($listaPaises); ?>;
var listaClimas = <?php echo json_encode($listaClimas); ?>;

var logged = '<?php echo (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true) ? "true" : "false"; ?>';
var admin = '<?php echo (isset($_SESSION['admin_status']) && $_SESSION['admin_status'] == 1) ? "true" : "false"; ?>';
var user_id = '<?php echo $_SESSION['user_id'] ?? 0; ?>';

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
            url: "search_stadium.php",
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
        tbl += "<th width='5%'>Foto</th>";
        tbl += "<th id='Nome' class='headings' width='25%'><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbsp;Estádio</th>";
        tbl += "<th id='Capacidade' class='headings' width='15%'><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbsp;Capacidade</th>";
        tbl += "<th id='nomeClima' class='headings' width='15%'><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbsp;Clima</th>";
        tbl += "<th id='Altitude' class='headings' width='10%'><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbsp;Altitude</th>";
        tbl += "<th id='Caldeirao' class='headings' width='10%'><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbsp;Caldeirão</th>";
        tbl += "<th id='siglaPais' class='headings' width='10%'><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbsp;País</th>";
        tbl += "<th width='10%'>Opções</th>";
        tbl += "</tr>";
        tbl += "</thead>";
        tbl += "<tbody>";

        if(total_results === 0){
            tbl += "<tr><td colspan='8' style='text-align:center; padding: 2rem;'>Nenhum estádio encontrado.</td></tr>";
        } else {
            $.each(ajax_data, function(index, val){
                if(index >= (from_result_num) && index < (from_result_num + results_per_page)){
                    var fotoSrc = val['foto'] ? '/images/estadios/' + val['foto'] : '/images/estadios/8-estadiodosol3707.webp';
                    
                    tbl += "<tr id='" + val['ID'] + "'>";
                    tbl += "<td><div class='imageUpload'><img class='stadiumThumb' src='" + fotoSrc + "' /> <input type='file' hidden id='foto" + val['ID'] + "' class='hiddenInput custom-file-upload' name='foto' accept='.jpg,.png,.jpeg,.webp'/></div></td>";
                    tbl += "<td><span class='nomeEstadio nomeEditavel' id='nom" + val['ID'] + "'>" + val['Nome'] + "</span></td>";
                    tbl += "<td><span class='capacidadeFixo' id='cap" + val['ID'] + "'>" + Number(val['Capacidade']).toLocaleString('pt-BR') + "</span><input id='capedit" + val['ID'] + "' type='number' min='0' step='100' class='capacidade inputHerdeiro' value='" + val['Capacidade'] + "' hidden></td>";

                    tbl += "<td><span class='nomeClima' id='cli" + val['ID'] + "'>" + (val['nomeClima'] || '') + "</span>";
                    tbl += "<select class='comboClima editavel' id='selcli" + val['ID'] + "' hidden>";
                    listaClimas.forEach(function(c){
                        tbl += "<option value='" + c[0] + "' " + (val['Clima'] == c[0] ? 'selected' : '') + ">" + c[1] + "</option>";
                    });
                    tbl += "</select></td>";

                    tbl += "<td><input type='checkbox' class='altitude' id='alt" + val['ID'] + "' " + (val['Altitude'] == 1 ? 'checked' : '') + " disabled></td>";
                    tbl += "<td><input type='checkbox' class='caldeirao' id='cal" + val['ID'] + "' " + (val['Caldeirao'] == 1 ? 'checked' : '') + " disabled></td>";

                    tbl += "<td><img src='/images/bandeiras/" + (val['bandeiraPais'] || 'flag.png') + "' class='bandeira nomePais' id='ban" + val['ID'] + "'> <span class='nomePais' id='pai" + val['ID'] + "'>" + (val['siglaPais'] || '') + "</span>";
                    tbl += "<select class='comboPais editavel' id='selpai" + val['ID'] + "' hidden>";
                    listaPaises.forEach(function(p){
                        tbl += "<option value='" + p[0] + "' " + (val['idPais'] == p[0] ? 'selected' : '') + ">" + p[1] + "</option>";
                    });
                    tbl += "</select></td>";

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
            var id = tbl_row.attr("id");

            tbl_row.find('span').each(function(){
                $(this).attr('original_entry', $(this).html());
            });

            tbl_row.find('.nomeEditavel').attr('contenteditable', 'true').addClass('editavel');
            tbl_row.find('.salvar').show();
            tbl_row.find('.cancelar').show();
            tbl_row.find('.editar').hide();
            tbl_row.find('.nomePais').hide();
            tbl_row.find('.nomeClima').hide();
            tbl_row.find('.capacidadeFixo').hide();
            tbl_row.find('.capacidade').show();
            tbl_row.find('.hiddenInput').show();

            document.getElementById("alt" + id).disabled = false;
            document.getElementById("cal" + id).disabled = false;
            document.getElementById("alt" + id).setAttribute("original_entry", document.getElementById("alt" + id).checked);
            document.getElementById("cal" + id).setAttribute("original_entry", document.getElementById("cal" + id).checked);

            tbl_row.find('.comboPais').show();
            tbl_row.find('.comboClima').show();
        });

        $('.cancelar').off('click').on('click', function(){
            var tbl_row = $(this).closest('tr');
            var id = tbl_row.attr("id");

            tbl_row.find('.nomeEditavel').attr('contenteditable', 'false').removeClass('editavel');
            tbl_row.find('.comboPais').hide();
            tbl_row.find('.nomePais').show();
            tbl_row.find('.nomeClima').show();
            tbl_row.find('.comboClima').hide();
            tbl_row.find('.salvar').hide();
            tbl_row.find('.cancelar').hide();
            tbl_row.find('.editar').show();
            tbl_row.find('.capacidadeFixo').show();
            tbl_row.find('.capacidade').hide();
            tbl_row.find('.hiddenInput').hide();

            document.getElementById("alt" + id).disabled = true;
            document.getElementById("cal" + id).disabled = true;
            document.getElementById("alt" + id).checked = (document.getElementById("alt" + id).getAttribute("original_entry") === 'true');
            document.getElementById("cal" + id).checked = (document.getElementById("cal" + id).getAttribute("original_entry") === 'true');

            tbl_row.find('span').each(function(){
                $(this).html($(this).attr('original_entry'));
            });
        });

        $('.salvar').off('click').on('click', function(){
            var tbl_row = $(this).closest('tr');
            var id = tbl_row.attr("id");

            var nomeEstadio = tbl_row.find('#nom' + id).text().trim();
            var capacidade = tbl_row.find('#capedit' + id).val();
            var clima = tbl_row.find('.comboClima').val();
            var pais = tbl_row.find('.comboPais').val();
            var altitude = document.getElementById("alt" + id).checked;
            var caldeirao = document.getElementById("cal" + id).checked;

            var inputFoto = (tbl_row.find('#foto' + id))[0];
            var foto = (inputFoto && inputFoto.files.length > 0) ? inputFoto.files[0] : null;

            var formData = new FormData();
            formData.append('id', id);
            formData.append('nomeEstadio', nomeEstadio);
            formData.append('capacidade', capacidade);
            formData.append('clima', clima);
            formData.append('altitude', altitude);
            formData.append('caldeirao', caldeirao);
            formData.append('pais', pais);

            if(foto != null){
                formData.append('foto', foto);
            }

            $.ajax({
                url: 'alterar_estadio.php',
                processData: false,
                contentType: false,
                cache: false,
                type: "POST",
                dataType: 'json',
                data: formData,
                success: function(data){
                    if(data.error && data.error !== ''){
                        alert(data.error);
                    }
                    load_data();
                },
                error: function(){
                    alert("Erro, o procedimento não foi realizado. Tente novamente.");
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
                var valA = a[column];
                var valB = b[column];

                if(column === 'Capacidade' || column === 'Altitude' || column === 'Caldeirao'){
                    valA = Number(valA) || 0;
                    valB = Number(valB) || 0;
                } else {
                    valA = (valA || '').toString().toLowerCase();
                    valB = (valB || '').toString().toLowerCase();
                }

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
            <h2 class="propostas-title">Quadro de estádios - <?php echo htmlspecialchars($_SESSION['nomereal'] ?? ''); ?></h2>
            <div class="header-buttons-wrapper">
                <div id="search_wrapper">
                    <span class="material-symbols-outlined">search</span>
                    <input type="text" id="caixa_pesquisa" placeholder="Buscar estádio..." autocomplete="off">
                </div>
                <button class="btn-action-primary" onclick="window.location='/ligas/criar_estadio.php';">
                    <span class="material-symbols-outlined">add</span> Criar estádio
                </button>
                <button class="btn-action-primary" onclick="window.location='/import/importar_estadio.php';">
                    <span class="material-symbols-outlined">upload_file</span> Importar estádio
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
