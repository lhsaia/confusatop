<?php  
	try {
		require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

		$item_pesquisado = isset($_POST['searchText']) ? $_POST['searchText'] : '';

		//estabelecer conexão com banco de dados
		include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
		include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
		include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
		include_once($_SERVER['DOCUMENT_ROOT']."/objetos/competicao_clube.php");

		$database = new Database();
		$db = $database->getConnection();

		$usuario = new Usuario($db);
		$pais = new Pais($db);
		$competicao = new Competicao_clube($db);

		if (isset($_SESSION['user_id']) && $_SESSION['user_id'] <> 0) {
			$user = $_SESSION['user_id'];
		} else {
			$user = 0;
		}

		$stmt = $competicao->readAllAjax($item_pesquisado, $user);
		$return_arr = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		// Encoding array in JSON format
		echo json_encode($return_arr);
	} catch (Exception $e) {
		http_response_code(500);
		echo json_encode(['error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
	} catch (Error $e) {
		http_response_code(500);
		echo json_encode(['error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
	}
 ?>