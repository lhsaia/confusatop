<?php

ini_set( 'display_errors', true );
error_reporting( E_ALL );
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

$is_success = false;
$error_msg = "";

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
    if (isset($_POST['sigla']) && trim($_POST['sigla']) !== '') {
        $time->sigla = trim($_POST['sigla']);
    }
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
        $ratio = $width/$height;
        if( $ratio > 1) {
          $new_width = (int) $maxDim;
          $new_height = (int) round($maxDim/$ratio);
        } else {
          $new_width = (int) round($maxDim*$ratio);
          $new_height = (int) $maxDim;
        }
    } else {
      $new_width = (int) $width;
      $new_height = (int) $height;
    }
        if($type != IMAGETYPE_PNG){
          $src = imagecreatefromstring( file_get_contents( $file_name ) );
        } else {
          $compressed_png_content = compress_png($file_name);
          $src = imagecreatefromstring($compressed_png_content);
        }

        $dst = imagecreatetruecolor( $new_width, $new_height );
        $background = imagecolorallocate($dst , 0, 0, 0);
        imagecolortransparent($dst, $background);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopyresampled( $dst, $src, 0, 0, 0, 0, $new_width, $new_height, (int)$width, (int)$height );
        imagedestroy( $src );
        imagepng( $dst, $target_filename );
        imagedestroy( $dst );

    }


    $allowed_image_exts = array("png", "jpg", "jpeg", "webp");
    $allowed_mimes = array("image/png", "image/jpg", "image/jpeg", "image/pjpeg", "image/x-png", "image/webp");

    if(isset($_FILES['escudo']) && is_array($_FILES['escudo']) && $_FILES['escudo']['error'] === UPLOAD_ERR_OK){
        $origName = $_FILES['escudo']['name'];
        $fileSize = $_FILES['escudo']['size'];
        $filePath = $_FILES['escudo']['tmp_name'];
        $fileExt = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $fileBase = pathinfo($origName, PATHINFO_FILENAME);
        $fileName = $fileBase . mt_rand(1, 10000) . "." . $fileExt;
        $upload_dir = "/images/escudos/";

        $mime = '';
        if (function_exists('mime_content_type') && file_exists($filePath)) {
            $mime = mime_content_type($filePath);
        } else if (isset($_FILES['escudo']['type'])) {
            $mime = $_FILES['escudo']['type'];
        }

        $isValidExt = in_array($fileExt, $allowed_image_exts);
        $isValidMime = empty($mime) || in_array($mime, $allowed_mimes);

        if($filePath != "" && $isValidExt && $isValidMime && $fileSize <= 2000000){
            $upload_path = $_SERVER['DOCUMENT_ROOT'] . $upload_dir . $_SESSION['user_id'] . "-" . $fileName;
            imageImporter($filePath, $upload_path);
            $time->escudo = $_SESSION['user_id'] . "-" . $fileName;
        } else {
            $error_msg .= "Não foi possível inserir o escudo. ";
            if($fileSize > 2000000){
                $error_msg .= "Arquivo deve ser menor que 2Mb. ";
            }
            if($filePath == ''){
                $error_msg .= "Falha no envio do arquivo. ";
            }
            if(!$isValidExt || !$isValidMime){
                $error_msg .= "Extensão ou formato '".$fileExt."' não é permitido. ";
            }
        }
    }

    if(isset($_FILES['uni1']) && is_array($_FILES['uni1']) && $_FILES['uni1']['error'] === UPLOAD_ERR_OK){
        $origName = $_FILES['uni1']['name'];
        $fileSize = $_FILES['uni1']['size'];
        $filePath = $_FILES['uni1']['tmp_name'];
        $fileExt = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $fileBase = pathinfo($origName, PATHINFO_FILENAME);
        $fileName = $fileBase . mt_rand(1, 10000) . "." . $fileExt;
        $upload_dir = "/images/uniformes/";

        $mime = '';
        if (function_exists('mime_content_type') && file_exists($filePath)) {
            $mime = mime_content_type($filePath);
        } else if (isset($_FILES['uni1']['type'])) {
            $mime = $_FILES['uni1']['type'];
        }

        $isValidExt = in_array($fileExt, $allowed_image_exts);
        $isValidMime = empty($mime) || in_array($mime, $allowed_mimes);

        if($filePath != "" && $isValidExt && $isValidMime && $fileSize <= 2000000){
            $upload_path = $_SERVER['DOCUMENT_ROOT'] . $upload_dir . $_SESSION['user_id'] . "-" . $fileName;
            imageImporter($filePath, $upload_path);
            $time->uniforme1 = $_SESSION['user_id'] . "-" . $fileName;
        } else {
            $error_msg .= "Não foi possível inserir o uniforme 1. ";
            if($fileSize > 2000000){
                $error_msg .= "Arquivo deve ser menor que 2Mb. ";
            }
            if($filePath == ''){
                $error_msg .= "Falha no envio do arquivo. ";
            }
            if(!$isValidExt || !$isValidMime){
                $error_msg .= "Extensão ou formato '".$fileExt."' não é permitido. ";
            }
        }
    }

    if(isset($_FILES['uni2']) && is_array($_FILES['uni2']) && $_FILES['uni2']['error'] === UPLOAD_ERR_OK){
        $origName = $_FILES['uni2']['name'];
        $fileSize = $_FILES['uni2']['size'];
        $filePath = $_FILES['uni2']['tmp_name'];
        $fileExt = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $fileBase = pathinfo($origName, PATHINFO_FILENAME);
        $fileName = $fileBase . mt_rand(1, 10000) . "." . $fileExt;
        $upload_dir = "/images/uniformes/";

        $mime = '';
        if (function_exists('mime_content_type') && file_exists($filePath)) {
            $mime = mime_content_type($filePath);
        } else if (isset($_FILES['uni2']['type'])) {
            $mime = $_FILES['uni2']['type'];
        }

        $isValidExt = in_array($fileExt, $allowed_image_exts);
        $isValidMime = empty($mime) || in_array($mime, $allowed_mimes);

        if($filePath != "" && $isValidExt && $isValidMime && $fileSize <= 2000000){
            $upload_path = $_SERVER['DOCUMENT_ROOT'] . $upload_dir . $_SESSION['user_id'] . "-" . $fileName;
            imageImporter($filePath, $upload_path);
            $time->uniforme2 = $_SESSION['user_id'] . "-" . $fileName;
        } else {
            $error_msg .= "Não foi possível inserir o uniforme 2. ";
            if($fileSize > 2000000){
                $error_msg .= "Arquivo deve ser menor que 2Mb. ";
            }
            if($filePath == ''){
                $error_msg .= "Falha no envio do arquivo. ";
            }
            if(!$isValidExt || !$isValidMime){
                $error_msg .= "Extensão ou formato '".$fileExt."' não é permitido. ";
            }
        }
    }

