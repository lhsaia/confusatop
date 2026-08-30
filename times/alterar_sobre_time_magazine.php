<?php

ini_set( 'display_errors', true );
error_reporting( E_ALL );
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){

    $idTime = $_POST['id'];
	$cidade = $_POST['cidade'];
	$fundacao = $_POST['fundacao'];
	$apelido = $_POST['apelido'];
	$patrocinio = $_POST['patrocinio'];
	$material_esportivo = $_POST['material_esportivo'];
	$titulos = $_POST['titulos'];
	$sobre_titulo = $_POST['sobre_titulo'];
	$sobre_subtitulo = $_POST['sobre_subtitulo'];
	$sobre_texto = $_POST['sobre_texto'];
    $error_msg = "";
    $foto_destaque_nome = null;

    //estabelecer conexão com banco de dados
    include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");
	require_once $_SERVER['DOCUMENT_ROOT'].'/lib/htmlpurifier/library/HTMLPurifier.auto.php';
    
    $purifier = new HTMLPurifier();
    $clean_html = $purifier->purify($sobre_texto);
    include_once($_SERVER['DOCUMENT_ROOT']."/lib/image_helper.php");
    $database = new Database();
    $db = $database->getConnection();
    $time = new Time($db);

    if(isset($_FILES['foto_destaque']) && !empty($_FILES['foto_destaque']['tmp_name'])){
        $fileName = $_FILES['foto_destaque']['name'];
        $fileExplode = explode(".", $fileName);
        $fileExt = strtolower(end($fileExplode));
        $newFileName = $_SESSION['user_id'] . "-" . time() . "-" . mt_rand(1,1000) . ".webp";
        $fileSize = $_FILES['foto_destaque']['size'];
        $filePath = $_FILES['foto_destaque']['tmp_name'];
        $fileType = $_FILES['foto_destaque']['type'];
        
        $correct_extensions = array("image/png","image/jpg","image/jpeg","image/webp");
        $upload_dir = "/images/destaques/";

        if($filePath != "" && (in_array($fileType, $correct_extensions) || in_array("image/".$fileExt, $correct_extensions)) && $fileSize <= 8000000){
            $upload_path = $_SERVER['DOCUMENT_ROOT'] . $upload_dir . $newFileName;
            processAndSaveWebPImage($filePath, $upload_path, 1200, 90);
            $foto_destaque_nome = $newFileName;
        } else {
            $error_msg .= "Não foi possível enviar a imagem de destaque. ";
            if($fileSize > 8000000){
                $error_msg .= "Arquivo deve ser menor que 8Mb.";
            }
            if($filePath == ''){
                $error_msg .= "Falha no arquivo.";
            }
        }
    }

    if($time->alterarSobreMagazine($idTime, $cidade, $fundacao, $apelido, $patrocinio, $material_esportivo, $titulos, $sobre_titulo, $sobre_subtitulo, $clean_html, $foto_destaque_nome)){
        $is_success = true;
    } else {
        $is_success = false;
        $error_msg .= "Falha ao alterar dados no banco de dados";
    }

} else {
    $is_success = false;
    $error_msg .= "Usuário não autorizado";
}

die(json_encode([ 'success'=> $is_success, 'error'=> $error_msg]));

?>
