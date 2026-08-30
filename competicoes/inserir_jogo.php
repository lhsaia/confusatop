<?php

// ini_set( 'display_errors', true );
// error_reporting( E_ALL );
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){

    if($_SESSION['emTestes'] ?? false){
        die(json_encode(['success' => false, 'error' => 'Usuários em período de testes não podem inserir partidas.']));
    }

    $id_competicao = $_POST['codigo_competicao'];
    $timeA = $_POST['timeA'];
    $timeB = $_POST['timeB'];
	$fase = $_POST['fase'];
	$arbitro = $_POST['arbitro'];
	$estadio = $_POST['estadio'];
	$datetime = $_POST['datetime'];
	$neutro = $_POST['neutro'];
    $error_msg = "";
	
	//estabelecer conexão com bancos de dados
    include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/config/sqliteDatabase.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/competicao_clube.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/arbitros.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/estadio.php");
    $database = new Database();
    $db = $database->getConnection();
    $competicao = new Competicao_clube($db);
	
    $compInfo = $competicao->readInfo($id_competicao);
    $dono = isset($compInfo['dono']) ? (int)$compInfo['dono'] : 0;
    $isAdmin = (isset($_SESSION['admin_status']) && $_SESSION['admin_status'] == 1);
    if(!$isAdmin && $_SESSION['user_id'] != $dono){
        die(json_encode(array("success" => false, "error" => "Você não tem permissão para inserir jogos nesta competição.")));
    }
	
	$liteDatabase = new SQLiteDatabase();
	$liteDatabase->fileName = $_SERVER['DOCUMENT_ROOT']."/competicoes/databases/".$id_competicao."-database.db3";
	$ldb = $liteDatabase->getConnection();
	$arbitro_objeto = new TrioArbitragem($ldb);
	$estadio_objeto = new Estadio($ldb);
	
	// sorteio de arbitro se necessário
	
	// sorteio de estádio se necessário


    //alterar arbitro
    if($competicao->inserirJogo($id_competicao,$timeA,$timeB,$fase,$arbitro,$estadio, $datetime, $neutro)){
        $is_success = true;
        $error_msg .= "";
    } else {
        $is_success = false;
        $error_msg .= "Falha ao inserir partida!";
    }

} else {
    $is_success = false;
    $error_msg .= "Usuário não tem acesso para realizar essa ação";
}

die(json_encode([ 'success'=> $is_success, 'error'=> $error_msg]));
?>
