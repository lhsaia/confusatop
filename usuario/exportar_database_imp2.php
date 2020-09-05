<?php

session_start();
$userId = $_SESSION['user_id'];

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
require("/home/lhsaia/confusa.top/lib/functions.php");

//conexão para obter dados do MySQL
$mainDatabase = new Database();
$db = $mainDatabase->getConnection();
$pais = new Pais($db);
$usuario = new Usuario($db);
$parametro = new Parametro($db);

$exportFiles = array();
$newFiles = array();

$listaPaises = explode("," , $_GET['countries']);
$listaLigas = explode(",", $_GET['leagues']);
$opcao = $_GET['option'];



//determinar paises do usuario e criar um array
$stmtPais = $pais->read($userId);
$listaPaises = array();
while ($row_pais = $stmtPais->fetch(PDO::FETCH_ASSOC)){
    extract($row_pais);
   $listaPaises[$id] = $nome;

}

//remove all user files and create directories only if needed
function delTree($dir) {
    $files = array_diff(scandir($dir), array('.','..'));
     foreach ($files as $file) {
       (is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file");
     }
   }
   

if (!is_dir('../sqlitedb/'. $userId)) {
    // dir doesn't exist, make it
    mkdir('../sqlitedb/'. $userId);
}

//inicio foreach paises
foreach($listaPaises as $idPais => $nomePais){

    $contagemArbitros = 0;
    $contagemParametros = 0;
    $contagemOpcoes = 0;

    if(!is_dir('../sqlitedb/'.$userId.'/'.$nomePais)){
        mkdir('../sqlitedb/'. $userId .'/'.$nomePais );
        mkdir('../sqlitedb/'. $userId .'/'.$nomePais. "/Escudos" );
        mkdir('../sqlitedb/'. $userId .'/'.$nomePais. "/Uniformes" );
        mkdir('../sqlitedb/'. $userId .'/'.$nomePais. "/data" );
    } else {
        delTree('../sqlitedb/'.$userId.'/'.$nomePais);
    }

    $exportFiles[] = ["/home/lhsaia/confusa.top/sqlitedb/".$userId."/".$nomePais."/data/database.db3",$nomePais."/data/database.db3"];

    $dir = new DirectoryIterator("../images/bandeiras");
foreach ($dir as $fileinfo) {
    if (!$fileinfo->isDot()) {
        $flagName = $fileinfo->getFilename();
        if(strlen($flagName) > 6 && $fileinfo->getMTime() > 1543817685){
            $preparedFlagname = $nomePais ."/data/paises/". $flagName;
            $newFiles[] = ["/home/lhsaia/confusa.top/images/bandeiras/" . $flagName, $preparedFlagname];
        }

    }
}

    $database = new SQLiteDatabase();
    $database->fileName = "../sqlitedb/".$userId."/".$nomePais."/data/database.db3";
    $database->getConnection();
    $database->prepareTables();
    $database->initialMainValues();

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
        $megaQueryPais .= "INSERT OR IGNORE INTO tecnico VALUES ('{$row['ID']}', '{$nomeTecnico}', '{$row['Idade']}', '{$row['Nivel']}', '{$row['Mentalidade']}', '{$row['Estilo']}'); ";
    }

    //buscar posicoes dos jogadores e adicionar na query
    $stmt = $jogador->exportacao($idPais);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        $megaQueryPais .= "INSERT OR IGNORE INTO posicaojogador VALUES ('{$row['idJogador']}', '{$row['StringPosicoes'][0]}', '{$row['StringPosicoes'][1]}', '{$row['StringPosicoes'][2]}', '{$row['StringPosicoes'][3]}', '{$row['StringPosicoes'][4]}', '{$row['StringPosicoes'][5]}', '{$row['StringPosicoes'][6]}', '{$row['StringPosicoes'][7]}', '{$row['StringPosicoes'][8]}', '{$row['StringPosicoes'][9]}', '{$row['StringPosicoes'][10]}', '{$row['StringPosicoes'][11]}', '{$row['StringPosicoes'][12]}', '{$row['StringPosicoes'][13]}', '{$row['StringPosicoes'][14]}'); ";

        $nomeJogador = str_replace("'", "''", $row['nomeJogador']);
        $megaQueryPais .= "INSERT OR IGNORE INTO jogador VALUES ('{$row['idJogador']}', '{$nomeJogador}', '{$row['Idade']}', '{$row['Nivel']}', '0' , '0', '{$row['Mentalidade']}', '{$row['CobradorFalta']}'); ";

        $testeNacionalidade = ($row['Nacionalidade'] != null ? $row['Nacionalidade'] : '-');
        $megaQueryPais .= "INSERT OR IGNORE INTO nacionalidades VALUES ('{$row['idJogador']}', '{$testeNacionalidade}'); ";

        if($row['StringPosicoes'][0] == 1){
            
            
        // inserir atributos recalculados goleiro
        
        $atributosGoleiro = adjustAttributes(true, $row['Nivel'], 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, $row['Reflexos'], $row['Seguranca'],  $row['Saidas'],  $row['JogoAereo'],  $row['Lancamentos'],  $row['DefesaPenaltis']);
        
        // fim atributos recalculados goleiro
        
            $megaQueryPais .= "INSERT OR IGNORE INTO atributosgoleiro VALUES ('{$row['idJogador']}', '{$atributosGoleiro['reflexos']}', '{$atributosGoleiro['seguranca']}', '{$atributosGoleiro['saidas']}', '{$atributosGoleiro['jogoAereo']}', '{$atributosGoleiro['lancamentos']}', '{$atributosGoleiro['defesaPenaltis']}', '1', '1'); ";

            $somaZero = abs(($row['Nivel'] * 0.50) - array_sum($atributosGoleiro));
            if($somaZero > 0.5){
                $megaQueryPais .= "INSERT OR IGNORE INTO jogadorpendente VALUES ('{$row['idJogador']}'); ";
            }

        } else {
            
            //inserir atributos recalculados
            
            $atributosJogador = adjustAttributes(false, $row['Nivel'], $row['Marcacao'], $row['Desarme'], $row['VisaoJogo'], $row['Movimentacao'], $row['Cruzamentos'], $row['Cabeceamento'], $row['Tecnica'], $row['ControleBola'], $row['Finalizacao'], $row['FaroGol'], $row['Velocidade'], $row['Forca'], 0, 0, 0, 0, 0, 0);
            
            
            //fim atributos recalculados
            
            $megaQueryPais .= "INSERT OR IGNORE INTO atributosjogador VALUES ('{$row['idJogador']}', '{$atributosJogador['marcacao']}', '{$atributosJogador['desarme']}', '{$atributosJogador['visaoJogo']}', '{$atributosJogador['movimentacao']}', '{$atributosJogador['cruzamentos']}', '{$atributosJogador['cabeceamento']}', '{$atributosJogador['tecnica']}', '{$atributosJogador['controleBola']}', '{$atributosJogador['finalizacao']}', '{$atributosJogador['faroGol']}', '{$atributosJogador['velocidade']}', '{$atributosJogador['forca']}', '1', '1'); ";
            


            $somaZero = abs(($row['Nivel'] * 0.65) - array_sum($atributosJogador));
            if($somaZero > 0.5){
                $megaQueryPais .= "INSERT OR IGNORE INTO jogadorpendente VALUES ('{$row['idJogador']}'); ";
                

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

        //file_put_contents("/home/lhsaia/confusa.top/sqlitedb/".$userId."/".$nomePais."/".$escudoTratado, base64_decode($escudoArray[0]));
        //file_put_contents("/home/lhsaia/confusa.top/sqlitedb/".$userId."/".$nomePais."/".$uni1Tratado, base64_decode($uni1Array[0]));
        //file_put_contents("/home/lhsaia/confusa.top/sqlitedb/".$userId."/".$nomePais."/".$uni2Tratado, base64_decode($uni2Array[0]));

        copy('/home/lhsaia/confusa.top/images/escudos/'.$row['Escudo'] , '/home/lhsaia/confusa.top/sqlitedb/'.$userId.'/'.$nomePais.'/'. $escudoTratado);
        copy('/home/lhsaia/confusa.top/images/uniformes/'.$row['Uniforme1'] , '/home/lhsaia/confusa.top/sqlitedb/'.$userId.'/'.$nomePais.'/'. $uni1Tratado);
        copy('/home/lhsaia/confusa.top/images/uniformes/'.$row['Uniforme2'] , '/home/lhsaia/confusa.top/sqlitedb/'.$userId.'/'.$nomePais.'/'. $uni2Tratado);

        //$txt .= $escudoTratado . "\n";
        $exportFiles[] = ["/home/lhsaia/confusa.top/sqlitedb/".$userId."/".$nomePais."/".$escudoTratado, $nomePais."/".$escudoTratado];
        //$txt .= $uni1Tratado . "\n";
        $exportFiles[] = ["/home/lhsaia/confusa.top/sqlitedb/".$userId."/".$nomePais."/".$uni1Tratado, $nomePais."/".$uni1Tratado];
        //$txt .= $uni2Tratado . "\n";
        $exportFiles[] = ["/home/lhsaia/confusa.top/sqlitedb/".$userId."/".$nomePais."/".$uni2Tratado, $nomePais."/".$uni2Tratado];

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

        $megaQueryPais .= "INSERT INTO elenco VALUES ('{$elenco[0]}', '{$elenco[1]}', '{$elenco[2]}', '{$elenco[3]}', '{$elenco[4]}', '{$elenco[5]}', '{$elenco[6]}', '{$elenco[7]}', '{$elenco[8]}', '{$elenco[9]}', '{$elenco[10]}', '{$elenco[11]}', '{$elenco[12]}', '{$elenco[13]}', '{$elenco[14]}', '{$elenco[15]}', '{$elenco[16]}', '{$elenco[17]}', '{$elenco[18]}', '{$elenco[19]}', '{$elenco[20]}', '{$elenco[21]}', '{$elenco[22]}', '{$elenco[23]}', '{$elenco[24]}'); ";

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

    //buscar parametros e adicionar na query (padrão se tiver 0)
    $stmt = $parametro->exportacao($idPais);
    $listaPaisPadrao = array();
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        $megaQueryPais .= "INSERT INTO parametros VALUES ('{$row['ID']}', '{$row['Nome']}', '{$row['Gols']}', '{$row['Faltas']}', '{$row['Impedimentos']}', '{$row['Cartoes']}', '{$row['Chao']}', '{$row['Alto']}', '{$row['Selecionado']}'); ";
        $megaQueryPais .= "INSERT INTO paispadrao VALUES ('{$row['ID']}', '{$row['PaisPadrao']}', '{$row['ExibirBandeiras']}'); ";
        $contagemParametros++;
    }

     //buscar opções se houver
     $stmt = $parametro->coletarOpcoes($userId);
     if($stmt->rowCount() != 0){
         $result = $stmt->fetch(PDO::FETCH_ASSOC);
         $megaQueryPais .= "INSERT INTO `opcoes` VALUES ('mostrarSumula',{$result['mostrarSumula']},0);
         INSERT INTO `opcoes` VALUES ('limitarLesoes',{$result['limitarLesoes']},0);
         INSERT INTO `opcoes` VALUES ('tempoLimite',{$result['porTempo']},0);
         INSERT INTO `opcoes` VALUES ('dataLimite',NULL,{$result['porData']});
         INSERT INTO `opcoes` VALUES ('VAR',{$result['videoAr']},0); ";
         $contagemOpcoes++;
     }

     // no caso de não ter árbitros
if($contagemArbitros == 0){
    $megaQueryPais .= "INSERT INTO `trioarbitragem` (ID,Arbitro,Auxiliar1,Auxiliar2,Estilo) VALUES (1,'Padrão','Padrão','Padrão',3); ";
}

//no caso de não ter parâmetros
if($contagemParametros == 0){
    $megaQueryPais .= "INSERT INTO `parametros` VALUES (1,'Padrão',10,10,5,5,1.0,1.0,1); ";
    $megaQueryPais .= "INSERT INTO `paispadrao` VALUES (1,'-',1); ";
}

//no caso de não ter opções
if($contagemOpcoes == 0){
    $megaQueryPais .= "INSERT INTO `opcoes` VALUES ('mostrarSumula',1,0);
    INSERT INTO `opcoes` VALUES ('limitarLesoes',0,0);
    INSERT INTO `opcoes` VALUES ('tempoLimite',180,0);
    INSERT INTO `opcoes` VALUES ('dataLimite',0,0);
    INSERT INTO `opcoes` VALUES ('VAR',0,0); ";
}

    $megaQueryPais .= "COMMIT; ";

    //testes
    // echo '<pre>' , var_dump($megaQueryPais) , '</pre>';
    // die();

    $database->directRun($megaQueryPais);

//fim foreach paises
}

//criar zip e fazer exportação
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
