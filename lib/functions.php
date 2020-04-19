<?php
// lib/functions.php

// attribute handling function
function adjustAttributes($isGoleiro, $nivelJogador, $marcacao, $desarme, $visaoJogo, $movimentacao, $cruzamentos, $cabeceamento, $tecnica, $controleBola, $finalizacao, $faroGol, $velocidade, $forca, $reflexos, $seguranca, $saidas, $jogoAereo, $lancamentos, $defesaPenaltis){
    
    if($isGoleiro){
        $pontosTotais = $nivelJogador * 0.50;
        $atributoMaximo = 10;
    } else {
        $pontosTotais = $nivelJogador * 0.65;
        $atributoMaximo = 7;
    }
    
    
    $arrayAtributos = array($marcacao, $desarme, $visaoJogo, $movimentacao, $cruzamentos, $cabeceamento, $tecnica, $controleBola, $finalizacao, $faroGol, $velocidade, $forca, $reflexos, $seguranca, $saidas, $jogoAereo, $lancamentos, $defesaPenaltis);
    
    $atributosTotais = array_sum($arrayAtributos);
    
    if($isGoleiro){
        if($atributosTotais == 0){
            $reflexos = 1;
            $seguranca = 1;
            $saidas = 1;
            $jogoAereo = 1;
            $lancamentos = 1;
            $defesaPenaltis = 1;
            $atributosTotais = 7;
        }
        
        $reflexos = ($reflexos / $atributosTotais) * $pontosTotais;
        $seguranca = ($seguranca / $atributosTotais) * $pontosTotais;
        $saidas = ($saidas / $atributosTotais) * $pontosTotais;
        $jogoAereo = ($jogoAereo / $atributosTotais) * $pontosTotais;
        $lancamentos = ($lancamentos / $atributosTotais) * $pontosTotais;
        $defesaPenaltis = ($defesaPenaltis / $atributosTotais) * $pontosTotais;

        
        $arrayJogador = array("reflexos" => $reflexos, "seguranca" => $seguranca, "saidas" => $saidas, "jogoAereo" => $jogoAereo, "lancamentos" => $lancamentos, "defesaPenaltis" => $defesaPenaltis);
        
    } else {
        
        if($atributosTotais == 0){
            $marcacao = 1;
            $desarme = 1;
            $visaoJogo = 1;
            $movimentacao = 1;
            $cruzamentos = 1;
            $cabeceamento = 1;
            $tecnica = 1;
            $controleBola = 1;
            $finalizacao = 1;
            $faroGol = 1;
            $velocidade = 1;
            $forca = 1;
            $atributosTotais = 13;
        }
        
        $marcacao = ($marcacao / $atributosTotais) * $pontosTotais;
        $desarme = ($desarme / $atributosTotais) * $pontosTotais;
        $visaoJogo = ($visaoJogo / $atributosTotais) * $pontosTotais;
        $movimentacao = ($movimentacao / $atributosTotais) * $pontosTotais;
        $cruzamentos = ($cruzamentos / $atributosTotais) * $pontosTotais;
        $cabeceamento = ($cabeceamento / $atributosTotais) * $pontosTotais;
        $tecnica = ($tecnica / $atributosTotais) * $pontosTotais;
        $controleBola = ($controleBola / $atributosTotais) * $pontosTotais;
        $finalizacao = ($finalizacao / $atributosTotais) * $pontosTotais;
        $faroGol = ($faroGol / $atributosTotais) * $pontosTotais;
        $velocidade = ($velocidade / $atributosTotais) * $pontosTotais;
        $forca = ($forca / $atributosTotais) * $pontosTotais;
        
        $arrayJogador = array("marcacao" => $marcacao, "desarme" => $desarme, "visaoJogo" => $visaoJogo, "movimentacao" => $movimentacao, "cruzamentos" => $cruzamentos, "cabeceamento" => $cabeceamento, "tecnica" => $tecnica, "controleBola" => $controleBola, "finalizacao" => $finalizacao, "faroGol" => $faroGol, "velocidade" => $velocidade, "forca" => $forca);
    }


     

     do {
       $resto = 0.0;
       $dividendo = 0.0;

       foreach($arrayJogador as $key => &$single_attribute){
         if($key == "velocidade" || $key == "forca"){
             $max_value = 5;
         } else {
             $max_value = $atributoMaximo;
         }
         
         if($single_attribute > $max_value){
           $resto = $resto + $single_attribute - $max_value;
           $single_attribute = $max_value;
         } else if($single_attribute < 0.5){
           $dividendo = $dividendo - (0.5 - $single_attribute);
           $single_attribute = 0.5;
         } else {
           $dividendo = $dividendo + $max_value;
         }
       }
       unset($single_attribute);

       if($resto > 0.0){
         $distribution = $resto / $dividendo;
         foreach($arrayJogador as $key => &$single_attribute){
            if($key == "velocidade" || $key == "forca"){
             $max_value = 5;
            } else {
             $max_value = $atributoMaximo;
            }
           if($single_attribute < $max_value){
             $single_attribute = $single_attribute + $distribution * $max_value;
           }
         }
         unset($single_attribute);
       }

   } while ($resto > 0.0);
    
    return $arrayJogador;
   }

//end of attribute handling function