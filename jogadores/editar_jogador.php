<?php

ini_set( 'display_errors', true );
error_reporting( E_ALL );
session_start();
if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){

	$localizacao_foto = null;
    $idJogador = $_POST['idJogador'];
    $tipo = $_POST['alteracao'];
	if(isset($_POST['timeParaDemissao'])){
		$timeParaDemissao = $_POST['timeParaDemissao'];
	} else {
		$timeParaDemissao = null;
	}

    //conferir informações sobre o dono do time e do jogador vs o usuário logado!

    //estabelecer conexão com banco de dados
    include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogador.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
	require ($_SERVER['DOCUMENT_ROOT']."/pngquant/utility.php");
    $database = new Database();
    $db = $database->getConnection();
    $jogador = new Jogador($db);
    $usuario = new Usuario($db);
	
	function imageImporter($file_name, $target_filename){
      $maxDim = 180;
      list($width, $height, $type, $attr) = getimagesize( $file_name );
      if ( $width > $maxDim || $height > $maxDim ) {
      //  $target_filename = $file_name;
        $ratio = $width/$height;
        if( $ratio > 1) {
          $new_width = $maxDim;
          $new_height = $maxDim/$ratio;
        } else {
          $new_width = $maxDim*$ratio;
          $new_height = $maxDim;
        }
    } else {
      $new_width = $width;
      $new_height = $height;
    }
        //$save_to_path = "uploads/compressed_file.png";
        if($type == "image/png"){
			$compressed_png_content = compress_png($file_name);
			$src = imagecreatefromstring($compressed_png_content);
        } else if ($type == 18 || $type == "") {
			$src = imagecreatefromwebp($file_name);
		} else {
        			
            try {
                $src = imagecreatefromstring( file_get_contents( $file_name ) );
            } catch (Exception $e) {
                $src = imagecreatefromwebp($file_name);
            }
			
			
        }

        //file_put_contents($save_to_path, $compressed_png_content);
        $dst = imagecreatetruecolor( $new_width, $new_height );
        //start changes
        $background = imagecolorallocate($dst , 0, 0, 0);
        imagecolortransparent($dst, $background);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        //end changes
        imagecopyresampled( $dst, $src, 0, 0, 0, 0, $new_width, $new_height, $width, $height );
        imagedestroy( $src );
        //imagepng( $dst, $target_filename ); // adjust format as needed
		imagewebp($dst, $target_filename);
        imagedestroy( $dst );

    }


    if($tipo == 1){
        //criar transferencia pendente
        if($jogador->disponibilizar($idJogador)){
            $is_success = true;
            $error_msg = "";
        } else {
            $is_success = false;
            $error_msg = "Falha ao disponibilizar jogador";
        }
    } else if($tipo == 2){
        //criar transferencia pendente
        $idTime = $_POST['idTime'];
        if($jogador->demitir($idJogador,$idTime)){
            $usuario->atualizarAlteracao($_SESSION['user_id']);
            $is_success = true;
            $error_msg = "";
        } else {
            $is_success = false;
            $error_msg = "Falha ao demitir jogador";
        }
    } else if($tipo == 3){
        //colocar aqui dados sobre edição de jogador
        $idDonoTime = $_SESSION['user_id'];
        //$idDonoTime = 9;
        $idDonoJogador = $jogador->verificarDono($idJogador);



        if($idDonoTime == $idDonoJogador || $idDonoJogador == 0){
            $nomeJogador = $_POST['nome'];
            $nacionalidadeJogador = $_POST['nacionalidade'];
            $nascimentoJogador = $_POST['nascimento'];
			$encerramentoContrato = $_POST['encerramento'];
			$desdeContrato = $_POST['desde'];
            $isDono = true;
        } else {
            $nomeJogador = null;
            $nacionalidadeJogador = null;
            $nascimentoJogador = null;
			$encerramentoContrato = null;
			$desdeContrato = null;
            $isDono = false;
        }

        $idTime = $_POST['idTime'];
        $valorJogador = $_POST['valor'];
        if(isset($_POST['posicoes'])){
            $posicoesJogador = $_POST['posicoes'];
        } else {
            $posicoesJogador = array();
        }

        $nivelJogador = $_POST['nivel'];

        if($jogador->editar($idJogador,$idTime,$nomeJogador,$nacionalidadeJogador,$nascimentoJogador,$valorJogador,$posicoesJogador,$nivelJogador,$isDono,null,null,null,null, $encerramentoContrato, null, $desdeContrato)){
            $usuario->atualizarAlteracao($_SESSION['user_id']);
            $is_success = true;
            $error_msg = "";
        } else {
            $is_success = false;
            $error_msg = "Falha ao editar jogador";
        }
    } else if($tipo == 4){
        //criar transferencia pendente
        $idTime = $_POST['idTime'];
        if($jogador->aposentar($idJogador,$idTime)){
            $usuario->atualizarAlteracao($_SESSION['user_id']);
            $is_success = true;
            $error_msg = "";
        } else {
            $is_success = false;
            $error_msg = "Falha ao aposentar jogador";
        }
    } else if($tipo == 5){
        //criar transferencia pendente
        $idTime = $_POST['idTime'];
        if($jogador->transferir($idJogador,$idTime,0,0,-1,0,0,0,0)){
            $usuario->atualizarAlteracao($_SESSION['user_id']);
            $is_success = true;
            $error_msg = "";
        } else {
            $is_success = false;
            $error_msg = "Falha ao repatriar jogador";
        }
    } else if($tipo == 6){
        //criar transferencia pendente
        $novoNivel = $_POST['novoNivel'];
        if($jogador->incorporarModificador($idJogador,$novoNivel)){
            $usuario->atualizarAlteracao($_SESSION['user_id']);
            $is_success = true;
            $error_msg = "";
        } else {
            $is_success = false;
            $error_msg = "Falha ao incorporar modificador";
        }
    } else if($tipo == 7){
		        //criar transferencia pendente
        $idTime = $_POST['idTime'];
        if($jogador->expatriar($idJogador,$idTime)){
            $usuario->atualizarAlteracao($_SESSION['user_id']);
            $is_success = true;
            $error_msg = "";
        } else {
            $is_success = false;
            $error_msg = "Falha ao expatriar jogador";
        }
	}else if($tipo == 9){
          //colocar aqui dados sobre edição de jogador
          $idDonoJogador = $_SESSION['user_id'];
          //$idDonoTime = 9;
          $idDonoTime = $jogador->verificarDonoTimeVinculado($idJogador);

          if(is_null($idDonoTime) || $idDonoTime == 0){
              $idDonoTime = $idDonoJogador;
          }

          if($idDonoTime == $idDonoJogador){
              $nomeJogador = $_POST['nome'];
              $nacionalidadeJogador = $_POST['nacionalidade'];
              $nascimentoJogador = $_POST['nascimento'];
              $valorJogador = $_POST['valor'];
              $determinacaoJogador = $_POST['determinacao'];
              $cobrancaFaltaJogador = $_POST['cobrancaFalta'];
              $mentalidadeJogador = $_POST['mentalidade'];
              $atividadeJogador = $_POST['atividade'];
			  $timeEnviado = $timeParaDemissao;
              $isDono = true;
          } else {
              $nomeJogador = null;
              $nacionalidadeJogador = null;
              $nascimentoJogador = null;
              $valorJogador = null;
              $determinacaoJogador = "none";
              $cobrancaFaltaJogador = null;
              $mentalidadeJogador = "none";
              $atividadeJogador = null;
              $isDono = false;
			  $timeEnviado = null;
          }

          //$idTime = $_POST['idTime'];

          if(isset($_POST['posicoes'])){
              $posicoesJogador = $_POST['posicoes'];
          } else {
              $posicoesJogador = array();
          }

          $nivelJogador = $_POST['nivel'];
		  
		      if(isset($_FILES['foto']) && !empty($_FILES['foto'])){
        $fileName = $_FILES['foto']['name'];
        $fileExplode = explode(".",$fileName);
        $fileName = $fileExplode[0] . mt_rand(1,10000).".webp";// .$fileExplode[1];
        $fileSize = $_FILES['foto']['size'];
        $filePath = $_FILES['foto']['tmp_name'];
        $fileType = $_FILES['foto']['type'];
        $fileExt = strtolower( end($fileExplode));
        $correct_extensions = array("image/png","image/jpg","image/jpeg", "image/webp");
        $upload_dir = "/images/jogadores/";

        if($filePath != "" && in_array($fileType,$correct_extensions) && $fileSize <= 2000000){

            $upload_path = $_SERVER['DOCUMENT_ROOT'] .$upload_dir .$_SESSION['user_id'] ."-" . $fileName;
            imageImporter($filePath, $upload_path);
            $localizacao_foto = $_SESSION['user_id'] ."-" .$fileName;


        } else {

            $error_msg .= "Não foi possível inserir a foto. ";
            if($fileSize > 2000000){
                $error_msg .= "Arquivo deve ser menor que 2Mb.";
            }
            if($filePath == ''){
                $error_msg .= "Falha no nome do arquivo.";
            }
            if(in_array($fileType,$correct_extensions) == false){
                $error_msg .= "Extensão ".$fileExt." não é permitida.";
            }
        }
    }


          if($jogador->editar($idJogador,$timeEnviado,$nomeJogador,$nacionalidadeJogador,$nascimentoJogador,$valorJogador,$posicoesJogador,$nivelJogador,$isDono,$atividadeJogador, $mentalidadeJogador, $determinacaoJogador, $cobrancaFaltaJogador,null, $localizacao_foto)){
            $usuario->atualizarAlteracao($_SESSION['user_id']);
              $is_success = true;
              $error_msg = "";
          } else {
              $is_success = false;
              $error_msg = "Falha ao editar jogador";
          }
    }



} else {
    $is_success = false;
    $error_msg = "Usuário não tem acesso para realizar essa ação";
}

die(json_encode([ 'success'=> $is_success, 'error'=> $error_msg]));


?>
