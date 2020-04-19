<?php

$id = $_POST["id"];

require_once "config/database.php";
require_once "classes/driver.php";
require_once "classes/car.php";

$database = new OctamotorDatabase();
$db = $database->getConnection();

$driver = new Driver($db);
$car = new Car($db);

$driver_info = $driver->loadDriver($id);

$result = $driver_info->fetch(PDO::FETCH_ASSOC);

$result["bio"] = html_entity_decode($result['bio']);

//var_dump($result);

die(json_encode(["driver_data" => $result]));


 ?>
