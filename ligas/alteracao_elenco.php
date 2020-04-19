<?php

// ini_set( 'display_errors', true );
// error_reporting( E_ALL );
session_start();
$error_msg = '';
if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){
    $idJogador1 = $_POST['idJogador1'];
    $tipoAlteracao = $_POST['tipoAlteracao'];
    $clube = $_POST['clube'];

    if(isset($_POST['idJogador2'])){
        $idJogador2 = $_POST['idJogador2'];
    } else {
        $idJogador2 = null;
    }

    if(isset($_POST['posicao1'])){
        $posJogador1 = $_POST['posicao1'];
    } else {
        $posJogador1 = null;
    } 

    if(isset($_POST['posicao2'])){
        $posJogador2 = $_POST['posicao2'];
    } else {
        $posJogador2 = null;
    }

    //estabelecer conexão com banco de dados
    include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
    $database = new Database();
    $db = $database->getConnection();
    $time = new Time($db);
    $usuario = new Usuario($db);

    //mover time para outra liga
    if($time->alterarElenco($idJogador1,$idJogador2,$tipoAlteracao,$posJogador1,$posJogador2, $clube)){
        $is_success = true;
        $error_msg .= "";
        $usuario->atualizarAlteracao($_SESSION['user_id']);
    } else {
        $is_success = false;
        $error_msg .= "Falha ao realizar alteração no banco de dados";
    }

} else {
    $is_success = false;
    $error_msg .= "Usuário não tem acesso para realizar essa ação";
}


die(json_encode([ 'success'=> $is_success, 'error'=> $error_msg]));


?>