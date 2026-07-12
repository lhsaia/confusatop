<?php
// ini_set( 'display_errors', true );
// error_reporting( E_ALL );
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

require_once "classes/db_name.php";
require_once "config/database.php";
require_once "classes/driver.php";
require_once "classes/car.php";
require_once "classes/track.php";
require_once "classes/competition.php";
require_once "classes/race.php";

$database = new OctamotorDatabase();
$db = $database->getConnection();

if(isset($_POST['track'])){
  $track_selected = $_POST['track'];
  $competition_selected = $_POST['competition'];
  $unix_time = $_POST['unix_time'];
} else {
  $track_selected = 1;
  $competition_selected = 1;
  $unix_time = time() + 24*60*60 + 60;
}

$driver = new Driver($db);
$car = new Car($db);
$track = new Track($db, $track_selected);
$competition = new Competition($db, $competition_selected);




if(!isset($_POST['stressTest'])){
	
	$race = new Race($db, $competition, $track);

	$race->setFilename();
	$race->setBaseTimestamp($unix_time);
	$race->setCurrentUser(isset($_SESSION['user_id']) ? $_SESSION['user_id'] : "");
	$race->setTotalLaps();
	$race->load_participants($driver, $car);
	$race->recordRaceInfo("", "2020", 1, '9999');
	$race->recordResults("A-0", 0);
	$race->calculateHighestLevel(0);

	$results = array();

	//pre-quali loop
	$all_racers = $race->getRaceList();
	$max_drivers = $competition->getMaxDrivers();
	$total_racers = count($all_racers);

	if($total_racers > $max_drivers){
	  $race->pre_quali();
	}

	$race->qualifying();
	$race->calculateHighestLevel(1);
	$race->runRace();
	die(json_encode(["success" => true]));
} else {
	

	$scoreTable = array();
	
	for ($i = 0; $i <= 1000; $i++) {
		
		$race = new Race($db, $competition, $track);

		$race->setFilename();
		$race->setBaseTimestamp($unix_time);
		$race->setCurrentUser(isset($_SESSION['user_id']) ? $_SESSION['user_id'] : "");
		$race->setTotalLaps();
		$race->load_participants($driver, $car);
		$race->recordRaceInfo("", "2020", 1, '9999');
		$race->recordResults("A-0", 0);
		$race->calculateHighestLevel(0);

		$results = array();

		//pre-quali loop
		$all_racers = $race->getRaceList();
		$max_drivers = $competition->getMaxDrivers();
		$total_racers = count($all_racers);

		
		if($total_racers > $max_drivers){
		  $race->pre_quali();
		}

		$race->qualifying();
		$race->calculateHighestLevel(1);
		$race->runRace(true);
		
		$results = $race->getLapResults();
		
		$lastLap = end($results);
		
		array_shift($lastLap);

		$summaryLap = array();
		
		foreach($lastLap as $item){
			$summaryLap[] = $item['driver']->getId();
		}
		
		$scoreTable[] = $summaryLap;
	
	}
	
	$resultMatrix = array();
	foreach($all_racers as $key => $value){
		$resultMatrix[] = ["id" => $value["driver"]->getId(),"name" => $value["driver"]->getName(), "level" => $value["driver"]->getLevel(), "car_name" => $value["car"]->getName(), "car_level" => ($value["car"]->getChassis() + $value["car"]->getEngine())/2, "wins" => 0, "second" => 0, "third" => 0, "fourth" => 0, "fifth" => 0, "sixth" => 0, "seventh" => 0, "eighth" => 0 ];
	}
	
	
	foreach($resultMatrix as &$item){
		$winCount = 0;
		$secondCount = 0;
		$thirdCount = 0;
		$fourthCount = 0;
		$fifthCount = 0;
		$sixthCount = 0;		
		$seventhCount = 0;		
		$eighthCount = 0;		
		foreach($scoreTable as $singleRace){
			if($singleRace[0] == $item["id"]){
				$winCount++;
			}
			if($singleRace[1] == $item["id"]){
				$secondCount++;
			}
			if($singleRace[2] == $item["id"]){
				$thirdCount++;
			}
			if($singleRace[3] == $item["id"]){
				$fourthCount++;
			}
			if($singleRace[4] == $item["id"]){
				$fifthCount++;
			}
			if($singleRace[5] == $item["id"]){
				$sixthCount++;
			}
			if($singleRace[6] == $item["id"]){
				$seventhCount++;
			}
			if($singleRace[7] == $item["id"]){
				$eighthCount++;
			}
		}
		$item["wins"] = $winCount;
		$item["second"] = $secondCount;
		$item["third"] = $thirdCount;
		$item["fourth"] = $fourthCount;
		$item["fifth"] = $fifthCount;
		$item["sixth"] = $sixthCount;
		$item["seventh"] = $seventhCount;
		$item["eighth"] = $eighthCount;
	}
	
	// order products by: wins DESC, inStock DESC, isRecommended DESC, name ASC
	usort($resultMatrix, function ($a, $b): int {
		return
			($b["wins"] <=> $a["wins"]) * 10000000 + // wins DESC
			($b["second"] <=> $a["second"]) * 1000000 + // second DESC
			($b["third"] <=> $a["third"]) * 100000 + // third DESC
			($b["fourth"] <=> $a["fourth"]) * 10000 + // fourth DESC
			($b["fifth"] <=> $a["fifth"]) * 1000 + // fifth DESC
			($b["sixth"] <=> $a["sixth"]) * 100 + // sixth DESC
			($b["seventh"] <=> $a["seventh"]) * 10  +// seventh DESC
			($b["eighth"] <=> $a["eighth"]) ; // eighth DESC
	});

	die(json_encode($resultMatrix));
	
}




?>
