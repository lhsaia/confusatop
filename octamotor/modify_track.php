<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
ini_set( 'display_errors', true );
error_reporting( E_ALL );

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){
  require_once $_SERVER['DOCUMENT_ROOT']."/octamotor/config/database.php";
  require_once $_SERVER['DOCUMENT_ROOT']."/octamotor/classes/track.php";
  include_once $_SERVER['DOCUMENT_ROOT']."/lib/image_helper.php";

  $track_data = array();

  foreach($_POST as $key => $item){
    if($key != "id" && $key != "previous_image"){
      $track_data[$key] = $item;
    }
  }

  // var_dump($car_data);

  $octamotorDatabase = new OctamotorDatabase();
  $odb = $octamotorDatabase->getConnection();

  $track = new Track($odb);

  $previous_image = $_POST["previous_image"];


  // tratamento e importação de imagem
  if(isset($_FILES['image']) && !empty($_FILES['image']['tmp_name']) && (file_exists($_FILES['image']['tmp_name']) || is_uploaded_file($_FILES['image']['tmp_name']))){
      $fileName = $_FILES['image']['name'];
      $fileExplode = explode(".",$fileName);
      $fileBase = preg_replace("/[^a-zA-Z0-9]/", "", $fileExplode[0]) ?: "track";
      $fileName = strtolower($fileBase) . mt_rand(1,10000).".webp";
      $filePath = $_FILES['image']['tmp_name'];
      $upload_dir = "/octamotor/images/track/";

      $upload_path = $_SERVER['DOCUMENT_ROOT'] .$upload_dir .$_SESSION['user_id'] ."-" . $fileName;
      if(processAndSaveWebPImage($filePath, $upload_path, 800, 90)){
          $track_data["image"] = $_SESSION['user_id'] ."-" .$fileName;
      } else {
          $error_msg .= "Não foi possível processar o traçado da pista em WebP. ";
          $track_data["image"] = $previous_image;
      }
  } else {
    $track_data["image"] = $previous_image;
  }

  //  var_dump($track_data);
  // //
  // die;
  // verify user_id, open_window, driver_owner, admin_status
  if(isset($_POST["id"]) && $_POST["id"] != ""){
    $id = $_POST["id"];

    if($track->isNotOwner($id, $_SESSION["user_id"]) && $_SESSION['admin_status'] == 0){
      die(json_encode(["success" => false, "error_msg" => "Usuário não é dono do circuito", "new_track" => false]));
    }

    if($track->updateTrack($id, $track_data)){
      $is_success = true;
      $error_msg = "Atualização realizada com sucesso!";
    } else {
      $is_success = false;
      $error_msg = "Houve um erro na solicitação. Caso o erro persista, entre em contato com os admins.";
    }
    $new_track = false;
  } else {

    if($track->insertTrack($track_data)){
      $is_success = true;
      $error_msg = "Criação realizada com sucesso!";
    } else {
      $is_success = false;
      $error_msg = "Houve um erro na solicitação. Caso o erro persista, entre em contato com os admins.";
    }
    $new_track = true;
    $id = $odb->lastInsertId();
  }

} else {
  $is_success = false;
  $error_msg = "Usuário não logado";
  $new_track = null;
}



die(json_encode(["success" => $is_success, "error_msg" => $error_msg, "new_track" => $new_track]));


 ?>
