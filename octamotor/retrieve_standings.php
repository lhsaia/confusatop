<?php

ini_set( 'display_errors', true );
error_reporting( E_ALL );

$id = $_POST["event_id"];
$type = $_POST["event_type"];

// var_dump($_POST);

require_once "config/database.php";
require_once "classes/competition.php";

$database = new OctamotorDatabase();
$db = $database->getConnection();

$competition = new Competition($db);

$standings_info = $competition->retrieveStandings($id, $type);

//$result = $seasons_info->fetch(PDO::FETCH_ASSOC);

//var_dump($result);

die(json_encode(["standings_data" => $standings_info]));


 ?>
