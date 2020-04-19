<?php

session_start();

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

// page given in URL parameter, default page is one
$page = isset($_GET['page']) ? $_GET['page'] : 1;

// set number of records per page
$records_per_page = 15;

// calculate for the query LIMIT clause
$from_record_num = ($records_per_page * $page) - $records_per_page;

$id_jogador = $_GET['player'];

//estabelecer conexão com banco de dados
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogador.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/tecnico.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/liga.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/estadio.php");

$database = new Database();
$db = $database->getConnection();

$pais = new Pais($db);
$jogador = new Jogador($db);
$time = new Time($db);
$tecnico = new Tecnico($db);
$liga = new Liga($db);
$estadio = new Estadio($db);

// query times
$info = $jogador->readInfo($id_jogador);

$nome_jogador = $info['nome']; //ok entrada pagina
$pais_jogador = $info['Pais']; //ok
$time_jogador = $info['time']; //ok entrada pagina
$liga_time = $info['liga']; //ok entrada pagina
$pais_time = $info['paisTime']; //ok
$tier_liga = $info['tier']; //ok
$id_time = $info['idTime']; //ok
$id_liga = $info['idLiga']; //ok
$id_pais = $info['idPais']; //ok
$logo_liga = $info['logoLiga']; //ok
$escudo_time = $info['escudoTime']; //ok
$bandeira_pais = $info['bandeiraPais']; //ok
$idade_jogador = $info['idade']; //ok
$nascimento_jogador = $info['nascimento']; //ok
$posicoes_jogador = $info['stringPosicoes'];
$valor_jogador = $info['valor']; //ok
$salario_jogador = $info['salario']; //ok
$desde_quando = $info['inicioContrato'];
$ate_quando = $info['fimContrato'];
$nome_pais_time = $info['nomePaisTime']; //ok
$bandeira_pais_time = $info['bandeiraPaisTime']; //ok

$page_title = $nome_jogador;
$css_filename = "indexRanking";
$css_login = 'login';
$aux_css = 'ligas';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

//$escudo_time = explode(".",$escudo_time);

echo "<div id='quadro-container'>";
echo "<h2>" . $nome_jogador ." </h2>";
echo "<h3><a href='paisstatus.php?country=".$pais_time."'><img class='smallthumb' src='/images/bandeiras/{$bandeira_pais_time}'>&nbsp" . $nome_pais_time ."</a><a href='leaguestatus.php?league=".$id_liga."'> - <img class='smallthumb' src='/images/ligas/{$logo_liga}'>&nbsp" . $liga_time ." (tier {$tier_liga})</a><a href='teamstatus.php?team=".$id_time."'> - <img class='smallthumb' src='/images/escudos/{$escudo_time}'>&nbsp".$time_jogador." </a></h3> ";
echo "<hr>";

$nascimento_jogador = explode("-",$nascimento_jogador);
$nascimento_jogador = $nascimento_jogador[2] . "/" . $nascimento_jogador[1] . "/" . $nascimento_jogador[0];
$valor_jogador = $valor_jogador / 1000;
$salario_jogador = $salario_jogador / 1000;

if($ate_quando == 0){
    $ate_quando = "Indeterminado";
}

$desde_quando = explode(" ",$desde_quando);
$desde_quando = explode("-",$desde_quando[0]);
$desde_quando = $desde_quando[2] . "/" . $desde_quando[1] . "/" . $desde_quando[0];
//$posicoes_jogador = "111111111111111";

