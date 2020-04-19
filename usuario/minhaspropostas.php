<?php

// ini_set( 'display_errors', true );
// error_reporting( E_ALL );
session_start();

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Minhas propostas de jogadores - ".$_SESSION['nomereal'];
$css_filename = "indexRanking";
$aux_css = "usuario";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");


if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){
?>

<div style='clear:both; float:center'></div>
<div id='errorbox'></div>

<div id="quadro-container">
<div align="center" id="quadroTimes">
<h2>Quadro de propostas de jogadores - <?php echo $_SESSION['nomereal']?></h2>

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
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/jogador.php");

$database = new Database();
$db = $database->getConnection();

$usuario = new Usuario($db);
$time = new Time($db);
$pais = new Pais($db);
$jogador = new Jogador($db);

//query
$stmt = $jogador->lerPropostasPendentes($_SESSION['user_id'],$from_record_num,$records_per_page);

$num = $stmt->rowCount();

// the page where this paging is used
$page_url = "minhaspropostas.php?";

    // count all products in the database to calculate total pages
    $total_rows = $jogador->contarPropostas($_SESSION['user_id']);


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
            echo "<th>Jogador</th>";
            echo "<th>Nivel</th>";
            echo "<th>Clube Origem</th>";
            echo "<th>Clube Destino</th>";
            echo "<th>Valor</th>";
            echo "<th>Tipo</th>";
            echo "<th>Encerramento</th>";
            echo "<th>Opções</th>";

        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";


        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){

            extract($row);
            
            if($emprestimo == 0){
                $tipoTransacao = "Venda";
            } else {
                $tipoTransacao = "Empréstimo";
            }
            
            if($encerramento == "0000-00-00"){
                $encerramentoContrato = "-";
            } else {
                $encerramentoContrato = $encerramento;
            }
            
            
            // if($escudoOrigem != ''){
            // //$escudo1 = explode(".",$escudoOrigem);
            // } else {
            //     $escudo1 = "";
            // }
            //$escudo2 = explode(".",$escudoDestino);
            $valor = (float)$valor/1000000;

            echo "<tr id='".$idTransferencia."' class='tipo".$status_execucao."'>";
                echo "<td><img src='/images/icons/".$direcao.".png' width='30px' height='30px'/></td>";
                echo "<td><span class='nomeEditavel'>{$nomeJogador}</span></td>";
                echo "<td><span class='nomeEditavel'>{$nivelJogador}</span></td>";
                echo "<td><img class='thumb' src='/images/escudos/".$escudoOrigem . "' />";
                if($idClubeOrigem != 0){
                  echo "<a href='/ligas/teamstatus.php?team={$idClubeOrigem}'>";
                }
                echo "<span class='nomeEditavel'> {$clubeOrigem}";
                if($idClubeOrigem != 0){
                  echo "</a>";
                }
                echo "</span></td>";
                echo "<td><img class='thumb' src='/images/escudos/".$escudoDestino . "' /><a href='/ligas/teamstatus.php?team={$idClubeDestino}' class='nomeEditavel'>{$clubeDestino}</a></td>";
                echo "<td><span class='nomeEditavel'>F$ {$valor} M</span></td>";
                echo "<td><span class='nomeEditavel'>{$tipoTransacao}</span></td>";
                echo "<td><span class='nomeEditavel'>{$encerramentoContrato}</span></td>";
                $optionsString = "<td class='wide'>";

                if($direcao == 'inbox' && $status_execucao == 0){
                    $optionsString .= "<a id='acc".$idJogador."' title='Aceitar' class='clickable aceitar'><i class='far fa-check-circle inlineButton positivo'></i></a>";
                    $optionsString .= "<a id='rec".$idJogador."' title='Recusar' class='clickable recusar'><i class='far fa-times-circle inlineButton negativo'></i></a>";
                    $optionsString .= "<a id='con".$idJogador."' title='Oferecer contraproposta' class='clickable contrapropor'><i class='far fa-question-circle inlineButton amarelo'></i></a>";
                } else if($direcao == 'outbox' && $status_execucao == 2){
                    $optionsString .= "<a id='acc".$idJogador."' title='Aceitar contraproposta' class='clickable aceitar'><i class='far fa-check-circle inlineButton positivo'></i></a>";
                    $optionsString .= "<a id='rec".$idJogador."' title='Recusar contraproposta' class='clickable recusar'><i class='far fa-times-circle inlineButton negativo'></i></a>";
                } else if($direcao == 'outbox' && $status_execucao == 0){
                    $optionsString .= "<a id='rec".$idJogador."' title='Cancelar proposta' class='clickable recusar'><i class='far fa-times-circle inlineButton negativo'></i></a>";
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
echo('</div>');

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
                url: '/jogadores/avaliar_proposta.php',
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

$(".contrapropor").click(function(){
    var idTransferencia = $(this).closest('tr').attr("id");
    var valorInicial = $(this).closest('tr').find('td:nth(5)').text();
    valorInicial = valorInicial.split(" ");
    valorInicial = parseFloat(valorInicial[1]);
    valorInicial = valorInicial*1000000;
    var r = prompt("Defina o valor da contraproposta:", valorInicial.toString());
     var formData = {
         "idTransferencia" : idTransferencia,
         "valor" : r,
         "acao" : 'contrapropor'
     }
         if (r) {
             $.ajax({
                 type: "POST",
                 url: '/jogadores/avaliar_proposta.php',
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
                url: '/jogadores/avaliar_proposta.php',
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
