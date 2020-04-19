<?php

session_start();
ini_set( 'display_errors', true );
error_reporting( E_ALL );

if(isset($_POST['ajax'])){



include($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include($_SERVER['DOCUMENT_ROOT']."/objetos/jogador.php");
include($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
include($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");
include($_SERVER['DOCUMENT_ROOT']."/objetos/estadio.php");
include($_SERVER['DOCUMENT_ROOT']."/objetos/clima.php");
include($_SERVER['DOCUMENT_ROOT']."/objetos/tecnico.php");
include($_SERVER['DOCUMENT_ROOT']."/objetos/liga.php");
include($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
include($_SERVER['DOCUMENT_ROOT']."/objetos/jogos.php");
include($_SERVER['DOCUMENT_ROOT']."/objetos/arbitros.php");

$database = new Database();
$db = $database->getConnection();
$jogador = new Jogador($db);
$pais = new Pais($db);
$time = new Time($db);
$estadio = new Estadio($db);
$clima = new Clima($db);
$tecnico = new Tecnico($db);
$liga = new Liga($db);
$usuario = new Usuario($db);
$jogo = new Jogo($db);
$trioArbitragem = new TrioArbitragem($db);


$sexo = $_POST['sexo'];
$campeonato_jogo_import = $_POST['campeonato_jogo_import'];
$fase_jogo_import = $_POST['fase_jogo_import'];

if(isset($_SESSION['jogadorTime'])){
  if($_SESSION['jogadorTime'] == 2){
      $correct_extension = 'ymt';
      $max_file_size = 400000;
      $arquivo_tratamento = "/times/tratamento_time.php";
  } else if ($_SESSION['jogadorTime'] == 1){
      $correct_extension = 'jog';
      $max_file_size = 2400;
      $arquivo_tratamento = "/jogadores/tratamento_jogador.php";
  } else if($_SESSION['jogadorTime'] == 3){
      $correct_extension = 'tda';
      $max_file_size = 400;
      $arquivo_tratamento = "/arbitros/tratamento_arbitro.php";
  } else if($_SESSION['jogadorTime'] == 4){
      $arquivo_tratamento = "/ranking/tratamento_jogo.php";
      $correct_extension = 'hyl';
      $max_file_size = 400000;
  } else if($_SESSION['jogadorTime'] == 5){
      $arquivo_tratamento = "/import/tratamento_tecnico.php";
      $correct_extension = 'tec';
      $max_file_size = 2400;
  } else if($_SESSION['jogadorTime'] == 6){
      $arquivo_tratamento = "/import/tratamento_estadio.php";
      $correct_extension = 'est';
      $max_file_size = 2000;
  }
}

    $upload_success = null;
    $upload_error = '';
    $is_success = false;
    if(isset($_POST['ligaselecionada'])){
        $ligaSelecionada = $_POST['ligaselecionada'];
        $paisLigaSelecionada = $_POST['paisligaselecionada'];
    } else {
        $ligaSelecionada = null;
    }

    if(isset($_POST['timeselecionado'])){
        $timeSelecionado = $_POST['timeselecionado'];
    } else {
        $timeSelecionado = null;
    }

    if(isset($_POST['nacionalidade'])){
        $nacionalidadeSelecionada = $_POST['nacionalidade'];
    } else {
        $nacionalidadeSelecionada = null;
    }



    if (!empty($_FILES['files'])) {

        $filesToUpload = array();
        $fileExt = [];
        $forbiddenFile = [];
        $fileSizeCheck = [];

        $j = 0;
        foreach($_FILES['files']['name'] as $fileName){
            $fileName = (string) $fileName;
            $fileExt = substr($fileName,-3);
            $countOfDots = (int)substr_count($fileName,".");

            if($countOfDots>01){
                $forbiddenFile = 1;
            } else {
                $forbiddenFile = 0;
            }
            $filesToUpload[$j][1] = $fileExt;
            $filesToUpload[$j][2] = $forbiddenFile;
            $j++;
        }

        $j = 0;
        foreach($_FILES['files']['size'] as $fileSize){
            $filesToUpload[$j][3] = $fileSize;
            $j++;
        }

        $j = 0;
        foreach($_FILES['files']['tmp_name'] as $tempName){
            $filesToUpload[$j][0] = $tempName;
            $j++;
        }

        $j = 0;
        foreach($_FILES['files']['name'] as $originalName){
            $filesToUpload[$j][4] = $originalName;
            $j++;
        }

        //libxml_use_internal_errors(true);

        $filePath = "";
        for($i = 0;$i <=count($filesToUpload)-1; $i++){
            $filePath = $filesToUpload[$i][0];
            $forbidden = $filesToUpload[$i][2];
            $importExt = $filesToUpload[$i][1];
            $importSize = $filesToUpload[$i][3];
            $originalName = $filesToUpload[$i][4];
            $error_msg = '';

            if($filePath != "" && $forbidden == 0 && $importExt == $correct_extension && $importSize <= $max_file_size){

              if($_SESSION['jogadorTime'] == 4){
                $xml = json_decode(file_get_contents($filePath));
              } else {
                if(simplexml_load_string(file_get_contents($filePath)) == false){
                    $xml = simplexml_load_string(utf8_encode(file_get_contents($filePath)));

                } else {
                    $xml = simplexml_load_string(file_get_contents($filePath));
                }
                $usuario->atualizarAlteracao($_SESSION['user_id']);
              }

                include($_SERVER['DOCUMENT_ROOT'].$arquivo_tratamento);

                if($xml === false){
                    foreach(libxml_get_errors() as $error) {
                        echo "\t", $error->message;
                    }
                }
            } else {
                if($filePath == ""){
                    $error_msg .= "Nome ".$filePath." inválido. ";
                }
                if($forbidden == 1){
                    $error_msg .= "Nome com muitos pontos. ";
                }
                if($importExt != $correct_extension){
                    $error_msg .= "Extensão ".$importExt." incorreta. ";
                }
                if($importSize > $max_file_size){
                    $error_msg .= "Arquivo muito grande. ";
                }

            }


        }

    die(json_encode([ 'success'=> $is_success, 'error'=> $error_msg]));

     }
    }



?>
