<!DOCTYPE html>

<?php

session_start();

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "OctaMotor - Competições";
$css_filename = "race_live";
$css_login = 'login';
$aux_css = "driver_info";
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");
include_once($_SERVER['DOCUMENT_ROOT']."/octamotor/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/octamotor/classes/track.php");
include_once($_SERVER['DOCUMENT_ROOT']."/octamotor/classes/competition.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");

$octa_database = new OctamotorDatabase();
$odb = $octa_database->getConnection();
$competition = new Competition($odb);

$pais = new Pais($db);

$competition_list = $competition->getCompetitionList();
$country_list = $pais->read(null, null, null);

?>
<div id='loadingDiv'><img src='/octamotor/images/lights.gif'/></div>
<div id="container-home-octamotor">
  <!-- Track viewer start -->
  <div id="driver-viewer" class="visible">
    <div class="container-control">
      <?php
      if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){
        echo "<a id='create-new-driver' class='editor-button'>Criar</a>";
      }

      echo "<select id='select-driver'>";
      foreach($competition_list as $competition_unit){
        echo "<option data-owner='{$competition_unit['owner']}' value='{$competition_unit['id']}'>{$competition_unit['name']} </option>";
      }
      echo "</select>";

      if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){
        echo "<a id='edit-driver' class='editor-button'>Editar</a>";
      }

        ?>

    </div>
    <div class="container-driver-main">
      <div id="container-competition-info">
        <div id='container-logo-competition'>
          <img id="competition-logo-display" class="image" src="" />
        </div>
        <span class="driver-info"><span class="driver-info-title">País: </span><span id="competition-country-info"></span></span>
        <span class="driver-info"><span class="driver-info-title">Ano inicial: </span><span id="competition-first-year-info"></span></span>
        <!-- <span class="driver-info"><span class="driver-info-title">Atual campeão: </span><span id="competition-current-champion-info"></span></span> -->
        <span class="driver-info"><span class="driver-info-title">Corridas: </span><span id="competition-total-races-info"></span></span>
        <span class="driver-info"><span class="driver-info-title">Temporadas: </span><span id="competition-total-seasons-info"></span></span>
        <span class="driver-info"><span class="driver-info-title">Sobre: </span><p id="competition-about-info"></p></span>
      </div>
      <div id="container-competition-seasons">

      </div>
      <div id="container-competition-standings">

      </div>
    </div>
  </div>
    <!-- Competition viewer end -->

    <!-- Competition editor start -->
  <div id="driver-editor" class="hidden">
    <div class="container-control">
      <a id="save-driver" class="editor-button">Salvar</a>
      <p id="driver-name-bar"></p>
      <a id="cancel-driver" class="editor-button">Cancelar</a>
    </div>
    <div class="container-driver-main">
      <div id="container-basic-form">
        <form id="driver-basic-form">
          <div hidden id="competition-id"></div>
          <div class='form-group'>
            <label for="competition-name">Nome</label>
            <input type="text" id="competition-name" placeholder="ex. F-Aerolito"/>
          </div>
          <div class='form-group'>
            <label for="competition-country">País</label>
            <select id="competition-country" required>
              <option value="" selected disable>Selecione o país...</option>
              <option value=0>Internacional</option>
              <?php
              while($result = $country_list->fetch(PDO::FETCH_ASSOC) ){
                echo "<option value='" . $result["id"] . "'>" . $result["nome"] . "</option>";
              }
              ?>
            </select>
          </div>
          <div class='form-group'>
            <label for="competition-quali-style">Tipo de qualificação</label>
            <select id="competition-quali-style" required>
              <option value="" selected disable>Selecione o tipo de qualificação...</option>
              <option value=1>Simples (uma volta)</option>
              <option value=2>Simples (três voltas)</option>
              <option value=3>Q1/Q2/Q3</option>
            </select>
          </div>
          <div class='form-group'>
            <label for="competition-max-drivers">Pilotos no grid (max)</label>
            <input type="number" value="28" id="competition-max-drivers"/>
          </div>
          <div class='form-group'>
            <label for="competition-max-time">Tempo máximo de prova (min)</label>
            <input type="number" value="120" id="competition-max-time"/>
          </div>
          <div class='form-group'>
            <label for="competition-total-length">Distância de prova (km)</label>
            <input type="number" value=300 id="competition-total-length"/>
          </div>
          <div class='form-group'>
            <label for="competition-point-system">Sistema de pontuação</label>
            <input type="text" placeholder='ex. 12-9-6-5-4-3-2-1' id="competition-point-system"/>
          </div>
          <div class='form-group'>
            <label for="competition-extra-points">Pontuação extra</label>
            <select id="competition-extra-points" required>
              <option value="" selected disable>Selecione se há pontuação extra...</option>
              <option value=0>Sem pontuação extra</option>
              <option value=1>+1 para volta mais rápida</option>
              <option value=2>+1 para pole position</option>
              <option value=3>+1 para volta mais rápida e +1 para pole position</option>
            </select>
          </div>
          <div class='form-group'>
            <label for="competition-about">Sobre</label>
            <textArea id="competition-about" placeholder="ex. História da competição, curiosidades, etc."></textArea>
          </div>
        </form>
      </div>
      <div id="container-image-form">
        <form id="driver-image-form">
          <div class='form-group'>
            <label class="picture-label" for="competition-logo"><span id="competition-logo-text">Logo</span><img class="hidden" id="competition-logo-preview" src=""/></label>
            <input type="file" id="competition-logo" onchange="readURL(this, 'logo');"/>
          </div>
		  <div class='form-group'>
            <label for="competition-type">Tipo de competição</label>
            <select id="competition-type" required>
              <option value="" selected disable>Selecione o tipo de competição...</option>
              <option value=0>Monopostos</option>
              <option value=1>Turismo</option>
            </select>
          </div>
        </form>
      </div>
      <div id="container-level-form">
        <form id="driver-level-form">
          <div class="form-master-group">
            <div class='form-group'>
              <label>Fatores</label>
            </div>
            <div class='form-group'>
              <label for="competition-car-factor">Carro</label>
              <input data-css="" type="range" min="0.5" max="1.5" step="0.1" value="1" id="competition-car-factor"/>
            </div>
            <div class='form-group'>
              <label for="competition-speed-factor">Volta lançada</label>
              <input data-css="" type="range" min="0.5" max="1.5" step="0.1" value="1" id="competition-speed-factor"/>
            </div>
            <div class='form-group'>
              <label for="competition-pace-factor">Ritmo de corrida</label>
              <input data-css="" type="range" min="0.5" max="1.5" step="0.1" value="1" id="competition-pace-factor"/>
            </div>
            <div class='form-group'>
              <label for="competition-technique-factor">Acerto do carro</label>
              <input data-css="" type="range" min="0.5" max="1.5" step="0.1" value="1" id="competition-technique-factor"/>
            </div>
            <div class='form-group'>
              <label for="competition-random-factor">Aleatório</label>
              <input data-css="" type="range" min="0.5" max="1.5" step="0.1" value="1" id="competition-random-factor"/>
            </div>
            <div class='form-group'>
              <label for="competition-aggressiveness-factor">Agressividade</label>
              <input data-css="" type="range" min="0.5" max="1.5" step="0.1" value="1" id="competition-aggressiveness-factor"/>
            </div>
            <div class='form-group'>
              <label for="competition-rain-factor">Chuva</label>
              <input data-css="" type="range" min="0.5" max="1.5" step="0.1" value="1" id="competition-rain-factor"/>
            </div>
            <div class='form-group'>
              <label for="competition-start-factor">Largada</label>
              <input data-css="" type="range" min="0.5" max="1.5" step="0.1" value="1" id="competition-start-factor"/>
            </div>
            <div class='form-group'>
              <label for="competition-event-factor">Eventos</label>
              <input data-css="" type="range" min="0.5" max="1.5" step="0.1" value="1" id="competition-event-factor"/>
            </div>
            <div class='form-group'>
              <label for="competition-position-factor">Posições</label>
              <input data-css="" type="range" min="0.5" max="1.5" step="0.1" value="1" id="competition-position-factor"/>
            </div>
            <div class='form-group'>
              <label for="competition-quali-prop-factor">Tempo treino</label>
              <input data-css="" type="range" min="0.5" max="1.5" step="0.01" value="1" id="competition-quali-prop-factor"/>
            </div>
            <div class='form-group'>
              <label for="competition-race-prop-factor">Tempo corrida</label>
              <input data-css="" type="range" min="0.5" max="1.5" step="0.01" value="1" id="competition-race-prop-factor"/>
            </div>

          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>

