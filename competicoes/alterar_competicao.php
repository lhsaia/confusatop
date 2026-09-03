<?php

// ini_set( 'display_errors', true );
// error_reporting( E_ALL );
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){

    include_once($_SERVER['DOCUMENT_ROOT']."/lib/image_helper.php");
    $id = $_POST['id'];
    $nome = $_POST['nome'];
    $sede = $_POST['sede'];
    $ano = $_POST['ano'];
    $federacao = $_POST['federacao'];
    $tipo = isset($_POST['tipo']) ? intval($_POST['tipo']) : null;
    $error_msg = "";
    $new_logo_path = null;

    if(isset($_FILES['logo']) && !empty($_FILES['logo']['tmp_name']) && (file_exists($_FILES['logo']['tmp_name']) || is_uploaded_file($_FILES['logo']['tmp_name']))){
        $logo_path = $_FILES['logo']['name'];
        $fileSize = $_FILES['logo']['size'];
        $filePath = $_FILES['logo']['tmp_name'];
        $fileExplode = explode(".", $logo_path);
        $fileBase = preg_replace("/[^a-zA-Z0-9]/", "", $fileExplode[0]) ?: "competicao";
        $fileName = strtolower($fileBase) . mt_rand(1, 10000) . ".webp";
        $upload_dir = "/images/competicoes/";

        if($fileSize <= 5000000){
            $upload_path = $_SERVER['DOCUMENT_ROOT'] . $upload_dir . $_SESSION['user_id'] . "-" . $fileName;
            if(processAndSaveWebPImage($filePath, $upload_path, 300, 90)){
                $new_logo_path = $_SESSION['user_id'] . "-" . $fileName;
            } else {
                $error_msg .= "Não foi possível processar o logo em WebP.";
            }
        } else {
            $error_msg .= "Arquivo de imagem deve ser menor que 5MB.";
        }
    }
    //estabelecer conexão com banco de dados
    include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/competicao_clube.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
    $database = new Database();
    $db = $database->getConnection();
    $competicao = new Competicao_clube($db);


    //alterar competicao
    if($competicao->alterar($id,$nome,$sede,$ano,$federacao,$new_logo_path, $tipo)){
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
