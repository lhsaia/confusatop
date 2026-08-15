<?php

//  definir informações básicas do jogo
        $jogo->estadio_nome = (string)$xml->estadio;
        $jogo->estadio_id = NULL;
        $jogo->timeA_gols = (int)$xml->placarTime1 + ((int)$xml->placarProrrogacaoTime1 >= 0 ? (int)$xml->placarProrrogacaoTime1 : 0);
        $jogo->timeB_gols = (int)$xml->placarTime2 + ((int)$xml->placarProrrogacaoTime2 >= 0 ? (int)$xml->placarProrrogacaoTime2 : 0);
        $jogo->timeA_penaltis = ((int)$xml->placarPenaltisTime1 >= 0 ? (int)$xml->placarPenaltisTime1 : NULL);
        $jogo->timeB_penaltis = ((int)$xml->placarPenaltisTime2 >= 0 ? (int)$xml->placarPenaltisTime2 : NULL);
        $jogo->fase = $fase_jogo_import;
        $jogo->competicao_id = $campeonato_jogo_import;
        $jogo->competicao_tipo = (int)(isset($_POST['competicao_tipo']) ? $_POST['competicao_tipo'] : 1);
        $jogo->dono = (int)$_SESSION['user_id'];

        $nome_time_A = (string)$xml->time1;
        $nome_time_B = (string)$xml->time2;
        $jogo->timeA_nome = $nome_time_A;
        $jogo->timeB_nome = $nome_time_B;
        //tratamento nome times e encontrar id

        $jogo->timeA_id = $time->idPorNome($nome_time_A);
        $jogo->timeB_id = $time->idPorNome($nome_time_B);

        if($jogo->timeA_id == 0){
          $jogo->timeA_id = NULL;
        }
        if($jogo->timeB_id == 0){
          $jogo->timeB_id = NULL;
        }
        
        $jogo->timeA_nome = $nome_time_A;
        $jogo->timeB_nome = $nome_time_B;

        // $jogo->timeA_bandeira = $pais->bandeiraPorId($jogo->timeA_id);
        // $jogo->timeB_bandeira = $pais->bandeiraPorId($jogo->timeB_id);


        // Pre-map players to teams and names from JSON rosters (definitive source)
        $player_team_map = Array();
        $player_name_map = Array();
        
        $rosterKeysA = ['escalacaoTime1', 'reservasTime1', 'titularesTime1', 'bancoTime1', 'elencoTime1'];
        $rosterKeysB = ['escalacaoTime2', 'reservasTime2', 'titularesTime2', 'bancoTime2', 'elencoTime2'];

        foreach($rosterKeysA as $key){
            if(isset($xml->$key) && (is_array($xml->$key) || is_object($xml->$key))){
                foreach($xml->$key as $p){
                    if(isset($p->id)){
                        $player_team_map[(int)$p->id] = 1;
                        if(isset($p->nome)) $player_name_map[(int)$p->id] = (string)$p->nome;
                    }
                }
            }
        }
        foreach($rosterKeysB as $key){
            if(isset($xml->$key) && (is_array($xml->$key) || is_object($xml->$key))){
                foreach($xml->$key as $p){
                    if(isset($p->id)){
                        $player_team_map[(int)$p->id] = 2;
                        if(isset($p->nome)) $player_name_map[(int)$p->id] = (string)$p->nome;
                    }
                }
            }
        }

        $log_eventos = Array();
        $substituicoes = Array();
        // tratamento de eventos
        foreach($xml->eventos as $single_event){
          if((int)$single_event->tempo > 5) continue;
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
              $tipoEvento = 0;
              break;
            case "substituicao":
              $tipoEvento = 0; // We handle this separately for the lineup
              $substituicoes[] = Array(
                "idSai" => (int)$single_event->idJogador,
                "idEntra" => (int)$single_event->idNovoJogador,
                "tempo" => (int)$single_event->tempo,
                "minutos" => (int)$single_event->minutos
              );
              break;
            default:
              $tipoEvento = 0;
              break;
          }
          if($tipoEvento > 0){
            $tempId = (int)$single_event->idJogador;
            $nomeJogador = isset($player_name_map[$tempId]) ? $player_name_map[$tempId] : "";
            $idTime = 0;
            $nomeTime = "";

            // Identify team based on where the ID was found in JSON rosters
            $foundTeam = isset($player_team_map[$tempId]) ? $player_team_map[$tempId] : 0;
            
            if($foundTeam == 1){
                $idTime = $jogo->timeA_id;
                $nomeTime = $nome_time_A;
            } elseif($foundTeam == 2){
                $idTime = $jogo->timeB_id;
                $nomeTime = $nome_time_B;
            } else {
                // If not found in ANY roster, then and ONLY then use the event's time attribute as a desperate fallback
                $prefTime = (int)$single_event->time;
                $idTime = ($prefTime == 2 ? $jogo->timeB_id : $jogo->timeA_id);
                $nomeTime = ($prefTime == 2 ? $nome_time_B : $nome_time_A);
            }

            // Check if player exists in DB. 
            $idJogador = $jogador->idPorNomeClube($nomeJogador, $idTime, $tempId);
            
            // If name was blank but DB found him, recover name for display
            if($nomeJogador == "" && $idJogador != 0){
                $playerInfo = $jogador->readInfo($idJogador);
                if($playerInfo) $nomeJogador = $playerInfo['Nome'];
            }
            
            $log_eventos[] = Array("tempo" => (int)$single_event->tempo, "minutos" => (int)$single_event->minutos, "tipo" => $tipoEvento ,"idJogador" => $idJogador , "nomeJogador" => $nomeJogador, "idTime" => $idTime, "nomeTime" => $nomeTime);
          }
        }
        
        $log_escalacao = Array();
        // tratamento escalacao Time A
        $rosterKeysA = ['escalacaoTime1', 'reservasTime1', 'titularesTime1', 'bancoTime1'];
        foreach($rosterKeysA as $rKey){
          if(!isset($xml->$rKey)) continue;
          foreach($xml->$rKey as $single_player){
             $nomeJogador = (string)$single_player->nome;
             $tempId = (int)$single_player->id;
             $idTime = $jogo->timeA_id;
             $posicaoJogador = 0; // Default
             $iniciou = 1; // Default
             
             // Check attributes if they exist
             if(isset($single_player->posicao)) $posicaoJogador = (string)$single_player->posicao;
             if(isset($single_player->titular)) {
                $iniciou = ((int)$single_player->titular == 1 || $single_player->titular === true || (string)$single_player->titular === "true") ? 1 : 0;
             } else {
                // Infer from key name
                if(strpos($rKey, 'reservas') !== false || strpos($rKey, 'banco') !== false) $iniciou = 0;
             }
             
             $idJogador = $jogador->idPorNomeClube($nomeJogador, $idTime, $tempId);

             // Sub logic
             $entrada_tempo = ($iniciou == 1 ? 0 : NULL);
             $entrada_minuto = ($iniciou == 1 ? 0 : NULL);
             $saida_tempo = NULL;
             $saida_minuto = NULL;

             foreach($substituicoes as $sub){
                if($sub['idSai'] == $tempId){
                    $saida_tempo = $sub['tempo'];
                    $saida_minuto = $sub['minutos'];
                }
                if($sub['idEntra'] == $tempId){
                    $entrada_tempo = $sub['tempo'];
                    $entrada_minuto = $sub['minutos'];
                }
             }
             
             $log_escalacao[] = Array(
                "idJogador" => $idJogador, 
                "nomeJogador" => $nomeJogador, 
                "idTime" => $idTime,
                "nomeTime" => $nome_time_A,
                "titular" => $iniciou, 
                "posicao" => $posicaoJogador,
                "numero" => (isset($single_player->numero) ? (int)$single_player->numero : 0),
                "entrada_tempo" => $entrada_tempo,
                "entrada_minuto" => $entrada_minuto,
                "saida_tempo" => $saida_tempo,
                "saida_minuto" => $saida_minuto
             );
          }
        }
        // tratamento escalacao Time B
        $rosterKeysB = ['escalacaoTime2', 'reservasTime2', 'titularesTime2', 'bancoTime2'];
        foreach($rosterKeysB as $rKey){
          if(!isset($xml->$rKey)) continue;
          foreach($xml->$rKey as $single_player){
             $nomeJogador = (string)$single_player->nome;
             $tempId = (int)$single_player->id;
             $idTime = $jogo->timeB_id;
             $posicaoJogador = 0; 
             $iniciou = 1; 
             
             if(isset($single_player->posicao)) $posicaoJogador = (string)$single_player->posicao;
             if(isset($single_player->titular)) {
                $iniciou = ((int)$single_player->titular == 1 || $single_player->titular === true || (string)$single_player->titular === "true") ? 1 : 0;
             } else {
                if(strpos($rKey, 'reservas') !== false || strpos($rKey, 'banco') !== false) $iniciou = 0;
             }
             
             $idJogador = $jogador->idPorNomeClube($nomeJogador, $idTime, $tempId);

             // Sub logic
             $entrada_tempo = ($iniciou == 1 ? 0 : NULL);
             $entrada_minuto = ($iniciou == 1 ? 0 : NULL);
             $saida_tempo = NULL;
             $saida_minuto = NULL;

             foreach($substituicoes as $sub){
                if($sub['idSai'] == $tempId){
                    $saida_tempo = $sub['tempo'];
                    $saida_minuto = $sub['minutos'];
                }
                if($sub['idEntra'] == $tempId){
                    $entrada_tempo = $sub['tempo'];
                    $entrada_minuto = $sub['minutos'];
                }
             }
             
             $log_escalacao[] = Array(
                "idJogador" => $idJogador, 
                "nomeJogador" => $nomeJogador, 
                "idTime" => $idTime, 
                "nomeTime" => $nome_time_B,
                "titular" => $iniciou, 
                "posicao" => $posicaoJogador,
                "numero" => (isset($single_player->numero) ? (int)$single_player->numero : 0),
                "entrada_tempo" => $entrada_tempo,
                "entrada_minuto" => $entrada_minuto,
                "saida_tempo" => $saida_tempo,
                "saida_minuto" => $saida_minuto
             );
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
			$jogo->importarEscalacao($log_escalacao, $idJogo);
			$is_success = true;
			} else {
        $error_msg = 'Acusando duplicata';
      }

?>
