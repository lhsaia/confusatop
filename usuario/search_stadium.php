<?php  
	require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
	
	if(isset($_SESSION['user_id']) && $_SESSION['user_id'] <> 0){
		$user = $_SESSION['user_id'];
	} else {
		$user = 0;
	}

    $item_pesquisado = isset($_POST['searchText']) ? $_POST['searchText'] : '';

	include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/estadio.php");

	$database = new Database();
	$db = $database->getConnection();
	$estadio = new Estadio($db);

    $stmt = $estadio->readAllAjax($item_pesquisado, $user);
    $return_arr = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($return_arr as &$row) {
        if (isset($row['Nome'])) {
            $row['Nome'] = html_entity_decode((string)$row['Nome'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
    }
    unset($row);
    
    echo json_encode($return_arr);
?>
