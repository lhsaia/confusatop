<?php

require_once($_SERVER['DOCUMENT_ROOT']."/lib/functions.php");

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
$timeInfo = $time->coletarInformacoesTime($idTime);
$jogadoresInfo = $jogador->coletarJogadoresTime($idTime);
$tecnicoInfo = $tecnico->coletarTecnicoTime($idTime);
$estadioInfo = $estadio->coletarEstadioTime($idTime);
$climaInfo = $clima->coletarClimaTime($idTime);

if(empty($timeInfo)){
    die(json_encode(['error' => 'Time não encontrado.']));
}

$error_messages = [];

if($elencoMenor = $time->verificarElencoMenor(null, [$idTime])){
    $error_messages[] = "Há elencos com menos de 11 jogadores.";
}

if($elencoMaior = $time->verificarElencoMaior(null, [$idTime])){
    $error_messages[] = "Há elencos com mais de 23 jogadores (fora suplentes).";
}

if($capitaoTime = $time->verificarCapitao(null, [$idTime])){
    $error_messages[] = "O time não possui exatamente 1 capitão titular escalado.";
}

if($penaltisTime = $time->verificarPenaltis(null, [$idTime])){
    $error_messages[] = "O time não possui todos os 3 cobradores de pênaltis titulares definidos.";
}

if($goleirosTime = $time->verificarGoleiros(null, [$idTime])){
    $error_messages[] = "O time não possui exatamente 1 goleiro titular escalado.";
}

if($escalacaoTime = $time->verificarEscalacoes(null, [$idTime])){
    $error_messages[] = "O time não possui os 11 jogadores titulares escalados.";
}

if($aposentadosTime = $time->verificarAposentados(null, [$idTime])){
    $error_messages[] = "O time possui jogadores com idade acima da permitida (> 45 anos).";
}

if($tecnicosTimes = $time->verificarTecnicos(null, [$idTime])){
    $error_messages[] = "O time não possui técnico cadastrado ou possui técnicos demais.";
}

if($climaEstadioTimes = $time->verificarClimaEstadio(null, [$idTime])){
    $error_messages[] = "O time não possui estádio associado ou o estádio está sem clima cadastrado.";
}

if(!empty($error_messages)){
    die(json_encode(['error' => implode('<br>', $error_messages)]));
}

$infos[] = $timeInfo;
$infos[] = $jogadoresInfo;
$infos[] = $tecnicoInfo;
$infos[] = $estadioInfo;
$infos[] = $climaInfo;
$escudoPath = __DIR__ . "/../images/escudos/" . $infos[0][0]["Escudo"];
if (!empty($infos[0][0]["Escudo"]) && $infos[0][0]["Escudo"] != "0.png" && file_exists($escudoPath) && is_file($escudoPath)) {
    $escudoContent = @file_get_contents($escudoPath);
    $infos[] = ($escudoContent !== false) ? base64_encode($escudoContent) : "";
} else {
    $infos[] = "";
}

$uni1Path = __DIR__ . "/../images/uniformes/" . $infos[0][0]["Uniforme1"];
if (!empty($infos[0][0]["Uniforme1"]) && $infos[0][0]["Uniforme1"] != "semclube1.png" && file_exists($uni1Path) && is_file($uni1Path)) {
    $uni1Content = @file_get_contents($uni1Path);
    $infos[] = ($uni1Content !== false) ? base64_encode($uni1Content) : "";
} else {
    $infos[] = "";
}

$uni2Path = __DIR__ . "/../images/uniformes/" . $infos[0][0]["Uniforme2"];
if (!empty($infos[0][0]["Uniforme2"]) && $infos[0][0]["Uniforme2"] != "semclube2.png" && file_exists($uni2Path) && is_file($uni2Path)) {
    $uni2Content = @file_get_contents($uni2Path);
    $infos[] = ($uni2Content !== false) ? base64_encode($uni2Content) : "";
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
