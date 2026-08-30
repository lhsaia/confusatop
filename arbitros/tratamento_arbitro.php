<?php

//  definir informações do trio
$trioArbitragem->nomeArbitro = preg_replace("/\[[^)]+\]/","",preg_replace("/\([^)]+\)/","",(string)$xml->Arbitro[0]));
$trioArbitragem->nomeAuxiliarUm = preg_replace("/\[[^)]+\]/","",preg_replace("/\([^)]+\)/","",(string)$xml->Auxiliar1[0]));
$trioArbitragem->nomeAuxiliarDois = preg_replace("/\[[^)]+\]/","",preg_replace("/\([^)]+\)/","",(string)$xml->Auxiliar2[0]));
$trioArbitragem->estilo = (string)$xml->Estilo[0];

// Se o usuário selecionou uma nacionalidade no formulário de importação, utiliza ela
if (!empty($nacionalidadeSelecionada)) {
    $trioArbitragem->pais = $nacionalidadeSelecionada;
} else {
    // Caso contrário, tenta extrair a sigla do país entre colchetes [BRA] ou parênteses (BRA)
    $siglaImport = "";
    if(preg_match('/\[(.*)\]/', (string)$xml->Arbitro[0], $matches)){
        $siglaImport .=  $matches[1][0] ?? '';
        $siglaImport .=  $matches[1][1] ?? '';
        $siglaImport .=  $matches[1][2] ?? '';
    } else if(preg_match('/\((.*)\)/', (string)$xml->Arbitro[0], $matches)){
        $siglaImport .=  $matches[1][0] ?? '';
        $siglaImport .=  $matches[1][1] ?? '';
        $siglaImport .=  $matches[1][2] ?? '';
    }

    $idObtida = !empty($siglaImport) ? $pais->idPorSigla($siglaImport) : 0;
    $trioArbitragem->pais = $idObtida ?: 0;
}

$trioArbitragem->nivel = 0;
$trioArbitragem->nascimento = "0000-00-00";

if($trioArbitragem->create()){
    $is_success = true;
} else {
    $error_msg = 'Acusando duplicata ou erro ao cadastrar árbitro';
}

?>