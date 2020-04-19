<?php

session_start();
// ini_set( 'display_errors', true );
// error_reporting( E_ALL );

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){
  require_once $_SERVER['DOCUMENT_ROOT']."/octamotor/config/database.php";
  require_once $_SERVER['DOCUMENT_ROOT']. "/octamotor/classes/driver.php";
  require_once $_SERVER['DOCUMENT_ROOT']. "/octamotor/classes/car.php";
  require ($_SERVER['DOCUMENT_ROOT']."/pngquant/utility.php");

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

  function imageImporter($file_name, $target_filename, $maxDim){
    list($width, $height, $type, $attr) = getimagesize( $file_name );
    if ( $width > $maxDim || $height > $maxDim ) {
    //  $target_filename = $file_name;
      $ratio = $width/$height;
      if( $ratio > 1) {
        $new_width = $maxDim;
        $new_height = $maxDim/$ratio;
      } else {
        $new_width = $maxDim*$ratio;
        $new_height = $maxDim;
      }
  } else {
    $new_width = $width;
    $new_height = $height;
  }

    $dst = imagecreatetruecolor( $new_width, $new_height );
      //$save_to_path = "uploads/compressed_file.png";
      if($type != 3){
        $src = imagecreatefromstring( file_get_contents( $file_name ) );
      } else {
        $compressed_png_content = compress_png($file_name);
        $src = imagecreatefromstring($compressed_png_content);
        imagecolortransparent($dst, imagecolorallocatealpha($dst, 0, 0, 0, 127));
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
      }

      imagecopyresampled( $dst, $src, 0, 0, 0, 0, $new_width, $new_height, $width, $height );
      imagedestroy( $src );
      imagepng( $dst, $target_filename ); // adjust format as needed
      imagedestroy( $dst );

  }

  // tratamento e importação de imagem
  if(isset($_FILES['logo']) && !empty($_FILES['logo'])){
      $fileName = $_FILES['logo']['name'];
      $fileExplode = explode(".",$fileName);
      $fileName = $fileExplode[0] . mt_rand(1,10000)."." .$fileExplode[1];
      $filePath = $_FILES['logo']['tmp_name'];
      $fileType = $_FILES['logo']['type'];
      $fileExt = strtolower(end($fileExplode));
      $correct_extensions = array("image/png","image/jpg","image/jpeg");
      $upload_dir = "/octamotor/images/car_logo/";

      if($filePath != "" && in_array($fileType,$correct_extensions)){

          $upload_path = $_SERVER['DOCUMENT_ROOT'] .$upload_dir .$_SESSION['user_id'] ."-" . $fileName;
          imageImporter($filePath, $upload_path,200);
          $car_data["logo"] = $_SESSION['user_id'] ."-" .$fileName;

      } else {

          $error_msg .= "Não foi possível inserir o logo. ";
          if($filePath == ''){
              $error_msg .= "Falha no nome do arquivo.";
          }
          if(in_array($fileType,$correct_extensions) == false){
              $error_msg .= "Extensão ".$fileExt." não é permitida.";
          }
          $car_data["logo"] = $previous_logo;
      }
  } else {
    $car_data["logo"] = $previous_logo;
  }

  if(isset($_FILES['picture']) && !empty($_FILES['picture'])){
      $fileName = $_FILES['picture']['name'];
      $fileExplode = explode(".",$fileName);
      $fileName = $fileExplode[0] . mt_rand(1,10000)."." .$fileExplode[1];
      $filePath = $_FILES['picture']['tmp_name'];
      $fileType = $_FILES['picture']['type'];
      $fileExt = strtolower(end($fileExplode));
      $correct_extensions = array("image/png","image/jpg","image/jpeg");
      $upload_dir = "/octamotor/images/car/";
      if($filePath != "" && in_array($fileType,$correct_extensions)){

          $upload_path = $_SERVER['DOCUMENT_ROOT'] .$upload_dir .$_SESSION['user_id'] ."-" . $fileName;
          imageImporter($filePath, $upload_path, 700);
          $car_data["picture"] = $_SESSION['user_id'] ."-" .$fileName;

      } else {

          $error_msg .= "Não foi possível inserir a imagem. ";
          if($filePath == ''){
              $error_msg .= "Falha no nome do arquivo.";
          }
          if(in_array($fileType,$correct_extensions) == false){
              $error_msg .= "Extensão ".$fileExt." não é permitida.";
          }
          $car_data["picture"] = $previous_picture;
      }
  } else {
    $car_data["picture"] = $previous_picture;
  }


  if(isset($_FILES['suit']) && !empty($_FILES['suit'])){
      $fileName = $_FILES['suit']['name'];
      $fileExplode = explode(".",$fileName);
      $fileName = $fileExplode[0] . mt_rand(1,10000)."." .$fileExplode[1];
      $filePath = $_FILES['suit']['tmp_name'];
      $fileType = $_FILES['suit']['type'];
      $fileExt = strtolower(end($fileExplode));
      $correct_extensions = array("image/png","image/jpg","image/jpeg");
      $upload_dir = "/octamotor/images/suit/";
      if($filePath != "" && in_array($fileType,$correct_extensions)){

          $upload_path = $_SERVER['DOCUMENT_ROOT'] .$upload_dir .$_SESSION['user_id'] ."-" . $fileName;
          imageImporter($filePath, $upload_path, 450);
          $car_data["suit"] = $_SESSION['user_id'] ."-" .$fileName;

      } else {

          $error_msg .= "Não foi possível inserir a imagem. ";
          if($filePath == ''){
              $error_msg .= "Falha no nome do arquivo.";
          }
          if(in_array($fileType,$correct_extensions) == false){
              $error_msg .= "Extensão ".$fileExt." não é permitida.";
          }
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
