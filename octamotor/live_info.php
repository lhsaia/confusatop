<!DOCTYPE html>

<?php

session_start();

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "OctaMotor - Ao Vivo";
$css_filename = "race_live";
$css_login = 'login';
$aux_css = "driver_info";
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");
include_once($_SERVER['DOCUMENT_ROOT']."/octamotor/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/octamotor/classes/track.php");
include_once($_SERVER['DOCUMENT_ROOT']."/octamotor/classes/race.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");

$octa_database = new OctamotorDatabase();
$odb = $octa_database->getConnection();
$race = new Race($odb);

$pais = new Pais($db);

$race_for_banner = $race->getWeeklyRaces();
$foca_circuit = "";
$foca_two_circuit = "";
$foca_logo = "src ='/octamotor/images/competition/23-FOCA logo 27783.png'";
$foca_logo_two = "src ='/octamotor/images/competition/23-FOCA logo 27783.png'";
$countdown_container = "";
$countdown_container_two = "";
$race_info_container = "<span>Fora de temporada</span>";
$race_info_container_two = "<span>Fora de temporada</span>";
$foca_season_status = " class='out-of-season' ";
$foca_season_status_two = " class='out-of-season' ";
$race_start_time = 0;
$race_start_time_two = 0;
$clickable = " ";
$clickable_two = " ";
$inner_container_other = "";

foreach($race_for_banner as $single_race){
  if($single_race['competition_id'] == 1){
    $foca_circuit = "src='/octamotor/images/track/" . $single_race['image'] . "'";
    $countdown_container = "<div id='countdown-container'>";
      $countdown_container .= "<div id='day-hour-minute-box'>";
        $countdown_container .= "<div id='day-box' class='timing-box'>";
        $countdown_container .= "</div>";
        $countdown_container .= "<div id='hour-box' class='timing-box'>";
        $countdown_container .= "</div>";
        $countdown_container .= "<div id='minute-box' class='timing-box'>";
        $countdown_container .= "</div>";
      $countdown_container .= "</div>";
      $countdown_container .= "<div id='rolex-time'>";
        $countdown_container .= "<img id='rolex-face' src='/octamotor/images/face%20(2).png'/>";
        $countdown_container .= "<img id='rolex-minute' src='/octamotor/images/minute-hand.png'/>";
        $countdown_container .= "<img id='rolex-hour' src='/octamotor/images/hour-hand.png'/>";
        $countdown_container .= "<img id='rolex-second' src='/octamotor/images/second-hand.png'/>";
      $countdown_container .= "</div>";
    $countdown_container .= "</div>";
    $foca_season_status = "";
    $race_info_container = "<div>";
      $race_info_container .= "<span>";
        $race_info_container .= $single_race['country_name'] . " ";
      $race_info_container .= "</span>";
      $race_info_container .= "<span>";
        $race_info_container .= $single_race['year'];
      $race_info_container .= "</span>";
    $race_info_container .= "</div>";
    $race_start_time = $single_race['datetime'];
    if($single_race['file']){
      $clickable = " clickable-true ";
    }
    $foca_race_id = $single_race['file'];
    // break;
  } else if($single_race['competition_id'] == 2){
      $foca_two_circuit = "src='/octamotor/images/track/" . $single_race['image'] . "'";
      $countdown_container_two = "<div id='countdown-container-two'>";
        $countdown_container_two .= "<div id='day-hour-minute-box-two'>";
          $countdown_container_two .= "<div id='day-box-two' class='timing-box'>";
          $countdown_container_two .= "</div>";
          $countdown_container_two .= "<div id='hour-box-two' class='timing-box'>";
          $countdown_container_two .= "</div>";
          $countdown_container_two .= "<div id='minute-box-two' class='timing-box'>";
          $countdown_container_two .= "</div>";
        $countdown_container_two .= "</div>";
      $countdown_container_two .= "</div>";
      $foca_two_season_status = "";
      $race_info_container_two = "<div>";
        $race_info_container_two .= "<span>";
          $race_info_container_two .= $single_race['country_name'] . " ";
        $race_info_container_two .= "</span>";
        $race_info_container_two .= "<span>";
          $race_info_container_two .= $single_race['year'];
        $race_info_container_two .= "</span>";
      $race_info_container_two .= "</div>";
      $race_start_time_two = $single_race['datetime'];
      if($single_race['file']){
        $clickable_two = " clickable-true ";
      }
      $foca_race_id_two = $single_race['file'];
  } else {
    if($single_race['file']){
      $other_class = " clickable-true ";
    } else {
      $other_class = " ";
    }
    $inner_container_other .= "<div class='other-competition-card ".$other_class." '>";
      $inner_container_other .= "<div hidden class='other-race-id'>".$single_race['file']."</div>";
      $inner_container_other .= "<img src='/octamotor/images/competition/" . $single_race['logo'] . "'  class='other-competition-name'/>";
      $inner_container_other .= "<div class='other-competition-race-name-year'>";
      $inner_container_other .= "<span>" . $single_race['name']  ."</span>";
      $inner_container_other .= "</div>";
        $inner_container_other .= "<img src='/octamotor/images/track/" . $single_race['image'] . "' class='other-competition-circuit'/>";
      $inner_container_other .= "<div class='other-competition-date-time'>";
        $inner_container_other .= "<span class='other-race-date-time'>" .  $single_race['datetime']  ."</span>";
      $inner_container_other .= "</div>";
    $inner_container_other .= "</div>";
  }

}

