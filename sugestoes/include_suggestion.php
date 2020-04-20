<?php  

	session_start();
    $titulo = $_POST['title'];
	$descricao = $_POST['description'];
	$tipo = $_POST['type'];
	$originador = $_SESSION['user_id'];

    include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/suggestion.php");
    
    $database = new Database();
    $db = $database->getConnection();
    
    $suggestion = new Suggestion($db);  

	if($originador <> 0){
    $stmt = $suggestion->insertSuggestion($titulo, $descricao, $tipo, $originador);
	
	$inserted_id = $db->lastInsertId();
	
	$stmt = $suggestion->toggleVote($originador, $inserted_id);
	
	$return_arr = $stmt;
	} else {
		$return_arr = false;
	}
    
    // Encoding array in JSON format
    echo json_encode($return_arr);
 ?>