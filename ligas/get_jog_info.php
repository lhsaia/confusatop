<?php

require("/home/lhsaia/confusa.top/lib/functions.php");

// ini_set( 'display_errors', true );
// error_reporting( E_ALL );
$idJogador = $_POST['idJogador'];
$idTime = $_POST['idTime'];

//estabelecer conexão com banco de dados
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogador.php");
$database = new Database();
$db = $database->getConnection();
$jogador = new Jogador($db);

//coletar informações
$infos = array();
$infos[] = $jogador->coletarInformacoesJogador($idJogador, $idTime);

//modificar atributos

//print_r($infos);

if($infos[0][0]["StringPosicoes"][0] == 1){
    $infos_modificadas[] = adjustAttributes(true, $infos[0][0]["Nivel"], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, $infos[0][0]["Reflexos"], $infos[0][0]["Seguranca"], $infos[0][0]["Saidas"], $infos[0][0]["JogoAereo"], $infos[0][0]["Lancamentos"], $infos[0][0]["DefesaPenaltis"]);
    
    //print_r($infos_modificadas);
    
    $infos[0][0]["Seguranca"] = $infos_modificadas[0]["seguranca"];
    $infos[0][0]["Reflexos"] = $infos_modificadas[0]["reflexos"];
    $infos[0][0]["Saidas"] = $infos_modificadas[0]["saidas"];
    $infos[0][0]["JogoAereo"] = $infos_modificadas[0]["jogoAereo"];
    $infos[0][0]["Lancamentos"] = $infos_modificadas[0]["lancamentos"];
    $infos[0][0]["DefesaPenaltis"] = $infos_modificadas[0]["defesaPenaltis"];
    
} else {
    $infos_modificadas[] = adjustAttributes(false, $infos[0][0]["Nivel"], $infos[0][0]["Marcacao"], $infos[0][0]["Desarme"], $infos[0][0]["VisaoJogo"], $infos[0][0]["Movimentacao"], $infos[0][0]["Cruzamentos"], $infos[0][0]["Cabeceamento"], $infos[0][0]["Tecnica"], $infos[0][0]["ControleBola"], $infos[0][0]["Finalizacao"], $infos[0][0]["FaroGol"], $infos[0][0]["Velocidade"], $infos[0][0]["Forca"], 0, 0, 0, 0, 0, 0);
    
        $infos[0][0]["Marcacao"] = $infos_modificadas[0]["marcacao"];
        $infos[0][0]["Desarme"] = $infos_modificadas[0]["desarme"];
        $infos[0][0]["VisaoJogo"] = $infos_modificadas[0]["visaoJogo"];
        $infos[0][0]["Cruzamentos"] = $infos_modificadas[0]["cruzamentos"];
        $infos[0][0]["Tecnica"] = $infos_modificadas[0]["tecnica"];
        $infos[0][0]["Finalizacao"] = $infos_modificadas[0]["finalizacao"];
        $infos[0][0]["Movimentacao"] = $infos_modificadas[0]["movimentacao"];
        $infos[0][0]["Cabeceamento"] = $infos_modificadas[0]["cabeceamento"];
        $infos[0][0]["ControleBola"] = $infos_modificadas[0]["controleBola"];
        $infos[0][0]["FaroGol"] = $infos_modificadas[0]["faroGol"];
        $infos[0][0]["Velocidade"] = $infos_modificadas[0]["velocidade"];
        $infos[0][0]["Forca"] = $infos_modificadas[0]["forca"];
    
}

die(json_encode($infos));

?>
