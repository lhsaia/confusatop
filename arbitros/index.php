<?php

ini_set( 'display_errors', true );
error_reporting( E_ALL );
session_start();

include_once($_SERVER['DOCUMENT_ROOT']."/elements/login_info.php");

$page_title = "Quadro de árbitros - CONFUSA";
$css_filename = "indexRanking";
$aux_css = "arbitro";
$css_login = 'login';
$css_versao = date('h:i:s');
include_once($_SERVER['DOCUMENT_ROOT']."/elements/header.php");

if(isset($_GET['fed'])){
    $federacion = $_GET['fed'];
} else {
    $federacion = null;
}

?>


<div id="quadro-container">
<div align="center" id="quadroArbitro">
<h2>Quadro de árbitros <span id="nomeFederacao"></h2>
<hr>
<div id="federation-select">
<a href="https://confusa.top/arbitros">Geral</a>
<span>  /  </span>
<a href="https://confusa.top/arbitros?fed=1">FEASCO</a>
<span>  /  </span>
<a href="https://confusa.top/arbitros?fed=2">FEMIFUS</a>
<span>  /  </span>
<a href="https://confusa.top/arbitros?fed=3">COMPACTA</a>
</div>
<hr>

<script>
    //Seleção de federação
    var codFederacao = "<?php echo $federacion; ?>";
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
$records_per_page = 18;

// calculate for the query LIMIT clause
$from_record_num = ($records_per_page * $page) - $records_per_page;

//estabelecer conexão com banco de dados
include_once($_SERVER['DOCUMENT_ROOT']."/config/database.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/usuarios.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/arbitros.php");
include_once($_SERVER['DOCUMENT_ROOT']."/objetos/paises.php");

$database = new Database();
$db = $database->getConnection();

$usuario = new Usuario($db);
$arbitro = new TrioArbitragem($db);
$pais = new Pais($db);

// query caixa de seleção países
$stmtPais = $pais->read();
$listaPaises = array();
while ($row_pais = $stmtPais->fetch(PDO::FETCH_ASSOC)){
    extract($row_pais);
    $addArray = array($id, $nome);
    $listaPaises[] = $addArray;
}



// query arbitros
if($federacion == null || $federacion == 0){
    $stmt = $arbitro->readAll($from_record_num, $records_per_page);
} else {
    $stmt = $arbitro->readFromFederation($from_record_num, $records_per_page, $federacion);
}

//echo $federacion;

$number_of_referees = $stmt->rowCount();



    // the page where this paging is used
    if($federacion != null && $federacion != 0){
        $page_url = "index.php?fed=" .$federacion . "&";
    } else {
        $page_url = "index.php?";
    }



    // count all products in the database to calculate total pages
    $total_rows = $arbitro->countAll($federacion);

    //echo $number_of_referees;


    // paging buttons here
    echo "<div style='clear:both;'></div>";
    include_once($_SERVER['DOCUMENT_ROOT']."/elements/paging.php");

echo "<hr>";

