<?php  

    $times = $_POST['times'];

    include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogos.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/competicao_clube.php");
    
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $compSync = new Competicao_clube($db);
        $compSync->sincronizarJogosRankingEncerrados();
    } catch (\Throwable $e) {}

    $jogo = new Jogo($db);  
	$pais = new Pais($db);  

    $stmt = $jogo->pesquisaRetrospecto($times);
    $return_arr = $stmt->fetchAll(PDO::FETCH_ASSOC);
	
	$merger = array("jogos" => $return_arr);
	$json_merge = json_encode($merger);
    
    // Encoding array in JSON format
    echo $json_merge;
 ?>