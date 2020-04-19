<?php
echo "<h3>Recordes de pontos em jogos</h3>";
  if($id != 0){
    echo "<h4>Melhores</h4>";
    } 
    ?>
    <table>
        <?php 

        echo "<thead>";
        echo "<tr><th></th><th></th><th></th><th></th><th></th><th>Data</th><th>Campeonato</th><th>Pontos</th></tr>";
        echo "</thead>";
        echo "<tbody>";
        $record_stmt = $jogo->ganhoPontos($id,'1');
        while ($result = $record_stmt->fetch(PDO::FETCH_ASSOC)){
        extract($result);
    
        echo "<tr><td class='nopadding'>{$nomeTime}</td><td class='nopadding'>{$timeGols}</td><td class='nopadding'>X</td><td class='nopadding'>{$adversarioGols}</td><td class='nopadding'>{$nomeAdversario}</td><td>{$data}</td><td>{$nomeCampeonato}</td><td>{$pontos}</td></tr>";
        }

        ?>
        </tbody>
    </table>

    <?php if($id != 0){
    echo "<br>";
    echo "<h4>Piores</h4>";
    echo "<table>";
        echo "<thead>";
        echo "<tr><th></th><th></th><th></th><th></th><th></th><th>Data</th><th>Campeonato</th><th>Pontos</th></tr>";
        echo "</thead>";
        echo "<tbody>";
        $record_stmt = $jogo->ganhoPontos($id,'0');
        while ($result = $record_stmt->fetch(PDO::FETCH_ASSOC)){
        extract($result);

        echo "<tr><td>{$nomeTime}</td><td>{$timeGols}</td><td>X</td><td>{$adversarioGols}</td><td>{$nomeAdversario}</td><td>{$data}</td><td>{$nomeCampeonato}</td><td>{$pontos}</td></tr>";
        }

        
      echo "</tbody>";
    echo "</table>";
    } 
    ?>
    
