<?php

session_start();
$userId = $_SESSION['user_id'];

//$userId = $argv[1];

//ini_set("default_socket_timeout", 6000);

if (!is_dir('../sqlitedb/'. $userId)) {
    // dir doesn't exist, make it
    mkdir('../sqlitedb/'. $userId);
    mkdir('../sqlitedb/'. $userId . "/Escudos" );
    mkdir('../sqlitedb/'. $userId . "/Uniformes" );
    mkdir('../sqlitedb/'. $userId . "/data" );
  }

if(file_exists("../sqlitedb/". $userId."/data/database.db3")){
    unlink("../sqlitedb/". $userId."/data/database.db3");
}

include_once("/home/lhsaia/confusa.top/config/sqliteDatabase.php");
include_once("/home/lhsaia/confusa.top/config/database.php");
include_once("/home/lhsaia/confusa.top/objetos/paises.php");
include_once("/home/lhsaia/confusa.top/objetos/jogador.php");
include_once("/home/lhsaia/confusa.top/objetos/time.php");
include_once("/home/lhsaia/confusa.top/objetos/arbitros.php");
include_once("/home/lhsaia/confusa.top/objetos/tecnico.php");
include_once("/home/lhsaia/confusa.top/objetos/estadio.php");
include_once("/home/lhsaia/confusa.top/objetos/usuarios.php");
include_once("/home/lhsaia/confusa.top/objetos/parametros.php");
include_once("/home/lhsaia/confusa.top/objetos/clima.php");

$database = new SQLiteDatabase();
$database->fileName = "../sqlitedb/".$userId."/data/database.db3";
$database->getConnection();
$database->prepareTables();
$database->initialMainValues();

$mainDatabase = new Database();
$db = $mainDatabase->getConnection();


$pais = new Pais($db);
$usuario = new Usuario($db);
$parametro = new Parametro($db);



$exportFiles = array();
$newFiles = array();

$dir = new DirectoryIterator("../images/bandeiras");
foreach ($dir as $fileinfo) {
    if (!$fileinfo->isDot()) {
        $flagName = $fileinfo->getFilename();
        if(strlen($flagName) > 6 && $fileinfo->getMTime() > 1543817685){
            $preparedFlagname = "data/paises/". $flagName;
            $newFiles[] = ["/home/lhsaia/confusa.top/images/bandeiras/" . $flagName, $preparedFlagname];
        }
        
    }
}



$exportFiles[] = ["/home/lhsaia/confusa.top/sqlitedb/".$userId."/data/database.db3","data/database.db3"];

//determinar paises do usuario e criar um array
$stmtPais = $pais->read($userId);
$listaPaises = array();
while ($row_pais = $stmtPais->fetch(PDO::FETCH_ASSOC)){
    extract($row_pais);
    $listaPaises[] = $id;
}

$queryInsercao = "";
$contagemArbitros = 0;
$contagemParametros = 0;
$contagemOpcoes = 0;

