<?php

session_start();
if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){
    $idApagar = $_POST['jogadorId'];

    //estabelecer conexão com banco de dados
    include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogador.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
    $database = new Database();
    $db = $database->getConnection();
    $jogador = new Jogador($db);
    $usuario = new Usuario($db);

    //verificar se jogador tem contrato com algum time ou já esteve em mais de um time

    $donoJogador = $jogador->verificarDono($idApagar);
    $usuarioLogado = $_SESSION['user_id'];


    if($donoJogador == $usuarioLogado){
      if($jogador->possivelApagar($idApagar)){
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
        $error_msg = "Jogador não pode ser excluído por ter contrato ativo ou já ter sido negociado entre clubes.";
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
