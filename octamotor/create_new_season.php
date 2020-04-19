<?php

session_start();
// ini_set( 'display_errors', true );
// error_reporting( E_ALL );

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){
  require_once $_SERVER['DOCUMENT_ROOT']."/octamotor/config/database.php";
  require_once $_SERVER['DOCUMENT_ROOT']. "/octamotor/classes/competition.php";

  $season_data = array();

  // var_dump($car_data);

  $octamotorDatabase = new OctamotorDatabase();
  $odb = $octamotorDatabase->getConnection();

  $competition = new Competition($odb);

  //$season_data["owner"] = $_SESSION['user_id'];
  $season_data["competition_id"] = $_POST['competition_id'];
  $season_data["season_year"] = $_POST['new_season'];


  if($competition->isNotOwner($season_data['competition_id'], $_SESSION["user_id"]) && $_SESSION['admin_status'] == 0){
    die(json_encode(["success" => false, "error_msg" => "Usuário não é dono da competição"]));
  }

  if($competition->createSeason($season_data)){
    $is_success = true;
    $error_msg = "Temporada criada com sucesso!";
  } else {
    $is_success = false;
    $error_msg = "Houve um erro na solicitação. Caso o erro persista, entre em contato com os admins.";
  }

} else {
  $is_success = false;
  $error_msg = "Usuário não logado";
  $new_competition = null;
}



die(json_encode(["success" => $is_success, "error_msg" => $error_msg]));


 ?>
