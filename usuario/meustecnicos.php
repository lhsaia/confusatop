<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Meus técnicos - ".($_SESSION['nomereal'] ?? '');
$css_filename = "home_redesign";
$aux_css = "meustecnicos_redesign";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){
    include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/tecnico.php");

    $database = new Database();
    $db = $database->getConnection();

    $usuario = new Usuario($db);
    $pais = new Pais($db);
    $tecnico = new Tecnico($db);

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
var listaMentalidades = [
    [1, "Retranca"],
    [2, "Defensiva"],
    [3, "Balanceada"],
    [4, "Ofensiva"],
    [5, "Ataque Total"]
];
var listaEstilos = [
    [1, "Explorar contra-ataques"],
    [2, "Cadenciar o jogo"],
    [3, "Neutro"],
    [4, "Atacar pelas laterais"],
    [5, "Impôr ritmo ofensivo"]
];

var mentalidadeMap = {
    1: "Retranca",
    2: "Defensiva",
    3: "Balanceada",
    4: "Ofensiva",
    5: "Ataque Total"
};

var estiloMap = {
    1: "Explorar contra-ataques",
    2: "Cadenciar o jogo",
    3: "Neutro",
    4: "Atacar pelas laterais",
    5: "Impôr ritmo ofensivo"
};

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
            url: "search_coach.php",
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
        tbl += "<th id='Nome' class='headings' width='18%'><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbsp;Nome</th>";
        tbl += "<th id='Nascimento' class='headings' width='12%'><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbsp;Nascimento</th>";
        tbl += "<th id='Nivel' class='headings' width='6%'><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbsp;Nível</th>";
        tbl += "<th id='Mentalidade' class='headings' width='11%'><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbsp;Mentalidade</th>";
        tbl += "<th id='Estilo' class='headings' width='14%'><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbsp;Estilo</th>";
        tbl += "<th id='siglaPais' class='headings' width='8%'><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbsp;País</th>";
        tbl += "<th id='clubeVinculado' class='headings' width='10%'><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbsp;Clube</th>";
        tbl += "<th id='disponibilidade' class='headings' width='10%'><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbsp;Status</th>";
        tbl += "<th width='6%'>Opções</th>";
        tbl += "</tr>";
        tbl += "</thead>";
        tbl += "<tbody>";

        if(total_results === 0){
            tbl += "<tr><td colspan='10' style='text-align:center; padding: 2rem;'>Nenhum técnico encontrado.</td></tr>";
        } else {
            $.each(ajax_data, function(index, val){
                if(index >= (from_result_num) && index < (from_result_num + results_per_page)){
                    var genderCode = (val['Sexo'] == 0) ? "M" : "F";
                    var genderClass = (val['Sexo'] == 0) ? "genderMas" : "genderFem";

                    var options = { year: 'numeric', month: '2-digit', day: '2-digit' };
                    var dataNascimento = new Date(val['Nascimento'].replace(/-/g, '\/'));
                    var nascimentoDisplay = dataNascimento.toLocaleDateString("pt-BR", options);
                    var isFalecido = (parseInt(val['disponibilidade']) === -3 || Boolean(val['data_falecimento']));
                    var fotoSrc = val['foto'] ? '/images/tecnicos/' + val['foto'] : '/images/tecnicos/semfoto.png';
                    var fotoClass = isFalecido ? "playerThumb foto-falecido" : "playerThumb";

                    var mentTexto = mentalidadeMap[val['Mentalidade']] || "Balanceada";
                    var estiloTexto = estiloMap[val['Estilo']] || "Neutro";

                    var nomeDisponibilidade = "";
                    var dataFalecFmt = "";
                    if(val['data_falecimento']){
                        var dF = new Date(val['data_falecimento'].replace(/-/g, '\/'));
                        dataFalecFmt = dF.toLocaleDateString("pt-BR", options);
                    }

                    switch(parseInt(val['disponibilidade'])){
                        case -3:
                            var txtDataFalec = dataFalecFmt ? dataFalecFmt : "Falecido";
                            nomeDisponibilidade = "<span class='badge-falecido' title='Falecido em " + (dataFalecFmt || "data não informada") + "'><span class='cruz-morte'>†</span> <svg class='luto-icon' viewBox='0 0 24 24' width='13' height='13' fill='currentColor'><path d='M12 2C8.69 2 6 4.69 6 8c0 2.21 1.2 4.15 3 5.19V22l3-2 3 2v-8.81c1.8-1.04 3-2.98 3-5.19 0-3.31-2.69-6-6-6zm0 2c2.21 0 4 1.79 4 4 0 1.25-.58 2.37-1.49 3.1-.47.38-.51 1.07-.09 1.5.42.43 1.12.43 1.58.01C17.26 11.45 18 9.82 18 8c0-3.31-2.69-6-6-6s-6 2.69-6 6c0 1.82.74 3.45 1.99 4.61.47.42 1.16.42 1.59-.01.42-.43.38-1.12-.09-1.5C8.58 10.37 8 9.25 8 8c0-2.21 1.79-4 4-4z'/></svg> " + txtDataFalec + "</span>";
                            break;
                        case -2:
                            nomeDisponibilidade = "Expatriado";
                            break;
                        case -1:
                            nomeDisponibilidade = "Aposentado";
                            break;
                        case 0:
                            nomeDisponibilidade = "Ativo";
                            break;
                        case 1:
                        default:
                            nomeDisponibilidade = "Ativo (disponível)";
                            break;
                    }

                    var valDataFalecimento = val['data_falecimento'] ? val['data_falecimento'] : new Date().toISOString().split('T')[0];

                    tbl += "<tr id='" + val['ID'] + "' data-sexo='" + val['Sexo'] + "' data-dono-pais='" + val['idDonoPais'] + "'>";
                    tbl += "<td><div class='imageUpload'><img class='" + fotoClass + "' src='" + fotoSrc + "' /> <input type='file' hidden id='foto" + val['ID'] + "' class='hiddenInput custom-file-upload' name='foto' accept='.jpg,.png,.jpeg,.webp'/></div></td>";
                    tbl += "<td><a href='/ligas/coachstatus.php?coach=" + val['ID'] + "' class='player-name-link' style='color:#0f172a; text-decoration:none; font-weight:600;'><span class='nomeEditavel' id='nom" + val['ID'] + "'>" + val['Nome'] + "</span></a><span class='" + genderClass + " genderSign'>" + genderCode + "</span></td>";
                    tbl += "<td><span class='nomeNascimento' id='nas" + val['ID'] + "'>" + nascimentoDisplay + " (" + val['idade'] + ")</span><input id='selnas" + val['ID'] + "' class='nascimentoEditavel editavel' type='date' value='" + val['Nascimento'] + "' hidden/></td>";
                    tbl += "<td><span class='nivelEditavel' id='niv" + val['ID'] + "'>" + val['Nivel'] + "</span></td>";

                    // Mentalidade
                    tbl += "<td>";
                    tbl += "<span class='nomeMentalidade' id='txtmen" + val['ID'] + "'>" + mentTexto + "</span>";
                    tbl += "<select class='comboMentalidade editavel' id='selmen" + val['ID'] + "' hidden>";
                    listaMentalidades.forEach(function(m){
                        tbl += "<option " + (val['Mentalidade'] == m[0] ? "selected" : "") + " value='" + m[0] + "'>" + m[1] + "</option>";
                    });
                    tbl += "</select></td>";

                    // Estilo
                    tbl += "<td>";
                    tbl += "<span class='nomeEstilo' id='txtest" + val['ID'] + "'>" + estiloTexto + "</span>";
                    tbl += "<select class='comboEstilo editavel' id='selest" + val['ID'] + "' hidden>";
                    listaEstilos.forEach(function(e){
                        tbl += "<option " + (val['Estilo'] == e[0] ? "selected" : "") + " value='" + e[0] + "'>" + e[1] + "</option>";
                    });
                    tbl += "</select></td>";

                    // País
                    tbl += "<td><img src='/images/bandeiras/" + (val['bandeiraPais'] || 'flag.png') + "' class='bandeira nomePais' id='ban" + val['ID'] + "'> <span class='nomePais' id='pai" + val['ID'] + "'>" + (val['siglaPais'] || '') + "</span>";
                    tbl += "<select class='comboPais editavel' id='selpai" + val['ID'] + "' hidden>";
                    listaPaises.forEach(function(p){
                        tbl += "<option value='" + p[0] + "' " + (val['idPais'] == p[0] ? 'selected' : '') + ">" + p[1] + "</option>";
                    });
                    tbl += "</select></td>";

                    // Clube
                    if(val['clubeVinculado'] != null){
                        tbl += "<td><a href='/ligas/teamstatus.php?team=" + val['idClubeVinculado'] + "' id='dis" + val['ID'] + "'><img class='minithumb' src='/images/escudos/" + val['escudoClubeVinculado'] + "'>" + val['clubeVinculado'] + "</a><span class='donoClubeVinculado' hidden>" + val['donoClubeVinculado'] + "</span></td>";
                    } else {
                        tbl += "<td><span style='color:#94a3b8; font-style:italic;'>Sem clube</span></td>";
                    }

                    // Status / Disponibilidade
                    tbl += "<td>";
                    tbl += "<span class='nomeAtividade' id='dis" + val['ID'] + "'>" + nomeDisponibilidade + "</span>";
                    tbl += "<div class='container-edit-atividade' style='display:inline-flex; flex-direction:column; gap:4px;'>";
                    tbl += "<select class='comboAtividade editavel' id='seldis" + val['ID'] + "' hidden>";
                    tbl += "<option value='1' " + (val['disponibilidade'] == 1 ? 'selected' : '') + ">Ativo (disponível)</option>";
                    tbl += "<option value='0' " + (val['disponibilidade'] == 0 ? 'selected' : '') + ">Ativo</option>";
                    tbl += "<option value='-1' " + (val['disponibilidade'] == -1 ? 'selected' : '') + ">Aposentado</option>";
                    tbl += "<option value='-2' " + (val['disponibilidade'] == -2 ? 'selected' : '') + ">Expatriado</option>";
                    tbl += "<option value='-3' " + (val['disponibilidade'] == -3 ? 'selected' : '') + ">Falecido</option>";
                    tbl += "</select>";
                    tbl += "<input type='date' class='falecimentoEditavel editavel' id='selfalec" + val['ID'] + "' value='" + valDataFalecimento + "' title='Data de Falecimento' hidden style='font-size:0.75rem; padding:2px 4px; max-width:125px;' />";
                    tbl += "</div></td>";

                    // Ações
                    tbl += "<td class='actions-col'>";
                    if(logged === "true"){
                        tbl += "<a id='edi" + val['ID'] + "' title='Editar técnico' class='clickable editar'><span class='material-symbols-outlined inlineButton'>edit</span></a>";
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
            tbl_row.find('input').each(function(){
                $(this).attr('data-original-entry', $(this).val());
            });

            tbl_row.find(".salvar").show();
            tbl_row.find(".cancelar").show();
            tbl_row.find(".editar").hide();
            tbl_row.find('.hiddenInput').show();

            var donoTime = tbl_row.find(".donoClubeVinculado").html();
            var donoPais = tbl_row.attr("data-dono-pais");

            var isDono = (admin === "true" || user_id == donoPais || (typeof donoTime !== 'undefined' && user_id == donoTime) || !donoTime);

            if(isDono){
                tbl_row.find('.nomeEditavel').attr('contenteditable', 'true').addClass('editavel');
                tbl_row.find('.nomePais').hide();
                tbl_row.find('.comboPais').show();

                tbl_row.find('.nomeEstilo').hide();
                tbl_row.find('.comboEstilo').show();

                tbl_row.find('.nomeMentalidade').hide();
                tbl_row.find('.comboMentalidade').show();

                tbl_row.find('.nomeNascimento').hide();
                tbl_row.find('.nascimentoEditavel').show();

                tbl_row.find('.nomeAtividade').hide();
                tbl_row.find('.comboAtividade').show();
                if(tbl_row.find('.comboAtividade').val() == "-3" || tbl_row.find('.badge-falecido').length > 0){
                    tbl_row.find('.falecimentoEditavel').show();
                } else {
                    tbl_row.find('.falecimentoEditavel').hide();
                }
            }

            tbl_row.find('.nivelEditavel').attr('contenteditable', 'true').addClass('editavel');
        });

        $(document).on('change', '.comboAtividade', function(){
            var tbl_row = $(this).closest('tr');
            if($(this).val() == "-3"){
                tbl_row.find('.falecimentoEditavel').show();
            } else {
                tbl_row.find('.falecimentoEditavel').hide();
            }
        });

        $('.cancelar').off('click').on('click', function(){
            var tbl_row = $(this).closest('tr');
            var id = tbl_row.attr("id");

            tbl_row.find('.nomeEditavel').attr('contenteditable', 'false').removeClass('editavel');
            tbl_row.find('.nivelEditavel').attr('contenteditable', 'false').removeClass('editavel');
            tbl_row.find('.nomeNascimento').show();
            tbl_row.find('.nascimentoEditavel').hide();
            tbl_row.find('.comboPais').hide();
            tbl_row.find('.nomePais').show();

            tbl_row.find('.comboEstilo').hide();
            tbl_row.find('.nomeEstilo').show();

            tbl_row.find('.comboMentalidade').hide();
            tbl_row.find('.nomeMentalidade').show();

            tbl_row.find('.comboAtividade').hide();
            tbl_row.find('.falecimentoEditavel').hide();
            tbl_row.find('.nomeAtividade').show();

            tbl_row.find('.salvar').hide();
            tbl_row.find('.cancelar').hide();
            tbl_row.find('.editar').show();
            tbl_row.find('.hiddenInput').hide();

            tbl_row.find('span').each(function(){
                $(this).html($(this).attr('original_entry'));
            });
            tbl_row.find('input').each(function(){
                $(this).val($(this).attr('data-original-entry'));
            });
        });

        $('.salvar').off('click').on('click', function(){
            var tbl_row = $(this).closest('tr');
            var id = tbl_row.attr('id');

            var donoTime = tbl_row.find(".donoClubeVinculado").html();
            var donoPais = tbl_row.attr("data-dono-pais");
            var isDono = (admin === "true" || user_id == donoPais || (typeof donoTime !== 'undefined' && user_id == donoTime) || !donoTime);

            var formData = new FormData();

            if(isDono){
                var nome = tbl_row.find('.nomeEditavel').text().trim();
                var nascimento = tbl_row.find(".nascimentoEditavel").val();
                var pais = tbl_row.find('.comboPais').val();
                var estilo = tbl_row.find('.comboEstilo').val();
                var mentalidade = tbl_row.find('.comboMentalidade').val();
                var atividade = tbl_row.find('.comboAtividade').val();
                var dataFalecimento = tbl_row.find('.falecimentoEditavel').val();

                formData.append('pais', pais);
                formData.append('estilo', estilo);
                formData.append('mentalidade', mentalidade);
                formData.append('nascimento', nascimento);
                formData.append('nome', nome);
                formData.append('atividade', atividade);
                formData.append('dataFalecimento', dataFalecimento);
            }

            var inputFoto = (tbl_row.find('#foto' + id))[0];
            var foto = (inputFoto && inputFoto.files.length > 0) ? inputFoto.files[0] : null;

            if(foto != null){
                formData.append('foto', foto);
            }

            var nivel = tbl_row.find(".nivelEditavel").text().trim();
            var alteracao = 9;

            formData.append('idTecnico', id);
            formData.append('nivel', nivel);
            formData.append('alteracao', alteracao);

            $.ajax({
                url: '/ligas/editar_tecnico.php',
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
                var valA = a[column];
                var valB = b[column];

                if(column === 'Nivel' || column === 'Mentalidade' || column === 'Estilo'){
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
            <h2 class="propostas-title">Quadro de técnicos - <?php echo htmlspecialchars($_SESSION['nomereal'] ?? ''); ?></h2>
            <div class="header-buttons-wrapper">
                <div id="search_wrapper">
                    <span class="material-symbols-outlined">search</span>
                    <input type="text" id="caixa_pesquisa" placeholder="Buscar técnico..." autocomplete="off">
                </div>
                <button class="btn-action-primary" onclick="window.location='/ligas/criar_tecnico.php';">
                    <span class="material-symbols-outlined">add</span> Criar técnico
                </button>
                <button class="btn-action-primary" onclick="window.location='/import/importar_tecnico.php';">
                    <span class="material-symbols-outlined">upload_file</span> Importar técnico
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
