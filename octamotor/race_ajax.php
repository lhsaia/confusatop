<?php
// json reader

ini_set( 'display_errors', true );
error_reporting( E_ALL );

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

if(isset($_POST['file_name']) && $_POST['file_name'] != ""){
	$file_name = $_POST['file_name'];
} else {
	if(isset($_SESSION['user_id'])){
			$file_name = $_SESSION['user_id'] . "test.json";
	} else {
			$file_name =  "test.json";
	}
}

$auxiliary_table = array();

	$file_path = "/octamotor/races/";
	//codigo para recuperar nome do arquivo do banco de dados por numero da corrida e da competicao

	$file = file_get_contents($_SERVER['DOCUMENT_ROOT'].$file_path . $file_name);

// Convert to array
	$lap_results = json_decode($file, true);

  //get info from a single lap
	//
  // $lap_number = $_POST['lap'];
	// $stage_number = $_POST['stage'];

	$race_info = array_shift($lap_results);

	if($_POST["fakeTimeOffset"] == 0){
		$current_timestamp = time();
	} else {
		$current_timestamp = $_POST["baseTimestamp"] + $_POST["fakeTimeOffset"];
	}
	

	$reverse_laps = array_reverse($lap_results);
	$breakNext = false;

	foreach($reverse_laps as $key => $event){
		if($event[0]['timestamp'] < $current_timestamp && $breakNext == false){
			$current_step = $key;
			$rain_status = $event[0]['rain_status'];
			$safety_car_status = $event[0]['safety_car_status'];
			$air_temp = $event[0]['air_temp'];
			$track_temp = $event[0]['track_temp'];
			$timestamp = $event[0]['timestamp'];
			$breakNext = true;
		} else if($event[0]['timestamp'] < $current_timestamp && $breakNext == true){
			$previous_step = $key;
			$breakNext = false;
			break;
		}
		// else {
		// 	$current_step = array_keys($lap_results)[0];
		// 	$rain_status = $event[0]['rain_status'];
		// 	$safety_car_status = $event[0]['safety_car_status'];
		// 	$air_temp = $event[0]['air_temp'];
		// 	$track_temp = $event[0]['track_temp'];
		// }
	}

if($current_step[0] == "R"){
	$stage_number = "R";
} else if($current_step[0] == "P"){
	$stage_number = "PQ";
} else if ($current_step[0] == "Q") {
	if($current_step[1] == "C"){
		$stage_number = "QC";
	} else {
		$stage_number = "QE";
	}
} else if($current_step[0] == "G") {
	$stage_number = "G";
} else {
	$stage_number = "A";
}


$single_lap_data = $lap_results[$current_step];
if(isset($previous_step)){
	$previous_lap_data = $lap_results[$previous_step];
}


preg_match_all('!\d+!', $current_step, $lap_number_array);

//if($stage_number = "R"){


	$lap_number = $lap_number_array[0][0] ?? 0;




	$previous_lap_number = $lap_number - 1;
	if($previous_lap_number >= 0){
		  //inserir foreach para previous_lap_data + colocar dados obtidos na tabela auxiliar (piloto e posicao)
		foreach($previous_lap_data as $position => $driver){

		if($position != 0){
				$auxiliary_table[$position] = $driver["driver"]['id'];
		} 

  }
	}
