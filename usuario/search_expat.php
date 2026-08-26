<?php  
	require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
	
	if(isset($_SESSION['user_id']) && $_SESSION['user_id'] <> 0){
		$user = $_SESSION['user_id'];
	} else {
		$user = 0;
	}

    $item_pesquisado = isset($_POST['searchText']) ? $_POST['searchText'] : '';

	include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogador.php");

	$database = new Database();
	$db = $database->getConnection();
	$jogador = new Jogador($db);

    $stmt = $jogador->readAllExpatAjax($item_pesquisado, $user);
    $return_arr = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row['posicoesFormatadas'] = $jogador->listaPosicoes($row['StringPosicoes'] ?? '');
        $row['podeRepatriar'] = $jogador->testeInatividade($row['ID']);
        $return_arr[] = $row;
    }
    
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($return_arr);
?>
