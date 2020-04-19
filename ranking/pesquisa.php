<?php  

    $item_pesquisado = isset($_POST['searchText']) ? $_POST['searchText'] : '';

    include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogos.php");
    
    $database = new Database();
    $db = $database->getConnection();
    
    $jogo = new Jogo($db);  

    $stmt = $jogo->pesquisaGeral($item_pesquisado);
    $return_arr = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Encoding array in JSON format
    echo json_encode($return_arr);
 ?>