<?php  
	session_start();
	
	if(isset($_SESSION['user_id']) && $_SESSION['user_id'] <> 0){
		$user = $_SESSION['user_id'];
	} else {
		$user = 0;
	}

    // $item_pesquisado = isset($_POST['searchText']) ? $_POST['searchText'] : '';
	$teamId = isset($_POST['teamId']) ? $_POST['teamId'] : '';
	$transactionType = isset($_POST['transactionType']) ? $_POST['transactionType'] : 0;
	$startDate = (isset($_POST['startDate']) && $_POST['startDate'] != "" ) ? $_POST['startDate'] : null;
	$endDate = (isset($_POST['endDate']) && $_POST['endDate'] != "" ) ? $_POST['endDate'] : null;

	//estabelecer conexão com banco de dados
	include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/transaction.php");

	$database = new Database();
	$db = $database->getConnection();

	$usuario = new Usuario($db);
	$transacao = new Transaction($db);

    $stmt = $transacao->retrieveTransactions($teamId, $transactionType, $startDate, $endDate);
    $return_arr = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Encoding array in JSON format
    echo json_encode($return_arr);
 ?>