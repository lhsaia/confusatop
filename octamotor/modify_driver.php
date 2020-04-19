<?php

session_start();
// ini_set( 'display_errors', true );
// error_reporting( E_ALL );

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){
  require_once $_SERVER['DOCUMENT_ROOT']."/octamotor/config/database.php";
  require_once $_SERVER['DOCUMENT_ROOT']. "/octamotor/classes/driver.php";
  require ($_SERVER['DOCUMENT_ROOT']."/pngquant/utility.php");

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

  function imageImporter($file_name, $target_filename){
    $maxDim = 330;
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
      //$save_to_path = "uploads/compressed_file.png";
      if($type != "image/png"){
        $src = imagecreatefromstring( file_get_contents( $file_name ) );
      } else {
        $compressed_png_content = compress_png($file_name);
        $src = imagecreatefromstring($compressed_png_content);
      }

      //file_put_contents($save_to_path, $compressed_png_content);
      $dst = imagecreatetruecolor( $new_width, $new_height );
      //start changes
      $background = imagecolorallocate($dimg , 0, 0, 0);
      imagecolortransparent($dst, $background);
      imagealphablending($dst, false);
      imagesavealpha($dst, true);
      //end changes
      imagecopyresampled( $dst, $src, 0, 0, 0, 0, $new_width, $new_height, $width, $height );
      imagedestroy( $src );
      imagepng( $dst, $target_filename ); // adjust format as needed
      imagedestroy( $dst );

  }

  // tratamento e importação de imagem
  if(isset($_FILES['photo']) && !empty($_FILES['photo'])){
      $fileName = $_FILES['photo']['name'];
      $fileExplode = explode(".",$fileName);
      $fileName = $fileExplode[0] . mt_rand(1,10000)."." .$fileExplode[1];
      $filePath = $_FILES['photo']['tmp_name'];
      $fileType = $_FILES['photo']['type'];
      $fileExt = strtolower(end($fileExplode));
      $correct_extensions = array("image/png","image/jpg","image/jpeg");
      $upload_dir = "/octamotor/images/picture/";

      if($filePath != "" && in_array($fileType,$correct_extensions)){

          $upload_path = $_SERVER['DOCUMENT_ROOT'] .$upload_dir .$_SESSION['user_id'] ."-" . $fileName;
          imageImporter($filePath, $upload_path);
          $driver_data["photo"] = $_SESSION['user_id'] ."-" .$fileName;

      } else {

          $error_msg .= "Não foi possível inserir a foto. ";
          if($filePath == ''){
              $error_msg .= "Falha no nome do arquivo.";
          }
          if(in_array($fileType,$correct_extensions) == false){
              $error_msg .= "Extensão ".$fileExt." não é permitida.";
          }
          $driver_data["photo"] = $previous_photo;
      }
  } else {
    $driver_data["photo"] = $previous_photo;
  }

  if(isset($_FILES['helmet']) && !empty($_FILES['helmet'])){
      $fileName = $_FILES['helmet']['name'];
      $fileExplode = explode(".",$fileName);
      $fileName = $fileExplode[0] . mt_rand(1,10000)."." .$fileExplode[1];
      $filePath = $_FILES['helmet']['tmp_name'];
      $fileType = $_FILES['helmet']['type'];
      $fileExt = strtolower(end($fileExplode));
      $correct_extensions = array("image/png","image/jpg","image/jpeg");
      $upload_dir = "/octamotor/images/helmet/";
      if($filePath != "" && in_array($fileType,$correct_extensions)){

          $upload_path = $_SERVER['DOCUMENT_ROOT'] .$upload_dir .$_SESSION['user_id'] ."-" . $fileName;
          imageImporter($filePath, $upload_path);
          $driver_data["helmet"] = $_SESSION['user_id'] ."-" .$fileName;

      } else {

          $error_msg .= "Não foi possível inserir a foto. ";
          if($filePath == ''){
              $error_msg .= "Falha no nome do arquivo.";
          }
          if(in_array($fileType,$correct_extensions) == false){
              $error_msg .= "Extensão ".$fileExt." não é permitida.";
          }
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
