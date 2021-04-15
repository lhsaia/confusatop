<?php

ini_set( 'display_errors', true );
error_reporting( E_ALL );
session_start();
$error_msg = '';
if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true && $_SESSION['user_id'] == $_POST['sorvete']){

    //var_dump($_POST);

    //informações recebidas para propor transferencia: id jogador, clube destino, clube origem, valor
    //$pacoteTransferencia = json_decode($_POST['data'],true);
    $idJogador = $_POST['idJogador'];
    $clubeOrigem = $_POST['clubeOrigem'];
    $clubeDestino = $_POST['clubeDestino'];
    $valor = $_POST['valor'];
    $tipoTransacao = $_POST['tipoTransacao'];
    $fimContrato = $_POST['fimContrato'];

    //estabelecer conexão com banco de dados
    include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogador.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
    $database = new Database();
    $db = $database->getConnection();
    $jogador = new Jogador($db);
    $time = new Time($db);
    $usuario = new Usuario($db);

    //verificar ID logado e do clube de origem
    $idLogado = $_SESSION['user_id'];
    $idDonoClube = $time->donoClube($clubeOrigem,$idJogador);
    $idDonoJogador = $jogador->donoJogador($idJogador);
    //$error_msg .= $idDonoClube;

    if($jogador->verificarAposentadoria($idJogador)){
        $is_success = false;
        $error_msg = "Jogador aposentado!";
        die(json_encode([ 'success'=> $is_success, 'error'=> $error_msg]));
    }

    if($clubeOrigem == $clubeDestino){
        $is_success = false;
        $error_msg = "Jogador não pode ir para o mesmo clube atual!";
        die(json_encode([ 'success'=> $is_success, 'error'=> $error_msg]));
    }
	
	if($_SESSION['emTestes'] && (($idLogado != $idDonoClube) || ($idDonoJogador != $idLogado))){
        $is_success = false;
        $error_msg = "Usuário em período de testes";
        die(json_encode([ 'success'=> $is_success, 'error'=> $error_msg]));
    }
	
    //criar transferencia pendente
    if($jogador->proporTransferencia($idJogador, $clubeOrigem, $clubeDestino, $valor, 0, $tipoTransacao, $fimContrato)){

        $idTransferencia = $db->lastInsertId();

        if($idLogado == $idDonoClube){
            if($jogador->avaliarProposta($idTransferencia, 'aceitar')){
                $usuario->atualizarAlteracao($_SESSION['user_id']);
                $is_success = true;
            } else {
                $is_success = false;
            }

        } else if($idDonoClube == 0 && $idDonoJogador == $idLogado){
          if($jogador->avaliarProposta($idTransferencia, 'aceitar')){
              $usuario->atualizarAlteracao($_SESSION['user_id']);
              $is_success = true;
          } else {
              $is_success = false;
          }
        } else {
          // enviar email
          $jogador->enviarEmailProposta($idJogador, $clubeOrigem, $clubeDestino, $idTransferencia);
          $is_success = true;
        }

        $error_msg .= "";
    } else {
        $is_success = false;
        $error_msg .= "Falha ao solicitar transferência";
    }

} else {
    $is_success = false;
    $error_msg .= "Usuário não tem acesso para realizar essa ação";
}


die(json_encode([ 'success'=> $is_success, 'error'=> $error_msg]));


?>
