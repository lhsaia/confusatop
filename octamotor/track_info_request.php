<?php

$id = $_POST["id"];

require_once "config/database.php";
require_once "classes/track.php";

$database = new OctamotorDatabase();
$db = $database->getConnection();

$track = new Track($db);

$track_info = $track->loadTrack($id);

$result = $track_info->fetch(PDO::FETCH_ASSOC);

die(json_encode(["track_data" => $result]));


 ?>
