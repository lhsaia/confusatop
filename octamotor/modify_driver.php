<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
// ini_set( 'display_errors', true );
// error_reporting( E_ALL );

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){
  require_once $_SERVER['DOCUMENT_ROOT']."/octamotor/config/database.php";
  require_once $_SERVER['DOCUMENT_ROOT']."/octamotor/classes/driver.php";
  include_once $_SERVER['DOCUMENT_ROOT']."/lib/image_helper.php";

  $driver_data = array();

  foreach($_POST as $key => $item){
    if($key != "id" && $key != "previous_photo" && $key != "previous_helmet"){
      $driver_data[$key] = $item;
    }
  }

  // var_dump($driver_data);

  $octamotorDatabase = new OctamotorDatabase();
  $odb = $octamotorDatabase->getConnection();

  $driver = new Driver($odb);

  $previous_photo = $_POST["previous_photo"];
  $previous_helmet = $_POST["previous_helmet"];

  // tratamento e importação de imagem
  if(isset($_FILES['photo']) && !empty($_FILES['photo']['tmp_name']) && (file_exists($_FILES['photo']['tmp_name']) || is_uploaded_file($_FILES['photo']['tmp_name']))){
      $fileName = $_FILES['photo']['name'];
      $fileExplode = explode(".",$fileName);
      $fileBase = preg_replace("/[^a-zA-Z0-9]/", "", $fileExplode[0]) ?: "driver";
      $fileName = strtolower($fileBase) . mt_rand(1,10000).".webp";
      $filePath = $_FILES['photo']['tmp_name'];
      $upload_dir = "/octamotor/images/driver/";

      $upload_path = $_SERVER['DOCUMENT_ROOT'] .$upload_dir .$_SESSION['user_id'] ."-" . $fileName;
      if(processAndSaveWebPImage($filePath, $upload_path, 330, 90)){
          $driver_data["photo"] = $_SESSION['user_id'] ."-" .$fileName;
      } else {
          $error_msg .= "Não foi possível processar a foto em WebP. ";
          $driver_data["photo"] = $previous_photo;
      }
  } else {
    $driver_data["photo"] = $previous_photo;
  }

  if(isset($_FILES['helmet']) && !empty($_FILES['helmet']['tmp_name']) && (file_exists($_FILES['helmet']['tmp_name']) || is_uploaded_file($_FILES['helmet']['tmp_name']))){
      $fileName = $_FILES['helmet']['name'];
      $fileExplode = explode(".",$fileName);
      $fileBase = preg_replace("/[^a-zA-Z0-9]/", "", $fileExplode[0]) ?: "helmet";
      $fileName = strtolower($fileBase) . mt_rand(1,10000).".webp";
      $filePath = $_FILES['helmet']['tmp_name'];
      $upload_dir = "/octamotor/images/helmet/";

      $upload_path = $_SERVER['DOCUMENT_ROOT'] .$upload_dir .$_SESSION['user_id'] ."-" . $fileName;
      if(processAndSaveWebPImage($filePath, $upload_path, 330, 90)){
          $driver_data["helmet"] = $_SESSION['user_id'] ."-" .$fileName;
      } else {
          $error_msg .= "Não foi possível processar o capacete em WebP. ";
          $driver_data["helmet"] = $previous_helmet;
      }
  } else {
    $driver_data["helmet"] = $previous_helmet;
  }

  //var_dump($upload_path);
  //die;
  // verify user_id, open_window, driver_owner, admin_status
  if(isset($_POST["id"]) && $_POST["id"] != ""){
    $id = $_POST["id"];

    if($driver->isNotOwner($id, $_SESSION["user_id"]) && $_SESSION['admin_status'] == 0){
      die(json_encode(["success" => false, "error_msg" => "Usuário não é dono do piloto", "new_driver" => false]));
    }

    if($driver->updateDriver($id, $driver_data)){
      $is_success = true;
      $error_msg = "Atualização realizada com sucesso!";
    } else {
      $is_success = false;
      $error_msg = "Houve um erro na solicitação. Caso o erro persista, entre em contato com os admins.";
    }
    $new_driver = false;
  } else {

    if($driver->insertDriver($driver_data)){
      $is_success = true;
      $error_msg = "Criação realizada com sucesso!";
    } else {
      $is_success = false;
      $error_msg = "Houve um erro na solicitação. Caso o erro persista, entre em contato com os admins.";
    }
    $new_driver = true;
  }


} else {
  $is_success = false;
  $error_msg = "Usuário não logado";
  $new_driver = null;
}



die(json_encode(["success" => $is_success, "error_msg" => $error_msg, "new_driver" => $new_driver]));


 ?>
