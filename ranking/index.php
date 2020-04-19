<?php

session_start();

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Ranking de Seleções - Masculino";
$css_filename = "indexRanking";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");
include_once 'ranking_header.php';

if(isset($_GET['fed'])){
    $federacao = $_GET['fed'];
} else {
    $federacao = null;
}

?>


<div id="ranking-container">
<div align="center" id="ranking">
<h2>Ranking de seleções masculino <span id="nomeFederacao"></h2>
<h3> 15 de Junho de 2019</h3>
<hr>
<div id="federation-select">
<a href="https://confusa.top/ranking">Geral</a>
<span>  /  </span>
<a href="https://confusa.top/ranking?fed=1">FEASCO</a>
<span>  /  </span>
<a href="https://confusa.top/ranking?fed=2">FEMIFUS</a>
<span>  /  </span>
<a href="https://confusa.top/ranking?fed=3">COMPACTA</a>
</div>
<hr>

<script>

    var codFederacao = "<?php echo $federacao; ?>";
    var nomeFederacao = '';

    switch (codFederacao) {
        case '1':
            nomeFederacao = ' da FEASCO';
            break;
        case '2':
            nomeFederacao = ' da FEMIFUS';
            break;
        case '3':
            nomeFederacao = ' da COMPACTA';
            break;
        default:
            break;
    }
    $("#nomeFederacao").html(nomeFederacao);



</script>


<?php

// page given in URL parameter, default page is one
$page = isset($_GET['page']) ? $_GET['page'] : 1;

// set number of records per page
$records_per_page = 16;

// calculate for the query LIMIT clause
$from_record_num = ($records_per_page * $page) - $records_per_page;

//estabelecer conexão com banco de dados
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");

$database = new Database();
$db = $database->getConnection();

$pais = new Pais($db);
$usuario = new Usuario($db);


// query paises
if($federacao == null){
    $stmt = $pais->readAll($from_record_num, $records_per_page);
} else {
    $stmt = $pais->readFromFederation($from_record_num, $records_per_page, $federacao);
}


$num = $stmt->rowCount();



    // the page where this paging is used
    if($federacao != null){
        $page_url = "index.php?fed=" .$federacao . "&";
    } else {
        $page_url = "index.php?";
    }


    // count all products in the database to calculate total pages
    $total_rows = $pais->countAll($federacao);


    // paging buttons here
    echo "<div style='clear:both;'></div>";
    include_once($_SERVER['DOCUMENT_ROOT']."/elements/paging.php");

echo "<hr>";

// display the products if there are any
if($num>0){

    echo "<table class='table'>";
    echo "<thead>";
        echo "<tr>";
            echo "<th>Posição</th>";
            echo "<th>Time</th>";
            echo "<th>Pontos totais</th>";
           // echo "<th>Pontos anteriores</th>";
           // echo "<th>Subiu/desceu</th>";
           // echo "<th>Posições</th>";
        echo "</tr>";
    echo "</thead>";
    echo "<tbody>";

        if($page == 1){
        $pos = 0;
        } else {
        $pos = ($page-1) * $records_per_page;
        }
        $comparapontos = 0;
$pular_posicao = 0;


        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){

            extract($row);

            if($pontos <> $comparapontos){
            $pos = $pos + 1 + $pular_posicao;
$pular_posicao = 0;
            } else {
$pular_posicao++;
}



            if($ativo==0){
                $cor = "cinza";
            } else {
                $cor = "preto";
            }

            echo "<tr class=".$cor. ">";
                echo "<td>".$pos. "</td>";
                echo "<td><img src='/images/bandeiras/{$bandeira}' class='bandeira'>  <a href='./teamstatus.php?team={$id}'>{$nome}</a> </td>";
                echo "<td>{$pontos}</td>";
             //   echo "<td>{$pontos_anteriores}</td>";
              //  echo "<td>0</td>";
              //  echo "<td>0</td>";
            echo "</tr>";

            $comparapontos = $pontos;

        }

    echo "</tbody>";
    echo "</table>";

}

// tell the user there are no products
else{
    echo "<div class='alert alert-info'>Não há países</div>";
}

//echo('<td><i class="fas fa-caret-up"></i></td>');



echo('</div>');
echo('</div>');

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
