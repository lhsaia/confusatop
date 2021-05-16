<?php

ini_set( 'display_errors', true );
error_reporting( E_ALL );

$competition_name = $_POST["competition_name"];

//var_dump($_POST);

require_once "config/database.php";
require_once "classes/competition.php";

$database = new OctamotorDatabase();
$db = $database->getConnection();

$competition = new Competition($db);

$competition_logo = $competition->getLogoFromName($competition_name);

die(json_encode($competition_logo));


 ?>
