<?php

ini_set( 'display_errors', true );
error_reporting( E_ALL );
session_start();
if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){

    $idTecnico = $_POST['idTecnico'];
    $tipo = $_POST['alteracao'];


    //conferir informações sobre o dono do time e do jogador vs o usuário logado!

    //estabelecer conexão com banco de dados
    include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/tecnico.php");
    $database = new Database();
    $db = $database->getConnection();
    $tecnico = new Tecnico($db);


    if($tipo == 1){
        // //criar transferencia pendente
        // if($jogador->disponibilizar($idJogador)){
        //     $is_success = true;
        //     $error_msg = "";
        // } else {
        //     $is_success = false;
        //     $error_msg = "Falha ao disponibilizar jogador";
        // }
    } else if($tipo == 2){
        //criar transferencia pendente
        $idTime = $_POST['idTime'];
        if($tecnico->demitir($idTecnico,$idTime)){
            $is_success = true;
            $error_msg = "";
        } else {
            $is_success = false;
            $error_msg = "Falha ao demitir técnico";
        }
    } else if($tipo == 3){
        // //colocar aqui dados sobre edição de jogador
         $idDonoTime = $_SESSION['user_id'];
        // //$idDonoTime = 9;
         $idDonoJogador = $tecnico->verificarDono($idTecnico);



        if($idDonoTime == $idDonoJogador){
            $nomeTecnico = $_POST['nome'];
            $nacionalidadeTecnico = $_POST['pais'];
            $nascimentoTecnico = $_POST['nascimento'];
            $isDono = true;
        } else {
            $nomeTecnico = null;
            $nacionalidadeTecnico = null;
            $nascimentoTecnico = null;
            $isDono = false;
        }

         $idTime = $_POST['idTime'];
        $nivelTecnico = $_POST['nivel'];

        if($tecnico->editar($idTecnico,$idTime,$nomeTecnico,$nacionalidadeTecnico,$nascimentoTecnico,$nivelTecnico,$isDono)){
            $is_success = true;
            $error_msg = "";
        } else {
            $is_success = false;
            $error_msg = "Falha ao editar técnico";
        }
    } else if($tipo == 4){
        // //criar transferencia pendente
        // $idTime = $_POST['idTime'];
        // if($jogador->aposentar($idJogador,$idTime)){
        //     $is_success = true;
        //     $error_msg = "";
        // } else {
        //     $is_success = false;
        //     $error_msg = "Falha ao aposentar jogador";
        // }
    } else if($tipo == 5){
        // //criar transferencia pendente
        // $idTime = $_POST['idTime'];
        // if($jogador->transferir($idJogador,$idTime,0,0,-1,0,0,0,0)){
        //     $is_success = true;
        //     $error_msg = "";
        // } else {
        //     $is_success = false;
        //     $error_msg = "Falha ao repatriar jogador";
        // }
    } else if($tipo == 6){
        // //criar transferencia pendente
        // $novoNivel = $_POST['novoNivel'];
        // if($jogador->incorporarModificador($idJogador,$novoNivel)){
        //     $is_success = true;
        //     $error_msg = "";
        // } else {
        //     $is_success = false;
        //     $error_msg = "Falha ao incorporar modificador";
        // }
    } else if($tipo == 9){
          //colocar aqui dados sobre edição de jogador
          $idDonoTecnico = $_SESSION['user_id'];
          //$idDonoTime = 9;
          $idDonoTime = $tecnico->verificarDonoTimeVinculado($idTecnico);

          if(is_null($idDonoTime) || $idDonoTime == 0){
              $idDonoTime = $idDonoTecnico;
          }

          if($idDonoTime == $idDonoTecnico){
              $nomeTecnico = $_POST['nome'];
              $nacionalidadeTecnico = $_POST['pais'];
              $nascimentoTecnico = $_POST['nascimento'];
              $mentalidadeTecnico = $_POST['mentalidade'];
              $estiloTecnico = $_POST['estilo'];

              $isDono = true;
          } else {
              $nomeTecnico = null;
              $nacionalidadeTecnico = null;
              $nascimentoTecnico = null;
              $mentalidadeTecnico = null;
              $estiloTecnico = null;
              $isDono = false;
          }


          $nivelTecnico = $_POST['nivel'];

          if($tecnico->editar($idTecnico,null,$nomeTecnico,$nacionalidadeTecnico,$nascimentoTecnico,$nivelTecnico,$isDono,$mentalidadeTecnico, $estiloTecnico)){
              $is_success = true;
              $error_msg = "";
          } else {
              $is_success = false;
              $error_msg = "Falha ao editar técnico";
          }
    }



} else {
    $is_success = false;
    $error_msg = "Usuário não tem acesso para realizar essa ação";
}

die(json_encode([ 'success'=> $is_success, 'error'=> $error_msg]));


?>
