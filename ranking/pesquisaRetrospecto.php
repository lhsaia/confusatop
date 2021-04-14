<?php  

    $times = $_POST['times'];

    include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogos.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
    
    $database = new Database();
    $db = $database->getConnection();
    
    $jogo = new Jogo($db);  
	$pais = new Pais($db);  

    $stmt = $jogo->pesquisaRetrospecto($times);
    $return_arr = $stmt->fetchAll(PDO::FETCH_ASSOC);
	
	$merger = array("jogos" => $return_arr);
	$json_merge = json_encode($merger);
    
    // Encoding array in JSON format
    echo $json_merge;
 ?>