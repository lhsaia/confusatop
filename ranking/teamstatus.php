<?php

//ini_set( 'display_errors', true );
//error_reporting( E_ALL );
session_start();

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

// page given in URL parameter, default page is one
$page = isset($_GET['page']) ? $_GET['page'] : 1;

// set number of records per page
$records_per_page = 15;

// calculate for the query LIMIT clause
$from_record_num = ($records_per_page * $page) - $records_per_page;

$id = $_GET['team'];

//estabelecer conexão com banco de dados
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogos.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/federacoes.php");

$database = new Database();
$db = $database->getConnection();

$pais = new Pais($db);
$jogo = new Jogo($db);
$federacao = new Federacao($db);

// query paises
$stmt = $pais->readInfo($id);
$info = $stmt->fetch(PDO::FETCH_ASSOC);
$nome_selecao = $info['nome'];
$federacao_selecao = $info['federacao'];
$pontos = $info['pontos'];
$bandeira = $info['bandeira'];
$ativo = ($info['ativo']) ? 'ativo' : 'inativo';

//query federacao
$stmt = $federacao->selFederacao($federacao_selecao);
$info = $stmt->fetch(PDO::FETCH_ASSOC);
$federacao_selecao = $info['nome'];

$page_title = "Ranking de Seleções - " . $nome_selecao;
$css_filename = "indexRanking";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");
include_once 'ranking_header.php';

echo "<div id='ranking-container'>";
echo "<div  id='ranking'>";
echo "<img id='bandeiraGrande' src='/images/bandeiras/" . $bandeira . "' width='100px'>" ;
echo "<h2>" . $nome_selecao . " - Informações </h2>";
echo "<h3>" . $federacao_selecao ." <span class='".$ativo."'> ".$ativo ."</span></h3> ";
echo "<hr>";

//query jogos time
$jogo_stmt = $jogo->selecionarJogosTime($id,$from_record_num, $records_per_page);

    // the page where this paging is used
    $page_url = "teamstatus.php?team=" . $id . "&";

    // count all products in the database to calculate total pages
    $total_rows = $jogo->countAllSingleTeam($id);



//query informacoes time
$info_stmt = $jogo->recuperarInfoTime($id);
$info = $info_stmt->fetch(PDO::FETCH_ASSOC);

$golsPro = $info['golsProVisitante']+$info['golsProMandante'];
$golsContra = $info['golsContraVisitante']+$info['golsContraMandante'];

// Add toggleButton class to buttons below
echo "<div id='info-jogos'>";
echo "<a href='#' id='Pontos' class='infoblock togglebutton' title='Pontos totais no ranking'><i class='fas fa-medal'></i> " . $pontos . "</a>";
echo "<a href='#' id='Jogos' class='infoblock togglebutton' title='Jogos totais'><i class='far fa-calendar-alt'></i> " . $total_rows . "</a>";
echo "<a href='#' id='Vitoria' class='infoblock togglebutton' title='Número de vitórias'><i class='fas fa-arrow-circle-up vitoria'></i> " . $info['vitorias'] . "</a>";
echo "<a href='#' id='Empate' class='infoblock togglebutton' title='Número de empates'><i class='fas fa-minus-circle empate'></i> " . $info['empates'] . "</a>";
echo "<a href='#' id='Derrota' class='infoblock togglebutton' title='Número de derrotas'><i class='fas fa-arrow-circle-down derrota'></i> " . $info['derrotas'] . "</a>";
echo "<a href='#' id='GolsPro' class='infoblock togglebutton' title='Total de gols marcados'><i class='fas fa-futbol vitoria'></i> " . $golsPro  . "</a>";
echo "<a href='#' id='GolsContra' class='infoblock togglebutton' title='Total de gols sofridos'><i class='fas fa-futbol derrota'></i> " .$golsContra . "</a>";
echo "</div>";
echo "<br>";
echo "<div style='clear:both; float:center'></div>";
echo "<p align='center'>Jogos</p>";

    // paging buttons here
    echo "<div style='clear:both; float:center'></div>";
    echo "<div align='center'>";
    include_once($_SERVER['DOCUMENT_ROOT']."/elements/paging.php");
    echo "</div>";
