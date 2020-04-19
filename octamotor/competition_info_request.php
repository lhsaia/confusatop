<?php

$id = $_POST["id"];

require_once "config/database.php";
require_once "classes/competition.php";

$database = new OctamotorDatabase();
$db = $database->getConnection();

$competition = new Competition($db);

$competition_info = $competition->loadCompetition($id);

$result = $competition_info->fetch(PDO::FETCH_ASSOC);

die(json_encode(["competition_data" => $result]));


 ?>
