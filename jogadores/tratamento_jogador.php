<?php

//  definir informações do jogador
                $jogador->nomeJogador = (string)$xml->jogador->Nome;
                $jogador->nascimento = (int)$xml->jogador->Idade;
                $jogador->nivel = (int)$xml->jogador->Nivel;
                $jogador->mentalidade = (int)$xml->jogador->Mentalidade;
                $jogador->cobradorFalta = (int)$xml->jogador->CobradorFalta;
                $jogador->condicao = (string)$xml->jogador->apto;

                //determinacao goleiro ou linha
                if($xml->posicoesJogador->G == 'true'){
                    $isGoleiro = true;
                } else {
                    $isGoleiro = false;
                }

                //determinacao dos atributos
                if($isGoleiro){
                    $jogador->marcacao = (float)0.0;
                    $jogador->desarme = (float)0.0;
                    $jogador->visaoJogo = (float)0.0;
                    $jogador->movimentacao = (float)0.0;
                    $jogador->cruzamentos = (float)0.0;
                    $jogador->cabeceamento = (float)0.0;
                    $jogador->tecnica = (float)0.0;
                    $jogador->controleBola = (float)0.0;
                    $jogador->finalizacao = (float)0.0;
                    $jogador->faroGol = (float)0.0;
                    $jogador->velocidade = (float)0.0;
                    $jogador->forca = (float)0.0;
                    $jogador->reflexos = (float)$xml->atributosGoleiro->Reflexos;
                    $jogador->seguranca = (float)$xml->atributosGoleiro->Seguranca;
                    $jogador->saidas = (float)$xml->atributosGoleiro->Saidas;
                    $jogador->jogoAereo = (float)$xml->atributosGoleiro->JogoAereo;
                    $jogador->lancamentos = (float)$xml->atributosGoleiro->Lancamentos;
                    $jogador->defesaPenaltis = (float)$xml->atributosGoleiro->DefesaPenaltis;
                    $jogador->determinacao = (float)$xml->atributosGoleiro->Determinacao;
                    $jogador->determinacaoOriginal = (float)$xml->atributosGoleiro->DeterminacaoOriginal;
                    
                } else {
                    $jogador->marcacao = (float)$xml->atributosJogador->Marcacao;
                    $jogador->desarme = (float)$xml->atributosJogador->Desarme;
                    $jogador->visaoJogo = (float)$xml->atributosJogador->VisaoJogo;
                    $jogador->movimentacao = (float)$xml->atributosJogador->Movimentacao;
                    $jogador->cruzamentos = (float)$xml->atributosJogador->Cruzamentos;
                    $jogador->cabeceamento = (float)$xml->atributosJogador->Cabeceamento;
                    $jogador->tecnica = (float)$xml->atributosJogador->Tecnica;
                    $jogador->controleBola = (float)$xml->atributosJogador->ControleBola;
                    $jogador->finalizacao = (float)$xml->atributosJogador->Finalizacao;
                    $jogador->faroGol = (float)$xml->atributosJogador->FaroGol;
                    $jogador->velocidade = (float)$xml->atributosJogador->Velocidade;
                    $jogador->forca = (float)$xml->atributosJogador->Forca;
                    $jogador->reflexos = (float)0.0;
                    $jogador->seguranca = (float)0.0;
                    $jogador->saidas = (float)0.0;
                    $jogador->jogoAereo = (float)0.0;
                    $jogador->lancamentos = (float)0.0;
                    $jogador->defesaPenaltis = (float)0.0;
                    $jogador->determinacao = (float)$xml->atributosJogador->Determinacao;
                    $jogador->determinacaoOriginal = (float)$xml->atributosJogador->DeterminacaoOriginal;

                }

                //nacionalidade
                $array = explode(".",$xml->nacionalidade[0]);
                $siglaImport = $array[0];
                if($siglaImport <> '-'){
                    $idObtida = $pais->idPorBandeira($siglaImport);
                    if($idObtida == null){
                        $error_msg .= 'Houve um erro com a nacionalidade do jogador '. $jogador->nomeJogador . ", com arquivo de bandeira " . $siglaImport . " que não foi reconhecido.";
                    }
                        $jogador->pais = $idObtida;
                } else {
                    $jogador->pais = 0;
                }

                //stringposicoes
                $stringPos = '';
                $counter = 0;
                foreach($xml->posicoesJogador->children() as $novaPosicao){
                    if($counter > 0){
                        $stringPos .= ($novaPosicao == 'true' ? '1': '0');
                    }
                    $counter++;
                } 

                $jogador->stringPosicoes = $stringPos;
                $jogador->sexo = $sexo;
                
                $jogador->valor = $jogador->calcularPasse();
                
                    
			 if($jogador->create()){
                 
                 
                 if(isset($timeSelecionado) && $timeSelecionado != 0){
                    $codigo_jogador = $db->lastInsertId();
                    if($jogador->transferir($codigo_jogador,$timeSelecionado,0,0,-1)){
                        $is_success = true;  
                    } else {
                        $is_success = false;
                    } 
                 } else {
                    
                    $is_success = true;
                 }
			 } else {
                 $error_msg .= 'Não foi possível inserir o jogador';
             }

?>