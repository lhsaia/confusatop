<?php

if(isset($_GET['apiKey'])){
	$apiKey = $_GET['apiKey'];

	include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");

	$database = new Database();
	$db = $database->getConnection();

	$usuario = new Usuario($db);
	$pais = new Pais($db);

	$user_id = $usuario->checkApiKey($apiKey);
	
	if($user_id != null){
		$stmtPais = $pais->read($user_id);
		$listaPaises = array();
		while ($row_pais = $stmtPais->fetch(PDO::FETCH_ASSOC)){
			extract($row_pais);
			$addArray = array("id" => $id, "name" => $nome);
			$listaPaises[] = $addArray;
		}
		
		echo json_encode($listaPaises);
	} 
	

}

?>