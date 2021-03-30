<?php

ini_set( 'display_errors', true );
error_reporting( E_ALL );
session_start();
if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){

$localizacao_foto = null;
    $idEstadio = $_POST['id'];
    $nomeEstadio = $_POST['nomeEstadio'];
    $capacidade = $_POST['capacidade'];
    $pais = $_POST['pais'];
	$altitude = $_POST['altitude'];
	$caldeirao = $_POST['caldeirao'];
	$clima = $_POST['clima'];
    $error_msg = "";

    //estabelecer conexão com banco de dados
    include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/estadio.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/clima.php");
	require ($_SERVER['DOCUMENT_ROOT']."/pngquant/utility.php");
    $database = new Database();
    $db = $database->getConnection();
    $estadio = new Estadio($db);
	
	function imageImporter($file_name, $target_filename){
      $maxDim = 180;
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
        if($type == "image/png"){
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
        //imagepng( $dst, $target_filename ); // adjust format as needed
		imagewebp($dst, $target_filename);
        imagedestroy( $dst );

    }

	if(isset($_FILES['foto']) && !empty($_FILES['foto'])){
        $fileName = $_FILES['foto']['name'];
        $fileExplode = explode(".",$fileName);
        $fileName = $fileExplode[0] . mt_rand(1,10000).".webp";// .$fileExplode[1];
        $fileSize = $_FILES['foto']['size'];
        $filePath = $_FILES['foto']['tmp_name'];
        $fileType = $_FILES['foto']['type'];
        $fileExt = strtolower( end($fileExplode));
        $correct_extensions = array("image/png","image/jpg","image/jpeg", "image/webp");
        $upload_dir = "/images/estadios/";

        if($filePath != "" && in_array($fileType,$correct_extensions) && $fileSize <= 2000000){

            $upload_path = $_SERVER['DOCUMENT_ROOT'] .$upload_dir .$_SESSION['user_id'] ."-" . $fileName;
            imageImporter($filePath, $upload_path);
            $localizacao_foto = $_SESSION['user_id'] ."-" .$fileName;


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
	
    //alterar arbitro
    if($estadio->alterar($idEstadio,$nomeEstadio,$capacidade,$pais,$altitude, $caldeirao, $clima, $localizacao_foto)){
        $is_success = true;
        $error_msg .= "";
    } else {
        $is_success = false;
        $error_msg .= "Falha ao alterar estádio no banco de dados";
    }

} else {
    $is_success = false;
    $error_msg .= "Usuário não tem acesso para realizar essa ação";
}

die(json_encode([ 'success'=> $is_success, 'error'=> $error_msg]));


?>