var logged_user = {  };
var id;
var country;
var logo;
var name;
var country_id;
var first_race;
var about;
var max_drivers;
var quali_type;
var speed_factor;
var pace_factor;
var technique_factor;
var random_factor;
var aggressiveness_factor;
var rain_factor;
var start_factor;
var event_factor;
var position_factor;
var quali_prop_factor;
var race_prop_factor;
var max_time;
var total_length;
var cached_data;
var point_system;
var extra_points;
var car_factor;
var competition_type;

function retrieveStandingsData(event_id, event_type){
  $.ajax({
    url: 'retrieve_standings.php',
    type: 'POST',
    dataType: 'json',
    data: {event_id: event_id,
          event_type: event_type}
  })
  .done(function(data) {

     var standings_data_html = "";

     //standings_data_html = "<h1>"+data.race+"</h1>";

     standings_data_html += "<table id='standings-table'>";
      standings_data_html += "<thead>";
      standings_data_html += "<tr>";
      standings_data_html += "<th></th>";
      standings_data_html += "<th>Driver</th>";
      standings_data_html += "<th>Team</th>";
      standings_data_html += "<th>Pts</th>";

      standings_data_html += "</tr>"
      standings_data_html += "</thead>";
      standings_data_html += "<tbody>";

      var counter = 1;
    data.standings_data.forEach(function(element) {
      standings_data_html += "<tr data-position='"+counter+"'><td class='driver-position'>"+counter+"</td><td class='driver-name driver-team-names driver-text'>" +element.name + "</td><td class='driver-team-names driver-text'>"+element.team_name+"</td><td class='driver-team-names driver-text'>"+element.total_points+"</td></tr>";
      counter++;
    });

  //  console.log(standings_data_html);

    $("#container-competition-standings").html(standings_data_html);

  })
  .fail(function() {

    });

}

