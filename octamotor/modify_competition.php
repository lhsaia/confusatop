<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
// ini_set( 'display_errors', true );
// error_reporting( E_ALL );

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){
  require_once $_SERVER['DOCUMENT_ROOT']."/octamotor/config/database.php";
  require_once $_SERVER['DOCUMENT_ROOT']. "/octamotor/classes/competition.php";
  include_once $_SERVER['DOCUMENT_ROOT']."/lib/image_helper.php";

  $competition_data = array();

  foreach($_POST as $key => $item){
    if($key != "id" && $key != "previous_logo"){
      $competition_data[$key] = $item;
    }
  }

  // var_dump($car_data);

  $octamotorDatabase = new OctamotorDatabase();
  $odb = $octamotorDatabase->getConnection();

  $competition = new Competition($odb);

  $previous_logo = $_POST["previous_logo"];


  // tratamento e importação de imagem
  if(isset($_FILES['logo']) && !empty($_FILES['logo']['tmp_name']) && (file_exists($_FILES['logo']['tmp_name']) || is_uploaded_file($_FILES['logo']['tmp_name']))){
      $fileName = $_FILES['logo']['name'];
      $fileExplode = explode(".",$fileName);
      $fileBase = preg_replace("/[^a-zA-Z0-9]/", "", $fileExplode[0]) ?: "competition";
      $fileName = strtolower($fileBase) . mt_rand(1,10000).".webp";
      $filePath = $_FILES['logo']['tmp_name'];
      $upload_dir = "/octamotor/images/competition/";

      $upload_path = $_SERVER['DOCUMENT_ROOT'] .$upload_dir .$_SESSION['user_id'] ."-" . $fileName;
      if(processAndSaveWebPImage($filePath, $upload_path, 300, 90)){
          $competition_data["logo"] = $_SESSION['user_id'] ."-" .$fileName;
      } else {
          $error_msg .= "Não foi possível processar o logo em WebP. ";
          $competition_data["logo"] = $previous_logo;
      }
  } else {
    $competition_data["logo"] = $previous_logo;
  }

  $competition_data["owner"] = $_SESSION['user_id'];

  //  var_dump($competition_data);
  // //
  // die;
  // verify user_id, open_window, driver_owner, admin_status
  if(isset($_POST["id"]) && $_POST["id"] != ""){
    $id = $_POST["id"];

    if($competition->isNotOwner($id, $_SESSION["user_id"]) && $_SESSION['admin_status'] == 0){
      die(json_encode(["success" => false, "error_msg" => "Usuário não é dono da competição", "new_competition" => false]));
    }

    if($competition->updateCompetition($id, $competition_data)){
      $is_success = true;
      $error_msg = "Atualização realizada com sucesso!";
    } else {
      $is_success = false;
      $error_msg = "Houve um erro na solicitação. Caso o erro persista, entre em contato com os admins.";
    }
    $new_competition = false;
  } else {

    if($competition->insertCompetition($competition_data)){
      $is_success = true;
      $error_msg = "Criação realizada com sucesso!";
    } else {
      $is_success = false;
      $error_msg = "Houve um erro na solicitação. Caso o erro persista, entre em contato com os admins.";
    }
    $new_competition = true;
    $id = $odb->lastInsertId();
  }

} else {
  $is_success = false;
  $error_msg = "Usuário não logado";
  $new_competition = null;
}



die(json_encode(["success" => $is_success, "error_msg" => $error_msg, "new_competition" => $new_competition]));


 ?>
