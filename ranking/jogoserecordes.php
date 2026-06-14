<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Jogos & Recordes";
$css_filename = "indexRanking";
$css_login = 'login';
$aux_css = 'jogoserecordes';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");
include_once 'ranking_header.php';

?>

<script>

var localData = [];
var asc = true;
var activeSort = '';

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
    tbl += "<hr>";
    tbl += "<table id='tabelajogos' class='table'>";
        tbl += "<thead id='headings'>";
            tbl += "<tr>";
                tbl += "<th asc='' id='nomeA' class='headings' width='24%'><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbspTime A</th>";
                tbl +=  "<th asc='' id='timeAgols' class='headings' width='5%'><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbspGols</th>";
                tbl +=  "<th asc='' id='timeApenaltis' class='headings' width='5%' class='penaltybox'><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbsp</th>";
                tbl +=  "<th asc='' id='timeBgols' class='headings' width='5%'><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbspGols</th>";
                tbl +=  "<th asc='' id='nomeB' class='headings' width='24%'><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbspTime B</th>";
                tbl +=  "<th asc='' id='data' class='headings' width='14%'><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbspData</th>";
                tbl +=  "<th asc='' id='campeonato' class='headings' width='14%'><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbspCampeonato</th>";
                tbl +=  "<th asc='' id='calculo' class='headings' width='5%'><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbspCalculado?</th>";
                tbl += "<th asc='' id='pontos' class='headings' width='4%'><span class='material-symbols-outlined ascending hidden'>arrow_drop_up</span><span class='material-symbols-outlined descending hidden'>arrow_drop_down</span>&nbspPontos</td>";
            tbl +=  "</tr>";
        tbl +=  "</thead>";
        tbl +=  "<tbody>";

        // criar linhas
        $.each(ajax_data, function(index, val){

            var pen = '';
            if(index>=(from_result_num-1) && index<=(from_result_num+results_per_page-2)){

            if(val['timeApenaltis'] != 0 && val['timeBpenaltis'] != 0){
                pen = "("+val['timeApenaltis']+") pen. ("+val['timeBpenaltis']+")";
            }

            tbl += "<tr id='"+val['id']+"' data-href='match_info.php?match_id="+val['id']+"'>";
                tbl += "<td class='esquerdo nopadding'><img src='/images/bandeiras/"+val['bandeiraA']+"' class='bandeira'>    <a href='./teamstatus.php?team="+val['idA']+"'>"+val['nomeA']+"</a></td>";
                tbl +=  "<td class='nopadding'>"+val['timeAgols']+"</td>";
                tbl +=  "<td class='penaltybox nopadding'>"+pen+"</td>";
                tbl +=  "<td class='nopadding'>"+val['timeBgols']+"</td>";
                tbl +=  "<td class='direito nopadding'><a href='./teamstatus.php?team="+val['idB']+"'>"+val['nomeB']+"</a>    <img src='/images/bandeiras/"+val['bandeiraB']+"' class='bandeira'>  </td>";
                tbl +=  "<td>"+val['data']+"</td>";
                tbl +=  "<td>"+val['campeonato']+"</td>";
                tbl +=  "<td>"+val['calculo']+"</td>";
                tbl += "<td>"+val['pontos']+"</td>";
            tbl +=  "</tr>";
            }
        });

        tbl += '</tbody>';
    tbl += '</table>';

    //mostrar dados da tabela
    $(document).find('.tbl_user_data').html(tbl);
    addFilters();

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
var pgn = '';
pgn += "<ul class='pagination'>";

// button for first page
if(current_page>1){
    pgn +=  "<li><button class='pagination_link' id='inicio' title='Ir para o início'>";
    pgn +=  "Inicio";
    pgn +=  "</button></li>";
}

// range of links to show
const range = 2;

// display links to 'range of pages' around 'current page'
var initial_num = current_page - range;
var condition_limit_num = (+current_page + +range)  + +1;

// teste com While
var x;
if(initial_num > 0){
    x = initial_num;
} else {
    x = 1;
}

while(x <= total_pages && x < condition_limit_num){
    if (x == current_page) {
            pgn += "<li><button class='pagination_link' id='"+x+"' disabled>"+x+"<span class=\"sr-only\">(current)</span></button></li>";
        }
        else {
            pgn += "<li><button class='pagination_link' id='"+x+"'>"+x+"</button></li>";
        }
    x = x+1;
}

