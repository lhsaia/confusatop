<?php

// ini_set( 'display_errors', true );
// error_reporting( E_ALL );

if(!isset($_POST['criar'])){
    session_start();
}

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){

    if(isset($_POST['criar'])){
        $nacionalidade = $_POST['pais'];
    } else {
        $sexo = $_POST['sexo'];
        $nacionalidade = $_POST['nacionalidade'];

        //estabelecer conexão com banco de dados
        include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
        include_once($_SERVER['DOCUMENT_ROOT']."/objetos/arbitros.php");
        include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
        include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");
        include_once($_SERVER['DOCUMENT_ROOT']."/objetos/liga.php");
        $database = new Database();
        $db = $database->getConnection();
        $arbitro = new TrioArbitragem($db);
        $pais = new Pais($db);
        $liga = new Liga($db);
        $time = new Time($db);
    }

    if($nacionalidade == 0){
        $nacionalidade = $pais->sorteiaNacionalidade($_SESSION['user_id']);
    }

    $errorCounter = 0;
    $error_msg = '';

    //arbitro
    $origemNomeArbitro = $pais->sorteioDemografico($nacionalidade, 0, $sexo);
    $origemSobrenomeArbitro = $pais->sorteioDemografico($nacionalidade,1, $sexo);
    $indiceMiscigenacaoArbitro = $pais->verificarMiscigenacao($nacionalidade,$origemNomeArbitro);
    $ocorrenciaNomeDuploArbitro = $pais->verificarNomeDuplo($nacionalidade,$origemNomeArbitro);

    //auxiliar 1
    $origemNomeAux1 = $pais->sorteioDemografico($nacionalidade, 0, $sexo);
    $origemSobrenomeAux1 = $pais->sorteioDemografico($nacionalidade,1, $sexo);
    $indiceMiscigenacaoAux1 = $pais->verificarMiscigenacao($nacionalidade,$origemNomeAux1);
    $ocorrenciaNomeDuploAux1 = $pais->verificarNomeDuplo($nacionalidade,$origemNomeAux1);

    //auxiliar 2
    $origemNomeAux2 = $pais->sorteioDemografico($nacionalidade, 0,$sexo);
    $origemSobrenomeAux2 = $pais->sorteioDemografico($nacionalidade,1,$sexo);
    $indiceMiscigenacaoAux2 = $pais->verificarMiscigenacao($nacionalidade,$origemNomeAux2);
    $ocorrenciaNomeDuploAux2 = $pais->verificarNomeDuplo($nacionalidade,$origemNomeAux2);

    $arbitro->randomTrio($nacionalidade, $origemNomeArbitro, $origemSobrenomeArbitro, $ocorrenciaNomeDuploArbitro, $indiceMiscigenacaoArbitro, $origemNomeAux1, $origemSobrenomeAux1, $ocorrenciaNomeDuploAux1, $indiceMiscigenacaoAux1, $origemNomeAux2, $origemSobrenomeAux2, $ocorrenciaNomeDuploAux2, $indiceMiscigenacaoAux2, $sexo);

if($errorCounter >0 ){
    $is_success = false;
    $error_msg .= "Houve " . $errorCounter . " erros durante a execução da inserção de árbitro";
} else {
    $is_success = true;
    $error_msg .= "";
}

} else {
    $is_success = false;
    $error_msg .= "Usuário não tem acesso para realizar essa ação";
}

if(!isset($_POST['criar'])){
    die(json_encode([ 'success'=> $is_success, 'error'=> $error_msg, 'arb_info' => $arbitro]));
}

?>
