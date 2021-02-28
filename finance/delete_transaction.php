<?php

session_start();
if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){
    $idApagar = $_POST['transactionId'];

    //estabelecer conexão com banco de dados
    include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/transaction.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
    $database = new Database();
    $db = $database->getConnection();
    $transaction = new Transaction($db);
    $usuario = new Usuario($db);

    //apagar transação
    if($transaction->apagar($idApagar)){
        $is_success = true;
        $error_msg = "";
    } else {
        $is_success = false;
        $error_msg = "Falha ao apagar árbitro do banco de dados";
    }

} else {
    $is_success = false;
    $error_msg = "Usuário não tem acesso para realizar essa ação";
}


die(json_encode([ 'success'=> $is_success, 'error'=> $error_msg]));


?>