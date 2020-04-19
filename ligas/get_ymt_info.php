<?php

require("/home/lhsaia/confusa.top/lib/functions.php");

// ini_set( 'display_errors', true );
// error_reporting( E_ALL );
$idTime = $_POST['idTime'];

//estabelecer conexão com banco de dados
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogador.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/tecnico.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/estadio.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/clima.php");
$database = new Database();
$db = $database->getConnection();
$time = new Time($db);
$jogador = new Jogador($db);
$tecnico = new Tecnico($db);
$estadio = new Estadio($db);
$clima = new Clima($db);



//coletar informações
$infos = array();
$infos[] = $time->coletarInformacoesTime($idTime);
$infos[] = $jogador->coletarJogadoresTime($idTime);
$infos[] = $tecnico->coletarTecnicoTime($idTime);
$infos[] = $estadio->coletarEstadioTime($idTime);
$infos[] = $clima->coletarClimaTime($idTime);
if($infos[0][0]["Escudo"] != "0.png"){
  $infos[] = base64_encode(file_get_contents(__DIR__ ."/../images/escudos/".$infos[0][0]["Escudo"]));
} else {
  $infos[] = "";
}
if($infos[0][0]["Uniforme1"] != "semclube1.png"){
  $infos[] = base64_encode(file_get_contents(__DIR__ ."/../images/uniformes/".$infos[0][0]["Uniforme1"]));
}  else {
  $infos[] = "";
}
if($infos[0][0]["Uniforme2"] != "semclube2.png"){
  $infos[] = base64_encode(file_get_contents(__DIR__ ."/../images/uniformes/".$infos[0][0]["Uniforme2"]));
} else {
  $infos[] = "";
}

for($i = 0; $i< sizeof($infos[1]); $i++){
    
    //modificacaoAtributosJogadores
if($infos[1][$i]["StringPosicoes"][0] == 1){
    $infos_modificadas[] = adjustAttributes(true, $infos[1][$i]["Nivel"], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, $infos[1][$i]["Reflexos"], $infos[1][$i]["Seguranca"], $infos[1][$i]["Saidas"], $infos[1][$i]["JogoAereo"], $infos[1][$i]["Lancamentos"], $infos[1][$i]["DefesaPenaltis"]);
    
    //print_r($infos_modificadas);
    
    $infos[1][$i]["Seguranca"] = $infos_modificadas[0]["seguranca"];
    $infos[1][$i]["Reflexos"] = $infos_modificadas[0]["reflexos"];
    $infos[1][$i]["Saidas"] = $infos_modificadas[0]["saidas"];
    $infos[1][$i]["JogoAereo"] = $infos_modificadas[0]["jogoAereo"];
    $infos[1][$i]["Lancamentos"] = $infos_modificadas[0]["lancamentos"];
    $infos[1][$i]["DefesaPenaltis"] = $infos_modificadas[0]["defesaPenaltis"];
    
} else {
    $infos_modificadas[] = adjustAttributes(false, $infos[1][$i]["Nivel"], $infos[1][$i]["Marcacao"], $infos[1][$i]["Desarme"], $infos[1][$i]["VisaoJogo"], $infos[1][$i]["Movimentacao"], $infos[1][$i]["Cruzamentos"], $infos[1][$i]["Cabeceamento"], $infos[1][$i]["Tecnica"], $infos[1][$i]["ControleBola"], $infos[1][$i]["Finalizacao"], $infos[1][$i]["FaroGol"], $infos[1][$i]["Velocidade"], $infos[1][$i]["Forca"], 0, 0, 0, 0, 0, 0);
    
        $infos[1][$i]["Marcacao"] = $infos_modificadas[0]["marcacao"];
        $infos[1][$i]["Desarme"] = $infos_modificadas[0]["desarme"];
        $infos[1][$i]["VisaoJogo"] = $infos_modificadas[0]["visaoJogo"];
        $infos[1][$i]["Cruzamentos"] = $infos_modificadas[0]["cruzamentos"];
        $infos[1][$i]["Tecnica"] = $infos_modificadas[0]["tecnica"];
        $infos[1][$i]["Finalizacao"] = $infos_modificadas[0]["finalizacao"];
        $infos[1][$i]["Movimentacao"] = $infos_modificadas[0]["movimentacao"];
        $infos[1][$i]["Cabeceamento"] = $infos_modificadas[0]["cabeceamento"];
        $infos[1][$i]["ControleBola"] = $infos_modificadas[0]["controleBola"];
        $infos[1][$i]["FaroGol"] = $infos_modificadas[0]["faroGol"];
        $infos[1][$i]["Velocidade"] = $infos_modificadas[0]["velocidade"];
        $infos[1][$i]["Forca"] = $infos_modificadas[0]["forca"];
        
        
    
}
        unset($infos_modificadas);
       // unset($infos[1][$i]);

}

//print("<pre>".print_r($infos,true)."</pre>");
die(json_encode($infos));


?>