//inicio foreach paises
foreach($listaPaises as $idPais){

    $time = new Time($db);
    $jogador = new Jogador($db);
    $trioarbitragem = new TrioArbitragem($db);
    $tecnico = new Tecnico($db);
    $estadio = new Estadio($db);
    $novoClima = new Clima($db);
    

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
    $listaConferencia = array();
    $listaConferenciaGoleiro = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        $listaPosicoes[] = array_merge([$row['idJogador']],str_split($row['StringPosicoes']));
        $listaJogadores[] = [$row['idJogador'], $row['nomeJogador'], $row['Idade'], $row['Nivel'], '0' , '0', $row['Mentalidade'], $row['CobradorFalta']];
        $listaNacionalidades[] = [$row['idJogador'], ($row['Nacionalidade'] != null ? $row['Nacionalidade'] : '-') ];
        if($row['StringPosicoes'][0] == 1){
            $listaAtributosGoleiro[] = [$row['idJogador'],$row['Reflexos'], $row['Seguranca'], $row['Saidas'], $row['JogoAereo'], $row['Lancamentos'], $row['DefesaPenaltis'], $row['Determinacao'], $row['DeterminacaoOriginal']];
            $listaConferenciaGoleiro[] = [$row['idJogador'], $row['Nivel'], $row['somaAtributos']];  
        } else {
            $listaAtributosJogador[] = [$row['idJogador'],$row['Marcacao'], $row['Desarme'], $row['VisaoJogo'], $row['Movimentacao'], $row['Cruzamentos'], $row['Cabeceamento'], $row['Tecnica'], $row['ControleBola'], $row['Finalizacao'], $row['FaroGol'], $row['Velocidade'], $row['Forca'], $row['Determinacao'], $row['DeterminacaoOriginal']];
            $listaConferencia[] = [$row['idJogador'], $row['Nivel'], $row['somaAtributos']];  
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

    //verificação da soma de atributos
    if(isset($listaConferencia)){
        foreach($listaConferencia as $index => $conferencia){

            $somaZero = abs(($conferencia[1] * 0.7) - $conferencia[2]);

            if($somaZero > 0.5){
                $queryInsercao = "INSERT INTO jogadorpendente VALUES (?)";
                $database->runQuery($queryInsercao,$conferencia[0]);
            }

        
        }
    } 

        //verificação da soma de atributos
        if(isset($listaConferenciaGoleiro)){
            foreach($listaConferenciaGoleiro as $index => $conferencia){
    
                $somaZero = abs(($conferencia[1] * 0.55) - $conferencia[2]);
    
                if($somaZero > 0.5){
                    $queryInsercao = "INSERT INTO jogadorpendente VALUES (?)";
                    $database->runQuery($queryInsercao,$conferencia[0]);
                }
    
            
            }
        } 


    //buscar estadio e adicionar na query
    $stmt = $estadio->exportacao($idPais);
    $listaEstadios = array();
    $listaClimas = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        $listaEstadios[] = [$row['ID'], $row['Nome'], $row['Capacidade'], $row['Clima'], $row['Altitude'], $row['Caldeirao']];

    }

    if(isset($listaEstadios)){
        foreach($listaEstadios as $index => $estadio){

            $queryInsercao = "INSERT INTO estadio VALUES (?,?,?,?,?,?)";
            $database->runQuery($queryInsercao,$estadio);
        
        }
    } 


    //buscar climas e adicionar na query
    $stmt = $novoClima->exportacao($idPais);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
       
        $listaClimas[] = [$row['idClima'], $row['nomeClima'], $row['TempVerao'], $row['EstiloVerao'], $row['TempOutono'], $row['EstiloOutono'],$row['TempInverno'], $row['EstiloInverno'],$row['TempPrimavera'], $row['EstiloPrimavera'],$row['Hemisferio']];
       
    }
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

        file_put_contents("/home/lhsaia/confusa.top/sqlitedb/".$userId."/".$escudoTratado, base64_decode($escudoArray[0]));
        file_put_contents("/home/lhsaia/confusa.top/sqlitedb/".$userId."/".$uni1Tratado, base64_decode($uni1Array[0]));
        file_put_contents("/home/lhsaia/confusa.top/sqlitedb/".$userId."/".$uni2Tratado, base64_decode($uni2Array[0]));

        //$txt .= $escudoTratado . "\n";
        $exportFiles[] = ["/home/lhsaia/confusa.top/sqlitedb/".$userId."/".$escudoTratado, $escudoTratado];
        //$txt .= $uni1Tratado . "\n";
        $exportFiles[] = ["/home/lhsaia/confusa.top/sqlitedb/".$userId."/".$uni1Tratado, $uni1Tratado];
        //$txt .= $uni2Tratado . "\n";
        $exportFiles[] = ["/home/lhsaia/confusa.top/sqlitedb/".$userId."/".$uni2Tratado, $uni2Tratado];

        if($time->verificarHomonimo($row['Nome'],$idPais) && $row['Sexo'] == '1'){
            $nomeExportado = $row['Nome'] . " (F)";
        } else {
            $nomeExportado = $row['Nome'];
        }

        $listaTimes[] = [$row['ID'], $nomeExportado, $row['TresLetras'], $row['Estadio'], $escudoTratado, $row['Uni1Cor1'], $row['Uni1Cor2'], $row['Uni1Cor3'],$uni1Tratado, $row['Uni2Cor1'], $row['Uni2Cor2'], $row['Uni2Cor3'], $uni2Tratado,$row['MaxTorcedores'], $row['Fidelidade']];
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

    //buscar parametros e adicionar na query (padrão se tiver 0)
    $stmt = $parametro->exportacao($userId);
    $listaParametros = array();
    $listaPaisPadrao = array();
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        $listaParametros[] = [$row['ID'], $row['Nome'], $row['Gols'], $row['Faltas'], $row['Impedimentos'],$row['Cartoes'],$row['Chao'],$row['Alto'],$row['Selecionado']];
        $listaPaisPadrao[] = [$row['ID'],$row['PaisPadrao'],$row['ExibirBandeiras']];
    }

    

    if(isset($listaParametros)){
        foreach($listaParametros as $parametroInserir){

            $queryInsercao = "INSERT INTO parametros VALUES (?,?,?,?,?,?,?,?,?)";
            $database->runQuery($queryInsercao,$parametroInserir);
            $contagemParametros++;
        
        }

        foreach($listaPaisPadrao as $paisPadrao){

            $queryInsercao = "INSERT INTO paispadrao VALUES (?,?,?)";
            $database->runQuery($queryInsercao,$paisPadrao);
        }
    }         

    //buscar opções se houver
    $stmt = $parametro->coletarOpcoes($userId);
    if($stmt->rowCount() != 0){
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $queryInsercaoDireta = "INSERT INTO `opcoes` VALUES ('mostrarSumula',{$result['mostrarSumula']},0);
        INSERT INTO `opcoes` VALUES ('limitarLesoes',{$result['limitarLesoes']},0);
        INSERT INTO `opcoes` VALUES ('tempoLimite',{$result['porTempo']},0);
        INSERT INTO `opcoes` VALUES ('dataLimite',NULL,{$result['porData']});
        INSERT INTO `opcoes` VALUES ('VAR',{$result['videoAr']},0);";
        $database->directRun($queryInsercaoDireta);
        $contagemOpcoes++;
    }

