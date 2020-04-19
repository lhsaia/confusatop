<?php

// ini_set( 'display_errors', true );
// error_reporting( E_ALL );

$id = $_POST["competition_id"];

// var_dump($_POST);

require_once "config/database.php";
require_once "classes/competition.php";
require_once "classes/track.php";

$database = new OctamotorDatabase();
$db = $database->getConnection();

$competition = new Competition($db);
$track = new Track($db);

$seasons_info = $competition->retrieveSeasons($id);
$track_info  = $track->retrieveTracks();

//$result = $seasons_info->fetch(PDO::FETCH_ASSOC);

//var_dump($result);

die(json_encode(["seasons_data" => $seasons_info, "track_data" => $track_info]));


 ?>
