<?php

session_start();
ini_set( 'display_errors', true );
error_reporting( E_ALL );

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
include_once("/home/lhsaia/confusa.top/objetos/export_torneios.php");

//conexão para obter dados do MySQL
$mainDatabase = new Database();
$db = $mainDatabase->getConnection();
$pais = new Pais($db);
$usuario = new Usuario($db);
$parametro = new Parametro($db);
$torneio = new ExportTorneio($db);
$trioarbitragem = new TrioArbitragem($db);

$error_msg = "";

$exportFiles = array();

$codigo_torneio = $_POST['codigo_campeonato'];
$federacao_torneio = $_POST['federacao_campeonato'];
$sede_torneio = $_POST['sede_campeonato'];
$listaTimesString = $_POST['array_times'];

$listaTimes = explode(',', $listaTimesString);

//die(json_encode([ 'success'=> true, 'info' => gettype($listaTimes)]));

$nomeCompeticao = $torneio->nome($codigo_torneio);

if(!is_dir('/home/lhsaia/confusa.top/sqlitedb/'.$userId.'/'.$nomeCompeticao)){
    mkdir('/home/lhsaia/confusa.top/sqlitedb/'. $userId .'/'.$nomeCompeticao );
    mkdir('/home/lhsaia/confusa.top/sqlitedb/'. $userId .'/'.$nomeCompeticao. "/Escudos" );
    mkdir('/home/lhsaia/confusa.top/sqlitedb/'. $userId .'/'.$nomeCompeticao. "/Uniformes" );
    mkdir('/home/lhsaia/confusa.top/sqlitedb/'. $userId .'/'.$nomeCompeticao. "/data" );
} else {
    delTree('/home/lhsaia/confusa.top/sqlitedb/'.$userId.'/'.$nomeCompeticao);
}

$exportFiles[] = ["/home/lhsaia/confusa.top/sqlitedb/".$userId."/".$nomeCompeticao."/data/database.db3","Hexacolor - " . $nomeCompeticao . "/data/database.db3"];


$database = new SQLiteDatabase();
$database->fileName = "/home/lhsaia/confusa.top/sqlitedb/".$userId."/".$nomeCompeticao."/data/database.db3";
$database->getConnection();
$database->prepareTables();
$database->initialMainValues();
//$torneio->salvar($codigo_torneio);

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

//tentativa de juntar as querys para aumentar performance
$megaQuery = "BEGIN TRANSACTION; ";

