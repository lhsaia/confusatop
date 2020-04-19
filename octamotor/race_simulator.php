<?php

  require_once("/home/lhsaia/confusa.top/octamotor/config/database.php");
  require_once("/home/lhsaia/confusa.top/octamotor/classes/race.php");
  require_once("/home/lhsaia/confusa.top/octamotor/classes/driver.php");
  require_once("/home/lhsaia/confusa.top/octamotor/classes/car.php");
  require_once("/home/lhsaia/confusa.top/octamotor/classes/track.php");
  require_once("/home/lhsaia/confusa.top/octamotor/classes/competition.php");
  $database = new OctamotorDatabase();
  $db = $database->getConnection();

  $current_time = time();
  $time_span = 5 * 24 * 60 * 60;

  $race_caller = new Race($db);
  //get all races during timeframe
  $all_races = $race_caller->supplySimulator($current_time, $time_span);

  //var_dump($all_races);

  foreach($all_races as $single_race){
    //var_dump($single_race);
    try {
      if($single_race['status'] == "0"){
        $track_selected = $single_race['track_id'];
        $competition_selected = $single_race['competition_id'];
        $unix_time = $single_race['datetime'];

        $driver = new Driver($db);
        $car = new Car($db);
        $track = new Track($db, $track_selected);
        $competition = new Competition($db, $competition_selected);
        $race = new Race($db, $competition, $track);

        $filename = "C" . $single_race['competition_id']  . "S" .$single_race['season_id']  . "R" . $single_race['id'] . ".json";

        $race->setFilename($filename);
        $race->setBaseTimestamp($unix_time);
        $race->setCurrentUser("");
        $race->setTotalLaps();
        $race->load_participants($driver, $car);
        $race->recordRaceInfo($single_race['name'], $single_race['season_year'], 1, $single_race['id']);
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

        if($race->runRace()) {
          $race->complete($single_race['id'], $filename);
          echo $single_race['id'] . " - Sucesso";
          echo "<br/>";
        } else {
          echo $single_race['id'] . " - Erro";
          echo "<br/>";
        }
      }
    } catch(Exception $e) {
      echo 'Message: ' .$e->getMessage();
      continue;
    }



  }
?>
