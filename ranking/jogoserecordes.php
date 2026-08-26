<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Jogos & Recordes";
$css_filename = "indexRanking";
$css_login = 'login';
$aux_css = 'home_redesign';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");
include_once 'ranking_header.php';

?>

<script>

var localData = [];
var asc = true;
var activeSort = '';
var activeDirection = true;

$(document).ready(function($){

load_data();

$('#caixa_pesquisa').keyup(function(){load_data()});

function load_data(){

var searchText = $('#caixa_pesquisa').val();
$('#loading').show();  // show loading indicator

$.ajax({
    url:"pesquisa.php",
    method:"POST",
    cache:false,
    data:{searchText:searchText},
    success:function(data){
        $('#loading').hide();  // hide loading indicator
        updateTable(JSON.parse(data),1,0,0);
        localData = JSON.parse(data);
    }
});
}



function updateTable(ajax_data, current_page, highlighted, direction){

    var results_per_page = 17;
    var total_results = ajax_data.length;
    var total_pages = Math.ceil(total_results/results_per_page);

    var treated_page;
    if(current_page == 'final'){
        treated_page = total_pages;
    } else if(current_page == 'inicio'){
        treated_page = 1;
    } else {
        treated_page = current_page;
    }

    var from_result_num = (results_per_page * treated_page) - results_per_page;

    var pgn = pagination(treated_page,total_pages);

    //criar tabela dinamicamente
    var tbl = '';
    tbl += pgn;
    tbl += "<table id='tabelajogos' class='table'>";
        tbl += "<thead id='headings'>";
            tbl += "<tr>";
                tbl += "<th asc='' id='nomeA' class='headings' style='width: 25%; text-align: left;'><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbsp;Time Mandante</th>";
                tbl +=  "<th asc='' id='timeAgols' class='headings' style='width: 6%;'><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbsp;Gols</th>";
                tbl +=  "<th asc='' id='timeApenaltis' class='headings penaltybox' style='width: 6%;'><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbsp;Pen.</th>";
                tbl +=  "<th asc='' id='timeBgols' class='headings' style='width: 6%;'><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbsp;Gols</th>";
                tbl +=  "<th asc='' id='nomeB' class='headings' style='width: 25%; text-align: left;'><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbsp;Time Visitante</th>";
                tbl +=  "<th asc='' id='data' class='headings' style='width: 12%;'><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbsp;Data</th>";
                tbl +=  "<th asc='' id='campeonato' class='headings' style='width: 14%;'><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbsp;Campeonato</th>";
                tbl +=  "<th asc='' id='calculo' class='headings' style='width: 6%;'><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbsp;Calc?</th>";
                tbl += "<th asc='' id='pontos' class='headings' style='width: 6%;'><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbsp;Pts</th>";
            tbl +=  "</tr>";
        tbl +=  "</thead>";
        tbl +=  "<tbody>";

        // criar linhas
        $.each(ajax_data, function(index, val){

            var pen = '-';
            if(index>=(from_result_num-1) && index<=(from_result_num+results_per_page-2)){

            if(val['timeApenaltis'] !== null && val['timeBpenaltis'] !== null && val['timeApenaltis'] !== '' && val['timeBpenaltis'] !== ''){
                pen = "("+val['timeApenaltis']+") pen. ("+val['timeBpenaltis']+")";
            }

            var calcBadge = (val['calculo'] == '1' || val['calculo'] == 1 || val['calculo'] == 'Sim') ? "<span class='ativo' style='font-size:0.75rem; padding:2px 6px;'>Sim</span>" : "<span class='inativo' style='font-size:0.75rem; padding:2px 6px;'>Não</span>";

            tbl += "<tr id='"+val['id']+"' data-href='match_info.php?match_id="+val['id']+"'>";
                tbl += "<td style='text-align: left;'><div class='team-cell'><img src='/images/bandeiras/"+val['bandeiraA']+"' class='bandeira' alt='"+val['nomeA']+"'> <a href='./teamstatus.php?team="+val['idA']+"' class='team-link'>"+val['nomeA']+"</a></div></td>";
                tbl +=  "<td style='font-weight: 700; font-size: 1.05rem;'>"+val['timeAgols']+"</td>";
                tbl +=  "<td class='penaltybox' style='color: #64748b; font-size: 0.8rem;'>"+pen+"</td>";
                tbl +=  "<td style='font-weight: 700; font-size: 1.05rem;'>"+val['timeBgols']+"</td>";
                tbl +=  "<td style='text-align: left;'><div class='team-cell'><img src='/images/bandeiras/"+val['bandeiraB']+"' class='bandeira' alt='"+val['nomeB']+"'> <a href='./teamstatus.php?team="+val['idB']+"' class='team-link'>"+val['nomeB']+"</a></div></td>";
                tbl +=  "<td style='color: #475569; font-size: 0.85rem;'>"+val['data']+"</td>";
                tbl +=  "<td style='font-weight: 500; font-size: 0.85rem;'>"+val['campeonato']+"</td>";
                tbl +=  "<td>"+calcBadge+"</td>";
                tbl += "<td class='points-cell'>"+val['pontos']+"</td>";
            tbl +=  "</tr>";
            }
        });

        tbl += '</tbody>';
    tbl += '</table>';

    //mostrar dados da tabela
    $(document).find('.tbl_user_data').html(tbl);
    addFilters();

    if(highlighted && highlighted !== 0 && highlighted !== '0'){
        $(document).find('#'+highlighted).addClass('highlighted');

        if(direction == 1){
            asc = activeDirection;
        }
        if(asc){
            $(document).find('#'+highlighted).find('.descending').addClass('hidden');
            $(document).find('#'+highlighted).find('.ascending').removeClass('hidden');
        } else {
            $(document).find('#'+highlighted).find('.ascending').addClass('hidden');
            $(document).find('#'+highlighted).find('.descending').removeClass('hidden');
        }
    }

    activeSort = highlighted;
    activeDirection = asc;

    $('*[data-href]').on('click', function() {
        window.location = $(this).data("href");
    });
}

$(document).on('click', '.pagination_link', function(){
    var page = $(this).attr('id');
    updateTable(localData, page,activeSort, 1);
});


function pagination(current_page, total_pages){
    var pgn = '<ul class="pagination">';
    if(total_pages > 1){
        var prev_page = parseInt(current_page) - 1;
        var next_page = parseInt(current_page) + 1;

        if(current_page > 1){
            pgn += '<li><button class="pagination_link" id="inicio" title="Primeira Página">&laquo;</button></li>';
            pgn += '<li><button class="pagination_link" id="' + prev_page + '" title="Página Anterior">&lsaquo;</button></li>';
        }

        var range = 2;
        var initial_num = parseInt(current_page) - range;
        var condition_limit_num = parseInt(current_page) + range + 1;

        for (var x = initial_num; x < condition_limit_num; x++) {
            if ((x > 0) && (x <= total_pages)) {
                if (x == current_page) {
                    pgn += '<li class="active"><button class="pagination_link" id="' + x + '" disabled>' + x + '<span class="sr-only">(current)</span></button></li>';
                } else {
                    pgn += '<li><button class="pagination_link" id="' + x + '">' + x + '</button></li>';
                }
            }
        }

        if(current_page < total_pages){
            pgn += '<li><button class="pagination_link" id="' + next_page + '" title="Próxima Página">&rsaquo;</button></li>';
            pgn += '<li><button class="pagination_link" id="final" title="Última Página">&raquo;</button></li>';
        }
    }
    pgn += '</ul>';
    return pgn;
}

$(document).on('click', ".toggleButton, .togglebutton", function() {

var modalType = $(this).attr("id");

if(modalType !== 'retornar'){
    $(".modalOverlay").fadeIn(200);
    $(".moreInfoModal").fadeIn(200);
    $(".modal-guts").hide();
    $("#modal"+modalType).show();
    $('#retornar').show();
} else {
    $(".modalOverlay").fadeOut(200);
    $(".moreInfoModal").fadeOut(200);
    $(".modal-guts").hide();
    $('#retornar').hide();
}
});

$(document).on('click', '.modalOverlay', function(e){
    $(".modalOverlay").fadeOut(200);
    $(".moreInfoModal").fadeOut(200);
    $(".modal-guts").hide();
    $('#retornar').hide();
});

function addFilters(){
    $(document).find('.headings').click(function(){
       treatResults(this);
    });
}

function treatResults(item){
    var id = $(item).attr('id');

    sortResults(id, asc);

    if(asc){
        asc = false;
    } else {
        asc = true;
    }

}

function sortResults(prop, asc) {

if(prop == 'pontos'){

    localData = localData.sort(
        function(a,b){
            if (asc) return a[prop] - b[prop];
            if (!asc) return b[prop] - a[prop];
            else return 0;
        }
    );
} else {
    localData = localData.sort(
        function(a, b) {
            if (((a[prop] < b[prop]) && (!asc))||((a[prop] > b[prop]) && (asc))) return 1;
            else if (((a[prop] > b[prop]) && (!asc))||((a[prop] < b[prop]) && (asc))) return -1;
            else return 0;
        }
    );
}

    updateTable(localData, 1,prop,0);
}

});

