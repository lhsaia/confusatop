<?php

//  definir informações do trio
				$trioArbitragem->nomeArbitro = preg_replace("/\[[^)]+\]/","",preg_replace("/\([^)]+\)/","",(string)$xml->Arbitro[0]));
				$trioArbitragem->nomeAuxiliarUm = preg_replace("/\[[^)]+\]/","",preg_replace("/\([^)]+\)/","",(string)$xml->Auxiliar1[0]));
				$trioArbitragem->nomeAuxiliarDois = preg_replace("/\[[^)]+\]/","",preg_replace("/\([^)]+\)/","",(string)$xml->Auxiliar2[0]));
                $trioArbitragem->estilo = (string)$xml->Estilo[0];

                //tentativa de ver se há nome de país
                $siglaImport = "";
                if(preg_match('/\[(.*)\]/', $xml->Arbitro[0], $matches)){
                    $siglaImport .=  $matches[1][0];
                    $siglaImport .=  $matches[1][1];
                    $siglaImport .=  $matches[1][2];
                } else if(preg_match('/\((.*)\)/', $xml->Arbitro[0], $matches)){
                    $siglaImport .=  $matches[1][0];
                    $siglaImport .=  $matches[1][1];
                    $siglaImport .=  $matches[1][2];
                };
            

                $idObtida = $pais->idPorSigla($siglaImport);
                
                $trioArbitragem->pais = $idObtida;         
                
                    
			if($trioArbitragem->create()){
				$is_success = true;
			} else {
                $error_msg = 'Acusando duplicata';
            }

?>