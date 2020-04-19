<?php

session_start();

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Meus parâmetros HYMT - ".$_SESSION['nomereal'];
$css_filename = "indexRanking";
$aux_css = "usuario";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin']==true){

   //coletar opções do usuário, caso existam

   //estabelecer conexão com banco de dados
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/parametros.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");

$database = new Database();
$db = $database->getConnection();

$parametro = new Parametro($db);
$pais = new Pais($db);

$opcoes = $parametro->coletarOpcoes($_SESSION['user_id']);

if($opcoes->rowCount() <> 0 ){
    $opcoesResult = $opcoes->fetch(PDO::FETCH_ASSOC);
    ($opcoesResult['mostrarSumula'] == 0 ? $mostrarSumula = '' : $mostrarSumula = 'checked');
    ($opcoesResult['VAR'] == 0 ? $VAR = '' : $VAR = 'checked');
    ($opcoesResult['limitarLesoes'] == 0 ? $limitarLesoes = '' : $limitarLesoes = 'checked');
    $porTempo = $opcoesResult['porTempo'];
    $porData = $opcoesResult['porData'];
}


?>


<div id="quadro-container">
<div align="center" id="quadroTimes">
<button id='importar_time' onclick="window.location='/usuario/criar_parametros.php';">Criar parâmetros</button>
<h2>Parâmetros HYMT - <?php echo $_SESSION['nomereal']?></h2>

<hr>

<p class='opcoesHYMT bold'>Opções</p>
<p class='opcoesHYMT'>Mostrar súmula? <br/><input id='checkboxSumulas' type='checkbox' <?php echo (isset($opcoesResult) ? $mostrarSumula : 'checked') ?> class='centeredCheckbox'/></p>
<p class='opcoesHYMT'>Utilizar VAR? <br/><input id='checkboxVAR' type='checkbox' <?php echo (isset($opcoesResult) ? $VAR : 'checked') ?> class='centeredCheckbox'/></p>
<p class='opcoesHYMT'>Limitar lesões? <br/><input id='checkboxLesoes' type='checkbox' <?php echo (isset($opcoesResult) ? $limitarLesoes : '') ?> class='centeredCheckbox'/></p>
<p class='opcoesHYMT'>Por tempo <br/><input class='form-control' min='1' max='365' value='<?php echo (isset($opcoesResult) ? $porTempo : 180) ?>' type='number' id='inputTempoLesao'/></p>
<p class='opcoesHYMT'>Por data <br/><input  class='form-control' value='<?php echo (isset($opcoesResult) ? $porData : date('Y-m-d')) ?>' type='date' id='inputDataLesao'/></p>
<p class='opcoesHYMT'><br/><input  class='form-control inlineButton' id='alterarOpcoes' type='submit' value='Alterar'/></p>

<div style='clear:both;'></div>

<hr>

<?php

// page given in URL parameter, default page is one
$page = isset($_GET['page']) ? $_GET['page'] : 1;

// set number of records per page
$records_per_page = 18;

// calculate for the query LIMIT clause
$from_record_num = ($records_per_page * $page) - $records_per_page;


// query caixa de seleção países desse dono
$stmtPais = $pais->read($_SESSION['user_id']);
$listaPaises = array();
while ($row_pais = $stmtPais->fetch(PDO::FETCH_ASSOC)){
    extract($row_pais);
    $addArray = array($id, $nome);
    $listaPaises[] = $addArray;
}

//queries de ligas e estadios

//query de ligas
$stmt = $parametro->readAll($from_record_num, $records_per_page, $_SESSION['user_id']);

$num = $stmt->rowCount();

