<?php

// ini_set( 'display_errors', true );
// error_reporting( E_ALL );
session_start();
$error_msg = '';
if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){
    $idLiga = $_POST['idNovaLiga'];
    $idTime = $_POST['idTime'];

    //estabelecer conexão com banco de dados
    include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");
    $database = new Database();
    $db = $database->getConnection();
    $time = new Time($db);


    //mover time para outra liga
    if($time->moverLiga($idTime,$idLiga)){
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