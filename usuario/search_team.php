<?php  
	session_start();
	
	if(isset($_SESSION['user_id']) && $_SESSION['user_id'] <> 0){
		$user = $_SESSION['user_id'];
	} else {
		$user = 0;
	}

    $item_pesquisado = isset($_POST['searchText']) ? $_POST['searchText'] : '';

	//estabelecer conexão com banco de dados
	include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/liga.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/estadio.php");

	$database = new Database();
	$db = $database->getConnection();

	$usuario = new Usuario($db);
	$time = new Time($db);
	$estadio = new Estadio($db);
	$pais = new Pais($db);
	$liga = new Liga($db);

    $stmt = $time->readAllAjax($item_pesquisado, $user);
    $return_arr = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Encoding array in JSON format
    echo json_encode($return_arr);
 ?>