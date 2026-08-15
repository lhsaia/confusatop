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
                $transfer = new Transfer($db);
                if ($transfer->carregarPorId($idTransferencia)) {
                    // Envia apenas se for transferência de clube para clube (ignora jogadores sem clube / livres)
                    if ($transfer->origemId > 0 && $transfer->destinoId > 0) {
                        $siteUrl = getenv('SITE_URL') ?: '';
                        $baseSiteUrl = rtrim($siteUrl, '/');
                        
                        $valorInt = (int)$transfer->valor;
                        if ($valorInt >= 1000000) {
                            $valorFormatado = "F$ " . number_format($valorInt, 0, ',', '.') . " (" . round($valorInt / 1000000, 1) . " M)";
                        } else {
                            $valorFormatado = "F$ " . number_format($valorInt, 0, ',', '.');
                        }

                        $bandeira_png = $transfer->bandeiraPng ? $baseSiteUrl . $transfer->bandeiraPng : '';
                        $foto = $transfer->fotoJogador ? $baseSiteUrl . '/images/jogadores/' . $transfer->fotoJogador : '';
                        $origem_escudo_png = $transfer->origemEscudo ? $baseSiteUrl . $transfer->origemEscudo : '';
                        $destino_escudo_png = $transfer->destinoEscudo ? $baseSiteUrl . $transfer->destinoEscudo : '';

                        $transferData = [
                            'nome' => $transfer->jogadorNome,
                            'bandeira_png' => $bandeira_png,
                            'tipo_transferencia' => $transfer->tipo,
                            'foto' => $foto,
                            'origem' => $transfer->origemNome,
                            'origem_escudo_png' => $origem_escudo_png,
                            'destino' => $transfer->destinoNome,
                            'destino_escudo_png' => $destino_escudo_png,
                            'valor' => $valorFormatado,
                            'data' => date('d/m/Y', strtotime($transfer->data))
                        ];
                        
                        $webhook = getenv('DISCORD_WEBHOOK');
                        if (!empty($webhook)) {
                            $notifier = new TransferNotifier($webhook);
                            $notifier->notify($transferData);
                        }
                    }
                }
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