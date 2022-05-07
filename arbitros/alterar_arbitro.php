<?php

ini_set( 'display_errors', true );
error_reporting( E_ALL );
session_start();
if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){
    $pacoteArbitro = json_decode($_POST['data'],true);
    $idArbitro = $pacoteArbitro['id'];
    $nomeArbitro = $pacoteArbitro['nomeArbitro'];
    $nomeAux1 = $pacoteArbitro['nomeAux1'];
    $nomeAux2 = $pacoteArbitro['nomeAux2'];
    $estilo = $pacoteArbitro['estilo'];
	$nivel = $pacoteArbitro['nivel'];
	$status = $pacoteArbitro['status'];
	$nascimento = $pacoteArbitro['nascimento'];
	
    if(isset($pacoteArbitro['pais'])){
        $pais = $pacoteArbitro['pais'];
    }
    //estabelecer conexão com banco de dados
    include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/arbitros.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
    $database = new Database();
    $db = $database->getConnection();
    $arbitro = new TrioArbitragem($db);
    $usuario = new Usuario($db);
    //$p = new Pais($db);
    //if(isset($pacoteArbitro['pais'])){
    //    $paisPreparado = $p->idPorSigla($pais);
    //} else {
    //    $paisPreparado = 0;
    //}
        $paisPreparado = $pais;

    //alterar arbitro
    if($arbitro->alterar($idArbitro,$nomeArbitro,$nomeAux1,$nomeAux2,$estilo,$paisPreparado, $nivel, $status, $nascimento)){
        $is_success = true;
        $error_msg = "";
        $usuario->atualizarAlteracao($_SESSION['user_id']);
    } else {
        $is_success = false;
        $error_msg = "Falha ao alterar árbitro no banco de dados";
    }

} else {
    $is_success = false;
    $error_msg = "Usuário não tem acesso para realizar essa ação";
}


die(json_encode([ 'success'=> $is_success, 'error'=> $error_msg]));


?>