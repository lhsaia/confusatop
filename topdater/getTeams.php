<?php

if(isset($_GET['apiKey'])){
	$apiKey = $_GET['apiKey'];
	
	include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");
	
	$database = new Database();
	$db = $database->getConnection();

	$usuario = new Usuario($db);
	$user_id = $usuario->checkApiKey($apiKey);
	
	$time = new Time($db);
	
	if($user_id != null){
		
		if(isset($_GET['league'])){
			$league_id = $_GET['league'];
			$stmt = $time->readAll(0, 1000, null, $league_id);
		} else if(isset($_GET['country'])){
			
			$country_id = $_GET['country'];
			$stmt = $time->readAll(0, 1000, null, null, $country_id);
		}
		
		$lista = array();
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
			extract($row);
			$addArray = array("id" => $ID, "name" => $Nome);
			$lista[] = $addArray;
			
		}
		
		echo json_encode($lista);
	} 
	

	




	

	
	

	

}

?>