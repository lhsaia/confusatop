<?php

// ini_set( 'display_errors', true );
// error_reporting( E_ALL );
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){
    $idPais = $_POST['id'];
    $nomePais = $_POST['nomePais'];
    $siglaPais = $_POST['siglaPais'];
    $federacaoPais = $_POST['federacaoPais'];
    $ranqueavel = $_POST['ranqueavel'];
	$latitude = $_POST['latitude'];
	$longitude = $_POST['longitude'];
    $error_msg = "";
    $new_logo_path = null;

    //estabelecer conexão com banco de dados
    include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/lib/image_helper.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
    $database = new Database();
    $db = $database->getConnection();
    $pais = new Pais($db);

    //alterar pais
    if($pais->alterar($idPais,$nomePais,$siglaPais,$federacaoPais, $ranqueavel,$new_logo_path, $latitude, $longitude)){
        if(isset($_FILES['logo']) && !empty($_FILES['logo']['tmp_name']) && (file_exists($_FILES['logo']['tmp_name']) || is_uploaded_file($_FILES['logo']['tmp_name']))){

            $logo_path = $_FILES['logo']['name'];
            $fileSize = $_FILES['logo']['size'];
            $filePath = $_FILES['logo']['tmp_name'];
            $upload_dir = "/images/bandeiras/";
            $new_logo_path = $siglaPais . ".webp";

            if($fileSize <= 5000000){
                $upload_path = $_SERVER['DOCUMENT_ROOT'] . $upload_dir . $new_logo_path;
                if(file_exists($upload_path)){
                    @unlink($upload_path);
                }
                if(processAndSaveWebPImage($filePath, $upload_path, 120, 90)){
                    $pais->atualizarBandeira($idPais, $new_logo_path);
                } else {
                    $error_msg .= "Não foi possível processar a imagem da bandeira em WebP.";
                }
            } else {
                $error_msg .= "Arquivo da bandeira deve ser menor que 5Mb.";
            }
        }

        $is_success = true;
        $error_msg .= "";
    } else {
        $is_success = false;
        $error_msg .= "Falha ao alterar país no banco de dados. Verifique se a sigla já não existe.";
    }

} else {
    $is_success = false;
    $error_msg .= "Usuário não tem acesso para realizar essa ação";
}

die(json_encode([ 'success'=> $is_success, 'error'=> $error_msg]));


?>
