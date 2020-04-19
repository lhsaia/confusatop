<?php

//  definir informações básicas do jogo
        $jogo->estadio = (string)$xml->estadio;
        $jogo->timeA_gols = (int)$xml->placarTime1 + ((int)$xml->placarProrrogacaoTime1 >= 0 ? (int)$xml->placarProrrogacaoTime1 : 0);
        $jogo->timeB_gols = (int)$xml->placarTime2 + ((int)$xml->placarProrrogacaoTime2 >= 0 ? (int)$xml->placarProrrogacaoTime2 : 0);
        $jogo->timeA_penaltis = ((int)$xml->placarPenaltisTime1 >= 0 ? (int)$xml->placarPenaltisTime1 : NULL);
        $jogo->timeB_penaltis = ((int)$xml->placarPenaltisTime2 >= 0 ? (int)$xml->placarPenaltisTime2 : NULL);
        $jogo->fase = $fase_jogo_import;
        //$jogo->data = $data_jogo_import;
        $jogo->campeonato = $campeonato_jogo_import;

        $nome_pais_A = (string)$xml->time1;
        $nome_pais_B = (string)$xml->time2;
        //tratamento nome países e encontrar id

        $jogo->timeA_id = $pais->idPorNomeTratado($nome_pais_A);
        $jogo->timeB_id = $pais->idPorNomeTratado($nome_pais_B);

        $jogo->timeA_bandeira = $pais->bandeiraPorId($jogo->timeA_id);
        $jogo->timeB_bandeira = $pais->bandeiraPorId($jogo->timeB_id);


        $log_eventos = Array();
        // tratamento de eventos
        foreach($xml->eventos as $single_event){
          switch($single_event->tipoEvento){
            case "amarelo":
              $tipoEvento = 2;
              break;
            case "vermelho":
              $tipoEvento = 3;
              break;
            case "gol":
              $tipoEvento = 1;
              break;
            case "golContra":
              $tipoEvento = 4;
              break;
            case "golAnuladoVAR":
              array_pop($log_eventos);
              break;
            default:
              $tipoEvento = 0;
              break;
          }
          if($tipoEvento > 0){
            foreach($xml->escalacaoTime1 as $single_player){
              if($single_event->idJogador == $single_player->id){
                $nomeJogador = $single_player->nome;
                $idTime = $jogo->timeA_id;
                $tempId = $single_player->id;
              }
            }
            foreach($xml->escalacaoTime2 as $single_player){
              if($single_event->idJogador == $single_player->id){
                $nomeJogador = $single_player->nome;
                $idTime = $jogo->timeB_id;
                $tempId = $single_player->id;
              }
            }
            $idJogador = $jogador->idPorNomePais($nomeJogador, $idTime, $tempId);
            $log_eventos[] = Array("tempo" => $single_event->tempo, "minutos" => $single_event->minutos, "tipo" => $tipoEvento ,"idJogador" => $idJogador , "nomeJogador" => $nomeJogador, "idTime" => $idTime);
          }
        }
        $originalName = substr($originalName,10);
        $explodedName = explode(".", $originalName);

        $jogo->data = date("Y-m-d", strtotime($explodedName[0]));

        //var_dump($log_eventos);
			if($jogo->importar()){
        if($db->lastInsertId() != 0){
          $idJogo = $db->lastInsertId();
        } else {
          $idJogo = $jogo->getMatchId();
        }
        $jogo->importarEventos($log_eventos, $idJogo);
				$is_success = true;
			} else {
        $error_msg = 'Acusando duplicata';
      }

?>
