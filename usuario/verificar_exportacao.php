<?php

//die(json_encode([ 'success'=> true, 'errors'=> 'nothing']));
// ini_set( 'display_errors', true );
// error_reporting( E_ALL );
session_start();
if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){

    //estabelecer conexão com banco de dados
    include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/liga.php");
    $database = new Database();
    $db = $database->getConnection();
    $time = new Time($db);
	$liga = new Liga($db);
    $error_msg = "";
	$listaLigas = $_POST['ligasSelecionadas'];
	$usuarioConectado = $_SESSION['user_id'];
	
	// verificar se as ligas são do usuário
	$ligasUsuario = $liga->isFromUser($listaLigas, $usuarioConectado);
	
	if($ligasUsuario){
		// criar lista de times das ligas
		$listaTimes = $time->readAllMultiLeague($listaLigas);
		

		if($elencoMenor = $time->verificarElencoMenor(null, $listaTimes)){
			$error_msg .= "Há elencos com menos de 11 jogadores. </br>";
			foreach($elencoMenor as $timeErro){
				$error_msg .= $timeErro[0] . "</br>";
			}
		}

		if($elencoMaior = $time->verificarElencoMaior(null, $listaTimes)){
			$error_msg .= "Há elencos com mais de 23 jogadores (fora suplentes)</br>";
			foreach($elencoMaior as $timeErro){
				$error_msg .= $timeErro[0] . "</br>";
			}
		}

		if($capitaoTime = $time->verificarCapitao(null, $listaTimes)){
			$error_msg .= "Há times sem capitão. </br>";
			foreach($capitaoTime as $timeErro){
				$error_msg .= $timeErro[0] . "</br>";
			}
		}

		if($penaltisTime = $time->verificarPenaltis(null, $listaTimes)){
			$error_msg .= "Há times sem todos os cobradores. </br>";
			foreach($penaltisTime as $timeErro){
				$error_msg .= $timeErro[0] . "</br>";
			}
		}

		if($goleirosTime = $time->verificarGoleiros(null, $listaTimes)){
			$error_msg .= "Há times com número incorreto de goleiros escalados </br>";
			foreach($goleirosTime as $timeErro){
				$error_msg .= $timeErro[0] . "</br>";
			}
		}

		if($escalacaoTime = $time->verificarEscalacoes(null, $listaTimes)){
			$error_msg .= "Há times sem os onze jogadores escalados. </br>";
			foreach($escalacaoTime as $timeErro){
				$error_msg .= $timeErro[0] . "</br>";
			}
		}

		if($aposentadosTime = $time->verificarAposentados(null, $listaTimes)){
			$error_msg .= "Há times com jogadores acima da idade permitida pelo Hexacolor. </br>";
			foreach($aposentadosTime as $timeErro){
				$error_msg .= $timeErro[0] . "</br>";
			}
		}

		if($tecnicosTimes = $time->verificarTecnicos(null, $listaTimes)){
			$error_msg .= "Há times sem técnico ou com técnicos demais. </br>";
			foreach($tecnicosTimes as $timeErro){
				$error_msg .= $timeErro[0] . "</br>";
			}
		}

	} else {
		$error_msg = "Pelo menos uma liga selecionada não é do usuário";
	}
		
	
	

} else {
    $error_msg = "Usuário não tem acesso para realizar essa ação";
}

if($error_msg != ""){
    $success = false;
} else {
    $success = true;
}

$errors = $error_msg;
die(json_encode([ 'success'=> $success, 'errors'=> $errors]));


?>
