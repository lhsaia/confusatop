<?php

ini_set( 'display_errors', true );
error_reporting( E_ALL );
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
$error_msg = '';
if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true && $_SESSION['user_id'] == $_POST['sorvete']){

    //var_dump($_POST);

    //informações recebidas para propor transferencia: id jogador, clube destino, clube origem, valor
    //$pacoteTransferencia = json_decode($_POST['data'],true);
    $idJogador = $_POST['idJogador'] ?? '';
    $clubeOrigemReq = $_POST['clubeOrigem'] ?? '';
    $clubeDestino = $_POST['clubeDestino'] ?? '';
    $valor = $_POST['valor'] ?? 0;
    $tipoTransacao = $_POST['tipoTransacao'] ?? 0;
    $fimContrato = $_POST['fimContrato'] ?? '';

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

    // Obter clube de origem real e se está emprestado
    $query_vinculo = "SELECT clube, clubeVinculado FROM contratos_jogador WHERE jogador=:jogador AND tipoContrato = 0";
    $stmt_vinculo = $db->prepare($query_vinculo);
    $stmt_vinculo->bindParam(":jogador", $idJogador);
    $stmt_vinculo->execute();
    $row_vinculo = $stmt_vinculo->fetch(PDO::FETCH_ASSOC);
    
    $clubeOrigem = 0;
    $estaEmprestado = false;
    if ($row_vinculo) {
        if ($row_vinculo['clubeVinculado'] != 0) {
            $clubeOrigem = $row_vinculo['clubeVinculado']; // Dono real dos direitos
            $estaEmprestado = true;
            $clubeAtual = $row_vinculo['clube']; // Clube em que atua (mutuário)
        } else {
            $clubeOrigem = $row_vinculo['clube'];
            $estaEmprestado = false;
            $clubeAtual = $row_vinculo['clube'];
        }
    }

    //verificar ID logado e do clube de origem
    $idLogado = $_SESSION['user_id'];
    $idDonoClube = $time->donoClube($clubeOrigem, $idJogador);
    $idDonoJogador = $jogador->donoJogador($idJogador);
    //$error_msg .= $idDonoClube;

    if($jogador->verificarAposentadoria($idJogador)){
        $is_success = false;
        $error_msg = "Jogador aposentado!";
        die(json_encode([ 'success'=> $is_success, 'error'=> $error_msg]));
    }

    if($clubeOrigem == $clubeDestino && !$estaEmprestado){
        $is_success = false;
        $error_msg = "Jogador não pode ir para o mesmo clube atual!";
        die(json_encode([ 'success'=> $is_success, 'error'=> $error_msg]));
    }
    
    if ($estaEmprestado) {
        if ($clubeDestino != $clubeAtual) {
            $is_success = false;
            $error_msg = "Apenas o clube que pegou o jogador emprestado pode fazer propostas durante o empréstimo.";
            die(json_encode([ 'success'=> $is_success, 'error'=> $error_msg]));
        }
        
        if ($tipoTransacao != 3 && $tipoTransacao != 0 && $tipoTransacao != 1) {
            $is_success = false;
            $error_msg = "Para estender o empréstimo, selecione 'Extensão de Empréstimo'.";
            die(json_encode([ 'success'=> $is_success, 'error'=> $error_msg]));
        }
    }
	
	if(($_SESSION['emTestes'] ?? false) && (($idLogado != $idDonoClube) || ($idDonoJogador != $idLogado))){
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
                $error_msg .= "Erro ao avaliar proposta (dono_clube).";
            }

        } else if($idDonoClube == 0 && $idDonoJogador == $idLogado){
          if($jogador->avaliarProposta($idTransferencia, 'aceitar')){
              $usuario->atualizarAlteracao($_SESSION['user_id']);
              $is_success = true;
          } else {
              $is_success = false;
              $error_msg .= "Erro ao avaliar proposta (dono_jogador).";
          }
        } else {
          // enviar email
          $jogador->enviarEmailProposta($idJogador, $clubeOrigem, $clubeDestino, $idTransferencia);
          $is_success = true;
        }

        $error_msg .= "";
    } else {
        $is_success = false;
        $error_msg .= "Falha ao solicitar transferência - bloqueado em proporTransferencia()";
    }

} else {
    $is_success = false;
    $error_msg .= "Usuário não tem acesso para realizar essa ação";
}


die(json_encode([ 'success'=> $is_success, 'error'=> $error_msg]));


?>
