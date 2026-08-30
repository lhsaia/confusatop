<?php

ini_set( 'display_errors', true );
error_reporting( E_ALL );
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';
if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){

$localizacao_foto = null;
    $idTecnico = $_POST['idTecnico'];
    $tipo = $_POST['alteracao'];


    //conferir informações sobre o dono do time e do jogador vs o usuário logado!

    //estabelecer conexão com banco de dados
    include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/lib/image_helper.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/tecnico.php");
    $database = new Database();
    $db = $database->getConnection();
    $tecnico = new Tecnico($db);


    if($tipo == 1){
        // //criar transferencia pendente
        // if($jogador->disponibilizar($idJogador)){
        //     $is_success = true;
        //     $error_msg = "";
        // } else {
        //     $is_success = false;
        //     $error_msg = "Falha ao disponibilizar jogador";
        // }
    } else if($tipo == 2){
        //criar transferencia pendente
        $idTime = $_POST['idTime'];
        if($tecnico->demitir($idTecnico,$idTime)){
            $is_success = true;
            $error_msg = "";
        } else {
            $is_success = false;
            $error_msg = "Falha ao demitir técnico";
        }
    } else if($tipo == 3){
        // //colocar aqui dados sobre edição de jogador
         $idDonoTime = $_SESSION['user_id'];
        // //$idDonoTime = 9;
         $idDonoJogador = $tecnico->verificarDono($idTecnico);



        if($idDonoTime == $idDonoJogador){
            $nomeTecnico = $_POST['nome'];
            $nacionalidadeTecnico = $_POST['pais'];
            $nascimentoTecnico = $_POST['nascimento'];
            $isDono = true;
			if(isset($_POST['desde'])){
				$desdeContrato = $_POST['desde'];
			} else {
				$desdeContrato = null;
			}
			
        } else {
            $nomeTecnico = null;
            $nacionalidadeTecnico = null;
            $nascimentoTecnico = null;
            $isDono = false;
			$desdeContrato = null;
        }

         $idTime = $_POST['idTime'];
        $nivelTecnico = $_POST['nivel'];

        if($tecnico->editar($idTecnico,$idTime,$nomeTecnico,$nacionalidadeTecnico,$nascimentoTecnico,$nivelTecnico,$isDono, null, null, null, $desdeContrato)){
            $is_success = true;
            $error_msg = "";
        } else {
            $is_success = false;
            $error_msg = "Falha ao editar técnico na tela de time";
        }
    } else if($tipo == 4){
        // //criar transferencia pendente
        // $idTime = $_POST['idTime'];
        // if($jogador->aposentar($idJogador,$idTime)){
        //     $is_success = true;
        //     $error_msg = "";
        // } else {
        //     $is_success = false;
        //     $error_msg = "Falha ao aposentar jogador";
        // }
    } else if($tipo == 5){
        // //criar transferencia pendente
        // $idTime = $_POST['idTime'];
        // if($jogador->transferir($idJogador,$idTime,0,0,-1,0,0,0,0)){
        //     $is_success = true;
        //     $error_msg = "";
        // } else {
        //     $is_success = false;
        //     $error_msg = "Falha ao repatriar jogador";
        // }
    } else if($tipo == 6){
        // //criar transferencia pendente
        // $novoNivel = $_POST['novoNivel'];
        // if($jogador->incorporarModificador($idJogador,$novoNivel)){
        //     $is_success = true;
        //     $error_msg = "";
        // } else {
        //     $is_success = false;
        //     $error_msg = "Falha ao incorporar modificador";
        // }
    } else if($tipo == 9){
          //colocar aqui dados sobre edição de jogador
          $idDonoTecnico = $_SESSION['user_id'];
          //$idDonoTime = 9;
          $idDonoTime = $tecnico->verificarDonoTimeVinculado($idTecnico);
          $idDonoPais = $tecnico->verificarDono($idTecnico);

          if(is_null($idDonoTime) || $idDonoTime == 0){
              $idDonoTime = $idDonoTecnico;
          }

          if($idDonoTime == $idDonoTecnico || $idDonoPais == $idDonoTecnico){
              $nomeTecnico = isset($_POST['nome']) ? $_POST['nome'] : null;
              $nacionalidadeTecnico = isset($_POST['pais']) ? $_POST['pais'] : null;
              $nascimentoTecnico = isset($_POST['nascimento']) ? $_POST['nascimento'] : null;
              $mentalidadeTecnico = isset($_POST['mentalidade']) ? $_POST['mentalidade'] : null;
              $estiloTecnico = isset($_POST['estilo']) ? $_POST['estilo'] : null;

              $isDono = (!empty($nomeTecnico)) ? true : false;
          } else {
              $nomeTecnico = null;
              $nacionalidadeTecnico = null;
              $nascimentoTecnico = null;
              $mentalidadeTecnico = null;
              $estiloTecnico = null;
              $isDono = false;
          }

		  		      if(isset($_FILES['foto']) && !empty($_FILES['foto'])){
        $fileName = $_FILES['foto']['name'];
        $fileExplode = explode(".",$fileName);
        $fileName = $fileExplode[0] . mt_rand(1,10000).".webp";// .$fileExplode[1];
        $fileSize = $_FILES['foto']['size'];
        $filePath = $_FILES['foto']['tmp_name'];
        $fileType = $_FILES['foto']['type'];
        $fileExt = strtolower( end($fileExplode));
        $correct_extensions = array("image/png","image/jpg","image/jpeg", "image/webp");
        $upload_dir = "/images/tecnicos/";

        if($filePath != "" && in_array($fileType,$correct_extensions) && $fileSize <= 8000000){

            $upload_path = $_SERVER['DOCUMENT_ROOT'] .$upload_dir .$_SESSION['user_id'] ."-" . $fileName;
            processAndSaveWebPImage($filePath, $upload_path, 300, 90);
            $localizacao_foto = $_SESSION['user_id'] ."-" .$fileName;


        } else {

            $error_msg .= "Não foi possível inserir a foto. ";
            if($fileSize > 8000000){
                $error_msg .= "Arquivo deve ser menor que 8Mb.";
            }
            if($filePath == ''){
                $error_msg .= "Falha no nome do arquivo.";
            }
            if(in_array($fileType,$correct_extensions) == false){
                $error_msg .= "Extensão ".$fileExt." não é permitida.";
            }
        }
    }
			
          $nivelTecnico = $_POST['nivel'];

          if($tecnico->editar($idTecnico,null,$nomeTecnico,$nacionalidadeTecnico,$nascimentoTecnico,$nivelTecnico,$isDono,$mentalidadeTecnico, $estiloTecnico, $localizacao_foto)){
              $is_success = true;
              $error_msg = "";
          } else {
              $is_success = false;
              $error_msg = "Falha ao editar técnico";
          }
    }



} else {
    $is_success = false;
    $error_msg = "Usuário não tem acesso para realizar essa ação";
}

die(json_encode([ 'success'=> $is_success, 'error'=> $error_msg]));


?>
