<!DOCTYPE html>

<?php

session_start();

$user_id = (isset($_SESSION["user_id"]) ? $_SESSION["user_id"] : "");

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Escudos Pops - CONFUSA.top";
$css_filename = "escudos_pop";
$css_login = 'login';
$aux_css = 'indexRanking';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/escudos_pop.php");

$escudos_pop = new EscudosPop($db);
$team_data = $escudos_pop->loadTeams();
$team_ids = $escudos_pop->loadTeamIds();

echo "<div id='escudos-pop-header'>
<div>
    <h2>Escudos Pops CONFUSA</h2>
    <h3> Redesenhamos <span id='contagem_escudos'>". count($team_ids). "</span> escudos CONFUSA de maneira minimalista.</h3>
    <h3>Você consegue adivinhar a quais clubes pertencem?</h3></div>
	<div>";
	echo "<label for='checkbox-21' id='filter-pending'><span>Incluir fase 1</span>";
    echo "<input type='checkbox' id='checkbox-21' name='apenasConfusa'>";
    echo "</label>";
	echo "</div>


</div>";

echo "<div id='tabela-escudos'>";

foreach($team_ids as $single_team){
  echo "<div class='conjunto-escudo' data-team='{$single_team['team_id']}'>";
  echo "<img class='escudo-time' src='/escudos_pop/images/{$single_team['team_id']}.png?v=3'/>";
  echo "<input type='text' class='adivinhador-nome' data-id='{$single_team['team_id']}'/>";
  echo "</div>";

}

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
      //console.log("loading success");
      var return_data = data.return_data;
      Object.values(return_data).forEach(function(item){
        // populate screen
        $("input[data-id='"+item.team_id+"']").val(item.team_name).addClass("palpite-correto").prop("disabled", "disabled");
        if(item.team_name.length > 14){

          $("input[data-id='"+item.team_id+"']").addClass("smaller-font");
        }
        //console.log(item.team_id);
      });



    })
    .fail(function() {
      console.log("loading error");
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
          console.log("saving success");
        })
        .fail(function() {
          console.log("saving error");
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

		$('#filter-pending span').text(new_text);
		
		$('.conjunto-escudo:lt(64)').toggle();
		
		let n = $( ".conjunto-escudo:visible").length;
		console.log(n);
		$("#contagem_escudos").text(n);

		
    });

  $(".adivinhador-nome").focusout(function(){

    if(team_name = check_guess(this)){
      $(this).val(team_name);
      if(team_name.length > 14){
        $(this).addClass("smaller-font");
      }
      $(this).addClass("palpite-correto");
      $(this).prop("disabled", "disabled");
      //console.log(true);
    } else {
      //console.log(false);
      $(this).addClass("palpite-incorreto");
    }
  });
});


</script>
