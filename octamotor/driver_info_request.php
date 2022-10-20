<?php

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

$driver_info = $driver->loadDriver($id);

$driver_race_info = $driver->loadDriverRaceInfo($id);
$driver_grid_info = $driver->loadDriverGridInfo($id);

$result = $driver_info->fetch(PDO::FETCH_ASSOC);

$result["bio"] = html_entity_decode($result['bio']);

$result_race = $driver_race_info->fetch(PDO::FETCH_ASSOC);
$result_grid = $driver_grid_info->fetch(PDO::FETCH_ASSOC);

//var_dump($result);

$driver_owner = $driver->getDriverOwner($id);
$competition_owner = $driver->getCompetitionOwner($id);

$competition_locked_status = $competition->getCompetitionLockedStatusByDriver($id);

if(isset($_SESSION['user_id']) && $_SESSION['user_id'] == $competition_owner && $competition_owner != 0 && !$_SESSION['emTestes']){
	$can_edit = true;
} else if(isset($_SESSION['user_id']) && $competition_locked_status == 0 && $driver_owner == $_SESSION['user_id'] && !$_SESSION['emTestes']){
	$can_edit = true;
} else if (($_SESSION['admin_status'] == '1' && (!isset($_SESSION['impersonated']) || $_SESSION['impersonated'] == false))){
	$can_edit = true;
} else {
	$can_edit = false;
}

die(json_encode(["driver_data" => $result, "driver_race_data" => $result_race, "driver_grid_data" => $result_grid, "can_edit" => $can_edit]));


 ?>
