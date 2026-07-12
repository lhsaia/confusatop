<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
?>
<!DOCTYPE html>

<?php
include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "OctaMotor - Stress Test";
$css_filename = "race_live";
$css_login = 'login';
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


$command_center = "";
	

$command_center .= "<select id='circuit-selection'>";

foreach($track_list as $single_track){
  $command_center .= "<option value={$single_track['id']}>{$single_track['name']}</option>";
}
 
$command_center .= "</select>";
$command_center .= "<select id='competition-selection'>";

foreach($competition_list as $single_competition){
  $command_center .= "<option value={$single_competition['id']}>{$single_competition['name']}</option>";
}
 
$command_center .= "</select>";
$command_center .= "<button id='resim'>Iniciar</button>";



?>
<div id='loadingDiv'><img src='/octamotor/images/lights.gif'/></div>
<div id="container-home-octamotor">
    <div id="container-live-table" style="width:90%">
    </div>
</div>

<script>

  var baseTimestamp = "<?php echo (isset($baseTimestamp) ? $baseTimestamp : 0)?>";
  var localTimeOffset = -new Date().getTimezoneOffset()/60;
  var dt = new Date();
  var current_time = dt.getHours().toString().padStart(2,"0") + ":" + dt.getMinutes().toString().padStart(2,"0");
  var current_date = (dt.getFullYear()) + "-" + (dt.getMonth()+1).toString().padStart(2,"0") + "-" + (dt.getDate()).toString().padStart(2,"0");
  
  let command_center = "<?php echo $command_center; ?>";
  $("#toolbar").html(command_center);

$("document").ready(function(){

	$('#loadingDiv').hide();
  var addedTime;
  var raw_data = [];
  var bestLapPosition;
  var race_started = 0;

  $("#resim").click(function(){
	  $('#loadingDiv').show();

    var competition = $("#competition-selection").val();
    var track = $("#circuit-selection").val();

    var date = new Date().getTime() / 1000;

    var unix_time = (date) - (localTimeOffset * 60 * 60);

    $.ajax({
    url: 'test_ground.php',
    type: 'POST',
    dataType: 'json',
    data: {competition: competition,
            track: track,
          unix_time: unix_time,
		  stressTest: true}
    })
    .done(function(data) {
		$('#loadingDiv').hide();
      display_table(data);
      console.log(data);
    })
          .fail(function(xhr, status, error) {
            console.log("error");
			console.log(xhr.responseText);
    });

  });

function display_table(raw_data){

	var tbl = "<table id='stress-test-table' style='text-align: center'>";
		tbl += "<thead>";
		tbl += "<tr>";
		tbl += "<th class='driver-headers headers-right'>Driver</th>";
		tbl += "<th class='driver-headers'>Level</th>";
		tbl += "<th >Team</th>";
		tbl += "<th ' >Motor/Chassis</th>";
		tbl += "<th class='driver-headers' >1st</th>";
		tbl += "<th class='driver-headers' >2nd</th>";
		tbl += "<th class='driver-headers' >3rd</th>";
		tbl += "<th class='driver-headers' >4th</th>";
		tbl += "<th class='driver-headers' >5th</th>";
		tbl += "<th class='driver-headers' >6th</th>";
		tbl += "<th class='driver-headers' >7th</th>";
		tbl += "<th class='driver-headers' >8th</th>";
		tbl += "</tr>"
		tbl += "</thead>";
		tbl += "<tbody>";
		
    Object.values(raw_data).forEach(function(row){

           // console.log(row);
           tbl += "<tr>";
           tbl += "<td class='driver-headers headers-right'>" + row.name + "</td>";
		   tbl += "<td class='driver-headers'>" + row.level + "</td>";
		   tbl += "<td class=''>" + row.car_name + "</td>";
		   tbl += "<td class=''>" + row.car_level + "</td>";
		   tbl += "<td class=''>" + row.wins + "</td>";
		   tbl += "<td class=''>" + row.second + "</td>";
		   tbl += "<td class=''>" + row.third + "</td>";
		   tbl += "<td class=''>" + row.fourth + "</td>";
		   tbl += "<td class=''>" + row.fifth + "</td>";
		   tbl += "<td class=''>" + row.sixth + "</td>";
		   tbl += "<td class=''>" + row.seventh + "</td>";
		   tbl += "<td class=''>" + row.eighth + "</td>";

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

});

</script>


<?php

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
