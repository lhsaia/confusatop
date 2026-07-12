<?php

// ini_set( 'display_errors', true );
// error_reporting( E_ALL );
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){

    $idTransferencia = $_POST['idTransferencia'];
    $acao = $_POST['acao'];
    if(isset($_POST['valor'])){ 
        $valor = $_POST['valor'];
    } else {
        $valor = null;
    }

    //estabelecer conexão com banco de dados
    include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogador.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/transferNotifier.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/transfer.php");
    $database = new Database();
    $db = $database->getConnection();
    $jogador = new Jogador($db);
    $usuario = new Usuario($db);

    //criar transferencia pendente
    if($jogador->avaliarProposta($idTransferencia, $acao, $valor)){
        $is_success = true;
        $error_msg = "";
        $usuario->atualizarAlteracao($_SESSION['user_id']);
		
		    // 🔔 Notificação Discord
		if ($acao === 'aceitar') {
			$transfer = Transfer::fromId($idTransferencia);
			
			$notifier = new TransferNotifier($_ENV['DISCORD_WEBHOOK']);
			$notifier->notify($transfer);
		}
	
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