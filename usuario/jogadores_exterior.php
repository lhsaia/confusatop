<?php

ini_set( 'display_errors', true );
error_reporting( E_ALL );
session_start();

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Jogadores no exterior - ".$_SESSION['nomereal'];
$css_filename = "indexRanking";
$aux_css = "usuario";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){
?>


<div id="quadro-container">
<div align="center" id="quadroTimes">
<h2>Jogadores no exterior - <?php echo $_SESSION['nomereal']?></h2>
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
$jogador = new Jogador($db);
$pais = new Pais($db);

//query de jogadores
$stmt = $jogador->readExpat($from_record_num, $records_per_page, $_SESSION['user_id']);

$num = $stmt->rowCount();

// the page where this paging is used
$page_url = "jogadores_exterior.php?";

    // count all products in the database to calculate total pages
    $total_rows = $jogador->countExpat($_SESSION['user_id']);


    // paging buttons here
    echo "<div style='clear:both;'></div>";
    include_once($_SERVER['DOCUMENT_ROOT']."/elements/paging.php");

echo "<hr>";
echo "<div id='errorbox'></div>";

// display the products if there are any
if($num>0){

    echo "<table id='tabelaPrincipal' class='table'>";
    echo "<thead>";
        echo "<tr>";
           // echo "<th>Id</th>";
            echo "<th>Nome</th>";
            echo "<th>Nascimento (idade)</th>";
            echo "<th>Mentalidade</th>";
            echo "<th>Cobrança de Falta</th>";
            echo "<th>Valor</th>";
            echo "<th>Posições</th>";
            echo "<th>Nivel</th>";
            echo "<th>Determinação</th>";
            echo "<th class='wide'>País</th>";
            echo "<th class='wide'>Clube</th>";
            echo "<th>Status</th>";
            //echo "<th class='wide'>Emp.</th>";
            //echo "<th class='wide'>Sel.</th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";


        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){

            extract($row);

            //verificar posicoes
            $posicoes = $jogador->listaPosicoes($StringPosicoes);

            $agora = date('Y-m-d');



            $nascimento = date_format(date_create_from_format('Y-m-d', $Nascimento),'d-m-Y');

            $valorDisplay = "F$ " . ($valor/1000) . " k";

            //$escudoVinculado = explode(".",$escudoClubeVinculado);


            echo "<tr id='".$ID."'>";
                //echo "<td><span id=".$id.">{$id}</span></td>";
                echo "<td><a href='/ligas/playerstatus.php?player={$ID}' class='nomeEditavel' id='nom".$ID."'>{$Nome}</a></td>";
                echo "<td><span class='nomeEditavel' id='nas".$ID."'>{$nascimento} ({$Idade})</span></td>";
                echo "<td><span class='nomeEditavel' id='men".$ID."'>{$Mentalidade}</span></td>";
                echo "<td><span class='nomeEditavel' id='cob".$ID."'>{$CobradorFalta}</span></td>";
                echo "<td><span class='nomeEditavel' id='val".$ID."'>{$valorDisplay}</span></td>";
                echo "<td><span class='nomeEditavel' id='pos".$ID."'>{$posicoes}</span></td>";
                echo "<td><span class='nomeEditavel nivelEMod' id='niv".$ID."'>{$Nivel} ({$modificadorNivel})</span></td>";
                echo "<td><span class='nomeEditavel' id='dis".$ID."'>{$determinacaoOriginal}</span></td>";
                if($idPais != 0){
                    echo "<td class='wide'><img src='/images/bandeiras/{$bandeiraPais}' class='bandeira nomePais' id='ban".$ID."'>  <span class='nomePais' id='pai".$ID."'>{$siglaPais}</span>";
                } else {
                    echo "<td>";
                }
                echo "</td>";
                if($clubeVinculado != null){
                    echo "<td><a href='/ligas/teamstatus.php?team={$idClubeVinculado}' id='dis".$ID."'><img class='minithumb' src='/images/escudos/{$escudoClubeVinculado}'>{$clubeVinculado}</a></td>";
                } else {
                    echo "<td>";
                }
                echo "</td>";
				switch($disponibilidade){
					case -2:
						$nomeDisponibilidade = "Expatriado";
						break;
					case -1:
						$nomeDisponibilidade = "Aposentado";
						break;
					case 0:
						$nomeDisponibilidade = "Ativo";
						break;
					case 1:
						$nomeDisponibilidade = "Ativo (disponível)";
						break;
				}
                echo "<td><span class='nomeEditavel' id='niv".$ID."'>".$nomeDisponibilidade."</span></td>";
                // if($clubeEmprestimo != null){
                //     echo "<td><span class='nomeEditavel' id='dis".$id."'>{$clubeEmprestimo}</span></td>";
                // } else {
                //     echo "<td>";
                // }
                //     echo "</td>";

                // if($clubeSelecao != null){
                //     echo "<td><span class='nomeEditavel' id='dis".$id."'>{$clubeSelecao}</span></td>";
                // } else {
                //     echo "<td>";
                // }
                //     echo "</td>";
                $optionsString = "<td class='wide'>";
                if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){
                    if($jogador->testeInatividade($ID)){
                        $optionsString .= "<a id='dem".$ID."' title='Repatriar jogador' class='clickable repatriar'><i class='fas fa-plane-arrival inlineButton vermelho'></i></a>";
                    }
                            $optionsString .= "<a id='dem".$ID."' title='Incorporar modificador de nível' class='clickable incorporar'><i class='fas fa-user-plus inlineButton azul'></i></a>";

                    $optionsString .= "</td>";
                    echo $optionsString;
                }
                 echo "</tr>";

            }

    echo "</tbody>";
    echo "</table>";

}

