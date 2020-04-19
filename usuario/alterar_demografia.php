<?php

session_start();

include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");

$database = new Database();
$db = $database->getConnection();

$pais = new Pais($db);
$pais->id = $_GET['idPais'];

$pais->readName();

$nomePagina = 'Demografia - ' . $pais->nome;
$stmt = $pais->demografias();

$num = $stmt->rowCount();

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = $nomePagina;
$css_filename = "indexRanking";
$aux_css = "usuario";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true && $pais->checarDono($pais->id,$_SESSION['user_id'])){



?>

<div id="quadro-container">
<div align="center" id="quadroTimes">
<button id='importar_time' onclick="window.location='/usuario/criar_demografia.php?idTime=<?php echo $pais->id ?>';">Criar demografia</button>
<h2><?php echo $nomePagina?></h2>
<div id='error_box'></div>

<hr>

<?php

echo "<div style='clear:both;'></div>";


echo "<hr>";

// display the products if there are any
if($num>0){

    echo "<table id='tabelaPrincipal' class='table'>";
    echo "<thead id='thead".$pais->id."'>";
        echo "<tr>";
            echo "<th>Origem</th>";
            echo "<th>Percentual da população (%)</th>";
            echo "<th>Ocorrência de nome duplo (%)</th>";
            echo "<th>Índice de miscigenação (%)</th>";
            echo "<th>Nome ou sobrenome?</th>";
            echo "<th>Opções</th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";

        $somaPercentualNome = 0;
        $somaPercentualSobrenome = 0;
        $arrayNomes = array();
        $arraySobrenomes = array();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
            extract($row);

            if($nomeOuSobrenome == 10){
                $somaPercentualNome = $somaPercentualNome + $fatorPercentual;
                $arrayNomes[] = ['origem' => $origem, 'fatorPercentual' => $fatorPercentual, 'idOrigem' => $idOrigem, 'ocorrenciaNomeDuplo' => $ocorrenciaNomeDuplo, 'indiceMiscigenacao' => $indiceMiscigenacao];
            } else if($nomeOuSobrenome == 1){
                $somaPercentualSobrenome = $somaPercentualSobrenome + $fatorPercentual;
                $arraySobrenomes[] = ['origem' => $origem, 'fatorPercentual' => $fatorPercentual, 'idOrigem' => $idOrigem];
            } else {
                $somaPercentualNome = $somaPercentualNome + $fatorPercentual;
                $somaPercentualSobrenome = $somaPercentualSobrenome + $fatorPercentual;
                $arrayNomes[] = ['origem' => $origem, 'fatorPercentual' => $fatorPercentual, 'idOrigem' => $idOrigem, 'ocorrenciaNomeDuplo' => $ocorrenciaNomeDuplo, 'indiceMiscigenacao' => $indiceMiscigenacao];
                $arraySobrenomes[] = ['origem' => $origem, 'fatorPercentual' => $fatorPercentual, 'idOrigem' => $idOrigem];
            }
            }

            foreach($arrayNomes as $novoNome){

                //nomes
                $percentualNormal = round(($novoNome['fatorPercentual'] / $somaPercentualNome)*100,0);
                echo "<tr>";
                echo "<td class='nopadding idOrigem' id='origem".$novoNome['idOrigem']."'>{$novoNome['origem']}</td>";
                echo "<td class='nopadding idPerc'>{$percentualNormal} %</td>";
                echo "<td class='nopadding idNomDup'>{$novoNome['ocorrenciaNomeDuplo']} %</td>";
                echo "<td class='nopadding idMisc'>{$novoNome['indiceMiscigenacao']} %</td>";
                echo "<td class='nopadding idNome'>Nomes</td>";
                $optionsString = "<td class='wide'>";

                        $optionsString .= "<a title='Apagar' class='clickable apagar'><i class='fas fa-trash-alt inlineButton negative'></i></a>";
                    $optionsString .= "</td>";
                    echo $optionsString;
                echo "</tr>";
            }
            foreach($arraySobrenomes as $novoSobrenome){

                //nomes
                $percentualNormal = round(($novoSobrenome['fatorPercentual'] / $somaPercentualSobrenome)*100,0);
                echo "<tr>";
                echo "<td class='nopadding idOrigem' id='origem".$novoSobrenome['idOrigem']."'>{$novoSobrenome['origem']}</td>";
                echo "<td class='nopadding idPerc'>{$percentualNormal} %</td>";
                echo "<td class='nopadding idNomDup'> - </td>";
                echo "<td class='nopadding idMisc'> - </td>";
                echo "<td class='nopadding idNome'>Sobrenomes</td>";
                $optionsString = "<td class='wide'>";

                $optionsString .= "<a title='Apagar' class='clickable apagar'><i class='fas fa-trash-alt inlineButton negative'></i></a>";
            $optionsString .= "</td>";
            echo $optionsString;
                echo "</tr>";
            }

    echo "</tbody>";
    echo "</table>";

    } else {
        echo "<div class='alert alert-info'>Não há demografias para o país selecionado</div>";
    }

} else {
    echo "<div class='alert alert-info'>Usuário sem permissão para realizar essa ação</div>";
}

?>
<script>

$(".apagar").on("click", function(){
    var tbl_row = $(this).closest("tr");
    //var identificador = tbl_row.find(".idNome").html();
    var origem = tbl_row.find(".idOrigem").attr("id").replace(/\D/g,'');
    var pais = $("thead").attr("id").replace(/\D/g,'');
   // console.log(origem + " " + pais);
    var r = confirm("Você tem certeza que deseja apagar essa fatia da demografia?");
        if (r) {

    var formData = {
        'origem' : origem,
        'pais' : pais
    }

     $.ajax({
            type        : 'POST', // define the type of HTTP verb we want to use (POST for our form)
            url         : '/usuario/apagar_demografia.php', // the url where we want to POST
            data        : formData, // our data object
            dataType    : 'json', // what type of data do we expect back from the server
                        encode          : true
            })

                    .done(function(data) {

            // log data to the console so we can see
            console.log(data);


            if (data.success) {
                location.reload();
                //$('#error_box').append('<div class="alert alert-success">Exclusão executada com sucesso!</div>');
            } else {
                $('#error_box').append('<div class="alert alert-danger">Não foi possível realizar a exclusão, '+data.error+'</div>');
            }


            // here we will handle errors and validation messages
            }).fail(function(jqXHR, textStatus, errorThrown ){
            console.log("Erro");
            console.log(jqXHR);
            console.log(textStatus);
            console.log(errorThrown);
            });
        }




});


</script>

<?php


include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
