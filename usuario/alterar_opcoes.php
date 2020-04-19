<?php

ini_set( 'display_errors', true );
error_reporting( E_ALL );
session_start();
if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){
    $sumulas = $_POST['sumulas'];
    $lesoes = $_POST['lesoes'];
    $porTempo = $_POST['porTempo'];
    $porData = $_POST['porData'];
    $dono = $_SESSION['user_id'];
    $VAR = $_POST['VAR'];
    $error_msg = "";

    //estabelecer conexão com banco de dados
    include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/parametros.php");
    $database = new Database();
    $db = $database->getConnection();
    $parametro = new Parametro($db);

    //alterar opcoes
    if($parametro->alterarOpcoes($dono, $sumulas, $lesoes, $porTempo, $porData, $VAR)){
        $is_success = true;
        $error_msg .= "";
    } else {
        $is_success = false;
        $error_msg .= "Falha ao alterar opções no banco de dados";
    }

} else {
    $is_success = false;
    $error_msg .= "Usuário não tem acesso para realizar essa ação";
}

die(json_encode([ 'success'=> $is_success, 'error'=> $error_msg]));


?>