//inicio foreach paises
foreach($listaTimes as $idTime){

    $time = new Time($db);
    $jogador = new Jogador($db);
    $tecnico = new Tecnico($db);
    $estadio = new Estadio($db);
    $novoClima = new Clima($db);

    //buscar tecnico e adicionar na query
    $stmt = $tecnico->exportacao(null, $idTime);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        $nomeTecnico = str_replace("'", "''", $row['Nome']);
        $megaQuery .= "INSERT OR IGNORE INTO tecnico VALUES ('{$row['ID']}', '{$nomeTecnico}', '{$row['Idade']}', '{$row['Nivel']}', '{$row['Mentalidade']}', '{$row['Estilo']}'); ";
    }

    //buscar posicoes dos jogadores e adicionar na query
    $stmt = $jogador->exportacao(null,$idTime);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        $megaQuery .= "INSERT OR IGNORE INTO posicaojogador VALUES ('{$row['idJogador']}', '{$row['StringPosicoes'][0]}', '{$row['StringPosicoes'][1]}', '{$row['StringPosicoes'][2]}', '{$row['StringPosicoes'][3]}', '{$row['StringPosicoes'][4]}', '{$row['StringPosicoes'][5]}', '{$row['StringPosicoes'][6]}', '{$row['StringPosicoes'][7]}', '{$row['StringPosicoes'][8]}', '{$row['StringPosicoes'][9]}', '{$row['StringPosicoes'][10]}', '{$row['StringPosicoes'][11]}', '{$row['StringPosicoes'][12]}', '{$row['StringPosicoes'][13]}', '{$row['StringPosicoes'][14]}'); ";

        $nomeJogador = str_replace("'", "''", $row['nomeJogador']);
        $megaQuery .= "INSERT OR IGNORE INTO jogador VALUES ('{$row['idJogador']}', '{$nomeJogador}', '{$row['Idade']}', '{$row['Nivel']}', '0' , '0', '{$row['Mentalidade']}', '{$row['CobradorFalta']}'); ";

        $testeNacionalidade = ($row['Nacionalidade'] != null ? $row['Nacionalidade'] : '-');
        $megaQuery .= "INSERT OR IGNORE INTO nacionalidades VALUES ('{$row['idJogador']}', '{$testeNacionalidade}'); ";

        if($row['StringPosicoes'][0] == 1){
            $megaQuery .= "INSERT OR IGNORE INTO atributosgoleiro VALUES ('{$row['idJogador']}', '{$row['Reflexos']}', '{$row['Seguranca']}', '{$row['Saidas']}', '{$row['JogoAereo']}', '{$row['Lancamentos']}', '{$row['DefesaPenaltis']}', '1', '1'); ";

            $somaZero = abs(($row['Nivel'] * 0.50) - ($row['somaAtributos']));
            if($somaZero > 0.5){
                $megaQuery .= "INSERT OR IGNORE INTO jogadorpendente VALUES ('{$row['idJogador']}'); ";
            }

        } else {
            $megaQuery .= "INSERT OR IGNORE INTO atributosjogador VALUES ('{$row['idJogador']}', '{$row['Marcacao']}', '{$row['Desarme']}', '{$row['VisaoJogo']}', '{$row['Movimentacao']}', '{$row['Cruzamentos']}', '{$row['Cabeceamento']}', '{$row['Tecnica']}', '{$row['ControleBola']}', '{$row['Finalizacao']}', '{$row['FaroGol']}', '{$row['Velocidade']}', '{$row['Forca']}', '1', '1'); ";

            $somaZero = abs(($row['Nivel'] * 0.65) - ($row['somaAtributos']));
            if($somaZero > 0.5){
                $megaQuery .= "INSERT OR IGNORE INTO jogadorpendente VALUES ('{$row['idJogador']}'); ";
            }
        }

    }

    //buscar estadio e adicionar na query
    $stmt = $estadio->exportacao(null, $idTime);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        $nomeEstadio = str_replace("'", "''", $row['Nome']);
        $megaQuery .= "INSERT or IGNORE INTO estadio VALUES ('{$row['ID']}', '{$nomeEstadio}', '{$row['Capacidade']}', '{$row['Clima']}', '{$row['Altitude']}', '{$row['Caldeirao']}'); ";

    }

    //buscar climas e adicionar na query
    $stmt = $novoClima->exportacao(null, $idTime);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        $nomeClima = str_replace("'", "''", $row['nomeClima']);
        $megaQuery .= "INSERT or IGNORE INTO clima VALUES ('{$row['idClima']}', '{$nomeClima}', '{$row['TempVerao']}', '{$row['EstiloVerao']}', '{$row['TempOutono']}', '{$row['EstiloOutono']}', '{$row['TempInverno']}', '{$row['EstiloInverno']}', '{$row['TempPrimavera']}', '{$row['EstiloPrimavera']}', '{$row['Hemisferio']}'); ";

    }

    //buscar clubes e adicionar na query
    $stmt = $time->exportacao(null, $idTime);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){

        //tratar uniforme e simbolo
        $escudoArray = explode(".",$row['Escudo']);
        $uni1Array = explode(".",$row['Uniforme1']);
        $uni2Array = explode(".",$row['Uniforme2']);
        $baseFileName = "team" . $row['ID'];
        $escudoTratado = "Escudos/" . $baseFileName . "." .$escudoArray[1];
        $uni1Tratado = "Uniformes/1-" . $baseFileName . "." .$uni1Array[1];
        $uni2Tratado = "Uniformes/2-" . $baseFileName . "." .$uni2Array[1];

        // correção para problema com &
        $escudo_corrigido = html_entity_decode($row['Escudo']);
        $uni1_corrigido = html_entity_decode($row['Uniforme1']);
        $uni2_corrigido = html_entity_decode($row['Uniforme2']);

        copy('/home/lhsaia/confusa.top/images/escudos/'.$escudo_corrigido , '/home/lhsaia/confusa.top/sqlitedb/'.$userId.'/'.$nomeCompeticao.'/'. $escudoTratado);
        copy('/home/lhsaia/confusa.top/images/uniformes/'.$uni1_corrigido , '/home/lhsaia/confusa.top/sqlitedb/'.$userId.'/'.$nomeCompeticao.'/'. $uni1Tratado);
        copy('/home/lhsaia/confusa.top/images/uniformes/'.$uni2_corrigido , '/home/lhsaia/confusa.top/sqlitedb/'.$userId.'/'.$nomeCompeticao.'/'. $uni2Tratado);

        //$txt .= $escudoTratado . "\n";
        $exportFiles[] = ["/home/lhsaia/confusa.top/sqlitedb/".$userId."/".$nomeCompeticao."/".$escudoTratado, "Hexacolor - " . $nomeCompeticao . "/" . $escudoTratado];
        //$txt .= $uni1Tratado . "\n";
        $exportFiles[] = ["/home/lhsaia/confusa.top/sqlitedb/".$userId."/".$nomeCompeticao."/".$uni1Tratado, "Hexacolor - " . $nomeCompeticao . "/" .$uni1Tratado];
        //$txt .= $uni2Tratado . "\n";
        $exportFiles[] = ["/home/lhsaia/confusa.top/sqlitedb/".$userId."/".$nomeCompeticao."/".$uni2Tratado,"Hexacolor - " . $nomeCompeticao . "/" . $uni2Tratado];

        $nomeExportado = str_replace("'", "''", $row['Nome']);

        $nomeExportado = html_entity_decode($nomeExportado);

        $megaQuery .= "INSERT INTO clube VALUES ('{$row['ID']}', '{$nomeExportado}', '{$row['TresLetras']}', '{$row['Estadio']}', '{$escudoTratado}', '{$row['Uni1Cor1']}', '{$row['Uni1Cor2']}', '{$row['Uni1Cor3']}', '{$uni1Tratado}', '{$row['Uni2Cor1']}', '{$row['Uni2Cor2']}', '{$row['Uni2Cor3']}', '{$uni2Tratado}', '{$row['MaxTorcedores']}', '{$row['Fidelidade']}'); ";

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

        $megaQuery .= "INSERT INTO elenco VALUES ('{$elenco[0]}', '{$elenco[1]}', '{$elenco[2]}', '{$elenco[3]}', '{$elenco[4]}', '{$elenco[5]}', '{$elenco[6]}', '{$elenco[7]}', '{$elenco[8]}', '{$elenco[9]}', '{$elenco[10]}', '{$elenco[11]}', '{$elenco[12]}', '{$elenco[13]}', '{$elenco[14]}', '{$elenco[15]}', '{$elenco[16]}', '{$elenco[17]}', '{$elenco[18]}', '{$elenco[19]}', '{$elenco[20]}', '{$elenco[21]}', '{$elenco[22]}', '{$elenco[23]}', '{$elenco[24]}'); ";

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

        $megaQuery .= "INSERT INTO escalacao VALUES ('{$escalacao[0]}', '{$escalacao[1]}', '{$escalacao[2]}', '{$escalacao[3]}', '{$escalacao[4]}', '{$escalacao[5]}', '{$escalacao[6]}', '{$escalacao[7]}', '{$escalacao[8]}', '{$escalacao[9]}', '{$escalacao[10]}', '{$escalacao[11]}', '{$escalacao[12]}', '{$escalacao[13]}', '{$escalacao[14]}', '{$escalacao[15]}', '{$escalacao[16]}', '{$escalacao[17]}', '{$escalacao[18]}', '{$escalacao[19]}', '{$escalacao[20]}', '{$escalacao[21]}', '{$escalacao[22]}', '{$escalacao[23]}', '{$escalacao[24]}', '{$escalacao[25]}', '{$escalacao[26]}'); ";

    }
    //testes
    // echo '<pre>' , var_dump($megaQuery) , '</pre>';
    // die();

