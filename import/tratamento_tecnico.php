<?php

//  definir informações do técnico
                $tecnico->nome = (string)$xml->Nome;
                $tecnico->nascimento = (int)$xml->Idade;
                $tecnico->nivel = (int)$xml->Nivel;
                $tecnico->mentalidade = (int)$xml->Mentalidade;
                $tecnico->estilo = (int)$xml->Estilo;
                $tecnico->pais = $nacionalidadeSelecionada;

                //sem nacionalidade no momento

                $tecnico->sexo = $sexo;
                
                
                
                    
			 if($tecnico->create()){
                 
                 
                 if(isset($timeSelecionado) && $timeSelecionado != 0){
                    $codigo_tecnico = $db->lastInsertId();
                    if($tecnico->transferir($codigo_tecnico,$timeSelecionado,0,0)){
                        $is_success = true;  
                    } else {
                        $is_success = false;
                    } 
                 } else {
                    
                    $is_success = true;
                 }
			 } else {
                 $error_msg .= 'Não foi possível inserir o técnico';
             }

?>