//}

  $total_data = array();
  $best_lap_overall = 0;
  
 
  //usar isso no foreach de baixo

  foreach($single_lap_data as $position => $driver){

		if($position != 0){
			
			// ver ganho de posicao
			$previous_position = array_search($driver["driver"]['id'], $auxiliary_table);
			

			$row_data = array();
			
			$row_data['position_gain'] = $previous_position - $position;
			if(isset($driver['car']['tv_name'])){
				$row_data['team_tv_name'] = $driver['car']['tv_name'];
			} else {
				$row_data['team_tv_name'] = "";
			}
	    $row_data["name"] = $driver["driver"]["name"];
			if(isset($driver["driver"]["tv_name"])){
				$row_data["tv_name"] = $driver["driver"]["tv_name"];
			} else {
				$row_data["tv_name"] = "";
			}
	    $row_data["nationality"] = $driver["driver"]["nationality"];
	    $row_data["team"] = $driver["car"]["team_name"];
			$row_data["car_picture"] = $driver["car"]["car_picture"];
	    $row_data["pits"] = $driver["driver"]["pits_done"];//$driver["driver"]["pit_count"] - $driver["driver"]["remaining_pits"];
			if($stage_number == "R"){
				    $row_data["lap"] = $lap_number;
				$row_data["last_lap"] = $driver["driver"]["race_lap_time"];
				$row_data["best_lap"] = $driver["driver"]["best_lap"];
			} else {
				    $row_data["lap"] = $lap_number;
				$row_data["last_lap"] = $driver["driver"]["qualifying_last_time"];
				$row_data["best_lap"] = $driver["driver"]["qualifying_best_time"];
			}

		$row_data["tire"] = $driver["driver"]["current_tire_set"];

	    if($position == 1){
				if($stage_number == "R"){
					$row_data["gap"] = "leader";
		} else if($stage_number == "A" || $current_step == "QE-0" || $current_step == "QC-0" || $current_step == "PQ-0" ){
					$row_data["gap"] = "0:00.000";
				} else {
					$row_data["gap"] = $driver["driver"]["qualifying_best_time"];
					$leader_qualifying_time = $row_data["gap"];
				}
	      $leader_total = $driver["driver"]["race_total_time"];
	      $leader_lap = $row_data["last_lap"];
	      $best_lap_overall = $row_data["best_lap"];
	      $previous_total = $leader_total;
				$leader_average_speed = $driver["driver"]["average_speed"];
	    } else {
				if($stage_number == "R"){
					$row_data["gap"] = $driver["driver"]["race_total_time"] - $previous_total;
				} else if($stage_number == "A" || $current_step == "QE-0" || $current_step == "QC-0" || $current_step == "PQ-0"){
					$row_data["gap"] = 0;
				} else {
					$row_data["gap"] = $driver["driver"]["qualifying_best_time"] - $leader_qualifying_time;
				}

	      if($row_data["best_lap"] < $best_lap_overall){
	          $best_lap_overall = $row_data["best_lap"];
	      }

	      $previous_total = $driver["driver"]["race_total_time"];
	    }
	    if(($previous_total - $leader_total) > $leader_lap){
				if($leader_lap == 0){
					$laps_behind = 0;
				} else {
				//	$laps_behind = floor(($previous_total - $leader_total)/$leader_lap);
						//$laps_behind = floor(($previous_total - $leader_total) * ($leader_average_speed - $driver["driver"]["average_speed"]));

						//$corrected_laps = round();
				}
				if($driver["driver"]["average_speed"] == 0){
					$speed_ratio = 1;
				} else {
					$speed_ratio = $leader_average_speed / $driver["driver"]["average_speed"];
				}

	      //$row_data["lap"] = $row_data["lap"] - $laps_behind;
				$row_data["lap"] = round($row_data["lap"] / $speed_ratio);
				if($row_data["lap"] < 0){
					$row_data["lap"] = 0;
				}
	    }

	    if($driver["driver"]["status"] < 0){
	      $row_data["gap"] = "OUT";
		  $row_data["last_lap"] = $driver["driver"]["issue_name"];
		  $row_data["lap"] = $driver["driver"]["abandoned_lap"];
	    }


		$row_data["permanent_penalty"] = $driver["driver"]["permanent_penalty"];
		$row_data["lap_penalty"] = $driver["driver"]["lap_penalty"];

		$row_data["total_time"] = $driver["driver"]["race_total_time"];

	    $total_data[$position] = $row_data;
		} else {
			//$total_data[$position] = $driver;
		}

  }

//var_dump($total_data); // print arrayCopy
if($stage_number == "R"){
	if($lap_number != 0){
		usort($total_data, function($a, $b) {

				if($a["lap"] == $b["lap"]){
					return $a['total_time'] <=> $b['total_time'];
				}
				return $b['lap'] <=> $a['lap'];

		});
	}

}


die(json_encode([ 'total_data'=> $total_data, 'current_step' => $current_step, 'race_info' => $race_info, 'rain_status' => $rain_status, 'safety_car_status' => $safety_car_status, 'air_temp' => $air_temp, 'track_temp' => $track_temp, 'timestamp' => $timestamp]));


?>