//fim foreach paises
}

//buscar estadio e adicionar na query (sede)
if($sede_torneio > 0){
  $stmt = $estadio->exportacao($sede_torneio);

  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
      $nomeEstadio = str_replace("'", "''", $row['Nome']);
      $megaQuery .= "INSERT OR IGNORE INTO estadio VALUES ('{$row['ID']}', '{$nomeEstadio}', '{$row['Capacidade']}', '{$row['Clima']}', '{$row['Altitude']}', '{$row['Caldeirao']}'); ";

  }

  //buscar climas e adicionar na query
  $stmt = $novoClima->exportacao($sede_torneio);

  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
      $nomeClima = str_replace("'", "''", $row['nomeClima']);
      $megaQuery .= "INSERT OR IGNORE INTO clima VALUES ('{$row['idClima']}', '{$nomeClima}', '{$row['TempVerao']}', '{$row['EstiloVerao']}', '{$row['TempOutono']}', '{$row['EstiloOutono']}', '{$row['TempInverno']}', '{$row['EstiloInverno']}', '{$row['TempPrimavera']}', '{$row['EstiloPrimavera']}', '{$row['Hemisferio']}'); ";

  }

}

$megaQuery .= "INSERT INTO `parametros` VALUES (1,'Padrão',10,10,5,5,1.0,1.0,1); ";
$megaQuery .= "INSERT INTO `paispadrao` VALUES (1,'-',1); ";
$megaQuery .= "INSERT INTO `opcoes` VALUES ('mostrarSumula',1,0);
INSERT INTO `opcoes` VALUES ('limitarLesoes',0,0);
INSERT INTO `opcoes` VALUES ('tempoLimite',180,0);
INSERT INTO `opcoes` VALUES ('dataLimite',0,0);
INSERT INTO `opcoes` VALUES ('VAR',1,0); ";

