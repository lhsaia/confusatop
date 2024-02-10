<?php

ini_set( 'display_errors', true );
error_reporting( E_ALL );
session_start();
if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){

    //estabelecer conexão com banco de dados
    include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogador.php");
	require($_SERVER['DOCUMENT_ROOT']."/lib/functions.php");
    $database = new Database();
    $db = $database->getConnection();
    $jogador = new Jogador($db);
	
	if(isset($_POST['salvar'])){
		$salvar = true;
	} else {
		$salvar = false;
	}
	
	//salvar
	if($salvar){
		$idJogador = $_POST['idJogador'];
		
		if($_POST['isGoleiro']){
			$attribute_array = adjustAttributes(true, $_POST['level'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, $_POST['reflexos'], $_POST['seguranca'],  $_POST['saida_bola'],  $_POST['jogo_aereo'],  $_POST['lancamentos'],  $_POST['penaltis']);
		} else {
			$attribute_array = adjustAttributes(false, $_POST['level'], $_POST['marcacao'], $_POST['desarme'], $_POST['visao'], $_POST['movimentacao'], $_POST['cruzamentos'], $_POST['cabeceamento'], $_POST['tecnica'], $_POST['controle'], $_POST['finalizacao'], $_POST['faroGol'], $_POST['velocidade'], $_POST['forca'], 0, 0, 0, 0, 0, 0);
		}
		
		if($jogador->alterarAtributos($idJogador, $attribute_array, $_POST['isGoleiro'])){
			$is_success = true;
			$error_msg = "";
		} else {
			$is_success = false;
			$error_msg = "Falha ao salvar atributos do jogador";
		}
		$personalidade = $jogador->avaliarPersonalidade($idJogador);
		
	} else {
		if($_POST['isGoleiro']){
			$attribute_array = adjustAttributes(true, $_POST['level'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, $_POST['reflexos'], $_POST['seguranca'],  $_POST['saida_bola'],  $_POST['jogo_aereo'],  $_POST['lancamentos'],  $_POST['penaltis']);
		} else {
			$attribute_array = adjustAttributes(false, $_POST['level'], $_POST['marcacao'], $_POST['desarme'], $_POST['visao'], $_POST['movimentacao'], $_POST['cruzamentos'], $_POST['cabeceamento'], $_POST['tecnica'], $_POST['controle'], $_POST['finalizacao'], $_POST['faroGol'], $_POST['velocidade'], $_POST['forca'], 0, 0, 0, 0, 0, 0);
		}
		
		$personalidade = $jogador->avaliarPersonalidadeDinamica($attribute_array);

		$is_success = true;
		$error_msg = "";
	}

	    
} else {
    $is_success = false;
    $error_msg = "Usuário não tem acesso para realizar essa ação";
}


die(json_encode([ 'success'=> $is_success, 'attributeArray'=> $attribute_array,  'error'=> $error_msg, 'personalidade'=> $personalidade]));



?>