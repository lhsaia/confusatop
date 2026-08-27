<?php

ini_set( 'display_errors', true );
error_reporting( E_ALL );
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
$error_msg = '';
if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true && $_SESSION['user_id'] == $_POST['sorveteTec']){

    //var_dump($_POST);

    //informações recebidas para propor transferencia: id jogador, clube destino, clube origem, valor
    //$pacoteTransferencia = json_decode($_POST['data'],true);
    $idTecnico = $_POST['idTecnico'];
    $clubeOrigem = $_POST['clubeOrigem'];
    $clubeDestino = $_POST['clubeDestino'];

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

    //verificar ID logado e do clube de origem
    $idLogado = $_SESSION['user_id'];
    $idDonoClube = $time->donoClube($clubeOrigem, $idTecnico);

    // Quando o técnico está sem clube (clubeOrigem=0), verificar se o usuário logado
    // é dono do país de origem do técnico
    $idDonoTecnico = 0;
    if ($clubeOrigem == 0) {
        $query_dono_tec = "SELECT p.dono FROM tecnico t LEFT JOIN paises p ON p.id = t.Pais WHERE t.ID = ?";
        $stmt_dono_tec = $db->prepare($query_dono_tec);
        $stmt_dono_tec->bindParam(1, $idTecnico);
        $stmt_dono_tec->execute();
        $row_dono_tec = $stmt_dono_tec->fetch(PDO::FETCH_ASSOC);
        if ($row_dono_tec) {
            $idDonoTecnico = (int)$row_dono_tec['dono'];
        }
    }

    if($clubeOrigem == $clubeDestino){
        $is_success = false;
        $error_msg = "Técnico não pode ir para o mesmo clube atual!";
        die(json_encode([ 'success'=> $is_success, 'error'=> $error_msg]));
    }

    //criar transferencia pendente
    if($tecnico->proporTransferencia($idTecnico, $clubeOrigem, $clubeDestino)){

        $idTransferencia = $db->lastInsertId();

        if($idLogado == $idDonoClube || ($clubeOrigem == 0 && $idLogado == $idDonoTecnico)){
            if($tecnico->avaliarProposta($idTransferencia, 'aceitar')){
                $usuario->atualizarAlteracao($_SESSION['user_id']);
                $is_success = true;
            } else {
                $is_success = false;
            }

        } else {
          $tecnico->enviarEmailProposta($idTecnico, $clubeOrigem, $clubeDestino, $idTransferencia);
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
