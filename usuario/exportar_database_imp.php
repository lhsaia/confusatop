<?php

session_start();
$userId = $_SESSION['user_id'];

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

    //tentativa de juntar as querys para aumentar performance
    $megaQueryPais = "BEGIN TRANSACTION; ";
    
    //buscar trios de arbitragem e adicionar na query (colocar padrão só se tiver 0)
    $stmt = $trioarbitragem->exportacao($idPais);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        $nomeArbitro = str_replace("'", "''", $row['nomeArbitro']);
        $nomeAuxiliarUm = str_replace("'", "''", $row['nomeAuxiliarUm']);
        $nomeAuxiliarDois = str_replace("'", "''", $row['nomeAuxiliarDois']);
        $megaQueryPais .= "INSERT INTO trioarbitragem VALUES ('{$row['id']}', '{$nomeArbitro}', '{$nomeAuxiliarUm}', '{$nomeAuxiliarDois}', '{$row['estilo']}'); ";
        $contagemArbitros++;
    }

    //buscar tecnico e adicionar na query
    $stmt = $tecnico->exportacao($idPais);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        $nomeTecnico = str_replace("'", "''", $row['Nome']);
        $megaQueryPais .= "INSERT INTO tecnico VALUES ('{$row['ID']}', '{$nomeTecnico}', '{$row['Idade']}', '{$row['Nivel']}', '{$row['Mentalidade']}', '{$row['Estilo']}'); ";
    }

    //buscar posicoes dos jogadores e adicionar na query
    $stmt = $jogador->exportacao($idPais);

    $listaConferencia = array();
    $listaConferenciaGoleiro = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        $megaQueryPais .= "INSERT INTO posicaojogador VALUES ('{$row['idJogador']}', '{$row['StringPosicoes'][0]}', '{$row['StringPosicoes'][1]}', '{$row['StringPosicoes'][2]}', '{$row['StringPosicoes'][3]}', '{$row['StringPosicoes'][4]}', '{$row['StringPosicoes'][5]}', '{$row['StringPosicoes'][6]}', '{$row['StringPosicoes'][7]}', '{$row['StringPosicoes'][8]}', '{$row['StringPosicoes'][9]}', '{$row['StringPosicoes'][10]}', '{$row['StringPosicoes'][11]}', '{$row['StringPosicoes'][12]}', '{$row['StringPosicoes'][13]}', '{$row['StringPosicoes'][14]}'); ";

        $nomeJogador = str_replace("'", "''", $row['nomeJogador']);
        $megaQueryPais .= "INSERT INTO jogador VALUES ('{$row['idJogador']}', '{$nomeJogador}', '{$row['Idade']}', '{$row['Nivel']}', '0' , '0', '{$row['Mentalidade']}', '{$row['CobradorFalta']}'); ";

        $testeNacionalidade = ($row['Nacionalidade'] != null ? $row['Nacionalidade'] : '-');
        $megaQueryPais .= "INSERT INTO nacionalidades VALUES ('{$row['idJogador']}', '{$testeNacionalidade}'); ";

        if($row['StringPosicoes'][0] == 1){
            $megaQueryPais .= "INSERT INTO atributosgoleiro VALUES ('{$row['idJogador']}', '{$row['Reflexos']}', '{$row['Seguranca']}', '{$row['Saidas']}', '{$row['JogoAereo']}', '{$row['Lancamentos']}', '{$row['DefesaPenaltis']}', '{$row['Determinacao']}', '{$row['DeterminacaoOriginal']}'); ";

            $somaZero = abs(($row['Nivel'] * 0.55) - ($row['somaAtributos']));
            if($somaZero > 0.5){
                $megaQueryPais .= "INSERT INTO jogadorpendente VALUES ('{$row['idJogador']}'); ";
            }

        } else {
            $megaQueryPais .= "INSERT INTO atributosjogador VALUES ('{$row['idJogador']}', '{$row['Marcacao']}', '{$row['Desarme']}', '{$row['VisaoJogo']}', '{$row['Movimentacao']}', '{$row['Cruzamentos']}', '{$row['Cabeceamento']}', '{$row['Tecnica']}', '{$row['ControleBola']}', '{$row['Finalizacao']}', '{$row['FaroGol']}', '{$row['Velocidade']}', '{$row['Forca']}', '{$row['Determinacao']}', '{$row['DeterminacaoOriginal']}'); ";
            
            $somaZero = abs(($row['Nivel'] * 0.7) - ($row['somaAtributos']));
            if($somaZero > 0.5){
                $megaQueryPais .= "INSERT INTO jogadorpendente VALUES ('{$row['idJogador']}'); ";
            }
        }
         
    }

    //buscar estadio e adicionar na query
    $stmt = $estadio->exportacao($idPais);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        $nomeEstadio = str_replace("'", "''", $row['Nome']);
        $megaQueryPais .= "INSERT INTO estadio VALUES ('{$row['ID']}', '{$nomeEstadio}', '{$row['Capacidade']}', '{$row['Clima']}', '{$row['Altitude']}', '{$row['Caldeirao']}'); ";

    }

    //buscar climas e adicionar na query
    $stmt = $novoClima->exportacao($idPais);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        $nomeClima = str_replace("'", "''", $row['nomeClima']);
        $megaQueryPais .= "INSERT INTO clima VALUES ('{$row['idClima']}', '{$nomeClima}', '{$row['TempVerao']}', '{$row['EstiloVerao']}', '{$row['TempOutono']}', '{$row['EstiloOutono']}', '{$row['TempInverno']}', '{$row['EstiloInverno']}', '{$row['TempPrimavera']}', '{$row['EstiloPrimavera']}', '{$row['Hemisferio']}'); ";
       
    }

    //buscar clubes e adicionar na query
    $stmt = $time->exportacao($idPais);
    
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

        $nomeExportado = str_replace("'", "''", $nomeExportado);

        $megaQueryPais .= "INSERT INTO clube VALUES ('{$row['ID']}', '{$nomeExportado}', '{$row['TresLetras']}', '{$row['Estadio']}', '{$escudoTratado}', '{$row['Uni1Cor1']}', '{$row['Uni1Cor2']}', '{$row['Uni1Cor3']}', '{$uni1Tratado}', '{$row['Uni2Cor1']}', '{$row['Uni2Cor2']}', '{$row['Uni2Cor3']}', '{$uni2Tratado}', '{$row['MaxTorcedores']}', '{$row['Fidelidade']}'); ";

        $elenco = array();
        $newStmt = $time->getElenco($row['ID']);
        $elenco[] = $row['ID'];
        while($newRow = $newStmt->fetch(PDO::FETCH_ASSOC)){
            $elenco[] = $newRow['ID'];
        }
        $total_jogadores = $time->getSizeElenco($row['ID']);
        while ($total_jogadores < 23){
            $elenco[] = '0';
            $total_jogadores++;
        }
        $tecStmt = $time->getTecnico($row['ID']);
        while($tecRow  = $tecStmt->fetch(PDO::FETCH_ASSOC)){
            $elenco[] = $tecRow['tecnico'];
        }

        $megaQueryPais .= "INSERT INTO elenco VALUES ('{$elenco[0]}', '{$elenco[1]}', '{$elenco[2]}', '{$elenco[3]}', '{$elenco[4]}', '$elenco[5]}', '{$elenco[6]}', '{$elenco[7]}', '{$elenco[8]}', '{$elenco[9]}', '{$elenco[10]}', '{$elenco[11]}', '{$elenco[12]}', '{$elenco[13]}', '{$elenco[14]}', '{$elenco[15]}', '{$elenco[16]}', '{$elenco[17]}', '{$elenco[18]}', '{$elenco[19]}', '{$elenco[20]}', '{$elenco[21]}', '{$elenco[22]}', '{$elenco[23]}', '{$elenco[24]}'); ";

        $escalacao = array();
        $escalacao[] = $row['ID'];
        $escStmt = $time->getEscalacao($row['ID']);
        while($escRow = $escStmt->fetch(PDO::FETCH_ASSOC)){
            $escalacao[] = $escRow['posicaoBase'];
            $escalacao[] = $escRow['jogador'];
        }
        $capStmt = $time->getCapitao($row['ID']);
        while($capRow = $capStmt->fetch(PDO::FETCH_ASSOC)){
            $escalacao[] = $capRow['jogador'];
        }
        $penStmt = $time->getPenaltis($row['ID']);
        while($penRow = $penStmt->fetch(PDO::FETCH_ASSOC)){
            $escalacao[] = $penRow['jogador'];
        }

        $megaQueryPais .= "INSERT INTO escalacao VALUES ('{$escalacao[0]}', '{$escalacao[1]}', '{$escalacao[2]}', '{$escalacao[3]}', '{$escalacao[4]}', '{$escalacao[5]}', '{$escalacao[6]}', '{$escalacao[7]}', '{$escalacao[8]}', '{$escalacao[9]}', '{$escalacao[10]}', '{$escalacao[11]}', '{$escalacao[12]}', '{$escalacao[13]}', '{$escalacao[14]}', '{$escalacao[15]}', '{$escalacao[16]}', '{$escalacao[17]}', '{$escalacao[18]}', '{$escalacao[19]}', '{$escalacao[20]}', '{$escalacao[21]}', '{$escalacao[22]}', '{$escalacao[23]}', '{$escalacao[24]}', '{$escalacao[25]}', '{$escalacao[26]}'); ";

    }

    $megaQueryPais .= "COMMIT; ";



    //echo "<pre>" . var_export($megaQueryPais) . "</pre>";

    //echo $megaQueryPais;

    $database->directRun($megaQueryPais);

//fim foreach paises
}

    //$megaQueryGeral = "BEGIN TRANSACION; ";

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

$usuario->atualizarDownload($userId);

header('Content-type: application/zip');
header('Content-disposition: filename="' . $zip_name . '"');
header("Content-length: " . filesize($zip_name));
readfile($zip_name);
unlink($zip_name);
foreach($exportFiles as $file)
{
    unlink($file[0]);
}