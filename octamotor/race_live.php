<!DOCTYPE html>

<?php

session_start();

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "OctaMotor - Ao Vivo";
$css_filename = "race_live";
$css_login = 'login';
//$aux_css = "newindex";
$aux_css = "driver_info";
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");
include_once($_SERVER['DOCUMENT_ROOT']."/octamotor/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/octamotor/classes/driver.php");
include_once($_SERVER['DOCUMENT_ROOT']."/octamotor/classes/track.php");
include_once($_SERVER['DOCUMENT_ROOT']."/octamotor/classes/competition.php");


// drivers selection to display
$octa_database = new OctamotorDatabase();
$odb = $octa_database->getConnection();
$driver = new Driver($odb);
$track = new Track($odb);
$competition = new Competition($odb);
$driver_list = $driver->getDriversList();
$competition_list = $competition->getCompetitionList();
$track_list = $track->getTracksList();

?>

<div id="container-home-octamotor">
  <div id="container-live-main">
    <div id="container-live-aux">
      <div id="container-race-info">
        <div id="race-name-bar">
          <img id='race-flag'/><span id='race-name'></span>
        </div>
        <div id="race-boxes">
        <div id='race-timing-info'>
          <span id='track-name'></span>
        <span id='event-name'></span>
        <div id="race-timing-box">

        </div>
      </div>
        <div id="race-technical-box">

          <div id="race-weather-bar">
             <span id='weather-name'></span>
          </div>
          <div id="race-flag-box">

          </div>
        </div>
      </div>
      </div>
      <div id="container-race-narration">
      </div>
      <hr/>
      <?php
      if(!isset($_GET['file_name'])){


       ?>
      <select id='circuit-selection'>
        <?php
        foreach($track_list as $single_track){
          echo "<option value={$single_track['id']}>{$single_track['name']}</option>";
        }
         ?>
      </select>
      <select id='competition-selection'>
        <?php
        foreach($competition_list as $single_competition){
          echo "<option value={$single_competition['id']}>{$single_competition['name']}</option>";
        }
         ?>
      </select>
      <input type="date" id="race-scheduled-date"
       name="race-scheduled-date">
      <input type="time" id="race-scheduled-time"
       name="race-scheduled-time">
      <button id='resim'>Re-simular</button>
    <?php } ?>
    </div>
    <div id="container-live-table">
    </div>
  </div>
</div>

<script>

  var localTimeOffset = -new Date().getTimezoneOffset()/60;
  var dt = new Date();
  var current_time = dt.getHours().toString().padStart(2,"0") + ":" + dt.getMinutes().toString().padStart(2,"0");
  var current_date = (dt.getFullYear()) + "-" + (dt.getMonth()+1).toString().padStart(2,"0") + "-" + (dt.getDate()).toString().padStart(2,"0");