//buscar trios de arbitragem e adicionar na query (colocar padrão só se tiver 0)
$stmt = $trioarbitragem->exportacao(null,$federacao_torneio);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
    $nomeArbitro = str_replace("'", "''", $row['nomeArbitro']);
    $nomeAuxiliarUm = str_replace("'", "''", $row['nomeAuxiliarUm']);
    $nomeAuxiliarDois = str_replace("'", "''", $row['nomeAuxiliarDois']);
    $megaQuery .= "INSERT INTO trioarbitragem VALUES ('{$row['id']}', '{$nomeArbitro}', '{$nomeAuxiliarUm}', '{$nomeAuxiliarDois}', '{$row['estilo']}'); ";
}

$megaQuery .= "COMMIT; ";
$database->directRun($megaQuery);

//outros arquivos do pacote

//criar zip e fazer exportação
$zip_name = $nomeCompeticao .mt_rand(1,1000) .'.zip'; //the real path of your final zip file on your system
if(file_exists($zip_name)){
    unlink($zip_name);
}

$zip = new ZipArchive;
$zip->open($zip_name, ZIPARCHIVE::CREATE);
$zip->addEmptyDir("Hexacolor - " . $nomeCompeticao);
$zip->addEmptyDir("Hexacolor - " . $nomeCompeticao . "/Exports");
$zip->addEmptyDir("Hexacolor - " . $nomeCompeticao . "/Imports");
$zip->addEmptyDir("Hexacolor - " . $nomeCompeticao . "/Partidas");

$exportFiles[] = ["/home/lhsaia/confusa.top/hexacolor_repo/HexacolorYMTv2.jar", "Hexacolor - " . $nomeCompeticao . "/HexacolorYMTv2.jar"];
$exportFiles[] = ["/home/lhsaia/confusa.top/hexacolor_repo/Leia-me.TXT", "Hexacolor - " . $nomeCompeticao . "/Leia-me.TXT"];


foreach($exportFiles as $file)
{
    $zip->addFile($file[0],$file[1]);

}

chdir('/home/lhsaia/confusa.top/hexacolor_repo/data');
foreach (glob("*") as $file) {
$zip->addFile($file, "Hexacolor - " . $nomeCompeticao . "/data/".$file);
}
chdir('/home/lhsaia/confusa.top/hexacolor_repo/lib');
foreach (glob("*") as $file) {
$zip->addFile($file, "Hexacolor - " . $nomeCompeticao . "/lib/".$file);
}
chdir('/home/lhsaia/confusa.top/hexacolor_repo/ImportacaoRapida');
foreach (glob("*") as $file) {
$zip->addFile($file, "Hexacolor - " . $nomeCompeticao . "/ImportacaoRapida/".$file);
}
chdir('/home/lhsaia/confusa.top/hexacolor_repo/Imagens');
foreach (glob("*") as $file) {
$zip->addFile($file, "Hexacolor - " . $nomeCompeticao . "/Imagens/".$file);
}
chdir('/home/lhsaia/confusa.top/images/bandeiras');
foreach (glob("*") as $file) {
  if(strlen($file) == 6){
    $zip->addFile($file, "Hexacolor - " . $nomeCompeticao . "/data/PaisesReais/".$file);
  }
}
foreach (glob("*") as $file) {
  if(strlen($file) > 6){
    $zip->addFile($file, "Hexacolor - " . $nomeCompeticao . "/data/Paises/".$file);
  }
}
$zip->close();

die(json_encode([ 'success'=> true, 'error'=> $error_msg, 'filename' => $zip_name]));
//
// header('Content-type: application/zip');
// header('Content-disposition: filename="' . $zip_name . '"');
// header("Content-length: " . filesize($zip_name));
// readfile($zip_name);
//
// foreach($exportFiles as $file)
// {
//     unlink($file[0]);
// }
