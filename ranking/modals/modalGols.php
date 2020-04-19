<?php 

echo "<h3 style='padding: 10px;'>Maiores goleadas {$titulo}</h3>";
    echo "<table>";
        

    echo "<thead>";
    echo "<tr><th></th><th></th><th></th><th></th><th></th><th>Data</th><th>Campeonato</th></tr>";
    echo "</thead>";
    echo "<tbody>";
    if($goleadasAplicadas == 1){
        $record_stmt = $jogo->maioresVitorias($id);
    } else {
        $record_stmt = $jogo->maioresDerrotas($id);
    }
    
    while ($result = $record_stmt->fetch(PDO::FETCH_ASSOC)){
    extract($result);

    echo "<tr><td class='nopadding'>{$nomeTime}</td><td class='nopadding'>{$timeGols}</td><td class='nopadding'>X</td><td class='nopadding'>{$adversarioGols}</td><td class='nopadding'>{$nomeAdversario}</td><td>{$data}</td><td>{$nomeCampeonato}</td></tr>";
    }

        
        echo "</tbody>";
    echo "</table>";
    ?>