function retrieveRaces(season_id, panel_element){

  $.ajax({
    url: 'retrieve_races.php',
    type: 'POST',
    dataType: 'json',
    data: {season_id: season_id}
  })
  .done(function(data) {

    //var str = JSON.stringify(data, null, 2); // spacing level = 2
    var races_html = "";

    data.races_data.forEach(function(element) {
      let add_edition;
      if(!element.name){
        element.name = "Grande Prêmio de " + element.country_name;
      }
      let driver_owner = parseInt($("#select-driver option:selected").attr("data-owner"));
      if(element.status == 0 && verifyLoggedUser(driver_owner)){
        add_edition = "<span class='editable-race'><i class='far fa-edit'></i></span>";
      } else{
        add_edition = "";
      }
      races_html += "<button class='accordion-race' data-status='"+element.status+"' data-race='"+ element.id +"'><img class='flag-thumb' src='/images/bandeiras/"+element.flag+"'/> " +element.name + add_edition +" </button>";
    });

    panel_element.innerHTML = races_html;

    var acc = document.getElementsByClassName("accordion-race");
    var i;

    for (i = 0; i < acc.length; i++) {
      acc[i].addEventListener("click", function() {
        var race_id = this.getAttribute("data-race");
        var classname = document.getElementsByClassName("active-race");

        Array.from(classname).forEach(function(element) {
            element.classList.remove("active-race");
         });
           this.classList.toggle("active-race");
        let driver_owner = parseInt($("#select-driver option:selected").attr("data-owner"));
        if(this.getAttribute("data-status") == 0 && verifyLoggedUser(driver_owner)){
          console.log(data.races_data);
          createRaceInputs(cached_data);

          for(let item of data.races_data){
            if(item.id == race_id){
              $("#new-race-name-input").val(item.name);
              $("#new-race-season-input").val(season_id);
              $("#new-race-track-input").val(item.track_id);

              let dt = new Date(parseInt(item.datetime)*1000);

              let formatted_time = dt.getHours().toString().padStart(2,"0") + ":" + dt.getMinutes().toString().padStart(2,"0");
              let formatted_date = (dt.getFullYear()) + "-" + (dt.getMonth()+1).toString().padStart(2,"0") + "-" + (dt.getDate()).toString().padStart(2,"0");

              $("#new-race-date-input").val(formatted_date);
              $("#new-race-time-input").val(formatted_time);

              $("#create-race").click(function(){

                let localTimeOffset = -new Date().getTimezoneOffset()/60;
                let race_name = $("#new-race-name-input").val();
                let race_season = $("#new-race-season-input").val();
                let race_track = $("#new-race-track-input").val();
                let race_date = new Date($("#new-race-date-input").val()).getTime() / 1000;
                let race_time_aux = $("#new-race-time-input").val().split(":");
                let race_time = race_time_aux[0] * 60 * 60 + race_time_aux[1] * 60;
                let race_datetime = race_time + race_date - localTimeOffset * 60 * 60;

                let formData = {
                  race_name: race_name,
                  race_season: race_season,
                  race_track: race_track,
                  race_datetime: race_datetime,
                  race_id: race_id
                };

                $.ajax({
                  url: 'edit_race.php',
                  type: 'POST',
                  dataType: 'json',
                  data: formData
                })
                .done(function(data) {
                  if(data.success){
                    console.log("success");
                  retrieveSeasons();
                  //retrieveRaces(season_id, panel_element);
                  } else {
                    console.log("data error");
                  }

                })
          .fail(function(xhr, status, error) {
            console.log("error");
			console.log(xhr.responseText);
                });


              });

              $("#cancel-race").click(function(){
                $("#create-new-season").show();
                $("#create-new-race").show();
                let elem = document.querySelector("#new-race-season-container div");
                elem.parentNode.removeChild(elem);
              });
            }
          }


        } else {
          retrieveStandingsData(race_id, 1);
        }

      });
    }

  })
  .fail(function() {

    });


}

