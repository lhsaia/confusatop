<?php
// 
// ini_set( 'display_errors', true );
// error_reporting( E_ALL );

session_start();

$id = $_POST["id"];

require_once "config/database.php";
require_once "classes/driver.php";
require_once "classes/competition.php";
require_once "classes/car.php";

$database = new OctamotorDatabase();
$db = $database->getConnection();

$driver = new Driver($db);
$car = new Car($db);
$competition = new Competition($db);

$car_info = $car->loadCar($id);
$driver_info = $driver->getDriversByTeam($id);

$result = $car_info->fetch(PDO::FETCH_ASSOC);
$driver_number = "first";
while($driver_result = $driver_info->fetch(PDO::FETCH_ASSOC)){
  $driver_data[$driver_number] = array("id" => $driver_result["id"], "name" => $driver_result["name"], "photo" => $driver_result["photo"]);
  $driver_number = "second";
}

if(!isset($driver_data)){
	$driver_data = [];
}

$car_owner = $car->getCarOwner($id);
$competition_owner = $car->getCompetitionOwner($id);

$competition_locked_status = $competition->getCompetitionLockedStatusByCar($id);

if(isset($_SESSION['user_id']) && $_SESSION['user_id'] == $competition_owner && $competition_owner != 0 && !$_SESSION['emTestes']){
	$can_edit = true;
} else if(isset($_SESSION['user_id']) && $competition_locked_status == 0 && $car_owner == $_SESSION['user_id'] && !$_SESSION['emTestes']){
	$can_edit = true;
} else if (($_SESSION['admin_status'] == '1' && (!isset($_SESSION['impersonated']) || $_SESSION['impersonated'] == false))){
	$can_edit = true;
} else {
	$can_edit = false;
}

die(json_encode(["car_data" => $result, "driver_data" => $driver_data, "can_edit" => $can_edit]));


 ?>