</script>

<?php

//query informacoes
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogos.php");
$database = new Database();
$db = $database->getConnection();

$jogo = new Jogo($db);
$info_stmt = $jogo->recuperarInfoGeral();
$info = $info_stmt->fetch(PDO::FETCH_ASSOC);

?>

<div class="ranking-container">
    <div class="ranking-card">
        <div class="ranking-card-header">
            <div>
                <h2 class="ranking-card-title">
                    <span class="material-symbols-outlined" style="color: #0284c7; font-size: 1.8rem;">history</span>
                    Recordes e Jogos Gerais
                </h2>
                <h3 class="ranking-card-date">Estatísticas acumuladas e histórico completo de partidas</h3>
            </div>
            
            <div id="direita" class="search-container">
                <input type="text" id="caixa_pesquisa" placeholder="Pesquisar partida...">
                <span class="material-symbols-outlined">search</span>
            </div>
        </div>

        <div id="info-jogos">
            <a href="#" id="Pontos" class="masterblock infoblock togglebutton" title="Clique para ver recordes de pontos">
                <span class="material-symbols-outlined">military_tech</span>
                <span><?php echo $info['pontosTrocados']; ?></span>
                <span class="stat-label">Pontos Trocados</span>
            </a>
            <a href="#" id="Jogos" class="masterblock infoblock togglebutton" title="Clique para ver recordes de jogos">
                <span class="material-symbols-outlined">calendar_today</span>
                <span><?php echo $info['jogosTotais']; ?></span>
                <span class="stat-label">Jogos Totais</span>
            </a>
            <a href="#" id="Vitoria" class="masterblock infoblock togglebutton" title="Clique para ver maiores vitórias">
                <span class="material-symbols-outlined vitoria">arrow_circle_up</span>
                <span class="vitoria"><?php echo $info['vitorias']; ?></span>
                <span class="stat-label">Vitórias</span>
            </a>
            <a href="#" id="Empate" class="masterblock infoblock togglebutton" title="Clique para ver empates">
                <span class="material-symbols-outlined empate">do_not_disturb_on</span>
                <span class="empate"><?php echo $info['empates']; ?></span>
                <span class="stat-label">Empates</span>
            </a>
            <a href="#" id="Gols" class="masterblock infoblock togglebutton" title="Clique para ver maiores goleadas">
                <span class="material-symbols-outlined" style="color: #0284c7;">sports_soccer</span>
                <span><?php echo $info['gols']; ?></span>
                <span class="stat-label">Gols Marcados</span>
            </a>
        </div>

        <div class="tbl_user_data">
            <div style="text-align: center; padding: 2rem;">
                <img id="loading" src="/images/icons/ajax-loader.gif" alt="Carregando...">
            </div>
        </div>
    </div>
</div>

<div class="modalOverlay closed" id="modalOverlay"></div>
<div class="moreInfoModal closed" id="moreInfoModal">
  <div id="modalPontos" class="modal-guts closed">
    <?php $id = 0; include 'modals/modalPontos.php'; ?>
  </div>
  <div id="modalJogos" class="modal-guts closed">
    <?php include_once 'modals/modalJogos.php'; ?>
  </div>
  <div id="modalVitoria" class="modal-guts closed">
    <?php
      $resultado_VED = 'V';
      include 'modals/modalResultados.php';
    ?>
  </div>
  <div id="modalEmpate" class="modal-guts closed">
    <?php
      $inicio_titulo = 'Com quem';
      $final_titulo = 'empatou';
      $resultado_VED = 'E';
      include 'modals/modalResultados.php';
    ?>
  </div>
  <div id="modalGols" class="modal-guts closed">
    <?php
      $titulo = "";
      $goleadasAplicadas = 1;
      include 'modals/modalGols.php';
    ?>
  </div>
  <div><button class="toggleButton" id="retornar">Fechar Janela</button></div>
</div>

<?php
include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");
?>
