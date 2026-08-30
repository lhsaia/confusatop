<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
// ini_set( 'display_errors', true );
// error_reporting( E_ALL );

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){
  require_once $_SERVER['DOCUMENT_ROOT']."/octamotor/config/database.php";
  require_once $_SERVER['DOCUMENT_ROOT']."/octamotor/classes/driver.php";
  require_once $_SERVER['DOCUMENT_ROOT']."/octamotor/classes/car.php";
  include_once $_SERVER['DOCUMENT_ROOT']."/lib/image_helper.php";

  $car_data = array();

  foreach($_POST as $key => $item){
    if($key != "id" && $key != "previous_logo" && $key != "previous_picture" && $key != "previous_suit" && $key != "driver1" && $key != "driver2"){
      $car_data[$key] = $item;
    }
  }

  // var_dump($car_data);

  $octamotorDatabase = new OctamotorDatabase();
  $odb = $octamotorDatabase->getConnection();

  $driver = new Driver($odb);
  $car = new Car($odb);

  $previous_picture = $_POST["previous_picture"];
  $previous_logo = $_POST["previous_logo"];
  $previous_suit = $_POST["previous_suit"];

  // tratamento e importação de imagem
  if(isset($_FILES['logo']) && !empty($_FILES['logo']['tmp_name']) && (file_exists($_FILES['logo']['tmp_name']) || is_uploaded_file($_FILES['logo']['tmp_name']))){
      $fileName = $_FILES['logo']['name'];
      $fileExplode = explode(".",$fileName);
      $fileBase = preg_replace("/[^a-zA-Z0-9]/", "", $fileExplode[0]) ?: "logo";
      $fileName = strtolower($fileBase) . mt_rand(1,10000).".webp";
      $filePath = $_FILES['logo']['tmp_name'];
      $upload_dir = "/octamotor/images/car_logo/";

      $upload_path = $_SERVER['DOCUMENT_ROOT'] .$upload_dir .$_SESSION['user_id'] ."-" . $fileName;
      if(processAndSaveWebPImage($filePath, $upload_path, 300, 90)){
          $car_data["logo"] = $_SESSION['user_id'] ."-" .$fileName;
      } else {
          $error_msg .= "Não foi possível processar o logo em WebP. ";
          $car_data["logo"] = $previous_logo;
      }
  } else {
    $car_data["logo"] = $previous_logo;
  }

  if(isset($_FILES['picture']) && !empty($_FILES['picture']['tmp_name']) && (file_exists($_FILES['picture']['tmp_name']) || is_uploaded_file($_FILES['picture']['tmp_name']))){
      $fileName = $_FILES['picture']['name'];
      $fileExplode = explode(".",$fileName);
      $fileBase = preg_replace("/[^a-zA-Z0-9]/", "", $fileExplode[0]) ?: "car";
      $fileName = strtolower($fileBase) . mt_rand(1,10000).".webp";
      $filePath = $_FILES['picture']['tmp_name'];
      $upload_dir = "/octamotor/images/car/";

      $upload_path = $_SERVER['DOCUMENT_ROOT'] .$upload_dir .$_SESSION['user_id'] ."-" . $fileName;
      if(processAndSaveWebPImage($filePath, $upload_path, 800, 90)){
          $car_data["picture"] = $_SESSION['user_id'] ."-" .$fileName;
      } else {
          $error_msg .= "Não foi possível processar a imagem do carro em WebP. ";
          $car_data["picture"] = $previous_picture;
      }
  } else {
    $car_data["picture"] = $previous_picture;
  }

  if(isset($_FILES['suit']) && !empty($_FILES['suit']['tmp_name']) && (file_exists($_FILES['suit']['tmp_name']) || is_uploaded_file($_FILES['suit']['tmp_name']))){
      $fileName = $_FILES['suit']['name'];
      $fileExplode = explode(".",$fileName);
      $fileBase = preg_replace("/[^a-zA-Z0-9]/", "", $fileExplode[0]) ?: "suit";
      $fileName = strtolower($fileBase) . mt_rand(1,10000).".webp";
      $filePath = $_FILES['suit']['tmp_name'];
      $upload_dir = "/octamotor/images/suit/";

      $upload_path = $_SERVER['DOCUMENT_ROOT'] .$upload_dir .$_SESSION['user_id'] ."-" . $fileName;
      if(processAndSaveWebPImage($filePath, $upload_path, 450, 90)){
          $car_data["suit"] = $_SESSION['user_id'] ."-" .$fileName;
      } else {
          $error_msg .= "Não foi possível processar o uniforme em WebP. ";
          $car_data["suit"] = $previous_suit;
      }
  } else {
    $car_data["suit"] = $previous_suit;
  }

  if($_POST['driver1']){
    $driver1 = $_POST['driver1'];
  }

  if($_POST['driver2']){
    $driver2 = $_POST['driver2'];
  }

  //  var_dump($car_data);
  // //
  // die;
  // verify user_id, open_window, driver_owner, admin_status
  if(isset($_POST["id"]) && $_POST["id"] != ""){
    $id = $_POST["id"];

    if($car->isNotOwner($id, $_SESSION["user_id"]) && $_SESSION['admin_status'] == 0){
      die(json_encode(["success" => false, "error_msg" => "Usuário não é dono do carro", "new_car" => false]));
    }

    if($car->updateCar($id, $car_data)){
      $is_success = true;
      $error_msg = "Atualização realizada com sucesso!";
    } else {
      $is_success = false;
      $error_msg = "Houve um erro na solicitação. Caso o erro persista, entre em contato com os admins.";
    }
    $new_car = false;
  } else {

    if($car->insertCar($car_data)){
      $is_success = true;
      $error_msg = "Criação realizada com sucesso!";
    } else {
      $is_success = false;
      $error_msg = "Houve um erro na solicitação. Caso o erro persista, entre em contato com os admins.";
    }
    $new_car = true;
    $id = $odb->lastInsertId();
  }

  if(isset($driver1) && $driver1 != 0){
    $driver->updateDriverTeam($driver1, $id, 1);
  } else {
    $driver->fireDriver($id, 1);
  }
  if(isset($driver2)  && $driver2 != 0){
    $driver->updateDriverTeam($driver2, $id, 2);
  } else {
    $driver->fireDriver($id, 2);
  }



} else {
  $is_success = false;
  $error_msg = "Usuário não logado";
  $new_car = null;
}



die(json_encode(["success" => $is_success, "error_msg" => $error_msg, "new_car" => $new_car]));


 ?>
