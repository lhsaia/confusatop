<?php

// ini_set( 'display_errors', true );
// error_reporting( E_ALL );
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){

    require ($_SERVER['DOCUMENT_ROOT']."/pngquant/utility.php");
    $id = $_POST['id'];
    $nome = $_POST['nome'];
    $sede = $_POST['sede'];
	$ano = $_POST['ano'];
	$federacao = $_POST['federacao'];
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
        if($type != "image/png"){
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

    if(isset($_FILES) && !empty($_FILES)){

        $logo_path = $_FILES['logo']['name'];
        $fileSize = $_FILES['logo']['size'];
        $filePath = $_FILES['logo']['tmp_name'];
        $fileType = $_FILES['logo']['type'];
        $correct_extensions = array("image/png","image/jpg","image/jpeg", "image/webp");
        $upload_dir = "/images/competicoes/";
        $new_logo_path = $_SESSION['user_id'] ."-" . $logo_path;

        if($logo_path != "" && in_array($fileType,$correct_extensions) && $fileSize <= 2000000){


            $upload_path = $_SERVER['DOCUMENT_ROOT'] .$upload_dir .$_SESSION['user_id'] ."-" . $logo_path;
            imageImporter($filePath, $upload_path);

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
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/competicao_clube.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
    $database = new Database();
    $db = $database->getConnection();
    $competicao = new Competicao_clube($db);


    //alterar arbitro
    if($competicao->alterar($id,$nome,$sede,$ano,$federacao,$new_logo_path)){
        $is_success = true;
        $error_msg .= "";
    } else {
        $is_success = false;
        $error_msg .= "Falha ao alterar competição no banco de dados";
    }

} else {
    $is_success = false;
    $error_msg .= "Usuário não tem acesso para realizar essa ação";
}

die(json_encode([ 'success'=> $is_success, 'error'=> $error_msg]));


?>
