<?php

//die(json_encode([ 'success'=> true, 'errors'=> 'nothing']));
 ini_set( 'display_errors', true );
 error_reporting( E_ALL );
session_start();
if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){

  $listaTimes = $_POST['array_times'];
  $num_equipes = $_POST['num_equipes'];
  $codigo_genero = $_POST['codigo_genero'];
  $codigo_competicao = $_POST['codigo_competicao'];
  $codigo_federacao = $_POST['codigo_federacao'];
  $codigo_sede = $_POST['codigo_sede'];

   //print_r($_POST);

    //estabelecer conexão com banco de dados
    include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/export_torneios.php");
    $database = new Database();
    $db = $database->getConnection();
    $time = new Time($db);
    $torneio = new ExportTorneio($db);
    $error_msg = "";

    if($codigo_competicao != 0){
      $torneio->salvar($codigo_competicao,$codigo_federacao,$codigo_genero,$num_equipes,$listaTimes, $codigo_sede);
    }

    if($elencoMenor = $time->verificarElencoMenor(null,$listaTimes)){
        $error_msg .= "Há elencos com menos de 11 jogadores. </br>";
        foreach($elencoMenor as $timeErro){
            $error_msg .= $timeErro[0] . "</br>";
        }
    }

    if($elencoMaior = $time->verificarElencoMaior(null,$listaTimes)){
        $error_msg .= "Há elencos com mais de 23 jogadores (fora suplentes)</br>";
        foreach($elencoMaior as $timeErro){
            $error_msg .= $timeErro[0] . "</br>";
        }
    }

    if($capitaoTime = $time->verificarCapitao(null,$listaTimes)){
        $error_msg .= "Há times sem capitão. </br>";
        foreach($capitaoTime as $timeErro){
            $error_msg .= $timeErro[0] . "</br>";
        }
    }

    if($penaltisTime = $time->verificarPenaltis(null,$listaTimes)){
        $error_msg .= "Há times sem todos os cobradores. </br>";
        foreach($penaltisTime as $timeErro){
            $error_msg .= $timeErro[0] . "</br>";
        }
    }

    if($goleirosTime = $time->verificarGoleiros(null,$listaTimes)){
        $error_msg .= "Há times com número incorreto de goleiros escalados </br>";
        foreach($goleirosTime as $timeErro){
            $error_msg .= $timeErro[0] . "</br>";
        }
    }

    if($escalacaoTime = $time->verificarEscalacoes(null,$listaTimes)){
        $error_msg .= "Há times sem os onze jogadores escalados. </br>";
        foreach($escalacaoTime as $timeErro){
            $error_msg .= $timeErro[0] . "</br>";
        }
    }

    if($aposentadosTime = $time->verificarAposentados(null,$listaTimes)){
        $error_msg .= "Há times com jogadores acima da idade permitida pelo Hexacolor. </br>";
        foreach($aposentadosTime as $timeErro){
            $error_msg .= $timeErro[0] . "</br>";
        }
    }

    if($tecnicosTimes = $time->verificarTecnicos(null,$listaTimes)){
        $error_msg .= "Há times sem técnico. </br>";
        foreach($tecnicosTimes as $timeErro){
            $error_msg .= $timeErro[0] . "</br>";
        }
    }

    //exec('php exportar_database.php ' . $_SESSION['user_id']);


} else {
    $error_msg = "Usuário não tem acesso para realizar essa ação";
}

if($error_msg != ""){
    $success = false;
} else {
    $success = true;
}

$errors = $error_msg;
die(json_encode([ 'success'=> $success, 'errors'=> $errors]));


?>