// the page where this paging is used
$page_url = "meusparametros.php?";

    // count all products in the database to calculate total pages
    $total_rows = $parametro->countAll($_SESSION['user_id']);


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
            echo "<th width='10%'>Nome</th>";
            echo "<th width='10%'>Gols</th>";
            echo "<th width='10%'>Faltas</th>";
            echo "<th width='10%'>Impedimentos</th>";
            echo "<th width='10%'>Cartões</th>";
            echo "<th width='10%'>Estilo</th>";
            echo "<th>Selecionado</th>";
            echo "<th>País padrão</th>";
            echo "<th>Exibir bandeiras</th>";
            echo "<th width='5%'>Opções</th>";

        echo "</tr>";
        echo "</thead>";

        echo "<tbody>";


        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){

            extract($row);

            $faixaGols = $Gols * 5;
            $faixaFaltas = $Faltas * 5;
            $faixaImpedimentos = $Impedimentos * 10;
            $faixaCartoes = $Cartoes * 10;
            $faixaEstilo = ($Estilo - 3)*50;

            if($faixaEstilo > 0){
                $faixaEstiloDir = $faixaEstilo;
                $faixaEstiloEsq = 0;
            } else if($faixaEstilo < 0){
                $faixaEstiloEsq = ($faixaEstilo) * -1;
                $faixaEstiloDir = 0;
            } else {
                $faixaEstiloEsq = 5;
                $faixaEstiloDir = 5;
            }

            echo "<tr id='".$ID."'>";
                echo "<td><span class='nomeEditavel' id='nom".$ID."'>{$Nome}</span></td>";
                echo "<td><div class='meter'><span class='meter-value' id='gol".$ID."'>{$Gols}</span><span class='meter-bar' style='width: {$faixaGols}%'></span></div></td>";
                echo "<td><div class='meter'><span class='meter-value' id='fal".$ID."'>{$Faltas}</span><span class='meter-bar' style='width: {$faixaFaltas}%'></span></div></td>";
                echo "<td><div class='meter'><span class='meter-value' id='imp".$ID."'>{$Impedimentos}</span><span class='meter-bar' style='width: {$faixaImpedimentos}%'></span></div></td>";
                echo "<td><div class='meter'><span class='meter-value' id='car".$ID."'>{$Cartoes}</span><span class='meter-bar' style='width: {$faixaCartoes}%'></span></div></td>";
                echo "<td><div class='meter geral-estilo'><div class='div-chao'><span class='meter-left'>Pelo<br/>chão</span><span class='meter-split-left' style='width: {$faixaEstiloEsq}%'></span></div><div class='div-alto'><span class='meter-right'>Pelo<br/>alto</span><span class='meter-split-right' style='width: {$faixaEstiloDir}%'></span></div></div>

                <select class='comboEstilo editavel' id='{$Estilo}' hidden>
                <option value='1'>Pelo chão</option>
                <option value='2'>Mais pelo chão</option>
                <option value='3'>Intermediário</option>
                <option value='4'>Mais pelo alto</option>
                <option value='5'>Pelo alto</option>

                </select></td>";
                echo "<td><input class='checkboxSelecionado' type='checkbox' id='sel".$ID."' ". ($Selecionado == 1? 'checked disabled' : 'disabled')."/></td>";
                if($PaisPadrao != 0){
                    echo "<td class='wide'><img src='/images/bandeiras/{$bandeira}' class='bandeira nomePais' id='ban".$ID."'>  <span class='nomePais' id='pai".$ID."'>{$sigla}</span>";
                } else {
                    echo "<td>";
                }
                echo " <select class='comboPais editavel ' id='{$PaisPadrao}' hidden>'  ";
                    //echo "<option>Selecione país...</option>";
                    for($i = 0; $i < count($listaPaises);$i++){
                        echo "<option value='{$listaPaises[$i][0]}'>{$listaPaises[$i][1]}</option>";
                    }
                    echo "</select>";
                    echo "</td>";
                echo "<td><input class='checkboxBandeiras' type='checkbox' id='exi".$ID."' ". ($ExibirBandeiras == 1? 'checked disabled' : 'disabled')."/></td>";
                $optionsString = "<td class='wide'>";

                $optionsString .= "<a id='edi".$id."' title='Editar' class='clickable editar'><i class='far fa-edit inlineButton'></i></a>";
                $optionsString .= "<a hidden id='sal".$id."' title='Salvar' class='clickable salvar'><i class='fas fa-check inlineButton positive'></i></a>";
                $optionsString .= "<a hidden id='can".$id."' title='Cancelar' class='clickable cancelar'><i class='fas fa-times inlineButton negative'></i></a>";
            $optionsString .= "</td>";
            echo $optionsString;
                 echo "</tr>";

            }

    echo "</tbody>";
    echo "</table>";

}

