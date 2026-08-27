<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
header('Content-Type: application/json; charset=utf-8');

$is_success = false;
$error_msg = "";

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $nome = $_POST['nomeClima'] ?? '';
    $tempVerao = $_POST['tempVerao'] ?? '';
    $estiloVerao = $_POST['estiloVerao'] ?? '';
    $tempOutono = $_POST['tempOutono'] ?? '';
    $estiloOutono = $_POST['estiloOutono'] ?? '';
    $tempInverno = $_POST['tempInverno'] ?? '';
    $estiloInverno = $_POST['estiloInverno'] ?? '';
    $tempPrimavera = $_POST['tempPrimavera'] ?? '';
    $estiloPrimavera = $_POST['estiloPrimavera'] ?? '';
    $hemisferio = $_POST['hemisferio'] ?? '';
    $pais = isset($_POST['pais']) ? (int)$_POST['pais'] : 0;

    if ($id <= 0 || empty($nome) || $pais <= 0) {
        die(json_encode(['success' => false, 'error' => 'Dados inválidos ou campos obrigatórios não preenchidos.']));
    }

    //estabelecer conexão com banco de dados
    include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/clima.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
    
    $database = new Database();
    $db = $database->getConnection();
    $clima = new Clima($db);
    $usuario = new Usuario($db);
    
    try {
        //alterar clima
        if($clima->alterar($id, $nome, $tempVerao, $estiloVerao, $tempOutono, $estiloOutono, $tempInverno, $estiloInverno, $tempPrimavera, $estiloPrimavera, $hemisferio, $pais)){
            $is_success = true;
            if(isset($_SESSION['user_id'])){
                $usuario->atualizarAlteracao($_SESSION['user_id']);
            }
        } else {
            $is_success = false;
            $error_msg = "Falha ao alterar clima no banco de dados.";
        }
    } catch (Exception $e) {
        $is_success = false;
        $error_msg = "Erro no banco de dados: " . $e->getMessage();
    }

} else {
    $is_success = false;
    $error_msg = "Usuário não tem acesso para realizar essa ação. Faça login novamente.";
}

die(json_encode([ 'success'=> $is_success, 'error'=> $error_msg]));
?>
