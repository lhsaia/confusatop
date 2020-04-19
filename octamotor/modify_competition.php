<?php

session_start();
// ini_set( 'display_errors', true );
// error_reporting( E_ALL );

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){
  require_once $_SERVER['DOCUMENT_ROOT']."/octamotor/config/database.php";
  require_once $_SERVER['DOCUMENT_ROOT']. "/octamotor/classes/competition.php";
  require ($_SERVER['DOCUMENT_ROOT']."/pngquant/utility.php");

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
      $upload_dir = "/octamotor/images/competition/";

      if($filePath != "" && in_array($fileType,$correct_extensions)){

          $upload_path = $_SERVER['DOCUMENT_ROOT'] .$upload_dir .$_SESSION['user_id'] ."-" . $fileName;
          imageImporter($filePath, $upload_path,450);
          $competition_data["logo"] = $_SESSION['user_id'] ."-" .$fileName;

      } else {

          $error_msg .= "Não foi possível inserir o logo.";
          if($filePath == ''){
              $error_msg .= "Falha no nome do arquivo.";
          }
          if(in_array($fileType,$correct_extensions) == false){
              $error_msg .= "Extensão ".$fileExt." não é permitida.";
          }
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
