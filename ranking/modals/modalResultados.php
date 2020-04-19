<?php 
if($id == 0){
    if($resultado_VED == 'V'){
        echo "<h3 style='padding: 10px;'>Maiores freguesias</h3>";
    } else {
        echo "<h3 style='padding: 10px;'>Duelos que mais empataram</h3>";
    }
    
} else {
    echo "<h3 style='padding: 10px;'>{$inicio_titulo} mais {$final_titulo}</h3>";
}

    echo "<table>";
        

        echo "<thead>";
        echo "<tr><th>".($id == 0 ? "Time A" : "")."</th><th></th><th>".($id == 0 ? "Time B" : "Adversário")."</th><th>Ocorrências</th></tr>";
        echo "</thead>";
        echo "<tbody>";
        $record_stmt = $jogo->maisVitoriasEmpatesDerrotas($id,$resultado_VED);
        while ($result = $record_stmt->fetch(PDO::FETCH_ASSOC)){
        extract($result);
            $plural = ($contagem<2 ? '' : 'es');
        echo "<tr><td  class='nopadding'>{$nomeTime}</td><td  class='nopadding'>". ($id == 0 ? ($resultado_VED == 'V' ? "venceu" : "empatou com") : "X") ."</td><td  class='nopadding' id='adversarioJogo'>{$nomeAdversario}</td><td class='nopadding'>{$contagem} vez{$plural}</td></tr>";
        }

        
        echo "</tbody>";
    echo "</table>";
    ?>