?>
<div id='loadingDiv'><img src='/octamotor/images/lights.gif'/></div>
<div id="container-home-octamotor">
  <!-- Track viewer start -->
  <div id="race-live-viewer" class="visible">
    <!-- <div class="container-driver-main"> -->

      <div id="container-foca" <?php echo $foca_season_status ?> class='<?php echo $clickable?>'>
        <div id="foca-race-id" hidden><?php echo $foca_race_id ?></div>
        <div id="foca-country-year-outer">
          <?php echo $race_info_container ?>
        </div>
        <div id="foca-countdown-outer">
          <?php echo $countdown_container ?>
        </div>
        <img id="foca-circuit-outer" <?php echo $foca_circuit ?> />
        <img id="foca-logo-live" <?php echo $foca_logo ?> />

      </div>
      <div id="container-foca-two" <?php echo $foca_season_status_two ?> class='<?php echo $clickable_two?>'>
        <div id="foca-two-race-id" hidden><?php echo $foca_race_id_two  ?></div>
        <div id="foca-two-country-year-outer">
          <?php echo $race_info_container_two ?>
        </div>
        <div id="foca-two-countdown-outer">
          <?php echo $countdown_container_two ?>
        </div>
        <img id="foca-two-circuit-outer" <?php echo $foca_two_circuit ?> />
        <img id="foca-two-logo-live" <?php echo $foca_logo_two ?> />

      </div>
      <div id="container-other-competitions">
        <?php echo ($inner_container_other != "" ? $inner_container_other : "<div class='others-empty'><span>Sem outras competições</span></div>") ?>
      </div>
    <!-- </div> -->
  </div>
    <!-- Competition viewer end -->
</div>

<script>

$("document").ready(function(){
	$("#toolbar").html("<a id='botaoPaginaTeste' href='race_live.php'>Página de teste</a>");
	
	var logged_user = {  };
	
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
  if(logged_user.admin_status > 0){
    return true;
  } else if (logged_user.user_id == user){
    return true;
  } else {
    return false;
  }
}

setLoggedUser();

console.log(logged_user);
console.log(verifyLoggedUser(-1));
	
	if(verifyLoggedUser(-1)){
		$("#toolbar").append("<a id='botaoPaginaStressTest' href='stress_test.php'>Stress test</a>");
	}
});

var race_start_time = <?php echo $race_start_time?>;
var race_start_time_two = <?php echo $race_start_time_two?>;
var update_flag = 1;

