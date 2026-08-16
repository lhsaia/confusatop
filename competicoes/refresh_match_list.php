<?php  
	require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
	
	if(isset($_SESSION['user_id']) && $_SESSION['user_id'] <> 0){
		$user = $_SESSION['user_id'];
	} else {
		$user = 0;
	}

    $idCompeticao = $_POST['codigo_competicao'];

	//estabelecer conexão com banco de dados
	include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/competicao_clube.php");


	$database = new Database();
	$db = $database->getConnection();

	$usuario = new Usuario($db);
	$competicao = new Competicao_clube($db);

    $stmt = $competicao->carregarListaJogos($idCompeticao);
    $return_arr = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    /*
    if(count($return_arr) == 0){
        // Log para debug se retornar vazio
        error_log("refresh_match_list: Nenhum jogo encontrado para competicao ID " . $idCompeticao);
    }
    */

    // Encoding array in JSON format
    echo json_encode($return_arr);
 ?>