<?php

ini_set( 'display_errors', true );
error_reporting( E_ALL );
session_start();
$error_msg = '';
if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){
    $capitao = $_POST['capitaoSelect'];
    $penal1 = $_POST['penal1Select'];
    $penal2 = $_POST['penal2Select'];
    $penal3 = $_POST['penal3Select'];
    $clube = $_POST['clube'];

    //estabelecer conexão com banco de dados
    include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
    $database = new Database();
    $db = $database->getConnection();
    $time = new Time($db);
    $usuario = new Usuario($db);

    

    //mover time para outra liga
    if($time->alterarCapitaoCobrador($capitao,$penal1,$penal2,$penal3,$clube)){
        $is_success = true;
        $error_msg .= "";
        $usuario->atualizarAlteracao($_SESSION['user_id']);
    } else {
        $is_success = false;
        $error_msg .= "Falha ao realizar alteração no banco de dados";
    }

} else {
    $is_success = false;
    $error_msg .= "Usuário não tem acesso para realizar essa ação";
}

die(json_encode([ 'success'=> $is_success, 'error'=> $error_msg]));


?>