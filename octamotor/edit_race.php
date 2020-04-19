<?php

session_start();
ini_set( 'display_errors', true );
error_reporting( E_ALL );

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){
  require_once $_SERVER['DOCUMENT_ROOT']."/octamotor/config/database.php";
  require_once $_SERVER['DOCUMENT_ROOT']. "/octamotor/classes/race.php";

  $season_data = array();

  // var_dump($car_data);

  $octamotorDatabase = new OctamotorDatabase();
  $odb = $octamotorDatabase->getConnection();

  $race = new Race($odb);

  //$season_data["owner"] = $_SESSION['user_id'];
  foreach($_POST as $key => $item){
    $race_data[$key] = $item;
  }

  // var_dump($race_data);
  //
  // die;



  if($race->isNotOwner($race_data['race_season'], $_SESSION["user_id"]) && $_SESSION['admin_status'] == 0){
    die(json_encode(["success" => false, "error_msg" => "Usuário não é dono da competição"]));
  }

  if($race->editRace($race_data)){
    $is_success = true;
    $error_msg = "Corrida criada com sucesso!";
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
