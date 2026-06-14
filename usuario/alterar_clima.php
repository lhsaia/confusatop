<?php

ini_set( 'display_errors', true );
error_reporting( E_ALL );
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){

    $id = $_POST['id'];
    $nome = $_POST['nomeClima'];
    $tempVerao = $_POST['tempVerao'];
    $estiloVerao = $_POST['estiloVerao'];
    $tempOutono = $_POST['tempOutono'];
    $estiloOutono = $_POST['estiloOutono'];
    $tempInverno = $_POST['tempInverno'];
    $estiloInverno = $_POST['estiloInverno'];
    $tempPrimavera = $_POST['tempPrimavera'];
    $estiloPrimavera = $_POST['estiloPrimavera'];
    $hemisferio = $_POST['hemisferio'];
    $pais = $_POST['pais'];
    $error_msg = "";

    //estabelecer conexão com banco de dados
    include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/clima.php");
    
    $database = new Database();
    $db = $database->getConnection();
    $clima = new Clima($db);
    
    //alterar clima
    if($clima->alterar($id, $nome, $tempVerao, $estiloVerao, $tempOutono, $estiloOutono, $tempInverno, $estiloInverno, $tempPrimavera, $estiloPrimavera, $hemisferio, $pais)){
        $is_success = true;
        $error_msg .= "";
    } else {
        $is_success = false;
        $error_msg .= "Falha ao alterar clima no banco de dados";
    }

} else {
    $is_success = false;
    $error_msg .= "Usuário não tem acesso para realizar essa ação";
}

die(json_encode([ 'success'=> $is_success, 'error'=> $error_msg]));


?>