// display the products if there are any
if($number_of_referees>0){

    echo "<table id='tabelaPrincipal' class='table'>";
    echo "<thead>";
        echo "<tr>";
           // echo "<th>Id</th>";
            echo "<th>Árbitro</th>";
            echo "<th>Auxiliar 1</th>";
            echo "<th>Auxiliar 2</th>";
            echo "<th>Estilo</th>";
            echo "<th class='wide'>País</th>";
            if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){
                echo "<th class='wide'>Opções</th>";
            }

        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";


        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){

            extract($row);

            $estiloComposto = [];

            switch ($estilo) {
                case 1:
                    $estiloComposto[0] = 1;
                    $estiloComposto[1] = "Gosta de deixar o jogo rolar";
                    break;
                case 2:
                    $estiloComposto[0] = 2;
                    $estiloComposto[1] = "Prefere conversar a dar cartões";
                    break;
                case 3:
                    $estiloComposto[0] = 3;
                    $estiloComposto[1] = "Moderado";
                    break;
                case 4:
                    $estiloComposto[0] = 4;
                    $estiloComposto[1] = "Rígido";
                    break;
                case 5:
                    $estiloComposto[0] = 5;
                    $estiloComposto[1] = "Carrasco";
                    break;
            }

            echo "<tr id='".$id."'>";
                //echo "<td><span id=".$id.">{$id}</span></td>";
                echo "<td><span class='nomeEditavel' id='nom".$id."'>{$nomeArbitro}</span></td>";
                echo "<td><span class='nomeEditavel' id='pax".$id."'>{$nomeAuxiliarUm}</span></td>";
                echo "<td><span class='nomeEditavel' id='sax".$id."'>{$nomeAuxiliarDois}</span></td>";
                echo "<td><span class='nomeEstilo' id='est".$id."'>{$estiloComposto[1]}</span>".
                        "<select class='comboEstilo editavel' id='{$estiloComposto[0]}' hidden>".
                            "<option value='1'><span>Gosta de deixar o jogo rolar</span></option>".
                            "<option value='2'>Prefere conversar a dar cartões</option>".
                            "<option value='3'>Moderado</option>".
                            "<option value='4'>Rígido</option>".
                            "<option value='5'>Carrasco</option>".
                        "</select></td>";
                if($siglaPais != ''){
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

                $optionsString = "<td class='wide'>";

                if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true){
                    if($_SESSION['admin_status'] == '1' || $_SESSION['user_id'] === $idDonoPais){
                        $optionsString .= "<a id='edi".$id."' title='Editar' class='clickable editar'><i class='far fa-edit inlineButton'></i></a>";
                        $optionsString .= "<a hidden id='sal".$id."' title='Salvar' class='clickable salvar'><i class='fas fa-check inlineButton positive'></i></a>";
                        $optionsString .= "<a hidden id='can".$id."' title='Cancelar' class='clickable cancelar'><i class='fas fa-times inlineButton negative'></i></a>";
                    }
                    if($_SESSION['admin_status'] == '1'){
                        $optionsString .= "<a id='del".$id."' title='Deletar' class='clickable deletar'><i class='far fa-trash-alt inlineButton negative'></i></a>";
                    }
                    $optionsString .= "<a id='exp".$id."' title='Exportar' class='clickable exportar'><i class='fas fa-file-export inlineButton'></i></a>";
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
    echo "<div class='alert alert-info'>Não há árbitros no quadro</div>";
}

echo('</div>');
echo('</div>');

include_once($_SERVER['DOCUMENT_ROOT']."/elements/footer.php");

?>

<script>

    // teste de mudança automática de página

window.onscroll = function(ev) {
    if (Math.floor(-(window.innerHeight + window.scrollY) + document.body.offsetHeight) == 0) {

    }
};


        //Exportar árbitro

    $('.exportar').click(function(){

        var digitosId = ($(this).attr('id')).length;
        var arbitroId = ($(this).attr('id')).substring(3,digitosId);
        var siglaPais = $("#pai"+arbitroId).html();
        var nomeArbitro = $("#nom"+arbitroId).html()+" ["+siglaPais+"]";
        var nomeAux1 = $("#pax"+arbitroId).html()+" ["+siglaPais+"]";
        var nomeAux2 = $("#sax"+arbitroId).html()+" ["+siglaPais+"]";
        
        // alterado em 09/11/19 para corrigir exportação
        var tbl_row =  $(this).closest('tr');
        var estilo = tbl_row.find('.comboEstilo').attr('id');
        
        //var estilo = $("#est"+arbitroId).html();
        //var pais = $("#pai"+arbitroId).html();

        var xmlData = "<trioArbitragem>\n  <ID>"+
        arbitroId+"</ID>\n  <Arbitro>"+
        nomeArbitro+"</Arbitro>\n  <Auxiliar1>"+
        nomeAux1+"</Auxiliar1>\n  <Auxiliar2>"+
        nomeAux2+"</Auxiliar2>\n  <Estilo>"+
        estilo+"</Estilo>\n"+
        //"  <Pais>"+pais+"</Pais>\n"+ // para futura compatibilidade com países
        "</trioArbitragem>";

        var fileName = "TA_-_"+(nomeArbitro.replace(/ /g,"_")).replace(/[!@#$%^()\[\]&*]/g,"")+".tda";

        function download(filename, text) {
            var element = document.createElement('a');
            element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
            element.setAttribute('download', filename);

            element.style.display = 'none';
            document.body.appendChild(element);

            element.click();

            document.body.removeChild(element);
        }

        download(fileName,xmlData);
    });

    $('.deletar').click(function(){
        var digitosId = ($(this).attr('id')).length;
        var arbitroId = ($(this).attr('id')).substring(3,digitosId);
        var r = confirm("Você tem certeza que deseja apagar esse trio?");
        if (r) {
            $.ajax({
                type: "POST",
                url: 'apagar_arbitro.php',
                data: {arbitroId:arbitroId},
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

    $('.editar').click(function(){
        var tbl_row =  $(this).closest('tr');
        tbl_row.find('span').each(function(index, val){
            $(this).attr('original_entry', $(this).html());

        });
        tbl_row.find('.nomeEditavel').attr('contenteditable', 'true').addClass('editavel');
        tbl_row.find('.salvar').show();
        tbl_row.find('.cancelar').show();
        tbl_row.find('.editar').hide();
        tbl_row.find('.deletar').hide();
        tbl_row.find('.exportar').hide();
        tbl_row.find('.nomeEstilo').hide();
        tbl_row.find('.nomePais').hide();

        var selectId = tbl_row.find('.comboEstilo').attr('id');
        tbl_row.find('.comboEstilo').show().val(selectId);

        var paisId = tbl_row.find('.comboPais').attr('id');
        tbl_row.find('.comboPais').show().val(paisId);

    });

    $('.cancelar').click(function(){
        var tbl_row =  $(this).closest('tr');
        tbl_row.find('.nomeEditavel').attr('contenteditable', 'false').removeClass('editavel');
        tbl_row.find('.comboEstilo').hide();
        tbl_row.find('.nomeEstilo').show();
        tbl_row.find('.comboPais').hide();
        tbl_row.find('.nomePais').show();
        tbl_row.find('.salvar').hide();
        tbl_row.find('.cancelar').hide();
        tbl_row.find('.editar').show();
        tbl_row.find('.deletar').show();
        tbl_row.find('.exportar').show();

        tbl_row.find('span').each(function(index, val){
            $(this).html($(this).attr('original_entry'));
        });
    });

    $('.salvar').click(function(){
        var tbl_row =  $(this).closest('tr');
        tbl_row.find('.nomeEditavel').attr('contenteditable', 'false').removeClass('editavel');
        tbl_row.find('.comboEstilo').hide();
        tbl_row.find('.nomeEstilo').show();
        tbl_row.find('.comboPais').hide();
        tbl_row.find('.nomePais').show();
        tbl_row.find('.salvar').hide();
        tbl_row.find('.cancelar').hide();
        tbl_row.find('.editar').show();
        tbl_row.find('.deletar').show();
        tbl_row.find('.exportar').show();

        var id = tbl_row.attr('id');
        var nomeArbitro = tbl_row.find('#nom'+id).html();
        var nomeAux1 = tbl_row.find('#pax'+id).html();
        var nomeAux2 = tbl_row.find('#sax'+id).html();
        var estilo = tbl_row.find('.comboEstilo').val();
        var pais = tbl_row.find('.comboPais').val();

        var pacoteArbitro = {id,nomeArbitro,nomeAux1,nomeAux2,estilo,pais};
        var data = JSON.stringify(pacoteArbitro);

        $.ajax({
            type: "POST",
            url: 'alterar_arbitro.php',
            data: {data:data},
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
    });

</script>