function retrieveSeasons(){

    var competition_id = $("#select-driver").val();

  $.ajax({
    url: 'retrieve_seasons.php',
    type: 'POST',
    dataType: 'json',
    data: {competition_id: competition_id}
  })
  .done(function(data) {

    cached_data = data;

    //var str = JSON.stringify(data, null, 2); // spacing level = 2
    var seasons_html = "";
    var driver_owner = parseInt($("#select-driver option:selected").attr("data-owner"));
    if(verifyLoggedUser(driver_owner)){
      seasons_html += "<div id='new-race-season-container'><button id='create-new-season'>Nova temporada</button><button id='create-new-race'>Nova corrida</button></div>";
    }



    data.seasons_data.forEach(function(element) {
      seasons_html += "<button class='accordion' data-season='"+ element.id +"'> Temporada " +element.year + "</button>";
      seasons_html += "<div class='panel'></div>";
    });


    $("#container-competition-seasons").html(seasons_html);

    if(verifyLoggedUser(driver_owner)){

      $("#create-new-season").on("click", function(){
        let container = document.getElementById("new-race-season-container");
        $("#create-new-season").hide();
        $("#create-new-race").hide();
        let form_div = document.createElement("div");
        let year_input = document.createElement("input");
        year_input.setAttribute("id", "new-season-year-input");
        year_input.setAttribute("type", "number");
        year_input.setAttribute("min", "2006");
        year_input.setAttribute("max", "2030");
        year_input.setAttribute("required", "required");
        let label = document.createElement("label");
        label.textContent = "Ano";
        let acc_button = document.createElement("button");
        acc_button.setAttribute("id", "create-season");
        let rej_button = document.createElement("button");
        rej_button.setAttribute("id", "cancel-season");
        acc_button.textContent = "Aceitar";
        rej_button.textContent = "Cancelar";
        form_div.appendChild(label);
        form_div.appendChild(year_input);
        form_div.appendChild(acc_button);
        form_div.appendChild(rej_button);
        container.appendChild(form_div);

        $("#create-season").click(function(){

          let new_season = $("#new-season-year-input").val();

        //  console.log(new_season);

          if(new_season > 2030 || new_season < 2006 || new_season == ""){
            $("#new-season-year-input").addClass(" wrong-input ");
            return;
          }

          for(let season of data.seasons_data){
            if(new_season == season.year){
              $("#new-season-year-input").addClass(" wrong-input ");
              return;
            }
          }

          $("#new-season-year-input").addClass(" correct-input ");
          $.ajax({
            url: 'create_new_season.php',
            type: 'POST',
            dataType: 'json',
            data: {new_season: new_season,
                   competition_id: competition_id}
          })
          .done(function(data) {
            if(data.success){
              console.log("success");
              retrieveSeasons();
            } else {
              console.log("data error");
            }

          })
          .fail(function(xhr, status, error) {
            console.log("error");
			console.log(xhr.responseText);
          });


        });

        $("#cancel-season").click(function(){
          $("#create-new-season").show();
          $("#create-new-race").show();
          let elem = document.querySelector("#new-race-season-container div");
          elem.parentNode.removeChild(elem);
        });
      });

      $("#create-new-race").on("click", function(){
        createRaceInputs(data);

        $("#create-race").click(function(){

          let localTimeOffset = -new Date().getTimezoneOffset()/60;
          let race_name = $("#new-race-name-input").val();
          let race_season = $("#new-race-season-input").val();
          let race_track = $("#new-race-track-input").val();
          let race_date = new Date($("#new-race-date-input").val()).getTime() / 1000;
          let race_time_aux = $("#new-race-time-input").val().split(":");
          let race_time = race_time_aux[0] * 60 * 60 + race_time_aux[1] * 60;
          let race_datetime = race_time + race_date - localTimeOffset * 60 * 60;

          let formData = {
            race_name: race_name,
            race_season: race_season,
            race_track: race_track,
            race_datetime: race_datetime
          };

          $.ajax({
            url: 'create_new_race.php',
            type: 'POST',
            dataType: 'json',
            data: formData
          })
          .done(function(data) {
            if(data.success){
              console.log("success");
              retrieveSeasons();
            } else {
              console.log("data error");
            }

          })
          .fail(function(xhr, status, error) {
            console.log("error");
			console.log(xhr.responseText);
          });


        });

        $("#cancel-race").click(function(){
          $("#create-new-season").show();
          $("#create-new-race").show();
          let elem = document.querySelector("#new-race-season-container div");
          elem.parentNode.removeChild(elem);
        });
      });
    }

    $('#container-competition-seasons button:first-child').addClass('active');

    var acc = document.getElementsByClassName("accordion");
    var i;

    for (i = 0; i < acc.length; i++) {
      acc[i].addEventListener("click", function() {
        var season_id = this.getAttribute("data-season");

        var classname = document.getElementsByClassName("active");
        // var is_active = false;
        //
        // if (this.classList.contains('active')) {
        //   is_active = true;
        // }

        Array.from(classname).forEach(function(element) {
            element.classList.remove("active");
         });

         // if(!is_active){
           this.classList.toggle("active");

           retrieveStandingsData(season_id, 0);
         // }
         var panelClass = document.getElementsByClassName("panel");

         Array.from(panelClass).forEach(function(element) {
             element.style.display = "none";
          });

        var panel = this.nextElementSibling;

        retrieveRaces(season_id, panel);

        if (panel.style.display === "block"){
          panel.style.display = "none";
        } else {
          panel.style.display = "block";
        }
      });
    }

  })
  .fail(function() {

    });
}

