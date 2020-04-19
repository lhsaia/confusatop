<?php 

echo "<h3 style='padding: 10px;'>".($id != 0 ? "Adversários mais enfrentados" : "Confrontos mais ocorridos")."</h3>";
    echo "<table>";
        
        echo "<thead>";
        echo "<tr><th>".($id != 0 ? "" : "Time A")."</th><th></th><th>".($id != 0 ? "Adversário" : "Time A")."</th><th>Ocorrências</th></tr>";
        echo "</thead>";
        echo "<tbody>";
        $record_stmt = $jogo->adversariosMaisEnfrentados($id);
        while ($result = $record_stmt->fetch(PDO::FETCH_ASSOC)){
        extract($result);
        $plural = ($contagem<2 ? '' : 'es');
        echo "<tr><td  class='nopadding'>{$nomeTime}</td><td  class='nopadding'> X </td><td  class='nopadding' id='adversarioJogo'>{$nomeAdversario}</td><td class='nopadding'>{$contagem} vez{$plural}</td></tr>";
        }

        ?>
        </tbody>
    </table>
