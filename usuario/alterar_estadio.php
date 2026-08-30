<?php

ini_set( 'display_errors', true );
error_reporting( E_ALL );
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
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
    include_once($_SERVER['DOCUMENT_ROOT']."/lib/image_helper.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/estadio.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/clima.php");
    $database = new Database();
    $db = $database->getConnection();
    $estadio = new Estadio($db);
	
	if(isset($_FILES['foto']) && !empty($_FILES['foto']['tmp_name']) && (file_exists($_FILES['foto']['tmp_name']) || is_uploaded_file($_FILES['foto']['tmp_name']))){
        $fileName = $_FILES['foto']['name'];
        $fileExplode = explode(".",$fileName);
        $fileBase = preg_replace("/[^a-zA-Z0-9]/", "", $fileExplode[0]) ?: "estadio";
        $fileName = strtolower($fileBase) . mt_rand(1,10000).".webp";
        $fileSize = $_FILES['foto']['size'];
        $filePath = $_FILES['foto']['tmp_name'];
        $upload_dir = "/images/estadios/";

        if($fileSize <= 10485760){
            $upload_path = $_SERVER['DOCUMENT_ROOT'] .$upload_dir .$_SESSION['user_id'] ."-" . $fileName;
            if(processAndSaveWebPImage($filePath, $upload_path, 1200, 90)){
                $estadio->foto = $_SESSION['user_id'] ."-" .$fileName;
            } else {
                $error_msg .= "Não foi possível processar a imagem do estádio em WebP.";
            }
        } else {
            $error_msg .= "Arquivo deve ser menor que 10Mb.";
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
