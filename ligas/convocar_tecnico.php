<?php

ini_set( 'display_errors', true );
error_reporting( E_ALL );
session_start();
$error_msg = '';
if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){

    //var_dump($_POST);

    //informações recebidas para convocar: id jogador, selecao
    //$pacoteTransferencia = json_decode($_POST['data'],true);
    $idTecnico = $_POST['idJogador'];
    $selecaoDestino = $_POST['selecaoDestino'];
    $tipoContrato =  $_POST['tipoContrato'];

    //estabelecer conexão com banco de dados
    include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/tecnico.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
    $database = new Database();
    $db = $database->getConnection();
    $tecnico = new Tecnico($db);
    $time = new Time($db);
    $usuario = new Usuario($db);

    //verificar ID logado e da seleção
    $idLogado = $_SESSION['user_id'];
    $idDonoClube = $time->donoClube($selecaoDestino,$idTecnico);
    //$error_msg .= $idDonoClube;

    // if($jogador->verificarAposentadoria($idJogador)){
    //     $is_success = false;
    //     $error_msg = "Jogador aposentado!";
    //     die(json_encode([ 'success'=> $is_success, 'error'=> $error_msg]));
    // }

    if($idLogado == $idDonoClube){
            //criar transferencia pendente
    if($tecnico->convocar($idTecnico, $selecaoDestino, $tipoContrato)){
        $is_success = true;
        $usuario->atualizarAlteracao($_SESSION['user_id']); 
    } else {
        $is_success = false;
        $error_msg .= "Falha ao convocar!";
    }

    } else {
        $is_success = false;
        $error_msg .= "Usuário não tem acesso para realizar essa ação";
    }


} else {
    $is_success = false;
    $error_msg .= "Usuário não tem acesso para realizar essa ação";
}


die(json_encode([ 'success'=> $is_success, 'error'=> $error_msg]));


?>