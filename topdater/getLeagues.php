<?php

if(isset($_GET['apiKey']) && isset($_GET['country'])){
	$apiKey = $_GET['apiKey'];
	$country_id = $_GET['country'];

	include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/liga.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");

	$database = new Database();
	$db = $database->getConnection();

	$usuario = new Usuario($db);
	$liga = new Liga($db);

	$user_id = $usuario->checkApiKey($apiKey);
	
	if($user_id != null){
		$stmtLiga = $liga->readAll(0, 1000, null, null, $country_id);
		$listaLigas = array();
		while ($row_liga = $stmtLiga->fetch(PDO::FETCH_ASSOC)){
			extract($row_liga);
			$addArray = array("id" => $id, "name" => $nome);
			$listaLigas[] = $addArray;
		}
		
		echo json_encode($listaLigas);
	} 
	

}

?>