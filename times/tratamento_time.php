<?php

//  definir informações do time
if(!function_exists('changeName')){
    function changeName($node, $name) {

       $stringname = "<".$name."></".$name.">";
       $newnode = new SimpleXMLElement($stringname);
 
       $newnode = dom_import_simplexml($newnode);

       //$node = $newnode->ownerDocument->importNode($node, true);

       foreach ($node->childNodes as $child){
           $child = $newnode->ownerDocument->importNode($child, true);
           $newnode->appendChild($child);
       }

       return $newnode;
   }
}

                //determinação do pais
                if(isset($paisLigaSelecionada) && $paisLigaSelecionada != 0){
                    $time->pais = $paisLigaSelecionada;
                    $estadio->pais = $paisLigaSelecionada;
                    $clima->pais = $paisLigaSelecionada;
                    $tecnico->pais = $paisLigaSelecionada;
                } else {
                    $teste_pais = array();
                    foreach($xml->nacionalidades->string as $pais_provavel){
                        //$array = explode(".",$pais_provavel);
                        $array = (string)$pais_provavel;
                        //$array = mb_substr($pais_provavel, 0, 3);
                        $teste_pais[] = $array;
                    }
                    $pais_recorrente = array_count_values($teste_pais);
                    arsort($pais_recorrente);
                    $pais_real = array_slice(array_keys($pais_recorrente),0,1,true);
                    
                    $bandeiraImport = $pais_real[0];
                    $bandeiraImport = explode(".",$bandeiraImport);
                    $bandeiraImport = $bandeiraImport[0];

                    if($bandeiraImport <> '-'){
                        $idObtida = $pais->idPorBandeira($bandeiraImport);
                        if($idObtida == null){
                            $error_msg .= "Erro ao inserir o país, não foi possível determinar a nacionalidade do time!";
                            $is_success = false;
                            die(json_encode([ 'success'=> $is_success, 'error'=> $error_msg]));
                        }
                        $time->pais = $idObtida;
                        $estadio->pais = $idObtida;
                        $clima->pais = $idObtida;
                        $tecnico->pais = $idObtida;
                    } else {
                        $time->pais = 0;
                        $estadio->pais = 0;
                        $clima->pais = 0;
                        $tecnico->pais = 0;
                    }
                }

                //criar e vincular clima
                $clima->nome = (string)$xml->clima->Nome;
                $clima->tempVerao = (string)$xml->clima->TempVerao;
                $clima->estiloVerao = (string)$xml->clima->EstiloVerao;
                $clima->tempOutono = (string)$xml->clima->TempOutono;
                $clima->estiloOutono = (string)$xml->clima->EstiloOutono;
                $clima->tempInverno = (string)$xml->clima->TempInverno;
                $clima->estiloInverno = (string)$xml->clima->EstiloInverno;
                $clima->tempPrimavera = (string)$xml->clima->TempPrimavera;
                $clima->estiloPrimavera = (string)$xml->clima->EstiloPrimavera;
                $clima->hemisferio = (string)$xml->clima->Hemisferio;


                // verificar se clima já existe (mesmo nome e país)
                if($clima->verificar() == 0){
                    if($clima->create()){
                        $codigo_clima = $db->lastInsertId();
                    } else {
                        $error_msg .= 'Houve erros durante a inserção do clima. ';
                        $codigo_clima = 0;
                    }
                } else {
                    $codigo_clima = $clima->codigoPorNomeEPais();
                }

            
                //criar e vincular estadio
                $estadio->nome = (string)$xml->estadio->Nome;
                $estadio->capacidade = (int)$xml->estadio->Capacidade;
                $estadio->altitude = (string)$xml->estadio->Altitude;
                $estadio->caldeirao = (string)$xml->estadio->Caldeirao;
                $estadio->clima = $codigo_clima;       
                
                if($estadio->verificar()==0){
                    if($estadio->create()){
                        $codigo_estadio = $db->lastInsertId();
                    } else {
                        $error_msg .= 'Houve erros durante a inserção do estádio. ';
                        $codigo_estadio = 0;
                    }
                } else { 
                    $codigo_estadio = $estadio->codigoPorNomeEPais();
                }
                

                $time->nome = (string)$xml->clube->Nome;
                $time->sigla = (string)$xml->clube->TresLetras;
                $time->uniforme1cor1 = (string)$xml->clube->Uni1Cor1;
                $time->uniforme1cor2 = (string)$xml->clube->Uni1Cor2;
                $time->uniforme1cor3 = (string)$xml->clube->Uni1Cor3;
                $time->uniforme2cor1 = (string)$xml->clube->Uni2Cor1;
                $time->uniforme2cor2 = (string)$xml->clube->Uni2Cor2;
                $time->uniforme2cor3 = (string)$xml->clube->Uni2Cor3;

                //$time->escudo = (string)$xml->escudoBase64[0];
                $upload_dir = "/images/escudos/";
                $formato_arquivo = (string)$xml->formatoEscudoBase64[0];
                $conferencia_arquivo = (string)$xml->escudoBase64[0];

                if($formato_arquivo !== "null" && strlen($conferencia_arquivo) > 0){
                    $output_file = $_SERVER['DOCUMENT_ROOT'] .$upload_dir .$_SESSION['user_id'] ."-" . $time->nome . "." . $formato_arquivo;
                    $preEscudo = (string)$xml->escudoBase64[0];
                    $preEscudoDecoded = base64_decode($preEscudo);
                    $escudo_file = fopen($output_file, "wb");
                    fwrite($escudo_file, $preEscudoDecoded);
                    fclose($escudo_file);
                    $time->escudo = $_SESSION['user_id']. "-". $time->nome. "." . $formato_arquivo;
                } else {
                    $time->escudo = $time->escudoPadrao();
                }
                

                //$time->escudo .= ".";
                //$time->escudo .= (string)$xml->formatoEscudoBase64[0];
                //$time->uniforme1 = (string)$xml->uniforme1Base64[0];
                $upload_dir = "/images/uniformes/";
                $formato_arquivo = (string)$xml->formatoUniforme1Base64[0];
                if($formato_arquivo !== "null"){
                    $output_file = $_SERVER['DOCUMENT_ROOT'] .$upload_dir .$_SESSION['user_id'] ."-" . $time->nome . "-1." . $formato_arquivo;
                    $preUniforme1 = (string)$xml->uniforme1Base64[0];
                    $preUniforme1Decoded = base64_decode($preUniforme1);
                    $uniforme1_file = fopen($output_file, "wb");
                    fwrite($uniforme1_file, $preUniforme1Decoded);
                    fclose($uniforme1_file);
                    $time->uniforme1 = $_SESSION['user_id']. "-". $time->nome. "-1." . $formato_arquivo;
                } else {
                    $time->uniforme1 = $time->uniforme1Padrao();
                }
   

                //$time->uniforme1 .= ".";
                //$time->uniforme1 .= (string)$xml->formatoUniforme1Base64[0];
                //$time->uniforme2 = (string)$xml->uniforme2Base64[0];
                $upload_dir = "/images/uniformes/";
                $formato_arquivo = (string)$xml->formatoUniforme2Base64[0];
                if($formato_arquivo !== "null"){
                
                $output_file = $_SERVER['DOCUMENT_ROOT'] .$upload_dir .$_SESSION['user_id'] ."-" . $time->nome . "-2." . $formato_arquivo;
                $preUniforme2 = (string)$xml->uniforme2Base64[0];
                $preUniforme2Decoded = base64_decode($preUniforme2);
                $uniforme2_file = fopen($output_file, "wb");
                fwrite($uniforme2_file, $preUniforme2Decoded);
                fclose($uniforme2_file);
                $time->uniforme2 = $_SESSION['user_id']. "-". $time->nome. "-2." . $formato_arquivo;
                } else {
                    $time->uniforme2 = $time->uniforme2Padrao();
                }


                //$time->uniforme2 .= ".";
                //$time->uniforme2 .= (string)$xml->formatoUniforme2Base64[0];
                $time->maxTorcedores = (string)$xml->clube->MaxTorcedores;
                $time->fidelidade = (string)$xml->clube->Fidelidade;
                $time->estadio = $codigo_estadio;
                $time->liga = $ligaSelecionada;
                $time->sexo = $sexo;

			 if($time->create()){
                 $is_success = true;
                 $codigo_time = $db->lastInsertId();
			 } else {
                 $is_success = false;
                 $error_msg .= 'Houve erros durante a inserção do time, possivelmente duplicado. O processo para os times que viriam na sequência foi interrompido';
                 die(json_encode([ 'success'=> $is_success, 'error'=> $error_msg]));

             }

            //importar tecnico
            $tecnico->nome = (string)$xml->tecnico->Nome;
            $tecnico->nascimento = (int)$xml->tecnico->Idade;
            $tecnico->nivel = (int)$xml->tecnico->Nivel;
            $tecnico->mentalidade = (int)$xml->tecnico->Mentalidade;
            $tecnico->estilo = (int)$xml->tecnico->Estilo;

            if($tecnico->create()){
               $codigo_tecnico = $db->lastInsertId();
            } else {
                $error_msg .= 'Houve erros durante a inserção do tecnico. ';
            }

            $tecnico->transferir($codigo_tecnico,$codigo_time);

            //importar jogadores

            $total_de_jogadores = $xml->elenco->Jogador->int->count();
            $count_goleiro = 0;
            $array_jogadores = array();

            for($j = 0;$j<$total_de_jogadores;$j++){

             //importar e vincular jogadores
             $dom_info = dom_import_simplexml($xml->jogadores->jogador[$j]);
             $dom_nacionalidade = dom_import_simplexml($xml->nacionalidades->string[$j]);
             $dom_nacionalidade = changeName($dom_nacionalidade, 'nacionalidade');
             $dom_posicoes = dom_import_simplexml($xml->posicoesJogador->posicoes[$j]);
             $dom_posicoes = changeName($dom_posicoes, 'posicoesJogador');

            //verificacao se é goleiro ou não
            if($xml->posicoesJogador->posicoes[$j]->G == 'true'){
            $verificacao_goleiro = true;
            } else {
            $verificacao_goleiro = false;
            }

             if($verificacao_goleiro){
                $dom_atributos = dom_import_simplexml($xml->atributosGoleiro->atributosGoleiro[$count_goleiro]);
                $count_goleiro++;
             } else {
                $dom_atributos = dom_import_simplexml($xml->atributosJogador->atributosJogador[$j-$count_goleiro]);
             }
             
             //criacao de um xml de jogador
             $xmlJogador = new SimpleXMLElement("<jogadorExportacao></jogadorExportacao>");

             //adição das crianças
             $dom_jogador = dom_import_simplexml($xmlJogador);

             // Import the node, and all its children, to the document
                $dom_info = $dom_jogador->ownerDocument->importNode($dom_info, true);
                $dom_jogador->appendChild($dom_info);
                $dom_nacionalidade = $dom_jogador->ownerDocument->importNode($dom_nacionalidade, true);
                $dom_jogador->appendChild($dom_nacionalidade);
                $dom_posicoes = $dom_jogador->ownerDocument->importNode($dom_posicoes, true);
                $dom_jogador->appendChild($dom_posicoes);
                $dom_atributos = $dom_jogador->ownerDocument->importNode($dom_atributos, true);
                $dom_jogador->appendChild($dom_atributos);


             $novoXml = simplexml_import_dom($dom_jogador);

             
             
             $array_jogadores[] = $novoXml;
            }

            $capitaoId = $xml->escalacao->Capitao;

            $penaltisId = array();
            foreach($xml->escalacao->Penalti->int as $cobrador){
                $penaltisId[] = $cobrador;
            }
            $titularesPos = array();
            foreach($xml->escalacao->Pos->string as $posicaoTitular){
                $titularesPos[] = $posicaoTitular;
            }

            $titularesId = array();
            foreach($xml->escalacao->Jogador->int as $idTitular){
                $titularesId[] = $idTitular;
            }
            
             foreach($array_jogadores as $xmlIterator){

                 $xml = $xmlIterator;
                 include($_SERVER['DOCUMENT_ROOT']."/jogadores/tratamento_jogador.php");
                 $codigo_jogador = $db->lastInsertId();

                 //verificar se é capitao ou penaltis (+ posicao base)
                $idVerificacao = $xml->jogador->ID;
                if(strcmp($idVerificacao,$capitaoId) == 0){
                    $isCapitao = 1;
                } else {
                    $isCapitao = 0;
                }

                $isPenalti = 0;
                foreach($penaltisId as $numero => $cobrador){
                    if(strcmp($idVerificacao,$cobrador) == 0){
                        $isPenalti = $numero+1;
                    }
                }

                $titularidade = 0;
                $posicaoBase = '';
                foreach($titularesId as $numero => $titular){
                    if(strcmp($idVerificacao, $titular) == 0){
                        $titularidade = 1;
                        $posicaoBase = $titularesPos[$numero];
                    }
                }

                if(strcmp($posicaoBase,'') != 0){
                    $posicaoBase = $jogador->posicaoPorSigla($posicaoBase);
                }

                 //transferir
                 $jogador->transferir($codigo_jogador,$codigo_time,$isCapitao,$isPenalti,$titularidade,$posicaoBase);

             }
             

             

?>