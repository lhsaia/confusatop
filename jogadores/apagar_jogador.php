<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){
    $idApagar = isset($_POST['jogadorId']) ? (int)$_POST['jogadorId'] : 0;

    //estabelecer conexão com banco de dados
    include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogador.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
    $database = new Database();
    $db = $database->getConnection();
    $jogador = new Jogador($db);
    $usuario = new Usuario($db);

    $idClube = isset($_POST['idTime']) ? (int)$_POST['idTime'] : 0;
    $is_impersonated = !empty($_SESSION['impersonated']);
    $donoJogador = $jogador->verificarDono($idApagar);
    $usuarioLogado = $_SESSION['user_id'] ?? 0;

    if($is_impersonated || $donoJogador == $usuarioLogado){
      $podeApagar = $is_impersonated 
          ? $jogador->possivelApagarComClube($idApagar, $idClube) 
          : $jogador->possivelApagar($idApagar);

      if($podeApagar){
        //apagar jogador
        if($jogador->apagar($idApagar)){
            $is_success = true;
            $error_msg = "";
            $usuario->atualizarAlteracao($_SESSION['user_id']);
        } else {
            $is_success = false;
            $error_msg = "Falha ao apagar jogador do banco de dados.";
        }
      } else {
        $is_success = false;
        $error_msg = ($is_impersonated && $idClube > 0)
            ? "O jogador não pode ser apagado pois possui contratos, histórico de transferências ou vínculos com outros clubes."
            : "Jogador não pode ser excluído por ter contrato ativo ou já ter sido negociado entre clubes.";
      }
    } else {
      $is_success = false;
      $error_msg = "Jogador é de outro usuário e não pode ser apagado.";
    }



} else {
    $is_success = false;
    $error_msg = "Usuário não tem acesso para realizar essa ação";
}


die(json_encode([ 'success'=> $is_success, 'error'=> $error_msg]));


?>
