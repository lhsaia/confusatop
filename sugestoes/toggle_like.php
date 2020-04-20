<?php  
	session_start();
    $suggestion_id = $_POST['id'];
	
    include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/suggestion.php");
    
    $database = new Database();
    $db = $database->getConnection();
    
    $suggestion = new Suggestion($db);  

	if(isset($_SESSION['user_id'])){	
		$user_id = $_SESSION['user_id'];
		$stmt = $suggestion->toggleVote($user_id, $suggestion_id);
		$return_arr = $stmt;
	} else{
		$return_arr = false;
	}
	
    // Encoding array in JSON format
    echo json_encode($return_arr);
 ?>