$(".other-race-date-time").each(function(){
  let timestamp = parseInt($(this).html());
  let timeOffset = new Date().getTimezoneOffset() * 60;
  let realTime = timestamp - timeOffset;

  let usedDate = new Date(((parseInt(realTime)))*1000);
  //
  // console.log(realTime);
  let date = usedDate.getDate();
  let month = usedDate.getMonth(); //Be careful! January is 0 not 1
  let year = usedDate.getFullYear();

  let hours = Math.floor((realTime % (60 * 60 * 24)) / ( 60 * 60));
  let minutes = Math.floor((realTime % (60 * 60)) / ( 60));

  $(this).html(date + "/" +(month + 1) + "/" + year + " " + hours.toString().padStart(2,"0") + ":" + minutes.toString().padStart(2,"0"));
});


if($("#foca-race-id").html() != ""){
  $("#container-foca").click(function(){
    window.location = "https://confusa.top/octamotor/race_live.php?file_name=" + $("#foca-race-id").html();
  });
}

if($("#foca-two-race-id").html() != ""){
  $("#container-foca-two").click(function(){
    window.location = "https://confusa.top/octamotor/race_live.php?file_name=" + $("#foca-two-race-id").html();
  });
}


  $(".other-competition-card").click(function(){
    if($(this).find(">:first-child").html() != ""){
      window.location = "https://confusa.top/octamotor/race_live.php?file_name=" + $(this).find(">:first-child").html();
    }
  });


function setTimer(functionName){
  if(update_flag == 1){
    updateInterval = setInterval(functionName, 1000);
  } else{
    if(updateInterval){
      clearInterval(updateInterval);
    }
  }
}

function updateCountdownTwo(){
  let now_time = new Date().getTime() / 1000;

  let time_offset = (race_start_time_two) - now_time;

  let days = Math.floor(time_offset / (60 * 60 * 24));
  let hours = Math.floor((time_offset % (60 * 60 * 24)) / ( 60 * 60));
  let minutes = Math.floor((time_offset % (60 * 60)) / ( 60));

  if(minutes < 0){
    minutes = 0;
  }
  if(hours < 0){
    hours = 0;
  }
  if(days < 0){
    days = 0;
  }


  $("#day-box-two").html(days);
  $("#hour-box-two").html(hours);
  $("#minute-box-two").html(minutes);


}

function updateCountdown(){

  var now_time = new Date().getTime() / 1000;

  var time_offset = (race_start_time) - now_time;

  var days = Math.floor(time_offset / (60 * 60 * 24));
  var hours = Math.floor((time_offset % (60 * 60 * 24)) / ( 60 * 60));
  var minutes = Math.floor((time_offset % (60 * 60)) / ( 60));

  if(minutes < 0){
    minutes = 0;
  }
  if(hours < 0){
    hours = 0;
  }
  if(days < 0){
    days = 0;
  }


  $("#day-box").html(days);
  $("#hour-box").html(hours);
  $("#minute-box").html(minutes);

  var current_hour = Math.floor((now_time % (60 * 60 * 24)) / ( 60 * 60));
  var current_minutes = Math.floor((now_time % (60 * 60)) / ( 60));
  var current_seconds = Math.floor(now_time % 60);

  var localTimeOffset = -new Date().getTimezoneOffset()/60;
  current_hour = current_hour + localTimeOffset;


  //console.log(current_hour);

  $("#rolex-hour").css('transform', 'rotateZ(' + (current_hour/12)*360 + 'deg)');
  $("#rolex-minute").css('transform', 'rotateZ(' + (current_minutes/60)*360 + 'deg)');
  $("#rolex-second").css('transform', 'rotateZ(' + (current_seconds*6) + 'deg)');

}

setTimer(updateCountdown);
setTimer(updateCountdownTwo);