echo "<div id='info_geral'>";
 echo "<div id='info-jogos' class='info_jogador'>";
 echo "<div id='nacionalidade' class='infoblock large' title='Nacionalidade'><span class='informacao'><i class='floatleft far fa-flag'></i>{$pais_jogador}&nbsp<img class='smallthumb' src='/images/bandeiras/{$bandeira_pais}'></span></div>";
 echo "<div id='idade' class='infoblock large' title='Nascimento (idade)'><span class='informacao'><i class='floatleft fas fa-calendar-alt'></i>{$nascimento_jogador} ({$idade_jogador} anos)</span></div>";
 echo "<div id='valor' class='infoblock large' title='Valor (em F$)'><span class='informacao'><i class='floatleft fas fa-dollar-sign'></i>{$valor_jogador} k</span></div>";
 echo "<div id='salario' class='infoblock large' title='Salário (em F$)'><span class='informacao'><i class='floatleft fas fa-file-invoice-dollar'></i>{$salario_jogador} k</span></div>";
 echo "<div id='inicioContrato' class='infoblock large' title='Início do contrato'><span class='informacao'><i class='floatleft fas fa-hourglass-start'></i>{$desde_quando}</span></div>";
 echo "<div id='fimContrato' class='infoblock large' title='Fim do contrato'><span class='informacao'><i class='floatleft fas fa-hourglass-end'></i>{$ate_quando}</span></div>";
 echo "</div>";
 echo "<div id='info-desempenho-selecao'>";
 if($info['golsSelecao'] + $info['amarelosSelecao'] + $info['vermelhosSelecao'] > 0){
   echo "<span>Desempenho na seleção</span>";
   echo "<div id='golsSelecao' class='infoblock small' title='Gols'><span class='informacao'><i class='floatleft fas fa-futbol'></i>{$info['golsSelecao']}</span></div>";
   echo "<div id='amarelosSelecao' class='infoblock small' title='Amarelos'><span class='informacao'><i class='floatleft far fa-square'></i>{$info['amarelosSelecao']}</span></div>";
   echo "<div id='vermelhosSelecao' class='infoblock small' title='Vermelhos'><span class='informacao'><i class='floatleft fas fa-square'></i>{$info['vermelhosSelecao']}</span></div>";
 }
  echo "</div>";
 echo "<div id='info_posicionamento'>";
 echo "<div ".($posicoes_jogador[0] == '1'?"":" hidden ")." class='posicaoCampao posGoleiro'></div>";
 echo "<div ".($posicoes_jogador[1] == '1'?"":" hidden ")." class='posicaoCampao posLD'></div>";
 echo "<div ".($posicoes_jogador[2] == '1'?"":" hidden ")." class='posicaoCampao posLE'></div>";
 echo "<div ".($posicoes_jogador[3] == '1'?"":" hidden ")." class='posicaoCampao posZagueiro'></div>";
 echo "<div ".($posicoes_jogador[4] == '1'?"":" hidden ")." class='posicaoCampao posAE'></div>";
 echo "<div ".($posicoes_jogador[5] == '1'?"":" hidden ")." class='posicaoCampao posAD'></div>";
 echo "<div ".($posicoes_jogador[6] == '1'?"":" hidden ")." class='posicaoCampao posVolante'></div>";
 echo "<div ".($posicoes_jogador[7] == '1'?"":" hidden ")." class='posicaoCampao posME'></div>";
 echo "<div ".($posicoes_jogador[8] == '1'?"":" hidden ")." class='posicaoCampao posMD'></div>";
 echo "<div ".($posicoes_jogador[9] == '1'?"":" hidden ")." class='posicaoCampao posMeia'></div>";
 echo "<div ".($posicoes_jogador[12] == '1'?"":" hidden ")." class='posicaoCampao posArmador'></div>";
 echo "<div ".($posicoes_jogador[13] == '1'?"":" hidden ")." class='posicaoCampao posAtacanteMov'></div>";
 echo "<div ".($posicoes_jogador[14] == '1'?"":" hidden ")." class='posicaoCampao posAtacanteArea'></div>";
 echo "<div ".($posicoes_jogador[10] == '1'?"":" hidden ")." class='posicaoCampao posPE'></div>";
 echo "<div ".($posicoes_jogador[11] == '1'?"":" hidden ")." class='posicaoCampao posPD'></div>";
 echo "</div>";
 echo "</div>";

 echo "<br>";

//query transferencias jogador
$transferencias_stmt = $jogador->readTransferencias($from_record_num,$records_per_page,$id_jogador);

    // the page where this paging is used
    $page_url = "playerstatus.php?player=" . $id_jogador . "&";

    // count all products in the database to calculate total pages
    $total_rows = $jogador->countAllTransferencias($id_jogador);

echo "<div style='clear:both; float:center'></div>";
echo "<hr>";
echo "<p align='center'>Transferências</p>";

    // paging buttons here
    echo "<div style='clear:both; float:center'></div>";
    echo "<div align='center'>";
    include_once($_SERVER['DOCUMENT_ROOT']."/elements/paging.php");
    echo "</div>";
echo "<hr>";

// display the products if there are any

echo "<table id='tabelaElenco' class='table'>";
echo "<thead>";
echo "<tr>";
echo "<th>Data</th>";
echo "<th>Saiu de</th>";
echo "<th>Foi para</th>";
echo "<th>Valor</th>";
echo "</tr>";
echo "</thead>";
echo "<tbody>";

        while ($row = $transferencias_stmt->fetch(PDO::FETCH_ASSOC)){

            extract($row);

             //$escudoOrigem = explode(".",$escudoOrigem);
             //$escudoDestino = explode(".",$escudoDestino);
             $valor = $valor/1000;
             $data = explode(" ",$data);
             $data = explode("-", $data[0]);
             $data = $data[2] . "/" . $data[1] . "/" . $data[0];


            echo "<tr>";
            echo "<td class='nopadding'>{$data}</td>";
            echo "<td class='nopadding'>";
                if($idOrigem != 0){
                    echo "<a href='/ligas/teamstatus.php?team=".$idOrigem."'>";
                } else {
                echo "<span>";
                }
                echo "<img src='/images/escudos/".$escudoOrigem."' class='minithumb'/>{$nomeOrigem}";
                if($idOrigem != 0){
                    echo "</a>";
                    echo "<br/><a class='posicao' href='/ligas/leaguestatus.php?league=".$idLigaOrigem."'><img src='/images/bandeiras/{$bandeiraOrigem}' class='minithumb' id='ban".$paisOrigem."'/>{$nomeLigaOrigem}</a>";
                } else {
                    echo "</span>";
                }
                echo "</td>";
                echo "<td class='nopadding'>";
                if($idDestino != 0){
                    echo "<a href='/ligas/teamstatus.php?team=".$idDestino."'>";
                } else {
                echo "<span>";
                }
                echo "<img src='/images/escudos/".$escudoDestino."' class='minithumb'/>{$nomeDestino}";
                if($idDestino != 0){
                    echo "</a>";
                    echo "<br/><a class='posicao' href='/ligas/leaguestatus.php?league=".$idLigaDestino."'><img src='/images/bandeiras/{$bandeiraDestino}' class='minithumb' id='ban".$paisDestino."'/>{$nomeLigaDestino}</a>";
                } else {
                    echo "</span>";
                }
                echo "</td>";
                echo "<td class='nopadding'>F$ {$valor} k</td>";

            echo "</tr>";

        }

        echo "</tbody>";




echo "</table>";



echo "</div>";
echo "</div>";


include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
