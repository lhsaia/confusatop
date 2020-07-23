<?php

ini_set( 'display_errors', true );
error_reporting( E_ALL );
session_start();
if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){

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
    $database = new Database();
    $db = $database->getConnection();
    $estadio = new Estadio($db);


    //alterar arbitro
    if($estadio->alterar($idEstadio,$nomeEstadio,$capacidade,$pais,$altitude, $caldeirao, $clima)){
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
