<?php

// ini_set( 'display_errors', true );
// error_reporting( E_ALL );
session_start();
if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){

    //estabelecer conexão com banco de dados
    include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/parametros.php");
    $database = new Database();
    $db = $database->getConnection();
    $parametro = new Parametro($db);

    $gols = ($_POST['gols'] > 20 ? 20 : $_POST['gols']);
    $gols = ($gols < 1 ? 1 : $gols); 
    $faltas = ($_POST['faltas'] > 20 ? 20 : $_POST['faltas']);
    $faltas = ($faltas < 1 ? 1 : $faltas); 
    $cartoes = ($_POST['cartoes'] > 10 ? 10 : $_POST['cartoes']);
    $cartoes = ($cartoes < 0 ? 0 : $cartoes); 
    $impedimentos = ($_POST['impedimentos'] > 10 ? 10 : $_POST['impedimentos']);
    $impedimentos = ($impedimentos < 0 ? 0 : $impedimentos); 

    $parametro->id = $_POST['id'];
    $parametro->nome = $_POST['nome'];
    $parametro->gols = $gols;
    $parametro->faltas = $faltas;
    $parametro->impedimentos = $impedimentos;
    $parametro->cartoes = $cartoes;
    $parametro->estilo = $_POST['estilo'];
    $parametro->paisPadrao = $_POST['pais'];
    $parametro->selecionado = (int)$_POST['selecionado'];
    $parametro->exibirBandeiras = (int)$_POST['bandeiras'];
    $parametro->dono = $_SESSION['user_id'];
    $error_msg = "";

    //alterar opcoes
    if($parametro->alterar()){
        $is_success = true;
        $error_msg .= "";
    } else {
        $is_success = false;
        $error_msg .= "Falha ao alterar parâmetros no banco de dados";
    }

} else {
    $is_success = false;
    $error_msg .= "Usuário não tem acesso para realizar essa ação";
}

die(json_encode([ 'success'=> $is_success, 'error'=> $error_msg]));


?>