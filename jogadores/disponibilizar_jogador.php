<?php

ini_set( 'display_errors', true );
error_reporting( E_ALL );
session_start();
if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){

    $idJogador = $_POST['idJogador'];
    $tipo = $_POST['alteracao'];

    //estabelecer conexão com banco de dados
    include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogador.php");
    $database = new Database();
    $db = $database->getConnection();
    $jogador = new Jogador($db);


    if($tipo == 1){
        //criar transferencia pendente
        if($jogador->disponibilizar($idJogador)){
            $is_success = true;
            $error_msg = "";
        } else {
            $is_success = false;
            $error_msg = "Falha ao disponibilizar jogador";
        }
    } else if($tipo == 2){
        //criar transferencia pendente
        $idTime = $_POST['idTime'];
        if($jogador->demitir($idJogador,$idTime)){
            $is_success = true;
            $error_msg = "";
        } else {
            $is_success = false;
            $error_msg = "Falha ao demitir jogador";
        }
    } else if($tipo == 3){
        //colocar aqui dados sobre edição de jogador
    }
    


} else {
    $is_success = false;
    $error_msg = "Usuário não tem acesso para realizar essa ação";
}

die(json_encode([ 'success'=> $is_success, 'error'=> $error_msg]));


?>