// var id;
// var country;
// var logo;
// var name;
// var country_id;
// var first_race;
// var about;
// var max_drivers;
// var quali_type;
// var speed_factor;
// var pace_factor;
// var technique_factor;
// var random_factor;
// var aggressiveness_factor;
// var rain_factor;
// var start_factor;
// var event_factor;
// var position_factor;
// var quali_prop_factor;
// var race_prop_factor;
// var max_time;
// var total_length;
//
// function retrieveStandingsData(event_id, event_type){
//   $.ajax({
//     url: 'retrieve_standings.php',
//     type: 'POST',
//     dataType: 'json',
//     data: {event_id: event_id,
//           event_type: event_type}
//   })
//   .done(function(data) {
//
//      var standings_data_html = "";
//
//      //standings_data_html = "<h1>"+data.race+"</h1>";
//
//      standings_data_html += "<table id='standings-table'>";
//       standings_data_html += "<thead>";
//       standings_data_html += "<tr>";
//       standings_data_html += "<th></th>";
//       standings_data_html += "<th>Driver</th>";
//       standings_data_html += "<th>Team</th>";
//       standings_data_html += "<th>Pts</th>";
//
//       standings_data_html += "</tr>"
//       standings_data_html += "</thead>";
//       standings_data_html += "<tbody>";
//
//       var counter = 1;
//     data.standings_data.forEach(function(element) {
//       standings_data_html += "<tr data-position='"+counter+"'><td class='driver-position'>"+counter+"</td><td class='driver-name driver-team-names driver-text'>" +element.name + "</td><td class='driver-team-names driver-text'>"+element.team_name+"</td><td class='driver-team-names driver-text'>"+element.total_points+"</td></tr>";
//       counter++;
//     });
//
//   //  console.log(standings_data_html);
//
//     $("#container-competition-standings").html(standings_data_html);
//
//   })
//   .fail(function() {
//
//     });
//
// }
//
// function retrieveRaces(season_id, panel_element){
//
//   $.ajax({
//     url: 'retrieve_races.php',
//     type: 'POST',
//     dataType: 'json',
//     data: {season_id: season_id}
//   })
//   .done(function(data) {
//
//     //var str = JSON.stringify(data, null, 2); // spacing level = 2
//     var races_html = "";
//
//     data.races_data.forEach(function(element) {
//       if(!element.name){
//         element.name = "Grande Prêmio de " + element.country_name;
//       }
//       races_html += "<button class='accordion-race' data-race='"+ element.id +"'><img class='flag-thumb' src='/images/bandeiras/"+element.flag+"'/> " +element.name + " </button>";;
//     });
//
//     panel_element.innerHTML = races_html;
//
//     var acc = document.getElementsByClassName("accordion-race");
//     var i;
//
//     for (i = 0; i < acc.length; i++) {
//       acc[i].addEventListener("click", function() {
//         var race_id = this.getAttribute("data-race");
//         var classname = document.getElementsByClassName("active-race");
//
//         Array.from(classname).forEach(function(element) {
//             element.classList.remove("active-race");
//          });
//            this.classList.toggle("active-race");
//
//            retrieveStandingsData(race_id, 1);
//       });
//     }
//
//   })
//   .fail(function() {
//
//     });
//
//
// }
//
// function retrieveSeasons(){
//
//     var competition_id = $("#select-driver").val();
//
//   $.ajax({
//     url: 'retrieve_seasons.php',
//     type: 'POST',
//     dataType: 'json',
//     data: {competition_id: competition_id}
//   })
//   .done(function(data) {
//
//     //var str = JSON.stringify(data, null, 2); // spacing level = 2
//     var seasons_html = "";
//
//     data.seasons_data.forEach(function(element) {
//       seasons_html += "<button class='accordion' data-season='"+ element.id +"'> Temporada " +element.year + "</button>";
//       seasons_html += "<div class='panel'></div>";
//     });
//
//
//     $("#container-competition-seasons").html(seasons_html);
//
//     $('#container-competition-seasons button:first-child').addClass('active');
//
//     var acc = document.getElementsByClassName("accordion");
//     var i;
//
//     for (i = 0; i < acc.length; i++) {
//       acc[i].addEventListener("click", function() {
//         var season_id = this.getAttribute("data-season");
//
//         var classname = document.getElementsByClassName("active");
//         // var is_active = false;
//         //
//         // if (this.classList.contains('active')) {
//         //   is_active = true;
//         // }
//
//         Array.from(classname).forEach(function(element) {
//             element.classList.remove("active");
//          });
//
//          // if(!is_active){
//            this.classList.toggle("active");
//
//            retrieveStandingsData(season_id, 0);
//          // }
//          var panelClass = document.getElementsByClassName("panel");
//
//          Array.from(panelClass).forEach(function(element) {
//              element.style.display = "none";
//           });
//
//         var panel = this.nextElementSibling;
//
//         retrieveRaces(season_id, panel);
//
//         if (panel.style.display === "block"){
//           panel.style.display = "none";
//         } else {
//           panel.style.display = "block";
//         }
//       });
//     }
//
//   })
//   .fail(function() {
//
//     });
// }
//
// function readURL(input, target_div) {
//        if (input.files && input.files[0]) {
//            var reader = new FileReader();
//
//            reader.onload = function (e) {
//                $('#competition-'+target_div + "-preview")
//                    .attr('src', e.target.result).removeClass("hidden");
//               $('#competition-' + target_div + '-text').addClass("hidden");
//               $('label[for="competition-'+target_div+'"]').addClass("no-padding");
//                    // .width(200)
//                    // .height(200);
//            };
//
//            reader.readAsDataURL(input.files[0]);
//        }
//    }
//
// function display_competition(updateEditor){
//   var driver_owner = parseInt($("#select-driver option:selected").attr("data-owner"));
//   if(!verifyLoggedUser(driver_owner)){
//     $("#edit-driver").hide().attr("disabled", "disabled");
//   } else {
//     $("#edit-driver").show().removeAttr("disabled");
//   }
//   var id = $("#select-driver").val();
//
//   $.ajax({
//     url: 'competition_info_request.php',
//     type: 'POST',
//     dataType: 'json',
//     data: {id: id}
//   })
//   .done(function(data) {
//     country = data.competition_data.country_name;
//     if(data.competition_data.logo){
//       logo = "/octamotor/images/competition/" + data.competition_data.logo;
//     } else {
//       logo = "/octamotor/images/competition/default-logo.png";
//     }
//     name =  data.competition_data.name;
//     country_id = data.competition_data.country_id;
//     first_year = data.competition_data.first_year;
//     total_races = data.competition_data.total_races;
//     total_seasons = data.competition_data.total_seasons;
//     about = data.competition_data.about;
//     max_drivers = data.competition_data.max_drivers;
//     quali_type = data.competition_data.qualifying_style;
//     speed_factor = data.competition_data.speed_factor;
//     pace_factor = data.competition_data.pace_factor;
//     technique_factor = data.competition_data.technique_factor;
//     random_factor = data.competition_data.random_factor;
//     aggressiveness_factor = data.competition_data.aggressiveness_factor;
//     rain_factor = data.competition_data.rain_factor;
//     start_factor = data.competition_data.start_factor;
//     event_factor = data.competition_data.event_factor;
//     position_factor = data.competition_data.position_factor;
//     quali_prop_factor = data.competition_data.quali_prop_factor;
//     race_prop_factor = data.competition_data.race_prop_factor;
//     max_time = data.competition_data.max_time;
//     total_length = data.competition_data.total_length;
//
//     if(country_id != 0){
//       $("#competition-country-info").html(country);
//     } else {
//       $("#competition-country-info").html("Internacional");
//     }
//
//     $("#competition-logo-display").attr("src", logo);
//     $("#competition-about-info").html(about);
//     $("#competition-total-seasons-info").html(total_seasons);
//     $("#competition-total-races-info").html(total_races);
//     $("#competition-first-year-info").html(first_year);
//
//
//     if(updateEditor){
//       populate_editor(false);
//     }
//   })
//   .fail(function() {
//     console.log("error");
//   });
//
// }
//
//
// function populate_editor(changeBar){
//   if(changeBar){
//     $("#driver-name-bar").html("Editar competição").removeClass("request_failure").removeClass("request_success");
//   }
//   $("#competition-id").html($("#select-driver").val());
//   $("#competition-name").val(name);
//   $("#competition-country").val(country_id);
//   $("#competition-about").val(about);
//   $("#competition-max-drivers").val(max_drivers);
//   $("#competition-quali-style").val(quali_type);
//   $("#competition-speed-factor").val(speed_factor);
//   $("#competition-pace-factor").val(pace_factor);
//   $("#competition-technique-factor").val(technique_factor);
//   $("#competition-random-factor").val(random_factor);
//   $("#competition-aggressiveness-factor").val(aggressiveness_factor);
//   $("#competition-rain-factor").val(rain_factor);
//   $("#competition-start-factor").val(start_factor);
//   $("#competition-event-factor").val(event_factor);
//   $("#competition-position-factor").val(position_factor);
//   $("#competition-quali-prop-factor").val(quali_prop_factor);
//   $("#competition-race-prop-factor").val(race_prop_factor);
//   $("#competition-logo-preview").attr("src", logo).removeClass("hidden");
//   $("#competition-logo-text").addClass("hidden");
//   $("#competition-max-time").val(max_time);
//   $("#competition-total-length").val(total_length);
//   $(".picture-label").addClass("no-padding");
//
// }
//
// function empty_editor(reloadBar){
//   if(reloadBar){
//     $("#driver-name-bar").html("Criar competição").removeClass("request_failure").removeClass("request_success");
//   }
//   $("#competition-id").html("");
//   $("#competition-name").val("");
//   $("#competition-country").val("");
//   $("#competition-about").val("");
//   $("#competition-max-drivers").val("");
//   $("#competition-quali-style").val("");
//   $("#competition-speed-factor").val("");
//   $("#competition-pace-factor").val("");
//   $("#competition-technique-factor").val("");
//   $("#competition-random-factor").val("");
//   $("#competition-aggressiveness-factor").val("");
//   $("#competition-rain-factor").val("");
//   $("#competition-start-factor").val("");
//   $("#competition-event-factor").val("");
//   $("#competition-position-factor").val("");
//   $("#competition-quali-prop-factor").val("");
//   $("#competition-race-prop-factor").val("");
//   $("#competition-max-time").val("");
//   $("#competition-total-length").val("");
//   $("#competition-logo-preview").attr("src", "").addClass("hidden");
//   $("#competition-logo-text").removeClass("hidden");
//   $(".picture-label").removeClass("no-padding");
//
// }
//
// $("#select-driver").change(function(){
//   display_competition(false);
//   $("#container-competition-standings").html("");
//   retrieveSeasons();
// });
//

