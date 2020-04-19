<?php

session_start();

$user_id = $_SESSION['user_id'];

// ini_set( 'display_errors', true );
// error_reporting( E_ALL );

$selected_id = $_POST["selected_id"];
$get_user = $_POST["user_id"];
$team_name = $_POST["team_name"];

if($team_name != ""){


require_once "/home/lhsaia/confusa.top/config/database.php";
require_once "/home/lhsaia/confusa.top/objetos/escudos_pop.php";

$database = new Database();
$db = $database->getConnection();

$escudos_pop = new EscudosPop($db);

if($get_user == $user_id){
  if($escudos_pop->gravarPalpite($user_id, $selected_id, $team_name)){
    $is_success = true;
  } else {
    $is_success = false;
  }

} else {
  $is_success = false;
}

}

die(json_encode(["success" => $is_success]));


 ?>



 ?>
