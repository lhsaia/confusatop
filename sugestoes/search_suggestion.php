<?php  
	session_start();
	
	if(isset($_SESSION['user_id']) && $_SESSION['user_id'] <> 0){
		$user = $_SESSION['user_id'];
	} else {
		$user = 0;
	}

    $item_pesquisado = isset($_POST['searchText']) ? $_POST['searchText'] : '';

    include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/suggestion.php");
    
    $database = new Database();
    $db = $database->getConnection();
    
    $suggestion = new Suggestion($db);  

    $stmt = $suggestion->readSuggestions($item_pesquisado, $user);
    $return_arr = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Encoding array in JSON format
    echo json_encode($return_arr);
 ?>