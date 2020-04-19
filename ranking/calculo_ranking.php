<?php


echo"<html>";
echo"<head>";
echo"</head>";
echo"<body>";

include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogos.php");
include_once 'calculo_pontos.php';
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/competicao.php");

$database = new Database();
$db = $database->getConnection();

$pais = new Pais($db);
$jogo = new Jogo($db);
$competicao = new Competicao($db);


// query jogos
$stmt = $jogo->selecionarNaoCalculados();
$num = $stmt->rowCount();

// display the products if there are any
if($num>0){
 
 
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
 
            //extract($row);
            $timeA_gols = $row['timeA_gols'];
            $timeB_gols = $row['timeB_gols'];
            $id = $row['id'];
            $timeA_id = $row['timeA_id'];
            $timeB_id = $row['timeB_id'];
            $calculado = $row['calculado'];
            $idJogo = $row['campeonato'];
            $dataJogo = $row['data'];
        

            //verificar informações dos times atuais em PAISES

            $stmt2 = $pais->readOne($timeA_id);
            $value = $stmt2->fetch(PDO::FETCH_ASSOC);

            $pontosatuais_timeA = $value['pontos'];

            $stmt2 = $pais->readOne($timeB_id);
            $value = $stmt2->fetch(PDO::FETCH_ASSOC);

            
            $pontosatuais_timeB = $value['pontos'];

             


            //determinar coeficientes
            $goaldif = abs($timeA_gols - $timeB_gols);
            
            
            //determinar fator K (competição)
        
            $compStmt = $competicao->selCoeficiente($idJogo);
            $compResult = $compStmt->fetch(PDO::FETCH_ASSOC);
            $kfactor = $compResult['coeficiente'];
            
            $homefactor = $competicao->detCasaFora($idJogo);
        
            if($timeA_gols>$timeB_gols){
                   $statusjogoA = Rating::WIN;
                   $statusjogoB = Rating::LOST;
            } elseif ($timeA_gols<$timeB_gols) {
                    $statusjogoA = Rating::LOST;
                    $statusjogoB = Rating::WIN;    
             } else {
                    $statusjogoA = Rating::DRAW;
                    $statusjogoB = Rating::DRAW;
             }


            //calcular PONTOS

            $rating = new Rating($pontosatuais_timeA,$pontosatuais_timeB,$statusjogoA,$statusjogoB,$kfactor,$homefactor,$goaldif);
            $results = $rating->getNewRatings();
            $pontosnovos_timeA = $results['a'] - $pontosatuais_timeA;
            $pontosnovos_timeB = $results['b'] - $pontosatuais_timeB;

            $pontosnovos_timeA = $pontosnovos_timeA;
            $pontosnovos_timeB = $pontosnovos_timeB;

            //atualizar PAISES com RANKINGATUAL e DATA ULTIMO JOGO
            $atualizacaopaisA = $pais->atualizarPaisRanking($timeA_id, $pontosatuais_timeA, $results['a'], $dataJogo);
            $atualizacaopaisB = $pais->atualizarPaisRanking($timeB_id, $pontosatuais_timeB, $results['b'], $dataJogo);

            // atualizar JOGO com PONTOS NOVOS
            $atualizacaojogo =  $jogo->atualizarJogo($id,$pontosnovos_timeA,$pontosnovos_timeB,$pontosatuais_timeA,$pontosatuais_timeB);

        }

}

echo "Foram processados " . $num . "jogos";
echo "<br>";



$pais->atualizarAtividade();

// return button
echo "<div class='right-button-margin'>";
    echo "<a href='index.php' class='btn btn-primary pull-right'>";
        echo "<span class='glyphicon glyphicon-list'></span> Voltar para Ranking";
    echo "</a>";
echo "</div>";
echo "</body>";
echo "</html>";
 

?>

