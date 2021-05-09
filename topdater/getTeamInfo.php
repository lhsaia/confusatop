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
			$stmt = $time->exportacao(null, $team_id);


		} else {
			$stmt = $time->exportacao(null, $team_id, null, true);

		}

		$lista = array();
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
			extract($row);
			$addArray = array("id" => $ID, "nome" => $Nome, "tresLetras" => $TresLetras, "estadio" => $Estadio, "escudo" => $Escudo, "uni1cor1" => $Uni1Cor1, "uni1cor2" => $Uni1Cor2, "uni1cor3" => $Uni1Cor3, "uniforme1" => $Uniforme1, "uni2cor1" => $Uni2Cor1, "uni2cor2" => $Uni2Cor2, "uni2cor3" => $Uni2Cor3, "uniforme2" => $Uniforme2, "maxTorcedores" => $MaxTorcedores, "fidelidade" => $Fidelidade);
			$lista[] = $addArray;
			
		}
		
		echo json_encode($lista);
	} 
	

	




	

	
	

	

}

?>