//
//
 var $loading = $('#loadingDiv').hide();
// $(document)
//   .ajaxStart(function () {
//     $loading.show();
//   })
//   .ajaxStop(function () {
//     $loading.hide();
//   });
//
// $("document").ready(function(){
//   display_competition(false);
//   retrieveSeasons();
//
//   $("#cancel-driver").on("click", function(){
//     $("#driver-viewer").removeClass("hidden").addClass("visible");
//     $("#driver-editor").removeClass("visible").addClass("hidden");
//   });
//
//   $("#create-new-driver").on("click", function(){
//     $("#driver-editor").removeClass("hidden").addClass("visible");
//     $("#driver-viewer").removeClass("visible").addClass("hidden");
//     empty_editor(true);
//   });
//
//   $("#edit-driver").on("click", function(){
//     var driver_owner = parseInt($("#select-driver option:selected").attr("data-owner"));
//     if(verifyLoggedUser(driver_owner)){
//       $("#driver-editor").removeClass("hidden").addClass("visible");
//       $("#driver-viewer").removeClass("visible").addClass("hidden");
//       populate_editor(true);
//     }
//   });
//
//   $('#competition-logo').on('change',function(){
//   if($(this).get(0).files.length > 0){ // only if a file is selected
//     var fileSize = $(this).get(0).files[0].size;
//     //console.log(fileSize);
//     if(fileSize > 1024 * 2000){
//       $("label[for='competition-logo']").addClass("invalid");
//     } else {
//       $("label[for='competition-logo']").removeClass("invalid");
//     }
//   }
// });
//
//   $("#save-driver").on("click", function(){
//
// if($("#competition-id").html() != ""){
//   var driver_owner = parseInt($("#select-driver option:selected").attr("data-owner"));
//   if(!verifyLoggedUser(driver_owner)){
//     return false;
//   }
// }
//
//
//     if($("label").hasClass("invalid")){
//       return false;
//     }
//
//     id = $("#competition-id").html();
//
//     var formData = new FormData();
//
//     var previous_logo = "default-logo.png";
//     formData.append("name", $("#competition-name").val());
//     formData.append("qualifying_style", $("#competition-quali-style").val());
//     formData.append("car_factor", $("#competition-car-factor").val());
//     formData.append("speed_factor", $("#competition-speed-factor").val());
//     formData.append("technique_factor", $("#competition-technique-factor").val());
//     formData.append("pace_factor", $("#competition-pace-factor").val());
//     formData.append("random_factor", $("#competition-random-factor").val());
//     formData.append("aggressiveness_factor", $("#competition-aggressiveness-factor").val());
//     formData.append("rain_skills_factor", $("#competition-rain-factor").val());
//     formData.append("start_skills_factor", $("#competition-start-factor").val());
//     formData.append("quali_prop_factor", $("#competition-quali-prop-factor").val());
//     formData.append("race_prop_factor", $("#competition-race-prop-factor").val());
//     formData.append("position_factor", $("#competition-position-factor").val());
//     formData.append("event_factor", $("#competition-event-factor").val());
//     formData.append("position_factor", $("#competition-position-factor").val());
//     formData.append("owner", "");
//     formData.append("country_id", $("#competition-country").val());
//     formData.append("max_drivers", $("#competition-max-drivers").val());
//     formData.append("about", $("#competition-about").val());
//     formData.append("max_time", $("#competition-max-time").val());
//     formData.append("total_length", $("#competition-total-length").val());
//
//       if($("#competition-id").html() != ""){
//         previous_logo = (logo.substr(logo.lastIndexOf("/") + 1));
//       }
//
//
//       // circuit
//       var inputLogo = $("#competition-logo")[0];
//       var loadLogo;
//
//       if (inputLogo.files.length > 0) {
//         loadLogo = inputLogo.files[0];
//       } else {
//          loadLogo = null;
//       }
//
//       formData.append("id", id);
//       formData.append("previous_logo", previous_logo);
//       if(loadLogo != null){
//         formData.append("logo", loadLogo);
//       }
//
//       // for (var pair of formData.entries()) {
//       //   console.log(pair[0]+ ', ' + pair[1]);
//       // }
//       // return false;
//       $.ajax({
//         url: 'modify_competition.php',
//         type: 'POST',
//         cache: false,
//         contentType: false,
//         processData: false,
//         dataType: 'json',
//         data: formData
//       })
//       .done(function(data) {
//         if(data.success){
//           $("#driver-name-bar").html(data.error_msg).addClass("request_success").removeClass("request_failure");
//
//           if(data.new_car){
//             empty_editor(false);
//           } else {
//             display_competition(true);
//           }
//
//
//         } else {
//           $("#driver-name-bar").html(data.error_msg).addClass("request_failure").removeClass("request_success");
//
//         }
//       })
//       .fail(function() {
//
//         $("#driver-name-bar").html("Erro na solicitação, contacte o admin.").addClass("request_failure").removeClass("request_success");
//       });
//
//     //console.log(id);
//
//   });
//
//
//
//
// });

</script>


<?php

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