function readURL(input, target_div) {
       if (input.files && input.files[0]) {
           var reader = new FileReader();

           reader.onload = function (e) {
               $('#competition-'+target_div + "-preview")
                   .attr('src', e.target.result).removeClass("hidden");
              $('#competition-' + target_div + '-text').addClass("hidden");
              $('label[for="competition-'+target_div+'"]').addClass("no-padding");
                   // .width(200)
                   // .height(200);
           };

           reader.readAsDataURL(input.files[0]);
       }
   }

function display_competition(updateEditor){
  var driver_owner = parseInt($("#select-driver option:selected").attr("data-owner"));
  if(!verifyLoggedUser(driver_owner)){
    $("#edit-driver").hide().attr("disabled", "disabled");
  } else {
    $("#edit-driver").show().removeAttr("disabled");
  }
  var id = $("#select-driver").val();

  $.ajax({
    url: 'competition_info_request.php',
    type: 'POST',
    dataType: 'json',
    data: {id: id}
  })
  .done(function(data) {
    point_system = data.competition_data.point_system;
    extra_points = data.competition_data.extra_points;
    country = data.competition_data.country_name;
    if(data.competition_data.logo){
      logo = "/octamotor/images/competition/" + data.competition_data.logo;
    } else {
      logo = "/octamotor/images/competition/default-logo.png";
    }
    name =  data.competition_data.name;
    country_id = data.competition_data.country_id;
    first_year = data.competition_data.first_year;
    total_races = data.competition_data.total_races;
    total_seasons = data.competition_data.total_seasons;
    about = data.competition_data.about;
    max_drivers = data.competition_data.max_drivers;
    quali_type = data.competition_data.qualifying_style;
    speed_factor = data.competition_data.speed_factor;
    pace_factor = data.competition_data.pace_factor;
    technique_factor = data.competition_data.technique_factor;
    random_factor = data.competition_data.random_factor;
    aggressiveness_factor = data.competition_data.aggressiveness_factor;
    rain_factor = data.competition_data.rain_skills_factor;
    start_factor = data.competition_data.start_factor;
    event_factor = data.competition_data.event_factor;
    position_factor = data.competition_data.position_factor;
    quali_prop_factor = data.competition_data.quali_prop_factor;
    race_prop_factor = data.competition_data.race_prop_factor;
    max_time = data.competition_data.max_time;
    total_length = data.competition_data.total_length;
    car_factor = data.competition_data.car_factor;
	competition_type = data.competition_data.competition_type;

    if(country_id != 0){
      $("#competition-country-info").html(country);
    } else {
      $("#competition-country-info").html("Internacional");
    }

    $("#competition-logo-display").attr("src", logo);
    $("#competition-about-info").html(about);
    $("#competition-total-seasons-info").html(total_seasons);
    $("#competition-total-races-info").html(total_races);
    $("#competition-first-year-info").html(first_year);


    if(updateEditor){
      populate_editor(false);
    }
  })
          .fail(function(xhr, status, error) {
            console.log("error");
			console.log(xhr.responseText);
  });

}