$("document").ready(function(){

  var file_name = "<?php echo (isset($_GET['file_name']) ? $_GET['file_name'] : "")?>";
  var addedTime;
  var update_flag = 1;
  var current_flag = "NO";
	var raw_data = [];
	var bestLapPosition;
  var race_started = 0;

  $("#race-scheduled-time").val(current_time);
  $("#race-scheduled-date").val(current_date);

  $("#resim").click(function(){

    var competition = $("#competition-selection").val();
    var track = $("#circuit-selection").val();

    var date = new Date($("#race-scheduled-date").val()).getTime() / 1000;
    var aux_time = $("#race-scheduled-time").val().split(":");
    var time = aux_time[0] * 60 * 60 + aux_time[1] * 60;

    var unix_time = (date + time) - (localTimeOffset * 60 * 60);

    $.ajax({
    url: 'test_ground.php',
    type: 'POST',
    dataType: 'json',
    data: {competition: competition,
            track: track,
          unix_time: unix_time}
    })
    .done(function(data) {
      get_ajax_data();
      //console.log(data);
    })
          .fail(function(xhr, status, error) {
            console.log("error");
			console.log(xhr.responseText);
    });

  });

  get_ajax_data();
  setInterval( get_ajax_data, 30000 );

function get_ajax_data(){

  $.ajax({
  url: 'race_ajax.php',
  type: 'POST',
  dataType: 'json',
  data: {file_name: file_name}
  })
  .done(function(data) {
	  
	 // console.log(data);

    var stage_letter = data.current_step.substring(0,1);
    var stage_name = "";
    var stage_code = 0;
    if(stage_letter == "R"){
      stage_name = "Race";
      stage_code = 5;
      addLapCounter();
      $("#lap-time-counter").text("lap " + data.current_step.replace(/\D/g,'') + "/" + data.race_info.total_laps);
    } else if(stage_letter == "P"){
      stage_name = "Pre-qualifying";
      if((data.current_step).substr((data.current_step).length - 1) == "G"){
        stage_code = 2;
        stage_name = "Qualifying grid";
        createCountdown(0);
        drivers_excess = 0;
        race_start_time = data.race_info.base_timestamp;
        if(addedTime != -1){
          addedTime = -1;
          setTimer();
        }
      } else {
        stage_code = 1;
        drivers_excess = data.race_info.drivers_excess;
        race_start_time = data.race_info.base_timestamp;
        createCountdown(1);
        if(data.current_step.replace(/\D/g,'') <= 13){
          if(addedTime != 60){
            addedTime = 60;
            setTimer();
          }
        }
      }
    } else if(stage_letter == "Q"){
      if(data.current_step.substring(1,1) == "C"){
        stage_name = "Qualifying";
        stage_code = 30;
        addLapCounter();
        $("#lap-time-counter").text("lap " + data.current_step.replace(/\D/g,''));
      } else {
        drivers_excess = data.race_info.drivers_excess;
        race_start_time = data.race_info.base_timestamp;
        createCountdown(1);
        if(data.current_step.replace(/\D/g,'') <= 6){
          stage_name = "Qualifying - Q1";
          stage_code = 31;
          if(addedTime != 18){
            addedTime = 18;
            setTimer();
          }
        } else if(data.current_step.replace(/\D/g,'') <= 11){
          stage_name = "Qualifying - Q2";
          stage_code = 32;
          if(addedTime != 33){
            addedTime = 33;
            setTimer();
          }
        } else if(data.current_step.replace(/\D/g,'') <= 15){
          stage_name = "Qualifying - Q3";
          stage_code = 33;
          if(addedTime != 45){
            addedTime = 45;
            setTimer();
          }
        }
      }
    } else if(stage_letter == "G") {
      stage_name = "Starting grid";
      stage_code = 4;
      createCountdown(0);
      drivers_excess = 0;
      race_start_time = data.race_info.base_timestamp;
      if(addedTime != -1){
        addedTime = -1;
        setTimer();
      }

    } else {
      stage_name = "";
      createCountdown(0);
      drivers_excess = data.race_info.drivers_excess;
      race_start_time = data.race_info.base_timestamp;
      if(addedTime != 0){
        addedTime = 0;
        setTimer();
      }

    }

    //weather box
    if(stage_code > 0){
      rain_status = data.rain_status;
      airTemp = data.air_temp;
      roadTemp = data.track_temp;
      day_status = data.race_info.day_night;
      setWeather();
    } else {
      $("#weather-name").html("");
    }

    //flag box
    if(data.safety_car_status == 1){
      setFlag("SC");
    } else {
      if(current_flag == "SC"){
        setFlag("GF");
      } else {
        setFlag("NO");
      }
    }

    if(stage_code != 4){
      if(data.current_step == "R-0" && race_started == 0){
        setFlag("SL");
        startLights();
        setTimeout(function(){
          setFlag("NO");
          display_table(data)
        }, 8000);
        race_started = 1;
      } else {
        display_table(data);
      }
    } else {
      //display_table(data);
      race_started = 0;
      createStartingGrid(data);
    }

    $("#event-name").text(stage_name);
    if(stage_code == 5){
      if(data.current_step.replace(/\D/g,'') == data.race_info.total_laps){
        setFlag("CF");
      }
	  if(data.timestamp > (parseFloat(data.race_info.base_timestamp) + data.race_info.max_time)){
        setFlag("CF");
      }
    }

    $("#track-name").text(data.race_info.track_name);

    if(data.race_info.name == ""){
      $("#race-name").text(data.race_info.competition + " Grand Prix of " + data.race_info.country_name + " " + data.race_info.season);
    } else {
      $("#race-name").text(data.race_info.competition + " " + data.race_info.name + " " + data.race_info.season);
    }
    $("#race-flag").attr("src", "/images/bandeiras/" + data.race_info.country_flag);

    bestLap();
  })
          .fail(function(xhr, status, error) {
            console.log("error");
			console.log(xhr.responseText);
  });
}

function addLapCounter(){
  var elem = document.querySelector('#countdown-container');
  if(!!elem){
    elem.parentNode.removeChild(elem);
  }
  var elem2 = document.querySelector("#lap-time-counter");
  if(!elem2){
    var lap_time_counter = document.createElement("span");
    lap_time_counter.setAttribute("id", 'lap-time-counter');
    document.getElementById("race-timing-box").appendChild(lap_time_counter);
  }
  update_flag = 0;
}

function updateCountdown(){

  var now_time = new Date().getTime() / 1000;

if(addedTime == 0){
  if(drivers_excess > 0){
    var time_offset = (race_start_time-(48*60*60)) - now_time;
  } else {
    var time_offset = (race_start_time-(24*60*60)) - now_time;
  }
} else if(addedTime > 0){
  var time_offset = (race_start_time-(24*60*60))+ (addedTime*60) - now_time;
} else {
  var time_offset = (race_start_time) - now_time;
}


  // Time calculations for days, hours, minutes and seconds
  var days = Math.floor(time_offset / (60 * 60 * 24));
  //var complete_hours = (time_offset - days * 24 * 60 * 60)*60*60;
  var hours = Math.floor((time_offset % (60 * 60 * 24)) / ( 60 * 60));
  //var complete_minutes = (complete_hours - hours)*60;
  var minutes = Math.floor((time_offset % (60 * 60)) / ( 60));
  var seconds = Math.floor(time_offset % 60);

  if(minutes < 0){
    minutes = 0;
  }
  if(hours < 0){
    hours = 0;
  }
  if(days < 0){
    days = 0;
  }
  if(seconds < 0){
    seconds = 0;
  }

  $("#day-box").html(days);
  $("#hour-box").html(hours);
  $("#minute-box").html(minutes);
  $("#second-box").html(seconds);

  // if(time_offset < 0){
  //   get_ajax_data();
  // }

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

function bestLap(){
  	$("#drivers-table tbody tr").each(function(){
  		var positionValue = $(this).find("td:first").text();
  		positionValue = parseInt(positionValue);
  		if(positionValue == bestLapPosition){
  			$(this).find("td:nth-child(8)").addClass("best-lap-overall");
  		}
  });
}


function display_table(raw_data){

  var elem = document.querySelector("#grid-container");

  if(!!elem){
    elem.parentNode.removeChild(elem);
  }

	var tbl = "<table id='drivers-table'>";
		tbl += "<thead>";
		tbl += "<tr>";
		tbl += "<th></th>";
		tbl += "<th class='driver-headers headers-left'>Driver</th>";
		tbl += "<th></th>";
		tbl += "<th class='driver-headers headers-left'>Team</th>";
    if(raw_data.current_step.substring(0,1) == "R"){
      tbl += "<th class='driver-headers headers-right' >Lap</th>";
      tbl += "<th class='driver-headers headers-right' >Pit</th>";
      tbl += "<th class='driver-headers headers-right' >Gap</th>";
    } else {
      tbl += "<th class='driver-headers headers-right' ></th>";
    }

    if(raw_data.current_step.substring(0,1) == "R"){
      tbl += "<th class='driver-headers headers-right' >Best</th>";
    }
		tbl += "<th class='driver-headers headers-right' >Last</th>";
		tbl += "<th class='driver-headers headers-right' >Tire</th>";
		tbl += "</tr>"
		tbl += "</thead>";
		tbl += "<tbody>";

    //console.log(raw_data);


		var overallBestLap = 0;
    var position = 0;

    Object.values(raw_data.total_data).forEach(function(row){
        position++;
          var outStatus = "OUT".localeCompare(row.gap) ? "" : "out-of-race";
          var bestLap = (convertTimeView(row.best_lap) == convertTimeView(row.last_lap)) ? " best-lap-own " : "";

          if(overallBestLap == 0 || (overallBestLap > row.best_lap && row.best_lap > 0)){
            overallBestLap = row.best_lap;
            bestLapPosition = position;
          }

          if(raw_data.current_step.substring(0,1) == "R"){
            var outQualiStatus = "";
          } else if(raw_data.current_step.substring(0,1) == "P"){
              if(position > 28){
                var outQualiStatus = " out-quali ";
              } else {
                var outQualiStatus = "";
              }

            } else if(raw_data.current_step.substring(0,1) == "Q"){
              if(row.lap <=6){
                if(position > 15){
                  var outQualiStatus = " out-quali ";
                } else {
                  var outQualiStatus = "";
                }

              } else if(row.lap <=11){
                if(position > 10){
                  var outQualiStatus = " out-quali ";
                } else {
                  var outQualiStatus = "";
                }
              } else {
                var outQualiStatus = "";
              }
            } else {
              var outQualiStatus = "";
            }

            //console.log(row);
          tbl += "<tr>";
          tbl += "<td class='driver-position" + outQualiStatus + "'>" + position + "</td>";
          tbl += "<td class='driver-name driver-team-names driver-text'>"+ row.name +"</td>";
          tbl += "<td><img class='driver-flag' src='/images/bandeiras/"+ row.nationality +"' /></td>";
          if(row.team_tv_name == ""){
            tbl += "<td class='driver-team-names driver-text'>"+ treatTeamName(row.team) +"</td>";
          } else {
            tbl += "<td class='driver-team-names driver-text'>"+ treatTeamName(row.team_tv_name) +"</td>";
          }

          if(raw_data.current_step.substring(0,1) == "R"){
            tbl += "<td class='driver-other-numbers driver-text'>"+row.lap+"</td>";
            tbl += "<td class='driver-other-numbers driver-text'>"+row.pits+"</td>";
          }
          tbl += "<td class='driver-other-numbers driver-text "+ outStatus + "'>"+convertGapView(row.gap, position)+"</td>";
          if(raw_data.current_step.substring(0,1) == "R"){
            tbl += "<td class='driver-other-numbers driver-text'>"+convertTimeView(row.best_lap)+"</td>";
          }
          tbl += "<td class='driver-other-numbers driver-text "+ bestLap + " "+ outStatus + "'>"+convertTimeView(row.last_lap)+"</td>";
        tbl += "<td class='driver-other-numbers tire driver-text "+ tireColor(row.tire) + "'>"+ tireType(row.tire)+"</td>";
        //tbl += "<td class='driver-other-numbers driver-text'>"+convertTimeView(o_row[row].total_time)+"</td>";
          tbl += "</tr>";

    });

		tbl += "</tbody>";
		tbl += "</table>";

		$("#container-live-table").html(tbl);

}

function convertTimeView(time_in_seconds){

	if(isNaN(time_in_seconds)){
		return time_in_seconds;

	} else {
		var minutes = Math.floor(time_in_seconds / 60);
		var seconds = (time_in_seconds%60).toFixed(3);
		var compound = minutes + ":" + (seconds < 10 ? "0" : "") + seconds;

		return compound;

	}

}

function tireType(tire_code){
	if(tire_code == 1){
		return "m";
	} else if(tire_code == 2){
		return "s";
	} else if(tire_code == 3){
    return "w";
  }
}

function tireColor(tire_code){
	if(tire_code == 1){
		return " yellow-tire ";
	} else if(tire_code == 2) {
		return " red-tire ";
	} else if(tire_code == 3){
    return " blue-tire ";
  }
}

function setFlag(flag_type){
  //console.log(flag_type);
  if(flag_type.localeCompare("SC") == 0){
    var flag_html = "<i class='far fa-flag flag-icon'></i><span class='flag-name'>  Safety Car </span>";
    var flag_class = "safety-car";
  }
  if(flag_type.localeCompare("CF") == 0){
    var flag_html = "<i class='fas fa-flag-checkered flag-icon'></i><span class='flag-name'> Chequered Flag </span>";
    var flag_class = "chequered-flag";
  }
  if(flag_type.localeCompare("GF") == 0){
    var flag_html = "<i class='far fa-flag flag-icon'></i><span class='flag-name'>  Race re-start </span>";
    var flag_class = "green-flag";
  }
  if(flag_type.localeCompare("SL") == 0){
    var flag_class = "starting-lights";
  }
  if(flag_type.localeCompare("NO") == 0) {
    var flag_html = "";
    var flag_class = "";
  }
  if(flag_type.localeCompare("SL") != 0){
    $("#race-flag-box").html(flag_html).attr( "class",  flag_class);
  } else {
    $("#race-flag-box").html("");
    $("#race-flag-box").addClass(flag_class);
    raceLights();
  }
  current_flag = flag_type;
}

function setWeather(){

  if(rain_status == 1 && day_status == 1){
    var forecast_modifier = "<i class='fas fa-cloud-showers-heavy'></i> Rain ";
  } else if(rain_status == 1 && day_status == 0){
    var forecast_modifier = "<i class='fas fa-cloud-moon-rain'></i> Rain ";
  } else if(rain_status == 0 && day_status == 1){
    var forecast_modifier = "<i class='fas fa-sun'></i> Clear ";
  } else if(rain_status == 0 && day_status == 0){
    var forecast_modifier = "<i class='fas fa-moon'></i> Clear ";
  }

  var temperature_modifier = "<i class='fas fa-road'></i> "+roadTemp+"&deg; <i class='fas fa-thermometer-half'></i> "+airTemp+"&deg; <i class='fas fa-wind'></i>";
  var weather = forecast_modifier + " <br/> " + temperature_modifier;
  $("#weather-name").html(weather);
}

function createCountdown(countdownType){
  var elem = document.querySelector("#lap-time-counter");
  if(!!elem){
    elem.parentNode.removeChild(elem);
  }
  var oppositeType = Math.abs(countdownType - 1);
  var elem0 = document.querySelector('div[data-counttype="'+oppositeType+'"]');
  if(!!elem0){
    elem0.parentNode.removeChild(elem0);
  }
  var elem2 = document.querySelector('div[data-counttype="'+countdownType+'"]');
  if(!elem2){
      var countdown_container = document.createElement("div");
      countdown_container.setAttribute("id", "countdown-container");
      var day_hour_minute_box = document.createElement("div");
      day_hour_minute_box.setAttribute("id", "day-hour-minute-box");
      var rolex_time = document.createElement("div");
      rolex_time.setAttribute("id", "rolex-time");
      var rolex_face = document.createElement("img");
      rolex_face.setAttribute("id", "rolex-face");
      rolex_face.setAttribute("src", "/octamotor/images/face%20(2).png");
      var rolex_minute = document.createElement("img");
      rolex_minute.setAttribute("id", "rolex-minute");
      rolex_minute.setAttribute("src", "/octamotor/images/minute-hand.png");
      var rolex_hour = document.createElement("img");
      rolex_hour.setAttribute("id", "rolex-hour");
      rolex_hour.setAttribute("src", "/octamotor/images/hour-hand.png");
      var rolex_second = document.createElement("img");
      rolex_second.setAttribute("id", "rolex-second");
      rolex_second.setAttribute("src", "/octamotor/images/second-hand.png");
      if(countdownType == 0){
        var hour_box = document.createElement("div");
        hour_box.setAttribute("id", "hour-box");
        hour_box.className += "timing-box";
        hour_box.setAttribute("data-tag", "HRS");
        var day_box = document.createElement("div");
        day_box.setAttribute("id", "day-box");
        day_box.className += "timing-box";
        day_box.setAttribute("data-tag", "DAYS");
        day_hour_minute_box.appendChild(day_box);
        day_hour_minute_box.appendChild(hour_box);
      }
      var minute_box = document.createElement("div");
      minute_box.setAttribute("id", "minute-box");
      minute_box.className += "timing-box";
      minute_box.setAttribute("data-tag", "MINS");
      day_hour_minute_box.appendChild(minute_box);
      if(countdownType == 1){
        var second_box = document.createElement("div");
        second_box.setAttribute("id", "second-box");
        second_box.className += "timing-box";
        second_box.setAttribute("data-tag", "SEC");
        day_hour_minute_box.appendChild(second_box);
      }

      countdown_container.appendChild(day_hour_minute_box);
      countdown_container.appendChild(rolex_time);
      rolex_time.appendChild(rolex_face);
      rolex_time.appendChild(rolex_minute);
      rolex_time.appendChild(rolex_hour);
      rolex_time.appendChild(rolex_second);
      document.getElementById("race-timing-box").appendChild(countdown_container);
      if(countdownType == 1){
        document.getElementById("countdown-container").setAttribute("data-counttype", "1");
      } else {
        document.getElementById("countdown-container").setAttribute("data-counttype", "0");
      }

  }
  update_flag = 1;
}

function setTimer(){
  if(update_flag == 1){
    updateInterval = setInterval(updateCountdown, 1000);
  } else{
    if(updateInterval){
      clearInterval(updateInterval);
    }
  }
}

function convertGapView(time_in_seconds, position){
  //console.log(typeof time_in_seconds);

  var adjustedPosition = parseInt(position);
	if(isNaN(time_in_seconds)){

      return time_in_seconds;

	}else if(time_in_seconds >= 60){
		var minutes = Math.floor(time_in_seconds / 60);
		var seconds = (time_in_seconds%60).toFixed(3);
    if(position != 1){
      var compound = "+" + minutes + ":" + (seconds < 10 ? "0" : "") + seconds;
    } else {
      var compound =  minutes + ":" + (seconds < 10 ? "0" : "") + seconds;
    }

	} else {


		var seconds = parseFloat(time_in_seconds).toFixed(3);

    if(seconds == 0.200){
      seconds = 0.200 + ((Math.random()/20)-0.0333);
      seconds = seconds.toFixed(3);
    }

		var compound = "+" + seconds;
	}


	return compound;

}

function createStartingGrid(data){

  var elem = document.querySelector("#drivers-table");

  if(!!elem){
    elem.parentNode.removeChild(elem);
  }

  var elem2 = document.querySelector('#grid-container');

  var totalObjects = Object.keys(data.total_data).length;


  if(!elem2){
      var grid_container = document.createElement("div");
      grid_container.setAttribute("id", "grid-container");
      var grid_element = document.createElement("div");
      grid_element.className += "grid-element";
      var grid_pos = document.createElement("div");
      grid_pos.className += "grid-pos";
      var pos_span = document.createElement("span");
      pos_span.className += "driverPosition";
      grid_pos.appendChild(pos_span);
      var grid_driver = document.createElement("div");
      grid_driver.className += "grid-driver";
      var grid_car = document.createElement("div");
      grid_car.className += "grid-car";
      var car_img = document.createElement("img");
      grid_car.appendChild(car_img);
      var grid_driver_label = document.createElement("div");
      grid_driver_label.className += "grid-driver-label";
      var grid_driver_name = document.createElement("div");
      grid_driver_name.className += "grid-driver-name";
      var name_span = document.createElement("span");
      name_span.className += "driverName";
      grid_driver_name.appendChild(name_span);
      var grid_driver_time = document.createElement("div");
      grid_driver_time.className += "grid-driver-time";
      var time_span = document.createElement("span");
      time_span.className += "driverTime";
      grid_driver_time.appendChild(time_span);
      var grid_driver_quali = document.createElement("div");
      grid_driver_quali.className += "grid-driver-quali";
      var quali_span = document.createElement("span");
      quali_span.className += "driverQuali";
      grid_driver_quali.appendChild(quali_span);
      var grid_row = document.createElement("div");
      grid_row.className += "grid-row";
      grid_driver_label.appendChild(grid_driver_name);
      grid_driver_label.appendChild(grid_driver_time);
      grid_driver_label.appendChild(grid_driver_quali);
      grid_driver.appendChild(grid_car);
      grid_driver.appendChild(grid_driver_label);
      grid_element.appendChild(grid_pos);
      grid_element.appendChild(grid_driver);

      for(i = 1;i < 3; i++){
        grid_row.appendChild(grid_element.cloneNode(true));
      }

      for(i = 1;i <= Math.ceil(totalObjects/2) ;i++){
        grid_container.appendChild(grid_row.cloneNode(true));
      }
      document.getElementById("container-live-table").appendChild(grid_container);
  }

  //edit starting grid
  var position = 0;
  var selection = 1;
  Object.values(data.total_data).forEach(function(row){
    position++;

    if(position < 11){
      adjustedQuali = "Q1";
    } else if(position < 16){
      adjustedQuali = "Q2";
    } else {
      adjustedQuali = "Q3";
    }

    if(position%2 == 0){
      document.querySelector("#grid-container > div:nth-child("+selection+") div:nth-child(2) div:nth-child(1) span.driverPosition").textContent = position.toString();
      document.querySelector("#grid-container > div:nth-child("+selection+") div:nth-child(2) div:nth-child(2) div:nth-child(1) img").setAttribute("src", '/octamotor/images/car/'+row.car_picture);
      if(row.tv_name == ""){
        document.querySelector("#grid-container > div:nth-child("+selection+") div:nth-child(2) div:nth-child(2) div:nth-child(2) div:nth-child(1) span.driverName").textContent = treatName(row.name);
      } else {
        document.querySelector("#grid-container > div:nth-child("+selection+") div:nth-child(2) div:nth-child(2) div:nth-child(2) div:nth-child(1) span.driverName").textContent = row.tv_name;
      }
      document.querySelector("#grid-container > div:nth-child("+selection+") div:nth-child(2) div:nth-child(2) div:nth-child(2) div:nth-child(3) span.driverQuali").textContent = adjustedQuali;
      document.querySelector("#grid-container > div:nth-child("+selection+") div:nth-child(2) div:nth-child(2) div:nth-child(2) div:nth-child(2) span.driverTime").textContent = convertGapView(row.gap, position);
      selection++;
    } else {
      document.querySelector("#grid-container > div:nth-child("+selection+") div:nth-child(1) div:nth-child(1) span.driverPosition").textContent = position;
      document.querySelector("#grid-container > div:nth-child("+selection+") div:nth-child(1) div:nth-child(2) div:nth-child(1) img").setAttribute("src", '/octamotor/images/car/'+row.car_picture);
      if(row.tv_name == ""){
        document.querySelector("#grid-container > div:nth-child("+selection+") div:nth-child(1) div:nth-child(2) div:nth-child(2) div:nth-child(1) span.driverName").textContent = treatName(row.name);
      } else {
        document.querySelector("#grid-container > div:nth-child("+selection+") div:nth-child(1) div:nth-child(2) div:nth-child(2) div:nth-child(1) span.driverName").textContent = row.tv_name;
      }
      document.querySelector("#grid-container > div:nth-child("+selection+") div:nth-child(1) div:nth-child(2) div:nth-child(2) div:nth-child(3) span.driverQuali").textContent = adjustedQuali;
      document.querySelector("#grid-container > div:nth-child("+selection+") div:nth-child(1) div:nth-child(2) div:nth-child(2) div:nth-child(2) span.driverTime").textContent = convertGapView(row.gap, position);

    }
    //console.log(convertGapView(row.gap,1));
  });

}

function treatName(name){
  let splitName = name.split(' ');
  let firstName = splitName[0].substring(0,1);
  splitName[0] = firstName;

  var PATTERN = '"',
  filteredName = splitName.filter(function (str) { return str.indexOf(PATTERN) === -1; });

  filteredName = filteredName.join(" ");
  //console.log(filteredName);
  return filteredName;
}

function treatTeamName(name){
  let splitName = name.split(' ');
  let filteredName = splitName.splice(0,2);

  filteredName = filteredName.join(" ");
  //console.log(filteredName);
  return filteredName;
}

function raceLights(){

  let elem = document.querySelector("#f1-lights");
  if(!elem){
    var f1lights = document.createElement("div");
    f1lights.setAttribute("id", "f1-lights");
    var backboard = document.createElement("div");
    backboard.setAttribute("id", "back-board");
    //f1lights.appendChild(backboard);

    var lightstrip = document.createElement("div");
    lightstrip.className += "light-strip";
    var light = document.createElement("div");
    light.className += "light";

    for(i = 1; i < 5; i++){
      lightstrip.appendChild(light.cloneNode());
    }

    for(i = 1; i < 6; i++){
      f1lights.appendChild(lightstrip.cloneNode(true));
    }
    document.getElementById("race-flag-box").appendChild(backboard);
    document.getElementById("race-flag-box").appendChild(f1lights);


    var mod_item = document.getElementById("race-flag-box");
    //console.log(day_status);
    //console.log(rain_status);

    if(day_status == 1 && rain_status == 1) { //chuva, dia
      let min=1;
      let max=3;
      let random =Math.floor(Math.random() * (+max - +min)) + +min;
      mod_item.className += " sky-gradient-rain-" + random.toString() + " ";
    } else if(day_status == 1 && rain_status == 0){ //seco, dia
      let min=1;
      let max=6;
      let random =Math.floor(Math.random() * (+max - +min)) + +min;
      mod_item.className += " sky-gradient-day-" + random.toString() + " ";
      //console.log(mod_item);
    } else { //noite
      let min=1;
      let max=10;
      let random =Math.floor(Math.random() * (+max - +min)) + +min;
      mod_item.className += " sky-gradient-night-" + random.toString() + " ";
    }
  }


}

function startLights() {

  const lights = Array.prototype.slice.call(document.querySelectorAll('.light-strip'));


  for (const light of lights) {
    light.classList.remove('on');
  }

  let lightsOn = 0;
  const lightsStart = performance.now();

  function frame(now) {
    const toLight = Math.floor((now - lightsStart) / 1000) + 1;

    if (toLight > lightsOn) {
      for (const light of lights.slice(0, toLight)) {
        light.classList.add('on');
      }
    }

    if (toLight < 5) {
      raf = requestAnimationFrame(frame);
    } else {
      const delay = Math.random() * 4000 + 1000;
      timeout = setTimeout(() => {
        for (const light of lights) {
          light.classList.remove('on');
        }
        lightsOutTime = performance.now();
      }, delay);
    }
  }

  raf = requestAnimationFrame(frame);
}

});

</script>


<?php

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