// button for last page
if(current_page<total_pages){
    pgn += "<li><button class='pagination_link' id='final' title='Última página é "+total_pages+".'>";
    pgn += "Final";
    pgn += "</button></li>";
}

pgn += "</ul>";

return pgn;
}

$(".toggleButton").click(function() {

var modalType = $(this).attr("id");

if(modalType !== 'retornar'){
    $(".modalOverlay").show();
    $(".moreInfoModal").show();
    $("#modal"+modalType).show();
    $('#retornar').show();
} else {
    $(".modalOverlay").hide();
    $(".moreInfoModal").hide();
    $(".modal-guts").hide();
    $('#retornar').hide();
}
});

$('.modalOverlay').click(function(e){
    $('*[id*=odal]').each(function() {
    $(this).hide();
    $('#retornar').hide();
    });
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

echo "<div id='ranking-container'>";
echo "<div  id='ranking'>";
echo "<div id='direita'><input type=text id='caixa_pesquisa' placeholder='Pesquisar...'><span class='material-symbols-outlined'>search</span></div>" ;
echo "<h2> Recordes e jogos gerais </h2>";
echo "<hr>";

//query informacoes
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogos.php");
$database = new Database();
$db = $database->getConnection();

$jogo = new Jogo($db);
$info_stmt = $jogo->recuperarInfoGeral();
$info = $info_stmt->fetch(PDO::FETCH_ASSOC);

echo "<div id='info-jogos'>";
echo "<a href='#' id='Pontos' class='masterblock infoblock togglebutton' title='Pontos trocados no ranking'><span class='material-symbols-outlined'>military_tech</span> " . $info['pontosTrocados']. "</a>";
echo "<a href='#' id='Jogos' class='masterblock infoblock  togglebutton' title='Jogos totais'><span class='material-symbols-outlined'>calendar_today</span> " . $info['jogosTotais'] . "</a>";
echo "<a href='#' id='Vitoria' class='masterblock infoblock  togglebutton' title='Número de jogos com vencedor'><span class='material-symbols-outlined vitoria'>arrow_circle_up</span> " . $info['vitorias'] . "</a>";
echo "<a href='#' id='Empate' class='masterblock infoblock  togglebutton' title='Número de empates'><span class='material-symbols-outlined empate'>do_not_disturb_on</span> " . $info['empates'] . "</a>";
echo "<a href='#' id='Gols' class='masterblock infoblock  togglebutton' title='Total de gols marcados'><span class='material-symbols-outlined vitoria'>sports_soccer</span> " . $info['gols']  . "</a>";
echo "</div>";
echo "<br>";

    // paging buttons here
    echo "<div style='clear:both; float:center'></div>";
echo "<hr>";

echo "<div style='clear:both; float:center'></div>";

// display the products if there are any

echo "<div class='tbl_user_data'><img id='loading' src='/images/icons/ajax-loader.gif'></div>";


echo('</div>');
echo('</div>');

echo '<div class="modalOverlay closed" id="modalOverlay"></div>';
$id = 0;
echo '<div class="moreInfoModal closed" id="moreInfoModal">';
  echo '<div id="modalPontos" class="modal-guts closed">';
        include 'modals/modalPontos.php';
 echo "</div>";
  echo " <div id='modalJogos' class='modal-guts closed'>";

         include_once 'modals/modalJogos.php';

  echo " </div>";
echo  " <div id='modalVitoria' class='modal-guts closed'>";

         $resultado_VED = 'V';
         include 'modals/modalResultados.php';

echo    "</div>";
   echo "<div id='modalEmpate' class='modal-guts closed'>";

         $inicio_titulo = 'Com quem';
         $final_titulo = 'empatou';
         $resultado_VED = 'E';
         include 'modals/modalResultados.php';

echo "</div>";
echo "<div id='modalGols' class='modal-guts closed'>";

         $titulo = "";
         $goleadasAplicadas = 1;
         include 'modals/modalGols.php';

echo " </div>";
echo ' <div><button class="toggleButton" id="retornar">Retornar</button></div>';
echo "</div>";

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