function populate_editor(changeBar){
  if(changeBar){
    $("#driver-name-bar").html("Editar competição").removeClass("request_failure").removeClass("request_success");
  }
  $("#competition-id").html($("#select-driver").val());
  $("#competition-name").val(name);
  $("#competition-country").val(country_id);
  $("#competition-point-system").val(point_system);
  $("#competition-extra-points").val(extra_points);
  $("#competition-about").val(about);
  $("#competition-max-drivers").val(max_drivers);
  $("#competition-quali-style").val(quali_type);
  $("#competition-speed-factor").val(speed_factor);
  $("#competition-pace-factor").val(pace_factor);
  $("#competition-car-factor").val(car_factor);
  $("#competition-technique-factor").val(technique_factor);
  $("#competition-random-factor").val(random_factor);
  $("#competition-aggressiveness-factor").val(aggressiveness_factor);
  $("#competition-rain-factor").val(rain_factor);
  $("#competition-start-factor").val(start_factor);
  $("#competition-event-factor").val(event_factor);
  $("#competition-position-factor").val(position_factor);
  $("#competition-quali-prop-factor").val(quali_prop_factor);
  $("#competition-race-prop-factor").val(race_prop_factor);
  $("#competition-logo-preview").attr("src", logo).removeClass("hidden");
  $("#competition-logo-text").addClass("hidden");
  $("#competition-max-time").val(max_time);
  $("#competition-total-length").val(total_length);
  $("#competition-logo").val("");
  $("#competition-type").val(competition_type);
  $(".picture-label").addClass("no-padding");

}

function empty_editor(reloadBar){
  if(reloadBar){
    $("#driver-name-bar").html("Criar competição").removeClass("request_failure").removeClass("request_success");
  }
  $("#competition-id").html("");
  $("#competition-name").val("");
  $("#competition-country").val("");
  $("#competition-point-system").val("");
  $("#competition-extra-points").val("");
  $("#competition-about").val("");
  $("#competition-max-drivers").val("");
  $("#competition-quali-style").val("");
  $("#competition-speed-factor").val("");
  $("#competition-pace-factor").val("");
  $("#competition-technique-factor").val("");
  $("#competition-random-factor").val("");
  $("#competition-aggressiveness-factor").val("");
  $("#competition-rain-factor").val("");
  $("#competition-start-factor").val("");
  $("#competition-event-factor").val("");
  $("#competition-position-factor").val("");
  $("#competition-quali-prop-factor").val("");
  $("#competition-race-prop-factor").val("");
  $("#competition-max-time").val("");
  $("#competition-total-length").val("");
  $("#competition-logo-preview").attr("src", "").addClass("hidden");
  $("#competition-logo-text").removeClass("hidden");
  $("#competition-logo").val("");
  $(".picture-label").removeClass("no-padding");

}

