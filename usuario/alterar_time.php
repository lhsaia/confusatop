<?php

ini_set( 'display_errors', true );
error_reporting( E_ALL );
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

$is_success = false;
$error_msg = "";

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){

    //estabelecer conexão com banco de dados
    include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/lib/image_helper.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
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

    $allowed_image_exts = array("png", "jpg", "jpeg", "webp");
    $allowed_mimes = array("image/png", "image/jpg", "image/jpeg", "image/pjpeg", "image/x-png", "image/webp");

    if(isset($_FILES['escudo']) && is_array($_FILES['escudo']) && $_FILES['escudo']['error'] === UPLOAD_ERR_OK){
        $origName = $_FILES['escudo']['name'];
        $fileSize = $_FILES['escudo']['size'];
        $filePath = $_FILES['escudo']['tmp_name'];
        $fileExt = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $fileBase = pathinfo($origName, PATHINFO_FILENAME);
        $cleanBase = preg_replace('/[^A-Za-z0-9_-]/', '', $fileBase) ?: 'escudo';
        $fileName = $cleanBase . mt_rand(1, 10000) . ".webp";
        $upload_dir = "/images/escudos/";

        $mime = '';
        if (function_exists('mime_content_type') && file_exists($filePath)) {
            $mime = mime_content_type($filePath);
        } else if (isset($_FILES['escudo']['type'])) {
            $mime = $_FILES['escudo']['type'];
        }

        $isValidExt = in_array($fileExt, $allowed_image_exts);
        $isValidMime = empty($mime) || in_array($mime, $allowed_mimes);

        if($filePath != "" && $isValidExt && $isValidMime && $fileSize <= 5000000){
            $upload_path = $_SERVER['DOCUMENT_ROOT'] . $upload_dir . $_SESSION['user_id'] . "-" . $fileName;
            $result = processAndSaveWebPImage($filePath, $upload_path, 512, 90);
            if ($result) {
                $time->escudo = $_SESSION['user_id'] . "-" . $fileName;
            } else {
                $error_msg .= "Não foi possível processar o escudo em WebP. ";
            }
        } else {
            $error_msg .= "Não foi possível inserir o escudo. ";
            if($fileSize > 5000000){
                $error_msg .= "Arquivo deve ser menor que 5Mb. ";
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
        $cleanBase = preg_replace('/[^A-Za-z0-9_-]/', '', $fileBase) ?: 'uni1';
        $fileName = $cleanBase . mt_rand(1, 10000) . ".webp";
        $upload_dir = "/images/uniformes/";

        $mime = '';
        if (function_exists('mime_content_type') && file_exists($filePath)) {
            $mime = mime_content_type($filePath);
        } else if (isset($_FILES['uni1']['type'])) {
            $mime = $_FILES['uni1']['type'];
        }

        $isValidExt = in_array($fileExt, $allowed_image_exts);
        $isValidMime = empty($mime) || in_array($mime, $allowed_mimes);

        if($filePath != "" && $isValidExt && $isValidMime && $fileSize <= 5000000){
            $upload_path = $_SERVER['DOCUMENT_ROOT'] . $upload_dir . $_SESSION['user_id'] . "-" . $fileName;
            $result = processAndSaveWebPImage($filePath, $upload_path, 600, 90);
            if ($result) {
                $time->uniforme1 = $_SESSION['user_id'] . "-" . $fileName;
            } else {
                $error_msg .= "Não foi possível processar o uniforme 1 em WebP. ";
            }
        } else {
            $error_msg .= "Não foi possível inserir o uniforme 1. ";
            if($fileSize > 5000000){
                $error_msg .= "Arquivo deve ser menor que 5Mb. ";
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
        $cleanBase = preg_replace('/[^A-Za-z0-9_-]/', '', $fileBase) ?: 'uni2';
        $fileName = $cleanBase . mt_rand(1, 10000) . ".webp";
        $upload_dir = "/images/uniformes/";

        $mime = '';
        if (function_exists('mime_content_type') && file_exists($filePath)) {
            $mime = mime_content_type($filePath);
        } else if (isset($_FILES['uni2']['type'])) {
            $mime = $_FILES['uni2']['type'];
        }

        $isValidExt = in_array($fileExt, $allowed_image_exts);
        $isValidMime = empty($mime) || in_array($mime, $allowed_mimes);

        if($filePath != "" && $isValidExt && $isValidMime && $fileSize <= 5000000){
            $upload_path = $_SERVER['DOCUMENT_ROOT'] . $upload_dir . $_SESSION['user_id'] . "-" . $fileName;
            $result = processAndSaveWebPImage($filePath, $upload_path, 600, 90);
            if ($result) {
                $time->uniforme2 = $_SESSION['user_id'] . "-" . $fileName;
            } else {
                $error_msg .= "Não foi possível processar o uniforme 2 em WebP. ";
            }
        } else {
            $error_msg .= "Não foi possível inserir o uniforme 2. ";
            if($fileSize > 5000000){
                $error_msg .= "Arquivo deve ser menor que 5Mb. ";
            }
            if($filePath == ''){
                $error_msg .= "Falha no envio do arquivo. ";
            }
            if(!$isValidExt || !$isValidMime){
                $error_msg .= "Extensão ou formato '".$fileExt."' não é permitido. ";
            }
        }
    }

    if(isset($_FILES['mascote']) && is_array($_FILES['mascote']) && $_FILES['mascote']['error'] === UPLOAD_ERR_OK){
        $origName = $_FILES['mascote']['name'];
        $fileSize = $_FILES['mascote']['size'];
        $filePath = $_FILES['mascote']['tmp_name'];
        $fileExt = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $fileBase = pathinfo($origName, PATHINFO_FILENAME);
        $cleanBase = preg_replace('/[^A-Za-z0-9_-]/', '', $fileBase) ?: 'mascote';
        $fileName = $cleanBase . mt_rand(1, 10000) . ".webp";
        $upload_dir = "/images/mascotes/";

        $mime = '';
        if (function_exists('mime_content_type') && file_exists($filePath)) {
            $mime = mime_content_type($filePath);
        } else if (isset($_FILES['mascote']['type'])) {
            $mime = $_FILES['mascote']['type'];
        }

        $isValidExt = in_array($fileExt, $allowed_image_exts);
        $isValidMime = empty($mime) || in_array($mime, $allowed_mimes);

        if($filePath != "" && $isValidExt && $isValidMime && $fileSize <= 5000000){
            $upload_path = $_SERVER['DOCUMENT_ROOT'] . $upload_dir . $_SESSION['user_id'] . "-" . $fileName;
            $result = processAndSaveWebPImage($filePath, $upload_path, 400, 90);
            if ($result) {
                $time->mascote = $_SESSION['user_id'] . "-" . $fileName;
            } else {
                $error_msg .= "Não foi possível processar o mascote em WebP. ";
            }
        } else {
            $error_msg .= "Não foi possível inserir o mascote. ";
            if($fileSize > 5000000){
                $error_msg .= "Arquivo deve ser menor que 5Mb. ";
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
