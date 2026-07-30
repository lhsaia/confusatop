<?php  
	require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
	
	if(isset($_SESSION['user_id']) && $_SESSION['user_id'] <> 0){
		$user = $_SESSION['user_id'];
	} else {
		$user = 0;
	}

    $idCompeticao = $_POST['codigo_competicao'];
	$codigo_time = $_POST['codigo_time'];

	//estabelecer conexão com banco de dados
	include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/config/sqliteDatabase.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/competicao_clube.php");

	$database = new Database();
	$db = $database->getConnection();
	
	$lite_database = new SQLiteDatabase();
	$lite_database->fileName = $_SERVER['DOCUMENT_ROOT']."/competicoes/databases/".$idCompeticao."-database.db3";
	$ldb = $lite_database->getConnection();

	$usuario = new Usuario($db);
	$competicao = new Competicao_clube($db);
	
	$time = new Time($ldb);

    $info = $time->encontrarTimeExterno($codigo_time);
	
    // Encoding array in JSON format
    die(json_encode([ 'success'=> true, 'Nome'=> $info['Nome'], 'Escudo'=> $info['Escudo']]));
 ?>