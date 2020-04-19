<?php

ini_set( 'display_errors', true );
error_reporting( E_ALL );
session_start();
if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){

    $idJogador = $_POST['idJogador'];
    $tipo = $_POST['alteracao'];

    //conferir informações sobre o dono do time e do jogador vs o usuário logado!

    //estabelecer conexão com banco de dados
    include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogador.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
    $database = new Database();
    $db = $database->getConnection();
    $jogador = new Jogador($db);
    $usuario = new Usuario($db);


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
            $usuario->atualizarAlteracao($_SESSION['user_id']);
            $is_success = true;
            $error_msg = "";
        } else {
            $is_success = false;
            $error_msg = "Falha ao demitir jogador";
        }
    } else if($tipo == 3){
        //colocar aqui dados sobre edição de jogador
        $idDonoTime = $_SESSION['user_id'];
        //$idDonoTime = 9;
        $idDonoJogador = $jogador->verificarDono($idJogador);



        if($idDonoTime == $idDonoJogador || $idDonoJogador == 0){
            $nomeJogador = $_POST['nome'];
            $nacionalidadeJogador = $_POST['nacionalidade'];
            $nascimentoJogador = $_POST['nascimento'];
            $isDono = true;
        } else {
            $nomeJogador = null;
            $nacionalidadeJogador = null;
            $nascimentoJogador = null;
            $isDono = false;
        }

        $idTime = $_POST['idTime'];
        $valorJogador = $_POST['valor'];
        if(isset($_POST['posicoes'])){
            $posicoesJogador = $_POST['posicoes'];
        } else {
            $posicoesJogador = array();
        }

        $nivelJogador = $_POST['nivel'];

        if($jogador->editar($idJogador,$idTime,$nomeJogador,$nacionalidadeJogador,$nascimentoJogador,$valorJogador,$posicoesJogador,$nivelJogador,$isDono)){
            $usuario->atualizarAlteracao($_SESSION['user_id']);
            $is_success = true;
            $error_msg = "";
        } else {
            $is_success = false;
            $error_msg = "Falha ao editar jogador";
        }
    } else if($tipo == 4){
        //criar transferencia pendente
        $idTime = $_POST['idTime'];
        if($jogador->aposentar($idJogador,$idTime)){
            $usuario->atualizarAlteracao($_SESSION['user_id']);
            $is_success = true;
            $error_msg = "";
        } else {
            $is_success = false;
            $error_msg = "Falha ao aposentar jogador";
        }
    } else if($tipo == 5){
        //criar transferencia pendente
        $idTime = $_POST['idTime'];
        if($jogador->transferir($idJogador,$idTime,0,0,-1,0,0,0,0)){
            $usuario->atualizarAlteracao($_SESSION['user_id']);
            $is_success = true;
            $error_msg = "";
        } else {
            $is_success = false;
            $error_msg = "Falha ao repatriar jogador";
        }
    } else if($tipo == 6){
        //criar transferencia pendente
        $novoNivel = $_POST['novoNivel'];
        if($jogador->incorporarModificador($idJogador,$novoNivel)){
            $usuario->atualizarAlteracao($_SESSION['user_id']);
            $is_success = true;
            $error_msg = "";
        } else {
            $is_success = false;
            $error_msg = "Falha ao incorporar modificador";
        }
    } else if($tipo == 9){
          //colocar aqui dados sobre edição de jogador
          $idDonoJogador = $_SESSION['user_id'];
          //$idDonoTime = 9;
          $idDonoTime = $jogador->verificarDonoTimeVinculado($idJogador);

          if(is_null($idDonoTime) || $idDonoTime == 0){
              $idDonoTime = $idDonoJogador;
          }

          if($idDonoTime == $idDonoJogador){
              $nomeJogador = $_POST['nome'];
              $nacionalidadeJogador = $_POST['nacionalidade'];
              $nascimentoJogador = $_POST['nascimento'];
              $valorJogador = $_POST['valor'];
              $determinacaoJogador = $_POST['determinacao'];
              $cobrancaFaltaJogador = $_POST['cobrancaFalta'];
              $mentalidadeJogador = $_POST['mentalidade'];
              $atividadeJogador = $_POST['atividade'];
              $isDono = true;
          } else {
              $nomeJogador = null;
              $nacionalidadeJogador = null;
              $nascimentoJogador = null;
              $valorJogador = null;
              $determinacaoJogador = "none";
              $cobrancaFaltaJogador = null;
              $mentalidadeJogador = "none";
              $atividadeJogador = null;
              $isDono = false;
          }

          //$idTime = $_POST['idTime'];

          if(isset($_POST['posicoes'])){
              $posicoesJogador = $_POST['posicoes'];
          } else {
              $posicoesJogador = array();
          }

          $nivelJogador = $_POST['nivel'];

          if($jogador->editar($idJogador,null,$nomeJogador,$nacionalidadeJogador,$nascimentoJogador,$valorJogador,$posicoesJogador,$nivelJogador,$isDono,$atividadeJogador, $mentalidadeJogador, $determinacaoJogador, $cobrancaFaltaJogador)){
            $usuario->atualizarAlteracao($_SESSION['user_id']);
              $is_success = true;
              $error_msg = "";
          } else {
              $is_success = false;
              $error_msg = "Falha ao editar jogador";
          }
    }



} else {
    $is_success = false;
    $error_msg = "Usuário não tem acesso para realizar essa ação";
}

die(json_encode([ 'success'=> $is_success, 'error'=> $error_msg]));


?>
