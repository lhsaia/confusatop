<?php

session_start();

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

// page given in URL parameter, default page is one
$page = isset($_GET['page']) ? $_GET['page'] : 1;

// set number of records per page
$records_per_page = 15;

// calculate for the query LIMIT clause
$from_record_num = ($records_per_page * $page) - $records_per_page;

$idPais = $_GET['country'];

//estabelecer conexão com banco de dados
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogador.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/liga.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/federacoes.php");

$database = new Database();
$db = $database->getConnection();

$pais = new Pais($db);
$jogador = new Jogador($db);
$time = new Time($db);
$liga = new Liga($db);
$federacao = new Federacao($db);

// query paises
$stmt = $pais->readInfo($idPais);
$info = $stmt->fetch(PDO::FETCH_ASSOC);
$moreInfo = $pais->readMoreInfo($idPais);
$nome_selecao = $info['nome'];
$federacao_id = $info['federacao'];
$pontos = $info['pontos'];
$bandeira = $info['bandeira'];
$ativo = ($info['ativo']) ? 'ativo' : 'inativo';

//query federacao
$stmt = $federacao->selFederacao($federacao_id);
$info = $stmt->fetch(PDO::FETCH_ASSOC);
$federacao_selecao = $info['nome'];

//outras informações para infoblock
$mediaIdade = number_format($moreInfo['mediaIdade'],1);
$estrangeiros = $moreInfo['estrangeiros'];
$valor_total_clube = number_format($moreInfo['valorTotal']/1000000000,2) . "B";
$jogadores = $moreInfo['jogadores'];

$page_title = "Ligas - ".$nome_selecao;
$css_filename = "indexRanking";
$css_login = 'login';
$aux_css = 'ligas';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

echo '<div style="clear:both;"></div>';
echo '<iframe id="results_sheet" hidden></iframe>';
echo '<div style="clear:both;"></div>';
echo "<div id='quadro-container'>";
echo "<img id='bandeiraGrande' class='margin-left' src='/images/bandeiras/".$bandeira."' height='100px'>" ;
echo "<h2>" . $nome_selecao ." </h2>";
echo "<h3><a href='geral.php?fed=g".$federacao_id."'>" . $federacao_selecao ." </a></h3> ";
echo "<hr>";

//query ligas
$liga_stmt = $liga->readAll($from_record_num,$records_per_page,null,null,$idPais);

    // the page where this paging is used
    $page_url = "leaguestatus.php?country=" . $idPais . "&";

    // count all products in the database to calculate total pages
    $total_rows = $liga->countAll(null,null,$idPais);

    $perc_estrangeiros = number_format(($estrangeiros / $jogadores)*100,1)."%";

echo "<div id='info-jogos'>";
echo "<div id='times' class='infoblock' title='Quantidade de ligas'><i class='fas fa-trophy'></i><span class='informacao'>{$total_rows}</span></div>";
echo "<div id='times' class='infoblock' title='Quantidade de times'><i class='fas fa-shield-alt'></i><span class='informacao'>{$moreInfo['clubes']}</span></div>";
echo "<div id='times' class='infoblock' title='Quantidade de jogadores'><i class='fas fa-users'></i><span class='informacao'>{$jogadores}</span></div>";
echo "<div id='Idades' class='infoblock' title='Média de idade'><i class='fas fa-male'></i><span class='informacao'>{$mediaIdade}</span></div>";
echo "<div id='Estrangeiros' class='infoblock' title='Estrangeiros'><i class='fas fa-globe'></i><span class='informacao'>{$estrangeiros}</span><span class='informacao micro'>({$perc_estrangeiros})</span></div>";
echo "<div id='Valor' class='infoblock' title='Valor de mercado (em F$)'><i class='fas fa-dollar-sign'></i><span class='informacao menor'>{$valor_total_clube}</span></div>";
echo "</div>";
echo "<br>";

echo "<div style='clear:both; float:center'></div>";
echo "<hr>";
echo "<p align='center'>Ligas</p>";

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
echo "<th>Liga</th>";
echo "<th>Número de jogadores</th>";
echo "<th>Média de idade</th>";
echo "<th>Estrangeiros</th>";
echo "<th>Valor de mercado</th>";
echo "<th>Valor médio (por jogador)</th>";
echo "<th>Opções</th>";
echo "</tr>";
echo "</thead>";
echo "<tbody>";

        while ($row = $liga_stmt->fetch(PDO::FETCH_ASSOC)){

            extract($row);

            $idLiga = $row['id'];
            $info = $liga->readInfo($idLiga);

            $elencoPorTime = $info['jogadores'];
            $mediaIdadePorTime = number_format($info['mediaIdade'],1);
            $estrangeirosPorTime = $info['estrangeiros'];
            $valorMercadoPorTime = "F$ ". number_format(($info['valorTotal']/1000000),2)."M";
            $valorMedioJogador = "F$ ". number_format(($info['valorTotal']/($elencoPorTime*1000000 + 0.0000000001)),2)."M";


            echo "<tr>";
                echo "<td class='nopadding'><img class='logoliga' src='/images/ligas/".$logo."' height='30px'/><a href='leaguestatus.php?league=".$idLiga."'>{$row['nome']}</a></td>";
                echo "<td class='nopadding'>{$elencoPorTime}</td>";
                echo "<td class='nopadding'>{$mediaIdadePorTime}</td>";
                echo "<td class='nopadding'>{$estrangeirosPorTime}</td>";
                echo "<td class='nopadding'>{$valorMercadoPorTime}</td>";
                echo "<td class='nopadding'>{$valorMedioJogador}</td>";
                echo "<td><a title='Baixar liga Footscore' id='dfs ".$idLiga."' class='clickable exportarFootscore'><i class='far fa-file-excel inlineButton azul'></i></a></td>";
            echo "</tr>";

        }

        echo "</tbody>";




echo "</table>";



echo "</div>";
echo "</div>";



include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>

<script>

     $(document).ready(function() {

        $('.exportarFootscore').click(function(){
            var idLiga = $(this).attr("id").replace(/\D/g,'');
            //window.location.href = "exportar_planilha.php?idPais="+ idPais;

            var formData = new FormData();
            formData.append('codigo_liga', idLiga);

            $.ajax({
                url: '/export/export_footscore.php',
                processData: false,
               contentType: false,
               cache: false,
               type: "POST",
               dataType: 'json',
                data: formData,
                     success: function(data) {
                         document.getElementById("results_sheet").src = data.filename;
                         //location.reload();
                     },
                     error: function(data) {
                         successmessage = 'Error';
                         alert("Erro na execução da solicitação");
                         //location.reload();
                     }
                 }).fail(function(jqXHR, textStatus, errorThrown ){
                     console.log("Erro");
                     console.log(jqXHR);
                     console.log(textStatus);
                     console.log(errorThrown);
                 });
        });
        
     });
    
    
</script>
