<?php

session_start();

if(file_exists("../sqlitedb/".$_SESSION['user_id'].".db3")){
    unlink("../sqlitedb/".$_SESSION['user_id'].".db3");
}

include_once($_SERVER['DOCUMENT_ROOT']."/config/sqliteDatabase.php");
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogador.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/arbitros.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/tecnico.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/estadio.php");

$database = new SQLiteDatabase();
$database->fileName = "../sqlitedb/".$_SESSION['user_id'].".db3";
$database->getConnection();
$database->prepareTables();
$database->initialMainValues();

$mainDatabase = new Database();
$db = $mainDatabase->getConnection();


$pais = new Pais($db);

$logFileName = "../sqlitedb/".$_SESSION['user_id'] . ".txt";
//$myfile = fopen($logFileName, "w") or die("Impossível abrir registro de times!");
//$txt = "";
$exportFiles = array();

$exportFiles[] = [$_SERVER['DOCUMENT_ROOT']."/sqlitedb/".$_SESSION['user_id'].".db3","data/database.db3"];

//determinar paises do usuario e criar um array
$stmtPais = $pais->read($_SESSION['user_id']);
$listaPaises = array();
while ($row_pais = $stmtPais->fetch(PDO::FETCH_ASSOC)){
    extract($row_pais);
    $listaPaises[] = $id;
}

$queryInsercao = "";
$contagemArbitros = 0;

