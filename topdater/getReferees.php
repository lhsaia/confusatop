<?php

if(isset($_GET['apiKey']) && isset($_GET['country'])){
	$apiKey = $_GET['apiKey'];
	$country_id = $_GET['country'];
	
	include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/arbitros.php");
	
	$database = new Database();
	$db = $database->getConnection();

	$usuario = new Usuario($db);
	$user_id = $usuario->checkApiKey($apiKey);
	
	$arbitro = new TrioArbitragem($db);
		
	if($user_id != null){
		// separar elenco de cada time
		$countries = explode(",",$country_id);
		
		
	
		$arrayCompleta = array();
			
		foreach($countries as $item){
			
			$country_id = $item;
			$stmt = $arbitro->exportacao($country_id);
			
			$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
			
			$arrayCompleta[] = $result;
			
			//while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
			//}
		
		}	
		
		echo json_encode($arrayCompleta);
	} 
	

	




	

	
	

	

}

?>