$("#select-driver").change(function(){
  display_competition(false);
  $("#container-competition-standings").html("");
  retrieveSeasons();
});

function setLoggedUser(){

    Object.defineProperty(logged_user, 'user_id', {
        value: '<?php echo (isset($_SESSION['user_id'])? $_SESSION['user_id'] : "") ?>',
        writable : false,
        enumerable : true,
        configurable : false
    });

    Object.defineProperty(logged_user, 'admin_status', {
        value: '<?php echo (isset($_SESSION['admin_status'])? $_SESSION['admin_status'] : "") ?>',
        writable : false,
        enumerable : true,
        configurable : false
    });

}

function verifyLoggedUser(user){
  //console.log(user);
  if(logged_user.admin_status > 0){
    return true;
  } else if (logged_user.user_id == user){
    return true;
  } else {
    return false;
  }
}

setLoggedUser();


var $loading = $('#loadingDiv').hide();
$(document)
  .ajaxStart(function () {
    $loading.show();
  })
  .ajaxStop(function () {
    $loading.hide();
  });

$("document").ready(function(){
  display_competition(false);
  retrieveSeasons();

  $("#cancel-driver").on("click", function(){
    $("#driver-viewer").removeClass("hidden").addClass("visible");
    $("#driver-editor").removeClass("visible").addClass("hidden");
  });

  $("#create-new-driver").on("click", function(){
    $("#driver-editor").removeClass("hidden").addClass("visible");
    $("#driver-viewer").removeClass("visible").addClass("hidden");
    empty_editor(true);
  });

  $("#edit-driver").on("click", function(){
    var driver_owner = parseInt($("#select-driver option:selected").attr("data-owner"));
    if(verifyLoggedUser(driver_owner)){
      $("#driver-editor").removeClass("hidden").addClass("visible");
      $("#driver-viewer").removeClass("visible").addClass("hidden");
      populate_editor(true);
    }
  });

  $('#competition-logo').on('change',function(){
  if($(this).get(0).files.length > 0){ // only if a file is selected
    var fileSize = $(this).get(0).files[0].size;
    //console.log(fileSize);
    if(fileSize > 1024 * 2000){
      $("label[for='competition-logo']").addClass("invalid");
    } else {
      $("label[for='competition-logo']").removeClass("invalid");
    }
  }
});

  $("#save-driver").on("click", function(){

if($("#competition-id").html() != ""){
  var driver_owner = parseInt($("#select-driver option:selected").attr("data-owner"));
  if(!verifyLoggedUser(driver_owner)){
    return false;
  }
}


    if($("label").hasClass("invalid")){
      return false;
    }

    id = $("#competition-id").html();

    var formData = new FormData();

    var previous_logo = "default-logo.png";
    formData.append("name", $("#competition-name").val());
    formData.append("extra_points", $("#competition-extra-points").val());
    formData.append("point_system", $("#competition-point-system").val());
    formData.append("qualifying_style", $("#competition-quali-style").val());
    formData.append("car_factor", $("#competition-car-factor").val());
    formData.append("speed_factor", $("#competition-speed-factor").val());
    formData.append("technique_factor", $("#competition-technique-factor").val());
    formData.append("pace_factor", $("#competition-pace-factor").val());
    formData.append("random_factor", $("#competition-random-factor").val());
    formData.append("aggressiveness_factor", $("#competition-aggressiveness-factor").val());
    formData.append("rain_skills_factor", $("#competition-rain-factor").val());
    formData.append("start_skills_factor", $("#competition-start-factor").val());
    formData.append("quali_prop_factor", $("#competition-quali-prop-factor").val());
    formData.append("race_prop_factor", $("#competition-race-prop-factor").val());
    formData.append("position_factor", $("#competition-position-factor").val());
    formData.append("event_factor", $("#competition-event-factor").val());
    formData.append("position_factor", $("#competition-position-factor").val());
    formData.append("owner", "");
    formData.append("country_id", $("#competition-country").val());
    formData.append("max_drivers", $("#competition-max-drivers").val());
    formData.append("about", $("#competition-about").val());
    formData.append("max_time", $("#competition-max-time").val());
    formData.append("total_length", $("#competition-total-length").val());
	formData.append("competition_type" , $("#competition-type").val());

      if($("#competition-id").html() != ""){
        previous_logo = (logo.substr(logo.lastIndexOf("/") + 1));
      }


      // circuit
      var inputLogo = $("#competition-logo")[0];
      var loadLogo;

      if (inputLogo.files.length > 0) {
        loadLogo = inputLogo.files[0];
      } else {
         loadLogo = null;
      }

      formData.append("id", id);
      formData.append("previous_logo", previous_logo);
      if(loadLogo != null){
        formData.append("logo", loadLogo);
      }

      // for (var pair of formData.entries()) {
      //   console.log(pair[0]+ ', ' + pair[1]);
      // }
      // return false;
      $.ajax({
        url: 'modify_competition.php',
        type: 'POST',
        cache: false,
        contentType: false,
        processData: false,
        dataType: 'json',
        data: formData
      })
      .done(function(data) {
        if(data.success){
          $("#driver-name-bar").html(data.error_msg).addClass("request_success").removeClass("request_failure");

          if(data.new_car){
            empty_editor(false);
          } else {
            display_competition(true);
          }


        } else {
          $("#driver-name-bar").html(data.error_msg).addClass("request_failure").removeClass("request_success");

        }
      })
      .fail(function() {

        $("#driver-name-bar").html("Erro na solicitação, contacte o admin.").addClass("request_failure").removeClass("request_success");
      });

    //console.log(id);

  });



});

