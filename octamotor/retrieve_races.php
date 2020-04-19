<?php

ini_set( 'display_errors', true );
error_reporting( E_ALL );

$id = $_POST["season_id"];

// var_dump($_POST);

require_once "config/database.php";
require_once "classes/competition.php";

$database = new OctamotorDatabase();
$db = $database->getConnection();

$competition = new Competition($db);

$races_info = $competition->retrieveRaces($id);

//$result = $seasons_info->fetch(PDO::FETCH_ASSOC);

//var_dump($result);

die(json_encode(["races_data" => $races_info]));


 ?>
