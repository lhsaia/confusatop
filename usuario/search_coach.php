<?php  
	require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
	
	if(isset($_SESSION['user_id']) && $_SESSION['user_id'] <> 0){
		$user = $_SESSION['user_id'];
	} else {
		$user = 0;
	}

    $item_pesquisado = isset($_POST['searchText']) ? $_POST['searchText'] : '';

	include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/tecnico.php");

	$database = new Database();
	$db = $database->getConnection();
	$tecnico = new Tecnico($db);

    $stmt = $tecnico->readAllAjax($item_pesquisado, $user);
    $return_arr = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($return_arr);
?>
