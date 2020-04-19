<?php
// 
// ini_set( 'display_errors', true );
// error_reporting( E_ALL );

$id = $_POST["id"];

require_once "config/database.php";
require_once "classes/driver.php";
require_once "classes/car.php";

$database = new OctamotorDatabase();
$db = $database->getConnection();

$driver = new Driver($db);
$car = new Car($db);

$car_info = $car->loadCar($id);
$driver_info = $driver->getDriversByTeam($id);

$result = $car_info->fetch(PDO::FETCH_ASSOC);
$driver_number = "first";
while($driver_result = $driver_info->fetch(PDO::FETCH_ASSOC)){
  $driver_data[$driver_number] = array("id" => $driver_result["id"], "name" => $driver_result["name"], "photo" => $driver_result["photo"]);
  $driver_number = "second";
}

die(json_encode(["car_data" => $result, "driver_data" => $driver_data]));


 ?>
