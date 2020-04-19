<?php

session_start();

$user_id = $_SESSION['user_id'];

ini_set( 'display_errors', true );
error_reporting( E_ALL );

$get_user = $_POST["user_id"];

require_once "/home/lhsaia/confusa.top/config/database.php";
require_once "/home/lhsaia/confusa.top/objetos/escudos_pop.php";

$database = new Database();
$db = $database->getConnection();

$escudos_pop = new EscudosPop($db);

if($get_user == $user_id){
  if($return_data = $escudos_pop->lerPalpites($user_id)){
    $is_success = true;
  } else {
    $is_success = false;
  }

} else {
  $is_success = false;
  $return_data = "";
}


die(json_encode(["success" => $is_success, "return_data" => $return_data]));


 ?>



 ?>