//inicio foreach paises
foreach($listaPaises as $idPais){

    $time = new Time($db);
    $jogador = new Jogador($db);
    $trioarbitragem = new TrioArbitragem($db);
    $tecnico = new Tecnico($db);
    $estadio = new Estadio($db);

    //buscar trios de arbitragem e adicionar na query (colocar padrão só se tiver 0)
    $stmt = $trioarbitragem->exportacao($idPais);
    $listaArbitros = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        $listaArbitros[] = [$row['id'], $row['nomeArbitro'], $row['nomeAuxiliarUm'], $row['nomeAuxiliarDois'], $row['estilo']];
    }

    if(isset($listaArbitros)){
        foreach($listaArbitros as $arbitro){

            $queryInsercao = "INSERT INTO trioarbitragem VALUES (?,?,?,?,?)";
            $database->runQuery($queryInsercao,$arbitro);
            $contagemArbitros++;
        
        }
    } 
    //buscar tecnico e adicionar na query
    $stmt = $tecnico->exportacao($idPais);
    
    $listaTecnicos = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        $listaTecnicos[] = [$row['ID'], $row['Nome'], $row['Idade'], $row['Nivel'], $row['Mentalidade'], $row['Estilo']];
    }

    //echo '<pre>Parametro: ' . var_export($listaTecnicos, true) . '</pre>'; 
    if(isset($listaTecnicos)){
        foreach($listaTecnicos as $tecnico){

            $queryInsercao = "INSERT INTO tecnico VALUES (?,?,?,?,?,?)";
            $database->runQuery($queryInsercao,$tecnico);
        }
    } 

    //buscar posicoes dos jogadores e adicionar na query
    $stmt = $jogador->exportacao($idPais);
    $listaPosicoes = array();
    $listaJogadores = array();
    $listaAtributosJogador = array();
    $listaAtributosGoleiro = array();
    $listaNacionalidades = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        $listaPosicoes[] = array_merge([$row['idJogador']],str_split($row['StringPosicoes']));
        $listaJogadores[] = [$row['idJogador'], $row['nomeJogador'], $row['Idade'], $row['Nivel'], '0' , '0', $row['Mentalidade'], $row['CobradorFalta']];
        $listaNacionalidades[] = [$row['idJogador'], ($row['Nacionalidade'] != null ? $row['Nacionalidade'] : '-') ];
        if($row['StringPosicoes'][0] == 1){
            $listaAtributosGoleiro[] = [$row['idJogador'],$row['Reflexos'], $row['Seguranca'], $row['Saidas'], $row['JogoAereo'], $row['Lancamentos'], $row['DefesaPenaltis'], $row['Determinacao'], $row['DeterminacaoOriginal']];
        } else {
            $listaAtributosJogador[] = [$row['idJogador'],$row['Marcacao'], $row['Desarme'], $row['VisaoJogo'], $row['Movimentacao'], $row['Cruzamentos'], $row['Cabeceamento'], $row['Tecnica'], $row['ControleBola'], $row['Finalizacao'], $row['FaroGol'], $row['Velocidade'], $row['Forca'], $row['Determinacao'], $row['DeterminacaoOriginal']];
        }   
    }

    if(isset($listaPosicoes)){
        foreach($listaPosicoes as $posicoes){

            $queryInsercao = "INSERT INTO posicaojogador VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            $database->runQuery($queryInsercao,$posicoes);
        
        }
    } 

    //buscar nacionalidades e adicionar na query
    if(isset($listaNacionalidades)){
        foreach($listaNacionalidades as $nacionalidade){

            $queryInsercao = "INSERT INTO nacionalidades VALUES (?,?)";
            $database->runQuery($queryInsercao,$nacionalidade);
        
        }
    } 

    //buscar jogadores e adicionar na query
    if(isset($listaJogadores)){
        foreach($listaJogadores as $jogador){

            $queryInsercao = "INSERT INTO jogador VALUES (?,?,?,?,?,?,?,?)";
            $database->runQuery($queryInsercao,$jogador);
        
        }
    } 

    //buscar atributos jogador e adicionar na query
    if(isset($listaAtributosJogador)){
        foreach($listaAtributosJogador as $index => $atributosJogador){

            $queryInsercao = "INSERT INTO atributosjogador VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            $database->runQuery($queryInsercao,$atributosJogador);
        
        }
    } 

    //buscar atributos goleiro e adicionar na query
    if(isset($listaAtributosGoleiro)){
        foreach($listaAtributosGoleiro as $index => $atributosGoleiro){

            $queryInsercao = "INSERT INTO atributosgoleiro VALUES (?,?,?,?,?,?,?,?,?)";
            $database->runQuery($queryInsercao,$atributosGoleiro);
        
        }
    } 

    //buscar estadio e adicionar na query
    $stmt = $estadio->exportacao($idPais);
    $listaEstadios = array();
    $listaClimas = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        $listaEstadios[] = [$row['ID'], $row['Nome'], $row['Capacidade'], $row['Clima'], $row['Altitude'], $row['Caldeirao']];
        $listaClimas[] = [$row['idClima'], $row['nomeClima'], $row['TempVerao'], $row['EstiloVerao'], $row['TempOutono'], $row['EstiloOutono'],$row['TempInverno'], $row['EstiloInverno'],$row['TempPrimavera'], $row['EstiloPrimavera'],$row['Hemisferio']];
       
    }

    if(isset($listaEstadios)){
        foreach($listaEstadios as $index => $estadio){

            $queryInsercao = "INSERT INTO estadio VALUES (?,?,?,?,?,?)";
            $database->runQuery($queryInsercao,$estadio);
        
        }
    } 

    //buscar climas e adicionar na query
    if(isset($listaClimas)){
        foreach($listaClimas as $index => $clima){

            $queryInsercao = "INSERT INTO clima VALUES (?,?,?,?,?,?,?,?,?,?,?)";
            $database->runQuery($queryInsercao,$clima);
        
        }
    } 

    //buscar clubes e adicionar na query
    $stmt = $time->exportacao($idPais);
    $listaTimes = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){

        //tratar uniforme e simbolo
        $escudoArray = explode(".",$row['Escudo']);
        $uni1Array = explode(".",$row['Uniforme1']);
        $uni2Array = explode(".",$row['Uniforme2']);
        $baseFileName = "team" . $row['ID'];
        $escudoTratado = "Escudos/" . $baseFileName . "." .$escudoArray[1];
        $uni1Tratado = "Uniformes/1-" . $baseFileName . "." .$uni1Array[1];
        $uni2Tratado = "Uniformes/2-" . $baseFileName . "." .$uni2Array[1];

        file_put_contents($_SERVER['DOCUMENT_ROOT']."/sqlitedb/".$escudoTratado, base64_decode($escudoArray[0]));
        file_put_contents($_SERVER['DOCUMENT_ROOT']."/sqlitedb/".$uni1Tratado, base64_decode($uni1Array[0]));
        file_put_contents($_SERVER['DOCUMENT_ROOT']."/sqlitedb/".$uni2Tratado, base64_decode($uni2Array[0]));

        //$txt .= $escudoTratado . "\n";
        $exportFiles[] = [$_SERVER['DOCUMENT_ROOT']."/sqlitedb/".$escudoTratado, $escudoTratado];
        //$txt .= $uni1Tratado . "\n";
        $exportFiles[] = [$_SERVER['DOCUMENT_ROOT']."/sqlitedb/".$uni1Tratado, $uni1Tratado];
        //$txt .= $uni2Tratado . "\n";
        $exportFiles[] = [$_SERVER['DOCUMENT_ROOT']."/sqlitedb/".$uni2Tratado, $uni2Tratado];

        $listaTimes[] = [$row['ID'], $row['Nome'], $row['TresLetras'], $row['Estadio'], $escudoTratado, $row['Uni1Cor1'], $row['Uni1Cor2'], $row['Uni1Cor3'],$uni1Tratado, $row['Uni2Cor1'], $row['Uni2Cor2'], $row['Uni2Cor3'], $uni2Tratado,$row['MaxTorcedores'], $row['Fidelidade']];
    }

    if(isset($listaTimes)){
        foreach($listaTimes as $equipe){

            $queryInsercao = "INSERT INTO clube VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            $database->runQuery($queryInsercao,$equipe);

            $elenco = array();
            //buscar elenco e adicionar na query
            $stmt = $time->getElenco($equipe[0]);

            $elenco[] = $equipe[0];
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                $elenco[] = $row['ID'];
            }

            $total_jogadores = $time->getSizeElenco($equipe[0]);
            while ($total_jogadores < 23){
                $elenco[] = '0';
                $total_jogadores++;
            }

            $stmt = $time->getTecnico($equipe[0]);
            while($row  = $stmt->fetch(PDO::FETCH_ASSOC)){
                $tecnico = $row['tecnico'];
            }
            

            $elenco[] = $tecnico;

            $queryInsercao = "INSERT INTO elenco VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            $database->runQuery($queryInsercao,$elenco);

            $escalacao = array();
            $escalacao[] = $equipe[0];
            //buscar escalacao e adicionar na query
            $stmt = $time->getEscalacao($equipe[0]);
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                $escalacao[] = $row['posicaoBase'];
                $escalacao[] = $row['jogador'];
            }
            $stmt = $time->getCapitao($equipe[0]);
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                $escalacao[] = $row['jogador'];
            }
            $stmt = $time->getPenaltis($equipe[0]);
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                $escalacao[] = $row['jogador'];
            }

            $queryInsercao = "INSERT INTO escalacao VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            $database->runQuery($queryInsercao,$escalacao);
        }
    } 

//fim foreach paises
}

// no caso de não ter árbitros
if($contagemArbitros == 0){
    $queryInsercao = "INSERT INTO `trioarbitragem` (ID,Arbitro,Auxiliar1,Auxiliar2,Estilo) VALUES (1,'Padrão','Padrão','Padrão',3)";
    $database->runQuery($queryInsercao);
}

//fwrite($myfile, $txt);
//fclose($myfile);

//criar zip
$zip_name = $_SESSION['username'].'.zip'; //the real path of your final zip file on your system
$zip = new ZipArchive;
$zip->open($zip_name, ZIPARCHIVE::CREATE);
foreach($exportFiles as $file)
{
    $zip->addFile($file[0],$file[1]);
    
}
$zip->close();

header('Content-type: application/zip');
header('Content-disposition: filename="' . $zip_name . '"');
header("Content-length: " . filesize($zip_name));
readfile($zip_name);
unlink($zip_name);
foreach($exportFiles as $file)
{
    unlink($file[0]);
    
}
exit();

?>