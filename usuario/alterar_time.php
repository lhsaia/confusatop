<?php

ini_set( 'display_errors', true );
error_reporting( E_ALL );
session_start();
if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){

    //estabelecer conexão com banco de dados
    include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
      require ($_SERVER['DOCUMENT_ROOT']."/pngquant/utility.php");
    $database = new Database();
    $db = $database->getConnection();
    $time = new Time($db);
    $usuario = new Usuario($db);
    $pais = new Pais($db);

    if(!$pais->checarDono($_POST['pais'], $_SESSION['user_id'])){
        die(json_encode([ 'success'=> false, 'error'=> "Você não é dono desse país"]));
    }

    $time->id = $_POST['id'];
    $time->nome = $_POST['nomeTime'];
    $time->estadio = $_POST['estadio'];
    $time->uniforme1cor1 = $_POST['uni1cor1'];
    $time->uniforme1cor2 = $_POST['uni1cor2'];
    $time->uniforme1cor3 = $_POST['uni1cor3'];
    $time->uniforme2cor1 = $_POST['uni2cor1'];
    $time->uniforme2cor2 = $_POST['uni2cor2'];
    $time->uniforme2cor3 = $_POST['uni2cor3'];
    $time->maxTorcedores = $_POST['maxTorcedores'];
    $time->fidelidade = $_POST['fidelidade'];
    $time->pais = $_POST['pais'];
    $time->liga = $_POST['liga'];

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


    if(isset($_FILES['escudo']) && !empty($_FILES['escudo'])){
        $fileName = $_FILES['escudo']['name'];
        $fileExplode = explode(".",$fileName);
        $fileName = $fileExplode[0] . mt_rand(1,10000)."." .$fileExplode[1];
        $fileSize = $_FILES['escudo']['size'];
        $filePath = $_FILES['escudo']['tmp_name'];
        $fileType = $_FILES['escudo']['type'];
        $fileExt = strtolower( end($fileExplode));
        $correct_extensions = array("image/png","image/jpg","image/jpeg");
        $upload_dir = "/images/escudos/";

        if($filePath != "" && in_array($fileType,$correct_extensions) && $fileSize <= 2000000){

            $upload_path = $_SERVER['DOCUMENT_ROOT'] .$upload_dir .$_SESSION['user_id'] ."-" . $fileName;
            imageImporter($filePath, $upload_path);
            //$result = move_uploaded_file($filePath, $upload_path);
              //  if (!$result) {
                //    $error_msg .= "Não foi possível inserir o escudo, erro na inserção.";

              //  } else {
                    $time->escudo = $_SESSION['user_id'] ."-" .$fileName;
              //  }

            //$fileData = file_get_contents($filePath);
            //$time->escudo = base64_encode($fileData).".".$fileExt;

        } else {

            $error_msg .= "Não foi possível inserir o escudo. ";
            if($fileSize > 2000000){
                $error_msg .= "Arquivo deve ser menor que 2Mb.";
            }
            if($filePath == ''){
                $error_msg .= "Falha no nome do arquivo.";
            }
            if(in_array($fileType,$correct_extensions) == false){
                $error_msg .= "Extensão ".$fileExt." não é permitida.";
            }
        }
    }

    if(isset($_FILES['uni1']) && !empty($_FILES['uni1'])){
        $fileName = $_FILES['uni1']['name'];
        $fileExplode = explode(".",$fileName);
        $fileName = $fileExplode[0] . mt_rand(1,10000)."." .$fileExplode[1];
        $fileSize = $_FILES['uni1']['size'];
        $filePath = $_FILES['uni1']['tmp_name'];
        $fileType = $_FILES['uni1']['type'];
        $fileExt = strtolower( end($fileExplode));
        $correct_extensions = array("image/png","image/jpg","image/jpeg");
        $upload_dir = "/images/uniformes/";

        if($filePath != "" && in_array($fileType,$correct_extensions) && $fileSize <= 2000000){

                     // $fileData = file_get_contents($filePath);
           // $time->uniforme1 = base64_encode($fileData).".".$fileExt;
           $upload_path = $_SERVER['DOCUMENT_ROOT'] .$upload_dir .$_SESSION['user_id'] ."-" . $fileName;
           imageImporter($filePath, $upload_path);
           //$result = move_uploaded_file($filePath, $upload_path);
            //   if (!$result) {
              //     $error_msg .= "Não foi possível inserir o uniforme, erro na inserção.";

          //     } else {
                   $time->uniforme1 = $_SESSION['user_id'] ."-" .$fileName;
          //     }


        } else {
            $error_msg .= "Não foi possível inserir o uniforme 1. ";
            if($fileSize > 2000000){
                $error_msg .= "Arquivo deve ser menor que 2Mb.";
            }
            if($filePath == ''){
                $error_msg .= "Falha no nome do arquivo.";
            }
            if(in_array($fileType,$correct_extensions) == false){
                $error_msg .= "Extensão ".$fileExt." não é permitida.";
            }
        }
    }

    if(isset($_FILES['uni2']) && !empty($_FILES['uni2'])){
        $fileName = $_FILES['uni2']['name'];
        $fileExplode = explode(".",$fileName);
        $fileName = $fileExplode[0] . mt_rand(1,10000) ."." .$fileExplode[1];
        $fileSize = $_FILES['uni2']['size'];
        $filePath = $_FILES['uni2']['tmp_name'];
        $fileType = $_FILES['uni2']['type'];
        $fileExt = strtolower( end($fileExplode));
        $correct_extensions = array("image/png","image/jpg","image/jpeg");
        $upload_dir = "/images/uniformes/";

        if($filePath != "" && in_array($fileType,$correct_extensions) && $fileSize <= 2000000){

                        //$fileData = file_get_contents($filePath);
            //$time->uniforme2 = base64_encode($fileData).".".$fileExt;
            $upload_path = $_SERVER['DOCUMENT_ROOT'] .$upload_dir .$_SESSION['user_id'] ."-" . $fileName ;
            imageImporter($filePath, $upload_path);
          //  $result = move_uploaded_file($filePath, $upload_path);
            //    if (!$result) {
              //      $error_msg .= "Não foi possível inserir o uniforme, erro na inserção.";

                //} else {
                    $time->uniforme2 = $_SESSION['user_id'] ."-" .$fileName;
                //}


        } else {
            $error_msg .= "Não foi possível inserir o uniforme 2. ";
            if($fileSize > 2000000){
                $error_msg .= "Arquivo deve ser menor que 2Mb.";
            }
            if($filePath == ''){
                $error_msg .= "Falha no nome do arquivo.";
            }
            if(in_array($fileType,$correct_extensions) == false){
                $error_msg .= "Extensão ".$fileExt." não é permitida.";
            }
        }
    }

    // echo '<pre>';
    // var_dump($time);
    // echo '</pre>';


    // alterar time
    if($time->alterar()){
        $is_success = true;
        $error_msg .= "";
        $usuario->atualizarAlteracao($_SESSION['user_id']);
    } else {
        $is_success = false;
        $error_msg .= "Falha ao alterar time no banco de dados";
    }

} else {
    $is_success = false;
    $error_msg .= "Usuário não tem acesso para realizar essa ação";
}

die(json_encode([ 'success'=> $is_success, 'error'=> $error_msg]));


?>
