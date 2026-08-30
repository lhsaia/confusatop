<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
?>
<!DOCTYPE html>

<?php
$user_id = (isset($_SESSION["user_id"]) ? $_SESSION["user_id"] : "");

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Escudos Pops - CONFUSA.top";
$css_filename = "home_redesign";
$aux_css = 'home_redesign';
$extra_css = 'escudos_pop';
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/escudos_pop.php");

$escudos_pop = new EscudosPop($db);
$team_data = $escudos_pop->loadTeams();
$team_ids = $escudos_pop->loadTeamIds();

echo "<div id='main-wrapper'>";

echo "<div id='escudos-pop-header'>
  <div class='header-title-container'>
    <h2><span class='material-symbols-outlined icon-header'>shield</span> Escudos Pops CONFUSA</h2>
    <h3>Redesenhamos <span id='contagem_escudos' class='count-badge'>". count($team_ids). "</span> escudos CONFUSA de maneira minimalista.</h3>
    <h3>Você consegue adivinhar a quais clubes pertencem?</h3>
  </div>
  <div class='header-filter-container'>
    <label for='checkbox-21' id='filter-pending' class='btn-filter'>
      <span class='material-symbols-outlined icon-toggle'>filter_list</span>
      <span id='filter-pending-text'>Incluir fase 1</span>
      <input type='checkbox' id='checkbox-21' name='apenasConfusa'>
    </label>
  </div>
</div>";

echo "<div id='tabela-escudos'>";

foreach($team_ids as $single_team){
  echo "<div class='conjunto-escudo' data-team='{$single_team['team_id']}'>";
  echo "<div class='escudo-img-wrap'><img class='escudo-time' src='/escudos_pop/images/{$single_team['team_id']}.png?v=3' alt='Escudo Pop' loading='lazy'/></div>";
  echo "<input type='text' class='adivinhador-nome' data-id='{$single_team['team_id']}' placeholder='Nome do time' autocomplete='off' spellcheck='false'/>";
  echo "</div>";
}

echo "</div>";

echo "</div>";

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>

<script>

var show_all = true;

var check_guess = (function () {
  var teams_info = <?php echo json_encode($team_data) ?>;
  var user_id = <?php echo (isset($_SESSION["user_id"]) ? $_SESSION["user_id"] : "undefined") ?>;

  if(user_id > 7){
    $.ajax({
      url: 'load_guess.php',
      type: 'POST',
      dataType: 'json',
      data: {user_id: user_id}
    })
    .done(function(data) {
      var return_data = data.return_data;
      if(return_data) {
        Object.values(return_data).forEach(function(item){
          var $input = $("input[data-id='"+item.team_id+"']");
          $input.val(item.team_name).addClass("palpite-correto").prop("disabled", "disabled");
          if(item.team_name.length > 14){
            $input.addClass("smaller-font");
          }
        });
      }
    })
    .fail(function() {
      // console.log("loading error");
    });
  }

  return function (target) {
    function filter_answers(team) {
      if(team.team_id == selected_id){
        return true;
      } else {
        return false;
      }
    }
    var selected_id = parseInt($(target).attr("data-id"));
    var user_input = $(target).val();

    //normalize input
    var normalized_input = user_input.normalize('NFD').replace(/[\u0300-\u036f]/g, "").toLowerCase();

    var guess_array = teams_info.filter(filter_answers);
    if(guess_array.find(el => el["possible_name"].normalize('NFD').replace(/[\u0300-\u036f]/g, "").toLowerCase() === normalized_input)){

      var team_name = guess_array[0].possible_name;

      if(user_id > 7 && team_name != ""){
        $.ajax({
          url: 'save_guess.php',
          type: 'POST',
          dataType: 'json',
          data: {selected_id: selected_id,
                  user_id: user_id,
                  team_name: team_name
                }
        })
        .done(function() {
          // console.log("saving success");
        })
        .fail(function() {
          // console.log("saving error");
        });

      }
      return team_name;
    } else {
      return false;
    }
  }
})();

$(document).ready(function() {
  $('.conjunto-escudo').hide();
  $('.conjunto-escudo:gt(63)').show();
  let n = $( ".conjunto-escudo:visible").length;
  $("#contagem_escudos").text(n);
  
  $('#filter-pending').click(function (e) {
    e.preventDefault();
    show_all = !show_all;
    let new_text = (show_all ? 'Incluir fase 1' : 'Apenas fase 2');

    $('#filter-pending-text').text(new_text);
    
    $('.conjunto-escudo:lt(64)').toggle();
    
    let n = $( ".conjunto-escudo:visible").length;
    $("#contagem_escudos").text(n);
  });

  $(".adivinhador-nome").on("keypress", function(e) {
    if(e.which === 13) {
      $(this).blur();
    }
  });

  $(".adivinhador-nome").focusout(function(){
    var self = this;
    var val = $(self).val().trim();
    if(!val) {
      $(self).removeClass("palpite-incorreto");
      return;
    }

    var team_name = check_guess(self);
    if(team_name){
      $(self).val(team_name);
      if(team_name.length > 14){
        $(self).addClass("smaller-font");
      }
      $(self).removeClass("palpite-incorreto").addClass("palpite-correto");
      $(self).prop("disabled", "disabled");
    } else {
      $(self).addClass("palpite-incorreto");
      setTimeout(function() {
        $(self).removeClass("palpite-incorreto");
      }, 1500);
    }
  });
});

</script>
