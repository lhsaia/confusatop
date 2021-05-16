<?php

ini_set( 'display_errors', true );
error_reporting( E_ALL );

$drivers = $_POST["podium_drivers"];

//var_dump($_POST);

require_once "config/database.php";
require_once "classes/driver.php";

$database = new OctamotorDatabase();
$db = $database->getConnection();

$driver = new Driver($db);

$driver_pictures = $driver->getPictures($drivers);

$pictures = array();

while($result = $driver_pictures->fetch(PDO::FETCH_ASSOC)){
	$pictures[$result["name"]] = $result["photo"];
}


die(json_encode($pictures));


 ?>
