<?php

ini_set( 'display_errors', true );
error_reporting( E_ALL );
session_start();
if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){

    require ($_SERVER['DOCUMENT_ROOT']."/pngquant/utility.php");
    $idLiga = $_POST['id'];
    $nomeLiga = $_POST['nomeLiga'];
    $tierLiga = $_POST['tierLiga'];
    $pais = $_POST['pais'];
    $error_msg = "";
    $new_logo_path = null;

    function imageImporter($file_name, $target_filename){
      $maxDim = 200;
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
        $background = imagecolorallocate($dst , 0, 0, 0);
        imagecolortransparent($dst, $background);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        //end changes
        imagecopyresampled( $dst, $src, 0, 0, 0, 0, $new_width, $new_height, $width, $height );
        imagedestroy( $src );
        imagepng( $dst, $target_filename ); // adjust format as needed
        imagedestroy( $dst );

    }

    if(isset($_FILES) && !empty($_FILES)){

        $logo_path = $_FILES['logo']['name'];
        $fileSize = $_FILES['logo']['size'];
        $filePath = $_FILES['logo']['tmp_name'];
        $fileType = $_FILES['logo']['type'];
        $correct_extensions = array("image/png","image/jpg","image/jpeg");
        $upload_dir = "/images/ligas/";
        $new_logo_path = $_SESSION['user_id'] ."-" . $logo_path;

        if($logo_path != "" && in_array($fileType,$correct_extensions) && $fileSize <= 2000000){


            $upload_path = $_SERVER['DOCUMENT_ROOT'] .$upload_dir .$_SESSION['user_id'] ."-" . $logo_path;
            imageImporter($filePath, $upload_path);
          //  $result = move_uploaded_file($filePath, $upload_path);
            //    if (!$result) {
                //    $error_msg .= "Não foi possível inserir o logo, erro na inserção.";
              //  }

        } else {
            $error_msg .= "Não foi possível inserir o logo. ";
            if($fileSize > 2000000){
                $error_msg .= "Arquivo deve ser menor que 2Mb.";
            }
            if($logo_path == ''){
                $error_msg .= "Falha no nome do arquivo.";
            }
            if(in_array($fileType,$correct_extensions) == false){
                $error_msg .= "Extensão ".$extension[1]." não é permitida.";
            }
        }
    }
    //estabelecer conexão com banco de dados
    include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/liga.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
    $database = new Database();
    $db = $database->getConnection();
    $liga = new Liga($db);


    //alterar arbitro
    if($liga->alterar($idLiga,$nomeLiga,$tierLiga,$pais,$new_logo_path)){
        $is_success = true;
        $error_msg .= "";
    } else {
        $is_success = false;
        $error_msg .= "Falha ao alterar liga no banco de dados";
    }

} else {
    $is_success = false;
    $error_msg .= "Usuário não tem acesso para realizar essa ação";
}

die(json_encode([ 'success'=> $is_success, 'error'=> $error_msg]));


?>