// tell the user there are no products
else{
    echo "<div class='alert alert-info'>Não há jogadores em países de outro usuário</div>";
}

echo('</div>');
echo('</div>');

?>

<div id="modalProposta" class="modal">

  <form id='formProposta' method="POST" class="modal-content animate larger" action="/jogadores/fazer_proposta.php">
    <div class="imgcontainer">
      <span onclick="document.getElementById('modalProposta').style.display='none'" class="close" title="Close Modal">&times;</span>
    </div>

    <div class="container">
      <label for="nomeJogadorTransf"><b>Jogador</b></label>
      <input id="nomeJogadorTransf"  type="text" name="nomeJogador" disabled>

      <label for="clubeDestinoTransf"><b>Clube de destino</b></label>
      <select id="clubeDestinoTransf"  name="clubeDestinoTransf" class="form-control" required>
          <?php
      // ler times do banco de dados
                $stmt = $time->read($_SESSION['user_id']);

                echo "<option value=''>Selecione time...</option>";

                while ($row_category = $stmt->fetch(PDO::FETCH_ASSOC)){
                    extract($row_category);
                    //if($id != $idTime){
                    echo "<option value='{$id}'>{$nome}</option>";
                    //}
                }

                ?>

      </select>

      <input type="hidden" value="" name="idJogadorTransf" id="idJogadorTransf" required>

      <button type="submit" name="newsubmit" class="submitbtn">Repatriar</button>
    </div>

    <div class="container" style="background-color:#f1f1f1">
      <button type="button" onclick="document.getElementById('modalProposta').style.display='none'" class="cancelbtn">Cancelar</button>
    </div>
  </form>
</div>

<script>
$(".incorporar").click(function(){
    var tbl_row = $(this).closest('tr');
    var id = $(this).attr("id").replace(/\D/g, "");
    var nivel = tbl_row.find(".nivelEMod").html().split(" ")[0];
    var mod = tbl_row.find(".nivelEMod").html().split(" ")[1].replace(/[{()}]/g, '');
    var novoNivel = parseInt(nivel)+parseInt(mod);
    //alert(novoNivel);

        var formData = {
        'idJogador' : id,
        'novoNivel' : novoNivel,
        'alteracao' : 6
    };

     $.ajax({
            type        : 'POST', // define the type of HTTP verb we want to use (POST for our form)
            url         : '/jogadores/editar_jogador.php', // the url where we want to POST
            data        : formData, // our data object
            dataType    : 'json', // what type of data do we expect back from the server
                        encode          : true
        })

                    .done(function(data) {

// log data to the console so we can see
console.log(data);
window.scrollTo(0, 0);

if (! data.success) {
    $('#modalProposta').hide();
     $('#errorbox').append('<div class="alert alert-danger">Não foi possível realizar a repatriação, '+data.error+'</div>');


} else {

$('#modalProposta').hide();
     //$('#errorbox').append("<div class='alert alert-success'>O jogador foi repatriado com sucesso!</div>");
    location.reload();
}

// here we will handle errors and validation messages
}).fail(function(jqXHR, textStatus, errorThrown ){
    console.log("Erro");
    console.log(jqXHR);
    console.log(textStatus);
    console.log(errorThrown);
});

});



$(".repatriar").click(function(){
    var nome = $(this).closest('tr').find('.nomeEditavel:first-child').html();
    var id = $(this).attr("id").replace(/\D/g, "");
    $('#nomeJogadorTransf').val(nome);
    $("#modalProposta").show();
    $("#idJogadorTransf").val(id);
});

$("#formProposta").submit(function(event){
    var formData = {
        'idJogador' : $('input[name=idJogadorTransf]').val(),
        'idTime' : $('select[name=clubeDestinoTransf]').val(),
        'alteracao' : 5
    };

     $.ajax({
            type        : 'POST', // define the type of HTTP verb we want to use (POST for our form)
            url         : '/jogadores/editar_jogador.php', // the url where we want to POST
            data        : formData, // our data object
            dataType    : 'json', // what type of data do we expect back from the server
                        encode          : true
        })

                    .done(function(data) {

// log data to the console so we can see
console.log(data);
window.scrollTo(0, 0);

if (! data.success) {
    $('#modalProposta').hide();
     $('#errorbox').append('<div class="alert alert-danger">Não foi possível realizar a repatriação, '+data.error+'</div>');


} else {

$('#modalProposta').hide();
     //$('#errorbox').append("<div class='alert alert-success'>O jogador foi repatriado com sucesso!</div>");
    location.reload();
}

// here we will handle errors and validation messages
}).fail(function(jqXHR, textStatus, errorThrown ){
    console.log("Erro");
    console.log(jqXHR);
    console.log(textStatus);
    console.log(errorThrown);
});


    event.preventDefault();
});
</script>


<?php

} else {
    echo "Usuário, por favor refaça o login.";
}

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
