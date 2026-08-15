<?php  

    $item_pesquisado = isset($_POST['searchText']) ? $_POST['searchText'] : '';

    include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogos_clube.php");
    
    $database = new Database();
    $db = $database->getConnection();
    
    $jogo = new Jogo($db);  

    // Uses the same generic search as ranking, which works well for general listing
    $stmt = $jogo->pesquisaGeral($item_pesquisado);
    $return_arr = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($return_arr);
 ?>