echo "<hr>";

// display the products if there are any

echo "<table id='tabelajogos' class='table'>";
echo "<thead>";
echo "<tr>";
echo "<th>Time A</th>";
echo "<th>Gols</th>";
echo "<th class='penaltybox'></th>";
echo "<th>Gols</th>";
echo "<th>Time B</th>";
echo "<th>Data</th>";
echo "<th>Campeonato</th>";
echo "<th>Calculado?</th>";
echo "</tr>";
echo "</thead>";
echo "<tbody>";


        while ($row = $jogo_stmt->fetch(PDO::FETCH_ASSOC)){

            extract($row);

            if($timeA_penaltis == 0 && $timeB_penaltis == 0){
                $pen = "";
            } else {
                $pen = "({$timeA_penaltis}) pen. ({$timeB_penaltis})";
            }


            echo "<tr data-href='match_info.php?match_id={$idJogo}'>";
                echo "<td class='esquerdo nopadding'><img src='/images/bandeiras/{$bandeiraA}' class='bandeira'>    <a href='./teamstatus.php?team={$idA}'>{$nomeA}</a></td>";
                echo "<td class='nopadding'>{$timeA_gols}</td>";
                echo "<td class='penaltybox nopadding'>{$pen}</td>";
                echo "<td class='nopadding'>{$timeB_gols}</td>";
                echo "<td class='direito nopadding'><a href='./teamstatus.php?team={$idB}'>{$nomeB}</a>    <img src='/images/bandeiras/{$bandeiraB}' class='bandeira'>  </td>";
                echo "<td>{$data}</td>";
                echo "<td>{$nomeCampeonato}</td>";
                echo "<td>{$calculo}</td>";
            echo "</tr>";

        }

        echo "</tbody>";
    echo "</table>";

echo('</div>');
echo('</div>');



?>

<div class="modalOverlay closed" id="modalOverlay"></div>

<div class="moreInfoModal closed" id="moreInfoModal">
  <div id='modalPontos' class="modal-guts closed">
    <?php
        include_once 'modals/modalPontos.php';
    ?>
  </div>
  <div id='modalJogos' class="modal-guts closed">
    <?php
        include_once 'modals/modalJogos.php';
    ?>
  </div>
  <div id='modalVitoria' class="modal-guts closed">
    <?php
        $inicio_titulo = 'A quem';
        $final_titulo = 'venceu';
        $resultado_VED = 'V';
        include 'modals/modalResultados.php';
    ?>
  </div>
  <div id='modalEmpate' class="modal-guts closed">
    <?php
        $inicio_titulo = 'Com quem';
        $final_titulo = 'empatou';
        $resultado_VED = 'E';
        include 'modals/modalResultados.php';
    ?>
  </div>
  <div id='modalDerrota' class="modal-guts closed">
    <?php
        $inicio_titulo = 'De quem';
        $final_titulo = 'perdeu';
        $resultado_VED = 'D';
        include 'modals/modalResultados.php';
    ?>
  </div>
  <div id='modalGolsPro' class="modal-guts closed">
    <?php
        $titulo = 'aplicadas';
        $goleadasAplicadas = 1;
        include 'modals/modalGols.php';
    ?>
  </div>
  <div id='modalGolsContra' class="modal-guts closed">
    <?php
        $titulo = 'sofridas';
        $goleadasAplicadas = 0;
        include 'modals/modalGols.php';
    ?>
  </div>
  <div><button class="toggleButton" id="retornar">Retornar</button></div>
</div>

<script>

jQuery(document).ready(function($) {
    $('*[data-href]').on('click', function() {
        window.location = $(this).data("href");
    });
});

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

// $('.moreInfoModal').click(function(e){
//     var identifier = e.target.id;

    $('.modalOverlay').click(function(e){
        $('*[id*=odal]').each(function() {
        $(this).hide();
        $('#retornar').hide();
        });
        // $("#modal"+identifier).hide();
    });


// });



</script>

<?php

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