function imageImporterWebP($file_name, $target_filename){
      $maxDim = 180;
      list($width, $height, $type, $attr) = getimagesize( $file_name );
      if ( $width > $maxDim || $height > $maxDim ) {
        $ratio = $width/$height;
        if( $ratio > 1) {
          $new_width = (int) $maxDim;
          $new_height = (int) round($maxDim/$ratio);
        } else {
          $new_width = (int) round($maxDim*$ratio);
          $new_height = (int) $maxDim;
        }
    } else {
      $new_width = (int) $width;
      $new_height = (int) $height;
    }
        if($type == IMAGETYPE_PNG){
			$compressed_png_content = compress_png($file_name);
			$src = imagecreatefromstring($compressed_png_content);
        } else if ($type == 18 || $type == "") {
			$src = imagecreatefromwebp($file_name);
		} else {
            try {
                $src = imagecreatefromstring( file_get_contents( $file_name ) );
            } catch (Exception $e) {
                $src = imagecreatefromwebp($file_name);
            }
        }
        $dst = imagecreatetruecolor( $new_width, $new_height );
        $background = imagecolorallocate($dst , 0, 0, 0);
        imagecolortransparent($dst, $background);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopyresampled( $dst, $src, 0, 0, 0, 0, $new_width, $new_height, (int)$width, (int)$height );
        imagedestroy( $src );
		imagewebp($dst, $target_filename);
        imagedestroy( $dst );
    }

    if(isset($_FILES['mascote']) && is_array($_FILES['mascote']) && $_FILES['mascote']['error'] === UPLOAD_ERR_OK){
        $origName = $_FILES['mascote']['name'];
        $fileSize = $_FILES['mascote']['size'];
        $filePath = $_FILES['mascote']['tmp_name'];
        $fileExt = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $fileBase = pathinfo($origName, PATHINFO_FILENAME);
        $fileName = $fileBase . mt_rand(1, 10000) . ".webp";
        $upload_dir = "/images/mascotes/";

        $mime = '';
        if (function_exists('mime_content_type') && file_exists($filePath)) {
            $mime = mime_content_type($filePath);
        } else if (isset($_FILES['mascote']['type'])) {
            $mime = $_FILES['mascote']['type'];
        }

        $isValidExt = in_array($fileExt, $allowed_image_exts);
        $isValidMime = empty($mime) || in_array($mime, $allowed_mimes);

        if($filePath != "" && $isValidExt && $isValidMime && $fileSize <= 3000000){
            $upload_path = $_SERVER['DOCUMENT_ROOT'] . $upload_dir . $_SESSION['user_id'] . "-" . $fileName;
            imageImporterWebP($filePath, $upload_path);
            $time->mascote = $_SESSION['user_id'] . "-" . $fileName;
        } else {
            $error_msg .= "Não foi possível inserir o mascote. ";
            if($fileSize > 3000000){
                $error_msg .= "Arquivo deve ser menor que 3Mb. ";
            }
            if($filePath == ''){
                $error_msg .= "Falha no envio do arquivo. ";
            }
            if(!$isValidExt || !$isValidMime){
                $error_msg .= "Extensão ou formato '".$fileExt."' não é permitido. ";
            }
        }
    }


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
