<?php

// ini_set( 'display_errors', true );
// error_reporting( E_ALL );
session_start();
if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){

    $idTransferencia = $_POST['idTransferencia'];
    $acao = $_POST['acao'];

    //estabelecer conexão com banco de dados
    include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/tecnico.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
    $database = new Database();
    $db = $database->getConnection();
    $tecnico = new Tecnico($db);
    $usuario = new Usuario($db);

    //criar transferencia pendente
    if($tecnico->avaliarProposta($idTransferencia, $acao)){
        $is_success = true;
        $error_msg = "";
        $usuario->atualizarAlteracao($_SESSION['user_id']);
    } else {
        $is_success = false;
        $error_msg = "Falha ao ".$acao." proposta";
    }

} else {
    $is_success = false;
    $error_msg = "Usuário não tem acesso para realizar essa ação";
}

die(json_encode([ 'success'=> $is_success, 'error'=> $error_msg]));


?>