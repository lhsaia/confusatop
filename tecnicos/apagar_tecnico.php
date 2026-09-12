<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){
    $idApagar = isset($_POST['tecnicoId']) ? (int)$_POST['tecnicoId'] : 0;

    //estabelecer conexão com banco de dados
    require_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
    require_once($_SERVER['DOCUMENT_ROOT']."/objetos/tecnico.php");
    require_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
    $database = new Database();
    $db = $database->getConnection();
    $tecnico = new Tecnico($db);
    $usuario = new Usuario($db);

    $idClube = isset($_POST['idTime']) ? (int)$_POST['idTime'] : 0;
    $is_impersonated = !empty($_SESSION['impersonated']);
    $donoTecnico = $tecnico->verificarDono($idApagar);
    $usuarioLogado = $_SESSION['user_id'] ?? 0;

    if($is_impersonated || $donoTecnico == $usuarioLogado){
      $podeApagar = $is_impersonated 
          ? $tecnico->possivelApagarComClube($idApagar, $idClube) 
          : $tecnico->possivelApagar($idApagar);

      if($podeApagar){
        //apagar tecnico
        if($tecnico->apagar($idApagar)){
            $is_success = true;
            $error_msg = "";
            $usuario->atualizarAlteracao($_SESSION['user_id']);
        } else {
            $is_success = false;
            $error_msg = "Falha ao apagar técnico do banco de dados.";
        }
      } else {
        $is_success = false;
        $error_msg = ($is_impersonated && $idClube > 0)
            ? "O técnico não pode ser apagado pois possui contratos, histórico de transferências ou vínculos com outros clubes."
            : "Técnico não pode ser excluído por ter contrato ativo ou já ter sido negociado entre clubes.";
      }
    } else {
      $is_success = false;
      $error_msg = "Técnico é de outro usuário e não pode ser apagado.";
    }

} else {
    $is_success = false;
    $error_msg = "Usuário não tem acesso para realizar essa ação";
}

die(json_encode([ 'success'=> $is_success, 'error'=> $error_msg]));
?>