function createRaceInputs(data){
  let container = document.getElementById("new-race-season-container");
  $("#create-new-season").hide();
  $("#create-new-race").hide();
  let form_div = document.createElement("div");
  let season_input = document.createElement("select");
  season_input.setAttribute("id", "new-race-season-input");
  season_input.setAttribute("required", "required");
  for(let season of data.seasons_data){
    let option = document.createElement("option");
    option.setAttribute("value", season.id);
    option.textContent = season.year;
    season_input.appendChild(option.cloneNode(true));
  }
  let label_season = document.createElement("label");
  label_season.textContent = "Temporada";

  let track_input = document.createElement("select");
  track_input.setAttribute("id", "new-race-track-input");
  track_input.setAttribute("required", "required");
  for(let track of data.track_data){
    let option = document.createElement("option");
    option.setAttribute("value", track.id);
    option.textContent = track.name;
    track_input.appendChild(option.cloneNode(true));
  }
  let label_track = document.createElement("label");
  label_track.textContent = "Circuito";

  let date_input = document.createElement("input");
  date_input.setAttribute("type", "date");
  date_input.setAttribute("id", "new-race-date-input");
  date_input.setAttribute("required", "required");
  let label_date = document.createElement("label");
  label_date.textContent = "Data da corrida";

  let time_input = document.createElement("input");
  time_input.setAttribute("type", "time");
  time_input.setAttribute("id", "new-race-time-input");
  time_input.setAttribute("required", "required");
  let label_time = document.createElement("label");
  label_time.textContent = "Hora da largada";

  let name_input = document.createElement("input");
  name_input.setAttribute("type", "text");
  name_input.setAttribute("id", "new-race-name-input");
  name_input.setAttribute("required", "required");
  let label_name = document.createElement("label");
  label_name.textContent = "Nome do evento";

  let acc_button = document.createElement("button");
  acc_button.setAttribute("id", "create-race");
  let rej_button = document.createElement("button");
  rej_button.setAttribute("id", "cancel-race");
  acc_button.textContent = "Aceitar";
  rej_button.textContent = "Cancelar";
  form_div.appendChild(label_name);
  form_div.appendChild(name_input);
  form_div.appendChild(label_season);
  form_div.appendChild(season_input);
  form_div.appendChild(label_track);
  form_div.appendChild(track_input);
  form_div.appendChild(label_date);
  form_div.appendChild(date_input);
  form_div.appendChild(label_time);
  form_div.appendChild(time_input);
  form_div.appendChild(acc_button);
  form_div.appendChild(rej_button);
  container.appendChild(form_div);
}

</script>


<?php

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
