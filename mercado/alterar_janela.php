<?php

ini_set( 'display_errors', true );
error_reporting( E_ALL );
session_start();
$error_msg = '';
if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){
    $idPais = $_POST['idPais'];
    $codeString = $_POST['codeString'];

    //estabelecer conexão com banco de dados
    include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
    $database = new Database();
    $db = $database->getConnection();
    $pais = new Pais($db);

    $pais->id = $idPais;

    //mover time para outra liga
    if($pais->alterarJanela($codeString)){
        $is_success = true;
        $error_msg .= "";
    } else {
        $is_success = false;
        $error_msg .= "Falha ao alterar janela no banco de dados";
    }

} else {
    $is_success = false;
    $error_msg .= "Usuário não tem acesso para realizar essa ação";
}

die(json_encode([ 'success'=> $is_success, 'error'=> $error_msg]));


?>