// tell the user there are no products
else{
    echo "<div class='alert alert-info'>Não há parâmetros personalizados</div>";
}

echo('</div>');
echo('</div>');

?>

<script>

    $(document).ready(function() {

$('#checkboxLesoes').bind('change', function(){
        val = this.checked; //<---
        $("#inputTempoLesao").prop( "disabled", !val );
        $("#inputDataLesao").prop( "disabled", !val );
});

if($('#checkboxLesoes').is(':checked')){
    $("#inputTempoLesao").prop( "disabled", false );
    $("#inputDataLesao").prop( "disabled", false );
} else {
    $("#inputTempoLesao").prop( "disabled", true );
    $("#inputDataLesao").prop( "disabled", true );
}


$("#alterarOpcoes").on("click", function(e){
    var sumulas = $("#checkboxSumulas").is(':checked') ? 1 : 0;
    var VAR = $("#checkboxVAR").is(':checked') ? 1 : 0;
    var lesoes = $("#checkboxLesoes").is(':checked') ? 1 : 0;
    var porTempo = $("#inputTempoLesao").val();
    var porData =  $("#inputDataLesao").val();

    var formData = {
        'sumulas' : sumulas,
        'lesoes' : lesoes,
        'porTempo' : porTempo,
        'porData' : porData,
        'VAR' : VAR
    }

    console.log(formData);

            $.ajax({
            type        : 'POST', // define the type of HTTP verb we want to use (POST for our form)
            url         : '/usuario/alterar_opcoes.php', // the url where we want to POST
            data        : formData, // our data object
            dataType    : 'json', // what type of data do we expect back from the server
                        encode          : true
            })

                    .done(function(data) {

            // log data to the console so we can see
            console.log(data);


            if (! data.success) {
                window.scrollTo(0, 0);
            $('#modalProposta').hide();
            $('#errorbox').append('<div class="alert alert-danger">Não foi possível realizar o pedido, '+data.error+'</div>');


            } else {

            location.reload();

            }

            // here we will handle errors and validation messages
            }).fail(function(jqXHR, textStatus, errorThrown ){
            console.log("Erro");
            console.log(jqXHR);
            console.log(textStatus);
            console.log(errorThrown);
            });
e.preventDefault();
});




});

     $(document).ready(function() {

         $('.editar').click(function(){
        var tbl_row =  $(this).closest('tr');
        tbl_row.find('span').each(function(index, val){
            $(this).attr('original_entry', $(this).html());

        });

        var checkSelecionado = tbl_row.find('.checkboxSelecionado');
        var checkBandeiras = tbl_row.find('.checkboxBandeiras');
        if(checkSelecionado.is(':checked')){
            checkSelecionado.attr('original_entry', true);
        } else {
            checkSelecionado.attr('original_entry', false);
        }
        if(checkBandeiras.is(':checked')){
            checkBandeiras.attr('original_entry', true);
        } else {
            checkBandeiras.attr('original_entry', false);
        }



        tbl_row.find('.nomeEditavel').css("cursor","text");
        tbl_row.find('.nomeEditavel').attr('contenteditable', 'true').addClass('editavel');
        tbl_row.find('.salvar').show();
        tbl_row.find('.cancelar').show();
        tbl_row.find('.editar').hide();
        tbl_row.find('.nomePais').hide();
        tbl_row.find('.geral-estilo').hide();
        tbl_row.find('.checkboxSelecionado').removeAttr("disabled");
        tbl_row.find('.checkboxBandeiras').removeAttr("disabled");
        tbl_row.find('.meter-value').each(function(){
            $(this).attr('contenteditable', 'true').addClass('editavel').addClass('displace');
        });


        var paisId = tbl_row.find('.comboPais').attr('id');
        tbl_row.find('.comboPais').show().val(paisId);
        var estiloId = tbl_row.find('.comboEstilo').attr('id');
        tbl_row.find('.comboEstilo').show().val(estiloId);

    });

    $('.cancelar').click(function(){
        var tbl_row =  $(this).closest('tr');
        tbl_row.find('.checkboxSelecionado').attr("disabled", true);
        tbl_row.find('.checkboxBandeiras').attr("disabled", true);
        tbl_row.find('.meter-value').each(function(){
            $(this).attr('contenteditable', 'false').removeClass('editavel').removeClass('displace');
        });
        tbl_row.find('.nomeEditavel').attr('contenteditable', 'false').removeClass('editavel');
        tbl_row.find('.comboPais').hide();
        tbl_row.find('.comboEstilo').hide();
        tbl_row.find('.nomePais').show();
        tbl_row.find('.geral-estilo').show();
        tbl_row.find('.salvar').hide();
        tbl_row.find('.cancelar').hide();
        tbl_row.find('.editar').show();
        tbl_row.find('span').each(function(index, val){
            $(this).html($(this).attr('original_entry'));
        });


        tbl_row.find('[class^="checkbox"]').each(function(index, val){

            if($(this).attr('original_entry').localeCompare("true") == 0){
                $(this).prop("checked", true);
            } else {
                $(this).prop("checked", false);
            }

        });

    });

    $('.salvar').click(function(){
        var tbl_row =  $(this).closest('tr');
        tbl_row.find('.nomeEditavel').attr('contenteditable', 'false').removeClass('editavel');
        tbl_row.find('.comboPais').hide();
        tbl_row.find('.nomePais').show();
        tbl_row.find('.salvar').hide();
        tbl_row.find('.cancelar').hide();
        tbl_row.find('.editar').show();
        tbl_row.find('.geral-estilo').show();
        tbl_row.find('.comboEstilo').hide();
        tbl_row.find('.checkboxSelecionado').attr("disabled", true);
        tbl_row.find('.checkboxBandeiras').attr("disabled", true);
        tbl_row.find('.meter-value').each(function(){
            $(this).attr('contenteditable', 'false').removeClass('editavel').removeClass('displace');
        });

        var id = tbl_row.attr('id');
        var nome = tbl_row.find('#nom'+id).html();
        var gols = tbl_row.find('#gol'+id).html();
        var faltas = tbl_row.find('#fal'+id).html();
        var impedimentos = tbl_row.find('#imp'+id).html();
        var cartoes = tbl_row.find('#car'+id).html();
        var estilo = tbl_row.find('.comboEstilo').val();
        var pais = tbl_row.find('.comboPais').val();

        var checkSelecionado = tbl_row.find('.checkboxSelecionado');
        var checkBandeiras = tbl_row.find('.checkboxBandeiras');
        if(checkSelecionado.is(':checked')){
            var selecionado = 1;
        } else {
            var selecionado = 0;
        }
        if(checkBandeiras.is(':checked')){
            var bandeiras = 1;
        } else {
            var bandeiras = 0;
        }

        var formData = {
            'id' : id,
            'nome' : nome,
            'gols' : gols,
            'faltas' : faltas,
            'impedimentos' : impedimentos,
            'cartoes' : cartoes,
            'estilo' : estilo,
            'pais' : pais,
            'selecionado' : selecionado,
            'bandeiras' : bandeiras
        }

        // console.log(formData);

         $.ajax({
            type        : 'POST', // define the type of HTTP verb we want to use (POST for our form)
            url         : '/usuario/alterar_parametros.php', // the url where we want to POST
            data        : formData, // our data object
            dataType    : 'json', // what type of data do we expect back from the server
                        encode          : true
        })

        .done(function(data) {

        // log data to the console so we can see
        console.log(data);


        if (! data.success) {
            window.scrollTo(0, 0);
        $('#errorbox').append('<div class="alert alert-danger">Houve um erro ao alterar os parâmetros, '+data.error+'</div>');


        } else {

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

 });

</script>

<?php

} else {
    echo "Usuário, por favor refaça o login.";
}

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>
