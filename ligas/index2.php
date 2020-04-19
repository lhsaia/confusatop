<?php

ini_set( 'display_errors', true );
error_reporting( E_ALL );
session_start();

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Ligas";
$css_filename = "indexRanking";
$aux_css = "usuario";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

?>


<div id="quadro-container">
<div align="center" id="quadroTimes">
<h2>Quadro de ligas</h2>

<hr>

<?php

// page given in URL parameter, default page is one
$page = isset($_GET['page']) ? $_GET['page'] : 1;

// set number of records per page
$records_per_page = 18;

// calculate for the query LIMIT clause
$from_record_num = ($records_per_page * $page) - $records_per_page;

//estabelecer conexão com banco de dados
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/time.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/liga.php");

$database = new Database();
$db = $database->getConnection();

$usuario = new Usuario($db);
$time = new Time($db);
$pais = new Pais($db);
$liga = new Liga($db);

// query caixa de seleção países desse dono
$stmtPais = $pais->read();
$listaPaises = array();
while ($row_pais = $stmtPais->fetch(PDO::FETCH_ASSOC)){
    extract($row_pais);
    $addArray = array($id, $nome);
    $listaPaises[] = $addArray;
}

//queries de ligas e estadios

//query de ligas
$stmt = $liga->readAll($from_record_num, $records_per_page);

$num = $stmt->rowCount();

// the page where this paging is used
$page_url = "minhasligas.php?";

    // count all products in the database to calculate total pages
    $total_rows = $liga->countAll();


    // paging buttons here
    echo "<div style='clear:both;'></div>";
    include_once($_SERVER['DOCUMENT_ROOT']."/elements/paging.php");

echo "<hr>";

// display the products if there are any
if($num>0){

    echo "<table id='tabelaPrincipal' class='table'>";
    echo "<thead>";
        echo "<tr>";
           // echo "<th>Id</th>";
            echo "<th>Liga</th>";
            echo "<th>Logo</th>";
            echo "<th>Tier</th>";
            echo "<th class='wide'>País</th>";

        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";


        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){

            extract($row);

            echo "<tr id='".$id."'>";
                //echo "<td><span id=".$id.">{$id}</span></td>";
                echo "<td><a class='nomeEditavel' href='leaguestatus.php?league=".$id."' id='nom".$id."'>{$nome}</a></td>";
                echo "<td></td>";
                echo "<td><span class='nomeEditavel' id='tie".$id."'>{$tier}</span></td>";
                if($idPais != 0){
                    echo "<td class='wide'><img src='/images/bandeiras/{$bandeiraPais}' class='bandeira nomePais' id='ban".$id."'>  <span class='nomePais' id='pai".$id."'>{$siglaPais}</span>";
                } else {
                    echo "<td>";
                }
                echo " <select class='comboPais editavel ' id='{$idPais}' hidden>'  ";
                    echo "<option>Selecione país...</option>";
                    for($i = 0; $i < count($listaPaises);$i++){
                        echo "<option value='{$listaPaises[$i][0]}'>{$listaPaises[$i][1]}</option>";
                    }
                    echo "</select>";
                    echo "</td>";

                 echo "</tr>";

            }

    echo "</tbody>";
    echo "</table>";

}

// tell the user there are no products
else{
    echo "<div class='alert alert-info'>Não há times na liga</div>";
}

echo('</div>');
echo('</div>');

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
