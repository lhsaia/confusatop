<?php

ini_set( 'display_errors', true );
error_reporting( E_ALL );
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){

    include_once($_SERVER['DOCUMENT_ROOT']."/lib/image_helper.php");
    $idLiga = $_POST['id'];
    $nomeLiga = $_POST['nomeLiga'];
    $tierLiga = $_POST['tierLiga'];
    $limiteIdade = isset($_POST['limiteIdade']) ? $_POST['limiteIdade'] : null;
    $pais = $_POST['pais'];
    $error_msg = "";
    $new_logo_path = null;

    if(isset($_FILES['logo']) && !empty($_FILES['logo']['tmp_name']) && (file_exists($_FILES['logo']['tmp_name']) || is_uploaded_file($_FILES['logo']['tmp_name']))){
        $logo_path = $_FILES['logo']['name'];
        $fileSize = $_FILES['logo']['size'];
        $filePath = $_FILES['logo']['tmp_name'];
        $fileBase = pathinfo($logo_path, PATHINFO_FILENAME);
        $cleanBase = preg_replace('/[^A-Za-z0-9_-]/', '', $fileBase) ?: 'liga';
        $fileName = $cleanBase . mt_rand(1, 10000) . ".webp";
        $upload_dir = "/images/ligas/";

        if($fileSize <= 5000000){
            $upload_path = $_SERVER['DOCUMENT_ROOT'] . $upload_dir . $_SESSION['user_id'] . "-" . $fileName;
            $result = processAndSaveWebPImage($filePath, $upload_path, 512, 90);
            if ($result) {
                $new_logo_path = $_SESSION['user_id'] . "-" . $fileName;
            } else {
                $error_msg .= "Não foi possível processar o logo em WebP.";
            }
        } else {
            $error_msg .= "Não foi possível inserir o logo. Arquivo deve ser menor que 5Mb.";
        }
    }
    //estabelecer conexão com banco de dados
    include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/liga.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
    $database = new Database();
    $db = $database->getConnection();
    $liga = new Liga($db);


    //alterar liga
    if($liga->alterar($idLiga,$nomeLiga,$tierLiga,$pais,$new_logo_path,$limiteIdade)){
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
