<?php

// ini_set( 'display_errors', true );
// error_reporting( E_ALL );

session_start();

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

// if(!isset($_POST['track'])){
//   $lap_results = $race->getRaceResults();
//
//
//
//     echo '<pre>'; print_r($lap_results); echo '</pre>';
//
//    foreach($lap_results as $key_lap => $lap_result){
//      foreach($lap_result as $key_driver => $driver_result){
//        //name, lap, position, lap_time, race_time
//        $driver_name = $driver_result['driver']->getName();
//        $lap = $key_lap;
//        $position = $key_driver + 1;
//        $lap_time = $driver_result['driver']->getLapTime();
//        $race_time = $driver_result['driver']->getTotalTime();
//        $overtakes = $driver_result['driver']->getOvertakes();
//        $stint = $driver_result['driver']->getStint();
//        $current_tire = $driver_result['driver']->getTire();
//        $pit_lap = $driver_result['driver']->getRemainingPits();
//        $threshold = $driver_result['driver']->getThreshold();
//        $time_difference = $driver_result['driver']->getTimeDifference();
//        $status = $driver_result['driver']->getStatus();
//        $issue_name = $driver_result['driver']->getIssueName();
//
//        $results[] = array($driver_name, $lap, $position, $lap_time, $race_time, $overtakes, $stint, $current_tire, $pit_lap, $threshold, $time_difference, $status, $issue_name);
//      }
//    }
//
//    unset($lap_results);
//
//   /* next steps:
//   * saving to database and displaying (JSON)
//   improve qualifying (events) + Q1, Q2, Q3
//   front-end
//   optimize parameters
//
//   */
//
//
//   // printing results
//
// <!--
//   <table>
//     <thead>
//       <tr>
//         <th>Driver</th>
//         <th>Lap</th>
//         <th>Position</th>
//         <th>Lap Time</th>
//         <th>Total Time</th>
//         <th>Overtakes</th>
//         <th>Stint</th>
//         <th>Current Tire</th>
//         <th>Pit Lap</th>
//         <th>Threshold</th>
//         <th>Time Difference</th>
//         <th>Status</th>
//         <th>Issue Name</th>
//       </tr>
//     </thead>
//     <tbody> -->

//         foreach($results as $result){
//           echo "<tr><td>{$result[0]}</td><td>{$result[1]}</td><td>{$result[2]}</td><td>{$result[3]}</td><td>{$result[4]}</td><td>{$result[5]}</td><td>{$result[6]}</td><td>{$result[7]}</td><td>{$result[8]}</td><td>{$result[9]}</td><td>{$result[10]}</td><td>{$result[11]}</td><td>{$result[12]}</td></tr>";
//         }
//
  //   <!-- </tbody>
//   </table> -->
  //} else {
//
  die(json_encode(["success" => true]));
// }
?>
