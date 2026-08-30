<?php

ini_set( 'display_errors', false );
error_reporting( 0 );
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

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
    include_once($_SERVER['DOCUMENT_ROOT']."/lib/image_helper.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogador.php");
    include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
    
    $database = new Database();
    $db = $database->getConnection();
    $jogador = new Jogador($db);
    $usuario = new Usuario($db);


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
			if(isset($_POST['desde'])){
				$desdeContrato = $_POST['desde'];
			} else {
				$desdeContrato = null;
			}
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
		
		if(isset($_POST['numeroCamisa'])){
			$numeroCamisa = $_POST['numeroCamisa'];
		} else {
			$numeroCamisa = null;
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

        if($jogador->editar($idJogador,$idTime,$nomeJogador,$nacionalidadeJogador,$nascimentoJogador,$valorJogador,$posicoesJogador,$nivelJogador,$isDono,null,null,null,null, $encerramentoContrato, $localizacao_foto, $desdeContrato, $numeroCamisa)){
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
          $idDonoPais = $jogador->verificarDono($idJogador);

          if(is_null($idDonoTime) || $idDonoTime == 0){
              $idDonoTime = $idDonoJogador;
          }

          if($idDonoTime == $idDonoJogador || $idDonoPais == $idDonoJogador){
              $nomeJogador = isset($_POST['nome']) ? $_POST['nome'] : null;
              $nacionalidadeJogador = isset($_POST['nacionalidade']) ? $_POST['nacionalidade'] : null;
              $nascimentoJogador = isset($_POST['nascimento']) ? $_POST['nascimento'] : null;
              $valorJogador = isset($_POST['valor']) ? $_POST['valor'] : null;
              $determinacaoJogador = isset($_POST['determinacao']) ? $_POST['determinacao'] : null;
              $cobrancaFaltaJogador = isset($_POST['cobrancaFalta']) ? $_POST['cobrancaFalta'] : null;
              $mentalidadeJogador = isset($_POST['mentalidade']) ? $_POST['mentalidade'] : null;
              $atividadeJogador = isset($_POST['atividade']) ? $_POST['atividade'] : null;
			  $timeEnviado = $timeParaDemissao;
              $isDono = (!empty($nomeJogador)) ? true : false;
          } else {
              $nomeJogador = isset($_POST['nome']) ? $_POST['nome'] : null;
              $nacionalidadeJogador = isset($_POST['nacionalidade']) ? $_POST['nacionalidade'] : null;
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


          if($jogador->editar($idJogador,$timeEnviado,$nomeJogador,$nacionalidadeJogador,$nascimentoJogador,$valorJogador,$posicoesJogador,$nivelJogador,$isDono,$atividadeJogador, $mentalidadeJogador, $determinacaoJogador, $cobrancaFaltaJogador,null, $localizacao_foto)){
            $usuario->atualizarAlteracao($_SESSION['user_id']);
              $is_success = true;
              $error_msg = "";
          } else {
              $is_success = false;
              $error_msg = "Falha ao editar jogador";
          }
    } else if($tipo == 10){
        // Atualizar link de referencia
        $referencia = isset($_POST['referencia']) ? trim($_POST['referencia']) : '';
        $resultado = $jogador->atualizarReferencia($idJogador, $referencia);
        if ($resultado === true) {
            $is_success = true;
            $error_msg = "";
        } else if ($resultado === "DUPLICATE") {
            $is_success = false;
            $error_msg = "Esse link já está sendo usado por outro jogador.";
        } else {
            $is_success = false;
            $error_msg = "Falha ao adicionar link de referência.";
        }
    } else if($tipo == 11){
        // Adicionar transferência histórica
        $clubeOrigem = isset($_POST['clubeOrigem']) ? (int)$_POST['clubeOrigem'] : 0;
        $clubeDestino = isset($_POST['clubeDestino']) ? (int)$_POST['clubeDestino'] : 0;
        $valor = isset($_POST['valor']) ? (float)$_POST['valor'] : 0.0;
        $data = isset($_POST['data']) ? trim($_POST['data']) : '';
        $emprestimo = isset($_POST['emprestimo']) ? (int)$_POST['emprestimo'] : 0;

        $idDonoJogador = $jogador->verificarDono($idJogador);
        if ($idDonoJogador == $_SESSION['user_id'] || $idDonoJogador == 0) {
            if ($jogador->adicionarTransferenciaHistorica($idJogador, $clubeOrigem, $clubeDestino, $valor, $data, $emprestimo)) {
                $is_success = true;
                $error_msg = "";
            } else {
                $is_success = false;
                $error_msg = "Falha ao registrar transferência histórica no banco de dados.";
            }
        } else {
            $is_success = false;
            $error_msg = "Você não tem permissão para alterar o histórico deste jogador.";
        }
    } else if($tipo == 12){
        // Deletar transferência histórica
        $idTransferencia = isset($_POST['idTransferencia']) ? (int)$_POST['idTransferencia'] : 0;

        $idDonoJogador = $jogador->verificarDono($idJogador);
        if ($idDonoJogador == $_SESSION['user_id'] || $idDonoJogador == 0) {
            if ($jogador->deletarTransferenciaHistorica($idTransferencia, $idJogador)) {
                $is_success = true;
                $error_msg = "";
            } else {
                $is_success = false;
                $error_msg = "Falha ao apagar transferência histórica do banco de dados.";
            }
        } else {
            $is_success = false;
            $error_msg = "Você não tem permissão para alterar o histórico deste jogador.";
        }
    } else if($tipo == 13){
        // Editar transferência histórica
        $idTransferencia = isset($_POST['idTransferencia']) ? (int)$_POST['idTransferencia'] : 0;
        $clubeOrigem = isset($_POST['clubeOrigem']) ? (int)$_POST['clubeOrigem'] : 0;
        $clubeDestino = isset($_POST['clubeDestino']) ? (int)$_POST['clubeDestino'] : 0;
        $valor = isset($_POST['valor']) ? (float)$_POST['valor'] : 0.0;
        $data = isset($_POST['data']) ? trim($_POST['data']) : '';
        $emprestimo = isset($_POST['emprestimo']) ? (int)$_POST['emprestimo'] : 0;

        $idDonoJogador = $jogador->verificarDono($idJogador);
        if ($idDonoJogador == $_SESSION['user_id'] || $idDonoJogador == 0) {
            if ($jogador->atualizarTransferenciaHistorica($idTransferencia, $idJogador, $clubeOrigem, $clubeDestino, $valor, $data, $emprestimo)) {
                $is_success = true;
                $error_msg = "";
            } else {
                $is_success = false;
                $error_msg = "Falha ao atualizar transferência histórica no banco de dados.";
            }
        } else {
            $is_success = false;
            $error_msg = "Você não tem permissão para alterar o histórico deste jogador.";
        }
    }



} else {
    $is_success = false;
    $error_msg = "Usuário não tem acesso para realizar essa ação";
}

die(json_encode([ 'success'=> $is_success, 'error'=> $error_msg]));


