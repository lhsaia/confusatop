<?php  
session_start();
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

if($_POST['tipoBusca'] === "busca"){
    $idadeMin = explode(" - ",$_POST['idades'])[0];
    $idadeMax = explode(" - ",$_POST['idades'])[1];
    $valorMin = (explode(" - ",$_POST['valores'])[0]);
    $valorMin = preg_replace('/[^0-9]/', '', $valorMin);
    $valorMin = $valorMin * 1000;
    $valorMax = (explode(" - ",$_POST['valores'])[1]);
    $valorMax = preg_replace('/[^0-9]/', '', $valorMax);
    $valorMax = $valorMax * 1000;
    if($valorMax == 50000000){
        $valorMax = 99999999999;
    }
    $cobrancaFalta = (isset($_POST['cfalta'])) ? 1 : null;
    $disponivel = (isset($_POST['disponivel'])) ? 1 : null;
    $seletorPosicoes = (isset($_POST['contemtodos'])) ? 1 : null;

    $stringPosicoes = '';

    for($i = 1;$i < 16;$i++){
        if(isset($_POST[$i])){
            $stringPosicoes .= "1";
        } else {
            $stringPosicoes .= "0";
        }
    }   
} else if($_POST['tipoBusca'] === "buscaTecnico"){
    $estilo = ($_POST['estilo'] != 0) ? $_POST['estilo'] : null; 
}

    $nivelMin = explode(" - ",$_POST['niveis'])[0];
    $nivelMax = explode(" - ",$_POST['niveis'])[1];
    $semclube = (isset($_POST['semclube'])) ? 1 : null;
    $nome = (strcmp($_POST['nomejogador'],'') != 0) ? $_POST['nomejogador'] : null;
    $nacionalidade = ($_POST['nacionalidade'] != 0) ? $_POST['nacionalidade'] : null;
    $mentalidade = ($_POST['mentalidade'] != 0) ? $_POST['mentalidade'] : null;
    $sexo = (isset($_POST['sexo'])) ? 1 : 0;
    $apenasConfusa = (isset($_POST['apenasConfusa'])) ? 1 : null;

    include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");

    if($_POST['tipoBusca'] === "busca"){
        include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogador.php");
    }else if($_POST['tipoBusca'] === "buscaTecnico"){
        include_once($_SERVER['DOCUMENT_ROOT']."/objetos/tecnico.php");
    }

    $database = new Database();
    $db = $database->getConnection();
    

    
    if($_POST['tipoBusca'] === "busca"){
        $jogador = new Jogador($db); 
    }else if($_POST['tipoBusca'] === "buscaTecnico"){
        $tecnico = new Tecnico($db); 
    }
     
   

    if(isset($_SESSION['user_id'])){
        $usuarioLogado = $_SESSION['user_id'];
    } else {
        $usuarioLogado = -1;
    }
    

    if($_POST['tipoBusca'] === "busca"){
        $jogador = new Jogador($db); 
        $stmt = $jogador->pesquisaAvancada($nivelMin, $nivelMax, $idadeMin, $idadeMax, $cobrancaFalta, $disponivel, $nome, $nacionalidade, $mentalidade, $stringPosicoes, $seletorPosicoes, $semclube, $valorMin, $valorMax, $sexo, $apenasConfusa, $usuarioLogado);
    }else if($_POST['tipoBusca'] === "buscaTecnico"){
        $tecnico = new Tecnico($db); 
        $stmt = $tecnico->pesquisaAvancada($nivelMin, $nivelMax, $nome, $nacionalidade, $mentalidade, $estilo, $semclube, $sexo, $apenasConfusa, $usuarioLogado);
    }
    
    $return_arr = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Encoding array in JSON format
    echo json_encode($return_arr);
 ?>