// no caso de não ter árbitros
if($contagemArbitros == 0){
    $queryInsercao = "INSERT INTO `trioarbitragem` (ID,Arbitro,Auxiliar1,Auxiliar2,Estilo) VALUES (1,'Padrão','Padrão','Padrão',3)";
    $database->runQuery($queryInsercao);
}

//no caso de não ter parâmetros
if($contagemParametros == 0){
    $queryInsercao = "INSERT INTO `parametros` VALUES (1,'Padrão',10,10,5,5,1.0,1.0,1)";
    $database->runQuery($queryInsercao);

    $queryInsercao = "INSERT INTO `paispadrao` VALUES (1,'-',1)";
    $database->runQuery($queryInsercao);   
} 

//no caso de não ter opções
if($contagemOpcoes == 0){
    $queryInsercao = "INSERT INTO `opcoes` VALUES ('mostrarSumula',1,0);
    INSERT INTO `opcoes` VALUES ('limitarLesoes',0,0);
    INSERT INTO `opcoes` VALUES ('tempoLimite',180,0);
    INSERT INTO `opcoes` VALUES ('dataLimite',0,0);
    INSERT INTO `opcoes` VALUES ('VAR',0,0);";
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
foreach($newFiles as $file){
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

$usuario->atualizarDownload($userId);
//exi