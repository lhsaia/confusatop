<?php

session_start();
if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){
    $origem = $_POST['origem'];
    $idPais = $_POST['pais'];

    //estabelecer conexão com banco de dados
    include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");

    $database = new Database();
    $db = $database->getConnection();
    $pais = new Pais($db);


    //apagar arbitro
    if($pais->apagarDemografia($idPais, $origem)){
        $is_success = true;
        $error_msg = "";
    } else {
        $is_success = false;
        $error_msg = "Falha ao apagar demografia do banco de dados";
    }

} else {
    $is_success = false;
    $error_msg = "Usuário não tem acesso para realizar essa ação";
}


die(json_encode([ 'success'=> $is_success, 'error'=> $error_msg]));


?>