<?php

ini_set( 'display_errors', true );
error_reporting( E_ALL );
session_start();
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
    $new_logo_path = null;

    //estabelecer conexão com banco de dados
    include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");
	require ($_SERVER['DOCUMENT_ROOT']."/pngquant/utility.php");
	require_once $_SERVER['DOCUMENT_ROOT'].'/lib/htmlpurifier/library/HTMLPurifier.auto.php';
    
    $purifier = new HTMLPurifier();
    $clean_html = $purifier->purify($sobre_texto);
	
    $database = new Database();
    $db = $database->getConnection();
    $time = new Time($db);

    //alterar arbitro
    if($time->alterarSobre($idTime,$cidade,$fundacao,$apelido,$patrocinio,$material_esportivo,$titulos,$sobre_titulo,$sobre_subtitulo,$clean_html)){
        $is_success = true;
        $error_msg .= "";
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
