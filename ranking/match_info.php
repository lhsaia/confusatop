<?php

session_start();

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Informações da partida";
$css_filename = "indexRanking";
$css_login = 'login';
$aux_css = "match_info";
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");
include_once 'ranking_header.php';

if(isset($_GET['match_id'])){
    $match_id = $_GET['match_id'];
} else {
    $match_id = null;
}

?>

<div id="ranking-container">
<div align="center" id="ranking">
<h2>Informações da partida</h2>
<hr>

<?php

//estabelecer conexão com banco de dados
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogador.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/arbitros.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/estadio.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/competicao.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogos.php");

$database = new Database();
$db = $database->getConnection();

$pais = new Pais($db);
$usuario = new Usuario($db);
$jogo = new Jogo($db);

$results = $jogo->getSingleMatchInfo($match_id);


echo "<div style='clear:both;'></div>";
echo "<hr>";

  echo "<div id='match-info-header'>";
    echo "<img id='match-info-team-1-flag' src='/images/bandeiras/{$results['timeA_bandeira']}'/>";
    echo "<span id='match-info-team-1-name'>";
      echo $results['timeA_nome'];
    echo "</span>";
    echo "<span id='match-info-team-1-score'>";
      echo $results['timeA_gols'];
    echo "</span>";
    if($results['timeA_penaltis'] + $results['timeB_penaltis'] != 0){
      echo "<span id='match-info-team-1-penalty'>";
        echo $results['timeA_penaltis'];
      echo "</span>";
    }
    echo "<span id='match-info-x-mark'>";
      echo "X";
    echo "</span>";
    if($results['timeA_penaltis'] + $results['timeB_penaltis'] != 0){
      echo "<span id='match-info-team-2-penalty'>";
        echo $results['timeB_penaltis'];
      echo "</span>";
    }
    echo "<span id='match-info-team-2-score'>";
      echo $results['timeB_gols'];
    echo "</span>";
    echo "<span id='match-info-team-2-name'>";
      echo $results['timeB_nome'];
    echo "</span>";
    echo "<img id='match-info-team-2-flag' src='/images/bandeiras/{$results['timeB_bandeira']}'/>";
  echo "</div>";
  echo "<div id='match-info-base'>";
    echo "<div id='match-info-competition-block'>";
      echo "<span id='match-info-competition'>";
        echo $results['competition_name'];
      echo "</span>";
      if($results['fase']){
        echo "<span id='match-info-phase'>";
          echo "&nbsp(" . $results['fase'] . ")";
        echo "</span>";
      }

    echo "</div>";
    echo "<span id='match-info-stadium'>";
      echo $results['estadio'];
    echo "</span>";
    echo "<span id='match-info-date'>";
      echo date("d-m-Y", strtotime($results['data']));;
    echo "</span>";
    if($results['nome_arbitro'] != ""){
      echo "<span id='match-info-referee'>";
        echo "Árbitro: " . $results['nome_arbitro'];
      echo "</span>";
    }

  echo "</div>";
  echo "<div id='match-info-events'>";

  $stmt = $jogo->getSingleMatchEvents($match_id);



        while ($event = $stmt->fetch(PDO::FETCH_ASSOC)){
          extract($event);
          if($tempo == 1 && $minutos > 45){
            $minutos_corrigidos = "45+" . ($minutos - 45);
          } else if($tempo == 2 && $minutos > 90){
            $minutos_corrigidos = "90+" . ($minutos - 90);
          } else {
            $minutos_corrigidos = $minutos;
          }

          if($results['timeA_id'] == $id_time){
            $player_name_event_a = stripslashes($nome_jogador);
            $player_id_event_a = $id_jogador;
            $minute_event_a = $minutos_corrigidos;
            $player_name_event_b = "";
            $player_id_event_b = "";
            $minute_event_b = "";
          } else if($results['timeB_id'] == $id_time){
            $player_name_event_b = stripslashes($nome_jogador);
            $player_id_event_b = $id_jogador;
            $minute_event_b = $minutos_corrigidos;
            $player_name_event_a = "";
            $player_id_event_a = "";
            $minute_event_a = "";
          }

          switch ($tipo) {
            case 1:
              $icon = 'fas fa-futbol goal ';
              break;
            case 2:
              $icon = 'fas fa-square yellow-card ';
              break;
            case 3:
              $icon = 'fas fa-square red-card ';
              break;
            case 4:
              $icon = 'fas fa-futbol own-goal ';
              break;
          }

            echo "<div class='match-event-unit'>";
              if($player_id_event_a != 0){
                echo "<a href='/ligas/playerstatus.php?player={$player_id_event_a}'><span class='match-event-player-name-1'>{$player_name_event_a}</span></a>";
              } else {
                echo "<span class='match-event-player-name-1'>{$player_name_event_a}</span>";
              }
              echo "<span class='match-event-minute-1'>{$minute_event_a}</span>";
              echo "<i class='match-event-icon {$icon}'>";
              echo "</i>";
              echo "<span class='match-event-minute-2'>{$minute_event_b}</span>";
              if($player_id_event_b != 0){
                echo "<a href='/ligas/playerstatus.php?player={$player_id_event_b}'><span class='match-event-player-name-2'>{$player_name_event_b}</span></a>";
              } else {
                echo "<span class='match-event-player-name-2'>{$player_name_event_b}</span>";
              }
            echo "</div>";


        }

  echo "</div>";

echo('</div>');
echo('</div>');

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
