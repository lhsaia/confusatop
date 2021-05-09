<?php

if(isset($_GET['apiKey']) && isset($_GET['team'])){
	$apiKey = $_GET['apiKey'];
	$team_id = $_GET['team'];
	
	include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");
	
	$database = new Database();
	$db = $database->getConnection();

	$usuario = new Usuario($db);
	$user_id = $usuario->checkApiKey($apiKey);
	
	$time = new Time($db);
	
	if($user_id != null){
		// separar elenco de cada time
		$times = explode(",",$team_id);
		
		$arrayCompleta = array();
			
		foreach($times as $item){
			
			$team_id = $item;
			$escStmt = $time->getEscalacao($team_id);
			$capStmt = $time->getCapitao($team_id);
			$penStmt = $time->getPenaltis($team_id);
			
			$escalacao = array();
			$escalacao[] = $team_id;
			while($escRow = $escStmt->fetch(PDO::FETCH_ASSOC)){
				$escalacao[] = $escRow['posicaoBase'];
				$escalacao[] = $escRow['jogador'];
			}
			
			while($capRow = $capStmt->fetch(PDO::FETCH_ASSOC)){
				$escalacao[] = $capRow['jogador'];
			}
			
			while($penRow = $penStmt->fetch(PDO::FETCH_ASSOC)){
				$escalacao[] = $penRow['jogador'];
			}
			
			array_push($arrayCompleta, $escalacao);
		}	
		
		echo json_encode($arrayCompleta);
	} 
	

	




	

	
	

	

}

?>