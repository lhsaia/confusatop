<?php

                //criar e vincular clima
                $clima->pais = $nacionalidadeSelecionada;
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
                $estadio->pais = $nacionalidadeSelecionada;
                
                if($estadio->verificar()==0){
                    if($estadio->create()){
                        $codigo_estadio = $db->lastInsertId();
                        if(isset($timeSelecionado) && $timeSelecionado != 0){
                            $time->adicionarEstadio($codigo_estadio, $timeSelecionado);
                        }
                        $is_success = true;
                    } else {
                        $error_msg .= 'Houve erros durante a inserção do estádio. ';
                    }
                } 


?>