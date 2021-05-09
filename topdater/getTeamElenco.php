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
		
		if(strpos($team_id, ",") === false){
			$stmt = $time->getElenco($team_id);
			$stmtTec = $time->getTecnico($team_id);


		} else {
			$stmt = $time->getElenco($team_id, true);
			$stmtTec = $time->getTecnico($team_id, true);
		}
		
		// separar elenco de cada time
		$times = explode(",",$team_id);
		
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
			foreach($times as &$item){
				if(!is_array($item)){
					$item = array($item);
				}
				if ($item[0] == $row['clube']){
					array_push($item, $row['ID']);
				}
			}
		}
		
		unset($item);
		foreach($times as &$item){
			while(count($item) < 24){
				array_push($item, 0);
			}
		}
		unset($item);
		
		while ($row = $stmtTec->fetch(PDO::FETCH_ASSOC)){
			foreach($times as &$item){
				if ($item[0] == $row['clube']){
					array_push($item, $row['tecnico']);
				}
			}
		}
		unset($item);		
		
		echo json_encode($times);
	} 
	

	




	

	
	

	

}

?>