<?php

if(isset($_GET['apiKey']) && isset($_GET['team'])){
	$apiKey = $_GET['apiKey'];
	$team_id = $_GET['team'];
	
	include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/estadio.php");
	
	$database = new Database();
	$db = $database->getConnection();

	$usuario = new Usuario($db);
	$user_id = $usuario->checkApiKey($apiKey);
	
	$estadio = new Estadio($db);
		
	if($user_id != null){
		// separar elenco de cada time
		$teams = explode(",",$team_id);

		$arrayCompleta = array();
			
		foreach($teams as $item){
			
			$team_id = $item;
			$stmt = $estadio->exportacao(null, $team_id);
			
			$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
			
			$arrayCompleta[] = $result[0];
			
			//while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
			//}
		
		}	
		
		echo json_encode($arrayCompleta);
	} 
	

	




	

	
	

	

}

?>