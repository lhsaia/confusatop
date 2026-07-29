<?php

ini_set( 'display_errors', true );
error_reporting( E_ALL );
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/session.php';

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Minhas propostas de técnicos - ".($_SESSION['nomereal'] ?? '');
$css_filename = "home_redesign";
$aux_css = "propostas_redesign";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");


if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){
?>

<main class="propostas-container">
    <div id='errorbox'></div>

    <div class="propostas-card">
        <h2 class="propostas-title">Quadro de propostas de técnicos - <?php echo $_SESSION['nomereal']?></h2>

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
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/tecnico.php");

$database = new Database();
$db = $database->getConnection();

$usuario = new Usuario($db);
$time = new Time($db);
$pais = new Pais($db);
$tecnico = new Tecnico($db);

//query
$stmt = $tecnico->lerPropostasPendentes($_SESSION['user_id'],$from_record_num,$records_per_page);

$num = $stmt->rowCount();

// the page where this paging is used
$page_url = "minhaspropostastecnicos.php?";

    // count all products in the database to calculate total pages
    $total_rows = $tecnico->contarPropostas($_SESSION['user_id']);


    // paging buttons here
    echo "<div style='clear:both;'></div>";
    include_once($_SERVER['DOCUMENT_ROOT']."/elements/paging.php");

echo "<hr>";

// display the products if there are any
if($num>0){
    echo "<div id='tabelaRecebidas'>";
    echo "<table id='tabelaPrincipal' class='table'>";
    echo "<thead>";
        echo "<tr>";
            echo "<th></th>";
            echo "<th>Técnico</th>";
            echo "<th>Nivel</th>";
            echo "<th>Clube Origem</th>";
            echo "<th>Clube Destino</th>";
            echo "<th>Data Prop.</th>";
            echo "<th>Data Concl.</th>";
            echo "<th>Opções</th>";

        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";


        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){

            extract($row);
            // if($escudoOrigem != ''){
            // //$escudo1 = explode(".",$escudoOrigem);
            // } else {
            //     $escudo1 = "";
            // }
            //$escudo2 = explode(".",$escudoDestino);
            //$valor = (float)$valor/1000000;

            $formattedDataProp = date("d/m/Y H:i", strtotime($data));
            $formattedDataConcl = empty($dataConclusao) ? "" : date("d/m/Y H:i", strtotime($dataConclusao));

            echo "<tr id='".$idTransferencia."' class='tipo".$status_execucao."'>";
                echo "<td><img src='/images/icons/".$direcao.".png' width='30px' height='30px'/></td>";
                echo "<td data-label='Técnico'><span class='nomeEditavel'>{$nomeJogador}</span></td>";
                echo "<td data-label='Nível'><span class='nomeEditavel'>{$nivelJogador}</span></td>";
                echo "<td data-label='Origem'><img class='thumb' src='/images/escudos/".$escudoOrigem . "' /><span class='nomeEditavel'>{$clubeOrigem}</span></td>";
                echo "<td data-label='Destino'><img class='thumb' src='/images/escudos/".$escudoDestino . "' /><span class='nomeEditavel'>{$clubeDestino}</span></td>";
                echo "<td data-label='Data Prop.'><span class='nomeEditavel'>{$formattedDataProp}</span></td>";
                echo "<td data-label='Data Concl.'><span class='nomeEditavel'>{$formattedDataConcl}</span></td>";
                $optionsString = "<td class='wide' data-label='Opções'>";

                if($direcao == 'inbox' && $status_execucao == 0){
                    $optionsString .= "<a id='acc".$idJogador."' title='Aceitar' class='clickable aceitar'><span class='material-symbols-outlined inlineButton positivo'>check_circle</span></a>";
                    $optionsString .= "<a id='rec".$idJogador."' title='Recusar' class='clickable recusar'><span class='material-symbols-outlined inlineButton negativo'>cancel</span></a>";
                }
                    $optionsString .= "</td>";
                    echo $optionsString;


                 echo "</tr>";

            }

    echo "</tbody>";
    echo "</table>";
    echo "</div>";

}

// tell the user there are no products
else{
    echo "<div class='alert alert-info'>Não há propostas</div>";
}

echo('</div>');
echo('</main>');

?>

<script>
$(".recusar").click(function(){
    var idTransferencia = $(this).closest('tr').attr("id");
    var r = confirm("Você tem certeza que deseja cancelar essa transferência?");
    var formData = {
        "idTransferencia" : idTransferencia,
        "acao" : 'recusar'
    }
        if (r) {
            $.ajax({
                type: "POST",
                url: '/ligas/avaliar_proposta_tecnico.php',
                data: formData,
                success: function(data) {
                    successmessage = 'Deu certo'; // modificar depois
                    //$("label#successmessage").text(successmessage);
                    location.reload();
                },
                error: function(data) {
                    successmessage = 'Error';
                    alert("Erro, o procedimento não foi realizado, tente novamente.");
                }
            });
        }


});

$(".aceitar").click(function(){
    var idTransferencia = $(this).closest('tr').attr("id");
    var r = confirm("Você tem certeza que deseja aceitar essa transferência?");
    var formData = {
        "idTransferencia" : idTransferencia,
        "acao" : 'aceitar'
    }
        if (r) {
            $.ajax({
                type: "POST",
                url: '/ligas/avaliar_proposta_tecnico.php',
                data: formData,
                success: function(data) {
                    successmessage = 'Deu certo'; // modificar depois
                    //$("label#successmessage").text(successmessage);
                    location.reload();
                },
                error: function(data) {
                    successmessage = 'Error';
                    alert("Erro, o procedimento não foi realizado, tente novamente.");
                }
            });
        }


});



</script>



<?php

} else {
    echo "Usuário, por favor refaça o login.";
}

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
