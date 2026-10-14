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
if (!function_exists('converteImagemYmtBase64')) {
    function converteImagemYmtBase64($filePath) {
        if (empty($filePath) || !file_exists($filePath) || !is_file($filePath)) {
            return "";
        }
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        // Se for WebP, converter para PNG pois o simulador Hexacolor (Java) não suporta WebP nativamente
        if ($ext === 'webp' && function_exists('imagecreatefromwebp')) {
            $img = @imagecreatefromwebp($filePath);
            if ($img) {
                imagealphablending($img, false);
                imagesavealpha($img, true);
                ob_start();
                imagepng($img);
                $pngData = ob_get_clean();
                imagedestroy($img);
                if ($pngData !== false && strlen($pngData) > 0) {
                    return base64_encode($pngData);
                }
            }
        }

        // Se for JPG/JPEG, converter para PNG para manter compatibilidade com o formato esperado pelo Hexacolor
        if (($ext === 'jpg' || $ext === 'jpeg') && function_exists('imagecreatefromjpeg')) {
            $img = @imagecreatefromjpeg($filePath);
            if ($img) {
                ob_start();
                imagepng($img);
                $pngData = ob_get_clean();
                imagedestroy($img);
                if ($pngData !== false && strlen($pngData) > 0) {
                    return base64_encode($pngData);
                }
            }
        }

        $content = @file_get_contents($filePath);
        return ($content !== false) ? base64_encode($content) : "";
    }
}

$rawEscudo = $infos[0][0]["Escudo"] ?? '';
$escudoPath = !empty($rawEscudo) ? (__DIR__ . "/../images/escudos/" . $rawEscudo) : '';

// Se for seleção (status > 0) e não tiver escudo personalizado ou estiver com 0.png, usa a bandeira do país como escudo
$isSelecao = isset($infos[0][0]["status"]) && (int)$infos[0][0]["status"] > 0;
if (($rawEscudo === '0.png' || empty($rawEscudo) || !file_exists($escudoPath)) && $isSelecao && !empty($infos[0][0]["bandeiraPais"])) {
    $bandeiraPath = __DIR__ . "/../images/bandeiras/" . $infos[0][0]["bandeiraPais"];
    if (file_exists($bandeiraPath) && is_file($bandeiraPath)) {
        $escudoPath = $bandeiraPath;
        $rawEscudo = $infos[0][0]["bandeiraPais"];
    }
}

if (!empty($rawEscudo) && $rawEscudo !== "0.png" && file_exists($escudoPath) && is_file($escudoPath)) {
    $escudoBase64 = converteImagemYmtBase64($escudoPath);
    $infos[] = $escudoBase64;
    if (!empty($escudoBase64)) {
        $infos[0][0]["Escudo"] = "team" . $idTime . ".png";
    }
} else {
    $infos[] = "";
}

$rawUni1 = $infos[0][0]["Uniforme1"] ?? '';
$uni1Path = (!empty($rawUni1) && $rawUni1 !== "semclube1.png") ? (__DIR__ . "/../images/uniformes/" . $rawUni1) : '';
if (!empty($rawUni1) && $rawUni1 !== "semclube1.png" && file_exists($uni1Path) && is_file($uni1Path)) {
    $uni1Base64 = converteImagemYmtBase64($uni1Path);
    $infos[] = $uni1Base64;
    if (!empty($uni1Base64)) {
        $infos[0][0]["Uniforme1"] = "1-team" . $idTime . ".png";
    }
} else {
    $infos[] = "";
}

$rawUni2 = $infos[0][0]["Uniforme2"] ?? '';
$uni2Path = (!empty($rawUni2) && $rawUni2 !== "semclube2.png") ? (__DIR__ . "/../images/uniformes/" . $rawUni2) : '';
if (!empty($rawUni2) && $rawUni2 !== "semclube2.png" && file_exists($uni2Path) && is_file($uni2Path)) {
    $uni2Base64 = converteImagemYmtBase64($uni2Path);
    $infos[] = $uni2Base64;
    if (!empty($uni2Base64)) {
        $infos[0][0]["Uniforme2"] = "2-team" . $idTime . ".png";
    }
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
