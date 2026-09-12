<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){
    $idApagar = isset($_POST['timeId']) ? (int)$_POST['timeId'] : 0;

    //estabelecer conexão com banco de dados
    require_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
    require_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");
    require_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
    $database = new Database();
    $db = $database->getConnection();
    $time = new Time($db);
    $usuario = new Usuario($db);

    $is_impersonated = !empty($_SESSION['impersonated']);
    $donoClube = $time->verificarDono($idApagar);
    $usuarioLogado = $_SESSION['user_id'] ?? 0;

    if($is_impersonated || $donoClube == $usuarioLogado){
        $check = $time->possivelApagar($idApagar);
        if($check['pode']){
            // Obter liga antes de apagar para redirecionamento
            $infoClube = $time->readInfo($idApagar);
            $idLiga = $infoClube['liga_id'] ?? 0;

            if($time->apagar($idApagar)){
                $is_success = true;
                $error_msg = "";
                $usuario->atualizarAlteracao($_SESSION['user_id']);
                $redirect = ($idLiga > 0) ? "/ligas/leaguestatus.php?league=" . $idLiga : "/usuario/meustimes.php";
            } else {
                $is_success = false;
                $error_msg = "Falha ao apagar clube do banco de dados.";
                $redirect = "";
            }
        } else {
            $is_success = false;
            $error_msg = $check['motivo'];
            $redirect = "";
        }
    } else {
        $is_success = false;
        $error_msg = "O clube pertence a outro usuário e não pode ser apagado.";
        $redirect = "";
    }

} else {
    $is_success = false;
    $error_msg = "Usuário não tem acesso para realizar essa ação";
    $redirect = "";
}

die(json_encode([ 'success'=> $is_success, 'error'=> $error_msg, 'redirect' => $redirect ?? '' ]));
?>
